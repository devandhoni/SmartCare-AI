<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;
use Illuminate\Support\Facades\DB;

class AIGovernanceActionGenerationEngine
{
    public function generate(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'generated' => false,
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
                'generated' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Governance action generation is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $governance = app(
            AIImprovementGovernanceProgressIntelligenceEngine::class
        )->analyze($snapshot->id);

        $evidence = app(
            AIImprovementEvidenceMaturityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIImprovementRiskExceptionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $executive = app(
            AIImprovementExecutivePortfolioIntelligenceEngine::class
        )->analyze($snapshot->id);

        foreach (
            [
                'governance' => $governance,
                'evidence' => $evidence,
                'risk' => $risk,
                'executive' => $executive,
            ]
            as $source => $result
        ) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'generated' => false,
                    'status' => 'SOURCE_INTELLIGENCE_INCOMPLETE',
                    'message' => "Governance action generation could not continue because {$source} intelligence is unavailable.",
                    'snapshot_id' => $snapshot->id,
                    'failed_source' => $source,
                ];
            }
        }

        $candidates = [];

        $pendingReviews = (int) (
            $governance['candidate_review_summary']['pending_reviews']
            ?? 0
        );

        if ($pendingReviews > 0) {
            $candidates[] = [
                'action_code' => 'REVIEW_PENDING_IMPROVEMENT_CANDIDATES',
                'action_category' => 'GOVERNANCE_REVIEW',
                'action_title' => 'Review pending improvement candidates',
                'action_description' => "{$pendingReviews} improvement governance review(s) remain pending and require human review.",
                'source_type' => 'GOVERNANCE_PROGRESS_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => 'PENDING_REVIEWS',
                'priority_level' => 'MODERATE',
                'priority_score' => 60,
                'source_context' => [
                    'pending_reviews' => $pendingReviews,
                    'governance_health' =>
                        $governance['governance_health'] ?? null,
                ],
            ];
        }

        $deferredReviews = (int) (
            $governance['candidate_review_summary']['deferred_reviews']
            ?? 0
        );

        if ($deferredReviews > 0) {
            $candidates[] = [
                'action_code' => 'REASSESS_DEFERRED_IMPROVEMENT_CANDIDATES',
                'action_category' => 'GOVERNANCE_REVIEW',
                'action_title' => 'Reassess deferred improvement candidates',
                'action_description' => "{$deferredReviews} deferred governance review(s) should be reconsidered only when additional evidence becomes available.",
                'source_type' => 'GOVERNANCE_PROGRESS_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => 'DEFERRED_REVIEWS',
                'priority_level' => 'ADVISORY',
                'priority_score' => 35,
                'source_context' => [
                    'deferred_reviews' => $deferredReviews,
                ],
            ];
        }

        $evidenceMaturity = strtoupper(
            (string) (
                $evidence['overall_evidence_maturity']
                ?? 'UNKNOWN'
            )
        );

        if (
            in_array(
                $evidenceMaturity,
                ['EARLY', 'FOUNDATIONAL', 'INSUFFICIENT'],
                true
            )
        ) {
            $candidates[] = [
                'action_code' => 'COLLECT_ADDITIONAL_LEARNING_EVIDENCE',
                'action_category' => 'EVIDENCE_MATURITY',
                'action_title' => 'Collect additional learning evidence',
                'action_description' => 'Improvement lifecycle evidence remains below mature evidence thresholds and should continue accumulating before stronger conclusions are made.',
                'source_type' => 'EVIDENCE_MATURITY_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => $evidenceMaturity,
                'priority_level' => 'MODERATE',
                'priority_score' => 55,
                'source_context' => [
                    'overall_evidence_maturity' =>
                        $evidence['overall_evidence_maturity'] ?? null,
                    'overall_confidence' =>
                        $evidence['overall_confidence'] ?? null,
                    'learning_evidence' =>
                        $evidence['learning_evidence_summary']['total_evaluated_evidence']
                        ?? 0,
                    'monitoring_observations' =>
                        $evidence['longitudinal_evidence_summary']['monitoring_observations']
                        ?? 0,
                ],
            ];
        }

        $evidenceConfidence = strtoupper(
            (string) (
                $evidence['overall_confidence']
                ?? 'UNKNOWN'
            )
        );

        if (
            in_array(
                $evidenceConfidence,
                ['LIMITED', 'VERY LIMITED'],
                true
            )
        ) {
            $candidates[] = [
                'action_code' => 'STRENGTHEN_IMPROVEMENT_EVIDENCE_CONFIDENCE',
                'action_category' => 'EVIDENCE_CONFIDENCE',
                'action_title' => 'Strengthen improvement evidence confidence',
                'action_description' => 'Current lifecycle evidence confidence remains limited and requires further validated observations.',
                'source_type' => 'EVIDENCE_MATURITY_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => $evidenceConfidence,
                'priority_level' => 'ADVISORY',
                'priority_score' => 45,
                'source_context' => [
                    'overall_confidence' =>
                        $evidence['overall_confidence'] ?? null,
                    'evidence_depth_score' =>
                        $evidence['evidence_depth_score'] ?? null,
                ],
            ];
        }

        $activeMonitoringRecords =
            (int) $snapshot->active_monitoring_records;

        if ($activeMonitoringRecords > 0) {
            $candidates[] = [
                'action_code' => 'CONTINUE_LONGITUDINAL_MONITORING',
                'action_category' => 'MONITORING',
                'action_title' => 'Continue longitudinal improvement monitoring',
                'action_description' => "{$activeMonitoringRecords} verified improvement monitoring record(s) remain active and should continue collecting longitudinal evidence.",
                'source_type' => 'LIFECYCLE_SNAPSHOT',
                'source_id' => $snapshot->id,
                'source_status' => 'ACTIVE_MONITORING',
                'priority_level' => 'MODERATE',
                'priority_score' => 50,
                'source_context' => [
                    'active_monitoring_records' =>
                        $activeMonitoringRecords,
                    'monitoring_status' =>
                        $snapshot->monitoring_status,
                ],
            ];
        }

        $criticalExceptions = (int) (
            $risk['exception_summary']['critical_exceptions']
            ?? 0
        );

        if ($criticalExceptions > 0) {
            $candidates[] = [
                'action_code' => 'ESCALATE_CRITICAL_IMPROVEMENT_EXCEPTIONS',
                'action_category' => 'RISK_ESCALATION',
                'action_title' => 'Escalate critical improvement exceptions',
                'action_description' => "{$criticalExceptions} critical improvement exception(s) require immediate human governance escalation.",
                'source_type' => 'RISK_EXCEPTION_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => 'CRITICAL_EXCEPTIONS_PRESENT',
                'priority_level' => 'CRITICAL',
                'priority_score' => 100,
                'source_context' => [
                    'critical_exceptions' =>
                        $criticalExceptions,
                ],
            ];
        }

        $highExceptions = (int) (
            $risk['exception_summary']['high_exceptions']
            ?? 0
        );

        if ($highExceptions > 0) {
            $candidates[] = [
                'action_code' => 'REVIEW_HIGH_SEVERITY_IMPROVEMENT_RISKS',
                'action_category' => 'RISK_REVIEW',
                'action_title' => 'Review high-severity improvement risks',
                'action_description' => "{$highExceptions} high-severity improvement exception(s) require prioritized human review.",
                'source_type' => 'RISK_EXCEPTION_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => 'HIGH_EXCEPTIONS_PRESENT',
                'priority_level' => 'HIGH',
                'priority_score' => 85,
                'source_context' => [
                    'high_exceptions' =>
                        $highExceptions,
                ],
            ];
        }

        $advisoryExceptions = (int) (
            $risk['exception_summary']['advisory_exceptions']
            ?? 0
        );

        if ($advisoryExceptions > 0) {
            $candidates[] = [
                'action_code' => 'REVIEW_IMPROVEMENT_PORTFOLIO_ADVISORIES',
                'action_category' => 'PORTFOLIO_ADVISORY',
                'action_title' => 'Review improvement portfolio advisories',
                'action_description' => "{$advisoryExceptions} improvement portfolio advisory item(s) remain active.",
                'source_type' => 'RISK_EXCEPTION_INTELLIGENCE',
                'source_id' => $snapshot->id,
                'source_status' => 'ADVISORIES_PRESENT',
                'priority_level' => 'ADVISORY',
                'priority_score' => 40,
                'source_context' => [
                    'advisory_exceptions' =>
                        $advisoryExceptions,
                    'portfolio_risk_level' =>
                        $risk['portfolio_risk_level'] ?? null,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Create Actions
        |--------------------------------------------------------------------------
        */

        $created = [];
        $duplicates = [];

        DB::transaction(function () use (
            $snapshot,
            $candidates,
            $executive,
            &$created,
            &$duplicates
        ) {
            foreach ($candidates as $candidate) {
                $existing = AIGovernanceAction::query()
                    ->where(
                        'lifecycle_snapshot_id',
                        $snapshot->id
                    )
                    ->where(
                        'action_code',
                        $candidate['action_code']
                    )
                    ->whereIn(
                        'action_status',
                        [
                            'OPEN',
                            'PENDING_REVIEW',
                            'UNDER_REVIEW',
                            'APPROVED',
                            'DEFERRED',
                        ]
                    )
                    ->first();

                if ($existing) {
                    $duplicates[] = [
                        'action_code' =>
                            $candidate['action_code'],
                        'action_id' =>
                            $existing->id,
                        'action_status' =>
                            $existing->action_status,
                    ];

                    continue;
                }

                $action = AIGovernanceAction::create([
                    'lifecycle_snapshot_id' =>
                        $snapshot->id,

                    'action_code' =>
                        $candidate['action_code'],

                    'action_category' =>
                        $candidate['action_category'],

                    'action_title' =>
                        $candidate['action_title'],

                    'action_description' =>
                        $candidate['action_description'],

                    'scope_type' =>
                        $snapshot->snapshot_scope,

                    'resident_id' =>
                        $snapshot->resident_id,

                    'source_type' =>
                        $candidate['source_type'],

                    'source_id' =>
                        $candidate['source_id'],

                    'source_status' =>
                        $candidate['source_status'],

                    'priority_level' =>
                        $candidate['priority_level'],

                    'priority_score' =>
                        $candidate['priority_score'],

                    'action_status' =>
                        'OPEN',

                    'eligibility_status' =>
                        'PENDING_VALIDATION',

                    'human_review_required' =>
                        true,

                    'governance_validation_required' =>
                        true,

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

                    'source_context' =>
                        $candidate['source_context'],

                    'priority_context' => [
                        'initial_priority_level' =>
                            $candidate['priority_level'],

                        'initial_priority_score' =>
                            $candidate['priority_score'],

                        'priority_generated_by' =>
                            'AIGovernanceActionGenerationEngine',
                    ],

                    'action_payload' => [
                        'generation_version' =>
                            '59.2',

                        'portfolio_status' =>
                            $executive['portfolio_status']
                            ?? null,

                        'strategic_readiness' =>
                            $executive['strategic_readiness']
                            ?? null,

                        'automatic_action_authorized' =>
                            false,

                        'human_governance_required' =>
                            true,

                        'generated_at' =>
                            now()->toIso8601String(),
                    ],

                    'generated_at' =>
                        now(),
                ]);

                $created[] = [
                    'action_id' =>
                        $action->id,

                    'action_code' =>
                        $action->action_code,

                    'action_category' =>
                        $action->action_category,

                    'priority_level' =>
                        $action->priority_level,

                    'priority_score' =>
                        $action->priority_score,

                    'action_status' =>
                        $action->action_status,

                    'eligibility_status' =>
                        $action->eligibility_status,

                    'automatic_execution_allowed' =>
                        $action->automatic_execution_allowed,

                    'automatic_change_allowed' =>
                        $action->automatic_change_allowed,
                ];
            }
        });

        return [
            'generated' => true,

            'status' =>
                count($created) > 0
                    ? 'GOVERNANCE_ACTIONS_GENERATED'
                    : 'NO_NEW_GOVERNANCE_ACTIONS',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'generation_summary' => [
                'candidate_actions_identified' =>
                    count($candidates),

                'new_actions_created' =>
                    count($created),

                'duplicate_actions_prevented' =>
                    count($duplicates),
            ],

            'generated_actions' =>
                $created,

            'duplicate_actions' =>
                $duplicates,

            'source_portfolio_context' => [
                'portfolio_status' =>
                    $executive['portfolio_status']
                    ?? null,

                'strategic_readiness' =>
                    $executive['strategic_readiness']
                    ?? null,

                'portfolio_risk_level' =>
                    $risk['portfolio_risk_level']
                    ?? null,

                'evidence_maturity' =>
                    $evidence['overall_evidence_maturity']
                    ?? null,

                'evidence_confidence' =>
                    $evidence['overall_confidence']
                    ?? null,
            ],

            'generation_guardrails' => [
                'action_generation_is_approval' =>
                    false,

                'action_generation_is_execution' =>
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
                    'Governance action generation creates structured human-review work items only. It does not execute, approve, deploy, rollback, modify AI behavior, or initiate clinical action.',
            ],
        ];
    }
}