<?php

namespace App\Services;

use App\Models\AIImprovementReview;
use Illuminate\Support\Facades\DB;

class AIImprovementHumanReviewDecisionEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.3
    | AI Improvement Human Review Decision Engine
    |--------------------------------------------------------------------------
    |
    | Supported decisions:
    |
    | - APPROVE_FOR_TESTING
    | - DEFER
    | - REJECT
    |
    | IMPORTANT:
    |
    | APPROVE_FOR_TESTING does NOT approve implementation.
    |
    | OBSERVE_ONLY candidates are not eligible for testing approval.
    |
    */

    public function decide(
        int $reviewId,
        string $decision,
        ?int $reviewedBy = null,
        ?string $notes = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Decision
        |--------------------------------------------------------------------------
        */

        $decision =
            strtoupper(
                trim(
                    $decision
                )
            );

        $allowedDecisions = [
            'APPROVE_FOR_TESTING',
            'DEFER',
            'REJECT',
        ];

        if (
            !in_array(
                $decision,
                $allowedDecisions,
                true
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'INVALID_DECISION',
                'message' => 'Unsupported governance review decision.',
                'allowed_decisions' => $allowedDecisions,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Review
        |--------------------------------------------------------------------------
        */

        $review =
            AIImprovementReview::find(
                $reviewId
            );

        if (!$review) {
            return [
                'decision_applied' => false,
                'status' => 'REVIEW_NOT_FOUND',
                'message' => 'AI improvement review record was not found.',
                'review_id' => $reviewId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Prevent Closed Review Reprocessing
        |--------------------------------------------------------------------------
        */

        $currentReviewStatus =
            strtoupper(
                (string) (
                    $review->review_status
                    ?? 'PENDING'
                )
            );

        if (
            in_array(
                $currentReviewStatus,
                [
                    'APPROVED_FOR_TESTING',
                    'REJECTED',
                ],
                true
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'REVIEW_ALREADY_CLOSED',
                'message' => 'This governance review already has a final review decision.',
                'review_id' => $review->id,
                'current_review_status' => $review->review_status,
                'current_review_decision' => $review->review_decision,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Governance Context
        |--------------------------------------------------------------------------
        */

        $eligibilityStatus =
            strtoupper(
                (string) (
                    $review->eligibility_status
                    ?? 'OBSERVE_ONLY'
                )
            );

        $implementationReviewReady =
            (bool) (
                $review->implementation_review_ready
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
        | 5. Critical Governance Guardrails
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ($automaticChangeAllowed) {
            $criticalIssues[] =
                'Automatic change is enabled on this review record.';
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
                'decision_applied' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Governance review cannot proceed because one or more safety controls are invalid.',
                'review_id' => $review->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Testing Approval Eligibility
        |--------------------------------------------------------------------------
        |
        | Only ELIGIBLE_FOR_HUMAN_REVIEW candidates may be approved
        | for controlled testing.
        |
        */

        if (
            $decision ===
                'APPROVE_FOR_TESTING'
            &&
            (
                $eligibilityStatus !==
                    'ELIGIBLE_FOR_HUMAN_REVIEW'
                ||
                !$implementationReviewReady
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'NOT_ELIGIBLE_FOR_TESTING',
                'message' => 'This candidate is not eligible for testing approval under the current learning and safety state.',
                'review_id' => $review->id,
                'candidate_code' => $review->candidate_code,
                'eligibility_status' => $eligibilityStatus,
                'implementation_review_ready' => $implementationReviewReady,
                'approved_for_testing' => false,
                'approved_for_implementation' => false,
                'guardrail' => 'OBSERVE_ONLY or insufficient-evidence candidates cannot be approved for testing.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Apply Human Decision
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $review,
                $decision,
                $reviewedBy,
                $notes
            ) {
                $reviewStatus =
                    'UNDER_REVIEW';

                $approvedForTesting =
                    false;

                $approvedForImplementation =
                    false;

                switch ($decision) {
                    case 'APPROVE_FOR_TESTING':

                        $reviewStatus =
                            'APPROVED_FOR_TESTING';

                        $approvedForTesting =
                            true;

                        break;

                    case 'DEFER':

                        $reviewStatus =
                            'DEFERRED';

                        break;

                    case 'REJECT':

                        $reviewStatus =
                            'REJECTED';

                        break;
                }

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |--------------------------------------------------------------------------
                |
                | No decision in Step 55.3 may approve implementation.
                |
                */

                $review->update([

                    'review_status' =>
                        $reviewStatus,

                    'review_decision' =>
                        $decision,

                    'review_notes' =>
                        $notes,

                    'reviewed_by' =>
                        $reviewedBy,

                    'reviewed_at' =>
                        now(),

                    'approved_for_testing' =>
                        $approvedForTesting,

                    'approved_for_implementation' =>
                        $approvedForImplementation,

                    'automatic_change_allowed' =>
                        false,

                    'human_approval_required' =>
                        true,

                    'governance_validation_required' =>
                        true,
                ]);

                $review->refresh();

                return [

                    'decision_applied' =>
                        true,

                    'status' =>
                        'DECISION_RECORDED',

                    'message' =>
                        'Human governance review decision recorded successfully.',

                    'review' => [

                        'review_id' =>
                            $review->id,

                        'candidate_code' =>
                            $review->candidate_code,

                        'scope_type' =>
                            $review->scope_type,

                        'resident_id' =>
                            $review->resident_id,

                        'eligibility_status' =>
                            $review->eligibility_status,

                        'implementation_review_ready' =>
                            (bool) $review->implementation_review_ready,

                        'review_status' =>
                            $review->review_status,

                        'review_decision' =>
                            $review->review_decision,

                        'review_notes' =>
                            $review->review_notes,

                        'reviewed_by' =>
                            $review->reviewed_by,

                        'reviewed_at' =>
                            $review->reviewed_at,

                        'approved_for_testing' =>
                            (bool) $review->approved_for_testing,

                        'approved_for_implementation' =>
                            (bool) $review->approved_for_implementation,

                        'automatic_change_allowed' =>
                            (bool) $review->automatic_change_allowed,

                        'human_approval_required' =>
                            (bool) $review->human_approval_required,

                        'governance_validation_required' =>
                            (bool) $review->governance_validation_required,
                    ],

                    'decision_guardrails' => [

                        'testing_approval_is_implementation_approval' =>
                            false,

                        'automatic_implementation_approval' =>
                            false,

                        'automatic_change_allowed' =>
                            false,

                        'implementation_requires_separate_step' =>
                            true,

                        'human_review_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Step 55.3 records human review decisions only. Approval for testing does not authorize production implementation or automatic AI system changes.',
                    ],
                ];
            }
        );
    }
}