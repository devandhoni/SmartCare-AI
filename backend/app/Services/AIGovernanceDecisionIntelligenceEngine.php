<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionIntelligenceEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resolve Lifecycle Snapshot
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Load Governance Actions
        |--------------------------------------------------------------------------
        */

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Load Human Governance Decision History
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | AIGovernanceActionReview is the authoritative source for historical
        | human governance decisions.
        |
        | AIGovernanceAction.review_decision represents the current lifecycle
        | state only and must not be treated as the historical decision ledger.
        |--------------------------------------------------------------------------
        */

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->where('review_status', 'COMPLETED')
            ->orderBy('id')
            ->get();

        if ($actions->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'NO_GOVERNANCE_DECISION_DATA',
                'decision_intelligence_mode' =>
                    'HUMAN_GOVERNANCE_DECISION_ANALYSIS',

                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,

                'decision_summary' => [
                    'total_actions' => 0,
                    'total_decisions' => 0,
                    'approved_decisions' => 0,
                    'rejected_decisions' => 0,
                    'deferred_decisions' => 0,
                    'more_evidence_decisions' => 0,
                    'pending_decisions' => 0,
                    'decision_completion_percentage' => 0.0,
                ],

                'decision_guardrails' =>
                    $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Decision Distribution
        |--------------------------------------------------------------------------
        */

        $approvedDecisions = $reviews
            ->where('review_decision', 'APPROVE')
            ->count();

        $rejectedDecisions = $reviews
            ->where('review_decision', 'REJECT')
            ->count();

        $deferredDecisions = $reviews
            ->where('review_decision', 'DEFER')
            ->count();

        $moreEvidenceDecisions = $reviews
            ->where('review_decision', 'REQUEST_MORE_EVIDENCE')
            ->count();

        $totalDecisions = $reviews->count();

        /*
        |--------------------------------------------------------------------------
        | Current Governance Lifecycle State
        |--------------------------------------------------------------------------
        */

        $pendingDecisions = $actions
            ->where('action_status', 'PENDING_REVIEW')
            ->count();

        $resolvedActions = $actions
            ->where('action_status', 'RESOLVED')
            ->count();

        $closedRejectedActions = $actions
            ->where('action_status', 'CLOSED_REJECTED')
            ->count();

        $deferredActions = $actions
            ->where('action_status', 'DEFERRED')
            ->count();

        $evidenceDependentActions = $actions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->count();

        $approvedUnresolvedActions = $actions
            ->where('action_status', 'APPROVED')
            ->count();

        $openActions = $actions
            ->where('action_status', 'OPEN')
            ->count();

        $closedActions =
            $resolvedActions + $closedRejectedActions;

        $activeActions =
            $actions->count() - $closedActions;

        /*
        |--------------------------------------------------------------------------
        | Decision Completion
        |--------------------------------------------------------------------------
        */

        $decisionCompletionPercentage = $actions->count() > 0
            ? round(
                ($totalDecisions / $actions->count()) * 100,
                2
            )
            : 0.0;

        $closurePercentage = $actions->count() > 0
            ? round(
                ($closedActions / $actions->count()) * 100,
                2
            )
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Decision Distribution Percentages
        |--------------------------------------------------------------------------
        */

        $decisionPercentage = function (int $count) use ($totalDecisions): float {
            return $totalDecisions > 0
                ? round(($count / $totalDecisions) * 100, 2)
                : 0.0;
        };

        /*
        |--------------------------------------------------------------------------
        | Highest Unresolved Governance Priority
        |--------------------------------------------------------------------------
        */

        $highestUnresolvedAction = $actions
            ->filter(
                fn ($action) =>
                    !in_array(
                        $action->action_status,
                        [
                            'RESOLVED',
                            'CLOSED_REJECTED',
                        ],
                        true
                    )
            )
            ->sortByDesc('priority_score')
            ->first();

        $highestUnresolvedPriority = $highestUnresolvedAction
            ? [
                'action_id' =>
                    $highestUnresolvedAction->id,

                'action_code' =>
                    $highestUnresolvedAction->action_code,

                'action_category' =>
                    $highestUnresolvedAction->action_category,

                'action_status' =>
                    $highestUnresolvedAction->action_status,

                'eligibility_status' =>
                    $highestUnresolvedAction->eligibility_status,

                'priority_level' =>
                    $highestUnresolvedAction->priority_level,

                'priority_score' =>
                    (int) $highestUnresolvedAction->priority_score,
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Decision History
        |--------------------------------------------------------------------------
        */

        $decisionHistory = $reviews
            ->map(function ($review) use ($actions) {
                $action = $actions->firstWhere(
                    'id',
                    $review->governance_action_id
                );

                return [
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

                    'review_status' =>
                        $review->review_status,

                    'decision_rationale' =>
                        $review->decision_rationale,

                    'reviewer_id' =>
                        $review->reviewer_id,

                    'reviewer_name' =>
                        $review->reviewer_name,

                    'reviewer_role' =>
                        $review->reviewer_role,

                    'reviewed_at' =>
                        $review->reviewed_at,

                    'current_action_status' =>
                        $action?->action_status,

                    'currently_resolved' =>
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
            })
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Governance Decision State
        |--------------------------------------------------------------------------
        */

        $decisionState = match (true) {
            $pendingDecisions > 0 =>
                'HUMAN_DECISIONS_PENDING',

            $evidenceDependentActions > 0 =>
                'ADDITIONAL_EVIDENCE_REQUIRED',

            $deferredActions > 0 =>
                'DEFERRED_DECISIONS_REMAIN',

            $activeActions > 0 =>
                'ACTIVE_GOVERNANCE_DECISIONS_REMAIN',

            default =>
                'GOVERNANCE_DECISION_CYCLE_COMPLETE',
        };

        /*
        |--------------------------------------------------------------------------
        | Decision Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            $actions->count()
                . ' governance action(s) are represented in decision intelligence.',

            $totalDecisions
                . ' completed human governance decision(s) are recorded.',

            $approvedDecisions
                . ' human approval decision(s) are recorded.',

            $rejectedDecisions
                . ' human rejection decision(s) are recorded.',

            $deferredDecisions
                . ' human defer decision(s) are recorded.',

            $moreEvidenceDecisions
                . ' decision(s) requested additional evidence.',

            $pendingDecisions
                . ' governance action(s) remain pending human decision.',

            "Human governance decision completion is {$decisionCompletionPercentage}%.",

            "Current governance action closure is {$closurePercentage}%.",

            'Governance decision history is sourced from the dedicated human review registry rather than inferred from current action state.',
        ];

        if ($highestUnresolvedPriority !== null) {
            $findings[] =
                'Highest unresolved governance priority is '
                . $highestUnresolvedPriority['action_code']
                . ' with priority score '
                . $highestUnresolvedPriority['priority_score']
                . '.';
        }

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $humanGovernanceIntegrity =
            $actions->every(
                fn ($action) =>
                    (bool) $action->human_review_required
                    &&
                    (bool) $action->governance_validation_required
            );

        $automaticPermissionIsolation =
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

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_INTELLIGENCE_AVAILABLE',

            'decision_intelligence_mode' =>
                'HUMAN_GOVERNANCE_DECISION_ANALYSIS',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'decision_state' =>
                $decisionState,

            'decision_summary' => [
                'total_actions' =>
                    $actions->count(),

                'total_decisions' =>
                    $totalDecisions,

                'approved_decisions' =>
                    $approvedDecisions,

                'rejected_decisions' =>
                    $rejectedDecisions,

                'deferred_decisions' =>
                    $deferredDecisions,

                'more_evidence_decisions' =>
                    $moreEvidenceDecisions,

                'pending_decisions' =>
                    $pendingDecisions,

                'decision_completion_percentage' =>
                    $decisionCompletionPercentage,
            ],

            'decision_distribution' => [
                'approve' => [
                    'count' =>
                        $approvedDecisions,

                    'percentage' =>
                        $decisionPercentage(
                            $approvedDecisions
                        ),
                ],

                'reject' => [
                    'count' =>
                        $rejectedDecisions,

                    'percentage' =>
                        $decisionPercentage(
                            $rejectedDecisions
                        ),
                ],

                'defer' => [
                    'count' =>
                        $deferredDecisions,

                    'percentage' =>
                        $decisionPercentage(
                            $deferredDecisions
                        ),
                ],

                'request_more_evidence' => [
                    'count' =>
                        $moreEvidenceDecisions,

                    'percentage' =>
                        $decisionPercentage(
                            $moreEvidenceDecisions
                        ),
                ],
            ],

            'resolution_summary' => [
                'resolved_actions' =>
                    $resolvedActions,

                'closed_rejected_actions' =>
                    $closedRejectedActions,

                'deferred_actions' =>
                    $deferredActions,

                'evidence_dependent_actions' =>
                    $evidenceDependentActions,

                'approved_unresolved_actions' =>
                    $approvedUnresolvedActions,

                'open_actions' =>
                    $openActions,

                'active_actions' =>
                    $activeActions,

                'closed_actions' =>
                    $closedActions,

                'closure_percentage' =>
                    $closurePercentage,
            ],

            'highest_unresolved_priority' =>
                $highestUnresolvedPriority,

            'decision_history' =>
                $decisionHistory,

            'governance_integrity' => [
                'human_governance_controls_intact' =>
                    $humanGovernanceIntegrity,

                'automatic_permission_isolation_intact' =>
                    $automaticPermissionIsolation,

                'historical_decision_source' =>
                    'AIGovernanceActionReview',
            ],

            'decision_findings' =>
                $findings,

            'decision_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_intelligence_enabled' =>
                true,

            'decision_intelligence_is_human_decision' =>
                false,

            'decision_intelligence_is_approval' =>
                false,

            'decision_intelligence_is_rejection' =>
                false,

            'decision_intelligence_is_resolution' =>
                false,

            'decision_intelligence_is_execution' =>
                false,

            'decision_pattern_analysis_authorizes_action' =>
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

            'automatic_model_change' =>
                false,

            'automatic_threshold_change' =>
                false,

            'automatic_confidence_change' =>
                false,

            'automatic_recommendation_change' =>
                false,

            'automatic_workflow_change' =>
                false,

            'automatic_clinical_rule_change' =>
                false,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'message' =>
                'Governance decision intelligence analyzes recorded human governance decisions and current resolution state only. It does not make governance decisions, approve or reject actions, resolve work items, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}