<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;
use Illuminate\Support\Facades\DB;

class AIImprovementMonitoringObservationCaptureEngine
{
    public function capture(
        int $monitoringId,
        float $observedScore,
        bool $safetyPassed = true,
        ?string $outcomeStatus = null,
        array $context = [],
        ?string $observationType = 'PERFORMANCE',
        ?string $evidenceQuality = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Monitoring Record
        |--------------------------------------------------------------------------
        */

        $monitoring = AIImprovementMonitoring::find($monitoringId);

        if (!$monitoring) {
            return [
                'captured' => false,
                'status' => 'MONITORING_NOT_FOUND',
                'message' => 'AI improvement monitoring record was not found.',
                'monitoring_id' => $monitoringId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Monitoring Must Be Active
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper((string) $monitoring->monitoring_status)
            !== 'ACTIVE'
        ) {
            return [
                'captured' => false,
                'status' => 'MONITORING_NOT_ACTIVE',
                'message' => 'Observations may only be captured for an active monitoring record.',
                'monitoring_id' => $monitoring->id,
                'monitoring_status' => $monitoring->monitoring_status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Governance Guardrails
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
                'captured' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Observation capture is blocked because an automatic-change permission is enabled.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Validate Score
        |--------------------------------------------------------------------------
        */

        $observedScore = max(
            0,
            min(100, $observedScore)
        );

        $baselineScore = $monitoring->baseline_score;

        if ($baselineScore === null) {
            return [
                'captured' => false,
                'status' => 'BASELINE_UNAVAILABLE',
                'message' => 'Monitoring baseline is required before observations may be captured.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $baselineScore = (float) $baselineScore;

        /*
        |--------------------------------------------------------------------------
        | 5. Calculate Performance Change
        |--------------------------------------------------------------------------
        */

        $absoluteChange = round(
            $observedScore - $baselineScore,
            2
        );

        if ($baselineScore != 0) {
            $percentageChange = round(
                ($absoluteChange / $baselineScore) * 100,
                2
            );
        } else {
            $percentageChange = null;
        }

        if ($absoluteChange > 0) {
            $direction = 'IMPROVED';
        } elseif ($absoluteChange < 0) {
            $direction = 'DETERIORATED';
        } else {
            $direction = 'STABLE';
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Outcome
        |--------------------------------------------------------------------------
        */

        if ($outcomeStatus === null) {
            if (!$safetyPassed) {
                $outcomeStatus = 'SAFETY_CONCERN';
            } elseif ($direction === 'IMPROVED') {
                $outcomeStatus = 'POSITIVE_SIGNAL';
            } elseif ($direction === 'DETERIORATED') {
                $outcomeStatus = 'NEGATIVE_SIGNAL';
            } else {
                $outcomeStatus = 'STABLE_SIGNAL';
            }
        }

        $outcomeStatus = strtoupper(
            trim($outcomeStatus)
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Evidence Quality
        |--------------------------------------------------------------------------
        */

        if ($evidenceQuality === null) {
            $futureCount =
                (int) $monitoring->observation_count + 1;

            if ($futureCount >= 20) {
                $evidenceQuality = 'STRONG';
            } elseif ($futureCount >= 5) {
                $evidenceQuality = 'MODERATE';
            } else {
                $evidenceQuality = 'LIMITED';
            }
        }

        $evidenceQuality = strtoupper(
            trim($evidenceQuality)
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Observation Context
        |--------------------------------------------------------------------------
        */

        $context['capture_metadata'] = [
            'capture_engine' =>
                'AIImprovementMonitoringObservationCaptureEngine',

            'capture_version' =>
                '57.3',

            'monitoring_baseline_score' =>
                $baselineScore,

            'production_change_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'automatic_rollback_allowed' =>
                false,

            'automatic_clinical_action_allowed' =>
                false,

            'captured_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 9. Persist Observation
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $monitoring,
                $observedScore,
                $baselineScore,
                $absoluteChange,
                $percentageChange,
                $direction,
                $safetyPassed,
                $outcomeStatus,
                $context,
                $observationType,
                $evidenceQuality
            ) {
                $observation =
                    AIImprovementMonitoringObservation::create([
                        'monitoring_id' =>
                            $monitoring->id,

                        'improvement_execution_id' =>
                            $monitoring->improvement_execution_id,

                        'candidate_code' =>
                            $monitoring->candidate_code,

                        'observation_type' =>
                            strtoupper(
                                trim(
                                    $observationType
                                    ?? 'PERFORMANCE'
                                )
                            ),

                        'observed_score' =>
                            $observedScore,

                        'baseline_score' =>
                            $baselineScore,

                        'absolute_change' =>
                            $absoluteChange,

                        'percentage_change' =>
                            $percentageChange,

                        'performance_direction' =>
                            $direction,

                        'safety_passed' =>
                            $safetyPassed,

                        'outcome_status' =>
                            $outcomeStatus,

                        'evidence_quality' =>
                            $evidenceQuality,

                        'observation_context' =>
                            $context,

                        'human_reviewed' =>
                            false,

                        'observed_at' =>
                            now(),
                    ]);

                /*
                |--------------------------------------------------------------------------
                | 10. Recalculate Monitoring Summary
                |--------------------------------------------------------------------------
                */

                $query =
                    AIImprovementMonitoringObservation::where(
                        'monitoring_id',
                        $monitoring->id
                    );

                $count =
                    $query->count();

                $average =
                    round(
                        (float) $query->avg('observed_score'),
                        2
                    );

                $latest =
                    AIImprovementMonitoringObservation::where(
                        'monitoring_id',
                        $monitoring->id
                    )
                    ->latest('observed_at')
                    ->latest('id')
                    ->first();

                $averageChange =
                    round(
                        $average
                        - (float) $monitoring->baseline_score,
                        2
                    );

                if ($averageChange > 0) {
                    $averageDirection =
                        'IMPROVED';
                } elseif ($averageChange < 0) {
                    $averageDirection =
                        'DETERIORATED';
                } else {
                    $averageDirection =
                        'STABLE';
                }

                /*
                |--------------------------------------------------------------------------
                | 11. Monitoring Stage
                |--------------------------------------------------------------------------
                */

                $minimumRequired =
                    (int) $monitoring->minimum_observations_required;

                $stage =
                    $count >= $minimumRequired
                        ? 'OBSERVATION_THRESHOLD_REACHED'
                        : 'OBSERVATIONS_COLLECTING';

                $monitoring->update([
                    'monitoring_stage' =>
                        $stage,

                    'observation_count' =>
                        $count,

                    'latest_observed_score' =>
                        $latest?->observed_score,

                    'average_observed_score' =>
                        $average,

                    'performance_change' =>
                        $averageChange,

                    'performance_direction' =>
                        $averageDirection,

                    'observation_summary' => [
                        'observation_count' =>
                            $count,

                        'minimum_observations_required' =>
                            $minimumRequired,

                        'latest_observed_score' =>
                            $latest?->observed_score,

                        'average_observed_score' =>
                            $average,

                        'baseline_score' =>
                            (float) $monitoring->baseline_score,

                        'average_change_from_baseline' =>
                            $averageChange,

                        'performance_direction' =>
                            $averageDirection,

                        'safe_observations' =>
                            AIImprovementMonitoringObservation::where(
                                'monitoring_id',
                                $monitoring->id
                            )
                            ->where(
                                'safety_passed',
                                true
                            )
                            ->count(),

                        'unsafe_observations' =>
                            AIImprovementMonitoringObservation::where(
                                'monitoring_id',
                                $monitoring->id
                            )
                            ->where(
                                'safety_passed',
                                false
                            )
                            ->count(),

                        'analysis_ready' =>
                            $count >= $minimumRequired,
                    ],

                    'last_observed_at' =>
                        $latest?->observed_at,

                    /*
                    |--------------------------------------------------------------------------
                    | No automated response
                    |--------------------------------------------------------------------------
                    */

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
                    'captured' => true,

                    'status' =>
                        'MONITORING_OBSERVATION_CAPTURED',

                    'message' =>
                        'Post-improvement monitoring observation captured successfully.',

                    'observation' => [
                        'observation_id' =>
                            $observation->id,

                        'monitoring_id' =>
                            $observation->monitoring_id,

                        'candidate_code' =>
                            $observation->candidate_code,

                        'observation_type' =>
                            $observation->observation_type,

                        'observed_score' =>
                            $observation->observed_score,

                        'baseline_score' =>
                            $observation->baseline_score,

                        'absolute_change' =>
                            $observation->absolute_change,

                        'percentage_change' =>
                            $observation->percentage_change,

                        'performance_direction' =>
                            $observation->performance_direction,

                        'safety_passed' =>
                            $observation->safety_passed,

                        'outcome_status' =>
                            $observation->outcome_status,

                        'evidence_quality' =>
                            $observation->evidence_quality,

                        'observed_at' =>
                            $observation->observed_at,
                    ],

                    'monitoring_summary' => [
                        'monitoring_status' =>
                            $monitoring->monitoring_status,

                        'monitoring_stage' =>
                            $monitoring->monitoring_stage,

                        'baseline_score' =>
                            $monitoring->baseline_score,

                        'observation_count' =>
                            $monitoring->observation_count,

                        'minimum_observations_required' =>
                            $monitoring->minimum_observations_required,

                        'latest_observed_score' =>
                            $monitoring->latest_observed_score,

                        'average_observed_score' =>
                            $monitoring->average_observed_score,

                        'performance_change' =>
                            $monitoring->performance_change,

                        'performance_direction' =>
                            $monitoring->performance_direction,
                    ],

                    'observation_guardrails' => [
                        'observation_capture_is_ai_change' =>
                            false,

                        'observation_capture_triggers_rollback' =>
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
                            'Monitoring observations provide longitudinal evidence only. A negative observation does not automatically change, deploy, rollback, or alter clinical AI behavior.',
                    ],
                ];
            }
        );
    }
}