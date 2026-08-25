<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationEligibilitySafetyIntelligenceEngine
{
    /**
     * Analyze Step 70 controlled activation execution authorization
     * eligibility and safety.
     *
     * This service is informational only.
     */
    public function analyze(?int $executionAuthorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 70 Record
        |--------------------------------------------------------------------------
        */

        $authorization = $executionAuthorizationId
            ? AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::find(
                $executionAuthorizationId
            )
            : AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'analysis_completed' => false,
                'status' =>
                    'NO_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_AVAILABLE',
                'message' =>
                    'No strategic plan controlled activation execution authorization record is available for eligibility and safety intelligence analysis.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 70.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $state =
            app(
                AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationStateIntelligenceEngine::class
            )->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' =>
                    'STEP_70_EXECUTION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Execution authorization eligibility and safety intelligence requires Step 70 execution authorization state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 70.4 Condition / Restriction Intelligence
        |--------------------------------------------------------------------------
        */

        $conditionRestriction =
            app(
                AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationConditionRestrictionIntelligenceEngine::class
            )->analyze($authorization->id);

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' =>
                    'STEP_70_EXECUTION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Execution authorization eligibility and safety intelligence requires Step 70 condition and restriction intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Contexts
        |--------------------------------------------------------------------------
        */

        $executionState =
            $state['execution_authorization_state']
            ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state']
            ?? [];

        $conditionSummary =
            $conditionRestriction['condition_summary']
            ?? [];

        $restrictionSummary =
            $conditionRestriction['restriction_summary']
            ?? [];

        $evidenceSummary =
            $state['evidence_summary']
            ?? [];

        $sourceAuthorizationContext =
            $state['source_activation_authorization_context']
            ?? [];

        $executionAuthorizationContext =
            $state['execution_authorization_context']
            ?? [];

        $actualExecutionContext =
            $state['actual_execution_context']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Requirements
        |--------------------------------------------------------------------------
        */

        $sourceActivationAuthorizationDecisionRecorded =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_decision_recorded'
                ]
                ?? false
            );

        $sourceActivationAuthorizationMadeByAuthorizedHuman =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_made_by_authorized_human'
                ]
                ?? false
            );

        $sourceActivationAuthorizationAttributionComplete =
            (bool) (
                $sourceAuthorizationContext[
                    'source_activation_authorization_attribution_complete'
                ]
                ?? false
            );

        $sourceControlledActivationAuthorizationCompleted =
            (bool) (
                $sourceAuthorizationContext[
                    'source_controlled_activation_authorization_completed'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Step 70 Human Execution Authorization State
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationDecisionRecorded =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_decision_recorded'
                ]
                ?? false
            );

        $executionAuthorizationMadeByAuthorizedHuman =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_made_by_authorized_human'
                ]
                ?? false
            );

        $executionAuthorizationAttributionComplete =
            (bool) (
                $executionAuthorizationContext[
                    'execution_authorization_attribution_complete'
                ]
                ?? false
            );

        $executionAuthorizationCompleted =
            (bool) (
                $executionAuthorizationContext[
                    'controlled_activation_execution_authorization_completed'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Actual Execution State
        |--------------------------------------------------------------------------
        */

        $controlledActivationExecuted =
            (bool) (
                $actualExecutionContext[
                    'controlled_activation_executed'
                ]
                ?? false
            );

        $actualExecutionAttributionComplete =
            (bool) (
                $actualExecutionContext[
                    'actual_execution_attribution_complete'
                ]
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Counts
        |--------------------------------------------------------------------------
        */

        $blockingExecutionAuthorizationConditions =
            (int) (
                $conditionSummary[
                    'blocking_execution_authorization_conditions'
                ]
                ?? 0
            );

        $blockingActualExecutionConditions =
            (int) (
                $conditionSummary[
                    'blocking_actual_execution_conditions'
                ]
                ?? 0
            );

        $criticalOpenConditions =
            (int) (
                $conditionSummary[
                    'critical_open_conditions'
                ]
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $restrictionSummary[
                    'material_restrictions'
                ]
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionSummary[
                    'critical_restrictions'
                ]
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence
        |--------------------------------------------------------------------------
        */

        $outstandingEvidenceItems =
            (int) (
                $evidenceSummary[
                    'outstanding_evidence_items'
                ]
                ?? 0
            );

        $blockingExecutionEvidenceItems =
            (int) (
                $evidenceSummary[
                    'blocking_execution_evidence_items'
                ]
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $executionReadinessScore =
            $this->score(
                $executionState[
                    'execution_readiness_score'
                ]
                ?? $authorization->execution_readiness_score
            );

        $executionRiskScore =
            $this->score(
                $executionState[
                    'execution_risk_score'
                ]
                ?? $authorization->execution_risk_score
            );

        $conditionResolutionScore =
            $this->score(
                $conditionState[
                    'condition_resolution_score'
                ]
                ?? $authorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $conditionState[
                    'evidence_resolution_score'
                ]
                ?? $authorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $conditionState[
                    'combined_resolution_score'
                ]
                ?? $authorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $conditionState[
                    'condition_pressure_score'
                ]
                ?? $authorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $conditionState[
                    'restriction_pressure_score'
                ]
                ?? $authorization->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $conditionState[
                    'combined_condition_restriction_pressure_score'
                ]
                ?? $authorization->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Review Eligibility
        |--------------------------------------------------------------------------
        |
        | Human review eligibility may be available even when completion is
        | prohibited. Review permission is not authorization.
        |
        */

        $authorizedHumanExecutionAuthorizationReviewEligibility =
            'ELIGIBLE_FOR_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Completion Eligibility
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationCompletionEligible =
            $sourceActivationAuthorizationDecisionRecorded
            && $sourceActivationAuthorizationMadeByAuthorizedHuman
            && $sourceActivationAuthorizationAttributionComplete
            && $sourceControlledActivationAuthorizationCompleted
            && $blockingExecutionAuthorizationConditions === 0
            && $criticalOpenConditions === 0
            && $criticalRestrictions === 0
            && $blockingExecutionEvidenceItems === 0;

        $executionAuthorizationCompletionEligibility =
            $executionAuthorizationCompletionEligible
                ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_COMPLETION'
                : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_COMPLETION';

        /*
        |--------------------------------------------------------------------------
        | Unrestricted Authorization Eligibility
        |--------------------------------------------------------------------------
        */

        $unrestrictedExecutionAuthorizationEligible =
            $executionAuthorizationCompletionEligible
            && $materialRestrictions === 0
            && $outstandingEvidenceItems === 0
            && $executionRiskScore < 50
            && $executionReadinessScore >= 75;

        $unrestrictedExecutionAuthorizationEligibility =
            $unrestrictedExecutionAuthorizationEligible
                ? 'ELIGIBLE_FOR_UNRESTRICTED_EXECUTION_AUTHORIZATION'
                : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_EXECUTION_AUTHORIZATION';

        /*
        |--------------------------------------------------------------------------
        | Conditional Review Eligibility
        |--------------------------------------------------------------------------
        */

        $conditionalExecutionAuthorizationConsiderationEligibility =
            !$unrestrictedExecutionAuthorizationEligible
                ? 'ELIGIBLE_FOR_CONDITIONAL_EXECUTION_AUTHORIZATION_REVIEW'
                : 'NOT_REQUIRED';

        /*
        |--------------------------------------------------------------------------
        | Deferral / Additional Resolution
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationDeferralEligibility =
            'ELIGIBLE_FOR_EXECUTION_AUTHORIZATION_DEFERRAL';

        $requestAdditionalExecutionResolutionEligibility =
            (
                $blockingExecutionAuthorizationConditions > 0
                || $criticalOpenConditions > 0
                || $materialRestrictions > 0
                || $criticalRestrictions > 0
                || $outstandingEvidenceItems > 0
            )
                ? 'ELIGIBLE_FOR_ADDITIONAL_EXECUTION_GOVERNANCE_RESOLUTION_REQUEST'
                : 'NOT_REQUIRED';

        /*
        |--------------------------------------------------------------------------
        | Actual Execution Review Eligibility
        |--------------------------------------------------------------------------
        |
        | Step 70 only authorizes execution. Actual execution remains separate.
        |
        */

        $actualExecutionReviewEligible =
            $executionAuthorizationDecisionRecorded
            && $executionAuthorizationMadeByAuthorizedHuman
            && $executionAuthorizationAttributionComplete
            && $executionAuthorizationCompleted
            && $blockingActualExecutionConditions === 0
            && $criticalRestrictions === 0
            && $blockingExecutionEvidenceItems === 0;

        $actualExecutionReviewEligibility =
            $actualExecutionReviewEligible
                ? 'ELIGIBLE_FOR_SEPARATE_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
                : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_REVIEW';

        $actualExecutionAuthorizationState =
            $actualExecutionReviewEligible
                ? 'VALID_HUMAN_EXECUTION_AUTHORIZATION_PRESENT_FOR_SEPARATE_EXECUTION_REVIEW'
                : 'NO_VALID_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_PRESENT';

        /*
        |--------------------------------------------------------------------------
        | Eligibility Score
        |--------------------------------------------------------------------------
        */

        $eligibilityScore =
            $this->calculateEligibilityScore(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $blockingExecutionAuthorizationConditions,
                $criticalOpenConditions,
                $criticalRestrictions,
                $materialRestrictions,
                $outstandingEvidenceItems,
                $blockingExecutionEvidenceItems,
                $executionReadinessScore,
                $combinedResolutionScore
            );

        /*
        |--------------------------------------------------------------------------
        | Safety Violations
        |--------------------------------------------------------------------------
        */

        $safetyViolations = [];

        if ($authorization->automatic_execution_authorization_allowed) {
            $safetyViolations[] =
                'Automatic execution authorization is enabled.';
        }

        if ($authorization->automatic_execution_allowed) {
            $safetyViolations[] =
                'Automatic controlled activation execution is enabled.';
        }

        if ($authorization->automatic_change_allowed) {
            $safetyViolations[] =
                'Automatic AI/system change is enabled.';
        }

        if ($authorization->automatic_deployment_allowed) {
            $safetyViolations[] =
                'Automatic deployment is enabled.';
        }

        if ($authorization->automatic_rollback_allowed) {
            $safetyViolations[] =
                'Automatic rollback is enabled.';
        }

        if ($authorization->automatic_clinical_action_allowed) {
            $safetyViolations[] =
                'Automatic clinical action is enabled.';
        }

        if (!$authorization->human_review_required) {
            $safetyViolations[] =
                'Human review requirement is disabled.';
        }

        if (!$authorization->controlled_activation_authorization_required) {
            $safetyViolations[] =
                'Controlled activation authorization requirement is disabled.';
        }

        if (!$authorization->authorized_human_execution_authorization_required) {
            $safetyViolations[] =
                'Authorized-human execution authorization requirement is disabled.';
        }

        if (!$authorization->authorized_human_execution_required) {
            $safetyViolations[] =
                'Authorized-human actual execution requirement is disabled.';
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            count($safetyViolations) === 0;

        /*
        |--------------------------------------------------------------------------
        | Safety Score
        |--------------------------------------------------------------------------
        */

        $safetyScore =
            $this->calculateSafetyScore(
                $governanceIntegrityIntact,
                $criticalOpenConditions,
                $criticalRestrictions,
                $blockingExecutionAuthorizationConditions,
                $blockingActualExecutionConditions,
                $materialRestrictions,
                $blockingExecutionEvidenceItems,
                $executionRiskScore,
                $combinedPressureScore
            );

        /*
        |--------------------------------------------------------------------------
        | Safety Level / Status
        |--------------------------------------------------------------------------
        */

        $safetyLevel =
            $this->determineSafetyLevel(
                $governanceIntegrityIntact,
                $criticalOpenConditions,
                $criticalRestrictions,
                $executionRiskScore,
                $blockingExecutionAuthorizationConditions
            );

        $safetyStatus =
            $this->determineSafetyStatus(
                $governanceIntegrityIntact,
                $criticalOpenConditions,
                $criticalRestrictions,
                $blockingExecutionAuthorizationConditions,
                $blockingActualExecutionConditions,
                $materialRestrictions
            );

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            !$governanceIntegrityIntact
            || $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $executionRiskScore >= 90;

        $humanManagementAttentionRequired =
            $immediateHumanInterventionRequired
            || $blockingExecutionAuthorizationConditions > 0
            || $blockingActualExecutionConditions > 0
            || $materialRestrictions > 0;

        /*
        |--------------------------------------------------------------------------
        | Progression
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationProgressionBlocked =
            !$executionAuthorizationCompletionEligible;

        $actualExecutionProgressionBlocked =
            !$actualExecutionReviewEligible;

        /*
        |--------------------------------------------------------------------------
        | Eligibility State
        |--------------------------------------------------------------------------
        */

        $eligibilityState = [
            'authorized_human_execution_authorization_review_eligibility' =>
                $authorizedHumanExecutionAuthorizationReviewEligibility,

            'controlled_activation_execution_authorization_completion_eligibility' =>
                $executionAuthorizationCompletionEligibility,

            'unrestricted_execution_authorization_eligibility' =>
                $unrestrictedExecutionAuthorizationEligibility,

            'conditional_execution_authorization_consideration_eligibility' =>
                $conditionalExecutionAuthorizationConsiderationEligibility,

            'execution_authorization_deferral_eligibility' =>
                $executionAuthorizationDeferralEligibility,

            'request_additional_execution_resolution_eligibility' =>
                $requestAdditionalExecutionResolutionEligibility,

            'actual_controlled_activation_execution_review_eligibility' =>
                $actualExecutionReviewEligibility,

            'actual_execution_authorization_state' =>
                $actualExecutionAuthorizationState,

            'execution_authorization_eligibility_score' =>
                $eligibilityScore,

            'execution_progression_readiness' =>
                $authorization->execution_readiness,

            'execution_authorization_progression_blocked' =>
                $executionAuthorizationProgressionBlocked,

            'actual_execution_progression_blocked' =>
                $actualExecutionProgressionBlocked,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety State
        |--------------------------------------------------------------------------
        */

        $safetyState = [
            'execution_authorization_safety_status' =>
                $safetyStatus,

            'execution_authorization_safety_level' =>
                $safetyLevel,

            'execution_authorization_safety_score' =>
                $safetyScore,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'safety_violation_count' =>
                count($safetyViolations),

            'critical_open_conditions' =>
                $criticalOpenConditions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'blocking_execution_authorization_conditions' =>
                $blockingExecutionAuthorizationConditions,

            'blocking_actual_execution_conditions' =>
                $blockingActualExecutionConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'outstanding_evidence_items' =>
                $outstandingEvidenceItems,

            'blocking_execution_evidence_items' =>
                $blockingExecutionEvidenceItems,

            'execution_risk_level' =>
                $authorization->execution_risk_level,

            'execution_risk_score' =>
                $executionRiskScore,

            'condition_pressure_score' =>
                $conditionPressureScore,

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safety Findings
        |--------------------------------------------------------------------------
        */

        $safetyFindings = [
            "Execution authorization eligibility and safety intelligence is based on Step 70 execution authorization record {$authorization->id}.",

            'Current execution authorization completion eligibility is '
                .$executionAuthorizationCompletionEligibility.'.',

            'Current unrestricted execution authorization eligibility is '
                .$unrestrictedExecutionAuthorizationEligibility.'.',

            'Current actual controlled activation execution review eligibility is '
                .$actualExecutionReviewEligibility.'.',

            "Current execution authorization eligibility score is {$eligibilityScore}.",

            "Current execution authorization safety status is {$safetyStatus}.",

            "Current execution authorization safety level is {$safetyLevel}.",

            "Current execution authorization safety score is {$safetyScore}.",

            'Governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            count($safetyViolations)
                .' governance safety control violation(s) are currently detected.',

            "{$blockingExecutionAuthorizationConditions} condition(s) block execution authorization progression.",

            "{$blockingActualExecutionConditions} condition(s) block actual controlled activation execution.",

            "{$criticalOpenConditions} critical execution authorization condition(s) remain open.",

            "{$materialRestrictions} material execution restriction(s) remain active.",

            "{$criticalRestrictions} critical execution restriction(s) remain active.",

            "{$outstandingEvidenceItems} execution evidence item(s) remain outstanding.",

            "{$blockingExecutionEvidenceItems} evidence item(s) currently block execution progression.",

            "Current execution readiness score is {$executionReadinessScore}.",

            "Current execution risk score is {$executionRiskScore}.",

            "Current combined resolution score is {$combinedResolutionScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            'Execution authorization progression remains '
                .($executionAuthorizationProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Actual controlled activation execution progression remains '
                .($actualExecutionProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Step 70.5 eligibility and safety intelligence does not authorize execution authorization or actual controlled activation execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceActivationAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'Record the required source controlled activation authorization decision through explicitly authorized human governance.';
        }

        if (!$sourceActivationAuthorizationMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure source controlled activation authorization is made by an explicitly authorized human governance authority.';
        }

        if (!$sourceActivationAuthorizationAttributionComplete) {
            $managementPriorities[] =
                'Complete source activation authorization attribution before execution authorization progression.';
        }

        if (!$sourceControlledActivationAuthorizationCompleted) {
            $managementPriorities[] =
                'Complete controlled activation authorization before execution authorization completion can be considered.';
        }

        if ($criticalOpenConditions > 0) {
            $managementPriorities[] =
                "Escalate {$criticalOpenConditions} critical execution authorization condition(s) for immediate authorized-human governance review.";
        }

        if ($criticalRestrictions > 0) {
            $managementPriorities[] =
                "Maintain immediate authorized-human governance treatment for {$criticalRestrictions} critical execution restriction(s).";
        }

        if ($blockingExecutionAuthorizationConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingExecutionAuthorizationConditions} condition(s) blocking execution authorization progression.";
        }

        if ($blockingActualExecutionConditions > 0) {
            $managementPriorities[] =
                "Keep actual controlled activation execution prohibited while {$blockingActualExecutionConditions} execution-blocking condition(s) remain.";
        }

        if (!$executionAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'A separate explicitly authorized human execution authorization decision remains required.';
        }

        if (!$executionAuthorizationCompleted) {
            $managementPriorities[] =
                'Do not complete execution authorization until all completion requirements are satisfied.';
        }

        if (!$controlledActivationExecuted) {
            $managementPriorities[] =
                'Maintain strict separation between execution authorization and actual controlled activation execution.';
        }

        if (!$actualExecutionAttributionComplete) {
            $managementPriorities[] =
                'Actual execution attribution must remain complete and independently governed if execution occurs in the future execution layer.';
        }

        $managementPriorities[] =
            'Do not interpret eligibility, safety, readiness, confidence, risk, resolution, pressure, or evidence scores as authorization to execute controlled activation.';

        $managementPriorities[] =
            'Preserve human governance authority, authorization attribution, execution attribution, evidence integrity, source traceability, safety controls, and authority separation throughout execution progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_authorization_id' =>
                $authorization->id,

            'execution_authorization_code' =>
                $authorization->execution_authorization_code,

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->strategic_plan_controlled_activation_authorization_id,

            'strategic_plan_governance_decision_confirmation_id' =>
                $authorization->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id' =>
                $authorization->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $authorization->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $authorization->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $authorization->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $authorization->strategic_plan_id,

            'strategic_snapshot_id' =>
                $authorization->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $authorization->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $authorization->lifecycle_snapshot_id,

            'decision_scope' =>
                $authorization->decision_scope,

            'resident_id' =>
                $authorization->resident_id,

            'execution_eligibility_state' =>
                $eligibilityState,

            'execution_safety_state' =>
                $safetyState,

            'execution_state_context' =>
                $executionState,

            'condition_restriction_context' =>
                $conditionState,

            'source_activation_authorization_context' =>
                $sourceAuthorizationContext,

            'execution_authorization_context' =>
                $executionAuthorizationContext,

            'actual_execution_context' =>
                $actualExecutionContext,

            'safety_violations' =>
                $safetyViolations,

            'eligibility_safety_findings' =>
                $safetyFindings,

            'management_priorities' =>
                array_values(
                    array_unique(
                        $managementPriorities
                    )
                ),

            'execution_authorization_eligibility_safety_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Eligibility Score
    |--------------------------------------------------------------------------
    */

    private function calculateEligibilityScore(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $authorizationBlockers,
        int $criticalOpenConditions,
        int $criticalRestrictions,
        int $materialRestrictions,
        int $outstandingEvidence,
        int $blockingEvidence,
        float $readinessScore,
        float $combinedResolutionScore
    ): float {
        $score = 0.0;

        if ($sourceDecisionRecorded) {
            $score += 12.5;
        }

        if ($sourceMadeByAuthorizedHuman) {
            $score += 12.5;
        }

        if ($sourceAttributionComplete) {
            $score += 10;
        }

        if ($sourceAuthorizationCompleted) {
            $score += 15;
        }

        if ($authorizationBlockers === 0) {
            $score += 15;
        }

        if ($criticalOpenConditions === 0) {
            $score += 7.5;
        }

        if ($criticalRestrictions === 0) {
            $score += 7.5;
        }

        if ($materialRestrictions === 0) {
            $score += 5;
        }

        if ($outstandingEvidence === 0) {
            $score += 5;
        }

        if ($blockingEvidence === 0) {
            $score += 5;
        }

        $score += min(
            2.5,
            $readinessScore * 0.025
        );

        $score += min(
            2.5,
            $combinedResolutionScore * 0.025
        );

        return $this->score($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Safety Score
    |--------------------------------------------------------------------------
    */

    private function calculateSafetyScore(
        bool $governanceIntegrityIntact,
        int $criticalOpenConditions,
        int $criticalRestrictions,
        int $authorizationBlockers,
        int $actualExecutionBlockers,
        int $materialRestrictions,
        int $blockingEvidence,
        float $executionRiskScore,
        float $combinedPressureScore
    ): float {
        if (!$governanceIntegrityIntact) {
            return 0.0;
        }

        $score = 100.0;

        $score -= min(
            30,
            $criticalOpenConditions * 5
        );

        $score -= min(
            30,
            $criticalRestrictions * 5
        );

        $score -= min(
            15,
            $authorizationBlockers * 1.5
        );

        $score -= min(
            10,
            $actualExecutionBlockers
        );

        $score -= min(
            10,
            $materialRestrictions
        );

        $score -= min(
            10,
            $blockingEvidence * 5
        );

        $score -= $executionRiskScore * 0.15;

        $score -= $combinedPressureScore * 0.10;

        return $this->score($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Safety Level
    |--------------------------------------------------------------------------
    */

    private function determineSafetyLevel(
        bool $governanceIntegrityIntact,
        int $criticalOpenConditions,
        int $criticalRestrictions,
        float $riskScore,
        int $authorizationBlockers
    ): string {
        if (!$governanceIntegrityIntact) {
            return 'GOVERNANCE_CONTROL_FAILURE';
        }

        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $riskScore >= 90
        ) {
            return 'CRITICAL';
        }

        if (
            $riskScore >= 75
            || $authorizationBlockers > 0
        ) {
            return 'HIGH';
        }

        if ($riskScore >= 50) {
            return 'MODERATE';
        }

        if ($riskScore >= 25) {
            return 'ELEVATED';
        }

        return 'CONTROLLED';
    }

    /*
    |--------------------------------------------------------------------------
    | Safety Status
    |--------------------------------------------------------------------------
    */

    private function determineSafetyStatus(
        bool $governanceIntegrityIntact,
        int $criticalOpenConditions,
        int $criticalRestrictions,
        int $authorizationBlockers,
        int $actualExecutionBlockers,
        int $materialRestrictions
    ): string {
        if (!$governanceIntegrityIntact) {
            return 'EXECUTION_AUTHORIZATION_GOVERNANCE_CONTROL_FAILURE';
        }

        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
        ) {
            return 'CONTROLLED_WITH_CRITICAL_EXECUTION_AUTHORIZATION_RESTRICTIONS';
        }

        if (
            $authorizationBlockers > 0
            || $actualExecutionBlockers > 0
            || $materialRestrictions > 0
        ) {
            return 'CONTROLLED_WITH_MATERIAL_EXECUTION_AUTHORIZATION_RESTRICTIONS';
        }

        return 'CONTROLLED_EXECUTION_AUTHORIZATION_GOVERNANCE_STATE';
    }

    /*
    |--------------------------------------------------------------------------
    | Score Helper
    |--------------------------------------------------------------------------
    */

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Step 70.5 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_eligibility_safety_intelligence_enabled' =>
                true,

            'eligibility_safety_intelligence_is_execution_authorization' =>
                false,

            'eligibility_safety_intelligence_is_actual_execution' =>
                false,

            'eligibility_safety_intelligence_makes_execution_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_records_execution_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_completes_execution_authorization' =>
                false,

            'eligibility_safety_intelligence_executes_controlled_activation' =>
                false,

            'eligibility_safety_intelligence_activates_strategic_plan' =>
                false,

            'eligibility_safety_intelligence_changes_source_activation_authorization' =>
                false,

            'eligibility_safety_intelligence_changes_execution_authorization_status' =>
                false,

            'eligibility_safety_intelligence_changes_execution_authorization_decision' =>
                false,

            'eligibility_safety_intelligence_changes_execution_authorization_outcome' =>
                false,

            'eligibility_safety_intelligence_changes_execution_status' =>
                false,

            'eligibility_safety_intelligence_resolves_conditions' =>
                false,

            'eligibility_safety_intelligence_waives_conditions' =>
                false,

            'eligibility_safety_intelligence_removes_restrictions' =>
                false,

            'eligibility_safety_intelligence_validates_evidence' =>
                false,

            'eligibility_score_authorizes_execution_authorization' =>
                false,

            'eligibility_score_authorizes_actual_execution' =>
                false,

            'safety_score_authorizes_execution_authorization' =>
                false,

            'safety_score_authorizes_actual_execution' =>
                false,

            'readiness_score_authorizes_execution_authorization' =>
                false,

            'readiness_score_authorizes_actual_execution' =>
                false,

            'risk_score_authorizes_execution_authorization' =>
                false,

            'risk_score_authorizes_actual_execution' =>
                false,

            'eligibility_classification_authorizes_execution' =>
                false,

            'safety_classification_authorizes_execution' =>
                false,

            'eligibility_safety_intelligence_authorizes_ai_change' =>
                false,

            'eligibility_safety_intelligence_authorizes_execution' =>
                false,

            'eligibility_safety_intelligence_authorizes_deployment' =>
                false,

            'eligibility_safety_intelligence_authorizes_rollback' =>
                false,

            'eligibility_safety_intelligence_authorizes_clinical_action' =>
                false,

            'eligibility_safety_intelligence_overrides_human_review' =>
                false,

            'eligibility_safety_intelligence_overrides_activation_authorization' =>
                false,

            'eligibility_safety_intelligence_overrides_execution_authorization' =>
                false,

            'eligibility_safety_intelligence_overrides_actual_execution_authority' =>
                false,

            'automatic_execution_authorization_allowed' =>
                false,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_confirmation_allowed' =>
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

            'execution_authorization_authority_reserved_for_authorized_human' =>
                true,

            'actual_execution_authority_separate_from_execution_authorization' =>
                true,

            'actual_execution_authority_reserved_for_authorized_human' =>
                true,

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'controlled_activation_authorization_required' =>
                true,

            'authorized_human_execution_authorization_required' =>
                true,

            'authorized_human_execution_required' =>
                true,

            'message' =>
                'Step 70.5 controlled activation execution authorization eligibility and safety intelligence evaluates authorized-human review eligibility, completion eligibility, unrestricted eligibility, conditional review eligibility, deferral eligibility, additional-resolution eligibility, separate actual-execution review eligibility, safety status, safety level, safety score, governance integrity, source activation authorization dependencies, conditions, restrictions, evidence, readiness, risk, and progression blocking. Eligibility or safety classifications do not authorize execution authorization or actual controlled activation execution. This intelligence does not make, record, or complete an execution authorization decision; execute controlled activation; activate the strategic plan; resolve conditions; waive requirements; remove restrictions; validate evidence; modify AI behavior; deploy updates; trigger rollback; or initiate clinical action. Execution authorization and actual controlled activation execution remain separate explicitly authorized human governance authorities.',
        ];
    }
}