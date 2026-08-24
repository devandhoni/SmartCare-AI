<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicSnapshot;

class AIGovernanceStrategicStateIntelligenceEngine
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
                'message' => 'No AI governance strategic snapshot is available for strategic state analysis.',
            ];
        }

        $readinessScore = (float) ($snapshot->strategic_readiness_score ?? 0);
        $capacityScore = (float) ($snapshot->governance_capacity_score ?? 0);
        $pressureScore = (float) ($snapshot->governance_pressure_score ?? 0);

        $strategicHealth = $this->determineStrategicHealth(
            $readinessScore,
            $capacityScore,
            $pressureScore
        );

        $strategicAttention = $this->determineStrategicAttention(
            $snapshot,
            $pressureScore
        );

        $constraintSeverity = $this->determineConstraintSeverity(
            $snapshot,
            $pressureScore
        );

        $strategicMaturity = $this->determineStrategicMaturity(
            $readinessScore,
            $capacityScore,
            $snapshot
        );

        $strategicConfidence = $this->determineStrategicConfidence($snapshot);

        $strategicBalanceScore = round(
            max(
                0,
                min(
                    100,
                    (
                        ($readinessScore * 0.40) +
                        ($capacityScore * 0.35) +
                        ((100 - $pressureScore) * 0.25)
                    )
                )
            ),
            2
        );

        $findings = [
            "Strategic governance intelligence is based on strategic snapshot {$snapshot->id}.",
            "Current strategic status is {$snapshot->strategic_status}.",
            "Current strategic readiness is {$snapshot->strategic_readiness} with readiness score {$readinessScore}.",
            "Current governance capacity is {$snapshot->capacity_status} with capacity score {$capacityScore}.",
            "Current governance pressure is {$snapshot->demand_pressure_status} with pressure score {$pressureScore}.",
            "Current strategic constraint status is {$snapshot->constraint_status}.",
            "Current strategic health is {$strategicHealth}.",
            "Current strategic attention level is {$strategicAttention}.",
            "Current strategic constraint severity is {$constraintSeverity}.",
            "Current strategic maturity is {$strategicMaturity}.",
            "Current strategic balance score is {$strategicBalanceScore}.",
            "Strategic governance intelligence confidence is {$strategicConfidence}.",
        ];

        if ((int) $snapshot->high_priority_active_actions > 0) {
            $findings[] =
                "{$snapshot->high_priority_active_actions} high-priority governance action(s) remain active and continue to constrain strategic readiness.";
        }

        if ((int) $snapshot->pending_human_reviews > 0) {
            $findings[] =
                "{$snapshot->pending_human_reviews} governance action(s) remain pending human review.";
        }

        if ((int) $snapshot->evidence_waiting_actions > 0) {
            $findings[] =
                "{$snapshot->evidence_waiting_actions} governance action(s) remain dependent on additional validated evidence.";
        }

        if ((int) $snapshot->deferred_actions > 0) {
            $findings[] =
                "{$snapshot->deferred_actions} governance action(s) remain deferred under controlled observation.";
        }

        $managementPriorities = [];

        if ($pressureScore >= 60) {
            $managementPriorities[] =
                'Reduce material governance pressure before materially expanding strategic governance workload.';
        }

        if ($capacityScore < 50) {
            $managementPriorities[] =
                'Strengthen governance capacity before increasing strategic governance demand.';
        }

        if ((int) $snapshot->evidence_waiting_actions > 0) {
            $managementPriorities[] =
                'Increase validated evidence availability for evidence-dependent governance actions.';
        }

        if ((int) $snapshot->pending_human_reviews > 0) {
            $managementPriorities[] =
                'Complete outstanding human governance reviews in descending priority order.';
        }

        if ((float) $snapshot->action_closure_percentage < 50) {
            $managementPriorities[] =
                'Improve governed action closure progression while preserving review, evidence, safety, and authority controls.';
        }

        if ((int) $snapshot->deferred_actions > 0) {
            $managementPriorities[] =
                'Maintain deferred governance work under controlled observation until reassessment conditions are satisfied.';
        }

        $managementPriorities[] =
            'Preserve human review, governance validation, evidence controls, and authority separation while strategic readiness improves.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $snapshot->id,
            'operational_snapshot_id' => $snapshot->operational_snapshot_id,
            'lifecycle_snapshot_id' => $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' => $snapshot->snapshot_scope,
            'resident_id' => $snapshot->resident_id,

            'strategic_state' => [
                'strategic_status' => $snapshot->strategic_status,
                'strategic_health' => $strategicHealth,
                'strategic_readiness' => $snapshot->strategic_readiness,
                'strategic_readiness_score' => $readinessScore,
                'capacity_status' => $snapshot->capacity_status,
                'governance_capacity_score' => $capacityScore,
                'demand_pressure_status' => $snapshot->demand_pressure_status,
                'governance_pressure_score' => $pressureScore,
                'constraint_status' => $snapshot->constraint_status,
                'constraint_severity' => $constraintSeverity,
                'strategic_attention_level' => $strategicAttention,
                'strategic_maturity' => $strategicMaturity,
                'strategic_balance_score' => $strategicBalanceScore,
                'strategic_confidence' => $strategicConfidence,
            ],

            'governance_portfolio_context' => [
                'total_governance_actions' => (int) $snapshot->total_governance_actions,
                'active_governance_actions' => (int) $snapshot->active_governance_actions,
                'closed_governance_actions' => (int) $snapshot->closed_governance_actions,
                'pending_human_reviews' => (int) $snapshot->pending_human_reviews,
                'evidence_waiting_actions' => (int) $snapshot->evidence_waiting_actions,
                'deferred_actions' => (int) $snapshot->deferred_actions,
                'high_priority_active_actions' => (int) $snapshot->high_priority_active_actions,
                'critical_priority_active_actions' => (int) $snapshot->critical_priority_active_actions,
            ],

            'progress_context' => [
                'action_closure_percentage' => (float) $snapshot->action_closure_percentage,
                'decision_completion_percentage' => (float) $snapshot->decision_completion_percentage,
            ],

            'capacity_context' => [
                'capacity_status' => $snapshot->capacity_status,
                'capacity_score' => $capacityScore,
                'capacity_available' => $capacityScore >= 60,
                'capacity_constrained' => $capacityScore < 50,
            ],

            'pressure_context' => [
                'demand_pressure_status' => $snapshot->demand_pressure_status,
                'pressure_score' => $pressureScore,
                'high_pressure' => $pressureScore >= 60,
                'critical_pressure' => $pressureScore >= 80,
            ],

            'constraint_context' => [
                'constraint_status' => $snapshot->constraint_status,
                'constraint_severity' => $constraintSeverity,
                'constraints_present' => !in_array(
                    $snapshot->constraint_status,
                    [
                        'NO_MATERIAL_CONSTRAINTS',
                        'MINIMAL_CONSTRAINTS',
                    ],
                    true
                ),
            ],

            'strategic_findings' => $findings,

            'management_priorities' => $managementPriorities,

            'strategic_guardrails' => [
                'strategic_state_intelligence_enabled' => true,
                'strategic_intelligence_is_governance_decision' => false,
                'strategic_intelligence_is_governance_approval' => false,
                'strategic_intelligence_is_action_resolution' => false,
                'strategic_readiness_is_execution_authorization' => false,
                'strategic_health_changes_action_state' => false,
                'strategic_attention_changes_priority' => false,
                'strategic_attention_changes_eligibility' => false,
                'strategic_maturity_expands_ai_authority' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' =>
                    'Governance strategic state intelligence interprets strategic readiness, governance capacity, pressure, constraints, maturity, and management attention for human strategic oversight only. It does not make governance decisions, resolve actions, change priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function determineStrategicHealth(
        float $readinessScore,
        float $capacityScore,
        float $pressureScore
    ): string {
        if ($pressureScore >= 80 || $capacityScore < 25) {
            return 'STRATEGICALLY_STRESSED';
        }

        if ($pressureScore >= 60 || $capacityScore < 50 || $readinessScore < 50) {
            return 'CONTROLLED_WITH_MATERIAL_CONSTRAINTS';
        }

        if ($pressureScore >= 40 || $readinessScore < 70) {
            return 'CONTROLLED_DEVELOPING_STATE';
        }

        return 'STRATEGICALLY_HEALTHY';
    }

    private function determineStrategicAttention(
        AIGovernanceStrategicSnapshot $snapshot,
        float $pressureScore
    ): string {
        if (
            (int) $snapshot->critical_priority_active_actions > 0 ||
            $pressureScore >= 80
        ) {
            return 'CRITICAL';
        }

        if (
            (int) $snapshot->high_priority_active_actions > 0 ||
            $pressureScore >= 60 ||
            (int) $snapshot->evidence_waiting_actions > 0
        ) {
            return 'HIGH';
        }

        if (
            (int) $snapshot->pending_human_reviews > 0 ||
            (int) $snapshot->deferred_actions > 0 ||
            $pressureScore >= 40
        ) {
            return 'MODERATE';
        }

        return 'ROUTINE';
    }

    private function determineConstraintSeverity(
        AIGovernanceStrategicSnapshot $snapshot,
        float $pressureScore
    ): string {
        if (
            $snapshot->constraint_status === 'CRITICAL_CONSTRAINTS_PRESENT' ||
            $pressureScore >= 80
        ) {
            return 'CRITICAL';
        }

        if (
            $snapshot->constraint_status === 'MATERIAL_CONSTRAINTS_PRESENT' ||
            (int) $snapshot->high_priority_active_actions > 0 ||
            (int) $snapshot->evidence_waiting_actions > 0
        ) {
            return 'HIGH';
        }

        if (
            $snapshot->constraint_status === 'MODERATE_CONSTRAINTS_PRESENT' ||
            (int) $snapshot->pending_human_reviews > 0
        ) {
            return 'MODERATE';
        }

        return 'LOW';
    }

    private function determineStrategicMaturity(
        float $readinessScore,
        float $capacityScore,
        AIGovernanceStrategicSnapshot $snapshot
    ): string {
        $closure = (float) $snapshot->action_closure_percentage;
        $decisionCompletion = (float) $snapshot->decision_completion_percentage;

        $score = (
            ($readinessScore * 0.35) +
            ($capacityScore * 0.25) +
            ($closure * 0.20) +
            ($decisionCompletion * 0.20)
        );

        if ($score >= 80) {
            return 'ADVANCED';
        }

        if ($score >= 65) {
            return 'ESTABLISHED';
        }

        if ($score >= 50) {
            return 'DEVELOPING';
        }

        return 'EARLY';
    }

    private function determineStrategicConfidence(
        AIGovernanceStrategicSnapshot $snapshot
    ): string {
        $totalActions = (int) $snapshot->total_governance_actions;

        if ($totalActions >= 30) {
            return 'HIGH';
        }

        if ($totalActions >= 15) {
            return 'MODERATE';
        }

        if ($totalActions >= 8) {
            return 'LIMITED';
        }

        return 'VERY_LIMITED';
    }
}