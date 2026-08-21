<?php

namespace App\Services;

use App\Models\AILearningEvidence;

class AIWorkflowEffectivenessLearningEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 54.5
    | Workflow Effectiveness Learning
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Workflow Evidence
        |--------------------------------------------------------------------------
        */

        $query =
            AILearningEvidence::query()
                ->where(
                    'evidence_type',
                    'WORKFLOW_OUTCOME'
                )
                ->where(
                    'ai_domain',
                    'CARE_WORKFLOW'
                )
                ->where(
                    'learning_status',
                    'EVALUATED'
                );

        if ($residentId !== null) {
            $query->where(
                'resident_id',
                $residentId
            );
        }

        $evidence =
            $query
                ->orderBy(
                    'observed_at'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | 2. No Evidence
        |--------------------------------------------------------------------------
        */

        if ($evidence->isEmpty()) {
            return [

                'learning_status' =>
                    'NO_EVIDENCE',

                'resident_id' =>
                    $residentId,

                'workflow_learning_summary' => [

                    'evaluated_evidence_count' =>
                        0,

                    'completed_workflows' =>
                        0,

                    'workflow_completion_rate' =>
                        0,

                    'average_effectiveness_score' =>
                        0,

                    'average_accuracy_score' =>
                        0,

                    'human_agreement_rate' =>
                        0,
                ],

                'workflow_type_performance' =>
                    [],

                'workflow_target_performance' =>
                    [],

                'outcome_distribution' =>
                    [],

                'learning_insights' => [
                    'No evaluated AI care workflow learning evidence is currently available.',
                ],

                'learning_guardrails' =>
                    $this->guardrails(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Overall Workflow Metrics
        |--------------------------------------------------------------------------
        */

        $evaluatedCount =
            $evidence->count();

        $completedWorkflows =
            $evidence->filter(
                fn ($item) =>
                    strtoupper(
                        (string) $item->workflow_status
                    )
                    === 'COMPLETED'
            )
            ->count();

        $workflowCompletionRate =
            $evaluatedCount > 0
            ?
            round(
                (
                    $completedWorkflows
                    /
                    $evaluatedCount
                )
                * 100,
                2
            )
            :
            0;


        /*
        |--------------------------------------------------------------------------
        | 4. Effectiveness Metrics
        |--------------------------------------------------------------------------
        */

        $effectivenessEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->effectiveness_score !== null
            );

        $averageEffectiveness =
            $effectivenessEvidence->isNotEmpty()
            ?
            round(
                (float) $effectivenessEvidence->avg(
                    'effectiveness_score'
                ),
                2
            )
            :
            0;

        $accuracyEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->accuracy_score !== null
            );

        $averageAccuracy =
            $accuracyEvidence->isNotEmpty()
            ?
            round(
                (float) $accuracyEvidence->avg(
                    'accuracy_score'
                ),
                2
            )
            :
            0;


        /*
        |--------------------------------------------------------------------------
        | 5. Human Review Metrics
        |--------------------------------------------------------------------------
        */

        $reviewedEvidence =
            $evidence->filter(
                fn ($item) =>
                    strtoupper(
                        (string) $item->human_review_status
                    )
                    === 'REVIEWED'
            );

        $agreementCount =
            $reviewedEvidence->filter(
                fn ($item) =>
                    $item->human_agreement === true
            )
            ->count();

        $disagreementCount =
            $reviewedEvidence->filter(
                fn ($item) =>
                    $item->human_agreement === false
            )
            ->count();

        $humanAgreementRate =
            $reviewedEvidence->count() > 0
            ?
            round(
                (
                    $agreementCount
                    /
                    $reviewedEvidence->count()
                )
                * 100,
                2
            )
            :
            0;


        /*
        |--------------------------------------------------------------------------
        | 6. Outcome Distribution
        |--------------------------------------------------------------------------
        */

        $outcomeDistribution =
            [];

        foreach ($evidence as $item) {

            $status =
                strtoupper(
                    (string) (
                        $item->outcome_status
                        ?? 'UNKNOWN'
                    )
                );

            if (!isset(
                $outcomeDistribution[
                    $status
                ]
            )) {
                $outcomeDistribution[
                    $status
                ] = 0;
            }

            $outcomeDistribution[
                $status
            ]++;
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Group by Execution Type
        |--------------------------------------------------------------------------
        */

        $executionGroups =
            [];

        foreach ($evidence as $item) {

            $payload =
                is_array(
                    $item->evidence_payload
                )
                ?
                $item->evidence_payload
                :
                [];

            $executionType =
                $payload[
                    'execution_type'
                ]
                ??
                'UNKNOWN_EXECUTION_TYPE';

            $executionType =
                strtoupper(
                    trim(
                        (string) $executionType
                    )
                );

            if ($executionType === '') {
                $executionType =
                    'UNKNOWN_EXECUTION_TYPE';
            }

            if (!isset(
                $executionGroups[
                    $executionType
                ]
            )) {
                $executionGroups[
                    $executionType
                ] = [];
            }

            $executionGroups[
                $executionType
            ][] =
                $item;
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Execution Type Performance
        |--------------------------------------------------------------------------
        */

        $workflowTypePerformance =
            [];

        foreach (
            $executionGroups
            as $executionType => $items
        ) {
            $collection =
                collect(
                    $items
                );

            $count =
                $collection->count();

            $completed =
                $collection->filter(
                    fn ($item) =>
                        strtoupper(
                            (string) $item->workflow_status
                        )
                        === 'COMPLETED'
                )
                ->count();

            $completionRate =
                $count > 0
                ?
                round(
                    (
                        $completed
                        /
                        $count
                    )
                    * 100,
                    2
                )
                :
                0;

            $effectivenessValues =
                $collection
                    ->pluck(
                        'effectiveness_score'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $avgEffectiveness =
                $effectivenessValues->isNotEmpty()
                ?
                round(
                    (float) $effectivenessValues->avg(),
                    2
                )
                :
                0;

            $accuracyValues =
                $collection
                    ->pluck(
                        'accuracy_score'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $avgAccuracy =
                $accuracyValues->isNotEmpty()
                ?
                round(
                    (float) $accuracyValues->avg(),
                    2
                )
                :
                0;

            $reviewed =
                $collection->filter(
                    fn ($item) =>
                        strtoupper(
                            (string) $item->human_review_status
                        )
                        === 'REVIEWED'
                );

            $agreements =
                $reviewed->filter(
                    fn ($item) =>
                        $item->human_agreement === true
                )
                ->count();

            $agreementRate =
                $reviewed->count() > 0
                ?
                round(
                    (
                        $agreements
                        /
                        $reviewed->count()
                    )
                    * 100,
                    2
                )
                :
                0;

            $performanceLevel =
                $this->determinePerformanceLevel(
                    $count,
                    $avgEffectiveness,
                    $completionRate,
                    $agreementRate
                );

            $workflowTypePerformance[] = [

                'execution_type' =>
                    $executionType,

                'evidence_count' =>
                    $count,

                'completed_workflows' =>
                    $completed,

                'workflow_completion_rate' =>
                    $completionRate,

                'average_effectiveness_score' =>
                    $avgEffectiveness,

                'average_accuracy_score' =>
                    $avgAccuracy,

                'human_review_count' =>
                    $reviewed->count(),

                'human_agreement_rate' =>
                    $agreementRate,

                'performance_level' =>
                    $performanceLevel,

                'learning_confidence' =>
                    $this->determineLearningConfidence(
                        $count
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Group by Workflow Target
        |--------------------------------------------------------------------------
        */

        $targetGroups =
            [];

        foreach ($evidence as $item) {

            $payload =
                is_array(
                    $item->evidence_payload
                )
                ?
                $item->evidence_payload
                :
                [];

            $workflowTarget =
                $payload[
                    'workflow_target'
                ]
                ??
                'UNKNOWN_WORKFLOW_TARGET';

            $workflowTarget =
                strtoupper(
                    trim(
                        (string) $workflowTarget
                    )
                );

            if ($workflowTarget === '') {
                $workflowTarget =
                    'UNKNOWN_WORKFLOW_TARGET';
            }

            if (!isset(
                $targetGroups[
                    $workflowTarget
                ]
            )) {
                $targetGroups[
                    $workflowTarget
                ] = [];
            }

            $targetGroups[
                $workflowTarget
            ][] =
                $item;
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Workflow Target Performance
        |--------------------------------------------------------------------------
        */

        $workflowTargetPerformance =
            [];

        foreach (
            $targetGroups
            as $workflowTarget => $items
        ) {
            $collection =
                collect(
                    $items
                );

            $count =
                $collection->count();

            $completed =
                $collection->filter(
                    fn ($item) =>
                        strtoupper(
                            (string) $item->workflow_status
                        )
                        === 'COMPLETED'
                )
                ->count();

            $completionRate =
                $count > 0
                ?
                round(
                    (
                        $completed
                        /
                        $count
                    )
                    * 100,
                    2
                )
                :
                0;

            $effectivenessValues =
                $collection
                    ->pluck(
                        'effectiveness_score'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $avgEffectiveness =
                $effectivenessValues->isNotEmpty()
                ?
                round(
                    (float) $effectivenessValues->avg(),
                    2
                )
                :
                0;

            $workflowTargetPerformance[] = [

                'workflow_target' =>
                    $workflowTarget,

                'evidence_count' =>
                    $count,

                'completed_workflows' =>
                    $completed,

                'workflow_completion_rate' =>
                    $completionRate,

                'average_effectiveness_score' =>
                    $avgEffectiveness,

                'performance_level' =>
                    $this->determineTargetPerformanceLevel(
                        $count,
                        $avgEffectiveness,
                        $completionRate
                    ),

                'learning_confidence' =>
                    $this->determineLearningConfidence(
                        $count
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Rank Workflow Types
        |--------------------------------------------------------------------------
        */

        usort(
            $workflowTypePerformance,
            function (
                array $a,
                array $b
            ) {
                if (
                    $a[
                        'average_effectiveness_score'
                    ]
                    !==
                    $b[
                        'average_effectiveness_score'
                    ]
                ) {
                    return
                        $b[
                            'average_effectiveness_score'
                        ]
                        <=>
                        $a[
                            'average_effectiveness_score'
                        ];
                }

                return
                    $b[
                        'evidence_count'
                    ]
                    <=>
                    $a[
                        'evidence_count'
                    ];
            }
        );


        /*
        |--------------------------------------------------------------------------
        | 12. Rank Workflow Targets
        |--------------------------------------------------------------------------
        */

        usort(
            $workflowTargetPerformance,
            function (
                array $a,
                array $b
            ) {
                if (
                    $a[
                        'average_effectiveness_score'
                    ]
                    !==
                    $b[
                        'average_effectiveness_score'
                    ]
                ) {
                    return
                        $b[
                            'average_effectiveness_score'
                        ]
                        <=>
                        $a[
                            'average_effectiveness_score'
                        ];
                }

                return
                    $b[
                        'evidence_count'
                    ]
                    <=>
                    $a[
                        'evidence_count'
                    ];
            }
        );


        /*
        |--------------------------------------------------------------------------
        | 13. Learning Maturity
        |--------------------------------------------------------------------------
        */

        $learningStatus =
            match (true) {

                $evaluatedCount >= 20 =>
                    'MATURE LEARNING',

                $evaluatedCount >= 5 =>
                    'DEVELOPING LEARNING',

                default =>
                    'EARLY LEARNING',
            };


        /*
        |--------------------------------------------------------------------------
        | 14. Learning Insights
        |--------------------------------------------------------------------------
        */

        $learningInsights =
            [];

        $learningInsights[] =
            'AI workflow effectiveness learning currently includes '
            . $evaluatedCount
            . ' evaluated workflow evidence record(s).';

        $learningInsights[] =
            'Overall workflow completion rate is '
            . $workflowCompletionRate
            . '%.';

        $learningInsights[] =
            'Average workflow effectiveness is '
            . $averageEffectiveness
            . '%.';

        if (
            $reviewedEvidence->count() > 0
        ) {
            $learningInsights[] =
                'Human agreement with reviewed workflow evidence is '
                . $humanAgreementRate
                . '%.';
        }

        if ($evaluatedCount < 5) {
            $learningInsights[] =
                'Workflow effectiveness learning remains preliminary because fewer than 5 evaluated workflow evidence records are available.';
        }


        /*
        |--------------------------------------------------------------------------
        | 15. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'learning_status' =>
                $learningStatus,

            'resident_id' =>
                $residentId,

            'workflow_learning_summary' => [

                'evaluated_evidence_count' =>
                    $evaluatedCount,

                'completed_workflows' =>
                    $completedWorkflows,

                'workflow_completion_rate' =>
                    $workflowCompletionRate,

                'average_effectiveness_score' =>
                    $averageEffectiveness,

                'average_accuracy_score' =>
                    $averageAccuracy,

                'human_reviewed_evidence' =>
                    $reviewedEvidence->count(),

                'human_agreement_count' =>
                    $agreementCount,

                'human_disagreement_count' =>
                    $disagreementCount,

                'human_agreement_rate' =>
                    $humanAgreementRate,
            ],

            'outcome_distribution' =>
                $outcomeDistribution,

            'workflow_type_performance' =>
                $workflowTypePerformance,

            'workflow_target_performance' =>
                $workflowTargetPerformance,

            'learning_insights' =>
                $learningInsights,

            'learning_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Performance Level
    |--------------------------------------------------------------------------
    */

    private function determinePerformanceLevel(
        int $count,
        float $effectiveness,
        float $completionRate,
        float $agreementRate
    ): string {
        if ($count < 5) {
            return 'INSUFFICIENT DATA';
        }

        $combined =
            (
                $effectiveness
                +
                $completionRate
                +
                $agreementRate
            )
            /
            3;

        if ($combined >= 90) {
            return 'HIGH PERFORMANCE';
        }

        if ($combined >= 75) {
            return 'GOOD PERFORMANCE';
        }

        if ($combined >= 60) {
            return 'MODERATE PERFORMANCE';
        }

        return 'REVIEW REQUIRED';
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Target Performance Level
    |--------------------------------------------------------------------------
    */

    private function determineTargetPerformanceLevel(
        int $count,
        float $effectiveness,
        float $completionRate
    ): string {
        if ($count < 5) {
            return 'INSUFFICIENT DATA';
        }

        $combined =
            (
                $effectiveness
                +
                $completionRate
            )
            /
            2;

        if ($combined >= 90) {
            return 'HIGH PERFORMANCE';
        }

        if ($combined >= 75) {
            return 'GOOD PERFORMANCE';
        }

        if ($combined >= 60) {
            return 'MODERATE PERFORMANCE';
        }

        return 'REVIEW REQUIRED';
    }


    /*
    |--------------------------------------------------------------------------
    | Learning Confidence
    |--------------------------------------------------------------------------
    */

    private function determineLearningConfidence(
        int $count
    ): string {
        if ($count >= 20) {
            return 'HIGHER';
        }

        if ($count >= 10) {
            return 'MODERATE';
        }

        if ($count >= 5) {
            return 'LIMITED';
        }

        return 'VERY LIMITED';
    }


    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'automatic_workflow_changes' =>
                false,

            'automatic_workflow_target_changes' =>
                false,

            'automatic_task_assignment_changes' =>
                false,

            'automatic_clinical_action' =>
                false,

            'workflow_learning_advisory_only' =>
                true,

            'human_validation_required' =>
                true,

            'message' =>
                'Workflow effectiveness learning is advisory only. It does not automatically alter workflow routing, task assignment, execution rules, or clinical action.',
        ];
    }
}