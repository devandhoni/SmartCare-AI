<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlan;
use App\Models\AIGovernanceStrategicPlanDecision;
use Illuminate\Support\Carbon;
use RuntimeException;

class AIGovernanceStrategicPlanDecisionPreparationEngine
{
    public function __construct(
        protected AIGovernanceStrategicPlanStateIntelligenceEngine $planStateEngine,
        protected AIGovernanceStrategicPlanDependencyFeasibilityIntelligenceEngine $dependencyFeasibilityEngine,
        protected AIGovernanceStrategicPlanRiskIntelligenceEngine $planRiskEngine,
        protected AIGovernanceStrategicPlanRecommendationIntelligenceEngine $recommendationEngine,
        protected AIGovernanceExecutiveStrategicPlanIntelligenceEngine $executiveEngine,
    ) {
    }

    public function prepare(?int $strategicPlanId = null): array
    {
        $strategicPlan = $this->resolveStrategicPlan($strategicPlanId);

        $planState = $this->planStateEngine->analyze($strategicPlan->id);

        $dependencyFeasibility = $this->dependencyFeasibilityEngine
            ->analyze($strategicPlan->id);

        $planRisk = $this->planRiskEngine
            ->analyze($strategicPlan->id);

        $recommendation = $this->recommendationEngine
            ->analyze($strategicPlan->id);

        $executive = $this->executiveEngine
            ->analyze($strategicPlan->id);

        $this->validateSourceIntelligence(
            $planState,
            $dependencyFeasibility,
            $planRisk,
            $recommendation,
            $executive
        );

        $planStateContext = $planState['plan_state'] ?? [];

        $dependencyState =
            $dependencyFeasibility['dependency_feasibility_state'] ?? [];

        $dependencySummary =
            $dependencyFeasibility['dependency_summary'] ?? [];

        $planRiskState =
            $planRisk['strategic_plan_risk_state'] ?? [];

        $riskSummary =
            $planRisk['risk_summary'] ?? [];

        $recommendationState =
            $recommendation['recommendation_state'] ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary'] ?? [];

        $topRecommendation =
            $recommendation['top_recommendation'] ?? null;

        $executiveState =
            $executive['executive_strategic_plan_state'] ?? [];

        $planningReadiness =
            $planStateContext['planning_readiness']
            ?? $strategicPlan->planning_readiness
            ?? 'UNKNOWN';

        $planningReadinessScore = round(
            (float) (
                $planStateContext['planning_readiness_score']
                ?? $strategicPlan->planning_readiness_score
                ?? 0
            ),
            2
        );

        $planRiskLevel =
            $planRiskState['strategic_plan_risk_level']
            ?? $strategicPlan->risk_level
            ?? 'UNKNOWN';

        $planRiskScore = round(
            (float) (
                $planRiskState['strategic_plan_risk_score']
                ?? $strategicPlan->risk_score
                ?? 0
            ),
            2
        );

        $dependencyFeasibilityStatus =
            $dependencyState['dependency_feasibility_status']
            ?? 'UNKNOWN';

        $dependencyAdjustedFeasibilityScore = round(
            (float) (
                $dependencyState['dependency_adjusted_feasibility_score']
                ?? 0
            ),
            2
        );

        $blockingDependencyCount = (int) (
            $dependencyState['blocking_dependency_count']
            ?? $dependencySummary['blocking_dependencies']
            ?? 0
        );

        $constrainingDependencyCount = (int) (
            $dependencyState['constraining_dependency_count']
            ?? $dependencySummary['constraining_dependencies']
            ?? 0
        );

        $criticalPlanRiskSignals = (int) (
            $riskSummary['critical_signals'] ?? 0
        );

        $immediateHumanInterventionRequired = (bool) (
            $planRiskState['immediate_human_intervention_required']
            ?? $executiveState['immediate_escalation_required']
            ?? false
        );

        $preparedDecision = $this->determinePreparedDecision(
            $criticalPlanRiskSignals,
            $immediateHumanInterventionRequired,
            $blockingDependencyCount,
            $constrainingDependencyCount,
            $dependencyAdjustedFeasibilityScore,
            $planningReadinessScore,
            $planRiskScore
        );

        $decisionPriority = $this->determineDecisionPriority(
            $criticalPlanRiskSignals,
            $immediateHumanInterventionRequired,
            $blockingDependencyCount,
            $planRiskScore
        );

        $decisionPriorityScore = $this->calculateDecisionPriorityScore(
            $planRiskScore,
            (float) $strategicPlan->strategic_priority_score,
            (float) ($dependencyState['dependency_pressure_score'] ?? 0),
            $blockingDependencyCount,
            $criticalPlanRiskSignals
        );

        $decisionConditions = $this->buildDecisionConditions(
            $dependencyFeasibility,
            $planState,
            $planRisk
        );

        $requiredEvidence = $this->buildRequiredEvidence(
            $dependencyFeasibility,
            $strategicPlan
        );

        $decisionRationale = $this->buildDecisionRationale(
            $preparedDecision,
            $blockingDependencyCount,
            $constrainingDependencyCount,
            $dependencyAdjustedFeasibilityScore,
            $planningReadiness,
            $planningReadinessScore,
            $planRiskLevel,
            $planRiskScore
        );

        $reviewContext = [
            'human_review_required' => true,
            'governance_validation_required' => true,

            'human_management_attention_required' => (bool) (
                $planRiskState['human_management_attention_required']
                ?? true
            ),

            'management_escalation_recommended' => (bool) (
                $executiveState['management_escalation_recommended']
                ?? false
            ),

            'immediate_escalation_required' => (bool) (
                $executiveState['immediate_escalation_required']
                ?? false
            ),

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'top_recommendation' => $topRecommendation,

            'review_state' => 'AWAITING_HUMAN_GOVERNANCE_REVIEW',
        ];

        $decisionContext = [
            'prepared_decision' => $preparedDecision,

            'decision_is_human_final_decision' => false,

            'plan_status' =>
                $planStateContext['plan_status']
                ?? $strategicPlan->plan_status,

            'strategic_plan_state' =>
                $planStateContext['strategic_plan_state']
                ?? null,

            'plan_health' =>
                $planStateContext['plan_health']
                ?? null,

            'plan_health_score' =>
                $planStateContext['plan_health_score']
                ?? null,

            'planning_readiness' =>
                $planningReadiness,

            'planning_readiness_score' =>
                $planningReadinessScore,

            'plan_feasibility' =>
                $planStateContext['plan_feasibility']
                ?? null,

            'plan_feasibility_score' =>
                $planStateContext['plan_feasibility_score']
                ?? null,

            'dependency_feasibility_status' =>
                $dependencyFeasibilityStatus,

            'dependency_resolution_readiness' =>
                $dependencyState['dependency_resolution_readiness']
                ?? null,

            'dependency_adjusted_feasibility_score' =>
                $dependencyAdjustedFeasibilityScore,

            'dependency_pressure_score' =>
                $dependencyState['dependency_pressure_score']
                ?? null,

            'blocking_dependency_count' =>
                $blockingDependencyCount,

            'constraining_dependency_count' =>
                $constrainingDependencyCount,

            'plan_risk_level' =>
                $planRiskLevel,

            'plan_risk_score' =>
                $planRiskScore,

            'risk_control_status' =>
                $planRiskState['risk_control_status']
                ?? null,

            'critical_plan_risk_signals' =>
                $criticalPlanRiskSignals,

            'recommendation_status' =>
                $recommendationState['recommendation_status']
                ?? null,

            'recommendation_count' =>
                $recommendationSummary['total_recommendations']
                ?? 0,

            'top_recommendation' =>
                $topRecommendation,

            'executive_strategic_plan_status' =>
                $executiveState['executive_strategic_plan_status']
                ?? null,

            'executive_readiness' =>
                $executiveState['executive_readiness']
                ?? null,

            'executive_confidence' =>
                $executiveState['executive_confidence']
                ?? null,

            'executive_strategic_plan_score' =>
                $executiveState['executive_strategic_plan_score']
                ?? null,
        ];

        $sourceContext = [
            'strategic_plan_id' =>
                $strategicPlan->id,

            'plan_code' =>
                $strategicPlan->plan_code,

            'strategic_snapshot_id' =>
                $strategicPlan->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $strategicPlan->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $strategicPlan->lifecycle_snapshot_id,

            'plan_scope' =>
                $strategicPlan->plan_scope,

            'resident_id' =>
                $strategicPlan->resident_id,

            'strategic_priority' =>
                $strategicPlan->strategic_priority,

            'strategic_priority_score' =>
                (float) $strategicPlan->strategic_priority_score,

            'primary_recommendation_code' =>
                $strategicPlan->primary_recommendation_code,

            'source_plan_generated_at' =>
                optional($strategicPlan->generated_at)?->toISOString(),
        ];

        $decisionCode = $this->generateDecisionCode(
            $strategicPlan->id
        );

        $decision = AIGovernanceStrategicPlanDecision::create([
            'strategic_plan_id' =>
                $strategicPlan->id,

            'strategic_snapshot_id' =>
                $strategicPlan->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $strategicPlan->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $strategicPlan->lifecycle_snapshot_id,

            'decision_scope' =>
                $strategicPlan->plan_scope,

            'resident_id' =>
                $strategicPlan->resident_id,

            'decision_code' =>
                $decisionCode,

            'decision_status' =>
                'PENDING_HUMAN_REVIEW',

            'decision_mode' =>
                'HUMAN_GOVERNED_STRATEGIC_PLAN_DECISION',

            'decision' =>
                $preparedDecision,

            'decision_priority' =>
                $decisionPriority,

            'decision_priority_score' =>
                $decisionPriorityScore,

            'decision_rationale' =>
                $decisionRationale,

            'planning_readiness' =>
                $planningReadiness,

            'planning_readiness_score' =>
                $planningReadinessScore,

            'plan_risk_level' =>
                $planRiskLevel,

            'plan_risk_score' =>
                $planRiskScore,

            'dependency_feasibility_status' =>
                $dependencyFeasibilityStatus,

            'dependency_adjusted_feasibility_score' =>
                $dependencyAdjustedFeasibilityScore,

            'blocking_dependency_count' =>
                $blockingDependencyCount,

            'constraining_dependency_count' =>
                $constrainingDependencyCount,

            'decision_conditions' =>
                $decisionConditions,

            'required_evidence' =>
                $requiredEvidence,

            'review_context' =>
                $reviewContext,

            'decision_context' =>
                $decisionContext,

            'source_context' =>
                $sourceContext,

            'reviewed_by' =>
                null,

            'reviewer_role' =>
                null,

            'reviewed_at' =>
                null,

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

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'decided_at' =>
                null,
        ]);

        return [
            'prepared' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_PREPARED',

            'message' =>
                'AI governance strategic plan decision package prepared successfully for human governance review.',

            'decision' => [
                'strategic_plan_decision_id' =>
                    $decision->id,

                'decision_code' =>
                    $decision->decision_code,

                'strategic_plan_id' =>
                    $decision->strategic_plan_id,

                'plan_code' =>
                    $strategicPlan->plan_code,

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

                'decision_status' =>
                    $decision->decision_status,

                'decision_mode' =>
                    $decision->decision_mode,

                'decision' =>
                    $decision->decision,

                'decision_priority' =>
                    $decision->decision_priority,

                'decision_priority_score' =>
                    $decision->decision_priority_score,

                'planning_readiness' =>
                    $decision->planning_readiness,

                'planning_readiness_score' =>
                    $decision->planning_readiness_score,

                'plan_risk_level' =>
                    $decision->plan_risk_level,

                'plan_risk_score' =>
                    $decision->plan_risk_score,

                'dependency_feasibility_status' =>
                    $decision->dependency_feasibility_status,

                'dependency_adjusted_feasibility_score' =>
                    $decision->dependency_adjusted_feasibility_score,

                'blocking_dependency_count' =>
                    $decision->blocking_dependency_count,

                'constraining_dependency_count' =>
                    $decision->constraining_dependency_count,

                'condition_count' =>
                    count($decisionConditions),

                'required_evidence_count' =>
                    count($requiredEvidence),
            ],

            'decision_rationale' =>
                $decisionRationale,

            'decision_conditions' =>
                $decisionConditions,

            'required_evidence' =>
                $requiredEvidence,

            'review_context' =>
                $reviewContext,

            'decision_context' =>
                $decisionContext,

            'decision_guardrails' => [
                'strategic_plan_decision_preparation_enabled' =>
                    true,

                'prepared_decision_is_human_final_decision' =>
                    false,

                'decision_preparation_is_governance_approval' =>
                    false,

                'decision_preparation_is_governance_rejection' =>
                    false,

                'decision_preparation_activates_plan' =>
                    false,

                'decision_preparation_resolves_dependencies' =>
                    false,

                'decision_preparation_changes_plan_status' =>
                    false,

                'decision_preparation_changes_action_state' =>
                    false,

                'decision_preparation_changes_priority' =>
                    false,

                'decision_preparation_changes_eligibility' =>
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

                'blocking_dependencies_are_automatically_resolved' =>
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

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Governance strategic plan decision preparation converts Step 63 planning intelligence into a structured decision package for human governance review only. The prepared decision is not a final governance decision and does not approve, reject, activate, execute, deploy, roll back, resolve dependencies, modify AI behavior, or initiate clinical action.',
            ],
        ];
    }

    protected function resolveStrategicPlan(
        ?int $strategicPlanId
    ): AIGovernanceStrategicPlan {
        $query = AIGovernanceStrategicPlan::query();

        if ($strategicPlanId !== null) {
            $strategicPlan = $query->find($strategicPlanId);
        } else {
            $strategicPlan = $query
                ->latest('id')
                ->first();
        }

        if (!$strategicPlan) {
            throw new RuntimeException(
                'No AI governance strategic plan is available for decision preparation.'
            );
        }

        return $strategicPlan;
    }

    protected function validateSourceIntelligence(
        array $planState,
        array $dependencyFeasibility,
        array $planRisk,
        array $recommendation,
        array $executive
    ): void {
        if (($planState['analysis_completed'] ?? false) !== true) {
            throw new RuntimeException(
                'Strategic plan state intelligence is not available.'
            );
        }

        if (($dependencyFeasibility['analysis_completed'] ?? false) !== true) {
            throw new RuntimeException(
                'Strategic plan dependency feasibility intelligence is not available.'
            );
        }

        if (($planRisk['analysis_completed'] ?? false) !== true) {
            throw new RuntimeException(
                'Strategic plan risk intelligence is not available.'
            );
        }

        if (($recommendation['analysis_completed'] ?? false) !== true) {
            throw new RuntimeException(
                'Strategic plan recommendation intelligence is not available.'
            );
        }

        if (($executive['analysis_completed'] ?? false) !== true) {
            throw new RuntimeException(
                'Executive strategic plan intelligence is not available.'
            );
        }
    }

    protected function determinePreparedDecision(
        int $criticalPlanRiskSignals,
        bool $immediateHumanInterventionRequired,
        int $blockingDependencyCount,
        int $constrainingDependencyCount,
        float $dependencyAdjustedFeasibilityScore,
        float $planningReadinessScore,
        float $planRiskScore
    ): string {
        if (
            $criticalPlanRiskSignals > 0
            || $immediateHumanInterventionRequired
        ) {
            return 'ESCALATE_FOR_IMMEDIATE_HUMAN_GOVERNANCE_DECISION';
        }

        if (
            $blockingDependencyCount > 0
            || $dependencyAdjustedFeasibilityScore < 40
            || $planningReadinessScore < 40
            || $planRiskScore >= 80
        ) {
            return 'REQUIRES_CONDITIONS_BEFORE_APPROVAL';
        }

        if (
            $constrainingDependencyCount > 0
            || $dependencyAdjustedFeasibilityScore < 60
            || $planningReadinessScore < 60
            || $planRiskScore >= 60
        ) {
            return 'CONDITIONAL_APPROVAL_REVIEW_RECOMMENDED';
        }

        return 'ELIGIBLE_FOR_HUMAN_APPROVAL_REVIEW';
    }

    protected function determineDecisionPriority(
        int $criticalPlanRiskSignals,
        bool $immediateHumanInterventionRequired,
        int $blockingDependencyCount,
        float $planRiskScore
    ): string {
        if (
            $criticalPlanRiskSignals > 0
            || $immediateHumanInterventionRequired
        ) {
            return 'CRITICAL';
        }

        if (
            $blockingDependencyCount > 0
            || $planRiskScore >= 75
        ) {
            return 'HIGH';
        }

        if ($planRiskScore >= 50) {
            return 'MODERATE';
        }

        return 'ADVISORY';
    }

    protected function calculateDecisionPriorityScore(
        float $planRiskScore,
        float $strategicPriorityScore,
        float $dependencyPressureScore,
        int $blockingDependencyCount,
        int $criticalPlanRiskSignals
    ): float {
        $score =
            ($planRiskScore * 0.40)
            + ($strategicPriorityScore * 0.30)
            + ($dependencyPressureScore * 0.20)
            + (min($blockingDependencyCount * 10, 30) * 0.10);

        if ($criticalPlanRiskSignals > 0) {
            $score += 10;
        }

        return round(
            min(100, max(0, $score)),
            2
        );
    }

    protected function buildDecisionConditions(
        array $dependencyFeasibility,
        array $planState,
        array $planRisk
    ): array {
        $conditions = [];

        foreach (
            $dependencyFeasibility['required_feasibility_conditions'] ?? []
            as $condition
        ) {
            $conditions[] = [
                'condition_code' =>
                    $condition['dependency_code'] ?? 'UNSPECIFIED',

                'condition_type' =>
                    $condition['dependency_type'] ?? 'DEPENDENCY',

                'priority_level' =>
                    $condition['severity'] ?? 'MODERATE',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    $condition['required_condition']
                    ?? 'Human governance review is required.',

                'human_review_required' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        $planStateContext = $planState['plan_state'] ?? [];

        if (
            (float) (
                $planStateContext['planning_readiness_score']
                ?? 0
            ) < 50
        ) {
            $conditions[] = [
                'condition_code' =>
                    'IMPROVE_PLANNING_READINESS',

                'condition_type' =>
                    'PLANNING_READINESS',

                'priority_level' =>
                    'MODERATE',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Strategic planning readiness should improve through governed progression before plan approval is considered.',

                'human_review_required' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        $planRiskState =
            $planRisk['strategic_plan_risk_state'] ?? [];

        if (
            (float) (
                $planRiskState['strategic_plan_risk_score']
                ?? 0
            ) >= 75
        ) {
            $conditions[] = [
                'condition_code' =>
                    'REDUCE_STRATEGIC_PLAN_RISK',

                'condition_type' =>
                    'PLAN_RISK',

                'priority_level' =>
                    'HIGH',

                'condition_status' =>
                    'OPEN',

                'condition' =>
                    'Strategic plan risk should be reduced or explicitly accepted by authorized human governance before approval.',

                'human_review_required' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        return array_values($conditions);
    }

    protected function buildRequiredEvidence(
        array $dependencyFeasibility,
        AIGovernanceStrategicPlan $strategicPlan
    ): array {
        $requiredEvidence = [];

        foreach (
            $dependencyFeasibility['blocking_dependencies'] ?? []
            as $dependency
        ) {
            if (
                ($dependency['dependency_type'] ?? null)
                === 'EVIDENCE'
            ) {
                $requiredEvidence[] = [
                    'evidence_code' =>
                        'VALIDATED_GOVERNANCE_EVIDENCE',

                    'evidence_type' =>
                        'GOVERNANCE_EVIDENCE',

                    'evidence_status' =>
                        'REQUIRED',

                    'priority_level' =>
                        $dependency['severity']
                        ?? 'HIGH',

                    'requirement' =>
                        'Provide additional validated governance evidence supporting resolution of the blocking evidence dependency.',

                    'source_dependency_code' =>
                        $dependency['dependency_code']
                        ?? null,

                    'human_validation_required' =>
                        true,

                    'automatic_validation_allowed' =>
                        false,
                ];
            }
        }

        $requiredEvidence[] = [
            'evidence_code' =>
                'STRATEGIC_PLAN_REVIEW_RECORD',

            'evidence_type' =>
                'HUMAN_GOVERNANCE_REVIEW',

            'evidence_status' =>
                'REQUIRED',

            'priority_level' =>
                'HIGH',

            'requirement' =>
                'Authorized human governance review of the strategic plan and decision package must be documented before any final plan decision.',

            'source_dependency_code' =>
                null,

            'human_validation_required' =>
                true,

            'automatic_validation_allowed' =>
                false,
        ];

        $requiredEvidence[] = [
            'evidence_code' =>
                'PLAN_SOURCE_TRACEABILITY',

            'evidence_type' =>
                'TRACEABILITY',

            'evidence_status' =>
                'AVAILABLE',

            'priority_level' =>
                'MODERATE',

            'requirement' =>
                "Maintain traceability to strategic plan {$strategicPlan->plan_code}, its strategic snapshot, operational snapshot, and lifecycle snapshot.",

            'source_dependency_code' =>
                null,

            'human_validation_required' =>
                true,

            'automatic_validation_allowed' =>
                false,
        ];

        return array_values($requiredEvidence);
    }

    protected function buildDecisionRationale(
        string $preparedDecision,
        int $blockingDependencyCount,
        int $constrainingDependencyCount,
        float $dependencyAdjustedFeasibilityScore,
        string $planningReadiness,
        float $planningReadinessScore,
        string $planRiskLevel,
        float $planRiskScore
    ): string {
        return sprintf(
            'Prepared human-governance decision classification is %s because %d blocking and %d constraining strategic planning dependency condition(s) remain, dependency-adjusted feasibility is %.2f, planning readiness is %s with score %.2f, and strategic plan risk is %s with score %.2f. This classification is advisory and requires an authorized human governance decision.',
            $preparedDecision,
            $blockingDependencyCount,
            $constrainingDependencyCount,
            $dependencyAdjustedFeasibilityScore,
            $planningReadiness,
            $planningReadinessScore,
            $planRiskLevel,
            $planRiskScore
        );
    }

    protected function generateDecisionCode(
        int $strategicPlanId
    ): string {
        return sprintf(
            'GOV-STRATEGIC-PLAN-DECISION-%d-%s',
            $strategicPlanId,
            Carbon::now()->format('YmdHis')
        );
    }
}