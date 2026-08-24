<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;
use App\Models\AIGovernanceStrategicPlanHumanDecision;
use Illuminate\Support\Facades\DB;

class AIGovernanceStrategicPlanHumanDecisionPreparationEngine
{
    public function prepare(?int $strategicPlanDecisionId = null): array
    {
        $strategicPlanDecision = $strategicPlanDecisionId
            ? AIGovernanceStrategicPlanDecision::find($strategicPlanDecisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$strategicPlanDecision) {
            return [
                'prepared' => false,
                'status' => 'NO_STRATEGIC_PLAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision package is available for human decision preparation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 64 intelligence
        |--------------------------------------------------------------------------
        |
        | Step 65 does not invent a new strategic decision context.
        | It carries forward the already-governed Step 64 decision package and
        | prepares it for an authorized human decision.
        |
        */

        $decisionState = app(
            AIGovernanceStrategicPlanDecisionStateIntelligenceEngine::class
        )->analyze($strategicPlanDecision->id);

        $conditionEvidence = app(
            AIGovernanceStrategicPlanDecisionConditionEvidenceIntelligenceEngine::class
        )->analyze($strategicPlanDecision->id);

        $eligibilityRisk = app(
            AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine::class
        )->analyze($strategicPlanDecision->id);

        $recommendation = app(
            AIGovernanceStrategicPlanHumanDecisionRecommendationIntelligenceEngine::class
        )->analyze($strategicPlanDecision->id);

        $executive = app(
            AIGovernanceExecutiveStrategicPlanDecisionIntelligenceEngine::class
        )->analyze($strategicPlanDecision->id);

        $finalValidation = app(
            AIGovernanceStrategicPlanDecisionFinalValidationEngine::class
        )->analyze($strategicPlanDecision->id);

        /*
        |--------------------------------------------------------------------------
        | Step 64 closure validation
        |--------------------------------------------------------------------------
        */

        if (($finalValidation['step_64_ready_for_closure'] ?? false) !== true) {
            return [
                'prepared' => false,
                'status' => 'STEP_64_NOT_READY_FOR_HUMAN_DECISION_PREPARATION',
                'message' => 'Step 64 strategic plan decision intelligence has not passed the required final validation.',
                'strategic_plan_decision_id' => $strategicPlanDecision->id,
                'validation_status' => $finalValidation['validation_status'] ?? null,
                'critical_issues' => $finalValidation['critical_issues'] ?? [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate active human-decision preparation records
        |--------------------------------------------------------------------------
        |
        | There should normally be one active human-decision record for one
        | strategic plan decision package.
        |
        */

        $existingHumanDecision = AIGovernanceStrategicPlanHumanDecision::query()
            ->where(
                'strategic_plan_decision_id',
                $strategicPlanDecision->id
            )
            ->whereIn('human_decision_status', [
                'PENDING_AUTHORIZED_HUMAN_DECISION',
                'UNDER_AUTHORIZED_HUMAN_REVIEW',
                'PENDING_GOVERNANCE_VALIDATION',
            ])
            ->latest('id')
            ->first();

        if ($existingHumanDecision) {
            return $this->buildExistingResponse(
                $existingHumanDecision,
                $strategicPlanDecision
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Extract governed intelligence contexts
        |--------------------------------------------------------------------------
        */

        $decisionStateContext =
            $decisionState['decision_state'] ?? [];

        $conditionEvidenceState =
            $conditionEvidence['condition_evidence_state'] ?? [];

        $eligibilityState =
            $eligibilityRisk['human_decision_eligibility_state'] ?? [];

        $humanDecisionRiskState =
            $eligibilityRisk['human_decision_risk_state'] ?? [];

        $eligibilitySummary =
            $eligibilityRisk['eligibility_summary'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveState =
            $executive['executive_strategic_plan_decision_state'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Prepared decision
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | This is the Step 64 prepared/recommended decision classification.
        | It is NOT the final human governance decision.
        |
        */

        $preparedDecision =
            $strategicPlanDecision->decision
            ?? $decisionStateContext['prepared_decision']
            ?? 'REQUIRES_AUTHORIZED_HUMAN_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Human decision code
        |--------------------------------------------------------------------------
        */

        $humanDecisionCode =
            'GOV-HUMAN-STRATEGIC-PLAN-DECISION-'
            .$strategicPlanDecision->id
            .'-'
            .now()->format('YmdHis');

        /*
        |--------------------------------------------------------------------------
        | Decision conditions
        |--------------------------------------------------------------------------
        */

        $decisionConditions =
            $strategicPlanDecision->decision_conditions ?? [];

        if (!is_array($decisionConditions)) {
            $decisionConditions = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence context
        |--------------------------------------------------------------------------
        */

        $requiredEvidence =
            $strategicPlanDecision->required_evidence ?? [];

        if (!is_array($requiredEvidence)) {
            $requiredEvidence = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Condition resolution context
        |--------------------------------------------------------------------------
        */

        $conditionResolutionContext = [
            'condition_evidence_status' =>
                $conditionEvidenceState['condition_evidence_status'] ?? null,

            'decision_block_status' =>
                $conditionEvidenceState['decision_block_status'] ?? null,

            'condition_pressure_score' =>
                $conditionEvidenceState['condition_pressure_score'] ?? null,

            'condition_resolution_score' =>
                $conditionEvidenceState['condition_resolution_score'] ?? null,

            'evidence_readiness_score' =>
                $conditionEvidenceState['evidence_readiness_score'] ?? null,

            'decision_evidence_readiness_score' =>
                $conditionEvidenceState['decision_evidence_readiness_score'] ?? null,

            'human_resolution_readiness' =>
                $conditionEvidenceState['human_resolution_readiness'] ?? null,

            'open_conditions' =>
                $eligibilitySummary['open_conditions'] ?? 0,

            'blocking_conditions' =>
                $eligibilitySummary['blocking_conditions'] ?? 0,

            'constraining_conditions' =>
                $eligibilitySummary['constraining_conditions'] ?? 0,

            'critical_open_conditions' =>
                $eligibilitySummary['critical_open_conditions'] ?? 0,

            'high_open_conditions' =>
                $eligibilitySummary['high_open_conditions'] ?? 0,

            'automatic_condition_resolution_allowed' => false,

            'human_resolution_required' =>
                (($eligibilitySummary['blocking_conditions'] ?? 0) > 0)
                || (($eligibilitySummary['constraining_conditions'] ?? 0) > 0),
        ];

        /*
        |--------------------------------------------------------------------------
        | Evidence governance context
        |--------------------------------------------------------------------------
        */

        $evidenceContext = [
            'requirements' => $requiredEvidence,

            'total_evidence_items' =>
                count($requiredEvidence),

            'outstanding_evidence_items' =>
                $eligibilitySummary['outstanding_evidence_items'] ?? 0,

            'decision_blocking_evidence_items' =>
                $eligibilitySummary['decision_blocking_evidence_items'] ?? 0,

            'critical_outstanding_evidence_items' =>
                $eligibilitySummary['critical_outstanding_evidence_items'] ?? 0,

            'high_outstanding_evidence_items' =>
                $eligibilitySummary['high_outstanding_evidence_items'] ?? 0,

            'dominant_evidence_requirement' =>
                $eligibilityRisk['dominant_evidence_requirement'] ?? null,

            'automatic_evidence_validation_allowed' => false,

            'human_validation_required' => true,
        ];

        /*
        |--------------------------------------------------------------------------
        | Human review context
        |--------------------------------------------------------------------------
        */

        $reviewContext = [
            'review_state' => 'AWAITING_AUTHORIZED_HUMAN_DECISION',

            'human_review_required' => true,

            'governance_validation_required' => true,

            'human_review_eligibility' =>
                $eligibilityState['human_review_eligibility'] ?? null,

            'human_approval_eligibility' =>
                $eligibilityState['human_approval_eligibility'] ?? null,

            'human_decision_eligibility' =>
                $eligibilityState['human_decision_eligibility'] ?? null,

            'human_decision_readiness' =>
                $eligibilityState['human_decision_readiness'] ?? null,

            'human_management_attention_required' =>
                $eligibilityState['human_management_attention_required']
                ?? true,

            'management_escalation_recommended' =>
                $executiveState['management_escalation_recommended']
                ?? false,

            'immediate_escalation_required' =>
                $executiveState['immediate_escalation_required']
                ?? false,

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'top_recommendation' =>
                $recommendation['top_recommendation']
                ?? null,

            'final_human_decision_recorded' => false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance context
        |--------------------------------------------------------------------------
        */

        $governanceContext = [
            'step_64_validation_status' =>
                $finalValidation['validation_status'] ?? null,

            'step_64_ready_for_closure' =>
                $finalValidation['step_64_ready_for_closure'] ?? false,

            'governance_integrity_intact' =>
                $humanDecisionRiskState['governance_integrity_intact']
                ?? false,

            'prepared_decision' =>
                $preparedDecision,

            'decision_review_readiness' =>
                $decisionStateContext['decision_review_readiness']
                ?? null,

            'decision_review_readiness_score' =>
                $decisionStateContext['decision_review_readiness_score']
                ?? null,

            'human_review_eligibility' =>
                $eligibilityState['human_review_eligibility']
                ?? null,

            'human_approval_eligibility' =>
                $eligibilityState['human_approval_eligibility']
                ?? null,

            'human_decision_eligibility' =>
                $eligibilityState['human_decision_eligibility']
                ?? null,

            'human_decision_eligibility_score' =>
                $eligibilityState['human_decision_eligibility_score']
                ?? null,

            'human_decision_risk_level' =>
                $humanDecisionRiskState['human_decision_risk_level']
                ?? null,

            'human_decision_risk_score' =>
                $humanDecisionRiskState['human_decision_risk_score']
                ?? null,

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'total_recommendations' =>
                $recommendationSummary['total_recommendations']
                ?? 0,

            'executive_strategic_plan_decision_status' =>
                $executiveState[
                    'executive_strategic_plan_decision_status'
                ] ?? null,

            'executive_readiness' =>
                $executiveState['executive_readiness']
                ?? null,

            'executive_confidence' =>
                $executiveState['executive_confidence']
                ?? null,

            'executive_strategic_plan_decision_score' =>
                $executiveState[
                    'executive_strategic_plan_decision_score'
                ] ?? null,

            'final_decision_authority' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source traceability
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
            'strategic_plan_decision_id' =>
                $strategicPlanDecision->id,

            'decision_code' =>
                $strategicPlanDecision->decision_code,

            'strategic_plan_id' =>
                $strategicPlanDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $strategicPlanDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $strategicPlanDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $strategicPlanDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $strategicPlanDecision->decision_scope,

            'resident_id' =>
                $strategicPlanDecision->resident_id,

            'prepared_decision' =>
                $preparedDecision,

            'prepared_decision_status' =>
                $strategicPlanDecision->decision_status,

            'decision_priority' =>
                $strategicPlanDecision->decision_priority,

            'decision_priority_score' =>
                $strategicPlanDecision->decision_priority_score,

            'source_decision_created_at' =>
                optional(
                    $strategicPlanDecision->created_at
                )?->toISOString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Create human decision preparation record
        |--------------------------------------------------------------------------
        */

        $humanDecision = DB::transaction(function () use (
            $strategicPlanDecision,
            $humanDecisionCode,
            $preparedDecision,
            $decisionConditions,
            $conditionResolutionContext,
            $evidenceContext,
            $reviewContext,
            $governanceContext,
            $sourceContext,
            $eligibilityState,
            $humanDecisionRiskState
        ) {
            $record = new AIGovernanceStrategicPlanHumanDecision();

            $record->strategic_plan_decision_id =
                $strategicPlanDecision->id;

            $record->strategic_plan_id =
                $strategicPlanDecision->strategic_plan_id;

            $record->strategic_snapshot_id =
                $strategicPlanDecision->strategic_snapshot_id;

            $record->operational_snapshot_id =
                $strategicPlanDecision->operational_snapshot_id;

            $record->lifecycle_snapshot_id =
                $strategicPlanDecision->lifecycle_snapshot_id;

            $record->decision_scope =
                $strategicPlanDecision->decision_scope;

            $record->resident_id =
                $strategicPlanDecision->resident_id;

            $record->human_decision_code =
                $humanDecisionCode;

            /*
            |--------------------------------------------------------------------------
            | Human decision state
            |--------------------------------------------------------------------------
            */

            $record->human_decision_status =
                'PENDING_AUTHORIZED_HUMAN_DECISION';

            $record->human_decision_mode =
                'AUTHORIZED_HUMAN_GOVERNANCE_DECISION';

            $record->prepared_decision =
                $preparedDecision;

            $record->prepared_decision_status =
                $strategicPlanDecision->decision_status;

            /*
            |--------------------------------------------------------------------------
            | CRITICAL AUTHORITY BOUNDARY
            |--------------------------------------------------------------------------
            |
            | AI does NOT populate the final human decision.
            |
            */

            $record->final_human_decision = null;

            $record->decision_rationale =
                $strategicPlanDecision->decision_rationale;

            $record->decision_notes = null;

            /*
            |--------------------------------------------------------------------------
            | Decision priority / risk
            |--------------------------------------------------------------------------
            */

            $record->decision_priority =
                $strategicPlanDecision->decision_priority;

            $record->decision_priority_score =
                $strategicPlanDecision->decision_priority_score;

            $record->decision_risk_level =
                $humanDecisionRiskState['human_decision_risk_level']
                ?? $strategicPlanDecision->plan_risk_level;

            $record->decision_risk_score =
                $humanDecisionRiskState['human_decision_risk_score']
                ?? $strategicPlanDecision->plan_risk_score;

            /*
            |--------------------------------------------------------------------------
            | Human decision eligibility
            |--------------------------------------------------------------------------
            */

            $record->approval_eligibility =
                $eligibilityState['human_approval_eligibility']
                ?? null;

            $record->decision_eligibility_score =
                $eligibilityState['human_decision_eligibility_score']
                ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Conditions / evidence / context
            |--------------------------------------------------------------------------
            */

            $record->decision_conditions =
                $decisionConditions;

            $record->condition_resolution_context =
                $conditionResolutionContext;

            $record->evidence_context =
                $evidenceContext;

            /*
            |--------------------------------------------------------------------------
            | No AI-validated evidence
            |--------------------------------------------------------------------------
            */

            $record->validated_evidence = [];

            $record->review_context =
                $reviewContext;

            $record->governance_context =
                $governanceContext;

            $record->source_context =
                $sourceContext;

            /*
            |--------------------------------------------------------------------------
            | Authorized human identity
            |--------------------------------------------------------------------------
            */

            $record->decided_by = null;
            $record->decider_role = null;

            $record->reviewed_at = null;
            $record->decided_at = null;

            /*
            |--------------------------------------------------------------------------
            | Governance validator identity
            |--------------------------------------------------------------------------
            */

            $record->validated_by = null;
            $record->validator_role = null;
            $record->validated_at = null;

            /*
            |--------------------------------------------------------------------------
            | Human authority confirmation
            |--------------------------------------------------------------------------
            */

            $record->decision_made_by_authorized_human = false;
            $record->governance_validation_completed = false;

            /*
            |--------------------------------------------------------------------------
            | Automatic authority isolation
            |--------------------------------------------------------------------------
            */

            $record->automatic_decision_allowed = false;
            $record->automatic_approval_allowed = false;
            $record->automatic_rejection_allowed = false;
            $record->automatic_activation_allowed = false;

            $record->automatic_condition_resolution_allowed = false;
            $record->automatic_evidence_validation_allowed = false;

            $record->automatic_execution_allowed = false;
            $record->automatic_change_allowed = false;
            $record->automatic_deployment_allowed = false;
            $record->automatic_rollback_allowed = false;
            $record->automatic_clinical_action_allowed = false;

            /*
            |--------------------------------------------------------------------------
            | Mandatory governance controls
            |--------------------------------------------------------------------------
            */

            $record->human_review_required = true;
            $record->governance_validation_required = true;

            $record->save();

            return $record->fresh();
        });

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return [
            'prepared' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_PREPARED',

            'message' =>
                'AI governance strategic plan human decision record prepared successfully for authorized human governance decision and validation.',

            'human_decision' => [
                'strategic_plan_human_decision_id' =>
                    $humanDecision->id,

                'human_decision_code' =>
                    $humanDecision->human_decision_code,

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

                'decision_priority' =>
                    $humanDecision->decision_priority,

                'decision_priority_score' =>
                    (float) $humanDecision->decision_priority_score,

                'decision_risk_level' =>
                    $humanDecision->decision_risk_level,

                'decision_risk_score' =>
                    (float) $humanDecision->decision_risk_score,

                'approval_eligibility' =>
                    $humanDecision->approval_eligibility,

                'decision_eligibility_score' =>
                    (float) $humanDecision->decision_eligibility_score,

                'decision_made_by_authorized_human' =>
                    (bool) $humanDecision->decision_made_by_authorized_human,

                'governance_validation_completed' =>
                    (bool) $humanDecision->governance_validation_completed,
            ],

            'human_decision_context' => [
                'prepared_decision' =>
                    $preparedDecision,

                'final_human_decision_recorded' => false,

                'human_review_eligibility' =>
                    $eligibilityState['human_review_eligibility']
                    ?? null,

                'human_approval_eligibility' =>
                    $eligibilityState['human_approval_eligibility']
                    ?? null,

                'human_decision_eligibility' =>
                    $eligibilityState['human_decision_eligibility']
                    ?? null,

                'human_decision_eligibility_score' =>
                    $eligibilityState['human_decision_eligibility_score']
                    ?? 0,

                'human_decision_readiness' =>
                    $eligibilityState['human_decision_readiness']
                    ?? null,

                'human_decision_risk_level' =>
                    $humanDecisionRiskState['human_decision_risk_level']
                    ?? null,

                'human_decision_risk_score' =>
                    $humanDecisionRiskState['human_decision_risk_score']
                    ?? null,

                'blocking_conditions' =>
                    $eligibilitySummary['blocking_conditions']
                    ?? 0,

                'constraining_conditions' =>
                    $eligibilitySummary['constraining_conditions']
                    ?? 0,

                'outstanding_evidence_items' =>
                    $eligibilitySummary['outstanding_evidence_items']
                    ?? 0,

                'decision_blocking_evidence_items' =>
                    $eligibilitySummary[
                        'decision_blocking_evidence_items'
                    ] ?? 0,
            ],

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'human_decision_guardrails' => [
                'human_decision_preparation_enabled' => true,

                'prepared_record_is_final_human_decision' => false,

                'ai_makes_final_human_decision' => false,

                'ai_can_approve_strategic_plan' => false,

                'ai_can_reject_strategic_plan' => false,

                'ai_can_activate_strategic_plan' => false,

                'ai_can_resolve_decision_conditions' => false,

                'ai_can_validate_decision_evidence' => false,

                'decision_preparation_changes_plan_status' => false,

                'decision_preparation_changes_action_state' => false,

                'decision_preparation_changes_priority' => false,

                'decision_preparation_changes_eligibility' => false,

                'decision_preparation_authorizes_ai_change' => false,

                'decision_preparation_authorizes_execution' => false,

                'decision_preparation_authorizes_deployment' => false,

                'decision_preparation_authorizes_rollback' => false,

                'decision_preparation_authorizes_clinical_action' => false,

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

                'final_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' => true,

                'governance_validation_required' => true,

                'message' =>
                    'Step 65 human decision preparation creates a governed record through which an authorized human may later make and document the final strategic plan decision. Preparation does not itself approve, reject, activate, resolve conditions or dependencies, validate evidence, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function buildExistingResponse(
        AIGovernanceStrategicPlanHumanDecision $humanDecision,
        AIGovernanceStrategicPlanDecision $strategicPlanDecision
    ): array {
        return [
            'prepared' => true,

            'status' =>
                'EXISTING_GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_AVAILABLE',

            'message' =>
                'An active human governance decision record already exists for this strategic plan decision package.',

            'human_decision' => [
                'strategic_plan_human_decision_id' =>
                    $humanDecision->id,

                'human_decision_code' =>
                    $humanDecision->human_decision_code,

                'strategic_plan_decision_id' =>
                    $humanDecision->strategic_plan_decision_id,

                'strategic_plan_id' =>
                    $humanDecision->strategic_plan_id,

                'decision_scope' =>
                    $humanDecision->decision_scope,

                'resident_id' =>
                    $humanDecision->resident_id,

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

                'decision_made_by_authorized_human' =>
                    (bool) $humanDecision->decision_made_by_authorized_human,

                'governance_validation_completed' =>
                    (bool) $humanDecision->governance_validation_completed,
            ],

            'source_decision' => [
                'strategic_plan_decision_id' =>
                    $strategicPlanDecision->id,

                'decision_code' =>
                    $strategicPlanDecision->decision_code,
            ],
        ];
    }
}