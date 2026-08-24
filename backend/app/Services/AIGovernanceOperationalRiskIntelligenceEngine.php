<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceOperationalRiskIntelligenceEngine
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

        if (
            ($operationalState['analysis_completed'] ?? false) !== true
            || ($escalation['analysis_completed'] ?? false) !== true
            || ($bottleneck['analysis_completed'] ?? false) !== true
            || ($throughput['analysis_completed'] ?? false) !== true
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
        | Action Population
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

        $criticalActiveActions = $activeActions
            ->where('priority_level', 'CRITICAL')
            ->values();

        $highActiveActions = $activeActions
            ->where('priority_level', 'HIGH')
            ->values();

        $pendingReviewActions = $activeActions
            ->where('action_status', 'PENDING_REVIEW')
            ->values();

        $evidenceDependentActions = $activeActions
            ->where('action_status', 'MORE_EVIDENCE_REQUIRED')
            ->values();

        $deferredActions = $activeActions
            ->where('action_status', 'DEFERRED')
            ->values();

        $approvedUnresolvedActions = $activeActions
            ->where('action_status', 'APPROVED')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Governance Control Integrity
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

        /*
        |--------------------------------------------------------------------------
        | Source Values
        |--------------------------------------------------------------------------
        */

        $attentionLevel =
            $operationalState['operational_state']['attention_level']
            ?? 'UNKNOWN';

        $attentionScore =
            (float) (
                $operationalState['operational_state']['attention_score']
                ?? 0
            );

        $operationalHealth =
            $operationalState['operational_state']['operational_health']
            ?? 'UNKNOWN';

        $operationalMaturity =
            $operationalState['operational_state']['operational_maturity']
            ?? 'UNKNOWN';

        $operationalMaturityScore =
            (float) (
                $operationalState['operational_state']['operational_maturity_score']
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

        $immediateEscalationRequired =
            (bool) (
                $escalation['escalation_state']['immediate_escalation_required']
                ?? false
            );

        $bottleneckLevel =
            $bottleneck['bottleneck_state']['bottleneck_level']
            ?? 'UNKNOWN';

        $bottleneckScore =
            (float) (
                $bottleneck['bottleneck_state']['bottleneck_score']
                ?? 0
            );

        $dominantBottleneck =
            $bottleneck['bottleneck_state']['dominant_bottleneck']
            ?? null;

        $throughputStatus =
            $throughput['throughput_state']['throughput_status']
            ?? 'UNKNOWN';

        $efficiencyStatus =
            $throughput['throughput_state']['efficiency_status']
            ?? 'UNKNOWN';

        $efficiencyScore =
            (float) (
                $throughput['throughput_state']['efficiency_score']
                ?? 0
            );

        $processBalanceStatus =
            $throughput['throughput_state']['process_balance_status']
            ?? 'UNKNOWN';

        $governanceIntegrityIntact =
            (bool) (
                $throughput['throughput_state']['governance_integrity_intact']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Risk Signals
        |--------------------------------------------------------------------------
        */

        $riskSignals = [
            'critical_priority_active_work' => [
                'detected' =>
                    $criticalActiveActions->count() > 0,

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $criticalActiveActions->count(),

                'message' =>
                    'Critical-priority governance work remains active or unresolved.',
            ],

            'high_priority_active_work' => [
                'detected' =>
                    $highActiveActions->count() > 0,

                'severity' =>
                    'HIGH',

                'value' =>
                    $highActiveActions->count(),

                'message' =>
                    'High-priority governance work remains active or unresolved.',
            ],

            'immediate_escalation_required' => [
                'detected' =>
                    $immediateEscalationRequired,

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $immediateEscalationRequired,

                'message' =>
                    'Operational governance intelligence indicates immediate escalation is required.',
            ],

            'elevated_escalation_pressure' => [
                'detected' =>
                    $escalationScore >= 60,

                'severity' =>
                    'MODERATE',

                'value' => [
                    'escalation_level' =>
                        $escalationLevel,

                    'escalation_score' =>
                        $escalationScore,
                ],

                'message' =>
                    'Operational governance escalation pressure remains elevated.',
            ],

            'significant_bottleneck' => [
                'detected' =>
                    in_array(
                        $bottleneckLevel,
                        [
                            'SIGNIFICANT_BOTTLENECK',
                            'SEVERE_BOTTLENECK',
                            'CRITICAL_BOTTLENECK',
                        ],
                        true
                    ),

                'severity' =>
                    'HIGH',

                'value' => [
                    'bottleneck_level' =>
                        $bottleneckLevel,

                    'bottleneck_score' =>
                        $bottleneckScore,

                    'dominant_bottleneck' =>
                        $dominantBottleneck,
                ],

                'message' =>
                    'Significant governance bottleneck pressure is currently present.',
            ],

            'low_operational_efficiency' => [
                'detected' =>
                    $efficiencyScore < 55,

                'severity' =>
                    'MODERATE',

                'value' => [
                    'efficiency_status' =>
                        $efficiencyStatus,

                    'efficiency_score' =>
                        $efficiencyScore,
                ],

                'message' =>
                    'Governance operational efficiency remains below the preferred developing threshold.',
            ],

            'developing_operational_efficiency' => [
                'detected' =>
                    $efficiencyScore >= 55
                    && $efficiencyScore < 70,

                'severity' =>
                    'ADVISORY',

                'value' => [
                    'efficiency_status' =>
                        $efficiencyStatus,

                    'efficiency_score' =>
                        $efficiencyScore,
                ],

                'message' =>
                    'Governance operational efficiency remains developing and should continue improving under existing controls.',
            ],

            'process_imbalance' => [
                'detected' =>
                    $processBalanceStatus === 'IMBALANCED',

                'severity' =>
                    'MODERATE',

                'value' =>
                    $processBalanceStatus,

                'message' =>
                    'Governance review, decision, and closure progression remain materially imbalanced.',
            ],

            'pending_human_review' => [
                'detected' =>
                    $pendingReviewActions->count() > 0,

                'severity' =>
                    'ADVISORY',

                'value' =>
                    $pendingReviewActions->count(),

                'message' =>
                    'Governance actions remain pending human review.',
            ],

            'evidence_dependency' => [
                'detected' =>
                    $evidenceDependentActions->count() > 0,

                'severity' =>
                    'MODERATE',

                'value' =>
                    $evidenceDependentActions->count(),

                'message' =>
                    'Governance actions remain unable to progress until additional evidence becomes available.',
            ],

            'deferred_governance_work' => [
                'detected' =>
                    $deferredActions->count() > 0,

                'severity' =>
                    'ADVISORY',

                'value' =>
                    $deferredActions->count(),

                'message' =>
                    'Deferred governance work remains under controlled observation.',
            ],

            'approved_unresolved_work' => [
                'detected' =>
                    $approvedUnresolvedActions->count() > 0,

                'severity' =>
                    'HIGH',

                'value' =>
                    $approvedUnresolvedActions->count(),

                'message' =>
                    'Human-approved governance work remains unresolved.',
            ],

            'automatic_permission_exception' => [
                'detected' =>
                    $automaticPermissionExceptions > 0,

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $automaticPermissionExceptions,

                'message' =>
                    'Automatic execution, AI modification, deployment, rollback, or clinical-action permissions must remain disabled.',
            ],

            'human_governance_control_exception' => [
                'detected' =>
                    $humanGovernanceExceptions > 0,

                'severity' =>
                    'CRITICAL',

                'value' =>
                    $humanGovernanceExceptions,

                'message' =>
                    'Human review and governance validation requirements must remain enabled.',
            ],

            'decision_consistency_degradation' => [
                'detected' =>
                    !in_array(
                        $snapshot->decision_consistency_status,
                        [
                            'HIGHLY_CONSISTENT',
                            'CONSISTENT',
                        ],
                        true
                    ),

                'severity' =>
                    'HIGH',

                'value' => [
                    'consistency_status' =>
                        $snapshot->decision_consistency_status,

                    'consistency_score' =>
                        (float) $snapshot->decision_consistency_score,
                ],

                'message' =>
                    'Human governance decision consistency has fallen outside the expected controlled state.',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Detected Signals
        |--------------------------------------------------------------------------
        */

        $detectedSignals = collect($riskSignals)
            ->filter(
                fn ($signal) =>
                    ($signal['detected'] ?? false) === true
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
        | Risk Score
        |--------------------------------------------------------------------------
        |
        | Weighted by severity.
        |
        | CRITICAL = 25
        | HIGH     = 15
        | MODERATE = 7
        | ADVISORY = 3
        |--------------------------------------------------------------------------
        */

        $riskScore =
            ($criticalSignals * 25)
            + ($highSignals * 15)
            + ($moderateSignals * 7)
            + ($advisorySignals * 3);

        $riskScore = min(
            100,
            $riskScore
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Classification
        |--------------------------------------------------------------------------
        */

        $riskLevel = match (true) {
            $criticalSignals > 0 =>
                'CRITICAL_OPERATIONAL_RISK',

            $riskScore >= 70 =>
                'HIGH_OPERATIONAL_RISK',

            $riskScore >= 45 =>
                'MODERATE_OPERATIONAL_RISK',

            $riskScore >= 20 =>
                'LOW_WITH_OPERATIONAL_ATTENTION',

            $riskScore > 0 =>
                'LOW_WITH_ADVISORIES',

            default =>
                'CONTROLLED_LOW_RISK',
        };

        /*
        |--------------------------------------------------------------------------
        | Risk Control Status
        |--------------------------------------------------------------------------
        */

        $riskControlStatus = match (true) {
            $automaticPermissionExceptions > 0 =>
                'AUTOMATIC_AUTHORITY_EXCEPTION',

            $humanGovernanceExceptions > 0 =>
                'HUMAN_GOVERNANCE_CONTROL_EXCEPTION',

            !$governanceIntegrityIntact =>
                'GOVERNANCE_INTEGRITY_EXCEPTION',

            $criticalSignals > 0 =>
                'CRITICAL_HUMAN_INTERVENTION_REQUIRED',

            $highSignals > 0 =>
                'CONTROLLED_WITH_HIGH_ATTENTION',

            $moderateSignals > 0 =>
                'CONTROLLED_WITH_MODERATE_ATTENTION',

            default =>
                'CONTROLLED',
        };

        /*
        |--------------------------------------------------------------------------
        | Highest Risk Action
        |--------------------------------------------------------------------------
        */

        $highestRiskAction = $activeActions
            ->sortByDesc('priority_score')
            ->first();

        $highestRiskActionPayload = $highestRiskAction
            ? [
                'action_id' =>
                    $highestRiskAction->id,

                'action_code' =>
                    $highestRiskAction->action_code,

                'action_category' =>
                    $highestRiskAction->action_category,

                'action_status' =>
                    $highestRiskAction->action_status,

                'eligibility_status' =>
                    $highestRiskAction->eligibility_status,

                'priority_level' =>
                    $highestRiskAction->priority_level,

                'priority_score' =>
                    (int) $highestRiskAction->priority_score,

                'review_decision' =>
                    $highestRiskAction->review_decision,

                'automatic_execution_allowed' =>
                    (bool) $highestRiskAction->automatic_execution_allowed,

                'automatic_change_allowed' =>
                    (bool) $highestRiskAction->automatic_change_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $highestRiskAction->automatic_deployment_allowed,

                'automatic_rollback_allowed' =>
                    (bool) $highestRiskAction->automatic_rollback_allowed,

                'automatic_clinical_action_allowed' =>
                    (bool) $highestRiskAction->automatic_clinical_action_allowed,
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($criticalActiveActions->count() > 0) {
            $managementPriorities[] =
                'Escalate critical-priority unresolved governance work for immediate human governance review.';
        }

        if ($highActiveActions->count() > 0) {
            $managementPriorities[] =
                'Prioritize unresolved high-priority governance work before lower-priority operational work.';
        }

        if ($evidenceDependentActions->count() > 0) {
            $managementPriorities[] =
                'Collect the additional validated evidence required by evidence-dependent governance actions.';
        }

        if ($pendingReviewActions->count() > 0) {
            $managementPriorities[] =
                'Complete pending human governance reviews in descending priority order.';
        }

        if ($processBalanceStatus === 'IMBALANCED') {
            $managementPriorities[] =
                'Reduce the operational gap between governance decision completion and formal action closure.';
        }

        if ($bottleneckLevel === 'SIGNIFICANT_BOTTLENECK') {
            $managementPriorities[] =
                'Address the dominant governance bottleneck before materially expanding governance workload.';
        }

        if ($deferredActions->count() > 0) {
            $managementPriorities[] =
                'Maintain deferred governance actions under controlled observation until reassessment criteria are satisfied.';
        }

        if ($automaticPermissionExceptions > 0) {
            $managementPriorities[] =
                'Immediately remove any automatic execution, modification, deployment, rollback, or clinical-action permission exception.';
        }

        if ($humanGovernanceExceptions > 0) {
            $managementPriorities[] =
                'Immediately restore mandatory human review and governance validation controls.';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "{$actions->count()} governance action(s) are represented in operational risk intelligence.",

            "{$activeActions->count()} governance action(s) remain active.",

            "{$detectedSignals->count()} operational governance risk or advisory signal(s) are currently detected.",

            "{$criticalSignals} critical, {$highSignals} high, {$moderateSignals} moderate, and {$advisorySignals} advisory signal(s) are currently detected.",

            "Current governance operational risk score is {$riskScore}.",

            "Current governance operational risk level is {$riskLevel}.",

            "Current risk control status is {$riskControlStatus}.",

            "Operational health is {$operationalHealth}.",

            "Operational attention level is {$attentionLevel} with score {$attentionScore}.",

            "Operational escalation level is {$escalationLevel} with score {$escalationScore}.",

            "Governance bottleneck level is {$bottleneckLevel} with score {$bottleneckScore}.",

            "Governance throughput status is {$throughputStatus}.",

            "Operational efficiency status is {$efficiencyStatus} with score {$efficiencyScore}.",

            "Process balance status is {$processBalanceStatus}.",

            "{$highActiveActions->count()} high-priority governance action(s) remain active.",

            "{$pendingReviewActions->count()} governance action(s) remain pending human review.",

            "{$evidenceDependentActions->count()} governance action(s) remain evidence-dependent.",

            "{$deferredActions->count()} governance action(s) remain deferred.",

            "{$approvedUnresolvedActions->count()} approved governance action(s) remain unresolved.",

            "{$automaticPermissionExceptions} automatic-permission exception(s) are currently detected.",

            "{$humanGovernanceExceptions} human-governance control exception(s) are currently detected.",
        ];

        if ($governanceIntegrityIntact) {
            $findings[] =
                'Governance control integrity remains intact.';
        }

        if ($criticalSignals === 0) {
            $findings[] =
                'No critical operational governance risk signal is currently detected.';
        }

        if ($automaticPermissionExceptions === 0) {
            $findings[] =
                'No autonomous execution, AI modification, deployment, rollback, or clinical-action permission exception is currently detected.';
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
                'GOVERNANCE_OPERATIONAL_RISK_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'operational_risk_state' => [
                'operational_risk_level' =>
                    $riskLevel,

                'operational_risk_score' =>
                    $riskScore,

                'risk_control_status' =>
                    $riskControlStatus,

                'human_review_recommended' =>
                    $detectedSignals->count() > 0,

                'immediate_human_intervention_required' =>
                    $criticalSignals > 0,

                'governance_integrity_intact' =>
                    $governanceIntegrityIntact,
            ],

            'risk_summary' => [
                'total_actions' =>
                    $actions->count(),

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

                'critical_priority_active_actions' =>
                    $criticalActiveActions->count(),

                'high_priority_active_actions' =>
                    $highActiveActions->count(),

                'pending_human_reviews' =>
                    $pendingReviewActions->count(),

                'evidence_dependent_actions' =>
                    $evidenceDependentActions->count(),

                'deferred_actions' =>
                    $deferredActions->count(),

                'approved_unresolved_actions' =>
                    $approvedUnresolvedActions->count(),

                'automatic_permission_exceptions' =>
                    $automaticPermissionExceptions,

                'human_governance_control_exceptions' =>
                    $humanGovernanceExceptions,
            ],

            'risk_signals' =>
                $riskSignals,

            'highest_risk_active_action' =>
                $highestRiskActionPayload,

            'operational_context' => [
                'operational_health' =>
                    $operationalHealth,

                'attention_level' =>
                    $attentionLevel,

                'attention_score' =>
                    $attentionScore,

                'operational_maturity' =>
                    $operationalMaturity,

                'operational_maturity_score' =>
                    $operationalMaturityScore,

                'escalation_level' =>
                    $escalationLevel,

                'escalation_score' =>
                    $escalationScore,

                'bottleneck_level' =>
                    $bottleneckLevel,

                'bottleneck_score' =>
                    $bottleneckScore,

                'dominant_bottleneck' =>
                    $dominantBottleneck,

                'throughput_status' =>
                    $throughputStatus,

                'efficiency_status' =>
                    $efficiencyStatus,

                'efficiency_score' =>
                    $efficiencyScore,

                'process_balance_status' =>
                    $processBalanceStatus,

                'decision_consistency_status' =>
                    $snapshot->decision_consistency_status,

                'decision_consistency_score' =>
                    (float) $snapshot->decision_consistency_score,
            ],

            'management_priorities' =>
                $managementPriorities,

            'risk_findings' =>
                $findings,

            'risk_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'governance_operational_risk_intelligence_enabled' =>
                true,

            'operational_risk_is_governance_decision' =>
                false,

            'operational_risk_changes_action_state' =>
                false,

            'operational_risk_changes_priority' =>
                false,

            'operational_risk_changes_eligibility' =>
                false,

            'operational_risk_triggers_execution' =>
                false,

            'operational_risk_triggers_deployment' =>
                false,

            'operational_risk_triggers_rollback' =>
                false,

            'operational_risk_triggers_clinical_action' =>
                false,

            'risk_score_is_human_performance_rating' =>
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
                'Governance operational risk intelligence consolidates unresolved work, human attention pressure, escalation, bottlenecks, throughput, evidence dependencies, and governance-control integrity for human operational oversight only. Risk intelligence does not make governance decisions, resolve actions, alter priority or eligibility, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}