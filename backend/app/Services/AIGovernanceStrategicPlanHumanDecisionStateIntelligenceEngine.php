<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanHumanDecisionStateIntelligenceEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $humanDecision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::find($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->first();

        if (!$humanDecision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_HUMAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan human decision record is available for analysis.',
            ];
        }

        $decisionConditions = is_array($humanDecision->decision_conditions)
            ? $humanDecision->decision_conditions
            : [];

        $validatedEvidence = is_array($humanDecision->validated_evidence)
            ? $humanDecision->validated_evidence
            : [];

        $conditionResolutionContext = is_array($humanDecision->condition_resolution_context)
            ? $humanDecision->condition_resolution_context
            : [];

        $evidenceContext = is_array($humanDecision->evidence_context)
            ? $humanDecision->evidence_context
            : [];

        $reviewContext = is_array($humanDecision->review_context)
            ? $humanDecision->review_context
            : [];

        $governanceContext = is_array($humanDecision->governance_context)
            ? $humanDecision->governance_context
            : [];

        $sourceContext = is_array($humanDecision->source_context)
            ? $humanDecision->source_context
            : [];

        /*
        |--------------------------------------------------------------------------
        | Decision Condition Analysis
        |--------------------------------------------------------------------------
        */

        $openConditions = collect($decisionConditions)
            ->filter(fn ($condition) =>
                strtoupper((string) ($condition['condition_status'] ?? 'OPEN')) === 'OPEN'
            )
            ->values();

        $resolvedConditions = collect($decisionConditions)
            ->filter(fn ($condition) =>
                in_array(
                    strtoupper((string) ($condition['condition_status'] ?? '')),
                    ['RESOLVED', 'SATISFIED', 'CLOSED'],
                    true
                )
            )
            ->values();

        $criticalOpenConditions = $openConditions
            ->where('priority_level', 'CRITICAL')
            ->count();

        $highOpenConditions = $openConditions
            ->where('priority_level', 'HIGH')
            ->count();

        $moderateOpenConditions = $openConditions
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisoryOpenConditions = $openConditions
            ->where('priority_level', 'ADVISORY')
            ->count();

        $blockingConditions = (int) (
            $conditionResolutionContext['blocking_conditions']
            ?? $highOpenConditions + $criticalOpenConditions
        );

        $constrainingConditions = (int) (
            $conditionResolutionContext['constraining_conditions']
            ?? $moderateOpenConditions
        );

        $dominantOpenCondition = $openConditions
            ->sortByDesc(function ($condition) {
                return match (strtoupper((string) ($condition['priority_level'] ?? ''))) {
                    'CRITICAL' => 100,
                    'HIGH' => 75,
                    'MODERATE' => 50,
                    'ADVISORY' => 25,
                    default => 0,
                };
            })
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Evidence Analysis
        |--------------------------------------------------------------------------
        */

        $evidenceRequirements = collect(
            $evidenceContext['requirements'] ?? []
        );

        $requiredEvidenceItems = $evidenceRequirements
            ->filter(fn ($evidence) =>
                strtoupper((string) ($evidence['evidence_status'] ?? '')) === 'REQUIRED'
            )
            ->values();

        $availableEvidenceItems = $evidenceRequirements
            ->filter(fn ($evidence) =>
                in_array(
                    strtoupper((string) ($evidence['evidence_status'] ?? '')),
                    ['AVAILABLE', 'VALIDATED', 'SATISFIED'],
                    true
                )
            )
            ->values();

        $outstandingEvidenceItems = (int) (
            $evidenceContext['outstanding_evidence_items']
            ?? $requiredEvidenceItems->count()
        );

        $decisionBlockingEvidenceItems = (int) (
            $evidenceContext['decision_blocking_evidence_items']
            ?? $requiredEvidenceItems
                ->whereIn('priority_level', ['CRITICAL', 'HIGH'])
                ->count()
        );

        $criticalOutstandingEvidenceItems = (int) (
            $evidenceContext['critical_outstanding_evidence_items']
            ?? $requiredEvidenceItems
                ->where('priority_level', 'CRITICAL')
                ->count()
        );

        $highOutstandingEvidenceItems = (int) (
            $evidenceContext['high_outstanding_evidence_items']
            ?? $requiredEvidenceItems
                ->where('priority_level', 'HIGH')
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Human Decision / Validation State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            !empty($humanDecision->final_human_decision);

        $decisionMadeByAuthorizedHuman =
            (bool) $humanDecision->decision_made_by_authorized_human;

        $governanceValidationCompleted =
            (bool) $humanDecision->governance_validation_completed;

        $humanReviewerIdentified =
            !empty($humanDecision->decided_by);

        $validatorIdentified =
            !empty($humanDecision->validated_by);

        $decisionTimestampAvailable =
            !empty($humanDecision->decided_at);

        $validationTimestampAvailable =
            !empty($humanDecision->validated_at);

        /*
        |--------------------------------------------------------------------------
        | Decision Progress Scores
        |--------------------------------------------------------------------------
        */

        $totalConditions = count($decisionConditions);

        $conditionResolutionScore = $totalConditions > 0
            ? round(($resolvedConditions->count() / $totalConditions) * 100, 2)
            : 100.0;

        $totalEvidenceRequirements = $evidenceRequirements->count();

        $evidenceReadinessScore = $totalEvidenceRequirements > 0
            ? round(
                (
                    ($totalEvidenceRequirements - $outstandingEvidenceItems)
                    / $totalEvidenceRequirements
                ) * 100,
                2
            )
            : 100.0;

        $authorizationScore = $decisionMadeByAuthorizedHuman ? 100.0 : 0.0;
        $validationScore = $governanceValidationCompleted ? 100.0 : 0.0;

        $humanDecisionCompletionScore = round(
            (
                ($conditionResolutionScore * 0.30)
                + ($evidenceReadinessScore * 0.25)
                + ($authorizationScore * 0.25)
                + ($validationScore * 0.20)
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Human Decision Readiness Score
        |--------------------------------------------------------------------------
        */

        $approvalEligibility =
            (string) ($humanDecision->approval_eligibility ?? 'UNKNOWN');

        $decisionEligibilityScore =
            (float) ($humanDecision->decision_eligibility_score ?? 0);

        $decisionRiskScore =
            (float) ($humanDecision->decision_risk_score ?? 0);

        $planningReadinessScore =
            (float) ($governanceContext['decision_review_readiness_score'] ?? 0);

        $conditionPressureScore =
            (float) ($conditionResolutionContext['condition_pressure_score'] ?? 0);

        $decisionEvidenceReadinessScore =
            (float) ($conditionResolutionContext['decision_evidence_readiness_score']
                ?? $evidenceReadinessScore);

        $humanDecisionReadinessScore = round(
            max(
                0,
                min(
                    100,
                    (
                        ($decisionEligibilityScore * 0.20)
                        + ($conditionResolutionScore * 0.20)
                        + ($decisionEvidenceReadinessScore * 0.20)
                        + ($planningReadinessScore * 0.15)
                        + ((100 - $decisionRiskScore) * 0.15)
                        + ((100 - $conditionPressureScore) * 0.10)
                    )
                )
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | State Classification
        |--------------------------------------------------------------------------
        */

        if (
            $finalHumanDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && $governanceValidationCompleted
        ) {
            $strategicHumanDecisionState =
                'FINAL_AUTHORIZED_HUMAN_DECISION_VALIDATED';
        } elseif (
            $finalHumanDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && !$governanceValidationCompleted
        ) {
            $strategicHumanDecisionState =
                'FINAL_HUMAN_DECISION_AWAITING_GOVERNANCE_VALIDATION';
        } elseif (
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidenceItems > 0
        ) {
            $strategicHumanDecisionState =
                'PENDING_HUMAN_DECISION_WITH_CRITICAL_REQUIREMENTS';
        } elseif (
            $blockingConditions > 0
            || $decisionBlockingEvidenceItems > 0
        ) {
            $strategicHumanDecisionState =
                'PENDING_HUMAN_DECISION_WITH_MATERIAL_REQUIREMENTS';
        } elseif ($constrainingConditions > 0 || $outstandingEvidenceItems > 0) {
            $strategicHumanDecisionState =
                'PENDING_HUMAN_DECISION_WITH_REMAINING_CONSTRAINTS';
        } else {
            $strategicHumanDecisionState =
                'READY_FOR_AUTHORIZED_HUMAN_DECISION';
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Readiness Classification
        |--------------------------------------------------------------------------
        */

        $humanDecisionReadiness = match (true) {
            $humanDecisionReadinessScore >= 80 =>
                'HIGH_HUMAN_DECISION_READINESS',

            $humanDecisionReadinessScore >= 60 =>
                'MODERATE_HUMAN_DECISION_READINESS',

            $humanDecisionReadinessScore >= 40 =>
                'LIMITED_HUMAN_DECISION_READINESS',

            default =>
                'VERY_LIMITED_HUMAN_DECISION_READINESS',
        };

        /*
        |--------------------------------------------------------------------------
        | Decision Confidence
        |--------------------------------------------------------------------------
        */

        $decisionConfidence = match (true) {
            $humanDecisionReadinessScore >= 80 => 'HIGH',
            $humanDecisionReadinessScore >= 60 => 'MODERATE',
            $humanDecisionReadinessScore >= 40 => 'LIMITED',
            $humanDecisionReadinessScore >= 20 => 'VERY_LIMITED',
            default => 'EXTREMELY_LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Governance Action State
        |--------------------------------------------------------------------------
        */

        if ($finalHumanDecisionRecorded && $governanceValidationCompleted) {
            $humanGovernanceActionState =
                'FINAL_HUMAN_DECISION_AND_VALIDATION_COMPLETE';
        } elseif ($finalHumanDecisionRecorded) {
            $humanGovernanceActionState =
                'FINAL_HUMAN_DECISION_AWAITING_VALIDATION';
        } elseif ($blockingConditions > 0 || $decisionBlockingEvidenceItems > 0) {
            $humanGovernanceActionState =
                'MATERIAL_REQUIREMENTS_REQUIRE_HUMAN_GOVERNANCE';
        } elseif ($constrainingConditions > 0 || $outstandingEvidenceItems > 0) {
            $humanGovernanceActionState =
                'REMAINING_REQUIREMENTS_REQUIRE_HUMAN_GOVERNANCE';
        } else {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_DECISION_MAY_PROCEED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionLevel = match (true) {
            $criticalOpenConditions > 0
                || $criticalOutstandingEvidenceItems > 0 => 'CRITICAL',

            $blockingConditions > 0
                || $decisionBlockingEvidenceItems > 0
                || $decisionRiskScore >= 75 => 'HIGH',

            $constrainingConditions > 0
                || $outstandingEvidenceItems > 0 => 'MODERATE',

            default => 'ROUTINE',
        };

        $humanManagementAttentionRequired =
            in_array(
                $humanManagementAttentionLevel,
                ['CRITICAL', 'HIGH', 'MODERATE'],
                true
            );

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidenceItems > 0;

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $humanDecisionFindings = [
            "Strategic plan human decision state intelligence is based on human decision record {$humanDecision->id}.",
            'Current human decision status is '
                .$humanDecision->human_decision_status.'.',
            'Current prepared strategic plan decision is '
                .$humanDecision->prepared_decision.'.',
            'Current strategic human decision state is '
                .$strategicHumanDecisionState.'.',
            'Current human decision readiness is '
                .$humanDecisionReadiness
                .' with score '.$humanDecisionReadinessScore.'.',
            'Current human decision confidence is '
                .$decisionConfidence.'.',
            'Current human governance action state is '
                .$humanGovernanceActionState.'.',
            $openConditions->count()
                .' human decision condition(s) remain open.',
            $blockingConditions
                .' blocking human decision condition(s) remain active.',
            $constrainingConditions
                .' constraining human decision condition(s) remain active.',
            $outstandingEvidenceItems
                .' strategic plan human decision evidence requirement(s) remain outstanding.',
            $decisionBlockingEvidenceItems
                .' outstanding evidence requirement(s) currently block unrestricted approval.',
            'Current condition resolution score is '
                .$conditionResolutionScore.'.',
            'Current evidence readiness score is '
                .$evidenceReadinessScore.'.',
            'Current approval eligibility is '
                .$approvalEligibility.'.',
            'Current human decision risk is '
                .$humanDecision->decision_risk_level
                .' with score '.$decisionRiskScore.'.',
            'Final human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',
            'Decision made by authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',
            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',
            'Strategic plan human decision state intelligence remains informational and does not make, alter, validate, or execute the final governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantOpenCondition) {
            $managementPriorities[] =
                'Address the dominant open human decision condition '
                .($dominantOpenCondition['condition_code'] ?? 'UNKNOWN')
                .' through the established authorized human governance process.';
        }

        if ($decisionBlockingEvidenceItems > 0) {
            $managementPriorities[] =
                'Provide and validate outstanding decision-blocking evidence before unrestricted strategic plan approval is considered.';
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] =
                'Resolve or formally govern active blocking human decision conditions before unrestricted strategic plan approval is considered.';
        }

        if ($constrainingConditions > 0) {
            $managementPriorities[] =
                'Reduce active constraining human decision conditions to improve final decision readiness.';
        }

        if ($outstandingEvidenceItems > 0) {
            $managementPriorities[] =
                'Complete outstanding evidence review and validation through authorized human governance.';
        }

        if ($decisionRiskScore >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized human governance oversight while strategic plan human decision risk remains high.';
        }

        if (!$finalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Ensure the final strategic plan decision is made, justified, and documented only by an authorized human governance decision-maker.';
        }

        if (
            $finalHumanDecisionRecorded
            && !$governanceValidationCompleted
        ) {
            $managementPriorities[] =
                'Complete independent governance validation of the recorded final human strategic plan decision.';
        }

        $managementPriorities[] =
            'Preserve evidence quality, human authority, governance validation, traceability, safety controls, and authority separation throughout the strategic plan human decision process.';

        $managementPriorities = array_values(
            array_unique($managementPriorities)
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_human_decision_id' => $humanDecision->id,
            'human_decision_code' => $humanDecision->human_decision_code,

            'strategic_plan_decision_id' =>
                $humanDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $humanDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $humanDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $humanDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $humanDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $humanDecision->decision_scope,

            'resident_id' =>
                $humanDecision->resident_id,

            'human_decision_state' => [
                'human_decision_status' =>
                    $humanDecision->human_decision_status,

                'human_decision_mode' =>
                    $humanDecision->human_decision_mode,

                'prepared_decision' =>
                    $humanDecision->prepared_decision,

                'prepared_decision_status' =>
                    $humanDecision->prepared_decision_status,

                'final_human_decision' =>
                    $humanDecision->final_human_decision,

                'strategic_human_decision_state' =>
                    $strategicHumanDecisionState,

                'human_decision_readiness' =>
                    $humanDecisionReadiness,

                'human_decision_readiness_score' =>
                    $humanDecisionReadinessScore,

                'human_decision_confidence' =>
                    $decisionConfidence,

                'human_governance_action_state' =>
                    $humanGovernanceActionState,

                'approval_eligibility' =>
                    $approvalEligibility,

                'decision_eligibility_score' =>
                    $decisionEligibilityScore,

                'decision_risk_level' =>
                    $humanDecision->decision_risk_level,

                'decision_risk_score' =>
                    $decisionRiskScore,

                'human_management_attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            'condition_context' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditions->count(),

                'resolved_conditions' =>
                    $resolvedConditions->count(),

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'high_open_conditions' =>
                    $highOpenConditions,

                'moderate_open_conditions' =>
                    $moderateOpenConditions,

                'advisory_open_conditions' =>
                    $advisoryOpenConditions,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'dominant_open_condition' =>
                    $dominantOpenCondition,
            ],

            'evidence_context' => [
                'total_evidence_requirements' =>
                    $totalEvidenceRequirements,

                'required_evidence_items' =>
                    $requiredEvidenceItems->count(),

                'available_evidence_items' =>
                    $availableEvidenceItems->count(),

                'validated_evidence_items' =>
                    count($validatedEvidence),

                'outstanding_evidence_items' =>
                    $outstandingEvidenceItems,

                'decision_blocking_evidence_items' =>
                    $decisionBlockingEvidenceItems,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidenceItems,

                'high_outstanding_evidence_items' =>
                    $highOutstandingEvidenceItems,

                'evidence_readiness_score' =>
                    $evidenceReadinessScore,
            ],

            'authorization_context' => [
                'decided_by' =>
                    $humanDecision->decided_by,

                'decider_role' =>
                    $humanDecision->decider_role,

                'decided_at' =>
                    $humanDecision->decided_at,

                'human_decider_identified' =>
                    $humanReviewerIdentified,

                'decision_timestamp_available' =>
                    $decisionTimestampAvailable,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,
            ],

            'validation_context' => [
                'validated_by' =>
                    $humanDecision->validated_by,

                'validator_role' =>
                    $humanDecision->validator_role,

                'validated_at' =>
                    $humanDecision->validated_at,

                'validator_identified' =>
                    $validatorIdentified,

                'validation_timestamp_available' =>
                    $validationTimestampAvailable,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,

                'governance_validation_required' =>
                    (bool) $humanDecision->governance_validation_required,
            ],

            'completion_context' => [
                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_readiness_score' =>
                    $evidenceReadinessScore,

                'authorization_score' =>
                    $authorizationScore,

                'validation_score' =>
                    $validationScore,

                'human_decision_completion_score' =>
                    $humanDecisionCompletionScore,
            ],

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'human_decision_findings' =>
                $humanDecisionFindings,

            'management_priorities' =>
                $managementPriorities,

            'human_decision_state_guardrails' => [
                'strategic_plan_human_decision_state_intelligence_enabled' => true,

                'state_intelligence_is_final_human_decision' => false,
                'state_intelligence_makes_governance_decision' => false,
                'state_intelligence_records_governance_decision' => false,

                'state_intelligence_approves_strategic_plan' => false,
                'state_intelligence_rejects_strategic_plan' => false,
                'state_intelligence_activates_strategic_plan' => false,

                'state_intelligence_changes_human_decision_status' => false,
                'state_intelligence_changes_final_human_decision' => false,
                'state_intelligence_changes_plan_status' => false,
                'state_intelligence_changes_action_state' => false,
                'state_intelligence_changes_priority' => false,
                'state_intelligence_changes_eligibility' => false,

                'state_intelligence_resolves_conditions' => false,
                'state_intelligence_resolves_dependencies' => false,
                'state_intelligence_validates_evidence' => false,
                'state_intelligence_completes_governance_validation' => false,

                'readiness_score_authorizes_approval' => false,
                'readiness_score_authorizes_rejection' => false,
                'completion_score_authorizes_approval' => false,
                'completion_score_authorizes_execution' => false,

                'state_intelligence_authorizes_ai_change' => false,
                'state_intelligence_authorizes_execution' => false,
                'state_intelligence_authorizes_deployment' => false,
                'state_intelligence_authorizes_rollback' => false,
                'state_intelligence_authorizes_clinical_action' => false,

                'state_intelligence_overrides_human_review' => false,
                'state_intelligence_overrides_governance_validation' => false,
                'state_intelligence_overrides_evidence_requirements' => false,

                'automatic_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'final_decision_authority_reserved_for_authorized_human' => true,
                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Strategic plan human decision state intelligence evaluates the current authorized-human decision record, unresolved conditions, evidence readiness, decision authority state, governance validation state, readiness, completion, and management attention requirements. It does not make or record the final human governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, complete governance validation, activate planning work, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}