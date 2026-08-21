<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceActionClosureIntelligenceEngine
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

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        if ($actions->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'NO_GOVERNANCE_ACTIONS_AVAILABLE',
                'snapshot_id' => $snapshot->id,
                'closure_summary' => [
                    'total_actions' => 0,
                    'resolved_actions' => 0,
                    'closed_rejected_actions' => 0,
                    'deferred_actions' => 0,
                    'more_evidence_required_actions' => 0,
                    'pending_review_actions' => 0,
                    'approved_actions' => 0,
                    'open_actions' => 0,
                    'closed_actions' => 0,
                    'active_actions' => 0,
                    'closure_percentage' => 0.0,
                ],
                'action_states' => [],
                'closure_guardrails' => $this->guardrails(),
            ];
        }

        $resolved = $actions->where('action_status', 'RESOLVED')->count();
        $closedRejected = $actions->where('action_status', 'CLOSED_REJECTED')->count();
        $deferred = $actions->where('action_status', 'DEFERRED')->count();
        $moreEvidence = $actions->where('action_status', 'MORE_EVIDENCE_REQUIRED')->count();
        $pendingReview = $actions->where('action_status', 'PENDING_REVIEW')->count();
        $approved = $actions->where('action_status', 'APPROVED')->count();
        $open = $actions->where('action_status', 'OPEN')->count();

        $closedActions = $resolved + $closedRejected;

        $activeActions = $actions->count() - $closedActions;

        $closurePercentage = $actions->count() > 0
            ? round(($closedActions / $actions->count()) * 100, 2)
            : 0.0;

        $actionStates = $actions->map(function ($action) {
            return [
                'action_id' => $action->id,
                'action_code' => $action->action_code,
                'action_category' => $action->action_category,
                'priority_level' => $action->priority_level,
                'priority_score' => (int) $action->priority_score,
                'action_status' => $action->action_status,
                'eligibility_status' => $action->eligibility_status,
                'review_decision' => $action->review_decision,
                'reviewed_by' => $action->reviewed_by,
                'reviewed_at' => $action->reviewed_at,
                'resolved_by' => $action->resolved_by,
                'resolved_at' => $action->resolved_at,
                'is_closed' => in_array(
                    $action->action_status,
                    ['RESOLVED', 'CLOSED_REJECTED'],
                    true
                ),
                'requires_more_evidence' =>
                    $action->action_status === 'MORE_EVIDENCE_REQUIRED',
                'remains_deferred' =>
                    $action->action_status === 'DEFERRED',
                'awaiting_human_review' =>
                    $action->action_status === 'PENDING_REVIEW',
                'automatic_execution_allowed' =>
                    (bool) $action->automatic_execution_allowed,
                'automatic_change_allowed' =>
                    (bool) $action->automatic_change_allowed,
                'automatic_deployment_allowed' =>
                    (bool) $action->automatic_deployment_allowed,
                'automatic_rollback_allowed' =>
                    (bool) $action->automatic_rollback_allowed,
                'automatic_clinical_action_allowed' =>
                    (bool) $action->automatic_clinical_action_allowed,
            ];
        })->values()->toArray();

        $highestOpenPriority = collect($actionStates)
            ->filter(fn ($action) => !$action['is_closed'])
            ->sortByDesc('priority_score')
            ->first();

        $managementStatus = match (true) {
            $pendingReview > 0 => 'HUMAN_REVIEW_REQUIRED',
            $moreEvidence > 0 => 'ADDITIONAL_EVIDENCE_REQUIRED',
            $deferred > 0 => 'DEFERRED_ACTIONS_REMAIN',
            $activeActions === 0 => 'ALL_ACTIONS_CLOSED',
            default => 'ACTIVE_GOVERNANCE_ACTIONS_REMAIN',
        };

        $findings = [
            $actions->count() . ' governance action(s) are included in closure intelligence.',
            "{$closedActions} action(s) are currently closed.",
            "{$activeActions} action(s) remain active or unresolved.",
            "{$pendingReview} action(s) remain pending human review.",
            "{$deferred} action(s) remain deferred.",
            "{$moreEvidence} action(s) require additional evidence.",
            "Current governance action closure percentage is {$closurePercentage}%.",
        ];

        if ($resolved > 0) {
            $findings[] =
                "{$resolved} approved governance action(s) have reached resolved status.";
        }

        if ($closedRejected > 0) {
            $findings[] =
                "{$closedRejected} rejected governance action(s) have been formally closed.";
        }

        if ($highestOpenPriority) {
            $findings[] =
                'Highest currently active governance priority is '
                . $highestOpenPriority['action_code']
                . ' with priority score '
                . $highestOpenPriority['priority_score']
                . '.';
        }

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_ACTION_CLOSURE_INTELLIGENCE_AVAILABLE',
            'snapshot_id' => $snapshot->id,
            'snapshot_scope' => $snapshot->snapshot_scope,
            'resident_id' => $snapshot->resident_id,

            'management_status' => $managementStatus,

            'closure_summary' => [
                'total_actions' => $actions->count(),
                'resolved_actions' => $resolved,
                'closed_rejected_actions' => $closedRejected,
                'deferred_actions' => $deferred,
                'more_evidence_required_actions' => $moreEvidence,
                'pending_review_actions' => $pendingReview,
                'approved_actions' => $approved,
                'open_actions' => $open,
                'closed_actions' => $closedActions,
                'active_actions' => $activeActions,
                'closure_percentage' => $closurePercentage,
            ],

            'highest_open_priority' => $highestOpenPriority,

            'action_states' => $actionStates,

            'closure_findings' => $findings,

            'closure_guardrails' => $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'closure_intelligence_is_resolution' => false,
            'closure_intelligence_is_execution' => false,
            'closure_intelligence_is_ai_change' => false,
            'automatic_execution_allowed' => false,
            'automatic_change_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,
            'automatic_clinical_action_allowed' => false,
            'human_review_required' => true,
            'governance_validation_required' => true,
            'message' =>
                'Governance action closure intelligence summarizes action lifecycle state only. It does not resolve actions, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}