<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationRecommendationIntelligenceEngine
{
    /**
     * Step 70.6
     *
     * Controlled Activation Execution Authorization Recommendation Intelligence.
     *
     * This service generates advisory governance recommendations only.
     * It does not authorize execution, complete execution authorization,
     * resolve conditions, remove restrictions, or execute controlled activation.
     */
    public function analyze(?int $executionAuthorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 70 Record
        |--------------------------------------------------------------------------
        */

        $authorization = $executionAuthorizationId
            ? AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::find(
                $executionAuthorizationId
            )
            : AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'analysis_completed' => false,

                'status' =>
                    'NO_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_AVAILABLE',

                'message' =>
                    'No strategic plan controlled activation execution authorization record is available for recommendation intelligence analysis.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 70.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $state =
            app(
                AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationStateIntelligenceEngine::class
            )->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,

                'status' =>
                    'STEP_70_EXECUTION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',

                'message' =>
                    'Step 70.6 recommendation intelligence requires Step 70.3 execution authorization state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 70.4 Condition / Restriction Intelligence
        |--------------------------------------------------------------------------
        */

        $conditionRestriction =
            app(
                AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationConditionRestrictionIntelligenceEngine::class
            )->analyze($authorization->id);

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,

                'status' =>
                    'STEP_70_EXECUTION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',

                'message' =>
                    'Step 70.6 recommendation intelligence requires Step 70.4 condition and restriction intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 70.5 Eligibility / Safety Intelligence
        |--------------------------------------------------------------------------
        */

        $eligibilitySafety =
            app(
                AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationEligibilitySafetyIntelligenceEngine::class
            )->analyze($authorization->id);

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,

                'status' =>
                    'STEP_70_EXECUTION_AUTHORIZATION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',

                'message' =>
                    'Step 70.6 recommendation intelligence requires Step 70.5 execution authorization eligibility and safety intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Contexts
        |--------------------------------------------------------------------------
        */

        $executionState =
            $state['execution_authorization_state']
            ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state']
            ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary']
            ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary']
            ?? [];

        $eligibilityState =
            $eligibilitySafety['execution_eligibility_state']
            ?? [];

        $safetyState =
            $eligibilitySafety['execution_safety_state']
            ?? [];

        $sourceAuthorizationContext =
            $state['source_activation_authorization_context']
            ?? [];

        $executionAuthorizationContext =
            $state['execution_authorization_context']
            ?? [];

        $actualExecutionContext =
            $state['actual_execution_context']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 State
        |--------------------------------------------------------------------------
        */

        $sourceActivationAuthorizationDecisionRecorded =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_decision_recorded'
                ]
                ?? false
            );

        $sourceActivationAuthorizationMadeByAuthorizedHuman =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_made_by_authorized_human'
                ]
                ?? false
            );

        $sourceActivationAuthorizationAttributionComplete =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_attribution_complete'
                ]
                ?? false
            );

        $sourceControlledActivationAuthorizationCompleted =
            (bool) (
                $sourceAuthorizationContext[
                    'source_controlled_activation_authorization_completed'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Step 70 Authorization State
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationDecisionRecorded =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_decision_recorded'
                ]
                ?? false
            );

        $executionAuthorizationMadeByAuthorizedHuman =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_made_by_authorized_human'
                ]
                ?? false
            );

        $executionAuthorizationAttributionComplete =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_attribution_complete'
                ]
                ?? false
            );

        $executionAuthorizationCompleted =
            (bool) (
                $executionAuthorizationContext[
                    'controlled_activation_execution_authorization_completed'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Actual Execution State
        |--------------------------------------------------------------------------
        */

        $controlledActivationExecuted =
            (bool) (
                $actualExecutionContext[
                    'controlled_activation_executed'
                ]
                ?? false
            );

        $actualExecutionAttributionComplete =
            (bool) (
                $actualExecutionContext[
                    'actual_execution_attribution_complete'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $blockingExecutionAuthorizationConditions =
            (int) (
                $conditionSummary[
                    'blocking_execution_authorization_conditions'
                ]
                ?? 0
            );

        $blockingActualExecutionConditions =
            (int) (
                $conditionSummary[
                    'blocking_actual_execution_conditions'
                ]
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary[
                    'critical_open_conditions'
                ]
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $restrictionSummary[
                    'material_restrictions'
                ]
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary[
                    'critical_restrictions'
                ]
                ?? 0
            );

        $outstandingEvidenceItems =
            (int) (
                $safetyState[
                    'outstanding_evidence_items'
                ]
                ?? 0
            );

        $blockingExecutionEvidenceItems =
            (int) (
                $safetyState[
                    'blocking_execution_evidence_items'
                ]
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores / Status
        |--------------------------------------------------------------------------
        */

        $executionReadinessScore =
            $this->score(
                $executionState[
                    'execution_readiness_score'
                ]
                ?? $authorization->execution_readiness_score
            );

        $executionRiskScore =
            $this->score(
                $executionState[
                    'execution_risk_score'
                ]
                ?? $authorization->execution_risk_score
            );

        $executionAuthorizationEligibilityScore =
            $this->score(
                $eligibilityState[
                    'execution_authorization_eligibility_score'
                ]
                ?? 0
            );

        $executionAuthorizationSafetyScore =
            $this->score(
                $safetyState[
                    'execution_authorization_safety_score'
                ]
                ?? 0
            );

        $combinedResolutionScore =
            $this->score(
                $conditionState[
                    'combined_resolution_score'
                ]
                ?? $authorization->combined_resolution_score
            );

        $combinedPressureScore =
            $this->score(
                $conditionState[
                    'combined_condition_restriction_pressure_score'
                ]
                ?? $authorization->combined_condition_restriction_pressure_score
            );

        $governanceIntegrityIntact =
            (bool) (
                $safetyState[
                    'governance_integrity_intact'
                ]
                ?? false
            );

        $executionAuthorizationProgressionBlocked =
            (bool) (
                $eligibilityState[
                    'execution_authorization_progression_blocked'
                ]
                ?? true
            );

        $actualExecutionProgressionBlocked =
            (bool) (
                $eligibilityState[
                    'actual_execution_progression_blocked'
                ]
                ?? true
            );

        /*
        |--------------------------------------------------------------------------
        | Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Source Activation Authorization Decision
        |--------------------------------------------------------------------------
        */

        if (!$sourceActivationAuthorizationDecisionRecorded) {
            $recommendations[] =
                $this->recommendation(
                    'RECORD_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION',
                    'SOURCE_ACTIVATION_AUTHORIZATION',
                    'CRITICAL',
                    100.0,
                    'Record the required controlled activation authorization decision through explicitly authorized human governance before execution authorization progresses.',
                    'AWAIT_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Source Human Authorization
        |--------------------------------------------------------------------------
        */

        if (!$sourceActivationAuthorizationMadeByAuthorizedHuman) {
            $recommendations[] =
                $this->recommendation(
                    'ESTABLISH_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION',
                    'SOURCE_AUTHORIZATION',
                    'CRITICAL',
                    99.0,
                    'Ensure the controlled activation authorization decision is made by an explicitly authorized human governance authority.',
                    'AWAIT_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Source Attribution
        |--------------------------------------------------------------------------
        */

        if (!$sourceActivationAuthorizationAttributionComplete) {
            $recommendations[] =
                $this->recommendation(
                    'COMPLETE_CONTROLLED_ACTIVATION_AUTHORIZATION_ATTRIBUTION',
                    'SOURCE_AUTHORIZATION_ATTRIBUTION',
                    'CRITICAL',
                    98.0,
                    'Complete controlled activation authorization attribution, including authorized human identity, governance role, and authorization timestamp.',
                    'COMPLETE_CONTROLLED_ACTIVATION_AUTHORIZATION_ATTRIBUTION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Complete Step 69 Authorization
        |--------------------------------------------------------------------------
        */

        if (!$sourceControlledActivationAuthorizationCompleted) {
            $recommendations[] =
                $this->recommendation(
                    'COMPLETE_CONTROLLED_ACTIVATION_AUTHORIZATION',
                    'SOURCE_AUTHORIZATION_COMPLETION',
                    'CRITICAL',
                    97.0,
                    'Complete the controlled activation authorization process through explicitly authorized human governance before execution authorization completion.',
                    'COMPLETE_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Critical Conditions
        |--------------------------------------------------------------------------
        */

        if ($criticalOpenConditions > 0) {
            $recommendations[] =
                $this->recommendation(
                    'RESOLVE_CRITICAL_EXECUTION_AUTHORIZATION_CONDITIONS',
                    'EXECUTION_CONDITION',
                    'CRITICAL',
                    96.0,
                    "Resolve or formally govern {$criticalOpenConditions} critical controlled activation execution authorization condition(s) through explicitly authorized human governance.",
                    'AUTHORIZED_HUMAN_CRITICAL_EXECUTION_CONDITION_RESOLUTION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Critical Restrictions
        |--------------------------------------------------------------------------
        */

        if ($criticalRestrictions > 0) {
            $recommendations[] =
                $this->recommendation(
                    'ADDRESS_CRITICAL_EXECUTION_AUTHORIZATION_RESTRICTIONS',
                    'EXECUTION_RESTRICTION',
                    'CRITICAL',
                    95.0,
                    "Maintain immediate authorized-human governance treatment for {$criticalRestrictions} critical controlled activation execution restriction(s).",
                    'AUTHORIZED_HUMAN_CRITICAL_EXECUTION_RESTRICTION_GOVERNANCE'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Authorization Blockers
        |--------------------------------------------------------------------------
        */

        if ($blockingExecutionAuthorizationConditions > 0) {
            $recommendations[] =
                $this->recommendation(
                    'RESOLVE_EXECUTION_AUTHORIZATION_BLOCKERS',
                    'EXECUTION_AUTHORIZATION_BLOCKER',
                    'HIGH',
                    90.0,
                    "Resolve or formally govern {$blockingExecutionAuthorizationConditions} condition(s) currently blocking execution authorization progression.",
                    'RESOLVE_EXECUTION_AUTHORIZATION_BLOCKING_REQUIREMENTS'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Actual Execution Blockers
        |--------------------------------------------------------------------------
        */

        if ($blockingActualExecutionConditions > 0) {
            $recommendations[] =
                $this->recommendation(
                    'MAINTAIN_ACTUAL_EXECUTION_PROHIBITION',
                    'ACTUAL_EXECUTION_SAFETY',
                    'HIGH',
                    89.0,
                    "Keep actual controlled activation execution prohibited while {$blockingActualExecutionConditions} execution-blocking condition(s) remain.",
                    'KEEP_ACTUAL_EXECUTION_BLOCKED'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Material Restrictions
        |--------------------------------------------------------------------------
        */

        if ($materialRestrictions > 0) {
            $recommendations[] =
                $this->recommendation(
                    'GOVERN_MATERIAL_EXECUTION_RESTRICTIONS',
                    'EXECUTION_RESTRICTION',
                    'HIGH',
                    88.0,
                    "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material controlled activation execution restriction(s).",
                    'AUTHORIZED_HUMAN_MATERIAL_EXECUTION_RESTRICTION_GOVERNANCE'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Critical Risk
        |--------------------------------------------------------------------------
        */

        if ($executionRiskScore >= 90) {
            $recommendations[] =
                $this->recommendation(
                    'MAINTAIN_CRITICAL_EXECUTION_RISK_OVERSIGHT',
                    'EXECUTION_RISK',
                    'CRITICAL',
                    94.0,
                    "Maintain critical authorized-human governance oversight while controlled activation execution risk remains {$authorization->execution_risk_level} with score {$executionRiskScore}.",
                    'CRITICAL_AUTHORIZED_HUMAN_EXECUTION_RISK_REVIEW'
                );
        } elseif ($executionRiskScore >= 75) {
            $recommendations[] =
                $this->recommendation(
                    'MAINTAIN_HIGH_EXECUTION_RISK_OVERSIGHT',
                    'EXECUTION_RISK',
                    'HIGH',
                    85.0,
                    "Maintain elevated authorized-human governance oversight while controlled activation execution risk remains {$authorization->execution_risk_level} with score {$executionRiskScore}.",
                    'HIGH_AUTHORIZED_HUMAN_EXECUTION_RISK_REVIEW'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Outstanding Evidence
        |--------------------------------------------------------------------------
        */

        if ($outstandingEvidenceItems > 0) {
            $recommendations[] =
                $this->recommendation(
                    'RESOLVE_OUTSTANDING_EXECUTION_EVIDENCE',
                    'EVIDENCE',
                    $blockingExecutionEvidenceItems > 0
                        ? 'CRITICAL'
                        : 'HIGH',
                    $blockingExecutionEvidenceItems > 0
                        ? 93.0
                        : 82.0,
                    "Review and resolve {$outstandingEvidenceItems} outstanding controlled activation execution evidence item(s) through explicitly authorized human governance.",
                    'RESOLVE_EXECUTION_AUTHORIZATION_EVIDENCE'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Execution Authorization Decision
        |--------------------------------------------------------------------------
        */

        if (!$executionAuthorizationDecisionRecorded) {
            $recommendations[] =
                $this->recommendation(
                    'RECORD_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION',
                    'EXECUTION_AUTHORIZATION',
                    'HIGH',
                    87.0,
                    'A separate controlled activation execution authorization decision must be recorded through explicitly authorized human governance when prerequisite requirements are satisfied.',
                    'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Execution Authorization Attribution
        |--------------------------------------------------------------------------
        */

        if (
            $executionAuthorizationDecisionRecorded
            && !$executionAuthorizationMadeByAuthorizedHuman
        ) {
            $recommendations[] =
                $this->recommendation(
                    'ESTABLISH_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION',
                    'EXECUTION_AUTHORIZATION_ATTRIBUTION',
                    'CRITICAL',
                    96.0,
                    'Ensure the controlled activation execution authorization decision is explicitly made by an authorized human governance authority.',
                    'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_ATTRIBUTION'
                );
        }

        if (
            $executionAuthorizationDecisionRecorded
            && !$executionAuthorizationAttributionComplete
        ) {
            $recommendations[] =
                $this->recommendation(
                    'COMPLETE_EXECUTION_AUTHORIZATION_ATTRIBUTION',
                    'EXECUTION_AUTHORIZATION_ATTRIBUTION',
                    'CRITICAL',
                    95.0,
                    'Complete execution authorization attribution, including authorizer identity, governance role, and execution authorization timestamp.',
                    'COMPLETE_EXECUTION_AUTHORIZATION_ATTRIBUTION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 14. Completion
        |--------------------------------------------------------------------------
        */

        if (!$executionAuthorizationCompleted) {
            $recommendations[] =
                $this->recommendation(
                    'KEEP_EXECUTION_AUTHORIZATION_INCOMPLETE',
                    'EXECUTION_AUTHORIZATION_COMPLETION',
                    'HIGH',
                    86.0,
                    'Keep controlled activation execution authorization incomplete until all prerequisite governance requirements are satisfied.',
                    'MAINTAIN_PENDING_EXECUTION_AUTHORIZATION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 15. Actual Execution Separation
        |--------------------------------------------------------------------------
        */

        if (!$controlledActivationExecuted) {
            $recommendations[] =
                $this->recommendation(
                    'PRESERVE_EXECUTION_AUTHORIZATION_EXECUTION_SEPARATION',
                    'AUTHORITY_SEPARATION',
                    'HIGH',
                    84.0,
                    'Preserve strict separation between controlled activation execution authorization and actual controlled activation execution.',
                    'KEEP_ACTUAL_EXECUTION_SEPARATE'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 16. Execution Attribution
        |--------------------------------------------------------------------------
        */

        if (!$actualExecutionAttributionComplete) {
            $recommendations[] =
                $this->recommendation(
                    'PRESERVE_SEPARATE_ACTUAL_EXECUTION_ATTRIBUTION',
                    'EXECUTION_ATTRIBUTION',
                    'ADVISORY',
                    60.0,
                    'Require complete and separately governed executor attribution if controlled activation execution occurs in a future execution layer.',
                    'REQUIRE_SEPARATE_ACTUAL_EXECUTION_ATTRIBUTION'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 17. Safety Score
        |--------------------------------------------------------------------------
        */

        if ($executionAuthorizationSafetyScore <= 25) {
            $recommendations[] =
                $this->recommendation(
                    'MAINTAIN_EXECUTION_AUTHORIZATION_SAFETY_BLOCK',
                    'SAFETY',
                    'CRITICAL',
                    92.0,
                    "Maintain controlled execution authorization restrictions while the execution authorization safety score remains {$executionAuthorizationSafetyScore}.",
                    'MAINTAIN_CONTROLLED_EXECUTION_SAFETY_RESTRICTIONS'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 18. Eligibility
        |--------------------------------------------------------------------------
        */

        if (
            (
                $eligibilityState[
                    'controlled_activation_execution_authorization_completion_eligibility'
                ]
                ?? null
            )
            ===
            'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_COMPLETION'
        ) {
            $recommendations[] =
                $this->recommendation(
                    'DO_NOT_COMPLETE_EXECUTION_AUTHORIZATION',
                    'ELIGIBILITY',
                    'HIGH',
                    91.0,
                    'Do not complete controlled activation execution authorization while completion eligibility remains unavailable.',
                    'MAINTAIN_EXECUTION_AUTHORIZATION_COMPLETION_BLOCK'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 19. Governance Integrity
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $recommendations[] =
                $this->recommendation(
                    'RESTORE_EXECUTION_GOVERNANCE_INTEGRITY',
                    'GOVERNANCE_INTEGRITY',
                    'CRITICAL',
                    100.0,
                    'Restore controlled activation execution governance integrity before any further execution authorization progression.',
                    'RESTORE_GOVERNANCE_INTEGRITY'
                );
        } else {
            $recommendations[] =
                $this->recommendation(
                    'PRESERVE_EXECUTION_GOVERNANCE_INTEGRITY',
                    'GOVERNANCE_INTEGRITY',
                    'ADVISORY',
                    55.0,
                    'Preserve execution governance integrity, human authority, traceability, safety isolation, and attribution controls throughout progression.',
                    'PRESERVE_EXECUTION_GOVERNANCE_CONTROLS'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Recommendations
        |--------------------------------------------------------------------------
        */

        usort(
            $recommendations,
            function (array $a, array $b): int {
                $scoreComparison =
                    ($b['priority_score'] ?? 0)
                    <=>
                    ($a['priority_score'] ?? 0);

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return
                    $this->priorityRank(
                        $b['priority_level'] ?? 'ADVISORY'
                    )
                    <=>
                    $this->priorityRank(
                        $a['priority_level'] ?? 'ADVISORY'
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Counts
        |--------------------------------------------------------------------------
        */

        $criticalRecommendations =
            count(
                array_filter(
                    $recommendations,
                    fn (array $recommendation): bool =>
                        ($recommendation['priority_level'] ?? null)
                        === 'CRITICAL'
                )
            );

        $highRecommendations =
            count(
                array_filter(
                    $recommendations,
                    fn (array $recommendation): bool =>
                        ($recommendation['priority_level'] ?? null)
                        === 'HIGH'
                )
            );

        $moderateRecommendations =
            count(
                array_filter(
                    $recommendations,
                    fn (array $recommendation): bool =>
                        ($recommendation['priority_level'] ?? null)
                        === 'MODERATE'
                )
            );

        $advisoryRecommendations =
            count(
                array_filter(
                    $recommendations,
                    fn (array $recommendation): bool =>
                        ($recommendation['priority_level'] ?? null)
                        === 'ADVISORY'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Top Recommendation
        |--------------------------------------------------------------------------
        */

        $topRecommendation =
            $recommendations[0]
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Recommendation Status
        |--------------------------------------------------------------------------
        */

        $recommendationStatus =
            match (true) {
                $criticalRecommendations > 0 =>
                    'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_RECOMMENDATIONS',

                $highRecommendations > 0 =>
                    'ELEVATED_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_RECOMMENDATIONS',

                $moderateRecommendations > 0 =>
                    'MODERATE_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_RECOMMENDATIONS',

                default =>
                    'CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_ADVISORY_RECOMMENDATIONS',
            };

        /*
        |--------------------------------------------------------------------------
        | Recommended Path
        |--------------------------------------------------------------------------
        */

        $recommendedExecutionPath =
            $topRecommendation['recommended_path']
            ?? 'AUTHORIZED_HUMAN_EXECUTION_GOVERNANCE_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Recommendation State
        |--------------------------------------------------------------------------
        */

        $recommendationState = [
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
                $topRecommendation['recommendation_code']
                ?? null,

            'top_recommendation_priority_level' =>
                $topRecommendation['priority_level']
                ?? null,

            'top_recommendation_priority_score' =>
                $topRecommendation['priority_score']
                ?? null,

            'top_recommended_execution_path' =>
                $recommendedExecutionPath,

            'execution_authorization_progression_blocked' =>
                $executionAuthorizationProgressionBlocked,

            'actual_execution_progression_blocked' =>
                $actualExecutionProgressionBlocked,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'human_management_attention_required' =>
                (bool) (
                    $safetyState[
                        'human_management_attention_required'
                    ]
                    ?? false
                ),

            'immediate_human_intervention_required' =>
                (bool) (
                    $safetyState[
                        'immediate_human_intervention_required'
                    ]
                    ?? false
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Context
        |--------------------------------------------------------------------------
        */

        $recommendationContext = [
            'execution_authorization_state' =>
                $executionState[
                    'execution_authorization_state'
                ]
                ?? null,

            'execution_readiness' =>
                $executionState[
                    'execution_readiness'
                ]
                ?? $authorization->execution_readiness,

            'execution_readiness_score' =>
                $executionReadinessScore,

            'execution_risk_level' =>
                $executionState[
                    'execution_risk_level'
                ]
                ?? $authorization->execution_risk_level,

            'execution_risk_score' =>
                $executionRiskScore,

            'execution_authorization_eligibility_score' =>
                $executionAuthorizationEligibilityScore,

            'execution_authorization_safety_status' =>
                $safetyState[
                    'execution_authorization_safety_status'
                ]
                ?? null,

            'execution_authorization_safety_level' =>
                $safetyState[
                    'execution_authorization_safety_level'
                ]
                ?? null,

            'execution_authorization_safety_score' =>
                $executionAuthorizationSafetyScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,

            'blocking_execution_authorization_conditions' =>
                $blockingExecutionAuthorizationConditions,

            'blocking_actual_execution_conditions' =>
                $blockingActualExecutionConditions,

            'critical_open_conditions' =>
                $criticalOpenConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'outstanding_evidence_items' =>
                $outstandingEvidenceItems,

            'blocking_execution_evidence_items' =>
                $blockingExecutionEvidenceItems,

            'source_activation_authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'source_activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'source_activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'source_controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'execution_authorization_decision_recorded' =>
                $executionAuthorizationDecisionRecorded,

            'execution_authorization_made_by_authorized_human' =>
                $executionAuthorizationMadeByAuthorizedHuman,

            'execution_authorization_attribution_complete' =>
                $executionAuthorizationAttributionComplete,

            'controlled_activation_execution_authorization_completed' =>
                $executionAuthorizationCompleted,

            'controlled_activation_executed' =>
                $controlledActivationExecuted,

            'actual_execution_attribution_complete' =>
                $actualExecutionAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $recommendationFindings = [
            "Controlled activation execution authorization recommendation intelligence is based on Step 70 execution authorization record {$authorization->id}.",

            "Current recommendation status is {$recommendationStatus}.",

            count($recommendations)
                .' controlled activation execution authorization recommendation(s) are currently represented.',

            "{$criticalRecommendations} critical recommendation(s) are currently represented.",

            "{$highRecommendations} high recommendation(s) are currently represented.",

            "{$moderateRecommendations} moderate recommendation(s) are currently represented.",

            "{$advisoryRecommendations} advisory recommendation(s) are currently represented.",

            'Current top recommendation is '
                .(
                    $topRecommendation['recommendation_code']
                    ?? 'NONE'
                ).'.',

            'Current top recommended execution path is '
                .$recommendedExecutionPath.'.',

            "Current execution readiness score is {$executionReadinessScore}.",

            "Current execution risk score is {$executionRiskScore}.",

            "Current execution authorization eligibility score is {$executionAuthorizationEligibilityScore}.",

            "Current execution authorization safety score is {$executionAuthorizationSafetyScore}.",

            "Current combined resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            "{$blockingExecutionAuthorizationConditions} execution authorization blocker(s) remain.",

            "{$blockingActualExecutionConditions} actual execution blocker(s) remain.",

            "{$criticalOpenConditions} critical condition(s) remain open.",

            "{$materialRestrictions} material restriction(s) remain.",

            "{$criticalRestrictions} critical restriction(s) remain.",

            'Governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            'Execution authorization progression remains '
                .($executionAuthorizationProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Actual controlled activation execution progression remains '
                .($actualExecutionProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Recommendation intelligence remains advisory and does not authorize execution authorization or actual controlled activation execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            array_values(
                array_unique(
                    array_map(
                        fn (array $recommendation): string =>
                            $recommendation['recommendation'],
                        $recommendations
                    )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_authorization_id' =>
                $authorization->id,

            'execution_authorization_code' =>
                $authorization->execution_authorization_code,

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->strategic_plan_controlled_activation_authorization_id,

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

            'recommendation_state' =>
                $recommendationState,

            'top_recommendation' =>
                $topRecommendation,

            'recommendations' =>
                $recommendations,

            'recommendation_context' =>
                $recommendationContext,

            'execution_state_context' =>
                $executionState,

            'condition_restriction_context' =>
                $conditionState,

            'execution_eligibility_context' =>
                $eligibilityState,

            'execution_safety_context' =>
                $safetyState,

            'source_activation_authorization_context' =>
                $sourceAuthorizationContext,

            'execution_authorization_context' =>
                $executionAuthorizationContext,

            'actual_execution_context' =>
                $actualExecutionContext,

            'recommendation_findings' =>
                $recommendationFindings,

            'management_priorities' =>
                $managementPriorities,

            'execution_authorization_recommendation_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation Builder
    |--------------------------------------------------------------------------
    */

    private function recommendation(
        string $code,
        string $type,
        string $priorityLevel,
        float $priorityScore,
        string $recommendation,
        string $recommendedPath
    ): array {
        return [
            'recommendation_code' =>
                $code,

            'recommendation_type' =>
                $type,

            'priority_level' =>
                $priorityLevel,

            'priority_score' =>
                $this->score($priorityScore),

            'recommendation' =>
                $recommendation,

            'recommended_path' =>
                $recommendedPath,

            'requires_authorized_human_governance' =>
                true,

            'automatic_action_allowed' =>
                false,

            'authorizes_execution_authorization' =>
                false,

            'authorizes_actual_execution' =>
                false,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Priority Rank
    |--------------------------------------------------------------------------
    */

    private function priorityRank(string $priority): int
    {
        return match (strtoupper($priority)) {
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
            default => 0,
        };
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
    | Step 70.6 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_recommendation_intelligence_enabled' =>
                true,

            'recommendation_intelligence_is_execution_authorization' =>
                false,

            'recommendation_intelligence_is_actual_execution' =>
                false,

            'recommendation_intelligence_makes_execution_authorization_decision' =>
                false,

            'recommendation_intelligence_records_execution_authorization_decision' =>
                false,

            'recommendation_intelligence_completes_execution_authorization' =>
                false,

            'recommendation_intelligence_executes_controlled_activation' =>
                false,

            'recommendation_intelligence_activates_strategic_plan' =>
                false,

            'recommendation_intelligence_changes_source_activation_authorization' =>
                false,

            'recommendation_intelligence_changes_execution_authorization_status' =>
                false,

            'recommendation_intelligence_changes_execution_authorization_decision' =>
                false,

            'recommendation_intelligence_changes_execution_authorization_outcome' =>
                false,

            'recommendation_intelligence_changes_execution_status' =>
                false,

            'recommendation_intelligence_resolves_conditions' =>
                false,

            'recommendation_intelligence_waives_conditions' =>
                false,

            'recommendation_intelligence_removes_restrictions' =>
                false,

            'recommendation_intelligence_downgrades_restrictions' =>
                false,

            'recommendation_intelligence_validates_evidence' =>
                false,

            'recommendation_priority_authorizes_execution_authorization' =>
                false,

            'recommendation_priority_authorizes_actual_execution' =>
                false,

            'recommendation_score_authorizes_execution_authorization' =>
                false,

            'recommendation_score_authorizes_actual_execution' =>
                false,

            'recommended_path_authorizes_execution_authorization' =>
                false,

            'recommended_path_authorizes_actual_execution' =>
                false,

            'recommendation_intelligence_authorizes_ai_change' =>
                false,

            'recommendation_intelligence_authorizes_execution' =>
                false,

            'recommendation_intelligence_authorizes_deployment' =>
                false,

            'recommendation_intelligence_authorizes_rollback' =>
                false,

            'recommendation_intelligence_authorizes_clinical_action' =>
                false,

            'recommendation_intelligence_overrides_human_review' =>
                false,

            'recommendation_intelligence_overrides_activation_authorization' =>
                false,

            'recommendation_intelligence_overrides_execution_authorization' =>
                false,

            'recommendation_intelligence_overrides_actual_execution_authority' =>
                false,

            'recommendation_intelligence_overrides_evidence_requirements' =>
                false,

            'automatic_execution_authorization_allowed' =>
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

            'execution_authorization_authority_reserved_for_authorized_human' =>
                true,

            'actual_execution_authority_separate_from_execution_authorization' =>
                true,

            'actual_execution_authority_reserved_for_authorized_human' =>
                true,

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'controlled_activation_authorization_required' =>
                true,

            'authorized_human_execution_authorization_required' =>
                true,

            'authorized_human_execution_required' =>
                true,

            'message' =>
                'Step 70.6 controlled activation execution authorization recommendation intelligence prioritizes authorized-human governance actions based on execution authorization state, source controlled activation authorization dependencies, critical conditions, execution blockers, restrictions, evidence, readiness, risk, eligibility, safety, governance integrity, authorization attribution, and actual-execution separation. Recommendations, priority levels, scores, rankings, and recommended paths are advisory only and cannot make or record an execution authorization decision, complete execution authorization, execute controlled activation, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action. Execution authorization and actual controlled activation execution remain separate authorities reserved for explicitly authorized human governance.',
        ];
    }
}