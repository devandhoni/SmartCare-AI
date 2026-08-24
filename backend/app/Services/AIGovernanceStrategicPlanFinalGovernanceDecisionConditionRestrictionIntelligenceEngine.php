<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionConditionRestrictionIntelligenceEngine
{
    public function analyze(?int $finalGovernanceDecisionId = null): array
    {
        $finalGovernanceDecision = $finalGovernanceDecisionId
            ? AIGovernanceStrategicPlanFinalGovernanceDecision::find($finalGovernanceDecisionId)
            : AIGovernanceStrategicPlanFinalGovernanceDecision::latest('id')->first();

        if (!$finalGovernanceDecision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan final governance decision record is available for condition and restriction intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 67.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanFinalGovernanceDecisionStateIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($finalGovernanceDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $finalGovernanceState =
            $state['final_governance_decision_state'] ?? [];

        $conditionContext =
            $state['condition_context'] ?? [];

        $restrictionContext =
            $state['restriction_context'] ?? [];

        $validationContext =
            $state['validation_context'] ?? [];

        $reviewContext =
            $state['review_context'] ?? [];

        $governanceContext =
            $state['governance_context'] ?? [];

        $sourceContext =
            $state['source_context'] ?? [];

        $decisionConditions =
            $finalGovernanceDecision->decision_conditions ?? [];

        $decisionRestrictions =
            $finalGovernanceDecision->decision_restrictions ?? [];

        $validatedEvidence =
            $finalGovernanceDecision->validated_evidence ?? [];

        /*
        |--------------------------------------------------------------------------
        | Condition Analysis
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = [];

        foreach ($decisionConditions as $condition) {
            $status =
                strtoupper((string) ($condition['condition_status'] ?? 'OPEN'));

            $priority =
                strtoupper((string) ($condition['priority_level'] ?? 'ADVISORY'));

            $conditionType =
                strtoupper((string) ($condition['condition_type'] ?? 'GENERAL'));

            $conditionOpen =
                $status !== 'RESOLVED'
                && $status !== 'CLOSED'
                && $status !== 'SATISFIED';

            $conditionResolved =
                !$conditionOpen;

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'LOW' => 25.0,
                default => 10.0,
            };

            $blocksFinalDecision =
                $conditionOpen
                && in_array($priority, ['CRITICAL', 'HIGH'], true);

            $constrainsFinalDecision =
                $conditionOpen
                && !$blocksFinalDecision
                && in_array($priority, ['MODERATE', 'LOW'], true);

            $requiresAuthorizedHumanResolution =
                (bool) (
                    $condition['requires_authorized_human_resolution']
                    ?? true
                );

            $conditionAnalysis[] = array_merge(
                $condition,
                [
                    'condition_open' =>
                        $conditionOpen,

                    'condition_resolved' =>
                        $conditionResolved,

                    'condition_classification' =>
                        $blocksFinalDecision
                            ? 'BLOCKING'
                            : (
                                $constrainsFinalDecision
                                    ? 'CONSTRAINING'
                                    : (
                                        $conditionResolved
                                            ? 'RESOLVED'
                                            : 'ADVISORY'
                                    )
                            ),

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_final_governance_decision' =>
                        $blocksFinalDecision,

                    'constrains_final_governance_decision' =>
                        $constrainsFinalDecision,

                    'requires_authorized_human_resolution' =>
                        $requiresAuthorizedHumanResolution,

                    'automatic_resolution_allowed' =>
                        (bool) (
                            $condition['automatic_resolution_allowed']
                            ?? false
                        ),

                    'condition_type_normalized' =>
                        $conditionType,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Condition Collections
        |--------------------------------------------------------------------------
        */

        $openDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        ($condition['condition_open'] ?? false) === true
                )
            );

        $blockingDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        ($condition['condition_classification'] ?? null)
                        === 'BLOCKING'
                )
            );

        $constrainingDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        ($condition['condition_classification'] ?? null)
                        === 'CONSTRAINING'
                )
            );

        $resolvedDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        ($condition['condition_resolved'] ?? false) === true
                )
            );

        $criticalDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        strtoupper(
                            (string) (
                                $condition['priority_level']
                                ?? ''
                            )
                        ) === 'CRITICAL'
                        && (
                            $condition['condition_open']
                            ?? false
                        ) === true
                )
            );

        $highDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        strtoupper(
                            (string) (
                                $condition['priority_level']
                                ?? ''
                            )
                        ) === 'HIGH'
                        && (
                            $condition['condition_open']
                            ?? false
                        ) === true
                )
            );

        $moderateDecisionConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        strtoupper(
                            (string) (
                                $condition['priority_level']
                                ?? ''
                            )
                        ) === 'MODERATE'
                        && (
                            $condition['condition_open']
                            ?? false
                        ) === true
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Restriction Analysis
        |--------------------------------------------------------------------------
        */

        $restrictionAnalysis = [];

        foreach ($decisionRestrictions as $restriction) {
            $severity =
                strtoupper((string) ($restriction['severity'] ?? 'MODERATE'));

            $severityWeight = match ($severity) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'LOW' => 25.0,
                default => 10.0,
            };

            $materialRestriction =
                in_array(
                    $severity,
                    ['CRITICAL', 'HIGH', 'MODERATE'],
                    true
                );

            $criticalRestriction =
                $severity === 'CRITICAL';

            $restrictionAnalysis[] = array_merge(
                $restriction,
                [
                    'severity_weight' =>
                        $severityWeight,

                    'material_restriction' =>
                        $materialRestriction,

                    'critical_restriction' =>
                        $criticalRestriction,

                    'requires_authorized_human_governance' =>
                        true,

                    'automatic_restriction_removal_allowed' =>
                        false,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Restriction Collections
        |--------------------------------------------------------------------------
        */

        $materialDecisionRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction) =>
                        ($restriction['material_restriction'] ?? false) === true
                )
            );

        $criticalDecisionRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction) =>
                        ($restriction['critical_restriction'] ?? false) === true
                )
            );

        $highDecisionRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction) =>
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
        | Dominant Condition
        |--------------------------------------------------------------------------
        */

        $dominantDecisionCondition = null;

        if (!empty($openDecisionConditions)) {
            $sortedConditions = $openDecisionConditions;

            usort(
                $sortedConditions,
                function (array $a, array $b): int {
                    $severityCompare =
                        ($b['severity_weight'] ?? 0)
                        <=>
                        ($a['severity_weight'] ?? 0);

                    if ($severityCompare !== 0) {
                        return $severityCompare;
                    }

                    $aBlocks =
                        ($a['blocks_final_governance_decision'] ?? false)
                            ? 1
                            : 0;

                    $bBlocks =
                        ($b['blocks_final_governance_decision'] ?? false)
                            ? 1
                            : 0;

                    return $bBlocks <=> $aBlocks;
                }
            );

            $dominantDecisionCondition =
                $sortedConditions[0] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Restriction
        |--------------------------------------------------------------------------
        */

        $dominantDecisionRestriction = null;

        if (!empty($restrictionAnalysis)) {
            $sortedRestrictions = $restrictionAnalysis;

            usort(
                $sortedRestrictions,
                function (array $a, array $b): int {
                    return
                        ($b['severity_weight'] ?? 0)
                        <=>
                        ($a['severity_weight'] ?? 0);
                }
            );

            $dominantDecisionRestriction =
                $sortedRestrictions[0] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            count($conditionAnalysis);

        $openConditions =
            count($openDecisionConditions);

        $blockingConditions =
            count($blockingDecisionConditions);

        $constrainingConditions =
            count($constrainingDecisionConditions);

        $resolvedConditions =
            count($resolvedDecisionConditions);

        $criticalOpenConditions =
            count($criticalDecisionConditions);

        $highOpenConditions =
            count($highDecisionConditions);

        $moderateOpenConditions =
            count($moderateDecisionConditions);

        $totalRestrictions =
            count($restrictionAnalysis);

        $materialRestrictions =
            count($materialDecisionRestrictions);

        $criticalRestrictions =
            count($criticalDecisionRestrictions);

        $highRestrictions =
            count($highDecisionRestrictions);

        /*
        |--------------------------------------------------------------------------
        | Existing Resolution Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            (float) (
                $finalGovernanceDecision->condition_resolution_score
                ?? $conditionContext['condition_resolution_score']
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $finalGovernanceDecision->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $finalGovernanceDecision->combined_resolution_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Condition Pressure
        |--------------------------------------------------------------------------
        */

        $conditionPressureScore = 0.0;

        if ($totalConditions > 0) {
            $conditionPressureWeight =
                array_sum(
                    array_map(
                        fn (array $condition) =>
                            (
                                ($condition['condition_open'] ?? false)
                                    ? (float) (
                                        $condition['severity_weight']
                                        ?? 0
                                    )
                                    : 0
                            ),
                        $conditionAnalysis
                    )
                );

            $conditionPressureScore =
                round(
                    min(
                        100,
                        $conditionPressureWeight
                        / max(1, $totalConditions)
                    ),
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Restriction Pressure
        |--------------------------------------------------------------------------
        */

        $restrictionPressureScore = 0.0;

        if ($totalRestrictions > 0) {
            $restrictionPressureWeight =
                array_sum(
                    array_map(
                        fn (array $restriction) =>
                            (float) (
                                $restriction['severity_weight']
                                ?? 0
                            ),
                        $restrictionAnalysis
                    )
                );

            $restrictionPressureScore =
                round(
                    min(
                        100,
                        $restrictionPressureWeight
                        / max(1, $totalRestrictions)
                    ),
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Combined Condition / Restriction Pressure
        |--------------------------------------------------------------------------
        */

        $combinedConditionRestrictionPressureScore =
            round(
                min(
                    100,
                    (
                        ($conditionPressureScore * 0.55)
                        +
                        ($restrictionPressureScore * 0.45)
                    )
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Authorization Condition Context
        |--------------------------------------------------------------------------
        */

        $authorizationConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $condition['condition_type_normalized']
                                    ?? ''
                                )
                            ),
                            ['HUMAN_DECISION', 'AUTHORIZATION'],
                            true
                        )
                )
            );

        $authorizationRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction) =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $restriction['restriction_type']
                                    ?? ''
                                )
                            ),
                            ['HUMAN_DECISION', 'AUTHORIZATION'],
                            true
                        )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Condition Context
        |--------------------------------------------------------------------------
        */

        $governanceValidationConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $condition['condition_type_normalized']
                                    ?? ''
                                )
                            ),
                            [
                                'VALIDATION_CONDITION',
                                'GOVERNANCE_VALIDATION',
                                'VALIDATION',
                            ],
                            true
                        )
                )
            );

        $governanceValidationRestrictions =
            array_values(
                array_filter(
                    $restrictionAnalysis,
                    fn (array $restriction) =>
                        in_array(
                            strtoupper(
                                (string) (
                                    $restriction['restriction_type']
                                    ?? ''
                                )
                            ),
                            [
                                'VALIDATION_CONDITION',
                                'GOVERNANCE_VALIDATION',
                                'VALIDATION',
                            ],
                            true
                        )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Risk Condition Context
        |--------------------------------------------------------------------------
        */

        $riskConditions =
            array_values(
                array_filter(
                    $conditionAnalysis,
                    fn (array $condition) =>
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
                    fn (array $restriction) =>
                        strtoupper(
                            (string) (
                                $restriction['restriction_type']
                                ?? ''
                            )
                        ) === 'RISK'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Progression State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) (
                $finalGovernanceDecision->source_final_human_decision_recorded
                ?? false
            );

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) (
                $finalGovernanceDecision->source_decision_made_by_authorized_human
                ?? false
            );

        $sourceGovernanceValidationCompleted =
            (bool) (
                $finalGovernanceDecision->source_governance_validation_completed
                ?? false
            );

        $finalGovernanceDecisionRecorded =
            !empty(
                $finalGovernanceDecision->final_governance_decision
            );

        $decisionMadeByAuthorizedHuman =
            (bool) (
                $finalGovernanceDecision->decision_made_by_authorized_human
                ?? false
            );

        $finalGovernanceConfirmationCompleted =
            (bool) (
                $finalGovernanceDecision->final_governance_confirmation_completed
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Aggregate Condition Restriction State
        |--------------------------------------------------------------------------
        */

        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
        ) {
            $conditionRestrictionState =
                'CRITICAL_FINAL_GOVERNANCE_DECISION_RESTRICTIONS';
        } elseif (
            $blockingConditions > 0
            || $materialRestrictions > 0
        ) {
            $conditionRestrictionState =
                'MATERIAL_BLOCKING_FINAL_GOVERNANCE_DECISION_REQUIREMENTS';
        } elseif ($constrainingConditions > 0) {
            $conditionRestrictionState =
                'CONSTRAINED_FINAL_GOVERNANCE_DECISION_PROGRESSION';
        } elseif (
            $openConditions > 0
            || $totalRestrictions > 0
        ) {
            $conditionRestrictionState =
                'ADVISORY_FINAL_GOVERNANCE_DECISION_REQUIREMENTS';
        } else {
            $conditionRestrictionState =
                'FINAL_GOVERNANCE_DECISION_REQUIREMENTS_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Human Governance Resolution Requirement
        |--------------------------------------------------------------------------
        */

        $humanGovernanceResolutionRequired =
            $blockingConditions > 0
            || $constrainingConditions > 0
            || $materialRestrictions > 0;

        $unrestrictedFinalGovernanceProgressionAllowed =
            !$humanGovernanceResolutionRequired
            && $finalHumanDecisionRecorded
            && $sourceDecisionMadeByAuthorizedHuman
            && $sourceGovernanceValidationCompleted;

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        if (
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0
        ) {
            $humanManagementAttentionLevel =
                'CRITICAL';
        } elseif (
            $blockingConditions > 0
            || $highRestrictions > 0
        ) {
            $humanManagementAttentionLevel =
                'HIGH';
        } elseif (
            $constrainingConditions > 0
            || $materialRestrictions > 0
        ) {
            $humanManagementAttentionLevel =
                'MODERATE';
        } else {
            $humanManagementAttentionLevel =
                'ROUTINE';
        }

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'ROUTINE';

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalRestrictions > 0;

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

            'blocking_conditions' =>
                $blockingConditions,

            'constraining_conditions' =>
                $constrainingConditions,

            'critical_open_conditions' =>
                $criticalOpenConditions,

            'high_open_conditions' =>
                $highOpenConditions,

            'moderate_open_conditions' =>
                $moderateOpenConditions,

            'condition_resolution_score' =>
                round($conditionResolutionScore, 2),

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
        | Condition Restriction State
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionStateContext = [
            'state' =>
                $conditionRestrictionState,

            'total_conditions' =>
                $totalConditions,

            'open_conditions' =>
                $openConditions,

            'blocking_conditions' =>
                $blockingConditions,

            'constraining_conditions' =>
                $constrainingConditions,

            'resolved_conditions' =>
                $resolvedConditions,

            'total_restrictions' =>
                $totalRestrictions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'condition_resolution_score' =>
                round($conditionResolutionScore, 2),

            'evidence_resolution_score' =>
                round($evidenceResolutionScore, 2),

            'combined_resolution_score' =>
                round($combinedResolutionScore, 2),

            'condition_pressure_score' =>
                $conditionPressureScore,

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedConditionRestrictionPressureScore,

            'human_governance_resolution_required' =>
                $humanGovernanceResolutionRequired,

            'unrestricted_final_governance_progression_allowed' =>
                $unrestrictedFinalGovernanceProgressionAllowed,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'final_governance_decision_recorded' =>
                $finalGovernanceDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'final_governance_confirmation_completed' =>
                $finalGovernanceConfirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Condition Context
        |--------------------------------------------------------------------------
        */

        $authorizationConditionContext = [
            'authorization_condition_count' =>
                count($authorizationConditions),

            'authorization_restriction_count' =>
                count($authorizationRestrictions),

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'authorization_progression_blocked' =>
                !$finalHumanDecisionRecorded
                || !$sourceDecisionMadeByAuthorizedHuman,

            'conditions' =>
                $authorizationConditions,

            'restrictions' =>
                $authorizationRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Condition Context
        |--------------------------------------------------------------------------
        */

        $governanceValidationConditionContext = [
            'governance_validation_condition_count' =>
                count($governanceValidationConditions),

            'governance_validation_restriction_count' =>
                count($governanceValidationRestrictions),

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_validation_progression_blocked' =>
                !$sourceGovernanceValidationCompleted
                || count($governanceValidationConditions) > 0
                || count($governanceValidationRestrictions) > 0,

            'conditions' =>
                $governanceValidationConditions,

            'restrictions' =>
                $governanceValidationRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Risk Condition Context
        |--------------------------------------------------------------------------
        */

        $riskConditionContext = [
            'risk_condition_count' =>
                count($riskConditions),

            'risk_restriction_count' =>
                count($riskRestrictions),

            'final_decision_risk_level' =>
                $finalGovernanceDecision->final_decision_risk_level,

            'final_decision_risk_score' =>
                (float) (
                    $finalGovernanceDecision->final_decision_risk_score
                    ?? 0
                ),

            'risk_requires_authorized_human_governance' =>
                !empty($riskConditions)
                || !empty($riskRestrictions),

            'conditions' =>
                $riskConditions,

            'restrictions' =>
                $riskRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionFindings = [
            "Final governance decision condition and restriction intelligence is based on final governance decision record {$finalGovernanceDecision->id}.",

            "{$totalConditions} final governance decision condition(s) are represented.",

            "{$openConditions} final governance decision condition(s) remain open.",

            "{$blockingConditions} blocking final governance decision condition(s) remain active.",

            "{$constrainingConditions} constraining final governance decision condition(s) remain active.",

            "{$resolvedConditions} final governance decision condition(s) are currently resolved.",

            "{$criticalOpenConditions} critical final governance decision condition(s) remain open.",

            "{$totalRestrictions} final governance decision restriction(s) are represented.",

            "{$materialRestrictions} material final governance decision restriction(s) remain active.",

            "{$criticalRestrictions} critical final governance decision restriction(s) are represented.",

            'Current dominant final governance decision condition is '
                .(
                    $dominantDecisionCondition['condition_code']
                    ?? 'NONE'
                )
                .'.',

            'Current dominant final governance decision restriction is '
                .(
                    $dominantDecisionRestriction['restriction_code']
                    ?? 'NONE'
                )
                .'.',

            'Current condition resolution score is '
                .round($conditionResolutionScore, 2)
                .'.',

            'Current evidence resolution score is '
                .round($evidenceResolutionScore, 2)
                .'.',

            'Current combined resolution score is '
                .round($combinedResolutionScore, 2)
                .'.',

            'Current condition pressure score is '
                .$conditionPressureScore
                .'.',

            'Current restriction pressure score is '
                .$restrictionPressureScore
                .'.',

            'Current combined condition and restriction pressure score is '
                .$combinedConditionRestrictionPressureScore
                .'.',

            'Current final governance condition/restriction state is '
                .$conditionRestrictionState
                .'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO')
                .'.',

            'Source strategic plan decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO')
                .'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO')
                .'.',

            'Final governance decision recorded is '
                .($finalGovernanceDecisionRecorded ? 'YES' : 'NO')
                .'.',

            'Final governance decision made by authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO')
                .'.',

            'Final governance confirmation completed is '
                .($finalGovernanceConfirmationCompleted ? 'YES' : 'NO')
                .'.',

            'Final governance decision condition and restriction intelligence remains advisory and does not resolve, waive, remove, downgrade, validate, decide, confirm, activate, or execute any governance action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantDecisionCondition) {
            $managementPriorities[] =
                'Address the dominant final governance decision condition '
                .(
                    $dominantDecisionCondition['condition_code']
                    ?? 'UNKNOWN'
                )
                .' through explicitly authorized human governance.';
        }

        if ($dominantDecisionRestriction) {
            $managementPriorities[] =
                'Address the dominant final governance decision restriction '
                .(
                    $dominantDecisionRestriction['restriction_code']
                    ?? 'UNKNOWN'
                )
                .' through the established human governance process.';
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConditions} blocking final governance decision condition(s) before unrestricted final governance progression.";
        }

        if ($constrainingConditions > 0) {
            $managementPriorities[] =
                "Reduce or formally govern {$constrainingConditions} constraining final governance decision condition(s) to improve governance readiness.";
        }

        if ($materialRestrictions > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material final governance decision restriction(s).";
        }

        if (!$finalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human strategic plan decision before final governance decision progression.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source strategic plan decision remains attributable to an explicitly authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete any required source governance-validation process through authorized human governance before unrestricted final governance progression.';
        }

        if (
            (float) (
                $finalGovernanceDecision->final_decision_risk_score
                ?? 0
            ) >= 75
        ) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while final strategic plan decision risk remains high.';
        }

        $managementPriorities[] =
            'Keep condition and restriction intelligence strictly separate from final governance decision authority.';

        $managementPriorities[] =
            'Do not interpret condition classification, restriction severity, pressure scores, resolution scores, or readiness indicators as authorization to make or confirm the final governance decision.';

        $managementPriorities[] =
            'Preserve human governance authority, decision attribution, validation attribution, evidence quality, traceability, safety controls, and authority separation throughout final governance progression.';

        $managementPriorities =
            array_values(
                array_unique($managementPriorities)
            );

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_FINAL_DECISION_CONDITION_RESTRICTION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_final_governance_decision_id' =>
                $finalGovernanceDecision->id,

            'final_governance_decision_code' =>
                $finalGovernanceDecision->final_governance_decision_code,

            'strategic_plan_decision_validation_id' =>
                $finalGovernanceDecision->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $finalGovernanceDecision->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $finalGovernanceDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $finalGovernanceDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $finalGovernanceDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $finalGovernanceDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $finalGovernanceDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $finalGovernanceDecision->decision_scope,

            'resident_id' =>
                $finalGovernanceDecision->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Core State
            |--------------------------------------------------------------------------
            */

            'condition_restriction_state' =>
                $conditionRestrictionStateContext,

            'condition_summary' =>
                $conditionSummary,

            'restriction_summary' =>
                $restrictionSummary,

            /*
            |--------------------------------------------------------------------------
            | Dominant Requirements
            |--------------------------------------------------------------------------
            */

            'dominant_decision_condition' =>
                $dominantDecisionCondition,

            'dominant_decision_restriction' =>
                $dominantDecisionRestriction,

            /*
            |--------------------------------------------------------------------------
            | Condition Collections
            |--------------------------------------------------------------------------
            */

            'blocking_decision_conditions' =>
                $blockingDecisionConditions,

            'constraining_decision_conditions' =>
                $constrainingDecisionConditions,

            'resolved_decision_conditions' =>
                $resolvedDecisionConditions,

            'critical_decision_conditions' =>
                $criticalDecisionConditions,

            /*
            |--------------------------------------------------------------------------
            | Restriction Collections
            |--------------------------------------------------------------------------
            */

            'material_decision_restrictions' =>
                $materialDecisionRestrictions,

            'critical_decision_restrictions' =>
                $criticalDecisionRestrictions,

            /*
            |--------------------------------------------------------------------------
            | Specialized Governance Context
            |--------------------------------------------------------------------------
            */

            'authorization_condition_context' =>
                $authorizationConditionContext,

            'governance_validation_condition_context' =>
                $governanceValidationConditionContext,

            'risk_condition_context' =>
                $riskConditionContext,

            /*
            |--------------------------------------------------------------------------
            | Full Analysis
            |--------------------------------------------------------------------------
            */

            'condition_restriction_analysis' => [
                'conditions' =>
                    $conditionAnalysis,

                'restrictions' =>
                    $restrictionAnalysis,
            ],

            'validated_evidence' =>
                $validatedEvidence,

            /*
            |--------------------------------------------------------------------------
            | Upstream Context
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_state_context' =>
                $finalGovernanceState,

            'review_context' =>
                $reviewContext,

            'validation_context' =>
                $validationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            /*
            |--------------------------------------------------------------------------
            | Findings / Priorities
            |--------------------------------------------------------------------------
            */

            'condition_restriction_findings' =>
                $conditionRestrictionFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 67.4 Guardrails
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_condition_restriction_guardrails' => [
                'final_governance_decision_condition_restriction_intelligence_enabled' =>
                    true,

                'condition_restriction_intelligence_is_final_governance_decision' =>
                    false,

                'condition_restriction_intelligence_makes_final_governance_decision' =>
                    false,

                'condition_restriction_intelligence_records_final_governance_decision' =>
                    false,

                'condition_restriction_intelligence_confirms_final_governance_decision' =>
                    false,

                'condition_restriction_intelligence_approves_strategic_plan' =>
                    false,

                'condition_restriction_intelligence_rejects_strategic_plan' =>
                    false,

                'condition_restriction_intelligence_conditionally_approves_strategic_plan' =>
                    false,

                'condition_restriction_intelligence_defers_strategic_plan' =>
                    false,

                'condition_restriction_intelligence_accepts_governance_risk' =>
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

                'condition_restriction_intelligence_changes_source_human_decision' =>
                    false,

                'condition_restriction_intelligence_changes_governance_validation' =>
                    false,

                'condition_restriction_intelligence_changes_final_governance_decision_status' =>
                    false,

                'condition_restriction_intelligence_changes_final_governance_decision' =>
                    false,

                'condition_restriction_intelligence_changes_final_governance_outcome' =>
                    false,

                'condition_restriction_intelligence_changes_plan_status' =>
                    false,

                'condition_restriction_intelligence_changes_action_state' =>
                    false,

                'condition_restriction_intelligence_changes_priority' =>
                    false,

                'condition_restriction_intelligence_changes_eligibility' =>
                    false,

                'condition_score_authorizes_final_decision' =>
                    false,

                'restriction_score_authorizes_final_decision' =>
                    false,

                'condition_pressure_score_authorizes_final_decision' =>
                    false,

                'restriction_pressure_score_authorizes_final_decision' =>
                    false,

                'condition_resolution_score_authorizes_final_decision' =>
                    false,

                'evidence_resolution_score_authorizes_final_decision' =>
                    false,

                'combined_resolution_score_authorizes_final_decision' =>
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

                'condition_restriction_intelligence_overrides_governance_validation' =>
                    false,

                'condition_restriction_intelligence_overrides_final_human_decision' =>
                    false,

                'condition_restriction_intelligence_overrides_evidence_requirements' =>
                    false,

                'automatic_condition_resolution_allowed' =>
                    false,

                'automatic_restriction_removal_allowed' =>
                    false,

                'automatic_evidence_validation_allowed' =>
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

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,

                'message' =>
                    'Step 67.4 final governance decision condition and restriction intelligence evaluates the current final-governance decision conditions, restrictions, authorization barriers, governance-validation barriers, risk restrictions, condition pressure, restriction pressure, resolution state, and management attention requirements for explicitly authorized human governance. Classification of any condition or restriction as blocking, constraining, resolved, critical, high, material, or advisory does not itself change, resolve, waive, remove, downgrade, validate, or satisfy that condition or restriction. The intelligence does not make, record, or confirm the final governance decision; approve, reject, conditionally approve, defer, accept governance risk, or activate the strategic plan; resolve conditions or dependencies; validate evidence; modify upstream human decisions or governance-validation state; authorize AI modification; execute changes; deploy updates; trigger rollback; or initiate clinical action. Final governance decision authority remains reserved exclusively for explicitly authorized human governance.',
            ],
        ];
    }
}