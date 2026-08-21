<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;
use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;
use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementExecution;

class AIImprovementGovernanceProgressIntelligenceEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Lifecycle Snapshot
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | 2. Snapshot Governance Guardrails
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $snapshot->automatic_change_allowed ||
            (bool) $snapshot->automatic_deployment_allowed ||
            (bool) $snapshot->automatic_rollback_allowed ||
            (bool) $snapshot->automatic_clinical_action_allowed
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Governance progress intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Scope Queries
        |--------------------------------------------------------------------------
        */

        $reviewQuery = AIImprovementReview::query();

        $testQuery = AIImprovementTest::query();

        $implementationReviewQuery =
            AIImprovementImplementationReview::query();

        $executionQuery =
            AIImprovementExecution::query();

        if ($snapshot->snapshot_scope === 'RESIDENT') {
            $residentId = $snapshot->resident_id;

            $reviewQuery->where('resident_id', $residentId);
            $testQuery->where('resident_id', $residentId);
            $implementationReviewQuery->where('resident_id', $residentId);
            $executionQuery->where('resident_id', $residentId);
        } else {
            $reviewQuery->where('scope_type', 'FACILITY');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Candidate Governance Review Distribution
        |--------------------------------------------------------------------------
        */

        $totalReviews = (clone $reviewQuery)->count();

        $pendingReviews = (clone $reviewQuery)
            ->where('review_status', 'PENDING')
            ->count();

        $deferredReviews = (clone $reviewQuery)
            ->where('review_status', 'DEFERRED')
            ->count();

        $rejectedReviews = (clone $reviewQuery)
            ->where('review_status', 'REJECTED')
            ->count();

        $testingApprovedReviews = (clone $reviewQuery)
            ->where('approved_for_testing', true)
            ->count();

        $implementationApprovedOnTestingReviews = (clone $reviewQuery)
            ->where('approved_for_implementation', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 5. Testing Governance
        |--------------------------------------------------------------------------
        */

        $totalTests = (clone $testQuery)->count();

        $plannedTests = (clone $testQuery)
            ->where('test_status', 'PLANNED')
            ->count();

        $runningTests = (clone $testQuery)
            ->where('test_status', 'RUNNING')
            ->count();

        $awaitingValidationTests = (clone $testQuery)
            ->where('test_status', 'AWAITING_VALIDATION')
            ->count();

        $validatedTests = (clone $testQuery)
            ->where('test_status', 'VALIDATED')
            ->count();

        $rejectedTests = (clone $testQuery)
            ->whereIn('test_status', [
                'REJECTED',
                'VALIDATION_REJECTED',
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 6. Implementation Governance
        |--------------------------------------------------------------------------
        */

        $totalImplementationReviews =
            (clone $implementationReviewQuery)->count();

        $pendingImplementationReviews =
            (clone $implementationReviewQuery)
                ->where('review_status', 'PENDING')
                ->count();

        $approvedImplementationReviews =
            (clone $implementationReviewQuery)
                ->where('approved_for_implementation', true)
                ->count();

        $rejectedImplementationReviews =
            (clone $implementationReviewQuery)
                ->where('review_status', 'REJECTED')
                ->count();

        $deferredImplementationReviews =
            (clone $implementationReviewQuery)
                ->where('review_status', 'DEFERRED')
                ->count();

        /*
        |--------------------------------------------------------------------------
        | 7. Execution Governance
        |--------------------------------------------------------------------------
        */

        $totalExecutions =
            (clone $executionQuery)->count();

        $authorizedExecutions =
            (clone $executionQuery)
                ->where('approved_for_execution', true)
                ->count();

        $verifiedExecutions =
            (clone $executionQuery)
                ->where('execution_status', 'VERIFIED')
                ->count();

        /*
        |--------------------------------------------------------------------------
        | 8. Approval Separation Validation
        |--------------------------------------------------------------------------
        */

        $testingImplementationOverlap =
            $implementationApprovedOnTestingReviews;

        $testingImplementationSeparated =
            $testingImplementationOverlap === 0;

        $approvedExecutionWithoutImplementationReview = 0;

        $executions = (clone $executionQuery)->get();

        foreach ($executions as $execution) {
            $implementationReview =
                AIImprovementImplementationReview::find(
                    $execution->implementation_review_id
                );

            if (
                $execution->approved_for_execution
                &&
                (
                    !$implementationReview
                    ||
                    !$implementationReview->approved_for_implementation
                )
            ) {
                $approvedExecutionWithoutImplementationReview++;
            }
        }

        $executionGovernanceChainValid =
            $approvedExecutionWithoutImplementationReview === 0;

        /*
        |--------------------------------------------------------------------------
        | 9. Governance Progress Metrics
        |--------------------------------------------------------------------------
        */

        $closedCandidateReviews =
            $deferredReviews
            + $rejectedReviews
            + $testingApprovedReviews;

        $candidateReviewCompletionPercentage =
            $totalReviews > 0
                ? round(
                    ($closedCandidateReviews / $totalReviews) * 100,
                    2
                )
                : 0;

        $testValidationPercentage =
            $totalTests > 0
                ? round(
                    ($validatedTests / $totalTests) * 100,
                    2
                )
                : 0;

        $implementationDecisionCount =
            $approvedImplementationReviews
            + $rejectedImplementationReviews
            + $deferredImplementationReviews;

        $implementationReviewCompletionPercentage =
            $totalImplementationReviews > 0
                ? round(
                    ($implementationDecisionCount / $totalImplementationReviews)
                    * 100,
                    2
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | 10. Governance Stage
        |--------------------------------------------------------------------------
        */

        if ($verifiedExecutions > 0) {
            $governanceStage =
                'POST_IMPLEMENTATION_EXECUTION_GOVERNANCE';
        } elseif ($approvedImplementationReviews > 0) {
            $governanceStage =
                'IMPLEMENTATION_APPROVED';
        } elseif ($validatedTests > 0) {
            $governanceStage =
                'CONTROLLED_TEST_VALIDATED';
        } elseif ($testingApprovedReviews > 0) {
            $governanceStage =
                'CONTROLLED_TEST_APPROVED';
        } elseif ($totalReviews > 0) {
            $governanceStage =
                'CANDIDATE_REVIEW_ACTIVE';
        } else {
            $governanceStage =
                'NO_GOVERNANCE_ACTIVITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Governance Integrity
        |--------------------------------------------------------------------------
        */

        $integrityChecks = [
            'testing_implementation_separated' => [
                'passed' =>
                    $testingImplementationSeparated,

                'invalid_testing_review_implementation_approvals' =>
                    $testingImplementationOverlap,

                'message' =>
                    'Testing approval must remain separate from implementation approval.',
            ],

            'execution_requires_implementation_approval' => [
                'passed' =>
                    $executionGovernanceChainValid,

                'invalid_executions' =>
                    $approvedExecutionWithoutImplementationReview,

                'message' =>
                    'Execution authorization must originate from an approved implementation governance review.',
            ],

            'automatic_change_isolation' => [
                'passed' =>
                    !(bool) $snapshot->automatic_change_allowed
                    &&
                    !(bool) $snapshot->automatic_deployment_allowed
                    &&
                    !(bool) $snapshot->automatic_rollback_allowed
                    &&
                    !(bool) $snapshot->automatic_clinical_action_allowed,

                'message' =>
                    'Automatic change, deployment, rollback, and clinical action remain disabled.',
            ],

            'human_governance_required' => [
                'passed' =>
                    (bool) $snapshot->human_review_required
                    &&
                    (bool) $snapshot->governance_validation_required,

                'message' =>
                    'Human review and governance validation remain required.',
            ],
        ];

        $passedIntegrityChecks =
            collect($integrityChecks)
                ->filter(
                    fn ($check) =>
                        (bool) ($check['passed'] ?? false)
                )
                ->count();

        $totalIntegrityChecks =
            count($integrityChecks);

        $failedIntegrityChecks =
            $totalIntegrityChecks
            - $passedIntegrityChecks;

        /*
        |--------------------------------------------------------------------------
        | 12. Governance Health
        |--------------------------------------------------------------------------
        */

        if ($failedIntegrityChecks > 0) {
            $governanceHealth =
                'GOVERNANCE_ISSUE_DETECTED';
        } elseif ($pendingReviews > 0) {
            $governanceHealth =
                'HEALTHY_WITH_PENDING_REVIEWS';
        } else {
            $governanceHealth =
                'HEALTHY';
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Governance progress intelligence currently includes {$totalReviews} candidate review record(s).";

        $findings[] =
            "{$testingApprovedReviews} candidate review(s) have received controlled-testing approval.";

        $findings[] =
            "{$validatedTests} controlled test(s) have completed human validation.";

        $findings[] =
            "{$approvedImplementationReviews} separate implementation governance review(s) have received implementation approval.";

        $findings[] =
            "{$verifiedExecutions} controlled execution(s) currently have verified post-execution outcomes.";

        $findings[] =
            "Candidate governance review completion is {$candidateReviewCompletionPercentage}%.";

        if ($pendingReviews > 0) {
            $findings[] =
                "{$pendingReviews} candidate governance review(s) remain pending.";
        }

        if ($deferredReviews > 0) {
            $findings[] =
                "{$deferredReviews} candidate governance review(s) remain deferred.";
        }

        if ($rejectedReviews > 0) {
            $findings[] =
                "{$rejectedReviews} candidate governance review(s) were rejected.";
        }

        if ($testingImplementationSeparated) {
            $findings[] =
                'Testing approval and implementation approval remain properly separated.';
        }

        if ($executionGovernanceChainValid) {
            $findings[] =
                'No authorized execution bypasses the implementation governance approval chain.';
        }

        /*
        |--------------------------------------------------------------------------
        | 14. Management Priorities
        |--------------------------------------------------------------------------
        */

        $priorities = [];

        if ($pendingReviews > 0) {
            $priorities[] =
                'Continue human review of pending improvement governance records.';
        }

        if ($deferredReviews > 0) {
            $priorities[] =
                'Reassess deferred candidates only when sufficient additional evidence becomes available.';
        }

        if ($snapshot->total_learning_evidence < 20) {
            $priorities[] =
                'Continue collecting validated learning evidence before expanding improvement authority.';
        }

        if ($snapshot->active_monitoring_records > 0) {
            $priorities[] =
                'Continue longitudinal monitoring of verified controlled improvements.';
        }

        /*
        |--------------------------------------------------------------------------
        | 15. Return Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_PROGRESS_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'governance_stage' =>
                $governanceStage,

            'governance_health' =>
                $governanceHealth,

            'candidate_review_summary' => [
                'total_reviews' =>
                    $totalReviews,

                'pending_reviews' =>
                    $pendingReviews,

                'deferred_reviews' =>
                    $deferredReviews,

                'rejected_reviews' =>
                    $rejectedReviews,

                'testing_approved_reviews' =>
                    $testingApprovedReviews,

                'review_completion_percentage' =>
                    $candidateReviewCompletionPercentage,
            ],

            'controlled_testing_summary' => [
                'total_tests' =>
                    $totalTests,

                'planned_tests' =>
                    $plannedTests,

                'running_tests' =>
                    $runningTests,

                'awaiting_validation_tests' =>
                    $awaitingValidationTests,

                'validated_tests' =>
                    $validatedTests,

                'rejected_tests' =>
                    $rejectedTests,

                'test_validation_percentage' =>
                    $testValidationPercentage,
            ],

            'implementation_governance_summary' => [
                'total_reviews' =>
                    $totalImplementationReviews,

                'pending_reviews' =>
                    $pendingImplementationReviews,

                'approved_reviews' =>
                    $approvedImplementationReviews,

                'rejected_reviews' =>
                    $rejectedImplementationReviews,

                'deferred_reviews' =>
                    $deferredImplementationReviews,

                'review_completion_percentage' =>
                    $implementationReviewCompletionPercentage,
            ],

            'execution_governance_summary' => [
                'total_executions' =>
                    $totalExecutions,

                'authorized_executions' =>
                    $authorizedExecutions,

                'verified_executions' =>
                    $verifiedExecutions,
            ],

            'governance_integrity' => [
                'total_checks' =>
                    $totalIntegrityChecks,

                'passed_checks' =>
                    $passedIntegrityChecks,

                'failed_checks' =>
                    $failedIntegrityChecks,

                'checks' =>
                    $integrityChecks,
            ],

            'governance_findings' =>
                $findings,

            'management_priorities' =>
                $priorities,

            'governance_guardrails' => [
                'governance_intelligence_is_approval' =>
                    false,

                'testing_approval_is_implementation_approval' =>
                    false,

                'implementation_approval_is_execution' =>
                    false,

                'execution_authorization_is_automatic_execution' =>
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

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Governance progress intelligence reports the status of human-controlled improvement reviews only. It does not approve testing, implementation, execution, deployment, rollback, or clinical action.',
            ],
        ];
    }
}