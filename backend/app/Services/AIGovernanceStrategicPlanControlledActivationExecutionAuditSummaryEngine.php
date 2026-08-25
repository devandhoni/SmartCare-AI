<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionAuditSummaryEngine
{
    public function analyze(?int $executionId = null): array
    {
        $execution = $executionId
            ? AIGovernanceStrategicPlanControlledActivationExecution::find($executionId)
            : AIGovernanceStrategicPlanControlledActivationExecution::latest('id')->first();

        if (!$execution) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_CONTROLLED_ACTIVATION_EXECUTION_AVAILABLE',
                'message' => 'No Step 71 controlled activation execution record is available for audit.',
            ];
        }

        $state = app(
            AIGovernanceStrategicPlanControlledActivationExecutionStateIntelligenceEngine::class
        )->analyze($execution->id);

        $conditionRestriction = app(
            AIGovernanceStrategicPlanControlledActivationExecutionConditionRestrictionIntelligenceEngine::class
        )->analyze($execution->id);

        $eligibilitySafety = app(
            AIGovernanceStrategicPlanControlledActivationExecutionEligibilitySafetyIntelligenceEngine::class
        )->analyze($execution->id);

        $recommendation = app(
            AIGovernanceStrategicPlanControlledActivationExecutionRecommendationIntelligenceEngine::class
        )->analyze($execution->id);

        $executive = app(
            AIGovernanceStrategicPlanControlledActivationExecutionExecutiveIntelligenceEngine::class
        )->analyze($execution->id);

        $executionState =
            $state['execution_state'] ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $eligibilityState =
            $eligibilitySafety['execution_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['execution_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $executiveState =
            $executive['executive_state'] ?? [];

        $sourceAuthorization =
            $state['source_execution_authorization_context'] ?? [];

        $executionAttribution =
            $state['execution_attribution_context'] ?? [];

        $stateGuardrails =
            $state['controlled_activation_execution_state_guardrails'] ?? [];

        $conditionGuardrails =
            $conditionRestriction['controlled_activation_execution_condition_restriction_guardrails'] ?? [];

        $eligibilityGuardrails =
            $eligibilitySafety['controlled_activation_execution_eligibility_safety_guardrails'] ?? [];

        $recommendationGuardrails =
            $recommendation['controlled_activation_execution_recommendation_guardrails'] ?? [];

        $executiveGuardrails =
            $executive['executive_controlled_activation_execution_guardrails'] ?? [];

        $sourceAuthorizationValid =
            (bool) ($sourceAuthorization['source_execution_authorization_valid'] ?? false);

        $sourceAuthorizationDecisionRecorded =
            (bool) ($sourceAuthorization['source_execution_authorization_decision_recorded'] ?? false);

        $sourceAuthorizationMadeByHuman =
            (bool) ($sourceAuthorization['source_execution_authorization_made_by_authorized_human'] ?? false);

        $sourceAuthorizationAttributionComplete =
            (bool) ($sourceAuthorization['source_execution_authorization_attribution_complete'] ?? false);

        $sourceAuthorizationCompleted =
            (bool) ($sourceAuthorization['source_execution_authorization_completed'] ?? false);

        $controlledActivationExecuted =
            (bool) ($executionAttribution['controlled_activation_executed'] ?? false);

        $executionAttributionComplete =
            (bool) ($executionAttribution['execution_attribution_complete'] ?? false);

        $blockingExecutionConditions =
            (int) ($conditionState['blocking_execution_conditions'] ?? 0);

        $criticalOpenConditions =
            (int) ($conditionState['critical_open_conditions'] ?? 0);

        $materialRestrictions =
            (int) ($conditionState['material_restrictions'] ?? 0);

        $criticalRestrictions =
            (int) ($conditionState['critical_restrictions'] ?? 0);

        $combinedResolutionScore =
            (float) ($conditionState['combined_resolution_score'] ?? 0);

        $combinedPressureScore =
            (float) ($conditionState['combined_condition_restriction_pressure_score'] ?? 0);

        $executionEligibilityScore =
            (float) ($eligibilityState['execution_eligibility_score'] ?? 0);

        $executionSafetyScore =
            (float) ($safetyState['execution_safety_score'] ?? 0);

        $governanceIntegrityIntact =
            (bool) ($safetyState['governance_integrity_intact'] ?? false);

        $checks = [];

        $checks['controlled_activation_execution_available'] = [
            'passed' => true,
            'message' => 'Controlled activation execution registry record is available.',
        ];

        $checks['execution_state_intelligence_available'] = [
            'passed' => (bool) ($state['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution state intelligence is available.',
        ];

        $checks['condition_restriction_intelligence_available'] = [
            'passed' => (bool) ($conditionRestriction['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution condition and restriction intelligence is available.',
        ];

        $checks['eligibility_safety_intelligence_available'] = [
            'passed' => (bool) ($eligibilitySafety['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution eligibility and safety intelligence is available.',
        ];

        $checks['recommendation_intelligence_available'] = [
            'passed' => (bool) ($recommendation['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution recommendation intelligence is available.',
        ];

        $checks['executive_intelligence_available'] = [
            'passed' => (bool) ($executive['analysis_completed'] ?? false),
            'message' => 'Executive controlled activation execution intelligence is available.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Controlled activation execution governance integrity remains intact.',
        ];

        $checks['critical_execution_condition_safety_response'] = [
            'passed' =>
                $criticalOpenConditions === 0
                || (
                    (bool) ($executionState['execution_progression_blocked'] ?? false)
                    && (bool) ($executionState['immediate_human_intervention_required'] ?? false)
                ),
            'value' => $criticalOpenConditions,
            'message' => 'Critical execution conditions must block progression and require authorized-human governance intervention.',
        ];

        $checks['critical_execution_restriction_safety_response'] = [
            'passed' =>
                $criticalRestrictions === 0
                || (
                    (bool) ($executionState['execution_progression_blocked'] ?? false)
                    && (bool) ($executionState['immediate_human_intervention_required'] ?? false)
                ),
            'value' => $criticalRestrictions,
            'message' => 'Critical execution restrictions must block unsafe progression and require authorized-human governance intervention.',
        ];

        $checks['automatic_execution_isolation'] = [
            'passed' =>
                !(bool) $execution->automatic_execution_allowed
                && !(bool) $execution->automatic_execution_authorization_allowed
                && !(bool) $execution->automatic_activation_allowed
                && !(bool) $execution->automatic_condition_resolution_allowed
                && !(bool) $execution->automatic_restriction_removal_allowed
                && !(bool) $execution->automatic_evidence_validation_allowed
                && !(bool) $execution->automatic_change_allowed
                && !(bool) $execution->automatic_deployment_allowed
                && !(bool) $execution->automatic_rollback_allowed
                && !(bool) $execution->automatic_clinical_action_allowed,
            'message' => 'Automatic execution, authorization, activation, condition resolution, restriction removal, evidence validation, AI/system change, deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $execution->human_review_required
                && (bool) $execution->governance_confirmation_required
                && (bool) $execution->controlled_activation_authorization_required
                && (bool) $execution->authorized_human_execution_authorization_required
                && (bool) $execution->authorized_human_execution_required
                && (bool) $execution->post_execution_human_validation_required,
            'message' => 'Human review, governance confirmation, controlled activation authorization, execution authorization, actual execution, and post-execution human validation requirements remain enabled.',
        ];

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['execution_state_intelligence_is_execution'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_is_execution_authorization'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_makes_execution_decision'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'Controlled activation execution state intelligence remains informational and isolated from execution authority.',
        ];

        $checks['condition_restriction_authority_isolation'] = [
            'passed' =>
                ($conditionGuardrails['condition_restriction_intelligence_is_execution'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_makes_execution_decision'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_executes_controlled_activation'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_resolves_conditions'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_removes_restrictions'] ?? true) === false,
            'message' => 'Condition and restriction intelligence remains advisory and isolated from execution, resolution, and restriction-removal authority.',
        ];

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilityGuardrails['eligibility_safety_intelligence_is_execution'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_is_execution_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_makes_execution_decision'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_executes_controlled_activation'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_score_authorizes_execution'] ?? true) === false
                && ($eligibilityGuardrails['safety_score_authorizes_execution'] ?? true) === false,
            'message' => 'Eligibility and safety intelligence remains informational and isolated from execution authority.',
        ];

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['recommendation_intelligence_is_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_makes_execution_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_executes_controlled_activation'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_score_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommended_path_authorizes_execution'] ?? true) === false,
            'message' => 'Recommendation intelligence remains advisory and isolated from controlled activation execution authority.',
        ];

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_execution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_is_execution_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_execution_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_executes_controlled_activation'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['executive_decision_posture_authorizes_execution'] ?? true) === false,
            'message' => 'Executive controlled activation execution intelligence remains informational and isolated from execution authority.',
        ];

        $checks['source_execution_authorization_sequence_integrity'] = [
            'passed' =>
                !$controlledActivationExecuted
                || (
                    $sourceAuthorizationDecisionRecorded
                    && $sourceAuthorizationMadeByHuman
                    && $sourceAuthorizationAttributionComplete
                    && $sourceAuthorizationCompleted
                    && $sourceAuthorizationValid
                ),
            'message' => 'Actual controlled activation execution must follow a valid completed explicitly authorized-human execution authorization.',
        ];

        $checks['execution_authorization_authority_reserved_for_authorized_human'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || $sourceAuthorizationMadeByHuman,
            'value' => $sourceAuthorizationCompleted,
            'message' => 'Completed controlled activation execution authorization must remain attributable to explicitly authorized human governance.',
        ];

        $checks['execution_authorization_attribution_integrity'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || $sourceAuthorizationAttributionComplete,
            'value' => $sourceAuthorizationCompleted,
            'message' => 'Completed execution authorization must include complete authorized-human authorizer attribution.',
        ];

        $checks['actual_execution_attribution_integrity'] = [
            'passed' =>
                !$controlledActivationExecuted
                || $executionAttributionComplete,
            'value' => $controlledActivationExecuted,
            'message' => 'Actual controlled activation execution must remain attributable to an identified authorized human executor.',
        ];

        $checks['execution_authorization_does_not_imply_execution'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || !$controlledActivationExecuted
                || $executionAttributionComplete,
            'message' => 'Execution authorization must remain separate from actual controlled activation execution.',
        ];

        $checks['actual_execution_authority_reserved_for_authorized_human'] = [
            'passed' =>
                !$controlledActivationExecuted
                || (
                    (bool) $execution->execution_made_by_authorized_human
                    && $executionAttributionComplete
                ),
            'value' => $controlledActivationExecuted,
            'message' => 'Actual controlled activation execution authority remains reserved for explicitly authorized human governance.',
        ];

        $checks['post_execution_validation_sequence_integrity'] = [
            'passed' =>
                !(bool) $execution->post_execution_validation_completed
                || $controlledActivationExecuted,
            'message' => 'Post-execution validation must not be completed before actual controlled activation execution.',
        ];

        $checks['post_execution_human_validation_control'] = [
            'passed' =>
                (bool) $execution->post_execution_human_validation_required,
            'message' => 'Post-execution human validation remains mandatory.',
        ];

        $checks['ai_change_execution_isolation'] = [
            'passed' =>
                !(bool) $execution->automatic_change_allowed
                && !(bool) $execution->automatic_deployment_allowed
                && !(bool) $execution->automatic_rollback_allowed
                && !(bool) $execution->automatic_clinical_action_allowed,
            'message' => 'Controlled activation execution intelligence does not authorize autonomous AI/system modification, deployment, rollback, or clinical action.',
        ];

        $checks['eligibility_authority_separation'] = [
            'passed' =>
                ($eligibilityState['authorized_human_controlled_activation_execution_review_eligibility'] ?? null)
                    !== 'EXECUTION_AUTHORIZED'
                && ($eligibilityGuardrails['eligibility_classification_authorizes_execution'] ?? true) === false,
            'message' => 'Eligibility for authorized-human review remains separate from actual execution authority.',
        ];

        $checks['executive_score_authority_separation'] = [
            'passed' =>
                ($executiveGuardrails['executive_score_authorizes_execution'] ?? true) === false,
            'message' => 'Executive scoring remains informational and cannot authorize controlled activation execution.',
        ];

        $checks['recommendation_path_authority_separation'] = [
            'passed' =>
                ($recommendationGuardrails['recommended_path_authorizes_execution'] ?? true) === false,
            'message' => 'Recommended execution paths remain advisory and cannot authorize controlled activation execution.',
        ];

        $passedChecks = collect($checks)
            ->filter(fn ($check) => ($check['passed'] ?? false) === true)
            ->count();

        $failedChecks = count($checks) - $passedChecks;

        $auditStatus =
            $failedChecks === 0
                ? 'COMPLETE'
                : 'FAILED';

        $managementStatus = match (true) {
            $failedChecks > 0
                => 'CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_CONTROL_FAILURE',

            !$governanceIntegrityIntact
                => 'CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_INTEGRITY_FAILURE',

            $criticalOpenConditions > 0
                || $criticalRestrictions > 0
                || $blockingExecutionConditions > 0
                || !$sourceAuthorizationValid
                => 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_WORK_REMAINS',

            default
                => 'CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_STABLE',
        };

        return [
            'audit_available' => true,

            'audit_status' => $auditStatus,

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'management_status'
                => $managementStatus,

            'audit_summary' => [
                'total_checks' => count($checks),
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
            ],

            'checks'
                => $checks,

            'execution_summary' => [
                'controlled_activation_execution_state'
                    => $executionState['controlled_activation_execution_state'] ?? null,

                'controlled_activation_execution_status'
                    => $executionState['controlled_activation_execution_status'] ?? null,

                'execution_readiness'
                    => $executionState['execution_readiness'] ?? null,

                'execution_readiness_score'
                    => $executionState['execution_readiness_score'] ?? null,

                'execution_risk_level'
                    => $executionState['execution_risk_level'] ?? null,

                'execution_risk_score'
                    => $executionState['execution_risk_score'] ?? null,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,

                'post_execution_status'
                    => $executionState['post_execution_status'] ?? null,

                'post_execution_validation_completed'
                    => (bool) $execution->post_execution_validation_completed,
            ],

            'condition_restriction_summary' => [
                'blocking_execution_conditions'
                    => $blockingExecutionConditions,

                'critical_open_conditions'
                    => $criticalOpenConditions,

                'material_restrictions'
                    => $materialRestrictions,

                'critical_restrictions'
                    => $criticalRestrictions,

                'combined_resolution_score'
                    => $combinedResolutionScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,
            ],

            'eligibility_safety_summary' => [
                'authorized_human_controlled_activation_execution_review_eligibility'
                    => $eligibilityState['authorized_human_controlled_activation_execution_review_eligibility'] ?? null,

                'controlled_activation_execution_completion_eligibility'
                    => $eligibilityState['controlled_activation_execution_completion_eligibility'] ?? null,

                'unrestricted_controlled_activation_execution_eligibility'
                    => $eligibilityState['unrestricted_controlled_activation_execution_eligibility'] ?? null,

                'execution_eligibility_score'
                    => $executionEligibilityScore,

                'execution_safety_status'
                    => $safetyState['execution_safety_status'] ?? null,

                'execution_safety_level'
                    => $safetyState['execution_safety_level'] ?? null,

                'execution_safety_score'
                    => $executionSafetyScore,

                'governance_integrity_intact'
                    => $governanceIntegrityIntact,
            ],

            'recommendation_summary' => [
                'recommendation_status'
                    => $recommendationState['recommendation_status'] ?? null,

                'total_recommendations'
                    => $recommendationState['total_recommendations'] ?? 0,

                'critical_recommendations'
                    => $recommendationState['critical_recommendations'] ?? 0,

                'high_recommendations'
                    => $recommendationState['high_recommendations'] ?? 0,

                'top_recommendation_code'
                    => $recommendationState['top_recommendation_code'] ?? null,

                'top_recommended_execution_path'
                    => $recommendationState['top_recommended_execution_path'] ?? null,
            ],

            'executive_summary' => [
                'executive_controlled_activation_execution_status'
                    => $executiveState['executive_controlled_activation_execution_status'] ?? null,

                'executive_controlled_activation_execution_readiness'
                    => $executiveState['executive_controlled_activation_execution_readiness'] ?? null,

                'executive_controlled_activation_execution_confidence'
                    => $executiveState['executive_controlled_activation_execution_confidence'] ?? null,

                'executive_controlled_activation_execution_score'
                    => $executiveState['executive_controlled_activation_execution_score'] ?? null,

                'executive_decision_posture'
                    => $executiveState['executive_decision_posture'] ?? null,

                'human_management_attention_required'
                    => $executiveState['human_management_attention_required'] ?? null,

                'immediate_human_intervention_required'
                    => $executiveState['immediate_human_intervention_required'] ?? null,
            ],

            'authorization_execution_summary' => [
                'source_execution_authorization_decision_recorded'
                    => $sourceAuthorizationDecisionRecorded,

                'source_execution_authorization_made_by_authorized_human'
                    => $sourceAuthorizationMadeByHuman,

                'source_execution_authorization_attribution_complete'
                    => $sourceAuthorizationAttributionComplete,

                'source_execution_authorization_completed'
                    => $sourceAuthorizationCompleted,

                'source_execution_authorization_valid'
                    => $sourceAuthorizationValid,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,

                'post_execution_validation_completed'
                    => (bool) $execution->post_execution_validation_completed,
            ],

            'integrity_summary' => [
                'governance_integrity_intact'
                    => $governanceIntegrityIntact,

                'automatic_execution_allowed'
                    => (bool) $execution->automatic_execution_allowed,

                'automatic_execution_authorization_allowed'
                    => (bool) $execution->automatic_execution_authorization_allowed,

                'automatic_activation_allowed'
                    => (bool) $execution->automatic_activation_allowed,

                'automatic_condition_resolution_allowed'
                    => (bool) $execution->automatic_condition_resolution_allowed,

                'automatic_restriction_removal_allowed'
                    => (bool) $execution->automatic_restriction_removal_allowed,

                'automatic_evidence_validation_allowed'
                    => (bool) $execution->automatic_evidence_validation_allowed,

                'automatic_change_allowed'
                    => (bool) $execution->automatic_change_allowed,

                'automatic_deployment_allowed'
                    => (bool) $execution->automatic_deployment_allowed,

                'automatic_rollback_allowed'
                    => (bool) $execution->automatic_rollback_allowed,

                'automatic_clinical_action_allowed'
                    => (bool) $execution->automatic_clinical_action_allowed,

                'human_review_required'
                    => (bool) $execution->human_review_required,

                'governance_confirmation_required'
                    => (bool) $execution->governance_confirmation_required,

                'controlled_activation_authorization_required'
                    => (bool) $execution->controlled_activation_authorization_required,

                'authorized_human_execution_authorization_required'
                    => (bool) $execution->authorized_human_execution_authorization_required,

                'authorized_human_execution_required'
                    => (bool) $execution->authorized_human_execution_required,

                'post_execution_human_validation_required'
                    => (bool) $execution->post_execution_human_validation_required,
            ],

            'audit_findings' => [
                "Step 71 controlled activation execution audit completed ".count($checks)." integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
                "Controlled activation execution audit is based on execution record {$execution->id}.",
                'Current controlled activation execution state is '.($executionState['controlled_activation_execution_state'] ?? 'UNKNOWN').'.',
                'Current execution readiness is '.($executionState['execution_readiness'] ?? 'UNKNOWN').' with score '.($executionState['execution_readiness_score'] ?? 0).'.',
                'Current execution risk is '.($executionState['execution_risk_level'] ?? 'UNKNOWN').' with score '.($executionState['execution_risk_score'] ?? 0).'.',
                "Current execution eligibility score is {$executionEligibilityScore}.",
                'Current execution safety status is '.($safetyState['execution_safety_status'] ?? 'UNKNOWN')." with score {$executionSafetyScore}.",
                "{$blockingExecutionConditions} controlled activation execution blocker(s) remain.",
                "{$criticalOpenConditions} critical controlled activation execution condition(s) remain open.",
                "{$materialRestrictions} material controlled activation execution restriction(s) remain.",
                "{$criticalRestrictions} critical controlled activation execution restriction(s) remain.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                'Current recommendation status is '.($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',
                'Top recommendation is '.($recommendationState['top_recommendation_code'] ?? 'NONE').'.',
                'Current executive controlled activation execution status is '.($executiveState['executive_controlled_activation_execution_status'] ?? 'UNKNOWN').'.',
                'Source execution authorization valid is '.($sourceAuthorizationValid ? 'YES' : 'NO').'.',
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Execution attribution complete is '.($executionAttributionComplete ? 'YES' : 'NO').'.',
                'Controlled activation execution governance integrity remains '.($governanceIntegrityIntact ? 'INTACT' : 'VIOLATED').'.',
                $failedChecks === 0
                    ? 'Step 71 controlled activation execution integrity controls contain no audit failures.'
                    : "Step 71 controlled activation execution integrity controls contain {$failedChecks} failure(s).",
            ],

            'management_priorities'
                => array_values(array_unique(array_merge(
                    $executive['executive_priorities'] ?? [],
                    $recommendation['management_priorities'] ?? [],
                    [
                        'Keep controlled activation execution blocked until valid explicitly authorized-human execution authorization exists.',
                        'Keep actual controlled activation execution separately governed from execution authorization.',
                        'Preserve authorized-human executor attribution if controlled activation execution occurs.',
                        'Preserve mandatory post-execution human validation.',
                        'Preserve strict separation between intelligence, authorization, execution, deployment, rollback, AI/system modification, and clinical action.',
                    ]
                ))),

            'audit_guardrails'
                => $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_audit_enabled'
                => true,

            'audit_is_execution'
                => false,

            'audit_is_execution_authorization'
                => false,

            'audit_makes_execution_decision'
                => false,

            'audit_records_execution_decision'
                => false,

            'audit_executes_controlled_activation'
                => false,

            'audit_activates_strategic_plan'
                => false,

            'audit_changes_execution_authorization'
                => false,

            'audit_changes_execution_status'
                => false,

            'audit_changes_execution_outcome'
                => false,

            'audit_resolves_conditions'
                => false,

            'audit_waives_conditions'
                => false,

            'audit_removes_restrictions'
                => false,

            'audit_downgrades_restrictions'
                => false,

            'audit_validates_evidence'
                => false,

            'audit_completes_execution_attribution'
                => false,

            'audit_completes_post_execution_validation'
                => false,

            'audit_authorizes_ai_change'
                => false,

            'audit_authorizes_execution'
                => false,

            'audit_authorizes_deployment'
                => false,

            'audit_authorizes_rollback'
                => false,

            'audit_authorizes_clinical_action'
                => false,

            'automatic_execution_allowed'
                => false,

            'automatic_execution_authorization_allowed'
                => false,

            'automatic_activation_allowed'
                => false,

            'automatic_condition_resolution_allowed'
                => false,

            'automatic_restriction_removal_allowed'
                => false,

            'automatic_evidence_validation_allowed'
                => false,

            'automatic_change_allowed'
                => false,

            'automatic_deployment_allowed'
                => false,

            'automatic_rollback_allowed'
                => false,

            'automatic_clinical_action_allowed'
                => false,

            'execution_authorization_required_before_execution'
                => true,

            'execution_authorization_separate_from_execution'
                => true,

            'actual_execution_authority_reserved_for_authorized_human'
                => true,

            'post_execution_human_validation_required'
                => true,

            'message' =>
                'Step 71.8 controlled activation execution audit consolidates preparation, execution-state intelligence, condition and restriction intelligence, eligibility and safety intelligence, recommendation intelligence, executive intelligence, execution-authorization sequencing, execution attribution, post-execution validation controls, governance integrity, and authority-isolation controls. The audit does not authorize or perform controlled activation execution, resolve or waive conditions, remove or downgrade restrictions, validate evidence, complete attribution, modify AI/system behavior, deploy changes, trigger rollback, or initiate clinical action. Actual controlled activation execution remains separately reserved for explicitly authorized human governance.',
        ];
    }
}