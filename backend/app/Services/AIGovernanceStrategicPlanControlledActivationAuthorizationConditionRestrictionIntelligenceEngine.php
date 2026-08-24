<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Authorization Record
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
                    'No strategic plan controlled activation authorization record is available for condition and restriction intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
        );

        $state =
            $stateEngine->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Controlled activation authorization condition and restriction intelligence could not continue because Step 69.3 state intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source State
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $conditionContext =
            $state['condition_context'] ?? [];

        $restrictionContext =
            $state['restriction_context'] ?? [];

        $evidenceContext =
            $state['evidence_context'] ?? [];

        $resolutionContext =
            $state['resolution_context'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Conditions
        |--------------------------------------------------------------------------
        */

        $conditions =
            is_array($state['condition_analysis'] ?? null)
                ? $state['condition_analysis']
                : [];

        $openConditions = [];
        $resolvedConditions = [];
        $blockingAuthorizationConditions = [];
        $blockingExecutionConditions = [];
        $constrainingConditions = [];
        $criticalConditions = [];
        $highConditions = [];
        $moderateConditions = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $priority =
                strtoupper(
                    (string) ($condition['priority_level'] ?? 'MODERATE')
                );

            $isOpen =
                (bool) ($condition['condition_open'] ?? false);

            $isResolved =
                (bool) ($condition['condition_resolved'] ?? false);

            $blocksAuthorization =
                (bool) (
                    $condition['blocks_activation_authorization']
                    ?? false
                );

            $blocksExecution =
                (bool) (
                    $condition['blocks_activation_execution']
                    ?? false
                );

            $constrains =
                (bool) (
                    $condition['constrains_activation_authorization']
                    ?? false
                );

            if ($isOpen) {
                $openConditions[] =
                    $condition;
            }

            if ($isResolved) {
                $resolvedConditions[] =
                    $condition;
            }

            if ($isOpen && $blocksAuthorization) {
                $blockingAuthorizationConditions[] =
                    $condition;
            }

            if ($isOpen && $blocksExecution) {
                $blockingExecutionConditions[] =
                    $condition;
            }

            if ($isOpen && $constrains) {
                $constrainingConditions[] =
                    $condition;
            }

            if ($isOpen && $priority === 'CRITICAL') {
                $criticalConditions[] =
                    $condition;
            }

            if ($isOpen && $priority === 'HIGH') {
                $highConditions[] =
                    $condition;
            }

            if ($isOpen && $priority === 'MODERATE') {
                $moderateConditions[] =
                    $condition;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Restrictions
        |--------------------------------------------------------------------------
        */

        $restrictions =
            is_array($state['restriction_analysis'] ?? null)
                ? $state['restriction_analysis']
                : [];

        $materialRestrictions = [];
        $criticalRestrictions = [];
        $highRestrictions = [];
        $moderateRestrictions = [];
        $authorizationRestrictions = [];
        $executionRestrictions = [];
        $confirmationRestrictions = [];
        $riskRestrictions = [];

        foreach ($restrictions as $restriction) {
            if (!is_array($restriction)) {
                continue;
            }

            $severity =
                strtoupper(
                    (string) ($restriction['severity'] ?? 'MODERATE')
                );

            $type =
                strtoupper(
                    (string) ($restriction['restriction_type'] ?? 'UNKNOWN')
                );

            $material =
                (bool) ($restriction['material_restriction'] ?? false);

            $critical =
                (bool) ($restriction['critical_restriction'] ?? false);

            if ($material) {
                $materialRestrictions[] =
                    $restriction;
            }

            if ($critical) {
                $criticalRestrictions[] =
                    $restriction;
            }

            if ($severity === 'HIGH') {
                $highRestrictions[] =
                    $restriction;
            }

            if ($severity === 'MODERATE') {
                $moderateRestrictions[] =
                    $restriction;
            }

            if (
                in_array(
                    $type,
                    [
                        'AUTHORIZATION',
                        'HUMAN_ACTIVATION_AUTHORIZATION',
                    ],
                    true
                )
            ) {
                $authorizationRestrictions[] =
                    $restriction;
            }

            if (
                in_array(
                    $type,
                    [
                        'ACTIVATION_CONDITION',
                        'ACTIVATION_RESTRICTION',
                        'HUMAN_ACTIVATION_AUTHORIZATION',
                    ],
                    true
                )
            ) {
                $executionRestrictions[] =
                    $restriction;
            }

            if (
                in_array(
                    $type,
                    [
                        'GOVERNANCE_CONFIRMATION',
                        'CONFIRMATION_COMPLETION',
                    ],
                    true
                )
            ) {
                $confirmationRestrictions[] =
                    $restriction;
            }

            if ($type === 'RISK') {
                $riskRestrictions[] =
                    $restriction;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Condition
        |--------------------------------------------------------------------------
        */

        $dominantCondition =
            $this->dominantCondition($openConditions);

        /*
        |--------------------------------------------------------------------------
        | Dominant Restriction
        |--------------------------------------------------------------------------
        */

        $dominantRestriction =
            $this->dominantRestriction($restrictions);

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            $this->score(
                $resolutionContext['condition_resolution_score']
                ?? $authorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $resolutionContext['evidence_resolution_score']
                ?? $authorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $resolutionContext['combined_resolution_score']
                ?? $authorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $resolutionContext['condition_pressure_score']
                ?? $authorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $resolutionContext['restriction_pressure_score']
                ?? $authorization->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $resolutionContext['combined_condition_restriction_pressure_score']
                ?? $authorization->combined_condition_restriction_pressure_score
            );

        $activationReadinessScore =
            $this->score(
                $activationState['activation_readiness_score']
                ?? $authorization->activation_readiness_score
            );

        $activationRiskScore =
            $this->score(
                $activationState['activation_risk_score']
                ?? $authorization->activation_risk_score
            );

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        if (
            count($criticalRestrictions) > 0
            || count($criticalConditions) > 0
        ) {
            $stateClassification =
                'CRITICAL_CONTROLLED_ACTIVATION_AUTHORIZATION_RESTRICTIONS';
        } elseif (
            count($blockingAuthorizationConditions) > 0
            || count($blockingExecutionConditions) > 0
            || count($materialRestrictions) > 0
        ) {
            $stateClassification =
                'MATERIAL_BLOCKING_CONTROLLED_ACTIVATION_AUTHORIZATION_REQUIREMENTS';
        } elseif (
            count($constrainingConditions) > 0
            || count($moderateRestrictions) > 0
        ) {
            $stateClassification =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_CONSTRAINED';
        } elseif (
            count($openConditions) === 0
            && count($materialRestrictions) === 0
        ) {
            $stateClassification =
                'NO_MATERIAL_CONTROLLED_ACTIVATION_AUTHORIZATION_RESTRICTIONS';
        } else {
            $stateClassification =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_REQUIREMENTS_PRESENT';
        }

        /*
        |--------------------------------------------------------------------------
        | Human Governance Requirement
        |--------------------------------------------------------------------------
        */

        $humanGovernanceResolutionRequired =
            count($openConditions) > 0
            || count($materialRestrictions) > 0
            || !($sourceConfirmationContext['source_confirmation_decision_recorded'] ?? false)
            || !($sourceConfirmationContext['source_confirmation_made_by_authorized_human'] ?? false)
            || !($sourceConfirmationContext['source_governance_decision_confirmation_completed'] ?? false)
            || !($authorizationContext['controlled_activation_authorization_completed'] ?? false);

        $unrestrictedAuthorizationProgressionAllowed =
            !$humanGovernanceResolutionRequired
            && count($blockingAuthorizationConditions) === 0
            && count($criticalRestrictions) === 0;

        $unrestrictedExecutionProgressionAllowed =
            $unrestrictedAuthorizationProgressionAllowed
            && count($blockingExecutionConditions) === 0
            && (
                $executionContext['controlled_activation_execution_authorized']
                ?? false
            ) === true;

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            count($criticalConditions) > 0
            || count($criticalRestrictions) > 0
            || (
                $activationState['immediate_human_intervention_required']
                ?? false
            ) === true;

        $humanManagementAttentionRequired =
            $immediateHumanInterventionRequired
            || count($blockingAuthorizationConditions) > 0
            || count($blockingExecutionConditions) > 0
            || count($materialRestrictions) > 0
            || $activationRiskScore >= 75
            || $humanGovernanceResolutionRequired;

        if ($immediateHumanInterventionRequired) {
            $humanManagementAttentionLevel =
                'CRITICAL';
        } elseif (
            count($blockingAuthorizationConditions) > 0
            || count($blockingExecutionConditions) > 0
            || count($materialRestrictions) > 0
            || $activationRiskScore >= 75
        ) {
            $humanManagementAttentionLevel =
                'HIGH';
        } elseif ($humanManagementAttentionRequired) {
            $humanManagementAttentionLevel =
                'MODERATE';
        } else {
            $humanManagementAttentionLevel =
                'CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Main Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionState = [
            'state' =>
                $stateClassification,

            'total_conditions' =>
                count($conditions),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'blocking_activation_authorization_conditions' =>
                count($blockingAuthorizationConditions),

            'blocking_activation_execution_conditions' =>
                count($blockingExecutionConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'critical_open_conditions' =>
                count($criticalConditions),

            'total_restrictions' =>
                count($restrictions),

            'material_restrictions' =>
                count($materialRestrictions),

            'critical_restrictions' =>
                count($criticalRestrictions),

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

            'activation_readiness_score' =>
                $activationReadinessScore,

            'activation_risk_score' =>
                $activationRiskScore,

            'human_governance_resolution_required' =>
                $humanGovernanceResolutionRequired,

            'unrestricted_activation_authorization_progression_allowed' =>
                $unrestrictedAuthorizationProgressionAllowed,

            'unrestricted_activation_execution_progression_allowed' =>
                $unrestrictedExecutionProgressionAllowed,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition Summary
        |--------------------------------------------------------------------------
        */

        $conditionSummary = [
            'total_conditions' =>
                count($conditions),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'blocking_activation_authorization_conditions' =>
                count($blockingAuthorizationConditions),

            'blocking_activation_execution_conditions' =>
                count($blockingExecutionConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'critical_open_conditions' =>
                count($criticalConditions),

            'high_open_conditions' =>
                count($highConditions),

            'moderate_open_conditions' =>
                count($moderateConditions),

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
                count($restrictions),

            'material_restrictions' =>
                count($materialRestrictions),

            'critical_restrictions' =>
                count($criticalRestrictions),

            'high_restrictions' =>
                count($highRestrictions),

            'moderate_restrictions' =>
                count($moderateRestrictions),

            'authorization_restrictions' =>
                count($authorizationRestrictions),

            'execution_restrictions' =>
                count($executionRestrictions),

            'confirmation_restrictions' =>
                count($confirmationRestrictions),

            'risk_restrictions' =>
                count($riskRestrictions),

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Barrier Context
        |--------------------------------------------------------------------------
        */

        $authorizationBarrierContext = [
            'blocking_authorization_condition_count' =>
                count($blockingAuthorizationConditions),

            'authorization_restriction_count' =>
                count($authorizationRestrictions),

            'authorization_decision_recorded' =>
                (bool) (
                    $activationState['authorization_decision_recorded']
                    ?? false
                ),

            'activation_authorization_made_by_authorized_human' =>
                (bool) (
                    $activationState['activation_authorization_made_by_authorized_human']
                    ?? false
                ),

            'authorization_attribution_complete' =>
                (bool) (
                    $activationState['authorization_attribution_complete']
                    ?? false
                ),

            'controlled_activation_authorization_completed' =>
                (bool) (
                    $activationState['controlled_activation_authorization_completed']
                    ?? false
                ),

            'authorization_progression_blocked' =>
                count($blockingAuthorizationConditions) > 0
                || count($authorizationRestrictions) > 0,

            'conditions' =>
                $blockingAuthorizationConditions,

            'restrictions' =>
                $authorizationRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Barrier Context
        |--------------------------------------------------------------------------
        */

        $executionBarrierContext = [
            'blocking_execution_condition_count' =>
                count($blockingExecutionConditions),

            'execution_restriction_count' =>
                count($executionRestrictions),

            'controlled_activation_execution_status' =>
                $executionContext['controlled_activation_execution_status']
                ?? null,

            'controlled_activation_execution_authorized' =>
                (bool) (
                    $executionContext['controlled_activation_execution_authorized']
                    ?? false
                ),

            'execution_authorization_attribution_complete' =>
                (bool) (
                    $executionContext['execution_authorization_attribution_complete']
                    ?? false
                ),

            'execution_progression_blocked' =>
                count($blockingExecutionConditions) > 0
                || count($executionRestrictions) > 0
                || !(
                    $executionContext['controlled_activation_execution_authorized']
                    ?? false
                ),

            'conditions' =>
                $blockingExecutionConditions,

            'restrictions' =>
                $executionRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Barrier Context
        |--------------------------------------------------------------------------
        */

        $confirmationBarrierContext = [
            'confirmation_restriction_count' =>
                count($confirmationRestrictions),

            'source_confirmation_decision' =>
                $sourceConfirmationContext['source_confirmation_decision']
                ?? null,

            'source_confirmation_decision_recorded' =>
                (bool) (
                    $sourceConfirmationContext['source_confirmation_decision_recorded']
                    ?? false
                ),

            'source_confirmation_made_by_authorized_human' =>
                (bool) (
                    $sourceConfirmationContext['source_confirmation_made_by_authorized_human']
                    ?? false
                ),

            'source_governance_decision_confirmation_completed' =>
                (bool) (
                    $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                    ?? false
                ),

            'source_controlled_activation_authorized' =>
                (bool) (
                    $sourceConfirmationContext['source_controlled_activation_authorized']
                    ?? false
                ),

            'confirmation_progression_blocked' =>
                !(
                    $sourceConfirmationContext['source_confirmation_decision_recorded']
                    ?? false
                )
                || !(
                    $sourceConfirmationContext['source_confirmation_made_by_authorized_human']
                    ?? false
                )
                || !(
                    $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                    ?? false
                ),

            'restrictions' =>
                $confirmationRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $riskContext = [
            'risk_restriction_count' =>
                count($riskRestrictions),

            'activation_risk_level' =>
                $activationState['activation_risk_level']
                ?? $authorization->activation_risk_level,

            'activation_risk_score' =>
                $activationRiskScore,

            'critical_risk_restriction_present' =>
                count($criticalRestrictions) > 0,

            'risk_requires_authorized_human_governance' =>
                $activationRiskScore >= 75
                || count($riskRestrictions) > 0,

            'restrictions' =>
                $riskRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Controlled activation authorization condition and restriction intelligence is based on authorization record {$authorization->id}.",

            count($conditions)
                .' controlled activation authorization condition(s) are represented.',

            count($openConditions)
                .' controlled activation authorization condition(s) remain open.',

            count($blockingAuthorizationConditions)
                .' condition(s) currently block controlled activation authorization.',

            count($blockingExecutionConditions)
                .' condition(s) currently block controlled activation execution.',

            count($constrainingConditions)
                .' condition(s) currently constrain controlled activation progression.',

            count($criticalConditions)
                .' critical activation condition(s) remain open.',

            count($restrictions)
                .' controlled activation authorization restriction(s) are represented.',

            count($materialRestrictions)
                .' material controlled activation restriction(s) remain active.',

            count($criticalRestrictions)
                .' critical controlled activation restriction(s) remain active.',

            count($authorizationRestrictions)
                .' authorization-related restriction(s) remain represented.',

            count($executionRestrictions)
                .' execution-related restriction(s) remain represented.',

            count($confirmationRestrictions)
                .' source confirmation restriction(s) remain represented.',

            count($riskRestrictions)
                .' activation risk restriction(s) remain represented.',

            'Current dominant controlled activation condition is '
                .($dominantCondition['condition_code'] ?? 'NONE').'.',

            'Current dominant controlled activation restriction is '
                .($dominantRestriction['restriction_code'] ?? 'NONE').'.',

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
                .$combinedPressureScore.'.',

            'Current activation readiness score is '
                .$activationReadinessScore.'.',

            'Current activation risk score is '
                .$activationRiskScore.'.',

            'Current condition/restriction state is '
                .$stateClassification.'.',

            'Unrestricted activation authorization progression allowed is '
                .($unrestrictedAuthorizationProgressionAllowed ? 'YES' : 'NO').'.',

            'Unrestricted activation execution progression allowed is '
                .($unrestrictedExecutionProgressionAllowed ? 'YES' : 'NO').'.',

            'Controlled activation condition and restriction intelligence remains advisory and does not resolve conditions, remove restrictions, authorize activation, or authorize execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantCondition) {
            $managementPriorities[] =
                'Address the dominant controlled activation condition '
                .$dominantCondition['condition_code']
                .' through explicitly authorized human governance.';
        }

        if ($dominantRestriction) {
            $managementPriorities[] =
                'Address the dominant controlled activation restriction '
                .$dominantRestriction['restriction_code']
                .' through the established authorized-human governance process.';
        }

        if (count($blockingAuthorizationConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingAuthorizationConditions)
                .' activation condition(s) before controlled activation authorization progression.';
        }

        if (count($blockingExecutionConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingExecutionConditions)
                .' activation condition(s) before activation execution progression.';
        }

        if (count($materialRestrictions) > 0) {
            $managementPriorities[] =
                'Maintain explicit authorized-human governance treatment for '
                .count($materialRestrictions)
                .' material controlled activation restriction(s).';
        }

        if (count($criticalRestrictions) > 0) {
            $managementPriorities[] =
                'Escalate '
                .count($criticalRestrictions)
                .' critical controlled activation restriction(s) for immediate authorized-human governance review.';
        }

        if (
            !(
                $sourceConfirmationContext['source_confirmation_decision_recorded']
                ?? false
            )
        ) {
            $managementPriorities[] =
                'Record the required authorized-human governance confirmation decision before controlled activation authorization progression.';
        }

        if (
            !(
                $sourceConfirmationContext['source_confirmation_made_by_authorized_human']
                ?? false
            )
        ) {
            $managementPriorities[] =
                'Ensure source governance confirmation is explicitly attributable to an authorized human governance confirmer.';
        }

        if (
            !(
                $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                ?? false
            )
        ) {
            $managementPriorities[] =
                'Complete source governance confirmation through authorized human governance before controlled activation authorization.';
        }

        if ($activationRiskScore >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while controlled activation risk remains high or critical.';
        }

        if (
            !(
                $authorizationContext['controlled_activation_authorization_completed']
                ?? false
            )
        ) {
            $managementPriorities[] =
                'An explicitly authorized human controlled activation authorization decision remains required.';
        }

        if (
            !(
                $executionContext['controlled_activation_execution_authorized']
                ?? false
            )
        ) {
            $managementPriorities[] =
                'Keep activation execution prohibited until separately authorized by an explicitly authorized human governance authority.';
        }

        $managementPriorities[] =
            'Keep condition and restriction intelligence separate from activation authorization authority and activation execution authority.';

        $managementPriorities[] =
            'Do not interpret condition classification, restriction severity, pressure scores, readiness scores, or risk scores as authorization to activate or execute.';

        $managementPriorities[] =
            'Preserve governance confirmation, authorization attribution, execution attribution, evidence traceability, safety controls, and authority separation throughout controlled activation progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_CONDITION_RESTRICTION_INTELLIGENCE_AVAILABLE',

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

            'condition_restriction_state' =>
                $conditionRestrictionState,

            'condition_summary' =>
                $conditionSummary,

            'restriction_summary' =>
                $restrictionSummary,

            'dominant_activation_condition' =>
                $dominantCondition,

            'dominant_activation_restriction' =>
                $dominantRestriction,

            'blocking_activation_authorization_conditions' =>
                $blockingAuthorizationConditions,

            'blocking_activation_execution_conditions' =>
                $blockingExecutionConditions,

            'constraining_activation_conditions' =>
                $constrainingConditions,

            'resolved_activation_conditions' =>
                $resolvedConditions,

            'critical_activation_conditions' =>
                $criticalConditions,

            'material_activation_restrictions' =>
                $materialRestrictions,

            'critical_activation_restrictions' =>
                $criticalRestrictions,

            'authorization_barrier_context' =>
                $authorizationBarrierContext,

            'execution_barrier_context' =>
                $executionBarrierContext,

            'confirmation_barrier_context' =>
                $confirmationBarrierContext,

            'risk_context' =>
                $riskContext,

            'condition_restriction_analysis' => [
                'conditions' =>
                    $conditions,

                'restrictions' =>
                    $restrictions,
            ],

            'evidence_context' =>
                $evidenceContext,

            'activation_authorization_state_context' =>
                $activationState,

            'authorization_context' =>
                $authorizationContext,

            'execution_context' =>
                $executionContext,

            'source_confirmation_context' =>
                $sourceConfirmationContext,

            'review_context' =>
                $state['review_context'] ?? [],

            'confirmation_context' =>
                $state['confirmation_context'] ?? [],

            'governance_context' =>
                $state['governance_context'] ?? [],

            'source_context' =>
                $state['source_context'] ?? [],

            'condition_restriction_findings' =>
                $findings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'activation_authorization_condition_restriction_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dominant Condition
    |--------------------------------------------------------------------------
    */

    private function dominantCondition(array $conditions): ?array
    {
        if (empty($conditions)) {
            return null;
        }

        usort(
            $conditions,
            function (array $a, array $b): int {
                $aWeight =
                    (float) ($a['severity_weight'] ?? 0);

                $bWeight =
                    (float) ($b['severity_weight'] ?? 0);

                if ($aWeight === $bWeight) {
                    $aAuthorization =
                        ($a['blocks_activation_authorization'] ?? false)
                            ? 1
                            : 0;

                    $bAuthorization =
                        ($b['blocks_activation_authorization'] ?? false)
                            ? 1
                            : 0;

                    if ($aAuthorization !== $bAuthorization) {
                        return $bAuthorization <=> $aAuthorization;
                    }

                    $aExecution =
                        ($a['blocks_activation_execution'] ?? false)
                            ? 1
                            : 0;

                    $bExecution =
                        ($b['blocks_activation_execution'] ?? false)
                            ? 1
                            : 0;

                    return $bExecution <=> $aExecution;
                }

                return $bWeight <=> $aWeight;
            }
        );

        return $conditions[0] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Dominant Restriction
    |--------------------------------------------------------------------------
    */

    private function dominantRestriction(array $restrictions): ?array
    {
        if (empty($restrictions)) {
            return null;
        }

        usort(
            $restrictions,
            function (array $a, array $b): int {
                $aWeight =
                    (float) ($a['severity_weight'] ?? 0);

                $bWeight =
                    (float) ($b['severity_weight'] ?? 0);

                return $bWeight <=> $aWeight;
            }
        );

        return $restrictions[0] ?? null;
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
    | Step 69.4 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_condition_restriction_intelligence_enabled' =>
                true,

            'condition_restriction_intelligence_is_activation_authorization' =>
                false,

            'condition_restriction_intelligence_is_activation_execution_authorization' =>
                false,

            'condition_restriction_intelligence_makes_activation_authorization_decision' =>
                false,

            'condition_restriction_intelligence_records_activation_authorization_decision' =>
                false,

            'condition_restriction_intelligence_completes_activation_authorization' =>
                false,

            'condition_restriction_intelligence_authorizes_activation_execution' =>
                false,

            'condition_restriction_intelligence_activates_strategic_plan' =>
                false,

            'condition_restriction_intelligence_changes_source_confirmation' =>
                false,

            'condition_restriction_intelligence_changes_activation_authorization_status' =>
                false,

            'condition_restriction_intelligence_changes_activation_authorization_decision' =>
                false,

            'condition_restriction_intelligence_changes_activation_outcome' =>
                false,

            'condition_restriction_intelligence_changes_execution_status' =>
                false,

            'condition_restriction_intelligence_changes_plan_status' =>
                false,

            'condition_restriction_intelligence_changes_action_state' =>
                false,

            'condition_restriction_intelligence_changes_priority' =>
                false,

            'condition_restriction_intelligence_changes_eligibility' =>
                false,

            'condition_restriction_intelligence_resolves_conditions' =>
                false,

            'condition_restriction_intelligence_waives_conditions' =>
                false,

            'condition_restriction_intelligence_removes_restrictions' =>
                false,

            'condition_restriction_intelligence_downgrades_restrictions' =>
                false,

            'condition_restriction_intelligence_resolves_dependencies' =>
                false,

            'condition_restriction_intelligence_validates_evidence' =>
                false,

            'condition_score_authorizes_activation' =>
                false,

            'restriction_score_authorizes_activation' =>
                false,

            'condition_pressure_score_authorizes_activation' =>
                false,

            'restriction_pressure_score_authorizes_activation' =>
                false,

            'activation_readiness_score_authorizes_activation' =>
                false,

            'activation_risk_score_authorizes_activation' =>
                false,

            'condition_resolution_score_authorizes_activation' =>
                false,

            'evidence_resolution_score_authorizes_activation' =>
                false,

            'combined_resolution_score_authorizes_activation' =>
                false,

            'condition_restriction_intelligence_authorizes_ai_change' =>
                false,

            'condition_restriction_intelligence_authorizes_execution' =>
                false,

            'condition_restriction_intelligence_authorizes_deployment' =>
                false,

            'condition_restriction_intelligence_authorizes_rollback' =>
                false,

            'condition_restriction_intelligence_authorizes_clinical_action' =>
                false,

            'condition_restriction_intelligence_overrides_human_review' =>
                false,

            'condition_restriction_intelligence_overrides_governance_confirmation' =>
                false,

            'condition_restriction_intelligence_overrides_activation_authorization' =>
                false,

            'condition_restriction_intelligence_overrides_execution_authorization' =>
                false,

            'condition_restriction_intelligence_overrides_evidence_requirements' =>
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

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'activation_execution_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'message' =>
                'Step 69.4 controlled activation authorization condition and restriction intelligence evaluates activation-authorization conditions, activation-execution blockers, governance-confirmation barriers, material restrictions, critical restrictions, risk restrictions, condition pressure, restriction pressure, readiness, risk, and human-management attention requirements for explicitly authorized human governance. It does not resolve or waive conditions, remove or downgrade restrictions, make or record a controlled activation authorization decision, authorize activation execution, activate the strategic plan, alter source governance confirmation, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Controlled activation authorization authority and activation execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}