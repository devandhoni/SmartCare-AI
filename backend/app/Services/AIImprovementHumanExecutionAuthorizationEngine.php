<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementHumanExecutionAuthorizationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.5
    | Human Execution Authorization
    |--------------------------------------------------------------------------
    |
    | Supported decisions:
    |
    | - AUTHORIZE
    | - DEFER
    | - REJECT
    |
    | Authorization permits progression to controlled human-supervised
    | execution only.
    |
    | It does NOT enable:
    |
    | - automatic execution
    | - automatic deployment
    | - autonomous AI modification
    | - automatic rollback
    | - unrestricted production execution
    |
    */

    public function decide(
        int $executionId,
        string $decision,
        ?int $authorizedBy = null,
        ?string $notes = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Decision
        |--------------------------------------------------------------------------
        */

        $decision = strtoupper(trim($decision));

        $allowedDecisions = [
            'AUTHORIZE',
            'DEFER',
            'REJECT',
        ];

        if (!in_array($decision, $allowedDecisions, true)) {
            return [
                'decision_applied' => false,
                'status' => 'INVALID_DECISION',
                'message' => 'Unsupported human execution authorization decision.',
                'allowed_decisions' => $allowedDecisions,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Execution
        |--------------------------------------------------------------------------
        */

        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'decision_applied' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Current State
        |--------------------------------------------------------------------------
        */

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Closed-State Guardrail
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $executionStatus,
                [
                    'AUTHORIZED',
                    'REJECTED',
                ],
                true
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'EXECUTION_REVIEW_ALREADY_CLOSED',
                'message' => 'This execution authorization review already has a final decision.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Required Safety State
        |--------------------------------------------------------------------------
        */

        $safetyStatus = strtoupper(
            (string) ($execution->safety_status ?? '')
        );

        $executionReviewReady = (bool) (
            $execution->execution_review_ready
            ?? false
        );

        if (
            $decision === 'AUTHORIZE'
            &&
            (
                $executionStage !== 'SAFETY_VALIDATED'
                ||
                $safetyStatus !== 'SAFE_FOR_HUMAN_EXECUTION_REVIEW'
                ||
                !$executionReviewReady
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'NOT_READY_FOR_EXECUTION_AUTHORIZATION',
                'message' => 'Execution package has not passed the required pre-execution safety and readiness checks.',
                'execution_id' => $execution->id,
                'execution_stage' => $executionStage,
                'safety_status' => $safetyStatus,
                'execution_review_ready' => $executionReviewReady,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Safety Guardrails
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $execution->production_execution_allowed) {
            $criticalIssues[] =
                'Production execution permission is already enabled.';
        }

        if ((bool) $execution->automatic_execution_allowed) {
            $criticalIssues[] =
                'Automatic execution permission is already enabled.';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $criticalIssues[] =
                'Automatic deployment permission is already enabled.';
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            $criticalIssues[] =
                'Automatic rollback permission is already enabled.';
        }

        if (!(bool) $execution->human_execution_required) {
            $criticalIssues[] =
                'Human execution requirement is disabled.';
        }

        if (!(bool) $execution->post_execution_validation_required) {
            $criticalIssues[] =
                'Post-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->rollback_plan_required) {
            $criticalIssues[] =
                'Rollback plan requirement is disabled.';
        }

        if (!(bool) $execution->governance_validation_required) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'decision_applied' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Execution authorization is blocked because governance controls are invalid.',
                'execution_id' => $execution->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Apply Human Decision
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $execution,
                $decision,
                $authorizedBy,
                $notes
            ) {
                $executionStatus = 'REGISTERED';
                $executionStage = 'SAFETY_VALIDATED';
                $approvedForExecution = false;

                switch ($decision) {
                    case 'AUTHORIZE':
                        $executionStatus =
                            'AUTHORIZED';

                        $executionStage =
                            'EXECUTION_AUTHORIZED';

                        $approvedForExecution =
                            true;

                        break;

                    case 'DEFER':
                        $executionStatus =
                            'DEFERRED';

                        $executionStage =
                            'AUTHORIZATION_DEFERRED';

                        break;

                    case 'REJECT':
                        $executionStatus =
                            'REJECTED';

                        $executionStage =
                            'AUTHORIZATION_REJECTED';

                        break;
                }

                $existingPayload =
                    $execution->execution_payload
                    ?? [];

                if (!is_array($existingPayload)) {
                    $existingPayload = [];
                }

                $authorizationPayload = [
                    'human_execution_authorization' => [
                        'decision' =>
                            $decision,

                        'authorized_by' =>
                            $authorizedBy,

                        'authorization_notes' =>
                            $notes,

                        'authorized_at' =>
                            now()->toIso8601String(),

                        'execution_authorized' =>
                            $decision === 'AUTHORIZE',

                        /*
                        |--------------------------------------------------------------------------
                        | Still no automatic execution authority
                        |--------------------------------------------------------------------------
                        */

                        'production_execution_authorized' =>
                            false,

                        'automatic_execution_authorized' =>
                            false,

                        'automatic_deployment_authorized' =>
                            false,

                        'automatic_rollback_authorized' =>
                            false,

                        'human_supervised_execution_required' =>
                            true,
                    ],
                ];

                $executionPayload =
                    array_merge(
                        $existingPayload,
                        $authorizationPayload
                    );

                $execution->update([
                    'execution_status' =>
                        $executionStatus,

                    'execution_stage' =>
                        $executionStage,

                    'approved_for_execution' =>
                        $approvedForExecution,

                    'authorized_by' =>
                        $authorizedBy,

                    'authorized_at' =>
                        now(),

                    'authorization_notes' =>
                        $notes,

                    /*
                    |--------------------------------------------------------------------------
                    | Critical Separation
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

                    'human_execution_required' =>
                        true,

                    'post_execution_validation_required' =>
                        true,

                    'rollback_plan_required' =>
                        true,

                    'governance_validation_required' =>
                        true,

                    'execution_payload' =>
                        $executionPayload,
                ]);

                $execution->refresh();

                return [
                    'decision_applied' =>
                        true,

                    'status' =>
                        'EXECUTION_AUTHORIZATION_RECORDED',

                    'message' =>
                        'Human execution authorization decision recorded successfully.',

                    'execution' => [
                        'execution_id' =>
                            $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'safety_status' =>
                            $execution->safety_status,

                        'execution_review_ready' =>
                            (bool) $execution->execution_review_ready,

                        'approved_for_execution' =>
                            (bool) $execution->approved_for_execution,

                        'authorized_by' =>
                            $execution->authorized_by,

                        'authorized_at' =>
                            $execution->authorized_at,

                        'authorization_notes' =>
                            $execution->authorization_notes,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,

                        'human_execution_required' =>
                            (bool) $execution->human_execution_required,

                        'post_execution_validation_required' =>
                            (bool) $execution->post_execution_validation_required,

                        'rollback_plan_required' =>
                            (bool) $execution->rollback_plan_required,

                        'governance_validation_required' =>
                            (bool) $execution->governance_validation_required,
                    ],

                    'authorization_guardrails' => [
                        'human_authorization_is_execution' =>
                            false,

                        'approved_for_execution' =>
                            $approvedForExecution,

                        'human_supervised_execution_required' =>
                            true,

                        'production_execution_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'automatic_model_change' =>
                            false,

                        'automatic_threshold_change' =>
                            false,

                        'automatic_confidence_change' =>
                            false,

                        'automatic_recommendation_change' =>
                            false,

                        'automatic_workflow_change' =>
                            false,

                        'automatic_clinical_rule_change' =>
                            false,

                        'automatic_clinical_action' =>
                            false,

                        'post_execution_validation_required' =>
                            true,

                        'rollback_plan_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Human execution authorization permits progression to the controlled execution stage only. It does not automatically execute, deploy, or modify production AI behavior.',
                    ],
                ];
            }
        );
    }
}