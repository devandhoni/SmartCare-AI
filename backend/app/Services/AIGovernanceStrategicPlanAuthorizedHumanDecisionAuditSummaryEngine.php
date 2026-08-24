<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanAuthorizedHumanDecisionAuditSummaryEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $humanDecision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::find($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->first();

        if (!$humanDecision) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_STRATEGIC_PLAN_HUMAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan authorized human decision record is available for audit.',
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

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($humanDecision->id);
        $resolution = $resolutionEngine->analyze($humanDecision->id);
        $eligibilitySafety = $eligibilitySafetyEngine->analyze($humanDecision->id);
        $recommendation = $recommendationEngine->analyze($humanDecision->id);
        $executive = $executiveEngine->analyze($humanDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $humanDecisionState = $state['human_decision_state'] ?? [];

        $conditionContext = $state['condition_context'] ?? [];
        $evidenceContext = $state['evidence_context'] ?? [];
        $authorizationContext = $state['authorization_context'] ?? [];
        $validationContext = $state['validation_context'] ?? [];
        $completionContext = $state['completion_context'] ?? [];

        $resolutionState = $resolution['resolution_state'] ?? [];
        $conditionSummary = $resolution['condition_summary'] ?? [];
        $evidenceSummary = $resolution['evidence_summary'] ?? [];

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

        /*
        |--------------------------------------------------------------------------
        | Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_human_decision_available'] = [
            'passed' => true,
            'message' => 'Authorized human strategic plan decision record is available.',
        ];

        $checks['human_decision_state_intelligence_available'] = [
            'passed' => ($state['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan human decision state intelligence is available.',
        ];

        $checks['condition_evidence_resolution_intelligence_available'] = [
            'passed' => ($resolution['analysis_completed'] ?? false) === true,
            'message' => 'Human decision condition and evidence resolution intelligence is available.',
        ];

        $checks['authorized_human_decision_eligibility_safety_intelligence_available'] = [
            'passed' => ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' => 'Authorized human decision eligibility and safety intelligence is available.',
        ];

        $checks['authorized_human_decision_recommendation_intelligence_available'] = [
            'passed' => ($recommendation['analysis_completed'] ?? false) === true,
            'message' => 'Authorized human strategic plan decision recommendation intelligence is available.',
        ];

        $checks['executive_authorized_human_decision_intelligence_available'] = [
            'passed' => ($executive['analysis_completed'] ?? false) === true,
            'message' => 'Executive authorized human strategic plan decision intelligence is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && ($recommendationState['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Authorized human strategic plan decision governance integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Condition / Evidence Checks
        |--------------------------------------------------------------------------
        */

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? $conditionContext['critical_open_conditions']
                ?? 0
            );

        $checks['no_critical_open_human_decision_condition'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical authorized-human decision condition should remain open.',
        ];

        $criticalOutstandingEvidence =
            (int) (
                $evidenceSummary['critical_outstanding_evidence_items']
                ?? $evidenceContext['critical_outstanding_evidence_items']
                ?? 0
            );

        $checks['no_critical_outstanding_decision_evidence'] = [
            'passed' => $criticalOutstandingEvidence === 0,
            'value' => $criticalOutstandingEvidence,
            'message' => 'No critical strategic plan human-decision evidence requirement should remain outstanding.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Escalation Check
        |--------------------------------------------------------------------------
        */

        $immediateEscalation =
            ($executiveState['immediate_escalation_required'] ?? false) === true;

        $checks['no_immediate_human_decision_escalation_requirement'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' => 'No immediate authorized-human strategic plan decision escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Human Authority Protection
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            ($humanDecisionState['final_human_decision_recorded'] ?? false) === true;

        $decisionMadeByAuthorizedHuman =
            (bool) $humanDecision->decision_made_by_authorized_human;

        $humanAuthorityCoherent =
            !$finalHumanDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($humanDecision->decided_by)
                && !empty($humanDecision->decider_role)
                && !empty($humanDecision->decided_at)
                && !empty($humanDecision->final_human_decision)
            );

        $checks['final_decision_authority_reserved_for_authorized_human'] = [
            'passed' => $humanAuthorityCoherent,
            'value' => $finalHumanDecisionRecorded,
            'message' => 'Final strategic plan decision authority remains reserved for an explicitly identified authorized human governance decision-maker.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_decision_execution_isolation'] = [
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
            'message' => 'Automatic decision, approval, rejection, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
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
            'message' => 'Authorized-human eligibility and safety intelligence remains isolated from approval, rejection, deferral, risk acceptance, validation, and execution authority.',
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
                && ($recommendationGuardrails['recommendation_changes_human_decision_status'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_final_human_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_plan_status'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_action_state'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_priority'] ?? true) === false
                && ($recommendationGuardrails['recommendation_changes_eligibility_record'] ?? true) === false
                && ($recommendationGuardrails['recommendation_resolves_conditions'] ?? true) === false
                && ($recommendationGuardrails['recommendation_resolves_dependencies'] ?? true) === false
                && ($recommendationGuardrails['recommendation_validates_evidence'] ?? true) === false
                && ($recommendationGuardrails['recommendation_completes_governance_validation'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_approval'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_rejection'] ?? true) === false
                && ($recommendationGuardrails['recommendation_priority_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_ai_change'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_deployment'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_rollback'] ?? true) === false
                && ($recommendationGuardrails['recommendation_authorizes_clinical_action'] ?? true) === false
                && ($recommendationGuardrails['recommendation_overrides_human_review'] ?? true) === false
                && ($recommendationGuardrails['recommendation_overrides_governance_validation'] ?? true) === false
                && ($recommendationGuardrails['recommendation_overrides_evidence_requirements'] ?? true) === false
                && ($recommendationGuardrails['automatic_decision_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_approval_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_rejection_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_activation_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_condition_resolution_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_evidence_validation_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_execution_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_change_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_deployment_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_rollback_allowed'] ?? true) === false
                && ($recommendationGuardrails['automatic_clinical_action_allowed'] ?? true) === false
                && ($recommendationGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true
                && ($recommendationGuardrails['human_review_required'] ?? false) === true
                && ($recommendationGuardrails['governance_validation_required'] ?? false) === true,
            'message' => 'Authorized-human decision recommendation intelligence remains advisory and isolated from final governance authority.',
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
            'message' => 'Executive authorized-human decision intelligence remains informational and isolated from final governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Coherence
        |--------------------------------------------------------------------------
        */

        $governanceValidationCompleted =
            (bool) $humanDecision->governance_validation_completed;

        $validationCoherent =
            !$governanceValidationCompleted
            || (
                !empty($humanDecision->validated_by)
                && !empty($humanDecision->validator_role)
                && !empty($humanDecision->validated_at)
            );

        $checks['governance_validation_authority_integrity'] = [
            'passed' => $validationCoherent,
            'value' => $governanceValidationCompleted,
            'message' => 'Completed governance validation must remain attributable to an identified human governance validator.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Totals
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->where('passed', true)
            ->count();

        $failedChecks = $totalChecks - $passedChecks;

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
        | Management Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {
            $managementStatus =
                'AUTHORIZED_HUMAN_DECISION_GOVERNANCE_INTEGRITY_ATTENTION_REQUIRED';
        } elseif (
            $blockingConditions > 0
            || $decisionBlockingEvidence > 0
        ) {
            $managementStatus =
                'ELEVATED_AUTHORIZED_HUMAN_DECISION_GOVERNANCE_WORK_REMAINS';
        } elseif (!$finalHumanDecisionRecorded) {
            $managementStatus =
                'AUTHORIZED_HUMAN_DECISION_PENDING';
        } elseif (!$governanceValidationCompleted) {
            $managementStatus =
                'AUTHORIZED_HUMAN_DECISION_RECORDED_PENDING_GOVERNANCE_VALIDATION';
        } else {
            $managementStatus =
                'AUTHORIZED_HUMAN_DECISION_GOVERNANCE_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            "Authorized human strategic plan decision audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",

            "Authorized human strategic plan decision audit is based on human decision record {$humanDecision->id}.",

            'Current human decision status is '
                .($humanDecisionState['human_decision_status'] ?? 'UNKNOWN').'.',

            'Current prepared strategic plan decision is '
                .($humanDecisionState['prepared_decision'] ?? 'UNKNOWN').'.',

            'Current strategic human decision state is '
                .($humanDecisionState['strategic_human_decision_state'] ?? 'UNKNOWN').'.',

            'Current human decision readiness is '
                .($humanDecisionState['human_decision_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($humanDecisionState['human_decision_readiness_score'] ?? 0).'.',

            "{$blockingConditions} blocking human decision condition(s) remain active.",

            "{$constrainingConditions} constraining human decision condition(s) remain active.",

            "{$outstandingEvidence} strategic plan human decision evidence requirement(s) remain outstanding.",

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

            'Current decision safety status is '
                .($safetyState['decision_safety_status'] ?? 'UNKNOWN')
                .' with score '
                .($safetyState['decision_safety_score'] ?? 0).'.',

            'Current authorized-human decision recommendation status is '
                .($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',

            'Current executive authorized-human decision status is '
                .($executiveState['executive_authorized_human_decision_status'] ?? 'UNKNOWN').'.',

            'Current executive authorized-human decision readiness is '
                .($executiveState['executive_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_authorized_human_decision_score'] ?? 0).'.',

            'Final human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Decision made by authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Authorized human strategic plan decision governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            "Step 65 authorized-human decision integrity controls currently contain {$failedChecks} failure(s).",
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $executive['management_priorities']
            ?? $recommendation['management_priorities']
            ?? $eligibilitySafety['management_priorities']
            ?? $resolution['management_priorities']
            ?? $state['management_priorities']
            ?? [];

        $managementPriorities =
            array_values(array_unique($managementPriorities));

        /*
        |--------------------------------------------------------------------------
        | Return Audit
        |--------------------------------------------------------------------------
        */

        return [
            'audit_available' => true,

            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_FAILURES',

            'strategic_plan_human_decision_id' => $humanDecision->id,
            'human_decision_code' => $humanDecision->human_decision_code,

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

            'management_status' =>
                $managementStatus,

            /*
            |--------------------------------------------------------------------------
            | Audit Summary
            |--------------------------------------------------------------------------
            */

            'audit_summary' => [
                'total_checks' => $totalChecks,
                'passed_checks' => $passedChecks,
                'failed_checks' => $failedChecks,
            ],

            'checks' => $checks,

            /*
            |--------------------------------------------------------------------------
            | Human Decision Summary
            |--------------------------------------------------------------------------
            */

            'human_decision_summary' => [
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

                'decision_risk_level' =>
                    $humanDecisionState['decision_risk_level'] ?? null,

                'decision_risk_score' =>
                    $humanDecisionState['decision_risk_score'] ?? null,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition / Evidence Summary
            |--------------------------------------------------------------------------
            */

            'condition_evidence_summary' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Eligibility / Safety Summary
            |--------------------------------------------------------------------------
            */

            'eligibility_safety_summary' => [
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

                'final_decision_progression_readiness' =>
                    $eligibilityState['final_decision_progression_readiness'] ?? null,

                'decision_safety_status' =>
                    $safetyState['decision_safety_status'] ?? null,

                'decision_safety_level' =>
                    $safetyState['decision_safety_level'] ?? null,

                'decision_safety_score' =>
                    $safetyState['decision_safety_score'] ?? null,

                'governance_integrity_intact' =>
                    $safetyState['governance_integrity_intact'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recommendation Summary
            |--------------------------------------------------------------------------
            */

            'recommendation_summary' => [
                'recommendation_status' =>
                    $recommendationState['recommendation_status'] ?? null,

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

                'top_recommended_human_decision_path' =>
                    $recommendationSummary['top_recommended_human_decision_path'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Summary
            |--------------------------------------------------------------------------
            */

            'executive_summary' => [
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
                    $executiveState['final_human_decision_recorded'] ?? false,

                'governance_validation_completed' =>
                    $executiveState['governance_validation_completed'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Authorization / Validation Summary
            |--------------------------------------------------------------------------
            */

            'authorization_validation_summary' => [
                'decided_by' =>
                    $humanDecision->decided_by,

                'decider_role' =>
                    $humanDecision->decider_role,

                'decided_at' =>
                    $humanDecision->decided_at,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'validated_by' =>
                    $humanDecision->validated_by,

                'validator_role' =>
                    $humanDecision->validator_role,

                'validated_at' =>
                    $humanDecision->validated_at,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,

                'human_decision_completion_score' =>
                    $completionContext['human_decision_completion_score'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Integrity Summary
            |--------------------------------------------------------------------------
            */

            'integrity_summary' => [
                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'final_decision_authority_reserved_for_authorized_human' =>
                    true,

                'automatic_decision_allowed' =>
                    false,

                'automatic_approval_allowed' =>
                    false,

                'automatic_rejection_allowed' =>
                    false,

                'automatic_activation_allowed' =>
                    false,

                'automatic_condition_resolution_allowed' =>
                    false,

                'automatic_evidence_validation_allowed' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,
            ],

            'audit_findings' =>
                $auditFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 65.8 Audit Guardrails
            |--------------------------------------------------------------------------
            */

            'audit_guardrails' => [
                'authorized_human_decision_audit_enabled' => true,

                'audit_is_final_human_decision' => false,
                'audit_makes_governance_decision' => false,
                'audit_records_governance_decision' => false,

                'audit_approves_strategic_plan' => false,
                'audit_rejects_strategic_plan' => false,
                'audit_conditionally_approves_strategic_plan' => false,
                'audit_defers_strategic_plan' => false,
                'audit_accepts_governance_risk' => false,
                'audit_activates_strategic_plan' => false,

                'audit_changes_human_decision_status' => false,
                'audit_changes_final_human_decision' => false,
                'audit_changes_plan_status' => false,
                'audit_changes_action_state' => false,
                'audit_changes_priority' => false,
                'audit_changes_eligibility' => false,

                'audit_resolves_conditions' => false,
                'audit_resolves_dependencies' => false,
                'audit_validates_evidence' => false,
                'audit_completes_governance_validation' => false,

                'audit_is_execution_authorization' => false,
                'audit_is_deployment_authorization' => false,
                'audit_is_rollback_authorization' => false,
                'audit_is_clinical_action_authorization' => false,

                'audit_overrides_human_review' => false,
                'audit_overrides_governance_validation' => false,
                'audit_overrides_evidence_requirements' => false,

                'automatic_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'final_decision_authority_reserved_for_authorized_human' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Strategic plan authorized-human decision audit consolidates Step 65 human decision preparation, human decision state intelligence, condition and evidence resolution intelligence, authorized-human decision eligibility and safety intelligence, advisory decision recommendations, executive decision intelligence, authorization state, governance-validation state, and authority-isolation controls for human governance audit and management review only. The audit does not make or record the final strategic plan decision, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, complete governance validation, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final strategic plan decision authority remains reserved exclusively for authorized human governance.',
            ],
        ];
    }
}