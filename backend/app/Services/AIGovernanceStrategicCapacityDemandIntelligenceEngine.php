<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicSnapshot;

class AIGovernanceStrategicCapacityDemandIntelligenceEngine
{
    public function analyze(?int $strategicSnapshotId = null): array
    {
        $snapshot = $strategicSnapshotId
            ? AIGovernanceStrategicSnapshot::find($strategicSnapshotId)
            : AIGovernanceStrategicSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'analysis_completed' => false,
                'status' => 'STRATEGIC_SNAPSHOT_NOT_AVAILABLE',
                'message' => 'No AI governance strategic snapshot is available for capacity and demand analysis.',
            ];
        }

        $capacityScore = (float) ($snapshot->governance_capacity_score ?? 0);
        $pressureScore = (float) ($snapshot->governance_pressure_score ?? 0);

        $totalActions = (int) ($snapshot->total_governance_actions ?? 0);
        $activeActions = (int) ($snapshot->active_governance_actions ?? 0);
        $closedActions = (int) ($snapshot->closed_governance_actions ?? 0);
        $pendingReviews = (int) ($snapshot->pending_human_reviews ?? 0);
        $evidenceWaiting = (int) ($snapshot->evidence_waiting_actions ?? 0);
        $deferredActions = (int) ($snapshot->deferred_actions ?? 0);
        $highPriorityActive = (int) ($snapshot->high_priority_active_actions ?? 0);
        $criticalPriorityActive = (int) ($snapshot->critical_priority_active_actions ?? 0);

        $closurePercentage = (float) ($snapshot->action_closure_percentage ?? 0);
        $decisionCompletion = (float) ($snapshot->decision_completion_percentage ?? 0);

        $capacityUtilization = $totalActions > 0
            ? round(($activeActions / $totalActions) * 100, 2)
            : 0.0;

        $reviewDemandPercentage = $totalActions > 0
            ? round(($pendingReviews / $totalActions) * 100, 2)
            : 0.0;

        $evidenceDemandPercentage = $totalActions > 0
            ? round(($evidenceWaiting / $totalActions) * 100, 2)
            : 0.0;

        $deferredDemandPercentage = $totalActions > 0
            ? round(($deferredActions / $totalActions) * 100, 2)
            : 0.0;

        $priorityPressurePercentage = $activeActions > 0
            ? round((($highPriorityActive + $criticalPriorityActive) / $activeActions) * 100, 2)
            : 0.0;

        $capacityDemandGap = round($pressureScore - $capacityScore, 2);

        $capacityDemandBalance = $this->determineCapacityDemandBalance(
            $capacityScore,
            $pressureScore,
            $capacityDemandGap
        );

        $capacityAdequacy = $this->determineCapacityAdequacy(
            $capacityScore,
            $activeActions,
            $pendingReviews,
            $evidenceWaiting
        );

        $demandIntensity = $this->determineDemandIntensity(
            $pressureScore,
            $highPriorityActive,
            $criticalPriorityActive,
            $pendingReviews,
            $evidenceWaiting
        );

        $strategicLoadStatus = $this->determineStrategicLoadStatus(
            $capacityUtilization,
            $capacityDemandGap,
            $criticalPriorityActive
        );

        $expansionReadiness = $this->determineExpansionReadiness(
            $capacityScore,
            $pressureScore,
            $closurePercentage,
            $capacityDemandGap
        );

        $findings = [
            "Strategic capacity and demand intelligence is based on strategic snapshot {$snapshot->id}.",
            "Current governance capacity score is {$capacityScore}.",
            "Current governance demand pressure score is {$pressureScore}.",
            "Current capacity-demand gap is {$capacityDemandGap} percentage point(s).",
            "Current capacity-demand balance is {$capacityDemandBalance}.",
            "Current governance capacity adequacy is {$capacityAdequacy}.",
            "Current governance demand intensity is {$demandIntensity}.",
            "Current strategic governance load status is {$strategicLoadStatus}.",
            "Current active governance workload represents {$capacityUtilization}% of the governance portfolio.",
            "Pending human review demand represents {$reviewDemandPercentage}% of the governance portfolio.",
            "Evidence-dependent demand represents {$evidenceDemandPercentage}% of the governance portfolio.",
            "Deferred governance demand represents {$deferredDemandPercentage}% of the governance portfolio.",
            "High or critical priority work represents {$priorityPressurePercentage}% of active governance work.",
            "Current strategic workload expansion readiness is {$expansionReadiness}.",
        ];

        if ($capacityDemandGap > 20) {
            $findings[] =
                'Governance demand materially exceeds currently available strategic capacity.';
        }

        if ($capacityScore < 50) {
            $findings[] =
                'Governance capacity remains below the preferred minimum level for sustainable strategic expansion.';
        }

        if ($pressureScore >= 60) {
            $findings[] =
                'Governance demand pressure remains elevated and should be reduced before materially increasing workload.';
        }

        if ($closurePercentage < 50) {
            $findings[] =
                "Governance action closure remains at {$closurePercentage}%, indicating incomplete workload absorption.";
        }

        if ($evidenceWaiting > 0) {
            $findings[] =
                "{$evidenceWaiting} governance action(s) remain dependent on additional evidence and therefore consume strategic capacity without progressing to closure.";
        }

        $managementPriorities = [];

        if ($capacityDemandGap > 20) {
            $managementPriorities[] =
                'Reduce the gap between governance demand and available governance capacity before increasing strategic workload.';
        }

        if ($capacityScore < 50) {
            $managementPriorities[] =
                'Strengthen governance review, evidence, and closure capacity before expanding strategic governance activity.';
        }

        if ($pressureScore >= 60) {
            $managementPriorities[] =
                'Reduce high governance demand pressure through governed workload progression rather than weakened controls.';
        }

        if ($pendingReviews > 0) {
            $managementPriorities[] =
                'Complete pending human governance reviews in descending priority order.';
        }

        if ($evidenceWaiting > 0) {
            $managementPriorities[] =
                'Increase validated evidence availability for governance actions blocked by evidence dependency.';
        }

        if ($closurePercentage < 50) {
            $managementPriorities[] =
                'Improve formal governance action closure progression to release constrained governance capacity.';
        }

        if ($deferredActions > 0) {
            $managementPriorities[] =
                'Maintain deferred governance work under observation without allowing it to consume unnecessary active review capacity.';
        }

        $managementPriorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation while increasing governance capacity.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_CAPACITY_DEMAND_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $snapshot->id,
            'operational_snapshot_id' => $snapshot->operational_snapshot_id,
            'lifecycle_snapshot_id' => $snapshot->lifecycle_snapshot_id,
            'snapshot_scope' => $snapshot->snapshot_scope,
            'resident_id' => $snapshot->resident_id,

            'capacity_demand_state' => [
                'capacity_status' => $snapshot->capacity_status,
                'capacity_score' => $capacityScore,
                'capacity_adequacy' => $capacityAdequacy,
                'demand_pressure_status' => $snapshot->demand_pressure_status,
                'demand_pressure_score' => $pressureScore,
                'demand_intensity' => $demandIntensity,
                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $capacityDemandBalance,
                'strategic_load_status' => $strategicLoadStatus,
                'workload_expansion_readiness' => $expansionReadiness,
            ],

            'portfolio_capacity_context' => [
                'total_governance_actions' => $totalActions,
                'active_governance_actions' => $activeActions,
                'closed_governance_actions' => $closedActions,
                'capacity_utilization_percentage' => $capacityUtilization,
                'action_closure_percentage' => $closurePercentage,
                'decision_completion_percentage' => $decisionCompletion,
            ],

            'governance_demand_context' => [
                'pending_human_reviews' => $pendingReviews,
                'pending_review_demand_percentage' => $reviewDemandPercentage,
                'evidence_waiting_actions' => $evidenceWaiting,
                'evidence_demand_percentage' => $evidenceDemandPercentage,
                'deferred_actions' => $deferredActions,
                'deferred_demand_percentage' => $deferredDemandPercentage,
                'high_priority_active_actions' => $highPriorityActive,
                'critical_priority_active_actions' => $criticalPriorityActive,
                'priority_pressure_percentage' => $priorityPressurePercentage,
            ],

            'capacity_signals' => [
                'capacity_below_preferred_threshold' => $capacityScore < 50,
                'demand_exceeds_capacity' => $pressureScore > $capacityScore,
                'material_capacity_gap' => $capacityDemandGap > 20,
                'high_governance_pressure' => $pressureScore >= 60,
                'critical_governance_pressure' => $pressureScore >= 80,
                'low_closure_throughput' => $closurePercentage < 50,
                'evidence_dependency_present' => $evidenceWaiting > 0,
                'pending_review_demand_present' => $pendingReviews > 0,
                'high_priority_pressure_present' => ($highPriorityActive + $criticalPriorityActive) > 0,
            ],

            'capacity_findings' => $findings,

            'management_priorities' => $managementPriorities,

            'capacity_guardrails' => [
                'strategic_capacity_demand_intelligence_enabled' => true,
                'capacity_score_is_staff_performance_rating' => false,
                'capacity_score_is_governance_decision' => false,
                'capacity_demand_gap_changes_priority' => false,
                'capacity_demand_gap_changes_eligibility' => false,
                'capacity_shortage_authorizes_automation' => false,
                'demand_pressure_authorizes_execution' => false,
                'expansion_readiness_is_execution_authorization' => false,
                'capacity_targets_override_human_review' => false,
                'capacity_targets_override_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' =>
                    'Governance strategic capacity and demand intelligence evaluates governance workload demand, available capacity, pressure, constraints, and expansion readiness for human strategic planning only. Capacity or demand conditions do not make governance decisions, change action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function determineCapacityDemandBalance(
        float $capacityScore,
        float $pressureScore,
        float $gap
    ): string {
        if ($gap >= 35) {
            return 'SEVERELY_DEMAND_HEAVY';
        }

        if ($gap >= 20) {
            return 'MATERIALLY_DEMAND_HEAVY';
        }

        if ($gap >= 10) {
            return 'MODERATELY_DEMAND_HEAVY';
        }

        if ($gap > -10) {
            return 'RELATIVELY_BALANCED';
        }

        if ($capacityScore > $pressureScore) {
            return 'CAPACITY_FAVORABLE';
        }

        return 'RELATIVELY_BALANCED';
    }

    private function determineCapacityAdequacy(
        float $capacityScore,
        int $activeActions,
        int $pendingReviews,
        int $evidenceWaiting
    ): string {
        if ($capacityScore < 30) {
            return 'SEVERELY_CONSTRAINED';
        }

        if (
            $capacityScore < 50 ||
            $pendingReviews >= 2 ||
            $evidenceWaiting > 0
        ) {
            return 'CONSTRAINED';
        }

        if ($capacityScore < 70 || $activeActions > 0) {
            return 'DEVELOPING';
        }

        return 'ADEQUATE';
    }

    private function determineDemandIntensity(
        float $pressureScore,
        int $highPriority,
        int $criticalPriority,
        int $pendingReviews,
        int $evidenceWaiting
    ): string {
        if (
            $pressureScore >= 80 ||
            $criticalPriority > 0
        ) {
            return 'CRITICAL';
        }

        if (
            $pressureScore >= 60 ||
            $highPriority > 0 ||
            ($pendingReviews + $evidenceWaiting) >= 3
        ) {
            return 'HIGH';
        }

        if (
            $pressureScore >= 40 ||
            $pendingReviews > 0 ||
            $evidenceWaiting > 0
        ) {
            return 'MODERATE';
        }

        return 'LOW';
    }

    private function determineStrategicLoadStatus(
        float $utilization,
        float $gap,
        int $criticalPriority
    ): string {
        if ($criticalPriority > 0 || $gap >= 35) {
            return 'OVERLOADED';
        }

        if ($gap >= 20 || $utilization >= 70) {
            return 'HEAVILY_LOADED';
        }

        if ($gap >= 10 || $utilization >= 50) {
            return 'MODERATELY_LOADED';
        }

        return 'CONTROLLED_LOAD';
    }

    private function determineExpansionReadiness(
        float $capacityScore,
        float $pressureScore,
        float $closurePercentage,
        float $gap
    ): string {
        if (
            $capacityScore >= 70 &&
            $pressureScore < 40 &&
            $closurePercentage >= 70 &&
            $gap <= 0
        ) {
            return 'READY_FOR_CONTROLLED_EXPANSION';
        }

        if (
            $capacityScore >= 55 &&
            $pressureScore < 60 &&
            $closurePercentage >= 50 &&
            $gap < 15
        ) {
            return 'CONDITIONALLY_READY';
        }

        return 'NOT_READY_FOR_EXPANSION';
    }
}