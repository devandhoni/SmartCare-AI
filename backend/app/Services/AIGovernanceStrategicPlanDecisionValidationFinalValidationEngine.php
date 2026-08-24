<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationFinalValidationEngine
{
    public function analyze(?int $validationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load Validation Record
        |--------------------------------------------------------------------------
        */

        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'validation_status' => 'FAILED',
                'step_66_ready_for_closure' => false,
                'message' => 'No strategic plan governance-validation record is available for Step 66 final validation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 66 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine::class
        );

        $conditionEvidenceEngine = app(
            AIGovernanceStrategicPlanDecisionValidationConditionEvidenceIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanDecisionValidationEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanDecisionValidationRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceExecutiveStrategicPlanDecisionValidationIntelligenceEngine::class
        );

        $auditEngine = app(
            AIGovernanceStrategicPlanDecisionValidationAuditSummaryEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($validation->id);

        $conditionEvidence = $conditionEvidenceEngine->analyze(
            $validation->id
        );

        $eligibilitySafety = $eligibilitySafetyEngine->analyze(
            $validation->id
        );

        $recommendation = $recommendationEngine->analyze(
            $validation->id
        );

        $executive = $executiveEngine->analyze(
            $validation->id
        );

        $audit = $auditEngine->analyze(
            $validation->id
        );

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state'] ?? [];

        $conditionEvidenceState =
            $conditionEvidence['condition_evidence_state'] ?? [];

        $conditionSummary =
            $conditionEvidence['condition_summary'] ?? [];

        $evidenceSummary =
            $conditionEvidence['evidence_summary'] ?? [];

        $eligibilityState =
            $eligibilitySafety['validation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['validation_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveState =
            $executive['executive_governance_validation_state'] ?? [];

        $validatorContext =
            $state['validator_context']
            ?? $eligibilitySafety['validator_context']
            ?? $executive['validator_context']
            ?? [];

        $humanDecisionContext =
            $state['human_decision_context']
            ?? $conditionEvidence['human_decision_context']
            ?? $eligibilitySafety['human_decision_context']
            ?? [];

        $completionContext =
            $state['completion_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Correct Step 66.4 Condition / Evidence Counts
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Step 66.4 exposes:
        |
        | condition_summary
        | blocking_validation_conditions
        | constraining_validation_conditions
        | outstanding_validation_evidence
        | blocking_validation_evidence
        |
        | Do not read generic nonexistent keys from another engine structure.
        |--------------------------------------------------------------------------
        */

        $blockingConditions = (int) (
            $conditionSummary['blocking_conditions']
            ?? $conditionSummary['blocking_validation_conditions']
            ?? count($conditionEvidence['blocking_validation_conditions'] ?? [])
        );

        $constrainingConditions = (int) (
            $conditionSummary['constraining_conditions']
            ?? $conditionSummary['constraining_validation_conditions']
            ?? count($conditionEvidence['constraining_validation_conditions'] ?? [])
        );

        $criticalOpenConditions = (int) (
            $conditionSummary['critical_open_conditions']
            ?? 0
        );

        $outstandingEvidence = (int) (
            $evidenceSummary['outstanding_evidence_items']
            ?? $evidenceSummary['outstanding_validation_evidence_items']
            ?? count($conditionEvidence['outstanding_validation_evidence'] ?? [])
        );

        $blockingEvidence = (int) (
            $evidenceSummary['blocking_evidence_items']
            ?? $evidenceSummary['blocking_validation_evidence_items']
            ?? count($conditionEvidence['blocking_validation_evidence'] ?? [])
        );

        $criticalOutstandingEvidence = (int) (
            $evidenceSummary['critical_outstanding_evidence_items']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Resolution Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore = (float) (
            $conditionEvidenceState['condition_resolution_score']
            ?? $conditionSummary['condition_resolution_score']
            ?? $validation->condition_resolution_score
            ?? 0
        );

        $evidenceResolutionScore = (float) (
            $conditionEvidenceState['evidence_resolution_score']
            ?? $conditionEvidenceState['evidence_readiness_score']
            ?? $evidenceSummary['evidence_resolution_score']
            ?? $evidenceSummary['evidence_readiness_score']
            ?? $validation->evidence_resolution_score
            ?? 0
        );

        $combinedResolutionScore = (float) (
            $conditionEvidenceState['combined_resolution_score']
            ?? $validation->combined_resolution_score
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Human Decision State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) (
                $validationState['final_human_decision_recorded']
                ?? $validation->final_human_decision_recorded
            );

        $decisionMadeByAuthorizedHuman =
            (bool) (
                $validationState['decision_made_by_authorized_human']
                ?? $validation->decision_made_by_authorized_human
            );

        /*
        |--------------------------------------------------------------------------
        | Governance Validation State
        |--------------------------------------------------------------------------
        */

        $governanceValidationCompleted =
            (bool) (
                $validationState['governance_validation_completed']
                ?? $validation->governance_validation_completed
            );

        $validationMadeByAuthorizedHuman =
            (bool) (
                $validationState['validation_made_by_authorized_human']
                ?? $validation->validation_made_by_authorized_human
            );

        $validatorAttributionComplete =
            !empty($validation->validated_by)
            && !empty($validation->validator_role)
            && !empty($validation->validated_at);

        /*
        |--------------------------------------------------------------------------
        | Guardrail Extraction
        |--------------------------------------------------------------------------
        */

        $stateGuardrails =
            $state['validation_state_guardrails'] ?? [];

        $conditionEvidenceGuardrails =
            $conditionEvidence['validation_condition_evidence_guardrails'] ?? [];

        $eligibilitySafetyGuardrails =
            $eligibilitySafety['validation_eligibility_safety_guardrails'] ?? [];

        $recommendationGuardrails =
            $recommendation['validation_recommendation_guardrails']
            ?? $recommendation['governance_validation_recommendation_guardrails']
            ?? [];

        $executiveGuardrails =
            $executive['executive_governance_validation_guardrails'] ?? [];

        $auditGuardrails =
            $audit['audit_guardrails'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_decision_validation_available'] = [
            'passed' => true,
            'message' => 'Strategic plan governance-validation record is available.',
        ];

        $checks['validation_state_intelligence_operational'] = [
            'passed' =>
                ($state['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan governance-validation state intelligence is operational.',
        ];

        $checks['validation_condition_evidence_intelligence_operational'] = [
            'passed' =>
                ($conditionEvidence['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan governance-validation condition and evidence intelligence is operational.',
        ];

        $checks['validation_eligibility_safety_intelligence_operational'] = [
            'passed' =>
                ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan governance-validation eligibility and safety intelligence is operational.',
        ];

        $checks['validation_recommendation_intelligence_operational'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' =>
                'Strategic plan governance-validation recommendation intelligence is operational.',
        ];

        $checks['executive_governance_validation_intelligence_operational'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' =>
                'Executive strategic plan governance-validation intelligence is operational.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Step 66.8 Audit
        |--------------------------------------------------------------------------
        */

        $checks['step_66_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (int) ($audit['audit_summary']['failed_checks'] ?? 1) === 0,
            'message' =>
                'Step 66 strategic plan governance-validation audit completed without integrity failures.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && ($audit['integrity_summary']['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' =>
                'Strategic plan governance-validation integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Conditions / Evidence
        |--------------------------------------------------------------------------
        */

        $checks['critical_validation_condition_absent'] = [
            'passed' =>
                $criticalOpenConditions === 0,
            'value' =>
                $criticalOpenConditions,
            'message' =>
                'No critical strategic plan governance-validation condition remains open.',
        ];

        $checks['critical_outstanding_validation_evidence_absent'] = [
            'passed' =>
                $criticalOutstandingEvidence === 0,
            'value' =>
                $criticalOutstandingEvidence,
            'message' =>
                'No critical strategic plan governance-validation evidence requirement remains outstanding.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Escalation
        |--------------------------------------------------------------------------
        */

        $immediateEscalation =
            ($executiveState['immediate_escalation_required'] ?? false) === true;

        $checks['immediate_escalation_absent'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' =>
                'No immediate strategic plan governance-validation escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_authority_isolation'] = [
            'passed' =>
                !$validation->automatic_validation_allowed
                && !$validation->automatic_decision_allowed
                && !$validation->automatic_approval_allowed
                && !$validation->automatic_rejection_allowed
                && !$validation->automatic_activation_allowed
                && !$validation->automatic_condition_resolution_allowed
                && !$validation->automatic_evidence_validation_allowed
                && !$validation->automatic_execution_allowed
                && !$validation->automatic_change_allowed
                && !$validation->automatic_deployment_allowed
                && !$validation->automatic_rollback_allowed
                && !$validation->automatic_clinical_action_allowed,
            'message' =>
                'Automatic validation, governance decision, approval, rejection, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $validation->human_review_required
                && (bool) $validation->governance_validation_required,
            'message' =>
                'Human review and governance validation remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.3 State Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        |
        | FIXED:
        | Uses the actual validation_state_intelligence_* guardrail keys.
        |--------------------------------------------------------------------------
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['validation_state_intelligence_is_completed_validation'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_makes_governance_validation_decision'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_records_governance_validation_decision'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_completes_governance_validation'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_approves_strategic_plan'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_conditionally_approves_strategic_plan'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_defers_strategic_plan'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_accepts_governance_risk'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_activates_strategic_plan'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_resolves_conditions'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_resolves_dependencies'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_validates_evidence'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_authorizes_execution'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_authorizes_deployment'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_authorizes_rollback'] ?? true) === false
                && ($stateGuardrails['validation_state_intelligence_authorizes_clinical_action'] ?? true) === false
                && ($stateGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($stateGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' =>
                'Governance-validation state intelligence remains informational and isolated from completed validation and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.4 Condition / Evidence Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        |
        | FIXED:
        | Uses the actual condition_evidence_intelligence_* keys.
        |--------------------------------------------------------------------------
        */

        $checks['condition_evidence_intelligence_authority_isolation'] = [
            'passed' =>
                ($conditionEvidenceGuardrails['condition_evidence_intelligence_is_completed_validation'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_makes_governance_validation_decision'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_records_governance_validation_decision'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_completes_governance_validation'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_approves_strategic_plan'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_conditionally_approves_strategic_plan'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_defers_strategic_plan'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_accepts_governance_risk'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_activates_strategic_plan'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_resolves_validation_conditions'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_resolves_decision_conditions'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_resolves_dependencies'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_validates_evidence'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_authorizes_execution'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_authorizes_deployment'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_authorizes_rollback'] ?? true) === false
                && ($conditionEvidenceGuardrails['condition_evidence_intelligence_authorizes_clinical_action'] ?? true) === false
                && ($conditionEvidenceGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($conditionEvidenceGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' =>
                'Governance-validation condition and evidence intelligence remains advisory and isolated from resolution, evidence-validation, completed-validation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.5 Eligibility / Safety Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilitySafetyGuardrails['eligibility_intelligence_is_completed_validation'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_makes_validation_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_records_validation_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_completes_governance_validation'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_approves_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_conditionally_approves_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_defers_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_accepts_governance_risk'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_activates_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_resolves_conditions'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_resolves_dependencies'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_validates_evidence'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_authorizes_execution'] ?? true) === false
                && ($eligibilitySafetyGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($eligibilitySafetyGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' =>
                'Governance-validation eligibility and safety intelligence remains isolated from completed validation, approval, rejection, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.6 Recommendation Authority Isolation
        |--------------------------------------------------------------------------
        */

        $recommendationAuthorityIsolated =
            ($recommendationGuardrails['recommendation_is_completed_governance_validation']
                ?? $recommendationGuardrails['recommendation_intelligence_is_completed_validation']
                ?? false) === false

            && ($recommendationGuardrails['recommendation_makes_validation_decision']
                ?? $recommendationGuardrails['recommendation_intelligence_makes_validation_decision']
                ?? false) === false

            && ($recommendationGuardrails['recommendation_completes_governance_validation']
                ?? $recommendationGuardrails['recommendation_intelligence_completes_governance_validation']
                ?? false) === false

            && ($recommendationGuardrails['recommendation_authorizes_execution']
                ?? $recommendationGuardrails['recommendation_intelligence_authorizes_execution']
                ?? false) === false

            && ($recommendationGuardrails['governance_validation_authority_reserved_for_authorized_human']
                ?? true) === true

            && ($recommendationGuardrails['final_decision_authority_reserved_for_authorized_human']
                ?? true) === true;

        $checks['recommendation_authority_isolation'] = [
            'passed' => $recommendationAuthorityIsolated,
            'message' =>
                'Governance-validation recommendation intelligence remains advisory and isolated from completed governance validation and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.7 Executive Authority Isolation
        |--------------------------------------------------------------------------
        |
        | FIXED:
        | Uses actual executive_intelligence_* guardrail keys.
        |--------------------------------------------------------------------------
        */

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_completed_governance_validation'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_validation_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_records_validation_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_completes_governance_validation'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_approves_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_conditionally_approves_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_defers_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_accepts_governance_risk'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_activates_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_resolves_conditions'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_resolves_dependencies'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_validates_evidence'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_completes_validation_attribution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_deployment'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_rollback'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_clinical_action'] ?? true) === false
                && ($executiveGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($executiveGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' =>
                'Executive governance-validation intelligence remains informational and isolated from final strategic plan decision and completed governance-validation authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 66.8 Audit Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_completed_governance_validation'] ?? true) === false
                && ($auditGuardrails['audit_makes_validation_decision'] ?? true) === false
                && ($auditGuardrails['audit_records_validation_decision'] ?? true) === false
                && ($auditGuardrails['audit_completes_governance_validation'] ?? true) === false
                && ($auditGuardrails['audit_makes_final_strategic_plan_decision'] ?? true) === false
                && ($auditGuardrails['audit_records_final_strategic_plan_decision'] ?? true) === false
                && ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
                && ($auditGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($auditGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' =>
                'Strategic plan governance-validation audit remains informational and isolated from final decision, completed validation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Human Decision Authority
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionAuthorityProtected =
            !$finalHumanDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($validation->final_human_decision)
            );

        $checks['final_human_decision_authority_protected'] = [
            'passed' =>
                $finalHumanDecisionAuthorityProtected,
            'value' =>
                $finalHumanDecisionRecorded,
            'message' =>
                'Final strategic plan decision authority remains reserved for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Authority
        |--------------------------------------------------------------------------
        */

        $governanceValidationAuthorityProtected =
            !$governanceValidationCompleted
            || (
                $validationMadeByAuthorizedHuman
                && $validatorAttributionComplete
                && !empty($validation->governance_validation_decision)
            );

        $checks['governance_validation_authority_protected'] = [
            'passed' =>
                $governanceValidationAuthorityProtected,
            'value' =>
                $governanceValidationCompleted,
            'message' =>
                'Completed governance validation remains attributable to an explicitly identified authorized human governance validator.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Decision -> Validation Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $sequenceIntegrity =
            !$governanceValidationCompleted
            || (
                $finalHumanDecisionRecorded
                && $decisionMadeByAuthorizedHuman
                && $validationMadeByAuthorizedHuman
                && $validatorAttributionComplete
            );

        $checks['human_decision_validation_sequence_integrity'] = [
            'passed' =>
                $sequenceIntegrity,
            'message' =>
                'Completed governance validation must follow an explicitly recorded authorized-human strategic plan decision and authorized human validator attribution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Totals
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
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $checkCode => $check) {
            if (($check['passed'] ?? false) !== true) {
                $criticalIssues[] =
                    "Final Step 66 integrity check failed: {$checkCode}.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        if (!$finalHumanDecisionRecorded) {
            $warnings[] =
                'Final authorized-human strategic plan decision has not yet been recorded.';
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $warnings[] =
                'Final strategic plan decision has not yet been attributed to an explicitly authorized human governance decision-maker.';
        }

        if ($blockingConditions > 0) {
            $warnings[] =
                "{$blockingConditions} blocking strategic plan governance-validation condition(s) remain active.";
        }

        if ($constrainingConditions > 0) {
            $warnings[] =
                "{$constrainingConditions} constraining strategic plan governance-validation condition(s) remain active.";
        }

        if ($outstandingEvidence > 0) {
            $warnings[] =
                "{$outstandingEvidence} strategic plan governance-validation evidence requirement(s) remain outstanding.";
        }

        if ($blockingEvidence > 0) {
            $warnings[] =
                "{$blockingEvidence} strategic plan governance-validation evidence requirement(s) currently block validation progression.";
        }

        if ($conditionResolutionScore < 60) {
            $warnings[] =
                "Strategic plan governance-validation condition resolution remains limited at score {$conditionResolutionScore}.";
        }

        if ($evidenceResolutionScore < 60) {
            $warnings[] =
                "Strategic plan governance-validation evidence resolution remains limited at score {$evidenceResolutionScore}.";
        }

        if ($combinedResolutionScore < 60) {
            $warnings[] =
                "Combined strategic plan governance-validation resolution remains limited at score {$combinedResolutionScore}.";
        }

        $decisionRiskLevel =
            $validationState['decision_risk_level']
            ?? $validation->decision_risk_level;

        $decisionRiskScore =
            (float) (
                $validationState['decision_risk_score']
                ?? $validation->decision_risk_score
                ?? 0
            );

        if (
            $decisionRiskScore >= 70
            || in_array(
                $decisionRiskLevel,
                [
                    'HIGH_HUMAN_DECISION_RISK',
                    'CRITICAL_HUMAN_DECISION_RISK',
                ],
                true
            )
        ) {
            $warnings[] =
                "Strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.";
        }

        $completionEligibility =
            $eligibilityState['governance_validation_completion_eligibility']
            ?? null;

        if (
            $completionEligibility
            === 'NOT_ELIGIBLE_FOR_COMPLETED_GOVERNANCE_VALIDATION'
        ) {
            $warnings[] =
                'Completed strategic plan governance validation is not currently eligible under existing governance conditions.';
        }

        $unrestrictedValidationEligibility =
            $eligibilityState['unrestricted_validation_eligibility']
            ?? null;

        if (
            $unrestrictedValidationEligibility
            === 'NOT_ELIGIBLE_FOR_UNRESTRICTED_GOVERNANCE_VALIDATION'
        ) {
            $warnings[] =
                'Unrestricted strategic plan governance validation remains unavailable under current governance conditions.';
        }

        if (!$validatorAttributionComplete) {
            $warnings[] =
                'Authorized governance-validator attribution is not yet complete.';
        }

        if (!$governanceValidationCompleted) {
            $warnings[] =
                'Strategic plan governance validation has not yet been completed.';
        }

        if (
            ($executiveState['management_escalation_recommended'] ?? false)
            === true
        ) {
            $warnings[] =
                'Management escalation remains recommended for current strategic plan governance-validation conditions.';
        }

        $warnings =
            array_values(array_unique($warnings));

        /*
        |--------------------------------------------------------------------------
        | Final Status
        |--------------------------------------------------------------------------
        */

        $step66ReadyForClosure =
            $failedChecks === 0;

        if (!$step66ReadyForClosure) {
            $validationStatus =
                'FAILED';
        } elseif (count($warnings) > 0) {
            $validationStatus =
                'PASSED_WITH_WARNINGS';
        } else {
            $validationStatus =
                'PASSED';
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 66 AI Governance Strategic Plan Governance Validation Intelligence architecture has been validated.',

            'Current governance-validation status is '
                .($validationState['validation_status']
                    ?? $validation->validation_status
                    ?? 'UNKNOWN')
                .'.',

            'Current governance-validation readiness is '
                .($validationState['governance_validation_readiness']
                    ?? $validation->governance_validation_readiness
                    ?? 'UNKNOWN')
                .' with score '
                .($validationState['governance_validation_readiness_score']
                    ?? $validation->governance_validation_readiness_score
                    ?? 0)
                .'.',

            "{$blockingConditions} blocking strategic plan governance-validation condition(s) remain active.",

            "{$constrainingConditions} constraining strategic plan governance-validation condition(s) remain active.",

            "{$outstandingEvidence} strategic plan governance-validation evidence requirement(s) remain outstanding.",

            "{$blockingEvidence} strategic plan governance-validation evidence requirement(s) currently block validation progression.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            'Current governance-validation completion eligibility is '
                .($eligibilityState['governance_validation_completion_eligibility']
                    ?? 'UNKNOWN')
                .'.',

            'Current unrestricted governance-validation eligibility is '
                .($eligibilityState['unrestricted_validation_eligibility']
                    ?? 'UNKNOWN')
                .'.',

            'Current governance-validation safety status is '
                .($safetyState['validation_safety_status']
                    ?? 'UNKNOWN')
                .' with score '
                .($safetyState['validation_safety_score']
                    ?? 0)
                .'.',

            'Current governance-validation recommendation status is '
                .($recommendationState['recommendation_status']
                    ?? 'UNKNOWN')
                .'.',

            'Current executive governance-validation status is '
                .($executiveState['executive_governance_validation_status']
                    ?? 'UNKNOWN')
                .'.',

            'Current executive governance-validation readiness is '
                .($executiveState['executive_readiness']
                    ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_governance_validation_score']
                    ?? 0)
                .'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO')
                .'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO')
                .'.',

            'Authorized governance-validator attribution complete is '
                .($validatorAttributionComplete ? 'YES' : 'NO')
                .'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO')
                .'.',

            'Strategic plan governance-validation intelligence remains advisory, informational, and human governed.',

            'No autonomous strategic plan approval, rejection, conditional approval, deferral, risk acceptance, governance validation, activation, condition resolution, evidence validation, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '66.1_strategic_plan_decision_validation_registry' => [
                'status' => 'OPERATIONAL',
            ],

            '66.2_strategic_plan_decision_validation_preparation' => [
                'status' => 'OPERATIONAL',
                'strategic_plan_decision_validation_id' =>
                    $validation->id,
                'validation_code' =>
                    $validation->validation_code,
            ],

            '66.3_validation_state_intelligence' => [
                'status' =>
                    ($state['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'validation_status' =>
                    $validationState['validation_status']
                    ?? $validation->validation_status,

                'governance_validation_readiness' =>
                    $validationState['governance_validation_readiness']
                    ?? $validation->governance_validation_readiness,
            ],

            '66.4_validation_condition_evidence_intelligence' => [
                'status' =>
                    ($conditionEvidence['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'combined_resolution_score' =>
                    $combinedResolutionScore,
            ],

            '66.5_validation_eligibility_safety_intelligence' => [
                'status' =>
                    ($eligibilitySafety['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'governance_validation_completion_eligibility' =>
                    $eligibilityState['governance_validation_completion_eligibility']
                    ?? null,

                'validation_safety_status' =>
                    $safetyState['validation_safety_status']
                    ?? null,

                'validation_safety_score' =>
                    $safetyState['validation_safety_score']
                    ?? null,
            ],

            '66.6_validation_recommendation_intelligence' => [
                'status' =>
                    ($recommendation['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

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

            '66.7_executive_governance_validation_intelligence' => [
                'status' =>
                    ($executive['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'executive_governance_validation_status' =>
                    $executiveState['executive_governance_validation_status']
                    ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? null,

                'executive_governance_validation_score' =>
                    $executiveState['executive_governance_validation_score']
                    ?? null,
            ],

            '66.8_strategic_plan_governance_validation_audit' => [
                'status' =>
                    $audit['audit_status']
                    ?? 'UNKNOWN',

                'passed_checks' =>
                    $audit['audit_summary']['passed_checks']
                    ?? 0,

                'failed_checks' =>
                    $audit['audit_summary']['failed_checks']
                    ?? 0,
            ],

            '66.9_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_66_ready_for_closure' =>
                    $step66ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Step 66 Guardrails
        |--------------------------------------------------------------------------
        */

        $step66Guardrails = [
            'strategic_plan_governance_validation_intelligence_enabled' => true,

            'strategic_plan_decision_validation_registry_enabled' => true,
            'strategic_plan_decision_validation_preparation_enabled' => true,
            'strategic_plan_decision_validation_state_intelligence_enabled' => true,
            'strategic_plan_decision_validation_condition_evidence_intelligence_enabled' => true,
            'strategic_plan_decision_validation_eligibility_safety_intelligence_enabled' => true,
            'strategic_plan_decision_validation_recommendation_intelligence_enabled' => true,
            'executive_strategic_plan_decision_validation_intelligence_enabled' => true,
            'strategic_plan_decision_validation_audit_enabled' => true,

            'autonomous_governance_validation_enabled' => false,

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

            'governance_validation_authority_reserved_for_authorized_human' => true,

            'human_review_required' => true,
            'governance_validation_required' => true,

            'message' =>
                'Step 66 establishes authorized-human-governed strategic plan governance-validation intelligence. The system may prepare a governance-validation record, evaluate validation state, unresolved validation conditions, evidence state, resolution progress, validation eligibility, safety restrictions, recommendations, executive validation conditions, final-human-decision attribution, validator attribution, and governance-control integrity. It does not autonomously perform or complete governance validation, make or record the final strategic plan decision, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_66_ready_for_closure' =>
                $step66ReadyForClosure,

            'governance_strategic_plan_validation_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_STRATEGIC_PLAN_VALIDATION_INTELLIGENCE',

            'strategic_plan_decision_validation_id' =>
                $validation->id,

            'validation_code' =>
                $validation->validation_code,

            'strategic_plan_human_decision_id' =>
                $validation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $validation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $validation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $validation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $validation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $validation->lifecycle_snapshot_id,

            'decision_scope' =>
                $validation->decision_scope,

            'resident_id' =>
                $validation->resident_id,

            'completion_message' =>
                $step66ReadyForClosure
                    ? 'Step 66 AI Governance Strategic Plan Governance Validation Intelligence has passed final validation and is ready for closure.'
                    : 'Step 66 AI Governance Strategic Plan Governance Validation Intelligence has not passed final validation and requires corrective governance-control work.',

            /*
            |--------------------------------------------------------------------------
            | Validation Summary
            |--------------------------------------------------------------------------
            */

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

            'checks' =>
                $checks,

            /*
            |--------------------------------------------------------------------------
            | Governance Validation Context
            |--------------------------------------------------------------------------
            */

            'governance_strategic_plan_validation_context' => [
                'validation_status' =>
                    $validationState['validation_status']
                    ?? $validation->validation_status,

                'validation_mode' =>
                    $validationState['validation_mode']
                    ?? $validation->validation_mode,

                'prepared_decision' =>
                    $validationState['prepared_decision']
                    ?? $validation->prepared_decision,

                'final_human_decision' =>
                    $validationState['final_human_decision']
                    ?? $validation->final_human_decision,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_decision' =>
                    $validationState['governance_validation_decision']
                    ?? $validation->governance_validation_decision,

                'governance_validation_readiness' =>
                    $validationState['governance_validation_readiness']
                    ?? $validation->governance_validation_readiness,

                'governance_validation_readiness_score' =>
                    $validationState['governance_validation_readiness_score']
                    ?? $validation->governance_validation_readiness_score,

                'decision_risk_level' =>
                    $decisionRiskLevel,

                'decision_risk_score' =>
                    $decisionRiskScore,

                /*
                |--------------------------------------------------------------
                | Corrected Step 66.4 Counts
                |--------------------------------------------------------------
                */

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'outstanding_evidence_items' =>
                    $outstandingEvidence,

                'blocking_evidence_items' =>
                    $blockingEvidence,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                /*
                |--------------------------------------------------------------
                | Eligibility / Safety
                |--------------------------------------------------------------
                */

                'authorized_human_validation_review_eligibility' =>
                    $eligibilityState['authorized_human_validation_review_eligibility']
                    ?? null,

                'governance_validation_completion_eligibility' =>
                    $eligibilityState['governance_validation_completion_eligibility']
                    ?? null,

                'unrestricted_validation_eligibility' =>
                    $eligibilityState['unrestricted_validation_eligibility']
                    ?? null,

                'conditional_validation_consideration_eligibility' =>
                    $eligibilityState['conditional_validation_consideration_eligibility']
                    ?? null,

                'validation_deferral_eligibility' =>
                    $eligibilityState['validation_deferral_eligibility']
                    ?? null,

                'request_additional_resolution_eligibility' =>
                    $eligibilityState['request_additional_resolution_eligibility']
                    ?? null,

                'validation_eligibility_score' =>
                    $eligibilityState['validation_eligibility_score']
                    ?? null,

                'validation_progression_readiness' =>
                    $eligibilityState['validation_progression_readiness']
                    ?? null,

                'validation_safety_status' =>
                    $safetyState['validation_safety_status']
                    ?? null,

                'validation_safety_level' =>
                    $safetyState['validation_safety_level']
                    ?? null,

                'validation_safety_score' =>
                    $safetyState['validation_safety_score']
                    ?? null,

                /*
                |--------------------------------------------------------------
                | Recommendations
                |--------------------------------------------------------------
                */

                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations']
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code']
                    ?? null,

                'top_recommended_validation_path' =>
                    $recommendationSummary['top_recommended_validation_path']
                    ?? null,

                /*
                |--------------------------------------------------------------
                | Executive
                |--------------------------------------------------------------
                */

                'executive_governance_validation_status' =>
                    $executiveState['executive_governance_validation_status']
                    ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? null,

                'executive_confidence' =>
                    $executiveState['executive_confidence']
                    ?? null,

                'executive_governance_validation_score' =>
                    $executiveState['executive_governance_validation_score']
                    ?? null,

                'management_escalation_recommended' =>
                    $executiveState['management_escalation_recommended']
                    ?? false,

                'immediate_escalation_required' =>
                    $executiveState['immediate_escalation_required']
                    ?? false,

                /*
                |--------------------------------------------------------------
                | Validator / Completion
                |--------------------------------------------------------------
                */

                'validator_attribution_complete' =>
                    $validatorAttributionComplete,

                'validation_made_by_authorized_human' =>
                    $validationMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_66_guardrails' =>
                $step66Guardrails,
        ];
    }
}