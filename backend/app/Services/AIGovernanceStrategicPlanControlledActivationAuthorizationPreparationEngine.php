<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;
use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;
use Illuminate\Support\Str;

class AIGovernanceStrategicPlanControlledActivationAuthorizationPreparationEngine
{
    public function prepare(?int $confirmationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Source Step 68 Confirmation
        |--------------------------------------------------------------------------
        */

        $confirmation = $confirmationId
            ? AIGovernanceStrategicPlanGovernanceDecisionConfirmation::find($confirmationId)
            : AIGovernanceStrategicPlanGovernanceDecisionConfirmation::latest('id')->first();

        if (!$confirmation) {
            return [
                'prepared' => false,
                'status' => 'NO_GOVERNANCE_DECISION_CONFIRMATION_AVAILABLE',
                'message' =>
                    'No strategic plan governance decision confirmation record is available for controlled activation authorization preparation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Idempotency
        |--------------------------------------------------------------------------
        */

        $existing =
            AIGovernanceStrategicPlanControlledActivationAuthorization::where(
                'strategic_plan_governance_decision_confirmation_id',
                $confirmation->id
            )->latest('id')->first();

        if ($existing) {
            return [
                'prepared' => true,
                'status' =>
                    'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_ALREADY_PREPARED',

                'message' =>
                    'A controlled activation authorization record has already been prepared for this governance confirmation.',

                'controlled_activation_authorization' =>
                    $this->authorizationSummary($existing),

                'activation_conditions' =>
                    $existing->activation_conditions ?? [],

                'activation_restrictions' =>
                    $existing->activation_restrictions ?? [],

                'validated_evidence' =>
                    $existing->validated_evidence ?? [],

                'activation_findings' =>
                    $existing->activation_findings ?? [],

                'review_context' =>
                    $existing->review_context ?? [],

                'confirmation_context' =>
                    $existing->confirmation_context ?? [],

                'governance_context' =>
                    $existing->governance_context ?? [],

                'source_context' =>
                    $existing->source_context ?? [],

                'activation_authorization_guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 68 Final Validation
        |--------------------------------------------------------------------------
        */

        $step68FinalValidationEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationFinalValidationEngine::class
        );

        $step68 =
            $step68FinalValidationEngine->analyze($confirmation->id);

        /*
        |--------------------------------------------------------------------------
        | Step 68 Supporting Intelligence
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

        /*
        |--------------------------------------------------------------------------
        | Step 68 Context
        |--------------------------------------------------------------------------
        */

        $step68Context =
            $step68['governance_confirmation_activation_context'] ?? [];

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

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation State
        |--------------------------------------------------------------------------
        */

        $sourceConfirmationDecision =
            $confirmation->governance_confirmation_decision;

        $sourceConfirmationDecisionRecorded =
            filled($sourceConfirmationDecision);

        $sourceConfirmationMadeByAuthorizedHuman =
            (bool) $confirmation->confirmation_made_by_authorized_human;

        $sourceConfirmationCompleted =
            (bool) $confirmation->governance_decision_confirmation_completed;

        $sourceControlledActivationStatus =
            $confirmation->controlled_activation_status;

        $sourceControlledActivationAuthorized =
            (bool) $confirmation->controlled_activation_authorized;

        /*
        |--------------------------------------------------------------------------
        | Conditions / Restrictions
        |--------------------------------------------------------------------------
        */

        $blockingConfirmationConditions =
            (int) (
                $step68Context['blocking_confirmation_conditions']
                ?? $conditionSummary['blocking_confirmation_conditions']
                ?? 0
            );

        $blockingActivationConditions =
            (int) (
                $step68Context['blocking_activation_conditions']
                ?? $conditionSummary['blocking_activation_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $step68Context['material_restrictions']
                ?? $restrictionSummary['material_restrictions']
                ?? 0
            );

        $criticalConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary['critical_restrictions']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence
        |--------------------------------------------------------------------------
        */

        $evidenceContext =
            $eligibilitySafety['evidence_context'] ?? [];

        $outstandingEvidenceItems =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

        $blockingActivationEvidenceItems =
            (int) (
                $evidenceContext['blocking_activation_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            $this->score(
                $step68Context['condition_resolution_score']
                ?? $confirmation->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $step68Context['evidence_resolution_score']
                ?? $confirmation->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $step68Context['combined_resolution_score']
                ?? $confirmation->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $step68Context['condition_pressure_score']
                ?? $confirmation->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $step68Context['restriction_pressure_score']
                ?? $confirmation->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $step68Context['combined_condition_restriction_pressure_score']
                ?? $confirmation->combined_condition_restriction_pressure_score
            );

        $sourceConfirmationRiskScore =
            $this->score(
                $step68Context['confirmation_risk_score']
                ?? $confirmation->confirmation_risk_score
            );

        /*
        |--------------------------------------------------------------------------
        | Activation Readiness Score
        |--------------------------------------------------------------------------
        */

        $readinessComponents = [
            'step_68_architecture' =>
                ($step68['step_68_ready_for_closure'] ?? false)
                    ? 20
                    : 0,

            'confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded
                    ? 15
                    : 0,

            'confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman
                    ? 15
                    : 0,

            'confirmation_completed' =>
                $sourceConfirmationCompleted
                    ? 20
                    : 0,

            'no_activation_blockers' =>
                $blockingActivationConditions === 0
                    ? 10
                    : 0,

            'no_material_restrictions' =>
                $materialRestrictions === 0
                    ? 10
                    : 0,

            'no_blocking_activation_evidence' =>
                $blockingActivationEvidenceItems === 0
                    ? 5
                    : 0,

            'source_activation_authorized' =>
                $sourceControlledActivationAuthorized
                    ? 5
                    : 0,
        ];

        $activationReadinessScore =
            round(array_sum($readinessComponents), 2);

        /*
        |--------------------------------------------------------------------------
        | Activation Readiness State
        |--------------------------------------------------------------------------
        */

        if (!$sourceConfirmationDecisionRecorded) {
            $activationReadiness =
                'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION';
        } elseif (!$sourceConfirmationMadeByAuthorizedHuman) {
            $activationReadiness =
                'AWAITING_AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION';
        } elseif (!$sourceConfirmationCompleted) {
            $activationReadiness =
                'AWAITING_COMPLETED_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        } elseif (
            $blockingActivationConditions > 0
            || $materialRestrictions > 0
            || $blockingActivationEvidenceItems > 0
        ) {
            $activationReadiness =
                'CONTROLLED_ACTIVATION_REQUIREMENTS_OUTSTANDING';
        } elseif (!$sourceControlledActivationAuthorized) {
            $activationReadiness =
                'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION';
        } else {
            $activationReadiness =
                'ELIGIBLE_FOR_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Activation Risk
        |--------------------------------------------------------------------------
        */

        $activationRiskScore =
            $sourceConfirmationRiskScore;

        if (!$sourceConfirmationDecisionRecorded) {
            $activationRiskScore += 5;
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $activationRiskScore += 5;
        }

        if (!$sourceConfirmationCompleted) {
            $activationRiskScore += 5;
        }

        if ($blockingActivationConditions > 0) {
            $activationRiskScore +=
                min(10, $blockingActivationConditions * 1.25);
        }

        if ($materialRestrictions > 0) {
            $activationRiskScore +=
                min(10, $materialRestrictions);
        }

        if ($blockingActivationEvidenceItems > 0) {
            $activationRiskScore +=
                min(5, $blockingActivationEvidenceItems * 2.5);
        }

        $activationRiskScore =
            round(
                max(
                    0,
                    min(
                        100,
                        $activationRiskScore
                    )
                ),
                2
            );

        if ($activationRiskScore >= 90) {
            $activationRiskLevel =
                'CRITICAL_CONTROLLED_ACTIVATION_RISK';
        } elseif ($activationRiskScore >= 75) {
            $activationRiskLevel =
                'HIGH_CONTROLLED_ACTIVATION_RISK';
        } elseif ($activationRiskScore >= 50) {
            $activationRiskLevel =
                'MODERATE_CONTROLLED_ACTIVATION_RISK';
        } else {
            $activationRiskLevel =
                'CONTROLLED_ACTIVATION_RISK';
        }

        /*
        |--------------------------------------------------------------------------
        | Activation Conditions
        |--------------------------------------------------------------------------
        */

        $activationConditions = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $activationConditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION_REQUIRED',

                'condition_type' =>
                    'GOVERNANCE_CONFIRMATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'An explicitly authorized human governance confirmation decision must be recorded before controlled activation authorization can progress.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $activationConditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION_REQUIRED',

                'condition_type' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Governance confirmation must remain explicitly attributable to an authorized human governance confirmer before activation authorization.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if (!$sourceConfirmationCompleted) {
            $activationConditions[] = [
                'condition_code' =>
                    'COMPLETED_GOVERNANCE_CONFIRMATION_REQUIRED',

                'condition_type' =>
                    'CONFIRMATION_COMPLETION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Governance decision confirmation must be completed through explicitly authorized human governance before controlled activation authorization.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if ($blockingActivationConditions > 0) {
            $activationConditions[] = [
                'condition_code' =>
                    'SOURCE_ACTIVATION_BLOCKING_CONDITIONS_REMAIN',

                'condition_type' =>
                    'ACTIVATION_CONDITION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$blockingActivationConditions} source governance-confirmation condition(s) currently block controlled activation.",

                'value' =>
                    $blockingActivationConditions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if ($materialRestrictions > 0) {
            $activationConditions[] = [
                'condition_code' =>
                    'MATERIAL_SOURCE_ACTIVATION_RESTRICTIONS_REMAIN',

                'condition_type' =>
                    'ACTIVATION_RESTRICTION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$materialRestrictions} material source governance-confirmation restriction(s) remain active.",

                'value' =>
                    $materialRestrictions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if ($blockingActivationEvidenceItems > 0) {
            $activationConditions[] = [
                'condition_code' =>
                    'BLOCKING_ACTIVATION_EVIDENCE_REMAINS',

                'condition_type' =>
                    'EVIDENCE',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$blockingActivationEvidenceItems} evidence requirement(s) currently block controlled activation.",

                'value' =>
                    $blockingActivationEvidenceItems,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        if ($activationRiskScore >= 75) {
            $activationConditions[] = [
                'condition_code' =>
                    'ELEVATED_CONTROLLED_ACTIVATION_RISK_REQUIRES_HUMAN_REVIEW',

                'condition_type' =>
                    'RISK',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "Controlled activation risk remains {$activationRiskLevel} with score {$activationRiskScore} and requires explicit authorized human governance review.",

                'value' =>
                    $activationRiskScore,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_activation_authorization' =>
                    true,

                'blocks_activation_execution' =>
                    true,
            ];
        }

        $activationConditions[] = [
            'condition_code' =>
                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_REQUIRED',

            'condition_type' =>
                'HUMAN_ACTIVATION_AUTHORIZATION',

            'priority_level' =>
                'HIGH',

            'condition_status' =>
                'OPEN',

            'condition' =>
                'An explicitly authorized human controlled-activation authorization is required before activation execution can be considered.',

            'requires_authorized_human_resolution' =>
                true,

            'automatic_resolution_allowed' =>
                false,

            'blocks_activation_authorization' =>
                false,

            'blocks_activation_execution' =>
                true,
        ];

        /*
        |--------------------------------------------------------------------------
        | Activation Restrictions
        |--------------------------------------------------------------------------
        */

        $activationRestrictions = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'GOVERNANCE_CONFIRMATION_DECISION_NOT_RECORDED',

                'restriction_type' =>
                    'GOVERNANCE_CONFIRMATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation authorization remains restricted because an authorized-human governance confirmation decision has not been recorded.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'GOVERNANCE_CONFIRMATION_ATTRIBUTION_INCOMPLETE',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation authorization remains restricted until governance confirmation attribution to an authorized human is complete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceConfirmationCompleted) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'GOVERNANCE_CONFIRMATION_INCOMPLETE',

                'restriction_type' =>
                    'CONFIRMATION_COMPLETION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation authorization remains restricted while governance decision confirmation is incomplete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($blockingActivationConditions > 0) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'SOURCE_ACTIVATION_BLOCKING_CONDITIONS',

                'restriction_type' =>
                    'ACTIVATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingActivationConditions,

                'message' =>
                    "{$blockingActivationConditions} source confirmation condition(s) currently block controlled activation.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($materialRestrictions > 0) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'MATERIAL_SOURCE_ACTIVATION_RESTRICTIONS',

                'restriction_type' =>
                    'ACTIVATION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material source governance-confirmation restriction(s) remain active.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($activationRiskScore >= 75) {
            $activationRestrictions[] = [
                'restriction_code' =>
                    'ELEVATED_CONTROLLED_ACTIVATION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    $activationRiskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'value' =>
                    $activationRiskScore,

                'message' =>
                    "Controlled activation authorization remains subject to elevated authorized-human oversight because activation risk is {$activationRiskLevel} with score {$activationRiskScore}.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        $activationRestrictions[] = [
            'restriction_code' =>
                'AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_NOT_COMPLETED',

            'restriction_type' =>
                'HUMAN_ACTIVATION_AUTHORIZATION',

            'severity' =>
                'HIGH',

            'message' =>
                'Controlled activation execution remains prohibited until an explicitly authorized human activation authorization is completed.',

            'requires_authorized_human_governance' =>
                true,

            'automatic_restriction_removal_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Validated Evidence
        |--------------------------------------------------------------------------
        */

        $validatedEvidence =
            $confirmation->validated_evidence ?? [];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $activationFindings = [
            "Controlled activation authorization preparation is based on Step 68 governance confirmation record {$confirmation->id}.",

            'Step 68 final validation status is '
                .($step68['validation_status'] ?? 'UNKNOWN').'.',

            'Step 68 ready for closure is '
                .(($step68['step_68_ready_for_closure'] ?? false)
                    ? 'YES'
                    : 'NO').'.',

            'Source governance confirmation decision recorded is '
                .($sourceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source governance confirmation made by authorized human is '
                .($sourceConfirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance decision confirmation completed is '
                .($sourceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Source controlled activation authorized is '
                .($sourceControlledActivationAuthorized ? 'YES' : 'NO').'.',

            "{$blockingConfirmationConditions} source condition(s) currently block governance confirmation.",

            "{$blockingActivationConditions} source condition(s) currently block controlled activation.",

            "{$materialRestrictions} material source governance restriction(s) remain active.",

            "{$outstandingEvidenceItems} source evidence requirement(s) remain outstanding.",

            "{$blockingActivationEvidenceItems} source evidence requirement(s) currently block controlled activation.",

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

            'Current activation readiness is '
                .$activationReadiness
                .' with score '
                .$activationReadinessScore.'.',

            'Current controlled activation risk is '
                .$activationRiskLevel
                .' with score '
                .$activationRiskScore.'.',

            count($activationConditions)
                .' Step 69 activation authorization condition(s) are represented.',

            count($activationRestrictions)
                .' Step 69 activation authorization restriction(s) are represented.',

            'Step 69.2 preparation does not authorize controlled activation.',

            'Step 69.2 preparation does not authorize activation execution.',

            'Step 69.2 preparation does not activate the strategic plan.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Review Context
        |--------------------------------------------------------------------------
        */

        $reviewContext = [
            'review_state' =>
                'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION',

            'step_68_validation_status' =>
                $step68['validation_status'] ?? null,

            'step_68_ready_for_closure' =>
                (bool) ($step68['step_68_ready_for_closure'] ?? false),

            'governance_integrity_intact' =>
                (bool) (
                    $safetyState['governance_integrity_intact']
                    ?? false
                ),

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded,

            'source_confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman,

            'source_governance_decision_confirmation_completed' =>
                $sourceConfirmationCompleted,

            'source_controlled_activation_status' =>
                $sourceControlledActivationStatus,

            'source_controlled_activation_authorized' =>
                $sourceControlledActivationAuthorized,

            'blocking_confirmation_conditions' =>
                $blockingConfirmationConditions,

            'blocking_activation_conditions' =>
                $blockingActivationConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_conditions' =>
                $criticalConditions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'activation_readiness' =>
                $activationReadiness,

            'activation_readiness_score' =>
                $activationReadinessScore,

            'activation_risk_level' =>
                $activationRiskLevel,

            'activation_risk_score' =>
                $activationRiskScore,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'human_review_required' =>
                true,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Context
        |--------------------------------------------------------------------------
        */

        $confirmationContext = [
            'confirmation_status' =>
                $confirmation->confirmation_status,

            'confirmation_mode' =>
                $confirmation->confirmation_mode,

            'governance_confirmation_decision' =>
                $confirmation->governance_confirmation_decision,

            'confirmation_outcome' =>
                $confirmation->confirmation_outcome,

            'confirmation_outcome_status' =>
                $confirmation->confirmation_outcome_status,

            'confirmation_readiness' =>
                $confirmation->confirmation_readiness,

            'confirmation_readiness_score' =>
                (float) $confirmation->confirmation_readiness_score,

            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                (float) $confirmation->confirmation_risk_score,

            'confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $sourceConfirmationCompleted,

            'controlled_activation_status' =>
                $sourceControlledActivationStatus,

            'controlled_activation_authorized' =>
                $sourceControlledActivationAuthorized,

            'controlled_activation_authorization_code' =>
                $confirmation->controlled_activation_authorization_code,

            'activation_authorized_by' =>
                $confirmation->activation_authorized_by,

            'activation_authorizer_role' =>
                $confirmation->activation_authorizer_role,

            'activation_authorized_at' =>
                optional(
                    $confirmation->activation_authorized_at
                )?->toISOString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Context
        |--------------------------------------------------------------------------
        */

        $governanceContext = [
            'step_68_validation_status' =>
                $step68['validation_status'] ?? null,

            'step_68_ready_for_closure' =>
                (bool) ($step68['step_68_ready_for_closure'] ?? false),

            'governance_integrity_intact' =>
                (bool) (
                    $safetyState['governance_integrity_intact']
                    ?? false
                ),

            'confirmation_eligibility_score' =>
                $eligibilityState['confirmation_eligibility_score']
                ?? 0,

            'confirmation_safety_status' =>
                $safetyState['confirmation_safety_status']
                ?? null,

            'confirmation_safety_score' =>
                $safetyState['confirmation_safety_score']
                ?? 0,

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'total_recommendations' =>
                $recommendationState['total_recommendations']
                ?? 0,

            'top_recommendation_code' =>
                $recommendationState['top_recommendation_code']
                ?? null,

            'executive_governance_confirmation_status' =>
                $executiveSummary['executive_governance_confirmation_status']
                ?? null,

            'executive_readiness' =>
                $executiveSummary['executive_readiness']
                ?? null,

            'executive_confidence' =>
                $executiveSummary['executive_confidence']
                ?? null,

            'executive_governance_confirmation_score' =>
                $executiveSummary['executive_governance_confirmation_score']
                ?? 0,

            'activation_authorization_readiness' =>
                $activationReadiness,

            'activation_authorization_readiness_score' =>
                $activationReadinessScore,

            'activation_authorization_risk_level' =>
                $activationRiskLevel,

            'activation_authorization_risk_score' =>
                $activationRiskScore,

            'activation_authorization_authority' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

            'activation_execution_authority' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
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

            'source_confirmation_decision' =>
                $sourceConfirmationDecision,

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded,

            'source_confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman,

            'source_governance_decision_confirmation_completed' =>
                $sourceConfirmationCompleted,

            'source_controlled_activation_status' =>
                $sourceControlledActivationStatus,

            'source_controlled_activation_authorized' =>
                $sourceControlledActivationAuthorized,

            'source_confirmation_created_at' =>
                optional(
                    $confirmation->created_at
                )?->toISOString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Step 69 Authorization Record
        |--------------------------------------------------------------------------
        */

        $authorization =
            AIGovernanceStrategicPlanControlledActivationAuthorization::create([
                'strategic_plan_governance_decision_confirmation_id' =>
                    $confirmation->id,

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

                'activation_authorization_code' =>
                    'GOV-STRATEGIC-PLAN-ACTIVATION-AUTH-'
                    .$confirmation->id
                    .'-'
                    .now()->format('YmdHis'),

                'activation_authorization_status' =>
                    'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION',

                'activation_authorization_mode' =>
                    'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION',

                'source_confirmation_decision' =>
                    $sourceConfirmationDecision,

                'source_confirmation_decision_recorded' =>
                    $sourceConfirmationDecisionRecorded,

                'source_confirmation_made_by_authorized_human' =>
                    $sourceConfirmationMadeByAuthorizedHuman,

                'source_governance_decision_confirmation_completed' =>
                    $sourceConfirmationCompleted,

                'source_controlled_activation_status' =>
                    $sourceControlledActivationStatus,

                'source_controlled_activation_authorized' =>
                    $sourceControlledActivationAuthorized,

                'controlled_activation_authorization_decision' =>
                    null,

                'activation_authorization_rationale' =>
                    'Controlled activation authorization package prepared from Step 68 governance decision confirmation intelligence for explicitly authorized human governance review. Preparation does not constitute controlled activation authorization or activation execution authorization.',

                'activation_authorization_notes' =>
                    null,

                'activation_authorization_outcome' =>
                    null,

                'activation_authorization_outcome_status' =>
                    'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION',

                'activation_readiness' =>
                    $activationReadiness,

                'activation_readiness_score' =>
                    $activationReadinessScore,

                'activation_risk_level' =>
                    $activationRiskLevel,

                'activation_risk_score' =>
                    $activationRiskScore,

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

                'activation_conditions' =>
                    $activationConditions,

                'validated_evidence' =>
                    $validatedEvidence,

                'activation_findings' =>
                    $activationFindings,

                'activation_restrictions' =>
                    $activationRestrictions,

                'review_context' =>
                    $reviewContext,

                'confirmation_context' =>
                    $confirmationContext,

                'governance_context' =>
                    $governanceContext,

                'source_context' =>
                    $sourceContext,

                /*
                |--------------------------------------------------------------------------
                | No Human Authorization Yet
                |--------------------------------------------------------------------------
                */

                'authorized_by' =>
                    null,

                'authorizer_role' =>
                    null,

                'authorized_at' =>
                    null,

                'activation_authorization_made_by_authorized_human' =>
                    false,

                'controlled_activation_authorization_completed' =>
                    false,

                /*
                |--------------------------------------------------------------------------
                | No Execution Authorization Yet
                |--------------------------------------------------------------------------
                */

                'controlled_activation_execution_status' =>
                    'NOT_AUTHORIZED_FOR_ACTIVATION_EXECUTION',

                'controlled_activation_execution_authorized' =>
                    false,

                'activation_execution_authorization_code' =>
                    null,

                'execution_authorized_by' =>
                    null,

                'execution_authorizer_role' =>
                    null,

                'execution_authorized_at' =>
                    null,

                /*
                |--------------------------------------------------------------------------
                | Automatic Authority Disabled
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Mandatory Human Governance
                |--------------------------------------------------------------------------
                */

                'human_review_required' =>
                    true,

                'governance_confirmation_required' =>
                    true,

                'authorized_human_activation_authorization_required' =>
                    true,

                'authorized_human_activation_execution_required' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'prepared' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_PREPARED',

            'message' =>
                'AI governance strategic plan controlled activation authorization record prepared successfully for explicitly authorized human activation-authorization review.',

            'controlled_activation_authorization' =>
                $this->authorizationSummary($authorization),

            'activation_context' => [
                'strategic_plan_governance_decision_confirmation_id' =>
                    $confirmation->id,

                'confirmation_code' =>
                    $confirmation->confirmation_code,

                'source_confirmation_decision_recorded' =>
                    $sourceConfirmationDecisionRecorded,

                'source_confirmation_made_by_authorized_human' =>
                    $sourceConfirmationMadeByAuthorizedHuman,

                'source_governance_decision_confirmation_completed' =>
                    $sourceConfirmationCompleted,

                'source_controlled_activation_authorized' =>
                    $sourceControlledActivationAuthorized,

                'activation_readiness' =>
                    $activationReadiness,

                'activation_readiness_score' =>
                    $activationReadinessScore,

                'activation_risk_level' =>
                    $activationRiskLevel,

                'activation_risk_score' =>
                    $activationRiskScore,

                'blocking_confirmation_conditions' =>
                    $blockingConfirmationConditions,

                'blocking_activation_conditions' =>
                    $blockingActivationConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'outstanding_evidence_items' =>
                    $outstandingEvidenceItems,

                'blocking_activation_evidence_items' =>
                    $blockingActivationEvidenceItems,

                'activation_condition_count' =>
                    count($activationConditions),

                'activation_restriction_count' =>
                    count($activationRestrictions),

                'controlled_activation_authorization_completed' =>
                    false,

                'controlled_activation_execution_authorized' =>
                    false,
            ],

            'activation_conditions' =>
                $activationConditions,

            'activation_restrictions' =>
                $activationRestrictions,

            'validated_evidence' =>
                $validatedEvidence,

            'activation_findings' =>
                $activationFindings,

            'review_context' =>
                $reviewContext,

            'confirmation_context' =>
                $confirmationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'activation_authorization_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Summary
    |--------------------------------------------------------------------------
    */

    private function authorizationSummary(
        AIGovernanceStrategicPlanControlledActivationAuthorization $authorization
    ): array {
        return [
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

            'activation_authorization_status' =>
                $authorization->activation_authorization_status,

            'activation_authorization_mode' =>
                $authorization->activation_authorization_mode,

            'source_confirmation_decision' =>
                $authorization->source_confirmation_decision,

            'source_confirmation_decision_recorded' =>
                (bool) $authorization->source_confirmation_decision_recorded,

            'source_confirmation_made_by_authorized_human' =>
                (bool) $authorization->source_confirmation_made_by_authorized_human,

            'source_governance_decision_confirmation_completed' =>
                (bool) $authorization->source_governance_decision_confirmation_completed,

            'source_controlled_activation_status' =>
                $authorization->source_controlled_activation_status,

            'source_controlled_activation_authorized' =>
                (bool) $authorization->source_controlled_activation_authorized,

            'controlled_activation_authorization_decision' =>
                $authorization->controlled_activation_authorization_decision,

            'activation_authorization_outcome' =>
                $authorization->activation_authorization_outcome,

            'activation_authorization_outcome_status' =>
                $authorization->activation_authorization_outcome_status,

            'activation_readiness' =>
                $authorization->activation_readiness,

            'activation_readiness_score' =>
                (float) $authorization->activation_readiness_score,

            'activation_risk_level' =>
                $authorization->activation_risk_level,

            'activation_risk_score' =>
                (float) $authorization->activation_risk_score,

            'condition_resolution_score' =>
                (float) $authorization->condition_resolution_score,

            'evidence_resolution_score' =>
                (float) $authorization->evidence_resolution_score,

            'combined_resolution_score' =>
                (float) $authorization->combined_resolution_score,

            'condition_pressure_score' =>
                (float) $authorization->condition_pressure_score,

            'restriction_pressure_score' =>
                (float) $authorization->restriction_pressure_score,

            'combined_condition_restriction_pressure_score' =>
                (float) $authorization->combined_condition_restriction_pressure_score,

            'activation_authorization_made_by_authorized_human' =>
                (bool) $authorization->activation_authorization_made_by_authorized_human,

            'controlled_activation_authorization_completed' =>
                (bool) $authorization->controlled_activation_authorization_completed,

            'controlled_activation_execution_status' =>
                $authorization->controlled_activation_execution_status,

            'controlled_activation_execution_authorized' =>
                (bool) $authorization->controlled_activation_execution_authorized,
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
    | Step 69.2 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_preparation_enabled' =>
                true,

            'prepared_record_is_activation_authorization' =>
                false,

            'prepared_record_is_activation_execution_authorization' =>
                false,

            'ai_makes_activation_authorization_decision' =>
                false,

            'ai_records_activation_authorization_decision' =>
                false,

            'ai_completes_activation_authorization' =>
                false,

            'ai_authorizes_activation_execution' =>
                false,

            'ai_activates_strategic_plan' =>
                false,

            'ai_changes_governance_confirmation' =>
                false,

            'ai_changes_final_governance_decision' =>
                false,

            'ai_approves_strategic_plan' =>
                false,

            'ai_rejects_strategic_plan' =>
                false,

            'ai_conditionally_approves_strategic_plan' =>
                false,

            'ai_defers_strategic_plan' =>
                false,

            'ai_accepts_governance_risk' =>
                false,

            'activation_preparation_changes_source_confirmation' =>
                false,

            'activation_preparation_changes_source_activation_status' =>
                false,

            'activation_preparation_changes_plan_status' =>
                false,

            'activation_preparation_changes_action_state' =>
                false,

            'activation_preparation_resolves_conditions' =>
                false,

            'activation_preparation_waives_conditions' =>
                false,

            'activation_preparation_removes_restrictions' =>
                false,

            'activation_preparation_validates_evidence' =>
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
                'Step 69.2 prepares a structured strategic plan controlled activation authorization record from Step 68 governance decision confirmation intelligence for explicitly authorized human governance review. Preparation does not make or record an activation authorization decision, authorize activation execution, activate the strategic plan, modify governance confirmation, alter the final governance decision, approve, reject, conditionally approve, defer, accept governance risk, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Controlled activation authorization authority and activation execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}