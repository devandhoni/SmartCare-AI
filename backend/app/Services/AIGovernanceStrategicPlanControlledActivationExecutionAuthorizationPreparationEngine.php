<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;
use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;
use Illuminate\Support\Facades\DB;
use Throwable;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationPreparationEngine
{
    /**
     * Prepare a Step 70 controlled activation execution authorization record.
     *
     * Preparation is advisory / administrative only.
     *
     * It does NOT:
     * - authorize activation execution;
     * - complete execution authorization;
     * - execute controlled activation;
     * - activate the strategic plan;
     * - resolve conditions;
     * - remove restrictions;
     * - validate evidence;
     * - modify AI behavior;
     * - deploy changes;
     * - trigger rollback;
     * - perform clinical action.
     */
    public function prepare(?int $sourceActivationAuthorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Controlled Activation Authorization
        |--------------------------------------------------------------------------
        */

        $sourceAuthorization = $sourceActivationAuthorizationId
            ? AIGovernanceStrategicPlanControlledActivationAuthorization::find(
                $sourceActivationAuthorizationId
            )
            : AIGovernanceStrategicPlanControlledActivationAuthorization::latest('id')->first();

        if (!$sourceAuthorization) {
            return [
                'prepared' => false,

                'status' =>
                    'NO_CONTROLLED_ACTIVATION_AUTHORIZATION_AVAILABLE',

                'message' =>
                    'No strategic plan controlled activation authorization record is available for execution authorization preparation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Idempotency
        |--------------------------------------------------------------------------
        |
        | Step 70.2 must not create duplicate execution-authorization packages
        | for the same Step 69 authorization record.
        |
        */

        $existing =
            AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::query()
                ->where(
                    'strategic_plan_controlled_activation_authorization_id',
                    $sourceAuthorization->id
                )
                ->latest('id')
                ->first();

        if ($existing) {
            return [
                'prepared' => true,

                'status' =>
                    'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_ALREADY_PREPARED',

                'message' =>
                    'A controlled activation execution authorization record has already been prepared for this Step 69 controlled activation authorization.',

                'controlled_activation_execution_authorization' =>
                    $this->recordSummary($existing),

                'execution_authorization_context' =>
                    $this->existingRecordContext($existing),

                'execution_authorization_guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 69 Final Validation
        |--------------------------------------------------------------------------
        |
        | Step 70 should only be built from a structurally validated Step 69
        | architecture.
        |
        | PASSED_WITH_WARNINGS is intentionally accepted because Step 69 may
        | be architecturally complete while operational human-governance work
        | is still pending.
        |
        */

        $step69FinalValidation =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationFinalValidationEngine::class
            )->analyze($sourceAuthorization->id);

        $step69ValidationStatus =
            $step69FinalValidation['validation_status']
            ?? 'UNKNOWN';

        $step69ReadyForClosure =
            (bool) (
                $step69FinalValidation['step_69_ready_for_closure']
                ?? false
            );

        if (
            !$step69ReadyForClosure
            || !in_array(
                $step69ValidationStatus,
                ['PASSED', 'PASSED_WITH_WARNINGS'],
                true
            )
        ) {
            return [
                'prepared' => false,

                'status' =>
                    'STEP_69_CONTROLLED_ACTIVATION_AUTHORIZATION_NOT_READY_FOR_EXECUTION_AUTHORIZATION_PREPARATION',

                'message' =>
                    'Step 70 execution authorization preparation requires a Step 69 architecture that has passed final validation and is ready for closure.',

                'step_69_validation_status' =>
                    $step69ValidationStatus,

                'step_69_ready_for_closure' =>
                    $step69ReadyForClosure,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69 Intelligence
        |--------------------------------------------------------------------------
        */

        $state =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
            )->analyze($sourceAuthorization->id);

        $conditionRestriction =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine::class
            )->analyze($sourceAuthorization->id);

        $eligibilitySafety =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationEligibilitySafetyIntelligenceEngine::class
            )->analyze($sourceAuthorization->id);

        $recommendation =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationRecommendationIntelligenceEngine::class
            )->analyze($sourceAuthorization->id);

        $executive =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationExecutiveIntelligenceEngine::class
            )->analyze($sourceAuthorization->id);

        $audit =
            app(
                AIGovernanceStrategicPlanControlledActivationAuthorizationAuditSummaryEngine::class
            )->analyze($sourceAuthorization->id);

        /*
        |--------------------------------------------------------------------------
        | Required Intelligence Availability
        |--------------------------------------------------------------------------
        */

        if (!($state['analysis_completed'] ?? false)) {
            return $this->sourceUnavailable(
                'STEP_69_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'Step 70 execution authorization preparation could not continue because Step 69 activation authorization state intelligence is unavailable.'
            );
        }

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return $this->sourceUnavailable(
                'STEP_69_ACTIVATION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'Step 70 execution authorization preparation could not continue because Step 69 condition and restriction intelligence is unavailable.'
            );
        }

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return $this->sourceUnavailable(
                'STEP_69_ACTIVATION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'Step 70 execution authorization preparation could not continue because Step 69 eligibility and safety intelligence is unavailable.'
            );
        }

        if (!($recommendation['analysis_completed'] ?? false)) {
            return $this->sourceUnavailable(
                'STEP_69_ACTIVATION_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'Step 70 execution authorization preparation could not continue because Step 69 recommendation intelligence is unavailable.'
            );
        }

        if (!($executive['analysis_completed'] ?? false)) {
            return $this->sourceUnavailable(
                'STEP_69_ACTIVATION_EXECUTIVE_INTELLIGENCE_UNAVAILABLE',
                'Step 70 execution authorization preparation could not continue because Step 69 executive intelligence is unavailable.'
            );
        }

        if (
            ($audit['audit_status'] ?? null) !== 'COMPLETE'
            || (int) ($audit['audit_summary']['failed_checks'] ?? 1) !== 0
        ) {
            return [
                'prepared' => false,

                'status' =>
                    'STEP_69_CONTROLLED_ACTIVATION_AUTHORIZATION_AUDIT_NOT_COMPLETE',

                'message' =>
                    'Step 70 execution authorization preparation requires the Step 69 controlled activation authorization audit to complete without integrity failures.',

                'audit_status' =>
                    $audit['audit_status'] ?? null,

                'audit_summary' =>
                    $audit['audit_summary'] ?? [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        $conditionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $eligibilityState =
            $eligibilitySafety['activation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['activation_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $executiveState =
            $executive['executive_state'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Authorization State
        |--------------------------------------------------------------------------
        */

        $sourceActivationAuthorizationDecision =
            $sourceAuthorization->controlled_activation_authorization_decision;

        $sourceActivationAuthorizationDecisionRecorded =
            !empty($sourceActivationAuthorizationDecision);

        $sourceActivationAuthorizationMadeByAuthorizedHuman =
            (bool) (
                $sourceAuthorization->activation_authorization_made_by_authorized_human
                ?? false
            );

        $sourceActivationAuthorizationAttributionComplete =
            $this->activationAuthorizationAttributionComplete(
                $sourceAuthorization
            );

        $sourceControlledActivationAuthorizationCompleted =
            (bool) (
                $sourceAuthorization->controlled_activation_authorization_completed
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Execution State
        |--------------------------------------------------------------------------
        */

        $sourceControlledActivationExecutionStatus =
            $sourceAuthorization->controlled_activation_execution_status;

        $sourceControlledActivationExecutionAuthorized =
            (bool) (
                $sourceAuthorization->controlled_activation_execution_authorized
                ?? false
            );

        $sourceExecutionAuthorizationCode =
            $sourceAuthorization->activation_execution_authorization_code;

        $sourceExecutionAuthorizedBy =
            $sourceAuthorization->execution_authorized_by;

        $sourceExecutionAuthorizerRole =
            $sourceAuthorization->execution_authorizer_role;

        $sourceExecutionAuthorizedAt =
            $sourceAuthorization->execution_authorized_at;

        $sourceExecutionAuthorizationAttributionComplete =
            $this->sourceExecutionAttributionComplete(
                $sourceAuthorization
            );

        /*
        |--------------------------------------------------------------------------
        | Conditions / Restrictions
        |--------------------------------------------------------------------------
        */

        $sourceAuthorizationBlockers =
            (int) (
                $conditionState['blocking_activation_authorization_conditions']
                ?? 0
            );

        $sourceExecutionBlockers =
            (int) (
                $conditionState['blocking_activation_execution_conditions']
                ?? 0
            );

        $sourceMaterialRestrictions =
            (int) (
                $conditionState['material_restrictions']
                ?? 0
            );

        $sourceCriticalRestrictions =
            (int) (
                $conditionState['critical_restrictions']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence
        |--------------------------------------------------------------------------
        */

        $validatedEvidence =
            $conditionRestriction['validated_evidence']
            ?? $sourceAuthorization->validated_evidence
            ?? [];

        if (!is_array($validatedEvidence)) {
            $validatedEvidence = [];
        }

        $outstandingEvidence =
            (int) (
                $recommendation['recommendation_context']['outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            $this->score(
                $conditionState['condition_resolution_score']
                ?? $sourceAuthorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $conditionState['evidence_resolution_score']
                ?? $sourceAuthorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $conditionState['combined_resolution_score']
                ?? $sourceAuthorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $conditionState['condition_pressure_score']
                ?? $sourceAuthorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $conditionState['restriction_pressure_score']
                ?? $sourceAuthorization->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $conditionState['combined_condition_restriction_pressure_score']
                ?? $sourceAuthorization->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Execution Readiness
        |--------------------------------------------------------------------------
        */

        $executionReadiness =
            $this->determineExecutionReadiness(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $sourceExecutionBlockers,
                $sourceMaterialRestrictions,
                $sourceCriticalRestrictions,
                $outstandingEvidence
            );

        $executionReadinessScore =
            $this->calculateExecutionReadinessScore(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $sourceExecutionBlockers,
                $sourceMaterialRestrictions,
                $sourceCriticalRestrictions,
                $outstandingEvidence,
                $combinedResolutionScore
            );

        /*
        |--------------------------------------------------------------------------
        | Execution Risk
        |--------------------------------------------------------------------------
        */

        $sourceActivationRiskScore =
            $this->score(
                $activationState['activation_risk_score']
                ?? $sourceAuthorization->activation_risk_score
            );

        $executionRiskScore =
            $this->calculateExecutionRiskScore(
                $sourceActivationRiskScore,
                $sourceControlledActivationAuthorizationCompleted,
                $sourceExecutionBlockers,
                $sourceMaterialRestrictions,
                $sourceCriticalRestrictions,
                $combinedPressureScore,
                $executionReadinessScore
            );

        $executionRiskLevel =
            $this->executionRiskLevel($executionRiskScore);

        /*
        |--------------------------------------------------------------------------
        | Build Step 70 Conditions
        |--------------------------------------------------------------------------
        */

        $executionConditions =
            $this->buildExecutionConditions(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $sourceExecutionBlockers,
                $sourceMaterialRestrictions,
                $sourceCriticalRestrictions,
                $outstandingEvidence,
                $executionRiskLevel,
                $executionRiskScore
            );

        /*
        |--------------------------------------------------------------------------
        | Build Step 70 Restrictions
        |--------------------------------------------------------------------------
        */

        $executionRestrictions =
            $this->buildExecutionRestrictions(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $sourceExecutionBlockers,
                $sourceMaterialRestrictions,
                $sourceCriticalRestrictions,
                $outstandingEvidence,
                $executionRiskLevel,
                $executionRiskScore
            );

        /*
        |--------------------------------------------------------------------------
        | Step 70 Findings
        |--------------------------------------------------------------------------
        */

        $executionFindings = [
            "Controlled activation execution authorization preparation is based on Step 69 controlled activation authorization record {$sourceAuthorization->id}.",

            "Step 69 final validation status is {$step69ValidationStatus}.",

            'Step 69 ready for closure is '
                .($step69ReadyForClosure ? 'YES' : 'NO').'.',

            'Source controlled activation authorization decision is '
                .(
                    $sourceActivationAuthorizationDecisionRecorded
                        ? $sourceActivationAuthorizationDecision
                        : 'NOT_RECORDED'
                ).'.',

            'Source activation authorization decision recorded is '
                .($sourceActivationAuthorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source activation authorization made by explicitly authorized human is '
                .($sourceActivationAuthorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source activation authorization attribution complete is '
                .($sourceActivationAuthorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Source controlled activation authorization completed is '
                .($sourceControlledActivationAuthorizationCompleted ? 'YES' : 'NO').'.',

            'Source controlled activation execution authorized is '
                .($sourceControlledActivationExecutionAuthorized ? 'YES' : 'NO').'.',

            "{$sourceAuthorizationBlockers} source condition(s) currently block controlled activation authorization.",

            "{$sourceExecutionBlockers} source condition(s) currently block activation execution.",

            "{$sourceMaterialRestrictions} material source controlled activation restriction(s) remain.",

            "{$sourceCriticalRestrictions} critical source controlled activation restriction(s) remain.",

            "{$outstandingEvidence} source evidence requirement(s) remain outstanding.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined resolution score is {$combinedResolutionScore}.",

            "Current condition pressure score is {$conditionPressureScore}.",

            "Current restriction pressure score is {$restrictionPressureScore}.",

            "Current combined condition/restriction pressure score is {$combinedPressureScore}.",

            "Current execution authorization readiness is {$executionReadiness} with score {$executionReadinessScore}.",

            "Current controlled activation execution risk is {$executionRiskLevel} with score {$executionRiskScore}.",

            count($executionConditions)
                .' Step 70 execution authorization condition(s) are represented.',

            count($executionRestrictions)
                .' Step 70 execution authorization restriction(s) are represented.',

            'Step 70.2 preparation does not make or record a controlled activation execution authorization decision.',

            'Step 70.2 preparation does not complete execution authorization.',

            'Step 70.2 preparation does not execute controlled activation.',

            'Step 70.2 preparation does not activate the strategic plan.',

            'Actual controlled activation execution remains a separate future governed execution layer.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Review Context
        |--------------------------------------------------------------------------
        */

        $reviewContext = [
            'review_state' =>
                'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION',

            'step_69_validation_status' =>
                $step69ValidationStatus,

            'step_69_ready_for_closure' =>
                $step69ReadyForClosure,

            'step_69_audit_status' =>
                $audit['audit_status'] ?? null,

            'step_69_governance_integrity_intact' =>
                (bool) (
                    $safetyState['governance_integrity_intact']
                    ?? false
                ),

            'source_activation_authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'source_activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'source_activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'source_controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'source_controlled_activation_execution_authorized' =>
                $sourceControlledActivationExecutionAuthorized,

            'source_execution_authorization_attribution_complete' =>
                $sourceExecutionAuthorizationAttributionComplete,

            'activation_authorization_completion_eligibility' =>
                $eligibilityState['controlled_activation_authorization_completion_eligibility']
                ?? null,

            'source_activation_execution_review_eligibility' =>
                $eligibilityState['activation_execution_review_eligibility']
                ?? null,

            'source_activation_progression_blocked' =>
                (bool) (
                    $eligibilityState['activation_progression_blocked']
                    ?? true
                ),

            'source_activation_safety_status' =>
                $safetyState['activation_authorization_safety_status']
                ?? null,

            'source_recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'source_top_recommendation_code' =>
                $recommendationState['top_recommendation_code']
                ?? null,

            'source_executive_status' =>
                $executiveState['executive_controlled_activation_authorization_status']
                ?? null,

            'source_executive_decision_posture' =>
                $executiveState['executive_decision_posture']
                ?? null,

            'execution_authorization_readiness' =>
                $executionReadiness,

            'execution_authorization_readiness_score' =>
                $executionReadinessScore,

            'execution_authorization_risk_level' =>
                $executionRiskLevel,

            'execution_authorization_risk_score' =>
                $executionRiskScore,

            'human_review_required' =>
                true,

            'authorized_human_execution_authorization_required' =>
                true,

            'authorized_human_execution_required' =>
                true,

            'automatic_execution_authorization_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Activation Authorization Context
        |--------------------------------------------------------------------------
        */

        $activationAuthorizationContext = [
            'activation_authorization_status' =>
                $sourceAuthorization->activation_authorization_status,

            'activation_authorization_mode' =>
                $sourceAuthorization->activation_authorization_mode,

            'controlled_activation_authorization_decision' =>
                $sourceActivationAuthorizationDecision,

            'activation_authorization_outcome' =>
                $sourceAuthorization->activation_authorization_outcome,

            'activation_authorization_outcome_status' =>
                $sourceAuthorization->activation_authorization_outcome_status,

            'activation_readiness' =>
                $activationState['activation_readiness']
                ?? $sourceAuthorization->activation_readiness,

            'activation_readiness_score' =>
                $this->score(
                    $activationState['activation_readiness_score']
                    ?? $sourceAuthorization->activation_readiness_score
                ),

            'activation_risk_level' =>
                $activationState['activation_risk_level']
                ?? $sourceAuthorization->activation_risk_level,

            'activation_risk_score' =>
                $sourceActivationRiskScore,

            'authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'controlled_activation_execution_status' =>
                $sourceControlledActivationExecutionStatus,

            'controlled_activation_execution_authorized' =>
                $sourceControlledActivationExecutionAuthorized,

            'execution_authorization_attribution_complete' =>
                $sourceExecutionAuthorizationAttributionComplete,

            'blocking_activation_authorization_conditions' =>
                $sourceAuthorizationBlockers,

            'blocking_activation_execution_conditions' =>
                $sourceExecutionBlockers,

            'material_restrictions' =>
                $sourceMaterialRestrictions,

            'critical_restrictions' =>
                $sourceCriticalRestrictions,

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
                $combinedPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Context
        |--------------------------------------------------------------------------
        */

        $governanceContext = [
            'step_69_validation_status' =>
                $step69ValidationStatus,

            'step_69_ready_for_closure' =>
                $step69ReadyForClosure,

            'step_69_audit_status' =>
                $audit['audit_status'] ?? null,

            'step_69_audit_management_status' =>
                $audit['management_status'] ?? null,

            'governance_integrity_intact' =>
                (bool) (
                    $safetyState['governance_integrity_intact']
                    ?? false
                ),

            'source_activation_authorization_eligibility_score' =>
                $this->score(
                    $eligibilityState['activation_authorization_eligibility_score']
                    ?? 0
                ),

            'source_activation_safety_status' =>
                $safetyState['activation_authorization_safety_status']
                ?? null,

            'source_activation_safety_score' =>
                $this->score(
                    $safetyState['activation_authorization_safety_score']
                    ?? 0
                ),

            'source_recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'source_total_recommendations' =>
                $recommendationState['total_recommendations']
                ?? 0,

            'source_top_recommendation_code' =>
                $recommendationState['top_recommendation_code']
                ?? null,

            'source_executive_status' =>
                $executiveState['executive_controlled_activation_authorization_status']
                ?? null,

            'source_executive_readiness' =>
                $executiveState['executive_controlled_activation_authorization_readiness']
                ?? null,

            'source_executive_score' =>
                $this->score(
                    $executiveState['executive_controlled_activation_authorization_score']
                    ?? 0
                ),

            'execution_authorization_readiness' =>
                $executionReadiness,

            'execution_authorization_readiness_score' =>
                $executionReadinessScore,

            'execution_authorization_risk_level' =>
                $executionRiskLevel,

            'execution_authorization_risk_score' =>
                $executionRiskScore,

            'execution_authorization_authority' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

            'actual_execution_authority' =>
                'SEPARATE_AUTHORIZED_HUMAN_GOVERNED_EXECUTION_LAYER',

            'automatic_execution_authorization_allowed' =>
                false,

            'automatic_execution_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Traceability Context
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
            'strategic_plan_controlled_activation_authorization_id' =>
                $sourceAuthorization->id,

            'activation_authorization_code' =>
                $sourceAuthorization->activation_authorization_code,

            'strategic_plan_governance_decision_confirmation_id' =>
                $sourceAuthorization->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id' =>
                $sourceAuthorization->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $sourceAuthorization->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $sourceAuthorization->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $sourceAuthorization->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $sourceAuthorization->strategic_plan_id,

            'strategic_snapshot_id' =>
                $sourceAuthorization->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $sourceAuthorization->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $sourceAuthorization->lifecycle_snapshot_id,

            'decision_scope' =>
                $sourceAuthorization->decision_scope,

            'resident_id' =>
                $sourceAuthorization->resident_id,

            'source_activation_authorization_decision' =>
                $sourceActivationAuthorizationDecision,

            'source_activation_authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'source_activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'source_activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'source_controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'source_controlled_activation_execution_status' =>
                $sourceControlledActivationExecutionStatus,

            'source_controlled_activation_execution_authorized' =>
                $sourceControlledActivationExecutionAuthorized,

            'source_activation_execution_authorization_code' =>
                $sourceExecutionAuthorizationCode,

            'source_execution_authorized_by' =>
                $sourceExecutionAuthorizedBy,

            'source_execution_authorizer_role' =>
                $sourceExecutionAuthorizerRole,

            'source_execution_authorized_at' =>
                $sourceExecutionAuthorizedAt?->toISOString(),

            'source_execution_authorization_attribution_complete' =>
                $sourceExecutionAuthorizationAttributionComplete,

            'source_activation_authorization_created_at' =>
                $sourceAuthorization->created_at?->toISOString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Persist Step 70 Record
        |--------------------------------------------------------------------------
        */

        try {
            $executionAuthorization = DB::transaction(
                function () use (
                    $sourceAuthorization,
                    $sourceActivationAuthorizationDecision,
                    $sourceActivationAuthorizationDecisionRecorded,
                    $sourceActivationAuthorizationMadeByAuthorizedHuman,
                    $sourceActivationAuthorizationAttributionComplete,
                    $sourceControlledActivationAuthorizationCompleted,
                    $sourceControlledActivationExecutionStatus,
                    $sourceControlledActivationExecutionAuthorized,
                    $sourceExecutionAuthorizationCode,
                    $sourceExecutionAuthorizedBy,
                    $sourceExecutionAuthorizerRole,
                    $sourceExecutionAuthorizedAt,
                    $sourceExecutionAuthorizationAttributionComplete,
                    $executionReadiness,
                    $executionReadinessScore,
                    $executionRiskLevel,
                    $executionRiskScore,
                    $conditionResolutionScore,
                    $evidenceResolutionScore,
                    $combinedResolutionScore,
                    $conditionPressureScore,
                    $restrictionPressureScore,
                    $combinedPressureScore,
                    $executionConditions,
                    $validatedEvidence,
                    $executionFindings,
                    $executionRestrictions,
                    $reviewContext,
                    $activationAuthorizationContext,
                    $governanceContext,
                    $sourceContext
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Re-check Idempotency Inside Transaction
                    |--------------------------------------------------------------------------
                    */

                    $existing =
                        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::query()
                            ->where(
                                'strategic_plan_controlled_activation_authorization_id',
                                $sourceAuthorization->id
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($existing) {
                        return $existing;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Draft First
                    |--------------------------------------------------------------------------
                    |
                    | Code needs the generated primary key, so create the record
                    | with a temporary unique code and replace it immediately.
                    |
                    */

                    $temporaryCode =
                        'TEMP-EXEC-AUTH-'
                        .$sourceAuthorization->id
                        .'-'
                        .now()->format('YmdHisv');

                    $record =
                        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::create([
                            /*
                            |--------------------------------------------------------------------------
                            | Source Governance Chain
                            |--------------------------------------------------------------------------
                            */

                            'strategic_plan_controlled_activation_authorization_id' =>
                                $sourceAuthorization->id,

                            'strategic_plan_governance_decision_confirmation_id' =>
                                $sourceAuthorization->strategic_plan_governance_decision_confirmation_id,

                            'strategic_plan_final_governance_decision_id' =>
                                $sourceAuthorization->strategic_plan_final_governance_decision_id,

                            'strategic_plan_decision_validation_id' =>
                                $sourceAuthorization->strategic_plan_decision_validation_id,

                            'strategic_plan_human_decision_id' =>
                                $sourceAuthorization->strategic_plan_human_decision_id,

                            'strategic_plan_decision_id' =>
                                $sourceAuthorization->strategic_plan_decision_id,

                            'strategic_plan_id' =>
                                $sourceAuthorization->strategic_plan_id,

                            'strategic_snapshot_id' =>
                                $sourceAuthorization->strategic_snapshot_id,

                            'operational_snapshot_id' =>
                                $sourceAuthorization->operational_snapshot_id,

                            'lifecycle_snapshot_id' =>
                                $sourceAuthorization->lifecycle_snapshot_id,

                            'decision_scope' =>
                                $sourceAuthorization->decision_scope,

                            'resident_id' =>
                                $sourceAuthorization->resident_id,

                            /*
                            |--------------------------------------------------------------------------
                            | Step 70 Identity
                            |--------------------------------------------------------------------------
                            */

                            'execution_authorization_code' =>
                                $temporaryCode,

                            'execution_authorization_status' =>
                                'PENDING_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION',

                            'execution_authorization_mode' =>
                                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION',

                            /*
                            |--------------------------------------------------------------------------
                            | Source Step 69 Authorization
                            |--------------------------------------------------------------------------
                            */

                            'source_activation_authorization_decision' =>
                                $sourceActivationAuthorizationDecision,

                            'source_activation_authorization_decision_recorded' =>
                                $sourceActivationAuthorizationDecisionRecorded,

                            'source_activation_authorization_made_by_authorized_human' =>
                                $sourceActivationAuthorizationMadeByAuthorizedHuman,

                            'source_activation_authorization_attribution_complete' =>
                                $sourceActivationAuthorizationAttributionComplete,

                            'source_controlled_activation_authorization_completed' =>
                                $sourceControlledActivationAuthorizationCompleted,

                            'source_activation_authorization_outcome' =>
                                $sourceAuthorization->activation_authorization_outcome,

                            'source_activation_authorization_outcome_status' =>
                                $sourceAuthorization->activation_authorization_outcome_status,

                            /*
                            |--------------------------------------------------------------------------
                            | Source Step 69 Execution State
                            |--------------------------------------------------------------------------
                            */

                            'source_controlled_activation_execution_status' =>
                                $sourceControlledActivationExecutionStatus,

                            'source_controlled_activation_execution_authorized' =>
                                $sourceControlledActivationExecutionAuthorized,

                            'source_activation_execution_authorization_code' =>
                                $sourceExecutionAuthorizationCode,

                            'source_execution_authorized_by' =>
                                $sourceExecutionAuthorizedBy,

                            'source_execution_authorizer_role' =>
                                $sourceExecutionAuthorizerRole,

                            'source_execution_authorized_at' =>
                                $sourceExecutionAuthorizedAt,

                            'source_execution_authorization_attribution_complete' =>
                                $sourceExecutionAuthorizationAttributionComplete,

                            /*
                            |--------------------------------------------------------------------------
                            | Step 70 Decision State
                            |--------------------------------------------------------------------------
                            */

                            'controlled_activation_execution_authorization_decision' =>
                                null,

                            'execution_authorization_rationale' =>
                                'Controlled activation execution authorization package prepared from Step 69 controlled activation authorization intelligence for explicitly authorized human governance review. Preparation does not constitute execution authorization and does not execute controlled activation.',

                            'execution_authorization_notes' =>
                                null,

                            'execution_authorization_outcome' =>
                                null,

                            'execution_authorization_outcome_status' =>
                                'PENDING_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION',

                            /*
                            |--------------------------------------------------------------------------
                            | Intelligence
                            |--------------------------------------------------------------------------
                            */

                            'execution_readiness' =>
                                $executionReadiness,

                            'execution_readiness_score' =>
                                $executionReadinessScore,

                            'execution_risk_level' =>
                                $executionRiskLevel,

                            'execution_risk_score' =>
                                $executionRiskScore,

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
                                $combinedPressureScore,

                            /*
                            |--------------------------------------------------------------------------
                            | Structured Package
                            |--------------------------------------------------------------------------
                            */

                            'execution_conditions' =>
                                $executionConditions,

                            'validated_evidence' =>
                                $validatedEvidence,

                            'execution_findings' =>
                                $executionFindings,

                            'execution_restrictions' =>
                                $executionRestrictions,

                            'review_context' =>
                                $reviewContext,

                            'activation_authorization_context' =>
                                $activationAuthorizationContext,

                            'governance_context' =>
                                $governanceContext,

                            'source_context' =>
                                $sourceContext,

                            /*
                            |--------------------------------------------------------------------------
                            | Step 70 Human Authorization Attribution
                            |--------------------------------------------------------------------------
                            */

                            'execution_authorized_by' =>
                                null,

                            'execution_authorizer_role' =>
                                null,

                            'execution_authorized_at' =>
                                null,

                            'execution_authorization_made_by_authorized_human' =>
                                false,

                            'controlled_activation_execution_authorization_completed' =>
                                false,

                            /*
                            |--------------------------------------------------------------------------
                            | Actual Execution Must Remain Unperformed
                            |--------------------------------------------------------------------------
                            */

                            'controlled_activation_execution_status' =>
                                'NOT_EXECUTED',

                            'controlled_activation_executed' =>
                                false,

                            'controlled_activation_execution_code' =>
                                null,

                            'executed_by' =>
                                null,

                            'executor_role' =>
                                null,

                            'executed_at' =>
                                null,

                            'execution_attribution_complete' =>
                                false,

                            /*
                            |--------------------------------------------------------------------------
                            | Autonomous Authority Disabled
                            |--------------------------------------------------------------------------
                            */

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

                            /*
                            |--------------------------------------------------------------------------
                            | Mandatory Human Governance
                            |--------------------------------------------------------------------------
                            */

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
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Final Authorization Code
                    |--------------------------------------------------------------------------
                    */

                    $record->execution_authorization_code =
                        'GOV-STRATEGIC-PLAN-EXEC-AUTH-'
                        .$record->id
                        .'-'
                        .now()->format('YmdHis');

                    $record->save();

                    return $record->fresh();
                }
            );
        } catch (Throwable $exception) {
            return [
                'prepared' => false,

                'status' =>
                    'CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_PREPARATION_FAILED',

                'message' =>
                    'The controlled activation execution authorization record could not be prepared.',

                'error' =>
                    $exception->getMessage(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'prepared' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_PREPARED',

            'message' =>
                'AI governance strategic plan controlled activation execution authorization record prepared successfully for explicitly authorized human execution-authorization review.',

            'controlled_activation_execution_authorization' =>
                $this->recordSummary($executionAuthorization),

            'execution_authorization_context' => [
                'strategic_plan_controlled_activation_authorization_id' =>
                    $sourceAuthorization->id,

                'activation_authorization_code' =>
                    $sourceAuthorization->activation_authorization_code,

                'step_69_validation_status' =>
                    $step69ValidationStatus,

                'step_69_ready_for_closure' =>
                    $step69ReadyForClosure,

                'source_activation_authorization_decision_recorded' =>
                    $sourceActivationAuthorizationDecisionRecorded,

                'source_activation_authorization_made_by_authorized_human' =>
                    $sourceActivationAuthorizationMadeByAuthorizedHuman,

                'source_activation_authorization_attribution_complete' =>
                    $sourceActivationAuthorizationAttributionComplete,

                'source_controlled_activation_authorization_completed' =>
                    $sourceControlledActivationAuthorizationCompleted,

                'source_controlled_activation_execution_authorized' =>
                    $sourceControlledActivationExecutionAuthorized,

                'source_execution_authorization_attribution_complete' =>
                    $sourceExecutionAuthorizationAttributionComplete,

                'source_activation_authorization_blockers' =>
                    $sourceAuthorizationBlockers,

                'source_activation_execution_blockers' =>
                    $sourceExecutionBlockers,

                'source_material_restrictions' =>
                    $sourceMaterialRestrictions,

                'source_critical_restrictions' =>
                    $sourceCriticalRestrictions,

                'source_outstanding_evidence_items' =>
                    $outstandingEvidence,

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
                    $combinedPressureScore,

                'execution_readiness' =>
                    $executionReadiness,

                'execution_readiness_score' =>
                    $executionReadinessScore,

                'execution_risk_level' =>
                    $executionRiskLevel,

                'execution_risk_score' =>
                    $executionRiskScore,

                'execution_condition_count' =>
                    count($executionConditions),

                'execution_restriction_count' =>
                    count($executionRestrictions),

                'controlled_activation_execution_authorization_completed' =>
                    false,

                'controlled_activation_executed' =>
                    false,
            ],

            'execution_conditions' =>
                $executionConditions,

            'execution_restrictions' =>
                $executionRestrictions,

            'validated_evidence' =>
                $validatedEvidence,

            'execution_findings' =>
                $executionFindings,

            'review_context' =>
                $reviewContext,

            'activation_authorization_context' =>
                $activationAuthorizationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'execution_authorization_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Execution Readiness
    |--------------------------------------------------------------------------
    */

    private function determineExecutionReadiness(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $executionBlockers,
        int $materialRestrictions,
        int $criticalRestrictions,
        int $outstandingEvidence
    ): string {
        if (!$sourceDecisionRecorded) {
            return 'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION';
        }

        if (!$sourceMadeByAuthorizedHuman) {
            return 'AWAITING_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_ATTRIBUTION';
        }

        if (!$sourceAttributionComplete) {
            return 'AWAITING_COMPLETE_ACTIVATION_AUTHORIZATION_ATTRIBUTION';
        }

        if (!$sourceAuthorizationCompleted) {
            return 'AWAITING_COMPLETED_CONTROLLED_ACTIVATION_AUTHORIZATION';
        }

        if ($criticalRestrictions > 0) {
            return 'CRITICAL_EXECUTION_RESTRICTIONS_REQUIRE_HUMAN_GOVERNANCE';
        }

        if ($executionBlockers > 0) {
            return 'EXECUTION_AUTHORIZATION_BLOCKED_BY_GOVERNANCE_CONDITIONS';
        }

        if ($outstandingEvidence > 0) {
            return 'AWAITING_EXECUTION_AUTHORIZATION_EVIDENCE';
        }

        if ($materialRestrictions > 0) {
            return 'LIMITED_EXECUTION_AUTHORIZATION_READINESS';
        }

        return 'READY_FOR_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_REVIEW';
    }

    /*
    |--------------------------------------------------------------------------
    | Readiness Score
    |--------------------------------------------------------------------------
    */

    private function calculateExecutionReadinessScore(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $executionBlockers,
        int $materialRestrictions,
        int $criticalRestrictions,
        int $outstandingEvidence,
        float $combinedResolutionScore
    ): float {
        $score = 0.0;

        if ($sourceDecisionRecorded) {
            $score += 15;
        }

        if ($sourceMadeByAuthorizedHuman) {
            $score += 15;
        }

        if ($sourceAttributionComplete) {
            $score += 10;
        }

        if ($sourceAuthorizationCompleted) {
            $score += 20;
        }

        if ($executionBlockers === 0) {
            $score += 15;
        }

        if ($materialRestrictions === 0) {
            $score += 10;
        }

        if ($criticalRestrictions === 0) {
            $score += 5;
        }

        if ($outstandingEvidence === 0) {
            $score += 5;
        }

        $score += min(
            5,
            $combinedResolutionScore * 0.05
        );

        return $this->score($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Execution Risk Score
    |--------------------------------------------------------------------------
    */

    private function calculateExecutionRiskScore(
        float $sourceActivationRiskScore,
        bool $sourceAuthorizationCompleted,
        int $executionBlockers,
        int $materialRestrictions,
        int $criticalRestrictions,
        float $combinedPressureScore,
        float $executionReadinessScore
    ): float {
        $risk =
            ($sourceActivationRiskScore * 0.30)
            + ($combinedPressureScore * 0.20)
            + ((100 - $executionReadinessScore) * 0.20);

        if (!$sourceAuthorizationCompleted) {
            $risk += 15;
        }

        if ($executionBlockers > 0) {
            $risk += min(
                10,
                $executionBlockers * 1.5
            );
        }

        if ($materialRestrictions > 0) {
            $risk += min(
                5,
                $materialRestrictions
            );
        }

        if ($criticalRestrictions > 0) {
            $risk += 10;
        }

        return $this->score($risk);
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Classification
    |--------------------------------------------------------------------------
    */

    private function executionRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 90 =>
                'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            $score >= 75 =>
                'HIGH_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            $score >= 50 =>
                'ELEVATED_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            $score >= 25 =>
                'MODERATE_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            default =>
                'LOW_CONTROLLED_ACTIVATION_EXECUTION_RISK',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Build Conditions
    |--------------------------------------------------------------------------
    */

    private function buildExecutionConditions(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $executionBlockers,
        int $materialRestrictions,
        int $criticalRestrictions,
        int $outstandingEvidence,
        string $executionRiskLevel,
        float $executionRiskScore
    ): array {
        $conditions = [];

        if (!$sourceDecisionRecorded) {
            $conditions[] = [
                'condition_code' =>
                    'CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION_REQUIRED',

                'condition_type' =>
                    'ACTIVATION_AUTHORIZATION',

                'priority_level' =>
                    'CRITICAL',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'An explicitly authorized human controlled activation authorization decision must be recorded before execution authorization progression.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if (!$sourceMadeByAuthorizedHuman) {
            $conditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_REQUIRED',

                'condition_type' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'CRITICAL',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'The source controlled activation authorization must be made by an explicitly authorized human governance authority.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if (!$sourceAttributionComplete) {
            $conditions[] = [
                'condition_code' =>
                    'ACTIVATION_AUTHORIZATION_ATTRIBUTION_REQUIRED',

                'condition_type' =>
                    'AUTHORIZATION_ATTRIBUTION',

                'priority_level' =>
                    'CRITICAL',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Source controlled activation authorization requires complete authorized-human authorizer attribution before execution authorization progression.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if (!$sourceAuthorizationCompleted) {
            $conditions[] = [
                'condition_code' =>
                    'COMPLETED_CONTROLLED_ACTIVATION_AUTHORIZATION_REQUIRED',

                'condition_type' =>
                    'ACTIVATION_AUTHORIZATION_COMPLETION',

                'priority_level' =>
                    'CRITICAL',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Controlled activation authorization must be completed through explicitly authorized human governance before execution authorization.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if ($executionBlockers > 0) {
            $conditions[] = [
                'condition_code' =>
                    'SOURCE_EXECUTION_BLOCKING_CONDITIONS_REMAIN',

                'condition_type' =>
                    'EXECUTION_CONDITION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$executionBlockers} source controlled activation condition(s) currently block execution progression.",

                'value' =>
                    $executionBlockers,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if ($materialRestrictions > 0) {
            $conditions[] = [
                'condition_code' =>
                    'MATERIAL_SOURCE_EXECUTION_RESTRICTIONS_REMAIN',

                'condition_type' =>
                    'EXECUTION_RESTRICTION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$materialRestrictions} material controlled activation restriction(s) remain active and require authorized human governance treatment.",

                'value' =>
                    $materialRestrictions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if ($criticalRestrictions > 0) {
            $conditions[] = [
                'condition_code' =>
                    'CRITICAL_SOURCE_EXECUTION_RESTRICTIONS_REMAIN',

                'condition_type' =>
                    'CRITICAL_RESTRICTION',

                'priority_level' =>
                    'CRITICAL',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$criticalRestrictions} critical controlled activation restriction(s) remain and require explicit authorized-human governance intervention.",

                'value' =>
                    $criticalRestrictions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if ($outstandingEvidence > 0) {
            $conditions[] = [
                'condition_code' =>
                    'EXECUTION_AUTHORIZATION_EVIDENCE_REQUIRED',

                'condition_type' =>
                    'EVIDENCE',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$outstandingEvidence} execution-relevant evidence requirement(s) remain outstanding.",

                'value' =>
                    $outstandingEvidence,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        if ($executionRiskScore >= 75) {
            $conditions[] = [
                'condition_code' =>
                    'ELEVATED_CONTROLLED_ACTIVATION_EXECUTION_RISK',

                'condition_type' =>
                    'RISK',

                'priority_level' =>
                    $executionRiskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "Controlled activation execution risk remains {$executionRiskLevel} with score {$executionRiskScore} and requires explicit authorized human governance review.",

                'value' =>
                    $executionRiskScore,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_execution_authorization' =>
                    true,

                'blocks_actual_execution' =>
                    true,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Human Execution Authorization Is Always Required
        |--------------------------------------------------------------------------
        */

        $conditions[] = [
            'condition_code' =>
                'AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_REQUIRED',

            'condition_type' =>
                'HUMAN_EXECUTION_AUTHORIZATION',

            'priority_level' =>
                'HIGH',

            'condition_status' =>
                'OPEN',

            'condition' =>
                'A separate explicitly authorized human controlled activation execution authorization is required before actual controlled activation execution can be considered.',

            'requires_authorized_human_resolution' =>
                true,

            'automatic_resolution_allowed' =>
                false,

            'blocks_execution_authorization' =>
                false,

            'blocks_actual_execution' =>
                true,
        ];

        return array_values($conditions);
    }

    /*
    |--------------------------------------------------------------------------
    | Build Restrictions
    |--------------------------------------------------------------------------
    */

    private function buildExecutionRestrictions(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $executionBlockers,
        int $materialRestrictions,
        int $criticalRestrictions,
        int $outstandingEvidence,
        string $executionRiskLevel,
        float $executionRiskScore
    ): array {
        $restrictions = [];

        if (!$sourceDecisionRecorded) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_ACTIVATION_AUTHORIZATION_DECISION_NOT_RECORDED',

                'restriction_type' =>
                    'ACTIVATION_AUTHORIZATION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'Execution authorization remains prohibited because the source controlled activation authorization decision has not been recorded.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceMadeByAuthorizedHuman) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_ACTIVATION_AUTHORIZATION_NOT_AUTHORIZED_HUMAN',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'Execution authorization remains prohibited until the source activation authorization is made by explicitly authorized human governance.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceAttributionComplete) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_ACTIVATION_AUTHORIZATION_ATTRIBUTION_INCOMPLETE',

                'restriction_type' =>
                    'AUTHORIZATION_ATTRIBUTION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'Execution authorization remains prohibited while source activation-authorization attribution is incomplete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceAuthorizationCompleted) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_CONTROLLED_ACTIVATION_AUTHORIZATION_INCOMPLETE',

                'restriction_type' =>
                    'ACTIVATION_AUTHORIZATION_COMPLETION',

                'severity' =>
                    'CRITICAL',

                'message' =>
                    'Execution authorization remains prohibited while source controlled activation authorization is incomplete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($executionBlockers > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_EXECUTION_BLOCKING_CONDITIONS',

                'restriction_type' =>
                    'EXECUTION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $executionBlockers,

                'message' =>
                    "{$executionBlockers} controlled activation condition(s) currently block execution progression.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($materialRestrictions > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'MATERIAL_SOURCE_EXECUTION_RESTRICTIONS',

                'restriction_type' =>
                    'EXECUTION_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialRestrictions,

                'message' =>
                    "{$materialRestrictions} material source controlled activation restriction(s) remain active.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($criticalRestrictions > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'CRITICAL_SOURCE_EXECUTION_RESTRICTIONS',

                'restriction_type' =>
                    'CRITICAL_RESTRICTION',

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $criticalRestrictions,

                'message' =>
                    "{$criticalRestrictions} critical source controlled activation restriction(s) remain active.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($outstandingEvidence > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'OUTSTANDING_EXECUTION_AUTHORIZATION_EVIDENCE',

                'restriction_type' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $outstandingEvidence,

                'message' =>
                    "{$outstandingEvidence} execution authorization evidence requirement(s) remain outstanding.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if ($executionRiskScore >= 75) {
            $restrictions[] = [
                'restriction_code' =>
                    'ELEVATED_CONTROLLED_ACTIVATION_EXECUTION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    $executionRiskScore >= 90
                        ? 'CRITICAL'
                        : 'HIGH',

                'value' =>
                    $executionRiskScore,

                'message' =>
                    "Controlled activation execution authorization remains subject to elevated human governance because execution risk is {$executionRiskLevel} with score {$executionRiskScore}.",

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Actual Execution Always Remains Separate
        |--------------------------------------------------------------------------
        */

        $restrictions[] = [
            'restriction_code' =>
                'ACTUAL_CONTROLLED_ACTIVATION_EXECUTION_PROHIBITED_BY_PREPARATION',

            'restriction_type' =>
                'EXECUTION_AUTHORITY',

            'severity' =>
                'HIGH',

            'message' =>
                'Step 70.2 preparation cannot execute controlled activation. Actual execution remains prohibited unless separately governed in the authorized execution layer.',

            'requires_authorized_human_governance' =>
                true,

            'automatic_restriction_removal_allowed' =>
                false,
        ];

        return array_values($restrictions);
    }

    /*
    |--------------------------------------------------------------------------
    | Activation Authorization Attribution
    |--------------------------------------------------------------------------
    */

    private function activationAuthorizationAttributionComplete(
        AIGovernanceStrategicPlanControlledActivationAuthorization $authorization
    ): bool {
        return
            !empty($authorization->authorized_by)
            && !empty($authorization->authorizer_role)
            && $authorization->authorized_at !== null
            && (bool) $authorization->activation_authorization_made_by_authorized_human;
    }

    /*
    |--------------------------------------------------------------------------
    | Source Execution Attribution
    |--------------------------------------------------------------------------
    */

    private function sourceExecutionAttributionComplete(
        AIGovernanceStrategicPlanControlledActivationAuthorization $authorization
    ): bool {
        return
            !empty($authorization->activation_execution_authorization_code)
            && !empty($authorization->execution_authorized_by)
            && !empty($authorization->execution_authorizer_role)
            && $authorization->execution_authorized_at !== null
            && (bool) $authorization->controlled_activation_execution_authorized;
    }

    /*
    |--------------------------------------------------------------------------
    | Record Summary
    |--------------------------------------------------------------------------
    */

    private function recordSummary(
        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization $record
    ): array {
        return [
            'strategic_plan_controlled_activation_execution_authorization_id' =>
                $record->id,

            'execution_authorization_code' =>
                $record->execution_authorization_code,

            'strategic_plan_controlled_activation_authorization_id' =>
                $record->strategic_plan_controlled_activation_authorization_id,

            'strategic_plan_governance_decision_confirmation_id' =>
                $record->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id' =>
                $record->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $record->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $record->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $record->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $record->strategic_plan_id,

            'strategic_snapshot_id' =>
                $record->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $record->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $record->lifecycle_snapshot_id,

            'decision_scope' =>
                $record->decision_scope,

            'resident_id' =>
                $record->resident_id,

            'execution_authorization_status' =>
                $record->execution_authorization_status,

            'execution_authorization_mode' =>
                $record->execution_authorization_mode,

            'source_activation_authorization_decision' =>
                $record->source_activation_authorization_decision,

            'source_activation_authorization_decision_recorded' =>
                (bool) $record->source_activation_authorization_decision_recorded,

            'source_activation_authorization_made_by_authorized_human' =>
                (bool) $record->source_activation_authorization_made_by_authorized_human,

            'source_activation_authorization_attribution_complete' =>
                (bool) $record->source_activation_authorization_attribution_complete,

            'source_controlled_activation_authorization_completed' =>
                (bool) $record->source_controlled_activation_authorization_completed,

            'controlled_activation_execution_authorization_decision' =>
                $record->controlled_activation_execution_authorization_decision,

            'execution_authorization_outcome' =>
                $record->execution_authorization_outcome,

            'execution_authorization_outcome_status' =>
                $record->execution_authorization_outcome_status,

            'execution_readiness' =>
                $record->execution_readiness,

            'execution_readiness_score' =>
                (float) $record->execution_readiness_score,

            'execution_risk_level' =>
                $record->execution_risk_level,

            'execution_risk_score' =>
                (float) $record->execution_risk_score,

            'condition_resolution_score' =>
                (float) $record->condition_resolution_score,

            'evidence_resolution_score' =>
                (float) $record->evidence_resolution_score,

            'combined_resolution_score' =>
                (float) $record->combined_resolution_score,

            'condition_pressure_score' =>
                (float) $record->condition_pressure_score,

            'restriction_pressure_score' =>
                (float) $record->restriction_pressure_score,

            'combined_condition_restriction_pressure_score' =>
                (float) $record->combined_condition_restriction_pressure_score,

            'execution_authorization_made_by_authorized_human' =>
                (bool) $record->execution_authorization_made_by_authorized_human,

            'controlled_activation_execution_authorization_completed' =>
                (bool) $record->controlled_activation_execution_authorization_completed,

            'controlled_activation_execution_status' =>
                $record->controlled_activation_execution_status,

            'controlled_activation_executed' =>
                (bool) $record->controlled_activation_executed,

            'execution_attribution_complete' =>
                (bool) $record->execution_attribution_complete,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Record Context
    |--------------------------------------------------------------------------
    */

    private function existingRecordContext(
        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization $record
    ): array {
        return [
            'strategic_plan_controlled_activation_authorization_id' =>
                $record->strategic_plan_controlled_activation_authorization_id,

            'source_activation_authorization_decision_recorded' =>
                (bool) $record->source_activation_authorization_decision_recorded,

            'source_activation_authorization_made_by_authorized_human' =>
                (bool) $record->source_activation_authorization_made_by_authorized_human,

            'source_activation_authorization_attribution_complete' =>
                (bool) $record->source_activation_authorization_attribution_complete,

            'source_controlled_activation_authorization_completed' =>
                (bool) $record->source_controlled_activation_authorization_completed,

            'execution_readiness' =>
                $record->execution_readiness,

            'execution_readiness_score' =>
                (float) $record->execution_readiness_score,

            'execution_risk_level' =>
                $record->execution_risk_level,

            'execution_risk_score' =>
                (float) $record->execution_risk_score,

            'execution_condition_count' =>
                count($record->execution_conditions ?? []),

            'execution_restriction_count' =>
                count($record->execution_restrictions ?? []),

            'controlled_activation_execution_authorization_completed' =>
                (bool) $record->controlled_activation_execution_authorization_completed,

            'controlled_activation_executed' =>
                (bool) $record->controlled_activation_executed,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Helper
    |--------------------------------------------------------------------------
    */

    private function sourceUnavailable(
        string $status,
        string $message
    ): array {
        return [
            'prepared' =>
                false,

            'status' =>
                $status,

            'message' =>
                $message,
        ];
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
    | Step 70.2 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_preparation_enabled' =>
                true,

            'prepared_record_is_execution_authorization' =>
                false,

            'prepared_record_is_actual_execution' =>
                false,

            'ai_makes_execution_authorization_decision' =>
                false,

            'ai_records_execution_authorization_decision' =>
                false,

            'ai_completes_execution_authorization' =>
                false,

            'ai_executes_controlled_activation' =>
                false,

            'ai_activates_strategic_plan' =>
                false,

            'ai_changes_source_activation_authorization' =>
                false,

            'ai_changes_governance_confirmation' =>
                false,

            'ai_changes_final_governance_decision' =>
                false,

            'execution_authorization_preparation_changes_source_authorization' =>
                false,

            'execution_authorization_preparation_changes_execution_status' =>
                false,

            'execution_authorization_preparation_changes_plan_status' =>
                false,

            'execution_authorization_preparation_changes_action_state' =>
                false,

            'execution_authorization_preparation_resolves_conditions' =>
                false,

            'execution_authorization_preparation_waives_conditions' =>
                false,

            'execution_authorization_preparation_removes_restrictions' =>
                false,

            'execution_authorization_preparation_validates_evidence' =>
                false,

            'execution_readiness_score_authorizes_execution' =>
                false,

            'execution_risk_score_authorizes_execution' =>
                false,

            'condition_resolution_score_authorizes_execution' =>
                false,

            'evidence_resolution_score_authorizes_execution' =>
                false,

            'combined_resolution_score_authorizes_execution' =>
                false,

            'condition_pressure_score_authorizes_execution' =>
                false,

            'restriction_pressure_score_authorizes_execution' =>
                false,

            'execution_authorization_preparation_authorizes_ai_change' =>
                false,

            'execution_authorization_preparation_authorizes_execution' =>
                false,

            'execution_authorization_preparation_authorizes_deployment' =>
                false,

            'execution_authorization_preparation_authorizes_rollback' =>
                false,

            'execution_authorization_preparation_authorizes_clinical_action' =>
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
                'Step 70.2 prepares a structured strategic plan controlled activation execution authorization record from Step 69 controlled activation authorization intelligence for explicitly authorized human governance review. Preparation does not make, record, or complete an execution authorization decision; execute controlled activation; activate the strategic plan; change source activation authorization, governance confirmation, or final governance decisions; resolve or waive conditions; remove restrictions; validate evidence; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Execution authorization authority remains reserved exclusively for explicitly authorized human governance, and actual controlled activation execution remains a distinct future governed execution function.',
        ];
    }
}