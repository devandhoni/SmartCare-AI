<?php

namespace App\Services;

use App\Models\AIGovernanceAction;

class AIGovernanceStrategicRiskIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine,
        protected AIGovernanceStrategicConstraintIntelligenceEngine $constraintEngine,
    ) {
    }

    public function analyze(?int $strategicSnapshotId = null): array
    {
        $strategicState = $this->strategicStateEngine->analyze($strategicSnapshotId);
        $capacityDemand = $this->capacityDemandEngine->analyze($strategicSnapshotId);
        $constraints = $this->constraintEngine->analyze($strategicSnapshotId);

        if (
            !($strategicState['analysis_completed'] ?? false) ||
            !($capacityDemand['analysis_completed'] ?? false) ||
            !($constraints['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_RISK_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Required strategic governance intelligence is not currently available.',
            ];
        }

        $strategicStateData = $strategicState['strategic_state'] ?? [];
        $capacityDemandState = $capacityDemand['capacity_demand_state'] ?? [];
        $constraintState = $constraints['constraint_state'] ?? [];
        $constraintSummary = $constraints['constraint_summary'] ?? [];
        $workContext = $constraints['governance_work_context'] ?? [];

        $readinessScore = (float) ($strategicStateData['strategic_readiness_score'] ?? 0);
        $capacityScore = (float) ($capacityDemandState['capacity_score'] ?? 0);
        $pressureScore = (float) ($capacityDemandState['demand_pressure_score'] ?? 0);
        $capacityDemandGap = (float) ($capacityDemandState['capacity_demand_gap'] ?? 0);
        $constraintScore = (float) ($constraintState['constraint_score'] ?? 0);
        $strategicBalanceScore = (float) ($strategicStateData['strategic_balance_score'] ?? 0);

        $criticalConstraints = (int) ($constraintSummary['critical_constraints'] ?? 0);
        $highConstraints = (int) ($constraintSummary['high_constraints'] ?? 0);
        $moderateConstraints = (int) ($constraintSummary['moderate_constraints'] ?? 0);

        $criticalPriorityActions = (int) ($workContext['critical_priority_active_actions'] ?? 0);
        $highPriorityActions = (int) ($workContext['high_priority_active_actions'] ?? 0);
        $pendingHumanReviews = (int) ($workContext['pending_human_reviews'] ?? 0);
        $evidenceWaitingActions = (int) ($workContext['evidence_waiting_actions'] ?? 0);
        $deferredActions = (int) ($workContext['deferred_actions'] ?? 0);
        $closurePercentage = (float) ($workContext['action_closure_percentage'] ?? 0);
        $decisionCompletionPercentage = (float) ($workContext['decision_completion_percentage'] ?? 0);

        $riskSignals = [
            'critical_strategic_constraints' => [
                'detected' => $criticalConstraints > 0,
                'severity' => 'CRITICAL',
                'value' => $criticalConstraints,
                'message' => 'Critical strategic governance constraints require immediate human management attention.',
            ],

            'severe_strategic_constraint_pressure' => [
                'detected' => in_array(
                    $constraintState['constraint_level'] ?? null,
                    ['SEVERE_STRATEGIC_CONSTRAINT', 'CRITICAL_STRATEGIC_CONSTRAINT'],
                    true
                ),
                'severity' => 'HIGH',
                'value' => [
                    'constraint_level' => $constraintState['constraint_level'] ?? null,
                    'constraint_score' => $constraintScore,
                ],
                'message' => 'Strategic governance constraint pressure remains materially elevated.',
            ],

            'high_governance_demand_pressure' => [
                'detected' => $pressureScore >= 60,
                'severity' => 'HIGH',
                'value' => $pressureScore,
                'message' => 'Governance demand pressure remains high relative to preferred strategic operating conditions.',
            ],

            'constrained_governance_capacity' => [
                'detected' => $capacityScore < 50,
                'severity' => 'HIGH',
                'value' => $capacityScore,
                'message' => 'Available governance capacity remains below the preferred strategic threshold.',
            ],

            'material_capacity_demand_gap' => [
                'detected' => $capacityDemandGap >= 20,
                'severity' => 'HIGH',
                'value' => $capacityDemandGap,
                'message' => 'Governance demand materially exceeds available governance capacity.',
            ],

            'limited_strategic_readiness' => [
                'detected' => $readinessScore < 50,
                'severity' => 'MODERATE',
                'value' => $readinessScore,
                'message' => 'Strategic readiness remains below the preferred readiness threshold.',
            ],

            'strategic_imbalance' => [
                'detected' => $strategicBalanceScore < 50,
                'severity' => 'MODERATE',
                'value' => $strategicBalanceScore,
                'message' => 'Strategic governance balance remains constrained by current workload, capacity, and pressure.',
            ],

            'high_priority_active_work' => [
                'detected' => ($criticalPriorityActions + $highPriorityActions) > 0,
                'severity' => $criticalPriorityActions > 0 ? 'CRITICAL' : 'HIGH',
                'value' => [
                    'critical_priority_active_actions' => $criticalPriorityActions,
                    'high_priority_active_actions' => $highPriorityActions,
                ],
                'message' => 'High or critical priority governance work remains active.',
            ],

            'pending_human_review_pressure' => [
                'detected' => $pendingHumanReviews > 0,
                'severity' => 'MODERATE',
                'value' => $pendingHumanReviews,
                'message' => 'Outstanding human governance reviews continue to contribute to strategic pressure.',
            ],

            'evidence_dependency' => [
                'detected' => $evidenceWaitingActions > 0,
                'severity' => 'MODERATE',
                'value' => $evidenceWaitingActions,
                'message' => 'Governance work remains dependent on additional validated evidence.',
            ],

            'deferred_governance_work' => [
                'detected' => $deferredActions > 0,
                'severity' => 'ADVISORY',
                'value' => $deferredActions,
                'message' => 'Deferred governance work remains under controlled observation.',
            ],

            'low_action_closure' => [
                'detected' => $closurePercentage < 50,
                'severity' => 'MODERATE',
                'value' => $closurePercentage,
                'threshold' => 50.0,
                'message' => 'Formal governance action closure remains below the preferred strategic progression threshold.',
            ],
        ];

        $detectedSignals = collect($riskSignals)
            ->filter(fn (array $signal) => $signal['detected'])
            ->values();

        $criticalSignals = $detectedSignals
            ->where('severity', 'CRITICAL')
            ->count();

        $highSignals = $detectedSignals
            ->where('severity', 'HIGH')
            ->count();

        $moderateSignals = $detectedSignals
            ->where('severity', 'MODERATE')
            ->count();

        $advisorySignals = $detectedSignals
            ->where('severity', 'ADVISORY')
            ->count();

        $riskScore = $this->calculateRiskScore(
            $readinessScore,
            $capacityScore,
            $pressureScore,
            $capacityDemandGap,
            $constraintScore,
            $strategicBalanceScore,
            $criticalSignals,
            $highSignals,
            $moderateSignals,
        );

        $riskLevel = $this->determineRiskLevel(
            $riskScore,
            $criticalSignals,
            $highSignals,
        );

        $riskControlStatus = $this->determineRiskControlStatus(
            $riskLevel,
            $criticalSignals
        );

        $highestRiskAction = $this->getHighestRiskActiveAction();

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_RISK_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $strategicState['strategic_snapshot_id'] ?? null,
            'operational_snapshot_id' => $strategicState['operational_snapshot_id'] ?? null,
            'lifecycle_snapshot_id' => $strategicState['lifecycle_snapshot_id'] ?? null,
            'snapshot_scope' => $strategicState['snapshot_scope'] ?? null,
            'resident_id' => $strategicState['resident_id'] ?? null,

            'strategic_risk_state' => [
                'strategic_risk_level' => $riskLevel,
                'strategic_risk_score' => $riskScore,
                'risk_control_status' => $riskControlStatus,
                'human_management_attention_required' => $riskScore >= 50,
                'immediate_human_intervention_required' => $criticalSignals > 0,
                'governance_integrity_intact' => true,
            ],

            'risk_summary' => [
                'detected_signal_count' => $detectedSignals->count(),
                'critical_signals' => $criticalSignals,
                'high_signals' => $highSignals,
                'moderate_signals' => $moderateSignals,
                'advisory_signals' => $advisorySignals,
                'critical_constraints' => $criticalConstraints,
                'high_constraints' => $highConstraints,
                'moderate_constraints' => $moderateConstraints,
                'high_priority_active_actions' => $highPriorityActions,
                'critical_priority_active_actions' => $criticalPriorityActions,
                'pending_human_reviews' => $pendingHumanReviews,
                'evidence_dependent_actions' => $evidenceWaitingActions,
                'deferred_actions' => $deferredActions,
            ],

            'risk_signals' => $riskSignals,

            'strategic_context' => [
                'strategic_status' => $strategicStateData['strategic_status'] ?? null,
                'strategic_health' => $strategicStateData['strategic_health'] ?? null,
                'strategic_readiness' => $strategicStateData['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $readinessScore,
                'strategic_balance_score' => $strategicBalanceScore,

                'capacity_status' => $capacityDemandState['capacity_status'] ?? null,
                'capacity_score' => $capacityScore,
                'capacity_adequacy' => $capacityDemandState['capacity_adequacy'] ?? null,

                'demand_pressure_status' => $capacityDemandState['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $pressureScore,
                'demand_intensity' => $capacityDemandState['demand_intensity'] ?? null,

                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $capacityDemandState['capacity_demand_balance'] ?? null,
                'strategic_load_status' => $capacityDemandState['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $capacityDemandState['workload_expansion_readiness'] ?? null,

                'constraint_level' => $constraintState['constraint_level'] ?? null,
                'constraint_score' => $constraintScore,
                'strategic_flexibility' => $constraintState['strategic_flexibility'] ?? null,
                'constraint_relief_readiness' => $constraintState['constraint_relief_readiness'] ?? null,
            ],

            'progress_context' => [
                'action_closure_percentage' => $closurePercentage,
                'decision_completion_percentage' => $decisionCompletionPercentage,
            ],

            'highest_risk_active_action' => $highestRiskAction,

            'management_priorities' => $this->buildManagementPriorities(
                $pressureScore,
                $capacityScore,
                $capacityDemandGap,
                $pendingHumanReviews,
                $evidenceWaitingActions,
                $deferredActions,
                $closurePercentage,
                $highPriorityActions,
                $criticalPriorityActions,
            ),

            'risk_findings' => [
                "{$detectedSignals->count()} strategic governance risk or advisory signal(s) are currently detected.",
                "{$criticalSignals} critical, {$highSignals} high, {$moderateSignals} moderate, and {$advisorySignals} advisory strategic risk signal(s) are currently detected.",
                "Current strategic governance risk score is {$riskScore}.",
                "Current strategic governance risk level is {$riskLevel}.",
                "Current strategic risk control status is {$riskControlStatus}.",
                "Strategic readiness score is {$readinessScore}.",
                "Governance capacity score is {$capacityScore}.",
                "Governance demand pressure score is {$pressureScore}.",
                "Current capacity-demand gap is {$capacityDemandGap}.",
                "Current strategic constraint level is " . ($constraintState['constraint_level'] ?? 'UNKNOWN') . " with score {$constraintScore}.",
                "Governance action closure is {$closurePercentage}%.",
                "Governance decision completion is {$decisionCompletionPercentage}%.",
                "{$highPriorityActions} high-priority governance action(s) remain active.",
                "{$criticalPriorityActions} critical-priority governance action(s) remain active.",
                "{$pendingHumanReviews} governance action(s) remain pending human review.",
                "{$evidenceWaitingActions} governance action(s) remain evidence-dependent.",
                "{$deferredActions} governance action(s) remain deferred.",
                $criticalSignals === 0
                    ? 'No critical strategic governance risk signal is currently detected.'
                    : 'One or more critical strategic governance risk signals require immediate human management attention.',
                'Strategic governance risk intelligence remains advisory and does not alter governance action state or authority.',
            ],

            'risk_guardrails' => [
                'strategic_risk_intelligence_enabled' => true,
                'strategic_risk_is_governance_decision' => false,
                'strategic_risk_is_governance_approval' => false,
                'strategic_risk_changes_action_state' => false,
                'strategic_risk_changes_priority' => false,
                'strategic_risk_changes_eligibility' => false,
                'strategic_risk_authorizes_execution' => false,
                'strategic_risk_authorizes_deployment' => false,
                'strategic_risk_authorizes_rollback' => false,
                'strategic_risk_authorizes_clinical_action' => false,
                'risk_score_is_staff_performance_rating' => false,
                'capacity_pressure_overrides_human_review' => false,
                'constraint_pressure_overrides_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' => 'Governance strategic risk intelligence consolidates strategic readiness, capacity, demand pressure, constraints, workload progression, evidence dependencies, and active governance priorities for human strategic oversight only. Strategic risk intelligence does not make governance decisions, resolve actions, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function calculateRiskScore(
        float $readinessScore,
        float $capacityScore,
        float $pressureScore,
        float $capacityDemandGap,
        float $constraintScore,
        float $strategicBalanceScore,
        int $criticalSignals,
        int $highSignals,
        int $moderateSignals,
    ): float {
        $readinessRisk = max(0, 100 - $readinessScore);
        $capacityRisk = max(0, 100 - $capacityScore);
        $balanceRisk = max(0, 100 - $strategicBalanceScore);
        $gapRisk = min(100, $capacityDemandGap * 2.5);

        $baseRisk =
            ($constraintScore * 0.25) +
            ($pressureScore * 0.20) +
            ($capacityRisk * 0.15) +
            ($readinessRisk * 0.15) +
            ($balanceRisk * 0.10) +
            ($gapRisk * 0.15);

        $severityAdjustment =
            ($criticalSignals * 10) +
            ($highSignals * 2) +
            ($moderateSignals * 0.5);

        return round(
            min(100, max(0, $baseRisk + $severityAdjustment)),
            2
        );
    }

    private function determineRiskLevel(
        float $riskScore,
        int $criticalSignals,
        int $highSignals,
    ): string {
        if ($criticalSignals > 0) {
            return 'CRITICAL_STRATEGIC_RISK';
        }

        if ($riskScore >= 75) {
            return 'HIGH_STRATEGIC_RISK';
        }

        if ($riskScore >= 50 || $highSignals >= 3) {
            return 'MODERATE_STRATEGIC_RISK';
        }

        if ($riskScore >= 25) {
            return 'LOW_WITH_STRATEGIC_ATTENTION';
        }

        return 'LOW_STRATEGIC_RISK';
    }

    private function determineRiskControlStatus(
        string $riskLevel,
        int $criticalSignals,
    ): string {
        if ($criticalSignals > 0) {
            return 'IMMEDIATE_HUMAN_GOVERNANCE_ATTENTION';
        }

        return match ($riskLevel) {
            'HIGH_STRATEGIC_RISK' => 'CONTROLLED_WITH_ELEVATED_STRATEGIC_ATTENTION',
            'MODERATE_STRATEGIC_RISK' => 'CONTROLLED_WITH_STRATEGIC_ATTENTION',
            'LOW_WITH_STRATEGIC_ATTENTION' => 'CONTROLLED_WITH_ADVISORIES',
            default => 'CONTROLLED',
        };
    }

    private function getHighestRiskActiveAction(): ?array
    {
        $action = AIGovernanceAction::query()
            ->whereNotIn('action_status', [
                'RESOLVED',
                'CLOSED_REJECTED',
                'CLOSED',
            ])
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->first();

        if (!$action) {
            return null;
        }

        return [
            'action_id' => $action->id,
            'action_code' => $action->action_code,
            'action_category' => $action->action_category,
            'action_status' => $action->action_status,
            'eligibility_status' => $action->eligibility_status,
            'priority_level' => $action->priority_level,
            'priority_score' => (int) $action->priority_score,
            'review_decision' => $action->review_decision,

            'automatic_execution_allowed' => (bool) $action->automatic_execution_allowed,
            'automatic_change_allowed' => (bool) $action->automatic_change_allowed,
            'automatic_deployment_allowed' => (bool) $action->automatic_deployment_allowed,
            'automatic_rollback_allowed' => (bool) $action->automatic_rollback_allowed,
            'automatic_clinical_action_allowed' => (bool) $action->automatic_clinical_action_allowed,
        ];
    }

    private function buildManagementPriorities(
        float $pressureScore,
        float $capacityScore,
        float $capacityDemandGap,
        int $pendingHumanReviews,
        int $evidenceWaitingActions,
        int $deferredActions,
        float $closurePercentage,
        int $highPriorityActions,
        int $criticalPriorityActions,
    ): array {
        $priorities = [];

        if (($criticalPriorityActions + $highPriorityActions) > 0) {
            $priorities[] = 'Prioritize unresolved high or critical priority governance work for human strategic attention.';
        }

        if ($pressureScore >= 60) {
            $priorities[] = 'Reduce governance demand pressure through governed progression of existing work before materially expanding workload.';
        }

        if ($capacityScore < 50) {
            $priorities[] = 'Strengthen governance capacity before increasing strategic governance demand.';
        }

        if ($capacityDemandGap >= 20) {
            $priorities[] = 'Reduce the material imbalance between available governance capacity and current governance demand.';
        }

        if ($evidenceWaitingActions > 0) {
            $priorities[] = 'Increase validated evidence availability for evidence-dependent governance work.';
        }

        if ($pendingHumanReviews > 0) {
            $priorities[] = 'Complete outstanding human governance reviews in descending priority order.';
        }

        if ($closurePercentage < 50) {
            $priorities[] = 'Improve formal governance action closure progression while preserving evidence, review, safety, and authority controls.';
        }

        if ($deferredActions > 0) {
            $priorities[] = 'Maintain deferred governance actions under controlled observation until reassessment criteria are satisfied.';
        }

        $priorities[] = 'Preserve human review, governance validation, evidence quality, safety controls, and authority separation while reducing strategic risk.';

        return $priorities;
    }
}