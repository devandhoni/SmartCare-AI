<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationEligibilitySafetyIntelligenceEngine
{
    public function analyze(?int $validationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load Validation Record
        |--------------------------------------------------------------------------
        */

        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision validation record is available for eligibility and safety analysis.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 66 Intelligence Engines
        |--------------------------------------------------------------------------
        |
        | 66.3 = Validation State Intelligence
        | 66.4 = Validation Condition / Evidence Intelligence
        |
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine::class
        );

        $conditionEvidenceEngine = app(
            AIGovernanceStrategicPlanDecisionValidationConditionEvidenceIntelligenceEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($validation->id);
        $conditionEvidence = $conditionEvidenceEngine->analyze($validation->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $validatorContext =
            $state['validator_context']
            ?? $state['validation_authority_context']
            ?? [];

        $reviewContext =
            $state['review_context'] ?? [];

        $governanceContext =
            $state['governance_context'] ?? [];

        $conditionEvidenceState =
            $conditionEvidence['condition_evidence_state']
            ?? $conditionEvidence['validation_condition_evidence_state']
            ?? $conditionEvidence['resolution_state']
            ?? [];

        $conditionSummary =
            $conditionEvidence['condition_summary']
            ?? $conditionEvidence['condition_context']
            ?? [];

        $evidenceSummary =
            $conditionEvidence['evidence_summary']
            ?? $conditionEvidence['evidence_context']
            ?? [];

        $dominantRestriction =
            $conditionEvidence['dominant_validation_restriction']
            ?? $conditionEvidence['dominant_restriction']
            ?? $conditionEvidence['dominant_open_condition']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Core Validation State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) (
                $validation->final_human_decision_recorded
                ?? $validationState['final_human_decision_recorded']
                ?? false
            );

        $decisionMadeByAuthorizedHuman =
            (bool) (
                $validation->decision_made_by_authorized_human
                ?? $validationState['decision_made_by_authorized_human']
                ?? false
            );

        $governanceValidationCompleted =
            (bool) (
                $validation->governance_validation_completed
                ?? $validationState['governance_validation_completed']
                ?? false
            );

        $validationMadeByAuthorizedHuman =
            (bool) (
                $validation->validation_made_by_authorized_human
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Condition Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) (
                $conditionSummary['total_conditions']
                ?? $conditionSummary['total_validation_conditions']
                ?? count($validation->validation_conditions ?? [])
            );

        $openConditions =
            (int) (
                $conditionSummary['open_conditions']
                ?? $conditionSummary['open_validation_conditions']
                ?? 0
            );

        $blockingConditions =
            (int) (
                $conditionSummary['blocking_conditions']
                ?? $conditionSummary['blocking_validation_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionSummary['constraining_conditions']
                ?? $conditionSummary['constraining_validation_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? $conditionSummary['critical_validation_conditions']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence Counts
        |--------------------------------------------------------------------------
        */

        $totalEvidenceItems =
            (int) (
                $evidenceSummary['total_evidence_items']
                ?? $evidenceSummary['total_evidence_requirements']
                ?? count($validation->validated_evidence ?? [])
            );

        $validatedEvidenceItems =
            (int) (
                $evidenceSummary['validated_evidence_items']
                ?? $evidenceSummary['satisfied_evidence_items']
                ?? count($validation->validated_evidence ?? [])
            );

        $outstandingEvidenceItems =
            (int) (
                $evidenceSummary['outstanding_evidence_items']
                ?? 0
            );

        $blockingEvidenceItems =
            (int) (
                $evidenceSummary['validation_blocking_evidence_items']
                ?? $evidenceSummary['decision_blocking_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidenceItems =
            (int) (
                $evidenceSummary['critical_outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Resolution Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            (float) (
                $conditionEvidenceState['condition_resolution_score']
                ?? $validation->condition_resolution_score
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $conditionEvidenceState['evidence_resolution_score']
                ?? $validation->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $conditionEvidenceState['combined_resolution_score']
                ?? $validation->combined_resolution_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Risk
        |--------------------------------------------------------------------------
        */

        $decisionRiskLevel =
            $validation->decision_risk_level
            ?? $validationState['decision_risk_level']
            ?? 'UNKNOWN';

        $decisionRiskScore =
            (float) (
                $validation->decision_risk_score
                ?? $validationState['decision_risk_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Validator Attribution
        |--------------------------------------------------------------------------
        */

        $validatorIdentified =
            !empty($validation->validated_by);

        $validatorRoleAvailable =
            !empty($validation->validator_role);

        $validationTimestampAvailable =
            !empty($validation->validated_at);

        $validatorAttributionComplete =
            $validatorIdentified
            && $validatorRoleAvailable
            && $validationTimestampAvailable;

        /*
        |--------------------------------------------------------------------------
        | Eligibility Checks
        |--------------------------------------------------------------------------
        |
        | These checks describe eligibility only.
        | Failed eligibility checks are NOT system failures.
        |
        */

        $eligibilityChecks = [];

        $eligibilityChecks['validation_record_available'] = [
            'passed' => true,
            'message' => 'Governance strategic plan decision validation record is available.',
        ];

        $eligibilityChecks['final_human_decision_available'] = [
            'passed' => $finalHumanDecisionRecorded,
            'value' => $finalHumanDecisionRecorded,
            'message' => 'A final authorized-human strategic plan decision must be recorded before completed governance validation.',
        ];

        $eligibilityChecks['decision_attributed_to_authorized_human'] = [
            'passed' => $decisionMadeByAuthorizedHuman,
            'value' => $decisionMadeByAuthorizedHuman,
            'message' => 'The final strategic plan decision must be attributable to an explicitly authorized human governance decision-maker.',
        ];

        $eligibilityChecks['blocking_validation_conditions_absent'] = [
            'passed' => $blockingConditions === 0,
            'value' => $blockingConditions,
            'message' => 'No blocking governance-validation condition should remain before unrestricted validation completion.',
        ];

        $eligibilityChecks['critical_validation_conditions_absent'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical governance-validation condition should remain open.',
        ];

        $eligibilityChecks['blocking_validation_evidence_absent'] = [
            'passed' => $blockingEvidenceItems === 0,
            'value' => $blockingEvidenceItems,
            'message' => 'No validation-blocking evidence requirement should remain outstanding.',
        ];

        $eligibilityChecks['critical_outstanding_validation_evidence_absent'] = [
            'passed' => $criticalOutstandingEvidenceItems === 0,
            'value' => $criticalOutstandingEvidenceItems,
            'message' => 'No critical governance-validation evidence requirement should remain outstanding.',
        ];

        $eligibilityChecks['validator_attribution_available'] = [
            'passed' => $validatorAttributionComplete,
            'value' => $validatorAttributionComplete,
            'message' => 'Completed governance validation must remain attributable to an identified authorized human governance validator.',
        ];

        $riskAcceptableForUnrestrictedValidation =
            $decisionRiskScore < 75;

        $eligibilityChecks['decision_risk_acceptable_for_unrestricted_validation'] = [
            'passed' => $riskAcceptableForUnrestrictedValidation,
            'value' => $decisionRiskScore,
            'message' => 'Decision risk should be below the high-risk threshold before unrestricted governance validation is considered.',
        ];

        $eligibilityChecks['automatic_validation_disabled'] = [
            'passed' =>
                !$validation->automatic_validation_allowed
                && !$validation->automatic_decision_allowed
                && !$validation->automatic_approval_allowed
                && !$validation->automatic_rejection_allowed
                && !$validation->automatic_activation_allowed
                && !$validation->automatic_condition_resolution_allowed
                && !$validation->automatic_evidence_validation_allowed
                && !$validation->automatic_execution_allowed
                && !$validation->automatic_change_allowed
                && !$validation->automatic_deployment_allowed
                && !$validation->automatic_rollback_allowed
                && !$validation->automatic_clinical_action_allowed,
            'message' => 'Automatic validation, governance decision, approval, rejection, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        $eligibilityChecks['human_validation_authority_protected'] = [
            'passed' =>
                (bool) $validation->human_review_required
                && (bool) $validation->governance_validation_required
                && !$validation->automatic_validation_allowed,
            'message' => 'Governance validation authority remains reserved for authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation Eligibility Score
        |--------------------------------------------------------------------------
        */

        $eligibilityScore = 100.0;

        if (!$finalHumanDecisionRecorded) {
            $eligibilityScore -= 30.0;
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $eligibilityScore -= 20.0;
        }

        if ($blockingConditions > 0) {
            $eligibilityScore -= min(
                25.0,
                $blockingConditions * 6.25
            );
        }

        if ($blockingEvidenceItems > 0) {
            $eligibilityScore -= min(
                15.0,
                $blockingEvidenceItems * 7.5
            );
        }

        if ($decisionRiskScore >= 75) {
            $eligibilityScore -= 10.0;
        }

        if (!$validatorAttributionComplete) {
            $eligibilityScore -= 10.0;
        }

        $eligibilityScore =
            round(
                max(0.0, min(100.0, $eligibilityScore)),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Human Validation Review Eligibility
        |--------------------------------------------------------------------------
        */

        $authorizedHumanValidationReviewEligibility =
            'ELIGIBLE_FOR_AUTHORIZED_HUMAN_VALIDATION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Completed Governance Validation Eligibility
        |--------------------------------------------------------------------------
        */

        $completedValidationEligible =
            $finalHumanDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && $blockingConditions === 0
            && $blockingEvidenceItems === 0
            && $criticalOpenConditions === 0
            && $criticalOutstandingEvidenceItems === 0
            && $riskAcceptableForUnrestrictedValidation
            && $validatorAttributionComplete;

        $governanceValidationCompletionEligibility =
            $completedValidationEligible
                ? 'ELIGIBLE_FOR_COMPLETED_GOVERNANCE_VALIDATION'
                : 'NOT_ELIGIBLE_FOR_COMPLETED_GOVERNANCE_VALIDATION';

        /*
        |--------------------------------------------------------------------------
        | Unrestricted Validation Eligibility
        |--------------------------------------------------------------------------
        */

        $unrestrictedValidationEligibility =
            $completedValidationEligible
                ? 'ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_VALIDATION'
                : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_VALIDATION';

        /*
        |--------------------------------------------------------------------------
        | Conditional Review Eligibility
        |--------------------------------------------------------------------------
        */

        $conditionalValidationEligibility =
            !$completedValidationEligible
                ? 'ELIGIBLE_FOR_CONDITIONAL_GOVERNANCE_REVIEW'
                : 'NOT_REQUIRED';

        /*
        |--------------------------------------------------------------------------
        | Validation Progression
        |--------------------------------------------------------------------------
        */

        $validationProgressionBlocked =
            !$completedValidationEligible;

        $validationProgressionReadiness =
            $completedValidationEligible
                ? 'READY_FOR_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION'
                : (
                    !$finalHumanDecisionRecorded
                        ? 'AWAITING_AUTHORIZED_HUMAN_FINAL_DECISION'
                        : (
                            $blockingConditions > 0
                            || $blockingEvidenceItems > 0
                                ? 'MATERIAL_VALIDATION_REQUIREMENTS_OUTSTANDING'
                                : (
                                    !$validatorAttributionComplete
                                        ? 'AWAITING_AUTHORIZED_HUMAN_VALIDATOR'
                                        : 'RESTRICTED_VALIDATION_PROGRESSION'
                                )
                        )
                );

        /*
        |--------------------------------------------------------------------------
        | Validation Safety Score
        |--------------------------------------------------------------------------
        */

        $safetyScore = 100.0;

        if (!$finalHumanDecisionRecorded) {
            $safetyScore -= 30.0;
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $safetyScore -= 20.0;
        }

        if ($blockingConditions > 0) {
            $safetyScore -= min(
                25.0,
                $blockingConditions * 6.25
            );
        }

        if ($blockingEvidenceItems > 0) {
            $safetyScore -= min(
                10.0,
                $blockingEvidenceItems * 5.0
            );
        }

        if ($decisionRiskScore >= 75) {
            $safetyScore -= 15.0;
        }

        if (!$validatorAttributionComplete) {
            $safetyScore -= 10.0;
        }

        $safetyScore =
            round(
                max(0.0, min(100.0, $safetyScore)),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Safety Classification
        |--------------------------------------------------------------------------
        */

        if ($criticalOpenConditions > 0 || $criticalOutstandingEvidenceItems > 0) {
            $validationSafetyStatus =
                'CONTROLLED_WITH_CRITICAL_GOVERNANCE_VALIDATION_RESTRICTIONS';

            $validationSafetyLevel = 'CRITICAL';
        } elseif (
            !$finalHumanDecisionRecorded
            || !$decisionMadeByAuthorizedHuman
            || $blockingConditions > 0
            || $blockingEvidenceItems > 0
            || $decisionRiskScore >= 75
        ) {
            $validationSafetyStatus =
                'CONTROLLED_WITH_MATERIAL_GOVERNANCE_VALIDATION_RESTRICTIONS';

            $validationSafetyLevel = 'HIGH';
        } elseif (!$validatorAttributionComplete) {
            $validationSafetyStatus =
                'CONTROLLED_PENDING_AUTHORIZED_HUMAN_VALIDATOR_ATTRIBUTION';

            $validationSafetyLevel = 'MODERATE';
        } else {
            $validationSafetyStatus =
                'CONTROLLED_FOR_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION';

            $validationSafetyLevel = 'CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Eligibility Restriction
        |--------------------------------------------------------------------------
        */

        if (!$finalHumanDecisionRecorded) {
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
                    'Completed governance validation is not eligible until an explicitly authorized human strategic plan decision has been recorded.',
            ];
        } elseif (!$decisionMadeByAuthorizedHuman) {
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
                    'Completed governance validation requires the final strategic plan decision to be attributable to an explicitly authorized human governance decision-maker.',
            ];
        } elseif ($criticalOpenConditions > 0) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'CRITICAL_VALIDATION_CONDITIONS_REMAIN',

                'restriction_type' =>
                    'VALIDATION_CONDITION',

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $criticalOpenConditions,

                'message' =>
                    "{$criticalOpenConditions} critical governance-validation condition(s) remain open.",
            ];
        } elseif ($blockingConditions > 0) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_CONDITIONS_REMAIN',

                'restriction_type' =>
                    'VALIDATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking governance-validation condition(s) currently restrict completed governance validation.",
            ];
        } elseif ($blockingEvidenceItems > 0) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_EVIDENCE_REMAINS',

                'restriction_type' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingEvidenceItems,

                'message' =>
                    "{$blockingEvidenceItems} validation-blocking evidence requirement(s) remain outstanding.",
            ];
        } elseif ($decisionRiskScore >= 75) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'HIGH_DECISION_RISK_REQUIRES_HUMAN_VALIDATION_REVIEW',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $decisionRiskScore,

                'message' =>
                    "Strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.",
            ];
        } elseif (!$validatorAttributionComplete) {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_VALIDATOR_ATTRIBUTION_REQUIRED',

                'restriction_type' =>
                    'VALIDATOR_AUTHORIZATION',

                'severity' =>
                    'MODERATE',

                'value' =>
                    false,

                'message' =>
                    'Completed governance validation requires an explicitly identified authorized human governance validator.',
            ];
        } else {
            $dominantEligibilityRestriction = [
                'restriction_code' =>
                    'NO_MATERIAL_VALIDATION_RESTRICTION',

                'restriction_type' =>
                    'NONE',

                'severity' =>
                    'NONE',

                'value' =>
                    0,

                'message' =>
                    'No material governance-validation eligibility restriction is currently identified.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Restrictions
        |--------------------------------------------------------------------------
        */

        $safetyRestrictions = [];

        if (!$finalHumanDecisionRecorded) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',

                'category' =>
                    'HUMAN_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance validation cannot be completed before an explicitly authorized human final strategic plan decision is recorded.',
            ];
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_REQUIRED',

                'category' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance validation cannot be completed unless the final strategic plan decision is attributable to an authorized human governance decision-maker.',
            ];
        }

        if ($blockingConditions > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_CONDITIONS',

                'category' =>
                    'VALIDATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking governance-validation condition(s) remain active.",
            ];
        }

        if ($blockingEvidenceItems > 0) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_EVIDENCE',

                'category' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingEvidenceItems,

                'message' =>
                    "{$blockingEvidenceItems} validation-blocking evidence requirement(s) remain outstanding.",
            ];
        }

        if ($decisionRiskScore >= 75) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'HIGH_DECISION_RISK',

                'category' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $decisionRiskScore,

                'message' =>
                    "Strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.",
            ];
        }

        if (!$validatorAttributionComplete) {
            $safetyRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_VALIDATOR_ATTRIBUTION_REQUIRED',

                'category' =>
                    'VALIDATOR_AUTHORIZATION',

                'severity' =>
                    'MODERATE',

                'message' =>
                    'Governance validation completion must remain attributable to an explicitly identified authorized human governance validator.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Eligibility State
        |--------------------------------------------------------------------------
        */

        $validationEligibilityState = [
            'authorized_human_validation_review_eligibility' =>
                $authorizedHumanValidationReviewEligibility,

            'governance_validation_completion_eligibility' =>
                $governanceValidationCompletionEligibility,

            'unrestricted_validation_eligibility' =>
                $unrestrictedValidationEligibility,

            'conditional_validation_consideration_eligibility' =>
                $conditionalValidationEligibility,

            'validation_deferral_eligibility' =>
                'ELIGIBLE_FOR_GOVERNANCE_VALIDATION_DEFERRAL',

            'request_additional_resolution_eligibility' =>
                'ELIGIBLE_FOR_ADDITIONAL_RESOLUTION_REQUEST',

            'validation_eligibility_score' =>
                $eligibilityScore,

            'validation_progression_readiness' =>
                $validationProgressionReadiness,

            'validation_progression_blocked' =>
                $validationProgressionBlocked,

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'governance_validation_completed' =>
                $governanceValidationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety State
        |--------------------------------------------------------------------------
        */

        $validationSafetyState = [
            'validation_safety_status' =>
                $validationSafetyStatus,

            'validation_safety_level' =>
                $validationSafetyLevel,

            'validation_safety_score' =>
                $safetyScore,

            'governance_integrity_intact' =>
                true,

            'human_validation_required' =>
                true,

            'authorized_validator_required' =>
                true,

            'final_human_decision_required' =>
                true,

            'condition_resolution_required' =>
                $blockingConditions > 0,

            'evidence_resolution_required' =>
                $blockingEvidenceItems > 0,

            'automatic_validation_prohibited' =>
                true,

            'automatic_decision_prohibited' =>
                true,

            'automatic_activation_prohibited' =>
                true,

            'human_management_attention_required' =>
                !$completedValidationEligible,

            'immediate_human_intervention_required' =>
                $criticalOpenConditions > 0
                || $criticalOutstandingEvidenceItems > 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Decision Context
        |--------------------------------------------------------------------------
        */

        $humanDecisionContext = [
            'prepared_decision' =>
                $validation->prepared_decision,

            'final_human_decision' =>
                $validation->final_human_decision,

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'decision_risk_level' =>
                $decisionRiskLevel,

            'decision_risk_score' =>
                $decisionRiskScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Validator Context
        |--------------------------------------------------------------------------
        */

        $validatorEligibilityContext = [
            'validated_by' =>
                $validation->validated_by,

            'validator_role' =>
                $validation->validator_role,

            'validated_at' =>
                $validation->validated_at,

            'validator_identified' =>
                $validatorIdentified,

            'validator_role_available' =>
                $validatorRoleAvailable,

            'validation_timestamp_available' =>
                $validationTimestampAvailable,

            'validator_attribution_complete' =>
                $validatorAttributionComplete,

            'validation_made_by_authorized_human' =>
                $validationMadeByAuthorizedHuman,

            'governance_validation_completed' =>
                $governanceValidationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $eligibilitySafetyFindings = [
            "Strategic plan governance validation eligibility and safety intelligence is based on validation record {$validation->id}.",

            'Current authorized-human validation review eligibility is '
                .$authorizedHumanValidationReviewEligibility.'.',

            'Current governance-validation completion eligibility is '
                .$governanceValidationCompletionEligibility.'.',

            'Current unrestricted governance-validation eligibility is '
                .$unrestrictedValidationEligibility.'.',

            'Current conditional governance-review eligibility is '
                .$conditionalValidationEligibility.'.',

            'Current validation progression readiness is '
                .$validationProgressionReadiness.'.',

            'Current validation eligibility score is '
                .$eligibilityScore.'.',

            'Current validation safety status is '
                .$validationSafetyStatus.'.',

            'Current validation safety level is '
                .$validationSafetyLevel.'.',

            'Current validation safety score is '
                .$safetyScore.'.',

            "{$totalConditions} governance-validation condition(s) are represented.",

            "{$openConditions} governance-validation condition(s) remain open.",

            "{$blockingConditions} blocking governance-validation condition(s) remain active.",

            "{$constrainingConditions} constraining governance-validation condition(s) remain active.",

            "{$criticalOpenConditions} critical governance-validation condition(s) remain open.",

            "{$totalEvidenceItems} validation evidence item(s) are represented.",

            "{$validatedEvidenceItems} validation evidence item(s) are currently represented as validated or satisfied.",

            "{$outstandingEvidenceItems} validation evidence requirement(s) remain outstanding.",

            "{$blockingEvidenceItems} validation-blocking evidence requirement(s) remain outstanding.",

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current strategic plan decision risk is '
                .$decisionRiskLevel
                .' with score '
                .$decisionRiskScore.'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Authorized governance validator attribution complete is '
                .($validatorAttributionComplete ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Validation eligibility identifies what an authorized human validator may review or consider; it does not perform or complete governance validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$finalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human strategic plan decision before governance validation completion is considered.';
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the final strategic plan decision remains attributable to an explicitly authorized human governance decision-maker.';
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConditions} blocking governance-validation condition(s) before completed governance validation.";
        }

        if ($blockingEvidenceItems > 0) {
            $managementPriorities[] =
                "Provide and validate {$blockingEvidenceItems} validation-blocking evidence requirement(s) through authorized human governance.";
        }

        if ($decisionRiskScore >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized human governance oversight while strategic plan decision risk remains high.';
        }

        if (!$validatorAttributionComplete) {
            $managementPriorities[] =
                'Ensure completed governance validation is explicitly attributable to an identified authorized human governance validator.';
        }

        $managementPriorities[] =
            'Keep validation eligibility intelligence separate from governance-validation authority.';

        $managementPriorities[] =
            'Do not interpret evidence readiness, condition-resolution scores, validation eligibility scores, or safety scores as authorization to complete governance validation.';

        $managementPriorities[] =
            'Preserve human governance authority, evidence quality, traceability, validation attribution, safety controls, and authority separation throughout strategic plan validation progression.';

        $managementPriorities =
            array_values(array_unique($managementPriorities));

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_validation_id' =>
                $validation->id,

            'validation_code' =>
                $validation->validation_code,

            'strategic_plan_human_decision_id' =>
                $validation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $validation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $validation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $validation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $validation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $validation->lifecycle_snapshot_id,

            'decision_scope' =>
                $validation->decision_scope,

            'resident_id' =>
                $validation->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Primary Intelligence
            |--------------------------------------------------------------------------
            */

            'validation_eligibility_state' =>
                $validationEligibilityState,

            'validation_safety_state' =>
                $validationSafetyState,

            'eligibility_checks' =>
                $eligibilityChecks,

            'safety_restrictions' =>
                $safetyRestrictions,

            'dominant_eligibility_restriction' =>
                $dominantEligibilityRestriction,

            /*
            |--------------------------------------------------------------------------
            | Supporting Context
            |--------------------------------------------------------------------------
            */

            'condition_context' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditions,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,
            ],

            'evidence_context' => [
                'total_evidence_items' =>
                    $totalEvidenceItems,

                'validated_evidence_items' =>
                    $validatedEvidenceItems,

                'outstanding_evidence_items' =>
                    $outstandingEvidenceItems,

                'blocking_evidence_items' =>
                    $blockingEvidenceItems,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidenceItems,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,
            ],

            'resolution_context' => [
                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'validation_progression_readiness' =>
                    $validationProgressionReadiness,
            ],

            'human_decision_context' =>
                $humanDecisionContext,

            'validator_context' =>
                $validatorEligibilityContext,

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'source_validation_context' => [
                'validation_status' =>
                    $validation->validation_status,

                'validation_mode' =>
                    $validation->validation_mode,

                'prepared_decision' =>
                    $validation->prepared_decision,

                'governance_validation_readiness' =>
                    $validation->governance_validation_readiness,

                'governance_validation_readiness_score' =>
                    $validation->governance_validation_readiness_score,

                'decision_risk_level' =>
                    $decisionRiskLevel,

                'decision_risk_score' =>
                    $decisionRiskScore,

                'source_condition_evidence_dominant_restriction' =>
                    $dominantRestriction,
            ],

            /*
            |--------------------------------------------------------------------------
            | Findings / Priorities
            |--------------------------------------------------------------------------
            */

            'eligibility_safety_findings' =>
                $eligibilitySafetyFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 66.5 Guardrails
            |--------------------------------------------------------------------------
            */

            'validation_eligibility_safety_guardrails' => [
                'strategic_plan_validation_eligibility_safety_intelligence_enabled' =>
                    true,

                'eligibility_intelligence_is_completed_validation' =>
                    false,

                'eligibility_intelligence_makes_validation_decision' =>
                    false,

                'eligibility_intelligence_records_validation_decision' =>
                    false,

                'eligibility_intelligence_completes_governance_validation' =>
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

                'eligibility_intelligence_activates_strategic_plan' =>
                    false,

                'eligibility_intelligence_changes_validation_status' =>
                    false,

                'eligibility_intelligence_changes_human_decision' =>
                    false,

                'eligibility_intelligence_changes_plan_status' =>
                    false,

                'eligibility_intelligence_changes_action_state' =>
                    false,

                'eligibility_intelligence_changes_priority' =>
                    false,

                'eligibility_intelligence_resolves_conditions' =>
                    false,

                'eligibility_intelligence_resolves_dependencies' =>
                    false,

                'eligibility_intelligence_validates_evidence' =>
                    false,

                'eligibility_score_authorizes_validation' =>
                    false,

                'eligibility_score_authorizes_approval' =>
                    false,

                'safety_score_authorizes_validation' =>
                    false,

                'safety_score_authorizes_approval' =>
                    false,

                'validated_evidence_readiness_authorizes_validation' =>
                    false,

                'evidence_resolution_score_authorizes_validation' =>
                    false,

                'condition_resolution_score_authorizes_validation' =>
                    false,

                'combined_resolution_score_authorizes_validation' =>
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

                'eligibility_intelligence_overrides_final_human_decision' =>
                    false,

                'eligibility_intelligence_overrides_governance_validation' =>
                    false,

                'eligibility_intelligence_overrides_evidence_requirements' =>
                    false,

                'automatic_validation_allowed' =>
                    false,

                'automatic_decision_allowed' =>
                    false,

                'automatic_approval_allowed' =>
                    false,

                'automatic_rejection_allowed' =>
                    false,

                'automatic_activation_allowed' =>
                    false,

                'automatic_condition_resolution_allowed' =>
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

                'governance_validation_authority_reserved_for_authorized_human' =>
                    true,

                'final_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Strategic plan governance validation eligibility and safety intelligence evaluates whether a prepared governance-validation package may progress through authorized human review, what material restrictions remain, and whether completed governance validation is currently eligible. Eligibility, readiness, evidence availability, evidence-resolution scores, condition-resolution scores, combined-resolution scores, risk scores, and safety scores do not constitute governance validation, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, evidence validation, AI modification, execution, deployment, rollback, or clinical action. Final strategic plan decision authority and governance-validation authority remain reserved exclusively for authorized human governance.',
            ],
        ];
    }
}