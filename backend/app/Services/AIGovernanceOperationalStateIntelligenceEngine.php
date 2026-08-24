<?php

namespace App\Services;

use App\Models\AIGovernanceOperationalSnapshot;

class AIGovernanceOperationalStateIntelligenceEngine
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
        | Core Ratios
        |--------------------------------------------------------------------------
        */

        $totalActions = max(
            0,
            (int) $snapshot->total_governance_actions
        );

        $activeActions = max(
            0,
            (int) $snapshot->active_governance_actions
        );

        $closedActions = max(
            0,
            (int) $snapshot->closed_governance_actions
        );

        $activePercentage = $totalActions > 0
            ? round(($activeActions / $totalActions) * 100, 2)
            : 0.0;

        $closedPercentage = $totalActions > 0
            ? round(($closedActions / $totalActions) * 100, 2)
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Attention Pressure
        |--------------------------------------------------------------------------
        */

        $critical = (int) $snapshot->critical_priority_active_actions;
        $high = (int) $snapshot->high_priority_active_actions;
        $pendingReviews = (int) $snapshot->pending_human_reviews;
        $pendingDecisions = (int) $snapshot->pending_human_decisions;
        $evidenceWaiting = (int) $snapshot->evidence_waiting_actions;
        $deferred = (int) $snapshot->deferred_actions;

        $attentionScore =
            ($critical * 30)
            + ($high * 18)
            + ($pendingReviews * 8)
            + ($pendingDecisions * 8)
            + ($evidenceWaiting * 6)
            + ($deferred * 4);

        $attentionScore = min(
            100,
            $attentionScore
        );

        $attentionLevel = match (true) {
            $attentionScore >= 80 =>
                'VERY_HIGH',

            $attentionScore >= 60 =>
                'HIGH',

            $attentionScore >= 35 =>
                'MODERATE',

            $attentionScore > 0 =>
                'LOW',

            default =>
                'CLEAR',
        };

        /*
        |--------------------------------------------------------------------------
        | Operational Maturity
        |--------------------------------------------------------------------------
        */

        $decisionCompletion =
            (float) $snapshot->decision_completion_percentage;

        $closurePercentage =
            (float) $snapshot->action_closure_percentage;

        $consistencyScore =
            (float) ($snapshot->decision_consistency_score ?? 0);

        $operationalMaturityScore = round(
            (
                ($decisionCompletion * 0.35)
                + ($closurePercentage * 0.30)
                + ($consistencyScore * 0.20)
                + ((100 - $attentionScore) * 0.15)
            ),
            2
        );

        $operationalMaturity = match (true) {
            $operationalMaturityScore >= 85 =>
                'ADVANCED',

            $operationalMaturityScore >= 70 =>
                'ESTABLISHED',

            $operationalMaturityScore >= 50 =>
                'DEVELOPING',

            $operationalMaturityScore >= 25 =>
                'EARLY',

            default =>
                'INITIAL',
        };

        /*
        |--------------------------------------------------------------------------
        | Human Governance State
        |--------------------------------------------------------------------------
        */

        $humanGovernanceState = match (true) {
            $critical > 0 =>
                'IMMEDIATE_HUMAN_ATTENTION_REQUIRED',

            $high > 0 =>
                'PRIORITY_HUMAN_ATTENTION_REQUIRED',

            $pendingDecisions > 0 =>
                'HUMAN_DECISION_WORK_PENDING',

            $pendingReviews > 0 =>
                'HUMAN_REVIEW_WORK_PENDING',

            $evidenceWaiting > 0 =>
                'EVIDENCE_DEPENDENT_GOVERNANCE_ACTIVE',

            $deferred > 0 =>
                'DEFERRED_GOVERNANCE_OBSERVATION_ACTIVE',

            $activeActions > 0 =>
                'CONTROLLED_GOVERNANCE_WORK_ACTIVE',

            default =>
                'NO_CURRENT_HUMAN_GOVERNANCE_BACKLOG',
        };

        /*
        |--------------------------------------------------------------------------
        | Attention Concentration
        |--------------------------------------------------------------------------
        */

        $attentionAreas = [];

        if ($critical > 0) {
            $attentionAreas[] = [
                'area' => 'CRITICAL_PRIORITY_ACTIONS',
                'count' => $critical,
                'severity' => 'CRITICAL',
            ];
        }

        if ($high > 0) {
            $attentionAreas[] = [
                'area' => 'HIGH_PRIORITY_ACTIONS',
                'count' => $high,
                'severity' => 'HIGH',
            ];
        }

        if ($pendingDecisions > 0) {
            $attentionAreas[] = [
                'area' => 'PENDING_HUMAN_DECISIONS',
                'count' => $pendingDecisions,
                'severity' => 'MODERATE',
            ];
        }

        if ($pendingReviews > 0) {
            $attentionAreas[] = [
                'area' => 'PENDING_HUMAN_REVIEWS',
                'count' => $pendingReviews,
                'severity' => 'MODERATE',
            ];
        }

        if ($evidenceWaiting > 0) {
            $attentionAreas[] = [
                'area' => 'EVIDENCE_DEPENDENT_ACTIONS',
                'count' => $evidenceWaiting,
                'severity' => 'MODERATE',
            ];
        }

        if ($deferred > 0) {
            $attentionAreas[] = [
                'area' => 'DEFERRED_ACTIONS',
                'count' => $deferred,
                'severity' => 'ADVISORY',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Operational Health
        |--------------------------------------------------------------------------
        */

        $operationalHealth = match (true) {
            $critical > 0 =>
                'CRITICAL_ATTENTION_REQUIRED',

            $snapshot->decision_risk_level === 'HIGH'
            || $snapshot->decision_risk_level === 'CRITICAL' =>
                'ELEVATED_GOVERNANCE_RISK',

            $high > 0 =>
                'CONTROLLED_WITH_PRIORITY_WORK',

            $activeActions > 0 =>
                'CONTROLLED_WITH_ACTIVE_WORK',

            default =>
                'OPERATIONALLY_CLEAR',
        };

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        $operationalConfidence = match (true) {
            $totalActions >= 20 &&
            $decisionCompletion >= 80 =>
                'STRONG',

            $totalActions >= 10 =>
                'MODERATE',

            $totalActions >= 5 =>
                'LIMITED',

            default =>
                'VERY_LIMITED',
        };

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Operational governance intelligence currently represents {$totalActions} governance action(s).",

            "{$activeActions} governance action(s), representing {$activePercentage}% of the current portfolio, remain active.",

            "{$closedActions} governance action(s), representing {$closedPercentage}% of the current portfolio, are closed.",

            "Current operational governance status is {$snapshot->operational_status}.",

            "Current human governance state is {$humanGovernanceState}.",

            "Operational attention score is {$attentionScore} with {$attentionLevel} attention classification.",

            "Current operational maturity score is {$operationalMaturityScore}, classified as {$operationalMaturity}.",

            "Decision completion is {$decisionCompletion}% while action closure is {$closurePercentage}%.",

            "Current decision consistency classification is {$snapshot->decision_consistency_status} with score {$consistencyScore}.",

            "Current governance decision risk level is {$snapshot->decision_risk_level} with risk score {$snapshot->decision_risk_score}.",
        ];

        if ($high > 0) {
            $findings[] =
                "{$high} high-priority governance action(s) remain active and should retain elevated human visibility.";
        }

        if ($pendingDecisions > 0) {
            $findings[] =
                "{$pendingDecisions} governance action(s) remain pending explicit human decision.";
        }

        if ($evidenceWaiting > 0) {
            $findings[] =
                "{$evidenceWaiting} governance action(s) remain dependent on additional evidence.";
        }

        if ($deferred > 0) {
            $findings[] =
                "{$deferred} governance action(s) remain deferred under controlled observation.";
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
                'GOVERNANCE_OPERATIONAL_STATE_INTELLIGENCE_AVAILABLE',

            'operational_snapshot_id' =>
                $snapshot->id,

            'lifecycle_snapshot_id' =>
                $snapshot->lifecycle_snapshot_id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'operational_state' => [
                'operational_status' =>
                    $snapshot->operational_status,

                'operational_health' =>
                    $operationalHealth,

                'human_governance_state' =>
                    $humanGovernanceState,

                'attention_level' =>
                    $attentionLevel,

                'attention_score' =>
                    $attentionScore,

                'operational_maturity' =>
                    $operationalMaturity,

                'operational_maturity_score' =>
                    $operationalMaturityScore,

                'operational_confidence' =>
                    $operationalConfidence,
            ],

            'workload_context' => [
                'governance_workload_status' =>
                    $snapshot->governance_workload_status,

                'total_actions' =>
                    $totalActions,

                'active_actions' =>
                    $activeActions,

                'closed_actions' =>
                    $closedActions,

                'active_percentage' =>
                    $activePercentage,

                'closed_percentage' =>
                    $closedPercentage,
            ],

            'human_attention_context' => [
                'critical_priority_active_actions' =>
                    $critical,

                'high_priority_active_actions' =>
                    $high,

                'pending_human_reviews' =>
                    $pendingReviews,

                'pending_human_decisions' =>
                    $pendingDecisions,

                'evidence_waiting_actions' =>
                    $evidenceWaiting,

                'deferred_actions' =>
                    $deferred,

                'attention_areas' =>
                    $attentionAreas,
            ],

            'decision_context' => [
                'decision_status' =>
                    $snapshot->decision_status,

                'decision_completion_percentage' =>
                    $decisionCompletion,

                'decision_consistency_status' =>
                    $snapshot->decision_consistency_status,

                'decision_consistency_score' =>
                    $consistencyScore,

                'decision_risk_level' =>
                    $snapshot->decision_risk_level,

                'decision_risk_score' =>
                    $snapshot->decision_risk_score,
            ],

            'closure_context' => [
                'action_closure_percentage' =>
                    $closurePercentage,

                'active_action_percentage' =>
                    $activePercentage,

                'closed_action_percentage' =>
                    $closedPercentage,
            ],

            'operational_findings' =>
                $findings,

            'operational_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'operational_state_intelligence_enabled' =>
                true,

            'operational_intelligence_is_governance_decision' =>
                false,

            'operational_intelligence_is_action_resolution' =>
                false,

            'attention_score_changes_priority' =>
                false,

            'attention_score_changes_eligibility' =>
                false,

            'operational_maturity_expands_ai_authority' =>
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
                'Governance operational state intelligence interprets current governance workload, human attention requirements, decision state, closure, consistency, and risk for operational oversight only. It does not approve, reject, defer, resolve, reprioritize, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}