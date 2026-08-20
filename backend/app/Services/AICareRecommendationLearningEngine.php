<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\NurseTask;

class AICareRecommendationLearningEngine
{
    protected AICareWorkflowOutcomeIntelligence $workflowOutcomeIntelligence;

    public function __construct(
        AICareWorkflowOutcomeIntelligence $workflowOutcomeIntelligence
    ) {
        $this->workflowOutcomeIntelligence =
            $workflowOutcomeIntelligence;
    }

    /**
     * --------------------------------------------------------------------------
     * Step 52.10
     * AI Care Recommendation Effectiveness Learning
     * --------------------------------------------------------------------------
     *
     * Learns how effective AI care recommendation types have been based on:
     *
     * - AI generated workflows
     * - completed operational tasks
     * - recorded clinical outcomes
     * - workflow effectiveness
     *
     * IMPORTANT:
     *
     * This service does NOT automatically modify clinical recommendations.
     * It only produces learning intelligence.
     *
     * Human-reviewed clinical safety remains the source of truth.
     */
    public function analyze(int $residentId): array
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Resident
        |--------------------------------------------------------------------------
        */

        $resident =
            Resident::find($residentId);

        if (!$resident) {
            return [
                'resident_id' => $residentId,
                'learning_status' => 'RESIDENT_NOT_FOUND',
                'recommendation_learning_summary' => [
                    'recommendation_types_evaluated' => 0,
                    'total_workflows_evaluated' => 0,
                    'successful_workflows' => 0,
                    'partially_successful_workflows' => 0,
                    'unsuccessful_workflows' => 0,
                    'overall_success_rate' => 0,
                ],
                'recommendation_performance' => [],
                'learning_insights' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 52.9 Outcome Intelligence
        |--------------------------------------------------------------------------
        */

        $outcomeIntelligence =
            $this->workflowOutcomeIntelligence
                ->analyze($residentId);

        $workflowOutcomes =
            $outcomeIntelligence[
                'workflow_outcomes'
            ] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Learning Containers
        |--------------------------------------------------------------------------
        */

        $recommendationPerformance = [];

        $totalEvaluated = 0;

        $totalSuccessful = 0;

        $totalPartial = 0;

        $totalUnsuccessful = 0;

        /*
        |--------------------------------------------------------------------------
        | Analyze Each Evaluated Workflow
        |--------------------------------------------------------------------------
        */

        foreach ($workflowOutcomes as $workflow) {

            $outcome =
                $workflow[
                    'outcome_intelligence'
                ] ?? [];

            $evaluated =
                (bool) (
                    $outcome['evaluated']
                    ?? false
                );

            /*
             * Ignore workflows without clinical outcome evaluation.
             */
            if (!$evaluated) {
                continue;
            }

            $recommendationCode =
                $workflow[
                    'source_recommendation_code'
                ]
                ?? 'unknown_recommendation';

            $effectiveness =
                strtoupper(
                    (string) (
                        $outcome['effectiveness']
                        ?? 'UNKNOWN'
                    )
                );

            $effectivenessScore =
                (float) (
                    $outcome[
                        'effectiveness_score'
                    ]
                    ?? 0
                );

            /*
            |--------------------------------------------------------------------------
            | Initialize Recommendation Type
            |--------------------------------------------------------------------------
            */

            if (
                !isset(
                    $recommendationPerformance[
                        $recommendationCode
                    ]
                )
            ) {
                $recommendationPerformance[
                    $recommendationCode
                ] = [

                    'recommendation_code' =>
                        $recommendationCode,

                    'workflows_evaluated' => 0,

                    'successful' => 0,

                    'partially_successful' => 0,

                    'unsuccessful' => 0,

                    'unknown' => 0,

                    'effectiveness_score_total' => 0,

                    'effectiveness_scores' => [],

                    'workflow_examples' => [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Count Workflow
            |--------------------------------------------------------------------------
            */

            $recommendationPerformance[
                $recommendationCode
            ]['workflows_evaluated']++;

            $recommendationPerformance[
                $recommendationCode
            ]['effectiveness_score_total'] +=
                $effectivenessScore;

            $recommendationPerformance[
                $recommendationCode
            ]['effectiveness_scores'][] =
                $effectivenessScore;

            $totalEvaluated++;

            /*
            |--------------------------------------------------------------------------
            | Outcome Classification
            |--------------------------------------------------------------------------
            */

            switch ($effectiveness) {

                case 'SUCCESSFUL':

                    $recommendationPerformance[
                        $recommendationCode
                    ]['successful']++;

                    $totalSuccessful++;

                    break;

                case 'PARTIALLY_SUCCESSFUL':

                    $recommendationPerformance[
                        $recommendationCode
                    ]['partially_successful']++;

                    $totalPartial++;

                    break;

                case 'UNSUCCESSFUL':

                    $recommendationPerformance[
                        $recommendationCode
                    ]['unsuccessful']++;

                    $totalUnsuccessful++;

                    break;

                default:

                    $recommendationPerformance[
                        $recommendationCode
                    ]['unknown']++;

                    break;
            }

            /*
            |--------------------------------------------------------------------------
            | Store Limited Workflow Example
            |--------------------------------------------------------------------------
            |
            | Keep learning output compact.
            |
            */

            if (
                count(
                    $recommendationPerformance[
                        $recommendationCode
                    ]['workflow_examples']
                ) < 5
            ) {
                $recommendationPerformance[
                    $recommendationCode
                ]['workflow_examples'][] = [

                    'proposal_id' =>
                        $workflow[
                            'proposal_id'
                        ] ?? null,

                    'task_id' =>
                        $workflow[
                            'task_id'
                        ] ?? null,

                    'effectiveness' =>
                        $effectiveness,

                    'effectiveness_score' =>
                        $effectivenessScore,

                    'clinical_outcome' =>
                        $workflow[
                            'clinical_outcome'
                        ]['status']
                        ?? null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Recommendation Performance
        |--------------------------------------------------------------------------
        */

        $finalPerformance = [];

        foreach (
            $recommendationPerformance
            as $recommendationCode => $performance
        ) {

            $evaluated =
                $performance[
                    'workflows_evaluated'
                ];

            $successful =
                $performance[
                    'successful'
                ];

            $partial =
                $performance[
                    'partially_successful'
                ];

            /*
             * Success rate gives partial success half credit.
             */
            $weightedSuccess =
                $successful
                +
                ($partial * 0.5);

            $successRate =
                $evaluated > 0
                ?
                round(
                    (
                        $weightedSuccess
                        /
                        $evaluated
                    ) * 100,
                    2
                )
                :
                0;

            /*
             * Average effectiveness score.
             */
            $averageEffectivenessScore =
                $evaluated > 0
                ?
                round(
                    $performance[
                        'effectiveness_score_total'
                    ]
                    /
                    $evaluated,
                    2
                )
                :
                0;

            /*
             * Confidence in learning depends heavily on sample size.
             *
             * We intentionally avoid claiming strong learning from
             * only one or two workflows.
             */
            $learningConfidence =
                $this->determineLearningConfidence(
                    $evaluated
                );

            $performanceLevel =
                $this->determinePerformanceLevel(
                    $successRate,
                    $evaluated
                );

            $finalPerformance[] = [

                'recommendation_code' =>
                    $recommendationCode,

                'workflows_evaluated' =>
                    $evaluated,

                'successful' =>
                    $successful,

                'partially_successful' =>
                    $partial,

                'unsuccessful' =>
                    $performance[
                        'unsuccessful'
                    ],

                'unknown' =>
                    $performance[
                        'unknown'
                    ],

                'success_rate' =>
                    $successRate,

                'average_effectiveness_score' =>
                    $averageEffectivenessScore,

                'performance_level' =>
                    $performanceLevel,

                'learning_confidence' =>
                    $learningConfidence,

                'workflow_examples' =>
                    $performance[
                        'workflow_examples'
                    ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Best Performing Recommendation Types First
        |--------------------------------------------------------------------------
        */

        usort(
            $finalPerformance,
            function (
                array $a,
                array $b
            ) {

                if (
                    $a[
                        'success_rate'
                    ]
                    !==
                    $b[
                        'success_rate'
                    ]
                ) {
                    return
                        $b[
                            'success_rate'
                        ]
                        <=>
                        $a[
                            'success_rate'
                        ];
                }

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
                        'workflows_evaluated'
                    ]
                    <=>
                    $a[
                        'workflows_evaluated'
                    ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Overall Success Rate
        |--------------------------------------------------------------------------
        */

        $weightedOverallSuccessful =
            $totalSuccessful
            +
            ($totalPartial * 0.5);

        $overallSuccessRate =
            $totalEvaluated > 0
            ?
            round(
                (
                    $weightedOverallSuccessful
                    /
                    $totalEvaluated
                ) * 100,
                2
            )
            :
            0;

        /*
        |--------------------------------------------------------------------------
        | Learning Status
        |--------------------------------------------------------------------------
        */

        $learningStatus =
            $this->determineSystemLearningStatus(
                $totalEvaluated
            );

        /*
        |--------------------------------------------------------------------------
        | Generate Learning Insights
        |--------------------------------------------------------------------------
        */

        $learningInsights =
            $this->generateLearningInsights(
                $finalPerformance,
                $totalEvaluated,
                $overallSuccessRate
            );

        /*
        |--------------------------------------------------------------------------
        | Return Step 52.10 Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'resident_id' =>
                $residentId,

            'resident_name' =>
                $resident->full_name
                ?? ('Resident ' . $residentId),

            'learning_status' =>
                $learningStatus,

            'recommendation_learning_summary' => [

                'recommendation_types_evaluated' =>
                    count(
                        $finalPerformance
                    ),

                'total_workflows_evaluated' =>
                    $totalEvaluated,

                'successful_workflows' =>
                    $totalSuccessful,

                'partially_successful_workflows' =>
                    $totalPartial,

                'unsuccessful_workflows' =>
                    $totalUnsuccessful,

                'overall_success_rate' =>
                    $overallSuccessRate,
            ],

            'recommendation_performance' =>
                $finalPerformance,

            'learning_insights' =>
                $learningInsights,

            /*
            |--------------------------------------------------------------------------
            | Safety Guardrails
            |--------------------------------------------------------------------------
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
                    'Recommendation effectiveness learning is advisory only. Clinical rules and workflow priorities are not automatically modified.',
            ],
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * Learning Confidence
     * --------------------------------------------------------------------------
     */
    protected function determineLearningConfidence(
        int $evaluatedCases
    ): string {

        if ($evaluatedCases >= 20) {
            return 'HIGH';
        }

        if ($evaluatedCases >= 10) {
            return 'MODERATE';
        }

        if ($evaluatedCases >= 5) {
            return 'LIMITED';
        }

        return 'VERY LIMITED';
    }

    /**
     * --------------------------------------------------------------------------
     * Recommendation Performance Level
     * --------------------------------------------------------------------------
     */
    protected function determinePerformanceLevel(
        float $successRate,
        int $evaluatedCases
    ): string {

        /*
         * Do not claim strong performance when sample size
         * is too small.
         */

        if ($evaluatedCases < 3) {
            return 'INSUFFICIENT DATA';
        }

        if ($successRate >= 90) {
            return 'EXCELLENT';
        }

        if ($successRate >= 75) {
            return 'HIGH PERFORMANCE';
        }

        if ($successRate >= 60) {
            return 'MODERATE PERFORMANCE';
        }

        if ($successRate >= 40) {
            return 'LOW PERFORMANCE';
        }

        return 'REQUIRES REVIEW';
    }

    /**
     * --------------------------------------------------------------------------
     * Overall Learning Status
     * --------------------------------------------------------------------------
     */
    protected function determineSystemLearningStatus(
        int $evaluatedCases
    ): string {

        if ($evaluatedCases === 0) {
            return 'AWAITING OUTCOME DATA';
        }

        if ($evaluatedCases < 5) {
            return 'EARLY LEARNING';
        }

        if ($evaluatedCases < 10) {
            return 'DEVELOPING';
        }

        if ($evaluatedCases < 20) {
            return 'ESTABLISHING PATTERNS';
        }

        return 'ACTIVE LEARNING';
    }

    /**
     * --------------------------------------------------------------------------
     * Learning Insights
     * --------------------------------------------------------------------------
     */
    protected function generateLearningInsights(
        array $performance,
        int $totalEvaluated,
        float $overallSuccessRate
    ): array {

        $insights = [];

        if ($totalEvaluated === 0) {

            return [
                'No completed AI care workflows with recorded clinical outcomes are currently available for recommendation learning.',
            ];
        }

        /*
         * Overall observation.
         */
        $insights[] =
            'AI care recommendation learning currently includes '
            . $totalEvaluated
            . ' evaluated workflow(s) with an overall weighted success rate of '
            . $overallSuccessRate
            . '%.';

        /*
         * Avoid over-learning from small samples.
         */
        if ($totalEvaluated < 5) {

            $insights[] =
                'Current recommendation learning remains preliminary because fewer than 5 workflows have been evaluated.';

        }

        /*
         * Best performer.
         */
        if (!empty($performance)) {

            $best =
                $performance[0];

            $insights[] =
                'Current highest-performing recommendation type is '
                . $best['recommendation_code']
                . ' with a '
                . $best['success_rate']
                . '% weighted success rate across '
                . $best['workflows_evaluated']
                . ' evaluated workflow(s).';
        }

        /*
         * Look for poor performers only when adequate sample exists.
         */
        foreach ($performance as $item) {

            if (
                $item['workflows_evaluated'] >= 3
                &&
                $item['success_rate'] < 50
            ) {
                $insights[] =
                    'Recommendation type '
                    . $item['recommendation_code']
                    . ' has a success rate below 50% and should be reviewed before any future model or rule adjustment.';
            }
        }

        return array_values(
            array_unique(
                $insights
            )
        );
    }
}