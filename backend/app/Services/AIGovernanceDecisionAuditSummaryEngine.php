<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionAuditSummaryEngine
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
                'snapshot_id' => $snapshotId,
                'message' => 'AI improvement lifecycle snapshot was not found.',
            ];
        }

        $decision = app(
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

        $risk = app(
            AIGovernanceDecisionRiskIntelligenceEngine::class
        )->analyze($snapshot->id);

        $trend = app(
            AIGovernanceDecisionTrendIntelligenceEngine::class
        )->analyze($snapshot->id);

        $recommendation = app(
            AIGovernanceDecisionRecommendationIntelligenceEngine::class
        )->analyze($snapshot->id);

        $executive = app(
            AIGovernanceDecisionExecutiveSummaryEngine::class
        )->analyze($snapshot->id);

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->get();

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->where('review_status', 'COMPLETED')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Audit Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['decision_intelligence_available'] = [
            'passed' =>
                ($decision['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision intelligence is available.',
        ];

        $checks['pattern_analysis_available'] = [
            'passed' =>
                ($pattern['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision pattern analysis is available.',
        ];

        $checks['decision_outcome_correlation_available'] = [
            'passed' =>
                ($correlation['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision-to-outcome correlation intelligence is available.',
        ];

        $checks['decision_consistency_available'] = [
            'passed' =>
                ($consistency['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision consistency intelligence is available.',
        ];

        $checks['decision_risk_available'] = [
            'passed' =>
                ($risk['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision risk intelligence is available.',
        ];

        $checks['decision_trend_available'] = [
            'passed' =>
                ($trend['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision trend intelligence is available.',
        ];

        $checks['decision_recommendation_available'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision recommendation intelligence is available.',
        ];

        $checks['decision_executive_summary_available'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision executive summary intelligence is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Historical Human Decision Integrity
        |--------------------------------------------------------------------------
        */

        $completedDecisionCount =
            (int) (
                $decision[
                    'decision_summary'
                ]['total_decisions']
                ?? 0
            );

        $checks['historical_review_registry_integrity'] = [
            'passed' =>
                $completedDecisionCount === $reviews->count(),
            'value' => [
                'decision_intelligence_count' =>
                    $completedDecisionCount,
                'review_registry_count' =>
                    $reviews->count(),
            ],
            'message' =>
                'Decision intelligence count should match the dedicated completed human governance review registry.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Decision State Consistency
        |--------------------------------------------------------------------------
        */

        $contradictionCount =
            (int) (
                $consistency[
                    'consistency_summary'
                ]['contradiction_count']
                ?? 0
            );

        $failedConsistencyChecks =
            (int) (
                $consistency[
                    'consistency_summary'
                ]['failed_checks']
                ?? 0
            );

        $checks['decision_state_consistency'] = [
            'passed' =>
                $contradictionCount === 0
                && $failedConsistencyChecks === 0,
            'value' => [
                'contradiction_count' =>
                    $contradictionCount,
                'failed_consistency_checks' =>
                    $failedConsistencyChecks,
            ],
            'message' =>
                'Governance decision lifecycle should contain no detected contradictions or failed consistency controls.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Decision Outcome Alignment
        |--------------------------------------------------------------------------
        */

        $inconsistentOutcomes =
            (int) (
                $correlation[
                    'correlation_summary'
                ]['inconsistent_outcomes']
                ?? 0
            );

        $checks['decision_outcome_alignment'] = [
            'passed' =>
                $inconsistentOutcomes === 0,
            'value' => [
                'inconsistent_outcomes' =>
                    $inconsistentOutcomes,
                'consistency_percentage' =>
                    $correlation[
                        'correlation_summary'
                    ]['consistency_percentage']
                    ?? 0,
            ],
            'message' =>
                'Recorded human governance decisions should remain aligned with current governance action states.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Permission Isolation
        |--------------------------------------------------------------------------
        */

        $automaticPermissionExceptions =
            $actions->filter(
                fn ($action) =>
                    (bool) $action->automatic_execution_allowed
                    || (bool) $action->automatic_change_allowed
                    || (bool) $action->automatic_deployment_allowed
                    || (bool) $action->automatic_rollback_allowed
                    || (bool) $action->automatic_clinical_action_allowed
            )->count();

        $checks['automatic_permission_isolation'] = [
            'passed' =>
                $automaticPermissionExceptions === 0,
            'value' =>
                $automaticPermissionExceptions,
            'message' =>
                'Automatic execution, AI modification, deployment, rollback, and clinical-action permissions must remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $humanGovernanceExceptions =
            $actions->filter(
                fn ($action) =>
                    !(bool) $action->human_review_required
                    || !(bool) $action->governance_validation_required
            )->count();

        $checks['human_governance_controls'] = [
            'passed' =>
                $humanGovernanceExceptions === 0,
            'value' =>
                $humanGovernanceExceptions,
            'message' =>
                'Human review and governance validation must remain mandatory across governance actions.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Authority Isolation
        |--------------------------------------------------------------------------
        */

        $recommendationGuardrails =
            $recommendation['recommendation_guardrails']
            ?? [];

        $checks['recommendation_authority_isolation'] = [
            'passed' =>
                ($recommendationGuardrails[
                    'recommendation_is_human_governance_decision'
                ] ?? true) === false
                &&
                ($recommendationGuardrails[
                    'recommendation_is_approval'
                ] ?? true) === false
                &&
                ($recommendationGuardrails[
                    'recommendation_is_rejection'
                ] ?? true) === false
                &&
                ($recommendationGuardrails[
                    'recommendation_is_resolution'
                ] ?? true) === false,
            'message' =>
                'Decision recommendation intelligence must remain advisory and must not become governance decision authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Executive Authority Isolation
        |--------------------------------------------------------------------------
        */

        $executiveGuardrails =
            $executive['executive_guardrails']
            ?? [];

        $checks['executive_authority_isolation'] = [
            'passed' =>
                ($executiveGuardrails[
                    'executive_summary_is_governance_decision'
                ] ?? true) === false
                &&
                ($executiveGuardrails[
                    'executive_status_is_approval'
                ] ?? true) === false
                &&
                ($executiveGuardrails[
                    'executive_status_is_rejection'
                ] ?? true) === false
                &&
                ($executiveGuardrails[
                    'executive_status_is_resolution'
                ] ?? true) === false
                &&
                ($executiveGuardrails[
                    'executive_readiness_is_execution_authorization'
                ] ?? true) === false,
            'message' =>
                'Executive governance intelligence must remain informational and must not become approval, rejection, resolution, or execution authority.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Totals
        |--------------------------------------------------------------------------
        */

        $totalChecks =
            count($checks);

        $passedChecks =
            collect($checks)
                ->where('passed', true)
                ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $decisionSummary =
            $decision['decision_summary']
            ?? [];

        $resolutionSummary =
            $decision['resolution_summary']
            ?? [];

        $riskSummary =
            $risk['risk_summary']
            ?? [];

        $trendSummary =
            $trend['trend_summary']
            ?? [];

        $recommendationSummary =
            $recommendation['recommendation_summary']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Audit Status
        |--------------------------------------------------------------------------
        */

        $criticalSignals =
            (int) ($riskSummary['critical_signals'] ?? 0);

        $highSignals =
            (int) ($riskSummary['high_signals'] ?? 0);

        $auditStatus = match (true) {
            $failedChecks > 0 =>
                'FAILED',

            $criticalSignals > 0 =>
                'COMPLETE_WITH_CRITICAL_ATTENTION',

            $highSignals > 0 =>
                'COMPLETE_WITH_HIGH_ATTENTION',

            default =>
                'COMPLETE',
        };

        $managementStatus = match (true) {
            $failedChecks > 0 =>
                'GOVERNANCE_INTEGRITY_REVIEW_REQUIRED',

            ($decisionSummary['pending_decisions'] ?? 0) > 0 =>
                'HUMAN_DECISION_WORK_REMAINS',

            ($resolutionSummary['active_actions'] ?? 0) > 0 =>
                'ACTIVE_GOVERNANCE_WORK_REMAINS',

            default =>
                'CURRENT_DECISION_WORK_COMPLETE',
        };

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Governance decision audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",

            ($decisionSummary['total_actions'] ?? 0)
                . ' governance action(s) are represented in Step 60 decision intelligence.',

            ($decisionSummary['total_decisions'] ?? 0)
                . ' completed historical human governance decision(s) are available.',

            'Decision completion is '
                . ($decisionSummary['decision_completion_percentage'] ?? 0)
                . '%.',

            'Governance action closure is '
                . ($resolutionSummary['closure_percentage'] ?? 0)
                . '%.',

            'Decision consistency status is '
                . (
                    $consistency[
                        'consistency_summary'
                    ]['consistency_status']
                    ?? 'UNKNOWN'
                )
                . '.',

            'Decision-to-outcome alignment is '
                . (
                    $correlation[
                        'correlation_summary'
                    ]['outcome_alignment']
                    ?? 'UNKNOWN'
                )
                . '.',

            'Decision risk level is '
                . ($risk['decision_risk_level'] ?? 'UNKNOWN')
                . '.',

            'Current decision trend classification is '
                . ($trendSummary['trend_direction'] ?? 'UNKNOWN')
                . '.',

            ($recommendationSummary['total_recommendations'] ?? 0)
                . ' human-governance management recommendation(s) are currently generated.',

            'No automatic execution, AI modification, deployment, rollback, or clinical-action authority is enabled by Step 60 intelligence.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities =
            $risk['management_priorities']
            ?? [];

        if (empty($managementPriorities)) {
            $managementPriorities = [
                'Continue human governance review and decision monitoring.',
            ];
        }

        return [
            'audit_available' =>
                true,

            'audit_status' =>
                $auditStatus,

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

            'decision_summary' => [
                'total_actions' =>
                    $decisionSummary['total_actions']
                    ?? 0,

                'completed_decisions' =>
                    $decisionSummary['total_decisions']
                    ?? 0,

                'pending_decisions' =>
                    $decisionSummary['pending_decisions']
                    ?? 0,

                'decision_completion_percentage' =>
                    $decisionSummary[
                        'decision_completion_percentage'
                    ]
                    ?? 0,

                'closure_percentage' =>
                    $resolutionSummary[
                        'closure_percentage'
                    ]
                    ?? 0,
            ],

            'consistency_summary' => [
                'consistency_status' =>
                    $consistency[
                        'consistency_summary'
                    ]['consistency_status']
                    ?? null,

                'consistency_score' =>
                    $consistency[
                        'consistency_summary'
                    ]['consistency_score']
                    ?? 0,

                'contradiction_count' =>
                    $contradictionCount,

                'failed_consistency_checks' =>
                    $failedConsistencyChecks,
            ],

            'outcome_summary' => [
                'outcome_alignment' =>
                    $correlation[
                        'correlation_summary'
                    ]['outcome_alignment']
                    ?? null,

                'consistency_percentage' =>
                    $correlation[
                        'correlation_summary'
                    ]['consistency_percentage']
                    ?? 0,

                'inconsistent_outcomes' =>
                    $inconsistentOutcomes,
            ],

            'risk_summary' => [
                'decision_risk_level' =>
                    $risk['decision_risk_level']
                    ?? null,

                'decision_risk_score' =>
                    $risk['decision_risk_score']
                    ?? null,

                'critical_signals' =>
                    $criticalSignals,

                'high_signals' =>
                    $highSignals,

                'moderate_signals' =>
                    $riskSummary['moderate_signals']
                    ?? 0,

                'advisory_signals' =>
                    $riskSummary['advisory_signals']
                    ?? 0,
            ],

            'trend_summary' => [
                'trend_direction' =>
                    $trendSummary['trend_direction']
                    ?? null,

                'trend_maturity' =>
                    $trendSummary['trend_maturity']
                    ?? null,

                'trend_confidence' =>
                    $trendSummary['trend_confidence']
                    ?? null,
            ],

            'recommendation_summary' => [
                'total_recommendations' =>
                    $recommendationSummary[
                        'total_recommendations'
                    ]
                    ?? 0,

                'critical_recommendations' =>
                    $recommendationSummary[
                        'critical_recommendations'
                    ]
                    ?? 0,

                'high_recommendations' =>
                    $recommendationSummary[
                        'high_recommendations'
                    ]
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary[
                        'top_recommendation_code'
                    ]
                    ?? null,
            ],

            'executive_summary' => [
                'executive_status' =>
                    $executive['executive_status']
                    ?? null,

                'executive_readiness' =>
                    $executive['executive_readiness']
                    ?? null,

                'executive_confidence' =>
                    $executive['executive_confidence']
                    ?? null,
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
            'decision_audit_enabled' =>
                true,

            'audit_is_governance_decision' =>
                false,

            'audit_is_approval' =>
                false,

            'audit_is_rejection' =>
                false,

            'audit_is_resolution' =>
                false,

            'audit_is_execution_authorization' =>
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
                'Governance decision audit consolidates Step 60 human governance decision intelligence for audit and management oversight only. It does not approve, reject, defer, resolve, reprioritize, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}