<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateIntelligenceEngine
    ) {
    }

    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $this->resolvePlan($strategicPlanId);

        if (!$plan) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan is currently available for dependency and feasibility analysis.',
            ];
        }

        $planStateResult = $this->planStateIntelligenceEngine->analyze($plan->id);

        if (!($planStateResult['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_STATE_INTELLIGENCE_UNAVAILABLE',
                'strategic_plan_id' => $plan->id,
                'message' => 'Strategic plan state intelligence is required before dependency and feasibility intelligence can be generated.',
            ];
        }

        $dependencies = is_array($plan->dependencies)
            ? $plan->dependencies
            : [];

        $dependencyAnalysis = $this->analyzeDependencies($dependencies);

        $blockingDependencies = $dependencyAnalysis['blocking_dependencies'];
        $constrainingDependencies = $dependencyAnalysis['constraining_dependencies'];
        $advisoryDependencies = $dependencyAnalysis['advisory_dependencies'];

        $dependencyPressureScore = $this->calculateDependencyPressureScore($dependencies);

        $baseFeasibilityScore = (float) (
            $planStateResult['plan_state']['plan_feasibility_score']
            ?? 0
        );

        $blockingPenalty = min(
            40,
            count($blockingDependencies) * 8
        );

        $constrainingPenalty = min(
            20,
            count($constrainingDependencies) * 4
        );

        $dependencyPressurePenalty = round(
            $dependencyPressureScore * 0.10,
            2
        );

        $effectiveFeasibilityScore = round(
            max(
                0,
                $baseFeasibilityScore
                - $blockingPenalty
                - $constrainingPenalty
                - $dependencyPressurePenalty
            ),
            2
        );

        $dependencyFeasibilityStatus = $this->classifyDependencyFeasibility(
            $effectiveFeasibilityScore,
            count($blockingDependencies)
        );

        $dependencyResolutionReadiness = $this->classifyResolutionReadiness(
            count($blockingDependencies),
            count($constrainingDependencies)
        );

        $dominantDependency = $this->selectDominantDependency($dependencies);

        $criticalDependencies = $this->countBySeverity($dependencies, 'CRITICAL');
        $highDependencies = $this->countBySeverity($dependencies, 'HIGH');
        $moderateDependencies = $this->countBySeverity($dependencies, 'MODERATE');
        $advisoryDependenciesCount = $this->countBySeverity($dependencies, 'ADVISORY');

        $activeDependencies = array_values(array_filter(
            $dependencies,
            fn (array $dependency): bool =>
                strtoupper((string) ($dependency['status'] ?? 'ACTIVE')) === 'ACTIVE'
        ));

        $requiredConditions = $this->buildRequiredConditions(
            $blockingDependencies,
            $constrainingDependencies
        );

        $managementPriorities = $this->buildManagementPriorities(
            $blockingDependencies,
            $constrainingDependencies,
            $dominantDependency
        );

        $findings = [
            "Strategic plan dependency and feasibility intelligence is based on strategic plan {$plan->id}.",
            count($dependencies) . ' strategic planning dependency condition(s) are represented.',
            count($activeDependencies) . ' strategic planning dependency condition(s) are currently active.',
            count($blockingDependencies) . ' blocking strategic planning dependency condition(s) are currently identified.',
            count($constrainingDependencies) . ' constraining strategic planning dependency condition(s) are currently identified.',
            count($advisoryDependencies) . ' advisory strategic planning dependency condition(s) are currently identified.',
            "Current dependency pressure score is {$dependencyPressureScore}.",
            "Current base plan feasibility score is {$baseFeasibilityScore}.",
            "Current dependency-adjusted feasibility score is {$effectiveFeasibilityScore}.",
            "Current dependency feasibility classification is {$dependencyFeasibilityStatus}.",
            "Current dependency resolution readiness is {$dependencyResolutionReadiness}.",
        ];

        if ($dominantDependency) {
            $findings[] =
                'Current dominant strategic planning dependency is '
                . ($dominantDependency['dependency_code'] ?? 'UNKNOWN')
                . ' with '
                . ($dominantDependency['severity'] ?? 'UNKNOWN')
                . ' severity.';
        }

        if (count($blockingDependencies) > 0) {
            $findings[] =
                'Material blocking dependencies should be addressed through human-governed planning before the strategic plan is treated as practically feasible.';
        }

        if ($criticalDependencies === 0) {
            $findings[] =
                'No critical strategic planning dependency is currently detected.';
        }

        $findings[] =
            'Dependency feasibility intelligence remains advisory and does not approve, reject, activate, execute, or resolve the strategic plan.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_DEPENDENCY_FEASIBILITY_INTELLIGENCE_AVAILABLE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'dependency_feasibility_state' => [
                'dependency_feasibility_status' => $dependencyFeasibilityStatus,
                'dependency_resolution_readiness' => $dependencyResolutionReadiness,
                'base_plan_feasibility_score' => round($baseFeasibilityScore, 2),
                'dependency_adjusted_feasibility_score' => $effectiveFeasibilityScore,
                'dependency_pressure_score' => $dependencyPressureScore,
                'blocking_dependency_count' => count($blockingDependencies),
                'constraining_dependency_count' => count($constrainingDependencies),
                'advisory_dependency_count' => count($advisoryDependencies),
                'human_management_attention_required' =>
                    count($blockingDependencies) > 0
                    || count($constrainingDependencies) > 0,
                'immediate_human_intervention_required' =>
                    $criticalDependencies > 0,
            ],

            'dependency_summary' => [
                'total_dependencies' => count($dependencies),
                'active_dependencies' => count($activeDependencies),
                'critical_dependencies' => $criticalDependencies,
                'high_dependencies' => $highDependencies,
                'moderate_dependencies' => $moderateDependencies,
                'advisory_dependencies' => $advisoryDependenciesCount,
                'blocking_dependencies' => count($blockingDependencies),
                'constraining_dependencies' => count($constrainingDependencies),
                'advisory_only_dependencies' => count($advisoryDependencies),
            ],

            'dominant_dependency' => $dominantDependency,

            'blocking_dependencies' => $blockingDependencies,

            'constraining_dependencies' => $constrainingDependencies,

            'advisory_dependencies' => $advisoryDependencies,

            'dependency_analysis' => $dependencyAnalysis['all_dependencies'],

            'required_feasibility_conditions' => $requiredConditions,

            'plan_state_context' => [
                'plan_status' =>
                    $planStateResult['plan_state']['plan_status'] ?? null,

                'strategic_plan_state' =>
                    $planStateResult['plan_state']['strategic_plan_state'] ?? null,

                'plan_health' =>
                    $planStateResult['plan_state']['plan_health'] ?? null,

                'plan_health_score' =>
                    $planStateResult['plan_state']['plan_health_score'] ?? null,

                'planning_readiness' =>
                    $planStateResult['plan_state']['planning_readiness'] ?? null,

                'planning_readiness_score' =>
                    $planStateResult['plan_state']['planning_readiness_score'] ?? null,

                'plan_feasibility' =>
                    $planStateResult['plan_state']['plan_feasibility'] ?? null,

                'plan_feasibility_score' =>
                    $planStateResult['plan_state']['plan_feasibility_score'] ?? null,

                'planning_maturity' =>
                    $planStateResult['plan_state']['planning_maturity'] ?? null,

                'planning_confidence' =>
                    $planStateResult['plan_state']['planning_confidence'] ?? null,
            ],

            'risk_constraint_context' =>
                $planStateResult['risk_constraint_context'] ?? [],

            'resource_context' =>
                $planStateResult['resource_context'] ?? [],

            'progress_context' =>
                $planStateResult['progress_context'] ?? [],

            'management_priorities' => $managementPriorities,

            'dependency_feasibility_findings' => $findings,

            'dependency_feasibility_guardrails' => [
                'strategic_plan_dependency_feasibility_intelligence_enabled' => true,

                'dependency_analysis_is_governance_decision' => false,
                'dependency_analysis_is_governance_approval' => false,
                'dependency_analysis_is_governance_rejection' => false,
                'dependency_analysis_is_action_resolution' => false,

                'dependency_classification_changes_action_state' => false,
                'dependency_classification_changes_priority' => false,
                'dependency_classification_changes_eligibility' => false,

                'feasibility_score_authorizes_execution' => false,
                'feasibility_score_authorizes_deployment' => false,
                'feasibility_score_authorizes_rollback' => false,
                'feasibility_score_authorizes_clinical_action' => false,

                'dependency_resolution_is_automatic' => false,
                'blocking_dependency_bypasses_human_review' => false,
                'blocking_dependency_bypasses_evidence_requirements' => false,

                'capacity_pressure_authorizes_automation' => false,
                'strategic_constraint_authorizes_automation' => false,
                'dependency_pressure_expands_ai_authority' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Governance strategic plan dependency and feasibility intelligence identifies blocking, constraining, and advisory dependencies that affect practical plan feasibility for human strategic planning only. Dependency classification and feasibility scores do not approve or reject the plan, resolve dependencies, change governance action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
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

    protected function analyzeDependencies(array $dependencies): array
    {
        $all = [];
        $blocking = [];
        $constraining = [];
        $advisory = [];

        foreach ($dependencies as $dependency) {
            if (!is_array($dependency)) {
                continue;
            }

            $severity = strtoupper(
                (string) ($dependency['severity'] ?? 'ADVISORY')
            );

            $status = strtoupper(
                (string) ($dependency['status'] ?? 'ACTIVE')
            );

            $classification = $this->classifyDependency(
                $severity,
                $status
            );

            $severityWeight = $this->severityWeight($severity);

            $analyzedDependency = array_merge(
                $dependency,
                [
                    'dependency_classification' => $classification,
                    'severity_weight' => $severityWeight,
                    'blocks_plan_feasibility' =>
                        $classification === 'BLOCKING',
                    'constrains_plan_feasibility' =>
                        $classification === 'CONSTRAINING',
                    'requires_human_resolution' =>
                        in_array(
                            $classification,
                            ['BLOCKING', 'CONSTRAINING'],
                            true
                        ),
                    'automatic_resolution_allowed' => false,
                ]
            );

            $all[] = $analyzedDependency;

            if ($classification === 'BLOCKING') {
                $blocking[] = $analyzedDependency;
            } elseif ($classification === 'CONSTRAINING') {
                $constraining[] = $analyzedDependency;
            } else {
                $advisory[] = $analyzedDependency;
            }
        }

        return [
            'all_dependencies' => $all,
            'blocking_dependencies' => $blocking,
            'constraining_dependencies' => $constraining,
            'advisory_dependencies' => $advisory,
        ];
    }

    protected function classifyDependency(
        string $severity,
        string $status
    ): string {
        if ($status !== 'ACTIVE') {
            return 'ADVISORY';
        }

        if (in_array($severity, ['CRITICAL', 'HIGH'], true)) {
            return 'BLOCKING';
        }

        if ($severity === 'MODERATE') {
            return 'CONSTRAINING';
        }

        return 'ADVISORY';
    }

    protected function calculateDependencyPressureScore(
        array $dependencies
    ): float {
        if (count($dependencies) === 0) {
            return 0.0;
        }

        $weights = [];

        foreach ($dependencies as $dependency) {
            if (!is_array($dependency)) {
                continue;
            }

            $status = strtoupper(
                (string) ($dependency['status'] ?? 'ACTIVE')
            );

            if ($status !== 'ACTIVE') {
                continue;
            }

            $severity = strtoupper(
                (string) ($dependency['severity'] ?? 'ADVISORY')
            );

            $weights[] = $this->severityWeight($severity);
        }

        if (count($weights) === 0) {
            return 0.0;
        }

        return round(
            array_sum($weights) / count($weights),
            2
        );
    }

    protected function severityWeight(string $severity): float
    {
        return match ($severity) {
            'CRITICAL' => 100.0,
            'HIGH' => 75.0,
            'MODERATE' => 50.0,
            'ADVISORY' => 25.0,
            default => 25.0,
        };
    }

    protected function countBySeverity(
        array $dependencies,
        string $severity
    ): int {
        return count(array_filter(
            $dependencies,
            fn ($dependency): bool =>
                is_array($dependency)
                && strtoupper(
                    (string) ($dependency['severity'] ?? '')
                ) === $severity
        ));
    }

    protected function classifyDependencyFeasibility(
        float $score,
        int $blockingDependencies
    ): string {
        if ($blockingDependencies >= 4 || $score < 15) {
            return 'VERY_LOW_DEPENDENCY_FEASIBILITY';
        }

        if ($blockingDependencies >= 2 || $score < 35) {
            return 'LOW_DEPENDENCY_FEASIBILITY';
        }

        if ($blockingDependencies >= 1 || $score < 55) {
            return 'CONSTRAINED_DEPENDENCY_FEASIBILITY';
        }

        if ($score < 75) {
            return 'DEVELOPING_DEPENDENCY_FEASIBILITY';
        }

        return 'STRONG_DEPENDENCY_FEASIBILITY';
    }

    protected function classifyResolutionReadiness(
        int $blockingDependencies,
        int $constrainingDependencies
    ): string {
        if ($blockingDependencies >= 3) {
            return 'SUBSTANTIAL_DEPENDENCY_RELIEF_REQUIRED';
        }

        if ($blockingDependencies > 0) {
            return 'DEPENDENCY_RELIEF_REQUIRED';
        }

        if ($constrainingDependencies > 0) {
            return 'PARTIAL_DEPENDENCY_RELIEF_REQUIRED';
        }

        return 'DEPENDENCIES_SUFFICIENTLY_CONTROLLED';
    }

    protected function selectDominantDependency(
        array $dependencies
    ): ?array {
        if (count($dependencies) === 0) {
            return null;
        }

        $ranked = array_values(array_filter(
            $dependencies,
            fn ($dependency): bool => is_array($dependency)
        ));

        usort(
            $ranked,
            function (array $a, array $b): int {
                $aWeight = $this->severityWeight(
                    strtoupper(
                        (string) ($a['severity'] ?? 'ADVISORY')
                    )
                );

                $bWeight = $this->severityWeight(
                    strtoupper(
                        (string) ($b['severity'] ?? 'ADVISORY')
                    )
                );

                if ($aWeight === $bWeight) {
                    return $this->dependencyTypeRank(
                        (string) ($b['dependency_type'] ?? '')
                    ) <=> $this->dependencyTypeRank(
                        (string) ($a['dependency_type'] ?? '')
                    );
                }

                return $bWeight <=> $aWeight;
            }
        );

        return $ranked[0] ?? null;
    }

    protected function dependencyTypeRank(string $type): int
    {
        return match (strtoupper($type)) {
            'EVIDENCE' => 100,
            'STRATEGIC_CONSTRAINT' => 90,
            'CAPACITY' => 80,
            'HUMAN_REVIEW' => 70,
            'DEFERRED_WORK' => 50,
            default => 25,
        };
    }

    protected function buildRequiredConditions(
        array $blockingDependencies,
        array $constrainingDependencies
    ): array {
        $conditions = [];

        foreach (
            array_merge(
                $blockingDependencies,
                $constrainingDependencies
            ) as $dependency
        ) {
            $code = (string) (
                $dependency['dependency_code']
                ?? 'UNKNOWN_DEPENDENCY'
            );

            $type = strtoupper(
                (string) (
                    $dependency['dependency_type']
                    ?? 'UNKNOWN'
                )
            );

            $condition = match ($type) {
                'EVIDENCE' =>
                    'Additional validated governance evidence should become available and be reviewed through the established human governance process.',

                'CAPACITY' =>
                    'Governance capacity should improve before materially expanding strategic workload or commitments.',

                'STRATEGIC_CONSTRAINT' =>
                    'Material strategic constraints should be reduced through governed operational and strategic progression.',

                'HUMAN_REVIEW' =>
                    'Outstanding human governance reviews should be completed through the established governance process.',

                'DEFERRED_WORK' =>
                    'Deferred governance work should remain controlled until documented reassessment conditions are satisfied.',

                default =>
                    'The dependency should be reviewed and appropriately addressed through human governance before plan feasibility is increased.',
            };

            $conditions[] = [
                'dependency_code' => $code,
                'dependency_type' => $type,
                'severity' =>
                    $dependency['severity'] ?? null,
                'dependency_classification' =>
                    $dependency['dependency_classification'] ?? null,
                'required_condition' => $condition,
                'human_review_required' => true,
                'automatic_resolution_allowed' => false,
            ];
        }

        return $conditions;
    }

    protected function buildManagementPriorities(
        array $blockingDependencies,
        array $constrainingDependencies,
        ?array $dominantDependency
    ): array {
        $priorities = [];

        if ($dominantDependency !== null) {
            $priorities[] =
                'Address the dominant strategic planning dependency '
                . ($dominantDependency['dependency_code'] ?? 'UNKNOWN')
                . ' through human-governed planning before materially increasing plan commitments.';
        }

        foreach ($blockingDependencies as $dependency) {
            $type = strtoupper(
                (string) (
                    $dependency['dependency_type']
                    ?? ''
                )
            );

            $priority = match ($type) {
                'EVIDENCE' =>
                    'Increase validated evidence availability for strategic governance work blocked by evidence dependency.',

                'CAPACITY' =>
                    'Strengthen governance capacity before materially increasing strategic governance workload.',

                'STRATEGIC_CONSTRAINT' =>
                    'Reduce material strategic governance constraints before increasing strategic commitments.',

                'HUMAN_REVIEW' =>
                    'Complete outstanding human governance reviews in descending priority order.',

                default =>
                    'Address active high-severity strategic planning dependencies through human governance review.',
            };

            $priorities[] = $priority;
        }

        if (count($constrainingDependencies) > 0) {
            $priorities[] =
                'Reduce moderate strategic planning dependency pressure before treating the plan as operationally feasible.';
        }

        $priorities[] =
            'Improve formal governance action closure progression to release constrained strategic capacity.';

        $priorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation while dependency pressure is reduced.';

        return array_values(
            array_unique($priorities)
        );
    }
}