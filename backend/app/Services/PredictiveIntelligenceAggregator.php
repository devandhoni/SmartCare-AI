<?php

namespace App\Services;

use App\Models\Resident;

class PredictiveIntelligenceAggregator
{
    protected PredictiveDeteriorationService $predictiveService;

    public function __construct(
        PredictiveDeteriorationService $predictiveService
    ) {
        $this->predictiveService = $predictiveService;
    }

    /**
     * Build facility-level predictive intelligence.
     */
    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Step 51.6B
        | Facility Predictive Intelligence Aggregation
        |--------------------------------------------------------------------------
        |
        | PredictiveDeteriorationService remains the single source of truth
        | for resident-level deterioration prediction.
        |
        | This service aggregates those resident predictions into an
        | executive / AI Command Center view.
        |
        */

        $residents = Resident::query()
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Facility Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'residents_evaluated' => 0,
            'critical_deterioration_risk' => 0,
            'high_deterioration_risk' => 0,
            'medium_deterioration_risk' => 0,
            'low_deterioration_risk' => 0,
            'urgent_clinical_reviews' => 0,
            'worsening_residents' => 0,
            'limited_evidence_cases' => 0,
        ];

        $driverCounts = [];

        $priorityResidents = [];

        /*
        |--------------------------------------------------------------------------
        | Analyze Every Resident
        |--------------------------------------------------------------------------
        */

        foreach ($residents as $resident) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Resident-Level Predictive Intelligence
                |--------------------------------------------------------------------------
                */

                $prediction =
                    $this->predictiveService->predict(
                        $resident->id
                    );

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Failure Isolation
                |--------------------------------------------------------------------------
                |
                | One resident prediction failure should not prevent the
                | entire AI Command Center from loading.
                |
                */

                continue;
            }

            if (!is_array($prediction)) {

                continue;
            }

            $summary['residents_evaluated']++;

            /*
            |--------------------------------------------------------------------------
            | Deterioration Risk Aggregation
            |--------------------------------------------------------------------------
            */

            $risk =
                strtoupper(
                    (string) (
                        $prediction['deterioration_risk']
                        ?? 'LOW'
                    )
                );

            switch ($risk) {

                case 'CRITICAL':

                    $summary[
                        'critical_deterioration_risk'
                    ]++;

                    break;

                case 'HIGH':

                    $summary[
                        'high_deterioration_risk'
                    ]++;

                    break;

                case 'MEDIUM':

                    $summary[
                        'medium_deterioration_risk'
                    ]++;

                    break;

                default:

                    $summary[
                        'low_deterioration_risk'
                    ]++;

                    break;
            }

            /*
            |--------------------------------------------------------------------------
            | Clinical Escalation Aggregation
            |--------------------------------------------------------------------------
            */

            $escalation =
                strtoupper(
                    (string) (
                        $prediction['escalation_status']
                        ?? ''
                    )
                );

            if (
                $escalation ===
                'URGENT CLINICAL REVIEW'
            ) {

                $summary[
                    'urgent_clinical_reviews'
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Trend Aggregation
            |--------------------------------------------------------------------------
            */

            $trend =
                strtoupper(
                    (string) (
                        $prediction['trend_direction']
                        ?? ''
                    )
                );

            if ($trend === 'WORSENING') {

                $summary[
                    'worsening_residents'
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Evidence Quality Aggregation
            |--------------------------------------------------------------------------
            */

            $evidenceQuality =
                strtoupper(
                    (string) (
                        $prediction[
                            'evidence_quality'
                        ]['status']
                        ?? 'UNKNOWN'
                    )
                );

            if ($evidenceQuality === 'LIMITED') {

                $summary[
                    'limited_evidence_cases'
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Clinical Driver Aggregation
            |--------------------------------------------------------------------------
            */

            $primaryDriver =
                $prediction[
                    'clinical_drivers'
                ]['primary_driver']
                ?? null;

            if ($primaryDriver) {

                if (
                    !isset(
                        $driverCounts[
                            $primaryDriver
                        ]
                    )
                ) {

                    $driverCounts[
                        $primaryDriver
                    ] = 0;
                }

                $driverCounts[
                    $primaryDriver
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Priority Resident Detection
            |--------------------------------------------------------------------------
            */

            $clinicalSeverity =
                strtoupper(
                    (string) (
                        $prediction[
                            'clinical_severity'
                        ]
                        ?? ''
                    )
                );

            $isPriority =
                in_array(
                    $risk,
                    [
                        'CRITICAL',
                        'HIGH'
                    ],
                    true
                )
                ||
                $clinicalSeverity ===
                    'CRITICAL'
                ||
                $trend ===
                    'WORSENING'
                ||
                $escalation ===
                    'URGENT CLINICAL REVIEW';

            if ($isPriority) {

                $priorityResidents[] = [

                    'resident_id' =>
                        (int) $resident->id,

                    'resident_name' =>
                        $resident->full_name
                        ??
                        $resident->name
                        ??
                        (
                            'Resident '
                            .
                            $resident->id
                        ),

                    'resident_status' =>
                        $resident->status
                        ?? 'UNKNOWN',

                    'clinical_severity' =>
                        $prediction[
                            'clinical_severity'
                        ]
                        ?? 'UNKNOWN',

                    'deterioration_risk' =>
                        $prediction[
                            'deterioration_risk'
                        ]
                        ?? 'UNKNOWN',

                    'risk_score' =>
                        $prediction[
                            'risk_score'
                        ]
                        ?? 0,

                    'prediction_window' =>
                        $prediction[
                            'prediction_window'
                        ]
                        ?? null,

                    'trend_direction' =>
                        $prediction[
                            'trend_direction'
                        ]
                        ?? 'UNKNOWN',

                    'prediction_confidence' =>
                        $prediction[
                            'prediction_confidence'
                        ]
                        ?? 0,

                    'escalation_status' =>
                        $prediction[
                            'escalation_status'
                        ]
                        ?? 'NONE',

                    'clinical_action_timing' =>
                        $prediction[
                            'clinical_action_timing'
                        ]
                        ?? 'ROUTINE',

                    'primary_driver' =>
                        $prediction[
                            'clinical_drivers'
                        ]['primary_driver']
                        ?? null,

                    'primary_driver_score' =>
                        $prediction[
                            'clinical_drivers'
                        ]['primary_score']
                        ?? 0,

                    'dominant_drivers' =>
                        $prediction[
                            'clinical_drivers'
                        ]['dominant_drivers']
                        ?? [],

                    'driver_summary' =>
                        $prediction[
                            'clinical_drivers'
                        ]['driver_summary']
                        ?? null,

                    'evidence_quality' =>
                        $prediction[
                            'evidence_quality'
                        ]['status']
                        ?? 'UNKNOWN',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Rank Clinical Drivers
        |--------------------------------------------------------------------------
        */

        arsort(
            $driverCounts
        );

        $topDrivers = [];

        foreach (
            $driverCounts
            as $driver => $count
        ) {

            $topDrivers[] = [

                'driver' =>
                    $driver,

                'resident_count' =>
                    $count,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Rank Priority Residents
        |--------------------------------------------------------------------------
        */

        $riskRank = [

            'CRITICAL' => 4,
            'HIGH' => 3,
            'MEDIUM' => 2,
            'LOW' => 1,
        ];

        usort(
            $priorityResidents,
            function (
                $a,
                $b
            ) use (
                $riskRank
            ) {

                $aRisk =
                    strtoupper(
                        (string) (
                            $a[
                                'deterioration_risk'
                            ]
                            ?? 'LOW'
                        )
                    );

                $bRisk =
                    strtoupper(
                        (string) (
                            $b[
                                'deterioration_risk'
                            ]
                            ?? 'LOW'
                        )
                    );

                $aRank =
                    $riskRank[
                        $aRisk
                    ]
                    ?? 0;

                $bRank =
                    $riskRank[
                        $bRisk
                    ]
                    ?? 0;

                /*
                |--------------------------------------------------------------------------
                | Highest Risk First
                |--------------------------------------------------------------------------
                */

                if ($aRank !== $bRank) {

                    return
                        $bRank
                        <=>
                        $aRank;
                }

                /*
                |--------------------------------------------------------------------------
                | Highest Risk Score Next
                |--------------------------------------------------------------------------
                */

                $aScore =
                    $a[
                        'risk_score'
                    ]
                    ?? 0;

                $bScore =
                    $b[
                        'risk_score'
                    ]
                    ?? 0;

                if ($aScore !== $bScore) {

                    return
                        $bScore
                        <=>
                        $aScore;
                }

                /*
                |--------------------------------------------------------------------------
                | Highest Prediction Confidence Next
                |--------------------------------------------------------------------------
                */

                return
                    (
                        $b[
                            'prediction_confidence'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'prediction_confidence'
                        ]
                        ?? 0
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Facility Command Status
        |--------------------------------------------------------------------------
        */

        $commandStatus =
            'STABLE';

        if (
            $summary[
                'critical_deterioration_risk'
            ] > 0
            ||
            $summary[
                'urgent_clinical_reviews'
            ] > 0
        ) {

            $commandStatus =
                'CRITICAL';

        } elseif (
            $summary[
                'high_deterioration_risk'
            ] > 0
            ||
            $summary[
                'worsening_residents'
            ] > 0
        ) {

            $commandStatus =
                'HIGH ATTENTION';

        } elseif (
            $summary[
                'medium_deterioration_risk'
            ] > 0
        ) {

            $commandStatus =
                'MONITOR';
        }

        /*
        |--------------------------------------------------------------------------
        | Final Facility Predictive Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'command_status' =>
                $commandStatus,

            'summary' =>
                $summary,

            'top_clinical_drivers' =>
                $topDrivers,

            'priority_residents' =>
                $priorityResidents,
        ];
    }
}