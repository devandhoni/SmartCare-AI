<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceAttentionEscalationIntelligenceEngine
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
        | Step 61.3 Source Intelligence
        |--------------------------------------------------------------------------
        */

        $operationalState = app(
            AIGovernanceOperationalStateIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (
            ($operationalState['analysis_completed'] ?? false) !== true
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'OPERATIONAL_STATE_INTELLIGENCE_UNAVAILABLE',
                'operational_snapshot_id' => $snapshot->id,
                'message' => 'Governance operational state intelligence is unavailable.',
            ];
        }

        $state =
            $operationalState['operational_state']
            ?? [];

        $attentionContext =
            $operationalState['human_attention_context']
            ?? [];

        $decisionContext =
            $operationalState['decision_context']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Current Governance Actions
        |--------------------------------------------------------------------------
        */

        $closedStatuses = [
            'RESOLVED',
            'CLOSED_REJECTED',
        ];

        $activeActions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->lifecycle_snapshot_id)
            ->whereNotIn('action_status', $closedStatuses)
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        $criticalActions = $activeActions
            ->where('priority_level', 'CRITICAL')
            ->values();

        $highActions = $activeActions
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

        /*
        |--------------------------------------------------------------------------
        | Escalation Signals
        |--------------------------------------------------------------------------
        */

        $signals = [];

        $signals['critical_priority_work'] = [
            'detected' => $criticalActions->count() > 0,
            'severity' => 'CRITICAL',
            'value' => $criticalActions->count(),
            'message' =>
                'Critical-priority unresolved governance work requires immediate human governance attention.',
        ];

        $signals['high_priority_work'] = [
            'detected' => $highActions->count() > 0,
            'severity' => 'HIGH',
            'value' => $highActions->count(),
            'message' =>
                'High-priority unresolved governance work requires elevated human visibility.',
        ];

        $signals['high_attention_pressure'] = [
            'detected' =>
                in_array(
                    $state['attention_level'] ?? null,
                    ['HIGH', 'VERY_HIGH'],
                    true
                ),
            'severity' => 'MODERATE',
            'value' => [
                'attention_level' =>
                    $state['attention_level'] ?? null,

                'attention_score' =>
                    $state['attention_score'] ?? null,
            ],
            'message' =>
                'Operational governance attention pressure is currently elevated.',
        ];

        $signals['pending_human_decisions'] = [
            'detected' =>
                ((int) ($attentionContext['pending_human_decisions'] ?? 0)) > 0,

            'severity' => 'MODERATE',

            'value' =>
                (int) ($attentionContext['pending_human_decisions'] ?? 0),

            'message' =>
                'Governance actions remain pending explicit human decisions.',
        ];

        $signals['pending_human_reviews'] = [
            'detected' =>
                ((int) ($attentionContext['pending_human_reviews'] ?? 0)) > 0,

            'severity' => 'MODERATE',

            'value' =>
                (int) ($attentionContext['pending_human_reviews'] ?? 0),

            'message' =>
                'Governance actions remain pending human review.',
        ];

        $signals['evidence_dependency'] = [
            'detected' => $evidenceDependentActions->count() > 0,
            'severity' => 'MODERATE',
            'value' => $evidenceDependentActions->count(),
            'message' =>
                'One or more governance actions cannot progress until additional evidence becomes available.',
        ];

        $signals['deferred_governance_work'] = [
            'detected' => $deferredActions->count() > 0,
            'severity' => 'ADVISORY',
            'value' => $deferredActions->count(),
            'message' =>
                'Deferred governance work remains under controlled observation.',
        ];

        $signals['low_action_closure'] = [
            'detected' =>
                ((float) $snapshot->action_closure_percentage) < 50.0
                && $activeActions->count() > 0,

            'severity' => 'ADVISORY',

            'value' =>
                (float) $snapshot->action_closure_percentage,

            'threshold' =>
                50.0,

            'message' =>
                'Current governance action closure remains below the operational observation threshold.',
        ];

        $signals['decision_risk_attention'] = [
            'detected' =>
                !in_array(
                    $snapshot->decision_risk_level,
                    [
                        null,
                        'LOW',
                        'MINIMAL',
                    ],
                    true
                ),

            'severity' => $this->decisionRiskSeverity(
                $snapshot->decision_risk_level
            ),

            'value' => [
                'risk_level' =>
                    $snapshot->decision_risk_level,

                'risk_score' =>
                    $snapshot->decision_risk_score,
            ],

            'message' =>
                'Current governance decision risk classification requires continued human visibility.',
        ];

        $signals['decision_inconsistency'] = [
            'detected' =>
                !in_array(
                    $snapshot->decision_consistency_status,
                    [
                        'HIGHLY_CONSISTENT',
                        'CONSISTENT',
                    ],
                    true
                ),

            'severity' => 'HIGH',

            'value' => [
                'consistency_status' =>
                    $snapshot->decision_consistency_status,

                'consistency_score' =>
                    $snapshot->decision_consistency_score,
            ],

            'message' =>
                'Governance decision consistency has fallen outside the expected controlled state.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Signal Counts
        |--------------------------------------------------------------------------
        */

        $detectedSignals = collect($signals)
            ->filter(
                fn (array $signal) =>
                    ($signal['detected'] ?? false) === true
            );

        $criticalSignalCount = $detectedSignals
            ->where('severity', 'CRITICAL')
            ->count();

        $highSignalCount = $detectedSignals
            ->where('severity', 'HIGH')
            ->count();

        $moderateSignalCount = $detectedSignals
            ->where('severity', 'MODERATE')
            ->count();

        $advisorySignalCount = $detectedSignals
            ->where('severity', 'ADVISORY')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Escalation Level
        |--------------------------------------------------------------------------
        */

        $escalationLevel = match (true) {
            $criticalSignalCount > 0 =>
                'CRITICAL_ESCALATION',

            $highSignalCount >= 2 =>
                'HIGH_ESCALATION',

            $highSignalCount === 1 =>
                'ELEVATED_HUMAN_ATTENTION',

            $moderateSignalCount >= 3 =>
                'MODERATE_ESCALATION',

            $moderateSignalCount > 0 =>
                'ROUTINE_HUMAN_ATTENTION',

            $advisorySignalCount > 0 =>
                'ADVISORY_MONITORING',

            default =>
                'NO_ESCALATION_REQUIRED',
        };

        /*
        |--------------------------------------------------------------------------
        | Escalation Score
        |--------------------------------------------------------------------------
        */

        $escalationScore =
            ($criticalSignalCount * 35)
            + ($highSignalCount * 20)
            + ($moderateSignalCount * 8)
            + ($advisorySignalCount * 3);

        $escalationScore = min(
            100,
            $escalationScore
        );

        /*
        |--------------------------------------------------------------------------
        | Management Escalation
        |--------------------------------------------------------------------------
        */

        $managementEscalationRecommended =
            in_array(
                $escalationLevel,
                [
                    'CRITICAL_ESCALATION',
                    'HIGH_ESCALATION',
                    'ELEVATED_HUMAN_ATTENTION',
                    'MODERATE_ESCALATION',
                ],
                true
            );

        $immediateEscalationRequired =
            $escalationLevel === 'CRITICAL_ESCALATION';

        /*
        |--------------------------------------------------------------------------
        | Highest Attention Action
        |--------------------------------------------------------------------------
        */

        $highestAttentionAction =
            $activeActions->first();

        $highestAttentionActionContext =
            $highestAttentionAction
                ? $this->actionContext(
                    $highestAttentionAction
                )
                : null;

        /*
        |--------------------------------------------------------------------------
        | Escalation Areas
        |--------------------------------------------------------------------------
        */

        $escalationAreas = $detectedSignals
            ->map(
                fn (array $signal, string $code) => [
                    'signal_code' => strtoupper($code),
                    'severity' => $signal['severity'],
                    'value' => $signal['value'] ?? null,
                    'message' => $signal['message'],
                ]
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            count($signals)
                . ' governance operational escalation condition(s) were evaluated.',

            $detectedSignals->count()
                . ' operational attention or escalation signal(s) are currently detected.',

            "{$criticalSignalCount} critical, {$highSignalCount} high, {$moderateSignalCount} moderate, and {$advisorySignalCount} advisory signal(s) are currently detected.",

            "Current governance escalation level is {$escalationLevel}.",

            "Current governance escalation score is {$escalationScore}.",

            'Management escalation recommendation is '
                . ($managementEscalationRecommended ? 'ACTIVE' : 'NOT_REQUIRED')
                . '.',

            'Immediate escalation requirement is '
                . ($immediateEscalationRequired ? 'ACTIVE' : 'NOT_REQUIRED')
                . '.',

            'Current operational attention level is '
                . ($state['attention_level'] ?? 'UNKNOWN')
                . ' with attention score '
                . ($state['attention_score'] ?? 0)
                . '.',

            "Current governance decision risk level is {$snapshot->decision_risk_level} with risk score {$snapshot->decision_risk_score}.",

            "Current decision consistency classification is {$snapshot->decision_consistency_status}.",
        ];

        if ($highestAttentionActionContext !== null) {
            $findings[] =
                "Highest currently active governance attention item is {$highestAttentionActionContext['action_code']} with priority score {$highestAttentionActionContext['priority_score']}.";
        }

        if ($criticalSignalCount === 0) {
            $findings[] =
                'No critical operational governance escalation condition is currently detected.';
        }

        if (
            $snapshot->automatic_execution_allowed === false
            && $snapshot->automatic_change_allowed === false
            && $snapshot->automatic_deployment_allowed === false
            && $snapshot->automatic_rollback_allowed === false
            && $snapshot->automatic_clinical_action_allowed === false
        ) {
            $findings[] =
                'No automatic execution, AI modification, deployment, rollback, or clinical-action authority is enabled by the operational snapshot.';
        }

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_ATTENTION_ESCALATION_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'escalation_state' => [
                'escalation_level' =>
                    $escalationLevel,

                'escalation_score' =>
                    $escalationScore,

                'management_escalation_recommended' =>
                    $managementEscalationRecommended,

                'immediate_escalation_required' =>
                    $immediateEscalationRequired,

                'human_attention_required' =>
                    $detectedSignals->count() > 0,
            ],

            'signal_summary' => [
                'total_conditions_evaluated' =>
                    count($signals),

                'detected_signal_count' =>
                    $detectedSignals->count(),

                'critical_signals' =>
                    $criticalSignalCount,

                'high_signals' =>
                    $highSignalCount,

                'moderate_signals' =>
                    $moderateSignalCount,

                'advisory_signals' =>
                    $advisorySignalCount,
            ],

            'escalation_signals' =>
                $signals,

            'escalation_areas' =>
                $escalationAreas,

            'highest_attention_action' =>
                $highestAttentionActionContext,

            'operational_context' => [
                'operational_status' =>
                    $snapshot->operational_status,

                'operational_health' =>
                    $state['operational_health']
                    ?? null,

                'attention_level' =>
                    $state['attention_level']
                    ?? null,

                'attention_score' =>
                    $state['attention_score']
                    ?? null,

                'operational_maturity' =>
                    $state['operational_maturity']
                    ?? null,

                'operational_maturity_score' =>
                    $state['operational_maturity_score']
                    ?? null,

                'governance_workload_status' =>
                    $snapshot->governance_workload_status,

                'decision_status' =>
                    $snapshot->decision_status,

                'decision_risk_level' =>
                    $snapshot->decision_risk_level,

                'decision_risk_score' =>
                    $snapshot->decision_risk_score,

                'decision_consistency_status' =>
                    $snapshot->decision_consistency_status,

                'decision_consistency_score' =>
                    $snapshot->decision_consistency_score,
            ],

            'attention_findings' =>
                $findings,

            'escalation_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function decisionRiskSeverity(?string $riskLevel): string
    {
        return match ($riskLevel) {
            'CRITICAL',
            'CRITICAL_ATTENTION_REQUIRED' =>
                'CRITICAL',

            'HIGH',
            'HIGH_RISK' =>
                'HIGH',

            'MODERATE',
            'MODERATE_RISK',
            'LOW_WITH_MODERATE_ATTENTION' =>
                'MODERATE',

            default =>
                'ADVISORY',
        };
    }

    private function actionContext(AIGovernanceAction $action): array
    {
        return [
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
    }

    private function guardrails(): array
    {
        return [
            'attention_escalation_intelligence_enabled' =>
                true,

            'escalation_signal_is_governance_decision' =>
                false,

            'escalation_signal_is_automatic_notification' =>
                false,

            'escalation_signal_changes_action_state' =>
                false,

            'escalation_signal_changes_priority' =>
                false,

            'escalation_signal_changes_eligibility' =>
                false,

            'management_escalation_recommendation_is_execution' =>
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
                'Governance attention and escalation intelligence identifies conditions requiring increased human governance visibility only. Escalation intelligence does not send notifications automatically, make governance decisions, resolve actions, change priority or eligibility, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}