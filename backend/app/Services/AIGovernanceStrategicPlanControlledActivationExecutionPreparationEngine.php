<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;
use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;
use Illuminate\Support\Facades\DB;

class AIGovernanceStrategicPlanControlledActivationExecutionPreparationEngine
{
    public function analyze(?int $executionAuthorizationId = null): array
    {
        $authorization = $executionAuthorizationId
            ? AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::find($executionAuthorizationId)
            : AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'preparation_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_AVAILABLE',
                'controlled_activation_execution' => null,
                'guardrails' => $this->guardrails(),
            ];
        }

        $sourceDecisionRecorded = !is_null($authorization->controlled_activation_execution_authorization_decision);

        $sourceAuthorizedHuman = (bool) $authorization->execution_authorization_made_by_authorized_human;

        $sourceAttributionComplete =
            !empty($authorization->execution_authorized_by) &&
            !empty($authorization->execution_authorizer_role) &&
            !is_null($authorization->execution_authorized_at) &&
            $sourceAuthorizedHuman;

        $sourceAuthorizationCompleted =
            (bool) $authorization->controlled_activation_execution_authorization_completed;

        /*
         * Step 71.2 is intentionally conservative.
         *
         * Preparation of a registry record is allowed even while the source
         * authorization is incomplete. Actual execution readiness, however,
         * must remain blocked until all source authorization requirements
         * have been satisfied by explicitly authorized human governance.
         */
        $sourceReadyForExecution =
            $sourceDecisionRecorded &&
            $sourceAuthorizedHuman &&
            $sourceAttributionComplete &&
            $sourceAuthorizationCompleted;

        $executionStatus = $sourceReadyForExecution
            ? 'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION'
            : 'BLOCKED_PENDING_VALID_EXECUTION_AUTHORIZATION';

        $executionReadiness = $sourceReadyForExecution
            ? 'READY_FOR_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
            : 'AWAITING_COMPLETED_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION';

        $executionRiskLevel = $sourceReadyForExecution
            ? 'CONTROLLED_EXECUTION_REQUIRES_AUTHORIZED_HUMAN_REVIEW'
            : 'CRITICAL_EXECUTION_AUTHORIZATION_DEPENDENCY_UNRESOLVED';

        $executionRiskScore = $sourceReadyForExecution ? 50.00 : 100.00;
        $executionReadinessScore = $sourceReadyForExecution ? 50.00 : 0.00;

        $execution = DB::transaction(function () use (
            $authorization,
            $sourceDecisionRecorded,
            $sourceAuthorizedHuman,
            $sourceAttributionComplete,
            $sourceAuthorizationCompleted,
            $sourceReadyForExecution,
            $executionStatus,
            $executionReadiness,
            $executionRiskLevel,
            $executionRiskScore,
            $executionReadinessScore
        ) {
            $execution = AIGovernanceStrategicPlanControlledActivationExecution::firstOrNew([
                'strategic_plan_controlled_activation_execution_authorization_id' => $authorization->id,
            ]);

            if (!$execution->exists) {
                $execution->controlled_activation_execution_code =
                    'GOV-STRATEGIC-PLAN-ACT-EXEC-' .
                    $authorization->id . '-' .
                    now()->format('YmdHis');
            }

            $execution->fill([
                'strategic_plan_controlled_activation_authorization_id'
                    => $authorization->strategic_plan_controlled_activation_authorization_id,

                'strategic_plan_governance_decision_confirmation_id'
                    => $authorization->strategic_plan_governance_decision_confirmation_id,

                'strategic_plan_final_governance_decision_id'
                    => $authorization->strategic_plan_final_governance_decision_id,

                'strategic_plan_decision_validation_id'
                    => $authorization->strategic_plan_decision_validation_id,

                'strategic_plan_human_decision_id'
                    => $authorization->strategic_plan_human_decision_id,

                'strategic_plan_decision_id'
                    => $authorization->strategic_plan_decision_id,

                'strategic_plan_id'
                    => $authorization->strategic_plan_id,

                'strategic_snapshot_id'
                    => $authorization->strategic_snapshot_id,

                'operational_snapshot_id'
                    => $authorization->operational_snapshot_id,

                'lifecycle_snapshot_id'
                    => $authorization->lifecycle_snapshot_id,

                'decision_scope'
                    => $authorization->decision_scope,

                'resident_id'
                    => $authorization->resident_id,

                'controlled_activation_execution_status'
                    => $executionStatus,

                'controlled_activation_execution_mode'
                    => 'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION',

                'source_execution_authorization_decision'
                    => $authorization->controlled_activation_execution_authorization_decision,

                'source_execution_authorization_decision_recorded'
                    => $sourceDecisionRecorded,

                'source_execution_authorization_made_by_authorized_human'
                    => $sourceAuthorizedHuman,

                'source_execution_authorization_attribution_complete'
                    => $sourceAttributionComplete,

                'source_execution_authorization_completed'
                    => $sourceAuthorizationCompleted,

                'source_execution_authorization_status'
                    => $authorization->execution_authorization_status,

                'source_execution_authorization_outcome'
                    => $authorization->execution_authorization_outcome,

                'source_execution_authorization_outcome_status'
                    => $authorization->execution_authorization_outcome_status,

                'execution_decision' => null,
                'execution_rationale' => null,
                'execution_notes' => null,
                'execution_outcome' => null,

                'execution_outcome_status'
                    => 'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION',

                'execution_readiness'
                    => $executionReadiness,

                'execution_readiness_score'
                    => $executionReadinessScore,

                'execution_risk_level'
                    => $executionRiskLevel,

                'execution_risk_score'
                    => $executionRiskScore,

                /*
                 * Step 71.2 does not claim condition/evidence resolution.
                 * Later Step 71 intelligence will calculate these values.
                 */
                'condition_resolution_score' => 0,
                'evidence_resolution_score' => 0,
                'combined_resolution_score' => 0,
                'condition_pressure_score' => $sourceReadyForExecution ? 50 : 100,
                'restriction_pressure_score' => $sourceReadyForExecution ? 50 : 100,
                'combined_condition_restriction_pressure_score'
                    => $sourceReadyForExecution ? 50 : 100,

                'execution_conditions' => [
                    [
                        'code' => 'VALID_EXECUTION_AUTHORIZATION_REQUIRED',
                        'satisfied' => $sourceAuthorizationCompleted,
                        'blocking_execution' => !$sourceAuthorizationCompleted,
                    ],
                    [
                        'code' => 'AUTHORIZED_HUMAN_EXECUTION_AUTHORIZER_REQUIRED',
                        'satisfied' => $sourceAuthorizedHuman,
                        'blocking_execution' => !$sourceAuthorizedHuman,
                    ],
                    [
                        'code' => 'EXECUTION_AUTHORIZATION_ATTRIBUTION_REQUIRED',
                        'satisfied' => $sourceAttributionComplete,
                        'blocking_execution' => !$sourceAttributionComplete,
                    ],
                    [
                        'code' => 'SEPARATE_AUTHORIZED_HUMAN_EXECUTION_REQUIRED',
                        'satisfied' => false,
                        'blocking_execution' => true,
                    ],
                ],

                'validated_evidence' => [],

                'execution_findings' => [
                    'Controlled activation execution registry record has been prepared.',
                    'Preparation does not constitute controlled activation execution.',
                    'Execution authorization and actual execution remain separate governance stages.',
                    $sourceReadyForExecution
                        ? 'Source execution authorization requirements appear complete for subsequent governed execution review.'
                        : 'Source execution authorization requirements remain incomplete and actual execution is blocked.',
                ],

                'execution_restrictions' => [
                    'NO_AUTOMATIC_CONTROLLED_ACTIVATION_EXECUTION',
                    'NO_AUTONOMOUS_STRATEGIC_PLAN_ACTIVATION',
                    'NO_AUTOMATIC_AI_CHANGE',
                    'NO_AUTOMATIC_DEPLOYMENT',
                    'NO_AUTOMATIC_ROLLBACK',
                    'NO_AUTOMATIC_CLINICAL_ACTION',
                    'AUTHORIZED_HUMAN_EXECUTION_REQUIRED',
                ],

                'review_context' => [
                    'source_ready_for_execution_review' => $sourceReadyForExecution,
                    'authorized_human_execution_required' => true,
                    'actual_execution_performed' => false,
                ],

                'execution_authorization_context' => [
                    'execution_authorization_id' => $authorization->id,
                    'execution_authorization_code'
                        => $authorization->execution_authorization_code,
                    'decision_recorded' => $sourceDecisionRecorded,
                    'made_by_authorized_human' => $sourceAuthorizedHuman,
                    'attribution_complete' => $sourceAttributionComplete,
                    'authorization_completed' => $sourceAuthorizationCompleted,
                ],

                'governance_context' => [
                    'human_review_required' => true,
                    'authorized_human_execution_required' => true,
                    'execution_authorization_separate_from_execution' => true,
                    'autonomous_execution_allowed' => false,
                ],

                'source_context' => [
                    'source_type'
                        => 'CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION',

                    'source_id'
                        => $authorization->id,

                    'source_code'
                        => $authorization->execution_authorization_code,
                ],

                /*
                 * Absolutely no execution attribution is generated by
                 * the preparation engine.
                 */
                'executed_by' => null,
                'executor_role' => null,
                'executed_at' => null,

                'execution_made_by_authorized_human' => false,
                'execution_attribution_complete' => false,
                'controlled_activation_executed' => false,

                'post_execution_status' => 'NOT_EXECUTED',
                'post_execution_validation_status' => null,
                'post_execution_validation_completed' => false,

                /*
                 * Authority isolation.
                 */
                'automatic_execution_allowed' => false,
                'automatic_execution_authorization_allowed' => false,
                'automatic_activation_authorization_allowed' => false,
                'automatic_confirmation_allowed' => false,
                'automatic_final_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_conditional_approval_allowed' => false,
                'automatic_deferral_allowed' => false,
                'automatic_risk_acceptance_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_restriction_removal_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_confirmation_required' => true,
                'controlled_activation_authorization_required' => true,
                'authorized_human_execution_authorization_required' => true,
                'authorized_human_execution_required' => true,
                'post_execution_human_validation_required' => true,
            ]);

            $execution->save();

            return $execution->fresh();
        });

        return [
            'preparation_completed' => true,
            'status' => 'CONTROLLED_ACTIVATION_EXECUTION_PREPARED',
            'strategic_plan_controlled_activation_execution_id' => $execution->id,
            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'strategic_plan_controlled_activation_execution_authorization_id'
                => $authorization->id,

            'execution_state' => [
                'controlled_activation_execution_status'
                    => $execution->controlled_activation_execution_status,

                'controlled_activation_execution_mode'
                    => $execution->controlled_activation_execution_mode,

                'execution_readiness'
                    => $execution->execution_readiness,

                'execution_readiness_score'
                    => $execution->execution_readiness_score,

                'execution_risk_level'
                    => $execution->execution_risk_level,

                'execution_risk_score'
                    => $execution->execution_risk_score,

                'controlled_activation_executed'
                    => $execution->controlled_activation_executed,

                'post_execution_status'
                    => $execution->post_execution_status,
            ],

            'source_execution_authorization' => [
                'decision_recorded'
                    => $execution->source_execution_authorization_decision_recorded,

                'made_by_authorized_human'
                    => $execution->source_execution_authorization_made_by_authorized_human,

                'attribution_complete'
                    => $execution->source_execution_authorization_attribution_complete,

                'authorization_completed'
                    => $execution->source_execution_authorization_completed,
            ],

            'execution_attribution' => [
                'executed_by' => $execution->executed_by,
                'executor_role' => $execution->executor_role,
                'executed_at' => $execution->executed_at,
                'execution_made_by_authorized_human'
                    => $execution->execution_made_by_authorized_human,
                'execution_attribution_complete'
                    => $execution->execution_attribution_complete,
            ],

            'guardrails' => $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_preparation_enabled' => true,

            'preparation_is_execution' => false,
            'preparation_authorizes_execution' => false,
            'preparation_executes_controlled_activation' => false,
            'preparation_activates_strategic_plan' => false,

            'preparation_changes_execution_authorization' => false,
            'preparation_completes_execution_authorization' => false,

            'preparation_resolves_conditions' => false,
            'preparation_removes_restrictions' => false,
            'preparation_validates_evidence' => false,

            'preparation_authorizes_ai_change' => false,
            'preparation_authorizes_deployment' => false,
            'preparation_authorizes_rollback' => false,
            'preparation_authorizes_clinical_action' => false,

            'automatic_execution_allowed' => false,
            'automatic_execution_authorization_allowed' => false,
            'automatic_activation_allowed' => false,
            'automatic_change_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,
            'automatic_clinical_action_allowed' => false,

            'execution_authorization_required_before_execution' => true,
            'execution_authorization_separate_from_execution' => true,
            'actual_execution_authority_reserved_for_authorized_human' => true,
            'authorized_human_execution_required' => true,
            'post_execution_human_validation_required' => true,

            'message' =>
                'Step 71.2 prepares a governed controlled activation execution registry record only. ' .
                'Preparation does not constitute execution authorization, actual controlled activation execution, ' .
                'strategic plan activation, AI/system modification, deployment, rollback, or clinical action. ' .
                'Actual execution remains a separate authority reserved for explicitly authorized human governance.',
        ];
    }
}