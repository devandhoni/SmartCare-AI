<?php

namespace App\Services;

class AIExecutivePerformanceSummaryEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine
    ) {
        $this->reportEngine = $reportEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 53.3
    | Executive AI Performance Summary
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Executive Intelligence Report
        |--------------------------------------------------------------------------
        */

        $report =
            $this->reportEngine->analyze();

        /*
        |--------------------------------------------------------------------------
        | 2. Source Sections
        |--------------------------------------------------------------------------
        */

        $clinicalPerformance =
            $report['clinical_performance']
            ?? [];

        $outcomePerformance =
            $report['ai_outcome_performance']
            ?? [];

        $careExecution =
            $report['care_execution']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | 3. Core Performance Metrics
        |--------------------------------------------------------------------------
        */

        $averageResponseTime =
            (float) (
                $clinicalPerformance[
                    'average_response_time_minutes'
                ]
                ?? 0
            );

        $averageResolutionTime =
            (float) (
                $clinicalPerformance[
                    'average_resolution_time_minutes'
                ]
                ?? 0
            );

        $slaCompliance =
            (float) (
                $clinicalPerformance[
                    'sla_compliance_percentage'
                ]
                ?? 0
            );

        $pendingTasks =
            (int) (
                $clinicalPerformance[
                    'pending_nurse_tasks'
                ]
                ?? 0
            );

        $completedTasks =
            (int) (
                $clinicalPerformance[
                    'completed_nurse_tasks'
                ]
                ?? 0
            );

        $totalOutcomes =
            (int) (
                $outcomePerformance[
                    'total_outcomes_recorded'
                ]
                ?? 0
            );

        $averageAIAccuracy =
            (float) (
                $outcomePerformance[
                    'average_ai_accuracy'
                ]
                ?? 0
            );

        $interventionSuccessRate =
            (float) (
                $outcomePerformance[
                    'intervention_success_rate'
                ]
                ?? 0
            );

        $evaluatedWorkflows =
            (int) (
                $careExecution[
                    'evaluated_workflows'
                ]
                ?? 0
            );

        $workflowSuccessRate =
            (float) (
                $careExecution[
                    'workflow_success_rate'
                ]
                ?? 0
            );

        $executionReadyActions =
            (int) (
                $careExecution[
                    'execution_ready_actions'
                ]
                ?? 0
            );

        $doctorReviewActions =
            (int) (
                $careExecution[
                    'doctor_review_actions'
                ]
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Workflow Learning Maturity
        |--------------------------------------------------------------------------
        */

        $learningMaturity =
            'AWAITING OUTCOME DATA';

        $learningConfidence =
            'NONE';

        if ($evaluatedWorkflows >= 20) {

            $learningMaturity =
                'MATURE LEARNING';

            $learningConfidence =
                'HIGH';

        } elseif ($evaluatedWorkflows >= 5) {

            $learningMaturity =
                'DEVELOPING LEARNING';

            $learningConfidence =
                'MODERATE';

        } elseif ($evaluatedWorkflows > 0) {

            $learningMaturity =
                'EARLY LEARNING';

            $learningConfidence =
                'VERY LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Workflow Success Interpretation
        |--------------------------------------------------------------------------
        */

        $workflowPerformanceLevel =
            'NOT YET EVALUATED';

        if ($evaluatedWorkflows > 0) {

            if ($workflowSuccessRate >= 90) {

                $workflowPerformanceLevel =
                    'HIGH PERFORMANCE';

            } elseif ($workflowSuccessRate >= 75) {

                $workflowPerformanceLevel =
                    'GOOD PERFORMANCE';

            } elseif ($workflowSuccessRate >= 60) {

                $workflowPerformanceLevel =
                    'MODERATE PERFORMANCE';

            } else {

                $workflowPerformanceLevel =
                    'REVIEW REQUIRED';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. AI Outcome Interpretation
        |--------------------------------------------------------------------------
        */

        $outcomePerformanceLevel =
            'NOT YET EVALUATED';

        if ($totalOutcomes > 0) {

            if (
                $averageAIAccuracy >= 90
                &&
                $interventionSuccessRate >= 90
            ) {

                $outcomePerformanceLevel =
                    'HIGH PERFORMANCE';

            } elseif (
                $averageAIAccuracy >= 75
                &&
                $interventionSuccessRate >= 75
            ) {

                $outcomePerformanceLevel =
                    'GOOD PERFORMANCE';

            } elseif (
                $averageAIAccuracy >= 60
                &&
                $interventionSuccessRate >= 60
            ) {

                $outcomePerformanceLevel =
                    'MODERATE PERFORMANCE';

            } else {

                $outcomePerformanceLevel =
                    'REVIEW REQUIRED';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. SLA Performance Interpretation
        |--------------------------------------------------------------------------
        */

        $slaPerformanceLevel =
            'REVIEW REQUIRED';

        if ($slaCompliance >= 90) {

            $slaPerformanceLevel =
                'STRONG';

        } elseif ($slaCompliance >= 75) {

            $slaPerformanceLevel =
                'ACCEPTABLE';

        } elseif ($slaCompliance >= 60) {

            $slaPerformanceLevel =
                'NEEDS IMPROVEMENT';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Task Completion Rate
        |--------------------------------------------------------------------------
        */

        $taskTotal =
            $pendingTasks
            +
            $completedTasks;

        $taskCompletionRate =
            $taskTotal > 0
            ?
            round(
                (
                    $completedTasks
                    /
                    $taskTotal
                )
                * 100,
                2
            )
            :
            0;

        /*
        |--------------------------------------------------------------------------
        | 9. Executive Performance Status
        |--------------------------------------------------------------------------
        */

        $performanceStatus =
            'STABLE';

        if (
            $slaCompliance < 60
            ||
            (
                $totalOutcomes > 0
                &&
                $interventionSuccessRate < 60
            )
        ) {

            $performanceStatus =
                'ATTENTION REQUIRED';

        } elseif (
            $slaCompliance < 75
            ||
            $learningMaturity === 'EARLY LEARNING'
        ) {

            $performanceStatus =
                'MONITOR';
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Executive Performance Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        if ($totalOutcomes > 0) {

            $findings[] =
                'AI outcome intelligence includes '
                . $totalOutcomes
                . ' recorded outcome(s), with average AI accuracy of '
                . $averageAIAccuracy
                . '%.';
        }

        if ($evaluatedWorkflows > 0) {

            $findings[] =
                'AI care workflows currently show a '
                . $workflowSuccessRate
                . '% success rate across '
                . $evaluatedWorkflows
                . ' evaluated workflow(s).';
        }

        $findings[] =
            'Current escalation SLA compliance is '
            . $slaCompliance
            . '%.';

        $findings[] =
            'Nurse task completion rate is '
            . $taskCompletionRate
            . '%.';

        if ($learningMaturity === 'EARLY LEARNING') {

            $findings[] =
                'AI care recommendation learning remains preliminary because only '
                . $evaluatedWorkflows
                . ' workflow(s) have been evaluated.';
        }

        if ($executionReadyActions > 0) {

            $findings[] =
                $executionReadyActions
                . ' AI-supported care action(s) are currently execution-ready.';
        }

        if ($doctorReviewActions > 0) {

            $findings[] =
                $doctorReviewActions
                . ' AI-supported action(s) require physician review.';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Management Interpretation
        |--------------------------------------------------------------------------
        */

        $managementInterpretation = [];

        if ($slaCompliance < 75) {

            $managementInterpretation[] =
                'Escalation SLA performance should be reviewed for possible workflow delay or response bottlenecks.';
        }

        if (
            $learningMaturity ===
            'EARLY LEARNING'
        ) {

            $managementInterpretation[] =
                'Current AI workflow success metrics should be interpreted cautiously because the evaluated sample size is still small.';
        }

        if ($taskCompletionRate < 75) {

            $managementInterpretation[] =
                'Pending nursing workload should be reviewed to determine whether operational capacity requires attention.';
        }

        if (
            $outcomePerformanceLevel ===
            'HIGH PERFORMANCE'
            &&
            $learningMaturity ===
            'EARLY LEARNING'
        ) {

            $managementInterpretation[] =
                'Early AI outcome performance is positive, but more evaluated workflows are required before drawing strong facility-wide conclusions.';
        }

        if (empty($managementInterpretation)) {

            $managementInterpretation[] =
                'Current AI and operational performance indicators are within acceptable ranges.';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Final Executive Performance Summary
        |--------------------------------------------------------------------------
        */

        return [

            'performance_status' =>
                $performanceStatus,

            'workflow_performance' => [

                'evaluated_workflows' =>
                    $evaluatedWorkflows,

                'workflow_success_rate' =>
                    $workflowSuccessRate,

                'performance_level' =>
                    $workflowPerformanceLevel,

                'learning_maturity' =>
                    $learningMaturity,

                'learning_confidence' =>
                    $learningConfidence,
            ],

            'ai_outcome_performance' => [

                'total_outcomes_recorded' =>
                    $totalOutcomes,

                'average_ai_accuracy' =>
                    $averageAIAccuracy,

                'intervention_success_rate' =>
                    $interventionSuccessRate,

                'performance_level' =>
                    $outcomePerformanceLevel,
            ],

            'operational_performance' => [

                'average_response_time_minutes' =>
                    $averageResponseTime,

                'average_resolution_time_minutes' =>
                    $averageResolutionTime,

                'sla_compliance_percentage' =>
                    $slaCompliance,

                'sla_performance_level' =>
                    $slaPerformanceLevel,

                'pending_nurse_tasks' =>
                    $pendingTasks,

                'completed_nurse_tasks' =>
                    $completedTasks,

                'task_completion_rate' =>
                    $taskCompletionRate,
            ],

            'care_execution' => [

                'execution_ready_actions' =>
                    $executionReadyActions,

                'doctor_review_actions' =>
                    $doctorReviewActions,
            ],

            'performance_findings' =>
                $findings,

            'management_interpretation' =>
                $managementInterpretation,

            'performance_guardrails' => [

                'small_sample_warning' =>
                    $evaluatedWorkflows < 5,

                'automatic_model_changes' =>
                    false,

                'automatic_clinical_rule_changes' =>
                    false,

                'human_validation_required' =>
                    true,
            ],
        ];
    }
}