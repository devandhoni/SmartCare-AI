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
        |
        | READ ONLY.
        | This controller never generates care-plan tasks. It only classifies
        | NurseTask occurrences that have already been generated elsewhere.
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
        |
        | READ ONLY.
        | This deliberately does not call MedicationScheduleService because
        | that service creates nurse tasks and notifications.
        |--------------------------------------------------------------------------
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

        $medicationIds = $residentMedications->pluck('id');

        $todayMedicationRecords = MedicationAdministrationRecord::query()
            ->whereIn('resident_medication_id', $medicationIds)
            ->whereDate('administered_date', $today)
            ->get()
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
                'status' => $record?->status ?? 'PENDING',
                'completed_time' => $record?->completed_time,
            ];
        }

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
                'critical_alerts' => $criticalAlerts,
                'weekly_vitals_due' => $weeklyVitalsDue->count(),
                'monthly_glucose_due' => $monthlyGlucoseDue->count(),
                'residents_on_leave' => $homeLeaveItems->count(),
            ],

            'medication_rounds' => [
                'AM' => [
                    'total' => count($medicationRounds['AM']),
                    'items' => $medicationRounds['AM'],
                ],
                'PM' => [
                    'total' => count($medicationRounds['PM']),
                    'items' => $medicationRounds['PM'],
                ],
                'NIGHT' => [
                    'total' => count($medicationRounds['NIGHT']),
                    'items' => $medicationRounds['NIGHT'],
                ],
            ],

            'due_checks' => [
                'weekly_vitals' => $weeklyVitalsDue,
                'monthly_glucose' => $monthlyGlucoseDue,
            ],

            'home_leave' => $homeLeaveItems,

            'care_activities' => [
                'due' => $dueCareTasks->map(function ($task) {
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
                        'overdue' => false,
                    ];
                })->values(),

                'overdue' => $overdueCareTasks->map(function ($task) {
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
                        'overdue' => true,
                    ];
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
}
