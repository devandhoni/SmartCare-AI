<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionFinalValidationEngine
{
    public function analyze(?int $executionId = null): array
    {
        $execution = $executionId
            ? AIGovernanceStrategicPlanControlledActivationExecution::find($executionId)
            : AIGovernanceStrategicPlanControlledActivationExecution::latest('id')->first();

        if (!$execution) {
            return [
                'validation_status' => 'FAILED',
                'step_71_ready_for_closure' => false,
                'message' => 'No Step 71 controlled activation execution record is available for final validation.',
                'critical_issues' => [
                    'Controlled activation execution registry record is unavailable.',
                ],
            ];
        }

        $preparation = app(
            AIGovernanceStrategicPlanControlledActivationExecutionPreparationEngine::class
        )->analyze($execution->strategic_plan_controlled_activation_execution_authorization_id);

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

        $audit = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuditSummaryEngine::class
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

        $auditGuardrails =
            $audit['audit_guardrails'] ?? [];

        $sourceAuthorizationDecisionRecorded =
            (bool) ($sourceAuthorization['source_execution_authorization_decision_recorded'] ?? false);

        $sourceAuthorizationMadeByHuman =
            (bool) ($sourceAuthorization['source_execution_authorization_made_by_authorized_human'] ?? false);

        $sourceAuthorizationAttributionComplete =
            (bool) ($sourceAuthorization['source_execution_authorization_attribution_complete'] ?? false);

        $sourceAuthorizationCompleted =
            (bool) ($sourceAuthorization['source_execution_authorization_completed'] ?? false);

        $sourceAuthorizationValid =
            (bool) ($sourceAuthorization['source_execution_authorization_valid'] ?? false);

        $controlledActivationExecuted =
            (bool) ($executionAttribution['controlled_activation_executed'] ?? false);

        $executionAttributionComplete =
            (bool) ($executionAttribution['execution_attribution_complete'] ?? false);

        $postExecutionValidationCompleted =
            (bool) $execution->post_execution_validation_completed;

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

        $checks['execution_preparation_operational'] = [
            'passed' => (bool) ($preparation['preparation_completed'] ?? false),
            'message' => 'Controlled activation execution preparation is operational.',
        ];

        $checks['execution_state_intelligence_operational'] = [
            'passed' => (bool) ($state['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution state intelligence is operational.',
        ];

        $checks['condition_restriction_intelligence_operational'] = [
            'passed' => (bool) ($conditionRestriction['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution condition and restriction intelligence is operational.',
        ];

        $checks['eligibility_safety_intelligence_operational'] = [
            'passed' => (bool) ($eligibilitySafety['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution eligibility and safety intelligence is operational.',
        ];

        $checks['recommendation_intelligence_operational'] = [
            'passed' => (bool) ($recommendation['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution recommendation intelligence is operational.',
        ];

        $checks['executive_intelligence_operational'] = [
            'passed' => (bool) ($executive['analysis_completed'] ?? false),
            'message' => 'Executive controlled activation execution intelligence is operational.',
        ];

        $checks['step_71_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (int) ($audit['audit_summary']['failed_checks'] ?? 1) === 0,
            'message' => 'Step 71 controlled activation execution audit completed without integrity failures.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Controlled activation execution governance integrity remains intact.',
        ];

        $checks['critical_execution_conditions_safely_governed'] = [
            'passed' =>
                $criticalOpenConditions === 0
                || (
                    (bool) ($executionState['execution_progression_blocked'] ?? false)
                    && (bool) ($executionState['immediate_human_intervention_required'] ?? false)
                ),
            'value' => $criticalOpenConditions,
            'message' => 'Critical controlled activation execution conditions must safely block progression and require authorized-human governance intervention.',
        ];

        $checks['critical_execution_restrictions_safely_governed'] = [
            'passed' =>
                $criticalRestrictions === 0
                || (
                    (bool) ($executionState['execution_progression_blocked'] ?? false)
                    && (bool) ($executionState['immediate_human_intervention_required'] ?? false)
                ),
            'value' => $criticalRestrictions,
            'message' => 'Critical controlled activation execution restrictions must safely block unsafe progression.',
        ];

        $checks['automatic_authority_isolation'] = [
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
            'message' => 'Automatic authorization, execution, activation, condition resolution, restriction removal, evidence validation, AI/system change, deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $execution->human_review_required
                && (bool) $execution->governance_confirmation_required
                && (bool) $execution->controlled_activation_authorization_required
                && (bool) $execution->authorized_human_execution_authorization_required
                && (bool) $execution->authorized_human_execution_required
                && (bool) $execution->post_execution_human_validation_required,
            'message' => 'Human review, governance confirmation, activation authorization, execution authorization, actual execution, and post-execution human validation remain mandatory.',
        ];

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['execution_state_intelligence_is_execution'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_is_execution_authorization'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_makes_execution_decision'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'State intelligence remains informational and isolated from actual execution authority.',
        ];

        $checks['condition_restriction_authority_isolation'] = [
            'passed' =>
                ($conditionGuardrails['condition_restriction_intelligence_is_execution'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_makes_execution_decision'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_executes_controlled_activation'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_resolves_conditions'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_removes_restrictions'] ?? true) === false,
            'message' => 'Condition and restriction intelligence remains advisory and isolated from execution authority.',
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
            'message' => 'Recommendation intelligence remains advisory and isolated from execution authority.',
        ];

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_execution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_is_execution_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_execution_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_executes_controlled_activation'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['executive_decision_posture_authorizes_execution'] ?? true) === false,
            'message' => 'Executive intelligence remains informational and isolated from controlled activation execution authority.',
        ];

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_execution'] ?? true) === false
                && ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
                && ($auditGuardrails['audit_makes_execution_decision'] ?? true) === false
                && ($auditGuardrails['audit_executes_controlled_activation'] ?? true) === false
                && ($auditGuardrails['audit_authorizes_execution'] ?? true) === false,
            'message' => 'Step 71 audit remains informational and isolated from execution authority.',
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
            'message' => 'Actual controlled activation execution must follow valid completed explicitly authorized-human execution authorization.',
        ];

        $checks['execution_authorization_authority_protected'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || $sourceAuthorizationMadeByHuman,
            'value' => $sourceAuthorizationCompleted,
            'message' => 'Execution authorization authority remains reserved for explicitly authorized human governance.',
        ];

        $checks['execution_authorization_attribution_integrity'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || $sourceAuthorizationAttributionComplete,
            'value' => $sourceAuthorizationCompleted,
            'message' => 'Completed execution authorization must remain attributable to an identified authorized human governance authorizer.',
        ];

        $checks['actual_execution_sequence_integrity'] = [
            'passed' =>
                !$controlledActivationExecuted
                || $sourceAuthorizationValid,
            'message' => 'Actual controlled activation execution must follow valid explicitly authorized-human execution authorization.',
        ];

        $checks['actual_execution_attribution_integrity'] = [
            'passed' =>
                !$controlledActivationExecuted
                || $executionAttributionComplete,
            'value' => $controlledActivationExecuted,
            'message' => 'Actual controlled activation execution must remain attributable to an identified authorized human executor.',
        ];

        $checks['authorization_execution_sequence_integrity'] = [
            'passed' =>
                !$controlledActivationExecuted
                || $sourceAuthorizationCompleted,
            'message' => 'Execution authorization and actual controlled activation execution remain separate governed authority stages.',
        ];

        $checks['authorization_does_not_imply_execution'] = [
            'passed' =>
                !$sourceAuthorizationCompleted
                || !$controlledActivationExecuted
                || $executionAttributionComplete,
            'message' => 'Completed execution authorization must not automatically or implicitly perform controlled activation execution.',
        ];

        $checks['actual_execution_authority_protected'] = [
            'passed' =>
                !$controlledActivationExecuted
                || (
                    (bool) $execution->execution_made_by_authorized_human
                    && $executionAttributionComplete
                ),
            'value' => $controlledActivationExecuted,
            'message' => 'Actual controlled activation execution authority remains separately reserved for explicitly authorized human governance.',
        ];

        $checks['post_execution_validation_sequence_integrity'] = [
            'passed' =>
                !$postExecutionValidationCompleted
                || $controlledActivationExecuted,
            'message' => 'Post-execution validation must occur only after actual controlled activation execution.',
        ];

        $checks['post_execution_human_validation_required'] = [
            'passed' =>
                (bool) $execution->post_execution_human_validation_required,
            'message' => 'Post-execution human validation remains mandatory.',
        ];

        $checks['ai_change_execution_clinical_isolation'] = [
            'passed' =>
                !(bool) $execution->automatic_change_allowed
                && !(bool) $execution->automatic_deployment_allowed
                && !(bool) $execution->automatic_rollback_allowed
                && !(bool) $execution->automatic_clinical_action_allowed,
            'message' => 'Step 71 does not authorize autonomous AI/system modification, deployment, rollback, or clinical action.',
        ];

        $passedChecks = collect($checks)
            ->filter(fn ($check) => ($check['passed'] ?? false) === true)
            ->count();

        $failedChecks =
            count($checks) - $passedChecks;

        $criticalIssues = collect($checks)
            ->filter(fn ($check) => !($check['passed'] ?? false))
            ->map(fn ($check, $key) => [
                'check' => $key,
                'message' => $check['message'] ?? 'Validation check failed.',
            ])
            ->values()
            ->all();

        $warnings = [];

        if (!$sourceAuthorizationDecisionRecorded) {
            $warnings[] =
                'Authorized-human controlled activation execution authorization decision has not yet been recorded.';
        }

        if (!$sourceAuthorizationMadeByHuman) {
            $warnings[] =
                'Controlled activation execution authorization has not yet been made by an explicitly authorized human governance authority.';
        }

        if (!$sourceAuthorizationAttributionComplete) {
            $warnings[] =
                'Controlled activation execution authorization attribution remains incomplete.';
        }

        if (!$sourceAuthorizationCompleted) {
            $warnings[] =
                'Controlled activation execution authorization has not yet been completed.';
        }

        if (!$sourceAuthorizationValid) {
            $warnings[] =
                'No valid completed controlled activation execution authorization is currently present.';
        }

        if ($blockingExecutionConditions > 0) {
            $warnings[] =
                "{$blockingExecutionConditions} controlled activation execution blocker(s) remain active.";
        }

        if ($criticalOpenConditions > 0) {
            $warnings[] =
                "{$criticalOpenConditions} critical controlled activation execution condition(s) remain active and are correctly blocking progression.";
        }

        if ($materialRestrictions > 0) {
            $warnings[] =
                "{$materialRestrictions} material controlled activation execution restriction(s) remain active.";
        }

        if ($criticalRestrictions > 0) {
            $warnings[] =
                "{$criticalRestrictions} critical controlled activation execution restriction(s) remain active and are correctly blocking unsafe progression.";
        }

        if (($executionState['execution_risk_score'] ?? 0) >= 90) {
            $warnings[] =
                'Controlled activation execution risk remains '
                .($executionState['execution_risk_level'] ?? 'CRITICAL')
                .' with score '
                .($executionState['execution_risk_score'] ?? 0)
                .'.';
        }

        if (!$controlledActivationExecuted) {
            $warnings[] =
                'Controlled activation has not been executed.';
        }

        if (!$executionAttributionComplete) {
            $warnings[] =
                'Actual controlled activation execution attribution is not present because execution has not occurred.';
        }

        if (!$postExecutionValidationCompleted) {
            $warnings[] =
                'Post-execution validation has not been completed because controlled activation has not been executed.';
        }

        if (
            ($eligibilityState['controlled_activation_execution_completion_eligibility'] ?? null)
            !== 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION'
        ) {
            $warnings[] =
                'Controlled activation execution is not currently eligible for completion.';
        }

        $validationStatus = match (true) {
            $failedChecks > 0
                => 'FAILED',

            count($warnings) > 0
                => 'PASSED_WITH_WARNINGS',

            default
                => 'PASSED',
        };

        $step71ReadyForClosure =
            $failedChecks === 0
            && $governanceIntegrityIntact
            && ($audit['audit_status'] ?? null) === 'COMPLETE';

        return [
            'validation_status'
                => $validationStatus,

            'step_71_ready_for_closure'
                => $step71ReadyForClosure,

            'controlled_activation_execution_mode'
                => 'AUTHORIZED_HUMAN_GOVERNED_CONTROLLED_ACTIVATION_EXECUTION_INTELLIGENCE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'strategic_plan_controlled_activation_execution_authorization_id'
                => $execution->strategic_plan_controlled_activation_execution_authorization_id,

            'strategic_plan_controlled_activation_authorization_id'
                => $execution->strategic_plan_controlled_activation_authorization_id,

            'strategic_plan_governance_decision_confirmation_id'
                => $execution->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id'
                => $execution->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id'
                => $execution->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id'
                => $execution->strategic_plan_human_decision_id,

            'strategic_plan_decision_id'
                => $execution->strategic_plan_decision_id,

            'strategic_plan_id'
                => $execution->strategic_plan_id,

            'strategic_snapshot_id'
                => $execution->strategic_snapshot_id,

            'operational_snapshot_id'
                => $execution->operational_snapshot_id,

            'lifecycle_snapshot_id'
                => $execution->lifecycle_snapshot_id,

            'decision_scope'
                => $execution->decision_scope,

            'resident_id'
                => $execution->resident_id,

            'completion_message' =>
                $step71ReadyForClosure
                    ? 'Step 71 AI Governance Strategic Plan Controlled Activation Execution Intelligence has passed final architecture validation with governed operational warnings. Actual controlled activation execution remains subject to valid explicitly authorized human execution authorization and separate human execution authority.'
                    : 'Step 71 AI Governance Strategic Plan Controlled Activation Execution Intelligence has not passed final architecture validation.',

            'validation_summary' => [
                'total_checks'
                    => count($checks),

                'passed_checks'
                    => $passedChecks,

                'failed_checks'
                    => $failedChecks,

                'warning_count'
                    => count($warnings),

                'critical_issue_count'
                    => count($criticalIssues),
            ],

            'checks'
                => $checks,

            'controlled_activation_execution_context' => [
                'controlled_activation_execution_state'
                    => $executionState['controlled_activation_execution_state'] ?? null,

                'controlled_activation_execution_status'
                    => $executionState['controlled_activation_execution_status'] ?? null,

                'controlled_activation_execution_mode'
                    => $executionState['controlled_activation_execution_mode'] ?? null,

                'execution_readiness'
                    => $executionState['execution_readiness'] ?? null,

                'execution_readiness_score'
                    => $executionState['execution_readiness_score'] ?? null,

                'execution_risk_level'
                    => $executionState['execution_risk_level'] ?? null,

                'execution_risk_score'
                    => $executionState['execution_risk_score'] ?? null,

                'execution_eligibility_score'
                    => $executionEligibilityScore,

                'execution_safety_status'
                    => $safetyState['execution_safety_status'] ?? null,

                'execution_safety_score'
                    => $executionSafetyScore,

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
                    => $postExecutionValidationCompleted,
            ],

            'architecture_summary' => [
                '71.1_controlled_activation_execution_registry' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_controlled_activation_execution_id'
                        => $execution->id,
                ],

                '71.2_controlled_activation_execution_preparation' => [
                    'status' =>
                        ($preparation['preparation_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'controlled_activation_execution_code'
                        => $execution->controlled_activation_execution_code,

                    'controlled_activation_execution_status'
                        => $execution->controlled_activation_execution_status,
                ],

                '71.3_controlled_activation_execution_state_intelligence' => [
                    'status' =>
                        ($state['analysis_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'controlled_activation_execution_state'
                        => $executionState['controlled_activation_execution_state'] ?? null,

                    'execution_readiness'
                        => $executionState['execution_readiness'] ?? null,

                    'execution_readiness_score'
                        => $executionState['execution_readiness_score'] ?? null,

                    'execution_risk_level'
                        => $executionState['execution_risk_level'] ?? null,

                    'execution_risk_score'
                        => $executionState['execution_risk_score'] ?? null,
                ],

                '71.4_condition_restriction_intelligence' => [
                    'status' =>
                        ($conditionRestriction['analysis_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'state'
                        => $conditionState['state'] ?? null,

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

                '71.5_eligibility_safety_intelligence' => [
                    'status' =>
                        ($eligibilitySafety['analysis_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'controlled_activation_execution_completion_eligibility'
                        => $eligibilityState['controlled_activation_execution_completion_eligibility'] ?? null,

                    'execution_eligibility_score'
                        => $executionEligibilityScore,

                    'execution_safety_status'
                        => $safetyState['execution_safety_status'] ?? null,

                    'execution_safety_level'
                        => $safetyState['execution_safety_level'] ?? null,

                    'execution_safety_score'
                        => $executionSafetyScore,
                ],

                '71.6_recommendation_intelligence' => [
                    'status' =>
                        ($recommendation['analysis_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'recommendation_status'
                        => $recommendationState['recommendation_status'] ?? null,

                    'total_recommendations'
                        => $recommendationState['total_recommendations'] ?? 0,

                    'critical_recommendations'
                        => $recommendationState['critical_recommendations'] ?? 0,

                    'top_recommendation_code'
                        => $recommendationState['top_recommendation_code'] ?? null,

                    'top_recommended_execution_path'
                        => $recommendationState['top_recommended_execution_path'] ?? null,
                ],

                '71.7_executive_controlled_activation_execution_intelligence' => [
                    'status' =>
                        ($executive['analysis_completed'] ?? false)
                            ? 'OPERATIONAL'
                            : 'FAILED',

                    'executive_controlled_activation_execution_status'
                        => $executiveState['executive_controlled_activation_execution_status'] ?? null,

                    'executive_controlled_activation_execution_readiness'
                        => $executiveState['executive_controlled_activation_execution_readiness'] ?? null,

                    'executive_controlled_activation_execution_score'
                        => $executiveState['executive_controlled_activation_execution_score'] ?? null,

                    'executive_decision_posture'
                        => $executiveState['executive_decision_posture'] ?? null,
                ],

                '71.8_controlled_activation_execution_audit' => [
                    'status'
                        => $audit['audit_status'] ?? 'FAILED',

                    'passed_checks'
                        => $audit['audit_summary']['passed_checks'] ?? 0,

                    'failed_checks'
                        => $audit['audit_summary']['failed_checks'] ?? 0,

                    'management_status'
                        => $audit['management_status'] ?? null,
                ],

                '71.9_final_validation' => [
                    'status'
                        => $validationStatus,

                    'step_71_ready_for_closure'
                        => $step71ReadyForClosure,
                ],
            ],

            'warnings'
                => $warnings,

            'critical_issues'
                => $criticalIssues,

            'governance_findings' => [
                'The complete Step 71 AI Governance Strategic Plan Controlled Activation Execution Intelligence architecture has been validated.',
                'Current controlled activation execution state is '.($executionState['controlled_activation_execution_state'] ?? 'UNKNOWN').'.',
                'Current execution readiness is '.($executionState['execution_readiness'] ?? 'UNKNOWN').' with score '.($executionState['execution_readiness_score'] ?? 0).'.',
                'Current execution risk is '.($executionState['execution_risk_level'] ?? 'UNKNOWN').' with score '.($executionState['execution_risk_score'] ?? 0).'.',
                "{$blockingExecutionConditions} controlled activation execution blocker(s) remain.",
                "{$criticalOpenConditions} critical controlled activation execution condition(s) remain.",
                "{$materialRestrictions} material controlled activation execution restriction(s) remain.",
                "{$criticalRestrictions} critical controlled activation execution restriction(s) remain.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                'Current controlled activation execution completion eligibility is '.($eligibilityState['controlled_activation_execution_completion_eligibility'] ?? 'UNKNOWN').'.',
                'Current execution safety status is '.($safetyState['execution_safety_status'] ?? 'UNKNOWN')." with score {$executionSafetyScore}.",
                'Current recommendation status is '.($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',
                'Current executive controlled activation execution status is '.($executiveState['executive_controlled_activation_execution_status'] ?? 'UNKNOWN').'.',
                'Current executive controlled activation execution readiness is '.($executiveState['executive_controlled_activation_execution_readiness'] ?? 'UNKNOWN').' with score '.($executiveState['executive_controlled_activation_execution_score'] ?? 0).'.',
                'Source execution authorization decision recorded is '.($sourceAuthorizationDecisionRecorded ? 'YES' : 'NO').'.',
                'Source execution authorization made by authorized human is '.($sourceAuthorizationMadeByHuman ? 'YES' : 'NO').'.',
                'Source execution authorization completed is '.($sourceAuthorizationCompleted ? 'YES' : 'NO').'.',
                'Source execution authorization valid is '.($sourceAuthorizationValid ? 'YES' : 'NO').'.',
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Execution attribution complete is '.($executionAttributionComplete ? 'YES' : 'NO').'.',
                'Post-execution validation completed is '.($postExecutionValidationCompleted ? 'YES' : 'NO').'.',
                'Controlled activation execution intelligence remains advisory, informational, human governed, and isolated from autonomous execution authority.',
                'No autonomous controlled activation execution, AI/system modification, deployment, rollback, or clinical-action pathway is enabled.',
            ],

            'step_71_guardrails'
                => $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_intelligence_enabled'
                => true,

            'controlled_activation_execution_registry_enabled'
                => true,

            'controlled_activation_execution_preparation_enabled'
                => true,

            'controlled_activation_execution_state_intelligence_enabled'
                => true,

            'controlled_activation_execution_condition_restriction_intelligence_enabled'
                => true,

            'controlled_activation_execution_eligibility_safety_intelligence_enabled'
                => true,

            'controlled_activation_execution_recommendation_intelligence_enabled'
                => true,

            'executive_controlled_activation_execution_intelligence_enabled'
                => true,

            'controlled_activation_execution_audit_enabled'
                => true,

            'autonomous_controlled_activation_execution_enabled'
                => false,

            'autonomous_strategic_plan_activation_enabled'
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
                'Step 71 establishes explicitly authorized-human-governed strategic plan controlled activation execution intelligence. The architecture may prepare an execution record and evaluate execution-authorization dependencies, execution state, conditions, restrictions, evidence, readiness, risk, eligibility, safety, recommendations, executive state, executor attribution, post-execution validation state, and governance-control integrity. It does not autonomously authorize or perform controlled activation execution, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, modify AI/system behavior, deploy updates, trigger rollback, or initiate clinical action. Actual controlled activation execution remains separately reserved for explicitly authorized human governance and requires post-execution human validation.',
        ];
    }
}