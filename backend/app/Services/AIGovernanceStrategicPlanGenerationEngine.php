<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;
use App\Models\AIGovernanceStrategicSnapshot;

class AIGovernanceStrategicPlanGenerationEngine
{
    public function generate(?int $strategicSnapshotId = null): array
    {
        $strategicSnapshot = $strategicSnapshotId
            ? AIGovernanceStrategicSnapshot::find($strategicSnapshotId)
            : AIGovernanceStrategicSnapshot::latest('id')->first();

        if (!$strategicSnapshot) {
            return [
                'generated' => false,
                'status' => 'NO_STRATEGIC_SNAPSHOT_AVAILABLE',
                'message' => 'No AI governance strategic snapshot is available for strategic plan generation.',
            ];
        }

        $strategicState = app(
            AIGovernanceStrategicStateIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        $capacityDemand = app(
            AIGovernanceStrategicCapacityDemandIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        $constraint = app(
            AIGovernanceStrategicConstraintIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        $risk = app(
            AIGovernanceStrategicRiskIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        $recommendation = app(
            AIGovernanceStrategicRecommendationIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        $executive = app(
            AIGovernanceExecutiveStrategicIntelligenceEngine::class
        )->analyze($strategicSnapshot->id);

        /*
        |--------------------------------------------------------------------------
        | Core strategic intelligence
        |--------------------------------------------------------------------------
        */

        $strategicRiskLevel =
            $risk['strategic_risk_state']['strategic_risk_level']
            ?? 'UNDETERMINED_STRATEGIC_RISK';

        $strategicRiskScore = (float) (
            $risk['strategic_risk_state']['strategic_risk_score']
            ?? 0
        );

        $constraintLevel =
            $constraint['constraint_state']['constraint_level']
            ?? 'UNDETERMINED_STRATEGIC_CONSTRAINT';

        $strategicReadinessScore = (float) (
            $strategicState['strategic_state']['strategic_readiness_score']
            ?? $strategicSnapshot->strategic_readiness_score
            ?? 0
        );

        $strategicBalanceScore = (float) (
            $strategicState['strategic_state']['strategic_balance_score']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Plan priority
        |--------------------------------------------------------------------------
        */

        if (
            $strategicRiskLevel === 'CRITICAL_STRATEGIC_RISK'
            || ($risk['risk_summary']['critical_signals'] ?? 0) > 0
        ) {
            $strategicPriority = 'CRITICAL';
            $strategicPriorityScore = 100;
        } elseif (
            $strategicRiskLevel === 'HIGH_STRATEGIC_RISK'
            || ($risk['risk_summary']['high_signals'] ?? 0) > 0
        ) {
            $strategicPriority = 'HIGH';
            $strategicPriorityScore = max(70, $strategicRiskScore);
        } elseif ($strategicRiskScore >= 40) {
            $strategicPriority = 'MODERATE';
            $strategicPriorityScore = $strategicRiskScore;
        } else {
            $strategicPriority = 'ADVISORY';
            $strategicPriorityScore = $strategicRiskScore;
        }

        $strategicPriorityScore = round(
            min(100, max(0, $strategicPriorityScore)),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Planning readiness
        |--------------------------------------------------------------------------
        */

        $capacityScore = (float) (
            $capacityDemand['capacity_demand_state']['capacity_score']
            ?? 0
        );

        $pressureScore = (float) (
            $capacityDemand['capacity_demand_state']['demand_pressure_score']
            ?? 0
        );

        $constraintScore = (float) (
            $constraint['constraint_state']['constraint_score']
            ?? 0
        );

        $planningReadinessScore = round(
            (
                ($strategicReadinessScore * 0.35)
                + ($strategicBalanceScore * 0.20)
                + ($capacityScore * 0.25)
                + ((100 - min(100, $pressureScore)) * 0.10)
                + ((100 - min(100, $constraintScore)) * 0.10)
            ),
            2
        );

        if ($planningReadinessScore >= 75) {
            $planningReadiness = 'HIGH_READINESS';
        } elseif ($planningReadinessScore >= 60) {
            $planningReadiness = 'MODERATE_READINESS';
        } elseif ($planningReadinessScore >= 40) {
            $planningReadiness = 'LIMITED_READINESS';
        } else {
            $planningReadiness = 'LOW_READINESS';
        }

        /*
        |--------------------------------------------------------------------------
        | Primary recommendation
        |--------------------------------------------------------------------------
        */

        $topRecommendation =
            $recommendation['top_recommendation']
            ?? null;

        $primaryRecommendationCode =
            $topRecommendation['recommendation_code']
            ?? 'CONTINUE_HUMAN_STRATEGIC_GOVERNANCE_REVIEW';

        $primaryRecommendation =
            $topRecommendation['recommendation']
            ?? 'Continue human strategic governance review under current governance controls.';

        /*
        |--------------------------------------------------------------------------
        | Objectives
        |--------------------------------------------------------------------------
        */

        $objectives = [];

        foreach (
            ($recommendation['recommendations'] ?? [])
            as $index => $item
        ) {
            $objectives[] = [
                'objective_sequence' => $index + 1,
                'objective_code' =>
                    $item['recommendation_code'] ?? null,
                'objective_category' =>
                    $item['recommendation_category'] ?? null,
                'priority_level' =>
                    $item['priority_level'] ?? null,
                'objective' =>
                    $item['recommendation'] ?? null,
                'reason' =>
                    $item['reason'] ?? null,
                'related_action_count' =>
                    $item['related_action_count'] ?? null,
                'highest_related_action' =>
                    $item['highest_related_action'] ?? null,
                'human_review_required' => true,
                'automatic_execution_allowed' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Dependencies
        |--------------------------------------------------------------------------
        */

        $dependencies = [];

        $pendingHumanReviews =
            (int) $strategicSnapshot->pending_human_reviews;

        $evidenceWaitingActions =
            (int) $strategicSnapshot->evidence_waiting_actions;

        $deferredActions =
            (int) $strategicSnapshot->deferred_actions;

        if ($pendingHumanReviews > 0) {
            $dependencies[] = [
                'dependency_code' =>
                    'PENDING_HUMAN_GOVERNANCE_REVIEW',
                'dependency_type' => 'HUMAN_REVIEW',
                'status' => 'ACTIVE',
                'severity' => 'MODERATE',
                'value' => $pendingHumanReviews,
                'message' =>
                    'Strategic governance progression depends on completion of outstanding human governance reviews.',
            ];
        }

        if ($evidenceWaitingActions > 0) {
            $dependencies[] = [
                'dependency_code' =>
                    'VALIDATED_GOVERNANCE_EVIDENCE',
                'dependency_type' => 'EVIDENCE',
                'status' => 'ACTIVE',
                'severity' => 'HIGH',
                'value' => $evidenceWaitingActions,
                'message' =>
                    'Strategic governance progression depends on additional validated evidence.',
            ];
        }

        if ($deferredActions > 0) {
            $dependencies[] = [
                'dependency_code' =>
                    'DEFERRED_GOVERNANCE_REASSESSMENT',
                'dependency_type' => 'DEFERRED_WORK',
                'status' => 'ACTIVE',
                'severity' => 'ADVISORY',
                'value' => $deferredActions,
                'message' =>
                    'Deferred governance work remains dependent on future reassessment conditions.',
            ];
        }

        if ($capacityScore < 50) {
            $dependencies[] = [
                'dependency_code' =>
                    'GOVERNANCE_CAPACITY_RECOVERY',
                'dependency_type' => 'CAPACITY',
                'status' => 'ACTIVE',
                'severity' => 'HIGH',
                'value' => $capacityScore,
                'message' =>
                    'Governance capacity should improve before strategic workload expansion.',
            ];
        }

        if ($constraintScore >= 60) {
            $dependencies[] = [
                'dependency_code' =>
                    'STRATEGIC_CONSTRAINT_RELIEF',
                'dependency_type' =>
                    'STRATEGIC_CONSTRAINT',
                'status' => 'ACTIVE',
                'severity' => 'HIGH',
                'value' => $constraintScore,
                'message' =>
                    'Material strategic governance constraints require reduction before strategic flexibility improves.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Resource context
        |--------------------------------------------------------------------------
        */

        $resourceContext = [
            'governance_capacity_status' =>
                $capacityDemand['capacity_demand_state']['capacity_status']
                ?? $strategicSnapshot->capacity_status,

            'governance_capacity_score' =>
                $capacityScore,

            'demand_pressure_status' =>
                $capacityDemand['capacity_demand_state']['demand_pressure_status']
                ?? $strategicSnapshot->demand_pressure_status,

            'demand_pressure_score' =>
                $pressureScore,

            'capacity_demand_gap' =>
                $capacityDemand['capacity_demand_state']['capacity_demand_gap']
                ?? null,

            'capacity_demand_balance' =>
                $capacityDemand['capacity_demand_state']['capacity_demand_balance']
                ?? null,

            'strategic_load_status' =>
                $capacityDemand['capacity_demand_state']['strategic_load_status']
                ?? null,

            'workload_expansion_readiness' =>
                $capacityDemand['capacity_demand_state']['workload_expansion_readiness']
                ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Planning context
        |--------------------------------------------------------------------------
        */

        $planningContext = [
            'strategic_status' =>
                $strategicState['strategic_state']['strategic_status']
                ?? $strategicSnapshot->strategic_status,

            'strategic_health' =>
                $strategicState['strategic_state']['strategic_health']
                ?? null,

            'strategic_readiness' =>
                $strategicState['strategic_state']['strategic_readiness']
                ?? $strategicSnapshot->strategic_readiness,

            'strategic_readiness_score' =>
                $strategicReadinessScore,

            'strategic_balance_score' =>
                $strategicBalanceScore,

            'strategic_maturity' =>
                $strategicState['strategic_state']['strategic_maturity']
                ?? null,

            'strategic_confidence' =>
                $strategicState['strategic_state']['strategic_confidence']
                ?? null,

            'constraint_level' =>
                $constraintLevel,

            'constraint_score' =>
                $constraintScore,

            'strategic_flexibility' =>
                $constraint['constraint_state']['strategic_flexibility']
                ?? null,

            'strategic_risk_level' =>
                $strategicRiskLevel,

            'strategic_risk_score' =>
                $strategicRiskScore,

            'recommendation_status' =>
                $recommendation['recommendation_state']['recommendation_status']
                ?? null,

            'total_recommendations' =>
                $recommendation['recommendation_summary']['total_recommendations']
                ?? 0,

            'executive_strategic_status' =>
                $executive['executive_strategic_state']['executive_strategic_status']
                ?? null,

            'executive_readiness' =>
                $executive['executive_strategic_state']['executive_readiness']
                ?? null,

            'executive_strategic_score' =>
                $executive['executive_strategic_state']['executive_strategic_score']
                ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Source context
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
            'strategic_snapshot_id' =>
                $strategicSnapshot->id,

            'operational_snapshot_id' =>
                $strategicSnapshot->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $strategicSnapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $strategicSnapshot->snapshot_scope,

            'resident_id' =>
                $strategicSnapshot->resident_id,

            'action_closure_percentage' =>
                (float) $strategicSnapshot->action_closure_percentage,

            'decision_completion_percentage' =>
                (float) $strategicSnapshot->decision_completion_percentage,

            'pending_human_reviews' =>
                $pendingHumanReviews,

            'evidence_waiting_actions' =>
                $evidenceWaitingActions,

            'deferred_actions' =>
                $deferredActions,

            'high_priority_active_actions' =>
                (int) $strategicSnapshot->high_priority_active_actions,

            'critical_priority_active_actions' =>
                (int) $strategicSnapshot->critical_priority_active_actions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Persist strategic plan
        |--------------------------------------------------------------------------
        */

        $plan = AIGovernanceStrategicPlan::create([
            'strategic_snapshot_id' =>
                $strategicSnapshot->id,

            'operational_snapshot_id' =>
                $strategicSnapshot->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $strategicSnapshot->lifecycle_snapshot_id,

            'plan_scope' =>
                $strategicSnapshot->snapshot_scope,

            'resident_id' =>
                $strategicSnapshot->resident_id,

            'plan_code' =>
                'GOV-STRATEGIC-PLAN-'
                . $strategicSnapshot->id
                . '-'
                . now()->format('YmdHis'),

            'plan_status' =>
                'DRAFT_FOR_HUMAN_REVIEW',

            'planning_mode' =>
                'HUMAN_GOVERNED_STRATEGIC_PLAN',

            'strategic_priority' =>
                $strategicPriority,

            'strategic_priority_score' =>
                $strategicPriorityScore,

            'planning_readiness' =>
                $planningReadiness,

            'planning_readiness_score' =>
                $planningReadinessScore,

            'risk_level' =>
                $strategicRiskLevel,

            'risk_score' =>
                $strategicRiskScore,

            'constraint_level' =>
                $constraintLevel,

            'primary_recommendation_code' =>
                $primaryRecommendationCode,

            'primary_recommendation' =>
                $primaryRecommendation,

            'objectives' =>
                $objectives,

            'dependencies' =>
                $dependencies,

            'resource_context' =>
                $resourceContext,

            'planning_context' =>
                $planningContext,

            'source_context' =>
                $sourceContext,

            'automatic_execution_allowed' => false,
            'automatic_change_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,
            'automatic_clinical_action_allowed' => false,

            'human_review_required' => true,
            'governance_validation_required' => true,

            'generated_at' => now(),
        ]);

        return [
            'generated' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_GENERATED',

            'message' =>
                'AI governance strategic plan generated successfully for human governance review.',

            'plan' => [
                'strategic_plan_id' =>
                    $plan->id,

                'plan_code' =>
                    $plan->plan_code,

                'strategic_snapshot_id' =>
                    $plan->strategic_snapshot_id,

                'operational_snapshot_id' =>
                    $plan->operational_snapshot_id,

                'lifecycle_snapshot_id' =>
                    $plan->lifecycle_snapshot_id,

                'plan_scope' =>
                    $plan->plan_scope,

                'resident_id' =>
                    $plan->resident_id,

                'plan_status' =>
                    $plan->plan_status,

                'planning_mode' =>
                    $plan->planning_mode,

                'strategic_priority' =>
                    $plan->strategic_priority,

                'strategic_priority_score' =>
                    $plan->strategic_priority_score,

                'planning_readiness' =>
                    $plan->planning_readiness,

                'planning_readiness_score' =>
                    $plan->planning_readiness_score,

                'risk_level' =>
                    $plan->risk_level,

                'risk_score' =>
                    $plan->risk_score,

                'constraint_level' =>
                    $plan->constraint_level,

                'primary_recommendation_code' =>
                    $plan->primary_recommendation_code,

                'primary_recommendation' =>
                    $plan->primary_recommendation,

                'objective_count' =>
                    count($objectives),

                'dependency_count' =>
                    count($dependencies),

                'generated_at' =>
                    $plan->generated_at,
            ],

            'planning_context' =>
                $planningContext,

            'resource_context' =>
                $resourceContext,

            'strategic_plan_guardrails' => [
                'strategic_plan_generation_enabled' => true,

                'plan_is_governance_decision' => false,
                'plan_is_governance_approval' => false,
                'plan_is_governance_rejection' => false,
                'plan_is_action_resolution' => false,

                'plan_changes_action_state' => false,
                'plan_changes_priority' => false,
                'plan_changes_eligibility' => false,

                'plan_authorizes_execution' => false,
                'plan_authorizes_deployment' => false,
                'plan_authorizes_rollback' => false,
                'plan_authorizes_clinical_action' => false,

                'planning_readiness_expands_ai_authority' => false,
                'strategic_priority_authorizes_automation' => false,
                'risk_level_authorizes_automation' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Governance strategic plan generation converts Step 62 strategic intelligence into a structured draft plan for human governance review only. Strategic plans do not make governance decisions, resolve actions, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}