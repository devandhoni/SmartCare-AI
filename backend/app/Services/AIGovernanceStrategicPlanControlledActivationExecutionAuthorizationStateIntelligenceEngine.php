<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationStateIntelligenceEngine
{
    /**
     * Analyze the current Step 70 controlled activation execution
     * authorization state.
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
                    'No strategic plan controlled activation execution authorization record is available for state intelligence analysis.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Structured Source Arrays
        |--------------------------------------------------------------------------
        */

        $conditions =
            is_array($authorization->execution_conditions)
                ? $authorization->execution_conditions
                : [];

        $restrictions =
            is_array($authorization->execution_restrictions)
                ? $authorization->execution_restrictions
                : [];

        $validatedEvidence =
            is_array($authorization->validated_evidence)
                ? $authorization->validated_evidence
                : [];

        $reviewContext =
            is_array($authorization->review_context)
                ? $authorization->review_context
                : [];

        $activationAuthorizationContext =
            is_array($authorization->activation_authorization_context)
                ? $authorization->activation_authorization_context
                : [];

        $governanceContext =
            is_array($authorization->governance_context)
                ? $authorization->governance_context
                : [];

        $sourceContext =
            is_array($authorization->source_context)
                ? $authorization->source_context
                : [];

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Authorization State
        |--------------------------------------------------------------------------
        */

        $sourceActivationAuthorizationDecisionRecorded =
            (bool) $authorization->source_activation_authorization_decision_recorded;

        $sourceActivationAuthorizationMadeByAuthorizedHuman =
            (bool) $authorization->source_activation_authorization_made_by_authorized_human;

        $sourceActivationAuthorizationAttributionComplete =
            (bool) $authorization->source_activation_authorization_attribution_complete;

        $sourceControlledActivationAuthorizationCompleted =
            (bool) $authorization->source_controlled_activation_authorization_completed;

        $sourceControlledActivationExecutionAuthorized =
            (bool) $authorization->source_controlled_activation_execution_authorized;

        $sourceExecutionAuthorizationAttributionComplete =
            (bool) $authorization->source_execution_authorization_attribution_complete;

        /*
        |--------------------------------------------------------------------------
        | Step 70 Execution Authorization State
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationDecisionRecorded =
            !empty(
                $authorization->controlled_activation_execution_authorization_decision
            );

        $executionAuthorizationMadeByAuthorizedHuman =
            (bool) $authorization->execution_authorization_made_by_authorized_human;

        $executionAuthorizationAttributionComplete =
            $this->executionAuthorizationAttributionComplete(
                $authorization
            );

        $executionAuthorizationCompleted =
            (bool) $authorization->controlled_activation_execution_authorization_completed;

        /*
        |--------------------------------------------------------------------------
        | Actual Controlled Activation Execution State
        |--------------------------------------------------------------------------
        */

        $controlledActivationExecuted =
            (bool) $authorization->controlled_activation_executed;

        $actualExecutionAttributionComplete =
            $this->actualExecutionAttributionComplete(
                $authorization
            );

        /*
        |--------------------------------------------------------------------------
        | Condition Intelligence
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            count($conditions);

        $openConditions =
            count(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED'
                )
            );

        $resolvedConditions =
            $totalConditions - $openConditions;

        $blockingExecutionAuthorizationConditions =
            count(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        ($condition['blocks_execution_authorization'] ?? false) === true
                        && strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED'
                )
            );

        $blockingActualExecutionConditions =
            count(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        ($condition['blocks_actual_execution'] ?? false) === true
                        && strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED'
                )
            );

        $criticalOpenConditions =
            count(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        strtoupper(
                            (string) ($condition['priority_level'] ?? '')
                        ) === 'CRITICAL'
                        && strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED'
                )
            );

        $highOpenConditions =
            count(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        strtoupper(
                            (string) ($condition['priority_level'] ?? '')
                        ) === 'HIGH'
                        && strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Restriction Intelligence
        |--------------------------------------------------------------------------
        */

        $totalRestrictions =
            count($restrictions);

        $materialRestrictions =
            count(
                array_filter(
                    $restrictions,
                    fn (array $restriction): bool =>
                        in_array(
                            strtoupper(
                                (string) ($restriction['severity'] ?? '')
                            ),
                            ['HIGH', 'CRITICAL'],
                            true
                        )
                )
            );

        $criticalRestrictions =
            count(
                array_filter(
                    $restrictions,
                    fn (array $restriction): bool =>
                        strtoupper(
                            (string) ($restriction['severity'] ?? '')
                        ) === 'CRITICAL'
                )
            );

        $highRestrictions =
            count(
                array_filter(
                    $restrictions,
                    fn (array $restriction): bool =>
                        strtoupper(
                            (string) ($restriction['severity'] ?? '')
                        ) === 'HIGH'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Evidence Intelligence
        |--------------------------------------------------------------------------
        */

        $totalEvidenceItems =
            count($validatedEvidence);

        $outstandingEvidenceItems =
            count(
                array_filter(
                    $validatedEvidence,
                    fn (array $evidence): bool =>
                        ($evidence['evidence_outstanding'] ?? false) === true
                )
            );

        $validatedEvidenceItems =
            count(
                array_filter(
                    $validatedEvidence,
                    fn (array $evidence): bool =>
                        ($evidence['evidence_validated'] ?? false) === true
                )
            );

        $blockingExecutionEvidenceItems =
            count(
                array_filter(
                    $validatedEvidence,
                    fn (array $evidence): bool =>
                        (
                            ($evidence['blocks_actual_execution'] ?? false) === true
                            || ($evidence['blocks_execution_authorization'] ?? false) === true
                        )
                        && ($evidence['evidence_outstanding'] ?? false) === true
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $executionReadinessScore =
            $this->score(
                $authorization->execution_readiness_score
            );

        $executionRiskScore =
            $this->score(
                $authorization->execution_risk_score
            );

        $conditionResolutionScore =
            $this->score(
                $authorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $authorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $authorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $authorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $authorization->restriction_pressure_score
            );

        $combinedConditionRestrictionPressureScore =
            $this->score(
                $authorization->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization State
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationState =
            $this->determineExecutionAuthorizationState(
                $sourceActivationAuthorizationDecisionRecorded,
                $sourceActivationAuthorizationMadeByAuthorizedHuman,
                $sourceActivationAuthorizationAttributionComplete,
                $sourceControlledActivationAuthorizationCompleted,
                $executionAuthorizationDecisionRecorded,
                $executionAuthorizationMadeByAuthorizedHuman,
                $executionAuthorizationAttributionComplete,
                $executionAuthorizationCompleted,
                $blockingExecutionAuthorizationConditions,
                $criticalRestrictions
            );

        /*
        |--------------------------------------------------------------------------
        | Readiness Classification
        |--------------------------------------------------------------------------
        */

        $executionReadinessClassification =
            $this->readinessClassification(
                $executionReadinessScore
            );

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationConfidence =
            $this->confidenceClassification(
                $executionReadinessScore,
                $executionRiskScore,
                $blockingExecutionAuthorizationConditions,
                $criticalRestrictions,
                $sourceControlledActivationAuthorizationCompleted
            );

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionLevel =
            $this->managementAttentionLevel(
                $executionRiskScore,
                $criticalRestrictions,
                $criticalOpenConditions,
                $blockingExecutionAuthorizationConditions,
                $blockingActualExecutionConditions
            );

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'NORMAL';

        $immediateHumanInterventionRequired =
            $humanManagementAttentionLevel === 'CRITICAL';

        /*
        |--------------------------------------------------------------------------
        | Progression State
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationProgressionBlocked =
            !$sourceActivationAuthorizationDecisionRecorded
            || !$sourceActivationAuthorizationMadeByAuthorizedHuman
            || !$sourceActivationAuthorizationAttributionComplete
            || !$sourceControlledActivationAuthorizationCompleted
            || $blockingExecutionAuthorizationConditions > 0
            || $criticalRestrictions > 0;

        $actualExecutionProgressionBlocked =
            !$executionAuthorizationDecisionRecorded
            || !$executionAuthorizationMadeByAuthorizedHuman
            || !$executionAuthorizationAttributionComplete
            || !$executionAuthorizationCompleted
            || $blockingActualExecutionConditions > 0
            || $criticalRestrictions > 0
            || $blockingExecutionEvidenceItems > 0;

        /*
        |--------------------------------------------------------------------------
        | State Summary
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationStateSummary = [
            'execution_authorization_state' =>
                $executionAuthorizationState,

            'execution_authorization_status' =>
                $authorization->execution_authorization_status,

            'execution_authorization_mode' =>
                $authorization->execution_authorization_mode,

            'execution_authorization_decision' =>
                $authorization->controlled_activation_execution_authorization_decision,

            'execution_authorization_outcome' =>
                $authorization->execution_authorization_outcome,

            'execution_authorization_outcome_status' =>
                $authorization->execution_authorization_outcome_status,

            'execution_readiness' =>
                $authorization->execution_readiness,

            'execution_readiness_score' =>
                $executionReadinessScore,

            'execution_readiness_classification' =>
                $executionReadinessClassification,

            'execution_authorization_confidence' =>
                $executionAuthorizationConfidence,

            'execution_risk_level' =>
                $authorization->execution_risk_level,

            'execution_risk_score' =>
                $executionRiskScore,

            'execution_authorization_progression_blocked' =>
                $executionAuthorizationProgressionBlocked,

            'actual_execution_progression_blocked' =>
                $actualExecutionProgressionBlocked,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'execution_authorization_decision_recorded' =>
                $executionAuthorizationDecisionRecorded,

            'execution_authorization_made_by_authorized_human' =>
                $executionAuthorizationMadeByAuthorizedHuman,

            'execution_authorization_attribution_complete' =>
                $executionAuthorizationAttributionComplete,

            'controlled_activation_execution_authorization_completed' =>
                $executionAuthorizationCompleted,

            'controlled_activation_execution_status' =>
                $authorization->controlled_activation_execution_status,

            'controlled_activation_executed' =>
                $controlledActivationExecuted,

            'actual_execution_attribution_complete' =>
                $actualExecutionAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Activation Authorization Context
        |--------------------------------------------------------------------------
        */

        $sourceActivationAuthorizationStateContext = [
            'source_activation_authorization_decision' =>
                $authorization->source_activation_authorization_decision,

            'source_activation_authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'source_activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'source_activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'source_controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'source_activation_authorization_outcome' =>
                $authorization->source_activation_authorization_outcome,

            'source_activation_authorization_outcome_status' =>
                $authorization->source_activation_authorization_outcome_status,

            'source_controlled_activation_execution_status' =>
                $authorization->source_controlled_activation_execution_status,

            'source_controlled_activation_execution_authorized' =>
                $sourceControlledActivationExecutionAuthorized,

            'source_activation_execution_authorization_code' =>
                $authorization->source_activation_execution_authorization_code,

            'source_execution_authorized_by' =>
                $authorization->source_execution_authorized_by,

            'source_execution_authorizer_role' =>
                $authorization->source_execution_authorizer_role,

            'source_execution_authorized_at' =>
                $authorization->source_execution_authorized_at,

            'source_execution_authorization_attribution_complete' =>
                $sourceExecutionAuthorizationAttributionComplete,

            'source_activation_authorization_ready_for_execution_authorization' =>
                $sourceActivationAuthorizationDecisionRecorded
                && $sourceActivationAuthorizationMadeByAuthorizedHuman
                && $sourceActivationAuthorizationAttributionComplete
                && $sourceControlledActivationAuthorizationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization Attribution Context
        |--------------------------------------------------------------------------
        */

        $executionAuthorizationContext = [
            'execution_authorized_by' =>
                $authorization->execution_authorized_by,

            'execution_authorizer_role' =>
                $authorization->execution_authorizer_role,

            'execution_authorized_at' =>
                $authorization->execution_authorized_at,

            'execution_authorizer_identified' =>
                !empty($authorization->execution_authorized_by),

            'execution_authorizer_role_available' =>
                !empty($authorization->execution_authorizer_role),

            'execution_authorization_timestamp_available' =>
                $authorization->execution_authorized_at !== null,

            'execution_authorization_decision_recorded' =>
                $executionAuthorizationDecisionRecorded,

            'execution_authorization_made_by_authorized_human' =>
                $executionAuthorizationMadeByAuthorizedHuman,

            'execution_authorization_attribution_complete' =>
                $executionAuthorizationAttributionComplete,

            'controlled_activation_execution_authorization_completed' =>
                $executionAuthorizationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Actual Execution Context
        |--------------------------------------------------------------------------
        */

        $actualExecutionContext = [
            'controlled_activation_execution_status' =>
                $authorization->controlled_activation_execution_status,

            'controlled_activation_executed' =>
                $controlledActivationExecuted,

            'controlled_activation_execution_code' =>
                $authorization->controlled_activation_execution_code,

            'executed_by' =>
                $authorization->executed_by,

            'executor_role' =>
                $authorization->executor_role,

            'executed_at' =>
                $authorization->executed_at,

            'executor_identified' =>
                !empty($authorization->executed_by),

            'executor_role_available' =>
                !empty($authorization->executor_role),

            'execution_timestamp_available' =>
                $authorization->executed_at !== null,

            'actual_execution_attribution_complete' =>
                $actualExecutionAttributionComplete,

            'actual_execution_separate_from_execution_authorization' =>
                true,
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition Summary
        |--------------------------------------------------------------------------
        */

        $conditionSummary = [
            'total_conditions' =>
                $totalConditions,

            'open_conditions' =>
                $openConditions,

            'resolved_conditions' =>
                $resolvedConditions,

            'blocking_execution_authorization_conditions' =>
                $blockingExecutionAuthorizationConditions,

            'blocking_actual_execution_conditions' =>
                $blockingActualExecutionConditions,

            'critical_open_conditions' =>
                $criticalOpenConditions,

            'high_open_conditions' =>
                $highOpenConditions,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'condition_pressure_score' =>
                $conditionPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Restriction Summary
        |--------------------------------------------------------------------------
        */

        $restrictionSummary = [
            'total_restrictions' =>
                $totalRestrictions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'high_restrictions' =>
                $highRestrictions,

            'restriction_pressure_score' =>
                $restrictionPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Evidence Summary
        |--------------------------------------------------------------------------
        */

        $evidenceSummary = [
            'total_evidence_items' =>
                $totalEvidenceItems,

            'validated_evidence_items' =>
                $validatedEvidenceItems,

            'outstanding_evidence_items' =>
                $outstandingEvidenceItems,

            'blocking_execution_evidence_items' =>
                $blockingExecutionEvidenceItems,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $stateFindings = [
            "Controlled activation execution authorization state intelligence is based on Step 70 execution authorization record {$authorization->id}.",

            'Current execution authorization state is '
                .$executionAuthorizationState.'.',

            'Current execution authorization status is '
                .$authorization->execution_authorization_status.'.',

            'Current execution authorization readiness is '
                .$authorization->execution_readiness
                .' with score '
                .$executionReadinessScore.'.',

            'Current execution authorization readiness classification is '
                .$executionReadinessClassification.'.',

            'Current execution authorization confidence is '
                .$executionAuthorizationConfidence.'.',

            'Current controlled activation execution risk is '
                .($authorization->execution_risk_level ?? 'UNKNOWN')
                .' with score '
                .$executionRiskScore.'.',

            'Source activation authorization decision recorded is '
                .($sourceActivationAuthorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source activation authorization made by authorized human is '
                .($sourceActivationAuthorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source activation authorization attribution complete is '
                .($sourceActivationAuthorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Source controlled activation authorization completed is '
                .($sourceControlledActivationAuthorizationCompleted ? 'YES' : 'NO').'.',

            "{$blockingExecutionAuthorizationConditions} condition(s) currently block execution authorization.",

            "{$blockingActualExecutionConditions} condition(s) currently block actual controlled activation execution.",

            "{$criticalOpenConditions} critical execution authorization condition(s) remain open.",

            "{$materialRestrictions} material execution restriction(s) remain active.",

            "{$criticalRestrictions} critical execution restriction(s) remain active.",

            "{$outstandingEvidenceItems} execution evidence item(s) remain outstanding.",

            "{$blockingExecutionEvidenceItems} evidence item(s) currently block execution progression.",

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current condition pressure score is '
                .$conditionPressureScore.'.',

            'Current restriction pressure score is '
                .$restrictionPressureScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedConditionRestrictionPressureScore.'.',

            'Execution authorization decision recorded is '
                .($executionAuthorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Execution authorization made by authorized human is '
                .($executionAuthorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Execution authorization attribution complete is '
                .($executionAuthorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation execution authorization completed is '
                .($executionAuthorizationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation actually executed is '
                .($controlledActivationExecuted ? 'YES' : 'NO').'.',

            'Actual controlled activation execution attribution complete is '
                .($actualExecutionAttributionComplete ? 'YES' : 'NO').'.',

            'Execution authorization progression is '
                .($executionAuthorizationProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Actual controlled activation execution progression is '
                .($actualExecutionProgressionBlocked ? 'BLOCKED' : 'NOT_BLOCKED').'.',

            'Step 70.3 state intelligence remains informational and does not authorize execution or execute controlled activation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceActivationAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'Record the required controlled activation authorization decision through explicitly authorized human governance.';
        }

        if (!$sourceActivationAuthorizationMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source controlled activation authorization is made by an explicitly authorized human governance authority.';
        }

        if (!$sourceActivationAuthorizationAttributionComplete) {
            $managementPriorities[] =
                'Complete source controlled activation authorization attribution before execution authorization progression.';
        }

        if (!$sourceControlledActivationAuthorizationCompleted) {
            $managementPriorities[] =
                'Complete controlled activation authorization through explicitly authorized human governance before execution authorization.';
        }

        if ($criticalOpenConditions > 0) {
            $managementPriorities[] =
                "Escalate {$criticalOpenConditions} critical execution authorization condition(s) for immediate authorized-human governance review.";
        }

        if ($blockingExecutionAuthorizationConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingExecutionAuthorizationConditions} condition(s) blocking execution authorization progression.";
        }

        if ($blockingActualExecutionConditions > 0) {
            $managementPriorities[] =
                "Keep actual controlled activation execution prohibited while {$blockingActualExecutionConditions} execution-blocking condition(s) remain.";
        }

        if ($criticalRestrictions > 0) {
            $managementPriorities[] =
                "Maintain immediate authorized-human governance oversight for {$criticalRestrictions} critical execution restriction(s).";
        }

        if ($materialRestrictions > 0) {
            $managementPriorities[] =
                "Maintain explicit governance treatment for {$materialRestrictions} material execution restriction(s).";
        }

        if ($executionRiskScore >= 90) {
            $managementPriorities[] =
                'Maintain critical authorized-human governance oversight while controlled activation execution risk remains critical.';
        }

        if (!$executionAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'A separate explicitly authorized human execution authorization decision remains required.';
        }

        if (!$executionAuthorizationCompleted) {
            $managementPriorities[] =
                'Keep controlled activation execution authorization incomplete until all human-governance requirements are satisfied.';
        }

        if (!$controlledActivationExecuted) {
            $managementPriorities[] =
                'Preserve separation between execution authorization and actual controlled activation execution.';
        }

        $managementPriorities[] =
            'Do not interpret readiness, risk, resolution, pressure, condition, restriction, or evidence classifications as authority to execute controlled activation.';

        $managementPriorities[] =
            'Preserve authorized-human governance authority, source traceability, authorization attribution, execution attribution, safety controls, and authority separation throughout execution progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_STATE_INTELLIGENCE_AVAILABLE',

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

            'execution_authorization_state' =>
                $executionAuthorizationStateSummary,

            'condition_summary' =>
                $conditionSummary,

            'restriction_summary' =>
                $restrictionSummary,

            'evidence_summary' =>
                $evidenceSummary,

            'source_activation_authorization_context' =>
                $sourceActivationAuthorizationStateContext,

            'execution_authorization_context' =>
                $executionAuthorizationContext,

            'actual_execution_context' =>
                $actualExecutionContext,

            'execution_conditions' =>
                $conditions,

            'execution_restrictions' =>
                $restrictions,

            'validated_evidence' =>
                $validatedEvidence,

            'review_context' =>
                $reviewContext,

            'activation_authorization_context' =>
                $activationAuthorizationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'state_findings' =>
                $stateFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'execution_authorization_state_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Execution Authorization State
    |--------------------------------------------------------------------------
    */

    private function determineExecutionAuthorizationState(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        bool $executionDecisionRecorded,
        bool $executionAuthorizationMadeByAuthorizedHuman,
        bool $executionAuthorizationAttributionComplete,
        bool $executionAuthorizationCompleted,
        int $blockingExecutionAuthorizationConditions,
        int $criticalRestrictions
    ): string {
        if (!$sourceDecisionRecorded) {
            return 'PENDING_SOURCE_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION';
        }

        if (!$sourceMadeByAuthorizedHuman) {
            return 'PENDING_SOURCE_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION';
        }

        if (!$sourceAttributionComplete) {
            return 'PENDING_SOURCE_ACTIVATION_AUTHORIZATION_ATTRIBUTION';
        }

        if (!$sourceAuthorizationCompleted) {
            return 'PENDING_COMPLETED_CONTROLLED_ACTIVATION_AUTHORIZATION';
        }

        if ($criticalRestrictions > 0) {
            return 'CRITICAL_EXECUTION_AUTHORIZATION_GOVERNANCE_RESTRICTIONS';
        }

        if ($blockingExecutionAuthorizationConditions > 0) {
            return 'BLOCKED_EXECUTION_AUTHORIZATION_GOVERNANCE_CONDITIONS';
        }

        if (!$executionDecisionRecorded) {
            return 'PENDING_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_DECISION';
        }

        if (!$executionAuthorizationMadeByAuthorizedHuman) {
            return 'PENDING_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION_ATTRIBUTION';
        }

        if (!$executionAuthorizationAttributionComplete) {
            return 'PENDING_COMPLETE_EXECUTION_AUTHORIZATION_ATTRIBUTION';
        }

        if (!$executionAuthorizationCompleted) {
            return 'PENDING_EXECUTION_AUTHORIZATION_COMPLETION';
        }

        return 'CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_COMPLETED';
    }

    /*
    |--------------------------------------------------------------------------
    | Readiness Classification
    |--------------------------------------------------------------------------
    */

    private function readinessClassification(float $score): string
    {
        return match (true) {
            $score >= 90 =>
                'VERY_HIGH_EXECUTION_AUTHORIZATION_READINESS',

            $score >= 75 =>
                'HIGH_EXECUTION_AUTHORIZATION_READINESS',

            $score >= 60 =>
                'MODERATE_EXECUTION_AUTHORIZATION_READINESS',

            $score >= 40 =>
                'LIMITED_EXECUTION_AUTHORIZATION_READINESS',

            $score >= 20 =>
                'VERY_LIMITED_EXECUTION_AUTHORIZATION_READINESS',

            default =>
                'EXTREMELY_LIMITED_EXECUTION_AUTHORIZATION_READINESS',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Confidence Classification
    |--------------------------------------------------------------------------
    */

    private function confidenceClassification(
        float $readinessScore,
        float $riskScore,
        int $blockingConditions,
        int $criticalRestrictions,
        bool $sourceAuthorizationCompleted
    ): string {
        if (
            !$sourceAuthorizationCompleted
            || $riskScore >= 90
            || $criticalRestrictions > 0
            || $readinessScore < 20
        ) {
            return 'EXTREMELY_LIMITED';
        }

        if (
            $riskScore >= 75
            || $blockingConditions > 0
            || $readinessScore < 40
        ) {
            return 'LIMITED';
        }

        if (
            $riskScore >= 50
            || $readinessScore < 60
        ) {
            return 'MODERATE';
        }

        if ($readinessScore < 80) {
            return 'HIGH';
        }

        return 'VERY_HIGH';
    }

    /*
    |--------------------------------------------------------------------------
    | Management Attention
    |--------------------------------------------------------------------------
    */

    private function managementAttentionLevel(
        float $riskScore,
        int $criticalRestrictions,
        int $criticalOpenConditions,
        int $authorizationBlockers,
        int $executionBlockers
    ): string {
        if (
            $riskScore >= 90
            || $criticalRestrictions > 0
            || $criticalOpenConditions > 0
        ) {
            return 'CRITICAL';
        }

        if (
            $riskScore >= 75
            || $authorizationBlockers > 0
            || $executionBlockers > 0
        ) {
            return 'HIGH';
        }

        if ($riskScore >= 50) {
            return 'MODERATE';
        }

        if ($riskScore >= 25) {
            return 'ELEVATED';
        }

        return 'NORMAL';
    }

    /*
    |--------------------------------------------------------------------------
    | Step 70 Authorization Attribution
    |--------------------------------------------------------------------------
    */

    private function executionAuthorizationAttributionComplete(
        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization $authorization
    ): bool {
        return
            !empty(
                $authorization->controlled_activation_execution_authorization_decision
            )
            && !empty(
                $authorization->execution_authorized_by
            )
            && !empty(
                $authorization->execution_authorizer_role
            )
            && $authorization->execution_authorized_at !== null
            && (bool) $authorization->execution_authorization_made_by_authorized_human;
    }

    /*
    |--------------------------------------------------------------------------
    | Actual Execution Attribution
    |--------------------------------------------------------------------------
    */

    private function actualExecutionAttributionComplete(
        AIGovernanceStrategicPlanControlledActivationExecutionAuthorization $authorization
    ): bool {
        return
            (bool) $authorization->controlled_activation_executed
            && !empty(
                $authorization->controlled_activation_execution_code
            )
            && !empty(
                $authorization->executed_by
            )
            && !empty(
                $authorization->executor_role
            )
            && $authorization->executed_at !== null
            && (bool) $authorization->execution_attribution_complete;
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
    | Step 70.3 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_state_intelligence_enabled' =>
                true,

            'execution_state_intelligence_is_execution_authorization' =>
                false,

            'execution_state_intelligence_is_actual_execution' =>
                false,

            'execution_state_intelligence_makes_execution_authorization_decision' =>
                false,

            'execution_state_intelligence_records_execution_authorization_decision' =>
                false,

            'execution_state_intelligence_completes_execution_authorization' =>
                false,

            'execution_state_intelligence_executes_controlled_activation' =>
                false,

            'execution_state_intelligence_activates_strategic_plan' =>
                false,

            'execution_state_intelligence_changes_source_activation_authorization' =>
                false,

            'execution_state_intelligence_changes_execution_authorization_status' =>
                false,

            'execution_state_intelligence_changes_execution_authorization_decision' =>
                false,

            'execution_state_intelligence_changes_execution_authorization_outcome' =>
                false,

            'execution_state_intelligence_changes_execution_status' =>
                false,

            'execution_state_intelligence_changes_plan_status' =>
                false,

            'execution_state_intelligence_changes_action_state' =>
                false,

            'execution_state_intelligence_changes_priority' =>
                false,

            'execution_state_intelligence_changes_eligibility' =>
                false,

            'execution_state_intelligence_resolves_conditions' =>
                false,

            'execution_state_intelligence_waives_conditions' =>
                false,

            'execution_state_intelligence_removes_restrictions' =>
                false,

            'execution_state_intelligence_downgrades_restrictions' =>
                false,

            'execution_state_intelligence_resolves_dependencies' =>
                false,

            'execution_state_intelligence_validates_evidence' =>
                false,

            'execution_readiness_score_authorizes_execution_authorization' =>
                false,

            'execution_readiness_score_authorizes_actual_execution' =>
                false,

            'execution_risk_score_authorizes_execution_authorization' =>
                false,

            'execution_risk_score_authorizes_actual_execution' =>
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

            'execution_state_intelligence_authorizes_ai_change' =>
                false,

            'execution_state_intelligence_authorizes_execution' =>
                false,

            'execution_state_intelligence_authorizes_deployment' =>
                false,

            'execution_state_intelligence_authorizes_rollback' =>
                false,

            'execution_state_intelligence_authorizes_clinical_action' =>
                false,

            'execution_state_intelligence_overrides_human_review' =>
                false,

            'execution_state_intelligence_overrides_activation_authorization' =>
                false,

            'execution_state_intelligence_overrides_execution_authorization' =>
                false,

            'execution_state_intelligence_overrides_actual_execution_authority' =>
                false,

            'execution_state_intelligence_overrides_evidence_requirements' =>
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
                'Step 70.3 controlled activation execution authorization state intelligence evaluates the current execution-authorization record, source controlled-activation authorization state, execution-authorization attribution, actual-execution attribution, execution conditions, restrictions, evidence state, readiness, risk, resolution progress, pressure, progression blocking, and management attention requirements for explicitly authorized human governance. State intelligence does not make, record, or complete an execution authorization decision; execute controlled activation; activate the strategic plan; alter source activation authorization or governance decisions; resolve or waive conditions; remove or downgrade restrictions; validate evidence; modify AI behavior; deploy updates; trigger rollback; or initiate clinical action. Execution authorization authority remains reserved exclusively for explicitly authorized human governance, and actual controlled activation execution remains a separate governed authority.',
        ];
    }
}