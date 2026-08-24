<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanRiskIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateIntelligenceEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityIntelligenceEngine
    ) {
    }

    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $this->resolvePlan($strategicPlanId);

        if (!$plan) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan is currently available for plan risk analysis.',
            ];
        }

        $planState = $this->planStateIntelligenceEngine->analyze($plan->id);

        $dependencyFeasibility = $this
            ->dependencyFeasibilityIntelligenceEngine
            ->analyze($plan->id);

        if (
            !($planState['analysis_completed'] ?? false)
            || !($dependencyFeasibility['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_RISK_SOURCE_INTELLIGENCE_UNAVAILABLE',
                'strategic_plan_id' => $plan->id,
                'message' => 'Strategic plan state and dependency feasibility intelligence are required before plan risk intelligence can be generated.',
            ];
        }

        $riskSignals = [];

        $this->addRiskSignal(
            $riskSignals,
            'high_strategic_plan_risk',
            strtoupper((string) $plan->risk_level) === 'HIGH_STRATEGIC_RISK',
            'HIGH',
            (float) $plan->risk_score,
            'The strategic plan inherits a high strategic governance risk classification.'
        );

        $this->addRiskSignal(
            $riskSignals,
            'severe_strategic_constraint',
            strtoupper((string) $plan->constraint_level) === 'SEVERE_STRATEGIC_CONSTRAINT',
            'HIGH',
            $plan->constraint_level,
            'The strategic plan remains subject to severe strategic governance constraint pressure.'
        );

        $planningReadinessScore = (float) (
            $planState['plan_state']['planning_readiness_score']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'low_planning_readiness',
            $planningReadinessScore < 50,
            'MODERATE',
            $planningReadinessScore,
            'Strategic planning readiness remains below the preferred controlled progression threshold.'
        );

        $planHealthScore = (float) (
            $planState['plan_state']['plan_health_score']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'weak_plan_health',
            $planHealthScore < 50,
            'MODERATE',
            $planHealthScore,
            'Strategic plan health remains below the preferred planning threshold.'
        );

        $planFeasibilityScore = (float) (
            $planState['plan_state']['plan_feasibility_score']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'low_plan_feasibility',
            $planFeasibilityScore < 50,
            'HIGH',
            $planFeasibilityScore,
            'The strategic plan currently has limited practical feasibility.'
        );

        $adjustedFeasibilityScore = (float) (
            $dependencyFeasibility['dependency_feasibility_state']['dependency_adjusted_feasibility_score']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'very_low_dependency_adjusted_feasibility',
            $adjustedFeasibilityScore < 25,
            'HIGH',
            $adjustedFeasibilityScore,
            'Dependency pressure materially reduces the strategic plan\'s practical feasibility.'
        );

        $blockingDependencies = (int) (
            $dependencyFeasibility['dependency_summary']['blocking_dependencies']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'blocking_dependencies_present',
            $blockingDependencies > 0,
            'HIGH',
            $blockingDependencies,
            'One or more active strategic planning dependencies materially block plan feasibility.'
        );

        $constrainingDependencies = (int) (
            $dependencyFeasibility['dependency_summary']['constraining_dependencies']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'constraining_dependencies_present',
            $constrainingDependencies > 0,
            'MODERATE',
            $constrainingDependencies,
            'One or more active strategic planning dependencies continue to constrain plan progression.'
        );

        $dependencyPressureScore = (float) (
            $dependencyFeasibility['dependency_feasibility_state']['dependency_pressure_score']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'elevated_dependency_pressure',
            $dependencyPressureScore >= 50,
            'MODERATE',
            $dependencyPressureScore,
            'Strategic planning dependency pressure remains materially elevated.'
        );

        $objectiveCount = (int) (
            $planState['objective_context']['objective_count']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'high_objective_load',
            $objectiveCount >= 8,
            'ADVISORY',
            $objectiveCount,
            'The strategic plan contains a comparatively high objective load.'
        );

        $highObjectives = (int) (
            $planState['objective_context']['high_objectives']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'high_priority_objective_pressure',
            $highObjectives >= 3,
            'MODERATE',
            $highObjectives,
            'Multiple high-priority strategic planning objectives remain active.'
        );

        $criticalDependencies = (int) (
            $dependencyFeasibility['dependency_summary']['critical_dependencies']
            ?? 0
        );

        $this->addRiskSignal(
            $riskSignals,
            'critical_dependency',
            $criticalDependencies > 0,
            'CRITICAL',
            $criticalDependencies,
            'Critical strategic planning dependencies require immediate human management attention.'
        );

        $detectedSignals = array_values(array_filter(
            $riskSignals,
            fn (array $signal): bool => $signal['detected'] === true
        ));

        $criticalSignals = $this->countSeverity($detectedSignals, 'CRITICAL');
        $highSignals = $this->countSeverity($detectedSignals, 'HIGH');
        $moderateSignals = $this->countSeverity($detectedSignals, 'MODERATE');
        $advisorySignals = $this->countSeverity($detectedSignals, 'ADVISORY');

        $riskScore = $this->calculateRiskScore(
            $detectedSignals
        );

        $riskLevel = $this->classifyRiskLevel(
            $riskScore,
            $criticalSignals,
            $highSignals
        );

        $riskControlStatus = $this->classifyRiskControlStatus(
            $criticalSignals,
            $riskScore
        );

        $immediateHumanInterventionRequired =
            $criticalSignals > 0;

        $humanManagementAttentionRequired =
            count($detectedSignals) > 0;

        $highestRiskDependency =
            $dependencyFeasibility['dominant_dependency']
            ?? null;

        $highestRiskObjective =
            $this->findHighestRiskObjective(
                is_array($plan->objectives)
                    ? $plan->objectives
                    : []
            );

        $managementPriorities = $this->buildManagementPriorities(
            $dependencyFeasibility,
            $planState
        );

        $findings = [
            count($detectedSignals) . ' strategic plan risk or advisory signal(s) are currently detected.',
            "{$criticalSignals} critical, {$highSignals} high, {$moderateSignals} moderate, and {$advisorySignals} advisory strategic plan risk signal(s) are currently detected.",
            "Current strategic plan risk score is {$riskScore}.",
            "Current strategic plan risk level is {$riskLevel}.",
            "Current strategic plan risk control status is {$riskControlStatus}.",
            "Current planning readiness score is {$planningReadinessScore}.",
            "Current plan health score is {$planHealthScore}.",
            "Current base plan feasibility score is {$planFeasibilityScore}.",
            "Current dependency-adjusted feasibility score is {$adjustedFeasibilityScore}.",
            "{$blockingDependencies} blocking strategic planning dependency condition(s) remain active.",
            "{$constrainingDependencies} constraining strategic planning dependency condition(s) remain active.",
            "Current dependency pressure score is {$dependencyPressureScore}.",
        ];

        if ($highestRiskDependency) {
            $findings[] =
                'Current highest-risk strategic planning dependency is '
                . ($highestRiskDependency['dependency_code'] ?? 'UNKNOWN')
                . '.';
        }

        if ($highestRiskObjective) {
            $findings[] =
                'Current highest-priority strategic planning objective is '
                . ($highestRiskObjective['objective_code'] ?? 'UNKNOWN')
                . '.';
        }

        if ($criticalSignals === 0) {
            $findings[] =
                'No critical strategic plan risk signal is currently detected.';
        }

        $findings[] =
            'Strategic plan risk intelligence remains advisory and does not approve, reject, activate, execute, or resolve the strategic plan.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_RISK_INTELLIGENCE_AVAILABLE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'strategic_plan_risk_state' => [
                'strategic_plan_risk_level' => $riskLevel,
                'strategic_plan_risk_score' => $riskScore,
                'risk_control_status' => $riskControlStatus,
                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,
                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,
                'governance_integrity_intact' => true,
            ],

            'risk_summary' => [
                'detected_signal_count' => count($detectedSignals),
                'critical_signals' => $criticalSignals,
                'high_signals' => $highSignals,
                'moderate_signals' => $moderateSignals,
                'advisory_signals' => $advisorySignals,

                'blocking_dependencies' => $blockingDependencies,
                'constraining_dependencies' => $constrainingDependencies,
                'critical_dependencies' => $criticalDependencies,

                'objective_count' => $objectiveCount,
                'high_objectives' => $highObjectives,
            ],

            'risk_signals' => $riskSignals,

            'highest_risk_dependency' =>
                $highestRiskDependency,

            'highest_risk_objective' =>
                $highestRiskObjective,

            'plan_state_context' =>
                $planState['plan_state'] ?? [],

            'dependency_feasibility_context' => [
                'dependency_feasibility_status' =>
                    $dependencyFeasibility['dependency_feasibility_state']['dependency_feasibility_status']
                    ?? null,

                'dependency_resolution_readiness' =>
                    $dependencyFeasibility['dependency_feasibility_state']['dependency_resolution_readiness']
                    ?? null,

                'base_plan_feasibility_score' =>
                    $dependencyFeasibility['dependency_feasibility_state']['base_plan_feasibility_score']
                    ?? null,

                'dependency_adjusted_feasibility_score' =>
                    $dependencyFeasibility['dependency_feasibility_state']['dependency_adjusted_feasibility_score']
                    ?? null,

                'dependency_pressure_score' =>
                    $dependencyFeasibility['dependency_feasibility_state']['dependency_pressure_score']
                    ?? null,

                'blocking_dependency_count' =>
                    $dependencyFeasibility['dependency_feasibility_state']['blocking_dependency_count']
                    ?? null,

                'constraining_dependency_count' =>
                    $dependencyFeasibility['dependency_feasibility_state']['constraining_dependency_count']
                    ?? null,
            ],

            'priority_context' =>
                $planState['priority_context'] ?? [],

            'risk_constraint_context' =>
                $planState['risk_constraint_context'] ?? [],

            'objective_context' =>
                $planState['objective_context'] ?? [],

            'resource_context' =>
                $planState['resource_context'] ?? [],

            'progress_context' =>
                $planState['progress_context'] ?? [],

            'management_priorities' =>
                $managementPriorities,

            'strategic_plan_risk_findings' =>
                $findings,

            'strategic_plan_risk_guardrails' => [
                'strategic_plan_risk_intelligence_enabled' => true,

                'plan_risk_is_governance_decision' => false,
                'plan_risk_is_governance_approval' => false,
                'plan_risk_is_governance_rejection' => false,
                'plan_risk_is_action_resolution' => false,

                'plan_risk_changes_action_state' => false,
                'plan_risk_changes_priority' => false,
                'plan_risk_changes_eligibility' => false,

                'plan_risk_authorizes_execution' => false,
                'plan_risk_authorizes_deployment' => false,
                'plan_risk_authorizes_rollback' => false,
                'plan_risk_authorizes_clinical_action' => false,

                'risk_score_is_staff_performance_rating' => false,
                'risk_level_expands_ai_authority' => false,

                'blocking_dependency_authorizes_automation' => false,
                'low_feasibility_authorizes_automation' => false,

                'plan_risk_overrides_human_review' => false,
                'plan_risk_overrides_evidence_requirements' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Governance strategic plan risk intelligence consolidates strategic plan health, readiness, feasibility, dependencies, objective load, strategic risk, and constraints for human planning oversight only. Risk intelligence does not approve or reject the plan, activate or resolve governance work, change priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    protected function resolvePlan(
        ?int $strategicPlanId
    ): ?AIGovernanceStrategicPlan {
        if ($strategicPlanId !== null) {
            return AIGovernanceStrategicPlan::query()
                ->find($strategicPlanId);
        }

        return AIGovernanceStrategicPlan::query()
            ->latest('id')
            ->first();
    }

    protected function addRiskSignal(
        array &$signals,
        string $code,
        bool $detected,
        string $severity,
        mixed $value,
        string $message
    ): void {
        $signals[$code] = [
            'detected' => $detected,
            'severity' => $severity,
            'value' => $value,
            'message' => $message,
        ];
    }

    protected function countSeverity(
        array $signals,
        string $severity
    ): int {
        return count(array_filter(
            $signals,
            fn (array $signal): bool =>
                strtoupper((string) $signal['severity'])
                === $severity
        ));
    }

    protected function calculateRiskScore(
        array $detectedSignals
    ): float {
        if (count($detectedSignals) === 0) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($detectedSignals as $signal) {
            $total += match (
                strtoupper((string) $signal['severity'])
            ) {
                'CRITICAL' => 100.0,
                'HIGH' => 80.0,
                'MODERATE' => 55.0,
                'ADVISORY' => 25.0,
                default => 20.0,
            };
        }

        return round(
            min(
                100,
                $total / count($detectedSignals)
                    + (count($detectedSignals) * 2)
            ),
            2
        );
    }

    protected function classifyRiskLevel(
        float $riskScore,
        int $criticalSignals,
        int $highSignals
    ): string {
        if ($criticalSignals > 0 || $riskScore >= 90) {
            return 'CRITICAL_STRATEGIC_PLAN_RISK';
        }

        if ($riskScore >= 70 || $highSignals >= 4) {
            return 'HIGH_STRATEGIC_PLAN_RISK';
        }

        if ($riskScore >= 45 || $highSignals >= 1) {
            return 'MODERATE_STRATEGIC_PLAN_RISK';
        }

        if ($riskScore >= 20) {
            return 'LOW_WITH_ATTENTION_STRATEGIC_PLAN_RISK';
        }

        return 'LOW_STRATEGIC_PLAN_RISK';
    }

    protected function classifyRiskControlStatus(
        int $criticalSignals,
        float $riskScore
    ): string {
        if ($criticalSignals > 0) {
            return 'IMMEDIATE_HUMAN_CONTROL_ATTENTION_REQUIRED';
        }

        if ($riskScore >= 70) {
            return 'CONTROLLED_WITH_HIGH_PLANNING_ATTENTION';
        }

        if ($riskScore >= 45) {
            return 'CONTROLLED_WITH_MODERATE_PLANNING_ATTENTION';
        }

        return 'CONTROLLED';
    }

    protected function findHighestRiskObjective(
        array $objectives
    ): ?array {
        if (count($objectives) === 0) {
            return null;
        }

        $ranked = array_values(array_filter(
            $objectives,
            fn ($objective): bool => is_array($objective)
        ));

        usort(
            $ranked,
            function (array $a, array $b): int {
                return $this->objectivePriorityWeight(
                    (string) ($b['priority_level'] ?? '')
                ) <=> $this->objectivePriorityWeight(
                    (string) ($a['priority_level'] ?? '')
                );
            }
        );

        return $ranked[0] ?? null;
    }

    protected function objectivePriorityWeight(
        string $priority
    ): int {
        return match (strtoupper($priority)) {
            'CRITICAL' => 100,
            'HIGH' => 80,
            'MODERATE' => 50,
            'ADVISORY' => 25,
            default => 10,
        };
    }

    protected function buildManagementPriorities(
        array $dependencyFeasibility,
        array $planState
    ): array {
        $priorities = [];

        $dominant =
            $dependencyFeasibility['dominant_dependency']
            ?? null;

        if ($dominant) {
            $priorities[] =
                'Address the dominant strategic planning dependency '
                . ($dominant['dependency_code'] ?? 'UNKNOWN')
                . ' before materially increasing strategic plan commitments.';
        }

        $blocking = (int) (
            $dependencyFeasibility['dependency_summary']['blocking_dependencies']
            ?? 0
        );

        if ($blocking > 0) {
            $priorities[] =
                'Reduce active blocking dependencies before treating the strategic plan as practically feasible.';
        }

        $priorities[] =
            'Increase validated evidence availability for governance work currently blocked by evidence dependency.';

        $priorities[] =
            'Strengthen governance capacity before materially increasing strategic governance workload.';

        $priorities[] =
            'Complete outstanding human governance reviews in descending priority order.';

        $priorities[] =
            'Improve formal governance action closure progression to release constrained capacity.';

        $priorities[] =
            'Reduce strategic constraints and dependency pressure while preserving evidence and governance controls.';

        $priorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation throughout strategic plan progression.';

        return array_values(array_unique($priorities));
    }
}