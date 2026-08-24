<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanHumanDecisionRecommendationIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine $eligibilityRiskEngine
    ) {
    }

    public function analyze(?int $strategicPlanDecisionId = null): array
    {
        $decision = $this->resolveDecision($strategicPlanDecisionId);

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'message' => 'No AI governance strategic plan decision record is available for recommendation analysis.',
            ];
        }

        $eligibilityRisk = $this->eligibilityRiskEngine->analyze($decision->id);

        if (($eligibilityRisk['analysis_completed'] ?? false) !== true) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'strategic_plan_decision_id' => $decision->id,
                'decision_code' => $decision->decision_code,
                'message' => 'Human decision eligibility and risk intelligence is unavailable.',
            ];
        }

        $eligibilityState = $eligibilityRisk['human_decision_eligibility_state'] ?? [];
        $riskState = $eligibilityRisk['human_decision_risk_state'] ?? [];
        $eligibilitySummary = $eligibilityRisk['eligibility_summary'] ?? [];
        $eligibilityBlockers = $eligibilityRisk['eligibility_blockers'] ?? [];
        $eligibilityConstraints = $eligibilityRisk['eligibility_constraints'] ?? [];
        $conditionEvidenceContext = $eligibilityRisk['condition_evidence_context'] ?? [];
        $planningRiskContext = $eligibilityRisk['planning_risk_context'] ?? [];
        $reviewContext = $eligibilityRisk['review_context'] ?? [];
        $dominantOpenCondition = $eligibilityRisk['dominant_open_condition'] ?? null;
        $dominantEvidenceRequirement = $eligibilityRisk['dominant_evidence_requirement'] ?? null;

        $recommendations = $this->buildRecommendations(
            $eligibilityState,
            $riskState,
            $eligibilitySummary,
            $eligibilityBlockers,
            $eligibilityConstraints,
            $conditionEvidenceContext,
            $planningRiskContext,
            $dominantOpenCondition,
            $dominantEvidenceRequirement
        );

        $recommendations = $this->sortRecommendations($recommendations);

        $recommendationSummary = $this->buildRecommendationSummary($recommendations);

        $topRecommendation = $recommendations[0] ?? null;

        $recommendationStatus = $this->determineRecommendationStatus(
            $recommendationSummary,
            $riskState,
            $eligibilityState
        );

        $humanManagementAttentionRequired =
            ($recommendationSummary['critical_recommendations'] ?? 0) > 0
            || ($recommendationSummary['high_recommendations'] ?? 0) > 0
            || ($riskState['human_management_attention_required'] ?? false) === true;

        $immediateHumanInterventionRequired =
            ($riskState['immediate_human_intervention_required'] ?? false) === true
            || ($recommendationSummary['critical_recommendations'] ?? 0) > 0;

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,
            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,
            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'recommendation_state' => [
                'recommendation_mode' => 'HUMAN_STRATEGIC_PLAN_DECISION_ADVISORY',
                'recommendation_status' => $recommendationStatus,
                'human_management_attention_required' => $humanManagementAttentionRequired,
                'immediate_human_intervention_required' => $immediateHumanInterventionRequired,
                'governance_integrity_intact' => true,
            ],

            'recommendation_summary' => $recommendationSummary,

            'top_recommendation' => $topRecommendation,

            'recommendations' => $recommendations,

            'human_decision_eligibility_context' => [
                'human_review_eligibility' => $eligibilityState['human_review_eligibility'] ?? null,
                'human_approval_eligibility' => $eligibilityState['human_approval_eligibility'] ?? null,
                'human_decision_eligibility' => $eligibilityState['human_decision_eligibility'] ?? null,
                'human_decision_eligibility_score' => $eligibilityState['human_decision_eligibility_score'] ?? null,
                'human_decision_readiness' => $eligibilityState['human_decision_readiness'] ?? null,
                'eligible_for_human_resolution' => $eligibilityState['eligible_for_human_resolution'] ?? false,
                'final_human_decision_recorded' => $eligibilityState['final_human_decision_recorded'] ?? false,
            ],

            'human_decision_risk_context' => [
                'human_decision_risk_level' => $riskState['human_decision_risk_level'] ?? null,
                'human_decision_risk_score' => $riskState['human_decision_risk_score'] ?? null,
                'risk_control_status' => $riskState['risk_control_status'] ?? null,
                'governance_integrity_intact' => $riskState['governance_integrity_intact'] ?? true,
            ],

            'eligibility_context' => [
                'open_conditions' => $eligibilitySummary['open_conditions'] ?? 0,
                'blocking_conditions' => $eligibilitySummary['blocking_conditions'] ?? 0,
                'constraining_conditions' => $eligibilitySummary['constraining_conditions'] ?? 0,
                'critical_open_conditions' => $eligibilitySummary['critical_open_conditions'] ?? 0,
                'high_open_conditions' => $eligibilitySummary['high_open_conditions'] ?? 0,
                'outstanding_evidence_items' => $eligibilitySummary['outstanding_evidence_items'] ?? 0,
                'decision_blocking_evidence_items' => $eligibilitySummary['decision_blocking_evidence_items'] ?? 0,
                'blocking_dependency_count' => $eligibilitySummary['blocking_dependency_count'] ?? 0,
                'constraining_dependency_count' => $eligibilitySummary['constraining_dependency_count'] ?? 0,
            ],

            'condition_evidence_context' => $conditionEvidenceContext,

            'planning_risk_context' => $planningRiskContext,

            'review_context' => $reviewContext,

            'dominant_open_condition' => $dominantOpenCondition,

            'dominant_evidence_requirement' => $dominantEvidenceRequirement,

            'recommendation_findings' => $this->buildFindings(
                $recommendations,
                $recommendationSummary,
                $recommendationStatus,
                $eligibilityState,
                $riskState,
                $eligibilitySummary,
                $conditionEvidenceContext,
                $planningRiskContext,
                $topRecommendation
            ),

            'management_priorities' => $this->buildManagementPriorities(
                $recommendations,
                $dominantOpenCondition,
                $dominantEvidenceRequirement
            ),

            'human_decision_recommendation_guardrails' => [
                'human_decision_recommendation_intelligence_enabled' => true,

                'recommendation_is_human_final_decision' => false,
                'recommendation_is_governance_approval' => false,
                'recommendation_is_governance_rejection' => false,
                'recommendation_is_plan_activation' => false,

                'recommendation_changes_decision_status' => false,
                'recommendation_changes_plan_status' => false,
                'recommendation_changes_action_state' => false,
                'recommendation_changes_priority' => false,
                'recommendation_changes_eligibility' => false,

                'recommendation_resolves_conditions' => false,
                'recommendation_resolves_dependencies' => false,
                'recommendation_validates_evidence' => false,

                'recommendation_priority_authorizes_approval' => false,
                'recommendation_priority_authorizes_rejection' => false,
                'recommendation_priority_authorizes_execution' => false,

                'eligibility_status_authorizes_approval' => false,
                'risk_level_authorizes_approval' => false,
                'risk_level_authorizes_rejection' => false,

                'high_risk_authorizes_automation' => false,
                'blocking_condition_authorizes_automation' => false,
                'blocking_evidence_authorizes_automation' => false,

                'recommendation_authorizes_ai_change' => false,
                'recommendation_authorizes_execution' => false,
                'recommendation_authorizes_deployment' => false,
                'recommendation_authorizes_rollback' => false,
                'recommendation_authorizes_clinical_action' => false,

                'recommendation_overrides_human_review' => false,
                'recommendation_overrides_evidence_requirements' => false,

                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Governance strategic plan human decision recommendation intelligence converts human-decision eligibility, decision risk, unresolved conditions, evidence requirements, planning readiness, dependency feasibility, and governance review context into ranked advisory recommendations for authorized human governance. Recommendations do not make or record the final governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, activate planning work, change decision, plan, or governance action state, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    protected function resolveDecision(?int $strategicPlanDecisionId): ?AIGovernanceStrategicPlanDecision
    {
        if ($strategicPlanDecisionId !== null) {
            return AIGovernanceStrategicPlanDecision::query()
                ->find($strategicPlanDecisionId);
        }

        return AIGovernanceStrategicPlanDecision::query()
            ->latest('id')
            ->first();
    }

    protected function buildRecommendations(
        array $eligibilityState,
        array $riskState,
        array $eligibilitySummary,
        array $eligibilityBlockers,
        array $eligibilityConstraints,
        array $conditionEvidenceContext,
        array $planningRiskContext,
        ?array $dominantOpenCondition,
        ?array $dominantEvidenceRequirement
    ): array {
        $recommendations = [];

        $blockingConditions = (int) ($eligibilitySummary['blocking_conditions'] ?? 0);
        $constrainingConditions = (int) ($eligibilitySummary['constraining_conditions'] ?? 0);
        $blockingEvidence = (int) ($eligibilitySummary['decision_blocking_evidence_items'] ?? 0);
        $blockingDependencies = (int) ($eligibilitySummary['blocking_dependency_count'] ?? 0);

        $riskScore = (float) ($riskState['human_decision_risk_score'] ?? 0);
        $riskLevel = $riskState['human_decision_risk_level'] ?? null;

        $eligibilityScore = (float) ($eligibilityState['human_decision_eligibility_score'] ?? 0);
        $approvalEligibility = $eligibilityState['human_approval_eligibility'] ?? null;

        $planningReadinessScore = (float) ($planningRiskContext['planning_readiness_score'] ?? 0);
        $dependencyAdjustedFeasibility = (float) ($planningRiskContext['dependency_adjusted_feasibility_score'] ?? 0);

        $conditionPressureScore = (float) ($conditionEvidenceContext['condition_pressure_score'] ?? 0);
        $decisionEvidenceReadinessScore = (float) ($conditionEvidenceContext['decision_evidence_readiness_score'] ?? 0);

        if ($blockingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' => 'RESOLVE_BLOCKING_HUMAN_DECISION_CONDITIONS',
                'recommendation_category' => 'DECISION_CONDITION_MANAGEMENT',
                'priority_level' => 'HIGH',
                'recommendation' => 'Address active blocking human-decision conditions through the established governance process before unrestricted strategic plan approval is considered.',
                'reason' => "{$blockingConditions} blocking human-decision condition(s) remain active.",
                'related_condition_count' => $blockingConditions,
                'related_evidence_count' => null,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => null,
            ];
        }

        if ($blockingEvidence > 0) {
            $recommendations[] = [
                'recommendation_code' => 'SATISFY_DECISION_BLOCKING_EVIDENCE_REQUIREMENTS',
                'recommendation_category' => 'DECISION_EVIDENCE',
                'priority_level' => 'HIGH',
                'recommendation' => 'Provide and validate outstanding decision-blocking evidence before unrestricted strategic plan approval is considered.',
                'reason' => "{$blockingEvidence} outstanding evidence requirement(s) currently block unrestricted approval.",
                'related_condition_count' => null,
                'related_evidence_count' => $blockingEvidence,
                'highest_related_condition' => null,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if ($blockingDependencies > 0) {
            $recommendations[] = [
                'recommendation_code' => 'GOVERN_BLOCKING_STRATEGIC_PLAN_DEPENDENCIES',
                'recommendation_category' => 'DEPENDENCY_MANAGEMENT',
                'priority_level' => 'HIGH',
                'recommendation' => 'Resolve or formally govern blocking strategic planning dependencies before unrestricted strategic plan approval is considered.',
                'reason' => "{$blockingDependencies} blocking strategic planning dependency condition(s) remain represented in the decision package.",
                'related_condition_count' => null,
                'related_evidence_count' => null,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if ($riskScore >= 70 || str_contains((string) $riskLevel, 'HIGH')) {
            $recommendations[] = [
                'recommendation_code' => 'MAINTAIN_HIGH_HUMAN_DECISION_RISK_OVERSIGHT',
                'recommendation_category' => 'HUMAN_DECISION_RISK',
                'priority_level' => 'HIGH',
                'recommendation' => 'Maintain elevated authorized human governance oversight while human decision risk remains high.',
                'reason' => "Current human decision risk is {$riskLevel} with risk score {$this->formatNumber($riskScore)}.",
                'related_condition_count' => $blockingConditions + $constrainingConditions,
                'related_evidence_count' => $blockingEvidence,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if (
            $approvalEligibility === 'NOT_READY_FOR_UNRESTRICTED_APPROVAL'
            || $eligibilityScore < 50
        ) {
            $recommendations[] = [
                'recommendation_code' => 'DO_NOT_TREAT_REVIEW_ELIGIBILITY_AS_APPROVAL_READINESS',
                'recommendation_category' => 'APPROVAL_GOVERNANCE',
                'priority_level' => 'HIGH',
                'recommendation' => 'Keep human governance review eligibility separate from unrestricted approval readiness until material blockers are resolved or formally governed.',
                'reason' => "Current human approval eligibility is {$approvalEligibility} with eligibility score {$this->formatNumber($eligibilityScore)}.",
                'related_condition_count' => $blockingConditions,
                'related_evidence_count' => $blockingEvidence,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if ($constrainingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' => 'REDUCE_CONSTRAINING_HUMAN_DECISION_CONDITIONS',
                'recommendation_category' => 'DECISION_READINESS',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Reduce active constraining decision conditions to improve authorized human decision readiness.',
                'reason' => "{$constrainingConditions} constraining human-decision condition(s) remain active.",
                'related_condition_count' => $constrainingConditions,
                'related_evidence_count' => null,
                'highest_related_condition' => $this->firstMatchingConstraint($eligibilityConstraints),
                'highest_related_evidence' => null,
            ];
        }

        if ($planningReadinessScore < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_HUMAN_DECISION_PLANNING_READINESS',
                'recommendation_category' => 'PLANNING_READINESS',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Improve strategic planning readiness before unrestricted strategic plan approval is considered.',
                'reason' => "Current strategic planning readiness score is {$this->formatNumber($planningReadinessScore)}.",
                'related_condition_count' => null,
                'related_evidence_count' => null,
                'highest_related_condition' => $this->findBlockerByCode(
                    $eligibilityBlockers,
                    'IMPROVE_PLANNING_READINESS'
                ),
                'highest_related_evidence' => null,
            ];
        }

        if ($dependencyAdjustedFeasibility < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_DECISION_DEPENDENCY_FEASIBILITY',
                'recommendation_category' => 'DEPENDENCY_FEASIBILITY',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Improve dependency-adjusted strategic plan feasibility through governed dependency reduction before unrestricted approval.',
                'reason' => "Current dependency-adjusted strategic plan feasibility score is {$this->formatNumber($dependencyAdjustedFeasibility)}.",
                'related_condition_count' => null,
                'related_evidence_count' => null,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if ($decisionEvidenceReadinessScore < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_DECISION_EVIDENCE_READINESS',
                'recommendation_category' => 'EVIDENCE_READINESS',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Improve validated decision evidence readiness before the human governance decision progresses toward unrestricted approval.',
                'reason' => "Current decision evidence readiness score is {$this->formatNumber($decisionEvidenceReadinessScore)}.",
                'related_condition_count' => null,
                'related_evidence_count' => $blockingEvidence,
                'highest_related_condition' => null,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        if ($conditionPressureScore > 0) {
            $recommendations[] = [
                'recommendation_code' => 'MAINTAIN_CONTROLLED_HUMAN_DECISION_PROGRESSION',
                'recommendation_category' => 'GOVERNANCE_CONTROL',
                'priority_level' => 'ADVISORY',
                'recommendation' => 'Maintain controlled human-governed decision progression while unresolved condition and evidence pressure remains.',
                'reason' => "Current decision condition pressure score is {$this->formatNumber($conditionPressureScore)}.",
                'related_condition_count' => $blockingConditions + $constrainingConditions,
                'related_evidence_count' => $blockingEvidence,
                'highest_related_condition' => $dominantOpenCondition,
                'highest_related_evidence' => $dominantEvidenceRequirement,
            ];
        }

        $recommendations[] = [
            'recommendation_code' => 'PRESERVE_FINAL_HUMAN_GOVERNANCE_AUTHORITY',
            'recommendation_category' => 'AUTHORITY_SEPARATION',
            'priority_level' => 'ADVISORY',
            'recommendation' => 'Ensure the final strategic plan decision is made and documented only by authorized human governance.',
            'reason' => 'Human decision recommendation intelligence is advisory and does not possess final governance decision authority.',
            'related_condition_count' => null,
            'related_evidence_count' => null,
            'highest_related_condition' => null,
            'highest_related_evidence' => null,
        ];

        return $recommendations;
    }

    protected function buildRecommendationSummary(array $recommendations): array
    {
        $critical = 0;
        $high = 0;
        $moderate = 0;
        $advisory = 0;

        foreach ($recommendations as $recommendation) {
            $priority = strtoupper((string) ($recommendation['priority_level'] ?? ''));

            match ($priority) {
                'CRITICAL' => $critical++,
                'HIGH' => $high++,
                'MODERATE' => $moderate++,
                default => $advisory++,
            };
        }

        $top = $recommendations[0] ?? null;

        return [
            'total_recommendations' => count($recommendations),
            'critical_recommendations' => $critical,
            'high_recommendations' => $high,
            'moderate_recommendations' => $moderate,
            'advisory_recommendations' => $advisory,
            'top_recommendation_code' => $top['recommendation_code'] ?? null,
            'top_recommendation_priority' => $top['priority_level'] ?? null,
        ];
    }

    protected function determineRecommendationStatus(
        array $summary,
        array $riskState,
        array $eligibilityState
    ): string {
        if (($summary['critical_recommendations'] ?? 0) > 0) {
            return 'CRITICAL_HUMAN_DECISION_MANAGEMENT_ATTENTION';
        }

        if (
            ($summary['high_recommendations'] ?? 0) > 0
            || ($riskState['human_decision_risk_level'] ?? null) === 'HIGH_HUMAN_DECISION_RISK'
            || ($eligibilityState['human_approval_eligibility'] ?? null) === 'NOT_READY_FOR_UNRESTRICTED_APPROVAL'
        ) {
            return 'ELEVATED_HUMAN_DECISION_MANAGEMENT_ATTENTION';
        }

        if (($summary['moderate_recommendations'] ?? 0) > 0) {
            return 'MODERATE_HUMAN_DECISION_MANAGEMENT_ATTENTION';
        }

        return 'CONTROLLED_HUMAN_DECISION_ADVISORY';
    }

    protected function buildFindings(
        array $recommendations,
        array $summary,
        string $recommendationStatus,
        array $eligibilityState,
        array $riskState,
        array $eligibilitySummary,
        array $conditionEvidenceContext,
        array $planningRiskContext,
        ?array $topRecommendation
    ): array {
        return [
            count($recommendations) . ' human strategic plan decision recommendation(s) are currently generated.',
            ($summary['critical_recommendations'] ?? 0) . ' critical human decision recommendation(s) are currently generated.',
            ($summary['high_recommendations'] ?? 0) . ' high human decision recommendation(s) are currently generated.',
            ($summary['moderate_recommendations'] ?? 0) . ' moderate human decision recommendation(s) are currently generated.',
            ($summary['advisory_recommendations'] ?? 0) . ' advisory human decision recommendation(s) are currently generated.',
            "Current human decision recommendation status is {$recommendationStatus}.",
            'Current human governance review eligibility is ' . ($eligibilityState['human_review_eligibility'] ?? 'UNKNOWN') . '.',
            'Current human approval eligibility is ' . ($eligibilityState['human_approval_eligibility'] ?? 'UNKNOWN') . '.',
            'Current human decision eligibility is ' . ($eligibilityState['human_decision_eligibility'] ?? 'UNKNOWN') . '.',
            'Current human decision eligibility score is ' . $this->formatNumber((float) ($eligibilityState['human_decision_eligibility_score'] ?? 0)) . '.',
            'Current human decision risk level is ' . ($riskState['human_decision_risk_level'] ?? 'UNKNOWN') . ' with score ' . $this->formatNumber((float) ($riskState['human_decision_risk_score'] ?? 0)) . '.',
            ($eligibilitySummary['blocking_conditions'] ?? 0) . ' blocking decision condition(s) remain active.',
            ($eligibilitySummary['constraining_conditions'] ?? 0) . ' constraining decision condition(s) remain active.',
            ($eligibilitySummary['decision_blocking_evidence_items'] ?? 0) . ' outstanding evidence requirement(s) currently block unrestricted approval.',
            ($eligibilitySummary['blocking_dependency_count'] ?? 0) . ' blocking strategic planning dependency condition(s) remain represented.',
            'Current decision condition pressure score is ' . $this->formatNumber((float) ($conditionEvidenceContext['condition_pressure_score'] ?? 0)) . '.',
            'Current decision evidence readiness score is ' . $this->formatNumber((float) ($conditionEvidenceContext['decision_evidence_readiness_score'] ?? 0)) . '.',
            'Current planning readiness score is ' . $this->formatNumber((float) ($planningRiskContext['planning_readiness_score'] ?? 0)) . '.',
            'Current dependency-adjusted feasibility score is ' . $this->formatNumber((float) ($planningRiskContext['dependency_adjusted_feasibility_score'] ?? 0)) . '.',
            'Top human governance decision recommendation is ' . ($topRecommendation['recommendation_code'] ?? 'NONE') . ' with ' . ($topRecommendation['priority_level'] ?? 'NONE') . ' priority.',
            'Human decision recommendation intelligence remains advisory and does not make or record the final governance decision.',
        ];
    }

    protected function buildManagementPriorities(
        array $recommendations,
        ?array $dominantOpenCondition,
        ?array $dominantEvidenceRequirement
    ): array {
        $priorities = [];

        foreach ($recommendations as $recommendation) {
            if (
                in_array(
                    $recommendation['priority_level'] ?? null,
                    ['CRITICAL', 'HIGH', 'MODERATE'],
                    true
                )
            ) {
                $priorities[] = $recommendation['recommendation'];
            }
        }

        if ($dominantOpenCondition) {
            $priorities[] =
                'Address the dominant open human decision condition '
                . ($dominantOpenCondition['condition_code'] ?? 'UNKNOWN')
                . ' through the established human governance process.';
        }

        if ($dominantEvidenceRequirement) {
            $priorities[] =
                'Provide and validate the dominant outstanding evidence requirement '
                . ($dominantEvidenceRequirement['evidence_code'] ?? 'UNKNOWN')
                . ' through authorized human governance.';
        }

        $priorities[] =
            'Ensure the final strategic plan decision is made, justified, and documented only by authorized human governance.';

        $priorities[] =
            'Preserve evidence quality, human review, governance validation, traceability, safety controls, and authority separation throughout final strategic plan decision progression.';

        return array_values(array_unique($priorities));
    }

    protected function sortRecommendations(array $recommendations): array
    {
        $weights = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
        ];

        usort(
            $recommendations,
            function (array $a, array $b) use ($weights): int {
                $aWeight = $weights[$a['priority_level'] ?? 'ADVISORY'] ?? 0;
                $bWeight = $weights[$b['priority_level'] ?? 'ADVISORY'] ?? 0;

                return $bWeight <=> $aWeight;
            }
        );

        return $recommendations;
    }

    protected function firstMatchingConstraint(array $constraints): ?array
    {
        return $constraints[0] ?? null;
    }

    protected function findBlockerByCode(array $blockers, string $code): ?array
    {
        foreach ($blockers as $blocker) {
            if (($blocker['blocker_code'] ?? null) === $code) {
                return $blocker;
            }
        }

        return null;
    }

    protected function formatNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}