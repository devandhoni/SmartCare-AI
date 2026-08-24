<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationEligibilitySafetyIntelligenceEngine
{
    /**
     * Step 68.5
     *
     * Strategic Plan Governance Decision Confirmation
     * Eligibility & Safety Intelligence
     *
     * Evaluates:
     * - authorized-human confirmation-review eligibility
     * - completed governance-confirmation eligibility
     * - controlled-activation consideration eligibility
     * - unresolved confirmation conditions/restrictions
     * - source final-governance decision requirements
     * - governance-validation requirements
     * - confirmation attribution requirements
     * - activation authorization requirements
     * - confirmation and activation safety
     *
     * This engine is advisory and informational only.
     */
    public function analyze(?int $confirmationId = null): array
    {
        $confirmation = $confirmationId
            ? AIGovernanceStrategicPlanGovernanceDecisionConfirmation::find($confirmationId)
            : AIGovernanceStrategicPlanGovernanceDecisionConfirmation::latest('id')->first();

        if (!$confirmation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_GOVERNANCE_DECISION_CONFIRMATION_AVAILABLE',
                'message' => 'No strategic plan governance decision confirmation record is available for eligibility and safety intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 68 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationStateIntelligenceEngine::class
        );

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationConditionRestrictionIntelligenceEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($confirmation->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($confirmation->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $confirmationState =
            $state['confirmation_state'] ?? [];

        $conditionContext =
            $conditionRestriction['condition_summary']
            ?? $state['condition_context']
            ?? [];

        $restrictionContext =
            $conditionRestriction['restriction_summary']
            ?? $state['restriction_context']
            ?? [];

        $evidenceContext =
            $conditionRestriction['evidence_summary']
            ?? $state['evidence_context']
            ?? [];

        $confirmationAttributionContext =
            $state['confirmation_attribution_context'] ?? [];

        $activationContext =
            $state['activation_context'] ?? [];

        $completionContext =
            $state['completion_context'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $sourceFinalGovernanceDecisionContext =
            $conditionRestriction['source_final_governance_decision_context']
            ?? [];

        $governanceValidationBarrierContext =
            $conditionRestriction['governance_validation_barrier_context']
            ?? [];

        $riskContext =
            $conditionRestriction['risk_context'] ?? [];

        $confirmationAuthorityContext =
            $conditionRestriction['confirmation_authority_context']
            ?? [];

        $controlledActivationContext =
            $conditionRestriction['controlled_activation_context']
            ?? [];

        $reviewContext =
            $state['review_context']
            ?? $this->normalizeArray($confirmation->review_context);

        $validationContext =
            $state['validation_context']
            ?? $this->normalizeArray($confirmation->validation_context);

        $governanceContext =
            $state['governance_context']
            ?? $this->normalizeArray($confirmation->governance_context);

        $sourceContext =
            $state['source_context']
            ?? $this->normalizeArray($confirmation->source_context);

        /*
        |--------------------------------------------------------------------------
        | Core Authority State
        |--------------------------------------------------------------------------
        */

        $sourceFinalGovernanceDecisionRecorded =
            (bool) $confirmation->source_final_governance_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $confirmation->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $confirmation->source_governance_validation_completed;

        $governanceConfirmationDecisionRecorded =
            filled($confirmation->governance_confirmation_decision);

        $confirmationMadeByAuthorizedHuman =
            (bool) $confirmation->confirmation_made_by_authorized_human;

        $confirmationCompleted =
            (bool) $confirmation->governance_decision_confirmation_completed;

        $controlledActivationAuthorized =
            (bool) $confirmation->controlled_activation_authorized;

        $confirmationAttributionComplete =
            ($confirmationAttributionContext['confirmation_attribution_complete'] ?? false)
            === true;

        $activationAuthorizerIdentified =
            filled($confirmation->activation_authorized_by);

        $activationAuthorizerRoleAvailable =
            filled($confirmation->activation_authorizer_role);

        $activationAuthorizationTimestampAvailable =
            !empty($confirmation->activation_authorized_at);

        $activationAuthorizationCodeAvailable =
            filled($confirmation->controlled_activation_authorization_code);

        $activationAttributionComplete =
            $activationAuthorizerIdentified
            && $activationAuthorizerRoleAvailable
            && $activationAuthorizationTimestampAvailable
            && $activationAuthorizationCodeAvailable
            && $controlledActivationAuthorized;

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) ($conditionContext['total_conditions'] ?? 0);

        $openConditions =
            (int) ($conditionContext['open_conditions'] ?? 0);

        $blockingConfirmationConditions =
            (int) (
                $conditionContext['blocking_confirmation_conditions']
                ?? $conditionRestrictionState['blocking_confirmation_conditions']
                ?? 0
            );

        $blockingActivationConditions =
            (int) (
                $conditionContext['blocking_activation_conditions']
                ?? $conditionRestrictionState['blocking_activation_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) ($conditionContext['constraining_conditions'] ?? 0);

        $criticalOpenConditions =
            (int) (
                $conditionContext['critical_open_conditions']
                ?? $conditionRestrictionState['critical_open_conditions']
                ?? 0
            );

        $totalRestrictions =
            (int) ($restrictionContext['total_restrictions'] ?? 0);

        $materialRestrictions =
            (int) (
                $restrictionContext['material_restrictions']
                ?? $conditionRestrictionState['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionContext['critical_restrictions']
                ?? $conditionRestrictionState['critical_restrictions']
                ?? 0
            );

        $outstandingEvidenceItems =
            (int) ($evidenceContext['outstanding_evidence_items'] ?? 0);

        $blockingConfirmationEvidenceItems =
            (int) (
                $evidenceContext['blocking_confirmation_evidence_items']
                ?? 0
            );

        $blockingActivationEvidenceItems =
            (int) (
                $evidenceContext['blocking_activation_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidenceItems =
            (int) (
                $evidenceContext['critical_outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $confirmationReadinessScore =
            $this->score(
                $confirmationState['confirmation_readiness_score']
                ?? $confirmation->confirmation_readiness_score
            );

        $confirmationRiskScore =
            $this->score(
                $confirmationState['confirmation_risk_score']
                ?? $confirmation->confirmation_risk_score
            );

        $conditionResolutionScore =
            $this->score(
                $conditionContext['condition_resolution_score']
                ?? $confirmation->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $evidenceContext['evidence_resolution_score']
                ?? $confirmation->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $evidenceContext['combined_resolution_score']
                ?? $confirmation->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $conditionContext['condition_pressure_score']
                ?? $confirmation->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $restrictionContext['restriction_pressure_score']
                ?? $confirmation->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $restrictionContext['combined_condition_restriction_pressure_score']
                ?? $confirmation->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility Checks
        |--------------------------------------------------------------------------
        */

        $eligibilityChecks = [];

        $eligibilityChecks['confirmation_record_available'] = [
            'passed' => true,
            'message' => 'Strategic plan governance decision confirmation record is available.',
        ];

        $eligibilityChecks['source_final_governance_decision_available'] = [
            'passed' => $sourceFinalGovernanceDecisionRecorded,
            'value' => $sourceFinalGovernanceDecisionRecorded,
            'message' => 'An explicitly authorized human final governance decision must be recorded before completed governance confirmation.',
        ];

        $eligibilityChecks['source_final_decision_authorized_human_attribution'] = [
            'passed' => $sourceDecisionMadeByAuthorizedHuman,
            'value' => $sourceDecisionMadeByAuthorizedHuman,
            'message' => 'The source final governance decision must remain attributable to an explicitly authorized human governance decision-maker.',
        ];

        $eligibilityChecks['source_governance_validation_completed'] = [
            'passed' => $sourceGovernanceValidationCompleted,
            'value' => $sourceGovernanceValidationCompleted,
            'message' => 'Required source governance validation must be completed before unrestricted governance confirmation.',
        ];

        $eligibilityChecks['blocking_confirmation_conditions_absent'] = [
            'passed' => $blockingConfirmationConditions === 0,
            'value' => $blockingConfirmationConditions,
            'message' => 'No blocking governance-confirmation condition should remain before completed governance confirmation.',
        ];

        $eligibilityChecks['critical_confirmation_conditions_absent'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical governance-confirmation condition should remain open.',
        ];

        $eligibilityChecks['critical_confirmation_restrictions_absent'] = [
            'passed' => $criticalRestrictions === 0,
            'value' => $criticalRestrictions,
            'message' => 'No critical governance-confirmation restriction should remain active.',
        ];

        $eligibilityChecks['blocking_confirmation_evidence_absent'] = [
            'passed' => $blockingConfirmationEvidenceItems === 0,
            'value' => $blockingConfirmationEvidenceItems,
            'message' => 'No evidence requirement should remain that blocks completed governance confirmation.',
        ];

        $eligibilityChecks['critical_outstanding_confirmation_evidence_absent'] = [
            'passed' => $criticalOutstandingEvidenceItems === 0,
            'value' => $criticalOutstandingEvidenceItems,
            'message' => 'No critical governance-confirmation evidence requirement should remain outstanding.',
        ];

        $eligibilityChecks['confirmation_attribution_available'] = [
            'passed' =>
                !$confirmationCompleted
                || $confirmationAttributionComplete,

            'value' =>
                $confirmationAttributionComplete,

            'message' => 'Completed governance confirmation must remain attributable to an explicitly identified authorized human governance confirmer.',
        ];

        $eligibilityChecks['confirmation_decision_available_if_completed'] = [
            'passed' =>
                !$confirmationCompleted
                || $governanceConfirmationDecisionRecorded,

            'value' =>
                $governanceConfirmationDecisionRecorded,

            'message' => 'Completed governance confirmation must include an explicit authorized-human governance confirmation decision.',
        ];

        $eligibilityChecks['confirmation_made_by_authorized_human_if_completed'] = [
            'passed' =>
                !$confirmationCompleted
                || $confirmationMadeByAuthorizedHuman,

            'value' =>
                $confirmationMadeByAuthorizedHuman,

            'message' => 'Completed governance confirmation must be explicitly made by authorized human governance.',
        ];

        $eligibilityChecks['decision_risk_acceptable_for_unrestricted_confirmation'] = [
            'passed' => $confirmationRiskScore < 70,
            'value' => $confirmationRiskScore,
            'message' => 'Governance-confirmation risk should be below the high-risk threshold before unrestricted confirmation is considered.',
        ];

        $eligibilityChecks['controlled_activation_prerequisites_satisfied'] = [
            'passed' =>
                $confirmationCompleted
                && $confirmationAttributionComplete
                && $blockingActivationConditions === 0
                && $blockingActivationEvidenceItems === 0
                && $materialRestrictions === 0,

            'message' => 'Controlled activation consideration requires completed authorized-human confirmation, complete confirmation attribution, no activation-blocking conditions or evidence, and no material confirmation restrictions.',
        ];

        $eligibilityChecks['controlled_activation_attribution_integrity'] = [
            'passed' =>
                !$controlledActivationAuthorized
                || $activationAttributionComplete,

            'value' =>
                $activationAttributionComplete,

            'message' => 'Any controlled activation authorization must remain attributable to an explicitly identified authorized human activation authority.',
        ];

        $eligibilityChecks['automatic_confirmation_disabled'] = [
            'passed' =>
                !$confirmation->automatic_confirmation_allowed
                && !$confirmation->automatic_final_decision_allowed
                && !$confirmation->automatic_approval_allowed
                && !$confirmation->automatic_rejection_allowed
                && !$confirmation->automatic_conditional_approval_allowed
                && !$confirmation->automatic_deferral_allowed
                && !$confirmation->automatic_risk_acceptance_allowed
                && !$confirmation->automatic_activation_allowed
                && !$confirmation->automatic_condition_resolution_allowed
                && !$confirmation->automatic_restriction_removal_allowed
                && !$confirmation->automatic_evidence_validation_allowed
                && !$confirmation->automatic_execution_allowed
                && !$confirmation->automatic_change_allowed
                && !$confirmation->automatic_deployment_allowed
                && !$confirmation->automatic_rollback_allowed
                && !$confirmation->automatic_clinical_action_allowed,

            'message' => 'Automatic confirmation, governance decision, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, restriction removal, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        $eligibilityChecks['human_confirmation_authority_protected'] = [
            'passed' =>
                (bool) $confirmation->human_review_required
                && (bool) $confirmation->authorized_human_confirmation_required,

            'message' => 'Governance confirmation authority remains reserved for explicitly authorized human governance.',
        ];

        $eligibilityChecks['human_activation_authority_protected'] = [
            'passed' =>
                (bool) $confirmation->authorized_human_activation_required
                && !$confirmation->automatic_activation_allowed,

            'message' => 'Controlled activation authority remains reserved for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility State
        |--------------------------------------------------------------------------
        */

        $authorizedHumanConfirmationReviewEligible =
            true;

        $completedGovernanceConfirmationEligible =
            $sourceFinalGovernanceDecisionRecorded
            && $sourceDecisionMadeByAuthorizedHuman
            && $sourceGovernanceValidationCompleted
            && $blockingConfirmationConditions === 0
            && $criticalOpenConditions === 0
            && $criticalRestrictions === 0
            && $blockingConfirmationEvidenceItems === 0
            && $criticalOutstandingEvidenceItems === 0;

        $unrestrictedConfirmationEligible =
            $completedGovernanceConfirmationEligible
            && $materialRestrictions === 0
            && $confirmationRiskScore < 70;

        $conditionalConfirmationConsiderationEligible =
            !$confirmationCompleted
            && !$unrestrictedConfirmationEligible
            && $criticalOpenConditions === 0
            && $criticalRestrictions === 0
            && $criticalOutstandingEvidenceItems === 0;

        $confirmationDeferralEligible =
            !$confirmationCompleted;

        $requestAdditionalResolutionEligible =
            !$completedGovernanceConfirmationEligible
            || $blockingConfirmationConditions > 0
            || $materialRestrictions > 0
            || $outstandingEvidenceItems > 0;

        $controlledActivationConsiderationEligible =
            $confirmationCompleted
            && $confirmationAttributionComplete
            && $blockingActivationConditions === 0
            && $blockingActivationEvidenceItems === 0
            && $materialRestrictions === 0
            && $confirmationRiskScore < 70;

        $controlledActivationAuthorizationComplete =
            $controlledActivationAuthorized
            && $activationAttributionComplete;

        /*
        |--------------------------------------------------------------------------
        | Eligibility Score
        |--------------------------------------------------------------------------
        */

        $eligibilityScore =
            $this->calculateEligibilityScore(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $blockingConfirmationConditions,
                $criticalOpenConditions,
                $materialRestrictions,
                $criticalRestrictions,
                $blockingConfirmationEvidenceItems,
                $criticalOutstandingEvidenceItems,
                $confirmationRiskScore,
                $confirmationReadinessScore
            );

        /*
        |--------------------------------------------------------------------------
        | Confirmation Progression
        |--------------------------------------------------------------------------
        */

        $confirmationProgressionBlocked =
            !$completedGovernanceConfirmationEligible;

        $confirmationProgressionReadiness =
            $this->determineConfirmationProgressionReadiness(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $blockingConfirmationConditions,
                $materialRestrictions,
                $confirmationCompleted,
                $confirmationAttributionComplete
            );

        /*
        |--------------------------------------------------------------------------
        | Safety State
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($state['analysis_completed'] ?? false) === true
            && ($conditionRestriction['analysis_completed'] ?? false) === true
            && !$confirmation->automatic_confirmation_allowed
            && !$confirmation->automatic_activation_allowed
            && !$confirmation->automatic_execution_allowed
            && (bool) $confirmation->authorized_human_confirmation_required
            && (bool) $confirmation->authorized_human_activation_required;

        $confirmationSafetyScore =
            $this->calculateSafetyScore(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $blockingConfirmationConditions,
                $blockingActivationConditions,
                $materialRestrictions,
                $criticalOpenConditions,
                $criticalRestrictions,
                $confirmationRiskScore,
                $confirmationCompleted,
                $confirmationAttributionComplete,
                $controlledActivationAuthorized,
                $activationAttributionComplete
            );

        $confirmationSafetyLevel =
            $this->determineSafetyLevel(
                $confirmationSafetyScore,
                $confirmationRiskScore,
                $criticalOpenConditions,
                $criticalRestrictions
            );

        $confirmationSafetyStatus =
            $this->determineSafetyStatus(
                $completedGovernanceConfirmationEligible,
                $controlledActivationConsiderationEligible,
                $confirmationCompleted,
                $materialRestrictions,
                $blockingConfirmationConditions,
                $criticalOpenConditions,
                $criticalRestrictions
            );

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $criticalOutstandingEvidenceItems > 0
            || $confirmationRiskScore >= 95
            || (
                $controlledActivationAuthorized
                && !$activationAttributionComplete
            );

        $humanManagementAttentionRequired =
            !$completedGovernanceConfirmationEligible
            || !$controlledActivationConsiderationEligible
            || $confirmationRiskScore >= 70
            || $materialRestrictions > 0
            || $blockingConfirmationConditions > 0;

        /*
        |--------------------------------------------------------------------------
        | Eligibility State Output
        |--------------------------------------------------------------------------
        */

        $confirmationEligibilityState = [
            'authorized_human_confirmation_review_eligibility' =>
                $authorizedHumanConfirmationReviewEligible
                    ? 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_CONFIRMATION_REVIEW'
                    : 'NOT_ELIGIBLE_FOR_AUTHORIZED_HUMAN_CONFIRMATION_REVIEW',

            'governance_confirmation_completion_eligibility' =>
                $completedGovernanceConfirmationEligible
                    ? 'ELIGIBLE_FOR_COMPLETED_GOVERNANCE_CONFIRMATION'
                    : 'NOT_ELIGIBLE_FOR_COMPLETED_GOVERNANCE_CONFIRMATION',

            'unrestricted_confirmation_eligibility' =>
                $unrestrictedConfirmationEligible
                    ? 'ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_CONFIRMATION'
                    : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_CONFIRMATION',

            'conditional_confirmation_consideration_eligibility' =>
                $conditionalConfirmationConsiderationEligible
                    ? 'ELIGIBLE_FOR_CONDITIONAL_GOVERNANCE_CONFIRMATION_REVIEW'
                    : 'NOT_ELIGIBLE_FOR_CONDITIONAL_GOVERNANCE_CONFIRMATION_REVIEW',

            'confirmation_deferral_eligibility' =>
                $confirmationDeferralEligible
                    ? 'ELIGIBLE_FOR_GOVERNANCE_CONFIRMATION_DEFERRAL'
                    : 'NOT_ELIGIBLE_FOR_GOVERNANCE_CONFIRMATION_DEFERRAL',

            'request_additional_resolution_eligibility' =>
                $requestAdditionalResolutionEligible
                    ? 'ELIGIBLE_FOR_ADDITIONAL_GOVERNANCE_RESOLUTION_REQUEST'
                    : 'NO_ADDITIONAL_GOVERNANCE_RESOLUTION_REQUEST_REQUIRED',

            'controlled_activation_consideration_eligibility' =>
                $controlledActivationConsiderationEligible
                    ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_CONSIDERATION'
                    : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_CONSIDERATION',

            'controlled_activation_authorization_status' =>
                $controlledActivationAuthorizationComplete
                    ? 'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETE'
                    : 'CONTROLLED_ACTIVATION_NOT_AUTHORIZED',

            'confirmation_eligibility_score' =>
                $eligibilityScore,

            'confirmation_progression_readiness' =>
                $confirmationProgressionReadiness,

            'confirmation_progression_blocked' =>
                $confirmationProgressionBlocked,

            'controlled_activation_progression_blocked' =>
                !$controlledActivationConsiderationEligible,

            'source_final_governance_decision_recorded' =>
                $sourceFinalGovernanceDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_confirmation_decision_recorded' =>
                $governanceConfirmationDecisionRecorded,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety State Output
        |--------------------------------------------------------------------------
        */

        $confirmationSafetyState = [
            'confirmation_safety_status' =>
                $confirmationSafetyStatus,

            'confirmation_safety_level' =>
                $confirmationSafetyLevel,

            'confirmation_safety_score' =>
                $confirmationSafetyScore,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'authorized_human_confirmation_required' =>
                true,

            'authorized_human_activation_required' =>
                true,

            'source_final_governance_decision_required' =>
                !$sourceFinalGovernanceDecisionRecorded,

            'source_authorized_human_decision_attribution_required' =>
                !$sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_required' =>
                !$sourceGovernanceValidationCompleted,

            'confirmation_condition_resolution_required' =>
                $blockingConfirmationConditions > 0,

            'confirmation_restriction_resolution_required' =>
                $materialRestrictions > 0,

            'confirmation_evidence_resolution_required' =>
                $blockingConfirmationEvidenceItems > 0,

            'confirmation_attribution_required' =>
                !$confirmationAttributionComplete,

            'controlled_activation_attribution_required' =>
                $controlledActivationAuthorized
                && !$activationAttributionComplete,

            'automatic_confirmation_prohibited' =>
                true,

            'automatic_activation_prohibited' =>
                true,

            'automatic_execution_prohibited' =>
                true,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety Restrictions
        |--------------------------------------------------------------------------
        */

        $safetyRestrictions = [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'FINAL_GOVERNANCE_DECISION_REQUIRED_BEFORE_CONFIRMATION',

                'category' =>
                    'FINAL_GOVERNANCE_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Completed governance confirmation is prohibited until an explicitly authorized human final governance decision has been recorded.',
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION_REQUIRED',

                'category' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Completed governance confirmation is prohibited until the source final governance decision is attributable to an explicitly authorized human governance decision-maker.',
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_COMPLETION_REQUIRED',

                'category' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Completed governance confirmation remains restricted while required source governance validation is incomplete.',
            ];
        }

        if ($blockingConfirmationConditions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_GOVERNANCE_CONFIRMATION_CONDITIONS',

                'category' =>
                    'CONFIRMATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConfirmationConditions,

                'message' =>
                    "{$blockingConfirmationConditions} governance-confirmation blocking condition(s) remain active.",
            ];
        }

        if ($materialRestrictions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'MATERIAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS',

                'category' =>
                    'CONFIRMATION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material governance-confirmation restriction(s) remain active.",
            ];
        }

        if ($blockingConfirmationEvidenceItems > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_GOVERNANCE_CONFIRMATION_EVIDENCE',

                'category' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConfirmationEvidenceItems,

                'message' =>
                    "{$blockingConfirmationEvidenceItems} evidence requirement(s) currently block governance confirmation.",
            ];
        }

        if ($confirmationRiskScore >= 70) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'HIGH_GOVERNANCE_CONFIRMATION_RISK',

                'category' =>
                    'RISK',

                'severity' =>
                    $confirmationRiskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'value' =>
                    $confirmationRiskScore,

                'message' =>
                    'Governance confirmation remains subject to elevated authorized-human oversight because confirmation risk is '
                    .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                    ." with score {$confirmationRiskScore}.",
            ];
        }

        if (!$confirmationAttributionComplete) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION_REQUIRED',

                'category' =>
                    'CONFIRMATION_AUTHORIZATION',

                'severity' =>
                    'MODERATE',

                'message' =>
                    'Any completed governance confirmation must remain attributable to an explicitly identified authorized human governance confirmer.',
            ];
        }

        if (!$confirmationCompleted) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_NOT_COMPLETED',

                'category' =>
                    'HUMAN_CONFIRMATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation remains prohibited until explicitly authorized human governance confirmation has been completed.',
            ];
        }

        if ($blockingActivationConditions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'CONTROLLED_ACTIVATION_BLOCKING_CONDITIONS',

                'category' =>
                    'CONTROLLED_ACTIVATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingActivationConditions,

                'message' =>
                    "{$blockingActivationConditions} confirmation condition(s) currently block controlled activation.",
            ];
        }

        if (!$controlledActivationAuthorized) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'CONTROLLED_ACTIVATION_NOT_AUTHORIZED',

                'category' =>
                    'CONTROLLED_ACTIVATION_AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation has not been authorized by explicitly authorized human governance.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Eligibility Restriction
        |--------------------------------------------------------------------------
        */

        $dominantEligibilityRestriction =
            $this->determineDominantEligibilityRestriction(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $blockingConfirmationConditions,
                $materialRestrictions,
                $blockingConfirmationEvidenceItems,
                $confirmationRiskScore,
                $confirmationCompleted,
                $confirmationAttributionComplete,
                $controlledActivationAuthorized
            );

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $eligibilitySafetyFindings = [
            "Strategic plan governance decision confirmation eligibility and safety intelligence is based on confirmation record {$confirmation->id}.",

            'Current authorized-human confirmation review eligibility is '
                .$confirmationEligibilityState['authorized_human_confirmation_review_eligibility'].'.',

            'Current governance-confirmation completion eligibility is '
                .$confirmationEligibilityState['governance_confirmation_completion_eligibility'].'.',

            'Current unrestricted governance-confirmation eligibility is '
                .$confirmationEligibilityState['unrestricted_confirmation_eligibility'].'.',

            'Current conditional confirmation-review eligibility is '
                .$confirmationEligibilityState['conditional_confirmation_consideration_eligibility'].'.',

            'Current controlled-activation consideration eligibility is '
                .$confirmationEligibilityState['controlled_activation_consideration_eligibility'].'.',

            'Current confirmation progression readiness is '
                .$confirmationProgressionReadiness.'.',

            'Current confirmation eligibility score is '
                .$eligibilityScore.'.',

            'Current confirmation safety status is '
                .$confirmationSafetyStatus.'.',

            'Current confirmation safety level is '
                .$confirmationSafetyLevel.'.',

            'Current confirmation safety score is '
                .$confirmationSafetyScore.'.',

            "{$totalConditions} governance-confirmation condition(s) are represented.",

            "{$openConditions} governance-confirmation condition(s) remain open.",

            "{$blockingConfirmationConditions} governance-confirmation condition(s) currently block completed confirmation.",

            "{$blockingActivationConditions} governance-confirmation condition(s) currently block controlled activation.",

            "{$constrainingConditions} governance-confirmation condition(s) constrain progression.",

            "{$criticalOpenConditions} critical governance-confirmation condition(s) remain open.",

            "{$totalRestrictions} governance-confirmation restriction(s) are represented.",

            "{$materialRestrictions} material governance-confirmation restriction(s) remain active.",

            "{$criticalRestrictions} critical governance-confirmation restriction(s) remain active.",

            "{$outstandingEvidenceItems} confirmation evidence requirement(s) remain outstanding.",

            "{$blockingConfirmationEvidenceItems} confirmation evidence requirement(s) currently block completed governance confirmation.",

            'Current confirmation readiness score is '
                .$confirmationReadinessScore.'.',

            'Current confirmation risk is '
                .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                .' with score '
                .$confirmationRiskScore.'.',

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current condition pressure score is '
                .$conditionPressureScore.'.',

            'Current restriction pressure score is '
                .$restrictionPressureScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedPressureScore.'.',

            'Source final governance decision recorded is '
                .($sourceFinalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Source final governance decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Authorized-human confirmation attribution complete is '
                .($confirmationAttributionComplete ? 'YES' : 'NO').'.',

            'Governance confirmation completed is '
                .($confirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorized is '
                .($controlledActivationAuthorized ? 'YES' : 'NO').'.',

            'Controlled activation attribution complete is '
                .($activationAttributionComplete ? 'YES' : 'NO').'.',

            'Confirmation eligibility and safety intelligence identifies what authorized human governance may review or consider; it does not complete governance confirmation or authorize controlled activation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human final strategic plan governance decision before completed governance confirmation is considered.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source final governance decision remains explicitly attributable to an authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete required source governance validation through explicitly authorized human governance.';
        }

        if ($blockingConfirmationConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConfirmationConditions} confirmation-blocking condition(s) before completed governance confirmation.";
        }

        if ($materialRestrictions > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material confirmation restriction(s).";
        }

        if ($outstandingEvidenceItems > 0) {
            $managementPriorities[] =
                "Address {$outstandingEvidenceItems} outstanding confirmation evidence requirement(s) through authorized human review.";
        }

        if ($confirmationRiskScore >= 70) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while confirmation risk remains high or critical.';
        }

        if (!$confirmationAttributionComplete) {
            $managementPriorities[] =
                'Ensure completed governance confirmation is explicitly attributable to an identified authorized human governance confirmer.';
        }

        if (!$confirmationCompleted) {
            $managementPriorities[] =
                'Do not treat governance confirmation as completed until an authorized human confirmation decision and attribution are formally recorded.';
        }

        if (!$controlledActivationConsiderationEligible) {
            $managementPriorities[] =
                'Keep controlled activation outside eligible consideration until all required confirmation, attribution, condition, restriction, evidence, and safety prerequisites are satisfied.';
        }

        if (!$controlledActivationAuthorized) {
            $managementPriorities[] =
                'Keep controlled activation unauthorized until a separate explicitly authorized human activation authorization is recorded.';
        }

        $managementPriorities[] =
            'Keep confirmation eligibility and safety intelligence strictly separate from governance-confirmation authority and controlled-activation authority.';

        $managementPriorities[] =
            'Do not interpret eligibility, readiness, safety, risk, condition-resolution, evidence-resolution, pressure, or completion scores as authority to confirm or activate the strategic plan.';

        $managementPriorities[] =
            'Preserve human governance authority, decision attribution, governance-validation attribution, confirmation attribution, evidence quality, traceability, safety controls, and activation-authority separation throughout Step 68.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'strategic_plan_governance_decision_confirmation_id' =>
                $confirmation->id,

            'confirmation_code' =>
                $confirmation->confirmation_code,

            'strategic_plan_final_governance_decision_id' =>
                $confirmation->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $confirmation->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $confirmation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $confirmation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $confirmation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $confirmation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $confirmation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $confirmation->lifecycle_snapshot_id,

            'decision_scope' =>
                $confirmation->decision_scope,

            'resident_id' =>
                $confirmation->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Main Intelligence
            |--------------------------------------------------------------------------
            */

            'confirmation_eligibility_state' =>
                $confirmationEligibilityState,

            'confirmation_safety_state' =>
                $confirmationSafetyState,

            'eligibility_checks' =>
                $eligibilityChecks,

            'safety_restrictions' =>
                $safetyRestrictions,

            'dominant_eligibility_restriction' =>
                $dominantEligibilityRestriction,

            /*
            |--------------------------------------------------------------------------
            | Condition / Restriction / Evidence Context
            |--------------------------------------------------------------------------
            */

            'condition_context' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditions,

                'blocking_confirmation_conditions' =>
                    $blockingConfirmationConditions,

                'blocking_activation_conditions' =>
                    $blockingActivationConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'condition_pressure_score' =>
                    $conditionPressureScore,
            ],

            'restriction_context' => [
                'total_restrictions' =>
                    $totalRestrictions,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'restriction_pressure_score' =>
                    $restrictionPressureScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,
            ],

            'evidence_context' => [
                'outstanding_evidence_items' =>
                    $outstandingEvidenceItems,

                'blocking_confirmation_evidence_items' =>
                    $blockingConfirmationEvidenceItems,

                'blocking_activation_evidence_items' =>
                    $blockingActivationEvidenceItems,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidenceItems,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Resolution Context
            |--------------------------------------------------------------------------
            */

            'resolution_context' => [
                'confirmation_readiness' =>
                    $confirmationState['confirmation_readiness']
                    ?? $confirmation->confirmation_readiness,

                'confirmation_readiness_score' =>
                    $confirmationReadinessScore,

                'confirmation_risk_level' =>
                    $confirmation->confirmation_risk_level,

                'confirmation_risk_score' =>
                    $confirmationRiskScore,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'condition_pressure_score' =>
                    $conditionPressureScore,

                'restriction_pressure_score' =>
                    $restrictionPressureScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,

                'confirmation_progression_readiness' =>
                    $confirmationProgressionReadiness,
            ],

            /*
            |--------------------------------------------------------------------------
            | Authority Context
            |--------------------------------------------------------------------------
            */

            'source_final_governance_decision_context' =>
                $sourceFinalGovernanceDecisionContext,

            'governance_validation_context' =>
                $governanceValidationBarrierContext,

            'confirmation_attribution_context' =>
                $confirmationAttributionContext,

            'confirmation_authority_context' =>
                $confirmationAuthorityContext,

            'controlled_activation_context' => [
                'controlled_activation_status' =>
                    $confirmation->controlled_activation_status,

                'controlled_activation_authorized' =>
                    $controlledActivationAuthorized,

                'controlled_activation_consideration_eligible' =>
                    $controlledActivationConsiderationEligible,

                'controlled_activation_authorization_complete' =>
                    $controlledActivationAuthorizationComplete,

                'controlled_activation_authorization_code' =>
                    $confirmation->controlled_activation_authorization_code,

                'activation_authorized_by' =>
                    $confirmation->activation_authorized_by,

                'activation_authorizer_role' =>
                    $confirmation->activation_authorizer_role,

                'activation_authorized_at' =>
                    $confirmation->activation_authorized_at,

                'activation_authorizer_identified' =>
                    $activationAuthorizerIdentified,

                'activation_authorizer_role_available' =>
                    $activationAuthorizerRoleAvailable,

                'activation_authorization_timestamp_available' =>
                    $activationAuthorizationTimestampAvailable,

                'activation_authorization_code_available' =>
                    $activationAuthorizationCodeAvailable,

                'activation_attribution_complete' =>
                    $activationAttributionComplete,

                'blocking_activation_conditions' =>
                    $blockingActivationConditions,

                'blocking_activation_evidence_items' =>
                    $blockingActivationEvidenceItems,

                'material_restrictions' =>
                    $materialRestrictions,

                'confirmation_completed' =>
                    $confirmationCompleted,

                'confirmation_attribution_complete' =>
                    $confirmationAttributionComplete,

                'automatic_activation_allowed' =>
                    (bool) $confirmation->automatic_activation_allowed,

                'automatic_execution_allowed' =>
                    (bool) $confirmation->automatic_execution_allowed,

                'authorized_human_activation_required' =>
                    (bool) $confirmation->authorized_human_activation_required,
            ],

            /*
            |--------------------------------------------------------------------------
            | Step 68.3 / 68.4 State Context
            |--------------------------------------------------------------------------
            */

            'confirmation_state_context' =>
                $confirmationState,

            'condition_restriction_state_context' =>
                $conditionRestrictionState,

            'activation_state_context' =>
                $activationContext,

            'completion_context' =>
                $completionContext,

            'risk_context' =>
                $riskContext,

            /*
            |--------------------------------------------------------------------------
            | Upstream Context
            |--------------------------------------------------------------------------
            */

            'review_context' =>
                $reviewContext,

            'validation_context' =>
                $validationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            /*
            |--------------------------------------------------------------------------
            | Findings
            |--------------------------------------------------------------------------
            */

            'eligibility_safety_findings' =>
                $eligibilitySafetyFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            /*
            |--------------------------------------------------------------------------
            | Step 68.5 Guardrails
            |--------------------------------------------------------------------------
            */

            'confirmation_eligibility_safety_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Eligibility Score
    |--------------------------------------------------------------------------
    */

    private function calculateEligibilityScore(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        int $blockingConfirmationConditions,
        int $criticalOpenConditions,
        int $materialRestrictions,
        int $criticalRestrictions,
        int $blockingEvidence,
        int $criticalOutstandingEvidence,
        float $riskScore,
        float $readinessScore
    ): float {
        $score = 0.0;

        if ($sourceFinalGovernanceDecisionRecorded) {
            $score += 15;
        }

        if ($sourceDecisionMadeByAuthorizedHuman) {
            $score += 15;
        }

        if ($sourceGovernanceValidationCompleted) {
            $score += 15;
        }

        if ($blockingConfirmationConditions === 0) {
            $score += 15;
        }

        if ($materialRestrictions === 0) {
            $score += 10;
        }

        if ($blockingEvidence === 0) {
            $score += 10;
        }

        if ($criticalOpenConditions === 0) {
            $score += 5;
        }

        if ($criticalRestrictions === 0) {
            $score += 5;
        }

        if ($criticalOutstandingEvidence === 0) {
            $score += 5;
        }

        if ($riskScore < 70) {
            $score += 5;
        }

        $score =
            ($score * 0.75)
            + ($readinessScore * 0.25);

        return round(
            max(0, min(100, $score)),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Safety Score
    |--------------------------------------------------------------------------
    */

    private function calculateSafetyScore(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        int $blockingConfirmationConditions,
        int $blockingActivationConditions,
        int $materialRestrictions,
        int $criticalConditions,
        int $criticalRestrictions,
        float $riskScore,
        bool $confirmationCompleted,
        bool $confirmationAttributionComplete,
        bool $controlledActivationAuthorized,
        bool $activationAttributionComplete
    ): float {
        $score = 100.0;

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $score -= 15;
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $score -= 15;
        }

        if (!$sourceGovernanceValidationCompleted) {
            $score -= 15;
        }

        $score -= min(
            20,
            $blockingConfirmationConditions * 3
        );

        $score -= min(
            15,
            $blockingActivationConditions * 2
        );

        $score -= min(
            15,
            $materialRestrictions * 2
        );

        $score -= min(
            25,
            $criticalConditions * 10
        );

        $score -= min(
            25,
            $criticalRestrictions * 10
        );

        if ($riskScore >= 90) {
            $score -= 15;
        } elseif ($riskScore >= 70) {
            $score -= 10;
        } elseif ($riskScore >= 50) {
            $score -= 5;
        }

        if (
            $confirmationCompleted
            && !$confirmationAttributionComplete
        ) {
            $score -= 25;
        }

        if (
            $controlledActivationAuthorized
            && !$activationAttributionComplete
        ) {
            $score -= 30;
        }

        return round(
            max(0, min(100, $score)),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Progression Readiness
    |--------------------------------------------------------------------------
    */

    private function determineConfirmationProgressionReadiness(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        int $blockingConfirmationConditions,
        int $materialRestrictions,
        bool $confirmationCompleted,
        bool $confirmationAttributionComplete
    ): string {
        if (
            $confirmationCompleted
            && $confirmationAttributionComplete
        ) {
            return 'GOVERNANCE_CONFIRMATION_COMPLETED';
        }

        if (!$sourceFinalGovernanceDecisionRecorded) {
            return 'AWAITING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return 'AWAITING_AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION';
        }

        if (!$sourceGovernanceValidationCompleted) {
            return 'AWAITING_SOURCE_GOVERNANCE_VALIDATION_COMPLETION';
        }

        if ($blockingConfirmationConditions > 0) {
            return 'BLOCKED_BY_GOVERNANCE_CONFIRMATION_CONDITIONS';
        }

        if ($materialRestrictions > 0) {
            return 'RESTRICTED_GOVERNANCE_CONFIRMATION_PROGRESSION';
        }

        return 'READY_FOR_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REVIEW';
    }

    /*
    |--------------------------------------------------------------------------
    | Safety Classification
    |--------------------------------------------------------------------------
    */

    private function determineSafetyLevel(
        float $safetyScore,
        float $riskScore,
        int $criticalConditions,
        int $criticalRestrictions
    ): string {
        if (
            $criticalConditions > 0
            || $criticalRestrictions > 0
            || $riskScore >= 90
        ) {
            return 'CRITICAL';
        }

        if (
            $safetyScore < 35
            || $riskScore >= 70
        ) {
            return 'HIGH';
        }

        if (
            $safetyScore < 65
            || $riskScore >= 50
        ) {
            return 'MODERATE';
        }

        return 'LOW';
    }

    private function determineSafetyStatus(
        bool $completedConfirmationEligible,
        bool $controlledActivationEligible,
        bool $confirmationCompleted,
        int $materialRestrictions,
        int $blockingConfirmationConditions,
        int $criticalConditions,
        int $criticalRestrictions
    ): string {
        if (
            $criticalConditions > 0
            || $criticalRestrictions > 0
        ) {
            return 'CONTROLLED_WITH_CRITICAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS';
        }

        if (!$completedConfirmationEligible) {
            return 'CONTROLLED_WITH_MATERIAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS';
        }

        if (
            !$confirmationCompleted
            || $materialRestrictions > 0
            || $blockingConfirmationConditions > 0
        ) {
            return 'CONTROLLED_AUTHORIZED_HUMAN_CONFIRMATION_REVIEW_REQUIRED';
        }

        if (!$controlledActivationEligible) {
            return 'GOVERNANCE_CONFIRMATION_CONTROLLED_ACTIVATION_RESTRICTED';
        }

        return 'CONTROLLED_GOVERNANCE_CONFIRMATION_AND_ACTIVATION_REVIEW_AVAILABLE';
    }

    /*
    |--------------------------------------------------------------------------
    | Dominant Restriction
    |--------------------------------------------------------------------------
    */

    private function determineDominantEligibilityRestriction(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        int $blockingConditions,
        int $materialRestrictions,
        int $blockingEvidence,
        float $riskScore,
        bool $confirmationCompleted,
        bool $confirmationAttributionComplete,
        bool $controlledActivationAuthorized
    ): ?array {
        if (!$sourceFinalGovernanceDecisionRecorded) {
            return [
                'restriction_code' =>
                    'FINAL_GOVERNANCE_DECISION_REQUIRED_BEFORE_CONFIRMATION',

                'restriction_type' =>
                    'FINAL_GOVERNANCE_DECISION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Completed governance confirmation is not eligible until an explicitly authorized human final governance decision has been recorded.',
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION_REQUIRED',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Completed governance confirmation is not eligible until the source final governance decision is attributable to an explicitly authorized human governance decision-maker.',
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            return [
                'restriction_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_COMPLETION_REQUIRED',

                'restriction_type' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Completed governance confirmation is not eligible while required source governance validation remains incomplete.',
            ];
        }

        if ($blockingConditions > 0) {
            return [
                'restriction_code' =>
                    'BLOCKING_GOVERNANCE_CONFIRMATION_CONDITIONS',

                'restriction_type' =>
                    'CONFIRMATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking governance-confirmation condition(s) remain active.",
            ];
        }

        if ($materialRestrictions > 0) {
            return [
                'restriction_code' =>
                    'MATERIAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS',

                'restriction_type' =>
                    'CONFIRMATION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material governance-confirmation restriction(s) remain active.",
            ];
        }

        if ($blockingEvidence > 0) {
            return [
                'restriction_code' =>
                    'BLOCKING_GOVERNANCE_CONFIRMATION_EVIDENCE',

                'restriction_type' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingEvidence,

                'message' =>
                    "{$blockingEvidence} evidence requirement(s) currently block completed governance confirmation.",
            ];
        }

        if ($riskScore >= 70) {
            return [
                'restriction_code' =>
                    'HIGH_GOVERNANCE_CONFIRMATION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    $riskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'value' =>
                    $riskScore,

                'message' =>
                    "Governance-confirmation risk remains elevated with score {$riskScore}.",
            ];
        }

        if (!$confirmationCompleted) {
            return [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REQUIRED',

                'restriction_type' =>
                    'HUMAN_CONFIRMATION',

                'severity' =>
                    'MODERATE',

                'value' =>
                    false,

                'message' =>
                    'An explicitly authorized human governance confirmation is still required.',
            ];
        }

        if (!$confirmationAttributionComplete) {
            return [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION_REQUIRED',

                'restriction_type' =>
                    'CONFIRMATION_AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Completed governance confirmation must remain attributable to an explicitly identified authorized human governance confirmer.',
            ];
        }

        if (!$controlledActivationAuthorized) {
            return [
                'restriction_code' =>
                    'CONTROLLED_ACTIVATION_NOT_AUTHORIZED',

                'restriction_type' =>
                    'CONTROLLED_ACTIVATION',

                'severity' =>
                    'MODERATE',

                'value' =>
                    false,

                'message' =>
                    'Governance confirmation does not itself authorize controlled activation.',
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode(
                $value,
                true
            );

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
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

    /*
    |--------------------------------------------------------------------------
    | Step 68.5 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'governance_confirmation_eligibility_safety_intelligence_enabled' =>
                true,

            'eligibility_intelligence_is_governance_confirmation' =>
                false,

            'eligibility_intelligence_makes_governance_confirmation_decision' =>
                false,

            'eligibility_intelligence_records_governance_confirmation_decision' =>
                false,

            'eligibility_intelligence_completes_governance_confirmation' =>
                false,

            'eligibility_intelligence_makes_final_governance_decision' =>
                false,

            'eligibility_intelligence_records_final_governance_decision' =>
                false,

            'eligibility_intelligence_approves_strategic_plan' =>
                false,

            'eligibility_intelligence_rejects_strategic_plan' =>
                false,

            'eligibility_intelligence_conditionally_approves_strategic_plan' =>
                false,

            'eligibility_intelligence_defers_strategic_plan' =>
                false,

            'eligibility_intelligence_accepts_governance_risk' =>
                false,

            'eligibility_intelligence_authorizes_controlled_activation' =>
                false,

            'eligibility_intelligence_activates_strategic_plan' =>
                false,

            'confirmation_review_eligibility_is_governance_confirmation' =>
                false,

            'completed_confirmation_eligibility_is_completed_confirmation' =>
                false,

            'unrestricted_confirmation_eligibility_is_governance_confirmation' =>
                false,

            'conditional_confirmation_eligibility_is_governance_confirmation' =>
                false,

            'controlled_activation_consideration_eligibility_is_activation_authorization' =>
                false,

            'controlled_activation_eligibility_activates_strategic_plan' =>
                false,

            'eligibility_intelligence_changes_confirmation_status' =>
                false,

            'eligibility_intelligence_changes_confirmation_decision' =>
                false,

            'eligibility_intelligence_changes_confirmation_outcome' =>
                false,

            'eligibility_intelligence_changes_source_final_governance_decision' =>
                false,

            'eligibility_intelligence_changes_governance_validation' =>
                false,

            'eligibility_intelligence_changes_controlled_activation_status' =>
                false,

            'eligibility_intelligence_changes_plan_status' =>
                false,

            'eligibility_intelligence_changes_action_state' =>
                false,

            'eligibility_intelligence_changes_priority' =>
                false,

            'eligibility_intelligence_resolves_conditions' =>
                false,

            'eligibility_intelligence_waives_conditions' =>
                false,

            'eligibility_intelligence_removes_restrictions' =>
                false,

            'eligibility_intelligence_resolves_dependencies' =>
                false,

            'eligibility_intelligence_validates_evidence' =>
                false,

            'eligibility_score_authorizes_confirmation' =>
                false,

            'eligibility_score_authorizes_activation' =>
                false,

            'safety_score_authorizes_confirmation' =>
                false,

            'safety_score_authorizes_activation' =>
                false,

            'confirmation_readiness_score_authorizes_confirmation' =>
                false,

            'confirmation_readiness_score_authorizes_activation' =>
                false,

            'confirmation_risk_score_authorizes_confirmation' =>
                false,

            'confirmation_risk_score_authorizes_activation' =>
                false,

            'condition_resolution_score_authorizes_confirmation' =>
                false,

            'evidence_resolution_score_authorizes_confirmation' =>
                false,

            'combined_resolution_score_authorizes_confirmation' =>
                false,

            'condition_pressure_score_authorizes_confirmation' =>
                false,

            'restriction_pressure_score_authorizes_confirmation' =>
                false,

            'combined_pressure_score_authorizes_confirmation' =>
                false,

            'eligibility_intelligence_authorizes_ai_change' =>
                false,

            'eligibility_intelligence_authorizes_execution' =>
                false,

            'eligibility_intelligence_authorizes_deployment' =>
                false,

            'eligibility_intelligence_authorizes_rollback' =>
                false,

            'eligibility_intelligence_authorizes_clinical_action' =>
                false,

            'eligibility_intelligence_overrides_human_review' =>
                false,

            'eligibility_intelligence_overrides_governance_validation' =>
                false,

            'eligibility_intelligence_overrides_final_governance_decision' =>
                false,

            'eligibility_intelligence_overrides_confirmation_authority' =>
                false,

            'eligibility_intelligence_overrides_activation_authority' =>
                false,

            'eligibility_intelligence_overrides_evidence_requirements' =>
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

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'controlled_activation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'authorized_human_confirmation_required' =>
                true,

            'authorized_human_activation_required' =>
                true,

            'message' =>
                'Step 68.5 strategic plan governance decision confirmation eligibility and safety intelligence evaluates whether a prepared confirmation package may progress through authorized-human review, whether completed governance confirmation is currently eligible, whether controlled activation may be considered, and what material safety restrictions remain. Eligibility, readiness, risk, safety, condition-resolution, evidence-resolution, pressure, completion, and activation indicators do not constitute governance confirmation or activation authorization. The intelligence does not make, record, or complete governance confirmation; make or alter the final governance decision; approve, reject, conditionally approve, defer, or accept governance risk; authorize or perform controlled activation; resolve or waive conditions; remove restrictions; validate evidence; alter governance-validation state; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Governance confirmation, final governance decision, and controlled activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}