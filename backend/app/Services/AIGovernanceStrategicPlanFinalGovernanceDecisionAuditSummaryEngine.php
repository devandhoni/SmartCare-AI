<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionAuditSummaryEngine
{
    public function analyze(?int $finalGovernanceDecisionId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load Final Governance Decision
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecision = $finalGovernanceDecisionId
            ? AIGovernanceStrategicPlanFinalGovernanceDecision::find($finalGovernanceDecisionId)
            : AIGovernanceStrategicPlanFinalGovernanceDecision::latest('id')->first();

        if (!$finalGovernanceDecision) {
            return [
                'audit_available' => false,
                'audit_status' => 'NO_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan final governance decision record is available for audit.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 67 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionStateIntelligenceEngine::class
        );

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionConditionRestrictionIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionExecutiveIntelligenceEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state =
            $stateEngine->analyze($finalGovernanceDecision->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($finalGovernanceDecision->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($finalGovernanceDecision->id);

        $recommendation =
            $recommendationEngine->analyze($finalGovernanceDecision->id);

        $executive =
            $executiveEngine->analyze($finalGovernanceDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $finalGovernanceState =
            $state['final_governance_decision_state'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary'] ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary'] ?? [];

        $eligibilityState =
            $eligibilitySafety['final_governance_decision_eligibility_state']
            ?? $eligibilitySafety['final_decision_eligibility_state']
            ?? [];

        $safetyState =
            $eligibilitySafety['final_governance_decision_safety_state']
            ?? $eligibilitySafety['final_decision_safety_state']
            ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveState =
            $executive['executive_final_governance_state'] ?? [];

        $executiveSummary =
            $executive['executive_summary'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Guardrail Extraction
        |--------------------------------------------------------------------------
        */

        $stateGuardrails =
            $state['final_governance_decision_state_guardrails'] ?? [];

        $conditionRestrictionGuardrails =
            $conditionRestriction['final_governance_decision_condition_restriction_guardrails'] ?? [];

        $eligibilitySafetyGuardrails =
            $eligibilitySafety['final_governance_decision_eligibility_safety_guardrails']
            ?? $eligibilitySafety['final_decision_eligibility_safety_guardrails']
            ?? [];

        $recommendationGuardrails =
            $recommendation['final_governance_decision_recommendation_guardrails']
            ?? [];

        $executiveGuardrails =
            $executive['executive_final_governance_decision_guardrails']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core Decision State
        |--------------------------------------------------------------------------
        */

        $sourceFinalHumanDecisionRecorded =
            (bool) $finalGovernanceDecision->source_final_human_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $finalGovernanceDecision->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $finalGovernanceDecision->source_governance_validation_completed;

        $finalGovernanceDecisionRecorded =
            !empty($finalGovernanceDecision->final_governance_decision);

        $decisionMadeByAuthorizedHuman =
            (bool) $finalGovernanceDecision->decision_made_by_authorized_human;

        $finalGovernanceConfirmationCompleted =
            (bool) $finalGovernanceDecision->final_governance_confirmation_completed;

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            (
                $safetyState['governance_integrity_intact']
                ?? $executiveState['governance_integrity_intact']
                ?? true
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Conditions / Restrictions
        |--------------------------------------------------------------------------
        */

        $blockingConditions =
            (int) (
                $conditionRestrictionState['blocking_conditions']
                ?? $conditionSummary['blocking_conditions']
                ?? 0
            );

        $constrainingConditions =
            (int) (
                $conditionRestrictionState['constraining_conditions']
                ?? $conditionSummary['constraining_conditions']
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary['critical_open_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $conditionRestrictionState['material_restrictions']
                ?? $restrictionSummary['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $conditionRestrictionState['critical_restrictions']
                ?? $restrictionSummary['critical_restrictions']
                ?? 0
            );

        $conditionResolutionScore =
            (float) (
                $conditionRestrictionState['condition_resolution_score']
                ?? $finalGovernanceDecision->condition_resolution_score
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $conditionRestrictionState['evidence_resolution_score']
                ?? $finalGovernanceDecision->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $conditionRestrictionState['combined_resolution_score']
                ?? $finalGovernanceDecision->combined_resolution_score
                ?? 0
            );

        $combinedPressureScore =
            (float) (
                $conditionRestrictionState['combined_condition_restriction_pressure_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $immediateEscalation =
            (
                $executiveState['immediate_human_intervention_required']
                ?? false
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_final_governance_decision_available'] = [
            'passed' => true,
            'message' => 'Strategic plan final governance decision record is available.',
        ];

        $checks['final_governance_decision_state_intelligence_available'] = [
            'passed' => ($state['analysis_completed'] ?? false) === true,
            'message' => 'Final governance decision state intelligence is available.',
        ];

        $checks['final_governance_condition_restriction_intelligence_available'] = [
            'passed' => ($conditionRestriction['analysis_completed'] ?? false) === true,
            'message' => 'Final governance decision condition and restriction intelligence is available.',
        ];

        $checks['final_governance_eligibility_safety_intelligence_available'] = [
            'passed' => ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' => 'Final governance decision eligibility and safety intelligence is available.',
        ];

        $checks['final_governance_recommendation_intelligence_available'] = [
            'passed' => ($recommendation['analysis_completed'] ?? false) === true,
            'message' => 'Final governance decision recommendation intelligence is available.',
        ];

        $checks['executive_final_governance_decision_intelligence_available'] = [
            'passed' => ($executive['analysis_completed'] ?? false) === true,
            'message' => 'Executive final governance decision intelligence is available.',
        ];

        $checks['governance_integrity_intact'] = [
            'passed' => $governanceIntegrityIntact,
            'message' => 'Final governance decision integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Conditions / Restrictions
        |--------------------------------------------------------------------------
        */

        $checks['no_critical_open_final_governance_condition'] = [
            'passed' => $criticalOpenConditions === 0,
            'value' => $criticalOpenConditions,
            'message' => 'No critical final governance decision condition should remain open.',
        ];

        $checks['no_critical_final_governance_restriction'] = [
            'passed' => $criticalRestrictions === 0,
            'value' => $criticalRestrictions,
            'message' => 'No critical final governance decision restriction should remain active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $checks['no_immediate_final_governance_escalation_requirement'] = [
            'passed' => !$immediateEscalation,
            'value' => $immediateEscalation,
            'message' => 'No immediate final governance decision escalation requirement is active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Governance Decision Authority
        |--------------------------------------------------------------------------
        */

        $finalDecisionAuthorityCoherent =
            !$finalGovernanceDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($finalGovernanceDecision->decided_by)
                && !empty($finalGovernanceDecision->decider_role)
                && !empty($finalGovernanceDecision->decided_at)
                && !empty($finalGovernanceDecision->final_governance_decision)
            );

        $checks['final_governance_decision_authority_reserved_for_authorized_human'] = [
            'passed' => $finalDecisionAuthorityCoherent,
            'value' => $finalGovernanceDecisionRecorded,
            'message' => 'Final governance decision authority remains reserved for an explicitly identified authorized human governance decision-maker.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Governance Confirmation Integrity
        |--------------------------------------------------------------------------
        */

        $confirmationCoherent =
            !$finalGovernanceConfirmationCompleted
            || (
                $finalGovernanceDecisionRecorded
                && $decisionMadeByAuthorizedHuman
                && !empty($finalGovernanceDecision->confirmed_by)
                && !empty($finalGovernanceDecision->confirmer_role)
                && !empty($finalGovernanceDecision->confirmed_at)
            );

        $checks['final_governance_confirmation_authority_integrity'] = [
            'passed' => $confirmationCoherent,
            'value' => $finalGovernanceConfirmationCompleted,
            'message' => 'Completed final governance confirmation must remain attributable to an identified authorized human governance confirmer.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Human Decision Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $sourceHumanDecisionCoherent =
            !$sourceFinalHumanDecisionRecorded
            || $sourceDecisionMadeByAuthorizedHuman;

        $checks['source_authorized_human_decision_sequence_integrity'] = [
            'passed' => $sourceHumanDecisionCoherent,
            'message' => 'Any recorded source final human strategic plan decision must remain attributable to explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $sourceValidationCoherent =
            !$sourceGovernanceValidationCompleted
            || (
                $sourceFinalHumanDecisionRecorded
                && $sourceDecisionMadeByAuthorizedHuman
            );

        $checks['source_governance_validation_sequence_integrity'] = [
            'passed' => $sourceValidationCoherent,
            'message' => 'Completed source governance validation must follow an explicitly authorized human strategic plan decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Governance Sequence Integrity
        |--------------------------------------------------------------------------
        */

        $finalGovernanceSequenceCoherent =
            !$finalGovernanceDecisionRecorded
            || (
                $sourceFinalHumanDecisionRecorded
                && $sourceDecisionMadeByAuthorizedHuman
                && $sourceGovernanceValidationCompleted
                && $decisionMadeByAuthorizedHuman
            );

        $checks['final_governance_decision_sequence_integrity'] = [
            'passed' => $finalGovernanceSequenceCoherent,
            'message' => 'A recorded final governance decision must follow authorized-human decision attribution and required governance-validation progression.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_final_governance_authority_isolation'] = [
            'passed' =>
                !$finalGovernanceDecision->automatic_final_decision_allowed
                && !$finalGovernanceDecision->automatic_approval_allowed
                && !$finalGovernanceDecision->automatic_rejection_allowed
                && !$finalGovernanceDecision->automatic_conditional_approval_allowed
                && !$finalGovernanceDecision->automatic_deferral_allowed
                && !$finalGovernanceDecision->automatic_risk_acceptance_allowed
                && !$finalGovernanceDecision->automatic_activation_allowed
                && !$finalGovernanceDecision->automatic_condition_resolution_allowed
                && !$finalGovernanceDecision->automatic_evidence_validation_allowed
                && !$finalGovernanceDecision->automatic_execution_allowed
                && !$finalGovernanceDecision->automatic_change_allowed
                && !$finalGovernanceDecision->automatic_deployment_allowed
                && !$finalGovernanceDecision->automatic_rollback_allowed
                && !$finalGovernanceDecision->automatic_clinical_action_allowed,
            'message' => 'Automatic final decision, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $finalGovernanceDecision->human_review_required
                && (bool) $finalGovernanceDecision->governance_validation_required
                && (bool) $finalGovernanceDecision->authorized_human_final_decision_required,
            'message' => 'Human review, governance validation, and authorized-human final decision controls remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | State Intelligence Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($stateGuardrails['state_intelligence_is_final_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_makes_final_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_records_final_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_confirms_final_governance_decision'] ?? true) === false
                && ($stateGuardrails['state_intelligence_authorizes_execution'] ?? true) === false
                && ($stateGuardrails['final_governance_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Final governance decision state intelligence remains informational and isolated from final governance authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['condition_restriction_intelligence_authority_isolation'] = [
            'passed' =>
                ($conditionRestrictionGuardrails['condition_restriction_intelligence_is_final_governance_decision'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_makes_final_governance_decision'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_records_final_governance_decision'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_confirms_final_governance_decision'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_resolves_conditions'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_removes_restrictions'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_validates_evidence'] ?? true) === false
                && ($conditionRestrictionGuardrails['condition_restriction_intelligence_authorizes_execution'] ?? true) === false
                && ($conditionRestrictionGuardrails['final_governance_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Final governance condition and restriction intelligence remains advisory and isolated from resolution, restriction removal, final decision, confirmation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Eligibility / Safety Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_is_final_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_makes_final_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_records_final_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_confirms_final_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_approves_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_rejects_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_conditionally_approves_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_defers_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_accepts_governance_risk'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_activates_strategic_plan'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_resolves_conditions'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_waives_conditions'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_removes_restrictions'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_downgrades_restrictions'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_resolves_dependencies'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_validates_evidence'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_authorizes_ai_change'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_authorizes_execution'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_authorizes_deployment'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_authorizes_rollback'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_authorizes_clinical_action'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_overrides_human_review'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_overrides_governance_validation'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_overrides_final_human_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['eligibility_safety_intelligence_overrides_final_governance_decision'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_final_decision_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_approval_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_rejection_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_conditional_approval_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_deferral_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_risk_acceptance_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_activation_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_execution_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_change_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_deployment_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_rollback_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['automatic_clinical_action_allowed'] ?? true) === false
                && ($eligibilitySafetyGuardrails['final_governance_decision_authority_reserved_for_authorized_human'] ?? false) === true
                && ($eligibilitySafetyGuardrails['human_review_required'] ?? false) === true
                && ($eligibilitySafetyGuardrails['governance_validation_required'] ?? false) === true
                && ($eligibilitySafetyGuardrails['authorized_human_final_decision_required'] ?? false) === true,

            'message' =>
                'Final governance eligibility and safety intelligence remains isolated from final decision, confirmation, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails['recommendation_intelligence_is_final_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_makes_final_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_records_final_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_confirms_final_governance_decision'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_activates_strategic_plan'] ?? true) === false
                && ($recommendationGuardrails['recommendation_intelligence_authorizes_execution'] ?? true) === false
                && ($recommendationGuardrails['final_governance_decision_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Final governance recommendation intelligence remains advisory and isolated from final governance decision, confirmation, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Authority Isolation
        |--------------------------------------------------------------------------
        */

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails['executive_intelligence_is_final_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_makes_final_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_records_final_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_confirms_final_governance_decision'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_approves_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_activates_strategic_plan'] ?? true) === false
                && ($executiveGuardrails['executive_intelligence_authorizes_execution'] ?? true) === false
                && ($executiveGuardrails['final_governance_decision_authority_reserved_for_authorized_human'] ?? false) === true
                && ($executiveGuardrails['final_governance_confirmation_authority_reserved_for_authorized_human'] ?? false) === true,
            'message' => 'Executive final governance intelligence remains informational and isolated from final governance decision, confirmation, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Totals
        |--------------------------------------------------------------------------
        */

        $totalChecks =
            count($checks);

        $passedChecks =
            collect($checks)
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
                'FINAL_GOVERNANCE_DECISION_INTEGRITY_ATTENTION_REQUIRED';
        } elseif (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
        ) {
            $managementStatus =
                'CRITICAL_FINAL_GOVERNANCE_DECISION_ATTENTION_REQUIRED';
        } elseif (
            $blockingConditions > 0
            || $materialRestrictions > 0
        ) {
            $managementStatus =
                'ELEVATED_FINAL_GOVERNANCE_DECISION_WORK_REMAINS';
        } elseif (!$sourceFinalHumanDecisionRecorded) {
            $managementStatus =
                'SOURCE_AUTHORIZED_HUMAN_FINAL_DECISION_PENDING';
        } elseif (!$sourceGovernanceValidationCompleted) {
            $managementStatus =
                'SOURCE_GOVERNANCE_VALIDATION_PENDING';
        } elseif (!$finalGovernanceDecisionRecorded) {
            $managementStatus =
                'FINAL_GOVERNANCE_DECISION_PENDING';
        } elseif (!$finalGovernanceConfirmationCompleted) {
            $managementStatus =
                'FINAL_GOVERNANCE_DECISION_RECORDED_PENDING_CONFIRMATION';
        } else {
            $managementStatus =
                'FINAL_GOVERNANCE_DECISION_GOVERNANCE_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            "Final governance decision audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",

            "Final governance decision audit is based on final governance decision record {$finalGovernanceDecision->id}.",

            'Current final governance decision status is '
                .($finalGovernanceDecision->final_governance_decision_status ?? 'UNKNOWN').'.',

            'Current final governance decision readiness is '
                .($finalGovernanceState['final_governance_decision_readiness']
                    ?? $finalGovernanceDecision->final_decision_readiness
                    ?? 'UNKNOWN')
                .' with score '
                .($finalGovernanceState['final_governance_decision_readiness_score']
                    ?? $finalGovernanceDecision->final_decision_readiness_score
                    ?? 0).'.',

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$constrainingConditions} constraining final governance decision condition(s) remain active.",

            "{$materialRestrictions} material final governance restriction(s) remain active.",

            "{$criticalRestrictions} critical final governance restriction(s) are represented.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            'Current final governance review eligibility is '
                .($eligibilityState['final_governance_review_eligibility'] ?? 'UNKNOWN').'.',

            'Current unrestricted final governance eligibility is '
                .($eligibilityState['unrestricted_final_decision_eligibility'] ?? 'UNKNOWN').'.',

            'Current final governance confirmation eligibility is '
                .($eligibilityState['final_governance_confirmation_eligibility'] ?? 'UNKNOWN').'.',

            'Current strategic plan activation eligibility is '
                .($eligibilityState['strategic_plan_activation_eligibility'] ?? 'UNKNOWN').'.',

            'Current final governance safety status is '
                .($safetyState['final_governance_decision_safety_status'] ?? 'UNKNOWN')
                .' with score '
                .($safetyState['final_governance_decision_safety_score'] ?? 0).'.',

            'Current final governance recommendation status is '
                .($recommendationState['recommendation_status'] ?? 'UNKNOWN').'.',

            'Current executive final governance status is '
                .($executiveState['executive_final_governance_decision_status'] ?? 'UNKNOWN').'.',

            'Current executive final governance readiness is '
                .($executiveState['executive_readiness'] ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_final_governance_decision_score'] ?? 0).'.',

            'Source final human strategic plan decision recorded is '
                .($sourceFinalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Source strategic plan decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Final governance decision recorded is '
                .($finalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Final governance decision made by authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Final governance confirmation completed is '
                .($finalGovernanceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Final governance decision integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            "Step 67 final-governance integrity controls currently contain {$failedChecks} failure(s).",
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $executive['executive_priorities']
            ?? $recommendation['management_priorities']
            ?? $eligibilitySafety['management_priorities']
            ?? $conditionRestriction['management_priorities']
            ?? $state['management_priorities']
            ?? [];

        $managementPriorities[] =
            'Preserve final governance decision and confirmation authority exclusively for explicitly authorized human governance.';

        $managementPriorities[] =
            'Do not interpret audit completion as approval, confirmation, strategic-plan activation, or execution authorization.';

        $managementPriorities[] =
            'Maintain source decision attribution, governance-validation attribution, final decision attribution, confirmation attribution, evidence traceability, and authority isolation throughout final governance progression.';

        $managementPriorities =
            array_values(
                array_unique(
                    array_filter($managementPriorities)
                )
            );

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

            'strategic_plan_final_governance_decision_id' =>
                $finalGovernanceDecision->id,

            'final_governance_decision_code' =>
                $finalGovernanceDecision->final_governance_decision_code,

            'strategic_plan_decision_validation_id' =>
                $finalGovernanceDecision->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $finalGovernanceDecision->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $finalGovernanceDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $finalGovernanceDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $finalGovernanceDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $finalGovernanceDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $finalGovernanceDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $finalGovernanceDecision->decision_scope,

            'resident_id' =>
                $finalGovernanceDecision->resident_id,

            'management_status' =>
                $managementStatus,

            /*
            |--------------------------------------------------------------------------
            | Audit Summary
            |--------------------------------------------------------------------------
            */

            'audit_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            /*
            |--------------------------------------------------------------------------
            | Final Governance Decision Summary
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_summary' => [
                'final_governance_decision_status' =>
                    $finalGovernanceDecision->final_governance_decision_status,

                'final_governance_decision_mode' =>
                    $finalGovernanceDecision->final_governance_decision_mode,

                'prepared_decision' =>
                    $finalGovernanceDecision->prepared_decision,

                'source_human_decision' =>
                    $finalGovernanceDecision->source_human_decision,

                'source_governance_validation_decision' =>
                    $finalGovernanceDecision->source_governance_validation_decision,

                'final_governance_decision' =>
                    $finalGovernanceDecision->final_governance_decision,

                'final_governance_outcome' =>
                    $finalGovernanceDecision->final_governance_outcome,

                'final_governance_outcome_status' =>
                    $finalGovernanceDecision->final_governance_outcome_status,

                'final_decision_readiness' =>
                    $finalGovernanceState['final_governance_decision_readiness']
                    ?? $finalGovernanceDecision->final_decision_readiness,

                'final_decision_readiness_score' =>
                    $finalGovernanceState['final_governance_decision_readiness_score']
                    ?? $finalGovernanceDecision->final_decision_readiness_score,

                'final_decision_risk_level' =>
                    $finalGovernanceDecision->final_decision_risk_level,

                'final_decision_risk_score' =>
                    $finalGovernanceDecision->final_decision_risk_score,

                'source_final_human_decision_recorded' =>
                    $sourceFinalHumanDecisionRecorded,

                'source_decision_made_by_authorized_human' =>
                    $sourceDecisionMadeByAuthorizedHuman,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'final_governance_decision_recorded' =>
                    $finalGovernanceDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'final_governance_confirmation_completed' =>
                    $finalGovernanceConfirmationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition / Restriction Summary
            |--------------------------------------------------------------------------
            */

            'condition_restriction_summary' => [
                'state' =>
                    $conditionRestrictionState['state'] ?? null,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'critical_restrictions' =>
                    $criticalRestrictions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'condition_pressure_score' =>
                    $conditionRestrictionState['condition_pressure_score'] ?? null,

                'restriction_pressure_score' =>
                    $conditionRestrictionState['restriction_pressure_score'] ?? null,

                'combined_condition_restriction_pressure_score' =>
                    $combinedPressureScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Eligibility / Safety Summary
            |--------------------------------------------------------------------------
            */

            'eligibility_safety_summary' => [
                'final_governance_review_eligibility' =>
                    $eligibilityState['final_governance_review_eligibility'] ?? null,

                'unrestricted_final_decision_eligibility' =>
                    $eligibilityState['unrestricted_final_decision_eligibility'] ?? null,

                'conditional_final_governance_consideration_eligibility' =>
                    $eligibilityState['conditional_final_governance_consideration_eligibility'] ?? null,

                'final_governance_deferral_eligibility' =>
                    $eligibilityState['final_governance_deferral_eligibility'] ?? null,

                'governance_risk_acceptance_review_eligibility' =>
                    $eligibilityState['governance_risk_acceptance_review_eligibility'] ?? null,

                'final_governance_confirmation_eligibility' =>
                    $eligibilityState['final_governance_confirmation_eligibility'] ?? null,

                'strategic_plan_activation_eligibility' =>
                    $eligibilityState['strategic_plan_activation_eligibility'] ?? null,

                'final_governance_decision_eligibility_score' =>
                    $eligibilityState['final_governance_decision_eligibility_score'] ?? null,

                'final_governance_decision_safety_status' =>
                    $safetyState['final_governance_decision_safety_status'] ?? null,

                'final_governance_decision_safety_level' =>
                    $safetyState['final_governance_decision_safety_level'] ?? null,

                'final_governance_decision_safety_score' =>
                    $safetyState['final_governance_decision_safety_score'] ?? null,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,
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

                'top_recommended_governance_path' =>
                    $recommendationSummary['top_recommended_governance_path'] ?? null,

                'recommendation_priority_score' =>
                    $recommendationSummary['recommendation_priority_score'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Executive Summary
            |--------------------------------------------------------------------------
            */

            'executive_summary' => [
                'executive_final_governance_decision_status' =>
                    $executiveState['executive_final_governance_decision_status'] ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness'] ?? null,

                'executive_confidence' =>
                    $executiveState['executive_confidence'] ?? null,

                'executive_final_governance_decision_score' =>
                    $executiveState['executive_final_governance_decision_score'] ?? null,

                'human_management_attention_level' =>
                    $executiveState['human_management_attention_level'] ?? null,

                'human_management_attention_required' =>
                    $executiveState['human_management_attention_required'] ?? false,

                'management_escalation_recommended' =>
                    $executiveState['management_escalation_recommended'] ?? false,

                'immediate_human_intervention_required' =>
                    $executiveState['immediate_human_intervention_required'] ?? false,

                'governance_integrity_intact' =>
                    $executiveState['governance_integrity_intact'] ?? false,

                'final_governance_decision_recorded' =>
                    $executiveState['final_governance_decision_recorded'] ?? false,

                'decision_made_by_authorized_human' =>
                    $executiveState['decision_made_by_authorized_human'] ?? false,

                'final_governance_confirmation_completed' =>
                    $executiveState['final_governance_confirmation_completed'] ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Authorization / Confirmation Summary
            |--------------------------------------------------------------------------
            */

            'authorization_confirmation_summary' => [
                'decided_by' =>
                    $finalGovernanceDecision->decided_by,

                'decider_role' =>
                    $finalGovernanceDecision->decider_role,

                'decided_at' =>
                    $finalGovernanceDecision->decided_at,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'confirmed_by' =>
                    $finalGovernanceDecision->confirmed_by,

                'confirmer_role' =>
                    $finalGovernanceDecision->confirmer_role,

                'confirmed_at' =>
                    $finalGovernanceDecision->confirmed_at,

                'final_governance_confirmation_completed' =>
                    $finalGovernanceConfirmationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Integrity Summary
            |--------------------------------------------------------------------------
            */

            'integrity_summary' => [
                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'final_governance_confirmation_authority_reserved_for_authorized_human' =>
                    true,

                'automatic_final_decision_allowed' =>
                    false,

                'automatic_approval_allowed' =>
                    false,

                'automatic_rejection_allowed' =>
                    false,

                'automatic_conditional_approval_allowed' =>
                    false,

                'automatic_deferral_allowed' =>
                    false,

                'automatic_risk_acceptance_allowed' =>
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

                'authorized_human_final_decision_required' =>
                    true,
            ],

            'audit_findings' =>
                $auditFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 67.8 Audit Guardrails
            |--------------------------------------------------------------------------
            */

            'audit_guardrails' => [
                'final_governance_decision_audit_enabled' =>
                    true,

                'audit_is_final_governance_decision' =>
                    false,

                'audit_makes_final_governance_decision' =>
                    false,

                'audit_records_final_governance_decision' =>
                    false,

                'audit_confirms_final_governance_decision' =>
                    false,

                'audit_approves_strategic_plan' =>
                    false,

                'audit_rejects_strategic_plan' =>
                    false,

                'audit_conditionally_approves_strategic_plan' =>
                    false,

                'audit_defers_strategic_plan' =>
                    false,

                'audit_accepts_governance_risk' =>
                    false,

                'audit_activates_strategic_plan' =>
                    false,

                'audit_changes_source_human_decision' =>
                    false,

                'audit_changes_governance_validation' =>
                    false,

                'audit_changes_final_governance_decision_status' =>
                    false,

                'audit_changes_final_governance_decision' =>
                    false,

                'audit_changes_final_governance_outcome' =>
                    false,

                'audit_changes_plan_status' =>
                    false,

                'audit_changes_action_state' =>
                    false,

                'audit_changes_priority' =>
                    false,

                'audit_changes_eligibility' =>
                    false,

                'audit_resolves_conditions' =>
                    false,

                'audit_waives_conditions' =>
                    false,

                'audit_removes_restrictions' =>
                    false,

                'audit_downgrades_restrictions' =>
                    false,

                'audit_resolves_dependencies' =>
                    false,

                'audit_validates_evidence' =>
                    false,

                'audit_is_execution_authorization' =>
                    false,

                'audit_is_deployment_authorization' =>
                    false,

                'audit_is_rollback_authorization' =>
                    false,

                'audit_is_clinical_action_authorization' =>
                    false,

                'audit_overrides_human_review' =>
                    false,

                'audit_overrides_governance_validation' =>
                    false,

                'audit_overrides_final_human_decision' =>
                    false,

                'audit_overrides_final_governance_decision' =>
                    false,

                'audit_overrides_confirmation_requirements' =>
                    false,

                'audit_overrides_evidence_requirements' =>
                    false,

                'automatic_final_decision_allowed' =>
                    false,

                'automatic_approval_allowed' =>
                    false,

                'automatic_rejection_allowed' =>
                    false,

                'automatic_conditional_approval_allowed' =>
                    false,

                'automatic_deferral_allowed' =>
                    false,

                'automatic_risk_acceptance_allowed' =>
                    false,

                'automatic_activation_allowed' =>
                    false,

                'automatic_condition_resolution_allowed' =>
                    false,

                'automatic_restriction_removal_allowed' =>
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

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'final_governance_confirmation_authority_reserved_for_authorized_human' =>
                    true,

                'strategic_plan_activation_requires_explicit_governance_authorization' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,

                'message' =>
                    'Step 67.8 final governance decision audit consolidates final-governance decision preparation, decision-state intelligence, condition and restriction intelligence, eligibility and safety intelligence, advisory recommendations, executive intelligence, source authorized-human decision state, source governance-validation state, final-decision attribution, confirmation attribution, and authority-isolation controls for authorized human governance audit and management review only. The audit does not make, record, or confirm the final governance decision; approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve or waive conditions, remove or downgrade restrictions, validate evidence, modify upstream governance records, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final governance decision and confirmation authority remain reserved exclusively for explicitly authorized human governance.',
            ],
        ];
    }
}