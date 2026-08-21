<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;
use App\Models\AIImprovementReview;
use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementExecution;
use App\Models\AIImprovementMonitoring;

class AIImprovementRiskExceptionIntelligenceEngine
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
                'message' => 'Risk and exception intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $residentId = $snapshot->snapshot_scope === 'RESIDENT'
            ? $snapshot->resident_id
            : null;

        /*
        |--------------------------------------------------------------------------
        | Candidate Review Exceptions
        |--------------------------------------------------------------------------
        */

        $reviewQuery = AIImprovementReview::query();

        if ($residentId !== null) {
            $reviewQuery->where('resident_id', $residentId);
        } else {
            $reviewQuery->where('scope_type', 'FACILITY');
        }

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

        /*
        |--------------------------------------------------------------------------
        | Implementation Governance Exceptions
        |--------------------------------------------------------------------------
        */

        $implementationQuery =
            AIImprovementImplementationReview::query();

        if ($residentId !== null) {
            $implementationQuery->where('resident_id', $residentId);
        }

        $pendingImplementationReviews =
            (clone $implementationQuery)
                ->where('review_status', 'PENDING')
                ->count();

        $rejectedImplementationReviews =
            (clone $implementationQuery)
                ->where('review_status', 'REJECTED')
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Execution Risk
        |--------------------------------------------------------------------------
        */

        $executionQuery = AIImprovementExecution::query();

        if ($residentId !== null) {
            $executionQuery->where('resident_id', $residentId);
        }

        $executions = $executionQuery->get();

        $highRiskExecutions = 0;
        $criticalRiskExecutions = 0;
        $rollbackRequiredExecutions = 0;
        $unverifiedCompletedExecutions = 0;

        foreach ($executions as $execution) {
            $impact = $execution->impact_analysis ?? [];
            $verification = $execution->verification_results ?? [];

            if (!is_array($impact)) {
                $impact = [];
            }

            if (!is_array($verification)) {
                $verification = [];
            }

            $riskLevel = strtoupper(
                (string) ($impact['risk_level'] ?? 'UNKNOWN')
            );

            if ($riskLevel === 'HIGH') {
                $highRiskExecutions++;
            }

            if ($riskLevel === 'CRITICAL') {
                $criticalRiskExecutions++;
            }

            if (
                (bool) (
                    $verification['rollback_required']
                    ?? false
                )
            ) {
                $rollbackRequiredExecutions++;
            }

            if (
                strtoupper((string) $execution->execution_status) ===
                    'AWAITING_VERIFICATION'
            ) {
                $unverifiedCompletedExecutions++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Monitoring Exceptions
        |--------------------------------------------------------------------------
        */

        $monitoringQuery = AIImprovementMonitoring::query();

        if ($residentId !== null) {
            $monitoringQuery->where('resident_id', $residentId);
        }

        $monitoringRecords = $monitoringQuery->get();

        $materialRegressionRecords = 0;
        $potentialRegressionRecords = 0;
        $safetyConcernRecords = 0;
        $belowSafetyThresholdRecords = 0;
        $notSustainedRecords = 0;
        $inconclusiveSustainabilityRecords = 0;
        $limitedConfidenceRecords = 0;

        foreach ($monitoringRecords as $monitoring) {
            $regression = strtoupper(
                (string) $monitoring->regression_status
            );

            $safety = strtoupper(
                (string) $monitoring->safety_monitoring_status
            );

            $sustainability = strtoupper(
                (string) $monitoring->sustainability_status
            );

            $analysis = $monitoring->sustainability_analysis ?? [];

            if (!is_array($analysis)) {
                $analysis = [];
            }

            if ($regression === 'REGRESSION_DETECTED') {
                $materialRegressionRecords++;
            }

            if ($regression === 'POTENTIAL_REGRESSION') {
                $potentialRegressionRecords++;
            }

            if ($safety === 'SAFETY_CONCERN') {
                $safetyConcernRecords++;
            }

            if ($safety === 'BELOW_SAFETY_THRESHOLD') {
                $belowSafetyThresholdRecords++;
            }

            if ($sustainability === 'NOT_SUSTAINED') {
                $notSustainedRecords++;
            }

            if ($sustainability === 'INCONCLUSIVE') {
                $inconclusiveSustainabilityRecords++;
            }

            if (
                strtoupper(
                    (string) (
                        $analysis['sustainability_confidence']
                        ?? 'UNKNOWN'
                    )
                ) === 'LIMITED'
            ) {
                $limitedConfidenceRecords++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Governance Integrity Exceptions
        |--------------------------------------------------------------------------
        */

        $testingImplementationOverlap = (clone $reviewQuery)
            ->where('approved_for_testing', true)
            ->where('approved_for_implementation', true)
            ->count();

        $automaticPermissionExceptions = 0;

        foreach ($executions as $execution) {
            if (
                (bool) $execution->production_execution_allowed ||
                (bool) $execution->automatic_execution_allowed ||
                (bool) $execution->automatic_deployment_allowed ||
                (bool) $execution->automatic_rollback_allowed
            ) {
                $automaticPermissionExceptions++;
            }
        }

        foreach ($monitoringRecords as $monitoring) {
            if (
                (bool) $monitoring->automatic_change_allowed ||
                (bool) $monitoring->automatic_rollback_allowed ||
                (bool) $monitoring->automatic_deployment_allowed ||
                (bool) $monitoring->automatic_clinical_action_allowed
            ) {
                $automaticPermissionExceptions++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exception Classification
        |--------------------------------------------------------------------------
        */

        $criticalExceptions =
            $criticalRiskExecutions
            + $safetyConcernRecords
            + $testingImplementationOverlap
            + $automaticPermissionExceptions;

        $highExceptions =
            $highRiskExecutions
            + $materialRegressionRecords
            + $belowSafetyThresholdRecords
            + $notSustainedRecords
            + $rollbackRequiredExecutions;

        $moderateExceptions =
            $potentialRegressionRecords
            + $pendingImplementationReviews
            + $unverifiedCompletedExecutions
            + $inconclusiveSustainabilityRecords;

        $advisoryExceptions =
            $pendingReviews
            + $deferredReviews
            + $rejectedReviews
            + $limitedConfidenceRecords;

        $totalExceptions =
            $criticalExceptions
            + $highExceptions
            + $moderateExceptions
            + $advisoryExceptions;

        /*
        |--------------------------------------------------------------------------
        | Portfolio Risk Level
        |--------------------------------------------------------------------------
        */

        if ($criticalExceptions > 0) {
            $portfolioRiskLevel = 'CRITICAL';
        } elseif ($highExceptions > 0) {
            $portfolioRiskLevel = 'HIGH';
        } elseif ($moderateExceptions > 0) {
            $portfolioRiskLevel = 'MODERATE';
        } elseif ($advisoryExceptions > 0) {
            $portfolioRiskLevel = 'LOW_WITH_ADVISORIES';
        } else {
            $portfolioRiskLevel = 'LOW';
        }

        /*
        |--------------------------------------------------------------------------
        | Exception Status
        |--------------------------------------------------------------------------
        */

        if ($criticalExceptions > 0) {
            $exceptionStatus = 'CRITICAL_EXCEPTIONS_PRESENT';
        } elseif ($highExceptions > 0) {
            $exceptionStatus = 'MATERIAL_EXCEPTIONS_PRESENT';
        } elseif ($moderateExceptions > 0) {
            $exceptionStatus = 'REVIEW_EXCEPTIONS_PRESENT';
        } elseif ($advisoryExceptions > 0) {
            $exceptionStatus = 'ADVISORIES_PRESENT';
        } else {
            $exceptionStatus = 'NO_EXCEPTIONS';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Cross-improvement risk intelligence identified {$totalExceptions} current exception or advisory item(s).",
            "Current portfolio risk level is {$portfolioRiskLevel}.",
            "{$criticalExceptions} critical, {$highExceptions} high, {$moderateExceptions} moderate, and {$advisoryExceptions} advisory item(s) are currently identified.",
        ];

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
                "{$rejectedReviews} candidate governance review(s) are recorded as rejected.";
        }

        if ($limitedConfidenceRecords > 0) {
            $findings[] =
                "{$limitedConfidenceRecords} monitoring record(s) currently have LIMITED sustainability confidence.";
        }

        if ($criticalExceptions === 0) {
            $findings[] =
                'No critical governance, safety, or automatic-permission exception is currently detected.';
        }

        if ($highExceptions === 0) {
            $findings[] =
                'No high-severity regression, rollback, sustainability, or execution-risk exception is currently detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $priorities = [];

        if ($criticalExceptions > 0) {
            $priorities[] =
                'Escalate critical improvement exceptions for immediate human governance review.';
        }

        if ($highExceptions > 0) {
            $priorities[] =
                'Prioritize human review of high-severity improvement risks before further lifecycle progression.';
        }

        if ($pendingReviews > 0) {
            $priorities[] =
                'Continue processing pending candidate governance reviews.';
        }

        if ($deferredReviews > 0) {
            $priorities[] =
                'Reassess deferred candidates only after additional supporting evidence is available.';
        }

        if ($limitedConfidenceRecords > 0) {
            $priorities[] =
                'Continue longitudinal evidence collection to improve sustainability confidence.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'RISK_EXCEPTION_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'portfolio_risk_level' =>
                $portfolioRiskLevel,

            'exception_status' =>
                $exceptionStatus,

            'exception_summary' => [
                'total_exceptions' =>
                    $totalExceptions,

                'critical_exceptions' =>
                    $criticalExceptions,

                'high_exceptions' =>
                    $highExceptions,

                'moderate_exceptions' =>
                    $moderateExceptions,

                'advisory_exceptions' =>
                    $advisoryExceptions,
            ],

            'governance_exceptions' => [
                'pending_candidate_reviews' =>
                    $pendingReviews,

                'deferred_candidate_reviews' =>
                    $deferredReviews,

                'rejected_candidate_reviews' =>
                    $rejectedReviews,

                'testing_approved_reviews' =>
                    $testingApprovedReviews,

                'pending_implementation_reviews' =>
                    $pendingImplementationReviews,

                'rejected_implementation_reviews' =>
                    $rejectedImplementationReviews,

                'testing_implementation_overlap' =>
                    $testingImplementationOverlap,
            ],

            'execution_exceptions' => [
                'high_risk_executions' =>
                    $highRiskExecutions,

                'critical_risk_executions' =>
                    $criticalRiskExecutions,

                'rollback_required_executions' =>
                    $rollbackRequiredExecutions,

                'unverified_completed_executions' =>
                    $unverifiedCompletedExecutions,

                'automatic_permission_exceptions' =>
                    $automaticPermissionExceptions,
            ],

            'monitoring_exceptions' => [
                'material_regression_records' =>
                    $materialRegressionRecords,

                'potential_regression_records' =>
                    $potentialRegressionRecords,

                'safety_concern_records' =>
                    $safetyConcernRecords,

                'below_safety_threshold_records' =>
                    $belowSafetyThresholdRecords,

                'not_sustained_records' =>
                    $notSustainedRecords,

                'inconclusive_sustainability_records' =>
                    $inconclusiveSustainabilityRecords,

                'limited_confidence_records' =>
                    $limitedConfidenceRecords,
            ],

            'risk_findings' =>
                $findings,

            'management_priorities' =>
                $priorities,

            'risk_guardrails' => [
                'risk_intelligence_is_execution_authorization' =>
                    false,

                'risk_intelligence_triggers_automatic_rollback' =>
                    false,

                'risk_intelligence_triggers_automatic_change' =>
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
                    'Cross-improvement risk and exception intelligence identifies governance, execution, safety, regression, and evidence concerns only. It does not automatically modify, execute, rollback, deploy, or alter clinical AI behavior.',
            ],
        ];
    }
}