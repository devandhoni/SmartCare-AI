<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanAuthorizedHumanDecisionFinalValidationEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $humanDecision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::find($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->first();

        if (!$humanDecision) {
            return [
                'validation_status' => 'FAILED',
                'step_65_ready_for_closure' => false,
                'message' => 'No AI governance strategic plan authorized human decision record is available for final validation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 65 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanHumanDecisionStateIntelligenceEngine::class
        );

        $resolutionEngine = app(
            AIGovernanceStrategicPlanHumanDecisionConditionEvidenceResolutionIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanAuthorizedHumanDecisionEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanAuthorizedHumanDecisionRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceStrategicPlanExecutiveAuthorizedHumanDecisionIntelligenceEngine::class
        );

        $auditEngine = app(
            AIGovernanceStrategicPlanAuthorizedHumanDecisionAuditSummaryEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Step 65 Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($humanDecision->id);
        $resolution = $resolutionEngine->analyze($humanDecision->id);
        $eligibilitySafety = $eligibilitySafetyEngine->analyze($humanDecision->id);
        $recommendation = $recommendationEngine->analyze($humanDecision->id);
        $executive = $executiveEngine->analyze($humanDecision->id);
        $audit = $auditEngine->analyze($humanDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $humanDecisionState =
            $state['human_decision_state'] ?? [];

        $conditionContext =
            $state['condition_context'] ?? [];

        $evidenceContext =
            $state['evidence_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $validationContext =
            $state['validation_context'] ?? [];

        $completionContext =
            $state['completion_context'] ?? [];

        $resolutionState =
            $resolution['resolution_state'] ?? [];

        $conditionSummary =
            $resolution['condition_summary'] ?? [];

        $evidenceSummary =
            $resolution['evidence_summary'] ?? [];

        $eligibilityState =
            $eligibilitySafety['authorized_human_decision_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['human_decision_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveState =
            $executive['executive_authorized_human_decision_state'] ?? [];

        $auditSummary =
            $audit['audit_summary'] ?? [];

        $auditIntegritySummary =
            $audit['integrity_summary'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Guardrail Extraction
        |--------------------------------------------------------------------------
        */

        $stateGuardrails =
            $state['human_decision_state_guardrails'] ?? [];

        $resolutionGuardrails =
            $resolution['condition_evidence_resolution_guardrails'] ?? [];

        $eligibilitySafetyGuardrails =
            $eligibilitySafety['authorized_human_decision_eligibility_safety_guardrails'] ?? [];

        $recommendationGuardrails =
            $recommendation['authorized_human_decision_recommendation_guardrails'] ?? [];

        $executiveGuardrails =
            $executive['executive_authorized_human_decision_guardrails'] ?? [];

        $auditGuardrails =
            $audit['audit_guardrails'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_human_decision_available'] = [
            'passed' => true,
            'message' => 'Authorized human strategic plan decision record is available.',
        ];

        $checks['human_decision_state_intelligence_operational'] = [
            'passed' => ($state['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan human decision state intelligence is operational.',
        ];

        $checks['condition_evidence_resolution_intelligence_operational'] = [
            'passed' => ($resolution['analysis_completed'] ?? false) === true,
            'message' => 'Human decision condition and evidence resolution intelligence is operational.',
        ];

        $checks['authorized_human_decision_eligibility_safety_intelligence_operational'] = [
            'passed' => ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' => 'Authorized human decision eligibility and safety intelligence is operational.',
        ];

        $checks['authorized_human_decision_recommendation_intelligence_operational'] = [
            'passed' => ($recommendation['analysis_completed'] ?? false) === true,
            'message' => 'Authorized human strategic plan decision recommendation intelligence is operational.',
        ];

        $checks['executive_authorized_human_decision_intelligence_operational'] = [
            'passed' => ($executive['analysis_completed'] ?? false) === true,
            'message' => 'Executive authorized human strategic plan decision intelligence is operational.',
        ];

        $checks['step_65_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (int) ($auditSummary['failed_checks'] ?? 1) === 0,
            'message' => 'Step 65 authorized-human strategic plan decision audit completed without integrity failures.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && ($auditIntegritySummary['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Authorized human strategic plan decision governance integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Issue Checks
        |--------------------------------------------------------------------------
        */

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? $conditionContext['critical_open_conditions']
                ?? 0
            );

        $checks['critical_human_decision_condition_absent'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical authorized-human strategic plan decision condition remains open.',
        ];

        $criticalOutstandingEvidence =
            (int) (
                $evidenceSummary['critical_outstanding_evidence_items']
                ?? $evidenceContext['critical_outstanding_evidence_items']
                ?? 0
            );

        $checks['critical_outstanding_decision_evidence_absent'] = [
            'passed' => $criticalOutstandingEvidence === 0,
            'value' => $criticalOutstandingEvidence,
            'message' => 'No critical authorized-human strategic plan decision evidence requirement remains outstanding.',
        ];

        $immediateEscalation =
            ($executiveState['immediate_escalation_required'] ?? false) === true;

        $checks['immediate_escalation_absent'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' => 'No immediate authorized-human strategic plan decision governance escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_authority_isolation'] = [
            'passed' =>
                !$humanDecision->automatic_decision_allowed
                && !$humanDecision->automatic_approval_allowed
                && !$humanDecision->automatic_rejection_allowed
                && !$humanDecision->automatic_activation_allowed
                && !$humanDecision->automatic_condition_resolution_allowed
                && !$humanDecision->automatic_evidence_validation_allowed
                && !$humanDecision->automatic_execution_allowed
                && !$humanDecision->automatic_change_allowed
                && !$humanDecision->automatic_deployment_allowed
                && !$humanDecision->automatic_rollback_allowed
                && !$humanDecision->automatic_clinical_action_allowed,
            'message' => 'Automatic governance decision, approval, rejection, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $humanDecision->human_review_required
                && (bool) $humanDecision->governance_validation_required,
            'message' => 'Human review and governance validation remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | State Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['state_intelligence_is_final_human_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_makes_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_records_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_approves_strategic_plan'] ?? true) === false
                && ($stateGuardrails['state_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($stateGuardrails['state_intelligence_activates_strategic_plan'] ?? true) === false
                && ($stateGuardrails['state_intelligence_authorizes_execution'] ?? true) === false
                && ($stateGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Human decision state intelligence remains informational and isolated from final governance authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Resolution Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['resolution_intelligence_authority_isolation'] = [
            'passed' =>
                ($resolutionGuardrails['resolution_intelligence_is_final_human_decision'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_makes_governance_decision'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_records_governance_decision'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_resolves_conditions'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_resolves_dependencies'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_validates_evidence'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_completes_governance_validation'] ?? true) === false
                && ($resolutionGuardrails['resolution_intelligence_authorizes_execution'] ?? true) === false
                && ($resolutionGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Condition and evidence resolution intelligence remains advisory and isolated from resolution, validation, and final decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilitySafetyGuardrails['eligibility_intelligence_is_final_human_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_makes_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_records_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['review_eligibility_is_governance_approval'] ?? true) === false
                && ($eligibilitySafetyGuardrails['conditional_approval_eligibility_is_governance_approval'] ?? true) === false
                && ($eligibilitySafetyGuardrails['unrestricted_approval_eligibility_is_governance_approval'] ?? true) === false
                && ($eligibilitySafetyGuardrails['rejection_review_eligibility_is_governance_rejection'] ?? true) === false
                && ($eligibilitySafetyGuardrails['risk_acceptance_review_eligibility_accepts_risk'] ?? true) === false
                && ($eligibilitySafetyGuardrails['deferral_eligibility_defers_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_authorizes_execution'] ?? true) === false
                && ($eligibilitySafetyGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Authorized-human eligibility and safety intelligence remains isolated from approval, rejection, deferral, risk acceptance, governance validation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['recommendation_is_final_human_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_makes_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_records_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_approves_strategic_plan'] ?? true) === false
                && ($recommendationGuardrails['recommendation_rejects_strategic_plan'] ?? true) === false
                && ($recommendationGuardrails['recommendation_defers_strategic_plan'] ?? true) === false
                && ($recommendationGuardrails['recommendation_accepts_governance_risk'] ?? true) === false
                && ($recommendationGuardrails['recommendation_activates_strategic_plan'] ?? true) === false
                && ($recommendationGuardrails['recommended_path_is_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['conditional_approval_recommendation_is_approval'] ?? true) === false
                && ($recommendationGuardrails['rejection_review_recommendation_is_rejection'] ?? true) === false
                && ($recommendationGuardrails['deferral_recommendation_is_deferral'] ?? true) === false
                && ($recommendationGuardrails['risk_acceptance_recommendation_accepts_risk'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_deployment'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_rollback'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_clinical_action'] ?? true) === false
                && ($recommendationGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Authorized-human strategic plan decision recommendation intelligence remains advisory and isolated from final governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_final_human_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_records_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_approves_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_defers_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_accepts_governance_risk'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_activates_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Executive authorized-human strategic plan decision intelligence remains informational and isolated from final governance authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_final_human_decision'] ?? true) === false
                && ($auditGuardrails['audit_makes_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_records_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_approves_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_rejects_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_conditionally_approves_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_defers_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_accepts_governance_risk'] ?? true) === false
                && ($auditGuardrails['audit_activates_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
                && ($auditGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Authorized-human strategic plan decision audit remains informational and isolated from final governance decision and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Human Decision Authority Integrity
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            ($humanDecisionState['final_human_decision_recorded'] ?? false) === true;

        $decisionMadeByAuthorizedHuman =
            (bool) $humanDecision->decision_made_by_authorized_human;

        $finalHumanAuthorityProtected =
            !$finalHumanDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($humanDecision->decided_by)
                && !empty($humanDecision->decider_role)
                && !empty($humanDecision->decided_at)
                && !empty($humanDecision->final_human_decision)
            );

        $checks['final_human_decision_authority_protected'] = [
            'passed' => $finalHumanAuthorityProtected,
            'value' => $finalHumanDecisionRecorded,
            'message' => 'Final strategic plan decision authority remains reserved for an explicitly identified authorized human governance decision-maker.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Authority Integrity
        |--------------------------------------------------------------------------
        */

        $governanceValidationCompleted =
            (bool) $humanDecision->governance_validation_completed;

        $governanceValidationAuthorityProtected =
            !$governanceValidationCompleted
            || (
                !empty($humanDecision->validated_by)
                && !empty($humanDecision->validator_role)
                && !empty($humanDecision->validated_at)
            );

        $checks['governance_validation_authority_protected'] = [
            'passed' => $governanceValidationAuthorityProtected,
            'value' => $governanceValidationCompleted,
            'message' => 'Completed strategic plan governance validation remains attributable to an identified human governance validator.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation Totals
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->where('passed', true)
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Current Governance Workload
        |--------------------------------------------------------------------------
        */

        $blockingConditions =
            (int) (
                $conditionSummary['blocking_conditions']
                ?? $conditionContext['blocking_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionSummary['constraining_conditions']
                ?? $conditionContext['constraining_conditions']
                ?? 0
            );

        $outstandingEvidence =
            (int) (
                $evidenceSummary['outstanding_evidence_items']
                ?? $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

        $decisionBlockingEvidence =
            (int) (
                $evidenceSummary['decision_blocking_evidence_items']
                ?? $evidenceContext['decision_blocking_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        $humanDecisionReadinessScore =
            (float) ($humanDecisionState['human_decision_readiness_score'] ?? 0);

        if ($humanDecisionReadinessScore < 50) {
            $warnings[] =
                "Authorized human strategic plan decision readiness remains limited at score {$humanDecisionReadinessScore}.";
        }

        if ($blockingConditions > 0) {
            $warnings[] =
                "{$blockingConditions} blocking authorized-human decision condition(s) remain active.";
        }

        if ($constrainingConditions > 0) {
            $warnings[] =
                "{$constrainingConditions} constraining authorized-human decision condition(s) remain active.";
        }

        if ($outstandingEvidence > 0) {
            $warnings[] =
                "{$outstandingEvidence} strategic plan authorized-human decision evidence requirement(s) remain outstanding.";
        }

        if ($decisionBlockingEvidence > 0) {
            $warnings[] =
                "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted strategic plan approval.";
        }

        $conditionResolutionScore =
            (float) ($resolutionState['condition_resolution_score'] ?? 0);

        if ($conditionResolutionScore < 50) {
            $warnings[] =
                "Authorized-human decision condition resolution remains limited at score {$conditionResolutionScore}.";
        }

        $evidenceResolutionScore =
            (float) ($resolutionState['evidence_resolution_score'] ?? 0);

        if ($evidenceResolutionScore < 50) {
            $warnings[] =
                "Authorized-human decision evidence resolution remains limited at score {$evidenceResolutionScore}.";
        }

        $combinedResolutionScore =
            (float) ($resolutionState['combined_resolution_score'] ?? 0);

        if ($combinedResolutionScore < 50) {
            $warnings[] =
                "Combined authorized-human condition and evidence resolution remains limited at score {$combinedResolutionScore}.";
        }

        $decisionRiskLevel =
            $humanDecisionState['decision_risk_level']
            ?? 'UNKNOWN';

        $decisionRiskScore =
            (float) ($humanDecisionState['decision_risk_score'] ?? 0);

        if (in_array($decisionRiskLevel, [
            'HIGH_HUMAN_DECISION_RISK',
            'CRITICAL_HUMAN_DECISION_RISK',
        ], true)) {
            $warnings[] =
                "Authorized-human strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.";
        }

        $unrestrictedApprovalEligibility =
            $eligibilityState['unrestricted_approval_eligibility']
            ?? 'UNKNOWN';

        if ($unrestrictedApprovalEligibility !== 'ELIGIBLE_FOR_UNRESTRICTED_APPROVAL') {
            $warnings[] =
                "Unrestricted strategic plan approval remains unavailable under current governance conditions.";
        }

        $governanceValidationEligibility =
            $eligibilityState['governance_validation_eligibility']
            ?? 'UNKNOWN';

        if ($governanceValidationEligibility !== 'ELIGIBLE_FOR_GOVERNANCE_VALIDATION') {
            $warnings[] =
                "Authorized-human strategic plan governance validation is not yet eligible under current decision conditions.";
        }

        $executiveScore =
            (float) ($executiveState['executive_authorized_human_decision_score'] ?? 0);

        if ($executiveScore < 50) {
            $warnings[] =
                "Executive authorized-human strategic plan decision readiness remains limited at score {$executiveScore}.";
        }

        if (
            ($executiveState['management_escalation_recommended'] ?? false)
            === true
        ) {
            $warnings[] =
                'Management escalation remains recommended for current authorized-human strategic plan decision conditions.';
        }

        if (!$finalHumanDecisionRecorded) {
            $warnings[] =
                'Final authorized human strategic plan decision has not yet been recorded.';
        }

        if (!$governanceValidationCompleted) {
            $warnings[] =
                'Strategic plan governance validation has not yet been completed.';
        }

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $checkCode => $check) {
            if (($check['passed'] ?? false) === false) {
                $criticalIssues[] = [
                    'check_code' => $checkCode,
                    'message' => $check['message'] ?? 'Final validation check failed.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Closure Readiness
        |--------------------------------------------------------------------------
        */

        $step65ReadyForClosure =
            $failedChecks === 0
            && count($criticalIssues) === 0;

        $validationStatus =
            !$step65ReadyForClosure
                ? 'FAILED'
                : (
                    count($warnings) > 0
                        ? 'PASSED_WITH_WARNINGS'
                        : 'PASSED'
                );

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '65.1_authorized_human_decision_registry' => [
                'status' => 'OPERATIONAL',
            ],

            '65.2_authorized_human_decision_preparation' => [
                'status' => 'OPERATIONAL',
                'strategic_plan_human_decision_id' =>
                    $humanDecision->id,
                'human_decision_code' =>
                    $humanDecision->human_decision_code,
            ],

            '65.3_human_decision_state_intelligence' => [
                'status' => 'OPERATIONAL',
                'strategic_human_decision_state' =>
                    $humanDecisionState['strategic_human_decision_state']
                    ?? null,
                'human_decision_readiness' =>
                    $humanDecisionState['human_decision_readiness']
                    ?? null,
            ],

            '65.4_condition_evidence_resolution_intelligence' => [
                'status' => 'OPERATIONAL',
                'decision_resolution_status' =>
                    $resolutionState['decision_resolution_status']
                    ?? null,
                'combined_resolution_score' =>
                    $resolutionState['combined_resolution_score']
                    ?? null,
            ],

            '65.5_authorized_human_decision_eligibility_safety_intelligence' => [
                'status' => 'OPERATIONAL',
                'unrestricted_approval_eligibility' =>
                    $eligibilityState['unrestricted_approval_eligibility']
                    ?? null,
                'decision_safety_status' =>
                    $safetyState['decision_safety_status']
                    ?? null,
                'decision_safety_score' =>
                    $safetyState['decision_safety_score']
                    ?? null,
            ],

            '65.6_authorized_human_decision_recommendation_intelligence' => [
                'status' => 'OPERATIONAL',
                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,
                'total_recommendations' =>
                    $recommendationSummary['total_recommendations']
                    ?? 0,
                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code']
                    ?? null,
            ],

            '65.7_executive_authorized_human_decision_intelligence' => [
                'status' => 'OPERATIONAL',
                'executive_authorized_human_decision_status' =>
                    $executiveState['executive_authorized_human_decision_status']
                    ?? null,
                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? null,
                'executive_authorized_human_decision_score' =>
                    $executiveState['executive_authorized_human_decision_score']
                    ?? null,
            ],

            '65.8_authorized_human_decision_audit' => [
                'status' =>
                    $audit['audit_status']
                    ?? 'UNKNOWN',
                'passed_checks' =>
                    $auditSummary['passed_checks']
                    ?? 0,
                'failed_checks' =>
                    $auditSummary['failed_checks']
                    ?? 0,
            ],

            '65.9_final_validation' => [
                'status' => $validationStatus,
                'step_65_ready_for_closure' =>
                    $step65ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 65 AI Governance Authorized Human Strategic Plan Decision Intelligence architecture has been validated.',

            'Current authorized-human strategic plan decision status is '
                .($humanDecisionState['human_decision_status'] ?? 'UNKNOWN').'.',

            'Current prepared strategic plan decision is '
                .($humanDecisionState['prepared_decision'] ?? 'UNKNOWN').'.',

            'Current strategic human decision state is '
                .($humanDecisionState['strategic_human_decision_state'] ?? 'UNKNOWN').'.',

            'Current authorized-human strategic plan decision readiness is '
                .($humanDecisionState['human_decision_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($humanDecisionState['human_decision_readiness_score'] ?? 0).'.',

            "{$blockingConditions} blocking authorized-human decision condition(s) remain active.",

            "{$constrainingConditions} constraining authorized-human decision condition(s) remain active.",

            "{$outstandingEvidence} strategic plan authorized-human decision evidence requirement(s) remain outstanding.",

            "{$decisionBlockingEvidence} outstanding evidence requirement(s) currently block unrestricted approval.",

            'Current condition resolution score is '
                .($resolutionState['condition_resolution_score'] ?? 0).'.',

            'Current evidence resolution score is '
                .($resolutionState['evidence_resolution_score'] ?? 0).'.',

            'Current combined condition/evidence resolution score is '
                .($resolutionState['combined_resolution_score'] ?? 0).'.',

            'Current unrestricted approval eligibility is '
                .($eligibilityState['unrestricted_approval_eligibility'] ?? 'UNKNOWN').'.',

            'Current conditional approval eligibility is '
                .($eligibilityState['conditional_approval_eligibility'] ?? 'UNKNOWN').'.',

            'Current governance validation eligibility is '
                .($eligibilityState['governance_validation_eligibility'] ?? 'UNKNOWN').'.',

            'Current authorized-human decision safety status is '
                .($safetyState['decision_safety_status'] ?? 'UNKNOWN')
                .' with score '
                .($safetyState['decision_safety_score'] ?? 0).'.',

            'Current authorized-human recommendation status is '
                .($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',

            'Current executive authorized-human strategic plan decision status is '
                .($executiveState['executive_authorized_human_decision_status'] ?? 'UNKNOWN').'.',

            'Current executive authorized-human strategic plan decision readiness is '
                .($executiveState['executive_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_authorized_human_decision_score'] ?? 0).'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Authorized-human decision intelligence remains advisory, informational, and human governed.',

            'No autonomous strategic plan approval, rejection, conditional approval, deferral, risk acceptance, governance validation, activation, condition resolution, evidence validation, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_65_ready_for_closure' =>
                $step65ReadyForClosure,

            'governance_authorized_human_decision_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_STRATEGIC_PLAN_DECISION_INTELLIGENCE',

            'strategic_plan_human_decision_id' =>
                $humanDecision->id,

            'human_decision_code' =>
                $humanDecision->human_decision_code,

            'strategic_plan_decision_id' =>
                $humanDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $humanDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $humanDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $humanDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $humanDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $humanDecision->decision_scope,

            'resident_id' =>
                $humanDecision->resident_id,

            'completion_message' =>
                $step65ReadyForClosure
                    ? 'Step 65 AI Governance Authorized Human Strategic Plan Decision Intelligence has passed final validation and is ready for closure.'
                    : 'Step 65 AI Governance Authorized Human Strategic Plan Decision Intelligence has not yet passed final validation.',

            /*
            |--------------------------------------------------------------------------
            | Validation Summary
            |--------------------------------------------------------------------------
            */

            'validation_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
                'warning_count' => count($warnings),
                'critical_issue_count' => count($criticalIssues),
            ],

            'checks' =>
                $checks,

            /*
            |--------------------------------------------------------------------------
            | Governance Context
            |--------------------------------------------------------------------------
            */

            'governance_authorized_human_decision_context' => [
                'human_decision_status' =>
                    $humanDecisionState['human_decision_status'] ?? null,

                'human_decision_mode' =>
                    $humanDecisionState['human_decision_mode'] ?? null,

                'prepared_decision' =>
                    $humanDecisionState['prepared_decision'] ?? null,

                'prepared_decision_status' =>
                    $humanDecisionState['prepared_decision_status'] ?? null,

                'final_human_decision' =>
                    $humanDecisionState['final_human_decision'] ?? null,

                'strategic_human_decision_state' =>
                    $humanDecisionState['strategic_human_decision_state'] ?? null,

                'human_decision_readiness' =>
                    $humanDecisionState['human_decision_readiness'] ?? null,

                'human_decision_readiness_score' =>
                    $humanDecisionState['human_decision_readiness_score'] ?? null,

                'human_decision_confidence' =>
                    $humanDecisionState['human_decision_confidence'] ?? null,

                'approval_eligibility' =>
                    $humanDecisionState['approval_eligibility'] ?? null,

                'decision_eligibility_score' =>
                    $humanDecisionState['decision_eligibility_score'] ?? null,

                'decision_risk_level' =>
                    $humanDecisionState['decision_risk_level'] ?? null,

                'decision_risk_score' =>
                    $humanDecisionState['decision_risk_score'] ?? null,

                'decision_resolution_status' =>
                    $resolutionState['decision_resolution_status'] ?? null,

                'condition_resolution_status' =>
                    $resolutionState['condition_resolution_status'] ?? null,

                'evidence_resolution_status' =>
                    $resolutionState['evidence_resolution_status'] ?? null,

                'governance_validation_readiness' =>
                    $resolutionState['governance_validation_readiness'] ?? null,

                'final_decision_progression_readiness' =>
                    $resolutionState['final_decision_progression_readiness'] ?? null,

                'condition_resolution_score' =>
                    $resolutionState['condition_resolution_score'] ?? null,

                'evidence_resolution_score' =>
                    $resolutionState['evidence_resolution_score'] ?? null,

                'combined_resolution_score' =>
                    $resolutionState['combined_resolution_score'] ?? null,

                'resolution_pressure_score' =>
                    $resolutionState['resolution_pressure_score'] ?? null,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'outstanding_evidence_items' =>
                    $outstandingEvidence,

                'decision_blocking_evidence_items' =>
                    $decisionBlockingEvidence,

                'authorized_human_review_eligibility' =>
                    $eligibilityState['authorized_human_review_eligibility'] ?? null,

                'unrestricted_approval_eligibility' =>
                    $eligibilityState['unrestricted_approval_eligibility'] ?? null,

                'conditional_approval_eligibility' =>
                    $eligibilityState['conditional_approval_eligibility'] ?? null,

                'request_more_evidence_eligibility' =>
                    $eligibilityState['request_more_evidence_eligibility'] ?? null,

                'deferral_eligibility' =>
                    $eligibilityState['deferral_eligibility'] ?? null,

                'rejection_review_eligibility' =>
                    $eligibilityState['rejection_review_eligibility'] ?? null,

                'risk_acceptance_review_eligibility' =>
                    $eligibilityState['risk_acceptance_review_eligibility'] ?? null,

                'governance_validation_eligibility' =>
                    $eligibilityState['governance_validation_eligibility'] ?? null,

                'decision_safety_status' =>
                    $safetyState['decision_safety_status'] ?? null,

                'decision_safety_level' =>
                    $safetyState['decision_safety_level'] ?? null,

                'decision_safety_score' =>
                    $safetyState['decision_safety_score'] ?? null,

                'recommendation_status' =>
                    $recommendationState['recommendation_status'] ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations'] ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code'] ?? null,

                'top_recommended_human_decision_path' =>
                    $recommendationSummary['top_recommended_human_decision_path'] ?? null,

                'executive_authorized_human_decision_status' =>
                    $executiveState['executive_authorized_human_decision_status'] ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness'] ?? null,

                'executive_confidence' =>
                    $executiveState['executive_confidence'] ?? null,

                'executive_authorized_human_decision_score' =>
                    $executiveState['executive_authorized_human_decision_score'] ?? null,

                'management_escalation_recommended' =>
                    $executiveState['management_escalation_recommended'] ?? false,

                'immediate_escalation_required' =>
                    $executiveState['immediate_escalation_required'] ?? false,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,

                'human_decision_completion_score' =>
                    $completionContext['human_decision_completion_score'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Architecture Summary
            |--------------------------------------------------------------------------
            */

            'architecture_summary' =>
                $architectureSummary,

            /*
            |--------------------------------------------------------------------------
            | Validation Findings
            |--------------------------------------------------------------------------
            */

            'warnings' =>
                array_values(array_unique($warnings)),

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            /*
            |--------------------------------------------------------------------------
            | Step 65 Guardrails
            |--------------------------------------------------------------------------
            */

            'step_65_guardrails' => [
                'authorized_human_strategic_plan_decision_intelligence_enabled' => true,

                'authorized_human_decision_registry_enabled' => true,
                'authorized_human_decision_preparation_enabled' => true,
                'human_decision_state_intelligence_enabled' => true,
                'human_decision_condition_evidence_resolution_intelligence_enabled' => true,
                'authorized_human_decision_eligibility_safety_intelligence_enabled' => true,
                'authorized_human_decision_recommendation_intelligence_enabled' => true,
                'executive_authorized_human_decision_intelligence_enabled' => true,
                'authorized_human_decision_audit_enabled' => true,

                'autonomous_strategic_plan_decision_enabled' => false,
                'autonomous_strategic_plan_approval_enabled' => false,
                'autonomous_strategic_plan_rejection_enabled' => false,
                'autonomous_strategic_plan_conditional_approval_enabled' => false,
                'autonomous_strategic_plan_deferral_enabled' => false,
                'autonomous_governance_risk_acceptance_enabled' => false,
                'autonomous_strategic_plan_activation_enabled' => false,

                'automatic_condition_resolution_enabled' => false,
                'automatic_dependency_resolution_enabled' => false,
                'automatic_evidence_validation_enabled' => false,
                'automatic_governance_validation_enabled' => false,

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

                'final_decision_authority_reserved_for_authorized_human' => true,

                'governance_validation_authority_reserved_for_human_governance' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Step 65 establishes authorized-human-governed strategic plan decision intelligence. The system may prepare an authorized human decision record, evaluate decision state, unresolved conditions, evidence requirements, condition and evidence resolution, decision-path eligibility, safety restrictions, recommendations, executive decision conditions, authorization state, and governance-validation state. It does not autonomously approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, complete governance validation, record the final governance decision, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
            ],
        ];
    }
}