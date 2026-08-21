<?php

namespace App\Services;

use App\Models\AIExecutiveIntelligenceSnapshot;
use Carbon\Carbon;

class AIExecutiveReportingPeriodIntelligence
{
    /*
    |--------------------------------------------------------------------------
    | Step 53.8C
    | Executive Reporting Period Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(
        int $days = 7
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Reporting Period
        |--------------------------------------------------------------------------
        */

        $days =
            max(
                1,
                min(
                    $days,
                    365
                )
            );

        $periodEnd =
            now();

        $periodStart =
            now()
                ->copy()
                ->subDays(
                    $days
                );


        /*
        |--------------------------------------------------------------------------
        | 2. Load Snapshots
        |--------------------------------------------------------------------------
        */

        $snapshots =
            AIExecutiveIntelligenceSnapshot::query()
                ->whereBetween(
                    'captured_at',
                    [
                        $periodStart,
                        $periodEnd,
                    ]
                )
                ->orderBy(
                    'captured_at'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | 3. No Data
        |--------------------------------------------------------------------------
        */

        if ($snapshots->isEmpty()) {
            return [

                'period_status' =>
                    'NO_DATA',

                'reporting_period' => [

                    'days' =>
                        $days,

                    'start' =>
                        $periodStart,

                    'end' =>
                        $periodEnd,
                ],

                'snapshot_count' =>
                    0,

                'period_summary' =>
                    [],

                'metric_changes' =>
                    [],

                'period_trend' =>
                    'UNKNOWN',

                'findings' => [
                    'No executive intelligence snapshots are available for the selected reporting period.',
                ],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 4. First and Last Snapshot
        |--------------------------------------------------------------------------
        */

        $first =
            $snapshots->first();

        $last =
            $snapshots->last();


        /*
        |--------------------------------------------------------------------------
        | 5. Calculate Period Averages
        |--------------------------------------------------------------------------
        */

        $averages = [

            'active_critical_cases' =>
                round(
                    $snapshots->avg(
                        'active_critical_cases'
                    ),
                    2
                ),

            'active_care_alerts' =>
                round(
                    $snapshots->avg(
                        'active_care_alerts'
                    ),
                    2
                ),

            'predictive_priority_residents' =>
                round(
                    $snapshots->avg(
                        'predictive_priority_residents'
                    ),
                    2
                ),

            'care_priority_residents' =>
                round(
                    $snapshots->avg(
                        'care_priority_residents'
                    ),
                    2
                ),

            'workflow_success_rate' =>
                round(
                    $snapshots->avg(
                        'workflow_success_rate'
                    ),
                    2
                ),

            'average_ai_accuracy' =>
                round(
                    $snapshots->avg(
                        'average_ai_accuracy'
                    ),
                    2
                ),

            'sla_compliance_percentage' =>
                round(
                    $snapshots->avg(
                        'sla_compliance_percentage'
                    ),
                    2
                ),

            'task_completion_rate' =>
                round(
                    $snapshots->avg(
                        'task_completion_rate'
                    ),
                    2
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | 6. Compare First vs Last Snapshot
        |--------------------------------------------------------------------------
        */

        $metricChanges = [

            'active_critical_cases' =>
                $this->compareLowerIsBetter(
                    'Active Critical Cases',
                    (float) $first->active_critical_cases,
                    (float) $last->active_critical_cases
                ),

            'active_care_alerts' =>
                $this->compareLowerIsBetter(
                    'Active Care Alerts',
                    (float) $first->active_care_alerts,
                    (float) $last->active_care_alerts
                ),

            'predictive_priority_residents' =>
                $this->compareLowerIsBetter(
                    'Predictive Priority Residents',
                    (float) $first->predictive_priority_residents,
                    (float) $last->predictive_priority_residents
                ),

            'care_priority_residents' =>
                $this->compareLowerIsBetter(
                    'Care Priority Residents',
                    (float) $first->care_priority_residents,
                    (float) $last->care_priority_residents
                ),

            'workflow_success_rate' =>
                $this->compareHigherIsBetter(
                    'Workflow Success Rate',
                    (float) $first->workflow_success_rate,
                    (float) $last->workflow_success_rate
                ),

            'average_ai_accuracy' =>
                $this->compareHigherIsBetter(
                    'Average AI Accuracy',
                    (float) $first->average_ai_accuracy,
                    (float) $last->average_ai_accuracy
                ),

            'sla_compliance_percentage' =>
                $this->compareHigherIsBetter(
                    'SLA Compliance',
                    (float) $first->sla_compliance_percentage,
                    (float) $last->sla_compliance_percentage
                ),

            'task_completion_rate' =>
                $this->compareHigherIsBetter(
                    'Task Completion Rate',
                    (float) $first->task_completion_rate,
                    (float) $last->task_completion_rate
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | 7. Overall Period Trend
        |--------------------------------------------------------------------------
        */

        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($metricChanges as $metric) {

            $impact =
                $metric[
                    'impact'
                ]
                ?? 'NEUTRAL';

            if ($impact === 'POSITIVE') {
                $positive++;

            } elseif ($impact === 'NEGATIVE') {
                $negative++;

            } else {
                $neutral++;
            }
        }


        $periodTrend =
            'STABLE';

        if (
            $positive > $negative
            &&
            $positive > 0
        ) {
            $periodTrend =
                'IMPROVING';

        } elseif (
            $negative > $positive
            &&
            $negative > 0
        ) {
            $periodTrend =
                'WORSENING';

        } elseif (
            $positive > 0
            &&
            $negative > 0
        ) {
            $periodTrend =
                'MIXED';
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        foreach ($metricChanges as $metric) {

            if (
                (
                    $metric[
                        'impact'
                    ]
                    ?? 'NEUTRAL'
                )
                === 'POSITIVE'
            ) {
                $findings[] =
                    $metric['label']
                    . ' improved from '
                    . $metric['start_value']
                    . ' to '
                    . $metric['end_value']
                    . '.';
            }

            if (
                (
                    $metric[
                        'impact'
                    ]
                    ?? 'NEUTRAL'
                )
                === 'NEGATIVE'
            ) {
                $findings[] =
                    $metric['label']
                    . ' changed adversely from '
                    . $metric['start_value']
                    . ' to '
                    . $metric['end_value']
                    . '.';
            }
        }


        if (empty($findings)) {
            $findings[] =
                'No material executive intelligence change was detected across the selected reporting period.';
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Reporting Data Quality
        |--------------------------------------------------------------------------
        */

        $uniqueDates =
            $snapshots
                ->map(
                    fn ($snapshot) =>
                        $snapshot
                            ->captured_at
                            ->format(
                                'Y-m-d'
                            )
                )
                ->unique()
                ->count();

        $coveragePercentage =
            round(
                min(
                    100,
                    (
                        $uniqueDates
                        /
                        $days
                    )
                    * 100
                ),
                2
            );

        $coverageStatus =
            'LIMITED';

        if ($coveragePercentage >= 80) {
            $coverageStatus =
                'GOOD';

        } elseif ($coveragePercentage >= 50) {
            $coverageStatus =
                'PARTIAL';
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Final Period Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'period_status' =>
                'AVAILABLE',

            'reporting_period' => [

                'days' =>
                    $days,

                'start' =>
                    $periodStart,

                'end' =>
                    $periodEnd,
            ],

            'snapshot_count' =>
                $snapshots->count(),

            'unique_reporting_days' =>
                $uniqueDates,

            'data_coverage' => [

                'coverage_percentage' =>
                    $coveragePercentage,

                'coverage_status' =>
                    $coverageStatus,

                'message' =>
                    $coveragePercentage < 50
                    ?
                    'Reporting-period interpretation is limited because snapshot coverage is currently sparse.'
                    :
                    'Snapshot coverage is sufficient for broader period interpretation.',
            ],

            'period_summary' => [

                'first_snapshot_id' =>
                    $first->id,

                'last_snapshot_id' =>
                    $last->id,

                'first_snapshot_at' =>
                    $first->captured_at,

                'last_snapshot_at' =>
                    $last->captured_at,

                'start_report_status' =>
                    $first->report_status,

                'end_report_status' =>
                    $last->report_status,
            ],

            'period_averages' =>
                $averages,

            'metric_changes' =>
                $metricChanges,

            'period_scorecard' => [

                'improving_indicators' =>
                    $positive,

                'worsening_indicators' =>
                    $negative,

                'stable_indicators' =>
                    $neutral,
            ],

            'period_trend' =>
                $periodTrend,

            'findings' =>
                $findings,

            'reporting_guardrails' => [

                'period_analysis_uses_stored_snapshots_only' =>
                    true,

                'duplicate_same_day_snapshots_may_exist_in_test_data' =>
                    true,

                'automatic_clinical_action' =>
                    false,

                'automatic_management_action' =>
                    false,

                'human_interpretation_required' =>
                    true,

                'message' =>
                    'Executive reporting-period intelligence is derived from stored snapshots and is intended for management interpretation only.',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Lower Is Better
    |--------------------------------------------------------------------------
    */

    private function compareLowerIsBetter(
        string $label,
        float $start,
        float $end
    ): array {
        if ($end < $start) {

            $direction =
                'DECREASING';

            $impact =
                'POSITIVE';

        } elseif ($end > $start) {

            $direction =
                'INCREASING';

            $impact =
                'NEGATIVE';

        } else {

            $direction =
                'STABLE';

            $impact =
                'NEUTRAL';
        }

        return [

            'label' =>
                $label,

            'start_value' =>
                $start,

            'end_value' =>
                $end,

            'absolute_change' =>
                round(
                    $end - $start,
                    2
                ),

            'direction' =>
                $direction,

            'impact' =>
                $impact,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Higher Is Better
    |--------------------------------------------------------------------------
    */

    private function compareHigherIsBetter(
        string $label,
        float $start,
        float $end
    ): array {
        if ($end > $start) {

            $direction =
                'INCREASING';

            $impact =
                'POSITIVE';

        } elseif ($end < $start) {

            $direction =
                'DECREASING';

            $impact =
                'NEGATIVE';

        } else {

            $direction =
                'STABLE';

            $impact =
                'NEUTRAL';
        }

        return [

            'label' =>
                $label,

            'start_value' =>
                $start,

            'end_value' =>
                $end,

            'absolute_change' =>
                round(
                    $end - $start,
                    2
                ),

            'direction' =>
                $direction,

            'impact' =>
                $impact,
        ];
    }
}