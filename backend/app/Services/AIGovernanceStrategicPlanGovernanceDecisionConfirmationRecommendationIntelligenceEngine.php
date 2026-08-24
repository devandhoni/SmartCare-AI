<?php

namespace App\Services;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationRecommendationIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanGovernanceDecisionConfirmationEligibilitySafetyIntelligenceEngine $eligibilitySafetyEngine
    ) {}

    public function analyze(?int $confirmationId = null): array
    {
        $source = $this->eligibilitySafetyEngine->analyze($confirmationId);

        if (!($source['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'reason' => 'STEP_68_5_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'source_context' => $source,
            ];
        }

        $eligibility = $source['confirmation_eligibility_state'] ?? [];
        $safety = $source['confirmation_safety_state'] ?? [];
        $conditions = $source['condition_context'] ?? [];
        $restrictions = $source['restriction_context'] ?? [];
        $evidence = $source['evidence_context'] ?? [];
        $activation = $source['controlled_activation_context'] ?? [];
        $dominantRestriction = $source['dominant_eligibility_restriction'] ?? [];

        $recommendations = [];

        $addRecommendation = function (
            string $code,
            string $category,
            string $priority,
            string $message,
            string $recommendedPath,
            bool $requiresAuthorizedHuman = true,
            bool $blocksConfirmation = false,
            bool $blocksActivation = false
        ) use (&$recommendations): void {
            $recommendations[] = [
                'recommendation_code' => $code,
                'category' => $category,
                'priority' => $priority,
                'message' => $message,
                'recommended_path' => $recommendedPath,
                'requires_authorized_human_governance' => $requiresAuthorizedHuman,
                'blocks_governance_confirmation' => $blocksConfirmation,
                'blocks_controlled_activation' => $blocksActivation,
                'automatic_action_allowed' => false,
            ];
        };

        if (!($eligibility['source_final_governance_decision_recorded'] ?? false)) {
            $addRecommendation(
                'RECORD_AUTHORIZED_HUMAN_FINAL_STRATEGIC_PLAN_DECISION',
                'FINAL_GOVERNANCE_DECISION',
                'CRITICAL',
                'Record an explicitly authorized human final strategic plan governance decision before completed governance confirmation is considered.',
                'AWAIT_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION',
                true,
                true,
                true
            );
        }

        if (!($eligibility['source_decision_made_by_authorized_human'] ?? false)) {
            $addRecommendation(
                'ESTABLISH_AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION',
                'AUTHORIZATION',
                'CRITICAL',
                'Ensure the source final governance decision is explicitly attributable to an authorized human governance decision-maker.',
                'COMPLETE_FINAL_DECISION_ATTRIBUTION',
                true,
                true,
                true
            );
        }

        if (!($eligibility['source_governance_validation_completed'] ?? false)) {
            $addRecommendation(
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION',
                'GOVERNANCE_VALIDATION',
                'HIGH',
                'Complete required source governance validation through explicitly authorized human governance.',
                'COMPLETE_SOURCE_GOVERNANCE_VALIDATION',
                true,
                true,
                true
            );
        }

        $blockingConfirmationConditions = (int) ($conditions['blocking_confirmation_conditions'] ?? 0);

        if ($blockingConfirmationConditions > 0) {
            $addRecommendation(
                'RESOLVE_CONFIRMATION_BLOCKING_CONDITIONS',
                'CONFIRMATION_CONDITION',
                'HIGH',
                "{$blockingConfirmationConditions} governance-confirmation blocking condition(s) require authorized human governance treatment.",
                'AUTHORIZED_HUMAN_CONDITION_RESOLUTION',
                true,
                true,
                true
            );
        }

        $materialRestrictions = (int) ($restrictions['material_restrictions'] ?? 0);

        if ($materialRestrictions > 0) {
            $addRecommendation(
                'GOVERN_MATERIAL_CONFIRMATION_RESTRICTIONS',
                'CONFIRMATION_RESTRICTION',
                'HIGH',
                "{$materialRestrictions} material governance-confirmation restriction(s) require explicit authorized human governance treatment.",
                'AUTHORIZED_HUMAN_RESTRICTION_GOVERNANCE',
                true,
                true,
                true
            );
        }

        $blockingEvidence = (int) ($evidence['blocking_confirmation_evidence_items'] ?? 0);

        if ($blockingEvidence > 0) {
            $addRecommendation(
                'RESOLVE_BLOCKING_CONFIRMATION_EVIDENCE',
                'EVIDENCE',
                'HIGH',
                "{$blockingEvidence} evidence requirement(s) currently block completed governance confirmation.",
                'RESOLVE_GOVERNANCE_CONFIRMATION_EVIDENCE',
                true,
                true,
                true
            );
        }

        $riskScore = (float) ($source['resolution_context']['confirmation_risk_score'] ?? 0);
        $riskLevel = $source['resolution_context']['confirmation_risk_level'] ?? 'UNKNOWN';

        if ($riskScore >= 75) {
            $addRecommendation(
                'MAINTAIN_ELEVATED_AUTHORIZED_HUMAN_GOVERNANCE_OVERSIGHT',
                'RISK',
                'HIGH',
                "Maintain elevated authorized-human governance oversight while confirmation risk remains {$riskLevel} with score {$riskScore}.",
                'ELEVATED_HUMAN_GOVERNANCE_OVERSIGHT',
                true,
                false,
                true
            );
        }

        if (!($source['confirmation_attribution_context']['confirmation_attribution_complete'] ?? false)) {
            $addRecommendation(
                'REQUIRE_AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION',
                'CONFIRMATION_AUTHORIZATION',
                'HIGH',
                'Any completed governance confirmation must be explicitly attributable to an identified authorized human governance confirmer.',
                'COMPLETE_CONFIRMATION_ATTRIBUTION',
                true,
                false,
                true
            );
        }

        if (!($source['confirmation_authority_context']['governance_decision_confirmation_completed'] ?? false)) {
            $addRecommendation(
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION',
                'HUMAN_CONFIRMATION',
                'HIGH',
                'Do not treat governance confirmation as completed until an explicit authorized-human confirmation decision and attribution are recorded.',
                'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION',
                true,
                false,
                true
            );
        }

        if (!($activation['controlled_activation_consideration_eligible'] ?? false)) {
            $addRecommendation(
                'KEEP_CONTROLLED_ACTIVATION_OUTSIDE_ELIGIBLE_CONSIDERATION',
                'CONTROLLED_ACTIVATION',
                'HIGH',
                'Keep controlled activation outside eligible consideration until all confirmation, attribution, condition, restriction, evidence, and safety prerequisites are satisfied.',
                'MAINTAIN_ACTIVATION_BLOCK',
                true,
                false,
                true
            );
        }

        if (!($activation['controlled_activation_authorized'] ?? false)) {
            $addRecommendation(
                'REQUIRE_SEPARATE_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION',
                'CONTROLLED_ACTIVATION_AUTHORIZATION',
                'HIGH',
                'Controlled activation must remain unauthorized until separately authorized by explicitly authorized human governance.',
                'AWAIT_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION',
                true,
                false,
                true
            );
        }

        $priorityWeights = [
            'CRITICAL' => 100,
            'HIGH' => 75,
            'MODERATE' => 50,
            'LOW' => 25,
        ];

        usort($recommendations, function (array $a, array $b) use ($priorityWeights): int {
            return ($priorityWeights[$b['priority']] ?? 0)
                <=> ($priorityWeights[$a['priority']] ?? 0);
        });

        $topRecommendation = $recommendations[0] ?? null;

        $criticalCount = count(array_filter(
            $recommendations,
            fn (array $recommendation) => $recommendation['priority'] === 'CRITICAL'
        ));

        $highCount = count(array_filter(
            $recommendations,
            fn (array $recommendation) => $recommendation['priority'] === 'HIGH'
        ));

        $managementAttentionRequired =
            ($safety['human_management_attention_required'] ?? false)
            || $criticalCount > 0
            || $highCount > 0;

        $recommendationState = [
            'recommendation_status' => $criticalCount > 0
                ? 'CRITICAL_GOVERNANCE_CONFIRMATION_RECOMMENDATIONS'
                : ($highCount > 0
                    ? 'ELEVATED_GOVERNANCE_CONFIRMATION_RECOMMENDATIONS'
                    : 'STANDARD_GOVERNANCE_CONFIRMATION_RECOMMENDATIONS'),

            'total_recommendations' => count($recommendations),
            'critical_recommendations' => $criticalCount,
            'high_recommendations' => $highCount,

            'top_recommendation_code' => $topRecommendation['recommendation_code'] ?? null,
            'top_recommended_path' => $topRecommendation['recommended_path'] ?? null,

            'management_attention_required' => $managementAttentionRequired,

            'confirmation_progression_blocked' =>
                (bool) ($eligibility['confirmation_progression_blocked'] ?? true),

            'controlled_activation_progression_blocked' =>
                (bool) ($eligibility['controlled_activation_progression_blocked'] ?? true),

            'authorized_human_governance_required' => true,
        ];

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_governance_decision_confirmation_id' =>
                $source['strategic_plan_governance_decision_confirmation_id'] ?? null,

            'confirmation_code' =>
                $source['confirmation_code'] ?? null,

            'strategic_plan_final_governance_decision_id' =>
                $source['strategic_plan_final_governance_decision_id'] ?? null,

            'strategic_plan_decision_validation_id' =>
                $source['strategic_plan_decision_validation_id'] ?? null,

            'strategic_plan_human_decision_id' =>
                $source['strategic_plan_human_decision_id'] ?? null,

            'strategic_plan_decision_id' =>
                $source['strategic_plan_decision_id'] ?? null,

            'strategic_plan_id' =>
                $source['strategic_plan_id'] ?? null,

            'strategic_snapshot_id' =>
                $source['strategic_snapshot_id'] ?? null,

            'operational_snapshot_id' =>
                $source['operational_snapshot_id'] ?? null,

            'lifecycle_snapshot_id' =>
                $source['lifecycle_snapshot_id'] ?? null,

            'decision_scope' =>
                $source['decision_scope'] ?? null,

            'resident_id' =>
                $source['resident_id'] ?? null,

            'recommendation_state' => $recommendationState,

            'recommendations' => $recommendations,

            'top_recommendation' => $topRecommendation,

            'dominant_eligibility_restriction' => $dominantRestriction,

            'confirmation_eligibility_context' => $eligibility,

            'confirmation_safety_context' => $safety,

            'condition_context' => $conditions,

            'restriction_context' => $restrictions,

            'evidence_context' => $evidence,

            'resolution_context' =>
                $source['resolution_context'] ?? [],

            'confirmation_attribution_context' =>
                $source['confirmation_attribution_context'] ?? [],

            'controlled_activation_context' =>
                $activation,

            'recommendation_findings' => [
                'Step 68.6 recommendation intelligence is available.',
                'Recommendations are derived from Step 68.5 confirmation eligibility and safety intelligence.',
                'Current top recommendation is ' . ($topRecommendation['recommendation_code'] ?? 'NONE') . '.',
                'Current recommended governance path is ' . ($topRecommendation['recommended_path'] ?? 'NONE') . '.',
                count($recommendations) . ' governance-confirmation recommendation(s) are represented.',
                $criticalCount . ' critical recommendation(s) require attention.',
                $highCount . ' high-priority recommendation(s) require attention.',
                'Recommendation intelligence does not constitute governance confirmation or controlled activation authorization.',
            ],

            'management_priorities' => array_values(array_map(
                fn (array $recommendation) => $recommendation['message'],
                $recommendations
            )),

            'confirmation_recommendation_guardrails' => [
                'governance_confirmation_recommendation_intelligence_enabled' => true,

                'recommendation_intelligence_is_governance_confirmation' => false,
                'recommendation_intelligence_makes_governance_confirmation_decision' => false,
                'recommendation_intelligence_records_governance_confirmation_decision' => false,
                'recommendation_intelligence_completes_governance_confirmation' => false,

                'recommendation_intelligence_makes_final_governance_decision' => false,
                'recommendation_intelligence_records_final_governance_decision' => false,

                'recommendation_intelligence_approves_strategic_plan' => false,
                'recommendation_intelligence_rejects_strategic_plan' => false,
                'recommendation_intelligence_conditionally_approves_strategic_plan' => false,
                'recommendation_intelligence_defers_strategic_plan' => false,
                'recommendation_intelligence_accepts_governance_risk' => false,

                'recommendation_intelligence_authorizes_controlled_activation' => false,
                'recommendation_intelligence_activates_strategic_plan' => false,

                'recommendation_intelligence_changes_confirmation_status' => false,
                'recommendation_intelligence_changes_confirmation_decision' => false,
                'recommendation_intelligence_changes_confirmation_outcome' => false,

                'recommendation_intelligence_changes_final_governance_decision' => false,
                'recommendation_intelligence_changes_governance_validation' => false,
                'recommendation_intelligence_changes_controlled_activation_status' => false,

                'recommendation_intelligence_resolves_conditions' => false,
                'recommendation_intelligence_waives_conditions' => false,
                'recommendation_intelligence_removes_restrictions' => false,
                'recommendation_intelligence_validates_evidence' => false,

                'recommendation_priority_authorizes_confirmation' => false,
                'recommendation_priority_authorizes_activation' => false,
                'top_recommendation_authorizes_confirmation' => false,
                'top_recommendation_authorizes_activation' => false,

                'recommendation_intelligence_authorizes_ai_change' => false,
                'recommendation_intelligence_authorizes_execution' => false,
                'recommendation_intelligence_authorizes_deployment' => false,
                'recommendation_intelligence_authorizes_rollback' => false,
                'recommendation_intelligence_authorizes_clinical_action' => false,

                'automatic_recommendation_execution_allowed' => false,
                'automatic_confirmation_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_execution_allowed' => false,

                'governance_confirmation_authority_reserved_for_authorized_human' => true,
                'controlled_activation_authority_reserved_for_authorized_human' => true,
                'final_governance_decision_authority_reserved_for_authorized_human' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,
                'authorized_human_confirmation_required' => true,
                'authorized_human_activation_required' => true,

                'message' =>
                    'Step 68.6 strategic plan governance decision confirmation recommendation intelligence prioritizes authorized-human governance actions from Step 68.5 eligibility and safety intelligence. Recommendations, priorities, paths, scores, and findings are advisory governance intelligence only and do not constitute or authorize a final governance decision, governance confirmation, risk acceptance, controlled activation, execution, deployment, rollback, AI modification, or clinical action.',
            ],

            'source_context' => $source['source_context'] ?? [],

            'governance_context' => array_merge(
                $source['governance_context'] ?? [],
                [
                    'step_68_confirmation_recommendation_intelligence' => true,
                    'confirmation_recommendation_authority' => 'ADVISORY_INTELLIGENCE_ONLY',
                    'top_confirmation_recommendation_code' =>
                        $topRecommendation['recommendation_code'] ?? null,
                    'top_confirmation_recommended_path' =>
                        $topRecommendation['recommended_path'] ?? null,
                    'confirmation_authority' => 'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',
                    'activation_authority' => 'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',
                    'automatic_recommendation_execution_allowed' => false,
                    'automatic_confirmation_allowed' => false,
                    'automatic_activation_allowed' => false,
                    'automatic_execution_allowed' => false,
                ]
            ),
        ];
    }
}