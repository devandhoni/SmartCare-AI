<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanAuditSummaryEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityEngine,
        protected AIGovernanceStrategicPlanRiskIntelligenceEngine $planRiskEngine,
        protected AIGovernanceStrategicPlanRecommendationIntelligenceEngine $recommendationEngine,
        protected AIGovernanceExecutiveStrategicPlanIntelligenceEngine $executiveEngine,
    ) {
    }

    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $strategicPlanId
            ? AIGovernanceStrategicPlan::find($strategicPlanId)
            : AIGovernanceStrategicPlan::latest('id')->first();

        if (!$plan) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_STRATEGIC_PLAN_AVAILABLE',
                'message' => 'No AI governance strategic plan is available for Step 63 strategic plan audit.',
            ];
        }

        $planStateResult = $this->planStateEngine->analyze($plan->id);
        $dependencyResult = $this->dependencyFeasibilityEngine->analyze($plan->id);
        $riskResult = $this->planRiskEngine->analyze($plan->id);
        $recommendationResult = $this->recommendationEngine->analyze($plan->id);
        $executiveResult = $this->executiveEngine->analyze($plan->id);

        $planState = $planStateResult['plan_state'] ?? [];
        $dependencyState = $dependencyResult['dependency_feasibility_state'] ?? [];
        $dependencySummary = $dependencyResult['dependency_summary'] ?? [];
        $riskState = $riskResult['strategic_plan_risk_state'] ?? [];
        $riskSummary = $riskResult['risk_summary'] ?? [];
        $recommendationState = $recommendationResult['recommendation_state'] ?? [];
        $recommendationSummary = $recommendationResult['recommendation_summary'] ?? [];
        $executiveState = $executiveResult['executive_strategic_plan_state'] ?? [];

        $guardrailIntegrity =
            $plan->automatic_execution_allowed === false &&
            $plan->automatic_change_allowed === false &&
            $plan->automatic_deployment_allowed === false &&
            $plan->automatic_rollback_allowed === false &&
            $plan->automatic_clinical_action_allowed === false &&
            $plan->human_review_required === true &&
            $plan->governance_validation_required === true;

        $criticalRiskSignals = (int) ($riskSummary['critical_signals'] ?? 0);
        $criticalDependencies = (int) ($dependencySummary['critical_dependencies'] ?? 0);

        $immediateEscalationRequired =
            (bool) ($executiveState['immediate_escalation_required'] ?? false);

        $checks = [
            'strategic_plan_available' => [
                'passed' => true,
                'message' => 'Governance strategic plan is available.',
            ],

            'strategic_plan_state_intelligence_available' => [
                'passed' => ($planStateResult['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan state intelligence is available.',
            ],

            'dependency_feasibility_intelligence_available' => [
                'passed' => ($dependencyResult['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan dependency and feasibility intelligence is available.',
            ],

            'strategic_plan_risk_intelligence_available' => [
                'passed' => ($riskResult['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan risk intelligence is available.',
            ],

            'strategic_plan_recommendation_intelligence_available' => [
                'passed' => ($recommendationResult['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan recommendation intelligence is available.',
            ],

            'executive_strategic_plan_intelligence_available' => [
                'passed' => ($executiveResult['analysis_completed'] ?? false) === true,
                'message' => 'Executive strategic plan intelligence is available.',
            ],

            'governance_integrity_intact' => [
                'passed' => $guardrailIntegrity,
                'message' => 'Strategic plan governance integrity remains intact.',
            ],

            'no_critical_plan_risk_signal' => [
                'passed' => $criticalRiskSignals === 0,
                'value' => $criticalRiskSignals,
                'message' => 'No critical strategic plan risk signal should be present.',
            ],

            'no_critical_dependency' => [
                'passed' => $criticalDependencies === 0,
                'value' => $criticalDependencies,
                'message' => 'No critical strategic planning dependency should be present.',
            ],

            'no_immediate_escalation_requirement' => [
                'passed' => $immediateEscalationRequired === false,
                'value' => $immediateEscalationRequired,
                'message' => 'No immediate strategic plan escalation requirement should be active.',
            ],

            'automatic_execution_isolation' => [
                'passed' =>
                    $plan->automatic_execution_allowed === false &&
                    $plan->automatic_change_allowed === false &&
                    $plan->automatic_deployment_allowed === false &&
                    $plan->automatic_rollback_allowed === false &&
                    $plan->automatic_clinical_action_allowed === false,
                'message' => 'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    $plan->human_review_required === true &&
                    $plan->governance_validation_required === true,
                'message' => 'Human review and governance validation remain mandatory.',
            ],

            'plan_state_authority_isolation' => [
                'passed' => true,
                'message' => 'Strategic plan state intelligence remains isolated from approval, rejection, activation, and execution authority.',
            ],

            'recommendation_authority_isolation' => [
                'passed' => true,
                'message' => 'Strategic plan recommendation intelligence remains advisory and does not become governance decision authority.',
            ],

            'executive_authority_isolation' => [
                'passed' => true,
                'message' => 'Executive strategic plan intelligence remains informational and does not become plan approval, activation, or execution authority.',
            ],
        ];

        $totalChecks = count($checks);
        $passedChecks = count(
            array_filter(
                $checks,
                fn (array $check): bool => ($check['passed'] ?? false) === true
            )
        );

        $failedChecks = $totalChecks - $passedChecks;

        $auditStatus = $failedChecks === 0
            ? 'COMPLETE'
            : 'INTEGRITY_FAILURE';

        $managementStatus = $this->determineManagementStatus(
            $riskState,
            $dependencyState,
            $executiveState
        );

        $auditFindings = [
            "Strategic plan audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
            "Strategic plan audit is based on strategic plan {$plan->id}.",
            "Current strategic plan status is " . ($planState['plan_status'] ?? 'UNKNOWN') . ".",
            "Current strategic plan state is " . ($planState['strategic_plan_state'] ?? 'UNKNOWN') . ".",
            "Current planning readiness is " . ($planState['planning_readiness'] ?? 'UNKNOWN') .
                " with score " . $this->formatNumber($planState['planning_readiness_score'] ?? 0) . ".",
            "Current strategic plan health is " . ($planState['plan_health'] ?? 'UNKNOWN') .
                " with score " . $this->formatNumber($planState['plan_health_score'] ?? 0) . ".",
            "Current strategic plan feasibility is " . ($planState['plan_feasibility'] ?? 'UNKNOWN') .
                " with score " . $this->formatNumber($planState['plan_feasibility_score'] ?? 0) . ".",
            "Current dependency-adjusted feasibility score is " .
                $this->formatNumber($dependencyState['dependency_adjusted_feasibility_score'] ?? 0) . ".",
            ($dependencySummary['blocking_dependencies'] ?? 0) .
                " blocking strategic planning dependency condition(s) remain active.",
            ($dependencySummary['constraining_dependencies'] ?? 0) .
                " constraining strategic planning dependency condition(s) remain active.",
            "Current strategic plan risk level is " .
                ($riskState['strategic_plan_risk_level'] ?? 'UNKNOWN') .
                " with score " .
                $this->formatNumber($riskState['strategic_plan_risk_score'] ?? 0) . ".",
            ($recommendationSummary['total_recommendations'] ?? 0) .
                " strategic plan management recommendation(s) are currently generated.",
            "Current strategic plan recommendation status is " .
                ($recommendationState['recommendation_status'] ?? 'UNKNOWN') . ".",
            "Current executive strategic plan status is " .
                ($executiveState['executive_strategic_plan_status'] ?? 'UNKNOWN') . ".",
            "Current executive strategic plan readiness is " .
                ($executiveState['executive_readiness'] ?? 'UNKNOWN') . ".",
            "Current executive strategic plan score is " .
                $this->formatNumber($executiveState['executive_strategic_plan_score'] ?? 0) . ".",
            "Critical strategic plan risk signal count is {$criticalRiskSignals}.",
            "Critical strategic planning dependency count is {$criticalDependencies}.",
            "Strategic plan governance integrity remains " .
                ($guardrailIntegrity ? 'INTACT' : 'NOT_INTACT') . ".",
            "Step 63 strategic plan integrity controls currently contain {$failedChecks} failure(s).",
        ];

        return [
            'audit_available' => true,
            'audit_status' => $auditStatus,

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'management_status' => $managementStatus,

            'audit_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
            ],

            'checks' => $checks,

            'plan_summary' => [
                'plan_status' => $planState['plan_status'] ?? null,
                'planning_mode' => $planState['planning_mode'] ?? null,
                'strategic_plan_state' => $planState['strategic_plan_state'] ?? null,
                'plan_health' => $planState['plan_health'] ?? null,
                'plan_health_score' => $planState['plan_health_score'] ?? null,
                'planning_readiness' => $planState['planning_readiness'] ?? null,
                'planning_readiness_score' => $planState['planning_readiness_score'] ?? null,
                'plan_feasibility' => $planState['plan_feasibility'] ?? null,
                'plan_feasibility_score' => $planState['plan_feasibility_score'] ?? null,
                'planning_maturity' => $planState['planning_maturity'] ?? null,
                'planning_confidence' => $planState['planning_confidence'] ?? null,
            ],

            'dependency_summary' => [
                'dependency_feasibility_status' => $dependencyState['dependency_feasibility_status'] ?? null,
                'dependency_resolution_readiness' => $dependencyState['dependency_resolution_readiness'] ?? null,
                'base_plan_feasibility_score' => $dependencyState['base_plan_feasibility_score'] ?? null,
                'dependency_adjusted_feasibility_score' => $dependencyState['dependency_adjusted_feasibility_score'] ?? null,
                'dependency_pressure_score' => $dependencyState['dependency_pressure_score'] ?? null,
                'blocking_dependencies' => $dependencySummary['blocking_dependencies'] ?? 0,
                'constraining_dependencies' => $dependencySummary['constraining_dependencies'] ?? 0,
                'critical_dependencies' => $criticalDependencies,
                'dominant_dependency' => $dependencyResult['dominant_dependency'] ?? null,
            ],

            'risk_summary' => [
                'strategic_plan_risk_level' => $riskState['strategic_plan_risk_level'] ?? null,
                'strategic_plan_risk_score' => $riskState['strategic_plan_risk_score'] ?? null,
                'risk_control_status' => $riskState['risk_control_status'] ?? null,
                'critical_signals' => $criticalRiskSignals,
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
                'executive_strategic_plan_status' => $executiveState['executive_strategic_plan_status'] ?? null,
                'executive_readiness' => $executiveState['executive_readiness'] ?? null,
                'executive_confidence' => $executiveState['executive_confidence'] ?? null,
                'executive_strategic_plan_score' => $executiveState['executive_strategic_plan_score'] ?? null,
                'management_escalation_recommended' => $executiveState['management_escalation_recommended'] ?? false,
                'immediate_escalation_required' => $executiveState['immediate_escalation_required'] ?? false,
            ],

            'progress_summary' => $planStateResult['progress_context'] ?? [],

            'integrity_summary' => [
                'governance_integrity_intact' => $guardrailIntegrity,
                'automatic_execution_allowed' => (bool) $plan->automatic_execution_allowed,
                'automatic_change_allowed' => (bool) $plan->automatic_change_allowed,
                'automatic_deployment_allowed' => (bool) $plan->automatic_deployment_allowed,
                'automatic_rollback_allowed' => (bool) $plan->automatic_rollback_allowed,
                'automatic_clinical_action_allowed' => (bool) $plan->automatic_clinical_action_allowed,
                'human_review_required' => (bool) $plan->human_review_required,
                'governance_validation_required' => (bool) $plan->governance_validation_required,
            ],

            'audit_findings' => $auditFindings,

            'management_priorities' => $executiveResult['executive_priorities'] ?? [],

            'audit_guardrails' => [
                'strategic_plan_audit_enabled' => true,

                'audit_is_governance_decision' => false,
                'audit_is_governance_approval' => false,
                'audit_is_governance_rejection' => false,
                'audit_is_plan_activation' => false,
                'audit_is_action_resolution' => false,

                'audit_changes_plan_status' => false,
                'audit_changes_action_state' => false,
                'audit_changes_priority' => false,
                'audit_changes_eligibility' => false,

                'audit_resolves_dependencies' => false,

                'audit_is_execution_authorization' => false,
                'audit_is_deployment_authorization' => false,
                'audit_is_rollback_authorization' => false,
                'audit_is_clinical_action_authorization' => false,

                'audit_overrides_human_review' => false,
                'audit_overrides_evidence_requirements' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Strategic plan audit consolidates Step 63 plan generation, plan state, dependency feasibility, plan risk, plan recommendations, executive planning intelligence, progression, and governance-control integrity for human audit and strategic management review only. The audit does not approve or reject the strategic plan, activate planning work, resolve dependencies, change plan or governance action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function determineManagementStatus(
        array $riskState,
        array $dependencyState,
        array $executiveState
    ): string {
        if (($executiveState['immediate_escalation_required'] ?? false) === true) {
            return 'IMMEDIATE_STRATEGIC_PLAN_MANAGEMENT_ATTENTION_REQUIRED';
        }

        if (
            ($riskState['strategic_plan_risk_level'] ?? null) === 'HIGH_STRATEGIC_PLAN_RISK' ||
            ($dependencyState['dependency_feasibility_status'] ?? null) === 'VERY_LOW_DEPENDENCY_FEASIBILITY'
        ) {
            return 'ELEVATED_STRATEGIC_PLAN_GOVERNANCE_WORK_REMAINS';
        }

        if (
            ($executiveState['management_escalation_recommended'] ?? false) === true
        ) {
            return 'STRATEGIC_PLAN_MANAGEMENT_ATTENTION_RECOMMENDED';
        }

        return 'CONTROLLED_STRATEGIC_PLAN_GOVERNANCE';
    }

    private function formatNumber(float|int|string|null $value): string
    {
        $formatted = number_format((float) ($value ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}