<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanHumanDecisionEligibilityRiskIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanDecisionStateIntelligenceEngine $decisionStateEngine,
        protected AIGovernanceStrategicPlanDecisionConditionEvidenceIntelligenceEngine $conditionEvidenceEngine
    ) {
    }

    public function analyze(?int $strategicPlanDecisionId = null): array
    {
        $decision = $strategicPlanDecisionId
            ? AIGovernanceStrategicPlanDecision::find($strategicPlanDecisionId)
            : AIGovernanceStrategicPlanDecision::latest('id')->first();

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_ELIGIBILITY_RISK_INTELLIGENCE_UNAVAILABLE',
                'message' => 'No strategic plan decision package is available for human decision eligibility and risk analysis.',
            ];
        }

        $decisionState = $this->decisionStateEngine->analyze($decision->id);
        $conditionEvidence = $this->conditionEvidenceEngine->analyze($decision->id);

        if (
            !($decisionState['analysis_completed'] ?? false) ||
            !($conditionEvidence['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_ELIGIBILITY_RISK_INTELLIGENCE_UNAVAILABLE',
                'strategic_plan_decision_id' => $decision->id,
                'message' => 'Required strategic plan decision intelligence is unavailable.',
            ];
        }

        $decisionStateContext = $decisionState['decision_state'] ?? [];
        $conditionState = $conditionEvidence['condition_evidence_state'] ?? [];
        $conditionSummary = $conditionEvidence['condition_summary'] ?? [];
        $evidenceSummary = $conditionEvidence['evidence_summary'] ?? [];
        $dependencyContext = $decisionState['dependency_context'] ?? [];
        $planningContext = $decisionState['planning_context'] ?? [];
        $reviewContext = $decisionState['review_context'] ?? [];

        $openConditions = (int) ($conditionSummary['open_conditions'] ?? 0);
        $blockingConditions = (int) ($conditionSummary['blocking_conditions'] ?? 0);
        $constrainingConditions = (int) ($conditionSummary['constraining_conditions'] ?? 0);
        $criticalOpenConditions = (int) ($conditionSummary['critical_open_conditions'] ?? 0);
        $highOpenConditions = (int) ($conditionSummary['high_open_conditions'] ?? 0);

        $outstandingEvidence = (int) ($evidenceSummary['outstanding_evidence_items'] ?? 0);
        $blockingEvidence = (int) ($evidenceSummary['decision_blocking_evidence_items'] ?? 0);
        $criticalOutstandingEvidence = (int) ($evidenceSummary['critical_outstanding_evidence_items'] ?? 0);
        $highOutstandingEvidence = (int) ($evidenceSummary['high_outstanding_evidence_items'] ?? 0);

        $conditionPressureScore = (float) ($conditionState['condition_pressure_score'] ?? 0);
        $conditionResolutionScore = (float) ($conditionState['condition_resolution_score'] ?? 0);
        $evidenceReadinessScore = (float) ($conditionState['evidence_readiness_score'] ?? 0);
        $decisionEvidenceReadinessScore = (float) ($conditionState['decision_evidence_readiness_score'] ?? 0);

        $planningReadinessScore = (float) ($planningContext['planning_readiness_score'] ?? 0);
        $planRiskScore = (float) ($planningContext['plan_risk_score'] ?? 0);
        $decisionPriorityScore = (float) ($planningContext['decision_priority_score'] ?? 0);

        $blockingDependencyCount = (int) ($dependencyContext['blocking_dependency_count'] ?? 0);
        $constrainingDependencyCount = (int) ($dependencyContext['constraining_dependency_count'] ?? 0);
        $dependencyAdjustedFeasibilityScore = (float) ($dependencyContext['dependency_adjusted_feasibility_score'] ?? 0);

        $finalHumanDecisionRecorded = (bool) ($decisionStateContext['final_human_decision_recorded'] ?? false);
        $eligibleForHumanResolution = (bool) ($decisionStateContext['eligible_for_human_resolution'] ?? false);

        /*
        |--------------------------------------------------------------------------
        | Human review eligibility
        |--------------------------------------------------------------------------
        |
        | Review eligibility is deliberately broader than approval eligibility.
        | Material conditions may require human review rather than prevent review.
        |
        */

        $humanReviewEligible =
            !$finalHumanDecisionRecorded &&
            (bool) ($decision->human_review_required ?? true) &&
            (bool) ($decision->governance_validation_required ?? true);

        $humanReviewEligibility = $humanReviewEligible
            ? 'ELIGIBLE_FOR_HUMAN_GOVERNANCE_REVIEW'
            : ($finalHumanDecisionRecorded
                ? 'FINAL_HUMAN_DECISION_ALREADY_RECORDED'
                : 'NOT_ELIGIBLE_FOR_HUMAN_GOVERNANCE_REVIEW');

        /*
        |--------------------------------------------------------------------------
        | Approval eligibility
        |--------------------------------------------------------------------------
        */

        if ($finalHumanDecisionRecorded) {
            $humanApprovalEligibility = 'FINAL_HUMAN_DECISION_ALREADY_RECORDED';
        } elseif (
            $criticalOpenConditions > 0 ||
            $criticalOutstandingEvidence > 0
        ) {
            $humanApprovalEligibility = 'NOT_ELIGIBLE_FOR_APPROVAL';
        } elseif (
            $blockingConditions > 0 ||
            $blockingEvidence > 0 ||
            $outstandingEvidence > 0 ||
            $blockingDependencyCount > 0
        ) {
            $humanApprovalEligibility = 'NOT_READY_FOR_UNRESTRICTED_APPROVAL';
        } elseif (
            $constrainingConditions > 0 ||
            $constrainingDependencyCount > 0 ||
            $planningReadinessScore < 50 ||
            $dependencyAdjustedFeasibilityScore < 50
        ) {
            $humanApprovalEligibility = 'CONDITIONAL_APPROVAL_REVIEW_REQUIRED';
        } else {
            $humanApprovalEligibility = 'ELIGIBLE_FOR_HUMAN_APPROVAL_CONSIDERATION';
        }

        /*
        |--------------------------------------------------------------------------
        | Overall human decision eligibility
        |--------------------------------------------------------------------------
        */

        if ($finalHumanDecisionRecorded) {
            $humanDecisionEligibility = 'FINAL_HUMAN_DECISION_ALREADY_RECORDED';
        } elseif (!$humanReviewEligible) {
            $humanDecisionEligibility = 'NOT_ELIGIBLE_FOR_HUMAN_DECISION';
        } elseif (
            $criticalOpenConditions > 0 ||
            $criticalOutstandingEvidence > 0
        ) {
            $humanDecisionEligibility = 'HUMAN_DECISION_REQUIRES_CRITICAL_RESOLUTION';
        } elseif (
            $blockingConditions > 0 ||
            $blockingEvidence > 0 ||
            $blockingDependencyCount > 0
        ) {
            $humanDecisionEligibility = 'CONDITIONALLY_ELIGIBLE_FOR_HUMAN_DECISION';
        } elseif (
            $constrainingConditions > 0 ||
            $outstandingEvidence > 0 ||
            $constrainingDependencyCount > 0
        ) {
            $humanDecisionEligibility = 'ELIGIBLE_WITH_GOVERNANCE_CONDITIONS';
        } else {
            $humanDecisionEligibility = 'ELIGIBLE_FOR_HUMAN_DECISION';
        }

        /*
        |--------------------------------------------------------------------------
        | Eligibility score
        |--------------------------------------------------------------------------
        */

        $eligibilityScore = 100.0;

        $eligibilityScore -= min(40, $blockingConditions * 10);
        $eligibilityScore -= min(15, $constrainingConditions * 5);
        $eligibilityScore -= min(20, $blockingEvidence * 10);
        $eligibilityScore -= min(10, $outstandingEvidence * 3);
        $eligibilityScore -= min(15, $blockingDependencyCount * 5);

        if ($planningReadinessScore < 50) {
            $eligibilityScore -= (50 - $planningReadinessScore) * 0.20;
        }

        if ($dependencyAdjustedFeasibilityScore < 50) {
            $eligibilityScore -= (50 - $dependencyAdjustedFeasibilityScore) * 0.15;
        }

        $eligibilityScore = round(max(0, min(100, $eligibilityScore)), 2);

        /*
        |--------------------------------------------------------------------------
        | Decision risk score
        |--------------------------------------------------------------------------
        */

        $decisionRiskScore = round(
            min(
                100,
                (
                    ($planRiskScore * 0.30) +
                    ($conditionPressureScore * 0.25) +
                    ((100 - $decisionEvidenceReadinessScore) * 0.20) +
                    ((100 - $planningReadinessScore) * 0.15) +
                    ((100 - $dependencyAdjustedFeasibilityScore) * 0.10)
                )
            ),
            2
        );

        if (
            $criticalOpenConditions > 0 ||
            $criticalOutstandingEvidence > 0 ||
            $decisionRiskScore >= 90
        ) {
            $decisionRiskLevel = 'CRITICAL_HUMAN_DECISION_RISK';
        } elseif ($decisionRiskScore >= 70) {
            $decisionRiskLevel = 'HIGH_HUMAN_DECISION_RISK';
        } elseif ($decisionRiskScore >= 50) {
            $decisionRiskLevel = 'MODERATE_HUMAN_DECISION_RISK';
        } elseif ($decisionRiskScore >= 25) {
            $decisionRiskLevel = 'ELEVATED_HUMAN_DECISION_RISK';
        } else {
            $decisionRiskLevel = 'CONTROLLED_HUMAN_DECISION_RISK';
        }

        /*
        |--------------------------------------------------------------------------
        | Human decision readiness
        |--------------------------------------------------------------------------
        */

        if ($criticalOpenConditions > 0 || $criticalOutstandingEvidence > 0) {
            $humanDecisionReadiness = 'CRITICAL_RESOLUTION_REQUIRED_BEFORE_DECISION';
        } elseif ($blockingConditions > 0 || $blockingEvidence > 0) {
            $humanDecisionReadiness = 'MATERIAL_CONDITIONS_REQUIRE_HUMAN_GOVERNANCE';
        } elseif ($constrainingConditions > 0 || $outstandingEvidence > 0) {
            $humanDecisionReadiness = 'CONDITIONAL_HUMAN_DECISION_READINESS';
        } else {
            $humanDecisionReadiness = 'READY_FOR_HUMAN_DECISION_CONSIDERATION';
        }

        /*
        |--------------------------------------------------------------------------
        | Risk control status
        |--------------------------------------------------------------------------
        */

        if ($decisionRiskLevel === 'CRITICAL_HUMAN_DECISION_RISK') {
            $riskControlStatus = 'CRITICAL_HUMAN_GOVERNANCE_ATTENTION_REQUIRED';
        } elseif ($decisionRiskLevel === 'HIGH_HUMAN_DECISION_RISK') {
            $riskControlStatus = 'CONTROLLED_WITH_HIGH_HUMAN_DECISION_ATTENTION';
        } elseif ($decisionRiskLevel === 'MODERATE_HUMAN_DECISION_RISK') {
            $riskControlStatus = 'CONTROLLED_WITH_MODERATE_HUMAN_DECISION_ATTENTION';
        } else {
            $riskControlStatus = 'CONTROLLED_HUMAN_DECISION_ENVIRONMENT';
        }

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0 ||
            $criticalOutstandingEvidence > 0;

        /*
        |--------------------------------------------------------------------------
        | Eligibility blockers
        |--------------------------------------------------------------------------
        */

        $eligibilityBlockers = [];

        foreach (($conditionEvidence['condition_analysis'] ?? []) as $condition) {
            if (($condition['condition_open'] ?? false) &&
                ($condition['blocks_final_decision'] ?? false)) {
                $eligibilityBlockers[] = [
                    'blocker_type' => 'DECISION_CONDITION',
                    'blocker_code' => $condition['condition_code'] ?? null,
                    'category' => $condition['condition_type'] ?? null,
                    'priority_level' => $condition['priority_level'] ?? null,
                    'message' => $condition['condition'] ?? null,
                    'requires_human_resolution' => true,
                    'automatic_resolution_allowed' => false,
                ];
            }
        }

        foreach (($conditionEvidence['evidence_analysis'] ?? []) as $evidence) {
            if (($evidence['evidence_outstanding'] ?? false) &&
                ($evidence['blocks_final_decision'] ?? false)) {
                $eligibilityBlockers[] = [
                    'blocker_type' => 'EVIDENCE_REQUIREMENT',
                    'blocker_code' => $evidence['evidence_code'] ?? null,
                    'category' => $evidence['evidence_type'] ?? null,
                    'priority_level' => $evidence['priority_level'] ?? null,
                    'message' => $evidence['requirement'] ?? null,
                    'requires_human_validation' => true,
                    'automatic_validation_allowed' => false,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Eligibility constraints
        |--------------------------------------------------------------------------
        */

        $eligibilityConstraints = [];

        foreach (($conditionEvidence['condition_analysis'] ?? []) as $condition) {
            if (($condition['condition_open'] ?? false) &&
                ($condition['constrains_final_decision'] ?? false)) {
                $eligibilityConstraints[] = [
                    'constraint_type' => 'DECISION_CONDITION',
                    'constraint_code' => $condition['condition_code'] ?? null,
                    'category' => $condition['condition_type'] ?? null,
                    'priority_level' => $condition['priority_level'] ?? null,
                    'message' => $condition['condition'] ?? null,
                    'requires_human_resolution' => true,
                    'automatic_resolution_allowed' => false,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Risk factors
        |--------------------------------------------------------------------------
        */

        $riskFactors = [
            [
                'risk_factor' => 'STRATEGIC_PLAN_RISK',
                'value' => $planRiskScore,
                'severity' => $planRiskScore >= 70 ? 'HIGH' : ($planRiskScore >= 50 ? 'MODERATE' : 'ADVISORY'),
            ],
            [
                'risk_factor' => 'CONDITION_PRESSURE',
                'value' => $conditionPressureScore,
                'severity' => $conditionPressureScore >= 70 ? 'HIGH' : ($conditionPressureScore >= 50 ? 'MODERATE' : 'ADVISORY'),
            ],
            [
                'risk_factor' => 'DECISION_EVIDENCE_READINESS',
                'value' => $decisionEvidenceReadinessScore,
                'severity' => $decisionEvidenceReadinessScore < 25 ? 'HIGH' : ($decisionEvidenceReadinessScore < 50 ? 'MODERATE' : 'ADVISORY'),
            ],
            [
                'risk_factor' => 'PLANNING_READINESS',
                'value' => $planningReadinessScore,
                'severity' => $planningReadinessScore < 35 ? 'HIGH' : ($planningReadinessScore < 50 ? 'MODERATE' : 'ADVISORY'),
            ],
            [
                'risk_factor' => 'DEPENDENCY_ADJUSTED_FEASIBILITY',
                'value' => $dependencyAdjustedFeasibilityScore,
                'severity' => $dependencyAdjustedFeasibilityScore < 25 ? 'HIGH' : ($dependencyAdjustedFeasibilityScore < 50 ? 'MODERATE' : 'ADVISORY'),
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Human decision eligibility and risk intelligence is based on strategic plan decision record {$decision->id}.",
            "Current human governance review eligibility is {$humanReviewEligibility}.",
            "Current human approval eligibility is {$humanApprovalEligibility}.",
            "Current overall human decision eligibility is {$humanDecisionEligibility}.",
            "Current human decision eligibility score is {$eligibilityScore}.",
            "Current human decision readiness is {$humanDecisionReadiness}.",
            "Current human decision risk score is {$decisionRiskScore}.",
            "Current human decision risk level is {$decisionRiskLevel}.",
            "Current decision risk control status is {$riskControlStatus}.",
            "{$openConditions} decision condition(s) remain open.",
            "{$blockingConditions} blocking decision condition(s) remain active.",
            "{$constrainingConditions} constraining decision condition(s) remain active.",
            "{$outstandingEvidence} evidence requirement(s) remain outstanding.",
            "{$blockingEvidence} outstanding evidence requirement(s) currently block unrestricted final approval.",
            "{$blockingDependencyCount} blocking strategic planning dependency condition(s) remain represented.",
            "Current planning readiness score is {$planningReadinessScore}.",
            "Current dependency-adjusted feasibility score is {$dependencyAdjustedFeasibilityScore}.",
            "Current strategic plan risk score is {$planRiskScore}.",
            'Eligibility for human governance review does not mean eligibility for unrestricted approval.',
            'Human decision eligibility and risk intelligence remains advisory and does not make or record the final governance decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!empty($eligibilityBlockers)) {
            $firstBlocker = $eligibilityBlockers[0]['blocker_code'] ?? 'UNKNOWN_BLOCKER';

            $managementPriorities[] =
                "Address the highest-priority human decision eligibility blocker {$firstBlocker} through the established human governance process.";
        }

        if ($blockingEvidence > 0) {
            $managementPriorities[] =
                'Provide and validate outstanding decision-blocking evidence before unrestricted strategic plan approval is considered.';
        }

        if ($blockingConditions > 0) {
            $managementPriorities[] =
                'Resolve or formally govern active blocking decision conditions before unrestricted strategic plan approval.';
        }

        if ($constrainingConditions > 0) {
            $managementPriorities[] =
                'Reduce active constraining decision conditions to improve human decision readiness.';
        }

        if ($planningReadinessScore < 50) {
            $managementPriorities[] =
                'Improve strategic planning readiness before considering unrestricted strategic plan approval.';
        }

        if ($dependencyAdjustedFeasibilityScore < 50) {
            $managementPriorities[] =
                'Improve dependency-adjusted strategic plan feasibility through governed dependency reduction.';
        }

        if ($decisionRiskScore >= 70) {
            $managementPriorities[] =
                'Maintain elevated authorized human governance oversight while human decision risk remains high.';
        }

        $managementPriorities[] =
            'Ensure eligibility analysis is used to support, not replace, the authorized human governance decision.';

        $managementPriorities[] =
            'Preserve evidence quality, human review, governance validation, traceability, safety controls, and authority separation throughout final decision progression.';

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_ELIGIBILITY_RISK_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,
            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,
            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'human_decision_eligibility_state' => [
                'human_review_eligibility' => $humanReviewEligibility,
                'human_approval_eligibility' => $humanApprovalEligibility,
                'human_decision_eligibility' => $humanDecisionEligibility,
                'human_decision_eligibility_score' => $eligibilityScore,
                'human_decision_readiness' => $humanDecisionReadiness,
                'eligible_for_human_resolution' => $eligibleForHumanResolution,
                'final_human_decision_recorded' => $finalHumanDecisionRecorded,
                'human_management_attention_required' => true,
                'immediate_human_intervention_required' => $immediateHumanInterventionRequired,
            ],

            'human_decision_risk_state' => [
                'human_decision_risk_level' => $decisionRiskLevel,
                'human_decision_risk_score' => $decisionRiskScore,
                'risk_control_status' => $riskControlStatus,
                'governance_integrity_intact' => true,
                'human_management_attention_required' => true,
                'immediate_human_intervention_required' => $immediateHumanInterventionRequired,
            ],

            'eligibility_summary' => [
                'open_conditions' => $openConditions,
                'blocking_conditions' => $blockingConditions,
                'constraining_conditions' => $constrainingConditions,
                'critical_open_conditions' => $criticalOpenConditions,
                'high_open_conditions' => $highOpenConditions,
                'outstanding_evidence_items' => $outstandingEvidence,
                'decision_blocking_evidence_items' => $blockingEvidence,
                'critical_outstanding_evidence_items' => $criticalOutstandingEvidence,
                'high_outstanding_evidence_items' => $highOutstandingEvidence,
                'blocking_dependency_count' => $blockingDependencyCount,
                'constraining_dependency_count' => $constrainingDependencyCount,
            ],

            'eligibility_blockers' => $eligibilityBlockers,

            'eligibility_constraints' => $eligibilityConstraints,

            'risk_factors' => $riskFactors,

            'condition_evidence_context' => [
                'condition_evidence_status' => $conditionState['condition_evidence_status'] ?? null,
                'decision_block_status' => $conditionState['decision_block_status'] ?? null,
                'condition_pressure_score' => $conditionPressureScore,
                'condition_resolution_score' => $conditionResolutionScore,
                'evidence_readiness_score' => $evidenceReadinessScore,
                'decision_evidence_readiness_score' => $decisionEvidenceReadinessScore,
                'human_resolution_readiness' => $conditionState['human_resolution_readiness'] ?? null,
            ],

            'planning_risk_context' => [
                'planning_readiness' => $planningContext['planning_readiness'] ?? null,
                'planning_readiness_score' => $planningReadinessScore,
                'plan_risk_level' => $planningContext['plan_risk_level'] ?? null,
                'plan_risk_score' => $planRiskScore,
                'decision_priority' => $planningContext['decision_priority'] ?? null,
                'decision_priority_score' => $decisionPriorityScore,
                'dependency_adjusted_feasibility_score' => $dependencyAdjustedFeasibilityScore,
            ],

            'review_context' => [
                'reviewed' => $reviewContext['reviewed'] ?? false,
                'reviewed_by' => $reviewContext['reviewed_by'] ?? null,
                'reviewer_role' => $reviewContext['reviewer_role'] ?? null,
                'reviewed_at' => $reviewContext['reviewed_at'] ?? null,
                'review_state' => $reviewContext['review_state'] ?? null,
                'human_review_required' => true,
                'governance_validation_required' => true,
            ],

            'dominant_open_condition' => $conditionEvidence['dominant_open_condition'] ?? null,

            'dominant_evidence_requirement' => $conditionEvidence['dominant_evidence_requirement'] ?? null,

            'eligibility_risk_findings' => $findings,

            'management_priorities' => array_values(array_unique($managementPriorities)),

            'human_decision_eligibility_risk_guardrails' => [
                'human_decision_eligibility_risk_intelligence_enabled' => true,

                'eligibility_is_human_final_decision' => false,
                'eligibility_is_governance_approval' => false,
                'eligibility_is_governance_rejection' => false,
                'eligibility_is_plan_activation' => false,

                'review_eligibility_equals_approval_eligibility' => false,

                'eligibility_score_authorizes_approval' => false,
                'eligibility_score_authorizes_rejection' => false,
                'decision_risk_score_authorizes_approval' => false,
                'decision_risk_score_authorizes_rejection' => false,

                'eligibility_analysis_changes_decision_status' => false,
                'eligibility_analysis_changes_plan_status' => false,
                'eligibility_analysis_changes_action_state' => false,
                'eligibility_analysis_changes_priority' => false,
                'eligibility_analysis_changes_eligibility' => false,

                'eligibility_analysis_resolves_conditions' => false,
                'eligibility_analysis_resolves_dependencies' => false,
                'eligibility_analysis_validates_evidence' => false,

                'human_review_eligibility_authorizes_execution' => false,
                'human_approval_eligibility_authorizes_execution' => false,

                'risk_level_expands_ai_authority' => false,
                'high_risk_authorizes_automation' => false,
                'blocking_condition_authorizes_automation' => false,

                'eligibility_analysis_authorizes_ai_change' => false,
                'eligibility_analysis_authorizes_execution' => false,
                'eligibility_analysis_authorizes_deployment' => false,
                'eligibility_analysis_authorizes_rollback' => false,
                'eligibility_analysis_authorizes_clinical_action' => false,

                'eligibility_analysis_overrides_human_review' => false,
                'eligibility_analysis_overrides_evidence_requirements' => false,

                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' => 'Governance strategic plan human decision eligibility and risk intelligence evaluates whether a prepared strategic plan decision package is eligible for authorized human governance review, whether material conditions prevent unrestricted approval, and what decision risk remains. Eligibility for review does not constitute approval. Eligibility and risk intelligence does not make or record the final governance decision, approve or reject the strategic plan, resolve conditions or dependencies, validate evidence, activate planning work, change plan or action state, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}