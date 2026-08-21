<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AIGovernanceActionResolutionEngine
{
    public function resolve(
        int $actionId,
        string $resolution,
        ?int $resolvedBy = null,
        ?string $notes = null,
        array $resolutionData = []
    ): array {
        $resolution = strtoupper(trim($resolution));

        $allowedResolutions = [
            'RESOLVE',
            'CLOSE_REJECTED',
            'KEEP_DEFERRED',
            'KEEP_OPEN_FOR_EVIDENCE',
        ];

        if (!in_array($resolution, $allowedResolutions, true)) {
            throw new InvalidArgumentException(
                'Unsupported governance action resolution.'
            );
        }

        $action = AIGovernanceAction::find($actionId);

        if (!$action) {
            return [
                'resolution_applied' => false,
                'status' => 'ACTION_NOT_FOUND',
                'message' => 'Governance action was not found.',
                'action_id' => $actionId,
            ];
        }

        if ($action->resolved_at !== null) {
            return [
                'resolution_applied' => false,
                'status' => 'ACTION_ALREADY_RESOLVED',
                'message' => 'This governance action has already been resolved.',
                'action_id' => $action->id,
                'action_status' => $action->action_status,
                'resolved_at' => $action->resolved_at,
            ];
        }

        $allowedForCurrentState = match ($action->action_status) {
            'APPROVED' => ['RESOLVE'],
            'REJECTED' => ['CLOSE_REJECTED'],
            'DEFERRED' => ['KEEP_DEFERRED'],
            'MORE_EVIDENCE_REQUIRED' => ['KEEP_OPEN_FOR_EVIDENCE'],
            default => [],
        };

        if (!in_array($resolution, $allowedForCurrentState, true)) {
            return [
                'resolution_applied' => false,
                'status' => 'INVALID_RESOLUTION_FOR_ACTION_STATE',
                'message' => 'The requested resolution is not valid for the current governance action state.',
                'action_id' => $action->id,
                'action_code' => $action->action_code,
                'action_status' => $action->action_status,
                'review_decision' => $action->review_decision,
                'requested_resolution' => $resolution,
            ];
        }

        $result = DB::transaction(function () use (
            $action,
            $resolution,
            $resolvedBy,
            $notes,
            $resolutionData
        ) {
            $now = now();

            $nextStatus = match ($resolution) {
                'RESOLVE' => 'RESOLVED',
                'CLOSE_REJECTED' => 'CLOSED_REJECTED',
                'KEEP_DEFERRED' => 'DEFERRED',
                'KEEP_OPEN_FOR_EVIDENCE' => 'MORE_EVIDENCE_REQUIRED',
            };

            $isFinalResolution = in_array(
                $resolution,
                [
                    'RESOLVE',
                    'CLOSE_REJECTED',
                ],
                true
            );

            $resolutionContext = [
                'resolution_version' => '59.6',
                'resolution' => $resolution,
                'source_action_status' => $action->action_status,
                'review_decision' => $action->review_decision,
                'resolution_notes' => $notes,
                'resolution_data' => $resolutionData,
                'final_resolution' => $isFinalResolution,
                'automatic_execution_authorized' => false,
                'automatic_change_authorized' => false,
                'automatic_deployment_authorized' => false,
                'automatic_rollback_authorized' => false,
                'automatic_clinical_action_authorized' => false,
                'resolved_at' => $isFinalResolution
                    ? $now->toIso8601String()
                    : null,
                'recorded_at' => $now->toIso8601String(),
            ];

            $action->update([
                'action_status' => $nextStatus,

                'resolved_by' => $isFinalResolution
                    ? $resolvedBy
                    : null,

                'resolved_at' => $isFinalResolution
                    ? $now
                    : null,

                'resolution_context' => $resolutionContext,

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,
            ]);

            return $action->fresh();
        });

        return [
            'resolution_applied' => true,
            'status' => 'GOVERNANCE_ACTION_RESOLUTION_RECORDED',
            'message' => 'Governance action resolution state recorded successfully.',

            'action' => [
                'action_id' => $result->id,
                'action_code' => $result->action_code,
                'review_decision' => $result->review_decision,
                'action_status' => $result->action_status,
                'eligibility_status' => $result->eligibility_status,
                'resolved_by' => $result->resolved_by,
                'resolved_at' => $result->resolved_at,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
            ],

            'resolution_guardrails' => [
                'resolution_is_execution' => false,
                'resolution_is_ai_change' => false,
                'resolution_is_deployment' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_governance_required' => true,
                'message' => 'Governance action resolution closes or maintains the governance work item only. It does not execute, deploy, rollback, modify AI behavior, or initiate clinical action.',
            ],
        ];
    }
}