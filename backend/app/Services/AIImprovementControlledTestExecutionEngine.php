<?php

namespace App\Services;

use App\Models\AIImprovementTest;
use Illuminate\Support\Facades\DB;

class AIImprovementControlledTestExecutionEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.5
    | Controlled Test Execution & Result Recording
    |--------------------------------------------------------------------------
    |
    | Allowed lifecycle:
    |
    | PLANNED
    |   -> RUNNING
    |   -> COMPLETED
    |   -> AWAITING_VALIDATION
    |
    | This engine records controlled test execution only.
    |
    | It never authorizes:
    |
    | - production changes
    | - automatic deployment
    | - model modification
    | - threshold modification
    | - recommendation modification
    | - workflow modification
    | - clinical action
    |
    */

    public function start(
        int $testId
    ): array {
        $test =
            AIImprovementTest::find(
                $testId
            );

        if (!$test) {
            return [
                'started' => false,
                'status' => 'TEST_NOT_FOUND',
                'message' => 'AI improvement controlled test was not found.',
                'test_id' => $testId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Guardrails
        |--------------------------------------------------------------------------
        */

        $guardrailIssue =
            $this->validateSafetyGuardrails(
                $test
            );

        if ($guardrailIssue !== null) {
            return $guardrailIssue;
        }

        /*
        |--------------------------------------------------------------------------
        | State Validation
        |--------------------------------------------------------------------------
        */

        $currentStatus =
            strtoupper(
                (string) (
                    $test->test_status
                    ?? 'PLANNED'
                )
            );

        if ($currentStatus !== 'PLANNED') {
            return [
                'started' => false,
                'status' => 'INVALID_TEST_STATE',
                'message' => 'Only PLANNED controlled tests may be started.',
                'test_id' => $test->id,
                'current_test_status' => $currentStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Start Test
        |--------------------------------------------------------------------------
        */

        $test->update([
            'test_status' =>
                'RUNNING',

            'started_at' =>
                now(),

            'production_change_allowed' =>
                false,

            'automatic_deployment_allowed' =>
                false,

            'human_validation_required' =>
                true,

            'governance_validation_required' =>
                true,
        ]);

        $test->refresh();

        return [
            'started' => true,
            'status' => 'TEST_STARTED',
            'message' => 'Controlled AI improvement test started successfully.',

            'test' => [
                'test_id' =>
                    $test->id,

                'candidate_code' =>
                    $test->candidate_code,

                'test_status' =>
                    $test->test_status,

                'test_environment' =>
                    $test->test_environment,

                'started_at' =>
                    $test->started_at,

                'production_change_allowed' =>
                    (bool) $test->production_change_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $test->automatic_deployment_allowed,

                'human_validation_required' =>
                    (bool) $test->human_validation_required,

                'governance_validation_required' =>
                    (bool) $test->governance_validation_required,
            ],

            'execution_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Controlled Test
    |--------------------------------------------------------------------------
    */

    public function complete(
        int $testId,
        array $results
    ): array {
        $test =
            AIImprovementTest::find(
                $testId
            );

        if (!$test) {
            return [
                'completed' => false,
                'status' => 'TEST_NOT_FOUND',
                'message' => 'AI improvement controlled test was not found.',
                'test_id' => $testId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Guardrails
        |--------------------------------------------------------------------------
        */

        $guardrailIssue =
            $this->validateSafetyGuardrails(
                $test
            );

        if ($guardrailIssue !== null) {
            return $guardrailIssue;
        }

        /*
        |--------------------------------------------------------------------------
        | State Validation
        |--------------------------------------------------------------------------
        */

        $currentStatus =
            strtoupper(
                (string) (
                    $test->test_status
                    ?? ''
                )
            );

        if ($currentStatus !== 'RUNNING') {
            return [
                'completed' => false,
                'status' => 'INVALID_TEST_STATE',
                'message' => 'Only RUNNING controlled tests may be completed.',
                'test_id' => $test->id,
                'current_test_status' => $currentStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Result Validation
        |--------------------------------------------------------------------------
        */

        $validatedResults =
            $this->normalizeResults(
                $results
            );

        if (
            $validatedResults[
                'valid'
            ] === false
        ) {
            return [
                'completed' => false,
                'status' => 'INVALID_TEST_RESULTS',
                'message' => 'Controlled test results are incomplete or invalid.',
                'test_id' => $test->id,
                'validation_errors' =>
                    $validatedResults[
                        'errors'
                    ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Record Result
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $test,
                $validatedResults
            ) {
                $test->update([

                    'test_status' =>
                        'AWAITING_VALIDATION',

                    'test_results' =>
                        $validatedResults[
                            'results'
                        ],

                    'completed_at' =>
                        now(),

                    'production_change_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'human_validation_required' =>
                        true,

                    'governance_validation_required' =>
                        true,
                ]);

                $test->refresh();

                return [

                    'completed' =>
                        true,

                    'status' =>
                        'TEST_RESULTS_RECORDED',

                    'message' =>
                        'Controlled AI improvement test completed and results recorded. Human validation is required before any further governance decision.',

                    'test' => [

                        'test_id' =>
                            $test->id,

                        'candidate_code' =>
                            $test->candidate_code,

                        'test_status' =>
                            $test->test_status,

                        'test_environment' =>
                            $test->test_environment,

                        'started_at' =>
                            $test->started_at,

                        'completed_at' =>
                            $test->completed_at,

                        'test_results' =>
                            $test->test_results,

                        'production_change_allowed' =>
                            (bool) $test->production_change_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $test->automatic_deployment_allowed,

                        'human_validation_required' =>
                            (bool) $test->human_validation_required,

                        'governance_validation_required' =>
                            (bool) $test->governance_validation_required,
                    ],

                    'execution_guardrails' =>
                        $this->guardrails(),
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Test Results
    |--------------------------------------------------------------------------
    */

    protected function normalizeResults(
        array $results
    ): array {
        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Required Result Fields
        |--------------------------------------------------------------------------
        */

        $baselineScore =
            isset(
                $results[
                    'baseline_score'
                ]
            )
            ?
            (float) $results[
                'baseline_score'
            ]
            :
            null;

        $testScore =
            isset(
                $results[
                    'test_score'
                ]
            )
            ?
            (float) $results[
                'test_score'
            ]
            :
            null;

        if ($baselineScore === null) {
            $errors[] =
                'baseline_score is required.';
        }

        if ($testScore === null) {
            $errors[] =
                'test_score is required.';
        }

        /*
        |--------------------------------------------------------------------------
        | Range Validation
        |--------------------------------------------------------------------------
        */

        if (
            $baselineScore !== null
            &&
            (
                $baselineScore < 0
                ||
                $baselineScore > 100
            )
        ) {
            $errors[] =
                'baseline_score must be between 0 and 100.';
        }

        if (
            $testScore !== null
            &&
            (
                $testScore < 0
                ||
                $testScore > 100
            )
        ) {
            $errors[] =
                'test_score must be between 0 and 100.';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'results' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Change
        |--------------------------------------------------------------------------
        */

        $absoluteChange =
            round(
                $testScore -
                $baselineScore,
                2
            );

        if ($absoluteChange > 0) {
            $direction =
                'IMPROVED';
        } elseif ($absoluteChange < 0) {
            $direction =
                'WORSENED';
        } else {
            $direction =
                'STABLE';
        }

        /*
        |--------------------------------------------------------------------------
        | Optional Safety Result
        |--------------------------------------------------------------------------
        */

        $safetyPassed =
            (bool) (
                $results[
                    'safety_passed'
                ]
                ?? false
            );

        $observations =
            $results[
                'observations'
            ]
            ?? [];

        if (!is_array($observations)) {
            $observations = [
                (string) $observations,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Test Outcome Classification
        |--------------------------------------------------------------------------
        */

        if (!$safetyPassed) {
            $outcomeStatus =
                'FAILED_SAFETY';
        } elseif ($absoluteChange > 0) {
            $outcomeStatus =
                'POSITIVE_SIGNAL';
        } elseif ($absoluteChange < 0) {
            $outcomeStatus =
                'NEGATIVE_SIGNAL';
        } else {
            $outcomeStatus =
                'NO_MATERIAL_CHANGE';
        }

        return [
            'valid' => true,
            'errors' => [],

            'results' => [

                'baseline_score' =>
                    $baselineScore,

                'test_score' =>
                    $testScore,

                'absolute_change' =>
                    $absoluteChange,

                'direction' =>
                    $direction,

                'safety_passed' =>
                    $safetyPassed,

                'outcome_status' =>
                    $outcomeStatus,

                'observations' =>
                    $observations,

                /*
                 * Explicitly advisory.
                 */
                'implementation_recommendation' =>
                    'REQUIRES_HUMAN_VALIDATION',

                'production_change_authorized' =>
                    false,

                'automatic_deployment_authorized' =>
                    false,

                'recorded_at' =>
                    now()->toIso8601String(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Test Safety Guardrails
    |--------------------------------------------------------------------------
    */

    protected function validateSafetyGuardrails(
        AIImprovementTest $test
    ): ?array {
        $criticalIssues = [];

        if (
            (bool) $test->
                production_change_allowed
        ) {
            $criticalIssues[] =
                'Production change permission is enabled.';
        }

        if (
            (bool) $test->
                automatic_deployment_allowed
        ) {
            $criticalIssues[] =
                'Automatic deployment permission is enabled.';
        }

        if (
            !(
                (bool) $test->
                    human_validation_required
            )
        ) {
            $criticalIssues[] =
                'Human validation requirement is disabled.';
        }

        if (
            !(
                (bool) $test->
                    governance_validation_required
            )
        ) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [

                'executed' =>
                    false,

                'status' =>
                    'GOVERNANCE_BLOCKED',

                'message' =>
                    'Controlled testing operation blocked because safety guardrails are invalid.',

                'test_id' =>
                    $test->id,

                'critical_issues' =>
                    $criticalIssues,
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Guardrail Response
    |--------------------------------------------------------------------------
    */

    protected function guardrails(): array
    {
        return [

            'controlled_environment_only' =>
                true,

            'production_change_allowed' =>
                false,

            'automatic_deployment_allowed' =>
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

            'test_results_are_implementation_approval' =>
                false,

            'human_validation_required' =>
                true,

            'governance_validation_required' =>
                true,

            'message' =>
                'Controlled test execution records experimental evidence only. Test results do not authorize production deployment or modification of AI or clinical behavior.',
        ];
    }
}