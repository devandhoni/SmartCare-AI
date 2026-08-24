<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionExecutiveIntelligenceEngine
{
    public function analyze(?int $finalGovernanceDecisionId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load Final Governance Decision
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecision = $finalGovernanceDecisionId
            ? AIGovernanceStrategicPlanFinalGovernanceDecision::find($finalGovernanceDecisionId)
            : AIGovernanceStrategicPlanFinalGovernanceDecision::latest('id')->first();

        if (!$finalGovernanceDecision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan final governance decision record is available for executive intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 67 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionStateIntelligenceEngine::class
        );

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionConditionRestrictionIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionRecommendationIntelligenceEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Step 67 Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($finalGovernanceDecision->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($finalGovernanceDecision->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($finalGovernanceDecision->id);

        $recommendation =
            $recommendationEngine->analyze($finalGovernanceDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionState =
            $state['final_governance_decision_state'] ?? [];

        $stateAuthorizationContext =
            $state['authorization_context'] ?? [];

        $stateConfirmationContext =
            $state['confirmation_context'] ?? [];

        $stateReviewContext =
            $state['review_context'] ?? [];

        $stateValidationContext =
            $state['validation_context'] ?? [];

        $stateGovernanceContext =
            $state['governance_context'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary'] ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary'] ?? [];

        $dominantDecisionCondition =
            $conditionRestriction['dominant_decision_condition'] ?? [];

        $dominantDecisionRestriction =
            $conditionRestriction['dominant_decision_restriction'] ?? [];

        $eligibilityState =
            $eligibilitySafety['final_governance_decision_eligibility_state']
            ?? $eligibilitySafety['final_decision_eligibility_state']
            ?? [];

        $safetyState =
            $eligibilitySafety['final_governance_decision_safety_state']
            ?? $eligibilitySafety['final_decision_safety_state']
            ?? [];

        $eligibilityAuthorizationContext =
            $eligibilitySafety['authorization_context'] ?? [];

        $eligibilityGovernanceValidationContext =
            $eligibilitySafety['governance_validation_context'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $topRecommendation =
            $recommendation['top_recommendation'] ?? [];

        $decisionPathContext =
            $recommendation['decision_path_context'] ?? [];

        $recommendationEligibilityContext =
            $recommendation['final_decision_eligibility_context'] ?? [];

        $recommendationSafetyContext =
            $recommendation['final_decision_safety_context'] ?? [];

        $recommendationRiskContext =
            $recommendation['risk_context'] ?? [];

        $recommendationAuthorizationContext =
            $recommendation['authorization_context'] ?? [];

        $recommendationGovernanceValidationContext =
            $recommendation['governance_validation_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core State
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionRecorded =
            !empty($finalGovernanceDecision->final_governance_decision);

        $decisionMadeByAuthorizedHuman =
            (bool) $finalGovernanceDecision->decision_made_by_authorized_human;

        $finalGovernanceConfirmationCompleted =
            (bool) $finalGovernanceDecision->final_governance_confirmation_completed;

        $sourceFinalHumanDecisionRecorded =
            (bool) $finalGovernanceDecision->source_final_human_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $finalGovernanceDecision->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $finalGovernanceDecision->source_governance_validation_completed;

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        $blockingConditions =
            (int) (
                $conditionRestrictionState['blocking_conditions']
                ?? $conditionSummary['blocking_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionRestrictionState['constraining_conditions']
                ?? $conditionSummary['constraining_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $conditionRestrictionState['material_restrictions']
                ?? $restrictionSummary['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $conditionRestrictionState['critical_restrictions']
                ?? $restrictionSummary['critical_restrictions']
                ?? 0
            );

        $conditionResolutionScore =
            (float) (
                $conditionRestrictionState['condition_resolution_score']
                ?? $finalGovernanceDecision->condition_resolution_score
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $conditionRestrictionState['evidence_resolution_score']
                ?? $finalGovernanceDecision->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $conditionRestrictionState['combined_resolution_score']
                ?? $finalGovernanceDecision->combined_resolution_score
                ?? 0
            );

        $conditionPressureScore =
            (float) (
                $conditionRestrictionState['condition_pressure_score']
                ?? $conditionSummary['condition_pressure_score']
                ?? 0
            );

        $restrictionPressureScore =
            (float) (
                $conditionRestrictionState['restriction_pressure_score']
                ?? $restrictionSummary['restriction_pressure_score']
                ?? 0
            );

        $combinedConditionRestrictionPressureScore =
            (float) (
                $conditionRestrictionState['combined_condition_restriction_pressure_score']
                ?? max($conditionPressureScore, $restrictionPressureScore)
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety State
        |--------------------------------------------------------------------------
        */

        $finalGovernanceReviewEligibility =
            $eligibilityState['final_governance_review_eligibility']
            ?? $recommendationEligibilityContext['final_governance_review_eligibility']
            ?? 'UNKNOWN';

        $unrestrictedFinalDecisionEligibility =
            $eligibilityState['unrestricted_final_decision_eligibility']
            ?? $recommendationEligibilityContext['unrestricted_final_decision_eligibility']
            ?? 'UNKNOWN';

        $finalGovernanceConfirmationEligibility =
            $eligibilityState['final_governance_confirmation_eligibility']
            ?? $recommendationEligibilityContext['final_governance_confirmation_eligibility']
            ?? 'UNKNOWN';

        $strategicPlanActivationEligibility =
            $eligibilityState['strategic_plan_activation_eligibility']
            ?? $recommendationEligibilityContext['strategic_plan_activation_eligibility']
            ?? 'UNKNOWN';

        $finalGovernanceDecisionEligibilityScore =
            (float) (
                $eligibilityState['final_governance_decision_eligibility_score']
                ?? $recommendationEligibilityContext['final_governance_decision_eligibility_score']
                ?? $recommendationSummary['final_governance_decision_eligibility_score']
                ?? 0
            );

        $authorizedHumanReviewAllowed =
            (
                $eligibilityState['authorized_human_review_allowed']
                ?? $recommendationEligibilityContext['authorized_human_review_allowed']
                ?? false
            ) === true;

        $unrestrictedFinalGovernanceProgressionAllowed =
            (
                $eligibilityState['unrestricted_final_governance_progression_allowed']
                ?? $recommendationEligibilityContext['unrestricted_final_governance_progression_allowed']
                ?? false
            ) === true;

        $finalGovernanceConfirmationAllowed =
            (
                $eligibilityState['final_governance_confirmation_allowed']
                ?? $recommendationEligibilityContext['final_governance_confirmation_allowed']
                ?? false
            ) === true;

        $strategicPlanActivationAllowed =
            (
                $eligibilityState['strategic_plan_activation_allowed']
                ?? $recommendationEligibilityContext['strategic_plan_activation_allowed']
                ?? false
            ) === true;

        $finalGovernanceDecisionSafetyStatus =
            $safetyState['final_governance_decision_safety_status']
            ?? $recommendationSafetyContext['final_governance_decision_safety_status']
            ?? 'UNKNOWN';

        $finalGovernanceDecisionSafetyLevel =
            $safetyState['final_governance_decision_safety_level']
            ?? $recommendationSafetyContext['final_governance_decision_safety_level']
            ?? 'UNKNOWN';

        $finalGovernanceDecisionSafetyScore =
            (float) (
                $safetyState['final_governance_decision_safety_score']
                ?? $recommendationSafetyContext['final_governance_decision_safety_score']
                ?? $recommendationSummary['final_governance_decision_safety_score']
                ?? 0
            );

        $governanceIntegrityIntact =
            (
                $safetyState['governance_integrity_intact']
                ?? $recommendationSafetyContext['governance_integrity_intact']
                ?? $stateGovernanceContext['governance_integrity_intact']
                ?? true
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Decision Risk
        |--------------------------------------------------------------------------
        */

        $finalDecisionRiskLevel =
            $recommendationRiskContext['final_decision_risk_level']
            ?? $finalGovernanceDecisionState['final_decision_risk_level']
            ?? $finalGovernanceDecision->final_decision_risk_level
            ?? 'UNKNOWN';

        $finalDecisionRiskScore =
            (float) (
                $recommendationRiskContext['final_decision_risk_score']
                ?? $finalGovernanceDecisionState['final_decision_risk_score']
                ?? $finalGovernanceDecision->final_decision_risk_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Readiness
        |--------------------------------------------------------------------------
        */

        $finalDecisionReadiness =
            $finalGovernanceDecisionState['final_governance_decision_readiness']
            ?? $finalGovernanceDecisionState['final_decision_readiness']
            ?? $finalGovernanceDecision->final_decision_readiness
            ?? 'UNKNOWN';

        $finalDecisionReadinessScore =
            (float) (
                $finalGovernanceDecisionState['final_governance_decision_readiness_score']
                ?? $finalGovernanceDecisionState['final_decision_readiness_score']
                ?? $finalGovernanceDecision->final_decision_readiness_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Context
        |--------------------------------------------------------------------------
        */

        $recommendationStatus =
            $recommendationState['recommendation_status']
            ?? $recommendationSummary['recommendation_status']
            ?? 'UNKNOWN';

        $totalRecommendations =
            (int) (
                $recommendationSummary['total_recommendations']
                ?? 0
            );

        $topRecommendationCode =
            $recommendationSummary['top_recommendation_code']
            ?? $topRecommendation['recommendation_code']
            ?? null;

        $topRecommendedGovernancePath =
            $recommendationSummary['top_recommended_governance_path']
            ?? $recommendationState['top_recommended_governance_path']
            ?? $decisionPathContext['recommended_path']
            ?? null;

        $recommendationPriorityScore =
            (float) (
                $recommendationSummary['recommendation_priority_score']
                ?? $recommendationState['recommendation_priority_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Score
        |--------------------------------------------------------------------------
        |
        | Executive score is informational only.
        | It NEVER authorizes a final decision, confirmation, activation,
        | approval, rejection, risk acceptance, or execution.
        |--------------------------------------------------------------------------
        */

        $executiveScoreComponents = [
            'final_decision_readiness_score' =>
                round($finalDecisionReadinessScore, 2),

            'final_governance_decision_eligibility_score' =>
                round($finalGovernanceDecisionEligibilityScore, 2),

            'final_governance_decision_safety_score' =>
                round($finalGovernanceDecisionSafetyScore, 2),

            'condition_resolution_score' =>
                round($conditionResolutionScore, 2),

            'combined_resolution_score' =>
                round($combinedResolutionScore, 2),

            'inverse_decision_risk_score' =>
                round(max(0, 100 - $finalDecisionRiskScore), 2),

            'inverse_condition_restriction_pressure_score' =>
                round(
                    max(
                        0,
                        100 - $combinedConditionRestrictionPressureScore
                    ),
                    2
                ),
        ];

        $executiveFinalGovernanceDecisionScore =
            round(
                (
                    ($executiveScoreComponents['final_decision_readiness_score'] * 0.20)
                    + ($executiveScoreComponents['final_governance_decision_eligibility_score'] * 0.15)
                    + ($executiveScoreComponents['final_governance_decision_safety_score'] * 0.15)
                    + ($executiveScoreComponents['condition_resolution_score'] * 0.10)
                    + ($executiveScoreComponents['combined_resolution_score'] * 0.10)
                    + ($executiveScoreComponents['inverse_decision_risk_score'] * 0.15)
                    + ($executiveScoreComponents['inverse_condition_restriction_pressure_score'] * 0.15)
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Status
        |--------------------------------------------------------------------------
        */

        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
        ) {
            $executiveStatus =
                'CRITICAL_FINAL_GOVERNANCE_DECISION_PRESSURE';
        } elseif (
            $blockingConditions > 0
            || $materialRestrictions > 0
            || !$sourceFinalHumanDecisionRecorded
            || !$sourceDecisionMadeByAuthorizedHuman
            || !$sourceGovernanceValidationCompleted
        ) {
            $executiveStatus =
                'CONTROLLED_MATERIAL_FINAL_GOVERNANCE_DECISION_PRESSURE';
        } elseif (!$finalGovernanceDecisionRecorded) {
            $executiveStatus =
                'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION_PENDING';
        } elseif (!$finalGovernanceConfirmationCompleted) {
            $executiveStatus =
                'FINAL_GOVERNANCE_DECISION_RECORDED_PENDING_CONFIRMATION';
        } else {
            $executiveStatus =
                'FINAL_GOVERNANCE_DECISION_GOVERNANCE_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Readiness Classification
        |--------------------------------------------------------------------------
        */

        if ($executiveFinalGovernanceDecisionScore >= 80) {
            $executiveReadiness =
                'STRONG_EXECUTIVE_FINAL_GOVERNANCE_DECISION_READINESS';

            $executiveConfidence =
                'STRONG';
        } elseif ($executiveFinalGovernanceDecisionScore >= 60) {
            $executiveReadiness =
                'MODERATE_EXECUTIVE_FINAL_GOVERNANCE_DECISION_READINESS';

            $executiveConfidence =
                'MODERATE';
        } elseif ($executiveFinalGovernanceDecisionScore >= 40) {
            $executiveReadiness =
                'LIMITED_EXECUTIVE_FINAL_GOVERNANCE_DECISION_READINESS';

            $executiveConfidence =
                'LIMITED';
        } elseif ($executiveFinalGovernanceDecisionScore >= 20) {
            $executiveReadiness =
                'VERY_LIMITED_EXECUTIVE_FINAL_GOVERNANCE_DECISION_READINESS';

            $executiveConfidence =
                'VERY_LIMITED';
        } else {
            $executiveReadiness =
                'EXTREMELY_LIMITED_EXECUTIVE_FINAL_GOVERNANCE_DECISION_READINESS';

            $executiveConfidence =
                'EXTREMELY_LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0;

        $managementEscalationRecommended =
            $immediateHumanInterventionRequired
            || $blockingConditions > 0
            || $materialRestrictions > 0
            || $finalDecisionRiskScore >= 70
            || !$sourceFinalHumanDecisionRecorded
            || !$sourceDecisionMadeByAuthorizedHuman
            || !$sourceGovernanceValidationCompleted;

        $humanManagementAttentionRequired =
            $managementEscalationRecommended
            || !$finalGovernanceDecisionRecorded
            || !$finalGovernanceConfirmationCompleted;

        if ($immediateHumanInterventionRequired) {
            $humanManagementAttentionLevel = 'CRITICAL';
        } elseif (
            $blockingConditions > 0
            || $materialRestrictions > 0
            || $finalDecisionRiskScore >= 70
        ) {
            $humanManagementAttentionLevel = 'HIGH';
        } elseif ($humanManagementAttentionRequired) {
            $humanManagementAttentionLevel = 'MODERATE';
        } else {
            $humanManagementAttentionLevel = 'ADVISORY';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Items
        |--------------------------------------------------------------------------
        */

        $executiveAttentionItems = [];

        if (!$sourceFinalHumanDecisionRecorded) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',

                'category' =>
                    'HUMAN_DECISION',

                'priority_level' =>
                    'HIGH',

                'message' =>
                    'The source strategic plan final authorized-human decision has not yet been recorded.',
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_AUTHORIZED_HUMAN_ATTRIBUTION_REQUIRED',

                'category' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'HIGH',

                'message' =>
                    'The source strategic plan decision has not yet been explicitly attributed to authorized human governance.',
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_REQUIRED',

                'category' =>
                    'GOVERNANCE_VALIDATION',

                'priority_level' =>
                    'HIGH',

                'message' =>
                    'Required source governance validation remains incomplete.',
            ];
        }

        if ($blockingConditions > 0) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'BLOCKING_FINAL_GOVERNANCE_CONDITIONS',

                'category' =>
                    'DECISION_CONDITION',

                'priority_level' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking final governance decision condition(s) remain active.",
            ];
        }

        if ($materialRestrictions > 0) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'MATERIAL_FINAL_GOVERNANCE_RESTRICTIONS',

                'category' =>
                    'DECISION_RESTRICTION',

                'priority_level' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material final governance decision restriction(s) remain active.",
            ];
        }

        if ($finalDecisionRiskScore >= 70) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'HIGH_FINAL_GOVERNANCE_DECISION_RISK',

                'category' =>
                    'RISK',

                'priority_level' =>
                    'HIGH',

                'value' =>
                    $finalDecisionRiskScore,

                'message' =>
                    "Final governance decision risk remains {$finalDecisionRiskLevel} with score {$finalDecisionRiskScore}.",
            ];
        }

        if (!$finalGovernanceDecisionRecorded) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'FINAL_GOVERNANCE_DECISION_NOT_RECORDED',

                'category' =>
                    'FINAL_GOVERNANCE_DECISION',

                'priority_level' =>
                    'MODERATE',

                'message' =>
                    'A final governance decision has not yet been explicitly recorded by authorized human governance.',
            ];
        }

        if (!$finalGovernanceConfirmationCompleted) {
            $executiveAttentionItems[] = [
                'attention_code' =>
                    'FINAL_GOVERNANCE_CONFIRMATION_PENDING',

                'category' =>
                    'CONFIRMATION',

                'priority_level' =>
                    'MODERATE',

                'message' =>
                    'Final governance confirmation has not yet been completed.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Findings
        |--------------------------------------------------------------------------
        */

        $executiveFindings = [
            "Executive final governance decision intelligence is based on final governance decision record {$finalGovernanceDecision->id}.",

            "Current executive final governance decision status is {$executiveStatus}.",

            "Current executive final governance decision readiness is {$executiveReadiness} with score {$executiveFinalGovernanceDecisionScore}.",

            "Current executive final governance decision confidence is {$executiveConfidence}.",

            "Current source final human decision recorded is "
                .($sourceFinalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            "Current source strategic plan decision made by authorized human is "
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            "Current source governance validation completed is "
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$constrainingConditions} constraining final governance decision condition(s) remain active.",

            "{$materialRestrictions} material final governance decision restriction(s) remain active.",

            "{$criticalRestrictions} critical final governance decision restriction(s) are represented.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedConditionRestrictionPressureScore}.",

            "Current final governance review eligibility is {$finalGovernanceReviewEligibility}.",

            "Current unrestricted final governance progression eligibility is {$unrestrictedFinalDecisionEligibility}.",

            "Current final governance confirmation eligibility is {$finalGovernanceConfirmationEligibility}.",

            "Current strategic plan activation eligibility is {$strategicPlanActivationEligibility}.",

            "Current final governance decision eligibility score is {$finalGovernanceDecisionEligibilityScore}.",

            "Current final governance decision safety status is {$finalGovernanceDecisionSafetyStatus} with score {$finalGovernanceDecisionSafetyScore}.",

            "Current final governance decision risk is {$finalDecisionRiskLevel} with score {$finalDecisionRiskScore}.",

            "Current final governance recommendation status is {$recommendationStatus}.",

            'Current top final governance recommendation is '
                .($topRecommendationCode ?? 'NONE').'.',

            'Current top recommended authorized-human governance path is '
                .($topRecommendedGovernancePath ?? 'NONE').'.',

            "Final governance decision recorded is "
                .($finalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            "Final governance decision made by authorized human is "
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            "Final governance confirmation completed is "
                .($finalGovernanceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Executive final governance intelligence remains informational and does not make, record, confirm, approve, reject, conditionally approve, defer, accept governance risk, activate, resolve, validate, execute, deploy, roll back, or initiate clinical action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Priorities
        |--------------------------------------------------------------------------
        */

        $executivePriorities =
            $recommendation['management_priorities']
            ?? $conditionRestriction['management_priorities']
            ?? $eligibilitySafety['management_priorities']
            ?? $state['management_priorities']
            ?? [];

        $executivePriorities[] =
            'Keep executive final-governance intelligence strictly separated from final governance decision authority, confirmation authority, strategic-plan activation authority, and execution authority.';

        $executivePriorities[] =
            'Ensure any final governance decision is explicitly made, justified, documented, and attributed to authorized human governance.';

        $executivePriorities[] =
            'Preserve source human-decision attribution, governance-validation attribution, final-governance attribution, evidence quality, traceability, safety controls, and authority separation throughout final strategic-plan governance.';

        $executivePriorities =
            array_values(
                array_unique(
                    array_filter($executivePriorities)
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Return Executive Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_EXECUTIVE_FINAL_GOVERNANCE_DECISION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_final_governance_decision_id' =>
                $finalGovernanceDecision->id,

            'final_governance_decision_code' =>
                $finalGovernanceDecision->final_governance_decision_code,

            'strategic_plan_decision_validation_id' =>
                $finalGovernanceDecision->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $finalGovernanceDecision->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $finalGovernanceDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $finalGovernanceDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $finalGovernanceDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $finalGovernanceDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $finalGovernanceDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $finalGovernanceDecision->decision_scope,

            'resident_id' =>
                $finalGovernanceDecision->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Executive Final Governance State
            |--------------------------------------------------------------------------
            */

            'executive_final_governance_state' => [
                'executive_final_governance_decision_status' =>
                    $executiveStatus,

                'executive_readiness' =>
                    $executiveReadiness,

                'executive_confidence' =>
                    $executiveConfidence,

                'executive_final_governance_decision_score' =>
                    $executiveFinalGovernanceDecisionScore,

                'human_management_attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'management_escalation_recommended' =>
                    $managementEscalationRecommended,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'final_governance_decision_recorded' =>
                    $finalGovernanceDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'final_governance_confirmation_completed' =>
                    $finalGovernanceConfirmationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Summary
            |--------------------------------------------------------------------------
            */

            'executive_summary' => [
                'final_governance_decision_status' =>
                    $finalGovernanceDecision->final_governance_decision_status,

                'prepared_decision' =>
                    $finalGovernanceDecision->prepared_decision,

                'final_governance_decision' =>
                    $finalGovernanceDecision->final_governance_decision,

                'final_governance_outcome' =>
                    $finalGovernanceDecision->final_governance_outcome,

                'final_governance_outcome_status' =>
                    $finalGovernanceDecision->final_governance_outcome_status,

                'final_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_decision_readiness_score' =>
                    $finalDecisionReadinessScore,

                'final_decision_risk_level' =>
                    $finalDecisionRiskLevel,

                'final_decision_risk_score' =>
                    $finalDecisionRiskScore,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedConditionRestrictionPressureScore,

                'final_governance_review_eligibility' =>
                    $finalGovernanceReviewEligibility,

                'unrestricted_final_decision_eligibility' =>
                    $unrestrictedFinalDecisionEligibility,

                'final_governance_confirmation_eligibility' =>
                    $finalGovernanceConfirmationEligibility,

                'strategic_plan_activation_eligibility' =>
                    $strategicPlanActivationEligibility,

                'final_governance_decision_eligibility_score' =>
                    $finalGovernanceDecisionEligibilityScore,

                'final_governance_decision_safety_status' =>
                    $finalGovernanceDecisionSafetyStatus,

                'final_governance_decision_safety_score' =>
                    $finalGovernanceDecisionSafetyScore,

                'recommendation_status' =>
                    $recommendationStatus,

                'total_recommendations' =>
                    $totalRecommendations,

                'top_recommendation_code' =>
                    $topRecommendationCode,

                'top_recommended_governance_path' =>
                    $topRecommendedGovernancePath,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Readiness Context
            |--------------------------------------------------------------------------
            */

            'executive_readiness_context' => [
                'final_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_decision_readiness_score' =>
                    $finalDecisionReadinessScore,

                'executive_readiness' =>
                    $executiveReadiness,

                'executive_confidence' =>
                    $executiveConfidence,

                'executive_final_governance_decision_score' =>
                    $executiveFinalGovernanceDecisionScore,

                'authorized_human_review_allowed' =>
                    $authorizedHumanReviewAllowed,

                'unrestricted_final_governance_progression_allowed' =>
                    $unrestrictedFinalGovernanceProgressionAllowed,

                'final_governance_confirmation_allowed' =>
                    $finalGovernanceConfirmationAllowed,

                'strategic_plan_activation_allowed' =>
                    $strategicPlanActivationAllowed,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Condition / Restriction Context
            |--------------------------------------------------------------------------
            */

            'executive_condition_restriction_context' => [
                'condition_restriction_state' =>
                    $conditionRestrictionState['state'] ?? null,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

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
                    $combinedConditionRestrictionPressureScore,

                'dominant_decision_condition' =>
                    $dominantDecisionCondition,

                'dominant_decision_restriction' =>
                    $dominantDecisionRestriction,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Eligibility / Safety Context
            |--------------------------------------------------------------------------
            */

            'executive_eligibility_safety_context' => [
                'final_governance_review_eligibility' =>
                    $finalGovernanceReviewEligibility,

                'unrestricted_final_decision_eligibility' =>
                    $unrestrictedFinalDecisionEligibility,

                'final_governance_confirmation_eligibility' =>
                    $finalGovernanceConfirmationEligibility,

                'strategic_plan_activation_eligibility' =>
                    $strategicPlanActivationEligibility,

                'final_governance_decision_eligibility_score' =>
                    $finalGovernanceDecisionEligibilityScore,

                'final_governance_decision_safety_status' =>
                    $finalGovernanceDecisionSafetyStatus,

                'final_governance_decision_safety_level' =>
                    $finalGovernanceDecisionSafetyLevel,

                'final_governance_decision_safety_score' =>
                    $finalGovernanceDecisionSafetyScore,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'authorized_human_review_allowed' =>
                    $authorizedHumanReviewAllowed,

                'unrestricted_final_governance_progression_allowed' =>
                    $unrestrictedFinalGovernanceProgressionAllowed,

                'final_governance_confirmation_allowed' =>
                    $finalGovernanceConfirmationAllowed,

                'strategic_plan_activation_allowed' =>
                    $strategicPlanActivationAllowed,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Risk Context
            |--------------------------------------------------------------------------
            */

            'executive_risk_context' => [
                'final_decision_risk_level' =>
                    $finalDecisionRiskLevel,

                'final_decision_risk_score' =>
                    $finalDecisionRiskScore,

                'high_risk' =>
                    $finalDecisionRiskScore >= 70,

                'risk_requires_authorized_human_governance' =>
                    $finalDecisionRiskScore >= 70,

                'risk_score_authorizes_final_decision' =>
                    false,

                'risk_score_authorizes_confirmation' =>
                    false,

                'risk_score_authorizes_activation' =>
                    false,

                'risk_score_authorizes_execution' =>
                    false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Recommendation Context
            |--------------------------------------------------------------------------
            */

            'executive_recommendation_context' => [
                'recommendation_status' =>
                    $recommendationStatus,

                'total_recommendations' =>
                    $totalRecommendations,

                'critical_recommendations' =>
                    $recommendationSummary['critical_recommendations'] ?? 0,

                'high_recommendations' =>
                    $recommendationSummary['high_recommendations'] ?? 0,

                'moderate_recommendations' =>
                    $recommendationSummary['moderate_recommendations'] ?? 0,

                'advisory_recommendations' =>
                    $recommendationSummary['advisory_recommendations'] ?? 0,

                'top_recommendation_code' =>
                    $topRecommendationCode,

                'top_recommended_governance_path' =>
                    $topRecommendedGovernancePath,

                'recommendation_priority_score' =>
                    $recommendationPriorityScore,

                'top_recommendation' =>
                    $topRecommendation,

                'decision_path_context' =>
                    $decisionPathContext,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Authorization Context
            |--------------------------------------------------------------------------
            */

            'executive_authorization_context' => [
                'source_final_human_decision_recorded' =>
                    $sourceFinalHumanDecisionRecorded,

                'source_decision_made_by_authorized_human' =>
                    $sourceDecisionMadeByAuthorizedHuman,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'final_governance_decision_recorded' =>
                    $finalGovernanceDecisionRecorded,

                'decided_by' =>
                    $finalGovernanceDecision->decided_by,

                'decider_role' =>
                    $finalGovernanceDecision->decider_role,

                'decided_at' =>
                    $finalGovernanceDecision->decided_at,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'confirmed_by' =>
                    $finalGovernanceDecision->confirmed_by,

                'confirmer_role' =>
                    $finalGovernanceDecision->confirmer_role,

                'confirmed_at' =>
                    $finalGovernanceDecision->confirmed_at,

                'final_governance_confirmation_completed' =>
                    $finalGovernanceConfirmationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Governance Validation Context
            |--------------------------------------------------------------------------
            */

            'executive_governance_validation_context' => [
                'source_governance_validation_decision' =>
                    $finalGovernanceDecision->source_governance_validation_decision,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'governance_validation_required' =>
                    (bool) $finalGovernanceDecision->governance_validation_required,

                'source_governance_validation_blocks_unrestricted_progression' =>
                    !$sourceGovernanceValidationCompleted,

                'state_validation_context' =>
                    $stateValidationContext,

                'eligibility_governance_validation_context' =>
                    $eligibilityGovernanceValidationContext,

                'recommendation_governance_validation_context' =>
                    $recommendationGovernanceValidationContext,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Attention
            |--------------------------------------------------------------------------
            */

            'executive_management_attention' => [
                'attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'management_escalation_recommended' =>
                    $managementEscalationRecommended,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'attention_item_count' =>
                    count($executiveAttentionItems),

                'attention_items' =>
                    $executiveAttentionItems,
            ],

            /*
            |--------------------------------------------------------------------------
            | Score Components
            |--------------------------------------------------------------------------
            */

            'score_components' =>
                $executiveScoreComponents,

            /*
            |--------------------------------------------------------------------------
            | Supporting Context
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_state_context' =>
                $finalGovernanceDecisionState,

            'review_context' =>
                $stateReviewContext
                ?: ($recommendation['review_context'] ?? []),

            'validation_context' =>
                $stateValidationContext
                ?: ($recommendation['validation_context'] ?? []),

            'governance_context' =>
                $stateGovernanceContext
                ?: ($recommendation['governance_context'] ?? []),

            'source_context' =>
                $state['source_context']
                ?? $recommendation['source_context']
                ?? [],

            /*
            |--------------------------------------------------------------------------
            | Findings / Priorities
            |--------------------------------------------------------------------------
            */

            'executive_findings' =>
                $executiveFindings,

            'executive_priorities' =>
                $executivePriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 67.7 Guardrails
            |--------------------------------------------------------------------------
            */

            'executive_final_governance_decision_guardrails' => [
                'executive_final_governance_decision_intelligence_enabled' =>
                    true,

                'executive_intelligence_is_final_governance_decision' =>
                    false,

                'executive_intelligence_makes_final_governance_decision' =>
                    false,

                'executive_intelligence_records_final_governance_decision' =>
                    false,

                'executive_intelligence_confirms_final_governance_decision' =>
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

                'executive_intelligence_activates_strategic_plan' =>
                    false,

                'executive_intelligence_resolves_conditions' =>
                    false,

                'executive_intelligence_waives_conditions' =>
                    false,

                'executive_intelligence_removes_restrictions' =>
                    false,

                'executive_intelligence_downgrades_restrictions' =>
                    false,

                'executive_intelligence_resolves_dependencies' =>
                    false,

                'executive_intelligence_validates_evidence' =>
                    false,

                'executive_intelligence_changes_source_human_decision' =>
                    false,

                'executive_intelligence_changes_governance_validation' =>
                    false,

                'executive_intelligence_changes_final_governance_decision_status' =>
                    false,

                'executive_intelligence_changes_final_governance_decision' =>
                    false,

                'executive_intelligence_changes_final_governance_outcome' =>
                    false,

                'executive_intelligence_changes_plan_status' =>
                    false,

                'executive_intelligence_changes_action_state' =>
                    false,

                'executive_intelligence_changes_priority' =>
                    false,

                'executive_intelligence_changes_eligibility' =>
                    false,

                'executive_score_authorizes_final_decision' =>
                    false,

                'executive_score_authorizes_approval' =>
                    false,

                'executive_score_authorizes_rejection' =>
                    false,

                'executive_score_authorizes_conditional_approval' =>
                    false,

                'executive_score_authorizes_deferral' =>
                    false,

                'executive_score_authorizes_risk_acceptance' =>
                    false,

                'executive_score_authorizes_confirmation' =>
                    false,

                'executive_score_authorizes_activation' =>
                    false,

                'executive_score_authorizes_execution' =>
                    false,

                'readiness_score_authorizes_final_decision' =>
                    false,

                'eligibility_score_authorizes_final_decision' =>
                    false,

                'safety_score_authorizes_final_decision' =>
                    false,

                'risk_score_authorizes_final_decision' =>
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

                'recommendation_priority_score_authorizes_final_decision' =>
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

                'executive_intelligence_overrides_final_human_decision' =>
                    false,

                'executive_intelligence_overrides_final_governance_decision' =>
                    false,

                'executive_intelligence_overrides_confirmation_requirements' =>
                    false,

                'executive_intelligence_overrides_evidence_requirements' =>
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

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'final_governance_confirmation_authority_reserved_for_authorized_human' =>
                    true,

                'strategic_plan_activation_requires_explicit_governance_authorization' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,

                'message' =>
                    'Step 67.7 executive final governance decision intelligence consolidates final-governance decision state, readiness, authorization, source human-decision state, source governance-validation state, unresolved conditions, material restrictions, eligibility, safety, resolution, pressure, decision risk, recommendations, confirmation state, and management attention into an executive governance view for explicitly authorized human oversight only. Executive statuses, readiness classifications, scores, findings, attention items, priorities, and recommended governance paths do not constitute or record a final governance decision, approval, rejection, conditional approval, deferral, governance-risk acceptance, final-governance confirmation, strategic-plan activation, condition resolution, restriction removal, evidence validation, governance validation, AI modification, execution, deployment, rollback, or clinical action. Final governance decision and confirmation authority remain reserved exclusively for explicitly authorized human governance.',
            ],
        ];
    }
}