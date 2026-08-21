<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;
use Illuminate\Support\Facades\DB;

class AIImprovementRegressionDriftDetectionEngine
{
    public function analyze(int $monitoringId): array
    {
        $monitoring = AIImprovementMonitoring::find($monitoringId);

        if (!$monitoring) {
            return [
                'analysis_completed' => false,
                'status' => 'MONITORING_NOT_FOUND',
                'message' => 'AI improvement monitoring record was not found.',
                'monitoring_id' => $monitoringId,
            ];
        }

        if (strtoupper((string) $monitoring->monitoring_status) !== 'ACTIVE') {
            return [
                'analysis_completed' => false,
                'status' => 'MONITORING_NOT_ACTIVE',
                'message' => 'Regression and drift detection requires an active monitoring record.',
                'monitoring_id' => $monitoring->id,
                'monitoring_status' => $monitoring->monitoring_status,
            ];
        }

        $longitudinal = $monitoring->longitudinal_analysis ?? [];

        if (!is_array($longitudinal) || empty($longitudinal)) {
            return [
                'analysis_completed' => false,
                'status' => 'LONGITUDINAL_ANALYSIS_REQUIRED',
                'message' => 'Longitudinal performance analysis must be completed first.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $comparison = $longitudinal['baseline_post_improvement_comparison'] ?? [];

        if (!is_array($comparison) || empty($comparison)) {
            return [
                'analysis_completed' => false,
                'status' => 'BASELINE_COMPARISON_REQUIRED',
                'message' => 'Baseline comparison must be completed before regression and drift detection.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        if (
            (bool) $monitoring->automatic_change_allowed ||
            (bool) $monitoring->automatic_rollback_allowed ||
            (bool) $monitoring->automatic_deployment_allowed ||
            (bool) $monitoring->automatic_clinical_action_allowed
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Regression analysis is blocked because an automatic-change permission is enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $observations = AIImprovementMonitoringObservation::where('monitoring_id', $monitoring->id)
            ->orderBy('observed_at')
            ->orderBy('id')
            ->get();

        $count = $observations->count();

        $minimumRequired = (int) $monitoring->minimum_observations_required;

        if ($count < $minimumRequired) {
            return [
                'analysis_completed' => false,
                'status' => 'INSUFFICIENT_OBSERVATIONS',
                'message' => 'Regression and drift detection requires the minimum monitoring observation threshold.',
                'observation_count' => $count,
                'minimum_observations_required' => $minimumRequired,
            ];
        }

        $baselineScore = (float) $monitoring->baseline_score;
        $averageScore = (float) $monitoring->average_observed_score;
        $latestScore = (float) $monitoring->latest_observed_score;

        $tolerancePercentage = (float) $monitoring->regression_tolerance_percentage;

        $tolerancePoints = round(
            $baselineScore * ($tolerancePercentage / 100),
            2
        );

        $averageDeclinePoints = round(
            max(0, $baselineScore - $averageScore),
            2
        );

        $latestDeclinePoints = round(
            max(0, $baselineScore - $latestScore),
            2
        );

        $belowBaseline = $observations->filter(
            fn ($observation) =>
                (float) $observation->observed_score < $baselineScore
        );

        $belowBaselineCount = $belowBaseline->count();

        $belowBaselinePercentage = round(
            ($belowBaselineCount / $count) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Consecutive Recent Deterioration
        |--------------------------------------------------------------------------
        */

        $consecutiveBelowBaseline = 0;

        foreach ($observations->reverse() as $observation) {
            if ((float) $observation->observed_score < $baselineScore) {
                $consecutiveBelowBaseline++;
            } else {
                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Recent Window
        |--------------------------------------------------------------------------
        */

        $recentWindowSize = min(3, $count);

        $recentObservations = $observations
            ->take(-$recentWindowSize);

        $recentAverage = round(
            (float) $recentObservations->avg('observed_score'),
            2
        );

        $recentDifference = round(
            $recentAverage - $baselineScore,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Longitudinal Trend
        |--------------------------------------------------------------------------
        */

        $trendDirection = strtoupper(
            (string) (
                $longitudinal['trend_analysis']['trend_direction']
                ?? 'UNKNOWN'
            )
        );

        $stabilityStatus = strtoupper(
            (string) (
                $monitoring->stability_status
                ?? 'UNKNOWN'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Detection Signals
        |--------------------------------------------------------------------------
        */

        $signals = [];

        $signals['average_outside_tolerance'] = [
            'detected' =>
                $averageDeclinePoints > $tolerancePoints,

            'value' =>
                $averageDeclinePoints,

            'threshold' =>
                $tolerancePoints,
        ];

        $signals['latest_outside_tolerance'] = [
            'detected' =>
                $latestDeclinePoints > $tolerancePoints,

            'value' =>
                $latestDeclinePoints,

            'threshold' =>
                $tolerancePoints,
        ];

        $signals['high_below_baseline_frequency'] = [
            'detected' =>
                $belowBaselinePercentage >= 50,

            'value' =>
                $belowBaselinePercentage,

            'threshold' =>
                50,
        ];

        $signals['consecutive_deterioration'] = [
            'detected' =>
                $consecutiveBelowBaseline >= 3,

            'value' =>
                $consecutiveBelowBaseline,

            'threshold' =>
                3,
        ];

        $signals['recent_average_below_baseline'] = [
            'detected' =>
                $recentAverage < $baselineScore,

            'value' =>
                $recentAverage,

            'threshold' =>
                $baselineScore,
        ];

        $signals['worsening_longitudinal_trend'] = [
            'detected' =>
                $trendDirection === 'WORSENING',

            'value' =>
                $trendDirection,
        ];

        $signals['high_variability'] = [
            'detected' =>
                $stabilityStatus === 'HIGH_VARIABILITY',

            'value' =>
                $stabilityStatus,
        ];

        $detectedSignals = collect($signals)
            ->filter(
                fn ($signal) =>
                    (bool) ($signal['detected'] ?? false)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Regression Classification
        |--------------------------------------------------------------------------
        */

        if (
            $signals['average_outside_tolerance']['detected'] ||
            $signals['consecutive_deterioration']['detected']
        ) {
            $regressionStatus = 'REGRESSION_DETECTED';
            $driftLevel = 'HIGH';
        } elseif (
            $detectedSignals >= 2
        ) {
            $regressionStatus = 'POTENTIAL_REGRESSION';
            $driftLevel = 'MODERATE';
        } elseif (
            $belowBaselineCount > 0
        ) {
            $regressionStatus = 'NO_MATERIAL_REGRESSION';
            $driftLevel = 'LOW';
        } else {
            $regressionStatus = 'NO_REGRESSION';
            $driftLevel = 'NONE';
        }

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        if ($count >= 20) {
            $detectionConfidence = 'HIGH';
        } elseif ($count >= 10) {
            $detectionConfidence = 'MODERATE';
        } else {
            $detectionConfidence = 'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Regression analysis evaluated {$count} longitudinal observation(s).";

        $findings[] =
            "Configured regression tolerance is {$tolerancePercentage}% ({$tolerancePoints} baseline point(s)).";

        $findings[] =
            "{$belowBaselineCount} observation(s), representing {$belowBaselinePercentage}% of the monitoring sample, are below the verified baseline.";

        $findings[] =
            "Recent {$recentWindowSize}-observation average is {$recentAverage} compared with baseline {$baselineScore}.";

        if ($regressionStatus === 'NO_REGRESSION') {
            $findings[] =
                'No monitored regression signal is currently detected.';
        } elseif ($regressionStatus === 'NO_MATERIAL_REGRESSION') {
            $findings[] =
                'Below-baseline variation exists, but current evidence does not indicate material regression.';
        } elseif ($regressionStatus === 'POTENTIAL_REGRESSION') {
            $findings[] =
                'Multiple deterioration signals are present and should receive human monitoring review.';
        } else {
            $findings[] =
                'A material regression signal has been detected and requires human governance review.';
        }

        if ($detectionConfidence === 'LIMITED') {
            $findings[] =
                'Regression interpretation remains preliminary because fewer than 10 observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis Payload
        |--------------------------------------------------------------------------
        */

        $analysis = [
            'analysis_version' =>
                '57.6',

            'baseline_score' =>
                $baselineScore,

            'observation_count' =>
                $count,

            'average_observed_score' =>
                $averageScore,

            'latest_observed_score' =>
                $latestScore,

            'regression_tolerance_percentage' =>
                $tolerancePercentage,

            'regression_tolerance_points' =>
                $tolerancePoints,

            'average_decline_points' =>
                $averageDeclinePoints,

            'latest_decline_points' =>
                $latestDeclinePoints,

            'below_baseline_count' =>
                $belowBaselineCount,

            'below_baseline_percentage' =>
                $belowBaselinePercentage,

            'consecutive_below_baseline' =>
                $consecutiveBelowBaseline,

            'recent_window_size' =>
                $recentWindowSize,

            'recent_average_score' =>
                $recentAverage,

            'recent_difference_from_baseline' =>
                $recentDifference,

            'longitudinal_trend_direction' =>
                $trendDirection,

            'stability_status' =>
                $stabilityStatus,

            'signals' =>
                $signals,

            'detected_signal_count' =>
                $detectedSignals,

            'regression_status' =>
                $regressionStatus,

            'drift_level' =>
                $driftLevel,

            'detection_confidence' =>
                $detectionConfidence,

            'human_review_recommended' =>
                in_array(
                    $regressionStatus,
                    [
                        'POTENTIAL_REGRESSION',
                        'REGRESSION_DETECTED',
                    ],
                    true
                ),

            'automatic_rollback_authorized' =>
                false,

            'automatic_change_authorized' =>
                false,

            'automatic_deployment_authorized' =>
                false,

            'automatic_clinical_action_authorized' =>
                false,

            'findings' =>
                $findings,

            'analyzed_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Persist
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $monitoring,
                $analysis,
                $regressionStatus
            ) {
                $monitoring->update([
                    'monitoring_stage' =>
                        'REGRESSION_ANALYZED',

                    'regression_status' =>
                        $regressionStatus,

                    'regression_analysis' =>
                        $analysis,

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
                    'analysis_completed' =>
                        true,

                    'status' =>
                        'REGRESSION_DRIFT_ANALYSIS_COMPLETED',

                    'message' =>
                        'AI improvement regression and drift detection completed successfully.',

                    'monitoring' => [
                        'monitoring_id' =>
                            $monitoring->id,

                        'monitoring_status' =>
                            $monitoring->monitoring_status,

                        'monitoring_stage' =>
                            $monitoring->monitoring_stage,

                        'baseline_score' =>
                            $monitoring->baseline_score,

                        'average_observed_score' =>
                            $monitoring->average_observed_score,

                        'performance_direction' =>
                            $monitoring->performance_direction,

                        'stability_status' =>
                            $monitoring->stability_status,

                        'regression_status' =>
                            $monitoring->regression_status,
                    ],

                    'regression_analysis' =>
                        $analysis,

                    'regression_guardrails' => [
                        'regression_detection_is_rollback_authorization' =>
                            false,

                        'regression_detection_is_ai_change' =>
                            false,

                        'automatic_change_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_clinical_action_allowed' =>
                            false,

                        'human_review_required_for_material_regression' =>
                            true,

                        'message' =>
                            'Regression detection identifies possible performance deterioration only. It does not automatically rollback, deploy, modify AI configuration, or alter clinical behavior.',
                    ],
                ];
            }
        );
    }
}