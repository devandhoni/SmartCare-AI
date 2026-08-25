<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationExecutionAuthorization;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorizationConditionRestrictionIntelligenceEngine
{
    /**
     * Analyze Step 70 execution-authorization conditions and restrictions.
     *
     * This service is advisory only.
     *
     * It does not:
     * - resolve conditions;
     * - waive conditions;
     * - remove restrictions;
     * - downgrade restrictions;
     * - validate evidence;
     * - make or record execution authorization;
     * - complete execution authorization;
     * - execute controlled activation;
     * - activate the strategic plan.
     */
    public function analyze(?int $executionAuthorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 70 Execution Authorization Record
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
                    'No strategic plan controlled activation execution authorization record is available for condition and restriction intelligence analysis.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Arrays
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
        | Normalize Conditions
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $status =
                strtoupper(
                    (string) (
                        $condition['condition_status']
                        ?? 'OPEN'
                    )
                );

            $priority =
                strtoupper(
                    (string) (
                        $condition['priority_level']
                        ?? 'MODERATE'
                    )
                );

            $type =
                strtoupper(
                    (string) (
                        $condition['condition_type']
                        ?? 'GENERAL'
                    )
                );

            $conditionOpen =
                !in_array(
                    $status,
                    [
                        'RESOLVED',
                        'CLOSED',
                        'SATISFIED',
                        'COMPLETED',
                    ],
                    true
                );

            $conditionResolved =
                !$conditionOpen;

            $blocksExecutionAuthorization =
                (bool) (
                    $condition['blocks_execution_authorization']
                    ?? false
                );

            $blocksActualExecution =
                (bool) (
                    $condition['blocks_actual_execution']
                    ?? false
                );

            $severityWeight =
                $this->priorityWeight($priority);

            $classification =
                $this->conditionClassification(
                    $conditionOpen,
                    $blocksExecutionAuthorization,
                    $blocksActualExecution,
                    $priority
                );

            $conditionAnalysis[] =
                array_merge(
                    $condition,
                    [
                        'condition_open' =>
                            $conditionOpen,

                        'condition_resolved' =>
                            $conditionResolved,

                        'condition_classification' =>
                            $classification,

                        'severity_weight' =>
                            $severityWeight,

                        'blocks_execution_authorization' =>
                            $blocksExecutionAuthorization,

                        'blocks_actual_execution' =>
                            $blocksActualExecution,

                        'condition_type_normalized' =>
                            $type,
                    ]
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Restrictions
        |--------------------------------------------------------------------------
        */

        $restrictionAnalysis = [];

        foreach ($restrictions as $restriction) {
            if (!is_array($restriction)) {
                continue;
            }

            $severity =
                strtoupper(
                    (string) (
                        $restriction['severity']
                        ?? 'MODERATE'
                    )
                );

            $severityWeight =
                $this->restrictionWeight($severity);

            $materialRestriction =
                in_array(
                    $severity,
                    [
                        'HIGH',
                        'CRITICAL',
                    ],
                    true
                );

            $criticalRestriction =
                $severity === 'CRITICAL';

            $restrictionType =
                strtoupper(
                    (string) (
                        $restriction['restriction_type']
                        ?? 'GENERAL'
                    )
                );

            $restrictionAnalysis[] =
                array_merge(
                    $restriction,
                    [
                        'severity_weight' =>
                            $severityWeight,

                        'material_restriction' =>
                            $materialRestriction,

                        'critical_restriction' =>
                            $criticalRestriction,

                        'requires_authorized_human_governance' =>
                            (bool) (
                                $restriction['requires_authorized_human_governance']
                                ?? true
                            ),

                        'automatic_restriction_removal_allowed' =>
                            false,

                        'restriction_type_normalized' =>
                            $restrictionType,
                    ]
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Condition Classification Collections
        |--------------------------------------------------------------------------
        */

        $openConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                )
            );

        $resolvedConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_resolved'] ?? false) === true
                )
            );

        $blockingExecutionAuthorizationConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                        && ($condition['blocks_execution_authorization'] ?? false) === true
                )
            );

        $blockingActualExecutionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                        && ($condition['blocks_actual_execution'] ?? false) === true
                )
            );

        $constrainingConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                        && ($condition['blocks_execution_authorization'] ?? false) === false
                        && ($condition['blocks_actual_execution'] ?? false) === false
                )
            );

        $criticalConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                        && strtoupper(
                            (string) (
                                $condition['priority_level']
                                ?? ''
                            )
                        ) === 'CRITICAL'
                )
            );

        $highConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                        && strtoupper(
                            (string) (
                                $condition['priority_level']
                                ?? ''
                            )
                        ) === 'HIGH'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Restriction Collections
        |--------------------------------------------------------------------------
        */

        $materialRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        ($restriction['material_restriction'] ?? false) === true
                )
            );

        $criticalRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        ($restriction['critical_restriction'] ?? false) === true
                )
            );

        $highRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        strtoupper(
                            (string) (
                                $restriction['severity']
                                ?? ''
                            )
                        ) === 'HIGH'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Dominant Condition / Restriction
        |--------------------------------------------------------------------------
        */

        $dominantDecisionCondition =
            $this->dominantCondition(
                $conditionAnalysis
            );

        $dominantDecisionRestriction =
            $this->dominantRestriction(
                $restrictionAnalysis
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Condition Pressure
        |--------------------------------------------------------------------------
        */

        $conditionPressureScore =
            $this->calculateConditionPressureScore(
                $conditionAnalysis
            );

        /*
        |--------------------------------------------------------------------------
        | Restriction Pressure
        |--------------------------------------------------------------------------
        */

        $restrictionPressureScore =
            $this->calculateRestrictionPressureScore(
                $restrictionAnalysis
            );

        $combinedConditionRestrictionPressureScore =
            $this->score(
                (
                    $conditionPressureScore
                    +
                    $restrictionPressureScore
                ) / 2
            );

        /*
        |--------------------------------------------------------------------------
        | Current Authorization / Execution State
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

        $controlledActivationExecuted =
            (bool) $authorization->controlled_activation_executed;

        $actualExecutionAttributionComplete =
            $this->actualExecutionAttributionComplete(
                $authorization
            );

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Governance State
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionState =
            $this->determineConditionRestrictionState(
                count($criticalConditions),
                count($blockingExecutionAuthorizationConditions),
                count($blockingActualExecutionConditions),
                count($criticalRestrictions),
                count($materialRestrictions)
            );

        /*
        |--------------------------------------------------------------------------
        | Progression
        |--------------------------------------------------------------------------
        */

        $humanGovernanceResolutionRequired =
            count($openConditions) > 0
            || count($materialRestrictions) > 0;

        $unrestrictedExecutionAuthorizationProgressionAllowed =
            count($blockingExecutionAuthorizationConditions) === 0
            && count($criticalRestrictions) === 0
            && $sourceActivationAuthorizationDecisionRecorded
            && $sourceActivationAuthorizationMadeByAuthorizedHuman
            && $sourceActivationAuthorizationAttributionComplete
            && $sourceControlledActivationAuthorizationCompleted;

        $unrestrictedActualExecutionProgressionAllowed =
            count($blockingActualExecutionConditions) === 0
            && count($criticalRestrictions) === 0
            && $executionAuthorizationDecisionRecorded
            && $executionAuthorizationMadeByAuthorizedHuman
            && $executionAuthorizationAttributionComplete
            && $executionAuthorizationCompleted;

        /*
        |--------------------------------------------------------------------------
        | Human Management Attention
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionLevel =
            $this->managementAttentionLevel(
                count($criticalConditions),
                count($criticalRestrictions),
                count($blockingExecutionAuthorizationConditions),
                count($blockingActualExecutionConditions),
                $authorization->execution_risk_score
            );

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'NORMAL';

        $immediateHumanInterventionRequired =
            $humanManagementAttentionLevel === 'CRITICAL';

        /*
        |--------------------------------------------------------------------------
        | Evidence Summary
        |--------------------------------------------------------------------------
        */

        $outstandingEvidenceItems =
            count(
                array_filter(
                    $validatedEvidence,
                    fn (array $evidence): bool =>
                        ($evidence['evidence_outstanding'] ?? false) === true
                )
            );

        $blockingExecutionEvidenceItems =
            count(
                array_filter(
                    $validatedEvidence,
                    fn (array $evidence): bool =>
                        (
                            ($evidence['blocks_execution_authorization'] ?? false) === true
                            || ($evidence['blocks_actual_execution'] ?? false) === true
                        )
                        && ($evidence['evidence_outstanding'] ?? false) === true
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Authorization Conditions
        |--------------------------------------------------------------------------
        */

        $sourceAuthorizationConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $condition['condition_type_normalized']
                                    ?? ''
                                )
                            ),
                            [
                                'ACTIVATION_AUTHORIZATION',
                                'AUTHORIZATION',
                                'AUTHORIZATION_ATTRIBUTION',
                                'ACTIVATION_AUTHORIZATION_COMPLETION',
                            ],
                            true
                        )
                )
            );

        $sourceAuthorizationRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $restriction['restriction_type_normalized']
                                    ?? ''
                                )
                            ),
                            [
                                'ACTIVATION_AUTHORIZATION',
                                'AUTHORIZATION',
                                'AUTHORIZATION_ATTRIBUTION',
                                'ACTIVATION_AUTHORIZATION_COMPLETION',
                            ],
                            true
                        )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Execution Condition Context
        |--------------------------------------------------------------------------
        */

        $executionConditionContext =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $condition['condition_type_normalized']
                                    ?? ''
                                )
                            ),
                            [
                                'EXECUTION_CONDITION',
                                'EXECUTION_RESTRICTION',
                                'CRITICAL_RESTRICTION',
                                'RISK',
                                'HUMAN_EXECUTION_AUTHORIZATION',
                            ],
                            true
                        )
                )
            );

        $executionRestrictionContext =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $restriction['restriction_type_normalized']
                                    ?? ''
                                )
                            ),
                            [
                                'EXECUTION_CONDITION',
                                'EXECUTION_RESTRICTION',
                                'CRITICAL_RESTRICTION',
                                'RISK',
                                'EXECUTION_AUTHORITY',
                            ],
                            true
                        )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $riskConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition): bool =>
                        strtoupper(
                            (string) (
                                $condition['condition_type_normalized']
                                ?? ''
                            )
                        ) === 'RISK'
                )
            );

        $riskRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction): bool =>
                        strtoupper(
                            (string) (
                                $restriction['restriction_type_normalized']
                                ?? ''
                            )
                        ) === 'RISK'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $conditionSummary = [
            'total_conditions' =>
                count($conditionAnalysis),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'blocking_execution_authorization_conditions' =>
                count($blockingExecutionAuthorizationConditions),

            'blocking_actual_execution_conditions' =>
                count($blockingActualExecutionConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'critical_open_conditions' =>
                count($criticalConditions),

            'high_open_conditions' =>
                count($highConditions),

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'condition_pressure_score' =>
                $conditionPressureScore,
        ];

        $restrictionSummary = [
            'total_restrictions' =>
                count($restrictionAnalysis),

            'material_restrictions' =>
                count($materialRestrictions),

            'critical_restrictions' =>
                count($criticalRestrictions),

            'high_restrictions' =>
                count($highRestrictions),

            'restriction_pressure_score' =>
                $restrictionPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Main Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionStateContext = [
            'state' =>
                $conditionRestrictionState,

            'total_conditions' =>
                count($conditionAnalysis),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'blocking_execution_authorization_conditions' =>
                count($blockingExecutionAuthorizationConditions),

            'blocking_actual_execution_conditions' =>
                count($blockingActualExecutionConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'critical_open_conditions' =>
                count($criticalConditions),

            'total_restrictions' =>
                count($restrictionAnalysis),

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
                $combinedConditionRestrictionPressureScore,

            'human_governance_resolution_required' =>
                $humanGovernanceResolutionRequired,

            'unrestricted_execution_authorization_progression_allowed' =>
                $unrestrictedExecutionAuthorizationProgressionAllowed,

            'unrestricted_actual_execution_progression_allowed' =>
                $unrestrictedActualExecutionProgressionAllowed,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'source_activation_authorization_decision_recorded' =>
                $sourceActivationAuthorizationDecisionRecorded,

            'source_activation_authorization_made_by_authorized_human' =>
                $sourceActivationAuthorizationMadeByAuthorizedHuman,

            'source_activation_authorization_attribution_complete' =>
                $sourceActivationAuthorizationAttributionComplete,

            'source_controlled_activation_authorization_completed' =>
                $sourceControlledActivationAuthorizationCompleted,

            'execution_authorization_decision_recorded' =>
                $executionAuthorizationDecisionRecorded,

            'execution_authorization_made_by_authorized_human' =>
                $executionAuthorizationMadeByAuthorizedHuman,

            'execution_authorization_attribution_complete' =>
                $executionAuthorizationAttributionComplete,

            'controlled_activation_execution_authorization_completed' =>
                $executionAuthorizationCompleted,

            'controlled_activation_executed' =>
                $controlledActivationExecuted,

            'actual_execution_attribution_complete' =>
                $actualExecutionAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionFindings = [
            "Controlled activation execution authorization condition and restriction intelligence is based on execution authorization record {$authorization->id}.",

            count($conditionAnalysis)
                .' execution authorization condition(s) are represented.',

            count($openConditions)
                .' execution authorization condition(s) remain open.',

            count($resolvedConditions)
                .' execution authorization condition(s) are currently resolved.',

            count($blockingExecutionAuthorizationConditions)
                .' condition(s) currently block execution authorization progression.',

            count($blockingActualExecutionConditions)
                .' condition(s) currently block actual controlled activation execution.',

            count($criticalConditions)
                .' critical execution authorization condition(s) remain open.',

            count($highConditions)
                .' high-priority execution authorization condition(s) remain open.',

            count($restrictionAnalysis)
                .' execution authorization restriction(s) are represented.',

            count($materialRestrictions)
                .' material execution authorization restriction(s) remain active.',

            count($criticalRestrictions)
                .' critical execution authorization restriction(s) remain active.',

            count($highRestrictions)
                .' high execution authorization restriction(s) remain active.',

            'Current dominant execution authorization condition is '
                .(
                    $dominantDecisionCondition['condition_code']
                    ?? 'NONE'
                ).'.',

            'Current dominant execution authorization restriction is '
                .(
                    $dominantDecisionRestriction['restriction_code']
                    ?? 'NONE'
                ).'.',

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined resolution score is {$combinedResolutionScore}.",

            "Current condition pressure score is {$conditionPressureScore}.",

            "Current restriction pressure score is {$restrictionPressureScore}.",

            "Current combined condition/restriction pressure score is {$combinedConditionRestrictionPressureScore}.",

            'Current execution authorization condition/restriction state is '
                .$conditionRestrictionState.'.',

            'Source controlled activation authorization decision recorded is '
                .($sourceActivationAuthorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source controlled activation authorization made by authorized human is '
                .($sourceActivationAuthorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source activation authorization attribution complete is '
                .($sourceActivationAuthorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Source controlled activation authorization completed is '
                .($sourceControlledActivationAuthorizationCompleted ? 'YES' : 'NO').'.',

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

            "{$outstandingEvidenceItems} execution evidence item(s) remain outstanding.",

            "{$blockingExecutionEvidenceItems} execution evidence item(s) currently block progression.",

            'Step 70.4 condition and restriction intelligence remains advisory and does not resolve conditions, remove restrictions, authorize execution, or execute controlled activation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantDecisionCondition) {
            $managementPriorities[] =
                'Address the dominant execution authorization condition '
                .$dominantDecisionCondition['condition_code']
                .' through explicitly authorized human governance.';
        }

        if ($dominantDecisionRestriction) {
            $managementPriorities[] =
                'Address the dominant execution authorization restriction '
                .$dominantDecisionRestriction['restriction_code']
                .' through the established human governance process.';
        }

        if (count($criticalConditions) > 0) {
            $managementPriorities[] =
                'Escalate '
                .count($criticalConditions)
                .' critical execution authorization condition(s) for immediate authorized-human governance review.';
        }

        if (count($criticalRestrictions) > 0) {
            $managementPriorities[] =
                'Maintain immediate authorized-human governance treatment for '
                .count($criticalRestrictions)
                .' critical execution authorization restriction(s).';
        }

        if (count($blockingExecutionAuthorizationConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingExecutionAuthorizationConditions)
                .' condition(s) blocking execution authorization progression.';
        }

        if (count($blockingActualExecutionConditions) > 0) {
            $managementPriorities[] =
                'Keep actual controlled activation execution prohibited while '
                .count($blockingActualExecutionConditions)
                .' execution-blocking condition(s) remain.';
        }

        if (!$sourceActivationAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'Record the required source controlled activation authorization decision through explicitly authorized human governance.';
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
                'Complete the source controlled activation authorization process before execution authorization completion.';
        }

        if (!$executionAuthorizationDecisionRecorded) {
            $managementPriorities[] =
                'A separate explicitly authorized human execution authorization decision remains required.';
        }

        if (!$executionAuthorizationCompleted) {
            $managementPriorities[] =
                'Keep execution authorization incomplete until all authorized-human governance requirements are satisfied.';
        }

        if (!$controlledActivationExecuted) {
            $managementPriorities[] =
                'Preserve strict separation between execution authorization and actual controlled activation execution.';
        }

        $managementPriorities[] =
            'Do not interpret condition classification, restriction severity, pressure scores, resolution scores, readiness, risk, or evidence state as authority to authorize or execute controlled activation.';

        $managementPriorities[] =
            'Preserve human governance authority, source traceability, authorization attribution, execution attribution, evidence integrity, safety controls, and authority separation throughout execution progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_CONDITION_RESTRICTION_INTELLIGENCE_AVAILABLE',

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

            'condition_restriction_state' =>
                $conditionRestrictionStateContext,

            'condition_summary' =>
                $conditionSummary,

            'restriction_summary' =>
                $restrictionSummary,

            'dominant_execution_condition' =>
                $dominantDecisionCondition,

            'dominant_execution_restriction' =>
                $dominantDecisionRestriction,

            'blocking_execution_authorization_conditions' =>
                $blockingExecutionAuthorizationConditions,

            'blocking_actual_execution_conditions' =>
                $blockingActualExecutionConditions,

            'constraining_execution_conditions' =>
                $constrainingConditions,

            'resolved_execution_conditions' =>
                $resolvedConditions,

            'critical_execution_conditions' =>
                $criticalConditions,

            'material_execution_restrictions' =>
                $materialRestrictions,

            'critical_execution_restrictions' =>
                $criticalRestrictions,

            'source_activation_authorization_condition_context' => [
                'condition_count' =>
                    count($sourceAuthorizationConditions),

                'restriction_count' =>
                    count($sourceAuthorizationRestrictions),

                'source_activation_authorization_decision_recorded' =>
                    $sourceActivationAuthorizationDecisionRecorded,

                'source_activation_authorization_made_by_authorized_human' =>
                    $sourceActivationAuthorizationMadeByAuthorizedHuman,

                'source_activation_authorization_attribution_complete' =>
                    $sourceActivationAuthorizationAttributionComplete,

                'source_controlled_activation_authorization_completed' =>
                    $sourceControlledActivationAuthorizationCompleted,

                'progression_blocked' =>
                    !$sourceActivationAuthorizationDecisionRecorded
                    || !$sourceActivationAuthorizationMadeByAuthorizedHuman
                    || !$sourceActivationAuthorizationAttributionComplete
                    || !$sourceControlledActivationAuthorizationCompleted,

                'conditions' =>
                    $sourceAuthorizationConditions,

                'restrictions' =>
                    $sourceAuthorizationRestrictions,
            ],

            'execution_condition_context' => [
                'condition_count' =>
                    count($executionConditionContext),

                'restriction_count' =>
                    count($executionRestrictionContext),

                'execution_authorization_decision_recorded' =>
                    $executionAuthorizationDecisionRecorded,

                'execution_authorization_made_by_authorized_human' =>
                    $executionAuthorizationMadeByAuthorizedHuman,

                'execution_authorization_attribution_complete' =>
                    $executionAuthorizationAttributionComplete,

                'execution_authorization_completed' =>
                    $executionAuthorizationCompleted,

                'actual_execution_completed' =>
                    $controlledActivationExecuted,

                'conditions' =>
                    $executionConditionContext,

                'restrictions' =>
                    $executionRestrictionContext,
            ],

            'risk_condition_context' => [
                'risk_condition_count' =>
                    count($riskConditions),

                'risk_restriction_count' =>
                    count($riskRestrictions),

                'execution_risk_level' =>
                    $authorization->execution_risk_level,

                'execution_risk_score' =>
                    $this->score(
                        $authorization->execution_risk_score
                    ),

                'risk_requires_authorized_human_governance' =>
                    count($riskConditions) > 0
                    || count($riskRestrictions) > 0,

                'conditions' =>
                    $riskConditions,

                'restrictions' =>
                    $riskRestrictions,
            ],

            'condition_restriction_analysis' => [
                'conditions' =>
                    $conditionAnalysis,

                'restrictions' =>
                    $restrictionAnalysis,
            ],

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

            'condition_restriction_findings' =>
                $conditionRestrictionFindings,

            'management_priorities' =>
                array_values(
                    array_unique(
                        $managementPriorities
                    )
                ),

            'execution_authorization_condition_restriction_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Condition Classification
    |--------------------------------------------------------------------------
    */

    private function conditionClassification(
        bool $open,
        bool $blocksExecutionAuthorization,
        bool $blocksActualExecution,
        string $priority
    ): string {
        if (!$open) {
            return 'RESOLVED';
        }

        if (
            $priority === 'CRITICAL'
            && (
                $blocksExecutionAuthorization
                || $blocksActualExecution
            )
        ) {
            return 'CRITICAL_BLOCKING';
        }

        if ($blocksExecutionAuthorization) {
            return 'BLOCKING_EXECUTION_AUTHORIZATION';
        }

        if ($blocksActualExecution) {
            return 'BLOCKING_ACTUAL_EXECUTION';
        }

        return 'CONSTRAINING';
    }

    /*
    |--------------------------------------------------------------------------
    | Condition Priority Weight
    |--------------------------------------------------------------------------
    */

    private function priorityWeight(string $priority): float
    {
        return match ($priority) {
            'CRITICAL' =>
                100.0,

            'HIGH' =>
                75.0,

            'MODERATE' =>
                50.0,

            'LOW' =>
                25.0,

            default =>
                25.0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Restriction Weight
    |--------------------------------------------------------------------------
    */

    private function restrictionWeight(string $severity): float
    {
        return match ($severity) {
            'CRITICAL' =>
                100.0,

            'HIGH' =>
                75.0,

            'MODERATE' =>
                50.0,

            'LOW' =>
                25.0,

            default =>
                25.0,
        };
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
                $aResolved =
                    ($a['condition_resolved'] ?? false) === true;

                $bResolved =
                    ($b['condition_resolved'] ?? false) === true;

                if ($aResolved !== $bResolved) {
                    return $aResolved ? 1 : -1;
                }

                $aAuthorizationBlock =
                    ($a['blocks_execution_authorization'] ?? false) === true;

                $bAuthorizationBlock =
                    ($b['blocks_execution_authorization'] ?? false) === true;

                if ($aAuthorizationBlock !== $bAuthorizationBlock) {
                    return $aAuthorizationBlock ? -1 : 1;
                }

                $aActualExecutionBlock =
                    ($a['blocks_actual_execution'] ?? false) === true;

                $bActualExecutionBlock =
                    ($b['blocks_actual_execution'] ?? false) === true;

                if ($aActualExecutionBlock !== $bActualExecutionBlock) {
                    return $aActualExecutionBlock ? -1 : 1;
                }

                return
                    ($b['severity_weight'] ?? 0)
                    <=>
                    ($a['severity_weight'] ?? 0);
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
            fn (array $a, array $b): int =>
                ($b['severity_weight'] ?? 0)
                <=>
                ($a['severity_weight'] ?? 0)
        );

        return $restrictions[0] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Condition Pressure Score
    |--------------------------------------------------------------------------
    */

    private function calculateConditionPressureScore(
        array $conditions
    ): float {
        $open =
            array_values(
                array_filter(
                    $conditions,
                    fn (array $condition): bool =>
                        ($condition['condition_open'] ?? false) === true
                )
            );

        if (empty($open)) {
            return 0.0;
        }

        $totalWeight =
            array_sum(
                array_map(
                    fn (array $condition): float =>
                        (float) (
                            $condition['severity_weight']
                            ?? 0
                        ),
                    $open
                )
            );

        return $this->score(
            $totalWeight / count($open)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restriction Pressure Score
    |--------------------------------------------------------------------------
    */

    private function calculateRestrictionPressureScore(
        array $restrictions
    ): float {
        if (empty($restrictions)) {
            return 0.0;
        }

        $totalWeight =
            array_sum(
                array_map(
                    fn (array $restriction): float =>
                        (float) (
                            $restriction['severity_weight']
                            ?? 0
                        ),
                    $restrictions
                )
            );

        return $this->score(
            $totalWeight / count($restrictions)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Condition Restriction State
    |--------------------------------------------------------------------------
    */

    private function determineConditionRestrictionState(
        int $criticalConditions,
        int $executionAuthorizationBlockers,
        int $actualExecutionBlockers,
        int $criticalRestrictions,
        int $materialRestrictions
    ): string {
        if (
            $criticalConditions > 0
            || $criticalRestrictions > 0
        ) {
            return 'CRITICAL_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION_RESTRICTIONS';
        }

        if (
            $executionAuthorizationBlockers > 0
            || $actualExecutionBlockers > 0
        ) {
            return 'MATERIAL_BLOCKING_CONTROLLED_ACTIVATION_EXECUTION_REQUIREMENTS';
        }

        if ($materialRestrictions > 0) {
            return 'MATERIAL_CONTROLLED_ACTIVATION_EXECUTION_RESTRICTIONS';
        }

        return 'CONTROLLED_ACTIVATION_EXECUTION_REQUIREMENTS_MANAGEABLE';
    }

    /*
    |--------------------------------------------------------------------------
    | Management Attention Level
    |--------------------------------------------------------------------------
    */

    private function managementAttentionLevel(
        int $criticalConditions,
        int $criticalRestrictions,
        int $executionAuthorizationBlockers,
        int $actualExecutionBlockers,
        mixed $executionRiskScore
    ): string {
        $risk =
            $this->score(
                $executionRiskScore
            );

        if (
            $criticalConditions > 0
            || $criticalRestrictions > 0
            || $risk >= 90
        ) {
            return 'CRITICAL';
        }

        if (
            $executionAuthorizationBlockers > 0
            || $actualExecutionBlockers > 0
            || $risk >= 75
        ) {
            return 'HIGH';
        }

        if ($risk >= 50) {
            return 'MODERATE';
        }

        if ($risk >= 25) {
            return 'ELEVATED';
        }

        return 'NORMAL';
    }

    /*
    |--------------------------------------------------------------------------
    | Execution Authorization Attribution
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
    | Step 70.4 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_execution_authorization_condition_restriction_intelligence_enabled' =>
                true,

            'condition_restriction_intelligence_is_execution_authorization' =>
                false,

            'condition_restriction_intelligence_is_actual_execution' =>
                false,

            'condition_restriction_intelligence_makes_execution_authorization_decision' =>
                false,

            'condition_restriction_intelligence_records_execution_authorization_decision' =>
                false,

            'condition_restriction_intelligence_completes_execution_authorization' =>
                false,

            'condition_restriction_intelligence_executes_controlled_activation' =>
                false,

            'condition_restriction_intelligence_activates_strategic_plan' =>
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

            'condition_restriction_intelligence_changes_source_activation_authorization' =>
                false,

            'condition_restriction_intelligence_changes_execution_authorization_status' =>
                false,

            'condition_restriction_intelligence_changes_execution_authorization_decision' =>
                false,

            'condition_restriction_intelligence_changes_execution_authorization_outcome' =>
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

            'condition_score_authorizes_execution_authorization' =>
                false,

            'condition_score_authorizes_actual_execution' =>
                false,

            'restriction_score_authorizes_execution_authorization' =>
                false,

            'restriction_score_authorizes_actual_execution' =>
                false,

            'condition_pressure_score_authorizes_execution_authorization' =>
                false,

            'condition_pressure_score_authorizes_actual_execution' =>
                false,

            'restriction_pressure_score_authorizes_execution_authorization' =>
                false,

            'restriction_pressure_score_authorizes_actual_execution' =>
                false,

            'condition_resolution_score_authorizes_execution' =>
                false,

            'evidence_resolution_score_authorizes_execution' =>
                false,

            'combined_resolution_score_authorizes_execution' =>
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

            'condition_restriction_intelligence_overrides_activation_authorization' =>
                false,

            'condition_restriction_intelligence_overrides_execution_authorization' =>
                false,

            'condition_restriction_intelligence_overrides_actual_execution_authority' =>
                false,

            'condition_restriction_intelligence_overrides_evidence_requirements' =>
                false,

            'automatic_condition_resolution_allowed' =>
                false,

            'automatic_restriction_removal_allowed' =>
                false,

            'automatic_evidence_validation_allowed' =>
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
                'Step 70.4 controlled activation execution authorization condition and restriction intelligence evaluates execution-authorization conditions, actual-execution conditions, source activation-authorization barriers, execution restrictions, critical restrictions, condition pressure, restriction pressure, resolution state, evidence state, progression blocking, and human-management attention requirements. Classification of a condition or restriction as blocking, constraining, critical, high, material, resolved, or advisory does not itself resolve, waive, remove, downgrade, validate, satisfy, authorize, or execute anything. This intelligence does not make, record, or complete an execution authorization decision; execute controlled activation; activate the strategic plan; modify source activation authorization; resolve dependencies; validate evidence; modify AI behavior; deploy changes; trigger rollback; or initiate clinical action. Execution authorization and actual controlled activation execution remain separate authorities reserved for explicitly authorized human governance.',
        ];
    }
}