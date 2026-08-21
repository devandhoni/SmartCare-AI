<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionConsistencyIntelligenceEngine
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

        $pattern = app(
            AIGovernanceDecisionPatternAnalysisEngine::class
        )->analyze($snapshot->id);

        $correlation = app(
            AIGovernanceDecisionOutcomeCorrelationEngine::class
        )->analyze($snapshot->id);

        if (!($pattern['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_PATTERN_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($correlation['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_OUTCOME_CORRELATION_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->get()
            ->keyBy('id');

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->where('review_status', 'COMPLETED')
            ->orderBy('id')
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'INSUFFICIENT_DECISION_DATA',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,
                'consistency_summary' => [
                    'total_decisions_analyzed' => 0,
                    'consistency_score' => 0.0,
                    'consistency_status' => 'INSUFFICIENT_DATA',
                    'consistency_confidence' => 'INSUFFICIENT_DATA',
                ],
                'consistency_guardrails' => $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['decision_outcome_alignment'] = [
            'passed' =>
                (float) (
                    $correlation['correlation_summary']['consistency_percentage']
                    ?? 0
                ) === 100.0,

            'value' =>
                $correlation['correlation_summary']['consistency_percentage']
                ?? 0,

            'message' =>
                'Recorded human decisions should remain aligned with current governance action states.',
        ];

        $checks['historical_decision_records_available'] = [
            'passed' =>
                $reviews->count() > 0,

            'value' =>
                $reviews->count(),

            'message' =>
                'Consistency analysis requires dedicated historical human review records.',
        ];

        $checks['no_missing_source_actions'] = [
            'passed' =>
                $reviews->every(
                    fn ($review) =>
                        $actions->has(
                            $review->governance_action_id
                        )
                ),

            'message' =>
                'Every historical governance decision should retain its source governance action.',
        ];

        $checks['decision_values_recognized'] = [
            'passed' =>
                $reviews->every(
                    fn ($review) =>
                        in_array(
                            $review->review_decision,
                            [
                                'APPROVE',
                                'REJECT',
                                'DEFER',
                                'REQUEST_MORE_EVIDENCE',
                            ],
                            true
                        )
                ),

            'message' =>
                'All completed human governance decisions should use recognized decision values.',
        ];

        $checks['priority_values_recognized'] = [
            'passed' =>
                $reviews->every(
                    function ($review) use ($actions) {
                        $action = $actions->get(
                            $review->governance_action_id
                        );

                        return $action
                            &&
                            in_array(
                                $action->priority_level,
                                [
                                    'CRITICAL',
                                    'HIGH',
                                    'MODERATE',
                                    'ADVISORY',
                                ],
                                true
                            );
                    }
                ),

            'message' =>
                'Governance decisions should retain recognized priority classifications.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Category Consistency
        |--------------------------------------------------------------------------
        */

        $categoryGroups = [];

        foreach ($reviews as $review) {
            $action = $actions->get(
                $review->governance_action_id
            );

            if (!$action) {
                continue;
            }

            $category = $action->action_category
                ?? 'UNKNOWN';

            $categoryGroups[$category][] = [
                'decision' => $review->review_decision,
                'priority_level' => $action->priority_level,
                'priority_score' => (int) $action->priority_score,
                'action_status' => $action->action_status,
            ];
        }

        $categoryConsistency = [];

        foreach ($categoryGroups as $category => $items) {
            $decisionCounts = collect($items)
                ->countBy('decision')
                ->toArray();

            $total = count($items);

            $maxCount = !empty($decisionCounts)
                ? max($decisionCounts)
                : 0;

            $dominantPercentage = $total > 0
                ? round(
                    ($maxCount / $total) * 100,
                    2
                )
                : 0.0;

            $categoryConsistency[] = [
                'action_category' =>
                    $category,

                'total_decisions' =>
                    $total,

                'decision_distribution' =>
                    $decisionCounts,

                'dominant_decision_percentage' =>
                    $dominantPercentage,

                'consistency_interpretation' =>
                    $total < 3
                        ? 'INSUFFICIENT_SAMPLE'
                        : (
                            $dominantPercentage >= 75
                                ? 'HIGHLY_CONSISTENT_PATTERN'
                                : (
                                    $dominantPercentage >= 60
                                        ? 'MODERATELY_CONSISTENT_PATTERN'
                                        : 'MIXED_PATTERN'
                                )
                        ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Priority-to-Decision Consistency
        |--------------------------------------------------------------------------
        */

        $priorityDecisionGroups = [];

        foreach ($reviews as $review) {
            $action = $actions->get(
                $review->governance_action_id
            );

            if (!$action) {
                continue;
            }

            $priority = $action->priority_level
                ?? 'UNKNOWN';

            if (!isset($priorityDecisionGroups[$priority])) {
                $priorityDecisionGroups[$priority] = [];
            }

            $priorityDecisionGroups[$priority][] =
                $review->review_decision;
        }

        $priorityConsistency = [];

        foreach ($priorityDecisionGroups as $priority => $decisions) {
            $counts = array_count_values($decisions);

            $total = count($decisions);

            $maxCount = !empty($counts)
                ? max($counts)
                : 0;

            $dominantPercentage = $total > 0
                ? round(
                    ($maxCount / $total) * 100,
                    2
                )
                : 0.0;

            $priorityConsistency[] = [
                'priority_level' =>
                    $priority,

                'total_decisions' =>
                    $total,

                'decision_distribution' =>
                    $counts,

                'dominant_decision_percentage' =>
                    $dominantPercentage,

                'consistency_interpretation' =>
                    $total < 3
                        ? 'INSUFFICIENT_SAMPLE'
                        : (
                            $dominantPercentage >= 75
                                ? 'HIGHLY_CONSISTENT_PATTERN'
                                : (
                                    $dominantPercentage >= 60
                                        ? 'MODERATELY_CONSISTENT_PATTERN'
                                        : 'MIXED_PATTERN'
                                )
                        ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Contradiction Detection
        |--------------------------------------------------------------------------
        */

        $contradictions = [];

        foreach ($reviews as $review) {
            $action = $actions->get(
                $review->governance_action_id
            );

            if (!$action) {
                continue;
            }

            $expectedStatuses = match ($review->review_decision) {
                'APPROVE' => [
                    'APPROVED',
                    'RESOLVED',
                ],

                'REJECT' => [
                    'REJECTED',
                    'CLOSED_REJECTED',
                ],

                'DEFER' => [
                    'DEFERRED',
                ],

                'REQUEST_MORE_EVIDENCE' => [
                    'MORE_EVIDENCE_REQUIRED',
                ],

                default => [],
            };

            if (
                !in_array(
                    $action->action_status,
                    $expectedStatuses,
                    true
                )
            ) {
                $contradictions[] = [
                    'review_id' =>
                        $review->id,

                    'action_id' =>
                        $action->id,

                    'action_code' =>
                        $action->action_code,

                    'review_decision' =>
                        $review->review_decision,

                    'current_action_status' =>
                        $action->action_status,

                    'expected_statuses' =>
                        $expectedStatuses,

                    'contradiction_type' =>
                        'DECISION_STATE_MISMATCH',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Permission Consistency
        |--------------------------------------------------------------------------
        */

        $automaticPermissionsConsistent =
            $actions->every(
                fn ($action) =>
                    !(bool) $action->automatic_execution_allowed
                    &&
                    !(bool) $action->automatic_change_allowed
                    &&
                    !(bool) $action->automatic_deployment_allowed
                    &&
                    !(bool) $action->automatic_rollback_allowed
                    &&
                    !(bool) $action->automatic_clinical_action_allowed
            );

        $checks['automatic_permission_consistency'] = [
            'passed' =>
                $automaticPermissionsConsistent,

            'message' =>
                'Automatic execution, change, deployment, rollback, and clinical action permissions must remain consistently disabled.',
        ];

        $humanControlsConsistent =
            $actions->every(
                fn ($action) =>
                    (bool) $action->human_review_required
                    &&
                    (bool) $action->governance_validation_required
            );

        $checks['human_governance_control_consistency'] = [
            'passed' =>
                $humanControlsConsistent,

            'message' =>
                'Human review and governance validation requirements must remain consistent across governance actions.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Score
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        $baseConsistencyScore =
            $totalChecks > 0
                ? (
                    $passedChecks
                    / $totalChecks
                ) * 100
                : 0.0;

        $contradictionPenalty =
            min(
                40,
                count($contradictions) * 10
            );

        $consistencyScore =
            round(
                max(
                    0,
                    $baseConsistencyScore
                    - $contradictionPenalty
                ),
                2
            );

        $consistencyStatus = match (true) {
            $consistencyScore >= 95 =>
                'HIGHLY_CONSISTENT',

            $consistencyScore >= 80 =>
                'CONSISTENT_WITH_MINOR_VARIATION',

            $consistencyScore >= 60 =>
                'MIXED_CONSISTENCY',

            default =>
                'CONSISTENCY_CONCERN',
        };

        $consistencyConfidence = match (true) {
            $reviews->count() >= 20 =>
                'HIGH',

            $reviews->count() >= 10 =>
                'MODERATE',

            $reviews->count() >= 5 =>
                'LIMITED',

            default =>
                'VERY_LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            $reviews->count()
                . ' completed human governance decision(s) were evaluated for consistency.',

            "{$passedChecks} of {$totalChecks} governance consistency control(s) currently pass.",

            count($contradictions)
                . ' explicit decision-state contradiction(s) are currently detected.',

            "Overall governance decision consistency score is {$consistencyScore}.",

            "Current decision consistency classification is {$consistencyStatus}.",

            'Decision-to-outcome alignment is '
                . (
                    $correlation[
                        'correlation_summary'
                    ]['outcome_alignment']
                    ?? 'UNKNOWN'
                )
                . '.',

            'Current decision-pattern classification is '
                . (
                    $pattern[
                        'pattern_summary'
                    ]['pattern_classification']
                    ?? 'UNKNOWN'
                )
                . '.',

            "Consistency confidence is {$consistencyConfidence}.",
        ];

        if ($reviews->count() < 10) {
            $findings[] =
                'Decision consistency conclusions remain preliminary because fewer than 10 completed governance decisions are available.';
        }

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_CONSISTENCY_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'consistency_summary' => [
                'total_decisions_analyzed' =>
                    $reviews->count(),

                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'contradiction_count' =>
                    count($contradictions),

                'consistency_score' =>
                    $consistencyScore,

                'consistency_status' =>
                    $consistencyStatus,

                'consistency_confidence' =>
                    $consistencyConfidence,
            ],

            'checks' =>
                $checks,

            'category_consistency' =>
                $categoryConsistency,

            'priority_consistency' =>
                $priorityConsistency,

            'decision_state_contradictions' =>
                $contradictions,

            'source_intelligence_context' => [
                'decision_pattern_classification' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_classification']
                    ?? null,

                'decision_pattern_confidence' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_confidence']
                    ?? null,

                'decision_outcome_alignment' =>
                    $correlation[
                        'correlation_summary'
                    ]['outcome_alignment']
                    ?? null,

                'decision_outcome_consistency_percentage' =>
                    $correlation[
                        'correlation_summary'
                    ]['consistency_percentage']
                    ?? null,

                'decision_outcome_confidence' =>
                    $correlation[
                        'correlation_summary'
                    ]['correlation_confidence']
                    ?? null,
            ],

            'consistency_findings' =>
                $findings,

            'consistency_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_consistency_intelligence_enabled' =>
                true,

            'consistency_analysis_is_governance_decision' =>
                false,

            'consistency_score_is_human_performance_rating' =>
                false,

            'consistency_analysis_overrides_human_decision' =>
                false,

            'consistency_analysis_changes_action_state' =>
                false,

            'consistency_analysis_changes_priority' =>
                false,

            'consistency_analysis_changes_eligibility' =>
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
                'Governance decision consistency intelligence evaluates internal consistency of recorded governance decisions and lifecycle states only. It does not judge individual reviewers, override human decisions, change action state, alter priority, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}