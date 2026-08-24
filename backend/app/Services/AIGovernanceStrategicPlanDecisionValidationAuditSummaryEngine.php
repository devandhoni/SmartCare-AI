<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationAuditSummaryEngine
{
    public function analyze(?int $validationId = null): array
    {
        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_STRATEGIC_PLAN_DECISION_VALIDATION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision validation record is available for audit.',
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

        $resolutionEngine = app(
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

        /*
        |--------------------------------------------------------------------------
        | Execute Step 66 Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($validation->id);

        $resolution = $resolutionEngine->analyze($validation->id);

        $eligibilitySafety = $eligibilitySafetyEngine->analyze(
            $validation->id
        );

        $recommendation = $recommendationEngine->analyze(
            $validation->id
        );

        $executive = $executiveEngine->analyze(
            $validation->id
        );

        /*
        |--------------------------------------------------------------------------
        | Core Context Extraction
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state']
            ?? $state['governance_validation_state']
            ?? [];

        $conditionContext =
            $resolution['condition_summary']
            ?? $resolution['condition_context']
            ?? $state['condition_context']
            ?? [];

        $evidenceContext =
            $resolution['evidence_summary']
            ?? $resolution['evidence_context']
            ?? $state['evidence_context']
            ?? [];

        $resolutionState =
            $resolution['resolution_state']
            ?? [];

        $humanDecisionContext =
            $state['human_decision_context']
            ?? $eligibilitySafety['human_decision_context']
            ?? [];

        $validatorContext =
            $state['validator_context']
            ?? $eligibilitySafety['validator_context']
            ?? [];

        $eligibilityState =
            $eligibilitySafety['validation_eligibility_state']
            ?? [];

        $safetyState =
            $eligibilitySafety['validation_safety_state']
            ?? [];

        $recommendationState =
            $recommendation['recommendation_state']
            ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary']
            ?? [];

        $executiveState =
            $executive['executive_governance_validation_state']
            ?? [];

        $executiveSummary =
            $executive['executive_summary']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Guardrail Extraction
        |--------------------------------------------------------------------------
        */

        $stateGuardrails =
            $state['validation_state_guardrails']
            ?? $state['governance_validation_state_guardrails']
            ?? [];

        $resolutionGuardrails =
            $resolution['validation_condition_evidence_resolution_guardrails']
            ?? $resolution['condition_evidence_resolution_guardrails']
            ?? [];

        $eligibilitySafetyGuardrails =
            $eligibilitySafety['validation_eligibility_safety_guardrails']
            ?? [];

        $recommendationGuardrails =
            $recommendation['validation_recommendation_guardrails']
            ?? $recommendation['governance_validation_recommendation_guardrails']
            ?? [];

        $executiveGuardrails =
            $executive['executive_governance_validation_guardrails']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Current Validation Authority State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) $validation->final_human_decision_recorded;

        $decisionMadeByAuthorizedHuman =
            (bool) $validation->decision_made_by_authorized_human;

        $validationMadeByAuthorizedHuman =
            (bool) $validation->validation_made_by_authorized_human;

        $governanceValidationCompleted =
            (bool) $validation->governance_validation_completed;

        $validatorAttributionComplete =
            !empty($validation->validated_by)
            && !empty($validation->validator_role)
            && !empty($validation->validated_at);

        /*
        |--------------------------------------------------------------------------
        | Current Condition / Evidence State
        |--------------------------------------------------------------------------
        */

        $blockingConditions =
            (int) (
                $conditionContext['blocking_conditions']
                ?? $executiveSummary['blocking_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionContext['constraining_conditions']
                ?? $executiveSummary['constraining_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionContext['critical_open_conditions']
                ?? 0
            );

        $outstandingEvidence =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? $executiveSummary['outstanding_evidence_items']
                ?? 0
            );

        $blockingEvidence =
            (int) (
                $evidenceContext['blocking_evidence_items']
                ?? $evidenceContext['decision_blocking_evidence_items']
                ?? $executiveSummary['blocking_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidence =
            (int) (
                $evidenceContext['critical_outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Audit Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_decision_validation_available'] = [
            'passed' => true,
            'message' => 'Strategic plan governance-validation record is available.',
        ];

        $checks['validation_state_intelligence_available'] = [
            'passed' =>
                ($state['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan governance-validation state intelligence is available.',
        ];

        $checks['validation_condition_evidence_resolution_intelligence_available'] = [
            'passed' =>
                ($resolution['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan governance-validation condition and evidence resolution intelligence is available.',
        ];

        $checks['validation_eligibility_safety_intelligence_available'] = [
            'passed' =>
                ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan governance-validation eligibility and safety intelligence is available.',
        ];

        $checks['validation_recommendation_intelligence_available'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' => 'Strategic plan governance-validation recommendation intelligence is available.',
        ];

        $checks['executive_governance_validation_intelligence_available'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' => 'Executive strategic plan governance-validation intelligence is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && ($recommendationState['governance_integrity_intact'] ?? false) === true
            && ($executiveState['governance_integrity_intact'] ?? false) === true;

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Strategic plan governance-validation integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Safety Conditions
        |--------------------------------------------------------------------------
        |
        | Ordinary HIGH blocking conditions are governance workload, not an
        | architectural integrity failure. Only CRITICAL unresolved conditions
        | fail this integrity check.
        |
        */

        $checks['no_critical_open_validation_condition'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical strategic plan governance-validation condition should remain open.',
        ];

        $checks['no_critical_outstanding_validation_evidence'] = [
            'passed' => $criticalOutstandingEvidence === 0,
            'value' => $criticalOutstandingEvidence,
            'message' => 'No critical strategic plan governance-validation evidence requirement should remain outstanding.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $immediateEscalation =
            ($executiveState['immediate_escalation_required'] ?? false) === true;

        $checks['no_immediate_validation_escalation_requirement'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' => 'No immediate strategic plan governance-validation escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Strategic Plan Decision Authority
        |--------------------------------------------------------------------------
        |
        | An unrecorded final decision is valid at this stage.
        | If recorded, it must be explicitly attributable to an authorized human.
        |
        */

        $finalDecisionAuthorityCoherent =
            !$finalHumanDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($validation->final_human_decision)
            );

        $checks['final_decision_authority_reserved_for_authorized_human'] = [
            'passed' => $finalDecisionAuthorityCoherent,
            'value' => $finalHumanDecisionRecorded,
            'message' => 'Final strategic plan decision authority remains reserved for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Authority
        |--------------------------------------------------------------------------
        |
        | Incomplete validation is valid.
        | Completed validation must be attributable to an authorized human
        | validator with identity, role and timestamp.
        |
        */

        $validationAuthorityCoherent =
            !$governanceValidationCompleted
            || (
                $validationMadeByAuthorizedHuman
                && $validatorAttributionComplete
                && !empty($validation->governance_validation_decision)
            );

        $checks['governance_validation_authority_reserved_for_authorized_human'] = [
            'passed' => $validationAuthorityCoherent,
            'value' => $governanceValidationCompleted,
            'message' => 'Completed governance validation remains reserved for an explicitly identified authorized human governance validator.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_validation_execution_isolation'] = [
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
            'message' => 'Automatic validation, decision, approval, rejection, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
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
            'message' => 'Human review and governance validation remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation State Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['state_intelligence_is_completed_validation'] ?? false) === false
                && ($stateGuardrails['state_intelligence_makes_validation_decision'] ?? false) === false
                && ($stateGuardrails['state_intelligence_records_validation_decision'] ?? false) === false
                && ($stateGuardrails['state_intelligence_completes_governance_validation'] ?? false) === false
                && ($stateGuardrails['state_intelligence_approves_strategic_plan'] ?? false) === false
                && ($stateGuardrails['state_intelligence_rejects_strategic_plan'] ?? false) === false
                && ($stateGuardrails['state_intelligence_authorizes_execution'] ?? false) === false,
            'message' => 'Governance-validation state intelligence remains informational and isolated from completed validation and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition / Evidence Resolution Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['resolution_intelligence_authority_isolation'] = [
            'passed' =>
                ($resolutionGuardrails['resolution_intelligence_is_completed_validation'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_makes_validation_decision'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_records_validation_decision'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_resolves_conditions'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_resolves_dependencies'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_validates_evidence'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_completes_governance_validation'] ?? false) === false
                && ($resolutionGuardrails['resolution_intelligence_authorizes_execution'] ?? false) === false,
            'message' => 'Governance-validation condition and evidence resolution intelligence remains advisory and isolated from resolution, evidence-validation, completed-validation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Authority Isolation
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
                && ($eligibilitySafetyGuardrails['eligibility_intelligence_authorizes_execution'] ?? true) === false
                && ($eligibilitySafetyGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Governance-validation eligibility and safety intelligence remains isolated from completed validation, approval, rejection, deferral, risk acceptance, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                (
                    ($recommendationGuardrails['recommendation_is_completed_validation'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_is_completed_validation'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_makes_validation_decision'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_makes_validation_decision'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_records_validation_decision'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_records_validation_decision'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_completes_governance_validation'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_completes_governance_validation'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_approves_strategic_plan'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_approves_strategic_plan'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_rejects_strategic_plan'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_rejects_strategic_plan'] ?? null) === false
                )
                && (
                    ($recommendationGuardrails['recommendation_authorizes_execution'] ?? null) === false
                    || ($recommendationGuardrails['recommendation_intelligence_authorizes_execution'] ?? null) === false
                )
                && ($recommendationGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Governance-validation recommendation intelligence remains advisory and isolated from final decision, completed validation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Intelligence Authority Isolation
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
                && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['governance_validation_authority_reserved_for_authorized_human'] ?? false) === true
                && ($executiveGuardrails['final_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Executive governance-validation intelligence remains informational and isolated from final strategic plan decision and completed governance-validation authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validator Attribution Coherence
        |--------------------------------------------------------------------------
        */

        $validatorAttributionCoherent =
            !$governanceValidationCompleted
            || $validatorAttributionComplete;

        $checks['validator_attribution_integrity'] = [
            'passed' => $validatorAttributionCoherent,
            'value' => $validatorAttributionComplete,
            'message' => 'Completed governance validation must remain attributable to an identified human governance validator.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Completed Validation Decision Coherence
        |--------------------------------------------------------------------------
        */

        $validationDecisionCoherent =
            !$governanceValidationCompleted
            || !empty($validation->governance_validation_decision);

        $checks['completed_validation_decision_integrity'] = [
            'passed' => $validationDecisionCoherent,
            'value' => $validation->governance_validation_decision,
            'message' => 'Completed governance validation must include an explicit human governance-validation decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Decision / Validation Sequencing
        |--------------------------------------------------------------------------
        */

        $validationSequenceCoherent =
            !$governanceValidationCompleted
            || (
                $finalHumanDecisionRecorded
                && $decisionMadeByAuthorizedHuman
                && $validationMadeByAuthorizedHuman
            );

        $checks['human_decision_validation_sequence_integrity'] = [
            'passed' => $validationSequenceCoherent,
            'message' => 'Completed governance validation must follow an explicitly recorded authorized-human strategic plan decision and remain attributable to authorized human validation.',
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

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Management Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {

            $managementStatus =
                'STRATEGIC_PLAN_GOVERNANCE_VALIDATION_INTEGRITY_ATTENTION_REQUIRED';

        } elseif (
            !$finalHumanDecisionRecorded
            || !$decisionMadeByAuthorizedHuman
            || $blockingConditions > 0
            || $blockingEvidence > 0
        ) {

            $managementStatus =
                'ELEVATED_STRATEGIC_PLAN_GOVERNANCE_VALIDATION_WORK_REMAINS';

        } elseif (!$validatorAttributionComplete) {

            $managementStatus =
                'STRATEGIC_PLAN_GOVERNANCE_VALIDATION_AWAITING_AUTHORIZED_VALIDATOR';

        } elseif (!$governanceValidationCompleted) {

            $managementStatus =
                'STRATEGIC_PLAN_GOVERNANCE_VALIDATION_PENDING';

        } else {

            $managementStatus =
                'STRATEGIC_PLAN_GOVERNANCE_VALIDATION_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            "Strategic plan governance-validation audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",

            "Strategic plan governance-validation audit is based on validation record {$validation->id}.",

            'Current validation status is '
                .($validationState['validation_status']
                    ?? $validation->validation_status
                    ?? 'UNKNOWN').'.',

            'Current governance-validation readiness is '
                .($validationState['governance_validation_readiness']
                    ?? $validation->governance_validation_readiness
                    ?? 'UNKNOWN')
                .' with score '
                .($validationState['governance_validation_readiness_score']
                    ?? $validation->governance_validation_readiness_score
                    ?? 0).'.',

            'Current validation progression readiness is '
                .($eligibilityState['validation_progression_readiness']
                    ?? 'UNKNOWN').'.',

            'Current governance-validation completion eligibility is '
                .($eligibilityState['governance_validation_completion_eligibility']
                    ?? 'UNKNOWN').'.',

            'Current unrestricted governance-validation eligibility is '
                .($eligibilityState['unrestricted_validation_eligibility']
                    ?? 'UNKNOWN').'.',

            'Current validation safety status is '
                .($safetyState['validation_safety_status']
                    ?? 'UNKNOWN')
                .' with score '
                .($safetyState['validation_safety_score']
                    ?? 0).'.',

            "{$blockingConditions} blocking governance-validation condition(s) remain active.",

            "{$constrainingConditions} constraining governance-validation condition(s) remain active.",

            "{$outstandingEvidence} governance-validation evidence requirement(s) remain outstanding.",

            "{$blockingEvidence} governance-validation evidence requirement(s) currently block validation progression.",

            'Current condition resolution score is '
                .($resolutionState['condition_resolution_score']
                    ?? $executiveSummary['condition_resolution_score']
                    ?? 0).'.',

            'Current evidence resolution score is '
                .($resolutionState['evidence_resolution_score']
                    ?? $executiveSummary['evidence_resolution_score']
                    ?? 0).'.',

            'Current combined condition/evidence resolution score is '
                .($resolutionState['combined_resolution_score']
                    ?? $executiveSummary['combined_resolution_score']
                    ?? 0).'.',

            'Current strategic plan decision risk is '
                .($validation->decision_risk_level ?? 'UNKNOWN')
                .' with score '
                .($validation->decision_risk_score ?? 0).'.',

            'Current governance-validation recommendation status is '
                .($recommendationState['recommendation_status']
                    ?? 'UNKNOWN').'.',

            'Current executive governance-validation status is '
                .($executiveState['executive_governance_validation_status']
                    ?? 'UNKNOWN').'.',

            'Current executive governance-validation readiness is '
                .($executiveState['executive_readiness']
                    ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_governance_validation_score']
                    ?? 0).'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Authorized governance-validator attribution complete is '
                .($validatorAttributionComplete ? 'YES' : 'NO').'.',

            'Governance validation made by authorized human is '
                .($validationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Strategic plan governance-validation integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            "Step 66 strategic plan governance-validation integrity controls currently contain {$failedChecks} failure(s).",
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $executive['executive_priorities']
            ?? $executive['management_priorities']
            ?? $recommendation['management_priorities']
            ?? $eligibilitySafety['management_priorities']
            ?? $resolution['management_priorities']
            ?? $state['management_priorities']
            ?? [];

        $managementPriorities =
            array_values(
                array_unique($managementPriorities)
            );

        /*
        |--------------------------------------------------------------------------
        | Return Step 66.8 Audit
        |--------------------------------------------------------------------------
        */

        return [
            'audit_available' => true,

            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_FAILURES',

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

            'checks' =>
                $checks,

            /*
            |--------------------------------------------------------------------------
            | Validation Summary
            |--------------------------------------------------------------------------
            */

            'validation_summary' => [
                'validation_status' =>
                    $validationState['validation_status']
                    ?? $validation->validation_status,

                'validation_mode' =>
                    $validationState['validation_mode']
                    ?? $validation->validation_mode,

                'prepared_decision' =>
                    $validation->prepared_decision,

                'final_human_decision' =>
                    $validation->final_human_decision,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_decision' =>
                    $validation->governance_validation_decision,

                'governance_validation_readiness' =>
                    $validationState['governance_validation_readiness']
                    ?? $validation->governance_validation_readiness,

                'governance_validation_readiness_score' =>
                    $validationState['governance_validation_readiness_score']
                    ?? $validation->governance_validation_readiness_score,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition / Evidence Summary
            |--------------------------------------------------------------------------
            */

            'condition_evidence_summary' => [
                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'outstanding_evidence_items' =>
                    $outstandingEvidence,

                'blocking_evidence_items' =>
                    $blockingEvidence,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidence,

                'condition_resolution_score' =>
                    $resolutionState['condition_resolution_score']
                    ?? $validation->condition_resolution_score,

                'evidence_resolution_score' =>
                    $resolutionState['evidence_resolution_score']
                    ?? $validation->evidence_resolution_score,

                'combined_resolution_score' =>
                    $resolutionState['combined_resolution_score']
                    ?? $validation->combined_resolution_score,

                'validation_progression_readiness' =>
                    $eligibilityState['validation_progression_readiness']
                    ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Eligibility / Safety Summary
            |--------------------------------------------------------------------------
            */

            'eligibility_safety_summary' => [
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

                'validation_progression_blocked' =>
                    $eligibilityState['validation_progression_blocked']
                    ?? false,

                'validation_safety_status' =>
                    $safetyState['validation_safety_status']
                    ?? null,

                'validation_safety_level' =>
                    $safetyState['validation_safety_level']
                    ?? null,

                'validation_safety_score' =>
                    $safetyState['validation_safety_score']
                    ?? null,

                'governance_integrity_intact' =>
                    $safetyState['governance_integrity_intact']
                    ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recommendation Summary
            |--------------------------------------------------------------------------
            */

            'recommendation_summary' => [
                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations']
                    ?? 0,

                'critical_recommendations' =>
                    $recommendationSummary['critical_recommendations']
                    ?? 0,

                'high_recommendations' =>
                    $recommendationSummary['high_recommendations']
                    ?? 0,

                'moderate_recommendations' =>
                    $recommendationSummary['moderate_recommendations']
                    ?? 0,

                'advisory_recommendations' =>
                    $recommendationSummary['advisory_recommendations']
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code']
                    ?? null,

                'top_recommended_validation_path' =>
                    $recommendationSummary['top_recommended_validation_path']
                    ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Summary
            |--------------------------------------------------------------------------
            */

            'executive_summary' => [
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

                'final_human_decision_recorded' =>
                    $executiveState['final_human_decision_recorded']
                    ?? false,

                'decision_made_by_authorized_human' =>
                    $executiveState['decision_made_by_authorized_human']
                    ?? false,

                'validator_attribution_complete' =>
                    $executiveState['validator_attribution_complete']
                    ?? false,

                'governance_validation_completed' =>
                    $executiveState['governance_validation_completed']
                    ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Human Decision / Validator Summary
            |--------------------------------------------------------------------------
            */

            'authorization_validation_summary' => [
                'final_human_decision' =>
                    $validation->final_human_decision,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_decision' =>
                    $validation->governance_validation_decision,

                'validated_by' =>
                    $validation->validated_by,

                'validator_role' =>
                    $validation->validator_role,

                'validated_at' =>
                    $validation->validated_at,

                'validator_attribution_complete' =>
                    $validatorAttributionComplete,

                'validation_made_by_authorized_human' =>
                    $validationMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
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

                'governance_validation_authority_reserved_for_authorized_human' =>
                    true,

                'automatic_validation_allowed' =>
                    false,

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
            | Step 66.8 Audit Guardrails
            |--------------------------------------------------------------------------
            */

            'audit_guardrails' => [
                'strategic_plan_governance_validation_audit_enabled' => true,

                'audit_is_completed_governance_validation' => false,
                'audit_makes_validation_decision' => false,
                'audit_records_validation_decision' => false,
                'audit_completes_governance_validation' => false,

                'audit_makes_final_strategic_plan_decision' => false,
                'audit_records_final_strategic_plan_decision' => false,

                'audit_approves_strategic_plan' => false,
                'audit_rejects_strategic_plan' => false,
                'audit_conditionally_approves_strategic_plan' => false,
                'audit_defers_strategic_plan' => false,
                'audit_accepts_governance_risk' => false,
                'audit_activates_strategic_plan' => false,

                'audit_changes_validation_status' => false,
                'audit_changes_human_decision' => false,
                'audit_changes_final_human_decision' => false,
                'audit_changes_plan_status' => false,
                'audit_changes_action_state' => false,
                'audit_changes_priority' => false,
                'audit_changes_eligibility' => false,

                'audit_resolves_conditions' => false,
                'audit_resolves_dependencies' => false,
                'audit_validates_evidence' => false,
                'audit_completes_validation_attribution' => false,

                'audit_is_execution_authorization' => false,
                'audit_is_deployment_authorization' => false,
                'audit_is_rollback_authorization' => false,
                'audit_is_clinical_action_authorization' => false,

                'audit_overrides_human_review' => false,
                'audit_overrides_final_human_decision' => false,
                'audit_overrides_governance_validation' => false,
                'audit_overrides_evidence_requirements' => false,

                'automatic_validation_allowed' => false,
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

                'governance_validation_authority_reserved_for_authorized_human' => true,
                'final_decision_authority_reserved_for_authorized_human' => true,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Step 66 strategic plan governance-validation audit consolidates validation preparation, validation state intelligence, condition and evidence resolution intelligence, validation eligibility and safety intelligence, governance-validation recommendations, executive validation intelligence, final-human-decision attribution, validator attribution, and authority-isolation controls for authorized human governance audit and management review only. The audit does not make or record the final strategic plan decision, perform or complete governance validation, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final strategic plan decision authority and governance-validation authority remain reserved exclusively for authorized human governance.',
            ],
        ];
    }
}