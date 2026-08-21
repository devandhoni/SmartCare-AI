<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;
use App\Models\AILearningEvidence;
use App\Models\AIImprovementTest;
use App\Models\AIImprovementExecution;
use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;

class AIImprovementEvidenceMaturityIntelligenceEngine
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
                'message' => 'Evidence maturity intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Scope
        |--------------------------------------------------------------------------
        */

        $residentId = $snapshot->snapshot_scope === 'RESIDENT'
            ? $snapshot->resident_id
            : null;

        /*
        |--------------------------------------------------------------------------
        | Learning Evidence
        |--------------------------------------------------------------------------
        */

        $learningQuery = AILearningEvidence::query()
            ->where('learning_status', 'EVALUATED');

        if ($residentId !== null) {
            $learningQuery->where('resident_id', $residentId);
        }

        $learningEvidenceCount = (clone $learningQuery)->count();

        $predictionEvidence = (clone $learningQuery)
            ->where('ai_domain', 'PREDICTIVE_HEALTH')
            ->count();

        $recommendationEvidence = (clone $learningQuery)
            ->where('ai_domain', 'CARE_RECOMMENDATION')
            ->count();

        $workflowEvidence = (clone $learningQuery)
            ->where('ai_domain', 'CARE_WORKFLOW')
            ->count();

        $strongEvidence = (clone $learningQuery)
            ->where('evidence_quality', 'STRONG')
            ->count();

        $moderateEvidence = (clone $learningQuery)
            ->where('evidence_quality', 'MODERATE')
            ->count();

        $limitedEvidence = (clone $learningQuery)
            ->where('evidence_quality', 'LIMITED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Controlled Testing Evidence
        |--------------------------------------------------------------------------
        */

        $testQuery = AIImprovementTest::query();

        if ($residentId !== null) {
            $testQuery->where('resident_id', $residentId);
        }

        $totalTests = (clone $testQuery)->count();

        $validatedTests = (clone $testQuery)
            ->where('test_status', 'VALIDATED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Execution Evidence
        |--------------------------------------------------------------------------
        */

        $executionQuery = AIImprovementExecution::query();

        if ($residentId !== null) {
            $executionQuery->where('resident_id', $residentId);
        }

        $totalExecutions = (clone $executionQuery)->count();

        $verifiedExecutions = (clone $executionQuery)
            ->where('execution_status', 'VERIFIED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Monitoring Evidence
        |--------------------------------------------------------------------------
        */

        $monitoringQuery = AIImprovementMonitoring::query();

        if ($residentId !== null) {
            $monitoringQuery->where('resident_id', $residentId);
        }

        $totalMonitoringRecords = (clone $monitoringQuery)->count();

        $activeMonitoringRecords = (clone $monitoringQuery)
            ->where('monitoring_status', 'ACTIVE')
            ->count();

        $monitoringIds = (clone $monitoringQuery)->pluck('id');

        $monitoringObservationCount = AIImprovementMonitoringObservation::whereIn(
            'monitoring_id',
            $monitoringIds
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Domain Coverage
        |--------------------------------------------------------------------------
        */

        $domainCoverage = [
            'prediction_evidence_available' => $predictionEvidence > 0,
            'recommendation_evidence_available' => $recommendationEvidence > 0,
            'workflow_evidence_available' => $workflowEvidence > 0,
            'controlled_test_evidence_available' => $validatedTests > 0,
            'verified_execution_evidence_available' => $verifiedExecutions > 0,
            'longitudinal_monitoring_evidence_available' => $monitoringObservationCount > 0,
        ];

        $availableDomains = collect($domainCoverage)
            ->filter(fn ($available) => $available === true)
            ->count();

        $totalDomains = count($domainCoverage);

        $domainCoveragePercentage = round(
            ($availableDomains / $totalDomains) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Learning Sample Maturity
        |--------------------------------------------------------------------------
        */

        if ($learningEvidenceCount >= 50) {
            $learningSampleMaturity = 'MATURE';
        } elseif ($learningEvidenceCount >= 20) {
            $learningSampleMaturity = 'ESTABLISHED';
        } elseif ($learningEvidenceCount >= 10) {
            $learningSampleMaturity = 'DEVELOPING';
        } elseif ($learningEvidenceCount >= 5) {
            $learningSampleMaturity = 'EARLY';
        } elseif ($learningEvidenceCount > 0) {
            $learningSampleMaturity = 'VERY_EARLY';
        } else {
            $learningSampleMaturity = 'NO_EVIDENCE';
        }

        /*
        |--------------------------------------------------------------------------
        | Monitoring Sample Maturity
        |--------------------------------------------------------------------------
        */

        if ($monitoringObservationCount >= 30) {
            $monitoringSampleMaturity = 'MATURE';
        } elseif ($monitoringObservationCount >= 20) {
            $monitoringSampleMaturity = 'ESTABLISHED';
        } elseif ($monitoringObservationCount >= 10) {
            $monitoringSampleMaturity = 'DEVELOPING';
        } elseif ($monitoringObservationCount >= 5) {
            $monitoringSampleMaturity = 'EARLY';
        } elseif ($monitoringObservationCount > 0) {
            $monitoringSampleMaturity = 'VERY_EARLY';
        } else {
            $monitoringSampleMaturity = 'NO_EVIDENCE';
        }

        /*
        |--------------------------------------------------------------------------
        | Quality Distribution
        |--------------------------------------------------------------------------
        */

        $strongEvidencePercentage = $learningEvidenceCount > 0
            ? round(($strongEvidence / $learningEvidenceCount) * 100, 2)
            : 0;

        $moderateEvidencePercentage = $learningEvidenceCount > 0
            ? round(($moderateEvidence / $learningEvidenceCount) * 100, 2)
            : 0;

        $limitedEvidencePercentage = $learningEvidenceCount > 0
            ? round(($limitedEvidence / $learningEvidenceCount) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Evidence Depth Score
        |--------------------------------------------------------------------------
        |
        | This is evidence architecture depth, not clinical certainty.
        |--------------------------------------------------------------------------
        */

        $depthComponents = [
            'learning_evidence' => min(100, $learningEvidenceCount * 5),
            'validated_testing' => $validatedTests > 0 ? 100 : 0,
            'verified_execution' => $verifiedExecutions > 0 ? 100 : 0,
            'longitudinal_monitoring' => min(100, $monitoringObservationCount * 10),
        ];

        $evidenceDepthScore = round(
            collect($depthComponents)->avg(),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Overall Evidence Maturity
        |--------------------------------------------------------------------------
        */

        if (
            $learningEvidenceCount >= 20 &&
            $monitoringObservationCount >= 20 &&
            $validatedTests > 0 &&
            $verifiedExecutions > 0 &&
            $domainCoveragePercentage >= 80
        ) {
            $overallEvidenceMaturity = 'ESTABLISHED';
        } elseif (
            $learningEvidenceCount >= 10 &&
            $monitoringObservationCount >= 10 &&
            $validatedTests > 0 &&
            $verifiedExecutions > 0
        ) {
            $overallEvidenceMaturity = 'DEVELOPING';
        } elseif (
            $learningEvidenceCount > 0 &&
            $validatedTests > 0 &&
            $verifiedExecutions > 0 &&
            $monitoringObservationCount > 0
        ) {
            $overallEvidenceMaturity = 'EARLY';
        } elseif ($learningEvidenceCount > 0) {
            $overallEvidenceMaturity = 'FOUNDATIONAL';
        } else {
            $overallEvidenceMaturity = 'INSUFFICIENT';
        }

        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */

        if ($overallEvidenceMaturity === 'ESTABLISHED') {
            $overallConfidence = 'HIGH';
        } elseif ($overallEvidenceMaturity === 'DEVELOPING') {
            $overallConfidence = 'MODERATE';
        } elseif ($overallEvidenceMaturity === 'EARLY') {
            $overallConfidence = 'LIMITED';
        } else {
            $overallConfidence = 'VERY LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Maturity Limiters
        |--------------------------------------------------------------------------
        */

        $maturityLimiters = [];

        if ($learningEvidenceCount < 20) {
            $maturityLimiters[] =
                "Validated learning evidence remains below the preferred established threshold of 20 records.";
        }

        if ($monitoringObservationCount < 20) {
            $maturityLimiters[] =
                "Longitudinal monitoring evidence remains below the preferred established threshold of 20 observations.";
        }

        if ($predictionEvidence < 5) {
            $maturityLimiters[] =
                'Prediction evidence remains limited.';
        }

        if ($recommendationEvidence < 5) {
            $maturityLimiters[] =
                'Care recommendation evidence remains limited.';
        }

        if ($workflowEvidence < 5) {
            $maturityLimiters[] =
                'Workflow effectiveness evidence remains limited.';
        }

        if ($validatedTests < 3) {
            $maturityLimiters[] =
                'Controlled-test evidence is based on a very small number of validated tests.';
        }

        if ($verifiedExecutions < 3) {
            $maturityLimiters[] =
                'Verified controlled-execution evidence remains limited.';
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Evidence maturity intelligence currently includes {$learningEvidenceCount} evaluated learning evidence record(s).",
            "Longitudinal monitoring currently contributes {$monitoringObservationCount} post-improvement observation(s).",
            "{$validatedTests} controlled test(s) have completed human validation.",
            "{$verifiedExecutions} controlled execution(s) have verified post-execution outcomes.",
            "Evidence domain coverage is {$domainCoveragePercentage}% across {$availableDomains} of {$totalDomains} tracked evidence areas.",
            "Overall evidence maturity is {$overallEvidenceMaturity} with {$overallConfidence} confidence.",
        ];

        if ($strongEvidence > 0) {
            $findings[] =
                "{$strongEvidencePercentage}% of evaluated learning evidence is currently classified as STRONG.";
        }

        if ($limitedEvidence > 0) {
            $findings[] =
                "{$limitedEvidencePercentage}% of evaluated learning evidence remains classified as LIMITED.";
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'EVIDENCE_MATURITY_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'overall_evidence_maturity' =>
                $overallEvidenceMaturity,

            'overall_confidence' =>
                $overallConfidence,

            'evidence_depth_score' =>
                $evidenceDepthScore,

            'learning_evidence_summary' => [
                'total_evaluated_evidence' =>
                    $learningEvidenceCount,

                'prediction_evidence' =>
                    $predictionEvidence,

                'recommendation_evidence' =>
                    $recommendationEvidence,

                'workflow_evidence' =>
                    $workflowEvidence,

                'learning_sample_maturity' =>
                    $learningSampleMaturity,
            ],

            'evidence_quality_distribution' => [
                'strong_evidence' =>
                    $strongEvidence,

                'moderate_evidence' =>
                    $moderateEvidence,

                'limited_evidence' =>
                    $limitedEvidence,

                'strong_percentage' =>
                    $strongEvidencePercentage,

                'moderate_percentage' =>
                    $moderateEvidencePercentage,

                'limited_percentage' =>
                    $limitedEvidencePercentage,
            ],

            'controlled_validation_summary' => [
                'total_controlled_tests' =>
                    $totalTests,

                'validated_tests' =>
                    $validatedTests,

                'total_controlled_executions' =>
                    $totalExecutions,

                'verified_executions' =>
                    $verifiedExecutions,
            ],

            'longitudinal_evidence_summary' => [
                'total_monitoring_records' =>
                    $totalMonitoringRecords,

                'active_monitoring_records' =>
                    $activeMonitoringRecords,

                'monitoring_observations' =>
                    $monitoringObservationCount,

                'monitoring_sample_maturity' =>
                    $monitoringSampleMaturity,
            ],

            'domain_coverage' =>
                $domainCoverage,

            'domain_coverage_summary' => [
                'available_domains' =>
                    $availableDomains,

                'total_domains' =>
                    $totalDomains,

                'coverage_percentage' =>
                    $domainCoveragePercentage,
            ],

            'evidence_depth_components' =>
                $depthComponents,

            'maturity_limiters' =>
                $maturityLimiters,

            'evidence_findings' =>
                $findings,

            'evidence_guardrails' => [
                'evidence_maturity_is_ai_authority' =>
                    false,

                'evidence_confidence_is_clinical_authority' =>
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
                    'Evidence maturity intelligence describes the strength and breadth of improvement evidence only. It does not expand AI authority, authorize clinical decisions, or permit automatic system changes.',
            ],
        ];
    }
}