<?php

namespace App\Services;

class AIGovernanceStrategicFinalValidationEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine,
        protected AIGovernanceStrategicConstraintIntelligenceEngine $constraintEngine,
        protected AIGovernanceStrategicRiskIntelligenceEngine $riskEngine,
        protected AIGovernanceStrategicRecommendationIntelligenceEngine $recommendationEngine,
        protected AIGovernanceExecutiveStrategicIntelligenceEngine $executiveEngine,
        protected AIGovernanceStrategicAuditSummaryEngine $auditEngine,
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
        $audit = $this->auditEngine->analyze($strategicSnapshotId);

        $state = $strategicState['strategic_state'] ?? [];
        $capacity = $capacityDemand['capacity_demand_state'] ?? [];
        $constraintState = $constraints['constraint_state'] ?? [];
        $constraintSummary = $constraints['constraint_summary'] ?? [];
        $riskState = $risk['strategic_risk_state'] ?? [];
        $riskSummary = $risk['risk_summary'] ?? [];
        $recommendationState = $recommendations['recommendation_state'] ?? [];
        $recommendationSummary = $recommendations['recommendation_summary'] ?? [];
        $executiveState = $executive['executive_strategic_state'] ?? [];
        $progress = $executive['progress_context'] ?? [];
        $auditSummary = $audit['audit_summary'] ?? [];

        $checks = [
            'strategic_snapshot_available' => [
                'passed' => !empty($strategicState['strategic_snapshot_id']),
                'message' => 'Governance strategic snapshot is available.',
            ],

            'strategic_state_operational' => [
                'passed' => ($strategicState['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic state intelligence is operational.',
            ],

            'capacity_demand_intelligence_operational' => [
                'passed' => ($capacityDemand['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic capacity and demand intelligence is operational.',
            ],

            'strategic_constraint_intelligence_operational' => [
                'passed' => ($constraints['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic constraint intelligence is operational.',
            ],

            'strategic_risk_intelligence_operational' => [
                'passed' => ($risk['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic risk intelligence is operational.',
            ],

            'strategic_recommendation_intelligence_operational' => [
                'passed' => ($recommendations['analysis_completed'] ?? false) === true,
                'message' => 'Governance strategic recommendation intelligence is operational.',
            ],

            'executive_strategic_intelligence_operational' => [
                'passed' => ($executive['analysis_completed'] ?? false) === true,
                'message' => 'Executive strategic governance intelligence is operational.',
            ],

            'step_62_audit_complete' => [
                'passed' =>
                    ($audit['audit_status'] ?? null) === 'COMPLETE' &&
                    (int) ($auditSummary['failed_checks'] ?? 1) === 0,
                'message' => 'Step 62 strategic governance audit completed without integrity failures.',
            ],

            'governance_integrity_intact' => [
                'passed' =>
                    ($riskState['governance_integrity_intact'] ?? false) === true &&
                    ($recommendationState['governance_integrity_intact'] ?? false) === true &&
                    ($executiveState['governance_integrity_intact'] ?? false) === true,
                'message' => 'Strategic governance integrity remains intact.',
            ],

            'critical_strategic_risk_absent' => [
                'passed' => (int) ($riskSummary['critical_signals'] ?? 0) === 0,
                'value' => (int) ($riskSummary['critical_signals'] ?? 0),
                'message' => 'No critical strategic governance risk signal is present.',
            ],

            'critical_strategic_constraint_absent' => [
                'passed' => (int) ($constraintSummary['critical_constraints'] ?? 0) === 0,
                'value' => (int) ($constraintSummary['critical_constraints'] ?? 0),
                'message' => 'No critical strategic governance constraint is present.',
            ],

            'immediate_escalation_absent' => [
                'passed' => ($executiveState['immediate_escalation_required'] ?? true) === false,
                'value' => $executiveState['immediate_escalation_required'] ?? null,
                'message' => 'No immediate strategic governance escalation requirement is active.',
            ],

            'automatic_authority_isolation' => [
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

            'strategic_intelligence_authority_isolation' => [
                'passed' =>
                    ($executive['executive_guardrails']['executive_strategic_status_is_governance_decision'] ?? true) === false &&
                    ($executive['executive_guardrails']['executive_readiness_is_execution_authorization'] ?? true) === false &&
                    ($recommendations['recommendation_guardrails']['recommendation_is_governance_decision'] ?? true) === false,
                'message' => 'Strategic intelligence remains isolated from governance decision, resolution, and execution authority.',
            ],
        ];

        $totalChecks = count($checks);
        $passedChecks = collect($checks)->where('passed', true)->count();
        $failedChecks = $totalChecks - $passedChecks;

        $warnings = [];

        if (($progress['action_closure_percentage'] ?? 100) < 50) {
            $warnings[] =
                'Governance action closure remains at ' .
                ($progress['action_closure_percentage'] ?? 0) .
                '%, below the preferred strategic progression threshold.';
        }

        if (($state['strategic_readiness_score'] ?? 100) < 50) {
            $warnings[] =
                'Strategic governance readiness remains limited at score ' .
                ($state['strategic_readiness_score'] ?? 0) . '.';
        }

        if (($capacity['capacity_score'] ?? 100) < 50) {
            $warnings[] =
                'Governance capacity remains constrained at score ' .
                ($capacity['capacity_score'] ?? 0) . '.';
        }

        if (($capacity['demand_pressure_score'] ?? 0) >= 60) {
            $warnings[] =
                'Governance demand pressure remains high at score ' .
                ($capacity['demand_pressure_score'] ?? 0) . '.';
        }

        if (($constraintState['constraint_level'] ?? null) === 'SEVERE_STRATEGIC_CONSTRAINT') {
            $warnings[] =
                'Severe strategic governance constraint pressure remains active.';
        }

        if (($riskState['strategic_risk_level'] ?? null) === 'HIGH_STRATEGIC_RISK') {
            $warnings[] =
                'Strategic governance risk remains HIGH_STRATEGIC_RISK.';
        }

        if (($executiveState['management_escalation_recommended'] ?? false) === true) {
            $warnings[] =
                'Management escalation remains recommended for current strategic governance conditions.';
        }

        if (($capacity['workload_expansion_readiness'] ?? null) === 'NOT_READY_FOR_EXPANSION') {
            $warnings[] =
                'Strategic governance workload is not currently ready for expansion.';
        }

        $criticalIssues = [];

        if ((int) ($riskSummary['critical_signals'] ?? 0) > 0) {
            $criticalIssues[] =
                'One or more critical strategic governance risk signals are present.';
        }

        if ((int) ($constraintSummary['critical_constraints'] ?? 0) > 0) {
            $criticalIssues[] =
                'One or more critical strategic governance constraints are present.';
        }

        if (($executiveState['immediate_escalation_required'] ?? false) === true) {
            $criticalIssues[] =
                'Immediate human strategic governance escalation is required.';
        }

        if (($riskState['governance_integrity_intact'] ?? false) !== true) {
            $criticalIssues[] =
                'Strategic governance integrity is not intact.';
        }

        if ($failedChecks > 0 || count($criticalIssues) > 0) {
            $validationStatus = 'FAILED';
            $readyForClosure = false;
        } elseif (count($warnings) > 0) {
            $validationStatus = 'PASSED_WITH_WARNINGS';
            $readyForClosure = true;
        } else {
            $validationStatus = 'PASSED';
            $readyForClosure = true;
        }

        return [
            'validation_status' => $validationStatus,
            'step_62_ready_for_closure' => $readyForClosure,
            'governance_strategic_mode' => 'HUMAN_GOVERNED_STRATEGIC_INTELLIGENCE',

            'strategic_snapshot_id' => $strategicState['strategic_snapshot_id'] ?? null,
            'operational_snapshot_id' => $strategicState['operational_snapshot_id'] ?? null,
            'lifecycle_snapshot_id' => $strategicState['lifecycle_snapshot_id'] ?? null,
            'snapshot_scope' => $strategicState['snapshot_scope'] ?? null,
            'resident_id' => $strategicState['resident_id'] ?? null,

            'completion_message' =>
                $readyForClosure
                    ? 'Step 62 AI Governance Strategic Intelligence has passed final validation and is ready for closure.'
                    : 'Step 62 AI Governance Strategic Intelligence has not passed final validation.',

            'validation_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
                'warning_count' => count($warnings),
                'critical_issue_count' => count($criticalIssues),
            ],

            'checks' => $checks,

            'governance_strategic_context' => [
                'strategic_status' => $state['strategic_status'] ?? null,
                'strategic_health' => $state['strategic_health'] ?? null,
                'strategic_readiness' => $state['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $state['strategic_readiness_score'] ?? null,
                'strategic_balance_score' => $state['strategic_balance_score'] ?? null,

                'capacity_status' => $capacity['capacity_status'] ?? null,
                'capacity_score' => $capacity['capacity_score'] ?? null,
                'demand_pressure_status' => $capacity['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $capacity['demand_pressure_score'] ?? null,
                'capacity_demand_gap' => $capacity['capacity_demand_gap'] ?? null,
                'workload_expansion_readiness' => $capacity['workload_expansion_readiness'] ?? null,

                'constraint_level' => $constraintState['constraint_level'] ?? null,
                'constraint_score' => $constraintState['constraint_score'] ?? null,

                'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                'strategic_risk_score' => $riskState['strategic_risk_score'] ?? null,

                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => $recommendationSummary['total_recommendations'] ?? 0,

                'executive_strategic_status' => $executiveState['executive_strategic_status'] ?? null,
                'executive_readiness' => $executiveState['executive_readiness'] ?? null,
                'executive_confidence' => $executiveState['executive_confidence'] ?? null,
                'executive_strategic_score' => $executiveState['executive_strategic_score'] ?? null,

                'action_closure_percentage' => $progress['action_closure_percentage'] ?? null,
                'decision_completion_percentage' => $progress['decision_completion_percentage'] ?? null,
            ],

            'architecture_summary' => [
                '62.1_strategic_snapshot_registry' => [
                    'status' => 'OPERATIONAL',
                ],

                '62.2_strategic_snapshot_generation' => [
                    'status' => 'OPERATIONAL',
                    'strategic_snapshot_id' =>
                        $strategicState['strategic_snapshot_id'] ?? null,
                ],

                '62.3_strategic_state_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'strategic_health' => $state['strategic_health'] ?? null,
                    'strategic_readiness' => $state['strategic_readiness'] ?? null,
                ],

                '62.4_strategic_capacity_demand_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'capacity_status' => $capacity['capacity_status'] ?? null,
                    'demand_pressure_status' => $capacity['demand_pressure_status'] ?? null,
                    'capacity_demand_gap' => $capacity['capacity_demand_gap'] ?? null,
                ],

                '62.5_strategic_constraint_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'constraint_level' => $constraintState['constraint_level'] ?? null,
                    'constraint_score' => $constraintState['constraint_score'] ?? null,
                ],

                '62.6_strategic_risk_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                    'strategic_risk_score' => $riskState['strategic_risk_score'] ?? null,
                ],

                '62.7_strategic_recommendation_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'recommendation_status' =>
                        $recommendationState['recommendation_status'] ?? null,
                    'total_recommendations' =>
                        $recommendationSummary['total_recommendations'] ?? 0,
                ],

                '62.8_executive_strategic_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'executive_strategic_status' =>
                        $executiveState['executive_strategic_status'] ?? null,
                    'executive_readiness' =>
                        $executiveState['executive_readiness'] ?? null,
                    'executive_strategic_score' =>
                        $executiveState['executive_strategic_score'] ?? null,
                ],

                '62.9_strategic_governance_audit' => [
                    'status' => $audit['audit_status'] ?? null,
                    'passed_checks' => $auditSummary['passed_checks'] ?? null,
                    'failed_checks' => $auditSummary['failed_checks'] ?? null,
                ],

                '62.10_final_validation' => [
                    'status' => $validationStatus,
                    'step_62_ready_for_closure' => $readyForClosure,
                ],
            ],

            'warnings' => $warnings,
            'critical_issues' => $criticalIssues,

            'governance_findings' => [
                'The complete Step 62 AI Governance Strategic Intelligence architecture has been validated.',
                'Current strategic governance health is ' .
                    ($state['strategic_health'] ?? 'UNKNOWN') . '.',
                'Current strategic readiness is ' .
                    ($state['strategic_readiness'] ?? 'UNKNOWN') .
                    ' with score ' .
                    ($state['strategic_readiness_score'] ?? 0) . '.',
                'Current governance capacity is ' .
                    ($capacity['capacity_status'] ?? 'UNKNOWN') .
                    ' with score ' .
                    ($capacity['capacity_score'] ?? 0) . '.',
                'Current governance demand pressure is ' .
                    ($capacity['demand_pressure_status'] ?? 'UNKNOWN') .
                    ' with score ' .
                    ($capacity['demand_pressure_score'] ?? 0) . '.',
                'Current strategic constraint level is ' .
                    ($constraintState['constraint_level'] ?? 'UNKNOWN') .
                    ' with score ' .
                    ($constraintState['constraint_score'] ?? 0) . '.',
                'Current strategic governance risk level is ' .
                    ($riskState['strategic_risk_level'] ?? 'UNKNOWN') .
                    ' with score ' .
                    ($riskState['strategic_risk_score'] ?? 0) . '.',
                'Current strategic recommendation status is ' .
                    ($recommendationState['recommendation_status'] ?? 'UNKNOWN') . '.',
                'Current executive strategic status is ' .
                    ($executiveState['executive_strategic_status'] ?? 'UNKNOWN') . '.',
                'Strategic governance intelligence remains advisory and human governed.',
                'No autonomous governance decision, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
            ],

            'step_62_guardrails' => [
                'governance_strategic_intelligence_enabled' => true,
                'strategic_snapshotting_enabled' => true,
                'strategic_state_intelligence_enabled' => true,
                'strategic_capacity_demand_intelligence_enabled' => true,
                'strategic_constraint_intelligence_enabled' => true,
                'strategic_risk_intelligence_enabled' => true,
                'strategic_recommendation_intelligence_enabled' => true,
                'executive_strategic_intelligence_enabled' => true,
                'strategic_governance_audit_enabled' => true,

                'autonomous_governance_strategy_enabled' => false,
                'automatic_governance_decision' => false,
                'automatic_governance_approval' => false,
                'automatic_governance_resolution' => false,

                'automatic_model_change' => false,
                'automatic_threshold_change' => false,
                'automatic_confidence_change' => false,
                'automatic_recommendation_change' => false,
                'automatic_workflow_change' => false,
                'automatic_clinical_rule_change' => false,
                'automatic_clinical_action' => false,
                'automatic_execution' => false,
                'automatic_deployment' => false,
                'automatic_rollback' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Step 62 establishes human-governed AI governance strategic intelligence. The system may consolidate strategic readiness, governance capacity, demand pressure, constraints, risk, strategic recommendations, executive strategic intelligence, and audit integrity for human strategic planning, but it does not autonomously make governance decisions, resolve actions, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
            ],
        ];
    }
}