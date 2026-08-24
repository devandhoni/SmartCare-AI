<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine
{
    public function analyze(?int $validationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resolve Validation Record
        |--------------------------------------------------------------------------
        */

        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision validation record is available for validation-state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Data
        |--------------------------------------------------------------------------
        */

        $validationConditions = is_array($validation->validation_conditions)
            ? $validation->validation_conditions
            : [];

        $validatedEvidence = is_array($validation->validated_evidence)
            ? $validation->validated_evidence
            : [];

        $reviewContext = is_array($validation->review_context)
            ? $validation->review_context
            : [];

        $governanceContext = is_array($validation->governance_context)
            ? $validation->governance_context
            : [];

        $sourceContext = is_array($validation->source_context)
            ? $validation->source_context
            : [];

        /*
        |--------------------------------------------------------------------------
        | Validation Condition Analysis
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = collect($validationConditions)
            ->map(function (array $condition): array {
                $status = strtoupper(
                    (string) ($condition['condition_status'] ?? 'OPEN')
                );

                $priority = strtoupper(
                    (string) ($condition['priority_level'] ?? 'ADVISORY')
                );

                $conditionOpen = !in_array(
                    $status,
                    [
                        'RESOLVED',
                        'CLOSED',
                        'SATISFIED',
                        'COMPLETED',
                        'VALIDATED',
                    ],
                    true
                );

                $severityWeight = match ($priority) {
                    'CRITICAL' => 100.0,
                    'HIGH' => 75.0,
                    'MODERATE' => 50.0,
                    'LOW' => 25.0,
                    default => 10.0,
                };

                return array_merge(
                    $condition,
                    [
                        'condition_open' => $conditionOpen,
                        'severity_weight' => $severityWeight,
                        'requires_authorized_human_resolution' =>
                            (bool) (
                                $condition['requires_authorized_human_resolution']
                                ?? true
                            ),
                        'automatic_resolution_allowed' =>
                            (bool) (
                                $condition['automatic_resolution_allowed']
                                ?? false
                            ),
                    ]
                );
            })
            ->values()
            ->all();

        $totalConditions = count($conditionAnalysis);

        $openConditions = collect($conditionAnalysis)
            ->where('condition_open', true)
            ->values()
            ->all();

        $resolvedConditions = collect($conditionAnalysis)
            ->where('condition_open', false)
            ->values()
            ->all();

        $criticalOpenConditions = collect($openConditions)
            ->where('priority_level', 'CRITICAL')
            ->count();

        $highOpenConditions = collect($openConditions)
            ->where('priority_level', 'HIGH')
            ->count();

        $moderateOpenConditions = collect($openConditions)
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisoryOpenConditions = count($openConditions)
            - $criticalOpenConditions
            - $highOpenConditions
            - $moderateOpenConditions;

        $dominantOpenCondition = collect($openConditions)
            ->sortByDesc(function (array $condition) {
                return $condition['severity_weight'] ?? 0;
            })
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Validation Condition Resolution Score
        |--------------------------------------------------------------------------
        */

        $validationConditionResolutionScore =
            $totalConditions > 0
                ? round(
                    (count($resolvedConditions) / $totalConditions) * 100,
                    2
                )
                : 100.0;

        /*
        |--------------------------------------------------------------------------
        | Evidence Analysis
        |--------------------------------------------------------------------------
        */

        $evidenceAnalysis = collect($validatedEvidence)
            ->map(function (array $evidence): array {
                $available =
                    (bool) (
                        $evidence['evidence_available']
                        ?? (
                            strtoupper(
                                (string) ($evidence['evidence_status'] ?? '')
                            ) === 'AVAILABLE'
                        )
                    );

                $validated =
                    (bool) (
                        $evidence['evidence_validated']
                        ?? $available
                    );

                return array_merge(
                    $evidence,
                    [
                        'evidence_available' => $available,
                        'evidence_validated' => $validated,
                    ]
                );
            })
            ->values()
            ->all();

        $totalValidatedEvidenceItems = count($evidenceAnalysis);

        $availableEvidenceItems = collect($evidenceAnalysis)
            ->where('evidence_available', true)
            ->count();

        $validatedEvidenceItems = collect($evidenceAnalysis)
            ->where('evidence_validated', true)
            ->count();

        $validatedEvidenceReadinessScore =
            $totalValidatedEvidenceItems > 0
                ? round(
                    ($validatedEvidenceItems / $totalValidatedEvidenceItems) * 100,
                    2
                )
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Human Decision State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) $validation->final_human_decision_recorded;

        $decisionMadeByAuthorizedHuman =
            (bool) $validation->decision_made_by_authorized_human;

        $finalHumanDecisionAvailable =
            !empty($validation->final_human_decision);

        /*
        |--------------------------------------------------------------------------
        | Validator Authorization State
        |--------------------------------------------------------------------------
        */

        $validatorIdentified =
            !empty($validation->validated_by);

        $validatorRoleIdentified =
            !empty($validation->validator_role);

        $validationTimestampAvailable =
            !empty($validation->validated_at);

        $validationMadeByAuthorizedHuman =
            (bool) $validation->validation_made_by_authorized_human;

        $governanceValidationCompleted =
            (bool) $validation->governance_validation_completed;

        /*
        |--------------------------------------------------------------------------
        | Validation Authorization Coherence
        |--------------------------------------------------------------------------
        */

        $validatorAttributionComplete =
            $validatorIdentified
            && $validatorRoleIdentified
            && $validationTimestampAvailable;

        $completedValidationAuthorityCoherent =
            !$governanceValidationCompleted
            || (
                $validationMadeByAuthorizedHuman
                && $validatorAttributionComplete
                && !empty($validation->governance_validation_decision)
            );

        /*
        |--------------------------------------------------------------------------
        | Validation Completion Score
        |--------------------------------------------------------------------------
        */

        $humanDecisionScore =
            $finalHumanDecisionRecorded
            && $decisionMadeByAuthorizedHuman
            && $finalHumanDecisionAvailable
                ? 100.0
                : 0.0;

        $validatorAuthorizationScore =
            $validationMadeByAuthorizedHuman
            && $validatorIdentified
            && $validatorRoleIdentified
                ? 100.0
                : 0.0;

        $validationTimestampScore =
            $validationTimestampAvailable
                ? 100.0
                : 0.0;

        $governanceValidationCompletionScore =
            $governanceValidationCompleted
                ? 100.0
                : 0.0;

        $validationCompletionScore = round(
            (
                ($humanDecisionScore * 0.30)
                + ($validatorAuthorizationScore * 0.20)
                + ($validationTimestampScore * 0.10)
                + ($governanceValidationCompletionScore * 0.25)
                + ($validationConditionResolutionScore * 0.15)
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Readiness Score
        |--------------------------------------------------------------------------
        */

        $sourceReadinessScore =
            max(
                0.0,
                min(
                    100.0,
                    (float) $validation->governance_validation_readiness_score
                )
            );

        $conditionResolutionScore =
            max(
                0.0,
                min(
                    100.0,
                    (float) $validation->condition_resolution_score
                )
            );

        $evidenceResolutionScore =
            max(
                0.0,
                min(
                    100.0,
                    (float) $validation->evidence_resolution_score
                )
            );

        $combinedResolutionScore =
            max(
                0.0,
                min(
                    100.0,
                    (float) $validation->combined_resolution_score
                )
            );

        $governanceValidationReadinessScore = round(
            (
                ($sourceReadinessScore * 0.25)
                + ($conditionResolutionScore * 0.20)
                + ($evidenceResolutionScore * 0.15)
                + ($combinedResolutionScore * 0.20)
                + ($humanDecisionScore * 0.20)
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Validation Readiness Classification
        |--------------------------------------------------------------------------
        */

        if (
            !$finalHumanDecisionRecorded
            || !$decisionMadeByAuthorizedHuman
            || !$finalHumanDecisionAvailable
        ) {
            $validationReadiness =
                'AWAITING_AUTHORIZED_HUMAN_FINAL_DECISION';
        } elseif (
            $criticalOpenConditions > 0
            || count($openConditions) > 0
        ) {
            $validationReadiness =
                'MATERIAL_VALIDATION_REQUIREMENTS_OUTSTANDING';
        } elseif (
            !$validationMadeByAuthorizedHuman
            || !$validatorAttributionComplete
        ) {
            $validationReadiness =
                'AWAITING_AUTHORIZED_HUMAN_VALIDATOR';
        } elseif (!$governanceValidationCompleted) {
            $validationReadiness =
                'READY_FOR_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION';
        } else {
            $validationReadiness =
                'GOVERNANCE_VALIDATION_COMPLETED';
        }

        /*
        |--------------------------------------------------------------------------
        | Strategic Validation State
        |--------------------------------------------------------------------------
        */

        if ($governanceValidationCompleted) {
            $strategicValidationState =
                'GOVERNANCE_VALIDATION_COMPLETED';
        } elseif (!$finalHumanDecisionRecorded) {
            $strategicValidationState =
                'PENDING_VALIDATION_AWAITING_FINAL_HUMAN_DECISION';
        } elseif (count($openConditions) > 0) {
            $strategicValidationState =
                'PENDING_VALIDATION_WITH_MATERIAL_REQUIREMENTS';
        } elseif (!$validationMadeByAuthorizedHuman) {
            $strategicValidationState =
                'PENDING_AUTHORIZED_HUMAN_VALIDATOR';
        } else {
            $strategicValidationState =
                'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION';
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Confidence
        |--------------------------------------------------------------------------
        */

        $validationConfidence = match (true) {
            $governanceValidationReadinessScore >= 85 =>
                'VERY_HIGH',

            $governanceValidationReadinessScore >= 70 =>
                'HIGH',

            $governanceValidationReadinessScore >= 50 =>
                'MODERATE',

            $governanceValidationReadinessScore >= 30 =>
                'LIMITED',

            $governanceValidationReadinessScore >= 15 =>
                'VERY_LIMITED',

            default =>
                'EXTREMELY_LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Governance Action State
        |--------------------------------------------------------------------------
        */

        if ($governanceValidationCompleted) {
            $humanGovernanceActionState =
                'VALIDATION_COMPLETED_REVIEW_GOVERNANCE_OUTCOME';
        } elseif (!$finalHumanDecisionRecorded) {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_FINAL_DECISION_REQUIRED';
        } elseif (count($openConditions) > 0) {
            $humanGovernanceActionState =
                'VALIDATION_REQUIREMENTS_REQUIRE_HUMAN_GOVERNANCE';
        } elseif (!$validationMadeByAuthorizedHuman) {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_VALIDATOR_REQUIRED';
        } else {
            $humanGovernanceActionState =
                'AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION_REQUIRED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $decisionRiskScore =
            (float) $validation->decision_risk_score;

        $humanManagementAttentionRequired =
            count($openConditions) > 0
            || !$finalHumanDecisionRecorded
            || !$decisionMadeByAuthorizedHuman
            || !$governanceValidationCompleted
            || $decisionRiskScore >= 70;

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || !$completedValidationAuthorityCoherent;

        $humanManagementAttentionLevel = match (true) {
            $immediateHumanInterventionRequired =>
                'CRITICAL',

            $decisionRiskScore >= 75
            || $highOpenConditions > 0 =>
                'HIGH',

            $moderateOpenConditions > 0 =>
                'MODERATE',

            default =>
                'NORMAL',
        };

        /*
        |--------------------------------------------------------------------------
        | State Contexts
        |--------------------------------------------------------------------------
        */

        $validationState = [
            'validation_status' =>
                $validation->validation_status,

            'validation_mode' =>
                $validation->validation_mode,

            'prepared_decision' =>
                $validation->prepared_decision,

            'final_human_decision' =>
                $validation->final_human_decision,

            'governance_validation_decision' =>
                $validation->governance_validation_decision,

            'strategic_validation_state' =>
                $strategicValidationState,

            'governance_validation_readiness' =>
                $validationReadiness,

            'governance_validation_readiness_score' =>
                $governanceValidationReadinessScore,

            'validation_confidence' =>
                $validationConfidence,

            'human_governance_action_state' =>
                $humanGovernanceActionState,

            'decision_risk_level' =>
                $validation->decision_risk_level,

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

            'validation_made_by_authorized_human' =>
                $validationMadeByAuthorizedHuman,

            'governance_validation_completed' =>
                $governanceValidationCompleted,
        ];

        $conditionContext = [
            'total_validation_conditions' =>
                $totalConditions,

            'open_validation_conditions' =>
                count($openConditions),

            'resolved_validation_conditions' =>
                count($resolvedConditions),

            'critical_open_validation_conditions' =>
                $criticalOpenConditions,

            'high_open_validation_conditions' =>
                $highOpenConditions,

            'moderate_open_validation_conditions' =>
                $moderateOpenConditions,

            'advisory_open_validation_conditions' =>
                $advisoryOpenConditions,

            'validation_condition_resolution_score' =>
                $validationConditionResolutionScore,

            'dominant_open_validation_condition' =>
                $dominantOpenCondition,
        ];

        $evidenceContext = [
            'total_validated_evidence_items' =>
                $totalValidatedEvidenceItems,

            'available_validated_evidence_items' =>
                $availableEvidenceItems,

            'validated_evidence_items' =>
                $validatedEvidenceItems,

            'validated_evidence_readiness_score' =>
                $validatedEvidenceReadinessScore,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,
        ];

        $humanDecisionContext = [
            'final_human_decision' =>
                $validation->final_human_decision,

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'final_human_decision_available' =>
                $finalHumanDecisionAvailable,

            'human_decision_score' =>
                $humanDecisionScore,
        ];

        $validatorContext = [
            'validated_by' =>
                $validation->validated_by,

            'validator_role' =>
                $validation->validator_role,

            'validated_at' =>
                $validation->validated_at,

            'validator_identified' =>
                $validatorIdentified,

            'validator_role_identified' =>
                $validatorRoleIdentified,

            'validation_timestamp_available' =>
                $validationTimestampAvailable,

            'validator_attribution_complete' =>
                $validatorAttributionComplete,

            'validation_made_by_authorized_human' =>
                $validationMadeByAuthorizedHuman,
        ];

        $completionContext = [
            'human_decision_score' =>
                $humanDecisionScore,

            'validator_authorization_score' =>
                $validatorAuthorizationScore,

            'validation_timestamp_score' =>
                $validationTimestampScore,

            'validation_condition_resolution_score' =>
                $validationConditionResolutionScore,

            'governance_validation_completion_score' =>
                $governanceValidationCompletionScore,

            'governance_validation_completion_score_combined' =>
                $validationCompletionScore,

            'completed_validation_authority_coherent' =>
                $completedValidationAuthorityCoherent,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $validationFindings = [
            "Strategic plan decision validation state intelligence is based on validation record {$validation->id}.",

            'Current validation status is '
                .$validation->validation_status.'.',

            'Current strategic validation state is '
                .$strategicValidationState.'.',

            'Current governance validation readiness is '
                .$validationReadiness
                .' with score '
                .$governanceValidationReadinessScore.'.',

            'Current validation confidence is '
                .$validationConfidence.'.',

            'Current human governance action state is '
                .$humanGovernanceActionState.'.',

            count($openConditions)
                .' validation condition(s) remain open.',

            $criticalOpenConditions
                .' critical validation condition(s) remain open.',

            $highOpenConditions
                .' high-priority validation condition(s) remain open.',

            'Current validation-condition resolution score is '
                .$validationConditionResolutionScore.'.',

            $validatedEvidenceItems
                .' validated evidence item(s) are currently represented.',

            'Current validated-evidence readiness score is '
                .$validatedEvidenceReadinessScore.'.',

            'Source condition resolution score is '
                .$conditionResolutionScore.'.',

            'Source evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Source combined condition/evidence resolution score is '
                .$combinedResolutionScore.'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Authorized human governance validator identified is '
                .($validatorIdentified ? 'YES' : 'NO').'.',

            'Governance validation made by authorized human is '
                .($validationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Governance validation completion authority coherence is '
                .($completedValidationAuthorityCoherent ? 'INTACT' : 'NOT_INTACT').'.',

            'Strategic plan decision validation state intelligence remains informational and does not make, record, or complete governance validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$finalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record the final strategic plan decision through an explicitly authorized human governance decision-maker before governance validation is completed.';
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure final strategic plan decision attribution remains linked to an explicitly authorized human governance decision-maker.';
        }

        if (count($openConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern outstanding validation conditions through the authorized human governance process.';
        }

        if ($dominantOpenCondition) {
            $managementPriorities[] =
                'Address the dominant open validation condition '
                .($dominantOpenCondition['condition_code'] ?? 'UNKNOWN')
                .' through authorized human governance.';
        }

        if ($conditionResolutionScore < 100) {
            $managementPriorities[] =
                'Improve authorized-human decision condition resolution before completed governance validation is considered.';
        }

        if ($evidenceResolutionScore < 100) {
            $managementPriorities[] =
                'Complete outstanding evidence review and validation through authorized human governance.';
        }

        if (!$validatorIdentified) {
            $managementPriorities[] =
                'Assign an explicitly authorized human governance validator before completed governance validation is recorded.';
        }

        if (!$governanceValidationCompleted) {
            $managementPriorities[] =
                'Keep governance validation pending until all required human-decision, validation-condition, evidence, attribution, and validator controls are satisfied or formally governed.';
        }

        if ($decisionRiskScore >= 70) {
            $managementPriorities[] =
                'Maintain elevated human governance oversight while strategic plan decision risk remains high.';
        }

        $managementPriorities[] =
            'Preserve human decision authority, human validation authority, evidence quality, traceability, safety controls, and strict authority separation throughout governance validation.';

        $managementPriorities =
            array_values(array_unique($managementPriorities));

        /*
        |--------------------------------------------------------------------------
        | Guardrails
        |--------------------------------------------------------------------------
        */

        $validationStateGuardrails = [
            'strategic_plan_decision_validation_state_intelligence_enabled' =>
                true,

            'validation_state_intelligence_is_completed_validation' =>
                false,

            'validation_state_intelligence_makes_governance_validation_decision' =>
                false,

            'validation_state_intelligence_records_governance_validation_decision' =>
                false,

            'validation_state_intelligence_completes_governance_validation' =>
                false,

            'validation_state_intelligence_approves_strategic_plan' =>
                false,

            'validation_state_intelligence_rejects_strategic_plan' =>
                false,

            'validation_state_intelligence_conditionally_approves_strategic_plan' =>
                false,

            'validation_state_intelligence_defers_strategic_plan' =>
                false,

            'validation_state_intelligence_accepts_governance_risk' =>
                false,

            'validation_state_intelligence_activates_strategic_plan' =>
                false,

            'validation_state_intelligence_changes_validation_status' =>
                false,

            'validation_state_intelligence_changes_governance_validation_decision' =>
                false,

            'validation_state_intelligence_changes_human_decision' =>
                false,

            'validation_state_intelligence_changes_plan_status' =>
                false,

            'validation_state_intelligence_changes_action_state' =>
                false,

            'validation_state_intelligence_changes_priority' =>
                false,

            'validation_state_intelligence_changes_eligibility' =>
                false,

            'validation_state_intelligence_resolves_conditions' =>
                false,

            'validation_state_intelligence_resolves_dependencies' =>
                false,

            'validation_state_intelligence_validates_evidence' =>
                false,

            'validation_readiness_score_authorizes_validation' =>
                false,

            'validation_readiness_score_authorizes_approval' =>
                false,

            'validation_completion_score_authorizes_validation' =>
                false,

            'validation_completion_score_authorizes_execution' =>
                false,

            'validation_state_intelligence_authorizes_ai_change' =>
                false,

            'validation_state_intelligence_authorizes_execution' =>
                false,

            'validation_state_intelligence_authorizes_deployment' =>
                false,

            'validation_state_intelligence_authorizes_rollback' =>
                false,

            'validation_state_intelligence_authorizes_clinical_action' =>
                false,

            'validation_state_intelligence_overrides_human_decision_authority' =>
                false,

            'validation_state_intelligence_overrides_human_review' =>
                false,

            'validation_state_intelligence_overrides_governance_validation' =>
                false,

            'validation_state_intelligence_overrides_evidence_requirements' =>
                false,

            'automatic_validation_allowed' =>
                false,

            'automatic_decision_allowed' =>
                false,

            'automatic_approval_allowed' =>
                false,

            'automatic_rejection_allowed' =>
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

            'governance_validation_authority_reserved_for_authorized_human' =>
                true,

            'final_decision_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'message' =>
                'Strategic plan decision validation state intelligence evaluates the current governance-validation package, unresolved validation conditions, validated evidence, final-human-decision state, validator attribution, governance-validation readiness, completion, decision risk, and management attention requirements. It does not make or record a governance-validation decision, complete governance validation, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final strategic plan decision and governance-validation authority remain reserved exclusively for authorized human governance.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_validation_id' =>
                $validation->id,

            'validation_code' =>
                $validation->validation_code,

            'strategic_plan_human_decision_id' =>
                $validation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $validation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $validation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $validation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $validation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $validation->lifecycle_snapshot_id,

            'decision_scope' =>
                $validation->decision_scope,

            'resident_id' =>
                $validation->resident_id,

            'validation_state' =>
                $validationState,

            'condition_context' =>
                $conditionContext,

            'evidence_context' =>
                $evidenceContext,

            'human_decision_context' =>
                $humanDecisionContext,

            'validator_context' =>
                $validatorContext,

            'completion_context' =>
                $completionContext,

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'condition_analysis' =>
                $conditionAnalysis,

            'open_validation_conditions' =>
                $openConditions,

            'resolved_validation_conditions' =>
                $resolvedConditions,

            'evidence_analysis' =>
                $evidenceAnalysis,

            'validation_findings' =>
                $validationFindings,

            'management_priorities' =>
                $managementPriorities,

            'validation_state_guardrails' =>
                $validationStateGuardrails,
        ];
    }
}