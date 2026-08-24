<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionEligibilitySafetyIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanFinalGovernanceDecisionConditionRestrictionIntelligenceEngine $conditionRestrictionEngine
    ) {
    }

    public function analyze(?int $finalGovernanceDecisionId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resolve Final Governance Decision
        |--------------------------------------------------------------------------
        */

        $decision = $finalGovernanceDecisionId
            ? AIGovernanceStrategicPlanFinalGovernanceDecision::find($finalGovernanceDecisionId)
            : AIGovernanceStrategicPlanFinalGovernanceDecision::latest('id')->first();

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan final governance decision record is available for eligibility and safety intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 67.4 Condition / Restriction Intelligence
        |--------------------------------------------------------------------------
        */

        $conditionRestriction =
            $this->conditionRestrictionEngine->analyze($decision->id);

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary'] ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary'] ?? [];

        $dominantDecisionCondition =
            $conditionRestriction['dominant_decision_condition'] ?? null;

        $dominantDecisionRestriction =
            $conditionRestriction['dominant_decision_restriction'] ?? null;

        $blockingDecisionConditions =
            $conditionRestriction['blocking_decision_conditions'] ?? [];

        $constrainingDecisionConditions =
            $conditionRestriction['constraining_decision_conditions'] ?? [];

        $resolvedDecisionConditions =
            $conditionRestriction['resolved_decision_conditions'] ?? [];

        $materialDecisionRestrictions =
            $conditionRestriction['material_decision_restrictions'] ?? [];

        $criticalDecisionRestrictions =
            $conditionRestriction['critical_decision_restrictions'] ?? [];

        $finalGovernanceDecisionStateContext =
            $conditionRestriction['final_governance_decision_state_context'] ?? [];

        $reviewContext =
            $conditionRestriction['review_context'] ?? [];

        $validationContext =
            $conditionRestriction['validation_context'] ?? [];

        $governanceContext =
            $conditionRestriction['governance_context'] ?? [];

        $sourceContext =
            $conditionRestriction['source_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Current Governance State
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionRecorded =
            !empty($decision->final_governance_decision);

        $sourceFinalHumanDecisionRecorded =
            (bool) $decision->source_final_human_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $decision->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $decision->source_governance_validation_completed;

        $decisionMadeByAuthorizedHuman =
            (bool) $decision->decision_made_by_authorized_human;

        $finalGovernanceConfirmationCompleted =
            (bool) $decision->final_governance_confirmation_completed;

        $authorizedFinalDecisionAttributionComplete =
            $decisionMadeByAuthorizedHuman
            && !empty($decision->decided_by)
            && !empty($decision->decider_role)
            && !empty($decision->decided_at);

        $confirmationAttributionComplete =
            $finalGovernanceConfirmationCompleted
            && !empty($decision->confirmed_by)
            && !empty($decision->confirmer_role)
            && !empty($decision->confirmed_at);

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) (
                $conditionSummary['total_conditions']
                ?? count($decision->decision_conditions ?? [])
            );

        $openConditions =
            (int) (
                $conditionSummary['open_conditions']
                ?? 0
            );

        $blockingConditions =
            (int) (
                $conditionSummary['blocking_conditions']
                ?? count($blockingDecisionConditions)
            );

        $constrainingConditions =
            (int) (
                $conditionSummary['constraining_conditions']
                ?? count($constrainingDecisionConditions)
            );

        $resolvedConditions =
            (int) (
                $conditionSummary['resolved_conditions']
                ?? count($resolvedDecisionConditions)
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
            );

        $totalRestrictions =
            (int) (
                $restrictionSummary['total_restrictions']
                ?? count($decision->decision_restrictions ?? [])
            );

        $materialRestrictions =
            (int) (
                $restrictionSummary['material_restrictions']
                ?? count($materialDecisionRestrictions)
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary['critical_restrictions']
                ?? count($criticalDecisionRestrictions)
            );

        /*
        |--------------------------------------------------------------------------
        | Resolution / Pressure Context
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            (float) (
                $conditionRestrictionState['condition_resolution_score']
                ?? $decision->condition_resolution_score
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $conditionRestrictionState['evidence_resolution_score']
                ?? $decision->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $conditionRestrictionState['combined_resolution_score']
                ?? $decision->combined_resolution_score
                ?? 0
            );

        $conditionPressureScore =
            (float) (
                $conditionRestrictionState['condition_pressure_score']
                ?? 0
            );

        $restrictionPressureScore =
            (float) (
                $conditionRestrictionState['restriction_pressure_score']
                ?? 0
            );

        $combinedPressureScore =
            (float) (
                $conditionRestrictionState['combined_condition_restriction_pressure_score']
                ?? max($conditionPressureScore, $restrictionPressureScore)
            );

        /*
        |--------------------------------------------------------------------------
        | Decision Risk
        |--------------------------------------------------------------------------
        */

        $decisionRiskLevel =
            $decision->final_decision_risk_level
            ?? 'UNKNOWN';

        $decisionRiskScore =
            (float) (
                $decision->final_decision_risk_score
                ?? 0
            );

        $highRisk =
            $decisionRiskScore >= 75
            || in_array(
                strtoupper((string) $decisionRiskLevel),
                [
                    'HIGH',
                    'CRITICAL',
                    'HIGH_RISK',
                    'CRITICAL_RISK',
                    'HIGH_HUMAN_DECISION_RISK',
                    'CRITICAL_HUMAN_DECISION_RISK',
                ],
                true
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility Checks
        |--------------------------------------------------------------------------
        */

        $eligibilityChecks = [];

        $eligibilityChecks['final_governance_decision_record_available'] = [
            'passed' => true,
            'message' => 'Strategic plan final governance decision record is available.',
        ];

        $eligibilityChecks['authorized_human_review_available'] = [
            'passed' => true,
            'message' => 'The final governance decision package may be reviewed by explicitly authorized human governance.',
        ];

        $eligibilityChecks['source_final_human_decision_available'] = [
            'passed' => $sourceFinalHumanDecisionRecorded,
            'value' => $sourceFinalHumanDecisionRecorded,
            'message' => 'An explicitly authorized human strategic plan decision should be recorded before unrestricted final governance progression.',
        ];

        $eligibilityChecks['source_decision_authorized_human_attribution'] = [
            'passed' => $sourceDecisionMadeByAuthorizedHuman,
            'value' => $sourceDecisionMadeByAuthorizedHuman,
            'message' => 'The source strategic plan decision should remain attributable to explicitly authorized human governance.',
        ];

        $eligibilityChecks['source_governance_validation_completed'] = [
            'passed' => $sourceGovernanceValidationCompleted,
            'value' => $sourceGovernanceValidationCompleted,
            'message' => 'Required source governance validation should be completed through authorized human governance before unrestricted final governance progression.',
        ];

        $eligibilityChecks['blocking_final_governance_conditions_absent'] = [
            'passed' => $blockingConditions === 0,
            'value' => $blockingConditions,
            'message' => 'No blocking final governance decision condition should remain before unrestricted progression.',
        ];

        $eligibilityChecks['critical_final_governance_conditions_absent'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical final governance decision condition should remain open.',
        ];

        $eligibilityChecks['material_final_governance_restrictions_absent'] = [
            'passed' => $materialRestrictions === 0,
            'value' => $materialRestrictions,
            'message' => 'No material final governance decision restriction should remain before unrestricted progression.',
        ];

        $eligibilityChecks['critical_final_governance_restrictions_absent'] = [
            'passed' => $criticalRestrictions === 0,
            'value' => $criticalRestrictions,
            'message' => 'No critical final governance decision restriction should remain active.',
        ];

        $eligibilityChecks['decision_risk_acceptable_for_unrestricted_progression'] = [
            'passed' => !$highRisk,
            'value' => $decisionRiskScore,
            'message' => 'Final governance decision risk should be below the high-risk threshold before unrestricted progression is considered.',
        ];

        $eligibilityChecks['automatic_final_decision_disabled'] = [
            'passed' =>
                !$decision->automatic_final_decision_allowed
                && !$decision->automatic_approval_allowed
                && !$decision->automatic_rejection_allowed
                && !$decision->automatic_conditional_approval_allowed
                && !$decision->automatic_deferral_allowed
                && !$decision->automatic_risk_acceptance_allowed
                && !$decision->automatic_activation_allowed
                && !$decision->automatic_condition_resolution_allowed
                && !$decision->automatic_evidence_validation_allowed
                && !$decision->automatic_execution_allowed
                && !$decision->automatic_change_allowed
                && !$decision->automatic_deployment_allowed
                && !$decision->automatic_rollback_allowed
                && !$decision->automatic_clinical_action_allowed,
            'message' => 'Automatic final decision, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        $eligibilityChecks['authorized_human_final_decision_authority_protected'] = [
            'passed' => (bool) $decision->authorized_human_final_decision_required,
            'message' => 'Final governance decision authority remains reserved for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility State
        |--------------------------------------------------------------------------
        */

        $authorizedHumanReviewEligible = true;

        $unrestrictedFinalDecisionEligible =
            $sourceFinalHumanDecisionRecorded
            && $sourceDecisionMadeByAuthorizedHuman
            && $sourceGovernanceValidationCompleted
            && $blockingConditions === 0
            && $materialRestrictions === 0
            && $criticalRestrictions === 0
            && $criticalOpenConditions === 0
            && !$highRisk;

        $confirmationEligible =
            $finalGovernanceDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && $authorizedFinalDecisionAttributionComplete;

        $activationEligible =
            $confirmationEligible
            && $finalGovernanceConfirmationCompleted
            && $confirmationAttributionComplete
            && $blockingConditions === 0
            && $materialRestrictions === 0
            && !$highRisk;

        /*
        |--------------------------------------------------------------------------
        | Eligibility Score
        |--------------------------------------------------------------------------
        |
        | This score is informational only.
        | It must never authorize any governance decision.
        |--------------------------------------------------------------------------
        */

        $eligibilityComponents = [
            $sourceFinalHumanDecisionRecorded ? 20 : 0,
            $sourceDecisionMadeByAuthorizedHuman ? 15 : 0,
            $sourceGovernanceValidationCompleted ? 15 : 0,
            $blockingConditions === 0 ? 15 : 0,
            $materialRestrictions === 0 ? 15 : 0,
            $criticalRestrictions === 0 ? 5 : 0,
            $criticalOpenConditions === 0 ? 5 : 0,
            !$highRisk ? 10 : 0,
        ];

        $eligibilityScore =
            round(array_sum($eligibilityComponents), 2);

        if ($unrestrictedFinalDecisionEligible) {
            $eligibilityClassification =
                'ELIGIBLE_FOR_UNRESTRICTED_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        } elseif ($authorizedHumanReviewEligible) {
            $eligibilityClassification =
                'ELIGIBLE_FOR_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_REVIEW';
        } else {
            $eligibilityClassification =
                'NOT_ELIGIBLE_FOR_FINAL_GOVERNANCE_REVIEW';
        }

        $unrestrictedEligibility =
            $unrestrictedFinalDecisionEligible
                ? 'ELIGIBLE_FOR_UNRESTRICTED_FINAL_GOVERNANCE_PROGRESSION'
                : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_FINAL_GOVERNANCE_PROGRESSION';

        $confirmationEligibility =
            $confirmationEligible
                ? 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_CONFIRMATION'
                : 'NOT_ELIGIBLE_FOR_FINAL_GOVERNANCE_CONFIRMATION';

        $activationEligibility =
            $activationEligible
                ? 'ELIGIBLE_FOR_SEPARATE_AUTHORIZED_GOVERNANCE_ACTIVATION_REVIEW'
                : 'NOT_ELIGIBLE_FOR_STRATEGIC_PLAN_ACTIVATION';

        $finalDecisionEligibilityState = [
            'final_governance_review_eligibility' =>
                $eligibilityClassification,

            'unrestricted_final_decision_eligibility' =>
                $unrestrictedEligibility,

            'final_governance_confirmation_eligibility' =>
                $confirmationEligibility,

            'strategic_plan_activation_eligibility' =>
                $activationEligibility,

            'final_governance_decision_eligibility_score' =>
                $eligibilityScore,

            'authorized_human_review_allowed' =>
                $authorizedHumanReviewEligible,

            'unrestricted_final_governance_progression_allowed' =>
                $unrestrictedFinalDecisionEligible,

            'final_governance_confirmation_allowed' =>
                $confirmationEligible,

            'strategic_plan_activation_allowed' =>
                $activationEligible,

            'final_governance_decision_recorded' =>
                $finalGovernanceDecisionRecorded,

            'source_final_human_decision_recorded' =>
                $sourceFinalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'final_governance_confirmation_completed' =>
                $finalGovernanceConfirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety Restrictions
        |--------------------------------------------------------------------------
        */

        $safetyRestrictions = [];

        if (!$sourceFinalHumanDecisionRecorded) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',

                'category' =>
                    'HUMAN_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Unrestricted final governance decision progression cannot occur until an explicitly authorized human strategic plan decision is recorded.',
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_REQUIRED',

                'category' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'The source strategic plan decision must remain attributable to explicitly authorized human governance.',
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_INCOMPLETE',

                'category' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Required source governance validation has not yet been completed.',
            ];
        }

        if ($blockingConditions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_FINAL_GOVERNANCE_DECISION_CONDITIONS',

                'category' =>
                    'DECISION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking final governance decision condition(s) remain active.",
            ];
        }

        if ($materialRestrictions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'MATERIAL_FINAL_GOVERNANCE_DECISION_RESTRICTIONS',

                'category' =>
                    'DECISION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material final governance decision restriction(s) remain active.",
            ];
        }

        if ($highRisk) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'HIGH_FINAL_GOVERNANCE_DECISION_RISK',

                'category' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $decisionRiskScore,

                'message' =>
                    "Strategic plan final governance decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.",
            ];
        }

        if (!$authorizedFinalDecisionAttributionComplete) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'FINAL_GOVERNANCE_DECISION_ATTRIBUTION_REQUIRED',

                'category' =>
                    'FINAL_DECISION_AUTHORIZATION',

                'severity' =>
                    'MODERATE',

                'message' =>
                    'Any recorded final governance decision must remain attributable to an explicitly authorized human governance decision-maker.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Safety Restriction
        |--------------------------------------------------------------------------
        */

        $dominantSafetyRestriction =
            $safetyRestrictions[0]
            ?? [
                'restriction_code' =>
                    'NO_MATERIAL_FINAL_GOVERNANCE_SAFETY_RESTRICTION',

                'category' =>
                    'NONE',

                'severity' =>
                    'ADVISORY',

                'message' =>
                    'No material final governance safety restriction is currently represented.',
            ];

        /*
        |--------------------------------------------------------------------------
        | Dominant Eligibility Restriction
        |--------------------------------------------------------------------------
        */

        if (!$sourceFinalHumanDecisionRecorded) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',

                'restriction_type' =>
                    'HUMAN_DECISION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Unrestricted final governance progression is not eligible until an explicitly authorized human strategic plan decision has been recorded.',
            ];
        } elseif (!$sourceDecisionMadeByAuthorizedHuman) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_REQUIRED',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Unrestricted final governance progression requires explicit authorized-human attribution of the source strategic plan decision.',
            ];
        } elseif (!$sourceGovernanceValidationCompleted) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_REQUIRED',

                'restriction_type' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    false,

                'message' =>
                    'Unrestricted final governance progression requires completion of the applicable authorized-human governance-validation process.',
            ];
        } elseif ($blockingConditions > 0) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'BLOCKING_FINAL_GOVERNANCE_DECISION_CONDITIONS',

                'restriction_type' =>
                    'DECISION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking final governance decision condition(s) currently prevent unrestricted progression.",
            ];
        } elseif ($materialRestrictions > 0) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'MATERIAL_FINAL_GOVERNANCE_DECISION_RESTRICTIONS',

                'restriction_type' =>
                    'DECISION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material final governance decision restriction(s) currently prevent unrestricted progression.",
            ];
        } elseif ($highRisk) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'HIGH_FINAL_GOVERNANCE_DECISION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $decisionRiskScore,

                'message' =>
                    'Final governance decision risk remains too high for unrestricted progression.',
            ];
        } else {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'NO_MATERIAL_ELIGIBILITY_RESTRICTION',

                'restriction_type' =>
                    'NONE',

                'severity' =>
                    'ADVISORY',

                'value' =>
                    null,

                'message' =>
                    'No material final governance eligibility restriction is currently represented.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Score
        |--------------------------------------------------------------------------
        |
        | Informational only.
        | A higher score does not authorize a decision.
        |--------------------------------------------------------------------------
        */

        $safetyScore = 100.0;

        if (!$sourceFinalHumanDecisionRecorded) {
            $safetyScore -= 20;
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $safetyScore -= 15;
        }

        if (!$sourceGovernanceValidationCompleted) {
            $safetyScore -= 15;
        }

        $safetyScore -= min(20, $blockingConditions * 5);
        $safetyScore -= min(15, $materialRestrictions * 3.75);

        if ($highRisk) {
            $safetyScore -= 15;
        }

        $safetyScore =
            round(max(0, min(100, $safetyScore)), 2);

        if (
            $criticalRestrictions > 0
            || $criticalOpenConditions > 0
        ) {
            $safetyStatus =
                'CONTROLLED_WITH_CRITICAL_FINAL_GOVERNANCE_RESTRICTIONS';

            $safetyLevel =
                'CRITICAL';
        } elseif (
            $blockingConditions > 0
            || $materialRestrictions > 0
            || $highRisk
            || !$sourceFinalHumanDecisionRecorded
            || !$sourceDecisionMadeByAuthorizedHuman
            || !$sourceGovernanceValidationCompleted
        ) {
            $safetyStatus =
                'CONTROLLED_WITH_MATERIAL_FINAL_GOVERNANCE_RESTRICTIONS';

            $safetyLevel =
                'HIGH';
        } elseif ($constrainingConditions > 0) {
            $safetyStatus =
                'CONTROLLED_WITH_CONSTRAINED_FINAL_GOVERNANCE_PROGRESSION';

            $safetyLevel =
                'MODERATE';
        } else {
            $safetyStatus =
                'CONTROLLED_FINAL_GOVERNANCE_REVIEW_STATE';

            $safetyLevel =
                'CONTROLLED';
        }

        $finalDecisionSafetyState = [
            'final_governance_decision_safety_status' =>
                $safetyStatus,

            'final_governance_decision_safety_level' =>
                $safetyLevel,

            'final_governance_decision_safety_score' =>
                $safetyScore,

            'governance_integrity_intact' =>
                true,

            'human_governance_oversight_required' =>
                true,

            'authorized_human_final_decision_required' =>
                true,

            'source_final_human_decision_required' =>
                !$sourceFinalHumanDecisionRecorded,

            'source_authorized_human_attribution_required' =>
                !$sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_required' =>
                !$sourceGovernanceValidationCompleted,

            'condition_resolution_required' =>
                $blockingConditions > 0,

            'restriction_governance_required' =>
                $materialRestrictions > 0,

            'risk_governance_required' =>
                $highRisk,

            'safe_for_authorized_human_review' =>
                true,

            'safe_for_unrestricted_progression' =>
                $unrestrictedFinalDecisionEligible,

            'safe_for_automatic_final_decision' =>
                false,

            'safe_for_automatic_activation' =>
                false,

            'human_management_attention_required' =>
                !$unrestrictedFinalDecisionEligible,

            'immediate_human_intervention_required' =>
                $criticalRestrictions > 0
                || $criticalOpenConditions > 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility Summary
        |--------------------------------------------------------------------------
        */

        $eligibilitySummary = [
            'final_governance_review_eligibility' =>
                $eligibilityClassification,

            'unrestricted_final_decision_eligibility' =>
                $unrestrictedEligibility,

            'final_governance_confirmation_eligibility' =>
                $confirmationEligibility,

            'strategic_plan_activation_eligibility' =>
                $activationEligibility,

            'final_governance_decision_eligibility_score' =>
                $eligibilityScore,

            'blocking_conditions' =>
                $blockingConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'source_final_human_decision_recorded' =>
                $sourceFinalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety Summary
        |--------------------------------------------------------------------------
        */

        $safetySummary = [
            'final_governance_decision_safety_status' =>
                $safetyStatus,

            'final_governance_decision_safety_level' =>
                $safetyLevel,

            'final_governance_decision_safety_score' =>
                $safetyScore,

            'total_safety_restrictions' =>
                count($safetyRestrictions),

            'blocking_conditions' =>
                $blockingConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'condition_pressure_score' =>
                $conditionPressureScore,

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,

            'decision_risk_level' =>
                $decisionRiskLevel,

            'decision_risk_score' =>
                $decisionRiskScore,

            'safe_for_authorized_human_review' =>
                true,

            'safe_for_unrestricted_progression' =>
                $unrestrictedFinalDecisionEligible,
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Context
        |--------------------------------------------------------------------------
        */

        $authorizationContext = [
            'source_final_human_decision_recorded' =>
                $sourceFinalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'final_governance_decision_recorded' =>
                $finalGovernanceDecisionRecorded,

            'decided_by' =>
                $decision->decided_by,

            'decider_role' =>
                $decision->decider_role,

            'decided_at' =>
                $decision->decided_at,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'authorized_final_decision_attribution_complete' =>
                $authorizedFinalDecisionAttributionComplete,

            'confirmed_by' =>
                $decision->confirmed_by,

            'confirmer_role' =>
                $decision->confirmer_role,

            'confirmed_at' =>
                $decision->confirmed_at,

            'final_governance_confirmation_completed' =>
                $finalGovernanceConfirmationCompleted,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Context
        |--------------------------------------------------------------------------
        */

        $governanceValidationContext = [
            'source_governance_validation_decision' =>
                $decision->source_governance_validation_decision,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_validation_required' =>
                (bool) $decision->governance_validation_required,

            'source_governance_validation_blocks_unrestricted_progression' =>
                !$sourceGovernanceValidationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $riskContext = [
            'final_decision_risk_level' =>
                $decisionRiskLevel,

            'final_decision_risk_score' =>
                $decisionRiskScore,

            'high_risk' =>
                $highRisk,

            'risk_requires_authorized_human_governance' =>
                $highRisk,

            'risk_score_authorizes_final_decision' =>
                false,

            'risk_score_authorizes_activation' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility Analysis
        |--------------------------------------------------------------------------
        */

        $eligibilityAnalysis = [
            'authorized_human_review_eligible' =>
                $authorizedHumanReviewEligible,

            'unrestricted_final_decision_eligible' =>
                $unrestrictedFinalDecisionEligible,

            'confirmation_eligible' =>
                $confirmationEligible,

            'activation_eligible' =>
                $activationEligible,

            'eligibility_score' =>
                $eligibilityScore,

            'eligibility_checks' =>
                $eligibilityChecks,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety Analysis
        |--------------------------------------------------------------------------
        */

        $safetyAnalysis = [
            'safety_status' =>
                $safetyStatus,

            'safety_level' =>
                $safetyLevel,

            'safety_score' =>
                $safetyScore,

            'safety_restrictions' =>
                $safetyRestrictions,

            'material_safety_restrictions_present' =>
                count($safetyRestrictions) > 0,

            'critical_safety_restrictions_present' =>
                $criticalRestrictions > 0
                || $criticalOpenConditions > 0,

            'safe_for_authorized_human_review' =>
                true,

            'safe_for_unrestricted_progression' =>
                $unrestrictedFinalDecisionEligible,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $eligibilitySafetyFindings = [
            "Final governance decision eligibility and safety intelligence is based on final governance decision record {$decision->id}.",

            'Current final governance review eligibility is '
                .$eligibilityClassification.'.',

            'Current unrestricted final governance decision eligibility is '
                .$unrestrictedEligibility.'.',

            'Current final governance confirmation eligibility is '
                .$confirmationEligibility.'.',

            'Current strategic plan activation eligibility is '
                .$activationEligibility.'.',

            "Current final governance decision eligibility score is {$eligibilityScore}.",

            'Current final governance decision safety status is '
                .$safetyStatus.'.',

            'Current final governance decision safety level is '
                .$safetyLevel.'.',

            "Current final governance decision safety score is {$safetyScore}.",

            "{$totalConditions} final governance decision condition(s) are represented.",

            "{$openConditions} final governance decision condition(s) remain open.",

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$constrainingConditions} constraining final governance decision condition(s) remain active.",

            "{$resolvedConditions} final governance decision condition(s) are resolved.",

            "{$totalRestrictions} final governance decision restriction(s) are represented.",

            "{$materialRestrictions} material final governance decision restriction(s) remain active.",

            "{$criticalRestrictions} critical final governance decision restriction(s) are represented.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            "Current strategic plan final governance decision risk is {$decisionRiskLevel} with score {$decisionRiskScore}.",

            'Final authorized-human source strategic plan decision recorded is '
                .($sourceFinalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Source strategic plan decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Final governance decision recorded is '
                .($finalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Final governance decision made by authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Final governance confirmation completed is '
                .($finalGovernanceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Eligibility for authorized-human review does not constitute approval, rejection, conditional approval, deferral, risk acceptance, activation, confirmation, or any final governance decision.',

            'Final governance decision eligibility and safety intelligence remains advisory and does not change governance state or exercise decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceFinalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human strategic plan decision before unrestricted final governance progression is considered.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source strategic plan decision remains explicitly attributable to an authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete required source governance validation through explicitly authorized human governance before unrestricted final governance progression.';
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConditions} blocking final governance decision condition(s) before unrestricted final governance progression.";
        }

        if ($materialRestrictions > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material final governance decision restriction(s).";
        }

        if ($highRisk) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while final strategic plan decision risk remains high.';
        }

        if (!$authorizedFinalDecisionAttributionComplete) {
            $managementPriorities[] =
                'Ensure any final governance decision is explicitly attributable to an identified authorized human governance decision-maker.';
        }

        if (!$confirmationAttributionComplete) {
            $managementPriorities[] =
                'Ensure any completed final governance confirmation remains attributable to an explicitly identified authorized human governance confirmer.';
        }

        $managementPriorities[] =
            'Keep final governance eligibility and safety intelligence strictly separate from final governance decision authority.';

        $managementPriorities[] =
            'Do not interpret eligibility scores, safety scores, readiness scores, condition classifications, restriction severity, pressure scores, or resolution scores as authorization for a governance decision or activation.';

        $managementPriorities[] =
            'Preserve human governance authority, decision attribution, governance-validation integrity, confirmation attribution, evidence quality, traceability, safety controls, and authority separation throughout final governance progression.';

        $managementPriorities =
            array_values(array_unique($managementPriorities));

        /*
        |--------------------------------------------------------------------------
        | Return Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

            'strategic_plan_final_governance_decision_id' =>
                $decision->id,

            'final_governance_decision_code' =>
                $decision->final_governance_decision_code,

            'strategic_plan_decision_validation_id' =>
                $decision->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $decision->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $decision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $decision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $decision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $decision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $decision->lifecycle_snapshot_id,

            'decision_scope' =>
                $decision->decision_scope,

            'resident_id' =>
                $decision->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Core Eligibility / Safety
            |--------------------------------------------------------------------------
            */

            'final_decision_eligibility_state' =>
                $finalDecisionEligibilityState,

            'final_decision_safety_state' =>
                $finalDecisionSafetyState,

            'eligibility_summary' =>
                $eligibilitySummary,

            'safety_summary' =>
                $safetySummary,

            'dominant_eligibility_restriction' =>
                $dominantEligibilityRestriction,

            'dominant_safety_restriction' =>
                $dominantSafetyRestriction,

            'eligibility_checks' =>
                $eligibilityChecks,

            'safety_restrictions' =>
                $safetyRestrictions,

            'eligibility_analysis' =>
                $eligibilityAnalysis,

            'safety_analysis' =>
                $safetyAnalysis,

            /*
            |--------------------------------------------------------------------------
            | Step 67.4 Context
            |--------------------------------------------------------------------------
            */

            'condition_restriction_context' => [
                'condition_restriction_state' =>
                    $conditionRestrictionState,

                'condition_summary' =>
                    $conditionSummary,

                'restriction_summary' =>
                    $restrictionSummary,

                'dominant_decision_condition' =>
                    $dominantDecisionCondition,

                'dominant_decision_restriction' =>
                    $dominantDecisionRestriction,

                'blocking_decision_conditions' =>
                    $blockingDecisionConditions,

                'constraining_decision_conditions' =>
                    $constrainingDecisionConditions,

                'material_decision_restrictions' =>
                    $materialDecisionRestrictions,

                'critical_decision_restrictions' =>
                    $criticalDecisionRestrictions,
            ],

            'authorization_context' =>
                $authorizationContext,

            'governance_validation_context' =>
                $governanceValidationContext,

            'risk_context' =>
                $riskContext,

            'final_governance_decision_state_context' =>
                $finalGovernanceDecisionStateContext,

            'review_context' =>
                $reviewContext,

            'validation_context' =>
                $validationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'eligibility_safety_findings' =>
                $eligibilitySafetyFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 67.5 Guardrails
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_eligibility_safety_guardrails' => [
                'final_governance_decision_eligibility_safety_intelligence_enabled' =>
                    true,

                'eligibility_safety_intelligence_is_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_makes_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_records_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_confirms_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_approves_strategic_plan' =>
                    false,

                'eligibility_safety_intelligence_rejects_strategic_plan' =>
                    false,

                'eligibility_safety_intelligence_conditionally_approves_strategic_plan' =>
                    false,

                'eligibility_safety_intelligence_defers_strategic_plan' =>
                    false,

                'eligibility_safety_intelligence_accepts_governance_risk' =>
                    false,

                'eligibility_safety_intelligence_activates_strategic_plan' =>
                    false,

                'eligibility_safety_intelligence_resolves_conditions' =>
                    false,

                'eligibility_safety_intelligence_waives_conditions' =>
                    false,

                'eligibility_safety_intelligence_removes_restrictions' =>
                    false,

                'eligibility_safety_intelligence_downgrades_restrictions' =>
                    false,

                'eligibility_safety_intelligence_resolves_dependencies' =>
                    false,

                'eligibility_safety_intelligence_validates_evidence' =>
                    false,

                'eligibility_safety_intelligence_changes_source_human_decision' =>
                    false,

                'eligibility_safety_intelligence_changes_governance_validation' =>
                    false,

                'eligibility_safety_intelligence_changes_final_governance_decision_status' =>
                    false,

                'eligibility_safety_intelligence_changes_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_changes_final_governance_outcome' =>
                    false,

                'eligibility_safety_intelligence_changes_plan_status' =>
                    false,

                'eligibility_safety_intelligence_changes_action_state' =>
                    false,

                'eligibility_safety_intelligence_changes_priority' =>
                    false,

                'eligibility_safety_intelligence_changes_eligibility_record' =>
                    false,

                'eligibility_score_authorizes_final_decision' =>
                    false,

                'eligibility_score_authorizes_approval' =>
                    false,

                'eligibility_score_authorizes_activation' =>
                    false,

                'safety_score_authorizes_final_decision' =>
                    false,

                'safety_score_authorizes_approval' =>
                    false,

                'safety_score_authorizes_activation' =>
                    false,

                'review_eligibility_authorizes_final_decision' =>
                    false,

                'unrestricted_eligibility_authorizes_final_decision' =>
                    false,

                'confirmation_eligibility_confirms_final_decision' =>
                    false,

                'activation_eligibility_activates_strategic_plan' =>
                    false,

                'condition_resolution_score_authorizes_final_decision' =>
                    false,

                'evidence_resolution_score_authorizes_final_decision' =>
                    false,

                'combined_resolution_score_authorizes_final_decision' =>
                    false,

                'condition_pressure_score_authorizes_final_decision' =>
                    false,

                'restriction_pressure_score_authorizes_final_decision' =>
                    false,

                'decision_risk_score_authorizes_final_decision' =>
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

                'eligibility_safety_intelligence_overrides_governance_validation' =>
                    false,

                'eligibility_safety_intelligence_overrides_final_human_decision' =>
                    false,

                'eligibility_safety_intelligence_overrides_final_governance_decision' =>
                    false,

                'eligibility_safety_intelligence_overrides_evidence_requirements' =>
                    false,

                'automatic_condition_resolution_allowed' =>
                    false,

                'automatic_restriction_removal_allowed' =>
                    false,

                'automatic_evidence_validation_allowed' =>
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

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,

                'message' =>
                    'Step 67.5 final governance decision eligibility and safety intelligence evaluates authorized-human review eligibility, unrestricted final-governance progression eligibility, confirmation eligibility, activation eligibility, material decision restrictions, unresolved conditions, source human-decision attribution, source governance-validation state, decision risk, safety controls, and governance readiness. Eligibility and safety classifications are advisory only. Eligibility for authorized-human review does not constitute a final governance decision, approval, rejection, conditional approval, deferral, risk acceptance, confirmation, strategic plan activation, governance validation, condition resolution, restriction removal, evidence validation, AI modification, execution, deployment, rollback, or clinical action. Scores, readiness indicators, eligibility states, safety states, condition classifications, restriction classifications, and risk classifications do not authorize any governance action. Final governance decision authority remains reserved exclusively for explicitly authorized human governance.',
            ],
        ];
    }
}