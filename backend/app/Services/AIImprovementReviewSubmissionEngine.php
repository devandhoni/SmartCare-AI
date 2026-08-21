<?php

namespace App\Services;

use App\Models\AIImprovementReview;

class AIImprovementReviewSubmissionEngine
{
    protected AIImprovementSafetyEligibilityEngine $safetyEngine;

    public function __construct(
        AIImprovementSafetyEligibilityEngine $safetyEngine
    ) {
        $this->safetyEngine =
            $safetyEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 55.2
    | AI Improvement Review Submission Engine
    |--------------------------------------------------------------------------
    */

    public function submit(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Safety-Validated Improvement Candidates
        |--------------------------------------------------------------------------
        */

        $safety =
            $this->safetyEngine->analyze(
                $residentId
            );

        $validatedCandidates =
            $safety[
                'validated_candidates'
            ]
            ?? [];

        $learningContext =
            $safety[
                'learning_context'
            ]
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | 2. Submission Containers
        |--------------------------------------------------------------------------
        */

        $submitted = [];

        $duplicates = [];

        $skipped = [];

        /*
        |--------------------------------------------------------------------------
        | 3. Process Every Candidate
        |--------------------------------------------------------------------------
        */

        foreach (
            $validatedCandidates
            as $candidate
        ) {
            if (!is_array($candidate)) {
                continue;
            }

            $candidateCode =
                strtoupper(
                    (string) (
                        $candidate[
                            'candidate_code'
                        ]
                        ?? ''
                    )
                );

            if ($candidateCode === '') {
                $skipped[] = [
                    'reason' =>
                        'MISSING_CANDIDATE_CODE',
                ];

                continue;
            }

            $eligibilityStatus =
                strtoupper(
                    (string) (
                        $candidate[
                            'eligibility_status'
                        ]
                        ?? 'OBSERVE_ONLY'
                    )
                );

            $sourceCandidate =
                $candidate[
                    'source_candidate'
                ]
                ?? [];

            /*
            |--------------------------------------------------------------------------
            | 4. Duplicate Prevention
            |--------------------------------------------------------------------------
            |
            | Only one active review record is allowed for the same candidate,
            | resident scope, and unresolved review status.
            |
            */

            $existing =
                AIImprovementReview::query()
                    ->where(
                        'candidate_code',
                        $candidateCode
                    )
                    ->where(
                        'resident_id',
                        $residentId
                    )
                    ->whereIn(
                        'review_status',
                        [
                            'PENDING',
                            'UNDER_REVIEW',
                        ]
                    )
                    ->latest('id')
                    ->first();

            if ($existing) {
                $duplicates[] = [

                    'candidate_code' =>
                        $candidateCode,

                    'review_id' =>
                        $existing->id,

                    'review_status' =>
                        $existing->review_status,

                    'message' =>
                        'An active governance review already exists for this candidate.',
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Scope
            |--------------------------------------------------------------------------
            */

            $scopeType =
                $residentId === null
                ?
                'FACILITY'
                :
                'RESIDENT';

            /*
            |--------------------------------------------------------------------------
            | 6. Determine Whether Review Submission Is Allowed
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | OBSERVE_ONLY candidates may still be stored in the governance
            | registry for transparency and future re-evaluation.
            |
            | Submission to the registry does NOT equal:
            |
            | - approval
            | - testing permission
            | - implementation permission
            |
            */

            $implementationReviewReady =
                (
                    $eligibilityStatus ===
                    'ELIGIBLE_FOR_HUMAN_REVIEW'
                );

            /*
            |--------------------------------------------------------------------------
            | 7. Create Governance Review Record
            |--------------------------------------------------------------------------
            */

            $review =
                AIImprovementReview::create([

                    'candidate_code' =>
                        $candidateCode,

                    'candidate_category' =>
                        $candidate[
                            'category'
                        ]
                        ?? null,

                    'candidate_title' =>
                        $candidate[
                            'title'
                        ]
                        ?? null,

                    'resident_id' =>
                        $residentId,

                    'scope_type' =>
                        $scopeType,

                    'learning_maturity' =>
                        $learningContext[
                            'learning_maturity'
                        ]
                        ?? null,

                    'pattern_confidence' =>
                        $learningContext[
                            'pattern_confidence'
                        ]
                        ?? null,

                    'evidence_count' =>
                        (int) (
                            $candidate[
                                'evidence_count'
                            ]
                            ?? 0
                        ),

                    'minimum_evidence_required' =>
                        (int) (
                            $candidate[
                                'minimum_evidence_required'
                            ]
                            ?? 0
                        ),

                    'safety_status' =>
                        $safety[
                            'safety_status'
                        ]
                        ?? null,

                    'eligibility_status' =>
                        $eligibilityStatus,

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

                    'approved_for_testing' =>
                        false,

                    'approved_for_implementation' =>
                        false,

                    'automatic_change_allowed' =>
                        false,

                    'human_approval_required' =>
                        true,

                    'governance_validation_required' =>
                        true,

                    'candidate_payload' =>
                        $sourceCandidate,

                    'safety_payload' => [

                        'validation_status' =>
                            $candidate[
                                'validation_status'
                            ]
                            ?? null,

                        'eligibility_status' =>
                            $eligibilityStatus,

                        'checks' =>
                            $candidate[
                                'checks'
                            ]
                            ?? [],

                        'warnings' =>
                            $candidate[
                                'warnings'
                            ]
                            ?? [],

                        'critical_issues' =>
                            $candidate[
                                'critical_issues'
                            ]
                            ?? [],

                        'source_safety_status' =>
                            $safety[
                                'safety_status'
                            ]
                            ?? null,

                        'source_overall_eligibility' =>
                            $safety[
                                'overall_eligibility'
                            ]
                            ?? null,
                    ],

                    'submitted_at' =>
                        now(),
                ]);

            $submitted[] = [

                'review_id' =>
                    $review->id,

                'candidate_code' =>
                    $candidateCode,

                'scope_type' =>
                    $scopeType,

                'resident_id' =>
                    $residentId,

                'eligibility_status' =>
                    $eligibilityStatus,

                'implementation_review_ready' =>
                    $implementationReviewReady,

                'review_status' =>
                    $review->review_status,

                'approved_for_testing' =>
                    false,

                'approved_for_implementation' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Submission Status
        |--------------------------------------------------------------------------
        */

        $submissionStatus =
            'NO_SUBMISSIONS';

        if (!empty($submitted)) {
            $submissionStatus =
                'SUBMITTED';
        } elseif (!empty($duplicates)) {
            $submissionStatus =
                'NO_NEW_SUBMISSIONS';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Return Result
        |--------------------------------------------------------------------------
        */

        return [

            'submission_status' =>
                $submissionStatus,

            'resident_id' =>
                $residentId,

            'submission_summary' => [

                'validated_candidates_received' =>
                    count(
                        $validatedCandidates
                    ),

                'new_reviews_created' =>
                    count(
                        $submitted
                    ),

                'duplicate_reviews_prevented' =>
                    count(
                        $duplicates
                    ),

                'skipped_candidates' =>
                    count(
                        $skipped
                    ),
            ],

            'submitted_reviews' =>
                $submitted,

            'duplicate_reviews' =>
                $duplicates,

            'skipped_candidates' =>
                $skipped,

            'source_safety_context' => [

                'safety_status' =>
                    $safety[
                        'safety_status'
                    ]
                    ?? 'UNKNOWN',

                'overall_eligibility' =>
                    $safety[
                        'overall_eligibility'
                    ]
                    ?? 'UNKNOWN',

                'implementation_review_ready' =>
                    (bool) (
                        $safety[
                            'implementation_review_ready'
                        ]
                        ?? false
                    ),

                'learning_maturity' =>
                    $learningContext[
                        'learning_maturity'
                    ]
                    ?? 'UNKNOWN',

                'pattern_confidence' =>
                    $learningContext[
                        'pattern_confidence'
                    ]
                    ?? 'UNKNOWN',
            ],

            'submission_guardrails' => [

                'submission_is_approval' =>
                    false,

                'automatic_testing_approval' =>
                    false,

                'automatic_implementation_approval' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'observation_only_candidates_may_be_registered' =>
                    true,

                'message' =>
                    'Submission creates a governance review record only. It does not approve testing, implementation, or any automatic AI system change.',
            ],
        ];
    }
}