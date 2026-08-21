<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;
use Illuminate\Support\Facades\DB;

class AIImprovementSafetyClinicalImpactMonitoringEngine
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
                'message' => 'Safety monitoring requires an active monitoring record.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        if (
            strtoupper((string) $monitoring->regression_status) === 'NOT_EVALUATED'
            ||
            empty($monitoring->regression_analysis)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'REGRESSION_ANALYSIS_REQUIRED',
                'message' => 'Step 57.6 regression analysis must be completed before safety and clinical impact monitoring.',
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
                'message' => 'Safety monitoring is blocked because automatic-change permissions are enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $observations = AIImprovementMonitoringObservation::where('monitoring_id', $monitoring->id)
            ->orderBy('observed_at')
            ->orderBy('id')
            ->get();

        $count = $observations->count();

        if ($count === 0) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_OBSERVATIONS',
                'message' => 'Safety monitoring requires post-improvement observations.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $safeCount = $observations->where('safety_passed', true)->count();
        $unsafeCount = $observations->where('safety_passed', false)->count();

        $safetyPercentage = round(
            ($safeCount / $count) * 100,
            2
        );

        $safetyThreshold = (float) $monitoring->safety_threshold_percentage;

        $safetyConcernCount = $observations
            ->filter(fn ($observation) =>
                strtoupper((string) $observation->outcome_status) === 'SAFETY_CONCERN'
            )
            ->count();

        $negativeSignalCount = $observations
            ->filter(fn ($observation) =>
                strtoupper((string) $observation->outcome_status) === 'NEGATIVE_SIGNAL'
            )
            ->count();

        $positiveSignalCount = $observations
            ->filter(fn ($observation) =>
                strtoupper((string) $observation->outcome_status) === 'POSITIVE_SIGNAL'
            )
            ->count();

        $stableSignalCount = $observations
            ->filter(fn ($observation) =>
                strtoupper((string) $observation->outcome_status) === 'STABLE_SIGNAL'
            )
            ->count();

        $regressionStatus = strtoupper(
            (string) $monitoring->regression_status
        );

        $regressionAnalysis = $monitoring->regression_analysis ?? [];

        if (!is_array($regressionAnalysis)) {
            $regressionAnalysis = [];
        }

        $driftLevel = strtoupper(
            (string) ($regressionAnalysis['drift_level'] ?? 'UNKNOWN')
        );

        /*
        |--------------------------------------------------------------------------
        | Clinical Impact Context
        |--------------------------------------------------------------------------
        */

        $baselineScore = (float) $monitoring->baseline_score;
        $averageScore = (float) $monitoring->average_observed_score;
        $performanceDirection = strtoupper(
            (string) $monitoring->performance_direction
        );

        $performanceChange = round(
            $averageScore - $baselineScore,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Safety Classification
        |--------------------------------------------------------------------------
        */

        if ($unsafeCount > 0 || $safetyConcernCount > 0) {
            $safetyStatus = 'SAFETY_CONCERN';
        } elseif ($safetyPercentage < $safetyThreshold) {
            $safetyStatus = 'BELOW_SAFETY_THRESHOLD';
        } elseif (
            $regressionStatus === 'REGRESSION_DETECTED'
            ||
            $driftLevel === 'HIGH'
        ) {
            $safetyStatus = 'REQUIRES_HUMAN_REVIEW';
        } else {
            $safetyStatus = 'SAFE_WITH_MONITORING';
        }

        /*
        |--------------------------------------------------------------------------
        | Clinical Impact Classification
        |--------------------------------------------------------------------------
        */

        if (
            $performanceDirection === 'IMPROVED'
            &&
            $safetyStatus === 'SAFE_WITH_MONITORING'
        ) {
            $clinicalImpactStatus = 'POSITIVE_MONITORED_SIGNAL';
        } elseif (
            $performanceDirection === 'STABLE'
            &&
            $safetyStatus === 'SAFE_WITH_MONITORING'
        ) {
            $clinicalImpactStatus = 'STABLE_MONITORED_SIGNAL';
        } elseif (
            $performanceDirection === 'DETERIORATED'
            ||
            in_array(
                $safetyStatus,
                ['SAFETY_CONCERN', 'BELOW_SAFETY_THRESHOLD'],
                true
            )
        ) {
            $clinicalImpactStatus = 'CAUTION_SIGNAL';
        } else {
            $clinicalImpactStatus = 'REVIEW_REQUIRED';
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Confidence
        |--------------------------------------------------------------------------
        */

        if ($count >= 20) {
            $monitoringConfidence = 'HIGH';
        } elseif ($count >= 10) {
            $monitoringConfidence = 'MODERATE';
        } else {
            $monitoringConfidence = 'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Human Review Recommendation
        |--------------------------------------------------------------------------
        */

        $humanReviewRecommended = in_array(
            $safetyStatus,
            [
                'SAFETY_CONCERN',
                'BELOW_SAFETY_THRESHOLD',
                'REQUIRES_HUMAN_REVIEW',
            ],
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Safety monitoring includes {$count} post-improvement observation(s).";

        $findings[] =
            "{$safeCount} observation(s) passed recorded safety checks and {$unsafeCount} observation(s) did not.";

        $findings[] =
            "Current safety pass rate is {$safetyPercentage}% against a configured threshold of {$safetyThreshold}%.";

        $findings[] =
            "Average monitored performance differs from baseline by {$performanceChange} point(s).";

        if ($safetyConcernCount > 0) {
            $findings[] =
                "{$safetyConcernCount} explicit safety concern observation(s) are present.";
        }

        if ($negativeSignalCount > 0) {
            $findings[] =
                "{$negativeSignalCount} negative performance signal(s) remain visible for human interpretation.";
        }

        if ($safetyStatus === 'SAFE_WITH_MONITORING') {
            $findings[] =
                'Current longitudinal evidence does not indicate a material monitored safety concern.';
        }

        if ($monitoringConfidence === 'LIMITED') {
            $findings[] =
                'Safety and clinical-impact conclusions remain preliminary because fewer than 10 observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis Payload
        |--------------------------------------------------------------------------
        */

        $analysis = [
            'analysis_version' => '57.7',

            'observation_count' => $count,

            'safe_observations' => $safeCount,
            'unsafe_observations' => $unsafeCount,

            'safety_percentage' => $safetyPercentage,
            'configured_safety_threshold_percentage' => $safetyThreshold,

            'signal_distribution' => [
                'positive_signals' => $positiveSignalCount,
                'stable_signals' => $stableSignalCount,
                'negative_signals' => $negativeSignalCount,
                'safety_concerns' => $safetyConcernCount,
            ],

            'performance_context' => [
                'baseline_score' => $baselineScore,
                'average_observed_score' => $averageScore,
                'performance_change' => $performanceChange,
                'performance_direction' => $performanceDirection,
            ],

            'regression_context' => [
                'regression_status' => $regressionStatus,
                'drift_level' => $driftLevel,
            ],

            'safety_status' => $safetyStatus,

            'clinical_impact_status' => $clinicalImpactStatus,

            'monitoring_confidence' => $monitoringConfidence,

            'human_review_recommended' => $humanReviewRecommended,

            'findings' => $findings,

            'automatic_change_authorized' => false,
            'automatic_rollback_authorized' => false,
            'automatic_deployment_authorized' => false,
            'automatic_clinical_action_authorized' => false,

            'analyzed_at' => now()->toIso8601String(),
        ];

        return DB::transaction(function () use (
            $monitoring,
            $analysis,
            $safetyStatus
        ) {
            $monitoring->update([
                'monitoring_stage' =>
                    'SAFETY_IMPACT_ANALYZED',

                'safety_monitoring_status' =>
                    $safetyStatus,

                'safety_analysis' =>
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
                    'SAFETY_CLINICAL_IMPACT_ANALYSIS_COMPLETED',

                'message' =>
                    'AI improvement safety and clinical impact monitoring completed successfully.',

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

                    'regression_status' =>
                        $monitoring->regression_status,

                    'safety_monitoring_status' =>
                        $monitoring->safety_monitoring_status,
                ],

                'safety_analysis' =>
                    $analysis,

                'safety_guardrails' => [
                    'safety_monitoring_is_clinical_action' =>
                        false,

                    'safety_monitoring_is_rollback_authorization' =>
                        false,

                    'automatic_change_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_clinical_action_allowed' =>
                        false,

                    'human_review_required_for_material_safety_concern' =>
                        true,

                    'message' =>
                        'Safety and clinical-impact monitoring evaluates longitudinal evidence only. It does not automatically alter AI behavior, execute rollback, deploy changes, or initiate clinical action.',
                ],
            ];
        });
    }
}