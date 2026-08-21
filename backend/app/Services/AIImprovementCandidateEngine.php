<?php

namespace App\Services;

class AIImprovementCandidateEngine
{
    protected AILearningPatternIntelligenceEngine $patternEngine;

    public function __construct(
        AILearningPatternIntelligenceEngine $patternEngine
    ) {
        $this->patternEngine =
            $patternEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 54.8
    | AI Improvement Candidate Engine
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Learning Patterns
        |--------------------------------------------------------------------------
        */

        $patternIntelligence =
            $this->patternEngine->analyze(
                $residentId
            );

        $patterns =
            $patternIntelligence[
                'patterns'
            ]
            ?? [];

        $learningMaturity =
            strtoupper(
                (string) (
                    $patternIntelligence[
                        'learning_maturity'
                    ]
                    ?? 'NO LEARNING DATA'
                )
            );

        $patternConfidence =
            strtoupper(
                (string) (
                    $patternIntelligence[
                        'pattern_confidence'
                    ]
                    ?? 'NONE'
                )
            );

        $totalEvidence =
            (int) (
                $patternIntelligence[
                    'learning_evidence_summary'
                ]['total_evaluated_evidence']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Candidate Containers
        |--------------------------------------------------------------------------
        */

        $candidates = [];

        $observationCandidates = [];

        $reviewCandidates = [];

        $blockedCandidates = [];


        /*
        |--------------------------------------------------------------------------
        | 3. Convert Patterns Into Improvement Candidates
        |--------------------------------------------------------------------------
        */

        foreach ($patterns as $pattern) {

            if (!is_array($pattern)) {
                continue;
            }

            $patternCode =
                strtoupper(
                    (string) (
                        $pattern[
                            'pattern_code'
                        ]
                        ?? ''
                    )
                );

            $evidenceCount =
                (int) (
                    $pattern[
                        'evidence_count'
                    ]
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | Conservative Confidence
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'CONSERVATIVE_CONFIDENCE'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'REVIEW_CONFIDENCE_CALIBRATION',

                        category:
                            'CALIBRATION',

                        title:
                            'Review AI confidence calibration',

                        description:
                            'Observed AI accuracy is currently higher than AI confidence. Continue collecting evidence and review whether confidence calibration remains consistently conservative.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            20,

                        candidateAction:
                            'Review confidence calibration methodology.',

                        prohibitedAutomaticAction:
                            'Do not automatically increase confidence values or modify predictive thresholds.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Potential Overconfidence
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'POTENTIAL_OVERCONFIDENCE'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'REVIEW_OVERCONFIDENCE_RISK',

                        category:
                            'CALIBRATION',

                        title:
                            'Review possible AI over-confidence',

                        description:
                            'AI confidence is currently higher than observed accuracy. Additional evidence and human validation are required before any calibration change is considered.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            10,

                        candidateAction:
                            'Review confidence calibration and prediction reliability.',

                        prohibitedAutomaticAction:
                            'Do not automatically reduce confidence values or alter decision thresholds.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | High Prediction Human Agreement
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'HIGH_PREDICTION_HUMAN_AGREEMENT'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'VALIDATE_PREDICTION_RELIABILITY',

                        category:
                            'PREDICTION',

                        title:
                            'Continue validating prediction reliability',

                        description:
                            'Human reviewers currently show strong agreement with evaluated predictive intelligence. More evidence is required before treating this as a stable reliability signal.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            20,

                        candidateAction:
                            'Continue structured validation of predictive performance.',

                        prohibitedAutomaticAction:
                            'Do not automatically increase prediction authority or reduce human review.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Strong Recommendation Outcome
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'STRONG_RECOMMENDATION_OUTCOME_SIGNAL'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'VALIDATE_RECOMMENDATION_EFFECTIVENESS',

                        category:
                            'CARE_RECOMMENDATION',

                        title:
                            'Validate care recommendation effectiveness',

                        description:
                            'Evaluated care recommendations currently show strong effectiveness and human agreement. Additional outcomes are required before considering recommendation-policy changes.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            20,

                        candidateAction:
                            'Continue validating recommendation effectiveness across additional cases.',

                        prohibitedAutomaticAction:
                            'Do not automatically promote, reprioritize, suppress, or expand recommendation behavior.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Strong Workflow Execution
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'STRONG_WORKFLOW_EXECUTION_SIGNAL'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'VALIDATE_WORKFLOW_EFFECTIVENESS',

                        category:
                            'CARE_WORKFLOW',

                        title:
                            'Validate workflow effectiveness',

                        description:
                            'Evaluated AI-supported workflows currently show strong completion and effectiveness. Additional operational evidence is required before considering workflow optimization.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            20,

                        candidateAction:
                            'Continue validating workflow completion and outcome effectiveness.',

                        prohibitedAutomaticAction:
                            'Do not automatically change workflow routing, assignment, or execution behavior.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Low Recommendation Effectiveness
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'LOW_RECOMMENDATION_EFFECTIVENESS'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'REVIEW_LOW_RECOMMENDATION_EFFECTIVENESS',

                        category:
                            'CARE_RECOMMENDATION',

                        title:
                            'Review low recommendation effectiveness',

                        description:
                            'Observed recommendation effectiveness is below the preferred learning range and should be reviewed after sufficient evidence is accumulated.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            10,

                        candidateAction:
                            'Perform human review of recommendation logic and outcome alignment.',

                        prohibitedAutomaticAction:
                            'Do not automatically suppress or modify recommendation rules.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Low Workflow Completion
            |--------------------------------------------------------------------------
            */

            if (
                $patternCode ===
                'LOW_WORKFLOW_COMPLETION'
            ) {
                $candidate =
                    $this->buildCandidate(

                        candidateCode:
                            'REVIEW_WORKFLOW_COMPLETION',

                        category:
                            'CARE_WORKFLOW',

                        title:
                            'Review workflow completion performance',

                        description:
                            'AI-supported workflow completion is below the preferred operational learning range.',

                        sourcePattern:
                            $pattern,

                        evidenceCount:
                            $evidenceCount,

                        minimumEvidenceRequired:
                            10,

                        candidateAction:
                            'Review workflow routing, assignment, and execution bottlenecks.',

                        prohibitedAutomaticAction:
                            'Do not automatically change workflow routing or task assignments.'
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Limited Data Patterns
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $patternCode,
                    [
                        'LIMITED_PREDICTION_EVIDENCE',
                        'LIMITED_RECOMMENDATION_EVIDENCE',
                        'LIMITED_WORKFLOW_EVIDENCE',
                    ],
                    true
                )
            ) {
                $candidate =
                    $this->buildDataCollectionCandidate(
                        $pattern
                    );

                $this->storeCandidate(
                    $candidate,
                    $candidates,
                    $observationCandidates,
                    $reviewCandidates,
                    $blockedCandidates
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Overall Improvement Readiness
        |--------------------------------------------------------------------------
        */

        $changeReadyCandidates =
            collect(
                $candidates
            )
            ->filter(
                fn ($candidate) =>
                    (
                        $candidate[
                            'candidate_status'
                        ]
                        ?? ''
                    )
                    === 'READY_FOR_HUMAN_REVIEW'
            )
            ->count();

        $improvementReadiness =
            'OBSERVATION_ONLY';

        if (
            $learningMaturity ===
                'MATURE LEARNING'
            &&
            $patternConfidence ===
                'HIGHER'
            &&
            $changeReadyCandidates > 0
        ) {
            $improvementReadiness =
                'HUMAN_REVIEW_READY';

        } elseif (
            $learningMaturity ===
                'DEVELOPING LEARNING'
            &&
            $changeReadyCandidates > 0
        ) {
            $improvementReadiness =
                'LIMITED_REVIEW_READY';
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Improvement Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            'AI improvement candidate analysis currently uses '
            . $totalEvidence
            . ' evaluated learning evidence record(s).';

        $findings[] =
            count(
                $candidates
            )
            . ' structured improvement candidate(s) were identified from observed learning patterns.';

        if ($changeReadyCandidates === 0) {
            $findings[] =
                'No improvement candidate is currently eligible for implementation review because evidence maturity remains insufficient.';
        }

        if (
            $learningMaturity ===
            'EARLY LEARNING'
        ) {
            $findings[] =
                'Current learning maturity is early; improvement candidates should remain observation-only until substantially more evidence is available.';
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'candidate_status' =>
                empty(
                    $candidates
                )
                ?
                'NO_CANDIDATES'
                :
                'CANDIDATES_AVAILABLE',

            'resident_id' =>
                $residentId,

            'improvement_readiness' =>
                $improvementReadiness,

            'learning_context' => [

                'learning_maturity' =>
                    $learningMaturity,

                'pattern_confidence' =>
                    $patternConfidence,

                'total_evaluated_evidence' =>
                    $totalEvidence,

                'source_pattern_count' =>
                    count(
                        $patterns
                    ),
            ],

            'candidate_summary' => [

                'total_candidates' =>
                    count(
                        $candidates
                    ),

                'observation_only_candidates' =>
                    count(
                        $observationCandidates
                    ),

                'human_review_candidates' =>
                    count(
                        $reviewCandidates
                    ),

                'blocked_candidates' =>
                    count(
                        $blockedCandidates
                    ),

                'change_ready_candidates' =>
                    $changeReadyCandidates,
            ],

            'candidates' =>
                $candidates,

            'observation_candidates' =>
                $observationCandidates,

            'human_review_candidates' =>
                $reviewCandidates,

            'blocked_candidates' =>
                $blockedCandidates,

            'improvement_findings' =>
                $findings,

            'source_pattern_intelligence' => [

                'pattern_status' =>
                    $patternIntelligence[
                        'pattern_status'
                    ]
                    ?? 'UNKNOWN',

                'learning_maturity' =>
                    $learningMaturity,

                'pattern_confidence' =>
                    $patternConfidence,

                'pattern_summary' =>
                    $patternIntelligence[
                        'pattern_summary'
                    ]
                    ?? [],
            ],

            'improvement_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Build Candidate
    |--------------------------------------------------------------------------
    */

    private function buildCandidate(
        string $candidateCode,
        string $category,
        string $title,
        string $description,
        array $sourcePattern,
        int $evidenceCount,
        int $minimumEvidenceRequired,
        string $candidateAction,
        string $prohibitedAutomaticAction
    ): array {
        $status =
            $this->determineCandidateStatus(
                $evidenceCount,
                $minimumEvidenceRequired
            );

        return [

            'candidate_code' =>
                $candidateCode,

            'category' =>
                $category,

            'title' =>
                $title,

            'candidate_status' =>
                $status,

            'description' =>
                $description,

            'candidate_action' =>
                $candidateAction,

            'source_pattern_code' =>
                $sourcePattern[
                    'pattern_code'
                ]
                ?? null,

            'source_pattern_severity' =>
                $sourcePattern[
                    'severity'
                ]
                ?? null,

            'evidence_count' =>
                $evidenceCount,

            'minimum_evidence_required' =>
                $minimumEvidenceRequired,

            'evidence_gap' =>
                max(
                    0,
                    $minimumEvidenceRequired
                    -
                    $evidenceCount
                ),

            'supporting_metrics' =>
                $sourcePattern[
                    'supporting_metrics'
                ]
                ?? [],

            'implementation_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'human_review_required' =>
                true,

            'prohibited_automatic_action' =>
                $prohibitedAutomaticAction,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Data Collection Candidate
    |--------------------------------------------------------------------------
    */

    private function buildDataCollectionCandidate(
        array $pattern
    ): array {
        $patternCode =
            strtoupper(
                (string) (
                    $pattern[
                        'pattern_code'
                    ]
                    ?? ''
                )
            );

        $category =
            match ($patternCode) {

                'LIMITED_PREDICTION_EVIDENCE' =>
                    'PREDICTION',

                'LIMITED_RECOMMENDATION_EVIDENCE' =>
                    'CARE_RECOMMENDATION',

                'LIMITED_WORKFLOW_EVIDENCE' =>
                    'CARE_WORKFLOW',

                default =>
                    'DATA_MATURITY',
            };

        $title =
            match ($patternCode) {

                'LIMITED_PREDICTION_EVIDENCE' =>
                    'Collect additional prediction evidence',

                'LIMITED_RECOMMENDATION_EVIDENCE' =>
                    'Collect additional recommendation evidence',

                'LIMITED_WORKFLOW_EVIDENCE' =>
                    'Collect additional workflow evidence',

                default =>
                    'Collect additional learning evidence',
            };

        return [

            'candidate_code' =>
                'INCREASE_'
                . $category
                . '_EVIDENCE',

            'category' =>
                'DATA_MATURITY',

            'title' =>
                $title,

            'candidate_status' =>
                'OBSERVE_ONLY',

            'description' =>
                $pattern[
                    'description'
                ]
                ??
                'Additional evidence is required before reliable learning conclusions can be made.',

            'candidate_action' =>
                'Continue structured evidence collection.',

            'source_pattern_code' =>
                $patternCode,

            'source_pattern_severity' =>
                $pattern[
                    'severity'
                ]
                ?? 'CAUTION',

            'evidence_count' =>
                (int) (
                    $pattern[
                        'evidence_count'
                    ]
                    ?? 0
                ),

            'minimum_evidence_required' =>
                5,

            'evidence_gap' =>
                max(
                    0,
                    5
                    -
                    (int) (
                        $pattern[
                            'evidence_count'
                        ]
                        ?? 0
                    )
                ),

            'supporting_metrics' =>
                [],

            'implementation_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'human_review_required' =>
                true,

            'prohibited_automatic_action' =>
                'Do not infer model or rule changes from insufficient evidence.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Candidate Status
    |--------------------------------------------------------------------------
    */

    private function determineCandidateStatus(
        int $evidenceCount,
        int $minimumEvidenceRequired
    ): string {
        if (
            $evidenceCount <
            $minimumEvidenceRequired
        ) {
            return 'INSUFFICIENT_EVIDENCE';
        }

        return 'READY_FOR_HUMAN_REVIEW';
    }


    /*
    |--------------------------------------------------------------------------
    | Store Candidate by Status
    |--------------------------------------------------------------------------
    */

    private function storeCandidate(
        array $candidate,
        array &$all,
        array &$observation,
        array &$review,
        array &$blocked
    ): void {
        $all[] =
            $candidate;

        $status =
            $candidate[
                'candidate_status'
            ]
            ?? 'UNKNOWN';

        if (
            $status ===
            'READY_FOR_HUMAN_REVIEW'
        ) {
            $review[] =
                $candidate;

            return;
        }

        if (
            in_array(
                $status,
                [
                    'INSUFFICIENT_EVIDENCE',
                    'OBSERVE_ONLY',
                ],
                true
            )
        ) {
            $observation[] =
                $candidate;

            return;
        }

        $blocked[] =
            $candidate;
    }


    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'automatic_model_changes' =>
                false,

            'automatic_threshold_changes' =>
                false,

            'automatic_confidence_changes' =>
                false,

            'automatic_recommendation_changes' =>
                false,

            'automatic_workflow_changes' =>
                false,

            'automatic_clinical_rule_changes' =>
                false,

            'automatic_clinical_action' =>
                false,

            'implementation_requires_separate_validation' =>
                true,

            'implementation_requires_human_approval' =>
                true,

            'candidate_generation_is_advisory_only' =>
                true,

            'message' =>
                'AI improvement candidates are advisory proposals only. Candidate generation does not authorize implementation, model modification, threshold adjustment, recommendation changes, workflow changes, or clinical action.',
        ];
    }
}