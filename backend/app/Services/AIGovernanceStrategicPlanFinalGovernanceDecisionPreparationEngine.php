<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;
use App\Models\AIGovernanceStrategicPlanFinalGovernanceDecision;

class AIGovernanceStrategicPlanFinalGovernanceDecisionPreparationEngine
{
    public function prepare(?int $validationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resolve Source Governance Validation
        |--------------------------------------------------------------------------
        */

        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'prepared' => false,
                'status' => 'NO_STRATEGIC_PLAN_DECISION_VALIDATION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision validation record is available for final governance decision preparation.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 66 Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine::class
        );

        $conditionEvidenceEngine = app(
            AIGovernanceStrategicPlanDecisionValidationConditionEvidenceIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanDecisionValidationEligibilitySafetyIntelligenceEngine::class
        );

        $recommendationEngine = app(
            AIGovernanceStrategicPlanDecisionValidationRecommendationIntelligenceEngine::class
        );

        $executiveEngine = app(
            AIGovernanceExecutiveStrategicPlanDecisionValidationIntelligenceEngine::class
        );

        $finalValidationEngine = app(
            AIGovernanceStrategicPlanDecisionValidationFinalValidationEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Step 66 Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($validation->id);

        $conditionEvidence = $conditionEvidenceEngine->analyze(
            $validation->id
        );

        $eligibilitySafety = $eligibilitySafetyEngine->analyze(
            $validation->id
        );

        $recommendation = $recommendationEngine->analyze(
            $validation->id
        );

        $executive = $executiveEngine->analyze(
            $validation->id
        );

        $step66Validation = $finalValidationEngine->analyze(
            $validation->id
        );

        /*
        |--------------------------------------------------------------------------
        | Extract Validation State
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state'] ?? [];

        $conditionEvidenceState =
            $conditionEvidence['condition_evidence_state'] ?? [];

        $conditionSummary =
            $conditionEvidence['condition_summary'] ?? [];

        $evidenceSummary =
            $conditionEvidence['evidence_summary'] ?? [];

        $eligibilityState =
            $eligibilitySafety['validation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['validation_safety_state'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $executiveState =
            $executive['executive_governance_validation_state'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Source Human Decision State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            (bool) $validation->final_human_decision_recorded;

        $decisionMadeByAuthorizedHuman =
            (bool) $validation->decision_made_by_authorized_human;

        $sourceGovernanceValidationCompleted =
            (bool) $validation->governance_validation_completed;

        /*
        |--------------------------------------------------------------------------
        | Condition / Evidence Metrics
        |--------------------------------------------------------------------------
        */

        $blockingConditions = (int) (
            $conditionSummary['blocking_conditions']
            ?? $conditionSummary['blocking_validation_conditions']
            ?? 0
        );

        $constrainingConditions = (int) (
            $conditionSummary['constraining_conditions']
            ?? $conditionSummary['constraining_validation_conditions']
            ?? 0
        );

        $criticalOpenConditions = (int) (
            $conditionSummary['critical_open_conditions']
            ?? 0
        );

        $outstandingEvidence = (int) (
            $evidenceSummary['outstanding_evidence_items']
            ?? 0
        );

        $blockingEvidence = (int) (
            $evidenceSummary['blocking_evidence_items']
            ?? $evidenceSummary['blocking_validation_evidence_items']
            ?? 0
        );

        $criticalOutstandingEvidence = (int) (
            $evidenceSummary['critical_outstanding_evidence_items']
            ?? 0
        );

        $conditionResolutionScore = (float) (
            $conditionEvidenceState['condition_resolution_score']
            ?? $validation->condition_resolution_score
            ?? 0
        );

        $evidenceResolutionScore = (float) (
            $conditionEvidenceState['evidence_resolution_score']
            ?? $validation->evidence_resolution_score
            ?? 0
        );

        $combinedResolutionScore = (float) (
            $conditionEvidenceState['combined_resolution_score']
            ?? $validation->combined_resolution_score
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Decision Risk
        |--------------------------------------------------------------------------
        */

        $decisionRiskLevel =
            $validationState['decision_risk_level']
            ?? $validation->decision_risk_level
            ?? 'UNKNOWN';

        $decisionRiskScore = (float) (
            $validationState['decision_risk_score']
            ?? $validation->decision_risk_score
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Final Governance Decision Readiness
        |--------------------------------------------------------------------------
        |
        | This is readiness to enter authorized-human final governance review.
        | It does NOT authorize or make the final governance decision.
        |--------------------------------------------------------------------------
        */

        $step66ReadyForClosure =
            ($step66Validation['step_66_ready_for_closure'] ?? false) === true;

        $governanceIntegrityIntact =
            ($safetyState['governance_integrity_intact'] ?? false) === true
            && (($step66Validation['validation_summary']['failed_checks'] ?? 1) === 0);

        $validationReviewEligible =
            ($eligibilityState['authorized_human_validation_review_eligibility'] ?? null)
                === 'ELIGIBLE_FOR_AUTHORIZED_HUMAN_VALIDATION_REVIEW';

        /*
        |--------------------------------------------------------------------------
        | Readiness Components
        |--------------------------------------------------------------------------
        */

        $step66Score =
            $step66ReadyForClosure
                ? 25.0
                : 0.0;

        $integrityScore =
            $governanceIntegrityIntact
                ? 20.0
                : 0.0;

        $reviewEligibilityScore =
            $validationReviewEligible
                ? 10.0
                : 0.0;

        $humanDecisionScore =
            $finalHumanDecisionRecorded
            && $decisionMadeByAuthorizedHuman
                ? 20.0
                : 0.0;

        $conditionScore =
            min(10.0, max(0.0, $conditionResolutionScore * 0.10));

        $evidenceScore =
            min(10.0, max(0.0, $evidenceResolutionScore * 0.10));

        $riskScore =
            max(
                0.0,
                5.0 - (($decisionRiskScore / 100) * 5.0)
            );

        $finalDecisionReadinessScore = round(
            $step66Score
            + $integrityScore
            + $reviewEligibilityScore
            + $humanDecisionScore
            + $conditionScore
            + $evidenceScore
            + $riskScore,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Final Decision Readiness Classification
        |--------------------------------------------------------------------------
        */

        if (
            !$step66ReadyForClosure
            || !$governanceIntegrityIntact
        ) {
            $finalDecisionReadiness =
                'FINAL_GOVERNANCE_DECISION_PREPARATION_BLOCKED';
        } elseif (
            !$finalHumanDecisionRecorded
            || !$decisionMadeByAuthorizedHuman
        ) {
            $finalDecisionReadiness =
                'AWAITING_AUTHORIZED_HUMAN_FINAL_DECISION';
        } elseif (
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidence > 0
        ) {
            $finalDecisionReadiness =
                'CRITICAL_GOVERNANCE_REQUIREMENTS_OUTSTANDING';
        } elseif (
            $blockingConditions > 0
            || $blockingEvidence > 0
        ) {
            $finalDecisionReadiness =
                'MATERIAL_GOVERNANCE_REQUIREMENTS_OUTSTANDING';
        } elseif ($sourceGovernanceValidationCompleted) {
            $finalDecisionReadiness =
                'READY_FOR_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_CONFIRMATION';
        } else {
            $finalDecisionReadiness =
                'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_PROGRESSION';
        }

        /*
        |--------------------------------------------------------------------------
        | Initial Final Governance Decision Status
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecisionStatus =
            'PENDING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION';

        /*
        |--------------------------------------------------------------------------
        | Decision Conditions
        |--------------------------------------------------------------------------
        */

        $decisionConditions = [];

        if (!$finalHumanDecisionRecorded) {
            $decisionConditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_FINAL_STRATEGIC_PLAN_DECISION_REQUIRED',

                'condition_type' =>
                    'HUMAN_DECISION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'An explicitly authorized human strategic plan decision must be recorded before final governance decision progression.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $decisionConditions[] = [
                'condition_code' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_REQUIRED',

                'condition_type' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'The strategic plan decision must remain explicitly attributable to an authorized human governance decision-maker.',

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        if ($blockingConditions > 0) {
            $decisionConditions[] = [
                'condition_code' =>
                    'BLOCKING_GOVERNANCE_VALIDATION_CONDITIONS_REMAIN',

                'condition_type' =>
                    'VALIDATION_CONDITION',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$blockingConditions} blocking governance-validation condition(s) remain active and require authorized human governance resolution or formal governance treatment.",

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        if ($blockingEvidence > 0) {
            $decisionConditions[] = [
                'condition_code' =>
                    'BLOCKING_GOVERNANCE_VALIDATION_EVIDENCE_REMAINS',

                'condition_type' =>
                    'EVIDENCE',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "{$blockingEvidence} governance-validation evidence requirement(s) currently block final governance decision progression.",

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        if ($decisionRiskScore >= 75) {
            $decisionConditions[] = [
                'condition_code' =>
                    'HIGH_FINAL_GOVERNANCE_DECISION_RISK',

                'condition_type' =>
                    'RISK',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    "Strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore} and requires explicit authorized human governance consideration.",

                'requires_authorized_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validated Evidence
        |--------------------------------------------------------------------------
        */

        $validatedEvidence =
            $conditionEvidence['validated_evidence']
            ?? $validation->validated_evidence
            ?? [];

        if (!is_array($validatedEvidence)) {
            $validatedEvidence = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Restrictions
        |--------------------------------------------------------------------------
        */

        $decisionRestrictions = [];

        if (!$finalHumanDecisionRecorded) {
            $decisionRestrictions[] = [
                'restriction_code' =>
                    'FINAL_AUTHORIZED_HUMAN_DECISION_NOT_RECORDED',

                'restriction_type' =>
                    'HUMAN_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Final governance decision progression remains restricted until an explicitly authorized human strategic plan decision has been recorded.',
            ];
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $decisionRestrictions[] = [
                'restriction_code' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_INCOMPLETE',

                'restriction_type' =>
                    'AUTHORIZATION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'Final governance decision progression remains restricted until strategic plan decision attribution to an authorized human is complete.',
            ];
        }

        if ($blockingConditions > 0) {
            $decisionRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_CONDITIONS',

                'restriction_type' =>
                    'VALIDATION_CONDITION',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingConditions,

                'message' =>
                    "{$blockingConditions} blocking governance-validation condition(s) remain active.",
            ];
        }

        if ($blockingEvidence > 0) {
            $decisionRestrictions[] = [
                'restriction_code' =>
                    'BLOCKING_VALIDATION_EVIDENCE',

                'restriction_type' =>
                    'EVIDENCE',

                'severity' =>
                    'HIGH',

                'value' =>
                    $blockingEvidence,

                'message' =>
                    "{$blockingEvidence} blocking governance-validation evidence requirement(s) remain outstanding.",
            ];
        }

        if ($decisionRiskScore >= 75) {
            $decisionRestrictions[] = [
                'restriction_code' =>
                    'HIGH_GOVERNANCE_DECISION_RISK',

                'restriction_type' =>
                    'RISK',

                'severity' =>
                    'HIGH',

                'value' =>
                    $decisionRiskScore,

                'message' =>
                    "Strategic plan governance decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.",
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Review Context
        |--------------------------------------------------------------------------
        */

        $reviewContext = [
            'review_state' =>
                'AWAITING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION',

            'step_66_validation_status' =>
                $step66Validation['validation_status'] ?? null,

            'step_66_ready_for_closure' =>
                $step66ReadyForClosure,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'authorized_human_validation_review_eligibility' =>
                $eligibilityState['authorized_human_validation_review_eligibility']
                ?? null,

            'governance_validation_completion_eligibility' =>
                $eligibilityState['governance_validation_completion_eligibility']
                ?? null,

            'unrestricted_validation_eligibility' =>
                $eligibilityState['unrestricted_validation_eligibility']
                ?? null,

            'validation_safety_status' =>
                $safetyState['validation_safety_status']
                ?? null,

            'human_management_attention_required' =>
                $safetyState['human_management_attention_required']
                ?? false,

            'management_escalation_recommended' =>
                $executiveState['management_escalation_recommended']
                ?? false,

            'immediate_escalation_required' =>
                $executiveState['immediate_escalation_required']
                ?? false,

            'top_recommendation_code' =>
                $recommendationSummary['top_recommendation_code']
                ?? null,

            'top_recommended_validation_path' =>
                $recommendationSummary['top_recommended_validation_path']
                ?? null,

            'authorized_human_final_decision_required' =>
                true,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,
        ];

        /*
        |--------------------------------------------------------------------------
        | Validation Context
        |--------------------------------------------------------------------------
        */

        $validationContext = [
            'validation_status' =>
                $validationState['validation_status']
                ?? $validation->validation_status,

            'validation_mode' =>
                $validationState['validation_mode']
                ?? $validation->validation_mode,

            'governance_validation_readiness' =>
                $validationState['governance_validation_readiness']
                ?? $validation->governance_validation_readiness,

            'governance_validation_readiness_score' =>
                $validationState['governance_validation_readiness_score']
                ?? $validation->governance_validation_readiness_score,

            'governance_validation_decision' =>
                $validation->governance_validation_decision,

            'governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'blocking_conditions' =>
                $blockingConditions,

            'constraining_conditions' =>
                $constrainingConditions,

            'outstanding_evidence_items' =>
                $outstandingEvidence,

            'blocking_evidence_items' =>
                $blockingEvidence,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Context
        |--------------------------------------------------------------------------
        */

        $governanceContext = [
            'step_66_validation_status' =>
                $step66Validation['validation_status'] ?? null,

            'step_66_ready_for_closure' =>
                $step66ReadyForClosure,

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'final_decision_readiness' =>
                $finalDecisionReadiness,

            'final_decision_readiness_score' =>
                $finalDecisionReadinessScore,

            'decision_risk_level' =>
                $decisionRiskLevel,

            'decision_risk_score' =>
                $decisionRiskScore,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,

            'validation_eligibility_score' =>
                $eligibilityState['validation_eligibility_score']
                ?? null,

            'validation_safety_status' =>
                $safetyState['validation_safety_status']
                ?? null,

            'validation_safety_score' =>
                $safetyState['validation_safety_score']
                ?? null,

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'total_recommendations' =>
                $recommendationSummary['total_recommendations']
                ?? 0,

            'top_recommendation_code' =>
                $recommendationSummary['top_recommendation_code']
                ?? null,

            'executive_governance_validation_status' =>
                $executiveState['executive_governance_validation_status']
                ?? null,

            'executive_readiness' =>
                $executiveState['executive_readiness']
                ?? null,

            'executive_confidence' =>
                $executiveState['executive_confidence']
                ?? null,

            'executive_governance_validation_score' =>
                $executiveState['executive_governance_validation_score']
                ?? null,

            'final_governance_decision_authority' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
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

            'prepared_decision' =>
                $validation->prepared_decision,

            'final_human_decision' =>
                $validation->final_human_decision,

            'source_final_human_decision_recorded' =>
                $finalHumanDecisionRecorded,

            'source_decision_made_by_authorized_human' =>
                $decisionMadeByAuthorizedHuman,

            'source_governance_validation_decision' =>
                $validation->governance_validation_decision,

            'source_governance_validation_completed' =>
                $sourceGovernanceValidationCompleted,

            'source_validation_created_at' =>
                optional($validation->created_at)->toISOString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Decision Findings
        |--------------------------------------------------------------------------
        */

        $decisionFindings = [
            "Final governance decision preparation is based on strategic plan governance-validation record {$validation->id}.",

            'Current source validation status is '
                .($validationState['validation_status']
                    ?? $validation->validation_status
                    ?? 'UNKNOWN')
                .'.',

            'Current prepared strategic plan decision is '
                .($validation->prepared_decision ?? 'UNKNOWN')
                .'.',

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO')
                .'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO')
                .'.',

            'Source governance validation completed is '
                .($sourceGovernanceValidationCompleted ? 'YES' : 'NO')
                .'.',

            "{$blockingConditions} blocking governance-validation condition(s) remain active.",

            "{$constrainingConditions} constraining governance-validation condition(s) remain active.",

            "{$outstandingEvidence} governance-validation evidence requirement(s) remain outstanding.",

            "{$blockingEvidence} governance-validation evidence requirement(s) currently block final governance decision progression.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined condition/evidence resolution score is {$combinedResolutionScore}.",

            "Current final governance decision readiness is {$finalDecisionReadiness} with score {$finalDecisionReadinessScore}.",

            "Current strategic plan decision risk is {$decisionRiskLevel} with score {$decisionRiskScore}.",

            'Step 66 final validation status is '
                .($step66Validation['validation_status'] ?? 'UNKNOWN')
                .'.',

            'Step 66 ready for closure is '
                .($step66ReadyForClosure ? 'YES' : 'NO')
                .'.',

            'Final governance decision preparation remains a preparation function and does not make or record the final governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Avoid Duplicate Preparation
        |--------------------------------------------------------------------------
        |
        | Only one current Step 67 final-governance-decision package should be
        | created per Step 66 validation record.
        |--------------------------------------------------------------------------
        */

        $existing = AIGovernanceStrategicPlanFinalGovernanceDecision::where(
            'strategic_plan_decision_validation_id',
            $validation->id
        )->latest('id')->first();

        if ($existing) {
            return [
                'prepared' => true,

                'status' =>
                    'GOVERNANCE_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_ALREADY_PREPARED',

                'message' =>
                    'An AI governance strategic plan final governance decision record has already been prepared for this validation package.',

                'final_governance_decision' =>
                    $this->decisionPayload($existing),

                'final_governance_decision_context' => [
                    'final_decision_readiness' =>
                        $existing->final_decision_readiness,

                    'final_decision_readiness_score' =>
                        $existing->final_decision_readiness_score,

                    'final_decision_risk_level' =>
                        $existing->final_decision_risk_level,

                    'final_decision_risk_score' =>
                        $existing->final_decision_risk_score,

                    'condition_resolution_score' =>
                        $existing->condition_resolution_score,

                    'evidence_resolution_score' =>
                        $existing->evidence_resolution_score,

                    'combined_resolution_score' =>
                        $existing->combined_resolution_score,

                    'decision_condition_count' =>
                        count($existing->decision_conditions ?? []),

                    'decision_restriction_count' =>
                        count($existing->decision_restrictions ?? []),
                ],

                'review_context' =>
                    $existing->review_context ?? [],

                'validation_context' =>
                    $existing->validation_context ?? [],

                'governance_context' =>
                    $existing->governance_context ?? [],

                'final_governance_decision_guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Governed Decision Code
        |--------------------------------------------------------------------------
        */

        $nextId =
            (AIGovernanceStrategicPlanFinalGovernanceDecision::max('id') ?? 0)
            + 1;

        $decisionCode =
            'GOV-FINAL-STRATEGIC-PLAN-DECISION-'
            .$nextId
            .'-'
            .now()->format('YmdHis');

        /*
        |--------------------------------------------------------------------------
        | Create Prepared Final Governance Decision Record
        |--------------------------------------------------------------------------
        */

        $finalGovernanceDecision =
            AIGovernanceStrategicPlanFinalGovernanceDecision::create([
                'strategic_plan_decision_validation_id' =>
                    $validation->id,

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

                /*
                |--------------------------------------------------------------------------
                | Decision Identity
                |--------------------------------------------------------------------------
                */

                'final_governance_decision_code' =>
                    $decisionCode,

                'final_governance_decision_status' =>
                    $finalGovernanceDecisionStatus,

                'final_governance_decision_mode' =>
                    'AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION',

                /*
                |--------------------------------------------------------------------------
                | Upstream Context
                |--------------------------------------------------------------------------
                */

                'prepared_decision' =>
                    $validation->prepared_decision,

                'source_human_decision' =>
                    $validation->final_human_decision,

                'source_final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'source_decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'source_governance_validation_decision' =>
                    $validation->governance_validation_decision,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                /*
                |--------------------------------------------------------------------------
                | Final Outcome — Must Remain Empty
                |--------------------------------------------------------------------------
                */

                'final_governance_decision' =>
                    null,

                'final_governance_decision_rationale' =>
                    'Final governance decision package prepared from Step 66 strategic plan governance-validation intelligence for authorized human governance review. Preparation does not constitute the final governance decision.',

                'final_governance_decision_notes' =>
                    null,

                'final_governance_outcome' =>
                    null,

                'final_governance_outcome_status' =>
                    'PENDING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION',

                /*
                |--------------------------------------------------------------------------
                | Readiness / Risk
                |--------------------------------------------------------------------------
                */

                'final_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_decision_readiness_score' =>
                    $finalDecisionReadinessScore,

                'final_decision_risk_level' =>
                    $decisionRiskLevel,

                'final_decision_risk_score' =>
                    $decisionRiskScore,

                /*
                |--------------------------------------------------------------------------
                | Resolution
                |--------------------------------------------------------------------------
                */

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                /*
                |--------------------------------------------------------------------------
                | Structured Governance Context
                |--------------------------------------------------------------------------
                */

                'decision_conditions' =>
                    $decisionConditions,

                'validated_evidence' =>
                    $validatedEvidence,

                'decision_findings' =>
                    $decisionFindings,

                'decision_restrictions' =>
                    $decisionRestrictions,

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
                | Authorized Human Attribution
                |--------------------------------------------------------------------------
                */

                'decided_by' =>
                    null,

                'decider_role' =>
                    null,

                'decided_at' =>
                    null,

                'decision_made_by_authorized_human' =>
                    false,

                /*
                |--------------------------------------------------------------------------
                | Confirmation
                |--------------------------------------------------------------------------
                */

                'confirmed_by' =>
                    null,

                'confirmer_role' =>
                    null,

                'confirmed_at' =>
                    null,

                'final_governance_confirmation_completed' =>
                    false,

                /*
                |--------------------------------------------------------------------------
                | Automatic Authority Isolation
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Human Governance Requirements
                |--------------------------------------------------------------------------
                */

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'authorized_human_final_decision_required' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Return Preparation Package
        |--------------------------------------------------------------------------
        */

        return [
            'prepared' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_FINAL_GOVERNANCE_DECISION_PREPARED',

            'message' =>
                'AI governance strategic plan final governance decision record prepared successfully for explicitly authorized human final governance decision review.',

            'final_governance_decision' =>
                $this->decisionPayload($finalGovernanceDecision),

            'final_governance_decision_context' => [
                'step_66_validation_status' =>
                    $step66Validation['validation_status'] ?? null,

                'step_66_ready_for_closure' =>
                    $step66ReadyForClosure,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'source_governance_validation_completed' =>
                    $sourceGovernanceValidationCompleted,

                'final_decision_readiness' =>
                    $finalDecisionReadiness,

                'final_decision_readiness_score' =>
                    $finalDecisionReadinessScore,

                'final_decision_risk_level' =>
                    $decisionRiskLevel,

                'final_decision_risk_score' =>
                    $decisionRiskScore,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'outstanding_evidence_items' =>
                    $outstandingEvidence,

                'blocking_evidence_items' =>
                    $blockingEvidence,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'decision_condition_count' =>
                    count($decisionConditions),

                'decision_restriction_count' =>
                    count($decisionRestrictions),
            ],

            'decision_conditions' =>
                $decisionConditions,

            'decision_restrictions' =>
                $decisionRestrictions,

            'validated_evidence' =>
                $validatedEvidence,

            'decision_findings' =>
                $decisionFindings,

            'review_context' =>
                $reviewContext,

            'validation_context' =>
                $validationContext,

            'governance_context' =>
                $governanceContext,

            'final_governance_decision_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Decision Payload
    |--------------------------------------------------------------------------
    */

    private function decisionPayload(
        AIGovernanceStrategicPlanFinalGovernanceDecision $decision
    ): array {
        return [
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

            'final_governance_decision_status' =>
                $decision->final_governance_decision_status,

            'final_governance_decision_mode' =>
                $decision->final_governance_decision_mode,

            'prepared_decision' =>
                $decision->prepared_decision,

            'source_human_decision' =>
                $decision->source_human_decision,

            'source_final_human_decision_recorded' =>
                $decision->source_final_human_decision_recorded,

            'source_decision_made_by_authorized_human' =>
                $decision->source_decision_made_by_authorized_human,

            'source_governance_validation_decision' =>
                $decision->source_governance_validation_decision,

            'source_governance_validation_completed' =>
                $decision->source_governance_validation_completed,

            'final_governance_decision' =>
                $decision->final_governance_decision,

            'final_governance_outcome' =>
                $decision->final_governance_outcome,

            'final_governance_outcome_status' =>
                $decision->final_governance_outcome_status,

            'final_decision_readiness' =>
                $decision->final_decision_readiness,

            'final_decision_readiness_score' =>
                $decision->final_decision_readiness_score,

            'final_decision_risk_level' =>
                $decision->final_decision_risk_level,

            'final_decision_risk_score' =>
                $decision->final_decision_risk_score,

            'condition_resolution_score' =>
                $decision->condition_resolution_score,

            'evidence_resolution_score' =>
                $decision->evidence_resolution_score,

            'combined_resolution_score' =>
                $decision->combined_resolution_score,

            'decision_made_by_authorized_human' =>
                $decision->decision_made_by_authorized_human,

            'final_governance_confirmation_completed' =>
                $decision->final_governance_confirmation_completed,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Step 67.2 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'final_governance_decision_preparation_enabled' =>
                true,

            'prepared_record_is_final_governance_decision' =>
                false,

            'ai_makes_final_governance_decision' =>
                false,

            'ai_records_final_governance_decision' =>
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

            'ai_activates_strategic_plan' =>
                false,

            'decision_preparation_changes_source_human_decision' =>
                false,

            'decision_preparation_changes_governance_validation' =>
                false,

            'decision_preparation_changes_plan_status' =>
                false,

            'decision_preparation_changes_action_state' =>
                false,

            'decision_preparation_resolves_conditions' =>
                false,

            'decision_preparation_resolves_dependencies' =>
                false,

            'decision_preparation_validates_evidence' =>
                false,

            'decision_readiness_score_authorizes_final_decision' =>
                false,

            'decision_readiness_score_authorizes_approval' =>
                false,

            'decision_risk_score_authorizes_final_decision' =>
                false,

            'condition_resolution_score_authorizes_final_decision' =>
                false,

            'evidence_resolution_score_authorizes_final_decision' =>
                false,

            'combined_resolution_score_authorizes_final_decision' =>
                false,

            'decision_preparation_authorizes_ai_change' =>
                false,

            'decision_preparation_authorizes_execution' =>
                false,

            'decision_preparation_authorizes_deployment' =>
                false,

            'decision_preparation_authorizes_rollback' =>
                false,

            'decision_preparation_authorizes_clinical_action' =>
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
                'Step 67.2 prepares a structured final strategic plan governance-decision record from Step 66 governance-validation intelligence for explicitly authorized human governance review. Preparation does not make or record the final governance decision, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, alter upstream human decisions or governance validation, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final governance decision authority remains reserved exclusively for authorized human governance.',
        ];
    }
}