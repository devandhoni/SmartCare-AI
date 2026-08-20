<?php

namespace App\Services;

use App\Models\Resident;

class AICareRecommendationAggregator
{
    protected AICareRecommendationEngine $recommendationEngine;
    protected AICareRecommendationLearningEngine $learningEngine;

    public function __construct(
        AICareRecommendationEngine $recommendationEngine,
        AICareRecommendationLearningEngine $learningEngine
    ) {
        $this->recommendationEngine = $recommendationEngine;
        $this->learningEngine = $learningEngine;
    }

    /**
     * Step 52.11A
     *
     * Build facility-level AI care recommendation intelligence.
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
        */

        $summary = [

            'residents_evaluated' => 0,

            'active_care_residents' => 0,
            'non_active_care_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | ACTIVE CARE Priority Distribution
            |--------------------------------------------------------------------------
            */

            'critical_care_residents' => 0,
            'high_care_residents' => 0,
            'medium_care_residents' => 0,
            'routine_care_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | Historical / Non-Active Priority Distribution
            |--------------------------------------------------------------------------
            */

            'non_active_critical_care_residents' => 0,
            'non_active_high_care_residents' => 0,
            'non_active_medium_care_residents' => 0,
            'non_active_routine_care_residents' => 0,

            /*
            |--------------------------------------------------------------------------
            | Operational Action Metrics
            |--------------------------------------------------------------------------
            */

            'immediate_action_residents' => 0,

            'execution_ready_actions' => 0,

            'doctor_review_actions' => 0,

            /*
            |--------------------------------------------------------------------------
            | Evidence Metrics
            |--------------------------------------------------------------------------
            */

            'limited_evidence_cases' => 0,

            'active_care_limited_evidence_cases' => 0,
            'non_active_limited_evidence_cases' => 0,

            /*
            |--------------------------------------------------------------------------
            | Learning Metrics
            |--------------------------------------------------------------------------
            */

            'learning_residents' => 0,

            'evaluated_workflows' => 0,

            'successful_workflows' => 0,

            'partially_successful_workflows' => 0,

            'unsuccessful_workflows' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Aggregation Containers
        |--------------------------------------------------------------------------
        */

        $careDriverCounts = [];

        $recommendationTypeCounts = [];

        $priorityResidents = [];

        $learningResidents = [];

        /*
        |--------------------------------------------------------------------------
        | Analyze Every Resident
        |--------------------------------------------------------------------------
        */

        foreach ($residents as $resident) {

            try {

                $recommendation =
                    $this->recommendationEngine->analyze(
                        (int) $resident->id
                    );

            } catch (\Throwable $e) {

                /*
                 * One invalid resident must not break
                 * the facility-level AI Command Center.
                 */

                continue;
            }

            if (!is_array($recommendation)) {
                continue;
            }

            $summary['residents_evaluated']++;

            /*
            |--------------------------------------------------------------------------
            | Resident Operational Status
            |--------------------------------------------------------------------------
            */

            $activeCareEligible =
                (bool) (
                    $recommendation[
                        'active_care_eligible'
                    ]
                    ?? false
                );

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
            | Care Priority
            |--------------------------------------------------------------------------
            */

            $carePriority =
                strtoupper(
                    (string) (
                        $recommendation[
                            'care_priority'
                        ]
                        ?? 'ROUTINE'
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | ACTIVE CARE Priority Aggregation
            |--------------------------------------------------------------------------
            |
            | Only residents currently eligible for active care contribute
            | to the operational care-priority distribution.
            |
            */

            if ($activeCareEligible) {

                switch ($carePriority) {

                    case 'CRITICAL':

                        $summary[
                            'critical_care_residents'
                        ]++;

                        break;

                    case 'HIGH':

                        $summary[
                            'high_care_residents'
                        ]++;

                        break;

                    case 'MEDIUM':

                        $summary[
                            'medium_care_residents'
                        ]++;

                        break;

                    default:

                        $summary[
                            'routine_care_residents'
                        ]++;

                        break;
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | Historical / Non-Active Priority Aggregation
                |--------------------------------------------------------------------------
                */

                switch ($carePriority) {

                    case 'CRITICAL':

                        $summary[
                            'non_active_critical_care_residents'
                        ]++;

                        break;

                    case 'HIGH':

                        $summary[
                            'non_active_high_care_residents'
                        ]++;

                        break;

                    case 'MEDIUM':

                        $summary[
                            'non_active_medium_care_residents'
                        ]++;

                        break;

                    default:

                        $summary[
                            'non_active_routine_care_residents'
                        ]++;

                        break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Action Timing
            |--------------------------------------------------------------------------
            */

            $actionTiming =
                strtoupper(
                    (string) (
                        $recommendation[
                            'action_timing'
                        ]
                        ?? 'ROUTINE'
                    )
                );

            /*
             * Only active-care residents may create a current operational
             * immediate-action resident count.
             */

            if (
                $activeCareEligible
                &&
                $actionTiming === 'IMMEDIATE'
            ) {

                $summary[
                    'immediate_action_residents'
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Evidence Quality
            |--------------------------------------------------------------------------
            */

            $evidenceQuality =
                strtoupper(
                    (string) (
                        $recommendation[
                            'clinical_context'
                        ]['evidence_quality']
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
            | Primary Care Driver
            |--------------------------------------------------------------------------
            */

            $primaryCareFocus =
                $recommendation[
                    'primary_care_focus'
                ]
                ?? null;

            if ($primaryCareFocus) {

                if (
                    !isset(
                        $careDriverCounts[
                            $primaryCareFocus
                        ]
                    )
                ) {

                    $careDriverCounts[
                        $primaryCareFocus
                    ] = 0;
                }

                /*
                 * Driver distribution remains facility-wide intelligence.
                 * Historical/non-active residents are still retained here.
                 */

                $careDriverCounts[
                    $primaryCareFocus
                ]++;
            }

            /*
            |--------------------------------------------------------------------------
            | Recommendation Actions
            |--------------------------------------------------------------------------
            */

            $recommendedActions =
                $recommendation[
                    'recommended_actions'
                ]
                ?? [];

            foreach (
                $recommendedActions
                as $action
            ) {

                if (!is_array($action)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Recommendation Type Distribution
                |--------------------------------------------------------------------------
                */

                $recommendationCode =
                    $action[
                        'code'
                    ]
                    ?? 'unknown';

                if (
                    !isset(
                        $recommendationTypeCounts[
                            $recommendationCode
                        ]
                    )
                ) {

                    $recommendationTypeCounts[
                        $recommendationCode
                    ] = 0;
                }

                /*
                 * Recommendation type distribution remains facility-wide.
                 */

                $recommendationTypeCounts[
                    $recommendationCode
                ]++;

                /*
                |--------------------------------------------------------------------------
                | Execution Readiness
                |--------------------------------------------------------------------------
                |
                | Execution-ready actions must be operational.
                |
                | Non-active residents may have historical recommendation
                | records but they must not contribute to active workflow
                | readiness metrics.
                |
                */

                $executionReady =
                    (bool) (
                        $action[
                            'execution_ready'
                        ]
                        ?? false
                    );

                if (
                    $activeCareEligible
                    &&
                    $executionReady
                ) {

                    $summary[
                        'execution_ready_actions'
                    ]++;
                }

                /*
                |--------------------------------------------------------------------------
                | Doctor Review Requirements
                |--------------------------------------------------------------------------
                */

                $requiresDoctorReview =
                    (bool) (
                        $action[
                            'requires_doctor_review'
                        ]
                        ?? false
                    );

                if (
                    $activeCareEligible
                    &&
                    $requiresDoctorReview
                ) {

                    $summary[
                        'doctor_review_actions'
                    ]++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Execution Ready Count Per Resident
            |--------------------------------------------------------------------------
            */

            $executionReadyCount = 0;

            foreach (
                $recommendedActions
                as $action
            ) {

                if (
                    is_array($action)
                    &&
                    (
                        $action[
                            'execution_ready'
                        ]
                        ?? false
                    )
                ) {

                    $executionReadyCount++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Priority Resident Detection
            |--------------------------------------------------------------------------
            |
            | Priority residents MUST:
            |
            | - be active-care eligible
            | - have CRITICAL/HIGH care priority
            |   OR immediate action timing
            |
            */

            $isPriority =
                $activeCareEligible
                &&
                (
                    in_array(
                        $carePriority,
                        [
                            'CRITICAL',
                            'HIGH',
                        ],
                        true
                    )
                    ||
                    $actionTiming === 'IMMEDIATE'
                );

            if ($isPriority) {

                $priorityResidents[] = [

                    'resident_id' =>
                        $resident->id,

                    'resident_name' =>
                        $resident->full_name
                        ??
                        $resident->name
                        ??
                        (
                            'Resident '
                            . $resident->id
                        ),

                    'resident_status' =>
                        $resident->status
                        ?? 'UNKNOWN',

                    'operational_care_status' =>
                        $recommendation[
                            'operational_care_status'
                        ]
                        ?? 'UNKNOWN',

                    'active_care_eligible' =>
                        $activeCareEligible,

                    'care_priority' =>
                        $carePriority,

                    'action_timing' =>
                        $actionTiming,

                    'recommendation_confidence' =>
                        $recommendation[
                            'recommendation_confidence'
                        ]
                        ?? 0,

                    'primary_care_focus' =>
                        $primaryCareFocus,

                    'primary_driver_score' =>
                        $recommendation[
                            'primary_driver_score'
                        ]
                        ?? 0,

                    'dominant_care_drivers' =>
                        $recommendation[
                            'dominant_care_drivers'
                        ]
                        ?? [],

                    'execution_ready_actions' =>
                        $executionReadyCount,

                    'total_recommendations' =>
                        count(
                            $recommendedActions
                        ),

                    'evidence_quality' =>
                        $evidenceQuality,

                    'care_summary' =>
                        $recommendation[
                            'care_summary'
                        ]
                        ?? null,

                    'safety_flags' =>
                        $recommendation[
                            'safety_flags'
                        ]
                        ?? [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Recommendation Learning
            |--------------------------------------------------------------------------
            */

            try {

                $learning =
                    $this->learningEngine->analyze(
                        (int) $resident->id
                    );

            } catch (\Throwable $e) {

                $learning = null;
            }

            if (
                is_array($learning)
                &&
                !empty(
                    $learning[
                        'recommendation_learning_summary'
                    ]
                )
            ) {

                $learningSummary =
                    $learning[
                        'recommendation_learning_summary'
                    ];

                $evaluatedWorkflows =
                    (int) (
                        $learningSummary[
                            'total_workflows_evaluated'
                        ]
                        ?? 0
                    );

                if ($evaluatedWorkflows > 0) {

                    $summary[
                        'learning_residents'
                    ]++;

                    $summary[
                        'evaluated_workflows'
                    ] +=
                        $evaluatedWorkflows;

                    $summary[
                        'successful_workflows'
                    ] +=
                        (int) (
                            $learningSummary[
                                'successful_workflows'
                            ]
                            ?? 0
                        );

                    $summary[
                        'partially_successful_workflows'
                    ] +=
                        (int) (
                            $learningSummary[
                                'partially_successful_workflows'
                            ]
                            ?? 0
                        );

                    $summary[
                        'unsuccessful_workflows'
                    ] +=
                        (int) (
                            $learningSummary[
                                'unsuccessful_workflows'
                            ]
                            ?? 0
                        );

                    $learningResidents[] = [

                        'resident_id' =>
                            $resident->id,

                        'resident_name' =>
                            $resident->full_name
                            ??
                            $resident->name
                            ??
                            (
                                'Resident '
                                . $resident->id
                            ),

                        'resident_status' =>
                            $resident->status
                            ?? 'UNKNOWN',

                        'active_care_eligible' =>
                            $activeCareEligible,

                        'learning_status' =>
                            $learning[
                                'learning_status'
                            ]
                            ?? 'UNKNOWN',

                        'workflows_evaluated' =>
                            $evaluatedWorkflows,

                        'overall_success_rate' =>
                            $learningSummary[
                                'overall_success_rate'
                            ]
                            ?? 0,

                        'recommendation_types_evaluated' =>
                            $learningSummary[
                                'recommendation_types_evaluated'
                            ]
                            ?? 0,
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Facility Workflow Success Rate
        |--------------------------------------------------------------------------
        */

        $evaluatedWorkflows =
            $summary[
                'evaluated_workflows'
            ];

        $successfulEquivalent =
            $summary[
                'successful_workflows'
            ]
            +
            (
                $summary[
                    'partially_successful_workflows'
                ]
                * 0.5
            );

        $workflowSuccessRate =
            $evaluatedWorkflows > 0
                ?
                round(
                    (
                        $successfulEquivalent
                        /
                        $evaluatedWorkflows
                    )
                    * 100,
                    2
                )
                :
                0;

        $summary[
            'workflow_success_rate'
        ] =
            $workflowSuccessRate;

        /*
        |--------------------------------------------------------------------------
        | Active Care Distribution Validation
        |--------------------------------------------------------------------------
        |
        | This field makes it easy to verify that:
        |
        | CRITICAL + HIGH + MEDIUM + ROUTINE
        | =
        | ACTIVE CARE RESIDENTS
        |
        */

        $activePriorityDistributionTotal =
            $summary[
                'critical_care_residents'
            ]
            +
            $summary[
                'high_care_residents'
            ]
            +
            $summary[
                'medium_care_residents'
            ]
            +
            $summary[
                'routine_care_residents'
            ];

        $summary[
            'active_priority_distribution_total'
        ] =
            $activePriorityDistributionTotal;

        $summary[
            'active_priority_distribution_valid'
        ] =
            (
                $activePriorityDistributionTotal
                ===
                $summary[
                    'active_care_residents'
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Historical Priority Distribution Total
        |--------------------------------------------------------------------------
        */

        $summary[
            'non_active_priority_distribution_total'
        ] =
            $summary[
                'non_active_critical_care_residents'
            ]
            +
            $summary[
                'non_active_high_care_residents'
            ]
            +
            $summary[
                'non_active_medium_care_residents'
            ]
            +
            $summary[
                'non_active_routine_care_residents'
            ];

        /*
        |--------------------------------------------------------------------------
        | Rank Top Care Drivers
        |--------------------------------------------------------------------------
        */

        arsort(
            $careDriverCounts
        );

        $topCareDrivers = [];

        foreach (
            $careDriverCounts
            as $driver => $count
        ) {

            $topCareDrivers[] = [

                'driver' =>
                    $driver,

                'resident_count' =>
                    $count,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Rank Recommendation Types
        |--------------------------------------------------------------------------
        */

        arsort(
            $recommendationTypeCounts
        );

        $topRecommendationTypes = [];

        foreach (
            $recommendationTypeCounts
            as $code => $count
        ) {

            $topRecommendationTypes[] = [

                'recommendation_code' =>
                    $code,

                'occurrences' =>
                    $count,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Rank Priority Residents
        |--------------------------------------------------------------------------
        */

        $priorityRank = [

            'CRITICAL' => 4,

            'HIGH' => 3,

            'MEDIUM' => 2,

            'ROUTINE' => 1,
        ];

        usort(
            $priorityResidents,
            function (
                array $a,
                array $b
            ) use ($priorityRank) {

                $aPriority =
                    strtoupper(
                        (string) (
                            $a[
                                'care_priority'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                $bPriority =
                    strtoupper(
                        (string) (
                            $b[
                                'care_priority'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                $aRank =
                    $priorityRank[
                        $aPriority
                    ]
                    ?? 0;

                $bRank =
                    $priorityRank[
                        $bPriority
                    ]
                    ?? 0;

                if ($aRank !== $bRank) {

                    return
                        $bRank
                        <=>
                        $aRank;
                }

                /*
                 * Immediate action before
                 * non-immediate action.
                 */

                $aImmediate =
                    strtoupper(
                        (string) (
                            $a[
                                'action_timing'
                            ]
                            ?? ''
                        )
                    ) === 'IMMEDIATE'
                        ? 1
                        : 0;

                $bImmediate =
                    strtoupper(
                        (string) (
                            $b[
                                'action_timing'
                            ]
                            ?? ''
                        )
                    ) === 'IMMEDIATE'
                        ? 1
                        : 0;

                if (
                    $aImmediate
                    !==
                    $bImmediate
                ) {

                    return
                        $bImmediate
                        <=>
                        $aImmediate;
                }

                /*
                 * More execution-ready actions
                 * rank higher.
                 */

                $aExecutionReady =
                    (int) (
                        $a[
                            'execution_ready_actions'
                        ]
                        ?? 0
                    );

                $bExecutionReady =
                    (int) (
                        $b[
                            'execution_ready_actions'
                        ]
                        ?? 0
                    );

                if (
                    $aExecutionReady
                    !==
                    $bExecutionReady
                ) {

                    return
                        $bExecutionReady
                        <=>
                        $aExecutionReady;
                }

                /*
                 * Higher recommendation confidence
                 * as final tie-breaker.
                 */

                return
                    (
                        $b[
                            'recommendation_confidence'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'recommendation_confidence'
                        ]
                        ?? 0
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Facility Command Status
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Current command status uses ACTIVE CARE intelligence only.
        |
        */

        $commandStatus =
            'STABLE';

        if (
            $summary[
                'critical_care_residents'
            ] > 0
            ||
            $summary[
                'immediate_action_residents'
            ] > 0
        ) {

            $commandStatus =
                'CRITICAL';

        } elseif (
            $summary[
                'high_care_residents'
            ] > 0
            ||
            $summary[
                'execution_ready_actions'
            ] > 0
        ) {

            $commandStatus =
                'HIGH ATTENTION';

        } elseif (
            $summary[
                'medium_care_residents'
            ] > 0
        ) {

            $commandStatus =
                'MONITOR';
        }

        /*
        |--------------------------------------------------------------------------
        | Learning Status
        |--------------------------------------------------------------------------
        */

        $facilityLearningStatus =
            'AWAITING OUTCOME DATA';

        if ($evaluatedWorkflows >= 20) {

            $facilityLearningStatus =
                'MATURE LEARNING';

        } elseif ($evaluatedWorkflows >= 5) {

            $facilityLearningStatus =
                'DEVELOPING LEARNING';

        } elseif ($evaluatedWorkflows > 0) {

            $facilityLearningStatus =
                'EARLY LEARNING';
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

            'non_active_critical_care_residents' =>
                $summary[
                    'non_active_critical_care_residents'
                ],

            'non_active_high_care_residents' =>
                $summary[
                    'non_active_high_care_residents'
                ],

            'non_active_medium_care_residents' =>
                $summary[
                    'non_active_medium_care_residents'
                ],

            'non_active_routine_care_residents' =>
                $summary[
                    'non_active_routine_care_residents'
                ],

            'excluded_from_current_care_escalation' =>
                true,

            'intelligence_retained' =>
                true,

            'message' =>
                'Non-active resident recommendation intelligence is retained for historical review but excluded from current operational care escalation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Return Facility Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'command_status' =>
                $commandStatus,

            'summary' =>
                $summary,

            'top_care_drivers' =>
                $topCareDrivers,

            'top_recommendation_types' =>
                $topRecommendationTypes,

            'priority_residents' =>
                $priorityResidents,

            'historical_context' =>
                $historicalContext,

            'recommendation_learning' => [

                'facility_learning_status' =>
                    $facilityLearningStatus,

                'residents_with_learning_data' =>
                    count(
                        $learningResidents
                    ),

                'total_workflows_evaluated' =>
                    $evaluatedWorkflows,

                'workflow_success_rate' =>
                    $workflowSuccessRate,

                'resident_learning' =>
                    $learningResidents,

                /*
                 * Learning remains advisory.
                 */

                'learning_guardrails' => [

                    'automatic_clinical_rule_changes' =>
                        false,

                    'automatic_priority_changes' =>
                        false,

                    'automatic_recommendation_suppression' =>
                        false,

                    'human_validation_required' =>
                        true,

                    'message' =>
                        'Facility recommendation learning is advisory only. Clinical rules, recommendation priorities, and workflow behavior are not automatically modified.',
                ],
            ],
        ];
    }
}