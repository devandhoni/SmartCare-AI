<?php

namespace App\Services;

use App\Models\HealthPrediction;
use App\Models\AiClinicalOutcome;
use Carbon\Carbon;

class AILearningAnalyzer
{
    /*
    |--------------------------------------------------------------------------
    | AI Learning Performance Analysis
    |--------------------------------------------------------------------------
    */

    public function analyze($residentId)
    {
        /*
        |--------------------------------------------------------------------------
        | Prediction Statistics
        |--------------------------------------------------------------------------
        */

        $predictions = HealthPrediction::where(
            'resident_id',
            $residentId
        )
        ->orderBy(
            'created_on',
            'asc'
        )
        ->get();

        $totalPredictions =
            $predictions->count();

        $averageConfidence =
            $totalPredictions
            ?
            round(
                $predictions->avg('confidence'),
                2
            )
            :
            0;

        /*
        |--------------------------------------------------------------------------
        | Prediction Model Performance
        |--------------------------------------------------------------------------
        */

        $modelPerformance = [];

        foreach (
            $predictions->groupBy('prediction_type')
            as $type => $items
        ) {
            $modelPerformance[$type] = [

                "prediction_count" =>
                    $items->count(),

                "average_confidence" =>
                    round(
                        $items->avg('confidence'),
                        2
                    ),

                "risk_distribution" =>
                    $items
                    ->groupBy('risk_level')
                    ->map(function ($risk) {
                        return $risk->count();
                    })
                    ->toArray()
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Outcome Learning
        |--------------------------------------------------------------------------
        */

        $outcomes = AiClinicalOutcome::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'recorded_at',
                'asc'
            )
            ->get();

        $evaluatedCases =
            $outcomes->count();

        $averageAccuracy =
            $evaluatedCases
            ?
            round(
                $outcomes->avg(
                    'ai_accuracy_score'
                ),
                2
            )
            :
            0;

        $successfulCases =
            $outcomes
            ->whereIn(
                'outcome_status',
                [
                    'IMPROVED',
                    'STABLE'
                ]
            )
            ->count();

        $successRate =
            $evaluatedCases
            ?
            round(
                (
                    $successfulCases
                    /
                    $evaluatedCases
                )
                *
                100,
                2
            )
            :
            0;

        /*
        |--------------------------------------------------------------------------
        | AI Confidence Calibration
        |--------------------------------------------------------------------------
        */

        $calibration = [

            "average_ai_confidence" =>
                $averageConfidence,

            "actual_accuracy" =>
                $averageAccuracy,

            "confidence_gap" =>
                round(
                    $averageConfidence
                    -
                    $averageAccuracy,
                    2
                ),

            "calibration_status" =>
                $averageAccuracy >= 90
                ?
                "HIGH PERFORMANCE"
                :
                (
                    $averageAccuracy >= 70
                    ?
                    "GOOD PERFORMANCE"
                    :
                    "NEEDS IMPROVEMENT"
                )
        ];

        /*
        |--------------------------------------------------------------------------
        | Learning Trend
        |--------------------------------------------------------------------------
        */

        $learningTrend =
            $outcomes
            ->map(function ($item) {

                return [

                    "date" =>
                        Carbon::parse(
                            $item->recorded_at
                            ??
                            $item->created_at
                        )
                        ->format('Y-m-d'),

                    "accuracy" =>
                        $item->ai_accuracy_score,

                    "status" =>
                        $item->outcome_status
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Final AI Learning Response
        |--------------------------------------------------------------------------
        */

        return [

            "resident_id" =>
                (int) $residentId,

            "system_learning_status" =>
                $evaluatedCases === 0
                ?
                "AWAITING OUTCOME DATA"
                :
                (
                    $averageAccuracy >= 90
                    ?
                    "OPTIMAL"
                    :
                    "LEARNING"
                ),

            "prediction_statistics" => [

                "total_predictions" =>
                    $totalPredictions,

                "average_confidence" =>
                    $averageConfidence
            ],

            "model_performance" =>
                $modelPerformance,

            "outcome_learning" => [

                "evaluated_cases" =>
                    $evaluatedCases,

                "average_accuracy" =>
                    $averageAccuracy,

                "successful_interventions" =>
                    $successfulCases,

                "success_rate" =>
                    $successRate
            ],

            "confidence_calibration" =>
                $calibration,

            "learning_trend" =>
                $learningTrend
        ];
    }
}