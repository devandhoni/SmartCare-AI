<?php

namespace App\Services;

use App\Models\AIGovernanceAction;

class AIGovernanceStrategicRecommendationIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine,
        protected AIGovernanceStrategicConstraintIntelligenceEngine $constraintEngine,
        protected AIGovernanceStrategicRiskIntelligenceEngine $riskEngine,
    ) {
    }

    public function analyze(?int $strategicSnapshotId = null): array
    {
        $strategicState = $this->strategicStateEngine->analyze($strategicSnapshotId);
        $capacityDemand = $this->capacityDemandEngine->analyze($strategicSnapshotId);
        $constraints = $this->constraintEngine->analyze($strategicSnapshotId);
        $risk = $this->riskEngine->analyze($strategicSnapshotId);

        if (
            !($strategicState['analysis_completed'] ?? false) ||
            !($capacityDemand['analysis_completed'] ?? false) ||
            !($constraints['analysis_completed'] ?? false) ||
            !($risk['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_RECOMMENDATION_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Required strategic governance intelligence is not currently available.',
            ];
        }

        $state = $strategicState['strategic_state'] ?? [];
        $capacity = $capacityDemand['capacity_demand_state'] ?? [];
        $constraintState = $constraints['constraint_state'] ?? [];
        $riskState = $risk['strategic_risk_state'] ?? [];
        $riskSummary = $risk['risk_summary'] ?? [];
        $workContext = $constraints['governance_work_context'] ?? [];

        $strategicReadinessScore = (float) ($state['strategic_readiness_score'] ?? 0);
        $strategicBalanceScore = (float) ($state['strategic_balance_score'] ?? 0);

        $capacityScore = (float) ($capacity['capacity_score'] ?? 0);
        $pressureScore = (float) ($capacity['demand_pressure_score'] ?? 0);
        $capacityDemandGap = (float) ($capacity['capacity_demand_gap'] ?? 0);

        $constraintScore = (float) ($constraintState['constraint_score'] ?? 0);
        $strategicRiskScore = (float) ($riskState['strategic_risk_score'] ?? 0);

        $pendingHumanReviews = (int) ($workContext['pending_human_reviews'] ?? 0);
        $evidenceWaitingActions = (int) ($workContext['evidence_waiting_actions'] ?? 0);
        $deferredActions = (int) ($workContext['deferred_actions'] ?? 0);
        $highPriorityActions = (int) ($workContext['high_priority_active_actions'] ?? 0);
        $criticalPriorityActions = (int) ($workContext['critical_priority_active_actions'] ?? 0);

        $closurePercentage = (float) ($workContext['action_closure_percentage'] ?? 0);
        $decisionCompletionPercentage = (float) ($workContext['decision_completion_percentage'] ?? 0);

        $recommendations = [];

        if (($criticalPriorityActions + $highPriorityActions) > 0) {
            $recommendations[] = $this->buildRecommendation(
                'PRIORITIZE_HIGH_STRATEGIC_GOVERNANCE_WORK',
                'PRIORITY_MANAGEMENT',
                $criticalPriorityActions > 0 ? 'CRITICAL' : 'HIGH',
                'Prioritize unresolved high or critical priority governance work for human strategic attention.',
                ($criticalPriorityActions + $highPriorityActions) .
                    ' high or critical priority governance action(s) remain active.',
                $criticalPriorityActions + $highPriorityActions,
                $this->getHighestPriorityActiveAction()
            );
        }

        if ($evidenceWaitingActions > 0) {
            $recommendations[] = $this->buildRecommendation(
                'INCREASE_VALIDATED_GOVERNANCE_EVIDENCE',
                'EVIDENCE_CAPACITY',
                'HIGH',
                'Increase validated evidence availability for governance work currently blocked by evidence dependency.',
                "{$evidenceWaitingActions} governance action(s) remain unable to progress without additional validated evidence.",
                $evidenceWaitingActions,
                $this->getHighestEvidenceDependentAction()
            );
        }

        if ($capacityScore < 50) {
            $recommendations[] = $this->buildRecommendation(
                'STRENGTHEN_GOVERNANCE_CAPACITY',
                'CAPACITY',
                'HIGH',
                'Strengthen governance capacity before materially increasing strategic governance workload.',
                "Current governance capacity score is {$capacityScore}, below the preferred strategic operating threshold.",
                null,
                null
            );
        }

        if ($pressureScore >= 60) {
            $recommendations[] = $this->buildRecommendation(
                'REDUCE_GOVERNANCE_DEMAND_PRESSURE',
                'DEMAND_MANAGEMENT',
                'HIGH',
                'Reduce governance demand pressure through governed progression of existing work before materially expanding workload.',
                "Current governance demand pressure score is {$pressureScore}.",
                null,
                null
            );
        }

        if ($capacityDemandGap >= 20) {
            $recommendations[] = $this->buildRecommendation(
                'REDUCE_CAPACITY_DEMAND_IMBALANCE',
                'CAPACITY_BALANCE',
                'HIGH',
                'Reduce the material imbalance between governance demand and available governance capacity.',
                "Current capacity-demand gap is {$capacityDemandGap} percentage point(s).",
                null,
                null
            );
        }

        if ($pendingHumanReviews > 0) {
            $recommendations[] = $this->buildRecommendation(
                'COMPLETE_PENDING_STRATEGIC_GOVERNANCE_REVIEWS',
                'HUMAN_GOVERNANCE',
                'MODERATE',
                'Complete outstanding human governance reviews in descending priority order.',
                "{$pendingHumanReviews} governance action(s) remain pending human review.",
                $pendingHumanReviews,
                $this->getHighestPendingReviewAction()
            );
        }

        if ($closurePercentage < 50) {
            $recommendations[] = $this->buildRecommendation(
                'IMPROVE_GOVERNANCE_CLOSURE_PROGRESSION',
                'WORKFLOW_PROGRESSION',
                'MODERATE',
                'Improve formal governance action closure progression while preserving evidence, review, safety, and authority controls.',
                "Current governance action closure is {$closurePercentage}%, below the preferred strategic progression threshold.",
                null,
                null
            );
        }

        if ($strategicReadinessScore < 50) {
            $recommendations[] = $this->buildRecommendation(
                'STRENGTHEN_STRATEGIC_READINESS',
                'STRATEGIC_READINESS',
                'MODERATE',
                'Improve strategic governance readiness before expanding governance workload or strategic commitments.',
                "Current strategic readiness score is {$strategicReadinessScore}.",
                null,
                null
            );
        }

        if ($strategicBalanceScore < 50) {
            $recommendations[] = $this->buildRecommendation(
                'RESTORE_STRATEGIC_BALANCE',
                'STRATEGIC_BALANCE',
                'MODERATE',
                'Improve balance between governance workload, capacity, decision progression, and formal closure.',
                "Current strategic balance score is {$strategicBalanceScore}.",
                null,
                null
            );
        }

        if ($deferredActions > 0) {
            $recommendations[] = $this->buildRecommendation(
                'MAINTAIN_DEFERRED_GOVERNANCE_OBSERVATION',
                'DEFERRED_GOVERNANCE',
                'ADVISORY',
                'Maintain deferred governance work under controlled observation until reassessment conditions are satisfied.',
                "{$deferredActions} governance action(s) remain deferred.",
                $deferredActions,
                $this->getHighestDeferredAction()
            );
        }

        $recommendations = collect($recommendations)
            ->sortByDesc(function (array $recommendation) {
                return $this->priorityWeight($recommendation['priority_level']);
            })
            ->values()
            ->all();

        $criticalRecommendations = collect($recommendations)
            ->where('priority_level', 'CRITICAL')
            ->count();

        $highRecommendations = collect($recommendations)
            ->where('priority_level', 'HIGH')
            ->count();

        $moderateRecommendations = collect($recommendations)
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisoryRecommendations = collect($recommendations)
            ->where('priority_level', 'ADVISORY')
            ->count();

        $topRecommendation = $recommendations[0] ?? null;

        $recommendationState = $this->determineRecommendationState(
            $criticalRecommendations,
            $highRecommendations,
            $strategicRiskScore
        );

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_STRATEGIC_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $strategicState['strategic_snapshot_id'] ?? null,
            'operational_snapshot_id' => $strategicState['operational_snapshot_id'] ?? null,
            'lifecycle_snapshot_id' => $strategicState['lifecycle_snapshot_id'] ?? null,
            'snapshot_scope' => $strategicState['snapshot_scope'] ?? null,
            'resident_id' => $strategicState['resident_id'] ?? null,

            'recommendation_state' => [
                'recommendation_mode' => 'HUMAN_STRATEGIC_GOVERNANCE_ADVISORY',
                'recommendation_status' => $recommendationState,
                'human_management_attention_required' =>
                    (bool) ($riskState['human_management_attention_required'] ?? false),
                'immediate_human_intervention_required' =>
                    (bool) ($riskState['immediate_human_intervention_required'] ?? false),
                'governance_integrity_intact' =>
                    (bool) ($riskState['governance_integrity_intact'] ?? true),
            ],

            'recommendation_summary' => [
                'total_recommendations' => count($recommendations),
                'critical_recommendations' => $criticalRecommendations,
                'high_recommendations' => $highRecommendations,
                'moderate_recommendations' => $moderateRecommendations,
                'advisory_recommendations' => $advisoryRecommendations,
                'top_recommendation_code' => $topRecommendation['recommendation_code'] ?? null,
                'top_recommendation_priority' => $topRecommendation['priority_level'] ?? null,
            ],

            'top_recommendation' => $topRecommendation,

            'recommendations' => $recommendations,

            'strategic_context' => [
                'strategic_status' => $state['strategic_status'] ?? null,
                'strategic_health' => $state['strategic_health'] ?? null,

                'strategic_readiness' => $state['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $strategicReadinessScore,

                'strategic_balance_score' => $strategicBalanceScore,

                'capacity_status' => $capacity['capacity_status'] ?? null,
                'capacity_score' => $capacityScore,

                'demand_pressure_status' => $capacity['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $pressureScore,

                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $capacity['capacity_demand_balance'] ?? null,

                'strategic_load_status' => $capacity['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $capacity['workload_expansion_readiness'] ?? null,

                'constraint_level' => $constraintState['constraint_level'] ?? null,
                'constraint_score' => $constraintScore,

                'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                'strategic_risk_score' => $strategicRiskScore,
                'risk_control_status' => $riskState['risk_control_status'] ?? null,
            ],

            'governance_work_context' => [
                'pending_human_reviews' => $pendingHumanReviews,
                'evidence_waiting_actions' => $evidenceWaitingActions,
                'deferred_actions' => $deferredActions,
                'high_priority_active_actions' => $highPriorityActions,
                'critical_priority_active_actions' => $criticalPriorityActions,
                'action_closure_percentage' => $closurePercentage,
                'decision_completion_percentage' => $decisionCompletionPercentage,
            ],

            'risk_context' => [
                'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                'strategic_risk_score' => $strategicRiskScore,
                'critical_signals' => (int) ($riskSummary['critical_signals'] ?? 0),
                'high_signals' => (int) ($riskSummary['high_signals'] ?? 0),
                'moderate_signals' => (int) ($riskSummary['moderate_signals'] ?? 0),
                'advisory_signals' => (int) ($riskSummary['advisory_signals'] ?? 0),
            ],

            'recommendation_findings' => [
                count($recommendations) . ' strategic governance management recommendation(s) are currently generated.',
                "{$criticalRecommendations} critical strategic recommendation(s) are currently generated.",
                "{$highRecommendations} high strategic recommendation(s) are currently generated.",
                "{$moderateRecommendations} moderate strategic recommendation(s) are currently generated.",
                "{$advisoryRecommendations} advisory strategic recommendation(s) are currently generated.",
                'Current strategic recommendation status is ' . $recommendationState . '.',
                'Current strategic risk level is ' . ($riskState['strategic_risk_level'] ?? 'UNKNOWN') .
                    " with risk score {$strategicRiskScore}.",
                'Current strategic constraint level is ' . ($constraintState['constraint_level'] ?? 'UNKNOWN') .
                    " with score {$constraintScore}.",
                "Current governance capacity score is {$capacityScore}.",
                "Current governance demand pressure score is {$pressureScore}.",
                "Current capacity-demand gap is {$capacityDemandGap}.",
                "Governance action closure is {$closurePercentage}%.",
                "Governance decision completion is {$decisionCompletionPercentage}%.",
                $topRecommendation
                    ? 'Top strategic governance recommendation is ' .
                        $topRecommendation['recommendation_code'] .
                        ' with ' .
                        $topRecommendation['priority_level'] .
                        ' priority.'
                    : 'No active strategic governance management recommendation is currently required.',
                'Strategic recommendation intelligence provides human management guidance only and does not make governance decisions.',
            ],

            'recommendation_guardrails' => [
                'strategic_recommendation_intelligence_enabled' => true,
                'recommendation_is_governance_decision' => false,
                'recommendation_is_governance_approval' => false,
                'recommendation_is_governance_rejection' => false,
                'recommendation_is_action_resolution' => false,
                'recommendation_changes_action_state' => false,
                'recommendation_changes_priority' => false,
                'recommendation_changes_eligibility' => false,
                'recommendation_authorizes_execution' => false,
                'recommendation_authorizes_deployment' => false,
                'recommendation_authorizes_rollback' => false,
                'recommendation_authorizes_clinical_action' => false,
                'strategic_pressure_authorizes_automation' => false,
                'capacity_shortage_authorizes_automation' => false,
                'risk_level_authorizes_ai_change' => false,
                'recommendation_overrides_human_review' => false,
                'recommendation_overrides_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' => 'Governance strategic recommendation intelligence generates human-management advisory priorities from strategic readiness, capacity, demand, constraints, risk, evidence dependencies, review workload, and closure progression. Recommendations do not make governance decisions, change action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function buildRecommendation(
        string $code,
        string $category,
        string $priorityLevel,
        string $recommendation,
        string $reason,
        ?int $relatedActionCount = null,
        ?array $highestRelatedAction = null
    ): array {
        return [
            'recommendation_code' => $code,
            'recommendation_category' => $category,
            'priority_level' => $priorityLevel,
            'recommendation' => $recommendation,
            'reason' => $reason,
            'related_action_count' => $relatedActionCount,
            'highest_related_action' => $highestRelatedAction,
        ];
    }

    private function determineRecommendationState(
        int $criticalRecommendations,
        int $highRecommendations,
        float $strategicRiskScore
    ): string {
        if ($criticalRecommendations > 0) {
            return 'IMMEDIATE_STRATEGIC_MANAGEMENT_ATTENTION';
        }

        if ($highRecommendations >= 3 || $strategicRiskScore >= 75) {
            return 'ELEVATED_STRATEGIC_MANAGEMENT_ATTENTION';
        }

        if ($highRecommendations > 0 || $strategicRiskScore >= 50) {
            return 'STRATEGIC_MANAGEMENT_ATTENTION_REQUIRED';
        }

        return 'CONTROLLED_STRATEGIC_ADVISORY';
    }

    private function priorityWeight(string $priorityLevel): int
    {
        return match ($priorityLevel) {
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
            default => 0,
        };
    }

    private function getHighestPriorityActiveAction(): ?array
    {
        $action = AIGovernanceAction::query()
            ->whereNotIn('action_status', [
                'RESOLVED',
                'CLOSED_REJECTED',
                'CLOSED',
            ])
            ->whereIn('priority_level', [
                'CRITICAL',
                'HIGH',
            ])
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->first();

        return $this->formatAction($action);
    }

    private function getHighestPendingReviewAction(): ?array
    {
        $action = AIGovernanceAction::query()
            ->where('action_status', 'PENDING_REVIEW')
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->first();

        return $this->formatAction($action);
    }

    private function getHighestEvidenceDependentAction(): ?array
    {
        $action = AIGovernanceAction::query()
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->first();

        return $this->formatAction($action);
    }

    private function getHighestDeferredAction(): ?array
    {
        $action = AIGovernanceAction::query()
            ->where('action_status', 'DEFERRED')
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->first();

        return $this->formatAction($action);
    }

    private function formatAction($action): ?array
    {
        if (!$action) {
            return null;
        }

        return [
            'action_id' => $action->id,
            'action_code' => $action->action_code,
            'action_category' => $action->action_category,
            'action_status' => $action->action_status,
            'eligibility_status' => $action->eligibility_status,
            'priority_level' => $action->priority_level,
            'priority_score' => (int) $action->priority_score,
            'review_decision' => $action->review_decision,
        ];
    }
}