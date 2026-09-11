<?php

namespace App\Http\Controllers;

use App\Models\AiAlert;
use App\Models\MedicationAdministrationRecord;
use App\Models\NurseTask;
use App\Models\Resident;
use App\Models\ResidentHomeLeave;
use App\Models\ResidentMedication;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TodayController extends Controller
{
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Active Residents
        |--------------------------------------------------------------------------
        */

        $activeResidents = Resident::query()
            ->where('status', 'Active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'room_id']);

        $activeResidentIds = $activeResidents->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Nurse Tasks
        |--------------------------------------------------------------------------
        |
        | READ ONLY. Today never creates NurseTasks. Care-plan generation and
        | medication due checking remain explicit side-effectful workflows.
        |
        */

        $openTaskStatuses = [
            'Pending',
            'ACKNOWLEDGED',
        ];

        $pendingTasks = NurseTask::query()
            ->with('resident:id,full_name,status')
            ->whereIn('resident_id', $activeResidentIds)
            ->whereIn('status', $openTaskStatuses)
            ->orderBy('scheduled_time')
            ->get();

        $overdueTasks = $pendingTasks
            ->filter(function ($task) use ($now) {
                return $task->scheduled_time
                    && Carbon::parse($task->scheduled_time)->lt($now);
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Due / Overdue Routine Care
        |--------------------------------------------------------------------------
        */

        $routineCareTasks = $pendingTasks
            ->filter(function ($task) {
                return $task->task_type === 'ROUTINE_CARE';
            })
            ->values();

        $overdueCareTasks = $routineCareTasks
            ->filter(function ($task) use ($now) {
                return $task->scheduled_time
                    && Carbon::parse($task->scheduled_time)->lt($now);
            })
            ->values();

        $dueCareTasks = $routineCareTasks
            ->filter(function ($task) use ($now) {
                if (!$task->scheduled_time) {
                    return false;
                }

                $scheduled = Carbon::parse($task->scheduled_time);

                return $scheduled->greaterThanOrEqualTo($now)
                    && $scheduled->isSameDay($now);
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Medication Reminder Tasks
        |--------------------------------------------------------------------------
        |
        | These are already-created NurseTasks from MedicationScheduleService.
        | Today only classifies and displays them.
        |
        */

        $medicationTasks = $pendingTasks
            ->filter(function ($task) {
                return strtoupper((string) $task->source_type) === 'MEDICATION';
            })
            ->values();

        $overdueMedicationTasks = $medicationTasks
            ->filter(function ($task) use ($now) {
                return $task->scheduled_time
                    && Carbon::parse($task->scheduled_time)->lt($now);
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Critical Alerts
        |--------------------------------------------------------------------------
        */

        $criticalAlerts = AiAlert::query()
            ->whereIn('resident_id', $activeResidentIds)
            ->where('status', 'OPEN')
            ->where('severity', 'CRITICAL')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Today's Medication Schedule
        |--------------------------------------------------------------------------
        |
        | READ ONLY.
        | This deliberately does not call MedicationScheduleService because that
        | service creates NurseTasks and Notifications.
        |
        */

        $residentMedications = ResidentMedication::query()
            ->with([
                'resident:id,full_name,status',
                'medication:id,medicine_name,dosage,unit',
            ])
            ->whereHas('resident', function ($query) {
                $query->where('status', 'Active');
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->whereIn('time_slot', ['AM', 'PM', 'NIGHT'])
            ->orderBy('scheduled_time')
            ->get();

        $residentMedicationIds = $residentMedications->pluck('id');

        $todayMedicationRecords = MedicationAdministrationRecord::query()
            ->whereIn('resident_medication_id', $residentMedicationIds)
            ->whereDate('administered_date', $today)
            ->orderByDesc('created_on')
            ->get()
            ->unique('resident_medication_id')
            ->keyBy('resident_medication_id');

        $medicationRounds = [
            'AM' => [],
            'PM' => [],
            'NIGHT' => [],
        ];

        foreach ($residentMedications as $residentMedication) {
            $record = $todayMedicationRecords->get($residentMedication->id);
            $slot = strtoupper((string) $residentMedication->time_slot);

            if (!array_key_exists($slot, $medicationRounds)) {
                continue;
            }

            $status = strtoupper((string) ($record?->status ?? 'PENDING'));
            $mealType = $record?->meal_type ?? $this->mealForSlot($slot);
            $mealConfirmed = (bool) ($record?->meal_confirmed ?? false);

            $scheduledAt = $this->scheduledDateTime(
                $today,
                $residentMedication->scheduled_time
            );

            $isPendingLike = in_array($status, ['PENDING', 'DELAYED'], true);
            $overdue = $isPendingLike
                && $scheduledAt
                && $scheduledAt->lt($now);

            $roundComplete = $status === 'COMPLETED'
                && ($mealType === null || $mealConfirmed);

            $familyNotificationEligible = $status === 'COMPLETED'
                && $mealType !== null
                && $mealConfirmed;

            $medicationRounds[$slot][] = [
                'resident_medication_id' => $residentMedication->id,
                'resident_id' => $residentMedication->resident_id,
                'resident' => $residentMedication->resident?->full_name,
                'medication_id' => $residentMedication->medication_id,
                'medicine' => $residentMedication->medication?->medicine_name,
                'dosage' => $residentMedication->medication?->dosage,
                'unit' => $residentMedication->medication?->unit,
                'dosage_instruction' => $residentMedication->dosage_instruction,
                'dosage_quantity' => $residentMedication->dosage_quantity,
                'scheduled_time' => $residentMedication->scheduled_time,
                'scheduled_at' => $scheduledAt?->toDateTimeString(),
                'status' => $status,
                'administration_record_id' => $record?->id,
                'completed_time' => $record?->completed_time,
                'completed_by' => $record?->completed_by,
                'remarks' => $record?->remarks,
                'meal_type' => $mealType,
                'meal_confirmed' => $mealConfirmed,
                'meal_confirmed_at' => $record?->meal_confirmed_at,
                'meal_notes' => $record?->meal_notes,
                'overdue' => $overdue,
                'round_complete' => $roundComplete,
                'family_notification_eligible' => $familyNotificationEligible,
            ];
        }

        $medicationItems = collect($medicationRounds)->flatten(1);

        $medicationScheduled = $medicationItems->count();

        $medicationCompleted = $medicationItems
            ->where('status', 'COMPLETED')
            ->count();

        $medicationPending = $medicationItems
            ->filter(function ($item) {
                return in_array(
                    strtoupper((string) ($item['status'] ?? '')),
                    ['PENDING', 'DELAYED'],
                    true
                );
            })
            ->count();

        $medicationExceptions = $medicationItems
            ->filter(function ($item) {
                return in_array(
                    strtoupper((string) ($item['status'] ?? '')),
                    ['HELD', 'REFUSED', 'UNAVAILABLE', 'MISSED'],
                    true
                );
            })
            ->count();

        $medicationOverdue = $medicationItems
            ->where('overdue', true)
            ->count();

        $medicationMealsPending = $medicationItems
            ->filter(function ($item) {
                return strtoupper((string) ($item['status'] ?? '')) === 'COMPLETED'
                    && !empty($item['meal_type'])
                    && empty($item['meal_confirmed']);
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Weekly Vital Checks
        |--------------------------------------------------------------------------
        */

        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        $weeklyCompletedResidentIds = VitalSign::query()
            ->whereIn('resident_id', $activeResidentIds)
            ->where('record_source', 'WEEKLY_VITALS')
            ->whereBetween('recorded_at', [$weekStart, $weekEnd])
            ->pluck('resident_id')
            ->unique();

        $weeklyVitalsDue = $activeResidents
            ->whereNotIn('id', $weeklyCompletedResidentIds)
            ->values()
            ->map(function ($resident) {
                return [
                    'resident_id' => $resident->id,
                    'resident' => $resident->full_name,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Monthly Glucose Checks
        |--------------------------------------------------------------------------
        */

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthlyCompletedResidentIds = VitalSign::query()
            ->whereIn('resident_id', $activeResidentIds)
            ->where('record_source', 'MONTHLY_GLUCOSE')
            ->whereBetween('recorded_at', [$monthStart, $monthEnd])
            ->pluck('resident_id')
            ->unique();

        $monthlyGlucoseDue = $activeResidents
            ->whereNotIn('id', $monthlyCompletedResidentIds)
            ->values()
            ->map(function ($resident) {
                return [
                    'resident_id' => $resident->id,
                    'resident' => $resident->full_name,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Residents Currently On Home Leave
        |--------------------------------------------------------------------------
        */

        $homeLeaves = ResidentHomeLeave::query()
            ->with('resident:id,full_name,status')
            ->whereHas('resident', function ($query) {
                $query->where('status', 'Active');
            })
            ->whereNull('returned_at')
            ->whereNotIn('status', ['RETURNED', 'CANCELLED'])
            ->orderBy('expected_return_at')
            ->get();

        $homeLeaveItems = $homeLeaves
            ->map(function ($leave) use ($now) {
                $expectedReturn = $leave->expected_return_at
                    ? Carbon::parse($leave->expected_return_at)
                    : null;

                return [
                    'id' => $leave->id,
                    'resident_id' => $leave->resident_id,
                    'resident' => $leave->resident?->full_name,
                    'leave_reference' => $leave->leave_reference,
                    'reason' => $leave->reason_for_leave,
                    'destination' => $leave->destination,
                    'leave_at' => $leave->leave_at,
                    'expected_return_at' => $leave->expected_return_at,
                    'status' => $leave->status,
                    'overdue' => $expectedReturn
                        ? $expectedReturn->lt($now)
                        : false,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'date' => $today->toDateString(),

            'summary' => [
                'active_residents' => $activeResidents->count(),
                'pending_tasks' => $pendingTasks->count(),
                'overdue_tasks' => $overdueTasks->count(),
                'due_care_tasks' => $dueCareTasks->count(),
                'overdue_care_tasks' => $overdueCareTasks->count(),
                'medication_scheduled' => $medicationScheduled,
                'medication_pending' => $medicationPending,
                'medication_completed' => $medicationCompleted,
                'medication_exceptions' => $medicationExceptions,
                'medication_overdue' => $medicationOverdue,
                'medication_meals_pending' => $medicationMealsPending,
                'medication_task_overdue' => $overdueMedicationTasks->count(),
                'critical_alerts' => $criticalAlerts,
                'weekly_vitals_due' => $weeklyVitalsDue->count(),
                'monthly_glucose_due' => $monthlyGlucoseDue->count(),
                'residents_on_leave' => $homeLeaveItems->count(),
            ],

            'medication_rounds' => [
                'AM' => $this->roundPayload($medicationRounds['AM']),
                'PM' => $this->roundPayload($medicationRounds['PM']),
                'NIGHT' => $this->roundPayload($medicationRounds['NIGHT']),
            ],

            'due_checks' => [
                'weekly_vitals' => $weeklyVitalsDue,
                'monthly_glucose' => $monthlyGlucoseDue,
            ],

            'home_leave' => $homeLeaveItems,

            'care_activities' => [
                'due' => $dueCareTasks->map(function ($task) {
                    return $this->taskPayload($task, false);
                })->values(),

                'overdue' => $overdueCareTasks->map(function ($task) {
                    return $this->taskPayload($task, true);
                })->values(),
            ],

            'tasks' => $pendingTasks->map(function ($task) use ($now) {
                $scheduled = $task->scheduled_time
                    ? Carbon::parse($task->scheduled_time)
                    : null;

                return [
                    'id' => $task->id,
                    'resident_id' => $task->resident_id,
                    'resident' => $task->resident?->full_name,
                    'task_name' => $task->task_name,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'scheduled_time' => $task->scheduled_time,
                    'ai_generated' => (bool) $task->ai_generated,
                    'task_type' => $task->task_type,
                    'source_type' => $task->source_type,
                    'care_plan_id' => $task->care_plan_id,
                    'occurrence_key' => $task->occurrence_key,
                    'overdue' => $scheduled
                        ? $scheduled->lt($now)
                        : false,
                ];
            })->values(),
        ]);
    }

    private function mealForSlot(string $slot): ?string
    {
        return match (strtoupper($slot)) {
            'AM' => 'Breakfast',
            'PM' => 'Lunch',
            'NIGHT' => 'Dinner',
            default => null,
        };
    }

    private function scheduledDateTime(
        Carbon $today,
        $scheduledTime
    ): ?Carbon {
        if (!$scheduledTime) {
            return null;
        }

        try {
            $time = Carbon::parse($scheduledTime)->format('H:i:s');

            return Carbon::parse(
                $today->toDateString() . ' ' . $time
            );
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function roundPayload(array $items): array
    {
        $collection = collect($items);

        return [
            'total' => $collection->count(),
            'completed' => $collection->where('status', 'COMPLETED')->count(),
            'pending' => $collection->filter(function ($item) {
                return in_array(
                    strtoupper((string) ($item['status'] ?? '')),
                    ['PENDING', 'DELAYED'],
                    true
                );
            })->count(),
            'exceptions' => $collection->filter(function ($item) {
                return in_array(
                    strtoupper((string) ($item['status'] ?? '')),
                    ['HELD', 'REFUSED', 'UNAVAILABLE', 'MISSED'],
                    true
                );
            })->count(),
            'overdue' => $collection->where('overdue', true)->count(),
            'meals_pending' => $collection->filter(function ($item) {
                return strtoupper((string) ($item['status'] ?? '')) === 'COMPLETED'
                    && !empty($item['meal_type'])
                    && empty($item['meal_confirmed']);
            })->count(),
            'items' => array_values($items),
        ];
    }

    private function taskPayload($task, bool $overdue): array
    {
        return [
            'id' => $task->id,
            'resident_id' => $task->resident_id,
            'resident' => $task->resident?->full_name,
            'task_name' => $task->task_name,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'scheduled_time' => $task->scheduled_time,
            'source_type' => $task->source_type,
            'care_plan_id' => $task->care_plan_id,
            'occurrence_key' => $task->occurrence_key,
            'overdue' => $overdue,
        ];
    }
}
