<?php

namespace App\Services;

use App\Models\NurseTask;
use App\Models\AiClinicalOutcome;
use Illuminate\Support\Collection;

class AICareWorkflowTracker
{
    /**
     * Step 52.8
     *
     * Track operational execution of AI Care Recommendation workflows.
     */
    public function analyze(int $residentId): array
    {
        $tasks = NurseTask::query()
            ->where('resident_id', $residentId)
            ->where('ai_generated', true)
            ->latest('created_on')
            ->get();

        $workflowTasks = $tasks
            ->filter(function ($task) {
                $plan = $this->normalizeClinicalActionPlan(
                    $task->clinical_action_plan
                );

                return ($plan['source'] ?? null)
                    === 'AI_CARE_RECOMMENDATION_INTELLIGENCE';
            })
            ->values();

        $workflows = [];

        foreach ($workflowTasks as $task) {
            $plan = $this->normalizeClinicalActionPlan(
                $task->clinical_action_plan
            );

            $proposalId = $plan['proposal_id'] ?? null;

            if (!$proposalId) {
                continue;
            }

            $status = strtoupper(
                trim((string) ($task->status ?? 'UNKNOWN'))
            );

            $acknowledged = !empty($task->acknowledged_at);

            $completed =
                $status === 'COMPLETED'
                || !empty($task->completed_time);

            $workflowStatus = $this->determineWorkflowStatus(
                $status,
                $acknowledged,
                $completed
            );

            $outcome = $this->findRelatedOutcome(
                $residentId,
                $task
            );

            $workflows[] = [
                'proposal_id' => $proposalId,

                'source_recommendation_code' =>
                    $plan['source_recommendation_code']
                    ?? null,

                'proposal_type' =>
                    $plan['proposal_type']
                    ?? null,

                /*
                |--------------------------------------------------------------------------
                | Operational Task
                |--------------------------------------------------------------------------
                */

                'task_id' => $task->id,

                'task_name' => $task->task_name,

                'task_description' => $task->description,

                'task_priority' => $task->priority,

                'task_status' => $task->status,

                'workflow_target' =>
                    $plan['workflow_target']
                    ?? null,

                'execution_type' =>
                    $plan['execution_type']
                    ?? null,

                /*
                |--------------------------------------------------------------------------
                | AI Recommendation Context
                |--------------------------------------------------------------------------
                */

                'priority_score' =>
                    $plan['priority_score']
                    ?? null,

                'priority_rank' =>
                    $plan['priority_rank']
                    ?? null,

                'priority_band' =>
                    $plan['priority_band']
                    ?? null,

                'human_review_required' =>
                    (bool) (
                        $plan['human_review_required']
                        ?? true
                    ),

                'auto_execution_allowed' =>
                    (bool) (
                        $plan['auto_execution_allowed']
                        ?? false
                    ),

                'requires_acknowledgement' =>
                    (bool) (
                        $plan['requires_acknowledgement']
                        ?? false
                    ),

                'requires_doctor_review' =>
                    (bool) (
                        $plan['requires_doctor_review']
                        ?? false
                    ),

                /*
                |--------------------------------------------------------------------------
                | Approval Tracking
                |--------------------------------------------------------------------------
                */

                'approval' => [
                    'approved' => !empty(
                        $plan['approved_at']
                    ),

                    'approved_by' =>
                        $plan['approved_by']
                        ?? null,

                    'approved_at' =>
                        $plan['approved_at']
                        ?? null,
                ],

                /*
                |--------------------------------------------------------------------------
                | Execution Tracking
                |--------------------------------------------------------------------------
                */

                'execution' => [
                    'workflow_status' =>
                        $workflowStatus,

                    'acknowledged' =>
                        $acknowledged,

                    'acknowledged_by' =>
                        $task->acknowledged_by,

                    'acknowledged_at' =>
                        $task->acknowledged_at,

                    'completed' =>
                        $completed,

                    'completed_at' =>
                        $task->completed_time,

                    'scheduled_time' =>
                        $task->scheduled_time,

                    'assigned_to' =>
                        $task->assigned_to,
                ],

                /*
                |--------------------------------------------------------------------------
                | Outcome Linkage
                |--------------------------------------------------------------------------
                |
                | We do NOT invent an outcome.
                |
                | If an AI clinical outcome exists after this workflow task,
                | we surface it as potentially linked clinical feedback.
                |
                */

                'outcome_linkage' => [
                    'status' =>
                        $outcome
                            ? 'OUTCOME_AVAILABLE'
                            : (
                                $completed
                                    ? 'AWAITING_OUTCOME_EVALUATION'
                                    : 'NOT_READY'
                            ),

                    'outcome_link_ready' =>
                        $completed,

                    'outcome_recorded' =>
                        $outcome !== null,

                    'outcome' =>
                        $outcome
                            ? $this->formatOutcome($outcome)
                            : null,
                ],

                /*
                |--------------------------------------------------------------------------
                | Audit Context
                |--------------------------------------------------------------------------
                */

                'audit' => [
                    'task_created_at' =>
                        $task->created_on,

                    'task_updated_at' =>
                        $task->updated_on,

                    'supporting_evidence' =>
                        $plan['supporting_evidence']
                        ?? [],

                    'priority_reasoning' =>
                        $plan['priority_reasoning']
                        ?? [],

                    'execution_reason' =>
                        $plan['execution_reason']
                        ?? null,
                ],
            ];
        }

        return [
            'resident_id' => $residentId,

            'workflow_summary' =>
                $this->buildSummary($workflows),

            'workflows' => $workflows,
        ];
    }

    /**
     * Convert clinical_action_plan into a normal PHP array.
     */
    private function normalizeClinicalActionPlan(
        mixed $clinicalActionPlan
    ): array {
        if (is_array($clinicalActionPlan)) {
            return $clinicalActionPlan;
        }

        if (is_object($clinicalActionPlan)) {
            return json_decode(
                json_encode($clinicalActionPlan),
                true
            ) ?? [];
        }

        if (is_string($clinicalActionPlan)) {
            $decoded = json_decode(
                $clinicalActionPlan,
                true
            );

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
    }

    /**
     * Determine current workflow lifecycle status.
     */
    private function determineWorkflowStatus(
        string $status,
        bool $acknowledged,
        bool $completed
    ): string {
        if ($completed) {
            return 'COMPLETED';
        }

        if ($status === 'CANCELLED') {
            return 'CANCELLED';
        }

        if (
            $status === 'IN PROGRESS'
            || $status === 'IN_PROGRESS'
        ) {
            return 'IN_PROGRESS';
        }

        if ($acknowledged) {
            return 'ACKNOWLEDGED';
        }

        if ($status === 'PENDING') {
            return 'PENDING_EXECUTION';
        }

        return 'UNKNOWN';
    }

    /**
     * Find the clinical outcome linked to this AI workflow task.
     */
    private function findRelatedOutcome(
        int $residentId,
        NurseTask $task
    ): ?AiClinicalOutcome {
        /*
        |--------------------------------------------------------------------------
        | Primary linkage
        |--------------------------------------------------------------------------
        |
        | The ai_clinical_outcomes table contains nurse_task_id.
        | This is the strongest and safest relationship.
        |
        */

        $outcome = AiClinicalOutcome::query()
            ->where('resident_id', $residentId)
            ->where('nurse_task_id', $task->id)
            ->latest('recorded_at')
            ->first();

        if ($outcome) {
            return $outcome;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback linkage
        |--------------------------------------------------------------------------
        |
        | Older outcome records may not contain nurse_task_id.
        | In that case, look for an outcome recorded after the task was created.
        |
        */

        return AiClinicalOutcome::query()
            ->where('resident_id', $residentId)
            ->whereNull('nurse_task_id')
            ->where(
                'recorded_at',
                '>=',
                $task->created_on
            )
            ->orderBy('recorded_at')
            ->first();
    }

    /**
     * Format linked AI clinical outcome.
     */
    private function formatOutcome(
        AiClinicalOutcome $outcome
    ): array {
        return [
            'id' =>
                $outcome->id,

            'resident_id' =>
                $outcome->resident_id,

            'nurse_task_id' =>
                $outcome->nurse_task_id,

            'prediction_id' =>
                $outcome->prediction_id,

            'initial_risk_level' =>
                $outcome->initial_risk_level,

            'initial_confidence' =>
                $outcome->initial_confidence !== null
                    ? (float) $outcome->initial_confidence
                    : null,

            'outcome_status' =>
                $outcome->outcome_status,

            'outcome_notes' =>
                $outcome->outcome_notes,

            'ai_accuracy_score' =>
                $outcome->ai_accuracy_score !== null
                    ? (float) $outcome->ai_accuracy_score
                    : null,

            'recorded_by' =>
                $outcome->recorded_by,

            'recorded_at' =>
                $outcome->recorded_at,

            'created_at' =>
                $outcome->created_at,
        ];
    }

    /**
     * Build resident workflow summary.
     */
    private function buildSummary(
        array $workflows
    ): array {
        $total = count($workflows);

        $pending = 0;
        $acknowledged = 0;
        $inProgress = 0;
        $completed = 0;
        $awaitingOutcome = 0;
        $outcomesRecorded = 0;

        foreach ($workflows as $workflow) {
            $workflowStatus =
                $workflow['execution']['workflow_status']
                ?? 'UNKNOWN';

            switch ($workflowStatus) {
                case 'PENDING_EXECUTION':
                    $pending++;
                    break;

                case 'ACKNOWLEDGED':
                    $acknowledged++;
                    break;

                case 'IN_PROGRESS':
                    $inProgress++;
                    break;

                case 'COMPLETED':
                    $completed++;
                    break;
            }

            $outcomeStatus =
                $workflow['outcome_linkage']['status']
                ?? null;

            if (
                $outcomeStatus
                === 'AWAITING_OUTCOME_EVALUATION'
            ) {
                $awaitingOutcome++;
            }

            if (
                (
                    $workflow['outcome_linkage']
                    ['outcome_recorded']
                    ?? false
                ) === true
            ) {
                $outcomesRecorded++;
            }
        }

        return [
            'total_ai_workflows' =>
                $total,

            'pending_execution' =>
                $pending,

            'acknowledged' =>
                $acknowledged,

            'in_progress' =>
                $inProgress,

            'completed' =>
                $completed,

            'awaiting_outcome_evaluation' =>
                $awaitingOutcome,

            'outcomes_recorded' =>
                $outcomesRecorded,
        ];
    }
}