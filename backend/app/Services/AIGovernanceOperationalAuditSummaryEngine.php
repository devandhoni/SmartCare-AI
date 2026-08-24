<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceOperationalAuditSummaryEngine
{
    public function analyze(?int $operationalSnapshotId = null): array
    {
        $snapshot = $operationalSnapshotId !== null
            ? AIGovernanceOperationalSnapshot::find($operationalSnapshotId)
            : AIGovernanceOperationalSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'audit_available' => false,
                'audit_status' => 'OPERATIONAL_SNAPSHOT_NOT_FOUND',
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

        $executive = app(
            AIGovernanceExecutiveOperationalIntelligenceEngine::class
        )->analyze($snapshot->id);

        /*
        |--------------------------------------------------------------------------
        | Action Registry
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Source Context
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Integrity Exceptions
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
        | Audit Checks
        |--------------------------------------------------------------------------
        */

        $checks = [
            'operational_snapshot_available' => [
                'passed' => true,
                'message' =>
                    'Governance operational snapshot is available.',
            ],

            'operational_state_intelligence_available' => [
                'passed' =>
                    ($operationalState['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance operational state intelligence is available.',
            ],

            'attention_escalation_intelligence_available' => [
                'passed' =>
                    ($escalation['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance attention and escalation intelligence is available.',
            ],

            'bottleneck_intelligence_available' => [
                'passed' =>
                    ($bottleneck['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance bottleneck intelligence is available.',
            ],

            'throughput_efficiency_intelligence_available' => [
                'passed' =>
                    ($throughput['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance throughput and efficiency intelligence is available.',
            ],

            'operational_risk_intelligence_available' => [
                'passed' =>
                    ($risk['analysis_completed'] ?? false) === true,

                'message' =>
                    'Governance operational risk intelligence is available.',
            ],

            'executive_operational_intelligence_available' => [
                'passed' =>
                    ($executive['analysis_completed'] ?? false) === true,

                'message' =>
                    'Executive operational governance intelligence is available.',
            ],

            'governance_integrity_intact' => [
                'passed' =>
                    ($throughputState['governance_integrity_intact'] ?? false)
                    === true,

                'message' =>
                    'Governance operational integrity remains intact.',
            ],

            'no_critical_operational_risk' => [
                'passed' =>
                    (int) ($riskSummary['critical_signals'] ?? 0) === 0,

                'value' =>
                    (int) ($riskSummary['critical_signals'] ?? 0),

                'message' =>
                    'No critical operational governance risk signal should be present.',
            ],

            'no_immediate_escalation_requirement' => [
                'passed' =>
                    ($escalationState['immediate_escalation_required'] ?? false)
                    === false,

                'value' =>
                    (bool) (
                        $escalationState['immediate_escalation_required']
                        ?? false
                    ),

                'message' =>
                    'No immediate operational governance escalation requirement should be active.',
            ],

            'automatic_permission_isolation' => [
                'passed' =>
                    $automaticPermissionExceptions === 0,

                'value' =>
                    $automaticPermissionExceptions,

                'message' =>
                    'Automatic execution, AI modification, deployment, rollback, and clinical-action authority must remain disabled.',
            ],

            'human_governance_controls' => [
                'passed' =>
                    $humanGovernanceControlExceptions === 0,

                'value' =>
                    $humanGovernanceControlExceptions,

                'message' =>
                    'Human review and governance validation must remain mandatory.',
            ],

            'executive_authority_isolation' => [
                'passed' =>
                    ($executive['executive_guardrails']['executive_intelligence_authorizes_execution'] ?? true)
                        === false
                    && ($executive['executive_guardrails']['executive_intelligence_authorizes_deployment'] ?? true)
                        === false
                    && ($executive['executive_guardrails']['executive_intelligence_authorizes_rollback'] ?? true)
                        === false,

                'message' =>
                    'Executive operational intelligence must remain informational and must not become execution, deployment, or rollback authority.',
            ],

            'operational_intelligence_authority_isolation' => [
                'passed' =>
                    ($risk['risk_guardrails']['operational_risk_changes_action_state'] ?? true)
                        === false
                    && ($bottleneck['bottleneck_guardrails']['bottleneck_detection_is_action_resolution'] ?? true)
                        === false
                    && ($escalation['escalation_guardrails']['escalation_signal_changes_action_state'] ?? true)
                        === false,

                'message' =>
                    'Operational intelligence must not autonomously change governance action lifecycle state.',
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
        | Management Status
        |--------------------------------------------------------------------------
        */

        $managementStatus = match (true) {
            $failedChecks > 0 =>
                'OPERATIONAL_GOVERNANCE_INTEGRITY_ATTENTION_REQUIRED',

            ($escalationState['immediate_escalation_required'] ?? false)
                === true =>
                    'IMMEDIATE_HUMAN_GOVERNANCE_ATTENTION_REQUIRED',

            $activeActions->count() > 0 =>
                'CONTROLLED_OPERATIONAL_GOVERNANCE_WORK_REMAINS',

            default =>
                'OPERATIONAL_GOVERNANCE_STABLE',
        };

        /*
        |--------------------------------------------------------------------------
        | Audit Findings
        |--------------------------------------------------------------------------
        */

        $auditFindings = [
            "Operational governance audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",

            "{$actions->count()} governance action(s) are represented in the Step 61 operational audit.",

            "{$activeActions->count()} governance action(s) remain active.",

            "{$closedActions->count()} governance action(s) are closed.",

            "Current operational health is "
                . ($operationalStateData['operational_health'] ?? 'UNKNOWN')
                . '.',

            "Current operational attention level is "
                . ($operationalStateData['attention_level'] ?? 'UNKNOWN')
                . " with score "
                . ($operationalStateData['attention_score'] ?? 0)
                . '.',

            "Current escalation classification is "
                . ($escalationState['escalation_level'] ?? 'UNKNOWN')
                . " with score "
                . ($escalationState['escalation_score'] ?? 0)
                . '.',

            "Current bottleneck classification is "
                . ($bottleneckState['bottleneck_level'] ?? 'UNKNOWN')
                . " with score "
                . ($bottleneckState['bottleneck_score'] ?? 0)
                . '.',

            "Current throughput classification is "
                . ($throughputState['throughput_status'] ?? 'UNKNOWN')
                . '.',

            "Current operational efficiency classification is "
                . ($throughputState['efficiency_status'] ?? 'UNKNOWN')
                . " with score "
                . ($throughputState['efficiency_score'] ?? 0)
                . '.',

            "Current operational risk level is "
                . ($riskState['operational_risk_level'] ?? 'UNKNOWN')
                . " with score "
                . ($riskState['operational_risk_score'] ?? 0)
                . '.',

            "Current executive operational status is "
                . ($executiveState['executive_operational_status'] ?? 'UNKNOWN')
                . '.',

            "Current executive readiness is "
                . ($executiveState['executive_readiness'] ?? 'UNKNOWN')
                . '.',

            "Automatic-permission exception count is {$automaticPermissionExceptions}.",

            "Human-governance control exception count is {$humanGovernanceControlExceptions}.",
        ];

        if ($failedChecks === 0) {
            $auditFindings[] =
                'Step 61 operational governance integrity controls currently contain no failures.';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $executive['executive_priorities'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'audit_available' =>
                true,

            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_FAILURES',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

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

            'operational_summary' => [
                'operational_status' =>
                    $snapshot->operational_status,

                'operational_health' =>
                    $operationalStateData['operational_health']
                    ?? 'UNKNOWN',

                'human_governance_state' =>
                    $operationalStateData['human_governance_state']
                    ?? 'UNKNOWN',

                'attention_level' =>
                    $operationalStateData['attention_level']
                    ?? 'UNKNOWN',

                'attention_score' =>
                    $operationalStateData['attention_score']
                    ?? 0,

                'operational_maturity' =>
                    $operationalStateData['operational_maturity']
                    ?? 'UNKNOWN',

                'operational_maturity_score' =>
                    $operationalStateData['operational_maturity_score']
                    ?? 0,
            ],

            'escalation_summary' => [
                'escalation_level' =>
                    $escalationState['escalation_level']
                    ?? 'UNKNOWN',

                'escalation_score' =>
                    $escalationState['escalation_score']
                    ?? 0,

                'management_escalation_recommended' =>
                    $escalationState['management_escalation_recommended']
                    ?? false,

                'immediate_escalation_required' =>
                    $escalationState['immediate_escalation_required']
                    ?? false,
            ],

            'bottleneck_summary' => [
                'bottleneck_level' =>
                    $bottleneckState['bottleneck_level']
                    ?? 'UNKNOWN',

                'bottleneck_score' =>
                    $bottleneckState['bottleneck_score']
                    ?? 0,

                'dominant_bottleneck' =>
                    $bottleneckState['dominant_bottleneck']
                    ?? null,
            ],

            'throughput_summary' => [
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

                'governance_integrity_intact' =>
                    $throughputState['governance_integrity_intact']
                    ?? false,
            ],

            'risk_summary' => [
                'operational_risk_level' =>
                    $riskState['operational_risk_level']
                    ?? 'UNKNOWN',

                'operational_risk_score' =>
                    $riskState['operational_risk_score']
                    ?? 0,

                'risk_control_status' =>
                    $riskState['risk_control_status']
                    ?? 'UNKNOWN',

                'critical_signals' =>
                    $riskSummary['critical_signals']
                    ?? 0,

                'high_signals' =>
                    $riskSummary['high_signals']
                    ?? 0,

                'moderate_signals' =>
                    $riskSummary['moderate_signals']
                    ?? 0,

                'advisory_signals' =>
                    $riskSummary['advisory_signals']
                    ?? 0,
            ],

            'executive_summary' => [
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

                'management_escalation_recommended' =>
                    $executiveState['management_escalation_recommended']
                    ?? false,
            ],

            'action_summary' => [
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
            ],

            'integrity_summary' => [
                'automatic_permission_exceptions' =>
                    $automaticPermissionExceptions,

                'human_governance_control_exceptions' =>
                    $humanGovernanceControlExceptions,

                'governance_integrity_intact' =>
                    $automaticPermissionExceptions === 0
                    && $humanGovernanceControlExceptions === 0,
            ],

            'audit_findings' =>
                $auditFindings,

            'management_priorities' =>
                $managementPriorities,

            'audit_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'operational_governance_audit_enabled' =>
                true,

            'audit_is_governance_decision' =>
                false,

            'audit_is_action_resolution' =>
                false,

            'audit_is_execution_authorization' =>
                false,

            'audit_is_deployment_authorization' =>
                false,

            'audit_is_rollback_authorization' =>
                false,

            'audit_changes_action_state' =>
                false,

            'audit_changes_priority' =>
                false,

            'audit_changes_eligibility' =>
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
                'Operational governance audit consolidates Step 61 governance operational state, attention, escalation, bottlenecks, throughput, efficiency, risk, executive oversight, and control integrity for audit and management review only. It does not make governance decisions, resolve actions, change priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}