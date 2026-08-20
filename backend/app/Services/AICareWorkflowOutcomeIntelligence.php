<?php

namespace App\Services;

use App\Models\NurseTask;
use App\Models\AiClinicalOutcome;

class AICareWorkflowOutcomeIntelligence
{
    public function analyze(int $residentId): array
    {
        $tasks = NurseTask::where('resident_id', $residentId)
            ->where('ai_generated', true)
            ->orderBy('created_on', 'asc')
            ->get();

        $workflowOutcomes = [];

        $summary = [
            'total_ai_workflows' => 0,
            'completed_workflows' => 0,
            'evaluated_workflows' => 0,
            'successful_workflows' => 0,
            'partially_successful_workflows' => 0,
            'unsuccessful_workflows' => 0,
            'unknown_outcomes' => 0,
        ];

        foreach ($tasks as $task) {

            $plan = $this->decodeClinicalActionPlan(
                $task->clinical_action_plan
            );

            /*
             * Only evaluate tasks originating from
             * AI Care Recommendation Intelligence.
             */
            if (
                ($plan['source'] ?? null)
                !== 'AI_CARE_RECOMMENDATION_INTELLIGENCE'
            ) {
                continue;
            }

            $summary['total_ai_workflows']++;

            $completed = strtoupper((string) $task->status) === 'COMPLETED'
                || !empty($task->completed_time);

            if ($completed) {
                $summary['completed_workflows']++;
            }

            /*
             * Outcome must be linked directly to the
             * operational NurseTask.
             */
            $outcome = AiClinicalOutcome::where(
                    'nurse_task_id',
                    $task->id
                )
                ->orderBy('recorded_at', 'desc')
                ->first();

            $evaluation = $this->evaluateOutcome(
                $completed,
                $outcome
            );

            if ($evaluation['evaluated']) {
                $summary['evaluated_workflows']++;

                switch ($evaluation['effectiveness']) {
                    case 'SUCCESSFUL':
                        $summary['successful_workflows']++;
                        break;

                    case 'PARTIALLY_SUCCESSFUL':
                        $summary['partially_successful_workflows']++;
                        break;

                    case 'UNSUCCESSFUL':
                        $summary['unsuccessful_workflows']++;
                        break;

                    default:
                        $summary['unknown_outcomes']++;
                        break;
                }
            }

            $workflowOutcomes[] = [
                'proposal_id' =>
                    $plan['proposal_id'] ?? null,

                'source_recommendation_code' =>
                    $plan['source_recommendation_code'] ?? null,

                'task_id' => $task->id,

                'task_name' => $task->task_name,

                'task_priority' => $task->priority,

                'workflow_target' =>
                    $plan['workflow_target'] ?? null,

                'execution_type' =>
                    $plan['execution_type'] ?? null,

                'execution' => [
                    'completed' => $completed,
                    'completed_at' => $task->completed_time,
                ],

                'clinical_outcome' => $outcome
                    ? [
                        'id' => $outcome->id,

                        'status' =>
                            strtoupper(
                                (string) $outcome->outcome_status
                            ),

                        'notes' =>
                            $outcome->outcome_notes,

                        /*
                         * Use the real DB column name.
                         * Your schema uses ai_accuracy_score.
                         */
                        'ai_accuracy_score' =>
                            $outcome->ai_accuracy_score !== null
                                ? (float) $outcome->ai_accuracy_score
                                : null,

                        'recorded_at' =>
                            $outcome->recorded_at,
                    ]
                    : null,

                'outcome_intelligence' => $evaluation,

                'recommendation_context' => [
                    'priority_score' =>
                        $plan['priority_score'] ?? null,

                    'priority_rank' =>
                        $plan['priority_rank'] ?? null,

                    'priority_band' =>
                        $plan['priority_band'] ?? null,

                    'supporting_evidence' =>
                        $plan['supporting_evidence'] ?? [],

                    'priority_reasoning' =>
                        $plan['priority_reasoning'] ?? [],
                ],
            ];
        }

        $summary['workflow_success_rate'] =
            $this->calculateSuccessRate($summary);

        return [
            'resident_id' => $residentId,

            'outcome_intelligence_summary' => $summary,

            'workflow_outcomes' => $workflowOutcomes,
        ];
    }


    /**
     * Evaluate the effectiveness of a completed
     * AI-generated care workflow.
     */
    private function evaluateOutcome(
        bool $completed,
        ?AiClinicalOutcome $outcome
    ): array {

        /*
         * Workflow has not finished.
         * It cannot yet be clinically evaluated.
         */
        if (!$completed) {
            return [
                'evaluated' => false,
                'effectiveness' => 'NOT_READY',
                'effectiveness_score' => null,
                'reason' =>
                    'Workflow execution has not been completed.',
            ];
        }

        /*
         * Workflow completed but no outcome recorded.
         */
        if (!$outcome) {
            return [
                'evaluated' => false,
                'effectiveness' => 'AWAITING_OUTCOME',
                'effectiveness_score' => null,
                'reason' =>
                    'Workflow completed but clinical outcome has not yet been recorded.',
            ];
        }

        $status = strtoupper(
            trim((string) $outcome->outcome_status)
        );

        $accuracy = $outcome->ai_accuracy_score !== null
            ? (float) $outcome->ai_accuracy_score
            : null;

        /*
         * Clinical improvement is considered
         * a successful workflow outcome.
         */
        if (in_array($status, [
            'IMPROVED',
            'RESOLVED',
            'SUCCESSFUL',
        ], true)) {

            return [
                'evaluated' => true,
                'effectiveness' => 'SUCCESSFUL',
                'effectiveness_score' =>
                    $this->successfulScore($accuracy),
                'reason' =>
                    'Recorded clinical outcome indicates improvement following the AI-supported care workflow.',
            ];
        }

        /*
         * Stable condition may indicate that the
         * workflow prevented further deterioration,
         * but should not automatically be labelled
         * fully successful.
         */
        if (in_array($status, [
            'STABLE',
            'UNCHANGED',
        ], true)) {

            return [
                'evaluated' => true,
                'effectiveness' => 'PARTIALLY_SUCCESSFUL',
                'effectiveness_score' =>
                    $accuracy ?? 60,
                'reason' =>
                    'Clinical condition remained stable after workflow execution; benefit is possible but improvement was not demonstrated.',
            ];
        }

        /*
         * Deterioration after intervention.
         */
        if (in_array($status, [
            'DETERIORATED',
            'WORSENED',
            'FAILED',
            'UNSUCCESSFUL',
        ], true)) {

            return [
                'evaluated' => true,
                'effectiveness' => 'UNSUCCESSFUL',
                'effectiveness_score' =>
                    $accuracy !== null
                        ? max(0, 100 - $accuracy)
                        : 20,
                'reason' =>
                    'Recorded clinical outcome indicates deterioration or unsuccessful intervention following workflow execution.',
            ];
        }

        return [
            'evaluated' => true,
            'effectiveness' => 'UNKNOWN',
            'effectiveness_score' => $accuracy,
            'reason' =>
                'A clinical outcome exists, but its status cannot yet be classified by the workflow effectiveness rules.',
        ];
    }


    /**
     * Calculate a conservative score for successful
     * workflows.
     */
    private function successfulScore(?float $accuracy): float
    {
        if ($accuracy === null) {
            return 80;
        }

        return round(
            min(100, max(0, $accuracy)),
            2
        );
    }


    /**
     * Calculate success percentage using only
     * workflows that have actually been evaluated.
     */
    private function calculateSuccessRate(array $summary): float
    {
        $evaluated = $summary['evaluated_workflows'];

        if ($evaluated <= 0) {
            return 0;
        }

        /*
         * Partial success receives half weighting.
         */
        $weightedSuccess =
            $summary['successful_workflows']
            + (
                $summary['partially_successful_workflows']
                * 0.5
            );

        return round(
            ($weightedSuccess / $evaluated) * 100,
            2
        );
    }


    /**
     * Safely normalize clinical_action_plan.
     */
    private function decodeClinicalActionPlan($plan): array
    {
        if (is_array($plan)) {
            return $plan;
        }

        if (is_object($plan)) {
            return (array) $plan;
        }

        if (is_string($plan) && $plan !== '') {
            $decoded = json_decode($plan, true);

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
    }
}