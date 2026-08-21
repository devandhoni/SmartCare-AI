<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;
use Illuminate\Support\Facades\DB;

class AIGovernanceActionPriorityIntelligenceEngine
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
                'message' => 'Governance action priority intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $risk = app(
            AIImprovementRiskExceptionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $evidence = app(
            AIImprovementEvidenceMaturityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $governance = app(
            AIImprovementGovernanceProgressIntelligenceEngine::class
        )->analyze($snapshot->id);

        foreach (
            [
                'risk' => $risk,
                'evidence' => $evidence,
                'governance' => $governance,
            ] as $source => $result
        ) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'analysis_completed' => false,
                    'status' => 'SOURCE_INTELLIGENCE_INCOMPLETE',
                    'message' => "Priority intelligence could not continue because {$source} intelligence is unavailable.",
                    'snapshot_id' => $snapshot->id,
                    'failed_source' => $source,
                ];
            }
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->whereIn('action_status', [
                'OPEN',
                'PENDING_REVIEW',
                'UNDER_REVIEW',
                'APPROVED',
                'DEFERRED',
            ])
            ->orderBy('id')
            ->get();

        if ($actions->isEmpty()) {
            return [
                'analysis_completed' => true,
                'status' => 'NO_GOVERNANCE_ACTIONS_AVAILABLE',
                'snapshot_id' => $snapshot->id,
                'snapshot_scope' => $snapshot->snapshot_scope,
                'resident_id' => $snapshot->resident_id,
                'priority_summary' => [
                    'total_actions' => 0,
                    'critical_actions' => 0,
                    'high_actions' => 0,
                    'moderate_actions' => 0,
                    'advisory_actions' => 0,
                    'highest_priority_score' => 0,
                ],
                'prioritized_actions' => [],
                'priority_guardrails' => $this->priorityGuardrails(),
            ];
        }

        $portfolioRisk = strtoupper(
            (string) (
                $risk['portfolio_risk_level']
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

        $pendingReviews = (int) (
            $governance['candidate_review_summary']['pending_reviews']
            ?? 0
        );

        $prioritized = [];

        DB::transaction(function () use (
            $actions,
            $portfolioRisk,
            $evidenceMaturity,
            $evidenceConfidence,
            $pendingReviews,
            $risk,
            &$prioritized
        ) {
            foreach ($actions as $action) {
                /*
                |--------------------------------------------------------------------------
                | Immutable Base Priority
                |--------------------------------------------------------------------------
                |
                | Important:
                | Do NOT use the currently stored priority_score as the base.
                | Doing so causes repeated calls to keep increasing priority.
                |
                | The base score comes from the original action type.
                |--------------------------------------------------------------------------
                */

                $baseScore = $this->getBasePriorityScore(
                    $action->action_code
                );

                $score = $baseScore;

                $factors = [];

                /*
                |--------------------------------------------------------------------------
                | Action-Specific Priority Factors
                |--------------------------------------------------------------------------
                */

                switch ($action->action_code) {
                    case 'REVIEW_PENDING_IMPROVEMENT_CANDIDATES':
                        if ($pendingReviews >= 10) {
                            $score += 20;

                            $factors[] =
                                'Large pending governance-review backlog increases operational priority.';
                        } elseif ($pendingReviews >= 5) {
                            $score += 10;

                            $factors[] =
                                'Pending governance-review backlog is significant.';
                        } elseif ($pendingReviews > 0) {
                            $score += 5;

                            $factors[] =
                                'Pending governance reviews require continued human attention.';
                        } else {
                            $factors[] =
                                'No material pending governance-review backlog is currently present.';
                        }

                        break;

                    case 'REASSESS_DEFERRED_IMPROVEMENT_CANDIDATES':
                        if (
                            in_array(
                                $evidenceMaturity,
                                [
                                    'ESTABLISHED',
                                    'DEVELOPING',
                                ],
                                true
                            )
                        ) {
                            $score += 15;

                            $factors[] =
                                'Improved evidence maturity may justify reassessment of deferred candidates.';
                        } else {
                            $factors[] =
                                'Deferred candidates should remain lower priority while evidence maturity is limited.';
                        }

                        break;

                    case 'COLLECT_ADDITIONAL_LEARNING_EVIDENCE':
                        if (
                            in_array(
                                $evidenceMaturity,
                                [
                                    'INSUFFICIENT',
                                    'FOUNDATIONAL',
                                    'EARLY',
                                ],
                                true
                            )
                        ) {
                            $score += 10;

                            $factors[] =
                                'Current evidence maturity remains below established levels.';
                        }

                        if (
                            in_array(
                                $evidenceConfidence,
                                [
                                    'LIMITED',
                                    'VERY LIMITED',
                                ],
                                true
                            )
                        ) {
                            $score += 5;

                            $factors[] =
                                'Limited evidence confidence increases the need for additional validated evidence.';
                        }

                        break;

                    case 'STRENGTHEN_IMPROVEMENT_EVIDENCE_CONFIDENCE':
                        if ($evidenceConfidence === 'VERY LIMITED') {
                            $score += 15;

                            $factors[] =
                                'Very limited confidence materially increases evidence-strengthening priority.';
                        } elseif ($evidenceConfidence === 'LIMITED') {
                            $score += 5;

                            $factors[] =
                                'Evidence confidence remains limited.';
                        } else {
                            $factors[] =
                                'Evidence confidence does not currently require additional priority escalation.';
                        }

                        break;

                    case 'CONTINUE_LONGITUDINAL_MONITORING':
                        $materialRegressionRecords = (int) (
                            $risk[
                                'monitoring_exceptions'
                            ]['material_regression_records']
                            ?? 0
                        );

                        $safetyConcernRecords = (int) (
                            $risk[
                                'monitoring_exceptions'
                            ]['safety_concern_records']
                            ?? 0
                        );

                        $limitedConfidenceRecords = (int) (
                            $risk[
                                'monitoring_exceptions'
                            ]['limited_confidence_records']
                            ?? 0
                        );

                        if (
                            $materialRegressionRecords > 0
                            ||
                            $safetyConcernRecords > 0
                        ) {
                            $score += 30;

                            $factors[] =
                                'Monitoring contains material regression or safety concerns.';
                        } elseif ($limitedConfidenceRecords > 0) {
                            $score += 10;

                            $factors[] =
                                'Monitoring confidence remains limited and requires continued evidence collection.';
                        } else {
                            $score += 5;

                            $factors[] =
                                'Active monitoring should continue until long-term evidence matures.';
                        }

                        break;

                    case 'REVIEW_IMPROVEMENT_PORTFOLIO_ADVISORIES':
                        $advisories = (int) (
                            $risk[
                                'exception_summary'
                            ]['advisory_exceptions']
                            ?? 0
                        );

                        if ($advisories >= 10) {
                            $score += 15;

                            $factors[] =
                                'High advisory volume increases portfolio review priority.';
                        } elseif ($advisories > 0) {
                            $score += 5;

                            $factors[] =
                                'Active portfolio advisories require continued governance visibility.';
                        } else {
                            $factors[] =
                                'No active portfolio advisories currently require priority escalation.';
                        }

                        break;

                    case 'ESCALATE_CRITICAL_IMPROVEMENT_EXCEPTIONS':
                        $score = max($score, 100);

                        $factors[] =
                            'Critical improvement exceptions require immediate governance escalation.';

                        break;

                    case 'REVIEW_HIGH_SEVERITY_IMPROVEMENT_RISKS':
                        $score = max($score, 85);

                        $factors[] =
                            'High-severity improvement risks require prioritized human review.';

                        break;

                    default:
                        $factors[] =
                            'No action-specific priority rule is currently configured; default governance priority applies.';

                        break;
                }

                /*
                |--------------------------------------------------------------------------
                | Portfolio-Wide Risk Adjustment
                |--------------------------------------------------------------------------
                */

                if ($portfolioRisk === 'CRITICAL') {
                    $score += 20;

                    $factors[] =
                        'Critical portfolio risk increases governance urgency.';
                } elseif ($portfolioRisk === 'HIGH') {
                    $score += 15;

                    $factors[] =
                        'High portfolio risk increases governance urgency.';
                } elseif ($portfolioRisk === 'MODERATE') {
                    $score += 5;

                    $factors[] =
                        'Moderate portfolio risk increases governance attention.';
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize Score
                |--------------------------------------------------------------------------
                */

                $score = min(
                    100,
                    max(0, $score)
                );

                /*
                |--------------------------------------------------------------------------
                | Priority Classification
                |--------------------------------------------------------------------------
                */

                if ($score >= 90) {
                    $level = 'CRITICAL';
                } elseif ($score >= 70) {
                    $level = 'HIGH';
                } elseif ($score >= 50) {
                    $level = 'MODERATE';
                } else {
                    $level = 'ADVISORY';
                }

                /*
                |--------------------------------------------------------------------------
                | Preserve Previous Stored State for Audit
                |--------------------------------------------------------------------------
                */

                $previousLevel =
                    $action->priority_level;

                $previousScore =
                    (int) $action->priority_score;

                /*
                |--------------------------------------------------------------------------
                | Priority Intelligence Context
                |--------------------------------------------------------------------------
                */

                $priorityContext = [
                    'priority_version' =>
                        '59.3',

                    'base_priority_score' =>
                        $baseScore,

                    'previous_priority_level' =>
                        $previousLevel,

                    'previous_priority_score' =>
                        $previousScore,

                    'reassessed_priority_level' =>
                        $level,

                    'reassessed_priority_score' =>
                        $score,

                    'portfolio_risk_level' =>
                        $portfolioRisk,

                    'evidence_maturity' =>
                        $evidenceMaturity,

                    'evidence_confidence' =>
                        $evidenceConfidence,

                    'pending_governance_reviews' =>
                        $pendingReviews,

                    'priority_factors' =>
                        $factors,

                    'automatic_execution_authorized' =>
                        false,

                    'automatic_change_authorized' =>
                        false,

                    'automatic_deployment_authorized' =>
                        false,

                    'automatic_rollback_authorized' =>
                        false,

                    'automatic_clinical_action_authorized' =>
                        false,

                    'analyzed_at' =>
                        now()->toIso8601String(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Persist Reassessed Priority
                |--------------------------------------------------------------------------
                */

                $action->update([
                    'priority_level' =>
                        $level,

                    'priority_score' =>
                        $score,

                    'priority_context' =>
                        $priorityContext,
                ]);

                $prioritized[] = [
                    'action_id' =>
                        $action->id,

                    'action_code' =>
                        $action->action_code,

                    'action_category' =>
                        $action->action_category,

                    'base_priority_score' =>
                        $baseScore,

                    'previous_priority_level' =>
                        $previousLevel,

                    'previous_priority_score' =>
                        $previousScore,

                    'priority_level' =>
                        $level,

                    'priority_score' =>
                        $score,

                    'priority_factors' =>
                        $factors,

                    'action_status' =>
                        $action->action_status,

                    'eligibility_status' =>
                        $action->eligibility_status,

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
        });

        /*
        |--------------------------------------------------------------------------
        | Sort Highest Priority First
        |--------------------------------------------------------------------------
        */

        usort(
            $prioritized,
            fn ($a, $b) =>
                $b['priority_score']
                <=> $a['priority_score']
        );

        /*
        |--------------------------------------------------------------------------
        | Priority Summary
        |--------------------------------------------------------------------------
        */

        $critical = collect($prioritized)
            ->where(
                'priority_level',
                'CRITICAL'
            )
            ->count();

        $high = collect($prioritized)
            ->where(
                'priority_level',
                'HIGH'
            )
            ->count();

        $moderate = collect($prioritized)
            ->where(
                'priority_level',
                'MODERATE'
            )
            ->count();

        $advisory = collect($prioritized)
            ->where(
                'priority_level',
                'ADVISORY'
            )
            ->count();

        $highestPriorityScore =
            collect($prioritized)
                ->max('priority_score')
                ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            count($prioritized)
                . ' active governance action(s) were reassessed using deterministic priority intelligence.',

            "{$high} high-priority governance action(s) are currently identified.",

            "{$moderate} moderate-priority governance action(s) are currently identified.",

            "{$advisory} advisory governance action(s) are currently identified.",

            "Current portfolio risk context is {$portfolioRisk}.",

            "Current evidence maturity is {$evidenceMaturity} with {$evidenceConfidence} confidence.",

            'Priority calculation uses immutable action base scores so repeated analysis does not inflate priority.',
        ];

        if ($critical > 0) {
            $findings[] =
                "{$critical} critical governance action(s) require immediate human attention.";
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
                'GOVERNANCE_ACTION_PRIORITY_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'priority_summary' => [
                'total_actions' =>
                    count($prioritized),

                'critical_actions' =>
                    $critical,

                'high_actions' =>
                    $high,

                'moderate_actions' =>
                    $moderate,

                'advisory_actions' =>
                    $advisory,

                'highest_priority_score' =>
                    $highestPriorityScore,
            ],

            'prioritized_actions' =>
                $prioritized,

            'priority_context' => [
                'portfolio_risk_level' =>
                    $portfolioRisk,

                'evidence_maturity' =>
                    $evidenceMaturity,

                'evidence_confidence' =>
                    $evidenceConfidence,

                'pending_governance_reviews' =>
                    $pendingReviews,

                'priority_calculation_mode' =>
                    'DETERMINISTIC_BASE_SCORE',
            ],

            'priority_findings' =>
                $findings,

            'priority_guardrails' =>
                $this->priorityGuardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Immutable Base Priority Scores
    |--------------------------------------------------------------------------
    |
    | These scores represent the starting priority assigned by the governance
    | action type itself.
    |
    | Repeated calls to analyze() ALWAYS begin from these values.
    |
    | Therefore:
    |
    | Run 1: base + current context
    | Run 2: base + current context
    | Run 3: base + current context
    |
    | Priority does not compound across repeated analysis calls.
    |--------------------------------------------------------------------------
    */

    private function getBasePriorityScore(string $actionCode): int
    {
        return match ($actionCode) {
            'REVIEW_PENDING_IMPROVEMENT_CANDIDATES' =>
                60,

            'REASSESS_DEFERRED_IMPROVEMENT_CANDIDATES' =>
                35,

            'COLLECT_ADDITIONAL_LEARNING_EVIDENCE' =>
                55,

            'STRENGTHEN_IMPROVEMENT_EVIDENCE_CONFIDENCE' =>
                45,

            'CONTINUE_LONGITUDINAL_MONITORING' =>
                50,

            'REVIEW_IMPROVEMENT_PORTFOLIO_ADVISORIES' =>
                40,

            'ESCALATE_CRITICAL_IMPROVEMENT_EXCEPTIONS' =>
                100,

            'REVIEW_HIGH_SEVERITY_IMPROVEMENT_RISKS' =>
                85,

            default =>
                25,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Priority Guardrails
    |--------------------------------------------------------------------------
    */

    private function priorityGuardrails(): array
    {
        return [
            'priority_intelligence_is_approval' =>
                false,

            'priority_intelligence_is_execution' =>
                false,

            'priority_increase_authorizes_execution' =>
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
                'Governance action priority intelligence ranks human-review work only. Priority levels do not authorize execution, AI modification, deployment, rollback, or clinical action.',
        ];
    }
}