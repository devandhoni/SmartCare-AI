<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionExecutiveIntelligenceEngine
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

        $eligibilitySafety = app(
            AIGovernanceStrategicPlanControlledActivationExecutionEligibilitySafetyIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'source_eligibility_safety_result' => $eligibilitySafety,
            ];
        }

        $recommendation = app(
            AIGovernanceStrategicPlanControlledActivationExecutionRecommendationIntelligenceEngine::class
        )->analyze($execution->id);

        if (!($recommendation['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'source_recommendation_result' => $recommendation,
            ];
        }

        $executionState = $state['execution_state'];
        $conditionState = $conditionRestriction['condition_restriction_state'];
        $eligibilityState = $eligibilitySafety['execution_eligibility_state'];
        $safetyState = $eligibilitySafety['execution_safety_state'];
        $recommendationState = $recommendation['recommendation_state'];
        $topRecommendation = $recommendation['top_recommendation'];
        $sourceAuthorization = $state['source_execution_authorization_context'];
        $executionAttribution = $state['execution_attribution_context'];

        $executionReadinessScore =
            (float) ($executionState['execution_readiness_score'] ?? 0);

        $executionRiskScore =
            (float) ($executionState['execution_risk_score'] ?? 100);

        $executionEligibilityScore =
            (float) ($eligibilityState['execution_eligibility_score'] ?? 0);

        $executionSafetyScore =
            (float) ($safetyState['execution_safety_score'] ?? 0);

        $combinedResolutionScore =
            (float) ($conditionState['combined_resolution_score'] ?? 0);

        $combinedPressureScore =
            (float) ($conditionState['combined_condition_restriction_pressure_score'] ?? 0);

        $blockingExecutionConditions =
            (int) ($conditionState['blocking_execution_conditions'] ?? 0);

        $criticalOpenConditions =
            (int) ($conditionState['critical_open_conditions'] ?? 0);

        $materialRestrictions =
            (int) ($conditionState['material_restrictions'] ?? 0);

        $criticalRestrictions =
            (int) ($conditionState['critical_restrictions'] ?? 0);

        $criticalRecommendations =
            (int) ($recommendationState['critical_recommendations'] ?? 0);

        $highRecommendations =
            (int) ($recommendationState['high_recommendations'] ?? 0);

        $totalRecommendations =
            (int) ($recommendationState['total_recommendations'] ?? 0);

        $governanceIntegrityIntact =
            (bool) ($safetyState['governance_integrity_intact'] ?? false);

        $executionProgressionBlocked =
            (bool) ($eligibilityState['execution_progression_blocked'] ?? true);

        $controlledActivationExecuted =
            (bool) ($executionAttribution['controlled_activation_executed'] ?? false);

        $executionAttributionComplete =
            (bool) ($executionAttribution['execution_attribution_complete'] ?? false);

        $sourceAuthorizationValid =
            (bool) ($sourceAuthorization['source_execution_authorization_valid'] ?? false);

        $executiveScore = round(
            (
                ($executionReadinessScore * 0.20)
                + ($executionEligibilityScore * 0.20)
                + ($executionSafetyScore * 0.20)
                + ($combinedResolutionScore * 0.15)
                + ((100 - $executionRiskScore) * 0.15)
                + ((100 - $combinedPressureScore) * 0.10)
            ),
            2
        );

        $executiveStatus = match (true) {
            !$governanceIntegrityIntact
                => 'CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_INTEGRITY_FAILURE',

            $criticalOpenConditions > 0
                || $criticalRestrictions > 0
                || $executionRiskScore >= 90
                => 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_PRESSURE',

            $blockingExecutionConditions > 0
                || $materialRestrictions > 0
                || $executionRiskScore >= 70
                => 'HIGH_CONTROLLED_ACTIVATION_EXECUTION_PRESSURE',

            $executionProgressionBlocked
                => 'CONTROLLED_ACTIVATION_EXECUTION_RESTRICTED',

            default
                => 'CONTROLLED_ACTIVATION_EXECUTION_GOVERNANCE_STABLE',
        };

        $executiveReadiness = match (true) {
            $executiveScore >= 80
                => 'HIGH_EXECUTIVE_CONTROLLED_ACTIVATION_EXECUTION_READINESS',

            $executiveScore >= 60
                => 'MODERATE_EXECUTIVE_CONTROLLED_ACTIVATION_EXECUTION_READINESS',

            $executiveScore >= 30
                => 'LIMITED_EXECUTIVE_CONTROLLED_ACTIVATION_EXECUTION_READINESS',

            default
                => 'EXTREMELY_LIMITED_EXECUTIVE_CONTROLLED_ACTIVATION_EXECUTION_READINESS',
        };

        $executiveConfidence = match (true) {
            $executiveScore >= 80 => 'HIGH',
            $executiveScore >= 60 => 'MODERATE',
            $executiveScore >= 30 => 'LIMITED',
            default => 'EXTREMELY_LIMITED',
        };

        $executiveDecisionPosture = match (true) {
            !$governanceIntegrityIntact
                => 'RESTORE_EXECUTION_GOVERNANCE_INTEGRITY',

            !$sourceAuthorizationValid
                => 'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION',

            $criticalOpenConditions > 0
                => 'RESOLVE_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_CONDITIONS',

            $criticalRestrictions > 0
                => 'GOVERN_CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',

            $executionProgressionBlocked
                => 'MAINTAIN_CONTROLLED_ACTIVATION_EXECUTION_BLOCK',

            $controlledActivationExecuted
                => 'POST_EXECUTION_HUMAN_VALIDATION_REQUIRED',

            default
                => 'READY_FOR_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW',
        };

        $humanManagementAttentionRequired =
            !$governanceIntegrityIntact
            || $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $blockingExecutionConditions > 0
            || $executionRiskScore >= 70;

        $immediateHumanInterventionRequired =
            !$governanceIntegrityIntact
            || $criticalOpenConditions > 0
            || $criticalRestrictions > 0
            || $executionRiskScore >= 90;

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_EXECUTIVE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'executive_state' => [
                'executive_controlled_activation_execution_status'
                    => $executiveStatus,

                'executive_controlled_activation_execution_readiness'
                    => $executiveReadiness,

                'executive_controlled_activation_execution_confidence'
                    => $executiveConfidence,

                'executive_controlled_activation_execution_score'
                    => $executiveScore,

                'executive_decision_posture'
                    => $executiveDecisionPosture,

                'execution_progression_blocked'
                    => $executionProgressionBlocked,

                'governance_integrity_intact'
                    => $governanceIntegrityIntact,

                'human_management_attention_required'
                    => $humanManagementAttentionRequired,

                'immediate_human_intervention_required'
                    => $immediateHumanInterventionRequired,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,
            ],

            'executive_metrics' => [
                'execution_readiness_score'
                    => $executionReadinessScore,

                'execution_risk_score'
                    => $executionRiskScore,

                'execution_eligibility_score'
                    => $executionEligibilityScore,

                'execution_safety_score'
                    => $executionSafetyScore,

                'combined_resolution_score'
                    => $combinedResolutionScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,

                'blocking_execution_conditions'
                    => $blockingExecutionConditions,

                'critical_open_conditions'
                    => $criticalOpenConditions,

                'material_restrictions'
                    => $materialRestrictions,

                'critical_restrictions'
                    => $criticalRestrictions,

                'critical_recommendations'
                    => $criticalRecommendations,

                'high_recommendations'
                    => $highRecommendations,

                'total_recommendations'
                    => $totalRecommendations,

                'source_execution_authorization_valid'
                    => $sourceAuthorizationValid,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,
            ],

            'top_recommendation'
                => $topRecommendation,

            'recommendation_state'
                => $recommendationState,

            'executive_priorities'
                => $this->executivePriorities(
                    $sourceAuthorizationValid,
                    $criticalOpenConditions,
                    $criticalRestrictions,
                    $blockingExecutionConditions,
                    $materialRestrictions,
                    $executionRiskScore,
                    $executionSafetyScore,
                    $controlledActivationExecuted,
                    $executionAttributionComplete,
                    $governanceIntegrityIntact,
                    $topRecommendation
                ),

            'executive_findings' => [
                "Executive controlled activation execution intelligence is based on Step 71 execution record {$execution->id}.",
                "Executive controlled activation execution status is {$executiveStatus}.",
                "Executive controlled activation execution readiness is {$executiveReadiness}.",
                "Executive controlled activation execution confidence is {$executiveConfidence}.",
                "Executive controlled activation execution score is {$executiveScore}.",
                "Executive decision posture is {$executiveDecisionPosture}.",
                "Execution readiness score is {$executionReadinessScore}.",
                "Execution risk score is {$executionRiskScore}.",
                "Execution eligibility score is {$executionEligibilityScore}.",
                "Execution safety score is {$executionSafetyScore}.",
                "Combined resolution score is {$combinedResolutionScore}.",
                "Combined condition/restriction pressure score is {$combinedPressureScore}.",
                "{$blockingExecutionConditions} execution blocker(s) remain.",
                "{$criticalOpenConditions} critical controlled activation execution condition(s) remain open.",
                "{$materialRestrictions} material execution restriction(s) remain.",
                "{$criticalRestrictions} critical execution restriction(s) remain.",
                "{$criticalRecommendations} critical recommendation(s) remain.",
                "{$highRecommendations} high recommendation(s) remain.",
                'Governance integrity remains '.($governanceIntegrityIntact ? 'INTACT' : 'VIOLATED').'.',
                'Source execution authorization valid is '.($sourceAuthorizationValid ? 'YES' : 'NO').'.',
                'Controlled activation actually executed is '.($controlledActivationExecuted ? 'YES' : 'NO').'.',
                'Execution attribution complete is '.($executionAttributionComplete ? 'YES' : 'NO').'.',
                'Executive intelligence remains informational and does not authorize or perform controlled activation execution.',
            ],

            'execution_state_context'
                => $executionState,

            'condition_restriction_state_context'
                => $conditionState,

            'execution_eligibility_context'
                => $eligibilityState,

            'execution_safety_context'
                => $safetyState,

            'recommendation_context'
                => $recommendation['recommendation_context'],

            'source_execution_authorization_context'
                => $sourceAuthorization,

            'execution_attribution_context'
                => $executionAttribution,

            'executive_controlled_activation_execution_guardrails'
                => $this->guardrails(),
        ];
    }

    private function executivePriorities(
        bool $sourceAuthorizationValid,
        int $criticalOpenConditions,
        int $criticalRestrictions,
        int $blockingExecutionConditions,
        int $materialRestrictions,
        float $executionRiskScore,
        float $executionSafetyScore,
        bool $controlledActivationExecuted,
        bool $executionAttributionComplete,
        bool $governanceIntegrityIntact,
        ?array $topRecommendation
    ): array {
        $priorities = [];

        if ($topRecommendation) {
            $priorities[] = $topRecommendation['recommendation'];
        }

        if (!$sourceAuthorizationValid) {
            $priorities[] =
                'Complete valid explicitly authorized-human execution authorization before controlled activation execution progression.';
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
                "Resolve or formally govern {$blockingExecutionConditions} controlled activation execution blocker(s).";
        }

        if ($materialRestrictions > 0) {
            $priorities[] =
                "Maintain explicit governance treatment for {$materialRestrictions} material controlled activation execution restriction(s).";
        }

        if ($executionRiskScore >= 90) {
            $priorities[] =
                "Maintain critical authorized-human governance oversight while controlled activation execution risk remains {$executionRiskScore}.";
        }

        if ($executionSafetyScore < 80) {
            $priorities[] =
                "Keep controlled activation execution prohibited while execution safety score remains {$executionSafetyScore}.";
        }

        if (!$governanceIntegrityIntact) {
            $priorities[] =
                'Restore controlled activation execution governance integrity before any progression.';
        }

        if (!$controlledActivationExecuted) {
            $priorities[] =
                'Keep controlled activation execution pending until all governance requirements are satisfied.';
        }

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            $priorities[] =
                'Complete executor attribution and required post-execution human governance validation immediately.';
        }

        $priorities[] =
            'Preserve strict separation between execution authorization and actual controlled activation execution.';

        $priorities[] =
            'Do not interpret executive status, score, readiness, confidence, posture, eligibility, safety, or recommendations as execution authority.';

        return array_values(array_unique($priorities));
    }

    private function guardrails(): array
    {
        return [
            'executive_controlled_activation_execution_intelligence_enabled'
                => true,

            'executive_intelligence_is_execution'
                => false,

            'executive_intelligence_is_execution_authorization'
                => false,

            'executive_intelligence_makes_execution_decision'
                => false,

            'executive_intelligence_records_execution_decision'
                => false,

            'executive_intelligence_executes_controlled_activation'
                => false,

            'executive_intelligence_activates_strategic_plan'
                => false,

            'executive_score_authorizes_execution'
                => false,

            'executive_readiness_authorizes_execution'
                => false,

            'executive_confidence_authorizes_execution'
                => false,

            'executive_decision_posture_authorizes_execution'
                => false,

            'executive_intelligence_changes_execution_status'
                => false,

            'executive_intelligence_changes_execution_outcome'
                => false,

            'executive_intelligence_resolves_conditions'
                => false,

            'executive_intelligence_waives_conditions'
                => false,

            'executive_intelligence_removes_restrictions'
                => false,

            'executive_intelligence_downgrades_restrictions'
                => false,

            'executive_intelligence_validates_evidence'
                => false,

            'executive_intelligence_authorizes_ai_change'
                => false,

            'executive_intelligence_authorizes_deployment'
                => false,

            'executive_intelligence_authorizes_rollback'
                => false,

            'executive_intelligence_authorizes_clinical_action'
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
                'Step 71.7 executive controlled activation execution intelligence consolidates execution state, readiness, risk, conditions, restrictions, eligibility, safety, recommendation priority, execution authorization state, execution attribution, governance integrity, and management attention requirements. Executive status, score, readiness, confidence, posture, findings, metrics, and priorities are informational and advisory only. They cannot authorize execution, perform controlled activation execution, resolve or waive conditions, remove or downgrade restrictions, validate evidence, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action. Actual controlled activation execution remains a separately governed authority reserved for explicitly authorized human governance.',
        ];
    }
}