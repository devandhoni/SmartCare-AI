<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceActionWorkloadQueueIntelligenceEngine
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

        $closure = app(
            AIGovernanceActionClosureIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($closure['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CLOSURE_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Governance workload intelligence requires action closure intelligence.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        if ($actions->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'NO_GOVERNANCE_WORKLOAD_AVAILABLE',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,
                'workload_status' => 'NO_ACTIVE_WORKLOAD',
                'workload_summary' => [
                    'total_actions' => 0,
                    'active_actions' => 0,
                    'closed_actions' => 0,
                    'pending_human_review' => 0,
                    'evidence_waiting_actions' => 0,
                    'deferred_actions' => 0,
                ],
                'queues' => [],
                'workload_guardrails' => $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Active / Closed State
        |--------------------------------------------------------------------------
        */

        $closedStatuses = [
            'RESOLVED',
            'CLOSED_REJECTED',
        ];

        $activeActions = $actions->filter(
            fn ($action) =>
                !in_array(
                    $action->action_status,
                    $closedStatuses,
                    true
                )
        );

        $closedActions = $actions->filter(
            fn ($action) =>
                in_array(
                    $action->action_status,
                    $closedStatuses,
                    true
                )
        );

        /*
        |--------------------------------------------------------------------------
        | Workload Queues
        |--------------------------------------------------------------------------
        */

        $humanReviewQueue = $activeActions
            ->where('action_status', 'PENDING_REVIEW')
            ->values();

        $evidenceQueue = $activeActions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->values();

        $deferredQueue = $activeActions
            ->where('action_status', 'DEFERRED')
            ->values();

        $approvedQueue = $activeActions
            ->where('action_status', 'APPROVED')
            ->values();

        $openQueue = $activeActions
            ->where('action_status', 'OPEN')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Priority Distribution
        |--------------------------------------------------------------------------
        */

        $criticalActive = $activeActions
            ->where('priority_level', 'CRITICAL')
            ->count();

        $highActive = $activeActions
            ->where('priority_level', 'HIGH')
            ->count();

        $moderateActive = $activeActions
            ->where('priority_level', 'MODERATE')
            ->count();

        $advisoryActive = $activeActions
            ->where('priority_level', 'ADVISORY')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Weighted Workload Score
        |--------------------------------------------------------------------------
        */

        $weightedScore = $activeActions->sum(function ($action) {
            $statusWeight = match ($action->action_status) {
                'PENDING_REVIEW' => 1.00,
                'MORE_EVIDENCE_REQUIRED' => 0.90,
                'APPROVED' => 0.80,
                'DEFERRED' => 0.50,
                'OPEN' => 0.70,
                default => 0.40,
            };

            return ((int) $action->priority_score) * $statusWeight;
        });

        $averageWeightedPriority = $activeActions->count() > 0
            ? round($weightedScore / $activeActions->count(), 2)
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Workload Classification
        |--------------------------------------------------------------------------
        */

        $workloadStatus = match (true) {
            $criticalActive > 0 =>
                'CRITICAL_ATTENTION_REQUIRED',

            $highActive >= 3 =>
                'HIGH_GOVERNANCE_WORKLOAD',

            $humanReviewQueue->count() >= 5 =>
                'HIGH_GOVERNANCE_WORKLOAD',

            $activeActions->count() >= 4 =>
                'MODERATE_GOVERNANCE_WORKLOAD',

            $activeActions->count() > 0 =>
                'LOW_GOVERNANCE_WORKLOAD',

            default =>
                'NO_ACTIVE_WORKLOAD',
        };

        /*
        |--------------------------------------------------------------------------
        | Queue Formatting
        |--------------------------------------------------------------------------
        */

        $formatQueue = function ($collection): array {
            return $collection
                ->sortByDesc('priority_score')
                ->map(function ($action) {
                    return [
                        'action_id' => $action->id,
                        'action_code' => $action->action_code,
                        'action_category' => $action->action_category,
                        'priority_level' => $action->priority_level,
                        'priority_score' => (int) $action->priority_score,
                        'action_status' => $action->action_status,
                        'eligibility_status' => $action->eligibility_status,
                        'review_decision' => $action->review_decision,
                        'human_review_required' =>
                            (bool) $action->human_review_required,
                        'governance_validation_required' =>
                            (bool) $action->governance_validation_required,
                    ];
                })
                ->values()
                ->toArray();
        };

        /*
        |--------------------------------------------------------------------------
        | Highest Active Priority
        |--------------------------------------------------------------------------
        */

        $highestPriorityAction = $activeActions
            ->sortByDesc('priority_score')
            ->first();

        $highestPriority = $highestPriorityAction
            ? [
                'action_id' => $highestPriorityAction->id,
                'action_code' => $highestPriorityAction->action_code,
                'action_status' => $highestPriorityAction->action_status,
                'priority_level' => $highestPriorityAction->priority_level,
                'priority_score' => (int) $highestPriorityAction->priority_score,
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($humanReviewQueue->count() > 0) {
            $managementPriorities[] =
                'Complete human review of pending governance actions in descending priority order.';
        }

        if ($evidenceQueue->count() > 0) {
            $managementPriorities[] =
                'Collect the additional validated evidence required by evidence-dependent governance actions.';
        }

        if ($deferredQueue->count() > 0) {
            $managementPriorities[] =
                'Keep deferred governance actions under observation until their reassessment conditions are satisfied.';
        }

        if ($criticalActive > 0 || $highActive > 0) {
            $managementPriorities[] =
                'Prioritize unresolved high or critical governance actions before lower-priority advisory work.';
        }

        if ($activeActions->count() === 0) {
            $managementPriorities[] =
                'No active governance action workload currently requires human attention.';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            $actions->count() . ' governance action(s) are included in workload intelligence.',
            $activeActions->count() . ' action(s) remain active.',
            $closedActions->count() . ' action(s) are closed.',
            $humanReviewQueue->count() . ' action(s) are awaiting human review.',
            $evidenceQueue->count() . ' action(s) are waiting for additional evidence.',
            $deferredQueue->count() . ' action(s) remain deferred.',
            "Current active governance workload classification is {$workloadStatus}.",
            "Average weighted active priority is {$averageWeightedPriority}.",
        ];

        if ($highestPriority) {
            $findings[] =
                'Highest active queue item is '
                . $highestPriority['action_code']
                . ' with priority score '
                . $highestPriority['priority_score']
                . '.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_WORKLOAD_QUEUE_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'workload_status' =>
                $workloadStatus,

            'workload_summary' => [
                'total_actions' =>
                    $actions->count(),

                'active_actions' =>
                    $activeActions->count(),

                'closed_actions' =>
                    $closedActions->count(),

                'pending_human_review' =>
                    $humanReviewQueue->count(),

                'evidence_waiting_actions' =>
                    $evidenceQueue->count(),

                'deferred_actions' =>
                    $deferredQueue->count(),

                'approved_unresolved_actions' =>
                    $approvedQueue->count(),

                'open_actions' =>
                    $openQueue->count(),

                'critical_active_actions' =>
                    $criticalActive,

                'high_active_actions' =>
                    $highActive,

                'moderate_active_actions' =>
                    $moderateActive,

                'advisory_active_actions' =>
                    $advisoryActive,

                'average_weighted_priority' =>
                    $averageWeightedPriority,

                'closure_percentage' =>
                    $closure['closure_summary']['closure_percentage']
                    ?? 0,
            ],

            'highest_active_priority' =>
                $highestPriority,

            'queues' => [
                'human_review_queue' =>
                    $formatQueue($humanReviewQueue),

                'evidence_queue' =>
                    $formatQueue($evidenceQueue),

                'deferred_queue' =>
                    $formatQueue($deferredQueue),

                'approved_unresolved_queue' =>
                    $formatQueue($approvedQueue),

                'open_queue' =>
                    $formatQueue($openQueue),
            ],

            'management_priorities' =>
                $managementPriorities,

            'workload_findings' =>
                $findings,

            'workload_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'workload_intelligence_is_approval' =>
                false,

            'workload_intelligence_is_resolution' =>
                false,

            'workload_intelligence_is_execution' =>
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
                'Governance workload and queue intelligence prioritizes human governance work only. It does not approve, resolve, execute, deploy, rollback, modify AI behavior, or initiate clinical action.',
        ];
    }
}