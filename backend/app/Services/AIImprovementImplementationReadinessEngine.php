<?php

namespace App\Services;

use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;

class AIImprovementImplementationReadinessEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.7
    | AI Improvement Implementation Readiness Assessment
    |--------------------------------------------------------------------------
    |
    | This engine evaluates readiness only.
    |
    | It does NOT:
    |
    | - approve implementation
    | - modify production systems
    | - deploy AI changes
    | - change models
    | - change thresholds
    | - change recommendation logic
    | - change workflow behavior
    | - authorize clinical action
    |
    */

    public function analyze(
        int $testId
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Controlled Test
        |--------------------------------------------------------------------------
        */

        $test =
            AIImprovementTest::find(
                $testId
            );

        if (!$test) {
            return [
                'readiness_status' =>
                    'TEST_NOT_FOUND',

                'implementation_review_ready' =>
                    false,

                'message' =>
                    'AI improvement controlled test was not found.',

                'test_id' =>
                    $testId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Governance Review
        |--------------------------------------------------------------------------
        */

        $review =
            AIImprovementReview::find(
                $test->improvement_review_id
            );

        if (!$review) {
            return [
                'readiness_status' =>
                    'REVIEW_NOT_FOUND',

                'implementation_review_ready' =>
                    false,

                'message' =>
                    'Associated AI improvement governance review was not found.',

                'test_id' =>
                    $test->id,

                'improvement_review_id' =>
                    $test->improvement_review_id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Source State
        |--------------------------------------------------------------------------
        */

        $testStatus =
            strtoupper(
                (string) (
                    $test->test_status
                    ?? ''
                )
            );

        $reviewStatus =
            strtoupper(
                (string) (
                    $review->review_status
                    ?? ''
                )
            );

        $eligibilityStatus =
            strtoupper(
                (string) (
                    $review->eligibility_status
                    ?? ''
                )
            );

        $results =
            $test->test_results
            ?? [];

        if (!is_array($results)) {
            $results = [];
        }

        $humanValidation =
            $results[
                'human_validation'
            ]
            ?? [];

        if (!is_array($humanValidation)) {
            $humanValidation = [];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Extract Test Performance
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

        $absoluteChange =
            isset(
                $results[
                    'absolute_change'
                ]
            )
            ?
            (float) $results[
                'absolute_change'
            ]
            :
            null;

        $direction =
            strtoupper(
                (string) (
                    $results[
                        'direction'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $outcomeStatus =
            strtoupper(
                (string) (
                    $results[
                        'outcome_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $safetyPassed =
            (bool) (
                $results[
                    'safety_passed'
                ]
                ?? false
            );

        $validationDecision =
            strtoupper(
                (string) (
                    $humanValidation[
                        'decision'
                    ]
                    ?? ''
                )
            );

        /*
        |--------------------------------------------------------------------------
        | 5. Readiness Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks[
            'controlled_test_validated'
        ] = [
            'passed' =>
                $testStatus ===
                    'VALIDATED',

            'value' =>
                $testStatus,

            'message' =>
                'Controlled test must have completed human validation.',
        ];

        $checks[
            'human_validation_confirmed'
        ] = [
            'passed' =>
                $validationDecision ===
                    'VALIDATE',

            'value' =>
                $validationDecision,

            'message' =>
                'Controlled test must have an explicit human VALIDATE decision.',
        ];

        $checks[
            'test_safety_passed'
        ] = [
            'passed' =>
                $safetyPassed,

            'value' =>
                $safetyPassed,

            'message' =>
                'Controlled test safety evaluation must pass.',
        ];

        $checks[
            'positive_or_stable_result'
        ] = [
            'passed' =>
                in_array(
                    $direction,
                    [
                        'IMPROVED',
                        'STABLE',
                    ],
                    true
                ),

            'value' =>
                $direction,

            'message' =>
                'Controlled test result must not show deterioration.',
        ];

        $checks[
            'acceptable_outcome_status'
        ] = [
            'passed' =>
                in_array(
                    $outcomeStatus,
                    [
                        'POSITIVE_SIGNAL',
                        'NO_MATERIAL_CHANGE',
                    ],
                    true
                ),

            'value' =>
                $outcomeStatus,

            'message' =>
                'Controlled test outcome must not indicate a negative or unsafe signal.',
        ];

        $checks[
            'original_governance_testing_approval'
        ] = [
            'passed' =>
                $reviewStatus ===
                    'APPROVED_FOR_TESTING'
                &&
                (bool) $review->
                    approved_for_testing,

            'value' => [
                'review_status' =>
                    $reviewStatus,

                'approved_for_testing' =>
                    (bool) $review->
                        approved_for_testing,
            ],

            'message' =>
                'Original governance approval for controlled testing must remain valid.',
        ];

        $checks[
            'original_learning_eligibility'
        ] = [
            'passed' =>
                $eligibilityStatus ===
                    'ELIGIBLE_FOR_HUMAN_REVIEW',

            'value' =>
                $eligibilityStatus,

            'message' =>
                'Candidate must have entered testing through the approved human-review eligibility pathway.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 6. Production Safety Controls
        |--------------------------------------------------------------------------
        */

        $productionControlsValid =
            !(
                (bool) $test->
                    production_change_allowed
            )
            &&
            !(
                (bool) $test->
                    automatic_deployment_allowed
            )
            &&
            !(
                (bool) $review->
                    automatic_change_allowed
            )
            &&
            !(
                (bool) $review->
                    approved_for_implementation
            );

        $checks[
            'production_change_isolation'
        ] = [
            'passed' =>
                $productionControlsValid,

            'value' => [
                'production_change_allowed' =>
                    (bool) $test->
                        production_change_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $test->
                        automatic_deployment_allowed,

                'automatic_change_allowed' =>
                    (bool) $review->
                        automatic_change_allowed,

                'approved_for_implementation' =>
                    (bool) $review->
                        approved_for_implementation,
            ],

            'message' =>
                'Implementation readiness analysis requires all automatic and production-change permissions to remain disabled.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 7. Human Governance Controls
        |--------------------------------------------------------------------------
        */

        $humanGovernanceValid =
            (bool) $test->
                human_validation_required
            &&
            (bool) $test->
                governance_validation_required
            &&
            (bool) $review->
                human_approval_required
            &&
            (bool) $review->
                governance_validation_required;

        $checks[
            'human_governance_controls'
        ] = [
            'passed' =>
                $humanGovernanceValid,

            'value' => [
                'test_human_validation_required' =>
                    (bool) $test->
                        human_validation_required,

                'test_governance_validation_required' =>
                    (bool) $test->
                        governance_validation_required,

                'review_human_approval_required' =>
                    (bool) $review->
                        human_approval_required,

                'review_governance_validation_required' =>
                    (bool) $review->
                        governance_validation_required,
            ],

            'message' =>
                'Human approval and governance validation must remain mandatory.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 8. Score Checks
        |--------------------------------------------------------------------------
        */

        $passedChecks = 0;
        $failedChecks = 0;

        foreach ($checks as $check) {
            if (
                (bool) (
                    $check[
                        'passed'
                    ]
                    ?? false
                )
            ) {
                $passedChecks++;
            } else {
                $failedChecks++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Implementation Review Readiness
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | This means eligible to ENTER a later implementation governance review.
        |
        | It does NOT mean implementation is approved.
        |
        */

        $implementationReviewReady =
            $failedChecks === 0;

        $readinessStatus =
            $implementationReviewReady
            ?
            'READY_FOR_IMPLEMENTATION_GOVERNANCE_REVIEW'
            :
            'NOT_READY';

        /*
        |--------------------------------------------------------------------------
        | 10. Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        if ($implementationReviewReady) {
            $findings[] =
                'Controlled test evidence has passed the current readiness checks for separate implementation governance review.';

            $findings[] =
                'Readiness does not constitute implementation approval.';
        } else {
            $findings[] =
                $failedChecks
                . ' implementation readiness check(s) remain unresolved.';
        }

        if ($direction === 'IMPROVED') {
            $findings[] =
                'Controlled testing recorded an improved performance signal.';
        }

        if ($safetyPassed) {
            $findings[] =
                'Controlled testing recorded no failed safety result.';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Return
        |--------------------------------------------------------------------------
        */

        return [

            'readiness_status' =>
                $readinessStatus,

            'implementation_review_ready' =>
                $implementationReviewReady,

            'test_id' =>
                $test->id,

            'improvement_review_id' =>
                $review->id,

            'candidate_code' =>
                $review->candidate_code,

            'candidate_category' =>
                $review->
                    candidate_category,

            'scope_type' =>
                $review->scope_type,

            'resident_id' =>
                $review->resident_id,

            'validation_summary' => [

                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            'controlled_test_context' => [

                'test_status' =>
                    $testStatus,

                'validation_decision' =>
                    $validationDecision,

                'baseline_score' =>
                    $baselineScore,

                'test_score' =>
                    $testScore,

                'absolute_change' =>
                    $absoluteChange,

                'direction' =>
                    $direction,

                'outcome_status' =>
                    $outcomeStatus,

                'safety_passed' =>
                    $safetyPassed,
            ],

            'governance_context' => [

                'review_status' =>
                    $reviewStatus,

                'eligibility_status' =>
                    $eligibilityStatus,

                'approved_for_testing' =>
                    (bool) $review->
                        approved_for_testing,

                'approved_for_implementation' =>
                    (bool) $review->
                        approved_for_implementation,

                'automatic_change_allowed' =>
                    (bool) $review->
                        automatic_change_allowed,
            ],

            'readiness_findings' =>
                $findings,

            'implementation_guardrails' => [

                'readiness_is_implementation_approval' =>
                    false,

                'readiness_is_production_authorization' =>
                    false,

                'approved_for_implementation' =>
                    false,

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

                'separate_implementation_governance_required' =>
                    true,

                'separate_human_approval_required' =>
                    true,

                'message' =>
                    'Implementation readiness means the validated controlled test may be considered by a separate human-governed implementation review process. It does not authorize deployment or production modification.',
            ],
        ];
    }
}