<?php

namespace App\Services;

use App\Models\AIImprovementImplementationReview;
use Illuminate\Support\Facades\DB;

class AIImprovementImplementationDecisionEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.9
    | Human Implementation Governance Decision Engine
    |--------------------------------------------------------------------------
    |
    | Supported decisions:
    |
    | - APPROVE_FOR_IMPLEMENTATION
    | - DEFER
    | - REJECT
    |
    | IMPORTANT:
    |
    | Approval in this step records a human governance decision only.
    |
    | It does NOT:
    |
    | - deploy anything
    | - modify production configuration
    | - change AI models
    | - change thresholds
    | - alter recommendations
    | - alter workflows
    | - authorize clinical action
    |
    */

    public function decide(
        int $implementationReviewId,
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
                trim($decision)
            );

        $allowedDecisions = [
            'APPROVE_FOR_IMPLEMENTATION',
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
                'message' => 'Unsupported implementation governance decision.',
                'allowed_decisions' => $allowedDecisions,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Implementation Review
        |--------------------------------------------------------------------------
        */

        $review =
            AIImprovementImplementationReview::find(
                $implementationReviewId
            );

        if (!$review) {
            return [
                'decision_applied' => false,
                'status' => 'IMPLEMENTATION_REVIEW_NOT_FOUND',
                'message' => 'AI improvement implementation governance review was not found.',
                'implementation_review_id' => $implementationReviewId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Current State
        |--------------------------------------------------------------------------
        */

        $currentStatus =
            strtoupper(
                (string) (
                    $review->review_status
                    ?? 'PENDING'
                )
            );

        if (
            in_array(
                $currentStatus,
                [
                    'APPROVED',
                    'REJECTED',
                ],
                true
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'REVIEW_ALREADY_CLOSED',
                'message' => 'This implementation governance review already has a final decision.',
                'implementation_review_id' => $review->id,
                'current_review_status' => $currentStatus,
                'current_review_decision' => $review->review_decision,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Readiness Validation
        |--------------------------------------------------------------------------
        */

        $readinessStatus =
            strtoupper(
                (string) (
                    $review->readiness_status
                    ?? ''
                )
            );

        $implementationReviewReady =
            (bool) (
                $review->implementation_review_ready
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | 5. Governance Safety Checks
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if (
            (bool) $review->production_change_allowed
        ) {
            $criticalIssues[] =
                'Production change permission is already enabled.';
        }

        if (
            (bool) $review->automatic_deployment_allowed
        ) {
            $criticalIssues[] =
                'Automatic deployment permission is already enabled.';
        }

        if (
            (bool) $review->automatic_change_allowed
        ) {
            $criticalIssues[] =
                'Automatic AI change permission is already enabled.';
        }

        if (
            !(bool) $review->human_approval_required
        ) {
            $criticalIssues[] =
                'Human approval requirement is disabled.';
        }

        if (
            !(bool) $review->governance_validation_required
        ) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'decision_applied' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Implementation governance decision is blocked because safety controls are invalid.',
                'implementation_review_id' => $review->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Approval Eligibility
        |--------------------------------------------------------------------------
        */

        if (
            $decision ===
                'APPROVE_FOR_IMPLEMENTATION'
            &&
            (
                $readinessStatus !==
                    'READY_FOR_IMPLEMENTATION_GOVERNANCE_REVIEW'
                ||
                !$implementationReviewReady
            )
        ) {
            return [
                'decision_applied' => false,
                'status' => 'NOT_READY_FOR_IMPLEMENTATION_APPROVAL',
                'message' => 'This candidate has not passed implementation readiness requirements.',
                'implementation_review_id' => $review->id,
                'readiness_status' => $readinessStatus,
                'implementation_review_ready' => $implementationReviewReady,
                'approved_for_implementation' => false,
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
                    'PENDING';

                $approvedForImplementation =
                    false;

                switch ($decision) {
                    case 'APPROVE_FOR_IMPLEMENTATION':

                        $reviewStatus =
                            'APPROVED';

                        $approvedForImplementation =
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
                | Decision Payload
                |--------------------------------------------------------------------------
                */

                $decisionPayload = [
                    'decision' =>
                        $decision,

                    'reviewed_by' =>
                        $reviewedBy,

                    'review_notes' =>
                        $notes,

                    'reviewed_at' =>
                        now()->toIso8601String(),

                    /*
                     * Explicit isolation.
                     */
                    'human_governance_decision_recorded' =>
                        true,

                    'production_change_authorized' =>
                        false,

                    'automatic_deployment_authorized' =>
                        false,

                    'automatic_change_authorized' =>
                        false,

                    'deployment_requires_separate_execution_control' =>
                        true,
                ];

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

                    'approved_for_implementation' =>
                        $approvedForImplementation,

                    /*
                    |--------------------------------------------------------------------------
                    | Critical Safety Isolation
                    |--------------------------------------------------------------------------
                    |
                    | Even after human implementation approval:
                    |
                    | production_change_allowed stays false.
                    | automatic_deployment_allowed stays false.
                    | automatic_change_allowed stays false.
                    |
                    */

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

                    'decision_payload' =>
                        $decisionPayload,
                ]);

                $review->refresh();

                return [

                    'decision_applied' =>
                        true,

                    'status' =>
                        'IMPLEMENTATION_DECISION_RECORDED',

                    'message' =>
                        'Human implementation governance decision recorded successfully.',

                    'implementation_review' => [

                        'implementation_review_id' =>
                            $review->id,

                        'improvement_review_id' =>
                            $review->improvement_review_id,

                        'improvement_test_id' =>
                            $review->improvement_test_id,

                        'candidate_code' =>
                            $review->candidate_code,

                        'readiness_status' =>
                            $review->readiness_status,

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

                        'approved_for_implementation' =>
                            (bool) $review->approved_for_implementation,

                        'production_change_allowed' =>
                            (bool) $review->production_change_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $review->automatic_deployment_allowed,

                        'automatic_change_allowed' =>
                            (bool) $review->automatic_change_allowed,

                        'human_approval_required' =>
                            (bool) $review->human_approval_required,

                        'governance_validation_required' =>
                            (bool) $review->governance_validation_required,
                    ],

                    'implementation_guardrails' => [

                        'human_approval_is_deployment' =>
                            false,

                        'approved_for_implementation' =>
                            $approvedForImplementation,

                        'production_change_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_change_allowed' =>
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

                        'separate_execution_control_required' =>
                            true,

                        'human_supervised_execution_required' =>
                            true,

                        'message' =>
                            'Human implementation approval records governance authorization only. It does not execute, deploy, or automatically apply any AI system change.',
                    ],
                ];
            }
        );
    }
}