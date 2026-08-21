<?php

namespace App\Services;

use App\Models\AILearningEvidence;

class AICareRecommendationPerformanceLearningEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 54.4
    | Care Recommendation Performance Learning
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Recommendation Evidence
        |--------------------------------------------------------------------------
        */

        $query =
            AILearningEvidence::query()
                ->where(
                    'evidence_type',
                    'RECOMMENDATION_OUTCOME'
                )
                ->where(
                    'ai_domain',
                    'CARE_RECOMMENDATION'
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

                'recommendation_learning_summary' => [

                    'evaluated_evidence_count' =>
                        0,

                    'average_ai_confidence' =>
                        0,

                    'average_accuracy_score' =>
                        0,

                    'average_effectiveness_score' =>
                        0,

                    'human_agreement_rate' =>
                        0,
                ],

                'recommendation_type_performance' =>
                    [],

                'learning_insights' => [
                    'No evaluated care-recommendation learning evidence is currently available.',
                ],

                'learning_guardrails' =>
                    $this->guardrails(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Overall Metrics
        |--------------------------------------------------------------------------
        */

        $confidenceEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->ai_confidence !== null
            );

        $accuracyEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->accuracy_score !== null
            );

        $effectivenessEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->effectiveness_score !== null
            );

        $averageConfidence =
            $confidenceEvidence->isNotEmpty()
            ?
            round(
                (float) $confidenceEvidence->avg(
                    'ai_confidence'
                ),
                2
            )
            :
            0;

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


        /*
        |--------------------------------------------------------------------------
        | 4. Human Review Metrics
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

        $humanAgreementCount =
            $reviewedEvidence->filter(
                fn ($item) =>
                    $item->human_agreement === true
            )
            ->count();

        $humanDisagreementCount =
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
                    $humanAgreementCount
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
        | 5. Workflow Completion Metrics
        |--------------------------------------------------------------------------
        */

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
            $evidence->count() > 0
            ?
            round(
                (
                    $completedWorkflows
                    /
                    $evidence->count()
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
        | 7. Group Recommendation Types
        |--------------------------------------------------------------------------
        */

        $grouped =
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

            $recommendationType =
                $payload[
                    'recommendation_code'
                ]
                ??
                $payload[
                    'recommendation_type'
                ]
                ??
                $item->ai_decision
                ??
                'UNKNOWN_RECOMMENDATION';

            $recommendationType =
                trim(
                    (string) $recommendationType
                );

            if ($recommendationType === '') {
                $recommendationType =
                    'UNKNOWN_RECOMMENDATION';
            }

            if (!isset(
                $grouped[
                    $recommendationType
                ]
            )) {
                $grouped[
                    $recommendationType
                ] = [];
            }

            $grouped[
                $recommendationType
            ][] =
                $item;
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Recommendation Type Performance
        |--------------------------------------------------------------------------
        */

        $recommendationTypePerformance =
            [];

        foreach (
            $grouped
            as $recommendationType => $items
        ) {
            $collection =
                collect(
                    $items
                );

            $confidenceValues =
                $collection
                    ->pluck(
                        'ai_confidence'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $accuracyValues =
                $collection
                    ->pluck(
                        'accuracy_score'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $effectivenessValues =
                $collection
                    ->pluck(
                        'effectiveness_score'
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

            $avgConfidence =
                $confidenceValues->isNotEmpty()
                ?
                round(
                    (float) $confidenceValues->avg(),
                    2
                )
                :
                0;

            $avgAccuracy =
                $accuracyValues->isNotEmpty()
                ?
                round(
                    (float) $accuracyValues->avg(),
                    2
                )
                :
                0;

            $avgEffectiveness =
                $effectivenessValues->isNotEmpty()
                ?
                round(
                    (float) $effectivenessValues->avg(),
                    2
                )
                :
                0;


            /*
            |--------------------------------------------------------------------------
            | Recommendation Human Agreement
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Recommendation Workflow Completion
            |--------------------------------------------------------------------------
            */

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
                $collection->count() > 0
                ?
                round(
                    (
                        $completed
                        /
                        $collection->count()
                    )
                    * 100,
                    2
                )
                :
                0;


            /*
            |--------------------------------------------------------------------------
            | Performance Classification
            |--------------------------------------------------------------------------
            */

            $performanceLevel =
                $this->determinePerformanceLevel(
                    $collection->count(),
                    $avgEffectiveness,
                    $agreementRate,
                    $completionRate
                );

            $learningConfidence =
                $this->determineLearningConfidence(
                    $collection->count()
                );


            /*
            |--------------------------------------------------------------------------
            | Recommendation Record
            |--------------------------------------------------------------------------
            */

            $recommendationTypePerformance[] = [

                'recommendation_type' =>
                    $recommendationType,

                'evidence_count' =>
                    $collection->count(),

                'average_ai_confidence' =>
                    $avgConfidence,

                'average_accuracy_score' =>
                    $avgAccuracy,

                'average_effectiveness_score' =>
                    $avgEffectiveness,

                'human_review_count' =>
                    $reviewed->count(),

                'human_agreement_rate' =>
                    $agreementRate,

                'completed_workflows' =>
                    $completed,

                'workflow_completion_rate' =>
                    $completionRate,

                'performance_level' =>
                    $performanceLevel,

                'learning_confidence' =>
                    $learningConfidence,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Rank Recommendations
        |--------------------------------------------------------------------------
        */

        usort(
            $recommendationTypePerformance,
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
        | 10. Learning Maturity
        |--------------------------------------------------------------------------
        */

        $evaluatedCount =
            $evidence->count();

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
        | 11. Learning Insights
        |--------------------------------------------------------------------------
        */

        $learningInsights =
            [];

        $learningInsights[] =
            'Care-recommendation learning currently includes '
            . $evaluatedCount
            . ' evaluated evidence record(s).';

        $learningInsights[] =
            'Average recommendation effectiveness is '
            . $averageEffectiveness
            . '%.';

        if (
            $reviewedEvidence->count() > 0
        ) {
            $learningInsights[] =
                'Human agreement with reviewed care recommendations is '
                . $humanAgreementRate
                . '%.';
        }

        $learningInsights[] =
            'Workflow completion associated with evaluated recommendations is '
            . $workflowCompletionRate
            . '%.';

        if (
            $evaluatedCount < 5
        ) {
            $learningInsights[] =
                'Care-recommendation performance learning remains preliminary because fewer than 5 evaluated evidence records are available.';
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'learning_status' =>
                $learningStatus,

            'resident_id' =>
                $residentId,

            'recommendation_learning_summary' => [

                'evaluated_evidence_count' =>
                    $evaluatedCount,

                'average_ai_confidence' =>
                    $averageConfidence,

                'average_accuracy_score' =>
                    $averageAccuracy,

                'average_effectiveness_score' =>
                    $averageEffectiveness,

                'human_reviewed_evidence' =>
                    $reviewedEvidence->count(),

                'human_agreement_count' =>
                    $humanAgreementCount,

                'human_disagreement_count' =>
                    $humanDisagreementCount,

                'human_agreement_rate' =>
                    $humanAgreementRate,

                'completed_workflows' =>
                    $completedWorkflows,

                'workflow_completion_rate' =>
                    $workflowCompletionRate,
            ],

            'outcome_distribution' =>
                $outcomeDistribution,

            'recommendation_type_performance' =>
                $recommendationTypePerformance,

            'learning_insights' =>
                $learningInsights,

            'learning_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Performance Level
    |--------------------------------------------------------------------------
    */

    private function determinePerformanceLevel(
        int $count,
        float $effectiveness,
        float $agreementRate,
        float $completionRate
    ): string {
        if ($count < 5) {
            return 'INSUFFICIENT DATA';
        }

        $combined =
            (
                $effectiveness
                +
                $agreementRate
                +
                $completionRate
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

            'automatic_recommendation_priority_changes' =>
                false,

            'automatic_recommendation_suppression' =>
                false,

            'automatic_recommendation_promotion' =>
                false,

            'automatic_clinical_rule_changes' =>
                false,

            'automatic_clinical_action' =>
                false,

            'performance_learning_advisory_only' =>
                true,

            'human_validation_required' =>
                true,

            'message' =>
                'Care recommendation performance learning is advisory only. It does not automatically promote, suppress, reprioritize, or execute recommendations.',
        ];
    }
}