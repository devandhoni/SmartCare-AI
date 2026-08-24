<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanStateIntelligenceEngine
{
    /**
     * Analyze the latest strategic plan or a specific strategic plan.
     */
    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $strategicPlanId
            ? AIGovernanceStrategicPlan::find($strategicPlanId)
            : AIGovernanceStrategicPlan::latest('id')->first();

        if (!$plan) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan is available for analysis.',
            ];
        }

        $objectives = $this->normalizeArray($plan->objectives);
        $dependencies = $this->normalizeArray($plan->dependencies);
        $resourceContext = $this->normalizeArray($plan->resource_context);
        $planningContext = $this->normalizeArray($plan->planning_context);
        $sourceContext = $this->normalizeArray($plan->source_context);

        $objectiveCount = count($objectives);
        $dependencyCount = count($dependencies);

        $objectiveSummary = $this->summarizeObjectives($objectives);
        $dependencySummary = $this->summarizeDependencies($dependencies);

        $planningReadinessScore = (float) ($plan->planning_readiness_score ?? 0);
        $strategicPriorityScore = (float) ($plan->strategic_priority_score ?? 0);
        $riskScore = (float) ($plan->risk_score ?? 0);

        $capacityScore = (float) (
            $resourceContext['governance_capacity_score']
            ?? 0
        );

        $demandPressureScore = (float) (
            $resourceContext['demand_pressure_score']
            ?? 0
        );

        $capacityDemandGap = (float) (
            $resourceContext['capacity_demand_gap']
            ?? 0
        );

        $strategicReadinessScore = (float) (
            $planningContext['strategic_readiness_score']
            ?? $planningReadinessScore
        );

        $strategicBalanceScore = (float) (
            $planningContext['strategic_balance_score']
            ?? 0
        );

        $constraintScore = (float) (
            $planningContext['constraint_score']
            ?? 0
        );

        $actionClosurePercentage = (float) (
            $sourceContext['action_closure_percentage']
            ?? 0
        );

        $decisionCompletionPercentage = (float) (
            $sourceContext['decision_completion_percentage']
            ?? 0
        );

        $planHealthScore = $this->calculatePlanHealthScore(
            planningReadinessScore: $planningReadinessScore,
            strategicReadinessScore: $strategicReadinessScore,
            strategicBalanceScore: $strategicBalanceScore,
            capacityScore: $capacityScore,
            actionClosurePercentage: $actionClosurePercentage,
            decisionCompletionPercentage: $decisionCompletionPercentage,
            riskScore: $riskScore,
            constraintScore: $constraintScore,
            dependencyPressureScore: $dependencySummary['dependency_pressure_score']
        );

        $planHealth = $this->classifyPlanHealth($planHealthScore);

        $feasibilityScore = $this->calculateFeasibilityScore(
            planningReadinessScore: $planningReadinessScore,
            capacityScore: $capacityScore,
            strategicBalanceScore: $strategicBalanceScore,
            actionClosurePercentage: $actionClosurePercentage,
            riskScore: $riskScore,
            dependencyPressureScore: $dependencySummary['dependency_pressure_score']
        );

        $feasibilityStatus = $this->classifyFeasibility($feasibilityScore);

        $objectiveLoad = $this->classifyObjectiveLoad($objectiveCount);

        $dependencyLoad = $this->classifyDependencyLoad(
            $dependencyCount,
            $dependencySummary['high_dependencies'],
            $dependencySummary['critical_dependencies']
        );

        $humanAttentionLevel = $this->determineHumanAttentionLevel(
            strategicPriorityScore: $strategicPriorityScore,
            riskScore: $riskScore,
            constraintScore: $constraintScore,
            dependencyPressureScore: $dependencySummary['dependency_pressure_score'],
            planningReadinessScore: $planningReadinessScore
        );

        $humanAttentionRequired = in_array(
            $humanAttentionLevel,
            ['HIGH', 'CRITICAL'],
            true
        );

        $immediateHumanInterventionRequired =
            $dependencySummary['critical_dependencies'] > 0
            || $riskScore >= 95
            || $strategicPriorityScore >= 95;

        $planningMaturity = $this->classifyPlanningMaturity(
            planningReadinessScore: $planningReadinessScore,
            feasibilityScore: $feasibilityScore,
            actionClosurePercentage: $actionClosurePercentage,
            decisionCompletionPercentage: $decisionCompletionPercentage
        );

        $planningConfidence = $this->classifyPlanningConfidence(
            planningReadinessScore: $planningReadinessScore,
            dependencyCount: $dependencyCount,
            riskScore: $riskScore,
            constraintScore: $constraintScore
        );

        $planState = $this->determinePlanState(
            planStatus: (string) $plan->plan_status,
            feasibilityStatus: $feasibilityStatus,
            planningReadinessScore: $planningReadinessScore,
            dependencyPressureScore: $dependencySummary['dependency_pressure_score'],
            humanAttentionLevel: $humanAttentionLevel
        );

        $findings = $this->buildFindings(
            plan: $plan,
            planState: $planState,
            planHealth: $planHealth,
            planHealthScore: $planHealthScore,
            feasibilityStatus: $feasibilityStatus,
            feasibilityScore: $feasibilityScore,
            objectiveCount: $objectiveCount,
            objectiveLoad: $objectiveLoad,
            dependencyCount: $dependencyCount,
            dependencyLoad: $dependencyLoad,
            dependencySummary: $dependencySummary,
            planningMaturity: $planningMaturity,
            planningConfidence: $planningConfidence,
            humanAttentionLevel: $humanAttentionLevel,
            actionClosurePercentage: $actionClosurePercentage,
            decisionCompletionPercentage: $decisionCompletionPercentage
        );

        $managementPriorities = $this->buildManagementPriorities(
            objectives: $objectives,
            dependencies: $dependencies,
            resourceContext: $resourceContext,
            planningContext: $planningContext,
            sourceContext: $sourceContext
        );

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'plan_state' => [
                'plan_status' => $plan->plan_status,
                'planning_mode' => $plan->planning_mode,
                'strategic_plan_state' => $planState,

                'plan_health' => $planHealth,
                'plan_health_score' => $planHealthScore,

                'planning_readiness' => $plan->planning_readiness,
                'planning_readiness_score' => $planningReadinessScore,

                'plan_feasibility' => $feasibilityStatus,
                'plan_feasibility_score' => $feasibilityScore,

                'planning_maturity' => $planningMaturity,
                'planning_confidence' => $planningConfidence,

                'human_management_attention_level' => $humanAttentionLevel,
                'human_management_attention_required' => $humanAttentionRequired,
                'immediate_human_intervention_required' => $immediateHumanInterventionRequired,
            ],

            'priority_context' => [
                'strategic_priority' => $plan->strategic_priority,
                'strategic_priority_score' => $strategicPriorityScore,
                'primary_recommendation_code' => $plan->primary_recommendation_code,
                'primary_recommendation' => $plan->primary_recommendation,
            ],

            'risk_constraint_context' => [
                'risk_level' => $plan->risk_level,
                'risk_score' => $riskScore,
                'constraint_level' => $plan->constraint_level,
                'constraint_score' => $constraintScore,
            ],

            'objective_context' => [
                'objective_count' => $objectiveCount,
                'objective_load' => $objectiveLoad,
                'critical_objectives' => $objectiveSummary['critical_objectives'],
                'high_objectives' => $objectiveSummary['high_objectives'],
                'moderate_objectives' => $objectiveSummary['moderate_objectives'],
                'advisory_objectives' => $objectiveSummary['advisory_objectives'],
                'objectives_with_related_actions' => $objectiveSummary['objectives_with_related_actions'],
            ],

            'dependency_context' => [
                'dependency_count' => $dependencyCount,
                'dependency_load' => $dependencyLoad,
                'critical_dependencies' => $dependencySummary['critical_dependencies'],
                'high_dependencies' => $dependencySummary['high_dependencies'],
                'moderate_dependencies' => $dependencySummary['moderate_dependencies'],
                'advisory_dependencies' => $dependencySummary['advisory_dependencies'],
                'active_dependencies' => $dependencySummary['active_dependencies'],
                'dependency_pressure_score' => $dependencySummary['dependency_pressure_score'],
                'dominant_dependency' => $dependencySummary['dominant_dependency'],
            ],

            'resource_context' => [
                'governance_capacity_status' => $resourceContext['governance_capacity_status'] ?? null,
                'governance_capacity_score' => $capacityScore,
                'demand_pressure_status' => $resourceContext['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $demandPressureScore,
                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $resourceContext['capacity_demand_balance'] ?? null,
                'strategic_load_status' => $resourceContext['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $resourceContext['workload_expansion_readiness'] ?? null,
            ],

            'strategic_context' => [
                'strategic_status' => $planningContext['strategic_status'] ?? null,
                'strategic_health' => $planningContext['strategic_health'] ?? null,
                'strategic_readiness' => $planningContext['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $strategicReadinessScore,
                'strategic_balance_score' => $strategicBalanceScore,
                'strategic_maturity' => $planningContext['strategic_maturity'] ?? null,
                'strategic_confidence' => $planningContext['strategic_confidence'] ?? null,
                'strategic_flexibility' => $planningContext['strategic_flexibility'] ?? null,
            ],

            'progress_context' => [
                'action_closure_percentage' => $actionClosurePercentage,
                'decision_completion_percentage' => $decisionCompletionPercentage,
                'pending_human_reviews' => (int) ($sourceContext['pending_human_reviews'] ?? 0),
                'evidence_waiting_actions' => (int) ($sourceContext['evidence_waiting_actions'] ?? 0),
                'deferred_actions' => (int) ($sourceContext['deferred_actions'] ?? 0),
                'high_priority_active_actions' => (int) ($sourceContext['high_priority_active_actions'] ?? 0),
                'critical_priority_active_actions' => (int) ($sourceContext['critical_priority_active_actions'] ?? 0),
            ],

            'plan_findings' => $findings,

            'management_priorities' => $managementPriorities,

            'strategic_plan_guardrails' => [
                'strategic_plan_state_intelligence_enabled' => true,

                'plan_state_is_governance_decision' => false,
                'plan_state_is_governance_approval' => false,
                'plan_state_is_governance_rejection' => false,
                'plan_state_is_action_resolution' => false,

                'plan_health_changes_action_state' => false,
                'plan_feasibility_changes_priority' => false,
                'plan_feasibility_changes_eligibility' => false,

                'planning_readiness_authorizes_execution' => false,
                'planning_maturity_expands_ai_authority' => false,
                'human_attention_level_is_automatic_notification' => false,

                'plan_state_authorizes_ai_change' => false,
                'plan_state_authorizes_execution' => false,
                'plan_state_authorizes_deployment' => false,
                'plan_state_authorizes_rollback' => false,
                'plan_state_authorizes_clinical_action' => false,

                'plan_state_overrides_human_review' => false,
                'plan_state_overrides_evidence_requirements' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Governance strategic plan state intelligence evaluates the readiness, feasibility, objective burden, dependency pressure, strategic health, and human-management attention requirements of a generated strategic plan. It does not approve or reject the plan, make governance decisions, change action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function summarizeObjectives(array $objectives): array
    {
        $summary = [
            'critical_objectives' => 0,
            'high_objectives' => 0,
            'moderate_objectives' => 0,
            'advisory_objectives' => 0,
            'objectives_with_related_actions' => 0,
        ];

        foreach ($objectives as $objective) {
            $priority = strtoupper((string) ($objective['priority_level'] ?? ''));

            match ($priority) {
                'CRITICAL' => $summary['critical_objectives']++,
                'HIGH' => $summary['high_objectives']++,
                'MODERATE' => $summary['moderate_objectives']++,
                'ADVISORY' => $summary['advisory_objectives']++,
                default => null,
            };

            if ((int) ($objective['related_action_count'] ?? 0) > 0) {
                $summary['objectives_with_related_actions']++;
            }
        }

        return $summary;
    }

    private function summarizeDependencies(array $dependencies): array
    {
        $critical = 0;
        $high = 0;
        $moderate = 0;
        $advisory = 0;
        $active = 0;

        $dominantDependency = null;
        $dominantWeight = -1;

        $severityWeights = [
            'CRITICAL' => 100,
            'HIGH' => 75,
            'MODERATE' => 50,
            'ADVISORY' => 25,
        ];

        foreach ($dependencies as $dependency) {
            $severity = strtoupper((string) ($dependency['severity'] ?? 'ADVISORY'));
            $status = strtoupper((string) ($dependency['status'] ?? ''));

            match ($severity) {
                'CRITICAL' => $critical++,
                'HIGH' => $high++,
                'MODERATE' => $moderate++,
                default => $advisory++,
            };

            if ($status === 'ACTIVE') {
                $active++;
            }

            $weight = $severityWeights[$severity] ?? 25;

            if ($weight > $dominantWeight) {
                $dominantWeight = $weight;
                $dominantDependency = $dependency;
            }
        }

        $count = max(count($dependencies), 1);

        $pressureScore = round(
            (
                ($critical * 100)
                + ($high * 75)
                + ($moderate * 50)
                + ($advisory * 25)
            ) / $count,
            2
        );

        return [
            'critical_dependencies' => $critical,
            'high_dependencies' => $high,
            'moderate_dependencies' => $moderate,
            'advisory_dependencies' => $advisory,
            'active_dependencies' => $active,
            'dependency_pressure_score' => $pressureScore,
            'dominant_dependency' => $dominantDependency,
        ];
    }

    private function calculatePlanHealthScore(
        float $planningReadinessScore,
        float $strategicReadinessScore,
        float $strategicBalanceScore,
        float $capacityScore,
        float $actionClosurePercentage,
        float $decisionCompletionPercentage,
        float $riskScore,
        float $constraintScore,
        float $dependencyPressureScore
    ): float {
        $positiveScore = (
            ($planningReadinessScore * 0.20)
            + ($strategicReadinessScore * 0.15)
            + ($strategicBalanceScore * 0.10)
            + ($capacityScore * 0.15)
            + ($actionClosurePercentage * 0.15)
            + ($decisionCompletionPercentage * 0.10)
        );

        $negativePressure = (
            ($riskScore * 0.05)
            + ($constraintScore * 0.05)
            + ($dependencyPressureScore * 0.05)
        );

        return round(
            max(0, min(100, $positiveScore - $negativePressure)),
            2
        );
    }

    private function classifyPlanHealth(float $score): string
    {
        return match (true) {
            $score >= 80 => 'STRONG_PLAN_HEALTH',
            $score >= 65 => 'CONTROLLED_PLAN_HEALTH',
            $score >= 50 => 'DEVELOPING_PLAN_HEALTH',
            $score >= 35 => 'CONSTRAINED_PLAN_HEALTH',
            default => 'WEAK_PLAN_HEALTH',
        };
    }

    private function calculateFeasibilityScore(
        float $planningReadinessScore,
        float $capacityScore,
        float $strategicBalanceScore,
        float $actionClosurePercentage,
        float $riskScore,
        float $dependencyPressureScore
    ): float {
        $base = (
            ($planningReadinessScore * 0.30)
            + ($capacityScore * 0.25)
            + ($strategicBalanceScore * 0.20)
            + ($actionClosurePercentage * 0.15)
            + ((100 - min($riskScore, 100)) * 0.10)
        );

        $dependencyPenalty = $dependencyPressureScore * 0.10;

        return round(
            max(0, min(100, $base - $dependencyPenalty)),
            2
        );
    }

    private function classifyFeasibility(float $score): string
    {
        return match (true) {
            $score >= 80 => 'HIGH_FEASIBILITY',
            $score >= 65 => 'GOOD_FEASIBILITY',
            $score >= 50 => 'CONDITIONAL_FEASIBILITY',
            $score >= 35 => 'LIMITED_FEASIBILITY',
            default => 'LOW_FEASIBILITY',
        };
    }

    private function classifyObjectiveLoad(int $objectiveCount): string
    {
        return match (true) {
            $objectiveCount >= 12 => 'VERY_HIGH_OBJECTIVE_LOAD',
            $objectiveCount >= 8 => 'HIGH_OBJECTIVE_LOAD',
            $objectiveCount >= 5 => 'MODERATE_OBJECTIVE_LOAD',
            $objectiveCount >= 1 => 'LOW_OBJECTIVE_LOAD',
            default => 'NO_OBJECTIVES',
        };
    }

    private function classifyDependencyLoad(
        int $dependencyCount,
        int $highDependencies,
        int $criticalDependencies
    ): string {
        if ($criticalDependencies > 0) {
            return 'CRITICAL_DEPENDENCY_LOAD';
        }

        if ($highDependencies >= 3 || $dependencyCount >= 7) {
            return 'HIGH_DEPENDENCY_LOAD';
        }

        if ($highDependencies > 0 || $dependencyCount >= 4) {
            return 'MODERATE_DEPENDENCY_LOAD';
        }

        if ($dependencyCount > 0) {
            return 'LOW_DEPENDENCY_LOAD';
        }

        return 'NO_ACTIVE_DEPENDENCY_LOAD';
    }

    private function determineHumanAttentionLevel(
        float $strategicPriorityScore,
        float $riskScore,
        float $constraintScore,
        float $dependencyPressureScore,
        float $planningReadinessScore
    ): string {
        $attentionScore = (
            ($strategicPriorityScore * 0.25)
            + ($riskScore * 0.30)
            + ($constraintScore * 0.20)
            + ($dependencyPressureScore * 0.15)
            + ((100 - $planningReadinessScore) * 0.10)
        );

        return match (true) {
            $attentionScore >= 90 => 'CRITICAL',
            $attentionScore >= 70 => 'HIGH',
            $attentionScore >= 45 => 'MODERATE',
            default => 'ADVISORY',
        };
    }

    private function classifyPlanningMaturity(
        float $planningReadinessScore,
        float $feasibilityScore,
        float $actionClosurePercentage,
        float $decisionCompletionPercentage
    ): string {
        $score = (
            ($planningReadinessScore * 0.35)
            + ($feasibilityScore * 0.30)
            + ($actionClosurePercentage * 0.15)
            + ($decisionCompletionPercentage * 0.20)
        );

        return match (true) {
            $score >= 80 => 'MATURE',
            $score >= 65 => 'ESTABLISHED',
            $score >= 50 => 'DEVELOPING',
            $score >= 35 => 'EARLY',
            default => 'VERY_EARLY',
        };
    }

    private function classifyPlanningConfidence(
        float $planningReadinessScore,
        int $dependencyCount,
        float $riskScore,
        float $constraintScore
    ): string {
        $confidenceScore = $planningReadinessScore;

        if ($dependencyCount >= 5) {
            $confidenceScore -= 10;
        }

        if ($riskScore >= 80) {
            $confidenceScore -= 10;
        }

        if ($constraintScore >= 80) {
            $confidenceScore -= 10;
        }

        return match (true) {
            $confidenceScore >= 80 => 'HIGH',
            $confidenceScore >= 65 => 'MODERATE',
            $confidenceScore >= 50 => 'LIMITED',
            $confidenceScore >= 30 => 'VERY_LIMITED',
            default => 'EXTREMELY_LIMITED',
        };
    }

    private function determinePlanState(
        string $planStatus,
        string $feasibilityStatus,
        float $planningReadinessScore,
        float $dependencyPressureScore,
        string $humanAttentionLevel
    ): string {
        if ($planStatus === 'DRAFT_FOR_HUMAN_REVIEW') {
            if (
                $humanAttentionLevel === 'CRITICAL'
                || $planningReadinessScore < 25
            ) {
                return 'DRAFT_REQUIRING_URGENT_HUMAN_REVIEW';
            }

            if (
                in_array(
                    $feasibilityStatus,
                    ['LOW_FEASIBILITY', 'LIMITED_FEASIBILITY'],
                    true
                )
                || $dependencyPressureScore >= 60
            ) {
                return 'DRAFT_WITH_MATERIAL_PLANNING_CONSTRAINTS';
            }

            return 'DRAFT_READY_FOR_STRUCTURED_HUMAN_REVIEW';
        }

        return 'HUMAN_GOVERNED_PLAN_UNDER_STRATEGIC_OVERSIGHT';
    }

    private function buildFindings(
        AIGovernanceStrategicPlan $plan,
        string $planState,
        string $planHealth,
        float $planHealthScore,
        string $feasibilityStatus,
        float $feasibilityScore,
        int $objectiveCount,
        string $objectiveLoad,
        int $dependencyCount,
        string $dependencyLoad,
        array $dependencySummary,
        string $planningMaturity,
        string $planningConfidence,
        string $humanAttentionLevel,
        float $actionClosurePercentage,
        float $decisionCompletionPercentage
    ): array {
        $findings = [
            "Strategic plan state intelligence is based on strategic plan {$plan->id}.",
            "Current strategic plan status is {$plan->plan_status}.",
            "Current strategic plan state is {$planState}.",
            "Current plan health is {$planHealth} with score {$planHealthScore}.",
            "Current plan feasibility is {$feasibilityStatus} with score {$feasibilityScore}.",
            "Current planning readiness is {$plan->planning_readiness} with score {$plan->planning_readiness_score}.",
            "Current planning maturity is {$planningMaturity}.",
            "Current planning confidence is {$planningConfidence}.",
            "Current human management attention level is {$humanAttentionLevel}.",
            "{$objectiveCount} strategic planning objective(s) are represented in the plan.",
            "Current objective load classification is {$objectiveLoad}.",
            "{$dependencyCount} strategic planning dependency condition(s) are represented.",
            "Current dependency load classification is {$dependencyLoad}.",
            "Dependency pressure score is {$dependencySummary['dependency_pressure_score']}.",
            "Governance action closure is {$actionClosurePercentage}%.",
            "Governance decision completion is {$decisionCompletionPercentage}%.",
            "Strategic plan state intelligence remains advisory and does not approve or execute the plan.",
        ];

        if ($dependencySummary['critical_dependencies'] > 0) {
            $findings[] = "{$dependencySummary['critical_dependencies']} critical strategic planning dependency condition(s) are active.";
        }

        if ($dependencySummary['high_dependencies'] > 0) {
            $findings[] = "{$dependencySummary['high_dependencies']} high-severity strategic planning dependency condition(s) are active.";
        }

        return $findings;
    }

    private function buildManagementPriorities(
        array $objectives,
        array $dependencies,
        array $resourceContext,
        array $planningContext,
        array $sourceContext
    ): array {
        $priorities = [];

        foreach ($objectives as $objective) {
            if (
                strtoupper((string) ($objective['priority_level'] ?? ''))
                === 'HIGH'
            ) {
                $text = $objective['objective'] ?? null;

                if ($text) {
                    $priorities[] = $text;
                }
            }
        }

        if (($sourceContext['pending_human_reviews'] ?? 0) > 0) {
            $priorities[] =
                'Complete outstanding human governance reviews in descending priority order.';
        }

        if (($sourceContext['evidence_waiting_actions'] ?? 0) > 0) {
            $priorities[] =
                'Increase validated evidence availability for governance work blocked by evidence dependency.';
        }

        if (
            ($resourceContext['workload_expansion_readiness'] ?? null)
            === 'NOT_READY_FOR_EXPANSION'
        ) {
            $priorities[] =
                'Do not materially expand strategic governance workload until capacity and readiness improve.';
        }

        if (
            (float) ($sourceContext['action_closure_percentage'] ?? 0) < 50
        ) {
            $priorities[] =
                'Improve formal governance action closure progression while preserving evidence, human review, and governance controls.';
        }

        if (
            ($planningContext['strategic_flexibility'] ?? null)
            === 'VERY_LOW_FLEXIBILITY'
        ) {
            $priorities[] =
                'Reduce strategic constraints before increasing governance commitments.';
        }

        foreach ($dependencies as $dependency) {
            if (
                strtoupper((string) ($dependency['severity'] ?? ''))
                === 'CRITICAL'
            ) {
                $priorities[] =
                    $dependency['message']
                    ?? 'Address critical strategic planning dependencies.';
            }
        }

        $priorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation throughout strategic plan progression.';

        return array_values(array_unique($priorities));
    }
}