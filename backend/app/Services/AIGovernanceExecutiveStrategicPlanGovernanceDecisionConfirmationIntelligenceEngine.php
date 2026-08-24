<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;

class AIGovernanceExecutiveStrategicPlanGovernanceDecisionConfirmationIntelligenceEngine
{
    public function analyze(?int $confirmationId = null): array
    {
        $confirmation = $confirmationId
            ? AIGovernanceStrategicPlanGovernanceDecisionConfirmation::find($confirmationId)
            : AIGovernanceStrategicPlanGovernanceDecisionConfirmation::latest('id')->first();

        if (!$confirmation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_GOVERNANCE_DECISION_CONFIRMATION_AVAILABLE',
                'message' => 'No strategic plan governance decision confirmation record is available for executive intelligence.',
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

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($confirmation->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($confirmation->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($confirmation->id);

        $recommendation =
            $recommendationEngine->analyze($confirmation->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $confirmationState =
            $state['confirmation_state'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

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

        $topRecommendation =
            $recommendation['top_recommendation'] ?? [];

        $dominantEligibilityRestriction =
            $eligibilitySafety['dominant_eligibility_restriction']
            ?? $recommendation['dominant_eligibility_restriction']
            ?? [];

        $confirmationAttributionContext =
            $eligibilitySafety['confirmation_attribution_context']
            ?? $state['confirmation_attribution_context']
            ?? [];

        $controlledActivationContext =
            $eligibilitySafety['controlled_activation_context']
            ?? [];

        $resolutionContext =
            $eligibilitySafety['resolution_context']
            ?? [];

        $reviewContext =
            $eligibilitySafety['review_context']
            ?? $state['review_context']
            ?? [];

        $validationContext =
            $eligibilitySafety['validation_context']
            ?? $state['validation_context']
            ?? [];

        $governanceContext =
            $eligibilitySafety['governance_context']
            ?? $state['governance_context']
            ?? [];

        $sourceContext =
            $eligibilitySafety['source_context']
            ?? $state['source_context']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core Governance State
        |--------------------------------------------------------------------------
        */

        $sourceFinalGovernanceDecisionRecorded =
            (bool) (
                $eligibilityState['source_final_governance_decision_recorded']
                ?? $confirmation->source_final_governance_decision_recorded
            );

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) (
                $eligibilityState['source_decision_made_by_authorized_human']
                ?? $confirmation->source_decision_made_by_authorized_human
            );

        $sourceGovernanceValidationCompleted =
            (bool) (
                $eligibilityState['source_governance_validation_completed']
                ?? $confirmation->source_governance_validation_completed
            );

        $confirmationDecisionRecorded =
            (bool) (
                $eligibilityState['governance_confirmation_decision_recorded']
                ?? filled($confirmation->governance_confirmation_decision)
            );

        $confirmationMadeByAuthorizedHuman =
            (bool) (
                $eligibilityState['confirmation_made_by_authorized_human']
                ?? $confirmation->confirmation_made_by_authorized_human
            );

        $confirmationCompleted =
            (bool) (
                $eligibilityState['governance_decision_confirmation_completed']
                ?? $confirmation->governance_decision_confirmation_completed
            );

        $controlledActivationAuthorized =
            (bool) (
                $eligibilityState['controlled_activation_authorized']
                ?? $confirmation->controlled_activation_authorized
            );

        $confirmationAttributionComplete =
            ($confirmationAttributionContext['confirmation_attribution_complete'] ?? false)
            === true;

        $activationAttributionComplete =
            ($controlledActivationContext['activation_attribution_complete'] ?? false)
            === true;

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) (
                $conditionSummary['total_conditions']
                ?? $conditionRestrictionState['total_conditions']
                ?? 0
            );

        $openConditions =
            (int) (
                $conditionSummary['open_conditions']
                ?? $conditionRestrictionState['open_conditions']
                ?? 0
            );

        $blockingConfirmationConditions =
            (int) (
                $eligibilitySafety['condition_context']['blocking_confirmation_conditions']
                ?? $conditionSummary['blocking_confirmation_conditions']
                ?? $conditionRestrictionState['blocking_confirmation_conditions']
                ?? 0
            );

        $blockingActivationConditions =
            (int) (
                $eligibilitySafety['condition_context']['blocking_activation_conditions']
                ?? $conditionSummary['blocking_activation_conditions']
                ?? $conditionRestrictionState['blocking_activation_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionSummary['constraining_conditions']
                ?? $conditionRestrictionState['constraining_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? $conditionRestrictionState['critical_open_conditions']
                ?? 0
            );

        $totalRestrictions =
            (int) (
                $restrictionSummary['total_restrictions']
                ?? $conditionRestrictionState['total_restrictions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $restrictionSummary['material_restrictions']
                ?? $conditionRestrictionState['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary['critical_restrictions']
                ?? $conditionRestrictionState['critical_restrictions']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence State
        |--------------------------------------------------------------------------
        */

        $evidenceContext =
            $eligibilitySafety['evidence_context'] ?? [];

        $outstandingEvidenceItems =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

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
                $resolutionContext['confirmation_readiness_score']
                ?? $confirmationState['confirmation_readiness_score']
                ?? $confirmation->confirmation_readiness_score
            );

        $confirmationRiskScore =
            $this->score(
                $resolutionContext['confirmation_risk_score']
                ?? $confirmationState['confirmation_risk_score']
                ?? $confirmation->confirmation_risk_score
            );

        $eligibilityScore =
            $this->score(
                $eligibilityState['confirmation_eligibility_score']
                ?? 0
            );

        $safetyScore =
            $this->score(
                $safetyState['confirmation_safety_score']
                ?? 0
            );

        $conditionResolutionScore =
            $this->score(
                $resolutionContext['condition_resolution_score']
                ?? $confirmation->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $resolutionContext['evidence_resolution_score']
                ?? $confirmation->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $resolutionContext['combined_resolution_score']
                ?? $confirmation->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $resolutionContext['condition_pressure_score']
                ?? $confirmation->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $resolutionContext['restriction_pressure_score']
                ?? $confirmation->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $resolutionContext['combined_condition_restriction_pressure_score']
                ?? $confirmation->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Values
        |--------------------------------------------------------------------------
        */

        $confirmationCompletionEligibility =
            $eligibilityState['governance_confirmation_completion_eligibility']
            ?? 'UNKNOWN';

        $unrestrictedConfirmationEligibility =
            $eligibilityState['unrestricted_confirmation_eligibility']
            ?? 'UNKNOWN';

        $controlledActivationConsiderationEligibility =
            $eligibilityState['controlled_activation_consideration_eligibility']
            ?? 'UNKNOWN';

        $confirmationProgressionReadiness =
            $eligibilityState['confirmation_progression_readiness']
            ?? $resolutionContext['confirmation_progression_readiness']
            ?? 'UNKNOWN';

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
            ?? 'UNKNOWN';

        $confirmationSafetyLevel =
            $safetyState['confirmation_safety_level']
            ?? 'UNKNOWN';

        /*
        |--------------------------------------------------------------------------
        | Recommendation State
        |--------------------------------------------------------------------------
        */

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
            ?? $topRecommendation['recommendation_code']
            ?? null;

        $topRecommendedPath =
            $recommendationState['top_recommended_path']
            ?? $topRecommendation['recommended_path']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && !$confirmation->automatic_confirmation_allowed
            && !$confirmation->automatic_activation_allowed
            && !$confirmation->automatic_execution_allowed
            && (bool) $confirmation->authorized_human_confirmation_required
            && (bool) $confirmation->authorized_human_activation_required;

        /*
        |--------------------------------------------------------------------------
        | Executive Score Components
        |--------------------------------------------------------------------------
        */

        $scoreComponents = [
            'confirmation_readiness_component' =>
                round($confirmationReadinessScore * 0.15, 2),

            'eligibility_component' =>
                round($eligibilityScore * 0.20, 2),

            'safety_component' =>
                round($safetyScore * 0.20, 2),

            'condition_resolution_component' =>
                round($conditionResolutionScore * 0.10, 2),

            'evidence_resolution_component' =>
                round($evidenceResolutionScore * 0.05, 2),

            'combined_resolution_component' =>
                round($combinedResolutionScore * 0.10, 2),

            'risk_control_component' =>
                round((100 - $confirmationRiskScore) * 0.10, 2),

            'pressure_control_component' =>
                round((100 - $combinedPressureScore) * 0.10, 2),
        ];

        $executiveScore =
            round(
                max(
                    0,
                    min(
                        100,
                        array_sum($scoreComponents)
                    )
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Readiness
        |--------------------------------------------------------------------------
        */

        $executiveReadiness =
            $this->determineExecutiveReadiness(
                $executiveScore,
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $confirmationCompleted,
                $blockingConfirmationConditions,
                $materialRestrictions
            );

        $executiveConfidence =
            $this->determineExecutiveConfidence(
                $executiveScore,
                $governanceIntegrityIntact,
                $criticalOpenConditions,
                $criticalRestrictions
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Status
        |--------------------------------------------------------------------------
        */

        $executiveStatus =
            $this->determineExecutiveStatus(
                $criticalOpenConditions,
                $criticalRestrictions,
                $confirmationRiskScore,
                $blockingConfirmationConditions,
                $materialRestrictions,
                $confirmationCompleted,
                $controlledActivationAuthorized
            );

        /*
        |--------------------------------------------------------------------------
        | Management Escalation
        |--------------------------------------------------------------------------
        */

        $immediateEscalationRequired =
            ($safetyState['immediate_human_intervention_required'] ?? false)
            === true
            || $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $criticalOutstandingEvidenceItems > 0
            || $confirmationRiskScore >= 95
            || (
                $controlledActivationAuthorized
                && !$activationAttributionComplete
            );

        $managementEscalationRecommended =
            $immediateEscalationRequired
            || ($safetyState['human_management_attention_required'] ?? false)
            || $criticalRecommendations > 0
            || $highRecommendations > 0
            || $blockingConfirmationConditions > 0
            || $materialRestrictions > 0
            || $confirmationRiskScore >= 70
            || !$confirmationCompleted
            || !$controlledActivationAuthorized;

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Items
        |--------------------------------------------------------------------------
        */

        $executiveAttentionItems = [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_FINAL_GOVERNANCE_DECISION_NOT_RECORDED',

                'category' =>
                    'FINAL_GOVERNANCE_DECISION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'An explicitly authorized human final governance decision has not yet been recorded.',
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_FINAL_DECISION_AUTHORIZATION_INCOMPLETE',

                'category' =>
                    'AUTHORIZATION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'Source final governance decision attribution to an explicitly authorized human remains incomplete.',
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_INCOMPLETE',

                'category' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Required source governance validation has not yet been completed.',
            ];
        }

        if ($blockingConfirmationConditions > 0) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'BLOCKING_CONFIRMATION_CONDITIONS_ACTIVE',

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

        if ($blockingActivationConditions > 0) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'BLOCKING_ACTIVATION_CONDITIONS_ACTIVE',

                'category' =>
                    'CONTROLLED_ACTIVATION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingActivationConditions,

                'message' =>
                    "{$blockingActivationConditions} condition(s) currently block controlled activation.",
            ];
        }

        if ($materialRestrictions > 0) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'MATERIAL_CONFIRMATION_RESTRICTIONS_ACTIVE',

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

        if ($confirmationRiskScore >= 70) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'ELEVATED_CONFIRMATION_RISK',

                'category' =>
                    'RISK',

                'severity' =>
                    $confirmationRiskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'value' =>
                    $confirmationRiskScore,

                'message' =>
                    'Governance confirmation risk remains '
                    .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                    ." with score {$confirmationRiskScore}.",
            ];
        }

        if (!$confirmationAttributionComplete) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'CONFIRMATION_ATTRIBUTION_INCOMPLETE',

                'category' =>
                    'CONFIRMATION_AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Authorized-human governance confirmation attribution remains incomplete.',
            ];
        }

        if (!$confirmationCompleted) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'GOVERNANCE_CONFIRMATION_NOT_COMPLETED',

                'category' =>
                    'HUMAN_CONFIRMATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance decision confirmation has not yet been completed by authorized human governance.',
            ];
        }

        if ($controlledActivationProgressionBlocked) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'CONTROLLED_ACTIVATION_PROGRESSION_BLOCKED',

                'category' =>
                    'CONTROLLED_ACTIVATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation progression remains blocked under current governance conditions.',
            ];
        }

        if (!$controlledActivationAuthorized) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'CONTROLLED_ACTIVATION_NOT_AUTHORIZED',

                'category' =>
                    'ACTIVATION_AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation has not been separately authorized by explicitly authorized human governance.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Priorities
        |--------------------------------------------------------------------------
        */

        $executivePriorities =
            $recommendation['management_priorities'] ?? [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            array_unshift(
                $executivePriorities,
                'Record an explicitly authorized human final strategic plan governance decision before governance confirmation progression.'
            );
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $executivePriorities[] =
                'Ensure final governance decision attribution remains explicitly tied to an authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $executivePriorities[] =
                'Complete required source governance validation through explicitly authorized human governance.';
        }

        if (!$confirmationCompleted) {
            $executivePriorities[] =
                'Complete governance decision confirmation only through explicitly authorized human governance after all required prerequisites are satisfied.';
        }

        if (!$controlledActivationAuthorized) {
            $executivePriorities[] =
                'Maintain controlled activation as unauthorized until separate explicitly authorized human activation authorization is recorded.';
        }

        $executivePriorities[] =
            'Keep executive confirmation intelligence separate from final governance decision authority, governance-confirmation authority, controlled-activation authority, and execution authority.';

        $executivePriorities[] =
            'Do not interpret executive readiness, confidence, risk, eligibility, safety, resolution, pressure, recommendation, or management-attention indicators as authorization to confirm or activate the strategic plan.';

        $executivePriorities =
            array_values(
                array_unique($executivePriorities)
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Findings
        |--------------------------------------------------------------------------
        */

        $executiveFindings = [
            "Executive governance decision confirmation intelligence is based on confirmation record {$confirmation->id}.",

            'Current executive governance-confirmation status is '
                .$executiveStatus.'.',

            'Current executive governance-confirmation readiness is '
                .$executiveReadiness
                .' with score '
                .$executiveScore.'.',

            'Current executive governance-confirmation confidence is '
                .$executiveConfidence.'.',

            'Current confirmation progression readiness is '
                .$confirmationProgressionReadiness.'.',

            'Current confirmation completion eligibility is '
                .$confirmationCompletionEligibility.'.',

            'Current unrestricted confirmation eligibility is '
                .$unrestrictedConfirmationEligibility.'.',

            'Current controlled-activation consideration eligibility is '
                .$controlledActivationConsiderationEligibility.'.',

            'Current confirmation safety status is '
                .$confirmationSafetyStatus.'.',

            'Current confirmation safety level is '
                .$confirmationSafetyLevel
                .' with score '
                .$safetyScore.'.',

            "{$totalConditions} governance-confirmation condition(s) are represented.",

            "{$openConditions} governance-confirmation condition(s) remain open.",

            "{$blockingConfirmationConditions} governance-confirmation condition(s) currently block completed confirmation.",

            "{$blockingActivationConditions} governance-confirmation condition(s) currently block controlled activation.",

            "{$constrainingConditions} governance-confirmation condition(s) constrain progression.",

            "{$criticalOpenConditions} critical governance-confirmation condition(s) remain open.",

            "{$totalRestrictions} governance-confirmation restriction(s) are represented.",

            "{$materialRestrictions} material governance-confirmation restriction(s) remain active.",

            "{$criticalRestrictions} critical governance-confirmation restriction(s) remain active.",

            "{$outstandingEvidenceItems} governance-confirmation evidence requirement(s) remain outstanding.",

            "{$blockingConfirmationEvidenceItems} governance-confirmation evidence requirement(s) currently block completed confirmation.",

            "{$blockingActivationEvidenceItems} governance-confirmation evidence requirement(s) currently block controlled activation.",

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedPressureScore.'.',

            'Current governance-confirmation risk is '
                .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                .' with score '
                .$confirmationRiskScore.'.',

            "{$totalRecommendations} governance-confirmation recommendation(s) are represented.",

            "{$criticalRecommendations} critical recommendation(s) require attention.",

            "{$highRecommendations} high-priority recommendation(s) require attention.",

            'Current top governance recommendation is '
                .($topRecommendationCode ?? 'NONE').'.',

            'Current recommended governance path is '
                .($topRecommendedPath ?? 'NONE').'.',

            'Source final governance decision recorded is '
                .($sourceFinalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Source final governance decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Authorized-human governance confirmation attribution complete is '
                .($confirmationAttributionComplete ? 'YES' : 'NO').'.',

            'Governance decision confirmation completed is '
                .($confirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorized is '
                .($controlledActivationAuthorized ? 'YES' : 'NO').'.',

            'Controlled activation authorization attribution complete is '
                .($activationAttributionComplete ? 'YES' : 'NO').'.',

            'Management escalation recommended is '
                .($managementEscalationRecommended ? 'YES' : 'NO').'.',

            'Immediate escalation required is '
                .($immediateEscalationRequired ? 'YES' : 'NO').'.',

            'Executive governance-confirmation intelligence remains informational and does not make, record, complete, authorize, confirm, activate, or execute any governance action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */

        $executiveSummary = [
            'executive_governance_confirmation_status' =>
                $executiveStatus,

            'executive_readiness' =>
                $executiveReadiness,

            'executive_confidence' =>
                $executiveConfidence,

            'executive_governance_confirmation_score' =>
                $executiveScore,

            'confirmation_progression_readiness' =>
                $confirmationProgressionReadiness,

            'confirmation_progression_blocked' =>
                $confirmationProgressionBlocked,

            'controlled_activation_progression_blocked' =>
                $controlledActivationProgressionBlocked,

            'governance_confirmation_completion_eligibility' =>
                $confirmationCompletionEligibility,

            'unrestricted_confirmation_eligibility' =>
                $unrestrictedConfirmationEligibility,

            'controlled_activation_consideration_eligibility' =>
                $controlledActivationConsiderationEligibility,

            'confirmation_safety_status' =>
                $confirmationSafetyStatus,

            'confirmation_safety_level' =>
                $confirmationSafetyLevel,

            'confirmation_safety_score' =>
                $safetyScore,

            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                $confirmationRiskScore,

            'top_recommendation_code' =>
                $topRecommendationCode,

            'top_recommended_path' =>
                $topRecommendedPath,

            'management_escalation_recommended' =>
                $managementEscalationRecommended,

            'immediate_escalation_required' =>
                $immediateEscalationRequired,

            'source_final_governance_decision_recorded' =>
                $sourceFinalGovernanceDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'governance_confirmation_decision_recorded' =>
                $confirmationDecisionRecorded,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,

            'activation_attribution_complete' =>
                $activationAttributionComplete,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,
        ];

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'EXECUTIVE_GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_INTELLIGENCE_AVAILABLE',

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
            | Executive Intelligence
            |--------------------------------------------------------------------------
            */

            'executive_governance_confirmation_state' =>
                $executiveSummary,

            'executive_summary' =>
                $executiveSummary,

            'score_components' =>
                $scoreComponents,

            'executive_attention_items' =>
                $executiveAttentionItems,

            'executive_priorities' =>
                $executivePriorities,

            'executive_findings' =>
                $executiveFindings,

            /*
            |--------------------------------------------------------------------------
            | Supporting Intelligence Context
            |--------------------------------------------------------------------------
            */

            'confirmation_state_context' =>
                $confirmationState,

            'condition_restriction_context' =>
                $conditionRestrictionState,

            'confirmation_eligibility_context' =>
                $eligibilityState,

            'confirmation_safety_context' =>
                $safetyState,

            'recommendation_context' => [
                'recommendation_status' =>
                    $recommendationState['recommendation_status'] ?? null,

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

                'management_attention_required' =>
                    $recommendationState['management_attention_required']
                    ?? false,
            ],

            'dominant_eligibility_restriction' =>
                $dominantEligibilityRestriction,

            /*
            |--------------------------------------------------------------------------
            | Condition / Restriction Context
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

            'resolution_context' => [
                'confirmation_readiness_score' =>
                    $confirmationReadinessScore,

                'confirmation_risk_score' =>
                    $confirmationRiskScore,

                'eligibility_score' =>
                    $eligibilityScore,

                'safety_score' =>
                    $safetyScore,

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
            ],

            /*
            |--------------------------------------------------------------------------
            | Authority Context
            |--------------------------------------------------------------------------
            */

            'confirmation_attribution_context' =>
                $confirmationAttributionContext,

            'controlled_activation_context' =>
                $controlledActivationContext,

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
                array_merge(
                    $governanceContext,
                    [
                        'step_68_executive_confirmation_intelligence' =>
                            true,

                        'executive_confirmation_authority' =>
                            'INFORMATIONAL_EXECUTIVE_INTELLIGENCE_ONLY',

                        'confirmation_authority' =>
                            'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

                        'activation_authority' =>
                            'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

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

                        'automatic_confirmation_allowed' =>
                            false,

                        'automatic_activation_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,
                    ]
                ),

            'source_context' =>
                $sourceContext,

            /*
            |--------------------------------------------------------------------------
            | Step 68.7 Guardrails
            |--------------------------------------------------------------------------
            */

            'executive_governance_confirmation_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Executive Status
    |--------------------------------------------------------------------------
    */

    private function determineExecutiveStatus(
        int $criticalOpenConditions,
        int $criticalRestrictions,
        float $riskScore,
        int $blockingConfirmationConditions,
        int $materialRestrictions,
        bool $confirmationCompleted,
        bool $activationAuthorized
    ): string {
        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $riskScore >= 95
        ) {
            return 'CRITICAL_GOVERNANCE_CONFIRMATION_PRESSURE';
        }

        if (
            !$confirmationCompleted
            && (
                $blockingConfirmationConditions > 0
                || $materialRestrictions > 0
                || $riskScore >= 70
            )
        ) {
            return 'CONTROLLED_MATERIAL_GOVERNANCE_CONFIRMATION_PRESSURE';
        }

        if (!$confirmationCompleted) {
            return 'CONTROLLED_PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        }

        if (!$activationAuthorized) {
            return 'GOVERNANCE_CONFIRMATION_COMPLETE_CONTROLLED_ACTIVATION_PENDING';
        }

        return 'CONTROLLED_GOVERNANCE_CONFIRMATION_AND_ACTIVATION_AUTHORIZATION_STATE';
    }

    /*
    |--------------------------------------------------------------------------
    | Executive Readiness
    |--------------------------------------------------------------------------
    */

    private function determineExecutiveReadiness(
        float $score,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionAuthorized,
        bool $governanceValidationCompleted,
        bool $confirmationCompleted,
        int $blockingConditions,
        int $materialRestrictions
    ): string {
        if (
            !$sourceFinalDecisionRecorded
            || !$sourceDecisionAuthorized
        ) {
            return 'EXTREMELY_LIMITED_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if (!$governanceValidationCompleted) {
            return 'VERY_LIMITED_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if (
            $blockingConditions > 0
            || $materialRestrictions > 0
        ) {
            return 'LIMITED_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if (!$confirmationCompleted) {
            return 'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REVIEW_READY';
        }

        if ($score >= 80) {
            return 'STRONG_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($score >= 60) {
            return 'MODERATE_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
        }

        return 'LIMITED_EXECUTIVE_GOVERNANCE_CONFIRMATION_READINESS';
    }

    /*
    |--------------------------------------------------------------------------
    | Executive Confidence
    |--------------------------------------------------------------------------
    */

    private function determineExecutiveConfidence(
        float $score,
        bool $governanceIntegrityIntact,
        int $criticalConditions,
        int $criticalRestrictions
    ): string {
        if (
            !$governanceIntegrityIntact
            || $criticalConditions > 0
            || $criticalRestrictions > 0
        ) {
            return 'EXTREMELY_LIMITED';
        }

        if ($score >= 85) {
            return 'VERY_HIGH';
        }

        if ($score >= 70) {
            return 'HIGH';
        }

        if ($score >= 55) {
            return 'MODERATE';
        }

        if ($score >= 35) {
            return 'LIMITED';
        }

        if ($score >= 15) {
            return 'VERY_LIMITED';
        }

        return 'EXTREMELY_LIMITED';
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
    | Step 68.7 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'executive_governance_confirmation_intelligence_enabled' =>
                true,

            'executive_intelligence_is_governance_confirmation' =>
                false,

            'executive_intelligence_makes_governance_confirmation_decision' =>
                false,

            'executive_intelligence_records_governance_confirmation_decision' =>
                false,

            'executive_intelligence_completes_governance_confirmation' =>
                false,

            'executive_intelligence_is_final_governance_decision' =>
                false,

            'executive_intelligence_makes_final_governance_decision' =>
                false,

            'executive_intelligence_records_final_governance_decision' =>
                false,

            'executive_intelligence_approves_strategic_plan' =>
                false,

            'executive_intelligence_rejects_strategic_plan' =>
                false,

            'executive_intelligence_conditionally_approves_strategic_plan' =>
                false,

            'executive_intelligence_defers_strategic_plan' =>
                false,

            'executive_intelligence_accepts_governance_risk' =>
                false,

            'executive_intelligence_authorizes_controlled_activation' =>
                false,

            'executive_intelligence_activates_strategic_plan' =>
                false,

            'executive_intelligence_changes_confirmation_status' =>
                false,

            'executive_intelligence_changes_confirmation_decision' =>
                false,

            'executive_intelligence_changes_confirmation_outcome' =>
                false,

            'executive_intelligence_changes_final_governance_decision' =>
                false,

            'executive_intelligence_changes_governance_validation' =>
                false,

            'executive_intelligence_changes_controlled_activation_status' =>
                false,

            'executive_intelligence_changes_plan_status' =>
                false,

            'executive_intelligence_changes_action_state' =>
                false,

            'executive_intelligence_changes_priority' =>
                false,

            'executive_intelligence_changes_eligibility' =>
                false,

            'executive_intelligence_resolves_conditions' =>
                false,

            'executive_intelligence_waives_conditions' =>
                false,

            'executive_intelligence_removes_restrictions' =>
                false,

            'executive_intelligence_resolves_dependencies' =>
                false,

            'executive_intelligence_validates_evidence' =>
                false,

            'executive_intelligence_completes_confirmation_attribution' =>
                false,

            'executive_intelligence_completes_activation_attribution' =>
                false,

            'executive_score_authorizes_confirmation' =>
                false,

            'executive_score_authorizes_activation' =>
                false,

            'executive_score_authorizes_approval' =>
                false,

            'executive_score_authorizes_execution' =>
                false,

            'executive_readiness_authorizes_confirmation' =>
                false,

            'executive_readiness_authorizes_activation' =>
                false,

            'executive_confidence_authorizes_confirmation' =>
                false,

            'executive_confidence_authorizes_activation' =>
                false,

            'management_escalation_authorizes_confirmation' =>
                false,

            'management_escalation_authorizes_activation' =>
                false,

            'executive_intelligence_authorizes_ai_change' =>
                false,

            'executive_intelligence_authorizes_execution' =>
                false,

            'executive_intelligence_authorizes_deployment' =>
                false,

            'executive_intelligence_authorizes_rollback' =>
                false,

            'executive_intelligence_authorizes_clinical_action' =>
                false,

            'executive_intelligence_overrides_human_review' =>
                false,

            'executive_intelligence_overrides_governance_validation' =>
                false,

            'executive_intelligence_overrides_final_governance_decision' =>
                false,

            'executive_intelligence_overrides_confirmation_authority' =>
                false,

            'executive_intelligence_overrides_activation_authority' =>
                false,

            'executive_intelligence_overrides_evidence_requirements' =>
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
                'Step 68.7 executive strategic plan governance decision confirmation intelligence consolidates confirmation state, unresolved conditions, material restrictions, eligibility, safety, evidence and resolution state, authorized-human attribution, recommendations, confirmation risk, governance pressure, executive readiness, executive confidence, management attention, and controlled-activation constraints for authorized human executive oversight only. Executive status, readiness, confidence, scores, attention items, priorities, findings, and recommendations do not constitute or complete governance confirmation, make or record the final governance decision, approve, reject, conditionally approve, defer, accept governance risk, authorize controlled activation, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final governance decision authority, governance-confirmation authority, and controlled-activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}