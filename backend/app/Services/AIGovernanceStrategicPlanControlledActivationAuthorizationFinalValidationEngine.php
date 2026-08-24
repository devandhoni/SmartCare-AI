<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationFinalValidationEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Authorization Record
        |--------------------------------------------------------------------------
        */

        $authorization = $authorizationId
            ? AIGovernanceStrategicPlanControlledActivationAuthorization::find($authorizationId)
            : AIGovernanceStrategicPlanControlledActivationAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'validation_status' =>
                    'FAILED',

                'step_69_ready_for_closure' =>
                    false,

                'controlled_activation_authorization_mode' =>
                    'AUTHORIZED_HUMAN_GOVERNED_CONTROLLED_ACTIVATION_AUTHORIZATION_INTELLIGENCE',

                'completion_message' =>
                    'Step 69 final validation could not continue because no controlled activation authorization record is available.',

                'validation_summary' => [
                    'total_checks' => 1,
                    'passed_checks' => 0,
                    'failed_checks' => 1,
                    'warning_count' => 0,
                    'critical_issue_count' => 1,
                ],

                'critical_issues' => [
                    'No controlled activation authorization record is available.',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 69 Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
        );

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationExecutiveIntelligenceEngine::class
        );

        $auditEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationAuditSummaryEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Analyze Step 69
        |--------------------------------------------------------------------------
        */

        $state =
            $stateEngine->analyze($authorization->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($authorization->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($authorization->id);

        $recommendation =
            $recommendationEngine->analyze($authorization->id);

        $executive =
            $executiveEngine->analyze($authorization->id);

        $audit =
            $auditEngine->analyze($authorization->id);

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $eligibilityState =
            $eligibilitySafety['activation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['activation_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $executiveState =
            $executive['executive_state'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Guardrails
        |--------------------------------------------------------------------------
        */

        $stateGuardrails =
            $state['activation_authorization_state_guardrails']
            ?? [];

        $conditionGuardrails =
            $conditionRestriction['activation_authorization_condition_restriction_guardrails']
            ?? [];

        $eligibilityGuardrails =
            $eligibilitySafety['activation_authorization_eligibility_safety_guardrails']
            ?? [];

        $recommendationGuardrails =
            $recommendation['activation_authorization_recommendation_guardrails']
            ?? [];

        $executiveGuardrails =
            $executive['executive_controlled_activation_authorization_guardrails']
            ?? [];

        $auditGuardrails =
            $audit['audit_guardrails']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core State
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            (bool) (
                $safetyState['governance_integrity_intact']
                ?? false
            );

        $sourceConfirmationDecisionRecorded =
            (bool) (
                $sourceConfirmationContext['source_confirmation_decision_recorded']
                ?? false
            );

        $sourceConfirmationMadeByAuthorizedHuman =
            (bool) (
                $sourceConfirmationContext['source_confirmation_made_by_authorized_human']
                ?? false
            );

        $sourceConfirmationCompleted =
            (bool) (
                $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                ?? false
            );

        $authorizationDecisionRecorded =
            (bool) (
                $activationState['authorization_decision_recorded']
                ?? false
            );

        $authorizationMadeByAuthorizedHuman =
            (bool) (
                $activationState['activation_authorization_made_by_authorized_human']
                ?? false
            );

        $authorizationAttributionComplete =
            (bool) (
                $activationState['authorization_attribution_complete']
                ?? false
            );

        $authorizationCompleted =
            (bool) (
                $activationState['controlled_activation_authorization_completed']
                ?? false
            );

        $executionAuthorized =
            (bool) (
                $executionContext['controlled_activation_execution_authorized']
                ?? false
            );

        $executionAttributionComplete =
            (bool) (
                $executionContext['execution_authorization_attribution_complete']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $authorizationBlockers =
            (int) (
                $conditionState['blocking_activation_authorization_conditions']
                ?? 0
            );

        $executionBlockers =
            (int) (
                $conditionState['blocking_activation_execution_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionState['critical_open_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $conditionState['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $conditionState['critical_restrictions']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $activationReadinessScore =
            $this->score(
                $activationState['activation_readiness_score']
                ?? $authorization->activation_readiness_score
            );

        $activationRiskScore =
            $this->score(
                $activationState['activation_risk_score']
                ?? $authorization->activation_risk_score
            );

        $eligibilityScore =
            $this->score(
                $eligibilityState['activation_authorization_eligibility_score']
                ?? 0
            );

        $safetyScore =
            $this->score(
                $safetyState['activation_authorization_safety_score']
                ?? 0
            );

        $combinedResolutionScore =
            $this->score(
                $conditionState['combined_resolution_score']
                ?? $authorization->combined_resolution_score
            );

        $combinedPressureScore =
            $this->score(
                $conditionState['combined_condition_restriction_pressure_score']
                ?? $authorization->combined_condition_restriction_pressure_score
            );

        $executiveScore =
            $this->score(
                $executiveState['executive_controlled_activation_authorization_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Final Architecture Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['controlled_activation_authorization_available'] = [
            'passed' => true,
            'message' =>
                'Controlled activation authorization record is available.',
        ];

        $checks['activation_authorization_preparation_operational'] = [
            'passed' =>
                $authorization->id !== null
                && !empty($authorization->activation_authorization_code),

            'message' =>
                'Controlled activation authorization preparation is operational.',
        ];

        $checks['activation_authorization_state_intelligence_operational'] = [
            'passed' =>
                (bool) ($state['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization state intelligence is operational.',
        ];

        $checks['condition_restriction_intelligence_operational'] = [
            'passed' =>
                (bool) ($conditionRestriction['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization condition and restriction intelligence is operational.',
        ];

        $checks['eligibility_safety_intelligence_operational'] = [
            'passed' =>
                (bool) ($eligibilitySafety['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization eligibility and safety intelligence is operational.',
        ];

        $checks['recommendation_intelligence_operational'] = [
            'passed' =>
                (bool) ($recommendation['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization recommendation intelligence is operational.',
        ];

        $checks['executive_intelligence_operational'] = [
            'passed' =>
                (bool) ($executive['analysis_completed'] ?? false),

            'message' =>
                'Executive controlled activation authorization intelligence is operational.',
        ];

        $checks['step_69_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (
                    (int) (
                        $audit['audit_summary']['failed_checks']
                        ?? 1
                    )
                ) === 0,

            'message' =>
                'Step 69 controlled activation authorization audit completed without integrity failures.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' =>
                $governanceIntegrityIntact,

            'message' =>
                'Controlled activation authorization governance integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Operational State Handling
        |--------------------------------------------------------------------------
        |
        | Critical restrictions are not architecture failures when they are
        | correctly detected and progression remains blocked.
        |
        */

        $checks['critical_activation_restriction_safely_governed'] = [
            'passed' =>
                $criticalRestrictions === 0
                || (
                    ($safetyState['immediate_human_intervention_required'] ?? false) === true
                    && ($eligibilityState['activation_progression_blocked'] ?? false) === true
                ),

            'value' =>
                $criticalRestrictions,

            'message' =>
                'Critical activation restrictions must trigger human governance intervention and block unsafe progression.',
        ];

        $checks['critical_open_activation_condition_absent'] = [
            'passed' =>
                $criticalOpenConditions === 0,

            'value' =>
                $criticalOpenConditions,

            'message' =>
                'No critical controlled activation authorization condition remains open.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_authority_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_activation_authorization_allowed === false
                && (bool) $authorization->automatic_confirmation_allowed === false
                && (bool) $authorization->automatic_final_decision_allowed === false
                && (bool) $authorization->automatic_approval_allowed === false
                && (bool) $authorization->automatic_rejection_allowed === false
                && (bool) $authorization->automatic_conditional_approval_allowed === false
                && (bool) $authorization->automatic_deferral_allowed === false
                && (bool) $authorization->automatic_risk_acceptance_allowed === false
                && (bool) $authorization->automatic_activation_allowed === false
                && (bool) $authorization->automatic_condition_resolution_allowed === false
                && (bool) $authorization->automatic_restriction_removal_allowed === false
                && (bool) $authorization->automatic_evidence_validation_allowed === false
                && (bool) $authorization->automatic_execution_allowed === false
                && (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,

            'message' =>
                'Automatic confirmation, authorization, activation, execution, governance decisions, condition resolution, restriction removal, evidence validation, AI change, deployment, rollback, and clinical action remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Requirements
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $authorization->human_review_required === true
                && (bool) $authorization->governance_confirmation_required === true
                && (bool) $authorization->authorized_human_activation_authorization_required === true
                && (bool) $authorization->authorized_human_activation_execution_required === true,

            'message' =>
                'Human review, governance confirmation, activation authorization, and execution authorization remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | State Intelligence Isolation
        |--------------------------------------------------------------------------
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['activation_state_intelligence_is_activation_authorization'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_is_activation_execution_authorization'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_records_activation_authorization_decision'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_completes_activation_authorization'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_activates_strategic_plan'] ?? true) === false,

            'message' =>
                'Controlled activation authorization state intelligence remains informational and isolated from authorization, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition Restriction Isolation
        |--------------------------------------------------------------------------
        */

        $checks['condition_restriction_authority_isolation'] = [
            'passed' =>
                ($conditionGuardrails['condition_restriction_intelligence_is_activation_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_is_activation_execution_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_records_activation_authorization_decision'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_completes_activation_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_resolves_conditions'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_removes_restrictions'] ?? true) === false,

            'message' =>
                'Condition and restriction intelligence remains advisory and isolated from resolution, authorization, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility Safety Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilityGuardrails['eligibility_safety_intelligence_is_activation_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_is_activation_execution_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_records_activation_authorization_decision'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_completes_activation_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_score_authorizes_activation'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_score_authorizes_execution'] ?? true) === false
                && ($eligibilityGuardrails['safety_score_authorizes_activation'] ?? true) === false
                && ($eligibilityGuardrails['safety_score_authorizes_execution'] ?? true) === false,

            'message' =>
                'Eligibility and safety intelligence remains informational and isolated from activation authorization and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Isolation
        |--------------------------------------------------------------------------
        */

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['recommendation_intelligence_is_activation_authorization'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_is_execution_authorization'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_records_activation_authorization_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_completes_activation_authorization'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($recommendationGuardrails['top_recommendation_authorizes_activation'] ?? true) === false
                && ($recommendationGuardrails['top_recommendation_authorizes_execution'] ?? true) === false,

            'message' =>
                'Recommendation intelligence remains advisory and isolated from activation authorization and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Isolation
        |--------------------------------------------------------------------------
        */

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_activation_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_is_execution_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_records_activation_authorization_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_completes_activation_authorization'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_activation'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['executive_decision_posture_authorizes_activation'] ?? true) === false
                && ($executiveGuardrails['executive_decision_posture_authorizes_execution'] ?? true) === false,

            'message' =>
                'Executive controlled activation intelligence remains informational and isolated from activation authorization and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Isolation
        |--------------------------------------------------------------------------
        */

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_activation_authorization'] ?? true) === false
                && ($auditGuardrails['audit_is_activation_execution_authorization'] ?? true) === false
                && ($auditGuardrails['audit_makes_activation_authorization_decision'] ?? true) === false
                && ($auditGuardrails['audit_records_activation_authorization_decision'] ?? true) === false
                && ($auditGuardrails['audit_completes_activation_authorization'] ?? true) === false
                && ($auditGuardrails['audit_authorizes_activation_execution'] ?? true) === false
                && ($auditGuardrails['audit_activates_strategic_plan'] ?? true) === false,

            'message' =>
                'Step 69 audit remains informational and isolated from activation authorization, execution authorization, and activation authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation Sequence Protection
        |--------------------------------------------------------------------------
        */

        $checks['source_confirmation_sequence_integrity'] = [
            'passed' =>
                !$authorizationCompleted
                || (
                    $sourceConfirmationDecisionRecorded
                    && $sourceConfirmationMadeByAuthorizedHuman
                    && $sourceConfirmationCompleted
                ),

            'message' =>
                'Completed controlled activation authorization must follow completed authorized-human governance confirmation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Activation Authorization Authority Protection
        |--------------------------------------------------------------------------
        */

        $checks['activation_authorization_authority_protected'] = [
            'passed' =>
                !$authorizationCompleted
                || (
                    $authorizationDecisionRecorded
                    && $authorizationMadeByAuthorizedHuman
                    && $authorizationAttributionComplete
                ),

            'value' =>
                $authorizationCompleted,

            'message' =>
                'Controlled activation authorization authority remains reserved for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Authority Protection
        |--------------------------------------------------------------------------
        */

        $checks['activation_execution_authority_protected'] = [
            'passed' =>
                !$executionAuthorized
                || (
                    $authorizationCompleted
                    && $authorizationAttributionComplete
                    && $executionAttributionComplete
                ),

            'value' =>
                $executionAuthorized,

            'message' =>
                'Controlled activation execution authority remains a separate explicitly authorized human governance function.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Attribution Integrity
        |--------------------------------------------------------------------------
        */

        $checks['activation_authorization_attribution_integrity'] = [
            'passed' =>
                !$authorizationCompleted
                || $authorizationAttributionComplete,

            'value' =>
                $authorizationAttributionComplete,

            'message' =>
                'Completed activation authorization must remain attributable to an identified authorized human governance authorizer.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Attribution Integrity
        |--------------------------------------------------------------------------
        */

        $checks['execution_authorization_attribution_integrity'] = [
            'passed' =>
                !$executionAuthorized
                || $executionAttributionComplete,

            'value' =>
                $executionAttributionComplete,

            'message' =>
                'Controlled activation execution authorization must remain separately attributable to an identified authorized human governance authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $checks['authorization_execution_sequence_integrity'] = [
            'passed' =>
                !$executionAuthorized
                || (
                    $authorizationCompleted
                    && $authorizationDecisionRecorded
                    && $authorizationMadeByAuthorizedHuman
                    && $authorizationAttributionComplete
                    && $executionAttributionComplete
                ),

            'message' =>
                'Controlled activation execution authorization must follow completed authorized-human activation authorization.',
        ];

        /*
        |--------------------------------------------------------------------------
        | No Implicit Execution from Authorization
        |--------------------------------------------------------------------------
        */

        $checks['authorization_does_not_imply_execution'] = [
            'passed' =>
                !$authorizationCompleted
                || (
                    $executionAuthorized === false
                    || $executionAttributionComplete === true
                ),

            'message' =>
                'Completed activation authorization must not implicitly or automatically authorize activation execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | AI Change / Clinical Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['ai_change_execution_clinical_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,

            'message' =>
                'Step 69 does not authorize autonomous AI modification, deployment, rollback, or clinical action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Count Checks
        |--------------------------------------------------------------------------
        */

        $passedChecks =
            count(
                array_filter(
                    $checks,
                    fn (array $check): bool =>
                        ($check['passed'] ?? false) === true
                )
            );

        $failedChecks =
            count($checks) - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $warnings[] =
                'Authorized-human governance confirmation decision has not yet been recorded.';
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $warnings[] =
                'Governance confirmation has not yet been attributed to an explicitly authorized human confirmer.';
        }

        if (!$sourceConfirmationCompleted) {
            $warnings[] =
                'Required governance decision confirmation has not yet been completed.';
        }

        if ($authorizationBlockers > 0) {
            $warnings[] =
                "{$authorizationBlockers} controlled activation authorization blocker(s) remain active.";
        }

        if ($executionBlockers > 0) {
            $warnings[] =
                "{$executionBlockers} controlled activation execution blocker(s) remain active.";
        }

        if ($materialRestrictions > 0) {
            $warnings[] =
                "{$materialRestrictions} material controlled activation restriction(s) remain active.";
        }

        if ($criticalRestrictions > 0) {
            $warnings[] =
                "{$criticalRestrictions} critical controlled activation restriction(s) remain active and are correctly blocking progression.";
        }

        if ($activationRiskScore >= 75) {
            $warnings[] =
                'Controlled activation risk remains '
                .($activationState['activation_risk_level'] ?? 'ELEVATED')
                .' with score '
                .$activationRiskScore.'.';
        }

        if (!$authorizationDecisionRecorded) {
            $warnings[] =
                'Authorized-human controlled activation authorization decision has not yet been recorded.';
        }

        if (!$authorizationMadeByAuthorizedHuman) {
            $warnings[] =
                'Controlled activation authorization has not yet been made by an explicitly authorized human governance authority.';
        }

        if (!$authorizationAttributionComplete) {
            $warnings[] =
                'Controlled activation authorization attribution remains incomplete.';
        }

        if (!$authorizationCompleted) {
            $warnings[] =
                'Controlled activation authorization has not yet been completed.';
        }

        if (!$executionAuthorized) {
            $warnings[] =
                'Controlled activation execution remains not authorized.';
        }

        if (!$executionAttributionComplete) {
            $warnings[] =
                'Controlled activation execution authorization attribution remains incomplete.';
        }

        if (
            ($eligibilityState['controlled_activation_authorization_completion_eligibility'] ?? null)
            !== 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETION'
        ) {
            $warnings[] =
                'Controlled activation authorization is not currently eligible for completion.';
        }

        if (
            ($eligibilityState['activation_execution_review_eligibility'] ?? null)
            !== 'ELIGIBLE_FOR_SEPARATE_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_REVIEW'
        ) {
            $warnings[] =
                'Controlled activation execution is not currently eligible for separate execution review.';
        }

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $checkCode => $check) {
            if (($check['passed'] ?? false) === false) {
                $criticalIssues[] =
                    "Final Step 69 integrity check failed: {$checkCode}.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Validation Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {
            $validationStatus =
                'FAILED';

            $step69ReadyForClosure =
                false;

            $completionMessage =
                'Step 69 AI Governance Strategic Plan Controlled Activation Authorization Intelligence has not passed final validation and requires corrective governance-control work.';
        } elseif (count($warnings) > 0) {
            $validationStatus =
                'PASSED_WITH_WARNINGS';

            $step69ReadyForClosure =
                true;

            $completionMessage =
                'Step 69 AI Governance Strategic Plan Controlled Activation Authorization Intelligence has passed final architecture validation with governed operational warnings. Controlled activation authorization and execution remain subject to explicitly authorized human governance.';
        } else {
            $validationStatus =
                'PASSED';

            $step69ReadyForClosure =
                true;

            $completionMessage =
                'Step 69 AI Governance Strategic Plan Controlled Activation Authorization Intelligence has passed final validation and is ready for closure.';
        }

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '69.1_controlled_activation_authorization_registry' => [
                'status' =>
                    'OPERATIONAL',

                'strategic_plan_controlled_activation_authorization_id' =>
                    $authorization->id,
            ],

            '69.2_controlled_activation_authorization_preparation' => [
                'status' =>
                    'OPERATIONAL',

                'activation_authorization_code' =>
                    $authorization->activation_authorization_code,

                'activation_authorization_status' =>
                    $authorization->activation_authorization_status,
            ],

            '69.3_activation_authorization_state_intelligence' => [
                'status' =>
                    ($state['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'FAILED',

                'controlled_activation_authorization_state' =>
                    $activationState['controlled_activation_authorization_state']
                    ?? null,

                'activation_readiness' =>
                    $activationState['activation_readiness']
                    ?? null,

                'activation_readiness_score' =>
                    $activationReadinessScore,

                'activation_risk_level' =>
                    $activationState['activation_risk_level']
                    ?? null,

                'activation_risk_score' =>
                    $activationRiskScore,
            ],

            '69.4_condition_restriction_intelligence' => [
                'status' =>
                    ($conditionRestriction['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'FAILED',

                'state' =>
                    $conditionState['state']
                    ?? null,

                'blocking_activation_authorization_conditions' =>
                    $authorizationBlockers,

                'blocking_activation_execution_conditions' =>
                    $executionBlockers,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,
            ],

            '69.5_eligibility_safety_intelligence' => [
                'status' =>
                    ($eligibilitySafety['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'FAILED',

                'controlled_activation_authorization_completion_eligibility' =>
                    $eligibilityState['controlled_activation_authorization_completion_eligibility']
                    ?? null,

                'activation_execution_review_eligibility' =>
                    $eligibilityState['activation_execution_review_eligibility']
                    ?? null,

                'activation_authorization_eligibility_score' =>
                    $eligibilityScore,

                'activation_authorization_safety_status' =>
                    $safetyState['activation_authorization_safety_status']
                    ?? null,

                'activation_authorization_safety_level' =>
                    $safetyState['activation_authorization_safety_level']
                    ?? null,

                'activation_authorization_safety_score' =>
                    $safetyScore,
            ],

            '69.6_recommendation_intelligence' => [
                'status' =>
                    ($recommendation['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'FAILED',

                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,

                'total_recommendations' =>
                    $recommendationState['total_recommendations']
                    ?? 0,

                'critical_recommendations' =>
                    $recommendationState['critical_recommendations']
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationState['top_recommendation_code']
                    ?? null,

                'top_recommended_activation_path' =>
                    $recommendationState['top_recommended_activation_path']
                    ?? null,
            ],

            '69.7_executive_controlled_activation_authorization_intelligence' => [
                'status' =>
                    ($executive['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'FAILED',

                'executive_controlled_activation_authorization_status' =>
                    $executiveState['executive_controlled_activation_authorization_status']
                    ?? null,

                'executive_controlled_activation_authorization_readiness' =>
                    $executiveState['executive_controlled_activation_authorization_readiness']
                    ?? null,

                'executive_controlled_activation_authorization_score' =>
                    $executiveScore,

                'executive_decision_posture' =>
                    $executiveState['executive_decision_posture']
                    ?? null,
            ],

            '69.8_controlled_activation_authorization_audit' => [
                'status' =>
                    $audit['audit_status']
                    ?? 'FAILED',

                'passed_checks' =>
                    $audit['audit_summary']['passed_checks']
                    ?? 0,

                'failed_checks' =>
                    $audit['audit_summary']['failed_checks']
                    ?? 0,

                'management_status' =>
                    $audit['management_status']
                    ?? null,
            ],

            '69.9_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_69_ready_for_closure' =>
                    $step69ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 69 AI Governance Strategic Plan Controlled Activation Authorization Intelligence architecture has been validated.',

            'Current controlled activation authorization state is '
                .($activationState['controlled_activation_authorization_state'] ?? 'UNKNOWN').'.',

            'Current activation readiness is '
                .($activationState['activation_readiness'] ?? 'UNKNOWN')
                .' with score '
                .$activationReadinessScore.'.',

            'Current controlled activation risk is '
                .($activationState['activation_risk_level'] ?? 'UNKNOWN')
                .' with score '
                .$activationRiskScore.'.',

            "{$authorizationBlockers} controlled activation authorization blocker(s) remain.",

            "{$executionBlockers} controlled activation execution blocker(s) remain.",

            "{$materialRestrictions} material controlled activation restriction(s) remain.",

            "{$criticalRestrictions} critical controlled activation restriction(s) remain.",

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedPressureScore.'.',

            'Current activation authorization completion eligibility is '
                .($eligibilityState['controlled_activation_authorization_completion_eligibility'] ?? 'UNKNOWN').'.',

            'Current activation execution review eligibility is '
                .($eligibilityState['activation_execution_review_eligibility'] ?? 'UNKNOWN').'.',

            'Current activation authorization safety status is '
                .($safetyState['activation_authorization_safety_status'] ?? 'UNKNOWN')
                .' with score '
                .$safetyScore.'.',

            'Current recommendation status is '
                .($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',

            'Current executive controlled activation authorization status is '
                .($executiveState['executive_controlled_activation_authorization_status'] ?? 'UNKNOWN').'.',

            'Current executive controlled activation authorization readiness is '
                .($executiveState['executive_controlled_activation_authorization_readiness'] ?? 'UNKNOWN')
                .' with score '
                .$executiveScore.'.',

            'Source governance confirmation decision recorded is '
                .($sourceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source governance confirmation made by authorized human is '
                .($sourceConfirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance confirmation completed is '
                .($sourceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorization decision recorded is '
                .($authorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Controlled activation authorization made by authorized human is '
                .($authorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Controlled activation authorization completed is '
                .($authorizationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation execution authorized is '
                .($executionAuthorized ? 'YES' : 'NO').'.',

            'Controlled activation execution attribution complete is '
                .($executionAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation authorization intelligence remains advisory, informational, human governed, and isolated from autonomous execution authority.',

            'No autonomous activation authorization, activation execution, AI modification, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Step 69 Guardrails
        |--------------------------------------------------------------------------
        */

        $step69Guardrails = [
            'controlled_activation_authorization_intelligence_enabled' =>
                true,

            'controlled_activation_authorization_registry_enabled' =>
                true,

            'controlled_activation_authorization_preparation_enabled' =>
                true,

            'controlled_activation_authorization_state_intelligence_enabled' =>
                true,

            'controlled_activation_authorization_condition_restriction_intelligence_enabled' =>
                true,

            'controlled_activation_authorization_eligibility_safety_intelligence_enabled' =>
                true,

            'controlled_activation_authorization_recommendation_intelligence_enabled' =>
                true,

            'executive_controlled_activation_authorization_intelligence_enabled' =>
                true,

            'controlled_activation_authorization_audit_enabled' =>
                true,

            'autonomous_activation_authorization_enabled' =>
                false,

            'autonomous_activation_execution_authorization_enabled' =>
                false,

            'autonomous_strategic_plan_activation_enabled' =>
                false,

            'automatic_confirmation_allowed' =>
                false,

            'automatic_final_decision_allowed' =>
                false,

            'automatic_approval_allowed' =>
                false,

            'automatic_rejection_allowed' =>
                false,

            'automatic_conditional_approval_allowed' =>
                false,

            'automatic_deferral_allowed' =>
                false,

            'automatic_risk_acceptance_allowed' =>
                false,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_condition_resolution_allowed' =>
                false,

            'automatic_restriction_removal_allowed' =>
                false,

            'automatic_evidence_validation_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'automatic_deployment_allowed' =>
                false,

            'automatic_rollback_allowed' =>
                false,

            'automatic_clinical_action_allowed' =>
                false,

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'activation_execution_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'message' =>
                'Step 69 establishes explicitly authorized-human-governed strategic plan controlled activation authorization intelligence. The architecture may prepare an authorization record; evaluate activation authorization state, confirmation dependencies, conditions, restrictions, evidence, readiness, risk, eligibility, safety, recommendations, executive state, authorizer attribution, execution-authorizer attribution, and governance-control integrity. It does not autonomously make or record controlled activation authorization, complete activation authorization, authorize activation execution, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, deploy updates, execute changes, trigger rollback, or initiate clinical action. Controlled activation authorization and activation execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_69_ready_for_closure' =>
                $step69ReadyForClosure,

            'controlled_activation_authorization_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_CONTROLLED_ACTIVATION_AUTHORIZATION_INTELLIGENCE',

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->id,

            'activation_authorization_code' =>
                $authorization->activation_authorization_code,

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
                $completionMessage,

            'validation_summary' => [
                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'warning_count' =>
                    count($warnings),

                'critical_issue_count' =>
                    count($criticalIssues),
            ],

            'checks' =>
                $checks,

            'controlled_activation_authorization_context' => [
                'activation_authorization_status' =>
                    $authorization->activation_authorization_status,

                'activation_authorization_mode' =>
                    $authorization->activation_authorization_mode,

                'controlled_activation_authorization_decision' =>
                    $authorization->controlled_activation_authorization_decision,

                'controlled_activation_authorization_state' =>
                    $activationState['controlled_activation_authorization_state']
                    ?? null,

                'activation_readiness' =>
                    $activationState['activation_readiness']
                    ?? null,

                'activation_readiness_score' =>
                    $activationReadinessScore,

                'activation_risk_level' =>
                    $activationState['activation_risk_level']
                    ?? null,

                'activation_risk_score' =>
                    $activationRiskScore,

                'activation_authorization_eligibility_score' =>
                    $eligibilityScore,

                'activation_authorization_safety_status' =>
                    $safetyState['activation_authorization_safety_status']
                    ?? null,

                'activation_authorization_safety_score' =>
                    $safetyScore,

                'blocking_activation_authorization_conditions' =>
                    $authorizationBlockers,

                'blocking_activation_execution_conditions' =>
                    $executionBlockers,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,

                'source_confirmation_decision_recorded' =>
                    $sourceConfirmationDecisionRecorded,

                'source_confirmation_made_by_authorized_human' =>
                    $sourceConfirmationMadeByAuthorizedHuman,

                'source_governance_decision_confirmation_completed' =>
                    $sourceConfirmationCompleted,

                'authorization_decision_recorded' =>
                    $authorizationDecisionRecorded,

                'activation_authorization_made_by_authorized_human' =>
                    $authorizationMadeByAuthorizedHuman,

                'authorization_attribution_complete' =>
                    $authorizationAttributionComplete,

                'controlled_activation_authorization_completed' =>
                    $authorizationCompleted,

                'controlled_activation_execution_authorized' =>
                    $executionAuthorized,

                'execution_authorization_attribution_complete' =>
                    $executionAttributionComplete,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_69_guardrails' =>
                $step69Guardrails,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Score Helper
    |--------------------------------------------------------------------------
    */

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }
}