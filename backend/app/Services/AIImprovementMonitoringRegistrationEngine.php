<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use App\Models\AIImprovementMonitoring;
use Illuminate\Support\Facades\DB;

class AIImprovementMonitoringRegistrationEngine
{
    public function register(
        int $executionId,
        int $monitoringWindowDays = 30,
        int $minimumObservationsRequired = 5,
        float $regressionTolerancePercentage = 10,
        float $safetyThresholdPercentage = 80
    ): array {
        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'registered' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Only verified controlled executions may enter monitoring
        |--------------------------------------------------------------------------
        */

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        if (
            $executionStatus !== 'VERIFIED'
            ||
            $executionStage !== 'POST_EXECUTION_VERIFIED'
        ) {
            return [
                'registered' => false,
                'status' => 'EXECUTION_NOT_ELIGIBLE_FOR_MONITORING',
                'message' => 'Only verified controlled executions may enter longitudinal monitoring.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Final Step 56 validation must still pass
        |--------------------------------------------------------------------------
        */

        $finalValidation = app(
            AIImprovementExecutionFinalValidationEngine::class
        )->analyze($execution->id);

        if (
            !($finalValidation['step_56_ready_for_closure'] ?? false)
            ||
            ($finalValidation['validation_status'] ?? null) !== 'PASSED'
        ) {
            return [
                'registered' => false,
                'status' => 'STEP_56_VALIDATION_REQUIRED',
                'message' => 'Step 56 final governance validation must pass before longitudinal monitoring may begin.',
                'execution_id' => $execution->id,
                'validation_status' => $finalValidation['validation_status'] ?? null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Prevent duplicate monitoring registration
        |--------------------------------------------------------------------------
        */

        $existing = AIImprovementMonitoring::where(
            'improvement_execution_id',
            $execution->id
        )->first();

        if ($existing) {
            return [
                'registered' => false,
                'status' => 'MONITORING_ALREADY_REGISTERED',
                'message' => 'A longitudinal monitoring record already exists for this improvement execution.',
                'monitoring_id' => $existing->id,
                'monitoring_status' => $existing->monitoring_status,
                'monitoring_stage' => $existing->monitoring_stage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Build baseline from verified Step 56 outcome
        |--------------------------------------------------------------------------
        */

        $executionResults = $execution->execution_results ?? [];

        if (!is_array($executionResults)) {
            $executionResults = [];
        }

        $verification = $execution->verification_results ?? [];

        if (!is_array($verification)) {
            $verification = [];
        }

        $baselineScore = isset($executionResults['execution_score'])
            ? (float) $executionResults['execution_score']
            : null;

        if ($baselineScore === null) {
            return [
                'registered' => false,
                'status' => 'MONITORING_BASELINE_UNAVAILABLE',
                'message' => 'Verified execution score is required to establish the longitudinal monitoring baseline.',
                'execution_id' => $execution->id,
            ];
        }

        $baselineDirection = strtoupper(
            (string) (
                $executionResults['direction']
                ?? 'UNKNOWN'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Validate monitoring parameters
        |--------------------------------------------------------------------------
        */

        $monitoringWindowDays = max(
            1,
            min(365, $monitoringWindowDays)
        );

        $minimumObservationsRequired = max(
            2,
            min(100, $minimumObservationsRequired)
        );

        $regressionTolerancePercentage = max(
            0,
            min(100, $regressionTolerancePercentage)
        );

        $safetyThresholdPercentage = max(
            0,
            min(100, $safetyThresholdPercentage)
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Baseline context
        |--------------------------------------------------------------------------
        */

        $baselineContext = [
            'source' => 'STEP_56_VERIFIED_CONTROLLED_EXECUTION',

            'execution_id' =>
                $execution->id,

            'execution_status' =>
                $execution->execution_status,

            'execution_stage' =>
                $execution->execution_stage,

            'execution_score' =>
                $baselineScore,

            'baseline_score' =>
                $executionResults['baseline_score']
                ?? null,

            'absolute_change' =>
                $executionResults['absolute_change']
                ?? null,

            'direction' =>
                $baselineDirection,

            'outcome_status' =>
                $executionResults['outcome_status']
                ?? null,

            'safety_passed' =>
                (bool) (
                    $executionResults['safety_passed']
                    ?? false
                ),

            'verification_decision' =>
                $verification['decision']
                ?? null,

            'verified_at' =>
                $execution->verified_at?->toIso8601String(),

            'production_change_applied' =>
                (bool) (
                    $executionResults['production_change_applied']
                    ?? false
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | 7. Monitoring configuration
        |--------------------------------------------------------------------------
        */

        $monitoringConfiguration = [
            'monitoring_version' => '57.2',

            'monitoring_mode' =>
                'LONGITUDINAL_VALIDATION',

            'monitoring_window_days' =>
                $monitoringWindowDays,

            'minimum_observations_required' =>
                $minimumObservationsRequired,

            'regression_tolerance_percentage' =>
                $regressionTolerancePercentage,

            'safety_threshold_percentage' =>
                $safetyThresholdPercentage,

            'baseline_score' =>
                $baselineScore,

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

            'governance_validation_required' =>
                true,

            'registered_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 8. Register monitoring record
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $execution,
                $baselineScore,
                $baselineDirection,
                $baselineContext,
                $monitoringConfiguration,
                $monitoringWindowDays,
                $minimumObservationsRequired,
                $regressionTolerancePercentage,
                $safetyThresholdPercentage
            ) {
                $monitoring = AIImprovementMonitoring::create([
                    'improvement_execution_id' =>
                        $execution->id,

                    'implementation_review_id' =>
                        $execution->implementation_review_id,

                    'improvement_review_id' =>
                        $execution->improvement_review_id,

                    'improvement_test_id' =>
                        $execution->improvement_test_id,

                    'candidate_code' =>
                        $execution->candidate_code,

                    'candidate_category' =>
                        $execution->candidate_category,

                    'scope_type' =>
                        $execution->scope_type,

                    'resident_id' =>
                        $execution->resident_id,

                    'monitoring_status' =>
                        'ACTIVE',

                    'monitoring_stage' =>
                        'BASELINE_ESTABLISHED',

                    'monitoring_mode' =>
                        'LONGITUDINAL_VALIDATION',

                    'baseline_score' =>
                        $baselineScore,

                    'baseline_direction' =>
                        $baselineDirection,

                    'baseline_context' =>
                        $baselineContext,

                    'monitoring_window_days' =>
                        $monitoringWindowDays,

                    'minimum_observations_required' =>
                        $minimumObservationsRequired,

                    'regression_tolerance_percentage' =>
                        $regressionTolerancePercentage,

                    'safety_threshold_percentage' =>
                        $safetyThresholdPercentage,

                    'observation_count' =>
                        0,

                    'stability_status' =>
                        'INSUFFICIENT_DATA',

                    'regression_status' =>
                        'NOT_EVALUATED',

                    'safety_monitoring_status' =>
                        'NOT_EVALUATED',

                    'sustainability_status' =>
                        'NOT_EVALUATED',

                    'monitoring_configuration' =>
                        $monitoringConfiguration,

                    'monitoring_payload' => [
                        'source_step_56_final_validation' =>
                            'PASSED',

                        'source_final_outcome' =>
                            'VERIFIED_POSITIVE',

                        'monitoring_registration_is_ai_change' =>
                            false,

                        'production_change_authorized' =>
                            false,

                        'automatic_change_authorized' =>
                            false,
                    ],

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

                    'governance_validation_required' =>
                        true,

                    'monitoring_started_at' =>
                        now(),
                ]);

                return [
                    'registered' => true,

                    'status' =>
                        'MONITORING_REGISTERED',

                    'message' =>
                        'AI improvement longitudinal monitoring registered successfully.',

                    'monitoring' => [
                        'monitoring_id' =>
                            $monitoring->id,

                        'improvement_execution_id' =>
                            $monitoring->improvement_execution_id,

                        'candidate_code' =>
                            $monitoring->candidate_code,

                        'candidate_category' =>
                            $monitoring->candidate_category,

                        'scope_type' =>
                            $monitoring->scope_type,

                        'resident_id' =>
                            $monitoring->resident_id,

                        'monitoring_status' =>
                            $monitoring->monitoring_status,

                        'monitoring_stage' =>
                            $monitoring->monitoring_stage,

                        'monitoring_mode' =>
                            $monitoring->monitoring_mode,

                        'baseline_score' =>
                            $monitoring->baseline_score,

                        'baseline_direction' =>
                            $monitoring->baseline_direction,

                        'monitoring_window_days' =>
                            $monitoring->monitoring_window_days,

                        'minimum_observations_required' =>
                            $monitoring->minimum_observations_required,

                        'regression_tolerance_percentage' =>
                            $monitoring->regression_tolerance_percentage,

                        'safety_threshold_percentage' =>
                            $monitoring->safety_threshold_percentage,

                        'observation_count' =>
                            $monitoring->observation_count,

                        'monitoring_started_at' =>
                            $monitoring->monitoring_started_at,
                    ],

                    'monitoring_guardrails' => [
                        'monitoring_registration_is_production_change' =>
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

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Longitudinal monitoring observes the verified improvement over time only. Registration does not modify AI configuration, clinical rules, workflows, or production behavior.',
                    ],
                ];
            }
        );
    }
}