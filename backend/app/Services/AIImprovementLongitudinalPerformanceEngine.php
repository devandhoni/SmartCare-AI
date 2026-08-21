<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;
use Illuminate\Support\Facades\DB;

class AIImprovementLongitudinalPerformanceEngine
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
                'analysis_completed' => false,
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
                'analysis_completed' => false,
                'status' => 'MONITORING_NOT_ACTIVE',
                'message' => 'Longitudinal analysis requires an active monitoring record.',
                'monitoring_id' => $monitoring->id,
                'monitoring_status' => $monitoring->monitoring_status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Observations
        |--------------------------------------------------------------------------
        */

        $observations = AIImprovementMonitoringObservation::where(
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
                'analysis_completed' => false,
                'status' => 'INSUFFICIENT_OBSERVATIONS',
                'message' => 'Longitudinal performance analysis requires additional monitoring observations.',
                'monitoring_id' => $monitoring->id,
                'observation_count' => $count,
                'minimum_observations_required' => $minimumRequired,
                'observations_remaining' => max(
                    0,
                    $minimumRequired - $count
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Governance Isolation
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
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Longitudinal analysis is blocked because an automatic-change permission is enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Core Statistics
        |--------------------------------------------------------------------------
        */

        $baselineScore =
            (float) $monitoring->baseline_score;

        $scores = $observations
            ->pluck('observed_score')
            ->map(fn ($value) => (float) $value)
            ->values();

        $averageScore = round(
            (float) $scores->avg(),
            2
        );

        $minimumScore = round(
            (float) $scores->min(),
            2
        );

        $maximumScore = round(
            (float) $scores->max(),
            2
        );

        $latestScore = round(
            (float) $scores->last(),
            2
        );

        $averageChange = round(
            $averageScore - $baselineScore,
            2
        );

        $averagePercentageChange =
            $baselineScore != 0
                ? round(
                    ($averageChange / $baselineScore) * 100,
                    2
                )
                : null;

        /*
        |--------------------------------------------------------------------------
        | 5. Observation Distribution
        |--------------------------------------------------------------------------
        */

        $improvedCount = $observations
            ->where('performance_direction', 'IMPROVED')
            ->count();

        $stableCount = $observations
            ->where('performance_direction', 'STABLE')
            ->count();

        $deterioratedCount = $observations
            ->where('performance_direction', 'DETERIORATED')
            ->count();

        $safeCount = $observations
            ->where('safety_passed', true)
            ->count();

        $unsafeCount = $observations
            ->where('safety_passed', false)
            ->count();

        $safePercentage = round(
            ($safeCount / $count) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Variability / Stability
        |--------------------------------------------------------------------------
        */

        $variance = 0.0;

        foreach ($scores as $score) {
            $variance += pow(
                $score - $averageScore,
                2
            );
        }

        $variance = $count > 0
            ? $variance / $count
            : 0;

        $standardDeviation = round(
            sqrt($variance),
            2
        );

        $range = round(
            $maximumScore - $minimumScore,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Stability Classification
        |--------------------------------------------------------------------------
        */

        if ($standardDeviation <= 2) {
            $stabilityStatus = 'STABLE';
        } elseif ($standardDeviation <= 5) {
            $stabilityStatus = 'MODERATELY_VARIABLE';
        } else {
            $stabilityStatus = 'HIGH_VARIABILITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Overall Performance Direction
        |--------------------------------------------------------------------------
        */

        if ($averageChange > 0) {
            $performanceDirection = 'IMPROVED';
        } elseif ($averageChange < 0) {
            $performanceDirection = 'DETERIORATED';
        } else {
            $performanceDirection = 'STABLE';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Early-vs-Late Trend
        |--------------------------------------------------------------------------
        */

        $splitPoint = (int) ceil($count / 2);

        $earlyScores = $scores->slice(
            0,
            $splitPoint
        );

        $lateScores = $scores->slice(
            $splitPoint
        );

        $earlyAverage = round(
            (float) $earlyScores->avg(),
            2
        );

        $lateAverage = $lateScores->count() > 0
            ? round(
                (float) $lateScores->avg(),
                2
            )
            : $earlyAverage;

        $trendChange = round(
            $lateAverage - $earlyAverage,
            2
        );

        if ($trendChange > 1) {
            $trendDirection = 'IMPROVING';
        } elseif ($trendChange < -1) {
            $trendDirection = 'WORSENING';
        } else {
            $trendDirection = 'STABLE';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Evidence Maturity
        |--------------------------------------------------------------------------
        */

        if ($count >= 20) {
            $evidenceMaturity = 'MATURE';
            $analysisConfidence = 'HIGH';
        } elseif ($count >= 10) {
            $evidenceMaturity = 'DEVELOPING';
            $analysisConfidence = 'MODERATE';
        } else {
            $evidenceMaturity = 'EARLY';
            $analysisConfidence = 'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Longitudinal monitoring currently includes {$count} observation(s).";

        $findings[] =
            "Verified execution baseline is {$baselineScore}, while the current observed average is {$averageScore}.";

        if ($performanceDirection === 'IMPROVED') {
            $findings[] =
                "Average monitored performance remains {$averageChange} point(s) above the verified execution baseline.";
        } elseif ($performanceDirection === 'DETERIORATED') {
            $findings[] =
                "Average monitored performance is {$averageChange} point(s) below the verified execution baseline.";
        } else {
            $findings[] =
                'Average monitored performance currently matches the verified execution baseline.';
        }

        $findings[] =
            "Observed longitudinal variability is {$stabilityStatus} with a standard deviation of {$standardDeviation}.";

        $findings[] =
            "{$safePercentage}% of recorded monitoring observations currently pass the recorded safety condition.";

        if ($deterioratedCount > 0) {
            $findings[] =
                "{$deterioratedCount} individual observation(s) were below the verified baseline and should remain visible in subsequent regression analysis.";
        }

        if ($analysisConfidence === 'LIMITED') {
            $findings[] =
                'Longitudinal conclusions remain preliminary because fewer than 10 observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Analysis Payload
        |--------------------------------------------------------------------------
        */

        $analysis = [
            'analysis_version' => '57.4',

            'baseline_score' =>
                $baselineScore,

            'observation_count' =>
                $count,

            'minimum_observations_required' =>
                $minimumRequired,

            'average_observed_score' =>
                $averageScore,

            'latest_observed_score' =>
                $latestScore,

            'minimum_observed_score' =>
                $minimumScore,

            'maximum_observed_score' =>
                $maximumScore,

            'average_change_from_baseline' =>
                $averageChange,

            'average_percentage_change' =>
                $averagePercentageChange,

            'overall_performance_direction' =>
                $performanceDirection,

            'distribution' => [
                'improved_observations' =>
                    $improvedCount,

                'stable_observations' =>
                    $stableCount,

                'deteriorated_observations' =>
                    $deterioratedCount,

                'safe_observations' =>
                    $safeCount,

                'unsafe_observations' =>
                    $unsafeCount,

                'safety_percentage' =>
                    $safePercentage,
            ],

            'variability' => [
                'minimum_score' =>
                    $minimumScore,

                'maximum_score' =>
                    $maximumScore,

                'range' =>
                    $range,

                'standard_deviation' =>
                    $standardDeviation,

                'stability_status' =>
                    $stabilityStatus,
            ],

            'trend_analysis' => [
                'early_period_average' =>
                    $earlyAverage,

                'late_period_average' =>
                    $lateAverage,

                'trend_change' =>
                    $trendChange,

                'trend_direction' =>
                    $trendDirection,
            ],

            'evidence_maturity' =>
                $evidenceMaturity,

            'analysis_confidence' =>
                $analysisConfidence,

            'findings' =>
                $findings,

            /*
            |--------------------------------------------------------------------------
            | No action authority
            |--------------------------------------------------------------------------
            */

            'automatic_change_authorized' =>
                false,

            'automatic_rollback_authorized' =>
                false,

            'automatic_deployment_authorized' =>
                false,

            'automatic_clinical_action_authorized' =>
                false,

            'analyzed_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 12. Persist Analysis
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $monitoring,
                $analysis,
                $averageScore,
                $latestScore,
                $averageChange,
                $performanceDirection,
                $stabilityStatus
            ) {
                $monitoring->update([
                    'monitoring_stage' =>
                        'LONGITUDINAL_ANALYZED',

                    'latest_observed_score' =>
                        $latestScore,

                    'average_observed_score' =>
                        $averageScore,

                    'performance_change' =>
                        $averageChange,

                    'performance_direction' =>
                        $performanceDirection,

                    'stability_status' =>
                        $stabilityStatus,

                    'longitudinal_analysis' =>
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
                        'LONGITUDINAL_ANALYSIS_COMPLETED',

                    'message' =>
                        'AI improvement longitudinal performance analysis completed successfully.',

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

                        'observation_count' =>
                            $monitoring->observation_count,

                        'average_observed_score' =>
                            $monitoring->average_observed_score,

                        'performance_change' =>
                            $monitoring->performance_change,

                        'performance_direction' =>
                            $monitoring->performance_direction,

                        'stability_status' =>
                            $monitoring->stability_status,

                        'last_analyzed_at' =>
                            $monitoring->last_analyzed_at,
                    ],

                    'longitudinal_analysis' =>
                        $analysis,

                    'analysis_guardrails' => [
                        'analysis_is_ai_change' =>
                            false,

                        'analysis_triggers_automatic_rollback' =>
                            false,

                        'analysis_triggers_automatic_deployment' =>
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
                            'Longitudinal analysis evaluates post-improvement performance only. It does not automatically change AI configuration, deploy updates, trigger rollback, or alter clinical behavior.',
                    ],
                ];
            }
        );
    }
}