<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanAuthorizedHumanDecisionRecommendationIntelligenceEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $humanDecision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::find($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->first();

        if (!$humanDecision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_HUMAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan human decision record is available for authorized human decision recommendation intelligence.',
            ];
        }

        $stateEngine = app(AIGovernanceStrategicPlanHumanDecisionStateIntelligenceEngine::class);
        $resolutionEngine = app(AIGovernanceStrategicPlanHumanDecisionConditionEvidenceResolutionIntelligenceEngine::class);
        $eligibilitySafetyEngine = app(AIGovernanceStrategicPlanAuthorizedHumanDecisionEligibilitySafetyIntelligenceEngine::class);

        $state = $stateEngine->analyze($humanDecision->id);
        $resolution = $resolutionEngine->analyze($humanDecision->id);
        $eligibilitySafety = $eligibilitySafetyEngine->analyze($humanDecision->id);

        $humanDecisionState = $state['human_decision_state'] ?? [];
        $conditionContext = $eligibilitySafety['condition_context'] ?? [];
        $evidenceContext = $eligibilitySafety['evidence_context'] ?? [];
        $resolutionContext = $eligibilitySafety['resolution_context'] ?? [];
        $eligibilityState = $eligibilitySafety['authorized_human_decision_eligibility_state'] ?? [];
        $safetyState = $eligibilitySafety['human_decision_safety_state'] ?? [];
        $decisionPaths = $eligibilitySafety['decision_path_summary'] ?? [];

        $blockingConditions = (int) ($conditionContext['blocking_conditions'] ?? 0);
        $constrainingConditions = (int) ($conditionContext['constraining_conditions'] ?? 0);
        $outstandingEvidence = (int) ($evidenceContext['outstanding_evidence_items'] ?? 0);
        $decisionBlockingEvidence = (int) ($evidenceContext['decision_blocking_evidence_items'] ?? 0);

        $decisionRiskScore = (float) ($humanDecisionState['decision_risk_score'] ?? 0);
        $decisionReadinessScore = (float) ($humanDecisionState['human_decision_readiness_score'] ?? 0);
        $decisionEligibilityScore = (float) ($humanDecisionState['decision_eligibility_score'] ?? 0);
        $combinedResolutionScore = (float) ($resolutionContext['combined_resolution_score'] ?? 0);

        $recommendations = [];

        if ($decisionBlockingEvidence > 0) {
            $recommendations[] = [
                'recommendation_code' => 'REQUEST_ADDITIONAL_VALIDATED_DECISION_EVIDENCE',
                'recommendation_category' => 'EVIDENCE',
                'priority_level' => 'HIGH',
                'recommended_human_decision_path' => 'REQUEST_MORE_EVIDENCE',
                'recommendation' => 'Consider requesting additional validated governance evidence before progressing toward unrestricted strategic plan approval.',
                'reason' => "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted strategic plan approval.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if ($blockingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' => 'GOVERN_BLOCKING_HUMAN_DECISION_CONDITIONS',
                'recommendation_category' => 'DECISION_CONDITION',
                'priority_level' => 'HIGH',
                'recommended_human_decision_path' => 'CONDITIONAL_APPROVAL_OR_DEFERRAL',
                'recommendation' => 'Consider formally governing unresolved blocking decision conditions before unrestricted strategic plan approval is considered.',
                'reason' => "{$blockingConditions} blocking human decision condition(s) remain active.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($decisionPaths['conditional_approval'] ?? null)
            === 'ELIGIBLE_FOR_CONDITIONAL_APPROVAL_CONSIDERATION'
        ) {
            $recommendations[] = [
                'recommendation_code' => 'CONSIDER_CONDITIONAL_STRATEGIC_PLAN_APPROVAL',
                'recommendation_category' => 'CONDITIONAL_APPROVAL',
                'priority_level' => 'HIGH',
                'recommended_human_decision_path' => 'CONDITIONAL_APPROVAL',
                'recommendation' => 'Conditional approval may be considered by authorized human governance only if explicit conditions, evidence requirements, risk controls, and validation obligations are formally documented.',
                'reason' => 'Conditional approval consideration is currently an eligible human-governed decision path while unrestricted approval remains restricted.',
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($decisionPaths['unrestricted_approval'] ?? null)
            === 'NOT_ELIGIBLE_FOR_UNRESTRICTED_APPROVAL'
        ) {
            $recommendations[] = [
                'recommendation_code' => 'DO_NOT_PROGRESS_TO_UNRESTRICTED_APPROVAL',
                'recommendation_category' => 'APPROVAL_RESTRICTION',
                'priority_level' => 'HIGH',
                'recommended_human_decision_path' => 'RESTRICT_UNRESTRICTED_APPROVAL',
                'recommendation' => 'Do not treat the current strategic plan decision package as ready for unrestricted approval.',
                'reason' => 'Material decision conditions and evidence requirements continue to restrict unrestricted approval eligibility.',
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($decisionPaths['risk_acceptance_review'] ?? null)
            === 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_RISK_ACCEPTANCE_REVIEW'
            && $decisionRiskScore >= 70
        ) {
            $recommendations[] = [
                'recommendation_code' => 'REQUIRE_EXPLICIT_HUMAN_RISK_ACCEPTANCE_IF_RISK_RETAINED',
                'recommendation_category' => 'RISK_ACCEPTANCE',
                'priority_level' => 'HIGH',
                'recommended_human_decision_path' => 'RISK_ACCEPTANCE_REVIEW',
                'recommendation' => 'If material strategic plan risk is retained rather than reduced, require explicit authorized human risk-acceptance review and documentation.',
                'reason' => "Current human strategic plan decision risk score is {$decisionRiskScore}.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($decisionPaths['deferral'] ?? null)
            === 'ELIGIBLE_FOR_HUMAN_GOVERNANCE_DEFERRAL'
            && ($blockingConditions > 0 || $decisionBlockingEvidence > 0)
        ) {
            $recommendations[] = [
                'recommendation_code' => 'CONSIDER_GOVERNANCE_DEFERRAL_WHILE_REQUIREMENTS_REMAIN',
                'recommendation_category' => 'DEFERRAL',
                'priority_level' => 'MODERATE',
                'recommended_human_decision_path' => 'DEFERRAL',
                'recommendation' => 'Authorized human governance may consider deferral while material decision conditions or evidence requirements remain unresolved.',
                'reason' => 'Material final-decision progression requirements remain outstanding.',
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if ($constrainingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' => 'REDUCE_CONSTRAINING_HUMAN_DECISION_CONDITIONS',
                'recommendation_category' => 'DECISION_READINESS',
                'priority_level' => 'MODERATE',
                'recommended_human_decision_path' => 'CONTINUE_GOVERNED_REVIEW',
                'recommendation' => 'Reduce or formally govern constraining decision conditions to improve authorized human decision readiness.',
                'reason' => "{$constrainingConditions} constraining human decision condition(s) remain active.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if ($decisionReadinessScore < 40) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_AUTHORIZED_HUMAN_DECISION_READINESS',
                'recommendation_category' => 'READINESS',
                'priority_level' => 'MODERATE',
                'recommended_human_decision_path' => 'CONTINUE_GOVERNED_REVIEW',
                'recommendation' => 'Improve human decision readiness before progressing toward unrestricted strategic plan approval.',
                'reason' => "Current human decision readiness score is {$decisionReadinessScore}.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if ($combinedResolutionScore < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_DECISION_RESOLUTION_PROGRESS',
                'recommendation_category' => 'RESOLUTION_PROGRESS',
                'priority_level' => 'MODERATE',
                'recommended_human_decision_path' => 'CONTINUE_GOVERNED_RESOLUTION',
                'recommendation' => 'Improve condition and evidence resolution progress before final strategic plan decision progression.',
                'reason' => "Current combined decision resolution score is {$combinedResolutionScore}.",
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($decisionPaths['rejection_review'] ?? null)
            === 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_REJECTION_CONSIDERATION'
        ) {
            $recommendations[] = [
                'recommendation_code' => 'PRESERVE_AUTHORIZED_HUMAN_REJECTION_REVIEW_PATH',
                'recommendation_category' => 'REJECTION_REVIEW',
                'priority_level' => 'ADVISORY',
                'recommended_human_decision_path' => 'REJECTION_REVIEW',
                'recommendation' => 'Preserve authorized human rejection review as an available governance pathway if the strategic plan is judged unacceptable after human review.',
                'reason' => 'Rejection review remains an eligible human-governed decision pathway.',
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        if (
            ($eligibilityState['governance_validation_eligibility'] ?? null)
            === 'NOT_ELIGIBLE_FOR_GOVERNANCE_VALIDATION'
        ) {
            $recommendations[] = [
                'recommendation_code' => 'DEFER_GOVERNANCE_VALIDATION_UNTIL_READY',
                'recommendation_category' => 'GOVERNANCE_VALIDATION',
                'priority_level' => 'ADVISORY',
                'recommended_human_decision_path' => 'CONTINUE_GOVERNED_REVIEW',
                'recommendation' => 'Do not treat the human decision record as ready for governance validation until material conditions and evidence requirements are appropriately resolved or formally governed.',
                'reason' => 'Current governance validation eligibility is NOT_ELIGIBLE_FOR_GOVERNANCE_VALIDATION.',
                'requires_authorized_human_decision' => true,
                'automatic_decision_allowed' => false,
            ];
        }

        $priorityWeight = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
        ];

        usort($recommendations, function (array $a, array $b) use ($priorityWeight) {
            return ($priorityWeight[$b['priority_level']] ?? 0)
                <=> ($priorityWeight[$a['priority_level']] ?? 0);
        });

        $criticalRecommendations = collect($recommendations)
            ->where('priority_level', 'CRITICAL')
            ->count();

        $highRecommendations = collect($recommendations)
            ->where('priority_level', 'HIGH')
            ->count();

        $moderateRecommendations = collect($recommendations)
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisoryRecommendations = collect($recommendations)
            ->where('priority_level', 'ADVISORY')
            ->count();

        $topRecommendation = $recommendations[0] ?? null;

        $recommendationStatus =
            $criticalRecommendations > 0
                ? 'CRITICAL_AUTHORIZED_HUMAN_DECISION_ATTENTION'
                : (
                    $highRecommendations > 0
                    ? 'ELEVATED_AUTHORIZED_HUMAN_DECISION_ATTENTION'
                    : (
                        $moderateRecommendations > 0
                        ? 'MODERATE_AUTHORIZED_HUMAN_DECISION_ATTENTION'
                        : 'CONTROLLED_AUTHORIZED_HUMAN_DECISION_ADVISORY'
                    )
                );

        $managementPriorities = array_values(array_unique(array_filter([
            $topRecommendation['recommendation'] ?? null,
            $decisionBlockingEvidence > 0
                ? "Provide and validate {$decisionBlockingEvidence} decision-blocking evidence requirement(s) through authorized human governance."
                : null,
            $blockingConditions > 0
                ? "Resolve or formally govern {$blockingConditions} blocking human decision condition(s) before unrestricted approval is considered."
                : null,
            $decisionRiskScore >= 70
                ? 'Maintain elevated authorized human governance oversight while strategic plan human decision risk remains high.'
                : null,
            $decisionEligibilityScore <= 0
                ? 'Keep human governance review eligibility separate from unrestricted approval readiness.'
                : null,
            'Ensure the final strategic plan decision is made, justified, and documented only by an authorized human governance decision-maker.',
            'Preserve evidence quality, traceability, human authorization, governance validation, safety controls, and authority separation throughout final strategic plan decision progression.',
        ])));

        $recommendationFindings = [
            count($recommendations).' authorized human strategic plan decision recommendation(s) are currently generated.',
            "{$criticalRecommendations} critical authorized human decision recommendation(s) are currently generated.",
            "{$highRecommendations} high authorized human decision recommendation(s) are currently generated.",
            "{$moderateRecommendations} moderate authorized human decision recommendation(s) are currently generated.",
            "{$advisoryRecommendations} advisory authorized human decision recommendation(s) are currently generated.",
            'Current authorized human decision recommendation status is '.$recommendationStatus.'.',
            'Current unrestricted approval eligibility is '.($eligibilityState['unrestricted_approval_eligibility'] ?? 'UNKNOWN').'.',
            'Current conditional approval eligibility is '.($eligibilityState['conditional_approval_eligibility'] ?? 'UNKNOWN').'.',
            'Current governance validation eligibility is '.($eligibilityState['governance_validation_eligibility'] ?? 'UNKNOWN').'.',
            "{$blockingConditions} blocking human decision condition(s) remain active.",
            "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted approval.",
            "Current human strategic plan decision risk score is {$decisionRiskScore}.",
            "Current human decision readiness score is {$decisionReadinessScore}.",
            "Current combined resolution score is {$combinedResolutionScore}.",
            'Top authorized human decision recommendation is '.($topRecommendation['recommendation_code'] ?? 'NONE').'.',
            'Authorized human decision recommendation intelligence remains advisory and does not make or record the final strategic plan governance decision.',
        ];

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_AUTHORIZED_HUMAN_DECISION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_human_decision_id' => $humanDecision->id,
            'human_decision_code' => $humanDecision->human_decision_code,
            'strategic_plan_decision_id' => $humanDecision->strategic_plan_decision_id,
            'strategic_plan_id' => $humanDecision->strategic_plan_id,
            'strategic_snapshot_id' => $humanDecision->strategic_snapshot_id,
            'operational_snapshot_id' => $humanDecision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $humanDecision->lifecycle_snapshot_id,
            'decision_scope' => $humanDecision->decision_scope,
            'resident_id' => $humanDecision->resident_id,

            'recommendation_state' => [
                'recommendation_mode' => 'AUTHORIZED_HUMAN_GOVERNANCE_DECISION_ADVISORY',
                'recommendation_status' => $recommendationStatus,
                'human_management_attention_required' =>
                    $highRecommendations > 0 || $criticalRecommendations > 0,
                'immediate_human_intervention_required' =>
                    $criticalRecommendations > 0,
                'governance_integrity_intact' =>
                    ($safetyState['governance_integrity_intact'] ?? false) === true,
                'final_human_decision_recorded' =>
                    (bool) ($humanDecisionState['final_human_decision_recorded'] ?? false),
            ],

            'recommendation_summary' => [
                'total_recommendations' => count($recommendations),
                'critical_recommendations' => $criticalRecommendations,
                'high_recommendations' => $highRecommendations,
                'moderate_recommendations' => $moderateRecommendations,
                'advisory_recommendations' => $advisoryRecommendations,
                'top_recommendation_code' => $topRecommendation['recommendation_code'] ?? null,
                'top_recommendation_priority' => $topRecommendation['priority_level'] ?? null,
                'top_recommended_human_decision_path' =>
                    $topRecommendation['recommended_human_decision_path'] ?? null,
            ],

            'top_recommendation' => $topRecommendation,

            'recommendations' => $recommendations,

            'authorized_human_decision_eligibility_context' => $eligibilityState,

            'human_decision_safety_context' => $safetyState,

            'decision_path_context' => $decisionPaths,

            'condition_context' => $conditionContext,

            'evidence_context' => $evidenceContext,

            'resolution_context' => $resolutionContext,

            'readiness_context' => [
                'human_decision_readiness' =>
                    $humanDecisionState['human_decision_readiness'] ?? null,
                'human_decision_readiness_score' => $decisionReadinessScore,
                'decision_eligibility_score' => $decisionEligibilityScore,
                'decision_risk_level' =>
                    $humanDecisionState['decision_risk_level'] ?? null,
                'decision_risk_score' => $decisionRiskScore,
                'human_decision_completion_score' =>
                    $state['completion_context']['human_decision_completion_score'] ?? null,
            ],

            'review_context' => $state['review_context'] ?? [],

            'governance_context' => $state['governance_context'] ?? [],

            'recommendation_findings' => $recommendationFindings,

            'management_priorities' => $managementPriorities,

            'authorized_human_decision_recommendation_guardrails' => [
                'authorized_human_decision_recommendation_intelligence_enabled' => true,

                'recommendation_is_final_human_decision' => false,
                'recommendation_makes_governance_decision' => false,
                'recommendation_records_governance_decision' => false,

                'recommendation_approves_strategic_plan' => false,
                'recommendation_rejects_strategic_plan' => false,
                'recommendation_defers_strategic_plan' => false,
                'recommendation_accepts_governance_risk' => false,
                'recommendation_activates_strategic_plan' => false,

                'recommended_path_is_governance_decision' => false,
                'conditional_approval_recommendation_is_approval' => false,
                'rejection_review_recommendation_is_rejection' => false,
                'deferral_recommendation_is_deferral' => false,
                'risk_acceptance_recommendation_accepts_risk' => false,

                'recommendation_changes_human_decision_status' => false,
                'recommendation_changes_final_human_decision' => false,
                'recommendation_changes_plan_status' => false,
                'recommendation_changes_action_state' => false,
                'recommendation_changes_priority' => false,
                'recommendation_changes_eligibility_record' => false,

                'recommendation_resolves_conditions' => false,
                'recommendation_resolves_dependencies' => false,
                'recommendation_validates_evidence' => false,
                'recommendation_completes_governance_validation' => false,

                'recommendation_priority_authorizes_approval' => false,
                'recommendation_priority_authorizes_rejection' => false,
                'recommendation_priority_authorizes_execution' => false,

                'recommendation_authorizes_ai_change' => false,
                'recommendation_authorizes_execution' => false,
                'recommendation_authorizes_deployment' => false,
                'recommendation_authorizes_rollback' => false,
                'recommendation_authorizes_clinical_action' => false,

                'recommendation_overrides_human_review' => false,
                'recommendation_overrides_governance_validation' => false,
                'recommendation_overrides_evidence_requirements' => false,

                'automatic_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'final_decision_authority_reserved_for_authorized_human' => true,
                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Authorized human decision recommendation intelligence converts human decision state, eligibility, safety restrictions, unresolved conditions, evidence requirements, resolution progress, readiness, and decision risk into ranked advisory recommendations for authorized human governance. Recommendations may identify governance pathways for human consideration, but they do not constitute approval, rejection, conditional approval, deferral, risk acceptance, governance validation, or any other final governance decision. The intelligence does not resolve conditions or dependencies, validate evidence, alter decision or plan state, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}