<?php

namespace App\Services;

use App\Models\AILearningEvidence;

class AIPredictionPerformanceLearningEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 54.3
    | Prediction Performance Learning
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Prediction Evidence
        |--------------------------------------------------------------------------
        */

        $query =
            AILearningEvidence::query()
                ->where(
                    'evidence_type',
                    'PREDICTION_OUTCOME'
                )
                ->where(
                    'ai_domain',
                    'PREDICTIVE_HEALTH'
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

                'prediction_learning_summary' => [

                    'evaluated_evidence_count' =>
                        0,

                    'average_ai_confidence' =>
                        0,

                    'average_accuracy_score' =>
                        0,

                    'average_effectiveness_score' =>
                        0,

                    'confidence_accuracy_gap' =>
                        0,
                ],

                'prediction_type_performance' =>
                    [],

                'learning_insights' => [
                    'No evaluated predictive-health learning evidence is currently available.',
                ],

                'learning_guardrails' =>
                    $this->guardrails(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Global Performance Metrics
        |--------------------------------------------------------------------------
        */

        $averageConfidence =
            round(
                (float) $evidence->avg(
                    'ai_confidence'
                ),
                2
            );

        $accuracyEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->accuracy_score
                    !== null
            );

        $effectivenessEvidence =
            $evidence->filter(
                fn ($item) =>
                    $item->effectiveness_score
                    !== null
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

        $confidenceAccuracyGap =
            round(
                $averageConfidence
                -
                $averageAccuracy,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | 4. Human Agreement Metrics
        |--------------------------------------------------------------------------
        */

        $reviewedEvidence =
            $evidence->filter(
                fn ($item) =>
                    strtoupper(
                        (string) $item
                            ->human_review_status
                    )
                    === 'REVIEWED'
            );

        $humanAgreementCount =
            $reviewedEvidence->filter(
                fn ($item) =>
                    $item->human_agreement
                    === true
            )
            ->count();

        $humanDisagreementCount =
            $reviewedEvidence->filter(
                fn ($item) =>
                    $item->human_agreement
                    === false
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
        | 5. Outcome Distribution
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
        | 6. Prediction Type Grouping
        |--------------------------------------------------------------------------
        |
        | Prediction type is read from evidence_payload.
        |
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

            $predictionType =
                $payload[
                    'prediction_type'
                ]
                ??
                $payload[
                    'prediction_code'
                ]
                ??
                $item->ai_decision
                ??
                'UNKNOWN_PREDICTION';

            $predictionType =
                trim(
                    (string) $predictionType
                );

            if ($predictionType === '') {
                $predictionType =
                    'UNKNOWN_PREDICTION';
            }

            if (!isset(
                $grouped[
                    $predictionType
                ]
            )) {
                $grouped[
                    $predictionType
                ] = [];
            }

            $grouped[
                $predictionType
            ][] =
                $item;
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Prediction Type Performance
        |--------------------------------------------------------------------------
        */

        $predictionTypePerformance =
            [];

        foreach (
            $grouped
            as $predictionType => $items
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

            $confidenceGap =
                round(
                    $avgConfidence
                    -
                    $avgAccuracy,
                    2
                );

            $reviewed =
                $collection->filter(
                    fn ($item) =>
                        strtoupper(
                            (string) $item
                                ->human_review_status
                        )
                        === 'REVIEWED'
                );

            $agreements =
                $reviewed->filter(
                    fn ($item) =>
                        $item->human_agreement
                        === true
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
            | Performance Level
            |--------------------------------------------------------------------------
            */

            $performanceLevel =
                $this->determinePerformanceLevel(
                    $collection->count(),
                    $avgAccuracy,
                    $avgEffectiveness
                );


            /*
            |--------------------------------------------------------------------------
            | Learning Confidence
            |--------------------------------------------------------------------------
            */

            $learningConfidence =
                $this->determineLearningConfidence(
                    $collection->count()
                );


            /*
            |--------------------------------------------------------------------------
            | Calibration Status
            |--------------------------------------------------------------------------
            */

            $calibrationStatus =
                $this->determineCalibrationStatus(
                    $confidenceGap
                );


            $predictionTypePerformance[] = [

                'prediction_type' =>
                    $predictionType,

                'evidence_count' =>
                    $collection->count(),

                'average_ai_confidence' =>
                    $avgConfidence,

                'average_accuracy_score' =>
                    $avgAccuracy,

                'average_effectiveness_score' =>
                    $avgEffectiveness,

                'confidence_accuracy_gap' =>
                    $confidenceGap,

                'calibration_status' =>
                    $calibrationStatus,

                'human_review_count' =>
                    $reviewed->count(),

                'human_agreement_rate' =>
                    $agreementRate,

                'performance_level' =>
                    $performanceLevel,

                'learning_confidence' =>
                    $learningConfidence,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Rank Prediction Types
        |--------------------------------------------------------------------------
        */

        usort(
            $predictionTypePerformance,
            function (
                array $a,
                array $b
            ) {
                if (
                    $a[
                        'average_accuracy_score'
                    ]
                    !==
                    $b[
                        'average_accuracy_score'
                    ]
                ) {
                    return
                        $b[
                            'average_accuracy_score'
                        ]
                        <=>
                        $a[
                            'average_accuracy_score'
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
        | 9. Learning Maturity
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
        | 10. Learning Insights
        |--------------------------------------------------------------------------
        */

        $learningInsights =
            [];

        $learningInsights[] =
            'Predictive-health learning currently includes '
            . $evaluatedCount
            . ' evaluated evidence record(s).';

        $learningInsights[] =
            'Average AI prediction confidence is '
            . $averageConfidence
            . '%, while average observed accuracy is '
            . $averageAccuracy
            . '%.';

        if ($confidenceAccuracyGap > 5) {

            $learningInsights[] =
                'AI prediction confidence is currently higher than observed accuracy by '
                . $confidenceAccuracyGap
                . ' percentage point(s), indicating possible over-confidence.';

        } elseif ($confidenceAccuracyGap < -5) {

            $learningInsights[] =
                'Observed prediction accuracy is currently higher than AI confidence by '
                . abs(
                    $confidenceAccuracyGap
                )
                . ' percentage point(s), indicating conservative confidence estimates.';

        } else {

            $learningInsights[] =
                'AI prediction confidence is currently reasonably aligned with observed accuracy.';
        }

        if (
            $reviewedEvidence->count() > 0
        ) {
            $learningInsights[] =
                'Human agreement with reviewed predictive evidence is '
                . $humanAgreementRate
                . '%.';
        }

        if (
            $evaluatedCount < 5
        ) {
            $learningInsights[] =
                'Prediction performance learning remains preliminary because fewer than 5 evaluated evidence records are available.';
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'learning_status' =>
                $learningStatus,

            'resident_id' =>
                $residentId,

            'prediction_learning_summary' => [

                'evaluated_evidence_count' =>
                    $evaluatedCount,

                'average_ai_confidence' =>
                    $averageConfidence,

                'average_accuracy_score' =>
                    $averageAccuracy,

                'average_effectiveness_score' =>
                    $averageEffectiveness,

                'confidence_accuracy_gap' =>
                    $confidenceAccuracyGap,

                'human_reviewed_evidence' =>
                    $reviewedEvidence->count(),

                'human_agreement_count' =>
                    $humanAgreementCount,

                'human_disagreement_count' =>
                    $humanDisagreementCount,

                'human_agreement_rate' =>
                    $humanAgreementRate,
            ],

            'outcome_distribution' =>
                $outcomeDistribution,

            'prediction_type_performance' =>
                $predictionTypePerformance,

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
        float $accuracy,
        float $effectiveness
    ): string {
        if ($count < 5) {
            return 'INSUFFICIENT DATA';
        }

        $combined =
            (
                $accuracy
                +
                $effectiveness
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
    | Calibration Status
    |--------------------------------------------------------------------------
    */

    private function determineCalibrationStatus(
        float $gap
    ): string {
        if ($gap > 10) {
            return 'OVER_CONFIDENT';
        }

        if ($gap > 5) {
            return 'SLIGHTLY_OVER_CONFIDENT';
        }

        if ($gap < -10) {
            return 'UNDER_CONFIDENT';
        }

        if ($gap < -5) {
            return 'SLIGHTLY_UNDER_CONFIDENT';
        }

        return 'WELL_CALIBRATED';
    }


    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'automatic_prediction_threshold_changes' =>
                false,

            'automatic_model_changes' =>
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
                'Prediction performance learning is advisory only. It does not automatically modify prediction thresholds, model logic, or clinical behavior.',
        ];
    }
}