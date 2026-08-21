<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionOutcomeCorrelationEngine
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
                'message' => 'Decision outcome correlation requires Step 60.1 governance decision intelligence.',
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
                'status' => 'INSUFFICIENT_DECISION_OUTCOME_DATA',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,
                'correlation_summary' => [
                    'total_decisions_analyzed' => 0,
                    'consistent_outcomes' => 0,
                    'inconsistent_outcomes' => 0,
                    'consistency_percentage' => 0.0,
                    'correlation_confidence' => 'INSUFFICIENT_DATA',
                ],
                'decision_outcome_correlations' => [],
                'correlation_guardrails' => $this->guardrails(),
            ];
        }

        $correlations = [];

        $consistentCount = 0;
        $inconsistentCount = 0;

        $approvedResolved = 0;
        $approvedUnresolved = 0;

        $rejectedClosed = 0;
        $rejectedNotClosed = 0;

        $deferredStillDeferred = 0;
        $deferredNoLongerDeferred = 0;

        $evidenceStillRequired = 0;
        $evidenceRequirementCleared = 0;

        foreach ($reviews as $review) {
            $action = $actions->get(
                $review->governance_action_id
            );

            if (!$action) {
                $correlations[] = [
                    'review_id' => $review->id,
                    'governance_action_id' => $review->governance_action_id,
                    'review_decision' => $review->review_decision,
                    'current_action_status' => null,
                    'expected_outcome_statuses' => [],
                    'outcome_consistent' => false,
                    'correlation_status' => 'SOURCE_ACTION_MISSING',
                ];

                $inconsistentCount++;

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

            $consistent = in_array(
                $action->action_status,
                $expectedStatuses,
                true
            );

            if ($consistent) {
                $consistentCount++;
            } else {
                $inconsistentCount++;
            }

            switch ($review->review_decision) {
                case 'APPROVE':
                    if ($action->action_status === 'RESOLVED') {
                        $approvedResolved++;
                    } else {
                        $approvedUnresolved++;
                    }
                    break;

                case 'REJECT':
                    if (
                        $action->action_status ===
                        'CLOSED_REJECTED'
                    ) {
                        $rejectedClosed++;
                    } else {
                        $rejectedNotClosed++;
                    }
                    break;

                case 'DEFER':
                    if (
                        $action->action_status ===
                        'DEFERRED'
                    ) {
                        $deferredStillDeferred++;
                    } else {
                        $deferredNoLongerDeferred++;
                    }
                    break;

                case 'REQUEST_MORE_EVIDENCE':
                    if (
                        $action->action_status ===
                        'MORE_EVIDENCE_REQUIRED'
                    ) {
                        $evidenceStillRequired++;
                    } else {
                        $evidenceRequirementCleared++;
                    }
                    break;
            }

            $correlations[] = [
                'review_id' =>
                    $review->id,

                'governance_action_id' =>
                    $action->id,

                'action_code' =>
                    $action->action_code,

                'action_category' =>
                    $action->action_category,

                'priority_level' =>
                    $action->priority_level,

                'priority_score' =>
                    (int) $action->priority_score,

                'review_decision' =>
                    $review->review_decision,

                'current_action_status' =>
                    $action->action_status,

                'expected_outcome_statuses' =>
                    $expectedStatuses,

                'outcome_consistent' =>
                    $consistent,

                'currently_closed' =>
                    in_array(
                        $action->action_status,
                        [
                            'RESOLVED',
                            'CLOSED_REJECTED',
                        ],
                        true
                    ),

                'resolved_at' =>
                    $action->resolved_at,

                'correlation_status' =>
                    $consistent
                        ? 'CONSISTENT'
                        : 'INCONSISTENT',
            ];
        }

        $totalDecisions = $reviews->count();

        $consistencyPercentage =
            $totalDecisions > 0
                ? round(
                    (
                        $consistentCount
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        $closureCount = collect($correlations)
            ->where('currently_closed', true)
            ->count();

        $closurePercentage =
            $totalDecisions > 0
                ? round(
                    (
                        $closureCount
                        / $totalDecisions
                    ) * 100,
                    2
                )
                : 0.0;

        $correlationConfidence = match (true) {
            $totalDecisions >= 20 =>
                'HIGH',

            $totalDecisions >= 10 =>
                'MODERATE',

            $totalDecisions >= 5 =>
                'LIMITED',

            default =>
                'VERY_LIMITED',
        };

        $outcomeAlignment = match (true) {
            $consistencyPercentage === 100.0 =>
                'FULLY_ALIGNED',

            $consistencyPercentage >= 80 =>
                'STRONGLY_ALIGNED',

            $consistencyPercentage >= 60 =>
                'PARTIALLY_ALIGNED',

            default =>
                'ALIGNMENT_CONCERN',
        };

        $findings = [
            "{$totalDecisions} completed human governance decision(s) were evaluated against current action outcomes.",

            "{$consistentCount} decision outcome(s) are currently consistent with their human governance decisions.",

            "{$inconsistentCount} decision outcome(s) are currently inconsistent with expected governance states.",

            "Decision-to-outcome consistency is {$consistencyPercentage}%.",

            "Current reviewed-action closure rate is {$closurePercentage}%.",

            "{$approvedResolved} approved decision(s) have reached resolved status.",

            "{$rejectedClosed} rejected decision(s) have reached closed-rejected status.",

            "{$deferredStillDeferred} deferred decision(s) remain deferred.",

            "{$evidenceStillRequired} additional-evidence decision(s) remain evidence-dependent.",

            "Current decision-outcome alignment classification is {$outcomeAlignment}.",
        ];

        if ($totalDecisions < 10) {
            $findings[] =
                'Decision-outcome correlation confidence remains preliminary because fewer than 10 completed human decisions are available.';
        }

        $exceptions = collect($correlations)
            ->filter(
                fn ($item) =>
                    !($item['outcome_consistent'] ?? false)
            )
            ->values()
            ->toArray();

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_OUTCOME_CORRELATION_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'correlation_summary' => [
                'total_decisions_analyzed' =>
                    $totalDecisions,

                'consistent_outcomes' =>
                    $consistentCount,

                'inconsistent_outcomes' =>
                    $inconsistentCount,

                'consistency_percentage' =>
                    $consistencyPercentage,

                'closed_reviewed_actions' =>
                    $closureCount,

                'closure_percentage' =>
                    $closurePercentage,

                'outcome_alignment' =>
                    $outcomeAlignment,

                'correlation_confidence' =>
                    $correlationConfidence,
            ],

            'decision_resolution_correlation' => [
                'approve' => [
                    'resolved' =>
                        $approvedResolved,

                    'not_resolved' =>
                        $approvedUnresolved,
                ],

                'reject' => [
                    'closed_rejected' =>
                        $rejectedClosed,

                    'not_closed_rejected' =>
                        $rejectedNotClosed,
                ],

                'defer' => [
                    'still_deferred' =>
                        $deferredStillDeferred,

                    'no_longer_deferred' =>
                        $deferredNoLongerDeferred,
                ],

                'request_more_evidence' => [
                    'still_evidence_required' =>
                        $evidenceStillRequired,

                    'evidence_requirement_cleared' =>
                        $evidenceRequirementCleared,
                ],
            ],

            'decision_outcome_correlations' =>
                $correlations,

            'correlation_exceptions' =>
                $exceptions,

            'correlation_findings' =>
                $findings,

            'correlation_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_outcome_correlation_enabled' =>
                true,

            'correlation_is_governance_decision' =>
                false,

            'correlation_is_resolution' =>
                false,

            'correlation_changes_action_state' =>
                false,

            'correlation_changes_priority' =>
                false,

            'correlation_changes_eligibility' =>
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
                'Decision outcome correlation compares recorded human governance decisions with current action lifecycle outcomes only. It does not approve, reject, resolve, reopen, reprioritize, execute, deploy, rollback, modify AI behavior, or initiate clinical action.',
        ];
    }
}
