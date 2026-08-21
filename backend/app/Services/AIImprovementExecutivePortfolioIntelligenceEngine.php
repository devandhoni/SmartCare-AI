<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;

class AIImprovementExecutivePortfolioIntelligenceEngine
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

        if (
            (bool) $snapshot->automatic_change_allowed ||
            (bool) $snapshot->automatic_deployment_allowed ||
            (bool) $snapshot->automatic_rollback_allowed ||
            (bool) $snapshot->automatic_clinical_action_allowed
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Executive portfolio intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Intelligence
        |--------------------------------------------------------------------------
        */

        $lifecycle = app(
            AIImprovementLifecycleStateIntelligenceEngine::class
        )->analyze($snapshot->id);

        $governance = app(
            AIImprovementGovernanceProgressIntelligenceEngine::class
        )->analyze($snapshot->id);

        $evidence = app(
            AIImprovementEvidenceMaturityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $outcome = app(
            AIImprovementOutcomeIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIImprovementRiskExceptionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $sourceResults = [
            'lifecycle' => $lifecycle,
            'governance' => $governance,
            'evidence' => $evidence,
            'outcome' => $outcome,
            'risk' => $risk,
        ];

        foreach ($sourceResults as $source => $result) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'analysis_completed' => false,
                    'status' => 'SOURCE_INTELLIGENCE_INCOMPLETE',
                    'message' => "Executive portfolio intelligence could not be completed because {$source} intelligence is unavailable.",
                    'snapshot_id' => $snapshot->id,
                    'failed_source' => $source,
                    'source_result' => $result,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Core Portfolio Context
        |--------------------------------------------------------------------------
        */

        $lifecycleState =
            $lifecycle['current_lifecycle_state'] ?? [];

        $governanceSummary =
            $governance['candidate_review_summary'] ?? [];

        $evidenceSummary =
            $evidence['learning_evidence_summary'] ?? [];

        $outcomeSummary =
            $outcome['consolidated_outcome_summary'] ?? [];

        $riskSummary =
            $risk['exception_summary'] ?? [];

        $lifecycleStage = strtoupper(
            (string) (
                $lifecycleState['lifecycle_stage']
                ?? 'UNKNOWN'
            )
        );

        $lifecycleMaturity = strtoupper(
            (string) (
                $lifecycleState['lifecycle_maturity']
                ?? 'UNKNOWN'
            )
        );

        $governanceHealth = strtoupper(
            (string) (
                $governance['governance_health']
                ?? 'UNKNOWN'
            )
        );

        $evidenceMaturity = strtoupper(
            (string) (
                $evidence['overall_evidence_maturity']
                ?? 'UNKNOWN'
            )
        );

        $evidenceConfidence = strtoupper(
            (string) (
                $evidence['overall_confidence']
                ?? 'UNKNOWN'
            )
        );

        $overallOutcome = strtoupper(
            (string) (
                $outcome['overall_outcome']
                ?? 'UNKNOWN'
            )
        );

        $outcomeConfidence = strtoupper(
            (string) (
                $outcome['outcome_confidence']
                ?? 'UNKNOWN'
            )
        );

        $portfolioRiskLevel = strtoupper(
            (string) (
                $risk['portfolio_risk_level']
                ?? 'UNKNOWN'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Executive Health Score
        |--------------------------------------------------------------------------
        |
        | Portfolio management score only. Not clinical performance.
        |--------------------------------------------------------------------------
        */

        $scoreComponents = [
            'lifecycle_progress' => (float) (
                $lifecycleState['lifecycle_progress_score']
                ?? 0
            ),

            'capability_coverage' => (float) (
                $lifecycle['capability_summary']['coverage_percentage']
                ?? 0
            ),

            'governance_integrity' =>
                ($governance['governance_integrity']['failed_checks'] ?? 1) === 0
                    ? 100
                    : 0,

            'evidence_depth' => (float) (
                $evidence['evidence_depth_score']
                ?? 0
            ),

            'outcome_score' => (float) (
                $outcome['outcome_score']
                ?? 0
            ),

            'risk_control' =>
                ($riskSummary['critical_exceptions'] ?? 0) === 0
                && ($riskSummary['high_exceptions'] ?? 0) === 0
                    ? 100
                    : 0,
        ];

        $executiveHealthScore = round(
            collect($scoreComponents)->avg(),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Portfolio Status
        |--------------------------------------------------------------------------
        */

        if (
            ($riskSummary['critical_exceptions'] ?? 0) > 0
        ) {
            $portfolioStatus =
                'CRITICAL_ATTENTION_REQUIRED';
        } elseif (
            ($riskSummary['high_exceptions'] ?? 0) > 0
        ) {
            $portfolioStatus =
                'HIGH_PRIORITY_REVIEW_REQUIRED';
        } elseif (
            $overallOutcome === 'NEGATIVE'
        ) {
            $portfolioStatus =
                'OUTCOME_REVIEW_REQUIRED';
        } elseif (
            $overallOutcome === 'POSITIVE'
            &&
            in_array(
                $portfolioRiskLevel,
                ['LOW', 'LOW_WITH_ADVISORIES'],
                true
            )
        ) {
            $portfolioStatus =
                'HEALTHY_CONTROLLED_PROGRESS';
        } else {
            $portfolioStatus =
                'CONTINUE_GOVERNED_MONITORING';
        }

        /*
        |--------------------------------------------------------------------------
        | Strategic Readiness
        |--------------------------------------------------------------------------
        */

        if (
            $evidenceMaturity === 'ESTABLISHED'
            &&
            $overallOutcome === 'POSITIVE'
            &&
            $portfolioRiskLevel === 'LOW'
        ) {
            $strategicReadiness =
                'STRONG';
        } elseif (
            in_array(
                $evidenceMaturity,
                ['EARLY', 'DEVELOPING'],
                true
            )
            &&
            $overallOutcome === 'POSITIVE'
        ) {
            $strategicReadiness =
                'PROMISING_BUT_EARLY';
        } else {
            $strategicReadiness =
                'LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */

        $executiveSummary =
            "The AI improvement portfolio is currently at {$lifecycleStage} with {$lifecycleMaturity} lifecycle maturity. "
            . "Governance health is {$governanceHealth}. "
            . "Evidence maturity is {$evidenceMaturity} with {$evidenceConfidence} confidence. "
            . "Current governed improvement outcome is {$overallOutcome} with {$outcomeConfidence} outcome confidence. "
            . "Portfolio risk is {$portfolioRiskLevel}.";

        /*
        |--------------------------------------------------------------------------
        | Executive Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Lifecycle architecture coverage is {$lifecycle['capability_summary']['coverage_percentage']}%.",
            "Current lifecycle stage is {$lifecycleStage}.",
            "Current governance health is {$governanceHealth}.",
            "Current evidence maturity is {$evidenceMaturity} with {$evidenceConfidence} confidence.",
            "Current improvement outcome is {$overallOutcome} with an outcome score of {$outcome['outcome_score']}.",
            "Current portfolio risk level is {$portfolioRiskLevel}.",
            "Executive portfolio health score is {$executiveHealthScore}.",
        ];

        if ($evidenceMaturity === 'EARLY') {
            $findings[] =
                'The improvement lifecycle is operationally advanced, but the evidence base remains early.';
        }

        if ($overallOutcome === 'POSITIVE') {
            $findings[] =
                'Available governed test, execution, and monitoring outcomes are currently directionally positive.';
        }

        if (
            ($riskSummary['critical_exceptions'] ?? 0) === 0
            &&
            ($riskSummary['high_exceptions'] ?? 0) === 0
        ) {
            $findings[] =
                'No critical or high-severity portfolio exception is currently detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Priorities
        |--------------------------------------------------------------------------
        */

        $priorities = [];

        if (($governanceSummary['pending_reviews'] ?? 0) > 0) {
            $priorities[] =
                'Continue human governance review of pending improvement candidates.';
        }

        if ($evidenceMaturity === 'EARLY') {
            $priorities[] =
                'Increase validated learning and longitudinal monitoring evidence before treating improvement performance as mature.';
        }

        if (
            ($riskSummary['advisory_exceptions'] ?? 0) > 0
        ) {
            $priorities[] =
                'Continue resolving portfolio advisories while maintaining current human governance controls.';
        }

        if ($snapshot->active_monitoring_records > 0) {
            $priorities[] =
                'Continue longitudinal monitoring of verified improvements.';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Items
        |--------------------------------------------------------------------------
        */

        $attentionItems = [];

        if (($riskSummary['critical_exceptions'] ?? 0) > 0) {
            $attentionItems[] =
                'Critical improvement exceptions require immediate governance escalation.';
        }

        if (($riskSummary['high_exceptions'] ?? 0) > 0) {
            $attentionItems[] =
                'High-severity improvement exceptions require prioritized governance review.';
        }

        if (($governanceSummary['pending_reviews'] ?? 0) > 0) {
            $attentionItems[] =
                ($governanceSummary['pending_reviews'])
                . ' candidate governance review(s) remain pending.';
        }

        if ($evidenceConfidence === 'LIMITED') {
            $attentionItems[] =
                'Evidence confidence remains limited despite full lifecycle architecture coverage.';
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
                'EXECUTIVE_PORTFOLIO_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'portfolio_status' =>
                $portfolioStatus,

            'strategic_readiness' =>
                $strategicReadiness,

            'executive_health_score' =>
                $executiveHealthScore,

            'executive_summary' =>
                $executiveSummary,

            'portfolio_context' => [
                'lifecycle_stage' =>
                    $lifecycleStage,

                'lifecycle_maturity' =>
                    $lifecycleMaturity,

                'lifecycle_progress_score' =>
                    $lifecycleState[
                        'lifecycle_progress_score'
                    ] ?? null,

                'capability_coverage_percentage' =>
                    $lifecycle[
                        'capability_summary'
                    ]['coverage_percentage'] ?? null,

                'governance_health' =>
                    $governanceHealth,

                'overall_evidence_maturity' =>
                    $evidenceMaturity,

                'evidence_confidence' =>
                    $evidenceConfidence,

                'overall_outcome' =>
                    $overallOutcome,

                'outcome_confidence' =>
                    $outcomeConfidence,

                'portfolio_risk_level' =>
                    $portfolioRiskLevel,
            ],

            'portfolio_counts' => [
                'learning_evidence' =>
                    $evidenceSummary[
                        'total_evaluated_evidence'
                    ] ?? 0,

                'candidate_reviews' =>
                    $governanceSummary[
                        'total_reviews'
                    ] ?? 0,

                'pending_candidate_reviews' =>
                    $governanceSummary[
                        'pending_reviews'
                    ] ?? 0,

                'controlled_tests' =>
                    $snapshot->total_controlled_tests,

                'implementation_reviews' =>
                    $snapshot->total_implementation_reviews,

                'controlled_executions' =>
                    $snapshot->total_controlled_executions,

                'monitoring_records' =>
                    $snapshot->total_monitoring_records,

                'active_monitoring_records' =>
                    $snapshot->active_monitoring_records,

                'total_outcome_signals' =>
                    $outcomeSummary[
                        'total_outcome_signals'
                    ] ?? 0,

                'total_exceptions' =>
                    $riskSummary[
                        'total_exceptions'
                    ] ?? 0,
            ],

            'score_components' =>
                $scoreComponents,

            'executive_findings' =>
                $findings,

            'executive_priorities' =>
                $priorities,

            'executive_attention_items' =>
                $attentionItems,

            'executive_guardrails' => [
                'executive_intelligence_is_ai_authority' =>
                    false,

                'executive_status_is_implementation_approval' =>
                    false,

                'executive_status_is_deployment_authorization' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'human_governance_required' =>
                    true,

                'message' =>
                    'Executive portfolio intelligence summarizes improvement lifecycle health, evidence, outcomes, governance, and risk for management review only. It does not authorize autonomous AI changes, execution, deployment, rollback, or clinical action.',
            ],
        ];
    }
}