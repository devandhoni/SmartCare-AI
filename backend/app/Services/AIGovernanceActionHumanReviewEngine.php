<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIGovernanceActionReview;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AIGovernanceActionHumanReviewEngine
{
    public function decide(
        int $actionId,
        string $decision,
        ?int $reviewerId = null,
        ?string $rationale = null,
        ?string $reviewerName = null,
        ?string $reviewerRole = null,
        array $conditions = []
    ): array {
        $decision = strtoupper(trim($decision));

        $allowedDecisions = [
            'APPROVE',
            'REJECT',
            'DEFER',
            'REQUEST_MORE_EVIDENCE',
        ];

        if (!in_array($decision, $allowedDecisions, true)) {
            throw new InvalidArgumentException(
                'Unsupported governance action review decision.'
            );
        }

        $action = AIGovernanceAction::find($actionId);

        if (!$action) {
            return [
                'decision_applied' => false,
                'status' => 'ACTION_NOT_FOUND',
                'message' => 'Governance action was not found.',
                'action_id' => $actionId,
            ];
        }

        if (
            $action->eligibility_status !== 'ELIGIBLE_FOR_HUMAN_REVIEW'
            ||
            $action->action_status !== 'PENDING_REVIEW'
        ) {
            return [
                'decision_applied' => false,
                'status' => 'ACTION_NOT_ELIGIBLE_FOR_REVIEW',
                'message' => 'This governance action is not currently eligible for a human review decision.',
                'action_id' => $action->id,
                'action_code' => $action->action_code,
                'action_status' => $action->action_status,
                'eligibility_status' => $action->eligibility_status,
            ];
        }

        $existingReview = AIGovernanceActionReview::query()
            ->where('governance_action_id', $action->id)
            ->where('review_status', 'COMPLETED')
            ->latest('id')
            ->first();

        if ($existingReview) {
            return [
                'decision_applied' => false,
                'status' => 'ACTION_REVIEW_ALREADY_COMPLETED',
                'message' => 'This governance action already has a completed human review decision.',
                'action_id' => $action->id,
                'review_id' => $existingReview->id,
                'current_decision' => $existingReview->review_decision,
            ];
        }

        $result = DB::transaction(function () use (
            $action,
            $decision,
            $reviewerId,
            $rationale,
            $reviewerName,
            $reviewerRole,
            $conditions
        ) {
            $reviewedAt = now();

            $nextActionStatus = match ($decision) {
                'APPROVE' => 'APPROVED',
                'REJECT' => 'REJECTED',
                'DEFER' => 'DEFERRED',
                'REQUEST_MORE_EVIDENCE' => 'MORE_EVIDENCE_REQUIRED',
            };

            $reviewContext = [
                'review_version' => '59.5',

                'source_action_status' =>
                    $action->action_status,

                'source_eligibility_status' =>
                    $action->eligibility_status,

                'priority_level' =>
                    $action->priority_level,

                'priority_score' =>
                    (int) $action->priority_score,

                'automatic_execution_authorized' =>
                    false,

                'automatic_change_authorized' =>
                    false,

                'automatic_deployment_authorized' =>
                    false,

                'automatic_rollback_authorized' =>
                    false,

                'automatic_clinical_action_authorized' =>
                    false,

                'reviewed_at' =>
                    $reviewedAt->toIso8601String(),
            ];

            $review = AIGovernanceActionReview::create([
                'governance_action_id' =>
                    $action->id,

                'lifecycle_snapshot_id' =>
                    $action->lifecycle_snapshot_id,

                'review_decision' =>
                    $decision,

                'review_status' =>
                    'COMPLETED',

                'reviewer_type' =>
                    $reviewerId !== null
                        ? 'App\\Models\\User'
                        : null,

                'reviewer_id' =>
                    $reviewerId,

                'reviewer_name' =>
                    $reviewerName,

                'reviewer_role' =>
                    $reviewerRole,

                'decision_rationale' =>
                    $rationale,

                'conditions' =>
                    $conditions,

                'review_context' =>
                    $reviewContext,

                'reviewed_at' =>
                    $reviewedAt,
            ]);

            $actionReviewContext = [
                'review_version' =>
                    '59.5',

                'review_id' =>
                    $review->id,

                'review_decision' =>
                    $decision,

                'review_status' =>
                    'COMPLETED',

                'decision_rationale' =>
                    $rationale,

                'conditions' =>
                    $conditions,

                'reviewer_id' =>
                    $reviewerId,

                'reviewer_name' =>
                    $reviewerName,

                'reviewer_role' =>
                    $reviewerRole,

                'reviewed_at' =>
                    $reviewedAt->toIso8601String(),

                'automatic_execution_authorized' =>
                    false,

                'automatic_change_authorized' =>
                    false,

                'automatic_deployment_authorized' =>
                    false,

                'automatic_rollback_authorized' =>
                    false,

                'automatic_clinical_action_authorized' =>
                    false,
            ];

            $action->update([
                'action_status' =>
                    $nextActionStatus,

                'review_decision' =>
                    $decision,

                'review_notes' =>
                    $rationale,

                'reviewed_by' =>
                    $reviewerId,

                'reviewed_at' =>
                    $reviewedAt,

                'review_context' =>
                    $actionReviewContext,

                'automatic_execution_allowed' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,
            ]);

            return [
                'review' => $review,
                'action' => $action->fresh(),
            ];
        });

        $review = $result['review'];
        $updatedAction = $result['action'];

        return [
            'decision_applied' => true,

            'status' =>
                'HUMAN_GOVERNANCE_ACTION_DECISION_RECORDED',

            'message' =>
                'Human governance action review decision recorded successfully.',

            'review' => [
                'review_id' =>
                    $review->id,

                'governance_action_id' =>
                    $updatedAction->id,

                'action_code' =>
                    $updatedAction->action_code,

                'review_decision' =>
                    $review->review_decision,

                'review_status' =>
                    $review->review_status,

                'action_status' =>
                    $updatedAction->action_status,

                'eligibility_status' =>
                    $updatedAction->eligibility_status,

                'priority_level' =>
                    $updatedAction->priority_level,

                'priority_score' =>
                    (int) $updatedAction->priority_score,

                'reviewed_by' =>
                    $updatedAction->reviewed_by,

                'reviewed_at' =>
                    $updatedAction->reviewed_at,

                'approved_for_automatic_execution' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,
            ],

            'review_guardrails' => [
                'human_review_is_execution' =>
                    false,

                'human_review_is_ai_change' =>
                    false,

                'approval_is_automatic_execution_authorization' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'separate_resolution_control_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Human governance action review records a governance decision only. Approval does not automatically execute, deploy, rollback, modify AI behavior, or initiate clinical action.',
            ],
        ];
    }
}