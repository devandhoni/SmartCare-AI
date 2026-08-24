<?php

namespace App\Services;

class AIGovernanceStrategicAuditSummaryEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine,
        protected AIGovernanceStrategicConstraintIntelligenceEngine $constraintEngine,
        protected AIGovernanceStrategicRiskIntelligenceEngine $riskEngine,
        protected AIGovernanceStrategicRecommendationIntelligenceEngine $recommendationEngine,
        protected AIGovernanceExecutiveStrategicIntelligenceEngine $executiveEngine,
    ) {
    }

    public function analyze(?int $strategicSnapshotId = null): array
    {
        $strategicState = $this->strategicStateEngine->analyze($strategicSnapshotId);
        $capacityDemand = $this->capacityDemandEngine->analyze($strategicSnapshotId);
        $constraints = $this->constraintEngine->analyze($strategicSnapshotId);
        $risk = $this->riskEngine->analyze($strategicSnapshotId);
        $recommendations = $this->recommendationEngine->analyze($strategicSnapshotId);
        $executive = $this->executiveEngine->analyze($strategicSnapshotId);

        $state = $strategicState['strategic_state'] ?? [];
        $capacity = $capacityDemand['capacity_demand_state'] ?? [];
        $constraintState = $constraints['constraint_state'] ?? [];
        $constraintSummary = $constraints['constraint_summary'] ?? [];
        $riskState = $risk['strategic_risk_state'] ?? [];
        $riskSummary = $risk['risk_summary'] ?? [];
        $recommendationState = $recommendations['recommendation_state'] ?? [];
        $recommendationSummary = $recommendations['recommendation_summary'] ?? [];
        $executiveState = $executive['executive_strategic_state'] ?? [];
        $progressContext = $executive['progress_context'] ?? [];

        $checks = [
            'strategic_state_intelligence_available' => [
                'passed' => ($strategicState['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic state intelligence is available.',
            ],

            'capacity_demand_intelligence_available' => [
                'passed' => ($capacityDemand['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic capacity and demand intelligence is available.',
            ],

            'strategic_constraint_intelligence_available' => [
                'passed' => ($constraints['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic constraint intelligence is available.',
            ],

            'strategic_risk_intelligence_available' => [
                'passed' => ($risk['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic risk intelligence is available.',
            ],

            'strategic_recommendation_intelligence_available' => [
                'passed' => ($recommendations['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic recommendation intelligence is available.',
            ],

            'executive_strategic_intelligence_available' => [
                'passed' => ($executive['analysis_completed'] ?? false) === true,
                'message' => 'Executive strategic governance intelligence is available.',
            ],

            'governance_integrity_intact' => [
                'passed' =>
                    ($riskState['governance_integrity_intact'] ?? false) === true &&
                    ($recommendationState['governance_integrity_intact'] ?? false) === true &&
                    ($executiveState['governance_integrity_intact'] ?? false) === true,
                'message' => 'Strategic governance integrity remains intact.',
            ],

            'no_critical_strategic_risk_signal' => [
                'passed' => (int) ($riskSummary['critical_signals'] ?? 0) === 0,
                'value' => (int) ($riskSummary['critical_signals'] ?? 0),
                'message' => 'No critical strategic governance risk signal should be present.',
            ],

            'no_critical_strategic_constraint' => [
                'passed' => (int) ($constraintSummary['critical_constraints'] ?? 0) === 0,
                'value' => (int) ($constraintSummary['critical_constraints'] ?? 0),
                'message' => 'No critical strategic governance constraint should be present.',
            ],

            'no_immediate_strategic_escalation_requirement' => [
                'passed' => ($executiveState['immediate_escalation_required'] ?? true) === false,
                'value' => $executiveState['immediate_escalation_required'] ?? null,
                'message' => 'No immediate strategic governance escalation requirement should be active.',
            ],

            'automatic_execution_isolation' => [
                'passed' =>
                    ($executive['executive_guardrails']['automatic_execution_allowed'] ?? true) === false &&
                    ($executive['executive_guardrails']['automatic_change_allowed'] ?? true) === false &&
                    ($executive['executive_guardrails']['automatic_deployment_allowed'] ?? true) === false &&
                    ($executive['executive_guardrails']['automatic_rollback_allowed'] ?? true) === false &&
                    ($executive['executive_guardrails']['automatic_clinical_action_allowed'] ?? true) === false,
                'message' => 'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    ($executive['executive_guardrails']['human_review_required'] ?? false) === true &&
                    ($executive['executive_guardrails']['governance_validation_required'] ?? false) === true,
                'message' => 'Human review and governance validation remain mandatory.',
            ],

            'strategic_recommendation_authority_isolation' => [
                'passed' =>
                    ($recommendations['recommendation_guardrails']['recommendation_is_governance_decision'] ?? true) === false &&
                    ($recommendations['recommendation_guardrails']['recommendation_changes_action_state'] ?? true) === false &&
                    ($recommendations['recommendation_guardrails']['recommendation_authorizes_execution'] ?? true) === false,
                'message' => 'Strategic recommendation intelligence remains advisory and does not become governance decision or execution authority.',
            ],

            'executive_authority_isolation' => [
                'passed' =>
                    ($executive['executive_guardrails']['executive_strategic_status_is_governance_decision'] ?? true) === false &&
                    ($executive['executive_guardrails']['executive_readiness_is_execution_authorization'] ?? true) === false &&
                    ($executive['executive_guardrails']['executive_intelligence_changes_action_state'] ?? true) === false,
                'message' => 'Executive strategic intelligence remains informational and does not become governance decision, resolution, or execution authority.',
            ],
        ];

        $totalChecks = count($checks);
        $passedChecks = collect($checks)->where('passed', true)->count();
        $failedChecks = $totalChecks - $passedChecks;

        $auditStatus = $failedChecks === 0
            ? 'COMPLETE'
            : 'INTEGRITY_FAILURE';

        $managementStatus = $this->determineManagementStatus(
            (float) ($riskState['strategic_risk_score'] ?? 0),
            (float) ($constraintState['constraint_score'] ?? 0),
            (bool) ($executiveState['management_escalation_recommended'] ?? false)
        );

        return [
            'audit_available' => true,
            'audit_status' => $auditStatus,

            'strategic_snapshot_id' => $strategicState['strategic_snapshot_id'] ?? null,
            'operational_snapshot_id' => $strategicState['operational_snapshot_id'] ?? null,
            'lifecycle_snapshot_id' => $strategicState['lifecycle_snapshot_id'] ?? null,
            'snapshot_scope' => $strategicState['snapshot_scope'] ?? null,
            'resident_id' => $strategicState['resident_id'] ?? null,

            'management_status' => $managementStatus,

            'audit_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
            ],

            'checks' => $checks,

            'strategic_summary' => [
                'strategic_status' => $state['strategic_status'] ?? null,
                'strategic_health' => $state['strategic_health'] ?? null,
                'strategic_readiness' => $state['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $state['strategic_readiness_score'] ?? null,
                'strategic_balance_score' => $state['strategic_balance_score'] ?? null,
                'strategic_maturity' => $state['strategic_maturity'] ?? null,
                'strategic_confidence' => $state['strategic_confidence'] ?? null,
            ],

            'capacity_demand_summary' => [
                'capacity_status' => $capacity['capacity_status'] ?? null,
                'capacity_score' => $capacity['capacity_score'] ?? null,
                'demand_pressure_status' => $capacity['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $capacity['demand_pressure_score'] ?? null,
                'capacity_demand_gap' => $capacity['capacity_demand_gap'] ?? null,
                'capacity_demand_balance' => $capacity['capacity_demand_balance'] ?? null,
                'strategic_load_status' => $capacity['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $capacity['workload_expansion_readiness'] ?? null,
            ],

            'constraint_summary' => [
                'constraint_level' => $constraintState['constraint_level'] ?? null,
                'constraint_score' => $constraintState['constraint_score'] ?? null,
                'detected_constraints' => $constraintState['detected_constraints'] ?? null,
                'critical_constraints' => $constraintSummary['critical_constraints'] ?? 0,
                'high_constraints' => $constraintSummary['high_constraints'] ?? 0,
                'moderate_constraints' => $constraintSummary['moderate_constraints'] ?? 0,
                'advisory_constraints' => $constraintSummary['advisory_constraints'] ?? 0,
                'dominant_constraint' => $constraintState['dominant_constraint'] ?? null,
                'strategic_flexibility' => $constraintState['strategic_flexibility'] ?? null,
            ],

            'risk_summary' => [
                'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                'strategic_risk_score' => $riskState['strategic_risk_score'] ?? null,
                'risk_control_status' => $riskState['risk_control_status'] ?? null,
                'critical_signals' => $riskSummary['critical_signals'] ?? 0,
                'high_signals' => $riskSummary['high_signals'] ?? 0,
                'moderate_signals' => $riskSummary['moderate_signals'] ?? 0,
                'advisory_signals' => $riskSummary['advisory_signals'] ?? 0,
            ],

            'recommendation_summary' => [
                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => $recommendationSummary['total_recommendations'] ?? 0,
                'critical_recommendations' => $recommendationSummary['critical_recommendations'] ?? 0,
                'high_recommendations' => $recommendationSummary['high_recommendations'] ?? 0,
                'moderate_recommendations' => $recommendationSummary['moderate_recommendations'] ?? 0,
                'advisory_recommendations' => $recommendationSummary['advisory_recommendations'] ?? 0,
                'top_recommendation_code' => $recommendationSummary['top_recommendation_code'] ?? null,
            ],

            'executive_summary' => [
                'executive_strategic_status' => $executiveState['executive_strategic_status'] ?? null,
                'executive_readiness' => $executiveState['executive_readiness'] ?? null,
                'executive_confidence' => $executiveState['executive_confidence'] ?? null,
                'executive_strategic_score' => $executiveState['executive_strategic_score'] ?? null,
                'management_escalation_recommended' => $executiveState['management_escalation_recommended'] ?? null,
                'immediate_escalation_required' => $executiveState['immediate_escalation_required'] ?? null,
            ],

            'progress_summary' => [
                'action_closure_percentage' => $progressContext['action_closure_percentage'] ?? null,
                'decision_completion_percentage' => $progressContext['decision_completion_percentage'] ?? null,
            ],

            'integrity_summary' => [
                'governance_integrity_intact' =>
                    ($riskState['governance_integrity_intact'] ?? false) &&
                    ($recommendationState['governance_integrity_intact'] ?? false) &&
                    ($executiveState['governance_integrity_intact'] ?? false),

                'automatic_execution_allowed' =>
                    $executive['executive_guardrails']['automatic_execution_allowed'] ?? null,

                'automatic_change_allowed' =>
                    $executive['executive_guardrails']['automatic_change_allowed'] ?? null,

                'automatic_deployment_allowed' =>
                    $executive['executive_guardrails']['automatic_deployment_allowed'] ?? null,

                'automatic_rollback_allowed' =>
                    $executive['executive_guardrails']['automatic_rollback_allowed'] ?? null,

                'automatic_clinical_action_allowed' =>
                    $executive['executive_guardrails']['automatic_clinical_action_allowed'] ?? null,

                'human_review_required' =>
                    $executive['executive_guardrails']['human_review_required'] ?? null,

                'governance_validation_required' =>
                    $executive['executive_guardrails']['governance_validation_required'] ?? null,
            ],

            'audit_findings' => [
                "Strategic governance audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
                'Current strategic governance status is ' . ($state['strategic_status'] ?? 'UNKNOWN') . '.',
                'Current strategic governance health is ' . ($state['strategic_health'] ?? 'UNKNOWN') . '.',
                'Current strategic readiness is ' . ($state['strategic_readiness'] ?? 'UNKNOWN') .
                    ' with score ' . ($state['strategic_readiness_score'] ?? 0) . '.',
                'Current governance capacity is ' . ($capacity['capacity_status'] ?? 'UNKNOWN') .
                    ' with score ' . ($capacity['capacity_score'] ?? 0) . '.',
                'Current governance demand pressure is ' . ($capacity['demand_pressure_status'] ?? 'UNKNOWN') .
                    ' with score ' . ($capacity['demand_pressure_score'] ?? 0) . '.',
                'Current strategic constraint level is ' . ($constraintState['constraint_level'] ?? 'UNKNOWN') .
                    ' with score ' . ($constraintState['constraint_score'] ?? 0) . '.',
                'Current strategic risk level is ' . ($riskState['strategic_risk_level'] ?? 'UNKNOWN') .
                    ' with score ' . ($riskState['strategic_risk_score'] ?? 0) . '.',
                ($recommendationSummary['total_recommendations'] ?? 0) .
                    ' strategic governance recommendation(s) are currently generated.',
                'Current executive strategic status is ' .
                    ($executiveState['executive_strategic_status'] ?? 'UNKNOWN') . '.',
                'Current executive strategic readiness is ' .
                    ($executiveState['executive_readiness'] ?? 'UNKNOWN') . '.',
                'Current executive strategic score is ' .
                    ($executiveState['executive_strategic_score'] ?? 0) . '.',
                'Governance action closure is ' .
                    ($progressContext['action_closure_percentage'] ?? 0) . '%.',
                'Governance decision completion is ' .
                    ($progressContext['decision_completion_percentage'] ?? 0) . '%.',
                'Critical strategic risk signal count is ' .
                    ($riskSummary['critical_signals'] ?? 0) . '.',
                'Critical strategic constraint count is ' .
                    ($constraintSummary['critical_constraints'] ?? 0) . '.',
                'Strategic governance integrity remains ' .
                    (
                        ($riskState['governance_integrity_intact'] ?? false) &&
                        ($recommendationState['governance_integrity_intact'] ?? false) &&
                        ($executiveState['governance_integrity_intact'] ?? false)
                            ? 'INTACT'
                            : 'NOT_INTACT'
                    ) . '.',
                'Step 62 strategic governance integrity controls currently contain ' .
                    ($failedChecks === 0 ? 'no failures.' : "{$failedChecks} failure(s)."),
            ],

            'management_priorities' =>
                $executive['executive_priorities'] ??
                $recommendations['recommendations'] ??
                [],

            'audit_guardrails' => [
                'strategic_governance_audit_enabled' => true,
                'audit_is_governance_decision' => false,
                'audit_is_governance_approval' => false,
                'audit_is_governance_rejection' => false,
                'audit_is_action_resolution' => false,
                'audit_is_execution_authorization' => false,
                'audit_is_deployment_authorization' => false,
                'audit_is_rollback_authorization' => false,
                'audit_changes_action_state' => false,
                'audit_changes_priority' => false,
                'audit_changes_eligibility' => false,
                'audit_overrides_human_review' => false,
                'audit_overrides_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' => 'Strategic governance audit consolidates Step 62 strategic readiness, capacity, demand pressure, constraints, risk, recommendations, executive oversight, progression, and governance-control integrity for human audit and strategic management review only. It does not make governance decisions, resolve actions, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function determineManagementStatus(
        float $riskScore,
        float $constraintScore,
        bool $managementEscalationRecommended
    ): string {
        if ($riskScore >= 75 || $constraintScore >= 90) {
            return 'ELEVATED_STRATEGIC_GOVERNANCE_WORK_REMAINS';
        }

        if ($managementEscalationRecommended) {
            return 'STRATEGIC_MANAGEMENT_ATTENTION_REQUIRED';
        }

        return 'CONTROLLED_STRATEGIC_GOVERNANCE';
    }
}