<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceBottleneckIntelligenceEngine
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

        if (
            ($operationalState['analysis_completed'] ?? false) !== true
            || ($escalation['analysis_completed'] ?? false) !== true
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'SOURCE_OPERATIONAL_INTELLIGENCE_UNAVAILABLE',
                'operational_snapshot_id' => $snapshot->id,
                'message' => 'Required operational governance intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Current Actions
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
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        $activeActions = $actions
            ->whereNotIn('action_status', $closedStatuses)
            ->values();

        $pendingReviewActions = $activeActions
            ->where('action_status', 'PENDING_REVIEW')
            ->values();

        $evidenceWaitingActions = $activeActions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->values();

        $deferredActions = $activeActions
            ->where('action_status', 'DEFERRED')
            ->values();

        $approvedUnresolvedActions = $activeActions
            ->filter(
                fn ($action) =>
                    $action->review_decision === 'APPROVE'
                    && !in_array(
                        $action->action_status,
                        $closedStatuses,
                        true
                    )
            )
            ->values();

        $highPriorityActiveActions = $activeActions
            ->filter(
                fn ($action) =>
                    in_array(
                        $action->priority_level,
                        ['HIGH', 'CRITICAL'],
                        true
                    )
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Bottleneck Signals
        |--------------------------------------------------------------------------
        */

        $signals = [];

        $signals['human_review_bottleneck'] = [
            'detected' =>
                $pendingReviewActions->count() > 0,

            'bottleneck_type' =>
                'HUMAN_REVIEW',

            'severity' =>
                $pendingReviewActions->count() >= 5
                    ? 'HIGH'
                    : 'MODERATE',

            'count' =>
                $pendingReviewActions->count(),

            'message' =>
                'Governance work is waiting for explicit human review.',
        ];

        $signals['human_decision_bottleneck'] = [
            'detected' =>
                ((int) $snapshot->pending_human_decisions) > 0,

            'bottleneck_type' =>
                'HUMAN_DECISION',

            'severity' =>
                ((int) $snapshot->pending_human_decisions) >= 5
                    ? 'HIGH'
                    : 'MODERATE',

            'count' =>
                (int) $snapshot->pending_human_decisions,

            'message' =>
                'Governance work remains dependent on pending human decisions.',
        ];

        $signals['evidence_bottleneck'] = [
            'detected' =>
                $evidenceWaitingActions->count() > 0,

            'bottleneck_type' =>
                'EVIDENCE_DEPENDENCY',

            'severity' =>
                $evidenceWaitingActions->contains(
                    fn ($action) =>
                        in_array(
                            $action->priority_level,
                            ['HIGH', 'CRITICAL'],
                            true
                        )
                )
                    ? 'HIGH'
                    : 'MODERATE',

            'count' =>
                $evidenceWaitingActions->count(),

            'message' =>
                'Governance work cannot progress until additional evidence is available.',
        ];

        $signals['deferred_work_bottleneck'] = [
            'detected' =>
                $deferredActions->count() > 0,

            'bottleneck_type' =>
                'DEFERRED_WORK',

            'severity' =>
                'ADVISORY',

            'count' =>
                $deferredActions->count(),

            'message' =>
                'Deferred governance work remains intentionally paused.',
        ];

        $signals['approved_unresolved_bottleneck'] = [
            'detected' =>
                $approvedUnresolvedActions->count() > 0,

            'bottleneck_type' =>
                'APPROVED_UNRESOLVED',

            'severity' =>
                'HIGH',

            'count' =>
                $approvedUnresolvedActions->count(),

            'message' =>
                'Human-approved governance actions remain unresolved.',
        ];

        $signals['high_priority_active_bottleneck'] = [
            'detected' =>
                $highPriorityActiveActions->count() > 0,

            'bottleneck_type' =>
                'HIGH_PRIORITY_ACTIVE_WORK',

            'severity' =>
                $highPriorityActiveActions->contains(
                    fn ($action) =>
                        $action->priority_level === 'CRITICAL'
                )
                    ? 'CRITICAL'
                    : 'HIGH',

            'count' =>
                $highPriorityActiveActions->count(),

            'message' =>
                'High or critical priority governance work remains active.',
        ];

        $signals['closure_throughput_bottleneck'] = [
            'detected' =>
                (float) $snapshot->action_closure_percentage < 50.0
                && $activeActions->count() > 0,

            'bottleneck_type' =>
                'LOW_CLOSURE_THROUGHPUT',

            'severity' =>
                (float) $snapshot->action_closure_percentage < 25.0
                    ? 'HIGH'
                    : 'MODERATE',

            'count' =>
                $activeActions->count(),

            'value' =>
                (float) $snapshot->action_closure_percentage,

            'threshold' =>
                50.0,

            'message' =>
                'Governance action closure throughput remains below the preferred operational threshold.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Detected Bottlenecks
        |--------------------------------------------------------------------------
        */

        $detected = collect($signals)
            ->filter(
                fn (array $signal) =>
                    ($signal['detected'] ?? false) === true
            );

        $criticalCount = $detected
            ->where('severity', 'CRITICAL')
            ->count();

        $highCount = $detected
            ->where('severity', 'HIGH')
            ->count();

        $moderateCount = $detected
            ->where('severity', 'MODERATE')
            ->count();

        $advisoryCount = $detected
            ->where('severity', 'ADVISORY')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Bottleneck Score
        |--------------------------------------------------------------------------
        */

        $bottleneckScore =
            ($criticalCount * 30)
            + ($highCount * 18)
            + ($moderateCount * 8)
            + ($advisoryCount * 3);

        $bottleneckScore = min(
            100,
            $bottleneckScore
        );

        $bottleneckLevel = match (true) {
            $criticalCount > 0 =>
                'CRITICAL_BOTTLENECK',

            $highCount >= 2 =>
                'SIGNIFICANT_BOTTLENECK',

            $highCount === 1 =>
                'ELEVATED_BOTTLENECK',

            $moderateCount >= 3 =>
                'MODERATE_BOTTLENECK',

            $moderateCount > 0 =>
                'LIMITED_BOTTLENECK',

            $advisoryCount > 0 =>
                'ADVISORY_BOTTLENECK',

            default =>
                'NO_MATERIAL_BOTTLENECK',
        };

        /*
        |--------------------------------------------------------------------------
        | Dominant Bottleneck
        |--------------------------------------------------------------------------
        */

        $severityWeight = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
        ];

        $dominant = $detected
            ->sortByDesc(
                fn (array $signal) =>
                    (
                        $severityWeight[
                            $signal['severity']
                        ] ?? 0
                    ) * 100
                    + ($signal['count'] ?? 0)
            )
            ->first();

        $dominantBottleneck = $dominant
            ? [
                'bottleneck_type' =>
                    $dominant['bottleneck_type'],

                'severity' =>
                    $dominant['severity'],

                'count' =>
                    $dominant['count'] ?? 0,

                'message' =>
                    $dominant['message'],
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Bottleneck Action Groups
        |--------------------------------------------------------------------------
        */

        $actionGroups = [
            'pending_human_review' =>
                $this->actionList(
                    $pendingReviewActions
                ),

            'evidence_waiting' =>
                $this->actionList(
                    $evidenceWaitingActions
                ),

            'deferred' =>
                $this->actionList(
                    $deferredActions
                ),

            'approved_unresolved' =>
                $this->actionList(
                    $approvedUnresolvedActions
                ),

            'high_priority_active' =>
                $this->actionList(
                    $highPriorityActiveActions
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($highPriorityActiveActions->count() > 0) {
            $managementPriorities[] =
                'Address unresolved high-priority governance work before lower-priority work where operationally appropriate.';
        }

        if ($evidenceWaitingActions->count() > 0) {
            $managementPriorities[] =
                'Collect the additional validated evidence required by evidence-dependent governance actions.';
        }

        if ($pendingReviewActions->count() > 0) {
            $managementPriorities[] =
                'Complete pending human governance reviews in descending priority order.';
        }

        if ((int) $snapshot->pending_human_decisions > 0) {
            $managementPriorities[] =
                'Complete pending explicit human governance decisions.';
        }

        if ($deferredActions->count() > 0) {
            $managementPriorities[] =
                'Maintain deferred governance actions under controlled observation until reassessment conditions are satisfied.';
        }

        if ((float) $snapshot->action_closure_percentage < 50.0) {
            $managementPriorities[] =
                'Improve governance action closure throughput without weakening human-review or governance controls.';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            count($signals)
                . ' governance bottleneck condition(s) were evaluated.',

            $detected->count()
                . ' governance bottleneck signal(s) are currently detected.',

            "{$criticalCount} critical, {$highCount} high, {$moderateCount} moderate, and {$advisoryCount} advisory bottleneck signal(s) are currently detected.",

            "Current governance bottleneck level is {$bottleneckLevel}.",

            "Current governance bottleneck score is {$bottleneckScore}.",

            "{$pendingReviewActions->count()} action(s) remain pending human review.",

            ((int) $snapshot->pending_human_decisions)
                . ' action(s) remain pending explicit human decision.',

            "{$evidenceWaitingActions->count()} action(s) remain evidence-dependent.",

            "{$deferredActions->count()} action(s) remain deferred.",

            "{$highPriorityActiveActions->count()} high or critical priority action(s) remain active.",

            "Current governance action closure is {$snapshot->action_closure_percentage}%.",
        ];

        if ($dominantBottleneck !== null) {
            $findings[] =
                "Current dominant governance bottleneck is {$dominantBottleneck['bottleneck_type']} with {$dominantBottleneck['severity']} severity.";
        }

        if ($criticalCount === 0) {
            $findings[] =
                'No critical governance bottleneck is currently detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_BOTTLENECK_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'bottleneck_state' => [
                'bottleneck_level' =>
                    $bottleneckLevel,

                'bottleneck_score' =>
                    $bottleneckScore,

                'detected_bottlenecks' =>
                    $detected->count(),

                'dominant_bottleneck' =>
                    $dominantBottleneck,

                'human_intervention_required' =>
                    $detected->count() > 0,
            ],

            'bottleneck_summary' => [
                'total_conditions_evaluated' =>
                    count($signals),

                'critical_bottlenecks' =>
                    $criticalCount,

                'high_bottlenecks' =>
                    $highCount,

                'moderate_bottlenecks' =>
                    $moderateCount,

                'advisory_bottlenecks' =>
                    $advisoryCount,
            ],

            'bottleneck_signals' =>
                $signals,

            'action_groups' =>
                $actionGroups,

            'operational_context' => [
                'active_actions' =>
                    (int) $snapshot->active_governance_actions,

                'closed_actions' =>
                    (int) $snapshot->closed_governance_actions,

                'pending_human_reviews' =>
                    (int) $snapshot->pending_human_reviews,

                'pending_human_decisions' =>
                    (int) $snapshot->pending_human_decisions,

                'evidence_waiting_actions' =>
                    (int) $snapshot->evidence_waiting_actions,

                'deferred_actions' =>
                    (int) $snapshot->deferred_actions,

                'high_priority_active_actions' =>
                    (int) $snapshot->high_priority_active_actions,

                'action_closure_percentage' =>
                    (float) $snapshot->action_closure_percentage,

                'attention_level' =>
                    $operationalState['operational_state']['attention_level']
                    ?? null,

                'attention_score' =>
                    $operationalState['operational_state']['attention_score']
                    ?? null,

                'escalation_level' =>
                    $escalation['escalation_state']['escalation_level']
                    ?? null,

                'escalation_score' =>
                    $escalation['escalation_state']['escalation_score']
                    ?? null,
            ],

            'management_priorities' =>
                $managementPriorities,

            'bottleneck_findings' =>
                $findings,

            'bottleneck_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function actionList($actions): array
    {
        return $actions
            ->map(
                fn ($action) => [
                    'action_id' =>
                        $action->id,

                    'action_code' =>
                        $action->action_code,

                    'action_category' =>
                        $action->action_category,

                    'action_status' =>
                        $action->action_status,

                    'eligibility_status' =>
                        $action->eligibility_status,

                    'priority_level' =>
                        $action->priority_level,

                    'priority_score' =>
                        (int) $action->priority_score,

                    'review_decision' =>
                        $action->review_decision,
                ]
            )
            ->values()
            ->all();
    }

    private function guardrails(): array
    {
        return [
            'governance_bottleneck_intelligence_enabled' =>
                true,

            'bottleneck_detection_is_governance_decision' =>
                false,

            'bottleneck_detection_is_action_resolution' =>
                false,

            'bottleneck_detection_changes_priority' =>
                false,

            'bottleneck_detection_changes_eligibility' =>
                false,

            'bottleneck_detection_executes_work' =>
                false,

            'human_intervention_required_is_execution_authorization' =>
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
                'Governance bottleneck intelligence identifies where human-governed work is delayed, waiting, deferred, evidence-dependent, or unresolved. Bottleneck intelligence does not approve, reject, defer, resolve, reprioritize, change eligibility, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}