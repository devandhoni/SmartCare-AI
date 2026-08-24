<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceOperationalFinalValidationEngine
{
    public function analyze(?int $operationalSnapshotId = null): array
    {
        $snapshot = $operationalSnapshotId !== null
            ? AIGovernanceOperationalSnapshot::find($operationalSnapshotId)
            : AIGovernanceOperationalSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'validation_status' => 'FAILED',
                'step_61_ready_for_closure' => false,
                'operational_snapshot_id' => $operationalSnapshotId,
                'message' => 'Governance operational snapshot was not found.',
            ];
        }

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

        $executive = app(
            AIGovernanceExecutiveOperationalIntelligenceEngine::class
        )->analyze($snapshot->id);

        $audit = app(
            AIGovernanceOperationalAuditSummaryEngine::class
        )->analyze($snapshot->id);

        $actions = AIGovernanceAction::query()
            ->where(
                'lifecycle_snapshot_id',
                $snapshot->lifecycle_snapshot_id
            )
            ->get();

        $closedStatuses = [
            'RESOLVED',
            'CLOSED_REJECTED',
        ];

        $activeActions = $actions
            ->whereNotIn('action_status', $closedStatuses);

        $closedActions = $actions
            ->whereIn('action_status', $closedStatuses);

        $operationalStateData =
            $operationalState['operational_state'] ?? [];

        $escalationState =
            $escalation['escalation_state'] ?? [];

        $bottleneckState =
            $bottleneck['bottleneck_state'] ?? [];

        $throughputState =
            $throughput['throughput_state'] ?? [];

        $riskState =
            $risk['operational_risk_state'] ?? [];

        $riskSummary =
            $risk['risk_summary'] ?? [];

        $executiveState =
            $executive['executive_operational_state'] ?? [];

        $auditSummary =
            $audit['audit_summary'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Integrity Controls
        |--------------------------------------------------------------------------
        */

        $automaticPermissionExceptions = $actions->filter(
            fn ($action) =>
                (bool) $action->automatic_execution_allowed
                || (bool) $action->automatic_change_allowed
                || (bool) $action->automatic_deployment_allowed
                || (bool) $action->automatic_rollback_allowed
                || (bool) $action->automatic_clinical_action_allowed
        )->count();

        $humanGovernanceControlExceptions = $actions->filter(
            fn ($action) =>
                !(bool) $action->human_review_required
                || !(bool) $action->governance_validation_required
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [
            'operational_snapshot_available' => [
                'passed' => true,
                'message' =>
                    'Governance operational snapshot is available.',
            ],

            'operational_state_operational' => [
                'passed' =>
                    ($operationalState['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance operational state intelligence is operational.',
            ],

            'attention_escalation_operational' => [
                'passed' =>
                    ($escalation['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance attention and escalation intelligence is operational.',
            ],

            'bottleneck_intelligence_operational' => [
                'passed' =>
                    ($bottleneck['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance bottleneck intelligence is operational.',
            ],

            'throughput_efficiency_operational' => [
                'passed' =>
                    ($throughput['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance throughput and efficiency intelligence is operational.',
            ],

            'operational_risk_operational' => [
                'passed' =>
                    ($risk['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance operational risk intelligence is operational.',
            ],

            'executive_operational_intelligence_operational' => [
                'passed' =>
                    ($executive['analysis_completed'] ?? false) === true,

                'message' =>
                    'Executive operational governance intelligence is operational.',
            ],

            'step_61_audit_complete' => [
                'passed' =>
                    ($audit['audit_status'] ?? null) === 'COMPLETE'
                    && (int) ($auditSummary['failed_checks'] ?? 1) === 0,

                'message' =>
                    'Step 61 operational governance audit completed without integrity failures.',
            ],

            'governance_integrity_intact' => [
                'passed' =>
                    ($throughputState['governance_integrity_intact'] ?? false)
                    === true,

                'message' =>
                    'Operational governance integrity remains intact.',
            ],

            'critical_operational_risk_absent' => [
                'passed' =>
                    (int) ($riskSummary['critical_signals'] ?? 0) === 0,

                'value' =>
                    (int) ($riskSummary['critical_signals'] ?? 0),

                'message' =>
                    'No critical operational governance risk signal is present.',
            ],

            'immediate_escalation_absent' => [
                'passed' =>
                    ($escalationState['immediate_escalation_required'] ?? false)
                    === false,

                'value' =>
                    (bool) (
                        $escalationState['immediate_escalation_required']
                        ?? false
                    ),

                'message' =>
                    'No immediate governance escalation requirement is active.',
            ],

            'automatic_authority_isolation' => [
                'passed' =>
                    $automaticPermissionExceptions === 0,

                'value' =>
                    $automaticPermissionExceptions,

                'message' =>
                    'Automatic execution, AI modification, deployment, rollback, and clinical-action authority remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    $humanGovernanceControlExceptions === 0,

                'value' =>
                    $humanGovernanceControlExceptions,

                'message' =>
                    'Human review and governance validation remain mandatory.',
            ],

            'operational_intelligence_authority_isolation' => [
                'passed' =>
                    ($operationalState['operational_guardrails']['operational_intelligence_is_governance_decision'] ?? true)
                        === false
                    && ($escalation['escalation_guardrails']['escalation_signal_changes_action_state'] ?? true)
                        === false
                    && ($bottleneck['bottleneck_guardrails']['bottleneck_detection_is_action_resolution'] ?? true)
                        === false
                    && ($throughput['throughput_guardrails']['efficiency_score_authorizes_execution'] ?? true)
                        === false
                    && ($risk['risk_guardrails']['operational_risk_changes_action_state'] ?? true)
                        === false
                    && ($executive['executive_guardrails']['executive_intelligence_authorizes_execution'] ?? true)
                        === false,

                'message' =>
                    'Operational governance intelligence remains isolated from decision, resolution, and execution authority.',
            ],
        ];

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->where('passed', true)
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        if ($activeActions->count() > 0) {
            $warnings[] =
                "{$activeActions->count()} governance action(s) remain active or unresolved.";
        }

        if ((int) $snapshot->pending_human_reviews > 0) {
            $warnings[] =
                "{$snapshot->pending_human_reviews} governance action(s) remain pending human review.";
        }

        if ((int) $snapshot->evidence_waiting_actions > 0) {
            $warnings[] =
                "{$snapshot->evidence_waiting_actions} governance action(s) remain dependent on additional evidence.";
        }

        if ((int) $snapshot->deferred_actions > 0) {
            $warnings[] =
                "{$snapshot->deferred_actions} governance action(s) remain deferred.";
        }

        if (
            ($bottleneckState['bottleneck_level'] ?? null)
            === 'SIGNIFICANT_BOTTLENECK'
        ) {
            $warnings[] =
                'A significant operational governance bottleneck remains active.';
        }

        if (
            ($escalationState['management_escalation_recommended'] ?? false)
            === true
        ) {
            $warnings[] =
                'Management escalation remains recommended for current operational governance conditions.';
        }

        if (
            ($throughputState['process_balance_status'] ?? null)
            === 'IMBALANCED'
        ) {
            $warnings[] =
                'Governance review, decision, and closure progression remain operationally imbalanced.';
        }

        if (
            (float) $snapshot->action_closure_percentage < 50
        ) {
            $warnings[] =
                "Governance action closure remains at {$snapshot->action_closure_percentage}%, below the preferred operational progression threshold.";
        }

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $code => $check) {
            if (($check['passed'] ?? false) === false) {
                $criticalIssues[] =
                    "Final validation control {$code} failed.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Status
        |--------------------------------------------------------------------------
        */

        $readyForClosure =
            $failedChecks === 0
            && count($criticalIssues) === 0;

        $validationStatus = match (true) {
            !$readyForClosure =>
                'FAILED',

            count($warnings) > 0 =>
                'PASSED_WITH_WARNINGS',

            default =>
                'PASSED',
        };

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '61.1_operational_snapshot_registry' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '61.2_operational_snapshot_generation' => [
                'status' =>
                    'OPERATIONAL',

                'operational_snapshot_id' =>
                    $snapshot->id,
            ],

            '61.3_operational_state_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'operational_health' =>
                    $operationalStateData['operational_health']
                    ?? 'UNKNOWN',

                'attention_level' =>
                    $operationalStateData['attention_level']
                    ?? 'UNKNOWN',
            ],

            '61.4_attention_escalation_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'escalation_level' =>
                    $escalationState['escalation_level']
                    ?? 'UNKNOWN',

                'escalation_score' =>
                    $escalationState['escalation_score']
                    ?? 0,
            ],

            '61.5_bottleneck_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'bottleneck_level' =>
                    $bottleneckState['bottleneck_level']
                    ?? 'UNKNOWN',

                'bottleneck_score' =>
                    $bottleneckState['bottleneck_score']
                    ?? 0,
            ],

            '61.6_throughput_efficiency_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'throughput_status' =>
                    $throughputState['throughput_status']
                    ?? 'UNKNOWN',

                'efficiency_status' =>
                    $throughputState['efficiency_status']
                    ?? 'UNKNOWN',

                'efficiency_score' =>
                    $throughputState['efficiency_score']
                    ?? 0,
            ],

            '61.7_operational_risk_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'operational_risk_level' =>
                    $riskState['operational_risk_level']
                    ?? 'UNKNOWN',

                'operational_risk_score' =>
                    $riskState['operational_risk_score']
                    ?? 0,
            ],

            '61.8_executive_operational_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'executive_operational_status' =>
                    $executiveState['executive_operational_status']
                    ?? 'UNKNOWN',

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? 'UNKNOWN',

                'executive_operational_score' =>
                    $executiveState['executive_operational_score']
                    ?? 0,
            ],

            '61.9_operational_governance_audit' => [
                'status' =>
                    $audit['audit_status'] ?? 'UNKNOWN',

                'passed_checks' =>
                    $auditSummary['passed_checks'] ?? 0,

                'failed_checks' =>
                    $auditSummary['failed_checks'] ?? 0,
            ],

            '61.10_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_61_ready_for_closure' =>
                    $readyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            'The complete Step 61 AI Governance Operational Intelligence architecture has been validated.',

            "{$actions->count()} governance action(s) are represented in the operational governance lifecycle.",

            "{$activeActions->count()} governance action(s) remain active.",

            "{$closedActions->count()} governance action(s) are closed.",

            'Current operational health is '
                . ($operationalStateData['operational_health'] ?? 'UNKNOWN')
                . '.',

            'Current operational attention level is '
                . ($operationalStateData['attention_level'] ?? 'UNKNOWN')
                . '.',

            'Current operational escalation classification is '
                . ($escalationState['escalation_level'] ?? 'UNKNOWN')
                . '.',

            'Current governance bottleneck classification is '
                . ($bottleneckState['bottleneck_level'] ?? 'UNKNOWN')
                . '.',

            'Current governance throughput classification is '
                . ($throughputState['throughput_status'] ?? 'UNKNOWN')
                . '.',

            'Current operational efficiency classification is '
                . ($throughputState['efficiency_status'] ?? 'UNKNOWN')
                . '.',

            'Current operational governance risk level is '
                . ($riskState['operational_risk_level'] ?? 'UNKNOWN')
                . '.',

            'Current executive operational status is '
                . ($executiveState['executive_operational_status'] ?? 'UNKNOWN')
                . '.',

            'Operational governance intelligence remains advisory and human governed.',

            'No autonomous governance decision, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        return [
            'validation_status' =>
                $validationStatus,

            'step_61_ready_for_closure' =>
                $readyForClosure,

            'governance_operational_mode' =>
                'HUMAN_GOVERNED_OPERATIONAL_INTELLIGENCE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'completion_message' =>
                $readyForClosure
                    ? 'Step 61 AI Governance Operational Intelligence has passed final validation and is ready for closure.'
                    : 'Step 61 AI Governance Operational Intelligence contains validation failures and is not ready for closure.',

            'validation_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'warning_count' =>
                    count($warnings),

                'critical_issue_count' =>
                    count($criticalIssues),
            ],

            'checks' =>
                $checks,

            'governance_operational_context' => [
                'total_actions' =>
                    $actions->count(),

                'active_actions' =>
                    $activeActions->count(),

                'closed_actions' =>
                    $closedActions->count(),

                'action_closure_percentage' =>
                    (float) $snapshot->action_closure_percentage,

                'decision_completion_percentage' =>
                    (float) $snapshot->decision_completion_percentage,

                'operational_health' =>
                    $operationalStateData['operational_health']
                    ?? 'UNKNOWN',

                'operational_maturity' =>
                    $operationalStateData['operational_maturity']
                    ?? 'UNKNOWN',

                'operational_maturity_score' =>
                    $operationalStateData['operational_maturity_score']
                    ?? 0,

                'attention_level' =>
                    $operationalStateData['attention_level']
                    ?? 'UNKNOWN',

                'attention_score' =>
                    $operationalStateData['attention_score']
                    ?? 0,

                'escalation_level' =>
                    $escalationState['escalation_level']
                    ?? 'UNKNOWN',

                'escalation_score' =>
                    $escalationState['escalation_score']
                    ?? 0,

                'bottleneck_level' =>
                    $bottleneckState['bottleneck_level']
                    ?? 'UNKNOWN',

                'bottleneck_score' =>
                    $bottleneckState['bottleneck_score']
                    ?? 0,

                'throughput_status' =>
                    $throughputState['throughput_status']
                    ?? 'UNKNOWN',

                'efficiency_status' =>
                    $throughputState['efficiency_status']
                    ?? 'UNKNOWN',

                'efficiency_score' =>
                    $throughputState['efficiency_score']
                    ?? 0,

                'process_balance_status' =>
                    $throughputState['process_balance_status']
                    ?? 'UNKNOWN',

                'operational_risk_level' =>
                    $riskState['operational_risk_level']
                    ?? 'UNKNOWN',

                'operational_risk_score' =>
                    $riskState['operational_risk_score']
                    ?? 0,

                'executive_operational_status' =>
                    $executiveState['executive_operational_status']
                    ?? 'UNKNOWN',

                'executive_readiness' =>
                    $executiveState['executive_readiness']
                    ?? 'UNKNOWN',

                'executive_confidence' =>
                    $executiveState['executive_confidence']
                    ?? 'UNKNOWN',

                'executive_operational_score' =>
                    $executiveState['executive_operational_score']
                    ?? 0,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_61_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'governance_operational_intelligence_enabled' =>
                true,

            'operational_snapshotting_enabled' =>
                true,

            'operational_state_intelligence_enabled' =>
                true,

            'attention_escalation_intelligence_enabled' =>
                true,

            'bottleneck_intelligence_enabled' =>
                true,

            'throughput_efficiency_intelligence_enabled' =>
                true,

            'operational_risk_intelligence_enabled' =>
                true,

            'executive_operational_intelligence_enabled' =>
                true,

            'operational_governance_audit_enabled' =>
                true,

            'autonomous_governance_operation_enabled' =>
                false,

            'automatic_governance_decision' =>
                false,

            'automatic_governance_resolution' =>
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

            'automatic_clinical_action' =>
                false,

            'automatic_execution' =>
                false,

            'automatic_deployment' =>
                false,

            'automatic_rollback' =>
                false,

            'human_review_required' =>
                true,

            'governance_validation_required' =>
                true,

            'message' =>
                'Step 61 establishes human-governed AI governance operational intelligence. The system may consolidate governance workload, operational state, attention, escalation, bottlenecks, throughput, efficiency, risk, executive operational status, and audit integrity, but it does not autonomously make governance decisions, resolve work items, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human governance or clinical authority.',
        ];
    }
}