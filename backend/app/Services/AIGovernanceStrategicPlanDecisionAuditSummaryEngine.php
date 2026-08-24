<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanDecisionAuditSummaryEngine
{
    public function analyze(?int $decisionId = null): array
    {
        $decision = $decisionId
            ? AIGovernanceStrategicPlanDecision::find($decisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$decision) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_STRATEGIC_PLAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision record is available for audit.',
            ];
        }

        $decisionStateEngine = app(AIGovernanceStrategicPlanDecisionStateIntelligenceEngine::class);
        $conditionEvidenceEngine = app(AIGovernanceStrategicPlanDecisionConditionEvidenceIntelligenceEngine::class);
        $eligibilityRiskEngine = app(AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine::class);
        $recommendationEngine = app(AIGovernanceStrategicPlanHumanDecisionRecommendationIntelligenceEngine::class);
        $executiveEngine = app(AIGovernanceExecutiveStrategicPlanDecisionIntelligenceEngine::class);

        $decisionState = $decisionStateEngine->analyze($decision->id);
        $conditionEvidence = $conditionEvidenceEngine->analyze($decision->id);
        $eligibilityRisk = $eligibilityRiskEngine->analyze($decision->id);
        $recommendation = $recommendationEngine->analyze($decision->id);
        $executive = $executiveEngine->analyze($decision->id);

        /*
        |--------------------------------------------------------------------------
        | Guardrail Contexts
        |--------------------------------------------------------------------------
        */

        $eligibilityGuardrails =
            $eligibilityRisk['human_decision_eligibility_risk_guardrails'] ?? [];

        $recommendationGuardrails =
            $recommendation['human_decision_recommendation_guardrails'] ?? [];

        $executiveGuardrails =
            $executive['executive_guardrails'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Audit Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_decision_available'] = [
            'passed' => true,
            'message' => 'Governance strategic plan decision package is available.',
        ];

        $checks['decision_state_intelligence_available'] = [
            'passed' => ($decisionState['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan decision state intelligence is available.',
        ];

        $checks['condition_evidence_intelligence_available'] = [
            'passed' => ($conditionEvidence['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan decision condition and evidence intelligence is available.',
        ];

        $checks['human_decision_eligibility_risk_intelligence_available'] = [
            'passed' => ($eligibilityRisk['analysis_completed'] ?? false) === true,
            'message' => 'Human strategic plan decision eligibility and risk intelligence is available.',
        ];

        $checks['human_decision_recommendation_intelligence_available'] = [
            'passed' => ($recommendation['analysis_completed'] ?? false) === true,
            'message' => 'Human strategic plan decision recommendation intelligence is available.',
        ];

        $checks['executive_strategic_plan_decision_intelligence_available'] = [
            'passed' => ($executive['analysis_completed'] ?? false) === true,
            'message' => 'Executive strategic plan decision intelligence is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($eligibilityRisk['human_decision_risk_state']['governance_integrity_intact'] ?? false) === true
            && ($executive['executive_strategic_plan_decision_state']['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Strategic plan decision governance integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Decision Conditions
        |--------------------------------------------------------------------------
        */

        $criticalOpenConditions =
            (int) ($eligibilityRisk['eligibility_summary']['critical_open_conditions'] ?? 0);

        $checks['no_critical_open_decision_condition'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical human decision condition should remain open.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Evidence Requirements
        |--------------------------------------------------------------------------
        */

        $criticalOutstandingEvidence =
            (int) ($eligibilityRisk['eligibility_summary']['critical_outstanding_evidence_items'] ?? 0);

        $checks['no_critical_outstanding_evidence_requirement'] = [
            'passed' => $criticalOutstandingEvidence === 0,
            'value' => $criticalOutstandingEvidence,
            'message' => 'No critical outstanding decision evidence requirement should remain.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $immediateEscalation =
            ($executive['executive_strategic_plan_decision_state']['immediate_escalation_required'] ?? false) === true;

        $checks['no_immediate_decision_escalation_requirement'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' => 'No immediate strategic plan decision escalation requirement should be active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Final Decision Authority
        |--------------------------------------------------------------------------
        */

        $finalDecisionRecorded =
            ($decisionState['decision_state']['final_human_decision_recorded'] ?? false) === true;

        $checks['final_decision_authority_remains_human'] = [
            'passed' =>
                !$finalDecisionRecorded
                || (
                    !empty($decision->reviewed_by)
                    && !empty($decision->reviewed_at)
                ),
            'value' => $finalDecisionRecorded,
            'message' => 'Final strategic plan decision authority remains reserved for authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_execution_isolation'] = [
            'passed' =>
                !(bool) $decision->automatic_execution_allowed
                && !(bool) $decision->automatic_change_allowed
                && !(bool) $decision->automatic_deployment_allowed
                && !(bool) $decision->automatic_rollback_allowed
                && !(bool) $decision->automatic_clinical_action_allowed,
            'message' => 'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $decision->human_review_required
                && (bool) $decision->governance_validation_required,
            'message' => 'Human review and governance validation remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_authority_isolation'] = [
            'passed' =>
                ($eligibilityGuardrails['human_decision_eligibility_risk_intelligence_enabled'] ?? false) === true
                && ($eligibilityGuardrails['eligibility_is_human_final_decision'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_is_governance_approval'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_is_governance_rejection'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_is_plan_activation'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_changes_decision_status'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_changes_plan_status'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_resolves_conditions'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_resolves_dependencies'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_validates_evidence'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_authorizes_execution'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_authorizes_deployment'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_authorizes_rollback'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_authorizes_clinical_action'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_overrides_human_review'] ?? true) === false
                && ($eligibilityGuardrails['eligibility_analysis_overrides_evidence_requirements'] ?? true) === false
                && ($eligibilityGuardrails['automatic_condition_resolution_allowed'] ?? true) === false
                && ($eligibilityGuardrails['automatic_evidence_validation_allowed'] ?? true) === false
                && ($eligibilityGuardrails['automatic_execution_allowed'] ?? true) === false
                && ($eligibilityGuardrails['human_review_required'] ?? false) === true
                && ($eligibilityGuardrails['governance_validation_required'] ?? false) === true,
            'message' => 'Human decision eligibility intelligence remains isolated from final approval and rejection authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['human_decision_recommendation_intelligence_enabled'] ?? false) === true
                && ($recommendationGuardrails['recommendation_is_human_final_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_is_governance_approval'] ?? true) === false
                && ($recommendationGuardrails['recommendation_is_governance_rejection'] ?? true) === false
                && ($recommendationGuardrails['recommendation_is_plan_activation'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_decision_status'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_plan_status'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_action_state'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_priority'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_eligibility'] ?? true) === false
                && ($recommendationGuardrails['recommendation_resolves_conditions'] ?? true) === false
                && ($recommendationGuardrails['recommendation_resolves_dependencies'] ?? true) === false
                && ($recommendationGuardrails['recommendation_validates_evidence'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_approval'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_rejection'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['eligibility_status_authorizes_approval'] ?? true) === false
                && ($recommendationGuardrails['risk_level_authorizes_approval'] ?? true) === false
                && ($recommendationGuardrails['risk_level_authorizes_rejection'] ?? true) === false
                && ($recommendationGuardrails['high_risk_authorizes_automation'] ?? true) === false
                && ($recommendationGuardrails['blocking_condition_authorizes_automation'] ?? true) === false
                && ($recommendationGuardrails['blocking_evidence_authorizes_automation'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_ai_change'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_deployment'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_rollback'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_clinical_action'] ?? true) === false
                && ($recommendationGuardrails['recommendation_overrides_human_review'] ?? true) === false
                && ($recommendationGuardrails['recommendation_overrides_evidence_requirements'] ?? true) === false
                && ($recommendationGuardrails['automatic_condition_resolution_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_evidence_validation_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_execution_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_change_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_deployment_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_rollback_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_clinical_action_allowed'] ?? true) === false
                && ($recommendationGuardrails['human_review_required'] ?? false) === true
                && ($recommendationGuardrails['governance_validation_required'] ?? false) === true,
            'message' => 'Human decision recommendation intelligence remains advisory and does not become governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_strategic_plan_decision_intelligence_enabled'] ?? false) === true
                && ($executiveGuardrails['executive_decision_status_is_human_final_decision'] ?? true) === false
                && ($executiveGuardrails['executive_readiness_is_governance_approval'] ?? true) === false
                && ($executiveGuardrails['executive_readiness_is_governance_rejection'] ?? true) === false
                && ($executiveGuardrails['executive_readiness_is_plan_activation'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_records_final_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_changes_decision_status'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_changes_plan_status'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_changes_action_state'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_changes_priority'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_changes_eligibility'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_resolves_conditions'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_resolves_dependencies'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_validates_evidence'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_ai_change'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_deployment'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_rollback'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_clinical_action'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_overrides_human_review'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_overrides_evidence_requirements'] ?? true) === false
                && ($executiveGuardrails['automatic_condition_resolution_allowed'] ?? true) === false
                && ($executiveGuardrails['automatic_evidence_validation_allowed'] ?? true) === false
                && ($executiveGuardrails['automatic_execution_allowed'] ?? true) === false
                && ($executiveGuardrails['human_review_required'] ?? false) === true
                && ($executiveGuardrails['governance_validation_required'] ?? false) === true,
            'message' => 'Executive strategic plan decision intelligence remains informational and does not become final governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Totals
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);
        $passedChecks = collect($checks)
            ->filter(fn (array $check) => ($check['passed'] ?? false) === true)
            ->count();

        $failedChecks = $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Intelligence Contexts
        |--------------------------------------------------------------------------
        */

        $decisionStateContext =
            $decisionState['decision_state'] ?? [];

        $conditionEvidenceContext =
            $conditionEvidence['condition_evidence_state'] ?? [];

        $eligibilityContext =
            $eligibilityRisk['human_decision_eligibility_state'] ?? [];

        $decisionRiskContext =
            $eligibilityRisk['human_decision_risk_state'] ?? [];

        $eligibilitySummary =
            $eligibilityRisk['eligibility_summary'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveContext =
            $executive['executive_strategic_plan_decision_state'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Management Status
        |--------------------------------------------------------------------------
        */

        $managementStatus = $failedChecks > 0
            ? 'STRATEGIC_PLAN_DECISION_GOVERNANCE_INTEGRITY_ATTENTION_REQUIRED'
            : (
                ($eligibilitySummary['blocking_conditions'] ?? 0) > 0
                || ($eligibilitySummary['decision_blocking_evidence_items'] ?? 0) > 0
                    ? 'ELEVATED_STRATEGIC_PLAN_DECISION_GOVERNANCE_WORK_REMAINS'
                    : 'STRATEGIC_PLAN_DECISION_GOVERNANCE_CONTROLLED'
            );

        /*
        |--------------------------------------------------------------------------
        | Audit Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            "Strategic plan decision audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
            "Strategic plan decision audit is based on decision record {$decision->id}.",

            'Current decision status is '
                .($decisionStateContext['decision_status'] ?? 'UNKNOWN').'.',

            'Current prepared decision is '
                .($decisionStateContext['prepared_decision'] ?? 'UNKNOWN').'.',

            'Current strategic decision state is '
                .($decisionStateContext['strategic_decision_state'] ?? 'UNKNOWN').'.',

            'Current decision review readiness is '
                .($decisionStateContext['decision_review_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($decisionStateContext['decision_review_readiness_score'] ?? 0)
                .'.',

            'Current human governance review eligibility is '
                .($eligibilityContext['human_review_eligibility'] ?? 'UNKNOWN').'.',

            'Current human approval eligibility is '
                .($eligibilityContext['human_approval_eligibility'] ?? 'UNKNOWN').'.',

            'Current human decision eligibility is '
                .($eligibilityContext['human_decision_eligibility'] ?? 'UNKNOWN').'.',

            'Current human decision eligibility score is '
                .($eligibilityContext['human_decision_eligibility_score'] ?? 0).'.',

            'Current human decision risk level is '
                .($decisionRiskContext['human_decision_risk_level'] ?? 'UNKNOWN')
                .' with score '
                .($decisionRiskContext['human_decision_risk_score'] ?? 0)
                .'.',

            ($eligibilitySummary['blocking_conditions'] ?? 0)
                .' blocking human-decision condition(s) remain active.',

            ($eligibilitySummary['constraining_conditions'] ?? 0)
                .' constraining human-decision condition(s) remain active.',

            ($eligibilitySummary['outstanding_evidence_items'] ?? 0)
                .' evidence requirement(s) remain outstanding.',

            ($eligibilitySummary['decision_blocking_evidence_items'] ?? 0)
                .' outstanding evidence requirement(s) currently block unrestricted approval.',

            'Current executive strategic plan decision status is '
                .($executiveContext['executive_strategic_plan_decision_status'] ?? 'UNKNOWN').'.',

            'Current executive strategic plan decision readiness is '
                .($executiveContext['executive_readiness'] ?? 'UNKNOWN').'.',

            'Current executive strategic plan decision score is '
                .($executiveContext['executive_strategic_plan_decision_score'] ?? 0).'.',

            'Final human decision recorded is '
                .(($executiveContext['final_human_decision_recorded'] ?? false) ? 'YES' : 'NO')
                .'.',

            'Strategic plan decision governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            "Step 64 strategic plan decision integrity controls currently contain {$failedChecks} failure(s).",
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $executive['executive_priorities']
            ?? $recommendation['management_priorities']
            ?? $eligibilityRisk['management_priorities']
            ?? [];

        $managementPriorities = array_values(
            array_unique($managementPriorities)
        );

        /*
        |--------------------------------------------------------------------------
        | Final Audit Result
        |--------------------------------------------------------------------------
        */

        return [
            'audit_available' => true,

            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_FAILURES',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,
            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,
            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'management_status' => $managementStatus,

            'audit_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
            ],

            'checks' => $checks,

            /*
            |--------------------------------------------------------------------------
            | Decision Summary
            |--------------------------------------------------------------------------
            */

            'decision_summary' => [
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

                'final_human_decision_recorded' =>
                    $decisionStateContext['final_human_decision_recorded'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition / Evidence Summary
            |--------------------------------------------------------------------------
            */

            'condition_evidence_summary' => [
                'condition_evidence_status' =>
                    $conditionEvidenceContext['condition_evidence_status'] ?? null,

                'decision_block_status' =>
                    $conditionEvidenceContext['decision_block_status'] ?? null,

                'condition_pressure_score' =>
                    $conditionEvidenceContext['condition_pressure_score'] ?? null,

                'condition_resolution_score' =>
                    $conditionEvidenceContext['condition_resolution_score'] ?? null,

                'evidence_readiness_score' =>
                    $conditionEvidenceContext['evidence_readiness_score'] ?? null,

                'decision_evidence_readiness_score' =>
                    $conditionEvidenceContext['decision_evidence_readiness_score'] ?? null,

                'human_resolution_readiness' =>
                    $conditionEvidenceContext['human_resolution_readiness'] ?? null,

                'blocking_conditions' =>
                    $eligibilitySummary['blocking_conditions'] ?? 0,

                'constraining_conditions' =>
                    $eligibilitySummary['constraining_conditions'] ?? 0,

                'outstanding_evidence_items' =>
                    $eligibilitySummary['outstanding_evidence_items'] ?? 0,

                'decision_blocking_evidence_items' =>
                    $eligibilitySummary['decision_blocking_evidence_items'] ?? 0,
            ],

            /*
            |--------------------------------------------------------------------------
            | Eligibility Summary
            |--------------------------------------------------------------------------
            */

            'eligibility_summary' => [
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

                'eligible_for_human_resolution' =>
                    $eligibilityContext['eligible_for_human_resolution'] ?? false,

                'final_human_decision_recorded' =>
                    $eligibilityContext['final_human_decision_recorded'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Risk Summary
            |--------------------------------------------------------------------------
            */

            'risk_summary' => [
                'human_decision_risk_level' =>
                    $decisionRiskContext['human_decision_risk_level'] ?? null,

                'human_decision_risk_score' =>
                    $decisionRiskContext['human_decision_risk_score'] ?? null,

                'risk_control_status' =>
                    $decisionRiskContext['risk_control_status'] ?? null,

                'governance_integrity_intact' =>
                    $decisionRiskContext['governance_integrity_intact'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recommendation Summary
            |--------------------------------------------------------------------------
            */

            'recommendation_summary' => [
                'recommendation_status' =>
                    $recommendation['recommendation_state']['recommendation_status'] ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations'] ?? 0,

                'critical_recommendations' =>
                    $recommendationSummary['critical_recommendations'] ?? 0,

                'high_recommendations' =>
                    $recommendationSummary['high_recommendations'] ?? 0,

                'moderate_recommendations' =>
                    $recommendationSummary['moderate_recommendations'] ?? 0,

                'advisory_recommendations' =>
                    $recommendationSummary['advisory_recommendations'] ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Summary
            |--------------------------------------------------------------------------
            */

            'executive_summary' => [
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
                    $executiveContext['final_human_decision_recorded'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Integrity Summary
            |--------------------------------------------------------------------------
            */

            'integrity_summary' => [
                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,
            ],

            'audit_findings' => $auditFindings,

            'management_priorities' => $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Audit Guardrails
            |--------------------------------------------------------------------------
            */

            'audit_guardrails' => [
                'strategic_plan_decision_audit_enabled' => true,

                'audit_is_human_final_decision' => false,
                'audit_is_governance_approval' => false,
                'audit_is_governance_rejection' => false,
                'audit_is_plan_activation' => false,

                'audit_records_final_decision' => false,

                'audit_changes_decision_status' => false,
                'audit_changes_plan_status' => false,
                'audit_changes_action_state' => false,
                'audit_changes_priority' => false,
                'audit_changes_eligibility' => false,

                'audit_resolves_conditions' => false,
                'audit_resolves_dependencies' => false,
                'audit_validates_evidence' => false,

                'audit_is_execution_authorization' => false,
                'audit_is_deployment_authorization' => false,
                'audit_is_rollback_authorization' => false,
                'audit_is_clinical_action_authorization' => false,

                'audit_overrides_human_review' => false,
                'audit_overrides_evidence_requirements' => false,

                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Strategic plan decision audit consolidates Step 64 decision preparation, decision state, condition and evidence intelligence, human decision eligibility and risk, decision recommendations, executive decision intelligence, and governance-control integrity for authorized human governance audit and management review only. The audit does not make or record the final governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, activate planning work, change plan or governance action state, alter priority or eligibility, bypass human review or evidence requirements, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}