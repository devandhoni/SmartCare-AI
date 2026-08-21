<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionRecommendationIntelligenceEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'analysis_completed' => false,
                'status' => 'SNAPSHOT_NOT_FOUND',
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'snapshot_id' => $snapshotId,
            ];
        }

        $decision = app(
            AIGovernanceDecisionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $consistency = app(
            AIGovernanceDecisionConsistencyIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIGovernanceDecisionRiskIntelligenceEngine::class
        )->analyze($snapshot->id);

        $trend = app(
            AIGovernanceDecisionTrendIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($decision['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($consistency['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_CONSISTENCY_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($risk['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_RISK_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($trend['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_TREND_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        $activeActions = $actions->filter(
            fn ($action) =>
                !in_array(
                    $action->action_status,
                    [
                        'RESOLVED',
                        'CLOSED_REJECTED',
                    ],
                    true
                )
        );

        $pendingReviewActions = $actions->where(
            'action_status',
            'PENDING_REVIEW'
        );

        $evidenceDependentActions = $actions->where(
            'action_status',
            'MORE_EVIDENCE_REQUIRED'
        );

        $deferredActions = $actions->where(
            'action_status',
            'DEFERRED'
        );

        $approvedUnresolvedActions = $actions->where(
            'action_status',
            'APPROVED'
        );

        $highPriorityActiveActions = $activeActions->filter(
            fn ($action) =>
                in_array(
                    $action->priority_level,
                    ['HIGH', 'CRITICAL'],
                    true
                )
        );

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | Pending Human Review
        |--------------------------------------------------------------------------
        */

        if ($pendingReviewActions->isNotEmpty()) {
            $highest = $pendingReviewActions
                ->sortByDesc('priority_score')
                ->first();

            $recommendations[] = [
                'recommendation_code' =>
                    'COMPLETE_PENDING_HUMAN_REVIEWS',

                'recommendation_category' =>
                    'HUMAN_GOVERNANCE_REVIEW',

                'priority_level' =>
                    $highest
                        && $highest->priority_level === 'HIGH'
                        ? 'HIGH'
                        : 'MODERATE',

                'recommendation' =>
                    'Complete pending human governance reviews in descending priority order.',

                'reason' =>
                    $pendingReviewActions->count()
                    . ' governance action(s) remain pending human review.',

                'related_action_count' =>
                    $pendingReviewActions->count(),

                'highest_related_action' =>
                    $this->actionContext($highest),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Collection
        |--------------------------------------------------------------------------
        */

        if ($evidenceDependentActions->isNotEmpty()) {
            $highest = $evidenceDependentActions
                ->sortByDesc('priority_score')
                ->first();

            $recommendations[] = [
                'recommendation_code' =>
                    'COLLECT_REQUIRED_GOVERNANCE_EVIDENCE',

                'recommendation_category' =>
                    'EVIDENCE',

                'priority_level' =>
                    in_array(
                        $highest?->priority_level,
                        ['HIGH', 'CRITICAL'],
                        true
                    )
                    ? 'HIGH'
                    : 'MODERATE',

                'recommendation' =>
                    'Collect the additional validated evidence required by evidence-dependent governance actions.',

                'reason' =>
                    $evidenceDependentActions->count()
                    . ' governance action(s) currently require additional evidence before further resolution.',

                'related_action_count' =>
                    $evidenceDependentActions->count(),

                'highest_related_action' =>
                    $this->actionContext($highest),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Deferred Action Observation
        |--------------------------------------------------------------------------
        */

        if ($deferredActions->isNotEmpty()) {
            $highest = $deferredActions
                ->sortByDesc('priority_score')
                ->first();

            $recommendations[] = [
                'recommendation_code' =>
                    'MAINTAIN_DEFERRED_ACTION_OBSERVATION',

                'recommendation_category' =>
                    'DEFERRED_GOVERNANCE',

                'priority_level' =>
                    'ADVISORY',

                'recommendation' =>
                    'Maintain deferred governance actions under governed observation until reassessment conditions are satisfied.',

                'reason' =>
                    $deferredActions->count()
                    . ' governance action(s) remain deferred.',

                'related_action_count' =>
                    $deferredActions->count(),

                'highest_related_action' =>
                    $this->actionContext($highest),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Approved But Unresolved
        |--------------------------------------------------------------------------
        */

        if ($approvedUnresolvedActions->isNotEmpty()) {
            $highest = $approvedUnresolvedActions
                ->sortByDesc('priority_score')
                ->first();

            $recommendations[] = [
                'recommendation_code' =>
                    'REVIEW_APPROVED_UNRESOLVED_ACTIONS',

                'recommendation_category' =>
                    'RESOLUTION',

                'priority_level' =>
                    'HIGH',

                'recommendation' =>
                    'Review approved governance actions that remain unresolved and determine the appropriate governed resolution state.',

                'reason' =>
                    $approvedUnresolvedActions->count()
                    . ' approved governance action(s) remain unresolved.',

                'related_action_count' =>
                    $approvedUnresolvedActions->count(),

                'highest_related_action' =>
                    $this->actionContext($highest),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | High-Priority Active Governance Work
        |--------------------------------------------------------------------------
        */

        if ($highPriorityActiveActions->isNotEmpty()) {
            $highest = $highPriorityActiveActions
                ->sortByDesc('priority_score')
                ->first();

            $recommendations[] = [
                'recommendation_code' =>
                    'PRIORITIZE_HIGH_GOVERNANCE_WORK',

                'recommendation_category' =>
                    'PRIORITY_MANAGEMENT',

                'priority_level' =>
                    'HIGH',

                'recommendation' =>
                    'Prioritize unresolved high-priority governance work for human attention.',

                'reason' =>
                    $highPriorityActiveActions->count()
                    . ' high or critical priority governance action(s) remain active.',

                'related_action_count' =>
                    $highPriorityActiveActions->count(),

                'highest_related_action' =>
                    $this->actionContext($highest),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Consistency Exceptions
        |--------------------------------------------------------------------------
        */

        $contradictionCount = (int) (
            $consistency[
                'consistency_summary'
            ]['contradiction_count']
            ?? 0
        );

        if ($contradictionCount > 0) {
            $recommendations[] = [
                'recommendation_code' =>
                    'REVIEW_DECISION_STATE_CONTRADICTIONS',

                'recommendation_category' =>
                    'GOVERNANCE_INTEGRITY',

                'priority_level' =>
                    'HIGH',

                'recommendation' =>
                    'Review governance decision-state contradictions before further lifecycle progression.',

                'reason' =>
                    "{$contradictionCount} explicit governance decision-state contradiction(s) are currently detected.",

                'related_action_count' =>
                    $contradictionCount,

                'highest_related_action' =>
                    null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Exceptions
        |--------------------------------------------------------------------------
        */

        $riskLevel =
            $risk['decision_risk_level']
            ?? 'UNKNOWN';

        if (
            in_array(
                $riskLevel,
                ['HIGH', 'CRITICAL'],
                true
            )
        ) {
            $recommendations[] = [
                'recommendation_code' =>
                    'ESCALATE_DECISION_RISK_REVIEW',

                'recommendation_category' =>
                    'RISK',

                'priority_level' =>
                    'CRITICAL',

                'recommendation' =>
                    'Escalate governance decision risk intelligence for immediate human governance review.',

                'reason' =>
                    "Current decision risk level is {$riskLevel}.",

                'related_action_count' =>
                    $activeActions->count(),

                'highest_related_action' =>
                    $this->actionContext(
                        $activeActions
                            ->sortByDesc('priority_score')
                            ->first()
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Maturity / Trend Confidence
        |--------------------------------------------------------------------------
        */

        $trendConfidence =
            $trend[
                'trend_summary'
            ]['trend_confidence']
            ?? 'UNKNOWN';

        $consistencyConfidence =
            $consistency[
                'consistency_summary'
            ]['consistency_confidence']
            ?? 'UNKNOWN';

        $limitedDecisionIntelligence =
            in_array(
                $trendConfidence,
                [
                    'EXTREMELY_LIMITED',
                    'VERY_LIMITED',
                    'LIMITED',
                    'INSUFFICIENT_DATA',
                ],
                true
            )
            ||
            in_array(
                $consistencyConfidence,
                [
                    'VERY_LIMITED',
                    'LIMITED',
                    'INSUFFICIENT_DATA',
                ],
                true
            );

        if ($limitedDecisionIntelligence) {
            $recommendations[] = [
                'recommendation_code' =>
                    'INCREASE_DECISION_EVIDENCE_BASE',

                'recommendation_category' =>
                    'INTELLIGENCE_MATURITY',

                'priority_level' =>
                    'ADVISORY',

                'recommendation' =>
                    'Increase the human governance decision evidence base before treating decision patterns or trends as mature intelligence.',

                'reason' =>
                    "Trend confidence is {$trendConfidence} and consistency confidence is {$consistencyConfidence}.",

                'related_action_count' =>
                    $decision[
                        'decision_summary'
                    ]['total_decisions']
                    ?? 0,

                'highest_related_action' =>
                    null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safe Default
        |--------------------------------------------------------------------------
        */

        if (empty($recommendations)) {
            $recommendations[] = [
                'recommendation_code' =>
                    'CONTINUE_ROUTINE_GOVERNANCE_MONITORING',

                'recommendation_category' =>
                    'ROUTINE_MONITORING',

                'priority_level' =>
                    'ADVISORY',

                'recommendation' =>
                    'Continue routine human-governed decision monitoring.',

                'reason' =>
                    'No material governance decision attention condition is currently detected.',

                'related_action_count' =>
                    0,

                'highest_related_action' =>
                    null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Ranking
        |--------------------------------------------------------------------------
        */

        $priorityRank = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
        ];

        usort(
            $recommendations,
            fn ($a, $b) =>
                (
                    $priorityRank[$b['priority_level']]
                    ?? 0
                )
                <=>
                (
                    $priorityRank[$a['priority_level']]
                    ?? 0
                )
        );

        $topRecommendation =
            $recommendations[0]
            ?? null;

        $summary = [
            'total_recommendations' =>
                count($recommendations),

            'critical_recommendations' =>
                collect($recommendations)
                    ->where(
                        'priority_level',
                        'CRITICAL'
                    )
                    ->count(),

            'high_recommendations' =>
                collect($recommendations)
                    ->where(
                        'priority_level',
                        'HIGH'
                    )
                    ->count(),

            'moderate_recommendations' =>
                collect($recommendations)
                    ->where(
                        'priority_level',
                        'MODERATE'
                    )
                    ->count(),

            'advisory_recommendations' =>
                collect($recommendations)
                    ->where(
                        'priority_level',
                        'ADVISORY'
                    )
                    ->count(),

            'top_recommendation_code' =>
                $topRecommendation[
                    'recommendation_code'
                ]
                ?? null,

            'top_recommendation_priority' =>
                $topRecommendation[
                    'priority_level'
                ]
                ?? null,
        ];

        $findings = [
            count($recommendations)
                . ' governance decision management recommendation(s) were generated.',

            'Recommendations describe human governance attention and management priorities only.',

            'Current decision risk level is '
                . $riskLevel
                . '.',

            'Current decision consistency classification is '
                . (
                    $consistency[
                        'consistency_summary'
                    ]['consistency_status']
                    ?? 'UNKNOWN'
                )
                . '.',

            'Current governance decision trend classification is '
                . (
                    $trend[
                        'trend_summary'
                    ]['trend_direction']
                    ?? 'UNKNOWN'
                )
                . '.',

            'No recommendation constitutes a human governance decision or execution authorization.',
        ];

        if ($limitedDecisionIntelligence) {
            $findings[] =
                'Recommendation interpretation remains evidence-sensitive because current governance decision intelligence confidence is limited.';
        }

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'recommendation_mode' =>
                'HUMAN_GOVERNANCE_ATTENTION_ADVISORY',

            'recommendation_summary' =>
                $summary,

            'top_recommendation' =>
                $topRecommendation,

            'recommendations' =>
                $recommendations,

            'source_intelligence_context' => [
                'decision_state' =>
                    $decision[
                        'decision_state'
                    ]
                    ?? null,

                'decision_completion_percentage' =>
                    $decision[
                        'decision_summary'
                    ]['decision_completion_percentage']
                    ?? null,

                'consistency_status' =>
                    $consistency[
                        'consistency_summary'
                    ]['consistency_status']
                    ?? null,

                'consistency_score' =>
                    $consistency[
                        'consistency_summary'
                    ]['consistency_score']
                    ?? null,

                'decision_risk_level' =>
                    $riskLevel,

                'decision_risk_score' =>
                    $risk[
                        'decision_risk_score'
                    ]
                    ?? null,

                'trend_direction' =>
                    $trend[
                        'trend_summary'
                    ]['trend_direction']
                    ?? null,

                'trend_confidence' =>
                    $trendConfidence,
            ],

            'recommendation_findings' =>
                $findings,

            'recommendation_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function actionContext(
        $action
    ): ?array {
        if (!$action) {
            return null;
        }

        return [
            'action_id' =>
                $action->id,

            'action_code' =>
                $action->action_code,

            'action_category' =>
                $action->action_category,

            'action_status' =>
                $action->action_status,

            'priority_level' =>
                $action->priority_level,

            'priority_score' =>
                (int) $action->priority_score,

            'review_decision' =>
                $action->review_decision,
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_recommendation_intelligence_enabled' =>
                true,

            'recommendation_is_human_governance_decision' =>
                false,

            'recommendation_predicts_required_decision' =>
                false,

            'recommendation_is_approval' =>
                false,

            'recommendation_is_rejection' =>
                false,

            'recommendation_is_resolution' =>
                false,

            'recommendation_changes_action_state' =>
                false,

            'recommendation_changes_priority' =>
                false,

            'recommendation_changes_eligibility' =>
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
                'Governance decision recommendation intelligence recommends human governance attention and management priorities only. It does not recommend or make an APPROVE, REJECT, DEFER, or REQUEST_MORE_EVIDENCE decision, alter action state, modify priority, change eligibility, execute changes, deploy updates, trigger rollback, modify AI behavior, or initiate clinical action.',
        ];
    }
}