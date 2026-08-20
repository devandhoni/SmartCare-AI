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
     * Step 51.6B
     *
     * Build facility-level predictive intelligence.
     */
    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Residents
        |--------------------------------------------------------------------------
        */

        $residents = Resident::query()
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Facility Summary
        |--------------------------------------------------------------------------
        |
        | Current operational predictive counts are separated from
        | historical / non-active resident predictive intelligence.
        |
        */

        $summary = [

            'residents_evaluated' => 0,

            /*
            |--------------------------------------------------------------------------
            | Resident Census
            |--------------------------------------------------------------------------
            */

            'active_care_residents' => 0,
            'non_active_care_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | Active-Care Predictive Risk
            |--------------------------------------------------------------------------
            */

            'critical_deterioration_risk' => 0,
            'high_deterioration_risk' => 0,
            'medium_deterioration_risk' => 0,
            'low_deterioration_risk' => 0,

            /*
            |--------------------------------------------------------------------------
            | Historical / Non-Active Predictive Risk
            |--------------------------------------------------------------------------
            */

            'historical_critical_deterioration_risk' => 0,
            'historical_high_deterioration_risk' => 0,
            'historical_medium_deterioration_risk' => 0,
            'historical_low_deterioration_risk' => 0,

            /*
            |--------------------------------------------------------------------------
            | Active-Care Operational Signals
            |--------------------------------------------------------------------------
            */

            'urgent_clinical_reviews' => 0,
            'worsening_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | Historical Operational Signals
            |--------------------------------------------------------------------------
            */

            'historical_urgent_clinical_reviews' => 0,
            'historical_worsening_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | Evidence Quality
            |--------------------------------------------------------------------------
            */

            'limited_evidence_cases' => 0,
            'active_care_limited_evidence_cases' => 0,
            'non_active_limited_evidence_cases' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Aggregation Containers
        |--------------------------------------------------------------------------
        */

        $driverCounts = [];

        $activeDriverCounts = [];

        $historicalDriverCounts = [];

        $priorityResidents = [];

        $historicalPriorityResidents = [];

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
                        (int) $resident->id
                    );

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Failure Isolation
                |--------------------------------------------------------------------------
                |
                | One resident prediction failure should not prevent the
                | facility AI Command Center from loading.
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
            | Resident Operational Care Status
            |--------------------------------------------------------------------------
            |
            | Only residents with status ACTIVE participate in current
            | operational predictive escalation.
            |
            */

            $residentStatus =
                strtoupper(
                    trim(
                        (string) (
                            $resident->status
                            ?? ''
                        )
                    )
                );

            $activeCareEligible =
                $residentStatus === 'ACTIVE';

            if ($activeCareEligible) {

                $summary[
                    'active_care_residents'
                ]++;

            } else {

                $summary[
                    'non_active_care_residents'
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Deterioration Risk
            |--------------------------------------------------------------------------
            */

            $risk =
                strtoupper(
                    (string) (
                        $prediction[
                            'deterioration_risk'
                        ]
                        ?? 'LOW'
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Active vs Historical Risk Aggregation
            |--------------------------------------------------------------------------
            */

            if ($activeCareEligible) {

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

            } else {

                switch ($risk) {

                    case 'CRITICAL':

                        $summary[
                            'historical_critical_deterioration_risk'
                        ]++;

                        break;

                    case 'HIGH':

                        $summary[
                            'historical_high_deterioration_risk'
                        ]++;

                        break;

                    case 'MEDIUM':

                        $summary[
                            'historical_medium_deterioration_risk'
                        ]++;

                        break;

                    default:

                        $summary[
                            'historical_low_deterioration_risk'
                        ]++;

                        break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Escalation Status
            |--------------------------------------------------------------------------
            */

            $escalation =
                strtoupper(
                    (string) (
                        $prediction[
                            'escalation_status'
                        ]
                        ?? ''
                    )
                );

            if (
                $escalation ===
                'URGENT CLINICAL REVIEW'
            ) {

                if ($activeCareEligible) {

                    $summary[
                        'urgent_clinical_reviews'
                    ]++;

                } else {

                    $summary[
                        'historical_urgent_clinical_reviews'
                    ]++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Trend Direction
            |--------------------------------------------------------------------------
            */

            $trend =
                strtoupper(
                    (string) (
                        $prediction[
                            'trend_direction'
                        ]
                        ?? ''
                    )
                );

            if ($trend === 'WORSENING') {

                if ($activeCareEligible) {

                    $summary[
                        'worsening_residents'
                    ]++;

                } else {

                    $summary[
                        'historical_worsening_residents'
                    ]++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Evidence Quality
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

                if ($activeCareEligible) {

                    $summary[
                        'active_care_limited_evidence_cases'
                    ]++;

                } else {

                    $summary[
                        'non_active_limited_evidence_cases'
                    ]++;
                }
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

                /*
                |--------------------------------------------------------------------------
                | All Residents
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Active vs Historical Driver Counts
                |--------------------------------------------------------------------------
                */

                if ($activeCareEligible) {

                    if (
                        !isset(
                            $activeDriverCounts[
                                $primaryDriver
                            ]
                        )
                    ) {
                        $activeDriverCounts[
                            $primaryDriver
                        ] = 0;
                    }

                    $activeDriverCounts[
                        $primaryDriver
                    ]++;

                } else {

                    if (
                        !isset(
                            $historicalDriverCounts[
                                $primaryDriver
                            ]
                        )
                    ) {
                        $historicalDriverCounts[
                            $primaryDriver
                        ] = 0;
                    }

                    $historicalDriverCounts[
                        $primaryDriver
                    ]++;
                }
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
                        'HIGH',
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

            /*
            |--------------------------------------------------------------------------
            | Shared Resident Predictive Payload
            |--------------------------------------------------------------------------
            */

            $residentPayload = [

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

                'active_care_eligible' =>
                    $activeCareEligible,

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

            /*
            |--------------------------------------------------------------------------
            | Active Priority Residents
            |--------------------------------------------------------------------------
            |
            | Only active-care residents may enter the operational
            | priority resident list.
            |
            */

            if (
                $isPriority
                &&
                $activeCareEligible
            ) {

                $priorityResidents[] =
                    $residentPayload;
            }

            /*
            |--------------------------------------------------------------------------
            | Historical Priority Residents
            |--------------------------------------------------------------------------
            |
            | Non-active residents retain predictive intelligence but are
            | explicitly separated from operational escalation.
            |
            */

            if (
                $isPriority
                &&
                !$activeCareEligible
            ) {

                $historicalPriorityResidents[] =
                    array_merge(
                        $residentPayload,
                        [
                            'operational_status' =>
                                'HISTORICAL / NON-ACTIVE',

                            'operational_escalation_allowed' =>
                                false,

                            'historical_context_message' =>
                                'Predictive risk is retained for historical intelligence only. Resident is not currently active in care.',
                        ]
                    );
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

        arsort(
            $activeDriverCounts
        );

        arsort(
            $historicalDriverCounts
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

                'active_care_resident_count' =>
                    $activeDriverCounts[
                        $driver
                    ]
                    ?? 0,

                'historical_resident_count' =>
                    $historicalDriverCounts[
                        $driver
                    ]
                    ?? 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Ranking
        |--------------------------------------------------------------------------
        */

        $riskRank = [

            'CRITICAL' => 4,
            'HIGH' => 3,
            'MEDIUM' => 2,
            'LOW' => 1,
        ];

        /*
        |--------------------------------------------------------------------------
        | Rank Active Priority Residents
        |--------------------------------------------------------------------------
        */

        $sortResidents =
            function (
                array &$residentCollection
            ) use (
                $riskRank
            ): void {

                usort(
                    $residentCollection,
                    function (
                        array $a,
                        array $b
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
                            (float) (
                                $a[
                                    'risk_score'
                                ]
                                ?? 0
                            );

                        $bScore =
                            (float) (
                                $b[
                                    'risk_score'
                                ]
                                ?? 0
                            );

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
            };

        $sortResidents(
            $priorityResidents
        );

        $sortResidents(
            $historicalPriorityResidents
        );

        /*
        |--------------------------------------------------------------------------
        | Distribution Validation
        |--------------------------------------------------------------------------
        */

        $activeRiskDistributionTotal =
            $summary[
                'critical_deterioration_risk'
            ]
            +
            $summary[
                'high_deterioration_risk'
            ]
            +
            $summary[
                'medium_deterioration_risk'
            ]
            +
            $summary[
                'low_deterioration_risk'
            ];

        $historicalRiskDistributionTotal =
            $summary[
                'historical_critical_deterioration_risk'
            ]
            +
            $summary[
                'historical_high_deterioration_risk'
            ]
            +
            $summary[
                'historical_medium_deterioration_risk'
            ]
            +
            $summary[
                'historical_low_deterioration_risk'
            ];

        $summary[
            'active_risk_distribution_total'
        ] =
            $activeRiskDistributionTotal;

        $summary[
            'active_risk_distribution_valid'
        ] =
            $activeRiskDistributionTotal
            ===
            $summary[
                'active_care_residents'
            ];

        $summary[
            'historical_risk_distribution_total'
        ] =
            $historicalRiskDistributionTotal;

        $summary[
            'historical_risk_distribution_valid'
        ] =
            $historicalRiskDistributionTotal
            ===
            $summary[
                'non_active_care_residents'
            ];

        /*
        |--------------------------------------------------------------------------
        | Facility Command Status
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Only active-care resident risk controls current operational
        | command status.
        |
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
        | Historical Context
        |--------------------------------------------------------------------------
        */

        $historicalContext = [

            'non_active_care_residents' =>
                $summary[
                    'non_active_care_residents'
                ],

            'critical_deterioration_risk' =>
                $summary[
                    'historical_critical_deterioration_risk'
                ],

            'high_deterioration_risk' =>
                $summary[
                    'historical_high_deterioration_risk'
                ],

            'medium_deterioration_risk' =>
                $summary[
                    'historical_medium_deterioration_risk'
                ],

            'low_deterioration_risk' =>
                $summary[
                    'historical_low_deterioration_risk'
                ],

            'urgent_clinical_reviews' =>
                $summary[
                    'historical_urgent_clinical_reviews'
                ],

            'worsening_residents' =>
                $summary[
                    'historical_worsening_residents'
                ],

            'excluded_from_current_care_escalation' =>
                true,

            'intelligence_retained' =>
                true,

            'message' =>
                'Predictive intelligence for non-active residents is retained for historical review but excluded from current operational clinical escalation.',
        ];

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

            /*
            |--------------------------------------------------------------------------
            | Active Operational Priority Residents
            |--------------------------------------------------------------------------
            */

            'priority_residents' =>
                $priorityResidents,

            /*
            |--------------------------------------------------------------------------
            | Historical Predictive Priority Residents
            |--------------------------------------------------------------------------
            */

            'historical_priority_residents' =>
                $historicalPriorityResidents,

            /*
            |--------------------------------------------------------------------------
            | Historical Predictive Context
            |--------------------------------------------------------------------------
            */

            'historical_context' =>
                $historicalContext,
        ];
    }
}