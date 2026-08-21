<?php

namespace App\Services;

class AILearningPatternIntelligenceEngine
{
    protected AIPredictionPerformanceLearningEngine $predictionLearning;

    protected AICareRecommendationPerformanceLearningEngine $recommendationLearning;

    protected AIWorkflowEffectivenessLearningEngine $workflowLearning;

    protected AIConfidenceCalibrationIntelligenceEngine $calibrationIntelligence;


    public function __construct(
        AIPredictionPerformanceLearningEngine $predictionLearning,
        AICareRecommendationPerformanceLearningEngine $recommendationLearning,
        AIWorkflowEffectivenessLearningEngine $workflowLearning,
        AIConfidenceCalibrationIntelligenceEngine $calibrationIntelligence
    ) {
        $this->predictionLearning =
            $predictionLearning;

        $this->recommendationLearning =
            $recommendationLearning;

        $this->workflowLearning =
            $workflowLearning;

        $this->calibrationIntelligence =
            $calibrationIntelligence;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 54.7
    | AI Learning Pattern Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Source Learning Intelligence
        |--------------------------------------------------------------------------
        */

        $prediction =
            $this->predictionLearning->analyze(
                $residentId
            );

        $recommendation =
            $this->recommendationLearning->analyze(
                $residentId
            );

        $workflow =
            $this->workflowLearning->analyze(
                $residentId
            );

        $calibration =
            $this->calibrationIntelligence->analyze(
                $residentId
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Extract Evidence Counts
        |--------------------------------------------------------------------------
        */

        $predictionCount =
            (int) (
                $prediction[
                    'prediction_learning_summary'
                ]['evaluated_evidence_count']
                ?? 0
            );

        $recommendationCount =
            (int) (
                $recommendation[
                    'recommendation_learning_summary'
                ]['evaluated_evidence_count']
                ?? 0
            );

        $workflowCount =
            (int) (
                $workflow[
                    'workflow_learning_summary'
                ]['evaluated_evidence_count']
                ?? 0
            );

        $totalEvidence =
            $predictionCount
            +
            $recommendationCount
            +
            $workflowCount;


        /*
        |--------------------------------------------------------------------------
        | 3. Core Performance Signals
        |--------------------------------------------------------------------------
        */

        $predictionAccuracy =
            (float) (
                $prediction[
                    'prediction_learning_summary'
                ]['average_accuracy_score']
                ?? 0
            );

        $predictionHumanAgreement =
            (float) (
                $prediction[
                    'prediction_learning_summary'
                ]['human_agreement_rate']
                ?? 0
            );

        $recommendationEffectiveness =
            (float) (
                $recommendation[
                    'recommendation_learning_summary'
                ]['average_effectiveness_score']
                ?? 0
            );

        $recommendationHumanAgreement =
            (float) (
                $recommendation[
                    'recommendation_learning_summary'
                ]['human_agreement_rate']
                ?? 0
            );

        $workflowEffectiveness =
            (float) (
                $workflow[
                    'workflow_learning_summary'
                ]['average_effectiveness_score']
                ?? 0
            );

        $workflowCompletionRate =
            (float) (
                $workflow[
                    'workflow_learning_summary'
                ]['workflow_completion_rate']
                ?? 0
            );

        $workflowHumanAgreement =
            (float) (
                $workflow[
                    'workflow_learning_summary'
                ]['human_agreement_rate']
                ?? 0
            );

        $overallCalibration =
            strtoupper(
                (string) (
                    $calibration[
                        'calibration_summary'
                    ]['overall_calibration']
                    ?? 'UNKNOWN'
                )
            );

        $confidenceGap =
            (float) (
                $calibration[
                    'calibration_summary'
                ]['average_confidence_gap']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | 4. Pattern Containers
        |--------------------------------------------------------------------------
        */

        $patterns = [];

        $positivePatterns = [];

        $cautionPatterns = [];

        $dataMaturityPatterns = [];


        /*
        |--------------------------------------------------------------------------
        | 5. Confidence Calibration Pattern
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $overallCalibration,
                [
                    'UNDER_CONFIDENT',
                    'SLIGHTLY_UNDER_CONFIDENT',
                ],
                true
            )
        ) {
            $pattern = [

                'pattern_code' =>
                    'CONSERVATIVE_CONFIDENCE',

                'category' =>
                    'CALIBRATION',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'ADVISORY',

                'evidence_count' =>
                    (int) (
                        $calibration[
                            'calibration_summary'
                        ]['evaluated_evidence_count']
                        ?? 0
                    ),

                'description' =>
                    'Observed accuracy is currently higher than AI confidence, indicating conservative confidence calibration.',

                'supporting_metrics' => [

                    'overall_calibration' =>
                        $overallCalibration,

                    'confidence_accuracy_gap' =>
                        $confidenceGap,
                ],
            ];

            $patterns[] =
                $pattern;

            $positivePatterns[] =
                $pattern;
        }

        if (
            in_array(
                $overallCalibration,
                [
                    'OVER_CONFIDENT',
                    'SLIGHTLY_OVER_CONFIDENT',
                ],
                true
            )
        ) {
            $pattern = [

                'pattern_code' =>
                    'POTENTIAL_OVERCONFIDENCE',

                'category' =>
                    'CALIBRATION',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    (int) (
                        $calibration[
                            'calibration_summary'
                        ]['evaluated_evidence_count']
                        ?? 0
                    ),

                'description' =>
                    'AI confidence is currently higher than observed accuracy.',

                'supporting_metrics' => [

                    'overall_calibration' =>
                        $overallCalibration,

                    'confidence_accuracy_gap' =>
                        $confidenceGap,
                ],
            ];

            $patterns[] =
                $pattern;

            $cautionPatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Prediction Human Agreement Pattern
        |--------------------------------------------------------------------------
        */

        if (
            $predictionCount > 0
            &&
            $predictionHumanAgreement >= 90
        ) {
            $pattern = [

                'pattern_code' =>
                    'HIGH_PREDICTION_HUMAN_AGREEMENT',

                'category' =>
                    'PREDICTION',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'POSITIVE',

                'evidence_count' =>
                    $predictionCount,

                'description' =>
                    'Human reviewers show high agreement with evaluated predictive intelligence.',

                'supporting_metrics' => [

                    'human_agreement_rate' =>
                        $predictionHumanAgreement,

                    'average_accuracy_score' =>
                        $predictionAccuracy,
                ],
            ];

            $patterns[] =
                $pattern;

            $positivePatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Recommendation Effectiveness Pattern
        |--------------------------------------------------------------------------
        */

        if (
            $recommendationCount > 0
            &&
            $recommendationEffectiveness >= 90
            &&
            $recommendationHumanAgreement >= 90
        ) {
            $pattern = [

                'pattern_code' =>
                    'STRONG_RECOMMENDATION_OUTCOME_SIGNAL',

                'category' =>
                    'CARE_RECOMMENDATION',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'POSITIVE',

                'evidence_count' =>
                    $recommendationCount,

                'description' =>
                    'Evaluated care recommendations currently show strong effectiveness and human agreement.',

                'supporting_metrics' => [

                    'average_effectiveness_score' =>
                        $recommendationEffectiveness,

                    'human_agreement_rate' =>
                        $recommendationHumanAgreement,
                ],
            ];

            $patterns[] =
                $pattern;

            $positivePatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Workflow Effectiveness Pattern
        |--------------------------------------------------------------------------
        */

        if (
            $workflowCount > 0
            &&
            $workflowEffectiveness >= 90
            &&
            $workflowCompletionRate >= 90
        ) {
            $pattern = [

                'pattern_code' =>
                    'STRONG_WORKFLOW_EXECUTION_SIGNAL',

                'category' =>
                    'CARE_WORKFLOW',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'POSITIVE',

                'evidence_count' =>
                    $workflowCount,

                'description' =>
                    'Evaluated AI-supported workflows currently show strong completion and effectiveness.',

                'supporting_metrics' => [

                    'average_effectiveness_score' =>
                        $workflowEffectiveness,

                    'workflow_completion_rate' =>
                        $workflowCompletionRate,

                    'human_agreement_rate' =>
                        $workflowHumanAgreement,
                ],
            ];

            $patterns[] =
                $pattern;

            $positivePatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Weak Performance Signals
        |--------------------------------------------------------------------------
        */

        if (
            $recommendationCount > 0
            &&
            $recommendationEffectiveness < 60
        ) {
            $pattern = [

                'pattern_code' =>
                    'LOW_RECOMMENDATION_EFFECTIVENESS',

                'category' =>
                    'CARE_RECOMMENDATION',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    $recommendationCount,

                'description' =>
                    'Observed recommendation effectiveness is currently below the preferred learning range.',

                'supporting_metrics' => [

                    'average_effectiveness_score' =>
                        $recommendationEffectiveness,
                ],
            ];

            $patterns[] =
                $pattern;

            $cautionPatterns[] =
                $pattern;
        }

        if (
            $workflowCount > 0
            &&
            $workflowCompletionRate < 70
        ) {
            $pattern = [

                'pattern_code' =>
                    'LOW_WORKFLOW_COMPLETION',

                'category' =>
                    'CARE_WORKFLOW',

                'pattern_status' =>
                    'OBSERVED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    $workflowCount,

                'description' =>
                    'AI-supported workflow completion is currently below the preferred operational learning range.',

                'supporting_metrics' => [

                    'workflow_completion_rate' =>
                        $workflowCompletionRate,
                ],
            ];

            $patterns[] =
                $pattern;

            $cautionPatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Data Maturity Patterns
        |--------------------------------------------------------------------------
        */

        if ($predictionCount < 5) {

            $pattern = [

                'pattern_code' =>
                    'LIMITED_PREDICTION_EVIDENCE',

                'category' =>
                    'DATA_MATURITY',

                'pattern_status' =>
                    'LIMITED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    $predictionCount,

                'description' =>
                    'Prediction learning evidence remains below the minimum sample preferred for performance conclusions.',
            ];

            $patterns[] =
                $pattern;

            $dataMaturityPatterns[] =
                $pattern;
        }

        if ($recommendationCount < 5) {

            $pattern = [

                'pattern_code' =>
                    'LIMITED_RECOMMENDATION_EVIDENCE',

                'category' =>
                    'DATA_MATURITY',

                'pattern_status' =>
                    'LIMITED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    $recommendationCount,

                'description' =>
                    'Care recommendation learning evidence remains below the minimum sample preferred for performance conclusions.',
            ];

            $patterns[] =
                $pattern;

            $dataMaturityPatterns[] =
                $pattern;
        }

        if ($workflowCount < 5) {

            $pattern = [

                'pattern_code' =>
                    'LIMITED_WORKFLOW_EVIDENCE',

                'category' =>
                    'DATA_MATURITY',

                'pattern_status' =>
                    'LIMITED',

                'severity' =>
                    'CAUTION',

                'evidence_count' =>
                    $workflowCount,

                'description' =>
                    'Workflow learning evidence remains below the minimum sample preferred for performance conclusions.',
            ];

            $patterns[] =
                $pattern;

            $dataMaturityPatterns[] =
                $pattern;
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Overall Learning Maturity
        |--------------------------------------------------------------------------
        */

        $learningMaturity =
            match (true) {

                $totalEvidence >= 60 =>
                    'MATURE LEARNING',

                $totalEvidence >= 15 =>
                    'DEVELOPING LEARNING',

                $totalEvidence > 0 =>
                    'EARLY LEARNING',

                default =>
                    'NO LEARNING DATA',
            };


        /*
        |--------------------------------------------------------------------------
        | 12. Pattern Confidence
        |--------------------------------------------------------------------------
        */

        $patternConfidence =
            match (true) {

                $totalEvidence >= 60 =>
                    'HIGHER',

                $totalEvidence >= 30 =>
                    'MODERATE',

                $totalEvidence >= 15 =>
                    'LIMITED',

                $totalEvidence > 0 =>
                    'VERY LIMITED',

                default =>
                    'NONE',
            };


        /*
        |--------------------------------------------------------------------------
        | 13. Learning Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            'AI learning pattern intelligence currently includes '
            . $totalEvidence
            . ' evaluated evidence record(s) across prediction, recommendation, and workflow domains.';

        if (!empty($positivePatterns)) {
            $findings[] =
                count(
                    $positivePatterns
                )
                . ' positive learning pattern(s) are currently observable.';
        }

        if (!empty($cautionPatterns)) {
            $findings[] =
                count(
                    $cautionPatterns
                )
                . ' caution pattern(s) require continued observation.';
        }

        if (!empty($dataMaturityPatterns)) {
            $findings[] =
                'Learning maturity remains limited because one or more domains have fewer than 5 evaluated evidence records.';
        }

        if (
            $overallCalibration ===
            'UNDER_CONFIDENT'
        ) {
            $findings[] =
                'Current calibration evidence suggests AI confidence is conservative relative to observed performance.';
        }


        /*
        |--------------------------------------------------------------------------
        | 14. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'pattern_status' =>
                $totalEvidence > 0
                ?
                'PATTERNS_AVAILABLE'
                :
                'NO_PATTERNS',

            'resident_id' =>
                $residentId,

            'learning_maturity' =>
                $learningMaturity,

            'pattern_confidence' =>
                $patternConfidence,

            'learning_evidence_summary' => [

                'total_evaluated_evidence' =>
                    $totalEvidence,

                'prediction_evidence' =>
                    $predictionCount,

                'recommendation_evidence' =>
                    $recommendationCount,

                'workflow_evidence' =>
                    $workflowCount,
            ],

            'pattern_summary' => [

                'total_patterns' =>
                    count(
                        $patterns
                    ),

                'positive_patterns' =>
                    count(
                        $positivePatterns
                    ),

                'caution_patterns' =>
                    count(
                        $cautionPatterns
                    ),

                'data_maturity_patterns' =>
                    count(
                        $dataMaturityPatterns
                    ),
            ],

            'patterns' =>
                $patterns,

            'positive_patterns' =>
                $positivePatterns,

            'caution_patterns' =>
                $cautionPatterns,

            'data_maturity_patterns' =>
                $dataMaturityPatterns,

            'learning_findings' =>
                $findings,

            'source_learning_status' => [

                'prediction_learning_status' =>
                    $prediction[
                        'learning_status'
                    ]
                    ?? 'UNKNOWN',

                'recommendation_learning_status' =>
                    $recommendation[
                        'learning_status'
                    ]
                    ?? 'UNKNOWN',

                'workflow_learning_status' =>
                    $workflow[
                        'learning_status'
                    ]
                    ?? 'UNKNOWN',

                'calibration_learning_status' =>
                    $calibration[
                        'calibration_status'
                    ]
                    ?? 'UNKNOWN',

                'overall_calibration' =>
                    $overallCalibration,
            ],

            'pattern_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'automatic_rule_changes' =>
                false,

            'automatic_threshold_changes' =>
                false,

            'automatic_model_retraining' =>
                false,

            'automatic_recommendation_changes' =>
                false,

            'automatic_workflow_changes' =>
                false,

            'automatic_clinical_action' =>
                false,

            'patterns_are_observational_only' =>
                true,

            'minimum_sample_required_before_action' =>
                true,

            'human_validation_required' =>
                true,

            'message' =>
                'AI learning patterns are observational and advisory only. Pattern detection does not authorize automatic changes to models, thresholds, recommendations, workflows, or clinical behavior.',
        ];
    }
}