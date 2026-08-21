<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementControlledExecutionEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.6
    | Controlled / Staged Execution Engine
    |--------------------------------------------------------------------------
    |
    | This engine records controlled execution activity only.
    |
    | It does NOT modify production configuration.
    |
    */

    public function start(int $executionId): array
    {
        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'started' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        if (
            $executionStatus !== 'AUTHORIZED'
            ||
            $executionStage !== 'EXECUTION_AUTHORIZED'
        ) {
            return [
                'started' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Only an authorized execution package may enter controlled execution.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        if (!(bool) $execution->approved_for_execution) {
            return [
                'started' => false,
                'status' => 'EXECUTION_NOT_APPROVED',
                'message' => 'Human execution authorization is required before controlled execution may start.',
                'execution_id' => $execution->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Isolation
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $execution->production_execution_allowed
            ||
            (bool) $execution->automatic_execution_allowed
            ||
            (bool) $execution->automatic_deployment_allowed
            ||
            (bool) $execution->automatic_rollback_allowed
        ) {
            return [
                'started' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Controlled execution is blocked because an automatic or production execution permission is enabled.',
                'execution_id' => $execution->id,
            ];
        }

        $scope = $execution->execution_scope ?? [];

        if (!is_array($scope)) {
            $scope = [];
        }

        $environment = strtoupper(
            (string) ($scope['environment'] ?? '')
        );

        if ($environment !== 'CONTROLLED') {
            return [
                'started' => false,
                'status' => 'INVALID_EXECUTION_ENVIRONMENT',
                'message' => 'Step 56.6 supports controlled execution only.',
                'execution_id' => $execution->id,
                'environment' => $environment,
            ];
        }

        $existingPayload = $execution->execution_payload ?? [];

        if (!is_array($existingPayload)) {
            $existingPayload = [];
        }

        $existingPayload['controlled_execution'] = [
            'execution_mode' => 'STAGED_CONTROLLED',
            'production_change_applied' => false,
            'automatic_execution_used' => false,
            'automatic_deployment_used' => false,
            'started_at' => now()->toIso8601String(),
        ];

        $execution->update([
            'execution_status' => 'RUNNING',
            'execution_stage' => 'CONTROLLED_EXECUTION',
            'execution_started_at' => now(),

            'production_execution_allowed' => false,
            'automatic_execution_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,

            'execution_payload' => $existingPayload,
        ]);

        $execution->refresh();

        return [
            'started' => true,
            'status' => 'CONTROLLED_EXECUTION_STARTED',
            'message' => 'Controlled AI improvement execution started successfully.',

            'execution' => [
                'execution_id' => $execution->id,
                'candidate_code' => $execution->candidate_code,
                'execution_status' => $execution->execution_status,
                'execution_stage' => $execution->execution_stage,
                'execution_started_at' => $execution->execution_started_at,

                'production_execution_allowed' =>
                    (bool) $execution->production_execution_allowed,

                'automatic_execution_allowed' =>
                    (bool) $execution->automatic_execution_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $execution->automatic_deployment_allowed,

                'automatic_rollback_allowed' =>
                    (bool) $execution->automatic_rollback_allowed,
            ],

            'execution_guardrails' => [
                'controlled_execution_only' => true,
                'production_configuration_modified' => false,
                'automatic_execution_used' => false,
                'automatic_deployment_used' => false,
                'automatic_rollback_used' => false,
                'post_execution_validation_required' => true,
                'rollback_plan_required' => true,
                'human_supervision_required' => true,
                'message' =>
                    'Step 56.6 records a controlled staged execution only. No production AI configuration or clinical behavior is modified.',
            ],
        ];
    }

    public function complete(
        int $executionId,
        array $results
    ): array {
        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'completed' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        if (
            strtoupper((string) $execution->execution_status) !== 'RUNNING'
            ||
            strtoupper((string) $execution->execution_stage) !== 'CONTROLLED_EXECUTION'
        ) {
            return [
                'completed' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Only a running controlled execution may be completed.',
                'execution_id' => $execution->id,
                'execution_status' => $execution->execution_status,
                'execution_stage' => $execution->execution_stage,
            ];
        }

        $baselineScore = isset($results['baseline_score'])
            ? (float) $results['baseline_score']
            : null;

        $executionScore = isset($results['execution_score'])
            ? (float) $results['execution_score']
            : null;

        $safetyPassed = (bool) (
            $results['safety_passed']
            ?? false
        );

        $observations = $results['observations'] ?? [];

        if (!is_array($observations)) {
            $observations = [$observations];
        }

        $absoluteChange = null;
        $direction = 'UNKNOWN';

        if (
            $baselineScore !== null
            &&
            $executionScore !== null
        ) {
            $absoluteChange = round(
                $executionScore - $baselineScore,
                2
            );

            if ($absoluteChange > 0) {
                $direction = 'IMPROVED';
            } elseif ($absoluteChange < 0) {
                $direction = 'DETERIORATED';
            } else {
                $direction = 'STABLE';
            }
        }

        if (!$safetyPassed) {
            $outcomeStatus = 'SAFETY_REVIEW_REQUIRED';
        } elseif ($direction === 'DETERIORATED') {
            $outcomeStatus = 'NEGATIVE_SIGNAL';
        } elseif ($direction === 'IMPROVED') {
            $outcomeStatus = 'POSITIVE_SIGNAL';
        } elseif ($direction === 'STABLE') {
            $outcomeStatus = 'STABLE_SIGNAL';
        } else {
            $outcomeStatus = 'INCONCLUSIVE';
        }

        $executionResults = [
            'execution_mode' =>
                'STAGED_CONTROLLED',

            'baseline_score' =>
                $baselineScore,

            'execution_score' =>
                $executionScore,

            'absolute_change' =>
                $absoluteChange,

            'direction' =>
                $direction,

            'safety_passed' =>
                $safetyPassed,

            'outcome_status' =>
                $outcomeStatus,

            'observations' =>
                $observations,

            /*
            |--------------------------------------------------------------------------
            | Critical evidence isolation
            |--------------------------------------------------------------------------
            */

            'production_change_applied' =>
                false,

            'automatic_execution_used' =>
                false,

            'automatic_deployment_used' =>
                false,

            'automatic_rollback_used' =>
                false,

            'post_execution_validation_required' =>
                true,

            'recorded_at' =>
                now()->toIso8601String(),
        ];

        return DB::transaction(
            function () use (
                $execution,
                $executionResults
            ) {
                $execution->update([
                    'execution_status' =>
                        'AWAITING_VERIFICATION',

                    'execution_stage' =>
                        'CONTROLLED_EXECUTION_COMPLETED',

                    'execution_results' =>
                        $executionResults,

                    'execution_completed_at' =>
                        now(),

                    /*
                    |--------------------------------------------------------------------------
                    | Still isolated
                    |--------------------------------------------------------------------------
                    */

                    'production_execution_allowed' =>
                        false,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,
                ]);

                $execution->refresh();

                return [
                    'completed' =>
                        true,

                    'status' =>
                        'CONTROLLED_EXECUTION_RESULTS_RECORDED',

                    'message' =>
                        'Controlled AI improvement execution completed. Post-execution human verification is required.',

                    'execution' => [
                        'execution_id' =>
                            $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'execution_started_at' =>
                            $execution->execution_started_at,

                        'execution_completed_at' =>
                            $execution->execution_completed_at,

                        'execution_results' =>
                            $execution->execution_results,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,
                    ],

                    'execution_guardrails' => [
                        'controlled_execution_only' =>
                            true,

                        'execution_results_are_final_approval' =>
                            false,

                        'production_change_applied' =>
                            false,

                        'automatic_execution_used' =>
                            false,

                        'automatic_deployment_used' =>
                            false,

                        'automatic_rollback_used' =>
                            false,

                        'post_execution_human_verification_required' =>
                            true,

                        'rollback_governance_required' =>
                            true,

                        'message' =>
                            'Controlled execution results are experimental execution evidence only. They do not authorize production deployment or waive post-execution verification.',
                    ],
                ];
            }
        );
    }
}