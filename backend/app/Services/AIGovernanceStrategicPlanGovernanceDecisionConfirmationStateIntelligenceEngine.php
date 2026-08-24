<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationStateIntelligenceEngine
{
    /**
     * Step 68.3
     *
     * Strategic Plan Governance Decision Confirmation State Intelligence
     *
     * This engine evaluates the current state of a prepared Step 68
     * governance-decision confirmation record.
     *
     * It is informational only.
     */
    public function analyze(?int $confirmationId = null): array
    {
        $confirmation = $confirmationId
            ? AIGovernanceStrategicPlanGovernanceDecisionConfirmation::find($confirmationId)
            : AIGovernanceStrategicPlanGovernanceDecisionConfirmation::latest('id')->first();

        if (!$confirmation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_GOVERNANCE_DECISION_CONFIRMATION_AVAILABLE',
                'message' => 'No strategic plan governance decision confirmation record is available for confirmation-state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Arrays
        |--------------------------------------------------------------------------
        */

        $conditions = $this->normalizeArray(
            $confirmation->confirmation_conditions
        );

        $restrictions = $this->normalizeArray(
            $confirmation->confirmation_restrictions
        );

        $validatedEvidence = $this->normalizeArray(
            $confirmation->validated_evidence
        );

        $reviewContext = $this->normalizeArray(
            $confirmation->review_context
        );

        $validationContext = $this->normalizeArray(
            $confirmation->validation_context
        );

        $governanceContext = $this->normalizeArray(
            $confirmation->governance_context
        );

        $sourceContext = $this->normalizeArray(
            $confirmation->source_context
        );

        /*
        |--------------------------------------------------------------------------
        | Core Confirmation State
        |--------------------------------------------------------------------------
        */

        $sourceFinalGovernanceDecisionRecorded =
            (bool) $confirmation->source_final_governance_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $confirmation->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $confirmation->source_governance_validation_completed;

        $confirmationMadeByAuthorizedHuman =
            (bool) $confirmation->confirmation_made_by_authorized_human;

        $confirmationCompleted =
            (bool) $confirmation->governance_decision_confirmation_completed;

        $controlledActivationAuthorized =
            (bool) $confirmation->controlled_activation_authorized;

        $governanceConfirmationDecisionRecorded =
            filled($confirmation->governance_confirmation_decision);

        $confirmationOutcomeRecorded =
            filled($confirmation->confirmation_outcome);

        $confirmerIdentified =
            filled($confirmation->confirmed_by);

        $confirmerRoleAvailable =
            filled($confirmation->confirmer_role);

        $confirmationTimestampAvailable =
            !empty($confirmation->confirmed_at);

        $confirmationAttributionComplete =
            $confirmerIdentified
            && $confirmerRoleAvailable
            && $confirmationTimestampAvailable
            && $confirmationMadeByAuthorizedHuman;

        /*
        |--------------------------------------------------------------------------
        | Condition Analysis
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = array_values(
            array_map(
                fn ($condition) => $this->analyzeCondition($condition),
                array_filter($conditions, 'is_array')
            )
        );

        $openConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_open'] ?? false) === true
            )
        );

        $resolvedConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_resolved'] ?? false) === true
            )
        );

        $blockingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['blocks_governance_confirmation'] ?? false) === true
            )
        );

        $activationBlockingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['blocks_controlled_activation'] ?? false) === true
            )
        );

        $criticalOpenConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'CRITICAL'
            )
        );

        $highOpenConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'HIGH'
            )
        );

        $moderateOpenConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    in_array(
                        strtoupper(
                            (string) ($condition['priority_level'] ?? '')
                        ),
                        ['MODERATE', 'MEDIUM'],
                        true
                    )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Restriction Analysis
        |--------------------------------------------------------------------------
        */

        $restrictionAnalysis = array_values(
            array_map(
                fn ($restriction) => $this->analyzeRestriction($restriction),
                array_filter($restrictions, 'is_array')
            )
        );

        $materialRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    ($restriction['material_restriction'] ?? false) === true
            )
        );

        $criticalRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    ($restriction['critical_restriction'] ?? false) === true
            )
        );

        $highRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    strtoupper(
                        (string) ($restriction['severity'] ?? '')
                    ) === 'HIGH'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Evidence Analysis
        |--------------------------------------------------------------------------
        */

        $evidenceAnalysis = array_values(
            array_map(
                fn ($evidence) => $this->analyzeEvidence($evidence),
                array_filter($validatedEvidence, 'is_array')
            )
        );

        $validatedEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_validated'] ?? false) === true
            )
        );

        $outstandingEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false) === true
            )
        );

        $blockingEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['blocks_confirmation'] ?? false) === true
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions = count($conditionAnalysis);
        $openConditionCount = count($openConditions);
        $resolvedConditionCount = count($resolvedConditions);
        $blockingConditionCount = count($blockingConditions);
        $activationBlockingConditionCount = count($activationBlockingConditions);

        $totalRestrictions = count($restrictionAnalysis);
        $materialRestrictionCount = count($materialRestrictions);
        $criticalRestrictionCount = count($criticalRestrictions);

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $confirmationReadinessScore =
            $this->score($confirmation->confirmation_readiness_score);

        $confirmationRiskScore =
            $this->score($confirmation->confirmation_risk_score);

        $conditionResolutionScore =
            $this->score($confirmation->condition_resolution_score);

        $evidenceResolutionScore =
            $this->score($confirmation->evidence_resolution_score);

        $combinedResolutionScore =
            $this->score($confirmation->combined_resolution_score);

        $conditionPressureScore =
            $this->score($confirmation->condition_pressure_score);

        $restrictionPressureScore =
            $this->score($confirmation->restriction_pressure_score);

        $combinedConditionRestrictionPressureScore =
            $this->score(
                $confirmation->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Confirmation State Classification
        |--------------------------------------------------------------------------
        */

        $strategicConfirmationState =
            $this->determineStrategicConfirmationState(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $governanceConfirmationDecisionRecorded,
                $confirmationAttributionComplete,
                $confirmationCompleted,
                $blockingConditionCount,
                $materialRestrictionCount
            );

        $confirmationReadiness =
            $this->determineConfirmationReadiness(
                $confirmation,
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $confirmationCompleted,
                $blockingConditionCount,
                $materialRestrictionCount,
                $confirmationReadinessScore
            );

        $confirmationConfidence =
            $this->determineConfirmationConfidence(
                $confirmationReadinessScore,
                $confirmationRiskScore,
                $blockingConditionCount,
                $materialRestrictionCount
            );

        $humanGovernanceActionState =
            $this->determineHumanGovernanceActionState(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $confirmationCompleted,
                $blockingConditionCount,
                $materialRestrictionCount
            );

        /*
        |--------------------------------------------------------------------------
        | Completion State
        |--------------------------------------------------------------------------
        */

        $confirmationCompletionScore =
            $this->calculateConfirmationCompletionScore(
                $sourceFinalGovernanceDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $governanceConfirmationDecisionRecorded,
                $confirmationAttributionComplete,
                $confirmationMadeByAuthorizedHuman,
                $confirmationCompleted,
                $confirmationOutcomeRecorded,
                $controlledActivationAuthorized
            );

        $confirmationCompletionState =
            $this->determineConfirmationCompletionState(
                $confirmationCompleted,
                $confirmationAttributionComplete,
                $governanceConfirmationDecisionRecorded,
                $controlledActivationAuthorized
            );

        /*
        |--------------------------------------------------------------------------
        | Activation State
        |--------------------------------------------------------------------------
        */

        $activationState =
            $this->determineActivationState(
                $confirmation,
                $confirmationCompleted,
                $confirmationAttributionComplete,
                $blockingConditionCount,
                $activationBlockingConditionCount,
                $materialRestrictionCount,
                $controlledActivationAuthorized
            );

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            count($criticalOpenConditions) > 0
            || $criticalRestrictionCount > 0
            || $confirmationRiskScore >= 95;

        $humanManagementAttentionLevel =
            $this->determineManagementAttentionLevel(
                $immediateHumanInterventionRequired,
                $confirmationRiskScore,
                $blockingConditionCount,
                $materialRestrictionCount,
                $confirmationCompleted
            );

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'LOW';

        /*
        |--------------------------------------------------------------------------
        | Dominant Condition / Restriction
        |--------------------------------------------------------------------------
        */

        $dominantOpenCondition =
            $this->dominantCondition($openConditions);

        $dominantRestriction =
            $this->dominantRestriction($restrictionAnalysis);

        /*
        |--------------------------------------------------------------------------
        | Confirmation State
        |--------------------------------------------------------------------------
        */

        $confirmationState = [
            'confirmation_status' =>
                $confirmation->confirmation_status,

            'confirmation_mode' =>
                $confirmation->confirmation_mode,

            'prepared_decision' =>
                $confirmation->prepared_decision,

            'source_final_governance_decision' =>
                $confirmation->source_final_governance_decision,

            'source_final_governance_decision_recorded' =>
                $sourceFinalGovernanceDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_final_governance_outcome' =>
                $confirmation->source_final_governance_outcome,

            'source_final_governance_outcome_status' =>
                $confirmation->source_final_governance_outcome_status,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_confirmation_decision' =>
                $confirmation->governance_confirmation_decision,

            'governance_confirmation_decision_recorded' =>
                $governanceConfirmationDecisionRecorded,

            'confirmation_outcome' =>
                $confirmation->confirmation_outcome,

            'confirmation_outcome_status' =>
                $confirmation->confirmation_outcome_status,

            'strategic_governance_confirmation_state' =>
                $strategicConfirmationState,

            'confirmation_readiness' =>
                $confirmationReadiness,

            'confirmation_readiness_score' =>
                $confirmationReadinessScore,

            'confirmation_confidence' =>
                $confirmationConfidence,

            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                $confirmationRiskScore,

            'human_governance_action_state' =>
                $humanGovernanceActionState,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'controlled_activation_status' =>
                $confirmation->controlled_activation_status,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Attribution Context
        |--------------------------------------------------------------------------
        */

        $confirmationAttributionContext = [
            'confirmed_by' =>
                $confirmation->confirmed_by,

            'confirmer_role' =>
                $confirmation->confirmer_role,

            'confirmed_at' =>
                $confirmation->confirmed_at,

            'confirmer_identified' =>
                $confirmerIdentified,

            'confirmer_role_available' =>
                $confirmerRoleAvailable,

            'confirmation_timestamp_available' =>
                $confirmationTimestampAvailable,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Activation Context
        |--------------------------------------------------------------------------
        */

        $activationContext = [
            'controlled_activation_status' =>
                $confirmation->controlled_activation_status,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,

            'controlled_activation_authorization_code' =>
                $confirmation->controlled_activation_authorization_code,

            'activation_authorized_by' =>
                $confirmation->activation_authorized_by,

            'activation_authorizer_role' =>
                $confirmation->activation_authorizer_role,

            'activation_authorized_at' =>
                $confirmation->activation_authorized_at,

            'activation_state' =>
                $activationState,

            'activation_blocking_conditions' =>
                $activationBlockingConditionCount,

            'material_confirmation_restrictions' =>
                $materialRestrictionCount,

            'confirmation_completed' =>
                $confirmationCompleted,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'automatic_activation_allowed' =>
                (bool) $confirmation->automatic_activation_allowed,

            'automatic_execution_allowed' =>
                (bool) $confirmation->automatic_execution_allowed,

            'authorized_human_activation_required' =>
                (bool) $confirmation->authorized_human_activation_required,
        ];

        /*
        |--------------------------------------------------------------------------
        | Completion Context
        |--------------------------------------------------------------------------
        */

        $completionContext = [
            'confirmation_completion_state' =>
                $confirmationCompletionState,

            'confirmation_completion_score' =>
                $confirmationCompletionScore,

            'source_final_governance_decision_recorded' =>
                $sourceFinalGovernanceDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_confirmation_decision_recorded' =>
                $governanceConfirmationDecisionRecorded,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'confirmation_outcome_recorded' =>
                $confirmationOutcomeRecorded,

            'controlled_activation_authorized' =>
                $controlledActivationAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $confirmationFindings = [
            "Strategic plan governance decision confirmation state intelligence is based on confirmation record {$confirmation->id}.",

            'Current confirmation status is '
                .($confirmation->confirmation_status ?? 'UNKNOWN').'.',

            'Current strategic governance confirmation state is '
                .$strategicConfirmationState.'.',

            'Current confirmation readiness is '
                .$confirmationReadiness
                .' with score '
                .$confirmationReadinessScore.'.',

            'Current confirmation confidence is '
                .$confirmationConfidence.'.',

            'Current confirmation risk is '
                .($confirmation->confirmation_risk_level ?? 'UNKNOWN')
                .' with score '
                .$confirmationRiskScore.'.',

            "{$totalConditions} confirmation condition(s) are represented.",

            "{$openConditionCount} confirmation condition(s) remain open.",

            "{$blockingConditionCount} confirmation condition(s) currently block governance confirmation.",

            "{$activationBlockingConditionCount} confirmation condition(s) currently block controlled activation.",

            "{$resolvedConditionCount} confirmation condition(s) are currently resolved.",

            count($criticalOpenConditions)
                ." critical confirmation condition(s) remain open.",

            "{$totalRestrictions} confirmation restriction(s) are represented.",

            "{$materialRestrictionCount} material confirmation restriction(s) remain active.",

            "{$criticalRestrictionCount} critical confirmation restriction(s) are represented.",

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

            'Source final governance decision recorded is '
                .($sourceFinalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Source final governance decision made by authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Governance confirmation decision recorded is '
                .($governanceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Authorized human confirmation attribution complete is '
                .($confirmationAttributionComplete ? 'YES' : 'NO').'.',

            'Governance decision confirmation completed is '
                .($confirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorized is '
                .($controlledActivationAuthorized ? 'YES' : 'NO').'.',

            'Current confirmation completion state is '
                .$confirmationCompletionState
                .' with score '
                .$confirmationCompletionScore.'.',

            'Current controlled activation state is '
                .$activationState.'.',

            'Governance decision confirmation state intelligence remains informational and does not confirm a governance decision or authorize controlled activation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human final strategic plan governance decision before governance confirmation progression.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source final strategic plan governance decision remains explicitly attributable to an authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete required source governance validation through authorized human governance before unrestricted confirmation progression.';
        }

        if ($blockingConditionCount > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConditionCount} confirmation-blocking condition(s) before completed governance confirmation.";
        }

        if ($materialRestrictionCount > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictionCount} material confirmation restriction(s).";
        }

        if (!$confirmationAttributionComplete) {
            $managementPriorities[] =
                'Ensure any completed governance confirmation remains explicitly attributable to an identified authorized human governance confirmer.';
        }

        if (!$confirmationCompleted) {
            $managementPriorities[] =
                'Do not treat the confirmation package as completed until an explicitly authorized human governance confirmation is recorded and attributed.';
        }

        if (!$controlledActivationAuthorized) {
            $managementPriorities[] =
                'Keep controlled activation unauthorized until the separate authorized-human activation process is completed.';
        }

        $managementPriorities[] =
            'Keep confirmation-state intelligence strictly separate from governance-confirmation authority and controlled-activation authority.';

        $managementPriorities[] =
            'Do not interpret readiness, risk, pressure, resolution, completion, confidence, or activation-state intelligence as authorization to confirm or activate the strategic plan.';

        $managementPriorities[] =
            'Preserve human governance authority, source-decision attribution, validation attribution, confirmation attribution, evidence traceability, and activation authority separation throughout Step 68.';

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_STATE_INTELLIGENCE_AVAILABLE',

            /*
            |--------------------------------------------------------------------------
            | Record Identity
            |--------------------------------------------------------------------------
            */

            'strategic_plan_governance_decision_confirmation_id' =>
                $confirmation->id,

            'confirmation_code' =>
                $confirmation->confirmation_code,

            'strategic_plan_final_governance_decision_id' =>
                $confirmation->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $confirmation->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $confirmation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $confirmation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $confirmation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $confirmation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $confirmation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $confirmation->lifecycle_snapshot_id,

            'decision_scope' =>
                $confirmation->decision_scope,

            'resident_id' =>
                $confirmation->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Confirmation Intelligence
            |--------------------------------------------------------------------------
            */

            'confirmation_state' =>
                $confirmationState,

            'condition_context' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditionCount,

                'resolved_conditions' =>
                    $resolvedConditionCount,

                'blocking_confirmation_conditions' =>
                    $blockingConditionCount,

                'blocking_activation_conditions' =>
                    $activationBlockingConditionCount,

                'critical_open_conditions' =>
                    count($criticalOpenConditions),

                'high_open_conditions' =>
                    count($highOpenConditions),

                'moderate_open_conditions' =>
                    count($moderateOpenConditions),

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'condition_pressure_score' =>
                    $conditionPressureScore,
            ],

            'restriction_context' => [
                'total_restrictions' =>
                    $totalRestrictions,

                'material_restrictions' =>
                    $materialRestrictionCount,

                'critical_restrictions' =>
                    $criticalRestrictionCount,

                'high_restrictions' =>
                    count($highRestrictions),

                'restriction_pressure_score' =>
                    $restrictionPressureScore,

                'combined_condition_restriction_pressure_score' =>
                    $combinedConditionRestrictionPressureScore,
            ],

            'evidence_context' => [
                'total_evidence_items' =>
                    count($evidenceAnalysis),

                'validated_evidence_items' =>
                    count($validatedEvidenceItems),

                'outstanding_evidence_items' =>
                    count($outstandingEvidenceItems),

                'blocking_evidence_items' =>
                    count($blockingEvidenceItems),

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,
            ],

            'dominant_open_condition' =>
                $dominantOpenCondition,

            'dominant_restriction' =>
                $dominantRestriction,

            'confirmation_attribution_context' =>
                $confirmationAttributionContext,

            'activation_context' =>
                $activationContext,

            'completion_context' =>
                $completionContext,

            /*
            |--------------------------------------------------------------------------
            | Source Context
            |--------------------------------------------------------------------------
            */

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
            | Detailed Analysis
            |--------------------------------------------------------------------------
            */

            'condition_analysis' =>
                $conditionAnalysis,

            'open_confirmation_conditions' =>
                $openConditions,

            'resolved_confirmation_conditions' =>
                $resolvedConditions,

            'blocking_confirmation_conditions' =>
                $blockingConditions,

            'activation_blocking_conditions' =>
                $activationBlockingConditions,

            'restriction_analysis' =>
                $restrictionAnalysis,

            'material_confirmation_restrictions' =>
                $materialRestrictions,

            'critical_confirmation_restrictions' =>
                $criticalRestrictions,

            'evidence_analysis' =>
                $evidenceAnalysis,

            /*
            |--------------------------------------------------------------------------
            | Management Intelligence
            |--------------------------------------------------------------------------
            */

            'confirmation_findings' =>
                $confirmationFindings,

            'management_priorities' =>
                array_values(array_unique($managementPriorities)),

            /*
            |--------------------------------------------------------------------------
            | Step 68.3 Guardrails
            |--------------------------------------------------------------------------
            */

            'confirmation_state_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Condition Analysis
    |--------------------------------------------------------------------------
    */

    private function analyzeCondition(array $condition): array
    {
        $status = strtoupper(
            (string) ($condition['condition_status'] ?? 'OPEN')
        );

        $conditionOpen = !in_array(
            $status,
            [
                'RESOLVED',
                'CLOSED',
                'SATISFIED',
                'COMPLETED',
            ],
            true
        );

        $conditionResolved = !$conditionOpen;

        $priority = strtoupper(
            (string) ($condition['priority_level'] ?? 'MODERATE')
        );

        $severityWeight = match ($priority) {
            'CRITICAL' => 100.0,
            'HIGH' => 75.0,
            'MODERATE', 'MEDIUM' => 50.0,
            'LOW' => 25.0,
            default => 50.0,
        };

        return array_merge(
            $condition,
            [
                'condition_open' =>
                    $conditionOpen,

                'condition_resolved' =>
                    $conditionResolved,

                'severity_weight' =>
                    $severityWeight,

                'blocks_governance_confirmation' =>
                    $conditionOpen
                    && (
                        ($condition['blocks_governance_confirmation'] ?? false)
                        === true
                    ),

                'blocks_controlled_activation' =>
                    $conditionOpen
                    && (
                        ($condition['blocks_controlled_activation'] ?? false)
                        === true
                    ),

                'requires_authorized_human_resolution' =>
                    ($condition['requires_authorized_human_resolution'] ?? false)
                    === true,

                'automatic_resolution_allowed' =>
                    ($condition['automatic_resolution_allowed'] ?? false)
                    === true,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restriction Analysis
    |--------------------------------------------------------------------------
    */

    private function analyzeRestriction(array $restriction): array
    {
        $severity = strtoupper(
            (string) ($restriction['severity'] ?? 'MODERATE')
        );

        $severityWeight = match ($severity) {
            'CRITICAL' => 100.0,
            'HIGH', 'MATERIAL' => 75.0,
            'MODERATE', 'MEDIUM' => 50.0,
            'LOW' => 25.0,
            default => 50.0,
        };

        return array_merge(
            $restriction,
            [
                'severity_weight' =>
                    $severityWeight,

                'material_restriction' =>
                    in_array(
                        $severity,
                        ['CRITICAL', 'HIGH', 'MATERIAL'],
                        true
                    ),

                'critical_restriction' =>
                    $severity === 'CRITICAL',

                'requires_authorized_human_governance' =>
                    ($restriction['requires_authorized_human_governance'] ?? false)
                    === true,

                'automatic_restriction_removal_allowed' =>
                    ($restriction['automatic_restriction_removal_allowed'] ?? false)
                    === true,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Evidence Analysis
    |--------------------------------------------------------------------------
    */

    private function analyzeEvidence(array $evidence): array
    {
        $available =
            ($evidence['evidence_available'] ?? false) === true
            || strtoupper(
                (string) ($evidence['evidence_status'] ?? '')
            ) === 'AVAILABLE';

        $validated =
            ($evidence['evidence_validated'] ?? false) === true;

        $outstanding =
            ($evidence['evidence_outstanding'] ?? false) === true
            || (!$available && !$validated);

        $blocksConfirmation =
            ($evidence['blocks_confirmation'] ?? false) === true
            || (
                ($evidence['blocks_final_decision'] ?? false) === true
                && $outstanding
            );

        return array_merge(
            $evidence,
            [
                'evidence_available' =>
                    $available,

                'evidence_validated' =>
                    $validated,

                'evidence_outstanding' =>
                    $outstanding,

                'blocks_confirmation' =>
                    $blocksConfirmation,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | State Classification
    |--------------------------------------------------------------------------
    */

    private function determineStrategicConfirmationState(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $governanceConfirmationDecisionRecorded,
        bool $confirmationAttributionComplete,
        bool $confirmationCompleted,
        int $blockingConditions,
        int $materialRestrictions
    ): string {
        if ($confirmationCompleted) {
            return 'GOVERNANCE_DECISION_CONFIRMATION_COMPLETED';
        }

        if (!$sourceFinalGovernanceDecisionRecorded) {
            return 'PENDING_CONFIRMATION_AWAITING_FINAL_GOVERNANCE_DECISION';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return 'PENDING_CONFIRMATION_AWAITING_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION';
        }

        if (!$sourceGovernanceValidationCompleted) {
            return 'PENDING_CONFIRMATION_AWAITING_GOVERNANCE_VALIDATION';
        }

        if ($blockingConditions > 0 || $materialRestrictions > 0) {
            return 'PENDING_CONFIRMATION_WITH_MATERIAL_GOVERNANCE_REQUIREMENTS';
        }

        if (!$governanceConfirmationDecisionRecorded) {
            return 'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        }

        if (!$confirmationAttributionComplete) {
            return 'PENDING_CONFIRMATION_ATTRIBUTION_COMPLETION';
        }

        return 'PENDING_CONFIRMATION_COMPLETION';
    }

    private function determineConfirmationReadiness(
        AIGovernanceStrategicPlanGovernanceDecisionConfirmation $confirmation,
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $confirmationCompleted,
        int $blockingConditions,
        int $materialRestrictions,
        float $score
    ): string {
        if ($confirmationCompleted) {
            return 'GOVERNANCE_CONFIRMATION_COMPLETED';
        }

        if (!$sourceFinalGovernanceDecisionRecorded) {
            return 'AWAITING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return 'AWAITING_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION';
        }

        if (!$sourceGovernanceValidationCompleted) {
            return 'AWAITING_GOVERNANCE_VALIDATION_COMPLETION';
        }

        if ($blockingConditions > 0) {
            return 'BLOCKED_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($materialRestrictions > 0) {
            return 'RESTRICTED_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($score < 35) {
            return 'VERY_LIMITED_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($score < 55) {
            return 'LIMITED_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($score < 75) {
            return 'CONDITIONAL_GOVERNANCE_CONFIRMATION_READINESS';
        }

        return 'READY_FOR_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REVIEW';
    }

    private function determineConfirmationConfidence(
        float $readinessScore,
        float $riskScore,
        int $blockingConditions,
        int $materialRestrictions
    ): string {
        $confidenceScore =
            ($readinessScore * 0.65)
            + ((100 - $riskScore) * 0.35);

        $confidenceScore -= min(
            25,
            ($blockingConditions * 3)
            + ($materialRestrictions * 2)
        );

        $confidenceScore = max(
            0,
            min(100, $confidenceScore)
        );

        return match (true) {
            $confidenceScore >= 85 => 'VERY_HIGH',
            $confidenceScore >= 70 => 'HIGH',
            $confidenceScore >= 55 => 'MODERATE',
            $confidenceScore >= 35 => 'LIMITED',
            $confidenceScore >= 15 => 'VERY_LIMITED',
            default => 'EXTREMELY_LIMITED',
        };
    }

    private function determineHumanGovernanceActionState(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $confirmationCompleted,
        int $blockingConditions,
        int $materialRestrictions
    ): string {
        if ($confirmationCompleted) {
            return 'GOVERNANCE_CONFIRMATION_COMPLETED_AWAITING_SEPARATE_ACTIVATION_AUTHORITY';
        }

        if (!$sourceFinalGovernanceDecisionRecorded) {
            return 'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION_REQUIRED';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return 'AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION_REQUIRED';
        }

        if (!$sourceGovernanceValidationCompleted) {
            return 'AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION_COMPLETION_REQUIRED';
        }

        if ($blockingConditions > 0) {
            return 'BLOCKING_CONFIRMATION_CONDITIONS_REQUIRE_HUMAN_GOVERNANCE';
        }

        if ($materialRestrictions > 0) {
            return 'MATERIAL_CONFIRMATION_RESTRICTIONS_REQUIRE_HUMAN_GOVERNANCE';
        }

        return 'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REQUIRED';
    }

    /*
    |--------------------------------------------------------------------------
    | Completion
    |--------------------------------------------------------------------------
    */

    private function calculateConfirmationCompletionScore(
        bool $sourceFinalGovernanceDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $governanceConfirmationDecisionRecorded,
        bool $confirmationAttributionComplete,
        bool $confirmationMadeByAuthorizedHuman,
        bool $confirmationCompleted,
        bool $confirmationOutcomeRecorded,
        bool $controlledActivationAuthorized
    ): float {
        $checks = [
            $sourceFinalGovernanceDecisionRecorded,
            $sourceDecisionMadeByAuthorizedHuman,
            $sourceGovernanceValidationCompleted,
            $governanceConfirmationDecisionRecorded,
            $confirmationAttributionComplete,
            $confirmationMadeByAuthorizedHuman,
            $confirmationCompleted,
            $confirmationOutcomeRecorded,
            $controlledActivationAuthorized,
        ];

        $passed = collect($checks)
            ->filter(fn ($value) => $value === true)
            ->count();

        return round(
            ($passed / count($checks)) * 100,
            2
        );
    }

    private function determineConfirmationCompletionState(
        bool $confirmationCompleted,
        bool $confirmationAttributionComplete,
        bool $governanceConfirmationDecisionRecorded,
        bool $controlledActivationAuthorized
    ): string {
        if (
            $confirmationCompleted
            && $confirmationAttributionComplete
            && $governanceConfirmationDecisionRecorded
        ) {
            return $controlledActivationAuthorized
                ? 'CONFIRMATION_COMPLETED_WITH_SEPARATE_CONTROLLED_ACTIVATION_AUTHORIZATION'
                : 'CONFIRMATION_COMPLETED_WITHOUT_CONTROLLED_ACTIVATION_AUTHORIZATION';
        }

        if ($governanceConfirmationDecisionRecorded) {
            return 'CONFIRMATION_DECISION_RECORDED_PENDING_COMPLETION_CONTROLS';
        }

        return 'GOVERNANCE_CONFIRMATION_NOT_COMPLETED';
    }

    /*
    |--------------------------------------------------------------------------
    | Controlled Activation
    |--------------------------------------------------------------------------
    */

    private function determineActivationState(
        AIGovernanceStrategicPlanGovernanceDecisionConfirmation $confirmation,
        bool $confirmationCompleted,
        bool $confirmationAttributionComplete,
        int $blockingConditions,
        int $activationBlockingConditions,
        int $materialRestrictions,
        bool $controlledActivationAuthorized
    ): string {
        if ($controlledActivationAuthorized) {
            if (
                !$confirmationCompleted
                || !$confirmationAttributionComplete
                || $blockingConditions > 0
                || $activationBlockingConditions > 0
                || $materialRestrictions > 0
            ) {
                return 'CONTROLLED_ACTIVATION_AUTHORIZATION_INTEGRITY_CONFLICT';
            }

            return 'CONTROLLED_ACTIVATION_AUTHORIZED_BY_HUMAN_GOVERNANCE';
        }

        if (!$confirmationCompleted) {
            return 'CONTROLLED_ACTIVATION_BLOCKED_PENDING_GOVERNANCE_CONFIRMATION';
        }

        if (!$confirmationAttributionComplete) {
            return 'CONTROLLED_ACTIVATION_BLOCKED_PENDING_CONFIRMATION_ATTRIBUTION';
        }

        if ($activationBlockingConditions > 0) {
            return 'CONTROLLED_ACTIVATION_BLOCKED_BY_CONFIRMATION_CONDITIONS';
        }

        if ($materialRestrictions > 0) {
            return 'CONTROLLED_ACTIVATION_RESTRICTED_BY_GOVERNANCE_CONTROLS';
        }

        return 'CONTROLLED_ACTIVATION_NOT_AUTHORIZED';
    }

    /*
    |--------------------------------------------------------------------------
    | Management Attention
    |--------------------------------------------------------------------------
    */

    private function determineManagementAttentionLevel(
        bool $immediateInterventionRequired,
        float $riskScore,
        int $blockingConditions,
        int $materialRestrictions,
        bool $confirmationCompleted
    ): string {
        if ($immediateInterventionRequired) {
            return 'CRITICAL';
        }

        if (
            $riskScore >= 70
            || $blockingConditions > 0
            || $materialRestrictions > 0
        ) {
            return 'HIGH';
        }

        if (!$confirmationCompleted || $riskScore >= 50) {
            return 'MODERATE';
        }

        return 'LOW';
    }

    /*
    |--------------------------------------------------------------------------
    | Dominant Condition / Restriction
    |--------------------------------------------------------------------------
    */

    private function dominantCondition(array $conditions): ?array
    {
        if (empty($conditions)) {
            return null;
        }

        usort(
            $conditions,
            fn ($a, $b) =>
                ($b['severity_weight'] ?? 0)
                <=> ($a['severity_weight'] ?? 0)
        );

        return $conditions[0] ?? null;
    }

    private function dominantRestriction(array $restrictions): ?array
    {
        if (empty($restrictions)) {
            return null;
        }

        usort(
            $restrictions,
            fn ($a, $b) =>
                ($b['severity_weight'] ?? 0)
                <=> ($a['severity_weight'] ?? 0)
        );

        return $restrictions[0] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
    }

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(
                0,
                min(100, (float) $value)
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Step 68.3 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'strategic_plan_governance_decision_confirmation_state_intelligence_enabled' =>
                true,

            'confirmation_state_intelligence_is_governance_confirmation' =>
                false,

            'confirmation_state_intelligence_makes_governance_confirmation_decision' =>
                false,

            'confirmation_state_intelligence_records_governance_confirmation_decision' =>
                false,

            'confirmation_state_intelligence_completes_governance_confirmation' =>
                false,

            'confirmation_state_intelligence_makes_final_governance_decision' =>
                false,

            'confirmation_state_intelligence_records_final_governance_decision' =>
                false,

            'confirmation_state_intelligence_approves_strategic_plan' =>
                false,

            'confirmation_state_intelligence_rejects_strategic_plan' =>
                false,

            'confirmation_state_intelligence_conditionally_approves_strategic_plan' =>
                false,

            'confirmation_state_intelligence_defers_strategic_plan' =>
                false,

            'confirmation_state_intelligence_accepts_governance_risk' =>
                false,

            'confirmation_state_intelligence_authorizes_controlled_activation' =>
                false,

            'confirmation_state_intelligence_activates_strategic_plan' =>
                false,

            'confirmation_state_intelligence_changes_confirmation_status' =>
                false,

            'confirmation_state_intelligence_changes_confirmation_decision' =>
                false,

            'confirmation_state_intelligence_changes_confirmation_outcome' =>
                false,

            'confirmation_state_intelligence_changes_source_final_governance_decision' =>
                false,

            'confirmation_state_intelligence_changes_governance_validation' =>
                false,

            'confirmation_state_intelligence_changes_plan_status' =>
                false,

            'confirmation_state_intelligence_changes_action_state' =>
                false,

            'confirmation_state_intelligence_changes_priority' =>
                false,

            'confirmation_state_intelligence_changes_eligibility' =>
                false,

            'confirmation_state_intelligence_resolves_conditions' =>
                false,

            'confirmation_state_intelligence_waives_conditions' =>
                false,

            'confirmation_state_intelligence_removes_restrictions' =>
                false,

            'confirmation_state_intelligence_resolves_dependencies' =>
                false,

            'confirmation_state_intelligence_validates_evidence' =>
                false,

            'confirmation_readiness_score_authorizes_confirmation' =>
                false,

            'confirmation_risk_score_authorizes_confirmation' =>
                false,

            'confirmation_confidence_authorizes_confirmation' =>
                false,

            'confirmation_completion_score_authorizes_confirmation' =>
                false,

            'condition_resolution_score_authorizes_confirmation' =>
                false,

            'evidence_resolution_score_authorizes_confirmation' =>
                false,

            'combined_resolution_score_authorizes_confirmation' =>
                false,

            'condition_pressure_score_authorizes_confirmation' =>
                false,

            'restriction_pressure_score_authorizes_confirmation' =>
                false,

            'activation_state_authorizes_activation' =>
                false,

            'confirmation_state_intelligence_authorizes_ai_change' =>
                false,

            'confirmation_state_intelligence_authorizes_execution' =>
                false,

            'confirmation_state_intelligence_authorizes_deployment' =>
                false,

            'confirmation_state_intelligence_authorizes_rollback' =>
                false,

            'confirmation_state_intelligence_authorizes_clinical_action' =>
                false,

            'confirmation_state_intelligence_overrides_human_review' =>
                false,

            'confirmation_state_intelligence_overrides_governance_validation' =>
                false,

            'confirmation_state_intelligence_overrides_final_governance_decision' =>
                false,

            'confirmation_state_intelligence_overrides_confirmation_authority' =>
                false,

            'confirmation_state_intelligence_overrides_activation_authority' =>
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

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'controlled_activation_authority_reserved_for_authorized_human' =>
                true,

            'final_governance_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'authorized_human_confirmation_required' =>
                true,

            'authorized_human_activation_required' =>
                true,

            'message' =>
                'Step 68.3 strategic plan governance decision confirmation state intelligence evaluates confirmation status, source final-governance-decision state, governance-validation state, confirmation readiness, risk, conditions, restrictions, evidence, authorized-human confirmation attribution, confirmation completion, and controlled-activation state for governance oversight only. The intelligence does not make, record, or complete governance confirmation; make or alter the final governance decision; approve, reject, conditionally approve, defer, or accept governance risk; authorize controlled activation; activate the strategic plan; resolve or waive conditions; remove restrictions; validate evidence; alter governance-validation state; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Governance confirmation, final governance decision, and controlled activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}