<?php

namespace App\Services;

use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;
use Illuminate\Support\Facades\DB;

class AIImprovementControlledTestingEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.4
    | AI Improvement Controlled Testing Engine
    |--------------------------------------------------------------------------
    |
    | This engine creates CONTROLLED testing records only.
    |
    | It does NOT:
    |
    | - change production configuration
    | - deploy AI changes
    | - modify models
    | - modify thresholds
    | - modify recommendations
    | - modify workflows
    | - execute clinical action
    |
    */

    public function createTest(
        int $reviewId,
        ?int $createdBy = null,
        ?string $objective = null,
        ?string $hypothesis = null,
        array $baselineConfiguration = [],
        array $proposedConfiguration = [],
        array $testMetrics = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Improvement Review
        |--------------------------------------------------------------------------
        */

        $review =
            AIImprovementReview::find(
                $reviewId
            );

        if (!$review) {
            return [
                'test_created' => false,
                'status' => 'REVIEW_NOT_FOUND',
                'message' => 'AI improvement review record was not found.',
                'review_id' => $reviewId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Governance State
        |--------------------------------------------------------------------------
        */

        $reviewStatus =
            strtoupper(
                (string) (
                    $review->review_status
                    ?? 'PENDING'
                )
            );

        $reviewDecision =
            strtoupper(
                (string) (
                    $review->review_decision
                    ?? ''
                )
            );

        $eligibilityStatus =
            strtoupper(
                (string) (
                    $review->eligibility_status
                    ?? 'OBSERVE_ONLY'
                )
            );

        $approvedForTesting =
            (bool) (
                $review->approved_for_testing
                ?? false
            );

        $approvedForImplementation =
            (bool) (
                $review->approved_for_implementation
                ?? false
            );

        $automaticChangeAllowed =
            (bool) (
                $review->automatic_change_allowed
                ?? false
            );

        $humanApprovalRequired =
            (bool) (
                $review->human_approval_required
                ?? true
            );

        $governanceValidationRequired =
            (bool) (
                $review->governance_validation_required
                ?? true
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Critical Safety Checks
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ($automaticChangeAllowed) {
            $criticalIssues[] =
                'Automatic change is enabled on the improvement review.';
        }

        if ($approvedForImplementation) {
            $criticalIssues[] =
                'Review is already marked approved for implementation before controlled testing validation.';
        }

        if (!$humanApprovalRequired) {
            $criticalIssues[] =
                'Human approval requirement is disabled.';
        }

        if (!$governanceValidationRequired) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'test_created' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Controlled testing cannot proceed because governance safety controls are invalid.',
                'review_id' => $review->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Testing Eligibility
        |--------------------------------------------------------------------------
        */

        $testingEligible =
            $reviewStatus ===
                'APPROVED_FOR_TESTING'
            &&
            $reviewDecision ===
                'APPROVE_FOR_TESTING'
            &&
            $approvedForTesting
            &&
            $eligibilityStatus ===
                'ELIGIBLE_FOR_HUMAN_REVIEW';

        if (!$testingEligible) {
            return [
                'test_created' => false,
                'status' => 'NOT_APPROVED_FOR_TESTING',
                'message' => 'This improvement candidate has not passed the governance requirements for controlled testing.',
                'review_id' => $review->id,
                'candidate_code' => $review->candidate_code,
                'review_status' => $reviewStatus,
                'review_decision' => $reviewDecision,
                'eligibility_status' => $eligibilityStatus,
                'approved_for_testing' => $approvedForTesting,
                'approved_for_implementation' => false,
                'guardrail' => 'Only human-reviewed candidates explicitly approved for controlled testing may enter the testing registry.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Duplicate Test Prevention
        |--------------------------------------------------------------------------
        */

        $existingTest =
            AIImprovementTest::query()
                ->where(
                    'improvement_review_id',
                    $review->id
                )
                ->first();

        if ($existingTest) {
            return [
                'test_created' => false,
                'status' => 'TEST_ALREADY_EXISTS',
                'message' => 'A controlled testing record already exists for this improvement review.',
                'review_id' => $review->id,
                'test_id' => $existingTest->id,
                'test_status' => $existingTest->test_status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Validate Proposed Configuration
        |--------------------------------------------------------------------------
        |
        | Proposed configuration is stored as testing metadata only.
        |
        | Nothing in this engine applies the configuration.
        |
        */

        $prohibitedKeys = [
            'production_change_allowed',
            'automatic_deployment_allowed',
            'automatic_clinical_action',
            'automatic_model_retraining',
            'automatic_threshold_change',
            'automatic_recommendation_change',
            'automatic_workflow_change',
        ];

        $prohibitedConfigurationKeys = [];

        foreach ($prohibitedKeys as $key) {
            if (
                array_key_exists(
                    $key,
                    $proposedConfiguration
                )
                &&
                (
                    $proposedConfiguration[
                        $key
                    ] === true
                )
            ) {
                $prohibitedConfigurationKeys[] =
                    $key;
            }
        }

        if (!empty($prohibitedConfigurationKeys)) {
            return [
                'test_created' => false,
                'status' => 'PROHIBITED_TEST_CONFIGURATION',
                'message' => 'Proposed controlled testing configuration contains prohibited automatic or production-change permissions.',
                'review_id' => $review->id,
                'prohibited_keys' => $prohibitedConfigurationKeys,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Create Controlled Test
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $review,
                $createdBy,
                $objective,
                $hypothesis,
                $baselineConfiguration,
                $proposedConfiguration,
                $testMetrics
            ) {
                $test =
                    AIImprovementTest::create([

                        'improvement_review_id' =>
                            $review->id,

                        'candidate_code' =>
                            $review->candidate_code,

                        'candidate_category' =>
                            $review->candidate_category,

                        'resident_id' =>
                            $review->resident_id,

                        'scope_type' =>
                            $review->scope_type,

                        'test_status' =>
                            'PLANNED',

                        'test_environment' =>
                            'CONTROLLED',

                        'test_objective' =>
                            $objective,

                        'test_hypothesis' =>
                            $hypothesis,

                        'baseline_configuration' =>
                            $baselineConfiguration,

                        'proposed_configuration' =>
                            $proposedConfiguration,

                        'test_metrics' =>
                            $testMetrics,

                        'test_results' =>
                            null,

                        'production_change_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'human_validation_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'created_by' =>
                            $createdBy,

                        'validated_by' =>
                            null,

                        'started_at' =>
                            null,

                        'completed_at' =>
                            null,

                        'validated_at' =>
                            null,
                    ]);

                return [

                    'test_created' =>
                        true,

                    'status' =>
                        'CONTROLLED_TEST_CREATED',

                    'message' =>
                        'Controlled AI improvement test record created successfully.',

                    'test' => [

                        'test_id' =>
                            $test->id,

                        'improvement_review_id' =>
                            $test->improvement_review_id,

                        'candidate_code' =>
                            $test->candidate_code,

                        'candidate_category' =>
                            $test->candidate_category,

                        'scope_type' =>
                            $test->scope_type,

                        'resident_id' =>
                            $test->resident_id,

                        'test_status' =>
                            $test->test_status,

                        'test_environment' =>
                            $test->test_environment,

                        'production_change_allowed' =>
                            (bool) $test->production_change_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $test->automatic_deployment_allowed,

                        'human_validation_required' =>
                            (bool) $test->human_validation_required,

                        'governance_validation_required' =>
                            (bool) $test->governance_validation_required,
                    ],

                    'testing_guardrails' => [

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

                        'automatic_recommendation_change' =>
                            false,

                        'automatic_workflow_change' =>
                            false,

                        'automatic_clinical_action' =>
                            false,

                        'test_creation_is_implementation_approval' =>
                            false,

                        'human_validation_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Controlled testing records proposed improvement experiments only. No production AI configuration, model, threshold, recommendation, workflow, or clinical action is modified.',
                    ],
                ];
            }
        );
    }
}