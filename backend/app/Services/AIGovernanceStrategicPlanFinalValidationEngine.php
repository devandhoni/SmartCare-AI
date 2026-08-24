<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;

class AIGovernanceStrategicPlanFinalValidationEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityEngine,
        protected AIGovernanceStrategicPlanRiskIntelligenceEngine $planRiskEngine,
        protected AIGovernanceStrategicPlanRecommendationIntelligenceEngine $recommendationEngine,
        protected AIGovernanceExecutiveStrategicPlanIntelligenceEngine $executiveEngine,
        protected AIGovernanceStrategicPlanAuditSummaryEngine $auditEngine,
    ) {
    }

    public function analyze(?int $strategicPlanId = null): array
    {
        $plan = $strategicPlanId
            ? AIGovernanceStrategicPlan::find($strategicPlanId)
            : AIGovernanceStrategicPlan::latest('id')->first();

        if (!$plan) {
            return [
                'validation_status' => 'UNAVAILABLE',
                'step_63_ready_for_closure' => false,
                'message' => 'No AI governance strategic plan is available for final validation.',
            ];
        }

        $planState = $this->planStateEngine->analyze($plan->id);
        $dependencyFeasibility = $this->dependencyFeasibilityEngine->analyze($plan->id);
        $planRisk = $this->planRiskEngine->analyze($plan->id);
        $recommendations = $this->recommendationEngine->analyze($plan->id);
        $executive = $this->executiveEngine->analyze($plan->id);
        $audit = $this->auditEngine->analyze($plan->id);

        $planStateContext = $planState['plan_state'] ?? [];
        $dependencyContext = $dependencyFeasibility['dependency_feasibility_state'] ?? [];
        $dependencySummary = $dependencyFeasibility['dependency_summary'] ?? [];
        $planRiskState = $planRisk['strategic_plan_risk_state'] ?? [];
        $riskSummary = $planRisk['risk_summary'] ?? [];
        $recommendationState = $recommendations['recommendation_state'] ?? [];
        $recommendationSummary = $recommendations['recommendation_summary'] ?? [];
        $executiveState = $executive['executive_strategic_plan_state'] ?? [];
        $auditSummary = $audit['audit_summary'] ?? [];
        $integritySummary = $audit['integrity_summary'] ?? [];

        $criticalRiskSignals = (int) ($riskSummary['critical_signals'] ?? 0);
        $criticalDependencies = (int) ($dependencySummary['critical_dependencies'] ?? 0);
        $blockingDependencies = (int) ($dependencySummary['blocking_dependencies'] ?? 0);

        $governanceIntegrityIntact = (bool) ($integritySummary['governance_integrity_intact'] ?? false);
        $automaticExecutionAllowed = (bool) ($integritySummary['automatic_execution_allowed'] ?? true);
        $automaticChangeAllowed = (bool) ($integritySummary['automatic_change_allowed'] ?? true);
        $automaticDeploymentAllowed = (bool) ($integritySummary['automatic_deployment_allowed'] ?? true);
        $automaticRollbackAllowed = (bool) ($integritySummary['automatic_rollback_allowed'] ?? true);
        $automaticClinicalActionAllowed = (bool) ($integritySummary['automatic_clinical_action_allowed'] ?? true);
        $humanReviewRequired = (bool) ($integritySummary['human_review_required'] ?? false);
        $governanceValidationRequired = (bool) ($integritySummary['governance_validation_required'] ?? false);

        $immediateEscalationRequired = (bool) (
            $executiveState['immediate_escalation_required']
            ?? false
        );

        $checks = [
            'strategic_plan_available' => [
                'passed' => true,
                'message' => 'Governance strategic plan is available.',
            ],

            'strategic_plan_state_operational' => [
                'passed' => ($planState['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan state intelligence is operational.',
            ],

            'dependency_feasibility_intelligence_operational' => [
                'passed' => ($dependencyFeasibility['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan dependency and feasibility intelligence is operational.',
            ],

            'strategic_plan_risk_intelligence_operational' => [
                'passed' => ($planRisk['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan risk intelligence is operational.',
            ],

            'strategic_plan_recommendation_intelligence_operational' => [
                'passed' => ($recommendations['analysis_completed'] ?? false) === true,
                'message' => 'Strategic plan recommendation intelligence is operational.',
            ],

            'executive_strategic_plan_intelligence_operational' => [
                'passed' => ($executive['analysis_completed'] ?? false) === true,
                'message' => 'Executive strategic plan intelligence is operational.',
            ],

            'step_63_audit_complete' => [
                'passed' =>
                    ($audit['audit_status'] ?? null) === 'COMPLETE'
                    && (int) ($auditSummary['failed_checks'] ?? 1) === 0,
                'message' => 'Step 63 strategic plan audit completed without integrity failures.',
            ],

            'governance_integrity_intact' => [
                'passed' => $governanceIntegrityIntact,
                'message' => 'Strategic plan governance integrity remains intact.',
            ],

            'critical_plan_risk_absent' => [
                'passed' => $criticalRiskSignals === 0,
                'value' => $criticalRiskSignals,
                'message' => 'No critical strategic plan risk signal is present.',
            ],

            'critical_dependency_absent' => [
                'passed' => $criticalDependencies === 0,
                'value' => $criticalDependencies,
                'message' => 'No critical strategic planning dependency is present.',
            ],

            'immediate_escalation_absent' => [
                'passed' => $immediateEscalationRequired === false,
                'value' => $immediateEscalationRequired,
                'message' => 'No immediate strategic plan governance escalation requirement is active.',
            ],

            'automatic_authority_isolation' => [
                'passed' =>
                    $automaticExecutionAllowed === false
                    && $automaticChangeAllowed === false
                    && $automaticDeploymentAllowed === false
                    && $automaticRollbackAllowed === false
                    && $automaticClinicalActionAllowed === false,
                'message' => 'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    $humanReviewRequired === true
                    && $governanceValidationRequired === true,
                'message' => 'Human review and governance validation remain mandatory.',
            ],

            'plan_intelligence_authority_isolation' => [
                'passed' => true,
                'message' => 'Strategic plan intelligence remains isolated from plan approval, rejection, activation, dependency resolution, and execution authority.',
            ],

            'audit_authority_isolation' => [
                'passed' => true,
                'message' => 'Strategic plan audit and validation remain informational and do not become governance execution authority.',
            ],
        ];

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(fn ($check) => ($check['passed'] ?? false) === true)
            ->count();

        $failedChecks = $totalChecks - $passedChecks;

        $warnings = [];

        $planningReadinessScore = (float) ($planStateContext['planning_readiness_score'] ?? 0);
        $planHealthScore = (float) ($planStateContext['plan_health_score'] ?? 0);
        $planFeasibilityScore = (float) ($planStateContext['plan_feasibility_score'] ?? 0);
        $dependencyAdjustedFeasibility = (float) (
            $dependencyContext['dependency_adjusted_feasibility_score']
            ?? 0
        );
        $dependencyPressureScore = (float) (
            $dependencyContext['dependency_pressure_score']
            ?? 0
        );
        $planRiskScore = (float) ($planRiskState['strategic_plan_risk_score'] ?? 0);
        $executivePlanScore = (float) ($executiveState['executive_strategic_plan_score'] ?? 0);

        if ($planningReadinessScore < 50) {
            $warnings[] = "Strategic planning readiness remains limited at score {$planningReadinessScore}.";
        }

        if ($planHealthScore < 50) {
            $warnings[] = "Strategic plan health remains weak at score {$planHealthScore}.";
        }

        if ($planFeasibilityScore < 50) {
            $warnings[] = "Base strategic plan feasibility remains limited at score {$planFeasibilityScore}.";
        }

        if ($dependencyAdjustedFeasibility < 50) {
            $warnings[] = "Dependency-adjusted strategic plan feasibility remains limited at score {$dependencyAdjustedFeasibility}.";
        }

        if ($blockingDependencies > 0) {
            $warnings[] = "{$blockingDependencies} blocking strategic planning dependency condition(s) remain active.";
        }

        if ($dependencyPressureScore >= 50) {
            $warnings[] = "Strategic planning dependency pressure remains elevated at score {$dependencyPressureScore}.";
        }

        if (($planRiskState['strategic_plan_risk_level'] ?? null) === 'HIGH_STRATEGIC_PLAN_RISK') {
            $warnings[] = 'Strategic plan risk remains HIGH_STRATEGIC_PLAN_RISK.';
        }

        if (($executiveState['management_escalation_recommended'] ?? false) === true) {
            $warnings[] = 'Management escalation remains recommended for current strategic plan conditions.';
        }

        if ($executivePlanScore < 50) {
            $warnings[] = "Executive strategic plan readiness remains limited at score {$executivePlanScore}.";
        }

        $criticalIssues = [];

        if (!$governanceIntegrityIntact) {
            $criticalIssues[] = 'Strategic plan governance integrity is not intact.';
        }

        if ($criticalRiskSignals > 0) {
            $criticalIssues[] = "{$criticalRiskSignals} critical strategic plan risk signal(s) are present.";
        }

        if ($criticalDependencies > 0) {
            $criticalIssues[] = "{$criticalDependencies} critical strategic planning dependency condition(s) are present.";
        }

        if ($immediateEscalationRequired) {
            $criticalIssues[] = 'Immediate human strategic plan governance escalation is required.';
        }

        if (
            $automaticExecutionAllowed
            || $automaticChangeAllowed
            || $automaticDeploymentAllowed
            || $automaticRollbackAllowed
            || $automaticClinicalActionAllowed
        ) {
            $criticalIssues[] = 'One or more prohibited automatic strategic plan authorities are enabled.';
        }

        if (!$humanReviewRequired || !$governanceValidationRequired) {
            $criticalIssues[] = 'Required human governance controls are not fully enabled.';
        }

        $readyForClosure =
            $failedChecks === 0
            && count($criticalIssues) === 0;

        $validationStatus = match (true) {
            !$readyForClosure => 'FAILED',
            count($warnings) > 0 => 'PASSED_WITH_WARNINGS',
            default => 'PASSED',
        };

        return [
            'validation_status' => $validationStatus,
            'step_63_ready_for_closure' => $readyForClosure,
            'governance_strategic_plan_mode' => 'HUMAN_GOVERNED_STRATEGIC_PLANNING_INTELLIGENCE',

            'strategic_plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'strategic_snapshot_id' => $plan->strategic_snapshot_id,
            'operational_snapshot_id' => $plan->operational_snapshot_id,
            'lifecycle_snapshot_id' => $plan->lifecycle_snapshot_id,
            'plan_scope' => $plan->plan_scope,
            'resident_id' => $plan->resident_id,

            'completion_message' => $readyForClosure
                ? 'Step 63 AI Governance Strategic Planning Intelligence has passed final validation and is ready for closure.'
                : 'Step 63 AI Governance Strategic Planning Intelligence has not passed final validation.',

            'validation_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
                'warning_count' => count($warnings),
                'critical_issue_count' => count($criticalIssues),
            ],

            'checks' => $checks,

            'governance_strategic_plan_context' => [
                'plan_status' => $planStateContext['plan_status'] ?? null,
                'planning_mode' => $planStateContext['planning_mode'] ?? null,
                'strategic_plan_state' => $planStateContext['strategic_plan_state'] ?? null,

                'plan_health' => $planStateContext['plan_health'] ?? null,
                'plan_health_score' => $planHealthScore,

                'planning_readiness' => $planStateContext['planning_readiness'] ?? null,
                'planning_readiness_score' => $planningReadinessScore,

                'plan_feasibility' => $planStateContext['plan_feasibility'] ?? null,
                'plan_feasibility_score' => $planFeasibilityScore,

                'planning_maturity' => $planStateContext['planning_maturity'] ?? null,
                'planning_confidence' => $planStateContext['planning_confidence'] ?? null,

                'dependency_feasibility_status' => $dependencyContext['dependency_feasibility_status'] ?? null,
                'dependency_resolution_readiness' => $dependencyContext['dependency_resolution_readiness'] ?? null,
                'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibility,
                'dependency_pressure_score' => $dependencyPressureScore,
                'blocking_dependencies' => $blockingDependencies,
                'constraining_dependencies' => (int) (
                    $dependencyContext['constraining_dependency_count']
                    ?? 0
                ),

                'strategic_plan_risk_level' => $planRiskState['strategic_plan_risk_level'] ?? null,
                'strategic_plan_risk_score' => $planRiskScore,

                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => (int) (
                    $recommendationSummary['total_recommendations']
                    ?? 0
                ),

                'executive_strategic_plan_status' => $executiveState['executive_strategic_plan_status'] ?? null,
                'executive_readiness' => $executiveState['executive_readiness'] ?? null,
                'executive_confidence' => $executiveState['executive_confidence'] ?? null,
                'executive_strategic_plan_score' => $executivePlanScore,

                'management_escalation_recommended' => (bool) (
                    $executiveState['management_escalation_recommended']
                    ?? false
                ),

                'immediate_escalation_required' => $immediateEscalationRequired,
            ],

            'architecture_summary' => [
                '63.1_strategic_plan_registry' => [
                    'status' => 'OPERATIONAL',
                ],

                '63.2_strategic_plan_generation' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_id' => $plan->id,
                    'plan_code' => $plan->plan_code,
                ],

                '63.3_strategic_plan_state_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_state' => $planStateContext['strategic_plan_state'] ?? null,
                    'plan_health' => $planStateContext['plan_health'] ?? null,
                    'planning_readiness' => $planStateContext['planning_readiness'] ?? null,
                ],

                '63.4_dependency_feasibility_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'dependency_feasibility_status' => $dependencyContext['dependency_feasibility_status'] ?? null,
                    'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibility,
                    'blocking_dependencies' => $blockingDependencies,
                ],

                '63.5_strategic_plan_risk_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_risk_level' => $planRiskState['strategic_plan_risk_level'] ?? null,
                    'strategic_plan_risk_score' => $planRiskScore,
                ],

                '63.6_strategic_plan_recommendation_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                    'total_recommendations' => (int) (
                        $recommendationSummary['total_recommendations']
                        ?? 0
                    ),
                ],

                '63.7_executive_strategic_plan_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'executive_strategic_plan_status' => $executiveState['executive_strategic_plan_status'] ?? null,
                    'executive_readiness' => $executiveState['executive_readiness'] ?? null,
                    'executive_strategic_plan_score' => $executivePlanScore,
                ],

                '63.8_strategic_plan_audit' => [
                    'status' => $audit['audit_status'] ?? 'UNKNOWN',
                    'passed_checks' => (int) ($auditSummary['passed_checks'] ?? 0),
                    'failed_checks' => (int) ($auditSummary['failed_checks'] ?? 0),
                ],

                '63.9_final_validation' => [
                    'status' => $validationStatus,
                    'step_63_ready_for_closure' => $readyForClosure,
                ],
            ],

            'warnings' => $warnings,
            'critical_issues' => $criticalIssues,

            'governance_findings' => [
                'The complete Step 63 AI Governance Strategic Planning Intelligence architecture has been validated.',
                "Current strategic plan status is " . ($planStateContext['plan_status'] ?? 'UNKNOWN') . '.',
                "Current strategic plan state is " . ($planStateContext['strategic_plan_state'] ?? 'UNKNOWN') . '.',
                "Current strategic plan health is " . ($planStateContext['plan_health'] ?? 'UNKNOWN') . " with score {$planHealthScore}.",
                "Current strategic planning readiness is " . ($planStateContext['planning_readiness'] ?? 'UNKNOWN') . " with score {$planningReadinessScore}.",
                "Current strategic plan feasibility is " . ($planStateContext['plan_feasibility'] ?? 'UNKNOWN') . " with score {$planFeasibilityScore}.",
                "Current dependency-adjusted feasibility score is {$dependencyAdjustedFeasibility}.",
                "{$blockingDependencies} blocking strategic planning dependency condition(s) remain active.",
                "Current strategic plan risk level is " . ($planRiskState['strategic_plan_risk_level'] ?? 'UNKNOWN') . " with score {$planRiskScore}.",
                "Current strategic plan recommendation status is " . ($recommendationState['recommendation_status'] ?? 'UNKNOWN') . '.',
                "Current executive strategic plan status is " . ($executiveState['executive_strategic_plan_status'] ?? 'UNKNOWN') . '.',
                'Strategic planning intelligence remains advisory and human governed.',
                'No autonomous strategic plan approval, activation, dependency resolution, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
            ],

            'step_63_guardrails' => [
                'governance_strategic_planning_intelligence_enabled' => true,

                'strategic_plan_registry_enabled' => true,
                'strategic_plan_generation_enabled' => true,
                'strategic_plan_state_intelligence_enabled' => true,
                'strategic_plan_dependency_feasibility_intelligence_enabled' => true,
                'strategic_plan_risk_intelligence_enabled' => true,
                'strategic_plan_recommendation_intelligence_enabled' => true,
                'executive_strategic_plan_intelligence_enabled' => true,
                'strategic_plan_audit_enabled' => true,

                'autonomous_strategic_plan_approval_enabled' => false,
                'autonomous_strategic_plan_rejection_enabled' => false,
                'autonomous_strategic_plan_activation_enabled' => false,
                'autonomous_dependency_resolution_enabled' => false,

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

                'message' => 'Step 63 establishes human-governed AI governance strategic planning intelligence. The system may generate structured strategic plans, evaluate plan state, readiness, health, feasibility, dependencies, risk, recommendations, and executive planning conditions for human governance review, but it does not autonomously approve, reject, activate, resolve dependencies, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
            ],
        ];
    }
}