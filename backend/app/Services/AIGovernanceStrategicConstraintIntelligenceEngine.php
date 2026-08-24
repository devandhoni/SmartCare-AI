<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicSnapshot;

class AIGovernanceStrategicConstraintIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine
    ) {
    }

    public function analyze(?int $strategicSnapshotId = null): array
    {
        $snapshot = $strategicSnapshotId
            ? AIGovernanceStrategicSnapshot::find($strategicSnapshotId)
            : AIGovernanceStrategicSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'analysis_completed' => false,
                'status' => 'STRATEGIC_SNAPSHOT_NOT_AVAILABLE',
                'message' => 'No AI governance strategic snapshot is available for strategic constraint analysis.',
            ];
        }

        $strategicState = $this->strategicStateEngine->analyze($snapshot->id);
        $capacityDemand = $this->capacityDemandEngine->analyze($snapshot->id);

        if (
            !($strategicState['analysis_completed'] ?? false) ||
            !($capacityDemand['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'STRATEGIC_SOURCE_INTELLIGENCE_UNAVAILABLE',
                'strategic_snapshot_id' => $snapshot->id,
                'message' => 'Required strategic state or capacity-demand intelligence is unavailable.',
            ];
        }

        $state = $strategicState['strategic_state'] ?? [];
        $capacityState = $capacityDemand['capacity_demand_state'] ?? [];

        $totalActions = (int) ($snapshot->total_governance_actions ?? 0);
        $activeActions = (int) ($snapshot->active_governance_actions ?? 0);
        $pendingReviews = (int) ($snapshot->pending_human_reviews ?? 0);
        $evidenceWaiting = (int) ($snapshot->evidence_waiting_actions ?? 0);
        $deferredActions = (int) ($snapshot->deferred_actions ?? 0);
        $highPriorityActive = (int) ($snapshot->high_priority_active_actions ?? 0);
        $criticalPriorityActive = (int) ($snapshot->critical_priority_active_actions ?? 0);

        $closurePercentage = (float) ($snapshot->action_closure_percentage ?? 0);
        $decisionCompletion = (float) ($snapshot->decision_completion_percentage ?? 0);

        $capacityScore = (float) ($capacityState['capacity_score'] ?? $snapshot->governance_capacity_score ?? 0);
        $pressureScore = (float) ($capacityState['demand_pressure_score'] ?? $snapshot->governance_pressure_score ?? 0);
        $capacityDemandGap = (float) ($capacityState['capacity_demand_gap'] ?? 0);

        $strategicReadinessScore = (float) ($state['strategic_readiness_score'] ?? $snapshot->strategic_readiness_score ?? 0);
        $strategicBalanceScore = (float) ($state['strategic_balance_score'] ?? 0);

        $constraints = [
            'capacity_constraint' => [
                'detected' => $capacityScore < 50,
                'constraint_type' => 'GOVERNANCE_CAPACITY',
                'severity' => $capacityScore < 30 ? 'CRITICAL' : ($capacityScore < 50 ? 'HIGH' : 'NONE'),
                'value' => $capacityScore,
                'threshold' => 50.0,
                'message' => 'Governance capacity remains below the preferred strategic operating threshold.',
            ],

            'demand_pressure_constraint' => [
                'detected' => $pressureScore >= 60,
                'constraint_type' => 'GOVERNANCE_DEMAND_PRESSURE',
                'severity' => $pressureScore >= 80 ? 'CRITICAL' : ($pressureScore >= 60 ? 'HIGH' : 'NONE'),
                'value' => $pressureScore,
                'threshold' => 60.0,
                'message' => 'Governance demand pressure remains elevated relative to preferred strategic operating conditions.',
            ],

            'capacity_demand_gap_constraint' => [
                'detected' => $capacityDemandGap >= 20,
                'constraint_type' => 'CAPACITY_DEMAND_IMBALANCE',
                'severity' => $capacityDemandGap >= 35 ? 'CRITICAL' : ($capacityDemandGap >= 20 ? 'HIGH' : 'NONE'),
                'value' => $capacityDemandGap,
                'threshold' => 20.0,
                'message' => 'Governance demand materially exceeds available governance capacity.',
            ],

            'closure_constraint' => [
                'detected' => $closurePercentage < 50,
                'constraint_type' => 'LOW_ACTION_CLOSURE',
                'severity' => $closurePercentage < 25 ? 'HIGH' : ($closurePercentage < 50 ? 'MODERATE' : 'NONE'),
                'value' => $closurePercentage,
                'threshold' => 50.0,
                'message' => 'Governance action closure remains below the preferred strategic progression threshold.',
            ],

            'human_review_constraint' => [
                'detected' => $pendingReviews > 0,
                'constraint_type' => 'PENDING_HUMAN_REVIEW',
                'severity' => $pendingReviews >= 5 ? 'HIGH' : ($pendingReviews > 0 ? 'MODERATE' : 'NONE'),
                'value' => $pendingReviews,
                'message' => 'Strategic governance progression remains dependent on outstanding human governance reviews.',
            ],

            'evidence_dependency_constraint' => [
                'detected' => $evidenceWaiting > 0,
                'constraint_type' => 'EVIDENCE_DEPENDENCY',
                'severity' => $evidenceWaiting >= 3 ? 'HIGH' : ($evidenceWaiting > 0 ? 'MODERATE' : 'NONE'),
                'value' => $evidenceWaiting,
                'message' => 'Governance work remains unable to progress until additional validated evidence becomes available.',
            ],

            'deferred_work_constraint' => [
                'detected' => $deferredActions > 0,
                'constraint_type' => 'DEFERRED_GOVERNANCE_WORK',
                'severity' => $deferredActions >= 3 ? 'MODERATE' : ($deferredActions > 0 ? 'ADVISORY' : 'NONE'),
                'value' => $deferredActions,
                'message' => 'Deferred governance work remains intentionally paused and continues to affect strategic progression.',
            ],

            'priority_work_constraint' => [
                'detected' => ($highPriorityActive + $criticalPriorityActive) > 0,
                'constraint_type' => 'HIGH_PRIORITY_ACTIVE_WORK',
                'severity' => $criticalPriorityActive > 0 ? 'CRITICAL' : ($highPriorityActive > 0 ? 'HIGH' : 'NONE'),
                'value' => [
                    'high_priority_active_actions' => $highPriorityActive,
                    'critical_priority_active_actions' => $criticalPriorityActive,
                ],
                'message' => 'High or critical priority governance work remains active and constrains strategic flexibility.',
            ],

            'strategic_readiness_constraint' => [
                'detected' => $strategicReadinessScore < 50,
                'constraint_type' => 'LOW_STRATEGIC_READINESS',
                'severity' => $strategicReadinessScore < 30 ? 'HIGH' : ($strategicReadinessScore < 50 ? 'MODERATE' : 'NONE'),
                'value' => $strategicReadinessScore,
                'threshold' => 50.0,
                'message' => 'Strategic governance readiness remains below the preferred readiness threshold.',
            ],

            'strategic_balance_constraint' => [
                'detected' => $strategicBalanceScore < 50,
                'constraint_type' => 'STRATEGIC_IMBALANCE',
                'severity' => $strategicBalanceScore < 30 ? 'HIGH' : ($strategicBalanceScore < 50 ? 'MODERATE' : 'NONE'),
                'value' => $strategicBalanceScore,
                'threshold' => 50.0,
                'message' => 'Strategic governance balance remains constrained by current capacity, pressure, and workload conditions.',
            ],
        ];

        $detectedConstraints = collect($constraints)
            ->filter(fn (array $constraint) => $constraint['detected'])
            ->values()
            ->all();

        $criticalCount = collect($detectedConstraints)
            ->where('severity', 'CRITICAL')
            ->count();

        $highCount = collect($detectedConstraints)
            ->where('severity', 'HIGH')
            ->count();

        $moderateCount = collect($detectedConstraints)
            ->where('severity', 'MODERATE')
            ->count();

        $advisoryCount = collect($detectedConstraints)
            ->where('severity', 'ADVISORY')
            ->count();

        $constraintScore = $this->calculateConstraintScore(
            $criticalCount,
            $highCount,
            $moderateCount,
            $advisoryCount
        );

        $constraintLevel = $this->determineConstraintLevel(
            $constraintScore,
            $criticalCount,
            $highCount
        );

        $dominantConstraint = $this->determineDominantConstraint($detectedConstraints);

        $strategicFlexibility = $this->determineStrategicFlexibility(
            $capacityScore,
            $pressureScore,
            $closurePercentage,
            $constraintScore
        );

        $constraintReliefReadiness = $this->determineConstraintReliefReadiness(
            $constraintScore,
            $closurePercentage,
            $capacityDemandGap
        );

        $findings = [
            "Strategic constraint intelligence is based on strategic snapshot {$snapshot->id}.",
            count($detectedConstraints) . ' strategic governance constraint(s) are currently detected.',
            "{$criticalCount} critical, {$highCount} high, {$moderateCount} moderate, and {$advisoryCount} advisory strategic constraint(s) are currently detected.",
            "Current strategic constraint score is {$constraintScore}.",
            "Current strategic constraint level is {$constraintLevel}.",
            "Current strategic flexibility classification is {$strategicFlexibility}.",
            "Current constraint-relief readiness is {$constraintReliefReadiness}.",
            "Current governance capacity score is {$capacityScore}.",
            "Current governance pressure score is {$pressureScore}.",
            "Current capacity-demand gap is {$capacityDemandGap}.",
            "Governance action closure is {$closurePercentage}%.",
            "Governance decision completion is {$decisionCompletion}%.",
        ];

        if ($dominantConstraint) {
            $findings[] =
                "Current dominant strategic constraint is {$dominantConstraint['constraint_type']} with {$dominantConstraint['severity']} severity.";
        }

        if ($criticalCount === 0) {
            $findings[] =
                'No critical strategic governance constraint is currently detected.';
        }

        if ($evidenceWaiting > 0) {
            $findings[] =
                "{$evidenceWaiting} governance action(s) remain evidence-dependent.";
        }

        if ($pendingReviews > 0) {
            $findings[] =
                "{$pendingReviews} governance action(s) remain pending human review.";
        }

        if ($highPriorityActive > 0 || $criticalPriorityActive > 0) {
            $findings[] =
                ($highPriorityActive + $criticalPriorityActive) .
                ' high or critical priority governance action(s) remain active.';
        }

        $managementPriorities = [];

        if ($capacityScore < 50) {
            $managementPriorities[] =
                'Strengthen governance capacity before materially increasing strategic workload.';
        }

        if ($pressureScore >= 60) {
            $managementPriorities[] =
                'Reduce governance demand pressure through governed progression of existing work.';
        }

        if ($capacityDemandGap >= 20) {
            $managementPriorities[] =
                'Reduce the material gap between governance demand and available governance capacity.';
        }

        if ($evidenceWaiting > 0) {
            $managementPriorities[] =
                'Increase validated evidence availability for governance work blocked by evidence dependency.';
        }

        if ($pendingReviews > 0) {
            $managementPriorities[] =
                'Complete outstanding human governance reviews in descending priority order.';
        }

        if ($closurePercentage < 50) {
            $managementPriorities[] =
                'Improve formal governance action closure progression to release constrained strategic capacity.';
        }

        if ($deferredActions > 0) {
            $managementPriorities[] =
                'Maintain deferred governance work under controlled observation until reassessment conditions are satisfied.';
        }

        $managementPriorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation while strategic constraints are reduced.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_CONSTRAINT_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $snapshot->id,
            'operational_snapshot_id' => $snapshot->operational_snapshot_id,
            'lifecycle_snapshot_id' => $snapshot->lifecycle_snapshot_id,
            'snapshot_scope' => $snapshot->snapshot_scope,
            'resident_id' => $snapshot->resident_id,

            'constraint_state' => [
                'constraint_level' => $constraintLevel,
                'constraint_score' => $constraintScore,
                'detected_constraints' => count($detectedConstraints),
                'dominant_constraint' => $dominantConstraint,
                'strategic_flexibility' => $strategicFlexibility,
                'constraint_relief_readiness' => $constraintReliefReadiness,
                'human_management_attention_required' => count($detectedConstraints) > 0,
            ],

            'constraint_summary' => [
                'total_conditions_evaluated' => count($constraints),
                'critical_constraints' => $criticalCount,
                'high_constraints' => $highCount,
                'moderate_constraints' => $moderateCount,
                'advisory_constraints' => $advisoryCount,
            ],

            'constraint_signals' => $constraints,

            'detected_constraint_list' => $detectedConstraints,

            'strategic_context' => [
                'strategic_status' => $state['strategic_status'] ?? null,
                'strategic_health' => $state['strategic_health'] ?? null,
                'strategic_readiness' => $state['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $strategicReadinessScore,
                'strategic_balance_score' => $strategicBalanceScore,
                'capacity_status' => $capacityState['capacity_status'] ?? null,
                'capacity_score' => $capacityScore,
                'capacity_adequacy' => $capacityState['capacity_adequacy'] ?? null,
                'demand_intensity' => $capacityState['demand_intensity'] ?? null,
                'demand_pressure_score' => $pressureScore,
                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $capacityState['capacity_demand_balance'] ?? null,
                'strategic_load_status' => $capacityState['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $capacityState['workload_expansion_readiness'] ?? null,
            ],

            'governance_work_context' => [
                'total_actions' => $totalActions,
                'active_actions' => $activeActions,
                'pending_human_reviews' => $pendingReviews,
                'evidence_waiting_actions' => $evidenceWaiting,
                'deferred_actions' => $deferredActions,
                'high_priority_active_actions' => $highPriorityActive,
                'critical_priority_active_actions' => $criticalPriorityActive,
                'action_closure_percentage' => $closurePercentage,
                'decision_completion_percentage' => $decisionCompletion,
            ],

            'constraint_findings' => $findings,

            'management_priorities' => $managementPriorities,

            'constraint_guardrails' => [
                'strategic_constraint_intelligence_enabled' => true,
                'constraint_detection_is_governance_decision' => false,
                'constraint_detection_is_action_resolution' => false,
                'constraint_score_changes_priority' => false,
                'constraint_score_changes_eligibility' => false,
                'constraint_relief_authorizes_execution' => false,
                'strategic_flexibility_expands_ai_authority' => false,
                'constraint_pressure_overrides_human_review' => false,
                'constraint_pressure_overrides_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' =>
                    'Governance strategic constraint intelligence identifies capacity, pressure, evidence, review, closure, priority, readiness, and balance constraints for human strategic oversight only. Constraint intelligence does not make governance decisions, resolve actions, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function calculateConstraintScore(
        int $critical,
        int $high,
        int $moderate,
        int $advisory
    ): int {
        $score =
            ($critical * 25) +
            ($high * 15) +
            ($moderate * 8) +
            ($advisory * 3);

        return min(100, $score);
    }

    private function determineConstraintLevel(
    int $score,
    int $critical,
    int $high
    ): string {
        if ($critical > 0) {
            return 'CRITICAL_STRATEGIC_CONSTRAINT';
        }

        if ($high >= 3 || $score >= 60) {
            return 'SEVERE_STRATEGIC_CONSTRAINT';
        }

        if ($high > 0 || $score >= 40) {
            return 'MATERIAL_STRATEGIC_CONSTRAINT';
        }

        if ($score > 0) {
            return 'LIMITED_STRATEGIC_CONSTRAINT';
        }

        return 'NO_MATERIAL_STRATEGIC_CONSTRAINT';
    }

    private function determineDominantConstraint(array $constraints): ?array
    {
        if (empty($constraints)) {
            return null;
        }

        $weights = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
            'NONE' => 0,
        ];

        usort($constraints, function (array $a, array $b) use ($weights) {
            $severityComparison =
                ($weights[$b['severity']] ?? 0) <=> ($weights[$a['severity']] ?? 0);

            if ($severityComparison !== 0) {
                return $severityComparison;
            }

            $aValue = is_numeric($a['value'] ?? null) ? (float) $a['value'] : 0;
            $bValue = is_numeric($b['value'] ?? null) ? (float) $b['value'] : 0;

            return $bValue <=> $aValue;
        });

        return [
            'constraint_type' => $constraints[0]['constraint_type'],
            'severity' => $constraints[0]['severity'],
            'value' => $constraints[0]['value'],
            'message' => $constraints[0]['message'],
        ];
    }

    private function determineStrategicFlexibility(
        float $capacityScore,
        float $pressureScore,
        float $closurePercentage,
        int $constraintScore
    ): string {
        if (
            $capacityScore < 30 ||
            $pressureScore >= 80 ||
            $constraintScore >= 80
        ) {
            return 'VERY_LOW_FLEXIBILITY';
        }

        if (
            $capacityScore < 50 ||
            $pressureScore >= 60 ||
            $closurePercentage < 50 ||
            $constraintScore >= 50
        ) {
            return 'LIMITED_FLEXIBILITY';
        }

        if (
            $capacityScore < 70 ||
            $pressureScore >= 40
        ) {
            return 'MODERATE_FLEXIBILITY';
        }

        return 'STRONG_FLEXIBILITY';
    }

    private function determineConstraintReliefReadiness(
        int $constraintScore,
        float $closurePercentage,
        float $capacityDemandGap
    ): string {
        if (
            $constraintScore < 25 &&
            $closurePercentage >= 70 &&
            $capacityDemandGap <= 5
        ) {
            return 'READY_FOR_CONSTRAINT_RELIEF';
        }

        if (
            $constraintScore < 50 &&
            $closurePercentage >= 50 &&
            $capacityDemandGap < 15
        ) {
            return 'PARTIALLY_READY_FOR_CONSTRAINT_RELIEF';
        }

        return 'CONSTRAINT_RELIEF_WORK_REQUIRED';
    }
}