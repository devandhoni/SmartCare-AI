<?php

namespace App\Services;

use App\Models\AIExecutiveIntelligenceSnapshot;

class AIExecutiveReportingPackageEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    protected AIExecutiveNarrativeEngine $narrativeEngine;

    protected AIExecutiveTrendIntelligenceEngine $trendEngine;

    protected AIExecutiveReportingSafetyEngine $safetyEngine;


    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine,
        AIExecutiveNarrativeEngine $narrativeEngine,
        AIExecutiveTrendIntelligenceEngine $trendEngine,
        AIExecutiveReportingSafetyEngine $safetyEngine
    ) {
        $this->reportEngine =
            $reportEngine;

        $this->narrativeEngine =
            $narrativeEngine;

        $this->trendEngine =
            $trendEngine;

        $this->safetyEngine =
            $safetyEngine;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.7
    | Final Executive AI Reporting Package
    |--------------------------------------------------------------------------
    */

    public function generate(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Generate Existing Executive Intelligence Layers
        |--------------------------------------------------------------------------
        */

        $report =
            $this->reportEngine->analyze();

        $narrative =
            $this->narrativeEngine->analyze();

        $trend =
            $this->trendEngine->analyze();

        $safety =
            $this->safetyEngine->analyze();


        /*
        |--------------------------------------------------------------------------
        | 2. Determine Reporting Readiness
        |--------------------------------------------------------------------------
        */

        $reportingReady =
            (bool) (
                $safety[
                    'executive_reporting_ready'
                ]
                ?? false
            );

        $criticalIssues =
            $safety[
                'critical_issues'
            ]
            ?? [];

        $warnings =
            $safety[
                'warnings'
            ]
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | 3. Block Unsafe Reporting Package
        |--------------------------------------------------------------------------
        |
        | The underlying intelligence can still be inspected by developers,
        | but the final executive reporting package must not be declared
        | ready when critical safety validation has failed.
        |
        */

        if (
            !$reportingReady
            ||
            !empty($criticalIssues)
        ) {
            return [

                'package_status' =>
                    'BLOCKED',

                'executive_reporting_ready' =>
                    false,

                'message' =>
                    'Executive AI reporting package was blocked because one or more reporting safety checks failed.',

                'generated_at' =>
                    now(),

                'safety' => [

                    'safety_status' =>
                        $safety[
                            'safety_status'
                        ]
                        ?? 'BLOCKED',

                    'validation_summary' =>
                        $safety[
                            'validation_summary'
                        ]
                        ?? [],

                    'warnings' =>
                        $warnings,

                    'critical_issues' =>
                        $criticalIssues,
                ],

                'reporting_guardrails' =>
                    $safety[
                        'reporting_guardrails'
                    ]
                    ?? [],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Latest Stored Snapshot
        |--------------------------------------------------------------------------
        */

        $latestSnapshot =
            AIExecutiveIntelligenceSnapshot::query()
                ->latest(
                    'captured_at'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | 5. Package Status
        |--------------------------------------------------------------------------
        */

        $packageStatus =
            empty($warnings)
            ?
            'READY'
            :
            'READY_WITH_WARNINGS';


        /*
        |--------------------------------------------------------------------------
        | 6. Executive Headline
        |--------------------------------------------------------------------------
        */

        $reportStatus =
            strtoupper(
                (string) (
                    $report[
                        'report_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $overallTrend =
            strtoupper(
                (string) (
                    $trend[
                        'overall_trend'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $executiveHeadline =
            $this->buildExecutiveHeadline(
                $report,
                $reportStatus,
                $overallTrend
            );


        /*
        |--------------------------------------------------------------------------
        | 7. Management Overview
        |--------------------------------------------------------------------------
        */

        $facilityCensus =
            $report[
                'facility_census'
            ]
            ?? [];

        $operationalRisk =
            $report[
                'operational_risk'
            ]
            ?? [];

        $careExecution =
            $report[
                'care_execution'
            ]
            ?? [];

        $clinicalPerformance =
            $report[
                'clinical_performance'
            ]
            ?? [];

        $aiOutcomePerformance =
            $report[
                'ai_outcome_performance'
            ]
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | 8. Final Executive Reporting Package
        |--------------------------------------------------------------------------
        */

        return [

            /*
            |--------------------------------------------------------------------------
            | Package Identity
            |--------------------------------------------------------------------------
            */

            'package_type' =>
                'SMARTCARE_EXECUTIVE_AI_REPORT',

            'package_version' =>
                '1.0',

            'package_status' =>
                $packageStatus,

            'executive_reporting_ready' =>
                true,

            'generated_at' =>
                now(),

            /*
            |--------------------------------------------------------------------------
            | Executive Headline
            |--------------------------------------------------------------------------
            */

            'executive_headline' =>
                $executiveHeadline,

            /*
            |--------------------------------------------------------------------------
            | Current Facility Status
            |--------------------------------------------------------------------------
            */

            'current_status' => [

                'report_status' =>
                    $reportStatus,

                'overall_trend' =>
                    $overallTrend,

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
            ],

            /*
            |--------------------------------------------------------------------------
            | Care Execution
            |--------------------------------------------------------------------------
            */

            'care_execution_summary' => [

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
            ],

            /*
            |--------------------------------------------------------------------------
            | Clinical Performance
            |--------------------------------------------------------------------------
            */

            'clinical_performance_summary' => [

                'average_response_time_minutes' =>
                    (float) (
                        $clinicalPerformance[
                            'average_response_time_minutes'
                        ]
                        ?? 0
                    ),

                'average_resolution_time_minutes' =>
                    (float) (
                        $clinicalPerformance[
                            'average_resolution_time_minutes'
                        ]
                        ?? 0
                    ),

                'sla_compliance_percentage' =>
                    (float) (
                        $clinicalPerformance[
                            'sla_compliance_percentage'
                        ]
                        ?? 0
                    ),

                'pending_nurse_tasks' =>
                    (int) (
                        $clinicalPerformance[
                            'pending_nurse_tasks'
                        ]
                        ?? 0
                    ),

                'completed_nurse_tasks' =>
                    (int) (
                        $clinicalPerformance[
                            'completed_nurse_tasks'
                        ]
                        ?? 0
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | AI Outcome Performance
            |--------------------------------------------------------------------------
            */

            'ai_outcome_summary' => [

                'total_outcomes_recorded' =>
                    (int) (
                        $aiOutcomePerformance[
                            'total_outcomes_recorded'
                        ]
                        ?? 0
                    ),

                'average_ai_accuracy' =>
                    (float) (
                        $aiOutcomePerformance[
                            'average_ai_accuracy'
                        ]
                        ?? 0
                    ),

                'intervention_success_rate' =>
                    (float) (
                        $aiOutcomePerformance[
                            'intervention_success_rate'
                        ]
                        ?? 0
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Narrative
            |--------------------------------------------------------------------------
            */

            'executive_narrative' =>
                $narrative,

            /*
            |--------------------------------------------------------------------------
            | Trend Intelligence
            |--------------------------------------------------------------------------
            */

            'trend_intelligence' => [

                'trend_status' =>
                    $trend[
                        'trend_status'
                    ]
                    ?? 'NO_DATA',

                'history_available' =>
                    (bool) (
                        $trend[
                            'history_available'
                        ]
                        ?? false
                    ),

                'snapshot_count_available' =>
                    (int) (
                        $trend[
                            'snapshot_count_available'
                        ]
                        ?? 0
                    ),

                'overall_trend' =>
                    $overallTrend,

                'trend_scorecard' =>
                    $trend[
                        'trend_scorecard'
                    ]
                    ?? [],

                'trend_findings' =>
                    $trend[
                        'trend_findings'
                    ]
                    ?? [],

                'management_interpretation' =>
                    $trend[
                        'management_interpretation'
                    ]
                    ?? [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Priority Intelligence
            |--------------------------------------------------------------------------
            */

            'priority_intelligence' => [

                'current_operational_priority' =>
                    $report[
                        'priority_residents'
                    ]
                    ?? [],

                'historical_intelligence' =>
                    $report[
                        'historical_intelligence'
                    ]
                    ?? [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Findings
            |--------------------------------------------------------------------------
            */

            'key_findings' =>
                $report[
                    'key_findings'
                ]
                ?? [],

            'management_actions' =>
                $report[
                    'management_actions'
                ]
                ?? [],

            /*
            |--------------------------------------------------------------------------
            | Reporting Safety
            |--------------------------------------------------------------------------
            */

            'reporting_safety' => [

                'safety_status' =>
                    $safety[
                        'safety_status'
                    ]
                    ?? 'UNKNOWN',

                'validation_summary' =>
                    $safety[
                        'validation_summary'
                    ]
                    ?? [],

                'warnings' =>
                    $warnings,

                'critical_issues' =>
                    $criticalIssues,
            ],

            /*
            |--------------------------------------------------------------------------
            | Snapshot Metadata
            |--------------------------------------------------------------------------
            */

            'snapshot_metadata' => [

                'latest_snapshot_id' =>
                    $latestSnapshot?->id,

                'latest_snapshot_captured_at' =>
                    $latestSnapshot?->captured_at,

                'stored_snapshot_count' =>
                    AIExecutiveIntelligenceSnapshot::count(),

                'historical_comparison_available' =>
                    (
                        AIExecutiveIntelligenceSnapshot::count()
                        >= 2
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Final Reporting Guardrails
            |--------------------------------------------------------------------------
            */

            'reporting_guardrails' =>
                $safety[
                    'reporting_guardrails'
                ]
                ??
                $report[
                    'report_guardrails'
                ]
                ??
                [],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Executive Headline
    |--------------------------------------------------------------------------
    */

    private function buildExecutiveHeadline(
        array $report,
        string $reportStatus,
        string $overallTrend
    ): string {
        $operationalRisk =
            $report[
                'operational_risk'
            ]
            ?? [];

        $criticalCases =
            (int) (
                $operationalRisk[
                    'active_critical_cases'
                ]
                ?? 0
            );

        $priorityResidents =
            (int) (
                $operationalRisk[
                    'predictive_priority_residents'
                ]
                ?? 0
            );

        if ($reportStatus === 'CRITICAL') {
            return
                'Facility requires executive attention with '
                . $criticalCases
                . ' active critical case(s) and '
                . $priorityResidents
                . ' predictive priority resident(s). Current executive trend is '
                . strtolower($overallTrend)
                . '.';
        }

        if (
            $overallTrend === 'WORSENING'
            ||
            $overallTrend === 'MIXED'
        ) {
            return
                'Facility remains operationally active, but executive trend intelligence indicates '
                . strtolower($overallTrend)
                . ' performance requiring management review.';
        }

        if ($overallTrend === 'IMPROVING') {
            return
                'Facility executive intelligence indicates improving operational performance while continuing routine clinical oversight.';
        }

        return
            'Facility executive intelligence is currently stable with no material change detected in the latest reporting comparison.';
    }
}