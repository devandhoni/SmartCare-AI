<?php

namespace App\Services;

use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementTest;
use Illuminate\Support\Facades\DB;

class AIImprovementImplementationReviewSubmissionEngine
{
    protected AIImprovementImplementationReadinessEngine $readinessEngine;

    public function __construct(
        AIImprovementImplementationReadinessEngine $readinessEngine
    ) {
        $this->readinessEngine = $readinessEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 55.8B
    | Implementation Governance Review Submission
    |--------------------------------------------------------------------------
    */

    public function submit(
        int $testId
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Controlled Test
        |--------------------------------------------------------------------------
        */

        $test = AIImprovementTest::find($testId);

        if (!$test) {
            return [
                'submitted' => false,
                'status' => 'TEST_NOT_FOUND',
                'message' => 'AI improvement controlled test was not found.',
                'test_id' => $testId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Run Implementation Readiness Assessment
        |--------------------------------------------------------------------------
        */

        $readiness = $this->readinessEngine->analyze($testId);

        $readinessStatus = strtoupper(
            (string) (
                $readiness['readiness_status']
                ?? 'NOT_READY'
            )
        );

        $implementationReviewReady = (bool) (
            $readiness['implementation_review_ready']
            ?? false
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Readiness Guardrail
        |--------------------------------------------------------------------------
        */

        if (
            $readinessStatus !==
                'READY_FOR_IMPLEMENTATION_GOVERNANCE_REVIEW'
            ||
            !$implementationReviewReady
        ) {
            return [
                'submitted' => false,
                'status' => 'NOT_READY_FOR_IMPLEMENTATION_REVIEW',
                'message' => 'Controlled test has not passed implementation readiness requirements.',
                'test_id' => $testId,
                'readiness_status' => $readinessStatus,
                'implementation_review_ready' => $implementationReviewReady,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Duplicate Prevention
        |--------------------------------------------------------------------------
        */

        $existing = AIImprovementImplementationReview::query()
            ->where(
                'improvement_test_id',
                $testId
            )
            ->first();

        if ($existing) {
            return [
                'submitted' => false,
                'status' => 'IMPLEMENTATION_REVIEW_ALREADY_EXISTS',
                'message' => 'An implementation governance review already exists for this controlled test.',
                'implementation_review_id' => $existing->id,
                'review_status' => $existing->review_status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Source Governance Context
        |--------------------------------------------------------------------------
        */

        $improvementReviewId = (int) (
            $readiness['improvement_review_id']
            ?? $test->improvement_review_id
        );

        $candidateCode = (string) (
            $readiness['candidate_code']
            ?? $test->candidate_code
        );

        $candidateCategory =
            $readiness['candidate_category']
            ?? $test->candidate_category;

        $scopeType = (string) (
            $readiness['scope_type']
            ?? $test->scope_type
            ?? 'FACILITY'
        );

        $residentId =
            $readiness['resident_id']
            ?? $test->resident_id;

        /*
        |--------------------------------------------------------------------------
        | 6. Create Separate Implementation Review
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $test,
                $readiness,
                $readinessStatus,
                $implementationReviewReady,
                $improvementReviewId,
                $candidateCode,
                $candidateCategory,
                $scopeType,
                $residentId
            ) {
                $implementationReview =
                    AIImprovementImplementationReview::create([

                        'improvement_review_id' =>
                            $improvementReviewId,

                        'improvement_test_id' =>
                            $test->id,

                        'candidate_code' =>
                            $candidateCode,

                        'candidate_category' =>
                            $candidateCategory,

                        'resident_id' =>
                            $residentId,

                        'scope_type' =>
                            $scopeType,

                        'readiness_status' =>
                            $readinessStatus,

                        'implementation_review_ready' =>
                            $implementationReviewReady,

                        'review_status' =>
                            'PENDING',

                        'review_decision' =>
                            null,

                        'review_notes' =>
                            null,

                        'reviewed_by' =>
                            null,

                        'reviewed_at' =>
                            null,

                        /*
                        |--------------------------------------------------------------------------
                        | Critical Implementation Guardrails
                        |--------------------------------------------------------------------------
                        */

                        'approved_for_implementation' =>
                            false,

                        'production_change_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_change_allowed' =>
                            false,

                        'human_approval_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'readiness_payload' =>
                            $readiness,

                        'decision_payload' =>
                            null,

                        'submitted_at' =>
                            now(),
                    ]);

                return [

                    'submitted' =>
                        true,

                    'status' =>
                        'IMPLEMENTATION_REVIEW_SUBMITTED',

                    'message' =>
                        'AI improvement candidate submitted for separate implementation governance review.',

                    'implementation_review' => [

                        'implementation_review_id' =>
                            $implementationReview->id,

                        'improvement_review_id' =>
                            $implementationReview->improvement_review_id,

                        'improvement_test_id' =>
                            $implementationReview->improvement_test_id,

                        'candidate_code' =>
                            $implementationReview->candidate_code,

                        'candidate_category' =>
                            $implementationReview->candidate_category,

                        'scope_type' =>
                            $implementationReview->scope_type,

                        'resident_id' =>
                            $implementationReview->resident_id,

                        'readiness_status' =>
                            $implementationReview->readiness_status,

                        'implementation_review_ready' =>
                            (bool) $implementationReview->implementation_review_ready,

                        'review_status' =>
                            $implementationReview->review_status,

                        'approved_for_implementation' =>
                            (bool) $implementationReview->approved_for_implementation,

                        'production_change_allowed' =>
                            (bool) $implementationReview->production_change_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $implementationReview->automatic_deployment_allowed,

                        'automatic_change_allowed' =>
                            (bool) $implementationReview->automatic_change_allowed,

                        'human_approval_required' =>
                            (bool) $implementationReview->human_approval_required,

                        'governance_validation_required' =>
                            (bool) $implementationReview->governance_validation_required,

                        'submitted_at' =>
                            $implementationReview->submitted_at,
                    ],

                    'submission_guardrails' => [

                        'submission_is_implementation_approval' =>
                            false,

                        'approved_for_implementation' =>
                            false,

                        'production_change_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_change_allowed' =>
                            false,

                        'human_approval_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'testing_approval_reused_as_implementation_approval' =>
                            false,

                        'message' =>
                            'Implementation review submission creates a separate human governance record only. It does not authorize deployment, production changes, or automatic AI modification.',
                    ],
                ];
            }
        );
    }
}