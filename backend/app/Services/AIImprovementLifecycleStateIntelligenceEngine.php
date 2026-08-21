<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;

class AIImprovementLifecycleStateIntelligenceEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Snapshot
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
        | 2. Governance Isolation
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
                'message' => 'Lifecycle intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Current Lifecycle State
        |--------------------------------------------------------------------------
        */

        $learningMaturity = strtoupper(
            (string) $snapshot->learning_maturity
        );

        $governanceStatus = strtoupper(
            (string) $snapshot->governance_status
        );

        $executionStatus = strtoupper(
            (string) $snapshot->execution_status
        );

        $monitoringStatus = strtoupper(
            (string) $snapshot->monitoring_status
        );

        $overallStatus = strtoupper(
            (string) $snapshot->overall_improvement_status
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Lifecycle Stage
        |--------------------------------------------------------------------------
        */

        if ($monitoringStatus === 'ACTIVE_MONITORING') {
            $lifecycleStage = 'POST_EXECUTION_MONITORING';
        } elseif ($executionStatus === 'VERIFIED_EXECUTION_AVAILABLE') {
            $lifecycleStage = 'EXECUTION_VERIFIED';
        } elseif ($governanceStatus === 'IMPLEMENTATION_APPROVAL_AVAILABLE') {
            $lifecycleStage = 'IMPLEMENTATION_GOVERNANCE';
        } elseif ($governanceStatus === 'TESTING_APPROVAL_AVAILABLE') {
            $lifecycleStage = 'CONTROLLED_TESTING_GOVERNANCE';
        } elseif ($snapshot->total_governance_reviews > 0) {
            $lifecycleStage = 'IMPROVEMENT_GOVERNANCE';
        } elseif ($snapshot->total_learning_evidence > 0) {
            $lifecycleStage = 'LEARNING_AND_CANDIDATE_DISCOVERY';
        } else {
            $lifecycleStage = 'NO_ACTIVE_IMPROVEMENT_LIFECYCLE';
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Stage Progress Score
        |--------------------------------------------------------------------------
        |
        | This is architectural progress, not clinical confidence.
        |--------------------------------------------------------------------------
        */

        $stageScoreMap = [
            'NO_ACTIVE_IMPROVEMENT_LIFECYCLE' => 0,
            'LEARNING_AND_CANDIDATE_DISCOVERY' => 20,
            'IMPROVEMENT_GOVERNANCE' => 40,
            'CONTROLLED_TESTING_GOVERNANCE' => 55,
            'IMPLEMENTATION_GOVERNANCE' => 70,
            'EXECUTION_VERIFIED' => 85,
            'POST_EXECUTION_MONITORING' => 100,
        ];

        $lifecycleProgressScore =
            $stageScoreMap[$lifecycleStage] ?? 0;

        /*
        |--------------------------------------------------------------------------
        | 6. Lifecycle Capability Matrix
        |--------------------------------------------------------------------------
        */

        $capabilities = [
            'learning_evidence_available' =>
                $snapshot->total_learning_evidence > 0,

            'governance_review_available' =>
                $snapshot->total_governance_reviews > 0,

            'controlled_testing_available' =>
                $snapshot->total_controlled_tests > 0,

            'implementation_review_available' =>
                $snapshot->total_implementation_reviews > 0,

            'controlled_execution_available' =>
                $snapshot->total_controlled_executions > 0,

            'longitudinal_monitoring_available' =>
                $snapshot->total_monitoring_records > 0,

            'active_longitudinal_monitoring' =>
                $snapshot->active_monitoring_records > 0,
        ];

        $availableCapabilities = collect($capabilities)
            ->filter(fn ($value) => $value === true)
            ->count();

        $totalCapabilities = count($capabilities);

        $capabilityCoveragePercentage = round(
            ($availableCapabilities / $totalCapabilities) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Learning Readiness
        |--------------------------------------------------------------------------
        */

        if ($learningMaturity === 'MATURE LEARNING') {
            $learningReadiness = 'MATURE';
        } elseif ($learningMaturity === 'DEVELOPING LEARNING') {
            $learningReadiness = 'DEVELOPING';
        } elseif ($learningMaturity === 'EARLY LEARNING') {
            $learningReadiness = 'EARLY';
        } else {
            $learningReadiness = 'UNAVAILABLE';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Governance Readiness
        |--------------------------------------------------------------------------
        */

        if ($governanceStatus === 'IMPLEMENTATION_APPROVAL_AVAILABLE') {
            $governanceReadiness = 'ADVANCED';
        } elseif ($governanceStatus === 'TESTING_APPROVAL_AVAILABLE') {
            $governanceReadiness = 'TESTING_STAGE';
        } elseif ($governanceStatus === 'GOVERNANCE_REVIEW_AVAILABLE') {
            $governanceReadiness = 'REVIEW_STAGE';
        } else {
            $governanceReadiness = 'NOT_STARTED';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Execution Readiness
        |--------------------------------------------------------------------------
        */

        if ($executionStatus === 'VERIFIED_EXECUTION_AVAILABLE') {
            $executionReadiness = 'VERIFIED';
        } elseif ($executionStatus === 'EXECUTION_ACTIVITY_AVAILABLE') {
            $executionReadiness = 'IN_PROGRESS';
        } else {
            $executionReadiness = 'NOT_STARTED';
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Monitoring Readiness
        |--------------------------------------------------------------------------
        */

        if ($monitoringStatus === 'ACTIVE_MONITORING') {
            $monitoringReadiness = 'ACTIVE';
        } elseif ($monitoringStatus === 'MONITORING_HISTORY_AVAILABLE') {
            $monitoringReadiness = 'HISTORICAL';
        } else {
            $monitoringReadiness = 'NOT_STARTED';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Lifecycle Maturity
        |--------------------------------------------------------------------------
        */

        if (
            $lifecycleStage === 'POST_EXECUTION_MONITORING'
            &&
            $learningReadiness === 'MATURE'
        ) {
            $lifecycleMaturity = 'MATURE_LIFECYCLE';
        } elseif (
            in_array(
                $lifecycleStage,
                [
                    'EXECUTION_VERIFIED',
                    'POST_EXECUTION_MONITORING',
                ],
                true
            )
        ) {
            $lifecycleMaturity = 'OPERATIONALLY_ADVANCED';
        } elseif (
            in_array(
                $lifecycleStage,
                [
                    'CONTROLLED_TESTING_GOVERNANCE',
                    'IMPLEMENTATION_GOVERNANCE',
                ],
                true
            )
        ) {
            $lifecycleMaturity = 'GOVERNED_DEVELOPMENT';
        } elseif (
            $lifecycleStage === 'IMPROVEMENT_GOVERNANCE'
        ) {
            $lifecycleMaturity = 'EARLY_GOVERNANCE';
        } elseif (
            $lifecycleStage === 'LEARNING_AND_CANDIDATE_DISCOVERY'
        ) {
            $lifecycleMaturity = 'EARLY_LEARNING';
        } else {
            $lifecycleMaturity = 'NOT_ESTABLISHED';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Intelligence Confidence
        |--------------------------------------------------------------------------
        */

        if ($snapshot->total_learning_evidence >= 20) {
            $intelligenceConfidence = 'HIGH';
        } elseif ($snapshot->total_learning_evidence >= 10) {
            $intelligenceConfidence = 'MODERATE';
        } elseif ($snapshot->total_learning_evidence > 0) {
            $intelligenceConfidence = 'LIMITED';
        } else {
            $intelligenceConfidence = 'VERY LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            "Current improvement lifecycle stage is {$lifecycleStage}.";

        $findings[] =
            "Lifecycle architecture coverage is {$capabilityCoveragePercentage}% across {$availableCapabilities} of {$totalCapabilities} tracked capabilities.";

        $findings[] =
            "Learning readiness is {$learningReadiness}, governance readiness is {$governanceReadiness}, execution readiness is {$executionReadiness}, and monitoring readiness is {$monitoringReadiness}.";

        $findings[] =
            "Current lifecycle maturity is {$lifecycleMaturity}.";

        if ($learningReadiness === 'EARLY') {
            $findings[] =
                'The lifecycle has advanced operationally, but learning evidence remains early and should not be treated as mature statistical evidence.';
        }

        if ($monitoringReadiness === 'ACTIVE') {
            $findings[] =
                'Post-execution longitudinal monitoring is currently active.';
        }

        if ($intelligenceConfidence === 'LIMITED') {
            $findings[] =
                'Lifecycle intelligence confidence remains limited because the learning evidence base is still small.';
        }

        /*
        |--------------------------------------------------------------------------
        | 14. State Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'LIFECYCLE_STATE_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'current_lifecycle_state' => [
                'overall_improvement_status' =>
                    $overallStatus,

                'lifecycle_stage' =>
                    $lifecycleStage,

                'lifecycle_progress_score' =>
                    $lifecycleProgressScore,

                'lifecycle_maturity' =>
                    $lifecycleMaturity,

                'intelligence_confidence' =>
                    $intelligenceConfidence,
            ],

            'stage_readiness' => [
                'learning_readiness' =>
                    $learningReadiness,

                'governance_readiness' =>
                    $governanceReadiness,

                'execution_readiness' =>
                    $executionReadiness,

                'monitoring_readiness' =>
                    $monitoringReadiness,
            ],

            'capability_matrix' =>
                $capabilities,

            'capability_summary' => [
                'available_capabilities' =>
                    $availableCapabilities,

                'total_capabilities' =>
                    $totalCapabilities,

                'coverage_percentage' =>
                    $capabilityCoveragePercentage,
            ],

            'source_snapshot_context' => [
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

                'learning_maturity' =>
                    $snapshot->learning_maturity,

                'governance_status' =>
                    $snapshot->governance_status,

                'execution_status' =>
                    $snapshot->execution_status,

                'monitoring_status' =>
                    $snapshot->monitoring_status,
            ],

            'lifecycle_findings' =>
                $findings,

            'lifecycle_guardrails' => [
                'state_intelligence_is_ai_change' =>
                    false,

                'state_intelligence_is_deployment_authorization' =>
                    false,

                'state_intelligence_is_rollback_authorization' =>
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
                    'Lifecycle state intelligence summarizes the current improvement lifecycle only. It does not authorize AI modification, deployment, rollback, or clinical action.',
            ],
        ];
    }
}