<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationFinalValidationEngine
{
    /**
     * Step 70.9
     *
     * Final Controlled Activation Execution Authorization Architecture Validation.
     *
     * This validates architecture integrity only.
     * It does not authorize execution or perform controlled activation.
     */
    public function analyze(?int $executionAuthorizationId = null): array
    {
        $authorization = $executionAuthorizationId
            ? AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::find($executionAuthorizationId)
            : AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'validation_status' => 'FAILED',
                'step_70_ready_for_closure' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_AVAILABLE',
                'message' => 'No controlled activation execution authorization record is available for Step 70 final validation.',
            ];
        }

        $state = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationStateIntelligenceEngine::class
        )->analyze($authorization->id);

        $conditionRestriction = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationConditionRestrictionIntelligenceEngine::class
        )->analyze($authorization->id);

        $eligibilitySafety = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationEligibilitySafetyIntelligenceEngine::class
        )->analyze($authorization->id);

        $recommendation = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationRecommendationIntelligenceEngine::class
        )->analyze($authorization->id);

        $executive = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationExecutiveIntelligenceEngine::class
        )->analyze($authorization->id);

        $audit = app(
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationAuditSummaryEngine::class
        )->analyze($authorization->id);

        $executionState = $state['execution_authorization_state'] ?? [];
        $conditionState = $conditionRestriction['condition_restriction_state'] ?? [];
        $conditionSummary = $conditionRestriction['condition_summary'] ?? [];
        $restrictionSummary = $conditionRestriction['restriction_summary'] ?? [];
        $eligibilityState = $eligibilitySafety['execution_eligibility_state'] ?? [];
        $safetyState = $eligibilitySafety['execution_safety_state'] ?? [];
        $recommendationState = $recommendation['recommendation_state'] ?? [];
        $executiveState = $executive['executive_state'] ?? [];

        $sourceContext = $state['source_activation_authorization_context'] ?? [];
        $executionAuthorizationContext = $state['execution_authorization_context'] ?? [];
        $actualExecutionContext = $state['actual_execution_context'] ?? [];

        $stateGuardrails = $state['execution_authorization_state_guardrails'] ?? [];
        $conditionGuardrails = $conditionRestriction['execution_authorization_condition_restriction_guardrails'] ?? [];
        $eligibilityGuardrails = $eligibilitySafety['execution_authorization_eligibility_safety_guardrails'] ?? [];
        $recommendationGuardrails = $recommendation['execution_authorization_recommendation_guardrails'] ?? [];
        $executiveGuardrails = $executive['executive_controlled_activation_execution_authorization_guardrails'] ?? [];
        $auditGuardrails = $audit['audit_guardrails'] ?? [];

        $blockingExecutionAuthorizationConditions = (int) ($conditionSummary['blocking_execution_authorization_conditions'] ?? 0);
        $blockingActualExecutionConditions = (int) ($conditionSummary['blocking_actual_execution_conditions'] ?? 0);
        $criticalOpenConditions = (int) ($conditionSummary['critical_open_conditions'] ?? 0);
        $materialRestrictions = (int) ($restrictionSummary['material_restrictions'] ?? 0);
        $criticalRestrictions = (int) ($restrictionSummary['critical_restrictions'] ?? 0);

        $executionReadinessScore = $this->score($executionState['execution_readiness_score'] ?? 0);
        $executionRiskScore = $this->score($executionState['execution_risk_score'] ?? 0);
        $executionEligibilityScore = $this->score($eligibilityState['execution_authorization_eligibility_score'] ?? 0);
        $executionSafetyScore = $this->score($safetyState['execution_authorization_safety_score'] ?? 0);
        $combinedResolutionScore = $this->score($conditionState['combined_resolution_score'] ?? 0);
        $combinedPressureScore = $this->score($conditionState['combined_condition_restriction_pressure_score'] ?? 0);

        $sourceDecisionRecorded = (bool) ($sourceContext['source_activation_authorization_decision_recorded'] ?? false);
        $sourceAuthorizedHuman = (bool) ($sourceContext['source_activation_authorization_made_by_authorized_human'] ?? false);
        $sourceAttributionComplete = (bool) ($sourceContext['source_activation_authorization_attribution_complete'] ?? false);
        $sourceAuthorizationCompleted = (bool) ($sourceContext['source_controlled_activation_authorization_completed'] ?? false);

        $executionDecisionRecorded = (bool) ($executionAuthorizationContext['execution_authorization_decision_recorded'] ?? false);
        $executionAuthorizedHuman = (bool) ($executionAuthorizationContext['execution_authorization_made_by_authorized_human'] ?? false);
        $executionAttributionComplete = (bool) ($executionAuthorizationContext['execution_authorization_attribution_complete'] ?? false);
        $executionAuthorizationCompleted = (bool) ($executionAuthorizationContext['controlled_activation_execution_authorization_completed'] ?? false);

        $controlledActivationExecuted = (bool) ($actualExecutionContext['controlled_activation_executed'] ?? false);
        $actualExecutionAttributionComplete = (bool) ($actualExecutionContext['actual_execution_attribution_complete'] ?? false);

        $governanceIntegrityIntact = (bool) ($safetyState['governance_integrity_intact'] ?? false);

        $checks = [];

        $checks['controlled_activation_execution_authorization_available'] = [
            'passed' => true,
            'message' => 'Controlled activation execution authorization registry record is available.',
        ];

        $checks['execution_authorization_preparation_operational'] = [
            'passed' => !empty($authorization->execution_authorization_code),
            'message' => 'Controlled activation execution authorization preparation is operational.',
        ];

        $checks['execution_authorization_state_intelligence_operational'] = [
            'passed' => (bool) ($state['analysis_completed'] ?? false),
            'message' => 'Controlled activation execution authorization state intelligence is operational.',
        ];

        $checks['condition_restriction_intelligence_operational'] = [
            'passed' => (bool) ($conditionRestriction['analysis_completed'] ?? false),
            'message' => 'Execution authorization condition and restriction intelligence is operational.',
        ];

        $checks['eligibility_safety_intelligence_operational'] = [
            'passed' => (bool) ($eligibilitySafety['analysis_completed'] ?? false),
            'message' => 'Execution authorization eligibility and safety intelligence is operational.',
        ];

        $checks['recommendation_intelligence_operational'] = [
            'passed' => (bool) ($recommendation['analysis_completed'] ?? false),
            'message' => 'Execution authorization recommendation intelligence is operational.',
        ];

        $checks['executive_intelligence_operational'] = [
            'passed' => (bool) ($executive['analysis_completed'] ?? false),
            'message' => 'Executive controlled activation execution authorization intelligence is operational.',
        ];

        $checks['step_70_audit_complete'] = [
            'passed' => ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (($audit['audit_summary']['failed_checks'] ?? 1) === 0),
            'message' => 'Step 70 controlled activation execution authorization audit completed without integrity failures.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Controlled activation execution authorization governance integrity remains intact.',
        ];

        $checks['critical_execution_conditions_safely_governed'] = [
            'passed' => $criticalOpenConditions === 0
                || (
                    ($executionState['execution_authorization_progression_blocked'] ?? false) === true
                    && ($executionState['actual_execution_progression_blocked'] ?? false) === true
                    && ($safetyState['immediate_human_intervention_required'] ?? false) === true
                ),
            'value' => $criticalOpenConditions,
            'message' => 'Critical execution authorization conditions must safely block progression and require authorized-human governance intervention.',
        ];

        $checks['critical_execution_restrictions_safely_governed'] = [
            'passed' => $criticalRestrictions === 0
                || (
                    ($executionState['execution_authorization_progression_blocked'] ?? false) === true
                    && ($executionState['actual_execution_progression_blocked'] ?? false) === true
                    && ($safetyState['immediate_human_intervention_required'] ?? false) === true
                ),
            'value' => $criticalRestrictions,
            'message' => 'Critical execution restrictions must safely block unsafe progression and require authorized-human governance intervention.',
        ];

        $checks['automatic_authority_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_execution_authorization_allowed === false
                && (bool) $authorization->automatic_activation_authorization_allowed === false
                && (bool) $authorization->automatic_activation_allowed === false
                && (bool) $authorization->automatic_execution_allowed === false
                && (bool) $authorization->automatic_condition_resolution_allowed === false
                && (bool) $authorization->automatic_restriction_removal_allowed === false
                && (bool) $authorization->automatic_evidence_validation_allowed === false
                && (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,
            'message' => 'Automatic authorization, activation, execution, condition resolution, restriction removal, evidence validation, AI change, deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $authorization->human_review_required === true
                && (bool) $authorization->governance_confirmation_required === true
                && (bool) $authorization->controlled_activation_authorization_required === true
                && (bool) $authorization->authorized_human_execution_authorization_required === true
                && (bool) $authorization->authorized_human_execution_required === true,
            'message' => 'Human review, governance confirmation, activation authorization, execution authorization, and actual execution governance remain mandatory.',
        ];

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['execution_state_intelligence_is_execution_authorization'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_is_actual_execution'] ?? true) === false
                && ($stateGuardrails['execution_state_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'State intelligence remains informational and isolated from execution authorization and actual execution authority.',
        ];

        $checks['condition_restriction_authority_isolation'] = [
            'passed' =>
                ($conditionGuardrails['condition_restriction_intelligence_is_execution_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_is_actual_execution'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_authorizes_execution'] ?? true) === false,
            'message' => 'Condition and restriction intelligence remains advisory and isolated from execution authorization and actual execution authority.',
        ];

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilityGuardrails['eligibility_safety_intelligence_is_execution_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_is_actual_execution'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'Eligibility and safety intelligence remains informational and isolated from execution authorization and actual execution authority.',
        ];

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['recommendation_intelligence_is_execution_authorization'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_is_actual_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'Recommendation intelligence remains advisory and isolated from execution authorization and actual execution authority.',
        ];

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_execution_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_is_actual_execution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_executes_controlled_activation'] ?? true) === false,
            'message' => 'Executive intelligence remains informational and isolated from execution authorization and actual execution authority.',
        ];

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
                && ($auditGuardrails['audit_is_actual_execution'] ?? true) === false
                && ($auditGuardrails['audit_executes_controlled_activation'] ?? true) === false
                && ($auditGuardrails['audit_authorizes_execution'] ?? true) === false,
            'message' => 'Step 70 audit remains informational and isolated from execution authorization and actual execution authority.',
        ];

        $checks['source_activation_authorization_sequence_integrity'] = [
            'passed' => !$executionAuthorizationCompleted
                || (
                    $sourceDecisionRecorded
                    && $sourceAuthorizedHuman
                    && $sourceAttributionComplete
                    && $sourceAuthorizationCompleted
                ),
            'message' => 'Completed execution authorization must follow completed explicitly authorized-human controlled activation authorization.',
        ];

        $checks['execution_authorization_authority_protected'] = [
            'passed' => !$executionAuthorizationCompleted
                || (
                    $executionDecisionRecorded
                    && $executionAuthorizedHuman
                    && $executionAttributionComplete
                ),
            'value' => $executionAuthorizationCompleted,
            'message' => 'Controlled activation execution authorization authority remains reserved for explicitly authorized human governance.',
        ];

        $checks['execution_authorization_attribution_integrity'] = [
            'passed' => !$executionAuthorizationCompleted || $executionAttributionComplete,
            'value' => $executionAttributionComplete,
            'message' => 'Completed execution authorization must remain attributable to an identified authorized human governance authorizer.',
        ];

        $checks['actual_execution_sequence_integrity'] = [
            'passed' => !$controlledActivationExecuted
                || (
                    $executionDecisionRecorded
                    && $executionAuthorizedHuman
                    && $executionAttributionComplete
                    && $executionAuthorizationCompleted
                ),
            'message' => 'Actual controlled activation execution must follow completed explicitly authorized-human execution authorization.',
        ];

        $checks['actual_execution_attribution_integrity'] = [
            'passed' => !$controlledActivationExecuted || $actualExecutionAttributionComplete,
            'value' => $actualExecutionAttributionComplete,
            'message' => 'Actual controlled activation execution must remain separately attributable to an identified authorized human executor.',
        ];

        $checks['authorization_execution_sequence_integrity'] = [
            'passed' =>
                ($stateGuardrails['actual_execution_authority_separate_from_execution_authorization'] ?? false) === true
                && ($executiveGuardrails['actual_execution_authority_separate_from_execution_authorization'] ?? false) === true
                && ($auditGuardrails['actual_execution_authority_separate_from_execution_authorization'] ?? false) === true,
            'message' => 'Execution authorization and actual controlled activation execution remain separate governed authority stages.',
        ];

        $checks['authorization_does_not_imply_execution'] = [
            'passed' => !$executionAuthorizationCompleted
                || !$controlledActivationExecuted
                || $actualExecutionAttributionComplete,
            'message' => 'Completed execution authorization must not implicitly or automatically execute controlled activation.',
        ];

        $checks['actual_execution_authority_protected'] = [
            'passed' => !$controlledActivationExecuted || $actualExecutionAttributionComplete,
            'value' => $controlledActivationExecuted,
            'message' => 'Actual controlled activation execution authority remains separately reserved for authorized human governance.',
        ];

        $checks['ai_change_execution_clinical_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_execution_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,
            'message' => 'Step 70 does not authorize autonomous AI modification, execution, deployment, rollback, or clinical action.',
        ];

        $totalChecks = count($checks);

        $passedChecks = count(
            array_filter(
                $checks,
                fn (array $check): bool => ($check['passed'] ?? false) === true
            )
        );

        $failedChecks = $totalChecks - $passedChecks;

        $warnings = [];

        if (!$sourceDecisionRecorded) {
            $warnings[] = 'Authorized-human controlled activation authorization decision has not yet been recorded.';
        }

        if (!$sourceAuthorizedHuman) {
            $warnings[] = 'Controlled activation authorization has not yet been attributed to an explicitly authorized human.';
        }

        if (!$sourceAttributionComplete) {
            $warnings[] = 'Controlled activation authorization attribution remains incomplete.';
        }

        if (!$sourceAuthorizationCompleted) {
            $warnings[] = 'Controlled activation authorization has not yet been completed.';
        }

        if ($blockingExecutionAuthorizationConditions > 0) {
            $warnings[] = "{$blockingExecutionAuthorizationConditions} execution authorization blocker(s) remain active.";
        }

        if ($blockingActualExecutionConditions > 0) {
            $warnings[] = "{$blockingActualExecutionConditions} actual controlled activation execution blocker(s) remain active.";
        }

        if ($criticalOpenConditions > 0) {
            $warnings[] = "{$criticalOpenConditions} critical execution authorization condition(s) remain active and are correctly blocking progression.";
        }

        if ($materialRestrictions > 0) {
            $warnings[] = "{$materialRestrictions} material execution restriction(s) remain active.";
        }

        if ($criticalRestrictions > 0) {
            $warnings[] = "{$criticalRestrictions} critical execution restriction(s) remain active and are correctly blocking unsafe progression.";
        }

        if ($executionRiskScore >= 90) {
            $warnings[] = "Controlled activation execution risk remains {$authorization->execution_risk_level} with score {$executionRiskScore}.";
        }

        if (!$executionDecisionRecorded) {
            $warnings[] = 'Authorized-human controlled activation execution authorization decision has not yet been recorded.';
        }

        if (!$executionAuthorizedHuman) {
            $warnings[] = 'Execution authorization has not yet been made by an explicitly authorized human governance authority.';
        }

        if (!$executionAttributionComplete) {
            $warnings[] = 'Execution authorization attribution remains incomplete.';
        }

        if (!$executionAuthorizationCompleted) {
            $warnings[] = 'Controlled activation execution authorization has not yet been completed.';
        }

        if (!$controlledActivationExecuted) {
            $warnings[] = 'Controlled activation has not been executed.';
        }

        if (!$actualExecutionAttributionComplete) {
            $warnings[] = 'Actual controlled activation execution attribution is not present because execution has not occurred.';
        }

        if (
            ($eligibilityState['controlled_activation_execution_authorization_completion_eligibility'] ?? null)
            === 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_COMPLETION'
        ) {
            $warnings[] = 'Controlled activation execution authorization is not currently eligible for completion.';
        }

        if (
            ($eligibilityState['actual_controlled_activation_execution_review_eligibility'] ?? null)
            === 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
        ) {
            $warnings[] = 'Actual controlled activation execution is not currently eligible for review.';
        }

        $criticalIssues = [];

        foreach ($checks as $code => $check) {
            if (($check['passed'] ?? false) === false) {
                $criticalIssues[] = [
                    'code' => strtoupper($code),
                    'message' => $check['message'] ?? 'Step 70 final validation check failed.',
                ];
            }
        }

        $validationStatus = match (true) {
            $failedChecks > 0 => 'FAILED',
            count($warnings) > 0 => 'PASSED_WITH_WARNINGS',
            default => 'PASSED',
        };

        $step70ReadyForClosure =
            $failedChecks === 0
            && $governanceIntegrityIntact
            && ($audit['audit_status'] ?? null) === 'COMPLETE';

        return [
            'validation_status' =>
                $validationStatus,

            'step_70_ready_for_closure' =>
                $step70ReadyForClosure,

            'controlled_activation_execution_authorization_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_INTELLIGENCE',

            'strategic_plan_controlled_activation_execution_authorization_id' =>
                $authorization->id,

            'execution_authorization_code' =>
                $authorization->execution_authorization_code,

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->strategic_plan_controlled_activation_authorization_id,

            'strategic_plan_governance_decision_confirmation_id' =>
                $authorization->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id' =>
                $authorization->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $authorization->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $authorization->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $authorization->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $authorization->strategic_plan_id,

            'strategic_snapshot_id' =>
                $authorization->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $authorization->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $authorization->lifecycle_snapshot_id,

            'decision_scope' =>
                $authorization->decision_scope,

            'resident_id' =>
                $authorization->resident_id,

            'completion_message' =>
                $step70ReadyForClosure
                    ? 'Step 70 AI Governance Strategic Plan Controlled Activation Execution Authorization Intelligence has passed final architecture validation with governed operational warnings. Execution authorization and actual controlled activation execution remain subject to explicitly authorized human governance.'
                    : 'Step 70 final validation identified architecture or governance-control failures that must be resolved before closure.',

            'validation_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
                'warning_count' => count($warnings),
                'critical_issue_count' => count($criticalIssues),
            ],

            'checks' =>
                $checks,

            'controlled_activation_execution_authorization_context' => [
                'execution_authorization_status' =>
                    $authorization->execution_authorization_status,

                'execution_authorization_mode' =>
                    $authorization->execution_authorization_mode,

                'controlled_activation_execution_authorization_decision' =>
                    $authorization->controlled_activation_execution_authorization_decision,

                'execution_authorization_state' =>
                    $executionState['execution_authorization_state'] ?? null,

                'execution_readiness' =>
                    $executionState['execution_readiness'] ?? $authorization->execution_readiness,

                'execution_readiness_score' =>
                    $executionReadinessScore,

                'execution_risk_level' =>
                    $executionState['execution_risk_level'] ?? $authorization->execution_risk_level,

                'execution_risk_score' =>
                    $executionRiskScore,

                'execution_authorization_eligibility_score' =>
                    $executionEligibilityScore,

                'execution_authorization_safety_status' =>
                    $safetyState['execution_authorization_safety_status'] ?? null,

                'execution_authorization_safety_score' =>
                    $executionSafetyScore,

                'blocking_execution_authorization_conditions' =>
                    $blockingExecutionAuthorizationConditions,

                'blocking_actual_execution_conditions' =>
                    $blockingActualExecutionConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,

                'source_activation_authorization_decision_recorded' =>
                    $sourceDecisionRecorded,

                'source_activation_authorization_made_by_authorized_human' =>
                    $sourceAuthorizedHuman,

                'source_activation_authorization_attribution_complete' =>
                    $sourceAttributionComplete,

                'source_controlled_activation_authorization_completed' =>
                    $sourceAuthorizationCompleted,

                'execution_authorization_decision_recorded' =>
                    $executionDecisionRecorded,

                'execution_authorization_made_by_authorized_human' =>
                    $executionAuthorizedHuman,

                'execution_authorization_attribution_complete' =>
                    $executionAttributionComplete,

                'controlled_activation_execution_authorization_completed' =>
                    $executionAuthorizationCompleted,

                'controlled_activation_executed' =>
                    $controlledActivationExecuted,

                'actual_execution_attribution_complete' =>
                    $actualExecutionAttributionComplete,
            ],

            'architecture_summary' => [
                '70.1_execution_authorization_registry' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_controlled_activation_execution_authorization_id' => $authorization->id,
                ],

                '70.2_execution_authorization_preparation' => [
                    'status' => 'OPERATIONAL',
                    'execution_authorization_code' => $authorization->execution_authorization_code,
                    'execution_authorization_status' => $authorization->execution_authorization_status,
                ],

                '70.3_execution_authorization_state_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'execution_authorization_state' => $executionState['execution_authorization_state'] ?? null,
                    'execution_readiness' => $executionState['execution_readiness'] ?? null,
                    'execution_readiness_score' => $executionReadinessScore,
                    'execution_risk_level' => $executionState['execution_risk_level'] ?? null,
                    'execution_risk_score' => $executionRiskScore,
                ],

                '70.4_condition_restriction_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'state' => $conditionState['state'] ?? null,
                    'blocking_execution_authorization_conditions' => $blockingExecutionAuthorizationConditions,
                    'blocking_actual_execution_conditions' => $blockingActualExecutionConditions,
                    'critical_open_conditions' => $criticalOpenConditions,
                    'material_restrictions' => $materialRestrictions,
                    'critical_restrictions' => $criticalRestrictions,
                    'combined_resolution_score' => $combinedResolutionScore,
                    'combined_condition_restriction_pressure_score' => $combinedPressureScore,
                ],

                '70.5_eligibility_safety_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'controlled_activation_execution_authorization_completion_eligibility' =>
                        $eligibilityState['controlled_activation_execution_authorization_completion_eligibility'] ?? null,
                    'actual_controlled_activation_execution_review_eligibility' =>
                        $eligibilityState['actual_controlled_activation_execution_review_eligibility'] ?? null,
                    'execution_authorization_eligibility_score' => $executionEligibilityScore,
                    'execution_authorization_safety_status' =>
                        $safetyState['execution_authorization_safety_status'] ?? null,
                    'execution_authorization_safety_level' =>
                        $safetyState['execution_authorization_safety_level'] ?? null,
                    'execution_authorization_safety_score' => $executionSafetyScore,
                ],

                '70.6_recommendation_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                    'total_recommendations' => $recommendationState['total_recommendations'] ?? 0,
                    'critical_recommendations' => $recommendationState['critical_recommendations'] ?? 0,
                    'top_recommendation_code' => $recommendationState['top_recommendation_code'] ?? null,
                    'top_recommended_execution_path' => $recommendationState['top_recommended_execution_path'] ?? null,
                ],

                '70.7_executive_execution_authorization_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'executive_controlled_activation_execution_authorization_status' =>
                        $executiveState['executive_controlled_activation_execution_authorization_status'] ?? null,
                    'executive_controlled_activation_execution_authorization_readiness' =>
                        $executiveState['executive_controlled_activation_execution_authorization_readiness'] ?? null,
                    'executive_controlled_activation_execution_authorization_score' =>
                        $executiveState['executive_controlled_activation_execution_authorization_score'] ?? null,
                    'executive_decision_posture' =>
                        $executiveState['executive_decision_posture'] ?? null,
                ],

                '70.8_execution_authorization_audit' => [
                    'status' => $audit['audit_status'] ?? 'UNKNOWN',
                    'passed_checks' => $audit['audit_summary']['passed_checks'] ?? 0,
                    'failed_checks' => $audit['audit_summary']['failed_checks'] ?? 0,
                    'management_status' => $audit['management_status'] ?? null,
                ],

                '70.9_final_validation' => [
                    'status' => $validationStatus,
                    'step_70_ready_for_closure' => $step70ReadyForClosure,
                ],
            ],

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' => [
                'The complete Step 70 AI Governance Strategic Plan Controlled Activation Execution Authorization Intelligence architecture has been validated.',
                'Current execution authorization state is '.($executionState['execution_authorization_state'] ?? 'UNKNOWN').'.',
                'Current execution readiness is '.($executionState['execution_readiness'] ?? 'UNKNOWN')." with score {$executionReadinessScore}.",
                'Current controlled activation execution risk is '.($executionState['execution_risk_level'] ?? 'UNKNOWN')." with score {$executionRiskScore}.",
                "{$blockingExecutionAuthorizationConditions} execution authorization blocker(s) remain.",
                "{$blockingActualExecutionConditions} actual controlled activation execution blocker(s) remain.",
                "{$criticalOpenConditions} critical execution authorization condition(s) remain.",
                "{$materialRestrictions} material execution restriction(s) remain.",
                "{$criticalRestrictions} critical execution restriction(s) remain.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                'Current execution authorization completion eligibility is '.($eligibilityState['controlled_activation_execution_authorization_completion_eligibility'] ?? 'UNKNOWN').'.',
                'Current actual controlled activation execution review eligibility is '.($eligibilityState['actual_controlled_activation_execution_review_eligibility'] ?? 'UNKNOWN').'.',
                'Current execution authorization safety status is '.($safetyState['execution_authorization_safety_status'] ?? 'UNKNOWN')." with score {$executionSafetyScore}.",
                'Current recommendation status is '.($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',
                'Current executive controlled activation execution authorization status is '.($executiveState['executive_controlled_activation_execution_authorization_status'] ?? 'UNKNOWN').'.',
                'Current executive controlled activation execution authorization readiness is '.($executiveState['executive_controlled_activation_execution_authorization_readiness'] ?? 'UNKNOWN').' with score '.($executiveState['executive_controlled_activation_execution_authorization_score'] ?? 0).'.',
                'Source controlled activation authorization decision recorded is '.($sourceDecisionRecorded ? 'YES' : 'NO').'.',
                'Source controlled activation authorization completed is '.($sourceAuthorizationCompleted ? 'YES' : 'NO').'.',
                'Execution authorization decision recorded is '.($executionDecisionRecorded ? 'YES' : 'NO').'.',
                'Execution authorization completed is '.($executionAuthorizationCompleted ? 'YES' : 'NO').'.',
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Actual controlled activation execution attribution complete is '.($actualExecutionAttributionComplete ? 'YES' : 'NO').'.',
                'Execution authorization intelligence remains advisory, informational, human governed, and isolated from autonomous execution authority.',
                'No autonomous execution authorization, controlled activation execution, AI modification, deployment, rollback, or clinical-action pathway is enabled.',
            ],

            'step_70_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(0, min(100, (float) $value)),
            2
        );
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_intelligence_enabled' => true,
            'controlled_activation_execution_authorization_registry_enabled' => true,
            'controlled_activation_execution_authorization_preparation_enabled' => true,
            'controlled_activation_execution_authorization_state_intelligence_enabled' => true,
            'controlled_activation_execution_authorization_condition_restriction_intelligence_enabled' => true,
            'controlled_activation_execution_authorization_eligibility_safety_intelligence_enabled' => true,
            'controlled_activation_execution_authorization_recommendation_intelligence_enabled' => true,
            'executive_controlled_activation_execution_authorization_intelligence_enabled' => true,
            'controlled_activation_execution_authorization_audit_enabled' => true,

            'autonomous_execution_authorization_enabled' => false,
            'autonomous_controlled_activation_execution_enabled' => false,
            'autonomous_strategic_plan_activation_enabled' => false,

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
            'automatic_execution_allowed' => false,
            'automatic_change_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,
            'automatic_clinical_action_allowed' => false,

            'execution_authorization_authority_reserved_for_authorized_human' => true,
            'actual_execution_authority_separate_from_execution_authorization' => true,
            'actual_execution_authority_reserved_for_authorized_human' => true,
            'activation_authorization_authority_reserved_for_authorized_human' => true,
            'governance_confirmation_authority_reserved_for_authorized_human' => true,
            'final_governance_decision_authority_reserved_for_authorized_human' => true,

            'human_review_required' => true,
            'governance_confirmation_required' => true,
            'controlled_activation_authorization_required' => true,
            'authorized_human_execution_authorization_required' => true,
            'authorized_human_execution_required' => true,

            'message' =>
                'Step 70 establishes explicitly authorized-human-governed strategic plan controlled activation execution authorization intelligence. The architecture may prepare an execution authorization record and evaluate source activation authorization dependencies, execution authorization state, conditions, restrictions, evidence, readiness, risk, eligibility, safety, recommendations, executive state, execution-authorizer attribution, actual-execution attribution, and governance-control integrity. It does not autonomously make or record execution authorization, complete execution authorization, execute controlled activation, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, deploy updates, execute autonomous changes, trigger rollback, or initiate clinical action. Execution authorization and actual controlled activation execution remain separate authorities reserved exclusively for explicitly authorized human governance.',
        ];
    }
}