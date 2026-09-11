<?php

namespace App\Http\Controllers;

use App\Models\AiAlert;
use App\Models\ClinicalTimeline;
use App\Models\MedicationAdministrationRecord;
use App\Models\MedicineInventory;
use App\Models\MedicineTransaction;
use App\Models\NurseTask;
use App\Models\Resident;
use App\Models\ResidentMedication;
use App\Services\ClinicalTimelineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicationAdministrationController extends Controller
{
    private const OUTCOMES = [
        'COMPLETED',
        'HELD',
        'REFUSED',
        'UNAVAILABLE',
        'MISSED',
        'DELAYED',
    ];

    /*
    |--------------------------------------------------------------------------
    | Get Resident Medication Schedule
    |--------------------------------------------------------------------------
    |
    | Read-only. This endpoint never creates tasks, notifications, stock
    | transactions, administration records, or timeline entries.
    |
    */

    public function getSchedule($id)
    {
        $resident = Resident::findOrFail($id);

        $medications = ResidentMedication::with('medication')
            ->where('resident_id', $id)
            ->where(function ($query) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', today());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->orderBy('time_slot')
            ->orderBy('scheduled_time')
            ->get();

        $schedule = [
            'AM' => [],
            'PM' => [],
            'NIGHT' => [],
            'OTHER' => [],
        ];

        foreach ($medications as $medication) {
            $latestRecord = MedicationAdministrationRecord::where(
                    'resident_medication_id',
                    $medication->id
                )
                ->whereDate('administered_date', today())
                ->latest('created_on')
                ->first();

            $slot = strtoupper((string) $medication->time_slot);

            if (!isset($schedule[$slot])) {
                $slot = 'OTHER';
            }

            $schedule[$slot][] = [
                'id' => $medication->id,
                'resident_medication_id' => $medication->id,
                'medication_id' => $medication->medication_id,
                'medicine' => $medication->medication?->medicine_name,
                'dosage' => $medication->medication?->dosage,
                'unit' => $medication->medication?->unit,
                'instruction' => $medication->dosage_instruction,
                'quantity' => $medication->dosage_quantity,
                'frequency' => $medication->frequency,
                'time_slot' => $slot,
                'scheduled_time' => $medication->scheduled_time,
                'start_date' => $medication->start_date,
                'end_date' => $medication->end_date,

                'status' => $latestRecord?->status ?? 'PENDING',
                'administration_record_id' => $latestRecord?->id,
                'completed_time' => $latestRecord?->completed_time,
                'completed_by' => $latestRecord?->completed_by,
                'remarks' => $latestRecord?->remarks,

                'meal_type' => $latestRecord?->meal_type
                    ?? $this->mealForSlot($slot),
                'meal_confirmed' => (bool) ($latestRecord?->meal_confirmed ?? false),
                'meal_confirmed_at' => $latestRecord?->meal_confirmed_at,
                'meal_notes' => $latestRecord?->meal_notes,
            ];
        }

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'name' => $resident->full_name,
                'status' => $resident->status,
            ],
            'can_administer' => $resident->status === 'Active',
            'schedule' => $schedule,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Record Medication Outcome
    |--------------------------------------------------------------------------
    |
    | Kept as complete() for backward compatibility with the existing route.
    | If status is omitted, the request behaves exactly like the old endpoint
    | and records COMPLETED.
    |
    */

    public function complete(
        Request $request,
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $validated = $request->validate([
            'status' =>
                'nullable|in:' . implode(',', self::OUTCOMES),

            'remarks' =>
                'nullable|string|max:2000',

            'meal_confirmed' =>
                'nullable|boolean',

            'meal_notes' =>
                'nullable|string|max:2000',
        ]);

        $status = strtoupper(
            (string) ($validated['status'] ?? 'COMPLETED')
        );

        $remarks = trim(
            (string) ($validated['remarks'] ?? '')
        );

        if (
            $status !== 'COMPLETED'
            && $remarks === ''
        ) {
            throw ValidationException::withMessages([
                'remarks' =>
                    'Please record a reason or note for this medication outcome.',
            ]);
        }

        $result = DB::transaction(function () use (
            $id,
            $validated,
            $status,
            $remarks,
            $timelineService
        ) {
            $residentMedication = ResidentMedication::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $residentMedication->load('medication');

            $resident = Resident::findOrFail(
                $residentMedication->resident_id
            );

            if ($resident->status !== 'Active') {
                abort(
                    422,
                    'Medication can only be administered to active residents.'
                );
            }

            $this->assertMedicationIsActiveToday(
                $residentMedication
            );

            $today = today()->toDateString();

            $existingRecord =
                MedicationAdministrationRecord::where(
                    'resident_medication_id',
                    $residentMedication->id
                )
                ->whereDate('administered_date', $today)
                ->lockForUpdate()
                ->latest('created_on')
                ->first();

            /*
            | DELAYED is intentionally non-terminal. A delayed occurrence may
            | later be updated to COMPLETED or another final outcome.
            */
            if (
                $existingRecord
                && $existingRecord->status !== 'DELAYED'
            ) {
                abort(
                    422,
                    'A final medication outcome has already been recorded today.'
                );
            }

            $mealType =
                $this->mealForSlot(
                    strtoupper(
                        (string) $residentMedication->time_slot
                    )
                );

            $mealConfirmed =
                (bool) ($validated['meal_confirmed'] ?? false);

            if (
                $mealConfirmed
                && $status !== 'COMPLETED'
            ) {
                throw ValidationException::withMessages([
                    'meal_confirmed' =>
                        'A meal can only be confirmed with a successfully administered medication.',
                ]);
            }

            if (
                $mealConfirmed
                && $mealType === null
            ) {
                throw ValidationException::withMessages([
                    'meal_confirmed' =>
                        'OTHER medication does not have an automatic linked meal.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            |
            | Stock is deducted only when the medication is successfully
            | administered. HELD / REFUSED / UNAVAILABLE / MISSED / DELAYED
            | never reduce stock.
            |
            */

            $inventory = null;
            $quantity = (int) $residentMedication->dosage_quantity;

            if ($status === 'COMPLETED') {
                $inventory = MedicineInventory::where(
                        'medication_id',
                        $residentMedication->medication_id
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    $inventory
                    && (int) $inventory->quantity < $quantity
                ) {
                    abort(
                        422,
                        'Insufficient medicine stock.'
                    );
                }
            }

            $recordData = [
                'resident_id' =>
                    $residentMedication->resident_id,

                'resident_medication_id' =>
                    $residentMedication->id,

                'time_slot' =>
                    strtoupper(
                        (string) $residentMedication->time_slot
                    ),

                'scheduled_time' =>
                    $residentMedication->scheduled_time,

                'status' =>
                    $status,

                'administered_date' =>
                    $today,

                'completed_time' =>
                    $status === 'DELAYED'
                        ? null
                        : now(),

                'completed_by' =>
                    auth()->id(),

                'remarks' =>
                    $remarks !== '' ? $remarks : null,

                'meal_type' =>
                    $mealType,

                'meal_confirmed' =>
                    $mealConfirmed,

                'meal_confirmed_at' =>
                    $mealConfirmed ? now() : null,

                'meal_notes' =>
                    !empty($validated['meal_notes'])
                        ? trim($validated['meal_notes'])
                        : null,
            ];

            if ($existingRecord) {
                $existingRecord->update($recordData);
                $administrationRecord =
                    $existingRecord->fresh();
            } else {
                $administrationRecord =
                    MedicationAdministrationRecord::create(
                        $recordData
                    );
            }

            $medicineName =
                $residentMedication->medication?->medicine_name
                ?? 'Medication';

            if ($status === 'COMPLETED') {
                if ($inventory) {
                    $inventory->quantity =
                        (int) $inventory->quantity - $quantity;

                    $inventory->save();

                    MedicineTransaction::create([
                        'medication_id' =>
                            $residentMedication->medication_id,

                        'resident_id' =>
                            $residentMedication->resident_id,

                        'transaction_type' =>
                            'OUT',

                        'quantity' =>
                            $quantity,

                        'reference' =>
                            'Resident medication administration',

                        'performed_by' =>
                            auth()->id(),

                        'transaction_date' =>
                            now(),
                    ]);

                    $this->createLowStockAlertIfNeeded(
                        $residentMedication,
                        $inventory
                    );
                }

                $timelineService->recordMedicationGiven(
                    $residentMedication->resident_id,
                    $medicineName . ' administered.',
                    $administrationRecord->id
                );

                $this->completeMedicationReminderTask(
                    $residentMedication,
                    $today
                );

                if (
                    $mealConfirmed
                    && $mealType !== null
                ) {
                    $mealDescription =
                        $mealType
                        . ' confirmed with '
                        . $medicineName
                        . ' medication round.';

                    if (!empty($validated['meal_notes'])) {
                        $mealDescription .=
                            ' Notes: '
                            . trim($validated['meal_notes']);
                    }

                    $timelineService->recordMedicationMealConfirmed(
                        $residentMedication->resident_id,
                        $mealDescription,
                        $administrationRecord->id
                    );
                }
            } elseif ($status === 'DELAYED') {
                $timelineService->recordMedicationDelayed(
                    $residentMedication->resident_id,
                    $medicineName
                        . ' delayed. Reason: '
                        . $remarks,
                    $administrationRecord->id
                );
            } elseif ($status === 'MISSED') {
                $timelineService->recordMedicationMissed(
                    $residentMedication->resident_id,
                    $medicineName
                        . ' missed. Reason: '
                        . $remarks,
                    $administrationRecord->id
                );
            } elseif ($status === 'HELD') {
                $timelineService->recordMedicationHeld(
                    $residentMedication->resident_id,
                    $medicineName
                        . ' held. Reason: '
                        . $remarks,
                    $administrationRecord->id
                );
            } elseif ($status === 'REFUSED') {
                $timelineService->recordMedicationRefused(
                    $residentMedication->resident_id,
                    $medicineName
                        . ' refused. Reason: '
                        . $remarks,
                    $administrationRecord->id
                );
            } elseif ($status === 'UNAVAILABLE') {
                $timelineService->recordMedicationUnavailable(
                    $residentMedication->resident_id,
                    $medicineName
                        . ' unavailable. Reason: '
                        . $remarks,
                    $administrationRecord->id
                );
            }

            return $administrationRecord;
        });

        $result->load('completedBy');

        return response()->json([
            'message' =>
                $this->outcomeMessage($result->status),

            'round_complete' =>
                $result->status === 'COMPLETED'
                && (
                    $result->meal_type === null
                    || $result->meal_confirmed
                ),

            'family_notification_eligible' =>
                $result->status === 'COMPLETED'
                && $result->meal_type !== null
                && (bool) $result->meal_confirmed,

            'administration_record' =>
                $result,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Linked Meal
    |--------------------------------------------------------------------------
    |
    | This is separate from medication administration so the medication may be
    | recorded first and breakfast/lunch/dinner confirmed afterwards.
    |
    */

    public function confirmMeal(
        Request $request,
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $validated = $request->validate([
            'meal_notes' =>
                'nullable|string|max:2000',
        ]);

        $record = DB::transaction(function () use (
            $id,
            $validated,
            $timelineService
        ) {
            $record =
                MedicationAdministrationRecord::with(
                    'residentMedication'
                )
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $resident = Resident::findOrFail(
                $record->resident_id
            );

            if ($resident->status !== 'Active') {
                abort(
                    422,
                    'Meal confirmation can only be recorded for active residents.'
                );
            }

            if ($record->status !== 'COMPLETED') {
                abort(
                    422,
                    'The linked meal can only be confirmed after medication was successfully administered.'
                );
            }

            $mealType =
                $record->meal_type
                ?: $this->mealForSlot(
                    strtoupper((string) $record->time_slot)
                );

            if ($mealType === null) {
                abort(
                    422,
                    'This medication round does not have an automatic linked meal.'
                );
            }

            if ((bool) $record->meal_confirmed) {
                return $record;
            }

            $record->update([
                'meal_type' =>
                    $mealType,

                'meal_confirmed' =>
                    true,

                'meal_confirmed_at' =>
                    now(),

                'meal_notes' =>
                    !empty($validated['meal_notes'])
                        ? trim($validated['meal_notes'])
                        : $record->meal_notes,
            ]);

            $record->load(
                'residentMedication.medication'
            );

            $medicineName =
                $record
                    ->residentMedication
                    ?->medication
                    ?->medicine_name
                ?? 'Medication';

            $mealDescription =
                $mealType
                . ' confirmed after '
                . $medicineName
                . ' medication administration.';

            if (!empty($validated['meal_notes'])) {
                $mealDescription .=
                    ' Notes: '
                    . trim($validated['meal_notes']);
            }

            $timelineService->recordMedicationMealConfirmed(
                $record->resident_id,
                $mealDescription,
                $record->id
            );

            return $record->fresh();
        });

        return response()->json([
            'message' =>
                $record->meal_type
                . ' confirmed successfully.',

            'round_complete' =>
                true,

            /*
            | F7 will consume this state. No WhatsApp provider call is made
            | here in F6.
            */
            'family_notification_eligible' =>
                true,

            'administration_record' =>
                $record,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Other Medication
    |--------------------------------------------------------------------------
    */

    public function addOtherMedication(
        Request $request,
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $resident = Resident::findOrFail($id);

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' =>
                    'Medication can only be assigned to active residents.',
            ], 422);
        }

        $validated = $request->validate([
            'medication_id' =>
                'required|exists:medications,id',

            'dosage_instruction' =>
                'required|string|max:255',

            'dosage_quantity' =>
                'required|integer|min:1',
        ]);

        $medication = ResidentMedication::create([
            'resident_id' =>
                $id,

            'medication_id' =>
                $validated['medication_id'],

            'dosage_instruction' =>
                $validated['dosage_instruction'],

            'dosage_quantity' =>
                $validated['dosage_quantity'],

            'frequency' =>
                'ON DEMAND',

            'time_slot' =>
                'OTHER',

            'start_date' =>
                today()->toDateString(),
        ]);

        $medication->load('medication');

        $timelineService->recordMedicationStarted(
            $id,
            (
                $medication->medication?->medicine_name
                ?? 'Medication'
            ) . ' medication assigned.',
            $medication->id
        );

        return response()->json([
            'message' =>
                'Other medication added successfully',

            'data' =>
                $medication,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Medication History
    |--------------------------------------------------------------------------
    */

    public function history($id)
    {
        $resident = Resident::findOrFail($id);

        $records = MedicationAdministrationRecord::with([
                'residentMedication.medication',
                'completedBy',
            ])
            ->where('resident_id', $id)
            ->orderBy('created_on', 'desc')
            ->get();

        $history = [];

        foreach ($records as $record) {
            $history[] = [
                'id' =>
                    $record->id,

                'resident_medication_id' =>
                    $record->resident_medication_id,

                'medicine' =>
                    $record
                        ->residentMedication
                        ?->medication
                        ?->medicine_name,

                'dosage' =>
                    $record
                        ->residentMedication
                        ?->medication
                        ?->dosage,

                'time_slot' =>
                    $record->time_slot,

                'scheduled_time' =>
                    $record->scheduled_time,

                'status' =>
                    $record->status,

                'completed_time' =>
                    $record->completed_time,

                'completed_by' =>
                    $record->completedBy
                        ?->full_name,

                'remarks' =>
                    $record->remarks,

                'meal_type' =>
                    $record->meal_type,

                'meal_confirmed' =>
                    (bool) $record->meal_confirmed,

                'meal_confirmed_at' =>
                    $record->meal_confirmed_at,

                'meal_notes' =>
                    $record->meal_notes,
            ];
        }

        $timeline = ClinicalTimeline::where(
                'resident_id',
                $id
            )
            ->whereIn(
                'event_type',
                [
                    'MEDICATION_STARTED',
                    'MEDICATION_GIVEN',
                    'MEDICATION_DELAYED',
                    'MEDICATION_MISSED',
                ]
            )
            ->orderBy('event_date', 'desc')
            ->get();

        return response()->json([
            'resident' => [
                'id' =>
                    $resident->id,

                'name' =>
                    $resident->full_name,

                'status' =>
                    $resident->status,
            ],

            'medication_history' =>
                $history,

            'clinical_timeline' =>
                $timeline,
        ]);
    }

    private function assertMedicationIsActiveToday(
        ResidentMedication $residentMedication
    ): void {
        $today = today()->toDateString();

        if (
            $residentMedication->start_date
            && Carbon::parse(
                $residentMedication->start_date
            )->toDateString() > $today
        ) {
            abort(
                422,
                'This medication has not started yet.'
            );
        }

        if (
            $residentMedication->end_date
            && Carbon::parse(
                $residentMedication->end_date
            )->toDateString() < $today
        ) {
            abort(
                422,
                'This medication is no longer active.'
            );
        }
    }

    private function mealForSlot(?string $slot): ?string
    {
        return match (strtoupper((string) $slot)) {
            'AM' =>
                'Breakfast',

            'PM' =>
                'Lunch',

            'NIGHT' =>
                'Dinner',

            default =>
                null,
        };
    }

    private function outcomeMessage(string $status): string
    {
        return match ($status) {
            'COMPLETED' =>
                'Medication administration recorded successfully.',

            'HELD' =>
                'Medication recorded as held.',

            'REFUSED' =>
                'Medication refusal recorded.',

            'UNAVAILABLE' =>
                'Medication recorded as unavailable.',

            'MISSED' =>
                'Medication recorded as missed.',

            'DELAYED' =>
                'Medication delay recorded.',

            default =>
                'Medication outcome recorded.',
        };
    }

    private function completeMedicationReminderTask(
        ResidentMedication $residentMedication,
        string $administeredDate
    ): void {
        $occurrenceKey =
            'MED:'
            . $residentMedication->id
            . ':'
            . $administeredDate;

        $task = NurseTask::where(
                'occurrence_key',
                $occurrenceKey
            )
            ->where('source_type', 'MEDICATION')
            ->lockForUpdate()
            ->first();

        if (!$task) {
            return;
        }

        if (in_array($task->status, ['Completed', 'Cancelled'], true)) {
            return;
        }

        $task->update([
            'status' => 'Completed',
            'completed_time' => now(),
            'completed_by' => auth()->id(),
        ]);
    }

    private function createLowStockAlertIfNeeded(
        ResidentMedication $residentMedication,
        MedicineInventory $inventory
    ): void {
        if (
            (int) $inventory->quantity
            > (int) $inventory->minimum_stock
        ) {
            return;
        }

        $medicineName =
            $residentMedication->medication?->medicine_name
            ?? 'Medicine';

        $existingAlert = AiAlert::where(
                'alert_type',
                'MEDICINE LOW STOCK'
            )
            ->where('status', 'OPEN')
            ->where(
                'resident_id',
                $residentMedication->resident_id
            )
            ->where(
                'message',
                'like',
                $medicineName . ' stock is low.%'
            )
            ->first();

        $message =
            $medicineName
            . ' stock is low. Current stock: '
            . $inventory->quantity
            . ' units.';

        if ($existingAlert) {
            /*
            | Keep one OPEN alert per resident + medicine, but refresh its
            | message so staff always see the current stock quantity.
            */
            $existingAlert->update([
                'severity' =>
                    'WARNING',

                'message' =>
                    $message,

                'ai_confidence' =>
                    100,
            ]);

            return;
        }

        AiAlert::create([
            'resident_id' =>
                $residentMedication->resident_id,

            'alert_type' =>
                'MEDICINE LOW STOCK',

            'severity' =>
                'WARNING',

            'message' =>
                $message,

            'ai_confidence' =>
                100,

            'status' =>
                'OPEN',
        ]);
    }
}
