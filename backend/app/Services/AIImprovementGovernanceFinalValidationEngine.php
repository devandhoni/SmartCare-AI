<?php

namespace App\Services;

use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;

class AIImprovementGovernanceFinalValidationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.10
    | AI Improvement Governance Final Validation
    |--------------------------------------------------------------------------
    |
    | This engine validates the complete Step 55 governance architecture.
    |
    | It does not execute or deploy any AI improvement.
    |
    */

    public function analyze(
        int $implementationReviewId
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Implementation Review
        |--------------------------------------------------------------------------
        */

        $implementationReview =
            AIImprovementImplementationReview::find(
                $implementationReviewId
            );

        if (!$implementationReview) {
            return [
                'validation_status' =>
                    'FAILED',

                'step_55_ready_for_closure' =>
                    false,

                'message' =>
                    'Implementation governance review was not found.',

                'implementation_review_id' =>
                    $implementationReviewId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Source Review
        |--------------------------------------------------------------------------
        */

        $improvementReview =
            AIImprovementReview::find(
                $implementationReview->
                    improvement_review_id
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Load Controlled Test
        |--------------------------------------------------------------------------
        */

        $improvementTest =
            AIImprovementTest::find(
                $implementationReview->
                    improvement_test_id
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        /*
        |--------------------------------------------------------------------------
        | Source Review Exists
        |--------------------------------------------------------------------------
        */

        $checks[
            'source_improvement_review_available'
        ] = [
            'passed' =>
                $improvementReview !== null,

            'message' =>
                'Original AI improvement governance review is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Controlled Test Exists
        |--------------------------------------------------------------------------
        */

        $checks[
            'controlled_test_available'
        ] = [
            'passed' =>
                $improvementTest !== null,

            'message' =>
                'Controlled AI improvement test is available.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Stop if dependencies are missing
        |--------------------------------------------------------------------------
        */

        if (
            !$improvementReview
            ||
            !$improvementTest
        ) {
            $failedChecks = 0;

            foreach ($checks as $check) {
                if (
                    !(
                        $check['passed']
                        ?? false
                    )
                ) {
                    $failedChecks++;
                }
            }

            return [
                'validation_status' =>
                    'FAILED',

                'step_55_ready_for_closure' =>
                    false,

                'validation_summary' => [
                    'total_checks' =>
                        count($checks),

                    'passed_checks' =>
                        count($checks)
                        -
                        $failedChecks,

                    'failed_checks' =>
                        $failedChecks,
                ],

                'checks' =>
                    $checks,

                'critical_issues' => [
                    'Required Step 55 governance records are incomplete.',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Source Testing Approval
        |--------------------------------------------------------------------------
        */

        $checks[
            'testing_approval_valid'
        ] = [
            'passed' =>
                strtoupper(
                    (string) (
                        $improvementReview->
                            review_status
                        ?? ''
                    )
                )
                ===
                'APPROVED_FOR_TESTING'
                &&
                (bool) $improvementReview->
                    approved_for_testing,

            'message' =>
                'Original governance review contains valid testing approval.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 6. Original Review Not Reused for Implementation Approval
        |--------------------------------------------------------------------------
        */

        $checks[
            'testing_and_implementation_approval_separated'
        ] = [
            'passed' =>
                !(
                    (bool) $improvementReview->
                        approved_for_implementation
                ),

            'message' =>
                'Original testing approval remains isolated from implementation approval.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 7. Controlled Test Validation
        |--------------------------------------------------------------------------
        */

        $checks[
            'controlled_test_validated'
        ] = [
            'passed' =>
                strtoupper(
                    (string) (
                        $improvementTest->
                            test_status
                        ?? ''
                    )
                )
                ===
                'VALIDATED',

            'message' =>
                'Controlled test completed human validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 8. Test Result Safety
        |--------------------------------------------------------------------------
        */

        $testResults =
            $improvementTest->
                test_results
            ?? [];

        if (!is_array($testResults)) {
            $testResults = [];
        }

        $checks[
            'controlled_test_safety_passed'
        ] = [
            'passed' =>
                (bool) (
                    $testResults[
                        'safety_passed'
                    ]
                    ?? false
                ),

            'message' =>
                'Controlled test recorded a passed safety result.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 9. Human Test Validation
        |--------------------------------------------------------------------------
        */

        $humanValidation =
            $testResults[
                'human_validation'
            ]
            ?? [];

        if (!is_array($humanValidation)) {
            $humanValidation = [];
        }

        $checks[
            'controlled_test_human_validation'
        ] = [
            'passed' =>
                strtoupper(
                    (string) (
                        $humanValidation[
                            'decision'
                        ]
                        ?? ''
                    )
                )
                ===
                'VALIDATE',

            'message' =>
                'Controlled test contains explicit human validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 10. Implementation Readiness
        |--------------------------------------------------------------------------
        */

        $checks[
            'implementation_readiness_confirmed'
        ] = [
            'passed' =>
                strtoupper(
                    (string) (
                        $implementationReview->
                            readiness_status
                        ?? ''
                    )
                )
                ===
                'READY_FOR_IMPLEMENTATION_GOVERNANCE_REVIEW'
                &&
                (bool) $implementationReview->
                    implementation_review_ready,

            'message' =>
                'Implementation readiness was confirmed before governance approval.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 11. Separate Human Implementation Approval
        |--------------------------------------------------------------------------
        */

        $checks[
            'human_implementation_approval'
        ] = [
            'passed' =>
                strtoupper(
                    (string) (
                        $implementationReview->
                            review_status
                        ?? ''
                    )
                )
                ===
                'APPROVED'
                &&
                strtoupper(
                    (string) (
                        $implementationReview->
                            review_decision
                        ?? ''
                    )
                )
                ===
                'APPROVE_FOR_IMPLEMENTATION'
                &&
                (bool) $implementationReview->
                    approved_for_implementation,

            'message' =>
                'Separate human implementation governance approval was recorded.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 12. Production Change Isolation
        |--------------------------------------------------------------------------
        */

        $productionIsolationValid =
            !(
                (bool) $implementationReview->
                    production_change_allowed
            )
            &&
            !(
                (bool) $implementationReview->
                    automatic_deployment_allowed
            )
            &&
            !(
                (bool) $implementationReview->
                    automatic_change_allowed
            )
            &&
            !(
                (bool) $improvementTest->
                    production_change_allowed
            )
            &&
            !(
                (bool) $improvementTest->
                    automatic_deployment_allowed
            )
            &&
            !(
                (bool) $improvementReview->
                    automatic_change_allowed
            );

        $checks[
            'production_change_isolation'
        ] = [
            'passed' =>
                $productionIsolationValid,

            'message' =>
                'Production and automatic-change permissions remain disabled throughout the governance chain.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 13. Human Governance Requirements
        |--------------------------------------------------------------------------
        */

        $humanGovernanceValid =
            (bool) $implementationReview->
                human_approval_required
            &&
            (bool) $implementationReview->
                governance_validation_required
            &&
            (bool) $improvementReview->
                human_approval_required
            &&
            (bool) $improvementReview->
                governance_validation_required
            &&
            (bool) $improvementTest->
                human_validation_required
            &&
            (bool) $improvementTest->
                governance_validation_required;

        $checks[
            'human_governance_requirements'
        ] = [
            'passed' =>
                $humanGovernanceValid,

            'message' =>
                'Human review and governance validation remain mandatory throughout the improvement lifecycle.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 14. No Deployment Authorization in Decision Payload
        |--------------------------------------------------------------------------
        */

        $decisionPayload =
            $implementationReview->
                decision_payload
            ?? [];

        if (!is_array($decisionPayload)) {
            $decisionPayload = [];
        }

        $checks[
            'decision_payload_deployment_isolation'
        ] = [
            'passed' =>
                !(
                    (bool) (
                        $decisionPayload[
                            'production_change_authorized'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $decisionPayload[
                            'automatic_deployment_authorized'
                        ]
                        ?? false
                    )
                )
                &&
                !(
                    (bool) (
                        $decisionPayload[
                            'automatic_change_authorized'
                        ]
                        ?? false
                    )
                ),

            'message' =>
                'Implementation governance decision does not contain deployment authorization.',
        ];

        /*
        |--------------------------------------------------------------------------
        | 15. Count Results
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
        | 16. Final Validation
        |--------------------------------------------------------------------------
        */

        $step55ReadyForClosure =
            $failedChecks === 0;

        $validationStatus =
            $step55ReadyForClosure
            ?
            'PASSED'
            :
            'FAILED';

        /*
        |--------------------------------------------------------------------------
        | 17. Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach (
            $checks
            as $code => $check
        ) {
            if (
                !(
                    $check[
                        'passed'
                    ]
                    ?? false
                )
            ) {
                $criticalIssues[] =
                    $code
                    . ': '
                    . (
                        $check[
                            'message'
                        ]
                        ?? 'Validation failed.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 18. Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [];

        if ($step55ReadyForClosure) {
            $governanceFindings[] =
                'The complete AI improvement governance lifecycle has passed final validation.';

            $governanceFindings[] =
                'Testing approval, controlled experimentation, human test validation, implementation readiness, and implementation approval remain separated governance stages.';

            $governanceFindings[] =
                'Human implementation approval exists without production deployment authority.';

            $governanceFindings[] =
                'No autonomous AI system modification pathway is enabled.';
        }

        /*
        |--------------------------------------------------------------------------
        | 19. Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [

            '55.1_improvement_review_registry' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '55.2_review_submission' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '55.3_human_review_decision' => [
                'status' =>
                    'OPERATIONAL',
            ],

            '55.4_controlled_testing' => [
                'status' =>
                    'OPERATIONAL',

                'test_id' =>
                    $improvementTest->id,
            ],

            '55.5_test_execution_results' => [
                'status' =>
                    'OPERATIONAL',

                'test_status' =>
                    $improvementTest->
                        test_status,
            ],

            '55.6_test_human_validation' => [
                'status' =>
                    strtoupper(
                        (string) (
                            $humanValidation[
                                'decision'
                            ]
                            ?? 'UNKNOWN'
                        )
                    ),
            ],

            '55.7_implementation_readiness' => [
                'status' =>
                    $implementationReview->
                        readiness_status,
            ],

            '55.8_implementation_review_registry' => [
                'status' =>
                    'OPERATIONAL',

                'implementation_review_id' =>
                    $implementationReview->
                        id,
            ],

            '55.9_implementation_human_decision' => [
                'status' =>
                    $implementationReview->
                        review_status,

                'decision' =>
                    $implementationReview->
                        review_decision,
            ],

            '55.10_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_55_ready_for_closure' =>
                    $step55ReadyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | 20. Return
        |--------------------------------------------------------------------------
        */

        return [

            'validation_status' =>
                $validationStatus,

            'step_55_ready_for_closure' =>
                $step55ReadyForClosure,

            'governance_mode' =>
                'HUMAN_GOVERNED_CONTROLLED_IMPROVEMENT',

            'implementation_review_id' =>
                $implementationReview->id,

            'candidate_code' =>
                $implementationReview->
                    candidate_code,

            'completion_message' =>
                $step55ReadyForClosure
                ?
                'Step 55 AI Improvement Governance has passed final validation and is ready for closure.'
                :
                'Step 55 AI Improvement Governance has unresolved validation issues.',

            'validation_summary' => [

                'total_checks' =>
                    count($checks),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'critical_issue_count' =>
                    count(
                        $criticalIssues
                    ),
            ],

            'checks' =>
                $checks,

            'governance_context' => [

                'testing_review_id' =>
                    $improvementReview->id,

                'controlled_test_id' =>
                    $improvementTest->id,

                'implementation_review_id' =>
                    $implementationReview->id,

                'testing_approved' =>
                    (bool) $improvementReview->
                        approved_for_testing,

                'controlled_test_validated' =>
                    strtoupper(
                        (string) $improvementTest->
                            test_status
                    )
                    ===
                    'VALIDATED',

                'implementation_review_ready' =>
                    (bool) $implementationReview->
                        implementation_review_ready,

                'approved_for_implementation' =>
                    (bool) $implementationReview->
                        approved_for_implementation,

                'production_change_allowed' =>
                    (bool) $implementationReview->
                        production_change_allowed,

                'automatic_deployment_allowed' =>
                    (bool) $implementationReview->
                        automatic_deployment_allowed,

                'automatic_change_allowed' =>
                    (bool) $implementationReview->
                        automatic_change_allowed,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_55_guardrails' => [

                'controlled_improvement_governance_enabled' =>
                    true,

                'human_testing_approval_required' =>
                    true,

                'controlled_testing_required' =>
                    true,

                'human_test_validation_required' =>
                    true,

                'implementation_readiness_assessment_required' =>
                    true,

                'separate_human_implementation_approval_required' =>
                    true,

                'implementation_approval_is_deployment' =>
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

                'automatic_deployment' =>
                    false,

                'production_change_execution_enabled' =>
                    false,

                'human_supervision_required' =>
                    true,

                'message' =>
                    'Step 55 provides a human-governed controlled AI improvement lifecycle. Approval for implementation does not itself execute or deploy any production AI system change.',
            ],
        ];
    }
}