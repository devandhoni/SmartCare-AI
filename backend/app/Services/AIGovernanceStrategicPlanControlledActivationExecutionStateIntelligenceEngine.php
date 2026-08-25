<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;
use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionStateIntelligenceEngine
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

        $authorization = AIGovernanceStrategicPlanControlledActivationExecutionAuthorization::find(
            $execution->strategic_plan_controlled_activation_execution_authorization_id
        );

        if (!$authorization) {
            return [
                'analysis_completed' => false,
                'status' => 'SOURCE_EXECUTION_AUTHORIZATION_NOT_AVAILABLE',
                'message' => 'The source Step 70 execution authorization record is unavailable.',
            ];
        }

        $sourceDecisionRecorded =
            !is_null($authorization->controlled_activation_execution_authorization_decision);

        $sourceMadeByAuthorizedHuman =
            (bool) $authorization->execution_authorization_made_by_authorized_human;

        $sourceAttributionComplete =
            !empty($authorization->execution_authorized_by)
            && !empty($authorization->execution_authorizer_role)
            && !is_null($authorization->execution_authorized_at)
            && $sourceMadeByAuthorizedHuman;

        $sourceAuthorizationCompleted =
            (bool) $authorization->controlled_activation_execution_authorization_completed;

        $sourceAuthorizationValid =
            $sourceDecisionRecorded
            && $sourceMadeByAuthorizedHuman
            && $sourceAttributionComplete
            && $sourceAuthorizationCompleted;

        $controlledActivationExecuted =
            (bool) $execution->controlled_activation_executed;

        $executionAttributionComplete =
            !empty($execution->executed_by)
            && !empty($execution->executor_role)
            && !is_null($execution->executed_at)
            && (bool) $execution->execution_made_by_authorized_human
            && (bool) $execution->execution_attribution_complete;

        $conditions = collect($execution->execution_conditions ?? []);

        $blockingConditions = $conditions->filter(
            fn ($condition) =>
                (bool) ($condition['blocking_execution'] ?? false) === true
                && (bool) ($condition['satisfied'] ?? false) === false
        )->count();

        $satisfiedConditions = $conditions->filter(
            fn ($condition) => (bool) ($condition['satisfied'] ?? false) === true
        )->count();

        $totalConditions = $conditions->count();

        $conditionResolutionScore = $totalConditions > 0
            ? round(($satisfiedConditions / $totalConditions) * 100, 2)
            : 0.0;

        $evidence = collect($execution->validated_evidence ?? []);

        $validatedEvidence = $evidence->filter(
            fn ($item) => (bool) ($item['evidence_validated'] ?? false) === true
        )->count();

        $evidenceResolutionScore = $evidence->count() > 0
            ? round(($validatedEvidence / $evidence->count()) * 100, 2)
            : 0.0;

        $executionState = match (true) {
            $controlledActivationExecuted && $executionAttributionComplete
                => 'CONTROLLED_ACTIVATION_EXECUTED_WITH_COMPLETE_ATTRIBUTION',

            $controlledActivationExecuted && !$executionAttributionComplete
                => 'CONTROLLED_ACTIVATION_EXECUTED_WITH_INCOMPLETE_ATTRIBUTION',

            !$sourceDecisionRecorded
                => 'PENDING_EXECUTION_AUTHORIZATION_DECISION',

            !$sourceMadeByAuthorizedHuman
                => 'PENDING_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION',

            !$sourceAttributionComplete
                => 'PENDING_EXECUTION_AUTHORIZATION_ATTRIBUTION',

            !$sourceAuthorizationCompleted
                => 'PENDING_COMPLETED_EXECUTION_AUTHORIZATION',

            $blockingConditions > 0
                => 'BLOCKED_BY_EXECUTION_CONDITIONS',

            default
                => 'READY_FOR_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW',
        };

        $executionReadiness = match ($executionState) {
            'CONTROLLED_ACTIVATION_EXECUTED_WITH_COMPLETE_ATTRIBUTION'
                => 'EXECUTION_COMPLETED',

            'CONTROLLED_ACTIVATION_EXECUTED_WITH_INCOMPLETE_ATTRIBUTION'
                => 'POST_EXECUTION_ATTRIBUTION_REQUIRES_GOVERNANCE_REVIEW',

            'READY_FOR_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW'
                => 'READY_FOR_AUTHORIZED_HUMAN_EXECUTION_REVIEW',

            'BLOCKED_BY_EXECUTION_CONDITIONS'
                => 'BLOCKED_BY_CONTROLLED_ACTIVATION_EXECUTION_CONDITIONS',

            default
                => 'AWAITING_COMPLETED_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION',
        };

        $executionReadinessScore = match (true) {
            $controlledActivationExecuted && $executionAttributionComplete => 100.0,
            $controlledActivationExecuted => 70.0,
            $sourceAuthorizationValid && $blockingConditions === 0 => 60.0,
            $sourceAuthorizationValid => 40.0,
            $sourceDecisionRecorded && $sourceMadeByAuthorizedHuman => 25.0,
            default => 0.0,
        };

        $executionRiskScore = match (true) {
            $controlledActivationExecuted && !$executionAttributionComplete => 100.0,
            !$sourceAuthorizationValid => 100.0,
            $blockingConditions > 0 => 90.0,
            !$executionAttributionComplete => 75.0,
            default => 50.0,
        };

        $executionRiskLevel = match (true) {
            $executionRiskScore >= 90
                => 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            $executionRiskScore >= 70
                => 'HIGH_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            $executionRiskScore >= 40
                => 'MODERATE_CONTROLLED_ACTIVATION_EXECUTION_RISK',

            default
                => 'CONTROLLED_EXECUTION_RISK',
        };

        $executionProgressionBlocked =
            !$sourceAuthorizationValid
            || $blockingConditions > 0
            || $controlledActivationExecuted;

        $humanManagementAttentionLevel = match (true) {
            $executionRiskScore >= 90 => 'CRITICAL',
            $executionRiskScore >= 70 => 'HIGH',
            $executionRiskScore >= 40 => 'MODERATE',
            default => 'ADVISORY',
        };

        $executionRestrictions = collect(
            $execution->execution_restrictions ?? []
        );

        $materialRestrictions = $executionRestrictions->count();

        $combinedResolutionScore = round(
            ($conditionResolutionScore * 0.6)
            + ($evidenceResolutionScore * 0.4),
            2
        );

        $conditionPressureScore = match (true) {
            $blockingConditions >= 4 => 100.0,
            $blockingConditions === 3 => 75.0,
            $blockingConditions === 2 => 50.0,
            $blockingConditions === 1 => 25.0,
            default => 0.0,
        };

        $restrictionPressureScore = match (true) {
            $materialRestrictions >= 6 => 100.0,
            $materialRestrictions >= 4 => 75.0,
            $materialRestrictions >= 2 => 50.0,
            $materialRestrictions === 1 => 25.0,
            default => 0.0,
        };

        $combinedPressureScore = round(
            ($conditionPressureScore + $restrictionPressureScore) / 2,
            2
        );

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'strategic_plan_controlled_activation_execution_authorization_id'
                => $authorization->id,

            'execution_state' => [
                'controlled_activation_execution_state'
                    => $executionState,

                'controlled_activation_execution_status'
                    => $execution->controlled_activation_execution_status,

                'controlled_activation_execution_mode'
                    => $execution->controlled_activation_execution_mode,

                'execution_readiness'
                    => $executionReadiness,

                'execution_readiness_score'
                    => $executionReadinessScore,

                'execution_risk_level'
                    => $executionRiskLevel,

                'execution_risk_score'
                    => $executionRiskScore,

                'execution_progression_blocked'
                    => $executionProgressionBlocked,

                'human_management_attention_level'
                    => $humanManagementAttentionLevel,

                'human_management_attention_required'
                    => $humanManagementAttentionLevel !== 'ADVISORY',

                'immediate_human_intervention_required'
                    => $humanManagementAttentionLevel === 'CRITICAL',

                'controlled_activation_executed'
                    => $controlledActivationExecuted,

                'execution_attribution_complete'
                    => $executionAttributionComplete,

                'post_execution_status'
                    => $execution->post_execution_status,

                'post_execution_validation_completed'
                    => (bool) $execution->post_execution_validation_completed,
            ],

            'condition_summary' => [
                'total_conditions'
                    => $totalConditions,

                'satisfied_conditions'
                    => $satisfiedConditions,

                'blocking_execution_conditions'
                    => $blockingConditions,

                'condition_resolution_score'
                    => $conditionResolutionScore,

                'condition_pressure_score'
                    => $conditionPressureScore,
            ],

            'restriction_summary' => [
                'material_restrictions'
                    => $materialRestrictions,

                'restriction_pressure_score'
                    => $restrictionPressureScore,
            ],

            'evidence_summary' => [
                'total_evidence_items'
                    => $evidence->count(),

                'validated_evidence_items'
                    => $validatedEvidence,

                'evidence_resolution_score'
                    => $evidenceResolutionScore,
            ],

            'resolution_pressure_summary' => [
                'combined_resolution_score'
                    => $combinedResolutionScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,
            ],

            'source_execution_authorization_context' => [
                'source_execution_authorization_decision'
                    => $authorization->controlled_activation_execution_authorization_decision,

                'source_execution_authorization_decision_recorded'
                    => $sourceDecisionRecorded,

                'source_execution_authorization_made_by_authorized_human'
                    => $sourceMadeByAuthorizedHuman,

                'source_execution_authorization_attribution_complete'
                    => $sourceAttributionComplete,

                'source_execution_authorization_completed'
                    => $sourceAuthorizationCompleted,

                'source_execution_authorization_valid'
                    => $sourceAuthorizationValid,

                'source_execution_authorized_by'
                    => $authorization->execution_authorized_by,

                'source_execution_authorizer_role'
                    => $authorization->execution_authorizer_role,

                'source_execution_authorized_at'
                    => $authorization->execution_authorized_at,
            ],

            'execution_attribution_context' => [
                'executed_by'
                    => $execution->executed_by,

                'executor_role'
                    => $execution->executor_role,

                'executed_at'
                    => $execution->executed_at,

                'execution_made_by_authorized_human'
                    => (bool) $execution->execution_made_by_authorized_human,

                'execution_attribution_complete'
                    => $executionAttributionComplete,

                'controlled_activation_executed'
                    => $controlledActivationExecuted,
            ],

            'state_findings' => [
                "Controlled activation execution state intelligence is based on Step 71 execution record {$execution->id}.",
                "Current controlled activation execution state is {$executionState}.",
                "Current execution readiness is {$executionReadiness} with score {$executionReadinessScore}.",
                "Current execution risk is {$executionRiskLevel} with score {$executionRiskScore}.",
                "Source execution authorization decision recorded is ".($sourceDecisionRecorded ? 'YES' : 'NO').'.',
                "Source execution authorization made by authorized human is ".($sourceMadeByAuthorizedHuman ? 'YES' : 'NO').'.',
                "Source execution authorization attribution complete is ".($sourceAttributionComplete ? 'YES' : 'NO').'.',
                "Source execution authorization completed is ".($sourceAuthorizationCompleted ? 'YES' : 'NO').'.',
                "{$blockingConditions} condition(s) currently block controlled activation execution.",
                "{$materialRestrictions} controlled activation execution restriction(s) remain represented.",
                "Controlled activation actually executed is ".($controlledActivationExecuted ? 'YES' : 'NO').'.',
                "Execution attribution complete is ".($executionAttributionComplete ? 'YES' : 'NO').'.',
                "Step 71.3 remains informational and does not execute controlled activation.",
            ],

            'management_priorities' => $this->managementPriorities(
                $sourceDecisionRecorded,
                $sourceMadeByAuthorizedHuman,
                $sourceAttributionComplete,
                $sourceAuthorizationCompleted,
                $blockingConditions,
                $controlledActivationExecuted,
                $executionAttributionComplete,
                $executionRiskLevel,
                $executionRiskScore
            ),

            'controlled_activation_execution_state_guardrails'
                => $this->guardrails(),
        ];
    }

    private function managementPriorities(
        bool $sourceDecisionRecorded,
        bool $sourceMadeByAuthorizedHuman,
        bool $sourceAttributionComplete,
        bool $sourceAuthorizationCompleted,
        int $blockingConditions,
        bool $controlledActivationExecuted,
        bool $executionAttributionComplete,
        string $executionRiskLevel,
        float $executionRiskScore
    ): array {
        $priorities = [];

        if (!$sourceDecisionRecorded) {
            $priorities[] =
                'Record the required controlled activation execution authorization decision through explicitly authorized human governance.';
        }

        if (!$sourceMadeByAuthorizedHuman) {
            $priorities[] =
                'Ensure controlled activation execution authorization is made by an explicitly authorized human governance authority.';
        }

        if (!$sourceAttributionComplete) {
            $priorities[] =
                'Complete execution authorization attribution before controlled activation execution progression.';
        }

        if (!$sourceAuthorizationCompleted) {
            $priorities[] =
                'Complete controlled activation execution authorization before actual execution consideration.';
        }

        if ($blockingConditions > 0) {
            $priorities[] =
                "Resolve or formally govern {$blockingConditions} execution-blocking condition(s) before controlled activation execution.";
        }

        if ($executionRiskScore >= 90) {
            $priorities[] =
                "Maintain critical authorized-human governance oversight while execution risk remains {$executionRiskLevel} with score {$executionRiskScore}.";
        }

        if (!$controlledActivationExecuted) {
            $priorities[] =
                'Keep controlled activation execution pending until all explicit human governance requirements are satisfied.';
        }

        if ($controlledActivationExecuted && !$executionAttributionComplete) {
            $priorities[] =
                'Immediately complete post-execution human executor attribution and governance validation.';
        }

        $priorities[] =
            'Preserve strict separation between execution authorization and actual controlled activation execution.';

        $priorities[] =
            'Do not interpret state, readiness, risk, resolution, pressure, or management classifications as authority to execute controlled activation.';

        return $priorities;
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_state_intelligence_enabled'
                => true,

            'execution_state_intelligence_is_execution'
                => false,

            'execution_state_intelligence_is_execution_authorization'
                => false,

            'execution_state_intelligence_makes_execution_decision'
                => false,

            'execution_state_intelligence_records_execution_decision'
                => false,

            'execution_state_intelligence_executes_controlled_activation'
                => false,

            'execution_state_intelligence_activates_strategic_plan'
                => false,

            'execution_state_intelligence_changes_source_execution_authorization'
                => false,

            'execution_state_intelligence_changes_execution_status'
                => false,

            'execution_state_intelligence_changes_execution_outcome'
                => false,

            'execution_state_intelligence_resolves_conditions'
                => false,

            'execution_state_intelligence_removes_restrictions'
                => false,

            'execution_state_intelligence_validates_evidence'
                => false,

            'execution_readiness_score_authorizes_execution'
                => false,

            'execution_risk_score_authorizes_execution'
                => false,

            'condition_resolution_score_authorizes_execution'
                => false,

            'evidence_resolution_score_authorizes_execution'
                => false,

            'combined_resolution_score_authorizes_execution'
                => false,

            'condition_pressure_score_authorizes_execution'
                => false,

            'restriction_pressure_score_authorizes_execution'
                => false,

            'execution_state_intelligence_authorizes_ai_change'
                => false,

            'execution_state_intelligence_authorizes_deployment'
                => false,

            'execution_state_intelligence_authorizes_rollback'
                => false,

            'execution_state_intelligence_authorizes_clinical_action'
                => false,

            'automatic_execution_allowed'
                => false,

            'automatic_execution_authorization_allowed'
                => false,

            'automatic_activation_allowed'
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

            'execution_authorization_separate_from_execution'
                => true,

            'actual_execution_authority_reserved_for_authorized_human'
                => true,

            'post_execution_human_validation_required'
                => true,

            'message' =>
                'Step 71.3 controlled activation execution state intelligence evaluates current execution state, source execution authorization dependencies, conditions, restrictions, evidence, readiness, risk, resolution progress, pressure, execution attribution, post-execution state, and human management attention requirements. State intelligence is informational only and cannot make an execution decision, authorize execution, execute controlled activation, activate the strategic plan, resolve conditions, remove restrictions, validate evidence, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action. Actual controlled activation execution remains separately reserved for explicitly authorized human governance.',
        ];
    }
}