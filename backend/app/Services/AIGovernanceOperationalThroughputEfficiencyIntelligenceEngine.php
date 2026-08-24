<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceOperationalThroughputEfficiencyIntelligenceEngine
{
    public function analyze(?int $operationalSnapshotId = null): array
    {
        $snapshot = $operationalSnapshotId !== null
            ? AIGovernanceOperationalSnapshot::find($operationalSnapshotId)
            : AIGovernanceOperationalSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'analysis_completed' => false,
                'status' => 'OPERATIONAL_SNAPSHOT_NOT_FOUND',
                'operational_snapshot_id' => $operationalSnapshotId,
                'message' => 'AI governance operational snapshot was not found.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Intelligence
        |--------------------------------------------------------------------------
        */

        $operationalState = app(
            AIGovernanceOperationalStateIntelligenceEngine::class
        )->analyze($snapshot->id);

        $escalation = app(
            AIGovernanceAttentionEscalationIntelligenceEngine::class
        )->analyze($snapshot->id);

        $bottleneck = app(
            AIGovernanceBottleneckIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (
            ($operationalState['analysis_completed'] ?? false) !== true
            || ($escalation['analysis_completed'] ?? false) !== true
            || ($bottleneck['analysis_completed'] ?? false) !== true
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'SOURCE_OPERATIONAL_INTELLIGENCE_UNAVAILABLE',
                'operational_snapshot_id' => $snapshot->id,
                'message' => 'Required governance operational intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Action Population
        |--------------------------------------------------------------------------
        */

        $closedStatuses = [
            'RESOLVED',
            'CLOSED_REJECTED',
        ];

        $actions = AIGovernanceAction::query()
            ->where(
                'lifecycle_snapshot_id',
                $snapshot->lifecycle_snapshot_id
            )
            ->orderBy('id')
            ->get();

        $totalActions = $actions->count();

        $closedActions = $actions
            ->whereIn('action_status', $closedStatuses)
            ->values();

        $activeActions = $actions
            ->whereNotIn('action_status', $closedStatuses)
            ->values();

        $reviewedActions = $actions
            ->filter(
                fn ($action) =>
                    !empty($action->review_decision)
            )
            ->values();

        $unreviewedActions = $actions
            ->filter(
                fn ($action) =>
                    empty($action->review_decision)
            )
            ->values();

        $pendingReviews = $activeActions
            ->where('action_status', 'PENDING_REVIEW')
            ->values();

        $evidenceWaiting = $activeActions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->values();

        $deferredActions = $activeActions
            ->where('action_status', 'DEFERRED')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Review Registry
        |--------------------------------------------------------------------------
        */

        $reviews = AIGovernanceActionReview::query()
            ->whereHas(
                'governanceAction',
                fn ($query) =>
                    $query->where(
                        'lifecycle_snapshot_id',
                        $snapshot->lifecycle_snapshot_id
                    )
            )
            ->where('review_status', 'COMPLETED')
            ->orderBy('id')
            ->get();

        $completedReviewRecords = $reviews->count();

        /*
        |--------------------------------------------------------------------------
        | Core Progress Percentages
        |--------------------------------------------------------------------------
        */

        $reviewCompletionPercentage = $this->percentage(
            $reviewedActions->count(),
            $totalActions
        );

        $decisionCompletionPercentage =
            (float) $snapshot->decision_completion_percentage;

        $closurePercentage =
            (float) $snapshot->action_closure_percentage;

        $activePercentage = $this->percentage(
            $activeActions->count(),
            $totalActions
        );

        $evidenceWaitingPercentage = $this->percentage(
            $evidenceWaiting->count(),
            $totalActions
        );

        $deferredPercentage = $this->percentage(
            $deferredActions->count(),
            $totalActions
        );

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity
        |--------------------------------------------------------------------------
        */

        $automaticPermissionExceptions = $actions
            ->filter(
                fn ($action) =>
                    (bool) $action->automatic_execution_allowed
                    || (bool) $action->automatic_change_allowed
                    || (bool) $action->automatic_deployment_allowed
                    || (bool) $action->automatic_rollback_allowed
                    || (bool) $action->automatic_clinical_action_allowed
            )
            ->count();

        $humanGovernanceExceptions = $actions
            ->filter(
                fn ($action) =>
                    !(bool) $action->human_review_required
                    || !(bool) $action->governance_validation_required
            )
            ->count();

        $governanceIntegrityIntact =
            $automaticPermissionExceptions === 0
            && $humanGovernanceExceptions === 0;

        /*
        |--------------------------------------------------------------------------
        | Throughput Components
        |--------------------------------------------------------------------------
        |
        | Efficiency is intentionally not "speed".
        |
        | The score rewards:
        | - human-review completion
        | - explicit decision completion
        | - governed closure
        | - consistency/integrity
        |
        | It penalizes unresolved pressure only moderately.
        |--------------------------------------------------------------------------
        */

        $reviewProgressComponent =
            $reviewCompletionPercentage;

        $decisionProgressComponent =
            $decisionCompletionPercentage;

        $closureProgressComponent =
            $closurePercentage;

        $consistencyComponent =
            min(
                100,
                max(
                    0,
                    (float) $snapshot->decision_consistency_score
                )
            );

        $governanceIntegrityComponent =
            $governanceIntegrityIntact
                ? 100.0
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Base Efficiency Score
        |--------------------------------------------------------------------------
        */

        $baseEfficiencyScore =
            ($reviewProgressComponent * 0.20)
            + ($decisionProgressComponent * 0.25)
            + ($closureProgressComponent * 0.25)
            + ($consistencyComponent * 0.15)
            + ($governanceIntegrityComponent * 0.15);

        /*
        |--------------------------------------------------------------------------
        | Operational Friction
        |--------------------------------------------------------------------------
        */

        $bottleneckLevel =
            $bottleneck['bottleneck_state']['bottleneck_level']
            ?? 'UNKNOWN';

        $bottleneckScore =
            (float) (
                $bottleneck['bottleneck_state']['bottleneck_score']
                ?? 0
            );

        $escalationLevel =
            $escalation['escalation_state']['escalation_level']
            ?? 'UNKNOWN';

        $escalationScore =
            (float) (
                $escalation['escalation_state']['escalation_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Friction Adjustment
        |--------------------------------------------------------------------------
        |
        | Keep this bounded. We do not want the score to punish the system
        | heavily simply because human review or evidence collection takes time.
        |--------------------------------------------------------------------------
        */

        $frictionAdjustment =
            min(
                15,
                ($bottleneckScore * 0.08)
                + ($escalationScore * 0.04)
            );

        $efficiencyScore = round(
            max(
                0,
                min(
                    100,
                    $baseEfficiencyScore - $frictionAdjustment
                )
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Efficiency Classification
        |--------------------------------------------------------------------------
        */

        $efficiencyStatus = match (true) {
            !$governanceIntegrityIntact =>
                'GOVERNANCE_CONTROL_EXCEPTION',

            $efficiencyScore >= 85 =>
                'HIGHLY_EFFECTIVE',

            $efficiencyScore >= 70 =>
                'EFFECTIVE_WITH_ACTIVE_WORK',

            $efficiencyScore >= 55 =>
                'DEVELOPING_EFFICIENCY',

            $efficiencyScore >= 40 =>
                'CONSTRAINED_EFFICIENCY',

            default =>
                'LOW_OPERATIONAL_EFFICIENCY',
        };

        /*
        |--------------------------------------------------------------------------
        | Throughput State
        |--------------------------------------------------------------------------
        */

        $throughputStatus = match (true) {
            $closurePercentage >= 80
                && $decisionCompletionPercentage >= 90 =>
                'STRONG_GOVERNANCE_THROUGHPUT',

            $closurePercentage >= 50
                && $decisionCompletionPercentage >= 70 =>
                'MODERATE_GOVERNANCE_THROUGHPUT',

            $decisionCompletionPercentage >= 50 =>
                'PARTIAL_GOVERNANCE_THROUGHPUT',

            default =>
                'EARLY_GOVERNANCE_THROUGHPUT',
        };

        /*
        |--------------------------------------------------------------------------
        | Process Balance
        |--------------------------------------------------------------------------
        */

        $progressSpread = max(
            $reviewCompletionPercentage,
            $decisionCompletionPercentage,
            $closurePercentage
        ) - min(
            $reviewCompletionPercentage,
            $decisionCompletionPercentage,
            $closurePercentage
        );

        $processBalanceStatus = match (true) {
            $progressSpread <= 10 =>
                'BALANCED',

            $progressSpread <= 25 =>
                'MODERATELY_IMBALANCED',

            default =>
                'IMBALANCED',
        };

        /*
        |--------------------------------------------------------------------------
        | Efficiency Signals
        |--------------------------------------------------------------------------
        */

        $signals = [
            'review_progress' => [
                'status' =>
                    $reviewCompletionPercentage >= 80
                        ? 'STRONG'
                        : (
                            $reviewCompletionPercentage >= 50
                                ? 'DEVELOPING'
                                : 'LIMITED'
                        ),

                'value' =>
                    $reviewCompletionPercentage,

                'message' =>
                    'Percentage of governance actions with recorded human governance decisions.',
            ],

            'decision_progress' => [
                'status' =>
                    $decisionCompletionPercentage >= 80
                        ? 'STRONG'
                        : (
                            $decisionCompletionPercentage >= 50
                                ? 'DEVELOPING'
                                : 'LIMITED'
                        ),

                'value' =>
                    $decisionCompletionPercentage,

                'message' =>
                    'Percentage of governance actions with completed explicit human decisions.',
            ],

            'closure_progress' => [
                'status' =>
                    $closurePercentage >= 80
                        ? 'STRONG'
                        : (
                            $closurePercentage >= 50
                                ? 'DEVELOPING'
                                : 'LIMITED'
                        ),

                'value' =>
                    $closurePercentage,

                'message' =>
                    'Percentage of governance actions that have reached formally closed states.',
            ],

            'decision_consistency' => [
                'status' =>
                    $snapshot->decision_consistency_status,

                'value' =>
                    (float) $snapshot->decision_consistency_score,

                'message' =>
                    'Human governance decisions remain internally consistent with the current action lifecycle.',
            ],

            'governance_integrity' => [
                'status' =>
                    $governanceIntegrityIntact
                        ? 'INTACT'
                        : 'EXCEPTION_DETECTED',

                'automatic_permission_exceptions' =>
                    $automaticPermissionExceptions,

                'human_governance_control_exceptions' =>
                    $humanGovernanceExceptions,

                'message' =>
                    'Operational efficiency must never be achieved by weakening human-governance or automatic-authority controls.',
            ],

            'operational_friction' => [
                'bottleneck_level' =>
                    $bottleneckLevel,

                'bottleneck_score' =>
                    $bottleneckScore,

                'escalation_level' =>
                    $escalationLevel,

                'escalation_score' =>
                    $escalationScore,

                'friction_adjustment' =>
                    round($frictionAdjustment, 2),

                'message' =>
                    'Current operational bottlenecks and escalation pressure reduce practical governance throughput.',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($evidenceWaiting->count() > 0) {
            $managementPriorities[] =
                'Increase validated evidence availability for evidence-dependent governance actions.';
        }

        if ($pendingReviews->count() > 0) {
            $managementPriorities[] =
                'Continue pending human governance reviews in descending priority order.';
        }

        if ($unreviewedActions->count() > 0) {
            $managementPriorities[] =
                'Reduce the number of governance actions awaiting explicit human decision.';
        }

        if ($closurePercentage < 50.0) {
            $managementPriorities[] =
                'Improve governed action closure progression while preserving review, evidence, and safety controls.';
        }

        if ($processBalanceStatus === 'IMBALANCED') {
            $managementPriorities[] =
                'Reduce the gap between human decision completion and formal governance action closure.';
        }

        if ($bottleneckLevel === 'SIGNIFICANT_BOTTLENECK') {
            $managementPriorities[] =
                'Address the dominant operational bottleneck before expanding governance workload.';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "{$totalActions} governance action(s) are represented in operational throughput intelligence.",

            "{$completedReviewRecords} completed human governance review record(s) are available.",

            "Human governance review completion is {$reviewCompletionPercentage}%.",

            "Human governance decision completion is {$decisionCompletionPercentage}%.",

            "Formal governance action closure is {$closurePercentage}%.",

            "Current governance throughput classification is {$throughputStatus}.",

            "Current operational efficiency score is {$efficiencyScore}.",

            "Current operational efficiency classification is {$efficiencyStatus}.",

            "Current process balance classification is {$processBalanceStatus}.",

            "Current bottleneck classification is {$bottleneckLevel} with score {$bottleneckScore}.",

            "Current escalation classification is {$escalationLevel} with score {$escalationScore}.",

            "{$evidenceWaiting->count()} governance action(s) remain evidence-dependent.",

            "{$pendingReviews->count()} governance action(s) remain pending human review.",

            "{$deferredActions->count()} governance action(s) remain deferred.",

            "Automatic-permission exception count is {$automaticPermissionExceptions}.",

            "Human-governance control exception count is {$humanGovernanceExceptions}.",
        ];

        if ($governanceIntegrityIntact) {
            $findings[] =
                'Governance integrity remains intact while operational throughput is evaluated.';
        }

        $findings[] =
            'Operational efficiency intelligence does not treat faster review or closure as preferable when doing so would weaken evidence, safety, or human-governance controls.';

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_OPERATIONAL_THROUGHPUT_EFFICIENCY_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'throughput_state' => [
                'throughput_status' =>
                    $throughputStatus,

                'efficiency_status' =>
                    $efficiencyStatus,

                'efficiency_score' =>
                    $efficiencyScore,

                'process_balance_status' =>
                    $processBalanceStatus,

                'process_progress_spread' =>
                    round($progressSpread, 2),

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,
            ],

            'throughput_summary' => [
                'total_actions' =>
                    $totalActions,

                'active_actions' =>
                    $activeActions->count(),

                'closed_actions' =>
                    $closedActions->count(),

                'reviewed_actions' =>
                    $reviewedActions->count(),

                'unreviewed_actions' =>
                    $unreviewedActions->count(),

                'completed_review_records' =>
                    $completedReviewRecords,

                'pending_reviews' =>
                    $pendingReviews->count(),

                'evidence_waiting_actions' =>
                    $evidenceWaiting->count(),

                'deferred_actions' =>
                    $deferredActions->count(),
            ],

            'progress_metrics' => [
                'review_completion_percentage' =>
                    $reviewCompletionPercentage,

                'decision_completion_percentage' =>
                    $decisionCompletionPercentage,

                'closure_percentage' =>
                    $closurePercentage,

                'active_percentage' =>
                    $activePercentage,

                'evidence_waiting_percentage' =>
                    $evidenceWaitingPercentage,

                'deferred_percentage' =>
                    $deferredPercentage,
            ],

            'efficiency_components' => [
                'review_progress' =>
                    round($reviewProgressComponent, 2),

                'decision_progress' =>
                    round($decisionProgressComponent, 2),

                'closure_progress' =>
                    round($closureProgressComponent, 2),

                'decision_consistency' =>
                    round($consistencyComponent, 2),

                'governance_integrity' =>
                    round($governanceIntegrityComponent, 2),

                'base_efficiency_score' =>
                    round($baseEfficiencyScore, 2),

                'friction_adjustment' =>
                    round($frictionAdjustment, 2),

                'final_efficiency_score' =>
                    $efficiencyScore,
            ],

            'efficiency_signals' =>
                $signals,

            'source_operational_context' => [
                'operational_health' =>
                    $operationalState['operational_state']['operational_health']
                    ?? null,

                'attention_level' =>
                    $operationalState['operational_state']['attention_level']
                    ?? null,

                'attention_score' =>
                    $operationalState['operational_state']['attention_score']
                    ?? null,

                'escalation_level' =>
                    $escalationLevel,

                'escalation_score' =>
                    $escalationScore,

                'bottleneck_level' =>
                    $bottleneckLevel,

                'bottleneck_score' =>
                    $bottleneckScore,

                'dominant_bottleneck' =>
                    $bottleneck['bottleneck_state']['dominant_bottleneck']
                    ?? null,
            ],

            'management_priorities' =>
                $managementPriorities,

            'throughput_findings' =>
                $findings,

            'throughput_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function percentage(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(
            ($value / $total) * 100,
            2
        );
    }

    private function guardrails(): array
    {
        return [
            'governance_throughput_efficiency_intelligence_enabled' =>
                true,

            'efficiency_score_is_human_performance_rating' =>
                false,

            'efficiency_score_is_governance_decision' =>
                false,

            'efficiency_score_changes_priority' =>
                false,

            'efficiency_score_changes_eligibility' =>
                false,

            'efficiency_score_authorizes_execution' =>
                false,

            'speed_overrides_governance_controls' =>
                false,

            'closure_target_overrides_evidence_requirements' =>
                false,

            'closure_target_overrides_human_review' =>
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
                'Governance operational throughput and efficiency intelligence measures governed workflow progression, decision completion, closure, consistency, and operational friction for management oversight only. Efficiency must never be improved by bypassing evidence requirements, human review, governance validation, safety controls, or authority separation.',
        ];
    }
}