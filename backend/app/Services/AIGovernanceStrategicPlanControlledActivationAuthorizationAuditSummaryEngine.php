<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationAuditSummaryEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Authorization
        |--------------------------------------------------------------------------
        */

        $authorization = $authorizationId
            ? AIGovernanceStrategicPlanControlledActivationAuthorization::find($authorizationId)
            : AIGovernanceStrategicPlanControlledActivationAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_CONTROLLED_ACTIVATION_AUTHORIZATION_AVAILABLE',
                'message' =>
                    'No strategic plan controlled activation authorization record is available for Step 69 audit.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 69 Intelligence Engines
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

        /*
        |--------------------------------------------------------------------------
        | Analyze
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($authorization->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($authorization->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($authorization->id);

        $recommendation =
            $recommendationEngine->analyze($authorization->id);

        $executive =
            $executiveEngine->analyze($authorization->id);

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
        | Core Values
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
        | Counts / Scores
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

        $criticalOpenConditions =
            (int) (
                $conditionState['critical_open_conditions']
                ?? 0
            );

        $activationReadinessScore =
            $this->score(
                $activationState['activation_readiness_score'] ?? 0
            );

        $activationRiskScore =
            $this->score(
                $activationState['activation_risk_score'] ?? 0
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
                $conditionState['combined_resolution_score'] ?? 0
            );

        $combinedPressureScore =
            $this->score(
                $conditionState['combined_condition_restriction_pressure_score']
                ?? 0
            );

        $executiveScore =
            $this->score(
                $executiveState['executive_controlled_activation_authorization_score']
                ?? 0
            );

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

        /*
        |--------------------------------------------------------------------------
        | Audit Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['controlled_activation_authorization_available'] = [
            'passed' => true,
            'message' =>
                'Controlled activation authorization record is available.',
        ];

        $checks['activation_authorization_state_intelligence_available'] = [
            'passed' =>
                (bool) ($state['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization state intelligence is available.',
        ];

        $checks['condition_restriction_intelligence_available'] = [
            'passed' =>
                (bool) ($conditionRestriction['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization condition and restriction intelligence is available.',
        ];

        $checks['eligibility_safety_intelligence_available'] = [
            'passed' =>
                (bool) ($eligibilitySafety['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization eligibility and safety intelligence is available.',
        ];

        $checks['recommendation_intelligence_available'] = [
            'passed' =>
                (bool) ($recommendation['analysis_completed'] ?? false),

            'message' =>
                'Controlled activation authorization recommendation intelligence is available.',
        ];

        $checks['executive_intelligence_available'] = [
            'passed' =>
                (bool) ($executive['analysis_completed'] ?? false),

            'message' =>
                'Executive controlled activation authorization intelligence is available.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' =>
                $governanceIntegrityIntact,

            'message' =>
                'Controlled activation authorization governance integrity remains intact.',
        ];

        $checks['no_critical_open_activation_condition'] = [
            'passed' =>
                $criticalOpenConditions === 0,

            'value' =>
                $criticalOpenConditions,

            'message' =>
                'No critical controlled activation authorization condition should remain open.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Restrictions Are State, Not Architecture Failure
        |--------------------------------------------------------------------------
        |
        | A critical restriction can legitimately exist while the system safely
        | blocks progression. Therefore the audit validates that critical
        | restrictions trigger human intervention rather than requiring zero.
        |
        */

        $checks['critical_restriction_safety_response'] = [
            'passed' =>
                $criticalRestrictions === 0
                || (
                    ($safetyState['immediate_human_intervention_required'] ?? false) === true
                    && ($eligibilityState['activation_progression_blocked'] ?? false) === true
                ),

            'value' =>
                $criticalRestrictions,

            'message' =>
                'Critical controlled activation restrictions must block progression and require authorized-human governance intervention.',
        ];

        $checks['automatic_activation_authorization_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_activation_authorization_allowed === false
                && (bool) $authorization->automatic_activation_allowed === false
                && (bool) $authorization->automatic_execution_allowed === false
                && (bool) $authorization->automatic_condition_resolution_allowed === false
                && (bool) $authorization->automatic_restriction_removal_allowed === false
                && (bool) $authorization->automatic_evidence_validation_allowed === false
                && (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,

            'message' =>
                'Automatic activation authorization, activation, execution, condition resolution, restriction removal, evidence validation, AI change, deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $authorization->human_review_required === true
                && (bool) $authorization->governance_confirmation_required === true
                && (bool) $authorization->authorized_human_activation_authorization_required === true
                && (bool) $authorization->authorized_human_activation_execution_required === true,

            'message' =>
                'Human review, governance confirmation, activation authorization, and execution authorization requirements remain enabled.',
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
                && ($stateGuardrails['activation_state_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($stateGuardrails['activation_state_intelligence_activates_strategic_plan'] ?? true) === false,

            'message' =>
                'Controlled activation authorization state intelligence remains informational and isolated from activation authorization and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Isolation
        |--------------------------------------------------------------------------
        */

        $checks['condition_restriction_authority_isolation'] = [
            'passed' =>
                ($conditionGuardrails['condition_restriction_intelligence_is_activation_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_is_activation_execution_authorization'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_resolves_conditions'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_removes_restrictions'] ?? true) === false
                && ($conditionGuardrails['condition_restriction_intelligence_authorizes_execution'] ?? true) === false,

            'message' =>
                'Condition and restriction intelligence remains advisory and isolated from resolution, activation authorization, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilityGuardrails['eligibility_safety_intelligence_is_activation_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_is_activation_execution_authorization'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_makes_activation_authorization_decision'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_safety_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_score_authorizes_activation'] ?? true) === false
                && ($eligibilityGuardrails['safety_score_authorizes_activation'] ?? true) === false,

            'message' =>
                'Eligibility and safety intelligence remains isolated from activation authorization and execution authority.',
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
                && ($executiveGuardrails['executive_intelligence_authorizes_activation_execution'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_activation'] ?? true) === false
                && ($executiveGuardrails['executive_score_authorizes_execution'] ?? true) === false,

            'message' =>
                'Executive controlled activation intelligence remains informational and isolated from activation authorization and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Authority Protection
        |--------------------------------------------------------------------------
        */

        $checks['activation_authorization_authority_reserved_for_authorized_human'] = [
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
                'Completed controlled activation authorization must remain attributable to an explicitly authorized human governance authorizer.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Authority Protection
        |--------------------------------------------------------------------------
        */

        $checks['activation_execution_authority_reserved_for_authorized_human'] = [
            'passed' =>
                !$executionAuthorized
                || $executionAttributionComplete,

            'value' =>
                $executionAuthorized,

            'message' =>
                'Controlled activation execution authorization must remain separately attributable to explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation Integrity
        |--------------------------------------------------------------------------
        */

        $checks['source_governance_confirmation_sequence_integrity'] = [
            'passed' =>
                !$authorizationCompleted
                || (
                    $sourceConfirmationDecisionRecorded
                    && $sourceConfirmationMadeByAuthorizedHuman
                    && $sourceConfirmationCompleted
                ),

            'message' =>
                'Completed activation authorization must follow an explicitly recorded and completed authorized-human governance confirmation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Decision Attribution
        |--------------------------------------------------------------------------
        */

        $checks['activation_authorization_attribution_integrity'] = [
            'passed' =>
                !$authorizationCompleted
                || $authorizationAttributionComplete,

            'value' =>
                $authorizationAttributionComplete,

            'message' =>
                'Completed controlled activation authorization must include complete human authorizer attribution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $checks['activation_execution_sequence_integrity'] = [
            'passed' =>
                !$executionAuthorized
                || (
                    $authorizationCompleted
                    && $authorizationAttributionComplete
                    && $executionAttributionComplete
                ),

            'message' =>
                'Controlled activation execution authorization must follow completed authorized-human activation authorization and remain separately attributable.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Separation
        |--------------------------------------------------------------------------
        */

        $checks['authorization_execution_authority_separation'] = [
            'passed' =>
                !$authorizationCompleted
                || !$executionAuthorized
                || (
                    $authorization->authorized_by !== null
                    && $authorization->execution_authorized_by !== null
                ),

            'message' =>
                'Activation authorization and activation execution remain distinct governed authority stages.',
        ];

        /*
        |--------------------------------------------------------------------------
        | AI Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['ai_change_execution_isolation'] = [
            'passed' =>
                (bool) $authorization->automatic_change_allowed === false
                && (bool) $authorization->automatic_deployment_allowed === false
                && (bool) $authorization->automatic_rollback_allowed === false
                && (bool) $authorization->automatic_clinical_action_allowed === false,

            'message' =>
                'Controlled activation authorization does not authorize autonomous AI modification, deployment, rollback, or clinical action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Count Results
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
        | Audit Status
        |--------------------------------------------------------------------------
        */

        $auditStatus =
            $failedChecks === 0
                ? 'COMPLETE'
                : 'FAILED';

        /*
        |--------------------------------------------------------------------------
        | Management Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {
            $managementStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_GOVERNANCE_CONTROL_FAILURE';
        } elseif (!$governanceIntegrityIntact) {
            $managementStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_GOVERNANCE_INTEGRITY_FAILURE';
        } elseif (
            $criticalRestrictions > 0
            || $activationRiskScore >= 90
        ) {
            $managementStatus =
                'CRITICAL_CONTROLLED_ACTIVATION_AUTHORIZATION_WORK_REMAINS';
        } elseif (
            $authorizationBlockers > 0
            || $executionBlockers > 0
            || $materialRestrictions > 0
        ) {
            $managementStatus =
                'ELEVATED_CONTROLLED_ACTIVATION_AUTHORIZATION_WORK_REMAINS';
        } elseif (!$authorizationCompleted) {
            $managementStatus =
                'AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_PENDING';
        } elseif (!$executionAuthorized) {
            $managementStatus =
                'AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION_PENDING';
        } else {
            $managementStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_GOVERNANCE_STATE_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            'Step 69 controlled activation authorization audit completed '
                .count($checks)
                .' integrity check(s), with '
                .$passedChecks
                .' passed and '
                .$failedChecks
                .' failed.',

            "Controlled activation authorization audit is based on authorization record {$authorization->id}.",

            'Current activation authorization state is '
                .($activationState['controlled_activation_authorization_state'] ?? 'UNKNOWN').'.',

            'Current activation readiness is '
                .($activationState['activation_readiness'] ?? 'UNKNOWN')
                .' with score '
                .$activationReadinessScore.'.',

            'Current controlled activation risk is '
                .($activationState['activation_risk_level'] ?? 'UNKNOWN')
                .' with score '
                .$activationRiskScore.'.',

            'Current activation authorization eligibility score is '
                .$eligibilityScore.'.',

            'Current activation authorization safety status is '
                .($safetyState['activation_authorization_safety_status'] ?? 'UNKNOWN')
                .' with score '
                .$safetyScore.'.',

            "{$authorizationBlockers} activation authorization blocker(s) remain.",

            "{$executionBlockers} activation execution blocker(s) remain.",

            "{$materialRestrictions} material controlled activation restriction(s) remain.",

            "{$criticalRestrictions} critical controlled activation restriction(s) remain.",

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedPressureScore.'.',

            'Current recommendation status is '
                .($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',

            'Top recommendation is '
                .($recommendationState['top_recommendation_code'] ?? 'NONE').'.',

            'Current executive controlled activation status is '
                .($executiveState['executive_controlled_activation_authorization_status'] ?? 'UNKNOWN').'.',

            'Current executive controlled activation readiness is '
                .($executiveState['executive_controlled_activation_authorization_readiness'] ?? 'UNKNOWN')
                .' with score '
                .$executiveScore.'.',

            'Source governance confirmation decision recorded is '
                .($sourceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source governance confirmation completed is '
                .($sourceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorization decision recorded is '
                .($authorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Controlled activation authorization made by authorized human is '
                .($authorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Controlled activation authorization attribution complete is '
                .($authorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation authorization completed is '
                .($authorizationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation execution authorized is '
                .($executionAuthorized ? 'YES' : 'NO').'.',

            'Controlled activation execution attribution complete is '
                .($executionAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            'Step 69 controlled activation authorization integrity controls currently contain '
                .$failedChecks
                .' failure(s).',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        foreach (
            array_slice(
                $recommendation['management_priorities'] ?? [],
                0,
                10
            ) as $priority
        ) {
            $managementPriorities[] = $priority;
        }

        if (!$sourceConfirmationDecisionRecorded) {
            $managementPriorities[] =
                'Record the required authorized-human governance confirmation decision before controlled activation authorization progression.';
        }

        if (!$sourceConfirmationCompleted) {
            $managementPriorities[] =
                'Complete authorized-human governance confirmation before activation authorization completion.';
        }

        if ($authorizationBlockers > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$authorizationBlockers} activation authorization blocker(s).";
        }

        if ($executionBlockers > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$executionBlockers} activation execution blocker(s).";
        }

        if ($criticalRestrictions > 0) {
            $managementPriorities[] =
                "Maintain immediate authorized-human governance review for {$criticalRestrictions} critical restriction(s).";
        }

        if (!$authorizationCompleted) {
            $managementPriorities[] =
                'Keep controlled activation authorization incomplete until all authorized-human governance requirements are satisfied.';
        }

        if (!$executionAuthorized) {
            $managementPriorities[] =
                'Keep activation execution prohibited until separately authorized by explicitly authorized human governance.';
        }

        $managementPriorities[] =
            'Preserve strict separation between governance confirmation, activation authorization, activation execution authorization, recommendation intelligence, eligibility intelligence, safety intelligence, and executive intelligence.';

        $managementPriorities[] =
            'Preserve human governance authority, evidence traceability, authorizer attribution, execution attribution, safety isolation, and governance-control integrity throughout activation progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'audit_available' =>
                true,

            'audit_status' =>
                $auditStatus,

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

            'management_status' =>
                $managementStatus,

            'audit_summary' => [
                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            'activation_authorization_summary' => [
                'activation_authorization_status' =>
                    $authorization->activation_authorization_status,

                'activation_authorization_mode' =>
                    $authorization->activation_authorization_mode,

                'controlled_activation_authorization_decision' =>
                    $authorization->controlled_activation_authorization_decision,

                'activation_readiness' =>
                    $activationState['activation_readiness'] ?? null,

                'activation_readiness_score' =>
                    $activationReadinessScore,

                'activation_risk_level' =>
                    $activationState['activation_risk_level'] ?? null,

                'activation_risk_score' =>
                    $activationRiskScore,

                'authorization_decision_recorded' =>
                    $authorizationDecisionRecorded,

                'activation_authorization_made_by_authorized_human' =>
                    $authorizationMadeByAuthorizedHuman,

                'authorization_attribution_complete' =>
                    $authorizationAttributionComplete,

                'controlled_activation_authorization_completed' =>
                    $authorizationCompleted,
            ],

            'condition_restriction_summary' => [
                'blocking_activation_authorization_conditions' =>
                    $authorizationBlockers,

                'blocking_activation_execution_conditions' =>
                    $executionBlockers,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,
            ],

            'eligibility_safety_summary' => [
                'authorized_human_activation_authorization_review_eligibility' =>
                    $eligibilityState['authorized_human_activation_authorization_review_eligibility']
                    ?? null,

                'controlled_activation_authorization_completion_eligibility' =>
                    $eligibilityState['controlled_activation_authorization_completion_eligibility']
                    ?? null,

                'unrestricted_controlled_activation_authorization_eligibility' =>
                    $eligibilityState['unrestricted_controlled_activation_authorization_eligibility']
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

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,
            ],

            'recommendation_summary' => [
                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,

                'total_recommendations' =>
                    $recommendationState['total_recommendations']
                    ?? 0,

                'critical_recommendations' =>
                    $recommendationState['critical_recommendations']
                    ?? 0,

                'high_recommendations' =>
                    $recommendationState['high_recommendations']
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationState['top_recommendation_code']
                    ?? null,

                'top_recommended_activation_path' =>
                    $recommendationState['top_recommended_activation_path']
                    ?? null,
            ],

            'executive_summary' => [
                'executive_controlled_activation_authorization_status' =>
                    $executiveState['executive_controlled_activation_authorization_status']
                    ?? null,

                'executive_controlled_activation_authorization_readiness' =>
                    $executiveState['executive_controlled_activation_authorization_readiness']
                    ?? null,

                'executive_controlled_activation_authorization_confidence' =>
                    $executiveState['executive_controlled_activation_authorization_confidence']
                    ?? null,

                'executive_controlled_activation_authorization_score' =>
                    $executiveScore,

                'executive_decision_posture' =>
                    $executiveState['executive_decision_posture']
                    ?? null,

                'human_management_attention_required' =>
                    $executiveState['human_management_attention_required']
                    ?? true,

                'immediate_human_intervention_required' =>
                    $executiveState['immediate_human_intervention_required']
                    ?? false,
            ],

            'authorization_execution_summary' => [
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

            'integrity_summary' => [
                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'automatic_activation_authorization_allowed' =>
                    (bool) $authorization->automatic_activation_authorization_allowed,

                'automatic_activation_allowed' =>
                    (bool) $authorization->automatic_activation_allowed,

                'automatic_execution_allowed' =>
                    (bool) $authorization->automatic_execution_allowed,

                'automatic_condition_resolution_allowed' =>
                    (bool) $authorization->automatic_condition_resolution_allowed,

                'automatic_restriction_removal_allowed' =>
                    (bool) $authorization->automatic_restriction_removal_allowed,

                'automatic_evidence_validation_allowed' =>
                    (bool) $authorization->automatic_evidence_validation_allowed,

                'automatic_change_allowed' =>
                    (bool) $authorization->automatic_change_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $authorization->automatic_deployment_allowed,

                'automatic_rollback_allowed' =>
                    (bool) $authorization->automatic_rollback_allowed,

                'automatic_clinical_action_allowed' =>
                    (bool) $authorization->automatic_clinical_action_allowed,

                'human_review_required' =>
                    (bool) $authorization->human_review_required,

                'governance_confirmation_required' =>
                    (bool) $authorization->governance_confirmation_required,

                'authorized_human_activation_authorization_required' =>
                    (bool) $authorization->authorized_human_activation_authorization_required,

                'authorized_human_activation_execution_required' =>
                    (bool) $authorization->authorized_human_activation_execution_required,
            ],

            'audit_findings' =>
                $auditFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'audit_guardrails' =>
                $this->guardrails(),
        ];
    }

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

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_audit_enabled' =>
                true,

            'audit_is_activation_authorization' =>
                false,

            'audit_is_activation_execution_authorization' =>
                false,

            'audit_makes_activation_authorization_decision' =>
                false,

            'audit_records_activation_authorization_decision' =>
                false,

            'audit_completes_activation_authorization' =>
                false,

            'audit_authorizes_activation_execution' =>
                false,

            'audit_activates_strategic_plan' =>
                false,

            'audit_changes_governance_confirmation' =>
                false,

            'audit_changes_activation_authorization_status' =>
                false,

            'audit_changes_activation_authorization_decision' =>
                false,

            'audit_changes_activation_authorization_outcome' =>
                false,

            'audit_changes_execution_status' =>
                false,

            'audit_resolves_conditions' =>
                false,

            'audit_waives_conditions' =>
                false,

            'audit_removes_restrictions' =>
                false,

            'audit_validates_evidence' =>
                false,

            'audit_completes_authorization_attribution' =>
                false,

            'audit_completes_execution_attribution' =>
                false,

            'audit_authorizes_ai_change' =>
                false,

            'audit_authorizes_execution' =>
                false,

            'audit_authorizes_deployment' =>
                false,

            'audit_authorizes_rollback' =>
                false,

            'audit_authorizes_clinical_action' =>
                false,

            'audit_overrides_human_review' =>
                false,

            'audit_overrides_governance_confirmation' =>
                false,

            'audit_overrides_activation_authorization' =>
                false,

            'audit_overrides_execution_authorization' =>
                false,

            'audit_overrides_evidence_requirements' =>
                false,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,

            'automatic_condition_resolution_allowed' =>
                false,

            'automatic_restriction_removal_allowed' =>
                false,

            'automatic_evidence_validation_allowed' =>
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

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'message' =>
                'Step 69.8 controlled activation authorization audit consolidates preparation, state intelligence, condition and restriction intelligence, eligibility and safety intelligence, recommendation intelligence, executive intelligence, governance-confirmation state, activation-authorization attribution, execution-authorization attribution, and authority-isolation controls for authorized human governance audit only. The audit does not make or record an activation authorization decision, complete activation authorization, authorize activation execution, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, alter governance confirmation, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Controlled activation authorization and execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}