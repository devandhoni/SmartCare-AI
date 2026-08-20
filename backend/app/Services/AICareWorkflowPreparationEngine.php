<?php

namespace App\Services;

class AICareWorkflowPreparationEngine
{
    /**
     * Build proposed human-reviewed care workflow
     * from AI care recommendation intelligence.
     */
    public function prepare(array $careRecommendationIntelligence): array
    {
        $residentId =
            $careRecommendationIntelligence['resident_id']
            ?? null;

        $residentName =
            $careRecommendationIntelligence['resident_name']
            ?? 'Unknown Resident';

        $residentStatus =
            $careRecommendationIntelligence['resident_status']
            ?? 'UNKNOWN';

        $activeCareEligible =
            (bool) (
                $careRecommendationIntelligence['active_care_eligible']
                ?? false
            );

        $recommendations =
            $careRecommendationIntelligence['recommended_actions']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Non-active resident guardrail
        |--------------------------------------------------------------------------
        */

        if (!$activeCareEligible) {
            return [
                'resident_id' => $residentId,
                'resident_name' => $residentName,
                'resident_status' => $residentStatus,

                'workflow_status' =>
                    'NOT_ELIGIBLE_FOR_ACTIVE_WORKFLOW',

                'workflow_ready' => false,

                'proposed_task_count' => 0,

                'proposed_tasks' => [],

                'workflow_summary' =>
                    'Resident is not currently eligible for active-care workflow generation.',

                'safety_controls' => [
                    'human_review_required' => true,
                    'automatic_task_creation_allowed' => false,
                    'resident_status_guardrail_applied' => true,
                ],
            ];
        }

        $proposedTasks = [];

        /*
        |--------------------------------------------------------------------------
        | Convert execution-ready recommendations into proposed workflow items
        |--------------------------------------------------------------------------
        */

        foreach ($recommendations as $recommendation) {

            $executionReady =
                (bool) (
                    $recommendation['execution_ready']
                    ?? false
                );

            if (!$executionReady) {
                continue;
            }

            $executionStatus =
                strtoupper(
                    (string) (
                        $recommendation['execution_status']
                        ?? ''
                    )
                );

            if (
                $executionStatus
                !==
                'READY_FOR_HUMAN_REVIEW'
            ) {
                continue;
            }

            $proposedTasks[] = [
                'recommendation_code' =>
                    $recommendation['code']
                    ?? null,

                'category' =>
                    $recommendation['category']
                    ?? 'Clinical Recommendation',

                'proposed_task_name' =>
                    $this->buildTaskName(
                        $recommendation
                    ),

                'proposed_description' =>
                    $recommendation['action']
                    ?? '',

                'execution_type' =>
                    $recommendation['execution_type']
                    ?? 'CLINICAL_RECOMMENDATION',

                'workflow_target' =>
                    $recommendation['workflow_target']
                    ?? 'NURSE',

                'priority' =>
                    $recommendation['priority']
                    ?? 'NORMAL',

                'priority_score' =>
                    $recommendation['priority_score']
                    ?? 0,

                'priority_band' =>
                    $recommendation['priority_band']
                    ?? 'ROUTINE',

                'priority_rank' =>
                    $recommendation['priority_rank']
                    ?? null,

                'suggested_due_minutes' =>
                    $recommendation['suggested_due_minutes']
                    ?? null,

                'requires_acknowledgement' =>
                    (bool) (
                        $recommendation['requires_acknowledgement']
                        ?? true
                    ),

                'requires_doctor_review' =>
                    (bool) (
                        $recommendation['requires_doctor_review']
                        ?? false
                    ),

                'human_review_required' =>
                    true,

                'auto_execution_allowed' =>
                    false,

                'supporting_evidence' =>
                    $recommendation['supporting_evidence']
                    ?? [],

                'execution_reason' =>
                    $recommendation['execution_reason']
                    ?? null,

                'workflow_state' =>
                    'PROPOSED',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Sort workflow by priority rank / score
        |--------------------------------------------------------------------------
        */

        usort(
            $proposedTasks,
            function ($a, $b) {

                $aRank =
                    $a['priority_rank']
                    ?? PHP_INT_MAX;

                $bRank =
                    $b['priority_rank']
                    ?? PHP_INT_MAX;

                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }

                return
                    ($b['priority_score'] ?? 0)
                    <=>
                    ($a['priority_score'] ?? 0);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Workflow status
        |--------------------------------------------------------------------------
        */

        $workflowReady =
            !empty($proposedTasks);

        $workflowStatus =
            $workflowReady
            ?
            'READY_FOR_HUMAN_REVIEW'
            :
            'NO_EXECUTION_READY_RECOMMENDATIONS';

        return [
            'resident_id' => $residentId,

            'resident_name' => $residentName,

            'resident_status' => $residentStatus,

            'workflow_status' =>
                $workflowStatus,

            'workflow_ready' =>
                $workflowReady,

            'proposed_task_count' =>
                count($proposedTasks),

            'proposed_tasks' =>
                $proposedTasks,

            'workflow_summary' =>
                $this->buildWorkflowSummary(
                    $workflowReady,
                    count($proposedTasks)
                ),

            'safety_controls' => [
                'human_review_required' => true,
                'automatic_task_creation_allowed' => false,
                'resident_status_guardrail_applied' => true,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Build Proposed Task Name
    |--------------------------------------------------------------------------
    */

    protected function buildTaskName(
        array $recommendation
    ): string {

        $code =
            $recommendation['code']
            ?? '';

        $category =
            $recommendation['category']
            ?? 'Clinical Recommendation';

        return match ($code) {

            'overall_clinical_review' =>
                'AI Clinical Review',

            'additional_clinical_data' =>
                'Repeat Clinical Observations',

            'respiratory_monitoring' =>
                'Respiratory Monitoring',

            'cardiovascular_monitoring' =>
                'Cardiovascular Monitoring',

            'metabolic_monitoring' =>
                'Metabolic Monitoring',

            'infection_monitoring' =>
                'Infection Monitoring',

            'increased_monitoring' =>
                'Increase Monitoring Frequency',

            'medication_alert_review' =>
                'Medication Alert Review',

            'medication_review' =>
                'Medication Review',

            default =>
                $category,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Summary
    |--------------------------------------------------------------------------
    */

    protected function buildWorkflowSummary(
        bool $workflowReady,
        int $taskCount
    ): string {

        if (!$workflowReady) {
            return
                'No care recommendations currently meet the threshold for proposed workflow preparation.';
        }

        return
            $taskCount
            .
            ' AI care recommendation(s) are ready for human-reviewed workflow preparation.';
    }
}