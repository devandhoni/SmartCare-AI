<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationEligibilitySafetyIntelligenceEngine
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
                'analysis_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_AUTHORIZATION_AVAILABLE',
                'message' =>
                    'No strategic plan controlled activation authorization record is available for eligibility and safety intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Controlled activation authorization eligibility and safety intelligence could not continue because Step 69.3 state intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69.4 Condition / Restriction Intelligence
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine::class
        );

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($authorization->id);

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Controlled activation authorization eligibility and safety intelligence could not continue because Step 69.4 condition and restriction intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $conditionContext =
            $state['condition_context'] ?? [];

        $restrictionContext =
            $state['restriction_context'] ?? [];

        $evidenceContext =
            $state['evidence_context'] ?? [];

        $resolutionContext =
            $state['resolution_context'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary'] ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary'] ?? [];

        $authorizationBarrierContext =
            $conditionRestriction['authorization_barrier_context'] ?? [];

        $executionBarrierContext =
            $conditionRestriction['execution_barrier_context'] ?? [];

        $confirmationBarrierContext =
            $conditionRestriction['confirmation_barrier_context'] ?? [];

        $riskContext =
            $conditionRestriction['risk_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core State
        |--------------------------------------------------------------------------
        */

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

        $sourceGovernanceConfirmationCompleted =
            (bool) (
                $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                ?? false
            );

        $sourceControlledActivationAuthorized =
            (bool) (
                $sourceConfirmationContext['source_controlled_activation_authorized']
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
        | Blocking Counts
        |--------------------------------------------------------------------------
        */

        $blockingAuthorizationConditions =
            (int) (
                $conditionSummary['blocking_activation_authorization_conditions']
                ?? 0
            );

        $blockingExecutionConditions =
            (int) (
                $conditionSummary['blocking_activation_execution_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $restrictionSummary['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary['critical_restrictions']
                ?? 0
            );

        $outstandingEvidenceItems =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

        $blockingAuthorizationEvidence =
            (int) (
                $evidenceContext['blocking_activation_authorization_evidence_items']
                ?? 0
            );

        $blockingExecutionEvidence =
            (int) (
                $evidenceContext['blocking_activation_execution_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidence =
            (int) (
                $evidenceContext['critical_outstanding_evidence_items']
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

        $conditionResolutionScore =
            $this->score(
                $resolutionContext['condition_resolution_score']
                ?? $authorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $resolutionContext['evidence_resolution_score']
                ?? $authorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $resolutionContext['combined_resolution_score']
                ?? $authorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $resolutionContext['condition_pressure_score']
                ?? $authorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $resolutionContext['restriction_pressure_score']
                ?? $authorization->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $resolutionContext['combined_condition_restriction_pressure_score']
                ?? $authorization->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Authorized-Human Review Eligibility
        |--------------------------------------------------------------------------
        */

        $authorizedHumanReviewEligible =
            true;

        $authorizedHumanReviewEligibility =
            'ELIGIBLE_FOR_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Authorization Completion Eligibility
        |--------------------------------------------------------------------------
        */

        $authorizationCompletionEligible =
            $sourceConfirmationDecisionRecorded
            && $sourceConfirmationMadeByAuthorizedHuman
            && $sourceGovernanceConfirmationCompleted
            && $blockingAuthorizationConditions === 0
            && $criticalOpenConditions === 0
            && $criticalRestrictions === 0
            && $blockingAuthorizationEvidence === 0
            && $criticalOutstandingEvidence === 0;

        $authorizationCompletionEligibility =
            $authorizationCompletionEligible
                ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETION'
                : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETION';

        /*
        |--------------------------------------------------------------------------
        | Unrestricted Authorization Eligibility
        |--------------------------------------------------------------------------
        */

        $unrestrictedAuthorizationEligible =
            $authorizationCompletionEligible
            && $materialRestrictions === 0
            && $outstandingEvidenceItems === 0
            && $activationRiskScore < 50
            && $combinedResolutionScore >= 80;

        $unrestrictedAuthorizationEligibility =
            $unrestrictedAuthorizationEligible
                ? 'ELIGIBLE_FOR_UNRESTRICTED_CONTROLLED_ACTIVATION_AUTHORIZATION'
                : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_CONTROLLED_ACTIVATION_AUTHORIZATION';

        /*
        |--------------------------------------------------------------------------
        | Conditional Authorization Consideration
        |--------------------------------------------------------------------------
        */

        $conditionalAuthorizationEligible =
            $authorizedHumanReviewEligible
            && !$unrestrictedAuthorizationEligible;

        $conditionalAuthorizationEligibility =
            $conditionalAuthorizationEligible
                ? 'ELIGIBLE_FOR_CONDITIONAL_CONTROLLED_ACTIVATION_AUTHORIZATION_REVIEW'
                : 'NOT_ELIGIBLE_FOR_CONDITIONAL_CONTROLLED_ACTIVATION_AUTHORIZATION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Deferral Eligibility
        |--------------------------------------------------------------------------
        */

        $deferralEligible =
            !$authorizationCompleted
            || $blockingAuthorizationConditions > 0
            || $materialRestrictions > 0
            || $activationRiskScore >= 75;

        $deferralEligibility =
            $deferralEligible
                ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_AUTHORIZATION_DEFERRAL'
                : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_AUTHORIZATION_DEFERRAL';

        /*
        |--------------------------------------------------------------------------
        | Additional Resolution Request Eligibility
        |--------------------------------------------------------------------------
        */

        $additionalResolutionEligible =
            $blockingAuthorizationConditions > 0
            || $blockingExecutionConditions > 0
            || $materialRestrictions > 0
            || $outstandingEvidenceItems > 0
            || !$sourceGovernanceConfirmationCompleted;

        $additionalResolutionEligibility =
            $additionalResolutionEligible
                ? 'ELIGIBLE_FOR_ADDITIONAL_CONTROLLED_ACTIVATION_RESOLUTION_REQUEST'
                : 'NO_ADDITIONAL_CONTROLLED_ACTIVATION_RESOLUTION_REQUEST_REQUIRED';

        /*
        |--------------------------------------------------------------------------
        | Execution Consideration Eligibility
        |--------------------------------------------------------------------------
        */

        $executionConsiderationEligible =
            $authorizationCompleted
            && $authorizationDecisionRecorded
            && $authorizationMadeByAuthorizedHuman
            && $authorizationAttributionComplete
            && $blockingExecutionConditions === 0
            && $blockingExecutionEvidence === 0
            && $criticalRestrictions === 0
            && $criticalOutstandingEvidence === 0;

        $executionConsiderationEligibility =
            $executionConsiderationEligible
                ? 'ELIGIBLE_FOR_SEPARATE_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_REVIEW'
                : 'NOT_ELIGIBLE_FOR_ACTIVATION_EXECUTION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization Status
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationEligibility =
            (
                $executionConsiderationEligible
                && $executionAuthorized
                && $executionAttributionComplete
            )
                ? 'AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_PRESENT'
                : 'NO_VALID_ACTIVATION_EXECUTION_AUTHORIZATION_PRESENT';

        /*
        |--------------------------------------------------------------------------
        | Eligibility Score
        |--------------------------------------------------------------------------
        */

        $eligibilityComponents = [
            'authorized_human_review' =>
                10,

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded ? 10 : 0,

            'source_confirmation_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman ? 10 : 0,

            'source_confirmation_completed' =>
                $sourceGovernanceConfirmationCompleted ? 10 : 0,

            'no_authorization_blockers' =>
                $blockingAuthorizationConditions === 0 ? 15 : 0,

            'no_execution_blockers' =>
                $blockingExecutionConditions === 0 ? 10 : 0,

            'no_critical_restrictions' =>
                $criticalRestrictions === 0 ? 10 : 0,

            'no_blocking_authorization_evidence' =>
                $blockingAuthorizationEvidence === 0 ? 5 : 0,

            'no_blocking_execution_evidence' =>
                $blockingExecutionEvidence === 0 ? 5 : 0,

            'authorization_attribution' =>
                $authorizationAttributionComplete ? 10 : 0,

            'authorization_completed' =>
                $authorizationCompleted ? 5 : 0,
        ];

        $activationAuthorizationEligibilityScore =
            round(
                array_sum($eligibilityComponents),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Safety Violations
        |--------------------------------------------------------------------------
        */

        $safetyViolations = [];

        if (
            (bool) $authorization->automatic_activation_authorization_allowed
        ) {
            $safetyViolations[] =
                'Automatic controlled activation authorization is enabled.';
        }

        if ((bool) $authorization->automatic_activation_allowed) {
            $safetyViolations[] =
                'Automatic strategic plan activation is enabled.';
        }

        if ((bool) $authorization->automatic_execution_allowed) {
            $safetyViolations[] =
                'Automatic activation execution is enabled.';
        }

        if ((bool) $authorization->automatic_change_allowed) {
            $safetyViolations[] =
                'Automatic AI or system change is enabled.';
        }

        if ((bool) $authorization->automatic_deployment_allowed) {
            $safetyViolations[] =
                'Automatic deployment is enabled.';
        }

        if ((bool) $authorization->automatic_rollback_allowed) {
            $safetyViolations[] =
                'Automatic rollback is enabled.';
        }

        if ((bool) $authorization->automatic_clinical_action_allowed) {
            $safetyViolations[] =
                'Automatic clinical action is enabled.';
        }

        if (!(bool) $authorization->human_review_required) {
            $safetyViolations[] =
                'Mandatory human review is disabled.';
        }

        if (!(bool) $authorization->governance_confirmation_required) {
            $safetyViolations[] =
                'Required governance confirmation control is disabled.';
        }

        if (
            !(bool) $authorization->authorized_human_activation_authorization_required
        ) {
            $safetyViolations[] =
                'Authorized-human activation authorization requirement is disabled.';
        }

        if (
            !(bool) $authorization->authorized_human_activation_execution_required
        ) {
            $safetyViolations[] =
                'Authorized-human activation execution requirement is disabled.';
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            count($safetyViolations) === 0;

        /*
        |--------------------------------------------------------------------------
        | Safety Score
        |--------------------------------------------------------------------------
        */

        $safetyScore = 100.0;

        $safetyScore -=
            min(
                30,
                $blockingAuthorizationConditions * 4
            );

        $safetyScore -=
            min(
                20,
                $blockingExecutionConditions * 2
            );

        $safetyScore -=
            min(
                20,
                $materialRestrictions * 2
            );

        $safetyScore -=
            min(
                20,
                $criticalRestrictions * 20
            );

        $safetyScore -=
            min(
                15,
                $criticalOutstandingEvidence * 15
            );

        if (!$sourceGovernanceConfirmationCompleted) {
            $safetyScore -= 10;
        }

        if (!$authorizationCompleted) {
            $safetyScore -= 10;
        }

        if ($activationRiskScore >= 90) {
            $safetyScore -= 20;
        } elseif ($activationRiskScore >= 75) {
            $safetyScore -= 15;
        } elseif ($activationRiskScore >= 50) {
            $safetyScore -= 10;
        }

        if (!$governanceIntegrityIntact) {
            $safetyScore -= 40;
        }

        $safetyScore =
            round(
                max(
                    0,
                    min(
                        100,
                        $safetyScore
                    )
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Safety Level
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $safetyLevel =
                'CRITICAL';
        } elseif (
            $criticalRestrictions > 0
            || $criticalOutstandingEvidence > 0
            || $activationRiskScore >= 90
        ) {
            $safetyLevel =
                'CRITICAL';
        } elseif (
            $blockingAuthorizationConditions > 0
            || $blockingExecutionConditions > 0
            || $activationRiskScore >= 75
        ) {
            $safetyLevel =
                'HIGH';
        } elseif (
            $materialRestrictions > 0
            || $activationRiskScore >= 50
        ) {
            $safetyLevel =
                'MODERATE';
        } else {
            $safetyLevel =
                'CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Status
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $safetyStatus =
                'GOVERNANCE_INTEGRITY_FAILURE';
        } elseif (
            $criticalRestrictions > 0
            || $criticalOutstandingEvidence > 0
            || $activationRiskScore >= 90
        ) {
            $safetyStatus =
                'CONTROLLED_WITH_CRITICAL_ACTIVATION_AUTHORIZATION_RESTRICTIONS';
        } elseif (
            $blockingAuthorizationConditions > 0
            || $blockingExecutionConditions > 0
            || $materialRestrictions > 0
        ) {
            $safetyStatus =
                'CONTROLLED_WITH_MATERIAL_ACTIVATION_AUTHORIZATION_RESTRICTIONS';
        } else {
            $safetyStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_SAFETY_STATE';
        }

        /*
        |--------------------------------------------------------------------------
        | Progression State
        |--------------------------------------------------------------------------
        */

        if (!$sourceConfirmationDecisionRecorded) {
            $progressionReadiness =
                'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION';
        } elseif (!$sourceConfirmationMadeByAuthorizedHuman) {
            $progressionReadiness =
                'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_ATTRIBUTION';
        } elseif (!$sourceGovernanceConfirmationCompleted) {
            $progressionReadiness =
                'AWAITING_COMPLETED_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        } elseif ($criticalRestrictions > 0) {
            $progressionReadiness =
                'CRITICAL_ACTIVATION_AUTHORIZATION_RESTRICTIONS_REQUIRE_HUMAN_GOVERNANCE';
        } elseif ($blockingAuthorizationConditions > 0) {
            $progressionReadiness =
                'ACTIVATION_AUTHORIZATION_BLOCKING_CONDITIONS_REQUIRE_RESOLUTION';
        } elseif (!$authorizationDecisionRecorded) {
            $progressionReadiness =
                'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION';
        } elseif (!$authorizationAttributionComplete) {
            $progressionReadiness =
                'AWAITING_CONTROLLED_ACTIVATION_AUTHORIZATION_ATTRIBUTION';
        } elseif (!$authorizationCompleted) {
            $progressionReadiness =
                'AWAITING_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETION';
        } elseif (!$executionAuthorized) {
            $progressionReadiness =
                'AWAITING_SEPARATE_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION';
        } else {
            $progressionReadiness =
                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_PRESENT';
        }

        $progressionBlocked =
            !$authorizationCompletionEligible
            || $blockingAuthorizationConditions > 0
            || $criticalRestrictions > 0
            || !$governanceIntegrityIntact;

        /*
        |--------------------------------------------------------------------------
        | Eligibility State
        |--------------------------------------------------------------------------
        */

        $eligibilityState = [
            'authorized_human_activation_authorization_review_eligibility' =>
                $authorizedHumanReviewEligibility,

            'controlled_activation_authorization_completion_eligibility' =>
                $authorizationCompletionEligibility,

            'unrestricted_controlled_activation_authorization_eligibility' =>
                $unrestrictedAuthorizationEligibility,

            'conditional_controlled_activation_authorization_consideration_eligibility' =>
                $conditionalAuthorizationEligibility,

            'controlled_activation_authorization_deferral_eligibility' =>
                $deferralEligibility,

            'request_additional_activation_resolution_eligibility' =>
                $additionalResolutionEligibility,

            'activation_execution_review_eligibility' =>
                $executionConsiderationEligibility,

            'activation_execution_authorization_state' =>
                $executionAuthorizationEligibility,

            'activation_authorization_eligibility_score' =>
                $activationAuthorizationEligibilityScore,

            'activation_progression_readiness' =>
                $progressionReadiness,

            'activation_progression_blocked' =>
                $progressionBlocked,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety State
        |--------------------------------------------------------------------------
        */

        $safetyState = [
            'activation_authorization_safety_status' =>
                $safetyStatus,

            'activation_authorization_safety_level' =>
                $safetyLevel,

            'activation_authorization_safety_score' =>
                $safetyScore,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'safety_violation_count' =>
                count($safetyViolations),

            'critical_restrictions' =>
                $criticalRestrictions,

            'critical_open_conditions' =>
                $criticalOpenConditions,

            'critical_outstanding_evidence_items' =>
                $criticalOutstandingEvidence,

            'blocking_activation_authorization_conditions' =>
                $blockingAuthorizationConditions,

            'blocking_activation_execution_conditions' =>
                $blockingExecutionConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'activation_risk_level' =>
                $activationState['activation_risk_level']
                ?? $authorization->activation_risk_level,

            'activation_risk_score' =>
                $activationRiskScore,

            'immediate_human_intervention_required' =>
                (
                    $criticalRestrictions > 0
                    || $criticalOpenConditions > 0
                    || $criticalOutstandingEvidence > 0
                    || !$governanceIntegrityIntact
                ),

            'human_management_attention_required' =>
                (
                    $progressionBlocked
                    || $activationRiskScore >= 75
                    || $materialRestrictions > 0
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $activationRiskContext = [
            'activation_risk_level' =>
                $activationState['activation_risk_level']
                ?? $authorization->activation_risk_level,

            'activation_risk_score' =>
                $activationRiskScore,

            'condition_pressure_score' =>
                $conditionPressureScore,

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,

            'risk_restriction_count' =>
                (int) (
                    $riskContext['risk_restriction_count']
                    ?? 0
                ),

            'critical_risk_restriction_present' =>
                (bool) (
                    $riskContext['critical_risk_restriction_present']
                    ?? false
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility Findings
        |--------------------------------------------------------------------------
        */

        $eligibilityFindings = [
            "Controlled activation authorization eligibility and safety intelligence is based on authorization record {$authorization->id}.",

            'Authorized-human activation authorization review eligibility is '
                .$authorizedHumanReviewEligibility.'.',

            'Controlled activation authorization completion eligibility is '
                .$authorizationCompletionEligibility.'.',

            'Unrestricted controlled activation authorization eligibility is '
                .$unrestrictedAuthorizationEligibility.'.',

            'Conditional activation authorization review eligibility is '
                .$conditionalAuthorizationEligibility.'.',

            'Controlled activation authorization deferral eligibility is '
                .$deferralEligibility.'.',

            'Additional activation resolution request eligibility is '
                .$additionalResolutionEligibility.'.',

            'Activation execution review eligibility is '
                .$executionConsiderationEligibility.'.',

            'Current activation execution authorization state is '
                .$executionAuthorizationEligibility.'.',

            'Current activation authorization eligibility score is '
                .$activationAuthorizationEligibilityScore.'.',

            'Current activation progression readiness is '
                .$progressionReadiness.'.',

            'Activation progression blocked is '
                .($progressionBlocked ? 'YES' : 'NO').'.',

            'Current activation authorization safety status is '
                .$safetyStatus.'.',

            'Current activation authorization safety level is '
                .$safetyLevel.'.',

            'Current activation authorization safety score is '
                .$safetyScore.'.',

            'Governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            "{$blockingAuthorizationConditions} controlled activation condition(s) currently block authorization.",

            "{$blockingExecutionConditions} controlled activation condition(s) currently block execution.",

            "{$materialRestrictions} material controlled activation restriction(s) remain active.",

            "{$criticalRestrictions} critical controlled activation restriction(s) remain active.",

            "{$outstandingEvidenceItems} controlled activation evidence requirement(s) remain outstanding.",

            "{$blockingAuthorizationEvidence} controlled activation evidence requirement(s) block authorization.",

            "{$blockingExecutionEvidence} controlled activation evidence requirement(s) block execution.",

            'Source governance confirmation decision recorded is '
                .($sourceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source governance confirmation made by authorized human is '
                .($sourceConfirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance confirmation completed is '
                .($sourceGovernanceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorization completed is '
                .($authorizationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation execution authorized is '
                .($executionAuthorized ? 'YES' : 'NO').'.',

            'Eligibility and safety intelligence remains advisory and does not authorize controlled activation or activation execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human governance confirmation decision before controlled activation authorization completion.';
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure source governance confirmation is explicitly attributable to an authorized human governance confirmer.';
        }

        if (!$sourceGovernanceConfirmationCompleted) {
            $managementPriorities[] =
                'Complete governance decision confirmation through authorized human governance before controlled activation authorization completion.';
        }

        if ($blockingAuthorizationConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingAuthorizationConditions} blocking activation-authorization condition(s).";
        }

        if ($blockingExecutionConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingExecutionConditions} activation-execution blocking condition(s).";
        }

        if ($materialRestrictions > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material activation restriction(s).";
        }

        if ($criticalRestrictions > 0) {
            $managementPriorities[] =
                "Escalate {$criticalRestrictions} critical activation restriction(s) for immediate authorized-human governance review.";
        }

        if ($activationRiskScore >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while controlled activation risk remains high or critical.';
        }

        if (!$authorizationDecisionRecorded) {
            $managementPriorities[] =
                'An explicitly authorized human controlled activation authorization decision remains required.';
        }

        if (!$authorizationAttributionComplete) {
            $managementPriorities[] =
                'Ensure completed controlled activation authorization remains attributable to an identified authorized human authorizer.';
        }

        if (!$executionAuthorized) {
            $managementPriorities[] =
                'Keep activation execution prohibited until a separate explicitly authorized human execution authorization is completed.';
        }

        $managementPriorities[] =
            'Keep eligibility and safety intelligence separate from controlled activation authorization authority and execution authority.';

        $managementPriorities[] =
            'Do not interpret eligibility, readiness, safety, risk, resolution, or pressure scores as authorization to activate or execute.';

        $managementPriorities[] =
            'Preserve governance confirmation, authorization attribution, execution attribution, evidence traceability, safety controls, and authority isolation throughout controlled activation progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

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

            'activation_eligibility_state' =>
                $eligibilityState,

            'activation_safety_state' =>
                $safetyState,

            'activation_risk_context' =>
                $activationRiskContext,

            'condition_context' =>
                $conditionContext,

            'restriction_context' =>
                $restrictionContext,

            'evidence_context' =>
                $evidenceContext,

            'resolution_context' =>
                $resolutionContext,

            'authorization_barrier_context' =>
                $authorizationBarrierContext,

            'execution_barrier_context' =>
                $executionBarrierContext,

            'confirmation_barrier_context' =>
                $confirmationBarrierContext,

            'source_confirmation_context' =>
                $sourceConfirmationContext,

            'authorization_context' =>
                $authorizationContext,

            'execution_context' =>
                $executionContext,

            'activation_authorization_state_context' =>
                $activationState,

            'condition_restriction_state_context' =>
                $conditionRestrictionState,

            'review_context' =>
                $state['review_context'] ?? [],

            'confirmation_context' =>
                $state['confirmation_context'] ?? [],

            'governance_context' =>
                $state['governance_context'] ?? [],

            'source_context' =>
                $state['source_context'] ?? [],

            'eligibility_components' =>
                $eligibilityComponents,

            'safety_violations' =>
                $safetyViolations,

            'eligibility_safety_findings' =>
                $eligibilityFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'activation_authorization_eligibility_safety_guardrails' =>
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
    | Step 69.5 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_eligibility_safety_intelligence_enabled' =>
                true,

            'eligibility_safety_intelligence_is_activation_authorization' =>
                false,

            'eligibility_safety_intelligence_is_activation_execution_authorization' =>
                false,

            'eligibility_safety_intelligence_makes_activation_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_records_activation_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_completes_activation_authorization' =>
                false,

            'eligibility_safety_intelligence_authorizes_activation_execution' =>
                false,

            'eligibility_safety_intelligence_activates_strategic_plan' =>
                false,

            'eligibility_safety_intelligence_changes_source_confirmation' =>
                false,

            'eligibility_safety_intelligence_changes_authorization_status' =>
                false,

            'eligibility_safety_intelligence_changes_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_changes_authorization_outcome' =>
                false,

            'eligibility_safety_intelligence_changes_execution_status' =>
                false,

            'eligibility_safety_intelligence_changes_plan_status' =>
                false,

            'eligibility_safety_intelligence_changes_action_state' =>
                false,

            'eligibility_safety_intelligence_changes_priority' =>
                false,

            'eligibility_safety_intelligence_changes_conditions' =>
                false,

            'eligibility_safety_intelligence_changes_restrictions' =>
                false,

            'eligibility_safety_intelligence_resolves_conditions' =>
                false,

            'eligibility_safety_intelligence_waives_conditions' =>
                false,

            'eligibility_safety_intelligence_removes_restrictions' =>
                false,

            'eligibility_safety_intelligence_validates_evidence' =>
                false,

            'eligibility_score_authorizes_activation' =>
                false,

            'eligibility_score_authorizes_execution' =>
                false,

            'safety_score_authorizes_activation' =>
                false,

            'safety_score_authorizes_execution' =>
                false,

            'activation_readiness_score_authorizes_activation' =>
                false,

            'activation_risk_score_authorizes_activation' =>
                false,

            'condition_resolution_score_authorizes_activation' =>
                false,

            'evidence_resolution_score_authorizes_activation' =>
                false,

            'combined_resolution_score_authorizes_activation' =>
                false,

            'condition_pressure_score_authorizes_activation' =>
                false,

            'restriction_pressure_score_authorizes_activation' =>
                false,

            'eligibility_safety_intelligence_authorizes_ai_change' =>
                false,

            'eligibility_safety_intelligence_authorizes_execution' =>
                false,

            'eligibility_safety_intelligence_authorizes_deployment' =>
                false,

            'eligibility_safety_intelligence_authorizes_rollback' =>
                false,

            'eligibility_safety_intelligence_authorizes_clinical_action' =>
                false,

            'eligibility_safety_intelligence_overrides_human_review' =>
                false,

            'eligibility_safety_intelligence_overrides_governance_confirmation' =>
                false,

            'eligibility_safety_intelligence_overrides_activation_authorization' =>
                false,

            'eligibility_safety_intelligence_overrides_execution_authorization' =>
                false,

            'eligibility_safety_intelligence_overrides_evidence_requirements' =>
                false,

            'automatic_activation_authorization_allowed' =>
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
                'Step 69.5 controlled activation authorization eligibility and safety intelligence evaluates authorized-human review eligibility, activation-authorization completion eligibility, unrestricted authorization eligibility, conditional review eligibility, deferral eligibility, additional-resolution eligibility, execution-review eligibility, safety restrictions, governance integrity, readiness, risk, evidence state, condition state, restriction state, and human-management requirements. Eligibility and safety classifications remain informational and advisory only. They do not make, record, or complete controlled activation authorization; authorize activation execution; activate the strategic plan; resolve or waive conditions; remove restrictions; validate evidence; alter governance confirmation; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Controlled activation authorization authority and activation execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}