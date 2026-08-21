<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;
use App\Models\AIImprovementTest;
use App\Models\AIImprovementExecution;
use App\Models\AIImprovementMonitoring;

class AIImprovementOutcomeIntelligenceEngine
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
                'message' => 'Improvement outcome intelligence is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        $residentId = $snapshot->snapshot_scope === 'RESIDENT'
            ? $snapshot->resident_id
            : null;

        /*
        |--------------------------------------------------------------------------
        | Controlled Tests
        |--------------------------------------------------------------------------
        */

        $testQuery = AIImprovementTest::query();

        if ($residentId !== null) {
            $testQuery->where('resident_id', $residentId);
        }

        $tests = $testQuery->get();

        $validatedTests = $tests->filter(
            fn ($test) =>
                strtoupper((string) $test->test_status) === 'VALIDATED'
        );

        $positiveTestSignals = 0;
        $stableTestSignals = 0;
        $negativeTestSignals = 0;

        foreach ($validatedTests as $test) {
            $results = $test->test_results ?? [];

            if (!is_array($results)) {
                $results = [];
            }

            $outcome = strtoupper(
                (string) ($results['outcome_status'] ?? 'UNKNOWN')
            );

            $direction = strtoupper(
                (string) ($results['direction'] ?? 'UNKNOWN')
            );

            if (
                $outcome === 'POSITIVE_SIGNAL'
                || $direction === 'IMPROVED'
            ) {
                $positiveTestSignals++;
            } elseif (
                $outcome === 'STABLE_SIGNAL'
                || $direction === 'STABLE'
            ) {
                $stableTestSignals++;
            } elseif (
                in_array(
                    $outcome,
                    ['NEGATIVE_SIGNAL', 'SAFETY_CONCERN'],
                    true
                )
                || $direction === 'DETERIORATED'
            ) {
                $negativeTestSignals++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Controlled Executions
        |--------------------------------------------------------------------------
        */

        $executionQuery = AIImprovementExecution::query();

        if ($residentId !== null) {
            $executionQuery->where('resident_id', $residentId);
        }

        $executions = $executionQuery->get();

        $verifiedExecutions = $executions->filter(
            fn ($execution) =>
                strtoupper((string) $execution->execution_status) === 'VERIFIED'
        );

        $positiveExecutionSignals = 0;
        $stableExecutionSignals = 0;
        $negativeExecutionSignals = 0;

        foreach ($verifiedExecutions as $execution) {
            $results = $execution->execution_results ?? [];

            if (!is_array($results)) {
                $results = [];
            }

            $verification = $execution->verification_results ?? [];

            if (!is_array($verification)) {
                $verification = [];
            }

            $outcome = strtoupper(
                (string) ($results['outcome_status'] ?? 'UNKNOWN')
            );

            $direction = strtoupper(
                (string) ($results['direction'] ?? 'UNKNOWN')
            );

            $verified = (bool) (
                $verification['controlled_execution_verified']
                ?? false
            );

            $rollbackRequired = (bool) (
                $verification['rollback_required']
                ?? false
            );

            if (
                $verified
                && !$rollbackRequired
                && (
                    $outcome === 'POSITIVE_SIGNAL'
                    || $direction === 'IMPROVED'
                )
            ) {
                $positiveExecutionSignals++;
            } elseif (
                $verified
                && !$rollbackRequired
                && (
                    $outcome === 'STABLE_SIGNAL'
                    || $direction === 'STABLE'
                )
            ) {
                $stableExecutionSignals++;
            } elseif (
                $rollbackRequired
                || in_array(
                    $outcome,
                    ['NEGATIVE_SIGNAL', 'SAFETY_CONCERN'],
                    true
                )
                || $direction === 'DETERIORATED'
            ) {
                $negativeExecutionSignals++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Longitudinal Monitoring
        |--------------------------------------------------------------------------
        */

        $monitoringQuery = AIImprovementMonitoring::query();

        if ($residentId !== null) {
            $monitoringQuery->where('resident_id', $residentId);
        }

        $monitoringRecords = $monitoringQuery->get();

        $positiveMonitoringSignals = 0;
        $stableMonitoringSignals = 0;
        $negativeMonitoringSignals = 0;
        $inconclusiveMonitoringSignals = 0;

        foreach ($monitoringRecords as $monitoring) {
            $sustainability = strtoupper(
                (string) $monitoring->sustainability_status
            );

            $regression = strtoupper(
                (string) $monitoring->regression_status
            );

            $safety = strtoupper(
                (string) $monitoring->safety_monitoring_status
            );

            if (
                in_array(
                    $sustainability,
                    ['SUSTAINED', 'PROVISIONALLY_SUSTAINED'],
                    true
                )
                &&
                in_array(
                    $regression,
                    ['NO_REGRESSION', 'NO_MATERIAL_REGRESSION'],
                    true
                )
                &&
                $safety === 'SAFE_WITH_MONITORING'
            ) {
                $positiveMonitoringSignals++;
            } elseif (
                $sustainability === 'INCONCLUSIVE'
            ) {
                $inconclusiveMonitoringSignals++;
            } elseif (
                $sustainability === 'NOT_SUSTAINED'
                || $regression === 'REGRESSION_DETECTED'
                || in_array(
                    $safety,
                    ['SAFETY_CONCERN', 'BELOW_SAFETY_THRESHOLD'],
                    true
                )
            ) {
                $negativeMonitoringSignals++;
            } elseif (
                strtoupper(
                    (string) $monitoring->performance_direction
                ) === 'STABLE'
            ) {
                $stableMonitoringSignals++;
            } else {
                $inconclusiveMonitoringSignals++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Outcome Totals
        |--------------------------------------------------------------------------
        */

        $positiveSignals =
            $positiveTestSignals
            + $positiveExecutionSignals
            + $positiveMonitoringSignals;

        $stableSignals =
            $stableTestSignals
            + $stableExecutionSignals
            + $stableMonitoringSignals;

        $negativeSignals =
            $negativeTestSignals
            + $negativeExecutionSignals
            + $negativeMonitoringSignals;

        $inconclusiveSignals =
            $inconclusiveMonitoringSignals;

        $totalOutcomeSignals =
            $positiveSignals
            + $stableSignals
            + $negativeSignals
            + $inconclusiveSignals;

        /*
        |--------------------------------------------------------------------------
        | Outcome Classification
        |--------------------------------------------------------------------------
        */

        if ($totalOutcomeSignals === 0) {
            $overallOutcome = 'INCONCLUSIVE';
        } elseif ($negativeSignals > 0 && $positiveSignals > 0) {
            $overallOutcome = 'MIXED';
        } elseif ($negativeSignals > 0) {
            $overallOutcome = 'NEGATIVE';
        } elseif (
            $positiveSignals > 0
            &&
            $negativeSignals === 0
        ) {
            $overallOutcome = 'POSITIVE';
        } elseif (
            $stableSignals > 0
            &&
            $negativeSignals === 0
        ) {
            $overallOutcome = 'STABLE';
        } else {
            $overallOutcome = 'INCONCLUSIVE';
        }

        /*
        |--------------------------------------------------------------------------
        | Outcome Confidence
        |--------------------------------------------------------------------------
        */

        if ($totalOutcomeSignals >= 10) {
            $outcomeConfidence = 'HIGH';
        } elseif ($totalOutcomeSignals >= 5) {
            $outcomeConfidence = 'MODERATE';
        } elseif ($totalOutcomeSignals >= 3) {
            $outcomeConfidence = 'LIMITED';
        } else {
            $outcomeConfidence = 'VERY LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Outcome Score
        |--------------------------------------------------------------------------
        */

        if ($totalOutcomeSignals > 0) {
            $outcomeScore = round(
                (
                    (($positiveSignals * 100)
                    + ($stableSignals * 70)
                    + ($inconclusiveSignals * 40))
                    / $totalOutcomeSignals
                ),
                2
            );
        } else {
            $outcomeScore = 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "{$validatedTests->count()} validated controlled test(s) contribute to improvement outcome intelligence.",
            "{$verifiedExecutions->count()} verified controlled execution(s) contribute to improvement outcome intelligence.",
            "{$monitoringRecords->count()} longitudinal monitoring record(s) contribute to improvement outcome intelligence.",
            "Current consolidated outcome includes {$positiveSignals} positive, {$stableSignals} stable, {$negativeSignals} negative, and {$inconclusiveSignals} inconclusive signal(s).",
            "Overall improvement outcome is {$overallOutcome} with {$outcomeConfidence} outcome confidence.",
        ];

        if ($overallOutcome === 'POSITIVE') {
            $findings[] =
                'Current governed improvement evidence is directionally positive across the available validation stages.';
        }

        if ($outcomeConfidence === 'LIMITED' || $outcomeConfidence === 'VERY LIMITED') {
            $findings[] =
                'Outcome interpretation remains preliminary because only a small number of validated lifecycle outcomes are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'IMPROVEMENT_OUTCOME_INTELLIGENCE_AVAILABLE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'overall_outcome' =>
                $overallOutcome,

            'outcome_confidence' =>
                $outcomeConfidence,

            'outcome_score' =>
                $outcomeScore,

            'controlled_test_outcomes' => [
                'validated_tests' =>
                    $validatedTests->count(),

                'positive_signals' =>
                    $positiveTestSignals,

                'stable_signals' =>
                    $stableTestSignals,

                'negative_signals' =>
                    $negativeTestSignals,
            ],

            'controlled_execution_outcomes' => [
                'verified_executions' =>
                    $verifiedExecutions->count(),

                'positive_signals' =>
                    $positiveExecutionSignals,

                'stable_signals' =>
                    $stableExecutionSignals,

                'negative_signals' =>
                    $negativeExecutionSignals,
            ],

            'longitudinal_monitoring_outcomes' => [
                'monitoring_records' =>
                    $monitoringRecords->count(),

                'positive_signals' =>
                    $positiveMonitoringSignals,

                'stable_signals' =>
                    $stableMonitoringSignals,

                'negative_signals' =>
                    $negativeMonitoringSignals,

                'inconclusive_signals' =>
                    $inconclusiveMonitoringSignals,
            ],

            'consolidated_outcome_summary' => [
                'total_outcome_signals' =>
                    $totalOutcomeSignals,

                'positive_signals' =>
                    $positiveSignals,

                'stable_signals' =>
                    $stableSignals,

                'negative_signals' =>
                    $negativeSignals,

                'inconclusive_signals' =>
                    $inconclusiveSignals,
            ],

            'outcome_findings' =>
                $findings,

            'outcome_guardrails' => [
                'outcome_intelligence_is_implementation_approval' =>
                    false,

                'positive_outcome_expands_ai_authority' =>
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
                    'Improvement outcome intelligence summarizes observed governed outcomes only. Positive results do not authorize autonomous AI changes, deployment, rollback, or clinical action.',
            ],
        ];
    }
}