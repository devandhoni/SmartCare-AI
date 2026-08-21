<?php

namespace App\Services;

use App\Models\AIExecutiveIntelligenceSnapshot;

class AIExecutiveTrendIntelligenceEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 53.5D
    | Executive Trend & Change Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Latest Two Snapshots
        |--------------------------------------------------------------------------
        */

        $snapshots =
            AIExecutiveIntelligenceSnapshot::query()
                ->orderByDesc('captured_at')
                ->limit(2)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. No Snapshot Available
        |--------------------------------------------------------------------------
        */

        if ($snapshots->count() === 0) {
            return [

                'trend_status' =>
                    'NO_DATA',

                'history_available' =>
                    false,

                'snapshot_count_available' =>
                    0,

                'message' =>
                    'No executive intelligence snapshots are available for trend analysis.',

                'current_snapshot' =>
                    null,

                'previous_snapshot' =>
                    null,

                'metric_trends' =>
                    [],

                'overall_trend' =>
                    'UNKNOWN',

                'trend_findings' =>
                    [],

                'guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Current Snapshot
        |--------------------------------------------------------------------------
        */

        $current =
            $snapshots->first();

        /*
        |--------------------------------------------------------------------------
        | 4. Baseline Only
        |--------------------------------------------------------------------------
        |
        | We deliberately do NOT manufacture a trend when only one snapshot
        | exists.
        |
        */

        if ($snapshots->count() < 2) {
            return [

                'trend_status' =>
                    'BASELINE_ONLY',

                'history_available' =>
                    false,

                'snapshot_count_available' =>
                    1,

                'message' =>
                    'Initial executive intelligence baseline has been recorded. At least two snapshots are required before change direction can be evaluated.',

                'current_snapshot' =>
                    $this->snapshotSummary(
                        $current
                    ),

                'previous_snapshot' =>
                    null,

                'metric_trends' =>
                    [],

                'overall_trend' =>
                    'BASELINE',

                'trend_findings' => [
                    'Executive intelligence baseline is available.',
                    'No improving or worsening trend is reported because only one snapshot exists.',
                ],

                'guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Previous Snapshot
        |--------------------------------------------------------------------------
        */

        $previous =
            $snapshots->get(1);

        /*
        |--------------------------------------------------------------------------
        | 6. Compare Executive Risk Metrics
        |--------------------------------------------------------------------------
        |
        | For these metrics:
        |
        | LOWER = generally operationally better.
        |
        */

        $riskMetrics = [

            'active_critical_cases' =>
                'Active Critical Cases',

            'active_care_alerts' =>
                'Active Care Alerts',

            'predictive_priority_residents' =>
                'Predictive Priority Residents',

            'care_priority_residents' =>
                'Care Priority Residents',

            'doctor_review_actions' =>
                'Doctor Review Actions',
        ];

        $metricTrends = [];

        foreach (
            $riskMetrics
            as $field => $label
        ) {
            $metricTrends[$field] =
                $this->compareLowerIsBetter(
                    $label,
                    (float) $current->{$field},
                    (float) $previous->{$field}
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Compare Performance Metrics
        |--------------------------------------------------------------------------
        |
        | For these metrics:
        |
        | HIGHER = generally better.
        |
        */

        $performanceMetrics = [

            'workflow_success_rate' =>
                'Workflow Success Rate',

            'average_ai_accuracy' =>
                'Average AI Accuracy',

            'intervention_success_rate' =>
                'Intervention Success Rate',

            'sla_compliance_percentage' =>
                'SLA Compliance',

            'task_completion_rate' =>
                'Task Completion Rate',
        ];

        foreach (
            $performanceMetrics
            as $field => $label
        ) {
            $metricTrends[$field] =
                $this->compareHigherIsBetter(
                    $label,
                    (float) $current->{$field},
                    (float) $previous->{$field}
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Informational / Volume Metrics
        |--------------------------------------------------------------------------
        |
        | These should not automatically be classified as clinically better
        | or worse simply because they increased or decreased.
        |
        */

        $metricTrends[
            'execution_ready_actions'
        ] =
            $this->compareInformational(
                'Execution Ready Actions',
                (float) $current->execution_ready_actions,
                (float) $previous->execution_ready_actions
            );

        $metricTrends[
            'evaluated_workflows'
        ] =
            $this->compareLearningVolume(
                'Evaluated Workflows',
                (float) $current->evaluated_workflows,
                (float) $previous->evaluated_workflows
            );

        $metricTrends[
            'total_outcomes_recorded'
        ] =
            $this->compareLearningVolume(
                'Recorded Clinical Outcomes',
                (float) $current->total_outcomes_recorded,
                (float) $previous->total_outcomes_recorded
            );

        /*
        |--------------------------------------------------------------------------
        | 9. Learning Maturity Change
        |--------------------------------------------------------------------------
        */

        $learningTrend =
            $this->compareLearningMaturity(
                $current->learning_maturity,
                $previous->learning_maturity
            );

        /*
        |--------------------------------------------------------------------------
        | 10. Determine Overall Direction
        |--------------------------------------------------------------------------
        */

        $improving = 0;
        $worsening = 0;
        $stable = 0;

        foreach ($metricTrends as $metric) {

            $impact =
                $metric['impact']
                ?? 'NEUTRAL';

            if ($impact === 'POSITIVE') {
                $improving++;
            } elseif ($impact === 'NEGATIVE') {
                $worsening++;
            } else {
                $stable++;
            }
        }

        if (
            ($learningTrend['impact'] ?? null)
            === 'POSITIVE'
        ) {
            $improving++;
        }

        /*
        |--------------------------------------------------------------------------
        | Overall Trend Classification
        |--------------------------------------------------------------------------
        */

        $overallTrend =
            'STABLE';

        if (
            $improving > $worsening
            &&
            $improving > 0
        ) {
            $overallTrend =
                'IMPROVING';

        } elseif (
            $worsening > $improving
            &&
            $worsening > 0
        ) {
            $overallTrend =
                'WORSENING';

        } elseif (
            $improving > 0
            &&
            $worsening > 0
        ) {
            $overallTrend =
                'MIXED';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Important Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        foreach ($metricTrends as $metric) {

            if (
                ($metric['impact'] ?? null)
                === 'POSITIVE'
            ) {
                $findings[] =
                    $metric['label']
                    . ' improved from '
                    . $metric['previous_value']
                    . ' to '
                    . $metric['current_value']
                    . '.';
            }

            if (
                ($metric['impact'] ?? null)
                === 'NEGATIVE'
            ) {
                $findings[] =
                    $metric['label']
                    . ' changed adversely from '
                    . $metric['previous_value']
                    . ' to '
                    . $metric['current_value']
                    . '.';
            }
        }

        if (
            $learningTrend['direction']
            === 'ADVANCING'
        ) {
            $findings[] =
                'AI learning maturity advanced from '
                . $learningTrend['previous_value']
                . ' to '
                . $learningTrend['current_value']
                . '.';
        }

        if (empty($findings)) {
            $findings[] =
                'No significant executive intelligence change is detected between the two latest snapshots.';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Management Interpretation
        |--------------------------------------------------------------------------
        */

        $managementInterpretation = [];

        if ($overallTrend === 'IMPROVING') {

            $managementInterpretation[] =
                'Executive intelligence indicates overall improvement compared with the previous recorded snapshot.';

        } elseif ($overallTrend === 'WORSENING') {

            $managementInterpretation[] =
                'Executive intelligence indicates overall deterioration compared with the previous recorded snapshot. Management review is recommended.';

        } elseif ($overallTrend === 'MIXED') {

            $managementInterpretation[] =
                'Executive intelligence shows mixed movement, with some indicators improving while others require attention.';

        } else {

            $managementInterpretation[] =
                'Executive intelligence remains broadly stable compared with the previous recorded snapshot.';
        }

        /*
        |--------------------------------------------------------------------------
        | Small Learning Sample Guardrail
        |--------------------------------------------------------------------------
        */

        if (
            (int) $current->evaluated_workflows
            < 5
        ) {
            $managementInterpretation[] =
                'AI workflow effectiveness remains based on a limited evaluation sample and should not yet be treated as mature facility-wide evidence.';
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Final Trend Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'trend_status' =>
                'COMPARISON_AVAILABLE',

            'history_available' =>
                true,

            'snapshot_count_available' =>
                AIExecutiveIntelligenceSnapshot::count(),

            'comparison_period' => [

                'previous_captured_at' =>
                    $previous->captured_at,

                'current_captured_at' =>
                    $current->captured_at,

                'minutes_between_snapshots' =>
                    $previous->captured_at
                        ->diffInMinutes(
                            $current->captured_at
                        ),
            ],

            'current_snapshot' =>
                $this->snapshotSummary(
                    $current
                ),

            'previous_snapshot' =>
                $this->snapshotSummary(
                    $previous
                ),

            'overall_trend' =>
                $overallTrend,

            'trend_scorecard' => [

                'improving_indicators' =>
                    $improving,

                'worsening_indicators' =>
                    $worsening,

                'stable_or_neutral_indicators' =>
                    $stable,
            ],

            'metric_trends' =>
                $metricTrends,

            'learning_maturity_trend' =>
                $learningTrend,

            'trend_findings' =>
                $findings,

            'management_interpretation' =>
                $managementInterpretation,

            'guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Lower Is Better Comparison
    |--------------------------------------------------------------------------
    */

    private function compareLowerIsBetter(
        string $label,
        float $current,
        float $previous
    ): array {
        $change =
            round(
                $current - $previous,
                2
            );

        if ($current < $previous) {
            $direction =
                'DECREASING';

            $impact =
                'POSITIVE';

        } elseif ($current > $previous) {
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

            'previous_value' =>
                $previous,

            'current_value' =>
                $current,

            'absolute_change' =>
                $change,

            'direction' =>
                $direction,

            'impact' =>
                $impact,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Higher Is Better Comparison
    |--------------------------------------------------------------------------
    */

    private function compareHigherIsBetter(
        string $label,
        float $current,
        float $previous
    ): array {
        $change =
            round(
                $current - $previous,
                2
            );

        if ($current > $previous) {
            $direction =
                'INCREASING';

            $impact =
                'POSITIVE';

        } elseif ($current < $previous) {
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

            'previous_value' =>
                $previous,

            'current_value' =>
                $current,

            'absolute_change' =>
                $change,

            'direction' =>
                $direction,

            'impact' =>
                $impact,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Informational Change
    |--------------------------------------------------------------------------
    */

    private function compareInformational(
        string $label,
        float $current,
        float $previous
    ): array {
        if ($current > $previous) {

            $direction =
                'INCREASING';

        } elseif ($current < $previous) {

            $direction =
                'DECREASING';

        } else {

            $direction =
                'STABLE';
        }

        return [

            'label' =>
                $label,

            'previous_value' =>
                $previous,

            'current_value' =>
                $current,

            'absolute_change' =>
                round(
                    $current - $previous,
                    2
                ),

            'direction' =>
                $direction,

            /*
             * Workload volume alone is not automatically
             * clinically positive or negative.
             */
            'impact' =>
                'NEUTRAL',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Learning Volume Comparison
    |--------------------------------------------------------------------------
    */

    private function compareLearningVolume(
        string $label,
        float $current,
        float $previous
    ): array {
        if ($current > $previous) {

            $direction =
                'INCREASING';

            $impact =
                'POSITIVE';

        } elseif ($current < $previous) {

            /*
             * Normally cumulative evaluated workflow/outcome counts should
             * not decrease, so flag this for review.
             */

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

            'previous_value' =>
                $previous,

            'current_value' =>
                $current,

            'absolute_change' =>
                round(
                    $current - $previous,
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
    | Learning Maturity Comparison
    |--------------------------------------------------------------------------
    */

    private function compareLearningMaturity(
        ?string $current,
        ?string $previous
    ): array {
        $rank = [

            'AWAITING OUTCOME DATA' => 0,

            'EARLY LEARNING' => 1,

            'DEVELOPING LEARNING' => 2,

            'MATURE LEARNING' => 3,
        ];

        $currentValue =
            strtoupper(
                (string) (
                    $current
                    ?? 'AWAITING OUTCOME DATA'
                )
            );

        $previousValue =
            strtoupper(
                (string) (
                    $previous
                    ?? 'AWAITING OUTCOME DATA'
                )
            );

        $currentRank =
            $rank[$currentValue]
            ?? 0;

        $previousRank =
            $rank[$previousValue]
            ?? 0;

        if ($currentRank > $previousRank) {

            $direction =
                'ADVANCING';

            $impact =
                'POSITIVE';

        } elseif ($currentRank < $previousRank) {

            $direction =
                'REGRESSING';

            $impact =
                'NEGATIVE';

        } else {

            $direction =
                'STABLE';

            $impact =
                'NEUTRAL';
        }

        return [

            'previous_value' =>
                $previousValue,

            'current_value' =>
                $currentValue,

            'direction' =>
                $direction,

            'impact' =>
                $impact,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Snapshot Summary
    |--------------------------------------------------------------------------
    */

    private function snapshotSummary(
        AIExecutiveIntelligenceSnapshot $snapshot
    ): array {
        return [

            'snapshot_id' =>
                $snapshot->id,

            'captured_at' =>
                $snapshot->captured_at,

            'report_status' =>
                $snapshot->report_status,

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

            'doctor_review_actions' =>
                $snapshot->doctor_review_actions,

            'evaluated_workflows' =>
                $snapshot->evaluated_workflows,

            'workflow_success_rate' =>
                (float)
                $snapshot->workflow_success_rate,

            'total_outcomes_recorded' =>
                $snapshot->total_outcomes_recorded,

            'average_ai_accuracy' =>
                (float)
                $snapshot->average_ai_accuracy,

            'intervention_success_rate' =>
                (float)
                $snapshot->intervention_success_rate,

            'sla_compliance_percentage' =>
                (float)
                $snapshot->sla_compliance_percentage,

            'task_completion_rate' =>
                (float)
                $snapshot->task_completion_rate,

            'learning_maturity' =>
                $snapshot->learning_maturity,

            'learning_confidence' =>
                $snapshot->learning_confidence,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Trend Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'minimum_snapshots_for_trend' =>
                2,

            'trend_based_on_stored_snapshots' =>
                true,

            'historical_data_not_reconstructed' =>
                true,

            'automatic_clinical_action' =>
                false,

            'automatic_management_action' =>
                false,

            'human_interpretation_required' =>
                true,

            'message' =>
                'Executive trend intelligence compares stored facility snapshots only and does not independently generate clinical conclusions.',
        ];
    }
}