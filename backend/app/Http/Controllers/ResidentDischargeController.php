<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Models\ResidentDischarge;
use App\Services\ClinicalTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentDischargeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Discharges
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $discharges =
            ResidentDischarge::with([
                'resident:id,room_id,full_name,status,admission_date,discharge_date',
                'room:id,room_number,floor,room_type',
                'preparedBy:id,full_name',
                'completedBy:id,full_name',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'discharges' =>
                $discharges,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident Discharge History
    |--------------------------------------------------------------------------
    */

    public function residentDischarges($residentId)
    {
        $resident =
            Resident::findOrFail(
                $residentId
            );

        $discharges =
            ResidentDischarge::with([
                'room:id,room_number,floor,room_type',
                'preparedBy:id,full_name',
                'completedBy:id,full_name',
            ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'resident' => [
                'id' =>
                    $resident->id,

                'full_name' =>
                    $resident->full_name,

                'status' =>
                    $resident->status,
            ],

            'discharges' =>
                $discharges,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Discharge Draft
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated =
            $request->validate([
                'resident_id' =>
                    'required|exists:residents,id',

                'discharged_at' =>
                    'nullable|date',

                'discharge_type' =>
                    'nullable|string|max:100',

                'discharge_destination' =>
                    'nullable|string|max:255',

                'reason_for_discharge' =>
                    'nullable|string',

                'condition_at_discharge' =>
                    'nullable|string',

                'treatment_care_summary' =>
                    'nullable|string',

                'medical_summary' =>
                    'nullable|string',

                'follow_up_instructions' =>
                    'nullable|string',

                'medication_summary' =>
                    'nullable|string',

                'medication_instructions' =>
                    'nullable|string',

                'discharged_to' =>
                    'nullable|string|max:255',

                'discharged_to_relationship' =>
                    'nullable|string|max:100',

                'discharged_to_contact' =>
                    'nullable|string|max:50',

                'belongings_returned' =>
                    'nullable|boolean',

                'belongings_notes' =>
                    'nullable|string',

                'administrative_notes' =>
                    'nullable|string',
            ]);

        $resident =
            Resident::findOrFail(
                $validated['resident_id']
            );

        if (
            $resident->status !== 'Active'
        ) {
            return response()->json([
                'message' =>
                    'Only active residents can have a new discharge prepared.',
            ], 422);
        }

        $existingDraft =
            ResidentDischarge::where(
                'resident_id',
                $resident->id
            )
            ->where(
                'status',
                'DRAFT'
            )
            ->first();

        if ($existingDraft) {
            return response()->json([
                'message' =>
                    'A discharge draft already exists for this resident.',

                'discharge' =>
                    $existingDraft,
            ], 422);
        }

        $discharge =
            ResidentDischarge::create([
                'resident_id' =>
                    $resident->id,

                'room_id_at_discharge' =>
                    $resident->room_id,

                'discharge_number' =>
                    $this->generateDischargeNumber(),

                'discharged_at' =>
                    $validated['discharged_at']
                    ?? null,

                'discharge_type' =>
                    $validated['discharge_type']
                    ?? null,

                'discharge_destination' =>
                    $validated['discharge_destination']
                    ?? null,

                'reason_for_discharge' =>
                    $validated['reason_for_discharge']
                    ?? null,

                'condition_at_discharge' =>
                    $validated['condition_at_discharge']
                    ?? null,

                'treatment_care_summary' =>
                    $validated['treatment_care_summary']
                    ?? null,

                'medical_summary' =>
                    $validated['medical_summary']
                    ?? null,

                'follow_up_instructions' =>
                    $validated['follow_up_instructions']
                    ?? null,

                'medication_summary' =>
                    $validated['medication_summary']
                    ?? null,

                'medication_instructions' =>
                    $validated['medication_instructions']
                    ?? null,

                'discharged_to' =>
                    $validated['discharged_to']
                    ?? null,

                'discharged_to_relationship' =>
                    $validated['discharged_to_relationship']
                    ?? null,

                'discharged_to_contact' =>
                    $validated['discharged_to_contact']
                    ?? null,

                'belongings_returned' =>
                    $validated['belongings_returned']
                    ?? false,

                'belongings_notes' =>
                    $validated['belongings_notes']
                    ?? null,

                'administrative_notes' =>
                    $validated['administrative_notes']
                    ?? null,

                'status' =>
                    'DRAFT',

                'prepared_by' =>
                    auth()->id(),
            ]);

        $discharge->load([
            'resident',
            'room',
            'preparedBy:id,full_name',
        ]);

        return response()->json([
            'message' =>
                'Discharge draft created successfully.',

            'discharge' =>
                $discharge,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Discharge
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $discharge =
            ResidentDischarge::with([
                'resident',
                'room',
                'preparedBy:id,full_name',
                'completedBy:id,full_name',
            ])
            ->findOrFail($id);

        return response()->json([
            'discharge' =>
                $discharge,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Discharge Draft
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $discharge =
            ResidentDischarge::findOrFail(
                $id
            );

        if (
            $discharge->status === 'COMPLETED'
        ) {
            return response()->json([
                'message' =>
                    'Completed discharge records cannot be edited.',
            ], 422);
        }

        $validated =
            $request->validate([
                'discharged_at' =>
                    'nullable|date',

                'discharge_type' =>
                    'nullable|string|max:100',

                'discharge_destination' =>
                    'nullable|string|max:255',

                'reason_for_discharge' =>
                    'nullable|string',

                'condition_at_discharge' =>
                    'nullable|string',

                'treatment_care_summary' =>
                    'nullable|string',

                'medical_summary' =>
                    'nullable|string',

                'follow_up_instructions' =>
                    'nullable|string',

                'medication_summary' =>
                    'nullable|string',

                'medication_instructions' =>
                    'nullable|string',

                'discharged_to' =>
                    'nullable|string|max:255',

                'discharged_to_relationship' =>
                    'nullable|string|max:100',

                'discharged_to_contact' =>
                    'nullable|string|max:50',

                'belongings_returned' =>
                    'nullable|boolean',

                'belongings_notes' =>
                    'nullable|string',

                'administrative_notes' =>
                    'nullable|string',
            ]);

        $discharge->update(
            $validated
        );

        return response()->json([
            'message' =>
                'Discharge draft updated successfully.',

            'discharge' =>
                $discharge->fresh([
                    'resident',
                    'room',
                    'preparedBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Discharge Draft
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $discharge =
            ResidentDischarge::findOrFail(
                $id
            );

        if (
            $discharge->status === 'COMPLETED'
        ) {
            return response()->json([
                'message' =>
                    'Completed discharge records cannot be deleted.',
            ], 422);
        }

        $discharge->delete();

        return response()->json([
            'message' =>
                'Discharge draft deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Discharge
    |--------------------------------------------------------------------------
    */

    public function complete(
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $discharge =
            ResidentDischarge::with(
                'resident'
            )
            ->findOrFail(
                $id
            );

        if (
            $discharge->status === 'COMPLETED'
        ) {
            return response()->json([
                'message' =>
                    'Discharge has already been completed.',

                'discharge' =>
                    $discharge,
            ]);
        }

        if (
            !$discharge->discharged_at
        ) {
            return response()->json([
                'message' =>
                    'Discharge date and time is required before completion.',
            ], 422);
        }

        if (
            empty(
                $discharge->reason_for_discharge
            )
        ) {
            return response()->json([
                'message' =>
                    'Reason for discharge is required before completion.',
            ], 422);
        }

        if (
            empty(
                $discharge->condition_at_discharge
            )
        ) {
            return response()->json([
                'message' =>
                    'Condition at discharge is required before completion.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $discharge
            ) {
                $discharge->update([
                    'status' =>
                        'COMPLETED',

                    'completed_at' =>
                        now(),

                    'completed_by' =>
                        auth()->id(),
                ]);

                $discharge->resident->update([
                    'status' =>
                        'Discharged',

                    'discharge_date' =>
                        $discharge
                            ->discharged_at
                            ->toDateString(),

                    /*
                    |--------------------------------------------------------------------------
                    | Release Current Room
                    |--------------------------------------------------------------------------
                    |
                    | The discharge record already keeps room_id_at_discharge.
                    |
                    */

                    'room_id' =>
                        null,
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline Event
        |--------------------------------------------------------------------------
        */

        $timelineService->record(
            $discharge->resident_id,
            ClinicalEventType::DISCHARGE,
            'Resident Discharge',
            'Resident discharged from SmartCare AI nursing facility. Discharge reference: ' .
                $discharge->discharge_number,
            'ResidentDischarge',
            $discharge->id
        );

        return response()->json([
            'message' =>
                'Resident discharge completed successfully.',

            'discharge' =>
                $discharge->fresh([
                    'resident',
                    'room',
                    'preparedBy:id,full_name',
                    'completedBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Discharge Number
    |--------------------------------------------------------------------------
    */

    private function generateDischargeNumber(): string
    {
        do {
            $number =
                'DIS-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(6)
                );
        } while (
            ResidentDischarge::where(
                'discharge_number',
                $number
            )->exists()
        );

        return $number;
    }
}