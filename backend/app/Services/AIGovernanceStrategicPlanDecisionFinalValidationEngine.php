<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanDecisionFinalValidationEngine
{
    public function analyze(?int $decisionId = null): array
    {
        $decision = $decisionId
            ? AIGovernanceStrategicPlanDecision::find($decisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$decision) {
            return [
                'validation_status' => 'FAILED',
                'step_64_ready_for_closure' => false,
                'governance_strategic_plan_decision_mode' => 'HUMAN_GOVERNED_STRATEGIC_PLAN_DECISION_INTELLIGENCE',
                'message' => 'No AI governance strategic plan decision record is available for final validation.',
            ];
        }

        $decisionStateEngine = app(
            AIGovernanceStrategicPlanDecisionStateIntelligenceEngine::class
        );

        $conditionEvidenceEngine = app(
            AIGovernanceStrategicPlanDecisionConditionEvidenceIntelligenceEngine::class
        );

        $eligibilityRiskEngine = app(
            AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanHumanDecisionRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceExecutiveStrategicPlanDecisionIntelligenceEngine::class
        );

        $auditEngine = app(
            AIGovernanceStrategicPlanDecisionAuditSummaryEngine::class
        );

        $decisionState = $decisionStateEngine->analyze($decision->id);
        $conditionEvidence = $conditionEvidenceEngine->analyze($decision->id);
        $eligibilityRisk = $eligibilityRiskEngine->analyze($decision->id);
        $recommendation = $recommendationEngine->analyze($decision->id);
        $executive = $executiveEngine->analyze($decision->id);
        $audit = $auditEngine->analyze($decision->id);

        /*
        |--------------------------------------------------------------------------
        | Core contexts
        |--------------------------------------------------------------------------
        */

        $decisionStateContext =
            $decisionState['decision_state'] ?? [];

        $conditionEvidenceContext =
            $conditionEvidence['condition_evidence_state'] ?? [];

        $eligibilityContext =
            $eligibilityRisk['human_decision_eligibility_state'] ?? [];

        $riskContext =
            $eligibilityRisk['human_decision_risk_state'] ?? [];

        $eligibilitySummary =
            $eligibilityRisk['eligibility_summary'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveContext =
            $executive['executive_strategic_plan_decision_state'] ?? [];

        $auditSummary =
            $audit['audit_summary'] ?? [];

        $auditGuardrails =
            $audit['audit_guardrails'] ?? [];

        $eligibilityGuardrails =
            $eligibilityRisk['human_decision_eligibility_risk_guardrails'] ?? [];

        $recommendationGuardrails =
            $recommendation['human_decision_recommendation_guardrails'] ?? [];

        $executiveGuardrails =
            $executive['executive_guardrails'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Validation checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_decision_available'] = [
            'passed' => true,
            'message' => 'Governance strategic plan decision package is available.',
        ];

        $checks['decision_state_intelligence_operational'] = [
            'passed' =>
                ($decisionState['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan decision state intelligence is operational.',
        ];

        $checks['condition_evidence_intelligence_operational'] = [
            'passed' =>
                ($conditionEvidence['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan decision condition and evidence intelligence is operational.',
        ];

        $checks['human_decision_eligibility_risk_intelligence_operational'] = [
            'passed' =>
                ($eligibilityRisk['analysis_completed'] ?? false) === true,
            'message' =>
                'Human strategic plan decision eligibility and risk intelligence is operational.',
        ];

        $checks['human_decision_recommendation_intelligence_operational'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' =>
                'Human strategic plan decision recommendation intelligence is operational.',
        ];

        $checks['executive_strategic_plan_decision_intelligence_operational'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' =>
                'Executive strategic plan decision intelligence is operational.',
        ];

        $checks['step_64_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (int) ($auditSummary['failed_checks'] ?? 1) === 0,
            'message' =>
                'Step 64 strategic plan decision audit completed without integrity failures.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($riskContext['governance_integrity_intact'] ?? false) === true
            && ($executiveContext['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' =>
                'Strategic plan decision governance integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical-condition validation
        |--------------------------------------------------------------------------
        */

        $criticalOpenConditions =
            (int) ($eligibilitySummary['critical_open_conditions'] ?? 0);

        $checks['critical_decision_condition_absent'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' =>
                'No critical strategic plan decision condition is currently open.',
        ];

        $criticalOutstandingEvidence =
            (int) ($eligibilitySummary['critical_outstanding_evidence_items'] ?? 0);

        $checks['critical_outstanding_evidence_absent'] = [
            'passed' => $criticalOutstandingEvidence === 0,
            'value' => $criticalOutstandingEvidence,
            'message' =>
                'No critical strategic plan decision evidence requirement remains outstanding.',
        ];

        $immediateEscalation =
            ($executiveContext['immediate_escalation_required'] ?? false) === true;

        $checks['immediate_escalation_absent'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' =>
                'No immediate strategic plan decision governance escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic authority isolation
        |--------------------------------------------------------------------------
        */

        $automaticAuthorityIsolated =
            !$decision->automatic_execution_allowed
            && !$decision->automatic_change_allowed
            && !$decision->automatic_deployment_allowed
            && !$decision->automatic_rollback_allowed
            && !$decision->automatic_clinical_action_allowed;

        $checks['automatic_authority_isolation'] = [
            'passed' => $automaticAuthorityIsolated,
            'message' =>
                'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human governance controls
        |--------------------------------------------------------------------------
        */

        $humanGovernanceControls =
            (bool) $decision->human_review_required
            && (bool) $decision->governance_validation_required;

        $checks['human_governance_controls'] = [
            'passed' => $humanGovernanceControls,
            'message' =>
                'Human review and governance validation remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility authority isolation
        |--------------------------------------------------------------------------
        */

        $eligibilityAuthorityIsolated =
            ($eligibilityGuardrails['eligibility_is_human_final_decision'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_is_governance_approval'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_is_governance_rejection'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_is_plan_activation'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_analysis_changes_decision_status'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_analysis_resolves_conditions'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_analysis_validates_evidence'] ?? true) === false
            && ($eligibilityGuardrails['eligibility_analysis_authorizes_execution'] ?? true) === false
            && ($eligibilityGuardrails['human_review_required'] ?? false) === true
            && ($eligibilityGuardrails['governance_validation_required'] ?? false) === true;

        $checks['eligibility_authority_isolation'] = [
            'passed' => $eligibilityAuthorityIsolated,
            'message' =>
                'Human decision eligibility intelligence remains isolated from final approval, rejection, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation authority isolation
        |--------------------------------------------------------------------------
        */

        $recommendationAuthorityIsolated =
            ($recommendationGuardrails['recommendation_is_human_final_decision'] ?? true) === false
            && ($recommendationGuardrails['recommendation_is_governance_approval'] ?? true) === false
            && ($recommendationGuardrails['recommendation_is_governance_rejection'] ?? true) === false
            && ($recommendationGuardrails['recommendation_is_plan_activation'] ?? true) === false
            && ($recommendationGuardrails['recommendation_changes_decision_status'] ?? true) === false
            && ($recommendationGuardrails['recommendation_changes_plan_status'] ?? true) === false
            && ($recommendationGuardrails['recommendation_resolves_conditions'] ?? true) === false
            && ($recommendationGuardrails['recommendation_resolves_dependencies'] ?? true) === false
            && ($recommendationGuardrails['recommendation_validates_evidence'] ?? true) === false
            && ($recommendationGuardrails['recommendation_authorizes_execution'] ?? true) === false
            && ($recommendationGuardrails['recommendation_authorizes_deployment'] ?? true) === false
            && ($recommendationGuardrails['recommendation_authorizes_rollback'] ?? true) === false
            && ($recommendationGuardrails['recommendation_authorizes_clinical_action'] ?? true) === false
            && ($recommendationGuardrails['human_review_required'] ?? false) === true
            && ($recommendationGuardrails['governance_validation_required'] ?? false) === true;

        $checks['recommendation_authority_isolation'] = [
            'passed' => $recommendationAuthorityIsolated,
            'message' =>
                'Human strategic plan decision recommendation intelligence remains advisory and isolated from final governance authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive authority isolation
        |--------------------------------------------------------------------------
        */

        $executiveAuthorityIsolated =
            ($executiveGuardrails['executive_decision_status_is_human_final_decision'] ?? true) === false
            && ($executiveGuardrails['executive_readiness_is_governance_approval'] ?? true) === false
            && ($executiveGuardrails['executive_readiness_is_governance_rejection'] ?? true) === false
            && ($executiveGuardrails['executive_readiness_is_plan_activation'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_records_final_decision'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_resolves_conditions'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_validates_evidence'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_authorizes_deployment'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_authorizes_rollback'] ?? true) === false
            && ($executiveGuardrails['executive_intelligence_authorizes_clinical_action'] ?? true) === false
            && ($executiveGuardrails['human_review_required'] ?? false) === true
            && ($executiveGuardrails['governance_validation_required'] ?? false) === true;

        $checks['executive_authority_isolation'] = [
            'passed' => $executiveAuthorityIsolated,
            'message' =>
                'Executive strategic plan decision intelligence remains informational and isolated from final governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit authority isolation
        |--------------------------------------------------------------------------
        */

        $auditAuthorityIsolated =
            ($auditGuardrails['audit_is_human_final_decision'] ?? true) === false
            && ($auditGuardrails['audit_is_governance_approval'] ?? true) === false
            && ($auditGuardrails['audit_is_governance_rejection'] ?? true) === false
            && ($auditGuardrails['audit_is_plan_activation'] ?? true) === false
            && ($auditGuardrails['audit_records_final_decision'] ?? true) === false
            && ($auditGuardrails['audit_resolves_conditions'] ?? true) === false
            && ($auditGuardrails['audit_resolves_dependencies'] ?? true) === false
            && ($auditGuardrails['audit_validates_evidence'] ?? true) === false
            && ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
            && ($auditGuardrails['audit_is_deployment_authorization'] ?? true) === false
            && ($auditGuardrails['audit_is_rollback_authorization'] ?? true) === false
            && ($auditGuardrails['audit_is_clinical_action_authorization'] ?? true) === false
            && ($auditGuardrails['human_review_required'] ?? false) === true
            && ($auditGuardrails['governance_validation_required'] ?? false) === true;

        $checks['audit_authority_isolation'] = [
            'passed' => $auditAuthorityIsolated,
            'message' =>
                'Strategic plan decision audit remains informational and isolated from final decision and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final human authority reservation
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            ($decisionStateContext['final_human_decision_recorded'] ?? false) === true;

        $finalDecisionAuthorityProtected =
            !$finalHumanDecisionRecorded
            || !empty($decision->reviewed_by);

        $checks['final_human_decision_authority_protected'] = [
            'passed' => $finalDecisionAuthorityProtected,
            'value' => $finalHumanDecisionRecorded,
            'message' =>
                'Final strategic plan decision authority remains reserved for authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation totals
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);

        $passedChecks =
            collect($checks)
                ->where('passed', true)
                ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Critical issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $checkCode => $check) {
            if (($check['passed'] ?? false) === false) {
                $criticalIssues[] = [
                    'check_code' => $checkCode,
                    'message' => $check['message'] ?? 'Validation check failed.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        $decisionReviewReadinessScore =
            (float) ($decisionStateContext['decision_review_readiness_score'] ?? 0);

        $humanDecisionEligibilityScore =
            (float) ($eligibilityContext['human_decision_eligibility_score'] ?? 0);

        $humanDecisionRiskScore =
            (float) ($riskContext['human_decision_risk_score'] ?? 0);

        $blockingConditions =
            (int) ($eligibilitySummary['blocking_conditions'] ?? 0);

        $constrainingConditions =
            (int) ($eligibilitySummary['constraining_conditions'] ?? 0);

        $outstandingEvidence =
            (int) ($eligibilitySummary['outstanding_evidence_items'] ?? 0);

        $decisionBlockingEvidence =
            (int) ($eligibilitySummary['decision_blocking_evidence_items'] ?? 0);

        $dependencyAdjustedFeasibility =
            (float) (
                $conditionEvidence['dependency_context']['dependency_adjusted_feasibility_score']
                ?? $eligibilityRisk['planning_risk_context']['dependency_adjusted_feasibility_score']
                ?? 0
            );

        $planningReadinessScore =
            (float) (
                $eligibilityRisk['planning_risk_context']['planning_readiness_score']
                ?? 0
            );

        $executiveDecisionScore =
            (float) ($executiveContext['executive_strategic_plan_decision_score'] ?? 0);

        if ($decisionReviewReadinessScore < 50) {
            $warnings[] =
                "Strategic plan decision review readiness remains limited at score {$decisionReviewReadinessScore}.";
        }

        if ($humanDecisionEligibilityScore < 50) {
            $warnings[] =
                "Human strategic plan decision eligibility remains limited at score {$humanDecisionEligibilityScore}.";
        }

        if ($blockingConditions > 0) {
            $warnings[] =
                "{$blockingConditions} blocking human-decision condition(s) remain active.";
        }

        if ($constrainingConditions > 0) {
            $warnings[] =
                "{$constrainingConditions} constraining human-decision condition(s) remain active.";
        }

        if ($outstandingEvidence > 0) {
            $warnings[] =
                "{$outstandingEvidence} strategic plan decision evidence requirement(s) remain outstanding.";
        }

        if ($decisionBlockingEvidence > 0) {
            $warnings[] =
                "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted strategic plan approval.";
        }

        if ($dependencyAdjustedFeasibility < 50) {
            $warnings[] =
                "Dependency-adjusted strategic plan feasibility remains limited at score {$dependencyAdjustedFeasibility}.";
        }

        if ($planningReadinessScore < 50) {
            $warnings[] =
                "Strategic planning readiness remains limited at score {$planningReadinessScore}.";
        }

        if (
            ($riskContext['human_decision_risk_level'] ?? null)
            === 'HIGH_HUMAN_DECISION_RISK'
        ) {
            $warnings[] =
                "Human strategic plan decision risk remains HIGH_HUMAN_DECISION_RISK with score {$humanDecisionRiskScore}.";
        }

        if (
            ($executiveContext['management_escalation_recommended'] ?? false)
            === true
        ) {
            $warnings[] =
                'Management escalation remains recommended for current strategic plan decision conditions.';
        }

        if ($executiveDecisionScore < 50) {
            $warnings[] =
                "Executive strategic plan decision readiness remains limited at score {$executiveDecisionScore}.";
        }

        /*
        |--------------------------------------------------------------------------
        | Closure decision
        |--------------------------------------------------------------------------
        |
        | Warnings do NOT block Step 64 closure.
        |
        | Only architecture / governance-control failures block closure.
        |
        */

        $step64ReadyForClosure =
            $failedChecks === 0;

        if (!$step64ReadyForClosure) {
            $validationStatus = 'FAILED';
        } elseif (count($warnings) > 0) {
            $validationStatus = 'PASSED_WITH_WARNINGS';
        } else {
            $validationStatus = 'PASSED';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 64 AI Governance Strategic Plan Decision Intelligence architecture has been validated.',

            'Current strategic plan decision status is '.
                ($decisionStateContext['decision_status'] ?? 'UNKNOWN').'.',

            'Current prepared strategic plan decision is '.
                ($decisionStateContext['prepared_decision'] ?? 'UNKNOWN').'.',

            'Current strategic decision state is '.
                ($decisionStateContext['strategic_decision_state'] ?? 'UNKNOWN').'.',

            'Current strategic plan decision review readiness is '.
                ($decisionStateContext['decision_review_readiness'] ?? 'UNKNOWN').
                ' with score '.
                ($decisionStateContext['decision_review_readiness_score'] ?? 0).'.',

            'Current human governance review eligibility is '.
                ($eligibilityContext['human_review_eligibility'] ?? 'UNKNOWN').'.',

            'Current human approval eligibility is '.
                ($eligibilityContext['human_approval_eligibility'] ?? 'UNKNOWN').'.',

            'Current human decision eligibility is '.
                ($eligibilityContext['human_decision_eligibility'] ?? 'UNKNOWN').'.',

            'Current human decision risk level is '.
                ($riskContext['human_decision_risk_level'] ?? 'UNKNOWN').
                ' with score '.
                ($riskContext['human_decision_risk_score'] ?? 0).'.',

            "{$blockingConditions} blocking human-decision condition(s) remain active.",

            "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted strategic plan approval.",

            'Current executive strategic plan decision status is '.
                ($executiveContext['executive_strategic_plan_decision_status'] ?? 'UNKNOWN').'.',

            'Final human strategic plan decision recorded is '.
                ($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Strategic plan decision intelligence remains advisory and human governed.',

            'No autonomous strategic plan approval, rejection, activation, condition resolution, evidence validation, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' => $validationStatus,

            'step_64_ready_for_closure' =>
                $step64ReadyForClosure,

            'governance_strategic_plan_decision_mode' =>
                'HUMAN_GOVERNED_STRATEGIC_PLAN_DECISION_INTELLIGENCE',

            'strategic_plan_decision_id' =>
                $decision->id,

            'decision_code' =>
                $decision->decision_code,

            'strategic_plan_id' =>
                $decision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $decision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $decision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $decision->lifecycle_snapshot_id,

            'decision_scope' =>
                $decision->decision_scope,

            'resident_id' =>
                $decision->resident_id,

            'completion_message' =>
                $step64ReadyForClosure
                    ? 'Step 64 AI Governance Strategic Plan Decision Intelligence has passed final validation and is ready for closure.'
                    : 'Step 64 AI Governance Strategic Plan Decision Intelligence contains validation failures and is not ready for closure.',

            'validation_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'warning_count' =>
                    count($warnings),

                'critical_issue_count' =>
                    count($criticalIssues),
            ],

            'checks' => $checks,

            'governance_strategic_plan_decision_context' => [
                'decision_status' =>
                    $decisionStateContext['decision_status'] ?? null,

                'decision_mode' =>
                    $decisionStateContext['decision_mode'] ?? null,

                'prepared_decision' =>
                    $decisionStateContext['prepared_decision'] ?? null,

                'strategic_decision_state' =>
                    $decisionStateContext['strategic_decision_state'] ?? null,

                'decision_review_readiness' =>
                    $decisionStateContext['decision_review_readiness'] ?? null,

                'decision_review_readiness_score' =>
                    $decisionStateContext['decision_review_readiness_score'] ?? null,

                'decision_confidence' =>
                    $decisionStateContext['decision_confidence'] ?? null,

                'human_resolution_readiness' =>
                    $decisionStateContext['human_resolution_readiness'] ?? null,

                'human_review_eligibility' =>
                    $eligibilityContext['human_review_eligibility'] ?? null,

                'human_approval_eligibility' =>
                    $eligibilityContext['human_approval_eligibility'] ?? null,

                'human_decision_eligibility' =>
                    $eligibilityContext['human_decision_eligibility'] ?? null,

                'human_decision_eligibility_score' =>
                    $eligibilityContext['human_decision_eligibility_score'] ?? null,

                'human_decision_readiness' =>
                    $eligibilityContext['human_decision_readiness'] ?? null,

                'human_decision_risk_level' =>
                    $riskContext['human_decision_risk_level'] ?? null,

                'human_decision_risk_score' =>
                    $riskContext['human_decision_risk_score'] ?? null,

                'condition_evidence_status' =>
                    $conditionEvidenceContext['condition_evidence_status'] ?? null,

                'decision_block_status' =>
                    $conditionEvidenceContext['decision_block_status'] ?? null,

                'condition_pressure_score' =>
                    $conditionEvidenceContext['condition_pressure_score'] ?? null,

                'condition_resolution_score' =>
                    $conditionEvidenceContext['condition_resolution_score'] ?? null,

                'decision_evidence_readiness_score' =>
                    $conditionEvidenceContext['decision_evidence_readiness_score'] ?? null,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'outstanding_evidence_items' =>
                    $outstandingEvidence,

                'decision_blocking_evidence_items' =>
                    $decisionBlockingEvidence,

                'recommendation_status' =>
                    $recommendationState['recommendation_status'] ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations'] ?? 0,

                'executive_strategic_plan_decision_status' =>
                    $executiveContext['executive_strategic_plan_decision_status'] ?? null,

                'executive_readiness' =>
                    $executiveContext['executive_readiness'] ?? null,

                'executive_confidence' =>
                    $executiveContext['executive_confidence'] ?? null,

                'executive_strategic_plan_decision_score' =>
                    $executiveContext['executive_strategic_plan_decision_score'] ?? null,

                'management_escalation_recommended' =>
                    $executiveContext['management_escalation_recommended'] ?? false,

                'immediate_escalation_required' =>
                    $executiveContext['immediate_escalation_required'] ?? false,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,
            ],

            'architecture_summary' => [
                '64.1_strategic_plan_decision_registry' => [
                    'status' => 'OPERATIONAL',
                ],

                '64.2_strategic_plan_decision_preparation' => [
                    'status' => 'OPERATIONAL',
                    'strategic_plan_decision_id' => $decision->id,
                    'decision_code' => $decision->decision_code,
                ],

                '64.3_strategic_plan_decision_state_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'strategic_decision_state' =>
                        $decisionStateContext['strategic_decision_state'] ?? null,
                    'decision_review_readiness' =>
                        $decisionStateContext['decision_review_readiness'] ?? null,
                ],

                '64.4_condition_evidence_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'condition_evidence_status' =>
                        $conditionEvidenceContext['condition_evidence_status'] ?? null,
                    'decision_evidence_readiness_score' =>
                        $conditionEvidenceContext['decision_evidence_readiness_score'] ?? null,
                ],

                '64.5_human_decision_eligibility_risk_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'human_decision_eligibility' =>
                        $eligibilityContext['human_decision_eligibility'] ?? null,
                    'human_decision_risk_level' =>
                        $riskContext['human_decision_risk_level'] ?? null,
                ],

                '64.6_human_decision_recommendation_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'recommendation_status' =>
                        $recommendationState['recommendation_status'] ?? null,
                    'total_recommendations' =>
                        $recommendationSummary['total_recommendations'] ?? 0,
                ],

                '64.7_executive_strategic_plan_decision_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'executive_strategic_plan_decision_status' =>
                        $executiveContext['executive_strategic_plan_decision_status'] ?? null,
                    'executive_readiness' =>
                        $executiveContext['executive_readiness'] ?? null,
                    'executive_strategic_plan_decision_score' =>
                        $executiveContext['executive_strategic_plan_decision_score'] ?? null,
                ],

                '64.8_strategic_plan_decision_audit' => [
                    'status' =>
                        $audit['audit_status'] ?? null,

                    'passed_checks' =>
                        $auditSummary['passed_checks'] ?? 0,

                    'failed_checks' =>
                        $auditSummary['failed_checks'] ?? 0,
                ],

                '64.9_final_validation' => [
                    'status' =>
                        $validationStatus,

                    'step_64_ready_for_closure' =>
                        $step64ReadyForClosure,
                ],
            ],

            'warnings' => $warnings,

            'critical_issues' => $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_64_guardrails' => [
                'governance_strategic_plan_decision_intelligence_enabled' => true,

                'strategic_plan_decision_registry_enabled' => true,
                'strategic_plan_decision_preparation_enabled' => true,
                'strategic_plan_decision_state_intelligence_enabled' => true,
                'strategic_plan_decision_condition_evidence_intelligence_enabled' => true,
                'human_decision_eligibility_risk_intelligence_enabled' => true,
                'human_decision_recommendation_intelligence_enabled' => true,
                'executive_strategic_plan_decision_intelligence_enabled' => true,
                'strategic_plan_decision_audit_enabled' => true,

                'autonomous_strategic_plan_decision_enabled' => false,
                'autonomous_strategic_plan_approval_enabled' => false,
                'autonomous_strategic_plan_rejection_enabled' => false,
                'autonomous_strategic_plan_activation_enabled' => false,

                'automatic_condition_resolution_enabled' => false,
                'automatic_dependency_resolution_enabled' => false,
                'automatic_evidence_validation_enabled' => false,

                'automatic_governance_decision' => false,
                'automatic_governance_approval' => false,
                'automatic_governance_rejection' => false,
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

                'final_decision_authority_reserved_for_human_governance' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Step 64 establishes human-governed AI governance strategic plan decision intelligence. The system may prepare structured strategic plan decision packages, evaluate decision state, conditions, evidence requirements, human decision eligibility, decision risk, recommendations, and executive decision conditions for authorized human governance review. It does not autonomously approve, reject, activate, resolve conditions or dependencies, validate evidence, record the final governance decision, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
            ],
        ];
    }
}