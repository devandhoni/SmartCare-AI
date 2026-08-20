<?php

namespace App\Services;

use App\Models\NurseTask;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AICareWorkflowApprovalService
{
    protected AICareRecommendationEngine $careRecommendationEngine;

    public function __construct(
        AICareRecommendationEngine $careRecommendationEngine
    ) {
        $this->careRecommendationEngine = $careRecommendationEngine;
    }

    /**
     * Approve an AI workflow proposal and safely create an operational task.
     */
    public function approve(
        int $residentId,
        string $proposalId,
        ?int $approvedBy = null
    ): array {
        return DB::transaction(function () use (
            $residentId,
            $proposalId,
            $approvedBy
        ) {

            /*
            |--------------------------------------------------------------------------
            | Resident validation
            |--------------------------------------------------------------------------
            */

            $resident = Resident::findOrFail($residentId);

            $residentStatus = strtoupper(
                trim((string) $resident->status)
            );

            if ($residentStatus !== 'ACTIVE') {
                throw new RuntimeException(
                    'Workflow approval blocked because resident is not currently active in care.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Regenerate current AI recommendation intelligence
            |--------------------------------------------------------------------------
            |
            | Never trust proposal data submitted by the client.
            |
            | The proposal must be regenerated from the current AI state so the
            | approval always uses the latest clinical intelligence.
            |
            */

            $careIntelligence =
                $this->careRecommendationEngine->analyze(
                    $residentId
                );

            $proposals =
                $careIntelligence['workflow_task_proposals']
                ?? [];

            if (!is_array($proposals)) {
                $proposals = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Locate requested proposal
            |--------------------------------------------------------------------------
            */

            $proposal = null;

            foreach ($proposals as $item) {
                if (
                    isset($item['proposal_id'])
                    && strtoupper((string) $item['proposal_id'])
                        === strtoupper($proposalId)
                ) {
                    $proposal = $item;
                    break;
                }
            }

            if (!$proposal) {
                throw new RuntimeException(
                    'Workflow proposal was not found or is no longer valid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Safety validation
            |--------------------------------------------------------------------------
            */

            $canCreateTask =
                (bool) ($proposal['can_create_task'] ?? false);

            $humanReviewRequired =
                (bool) ($proposal['human_review_required'] ?? true);

            $autoExecutionAllowed =
                (bool) ($proposal['auto_execution_allowed'] ?? false);

            if (!$canCreateTask) {
                throw new RuntimeException(
                    'This workflow proposal is not eligible for task creation.'
                );
            }

            if (!$humanReviewRequired) {
                throw new RuntimeException(
                    'Workflow proposal does not meet human-review requirements.'
                );
            }

            if ($autoExecutionAllowed) {
                throw new RuntimeException(
                    'Automatic AI task execution is not permitted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate prevention
            |--------------------------------------------------------------------------
            |
            | We store the AI proposal ID in the clinical_action_plan JSON.
            |
            | This prevents repeated approval calls from creating duplicate
            | operational tasks.
            |
            */

            $existingTask = NurseTask::where(
                    'resident_id',
                    $residentId
                )
                ->where('ai_generated', true)
                ->where(function ($query) use ($proposalId) {
                    $query
                        ->where(
                            'clinical_action_plan',
                            'like',
                            '%"' .
                            $proposalId .
                            '"%'
                        );
                })
                ->latest('created_on')
                ->first();

            if ($existingTask) {
                return [
                    'approval_status' =>
                        'ALREADY_APPROVED',

                    'message' =>
                        'This AI workflow proposal already has an operational task.',

                    'proposal_id' =>
                        $proposalId,

                    'task_created' =>
                        false,

                    'duplicate_prevented' =>
                        true,

                    'task' =>
                        $existingTask,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Determine operational task priority
            |--------------------------------------------------------------------------
            */

            $priority = strtoupper(
                (string) ($proposal['priority'] ?? 'NORMAL')
            );

            $priority = strtoupper(
                (string) ($proposal['priority'] ?? 'NORMAL')
            );

            /*
            |--------------------------------------------------------------------------
            | Normalize workflow priority for NurseTask database
            |--------------------------------------------------------------------------
            |
            | AI recommendation intelligence may use ROUTINE as a descriptive
            | priority, but operational NurseTask priority uses the standard
            | clinical task priority scale.
            |
            */

            if ($priority === 'ROUTINE') {
                $priority = 'NORMAL';
            }

            if (!in_array(
                $priority,
                [
                    'LOW',
                    'NORMAL',
                    'HIGH',
                    'URGENT',
                    'CRITICAL',
                ],
                true
            )) {
                $priority = 'NORMAL';
            }

            /*
            |--------------------------------------------------------------------------
            | Suggested due time
            |--------------------------------------------------------------------------
            */

            $suggestedDueMinutes =
                $proposal['suggested_due_minutes']
                ?? null;

            $scheduledTime = null;

            if (
                is_numeric($suggestedDueMinutes)
                && (int) $suggestedDueMinutes > 0
            ) {
                $scheduledTime =
                    now()->addMinutes(
                        (int) $suggestedDueMinutes
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Clinical action plan
            |--------------------------------------------------------------------------
            */

            $clinicalActionPlan = [
                'source' =>
                    'AI_CARE_RECOMMENDATION_INTELLIGENCE',

                'proposal_id' =>
                    $proposal['proposal_id'] ?? $proposalId,

                'source_recommendation_code' =>
                    $proposal['source_recommendation_code']
                    ?? null,

                'proposal_type' =>
                    $proposal['proposal_type']
                    ?? null,

                'workflow_target' =>
                    $proposal['workflow_target']
                    ?? null,

                'execution_type' =>
                    $proposal['execution_type']
                    ?? null,

                'priority_score' =>
                    $proposal['priority_score']
                    ?? 0,

                'priority_rank' =>
                    $proposal['priority_rank']
                    ?? null,

                'priority_band' =>
                    $proposal['priority_band']
                    ?? null,

                'requires_acknowledgement' =>
                    $proposal['requires_acknowledgement']
                    ?? true,

                'requires_doctor_review' =>
                    $proposal['requires_doctor_review']
                    ?? false,

                'human_review_required' =>
                    true,

                'auto_execution_allowed' =>
                    false,

                'approved_by' =>
                    $approvedBy,

                'approved_at' =>
                    now()->toISOString(),

                'supporting_evidence' =>
                    $proposal['supporting_evidence']
                    ?? [],

                'priority_reasoning' =>
                    $proposal['priority_reasoning']
                    ?? [],

                'execution_reason' =>
                    $proposal['execution_reason']
                    ?? null,

                'proposal_reason' =>
                    $proposal['proposal_reason']
                    ?? null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Create operational nurse task
            |--------------------------------------------------------------------------
            */

            $task = new NurseTask();

            $task->resident_id =
                $residentId;

            $task->source_alert_id =
                null;

            $task->ai_generated =
                true;

            $task->assigned_to =
                null;

            $task->task_name =
                $proposal['task_title']
                ?? 'AI Care Task';

            $task->description =
                $proposal['task_description']
                ?? 'AI generated clinical workflow task.';

            /*
             * If clinical_action_plan is cast as array/json in NurseTask model,
             * assign the array directly.
             *
             * Otherwise replace this with:
             *
             * json_encode($clinicalActionPlan)
             */
            $task->clinical_action_plan =
                $clinicalActionPlan;

            $task->scheduled_time =
                $scheduledTime;

            $task->status =
                'Pending';

            $task->acknowledged_by =
                null;

            $task->acknowledged_at =
                null;

            $task->priority =
                $priority;

            $task->completed_time =
                null;

            $task->save();

            /*
            |--------------------------------------------------------------------------
            | Return approval result
            |--------------------------------------------------------------------------
            */

            return [
                'approval_status' =>
                    'APPROVED',

                'message' =>
                    'AI care workflow proposal approved and operational task created successfully.',

                'proposal_id' =>
                    $proposal['proposal_id']
                    ?? $proposalId,

                'task_created' =>
                    true,

                'duplicate_prevented' =>
                    false,

                'approved_by' =>
                    $approvedBy,

                'approved_at' =>
                    now()->toISOString(),

                'task' => [
                    'id' =>
                        $task->id,

                    'resident_id' =>
                        $task->resident_id,

                    'task_name' =>
                        $task->task_name,

                    'description' =>
                        $task->description,

                    'priority' =>
                        $task->priority,

                    'status' =>
                        $task->status,

                    'scheduled_time' =>
                        $task->scheduled_time,

                    'ai_generated' =>
                        (bool) $task->ai_generated,

                    'workflow_target' =>
                        $proposal['workflow_target']
                        ?? null,

                    'execution_type' =>
                        $proposal['execution_type']
                        ?? null,

                    'requires_doctor_review' =>
                        $proposal['requires_doctor_review']
                        ?? false,
                ],
            ];
        });
    }
}