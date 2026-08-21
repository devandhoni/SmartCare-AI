<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use Illuminate\Support\Facades\DB;

class AIImprovementSustainabilityIntelligenceEngine
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
                'message' => 'Sustainability analysis requires an active monitoring record.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $longitudinal = $monitoring->longitudinal_analysis ?? [];
        $regression = $monitoring->regression_analysis ?? [];
        $safety = $monitoring->safety_analysis ?? [];

        if (
            !is_array($longitudinal) ||
            !is_array($regression) ||
            !is_array($safety) ||
            empty($longitudinal) ||
            empty($regression) ||
            empty($safety)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'PRIOR_ANALYSIS_REQUIRED',
                'message' => 'Longitudinal, regression, and safety analyses must be completed before sustainability analysis.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $comparison = $longitudinal['baseline_post_improvement_comparison'] ?? [];

        if (!is_array($comparison) || empty($comparison)) {
            return [
                'analysis_completed' => false,
                'status' => 'BASELINE_COMPARISON_REQUIRED',
                'message' => 'Baseline comparison must be available before sustainability analysis.',
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
                'message' => 'Sustainability analysis is blocked because automatic-change permissions are enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $count = (int) $monitoring->observation_count;

        $benefitStatus = strtoupper(
            (string) ($comparison['benefit_status'] ?? 'UNKNOWN')
        );

        $stabilityStatus = strtoupper(
            (string) ($monitoring->stability_status ?? 'UNKNOWN')
        );

        $regressionStatus = strtoupper(
            (string) ($monitoring->regression_status ?? 'UNKNOWN')
        );

        $driftLevel = strtoupper(
            (string) ($regression['drift_level'] ?? 'UNKNOWN')
        );

        $safetyStatus = strtoupper(
            (string) ($monitoring->safety_monitoring_status ?? 'UNKNOWN')
        );

        $clinicalImpactStatus = strtoupper(
            (string) ($safety['clinical_impact_status'] ?? 'UNKNOWN')
        );

        $safetyPercentage = (float) (
            $safety['safety_percentage'] ?? 0
        );

        $performanceDirection = strtoupper(
            (string) ($monitoring->performance_direction ?? 'UNKNOWN')
        );

        $retentionRatio = isset($comparison['retention_ratio_percentage'])
            ? (float) $comparison['retention_ratio_percentage']
            : null;

        /*
        |--------------------------------------------------------------------------
        | Evidence Maturity
        |--------------------------------------------------------------------------
        */

        if ($count >= 20) {
            $evidenceMaturity = 'MATURE';
            $sustainabilityConfidence = 'HIGH';
        } elseif ($count >= 10) {
            $evidenceMaturity = 'DEVELOPING';
            $sustainabilityConfidence = 'MODERATE';
        } else {
            $evidenceMaturity = 'EARLY';
            $sustainabilityConfidence = 'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Sustainability Signals
        |--------------------------------------------------------------------------
        */

        $signals = [
            'benefit_retained' => in_array(
                $benefitStatus,
                ['BENEFIT_RETAINED', 'BENEFIT_IMPROVED'],
                true
            ),

            'performance_not_deteriorating' =>
                $performanceDirection !== 'DETERIORATED',

            'stable_variability' => in_array(
                $stabilityStatus,
                ['STABLE', 'MODERATELY_VARIABLE'],
                true
            ),

            'no_material_regression' => in_array(
                $regressionStatus,
                ['NO_REGRESSION', 'NO_MATERIAL_REGRESSION'],
                true
            ),

            'low_regression_drift' => in_array(
                $driftLevel,
                ['NONE', 'LOW'],
                true
            ),

            'safety_acceptable' =>
                $safetyStatus === 'SAFE_WITH_MONITORING',

            'clinical_impact_non_negative' => in_array(
                $clinicalImpactStatus,
                [
                    'POSITIVE_MONITORED_SIGNAL',
                    'STABLE_MONITORED_SIGNAL',
                ],
                true
            ),
        ];

        $positiveSignalCount = collect($signals)
            ->filter(fn ($value) => $value === true)
            ->count();

        $totalSignals = count($signals);

        $signalScore = round(
            ($positiveSignalCount / $totalSignals) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Sustainability Classification
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $safetyStatus,
                ['SAFETY_CONCERN', 'BELOW_SAFETY_THRESHOLD'],
                true
            )
            ||
            $regressionStatus === 'REGRESSION_DETECTED'
            ||
            $benefitStatus === 'BENEFIT_LOST'
        ) {
            $sustainabilityStatus = 'NOT_SUSTAINED';
        } elseif (
            $signalScore >= 85
            &&
            $count >= 20
        ) {
            $sustainabilityStatus = 'SUSTAINED';
        } elseif (
            $signalScore >= 70
        ) {
            $sustainabilityStatus = 'PROVISIONALLY_SUSTAINED';
        } else {
            $sustainabilityStatus = 'INCONCLUSIVE';
        }

        /*
        |--------------------------------------------------------------------------
        | Readiness Interpretation
        |--------------------------------------------------------------------------
        */

        $longTermConfidenceReady =
            $count >= 20
            &&
            $sustainabilityStatus === 'SUSTAINED';

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Sustainability analysis currently uses {$count} post-improvement observation(s).";

        $findings[] =
            "Current retained-benefit classification is {$benefitStatus}.";

        $findings[] =
            "Performance stability is {$stabilityStatus}, while regression status is {$regressionStatus} with {$driftLevel} drift.";

        $findings[] =
            "Recorded safety pass rate is {$safetyPercentage}% and safety monitoring status is {$safetyStatus}.";

        if ($retentionRatio !== null) {
            $findings[] =
                "Post-improvement monitoring currently retains {$retentionRatio}% of the verified controlled-execution baseline.";
        }

        $findings[] =
            "{$positiveSignalCount} of {$totalSignals} sustainability indicators currently support continued benefit.";

        if ($sustainabilityStatus === 'SUSTAINED') {
            $findings[] =
                'Current evidence supports a sustained improvement signal.';
        } elseif ($sustainabilityStatus === 'PROVISIONALLY_SUSTAINED') {
            $findings[] =
                'Current evidence supports a provisional sustained-improvement signal, but further longitudinal observations are required.';
        } elseif ($sustainabilityStatus === 'NOT_SUSTAINED') {
            $findings[] =
                'Current evidence does not support continued improvement sustainability and requires human governance review.';
        } else {
            $findings[] =
                'Current evidence is insufficient for a clear sustainability conclusion.';
        }

        if ($sustainabilityConfidence === 'LIMITED') {
            $findings[] =
                'Sustainability confidence remains limited because fewer than 10 observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis Payload
        |--------------------------------------------------------------------------
        */

        $analysis = [
            'analysis_version' => '57.8',

            'observation_count' => $count,

            'evidence_maturity' => $evidenceMaturity,

            'sustainability_confidence' =>
                $sustainabilityConfidence,

            'performance_context' => [
                'performance_direction' =>
                    $performanceDirection,

                'stability_status' =>
                    $stabilityStatus,

                'benefit_status' =>
                    $benefitStatus,

                'retention_ratio_percentage' =>
                    $retentionRatio,
            ],

            'regression_context' => [
                'regression_status' =>
                    $regressionStatus,

                'drift_level' =>
                    $driftLevel,
            ],

            'safety_context' => [
                'safety_status' =>
                    $safetyStatus,

                'safety_percentage' =>
                    $safetyPercentage,

                'clinical_impact_status' =>
                    $clinicalImpactStatus,
            ],

            'sustainability_signals' =>
                $signals,

            'positive_signal_count' =>
                $positiveSignalCount,

            'total_signal_count' =>
                $totalSignals,

            'sustainability_signal_score' =>
                $signalScore,

            'sustainability_status' =>
                $sustainabilityStatus,

            'long_term_confidence_ready' =>
                $longTermConfidenceReady,

            'human_review_recommended' =>
                in_array(
                    $sustainabilityStatus,
                    ['NOT_SUSTAINED', 'INCONCLUSIVE'],
                    true
                ),

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

            'analyzed_at' =>
                now()->toIso8601String(),
        ];

        return DB::transaction(function () use (
            $monitoring,
            $analysis,
            $sustainabilityStatus
        ) {
            $monitoring->update([
                'monitoring_stage' =>
                    'SUSTAINABILITY_ANALYZED',

                'sustainability_status' =>
                    $sustainabilityStatus,

                'sustainability_analysis' =>
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
                    'SUSTAINABILITY_ANALYSIS_COMPLETED',

                'message' =>
                    'AI improvement sustainability intelligence analysis completed successfully.',

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

                    'safety_monitoring_status' =>
                        $monitoring->safety_monitoring_status,

                    'sustainability_status' =>
                        $monitoring->sustainability_status,
                ],

                'sustainability_analysis' =>
                    $analysis,

                'sustainability_guardrails' => [
                    'sustainability_analysis_is_ai_change' =>
                        false,

                    'sustainability_analysis_is_deployment_authorization' =>
                        false,

                    'sustainability_analysis_is_rollback_authorization' =>
                        false,

                    'automatic_change_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_clinical_action_allowed' =>
                        false,

                    'human_governance_required' =>
                        true,

                    'message' =>
                        'Sustainability intelligence evaluates whether improvement benefits persist over time. It does not automatically modify, deploy, rollback, or alter clinical AI behavior.',
                ],
            ];
        });
    }
}