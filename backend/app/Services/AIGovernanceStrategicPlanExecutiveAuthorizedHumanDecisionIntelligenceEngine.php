<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanExecutiveAuthorizedHumanDecisionIntelligenceEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $decision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::findOrFail($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->firstOrFail();

        $stateEngine = app(AIGovernanceStrategicPlanHumanDecisionStateIntelligenceEngine::class);
        $resolutionEngine = app(AIGovernanceStrategicPlanHumanDecisionConditionEvidenceResolutionIntelligenceEngine::class);
        $eligibilityEngine = app(AIGovernanceStrategicPlanAuthorizedHumanDecisionEligibilitySafetyIntelligenceEngine::class);
        $recommendationEngine = app(AIGovernanceStrategicPlanAuthorizedHumanDecisionRecommendationIntelligenceEngine::class);

        $state = $stateEngine->analyze($decision->id);
        $resolution = $resolutionEngine->analyze($decision->id);
        $eligibility = $eligibilityEngine->analyze($decision->id);
        $recommendation = $recommendationEngine->analyze($decision->id);

        $humanDecisionState = $state['human_decision_state'] ?? [];
        $conditionContext = $resolution['condition_context'] ?? $state['condition_context'] ?? [];
        $evidenceContext = $resolution['evidence_context'] ?? $state['evidence_context'] ?? [];
        $resolutionContext = $resolution['resolution_state'] ?? [];
        $eligibilityState = $eligibility['authorized_human_decision_eligibility_state'] ?? [];
        $safetyState = $eligibility['human_decision_safety_state'] ?? [];
        $recommendationState = $recommendation['recommendation_state'] ?? [];
        $recommendationSummary = $recommendation['recommendation_summary'] ?? [];
        $topRecommendation = $recommendation['top_recommendation'] ?? [];

        $blockingConditions = (int) ($conditionContext['blocking_conditions'] ?? 0);
        $constrainingConditions = (int) ($conditionContext['constraining_conditions'] ?? 0);
        $outstandingEvidence = (int) ($evidenceContext['outstanding_evidence_items'] ?? 0);
        $blockingEvidence = (int) ($evidenceContext['decision_blocking_evidence_items'] ?? 0);

        $humanDecisionReadinessScore = (float) ($humanDecisionState['human_decision_readiness_score'] ?? 0);
        $humanDecisionRiskScore = (float) ($humanDecisionState['decision_risk_score'] ?? 0);
        $combinedResolutionScore = (float) ($resolutionContext['combined_resolution_score'] ?? 0);
        $decisionSafetyScore = (float) ($safetyState['decision_safety_score'] ?? 0);
        $completionScore = (float) (($state['completion_context']['human_decision_completion_score'] ?? 0));

        $executiveScore = round(
            ($humanDecisionReadinessScore * 0.25) +
            ($combinedResolutionScore * 0.25) +
            ($decisionSafetyScore * 0.20) +
            ($completionScore * 0.15) +
            ((100 - min(100, max(0, $humanDecisionRiskScore))) * 0.15),
            2
        );

        $finalDecisionRecorded = (bool) ($humanDecisionState['final_human_decision_recorded'] ?? false);
        $governanceValidationCompleted = (bool) ($humanDecisionState['governance_validation_completed'] ?? false);

        if ($finalDecisionRecorded && $governanceValidationCompleted) {
            $executiveStatus = 'HUMAN_DECISION_GOVERNANCE_COMPLETED';
        } elseif ($blockingConditions > 0 || $blockingEvidence > 0) {
            $executiveStatus = 'CONTROLLED_MATERIAL_HUMAN_DECISION_PRESSURE';
        } elseif ($humanDecisionRiskScore >= 70) {
            $executiveStatus = 'ELEVATED_HUMAN_DECISION_RISK';
        } else {
            $executiveStatus = 'CONTROLLED_HUMAN_DECISION_GOVERNANCE';
        }

        if ($executiveScore >= 80) {
            $executiveReadiness = 'HIGH_EXECUTIVE_DECISION_READINESS';
        } elseif ($executiveScore >= 60) {
            $executiveReadiness = 'MODERATE_EXECUTIVE_DECISION_READINESS';
        } elseif ($executiveScore >= 40) {
            $executiveReadiness = 'LIMITED_EXECUTIVE_DECISION_READINESS';
        } elseif ($executiveScore >= 20) {
            $executiveReadiness = 'VERY_LIMITED_EXECUTIVE_DECISION_READINESS';
        } else {
            $executiveReadiness = 'EXTREMELY_LIMITED_EXECUTIVE_DECISION_READINESS';
        }

        if ($executiveScore >= 75) {
            $executiveConfidence = 'HIGH';
        } elseif ($executiveScore >= 50) {
            $executiveConfidence = 'MODERATE';
        } elseif ($executiveScore >= 25) {
            $executiveConfidence = 'LIMITED';
        } elseif ($executiveScore >= 10) {
            $executiveConfidence = 'VERY_LIMITED';
        } else {
            $executiveConfidence = 'EXTREMELY_LIMITED';
        }

        $managementEscalationRecommended =
            $blockingConditions > 0 ||
            $blockingEvidence > 0 ||
            $humanDecisionRiskScore >= 70 ||
            ($recommendationState['human_management_attention_required'] ?? false);

        $immediateEscalationRequired =
            (bool) ($recommendationState['immediate_human_intervention_required'] ?? false);

        $executiveState = [
            'executive_authorized_human_decision_status' => $executiveStatus,
            'executive_readiness' => $executiveReadiness,
            'executive_confidence' => $executiveConfidence,
            'executive_authorized_human_decision_score' => $executiveScore,
            'management_escalation_recommended' => $managementEscalationRecommended,
            'immediate_escalation_required' => $immediateEscalationRequired,
            'final_human_decision_recorded' => $finalDecisionRecorded,
            'governance_validation_completed' => $governanceValidationCompleted,
        ];

        $executiveSummary = [
            'human_decision_status' => $humanDecisionState['human_decision_status'] ?? null,
            'prepared_decision' => $humanDecisionState['prepared_decision'] ?? null,
            'strategic_human_decision_state' => $humanDecisionState['strategic_human_decision_state'] ?? null,
            'human_decision_readiness' => $humanDecisionState['human_decision_readiness'] ?? null,
            'human_decision_readiness_score' => $humanDecisionReadinessScore,
            'decision_risk_level' => $humanDecisionState['decision_risk_level'] ?? null,
            'decision_risk_score' => $humanDecisionRiskScore,
            'blocking_conditions' => $blockingConditions,
            'constraining_conditions' => $constrainingConditions,
            'outstanding_evidence_items' => $outstandingEvidence,
            'decision_blocking_evidence_items' => $blockingEvidence,
            'combined_resolution_score' => $combinedResolutionScore,
            'decision_safety_status' => $safetyState['decision_safety_status'] ?? null,
            'decision_safety_score' => $decisionSafetyScore,
            'unrestricted_approval_eligibility' => $eligibilityState['unrestricted_approval_eligibility'] ?? null,
            'conditional_approval_eligibility' => $eligibilityState['conditional_approval_eligibility'] ?? null,
            'governance_validation_eligibility' => $eligibilityState['governance_validation_eligibility'] ?? null,
            'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
            'total_recommendations' => (int) ($recommendationSummary['total_recommendations'] ?? 0),
            'top_recommendation_code' => $recommendationSummary['top_recommendation_code'] ?? null,
            'top_recommended_human_decision_path' => $recommendationSummary['top_recommended_human_decision_path'] ?? null,
        ];

        $executiveFindings = [
            "Executive authorized human strategic plan decision intelligence is based on human decision record {$decision->id}.",
            "Current executive authorized human decision status is {$executiveStatus}.",
            "Current executive decision readiness is {$executiveReadiness} with score {$executiveScore}.",
            "Current executive decision confidence is {$executiveConfidence}.",
            "Current human decision readiness score is {$humanDecisionReadinessScore}.",
            "Current human decision risk score is {$humanDecisionRiskScore}.",
            "{$blockingConditions} blocking human decision condition(s) remain active.",
            "{$constrainingConditions} constraining human decision condition(s) remain active.",
            "{$outstandingEvidence} evidence requirement(s) remain outstanding.",
            "{$blockingEvidence} outstanding evidence requirement(s) currently block unrestricted approval.",
            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",
            'Current unrestricted approval eligibility is ' . ($eligibilityState['unrestricted_approval_eligibility'] ?? 'UNKNOWN') . '.',
            'Current governance validation eligibility is ' . ($eligibilityState['governance_validation_eligibility'] ?? 'UNKNOWN') . '.',
            'Current top authorized human decision recommendation is ' . ($recommendationSummary['top_recommendation_code'] ?? 'NONE') . '.',
            'Final human strategic plan decision recorded is ' . ($finalDecisionRecorded ? 'YES' : 'NO') . '.',
            'Governance validation completed is ' . ($governanceValidationCompleted ? 'YES' : 'NO') . '.',
            'Executive authorized human decision intelligence remains informational and does not make, record, validate, approve, reject, defer, activate, or execute the final governance decision.',
        ];

        $managementPriorities = [];

        if ($blockingEvidence > 0) {
            $managementPriorities[] = "Provide and validate {$blockingEvidence} decision-blocking evidence requirement(s) through authorized human governance.";
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] = "Resolve or formally govern {$blockingConditions} blocking human decision condition(s) before unrestricted approval is considered.";
        }

        if ($humanDecisionRiskScore >= 70) {
            $managementPriorities[] = 'Maintain elevated authorized human governance oversight while strategic plan decision risk remains high.';
        }

        if (!empty($topRecommendation['recommendation'])) {
            $managementPriorities[] = $topRecommendation['recommendation'];
        }

        $managementPriorities[] = 'Keep executive intelligence, eligibility intelligence, recommendations, and final human decision authority strictly separated.';
        $managementPriorities[] = 'Ensure any final strategic plan decision is explicitly made, justified, and documented by an authorized human governance decision-maker.';
        $managementPriorities[] = 'Preserve evidence quality, traceability, governance validation, safety controls, and authority separation throughout final decision progression.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_EXECUTIVE_AUTHORIZED_HUMAN_DECISION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_human_decision_id' => $decision->id,
            'human_decision_code' => $decision->human_decision_code,
            'strategic_plan_decision_id' => $decision->strategic_plan_decision_id,
            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,
            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'executive_authorized_human_decision_state' => $executiveState,
            'executive_summary' => $executiveSummary,

            'human_decision_state_context' => $humanDecisionState,
            'condition_context' => $conditionContext,
            'evidence_context' => $evidenceContext,
            'resolution_context' => $resolutionContext,
            'authorized_human_decision_eligibility_context' => $eligibilityState,
            'human_decision_safety_context' => $safetyState,
            'recommendation_context' => [
                'recommendation_state' => $recommendationState,
                'recommendation_summary' => $recommendationSummary,
                'top_recommendation' => $topRecommendation,
            ],

            'executive_findings' => $executiveFindings,
            'management_priorities' => array_values(array_unique($managementPriorities)),

            'executive_authorized_human_decision_guardrails' => [
                'executive_authorized_human_decision_intelligence_enabled' => true,

                'executive_intelligence_is_final_human_decision' => false,
                'executive_intelligence_makes_governance_decision' => false,
                'executive_intelligence_records_governance_decision' => false,

                'executive_intelligence_approves_strategic_plan' => false,
                'executive_intelligence_rejects_strategic_plan' => false,
                'executive_intelligence_defers_strategic_plan' => false,
                'executive_intelligence_accepts_governance_risk' => false,
                'executive_intelligence_activates_strategic_plan' => false,

                'executive_score_authorizes_approval' => false,
                'executive_score_authorizes_rejection' => false,
                'executive_score_authorizes_execution' => false,

                'executive_intelligence_changes_human_decision_status' => false,
                'executive_intelligence_changes_final_human_decision' => false,
                'executive_intelligence_changes_plan_status' => false,
                'executive_intelligence_changes_action_state' => false,
                'executive_intelligence_changes_priority' => false,
                'executive_intelligence_changes_eligibility' => false,

                'executive_intelligence_resolves_conditions' => false,
                'executive_intelligence_resolves_dependencies' => false,
                'executive_intelligence_validates_evidence' => false,
                'executive_intelligence_completes_governance_validation' => false,

                'executive_intelligence_authorizes_ai_change' => false,
                'executive_intelligence_authorizes_execution' => false,
                'executive_intelligence_authorizes_deployment' => false,
                'executive_intelligence_authorizes_rollback' => false,
                'executive_intelligence_authorizes_clinical_action' => false,

                'executive_intelligence_overrides_human_review' => false,
                'executive_intelligence_overrides_governance_validation' => false,
                'executive_intelligence_overrides_evidence_requirements' => false,

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

                'message' => 'Executive authorized human strategic plan decision intelligence consolidates human decision state, condition and evidence resolution, decision eligibility, safety restrictions, readiness, risk, and advisory recommendations into an executive governance view. It remains informational and does not constitute or record approval, rejection, conditional approval, deferral, risk acceptance, governance validation, activation, execution, AI modification, deployment, rollback, or clinical action. Final strategic plan decision authority remains reserved exclusively for authorized human governance.',
            ],
        ];
    }
}