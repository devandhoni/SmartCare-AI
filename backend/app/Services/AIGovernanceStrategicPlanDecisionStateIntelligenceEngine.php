<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanDecisionStateIntelligenceEngine
{
    public function analyze(?int $decisionId = null): array
    {
        $decision = $decisionId
            ? AIGovernanceStrategicPlanDecision::find($decisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_DECISION_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan decision record is currently available.',
            ];
        }

        $decisionConditions = is_array($decision->decision_conditions)
            ? $decision->decision_conditions
            : [];

        $requiredEvidence = is_array($decision->required_evidence)
            ? $decision->required_evidence
            : [];

        $reviewContext = is_array($decision->review_context)
            ? $decision->review_context
            : [];

        $decisionContext = is_array($decision->decision_context)
            ? $decision->decision_context
            : [];

        $sourceContext = is_array($decision->source_context)
            ? $decision->source_context
            : [];

        /*
        |--------------------------------------------------------------------------
        | Condition Analysis
        |--------------------------------------------------------------------------
        */

        $openConditions = collect($decisionConditions)
            ->filter(fn ($condition) =>
                strtoupper((string) ($condition['condition_status'] ?? 'OPEN')) === 'OPEN'
            )
            ->values();

        $highConditions = $openConditions
            ->filter(fn ($condition) =>
                strtoupper((string) ($condition['priority_level'] ?? '')) === 'HIGH'
            )
            ->values();

        $moderateConditions = $openConditions
            ->filter(fn ($condition) =>
                strtoupper((string) ($condition['priority_level'] ?? '')) === 'MODERATE'
            )
            ->values();

        $criticalConditions = $openConditions
            ->filter(fn ($condition) =>
                strtoupper((string) ($condition['priority_level'] ?? '')) === 'CRITICAL'
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Evidence Analysis
        |--------------------------------------------------------------------------
        */

        $requiredEvidenceItems = collect($requiredEvidence)
            ->filter(fn ($evidence) =>
                strtoupper((string) ($evidence['evidence_status'] ?? '')) === 'REQUIRED'
            )
            ->values();

        $availableEvidenceItems = collect($requiredEvidence)
            ->filter(fn ($evidence) =>
                strtoupper((string) ($evidence['evidence_status'] ?? '')) === 'AVAILABLE'
            )
            ->values();

        $highPriorityEvidence = $requiredEvidenceItems
            ->filter(fn ($evidence) =>
                strtoupper((string) ($evidence['priority_level'] ?? '')) === 'HIGH'
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Core Decision State Inputs
        |--------------------------------------------------------------------------
        */

        $decisionStatus = strtoupper((string) $decision->decision_status);
        $preparedDecision = strtoupper((string) $decision->decision);

        $blockingDependencyCount = (int) $decision->blocking_dependency_count;
        $constrainingDependencyCount = (int) $decision->constraining_dependency_count;

        $planningReadinessScore = (float) $decision->planning_readiness_score;
        $planRiskScore = (float) $decision->plan_risk_score;
        $dependencyAdjustedFeasibilityScore =
            (float) $decision->dependency_adjusted_feasibility_score;

        $humanReviewRequired = (bool) $decision->human_review_required;
        $governanceValidationRequired =
            (bool) $decision->governance_validation_required;

        $reviewed = !is_null($decision->reviewed_at);

        $finalDecisionRecorded =
            !is_null($decision->decided_at)
            || in_array($decisionStatus, [
                'APPROVED',
                'APPROVED_WITH_CONDITIONS',
                'REJECTED',
                'DEFERRED',
                'CLOSED',
            ], true);

        /*
        |--------------------------------------------------------------------------
        | Decision Review Readiness Score
        |--------------------------------------------------------------------------
        */

        $conditionPressure = min(
            100,
            ($criticalConditions->count() * 30)
            + ($highConditions->count() * 15)
            + ($moderateConditions->count() * 8)
        );

        $evidencePressure = min(
            100,
            ($highPriorityEvidence->count() * 20)
            + (($requiredEvidenceItems->count() - $highPriorityEvidence->count()) * 10)
        );

        $dependencyPressure = min(
            100,
            ($blockingDependencyCount * 20)
            + ($constrainingDependencyCount * 10)
        );

        $riskPressure = min(100, $planRiskScore);

        $readinessBase = (
            ($planningReadinessScore * 0.35)
            + ($dependencyAdjustedFeasibilityScore * 0.25)
            + ((100 - $riskPressure) * 0.20)
            + ((100 - $conditionPressure) * 0.10)
            + ((100 - $evidencePressure) * 0.10)
        );

        $decisionReviewReadinessScore = round(
            max(0, min(100, $readinessBase)),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Review Readiness Classification
        |--------------------------------------------------------------------------
        */

        $decisionReviewReadiness = match (true) {
            $decisionReviewReadinessScore >= 80
                => 'HIGH_REVIEW_READINESS',

            $decisionReviewReadinessScore >= 60
                => 'MODERATE_REVIEW_READINESS',

            $decisionReviewReadinessScore >= 40
                => 'LIMITED_REVIEW_READINESS',

            $decisionReviewReadinessScore >= 20
                => 'LOW_REVIEW_READINESS',

            default
                => 'VERY_LOW_REVIEW_READINESS',
        };

        /*
        |--------------------------------------------------------------------------
        | Decision State Classification
        |--------------------------------------------------------------------------
        */

        if ($finalDecisionRecorded) {
            $strategicDecisionState = 'HUMAN_GOVERNANCE_DECISION_RECORDED';
        } elseif (
            $criticalConditions->count() > 0
            || $blockingDependencyCount > 0
            || $requiredEvidenceItems->count() > 0
        ) {
            $strategicDecisionState =
                'PENDING_HUMAN_REVIEW_WITH_MATERIAL_CONDITIONS';
        } elseif (
            $constrainingDependencyCount > 0
            || $openConditions->count() > 0
        ) {
            $strategicDecisionState =
                'PENDING_HUMAN_REVIEW_WITH_CONSTRAINTS';
        } elseif ($humanReviewRequired) {
            $strategicDecisionState =
                'READY_FOR_HUMAN_GOVERNANCE_REVIEW';
        } else {
            $strategicDecisionState =
                'DECISION_REVIEW_STATE_UNDETERMINED';
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Readiness
        |--------------------------------------------------------------------------
        */

        $evidenceReadiness = match (true) {
            $requiredEvidenceItems->count() === 0
                => 'EVIDENCE_REQUIREMENTS_SATISFIED_OR_NOT_REQUIRED',

            $requiredEvidenceItems->count() === 1
                => 'LIMITED_EVIDENCE_REQUIREMENTS_OUTSTANDING',

            default
                => 'MATERIAL_EVIDENCE_REQUIREMENTS_OUTSTANDING',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Review Eligibility
        |--------------------------------------------------------------------------
        */

        $eligibleForHumanResolution =
            !$finalDecisionRecorded
            && $humanReviewRequired
            && $criticalConditions->count() === 0;

        $humanResolutionReadiness = match (true) {
            $finalDecisionRecorded
                => 'FINAL_HUMAN_DECISION_ALREADY_RECORDED',

            $criticalConditions->count() > 0
                => 'NOT_READY_CRITICAL_CONDITIONS_PRESENT',

            $blockingDependencyCount > 0
                => 'CONDITIONS_REQUIRE_HUMAN_RESOLUTION',

            $requiredEvidenceItems->count() > 0
                => 'ADDITIONAL_EVIDENCE_REQUIRED',

            $decisionReviewReadinessScore >= 60
                => 'READY_FOR_STRUCTURED_HUMAN_DECISION',

            default
                => 'LIMITED_HUMAN_DECISION_READINESS',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Attention Level
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionLevel = match (true) {
            $criticalConditions->count() > 0
                => 'CRITICAL',

            $planRiskScore >= 80
            || $blockingDependencyCount >= 2
            || $highConditions->count() >= 3
                => 'HIGH',

            $blockingDependencyCount > 0
            || $requiredEvidenceItems->count() > 0
            || $constrainingDependencyCount > 0
                => 'MODERATE',

            default
                => 'ROUTINE',
        };

        $humanManagementAttentionRequired =
            $humanManagementAttentionLevel !== 'ROUTINE';

        $immediateHumanInterventionRequired =
            $criticalConditions->count() > 0;

        /*
        |--------------------------------------------------------------------------
        | Decision Confidence
        |--------------------------------------------------------------------------
        */

        $decisionConfidence = match (true) {
            $decisionReviewReadinessScore >= 80
                => 'HIGH',

            $decisionReviewReadinessScore >= 60
                => 'MODERATE',

            $decisionReviewReadinessScore >= 40
                => 'LIMITED',

            $decisionReviewReadinessScore >= 20
                => 'VERY_LIMITED',

            default
                => 'EXTREMELY_LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Dominant Open Condition
        |--------------------------------------------------------------------------
        */

        $dominantCondition = $openConditions
            ->sortByDesc(function ($condition) {
                return match (
                    strtoupper((string) ($condition['priority_level'] ?? ''))
                ) {
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
        | Findings
        |--------------------------------------------------------------------------
        */

        $decisionFindings = [
            "Strategic plan decision state intelligence is based on decision record {$decision->id}.",

            "Current decision status is {$decision->decision_status}.",

            "Current prepared decision classification is {$decision->decision}.",

            "Current strategic decision state is {$strategicDecisionState}.",

            "Current decision review readiness is {$decisionReviewReadiness} with score {$decisionReviewReadinessScore}.",

            "Current decision confidence is {$decisionConfidence}.",

            "Current human resolution readiness is {$humanResolutionReadiness}.",

            $openConditions->count()
                . ' decision condition(s) remain open.',

            $criticalConditions->count()
                . ' critical decision condition(s) remain open.',

            $highConditions->count()
                . ' high-priority decision condition(s) remain open.',

            $requiredEvidenceItems->count()
                . ' required evidence item(s) remain outstanding.',

            "{$blockingDependencyCount} blocking dependency condition(s) remain represented in the decision package.",

            "{$constrainingDependencyCount} constraining dependency condition(s) remain represented in the decision package.",

            "Current planning readiness score is {$planningReadinessScore}.",

            "Current dependency-adjusted feasibility score is {$dependencyAdjustedFeasibilityScore}.",

            "Current strategic plan risk score is {$planRiskScore}.",

            'Human governance review remains '
                . ($humanReviewRequired ? 'REQUIRED.' : 'NOT_REQUIRED.'),

            'Governance validation remains '
                . ($governanceValidationRequired ? 'REQUIRED.' : 'NOT_REQUIRED.'),

            'Strategic plan decision state intelligence remains advisory and does not make or record the final human governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantCondition) {
            $managementPriorities[] =
                'Address the highest-priority open decision condition '
                . ($dominantCondition['condition_code'] ?? 'UNSPECIFIED')
                . ' through the established human governance process.';
        }

        if ($requiredEvidenceItems->count() > 0) {
            $managementPriorities[] =
                'Provide and validate outstanding evidence requirements before final strategic plan approval is considered.';
        }

        if ($blockingDependencyCount > 0) {
            $managementPriorities[] =
                'Resolve or formally govern active blocking strategic planning dependencies before final plan approval.';
        }

        if ($constrainingDependencyCount > 0) {
            $managementPriorities[] =
                'Reduce active constraining dependencies to improve human decision readiness.';
        }

        if ($planningReadinessScore < 50) {
            $managementPriorities[] =
                'Improve strategic planning readiness before considering unrestricted plan approval.';
        }

        if ($dependencyAdjustedFeasibilityScore < 40) {
            $managementPriorities[] =
                'Improve practical strategic plan feasibility through governed dependency reduction.';
        }

        if ($planRiskScore >= 70) {
            $managementPriorities[] =
                'Maintain elevated human management oversight while strategic plan risk remains high.';
        }

        $managementPriorities[] =
            'Ensure the final strategic plan decision is made and documented only by authorized human governance.';

        $managementPriorities[] =
            'Preserve evidence quality, human review, governance validation, safety controls, traceability, and authority separation throughout the decision process.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_STATE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,

            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,

            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'decision_state' => [
                'decision_status' => $decision->decision_status,
                'decision_mode' => $decision->decision_mode,
                'prepared_decision' => $decision->decision,

                'strategic_decision_state' => $strategicDecisionState,

                'decision_review_readiness' =>
                    $decisionReviewReadiness,

                'decision_review_readiness_score' =>
                    $decisionReviewReadinessScore,

                'decision_confidence' => $decisionConfidence,

                'human_resolution_readiness' =>
                    $humanResolutionReadiness,

                'eligible_for_human_resolution' =>
                    $eligibleForHumanResolution,

                'evidence_readiness' => $evidenceReadiness,

                'human_management_attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'final_human_decision_recorded' =>
                    $finalDecisionRecorded,
            ],

            'condition_context' => [
                'total_conditions' =>
                    count($decisionConditions),

                'open_conditions' =>
                    $openConditions->count(),

                'critical_conditions' =>
                    $criticalConditions->count(),

                'high_conditions' =>
                    $highConditions->count(),

                'moderate_conditions' =>
                    $moderateConditions->count(),

                'dominant_open_condition' =>
                    $dominantCondition,
            ],

            'evidence_context' => [
                'total_evidence_items' =>
                    count($requiredEvidence),

                'required_evidence_items' =>
                    $requiredEvidenceItems->count(),

                'available_evidence_items' =>
                    $availableEvidenceItems->count(),

                'high_priority_required_evidence_items' =>
                    $highPriorityEvidence->count(),

                'evidence_readiness' =>
                    $evidenceReadiness,
            ],

            'dependency_context' => [
                'dependency_feasibility_status' =>
                    $decision->dependency_feasibility_status,

                'dependency_adjusted_feasibility_score' =>
                    $dependencyAdjustedFeasibilityScore,

                'blocking_dependency_count' =>
                    $blockingDependencyCount,

                'constraining_dependency_count' =>
                    $constrainingDependencyCount,
            ],

            'planning_context' => [
                'planning_readiness' =>
                    $decision->planning_readiness,

                'planning_readiness_score' =>
                    $planningReadinessScore,

                'plan_risk_level' =>
                    $decision->plan_risk_level,

                'plan_risk_score' =>
                    $planRiskScore,

                'decision_priority' =>
                    $decision->decision_priority,

                'decision_priority_score' =>
                    (float) $decision->decision_priority_score,
            ],

            'review_context' => [
                'reviewed' => $reviewed,

                'reviewed_by' => $decision->reviewed_by,
                'reviewer_role' => $decision->reviewer_role,
                'reviewed_at' => $decision->reviewed_at,

                'human_review_required' =>
                    $humanReviewRequired,

                'governance_validation_required' =>
                    $governanceValidationRequired,

                'review_state' =>
                    $reviewContext['review_state']
                    ?? (
                        $reviewed
                            ? 'HUMAN_GOVERNANCE_REVIEW_RECORDED'
                            : 'AWAITING_HUMAN_GOVERNANCE_REVIEW'
                    ),
            ],

            'prepared_decision_context' =>
                $decisionContext,

            'source_context' =>
                $sourceContext,

            'decision_findings' =>
                $decisionFindings,

            'management_priorities' =>
                array_values(array_unique($managementPriorities)),

            'decision_state_guardrails' => [
                'strategic_plan_decision_state_intelligence_enabled' =>
                    true,

                'decision_state_is_human_final_decision' =>
                    false,

                'decision_state_is_governance_approval' =>
                    false,

                'decision_state_is_governance_rejection' =>
                    false,

                'decision_state_is_plan_activation' =>
                    false,

                'decision_state_changes_plan_status' =>
                    false,

                'decision_state_changes_action_state' =>
                    false,

                'decision_state_changes_priority' =>
                    false,

                'decision_state_changes_eligibility' =>
                    false,

                'decision_state_resolves_conditions' =>
                    false,

                'decision_state_resolves_dependencies' =>
                    false,

                'decision_state_validates_evidence' =>
                    false,

                'decision_review_readiness_authorizes_approval' =>
                    false,

                'decision_review_readiness_authorizes_execution' =>
                    false,

                'decision_confidence_expands_ai_authority' =>
                    false,

                'decision_state_authorizes_ai_change' =>
                    false,

                'decision_state_authorizes_execution' =>
                    false,

                'decision_state_authorizes_deployment' =>
                    false,

                'decision_state_authorizes_rollback' =>
                    false,

                'decision_state_authorizes_clinical_action' =>
                    false,

                'decision_state_overrides_human_review' =>
                    false,

                'decision_state_overrides_evidence_requirements' =>
                    false,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Governance strategic plan decision state intelligence evaluates the current review state, open decision conditions, evidence requirements, dependency constraints, planning readiness, risk, and human decision readiness of a prepared strategic plan decision package. It does not make or record the final governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, activate planning work, change plan or action state, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}