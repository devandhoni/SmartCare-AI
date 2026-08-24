<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanRecommendationIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityEngine,
        protected AIGovernanceStrategicPlanRiskIntelligenceEngine $planRiskEngine,
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
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'message' => 'No AI governance strategic plan is available for recommendation intelligence.',
            ];
        }

        $planState = $this->planStateEngine->analyze($plan->id);
        $dependencyFeasibility = $this->dependencyFeasibilityEngine->analyze($plan->id);
        $planRisk = $this->planRiskEngine->analyze($plan->id);

        if (
            !($planState['analysis_completed'] ?? false) ||
            !($dependencyFeasibility['analysis_completed'] ?? false) ||
            !($planRisk['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_RECOMMENDATION_INTELLIGENCE_INCOMPLETE',
                'strategic_plan_id' => $plan->id,
                'message' => 'Required strategic plan intelligence components are not fully available.',
            ];
        }

        $state = $planState['plan_state'] ?? [];
        $priorityContext = $planState['priority_context'] ?? [];
        $riskConstraintContext = $planState['risk_constraint_context'] ?? [];
        $objectiveContext = $planState['objective_context'] ?? [];
        $resourceContext = $planState['resource_context'] ?? [];
        $progressContext = $planState['progress_context'] ?? [];

        $dependencyState = $dependencyFeasibility['dependency_feasibility_state'] ?? [];
        $dependencySummary = $dependencyFeasibility['dependency_summary'] ?? [];
        $dominantDependency = $dependencyFeasibility['dominant_dependency'] ?? null;
        $blockingDependencies = $dependencyFeasibility['blocking_dependencies'] ?? [];
        $constrainingDependencies = $dependencyFeasibility['constraining_dependencies'] ?? [];

        $riskState = $planRisk['strategic_plan_risk_state'] ?? [];
        $riskSummary = $planRisk['risk_summary'] ?? [];
        $highestRiskDependency = $planRisk['highest_risk_dependency'] ?? null;
        $highestRiskObjective = $planRisk['highest_risk_objective'] ?? null;

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | Recommendation 1: Critical plan conditions
        |--------------------------------------------------------------------------
        */

        if (($riskSummary['critical_signals'] ?? 0) > 0) {
            $recommendations[] = [
                'recommendation_code' => 'ESCALATE_CRITICAL_STRATEGIC_PLAN_RISK',
                'recommendation_category' => 'CRITICAL_RISK_MANAGEMENT',
                'priority_level' => 'CRITICAL',
                'recommendation' => 'Escalate critical strategic planning risk conditions for immediate human governance assessment.',
                'reason' => ($riskSummary['critical_signals'] ?? 0)
                    . ' critical strategic plan risk signal(s) are currently detected.',
                'related_dependency_count' => $dependencySummary['critical_dependencies'] ?? 0,
                'related_objective_count' => $objectiveContext['critical_objectives'] ?? 0,
                'highest_related_dependency' => $highestRiskDependency,
                'highest_related_objective' => $highestRiskObjective,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 2: Blocking dependencies
        |--------------------------------------------------------------------------
        */

        if (($dependencySummary['blocking_dependencies'] ?? 0) > 0) {
            $recommendations[] = [
                'recommendation_code' => 'RESOLVE_BLOCKING_STRATEGIC_PLAN_DEPENDENCIES',
                'recommendation_category' => 'DEPENDENCY_MANAGEMENT',
                'priority_level' => 'HIGH',
                'recommendation' => 'Address active blocking strategic planning dependencies through the established human governance process before materially increasing plan commitments.',
                'reason' => ($dependencySummary['blocking_dependencies'] ?? 0)
                    . ' blocking strategic planning dependency condition(s) remain active.',
                'related_dependency_count' => $dependencySummary['blocking_dependencies'] ?? 0,
                'related_objective_count' => null,
                'highest_related_dependency' => $dominantDependency,
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 3: Evidence dependency
        |--------------------------------------------------------------------------
        */

        $evidenceDependency = collect($blockingDependencies)
            ->first(fn (array $dependency) =>
                ($dependency['dependency_type'] ?? null) === 'EVIDENCE'
            );

        if ($evidenceDependency) {
            $recommendations[] = [
                'recommendation_code' => 'INCREASE_VALIDATED_PLAN_EVIDENCE',
                'recommendation_category' => 'EVIDENCE',
                'priority_level' => 'HIGH',
                'recommendation' => 'Increase validated governance evidence for strategic plan dependencies that currently prevent practical progression.',
                'reason' => 'The strategic plan contains an active blocking evidence dependency.',
                'related_dependency_count' => 1,
                'related_objective_count' => null,
                'highest_related_dependency' => $evidenceDependency,
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 4: Capacity recovery
        |--------------------------------------------------------------------------
        */

        $capacityScore = (float) ($resourceContext['governance_capacity_score'] ?? 0);

        if ($capacityScore < 50) {
            $recommendations[] = [
                'recommendation_code' => 'STRENGTHEN_STRATEGIC_PLAN_CAPACITY',
                'recommendation_category' => 'CAPACITY',
                'priority_level' => 'HIGH',
                'recommendation' => 'Strengthen available governance capacity before materially expanding strategic plan workload or commitments.',
                'reason' => 'Current governance capacity score is '
                    . $this->formatNumber($capacityScore)
                    . ', below the preferred strategic planning threshold.',
                'related_dependency_count' => null,
                'related_objective_count' => null,
                'highest_related_dependency' => $this->findDependencyByType(
                    $blockingDependencies,
                    'CAPACITY'
                ),
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 5: Severe strategic constraints
        |--------------------------------------------------------------------------
        */

        $constraintLevel = $riskConstraintContext['constraint_level'] ?? null;
        $constraintScore = (float) ($riskConstraintContext['constraint_score'] ?? 0);

        if (
            in_array($constraintLevel, [
                'SEVERE_STRATEGIC_CONSTRAINT',
                'CRITICAL_STRATEGIC_CONSTRAINT',
            ], true)
            || $constraintScore >= 75
        ) {
            $recommendations[] = [
                'recommendation_code' => 'REDUCE_STRATEGIC_PLAN_CONSTRAINT_PRESSURE',
                'recommendation_category' => 'STRATEGIC_CONSTRAINT',
                'priority_level' => 'HIGH',
                'recommendation' => 'Reduce material strategic governance constraints before increasing strategic planning commitments.',
                'reason' => 'Current strategic constraint level is '
                    . ($constraintLevel ?? 'UNKNOWN')
                    . ' with score '
                    . $this->formatNumber($constraintScore)
                    . '.',
                'related_dependency_count' => null,
                'related_objective_count' => null,
                'highest_related_dependency' => $this->findDependencyByType(
                    $blockingDependencies,
                    'STRATEGIC_CONSTRAINT'
                ),
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 6: High plan risk
        |--------------------------------------------------------------------------
        */

        $planRiskLevel = $riskState['strategic_plan_risk_level'] ?? null;
        $planRiskScore = (float) ($riskState['strategic_plan_risk_score'] ?? 0);

        if ($planRiskScore >= 70) {
            $recommendations[] = [
                'recommendation_code' => 'MAINTAIN_HIGH_PLAN_RISK_OVERSIGHT',
                'recommendation_category' => 'PLAN_RISK',
                'priority_level' => 'HIGH',
                'recommendation' => 'Maintain elevated human management oversight while strategic plan risk remains high.',
                'reason' => 'Current strategic plan risk is '
                    . ($planRiskLevel ?? 'UNKNOWN')
                    . ' with risk score '
                    . $this->formatNumber($planRiskScore)
                    . '.',
                'related_dependency_count' => $dependencySummary['blocking_dependencies'] ?? 0,
                'related_objective_count' => $objectiveContext['high_objectives'] ?? 0,
                'highest_related_dependency' => $highestRiskDependency,
                'highest_related_objective' => $highestRiskObjective,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 7: Pending human review
        |--------------------------------------------------------------------------
        */

        $pendingHumanReviews = (int) ($progressContext['pending_human_reviews'] ?? 0);

        if ($pendingHumanReviews > 0) {
            $recommendations[] = [
                'recommendation_code' => 'COMPLETE_PENDING_PLAN_GOVERNANCE_REVIEWS',
                'recommendation_category' => 'HUMAN_GOVERNANCE',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Complete outstanding human governance reviews that continue to constrain strategic plan progression.',
                'reason' => $pendingHumanReviews
                    . ' governance action(s) remain pending human review.',
                'related_dependency_count' => count($constrainingDependencies),
                'related_objective_count' => null,
                'highest_related_dependency' => $this->findDependencyByType(
                    $constrainingDependencies,
                    'HUMAN_REVIEW'
                ),
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 8: Low feasibility
        |--------------------------------------------------------------------------
        */

        $dependencyAdjustedFeasibility = (float) (
            $dependencyState['dependency_adjusted_feasibility_score'] ?? 0
        );

        if ($dependencyAdjustedFeasibility < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_STRATEGIC_PLAN_FEASIBILITY',
                'recommendation_category' => 'PLAN_FEASIBILITY',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Improve strategic plan feasibility by reducing dependency pressure and resolving material planning constraints.',
                'reason' => 'Current dependency-adjusted feasibility score is '
                    . $this->formatNumber($dependencyAdjustedFeasibility)
                    . '.',
                'related_dependency_count' => $dependencySummary['active_dependencies'] ?? 0,
                'related_objective_count' => null,
                'highest_related_dependency' => $dominantDependency,
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 9: Closure progression
        |--------------------------------------------------------------------------
        */

        $closurePercentage = (float) (
            $progressContext['action_closure_percentage'] ?? 0
        );

        if ($closurePercentage < 50) {
            $recommendations[] = [
                'recommendation_code' => 'IMPROVE_PLAN_GOVERNANCE_CLOSURE_PROGRESSION',
                'recommendation_category' => 'WORKFLOW_PROGRESSION',
                'priority_level' => 'MODERATE',
                'recommendation' => 'Improve formal governance action closure progression while preserving evidence, human review, safety, and governance controls.',
                'reason' => 'Current governance action closure is '
                    . $this->formatNumber($closurePercentage)
                    . '%, below the preferred strategic planning progression threshold.',
                'related_dependency_count' => null,
                'related_objective_count' => null,
                'highest_related_dependency' => null,
                'highest_related_objective' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 10: High objective load
        |--------------------------------------------------------------------------
        */

        $objectiveCount = (int) ($objectiveContext['objective_count'] ?? 0);

        if (
            ($objectiveContext['objective_load'] ?? null) === 'HIGH_OBJECTIVE_LOAD'
            || $objectiveCount >= 8
        ) {
            $recommendations[] = [
                'recommendation_code' => 'CONTROL_STRATEGIC_PLAN_OBJECTIVE_LOAD',
                'recommendation_category' => 'OBJECTIVE_MANAGEMENT',
                'priority_level' => 'ADVISORY',
                'recommendation' => 'Maintain controlled sequencing of strategic planning objectives and avoid expanding objective load until material dependencies and constraints improve.',
                'reason' => $objectiveCount
                    . ' strategic planning objective(s) are currently represented.',
                'related_dependency_count' => null,
                'related_objective_count' => $objectiveCount,
                'highest_related_dependency' => null,
                'highest_related_objective' => $highestRiskObjective,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation 11: Deferred governance observation
        |--------------------------------------------------------------------------
        */

        $deferredActions = (int) ($progressContext['deferred_actions'] ?? 0);

        if ($deferredActions > 0) {
            $recommendations[] = [
                'recommendation_code' => 'MAINTAIN_DEFERRED_PLAN_GOVERNANCE_OBSERVATION',
                'recommendation_category' => 'DEFERRED_GOVERNANCE',
                'priority_level' => 'ADVISORY',
                'recommendation' => 'Maintain deferred governance work under controlled observation until established reassessment conditions are satisfied.',
                'reason' => $deferredActions
                    . ' governance action(s) remain deferred.',
                'related_dependency_count' => null,
                'related_objective_count' => null,
                'highest_related_dependency' => null,
                'highest_related_objective' => null,
            ];
        }

        $recommendations = $this->sortRecommendations($recommendations);

        $summary = $this->buildRecommendationSummary($recommendations);

        $topRecommendation = $recommendations[0] ?? null;

        $recommendationStatus = $this->determineRecommendationStatus($summary);

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'recommendation_state' => [
                'recommendation_mode' => 'HUMAN_STRATEGIC_PLAN_ADVISORY',
                'recommendation_status' => $recommendationStatus,
                'human_management_attention_required' =>
                    ($summary['critical_recommendations'] ?? 0) > 0
                    || ($summary['high_recommendations'] ?? 0) > 0,
                'immediate_human_intervention_required' =>
                    ($summary['critical_recommendations'] ?? 0) > 0,
                'governance_integrity_intact' => true,
            ],

            'recommendation_summary' => $summary,

            'top_recommendation' => $topRecommendation,

            'recommendations' => $recommendations,

            'plan_state_context' => [
                'plan_status' => $state['plan_status'] ?? null,
                'strategic_plan_state' => $state['strategic_plan_state'] ?? null,
                'plan_health' => $state['plan_health'] ?? null,
                'plan_health_score' => $state['plan_health_score'] ?? null,
                'planning_readiness' => $state['planning_readiness'] ?? null,
                'planning_readiness_score' => $state['planning_readiness_score'] ?? null,
                'plan_feasibility' => $state['plan_feasibility'] ?? null,
                'plan_feasibility_score' => $state['plan_feasibility_score'] ?? null,
                'planning_maturity' => $state['planning_maturity'] ?? null,
                'planning_confidence' => $state['planning_confidence'] ?? null,
            ],

            'dependency_feasibility_context' => [
                'dependency_feasibility_status' =>
                    $dependencyState['dependency_feasibility_status'] ?? null,
                'dependency_resolution_readiness' =>
                    $dependencyState['dependency_resolution_readiness'] ?? null,
                'base_plan_feasibility_score' =>
                    $dependencyState['base_plan_feasibility_score'] ?? null,
                'dependency_adjusted_feasibility_score' =>
                    $dependencyState['dependency_adjusted_feasibility_score'] ?? null,
                'dependency_pressure_score' =>
                    $dependencyState['dependency_pressure_score'] ?? null,
                'blocking_dependency_count' =>
                    $dependencyState['blocking_dependency_count'] ?? null,
                'constraining_dependency_count' =>
                    $dependencyState['constraining_dependency_count'] ?? null,
            ],

            'plan_risk_context' => [
                'strategic_plan_risk_level' =>
                    $riskState['strategic_plan_risk_level'] ?? null,
                'strategic_plan_risk_score' =>
                    $riskState['strategic_plan_risk_score'] ?? null,
                'risk_control_status' =>
                    $riskState['risk_control_status'] ?? null,
                'critical_signals' =>
                    $riskSummary['critical_signals'] ?? 0,
                'high_signals' =>
                    $riskSummary['high_signals'] ?? 0,
                'moderate_signals' =>
                    $riskSummary['moderate_signals'] ?? 0,
                'advisory_signals' =>
                    $riskSummary['advisory_signals'] ?? 0,
            ],

            'priority_context' => [
                'strategic_priority' =>
                    $priorityContext['strategic_priority'] ?? null,
                'strategic_priority_score' =>
                    $priorityContext['strategic_priority_score'] ?? null,
                'primary_recommendation_code' =>
                    $priorityContext['primary_recommendation_code'] ?? null,
                'primary_recommendation' =>
                    $priorityContext['primary_recommendation'] ?? null,
            ],

            'risk_constraint_context' => [
                'risk_level' =>
                    $riskConstraintContext['risk_level'] ?? null,
                'risk_score' =>
                    $riskConstraintContext['risk_score'] ?? null,
                'constraint_level' =>
                    $riskConstraintContext['constraint_level'] ?? null,
                'constraint_score' =>
                    $riskConstraintContext['constraint_score'] ?? null,
            ],

            'objective_context' => $objectiveContext,

            'resource_context' => $resourceContext,

            'progress_context' => $progressContext,

            'recommendation_findings' => [
                $summary['total_recommendations']
                    . ' strategic plan management recommendation(s) are currently generated.',

                $summary['critical_recommendations']
                    . ' critical strategic plan recommendation(s) are currently generated.',

                $summary['high_recommendations']
                    . ' high strategic plan recommendation(s) are currently generated.',

                $summary['moderate_recommendations']
                    . ' moderate strategic plan recommendation(s) are currently generated.',

                $summary['advisory_recommendations']
                    . ' advisory strategic plan recommendation(s) are currently generated.',

                'Current strategic plan recommendation status is '
                    . $recommendationStatus
                    . '.',

                'Current strategic plan risk level is '
                    . ($riskState['strategic_plan_risk_level'] ?? 'UNKNOWN')
                    . ' with score '
                    . $this->formatNumber(
                        (float) ($riskState['strategic_plan_risk_score'] ?? 0)
                    )
                    . '.',

                'Current dependency-adjusted feasibility score is '
                    . $this->formatNumber(
                        (float) (
                            $dependencyState['dependency_adjusted_feasibility_score']
                            ?? 0
                        )
                    )
                    . '.',

                ($dependencySummary['blocking_dependencies'] ?? 0)
                    . ' blocking strategic planning dependency condition(s) remain active.',

                'Top strategic plan recommendation is '
                    . ($topRecommendation['recommendation_code'] ?? 'NONE')
                    . ' with '
                    . ($topRecommendation['priority_level'] ?? 'NONE')
                    . ' priority.',

                'Strategic plan recommendation intelligence remains advisory and does not approve, reject, activate, execute, or resolve the strategic plan.',
            ],

            'recommendation_guardrails' => [
                'strategic_plan_recommendation_intelligence_enabled' => true,

                'recommendation_is_governance_decision' => false,
                'recommendation_is_governance_approval' => false,
                'recommendation_is_governance_rejection' => false,
                'recommendation_is_action_resolution' => false,

                'recommendation_changes_plan_status' => false,
                'recommendation_changes_action_state' => false,
                'recommendation_changes_priority' => false,
                'recommendation_changes_eligibility' => false,

                'recommendation_authorizes_execution' => false,
                'recommendation_authorizes_deployment' => false,
                'recommendation_authorizes_rollback' => false,
                'recommendation_authorizes_clinical_action' => false,

                'recommendation_resolves_dependencies' => false,
                'recommendation_activates_plan' => false,

                'recommendation_priority_expands_ai_authority' => false,
                'plan_risk_authorizes_automation' => false,
                'blocking_dependency_authorizes_automation' => false,
                'low_feasibility_authorizes_automation' => false,

                'recommendation_overrides_human_review' => false,
                'recommendation_overrides_evidence_requirements' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Governance strategic plan recommendation intelligence converts strategic plan state, dependency feasibility, risk, objective burden, constraints, resources, and progression into ranked human-management advisory priorities. Recommendations do not approve or reject the strategic plan, resolve dependencies, activate governance work, change plan or action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    protected function buildRecommendationSummary(array $recommendations): array
    {
        $critical = collect($recommendations)
            ->where('priority_level', 'CRITICAL')
            ->count();

        $high = collect($recommendations)
            ->where('priority_level', 'HIGH')
            ->count();

        $moderate = collect($recommendations)
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisory = collect($recommendations)
            ->where('priority_level', 'ADVISORY')
            ->count();

        $top = $recommendations[0] ?? null;

        return [
            'total_recommendations' => count($recommendations),
            'critical_recommendations' => $critical,
            'high_recommendations' => $high,
            'moderate_recommendations' => $moderate,
            'advisory_recommendations' => $advisory,
            'top_recommendation_code' =>
                $top['recommendation_code'] ?? null,
            'top_recommendation_priority' =>
                $top['priority_level'] ?? null,
        ];
    }

    protected function determineRecommendationStatus(array $summary): string
    {
        if (($summary['critical_recommendations'] ?? 0) > 0) {
            return 'CRITICAL_STRATEGIC_PLAN_MANAGEMENT_ATTENTION';
        }

        if (($summary['high_recommendations'] ?? 0) >= 3) {
            return 'ELEVATED_STRATEGIC_PLAN_MANAGEMENT_ATTENTION';
        }

        if (($summary['high_recommendations'] ?? 0) > 0) {
            return 'HIGH_STRATEGIC_PLAN_MANAGEMENT_ATTENTION';
        }

        if (($summary['moderate_recommendations'] ?? 0) > 0) {
            return 'MODERATE_STRATEGIC_PLAN_MANAGEMENT_ATTENTION';
        }

        return 'CONTROLLED_STRATEGIC_PLAN_ADVISORY';
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
            fn (array $a, array $b) =>
                ($weights[$b['priority_level']] ?? 0)
                <=> ($weights[$a['priority_level']] ?? 0)
        );

        return array_values($recommendations);
    }

    protected function findDependencyByType(
        array $dependencies,
        string $type
    ): ?array {
        foreach ($dependencies as $dependency) {
            if (($dependency['dependency_type'] ?? null) === $type) {
                return $dependency;
            }
        }

        return null;
    }

    protected function formatNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}