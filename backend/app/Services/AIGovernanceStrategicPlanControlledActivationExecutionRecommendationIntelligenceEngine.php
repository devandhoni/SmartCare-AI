<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionRecommendationIntelligenceEngine
{
    public function analyze(?int $executionId = null): array
    {
        $execution = $executionId
            ? AIGovernanceStrategicPlanControlledActivationExecution::find($executionId)
            : AIGovernanceStrategicPlanControlledActivationExecution::latest('id')->first();

        if (!$execution) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_EXECUTION_AVAILABLE',
                'message' => 'No Step 71 controlled activation execution record is available.',
            ];
        }

        $state = app(
            AIGovernanceStrategicPlanControlledActivationExecutionStateIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_STATE_INTELLIGENCE_UNAVAILABLE',
                'source_state_result' => $state,
            ];
        }

        $conditionRestriction = app(
            AIGovernanceStrategicPlanControlledActivationExecutionConditionRestrictionIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'source_condition_restriction_result' => $conditionRestriction,
            ];
        }

        $eligibilitySafety = app(
            AIGovernanceStrategicPlanControlledActivationExecutionEligibilitySafetyIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'source_eligibility_safety_result' => $eligibilitySafety,
            ];
        }

        $executionState = $state['execution_state'];
        $conditionState = $conditionRestriction['condition_restriction_state'];
        $eligibilityState = $eligibilitySafety['execution_eligibility_state'];
        $safetyState = $eligibilitySafety['execution_safety_state'];
        $sourceAuthorization = $state['source_execution_authorization_context'];
        $executionAttribution = $state['execution_attribution_context'];

        $sourceDecisionRecorded =
            (bool) ($sourceAuthorization['source_execution_authorization_decision_recorded'] ?? false);

        $sourceMadeByAuthorizedHuman =
            (bool) ($sourceAuthorization['source_execution_authorization_made_by_authorized_human'] ?? false);

        $sourceAttributionComplete =
            (bool) ($sourceAuthorization['source_execution_authorization_attribution_complete'] ?? false);

        $sourceAuthorizationCompleted =
            (bool) ($sourceAuthorization['source_execution_authorization_completed'] ?? false);

        $sourceAuthorizationValid =
            (bool) ($sourceAuthorization['source_execution_authorization_valid'] ?? false);

        $blockingExecutionConditions =
            (int) ($conditionState['blocking_execution_conditions'] ?? 0);

        $criticalOpenConditions =
            (int) ($conditionState['critical_open_conditions'] ?? 0);

        $materialRestrictions =
            (int) ($conditionState['material_restrictions'] ?? 0);

        $criticalRestrictions =
            (int) ($conditionState['critical_restrictions'] ?? 0);

        $executionReadinessScore =
            (float) ($executionState['execution_readiness_score'] ?? 0);

        $executionRiskScore =
            (float) ($executionState['execution_risk_score'] ?? 100);

        $executionRiskLevel =
            $executionState['execution_risk_level']
            ?? 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RISK';

        $executionEligibilityScore =
            (float) ($eligibilityState['execution_eligibility_score'] ?? 0);

        $executionSafetyScore =
            (float) ($safetyState['execution_safety_score'] ?? 0);

        $combinedResolutionScore =
            (float) ($conditionState['combined_resolution_score'] ?? 0);

        $combinedPressureScore =
            (float) ($conditionState['combined_condition_restriction_pressure_score'] ?? 0);

        $governanceIntegrityIntact =
            (bool) ($safetyState['governance_integrity_intact'] ?? false);

        $controlledActivationExecuted =
            (bool) ($executionAttribution['controlled_activation_executed'] ?? false);

        $executionAttributionComplete =
            (bool) ($executionAttribution['execution_attribution_complete'] ?? false);

        $recommendations = [];

        if (!$sourceDecisionRecorded) {
            $recommendations[] = $this->recommendation(
                'RECORD_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION',
                'EXECUTION_AUTHORIZATION',
                'CRITICAL',
                100.0,
                'Record the required controlled activation execution authorization decision through explicitly authorized human governance before controlled activation execution progression.',
                'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION'
            );
        }

        if (!$sourceMadeByAuthorizedHuman) {
            $recommendations[] = $this->recommendation(
                'ESTABLISH_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION',
                'EXECUTION_AUTHORIZATION_AUTHORITY',
                'CRITICAL',
                99.0,
                'Ensure controlled activation execution authorization is made by an explicitly authorized human governance authority.',
                'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION'
            );
        }

        if (!$sourceAttributionComplete) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_EXECUTION_AUTHORIZATION_ATTRIBUTION',
                'EXECUTION_AUTHORIZATION_ATTRIBUTION',
                'CRITICAL',
                98.0,
                'Complete execution authorization attribution, including authorized human identity, governance role, and authorization timestamp.',
                'COMPLETE_EXECUTION_AUTHORIZATION_ATTRIBUTION'
            );
        }

        if (!$sourceAuthorizationCompleted) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION',
                'EXECUTION_AUTHORIZATION_COMPLETION',
                'CRITICAL',
                97.0,
                'Complete controlled activation execution authorization through explicitly authorized human governance before actual controlled activation execution can be considered.',
                'COMPLETE_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION'
            );
        }

        if ($criticalOpenConditions > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_CONDITIONS',
                'EXECUTION_CONDITION',
                'CRITICAL',
                96.0,
                "Resolve or formally govern {$criticalOpenConditions} critical controlled activation execution condition(s) through explicitly authorized human governance.",
                'AUTHORIZED_HUMAN_CRITICAL_EXECUTION_CONDITION_RESOLUTION'
            );
        }

        if ($criticalRestrictions > 0) {
            $recommendations[] = $this->recommendation(
                'ADDRESS_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',
                'EXECUTION_RESTRICTION',
                'CRITICAL',
                95.0,
                "Maintain immediate authorized-human governance treatment for {$criticalRestrictions} critical controlled activation execution restriction(s).",
                'AUTHORIZED_HUMAN_CRITICAL_EXECUTION_RESTRICTION_GOVERNANCE'
            );
        }

        if ($executionRiskScore >= 90) {
            $recommendations[] = $this->recommendation(
                'MAINTAIN_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RISK_OVERSIGHT',
                'EXECUTION_RISK',
                'CRITICAL',
                94.0,
                "Maintain critical authorized-human governance oversight while controlled activation execution risk remains {$executionRiskLevel} with score {$executionRiskScore}.",
                'CRITICAL_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_RISK_REVIEW'
            );
        }

        if ($executionSafetyScore < 80) {
            $recommendations[] = $this->recommendation(
                'MAINTAIN_CONTROLLED_ACTIVATION_EXECUTION_SAFETY_BLOCK',
                'EXECUTION_SAFETY',
                'CRITICAL',
                93.0,
                "Maintain controlled activation execution restrictions while the execution safety score remains {$executionSafetyScore}.",
                'MAINTAIN_CONTROLLED_ACTIVATION_EXECUTION_SAFETY_RESTRICTIONS'
            );
        }

        if (
            ($eligibilityState['controlled_activation_execution_completion_eligibility'] ?? null)
            !== 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION'
        ) {
            $recommendations[] = $this->recommendation(
                'DO_NOT_COMPLETE_CONTROLLED_ACTIVATION_EXECUTION',
                'EXECUTION_ELIGIBILITY',
                'HIGH',
                91.0,
                'Do not complete controlled activation execution while execution completion eligibility remains unavailable.',
                'MAINTAIN_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION_BLOCK'
            );
        }

        if ($blockingExecutionConditions > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_CONTROLLED_ACTIVATION_EXECUTION_BLOCKERS',
                'EXECUTION_BLOCKER',
                'HIGH',
                90.0,
                "Resolve or formally govern {$blockingExecutionConditions} condition(s) currently blocking controlled activation execution.",
                'RESOLVE_CONTROLLED_ACTIVATION_EXECUTION_BLOCKING_REQUIREMENTS'
            );
        }

        if ($materialRestrictions > 0) {
            $recommendations[] = $this->recommendation(
                'GOVERN_MATERIAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',
                'EXECUTION_RESTRICTION',
                'HIGH',
                88.0,
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material controlled activation execution restriction(s).",
                'AUTHORIZED_HUMAN_MATERIAL_EXECUTION_RESTRICTION_GOVERNANCE'
            );
        }

        if (!$sourceAuthorizationValid) {
            $recommendations[] = $this->recommendation(
                'KEEP_CONTROLLED_ACTIVATION_EXECUTION_BLOCKED',
                'EXECUTION_SEQUENCE',
                'HIGH',
                87.0,
                'Keep actual controlled activation execution blocked until a valid completed explicitly authorized-human execution authorization exists.',
                'KEEP_CONTROLLED_ACTIVATION_EXECUTION_BLOCKED'
            );
        }

        if (!$controlledActivationExecuted) {
            $recommendations[] = $this->recommendation(
                'MAINTAIN_PENDING_CONTROLLED_ACTIVATION_EXECUTION',
                'EXECUTION_STATE',
                'HIGH',
                86.0,
                'Keep controlled activation execution pending until all authorization, eligibility, safety, condition, restriction, and attribution requirements are satisfied.',
                'MAINTAIN_PENDING_CONTROLLED_ACTIVATION_EXECUTION'
            );
        }

        $recommendations[] = $this->recommendation(
            'PRESERVE_EXECUTION_AUTHORIZATION_EXECUTION_SEPARATION',
            'AUTHORITY_SEPARATION',
            'HIGH',
            84.0,
            'Preserve strict separation between controlled activation execution authorization and actual controlled activation execution.',
            'KEEP_EXECUTION_AUTHORIZATION_AND_EXECUTION_SEPARATE'
        );

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_POST_EXECUTION_ATTRIBUTION',
                'POST_EXECUTION_ATTRIBUTION',
                'CRITICAL',
                100.0,
                'Immediately complete authorized-human executor attribution for the recorded controlled activation execution.',
                'COMPLETE_POST_EXECUTION_HUMAN_ATTRIBUTION'
            );
        }

        if (!$governanceIntegrityIntact) {
            $recommendations[] = $this->recommendation(
                'RESTORE_CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_INTEGRITY',
                'GOVERNANCE_INTEGRITY',
                'CRITICAL',
                100.0,
                'Immediately restore controlled activation execution governance integrity before any further progression.',
                'RESTORE_EXECUTION_GOVERNANCE_INTEGRITY'
            );
        }

        $recommendations[] = $this->recommendation(
            'PRESERVE_POST_EXECUTION_HUMAN_VALIDATION',
            'POST_EXECUTION_GOVERNANCE',
            'ADVISORY',
            60.0,
            'Preserve mandatory post-execution human validation if controlled activation execution occurs in the future.',
            'REQUIRE_POST_EXECUTION_HUMAN_VALIDATION'
        );

        $recommendations[] = $this->recommendation(
            'PRESERVE_CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_INTEGRITY',
            'GOVERNANCE_INTEGRITY',
            'ADVISORY',
            55.0,
            'Preserve human authority, source traceability, authorization attribution, execution attribution, safety isolation, and governance-control integrity throughout controlled activation execution progression.',
            'PRESERVE_CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_CONTROLS'
        );

        $recommendations = collect($recommendations)
            ->sortByDesc('priority_score')
            ->values();

        $criticalRecommendations =
            $recommendations->where('priority_level', 'CRITICAL')->count();

        $highRecommendations =
            $recommendations->where('priority_level', 'HIGH')->count();

        $moderateRecommendations =
            $recommendations->where('priority_level', 'MODERATE')->count();

        $advisoryRecommendations =
            $recommendations->where('priority_level', 'ADVISORY')->count();

        $topRecommendation = $recommendations->first();

        $recommendationStatus = match (true) {
            $criticalRecommendations > 0
                => 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATIONS',

            $highRecommendations > 0
                => 'HIGH_CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATIONS',

            $moderateRecommendations > 0
                => 'MODERATE_CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATIONS',

            default
                => 'ADVISORY_CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATIONS',
        };

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'recommendation_state' => [
                'recommendation_status'
                    => $recommendationStatus,

                'total_recommendations'
                    => $recommendations->count(),

                'critical_recommendations'
                    => $criticalRecommendations,

                'high_recommendations'
                    => $highRecommendations,

                'moderate_recommendations'
                    => $moderateRecommendations,

                'advisory_recommendations'
                    => $advisoryRecommendations,

                'top_recommendation_code'
                    => $topRecommendation['recommendation_code'] ?? null,

                'top_recommendation_priority_level'
                    => $topRecommendation['priority_level'] ?? null,

                'top_recommendation_priority_score'
                    => $topRecommendation['priority_score'] ?? null,

                'top_recommended_execution_path'
                    => $topRecommendation['recommended_path'] ?? null,

                'execution_progression_blocked'
                    => (bool) ($eligibilityState['execution_progression_blocked'] ?? true),

                'governance_integrity_intact'
                    => $governanceIntegrityIntact,

                'human_management_attention_required'
                    => (bool) ($safetyState['human_management_attention_required'] ?? true),

                'immediate_human_intervention_required'
                    => (bool) ($safetyState['immediate_human_intervention_required'] ?? true),
            ],

            'top_recommendation'
                => $topRecommendation,

            'recommendations'
                => $recommendations->all(),

            'recommendation_context' => [
                'controlled_activation_execution_state'
                    => $executionState['controlled_activation_execution_state'],

                'execution_readiness'
                    => $executionState['execution_readiness'],

                'execution_readiness_score'
                    => $executionReadinessScore,

                'execution_risk_level'
                    => $executionRiskLevel,

                'execution_risk_score'
                    => $executionRiskScore,

                'execution_eligibility_score'
                    => $executionEligibilityScore,

                'execution_safety_status'
                    => $safetyState['execution_safety_status'],

                'execution_safety_level'
                    => $safetyState['execution_safety_level'],

                'execution_safety_score'
                    => $executionSafetyScore,

                'combined_resolution_score'
                    => $combinedResolutionScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,

                'blocking_execution_conditions'
                    => $blockingExecutionConditions,

                'critical_open_conditions'
                    => $criticalOpenConditions,

                'material_restrictions'
                    => $materialRestrictions,

                'critical_restrictions'
                    => $criticalRestrictions,

                'source_execution_authorization_decision_recorded'
                    => $sourceDecisionRecorded,

                'source_execution_authorization_made_by_authorized_human'
                    => $sourceMadeByAuthorizedHuman,

                'source_execution_authorization_attribution_complete'
                    => $sourceAttributionComplete,

                'source_execution_authorization_completed'
                    => $sourceAuthorizationCompleted,

                'source_execution_authorization_valid'
                    => $sourceAuthorizationValid,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,
            ],

            'execution_state_context'
                => $executionState,

            'condition_restriction_context'
                => $conditionState,

            'execution_eligibility_context'
                => $eligibilityState,

            'execution_safety_context'
                => $safetyState,

            'source_execution_authorization_context'
                => $sourceAuthorization,

            'execution_attribution_context'
                => $executionAttribution,

            'recommendation_findings' => [
                "Controlled activation execution recommendation intelligence is based on Step 71 execution record {$execution->id}.",
                "Current recommendation status is {$recommendationStatus}.",
                $recommendations->count().' controlled activation execution recommendation(s) are represented.',
                "{$criticalRecommendations} critical recommendation(s) are represented.",
                "{$highRecommendations} high recommendation(s) are represented.",
                "{$moderateRecommendations} moderate recommendation(s) are represented.",
                "{$advisoryRecommendations} advisory recommendation(s) are represented.",
                'Current top recommendation is '.($topRecommendation['recommendation_code'] ?? 'NONE').'.',
                'Current top recommended execution path is '.($topRecommendation['recommended_path'] ?? 'NONE').'.',
                "Current execution readiness score is {$executionReadinessScore}.",
                "Current execution risk score is {$executionRiskScore}.",
                "Current execution eligibility score is {$executionEligibilityScore}.",
                "Current execution safety score is {$executionSafetyScore}.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                "{$blockingExecutionConditions} controlled activation execution blocker(s) remain.",
                "{$criticalOpenConditions} critical execution condition(s) remain open.",
                "{$materialRestrictions} material execution restriction(s) remain.",
                "{$criticalRestrictions} critical execution restriction(s) remain.",
                'Governance integrity remains '.($governanceIntegrityIntact ? 'INTACT' : 'VIOLATED').'.',
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Recommendation intelligence remains advisory and does not authorize or perform controlled activation execution.',
            ],

            'management_priorities'
                => $recommendations
                    ->take(15)
                    ->pluck('recommendation')
                    ->values()
                    ->all(),

            'controlled_activation_execution_recommendation_guardrails'
                => $this->guardrails(),
        ];
    }

    private function recommendation(
        string $code,
        string $type,
        string $priority,
        float $score,
        string $recommendation,
        string $recommendedPath
    ): array {
        return [
            'recommendation_code' => $code,
            'recommendation_type' => $type,
            'priority_level' => $priority,
            'priority_score' => $score,
            'recommendation' => $recommendation,
            'recommended_path' => $recommendedPath,
            'requires_authorized_human_governance' => true,
            'automatic_action_allowed' => false,
            'authorizes_execution' => false,
            'performs_execution' => false,
        ];
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_recommendation_intelligence_enabled'
                => true,

            'recommendation_intelligence_is_execution'
                => false,

            'recommendation_intelligence_is_execution_authorization'
                => false,

            'recommendation_intelligence_makes_execution_decision'
                => false,

            'recommendation_intelligence_records_execution_decision'
                => false,

            'recommendation_intelligence_executes_controlled_activation'
                => false,

            'recommendation_intelligence_activates_strategic_plan'
                => false,

            'recommendation_intelligence_changes_execution_status'
                => false,

            'recommendation_intelligence_changes_execution_outcome'
                => false,

            'recommendation_intelligence_resolves_conditions'
                => false,

            'recommendation_intelligence_waives_conditions'
                => false,

            'recommendation_intelligence_removes_restrictions'
                => false,

            'recommendation_intelligence_downgrades_restrictions'
                => false,

            'recommendation_intelligence_validates_evidence'
                => false,

            'recommendation_priority_authorizes_execution'
                => false,

            'recommendation_score_authorizes_execution'
                => false,

            'recommended_path_authorizes_execution'
                => false,

            'recommendation_intelligence_authorizes_ai_change'
                => false,

            'recommendation_intelligence_authorizes_deployment'
                => false,

            'recommendation_intelligence_authorizes_rollback'
                => false,

            'recommendation_intelligence_authorizes_clinical_action'
                => false,

            'automatic_execution_allowed'
                => false,

            'automatic_execution_authorization_allowed'
                => false,

            'automatic_activation_allowed'
                => false,

            'automatic_condition_resolution_allowed'
                => false,

            'automatic_restriction_removal_allowed'
                => false,

            'automatic_evidence_validation_allowed'
                => false,

            'automatic_change_allowed'
                => false,

            'automatic_deployment_allowed'
                => false,

            'automatic_rollback_allowed'
                => false,

            'automatic_clinical_action_allowed'
                => false,

            'execution_authorization_required_before_execution'
                => true,

            'actual_execution_authority_reserved_for_authorized_human'
                => true,

            'post_execution_human_validation_required'
                => true,

            'message' =>
                'Step 71.6 controlled activation execution recommendation intelligence prioritizes authorized-human governance actions based on execution state, source execution authorization, blockers, critical conditions, restrictions, readiness, risk, eligibility, safety, resolution progress, pressure, execution attribution, and governance integrity. Recommendations, priorities, scores, rankings, and recommended paths are advisory only and cannot authorize execution, perform controlled activation execution, resolve or waive conditions, remove restrictions, validate evidence, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action. Actual controlled activation execution remains a separately governed authority reserved for explicitly authorized human governance.',
        ];
    }
}