<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionRiskIntelligenceEngine
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

        $correlation = app(
            AIGovernanceDecisionOutcomeCorrelationEngine::class
        )->analyze($snapshot->id);

        $consistency = app(
            AIGovernanceDecisionConsistencyIntelligenceEngine::class
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

        if (!($correlation['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_OUTCOME_CORRELATION_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($consistency['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'DECISION_CONSISTENCY_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->where('review_status', 'COMPLETED')
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Current Action Risk Context
        |--------------------------------------------------------------------------
        */

        $activeActions = $actions->filter(
            fn ($action) =>
                !in_array(
                    $action->action_status,
                    [
                        'RESOLVED',
                        'CLOSED_REJECTED',
                    ],
                    true
                )
        );

        $pendingReviewActions = $actions->where(
            'action_status',
            'PENDING_REVIEW'
        );

        $evidenceDependentActions = $actions->where(
            'action_status',
            'MORE_EVIDENCE_REQUIRED'
        );

        $deferredActions = $actions->where(
            'action_status',
            'DEFERRED'
        );

        $approvedUnresolvedActions = $actions->where(
            'action_status',
            'APPROVED'
        );

        $highPriorityActiveActions = $activeActions->filter(
            fn ($action) =>
                in_array(
                    $action->priority_level,
                    [
                        'HIGH',
                        'CRITICAL',
                    ],
                    true
                )
        );

        $criticalPriorityActiveActions = $activeActions->where(
            'priority_level',
            'CRITICAL'
        );

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity Risks
        |--------------------------------------------------------------------------
        */

        $contradictionCount = (int) (
            $consistency[
                'consistency_summary'
            ]['contradiction_count']
            ?? 0
        );

        $failedConsistencyChecks = (int) (
            $consistency[
                'consistency_summary'
            ]['failed_checks']
            ?? 0
        );

        $outcomeInconsistencies = (int) (
            $correlation[
                'correlation_summary'
            ]['inconsistent_outcomes']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Autonomous Permission Risk Detection
        |--------------------------------------------------------------------------
        */

        $permissionExceptions = $actions->filter(
            fn ($action) =>
                (bool) $action->automatic_execution_allowed
                ||
                (bool) $action->automatic_change_allowed
                ||
                (bool) $action->automatic_deployment_allowed
                ||
                (bool) $action->automatic_rollback_allowed
                ||
                (bool) $action->automatic_clinical_action_allowed
        );

        /*
        |--------------------------------------------------------------------------
        | Human Governance Control Risk
        |--------------------------------------------------------------------------
        */

        $humanControlExceptions = $actions->filter(
            fn ($action) =>
                !(bool) $action->human_review_required
                ||
                !(bool) $action->governance_validation_required
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Signals
        |--------------------------------------------------------------------------
        */

        $signals = [];

        $signals['critical_active_governance_actions'] = [
            'detected' =>
                $criticalPriorityActiveActions->count() > 0,

            'severity' =>
                'CRITICAL',

            'value' =>
                $criticalPriorityActiveActions->count(),

            'message' =>
                'Critical-priority unresolved governance actions require immediate human governance attention.',
        ];

        $signals['high_priority_active_governance_actions'] = [
            'detected' =>
                $highPriorityActiveActions->count() > 0,

            'severity' =>
                'MODERATE',

            'value' =>
                $highPriorityActiveActions->count(),

            'message' =>
                'High-priority governance actions remain active or unresolved.',
        ];

        $signals['decision_state_contradictions'] = [
            'detected' =>
                $contradictionCount > 0,

            'severity' =>
                'HIGH',

            'value' =>
                $contradictionCount,

            'message' =>
                'Human governance decision states should remain internally consistent.',
        ];

        $signals['decision_outcome_inconsistency'] = [
            'detected' =>
                $outcomeInconsistencies > 0,

            'severity' =>
                'HIGH',

            'value' =>
                $outcomeInconsistencies,

            'message' =>
                'Recorded human decisions should remain aligned with current governance action outcomes.',
        ];

        $signals['failed_consistency_controls'] = [
            'detected' =>
                $failedConsistencyChecks > 0,

            'severity' =>
                'HIGH',

            'value' =>
                $failedConsistencyChecks,

            'message' =>
                'Governance decision consistency controls should not contain failures.',
        ];

        $signals['pending_human_decisions'] = [
            'detected' =>
                $pendingReviewActions->count() > 0,

            'severity' =>
                'ADVISORY',

            'value' =>
                $pendingReviewActions->count(),

            'message' =>
                'Governance actions remain awaiting human decision.',
        ];

        $signals['additional_evidence_required'] = [
            'detected' =>
                $evidenceDependentActions->count() > 0,

            'severity' =>
                'ADVISORY',

            'value' =>
                $evidenceDependentActions->count(),

            'message' =>
                'Some governance decisions remain dependent on additional evidence.',
        ];

        $signals['deferred_governance_decisions'] = [
            'detected' =>
                $deferredActions->count() > 0,

            'severity' =>
                'ADVISORY',

            'value' =>
                $deferredActions->count(),

            'message' =>
                'Deferred governance decisions remain under observation.',
        ];

        $signals['approved_unresolved_actions'] = [
            'detected' =>
                $approvedUnresolvedActions->count() > 0,

            'severity' =>
                'MODERATE',

            'value' =>
                $approvedUnresolvedActions->count(),

            'message' =>
                'Human-approved governance actions remain unresolved.',
        ];

        $signals['automatic_permission_exception'] = [
            'detected' =>
                $permissionExceptions->count() > 0,

            'severity' =>
                'CRITICAL',

            'value' =>
                $permissionExceptions->count(),

            'message' =>
                'Automatic execution, AI change, deployment, rollback, and clinical-action permissions must remain disabled.',
        ];

        $signals['human_governance_control_exception'] = [
            'detected' =>
                $humanControlExceptions->count() > 0,

            'severity' =>
                'CRITICAL',

            'value' =>
                $humanControlExceptions->count(),

            'message' =>
                'Human review and governance validation must remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Evidence Confidence Advisory
        |--------------------------------------------------------------------------
        */

        $patternConfidence =
            $pattern[
                'pattern_summary'
            ]['pattern_confidence']
            ?? 'UNKNOWN';

        $consistencyConfidence =
            $consistency[
                'consistency_summary'
            ]['consistency_confidence']
            ?? 'UNKNOWN';

        $correlationConfidence =
            $correlation[
                'correlation_summary'
            ]['correlation_confidence']
            ?? 'UNKNOWN';

        $limitedEvidence =
            in_array(
                $patternConfidence,
                [
                    'VERY_LIMITED',
                    'LIMITED',
                    'INSUFFICIENT_DATA',
                ],
                true
            )
            ||
            in_array(
                $consistencyConfidence,
                [
                    'VERY_LIMITED',
                    'LIMITED',
                    'INSUFFICIENT_DATA',
                ],
                true
            )
            ||
            in_array(
                $correlationConfidence,
                [
                    'VERY_LIMITED',
                    'LIMITED',
                    'INSUFFICIENT_DATA',
                ],
                true
            );

        $signals['limited_decision_evidence'] = [
            'detected' =>
                $limitedEvidence,

            'severity' =>
                'ADVISORY',

            'value' => [
                'pattern_confidence' =>
                    $patternConfidence,

                'consistency_confidence' =>
                    $consistencyConfidence,

                'correlation_confidence' =>
                    $correlationConfidence,
            ],

            'message' =>
                'Governance decision intelligence remains evidence-sensitive while the human decision sample is small.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Severity Counts
        |--------------------------------------------------------------------------
        */

        $detectedSignals = collect($signals)
            ->filter(
                fn ($signal) =>
                    (bool) ($signal['detected'] ?? false)
            );

        $criticalSignals = $detectedSignals
            ->where('severity', 'CRITICAL')
            ->count();

        $highSignals = $detectedSignals
            ->where('severity', 'HIGH')
            ->count();

        $moderateSignals = $detectedSignals
            ->where('severity', 'MODERATE')
            ->count();

        $advisorySignals = $detectedSignals
            ->where('severity', 'ADVISORY')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Decision Risk Score
        |--------------------------------------------------------------------------
        |
        | Critical = 40
        | High     = 20
        | Moderate = 10
        | Advisory = 3
        |
        | Score is intentionally capped at 100.
        |--------------------------------------------------------------------------
        */

        $riskScore = min(
            100,
            ($criticalSignals * 40)
            +
            ($highSignals * 20)
            +
            ($moderateSignals * 10)
            +
            ($advisorySignals * 3)
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Level
        |--------------------------------------------------------------------------
        */

        $riskLevel = match (true) {
            $criticalSignals > 0 =>
                'CRITICAL',

            $highSignals > 0 =>
                'HIGH',

            $moderateSignals >= 2 =>
                'MODERATE',

            $moderateSignals === 1 =>
                'LOW_WITH_MODERATE_ATTENTION',

            $advisorySignals > 0 =>
                'LOW_WITH_ADVISORIES',

            default =>
                'LOW',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Review Recommendation
        |--------------------------------------------------------------------------
        */

        $humanReviewRecommended =
            $criticalSignals > 0
            ||
            $highSignals > 0
            ||
            $highPriorityActiveActions->count() > 0;

        /*
        |--------------------------------------------------------------------------
        | Highest Risk Action
        |--------------------------------------------------------------------------
        */

        $highestRiskAction = $activeActions
            ->sortByDesc('priority_score')
            ->first();

        $highestRiskActionContext =
            $highestRiskAction
                ? [
                    'action_id' =>
                        $highestRiskAction->id,

                    'action_code' =>
                        $highestRiskAction->action_code,

                    'action_category' =>
                        $highestRiskAction->action_category,

                    'action_status' =>
                        $highestRiskAction->action_status,

                    'priority_level' =>
                        $highestRiskAction->priority_level,

                    'priority_score' =>
                        (int) $highestRiskAction->priority_score,

                    'review_decision' =>
                        $highestRiskAction->review_decision,
                ]
                : null;

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            $actions->count()
                . ' governance action(s) are represented in decision risk intelligence.',

            $reviews->count()
                . ' completed human governance decision(s) contribute to risk analysis.',

            $detectedSignals->count()
                . ' governance decision risk or advisory signal(s) are currently detected.',

            "{$criticalSignals} critical, {$highSignals} high, {$moderateSignals} moderate, and {$advisorySignals} advisory signal(s) are currently detected.",

            "Current governance decision risk score is {$riskScore}.",

            "Current governance decision risk level is {$riskLevel}.",

            $highPriorityActiveActions->count()
                . ' high or critical priority governance action(s) remain active.',

            $pendingReviewActions->count()
                . ' governance action(s) remain pending human review.',

            $evidenceDependentActions->count()
                . ' governance action(s) currently require additional evidence.',

            $deferredActions->count()
                . ' governance action(s) remain deferred.',

            "{$contradictionCount} decision-state contradiction(s) are currently detected.",

            $permissionExceptions->count()
                . ' autonomous-permission exception(s) are currently detected.',
        ];

        if ($limitedEvidence) {
            $findings[] =
                'Governance decision risk interpretation remains preliminary because current decision intelligence confidence is limited.';
        }

        if ($permissionExceptions->isEmpty()) {
            $findings[] =
                'No automatic execution, deployment, rollback, AI-change, or clinical-action permission exception is currently detected.';
        }

        if ($contradictionCount === 0) {
            $findings[] =
                'No explicit human decision-to-state contradiction is currently detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($criticalSignals > 0) {
            $managementPriorities[] =
                'Immediately review critical governance decision risk exceptions.';
        }

        if ($highPriorityActiveActions->count() > 0) {
            $managementPriorities[] =
                'Prioritize unresolved high-priority governance actions for human attention.';
        }

        if ($pendingReviewActions->count() > 0) {
            $managementPriorities[] =
                'Complete pending human governance reviews in descending priority order.';
        }

        if ($evidenceDependentActions->count() > 0) {
            $managementPriorities[] =
                'Collect the additional validated evidence required by evidence-dependent governance decisions.';
        }

        if ($deferredActions->count() > 0) {
            $managementPriorities[] =
                'Maintain deferred governance decisions under observation until reassessment conditions are satisfied.';
        }

        if ($limitedEvidence) {
            $managementPriorities[] =
                'Increase the human governance decision sample before treating decision patterns as mature intelligence.';
        }

        if (empty($managementPriorities)) {
            $managementPriorities[] =
                'Continue routine human-governed decision monitoring.';
        }

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_RISK_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'decision_risk_level' =>
                $riskLevel,

            'decision_risk_score' =>
                $riskScore,

            'risk_summary' => [
                'total_actions' =>
                    $actions->count(),

                'total_human_decisions' =>
                    $reviews->count(),

                'active_actions' =>
                    $activeActions->count(),

                'detected_signal_count' =>
                    $detectedSignals->count(),

                'critical_signals' =>
                    $criticalSignals,

                'high_signals' =>
                    $highSignals,

                'moderate_signals' =>
                    $moderateSignals,

                'advisory_signals' =>
                    $advisorySignals,

                'high_priority_active_actions' =>
                    $highPriorityActiveActions->count(),

                'pending_human_reviews' =>
                    $pendingReviewActions->count(),

                'evidence_dependent_actions' =>
                    $evidenceDependentActions->count(),

                'deferred_actions' =>
                    $deferredActions->count(),

                'approved_unresolved_actions' =>
                    $approvedUnresolvedActions->count(),

                'decision_state_contradictions' =>
                    $contradictionCount,

                'automatic_permission_exceptions' =>
                    $permissionExceptions->count(),

                'human_governance_control_exceptions' =>
                    $humanControlExceptions->count(),
            ],

            'risk_signals' =>
                $signals,

            'highest_risk_active_action' =>
                $highestRiskActionContext,

            'intelligence_confidence' => [
                'pattern_confidence' =>
                    $patternConfidence,

                'consistency_confidence' =>
                    $consistencyConfidence,

                'correlation_confidence' =>
                    $correlationConfidence,

                'evidence_limitation_present' =>
                    $limitedEvidence,
            ],

            'human_review_recommended' =>
                $humanReviewRecommended,

            'risk_findings' =>
                $findings,

            'management_priorities' =>
                $managementPriorities,

            'risk_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_risk_intelligence_enabled' =>
                true,

            'risk_intelligence_is_governance_decision' =>
                false,

            'risk_score_is_automatic_priority_change' =>
                false,

            'risk_intelligence_changes_action_state' =>
                false,

            'risk_intelligence_changes_eligibility' =>
                false,

            'risk_intelligence_triggers_execution' =>
                false,

            'risk_intelligence_triggers_deployment' =>
                false,

            'risk_intelligence_triggers_rollback' =>
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
                'Governance decision risk intelligence identifies governance decision risk and advisory signals for human review only. Risk classification does not approve, reject, defer, resolve, reprioritize, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}