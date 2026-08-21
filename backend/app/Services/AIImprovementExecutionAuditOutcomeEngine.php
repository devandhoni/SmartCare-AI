<?php

namespace App\Services;

use App\Models\AIImprovementExecution;

class AIImprovementExecutionAuditOutcomeEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.9
    | Execution Audit & Outcome Capture
    |--------------------------------------------------------------------------
    |
    | This engine consolidates the Step 56 execution lifecycle into a
    | structured audit and outcome package.
    |
    | It does NOT modify execution authorization or production behavior.
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
                'audit_status' => 'EXECUTION_NOT_FOUND',
                'audit_available' => false,
                'execution_id' => $executionId,
                'message' => 'AI improvement execution record was not found.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Source Payloads
        |--------------------------------------------------------------------------
        */

        $impact = $execution->impact_analysis ?? [];
        $safety = $execution->safety_validation ?? [];
        $executionPayload = $execution->execution_payload ?? [];
        $executionResults = $execution->execution_results ?? [];
        $verification = $execution->verification_results ?? [];
        $rollbackPlan = $execution->rollback_plan ?? [];
        $rollbackResults = $execution->rollback_results ?? [];

        foreach (
            [
                'impact',
                'safety',
                'executionPayload',
                'executionResults',
                'verification',
                'rollbackPlan',
                'rollbackResults',
            ] as $variableName
        ) {
            if (!is_array($$variableName)) {
                $$variableName = [];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Completion Context
        |--------------------------------------------------------------------------
        */

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        $verifiedExecution =
            $executionStatus === 'VERIFIED'
            &&
            $executionStage === 'POST_EXECUTION_VERIFIED';

        $rollbackRequired =
            $executionStatus === 'ROLLBACK_REQUIRED'
            ||
            (bool) (
                $verification['rollback_required']
                ?? false
            );

        $rolledBack =
            $executionStatus === 'ROLLED_BACK'
            ||
            $execution->rolled_back_at !== null;

        /*
        |--------------------------------------------------------------------------
        | 4. Outcome Context
        |--------------------------------------------------------------------------
        */

        $direction = strtoupper(
            (string) (
                $executionResults['direction']
                ?? 'UNKNOWN'
            )
        );

        $outcomeStatus = strtoupper(
            (string) (
                $executionResults['outcome_status']
                ?? 'UNKNOWN'
            )
        );

        $executionSafetyPassed = (bool) (
            $executionResults['safety_passed']
            ?? false
        );

        $verificationDecision = strtoupper(
            (string) (
                $verification['decision']
                ?? 'UNAVAILABLE'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Audit Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['impact_analysis_recorded'] = [
            'passed' => !empty($impact),
            'message' => 'Execution impact analysis is recorded.',
        ];

        $checks['pre_execution_safety_recorded'] = [
            'passed' => !empty($safety),
            'message' => 'Pre-execution safety validation is recorded.',
        ];

        $checks['human_authorization_recorded'] = [
            'passed' =>
                isset(
                    $executionPayload[
                        'human_execution_authorization'
                    ]
                ),

            'message' =>
                'Human execution authorization evidence is recorded.',
        ];

        $checks['controlled_execution_results_recorded'] = [
            'passed' => !empty($executionResults),
            'message' => 'Controlled execution results are recorded.',
        ];

        $checks['post_execution_verification_recorded'] = [
            'passed' => !empty($verification),
            'message' => 'Post-execution human verification is recorded.',
        ];

        $checks['rollback_plan_recorded'] = [
            'passed' => !empty($rollbackPlan),
            'message' => 'Rollback and recovery plan is recorded.',
        ];

        $checks['production_execution_isolated'] = [
            'passed' =>
                !(bool) $execution->production_execution_allowed,

            'message' =>
                'Production execution permission remains disabled.',
        ];

        $checks['automatic_execution_isolated'] = [
            'passed' =>
                !(bool) $execution->automatic_execution_allowed
                &&
                !(bool) $execution->automatic_deployment_allowed
                &&
                !(bool) $execution->automatic_rollback_allowed,

            'message' =>
                'Automatic execution, deployment, and rollback remain disabled.',
        ];

        $checks['execution_results_no_production_change'] = [
            'passed' =>
                !(
                    (bool) (
                        $executionResults[
                            'production_change_applied'
                        ]
                        ?? false
                    )
                ),

            'message' =>
                'Controlled execution evidence records no production change.',
        ];

        $checks['verification_no_production_authorization'] = [
            'passed' =>
                !(
                    (bool) (
                        $verification[
                            'production_change_authorized'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $verification[
                            'automatic_execution_authorized'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $verification[
                            'automatic_deployment_authorized'
                        ]
                        ?? false
                    )
                ),

            'message' =>
                'Post-execution verification contains no production or automatic execution authorization.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 6. Count Audit Results
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
        | 7. Audit Status
        |--------------------------------------------------------------------------
        */

        if ($failedChecks === 0) {
            $auditStatus = 'COMPLETE';
        } else {
            $auditStatus = 'INCOMPLETE';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Outcome Classification
        |--------------------------------------------------------------------------
        */

        if ($rolledBack) {
            $finalOutcome = 'ROLLED_BACK';
        } elseif ($rollbackRequired) {
            $finalOutcome = 'ROLLBACK_REQUIRED';
        } elseif (
            $verifiedExecution
            &&
            $executionSafetyPassed
            &&
            $direction === 'IMPROVED'
        ) {
            $finalOutcome = 'VERIFIED_POSITIVE';
        } elseif (
            $verifiedExecution
            &&
            $executionSafetyPassed
            &&
            $direction === 'STABLE'
        ) {
            $finalOutcome = 'VERIFIED_STABLE';
        } elseif (
            $direction === 'DETERIORATED'
            ||
            $outcomeStatus === 'NEGATIVE_SIGNAL'
        ) {
            $finalOutcome = 'NEGATIVE_SIGNAL';
        } else {
            $finalOutcome = 'INCONCLUSIVE';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Timeline
        |--------------------------------------------------------------------------
        */

        $timeline = [
            'registered_at' =>
                $execution->registered_at,

            'authorized_at' =>
                $execution->authorized_at,

            'execution_started_at' =>
                $execution->execution_started_at,

            'execution_completed_at' =>
                $execution->execution_completed_at,

            'verified_at' =>
                $execution->verified_at,

            'rolled_back_at' =>
                $execution->rolled_back_at,
        ];

        /*
        |--------------------------------------------------------------------------
        | 10. Governance Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            'Execution audit includes '
            . count($checks)
            . ' governance integrity check(s).';

        if ($verifiedExecution) {
            $findings[] =
                'Controlled execution completed post-execution human verification.';
        }

        if ($finalOutcome === 'VERIFIED_POSITIVE') {
            $findings[] =
                'The controlled execution produced a verified positive staged signal.';
        }

        if (!empty($rollbackPlan)) {
            $findings[] =
                'A governed rollback and recovery plan is available.';
        }

        if (!$rollbackRequired && !$rolledBack) {
            $findings[] =
                'Rollback is not currently required.';
        }

        $findings[] =
            'No automatic production execution or deployment pathway is enabled.';

        /*
        |--------------------------------------------------------------------------
        | 11. Return Audit Package
        |--------------------------------------------------------------------------
        */

        return [
            'audit_status' =>
                $auditStatus,

            'audit_available' =>
                true,

            'execution_id' =>
                $execution->id,

            'candidate_code' =>
                $execution->candidate_code,

            'candidate_category' =>
                $execution->candidate_category,

            'scope_type' =>
                $execution->scope_type,

            'resident_id' =>
                $execution->resident_id,

            'execution_state' => [
                'execution_status' =>
                    $execution->execution_status,

                'execution_stage' =>
                    $execution->execution_stage,

                'safety_status' =>
                    $execution->safety_status,

                'approved_for_execution' =>
                    (bool) $execution->approved_for_execution,
            ],

            'outcome_summary' => [
                'final_outcome' =>
                    $finalOutcome,

                'direction' =>
                    $direction,

                'outcome_status' =>
                    $outcomeStatus,

                'execution_safety_passed' =>
                    $executionSafetyPassed,

                'verification_decision' =>
                    $verificationDecision,

                'controlled_execution_verified' =>
                    $verifiedExecution,

                'rollback_required' =>
                    $rollbackRequired,

                'rolled_back' =>
                    $rolledBack,
            ],

            'risk_context' => [
                'risk_level' =>
                    $impact['risk_level']
                    ?? null,

                'risk_score' =>
                    $impact['risk_score']
                    ?? null,

                'clinical_exposure' =>
                    $impact['clinical_exposure']
                    ?? null,

                'production_exposure' =>
                    $impact['production_exposure']
                    ?? null,

                'reversible' =>
                    $impact['reversible']
                    ?? null,

                'rollback_complexity' =>
                    $impact['rollback_complexity']
                    ?? null,
            ],

            'performance_context' => [
                'baseline_score' =>
                    $executionResults['baseline_score']
                    ?? null,

                'execution_score' =>
                    $executionResults['execution_score']
                    ?? null,

                'absolute_change' =>
                    $executionResults['absolute_change']
                    ?? null,

                'direction' =>
                    $direction,
            ],

            'audit_summary' => [
                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            'timeline' =>
                $timeline,

            'rollback_context' => [
                'rollback_plan_available' =>
                    !empty($rollbackPlan),

                'rollback_required' =>
                    $rollbackRequired,

                'rollback_results_available' =>
                    !empty($rollbackResults),

                'rolled_back' =>
                    $rolledBack,
            ],

            'governance_findings' =>
                $findings,

            'audit_guardrails' => [
                'audit_is_execution_authorization' =>
                    false,

                'audit_is_production_deployment' =>
                    false,

                'production_execution_allowed' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
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

                'human_governance_required' =>
                    true,

                'message' =>
                    'Step 56.9 consolidates controlled execution evidence for audit and outcome review only. It does not execute, deploy, or authorize production AI changes.',
            ],
        ];
    }
}