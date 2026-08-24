<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationExecutiveIntelligenceEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Controlled Activation Authorization
        |--------------------------------------------------------------------------
        */

        $authorization = $authorizationId
            ? AIGovernanceStrategicPlanControlledActivationAuthorization::find($authorizationId)
            : AIGovernanceStrategicPlanControlledActivationAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_AUTHORIZATION_AVAILABLE',
                'message' =>
                    'No strategic plan controlled activation authorization record is available for executive intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69 Intelligence
        |--------------------------------------------------------------------------
        */

        $state = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
        )->analyze($authorization->id);

        $conditionRestriction = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine::class
        )->analyze($authorization->id);

        $eligibilitySafety = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationEligibilitySafetyIntelligenceEngine::class
        )->analyze($authorization->id);

        $recommendation = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationRecommendationIntelligenceEngine::class
        )->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return $this->unavailable(
                'CONTROLLED_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'Executive intelligence could not continue because controlled activation authorization state intelligence is unavailable.'
            );
        }

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return $this->unavailable(
                'CONTROLLED_ACTIVATION_AUTHORIZATION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'Executive intelligence could not continue because condition and restriction intelligence is unavailable.'
            );
        }

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return $this->unavailable(
                'CONTROLLED_ACTIVATION_AUTHORIZATION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'Executive intelligence could not continue because eligibility and safety intelligence is unavailable.'
            );
        }

        if (!($recommendation['analysis_completed'] ?? false)) {
            return $this->unavailable(
                'CONTROLLED_ACTIVATION_AUTHORIZATION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'Executive intelligence could not continue because recommendation intelligence is unavailable.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $eligibilityState =
            $eligibilitySafety['activation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['activation_safety_state'] ?? [];

        $riskContext =
            $eligibilitySafety['activation_risk_context'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationContext =
            $recommendation['recommendation_context'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $readinessScore = $this->score(
            $activationState['activation_readiness_score'] ?? 0
        );

        $riskScore = $this->score(
            $activationState['activation_risk_score'] ?? 0
        );

        $eligibilityScore = $this->score(
            $eligibilityState['activation_authorization_eligibility_score'] ?? 0
        );

        $safetyScore = $this->score(
            $safetyState['activation_authorization_safety_score'] ?? 0
        );

        $resolutionScore = $this->score(
            $conditionState['combined_resolution_score'] ?? 0
        );

        $pressureScore = $this->score(
            $conditionState['combined_condition_restriction_pressure_score'] ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Governance State
        |--------------------------------------------------------------------------
        */

        $governanceIntegrityIntact =
            (bool) ($safetyState['governance_integrity_intact'] ?? false);

        $sourceConfirmationRecorded =
            (bool) (
                $sourceConfirmationContext['source_confirmation_decision_recorded']
                ?? false
            );

        $sourceConfirmationCompleted =
            (bool) (
                $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                ?? false
            );

        $authorizationCompleted =
            (bool) (
                $activationState['controlled_activation_authorization_completed']
                ?? false
            );

        $executionAuthorized =
            (bool) (
                $executionContext['controlled_activation_execution_authorized']
                ?? false
            );

        $progressionBlocked =
            (bool) (
                $eligibilityState['activation_progression_blocked']
                ?? true
            );

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $authorizationBlockers =
            (int) (
                $conditionState['blocking_activation_authorization_conditions']
                ?? 0
            );

        $executionBlockers =
            (int) (
                $conditionState['blocking_activation_execution_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) ($conditionState['material_restrictions'] ?? 0);

        $criticalRestrictions =
            (int) ($conditionState['critical_restrictions'] ?? 0);

        $criticalRecommendations =
            (int) ($recommendationState['critical_recommendations'] ?? 0);

        $highRecommendations =
            (int) ($recommendationState['high_recommendations'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Executive Score
        |--------------------------------------------------------------------------
        |
        | Higher readiness, eligibility, safety and resolution increase the
        | executive score. Higher risk and pressure reduce it.
        |
        */

        $executiveScore = round(
            (
                ($readinessScore * 0.25)
                + ($eligibilityScore * 0.20)
                + ($safetyScore * 0.20)
                + ($resolutionScore * 0.15)
                + ((100 - $riskScore) * 0.10)
                + ((100 - $pressureScore) * 0.10)
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Executive Classification
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $executiveStatus =
                'CONTROLLED_ACTIVATION_GOVERNANCE_INTEGRITY_FAILURE';

            $executiveReadiness =
                'NOT_READY_FOR_CONTROLLED_ACTIVATION_GOVERNANCE_PROGRESSION';

            $executiveConfidence =
                'UNACCEPTABLE';
        } elseif (
            $criticalRestrictions > 0
            || $riskScore >= 90
            || $safetyScore <= 20
        ) {
            $executiveStatus =
                'CRITICAL_CONTROLLED_ACTIVATION_AUTHORIZATION_PRESSURE';

            $executiveReadiness =
                'EXTREMELY_LIMITED_CONTROLLED_ACTIVATION_AUTHORIZATION_READINESS';

            $executiveConfidence =
                'EXTREMELY_LIMITED';
        } elseif (
            $authorizationBlockers > 0
            || $executionBlockers > 0
            || $materialRestrictions > 0
        ) {
            $executiveStatus =
                'ELEVATED_CONTROLLED_ACTIVATION_AUTHORIZATION_PRESSURE';

            $executiveReadiness =
                'LIMITED_CONTROLLED_ACTIVATION_AUTHORIZATION_READINESS';

            $executiveConfidence =
                'LIMITED';
        } elseif (!$authorizationCompleted) {
            $executiveStatus =
                'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION';

            $executiveReadiness =
                'CONDITIONAL_CONTROLLED_ACTIVATION_AUTHORIZATION_READINESS';

            $executiveConfidence =
                'MODERATE';
        } elseif (!$executionAuthorized) {
            $executiveStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZED_EXECUTION_PENDING';

            $executiveReadiness =
                'READY_FOR_AUTHORIZED_HUMAN_EXECUTION_REVIEW';

            $executiveConfidence =
                'HIGH';
        } else {
            $executiveStatus =
                'CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_PRESENT';

            $executiveReadiness =
                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_STATE';

            $executiveConfidence =
                'HIGH';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Decision Posture
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $decisionPosture =
                'HALT_AND_RESTORE_GOVERNANCE_INTEGRITY';
        } elseif (!$sourceConfirmationRecorded) {
            $decisionPosture =
                'AWAIT_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION';
        } elseif (!$sourceConfirmationCompleted) {
            $decisionPosture =
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        } elseif ($criticalRestrictions > 0) {
            $decisionPosture =
                'REQUIRE_CRITICAL_AUTHORIZED_HUMAN_GOVERNANCE_REVIEW';
        } elseif ($authorizationBlockers > 0) {
            $decisionPosture =
                'RESOLVE_CONTROLLED_ACTIVATION_AUTHORIZATION_BLOCKERS';
        } elseif (!$authorizationCompleted) {
            $decisionPosture =
                'AWAIT_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION';
        } elseif (!$executionAuthorized) {
            $decisionPosture =
                'AWAIT_SEPARATE_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION';
        } else {
            $decisionPosture =
                'AUTHORIZED_HUMAN_EXECUTION_GOVERNANCE_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive State
        |--------------------------------------------------------------------------
        */

        $executiveState = [
            'executive_controlled_activation_authorization_status' =>
                $executiveStatus,

            'executive_controlled_activation_authorization_readiness' =>
                $executiveReadiness,

            'executive_controlled_activation_authorization_confidence' =>
                $executiveConfidence,

            'executive_controlled_activation_authorization_score' =>
                $executiveScore,

            'executive_decision_posture' =>
                $decisionPosture,

            'activation_progression_blocked' =>
                $progressionBlocked,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'human_management_attention_required' =>
                (bool) (
                    $safetyState['human_management_attention_required']
                    ?? true
                ),

            'immediate_human_intervention_required' =>
                (bool) (
                    $safetyState['immediate_human_intervention_required']
                    ?? false
                ),

            'controlled_activation_authorization_completed' =>
                $authorizationCompleted,

            'controlled_activation_execution_authorized' =>
                $executionAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Metrics
        |--------------------------------------------------------------------------
        */

        $executiveMetrics = [
            'activation_readiness_score' =>
                $readinessScore,

            'activation_risk_score' =>
                $riskScore,

            'activation_authorization_eligibility_score' =>
                $eligibilityScore,

            'activation_authorization_safety_score' =>
                $safetyScore,

            'combined_resolution_score' =>
                $resolutionScore,

            'combined_condition_restriction_pressure_score' =>
                $pressureScore,

            'blocking_activation_authorization_conditions' =>
                $authorizationBlockers,

            'blocking_activation_execution_conditions' =>
                $executionBlockers,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'critical_recommendations' =>
                $criticalRecommendations,

            'high_recommendations' =>
                $highRecommendations,

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationRecorded,

            'source_governance_decision_confirmation_completed' =>
                $sourceConfirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Executive controlled activation authorization intelligence is based on authorization record {$authorization->id}.",

            'Executive controlled activation authorization status is '
                .$executiveStatus.'.',

            'Executive controlled activation authorization readiness is '
                .$executiveReadiness.'.',

            'Executive controlled activation authorization confidence is '
                .$executiveConfidence.'.',

            'Executive controlled activation authorization score is '
                .$executiveScore.'.',

            'Executive decision posture is '
                .$decisionPosture.'.',

            'Activation readiness score is '
                .$readinessScore.'.',

            'Activation risk score is '
                .$riskScore.'.',

            'Activation authorization eligibility score is '
                .$eligibilityScore.'.',

            'Activation authorization safety score is '
                .$safetyScore.'.',

            "{$authorizationBlockers} activation authorization blocker(s) remain.",

            "{$executionBlockers} activation execution blocker(s) remain.",

            "{$materialRestrictions} material restriction(s) remain.",

            "{$criticalRestrictions} critical restriction(s) remain.",

            "{$criticalRecommendations} critical recommendation(s) remain.",

            'Governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            'Controlled activation authorization remains '
                .($authorizationCompleted ? 'COMPLETE' : 'INCOMPLETE').'.',

            'Controlled activation execution remains '
                .($executionAuthorized ? 'AUTHORIZED' : 'NOT_AUTHORIZED').'.',

            'Executive intelligence is advisory and does not authorize activation or execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Priorities
        |--------------------------------------------------------------------------
        */

        $executivePriorities = [];

        foreach (
            array_slice(
                $recommendation['management_priorities'] ?? [],
                0,
                8
            ) as $priority
        ) {
            $executivePriorities[] = $priority;
        }

        $executivePriorities[] =
            'Preserve explicitly authorized human governance authority for controlled activation authorization.';

        $executivePriorities[] =
            'Preserve separately authorized human governance authority for controlled activation execution.';

        $executivePriorities[] =
            'Do not interpret the executive score, readiness, confidence, posture, or recommendations as authorization to activate or execute.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_EXECUTIVE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->id,

            'activation_authorization_code' =>
                $authorization->activation_authorization_code,

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

            'executive_state' =>
                $executiveState,

            'executive_metrics' =>
                $executiveMetrics,

            'top_recommendation' =>
                $recommendation['top_recommendation'] ?? null,

            'recommendation_state' =>
                $recommendationState,

            'executive_priorities' =>
                array_values(array_unique($executivePriorities)),

            'executive_findings' =>
                $findings,

            'activation_authorization_state_context' =>
                $activationState,

            'condition_restriction_state_context' =>
                $conditionState,

            'activation_eligibility_context' =>
                $eligibilityState,

            'activation_safety_context' =>
                $safetyState,

            'activation_risk_context' =>
                $riskContext,

            'recommendation_context' =>
                $recommendationContext,

            'source_confirmation_context' =>
                $sourceConfirmationContext,

            'authorization_context' =>
                $authorizationContext,

            'execution_context' =>
                $executionContext,

            'executive_controlled_activation_authorization_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function unavailable(
        string $status,
        string $message
    ): array {
        return [
            'analysis_completed' => false,
            'status' => $status,
            'message' => $message,
        ];
    }

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(0, min(100, (float) $value)),
            2
        );
    }

    private function guardrails(): array
    {
        return [
            'executive_controlled_activation_authorization_intelligence_enabled' =>
                true,

            'executive_intelligence_is_activation_authorization' =>
                false,

            'executive_intelligence_is_execution_authorization' =>
                false,

            'executive_intelligence_makes_activation_authorization_decision' =>
                false,

            'executive_intelligence_records_activation_authorization_decision' =>
                false,

            'executive_intelligence_completes_activation_authorization' =>
                false,

            'executive_intelligence_authorizes_activation_execution' =>
                false,

            'executive_intelligence_activates_strategic_plan' =>
                false,

            'executive_score_authorizes_activation' =>
                false,

            'executive_score_authorizes_execution' =>
                false,

            'executive_readiness_authorizes_activation' =>
                false,

            'executive_readiness_authorizes_execution' =>
                false,

            'executive_confidence_authorizes_activation' =>
                false,

            'executive_confidence_authorizes_execution' =>
                false,

            'executive_decision_posture_authorizes_activation' =>
                false,

            'executive_decision_posture_authorizes_execution' =>
                false,

            'executive_intelligence_changes_governance_confirmation' =>
                false,

            'executive_intelligence_changes_authorization_status' =>
                false,

            'executive_intelligence_changes_authorization_decision' =>
                false,

            'executive_intelligence_changes_authorization_outcome' =>
                false,

            'executive_intelligence_changes_execution_status' =>
                false,

            'executive_intelligence_resolves_conditions' =>
                false,

            'executive_intelligence_waives_conditions' =>
                false,

            'executive_intelligence_removes_restrictions' =>
                false,

            'executive_intelligence_validates_evidence' =>
                false,

            'executive_intelligence_authorizes_ai_change' =>
                false,

            'executive_intelligence_authorizes_deployment' =>
                false,

            'executive_intelligence_authorizes_rollback' =>
                false,

            'executive_intelligence_authorizes_clinical_action' =>
                false,

            'executive_intelligence_overrides_human_review' =>
                false,

            'executive_intelligence_overrides_governance_confirmation' =>
                false,

            'executive_intelligence_overrides_activation_authorization' =>
                false,

            'executive_intelligence_overrides_execution_authorization' =>
                false,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,

            'automatic_condition_resolution_allowed' =>
                false,

            'automatic_restriction_removal_allowed' =>
                false,

            'automatic_evidence_validation_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'automatic_deployment_allowed' =>
                false,

            'automatic_rollback_allowed' =>
                false,

            'automatic_clinical_action_allowed' =>
                false,

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'activation_execution_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'message' =>
                'Step 69.7 executive controlled activation authorization intelligence consolidates readiness, risk, eligibility, safety, condition and restriction pressure, recommendation priority, governance confirmation state, authorization state, and execution state for management review. Executive status, score, readiness, confidence, posture, findings, and priorities are advisory only and cannot authorize activation, authorize execution, complete authorization, resolve conditions, remove restrictions, validate evidence, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action.',
        ];
    }
}