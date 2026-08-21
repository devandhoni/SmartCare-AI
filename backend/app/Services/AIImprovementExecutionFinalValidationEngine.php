<?php

namespace App\Services;

use App\Models\AIImprovementExecution;

class AIImprovementExecutionFinalValidationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.10
    | Final Controlled Execution Governance Validation
    |--------------------------------------------------------------------------
    |
    | Validates the complete Step 56 execution lifecycle.
    |
    | This engine is read-only.
    | It does not execute, deploy, rollback, or alter AI behavior.
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
                'validation_status' => 'EXECUTION_NOT_FOUND',
                'step_56_ready_for_closure' => false,
                'execution_id' => $executionId,
                'message' => 'AI improvement execution record was not found.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Generate Step 56.9 Audit
        |--------------------------------------------------------------------------
        */

        $auditEngine = app(
            AIImprovementExecutionAuditOutcomeEngine::class
        );

        $audit = $auditEngine->analyze(
            $execution->id
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Source Payloads
        |--------------------------------------------------------------------------
        */

        $impact =
            $execution->impact_analysis
            ?? [];

        $safety =
            $execution->safety_validation
            ?? [];

        $executionPayload =
            $execution->execution_payload
            ?? [];

        $executionResults =
            $execution->execution_results
            ?? [];

        $verification =
            $execution->verification_results
            ?? [];

        $rollbackPlan =
            $execution->rollback_plan
            ?? [];

        foreach (
            [
                'impact',
                'safety',
                'executionPayload',
                'executionResults',
                'verification',
                'rollbackPlan',
            ] as $variable
        ) {
            if (!is_array($$variable)) {
                $$variable = [];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Lifecycle Context
        |--------------------------------------------------------------------------
        */

        $executionStatus = strtoupper(
            (string) (
                $execution->execution_status
                ?? ''
            )
        );

        $executionStage = strtoupper(
            (string) (
                $execution->execution_stage
                ?? ''
            )
        );

        $verificationDecision = strtoupper(
            (string) (
                $verification['decision']
                ?? ''
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['execution_registry_available'] = [
            'passed' =>
                $execution->id !== null,

            'message' =>
                'AI improvement execution registry record is available.',
        ];

        $checks['approved_change_specification_available'] = [
            'passed' =>
                !empty($execution->change_type)
                &&
                !empty($execution->change_summary)
                &&
                !empty($execution->baseline_configuration)
                &&
                !empty($execution->proposed_configuration),

            'message' =>
                'Approved change specification contains baseline and proposed configuration.',
        ];

        $checks['impact_analysis_completed'] = [
            'passed' =>
                !empty($impact)
                &&
                isset($impact['risk_level'])
                &&
                isset($impact['risk_score']),

            'risk_level' =>
                $impact['risk_level']
                ?? null,

            'risk_score' =>
                $impact['risk_score']
                ?? null,

            'message' =>
                'Execution scope and impact analysis is available.',
        ];

        $checks['pre_execution_safety_passed'] = [
            'passed' =>
                !empty($safety)
                &&
                (bool) (
                    $safety['safety_passed']
                    ?? false
                )
                &&
                strtoupper(
                    (string) (
                        $safety['safety_status']
                        ?? ''
                    )
                )
                    ===
                    'SAFE_FOR_HUMAN_EXECUTION_REVIEW',

            'safety_status' =>
                $execution->safety_status,

            'message' =>
                'Pre-execution safety validation passed.',
        ];

        $authorization =
            $executionPayload[
                'human_execution_authorization'
            ]
            ?? [];

        if (!is_array($authorization)) {
            $authorization = [];
        }

        $checks['human_execution_authorization'] = [
            'passed' =>
                (bool) (
                    $execution->approved_for_execution
                    ?? false
                )
                &&
                strtoupper(
                    (string) (
                        $authorization['decision']
                        ?? ''
                    )
                ) === 'AUTHORIZE',

            'authorized_by' =>
                $execution->authorized_by,

            'authorized_at' =>
                $execution->authorized_at,

            'message' =>
                'Separate human execution authorization was recorded.',
        ];

        $checks['controlled_execution_completed'] = [
            'passed' =>
                !empty($executionResults)
                &&
                $execution->execution_started_at !== null
                &&
                $execution->execution_completed_at !== null,

            'direction' =>
                $executionResults['direction']
                ?? null,

            'outcome_status' =>
                $executionResults['outcome_status']
                ?? null,

            'message' =>
                'Controlled staged execution completed and recorded results.',
        ];

        $checks['controlled_execution_safety'] = [
            'passed' =>
                (bool) (
                    $executionResults['safety_passed']
                    ?? false
                )
                &&
                !(
                    (bool) (
                        $executionResults[
                            'production_change_applied'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $executionResults[
                            'automatic_execution_used'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $executionResults[
                            'automatic_deployment_used'
                        ]
                        ?? false
                    )
                ),

            'message' =>
                'Controlled execution passed safety checks without production or automatic execution.',
        ];

        $checks['post_execution_human_verification'] = [
            'passed' =>
                $executionStatus === 'VERIFIED'
                &&
                $executionStage === 'POST_EXECUTION_VERIFIED'
                &&
                $verificationDecision === 'VERIFY'
                &&
                (bool) (
                    $verification[
                        'controlled_execution_verified'
                    ]
                    ?? false
                ),

            'verification_decision' =>
                $verificationDecision,

            'message' =>
                'Controlled execution completed explicit post-execution human verification.',
        ];

        $checks['rollback_recovery_governance'] = [
            'passed' =>
                !empty($rollbackPlan)
                &&
                strtoupper(
                    (string) (
                        $rollbackPlan['plan_status']
                        ?? ''
                    )
                ) === 'READY'
                &&
                (bool) (
                    $rollbackPlan[
                        'human_rollback_authorization_required'
                    ]
                    ?? false
                )
                &&
                !(
                    (bool) (
                        $rollbackPlan[
                            'automatic_rollback_authorized'
                        ]
                        ?? false
                    )
                ),

            'message' =>
                'Governed rollback and recovery plan is available.',
        ];

        $checks['execution_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null)
                    === 'COMPLETE'
                &&
                (bool) (
                    $audit['audit_available']
                    ?? false
                )
                &&
                (
                    $audit[
                        'audit_summary'
                    ]['failed_checks']
                    ?? 1
                ) === 0,

            'audit_status' =>
                $audit['audit_status']
                ?? null,

            'final_outcome' =>
                $audit[
                    'outcome_summary'
                ]['final_outcome']
                ?? null,

            'message' =>
                'Step 56 execution audit and outcome capture is complete.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Production / Automatic Change Isolation
        |--------------------------------------------------------------------------
        */

        $checks['production_change_isolation'] = [
            'passed' =>
                !(bool) $execution->production_execution_allowed
                &&
                !(bool) $execution->automatic_execution_allowed
                &&
                !(bool) $execution->automatic_deployment_allowed
                &&
                !(bool) $execution->automatic_rollback_allowed,

            'message' =>
                'Production execution, automatic execution, deployment, and rollback remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Human Governance Continuity
        |--------------------------------------------------------------------------
        */

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $execution->human_execution_required
                &&
                (bool) $execution->pre_execution_validation_required
                &&
                (bool) $execution->post_execution_validation_required
                &&
                (bool) $execution->rollback_plan_required
                &&
                (bool) $execution->governance_validation_required,

            'message' =>
                'Human supervision, safety validation, rollback planning, and governance controls remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 6. Count Results
        |--------------------------------------------------------------------------
        */

        $passedChecks = 0;
        $failedChecks = 0;

        $criticalIssues = [];

        foreach (
            $checks
            as $code => $check
        ) {
            if (
                (bool) (
                    $check['passed']
                    ?? false
                )
            ) {
                $passedChecks++;
            } else {
                $failedChecks++;

                $criticalIssues[] =
                    $code
                    . ': '
                    . (
                        $check['message']
                        ?? 'Validation failed.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Closure Status
        |--------------------------------------------------------------------------
        */

        $stepReadyForClosure =
            $failedChecks === 0;

        $validationStatus =
            $stepReadyForClosure
                ? 'PASSED'
                : 'FAILED';

        /*
        |--------------------------------------------------------------------------
        | 8. Governance Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        if ($stepReadyForClosure) {
            $findings[] =
                'The complete Step 56 controlled execution lifecycle passed final governance validation.';

            $findings[] =
                'Approved change registration, impact analysis, safety validation, human authorization, controlled execution, verification, rollback readiness, and audit layers are connected.';
        }

        $finalOutcome =
            $audit[
                'outcome_summary'
            ]['final_outcome']
            ?? 'UNKNOWN';

        $findings[] =
            'Final controlled execution outcome is '
            . $finalOutcome
            . '.';

        $findings[] =
            'No production AI configuration change was automatically applied.';

        $findings[] =
            'No autonomous AI execution, deployment, rollback, or clinical action pathway is enabled.';

        $findings[] =
            'Human governance remains mandatory for future execution and production-change decisions.';

        /*
        |--------------------------------------------------------------------------
        | 9. Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '56.1_execution_registry' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '56.2_change_specification_registration' => [
                'status' =>
                    !empty($execution->change_type)
                        ? 'OPERATIONAL'
                        : 'INCOMPLETE',
            ],

            '56.3_scope_impact_analysis' => [
                'status' =>
                    !empty($impact)
                        ? 'OPERATIONAL'
                        : 'INCOMPLETE',

                'risk_level' =>
                    $impact['risk_level']
                    ?? null,

                'risk_score' =>
                    $impact['risk_score']
                    ?? null,
            ],

            '56.4_pre_execution_safety' => [
                'status' =>
                    $execution->safety_status,
            ],

            '56.5_human_execution_authorization' => [
                'status' =>
                    (bool) $execution->approved_for_execution
                        ? 'AUTHORIZED'
                        : 'NOT_AUTHORIZED',

                'authorized_by' =>
                    $execution->authorized_by,
            ],

            '56.6_controlled_execution' => [
                'status' =>
                    !empty($executionResults)
                        ? 'COMPLETED'
                        : 'INCOMPLETE',

                'outcome_status' =>
                    $executionResults[
                        'outcome_status'
                    ]
                    ?? null,
            ],

            '56.7_post_execution_verification' => [
                'status' =>
                    $verificationDecision
                    ?: 'UNAVAILABLE',
            ],

            '56.8_rollback_recovery_governance' => [
                'status' =>
                    !empty($rollbackPlan)
                        ? 'READY'
                        : 'INCOMPLETE',

                'rollback_required' =>
                    $verification[
                        'rollback_required'
                    ]
                    ?? false,
            ],

            '56.9_execution_audit_outcome' => [
                'status' =>
                    $audit['audit_status']
                    ?? 'UNAVAILABLE',

                'final_outcome' =>
                    $finalOutcome,
            ],

            '56.10_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_56_ready_for_closure' =>
                    $stepReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | 10. Final Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_status' =>
                $validationStatus,

            'step_56_ready_for_closure' =>
                $stepReadyForClosure,

            'execution_control_mode' =>
                'HUMAN_GOVERNED_CONTROLLED_EXECUTION',

            'execution_id' =>
                $execution->id,

            'candidate_code' =>
                $execution->candidate_code,

            'completion_message' =>
                $stepReadyForClosure
                    ? 'Step 56 AI Improvement Controlled Execution has passed final validation and is ready for closure.'
                    : 'Step 56 AI Improvement Controlled Execution has unresolved governance validation issues.',

            'validation_summary' => [
                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'critical_issue_count' =>
                    count($criticalIssues),
            ],

            'checks' =>
                $checks,

            'execution_context' => [
                'execution_status' =>
                    $execution->execution_status,

                'execution_stage' =>
                    $execution->execution_stage,

                'safety_status' =>
                    $execution->safety_status,

                'approved_for_execution' =>
                    (bool) $execution->approved_for_execution,

                'final_outcome' =>
                    $finalOutcome,

                'risk_level' =>
                    $impact['risk_level']
                    ?? null,

                'risk_score' =>
                    $impact['risk_score']
                    ?? null,

                'rollback_plan_available' =>
                    !empty($rollbackPlan),

                'rollback_required' =>
                    (bool) (
                        $verification[
                            'rollback_required'
                        ]
                        ?? false
                    ),
            ],

            'architecture_summary' =>
                $architectureSummary,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $findings,

            'step_56_guardrails' => [
                'controlled_execution_foundation_enabled' =>
                    true,

                'human_execution_authorization_required' =>
                    true,

                'impact_analysis_required' =>
                    true,

                'pre_execution_safety_required' =>
                    true,

                'controlled_staged_execution_required' =>
                    true,

                'post_execution_human_verification_required' =>
                    true,

                'rollback_recovery_plan_required' =>
                    true,

                'execution_audit_required' =>
                    true,

                'production_execution_enabled' =>
                    false,

                'automatic_execution_enabled' =>
                    false,

                'automatic_deployment_enabled' =>
                    false,

                'automatic_rollback_enabled' =>
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

                'human_supervision_required' =>
                    true,

                'message' =>
                    'Step 56 establishes a human-governed controlled AI improvement execution lifecycle. Controlled execution evidence does not enable autonomous production deployment or automatic AI or clinical behavior changes.',
            ],
        ];
    }
}