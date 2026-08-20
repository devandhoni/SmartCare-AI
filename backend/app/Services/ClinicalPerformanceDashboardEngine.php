<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\HealthRiskScore;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\NurseTask;
use App\Models\AlertEscalationLog;
use Carbon\Carbon;

class ClinicalPerformanceDashboardEngine
{
    /*
    |--------------------------------------------------------------------------
    | Generate Clinical Performance Dashboard
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Resident Census
        |--------------------------------------------------------------------------
        |
        | Separate residents currently receiving care from historical /
        | non-active resident records.
        |
        */

        $totalResidents =
            Resident::count();

        $activeResidentIds =
            Resident::query()
                ->whereRaw('UPPER(status) = ?', ['ACTIVE'])
                ->pluck('id');

        $nonActiveResidentIds =
            Resident::query()
                ->whereRaw('UPPER(status) <> ?', ['ACTIVE'])
                ->pluck('id');

        $activeResidents =
            $activeResidentIds->count();

        $nonActiveResidents =
            $nonActiveResidentIds->count();


        /*
        |--------------------------------------------------------------------------
        | 2. Active Critical Resident Count
        |--------------------------------------------------------------------------
        |
        | Only ACTIVE residents should contribute to current clinical
        | performance and operational escalation metrics.
        |
        */

        $activeCriticalResidents =
            HealthRiskScore::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | 3. Historical / Non-Active Critical Cases
        |--------------------------------------------------------------------------
        |
        | These remain clinically important for audit/history, but they must
        | not inflate the current operational critical-case count.
        |
        */

        $historicalCriticalResidents =
            HealthRiskScore::query()
                ->whereIn(
                    'resident_id',
                    $nonActiveResidentIds
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | 4. Active AI Alert Metrics
        |--------------------------------------------------------------------------
        |
        | Current-care alerts and historical/non-active alerts are separated.
        |
        */

        $activeAlerts =
            AiAlert::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $historicalOpenAlerts =
            AiAlert::query()
                ->whereIn(
                    'resident_id',
                    $nonActiveResidentIds
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 5. AI Prediction Metrics
        |--------------------------------------------------------------------------
        |
        | Preserve total AI prediction counts for performance history.
        |
        | Also expose active-care prediction counts separately so the command
        | center can distinguish current operational intelligence from
        | historical AI activity.
        |
        */

        $totalPredictions =
            HealthPrediction::count();

        $highRiskPredictions =
            HealthPrediction::query()
                ->whereIn(
                    'risk_level',
                    [
                        'HIGH',
                        'CRITICAL',
                    ]
                )
                ->count();

        $activeCarePredictions =
            HealthPrediction::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->count();

        $activeCareHighRiskPredictions =
            HealthPrediction::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->whereIn(
                    'risk_level',
                    [
                        'HIGH',
                        'CRITICAL',
                    ]
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 6. Escalation Performance
        |--------------------------------------------------------------------------
        */

        $escalations =
            AlertEscalationLog::all();

        $responseTimes = [];
        $resolutionTimes = [];

        foreach ($escalations as $log) {

            /*
            |--------------------------------------------------------------------------
            | Response Time
            |--------------------------------------------------------------------------
            */

            if (
                $log->escalated_at
                &&
                $log->acknowledged_at
            ) {
                $responseTimes[] =
                    Carbon::parse(
                        $log->escalated_at
                    )
                    ->diffInMinutes(
                        Carbon::parse(
                            $log->acknowledged_at
                        )
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Resolution Time
            |--------------------------------------------------------------------------
            */

            if (
                $log->acknowledged_at
                &&
                $log->resolved_at
            ) {
                $resolutionTimes[] =
                    Carbon::parse(
                        $log->acknowledged_at
                    )
                    ->diffInMinutes(
                        Carbon::parse(
                            $log->resolved_at
                        )
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Average Response Time
        |--------------------------------------------------------------------------
        */

        $averageResponseTime =
            count($responseTimes) > 0
                ?
                round(
                    array_sum(
                        $responseTimes
                    )
                    /
                    count(
                        $responseTimes
                    ),
                    2
                )
                :
                0;


        /*
        |--------------------------------------------------------------------------
        | 8. Average Resolution Time
        |--------------------------------------------------------------------------
        */

        $averageResolutionTime =
            count($resolutionTimes) > 0
                ?
                round(
                    array_sum(
                        $resolutionTimes
                    )
                    /
                    count(
                        $resolutionTimes
                    ),
                    2
                )
                :
                0;


        /*
        |--------------------------------------------------------------------------
        | 9. SLA Performance
        |--------------------------------------------------------------------------
        */

        $totalEscalations =
            AlertEscalationLog::count();

        $resolvedEscalations =
            AlertEscalationLog::query()
                ->where(
                    'status',
                    'RESOLVED'
                )
                ->count();

        $slaPercentage =
            $totalEscalations > 0
                ?
                round(
                    (
                        $resolvedEscalations
                        /
                        $totalEscalations
                    )
                    * 100,
                    2
                )
                :
                0;


        /*
        |--------------------------------------------------------------------------
        | 10. Nurse Task Performance
        |--------------------------------------------------------------------------
        |
        | Preserve overall task metrics for operational reporting.
        |
        */

        $pendingTasks =
            NurseTask::query()
                ->where(
                    'status',
                    'Pending'
                )
                ->count();

        $acknowledgedTasks =
            NurseTask::query()
                ->where(
                    'status',
                    'Acknowledged'
                )
                ->count();

        $completedTasks =
            NurseTask::query()
                ->where(
                    'status',
                    'Completed'
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 11. AI-Generated Care Workflow Metrics
        |--------------------------------------------------------------------------
        |
        | Step 52 workflows can now be identified separately from ordinary
        | manually-created nurse tasks.
        |
        */

        $aiGeneratedTasks =
            NurseTask::query()
                ->where(
                    'ai_generated',
                    true
                )
                ->count();

        $pendingAiGeneratedTasks =
            NurseTask::query()
                ->where(
                    'ai_generated',
                    true
                )
                ->where(
                    'status',
                    'Pending'
                )
                ->count();

        $completedAiGeneratedTasks =
            NurseTask::query()
                ->where(
                    'ai_generated',
                    true
                )
                ->where(
                    'status',
                    'Completed'
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 12. Operational Clinical Status
        |--------------------------------------------------------------------------
        |
        | This status reflects CURRENT ACTIVE CARE only.
        |
        */

        $systemStatus = 'STABLE';

        if (
            $activeCriticalResidents > 0
            ||
            $activeAlerts > 0
        ) {
            $systemStatus =
                'ATTENTION REQUIRED';
        } elseif (
            $pendingTasks > 0
            ||
            $pendingAiGeneratedTasks > 0
        ) {
            $systemStatus =
                'ACTIVE';
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Dashboard Response
        |--------------------------------------------------------------------------
        */

        return [

            /*
            |--------------------------------------------------------------------------
            | Operational Status
            |--------------------------------------------------------------------------
            */

            'system_status' =>
                $systemStatus,


            /*
            |--------------------------------------------------------------------------
            | Clinical Summary
            |--------------------------------------------------------------------------
            */

            'clinical_summary' => [

                'total_resident_records' =>
                    $totalResidents,

                'active_care_residents' =>
                    $activeResidents,

                'non_active_residents' =>
                    $nonActiveResidents,

                'active_critical_cases' =>
                    $activeCriticalResidents,

                'historical_non_active_critical_cases' =>
                    $historicalCriticalResidents,

                'active_care_alerts' =>
                    $activeAlerts,

                'historical_non_active_open_alerts' =>
                    $historicalOpenAlerts,
            ],


            /*
            |--------------------------------------------------------------------------
            | AI Metrics
            |--------------------------------------------------------------------------
            */

            'ai_metrics' => [

                'predictions_generated' =>
                    $totalPredictions,

                'high_risk_predictions' =>
                    $highRiskPredictions,

                'active_care_predictions' =>
                    $activeCarePredictions,

                'active_care_high_risk_predictions' =>
                    $activeCareHighRiskPredictions,
            ],


            /*
            |--------------------------------------------------------------------------
            | Escalation Metrics
            |--------------------------------------------------------------------------
            */

            'escalation_metrics' => [

                'total_escalations' =>
                    $totalEscalations,

                'resolved_escalations' =>
                    $resolvedEscalations,

                'average_response_time_minutes' =>
                    $averageResponseTime,

                'average_resolution_time_minutes' =>
                    $averageResolutionTime,

                'sla_compliance_percentage' =>
                    $slaPercentage,
            ],


            /*
            |--------------------------------------------------------------------------
            | Nursing Metrics
            |--------------------------------------------------------------------------
            */

            'nursing_metrics' => [

                'pending_tasks' =>
                    $pendingTasks,

                'acknowledged_tasks' =>
                    $acknowledgedTasks,

                'completed_tasks' =>
                    $completedTasks,
            ],


            /*
            |--------------------------------------------------------------------------
            | Step 52 AI Care Workflow Metrics
            |--------------------------------------------------------------------------
            */

            'ai_care_workflow_metrics' => [

                'total_ai_generated_tasks' =>
                    $aiGeneratedTasks,

                'pending_ai_generated_tasks' =>
                    $pendingAiGeneratedTasks,

                'completed_ai_generated_tasks' =>
                    $completedAiGeneratedTasks,
            ],


            /*
            |--------------------------------------------------------------------------
            | Historical Context
            |--------------------------------------------------------------------------
            |
            | Explicitly tells the frontend / executive layer that historical
            | non-active risk is retained but excluded from current escalation.
            |
            */

            'historical_context' => [

                'non_active_critical_cases' =>
                    $historicalCriticalResidents,

                'non_active_open_alerts' =>
                    $historicalOpenAlerts,

                'excluded_from_current_care_escalation' =>
                    true,

                'message' =>
                    'Non-active resident clinical risk and open alerts are retained for historical intelligence but excluded from current operational care escalation.',
            ],
        ];
    }
}