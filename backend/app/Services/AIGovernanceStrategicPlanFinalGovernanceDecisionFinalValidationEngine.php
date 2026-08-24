<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionFinalValidationEngine
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
                'validation_status' => 'FAILED',
                'step_67_ready_for_closure' => false,
                'governance_final_decision_mode' =>
                    'AUTHORIZED_HUMAN_GOVERNED_FINAL_STRATEGIC_PLAN_DECISION_INTELLIGENCE',
                'completion_message' =>
                    'Step 67 cannot be validated because no strategic plan final governance decision record is available.',
                'validation_summary' => [
                    'total_checks' => 1,
                    'passed_checks' => 0,
                    'failed_checks' => 1,
                    'warning_count' => 0,
                    'critical_issue_count' => 1,
                ],
                'critical_issues' => [
                    'No strategic plan final governance decision record is available.',
                ],
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

        $auditEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionAuditSummaryEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Step 67 Intelligence
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

        $audit =
            $auditEngine->analyze($finalGovernanceDecision->id);

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
            $executive['executive_final_governance_state']
            ?? $executive['executive_final_governance_decision_state']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Audit Context
        |--------------------------------------------------------------------------
        */

        $auditChecks =
            $audit['checks'] ?? [];

        $auditSummary =
            $audit['audit_summary'] ?? [];

        $auditIntegritySummary =
            $audit['integrity_summary'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Current Governance State
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
        | Condition / Restriction State
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

        $resolvedConditions =
            (int) (
                $conditionRestrictionState['resolved_conditions']
                ?? $conditionSummary['resolved_conditions']
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

        $conditionPressureScore =
            (float) (
                $conditionRestrictionState['condition_pressure_score']
                ?? 0
            );

        $restrictionPressureScore =
            (float) (
                $conditionRestrictionState['restriction_pressure_score']
                ?? 0
            );

        $combinedConditionRestrictionPressureScore =
            (float) (
                $conditionRestrictionState[
                    'combined_condition_restriction_pressure_score'
                ]
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            (
                $safetyState['governance_integrity_intact']
                ?? $executiveState['governance_integrity_intact']
                ?? $auditIntegritySummary['governance_integrity_intact']
                ?? false
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            (
                $executiveState['immediate_human_intervention_required']
                ?? $executiveState['immediate_escalation_required']
                ?? false
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['strategic_plan_final_governance_decision_available'] = [
            'passed' => true,
            'message' =>
                'Strategic plan final governance decision record is available.',
        ];

        $checks['final_governance_decision_state_intelligence_operational'] = [
            'passed' =>
                ($state['analysis_completed'] ?? false) === true,
            'message' =>
                'Final governance decision state intelligence is operational.',
        ];

        $checks['final_governance_condition_restriction_intelligence_operational'] = [
            'passed' =>
                ($conditionRestriction['analysis_completed'] ?? false) === true,
            'message' =>
                'Final governance decision condition and restriction intelligence is operational.',
        ];

        $checks['final_governance_eligibility_safety_intelligence_operational'] = [
            'passed' =>
                ($eligibilitySafety['analysis_completed'] ?? false) === true,
            'message' =>
                'Final governance decision eligibility and safety intelligence is operational.',
        ];

        $checks['final_governance_recommendation_intelligence_operational'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' =>
                'Final governance decision recommendation intelligence is operational.',
        ];

        $checks['executive_final_governance_decision_intelligence_operational'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' =>
                'Executive final governance decision intelligence is operational.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Step 67.8 Audit
        |--------------------------------------------------------------------------
        */

        $checks['step_67_audit_complete'] = [
            'passed' =>
                ($audit['audit_available'] ?? false) === true
                && ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (int) ($auditSummary['failed_checks'] ?? 1) === 0,
            'message' =>
                'Step 67 final governance decision audit completed without integrity failures.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $checks['governance_integrity_intact'] = [
            'passed' =>
                $governanceIntegrityIntact,
            'message' =>
                'Strategic plan final governance decision integrity remains intact.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Critical Condition / Restriction Checks
        |--------------------------------------------------------------------------
        */

        $checks['critical_final_governance_condition_absent'] = [
            'passed' =>
                $criticalOpenConditions === 0,
            'value' =>
                $criticalOpenConditions,
            'message' =>
                'No critical strategic plan final governance decision condition remains open.',
        ];

        $checks['critical_final_governance_restriction_absent'] = [
            'passed' =>
                $criticalRestrictions === 0,
            'value' =>
                $criticalRestrictions,
            'message' =>
                'No critical strategic plan final governance decision restriction remains active.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Immediate Escalation
        |--------------------------------------------------------------------------
        */

        $checks['immediate_final_governance_escalation_absent'] = [
            'passed' =>
                !$immediateHumanInterventionRequired,
            'value' =>
                $immediateHumanInterventionRequired,
            'message' =>
                'No immediate final governance decision escalation requirement is active.',
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
            'message' =>
                'Automatic final governance decision, approval, rejection, conditional approval, deferral, risk acceptance, activation, condition resolution, evidence validation, execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Mandatory Human Governance
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $finalGovernanceDecision->human_review_required
                && (bool) $finalGovernanceDecision->governance_validation_required
                && (bool) $finalGovernanceDecision->authorized_human_final_decision_required,
            'message' =>
                'Human review, governance validation, and explicitly authorized-human final decision controls remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Authority Isolation
        |--------------------------------------------------------------------------
        |
        | Step 67.8 already validates the exact guardrail contracts for each
        | intelligence engine. Step 67.9 validates those audited isolation
        | results rather than introducing a second incompatible key contract.
        |
        */

        $checks['state_intelligence_authority_isolation'] = [
            'passed' =>
                ($auditChecks['state_intelligence_authority_isolation']['passed']
                    ?? false) === true,
            'message' =>
                'Final governance decision state intelligence remains informational and isolated from final governance authority.',
        ];

        $checks['condition_restriction_intelligence_authority_isolation'] = [
            'passed' =>
                ($auditChecks[
                    'condition_restriction_intelligence_authority_isolation'
                ]['passed'] ?? false) === true,
            'message' =>
                'Final governance condition and restriction intelligence remains advisory and isolated from resolution, restriction removal, final decision, confirmation, and execution authority.',
        ];

        $checks['eligibility_safety_authority_isolation'] = [
            'passed' =>
                ($auditChecks['eligibility_safety_authority_isolation']['passed']
                    ?? false) === true,
            'message' =>
                'Final governance eligibility and safety intelligence remains isolated from final decision, confirmation, activation, and execution authority.',
        ];

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($auditChecks['recommendation_authority_isolation']['passed']
                    ?? false) === true,
            'message' =>
                'Final governance recommendation intelligence remains advisory and isolated from final governance decision, confirmation, activation, and execution authority.',
        ];

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($auditChecks['executive_authority_isolation']['passed']
                    ?? false) === true,
            'message' =>
                'Executive final governance intelligence remains informational and isolated from final governance decision, confirmation, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Decision Authority Protection
        |--------------------------------------------------------------------------
        */

        $finalDecisionAuthorityCoherent =
            !$finalGovernanceDecisionRecorded
            || (
                $decisionMadeByAuthorizedHuman
                && !empty($finalGovernanceDecision->decided_by)
                && !empty($finalGovernanceDecision->decider_role)
                && !empty($finalGovernanceDecision->decided_at)
            );

        $checks['final_governance_decision_authority_protected'] = [
            'passed' =>
                $finalDecisionAuthorityCoherent,
            'value' =>
                $finalGovernanceDecisionRecorded,
            'message' =>
                'Final strategic plan governance decision authority remains reserved for an explicitly identified authorized human governance decision-maker.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Authority Protection
        |--------------------------------------------------------------------------
        */

        $confirmationAuthorityCoherent =
            !$finalGovernanceConfirmationCompleted
            || (
                $finalGovernanceDecisionRecorded
                && $decisionMadeByAuthorizedHuman
                && !empty($finalGovernanceDecision->confirmed_by)
                && !empty($finalGovernanceDecision->confirmer_role)
                && !empty($finalGovernanceDecision->confirmed_at)
            );

        $checks['final_governance_confirmation_authority_protected'] = [
            'passed' =>
                $confirmationAuthorityCoherent,
            'value' =>
                $finalGovernanceConfirmationCompleted,
            'message' =>
                'Final governance confirmation remains attributable to explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Human Decision Sequence
        |--------------------------------------------------------------------------
        */

        $sourceDecisionSequenceCoherent =
            !$sourceFinalHumanDecisionRecorded
            || $sourceDecisionMadeByAuthorizedHuman;

        $checks['source_human_decision_sequence_integrity'] = [
            'passed' =>
                $sourceDecisionSequenceCoherent,
            'message' =>
                'Any recorded source strategic plan final human decision remains attributable to authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Governance Validation Sequence
        |--------------------------------------------------------------------------
        */

        $sourceValidationSequenceCoherent =
            !$sourceGovernanceValidationCompleted
            || (
                $sourceFinalHumanDecisionRecorded
                && $sourceDecisionMadeByAuthorizedHuman
            );

        $checks['source_governance_validation_sequence_integrity'] = [
            'passed' =>
                $sourceValidationSequenceCoherent,
            'message' =>
                'Completed source governance validation follows an explicitly authorized human strategic plan decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Governance Decision Sequence
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
            'passed' =>
                $finalGovernanceSequenceCoherent,
            'message' =>
                'A recorded final governance decision follows required authorized-human decision and governance-validation sequencing.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Authority Isolation
        |--------------------------------------------------------------------------
        */

        $auditGuardrails =
            $audit['audit_guardrails'] ?? [];

        $checks['audit_authority_isolation'] = [
            'passed' =>
                ($auditGuardrails['audit_is_final_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_makes_final_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_records_final_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_confirms_final_governance_decision'] ?? true) === false
                && ($auditGuardrails['audit_approves_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_activates_strategic_plan'] ?? true) === false
                && ($auditGuardrails['audit_is_execution_authorization'] ?? true) === false
                && ($auditGuardrails[
                    'final_governance_decision_authority_reserved_for_authorized_human'
                ] ?? false) === true
                && ($auditGuardrails[
                    'final_governance_confirmation_authority_reserved_for_authorized_human'
                ] ?? false) === true,
            'message' =>
                'Step 67 audit remains informational and isolated from final governance decision, confirmation, activation, and execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation Totals
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
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        $finalDecisionReadiness =
            $finalGovernanceState['final_governance_decision_readiness']
            ?? $finalGovernanceDecision->final_decision_readiness
            ?? 'UNKNOWN';

        $finalDecisionReadinessScore =
            (float) (
                $finalGovernanceState['final_governance_decision_readiness_score']
                ?? $finalGovernanceDecision->final_decision_readiness_score
                ?? 0
            );

        $finalDecisionRiskLevel =
            $finalGovernanceDecision->final_decision_risk_level
            ?? 'UNKNOWN';

        $finalDecisionRiskScore =
            (float) (
                $finalGovernanceDecision->final_decision_risk_score
                ?? 0
            );

        if (!$sourceFinalHumanDecisionRecorded) {
            $warnings[] =
                'Source authorized-human strategic plan final decision has not yet been recorded.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $warnings[] =
                'Source strategic plan final decision has not yet been attributed to an explicitly authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $warnings[] =
                'Source strategic plan governance validation has not yet been completed.';
        }

        if ($blockingConditions > 0) {
            $warnings[] =
                "{$blockingConditions} blocking final governance decision condition(s) remain active.";
        }

        if ($constrainingConditions > 0) {
            $warnings[] =
                "{$constrainingConditions} constraining final governance decision condition(s) remain active.";
        }

        if ($materialRestrictions > 0) {
            $warnings[] =
                "{$materialRestrictions} material final governance decision restriction(s) remain active.";
        }

        if ($conditionResolutionScore < 100) {
            $warnings[] =
                "Final governance decision condition resolution remains limited at score {$conditionResolutionScore}.";
        }

        if ($evidenceResolutionScore < 100) {
            $warnings[] =
                "Final governance decision evidence resolution remains limited at score {$evidenceResolutionScore}.";
        }

        if ($combinedResolutionScore < 100) {
            $warnings[] =
                "Combined final governance condition and evidence resolution remains limited at score {$combinedResolutionScore}.";
        }

        if ($combinedConditionRestrictionPressureScore > 0) {
            $warnings[] =
                "Final governance combined condition/restriction pressure remains active at score {$combinedConditionRestrictionPressureScore}.";
        }

        if (
            str_contains(
                strtoupper((string) $finalDecisionRiskLevel),
                'HIGH'
            )
            || $finalDecisionRiskScore >= 75
        ) {
            $warnings[] =
                "Strategic plan final governance decision risk remains {$finalDecisionRiskLevel} with score {$finalDecisionRiskScore}.";
        }

        $unrestrictedEligibility =
            $eligibilityState['unrestricted_final_decision_eligibility']
            ?? null;

        if (
            $unrestrictedEligibility !== null
            && !str_starts_with(
                strtoupper((string) $unrestrictedEligibility),
                'ELIGIBLE'
            )
        ) {
            $warnings[] =
                'Unrestricted final governance decision progression remains unavailable under current governance conditions.';
        }

        $confirmationEligibility =
            $eligibilityState['final_governance_confirmation_eligibility']
            ?? null;

        if (
            $confirmationEligibility !== null
            && !str_starts_with(
                strtoupper((string) $confirmationEligibility),
                'ELIGIBLE'
            )
        ) {
            $warnings[] =
                'Final governance confirmation is not currently eligible under existing governance conditions.';
        }

        $activationEligibility =
            $eligibilityState['strategic_plan_activation_eligibility']
            ?? null;

        if (
            $activationEligibility !== null
            && !str_starts_with(
                strtoupper((string) $activationEligibility),
                'ELIGIBLE'
            )
        ) {
            $warnings[] =
                'Strategic plan activation remains unavailable under current governance conditions.';
        }

        if (!$finalGovernanceDecisionRecorded) {
            $warnings[] =
                'Final strategic plan governance decision has not yet been recorded.';
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $warnings[] =
                'Final governance decision has not yet been attributed to an explicitly authorized human governance decision-maker.';
        }

        if (!$finalGovernanceConfirmationCompleted) {
            $warnings[] =
                'Final governance confirmation has not yet been completed.';
        }

        if (
            ($executiveState['management_escalation_recommended'] ?? false)
            === true
        ) {
            $warnings[] =
                'Management escalation remains recommended for current final governance decision conditions.';
        }

        $warnings =
            array_values(
                array_unique(
                    array_filter($warnings)
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $checkName => $check) {
            if (($check['passed'] ?? false) !== true) {
                $criticalIssues[] =
                    "Final Step 67 integrity check failed: {$checkName}.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Validation Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks > 0) {
            $validationStatus =
                'FAILED';

            $step67ReadyForClosure =
                false;

            $completionMessage =
                'Step 67 AI Governance Strategic Plan Final Governance Decision Intelligence has not passed final validation and requires corrective governance-control work.';
        } elseif (count($warnings) > 0) {
            $validationStatus =
                'PASSED_WITH_WARNINGS';

            $step67ReadyForClosure =
                true;

            $completionMessage =
                'Step 67 AI Governance Strategic Plan Final Governance Decision Intelligence has passed final validation with governed human-decision warnings and is ready for closure.';
        } else {
            $validationStatus =
                'PASSED';

            $step67ReadyForClosure =
                true;

            $completionMessage =
                'Step 67 AI Governance Strategic Plan Final Governance Decision Intelligence has passed final validation and is ready for closure.';
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 67 AI Governance Strategic Plan Final Governance Decision Intelligence architecture has been validated.',

            'Current final governance decision status is '
                .($finalGovernanceDecision->final_governance_decision_status
                    ?? 'UNKNOWN').'.',

            'Current final governance decision readiness is '
                .$finalDecisionReadiness
                .' with score '
                .$finalDecisionReadinessScore.'.',

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$constrainingConditions} constraining final governance decision condition(s) remain active.",

            "{$resolvedConditions} final governance decision condition(s) are currently resolved.",

            "{$materialRestrictions} material final governance decision restriction(s) remain active.",

            "{$criticalRestrictions} critical final governance decision restriction(s) are represented.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            "Current condition pressure score is {$conditionPressureScore}.",

            "Current restriction pressure score is {$restrictionPressureScore}.",

            "Current combined condition/restriction pressure score is {$combinedConditionRestrictionPressureScore}.",

            'Current unrestricted final governance eligibility is '
                .($eligibilityState['unrestricted_final_decision_eligibility']
                    ?? 'UNKNOWN').'.',

            'Current final governance confirmation eligibility is '
                .($eligibilityState['final_governance_confirmation_eligibility']
                    ?? 'UNKNOWN').'.',

            'Current strategic plan activation eligibility is '
                .($eligibilityState['strategic_plan_activation_eligibility']
                    ?? 'UNKNOWN').'.',

            'Current final governance decision safety status is '
                .($safetyState['final_governance_decision_safety_status']
                    ?? 'UNKNOWN')
                .' with score '
                .($safetyState['final_governance_decision_safety_score']
                    ?? 0).'.',

            'Current final governance recommendation status is '
                .($recommendationState['recommendation_status']
                    ?? 'UNKNOWN').'.',

            'Current executive final governance decision status is '
                .($executiveState['executive_final_governance_decision_status']
                    ?? 'UNKNOWN').'.',

            'Current executive final governance readiness is '
                .($executiveState['executive_readiness']
                    ?? 'UNKNOWN')
                .' with score '
                .($executiveState['executive_final_governance_decision_score']
                    ?? 0).'.',

            'Source authorized-human strategic plan final decision recorded is '
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

            'Final governance decision intelligence remains advisory, informational, and explicitly human governed.',

            'No autonomous final governance decision, approval, rejection, conditional approval, deferral, risk acceptance, strategic-plan activation, condition resolution, restriction removal, evidence validation, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '67.1_final_governance_decision_registry' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '67.2_final_governance_decision_preparation' => [
                'status' =>
                    'OPERATIONAL',

                'strategic_plan_final_governance_decision_id' =>
                    $finalGovernanceDecision->id,

                'final_governance_decision_code' =>
                    $finalGovernanceDecision->final_governance_decision_code,
            ],

            '67.3_final_governance_decision_state_intelligence' => [
                'status' =>
                    ($state['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'final_governance_decision_status' =>
                    $finalGovernanceDecision->final_governance_decision_status,

                'final_governance_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_governance_decision_readiness_score' =>
                    $finalDecisionReadinessScore,
            ],

            '67.4_final_governance_condition_restriction_intelligence' => [
                'status' =>
                    ($conditionRestriction['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'material_restrictions' =>
                    $materialRestrictions,

                'combined_condition_restriction_pressure_score' =>
                    $combinedConditionRestrictionPressureScore,
            ],

            '67.5_final_governance_eligibility_safety_intelligence' => [
                'status' =>
                    ($eligibilitySafety['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'unrestricted_final_decision_eligibility' =>
                    $eligibilityState['unrestricted_final_decision_eligibility']
                    ?? null,

                'final_governance_confirmation_eligibility' =>
                    $eligibilityState['final_governance_confirmation_eligibility']
                    ?? null,

                'strategic_plan_activation_eligibility' =>
                    $eligibilityState['strategic_plan_activation_eligibility']
                    ?? null,

                'final_governance_decision_safety_status' =>
                    $safetyState['final_governance_decision_safety_status']
                    ?? null,

                'final_governance_decision_safety_score' =>
                    $safetyState['final_governance_decision_safety_score']
                    ?? null,
            ],

            '67.6_final_governance_recommendation_intelligence' => [
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

            '67.7_executive_final_governance_decision_intelligence' => [
                'status' =>
                    ($executive['analysis_completed'] ?? false)
                        ? 'OPERATIONAL'
                        : 'NOT_OPERATIONAL',

                'executive_final_governance_decision_status' =>
                    $executiveState['executive_final_governance_decision_status']
                    ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? null,

                'executive_final_governance_decision_score' =>
                    $executiveState['executive_final_governance_decision_score']
                    ?? null,
            ],

            '67.8_final_governance_decision_audit' => [
                'status' =>
                    $audit['audit_status'] ?? 'UNKNOWN',

                'passed_checks' =>
                    $auditSummary['passed_checks'] ?? 0,

                'failed_checks' =>
                    $auditSummary['failed_checks'] ?? 0,
            ],

            '67.9_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_67_ready_for_closure' =>
                    $step67ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Step 67 Guardrails
        |--------------------------------------------------------------------------
        */

        $step67Guardrails = [
            'strategic_plan_final_governance_decision_intelligence_enabled' =>
                true,

            'final_governance_decision_registry_enabled' =>
                true,

            'final_governance_decision_preparation_enabled' =>
                true,

            'final_governance_decision_state_intelligence_enabled' =>
                true,

            'final_governance_decision_condition_restriction_intelligence_enabled' =>
                true,

            'final_governance_decision_eligibility_safety_intelligence_enabled' =>
                true,

            'final_governance_decision_recommendation_intelligence_enabled' =>
                true,

            'executive_final_governance_decision_intelligence_enabled' =>
                true,

            'final_governance_decision_audit_enabled' =>
                true,

            /*
            |--------------------------------------------------------------------------
            | Autonomous Governance Decision Prohibitions
            |--------------------------------------------------------------------------
            */

            'autonomous_final_governance_decision_enabled' =>
                false,

            'autonomous_strategic_plan_approval_enabled' =>
                false,

            'autonomous_strategic_plan_rejection_enabled' =>
                false,

            'autonomous_strategic_plan_conditional_approval_enabled' =>
                false,

            'autonomous_strategic_plan_deferral_enabled' =>
                false,

            'autonomous_governance_risk_acceptance_enabled' =>
                false,

            'autonomous_strategic_plan_activation_enabled' =>
                false,

            'autonomous_final_governance_confirmation_enabled' =>
                false,

            /*
            |--------------------------------------------------------------------------
            | Automatic Resolution / Validation Prohibitions
            |--------------------------------------------------------------------------
            */

            'automatic_condition_resolution_enabled' =>
                false,

            'automatic_restriction_removal_enabled' =>
                false,

            'automatic_dependency_resolution_enabled' =>
                false,

            'automatic_evidence_validation_enabled' =>
                false,

            'automatic_governance_validation_enabled' =>
                false,

            /*
            |--------------------------------------------------------------------------
            | Automatic AI / Execution Prohibitions
            |--------------------------------------------------------------------------
            */

            'automatic_final_governance_decision' =>
                false,

            'automatic_governance_approval' =>
                false,

            'automatic_governance_rejection' =>
                false,

            'automatic_governance_conditional_approval' =>
                false,

            'automatic_governance_deferral' =>
                false,

            'automatic_governance_risk_acceptance' =>
                false,

            'automatic_strategic_plan_activation' =>
                false,

            'automatic_model_change' =>
                false,

            'automatic_threshold_change' =>
                false,

            'automatic_confidence_change' =>
                false,

            'automatic_recommendation_change' =>
                false,

            'automatic_workflow_change' =>
                false,

            'automatic_clinical_rule_change' =>
                false,

            'automatic_clinical_action' =>
                false,

            'automatic_execution' =>
                false,

            'automatic_deployment' =>
                false,

            'automatic_rollback' =>
                false,

            /*
            |--------------------------------------------------------------------------
            | Human Governance Authority
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'source_human_decision_authority_reserved_for_authorized_human' =>
                true,

            'governance_validation_authority_reserved_for_authorized_human' =>
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
                'Step 67 establishes explicitly authorized-human-governed final strategic plan governance decision intelligence. The system may prepare a final-governance decision record, evaluate final decision state, unresolved conditions, restrictions, eligibility, safety controls, advisory recommendations, executive governance pressure, source authorized-human decision state, source governance-validation state, final-decision attribution, confirmation state, readiness, risk, and governance-control integrity. It does not autonomously make, record, or confirm the final governance decision; approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve or waive conditions, remove or downgrade restrictions, resolve dependencies, validate evidence, alter upstream governance decisions, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority. Final governance decision and confirmation authority remain reserved exclusively for explicitly authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return Final Step 67 Validation
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_67_ready_for_closure' =>
                $step67ReadyForClosure,

            'governance_final_decision_mode' =>
                'AUTHORIZED_HUMAN_GOVERNED_FINAL_STRATEGIC_PLAN_DECISION_INTELLIGENCE',

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

            'completion_message' =>
                $completionMessage,

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
            | Final Governance Context
            |--------------------------------------------------------------------------
            */

            'governance_final_decision_context' => [
                'final_governance_decision_status' =>
                    $finalGovernanceDecision->final_governance_decision_status,

                'final_governance_decision_mode' =>
                    $finalGovernanceDecision->final_governance_decision_mode,

                'prepared_decision' =>
                    $finalGovernanceDecision->prepared_decision,

                'source_human_decision' =>
                    $finalGovernanceDecision->source_human_decision,

                'source_final_human_decision_recorded' =>
                    $sourceFinalHumanDecisionRecorded,

                'source_decision_made_by_authorized_human' =>
                    $sourceDecisionMadeByAuthorizedHuman,

                'source_governance_validation_decision' =>
                    $finalGovernanceDecision->source_governance_validation_decision,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'final_governance_decision' =>
                    $finalGovernanceDecision->final_governance_decision,

                'final_governance_outcome' =>
                    $finalGovernanceDecision->final_governance_outcome,

                'final_governance_outcome_status' =>
                    $finalGovernanceDecision->final_governance_outcome_status,

                'final_governance_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_governance_decision_readiness_score' =>
                    $finalDecisionReadinessScore,

                'final_decision_risk_level' =>
                    $finalDecisionRiskLevel,

                'final_decision_risk_score' =>
                    $finalDecisionRiskScore,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'resolved_conditions' =>
                    $resolvedConditions,

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
                    $conditionPressureScore,

                'restriction_pressure_score' =>
                    $restrictionPressureScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedConditionRestrictionPressureScore,

                'final_governance_review_eligibility' =>
                    $eligibilityState['final_governance_review_eligibility']
                    ?? null,

                'unrestricted_final_decision_eligibility' =>
                    $eligibilityState['unrestricted_final_decision_eligibility']
                    ?? null,

                'conditional_final_governance_consideration_eligibility' =>
                    $eligibilityState[
                        'conditional_final_governance_consideration_eligibility'
                    ]
                    ?? null,

                'final_governance_deferral_eligibility' =>
                    $eligibilityState['final_governance_deferral_eligibility']
                    ?? null,

                'governance_risk_acceptance_review_eligibility' =>
                    $eligibilityState[
                        'governance_risk_acceptance_review_eligibility'
                    ]
                    ?? null,

                'final_governance_confirmation_eligibility' =>
                    $eligibilityState['final_governance_confirmation_eligibility']
                    ?? null,

                'strategic_plan_activation_eligibility' =>
                    $eligibilityState['strategic_plan_activation_eligibility']
                    ?? null,

                'final_governance_decision_safety_status' =>
                    $safetyState['final_governance_decision_safety_status']
                    ?? null,

                'final_governance_decision_safety_level' =>
                    $safetyState['final_governance_decision_safety_level']
                    ?? null,

                'final_governance_decision_safety_score' =>
                    $safetyState['final_governance_decision_safety_score']
                    ?? null,

                'recommendation_status' =>
                    $recommendationState['recommendation_status']
                    ?? null,

                'total_recommendations' =>
                    $recommendationSummary['total_recommendations']
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary['top_recommendation_code']
                    ?? null,

                'top_recommended_governance_path' =>
                    $recommendationSummary['top_recommended_governance_path']
                    ?? null,

                'executive_final_governance_decision_status' =>
                    $executiveState['executive_final_governance_decision_status']
                    ?? null,

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? null,

                'executive_confidence' =>
                    $executiveState['executive_confidence']
                    ?? null,

                'executive_final_governance_decision_score' =>
                    $executiveState['executive_final_governance_decision_score']
                    ?? null,

                'management_escalation_recommended' =>
                    $executiveState['management_escalation_recommended']
                    ?? false,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'final_governance_decision_recorded' =>
                    $finalGovernanceDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'final_governance_confirmation_completed' =>
                    $finalGovernanceConfirmationCompleted,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_67_guardrails' =>
                $step67Guardrails,
        ];
    }
}