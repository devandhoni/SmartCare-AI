<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationFinalValidationEngine
{
    public function analyze(?int $confirmationId = null): array
    {
        $confirmation = $confirmationId
            ? AIGovernanceStrategicPlanGovernanceDecisionConfirmation::find($confirmationId)
            : AIGovernanceStrategicPlanGovernanceDecisionConfirmation::latest('id')->first();

        if (!$confirmation) {
            return [
                'validation_status' => 'FAILED',
                'step_68_ready_for_closure' => false,
                'completion_message' =>
                    'Step 68 final validation cannot be completed because no strategic plan governance decision confirmation record is available.',
                'validation_summary' => [
                    'total_checks' => 1,
                    'passed_checks' => 0,
                    'failed_checks' => 1,
                    'warning_count' => 0,
                    'critical_issue_count' => 1,
                ],
                'checks' => [
                    'strategic_plan_governance_decision_confirmation_available' => [
                        'passed' => false,
                        'message' =>
                            'Strategic plan governance decision confirmation record is unavailable.',
                    ],
                ],
                'warnings' => [],
                'critical_issues' => [
                    'No strategic plan governance decision confirmation record is available for Step 68 final validation.',
                ],
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

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceExecutiveStrategicPlanGovernanceDecisionConfirmationIntelligenceEngine::class
        );

        $auditEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationAuditSummaryEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Step 68 Intelligence
        |--------------------------------------------------------------------------
        */

        $state =
            $stateEngine->analyze($confirmation->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($confirmation->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($confirmation->id);

        $recommendation =
            $recommendationEngine->analyze($confirmation->id);

        $executive =
            $executiveEngine->analyze($confirmation->id);

        $audit =
            $auditEngine->analyze($confirmation->id);

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $confirmationState =
            $state['confirmation_state'] ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary'] ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary'] ?? [];

        $eligibilityState =
            $eligibilitySafety['confirmation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['confirmation_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $executiveSummary =
            $executive['executive_summary']
            ?? $executive['executive_governance_confirmation_state']
            ?? [];

        $auditSummary =
            $audit['audit_summary'] ?? [];

        $auditChecks =
            $audit['checks'] ?? [];

        $auditIntegritySummary =
            $audit['integrity_summary'] ?? [];

        $conditionRestrictionSummary =
            $audit['condition_restriction_summary'] ?? [];

        $confirmationSummary =
            $audit['confirmation_summary'] ?? [];

        $authorizationConfirmationSummary =
            $audit['authorization_confirmation_summary'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Current Governance State
        |--------------------------------------------------------------------------
        */

        $sourceFinalGovernanceDecisionRecorded =
            (bool) (
                $confirmationSummary['source_final_governance_decision_recorded']
                ?? $confirmation->source_final_governance_decision_recorded
            );

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) (
                $confirmationSummary['source_decision_made_by_authorized_human']
                ?? $confirmation->source_decision_made_by_authorized_human
            );

        $sourceGovernanceValidationCompleted =
            (bool) (
                $confirmationSummary['source_governance_validation_completed']
                ?? $confirmation->source_governance_validation_completed
            );

        $governanceConfirmationDecisionRecorded =
            (bool) (
                $confirmationSummary['governance_confirmation_decision_recorded']
                ?? filled($confirmation->governance_confirmation_decision)
            );

        $confirmationMadeByAuthorizedHuman =
            (bool) (
                $confirmationSummary['confirmation_made_by_authorized_human']
                ?? $confirmation->confirmation_made_by_authorized_human
            );

        $confirmationCompleted =
            (bool) (
                $confirmationSummary['governance_decision_confirmation_completed']
                ?? $confirmation->governance_decision_confirmation_completed
            );

        $controlledActivationAuthorized =
            (bool) (
                $confirmationSummary['controlled_activation_authorized']
                ?? $confirmation->controlled_activation_authorized
            );

        $confirmationAttributionComplete =
            (bool) (
                $authorizationConfirmationSummary['confirmation_attribution_complete']
                ?? false
            );

        $activationAttributionComplete =
            (bool) (
                $authorizationConfirmationSummary['activation_attribution_complete']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Conditions / Restrictions / Evidence
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) (
                $conditionRestrictionSummary['total_conditions']
                ?? $conditionSummary['total_conditions']
                ?? 0
            );

        $openConditions =
            (int) (
                $conditionRestrictionSummary['open_conditions']
                ?? $conditionSummary['open_conditions']
                ?? 0
            );

        $blockingConfirmationConditions =
            (int) (
                $conditionRestrictionSummary['blocking_confirmation_conditions']
                ?? 0
            );

        $blockingActivationConditions =
            (int) (
                $conditionRestrictionSummary['blocking_activation_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionRestrictionSummary['constraining_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionRestrictionSummary['critical_open_conditions']
                ?? 0
            );

        $totalRestrictions =
            (int) (
                $conditionRestrictionSummary['total_restrictions']
                ?? $restrictionSummary['total_restrictions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $conditionRestrictionSummary['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $conditionRestrictionSummary['critical_restrictions']
                ?? 0
            );

        $outstandingEvidenceItems =
            (int) (
                $conditionRestrictionSummary['outstanding_evidence_items']
                ?? 0
            );

        $blockingConfirmationEvidenceItems =
            (int) (
                $conditionRestrictionSummary['blocking_confirmation_evidence_items']
                ?? 0
            );

        $blockingActivationEvidenceItems =
            (int) (
                $conditionRestrictionSummary['blocking_activation_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidenceItems =
            (int) (
                $conditionRestrictionSummary['critical_outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $confirmationReadinessScore =
            $this->score(
                $confirmationSummary['confirmation_readiness_score']
                ?? $confirmationState['confirmation_readiness_score']
                ?? $confirmation->confirmation_readiness_score
            );

        $confirmationRiskScore =
            $this->score(
                $confirmationSummary['confirmation_risk_score']
                ?? $confirmationState['confirmation_risk_score']
                ?? $confirmation->confirmation_risk_score
            );

        $conditionResolutionScore =
            $this->score(
                $conditionRestrictionSummary['condition_resolution_score']
                ?? $confirmation->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $conditionRestrictionSummary['evidence_resolution_score']
                ?? $confirmation->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $conditionRestrictionSummary['combined_resolution_score']
                ?? $confirmation->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $conditionRestrictionSummary['condition_pressure_score']
                ?? $confirmation->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $conditionRestrictionSummary['restriction_pressure_score']
                ?? $confirmation->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $conditionRestrictionSummary['combined_condition_restriction_pressure_score']
                ?? $confirmation->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety
        |--------------------------------------------------------------------------
        */

        $confirmationReviewEligibility =
            $eligibilityState['authorized_human_confirmation_review_eligibility']
            ?? null;

        $confirmationCompletionEligibility =
            $eligibilityState['governance_confirmation_completion_eligibility']
            ?? null;

        $unrestrictedConfirmationEligibility =
            $eligibilityState['unrestricted_confirmation_eligibility']
            ?? null;

        $conditionalConfirmationEligibility =
            $eligibilityState['conditional_confirmation_consideration_eligibility']
            ?? null;

        $confirmationDeferralEligibility =
            $eligibilityState['confirmation_deferral_eligibility']
            ?? null;

        $additionalResolutionEligibility =
            $eligibilityState['request_additional_resolution_eligibility']
            ?? null;

        $controlledActivationConsiderationEligibility =
            $eligibilityState['controlled_activation_consideration_eligibility']
            ?? null;

        $confirmationEligibilityScore =
            $this->score(
                $eligibilityState['confirmation_eligibility_score']
                ?? 0
            );

        $confirmationProgressionReadiness =
            $eligibilityState['confirmation_progression_readiness']
            ?? null;

        $confirmationProgressionBlocked =
            (bool) (
                $eligibilityState['confirmation_progression_blocked']
                ?? true
            );

        $controlledActivationProgressionBlocked =
            (bool) (
                $eligibilityState['controlled_activation_progression_blocked']
                ?? true
            );

        $confirmationSafetyStatus =
            $safetyState['confirmation_safety_status']
            ?? null;

        $confirmationSafetyLevel =
            $safetyState['confirmation_safety_level']
            ?? null;

        $confirmationSafetyScore =
            $this->score(
                $safetyState['confirmation_safety_score']
                ?? 0
            );

        $governanceIntegrityIntact =
            ($auditIntegritySummary['governance_integrity_intact'] ?? false)
            === true;

        /*
        |--------------------------------------------------------------------------
        | Recommendation
        |--------------------------------------------------------------------------
        */

        $recommendationStatus =
            $recommendationState['recommendation_status']
            ?? null;

        $totalRecommendations =
            (int) (
                $recommendationState['total_recommendations']
                ?? 0
            );

        $criticalRecommendations =
            (int) (
                $recommendationState['critical_recommendations']
                ?? 0
            );

        $highRecommendations =
            (int) (
                $recommendationState['high_recommendations']
                ?? 0
            );

        $topRecommendationCode =
            $recommendationState['top_recommendation_code']
            ?? null;

        $topRecommendedPath =
            $recommendationState['top_recommended_path']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Executive
        |--------------------------------------------------------------------------
        */

        $executiveStatus =
            $executiveSummary['executive_governance_confirmation_status']
            ?? null;

        $executiveReadiness =
            $executiveSummary['executive_readiness']
            ?? null;

        $executiveConfidence =
            $executiveSummary['executive_confidence']
            ?? null;

        $executiveScore =
            $this->score(
                $executiveSummary['executive_governance_confirmation_score']
                ?? 0
            );

        $managementEscalationRecommended =
            (bool) (
                $executiveSummary['management_escalation_recommended']
                ?? false
            );

        $immediateEscalationRequired =
            (bool) (
                $executiveSummary['immediate_escalation_required']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Audit Integrity
        |--------------------------------------------------------------------------
        */

        $auditComplete =
            ($audit['audit_status'] ?? null) === 'COMPLETE'
            && (int) ($auditSummary['failed_checks'] ?? 1) === 0;

        $finalGovernanceDecisionAuthorityProtected =
            ($auditIntegritySummary['final_governance_decision_authority_reserved_for_authorized_human'] ?? false)
            === true;

        $governanceConfirmationAuthorityProtected =
            ($auditIntegritySummary['governance_confirmation_authority_reserved_for_authorized_human'] ?? false)
            === true;

        $controlledActivationAuthorityProtected =
            ($auditIntegritySummary['controlled_activation_authority_reserved_for_authorized_human'] ?? false)
            === true;

        $sequenceIntegrity =
            ($auditIntegritySummary['human_confirmation_activation_sequence_integrity'] ?? false)
            === true;

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $automaticAuthorityIsolation =
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
            && !$confirmation->automatic_clinical_action_allowed;

        $humanGovernanceControls =
            (bool) $confirmation->human_review_required
            && (bool) $confirmation->governance_validation_required
            && (bool) $confirmation->authorized_human_confirmation_required
            && (bool) $confirmation->authorized_human_activation_required;

        /*
        |--------------------------------------------------------------------------
        | Step 68.9 Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [
            'strategic_plan_governance_decision_confirmation_available' => [
                'passed' => true,
                'message' =>
                    'Strategic plan governance decision confirmation record is available.',
            ],

            'confirmation_state_intelligence_operational' => [
                'passed' =>
                    ($state['analysis_completed'] ?? false) === true,

                'message' =>
                    'Strategic plan governance-confirmation state intelligence is operational.',
            ],

            'confirmation_condition_restriction_intelligence_operational' => [
                'passed' =>
                    ($conditionRestriction['analysis_completed'] ?? false) === true,

                'message' =>
                    'Strategic plan governance-confirmation condition and restriction intelligence is operational.',
            ],

            'confirmation_eligibility_safety_intelligence_operational' => [
                'passed' =>
                    ($eligibilitySafety['analysis_completed'] ?? false) === true,

                'message' =>
                    'Strategic plan governance-confirmation eligibility and safety intelligence is operational.',
            ],

            'confirmation_recommendation_intelligence_operational' => [
                'passed' =>
                    ($recommendation['analysis_completed'] ?? false) === true,

                'message' =>
                    'Strategic plan governance-confirmation recommendation intelligence is operational.',
            ],

            'executive_governance_confirmation_intelligence_operational' => [
                'passed' =>
                    ($executive['analysis_completed'] ?? false) === true,

                'message' =>
                    'Executive strategic plan governance-confirmation intelligence is operational.',
            ],

            'step_68_audit_complete' => [
                'passed' =>
                    $auditComplete,

                'message' =>
                    'Step 68 governance-confirmation audit completed without integrity failures.',
            ],

            'governance_integrity_intact' => [
                'passed' =>
                    $governanceIntegrityIntact,

                'message' =>
                    'Strategic plan governance-confirmation integrity remains intact.',
            ],

            'critical_confirmation_condition_absent' => [
                'passed' =>
                    $criticalOpenConditions === 0,

                'value' =>
                    $criticalOpenConditions,

                'message' =>
                    'No critical strategic plan governance-confirmation condition remains open.',
            ],

            'critical_confirmation_restriction_absent' => [
                'passed' =>
                    $criticalRestrictions === 0,

                'value' =>
                    $criticalRestrictions,

                'message' =>
                    'No critical strategic plan governance-confirmation restriction remains active.',
            ],

            'critical_outstanding_confirmation_evidence_absent' => [
                'passed' =>
                    $criticalOutstandingEvidenceItems === 0,

                'value' =>
                    $criticalOutstandingEvidenceItems,

                'message' =>
                    'No critical strategic plan governance-confirmation evidence requirement remains outstanding.',
            ],

            'immediate_escalation_absent' => [
                'passed' =>
                    !$immediateEscalationRequired,

                'value' =>
                    $immediateEscalationRequired,

                'message' =>
                    'No immediate strategic plan governance-confirmation escalation requirement is active.',
            ],

            'automatic_authority_isolation' => [
                'passed' =>
                    $automaticAuthorityIsolation,

                'message' =>
                    'Automatic confirmation, final decision, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, restriction removal, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    $humanGovernanceControls,

                'message' =>
                    'Human review, governance validation, authorized-human confirmation, and authorized-human activation remain mandatory.',
            ],

            'state_intelligence_authority_isolation' => [
                'passed' =>
                    ($auditChecks['state_intelligence_authority_isolation']['passed'] ?? false)
                    === true,

                'message' =>
                    'Governance-confirmation state intelligence remains informational and isolated from confirmation, activation, and execution authority.',
            ],

            'condition_restriction_intelligence_authority_isolation' => [
                'passed' =>
                    ($auditChecks['condition_restriction_intelligence_authority_isolation']['passed'] ?? false)
                    === true,

                'message' =>
                    'Governance-confirmation condition and restriction intelligence remains advisory and isolated from condition resolution, restriction removal, confirmation, activation, and execution authority.',
            ],

            'eligibility_safety_authority_isolation' => [
                'passed' =>
                    ($auditChecks['eligibility_safety_authority_isolation']['passed'] ?? false)
                    === true,

                'message' =>
                    'Governance-confirmation eligibility and safety intelligence remains isolated from governance-confirmation, controlled-activation, and execution authority.',
            ],

            'recommendation_authority_isolation' => [
                'passed' =>
                    ($auditChecks['recommendation_authority_isolation']['passed'] ?? false)
                    === true,

                'message' =>
                    'Governance-confirmation recommendation intelligence remains advisory and isolated from final governance decision, governance confirmation, controlled activation, and execution authority.',
            ],

            'executive_authority_isolation' => [
                'passed' =>
                    ($auditChecks['executive_authority_isolation']['passed'] ?? false)
                    === true,

                'message' =>
                    'Executive governance-confirmation intelligence remains informational and isolated from final governance decision, governance confirmation, controlled activation, and execution authority.',
            ],

            'audit_authority_isolation' => [
                'passed' =>
                    ($audit['audit_guardrails']['audit_makes_governance_confirmation_decision'] ?? true) === false
                    && ($audit['audit_guardrails']['audit_completes_governance_confirmation'] ?? true) === false
                    && ($audit['audit_guardrails']['audit_authorizes_controlled_activation'] ?? true) === false
                    && ($audit['audit_guardrails']['audit_is_execution_authorization'] ?? true) === false,

                'message' =>
                    'Step 68 audit remains informational and isolated from final governance decision, governance confirmation, controlled activation, and execution authority.',
            ],

            'final_governance_decision_authority_protected' => [
                'passed' =>
                    $finalGovernanceDecisionAuthorityProtected,

                'value' =>
                    $sourceFinalGovernanceDecisionRecorded,

                'message' =>
                    'Final strategic plan governance decision authority remains reserved for explicitly authorized human governance.',
            ],

            'governance_confirmation_authority_protected' => [
                'passed' =>
                    $governanceConfirmationAuthorityProtected,

                'value' =>
                    $confirmationCompleted,

                'message' =>
                    'Governance decision confirmation authority remains reserved for explicitly authorized human governance.',
            ],

            'controlled_activation_authority_protected' => [
                'passed' =>
                    $controlledActivationAuthorityProtected,

                'value' =>
                    $controlledActivationAuthorized,

                'message' =>
                    'Controlled activation authority remains reserved for explicitly authorized human governance.',
            ],

            'human_confirmation_activation_sequence_integrity' => [
                'passed' =>
                    $sequenceIntegrity,

                'message' =>
                    'Governance confirmation and controlled activation remain correctly sequenced behind explicitly authorized human final governance decision, governance validation, confirmation attribution, and activation attribution.',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation Summary
        |--------------------------------------------------------------------------
        */

        $totalChecks =
            count($checks);

        $passedChecks =
            count(
                array_filter(
                    $checks,
                    fn (array $check) =>
                        ($check['passed'] ?? false) === true
                )
            );

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $warnings[] =
                'Final authorized-human strategic plan governance decision has not yet been recorded.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $warnings[] =
                'Final strategic plan governance decision has not yet been attributed to an explicitly authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $warnings[] =
                'Required source strategic plan governance validation has not yet been completed.';
        }

        if ($blockingConfirmationConditions > 0) {
            $warnings[] =
                "{$blockingConfirmationConditions} governance-confirmation condition(s) currently block completed confirmation.";
        }

        if ($blockingActivationConditions > 0) {
            $warnings[] =
                "{$blockingActivationConditions} governance-confirmation condition(s) currently block controlled activation.";
        }

        if ($constrainingConditions > 0) {
            $warnings[] =
                "{$constrainingConditions} governance-confirmation condition(s) currently constrain progression.";
        }

        if ($materialRestrictions > 0) {
            $warnings[] =
                "{$materialRestrictions} material governance-confirmation restriction(s) remain active.";
        }

        if ($conditionResolutionScore < 60) {
            $warnings[] =
                "Strategic plan governance-confirmation condition resolution remains limited at score {$conditionResolutionScore}.";
        }

        if ($evidenceResolutionScore < 60) {
            $warnings[] =
                "Strategic plan governance-confirmation evidence resolution remains limited at score {$evidenceResolutionScore}.";
        }

        if ($combinedResolutionScore < 60) {
            $warnings[] =
                "Combined strategic plan governance-confirmation resolution remains limited at score {$combinedResolutionScore}.";
        }

        if ($confirmationRiskScore >= 70) {
            $warnings[] =
                'Strategic plan governance-confirmation risk remains '
                .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                ." with score {$confirmationRiskScore}.";
        }

        if (
            $confirmationCompletionEligibility
            === 'NOT_ELIGIBLE_FOR_COMPLETED_GOVERNANCE_CONFIRMATION'
        ) {
            $warnings[] =
                'Completed strategic plan governance confirmation is not currently eligible under existing governance conditions.';
        }

        if (
            $unrestrictedConfirmationEligibility
            === 'NOT_ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_CONFIRMATION'
        ) {
            $warnings[] =
                'Unrestricted strategic plan governance confirmation remains unavailable under current governance conditions.';
        }

        if (!$confirmationAttributionComplete) {
            $warnings[] =
                'Authorized governance-confirmation attribution is not yet complete.';
        }

        if (!$confirmationCompleted) {
            $warnings[] =
                'Strategic plan governance decision confirmation has not yet been completed.';
        }

        if (
            $controlledActivationConsiderationEligibility
            === 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_CONSIDERATION'
        ) {
            $warnings[] =
                'Controlled strategic plan activation is not currently eligible for consideration.';
        }

        if (!$controlledActivationAuthorized) {
            $warnings[] =
                'Controlled strategic plan activation has not been authorized.';
        }

        if (!$activationAttributionComplete) {
            $warnings[] =
                'Controlled-activation authorization attribution is not yet complete.';
        }

        if ($managementEscalationRecommended) {
            $warnings[] =
                'Management escalation remains recommended for current governance-confirmation conditions.';
        }

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $code => $check) {
            if (($check['passed'] ?? false) !== true) {
                $criticalIssues[] =
                    "Final Step 68 integrity check failed: {$code}.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {
            $validationStatus =
                'FAILED';

            $step68ReadyForClosure =
                false;
        } elseif (count($warnings) > 0) {
            $validationStatus =
                'PASSED_WITH_WARNINGS';

            $step68ReadyForClosure =
                true;
        } else {
            $validationStatus =
                'PASSED';

            $step68ReadyForClosure =
                true;
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Context
        |--------------------------------------------------------------------------
        */

        $governanceConfirmationContext = [
            'confirmation_status' =>
                $confirmation->confirmation_status,

            'confirmation_mode' =>
                $confirmation->confirmation_mode,

            'prepared_decision' =>
                $confirmation->prepared_decision,

            'source_final_governance_decision' =>
                $confirmation->source_final_governance_decision,

            'source_final_governance_decision_recorded' =>
                $sourceFinalGovernanceDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_confirmation_decision' =>
                $confirmation->governance_confirmation_decision,

            'governance_confirmation_decision_recorded' =>
                $governanceConfirmationDecisionRecorded,

            'confirmation_readiness' =>
                $confirmation->confirmation_readiness,

            'confirmation_readiness_score' =>
                $confirmationReadinessScore,

            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                $confirmationRiskScore,

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

            'total_restrictions' =>
                $totalRestrictions,

            'material_restrictions' =>
                $materialRestrictions,

            'outstanding_evidence_items' =>
                $outstandingEvidenceItems,

            'blocking_confirmation_evidence_items' =>
                $blockingConfirmationEvidenceItems,

            'blocking_activation_evidence_items' =>
                $blockingActivationEvidenceItems,

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

            'authorized_human_confirmation_review_eligibility' =>
                $confirmationReviewEligibility,

            'governance_confirmation_completion_eligibility' =>
                $confirmationCompletionEligibility,

            'unrestricted_confirmation_eligibility' =>
                $unrestrictedConfirmationEligibility,

            'conditional_confirmation_consideration_eligibility' =>
                $conditionalConfirmationEligibility,

            'confirmation_deferral_eligibility' =>
                $confirmationDeferralEligibility,

            'request_additional_resolution_eligibility' =>
                $additionalResolutionEligibility,

            'controlled_activation_consideration_eligibility' =>
                $controlledActivationConsiderationEligibility,

            'confirmation_eligibility_score' =>
                $confirmationEligibilityScore,

            'confirmation_progression_readiness' =>
                $confirmationProgressionReadiness,

            'confirmation_progression_blocked' =>
                $confirmationProgressionBlocked,

            'controlled_activation_progression_blocked' =>
                $controlledActivationProgressionBlocked,

            'confirmation_safety_status' =>
                $confirmationSafetyStatus,

            'confirmation_safety_level' =>
                $confirmationSafetyLevel,

            'confirmation_safety_score' =>
                $confirmationSafetyScore,

            'recommendation_status' =>
                $recommendationStatus,

            'total_recommendations' =>
                $totalRecommendations,

            'critical_recommendations' =>
                $criticalRecommendations,

            'high_recommendations' =>
                $highRecommendations,

            'top_recommendation_code' =>
                $topRecommendationCode,

            'top_recommended_path' =>
                $topRecommendedPath,

            'executive_governance_confirmation_status' =>
                $executiveStatus,

            'executive_readiness' =>
                $executiveReadiness,

            'executive_confidence' =>
                $executiveConfidence,

            'executive_governance_confirmation_score' =>
                $executiveScore,

            'management_escalation_recommended' =>
                $managementEscalationRecommended,

            'immediate_escalation_required' =>
                $immediateEscalationRequired,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'controlled_activation_status' =>
                $confirmation->controlled_activation_status,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,

            'activation_attribution_complete' =>
                $activationAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '68.1_governance_decision_confirmation_registry' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '68.2_governance_decision_confirmation_preparation' => [
                'status' =>
                    'OPERATIONAL',

                'strategic_plan_governance_decision_confirmation_id' =>
                    $confirmation->id,

                'confirmation_code' =>
                    $confirmation->confirmation_code,
            ],

            '68.3_confirmation_state_intelligence' => [
                'status' =>
                    ($state['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'confirmation_status' =>
                    $confirmation->confirmation_status,

                'confirmation_readiness' =>
                    $confirmation->confirmation_readiness,
            ],

            '68.4_confirmation_condition_restriction_intelligence' => [
                'status' =>
                    ($conditionRestriction['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'blocking_confirmation_conditions' =>
                    $blockingConfirmationConditions,

                'blocking_activation_conditions' =>
                    $blockingActivationConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,
            ],

            '68.5_confirmation_eligibility_safety_intelligence' => [
                'status' =>
                    ($eligibilitySafety['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'governance_confirmation_completion_eligibility' =>
                    $confirmationCompletionEligibility,

                'controlled_activation_consideration_eligibility' =>
                    $controlledActivationConsiderationEligibility,

                'confirmation_safety_status' =>
                    $confirmationSafetyStatus,

                'confirmation_safety_score' =>
                    $confirmationSafetyScore,
            ],

            '68.6_confirmation_recommendation_intelligence' => [
                'status' =>
                    ($recommendation['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'recommendation_status' =>
                    $recommendationStatus,

                'total_recommendations' =>
                    $totalRecommendations,

                'top_recommendation_code' =>
                    $topRecommendationCode,
            ],

            '68.7_executive_governance_confirmation_intelligence' => [
                'status' =>
                    ($executive['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'executive_governance_confirmation_status' =>
                    $executiveStatus,

                'executive_readiness' =>
                    $executiveReadiness,

                'executive_governance_confirmation_score' =>
                    $executiveScore,
            ],

            '68.8_governance_confirmation_audit' => [
                'status' =>
                    $audit['audit_status'] ?? 'UNKNOWN',

                'passed_checks' =>
                    (int) ($auditSummary['passed_checks'] ?? 0),

                'failed_checks' =>
                    (int) ($auditSummary['failed_checks'] ?? 0),
            ],

            '68.9_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_68_ready_for_closure' =>
                    $step68ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 68 Strategic Plan Governance Decision Confirmation & Controlled Activation Intelligence architecture has been validated.',

            'Current governance-confirmation status is '
                .($confirmation->confirmation_status ?? 'UNKNOWN').'.',

            'Current governance-confirmation readiness is '
                .($confirmation->confirmation_readiness ?? 'UNKNOWN')
                .' with score '
                .$confirmationReadinessScore.'.',

            "{$blockingConfirmationConditions} governance-confirmation condition(s) currently block completed confirmation.",

            "{$blockingActivationConditions} governance-confirmation condition(s) currently block controlled activation.",

            "{$constrainingConditions} governance-confirmation condition(s) currently constrain progression.",

            "{$materialRestrictions} material governance-confirmation restriction(s) remain active.",

            "{$outstandingEvidenceItems} governance-confirmation evidence requirement(s) remain outstanding.",

            "{$blockingConfirmationEvidenceItems} governance-confirmation evidence requirement(s) currently block completed confirmation.",

            "{$blockingActivationEvidenceItems} governance-confirmation evidence requirement(s) currently block controlled activation.",

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined condition/evidence resolution score is '
                .$combinedResolutionScore.'.',

            'Current confirmation eligibility score is '
                .$confirmationEligibilityScore.'.',

            'Current governance-confirmation completion eligibility is '
                .($confirmationCompletionEligibility ?? 'UNKNOWN').'.',

            'Current unrestricted governance-confirmation eligibility is '
                .($unrestrictedConfirmationEligibility ?? 'UNKNOWN').'.',

            'Current controlled-activation consideration eligibility is '
                .($controlledActivationConsiderationEligibility ?? 'UNKNOWN').'.',

            'Current confirmation safety status is '
                .($confirmationSafetyStatus ?? 'UNKNOWN')
                .' with score '
                .$confirmationSafetyScore.'.',

            'Current governance-confirmation recommendation status is '
                .($recommendationStatus ?? 'UNKNOWN').'.',

            'Current executive governance-confirmation status is '
                .($executiveStatus ?? 'UNKNOWN').'.',

            'Current executive governance-confirmation readiness is '
                .($executiveReadiness ?? 'UNKNOWN')
                .' with score '
                .$executiveScore.'.',

            'Source final governance decision recorded is '
                .($sourceFinalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Source final governance decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Governance confirmation decision recorded is '
                .($governanceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Confirmation made by explicitly authorized human is '
                .($confirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Governance decision confirmation completed is '
                .($confirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorized is '
                .($controlledActivationAuthorized ? 'YES' : 'NO').'.',

            'Strategic plan governance-confirmation and controlled-activation intelligence remains advisory, informational, and human governed.',

            'No autonomous final governance decision, governance confirmation, approval, rejection, conditional approval, deferral, risk acceptance, controlled activation, strategic plan activation, condition resolution, restriction removal, evidence validation, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_68_ready_for_closure' =>
                $step68ReadyForClosure,

            'governance_confirmation_activation_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_STRATEGIC_PLAN_CONFIRMATION_CONTROLLED_ACTIVATION_INTELLIGENCE',

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

            'completion_message' =>
                $step68ReadyForClosure
                    ? 'Step 68 Strategic Plan Governance Decision Confirmation & Controlled Activation Intelligence has passed final validation and is ready for closure.'
                    : 'Step 68 Strategic Plan Governance Decision Confirmation & Controlled Activation Intelligence has not passed final validation and requires corrective governance-control work.',

            'validation_summary' => [
                'total_checks' =>
                    $totalChecks,

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

            'governance_confirmation_activation_context' =>
                $governanceConfirmationContext,

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_68_guardrails' =>
                $this->guardrails(),
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

    /*
    |--------------------------------------------------------------------------
    | Step 68 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'strategic_plan_governance_confirmation_controlled_activation_intelligence_enabled' =>
                true,

            'governance_decision_confirmation_registry_enabled' =>
                true,

            'governance_decision_confirmation_preparation_enabled' =>
                true,

            'governance_decision_confirmation_state_intelligence_enabled' =>
                true,

            'governance_decision_confirmation_condition_restriction_intelligence_enabled' =>
                true,

            'governance_decision_confirmation_eligibility_safety_intelligence_enabled' =>
                true,

            'governance_decision_confirmation_recommendation_intelligence_enabled' =>
                true,

            'executive_governance_decision_confirmation_intelligence_enabled' =>
                true,

            'governance_decision_confirmation_audit_enabled' =>
                true,

            /*
            |--------------------------------------------------------------------------
            | Autonomous Governance Prohibitions
            |--------------------------------------------------------------------------
            */

            'autonomous_final_governance_decision_enabled' =>
                false,

            'autonomous_governance_confirmation_enabled' =>
                false,

            'autonomous_strategic_plan_approval_enabled' =>
                false,

            'autonomous_strategic_plan_rejection_enabled' =>
                false,

            'autonomous_strategic_plan_conditional_approval_enabled' =>
                false,

            'autonomous_strategic_plan_deferral_enabled' =>
                false,

            'autonomous_governance_risk_acceptance_enabled' =>
                false,

            'autonomous_controlled_activation_enabled' =>
                false,

            'autonomous_strategic_plan_activation_enabled' =>
                false,

            /*
            |--------------------------------------------------------------------------
            | Automatic Action Prohibitions
            |--------------------------------------------------------------------------
            */

            'automatic_governance_confirmation_enabled' =>
                false,

            'automatic_final_governance_decision_enabled' =>
                false,

            'automatic_condition_resolution_enabled' =>
                false,

            'automatic_dependency_resolution_enabled' =>
                false,

            'automatic_restriction_removal_enabled' =>
                false,

            'automatic_evidence_validation_enabled' =>
                false,

            'automatic_activation_enabled' =>
                false,

            'automatic_governance_decision' =>
                false,

            'automatic_governance_approval' =>
                false,

            'automatic_governance_rejection' =>
                false,

            'automatic_governance_resolution' =>
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

            'automatic_execution' =>
                false,

            'automatic_deployment' =>
                false,

            'automatic_rollback' =>
                false,

            /*
            |--------------------------------------------------------------------------
            | Human Authority
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'controlled_activation_authority_reserved_for_authorized_human' =>
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
                'Step 68 establishes authorized-human-governed strategic plan governance decision confirmation and controlled-activation intelligence. The system may prepare a governance-confirmation record, evaluate confirmation state, unresolved confirmation conditions, material restrictions, evidence state, resolution progress, confirmation eligibility, safety restrictions, recommendations, executive confirmation conditions, source final-governance-decision attribution, governance-validation state, confirmation attribution, controlled-activation eligibility, activation attribution, and governance-control integrity. It does not autonomously make or record the final governance decision, complete governance confirmation, approve, reject, conditionally approve, defer, accept governance risk, authorize controlled activation, activate the strategic plan, resolve conditions or dependencies, remove restrictions, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority. Final governance decision authority, governance-confirmation authority, and controlled-activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}