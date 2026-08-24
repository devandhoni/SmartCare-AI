<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionRecommendationIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanFinalGovernanceDecisionConditionRestrictionIntelligenceEngine $conditionRestrictionEngine,
        protected AIGovernanceStrategicPlanFinalGovernanceDecisionEligibilitySafetyIntelligenceEngine $eligibilitySafetyEngine
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
                'message' => 'No AI governance strategic plan final governance decision record is available for recommendation intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 67 Intelligence
        |--------------------------------------------------------------------------
        */

        $conditionRestriction =
            $this->conditionRestrictionEngine->analyze($decision->id);

        $eligibilitySafety =
            $this->eligibilitySafetyEngine->analyze($decision->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

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

        $materialDecisionRestrictions =
            $conditionRestriction['material_decision_restrictions'] ?? [];

        $criticalDecisionRestrictions =
            $conditionRestriction['critical_decision_restrictions'] ?? [];

        $eligibilityState =
            $eligibilitySafety['final_decision_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['final_decision_safety_state'] ?? [];

        $eligibilitySummary =
            $eligibilitySafety['eligibility_summary'] ?? [];

        $safetySummary =
            $eligibilitySafety['safety_summary'] ?? [];

        $dominantEligibilityRestriction =
            $eligibilitySafety['dominant_eligibility_restriction'] ?? null;

        $dominantSafetyRestriction =
            $eligibilitySafety['dominant_safety_restriction'] ?? null;

        $authorizationContext =
            $eligibilitySafety['authorization_context'] ?? [];

        $governanceValidationContext =
            $eligibilitySafety['governance_validation_context'] ?? [];

        $riskContext =
            $eligibilitySafety['risk_context'] ?? [];

        $finalGovernanceDecisionStateContext =
            $eligibilitySafety['final_governance_decision_state_context'] ?? [];

        $reviewContext =
            $eligibilitySafety['review_context'] ?? [];

        $validationContext =
            $eligibilitySafety['validation_context'] ?? [];

        $governanceContext =
            $eligibilitySafety['governance_context'] ?? [];

        $sourceContext =
            $eligibilitySafety['source_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core Governance State
        |--------------------------------------------------------------------------
        */

        $sourceFinalHumanDecisionRecorded =
            (bool) $decision->source_final_human_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $decision->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $decision->source_governance_validation_completed;

        $finalGovernanceDecisionRecorded =
            !empty($decision->final_governance_decision);

        $decisionMadeByAuthorizedHuman =
            (bool) $decision->decision_made_by_authorized_human;

        $finalGovernanceConfirmationCompleted =
            (bool) $decision->final_governance_confirmation_completed;

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Metrics
        |--------------------------------------------------------------------------
        */

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

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
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
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Metrics
        |--------------------------------------------------------------------------
        */

        $eligibilityScore =
            (float) (
                $eligibilityState['final_governance_decision_eligibility_score']
                ?? 0
            );

        $unrestrictedProgressionAllowed =
            ($eligibilityState['unrestricted_final_governance_progression_allowed'] ?? false) === true;

        $confirmationAllowed =
            ($eligibilityState['final_governance_confirmation_allowed'] ?? false) === true;

        $activationAllowed =
            ($eligibilityState['strategic_plan_activation_allowed'] ?? false) === true;

        $safeForAuthorizedHumanReview =
            ($safetyState['safe_for_authorized_human_review'] ?? false) === true;

        $safeForUnrestrictedProgression =
            ($safetyState['safe_for_unrestricted_progression'] ?? false) === true;

        $safetyScore =
            (float) (
                $safetyState['final_governance_decision_safety_score']
                ?? 0
            );

        $safetyLevel =
            $safetyState['final_governance_decision_safety_level']
            ?? 'UNKNOWN';

        $safetyStatus =
            $safetyState['final_governance_decision_safety_status']
            ?? 'UNKNOWN';

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $decisionRiskLevel =
            $riskContext['final_decision_risk_level']
            ?? $decision->final_decision_risk_level
            ?? 'UNKNOWN';

        $decisionRiskScore =
            (float) (
                $riskContext['final_decision_risk_score']
                ?? $decision->final_decision_risk_score
                ?? 0
            );

        $highRisk =
            ($riskContext['high_risk'] ?? false) === true
            || $decisionRiskScore >= 75;

        /*
        |--------------------------------------------------------------------------
        | Recommendation Builder
        |--------------------------------------------------------------------------
        */

        $recommendations = [];

        $addRecommendation = function (
            string $code,
            string $category,
            string $priority,
            string $recommendedPath,
            string $recommendation,
            string $rationale,
            bool $requiresAuthorizedHumanReview = true
        ) use (&$recommendations) {
            $priorityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                default => 25.0,
            };

            $recommendations[] = [
                'recommendation_code' => $code,
                'recommendation_category' => $category,
                'priority_level' => $priority,
                'priority_weight' => $priorityWeight,
                'recommended_human_governance_path' => $recommendedPath,
                'recommendation' => $recommendation,
                'rationale' => $rationale,
                'requires_authorized_human_review' => $requiresAuthorizedHumanReview,
                'automatic_action_allowed' => false,
                'recommendation_is_final_governance_decision' => false,
            ];
        };

        /*
        |--------------------------------------------------------------------------
        | Source Human Decision Recommendations
        |--------------------------------------------------------------------------
        */

        if (!$sourceFinalHumanDecisionRecorded) {
            $addRecommendation(
                'RECORD_AUTHORIZED_HUMAN_FINAL_STRATEGIC_PLAN_DECISION',
                'HUMAN_DECISION',
                'HIGH',
                'AWAIT_AUTHORIZED_HUMAN_FINAL_DECISION',
                'Record an explicitly authorized human strategic plan decision before unrestricted final governance progression.',
                'The source strategic plan decision has not yet been recorded as a final authorized-human decision.'
            );
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $addRecommendation(
                'COMPLETE_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION',
                'AUTHORIZATION',
                'HIGH',
                'COMPLETE_HUMAN_DECISION_ATTRIBUTION',
                'Ensure the source strategic plan decision is explicitly attributable to an authorized human governance decision-maker.',
                'Authorized-human attribution remains incomplete for the source strategic plan decision.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$sourceGovernanceValidationCompleted) {
            $addRecommendation(
                'COMPLETE_SOURCE_GOVERNANCE_VALIDATION',
                'GOVERNANCE_VALIDATION',
                'HIGH',
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION',
                'Complete the required strategic plan governance-validation process through explicitly authorized human governance.',
                'Source governance validation remains incomplete and restricts unrestricted final governance progression.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Condition Recommendations
        |--------------------------------------------------------------------------
        */

        if ($blockingConditions > 0) {
            $addRecommendation(
                'RESOLVE_BLOCKING_FINAL_GOVERNANCE_CONDITIONS',
                'DECISION_CONDITION',
                'HIGH',
                'RESOLVE_OR_FORMALLY_GOVERN_BLOCKING_CONDITIONS',
                "Resolve or formally govern {$blockingConditions} blocking final governance decision condition(s).",
                'Blocking final governance conditions prevent unrestricted final governance progression.'
            );
        }

        if ($constrainingConditions > 0) {
            $addRecommendation(
                'REDUCE_CONSTRAINING_FINAL_GOVERNANCE_CONDITIONS',
                'DECISION_CONDITION',
                'MODERATE',
                'REDUCE_FINAL_GOVERNANCE_CONSTRAINTS',
                "Reduce or formally govern {$constrainingConditions} constraining final governance decision condition(s).",
                'Constraining conditions reduce final governance decision readiness.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Restriction Recommendations
        |--------------------------------------------------------------------------
        */

        if ($materialRestrictions > 0) {
            $addRecommendation(
                'GOVERN_MATERIAL_FINAL_DECISION_RESTRICTIONS',
                'DECISION_RESTRICTION',
                'HIGH',
                'MAINTAIN_AUTHORIZED_HUMAN_RESTRICTION_GOVERNANCE',
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material final governance decision restriction(s).",
                'Material final governance restrictions remain active.'
            );
        }

        if ($criticalRestrictions > 0 || $criticalOpenConditions > 0) {
            $addRecommendation(
                'IMMEDIATE_CRITICAL_FINAL_GOVERNANCE_REVIEW',
                'CRITICAL_GOVERNANCE',
                'CRITICAL',
                'IMMEDIATE_AUTHORIZED_HUMAN_GOVERNANCE_REVIEW',
                'Perform immediate authorized-human governance review of critical final governance restrictions or conditions.',
                'One or more critical final governance restrictions or conditions remain active.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Recommendation
        |--------------------------------------------------------------------------
        */

        if ($highRisk) {
            $addRecommendation(
                'MAINTAIN_ELEVATED_FINAL_GOVERNANCE_RISK_OVERSIGHT',
                'RISK',
                'HIGH',
                'ELEVATED_AUTHORIZED_HUMAN_RISK_REVIEW',
                'Maintain elevated authorized-human governance oversight while final governance decision risk remains high.',
                "Current final governance decision risk is {$decisionRiskLevel} with score {$decisionRiskScore}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Final Decision Attribution Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$finalGovernanceDecisionRecorded) {
            $addRecommendation(
                'DO_NOT_RECORD_PREMATURE_FINAL_GOVERNANCE_DECISION',
                'FINAL_GOVERNANCE_DECISION',
                'MODERATE',
                'CONTINUE_AUTHORIZED_HUMAN_REVIEW',
                'Do not treat the prepared final governance decision package as a completed final governance decision.',
                'No final governance decision has yet been explicitly recorded by authorized human governance.'
            );
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $addRecommendation(
                'PRESERVE_FINAL_GOVERNANCE_DECISION_AUTHORITY',
                'AUTHORIZATION',
                'MODERATE',
                'AUTHORIZED_HUMAN_FINAL_DECISION_ONLY',
                'Preserve final governance decision authority exclusively for explicitly authorized human governance.',
                'The final governance decision has not yet been attributed to an authorized human decision-maker.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmation Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$finalGovernanceConfirmationCompleted) {
            $addRecommendation(
                'DEFER_FINAL_GOVERNANCE_CONFIRMATION',
                'CONFIRMATION',
                'MODERATE',
                'AWAIT_VALID_FINAL_GOVERNANCE_DECISION',
                'Do not complete final governance confirmation until a valid authorized-human final governance decision exists and confirmation requirements are satisfied.',
                'Final governance confirmation has not been completed and current eligibility does not support confirmation.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Activation Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$activationAllowed) {
            $addRecommendation(
                'DO_NOT_ACTIVATE_STRATEGIC_PLAN',
                'ACTIVATION',
                'HIGH',
                'KEEP_STRATEGIC_PLAN_ACTIVATION_BLOCKED',
                'Do not activate the strategic plan under the current final-governance decision conditions.',
                'Strategic plan activation is not currently eligible under Step 67 governance conditions.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Isolation Recommendation
        |--------------------------------------------------------------------------
        */

        $addRecommendation(
            'PRESERVE_FINAL_GOVERNANCE_AUTHORITY_ISOLATION',
            'GOVERNANCE_CONTROL',
            'ADVISORY',
            'MAINTAIN_HUMAN_GOVERNANCE_AUTHORITY',
            'Keep recommendation intelligence strictly separate from final governance decision, confirmation, activation, and execution authority.',
            'Recommendation intelligence is advisory only and must never become an autonomous governance-action mechanism.'
        );

        /*
        |--------------------------------------------------------------------------
        | Traceability Recommendation
        |--------------------------------------------------------------------------
        */

        $addRecommendation(
            'PRESERVE_FINAL_GOVERNANCE_TRACEABILITY',
            'TRACEABILITY',
            'ADVISORY',
            'MAINTAIN_GOVERNANCE_TRACEABILITY',
            'Preserve traceability across source human decision, governance validation, final governance decision, conditions, restrictions, evidence, confirmation, and governance findings.',
            'End-to-end governance traceability is required for accountable final strategic plan governance.'
        );

        /*
        |--------------------------------------------------------------------------
        | Rank Recommendations
        |--------------------------------------------------------------------------
        */

        usort(
            $recommendations,
            function (array $a, array $b) {
                return $b['priority_weight'] <=> $a['priority_weight'];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Counts
        |--------------------------------------------------------------------------
        */

        $criticalRecommendations =
            count(array_filter(
                $recommendations,
                fn ($item) =>
                    ($item['priority_level'] ?? null) === 'CRITICAL'
            ));

        $highRecommendations =
            count(array_filter(
                $recommendations,
                fn ($item) =>
                    ($item['priority_level'] ?? null) === 'HIGH'
            ));

        $moderateRecommendations =
            count(array_filter(
                $recommendations,
                fn ($item) =>
                    ($item['priority_level'] ?? null) === 'MODERATE'
            ));

        $advisoryRecommendations =
            count(array_filter(
                $recommendations,
                fn ($item) =>
                    ($item['priority_level'] ?? null) === 'ADVISORY'
            ));

        $topRecommendation =
            $recommendations[0] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Recommended Governance Path
        |--------------------------------------------------------------------------
        */

        if ($criticalRecommendations > 0) {
            $topRecommendedGovernancePath =
                'IMMEDIATE_AUTHORIZED_HUMAN_GOVERNANCE_REVIEW';
        } elseif (!$sourceFinalHumanDecisionRecorded) {
            $topRecommendedGovernancePath =
                'AWAIT_AUTHORIZED_HUMAN_FINAL_DECISION';
        } elseif (!$sourceDecisionMadeByAuthorizedHuman) {
            $topRecommendedGovernancePath =
                'COMPLETE_HUMAN_DECISION_ATTRIBUTION';
        } elseif (!$sourceGovernanceValidationCompleted) {
            $topRecommendedGovernancePath =
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION';
        } elseif ($blockingConditions > 0) {
            $topRecommendedGovernancePath =
                'RESOLVE_OR_FORMALLY_GOVERN_BLOCKING_CONDITIONS';
        } elseif ($materialRestrictions > 0) {
            $topRecommendedGovernancePath =
                'MAINTAIN_AUTHORIZED_HUMAN_RESTRICTION_GOVERNANCE';
        } elseif ($highRisk) {
            $topRecommendedGovernancePath =
                'ELEVATED_AUTHORIZED_HUMAN_RISK_REVIEW';
        } elseif (!$finalGovernanceDecisionRecorded) {
            $topRecommendedGovernancePath =
                'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        } elseif (!$confirmationAllowed) {
            $topRecommendedGovernancePath =
                'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_CONFIRMATION_REVIEW';
        } elseif (!$activationAllowed) {
            $topRecommendedGovernancePath =
                'SEPARATE_AUTHORIZED_GOVERNANCE_ACTIVATION_REVIEW';
        } else {
            $topRecommendedGovernancePath =
                'CONTROLLED_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_PROGRESSION';
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation Status
        |--------------------------------------------------------------------------
        */

        if ($criticalRecommendations > 0) {
            $recommendationStatus =
                'CRITICAL_FINAL_GOVERNANCE_DECISION_ATTENTION';
        } elseif (
            $highRecommendations > 0
            || !$unrestrictedProgressionAllowed
            || !$safeForUnrestrictedProgression
        ) {
            $recommendationStatus =
                'ELEVATED_FINAL_GOVERNANCE_DECISION_ATTENTION';
        } elseif ($moderateRecommendations > 0) {
            $recommendationStatus =
                'CONTROLLED_FINAL_GOVERNANCE_DECISION_ATTENTION';
        } else {
            $recommendationStatus =
                'ROUTINE_FINAL_GOVERNANCE_DECISION_OVERSIGHT';
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation Priority Score
        |--------------------------------------------------------------------------
        */

        $priorityWeights =
            array_column($recommendations, 'priority_weight');

        $recommendationPriorityScore =
            !empty($priorityWeights)
                ? round(array_sum($priorityWeights) / count($priorityWeights), 2)
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Governance Readiness Recommendation Context
        |--------------------------------------------------------------------------
        */

        $recommendationState = [
            'recommendation_status' =>
                $recommendationStatus,

            'top_recommended_governance_path' =>
                $topRecommendedGovernancePath,

            'recommendation_priority_score' =>
                $recommendationPriorityScore,

            'total_recommendations' =>
                count($recommendations),

            'critical_recommendations' =>
                $criticalRecommendations,

            'high_recommendations' =>
                $highRecommendations,

            'moderate_recommendations' =>
                $moderateRecommendations,

            'advisory_recommendations' =>
                $advisoryRecommendations,

            'unrestricted_final_governance_progression_allowed' =>
                $unrestrictedProgressionAllowed,

            'safe_for_authorized_human_review' =>
                $safeForAuthorizedHumanReview,

            'safe_for_unrestricted_progression' =>
                $safeForUnrestrictedProgression,

            'final_governance_confirmation_allowed' =>
                $confirmationAllowed,

            'strategic_plan_activation_allowed' =>
                $activationAllowed,

            'human_management_attention_required' =>
                !$unrestrictedProgressionAllowed,

            'immediate_human_intervention_required' =>
                $criticalRecommendations > 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Summary
        |--------------------------------------------------------------------------
        */

        $recommendationSummary = [
            'recommendation_status' =>
                $recommendationStatus,

            'total_recommendations' =>
                count($recommendations),

            'critical_recommendations' =>
                $criticalRecommendations,

            'high_recommendations' =>
                $highRecommendations,

            'moderate_recommendations' =>
                $moderateRecommendations,

            'advisory_recommendations' =>
                $advisoryRecommendations,

            'top_recommendation_code' =>
                $topRecommendation['recommendation_code'] ?? null,

            'top_recommended_governance_path' =>
                $topRecommendedGovernancePath,

            'recommendation_priority_score' =>
                $recommendationPriorityScore,

            'final_governance_decision_eligibility_score' =>
                $eligibilityScore,

            'final_governance_decision_safety_score' =>
                $safetyScore,

            'decision_risk_score' =>
                $decisionRiskScore,

            'blocking_conditions' =>
                $blockingConditions,

            'material_restrictions' =>
                $materialRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Decision Path Context
        |--------------------------------------------------------------------------
        */

        $decisionPathContext = [
            'authorized_human_review_path_available' =>
                true,

            'unrestricted_final_governance_path_available' =>
                $unrestrictedProgressionAllowed,

            'final_governance_confirmation_path_available' =>
                $confirmationAllowed,

            'strategic_plan_activation_path_available' =>
                $activationAllowed,

            'recommended_path' =>
                $topRecommendedGovernancePath,

            'recommended_path_is_final_governance_decision' =>
                false,

            'recommended_path_is_activation_authorization' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $recommendationFindings = [
            "Final governance decision recommendation intelligence is based on final governance decision record {$decision->id}.",

            "Current recommendation status is {$recommendationStatus}.",

            'Current top recommendation is '
                .($topRecommendation['recommendation_code'] ?? 'NONE').'.',

            "Current top recommended human-governance path is {$topRecommendedGovernancePath}.",

            'Current recommendation priority score is '
                .$recommendationPriorityScore.'.',

            count($recommendations)
                .' final governance recommendation(s) are represented.',

            "{$criticalRecommendations} critical recommendation(s) are represented.",

            "{$highRecommendations} high-priority recommendation(s) are represented.",

            "{$moderateRecommendations} moderate recommendation(s) are represented.",

            "{$advisoryRecommendations} advisory recommendation(s) are represented.",

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$materialRestrictions} material final governance decision restriction(s) remain active.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            "Current final governance decision eligibility score is {$eligibilityScore}.",

            "Current final governance decision safety status is {$safetyStatus} with score {$safetyScore}.",

            "Current final governance decision risk is {$decisionRiskLevel} with score {$decisionRiskScore}.",

            'Source final authorized-human strategic plan decision recorded is '
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

            'Final governance recommendations are advisory and do not constitute approval, rejection, conditional approval, deferral, risk acceptance, confirmation, activation, or any final governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            array_map(
                fn ($item) => $item['recommendation'],
                array_slice($recommendations, 0, 10)
            );

        $managementPriorities[] =
            'Keep final governance recommendation intelligence strictly separated from final governance decision, confirmation, activation, and execution authority.';

        $managementPriorities[] =
            'Preserve human governance authority, decision attribution, governance-validation attribution, confirmation attribution, evidence quality, traceability, safety controls, and authority separation throughout final governance progression.';

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
                'GOVERNANCE_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

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
            | Recommendation Intelligence
            |--------------------------------------------------------------------------
            */

            'recommendation_state' =>
                $recommendationState,

            'recommendation_summary' =>
                $recommendationSummary,

            'top_recommendation' =>
                $topRecommendation,

            'recommendations' =>
                $recommendations,

            'decision_path_context' =>
                $decisionPathContext,

            /*
            |--------------------------------------------------------------------------
            | Eligibility / Safety Context
            |--------------------------------------------------------------------------
            */

            'final_decision_eligibility_context' =>
                $eligibilityState,

            'final_decision_safety_context' =>
                $safetyState,

            'dominant_eligibility_restriction' =>
                $dominantEligibilityRestriction,

            'dominant_safety_restriction' =>
                $dominantSafetyRestriction,

            /*
            |--------------------------------------------------------------------------
            | Condition / Restriction Context
            |--------------------------------------------------------------------------
            */

            'condition_context' => [
                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'condition_pressure_score' =>
                    $conditionPressureScore,

                'dominant_decision_condition' =>
                    $dominantDecisionCondition,
            ],

            'restriction_context' => [
                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'restriction_pressure_score' =>
                    $restrictionPressureScore,

                'dominant_decision_restriction' =>
                    $dominantDecisionRestriction,
            ],

            'resolution_context' => [
                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,
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

            'recommendation_findings' =>
                $recommendationFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 67.6 Guardrails
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_recommendation_guardrails' => [
                'final_governance_decision_recommendation_intelligence_enabled' => true,

                'recommendation_intelligence_is_final_governance_decision' => false,
                'recommendation_intelligence_makes_final_governance_decision' => false,
                'recommendation_intelligence_records_final_governance_decision' => false,
                'recommendation_intelligence_confirms_final_governance_decision' => false,

                'recommendation_intelligence_approves_strategic_plan' => false,
                'recommendation_intelligence_rejects_strategic_plan' => false,
                'recommendation_intelligence_conditionally_approves_strategic_plan' => false,
                'recommendation_intelligence_defers_strategic_plan' => false,
                'recommendation_intelligence_accepts_governance_risk' => false,
                'recommendation_intelligence_activates_strategic_plan' => false,

                'recommended_path_is_final_governance_decision' => false,
                'recommended_path_is_confirmation' => false,
                'recommended_path_is_activation_authorization' => false,

                'approval_recommendation_is_approval' => false,
                'rejection_recommendation_is_rejection' => false,
                'conditional_approval_recommendation_is_approval' => false,
                'deferral_recommendation_is_deferral' => false,
                'risk_acceptance_recommendation_accepts_risk' => false,
                'activation_recommendation_activates_strategic_plan' => false,

                'recommendation_intelligence_resolves_conditions' => false,
                'recommendation_intelligence_waives_conditions' => false,
                'recommendation_intelligence_removes_restrictions' => false,
                'recommendation_intelligence_downgrades_restrictions' => false,
                'recommendation_intelligence_resolves_dependencies' => false,
                'recommendation_intelligence_validates_evidence' => false,

                'recommendation_intelligence_changes_source_human_decision' => false,
                'recommendation_intelligence_changes_governance_validation' => false,
                'recommendation_intelligence_changes_final_governance_decision_status' => false,
                'recommendation_intelligence_changes_final_governance_decision' => false,
                'recommendation_intelligence_changes_final_governance_outcome' => false,
                'recommendation_intelligence_changes_plan_status' => false,
                'recommendation_intelligence_changes_action_state' => false,
                'recommendation_intelligence_changes_priority' => false,
                'recommendation_intelligence_changes_eligibility' => false,

                'recommendation_priority_authorizes_final_decision' => false,
                'recommendation_priority_authorizes_approval' => false,
                'recommendation_priority_authorizes_rejection' => false,
                'recommendation_priority_authorizes_activation' => false,

                'eligibility_score_authorizes_final_decision' => false,
                'safety_score_authorizes_final_decision' => false,
                'decision_risk_score_authorizes_final_decision' => false,
                'condition_resolution_score_authorizes_final_decision' => false,
                'evidence_resolution_score_authorizes_final_decision' => false,
                'combined_resolution_score_authorizes_final_decision' => false,

                'recommendation_intelligence_authorizes_ai_change' => false,
                'recommendation_intelligence_authorizes_execution' => false,
                'recommendation_intelligence_authorizes_deployment' => false,
                'recommendation_intelligence_authorizes_rollback' => false,
                'recommendation_intelligence_authorizes_clinical_action' => false,

                'recommendation_intelligence_overrides_human_review' => false,
                'recommendation_intelligence_overrides_governance_validation' => false,
                'recommendation_intelligence_overrides_final_human_decision' => false,
                'recommendation_intelligence_overrides_final_governance_decision' => false,
                'recommendation_intelligence_overrides_evidence_requirements' => false,

                'automatic_final_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_conditional_approval_allowed' => false,
                'automatic_deferral_allowed' => false,
                'automatic_risk_acceptance_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_restriction_removal_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'final_governance_decision_authority_reserved_for_authorized_human' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,
                'authorized_human_final_decision_required' => true,

                'message' =>
                    'Step 67.6 final governance decision recommendation intelligence converts final-governance decision state, unresolved conditions, material restrictions, source human-decision attribution, source governance-validation state, eligibility, safety restrictions, readiness, resolution scores, pressure, and decision risk into ranked advisory recommendations for explicitly authorized human governance. Recommendations and recommended governance paths do not constitute or record a final governance decision, approval, rejection, conditional approval, deferral, risk acceptance, confirmation, strategic-plan activation, condition resolution, restriction removal, evidence validation, governance validation, AI modification, execution, deployment, rollback, or clinical action. Final governance decision authority remains reserved exclusively for explicitly authorized human governance.',
            ],
        ];
    }
}