<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceExecutiveStrategicPlanDecisionIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanDecisionStateIntelligenceEngine $decisionStateEngine,
        protected AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine $eligibilityRiskEngine,
        protected AIGovernanceStrategicPlanHumanDecisionRecommendationIntelligenceEngine $recommendationEngine,
    ) {
    }

    public function analyze(?int $decisionId = null): array
    {
        $decision = $decisionId
            ? AIGovernanceStrategicPlanDecision::find($decisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_DECISION_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan decision record is currently available for executive decision intelligence.',
            ];
        }

        $decisionStateResult = $this->decisionStateEngine->analyze($decision->id);
        $eligibilityRiskResult = $this->eligibilityRiskEngine->analyze($decision->id);
        $recommendationResult = $this->recommendationEngine->analyze($decision->id);

        if (
            !($decisionStateResult['analysis_completed'] ?? false) ||
            !($eligibilityRiskResult['analysis_completed'] ?? false) ||
            !($recommendationResult['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_EXECUTIVE_STRATEGIC_PLAN_DECISION_INTELLIGENCE_UNAVAILABLE',
                'strategic_plan_decision_id' => $decision->id,
                'message' => 'One or more prerequisite strategic plan decision intelligence layers are unavailable.',
            ];
        }

        $decisionState = $decisionStateResult['decision_state'] ?? [];
        $conditionContext = $decisionStateResult['condition_context'] ?? [];
        $evidenceContext = $decisionStateResult['evidence_context'] ?? [];
        $dependencyContext = $decisionStateResult['dependency_context'] ?? [];
        $planningContext = $decisionStateResult['planning_context'] ?? [];
        $reviewContext = $decisionStateResult['review_context'] ?? [];

        $eligibilityState = $eligibilityRiskResult['human_decision_eligibility_state'] ?? [];
        $humanDecisionRiskState = $eligibilityRiskResult['human_decision_risk_state'] ?? [];
        $eligibilitySummary = $eligibilityRiskResult['eligibility_summary'] ?? [];
        $conditionEvidenceContext = $eligibilityRiskResult['condition_evidence_context'] ?? [];
        $dominantOpenCondition = $eligibilityRiskResult['dominant_open_condition'] ?? null;
        $dominantEvidenceRequirement = $eligibilityRiskResult['dominant_evidence_requirement'] ?? null;

        $recommendationState = $recommendationResult['recommendation_state'] ?? [];
        $recommendationSummary = $recommendationResult['recommendation_summary'] ?? [];
        $topRecommendation = $recommendationResult['top_recommendation'] ?? null;

        $decisionReviewReadinessScore = (float) (
            $decisionState['decision_review_readiness_score'] ?? 0
        );

        $humanDecisionEligibilityScore = (float) (
            $eligibilityState['human_decision_eligibility_score'] ?? 0
        );

        $decisionEvidenceReadinessScore = (float) (
            $conditionEvidenceContext['decision_evidence_readiness_score'] ?? 0
        );

        $planningReadinessScore = (float) (
            $planningContext['planning_readiness_score']
            ?? $decision->planning_readiness_score
            ?? 0
        );

        $dependencyAdjustedFeasibilityScore = (float) (
            $dependencyContext['dependency_adjusted_feasibility_score']
            ?? $decision->dependency_adjusted_feasibility_score
            ?? 0
        );

        $humanDecisionRiskScore = (float) (
            $humanDecisionRiskState['human_decision_risk_score'] ?? 0
        );

        $conditionPressureScore = (float) (
            $conditionEvidenceContext['condition_pressure_score'] ?? 0
        );

        $planRiskScore = (float) (
            $planningContext['plan_risk_score']
            ?? $decision->plan_risk_score
            ?? 0
        );

        $blockingConditions = (int) (
            $eligibilitySummary['blocking_conditions'] ?? 0
        );

        $constrainingConditions = (int) (
            $eligibilitySummary['constraining_conditions'] ?? 0
        );

        $outstandingEvidenceItems = (int) (
            $eligibilitySummary['outstanding_evidence_items'] ?? 0
        );

        $decisionBlockingEvidenceItems = (int) (
            $eligibilitySummary['decision_blocking_evidence_items'] ?? 0
        );

        $blockingDependencies = (int) (
            $eligibilitySummary['blocking_dependency_count']
            ?? $dependencyContext['blocking_dependency_count']
            ?? 0
        );

        $constrainingDependencies = (int) (
            $eligibilitySummary['constraining_dependency_count']
            ?? $dependencyContext['constraining_dependency_count']
            ?? 0
        );

        $criticalOpenConditions = (int) (
            $eligibilitySummary['critical_open_conditions'] ?? 0
        );

        $criticalOutstandingEvidenceItems = (int) (
            $eligibilitySummary['critical_outstanding_evidence_items'] ?? 0
        );

        $baseExecutiveScore = round(
            (
                $decisionReviewReadinessScore +
                $humanDecisionEligibilityScore +
                $decisionEvidenceReadinessScore +
                $planningReadinessScore +
                $dependencyAdjustedFeasibilityScore
            ) / 5,
            2
        );

        $decisionPressureScore = round(
            (
                $humanDecisionRiskScore +
                $conditionPressureScore +
                $planRiskScore
            ) / 3,
            2
        );

        $pressureAdjustment = round(
            $decisionPressureScore * 0.10,
            2
        );

        $executiveStrategicPlanDecisionScore = round(
            max(0, min(100, $baseExecutiveScore - $pressureAdjustment)),
            2
        );

        $executiveReadiness = $this->classifyExecutiveReadiness(
            $executiveStrategicPlanDecisionScore
        );

        $executiveConfidence = $this->classifyExecutiveConfidence(
            $executiveStrategicPlanDecisionScore,
            $blockingConditions,
            $decisionBlockingEvidenceItems
        );

        $immediateEscalationRequired =
            $criticalOpenConditions > 0 ||
            $criticalOutstandingEvidenceItems > 0;

        $managementEscalationRecommended =
            $immediateEscalationRequired ||
            $blockingConditions > 0 ||
            $decisionBlockingEvidenceItems > 0 ||
            $blockingDependencies > 0 ||
            $humanDecisionRiskScore >= 70;

        $executiveStatus = $this->classifyExecutiveStatus(
            $immediateEscalationRequired,
            $humanDecisionRiskScore,
            $blockingConditions,
            $decisionBlockingEvidenceItems,
            $executiveStrategicPlanDecisionScore
        );

        $executiveAttentionItems = $this->buildExecutiveAttentionItems(
            $blockingConditions,
            $constrainingConditions,
            $outstandingEvidenceItems,
            $decisionBlockingEvidenceItems,
            $blockingDependencies,
            $constrainingDependencies,
            $decisionReviewReadinessScore,
            $humanDecisionEligibilityScore,
            $decisionEvidenceReadinessScore,
            $planningReadinessScore,
            $dependencyAdjustedFeasibilityScore,
            $humanDecisionRiskScore
        );

        $executivePriorities = $this->buildExecutivePriorities(
            $recommendationResult['management_priorities'] ?? [],
            $topRecommendation,
            $dominantOpenCondition,
            $dominantEvidenceRequirement
        );

        $executiveSummary = $this->buildExecutiveSummary(
            $decision,
            $executiveStatus,
            $executiveReadiness,
            $executiveStrategicPlanDecisionScore,
            $humanDecisionRiskState,
            $eligibilityState,
            $blockingConditions,
            $decisionBlockingEvidenceItems
        );

        $executiveFindings = [
            "Executive strategic plan decision intelligence is based on decision record {$decision->id}.",
            "Current executive strategic plan decision status is {$executiveStatus}.",
            "Current executive decision readiness is {$executiveReadiness}.",
            "Current executive decision confidence is {$executiveConfidence}.",
            "Current executive strategic plan decision score is {$executiveStrategicPlanDecisionScore}.",
            'Current prepared decision is ' . ($decisionState['prepared_decision'] ?? $decision->decision ?? 'UNKNOWN') . '.',
            'Current strategic decision state is ' . ($decisionState['strategic_decision_state'] ?? 'UNKNOWN') . '.',
            'Current human governance review eligibility is ' . ($eligibilityState['human_review_eligibility'] ?? 'UNKNOWN') . '.',
            'Current human approval eligibility is ' . ($eligibilityState['human_approval_eligibility'] ?? 'UNKNOWN') . '.',
            'Current human decision eligibility is ' . ($eligibilityState['human_decision_eligibility'] ?? 'UNKNOWN') . '.',
            "Current human decision eligibility score is {$humanDecisionEligibilityScore}.",
            'Current human decision risk level is ' . ($humanDecisionRiskState['human_decision_risk_level'] ?? 'UNKNOWN') . " with score {$humanDecisionRiskScore}.",
            "{$blockingConditions} blocking human-decision condition(s) remain active.",
            "{$constrainingConditions} constraining human-decision condition(s) remain active.",
            "{$decisionBlockingEvidenceItems} outstanding evidence requirement(s) currently block unrestricted approval.",
            "{$blockingDependencies} blocking strategic planning dependency condition(s) remain represented.",
            "Current decision review readiness score is {$decisionReviewReadinessScore}.",
            "Current decision evidence readiness score is {$decisionEvidenceReadinessScore}.",
            "Current dependency-adjusted feasibility score is {$dependencyAdjustedFeasibilityScore}.",
            'Management escalation recommendation is ' . ($managementEscalationRecommended ? 'ACTIVE' : 'NOT_REQUIRED') . '.',
            'Immediate escalation requirement is ' . ($immediateEscalationRequired ? 'REQUIRED' : 'NOT_REQUIRED') . '.',
            'Executive strategic plan decision intelligence remains advisory and human governed.',
        ];

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_EXECUTIVE_STRATEGIC_PLAN_DECISION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,
            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,
            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'executive_strategic_plan_decision_state' => [
                'executive_strategic_plan_decision_status' => $executiveStatus,
                'executive_readiness' => $executiveReadiness,
                'executive_confidence' => $executiveConfidence,
                'executive_strategic_plan_decision_score' => $executiveStrategicPlanDecisionScore,
                'governance_integrity_intact' => true,
                'management_escalation_recommended' => $managementEscalationRecommended,
                'immediate_escalation_required' => $immediateEscalationRequired,
                'final_human_decision_recorded' => (bool) (
                    $decisionState['final_human_decision_recorded'] ?? false
                ),
            ],

            'executive_summary' => $executiveSummary,

            'decision_state_context' => [
                'decision_status' => $decisionState['decision_status'] ?? $decision->decision_status,
                'decision_mode' => $decisionState['decision_mode'] ?? $decision->decision_mode,
                'prepared_decision' => $decisionState['prepared_decision'] ?? $decision->decision,
                'strategic_decision_state' => $decisionState['strategic_decision_state'] ?? null,
                'decision_review_readiness' => $decisionState['decision_review_readiness'] ?? null,
                'decision_review_readiness_score' => $decisionReviewReadinessScore,
                'decision_confidence' => $decisionState['decision_confidence'] ?? null,
                'human_resolution_readiness' => $decisionState['human_resolution_readiness'] ?? null,
                'eligible_for_human_resolution' => (bool) (
                    $decisionState['eligible_for_human_resolution'] ?? false
                ),
                'evidence_readiness' => $decisionState['evidence_readiness'] ?? null,
                'final_human_decision_recorded' => (bool) (
                    $decisionState['final_human_decision_recorded'] ?? false
                ),
            ],

            'human_decision_eligibility_context' => $eligibilityState,

            'human_decision_risk_context' => $humanDecisionRiskState,

            'condition_evidence_context' => [
                'open_conditions' => (int) ($eligibilitySummary['open_conditions'] ?? 0),
                'blocking_conditions' => $blockingConditions,
                'constraining_conditions' => $constrainingConditions,
                'critical_open_conditions' => $criticalOpenConditions,
                'high_open_conditions' => (int) ($eligibilitySummary['high_open_conditions'] ?? 0),
                'outstanding_evidence_items' => $outstandingEvidenceItems,
                'decision_blocking_evidence_items' => $decisionBlockingEvidenceItems,
                'condition_evidence_status' => $conditionEvidenceContext['condition_evidence_status'] ?? null,
                'decision_block_status' => $conditionEvidenceContext['decision_block_status'] ?? null,
                'condition_pressure_score' => $conditionPressureScore,
                'condition_resolution_score' => (float) (
                    $conditionEvidenceContext['condition_resolution_score'] ?? 0
                ),
                'evidence_readiness_score' => (float) (
                    $conditionEvidenceContext['evidence_readiness_score'] ?? 0
                ),
                'decision_evidence_readiness_score' => $decisionEvidenceReadinessScore,
                'human_resolution_readiness' => $conditionEvidenceContext['human_resolution_readiness'] ?? null,
            ],

            'dependency_context' => [
                'dependency_feasibility_status' => $dependencyContext['dependency_feasibility_status']
                    ?? $decision->dependency_feasibility_status,
                'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibilityScore,
                'blocking_dependency_count' => $blockingDependencies,
                'constraining_dependency_count' => $constrainingDependencies,
            ],

            'planning_risk_context' => [
                'planning_readiness' => $planningContext['planning_readiness']
                    ?? $decision->planning_readiness,
                'planning_readiness_score' => $planningReadinessScore,
                'plan_risk_level' => $planningContext['plan_risk_level']
                    ?? $decision->plan_risk_level,
                'plan_risk_score' => $planRiskScore,
                'decision_priority' => $planningContext['decision_priority']
                    ?? $decision->decision_priority,
                'decision_priority_score' => (float) (
                    $planningContext['decision_priority_score']
                    ?? $decision->decision_priority_score
                    ?? 0
                ),
                'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibilityScore,
            ],

            'recommendation_context' => [
                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => (int) ($recommendationSummary['total_recommendations'] ?? 0),
                'critical_recommendations' => (int) ($recommendationSummary['critical_recommendations'] ?? 0),
                'high_recommendations' => (int) ($recommendationSummary['high_recommendations'] ?? 0),
                'moderate_recommendations' => (int) ($recommendationSummary['moderate_recommendations'] ?? 0),
                'advisory_recommendations' => (int) ($recommendationSummary['advisory_recommendations'] ?? 0),
                'top_recommendation' => $topRecommendation,
            ],

            'review_context' => $reviewContext,

            'dominant_open_condition' => $dominantOpenCondition,

            'dominant_evidence_requirement' => $dominantEvidenceRequirement,

            'score_components' => [
                'decision_review_readiness' => $decisionReviewReadinessScore,
                'human_decision_eligibility' => $humanDecisionEligibilityScore,
                'decision_evidence_readiness' => $decisionEvidenceReadinessScore,
                'planning_readiness' => $planningReadinessScore,
                'dependency_adjusted_feasibility' => $dependencyAdjustedFeasibilityScore,
                'base_executive_score' => $baseExecutiveScore,
                'human_decision_risk_pressure' => $humanDecisionRiskScore,
                'condition_pressure' => $conditionPressureScore,
                'strategic_plan_risk_pressure' => $planRiskScore,
                'decision_pressure_score' => $decisionPressureScore,
                'pressure_adjustment' => $pressureAdjustment,
                'executive_strategic_plan_decision_score' => $executiveStrategicPlanDecisionScore,
            ],

            'executive_attention_items' => $executiveAttentionItems,

            'executive_priorities' => $executivePriorities,

            'executive_findings' => $executiveFindings,

            'executive_guardrails' => [
                'executive_strategic_plan_decision_intelligence_enabled' => true,

                'executive_decision_status_is_human_final_decision' => false,
                'executive_readiness_is_governance_approval' => false,
                'executive_readiness_is_governance_rejection' => false,
                'executive_readiness_is_plan_activation' => false,
                'executive_score_is_staff_performance_rating' => false,

                'executive_intelligence_records_final_decision' => false,
                'executive_intelligence_changes_decision_status' => false,
                'executive_intelligence_changes_plan_status' => false,
                'executive_intelligence_changes_action_state' => false,
                'executive_intelligence_changes_priority' => false,
                'executive_intelligence_changes_eligibility' => false,

                'executive_intelligence_resolves_conditions' => false,
                'executive_intelligence_resolves_dependencies' => false,
                'executive_intelligence_validates_evidence' => false,

                'management_escalation_is_automatic_notification' => false,

                'executive_readiness_authorizes_approval' => false,
                'executive_readiness_authorizes_rejection' => false,
                'executive_score_authorizes_approval' => false,
                'executive_score_authorizes_rejection' => false,
                'human_decision_risk_authorizes_automation' => false,
                'blocking_condition_authorizes_automation' => false,
                'blocking_evidence_authorizes_automation' => false,

                'executive_intelligence_authorizes_ai_change' => false,
                'executive_intelligence_authorizes_execution' => false,
                'executive_intelligence_authorizes_deployment' => false,
                'executive_intelligence_authorizes_rollback' => false,
                'executive_intelligence_authorizes_clinical_action' => false,

                'executive_intelligence_overrides_human_review' => false,
                'executive_intelligence_overrides_evidence_requirements' => false,

                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Executive strategic plan decision intelligence consolidates strategic plan decision state, human decision eligibility, decision risk, unresolved conditions, evidence requirements, planning readiness, dependency feasibility, recommendations, and governance review status for authorized human executive governance oversight only. Executive status, readiness, scores, escalation recommendations, attention items, and priorities do not make or record the final governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, activate planning work, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function classifyExecutiveReadiness(float $score): string
    {
        return match (true) {
            $score >= 80 => 'STRONG_EXECUTIVE_DECISION_READINESS',
            $score >= 65 => 'GOOD_EXECUTIVE_DECISION_READINESS',
            $score >= 50 => 'MODERATE_EXECUTIVE_DECISION_READINESS',
            $score >= 30 => 'LIMITED_EXECUTIVE_DECISION_READINESS',
            default => 'VERY_LIMITED_EXECUTIVE_DECISION_READINESS',
        };
    }

    private function classifyExecutiveConfidence(
        float $score,
        int $blockingConditions,
        int $blockingEvidence
    ): string {
        if ($blockingConditions > 0 || $blockingEvidence > 0) {
            return $score >= 30
                ? 'VERY_LIMITED'
                : 'EXTREMELY_LIMITED';
        }

        return match (true) {
            $score >= 80 => 'HIGH',
            $score >= 65 => 'GOOD',
            $score >= 50 => 'MODERATE',
            $score >= 30 => 'LIMITED',
            default => 'VERY_LIMITED',
        };
    }

    private function classifyExecutiveStatus(
        bool $immediateEscalationRequired,
        float $humanDecisionRiskScore,
        int $blockingConditions,
        int $blockingEvidence,
        float $executiveScore
    ): string {
        if ($immediateEscalationRequired) {
            return 'CRITICAL_HUMAN_GOVERNANCE_DECISION_ATTENTION';
        }

        if (
            $humanDecisionRiskScore >= 70 ||
            $blockingConditions > 0 ||
            $blockingEvidence > 0 ||
            $executiveScore < 30
        ) {
            return 'CONTROLLED_HIGH_HUMAN_DECISION_PRESSURE';
        }

        if (
            $humanDecisionRiskScore >= 50 ||
            $executiveScore < 50
        ) {
            return 'CONTROLLED_MODERATE_HUMAN_DECISION_PRESSURE';
        }

        return 'CONTROLLED_HUMAN_DECISION_GOVERNANCE';
    }

    private function buildExecutiveAttentionItems(
        int $blockingConditions,
        int $constrainingConditions,
        int $outstandingEvidenceItems,
        int $decisionBlockingEvidenceItems,
        int $blockingDependencies,
        int $constrainingDependencies,
        float $decisionReviewReadinessScore,
        float $humanDecisionEligibilityScore,
        float $decisionEvidenceReadinessScore,
        float $planningReadinessScore,
        float $dependencyAdjustedFeasibilityScore,
        float $humanDecisionRiskScore
    ): array {
        $items = [];

        if ($blockingConditions > 0) {
            $items[] = "{$blockingConditions} blocking human-decision condition(s) remain active.";
        }

        if ($constrainingConditions > 0) {
            $items[] = "{$constrainingConditions} constraining human-decision condition(s) remain active.";
        }

        if ($decisionBlockingEvidenceItems > 0) {
            $items[] = "{$decisionBlockingEvidenceItems} outstanding evidence requirement(s) currently block unrestricted strategic plan approval.";
        }

        if ($outstandingEvidenceItems > 0) {
            $items[] = "{$outstandingEvidenceItems} required evidence item(s) remain outstanding.";
        }

        if ($blockingDependencies > 0) {
            $items[] = "{$blockingDependencies} blocking strategic planning dependency condition(s) remain represented.";
        }

        if ($constrainingDependencies > 0) {
            $items[] = "{$constrainingDependencies} constraining strategic planning dependency condition(s) remain represented.";
        }

        if ($decisionReviewReadinessScore < 50) {
            $items[] = "Human decision review readiness remains limited at score {$decisionReviewReadinessScore}.";
        }

        if ($humanDecisionEligibilityScore < 50) {
            $items[] = "Human decision eligibility remains materially limited at score {$humanDecisionEligibilityScore}.";
        }

        if ($decisionEvidenceReadinessScore < 50) {
            $items[] = "Decision evidence readiness remains limited at score {$decisionEvidenceReadinessScore}.";
        }

        if ($planningReadinessScore < 50) {
            $items[] = "Strategic planning readiness remains limited at score {$planningReadinessScore}.";
        }

        if ($dependencyAdjustedFeasibilityScore < 50) {
            $items[] = "Dependency-adjusted strategic plan feasibility remains limited at score {$dependencyAdjustedFeasibilityScore}.";
        }

        if ($humanDecisionRiskScore >= 70) {
            $items[] = "Human strategic plan decision risk remains high at score {$humanDecisionRiskScore}.";
        }

        return $items;
    }

    private function buildExecutivePriorities(
        array $managementPriorities,
        ?array $topRecommendation,
        ?array $dominantOpenCondition,
        ?array $dominantEvidenceRequirement
    ): array {
        $priorities = [];

        if (!empty($topRecommendation['recommendation'])) {
            $priorities[] = $topRecommendation['recommendation'];
        }

        if (!empty($dominantOpenCondition['condition_code'])) {
            $priorities[] =
                'Address the dominant open human decision condition ' .
                $dominantOpenCondition['condition_code'] .
                ' through the established authorized human governance process.';
        }

        if (!empty($dominantEvidenceRequirement['evidence_code'])) {
            $priorities[] =
                'Provide and validate the dominant outstanding evidence requirement ' .
                $dominantEvidenceRequirement['evidence_code'] .
                ' through authorized human governance.';
        }

        foreach ($managementPriorities as $priority) {
            if (is_string($priority) && $priority !== '') {
                $priorities[] = $priority;
            }
        }

        $priorities[] =
            'Ensure the final strategic plan decision is made, justified, and documented only by authorized human governance.';

        $priorities[] =
            'Preserve evidence quality, human review, governance validation, traceability, safety controls, and authority separation throughout final decision progression.';

        return array_values(array_unique($priorities));
    }

    private function buildExecutiveSummary(
        AIGovernanceStrategicPlanDecision $decision,
        string $executiveStatus,
        string $executiveReadiness,
        float $executiveScore,
        array $humanDecisionRiskState,
        array $eligibilityState,
        int $blockingConditions,
        int $blockingEvidence
    ): string {
        $humanDecisionRiskLevel =
            $humanDecisionRiskState['human_decision_risk_level'] ?? 'UNKNOWN';

        $humanDecisionRiskScore =
            $humanDecisionRiskState['human_decision_risk_score'] ?? 0;

        $humanReviewEligibility =
            $eligibilityState['human_review_eligibility'] ?? 'UNKNOWN';

        $humanApprovalEligibility =
            $eligibilityState['human_approval_eligibility'] ?? 'UNKNOWN';

        return
            "Strategic plan decision {$decision->decision_code} is currently {$executiveStatus} " .
            "with executive readiness {$executiveReadiness} and executive strategic plan decision score {$executiveScore}. " .
            "Human governance review eligibility is {$humanReviewEligibility}, while human approval eligibility is {$humanApprovalEligibility}. " .
            "Human decision risk is {$humanDecisionRiskLevel} with score {$humanDecisionRiskScore}. " .
            "{$blockingConditions} blocking decision condition(s) and {$blockingEvidence} decision-blocking evidence requirement(s) remain active. " .
            "The executive intelligence remains advisory and the final strategic plan decision remains reserved for authorized human governance.";
    }
}