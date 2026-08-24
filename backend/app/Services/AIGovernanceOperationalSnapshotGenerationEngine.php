<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;
use App\Models\AIImprovementLifecycleSnapshot;
use Illuminate\Support\Facades\DB;

class AIGovernanceOperationalSnapshotGenerationEngine
{
    public function capture(?int $lifecycleSnapshotId = null): array
    {
        $lifecycleSnapshot = $lifecycleSnapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($lifecycleSnapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$lifecycleSnapshot) {
            return [
                'captured' => false,
                'status' => 'LIFECYCLE_SNAPSHOT_NOT_FOUND',
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'lifecycle_snapshot_id' => $lifecycleSnapshotId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Intelligence
        |--------------------------------------------------------------------------
        */

        $workload = app(
            AIGovernanceActionWorkloadQueueIntelligenceEngine::class
        )->analyze();

        $decision = app(
            AIGovernanceDecisionIntelligenceEngine::class
        )->analyze($lifecycleSnapshot->id);

        $consistency = app(
            AIGovernanceDecisionConsistencyIntelligenceEngine::class
        )->analyze($lifecycleSnapshot->id);

        $risk = app(
            AIGovernanceDecisionRiskIntelligenceEngine::class
        )->analyze($lifecycleSnapshot->id);

        $executive = app(
            AIGovernanceDecisionExecutiveSummaryEngine::class
        )->analyze($lifecycleSnapshot->id);

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $workloadSummary =
            $workload['workload_summary']
            ?? [];

        $decisionSummary =
            $decision['decision_summary']
            ?? [];

        $resolutionSummary =
            $decision['resolution_summary']
            ?? [];

        $consistencySummary =
            $consistency['consistency_summary']
            ?? [];

        $riskSummary =
            $risk['risk_summary']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Governance Action Counts
        |--------------------------------------------------------------------------
        */

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $lifecycleSnapshot->id)
            ->get();

        $totalActions =
            $actions->count();

        $closedStatuses = [
            'RESOLVED',
            'CLOSED_REJECTED',
        ];

        $closedActions =
            $actions
                ->whereIn(
                    'action_status',
                    $closedStatuses
                )
                ->count();

        $activeActions =
            max(
                0,
                $totalActions - $closedActions
            );

        $pendingHumanReviews =
            (int) (
                $workloadSummary[
                    'pending_human_review'
                ]
                ?? 0
            );

        $pendingHumanDecisions =
            (int) (
                $decisionSummary[
                    'pending_decisions'
                ]
                ?? 0
            );

        $evidenceWaitingActions =
            (int) (
                $workloadSummary[
                    'evidence_waiting_actions'
                ]
                ?? 0
            );

        $deferredActions =
            (int) (
                $workloadSummary[
                    'deferred_actions'
                ]
                ?? 0
            );

        $highPriorityActive =
            $actions
                ->filter(
                    fn ($action) =>
                        !in_array(
                            $action->action_status,
                            $closedStatuses,
                            true
                        )
                        &&
                        $action->priority_level === 'HIGH'
                )
                ->count();

        $criticalPriorityActive =
            $actions
                ->filter(
                    fn ($action) =>
                        !in_array(
                            $action->action_status,
                            $closedStatuses,
                            true
                        )
                        &&
                        $action->priority_level === 'CRITICAL'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Operational Status
        |--------------------------------------------------------------------------
        */

        $operationalStatus =
            $this->determineOperationalStatus(
                $criticalPriorityActive,
                $highPriorityActive,
                $pendingHumanReviews,
                $pendingHumanDecisions,
                $evidenceWaitingActions,
                $deferredActions,
                $activeActions
            );

        /*
        |--------------------------------------------------------------------------
        | Operational Context
        |--------------------------------------------------------------------------
        */

        $operationalContext = [
            'snapshot_version' =>
                '61.2',

            'workload_status' =>
                $workload['workload_status']
                ?? null,

            'executive_status' =>
                $executive['executive_status']
                ?? null,

            'executive_readiness' =>
                $executive['executive_readiness']
                ?? null,

            'executive_confidence' =>
                $executive['executive_confidence']
                ?? null,

            'highest_active_priority' =>
                $workload['highest_active_priority']
                ?? null,

            'highest_unresolved_priority' =>
                $decision['highest_unresolved_priority']
                ?? null,

            'decision_state' =>
                $decision['decision_state']
                ?? null,

            'decision_outcome_alignment' =>
                app(
                    AIGovernanceDecisionOutcomeCorrelationEngine::class
                )->analyze(
                    $lifecycleSnapshot->id
                )['correlation_summary']['outcome_alignment']
                ?? null,

            'human_review_recommended' =>
                $risk['human_review_recommended']
                ?? false,

            'captured_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

        $sourceContext = [
            'lifecycle_snapshot_id' =>
                $lifecycleSnapshot->id,

            'lifecycle_snapshot_status' =>
                $lifecycleSnapshot->snapshot_status,

            'lifecycle_overall_improvement_status' =>
                $lifecycleSnapshot->overall_improvement_status,

            'governance_workload_status' =>
                $workload['workload_status']
                ?? null,

            'decision_state' =>
                $decision['decision_state']
                ?? null,

            'decision_risk_level' =>
                $risk['decision_risk_level']
                ?? null,

            'decision_risk_score' =>
                $risk['decision_risk_score']
                ?? null,

            'decision_consistency_status' =>
                $consistencySummary[
                    'consistency_status'
                ]
                ?? null,

            'decision_consistency_score' =>
                $consistencySummary[
                    'consistency_score'
                ]
                ?? null,

            'executive_status' =>
                $executive['executive_status']
                ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Persistence
        |--------------------------------------------------------------------------
        */

        $snapshot = DB::transaction(
            function () use (
                $lifecycleSnapshot,
                $operationalStatus,
                $workload,
                $decision,
                $totalActions,
                $activeActions,
                $closedActions,
                $pendingHumanReviews,
                $pendingHumanDecisions,
                $evidenceWaitingActions,
                $deferredActions,
                $highPriorityActive,
                $criticalPriorityActive,
                $resolutionSummary,
                $decisionSummary,
                $consistencySummary,
                $risk,
                $operationalContext,
                $sourceContext
            ) {
                return AIGovernanceOperationalSnapshot::create([
                    'lifecycle_snapshot_id' =>
                        $lifecycleSnapshot->id,

                    'snapshot_scope' =>
                        $lifecycleSnapshot->snapshot_scope,

                    'resident_id' =>
                        $lifecycleSnapshot->resident_id,

                    'snapshot_status' =>
                        'CAPTURED',

                    'operational_status' =>
                        $operationalStatus,

                    'governance_workload_status' =>
                        $workload['workload_status']
                        ?? null,

                    'decision_status' =>
                        $decision['decision_state']
                        ?? null,

                    'total_governance_actions' =>
                        $totalActions,

                    'active_governance_actions' =>
                        $activeActions,

                    'closed_governance_actions' =>
                        $closedActions,

                    'pending_human_reviews' =>
                        $pendingHumanReviews,

                    'pending_human_decisions' =>
                        $pendingHumanDecisions,

                    'evidence_waiting_actions' =>
                        $evidenceWaitingActions,

                    'deferred_actions' =>
                        $deferredActions,

                    'high_priority_active_actions' =>
                        $highPriorityActive,

                    'critical_priority_active_actions' =>
                        $criticalPriorityActive,

                    'action_closure_percentage' =>
                        (float) (
                            $resolutionSummary[
                                'closure_percentage'
                            ]
                            ?? 0
                        ),

                    'decision_completion_percentage' =>
                        (float) (
                            $decisionSummary[
                                'decision_completion_percentage'
                            ]
                            ?? 0
                        ),

                    'decision_consistency_status' =>
                        $consistencySummary[
                            'consistency_status'
                        ]
                        ?? null,

                    'decision_consistency_score' =>
                        $consistencySummary[
                            'consistency_score'
                        ]
                        ?? null,

                    'decision_risk_level' =>
                        $risk['decision_risk_level']
                        ?? null,

                    'decision_risk_score' =>
                        $risk['decision_risk_score']
                        ?? null,

                    'operational_context' =>
                        $operationalContext,

                    'source_context' =>
                        $sourceContext,

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

                    'captured_at' =>
                        now(),
                ]);
            }
        );

        return [
            'captured' =>
                true,

            'status' =>
                'GOVERNANCE_OPERATIONAL_SNAPSHOT_CAPTURED',

            'message' =>
                'AI governance operational snapshot captured successfully.',

            'snapshot' => [
                'operational_snapshot_id' =>
                    $snapshot->id,

                'lifecycle_snapshot_id' =>
                    $snapshot->lifecycle_snapshot_id,

                'snapshot_scope' =>
                    $snapshot->snapshot_scope,

                'resident_id' =>
                    $snapshot->resident_id,

                'snapshot_status' =>
                    $snapshot->snapshot_status,

                'operational_status' =>
                    $snapshot->operational_status,

                'governance_workload_status' =>
                    $snapshot->governance_workload_status,

                'decision_status' =>
                    $snapshot->decision_status,

                'total_governance_actions' =>
                    $snapshot->total_governance_actions,

                'active_governance_actions' =>
                    $snapshot->active_governance_actions,

                'closed_governance_actions' =>
                    $snapshot->closed_governance_actions,

                'pending_human_reviews' =>
                    $snapshot->pending_human_reviews,

                'pending_human_decisions' =>
                    $snapshot->pending_human_decisions,

                'evidence_waiting_actions' =>
                    $snapshot->evidence_waiting_actions,

                'deferred_actions' =>
                    $snapshot->deferred_actions,

                'high_priority_active_actions' =>
                    $snapshot->high_priority_active_actions,

                'critical_priority_active_actions' =>
                    $snapshot->critical_priority_active_actions,

                'action_closure_percentage' =>
                    $snapshot->action_closure_percentage,

                'decision_completion_percentage' =>
                    $snapshot->decision_completion_percentage,

                'decision_consistency_status' =>
                    $snapshot->decision_consistency_status,

                'decision_consistency_score' =>
                    $snapshot->decision_consistency_score,

                'decision_risk_level' =>
                    $snapshot->decision_risk_level,

                'decision_risk_score' =>
                    $snapshot->decision_risk_score,

                'captured_at' =>
                    $snapshot->captured_at,
            ],

            'snapshot_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function determineOperationalStatus(
        int $criticalPriorityActive,
        int $highPriorityActive,
        int $pendingHumanReviews,
        int $pendingHumanDecisions,
        int $evidenceWaitingActions,
        int $deferredActions,
        int $activeActions
    ): string {
        if ($criticalPriorityActive > 0) {
            return 'CRITICAL_HUMAN_ATTENTION_REQUIRED';
        }

        if ($highPriorityActive > 0) {
            return 'HIGH_PRIORITY_GOVERNANCE_WORK_ACTIVE';
        }

        if (
            $pendingHumanReviews > 0
            || $pendingHumanDecisions > 0
        ) {
            return 'HUMAN_GOVERNANCE_WORK_PENDING';
        }

        if (
            $evidenceWaitingActions > 0
            || $deferredActions > 0
        ) {
            return 'GOVERNANCE_OBSERVATION_REQUIRED';
        }

        if ($activeActions > 0) {
            return 'CONTROLLED_GOVERNANCE_WORK_ACTIVE';
        }

        return 'CURRENT_GOVERNANCE_WORK_CLEAR';
    }

    private function guardrails(): array
    {
        return [
            'operational_snapshotting_enabled' =>
                true,

            'snapshot_is_governance_decision' =>
                false,

            'snapshot_is_action_resolution' =>
                false,

            'snapshot_is_execution_authorization' =>
                false,

            'snapshot_changes_priority' =>
                false,

            'snapshot_changes_eligibility' =>
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
                'Governance operational snapshot generation consolidates current governance workload, decision, consistency, and risk state for operational oversight only. Snapshot generation does not approve, reject, defer, resolve, reprioritize, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}