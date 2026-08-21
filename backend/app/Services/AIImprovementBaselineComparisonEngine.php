<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;
use Illuminate\Support\Facades\DB;

class AIImprovementBaselineComparisonEngine
{
    public function analyze(int $monitoringId): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Monitoring
        |--------------------------------------------------------------------------
        */

        $monitoring = AIImprovementMonitoring::find($monitoringId);

        if (!$monitoring) {
            return [
                'comparison_completed' => false,
                'status' => 'MONITORING_NOT_FOUND',
                'message' => 'AI improvement monitoring record was not found.',
                'monitoring_id' => $monitoringId,
            ];
        }

        if (
            strtoupper((string) $monitoring->monitoring_status)
            !== 'ACTIVE'
        ) {
            return [
                'comparison_completed' => false,
                'status' => 'MONITORING_NOT_ACTIVE',
                'message' => 'Baseline comparison requires an active monitoring record.',
                'monitoring_id' => $monitoring->id,
                'monitoring_status' => $monitoring->monitoring_status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Longitudinal Analysis Required
        |--------------------------------------------------------------------------
        */

        $longitudinal =
            $monitoring->longitudinal_analysis ?? [];

        if (!is_array($longitudinal) || empty($longitudinal)) {
            return [
                'comparison_completed' => false,
                'status' => 'LONGITUDINAL_ANALYSIS_REQUIRED',
                'message' => 'Step 57.4 longitudinal analysis must be completed before baseline comparison.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Load Observations
        |--------------------------------------------------------------------------
        */

        $observations =
            AIImprovementMonitoringObservation::where(
                'monitoring_id',
                $monitoring->id
            )
                ->orderBy('observed_at')
                ->orderBy('id')
                ->get();

        $count = $observations->count();

        $minimumRequired =
            (int) $monitoring->minimum_observations_required;

        if ($count < $minimumRequired) {
            return [
                'comparison_completed' => false,
                'status' => 'INSUFFICIENT_OBSERVATIONS',
                'message' => 'Baseline comparison requires the minimum longitudinal observation threshold.',
                'monitoring_id' => $monitoring->id,
                'observation_count' => $count,
                'minimum_observations_required' => $minimumRequired,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Governance Isolation
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $monitoring->automatic_change_allowed
            ||
            (bool) $monitoring->automatic_rollback_allowed
            ||
            (bool) $monitoring->automatic_deployment_allowed
            ||
            (bool) $monitoring->automatic_clinical_action_allowed
        ) {
            return [
                'comparison_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Baseline comparison is blocked because an automatic-change permission is enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Baseline / Post-Improvement Metrics
        |--------------------------------------------------------------------------
        */

        $baselineScore =
            (float) $monitoring->baseline_score;

        $averageScore =
            (float) (
                $longitudinal['average_observed_score']
                ?? $monitoring->average_observed_score
                ?? 0
            );

        $latestScore =
            (float) (
                $longitudinal['latest_observed_score']
                ?? $monitoring->latest_observed_score
                ?? 0
            );

        $minimumScore =
            (float) (
                $longitudinal['minimum_observed_score']
                ?? $observations->min('observed_score')
                ?? 0
            );

        $maximumScore =
            (float) (
                $longitudinal['maximum_observed_score']
                ?? $observations->max('observed_score')
                ?? 0
            );

        $averageDifference = round(
            $averageScore - $baselineScore,
            2
        );

        $latestDifference = round(
            $latestScore - $baselineScore,
            2
        );

        $averagePercentageDifference =
            $baselineScore != 0
                ? round(
                    ($averageDifference / $baselineScore) * 100,
                    2
                )
                : null;

        /*
        |--------------------------------------------------------------------------
        | 6. Retention Ratio
        |--------------------------------------------------------------------------
        |
        | 100% = average observed score equals the verified baseline.
        | >100% = post-improvement monitoring exceeds the baseline.
        |--------------------------------------------------------------------------
        */

        $retentionRatio =
            $baselineScore != 0
                ? round(
                    ($averageScore / $baselineScore) * 100,
                    2
                )
                : null;

        /*
        |--------------------------------------------------------------------------
        | 7. Benefit Classification
        |--------------------------------------------------------------------------
        */

        $tolerance =
            (float) $monitoring->regression_tolerance_percentage;

        /*
        | Convert tolerance percentage to baseline points.
        */

        $tolerancePoints =
            round(
                $baselineScore
                * ($tolerance / 100),
                2
            );

        if ($averageDifference > 1) {
            $benefitStatus =
                'BENEFIT_IMPROVED';
        } elseif ($averageDifference >= 0) {
            $benefitStatus =
                'BENEFIT_RETAINED';
        } elseif (
            abs($averageDifference)
            <= $tolerancePoints
        ) {
            $benefitStatus =
                'BENEFIT_REDUCED';
        } else {
            $benefitStatus =
                'BENEFIT_LOST';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Below-Baseline Frequency
        |--------------------------------------------------------------------------
        */

        $belowBaselineCount =
            $observations
                ->filter(
                    fn ($observation) =>
                        (float) $observation->observed_score
                        < $baselineScore
                )
                ->count();

        $atBaselineCount =
            $observations
                ->filter(
                    fn ($observation) =>
                        (float) $observation->observed_score
                        === $baselineScore
                )
                ->count();

        $aboveBaselineCount =
            $observations
                ->filter(
                    fn ($observation) =>
                        (float) $observation->observed_score
                        > $baselineScore
                )
                ->count();

        $belowBaselinePercentage = round(
            ($belowBaselineCount / $count) * 100,
            2
        );

        $atOrAboveBaselinePercentage = round(
            (
                ($atBaselineCount + $aboveBaselineCount)
                / $count
            ) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Comparison Confidence
        |--------------------------------------------------------------------------
        */

        if ($count >= 20) {
            $comparisonConfidence = 'HIGH';
        } elseif ($count >= 10) {
            $comparisonConfidence = 'MODERATE';
        } else {
            $comparisonConfidence = 'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Verified controlled-execution baseline is {$baselineScore}.";

        $findings[] =
            "Current post-improvement average is {$averageScore}, representing a {$averageDifference} point difference from baseline.";

        if ($retentionRatio !== null) {
            $findings[] =
                "Average monitored performance retains {$retentionRatio}% of the verified execution baseline score.";
        }

        if ($benefitStatus === 'BENEFIT_IMPROVED') {
            $findings[] =
                'Post-improvement monitored performance currently exceeds the verified execution baseline.';
        } elseif ($benefitStatus === 'BENEFIT_RETAINED') {
            $findings[] =
                'The performance benefit demonstrated during controlled execution is currently retained.';
        } elseif ($benefitStatus === 'BENEFIT_REDUCED') {
            $findings[] =
                'The monitored improvement benefit has reduced but remains within the configured regression tolerance.';
        } else {
            $findings[] =
                'The monitored improvement benefit is currently below the configured acceptable retention threshold.';
        }

        $findings[] =
            "{$atOrAboveBaselinePercentage}% of observations are currently at or above the verified baseline.";

        if ($belowBaselineCount > 0) {
            $findings[] =
                "{$belowBaselineCount} observation(s) are below baseline; these remain relevant for regression analysis.";
        }

        if ($comparisonConfidence === 'LIMITED') {
            $findings[] =
                'Baseline comparison remains preliminary because fewer than 10 monitoring observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Comparison Payload
        |--------------------------------------------------------------------------
        */

        $comparison = [
            'comparison_version' =>
                '57.5',

            'baseline_score' =>
                $baselineScore,

            'post_improvement_average_score' =>
                $averageScore,

            'post_improvement_latest_score' =>
                $latestScore,

            'post_improvement_minimum_score' =>
                $minimumScore,

            'post_improvement_maximum_score' =>
                $maximumScore,

            'average_difference_from_baseline' =>
                $averageDifference,

            'latest_difference_from_baseline' =>
                $latestDifference,

            'average_percentage_difference' =>
                $averagePercentageDifference,

            'retention_ratio_percentage' =>
                $retentionRatio,

            'configured_regression_tolerance_percentage' =>
                $tolerance,

            'configured_regression_tolerance_points' =>
                $tolerancePoints,

            'benefit_status' =>
                $benefitStatus,

            'observation_distribution' => [
                'total_observations' =>
                    $count,

                'above_baseline' =>
                    $aboveBaselineCount,

                'at_baseline' =>
                    $atBaselineCount,

                'below_baseline' =>
                    $belowBaselineCount,

                'below_baseline_percentage' =>
                    $belowBaselinePercentage,

                'at_or_above_baseline_percentage' =>
                    $atOrAboveBaselinePercentage,
            ],

            'stability_status' =>
                $monitoring->stability_status,

            'comparison_confidence' =>
                $comparisonConfidence,

            'findings' =>
                $findings,

            'automatic_change_authorized' =>
                false,

            'automatic_rollback_authorized' =>
                false,

            'automatic_deployment_authorized' =>
                false,

            'automatic_clinical_action_authorized' =>
                false,

            'compared_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 12. Store Within Longitudinal Analysis
        |--------------------------------------------------------------------------
        */

        $longitudinal[
            'baseline_post_improvement_comparison'
        ] = $comparison;

        return DB::transaction(
            function () use (
                $monitoring,
                $longitudinal,
                $comparison
            ) {
                $monitoring->update([
                    'monitoring_stage' =>
                        'BASELINE_COMPARED',

                    'longitudinal_analysis' =>
                        $longitudinal,

                    'last_analyzed_at' =>
                        now(),

                    'automatic_change_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_clinical_action_allowed' =>
                        false,
                ]);

                $monitoring->refresh();

                return [
                    'comparison_completed' =>
                        true,

                    'status' =>
                        'BASELINE_COMPARISON_COMPLETED',

                    'message' =>
                        'Verified baseline and post-improvement performance comparison completed successfully.',

                    'monitoring' => [
                        'monitoring_id' =>
                            $monitoring->id,

                        'candidate_code' =>
                            $monitoring->candidate_code,

                        'monitoring_status' =>
                            $monitoring->monitoring_status,

                        'monitoring_stage' =>
                            $monitoring->monitoring_stage,

                        'baseline_score' =>
                            $monitoring->baseline_score,

                        'average_observed_score' =>
                            $monitoring->average_observed_score,

                        'performance_change' =>
                            $monitoring->performance_change,

                        'performance_direction' =>
                            $monitoring->performance_direction,

                        'stability_status' =>
                            $monitoring->stability_status,
                    ],

                    'baseline_comparison' =>
                        $comparison,

                    'comparison_guardrails' => [
                        'comparison_is_ai_change' =>
                            false,

                        'comparison_is_rollback_decision' =>
                            false,

                        'comparison_is_deployment_decision' =>
                            false,

                        'automatic_change_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_clinical_action_allowed' =>
                            false,

                        'human_review_required' =>
                            true,

                        'message' =>
                            'Baseline comparison evaluates whether verified improvement benefit is being retained. It does not automatically change, deploy, rollback, or alter clinical AI behavior.',
                    ],
                ];
            }
        );
    }
}