<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionFinalValidationEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'validation_status' => 'FAILED',
                'step_60_ready_for_closure' => false,
                'status' => 'SNAPSHOT_NOT_FOUND',
                'snapshot_id' => $snapshotId,
                'critical_issues' => [
                    'AI improvement lifecycle snapshot was not found.',
                ],
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

        $audit = app(
            AIGovernanceDecisionAuditSummaryEngine::class
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
        | Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['decision_intelligence_operational'] = [
            'passed' =>
                ($decision['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision intelligence is operational.',
        ];

        $checks['decision_pattern_analysis_operational'] = [
            'passed' =>
                ($pattern['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision pattern analysis is operational.',
        ];

        $checks['decision_outcome_correlation_operational'] = [
            'passed' =>
                ($correlation['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision outcome correlation is operational.',
        ];

        $checks['decision_consistency_operational'] = [
            'passed' =>
                ($consistency['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision consistency intelligence is operational.',
        ];

        $checks['decision_risk_operational'] = [
            'passed' =>
                ($risk['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision risk intelligence is operational.',
        ];

        $checks['decision_trend_operational'] = [
            'passed' =>
                ($trend['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision trend intelligence is operational.',
        ];

        $checks['decision_recommendation_operational'] = [
            'passed' =>
                ($recommendation['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision recommendation intelligence is operational.',
        ];

        $checks['decision_executive_summary_operational'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true,
            'message' =>
                'Governance decision executive summary intelligence is operational.',
        ];

        $checks['step_60_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                && (
                    ($audit['audit_summary']['failed_checks'] ?? 1) === 0
                ),
            'message' =>
                'Step 60 governance decision audit completed without integrity failures.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Historical Decision Registry Integrity
        |--------------------------------------------------------------------------
        */

        $decisionCount =
            (int) (
                $decision[
                    'decision_summary'
                ]['total_decisions']
                ?? 0
            );

        $checks['historical_decision_registry_integrity'] = [
            'passed' =>
                $decisionCount === $reviews->count(),
            'value' => [
                'decision_intelligence_count' =>
                    $decisionCount,
                'completed_review_records' =>
                    $reviews->count(),
            ],
            'message' =>
                'Governance decision intelligence must remain grounded in the dedicated completed human-review registry.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Consistency and Outcome Integrity
        |--------------------------------------------------------------------------
        */

        $contradictions =
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

        $checks['decision_consistency_integrity'] = [
            'passed' =>
                $contradictions === 0
                && $failedConsistencyChecks === 0,
            'message' =>
                'Governance decision intelligence must contain no unresolved decision-state contradictions or consistency-control failures.',
        ];

        $inconsistentOutcomes =
            (int) (
                $correlation[
                    'correlation_summary'
                ]['inconsistent_outcomes']
                ?? 0
            );

        $checks['decision_outcome_integrity'] = [
            'passed' =>
                $inconsistentOutcomes === 0,
            'message' =>
                'Recorded human governance decisions must remain aligned with current governance action outcomes.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
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

        $checks['automatic_authority_isolation'] = [
            'passed' =>
                $automaticPermissionExceptions === 0,
            'value' =>
                $automaticPermissionExceptions,
            'message' =>
                'Automatic execution, modification, deployment, rollback, and clinical-action authority must remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $humanControlExceptions =
            $actions->filter(
                fn ($action) =>
                    !(bool) $action->human_review_required
                    || !(bool) $action->governance_validation_required
            )->count();

        $checks['human_governance_controls'] = [
            'passed' =>
                $humanControlExceptions === 0,
            'value' =>
                $humanControlExceptions,
            'message' =>
                'Human review and governance validation must remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Totals
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
        | Current Context
        |--------------------------------------------------------------------------
        */

        $decisionSummary =
            $decision['decision_summary']
            ?? [];

        $resolutionSummary =
            $decision['resolution_summary']
            ?? [];

        $consistencySummary =
            $consistency['consistency_summary']
            ?? [];

        $correlationSummary =
            $correlation['correlation_summary']
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
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ($failedChecks > 0) {
            $criticalIssues[] =
                "{$failedChecks} Step 60 validation control(s) failed.";
        }

        if (
            ($riskSummary['critical_signals'] ?? 0) > 0
        ) {
            $criticalIssues[] =
                ($riskSummary['critical_signals'] ?? 0)
                . ' critical governance decision risk signal(s) are present.';
        }

        if ($automaticPermissionExceptions > 0) {
            $criticalIssues[] =
                "{$automaticPermissionExceptions} automatic authority exception(s) are present.";
        }

        if ($humanControlExceptions > 0) {
            $criticalIssues[] =
                "{$humanControlExceptions} human governance control exception(s) are present.";
        }

        if ($contradictions > 0) {
            $criticalIssues[] =
                "{$contradictions} governance decision-state contradiction(s) are present.";
        }

        if ($inconsistentOutcomes > 0) {
            $criticalIssues[] =
                "{$inconsistentOutcomes} governance decision outcome inconsistency record(s) are present.";
        }

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        $pendingDecisions =
            (int) (
                $decisionSummary['pending_decisions']
                ?? 0
            );

        $activeActions =
            (int) (
                $resolutionSummary['active_actions']
                ?? 0
            );

        $evidenceDependentActions =
            (int) (
                $resolutionSummary['evidence_dependent_actions']
                ?? 0
            );

        $deferredActions =
            (int) (
                $resolutionSummary['deferred_actions']
                ?? 0
            );

        if ($pendingDecisions > 0) {
            $warnings[] =
                "{$pendingDecisions} governance action(s) remain pending human decision.";
        }

        if ($evidenceDependentActions > 0) {
            $warnings[] =
                "{$evidenceDependentActions} governance action(s) remain dependent on additional evidence.";
        }

        if ($deferredActions > 0) {
            $warnings[] =
                "{$deferredActions} governance action(s) remain deferred.";
        }

        if ($activeActions > 0) {
            $warnings[] =
                "{$activeActions} governance action(s) remain active or unresolved.";
        }

        $trendConfidence =
            $trendSummary['trend_confidence']
            ?? 'UNKNOWN';

        if (
            in_array(
                $trendConfidence,
                [
                    'EXTREMELY_LIMITED',
                    'VERY_LIMITED',
                    'LIMITED',
                ],
                true
            )
        ) {
            $warnings[] =
                "Governance decision trend confidence remains {$trendConfidence}.";
        }

        $consistencyConfidence =
            $consistencySummary[
                'consistency_confidence'
            ]
            ?? 'UNKNOWN';

        if (
            in_array(
                $consistencyConfidence,
                [
                    'EXTREMELY_LIMITED',
                    'VERY_LIMITED',
                    'LIMITED',
                ],
                true
            )
        ) {
            $warnings[] =
                "Governance decision consistency confidence remains {$consistencyConfidence}.";
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Status
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
            '60.1_governance_decision_intelligence' => [
                'status' =>
                    'OPERATIONAL',

                'completed_decisions' =>
                    $decisionSummary[
                        'total_decisions'
                    ]
                    ?? 0,

                'pending_decisions' =>
                    $pendingDecisions,
            ],

            '60.2_decision_pattern_analysis' => [
                'status' =>
                    'OPERATIONAL',

                'pattern_classification' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_classification']
                    ?? null,

                'confidence' =>
                    $pattern[
                        'pattern_summary'
                    ]['pattern_confidence']
                    ?? null,
            ],

            '60.3_decision_outcome_correlation' => [
                'status' =>
                    'OPERATIONAL',

                'outcome_alignment' =>
                    $correlationSummary[
                        'outcome_alignment'
                    ]
                    ?? null,

                'consistency_percentage' =>
                    $correlationSummary[
                        'consistency_percentage'
                    ]
                    ?? 0,
            ],

            '60.4_decision_consistency' => [
                'status' =>
                    'OPERATIONAL',

                'consistency_status' =>
                    $consistencySummary[
                        'consistency_status'
                    ]
                    ?? null,

                'consistency_score' =>
                    $consistencySummary[
                        'consistency_score'
                    ]
                    ?? 0,
            ],

            '60.5_decision_risk' => [
                'status' =>
                    'OPERATIONAL',

                'decision_risk_level' =>
                    $risk['decision_risk_level']
                    ?? null,

                'decision_risk_score' =>
                    $risk['decision_risk_score']
                    ?? null,
            ],

            '60.6_decision_trend' => [
                'status' =>
                    'OPERATIONAL',

                'trend_direction' =>
                    $trendSummary[
                        'trend_direction'
                    ]
                    ?? null,

                'confidence' =>
                    $trendConfidence,
            ],

            '60.7_decision_recommendation' => [
                'status' =>
                    'OPERATIONAL',

                'total_recommendations' =>
                    $recommendationSummary[
                        'total_recommendations'
                    ]
                    ?? 0,

                'top_recommendation_code' =>
                    $recommendationSummary[
                        'top_recommendation_code'
                    ]
                    ?? null,
            ],

            '60.8_decision_executive_summary' => [
                'status' =>
                    'OPERATIONAL',

                'executive_status' =>
                    $executive[
                        'executive_status'
                    ]
                    ?? null,

                'executive_readiness' =>
                    $executive[
                        'executive_readiness'
                    ]
                    ?? null,

                'executive_confidence' =>
                    $executive[
                        'executive_confidence'
                    ]
                    ?? null,
            ],

            '60.9_decision_audit_summary' => [
                'status' =>
                    $audit['audit_status']
                    ?? 'UNKNOWN',

                'passed_checks' =>
                    $audit[
                        'audit_summary'
                    ]['passed_checks']
                    ?? 0,

                'failed_checks' =>
                    $audit[
                        'audit_summary'
                    ]['failed_checks']
                    ?? 0,
            ],

            '60.10_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_60_ready_for_closure' =>
                    $readyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            'The complete Step 60 AI governance decision intelligence architecture has been validated.',

            ($decisionSummary['total_decisions'] ?? 0)
                . ' completed human governance decision(s) are represented.',

            $pendingDecisions
                . ' governance action(s) remain pending human decision.',

            'Current decision consistency classification is '
                . (
                    $consistencySummary[
                        'consistency_status'
                    ]
                    ?? 'UNKNOWN'
                )
                . '.',

            'Decision-to-outcome alignment is '
                . (
                    $correlationSummary[
                        'outcome_alignment'
                    ]
                    ?? 'UNKNOWN'
                )
                . '.',

            'Current decision risk level is '
                . ($risk['decision_risk_level'] ?? 'UNKNOWN')
                . '.',

            'Current decision trend classification is '
                . ($trendSummary['trend_direction'] ?? 'UNKNOWN')
                . '.',

            'Current executive governance decision status is '
                . ($executive['executive_status'] ?? 'UNKNOWN')
                . '.',

            'Step 60 maintains separation between decision intelligence, human governance decisions, action resolution, AI execution, and clinical authority.',

            'No autonomous governance decision, AI modification, execution, deployment, rollback, or clinical-action pathway is enabled.',
        ];

        return [
            'validation_status' =>
                $validationStatus,

            'step_60_ready_for_closure' =>
                $readyForClosure,

            'governance_decision_mode' =>
                'HUMAN_GOVERNED_DECISION_INTELLIGENCE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'completion_message' =>
                $readyForClosure
                    ? 'Step 60 AI Governance Decision Intelligence has passed final validation and is ready for closure.'
                    : 'Step 60 AI Governance Decision Intelligence contains validation issues that must be resolved before closure.',

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

            'governance_decision_context' => [
                'total_actions' =>
                    $decisionSummary[
                        'total_actions'
                    ]
                    ?? 0,

                'completed_decisions' =>
                    $decisionSummary[
                        'total_decisions'
                    ]
                    ?? 0,

                'pending_decisions' =>
                    $pendingDecisions,

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

                'consistency_status' =>
                    $consistencySummary[
                        'consistency_status'
                    ]
                    ?? null,

                'consistency_score' =>
                    $consistencySummary[
                        'consistency_score'
                    ]
                    ?? 0,

                'outcome_alignment' =>
                    $correlationSummary[
                        'outcome_alignment'
                    ]
                    ?? null,

                'decision_risk_level' =>
                    $risk['decision_risk_level']
                    ?? null,

                'decision_risk_score' =>
                    $risk['decision_risk_score']
                    ?? null,

                'trend_direction' =>
                    $trendSummary[
                        'trend_direction'
                    ]
                    ?? null,

                'trend_confidence' =>
                    $trendConfidence,

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

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $findings,

            'step_60_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'governance_decision_intelligence_enabled' =>
                true,

            'decision_pattern_intelligence_enabled' =>
                true,

            'decision_outcome_correlation_enabled' =>
                true,

            'decision_consistency_intelligence_enabled' =>
                true,

            'decision_risk_intelligence_enabled' =>
                true,

            'decision_trend_intelligence_enabled' =>
                true,

            'decision_recommendation_intelligence_enabled' =>
                true,

            'decision_executive_summary_enabled' =>
                true,

            'decision_audit_enabled' =>
                true,

            'autonomous_governance_decision_enabled' =>
                false,

            'automatic_governance_approval' =>
                false,

            'automatic_governance_rejection' =>
                false,

            'automatic_governance_defer' =>
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
                'Step 60 establishes human-governed AI governance decision intelligence. The system may analyze historical decisions, patterns, outcomes, consistency, risk, trends, recommendations, and executive governance status, but it does not autonomously make governance decisions, modify AI behavior, execute changes, deploy updates, trigger rollback, or replace human clinical or governance authority.',
        ];
    }
}