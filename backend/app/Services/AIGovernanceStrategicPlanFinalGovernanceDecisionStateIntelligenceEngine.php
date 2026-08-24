<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionStateIntelligenceEngine
{
    public function analyze(?int $finalGovernanceDecisionId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resolve Final Governance Decision Record
        |--------------------------------------------------------------------------
        */

        $decision = $finalGovernanceDecisionId
            ? AIGovernanceStrategicPlanFinalGovernanceDecision::find($finalGovernanceDecisionId)
            : AIGovernanceStrategicPlanFinalGovernanceDecision::latest('id')->first();

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_AVAILABLE',
                'message' => 'No strategic plan final governance decision record is available for state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Collections
        |--------------------------------------------------------------------------
        */

        $conditions = $decision->decision_conditions ?? [];
        $validatedEvidence = $decision->validated_evidence ?? [];
        $restrictions = $decision->decision_restrictions ?? [];
        $reviewContext = $decision->review_context ?? [];
        $validationContext = $decision->validation_context ?? [];
        $governanceContext = $decision->governance_context ?? [];
        $sourceContext = $decision->source_context ?? [];

        if (!is_array($conditions)) {
            $conditions = [];
        }

        if (!is_array($validatedEvidence)) {
            $validatedEvidence = [];
        }

        if (!is_array($restrictions)) {
            $restrictions = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision State
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionRecorded =
            !empty($decision->final_governance_decision);

        $decisionMadeByAuthorizedHuman =
            (bool) $decision->decision_made_by_authorized_human;

        $confirmationCompleted =
            (bool) $decision->final_governance_confirmation_completed;

        $sourceFinalHumanDecisionRecorded =
            (bool) $decision->source_final_human_decision_recorded;

        $sourceDecisionMadeByAuthorizedHuman =
            (bool) $decision->source_decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $decision->source_governance_validation_completed;

        /*
        |--------------------------------------------------------------------------
        | Condition Analysis
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = [];

        foreach ($conditions as $condition) {
            $priority = strtoupper(
                (string) ($condition['priority_level'] ?? 'MODERATE')
            );

            $status = strtoupper(
                (string) ($condition['condition_status'] ?? 'OPEN')
            );

            $isOpen = !in_array(
                $status,
                ['RESOLVED', 'CLOSED', 'SATISFIED', 'COMPLETED'],
                true
            );

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'LOW' => 25.0,
                default => 25.0,
            };

            $classification = match (true) {
                $priority === 'CRITICAL' => 'BLOCKING',
                $priority === 'HIGH' => 'BLOCKING',
                $priority === 'MODERATE' => 'CONSTRAINING',
                default => 'ADVISORY',
            };

            $conditionAnalysis[] = array_merge(
                $condition,
                [
                    'condition_open' => $isOpen,
                    'condition_classification' => $classification,
                    'severity_weight' => $severityWeight,
                    'blocks_final_governance_decision' =>
                        $isOpen && $classification === 'BLOCKING',
                    'constrains_final_governance_decision' =>
                        $isOpen && $classification === 'CONSTRAINING',
                    'requires_authorized_human_resolution' =>
                        (bool) (
                            $condition['requires_authorized_human_resolution']
                            ?? true
                        ),
                ]
            );
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
                    ($condition['condition_open'] ?? true) === false
            )
        );

        $blockingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['blocks_final_governance_decision'] ?? false)
                    === true
            )
        );

        $constrainingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['constrains_final_governance_decision'] ?? false)
                    === true
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
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'MODERATE'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Restriction Analysis
        |--------------------------------------------------------------------------
        */

        $restrictionAnalysis = [];

        foreach ($restrictions as $restriction) {
            $severity = strtoupper(
                (string) ($restriction['severity'] ?? 'MODERATE')
            );

            $severityWeight = match ($severity) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'LOW' => 25.0,
                default => 25.0,
            };

            $restrictionAnalysis[] = array_merge(
                $restriction,
                [
                    'severity_weight' => $severityWeight,
                    'material_restriction' =>
                        in_array(
                            $severity,
                            ['CRITICAL', 'HIGH'],
                            true
                        ),
                ]
            );
        }

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
                    strtoupper(
                        (string) ($restriction['severity'] ?? '')
                    ) === 'CRITICAL'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Dominant Condition
        |--------------------------------------------------------------------------
        */

        $dominantCondition = null;

        if (!empty($openConditions)) {
            usort(
                $openConditions,
                fn ($a, $b) =>
                    ($b['severity_weight'] ?? 0)
                    <=>
                    ($a['severity_weight'] ?? 0)
            );

            $dominantCondition = $openConditions[0];
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Restriction
        |--------------------------------------------------------------------------
        */

        $dominantRestriction = null;

        if (!empty($restrictionAnalysis)) {
            $sortedRestrictions = $restrictionAnalysis;

            usort(
                $sortedRestrictions,
                fn ($a, $b) =>
                    ($b['severity_weight'] ?? 0)
                    <=>
                    ($a['severity_weight'] ?? 0)
            );

            $dominantRestriction = $sortedRestrictions[0];
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence State
        |--------------------------------------------------------------------------
        */

        $validatedEvidenceCount = count($validatedEvidence);

        $evidenceReadinessScore =
            (float) (
                $decision->evidence_resolution_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Completion Components
        |--------------------------------------------------------------------------
        */

        $sourceHumanDecisionScore =
            (
                $sourceFinalHumanDecisionRecorded
                && $sourceDecisionMadeByAuthorizedHuman
            )
                ? 100.0
                : 0.0;

        $sourceValidationScore =
            $sourceGovernanceValidationCompleted
                ? 100.0
                : 0.0;

        $finalDecisionScore =
            (
                $finalGovernanceDecisionRecorded
                && $decisionMadeByAuthorizedHuman
            )
                ? 100.0
                : 0.0;

        $confirmationScore =
            $confirmationCompleted
                ? 100.0
                : 0.0;

        $conditionResolutionScore =
            (float) (
                $decision->condition_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $decision->combined_resolution_score
                ?? 0
            );

        $finalGovernanceCompletionScore = round(
            (
                $sourceHumanDecisionScore
                + $sourceValidationScore
                + $finalDecisionScore
                + $confirmationScore
                + $conditionResolutionScore
                + $evidenceReadinessScore
                + $combinedResolutionScore
            ) / 7,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Decision State Classification
        |--------------------------------------------------------------------------
        */

        if (
            $finalGovernanceDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && $confirmationCompleted
        ) {
            $strategicFinalGovernanceDecisionState =
                'FINAL_GOVERNANCE_DECISION_CONFIRMED';
        } elseif (
            $finalGovernanceDecisionRecorded
            && $decisionMadeByAuthorizedHuman
        ) {
            $strategicFinalGovernanceDecisionState =
                'FINAL_GOVERNANCE_DECISION_RECORDED_PENDING_CONFIRMATION';
        } elseif (
            count($criticalOpenConditions) > 0
            || count($criticalRestrictions) > 0
        ) {
            $strategicFinalGovernanceDecisionState =
                'FINAL_GOVERNANCE_DECISION_BLOCKED_BY_CRITICAL_REQUIREMENTS';
        } elseif (
            count($blockingConditions) > 0
            || count($materialRestrictions) > 0
        ) {
            $strategicFinalGovernanceDecisionState =
                'PENDING_FINAL_GOVERNANCE_DECISION_WITH_MATERIAL_REQUIREMENTS';
        } else {
            $strategicFinalGovernanceDecisionState =
                'PENDING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        }

        /*
        |--------------------------------------------------------------------------
        | Readiness Classification
        |--------------------------------------------------------------------------
        */

        $readinessScore =
            (float) (
                $decision->final_decision_readiness_score
                ?? 0
            );

        if ($readinessScore >= 85) {
            $finalGovernanceDecisionReadiness =
                'HIGH_FINAL_GOVERNANCE_DECISION_READINESS';
        } elseif ($readinessScore >= 70) {
            $finalGovernanceDecisionReadiness =
                'MODERATE_FINAL_GOVERNANCE_DECISION_READINESS';
        } elseif ($readinessScore >= 50) {
            $finalGovernanceDecisionReadiness =
                'LIMITED_FINAL_GOVERNANCE_DECISION_READINESS';
        } elseif ($readinessScore >= 25) {
            $finalGovernanceDecisionReadiness =
                'VERY_LIMITED_FINAL_GOVERNANCE_DECISION_READINESS';
        } else {
            $finalGovernanceDecisionReadiness =
                'EXTREMELY_LIMITED_FINAL_GOVERNANCE_DECISION_READINESS';
        }

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        if (
            $readinessScore >= 85
            && count($blockingConditions) === 0
            && count($materialRestrictions) === 0
        ) {
            $finalGovernanceDecisionConfidence = 'HIGH';
        } elseif ($readinessScore >= 70) {
            $finalGovernanceDecisionConfidence = 'MODERATE';
        } elseif ($readinessScore >= 50) {
            $finalGovernanceDecisionConfidence = 'LIMITED';
        } elseif ($readinessScore >= 25) {
            $finalGovernanceDecisionConfidence = 'VERY_LIMITED';
        } else {
            $finalGovernanceDecisionConfidence = 'EXTREMELY_LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Human Governance Action State
        |--------------------------------------------------------------------------
        */

        if ($finalGovernanceDecisionRecorded && $confirmationCompleted) {
            $humanGovernanceActionState =
                'FINAL_GOVERNANCE_DECISION_COMPLETED';
        } elseif (!$sourceFinalHumanDecisionRecorded) {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_FINAL_STRATEGIC_PLAN_DECISION_REQUIRED';
        } elseif (!$sourceGovernanceValidationCompleted) {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION_REQUIRED';
        } elseif (count($blockingConditions) > 0) {
            $humanGovernanceActionState =
                'MATERIAL_FINAL_GOVERNANCE_REQUIREMENTS_REQUIRE_RESOLUTION';
        } else {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION_REQUIRED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            count($criticalOpenConditions) > 0
            || count($criticalRestrictions) > 0;

        $humanManagementAttentionRequired =
            $immediateHumanInterventionRequired
            || count($blockingConditions) > 0
            || count($materialRestrictions) > 0
            || ((float) $decision->final_decision_risk_score >= 75)
            || !$finalGovernanceDecisionRecorded;

        $humanManagementAttentionLevel =
            $immediateHumanInterventionRequired
                ? 'CRITICAL'
                : (
                    $humanManagementAttentionRequired
                        ? 'HIGH'
                        : 'CONTROLLED'
                );

        /*
        |--------------------------------------------------------------------------
        | Final Decision State
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionState = [
            'final_governance_decision_status' =>
                $decision->final_governance_decision_status,

            'final_governance_decision_mode' =>
                $decision->final_governance_decision_mode,

            'prepared_decision' =>
                $decision->prepared_decision,

            'source_human_decision' =>
                $decision->source_human_decision,

            'source_governance_validation_decision' =>
                $decision->source_governance_validation_decision,

            'final_governance_decision' =>
                $decision->final_governance_decision,

            'final_governance_outcome' =>
                $decision->final_governance_outcome,

            'final_governance_outcome_status' =>
                $decision->final_governance_outcome_status,

            'strategic_final_governance_decision_state' =>
                $strategicFinalGovernanceDecisionState,

            'final_governance_decision_readiness' =>
                $finalGovernanceDecisionReadiness,

            'final_governance_decision_readiness_score' =>
                $readinessScore,

            'final_governance_decision_confidence' =>
                $finalGovernanceDecisionConfidence,

            'human_governance_action_state' =>
                $humanGovernanceActionState,

            'final_decision_risk_level' =>
                $decision->final_decision_risk_level,

            'final_decision_risk_score' =>
                (float) $decision->final_decision_risk_score,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'source_final_human_decision_recorded' =>
                $sourceFinalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $sourceDecisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'final_governance_decision_recorded' =>
                $finalGovernanceDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'final_governance_confirmation_completed' =>
                $confirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition Context
        |--------------------------------------------------------------------------
        */

        $conditionContext = [
            'total_conditions' =>
                count($conditionAnalysis),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'critical_open_conditions' =>
                count($criticalOpenConditions),

            'high_open_conditions' =>
                count($highOpenConditions),

            'moderate_open_conditions' =>
                count($moderateOpenConditions),

            'blocking_conditions' =>
                count($blockingConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'dominant_open_condition' =>
                $dominantCondition,
        ];

        /*
        |--------------------------------------------------------------------------
        | Restriction Context
        |--------------------------------------------------------------------------
        */

        $restrictionContext = [
            'total_restrictions' =>
                count($restrictionAnalysis),

            'material_restrictions' =>
                count($materialRestrictions),

            'critical_restrictions' =>
                count($criticalRestrictions),

            'dominant_restriction' =>
                $dominantRestriction,
        ];

        /*
        |--------------------------------------------------------------------------
        | Evidence Context
        |--------------------------------------------------------------------------
        */

        $evidenceContext = [
            'validated_evidence_items' =>
                $validatedEvidenceCount,

            'evidence_resolution_score' =>
                $evidenceReadinessScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Context
        |--------------------------------------------------------------------------
        */

        $authorizationContext = [
            'decided_by' =>
                $decision->decided_by,

            'decider_role' =>
                $decision->decider_role,

            'decided_at' =>
                $decision->decided_at,

            'authorized_final_governance_decider_identified' =>
                !empty($decision->decided_by),

            'authorized_decider_role_available' =>
                !empty($decision->decider_role),

            'decision_timestamp_available' =>
                !empty($decision->decided_at),

            'final_governance_decision_recorded' =>
                $finalGovernanceDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,
        ];

        /*
        |--------------------------------------------------------------------------
        | Confirmation Context
        |--------------------------------------------------------------------------
        */

        $confirmationContext = [
            'confirmed_by' =>
                $decision->confirmed_by,

            'confirmer_role' =>
                $decision->confirmer_role,

            'confirmed_at' =>
                $decision->confirmed_at,

            'confirmer_identified' =>
                !empty($decision->confirmed_by),

            'confirmer_role_available' =>
                !empty($decision->confirmer_role),

            'confirmation_timestamp_available' =>
                !empty($decision->confirmed_at),

            'final_governance_confirmation_completed' =>
                $confirmationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Completion Context
        |--------------------------------------------------------------------------
        */

        $completionContext = [
            'source_human_decision_score' =>
                $sourceHumanDecisionScore,

            'source_governance_validation_score' =>
                $sourceValidationScore,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceReadinessScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,

            'final_governance_decision_score' =>
                $finalDecisionScore,

            'confirmation_score' =>
                $confirmationScore,

            'final_governance_completion_score' =>
                $finalGovernanceCompletionScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Strategic plan final governance decision state intelligence is based on final governance decision record {$decision->id}.",

            'Current final governance decision status is '
                .($decision->final_governance_decision_status ?? 'UNKNOWN')
                .'.',

            'Current prepared strategic plan decision is '
                .($decision->prepared_decision ?? 'UNKNOWN')
                .'.',

            'Current strategic final governance decision state is '
                .$strategicFinalGovernanceDecisionState
                .'.',

            'Current final governance decision readiness is '
                .$finalGovernanceDecisionReadiness
                .' with score '
                .$readinessScore
                .'.',

            'Current final governance decision confidence is '
                .$finalGovernanceDecisionConfidence
                .'.',

            'Current human governance action state is '
                .$humanGovernanceActionState
                .'.',

            count($openConditions)
                .' final governance decision condition(s) remain open.',

            count($blockingConditions)
                .' blocking final governance decision condition(s) remain active.',

            count($constrainingConditions)
                .' constraining final governance decision condition(s) remain active.',

            count($materialRestrictions)
                .' material final governance decision restriction(s) remain active.',

            'Current condition resolution score is '
                .$conditionResolutionScore
                .'.',

            'Current evidence resolution score is '
                .$evidenceReadinessScore
                .'.',

            'Current combined resolution score is '
                .$combinedResolutionScore
                .'.',

            'Current final governance decision risk is '
                .($decision->final_decision_risk_level ?? 'UNKNOWN')
                .' with score '
                .($decision->final_decision_risk_score ?? 0)
                .'.',

            'Source final authorized-human strategic plan decision recorded is '
                .($sourceFinalHumanDecisionRecorded ? 'YES' : 'NO')
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
                .($confirmationCompleted ? 'YES' : 'NO')
                .'.',

            'Final governance decision state intelligence remains informational and does not make, record, confirm, approve, reject, conditionally approve, defer, accept risk, activate, or execute the final strategic plan governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceFinalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human strategic plan decision before final governance decision progression.';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the source strategic plan decision remains explicitly attributable to an authorized human governance decision-maker.';
        }

        if (!$sourceGovernanceValidationCompleted) {
            $managementPriorities[] =
                'Complete required strategic plan governance-validation activity through authorized human governance before final governance progression.';
        }

        if ($dominantCondition) {
            $managementPriorities[] =
                'Address the dominant open final governance decision condition '
                .($dominantCondition['condition_code'] ?? 'UNKNOWN')
                .' through authorized human governance.';
        }

        if (count($blockingConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingConditions)
                .' blocking final governance decision condition(s) before unrestricted final governance progression.';
        }

        if (count($materialRestrictions) > 0) {
            $managementPriorities[] =
                'Address '
                .count($materialRestrictions)
                .' material final governance decision restriction(s) through authorized human governance.';
        }

        if ((float) $decision->final_decision_risk_score >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized human governance oversight while final strategic plan decision risk remains high.';
        }

        if (!$finalGovernanceDecisionRecorded) {
            $managementPriorities[] =
                'Ensure any final governance decision is explicitly made, justified, and documented only by an authorized human governance decision-maker.';
        }

        if (!$confirmationCompleted) {
            $managementPriorities[] =
                'Preserve independent authorized-human confirmation requirements before treating the final governance decision as completed.';
        }

        $managementPriorities[] =
            'Preserve human decision authority, governance-validation authority, evidence quality, traceability, confirmation controls, and execution isolation throughout final governance progression.';

        /*
        |--------------------------------------------------------------------------
        | Return Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_final_governance_decision_id' =>
                $decision->id,

            'final_governance_decision_code' =>
                $decision->final_governance_decision_code,

            'strategic_plan_decision_validation_id' =>
                $decision->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $decision->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $decision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $decision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $decision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $decision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $decision->lifecycle_snapshot_id,

            'decision_scope' =>
                $decision->decision_scope,

            'resident_id' =>
                $decision->resident_id,

            'final_governance_decision_state' =>
                $finalGovernanceDecisionState,

            'condition_context' =>
                $conditionContext,

            'restriction_context' =>
                $restrictionContext,

            'evidence_context' =>
                $evidenceContext,

            'authorization_context' =>
                $authorizationContext,

            'confirmation_context' =>
                $confirmationContext,

            'completion_context' =>
                $completionContext,

            'review_context' =>
                $reviewContext,

            'validation_context' =>
                $validationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'condition_analysis' =>
                $conditionAnalysis,

            'open_decision_conditions' =>
                $openConditions,

            'resolved_decision_conditions' =>
                $resolvedConditions,

            'restriction_analysis' =>
                $restrictionAnalysis,

            'final_governance_decision_findings' =>
                $findings,

            'management_priorities' =>
                array_values(array_unique($managementPriorities)),

            /*
            |--------------------------------------------------------------------------
            | Step 67.3 Guardrails
            |--------------------------------------------------------------------------
            */

            'final_governance_decision_state_guardrails' => [
                'strategic_plan_final_governance_decision_state_intelligence_enabled' =>
                    true,

                'state_intelligence_is_final_governance_decision' =>
                    false,

                'state_intelligence_makes_final_governance_decision' =>
                    false,

                'state_intelligence_records_final_governance_decision' =>
                    false,

                'state_intelligence_confirms_final_governance_decision' =>
                    false,

                'state_intelligence_approves_strategic_plan' =>
                    false,

                'state_intelligence_rejects_strategic_plan' =>
                    false,

                'state_intelligence_conditionally_approves_strategic_plan' =>
                    false,

                'state_intelligence_defers_strategic_plan' =>
                    false,

                'state_intelligence_accepts_governance_risk' =>
                    false,

                'state_intelligence_activates_strategic_plan' =>
                    false,

                'state_intelligence_changes_final_governance_decision_status' =>
                    false,

                'state_intelligence_changes_final_governance_decision' =>
                    false,

                'state_intelligence_changes_final_governance_outcome' =>
                    false,

                'state_intelligence_changes_source_human_decision' =>
                    false,

                'state_intelligence_changes_governance_validation' =>
                    false,

                'state_intelligence_changes_plan_status' =>
                    false,

                'state_intelligence_changes_action_state' =>
                    false,

                'state_intelligence_resolves_conditions' =>
                    false,

                'state_intelligence_resolves_dependencies' =>
                    false,

                'state_intelligence_validates_evidence' =>
                    false,

                'state_intelligence_completes_governance_validation' =>
                    false,

                'state_intelligence_completes_final_confirmation' =>
                    false,

                'readiness_score_authorizes_final_decision' =>
                    false,

                'readiness_score_authorizes_approval' =>
                    false,

                'completion_score_authorizes_final_decision' =>
                    false,

                'completion_score_authorizes_execution' =>
                    false,

                'risk_score_authorizes_final_decision' =>
                    false,

                'state_intelligence_authorizes_ai_change' =>
                    false,

                'state_intelligence_authorizes_execution' =>
                    false,

                'state_intelligence_authorizes_deployment' =>
                    false,

                'state_intelligence_authorizes_rollback' =>
                    false,

                'state_intelligence_authorizes_clinical_action' =>
                    false,

                'state_intelligence_overrides_human_review' =>
                    false,

                'state_intelligence_overrides_final_human_decision' =>
                    false,

                'state_intelligence_overrides_governance_validation' =>
                    false,

                'state_intelligence_overrides_final_confirmation' =>
                    false,

                'state_intelligence_overrides_evidence_requirements' =>
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

                'final_governance_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,

                'message' =>
                    'Strategic plan final governance decision state intelligence evaluates the prepared final-governance-decision record, unresolved decision conditions, restrictions, evidence state, upstream human-decision state, governance-validation state, final-decision readiness, decision risk, authorized-human attribution, confirmation state, completion, and management attention requirements. It does not make, record, or confirm the final governance decision, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, complete governance validation, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final governance decision authority remains reserved exclusively for authorized human governance.',
            ],
        ];
    }
}