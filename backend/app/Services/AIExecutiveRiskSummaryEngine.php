<?php

namespace App\Services;

class AIExecutiveRiskSummaryEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine
    ) {
        $this->reportEngine = $reportEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 53.2
    | Executive Risk & Priority Summary
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        $report =
            $this->reportEngine->analyze();

        $operationalRisk =
            $report['operational_risk']
            ?? [];

        $priorityResidents =
            $report['priority_residents']
            ?? [];

        $historicalIntelligence =
            $report['historical_intelligence']
            ?? [];

        $careExecution =
            $report['care_execution']
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Active Predictive Priority Residents
        |--------------------------------------------------------------------------
        */

        $predictivePriority =
            $priorityResidents[
                'predictive_priority'
            ]
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Active Care Priority Residents
        |--------------------------------------------------------------------------
        */

        $carePriority =
            $priorityResidents[
                'care_priority'
            ]
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Merge Active Priority Residents
        |--------------------------------------------------------------------------
        |
        | A resident may appear in both predictive and care recommendation
        | intelligence. Executive reporting should display one consolidated
        | resident priority entry instead of duplicates.
        |
        */

        $residentMap = [];

        foreach ($predictivePriority as $resident) {
            if (!is_array($resident)) {
                continue;
            }

            $residentId =
                (int) (
                    $resident['resident_id']
                    ?? 0
                );

            if ($residentId <= 0) {
                continue;
            }

            $residentMap[$residentId] = [

                'resident_id' =>
                    $residentId,

                'resident_name' =>
                    $resident['resident_name']
                    ?? ('Resident ' . $residentId),

                'resident_status' =>
                    $resident['resident_status']
                    ?? 'UNKNOWN',

                'priority_source' => [
                    'PREDICTIVE_INTELLIGENCE',
                ],

                'clinical_severity' =>
                    $resident['clinical_severity']
                    ?? 'UNKNOWN',

                'deterioration_risk' =>
                    $resident['deterioration_risk']
                    ?? 'UNKNOWN',

                'care_priority' =>
                    null,

                'risk_score' =>
                    $resident['risk_score']
                    ?? 0,

                'primary_driver' =>
                    $resident['primary_driver']
                    ?? null,

                'trend_direction' =>
                    $resident['trend_direction']
                    ?? 'UNKNOWN',

                'action_timing' =>
                    $resident['clinical_action_timing']
                    ?? 'ROUTINE',

                'execution_ready_actions' =>
                    0,

                'doctor_review_required' =>
                    false,

                'evidence_quality' =>
                    $resident['evidence_quality']
                    ?? 'UNKNOWN',

                'safety_flags' =>
                    [],
            ];
        }

        foreach ($carePriority as $resident) {
            if (!is_array($resident)) {
                continue;
            }

            $residentId =
                (int) (
                    $resident['resident_id']
                    ?? 0
                );

            if ($residentId <= 0) {
                continue;
            }

            if (!isset($residentMap[$residentId])) {
                $residentMap[$residentId] = [

                    'resident_id' =>
                        $residentId,

                    'resident_name' =>
                        $resident['resident_name']
                        ?? ('Resident ' . $residentId),

                    'resident_status' =>
                        $resident['resident_status']
                        ?? 'UNKNOWN',

                    'priority_source' =>
                        [],

                    'clinical_severity' =>
                        null,

                    'deterioration_risk' =>
                        null,

                    'care_priority' =>
                        null,

                    'risk_score' =>
                        0,

                    'primary_driver' =>
                        null,

                    'trend_direction' =>
                        null,

                    'action_timing' =>
                        null,

                    'execution_ready_actions' =>
                        0,

                    'doctor_review_required' =>
                        false,

                    'evidence_quality' =>
                        'UNKNOWN',

                    'safety_flags' =>
                        [],
                ];
            }

            $residentMap[
                $residentId
            ]['priority_source'][] =
                'CARE_RECOMMENDATION_INTELLIGENCE';

            $residentMap[
                $residentId
            ]['care_priority'] =
                $resident['care_priority']
                ?? null;

            $residentMap[
                $residentId
            ]['primary_driver'] =
                $resident['primary_care_focus']
                ??
                $residentMap[
                    $residentId
                ]['primary_driver'];

            $residentMap[
                $residentId
            ]['action_timing'] =
                $resident['action_timing']
                ??
                $residentMap[
                    $residentId
                ]['action_timing'];

            $residentMap[
                $residentId
            ]['execution_ready_actions'] =
                (int) (
                    $resident[
                        'execution_ready_actions'
                    ]
                    ?? 0
                );

            $residentMap[
                $residentId
            ]['evidence_quality'] =
                $resident['evidence_quality']
                ??
                $residentMap[
                    $residentId
                ]['evidence_quality'];

            $residentMap[
                $residentId
            ]['safety_flags'] =
                $resident['safety_flags']
                ?? [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Priority Sources
        |--------------------------------------------------------------------------
        */

        foreach ($residentMap as &$resident) {
            $resident['priority_source'] =
                array_values(
                    array_unique(
                        $resident['priority_source']
                    )
                );
        }

        unset($resident);

        /*
        |--------------------------------------------------------------------------
        | Executive Priority Ranking
        |--------------------------------------------------------------------------
        */

        $activePriorityResidents =
            array_values(
                $residentMap
            );

        usort(
            $activePriorityResidents,
            function (
                array $a,
                array $b
            ) {
                $riskRank = [
                    'CRITICAL' => 4,
                    'HIGH' => 3,
                    'MEDIUM' => 2,
                    'LOW' => 1,
                    'UNKNOWN' => 0,
                ];

                $careRank = [
                    'CRITICAL' => 4,
                    'HIGH' => 3,
                    'MEDIUM' => 2,
                    'ROUTINE' => 1,
                ];

                $aRisk =
                    $riskRank[
                        strtoupper(
                            (string) (
                                $a[
                                    'deterioration_risk'
                                ]
                                ?? 'UNKNOWN'
                            )
                        )
                    ]
                    ?? 0;

                $bRisk =
                    $riskRank[
                        strtoupper(
                            (string) (
                                $b[
                                    'deterioration_risk'
                                ]
                                ?? 'UNKNOWN'
                            )
                        )
                    ]
                    ?? 0;

                if ($aRisk !== $bRisk) {
                    return $bRisk <=> $aRisk;
                }

                $aCare =
                    $careRank[
                        strtoupper(
                            (string) (
                                $a['care_priority']
                                ?? 'ROUTINE'
                            )
                        )
                    ]
                    ?? 0;

                $bCare =
                    $careRank[
                        strtoupper(
                            (string) (
                                $b['care_priority']
                                ?? 'ROUTINE'
                            )
                        )
                    ]
                    ?? 0;

                if ($aCare !== $bCare) {
                    return $bCare <=> $aCare;
                }

                return
                    (
                        $b['risk_score']
                        ?? 0
                    )
                    <=>
                    (
                        $a['risk_score']
                        ?? 0
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Historical Priority Context
        |--------------------------------------------------------------------------
        */

        $historicalPriority =
            $historicalIntelligence[
                'historical_predictive_priority'
            ]
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Executive Attention Level
        |--------------------------------------------------------------------------
        */

        $attentionLevel =
            'ROUTINE';

        if (
            (
                $operationalRisk[
                    'active_critical_cases'
                ]
                ?? 0
            ) > 0
            ||
            (
                $operationalRisk[
                    'predictive_priority_residents'
                ]
                ?? 0
            ) > 0
        ) {
            $attentionLevel =
                'CRITICAL';
        } elseif (
            (
                $operationalRisk[
                    'active_care_alerts'
                ]
                ?? 0
            ) > 0
        ) {
            $attentionLevel =
                'HIGH ATTENTION';
        }

        /*
        |--------------------------------------------------------------------------
        | Executive Risk Message
        |--------------------------------------------------------------------------
        */

        $riskMessage =
            'Current operational care risk is stable.';

        if ($attentionLevel === 'CRITICAL') {
            $riskMessage =
                'Current active-care intelligence requires executive attention due to active critical clinical risk.';
        } elseif ($attentionLevel === 'HIGH ATTENTION') {
            $riskMessage =
                'Active-care alerts require management attention, although no active critical resident is currently identified.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return Risk Summary
        |--------------------------------------------------------------------------
        */

        return [

            'attention_level' =>
                $attentionLevel,

            'risk_message' =>
                $riskMessage,

            'operational_counts' => [

                'active_critical_cases' =>
                    $operationalRisk[
                        'active_critical_cases'
                    ]
                    ?? 0,

                'active_care_alerts' =>
                    $operationalRisk[
                        'active_care_alerts'
                    ]
                    ?? 0,

                'predictive_priority_residents' =>
                    $operationalRisk[
                        'predictive_priority_residents'
                    ]
                    ?? 0,

                'care_priority_residents' =>
                    $operationalRisk[
                        'care_priority_residents'
                    ]
                    ?? 0,

                'execution_ready_actions' =>
                    $careExecution[
                        'execution_ready_actions'
                    ]
                    ?? 0,

                'doctor_review_actions' =>
                    $careExecution[
                        'doctor_review_actions'
                    ]
                    ?? 0,
            ],

            'active_priority_residents' =>
                $activePriorityResidents,

            'historical_priority_residents' =>
                $historicalPriority,

            'historical_context' => [

                'historical_critical_cases' =>
                    $historicalIntelligence[
                        'historical_critical_cases'
                    ]
                    ?? 0,

                'historical_open_alerts' =>
                    $historicalIntelligence[
                        'historical_open_alerts'
                    ]
                    ?? 0,

                'excluded_from_current_escalation' =>
                    true,
            ],

            'guardrails' => [

                'active_and_historical_separated' =>
                    true,

                'automatic_escalation' =>
                    false,

                'human_review_required' =>
                    true,
            ],
        ];
    }
}