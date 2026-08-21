<?php

namespace App\Services;

class AIExecutiveReportingPeriodSummaryEngine
{
    protected AIExecutiveReportingPeriodIntelligence $periodIntelligence;

    public function __construct(
        AIExecutiveReportingPeriodIntelligence $periodIntelligence
    ) {
        $this->periodIntelligence =
            $periodIntelligence;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.9
    | Executive Reporting Period Summary & Management Insights
    |--------------------------------------------------------------------------
    */

    public function analyze(
        int $days = 7
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Reporting Period Intelligence
        |--------------------------------------------------------------------------
        */

        $period =
            $this->periodIntelligence->analyze(
                $days
            );


        /*
        |--------------------------------------------------------------------------
        | 2. No Period Data
        |--------------------------------------------------------------------------
        */

        if (
            (
                $period[
                    'period_status'
                ]
                ?? 'NO_DATA'
            )
            !== 'AVAILABLE'
        ) {
            return [

                'summary_status' =>
                    'NO_DATA',

                'reporting_period_days' =>
                    $days,

                'management_summary' =>
                    'No executive intelligence snapshot data is available for the selected reporting period.',

                'management_insights' =>
                    [],

                'management_priorities' =>
                    [],

                'period_confidence' =>
                    'NONE',

                'source_period_intelligence' =>
                    $period,

                'guardrails' => [

                    'source_period_intelligence_only' =>
                        true,

                    'automatic_management_action' =>
                        false,

                    'automatic_clinical_action' =>
                        false,

                    'human_interpretation_required' =>
                        true,
                ],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Source Sections
        |--------------------------------------------------------------------------
        */

        $coverage =
            $period[
                'data_coverage'
            ]
            ?? [];

        $averages =
            $period[
                'period_averages'
            ]
            ?? [];

        $changes =
            $period[
                'metric_changes'
            ]
            ?? [];

        $scorecard =
            $period[
                'period_scorecard'
            ]
            ?? [];

        $periodTrend =
            strtoupper(
                (string) (
                    $period[
                        'period_trend'
                    ]
                    ?? 'UNKNOWN'
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 4. Coverage Interpretation
        |--------------------------------------------------------------------------
        */

        $coveragePercentage =
            (float) (
                $coverage[
                    'coverage_percentage'
                ]
                ?? 0
            );

        $coverageStatus =
            strtoupper(
                (string) (
                    $coverage[
                        'coverage_status'
                    ]
                    ?? 'LIMITED'
                )
            );

        $periodConfidence =
            match ($coverageStatus) {

                'GOOD' =>
                    'HIGHER',

                'PARTIAL' =>
                    'MODERATE',

                default =>
                    'LIMITED',
            };


        /*
        |--------------------------------------------------------------------------
        | 5. Management Insight Containers
        |--------------------------------------------------------------------------
        */

        $managementInsights = [];

        $managementPriorities = [];


        /*
        |--------------------------------------------------------------------------
        | 6. Coverage Insight
        |--------------------------------------------------------------------------
        */

        if ($coverageStatus === 'LIMITED') {

            $managementInsights[] =
                'Reporting-period coverage is limited at '
                . $coveragePercentage
                . '%. Trend interpretation should remain cautious until more scheduled daily snapshots are available.';

            $managementPriorities[] =
                'Continue scheduled daily executive snapshot collection to strengthen reporting-period reliability.';

        } elseif ($coverageStatus === 'PARTIAL') {

            $managementInsights[] =
                'Reporting-period coverage is partial at '
                . $coveragePercentage
                . '%. Current period trends are informative but should still be interpreted with caution.';

        } else {

            $managementInsights[] =
                'Reporting-period snapshot coverage is strong at '
                . $coveragePercentage
                . '%, supporting broader period-level management interpretation.';
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Overall Trend Insight
        |--------------------------------------------------------------------------
        */

        if ($periodTrend === 'IMPROVING') {

            $managementInsights[] =
                'Executive reporting-period indicators show an overall improving direction.';

        } elseif ($periodTrend === 'WORSENING') {

            $managementInsights[] =
                'Executive reporting-period indicators show an overall worsening direction requiring management review.';

            $managementPriorities[] =
                'Review worsening operational and clinical performance indicators identified in the selected reporting period.';

        } elseif ($periodTrend === 'MIXED') {

            $managementInsights[] =
                'Executive reporting-period performance is mixed, with both improving and worsening indicators present.';

            $managementPriorities[] =
                'Review the individual worsening indicators before drawing an overall facility-performance conclusion.';

        } else {

            $managementInsights[] =
                'No material overall executive intelligence change was detected across the selected reporting period.';
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Operational Risk Insights
        |--------------------------------------------------------------------------
        */

        $criticalCases =
            $changes[
                'active_critical_cases'
            ]
            ?? null;

        if (is_array($criticalCases)) {

            $impact =
                $criticalCases[
                    'impact'
                ]
                ?? 'NEUTRAL';

            if ($impact === 'NEGATIVE') {

                $managementInsights[] =
                    'Active critical cases increased during the reporting period.';

                $managementPriorities[] =
                    'Review the increase in active critical cases and confirm adequate clinical escalation capacity.';

            } elseif ($impact === 'POSITIVE') {

                $managementInsights[] =
                    'Active critical cases decreased during the reporting period.';

            } else {

                $managementInsights[] =
                    'Active critical case volume remained stable during the reporting period.';
            }
        }


        $activeAlerts =
            $changes[
                'active_care_alerts'
            ]
            ?? null;

        if (is_array($activeAlerts)) {

            $impact =
                $activeAlerts[
                    'impact'
                ]
                ?? 'NEUTRAL';

            if ($impact === 'NEGATIVE') {

                $managementInsights[] =
                    'Active-care AI alerts increased during the reporting period.';

                $managementPriorities[] =
                    'Review unresolved AI alert workload and escalation handling capacity.';

            } elseif ($impact === 'POSITIVE') {

                $managementInsights[] =
                    'Active-care AI alerts decreased during the reporting period.';

            } else {

                $managementInsights[] =
                    'Active-care AI alert volume remained stable during the reporting period.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 9. AI Workflow Effectiveness Insight
        |--------------------------------------------------------------------------
        */

        $workflowSuccessRate =
            (float) (
                $averages[
                    'workflow_success_rate'
                ]
                ?? 0
            );

        $managementInsights[] =
            'Average AI care workflow success rate during the selected period is '
            . $workflowSuccessRate
            . '%.';

        if (
            (
                $period[
                    'unique_reporting_days'
                ]
                ?? 0
            )
            < 5
        ) {
            $managementInsights[] =
                'Current AI workflow effectiveness should remain classified as preliminary because reporting history is still limited.';

            $managementPriorities[] =
                'Continue collecting workflow outcome and snapshot data before treating AI effectiveness rates as mature facility evidence.';
        }


        /*
        |--------------------------------------------------------------------------
        | 10. AI Accuracy Insight
        |--------------------------------------------------------------------------
        */

        $averageAiAccuracy =
            (float) (
                $averages[
                    'average_ai_accuracy'
                ]
                ?? 0
            );

        $managementInsights[] =
            'Average recorded AI accuracy across the reporting period is '
            . $averageAiAccuracy
            . '%.';


        /*
        |--------------------------------------------------------------------------
        | 11. SLA Performance Insight
        |--------------------------------------------------------------------------
        */

        $slaCompliance =
            (float) (
                $averages[
                    'sla_compliance_percentage'
                ]
                ?? 0
            );

        $managementInsights[] =
            'Average escalation SLA compliance during the reporting period is '
            . $slaCompliance
            . '%.';

        if ($slaCompliance < 80) {

            $managementPriorities[] =
                'Review escalation response processes because average SLA compliance remains below 80%.';
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Task Completion Insight
        |--------------------------------------------------------------------------
        */

        $taskCompletionRate =
            (float) (
                $averages[
                    'task_completion_rate'
                ]
                ?? 0
            );

        $managementInsights[] =
            'Average nurse task completion rate during the reporting period is '
            . $taskCompletionRate
            . '%.';

        if ($taskCompletionRate < 70) {

            $managementPriorities[] =
                'Review nursing workload and outstanding task capacity because task completion remains below 70%.';
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Scorecard Interpretation
        |--------------------------------------------------------------------------
        */

        $improvingIndicators =
            (int) (
                $scorecard[
                    'improving_indicators'
                ]
                ?? 0
            );

        $worseningIndicators =
            (int) (
                $scorecard[
                    'worsening_indicators'
                ]
                ?? 0
            );

        $stableIndicators =
            (int) (
                $scorecard[
                    'stable_indicators'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | 14. Management Summary
        |--------------------------------------------------------------------------
        */

        $managementSummary =
            $this->buildManagementSummary(
                $days,
                $coverageStatus,
                $coveragePercentage,
                $periodTrend,
                $improvingIndicators,
                $worseningIndicators,
                $stableIndicators,
                $workflowSuccessRate,
                $slaCompliance,
                $taskCompletionRate
            );


        /*
        |--------------------------------------------------------------------------
        | 15. De-duplicate Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            array_values(
                array_unique(
                    $managementPriorities
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 16. Final Period Summary Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'summary_status' =>
                'AVAILABLE',

            'reporting_period_days' =>
                $days,

            'period_confidence' =>
                $periodConfidence,

            'coverage_context' => [

                'coverage_percentage' =>
                    $coveragePercentage,

                'coverage_status' =>
                    $coverageStatus,

                'snapshot_count' =>
                    (int) (
                        $period[
                            'snapshot_count'
                        ]
                        ?? 0
                    ),

                'unique_reporting_days' =>
                    (int) (
                        $period[
                            'unique_reporting_days'
                        ]
                        ?? 0
                    ),
            ],

            'trend_context' => [

                'period_trend' =>
                    $periodTrend,

                'improving_indicators' =>
                    $improvingIndicators,

                'worsening_indicators' =>
                    $worseningIndicators,

                'stable_indicators' =>
                    $stableIndicators,
            ],

            'management_summary' =>
                $managementSummary,

            'management_insights' =>
                $managementInsights,

            'management_priorities' =>
                $managementPriorities,

            'performance_context' => [

                'workflow_success_rate' =>
                    $workflowSuccessRate,

                'average_ai_accuracy' =>
                    $averageAiAccuracy,

                'sla_compliance_percentage' =>
                    $slaCompliance,

                'task_completion_rate' =>
                    $taskCompletionRate,
            ],

            'source_period_intelligence' =>
                $period,

            'summary_guardrails' => [

                'source_period_intelligence_only' =>
                    true,

                'coverage_sensitive_interpretation' =>
                    true,

                'small_sample_caution_required' =>
                    true,

                'automatic_management_action' =>
                    false,

                'automatic_clinical_action' =>
                    false,

                'human_interpretation_required' =>
                    true,

                'message' =>
                    'Executive period summaries are management-support intelligence only and are constrained by stored snapshot coverage and data maturity.',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Build Management Summary
    |--------------------------------------------------------------------------
    */

    private function buildManagementSummary(
        int $days,
        string $coverageStatus,
        float $coveragePercentage,
        string $periodTrend,
        int $improving,
        int $worsening,
        int $stable,
        float $workflowSuccessRate,
        float $slaCompliance,
        float $taskCompletionRate
    ): string {
        $summary =
            'The '
            . $days
            . '-day executive reporting period currently has '
            . $coveragePercentage
            . '% snapshot coverage and is classified as '
            . strtolower($coverageStatus)
            . ' coverage. ';

        $summary .=
            'Overall period direction is '
            . strtolower($periodTrend)
            . ', with '
            . $improving
            . ' improving indicator(s), '
            . $worsening
            . ' worsening indicator(s), and '
            . $stable
            . ' stable indicator(s). ';

        $summary .=
            'Average AI workflow success is '
            . $workflowSuccessRate
            . '%, average escalation SLA compliance is '
            . $slaCompliance
            . '%, and average nurse task completion is '
            . $taskCompletionRate
            . '%.';

        if ($coverageStatus === 'LIMITED') {

            $summary .=
                ' Period conclusions should remain cautious until additional scheduled daily snapshots are accumulated.';
        }

        return $summary;
    }
}