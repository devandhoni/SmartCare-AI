<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceActionAuditSummaryEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'audit_available' => false,
                'audit_status' => 'SNAPSHOT_NOT_FOUND',
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'snapshot_id' => $snapshotId,
            ];
        }

        $closure = app(
            AIGovernanceActionClosureIntelligenceEngine::class
        )->analyze($snapshot->id);

        $workload = app(
            AIGovernanceActionWorkloadQueueIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($closure['analysis_completed'] ?? false)) {
            return [
                'audit_available' => false,
                'audit_status' => 'CLOSURE_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($workload['analysis_completed'] ?? false)) {
            return [
                'audit_available' => false,
                'audit_status' => 'WORKLOAD_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        $checks = [];

        $checks['governance_actions_available'] = [
            'passed' => $actions->count() > 0,
            'message' => 'Governance action registry contains generated action records.',
        ];

        $checks['priority_intelligence_available'] = [
            'passed' => $actions->every(
                fn ($action) =>
                    $action->priority_context !== null
            ),
            'message' => 'Governance actions contain deterministic priority intelligence.',
        ];

        $checks['eligibility_validation_available'] = [
            'passed' => $actions->every(
                fn ($action) =>
                    $action->eligibility_context !== null
            ),
            'message' => 'Governance actions contain eligibility and safety validation.',
        ];

        $reviewedActions = $actions->filter(
            fn ($action) =>
                $action->review_decision !== null
        );

        $checks['human_review_integrity'] = [
            'passed' =>
                $reviewedActions->count() === $reviews->count(),
            'message' =>
                'Human governance decisions are represented by dedicated review records.',
        ];

        $closedActions = $actions->filter(
            fn ($action) =>
                in_array(
                    $action->action_status,
                    ['RESOLVED', 'CLOSED_REJECTED'],
                    true
                )
        );

        $checks['closed_actions_have_resolution_evidence'] = [
            'passed' =>
                $closedActions->every(
                    fn ($action) =>
                        $action->resolved_at !== null
                        && $action->resolution_context !== null
                ),
            'message' =>
                'Closed governance actions must contain explicit resolution evidence.',
        ];

        $checks['automatic_execution_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_execution_allowed
                ),
            'message' =>
                'Automatic governance action execution remains disabled.',
        ];

        $checks['automatic_change_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_change_allowed
                ),
            'message' =>
                'Automatic AI system modification remains disabled.',
        ];

        $checks['automatic_deployment_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_deployment_allowed
                ),
            'message' =>
                'Automatic deployment remains disabled.',
        ];

        $checks['automatic_rollback_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_rollback_allowed
                ),
            'message' =>
                'Automatic rollback remains disabled.',
        ];

        $checks['automatic_clinical_action_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_clinical_action_allowed
                ),
            'message' =>
                'Automatic clinical action remains disabled.',
        ];

        $checks['human_review_controls'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        (bool) $action->human_review_required
                ),
            'message' =>
                'Human review remains required across the governance action lifecycle.',
        ];

        $checks['governance_validation_controls'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        (bool) $action->governance_validation_required
                ),
            'message' =>
                'Governance validation remains mandatory across all actions.',
        ];

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        $decisionSummary = [
            'total_review_records' =>
                $reviews->count(),

            'approved_decisions' =>
                $reviews->where(
                    'review_decision',
                    'APPROVE'
                )->count(),

            'rejected_decisions' =>
                $reviews->where(
                    'review_decision',
                    'REJECT'
                )->count(),

            'deferred_decisions' =>
                $reviews->where(
                    'review_decision',
                    'DEFER'
                )->count(),

            'more_evidence_decisions' =>
                $reviews->where(
                    'review_decision',
                    'REQUEST_MORE_EVIDENCE'
                )->count(),
        ];

        $managementStatus = match (true) {
            $failedChecks > 0 =>
                'GOVERNANCE_INTEGRITY_ATTENTION_REQUIRED',

            ($workload['workload_summary']['critical_active_actions'] ?? 0) > 0 =>
                'CRITICAL_ACTION_ATTENTION_REQUIRED',

            ($workload['workload_summary']['pending_human_review'] ?? 0) > 0 =>
                'HUMAN_REVIEW_WORK_REMAINS',

            ($workload['workload_summary']['evidence_waiting_actions'] ?? 0) > 0 =>
                'EVIDENCE_COLLECTION_REMAINS',

            ($workload['workload_summary']['active_actions'] ?? 0) > 0 =>
                'ACTIVE_GOVERNANCE_WORK_REMAINS',

            default =>
                'GOVERNANCE_ACTION_LIFECYCLE_COMPLETE',
        };

        $findings = [
            'Governance action audit completed '
                . $totalChecks
                . ' integrity check(s), with '
                . $passedChecks
                . ' passed and '
                . $failedChecks
                . ' failed.',

            $actions->count()
                . ' governance action(s) are included in the Step 59 audit.',

            $reviews->count()
                . ' completed human governance review record(s) are available.',

            ($closure['closure_summary']['closed_actions'] ?? 0)
                . ' governance action(s) are closed.',

            ($closure['closure_summary']['active_actions'] ?? 0)
                . ' governance action(s) remain active.',

            ($workload['workload_summary']['pending_human_review'] ?? 0)
                . ' governance action(s) remain pending human review.',

            ($workload['workload_summary']['evidence_waiting_actions'] ?? 0)
                . ' governance action(s) require additional evidence.',

            'Current governance workload status is '
                . ($workload['workload_status'] ?? 'UNKNOWN')
                . '.',

            'Current action closure percentage is '
                . ($closure['closure_summary']['closure_percentage'] ?? 0)
                . '%.',
        ];

        $managementPriorities = [];

        if (
            ($workload['workload_summary']['pending_human_review'] ?? 0) > 0
        ) {
            $managementPriorities[] =
                'Complete pending human governance action reviews in priority order.';
        }

        if (
            ($workload['workload_summary']['evidence_waiting_actions'] ?? 0) > 0
        ) {
            $managementPriorities[] =
                'Collect additional validated evidence for evidence-dependent governance actions.';
        }

        if (
            ($workload['workload_summary']['deferred_actions'] ?? 0) > 0
        ) {
            $managementPriorities[] =
                'Maintain deferred actions under governed observation until reassessment conditions are met.';
        }

        if ($failedChecks > 0) {
            $managementPriorities[] =
                'Resolve governance action integrity failures before expanding action lifecycle use.';
        }

        if (
            ($workload['workload_summary']['active_actions'] ?? 0) === 0
        ) {
            $managementPriorities[] =
                'No unresolved Step 59 governance action workload remains.';
        }

        return [
            'audit_available' => true,

            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_ISSUES',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'management_status' =>
                $managementStatus,

            'audit_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            'action_summary' => [
                'total_actions' =>
                    $actions->count(),

                'closed_actions' =>
                    $closure['closure_summary']['closed_actions'] ?? 0,

                'active_actions' =>
                    $closure['closure_summary']['active_actions'] ?? 0,

                'closure_percentage' =>
                    $closure['closure_summary']['closure_percentage'] ?? 0,

                'workload_status' =>
                    $workload['workload_status'] ?? 'UNKNOWN',

                'highest_active_priority' =>
                    $workload['highest_active_priority'] ?? null,
            ],

            'decision_summary' =>
                $decisionSummary,

            'queue_summary' => [
                'pending_human_review' =>
                    $workload['workload_summary']['pending_human_review'] ?? 0,

                'evidence_waiting_actions' =>
                    $workload['workload_summary']['evidence_waiting_actions'] ?? 0,

                'deferred_actions' =>
                    $workload['workload_summary']['deferred_actions'] ?? 0,

                'approved_unresolved_actions' =>
                    $workload['workload_summary']['approved_unresolved_actions'] ?? 0,
            ],

            'audit_findings' =>
                $findings,

            'management_priorities' =>
                $managementPriorities,

            'audit_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'audit_is_approval' =>
                false,

            'audit_is_resolution' =>
                false,

            'audit_is_execution_authorization' =>
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
                'Governance action audit consolidates Step 59 governance work for management review only. It does not approve, resolve, execute, deploy, rollback, modify AI behavior, or initiate clinical action.',
        ];
    }
}