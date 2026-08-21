<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementExecutionImpactAnalysisEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.3
    | Execution Scope & Impact Analysis
    |--------------------------------------------------------------------------
    |
    | This engine analyzes the registered execution specification.
    |
    | It does NOT authorize execution.
    |
    */

    public function analyze(
        int $executionId
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Execution Record
        |--------------------------------------------------------------------------
        */

        $execution =
            AIImprovementExecution::find(
                $executionId
            );

        if (!$execution) {
            return [
                'analysis_completed' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. State Validation
        |--------------------------------------------------------------------------
        */

        $executionStatus =
            strtoupper(
                (string) (
                    $execution->execution_status
                    ?? ''
                )
            );

        $executionStage =
            strtoupper(
                (string) (
                    $execution->execution_stage
                    ?? ''
                )
            );

        if (
            $executionStatus !== 'REGISTERED'
            ||
            $executionStage !== 'PREPARATION'
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Impact analysis requires a REGISTERED execution in PREPARATION stage.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Guardrail Validation
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $execution->production_execution_allowed) {
            $criticalIssues[] =
                'Production execution permission is already enabled.';
        }

        if ((bool) $execution->automatic_execution_allowed) {
            $criticalIssues[] =
                'Automatic execution permission is already enabled.';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $criticalIssues[] =
                'Automatic deployment permission is already enabled.';
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            $criticalIssues[] =
                'Automatic rollback permission is already enabled.';
        }

        if (!(bool) $execution->human_execution_required) {
            $criticalIssues[] =
                'Human execution requirement is disabled.';
        }

        if (!(bool) $execution->pre_execution_validation_required) {
            $criticalIssues[] =
                'Pre-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->post_execution_validation_required) {
            $criticalIssues[] =
                'Post-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->rollback_plan_required) {
            $criticalIssues[] =
                'Rollback plan requirement is disabled.';
        }

        if (!(bool) $execution->governance_validation_required) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Execution impact analysis is blocked because governance controls are invalid.',
                'execution_id' => $execution->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Source Specification
        |--------------------------------------------------------------------------
        */

        $changeType =
            strtoupper(
                (string) (
                    $execution->change_type
                    ?? 'UNKNOWN'
                )
            );

        $baseline =
            $execution->baseline_configuration
            ?? [];

        $proposed =
            $execution->proposed_configuration
            ?? [];

        $scope =
            $execution->execution_scope
            ?? [];

        if (!is_array($baseline)) {
            $baseline = [];
        }

        if (!is_array($proposed)) {
            $proposed = [];
        }

        if (!is_array($scope)) {
            $scope = [];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Scope Analysis
        |--------------------------------------------------------------------------
        */

        $environment =
            strtoupper(
                (string) (
                    $scope['environment']
                    ?? 'UNSPECIFIED'
                )
            );

        $scopeName =
            strtoupper(
                (string) (
                    $scope['scope']
                    ?? $execution->scope_type
                    ?? 'UNSPECIFIED'
                )
            );

        $productionTargets =
            $scope['production_targets']
            ?? [];

        if (!is_array($productionTargets)) {
            $productionTargets = [];
        }

        $productionTargetCount =
            count(
                $productionTargets
            );

        /*
        |--------------------------------------------------------------------------
        | 6. Change Surface
        |--------------------------------------------------------------------------
        */

        $baselineKeys =
            array_keys($baseline);

        $proposedKeys =
            array_keys($proposed);

        $affectedConfigurationKeys =
            array_values(
                array_unique(
                    array_merge(
                        $baselineKeys,
                        $proposedKeys
                    )
                )
            );

        $configurationChangeCount =
            0;

        foreach (
            $affectedConfigurationKeys
            as $key
        ) {
            $baselineValue =
                $baseline[$key]
                ?? null;

            $proposedValue =
                $proposed[$key]
                ?? null;

            if ($baselineValue !== $proposedValue) {
                $configurationChangeCount++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Change-Type Risk
        |--------------------------------------------------------------------------
        */

        $changeTypeRiskMap = [
            'DOCUMENTATION' => 1,
            'REPORTING' => 1,
            'OBSERVABILITY' => 1,

            'CONFIDENCE_CALIBRATION' => 2,
            'RECOMMENDATION_CONFIGURATION' => 3,
            'WORKFLOW_CONFIGURATION' => 3,

            'PREDICTION_THRESHOLD' => 4,
            'CLINICAL_RULE' => 5,
            'MODEL_CHANGE' => 5,
        ];

        $changeTypeRisk =
            $changeTypeRiskMap[
                $changeType
            ]
            ?? 3;

        /*
        |--------------------------------------------------------------------------
        | 8. Clinical Exposure
        |--------------------------------------------------------------------------
        */

        $clinicalChangeTypes = [
            'CONFIDENCE_CALIBRATION',
            'PREDICTION_THRESHOLD',
            'RECOMMENDATION_CONFIGURATION',
            'WORKFLOW_CONFIGURATION',
            'CLINICAL_RULE',
            'MODEL_CHANGE',
        ];

        $clinicalExposure =
            in_array(
                $changeType,
                $clinicalChangeTypes,
                true
            );

        /*
        |--------------------------------------------------------------------------
        | 9. Production Exposure
        |--------------------------------------------------------------------------
        */

        $productionExposure =
            $environment === 'PRODUCTION'
            ||
            $productionTargetCount > 0;

        /*
        |--------------------------------------------------------------------------
        | 10. Reversibility
        |--------------------------------------------------------------------------
        */

        $baselineAvailable =
            !empty($baseline);

        $proposedAvailable =
            !empty($proposed);

        $reversible =
            $baselineAvailable
            &&
            $proposedAvailable;

        $rollbackComplexity =
            'LOW';

        if (!$reversible) {
            $rollbackComplexity =
                'HIGH';
        } elseif (
            $configurationChangeCount >= 5
            ||
            $changeTypeRisk >= 4
        ) {
            $rollbackComplexity =
                'HIGH';
        } elseif (
            $configurationChangeCount >= 2
            ||
            $changeTypeRisk >= 3
        ) {
            $rollbackComplexity =
                'MEDIUM';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Risk Score
        |--------------------------------------------------------------------------
        */

        $riskScore = 0;

        $riskScore +=
            $changeTypeRisk * 10;

        if ($clinicalExposure) {
            $riskScore += 15;
        }

        if ($productionExposure) {
            $riskScore += 25;
        }

        if (!$reversible) {
            $riskScore += 20;
        }

        if ($configurationChangeCount >= 5) {
            $riskScore += 10;
        } elseif ($configurationChangeCount >= 2) {
            $riskScore += 5;
        }

        $riskScore =
            min(
                100,
                $riskScore
            );

        /*
        |--------------------------------------------------------------------------
        | 12. Risk Classification
        |--------------------------------------------------------------------------
        */

        if ($riskScore >= 75) {
            $riskLevel =
                'CRITICAL';
        } elseif ($riskScore >= 50) {
            $riskLevel =
                'HIGH';
        } elseif ($riskScore >= 25) {
            $riskLevel =
                'MEDIUM';
        } else {
            $riskLevel =
                'LOW';
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Impact Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            'Execution change type is '
            . $changeType
            . '.';

        $findings[] =
            $configurationChangeCount
            . ' configuration value(s) differ between baseline and proposed state.';

        if ($clinicalExposure) {
            $findings[] =
                'The proposed change may influence AI-supported clinical intelligence and therefore requires enhanced human review.';
        }

        if ($productionExposure) {
            $findings[] =
                'Production exposure is present in the current execution scope.';
        } else {
            $findings[] =
                'No production target is currently included in the registered execution scope.';
        }

        if ($reversible) {
            $findings[] =
                'A baseline configuration is available to support rollback planning.';
        } else {
            $findings[] =
                'Rollback planning is constrained because a complete reversible baseline is not available.';
        }

        /*
        |--------------------------------------------------------------------------
        | 14. Execution Readiness At This Stage
        |--------------------------------------------------------------------------
        |
        | Impact analysis alone does NOT make execution ready.
        |
        */

        $impactAnalysis = [

            'analysis_version' =>
                '56.3',

            'change_type' =>
                $changeType,

            'environment' =>
                $environment,

            'scope' =>
                $scopeName,

            'production_target_count' =>
                $productionTargetCount,

            'affected_configuration_keys' =>
                $affectedConfigurationKeys,

            'configuration_change_count' =>
                $configurationChangeCount,

            'clinical_exposure' =>
                $clinicalExposure,

            'production_exposure' =>
                $productionExposure,

            'baseline_available' =>
                $baselineAvailable,

            'proposed_configuration_available' =>
                $proposedAvailable,

            'reversible' =>
                $reversible,

            'rollback_complexity' =>
                $rollbackComplexity,

            'risk_score' =>
                $riskScore,

            'risk_level' =>
                $riskLevel,

            'findings' =>
                $findings,

            'execution_authorized' =>
                false,

            'production_execution_authorized' =>
                false,

            'automatic_execution_authorized' =>
                false,

            'analyzed_at' =>
                now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 15. Persist Analysis
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $execution,
                $impactAnalysis,
                $riskLevel,
                $riskScore
            ) {
                $execution->update([

                    'impact_analysis' =>
                        $impactAnalysis,

                    'execution_stage' =>
                        'IMPACT_ANALYZED',

                    /*
                     * Still not safety-cleared.
                     */
                    'safety_status' =>
                        'PENDING',

                    'execution_review_ready' =>
                        false,

                    'approved_for_execution' =>
                        false,

                    'production_execution_allowed' =>
                        false,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,
                ]);

                $execution->refresh();

                return [

                    'analysis_completed' =>
                        true,

                    'status' =>
                        'IMPACT_ANALYSIS_COMPLETED',

                    'message' =>
                        'AI improvement execution scope and impact analysis completed successfully.',

                    'execution' => [

                        'execution_id' =>
                            $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'safety_status' =>
                            $execution->safety_status,

                        'risk_level' =>
                            $riskLevel,

                        'risk_score' =>
                            $riskScore,

                        'impact_analysis' =>
                            $execution->impact_analysis,

                        'execution_review_ready' =>
                            (bool) $execution->execution_review_ready,

                        'approved_for_execution' =>
                            (bool) $execution->approved_for_execution,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,
                    ],

                    'impact_guardrails' => [

                        'impact_analysis_is_execution_approval' =>
                            false,

                        'execution_authorized' =>
                            false,

                        'production_execution_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'pre_execution_safety_validation_required' =>
                            true,

                        'human_execution_authorization_required' =>
                            true,

                        'rollback_plan_required' =>
                            true,

                        'post_execution_validation_required' =>
                            true,

                        'message' =>
                            'Impact analysis describes execution risk and affected scope only. It does not authorize execution or production modification.',
                    ],
                ];
            }
        );
    }
}