<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionTrendIntelligenceEngine
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

        $pattern = app(
            AIGovernanceDecisionPatternAnalysisEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIGovernanceDecisionRiskIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($foundation['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($pattern['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_PATTERN_INTELLIGENCE_UNAVAILABLE',
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
                'status' => 'INSUFFICIENT_DECISION_TREND_DATA',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,
                'trend_summary' => [
                    'total_decisions_analyzed' => 0,
                    'trend_direction' => 'INSUFFICIENT_DATA',
                    'trend_confidence' => 'INSUFFICIENT_DATA',
                ],
                'decision_timeline' => [],
                'trend_guardrails' => $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Timeline
        |--------------------------------------------------------------------------
        */

        $timeline = [];

        foreach ($reviews as $index => $review) {
            $action = $actions->get(
                $review->governance_action_id
            );

            $decisionClass = $this->decisionClass(
                $review->review_decision
            );

            $timeline[] = [
                'sequence' =>
                    $index + 1,

                'review_id' =>
                    $review->id,

                'governance_action_id' =>
                    $review->governance_action_id,

                'action_code' =>
                    $action?->action_code,

                'action_category' =>
                    $action?->action_category,

                'priority_level' =>
                    $action?->priority_level,

                'priority_score' =>
                    $action
                        ? (int) $action->priority_score
                        : null,

                'review_decision' =>
                    $review->review_decision,

                'decision_class' =>
                    $decisionClass,

                'reviewed_at' =>
                    $review->reviewed_at,

                'current_action_status' =>
                    $action?->action_status,

                'currently_closed' =>
                    $action !== null
                    &&
                    in_array(
                        $action->action_status,
                        [
                            'RESOLVED',
                            'CLOSED_REJECTED',
                        ],
                        true
                    ),
            ];
        }

        $totalDecisions = count($timeline);

        /*
        |--------------------------------------------------------------------------
        | Early vs Late Window
        |--------------------------------------------------------------------------
        */

        if ($totalDecisions === 1) {
            $earlyTimeline = collect($timeline);
            $lateTimeline = collect($timeline);
        } else {
            $splitPoint = (int) ceil(
                $totalDecisions / 2
            );

            $earlyTimeline = collect(
                array_slice(
                    $timeline,
                    0,
                    $splitPoint
                )
            );

            $lateTimeline = collect(
                array_slice(
                    $timeline,
                    $splitPoint
                )
            );

            if ($lateTimeline->isEmpty()) {
                $lateTimeline = collect(
                    [$timeline[$totalDecisions - 1]]
                );
            }
        }

        $earlyMetrics = $this->windowMetrics(
            $earlyTimeline
        );

        $lateMetrics = $this->windowMetrics(
            $lateTimeline
        );

        /*
        |--------------------------------------------------------------------------
        | Trend Changes
        |--------------------------------------------------------------------------
        */

        $cautionChange = round(
            $lateMetrics['cautious_percentage']
            -
            $earlyMetrics['cautious_percentage'],
            2
        );

        $progressionChange = round(
            $lateMetrics['progression_percentage']
            -
            $earlyMetrics['progression_percentage'],
            2
        );

        $restrictiveChange = round(
            $lateMetrics['restrictive_percentage']
            -
            $earlyMetrics['restrictive_percentage'],
            2
        );

        $closureChange = round(
            $lateMetrics['closure_percentage']
            -
            $earlyMetrics['closure_percentage'],
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Trend Direction
        |--------------------------------------------------------------------------
        */

        $trendDirection = match (true) {
            $totalDecisions < 4 =>
                'INSUFFICIENT_FOR_DIRECTION',

            $cautionChange >= 25 =>
                'BECOMING_MORE_CAUTIOUS',

            $cautionChange <= -25
                && $progressionChange >= 25 =>
                'BECOMING_MORE_PROGRESSIVE',

            $restrictiveChange >= 25 =>
                'BECOMING_MORE_RESTRICTIVE',

            abs($cautionChange) < 25
                &&
                abs($progressionChange) < 25
                &&
                abs($restrictiveChange) < 25 =>
                'STABLE_MIXED_TREND',

            default =>
                'MIXED_EVOLVING_TREND',
        };

        /*
        |--------------------------------------------------------------------------
        | Sequential Pattern
        |--------------------------------------------------------------------------
        */

        $decisionSequence = collect($timeline)
            ->pluck('review_decision')
            ->values()
            ->toArray();

        $classSequence = collect($timeline)
            ->pluck('decision_class')
            ->values()
            ->toArray();

        $consecutiveCautious = $this->maxConsecutive(
            $classSequence,
            'CAUTIOUS'
        );

        $consecutiveProgression = $this->maxConsecutive(
            $classSequence,
            'PROGRESSION'
        );

        $consecutiveRestrictive = $this->maxConsecutive(
            $classSequence,
            'RESTRICTIVE'
        );

        /*
        |--------------------------------------------------------------------------
        | Resolution Timing Trend
        |--------------------------------------------------------------------------
        */

        $closedTimeline = collect($timeline)
            ->where('currently_closed', true);

        $unclosedTimeline = collect($timeline)
            ->where('currently_closed', false);

        $reviewedClosureRate =
            $totalDecisions > 0
                ? round(
                    (
                        $closedTimeline->count()
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Trend Confidence
        |--------------------------------------------------------------------------
        */

        $trendConfidence = match (true) {
            $totalDecisions >= 30 =>
                'HIGH',

            $totalDecisions >= 20 =>
                'MODERATE',

            $totalDecisions >= 10 =>
                'LIMITED',

            $totalDecisions >= 5 =>
                'VERY_LIMITED',

            default =>
                'EXTREMELY_LIMITED',
        };

        $trendMaturity = match (true) {
            $totalDecisions >= 30 =>
                'ESTABLISHED',

            $totalDecisions >= 20 =>
                'DEVELOPING',

            $totalDecisions >= 10 =>
                'EARLY',

            default =>
                'VERY_EARLY',
        };

        /*
        |--------------------------------------------------------------------------
        | Trend Signals
        |--------------------------------------------------------------------------
        */

        $signals = [
            'increasing_caution' => [
                'detected' =>
                    $cautionChange >= 25,

                'value' =>
                    $cautionChange,

                'message' =>
                    'Later governance decisions contain a higher proportion of defer or additional-evidence outcomes.',
            ],

            'increasing_progression' => [
                'detected' =>
                    $progressionChange >= 25,

                'value' =>
                    $progressionChange,

                'message' =>
                    'Later governance decisions contain a higher proportion of approval outcomes.',
            ],

            'increasing_restriction' => [
                'detected' =>
                    $restrictiveChange >= 25,

                'value' =>
                    $restrictiveChange,

                'message' =>
                    'Later governance decisions contain a higher proportion of rejection outcomes.',
            ],

            'improving_closure' => [
                'detected' =>
                    $closureChange >= 25,

                'value' =>
                    $closureChange,

                'message' =>
                    'Later reviewed governance actions show a higher closure proportion.',
            ],

            'declining_closure' => [
                'detected' =>
                    $closureChange <= -25,

                'value' =>
                    $closureChange,

                'message' =>
                    'Later reviewed governance actions show a lower closure proportion.',
            ],

            'cautious_sequence' => [
                'detected' =>
                    $consecutiveCautious >= 3,

                'value' =>
                    $consecutiveCautious,

                'message' =>
                    'Multiple consecutive cautious human governance decisions are present.',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "{$totalDecisions} completed human governance decision(s) are represented in temporal trend intelligence.",

            "Current governance decision trend direction is {$trendDirection}.",

            "Early-period cautious decision percentage is {$earlyMetrics['cautious_percentage']}%, compared with {$lateMetrics['cautious_percentage']}% in the later period.",

            "Early-period progression percentage is {$earlyMetrics['progression_percentage']}%, compared with {$lateMetrics['progression_percentage']}% in the later period.",

            "Early-period restrictive percentage is {$earlyMetrics['restrictive_percentage']}%, compared with {$lateMetrics['restrictive_percentage']}% in the later period.",

            "Current reviewed-action closure rate is {$reviewedClosureRate}%.",

            "Maximum consecutive cautious decision sequence is {$consecutiveCautious}.",

            "Trend evidence maturity is {$trendMaturity} with {$trendConfidence} confidence.",
        ];

        if ($totalDecisions < 10) {
            $findings[] =
                'Governance decision trend conclusions remain highly preliminary because fewer than 10 completed decisions are available.';
        }

        if ($totalDecisions < 5) {
            $findings[] =
                'Current decision history is too small for reliable longitudinal governance trend inference.';
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
                'GOVERNANCE_DECISION_TREND_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'trend_summary' => [
                'total_decisions_analyzed' =>
                    $totalDecisions,

                'trend_direction' =>
                    $trendDirection,

                'trend_maturity' =>
                    $trendMaturity,

                'trend_confidence' =>
                    $trendConfidence,

                'reviewed_action_closure_percentage' =>
                    $reviewedClosureRate,

                'caution_change_percentage_points' =>
                    $cautionChange,

                'progression_change_percentage_points' =>
                    $progressionChange,

                'restrictive_change_percentage_points' =>
                    $restrictiveChange,

                'closure_change_percentage_points' =>
                    $closureChange,
            ],

            'decision_timeline' =>
                $timeline,

            'decision_sequence' => [
                'decisions' =>
                    $decisionSequence,

                'decision_classes' =>
                    $classSequence,

                'maximum_consecutive_cautious' =>
                    $consecutiveCautious,

                'maximum_consecutive_progression' =>
                    $consecutiveProgression,

                'maximum_consecutive_restrictive' =>
                    $consecutiveRestrictive,
            ],

            'early_period' =>
                $earlyMetrics,

            'late_period' =>
                $lateMetrics,

            'trend_signals' =>
                $signals,

            'source_intelligence_context' => [
                'pattern_classification' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_classification']
                    ?? null,

                'pattern_confidence' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_confidence']
                    ?? null,

                'decision_risk_level' =>
                    $risk[
                        'decision_risk_level'
                    ]
                    ?? null,

                'decision_risk_score' =>
                    $risk[
                        'decision_risk_score'
                    ]
                    ?? null,
            ],

            'trend_findings' =>
                $findings,

            'trend_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function decisionClass(
        string $decision
    ): string {
        return match ($decision) {
            'APPROVE' =>
                'PROGRESSION',

            'REJECT' =>
                'RESTRICTIVE',

            'DEFER',
            'REQUEST_MORE_EVIDENCE' =>
                'CAUTIOUS',

            default =>
                'UNKNOWN',
        };
    }

    private function windowMetrics(
        $items
    ): array {
        $total = $items->count();

        $progression = $items
            ->where(
                'decision_class',
                'PROGRESSION'
            )
            ->count();

        $restrictive = $items
            ->where(
                'decision_class',
                'RESTRICTIVE'
            )
            ->count();

        $cautious = $items
            ->where(
                'decision_class',
                'CAUTIOUS'
            )
            ->count();

        $closed = $items
            ->where(
                'currently_closed',
                true
            )
            ->count();

        return [
            'decision_count' =>
                $total,

            'progression_decisions' =>
                $progression,

            'restrictive_decisions' =>
                $restrictive,

            'cautious_decisions' =>
                $cautious,

            'progression_percentage' =>
                $this->percentage(
                    $progression,
                    $total
                ),

            'restrictive_percentage' =>
                $this->percentage(
                    $restrictive,
                    $total
                ),

            'cautious_percentage' =>
                $this->percentage(
                    $cautious,
                    $total
                ),

            'closed_actions' =>
                $closed,

            'closure_percentage' =>
                $this->percentage(
                    $closed,
                    $total
                ),
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

    private function maxConsecutive(
        array $sequence,
        string $target
    ): int {
        $max = 0;
        $current = 0;

        foreach ($sequence as $value) {
            if ($value === $target) {
                $current++;

                if ($current > $max) {
                    $max = $current;
                }
            } else {
                $current = 0;
            }
        }

        return $max;
    }

    private function guardrails(): array
    {
        return [
            'decision_trend_intelligence_enabled' =>
                true,

            'trend_intelligence_is_governance_decision' =>
                false,

            'trend_intelligence_predicts_required_decision' =>
                false,

            'trend_intelligence_changes_action_state' =>
                false,

            'trend_intelligence_changes_priority' =>
                false,

            'trend_intelligence_changes_eligibility' =>
                false,

            'trend_intelligence_overrides_human_review' =>
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
                'Governance decision trend intelligence describes temporal patterns in recorded human governance decisions only. Trend intelligence does not predict a required human decision, override governance review, change action state, alter priority, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}