<?php

namespace App\Services;

use App\Models\AIExecutiveIntelligenceSnapshot;

class AIExecutiveIntelligenceSnapshotEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    protected AIExecutivePerformanceSummaryEngine $performanceEngine;


    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine,
        AIExecutivePerformanceSummaryEngine $performanceEngine
    ) {
        $this->reportEngine =
            $reportEngine;

        $this->performanceEngine =
            $performanceEngine;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.5
    | Capture Executive Intelligence Snapshot
    |--------------------------------------------------------------------------
    */

    public function capture(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Generate Current Executive Intelligence
        |--------------------------------------------------------------------------
        */

        $report =
            $this->reportEngine->analyze();

        $performance =
            $this->performanceEngine->analyze();


        /*
        |--------------------------------------------------------------------------
        | 2. Source Sections
        |--------------------------------------------------------------------------
        */

        $facilityCensus =
            $report['facility_census']
            ?? [];

        $operationalRisk =
            $report['operational_risk']
            ?? [];

        $careExecution =
            $report['care_execution']
            ?? [];

        $outcomePerformance =
            $report['ai_outcome_performance']
            ?? [];

        $workflowPerformance =
            $performance['workflow_performance']
            ?? [];

        $operationalPerformance =
            $performance['operational_performance']
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | 3. Create Snapshot
        |--------------------------------------------------------------------------
        */

        $snapshot =
            AIExecutiveIntelligenceSnapshot::create([

                'report_status' =>
                    $report['report_status']
                    ?? 'UNKNOWN',

                /*
                |--------------------------------------------------------------------------
                | Census
                |--------------------------------------------------------------------------
                */

                'active_care_residents' =>
                    (int) (
                        $facilityCensus[
                            'active_care_residents'
                        ]
                        ?? 0
                    ),

                'non_active_residents' =>
                    (int) (
                        $facilityCensus[
                            'non_active_residents'
                        ]
                        ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | Operational Risk
                |--------------------------------------------------------------------------
                */

                'active_critical_cases' =>
                    (int) (
                        $operationalRisk[
                            'active_critical_cases'
                        ]
                        ?? 0
                    ),

                'active_care_alerts' =>
                    (int) (
                        $operationalRisk[
                            'active_care_alerts'
                        ]
                        ?? 0
                    ),

                'predictive_priority_residents' =>
                    (int) (
                        $operationalRisk[
                            'predictive_priority_residents'
                        ]
                        ?? 0
                    ),

                'care_priority_residents' =>
                    (int) (
                        $operationalRisk[
                            'care_priority_residents'
                        ]
                        ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | Care Execution
                |--------------------------------------------------------------------------
                */

                'execution_ready_actions' =>
                    (int) (
                        $careExecution[
                            'execution_ready_actions'
                        ]
                        ?? 0
                    ),

                'doctor_review_actions' =>
                    (int) (
                        $careExecution[
                            'doctor_review_actions'
                        ]
                        ?? 0
                    ),

                'evaluated_workflows' =>
                    (int) (
                        $careExecution[
                            'evaluated_workflows'
                        ]
                        ?? 0
                    ),

                'workflow_success_rate' =>
                    (float) (
                        $careExecution[
                            'workflow_success_rate'
                        ]
                        ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | AI Outcome Performance
                |--------------------------------------------------------------------------
                */

                'total_outcomes_recorded' =>
                    (int) (
                        $outcomePerformance[
                            'total_outcomes_recorded'
                        ]
                        ?? 0
                    ),

                'average_ai_accuracy' =>
                    (float) (
                        $outcomePerformance[
                            'average_ai_accuracy'
                        ]
                        ?? 0
                    ),

                'intervention_success_rate' =>
                    (float) (
                        $outcomePerformance[
                            'intervention_success_rate'
                        ]
                        ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | Operational Performance
                |--------------------------------------------------------------------------
                */

                'sla_compliance_percentage' =>
                    (float) (
                        $operationalPerformance[
                            'sla_compliance_percentage'
                        ]
                        ?? 0
                    ),

                'task_completion_rate' =>
                    (float) (
                        $operationalPerformance[
                            'task_completion_rate'
                        ]
                        ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | Learning State
                |--------------------------------------------------------------------------
                */

                'learning_maturity' =>
                    $workflowPerformance[
                        'learning_maturity'
                    ]
                    ?? 'AWAITING OUTCOME DATA',

                'learning_confidence' =>
                    $workflowPerformance[
                        'learning_confidence'
                    ]
                    ?? 'NONE',

                /*
                |--------------------------------------------------------------------------
                | Preserve Full Executive Report
                |--------------------------------------------------------------------------
                */

                'snapshot_payload' =>
                    $report,

                /*
                |--------------------------------------------------------------------------
                | Capture Time
                |--------------------------------------------------------------------------
                */

                'captured_at' =>
                    now(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | 4. Return Snapshot
        |--------------------------------------------------------------------------
        */

        return [

            'snapshot_created' =>
                true,

            'snapshot_id' =>
                $snapshot->id,

            'captured_at' =>
                $snapshot->captured_at,

            'report_status' =>
                $snapshot->report_status,

            'snapshot_summary' => [

                'active_care_residents' =>
                    $snapshot->active_care_residents,

                'active_critical_cases' =>
                    $snapshot->active_critical_cases,

                'active_care_alerts' =>
                    $snapshot->active_care_alerts,

                'predictive_priority_residents' =>
                    $snapshot->predictive_priority_residents,

                'care_priority_residents' =>
                    $snapshot->care_priority_residents,

                'execution_ready_actions' =>
                    $snapshot->execution_ready_actions,

                'evaluated_workflows' =>
                    $snapshot->evaluated_workflows,

                'workflow_success_rate' =>
                    $snapshot->workflow_success_rate,

                'average_ai_accuracy' =>
                    $snapshot->average_ai_accuracy,

                'sla_compliance_percentage' =>
                    $snapshot->sla_compliance_percentage,

                'task_completion_rate' =>
                    $snapshot->task_completion_rate,

                'learning_maturity' =>
                    $snapshot->learning_maturity,
            ],
        ];
    }
}