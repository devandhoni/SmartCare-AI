<?php

namespace App\Services;

use App\Models\AILearningEvidence;
use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;
use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementExecution;
use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementLifecycleSnapshot;
use Illuminate\Support\Facades\DB;

class AIImprovementLifecycleSnapshotGenerationEngine
{
    public function capture(?int $residentId = null): array
    {
        $scope = $residentId === null ? 'FACILITY' : 'RESIDENT';

        /*
        |--------------------------------------------------------------------------
        | 1. Learning Evidence
        |--------------------------------------------------------------------------
        */

        $learningQuery = AILearningEvidence::query();

        if ($residentId !== null) {
            $learningQuery->where('resident_id', $residentId);
        }

        $totalLearningEvidence = $learningQuery
            ->where('learning_status', 'EVALUATED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 2. Improvement Reviews
        |--------------------------------------------------------------------------
        */

        $reviewQuery = AIImprovementReview::query();

        if ($residentId !== null) {
            $reviewQuery->where('resident_id', $residentId);
        } else {
            $reviewQuery->where('scope_type', 'FACILITY');
        }

        $totalGovernanceReviews = $reviewQuery->count();

        $approvedTestingReviews = (clone $reviewQuery)
            ->where('approved_for_testing', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. Controlled Tests
        |--------------------------------------------------------------------------
        */

        $testQuery = AIImprovementTest::query();

        if ($residentId !== null) {
            $testQuery->where('resident_id', $residentId);
        }

        $totalControlledTests = $testQuery->count();

        $validatedTests = (clone $testQuery)
            ->where('test_status', 'VALIDATED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Implementation Reviews
        |--------------------------------------------------------------------------
        */

        $implementationReviewQuery =
            AIImprovementImplementationReview::query();

        if ($residentId !== null) {
            $implementationReviewQuery->where('resident_id', $residentId);
        }

        $totalImplementationReviews =
            $implementationReviewQuery->count();

        $approvedImplementationReviews =
            (clone $implementationReviewQuery)
                ->where('approved_for_implementation', true)
                ->count();

        /*
        |--------------------------------------------------------------------------
        | 5. Controlled Executions
        |--------------------------------------------------------------------------
        */

        $executionQuery = AIImprovementExecution::query();

        if ($residentId !== null) {
            $executionQuery->where('resident_id', $residentId);
        }

        $totalControlledExecutions =
            $executionQuery->count();

        $verifiedExecutions =
            (clone $executionQuery)
                ->where('execution_status', 'VERIFIED')
                ->count();

        /*
        |--------------------------------------------------------------------------
        | 6. Monitoring
        |--------------------------------------------------------------------------
        */

        $monitoringQuery = AIImprovementMonitoring::query();

        if ($residentId !== null) {
            $monitoringQuery->where('resident_id', $residentId);
        }

        $totalMonitoringRecords =
            $monitoringQuery->count();

        $activeMonitoringRecords =
            (clone $monitoringQuery)
                ->where('monitoring_status', 'ACTIVE')
                ->count();

        /*
        |--------------------------------------------------------------------------
        | 7. Learning Maturity
        |--------------------------------------------------------------------------
        */

        if ($totalLearningEvidence >= 20) {
            $learningMaturity = 'MATURE LEARNING';
        } elseif ($totalLearningEvidence >= 10) {
            $learningMaturity = 'DEVELOPING LEARNING';
        } elseif ($totalLearningEvidence > 0) {
            $learningMaturity = 'EARLY LEARNING';
        } else {
            $learningMaturity = 'NO LEARNING EVIDENCE';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Governance Status
        |--------------------------------------------------------------------------
        */

        if ($approvedImplementationReviews > 0) {
            $governanceStatus =
                'IMPLEMENTATION_APPROVAL_AVAILABLE';
        } elseif ($approvedTestingReviews > 0) {
            $governanceStatus =
                'TESTING_APPROVAL_AVAILABLE';
        } elseif ($totalGovernanceReviews > 0) {
            $governanceStatus =
                'GOVERNANCE_REVIEW_AVAILABLE';
        } else {
            $governanceStatus =
                'NO_GOVERNANCE_ACTIVITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Execution Status
        |--------------------------------------------------------------------------
        */

        if ($verifiedExecutions > 0) {
            $executionStatus =
                'VERIFIED_EXECUTION_AVAILABLE';
        } elseif ($totalControlledExecutions > 0) {
            $executionStatus =
                'EXECUTION_ACTIVITY_AVAILABLE';
        } else {
            $executionStatus =
                'NO_EXECUTION_ACTIVITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Monitoring Status
        |--------------------------------------------------------------------------
        */

        if ($activeMonitoringRecords > 0) {
            $monitoringStatus =
                'ACTIVE_MONITORING';
        } elseif ($totalMonitoringRecords > 0) {
            $monitoringStatus =
                'MONITORING_HISTORY_AVAILABLE';
        } else {
            $monitoringStatus =
                'NO_MONITORING_ACTIVITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Overall Improvement Status
        |--------------------------------------------------------------------------
        */

        if (
            $verifiedExecutions > 0
            &&
            $activeMonitoringRecords > 0
        ) {
            $overallImprovementStatus =
                'POST_EXECUTION_MONITORING_ACTIVE';
        } elseif (
            $approvedImplementationReviews > 0
        ) {
            $overallImprovementStatus =
                'IMPLEMENTATION_APPROVED';
        } elseif (
            $validatedTests > 0
        ) {
            $overallImprovementStatus =
                'CONTROLLED_TEST_VALIDATED';
        } elseif (
            $approvedTestingReviews > 0
        ) {
            $overallImprovementStatus =
                'CONTROLLED_TESTING_APPROVED';
        } elseif (
            $totalGovernanceReviews > 0
        ) {
            $overallImprovementStatus =
                'GOVERNANCE_REVIEW_STAGE';
        } elseif (
            $totalLearningEvidence > 0
        ) {
            $overallImprovementStatus =
                'LEARNING_STAGE';
        } else {
            $overallImprovementStatus =
                'NO_IMPROVEMENT_ACTIVITY';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Context Payloads
        |--------------------------------------------------------------------------
        */

        $learningContext = [
            'total_evaluated_evidence' =>
                $totalLearningEvidence,

            'learning_maturity' =>
                $learningMaturity,
        ];

        $governanceContext = [
            'total_governance_reviews' =>
                $totalGovernanceReviews,

            'approved_testing_reviews' =>
                $approvedTestingReviews,

            'total_implementation_reviews' =>
                $totalImplementationReviews,

            'approved_implementation_reviews' =>
                $approvedImplementationReviews,

            'governance_status' =>
                $governanceStatus,
        ];

        $executionContext = [
            'total_controlled_tests' =>
                $totalControlledTests,

            'validated_tests' =>
                $validatedTests,

            'total_controlled_executions' =>
                $totalControlledExecutions,

            'verified_executions' =>
                $verifiedExecutions,

            'execution_status' =>
                $executionStatus,
        ];

        $monitoringContext = [
            'total_monitoring_records' =>
                $totalMonitoringRecords,

            'active_monitoring_records' =>
                $activeMonitoringRecords,

            'monitoring_status' =>
                $monitoringStatus,
        ];

        $lifecycleSummary = [
            'scope' =>
                $scope,

            'resident_id' =>
                $residentId,

            'overall_improvement_status' =>
                $overallImprovementStatus,

            'learning_maturity' =>
                $learningMaturity,

            'governance_status' =>
                $governanceStatus,

            'execution_status' =>
                $executionStatus,

            'monitoring_status' =>
                $monitoringStatus,
        ];

        /*
        |--------------------------------------------------------------------------
        | 13. Store Snapshot
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $scope,
            $residentId,
            $totalLearningEvidence,
            $totalGovernanceReviews,
            $totalControlledTests,
            $totalImplementationReviews,
            $totalControlledExecutions,
            $totalMonitoringRecords,
            $activeMonitoringRecords,
            $learningMaturity,
            $governanceStatus,
            $executionStatus,
            $monitoringStatus,
            $overallImprovementStatus,
            $learningContext,
            $governanceContext,
            $executionContext,
            $monitoringContext,
            $lifecycleSummary
        ) {
            $snapshot =
                AIImprovementLifecycleSnapshot::create([
                    'snapshot_scope' =>
                        $scope,

                    'resident_id' =>
                        $residentId,

                    'snapshot_status' =>
                        'CAPTURED',

                    'total_learning_evidence' =>
                        $totalLearningEvidence,

                    'total_improvement_candidates' =>
                        $totalGovernanceReviews,

                    'total_governance_reviews' =>
                        $totalGovernanceReviews,

                    'total_controlled_tests' =>
                        $totalControlledTests,

                    'total_implementation_reviews' =>
                        $totalImplementationReviews,

                    'total_controlled_executions' =>
                        $totalControlledExecutions,

                    'total_monitoring_records' =>
                        $totalMonitoringRecords,

                    'active_monitoring_records' =>
                        $activeMonitoringRecords,

                    'learning_maturity' =>
                        $learningMaturity,

                    'governance_status' =>
                        $governanceStatus,

                    'execution_status' =>
                        $executionStatus,

                    'monitoring_status' =>
                        $monitoringStatus,

                    'overall_improvement_status' =>
                        $overallImprovementStatus,

                    'learning_context' =>
                        $learningContext,

                    'governance_context' =>
                        $governanceContext,

                    'execution_context' =>
                        $executionContext,

                    'monitoring_context' =>
                        $monitoringContext,

                    'lifecycle_summary' =>
                        $lifecycleSummary,

                    'snapshot_payload' => [
                        'snapshot_version' =>
                            '58.2',

                        'automatic_change_authorized' =>
                            false,

                        'automatic_deployment_authorized' =>
                            false,

                        'automatic_rollback_authorized' =>
                            false,

                        'automatic_clinical_action_authorized' =>
                            false,

                        'human_review_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'captured_at' =>
                            now()->toIso8601String(),
                    ],

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

                    'captured_at' =>
                        now(),
                ]);

            return [
                'captured' => true,

                'status' =>
                    'LIFECYCLE_SNAPSHOT_CAPTURED',

                'message' =>
                    'AI improvement lifecycle snapshot captured successfully.',

                'snapshot' => [
                    'snapshot_id' =>
                        $snapshot->id,

                    'snapshot_scope' =>
                        $snapshot->snapshot_scope,

                    'resident_id' =>
                        $snapshot->resident_id,

                    'snapshot_status' =>
                        $snapshot->snapshot_status,

                    'learning_maturity' =>
                        $snapshot->learning_maturity,

                    'governance_status' =>
                        $snapshot->governance_status,

                    'execution_status' =>
                        $snapshot->execution_status,

                    'monitoring_status' =>
                        $snapshot->monitoring_status,

                    'overall_improvement_status' =>
                        $snapshot->overall_improvement_status,

                    'total_learning_evidence' =>
                        $snapshot->total_learning_evidence,

                    'total_governance_reviews' =>
                        $snapshot->total_governance_reviews,

                    'total_controlled_tests' =>
                        $snapshot->total_controlled_tests,

                    'total_implementation_reviews' =>
                        $snapshot->total_implementation_reviews,

                    'total_controlled_executions' =>
                        $snapshot->total_controlled_executions,

                    'total_monitoring_records' =>
                        $snapshot->total_monitoring_records,

                    'active_monitoring_records' =>
                        $snapshot->active_monitoring_records,

                    'captured_at' =>
                        $snapshot->captured_at,
                ],

                'snapshot_guardrails' => [
                    'snapshot_is_ai_change' =>
                        false,

                    'snapshot_is_deployment_authorization' =>
                        false,

                    'snapshot_is_rollback_authorization' =>
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
                        'Lifecycle snapshots consolidate improvement evidence and governance state only. Snapshot capture does not modify AI configuration, deploy changes, trigger rollback, or alter clinical behavior.',
                ],
            ];
        });
    }
}