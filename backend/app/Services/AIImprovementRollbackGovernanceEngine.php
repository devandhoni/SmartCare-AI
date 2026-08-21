<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementRollbackGovernanceEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.8
    | Rollback & Recovery Governance
    |--------------------------------------------------------------------------
    |
    | This engine:
    |
    | - creates a governed rollback plan
    | - validates rollback readiness
    | - may record a human rollback execution decision
    |
    | It does NOT perform automatic rollback.
    |
    */

    public function preparePlan(
        int $executionId,
        array $rollbackSteps = [],
        ?string $recoveryObjective = null
    ): array {
        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'plan_created' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Supported states
        |--------------------------------------------------------------------------
        |
        | A rollback plan may be prepared after controlled execution evidence
        | exists. For the successful synthetic pathway we are currently at
        | VERIFIED / POST_EXECUTION_VERIFIED.
        |
        */

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $allowedStatuses = [
            'VERIFIED',
            'ROLLBACK_REQUIRED',
            'VERIFICATION_REJECTED',
        ];

        if (!in_array($executionStatus, $allowedStatuses, true)) {
            return [
                'plan_created' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Rollback planning requires completed post-execution evidence.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $execution->execution_stage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Guardrails
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $execution->automatic_rollback_allowed) {
            $criticalIssues[] = 'Automatic rollback permission is enabled.';
        }

        if ((bool) $execution->automatic_execution_allowed) {
            $criticalIssues[] = 'Automatic execution permission is enabled.';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $criticalIssues[] = 'Automatic deployment permission is enabled.';
        }

        if (!(bool) $execution->rollback_plan_required) {
            $criticalIssues[] = 'Rollback planning requirement is disabled.';
        }

        if (!(bool) $execution->governance_validation_required) {
            $criticalIssues[] = 'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'plan_created' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Rollback planning is blocked because governance controls are invalid.',
                'execution_id' => $execution->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Baseline Required
        |--------------------------------------------------------------------------
        */

        $baseline = $execution->baseline_configuration ?? [];

        if (!is_array($baseline) || empty($baseline)) {
            return [
                'plan_created' => false,
                'status' => 'ROLLBACK_BASELINE_MISSING',
                'message' => 'Rollback planning requires the original baseline configuration.',
                'execution_id' => $execution->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Default Rollback Steps
        |--------------------------------------------------------------------------
        */

        if (empty($rollbackSteps)) {
            $rollbackSteps = [
                'Confirm human rollback authorization.',
                'Suspend further use of the staged proposed configuration.',
                'Restore the registered baseline configuration.',
                'Verify restored configuration integrity.',
                'Perform post-rollback safety validation.',
                'Record rollback outcome and governance evidence.',
            ];
        }

        $impact = $execution->impact_analysis ?? [];

        if (!is_array($impact)) {
            $impact = [];
        }

        $verification = $execution->verification_results ?? [];

        if (!is_array($verification)) {
            $verification = [];
        }

        $rollbackRequired = (bool) (
            $verification['rollback_required']
            ?? false
        );

        $rollbackPlan = [
            'rollback_version' => '56.8',

            'plan_status' => 'READY',

            'rollback_required_currently' =>
                $rollbackRequired,

            'recovery_objective' =>
                $recoveryObjective
                ?? 'Restore the registered baseline configuration and verify controlled system stability.',

            'baseline_configuration' =>
                $baseline,

            'rollback_steps' =>
                array_values($rollbackSteps),

            'rollback_complexity' =>
                strtoupper(
                    (string) (
                        $impact['rollback_complexity']
                        ?? 'UNKNOWN'
                    )
                ),

            'reversible' =>
                (bool) (
                    $impact['reversible']
                    ?? false
                ),

            /*
            |--------------------------------------------------------------------------
            | Governance
            |--------------------------------------------------------------------------
            */

            'human_rollback_authorization_required' =>
                true,

            'automatic_rollback_authorized' =>
                false,

            'production_change_authorized' =>
                false,

            'post_rollback_validation_required' =>
                true,

            'governance_validation_required' =>
                true,

            'prepared_at' =>
                now()->toIso8601String(),
        ];

        return DB::transaction(
            function () use (
                $execution,
                $rollbackPlan
            ) {
                $execution->update([
                    'rollback_plan' =>
                        $rollbackPlan,

                    /*
                    |--------------------------------------------------------------------------
                    | Successful verified executions stay verified.
                    |
                    | We record that rollback governance is prepared without
                    | pretending rollback is needed.
                    |--------------------------------------------------------------------------
                    */

                    'automatic_rollback_allowed' =>
                        false,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'production_execution_allowed' =>
                        false,
                ]);

                $execution->refresh();

                return [
                    'plan_created' => true,

                    'status' =>
                        'ROLLBACK_PLAN_READY',

                    'message' =>
                        'Rollback and recovery governance plan prepared successfully.',

                    'execution' => [
                        'execution_id' =>
                            $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'rollback_plan' =>
                            $execution->rollback_plan,

                        'rolled_back_at' =>
                            $execution->rolled_back_at,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,
                    ],

                    'rollback_guardrails' => [
                        'rollback_plan_is_rollback_execution' =>
                            false,

                        'human_rollback_authorization_required' =>
                            true,

                        'automatic_rollback_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'production_execution_allowed' =>
                            false,

                        'post_rollback_validation_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Rollback planning establishes a governed recovery path only. It does not automatically restore configuration or execute any system change.',
                    ],
                ];
            }
        );
    }

    public function recordRollback(
        int $executionId,
        int $authorizedBy,
        string $notes,
        array $results = []
    ): array {
        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'rollback_recorded' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Rollback only when explicitly required
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper((string) $execution->execution_status)
            !== 'ROLLBACK_REQUIRED'
        ) {
            return [
                'rollback_recorded' => false,
                'status' => 'ROLLBACK_NOT_REQUIRED',
                'message' => 'This execution is not currently in a rollback-required state.',
                'execution_id' => $execution->id,
                'execution_status' => $execution->execution_status,
                'execution_stage' => $execution->execution_stage,
            ];
        }

        $plan = $execution->rollback_plan ?? [];

        if (!is_array($plan) || empty($plan)) {
            return [
                'rollback_recorded' => false,
                'status' => 'ROLLBACK_PLAN_MISSING',
                'message' => 'A governed rollback plan is required before rollback may be recorded.',
                'execution_id' => $execution->id,
            ];
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            return [
                'rollback_recorded' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Rollback cannot proceed while automatic rollback permission is enabled.',
                'execution_id' => $execution->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | This method records a human-supervised rollback outcome.
        | It still does NOT contain an adapter that modifies production
        | configuration.
        |
        */

        $rollbackResults = [
            'rollback_version' => '56.8',

            'rollback_mode' =>
                'HUMAN_SUPERVISED_CONTROLLED',

            'authorized_by' =>
                $authorizedBy,

            'authorization_notes' =>
                $notes,

            'results' =>
                $results,

            'baseline_restoration_recorded' =>
                (bool) (
                    $results['baseline_restored']
                    ?? false
                ),

            'safety_passed' =>
                (bool) (
                    $results['safety_passed']
                    ?? false
                ),

            'production_configuration_automatically_modified' =>
                false,

            'automatic_rollback_used' =>
                false,

            'post_rollback_validation_required' =>
                true,

            'recorded_at' =>
                now()->toIso8601String(),
        ];

        return DB::transaction(
            function () use (
                $execution,
                $rollbackResults
            ) {
                $execution->update([
                    'execution_status' =>
                        'ROLLED_BACK',

                    'execution_stage' =>
                        'ROLLBACK_RECORDED',

                    'rollback_results' =>
                        $rollbackResults,

                    'rolled_back_at' =>
                        now(),

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
                    'rollback_recorded' =>
                        true,

                    'status' =>
                        'ROLLBACK_OUTCOME_RECORDED',

                    'message' =>
                        'Human-supervised rollback outcome recorded successfully.',

                    'execution' => [
                        'execution_id' =>
                            $execution->id,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'rollback_results' =>
                            $execution->rollback_results,

                        'rolled_back_at' =>
                            $execution->rolled_back_at,
                    ],

                    'rollback_guardrails' => [
                        'automatic_rollback_used' =>
                            false,

                        'automatic_deployment_used' =>
                            false,

                        'post_rollback_validation_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Rollback outcome recording does not itself perform an automatic production rollback.',
                    ],
                ];
            }
        );
    }
}