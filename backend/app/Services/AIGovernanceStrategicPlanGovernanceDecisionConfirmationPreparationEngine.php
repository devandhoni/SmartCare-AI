<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;
use App\Models\AIGovernanceStrategicPlanGovernanceDecisionConfirmation;
use Illuminate\Support\Facades\DB;
use Throwable;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmationPreparationEngine
{
    /**
     * Step 68.2
     *
     * Strategic Plan Governance Decision Confirmation Preparation
     *
     * This engine prepares a governance-decision confirmation record from
     * the Step 67 final-governance-decision record.
     *
     * It does NOT:
     * - make a final governance decision;
     * - confirm a final governance decision;
     * - authorize controlled activation;
     * - activate a strategic plan;
     * - resolve governance conditions;
     * - remove governance restrictions;
     * - validate evidence;
     * - execute, deploy, rollback, or initiate clinical action.
     */
    public function prepare(?int $strategicPlanFinalGovernanceDecisionId = null): array
    {
        try {
            $sourceDecision = $this->resolveSourceDecision(
                $strategicPlanFinalGovernanceDecisionId
            );

            if (!$sourceDecision) {
                return [
                    'prepared' => false,
                    'status' => 'GOVERNANCE_STRATEGIC_PLAN_CONFIRMATION_SOURCE_NOT_FOUND',
                    'message' => 'No strategic plan final governance decision record was available for confirmation preparation.',
                    'strategic_plan_final_governance_decision_id' => $strategicPlanFinalGovernanceDecisionId,
                    'confirmation_guardrails' => $this->guardrails(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Idempotency
            |--------------------------------------------------------------------------
            |
            | A Step 67 final-governance-decision record should map to one
            | Step 68 confirmation registry record.
            |
            */

            $existing = AIGovernanceStrategicPlanGovernanceDecisionConfirmation::query()
                ->where(
                    'strategic_plan_final_governance_decision_id',
                    $sourceDecision->id
                )
                ->latest('id')
                ->first();

            if ($existing) {
                return $this->buildExistingResponse(
                    $existing,
                    $sourceDecision
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Source State
            |--------------------------------------------------------------------------
            */

            $sourceFinalDecisionRecorded =
                $this->hasRecordedFinalGovernanceDecision($sourceDecision);

            $sourceDecisionMadeByAuthorizedHuman =
                (bool) ($sourceDecision->decision_made_by_authorized_human ?? false);

            $sourceGovernanceValidationCompleted =
                (bool) ($sourceDecision->source_governance_validation_completed ?? false);

            $sourceFinalGovernanceConfirmationCompleted =
                (bool) ($sourceDecision->final_governance_confirmation_completed ?? false);

            /*
            |--------------------------------------------------------------------------
            | Step 67 Structured Context
            |--------------------------------------------------------------------------
            */

            $sourceConditions = $this->normalizeArray(
                $sourceDecision->decision_conditions
            );

            $validatedEvidence = $this->normalizeArray(
                $sourceDecision->validated_evidence
            );

            $sourceRestrictions = $this->normalizeArray(
                $sourceDecision->decision_restrictions
            );

            $sourceFindings = $this->normalizeArray(
                $sourceDecision->decision_findings
            );

            $reviewContext = $this->normalizeArray(
                $sourceDecision->review_context
            );

            $validationContext = $this->normalizeArray(
                $sourceDecision->validation_context
            );

            $governanceContext = $this->normalizeArray(
                $sourceDecision->governance_context
            );

            $sourceContext = $this->normalizeArray(
                $sourceDecision->source_context
            );

            /*
            |--------------------------------------------------------------------------
            | Resolution / Pressure State
            |--------------------------------------------------------------------------
            */

            $conditionResolutionScore = $this->score(
                $sourceDecision->condition_resolution_score
            );

            $evidenceResolutionScore = $this->score(
                $sourceDecision->evidence_resolution_score
            );

            $combinedResolutionScore = $this->score(
                $sourceDecision->combined_resolution_score
            );

            $conditionPressureScore = $this->calculateConditionPressureScore(
                $sourceConditions
            );

            $restrictionPressureScore = $this->calculateRestrictionPressureScore(
                $sourceRestrictions
            );

            $combinedConditionRestrictionPressureScore = round(
                (
                    $conditionPressureScore +
                    $restrictionPressureScore
                ) / 2,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Confirmation Conditions
            |--------------------------------------------------------------------------
            */

            $confirmationConditions = $this->buildConfirmationConditions(
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $sourceFinalGovernanceConfirmationCompleted,
                $sourceConditions,
                $sourceRestrictions
            );

            /*
            |--------------------------------------------------------------------------
            | Confirmation Restrictions
            |--------------------------------------------------------------------------
            */

            $confirmationRestrictions = $this->buildConfirmationRestrictions(
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $sourceFinalGovernanceConfirmationCompleted,
                $sourceConditions,
                $sourceRestrictions
            );

            /*
            |--------------------------------------------------------------------------
            | Confirmation Readiness
            |--------------------------------------------------------------------------
            */

            $confirmationReadinessScore = $this->calculateConfirmationReadinessScore(
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $sourceFinalGovernanceConfirmationCompleted,
                $confirmationConditions,
                $confirmationRestrictions,
                $combinedResolutionScore
            );

            $confirmationReadiness = $this->determineConfirmationReadiness(
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $sourceFinalGovernanceConfirmationCompleted,
                $confirmationConditions,
                $confirmationRestrictions,
                $confirmationReadinessScore
            );

            /*
            |--------------------------------------------------------------------------
            | Confirmation Risk
            |--------------------------------------------------------------------------
            */

            $confirmationRiskScore = $this->calculateConfirmationRiskScore(
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $confirmationConditions,
                $confirmationRestrictions,
                $combinedConditionRestrictionPressureScore
            );

            $confirmationRiskLevel = $this->determineConfirmationRiskLevel(
                $confirmationRiskScore
            );

            /*
            |--------------------------------------------------------------------------
            | Findings
            |--------------------------------------------------------------------------
            */

            $confirmationFindings = $this->buildConfirmationFindings(
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $sourceFinalGovernanceConfirmationCompleted,
                $confirmationReadiness,
                $confirmationReadinessScore,
                $confirmationRiskLevel,
                $confirmationRiskScore,
                $conditionResolutionScore,
                $evidenceResolutionScore,
                $combinedResolutionScore,
                $conditionPressureScore,
                $restrictionPressureScore,
                $combinedConditionRestrictionPressureScore,
                $confirmationConditions,
                $confirmationRestrictions,
                $sourceFindings
            );

            /*
            |--------------------------------------------------------------------------
            | Step 68 Review Context
            |--------------------------------------------------------------------------
            */

            $confirmationReviewContext = array_merge(
                $reviewContext,
                [
                    'confirmation_review_state' =>
                        'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION',

                    'source_final_governance_decision_recorded' =>
                        $sourceFinalDecisionRecorded,

                    'source_decision_made_by_authorized_human' =>
                        $sourceDecisionMadeByAuthorizedHuman,

                    'source_governance_validation_completed' =>
                        $sourceGovernanceValidationCompleted,

                    'source_final_governance_confirmation_completed' =>
                        $sourceFinalGovernanceConfirmationCompleted,

                    'confirmation_readiness' =>
                        $confirmationReadiness,

                    'confirmation_readiness_score' =>
                        $confirmationReadinessScore,

                    'confirmation_risk_level' =>
                        $confirmationRiskLevel,

                    'confirmation_risk_score' =>
                        $confirmationRiskScore,

                    'authorized_human_confirmation_required' => true,

                    'authorized_human_activation_required' => true,

                    'human_review_required' => true,

                    'governance_validation_required' => true,

                    'controlled_activation_authorized' => false,

                    'automatic_confirmation_allowed' => false,

                    'automatic_activation_allowed' => false,

                    'automatic_execution_allowed' => false,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Step 68 Validation Context
            |--------------------------------------------------------------------------
            */

            $confirmationValidationContext = array_merge(
                $validationContext,
                [
                    'source_final_governance_decision_status' =>
                        $sourceDecision->final_governance_decision_status,

                    'source_final_governance_decision' =>
                        $sourceDecision->final_governance_decision,

                    'source_final_governance_outcome' =>
                        $sourceDecision->final_governance_outcome,

                    'source_final_governance_outcome_status' =>
                        $sourceDecision->final_governance_outcome_status,

                    'source_final_governance_decision_recorded' =>
                        $sourceFinalDecisionRecorded,

                    'source_decision_made_by_authorized_human' =>
                        $sourceDecisionMadeByAuthorizedHuman,

                    'source_governance_validation_completed' =>
                        $sourceGovernanceValidationCompleted,

                    'confirmation_readiness' =>
                        $confirmationReadiness,

                    'confirmation_readiness_score' =>
                        $confirmationReadinessScore,

                    'confirmation_risk_level' =>
                        $confirmationRiskLevel,

                    'confirmation_risk_score' =>
                        $confirmationRiskScore,

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
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Step 68 Governance Context
            |--------------------------------------------------------------------------
            */

            $confirmationGovernanceContext = array_merge(
                $governanceContext,
                [
                    'step_68_confirmation_registry' => true,

                    'step_68_confirmation_preparation' => true,

                    'confirmation_authority' =>
                        'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

                    'activation_authority' =>
                        'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

                    'confirmation_readiness' =>
                        $confirmationReadiness,

                    'confirmation_readiness_score' =>
                        $confirmationReadinessScore,

                    'confirmation_risk_level' =>
                        $confirmationRiskLevel,

                    'confirmation_risk_score' =>
                        $confirmationRiskScore,

                    'controlled_activation_status' =>
                        'NOT_AUTHORIZED_FOR_ACTIVATION',

                    'controlled_activation_authorized' => false,

                    'governance_decision_confirmation_completed' => false,

                    'confirmation_made_by_authorized_human' => false,

                    'automatic_confirmation_allowed' => false,

                    'automatic_activation_allowed' => false,

                    'automatic_execution_allowed' => false,

                    'authorized_human_confirmation_required' => true,

                    'authorized_human_activation_required' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Source Traceability
            |--------------------------------------------------------------------------
            */

            $confirmationSourceContext = array_merge(
                $sourceContext,
                [
                    'strategic_plan_final_governance_decision_id' =>
                        $sourceDecision->id,

                    'final_governance_decision_code' =>
                        $sourceDecision->final_governance_decision_code,

                    'strategic_plan_decision_validation_id' =>
                        $sourceDecision->strategic_plan_decision_validation_id,

                    'strategic_plan_human_decision_id' =>
                        $sourceDecision->strategic_plan_human_decision_id,

                    'strategic_plan_decision_id' =>
                        $sourceDecision->strategic_plan_decision_id,

                    'strategic_plan_id' =>
                        $sourceDecision->strategic_plan_id,

                    'strategic_snapshot_id' =>
                        $sourceDecision->strategic_snapshot_id,

                    'operational_snapshot_id' =>
                        $sourceDecision->operational_snapshot_id,

                    'lifecycle_snapshot_id' =>
                        $sourceDecision->lifecycle_snapshot_id,

                    'decision_scope' =>
                        $sourceDecision->decision_scope,

                    'resident_id' =>
                        $sourceDecision->resident_id,

                    'prepared_decision' =>
                        $sourceDecision->prepared_decision,

                    'source_final_governance_decision' =>
                        $sourceDecision->final_governance_decision,

                    'source_final_governance_decision_recorded' =>
                        $sourceFinalDecisionRecorded,

                    'source_decision_made_by_authorized_human' =>
                        $sourceDecisionMadeByAuthorizedHuman,

                    'source_final_governance_outcome' =>
                        $sourceDecision->final_governance_outcome,

                    'source_final_governance_outcome_status' =>
                        $sourceDecision->final_governance_outcome_status,

                    'source_governance_validation_completed' =>
                        $sourceGovernanceValidationCompleted,

                    'source_final_governance_confirmation_completed' =>
                        $sourceFinalGovernanceConfirmationCompleted,

                    'source_final_governance_decision_created_at' =>
                        optional($sourceDecision->created_at)?->toISOString(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Persist Confirmation Registry Record
            |--------------------------------------------------------------------------
            */

            $confirmation = DB::transaction(function () use (
                $sourceDecision,
                $sourceFinalDecisionRecorded,
                $sourceDecisionMadeByAuthorizedHuman,
                $sourceGovernanceValidationCompleted,
                $confirmationReadiness,
                $confirmationReadinessScore,
                $confirmationRiskLevel,
                $confirmationRiskScore,
                $conditionResolutionScore,
                $evidenceResolutionScore,
                $combinedResolutionScore,
                $conditionPressureScore,
                $restrictionPressureScore,
                $combinedConditionRestrictionPressureScore,
                $confirmationConditions,
                $validatedEvidence,
                $confirmationFindings,
                $confirmationRestrictions,
                $confirmationReviewContext,
                $confirmationValidationContext,
                $confirmationGovernanceContext,
                $confirmationSourceContext
            ) {
                /*
                |--------------------------------------------------------------------------
                | Re-check Idempotency Inside Transaction
                |--------------------------------------------------------------------------
                */

                $existing = AIGovernanceStrategicPlanGovernanceDecisionConfirmation::query()
                    ->where(
                        'strategic_plan_final_governance_decision_id',
                        $sourceDecision->id
                    )
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $confirmation =
                    new AIGovernanceStrategicPlanGovernanceDecisionConfirmation();

                $confirmation->strategic_plan_final_governance_decision_id =
                    $sourceDecision->id;

                $confirmation->strategic_plan_decision_validation_id =
                    $sourceDecision->strategic_plan_decision_validation_id;

                $confirmation->strategic_plan_human_decision_id =
                    $sourceDecision->strategic_plan_human_decision_id;

                $confirmation->strategic_plan_decision_id =
                    $sourceDecision->strategic_plan_decision_id;

                $confirmation->strategic_plan_id =
                    $sourceDecision->strategic_plan_id;

                $confirmation->strategic_snapshot_id =
                    $sourceDecision->strategic_snapshot_id;

                $confirmation->operational_snapshot_id =
                    $sourceDecision->operational_snapshot_id;

                $confirmation->lifecycle_snapshot_id =
                    $sourceDecision->lifecycle_snapshot_id;

                $confirmation->decision_scope =
                    $sourceDecision->decision_scope;

                $confirmation->resident_id =
                    $sourceDecision->resident_id;

                /*
                |--------------------------------------------------------------------------
                | Confirmation Identity
                |--------------------------------------------------------------------------
                */

                $confirmation->confirmation_code =
                    $this->generateConfirmationCode($sourceDecision);

                $confirmation->confirmation_status =
                    'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';

                $confirmation->confirmation_mode =
                    'AUTHORIZED_HUMAN_GOVERNANCE_DECISION_CONFIRMATION';

                /*
                |--------------------------------------------------------------------------
                | Source Final Governance Decision
                |--------------------------------------------------------------------------
                */

                $confirmation->prepared_decision =
                    $sourceDecision->prepared_decision;

                $confirmation->source_final_governance_decision =
                    $sourceDecision->final_governance_decision;

                $confirmation->source_final_governance_decision_recorded =
                    $sourceFinalDecisionRecorded;

                $confirmation->source_decision_made_by_authorized_human =
                    $sourceDecisionMadeByAuthorizedHuman;

                $confirmation->source_final_governance_outcome =
                    $sourceDecision->final_governance_outcome;

                $confirmation->source_final_governance_outcome_status =
                    $sourceDecision->final_governance_outcome_status;

                $confirmation->source_governance_validation_completed =
                    $sourceGovernanceValidationCompleted;

                /*
                |--------------------------------------------------------------------------
                | Confirmation State
                |--------------------------------------------------------------------------
                |
                | Step 68.2 MUST NOT populate a human confirmation decision.
                |
                */

                $confirmation->governance_confirmation_decision = null;

                $confirmation->confirmation_rationale =
                    'Governance decision confirmation package prepared from Step 67 final governance decision intelligence for explicitly authorized human governance review. Preparation does not constitute governance confirmation or controlled activation authorization.';

                $confirmation->confirmation_notes = null;

                $confirmation->confirmation_outcome = null;

                $confirmation->confirmation_outcome_status =
                    'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';

                /*
                |--------------------------------------------------------------------------
                | Readiness / Risk
                |--------------------------------------------------------------------------
                */

                $confirmation->confirmation_readiness =
                    $confirmationReadiness;

                $confirmation->confirmation_readiness_score =
                    $confirmationReadinessScore;

                $confirmation->confirmation_risk_level =
                    $confirmationRiskLevel;

                $confirmation->confirmation_risk_score =
                    $confirmationRiskScore;

                $confirmation->condition_resolution_score =
                    $conditionResolutionScore;

                $confirmation->evidence_resolution_score =
                    $evidenceResolutionScore;

                $confirmation->combined_resolution_score =
                    $combinedResolutionScore;

                $confirmation->condition_pressure_score =
                    $conditionPressureScore;

                $confirmation->restriction_pressure_score =
                    $restrictionPressureScore;

                $confirmation->combined_condition_restriction_pressure_score =
                    $combinedConditionRestrictionPressureScore;

                /*
                |--------------------------------------------------------------------------
                | Structured Context
                |--------------------------------------------------------------------------
                */

                $confirmation->confirmation_conditions =
                    $confirmationConditions;

                $confirmation->validated_evidence =
                    $validatedEvidence;

                $confirmation->confirmation_findings =
                    $confirmationFindings;

                $confirmation->confirmation_restrictions =
                    $confirmationRestrictions;

                $confirmation->review_context =
                    $confirmationReviewContext;

                $confirmation->validation_context =
                    $confirmationValidationContext;

                $confirmation->governance_context =
                    $confirmationGovernanceContext;

                $confirmation->source_context =
                    $confirmationSourceContext;

                /*
                |--------------------------------------------------------------------------
                | Authorized Human Confirmation Attribution
                |--------------------------------------------------------------------------
                */

                $confirmation->confirmed_by = null;
                $confirmation->confirmer_role = null;
                $confirmation->confirmed_at = null;

                $confirmation->confirmation_made_by_authorized_human = false;

                $confirmation->governance_decision_confirmation_completed =
                    false;

                /*
                |--------------------------------------------------------------------------
                | Controlled Activation
                |--------------------------------------------------------------------------
                */

                $confirmation->controlled_activation_status =
                    'NOT_AUTHORIZED_FOR_ACTIVATION';

                $confirmation->controlled_activation_authorized = false;

                $confirmation->controlled_activation_authorization_code = null;

                $confirmation->activation_authorized_by = null;

                $confirmation->activation_authorizer_role = null;

                $confirmation->activation_authorized_at = null;

                /*
                |--------------------------------------------------------------------------
                | Automatic Authority Prohibitions
                |--------------------------------------------------------------------------
                */

                $confirmation->automatic_confirmation_allowed = false;
                $confirmation->automatic_final_decision_allowed = false;
                $confirmation->automatic_approval_allowed = false;
                $confirmation->automatic_rejection_allowed = false;
                $confirmation->automatic_conditional_approval_allowed = false;
                $confirmation->automatic_deferral_allowed = false;
                $confirmation->automatic_risk_acceptance_allowed = false;
                $confirmation->automatic_activation_allowed = false;
                $confirmation->automatic_condition_resolution_allowed = false;
                $confirmation->automatic_restriction_removal_allowed = false;
                $confirmation->automatic_evidence_validation_allowed = false;
                $confirmation->automatic_execution_allowed = false;
                $confirmation->automatic_change_allowed = false;
                $confirmation->automatic_deployment_allowed = false;
                $confirmation->automatic_rollback_allowed = false;
                $confirmation->automatic_clinical_action_allowed = false;

                /*
                |--------------------------------------------------------------------------
                | Mandatory Human Governance
                |--------------------------------------------------------------------------
                */

                $confirmation->human_review_required = true;

                $confirmation->governance_validation_required = true;

                $confirmation->authorized_human_confirmation_required = true;

                $confirmation->authorized_human_activation_required = true;

                $confirmation->save();

                return $confirmation;
            });

            return $this->buildPreparedResponse(
                $confirmation,
                $sourceDecision
            );
        } catch (Throwable $exception) {
            return [
                'prepared' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_CONFIRMATION_PREPARATION_FAILED',
                'message' => 'Strategic plan governance decision confirmation preparation failed.',
                'error' => $exception->getMessage(),
                'strategic_plan_final_governance_decision_id' =>
                    $strategicPlanFinalGovernanceDecisionId,
                'confirmation_guardrails' => $this->guardrails(),
            ];
        }
    }

    /**
     * Resolve the Step 67 final-governance-decision source.
     */
    private function resolveSourceDecision(
        ?int $strategicPlanFinalGovernanceDecisionId
    ): ?AIGovernanceStrategicPlanFinalGovernanceDecision {
        $query = AIGovernanceStrategicPlanFinalGovernanceDecision::query();

        if ($strategicPlanFinalGovernanceDecisionId !== null) {
            return $query->find($strategicPlanFinalGovernanceDecisionId);
        }

        return $query->latest('id')->first();
    }

    /**
     * Determine whether Step 67 contains an actual final governance decision.
     */
    private function hasRecordedFinalGovernanceDecision(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision
    ): bool {
        return filled($decision->final_governance_decision)
            && (bool) ($decision->decision_made_by_authorized_human ?? false);
    }

    /**
     * Normalize JSON/array values.
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

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Normalize numeric scores.
     */
    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            max(0, min(100, (float) $value)),
            2
        );
    }

    /**
     * Build confirmation conditions.
     */
    private function buildConfirmationConditions(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $sourceFinalGovernanceConfirmationCompleted,
        array $sourceConditions,
        array $sourceRestrictions
    ): array {
        $conditions = [];

        if (!$sourceFinalDecisionRecorded) {
            $conditions[] = [
                'condition_code' =>
                    'FINAL_GOVERNANCE_DECISION_REQUIRED_BEFORE_CONFIRMATION',

                'condition_type' =>
                    'FINAL_GOVERNANCE_DECISION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'An explicitly authorized human final governance decision must be recorded before governance decision confirmation can be completed.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $conditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_FINAL_DECISION_ATTRIBUTION_REQUIRED',

                'condition_type' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'The source final governance decision must be explicitly attributable to an authorized human governance decision-maker before confirmation progression.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $conditions[] = [
                'condition_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_COMPLETION_REQUIRED',

                'condition_type' =>
                    'GOVERNANCE_VALIDATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Required source governance validation remains incomplete and requires authorized human governance completion before unrestricted confirmation progression.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        $openSourceConditions = collect($sourceConditions)
            ->filter(function ($condition) {
                if (!is_array($condition)) {
                    return false;
                }

                $status = strtoupper(
                    (string) ($condition['condition_status'] ?? 'OPEN')
                );

                return !in_array(
                    $status,
                    [
                        'RESOLVED',
                        'CLOSED',
                        'SATISFIED',
                        'COMPLETED',
                    ],
                    true
                );
            })
            ->count();

        if ($openSourceConditions > 0) {
            $conditions[] = [
                'condition_code' =>
                    'SOURCE_FINAL_GOVERNANCE_CONDITIONS_REMAIN',

                'condition_type' =>
                    'GOVERNANCE_CONDITION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    $openSourceConditions .
                    ' source final-governance decision condition(s) remain unresolved and require authorized human governance treatment.',

                'value' =>
                    $openSourceConditions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        $materialSourceRestrictions = collect($sourceRestrictions)
            ->filter(function ($restriction) {
                if (!is_array($restriction)) {
                    return false;
                }

                $severity = strtoupper(
                    (string) ($restriction['severity'] ?? '')
                );

                return in_array(
                    $severity,
                    [
                        'CRITICAL',
                        'HIGH',
                        'MATERIAL',
                    ],
                    true
                );
            })
            ->count();

        if ($materialSourceRestrictions > 0) {
            $conditions[] = [
                'condition_code' =>
                    'MATERIAL_SOURCE_GOVERNANCE_RESTRICTIONS_REMAIN',

                'condition_type' =>
                    'GOVERNANCE_RESTRICTION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    $materialSourceRestrictions .
                    ' material source governance restriction(s) remain and require explicit authorized human governance treatment.',

                'value' =>
                    $materialSourceRestrictions,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        $sourceRiskScore = $this->score(
            $decision->final_decision_risk_score
        );

        if ($sourceRiskScore >= 70) {
            $conditions[] = [
                'condition_code' =>
                    'HIGH_CONFIRMATION_GOVERNANCE_RISK',

                'condition_type' =>
                    'RISK',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Source final governance decision risk remains ' .
                    ($decision->final_decision_risk_level ?? 'HIGH') .
                    ' with score ' .
                    $sourceRiskScore .
                    ' and requires explicit authorized human consideration before confirmation.',

                'value' =>
                    $sourceRiskScore,

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    true,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Explicit Human Confirmation Requirement
        |--------------------------------------------------------------------------
        |
        | Even a clean source package does not automatically confirm itself.
        |
        */

        if (!$sourceFinalGovernanceConfirmationCompleted) {
            $conditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REQUIRED',

                'condition_type' =>
                    'HUMAN_CONFIRMATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'An explicitly authorized human governance confirmation is required before controlled activation can be considered.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,

                'blocks_governance_confirmation' =>
                    false,

                'blocks_controlled_activation' =>
                    true,
            ];
        }

        return array_values($conditions);
    }

    /**
     * Build confirmation restrictions.
     */
    private function buildConfirmationRestrictions(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $sourceFinalGovernanceConfirmationCompleted,
        array $sourceConditions,
        array $sourceRestrictions
    ): array {
        $restrictions = [];

        if (!$sourceFinalDecisionRecorded) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_FINAL_GOVERNANCE_DECISION_NOT_RECORDED',

                'restriction_type' =>
                    'FINAL_GOVERNANCE_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance confirmation remains restricted because an authorized-human final governance decision has not been recorded.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_INCOMPLETE',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance confirmation remains restricted until final governance decision attribution to an authorized human is complete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceGovernanceValidationCompleted) {
            $restrictions[] = [
                'restriction_code' =>
                    'SOURCE_GOVERNANCE_VALIDATION_INCOMPLETE',

                'restriction_type' =>
                    'GOVERNANCE_VALIDATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Governance confirmation remains restricted while required source governance validation is incomplete.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        $openSourceConditions = collect($sourceConditions)
            ->filter(function ($condition) {
                if (!is_array($condition)) {
                    return false;
                }

                $status = strtoupper(
                    (string) ($condition['condition_status'] ?? 'OPEN')
                );

                return !in_array(
                    $status,
                    [
                        'RESOLVED',
                        'CLOSED',
                        'SATISFIED',
                        'COMPLETED',
                    ],
                    true
                );
            })
            ->count();

        if ($openSourceConditions > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'UNRESOLVED_SOURCE_FINAL_GOVERNANCE_CONDITIONS',

                'restriction_type' =>
                    'GOVERNANCE_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $openSourceConditions,

                'message' =>
                    $openSourceConditions .
                    ' source final-governance decision condition(s) remain unresolved.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        $materialSourceRestrictions = collect($sourceRestrictions)
            ->filter(function ($restriction) {
                if (!is_array($restriction)) {
                    return false;
                }

                $severity = strtoupper(
                    (string) ($restriction['severity'] ?? '')
                );

                return in_array(
                    $severity,
                    [
                        'CRITICAL',
                        'HIGH',
                        'MATERIAL',
                    ],
                    true
                );
            })
            ->count();

        if ($materialSourceRestrictions > 0) {
            $restrictions[] = [
                'restriction_code' =>
                    'MATERIAL_SOURCE_FINAL_GOVERNANCE_RESTRICTIONS',

                'restriction_type' =>
                    'GOVERNANCE_RESTRICTION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $materialSourceRestrictions,

                'message' =>
                    $materialSourceRestrictions .
                    ' material source final-governance restriction(s) remain active.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        $riskScore = $this->score(
            $decision->final_decision_risk_score
        );

        if ($riskScore >= 70) {
            $restrictions[] = [
                'restriction_code' =>
                    'HIGH_GOVERNANCE_CONFIRMATION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $riskScore,

                'message' =>
                    'Governance confirmation remains subject to elevated authorized-human oversight because source decision risk is ' .
                    ($decision->final_decision_risk_level ?? 'HIGH') .
                    ' with score ' .
                    $riskScore .
                    '.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        if (!$sourceFinalGovernanceConfirmationCompleted) {
            $restrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_NOT_COMPLETED',

                'restriction_type' =>
                    'HUMAN_CONFIRMATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Controlled activation remains prohibited until an explicitly authorized human governance confirmation has been completed.',

                'requires_authorized_human_governance' =>
                    true,

                'automatic_restriction_removal_allowed' =>
                    false,
            ];
        }

        return array_values($restrictions);
    }

    /**
     * Calculate condition pressure.
     */
    private function calculateConditionPressureScore(
        array $conditions
    ): float {
        if (empty($conditions)) {
            return 0.0;
        }

        $weights = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $status = strtoupper(
                (string) ($condition['condition_status'] ?? 'OPEN')
            );

            if (in_array(
                $status,
                [
                    'RESOLVED',
                    'CLOSED',
                    'SATISFIED',
                    'COMPLETED',
                ],
                true
            )) {
                $weights[] = 0.0;
                continue;
            }

            $priority = strtoupper(
                (string) ($condition['priority_level'] ?? 'MODERATE')
            );

            $weights[] = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE', 'MEDIUM' => 50.0,
                'LOW' => 25.0,
                default => 50.0,
            };
        }

        if (empty($weights)) {
            return 0.0;
        }

        return round(
            array_sum($weights) / count($weights),
            2
        );
    }

    /**
     * Calculate restriction pressure.
     */
    private function calculateRestrictionPressureScore(
        array $restrictions
    ): float {
        if (empty($restrictions)) {
            return 0.0;
        }

        $weights = [];

        foreach ($restrictions as $restriction) {
            if (!is_array($restriction)) {
                continue;
            }

            $severity = strtoupper(
                (string) ($restriction['severity'] ?? 'MODERATE')
            );

            $weights[] = match ($severity) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MATERIAL' => 75.0,
                'MODERATE', 'MEDIUM' => 50.0,
                'LOW' => 25.0,
                default => 50.0,
            };
        }

        if (empty($weights)) {
            return 0.0;
        }

        return round(
            array_sum($weights) / count($weights),
            2
        );
    }

    /**
     * Calculate confirmation readiness.
     *
     * Scores are informational only.
     */
    private function calculateConfirmationReadinessScore(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $sourceFinalGovernanceConfirmationCompleted,
        array $confirmationConditions,
        array $confirmationRestrictions,
        float $combinedResolutionScore
    ): float {
        $score = 0.0;

        if ($sourceFinalDecisionRecorded) {
            $score += 25.0;
        }

        if ($sourceDecisionMadeByAuthorizedHuman) {
            $score += 20.0;
        }

        if ($sourceGovernanceValidationCompleted) {
            $score += 20.0;
        }

        if ($sourceFinalGovernanceConfirmationCompleted) {
            $score += 10.0;
        }

        $score += ($combinedResolutionScore * 0.15);

        $sourceReadinessScore = $this->score(
            $decision->final_decision_readiness_score
        );

        $score += ($sourceReadinessScore * 0.10);

        $blockingConditions = collect($confirmationConditions)
            ->filter(
                fn ($condition) =>
                    is_array($condition)
                    && ($condition['blocks_governance_confirmation'] ?? false)
            )
            ->count();

        $materialRestrictions = collect($confirmationRestrictions)
            ->filter(function ($restriction) {
                if (!is_array($restriction)) {
                    return false;
                }

                return in_array(
                    strtoupper(
                        (string) ($restriction['severity'] ?? '')
                    ),
                    [
                        'CRITICAL',
                        'HIGH',
                        'MATERIAL',
                    ],
                    true
                );
            })
            ->count();

        $score -= min(20, $blockingConditions * 4);
        $score -= min(15, $materialRestrictions * 2);

        return round(
            max(0, min(100, $score)),
            2
        );
    }

    /**
     * Determine confirmation readiness classification.
     */
    private function determineConfirmationReadiness(
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $sourceFinalGovernanceConfirmationCompleted,
        array $confirmationConditions,
        array $confirmationRestrictions,
        float $score
    ): string {
        if (!$sourceFinalDecisionRecorded) {
            return 'AWAITING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            return 'AWAITING_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION';
        }

        if (!$sourceGovernanceValidationCompleted) {
            return 'AWAITING_GOVERNANCE_VALIDATION_COMPLETION';
        }

        $blockingConditions = collect($confirmationConditions)
            ->filter(
                fn ($condition) =>
                    is_array($condition)
                    && ($condition['blocks_governance_confirmation'] ?? false)
            )
            ->count();

        if ($blockingConditions > 0) {
            return 'RESTRICTED_GOVERNANCE_CONFIRMATION_READINESS';
        }

        if ($sourceFinalGovernanceConfirmationCompleted) {
            return 'SOURCE_CONFIRMATION_ALREADY_RECORDED';
        }

        if (!empty($confirmationRestrictions) || $score < 75) {
            return 'CONDITIONAL_AUTHORIZED_HUMAN_CONFIRMATION_READINESS';
        }

        return 'READY_FOR_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_REVIEW';
    }

    /**
     * Calculate confirmation risk.
     *
     * Risk does not authorize or prohibit an action by itself.
     */
    private function calculateConfirmationRiskScore(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        array $confirmationConditions,
        array $confirmationRestrictions,
        float $pressureScore
    ): float {
        $sourceRiskScore = $this->score(
            $decision->final_decision_risk_score
        );

        $risk = ($sourceRiskScore * 0.55)
            + ($pressureScore * 0.30);

        if (!$sourceFinalDecisionRecorded) {
            $risk += 5.0;
        }

        if (!$sourceDecisionMadeByAuthorizedHuman) {
            $risk += 5.0;
        }

        if (!$sourceGovernanceValidationCompleted) {
            $risk += 5.0;
        }

        $blockingConditionCount = collect($confirmationConditions)
            ->filter(
                fn ($condition) =>
                    is_array($condition)
                    && ($condition['blocks_governance_confirmation'] ?? false)
            )
            ->count();

        $criticalRestrictionCount = collect($confirmationRestrictions)
            ->filter(
                fn ($restriction) =>
                    is_array($restriction)
                    && strtoupper(
                        (string) ($restriction['severity'] ?? '')
                    ) === 'CRITICAL'
            )
            ->count();

        $risk += min(5, $blockingConditionCount);
        $risk += min(5, $criticalRestrictionCount * 2);

        return round(
            max(0, min(100, $risk)),
            2
        );
    }

    /**
     * Determine confirmation risk level.
     */
    private function determineConfirmationRiskLevel(
        float $score
    ): string {
        return match (true) {
            $score >= 85 =>
                'CRITICAL_GOVERNANCE_CONFIRMATION_RISK',

            $score >= 70 =>
                'HIGH_GOVERNANCE_CONFIRMATION_RISK',

            $score >= 50 =>
                'ELEVATED_GOVERNANCE_CONFIRMATION_RISK',

            $score >= 30 =>
                'MODERATE_GOVERNANCE_CONFIRMATION_RISK',

            default =>
                'CONTROLLED_GOVERNANCE_CONFIRMATION_RISK',
        };
    }

    /**
     * Build confirmation findings.
     */
    private function buildConfirmationFindings(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision,
        bool $sourceFinalDecisionRecorded,
        bool $sourceDecisionMadeByAuthorizedHuman,
        bool $sourceGovernanceValidationCompleted,
        bool $sourceFinalGovernanceConfirmationCompleted,
        string $confirmationReadiness,
        float $confirmationReadinessScore,
        string $confirmationRiskLevel,
        float $confirmationRiskScore,
        float $conditionResolutionScore,
        float $evidenceResolutionScore,
        float $combinedResolutionScore,
        float $conditionPressureScore,
        float $restrictionPressureScore,
        float $combinedPressureScore,
        array $confirmationConditions,
        array $confirmationRestrictions,
        array $sourceFindings
    ): array {
        $findings = [
            'Governance decision confirmation preparation is based on Step 67 final governance decision record ' .
                $decision->id .
                '.',

            'Source final governance decision status is ' .
                ($decision->final_governance_decision_status ?? 'UNKNOWN') .
                '.',

            'Source prepared strategic plan decision is ' .
                ($decision->prepared_decision ?? 'NOT_AVAILABLE') .
                '.',

            'Source final governance decision is ' .
                ($decision->final_governance_decision ?? 'NOT_RECORDED') .
                '.',

            'Source final governance decision recorded is ' .
                ($sourceFinalDecisionRecorded ? 'YES' : 'NO') .
                '.',

            'Source final governance decision made by explicitly authorized human is ' .
                ($sourceDecisionMadeByAuthorizedHuman ? 'YES' : 'NO') .
                '.',

            'Source governance validation completed is ' .
                ($sourceGovernanceValidationCompleted ? 'YES' : 'NO') .
                '.',

            'Source final governance confirmation completed is ' .
                ($sourceFinalGovernanceConfirmationCompleted ? 'YES' : 'NO') .
                '.',

            count($confirmationConditions) .
                ' Step 68 confirmation condition(s) are represented.',

            count($confirmationRestrictions) .
                ' Step 68 confirmation restriction(s) are represented.',

            'Current condition resolution score is ' .
                $conditionResolutionScore .
                '.',

            'Current evidence resolution score is ' .
                $evidenceResolutionScore .
                '.',

            'Current combined resolution score is ' .
                $combinedResolutionScore .
                '.',

            'Current condition pressure score is ' .
                $conditionPressureScore .
                '.',

            'Current restriction pressure score is ' .
                $restrictionPressureScore .
                '.',

            'Current combined condition/restriction pressure score is ' .
                $combinedPressureScore .
                '.',

            'Current governance confirmation readiness is ' .
                $confirmationReadiness .
                ' with score ' .
                $confirmationReadinessScore .
                '.',

            'Current governance confirmation risk is ' .
                $confirmationRiskLevel .
                ' with score ' .
                $confirmationRiskScore .
                '.',

            'Controlled activation authorization is NOT granted by confirmation preparation.',

            'Step 68.2 confirmation preparation does not make or record governance confirmation.',

            'Step 68.2 confirmation preparation does not authorize or perform strategic plan activation.',
        ];

        if (!empty($sourceFindings)) {
            $findings[] =
                count($sourceFindings) .
                ' Step 67 source governance finding(s) remain traceable through the confirmation package.';
        }

        return $findings;
    }

    /**
     * Generate confirmation identity.
     */
    private function generateConfirmationCode(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision
    ): string {
        return sprintf(
            'GOV-STRATEGIC-PLAN-CONFIRMATION-%d-%s',
            $decision->id,
            now()->format('YmdHis')
        );
    }

    /**
     * Response for newly prepared record.
     */
    private function buildPreparedResponse(
        AIGovernanceStrategicPlanGovernanceDecisionConfirmation $confirmation,
        AIGovernanceStrategicPlanFinalGovernanceDecision $sourceDecision
    ): array {
        return [
            'prepared' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_PREPARED',

            'message' =>
                'AI governance strategic plan decision confirmation record prepared successfully for explicitly authorized human governance confirmation review.',

            'governance_decision_confirmation' =>
                $this->confirmationSummary($confirmation),

            'confirmation_context' => [
                'strategic_plan_final_governance_decision_id' =>
                    $sourceDecision->id,

                'final_governance_decision_code' =>
                    $sourceDecision->final_governance_decision_code,

                'source_final_governance_decision_status' =>
                    $sourceDecision->final_governance_decision_status,

                'source_final_governance_decision' =>
                    $sourceDecision->final_governance_decision,

                'source_final_governance_decision_recorded' =>
                    (bool) $confirmation->source_final_governance_decision_recorded,

                'source_decision_made_by_authorized_human' =>
                    (bool) $confirmation->source_decision_made_by_authorized_human,

                'source_governance_validation_completed' =>
                    (bool) $confirmation->source_governance_validation_completed,

                'confirmation_readiness' =>
                    $confirmation->confirmation_readiness,

                'confirmation_readiness_score' =>
                    $confirmation->confirmation_readiness_score,

                'confirmation_risk_level' =>
                    $confirmation->confirmation_risk_level,

                'confirmation_risk_score' =>
                    $confirmation->confirmation_risk_score,

                'condition_resolution_score' =>
                    $confirmation->condition_resolution_score,

                'evidence_resolution_score' =>
                    $confirmation->evidence_resolution_score,

                'combined_resolution_score' =>
                    $confirmation->combined_resolution_score,

                'condition_pressure_score' =>
                    $confirmation->condition_pressure_score,

                'restriction_pressure_score' =>
                    $confirmation->restriction_pressure_score,

                'combined_condition_restriction_pressure_score' =>
                    $confirmation->combined_condition_restriction_pressure_score,

                'confirmation_condition_count' =>
                    count($confirmation->confirmation_conditions ?? []),

                'confirmation_restriction_count' =>
                    count($confirmation->confirmation_restrictions ?? []),

                'controlled_activation_authorized' =>
                    false,

                'governance_decision_confirmation_completed' =>
                    false,
            ],

            'confirmation_conditions' =>
                $confirmation->confirmation_conditions ?? [],

            'confirmation_restrictions' =>
                $confirmation->confirmation_restrictions ?? [],

            'validated_evidence' =>
                $confirmation->validated_evidence ?? [],

            'confirmation_findings' =>
                $confirmation->confirmation_findings ?? [],

            'review_context' =>
                $confirmation->review_context ?? [],

            'validation_context' =>
                $confirmation->validation_context ?? [],

            'governance_context' =>
                $confirmation->governance_context ?? [],

            'source_context' =>
                $confirmation->source_context ?? [],

            'confirmation_guardrails' =>
                $this->guardrails(),
        ];
    }

    /**
     * Response when record was already prepared.
     */
    private function buildExistingResponse(
        AIGovernanceStrategicPlanGovernanceDecisionConfirmation $confirmation,
        AIGovernanceStrategicPlanFinalGovernanceDecision $sourceDecision
    ): array {
        return [
            'prepared' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONFIRMATION_ALREADY_PREPARED',

            'message' =>
                'The strategic plan governance decision confirmation record has already been prepared for this Step 67 final governance decision.',

            'governance_decision_confirmation' =>
                $this->confirmationSummary($confirmation),

            'confirmation_context' => [
                'strategic_plan_final_governance_decision_id' =>
                    $sourceDecision->id,

                'final_governance_decision_code' =>
                    $sourceDecision->final_governance_decision_code,

                'source_final_governance_decision_recorded' =>
                    (bool) $confirmation->source_final_governance_decision_recorded,

                'source_decision_made_by_authorized_human' =>
                    (bool) $confirmation->source_decision_made_by_authorized_human,

                'source_governance_validation_completed' =>
                    (bool) $confirmation->source_governance_validation_completed,

                'confirmation_readiness' =>
                    $confirmation->confirmation_readiness,

                'confirmation_readiness_score' =>
                    $confirmation->confirmation_readiness_score,

                'confirmation_risk_level' =>
                    $confirmation->confirmation_risk_level,

                'confirmation_risk_score' =>
                    $confirmation->confirmation_risk_score,

                'controlled_activation_authorized' =>
                    (bool) $confirmation->controlled_activation_authorized,

                'governance_decision_confirmation_completed' =>
                    (bool) $confirmation->governance_decision_confirmation_completed,
            ],

            'confirmation_guardrails' =>
                $this->guardrails(),
        ];
    }

    /**
     * Compact confirmation record response.
     */
    private function confirmationSummary(
        AIGovernanceStrategicPlanGovernanceDecisionConfirmation $confirmation
    ): array {
        return [
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

            'confirmation_status' =>
                $confirmation->confirmation_status,

            'confirmation_mode' =>
                $confirmation->confirmation_mode,

            'prepared_decision' =>
                $confirmation->prepared_decision,

            'source_final_governance_decision' =>
                $confirmation->source_final_governance_decision,

            'source_final_governance_decision_recorded' =>
                (bool) $confirmation->source_final_governance_decision_recorded,

            'source_decision_made_by_authorized_human' =>
                (bool) $confirmation->source_decision_made_by_authorized_human,

            'source_governance_validation_completed' =>
                (bool) $confirmation->source_governance_validation_completed,

            'governance_confirmation_decision' =>
                $confirmation->governance_confirmation_decision,

            'confirmation_outcome' =>
                $confirmation->confirmation_outcome,

            'confirmation_outcome_status' =>
                $confirmation->confirmation_outcome_status,

            'confirmation_readiness' =>
                $confirmation->confirmation_readiness,

            'confirmation_readiness_score' =>
                $confirmation->confirmation_readiness_score,

            'confirmation_risk_level' =>
                $confirmation->confirmation_risk_level,

            'confirmation_risk_score' =>
                $confirmation->confirmation_risk_score,

            'condition_resolution_score' =>
                $confirmation->condition_resolution_score,

            'evidence_resolution_score' =>
                $confirmation->evidence_resolution_score,

            'combined_resolution_score' =>
                $confirmation->combined_resolution_score,

            'condition_pressure_score' =>
                $confirmation->condition_pressure_score,

            'restriction_pressure_score' =>
                $confirmation->restriction_pressure_score,

            'combined_condition_restriction_pressure_score' =>
                $confirmation->combined_condition_restriction_pressure_score,

            'confirmation_made_by_authorized_human' =>
                (bool) $confirmation->confirmation_made_by_authorized_human,

            'governance_decision_confirmation_completed' =>
                (bool) $confirmation->governance_decision_confirmation_completed,

            'controlled_activation_status' =>
                $confirmation->controlled_activation_status,

            'controlled_activation_authorized' =>
                (bool) $confirmation->controlled_activation_authorized,
        ];
    }

    /**
     * Step 68.2 authority-isolation guardrails.
     */
    private function guardrails(): array
    {
        return [
            'governance_decision_confirmation_preparation_enabled' =>
                true,

            'prepared_record_is_governance_confirmation' =>
                false,

            'ai_makes_governance_confirmation' =>
                false,

            'ai_records_governance_confirmation' =>
                false,

            'ai_confirms_final_governance_decision' =>
                false,

            'ai_makes_final_governance_decision' =>
                false,

            'ai_changes_final_governance_decision' =>
                false,

            'ai_approves_strategic_plan' =>
                false,

            'ai_rejects_strategic_plan' =>
                false,

            'ai_conditionally_approves_strategic_plan' =>
                false,

            'ai_defers_strategic_plan' =>
                false,

            'ai_accepts_governance_risk' =>
                false,

            'ai_authorizes_controlled_activation' =>
                false,

            'ai_activates_strategic_plan' =>
                false,

            'confirmation_preparation_changes_source_final_decision' =>
                false,

            'confirmation_preparation_changes_governance_validation' =>
                false,

            'confirmation_preparation_changes_plan_status' =>
                false,

            'confirmation_preparation_changes_action_state' =>
                false,

            'confirmation_preparation_resolves_conditions' =>
                false,

            'confirmation_preparation_waives_conditions' =>
                false,

            'confirmation_preparation_removes_restrictions' =>
                false,

            'confirmation_preparation_resolves_dependencies' =>
                false,

            'confirmation_preparation_validates_evidence' =>
                false,

            'confirmation_readiness_score_authorizes_confirmation' =>
                false,

            'confirmation_readiness_score_authorizes_activation' =>
                false,

            'confirmation_risk_score_authorizes_confirmation' =>
                false,

            'confirmation_risk_score_authorizes_activation' =>
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

            'confirmation_preparation_authorizes_ai_change' =>
                false,

            'confirmation_preparation_authorizes_execution' =>
                false,

            'confirmation_preparation_authorizes_deployment' =>
                false,

            'confirmation_preparation_authorizes_rollback' =>
                false,

            'confirmation_preparation_authorizes_clinical_action' =>
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

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'authorized_human_confirmation_required' =>
                true,

            'authorized_human_activation_required' =>
                true,

            'message' =>
                'Step 68.2 prepares a structured strategic plan governance-decision confirmation record from Step 67 final-governance-decision intelligence for explicitly authorized human governance review. Preparation does not make, record, or confirm a final governance decision; approve, reject, conditionally approve, defer, or accept governance risk; authorize or perform controlled activation; resolve or waive conditions; remove restrictions; validate evidence; alter upstream governance decisions or governance-validation state; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Governance confirmation and controlled activation authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}