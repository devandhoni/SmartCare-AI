<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionEligibilitySafetyIntelligenceEngine
{
    public function analyze(?int $executionId = null): array
    {
        $execution = $executionId
            ? AIGovernanceStrategicPlanControlledActivationExecution::find($executionId)
            : AIGovernanceStrategicPlanControlledActivationExecution::latest('id')->first();

        if (!$execution) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_EXECUTION_AVAILABLE',
                'message' => 'No Step 71 controlled activation execution record is available.',
            ];
        }

        $state = app(
            AIGovernanceStrategicPlanControlledActivationExecutionStateIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_STATE_INTELLIGENCE_UNAVAILABLE',
                'source_state_result' => $state,
            ];
        }

        $conditionRestriction = app(
            AIGovernanceStrategicPlanControlledActivationExecutionConditionRestrictionIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'source_condition_restriction_result' => $conditionRestriction,
            ];
        }

        $executionState = $state['execution_state'];
        $conditionState = $conditionRestriction['condition_restriction_state'];
        $sourceAuthorization = $state['source_execution_authorization_context'];
        $executionAttribution = $state['execution_attribution_context'];

        $sourceAuthorizationValid =
            (bool) ($sourceAuthorization['source_execution_authorization_valid'] ?? false);

        $controlledActivationExecuted =
            (bool) ($executionAttribution['controlled_activation_executed'] ?? false);

        $executionAttributionComplete =
            (bool) ($executionAttribution['execution_attribution_complete'] ?? false);

        $blockingExecutionConditions =
            (int) ($conditionState['blocking_execution_conditions'] ?? 0);

        $criticalOpenConditions =
            (int) ($conditionState['critical_open_conditions'] ?? 0);

        $materialRestrictions =
            (int) ($conditionState['material_restrictions'] ?? 0);

        $criticalRestrictions =
            (int) ($conditionState['critical_restrictions'] ?? 0);

        $combinedResolutionScore =
            (float) ($conditionState['combined_resolution_score'] ?? 0);

        $combinedPressureScore =
            (float) ($conditionState['combined_condition_restriction_pressure_score'] ?? 0);

        $executionRiskScore =
            (float) ($executionState['execution_risk_score'] ?? 100);

        $executionRiskLevel =
            $executionState['execution_risk_level']
            ?? 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RISK';

        $executionReadinessScore =
            (float) ($executionState['execution_readiness_score'] ?? 0);

        $executionProgressionBlocked =
            (bool) ($executionState['execution_progression_blocked'] ?? true);

        /*
         * Eligibility is deliberately distinct from authority.
         * A record may be eligible for authorized-human review while still
         * being completely ineligible for execution completion.
         */
        $authorizedHumanExecutionReviewEligibility =
            !$controlledActivationExecuted
                ? 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
                : 'NOT_APPLICABLE_EXECUTION_ALREADY_RECORDED';

        $executionCompletionEligibility =
            $sourceAuthorizationValid
            && $blockingExecutionConditions === 0
            && $materialRestrictions === 0
            && !$controlledActivationExecuted
                ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION'
                : 'NOT_ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION';

        $unrestrictedExecutionEligibility =
            $sourceAuthorizationValid
            && $blockingExecutionConditions === 0
            && $criticalOpenConditions === 0
            && $materialRestrictions === 0
            && $criticalRestrictions === 0
            && !$executionProgressionBlocked
            && !$controlledActivationExecuted
                ? 'ELIGIBLE_FOR_UNRESTRICTED_CONTROLLED_ACTIVATION_EXECUTION'
                : 'NOT_ELIGIBLE_FOR_UNRESTRICTED_CONTROLLED_ACTIVATION_EXECUTION';

        $conditionalExecutionConsiderationEligibility =
            !$controlledActivationExecuted
                ? 'ELIGIBLE_FOR_CONDITIONAL_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
                : 'NOT_APPLICABLE_EXECUTION_ALREADY_RECORDED';

        $executionDeferralEligibility =
            !$controlledActivationExecuted
                ? 'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_DEFERRAL'
                : 'NOT_APPLICABLE_EXECUTION_ALREADY_RECORDED';

        $additionalResolutionEligibility =
            (
                $blockingExecutionConditions > 0
                || $materialRestrictions > 0
                || !$sourceAuthorizationValid
            )
                ? 'ELIGIBLE_FOR_ADDITIONAL_CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_RESOLUTION_REQUEST'
                : 'NO_ADDITIONAL_EXECUTION_RESOLUTION_REQUEST_CURRENTLY_REQUIRED';

        $postExecutionValidationEligibility =
            $controlledActivationExecuted
                ? 'ELIGIBLE_FOR_POST_EXECUTION_HUMAN_VALIDATION'
                : 'NOT_ELIGIBLE_FOR_POST_EXECUTION_VALIDATION_BEFORE_EXECUTION';

        $executionEligibilityScore = $this->eligibilityScore(
            $sourceAuthorizationValid,
            $blockingExecutionConditions,
            $criticalOpenConditions,
            $materialRestrictions,
            $criticalRestrictions,
            $executionReadinessScore,
            $combinedResolutionScore,
            $controlledActivationExecuted
        );

        $safetyViolations = [];

        /*
         * Safety violations represent architecture/control violations,
         * not ordinary blockers.
         */
        if ((bool) $execution->automatic_execution_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_CONTROLLED_ACTIVATION_EXECUTION_ENABLED';
        }

        if ((bool) $execution->automatic_activation_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_STRATEGIC_PLAN_ACTIVATION_ENABLED';
        }

        if ((bool) $execution->automatic_change_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_AI_OR_SYSTEM_CHANGE_ENABLED';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_DEPLOYMENT_ENABLED';
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_ROLLBACK_ENABLED';
        }

        if ((bool) $execution->automatic_clinical_action_allowed) {
            $safetyViolations[] =
                'AUTOMATIC_CLINICAL_ACTION_ENABLED';
        }

        if (!(bool) $execution->authorized_human_execution_required) {
            $safetyViolations[] =
                'AUTHORIZED_HUMAN_EXECUTION_REQUIREMENT_DISABLED';
        }

        if (!(bool) $execution->authorized_human_execution_authorization_required) {
            $safetyViolations[] =
                'AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_REQUIREMENT_DISABLED';
        }

        if (!(bool) $execution->post_execution_human_validation_required) {
            $safetyViolations[] =
                'POST_EXECUTION_HUMAN_VALIDATION_REQUIREMENT_DISABLED';
        }

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            $safetyViolations[] =
                'CONTROLLED_ACTIVATION_EXECUTED_WITH_INCOMPLETE_ATTRIBUTION';
        }

        if ($controlledActivationExecuted && !$sourceAuthorizationValid) {
            $safetyViolations[] =
                'CONTROLLED_ACTIVATION_EXECUTED_WITHOUT_VALID_SOURCE_EXECUTION_AUTHORIZATION';
        }

        $governanceIntegrityIntact = count($safetyViolations) === 0;

        $executionSafetyLevel = match (true) {
            !$governanceIntegrityIntact
                => 'CRITICAL',

            $criticalOpenConditions > 0 || $criticalRestrictions > 0
                => 'CRITICAL',

            $blockingExecutionConditions > 0 || $materialRestrictions > 0
                => 'HIGH',

            $executionRiskScore >= 70
                => 'HIGH',

            default
                => 'CONTROLLED',
        };

        $executionSafetyStatus = match ($executionSafetyLevel) {
            'CRITICAL'
                => 'CONTROLLED_WITH_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',

            'HIGH'
                => 'CONTROLLED_WITH_MATERIAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',

            default
                => 'CONTROLLED_EXECUTION_GOVERNANCE_INTACT',
        };

        $executionSafetyScore = $this->safetyScore(
            $governanceIntegrityIntact,
            $sourceAuthorizationValid,
            $blockingExecutionConditions,
            $criticalOpenConditions,
            $materialRestrictions,
            $criticalRestrictions,
            $executionRiskScore,
            $combinedPressureScore,
            $controlledActivationExecuted,
            $executionAttributionComplete
        );

        $executionProgressionAllowed =
            $executionCompletionEligibility ===
                'ELIGIBLE_FOR_CONTROLLED_ACTIVATION_EXECUTION_COMPLETION'
            && $executionSafetyScore >= 80
            && $governanceIntegrityIntact
            && !$controlledActivationExecuted;

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_ELIGIBILITY_SAFETY_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'execution_eligibility_state' => [
                'authorized_human_controlled_activation_execution_review_eligibility'
                    => $authorizedHumanExecutionReviewEligibility,

                'controlled_activation_execution_completion_eligibility'
                    => $executionCompletionEligibility,

                'unrestricted_controlled_activation_execution_eligibility'
                    => $unrestrictedExecutionEligibility,

                'conditional_controlled_activation_execution_consideration_eligibility'
                    => $conditionalExecutionConsiderationEligibility,

                'controlled_activation_execution_deferral_eligibility'
                    => $executionDeferralEligibility,

                'request_additional_execution_resolution_eligibility'
                    => $additionalResolutionEligibility,

                'post_execution_validation_eligibility'
                    => $postExecutionValidationEligibility,

                'execution_eligibility_score'
                    => $executionEligibilityScore,

                'execution_progression_readiness'
                    => $executionState['execution_readiness'],

                'execution_progression_blocked'
                    => !$executionProgressionAllowed,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,
            ],

            'execution_safety_state' => [
                'execution_safety_status'
                    => $executionSafetyStatus,

                'execution_safety_level'
                    => $executionSafetyLevel,

                'execution_safety_score'
                    => $executionSafetyScore,

                'governance_integrity_intact'
                    => $governanceIntegrityIntact,

                'safety_violation_count'
                    => count($safetyViolations),

                'critical_open_conditions'
                    => $criticalOpenConditions,

                'blocking_execution_conditions'
                    => $blockingExecutionConditions,

                'material_restrictions'
                    => $materialRestrictions,

                'critical_restrictions'
                    => $criticalRestrictions,

                'execution_risk_level'
                    => $executionRiskLevel,

                'execution_risk_score'
                    => $executionRiskScore,

                'combined_resolution_score'
                    => $combinedResolutionScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,

                'immediate_human_intervention_required'
                    => $executionSafetyLevel === 'CRITICAL',

                'human_management_attention_required'
                    => in_array($executionSafetyLevel, ['CRITICAL', 'HIGH'], true),
            ],

            'execution_state_context'
                => $executionState,

            'condition_restriction_context'
                => $conditionState,

            'source_execution_authorization_context'
                => $sourceAuthorization,

            'execution_attribution_context'
                => $executionAttribution,

            'safety_violations'
                => $safetyViolations,

            'eligibility_safety_findings' => [
                "Controlled activation execution eligibility and safety intelligence is based on Step 71 execution record {$execution->id}.",
                "Current execution completion eligibility is {$executionCompletionEligibility}.",
                "Current unrestricted execution eligibility is {$unrestrictedExecutionEligibility}.",
                "Current execution eligibility score is {$executionEligibilityScore}.",
                "Current controlled activation execution safety status is {$executionSafetyStatus}.",
                "Current controlled activation execution safety level is {$executionSafetyLevel}.",
                "Current controlled activation execution safety score is {$executionSafetyScore}.",
                'Governance integrity remains '.($governanceIntegrityIntact ? 'INTACT' : 'VIOLATED').'.',
                count($safetyViolations).' governance safety control violation(s) are currently detected.',
                "{$blockingExecutionConditions} condition(s) currently block controlled activation execution.",
                "{$criticalOpenConditions} critical execution condition(s) remain open.",
                "{$materialRestrictions} material controlled activation execution restriction(s) remain.",
                "{$criticalRestrictions} critical controlled activation execution restriction(s) remain.",
                "Current execution risk is {$executionRiskLevel} with score {$executionRiskScore}.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Step 71.5 eligibility and safety intelligence does not authorize or perform controlled activation execution.',
            ],

            'management_priorities' => $this->managementPriorities(
                $sourceAuthorizationValid,
                $blockingExecutionConditions,
                $criticalOpenConditions,
                $materialRestrictions,
                $criticalRestrictions,
                $executionSafetyScore,
                $executionRiskLevel,
                $executionRiskScore,
                $controlledActivationExecuted,
                $executionAttributionComplete,
                $governanceIntegrityIntact
            ),

            'controlled_activation_execution_eligibility_safety_guardrails'
                => $this->guardrails(),
        ];
    }

    private function eligibilityScore(
        bool $sourceAuthorizationValid,
        int $blockingExecutionConditions,
        int $criticalOpenConditions,
        int $materialRestrictions,
        int $criticalRestrictions,
        float $executionReadinessScore,
        float $combinedResolutionScore,
        bool $controlledActivationExecuted
    ): float {
        if ($controlledActivationExecuted) {
            return 0.0;
        }

        $score = 0.0;

        if ($sourceAuthorizationValid) {
            $score += 35;
        }

        if ($blockingExecutionConditions === 0) {
            $score += 20;
        }

        if ($criticalOpenConditions === 0) {
            $score += 10;
        }

        if ($materialRestrictions === 0) {
            $score += 15;
        }

        if ($criticalRestrictions === 0) {
            $score += 10;
        }

        $score += min(5, $executionReadinessScore * 0.05);
        $score += min(5, $combinedResolutionScore * 0.05);

        return round(min(100, $score), 2);
    }

    private function safetyScore(
        bool $governanceIntegrityIntact,
        bool $sourceAuthorizationValid,
        int $blockingExecutionConditions,
        int $criticalOpenConditions,
        int $materialRestrictions,
        int $criticalRestrictions,
        float $executionRiskScore,
        float $combinedPressureScore,
        bool $controlledActivationExecuted,
        bool $executionAttributionComplete
    ): float {
        if (!$governanceIntegrityIntact) {
            return 0.0;
        }

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            return 0.0;
        }

        $score = 100.0;

        if (!$sourceAuthorizationValid) {
            $score -= 30;
        }

        $score -= min(25, $blockingExecutionConditions * 6.25);
        $score -= min(20, $criticalOpenConditions * 7.5);
        $score -= min(20, $criticalRestrictions * 7.5);
        $score -= min(15, $materialRestrictions * 2.5);

        $score -= min(20, $executionRiskScore * 0.20);
        $score -= min(20, $combinedPressureScore * 0.20);

        return round(max(0, $score), 2);
    }

    private function managementPriorities(
        bool $sourceAuthorizationValid,
        int $blockingExecutionConditions,
        int $criticalOpenConditions,
        int $materialRestrictions,
        int $criticalRestrictions,
        float $executionSafetyScore,
        string $executionRiskLevel,
        float $executionRiskScore,
        bool $controlledActivationExecuted,
        bool $executionAttributionComplete,
        bool $governanceIntegrityIntact
    ): array {
        $priorities = [];

        if (!$sourceAuthorizationValid) {
            $priorities[] =
                'Complete and validate explicitly authorized-human execution authorization before controlled activation execution progression.';
        }

        if ($criticalOpenConditions > 0) {
            $priorities[] =
                "Escalate {$criticalOpenConditions} critical controlled activation execution condition(s) for immediate authorized-human governance review.";
        }

        if ($criticalRestrictions > 0) {
            $priorities[] =
                "Maintain immediate authorized-human governance treatment for {$criticalRestrictions} critical controlled activation execution restriction(s).";
        }

        if ($blockingExecutionConditions > 0) {
            $priorities[] =
                "Resolve or formally govern {$blockingExecutionConditions} condition(s) blocking controlled activation execution.";
        }

        if ($materialRestrictions > 0) {
            $priorities[] =
                "Maintain explicit governance treatment for {$materialRestrictions} material controlled activation execution restriction(s).";
        }

        if ($executionSafetyScore < 80) {
            $priorities[] =
                "Do not permit controlled activation execution while execution safety score remains {$executionSafetyScore}.";
        }

        if ($executionRiskScore >= 90) {
            $priorities[] =
                "Maintain critical authorized-human governance oversight while execution risk remains {$executionRiskLevel} with score {$executionRiskScore}.";
        }

        if (!$governanceIntegrityIntact) {
            $priorities[] =
                'Immediately correct execution governance safety-control violations before any progression.';
        }

        if (!$controlledActivationExecuted) {
            $priorities[] =
                'Keep controlled activation execution pending until all execution eligibility, safety, authorization, and attribution requirements are satisfied.';
        }

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            $priorities[] =
                'Immediately complete human executor attribution and required post-execution governance validation.';
        }

        $priorities[] =
            'Do not interpret eligibility, safety, readiness, risk, resolution, or pressure scores as authority to execute controlled activation.';

        $priorities[] =
            'Preserve actual controlled activation execution as a separately governed authorized-human action.';

        return $priorities;
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_eligibility_safety_intelligence_enabled'
                => true,

            'eligibility_safety_intelligence_is_execution'
                => false,

            'eligibility_safety_intelligence_is_execution_authorization'
                => false,

            'eligibility_safety_intelligence_makes_execution_decision'
                => false,

            'eligibility_safety_intelligence_records_execution_decision'
                => false,

            'eligibility_safety_intelligence_executes_controlled_activation'
                => false,

            'eligibility_safety_intelligence_activates_strategic_plan'
                => false,

            'eligibility_safety_intelligence_changes_execution_status'
                => false,

            'eligibility_safety_intelligence_changes_execution_outcome'
                => false,

            'eligibility_safety_intelligence_resolves_conditions'
                => false,

            'eligibility_safety_intelligence_waives_conditions'
                => false,

            'eligibility_safety_intelligence_removes_restrictions'
                => false,

            'eligibility_safety_intelligence_validates_evidence'
                => false,

            'eligibility_score_authorizes_execution'
                => false,

            'safety_score_authorizes_execution'
                => false,

            'readiness_score_authorizes_execution'
                => false,

            'risk_score_authorizes_execution'
                => false,

            'eligibility_classification_authorizes_execution'
                => false,

            'safety_classification_authorizes_execution'
                => false,

            'eligibility_safety_intelligence_authorizes_ai_change'
                => false,

            'eligibility_safety_intelligence_authorizes_deployment'
                => false,

            'eligibility_safety_intelligence_authorizes_rollback'
                => false,

            'eligibility_safety_intelligence_authorizes_clinical_action'
                => false,

            'automatic_execution_allowed'
                => false,

            'automatic_execution_authorization_allowed'
                => false,

            'automatic_activation_allowed'
                => false,

            'automatic_condition_resolution_allowed'
                => false,

            'automatic_restriction_removal_allowed'
                => false,

            'automatic_evidence_validation_allowed'
                => false,

            'automatic_change_allowed'
                => false,

            'automatic_deployment_allowed'
                => false,

            'automatic_rollback_allowed'
                => false,

            'automatic_clinical_action_allowed'
                => false,

            'execution_authorization_required_before_execution'
                => true,

            'actual_execution_authority_reserved_for_authorized_human'
                => true,

            'post_execution_human_validation_required'
                => true,

            'message' =>
                'Step 71.5 controlled activation execution eligibility and safety intelligence evaluates authorized-human review eligibility, execution completion eligibility, unrestricted execution eligibility, conditional review, deferral, additional-resolution needs, post-execution validation eligibility, safety status, safety level, safety score, governance integrity, source execution authorization, execution blockers, restrictions, readiness, risk, resolution, pressure, and execution attribution. Eligibility or safety intelligence cannot authorize or perform controlled activation execution. Actual execution remains a separate authority reserved for explicitly authorized human governance.',
        ];
    }
}