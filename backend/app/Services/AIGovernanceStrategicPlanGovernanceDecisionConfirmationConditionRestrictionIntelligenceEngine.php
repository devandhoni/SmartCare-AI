<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationConditionRestrictionIntelligenceEngine
{
    /**
     * Step 68.4
     *
     * Strategic Plan Governance Decision Confirmation
     * Condition & Restriction Intelligence
     *
     * This engine evaluates confirmation conditions, confirmation restrictions,
     * source-governance dependencies, controlled-activation barriers,
     * condition/restriction pressure, and management attention requirements.
     *
     * It is strictly advisory and informational.
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
                'message' => 'No strategic plan governance decision confirmation record is available for condition and restriction intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 68.3 State Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanGovernanceDecisionConfirmationStateIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($confirmation->id);

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $confirmationState =
            $state['confirmation_state'] ?? [];

        $stateConditionContext =
            $state['condition_context'] ?? [];

        $stateRestrictionContext =
            $state['restriction_context'] ?? [];

        $stateEvidenceContext =
            $state['evidence_context'] ?? [];

        $confirmationAttributionContext =
            $state['confirmation_attribution_context'] ?? [];

        $activationContext =
            $state['activation_context'] ?? [];

        $completionContext =
            $state['completion_context'] ?? [];

        $reviewContext =
            $state['review_context'] ?? $this->normalizeArray(
                $confirmation->review_context
            );

        $validationContext =
            $state['validation_context'] ?? $this->normalizeArray(
                $confirmation->validation_context
            );

        $governanceContext =
            $state['governance_context'] ?? $this->normalizeArray(
                $confirmation->governance_context
            );

        $sourceContext =
            $state['source_context'] ?? $this->normalizeArray(
                $confirmation->source_context
            );

        /*
        |--------------------------------------------------------------------------
        | Core Governance State
        |--------------------------------------------------------------------------
        */

        $sourceFinalGovernanceDecisionRecorded =
            (bool) $confirmation->source_final_governance_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $confirmation->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $confirmation->source_governance_validation_completed;

        $governanceConfirmationDecisionRecorded =
            filled($confirmation->governance_confirmation_decision);

        $confirmationMadeByAuthorizedHuman =
            (bool) $confirmation->confirmation_made_by_authorized_human;

        $confirmationCompleted =
            (bool) $confirmation->governance_decision_confirmation_completed;

        $controlledActivationAuthorized =
            (bool) $confirmation->controlled_activation_authorized;

        $confirmationAttributionComplete =
            ($confirmationAttributionContext['confirmation_attribution_complete'] ?? false)
            === true;

        /*
        |--------------------------------------------------------------------------
        | Condition Analysis
        |--------------------------------------------------------------------------
        */

        $conditions = $this->normalizeArray(
            $confirmation->confirmation_conditions
        );

        $conditionAnalysis = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $conditionAnalysis[] =
                $this->analyzeCondition($condition);
        }

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

        $blockingConfirmationConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    ($condition['blocks_governance_confirmation'] ?? false)
                    === true
            )
        );

        $blockingActivationConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    ($condition['blocks_controlled_activation'] ?? false)
                    === true
            )
        );

        $constrainingConfirmationConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    ($condition['blocks_governance_confirmation'] ?? false)
                        !== true
                    && ($condition['blocks_controlled_activation'] ?? false)
                        === true
            )
        );

        $criticalConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'CRITICAL'
            )
        );

        $highConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'HIGH'
            )
        );

        $moderateConditions = array_values(
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

        $restrictions = $this->normalizeArray(
            $confirmation->confirmation_restrictions
        );

        $restrictionAnalysis = [];

        foreach ($restrictions as $restriction) {
            if (!is_array($restriction)) {
                continue;
            }

            $restrictionAnalysis[] =
                $this->analyzeRestriction($restriction);
        }

        $materialRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    ($restriction['material_restriction'] ?? false)
                    === true
            )
        );

        $criticalRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    ($restriction['critical_restriction'] ?? false)
                    === true
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

        $validatedEvidence =
            $this->normalizeArray(
                $confirmation->validated_evidence
            );

        $evidenceAnalysis = [];

        foreach ($validatedEvidence as $evidence) {
            if (!is_array($evidence)) {
                continue;
            }

            $evidenceAnalysis[] =
                $this->analyzeEvidence($evidence);
        }

        $validatedEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_validated'] ?? false)
                    === true
            )
        );

        $outstandingEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false)
                    === true
            )
        );

        $blockingConfirmationEvidence = array_values(
            array_filter(
                $outstandingEvidenceItems,
                fn ($evidence) =>
                    ($evidence['blocks_governance_confirmation'] ?? false)
                    === true
            )
        );

        $blockingActivationEvidence = array_values(
            array_filter(
                $outstandingEvidenceItems,
                fn ($evidence) =>
                    ($evidence['blocks_controlled_activation'] ?? false)
                    === true
            )
        );

        $criticalOutstandingEvidence = array_values(
            array_filter(
                $outstandingEvidenceItems,
                fn ($evidence) =>
                    strtoupper(
                        (string) ($evidence['priority_level'] ?? '')
                    ) === 'CRITICAL'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            count($conditionAnalysis);

        $openConditionCount =
            count($openConditions);

        $resolvedConditionCount =
            count($resolvedConditions);

        $blockingConfirmationConditionCount =
            count($blockingConfirmationConditions);

        $blockingActivationConditionCount =
            count($blockingActivationConditions);

        $constrainingConditionCount =
            count($constrainingConfirmationConditions);

        $criticalConditionCount =
            count($criticalConditions);

        $totalRestrictions =
            count($restrictionAnalysis);

        $materialRestrictionCount =
            count($materialRestrictions);

        $criticalRestrictionCount =
            count($criticalRestrictions);

        $outstandingEvidenceCount =
            count($outstandingEvidenceItems);

        $blockingConfirmationEvidenceCount =
            count($blockingConfirmationEvidence);

        $blockingActivationEvidenceCount =
            count($blockingActivationEvidence);

        $criticalOutstandingEvidenceCount =
            count($criticalOutstandingEvidence);

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $conditionResolutionScore =
            $this->score(
                $confirmation->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $confirmation->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $confirmation->combined_resolution_score
            );

        $storedConditionPressureScore =
            $this->score(
                $confirmation->condition_pressure_score
            );

        $storedRestrictionPressureScore =
            $this->score(
                $confirmation->restriction_pressure_score
            );

        $storedCombinedPressureScore =
            $this->score(
                $confirmation->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Calculated Pressure
        |--------------------------------------------------------------------------
        */

        $calculatedConditionPressureScore =
            $this->calculateConditionPressure(
                $conditionAnalysis
            );

        $calculatedRestrictionPressureScore =
            $this->calculateRestrictionPressure(
                $restrictionAnalysis
            );

        $conditionPressureScore =
            max(
                $storedConditionPressureScore,
                $calculatedConditionPressureScore
            );

        $restrictionPressureScore =
            max(
                $storedRestrictionPressureScore,
                $calculatedRestrictionPressureScore
            );

        $combinedConditionRestrictionPressureScore =
            round(
                max(
                    $storedCombinedPressureScore,
                    (
                        ($conditionPressureScore * 0.55)
                        + ($restrictionPressureScore * 0.45)
                    )
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Dominant Governance Barriers
        |--------------------------------------------------------------------------
        */

        $dominantConfirmationCondition =
            $this->dominantCondition(
                $openConditions
            );

        $dominantConfirmationRestriction =
            $this->dominantRestriction(
                $restrictionAnalysis
            );

        /*
        |--------------------------------------------------------------------------
        | Specialized Contexts
        |--------------------------------------------------------------------------
        */

        $sourceDecisionConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    in_array(
                        strtoupper(
                            (string) ($condition['condition_type'] ?? '')
                        ),
                        [
                            'FINAL_GOVERNANCE_DECISION',
                            'AUTHORIZATION',
                        ],
                        true
                    )
            )
        );

        $validationConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    in_array(
                        strtoupper(
                            (string) ($condition['condition_type'] ?? '')
                        ),
                        [
                            'GOVERNANCE_VALIDATION',
                            'GOVERNANCE_CONDITION',
                            'GOVERNANCE_RESTRICTION',
                        ],
                        true
                    )
            )
        );

        $riskConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['condition_type'] ?? '')
                    ) === 'RISK'
            )
        );

        $confirmationAuthorityConditions = array_values(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['condition_type'] ?? '')
                    ) === 'HUMAN_CONFIRMATION'
            )
        );

        $sourceDecisionRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    in_array(
                        strtoupper(
                            (string) ($restriction['restriction_type'] ?? '')
                        ),
                        [
                            'FINAL_GOVERNANCE_DECISION',
                            'AUTHORIZATION',
                        ],
                        true
                    )
            )
        );

        $validationRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    in_array(
                        strtoupper(
                            (string) ($restriction['restriction_type'] ?? '')
                        ),
                        [
                            'GOVERNANCE_VALIDATION',
                            'GOVERNANCE_CONDITION',
                            'GOVERNANCE_RESTRICTION',
                        ],
                        true
                    )
            )
        );

        $riskRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    strtoupper(
                        (string) ($restriction['restriction_type'] ?? '')
                    ) === 'RISK'
            )
        );

        $confirmationAuthorityRestrictions = array_values(
            array_filter(
                $restrictionAnalysis,
                fn ($restriction) =>
                    strtoupper(
                        (string) ($restriction['restriction_type'] ?? '')
                    ) === 'HUMAN_CONFIRMATION'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Confirmation Progression
        |--------------------------------------------------------------------------
        */

        $confirmationProgressionBlocked =
            !$sourceFinalGovernanceDecisionRecorded
            || !$sourceDecisionMadeByAuthorizedHuman
            || !$sourceGovernanceValidationCompleted
            || $blockingConfirmationConditionCount > 0
            || $blockingConfirmationEvidenceCount > 0
            || $criticalRestrictionCount > 0;

        $controlledActivationBlocked =
            !$confirmationCompleted
            || !$confirmationAttributionComplete
            || $blockingActivationConditionCount > 0
            || $blockingActivationEvidenceCount > 0
            || $materialRestrictionCount > 0
            || !$controlledActivationAuthorized;

        /*
        |--------------------------------------------------------------------------
        | Condition / Restriction State
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionState =
            $this->determineConditionRestrictionState(
                $criticalConditionCount,
                $criticalRestrictionCount,
                $blockingConfirmationConditionCount,
                $blockingActivationConditionCount,
                $materialRestrictionCount,
                $outstandingEvidenceCount,
                $confirmationCompleted,
                $controlledActivationAuthorized
            );

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $confirmationRiskScore =
            $this->score(
                $confirmation->confirmation_risk_score
            );

        $immediateHumanInterventionRequired =
            $criticalConditionCount > 0
            || $criticalRestrictionCount > 0
            || $criticalOutstandingEvidenceCount > 0
            || $confirmationRiskScore >= 95;

        $humanManagementAttentionLevel =
            $this->determineManagementAttentionLevel(
                $immediateHumanInterventionRequired,
                $confirmationRiskScore,
                $blockingConfirmationConditionCount,
                $blockingActivationConditionCount,
                $materialRestrictionCount,
                $confirmationCompleted,
                $controlledActivationAuthorized
            );

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'LOW';

        /*
        |--------------------------------------------------------------------------
        | Source Decision Context
        |--------------------------------------------------------------------------
        */

        $sourceDecisionContext = [
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

            'source_decision_condition_count' =>
                count($sourceDecisionConditions),

            'source_decision_restriction_count' =>
                count($sourceDecisionRestrictions),

            'source_decision_progression_blocked' =>
                !$sourceFinalGovernanceDecisionRecorded
                || !$sourceDecisionMadeByAuthorizedHuman,

            'conditions' =>
                $sourceDecisionConditions,

            'restrictions' =>
                $sourceDecisionRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Barrier Context
        |--------------------------------------------------------------------------
        */

        $governanceValidationBarrierContext = [
            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'governance_validation_condition_count' =>
                count($validationConditions),

            'governance_validation_restriction_count' =>
                count($validationRestrictions),

            'governance_validation_progression_blocked' =>
                !$sourceGovernanceValidationCompleted
                || count($validationConditions) > 0
                || count($validationRestrictions) > 0,

            'conditions' =>
                $validationConditions,

            'restrictions' =>
                $validationRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Risk Context
        |--------------------------------------------------------------------------
        */

        $riskContext = [
            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                $confirmationRiskScore,

            'risk_condition_count' =>
                count($riskConditions),

            'risk_restriction_count' =>
                count($riskRestrictions),

            'risk_requires_authorized_human_governance' =>
                $confirmationRiskScore >= 70
                || count($riskConditions) > 0
                || count($riskRestrictions) > 0,

            'conditions' =>
                $riskConditions,

            'restrictions' =>
                $riskRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Authority Context
        |--------------------------------------------------------------------------
        */

        $confirmationAuthorityContext = [
            'governance_confirmation_decision' =>
                $confirmation->governance_confirmation_decision,

            'governance_confirmation_decision_recorded' =>
                $governanceConfirmationDecisionRecorded,

            'confirmation_made_by_authorized_human' =>
                $confirmationMadeByAuthorizedHuman,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'governance_decision_confirmation_completed' =>
                $confirmationCompleted,

            'confirmation_authority_condition_count' =>
                count($confirmationAuthorityConditions),

            'confirmation_authority_restriction_count' =>
                count($confirmationAuthorityRestrictions),

            'conditions' =>
                $confirmationAuthorityConditions,

            'restrictions' =>
                $confirmationAuthorityRestrictions,
        ];

        /*
        |--------------------------------------------------------------------------
        | Controlled Activation Context
        |--------------------------------------------------------------------------
        */

        $controlledActivationContext = [
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

            'blocking_activation_conditions' =>
                $blockingActivationConditionCount,

            'blocking_activation_evidence_items' =>
                $blockingActivationEvidenceCount,

            'material_restrictions' =>
                $materialRestrictionCount,

            'confirmation_completed' =>
                $confirmationCompleted,

            'confirmation_attribution_complete' =>
                $confirmationAttributionComplete,

            'controlled_activation_blocked' =>
                $controlledActivationBlocked,

            'automatic_activation_allowed' =>
                (bool) $confirmation->automatic_activation_allowed,

            'automatic_execution_allowed' =>
                (bool) $confirmation->automatic_execution_allowed,

            'authorized_human_activation_required' =>
                (bool) $confirmation->authorized_human_activation_required,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $conditionRestrictionFindings = [
            "Strategic plan governance decision confirmation condition and restriction intelligence is based on confirmation record {$confirmation->id}.",

            "{$totalConditions} confirmation condition(s) are represented.",

            "{$openConditionCount} confirmation condition(s) remain open.",

            "{$blockingConfirmationConditionCount} confirmation condition(s) currently block completed governance confirmation.",

            "{$blockingActivationConditionCount} confirmation condition(s) currently block controlled activation.",

            "{$constrainingConditionCount} confirmation condition(s) constrain activation without directly blocking governance confirmation.",

            "{$resolvedConditionCount} confirmation condition(s) are currently resolved.",

            "{$criticalConditionCount} critical confirmation condition(s) remain open.",

            count($highConditions)
                ." high confirmation condition(s) remain open.",

            count($moderateConditions)
                ." moderate confirmation condition(s) remain open.",

            "{$totalRestrictions} confirmation restriction(s) are represented.",

            "{$materialRestrictionCount} material confirmation restriction(s) remain active.",

            "{$criticalRestrictionCount} critical confirmation restriction(s) remain active.",

            count($highRestrictions)
                ." high confirmation restriction(s) remain active.",

            "{$outstandingEvidenceCount} confirmation evidence requirement(s) remain outstanding.",

            "{$blockingConfirmationEvidenceCount} evidence requirement(s) currently block governance confirmation.",

            "{$blockingActivationEvidenceCount} evidence requirement(s) currently block controlled activation.",

            'Current dominant confirmation condition is '
                .($dominantConfirmationCondition['condition_code'] ?? 'NONE').'.',

            'Current dominant confirmation restriction is '
                .($dominantConfirmationRestriction['restriction_code'] ?? 'NONE').'.',

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

            'Current confirmation condition/restriction state is '
                .$conditionRestrictionState.'.',

            'Source final governance decision recorded is '
                .($sourceFinalGovernanceDecisionRecorded ? 'YES' : 'NO').'.',

            'Source decision made by explicitly authorized human is '
                .($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO').'.',

            'Governance confirmation decision recorded is '
                .($governanceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Governance confirmation made by authorized human is '
                .($confirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Governance confirmation completed is '
                .($confirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorized is '
                .($controlledActivationAuthorized ? 'YES' : 'NO').'.',

            'Governance confirmation progression blocked is '
                .($confirmationProgressionBlocked ? 'YES' : 'NO').'.',

            'Controlled activation progression blocked is '
                .($controlledActivationBlocked ? 'YES' : 'NO').'.',

            'Confirmation condition and restriction intelligence remains advisory and does not resolve, waive, remove, confirm, authorize, activate, or execute any governance action.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantConfirmationCondition) {
            $managementPriorities[] =
                'Address dominant confirmation condition '
                .$dominantConfirmationCondition['condition_code']
                .' through explicitly authorized human governance.';
        }

        if ($dominantConfirmationRestriction) {
            $managementPriorities[] =
                'Address dominant confirmation restriction '
                .$dominantConfirmationRestriction['restriction_code']
                .' through established authorized-human governance.';
        }

        if (!$sourceFinalGovernanceDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human final strategic plan governance decision before governance confirmation progression.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure source final governance decision attribution to an explicitly authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete required source governance validation through authorized human governance before unrestricted confirmation progression.';
        }

        if ($blockingConfirmationConditionCount > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConfirmationConditionCount} confirmation-blocking condition(s) before completed governance confirmation.";
        }

        if ($blockingActivationConditionCount > 0) {
            $managementPriorities[] =
                "Maintain controlled-activation prohibition while {$blockingActivationConditionCount} activation-blocking confirmation condition(s) remain.";
        }

        if ($materialRestrictionCount > 0) {
            $managementPriorities[] =
                "Maintain explicit authorized-human governance treatment for {$materialRestrictionCount} material confirmation restriction(s).";
        }

        if ($outstandingEvidenceCount > 0) {
            $managementPriorities[] =
                "Address {$outstandingEvidenceCount} outstanding confirmation evidence requirement(s) through authorized human evidence review.";
        }

        if (!$confirmationAttributionComplete) {
            $managementPriorities[] =
                'Ensure any completed governance confirmation remains attributable to an identified authorized human governance confirmer.';
        }

        if (!$confirmationCompleted) {
            $managementPriorities[] =
                'Keep governance confirmation incomplete until an explicitly authorized human confirmation decision and attribution are properly recorded.';
        }

        if (!$controlledActivationAuthorized) {
            $managementPriorities[] =
                'Keep controlled activation unauthorized until separate authorized-human activation authority is explicitly established.';
        }

        if ($confirmationRiskScore >= 70) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while confirmation risk remains high or critical.';
        }

        $managementPriorities[] =
            'Keep confirmation condition/restriction intelligence separate from governance-confirmation authority and controlled-activation authority.';

        $managementPriorities[] =
            'Do not interpret condition classification, restriction severity, resolution scores, pressure scores, readiness, risk, or evidence state as authority to confirm or activate the strategic plan.';

        $managementPriorities[] =
            'Preserve human governance authority, source-decision attribution, validation attribution, confirmation attribution, evidence quality, traceability, and controlled-activation authority separation throughout Step 68.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_CONDITION_RESTRICTION_INTELLIGENCE_AVAILABLE',

            /*
            |--------------------------------------------------------------------------
            | Identity
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
            | Main Intelligence State
            |--------------------------------------------------------------------------
            */

            'condition_restriction_state' => [
                'state' =>
                    $conditionRestrictionState,

                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditionCount,

                'resolved_conditions' =>
                    $resolvedConditionCount,

                'blocking_confirmation_conditions' =>
                    $blockingConfirmationConditionCount,

                'blocking_activation_conditions' =>
                    $blockingActivationConditionCount,

                'constraining_conditions' =>
                    $constrainingConditionCount,

                'critical_open_conditions' =>
                    $criticalConditionCount,

                'total_restrictions' =>
                    $totalRestrictions,

                'material_restrictions' =>
                    $materialRestrictionCount,

                'critical_restrictions' =>
                    $criticalRestrictionCount,

                'outstanding_evidence_items' =>
                    $outstandingEvidenceCount,

                'blocking_confirmation_evidence_items' =>
                    $blockingConfirmationEvidenceCount,

                'blocking_activation_evidence_items' =>
                    $blockingActivationEvidenceCount,

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

                'confirmation_progression_blocked' =>
                    $confirmationProgressionBlocked,

                'controlled_activation_blocked' =>
                    $controlledActivationBlocked,

                'human_governance_resolution_required' =>
                    $confirmationProgressionBlocked
                    || $controlledActivationBlocked,

                'human_management_attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'source_final_governance_decision_recorded' =>
                    $sourceFinalGovernanceDecisionRecorded,

                'source_decision_made_by_authorized_human' =>
                    $sourceDecisionMadeByAuthorizedHuman,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'governance_confirmation_decision_recorded' =>
                    $governanceConfirmationDecisionRecorded,

                'confirmation_made_by_authorized_human' =>
                    $confirmationMadeByAuthorizedHuman,

                'governance_decision_confirmation_completed' =>
                    $confirmationCompleted,

                'controlled_activation_authorized' =>
                    $controlledActivationAuthorized,
            ],

            /*
            |--------------------------------------------------------------------------
            | Summaries
            |--------------------------------------------------------------------------
            */

            'condition_summary' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditionCount,

                'resolved_conditions' =>
                    $resolvedConditionCount,

                'blocking_confirmation_conditions' =>
                    $blockingConfirmationConditionCount,

                'blocking_activation_conditions' =>
                    $blockingActivationConditionCount,

                'constraining_conditions' =>
                    $constrainingConditionCount,

                'critical_open_conditions' =>
                    $criticalConditionCount,

                'high_open_conditions' =>
                    count($highConditions),

                'moderate_open_conditions' =>
                    count($moderateConditions),

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'condition_pressure_score' =>
                    $conditionPressureScore,
            ],

            'restriction_summary' => [
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

            'evidence_summary' => [
                'total_evidence_items' =>
                    count($evidenceAnalysis),

                'validated_evidence_items' =>
                    count($validatedEvidenceItems),

                'outstanding_evidence_items' =>
                    $outstandingEvidenceCount,

                'blocking_confirmation_evidence_items' =>
                    $blockingConfirmationEvidenceCount,

                'blocking_activation_evidence_items' =>
                    $blockingActivationEvidenceCount,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidenceCount,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Dominant Barriers
            |--------------------------------------------------------------------------
            */

            'dominant_confirmation_condition' =>
                $dominantConfirmationCondition,

            'dominant_confirmation_restriction' =>
                $dominantConfirmationRestriction,

            /*
            |--------------------------------------------------------------------------
            | Classified Conditions
            |--------------------------------------------------------------------------
            */

            'blocking_confirmation_conditions' =>
                $blockingConfirmationConditions,

            'blocking_activation_conditions' =>
                $blockingActivationConditions,

            'constraining_confirmation_conditions' =>
                $constrainingConfirmationConditions,

            'resolved_confirmation_conditions' =>
                $resolvedConditions,

            'critical_confirmation_conditions' =>
                $criticalConditions,

            /*
            |--------------------------------------------------------------------------
            | Classified Restrictions
            |--------------------------------------------------------------------------
            */

            'material_confirmation_restrictions' =>
                $materialRestrictions,

            'critical_confirmation_restrictions' =>
                $criticalRestrictions,

            /*
            |--------------------------------------------------------------------------
            | Detailed Analysis
            |--------------------------------------------------------------------------
            */

            'condition_analysis' =>
                $conditionAnalysis,

            'restriction_analysis' =>
                $restrictionAnalysis,

            'evidence_analysis' =>
                $evidenceAnalysis,

            'validated_evidence' =>
                $validatedEvidenceItems,

            'outstanding_confirmation_evidence' =>
                $outstandingEvidenceItems,

            'blocking_confirmation_evidence' =>
                $blockingConfirmationEvidence,

            'blocking_activation_evidence' =>
                $blockingActivationEvidence,

            /*
            |--------------------------------------------------------------------------
            | Specialized Governance Contexts
            |--------------------------------------------------------------------------
            */

            'source_final_governance_decision_context' =>
                $sourceDecisionContext,

            'governance_validation_barrier_context' =>
                $governanceValidationBarrierContext,

            'risk_context' =>
                $riskContext,

            'confirmation_authority_context' =>
                $confirmationAuthorityContext,

            'controlled_activation_context' =>
                $controlledActivationContext,

            /*
            |--------------------------------------------------------------------------
            | Step 68.3 Context
            |--------------------------------------------------------------------------
            */

            'confirmation_state_context' =>
                $confirmationState,

            'state_condition_context' =>
                $stateConditionContext,

            'state_restriction_context' =>
                $stateRestrictionContext,

            'state_evidence_context' =>
                $stateEvidenceContext,

            'confirmation_attribution_context' =>
                $confirmationAttributionContext,

            'activation_context' =>
                $activationContext,

            'completion_context' =>
                $completionContext,

            /*
            |--------------------------------------------------------------------------
            | Upstream Context
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
            | Findings / Priorities
            |--------------------------------------------------------------------------
            */

            'condition_restriction_findings' =>
                $conditionRestrictionFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            /*
            |--------------------------------------------------------------------------
            | Step 68.4 Guardrails
            |--------------------------------------------------------------------------
            */

            'confirmation_condition_restriction_guardrails' =>
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

        $conditionResolved = in_array(
            $status,
            [
                'RESOLVED',
                'CLOSED',
                'SATISFIED',
                'COMPLETED',
            ],
            true
        );

        $conditionOpen =
            !$conditionResolved;

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

                'condition_type_normalized' =>
                    strtoupper(
                        (string) ($condition['condition_type'] ?? 'UNKNOWN')
                    ),
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
                        [
                            'CRITICAL',
                            'HIGH',
                            'MATERIAL',
                        ],
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

                'restriction_type_normalized' =>
                    strtoupper(
                        (string) (
                            $restriction['restriction_type']
                            ?? 'UNKNOWN'
                        )
                    ),
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
        $status = strtoupper(
            (string) ($evidence['evidence_status'] ?? '')
        );

        $available =
            ($evidence['evidence_available'] ?? false) === true
            || in_array(
                $status,
                [
                    'AVAILABLE',
                    'VALIDATED',
                    'SATISFIED',
                ],
                true
            );

        $validated =
            ($evidence['evidence_validated'] ?? false) === true
            || in_array(
                $status,
                [
                    'VALIDATED',
                    'SATISFIED',
                ],
                true
            );

        $outstanding =
            ($evidence['evidence_outstanding'] ?? false) === true
            || (
                !$available
                && !$validated
            );

        $blocksGovernanceConfirmation =
            $outstanding
            && (
                ($evidence['blocks_governance_confirmation'] ?? false)
                === true
                || ($evidence['blocks_confirmation'] ?? false) === true
                || ($evidence['blocks_governance_validation'] ?? false) === true
            );

        $blocksControlledActivation =
            $outstanding
            && (
                ($evidence['blocks_controlled_activation'] ?? false)
                === true
                || $blocksGovernanceConfirmation
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

                'blocks_governance_confirmation' =>
                    $blocksGovernanceConfirmation,

                'blocks_controlled_activation' =>
                    $blocksControlledActivation,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pressure Scoring
    |--------------------------------------------------------------------------
    */

    private function calculateConditionPressure(
        array $conditions
    ): float {
        if (empty($conditions)) {
            return 0.0;
        }

        $openConditions = array_values(
            array_filter(
                $conditions,
                fn ($condition) =>
                    ($condition['condition_open'] ?? false) === true
            )
        );

        if (empty($openConditions)) {
            return 0.0;
        }

        $weight = collect($openConditions)
            ->avg(
                fn ($condition) =>
                    (float) ($condition['severity_weight'] ?? 0)
            );

        return round(
            max(0, min(100, (float) $weight)),
            2
        );
    }

    private function calculateRestrictionPressure(
        array $restrictions
    ): float {
        if (empty($restrictions)) {
            return 0.0;
        }

        $weight = collect($restrictions)
            ->avg(
                fn ($restriction) =>
                    (float) ($restriction['severity_weight'] ?? 0)
            );

        return round(
            max(0, min(100, (float) $weight)),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dominant Condition / Restriction
    |--------------------------------------------------------------------------
    */

    private function dominantCondition(
        array $conditions
    ): ?array {
        if (empty($conditions)) {
            return null;
        }

        usort(
            $conditions,
            function ($a, $b) {
                $severityComparison =
                    ($b['severity_weight'] ?? 0)
                    <=> ($a['severity_weight'] ?? 0);

                if ($severityComparison !== 0) {
                    return $severityComparison;
                }

                $aBlocksConfirmation =
                    ($a['blocks_governance_confirmation'] ?? false)
                    ? 1
                    : 0;

                $bBlocksConfirmation =
                    ($b['blocks_governance_confirmation'] ?? false)
                    ? 1
                    : 0;

                return $bBlocksConfirmation
                    <=> $aBlocksConfirmation;
            }
        );

        return $conditions[0] ?? null;
    }

    private function dominantRestriction(
        array $restrictions
    ): ?array {
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
    | Condition / Restriction State
    |--------------------------------------------------------------------------
    */

    private function determineConditionRestrictionState(
        int $criticalConditions,
        int $criticalRestrictions,
        int $blockingConfirmationConditions,
        int $blockingActivationConditions,
        int $materialRestrictions,
        int $outstandingEvidence,
        bool $confirmationCompleted,
        bool $controlledActivationAuthorized
    ): string {
        if (
            $controlledActivationAuthorized
            && (
                !$confirmationCompleted
                || $blockingActivationConditions > 0
                || $criticalConditions > 0
                || $criticalRestrictions > 0
                || $materialRestrictions > 0
            )
        ) {
            return 'CONTROLLED_ACTIVATION_AUTHORIZATION_INTEGRITY_CONFLICT';
        }

        if (
            $criticalConditions > 0
            || $criticalRestrictions > 0
        ) {
            return 'CRITICAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS';
        }

        if ($blockingConfirmationConditions > 0) {
            return 'MATERIAL_BLOCKING_GOVERNANCE_CONFIRMATION_REQUIREMENTS';
        }

        if ($blockingActivationConditions > 0) {
            return 'CONFIRMATION_REVIEW_AVAILABLE_ACTIVATION_REMAINS_BLOCKED';
        }

        if ($materialRestrictions > 0) {
            return 'MATERIAL_GOVERNANCE_CONFIRMATION_RESTRICTIONS_REMAIN';
        }

        if ($outstandingEvidence > 0) {
            return 'GOVERNANCE_CONFIRMATION_EVIDENCE_REQUIREMENTS_REMAIN';
        }

        if (!$confirmationCompleted) {
            return 'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        }

        if (!$controlledActivationAuthorized) {
            return 'GOVERNANCE_CONFIRMATION_COMPLETED_ACTIVATION_NOT_AUTHORIZED';
        }

        return 'GOVERNANCE_CONFIRMATION_AND_CONTROLLED_ACTIVATION_CONTROLS_SATISFIED';
    }

    /*
    |--------------------------------------------------------------------------
    | Management Attention
    |--------------------------------------------------------------------------
    */

    private function determineManagementAttentionLevel(
        bool $immediateInterventionRequired,
        float $riskScore,
        int $blockingConfirmationConditions,
        int $blockingActivationConditions,
        int $materialRestrictions,
        bool $confirmationCompleted,
        bool $controlledActivationAuthorized
    ): string {
        if ($immediateInterventionRequired) {
            return 'CRITICAL';
        }

        if (
            $riskScore >= 70
            || $blockingConfirmationConditions > 0
            || $materialRestrictions > 0
        ) {
            return 'HIGH';
        }

        if (
            $blockingActivationConditions > 0
            || !$confirmationCompleted
            || !$controlledActivationAuthorized
            || $riskScore >= 50
        ) {
            return 'MODERATE';
        }

        return 'LOW';
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function normalizeArray(
        mixed $value
    ): array {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode(
                $value,
                true
            );

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
    }

    private function score(
        mixed $value
    ): float {
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
    | Step 68.4 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'governance_confirmation_condition_restriction_intelligence_enabled' =>
                true,

            'condition_restriction_intelligence_is_governance_confirmation' =>
                false,

            'condition_restriction_intelligence_makes_governance_confirmation_decision' =>
                false,

            'condition_restriction_intelligence_records_governance_confirmation_decision' =>
                false,

            'condition_restriction_intelligence_completes_governance_confirmation' =>
                false,

            'condition_restriction_intelligence_makes_final_governance_decision' =>
                false,

            'condition_restriction_intelligence_records_final_governance_decision' =>
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

            'condition_restriction_intelligence_authorizes_controlled_activation' =>
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

            'condition_restriction_intelligence_changes_source_final_governance_decision' =>
                false,

            'condition_restriction_intelligence_changes_governance_validation' =>
                false,

            'condition_restriction_intelligence_changes_confirmation_status' =>
                false,

            'condition_restriction_intelligence_changes_confirmation_decision' =>
                false,

            'condition_restriction_intelligence_changes_confirmation_outcome' =>
                false,

            'condition_restriction_intelligence_changes_controlled_activation_status' =>
                false,

            'condition_restriction_intelligence_changes_plan_status' =>
                false,

            'condition_restriction_intelligence_changes_action_state' =>
                false,

            'condition_restriction_intelligence_changes_priority' =>
                false,

            'condition_restriction_intelligence_changes_eligibility' =>
                false,

            'condition_score_authorizes_confirmation' =>
                false,

            'restriction_score_authorizes_confirmation' =>
                false,

            'condition_pressure_score_authorizes_confirmation' =>
                false,

            'restriction_pressure_score_authorizes_confirmation' =>
                false,

            'combined_pressure_score_authorizes_confirmation' =>
                false,

            'condition_resolution_score_authorizes_confirmation' =>
                false,

            'evidence_resolution_score_authorizes_confirmation' =>
                false,

            'combined_resolution_score_authorizes_confirmation' =>
                false,

            'condition_score_authorizes_activation' =>
                false,

            'restriction_score_authorizes_activation' =>
                false,

            'condition_pressure_score_authorizes_activation' =>
                false,

            'restriction_pressure_score_authorizes_activation' =>
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

            'condition_restriction_intelligence_overrides_final_governance_decision' =>
                false,

            'condition_restriction_intelligence_overrides_confirmation_authority' =>
                false,

            'condition_restriction_intelligence_overrides_activation_authority' =>
                false,

            'condition_restriction_intelligence_overrides_evidence_requirements' =>
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
                'Step 68.4 strategic plan governance decision confirmation condition and restriction intelligence evaluates confirmation conditions, restrictions, source final-governance-decision dependencies, governance-validation barriers, confirmation authority requirements, controlled-activation barriers, evidence state, resolution state, risk, condition pressure, restriction pressure, and management attention requirements for explicitly authorized human governance. Classification of a condition or restriction as blocking, constraining, resolved, high, critical, material, or advisory does not itself resolve, waive, remove, downgrade, satisfy, confirm, authorize, or activate anything. The intelligence does not make, record, or complete governance confirmation; make or change the final governance decision; approve, reject, conditionally approve, defer, or accept governance risk; authorize controlled activation; activate the strategic plan; resolve conditions or dependencies; remove restrictions; validate evidence; alter upstream governance-validation state; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Governance confirmation, final governance decision, and controlled activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}