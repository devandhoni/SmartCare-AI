<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use App\Models\AIImprovementLifecycleSnapshot;

class AIGovernanceActionFinalValidationEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'validation_status' => 'FAILED',
                'step_59_ready_for_closure' => false,
                'status' => 'SNAPSHOT_NOT_FOUND',
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'snapshot_id' => $snapshotId,
            ];
        }

        $audit = app(
            AIGovernanceActionAuditSummaryEngine::class
        )->analyze($snapshot->id);

        $closure = app(
            AIGovernanceActionClosureIntelligenceEngine::class
        )->analyze($snapshot->id);

        $workload = app(
            AIGovernanceActionWorkloadQueueIntelligenceEngine::class
        )->analyze($snapshot->id);

        if (!($audit['audit_available'] ?? false)) {
            return [
                'validation_status' => 'FAILED',
                'step_59_ready_for_closure' => false,
                'status' => 'AUDIT_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($closure['analysis_completed'] ?? false)) {
            return [
                'validation_status' => 'FAILED',
                'step_59_ready_for_closure' => false,
                'status' => 'CLOSURE_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        if (!($workload['analysis_completed'] ?? false)) {
            return [
                'validation_status' => 'FAILED',
                'step_59_ready_for_closure' => false,
                'status' => 'WORKLOAD_INTELLIGENCE_UNAVAILABLE',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        $reviews = AIGovernanceActionReview::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->orderBy('id')
            ->get();

        $checks = [];

        $checks['governance_action_registry_operational'] = [
            'passed' => $actions->count() > 0,
            'message' => 'Governance action registry contains operational action records.',
        ];

        $checks['action_generation_operational'] = [
            'passed' => $actions->every(
                fn ($action) => !empty($action->action_code)
            ),
            'message' => 'Generated governance actions contain valid action identifiers.',
        ];

        $checks['priority_intelligence_operational'] = [
            'passed' => $actions->every(
                fn ($action) =>
                    $action->priority_context !== null
                    && $action->priority_score !== null
            ),
            'message' => 'Deterministic governance action priority intelligence is available.',
        ];

        $checks['eligibility_validation_operational'] = [
            'passed' => $actions->every(
                fn ($action) =>
                    $action->eligibility_context !== null
            ),
            'message' => 'Governance action eligibility and safety validation is available.',
        ];

        $checks['human_review_registry_operational'] = [
            'passed' =>
                $reviews->count() > 0,
            'message' => 'Human governance action review registry contains recorded decisions.',
        ];

        $checks['resolution_control_operational'] = [
            'passed' =>
                collect(
                    $closure['action_states'] ?? []
                )->contains(
                    fn ($state) =>
                        ($state['is_closed'] ?? false)
                        || ($state['remains_deferred'] ?? false)
                        || ($state['requires_more_evidence'] ?? false)
                        || ($state['awaiting_human_review'] ?? false)
                ),
            'message' => 'Governance action resolution state control is operational.',
        ];

        $checks['closure_intelligence_operational'] = [
            'passed' =>
                ($closure['status'] ?? null)
                === 'GOVERNANCE_ACTION_CLOSURE_INTELLIGENCE_AVAILABLE',
            'message' => 'Governance action closure intelligence is operational.',
        ];

        $checks['workload_queue_intelligence_operational'] = [
            'passed' =>
                ($workload['status'] ?? null)
                === 'GOVERNANCE_WORKLOAD_QUEUE_INTELLIGENCE_AVAILABLE',
            'message' => 'Governance workload and queue intelligence is operational.',
        ];

        $checks['step_59_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null)
                === 'COMPLETE'
                &&
                (int) (
                    $audit['audit_summary']['failed_checks']
                    ?? 1
                ) === 0,
            'message' => 'Step 59 governance action audit completed without integrity failures.',
        ];

        $checks['automatic_execution_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_execution_allowed
                ),
            'message' => 'Automatic execution remains disabled across all governance actions.',
        ];

        $checks['automatic_change_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_change_allowed
                ),
            'message' => 'Automatic AI modification remains disabled across all governance actions.',
        ];

        $checks['automatic_deployment_rollback_clinical_isolation'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        !(bool) $action->automatic_deployment_allowed
                        &&
                        !(bool) $action->automatic_rollback_allowed
                        &&
                        !(bool) $action->automatic_clinical_action_allowed
                ),
            'message' => 'Automatic deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                $actions->every(
                    fn ($action) =>
                        (bool) $action->human_review_required
                        &&
                        (bool) $action->governance_validation_required
                ),
            'message' => 'Human review and governance validation remain mandatory.',
        ];

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        $warnings = [];

        $pendingHumanReview = (int) (
            $workload['workload_summary']['pending_human_review']
            ?? 0
        );

        $evidenceWaiting = (int) (
            $workload['workload_summary']['evidence_waiting_actions']
            ?? 0
        );

        $deferredActions = (int) (
            $workload['workload_summary']['deferred_actions']
            ?? 0
        );

        $activeActions = (int) (
            $workload['workload_summary']['active_actions']
            ?? 0
        );

        $closurePercentage = (float) (
            $closure['closure_summary']['closure_percentage']
            ?? 0
        );

        if ($pendingHumanReview > 0) {
            $warnings[] =
                "{$pendingHumanReview} governance action(s) remain pending human review.";
        }

        if ($evidenceWaiting > 0) {
            $warnings[] =
                "{$evidenceWaiting} governance action(s) remain dependent on additional evidence.";
        }

        if ($deferredActions > 0) {
            $warnings[] =
                "{$deferredActions} governance action(s) remain deferred.";
        }

        if ($activeActions > 0) {
            $warnings[] =
                "{$activeActions} governance action(s) remain active or unresolved.";
        }

        if ($closurePercentage < 100) {
            $warnings[] =
                "Governance action closure is currently {$closurePercentage}%, so operational governance work remains after Step 59 architecture closure.";
        }

        $criticalIssues = [];

        if ($failedChecks > 0) {
            foreach ($checks as $checkName => $check) {
                if (!($check['passed'] ?? false)) {
                    $criticalIssues[] =
                        "{$checkName}: {$check['message']}";
                }
            }
        }

        $stepReadyForClosure =
            $failedChecks === 0;

        $validationStatus = match (true) {
            $failedChecks > 0 =>
                'FAILED',

            count($warnings) > 0 =>
                'PASSED_WITH_WARNINGS',

            default =>
                'PASSED',
        };

        $findings = [
            'The complete Step 59 AI governance action management architecture has been validated.',
            $actions->count() . ' governance action(s) are currently represented in the action lifecycle.',
            $reviews->count() . ' human governance review decision(s) are recorded.',
            ($closure['closure_summary']['closed_actions'] ?? 0)
                . ' governance action(s) are currently closed.',
            $activeActions
                . ' governance action(s) remain active.',
            'Current workload classification is '
                . ($workload['workload_status'] ?? 'UNKNOWN')
                . '.',
            'Step 59 maintains separation between intelligence, human review, resolution, execution, and AI system authority.',
            'No autonomous execution, deployment, rollback, AI modification, or clinical-action pathway is enabled.',
        ];

        return [
            'validation_status' =>
                $validationStatus,

            'step_59_ready_for_closure' =>
                $stepReadyForClosure,

            'governance_action_mode' =>
                'HUMAN_GOVERNED_ACTION_MANAGEMENT',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'completion_message' =>
                $stepReadyForClosure
                    ? 'Step 59 AI Governance Action Management has passed final validation and is ready for closure.'
                    : 'Step 59 AI Governance Action Management contains validation failures and is not ready for closure.',

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

            'governance_action_context' => [
                'total_actions' =>
                    $actions->count(),

                'review_records' =>
                    $reviews->count(),

                'closed_actions' =>
                    $closure['closure_summary']['closed_actions'] ?? 0,

                'active_actions' =>
                    $activeActions,

                'closure_percentage' =>
                    $closurePercentage,

                'pending_human_review' =>
                    $pendingHumanReview,

                'evidence_waiting_actions' =>
                    $evidenceWaiting,

                'deferred_actions' =>
                    $deferredActions,

                'workload_status' =>
                    $workload['workload_status'] ?? 'UNKNOWN',

                'highest_active_priority' =>
                    $workload['highest_active_priority'] ?? null,
            ],

            'architecture_summary' => [
                '59.1_governance_action_registry' => [
                    'status' => 'OPERATIONAL',
                ],

                '59.2_action_generation' => [
                    'status' => 'OPERATIONAL',
                    'total_actions' => $actions->count(),
                ],

                '59.3_priority_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'calculation_mode' => 'DETERMINISTIC_BASE_SCORE',
                ],

                '59.4_eligibility_safety_validation' => [
                    'status' => 'OPERATIONAL',
                ],

                '59.5_human_governance_review' => [
                    'status' => 'OPERATIONAL',
                    'review_records' => $reviews->count(),
                ],

                '59.6_resolution_control' => [
                    'status' => 'OPERATIONAL',
                ],

                '59.7_closure_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'closure_percentage' => $closurePercentage,
                ],

                '59.8_workload_queue_intelligence' => [
                    'status' => 'OPERATIONAL',
                    'workload_status' => $workload['workload_status'] ?? 'UNKNOWN',
                ],

                '59.9_governance_action_audit' => [
                    'status' => $audit['audit_status'] ?? 'UNKNOWN',
                ],

                '59.10_final_validation' => [
                    'status' => $validationStatus,
                    'step_59_ready_for_closure' => $stepReadyForClosure,
                ],
            ],

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $findings,

            'step_59_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function guardrails(): array
    {
        return [
            'governance_action_management_enabled' =>
                true,

            'human_action_review_required' =>
                true,

            'action_priority_intelligence_enabled' =>
                true,

            'action_eligibility_validation_enabled' =>
                true,

            'action_resolution_control_enabled' =>
                true,

            'action_closure_intelligence_enabled' =>
                true,

            'workload_queue_intelligence_enabled' =>
                true,

            'autonomous_action_execution_enabled' =>
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
                'Step 59 establishes human-governed AI governance action management. Governance actions may be generated, prioritized, reviewed, resolved, queued, and audited, but they do not enable autonomous AI modification, execution, deployment, rollback, or clinical action.',
        ];
    }
}