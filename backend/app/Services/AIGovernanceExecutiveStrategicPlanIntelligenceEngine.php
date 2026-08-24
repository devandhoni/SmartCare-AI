<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceExecutiveStrategicPlanIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityEngine,
        protected AIGovernanceStrategicPlanRiskIntelligenceEngine $planRiskEngine,
        protected AIGovernanceStrategicPlanRecommendationIntelligenceEngine $recommendationEngine,
    ) {
    }

    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $strategicPlanId
            ? AIGovernanceStrategicPlan::find($strategicPlanId)
            : AIGovernanceStrategicPlan::latest('id')->first();

        if (!$plan) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_GOVERNANCE_STRATEGIC_PLAN_AVAILABLE',
                'message' => 'No AI governance strategic plan is available for executive strategic plan intelligence.',
            ];
        }

        $planStateResult = $this->planStateEngine->analyze($plan->id);
        $dependencyResult = $this->dependencyFeasibilityEngine->analyze($plan->id);
        $riskResult = $this->planRiskEngine->analyze($plan->id);
        $recommendationResult = $this->recommendationEngine->analyze($plan->id);

        $planState = $planStateResult['plan_state'] ?? [];
        $priorityContext = $planStateResult['priority_context'] ?? [];
        $riskConstraintContext = $planStateResult['risk_constraint_context'] ?? [];
        $objectiveContext = $planStateResult['objective_context'] ?? [];
        $resourceContext = $planStateResult['resource_context'] ?? [];
        $progressContext = $planStateResult['progress_context'] ?? [];

        $dependencyState = $dependencyResult['dependency_feasibility_state'] ?? [];
        $dependencySummary = $dependencyResult['dependency_summary'] ?? [];
        $dominantDependency = $dependencyResult['dominant_dependency'] ?? null;

        $planRiskState = $riskResult['strategic_plan_risk_state'] ?? [];
        $riskSummary = $riskResult['risk_summary'] ?? [];

        $recommendationState = $recommendationResult['recommendation_state'] ?? [];
        $recommendationSummary = $recommendationResult['recommendation_summary'] ?? [];
        $topRecommendation = $recommendationResult['top_recommendation'] ?? null;

        $planningReadinessScore = (float) ($planState['planning_readiness_score'] ?? 0);
        $planHealthScore = (float) ($planState['plan_health_score'] ?? 0);
        $basePlanFeasibilityScore = (float) ($planState['plan_feasibility_score'] ?? 0);
        $dependencyAdjustedFeasibilityScore = (float) ($dependencyState['dependency_adjusted_feasibility_score'] ?? 0);

        $strategicPriorityScore = (float) ($priorityContext['strategic_priority_score'] ?? 0);
        $strategicPlanRiskScore = (float) ($planRiskState['strategic_plan_risk_score'] ?? 0);
        $dependencyPressureScore = (float) ($dependencyState['dependency_pressure_score'] ?? 0);
        $constraintScore = (float) ($riskConstraintContext['constraint_score'] ?? 0);

        $baseExecutiveScore = round(
            (
                $planningReadinessScore +
                $planHealthScore +
                $basePlanFeasibilityScore +
                $dependencyAdjustedFeasibilityScore
            ) / 4,
            2
        );

        $planningPressureScore = round(
            (
                $strategicPlanRiskScore +
                $dependencyPressureScore +
                $constraintScore +
                $strategicPriorityScore
            ) / 4,
            2
        );

        $pressureAdjustment = round($planningPressureScore * 0.10, 2);

        $executiveStrategicPlanScore = round(
            max(0, min(100, $baseExecutiveScore - $pressureAdjustment)),
            2
        );

        $blockingDependencies = (int) ($dependencySummary['blocking_dependencies'] ?? 0);
        $constrainingDependencies = (int) ($dependencySummary['constraining_dependencies'] ?? 0);
        $criticalDependencies = (int) ($dependencySummary['critical_dependencies'] ?? 0);

        $criticalRiskSignals = (int) ($riskSummary['critical_signals'] ?? 0);
        $highRiskSignals = (int) ($riskSummary['high_signals'] ?? 0);

        $criticalRecommendations = (int) ($recommendationSummary['critical_recommendations'] ?? 0);
        $highRecommendations = (int) ($recommendationSummary['high_recommendations'] ?? 0);

        $immediateEscalationRequired =
            $criticalDependencies > 0 ||
            $criticalRiskSignals > 0 ||
            (bool) ($planRiskState['immediate_human_intervention_required'] ?? false) ||
            (bool) ($recommendationState['immediate_human_intervention_required'] ?? false);

        $managementEscalationRecommended =
            $immediateEscalationRequired ||
            $blockingDependencies > 0 ||
            $strategicPlanRiskScore >= 70 ||
            $dependencyAdjustedFeasibilityScore < 40 ||
            $planningReadinessScore < 50 ||
            $highRecommendations > 0;

        $executiveReadiness = $this->classifyExecutiveReadiness(
            $executiveStrategicPlanScore,
            $dependencyAdjustedFeasibilityScore,
            $blockingDependencies
        );

        $executiveStatus = $this->classifyExecutiveStatus(
            $immediateEscalationRequired,
            $blockingDependencies,
            $strategicPlanRiskScore,
            $dependencyAdjustedFeasibilityScore,
            $planningReadinessScore
        );

        $executiveConfidence = $this->classifyExecutiveConfidence(
            $planningReadinessScore,
            $basePlanFeasibilityScore,
            $dependencyAdjustedFeasibilityScore
        );

        $executiveAttentionItems = $this->buildExecutiveAttentionItems(
            $planState,
            $dependencyState,
            $dependencySummary,
            $planRiskState,
            $riskSummary,
            $recommendationSummary,
            $resourceContext,
            $progressContext
        );

        $executivePriorities = $this->buildExecutivePriorities(
            $topRecommendation,
            $dominantDependency,
            $dependencySummary,
            $resourceContext,
            $progressContext,
            $planRiskState
        );

        $executiveSummary = sprintf(
            'Strategic plan %s is currently %s with executive readiness %s and executive strategic plan score %s. Plan health is %s with score %s, planning readiness is %s with score %s, base feasibility is %s with score %s, dependency-adjusted feasibility is %s, and strategic plan risk is %s with score %s. %d blocking dependency condition(s) remain active.',
            $plan->plan_code,
            $executiveStatus,
            $executiveReadiness,
            $this->formatNumber($executiveStrategicPlanScore),
            $planState['plan_health'] ?? 'UNKNOWN',
            $this->formatNumber($planHealthScore),
            $planState['planning_readiness'] ?? 'UNKNOWN',
            $this->formatNumber($planningReadinessScore),
            $planState['plan_feasibility'] ?? 'UNKNOWN',
            $this->formatNumber($basePlanFeasibilityScore),
            $this->formatNumber($dependencyAdjustedFeasibilityScore),
            $planRiskState['strategic_plan_risk_level'] ?? 'UNKNOWN',
            $this->formatNumber($strategicPlanRiskScore),
            $blockingDependencies
        );

        $executiveFindings = [
            "Executive strategic plan intelligence is based on strategic plan {$plan->id}.",
            "Current executive strategic plan status is {$executiveStatus}.",
            "Current executive strategic plan readiness is {$executiveReadiness}.",
            "Current executive strategic plan score is {$this->formatNumber($executiveStrategicPlanScore)}.",
            "Current executive confidence is {$executiveConfidence}.",
            "Current strategic plan state is " . ($planState['strategic_plan_state'] ?? 'UNKNOWN') . ".",
            "Current plan health is " . ($planState['plan_health'] ?? 'UNKNOWN') . " with score {$this->formatNumber($planHealthScore)}.",
            "Current planning readiness is " . ($planState['planning_readiness'] ?? 'UNKNOWN') . " with score {$this->formatNumber($planningReadinessScore)}.",
            "Current base strategic plan feasibility is " . ($planState['plan_feasibility'] ?? 'UNKNOWN') . " with score {$this->formatNumber($basePlanFeasibilityScore)}.",
            "Current dependency-adjusted feasibility score is {$this->formatNumber($dependencyAdjustedFeasibilityScore)}.",
            "Current dependency feasibility classification is " . ($dependencyState['dependency_feasibility_status'] ?? 'UNKNOWN') . ".",
            "{$blockingDependencies} blocking strategic planning dependency condition(s) remain active.",
            "{$constrainingDependencies} constraining strategic planning dependency condition(s) remain active.",
            "Current dependency pressure score is {$this->formatNumber($dependencyPressureScore)}.",
            "Current strategic plan risk level is " . ($planRiskState['strategic_plan_risk_level'] ?? 'UNKNOWN') . " with score {$this->formatNumber($strategicPlanRiskScore)}.",
            "{$criticalRiskSignals} critical and {$highRiskSignals} high strategic plan risk signal(s) are currently detected.",
            "{$criticalRecommendations} critical and {$highRecommendations} high strategic plan management recommendation(s) are currently generated.",
            "Management escalation recommendation is " . ($managementEscalationRecommended ? 'ACTIVE' : 'NOT_REQUIRED') . ".",
            "Immediate escalation requirement is " . ($immediateEscalationRequired ? 'REQUIRED' : 'NOT_REQUIRED') . ".",
            "Executive strategic plan intelligence remains advisory and human governed.",
        ];

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_EXECUTIVE_STRATEGIC_PLAN_INTELLIGENCE_AVAILABLE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'executive_strategic_plan_state' => [
                'executive_strategic_plan_status' => $executiveStatus,
                'executive_readiness' => $executiveReadiness,
                'executive_confidence' => $executiveConfidence,
                'executive_strategic_plan_score' => $executiveStrategicPlanScore,
                'governance_integrity_intact' => true,
                'management_escalation_recommended' => $managementEscalationRecommended,
                'immediate_escalation_required' => $immediateEscalationRequired,
            ],

            'executive_summary' => $executiveSummary,

            'plan_state_context' => [
                'plan_status' => $planState['plan_status'] ?? null,
                'planning_mode' => $planState['planning_mode'] ?? null,
                'strategic_plan_state' => $planState['strategic_plan_state'] ?? null,
                'plan_health' => $planState['plan_health'] ?? null,
                'plan_health_score' => $planHealthScore,
                'planning_readiness' => $planState['planning_readiness'] ?? null,
                'planning_readiness_score' => $planningReadinessScore,
                'plan_feasibility' => $planState['plan_feasibility'] ?? null,
                'plan_feasibility_score' => $basePlanFeasibilityScore,
                'planning_maturity' => $planState['planning_maturity'] ?? null,
                'planning_confidence' => $planState['planning_confidence'] ?? null,
            ],

            'dependency_feasibility_context' => [
                'dependency_feasibility_status' => $dependencyState['dependency_feasibility_status'] ?? null,
                'dependency_resolution_readiness' => $dependencyState['dependency_resolution_readiness'] ?? null,
                'base_plan_feasibility_score' => $dependencyState['base_plan_feasibility_score'] ?? null,
                'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibilityScore,
                'dependency_pressure_score' => $dependencyPressureScore,
                'blocking_dependency_count' => $blockingDependencies,
                'constraining_dependency_count' => $constrainingDependencies,
                'advisory_dependency_count' => $dependencyState['advisory_dependency_count'] ?? 0,
                'dominant_dependency' => $dominantDependency,
            ],

            'plan_risk_context' => [
                'strategic_plan_risk_level' => $planRiskState['strategic_plan_risk_level'] ?? null,
                'strategic_plan_risk_score' => $strategicPlanRiskScore,
                'risk_control_status' => $planRiskState['risk_control_status'] ?? null,
                'critical_signals' => $criticalRiskSignals,
                'high_signals' => $highRiskSignals,
                'moderate_signals' => $riskSummary['moderate_signals'] ?? 0,
                'advisory_signals' => $riskSummary['advisory_signals'] ?? 0,
            ],

            'recommendation_context' => [
                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => $recommendationSummary['total_recommendations'] ?? 0,
                'critical_recommendations' => $criticalRecommendations,
                'high_recommendations' => $highRecommendations,
                'moderate_recommendations' => $recommendationSummary['moderate_recommendations'] ?? 0,
                'advisory_recommendations' => $recommendationSummary['advisory_recommendations'] ?? 0,
                'top_recommendation' => $topRecommendation,
            ],

            'priority_context' => $priorityContext,
            'risk_constraint_context' => $riskConstraintContext,
            'objective_context' => $objectiveContext,
            'resource_context' => $resourceContext,
            'progress_context' => $progressContext,

            'score_components' => [
                'planning_readiness' => $planningReadinessScore,
                'plan_health' => $planHealthScore,
                'base_plan_feasibility' => $basePlanFeasibilityScore,
                'dependency_adjusted_feasibility' => $dependencyAdjustedFeasibilityScore,
                'base_executive_score' => $baseExecutiveScore,
                'strategic_priority_pressure' => $strategicPriorityScore,
                'strategic_plan_risk_pressure' => $strategicPlanRiskScore,
                'dependency_pressure' => $dependencyPressureScore,
                'strategic_constraint_pressure' => $constraintScore,
                'planning_pressure_score' => $planningPressureScore,
                'pressure_adjustment' => $pressureAdjustment,
                'executive_strategic_plan_score' => $executiveStrategicPlanScore,
            ],

            'highest_risk_dependency' => $riskResult['highest_risk_dependency'] ?? $dominantDependency,
            'highest_risk_objective' => $riskResult['highest_risk_objective'] ?? null,

            'executive_attention_items' => $executiveAttentionItems,
            'executive_priorities' => $executivePriorities,
            'executive_findings' => $executiveFindings,

            'executive_guardrails' => [
                'executive_strategic_plan_intelligence_enabled' => true,

                'executive_plan_status_is_governance_decision' => false,
                'executive_readiness_is_governance_approval' => false,
                'executive_readiness_is_governance_rejection' => false,
                'executive_readiness_is_execution_authorization' => false,
                'executive_score_is_staff_performance_rating' => false,

                'executive_intelligence_changes_plan_status' => false,
                'executive_intelligence_changes_action_state' => false,
                'executive_intelligence_changes_priority' => false,
                'executive_intelligence_changes_eligibility' => false,

                'management_escalation_is_automatic_notification' => false,

                'strategic_plan_risk_authorizes_automation' => false,
                'blocking_dependency_authorizes_automation' => false,
                'low_feasibility_authorizes_automation' => false,

                'executive_intelligence_resolves_dependencies' => false,
                'executive_intelligence_activates_plan' => false,

                'executive_intelligence_authorizes_ai_change' => false,
                'executive_intelligence_authorizes_execution' => false,
                'executive_intelligence_authorizes_deployment' => false,
                'executive_intelligence_authorizes_rollback' => false,
                'executive_intelligence_authorizes_clinical_action' => false,

                'executive_intelligence_overrides_human_review' => false,
                'executive_intelligence_overrides_evidence_requirements' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Executive strategic plan intelligence consolidates strategic plan state, readiness, health, feasibility, dependency pressure, risk, objectives, recommendations, resources, and progression for human executive planning oversight only. Executive status, readiness, scores, escalation recommendations, and priorities do not approve or reject the strategic plan, resolve dependencies, activate governance work, change plan or action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function classifyExecutiveStatus(
        bool $immediateEscalationRequired,
        int $blockingDependencies,
        float $riskScore,
        float $dependencyAdjustedFeasibilityScore,
        float $planningReadinessScore
    ): string {
        if ($immediateEscalationRequired) {
            return 'IMMEDIATE_EXECUTIVE_STRATEGIC_PLAN_ATTENTION';
        }

        if (
            $blockingDependencies >= 3 ||
            $riskScore >= 80 ||
            $dependencyAdjustedFeasibilityScore < 20
        ) {
            return 'CONTROLLED_HIGH_STRATEGIC_PLAN_PRESSURE';
        }

        if (
            $blockingDependencies > 0 ||
            $riskScore >= 60 ||
            $planningReadinessScore < 50
        ) {
            return 'CONTROLLED_ELEVATED_STRATEGIC_PLAN_PRESSURE';
        }

        if ($planningReadinessScore < 70) {
            return 'CONTROLLED_STRATEGIC_PLAN_DEVELOPMENT';
        }

        return 'CONTROLLED_STRATEGIC_PLAN_READY_FOR_HUMAN_REVIEW';
    }

    private function classifyExecutiveReadiness(
        float $executiveScore,
        float $dependencyAdjustedFeasibilityScore,
        int $blockingDependencies
    ): string {
        if (
            $blockingDependencies >= 3 ||
            $dependencyAdjustedFeasibilityScore < 20 ||
            $executiveScore < 25
        ) {
            return 'VERY_LIMITED_READINESS';
        }

        if (
            $blockingDependencies > 0 ||
            $dependencyAdjustedFeasibilityScore < 50 ||
            $executiveScore < 50
        ) {
            return 'LIMITED_READINESS';
        }

        if ($executiveScore < 70) {
            return 'PARTIALLY_READY';
        }

        if ($executiveScore < 85) {
            return 'READY_WITH_HUMAN_GOVERNANCE_REVIEW';
        }

        return 'HIGH_READINESS_FOR_HUMAN_GOVERNANCE_REVIEW';
    }

    private function classifyExecutiveConfidence(
        float $planningReadinessScore,
        float $baseFeasibilityScore,
        float $dependencyAdjustedFeasibilityScore
    ): string {
        $confidenceScore = round(
            (
                $planningReadinessScore +
                $baseFeasibilityScore +
                $dependencyAdjustedFeasibilityScore
            ) / 3,
            2
        );

        if ($confidenceScore < 25) {
            return 'EXTREMELY_LIMITED';
        }

        if ($confidenceScore < 45) {
            return 'VERY_LIMITED';
        }

        if ($confidenceScore < 65) {
            return 'LIMITED';
        }

        if ($confidenceScore < 80) {
            return 'MODERATE';
        }

        return 'HIGH';
    }

    private function buildExecutiveAttentionItems(
        array $planState,
        array $dependencyState,
        array $dependencySummary,
        array $planRiskState,
        array $riskSummary,
        array $recommendationSummary,
        array $resourceContext,
        array $progressContext
    ): array {
        $items = [];

        $blockingDependencies = (int) ($dependencySummary['blocking_dependencies'] ?? 0);
        $constrainingDependencies = (int) ($dependencySummary['constraining_dependencies'] ?? 0);

        if ($blockingDependencies > 0) {
            $items[] = "{$blockingDependencies} blocking strategic planning dependency condition(s) remain active.";
        }

        if ($constrainingDependencies > 0) {
            $items[] = "{$constrainingDependencies} constraining strategic planning dependency condition(s) remain active.";
        }

        if (($dependencyState['dependency_adjusted_feasibility_score'] ?? 100) < 40) {
            $items[] = 'Dependency-adjusted strategic plan feasibility remains materially limited.';
        }

        if (($planState['planning_readiness_score'] ?? 100) < 50) {
            $items[] = 'Strategic planning readiness remains below the preferred executive planning threshold.';
        }

        if (($planState['plan_health_score'] ?? 100) < 50) {
            $items[] = 'Strategic plan health remains weak and requires continued human management attention.';
        }

        if (($planRiskState['strategic_plan_risk_score'] ?? 0) >= 70) {
            $items[] = 'Strategic plan risk remains high and requires continued executive oversight.';
        }

        if (($resourceContext['governance_capacity_score'] ?? 100) < 50) {
            $items[] = 'Governance capacity remains constrained and limits strategic plan flexibility.';
        }

        if (($resourceContext['workload_expansion_readiness'] ?? null) === 'NOT_READY_FOR_EXPANSION') {
            $items[] = 'Current governance workload is not ready for strategic expansion.';
        }

        if (($progressContext['action_closure_percentage'] ?? 100) < 50) {
            $items[] = 'Governance action closure remains below the preferred strategic planning progression threshold.';
        }

        if (($riskSummary['high_signals'] ?? 0) > 0) {
            $items[] = ($riskSummary['high_signals'] ?? 0) . ' high strategic plan risk signal(s) remain active.';
        }

        if (($recommendationSummary['high_recommendations'] ?? 0) > 0) {
            $items[] = ($recommendationSummary['high_recommendations'] ?? 0) . ' high strategic plan management recommendation(s) require human attention.';
        }

        return array_values(array_unique($items));
    }

    private function buildExecutivePriorities(
        ?array $topRecommendation,
        ?array $dominantDependency,
        array $dependencySummary,
        array $resourceContext,
        array $progressContext,
        array $planRiskState
    ): array {
        $priorities = [];

        if (!empty($topRecommendation['recommendation'])) {
            $priorities[] = $topRecommendation['recommendation'];
        }

        if (!empty($dominantDependency['dependency_code'])) {
            $priorities[] =
                'Address the dominant strategic planning dependency ' .
                $dominantDependency['dependency_code'] .
                ' through established human governance processes.';
        }

        if (($dependencySummary['blocking_dependencies'] ?? 0) > 0) {
            $priorities[] = 'Reduce active blocking strategic planning dependencies before materially increasing plan commitments.';
        }

        if (($resourceContext['governance_capacity_score'] ?? 100) < 50) {
            $priorities[] = 'Strengthen governance capacity before materially expanding strategic plan workload.';
        }

        if (($resourceContext['demand_pressure_score'] ?? 0) >= 60) {
            $priorities[] = 'Reduce governance demand pressure through governed progression of existing work.';
        }

        if (($progressContext['pending_human_reviews'] ?? 0) > 0) {
            $priorities[] = 'Complete outstanding human governance reviews in descending priority order.';
        }

        if (($progressContext['evidence_waiting_actions'] ?? 0) > 0) {
            $priorities[] = 'Increase validated governance evidence for work currently blocked by evidence dependency.';
        }

        if (($progressContext['action_closure_percentage'] ?? 100) < 50) {
            $priorities[] = 'Improve formal governance action closure progression while preserving evidence, review, safety, and governance controls.';
        }

        if (($planRiskState['strategic_plan_risk_score'] ?? 0) >= 70) {
            $priorities[] = 'Maintain elevated human executive oversight while strategic plan risk remains high.';
        }

        $priorities[] = 'Preserve human review, governance validation, evidence quality, safety controls, and authority separation throughout strategic plan progression.';

        return array_values(array_unique($priorities));
    }

    private function formatNumber(float|int $value): string
    {
        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}