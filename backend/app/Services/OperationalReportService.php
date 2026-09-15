<?php

namespace App\Services;

use App\Models\NurseTask;
use App\Models\CareRecord;
use App\Models\MedicationAdministrationRecord;
use App\Models\Resident;
use App\Models\ResidentAdmission;
use App\Models\ResidentDischarge;
use App\Models\VitalSign;
use App\Models\MedicineInventory;
use App\Models\MedicineTransaction;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\ActivityLog;
use App\Models\FamilyMessageLog;
use Carbon\Carbon;

class OperationalReportService
{
    /*
    |--------------------------------------------------------------------------
    | Reporting Overview
    |--------------------------------------------------------------------------
    |
    | Read-only reporting foundation.
    |
    */

    public function overview(
        Carbon $from,
        Carbon $to
    ): array {
        return [
            'period' => $this->period($from, $to),

            'residents' => [
                'total' => Resident::count(),

                'active' => Resident::where(
                    'status',
                    'Active'
                )->count(),

                'discharged' => Resident::where(
                    'status',
                    'Discharged'
                )->count(),
            ],
        ];
    }


    /*
|--------------------------------------------------------------------------
| Care Operations Report
|--------------------------------------------------------------------------
|
| Read-only reporting over existing routine-care tasks and care records.
|
| IMPORTANT:
| This method never generates care-plan occurrences or nurse tasks.
|
*/

public function careOperations(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Scheduled Routine Care
    |--------------------------------------------------------------------------
    */

    $scheduledTasks = NurseTask::with([
        'resident',
        'carePlan',
        'completedBy',
        'careRecord',
    ])
        ->where(
            'task_type',
            'ROUTINE_CARE'
        )
        ->whereBetween(
            'scheduled_time',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('scheduled_time')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Completed Routine Care
    |--------------------------------------------------------------------------
    |
    | Completion activity is based on completed_time so care completed during
    | the selected period is represented even if it was originally scheduled
    | outside that period.
    |
    */

    $completedTasks = NurseTask::with([
        'resident',
        'carePlan',
        'completedBy',
        'careRecord',
    ])
        ->where(
            'task_type',
            'ROUTINE_CARE'
        )
        ->where(
            'status',
            'Completed'
        )
        ->whereBetween(
            'completed_time',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('completed_time')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Care Records
    |--------------------------------------------------------------------------
    */

    $careRecords = CareRecord::with([
        'resident',
        'recorder',
    ])
        ->whereBetween(
            'recorded_at',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('recorded_at')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Scheduled Task Summary
    |--------------------------------------------------------------------------
    */

    $pending = $scheduledTasks
        ->where(
            'status',
            'Pending'
        )
        ->count();

    $acknowledged = $scheduledTasks
        ->where(
            'status',
            'ACKNOWLEDGED'
        )
        ->count();

    $completedScheduled = $scheduledTasks
        ->where(
            'status',
            'Completed'
        )
        ->count();

    $cancelled = $scheduledTasks
        ->where(
            'status',
            'Cancelled'
        )
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Source Classification
    |--------------------------------------------------------------------------
    */

    $carePlanTasks = $scheduledTasks
        ->where(
            'source_type',
            'CARE_PLAN'
        )
        ->count();

    $manualCareTasks = $scheduledTasks
        ->where(
            'source_type',
            'CARE'
        )
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Completion Rate
    |--------------------------------------------------------------------------
    |
    | This is the current outcome of tasks scheduled inside the selected
    | reporting period.
    |
    */

    $completionRate = $scheduledTasks->count() > 0
        ? round(
            (
                $completedScheduled
                /
                $scheduledTasks->count()
            ) * 100,
            1
        )
        : 0;

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'summary' => [
            'scheduled' =>
                $scheduledTasks->count(),

            'completed_scheduled' =>
                $completedScheduled,

            'completed_during_period' =>
                $completedTasks->count(),

            'pending' =>
                $pending,

            'acknowledged' =>
                $acknowledged,

            'cancelled' =>
                $cancelled,

            'care_records' =>
                $careRecords->count(),

            'completion_rate' =>
                $completionRate,
        ],

        'source_summary' => [
            'care_plan' =>
                $carePlanTasks,

            'manual_care' =>
                $manualCareTasks,

            'other' =>
                $scheduledTasks->count()
                - $carePlanTasks
                - $manualCareTasks,
        ],

        'scheduled_tasks' =>
            $scheduledTasks
                ->map(
                    function ($task) {
                        return [
                            'task_id' =>
                                $task->id,

                            'resident_id' =>
                                $task->resident_id,

                            'resident_name' =>
                                $task->resident?->full_name,

                            'task_name' =>
                                $task->task_name,

                            'source_type' =>
                                $task->source_type,

                            'care_plan_id' =>
                                $task->care_plan_id,

                            'care_plan_title' =>
                                $task->carePlan?->title,

                            'scheduled_time' =>
                                $task->scheduled_time
                                    ?->toDateTimeString(),

                            'status' =>
                                $task->status,

                            'priority' =>
                                $task->priority,

                            'completed_time' =>
                                $task->completed_time
                                    ?->toDateTimeString(),

                            'care_record_id' =>
                                $task->care_record_id,
                        ];
                    }
                )
                ->values(),

        'completed_tasks' =>
            $completedTasks
                ->map(
                    function ($task) {
                        return [
                            'task_id' =>
                                $task->id,

                            'resident_id' =>
                                $task->resident_id,

                            'resident_name' =>
                                $task->resident?->full_name,

                            'task_name' =>
                                $task->task_name,

                            'source_type' =>
                                $task->source_type,

                            'care_plan_id' =>
                                $task->care_plan_id,

                            'care_plan_title' =>
                                $task->carePlan?->title,

                            'completed_time' =>
                                $task->completed_time
                                    ?->toDateTimeString(),

                            'completed_by' =>
                                $task->completedBy?->full_name,

                            'care_record_id' =>
                                $task->care_record_id,
                        ];
                    }
                )
                ->values(),

        'care_records' =>
            $careRecords
                ->map(
                    function ($record) {
                        return [
                            'care_record_id' =>
                                $record->id,

                            'resident_id' =>
                                $record->resident_id,

                            'resident_name' =>
                                $record->resident?->full_name,

                            'care_type' =>
                                $record->care_type,

                            'title' =>
                                $record->title,

                            'care_status' =>
                                $record->care_status,

                            'recorded_at' =>
                                $record->recorded_at
                                    ?->toDateTimeString(),

                            'recorded_by' =>
                                $record->recorder?->full_name,
                        ];
                    }
                )
                ->values(),
    ];
}

    /*
    |--------------------------------------------------------------------------
    | Resident Census Report
    |--------------------------------------------------------------------------
    */

    public function residentCensus(
        Carbon $from,
        Carbon $to
    ): array {
        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Completed Admissions During Period
        |--------------------------------------------------------------------------
        */

        $admissions = ResidentAdmission::with('resident')
            ->where('status', 'COMPLETED')
            ->whereBetween(
                'admitted_at',
                [
                    $fromDate,
                    $toDate,
                ]
            )
            ->orderBy('admitted_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Completed Discharges During Period
        |--------------------------------------------------------------------------
        */

        $discharges = ResidentDischarge::with('resident')
            ->where('status', 'COMPLETED')
            ->whereBetween(
                'discharged_at',
                [
                    $fromDate,
                    $toDate,
                ]
            )
            ->orderBy('discharged_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Residents Present During Period
        |--------------------------------------------------------------------------
        |
        | A resident is included when at least one completed admission existed
        | on or before the end of the selected reporting period and that
        | admission was not already closed by a completed discharge before
        | the reporting period began.
        |
        */

        $residentIds = ResidentAdmission::query()
            ->where('status', 'COMPLETED')
            ->where(
                'admitted_at',
                '<=',
                $toDate
            )
            ->pluck('resident_id')
            ->unique()
            ->values();

        $presentResidents = Resident::query()
            ->whereIn(
                'id',
                $residentIds
            )
            ->with([
                'admissions' => function ($query) use ($toDate) {
                    $query
                        ->where('status', 'COMPLETED')
                        ->where(
                            'admitted_at',
                            '<=',
                            $toDate
                        )
                        ->orderByDesc('admitted_at');
                },

                'discharges' => function ($query) use ($toDate) {
                    $query
                        ->where('status', 'COMPLETED')
                        ->where(
                            'discharged_at',
                            '<=',
                            $toDate
                        )
                        ->orderByDesc('discharged_at');
                },
            ])
            ->orderBy('full_name')
            ->get()
            ->filter(
                function ($resident) use ($fromDate) {
                    $latestAdmission =
                        $resident->admissions->first();

                    if (!$latestAdmission) {
                        return false;
                    }

                    $latestDischarge =
                        $resident->discharges
                            ->first(
                                function ($discharge) use ($latestAdmission) {
                                    return $discharge->discharged_at
                                        ->greaterThanOrEqualTo(
                                            $latestAdmission->admitted_at
                                        );
                                }
                            );

                    if (!$latestDischarge) {
                        return true;
                    }

                    return $latestDischarge->discharged_at
                        ->greaterThanOrEqualTo(
                            $fromDate
                        );
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Active At Period End
        |--------------------------------------------------------------------------
        |
        | Historical period-end occupancy is derived from completed admission
        | and discharge evidence rather than the resident's current status.
        |
        */

        $activeAtPeriodEnd = $presentResidents
            ->filter(
                function ($resident) use ($toDate) {
                    $latestAdmission =
                        $resident->admissions->first();

                    if (!$latestAdmission) {
                        return false;
                    }

                    $latestDischarge =
                        $resident->discharges
                            ->first(
                                function ($discharge) use (
                                    $latestAdmission,
                                    $toDate
                                ) {
                                    return $discharge->discharged_at
                                        ->greaterThanOrEqualTo(
                                            $latestAdmission->admitted_at
                                        )
                                        &&
                                        $discharge->discharged_at
                                            ->lessThanOrEqualTo(
                                                $toDate
                                            );
                                }
                            );

                    return $latestDischarge === null;
                }
            )
            ->values();

        return [
            'period' => $this->period(
                $from,
                $to
            ),

            'summary' => [
                'residents_present' =>
                    $presentResidents->count(),

                'admissions' =>
                    $admissions->count(),

                'discharges' =>
                    $discharges->count(),

                'active_at_period_end' =>
                    $activeAtPeriodEnd->count(),
            ],

            'admissions' => $admissions
                ->map(
                    function ($admission) {
                        return [
                            'admission_id' =>
                                $admission->id,

                            'resident_id' =>
                                $admission->resident_id,

                            'resident_name' =>
                                $admission->resident?->full_name,

                            'admission_number' =>
                                $admission->admission_number,

                            'admitted_at' =>
                                $admission->admitted_at
                                    ?->toDateTimeString(),

                            'admission_type' =>
                                $admission->admission_type,
                        ];
                    }
                )
                ->values(),

            'discharges' => $discharges
                ->map(
                    function ($discharge) {
                        return [
                            'discharge_id' =>
                                $discharge->id,

                            'resident_id' =>
                                $discharge->resident_id,

                            'resident_name' =>
                                $discharge->resident?->full_name,

                            'discharge_number' =>
                                $discharge->discharge_number,

                            'discharged_at' =>
                                $discharge->discharged_at
                                    ?->toDateTimeString(),

                            'discharge_type' =>
                                $discharge->discharge_type,

                            'destination' =>
                                $discharge->discharge_destination,
                        ];
                    }
                )
                ->values(),

            'residents' => $presentResidents
                ->map(
                    function ($resident) use ($toDate) {
                        $latestAdmission =
                            $resident->admissions->first();

                        $latestDischarge =
                            $resident->discharges
                                ->first(
                                    function ($discharge) use (
                                        $latestAdmission,
                                        $toDate
                                    ) {
                                        return $latestAdmission
                                            &&
                                            $discharge->discharged_at
                                                ->greaterThanOrEqualTo(
                                                    $latestAdmission->admitted_at
                                                )
                                            &&
                                            $discharge->discharged_at
                                                ->lessThanOrEqualTo(
                                                    $toDate
                                                );
                                    }
                                );

                        return [
                            'resident_id' =>
                                $resident->id,

                            'resident_name' =>
                                $resident->full_name,

                            'admitted_at' =>
                                $latestAdmission?->admitted_at
                                    ?->toDateTimeString(),

                            'discharged_at' =>
                                $latestDischarge?->discharged_at
                                    ?->toDateTimeString(),

                            'status_at_period_end' =>
                                $latestDischarge
                                    ? 'Discharged'
                                    : 'Active',
                        ];
                    }
                )
                ->values(),
        ];
    }


    /*
|--------------------------------------------------------------------------
| Medication Operations Report
|--------------------------------------------------------------------------
|
| Historical, read-only medication reporting based exclusively on existing
| medication administration records.
|
| IMPORTANT:
| This method never checks medication due status, creates administration
| records, deducts inventory, or sends family notifications.
|
*/

public function medicationOperations(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Administration Evidence
    |--------------------------------------------------------------------------
    |
    | administered_date is the authoritative operational date for historical
    | medication administration reporting.
    |
    */

    $records = MedicationAdministrationRecord::with([
        'resident',
        'residentMedication.medication',
        'completedBy',
    ])
        ->whereBetween(
            'administered_date',
            [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ]
        )
        ->orderBy('administered_date')
        ->orderBy('scheduled_time')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Status Summary
    |--------------------------------------------------------------------------
    */

    $statusCounts = [
        'COMPLETED' =>
            $records->where('status', 'COMPLETED')->count(),

        'DELAYED' =>
            $records->where('status', 'DELAYED')->count(),

        'MISSED' =>
            $records->where('status', 'MISSED')->count(),

        'HELD' =>
            $records->where('status', 'HELD')->count(),

        'REFUSED' =>
            $records->where('status', 'REFUSED')->count(),

        'UNAVAILABLE' =>
            $records->where('status', 'UNAVAILABLE')->count(),
    ];

    $knownStatusTotal = array_sum($statusCounts);

    /*
    |--------------------------------------------------------------------------
    | Time Slot Summary
    |--------------------------------------------------------------------------
    */

    $timeSlotSummary = [
    'AM' => [
        'total' =>
            $records->where('time_slot', 'AM')->count(),

        'completed' =>
            $records
                ->where('time_slot', 'AM')
                ->where('status', 'COMPLETED')
                ->count(),
    ],

    'PM' => [
        'total' =>
            $records->where('time_slot', 'PM')->count(),

        'completed' =>
            $records
                ->where('time_slot', 'PM')
                ->where('status', 'COMPLETED')
                ->count(),
    ],

    'NIGHT' => [
        'total' =>
            $records->where('time_slot', 'NIGHT')->count(),

        'completed' =>
            $records
                ->where('time_slot', 'NIGHT')
                ->where('status', 'COMPLETED')
                ->count(),
    ],

    'OTHER' => [
        'total' =>
            $records->where('time_slot', 'OTHER')->count(),

        'completed' =>
            $records
                ->where('time_slot', 'OTHER')
                ->where('status', 'COMPLETED')
                ->count(),
    ],
];
    /*
    |--------------------------------------------------------------------------
    | Meal Confirmation
    |--------------------------------------------------------------------------
    |
    | Meal completion is reported independently from medication status.
    | This preserves the actual recorded evidence rather than assuming that
    | a completed medication automatically means its meal was confirmed.
    |
    */

    $mealApplicable = $records
        ->filter(
            fn ($record) =>
                !empty($record->meal_type)
        );

    $mealConfirmed = $mealApplicable
        ->where(
            'meal_confirmed',
            true
        )
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Completion Rate
    |--------------------------------------------------------------------------
    */

    $completionRate = $records->count() > 0
        ? round(
            (
                $statusCounts['COMPLETED']
                /
                $records->count()
            ) * 100,
            1
        )
        : 0;

    $mealConfirmationRate = $mealApplicable->count() > 0
        ? round(
            (
                $mealConfirmed
                /
                $mealApplicable->count()
            ) * 100,
            1
        )
        : 0;

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'summary' => [
            'total_records' =>
                $records->count(),

            'completed' =>
                $statusCounts['COMPLETED'],

            'delayed' =>
                $statusCounts['DELAYED'],

            'missed' =>
                $statusCounts['MISSED'],

            'held' =>
                $statusCounts['HELD'],

            'refused' =>
                $statusCounts['REFUSED'],

            'unavailable' =>
                $statusCounts['UNAVAILABLE'],

            'other_status' =>
                $records->count()
                - $knownStatusTotal,

            'completion_rate' =>
                $completionRate,
        ],

        'time_slots' =>
            $timeSlotSummary,

        'meals' => [
            'applicable' =>
                $mealApplicable->count(),

            'confirmed' =>
                $mealConfirmed,

            'not_confirmed' =>
                $mealApplicable->count()
                - $mealConfirmed,

            'confirmation_rate' =>
                $mealConfirmationRate,
        ],

        'records' =>
            $records
                ->map(
                    function ($record) {
                        return [
                            'administration_record_id' =>
                                $record->id,

                            'resident_id' =>
                                $record->resident_id,

                            'resident_name' =>
                                $record->resident?->full_name,

                            'resident_medication_id' =>
                                $record->resident_medication_id,

                            'medication_id' =>
                                $record->residentMedication
                                    ?->medication_id,

                            'medication_name' =>
                                $record->residentMedication
                                    ?->medication
                                    ?->medicine_name,

                            'dosage_instruction' =>
                                $record->residentMedication
                                    ?->dosage_instruction,

                            'dosage_quantity' =>
                                $record->residentMedication
                                    ?->dosage_quantity,

                            'time_slot' =>
                                $record->time_slot,

                            'scheduled_time' =>
                                $record->scheduled_time,

                            'administered_date' =>
                                $record->administered_date
                                    ?->toDateString(),

                            'status' =>
                                $record->status,

                            'completed_time' =>
                                $record->completed_time
                                    ?->toDateTimeString(),

                            'completed_by' =>
                                $record->completedBy?->full_name,

                            'meal_type' =>
                                $record->meal_type,

                            'meal_confirmed' =>
                                (bool) $record->meal_confirmed,

                            'meal_confirmed_at' =>
                                $record->meal_confirmed_at
                                    ?->toDateTimeString(),

                            'remarks' =>
                                $record->remarks,
                        ];
                    }
                )
                ->values(),
    ];
}



/*
|--------------------------------------------------------------------------
| Clinical Monitoring Report
|--------------------------------------------------------------------------
|
| Read-only historical reporting over existing weekly vital-sign checks
| and monthly glucose checks.
|
| This method never creates clinical records, invokes AI analysis,
| creates timeline events, or writes activity logs.
|
*/

public function clinicalMonitoring(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Weekly Vital Signs
    |--------------------------------------------------------------------------
    */

    $weeklyVitals = VitalSign::with([
        'resident',
    ])
        ->where(
            'record_source',
            'WEEKLY_VITALS'
        )
        ->whereBetween(
            'recorded_at',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('recorded_at')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Monthly Glucose
    |--------------------------------------------------------------------------
    */

    $monthlyGlucose = VitalSign::with([
        'resident',
    ])
        ->where(
            'record_source',
            'MONTHLY_GLUCOSE'
        )
        ->whereNotNull(
            'blood_glucose'
        )
        ->whereBetween(
            'recorded_at',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('recorded_at')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Residents Represented
    |--------------------------------------------------------------------------
    */

    $residentIds = $weeklyVitals
        ->pluck('resident_id')
        ->merge(
            $monthlyGlucose->pluck(
                'resident_id'
            )
        )
        ->unique()
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Weekly Measurement Coverage
    |--------------------------------------------------------------------------
    |
    | These are evidence/coverage counts only.
    | They are NOT clinical interpretation or abnormality detection.
    |
    */

    $weeklyCoverage = [
        'blood_pressure' =>
            $weeklyVitals
                ->filter(
                    fn ($record) =>
                        $record->blood_pressure_systolic !== null
                        &&
                        $record->blood_pressure_diastolic !== null
                )
                ->count(),

        'heart_rate' =>
            $weeklyVitals
                ->whereNotNull(
                    'heart_rate'
                )
                ->count(),

        'oxygen_level' =>
            $weeklyVitals
                ->whereNotNull(
                    'oxygen_level'
                )
                ->count(),

        'temperature' =>
            $weeklyVitals
                ->whereNotNull(
                    'temperature'
                )
                ->count(),

        'weight' =>
            $weeklyVitals
                ->whereNotNull(
                    'weight'
                )
                ->count(),

        'blood_glucose' =>
            $weeklyVitals
                ->whereNotNull(
                    'blood_glucose'
                )
                ->count(),
    ];

    /*
    |--------------------------------------------------------------------------
    | Monthly Glucose Measurement Types
    |--------------------------------------------------------------------------
    */

    $glucoseTypes = [
        'FASTING' =>
            $monthlyGlucose
                ->where(
                    'glucose_measurement_type',
                    'FASTING'
                )
                ->count(),

        'BEFORE_MEAL' =>
            $monthlyGlucose
                ->where(
                    'glucose_measurement_type',
                    'BEFORE_MEAL'
                )
                ->count(),

        'AFTER_MEAL' =>
            $monthlyGlucose
                ->where(
                    'glucose_measurement_type',
                    'AFTER_MEAL'
                )
                ->count(),

        'RANDOM' =>
            $monthlyGlucose
                ->where(
                    'glucose_measurement_type',
                    'RANDOM'
                )
                ->count(),
    ];

    $knownGlucoseTypes =
        array_sum(
            $glucoseTypes
        );

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'summary' => [
            'weekly_vital_checks' =>
                $weeklyVitals->count(),

            'monthly_glucose_checks' =>
                $monthlyGlucose->count(),

            'total_monitoring_records' =>
                $weeklyVitals->count()
                +
                $monthlyGlucose->count(),

            'residents_monitored' =>
                $residentIds->count(),
        ],

        'weekly_measurement_coverage' =>
            $weeklyCoverage,

        'glucose_measurement_types' => [
            ...$glucoseTypes,

            'OTHER' =>
                $monthlyGlucose->count()
                -
                $knownGlucoseTypes,
        ],

        'weekly_vitals' =>
            $weeklyVitals
                ->map(
                    function ($record) {
                        return [
                            'vital_sign_id' =>
                                $record->id,

                            'resident_id' =>
                                $record->resident_id,

                            'resident_name' =>
                                $record->resident?->full_name,

                            'recorded_at' =>
                                $record->recorded_at
                                    ?->toDateTimeString(),

                            'blood_pressure_systolic' =>
                                $record->blood_pressure_systolic,

                            'blood_pressure_diastolic' =>
                                $record->blood_pressure_diastolic,

                            'heart_rate' =>
                                $record->heart_rate,

                            'oxygen_level' =>
                                $record->oxygen_level,

                            'temperature' =>
                                $record->temperature,

                            'weight' =>
                                $record->weight,

                            'blood_glucose' =>
                                $record->blood_glucose,
                        ];
                    }
                )
                ->values(),

        'monthly_glucose' =>
            $monthlyGlucose
                ->map(
                    function ($record) {
                        return [
                            'vital_sign_id' =>
                                $record->id,

                            'resident_id' =>
                                $record->resident_id,

                            'resident_name' =>
                                $record->resident?->full_name,

                            'recorded_at' =>
                                $record->recorded_at
                                    ?->toDateTimeString(),

                            'blood_glucose' =>
                                $record->blood_glucose,

                            'measurement_type' =>
                                $record->glucose_measurement_type,

                            'notes' =>
                                $record->glucose_notes,
                        ];
                    }
                )
                ->values(),
    ];
}


/*
|--------------------------------------------------------------------------
| Inventory Report
|--------------------------------------------------------------------------
|
| Historical inventory movement is derived from medicine_transactions.
| Inventory quantities/statuses are explicitly a current snapshot.
|
| Read-only:
| - no stock reconciliation
| - no expiry reconciliation
| - no inventory adjustment
| - no alert creation
|
*/

public function inventoryOperations(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Historical Transactions
    |--------------------------------------------------------------------------
    */

    $transactions = MedicineTransaction::with([
        'medication',
        'resident',
        'performedBy',
    ])
        ->whereBetween(
            'transaction_date',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('transaction_date')
        ->orderBy('id')
        ->get();

    $inTransactions =
        $transactions->where(
            'transaction_type',
            'IN'
        );

    $outTransactions =
        $transactions->where(
            'transaction_type',
            'OUT'
        );

    $inQuantity =
        (int) $inTransactions->sum(
            'quantity'
        );

    $outQuantity =
        (int) $outTransactions->sum(
            'quantity'
        );

    /*
    |--------------------------------------------------------------------------
    | Current Inventory Snapshot
    |--------------------------------------------------------------------------
    |
    | These values represent inventory NOW.
    | They are not reconstructed historical balances.
    |
    */

    $inventory = MedicineInventory::with([
        'medication',
    ])
        ->orderBy('id')
        ->get();

    $statusCounts = [
        MedicineInventory::STATUS_IN_STOCK => 0,
        MedicineInventory::STATUS_LOW_STOCK => 0,
        MedicineInventory::STATUS_OUT_OF_STOCK => 0,
        MedicineInventory::STATUS_EXPIRING_SOON => 0,
        MedicineInventory::STATUS_EXPIRED => 0,
    ];

    foreach ($inventory as $item) {
        $status = $item->stock_status;

        if (array_key_exists(
            $status,
            $statusCounts
        )) {
            $statusCounts[$status]++;
        }
    }

    $needsAttention =
        $inventory
            ->filter(
                fn ($item) =>
                    $item->needs_attention
            )
            ->count();

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'movement_summary' => [
            'total_transactions' =>
                $transactions->count(),

            'in_transactions' =>
                $inTransactions->count(),

            'out_transactions' =>
                $outTransactions->count(),

            'in_quantity' =>
                $inQuantity,

            'out_quantity' =>
                $outQuantity,

            'net_quantity_movement' =>
                $inQuantity - $outQuantity,
        ],

        'current_inventory_summary' => [
            'snapshot_date' =>
                today()->toDateString(),

            'total_items' =>
                $inventory->count(),

            'total_quantity' =>
                (int) $inventory->sum(
                    'quantity'
                ),

            'in_stock' =>
                $statusCounts[
                    MedicineInventory::STATUS_IN_STOCK
                ],

            'low_stock' =>
                $statusCounts[
                    MedicineInventory::STATUS_LOW_STOCK
                ],

            'out_of_stock' =>
                $statusCounts[
                    MedicineInventory::STATUS_OUT_OF_STOCK
                ],

            'expiring_soon' =>
                $statusCounts[
                    MedicineInventory::STATUS_EXPIRING_SOON
                ],

            'expired' =>
                $statusCounts[
                    MedicineInventory::STATUS_EXPIRED
                ],

            'needs_attention' =>
                $needsAttention,
        ],

        'transactions' =>
            $transactions
                ->map(
                    function ($transaction) {
                        return [
                            'transaction_id' =>
                                $transaction->id,

                            'medication_id' =>
                                $transaction->medication_id,

                            'medication_name' =>
                                $transaction->medication
                                    ?->medicine_name,

                            'resident_id' =>
                                $transaction->resident_id,

                            'resident_name' =>
                                $transaction->resident
                                    ?->full_name,

                            'transaction_type' =>
                                $transaction->transaction_type,

                            'quantity' =>
                                (int) $transaction->quantity,

                            'reference' =>
                                $transaction->reference,

                            'performed_by' =>
                                $transaction->performedBy
                                    ?->full_name,

                            'transaction_date' =>
                                $transaction->transaction_date,
                        ];
                    }
                )
                ->values(),

        'current_inventory' =>
            $inventory
                ->map(
                    function ($item) {
                        return [
                            'inventory_id' =>
                                $item->id,

                            'medication_id' =>
                                $item->medication_id,

                            'medication_name' =>
                                $item->medication
                                    ?->medicine_name,

                            'quantity' =>
                                (int) $item->quantity,

                            'minimum_stock' =>
                                (int) $item->minimum_stock,

                            'expiry_date' =>
                                $item->expiry_date
                                    ?->format('Y-m-d'),

                            'location' =>
                                $item->location,

                            'stock_status' =>
                                $item->stock_status,

                            'days_to_expiry' =>
                                $item->days_to_expiry,

                            'needs_attention' =>
                                $item->needs_attention,
                        ];
                    }
                )
                ->values(),
    ];
}


/*
|--------------------------------------------------------------------------
| Billing Report
|--------------------------------------------------------------------------
|
| Read-only deterministic billing reporting.
|
| Invoice selection is based on billing-period overlap.
| Payment selection is based on actual payment_date.
|
| This method never generates invoices, records payments, changes invoice
| status, creates timeline events, or mutates billing records.
|
*/

public function billingOperations(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Invoices Whose Billing Period Overlaps Report Period
    |--------------------------------------------------------------------------
    */

    $invoices = BillingInvoice::with([
        'resident',
        'items',
        'generatedBy',
    ])
        ->whereDate(
            'billing_period_start',
            '<=',
            $toDate->toDateString()
        )
        ->whereDate(
            'billing_period_end',
            '>=',
            $fromDate->toDateString()
        )
        ->orderBy('billing_period_start')
        ->orderBy('id')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Payments Actually Received During Report Period
    |--------------------------------------------------------------------------
    */

    $payments = BillingPayment::with([
        'invoice',
        'resident',
        'receivedBy',
    ])
        ->whereBetween(
            'payment_date',
            [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ]
        )
        ->orderBy('payment_date')
        ->orderBy('id')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Invoice Status Summary
    |--------------------------------------------------------------------------
    */

    $statusCounts = [
        BillingInvoice::STATUS_DRAFT => 0,
        BillingInvoice::STATUS_ISSUED => 0,
        BillingInvoice::STATUS_PARTIALLY_PAID => 0,
        BillingInvoice::STATUS_PAID => 0,
        BillingInvoice::STATUS_VOID => 0,
    ];

    foreach ($invoices as $invoice) {
        if (array_key_exists(
            $invoice->status,
            $statusCounts
        )) {
            $statusCounts[$invoice->status]++;
        }
    }

    $otherStatus =
        $invoices->count()
        - array_sum($statusCounts);

    /*
    |--------------------------------------------------------------------------
    | Non-Void Financial Totals
    |--------------------------------------------------------------------------
    |
    | VOID invoices remain visible in the report but are excluded from
    | collectible/current invoice financial totals.
    |
    */

    $nonVoidInvoices =
        $invoices->where(
            'status',
            '!=',
            BillingInvoice::STATUS_VOID
        );

    $invoiceTotal =
        (float) $nonVoidInvoices->sum(
            fn ($invoice) =>
                (float) $invoice->total_amount
        );

    $invoiceAmountPaid =
        (float) $nonVoidInvoices->sum(
            fn ($invoice) =>
                (float) $invoice->amount_paid
        );

    $outstandingBalance =
        (float) $nonVoidInvoices->sum(
            fn ($invoice) =>
                (float) $invoice->balance_due
        );

    $overdueInvoices =
        $nonVoidInvoices
            ->filter(
                fn ($invoice) =>
                    $invoice->is_overdue
            );

    /*
    |--------------------------------------------------------------------------
    | Payments Received
    |--------------------------------------------------------------------------
    */

    $paymentsReceived =
        (float) $payments->sum(
            fn ($payment) =>
                (float) $payment->amount
        );

    $paymentMethods = [
        BillingPayment::METHOD_CASH =>
            $payments
                ->where(
                    'payment_method',
                    BillingPayment::METHOD_CASH
                )
                ->count(),

        BillingPayment::METHOD_BANK_TRANSFER =>
            $payments
                ->where(
                    'payment_method',
                    BillingPayment::METHOD_BANK_TRANSFER
                )
                ->count(),

        BillingPayment::METHOD_CARD =>
            $payments
                ->where(
                    'payment_method',
                    BillingPayment::METHOD_CARD
                )
                ->count(),

        BillingPayment::METHOD_CHEQUE =>
            $payments
                ->where(
                    'payment_method',
                    BillingPayment::METHOD_CHEQUE
                )
                ->count(),

        BillingPayment::METHOD_OTHER =>
            $payments
                ->where(
                    'payment_method',
                    BillingPayment::METHOD_OTHER
                )
                ->count(),
    ];

    $knownPaymentMethods =
        array_sum($paymentMethods);

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'invoice_summary' => [
            'total_invoices' =>
                $invoices->count(),

            'draft' =>
                $statusCounts[
                    BillingInvoice::STATUS_DRAFT
                ],

            'issued' =>
                $statusCounts[
                    BillingInvoice::STATUS_ISSUED
                ],

            'partially_paid' =>
                $statusCounts[
                    BillingInvoice::STATUS_PARTIALLY_PAID
                ],

            'paid' =>
                $statusCounts[
                    BillingInvoice::STATUS_PAID
                ],

            'void' =>
                $statusCounts[
                    BillingInvoice::STATUS_VOID
                ],

            'other_status' =>
                $otherStatus,

            'overdue' =>
                $overdueInvoices->count(),

            'non_void_invoice_total' =>
                round(
                    $invoiceTotal,
                    2
                ),

            'current_amount_paid' =>
                round(
                    $invoiceAmountPaid,
                    2
                ),

            'current_outstanding_balance' =>
                round(
                    $outstandingBalance,
                    2
                ),
        ],

        'payment_summary' => [
            'payments_received' =>
                $payments->count(),

            'amount_received' =>
                round(
                    $paymentsReceived,
                    2
                ),

            'methods' => [
                ...$paymentMethods,

                'UNKNOWN' =>
                    $payments->count()
                    -
                    $knownPaymentMethods,
            ],
        ],

        'invoices' =>
            $invoices
                ->map(
                    function ($invoice) {
                        return [
                            'invoice_id' =>
                                $invoice->id,

                            'invoice_number' =>
                                $invoice->invoice_number,

                            'resident_id' =>
                                $invoice->resident_id,

                            'resident_name' =>
                                $invoice->resident
                                    ?->full_name,

                            'billing_period_start' =>
                                $invoice
                                    ->billing_period_start
                                    ?->format('Y-m-d'),

                            'billing_period_end' =>
                                $invoice
                                    ->billing_period_end
                                    ?->format('Y-m-d'),

                            'issue_date' =>
                                $invoice
                                    ->issue_date
                                    ?->format('Y-m-d'),

                            'due_date' =>
                                $invoice
                                    ->due_date
                                    ?->format('Y-m-d'),

                            'subtotal' =>
                                (float) $invoice->subtotal,

                            'discount_amount' =>
                                (float) $invoice
                                    ->discount_amount,

                            'adjustment_amount' =>
                                (float) $invoice
                                    ->adjustment_amount,

                            'total_amount' =>
                                (float) $invoice->total_amount,

                            'amount_paid' =>
                                (float) $invoice->amount_paid,

                            'balance_due' =>
                                (float) $invoice->balance_due,

                            'status' =>
                                $invoice->status,

                            'is_overdue' =>
                                $invoice->is_overdue,

                            'generated_by' =>
                                $invoice->generatedBy
                                    ?->full_name,

                            'items' =>
                                $invoice->items
                                    ->map(
                                        function ($item) {
                                            return [
                                                'invoice_item_id' =>
                                                    $item->id,

                                                'item_type' =>
                                                    $item->item_type,

                                                'description' =>
                                                    $item->description,

                                                'quantity' =>
                                                    (float) $item->quantity,

                                                'unit_amount' =>
                                                    (float) $item->unit_amount,

                                                'line_total' =>
                                                    (float) $item->line_total,
                                            ];
                                        }
                                    )
                                    ->values(),
                        ];
                    }
                )
                ->values(),

        'payments' =>
            $payments
                ->map(
                    function ($payment) {
                        return [
                            'payment_id' =>
                                $payment->id,

                            'payment_reference' =>
                                $payment->payment_reference,

                            'billing_invoice_id' =>
                                $payment->billing_invoice_id,

                            'invoice_number' =>
                                $payment->invoice
                                    ?->invoice_number,

                            'resident_id' =>
                                $payment->resident_id,

                            'resident_name' =>
                                $payment->resident
                                    ?->full_name,

                            'amount' =>
                                (float) $payment->amount,

                            'payment_date' =>
                                $payment
                                    ->payment_date
                                    ?->format('Y-m-d'),

                            'payment_method' =>
                                $payment->payment_method,

                            'received_by' =>
                                $payment->receivedBy
                                    ?->full_name,

                            'notes' =>
                                $payment->notes,
                        ];
                    }
                )
                ->values(),
    ];
}

/*
|--------------------------------------------------------------------------
| Family Communication & Facility Activity Report
|--------------------------------------------------------------------------
|
| Read-only operational reporting.
|
| Family communication is derived from existing family_message_logs.
| Facility activity is derived from existing activity_logs.
|
| This method never sends/retries messages or creates activity records.
|
*/

public function familyFacilityOperations(
    Carbon $from,
    Carbon $to
): array {
    $fromDate = $from->copy()->startOfDay();
    $toDate = $to->copy()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Family Communication
    |--------------------------------------------------------------------------
    */

    $familyMessages = FamilyMessageLog::with([
        'resident',
        'residentContact',
        'creator',
    ])
        ->whereBetween(
            'created_at',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

    $messageStatuses = [
        FamilyMessageLog::STATUS_PENDING => 0,
        FamilyMessageLog::STATUS_SENT => 0,
        FamilyMessageLog::STATUS_FAILED => 0,
        FamilyMessageLog::STATUS_SKIPPED => 0,
    ];

    foreach ($familyMessages as $message) {
        if (array_key_exists(
            $message->status,
            $messageStatuses
        )) {
            $messageStatuses[$message->status]++;
        }
    }

    $otherMessageStatus =
        $familyMessages->count()
        - array_sum($messageStatuses);

    $totalAttempts =
        (int) $familyMessages->sum(
            'attempt_count'
        );

    /*
    |--------------------------------------------------------------------------
    | Facility Activity
    |--------------------------------------------------------------------------
    */

    $activities = ActivityLog::with([
        'user',
        'resident',
    ])
        ->whereBetween(
            'created_on',
            [
                $fromDate,
                $toDate,
            ]
        )
        ->orderBy('created_on')
        ->orderBy('id')
        ->get();

    $moduleSummary =
        $activities
            ->groupBy(
                fn ($activity) =>
                    $activity->module
                    ?: 'UNSPECIFIED'
            )
            ->map(
                fn ($records) =>
                    $records->count()
            )
            ->sortDesc();

    $actionSummary =
        $activities
            ->groupBy(
                fn ($activity) =>
                    $activity->action
                    ?: 'UNSPECIFIED'
            )
            ->map(
                fn ($records) =>
                    $records->count()
            )
            ->sortDesc();

    return [
        'period' =>
            $this->period(
                $from,
                $to
            ),

        'family_communication_summary' => [
            'total_messages' =>
                $familyMessages->count(),

            'pending' =>
                $messageStatuses[
                    FamilyMessageLog::STATUS_PENDING
                ],

            'sent' =>
                $messageStatuses[
                    FamilyMessageLog::STATUS_SENT
                ],

            'failed' =>
                $messageStatuses[
                    FamilyMessageLog::STATUS_FAILED
                ],

            'skipped' =>
                $messageStatuses[
                    FamilyMessageLog::STATUS_SKIPPED
                ],

            'other_status' =>
                $otherMessageStatus,

            'total_attempts' =>
                $totalAttempts,
        ],

        'facility_activity_summary' => [
            'total_activities' =>
                $activities->count(),

            'modules' =>
                $moduleSummary,

            'actions' =>
                $actionSummary,
        ],

        'family_messages' =>
            $familyMessages
                ->map(
                    function ($message) {
                        return [
                            'family_message_log_id' =>
                                $message->id,

                            'resident_id' =>
                                $message->resident_id,

                            'resident_name' =>
                                $message->resident
                                    ?->full_name,

                            'resident_contact_id' =>
                                $message->resident_contact_id,

                            'recipient_name' =>
                                $message->recipient_name,

                            'channel' =>
                                $message->channel,

                            'message_type' =>
                                $message->message_type,

                            'message' =>
                                $message->message,

                            'source_type' =>
                                $message->source_type,

                            'source_id' =>
                                $message->source_id,

                            'status' =>
                                $message->status,

                            'provider' =>
                                $message->provider,

                            'attempt_count' =>
                                (int) $message->attempt_count,

                            'sent_at' =>
                                $message->sent_at
                                    ?->toDateTimeString(),

                            'failed_at' =>
                                $message->failed_at
                                    ?->toDateTimeString(),

                            'failure_reason' =>
                                $message->failure_reason,

                            'created_by' =>
                                $message->creator
                                    ?->full_name,

                            'created_at' =>
                                $message->created_at
                                    ?->toDateTimeString(),
                        ];
                    }
                )
                ->values(),

        'facility_activities' =>
            $activities
                ->map(
                    function ($activity) {
                        return [
                            'activity_log_id' =>
                                $activity->id,

                            'user_id' =>
                                $activity->user_id,

                            'user_name' =>
                                $activity->user
                                    ?->full_name,

                            'resident_id' =>
                                $activity->resident_id,

                            'resident_name' =>
                                $activity->resident
                                    ?->full_name,

                            'module' =>
                                $activity->module,

                            'action' =>
                                $activity->action,

                            'description' =>
                                $activity->description,

                            'created_on' =>
                                $activity->created_on
                                    ?->toDateTimeString(),
                        ];
                    }
                )
                ->values(),
    ];
}



    /*
    |--------------------------------------------------------------------------
    | Reporting Period
    |--------------------------------------------------------------------------
    */

    private function period(
        Carbon $from,
        Carbon $to
    ): array {
        return [
            'from' =>
                $from->toDateString(),

            'to' =>
                $to->toDateString(),

            'days' =>
                (int) $from
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $to
                            ->copy()
                            ->startOfDay()
                    ) + 1,
        ];
    }
}