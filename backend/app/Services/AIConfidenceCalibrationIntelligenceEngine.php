<?php

namespace App\Services;

use App\Models\AILearningEvidence;

class AIConfidenceCalibrationIntelligenceEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 54.6
    | Confidence Calibration Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Evaluated Evidence
        |--------------------------------------------------------------------------
        */

        $query =
            AILearningEvidence::query()
                ->where(
                    'learning_status',
                    'EVALUATED'
                )
                ->whereNotNull(
                    'ai_confidence'
                )
                ->whereNotNull(
                    'accuracy_score'
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

                'calibration_status' =>
                    'NO_EVIDENCE',

                'resident_id' =>
                    $residentId,

                'calibration_summary' => [

                    'evaluated_evidence_count' =>
                        0,

                    'average_ai_confidence' =>
                        0,

                    'average_observed_accuracy' =>
                        0,

                    'average_confidence_gap' =>
                        0,

                    'overall_calibration' =>
                        'UNKNOWN',
                ],

                'domain_calibration' =>
                    [],

                'confidence_bands' =>
                    [],

                'calibration_findings' => [
                    'No evaluated evidence with both AI confidence and observed accuracy is available.',
                ],

                'calibration_guardrails' =>
                    $this->guardrails(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Overall Calibration
        |--------------------------------------------------------------------------
        */

        $averageConfidence =
            round(
                (float) $evidence->avg(
                    'ai_confidence'
                ),
                2
            );

        $averageAccuracy =
            round(
                (float) $evidence->avg(
                    'accuracy_score'
                ),
                2
            );

        $averageGap =
            round(
                $averageConfidence
                -
                $averageAccuracy,
                2
            );

        $overallCalibration =
            $this->determineCalibration(
                $averageGap
            );


        /*
        |--------------------------------------------------------------------------
        | 4. Domain Grouping
        |--------------------------------------------------------------------------
        */

        $domainGroups =
            [];

        foreach ($evidence as $item) {

            $domain =
                strtoupper(
                    trim(
                        (string) (
                            $item->ai_domain
                            ?? 'UNKNOWN'
                        )
                    )
                );

            if ($domain === '') {
                $domain =
                    'UNKNOWN';
            }

            if (!isset(
                $domainGroups[
                    $domain
                ]
            )) {
                $domainGroups[
                    $domain
                ] = [];
            }

            $domainGroups[
                $domain
            ][] =
                $item;
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Domain Calibration
        |--------------------------------------------------------------------------
        */

        $domainCalibration =
            [];

        foreach (
            $domainGroups
            as $domain => $items
        ) {
            $collection =
                collect(
                    $items
                );

            $avgConfidence =
                round(
                    (float) $collection->avg(
                        'ai_confidence'
                    ),
                    2
                );

            $avgAccuracy =
                round(
                    (float) $collection->avg(
                        'accuracy_score'
                    ),
                    2
                );

            $gap =
                round(
                    $avgConfidence
                    -
                    $avgAccuracy,
                    2
                );

            $domainCalibration[] = [

                'ai_domain' =>
                    $domain,

                'evidence_count' =>
                    $collection->count(),

                'average_ai_confidence' =>
                    $avgConfidence,

                'average_observed_accuracy' =>
                    $avgAccuracy,

                'confidence_accuracy_gap' =>
                    $gap,

                'calibration_status' =>
                    $this->determineCalibration(
                        $gap
                    ),

                'learning_confidence' =>
                    $this->determineLearningConfidence(
                        $collection->count()
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Confidence Bands
        |--------------------------------------------------------------------------
        */

        $bands = [

            '0_59' => [
                'label' =>
                    '0-59',

                'min' =>
                    0,

                'max' =>
                    59.99,

                'items' =>
                    [],
            ],

            '60_79' => [
                'label' =>
                    '60-79',

                'min' =>
                    60,

                'max' =>
                    79.99,

                'items' =>
                    [],
            ],

            '80_100' => [
                'label' =>
                    '80-100',

                'min' =>
                    80,

                'max' =>
                    100,

                'items' =>
                    [],
            ],
        ];

        foreach ($evidence as $item) {

            $confidence =
                (float) $item->ai_confidence;

            if ($confidence < 60) {

                $bands[
                    '0_59'
                ]['items'][] =
                    $item;

            } elseif ($confidence < 80) {

                $bands[
                    '60_79'
                ]['items'][] =
                    $item;

            } else {

                $bands[
                    '80_100'
                ]['items'][] =
                    $item;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Confidence Band Performance
        |--------------------------------------------------------------------------
        */

        $confidenceBands =
            [];

        foreach ($bands as $band) {

            $collection =
                collect(
                    $band[
                        'items'
                    ]
                );

            if ($collection->isEmpty()) {
                continue;
            }

            $avgConfidence =
                round(
                    (float) $collection->avg(
                        'ai_confidence'
                    ),
                    2
                );

            $avgAccuracy =
                round(
                    (float) $collection->avg(
                        'accuracy_score'
                    ),
                    2
                );

            $gap =
                round(
                    $avgConfidence
                    -
                    $avgAccuracy,
                    2
                );

            $confidenceBands[] = [

                'confidence_band' =>
                    $band[
                        'label'
                    ],

                'evidence_count' =>
                    $collection->count(),

                'average_ai_confidence' =>
                    $avgConfidence,

                'average_observed_accuracy' =>
                    $avgAccuracy,

                'confidence_accuracy_gap' =>
                    $gap,

                'calibration_status' =>
                    $this->determineCalibration(
                        $gap
                    ),

                'learning_confidence' =>
                    $this->determineLearningConfidence(
                        $collection->count()
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Calibration Findings
        |--------------------------------------------------------------------------
        */

        $findings =
            [];

        $findings[] =
            'Confidence calibration currently includes '
            . $evidence->count()
            . ' evaluated evidence record(s).';

        $findings[] =
            'Average AI confidence is '
            . $averageConfidence
            . '%, compared with average observed accuracy of '
            . $averageAccuracy
            . '%.';

        if ($averageGap > 5) {

            $findings[] =
                'Overall AI confidence is higher than observed accuracy by '
                . $averageGap
                . ' percentage point(s), indicating potential over-confidence.';

        } elseif ($averageGap < -5) {

            $findings[] =
                'Observed accuracy is higher than AI confidence by '
                . abs(
                    $averageGap
                )
                . ' percentage point(s), indicating conservative confidence calibration.';

        } else {

            $findings[] =
                'Overall AI confidence is reasonably aligned with observed accuracy.';
        }

        foreach ($domainCalibration as $domain) {

            if (
                $domain[
                    'calibration_status'
                ]
                === 'OVER_CONFIDENT'
                ||
                $domain[
                    'calibration_status'
                ]
                === 'SLIGHTLY_OVER_CONFIDENT'
            ) {
                $findings[] =
                    $domain[
                        'ai_domain'
                    ]
                    . ' currently shows '
                    . strtolower(
                        str_replace(
                            '_',
                            ' ',
                            $domain[
                                'calibration_status'
                            ]
                        )
                    )
                    . ' confidence behavior.';
            }

            if (
                $domain[
                    'calibration_status'
                ]
                === 'UNDER_CONFIDENT'
                ||
                $domain[
                    'calibration_status'
                ]
                === 'SLIGHTLY_UNDER_CONFIDENT'
            ) {
                $findings[] =
                    $domain[
                        'ai_domain'
                    ]
                    . ' currently shows conservative confidence relative to observed accuracy.';
            }
        }

        if ($evidence->count() < 5) {
            $findings[] =
                'Calibration interpretation remains preliminary because fewer than 5 evaluated evidence records are available.';
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'calibration_status' =>
                $this->determineLearningStatus(
                    $evidence->count()
                ),

            'resident_id' =>
                $residentId,

            'calibration_summary' => [

                'evaluated_evidence_count' =>
                    $evidence->count(),

                'average_ai_confidence' =>
                    $averageConfidence,

                'average_observed_accuracy' =>
                    $averageAccuracy,

                'average_confidence_gap' =>
                    $averageGap,

                'overall_calibration' =>
                    $overallCalibration,
            ],

            'domain_calibration' =>
                $domainCalibration,

            'confidence_bands' =>
                $confidenceBands,

            'calibration_findings' =>
                $findings,

            'calibration_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Calibration Classification
    |--------------------------------------------------------------------------
    */

    private function determineCalibration(
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
    | Learning Status
    |--------------------------------------------------------------------------
    */

    private function determineLearningStatus(
        int $count
    ): string {
        if ($count >= 20) {
            return 'MATURE LEARNING';
        }

        if ($count >= 5) {
            return 'DEVELOPING LEARNING';
        }

        return 'EARLY LEARNING';
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

            'automatic_confidence_adjustment' =>
                false,

            'automatic_prediction_threshold_changes' =>
                false,

            'automatic_recommendation_threshold_changes' =>
                false,

            'automatic_model_retraining' =>
                false,

            'automatic_clinical_rule_changes' =>
                false,

            'automatic_clinical_action' =>
                false,

            'calibration_intelligence_advisory_only' =>
                true,

            'human_validation_required' =>
                true,

            'message' =>
                'Confidence calibration intelligence is advisory only. It does not automatically change confidence values, thresholds, models, recommendations, or clinical behavior.',
        ];
    }
}