<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecution;

class AIGovernanceStrategicPlanControlledActivationExecutionConditionRestrictionIntelligenceEngine
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

        $stateEngine = app(
            AIGovernanceStrategicPlanControlledActivationExecutionStateIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($execution->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_EXECUTION_STATE_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Step 71.3 controlled activation execution state intelligence is unavailable.',
                'source_state_result' => $state,
            ];
        }

        $conditions = collect($execution->execution_conditions ?? []);
        $restrictions = collect($execution->execution_restrictions ?? []);

        /*
         * Step 71.2 initially stores compact condition structures.
         * Step 71.4 normalizes them into richer intelligence without
         * changing the persisted conditions or restrictions.
         */
        $normalizedConditions = $conditions->map(function ($condition) {
            $code = $condition['condition_code']
                ?? $condition['code']
                ?? 'UNSPECIFIED_EXECUTION_CONDITION';

            $satisfied = (bool) ($condition['satisfied'] ?? false);

            $blocking = (bool) (
                $condition['blocks_execution']
                ?? $condition['blocking_execution']
                ?? false
            );

            $priority = $condition['priority_level']
                ?? $this->conditionPriority($code, $blocking);

            $status = $condition['condition_status']
                ?? ($satisfied ? 'RESOLVED' : 'OPEN');

            return [
                'condition_code' => $code,
                'condition_type' => $condition['condition_type']
                    ?? $this->conditionType($code),
                'priority_level' => $priority,
                'condition_status' => $status,
                'satisfied' => $satisfied,
                'blocks_controlled_activation_execution' => $blocking,
                'requires_authorized_human_resolution' => true,
                'automatic_resolution_allowed' => false,
            ];
        })->values();

        $normalizedRestrictions = $restrictions->map(function ($restriction) {
            if (is_string($restriction)) {
                $code = $restriction;

                return [
                    'restriction_code' => $code,
                    'restriction_type' => $this->restrictionType($code),
                    'severity' => $this->restrictionSeverity($code),
                    'requires_authorized_human_governance' => true,
                    'automatic_restriction_removal_allowed' => false,
                ];
            }

            $code = $restriction['restriction_code']
                ?? $restriction['code']
                ?? 'UNSPECIFIED_EXECUTION_RESTRICTION';

            return [
                'restriction_code' => $code,
                'restriction_type' => $restriction['restriction_type']
                    ?? $this->restrictionType($code),
                'severity' => $restriction['severity']
                    ?? $this->restrictionSeverity($code),
                'requires_authorized_human_governance' => true,
                'automatic_restriction_removal_allowed' => false,
            ];
        })->values();

        $totalConditions = $normalizedConditions->count();

        $openConditions = $normalizedConditions->where(
            'condition_status',
            'OPEN'
        )->count();

        $resolvedConditions = $normalizedConditions->where(
            'condition_status',
            'RESOLVED'
        )->count();

        $blockingExecutionConditions = $normalizedConditions->filter(
            fn ($condition) =>
                ($condition['condition_status'] ?? null) === 'OPEN'
                && (bool) ($condition['blocks_controlled_activation_execution'] ?? false)
        )->count();

        $criticalOpenConditions = $normalizedConditions->filter(
            fn ($condition) =>
                ($condition['condition_status'] ?? null) === 'OPEN'
                && ($condition['priority_level'] ?? null) === 'CRITICAL'
        )->count();

        $highOpenConditions = $normalizedConditions->filter(
            fn ($condition) =>
                ($condition['condition_status'] ?? null) === 'OPEN'
                && ($condition['priority_level'] ?? null) === 'HIGH'
        )->count();

        $totalRestrictions = $normalizedRestrictions->count();

        $materialRestrictions = $normalizedRestrictions->filter(
            fn ($restriction) =>
                in_array(
                    $restriction['severity'] ?? null,
                    ['CRITICAL', 'HIGH'],
                    true
                )
        )->count();

        $criticalRestrictions = $normalizedRestrictions->where(
            'severity',
            'CRITICAL'
        )->count();

        $highRestrictions = $normalizedRestrictions->where(
            'severity',
            'HIGH'
        )->count();

        $conditionResolutionScore = $totalConditions > 0
            ? round(($resolvedConditions / $totalConditions) * 100, 2)
            : 0.0;

        $evidenceResolutionScore = (float) (
            $state['evidence_summary']['evidence_resolution_score'] ?? 0
        );

        $combinedResolutionScore = round(
            ($conditionResolutionScore * 0.60)
            + ($evidenceResolutionScore * 0.40),
            2
        );

        $conditionPressureScore = $this->pressureScore(
            $criticalOpenConditions,
            $highOpenConditions,
            $blockingExecutionConditions
        );

        $restrictionPressureScore = $this->restrictionPressureScore(
            $criticalRestrictions,
            $highRestrictions,
            $materialRestrictions
        );

        $combinedPressureScore = round(
            ($conditionPressureScore + $restrictionPressureScore) / 2,
            2
        );

        $restrictionState = match (true) {
            $criticalOpenConditions > 0 || $criticalRestrictions > 0
                => 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',

            $blockingExecutionConditions > 0 || $materialRestrictions > 0
                => 'MATERIAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',

            $openConditions > 0 || $totalRestrictions > 0
                => 'CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS_REMAIN',

            default
                => 'NO_MATERIAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS',
        };

        $humanGovernanceResolutionRequired =
            $blockingExecutionConditions > 0
            || $materialRestrictions > 0;

        $unrestrictedExecutionProgressionAllowed =
            $blockingExecutionConditions === 0
            && $materialRestrictions === 0
            && !($state['execution_state']['execution_progression_blocked'] ?? true);

        $humanManagementAttentionLevel = match (true) {
            $criticalOpenConditions > 0 || $criticalRestrictions > 0
                => 'CRITICAL',

            $blockingExecutionConditions > 0 || $materialRestrictions > 0
                => 'HIGH',

            $openConditions > 0 || $totalRestrictions > 0
                => 'MODERATE',

            default
                => 'ADVISORY',
        };

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_CONDITION_RESTRICTION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_execution_id'
                => $execution->id,

            'controlled_activation_execution_code'
                => $execution->controlled_activation_execution_code,

            'condition_restriction_state' => [
                'state' => $restrictionState,

                'total_conditions' => $totalConditions,
                'open_conditions' => $openConditions,
                'resolved_conditions' => $resolvedConditions,

                'blocking_execution_conditions'
                    => $blockingExecutionConditions,

                'critical_open_conditions'
                    => $criticalOpenConditions,

                'high_open_conditions'
                    => $highOpenConditions,

                'total_restrictions'
                    => $totalRestrictions,

                'material_restrictions'
                    => $materialRestrictions,

                'critical_restrictions'
                    => $criticalRestrictions,

                'high_restrictions'
                    => $highRestrictions,

                'condition_resolution_score'
                    => $conditionResolutionScore,

                'evidence_resolution_score'
                    => $evidenceResolutionScore,

                'combined_resolution_score'
                    => $combinedResolutionScore,

                'condition_pressure_score'
                    => $conditionPressureScore,

                'restriction_pressure_score'
                    => $restrictionPressureScore,

                'combined_condition_restriction_pressure_score'
                    => $combinedPressureScore,

                'human_governance_resolution_required'
                    => $humanGovernanceResolutionRequired,

                'unrestricted_execution_progression_allowed'
                    => $unrestrictedExecutionProgressionAllowed,

                'human_management_attention_level'
                    => $humanManagementAttentionLevel,

                'human_management_attention_required'
                    => $humanManagementAttentionLevel !== 'ADVISORY',

                'immediate_human_intervention_required'
                    => $humanManagementAttentionLevel === 'CRITICAL',
            ],

            'conditions' => $normalizedConditions->all(),

            'restrictions' => $normalizedRestrictions->all(),

            'execution_state_context'
                => $state['execution_state'],

            'source_execution_authorization_context'
                => $state['source_execution_authorization_context'],

            'execution_attribution_context'
                => $state['execution_attribution_context'],

            'condition_restriction_findings' => [
                "Controlled activation execution condition and restriction intelligence is based on Step 71 execution record {$execution->id}.",
                "Current condition/restriction state is {$restrictionState}.",
                "{$totalConditions} execution condition(s) are represented.",
                "{$openConditions} execution condition(s) remain open.",
                "{$resolvedConditions} execution condition(s) are resolved.",
                "{$blockingExecutionConditions} condition(s) currently block controlled activation execution.",
                "{$criticalOpenConditions} critical execution condition(s) remain open.",
                "{$highOpenConditions} high-priority execution condition(s) remain open.",
                "{$totalRestrictions} execution restriction(s) are represented.",
                "{$materialRestrictions} material execution restriction(s) remain.",
                "{$criticalRestrictions} critical execution restriction(s) remain.",
                "{$highRestrictions} high-severity execution restriction(s) remain.",
                "Current condition resolution score is {$conditionResolutionScore}.",
                "Current evidence resolution score is {$evidenceResolutionScore}.",
                "Current combined resolution score is {$combinedResolutionScore}.",
                "Current condition pressure score is {$conditionPressureScore}.",
                "Current restriction pressure score is {$restrictionPressureScore}.",
                "Current combined condition/restriction pressure score is {$combinedPressureScore}.",
                'Condition and restriction intelligence does not resolve conditions or remove restrictions.',
                'Step 71.4 does not authorize or perform controlled activation execution.',
            ],

            'management_priorities' => $this->managementPriorities(
                $criticalOpenConditions,
                $highOpenConditions,
                $blockingExecutionConditions,
                $criticalRestrictions,
                $materialRestrictions,
                $conditionResolutionScore,
                $combinedPressureScore
            ),

            'controlled_activation_execution_condition_restriction_guardrails'
                => $this->guardrails(),
        ];
    }

    private function conditionPriority(string $code, bool $blocking): string
    {
        if (
            str_contains($code, 'VALID_EXECUTION_AUTHORIZATION')
            || str_contains($code, 'AUTHORIZED_HUMAN_EXECUTION_AUTHORIZER')
            || str_contains($code, 'EXECUTION_AUTHORIZATION_ATTRIBUTION')
        ) {
            return 'CRITICAL';
        }

        if (
            str_contains($code, 'SEPARATE_AUTHORIZED_HUMAN_EXECUTION')
            || $blocking
        ) {
            return 'HIGH';
        }

        return 'MODERATE';
    }

    private function conditionType(string $code): string
    {
        return match (true) {
            str_contains($code, 'AUTHORIZATION_ATTRIBUTION')
                => 'EXECUTION_AUTHORIZATION_ATTRIBUTION',

            str_contains($code, 'AUTHORIZATION')
                => 'EXECUTION_AUTHORIZATION',

            str_contains($code, 'AUTHORIZED_HUMAN_EXECUTION')
                => 'HUMAN_EXECUTION_AUTHORITY',

            default
                => 'CONTROLLED_ACTIVATION_EXECUTION_CONDITION',
        };
    }

    private function restrictionSeverity(string $code): string
    {
        return match (true) {
            str_contains($code, 'NO_AUTOMATIC_CONTROLLED_ACTIVATION_EXECUTION')
                => 'CRITICAL',

            str_contains($code, 'NO_AUTONOMOUS_STRATEGIC_PLAN_ACTIVATION')
                => 'CRITICAL',

            str_contains($code, 'AUTHORIZED_HUMAN_EXECUTION_REQUIRED')
                => 'CRITICAL',

            str_contains($code, 'NO_AUTOMATIC_AI_CHANGE')
                => 'HIGH',

            str_contains($code, 'NO_AUTOMATIC_DEPLOYMENT')
                => 'HIGH',

            str_contains($code, 'NO_AUTOMATIC_ROLLBACK')
                => 'HIGH',

            str_contains($code, 'NO_AUTOMATIC_CLINICAL_ACTION')
                => 'HIGH',

            default
                => 'MODERATE',
        };
    }

    private function restrictionType(string $code): string
    {
        return match (true) {
            str_contains($code, 'EXECUTION')
                => 'EXECUTION_AUTHORITY',

            str_contains($code, 'STRATEGIC_PLAN_ACTIVATION')
                => 'ACTIVATION_AUTHORITY',

            str_contains($code, 'AI_CHANGE')
                => 'AI_CHANGE_CONTROL',

            str_contains($code, 'DEPLOYMENT')
                => 'DEPLOYMENT_CONTROL',

            str_contains($code, 'ROLLBACK')
                => 'ROLLBACK_CONTROL',

            str_contains($code, 'CLINICAL_ACTION')
                => 'CLINICAL_ACTION_CONTROL',

            default
                => 'GOVERNANCE_RESTRICTION',
        };
    }

    private function pressureScore(
        int $critical,
        int $high,
        int $blocking
    ): float {
        if ($critical >= 3 || $blocking >= 4) {
            return 100.0;
        }

        if ($critical > 0 || $blocking >= 3) {
            return 91.67;
        }

        if ($high >= 2 || $blocking >= 2) {
            return 75.0;
        }

        if ($high > 0 || $blocking === 1) {
            return 50.0;
        }

        return 0.0;
    }

    private function restrictionPressureScore(
        int $critical,
        int $high,
        int $material
    ): float {
        if ($critical >= 3 || $material >= 6) {
            return 100.0;
        }

        if ($critical > 0 || $material >= 4) {
            return 91.67;
        }

        if ($high >= 2 || $material >= 2) {
            return 75.0;
        }

        if ($high > 0 || $material === 1) {
            return 50.0;
        }

        return 0.0;
    }

    private function managementPriorities(
        int $criticalOpenConditions,
        int $highOpenConditions,
        int $blockingConditions,
        int $criticalRestrictions,
        int $materialRestrictions,
        float $conditionResolutionScore,
        float $combinedPressureScore
    ): array {
        $priorities = [];

        if ($criticalOpenConditions > 0) {
            $priorities[] =
                "Escalate {$criticalOpenConditions} critical controlled activation execution condition(s) for immediate authorized-human governance review.";
        }

        if ($criticalRestrictions > 0) {
            $priorities[] =
                "Maintain immediate authorized-human governance treatment for {$criticalRestrictions} critical controlled activation execution restriction(s).";
        }

        if ($blockingConditions > 0) {
            $priorities[] =
                "Resolve or formally govern {$blockingConditions} condition(s) currently blocking controlled activation execution.";
        }

        if ($highOpenConditions > 0) {
            $priorities[] =
                "Maintain explicit authorized-human governance review for {$highOpenConditions} high-priority execution condition(s).";
        }

        if ($materialRestrictions > 0) {
            $priorities[] =
                "Maintain explicit governance treatment for {$materialRestrictions} material controlled activation execution restriction(s).";
        }

        if ($conditionResolutionScore < 100) {
            $priorities[] =
                "Keep controlled activation execution blocked while condition resolution remains {$conditionResolutionScore}%.";
        }

        if ($combinedPressureScore >= 90) {
            $priorities[] =
                "Maintain critical human governance oversight while combined execution condition/restriction pressure remains {$combinedPressureScore}.";
        }

        $priorities[] =
            'Do not automatically resolve conditions, waive requirements, remove restrictions, or downgrade execution controls.';

        $priorities[] =
            'Preserve authorized-human execution authority and strict separation between execution intelligence and actual execution.';

        return $priorities;
    }

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_condition_restriction_intelligence_enabled'
                => true,

            'condition_restriction_intelligence_is_execution'
                => false,

            'condition_restriction_intelligence_is_execution_authorization'
                => false,

            'condition_restriction_intelligence_makes_execution_decision'
                => false,

            'condition_restriction_intelligence_executes_controlled_activation'
                => false,

            'condition_restriction_intelligence_resolves_conditions'
                => false,

            'condition_restriction_intelligence_waives_conditions'
                => false,

            'condition_restriction_intelligence_removes_restrictions'
                => false,

            'condition_restriction_intelligence_downgrades_restrictions'
                => false,

            'condition_restriction_intelligence_validates_evidence'
                => false,

            'condition_classification_authorizes_execution'
                => false,

            'restriction_classification_authorizes_execution'
                => false,

            'condition_resolution_score_authorizes_execution'
                => false,

            'condition_pressure_score_authorizes_execution'
                => false,

            'restriction_pressure_score_authorizes_execution'
                => false,

            'combined_pressure_score_authorizes_execution'
                => false,

            'condition_restriction_intelligence_authorizes_ai_change'
                => false,

            'condition_restriction_intelligence_authorizes_deployment'
                => false,

            'condition_restriction_intelligence_authorizes_rollback'
                => false,

            'condition_restriction_intelligence_authorizes_clinical_action'
                => false,

            'automatic_execution_allowed'
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

            'human_governance_resolution_required'
                => true,

            'message' =>
                'Step 71.4 controlled activation execution condition and restriction intelligence classifies execution conditions, blockers, restrictions, severity, resolution state, pressure, and management attention requirements for explicitly authorized human governance. It does not resolve or waive conditions, remove or downgrade restrictions, validate evidence, authorize execution, execute controlled activation, activate the strategic plan, modify AI behavior, deploy changes, trigger rollback, or initiate clinical action. Actual controlled activation execution authority remains reserved for explicitly authorized human governance.',
        ];
    }
}