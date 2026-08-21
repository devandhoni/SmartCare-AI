<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceDecisionExecutiveSummaryEngine
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

        foreach ([
            'decision' => $decision,
            'pattern' => $pattern,
            'correlation' => $correlation,
            'consistency' => $consistency,
            'risk' => $risk,
            'trend' => $trend,
            'recommendation' => $recommendation,
        ] as $name => $result) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'analysis_completed' => false,
                    'status' => strtoupper($name) . '_INTELLIGENCE_UNAVAILABLE',
                    'snapshot_id' => $snapshot->id,
                ];
            }
        }

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
        | Executive Status
        |--------------------------------------------------------------------------
        */

        $criticalSignals =
            (int) ($riskSummary['critical_signals'] ?? 0);

        $highSignals =
            (int) ($riskSummary['high_signals'] ?? 0);

        $pendingDecisions =
            (int) ($decisionSummary['pending_decisions'] ?? 0);

        $consistencyScore =
            (float) ($consistencySummary['consistency_score'] ?? 0);

        $outcomeConsistency =
            (float) ($correlationSummary['consistency_percentage'] ?? 0);

        $executiveStatus = match (true) {
            $criticalSignals > 0 =>
                'CRITICAL_GOVERNANCE_ATTENTION_REQUIRED',

            $highSignals > 0 =>
                'HIGH_GOVERNANCE_ATTENTION_REQUIRED',

            $pendingDecisions > 0 =>
                'CONTROLLED_GOVERNANCE_WORK_REMAINS',

            $consistencyScore < 80 =>
                'GOVERNANCE_CONSISTENCY_ATTENTION_REQUIRED',

            $outcomeConsistency < 100 =>
                'DECISION_OUTCOME_ALIGNMENT_ATTENTION_REQUIRED',

            default =>
                'GOVERNANCE_DECISIONS_STABLE',
        };

        /*
        |--------------------------------------------------------------------------
        | Executive Readiness
        |--------------------------------------------------------------------------
        */

        $decisionCompletion =
            (float) (
                $decisionSummary[
                    'decision_completion_percentage'
                ]
                ?? 0
            );

        $closurePercentage =
            (float) (
                $resolutionSummary[
                    'closure_percentage'
                ]
                ?? 0
            );

        $executiveReadiness = match (true) {
            $criticalSignals > 0 =>
                'NOT_READY',

            $highSignals > 0 =>
                'REQUIRES_HIGH_ATTENTION',

            $decisionCompletion < 75 =>
                'PARTIALLY_READY',

            $closurePercentage < 75 =>
                'PARTIALLY_READY',

            default =>
                'READY_WITH_HUMAN_GOVERNANCE',
        };

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        $patternConfidence =
            $pattern[
                'pattern_summary'
            ]['pattern_confidence']
            ?? 'UNKNOWN';

        $consistencyConfidence =
            $consistencySummary[
                'consistency_confidence'
            ]
            ?? 'UNKNOWN';

        $correlationConfidence =
            $correlationSummary[
                'correlation_confidence'
            ]
            ?? 'UNKNOWN';

        $trendConfidence =
            $trendSummary[
                'trend_confidence'
            ]
            ?? 'UNKNOWN';

        $limitedConfidence =
            collect([
                $patternConfidence,
                $consistencyConfidence,
                $correlationConfidence,
                $trendConfidence,
            ])->contains(
                fn ($value) =>
                    in_array(
                        $value,
                        [
                            'EXTREMELY_LIMITED',
                            'VERY_LIMITED',
                            'LIMITED',
                            'INSUFFICIENT_DATA',
                        ],
                        true
                    )
            );

        $executiveConfidence =
            $limitedConfidence
                ? 'LIMITED'
                : 'ESTABLISHED';

        /*
        |--------------------------------------------------------------------------
        | Executive Summary Text
        |--------------------------------------------------------------------------
        */

        $topRecommendation =
            $recommendation['top_recommendation']
            ?? null;

        $executiveSummary =
            'Human governance decision intelligence currently includes '
            . ($decisionSummary['total_decisions'] ?? 0)
            . ' completed decision(s) across '
            . ($decisionSummary['total_actions'] ?? 0)
            . ' governance action(s). Decision completion is '
            . $decisionCompletion
            . '%, while governance action closure is '
            . $closurePercentage
            . '%. Decision consistency is '
            . ($consistencySummary['consistency_status'] ?? 'UNKNOWN')
            . ' with a score of '
            . $consistencyScore
            . '. Decision-to-outcome alignment is '
            . ($correlationSummary['outcome_alignment'] ?? 'UNKNOWN')
            . '. Current governance decision risk is '
            . ($risk['decision_risk_level'] ?? 'UNKNOWN')
            . ' with risk score '
            . ($risk['decision_risk_score'] ?? 0)
            . '. Current trend classification is '
            . ($trendSummary['trend_direction'] ?? 'UNKNOWN')
            . ', but trend confidence remains '
            . $trendConfidence
            . '.';

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Items
        |--------------------------------------------------------------------------
        */

        $attentionItems = [];

        if (
            ($riskSummary['high_priority_active_actions'] ?? 0) > 0
        ) {
            $attentionItems[] =
                ($riskSummary['high_priority_active_actions'] ?? 0)
                . ' high or critical priority governance action(s) remain active.';
        }

        if (
            ($decisionSummary['pending_decisions'] ?? 0) > 0
        ) {
            $attentionItems[] =
                ($decisionSummary['pending_decisions'] ?? 0)
                . ' governance action(s) remain pending human decision.';
        }

        if (
            ($riskSummary['evidence_dependent_actions'] ?? 0) > 0
        ) {
            $attentionItems[] =
                ($riskSummary['evidence_dependent_actions'] ?? 0)
                . ' governance action(s) remain dependent on additional evidence.';
        }

        if (
            ($riskSummary['deferred_actions'] ?? 0) > 0
        ) {
            $attentionItems[] =
                ($riskSummary['deferred_actions'] ?? 0)
                . ' governance action(s) remain deferred.';
        }

        if ($limitedConfidence) {
            $attentionItems[] =
                'Governance decision intelligence confidence remains limited because the completed human decision sample is still small.';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            'Current executive governance decision status is '
                . $executiveStatus
                . '.',

            'Current executive readiness is '
                . $executiveReadiness
                . '.',

            'Human governance decision completion is '
                . $decisionCompletion
                . '%.',

            'Governance action closure is '
                . $closurePercentage
                . '%.',

            'Decision consistency status is '
                . ($consistencySummary['consistency_status'] ?? 'UNKNOWN')
                . ' with score '
                . $consistencyScore
                . '.',

            'Decision-outcome alignment is '
                . ($correlationSummary['outcome_alignment'] ?? 'UNKNOWN')
                . ' at '
                . $outcomeConsistency
                . '%.',

            'Decision risk level is '
                . ($risk['decision_risk_level'] ?? 'UNKNOWN')
                . ' with risk score '
                . ($risk['decision_risk_score'] ?? 0)
                . '.',

            'Decision trend classification is '
                . ($trendSummary['trend_direction'] ?? 'UNKNOWN')
                . ' with '
                . $trendConfidence
                . ' confidence.',

            'Top governance management recommendation is '
                . (
                    $topRecommendation[
                        'recommendation_code'
                    ]
                    ?? 'NONE'
                )
                . '.',
        ];

        if ($limitedConfidence) {
            $findings[] =
                'Executive interpretation should remain evidence-sensitive because current human governance decision history is limited.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_DECISION_EXECUTIVE_SUMMARY_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'executive_status' =>
                $executiveStatus,

            'executive_readiness' =>
                $executiveReadiness,

            'executive_confidence' =>
                $executiveConfidence,

            'executive_summary' =>
                $executiveSummary,

            'decision_context' => [
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
                    $decisionCompletion,

                'closure_percentage' =>
                    $closurePercentage,
            ],

            'consistency_context' => [
                'consistency_status' =>
                    $consistencySummary[
                        'consistency_status'
                    ]
                    ?? null,

                'consistency_score' =>
                    $consistencyScore,

                'contradiction_count' =>
                    $consistencySummary[
                        'contradiction_count'
                    ]
                    ?? 0,

                'consistency_confidence' =>
                    $consistencyConfidence,
            ],

            'outcome_context' => [
                'outcome_alignment' =>
                    $correlationSummary[
                        'outcome_alignment'
                    ]
                    ?? null,

                'consistency_percentage' =>
                    $outcomeConsistency,

                'inconsistent_outcomes' =>
                    $correlationSummary[
                        'inconsistent_outcomes'
                    ]
                    ?? 0,

                'correlation_confidence' =>
                    $correlationConfidence,
            ],

            'risk_context' => [
                'decision_risk_level' =>
                    $risk[
                        'decision_risk_level'
                    ]
                    ?? null,

                'decision_risk_score' =>
                    $risk[
                        'decision_risk_score'
                    ]
                    ?? null,

                'critical_signals' =>
                    $criticalSignals,

                'high_signals' =>
                    $highSignals,

                'moderate_signals' =>
                    $riskSummary[
                        'moderate_signals'
                    ]
                    ?? 0,

                'advisory_signals' =>
                    $riskSummary[
                        'advisory_signals'
                    ]
                    ?? 0,

                'human_review_recommended' =>
                    $risk[
                        'human_review_recommended'
                    ]
                    ?? false,
            ],

            'trend_context' => [
                'trend_direction' =>
                    $trendSummary[
                        'trend_direction'
                    ]
                    ?? null,

                'trend_maturity' =>
                    $trendSummary[
                        'trend_maturity'
                    ]
                    ?? null,

                'trend_confidence' =>
                    $trendConfidence,

                'reviewed_action_closure_percentage' =>
                    $trendSummary[
                        'reviewed_action_closure_percentage'
                    ]
                    ?? 0,
            ],

            'recommendation_context' => [
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

                'top_recommendation' =>
                    $topRecommendation,
            ],

            'executive_attention_items' =>
                $attentionItems,

            'executive_findings' =>
                $findings,

            'executive_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'decision_executive_summary_enabled' =>
                true,

            'executive_summary_is_governance_decision' =>
                false,

            'executive_status_is_approval' =>
                false,

            'executive_status_is_rejection' =>
                false,

            'executive_status_is_resolution' =>
                false,

            'executive_readiness_is_execution_authorization' =>
                false,

            'executive_summary_changes_action_state' =>
                false,

            'executive_summary_changes_priority' =>
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
                'Governance decision executive summary intelligence consolidates recorded human governance decision status, consistency, risk, trends, and management recommendations for executive oversight only. It does not approve, reject, defer, resolve, reprioritize, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}