<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementPreExecutionSafetyValidationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.4
    | Pre-Execution Safety Validation
    |--------------------------------------------------------------------------
    |
    | This engine evaluates whether an analyzed execution package may advance
    | to human execution authorization review.
    |
    | It does NOT authorize execution.
    |
    */

    public function analyze(int $executionId): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Execution
        |--------------------------------------------------------------------------
        */

        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'validation_completed' => false,
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

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        if (
            $executionStatus !== 'REGISTERED'
            ||
            $executionStage !== 'IMPACT_ANALYZED'
        ) {
            return [
                'validation_completed' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Pre-execution safety validation requires a REGISTERED execution in IMPACT_ANALYZED stage.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Validate Governance Guardrails
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $execution->production_execution_allowed) {
            $criticalIssues[] = 'Production execution permission is enabled.';
        }

        if ((bool) $execution->automatic_execution_allowed) {
            $criticalIssues[] = 'Automatic execution permission is enabled.';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $criticalIssues[] = 'Automatic deployment permission is enabled.';
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            $criticalIssues[] = 'Automatic rollback permission is enabled.';
        }

        if (!(bool) $execution->human_execution_required) {
            $criticalIssues[] = 'Human execution requirement is disabled.';
        }

        if (!(bool) $execution->pre_execution_validation_required) {
            $criticalIssues[] = 'Pre-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->post_execution_validation_required) {
            $criticalIssues[] = 'Post-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->rollback_plan_required) {
            $criticalIssues[] = 'Rollback plan requirement is disabled.';
        }

        if (!(bool) $execution->governance_validation_required) {
            $criticalIssues[] = 'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'validation_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Pre-execution safety validation is blocked because governance controls are invalid.',
                'execution_id' => $execution->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Load Impact Analysis
        |--------------------------------------------------------------------------
        */

        $impact = $execution->impact_analysis ?? [];

        if (!is_array($impact) || empty($impact)) {
            return [
                'validation_completed' => false,
                'status' => 'IMPACT_ANALYSIS_MISSING',
                'message' => 'Execution impact analysis is required before pre-execution safety validation.',
                'execution_id' => $execution->id,
            ];
        }

        $riskLevel = strtoupper(
            (string) ($impact['risk_level'] ?? 'UNKNOWN')
        );

        $riskScore = (float) ($impact['risk_score'] ?? 100);

        $productionExposure = (bool) (
            $impact['production_exposure']
            ?? false
        );

        $clinicalExposure = (bool) (
            $impact['clinical_exposure']
            ?? false
        );

        $reversible = (bool) (
            $impact['reversible']
            ?? false
        );

        $rollbackComplexity = strtoupper(
            (string) (
                $impact['rollback_complexity']
                ?? 'UNKNOWN'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Source Configuration
        |--------------------------------------------------------------------------
        */

        $baseline = $execution->baseline_configuration ?? [];
        $proposed = $execution->proposed_configuration ?? [];
        $scope = $execution->execution_scope ?? [];

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
        | 6. Safety Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['impact_analysis_available'] = [
            'passed' => !empty($impact),
            'message' => 'Impact analysis is available.',
        ];

        $checks['risk_not_critical'] = [
            'passed' => $riskLevel !== 'CRITICAL',
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'message' => 'Execution risk must not be classified as CRITICAL.',
        ];

        $checks['no_production_exposure'] = [
            'passed' => !$productionExposure,
            'production_exposure' => $productionExposure,
            'message' => 'Pre-execution validation requires no production target exposure at this controlled stage.',
        ];

        $checks['reversible_change'] = [
            'passed' => $reversible,
            'reversible' => $reversible,
            'rollback_complexity' => $rollbackComplexity,
            'message' => 'Execution package must have a reversible baseline.',
        ];

        $checks['baseline_configuration_available'] = [
            'passed' => !empty($baseline),
            'message' => 'Baseline configuration must be available for rollback planning.',
        ];

        $checks['proposed_configuration_available'] = [
            'passed' => !empty($proposed),
            'message' => 'Proposed configuration must be available.',
        ];

        $environment = strtoupper(
            (string) ($scope['environment'] ?? 'UNSPECIFIED')
        );

        $checks['controlled_environment'] = [
            'passed' => $environment === 'CONTROLLED',
            'environment' => $environment,
            'message' => 'Execution preparation must remain in a controlled environment.',
        ];

        $productionTargets = $scope['production_targets'] ?? [];

        if (!is_array($productionTargets)) {
            $productionTargets = [];
        }

        $checks['no_production_targets'] = [
            'passed' => count($productionTargets) === 0,
            'production_target_count' => count($productionTargets),
            'message' => 'Controlled execution preparation must not include production targets.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 7. Clinical Exposure Check
        |--------------------------------------------------------------------------
        |
        | Clinical exposure does not automatically fail validation.
        | It requires enhanced human governance.
        |
        */

        $checks['clinical_exposure_governed'] = [
            'passed' =>
                !$clinicalExposure
                ||
                (
                    (bool) $execution->human_execution_required
                    &&
                    (bool) $execution->post_execution_validation_required
                    &&
                    (bool) $execution->governance_validation_required
                ),

            'clinical_exposure' => $clinicalExposure,

            'message' =>
                'Clinical-impacting changes require human execution and post-execution governance controls.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 8. Automatic Execution Isolation
        |--------------------------------------------------------------------------
        */

        $checks['automatic_execution_disabled'] = [
            'passed' =>
                !(bool) $execution->automatic_execution_allowed
                &&
                !(bool) $execution->automatic_deployment_allowed
                &&
                !(bool) $execution->automatic_rollback_allowed,

            'message' => 'All automatic execution, deployment, and rollback permissions must remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 9. Prohibited Configuration Scan
        |--------------------------------------------------------------------------
        */

        $prohibitedKeys = [
            'automatic_execution_allowed',
            'automatic_deployment_allowed',
            'automatic_rollback_allowed',
            'production_execution_allowed',
            'automatic_model_change',
            'automatic_threshold_change',
            'automatic_confidence_change',
            'automatic_recommendation_change',
            'automatic_workflow_change',
            'automatic_clinical_rule_change',
            'automatic_clinical_action',
        ];

        $prohibitedMatches = [];

        foreach ([$proposed, $scope] as $configuration) {
            foreach ($prohibitedKeys as $key) {
                if (
                    array_key_exists($key, $configuration)
                    &&
                    $configuration[$key] === true
                ) {
                    $prohibitedMatches[] = $key;
                }
            }
        }

        $prohibitedMatches = array_values(
            array_unique($prohibitedMatches)
        );

        $checks['prohibited_permissions_absent'] = [
            'passed' => empty($prohibitedMatches),
            'prohibited_matches' => $prohibitedMatches,
            'message' => 'Execution specification must not contain prohibited automatic or production permissions.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 10. Count Validation Results
        |--------------------------------------------------------------------------
        */

        $passedChecks = 0;
        $failedChecks = 0;

        foreach ($checks as $check) {
            if ((bool) ($check['passed'] ?? false)) {
                $passedChecks++;
            } else {
                $failedChecks++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Determine Safety Status
        |--------------------------------------------------------------------------
        */

        $safetyPassed = $failedChecks === 0;

        if ($safetyPassed) {
            $safetyStatus = 'SAFE_FOR_HUMAN_EXECUTION_REVIEW';
        } elseif ($riskLevel === 'CRITICAL' || $productionExposure) {
            $safetyStatus = 'BLOCKED';
        } else {
            $safetyStatus = 'REQUIRES_REMEDIATION';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Safety Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        if ($safetyPassed) {
            $findings[] =
                'Pre-execution safety validation passed all current checks.';

            $findings[] =
                'Execution package may advance to separate human execution authorization review.';
        } else {
            $findings[] =
                $failedChecks
                . ' pre-execution safety check(s) failed.';
        }

        if ($clinicalExposure) {
            $findings[] =
                'Clinical exposure is present and requires enhanced human supervision.';
        }

        if (!$productionExposure) {
            $findings[] =
                'No production target exposure is currently present.';
        }

        if ($reversible) {
            $findings[] =
                'Baseline configuration is available to support rollback planning.';
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Safety Payload
        |--------------------------------------------------------------------------
        */

        $safetyValidation = [
            'validation_version' => '56.4',

            'safety_status' => $safetyStatus,

            'safety_passed' => $safetyPassed,

            'risk_level' => $riskLevel,

            'risk_score' => $riskScore,

            'clinical_exposure' => $clinicalExposure,

            'production_exposure' => $productionExposure,

            'reversible' => $reversible,

            'rollback_complexity' => $rollbackComplexity,

            'total_checks' => count($checks),

            'passed_checks' => $passedChecks,

            'failed_checks' => $failedChecks,

            'checks' => $checks,

            'findings' => $findings,

            'execution_authorized' => false,

            'production_execution_authorized' => false,

            'automatic_execution_authorized' => false,

            'automatic_deployment_authorized' => false,

            'human_execution_authorization_required' => true,

            'validated_at' => now()->toIso8601String(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 14. Persist
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $execution,
                $safetyValidation,
                $safetyStatus,
                $safetyPassed,
                $passedChecks,
                $failedChecks
            ) {
                $execution->update([
                    'safety_validation' => $safetyValidation,

                    'safety_status' => $safetyStatus,

                    'execution_stage' =>
                        $safetyPassed
                        ? 'SAFETY_VALIDATED'
                        : 'SAFETY_REMEDIATION_REQUIRED',

                    /*
                    |--------------------------------------------------------------------------
                    | Ready for human review ≠ approved for execution
                    |--------------------------------------------------------------------------
                    */

                    'execution_review_ready' =>
                        $safetyPassed,

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
                    'validation_completed' => true,

                    'status' =>
                        $safetyPassed
                        ? 'PRE_EXECUTION_SAFETY_VALIDATED'
                        : 'PRE_EXECUTION_SAFETY_FAILED',

                    'message' =>
                        $safetyPassed
                        ? 'Pre-execution safety validation passed. Separate human execution authorization is still required.'
                        : 'Pre-execution safety validation identified unresolved issues.',

                    'execution' => [
                        'execution_id' => $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'safety_status' =>
                            $execution->safety_status,

                        'validation_summary' => [
                            'total_checks' =>
                                $passedChecks + $failedChecks,

                            'passed_checks' =>
                                $passedChecks,

                            'failed_checks' =>
                                $failedChecks,
                        ],

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

                        'safety_validation' =>
                            $execution->safety_validation,
                    ],

                    'safety_guardrails' => [
                        'safety_validation_is_execution_approval' =>
                            false,

                        'execution_review_ready' =>
                            $safetyPassed,

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

                        'human_execution_authorization_required' =>
                            true,

                        'rollback_plan_required_before_execution' =>
                            true,

                        'post_execution_validation_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Passing pre-execution safety validation only makes the execution package eligible for separate human authorization review. It does not authorize execution.',
                    ],
                ];
            }
        );
    }
}