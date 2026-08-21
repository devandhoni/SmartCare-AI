<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionPatternAnalysisEngine
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

        $foundation = app(
            AIGovernanceDecisionIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($foundation['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Governance decision pattern analysis requires Step 60.1 decision intelligence.',
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
            ->orderBy('reviewed_at')
            ->orderBy('id')
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'INSUFFICIENT_DECISION_DATA',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,

                'pattern_summary' => [
                    'total_decisions_analyzed' => 0,
                    'decision_categories_observed' => 0,
                    'action_categories_observed' => 0,
                    'pattern_confidence' => 'INSUFFICIENT_DATA',
                ],

                'decision_patterns' => [],
                'category_patterns' => [],

                'pattern_guardrails' => $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Counts
        |--------------------------------------------------------------------------
        */

        $decisionCounts = [
            'APPROVE' => $reviews
                ->where('review_decision', 'APPROVE')
                ->count(),

            'REJECT' => $reviews
                ->where('review_decision', 'REJECT')
                ->count(),

            'DEFER' => $reviews
                ->where('review_decision', 'DEFER')
                ->count(),

            'REQUEST_MORE_EVIDENCE' => $reviews
                ->where('review_decision', 'REQUEST_MORE_EVIDENCE')
                ->count(),
        ];

        $totalDecisions = $reviews->count();

        /*
        |--------------------------------------------------------------------------
        | Dominant Decision
        |--------------------------------------------------------------------------
        */

        $maxDecisionCount = max($decisionCounts);

        $dominantDecisions = collect($decisionCounts)
            ->filter(fn ($count) => $count === $maxDecisionCount)
            ->keys()
            ->values()
            ->toArray();

        $dominantDecision = count($dominantDecisions) === 1
            ? $dominantDecisions[0]
            : 'MIXED';

        /*
        |--------------------------------------------------------------------------
        | Action Category Pattern Analysis
        |--------------------------------------------------------------------------
        */

        $categoryGroups = [];

        foreach ($reviews as $review) {
            $action = $actions->get($review->governance_action_id);

            if (!$action) {
                continue;
            }

            $category = $action->action_category ?? 'UNKNOWN';

            if (!isset($categoryGroups[$category])) {
                $categoryGroups[$category] = [
                    'total_decisions' => 0,
                    'APPROVE' => 0,
                    'REJECT' => 0,
                    'DEFER' => 0,
                    'REQUEST_MORE_EVIDENCE' => 0,
                    'priority_scores' => [],
                    'resolved_count' => 0,
                ];
            }

            $categoryGroups[$category]['total_decisions']++;

            if (
                isset(
                    $categoryGroups[$category][
                        $review->review_decision
                    ]
                )
            ) {
                $categoryGroups[$category][
                    $review->review_decision
                ]++;
            }

            $categoryGroups[$category]['priority_scores'][] =
                (int) $action->priority_score;

            if (
                in_array(
                    $action->action_status,
                    [
                        'RESOLVED',
                        'CLOSED_REJECTED',
                    ],
                    true
                )
            ) {
                $categoryGroups[$category]['resolved_count']++;
            }
        }

        $categoryPatterns = [];

        foreach ($categoryGroups as $category => $data) {
            $averagePriority = count($data['priority_scores']) > 0
                ? round(
                    array_sum($data['priority_scores'])
                    / count($data['priority_scores']),
                    2
                )
                : 0.0;

            $decisionDistribution = [
                'approve' => $data['APPROVE'],
                'reject' => $data['REJECT'],
                'defer' => $data['DEFER'],
                'request_more_evidence' =>
                    $data['REQUEST_MORE_EVIDENCE'],
            ];

            $categoryDecisionCounts = [
                'APPROVE' => $data['APPROVE'],
                'REJECT' => $data['REJECT'],
                'DEFER' => $data['DEFER'],
                'REQUEST_MORE_EVIDENCE' =>
                    $data['REQUEST_MORE_EVIDENCE'],
            ];

            $maxCategoryCount = max($categoryDecisionCounts);

            $categoryDominant = collect(
                $categoryDecisionCounts
            )
                ->filter(
                    fn ($count) =>
                        $count === $maxCategoryCount
                        && $count > 0
                )
                ->keys()
                ->values()
                ->toArray();

            $dominantCategoryDecision =
                count($categoryDominant) === 1
                    ? $categoryDominant[0]
                    : 'MIXED';

            $resolutionRate =
                $data['total_decisions'] > 0
                    ? round(
                        (
                            $data['resolved_count']
                            / $data['total_decisions']
                        ) * 100,
                        2
                    )
                    : 0.0;

            $categoryPatterns[] = [
                'action_category' =>
                    $category,

                'total_decisions' =>
                    $data['total_decisions'],

                'dominant_decision' =>
                    $dominantCategoryDecision,

                'decision_distribution' =>
                    $decisionDistribution,

                'average_priority_score' =>
                    $averagePriority,

                'resolved_decisions' =>
                    $data['resolved_count'],

                'resolution_rate_percentage' =>
                    $resolutionRate,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Priority Pattern Analysis
        |--------------------------------------------------------------------------
        */

        $priorityLevels = [
            'CRITICAL',
            'HIGH',
            'MODERATE',
            'ADVISORY',
        ];

        $priorityPatterns = [];

        foreach ($priorityLevels as $priorityLevel) {
            $priorityReviews = $reviews->filter(
                function ($review) use (
                    $actions,
                    $priorityLevel
                ) {
                    $action = $actions->get(
                        $review->governance_action_id
                    );

                    return $action
                        && $action->priority_level ===
                            $priorityLevel;
                }
            );

            if ($priorityReviews->isEmpty()) {
                continue;
            }

            $priorityPatterns[] = [
                'priority_level' =>
                    $priorityLevel,

                'total_decisions' =>
                    $priorityReviews->count(),

                'approved' =>
                    $priorityReviews
                        ->where(
                            'review_decision',
                            'APPROVE'
                        )
                        ->count(),

                'rejected' =>
                    $priorityReviews
                        ->where(
                            'review_decision',
                            'REJECT'
                        )
                        ->count(),

                'deferred' =>
                    $priorityReviews
                        ->where(
                            'review_decision',
                            'DEFER'
                        )
                        ->count(),

                'more_evidence_required' =>
                    $priorityReviews
                        ->where(
                            'review_decision',
                            'REQUEST_MORE_EVIDENCE'
                        )
                        ->count(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Resolution Patterns
        |--------------------------------------------------------------------------
        */

        $resolvedDecisionCount = $reviews
            ->filter(function ($review) use ($actions) {
                $action = $actions->get(
                    $review->governance_action_id
                );

                return $action
                    && in_array(
                        $action->action_status,
                        [
                            'RESOLVED',
                            'CLOSED_REJECTED',
                        ],
                        true
                    );
            })
            ->count();

        $unresolvedDecisionCount =
            $totalDecisions - $resolvedDecisionCount;

        $decisionResolutionRate =
            $totalDecisions > 0
                ? round(
                    (
                        $resolvedDecisionCount
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Evidence-Seeking Pattern
        |--------------------------------------------------------------------------
        */

        $evidenceSeekingPercentage =
            $totalDecisions > 0
                ? round(
                    (
                        $decisionCounts[
                            'REQUEST_MORE_EVIDENCE'
                        ]
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        $cautiousDecisions =
            $decisionCounts['DEFER']
            +
            $decisionCounts[
                'REQUEST_MORE_EVIDENCE'
            ];

        $cautiousDecisionPercentage =
            $totalDecisions > 0
                ? round(
                    (
                        $cautiousDecisions
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Pattern Confidence
        |--------------------------------------------------------------------------
        */

        $patternConfidence = match (true) {
            $totalDecisions >= 20 =>
                'HIGH',

            $totalDecisions >= 10 =>
                'MODERATE',

            $totalDecisions >= 5 =>
                'LIMITED',

            default =>
                'VERY_LIMITED',
        };

        $evidenceMaturity = match (true) {
            $totalDecisions >= 20 =>
                'ESTABLISHED',

            $totalDecisions >= 10 =>
                'DEVELOPING',

            $totalDecisions >= 5 =>
                'EARLY',

            default =>
                'VERY_EARLY',
        };

        /*
        |--------------------------------------------------------------------------
        | Pattern Classification
        |--------------------------------------------------------------------------
        */

        $patternClassification = match (true) {
            $cautiousDecisionPercentage >= 60 =>
                'CAUTIOUS_GOVERNANCE_PATTERN',

            $decisionCounts['APPROVE'] >
                (
                    $decisionCounts['REJECT']
                    +
                    $decisionCounts['DEFER']
                    +
                    $decisionCounts[
                        'REQUEST_MORE_EVIDENCE'
                    ]
                ) =>
                'APPROVAL_LEANING_PATTERN',

            $decisionCounts['REJECT'] >
                (
                    $decisionCounts['APPROVE']
                    +
                    $decisionCounts['DEFER']
                    +
                    $decisionCounts[
                        'REQUEST_MORE_EVIDENCE'
                    ]
                ) =>
                'REJECTION_LEANING_PATTERN',

            default =>
                'BALANCED_MIXED_PATTERN',
        };

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "{$totalDecisions} completed human governance decision(s) are included in pattern analysis.",

            "Current dominant decision classification is {$dominantDecision}.",

            "Current decision pattern classification is {$patternClassification}.",

            "{$cautiousDecisionPercentage}% of recorded decisions currently represent defer or additional-evidence pathways.",

            "{$evidenceSeekingPercentage}% of decisions currently request additional evidence.",

            "Current human-decision resolution rate is {$decisionResolutionRate}%.",

            count($categoryPatterns)
                . ' action category pattern(s) are represented in the current governance decision sample.',

            "Decision-pattern evidence maturity is {$evidenceMaturity} with {$patternConfidence} confidence.",
        ];

        if ($totalDecisions < 10) {
            $findings[] =
                'Governance decision pattern conclusions remain preliminary because fewer than 10 completed decisions are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_PATTERN_ANALYSIS_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'pattern_summary' => [
                'total_decisions_analyzed' =>
                    $totalDecisions,

                'dominant_decision' =>
                    $dominantDecision,

                'pattern_classification' =>
                    $patternClassification,

                'decision_categories_observed' =>
                    collect($decisionCounts)
                        ->filter(
                            fn ($count) =>
                                $count > 0
                        )
                        ->count(),

                'action_categories_observed' =>
                    count($categoryPatterns),

                'resolved_decisions' =>
                    $resolvedDecisionCount,

                'unresolved_decisions' =>
                    $unresolvedDecisionCount,

                'decision_resolution_rate_percentage' =>
                    $decisionResolutionRate,

                'evidence_seeking_percentage' =>
                    $evidenceSeekingPercentage,

                'cautious_decision_percentage' =>
                    $cautiousDecisionPercentage,

                'evidence_maturity' =>
                    $evidenceMaturity,

                'pattern_confidence' =>
                    $patternConfidence,
            ],

            'decision_patterns' => [
                'approve' => [
                    'count' =>
                        $decisionCounts['APPROVE'],

                    'percentage' =>
                        $this->percentage(
                            $decisionCounts['APPROVE'],
                            $totalDecisions
                        ),
                ],

                'reject' => [
                    'count' =>
                        $decisionCounts['REJECT'],

                    'percentage' =>
                        $this->percentage(
                            $decisionCounts['REJECT'],
                            $totalDecisions
                        ),
                ],

                'defer' => [
                    'count' =>
                        $decisionCounts['DEFER'],

                    'percentage' =>
                        $this->percentage(
                            $decisionCounts['DEFER'],
                            $totalDecisions
                        ),
                ],

                'request_more_evidence' => [
                    'count' =>
                        $decisionCounts[
                            'REQUEST_MORE_EVIDENCE'
                        ],

                    'percentage' =>
                        $this->percentage(
                            $decisionCounts[
                                'REQUEST_MORE_EVIDENCE'
                            ],
                            $totalDecisions
                        ),
                ],
            ],

            'category_patterns' =>
                $categoryPatterns,

            'priority_patterns' =>
                $priorityPatterns,

            'resolution_pattern' => [
                'resolved_decisions' =>
                    $resolvedDecisionCount,

                'unresolved_decisions' =>
                    $unresolvedDecisionCount,

                'resolution_rate_percentage' =>
                    $decisionResolutionRate,
            ],

            'governance_behavior_pattern' => [
                'pattern_classification' =>
                    $patternClassification,

                'dominant_decision' =>
                    $dominantDecision,

                'cautious_decisions' =>
                    $cautiousDecisions,

                'cautious_decision_percentage' =>
                    $cautiousDecisionPercentage,

                'evidence_seeking_percentage' =>
                    $evidenceSeekingPercentage,

                'interpretation' =>
                    $this->interpretPattern(
                        $patternClassification
                    ),
            ],

            'pattern_findings' =>
                $findings,

            'pattern_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function percentage(
        int $count,
        int $total
    ): float {
        return $total > 0
            ? round(
                ($count / $total) * 100,
                2
            )
            : 0.0;
    }

    private function interpretPattern(
        string $classification
    ): string {
        return match ($classification) {
            'CAUTIOUS_GOVERNANCE_PATTERN' =>
                'Human governance decisions currently favor deferment or additional evidence before progressing governance work.',

            'APPROVAL_LEANING_PATTERN' =>
                'Human governance decisions currently show a greater proportion of approvals than restrictive decision pathways.',

            'REJECTION_LEANING_PATTERN' =>
                'Human governance decisions currently show a greater proportion of rejection outcomes than progression pathways.',

            default =>
                'Human governance decisions currently show a mixed distribution without a dominant progression or restriction pattern.',
        };
    }

    private function guardrails(): array
    {
        return [
            'decision_pattern_analysis_enabled' =>
                true,

            'pattern_analysis_is_governance_decision' =>
                false,

            'pattern_analysis_is_approval' =>
                false,

            'pattern_analysis_is_rejection' =>
                false,

            'pattern_analysis_is_resolution' =>
                false,

            'pattern_analysis_changes_priority' =>
                false,

            'pattern_analysis_changes_eligibility' =>
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
                'Governance decision pattern analysis identifies patterns in recorded human decisions only. Patterns do not approve, reject, defer, resolve, reprioritize, modify, execute, deploy, rollback, or initiate clinical action.',
        ];
    }
}