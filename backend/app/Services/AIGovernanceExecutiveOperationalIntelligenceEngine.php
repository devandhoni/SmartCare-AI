<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceExecutiveOperationalIntelligenceEngine
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

        $throughput = app(
            AIGovernanceOperationalThroughputEfficiencyIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIGovernanceOperationalRiskIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (
            ($operationalState['analysis_completed'] ?? false) !== true
            || ($escalation['analysis_completed'] ?? false) !== true
            || ($bottleneck['analysis_completed'] ?? false) !== true
            || ($throughput['analysis_completed'] ?? false) !== true
            || ($risk['analysis_completed'] ?? false) !== true
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
        | Action Context
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

        $closedActions = $actions
            ->whereIn('action_status', $closedStatuses)
            ->values();

        $pendingReviews = $activeActions
            ->where('action_status', 'PENDING_REVIEW')
            ->values();

        $evidenceDependent = $activeActions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->values();

        $deferredActions = $activeActions
            ->where('action_status', 'DEFERRED')
            ->values();

        $highPriorityActive = $activeActions
            ->whereIn('priority_level', ['HIGH', 'CRITICAL'])
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Source Values
        |--------------------------------------------------------------------------
        */

        $operationalStateData =
            $operationalState['operational_state'] ?? [];

        $operationalHealth =
            $operationalStateData['operational_health']
            ?? 'UNKNOWN';

        $humanGovernanceState =
            $operationalStateData['human_governance_state']
            ?? 'UNKNOWN';

        $attentionLevel =
            $operationalStateData['attention_level']
            ?? 'UNKNOWN';

        $attentionScore =
            (float) (
                $operationalStateData['attention_score']
                ?? 0
            );

        $operationalMaturity =
            $operationalStateData['operational_maturity']
            ?? 'UNKNOWN';

        $operationalMaturityScore =
            (float) (
                $operationalStateData['operational_maturity_score']
                ?? 0
            );

        $operationalConfidence =
            $operationalStateData['operational_confidence']
            ?? 'UNKNOWN';

        $escalationState =
            $escalation['escalation_state'] ?? [];

        $escalationLevel =
            $escalationState['escalation_level']
            ?? 'UNKNOWN';

        $escalationScore =
            (float) (
                $escalationState['escalation_score']
                ?? 0
            );

        $managementEscalationRecommended =
            (bool) (
                $escalationState['management_escalation_recommended']
                ?? false
            );

        $immediateEscalationRequired =
            (bool) (
                $escalationState['immediate_escalation_required']
                ?? false
            );

        $bottleneckState =
            $bottleneck['bottleneck_state'] ?? [];

        $bottleneckLevel =
            $bottleneckState['bottleneck_level']
            ?? 'UNKNOWN';

        $bottleneckScore =
            (float) (
                $bottleneckState['bottleneck_score']
                ?? 0
            );

        $dominantBottleneck =
            $bottleneckState['dominant_bottleneck']
            ?? null;

        $throughputState =
            $throughput['throughput_state'] ?? [];

        $throughputStatus =
            $throughputState['throughput_status']
            ?? 'UNKNOWN';

        $efficiencyStatus =
            $throughputState['efficiency_status']
            ?? 'UNKNOWN';

        $efficiencyScore =
            (float) (
                $throughputState['efficiency_score']
                ?? 0
            );

        $processBalanceStatus =
            $throughputState['process_balance_status']
            ?? 'UNKNOWN';

        $governanceIntegrityIntact =
            (bool) (
                $throughputState['governance_integrity_intact']
                ?? false
            );

        $riskState =
            $risk['operational_risk_state'] ?? [];

        $operationalRiskLevel =
            $riskState['operational_risk_level']
            ?? 'UNKNOWN';

        $operationalRiskScore =
            (float) (
                $riskState['operational_risk_score']
                ?? 0
            );

        $riskControlStatus =
            $riskState['risk_control_status']
            ?? 'UNKNOWN';

        $riskSummary =
            $risk['risk_summary'] ?? [];

        $criticalRiskSignals =
            (int) (
                $riskSummary['critical_signals']
                ?? 0
            );

        $highRiskSignals =
            (int) (
                $riskSummary['high_signals']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Executive Operational Score
        |--------------------------------------------------------------------------
        |
        | This is an oversight score, not authority.
        |
        | Maturity         25%
        | Efficiency       25%
        | Closure          20%
        | Decision progress 15%
        | Consistency      15%
        |--------------------------------------------------------------------------
        */

        $closurePercentage =
            (float) $snapshot->action_closure_percentage;

        $decisionCompletionPercentage =
            (float) $snapshot->decision_completion_percentage;

        $decisionConsistencyScore =
            (float) $snapshot->decision_consistency_score;

        $baseExecutiveScore = round(
            (
                ($operationalMaturityScore * 0.25)
                + ($efficiencyScore * 0.25)
                + ($closurePercentage * 0.20)
                + ($decisionCompletionPercentage * 0.15)
                + ($decisionConsistencyScore * 0.15)
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Operational Pressure Adjustment
        |--------------------------------------------------------------------------
        |
        | Escalation  40%
        | Bottleneck  35%
        | Risk        25%
        |--------------------------------------------------------------------------
        */

        $pressureScore = round(
            (
                ($escalationScore * 0.40)
                + ($bottleneckScore * 0.35)
                + ($operationalRiskScore * 0.25)
            ),
            2
        );

        $pressureAdjustment = round(
            $pressureScore * 0.10,
            2
        );

        $executiveOperationalScore = round(
            max(
                0,
                min(
                    100,
                    $baseExecutiveScore - $pressureAdjustment
                )
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Executive Status
        |--------------------------------------------------------------------------
        */

        $executiveOperationalStatus = match (true) {
            $criticalRiskSignals > 0
                || $immediateEscalationRequired =>
                    'CRITICAL_GOVERNANCE_ATTENTION_REQUIRED',

            $highRiskSignals > 0
                && $operationalRiskScore >= 70 =>
                    'HIGH_OPERATIONAL_GOVERNANCE_RISK',

            $highPriorityActive->count() > 0 =>
                    'CONTROLLED_PRIORITY_GOVERNANCE_WORK',

            $activeActions->count() > 0 =>
                    'CONTROLLED_OPERATIONAL_WORK_REMAINS',

            default =>
                    'STABLE_OPERATIONAL_GOVERNANCE',
        };

        /*
        |--------------------------------------------------------------------------
        | Executive Readiness
        |--------------------------------------------------------------------------
        */

        $executiveReadiness = match (true) {
            !$governanceIntegrityIntact =>
                'NOT_READY',

            $criticalRiskSignals > 0 =>
                'NOT_READY',

            $executiveOperationalScore >= 85
                && $activeActions->count() === 0 =>
                    'OPERATIONALLY_READY',

            $executiveOperationalScore >= 70 =>
                    'MOSTLY_READY_WITH_GOVERNANCE_WORK',

            $executiveOperationalScore >= 50 =>
                    'PARTIALLY_READY',

            default =>
                    'DEVELOPING',
        };

        /*
        |--------------------------------------------------------------------------
        | Executive Confidence
        |--------------------------------------------------------------------------
        */

        $executiveConfidence = match (true) {
            $actions->count() >= 20
                && $snapshot->decision_completion_percentage >= 90 =>
                    'HIGH',

            $actions->count() >= 10 =>
                    'MODERATE',

            default =>
                    'LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Items
        |--------------------------------------------------------------------------
        */

        $attentionItems = [];

        if ($highPriorityActive->count() > 0) {
            $attentionItems[] =
                "{$highPriorityActive->count()} high or critical priority governance action(s) remain active.";
        }

        if ($pendingReviews->count() > 0) {
            $attentionItems[] =
                "{$pendingReviews->count()} governance action(s) remain pending human review.";
        }

        if ($evidenceDependent->count() > 0) {
            $attentionItems[] =
                "{$evidenceDependent->count()} governance action(s) remain dependent on additional evidence.";
        }

        if ($deferredActions->count() > 0) {
            $attentionItems[] =
                "{$deferredActions->count()} governance action(s) remain deferred.";
        }

        if ($closurePercentage < 50) {
            $attentionItems[] =
                "Governance action closure remains at {$closurePercentage}%, below the preferred operational progression threshold.";
        }

        if ($processBalanceStatus === 'IMBALANCED') {
            $attentionItems[] =
                'Governance review, decision, and formal closure progression remain operationally imbalanced.';
        }

        if ($bottleneckLevel === 'SIGNIFICANT_BOTTLENECK') {
            $attentionItems[] =
                'A significant operational governance bottleneck remains active.';
        }

        if ($managementEscalationRecommended) {
            $attentionItems[] =
                'Management escalation remains recommended for current operational governance conditions.';
        }

        /*
        |--------------------------------------------------------------------------
        | Highest Priority Action
        |--------------------------------------------------------------------------
        */

        $highestPriorityAction = $activeActions
            ->sortByDesc('priority_score')
            ->first();

        $highestPriorityActionPayload = $highestPriorityAction
            ? [
                'action_id' =>
                    $highestPriorityAction->id,

                'action_code' =>
                    $highestPriorityAction->action_code,

                'action_category' =>
                    $highestPriorityAction->action_category,

                'action_status' =>
                    $highestPriorityAction->action_status,

                'eligibility_status' =>
                    $highestPriorityAction->eligibility_status,

                'priority_level' =>
                    $highestPriorityAction->priority_level,

                'priority_score' =>
                    (int) $highestPriorityAction->priority_score,

                'review_decision' =>
                    $highestPriorityAction->review_decision,
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Executive Priorities
        |--------------------------------------------------------------------------
        */

        $executivePriorities = [];

        if ($highPriorityActive->count() > 0) {
            $executivePriorities[] =
                'Prioritize unresolved high-priority governance work for human management attention.';
        }

        if ($evidenceDependent->count() > 0) {
            $executivePriorities[] =
                'Increase validated evidence availability for governance actions that cannot currently progress.';
        }

        if ($pendingReviews->count() > 0) {
            $executivePriorities[] =
                'Complete outstanding human governance reviews in descending priority order.';
        }

        if ($closurePercentage < $decisionCompletionPercentage) {
            $executivePriorities[] =
                'Reduce the gap between completed governance decisions and formal action closure.';
        }

        if ($bottleneckLevel !== 'NO_MATERIAL_BOTTLENECK') {
            $executivePriorities[] =
                'Address material governance bottlenecks before expanding operational governance workload.';
        }

        if ($managementEscalationRecommended) {
            $executivePriorities[] =
                'Maintain elevated management visibility until governance attention pressure decreases.';
        }

        $executivePriorities[] =
            'Preserve human-review, governance-validation, evidence, safety, and authority-separation controls while operational progression improves.';

        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */

        $executiveSummary =
            "Operational governance currently contains "
            . "{$actions->count()} action(s), with "
            . "{$activeActions->count()} active and "
            . "{$closedActions->count()} closed. "
            . "Operational health is {$operationalHealth}, "
            . "attention is {$attentionLevel} with score {$attentionScore}, "
            . "escalation is {$escalationLevel} with score {$escalationScore}, "
            . "and bottleneck status is {$bottleneckLevel} with score {$bottleneckScore}. "
            . "Governance throughput is {$throughputStatus} with "
            . "{$efficiencyStatus} at efficiency score {$efficiencyScore}. "
            . "Operational risk is {$operationalRiskLevel} with risk score {$operationalRiskScore}. "
            . "Executive operational status is {$executiveOperationalStatus} "
            . "with readiness {$executiveReadiness}.";

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Executive operational governance intelligence currently represents {$actions->count()} governance action(s).",

            "{$activeActions->count()} governance action(s) remain active and {$closedActions->count()} are closed.",

            "Current executive operational status is {$executiveOperationalStatus}.",

            "Current executive operational readiness is {$executiveReadiness}.",

            "Current executive operational score is {$executiveOperationalScore}.",

            "Current executive confidence is {$executiveConfidence}.",

            "Operational health is {$operationalHealth}.",

            "Human governance state is {$humanGovernanceState}.",

            "Operational maturity is {$operationalMaturity} with score {$operationalMaturityScore}.",

            "Operational attention is {$attentionLevel} with score {$attentionScore}.",

            "Operational escalation is {$escalationLevel} with score {$escalationScore}.",

            "Operational bottleneck classification is {$bottleneckLevel} with score {$bottleneckScore}.",

            "Governance throughput classification is {$throughputStatus}.",

            "Operational efficiency classification is {$efficiencyStatus} with score {$efficiencyScore}.",

            "Process balance classification is {$processBalanceStatus}.",

            "Operational governance risk is {$operationalRiskLevel} with score {$operationalRiskScore}.",

            "Governance action closure is {$closurePercentage}%.",

            "Governance decision completion is {$decisionCompletionPercentage}%.",

            "Governance decision consistency is {$snapshot->decision_consistency_status} with score {$decisionConsistencyScore}.",
        ];

        if ($governanceIntegrityIntact) {
            $findings[] =
                'Operational governance integrity remains intact.';
        }

        if ($criticalRiskSignals === 0) {
            $findings[] =
                'No critical operational governance risk signal is currently detected.';
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
                'GOVERNANCE_EXECUTIVE_OPERATIONAL_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'executive_operational_state' => [
                'executive_operational_status' =>
                    $executiveOperationalStatus,

                'executive_readiness' =>
                    $executiveReadiness,

                'executive_confidence' =>
                    $executiveConfidence,

                'executive_operational_score' =>
                    $executiveOperationalScore,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,

                'management_escalation_recommended' =>
                    $managementEscalationRecommended,

                'immediate_escalation_required' =>
                    $immediateEscalationRequired,
            ],

            'executive_summary' =>
                $executiveSummary,

            'portfolio_context' => [
                'total_actions' =>
                    $actions->count(),

                'active_actions' =>
                    $activeActions->count(),

                'closed_actions' =>
                    $closedActions->count(),

                'pending_human_reviews' =>
                    $pendingReviews->count(),

                'evidence_dependent_actions' =>
                    $evidenceDependent->count(),

                'deferred_actions' =>
                    $deferredActions->count(),

                'high_priority_active_actions' =>
                    $highPriorityActive->count(),

                'action_closure_percentage' =>
                    $closurePercentage,

                'decision_completion_percentage' =>
                    $decisionCompletionPercentage,
            ],

            'operational_context' => [
                'operational_health' =>
                    $operationalHealth,

                'human_governance_state' =>
                    $humanGovernanceState,

                'operational_maturity' =>
                    $operationalMaturity,

                'operational_maturity_score' =>
                    $operationalMaturityScore,

                'operational_confidence' =>
                    $operationalConfidence,

                'attention_level' =>
                    $attentionLevel,

                'attention_score' =>
                    $attentionScore,
            ],

            'escalation_context' => [
                'escalation_level' =>
                    $escalationLevel,

                'escalation_score' =>
                    $escalationScore,

                'management_escalation_recommended' =>
                    $managementEscalationRecommended,

                'immediate_escalation_required' =>
                    $immediateEscalationRequired,
            ],

            'bottleneck_context' => [
                'bottleneck_level' =>
                    $bottleneckLevel,

                'bottleneck_score' =>
                    $bottleneckScore,

                'dominant_bottleneck' =>
                    $dominantBottleneck,
            ],

            'throughput_context' => [
                'throughput_status' =>
                    $throughputStatus,

                'efficiency_status' =>
                    $efficiencyStatus,

                'efficiency_score' =>
                    $efficiencyScore,

                'process_balance_status' =>
                    $processBalanceStatus,
            ],

            'risk_context' => [
                'operational_risk_level' =>
                    $operationalRiskLevel,

                'operational_risk_score' =>
                    $operationalRiskScore,

                'risk_control_status' =>
                    $riskControlStatus,

                'critical_signals' =>
                    $criticalRiskSignals,

                'high_signals' =>
                    $highRiskSignals,
            ],

            'score_components' => [
                'operational_maturity' =>
                    $operationalMaturityScore,

                'operational_efficiency' =>
                    $efficiencyScore,

                'action_closure' =>
                    $closurePercentage,

                'decision_completion' =>
                    $decisionCompletionPercentage,

                'decision_consistency' =>
                    $decisionConsistencyScore,

                'base_executive_score' =>
                    $baseExecutiveScore,

                'operational_pressure_score' =>
                    $pressureScore,

                'pressure_adjustment' =>
                    $pressureAdjustment,

                'executive_operational_score' =>
                    $executiveOperationalScore,
            ],

            'highest_priority_active_action' =>
                $highestPriorityActionPayload,

            'executive_attention_items' =>
                $attentionItems,

            'executive_priorities' =>
                $executivePriorities,

            'executive_findings' =>
                $findings,

            'executive_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'executive_operational_intelligence_enabled' =>
                true,

            'executive_operational_status_is_governance_decision' =>
                false,

            'executive_readiness_is_execution_authorization' =>
                false,

            'executive_score_is_human_performance_rating' =>
                false,

            'executive_intelligence_changes_action_state' =>
                false,

            'executive_intelligence_changes_priority' =>
                false,

            'executive_intelligence_changes_eligibility' =>
                false,

            'management_escalation_is_automatic_notification' =>
                false,

            'executive_intelligence_authorizes_execution' =>
                false,

            'executive_intelligence_authorizes_deployment' =>
                false,

            'executive_intelligence_authorizes_rollback' =>
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
                'Executive operational governance intelligence consolidates governance workload, operational state, attention, escalation, bottlenecks, throughput, efficiency, risk, and management priorities for executive oversight only. Executive status, readiness, scores, and recommendations do not make governance decisions, change action state, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}