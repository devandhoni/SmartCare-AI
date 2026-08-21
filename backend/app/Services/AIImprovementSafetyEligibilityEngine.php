<?php

namespace App\Services;

class AIImprovementSafetyEligibilityEngine
{
    protected AIImprovementCandidateEngine $candidateEngine;

    public function __construct(
        AIImprovementCandidateEngine $candidateEngine
    ) {
        $this->candidateEngine =
            $candidateEngine;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 54.9
    | AI Improvement Safety & Eligibility Engine
    |--------------------------------------------------------------------------
    */

    public function analyze(
        ?int $residentId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Improvement Candidates
        |--------------------------------------------------------------------------
        */

        $candidateIntelligence =
            $this->candidateEngine->analyze(
                $residentId
            );

        $candidates =
            $candidateIntelligence[
                'candidates'
            ]
            ?? [];

        $learningContext =
            $candidateIntelligence[
                'learning_context'
            ]
            ?? [];

        $learningMaturity =
            strtoupper(
                (string) (
                    $learningContext[
                        'learning_maturity'
                    ]
                    ?? 'NO LEARNING DATA'
                )
            );

        $patternConfidence =
            strtoupper(
                (string) (
                    $learningContext[
                        'pattern_confidence'
                    ]
                    ?? 'NONE'
                )
            );

        $totalEvidence =
            (int) (
                $learningContext[
                    'total_evaluated_evidence'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Validation Containers
        |--------------------------------------------------------------------------
        */

        $validatedCandidates = [];

        $observeOnlyCandidates = [];

        $humanReviewEligibleCandidates = [];

        $blockedCandidates = [];

        $warnings = [];

        $criticalIssues = [];


        /*
        |--------------------------------------------------------------------------
        | 3. Validate Every Candidate
        |--------------------------------------------------------------------------
        */

        foreach ($candidates as $candidate) {

            if (!is_array($candidate)) {
                continue;
            }

            $validation =
                $this->validateCandidate(
                    $candidate,
                    $learningMaturity,
                    $patternConfidence
                );

            $validatedCandidates[] =
                $validation;

            $eligibilityStatus =
                $validation[
                    'eligibility_status'
                ]
                ?? 'BLOCKED';

            if (
                $eligibilityStatus ===
                'OBSERVE_ONLY'
            ) {
                $observeOnlyCandidates[] =
                    $validation;
            }

            if (
                $eligibilityStatus ===
                'ELIGIBLE_FOR_HUMAN_REVIEW'
            ) {
                $humanReviewEligibleCandidates[] =
                    $validation;
            }

            if (
                $eligibilityStatus ===
                'BLOCKED'
            ) {
                $blockedCandidates[] =
                    $validation;
            }

            foreach (
                $validation[
                    'warnings'
                ]
                ?? []
                as $warning
            ) {
                $warnings[] =
                    $warning;
            }

            foreach (
                $validation[
                    'critical_issues'
                ]
                ?? []
                as $issue
            ) {
                $criticalIssues[] =
                    $issue;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Remove Duplicate Warnings / Issues
        |--------------------------------------------------------------------------
        */

        $warnings =
            array_values(
                array_unique(
                    $warnings
                )
            );

        $criticalIssues =
            array_values(
                array_unique(
                    $criticalIssues
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 5. Safety Status
        |--------------------------------------------------------------------------
        */

        $safetyStatus =
            'SAFE';

        if (!empty($criticalIssues)) {

            $safetyStatus =
                'BLOCKED';

        } elseif (!empty($warnings)) {

            $safetyStatus =
                'SAFE_WITH_WARNINGS';
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Implementation Review Readiness
        |--------------------------------------------------------------------------
        */

        $implementationReviewReady =
            count(
                $humanReviewEligibleCandidates
            ) > 0
            &&
            empty(
                $criticalIssues
            );


        /*
        |--------------------------------------------------------------------------
        | 7. Overall Eligibility
        |--------------------------------------------------------------------------
        */

        $overallEligibility =
            'OBSERVATION_ONLY';

        if (!empty($criticalIssues)) {

            $overallEligibility =
                'BLOCKED';

        } elseif ($implementationReviewReady) {

            $overallEligibility =
                'HUMAN_REVIEW_ELIGIBLE';
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Validation Summary
        |--------------------------------------------------------------------------
        */

        $validationSummary = [

            'total_candidates_evaluated' =>
                count(
                    $validatedCandidates
                ),

            'observe_only_candidates' =>
                count(
                    $observeOnlyCandidates
                ),

            'human_review_eligible_candidates' =>
                count(
                    $humanReviewEligibleCandidates
                ),

            'blocked_candidates' =>
                count(
                    $blockedCandidates
                ),

            'warning_count' =>
                count(
                    $warnings
                ),

            'critical_issue_count' =>
                count(
                    $criticalIssues
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | 9. Safety Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];

        $findings[] =
            'AI improvement safety validation evaluated '
            . count(
                $validatedCandidates
            )
            . ' improvement candidate(s).';

        if (
            count(
                $humanReviewEligibleCandidates
            ) === 0
        ) {
            $findings[] =
                'No improvement candidate is currently eligible for implementation review.';
        }

        if (
            count(
                $observeOnlyCandidates
            ) > 0
        ) {
            $findings[] =
                count(
                    $observeOnlyCandidates
                )
                . ' candidate(s) remain observation-only because evidence or learning maturity is insufficient.';
        }

        if (
            $learningMaturity ===
            'EARLY LEARNING'
        ) {
            $findings[] =
                'Learning maturity remains early, so implementation eligibility is intentionally restricted.';
        }

        if (
            $patternConfidence ===
            'VERY LIMITED'
        ) {
            $findings[] =
                'Pattern confidence remains very limited and does not support implementation-level decisions.';
        }

        if (
            empty(
                $criticalIssues
            )
        ) {
            $findings[] =
                'No critical self-improvement safety issue was detected.';
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'safety_status' =>
                $safetyStatus,

            'resident_id' =>
                $residentId,

            'overall_eligibility' =>
                $overallEligibility,

            'implementation_review_ready' =>
                $implementationReviewReady,

            'learning_context' => [

                'learning_maturity' =>
                    $learningMaturity,

                'pattern_confidence' =>
                    $patternConfidence,

                'total_evaluated_evidence' =>
                    $totalEvidence,

                'candidate_status' =>
                    $candidateIntelligence[
                        'candidate_status'
                    ]
                    ?? 'UNKNOWN',

                'improvement_readiness' =>
                    $candidateIntelligence[
                        'improvement_readiness'
                    ]
                    ?? 'UNKNOWN',
            ],

            'validation_summary' =>
                $validationSummary,

            'validated_candidates' =>
                $validatedCandidates,

            'observe_only_candidates' =>
                $observeOnlyCandidates,

            'human_review_eligible_candidates' =>
                $humanReviewEligibleCandidates,

            'blocked_candidates' =>
                $blockedCandidates,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'safety_findings' =>
                $findings,

            'source_candidate_summary' =>
                $candidateIntelligence[
                    'candidate_summary'
                ]
                ?? [],

            'safety_guardrails' =>
                $this->guardrails(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Candidate
    |--------------------------------------------------------------------------
    */

    private function validateCandidate(
        array $candidate,
        string $learningMaturity,
        string $patternConfidence
    ): array {
        $checks = [];

        $warnings = [];

        $criticalIssues = [];


        /*
        |--------------------------------------------------------------------------
        | Candidate Identity
        |--------------------------------------------------------------------------
        */

        $candidateCode =
            strtoupper(
                (string) (
                    $candidate[
                        'candidate_code'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $candidateStatus =
            strtoupper(
                (string) (
                    $candidate[
                        'candidate_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $evidenceCount =
            (int) (
                $candidate[
                    'evidence_count'
                ]
                ?? 0
            );

        $minimumEvidenceRequired =
            (int) (
                $candidate[
                    'minimum_evidence_required'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Check 1
        | Candidate Must Never Allow Automatic Change
        |--------------------------------------------------------------------------
        */

        $automaticChangeAllowed =
            (bool) (
                $candidate[
                    'automatic_change_allowed'
                ]
                ?? false
            );

        $checks[
            'automatic_change_disabled'
        ] = [

            'passed' =>
                $automaticChangeAllowed === false,

            'value' =>
                $automaticChangeAllowed,

            'message' =>
                $automaticChangeAllowed === false
                ?
                'Automatic candidate implementation is disabled.'
                :
                'Automatic candidate implementation must not be enabled.',
        ];

        if ($automaticChangeAllowed) {
            $criticalIssues[] =
                $candidateCode
                . ': automatic change is enabled.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 2
        | Implementation Must Not Already Be Allowed
        |--------------------------------------------------------------------------
        */

        $implementationAllowed =
            (bool) (
                $candidate[
                    'implementation_allowed'
                ]
                ?? false
            );

        $checks[
            'implementation_not_pre_authorized'
        ] = [

            'passed' =>
                $implementationAllowed === false,

            'value' =>
                $implementationAllowed,

            'message' =>
                $implementationAllowed === false
                ?
                'Candidate implementation is not pre-authorized.'
                :
                'Candidate implementation must not be pre-authorized.',
        ];

        if ($implementationAllowed) {
            $criticalIssues[] =
                $candidateCode
                . ': implementation is already authorized.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 3
        | Human Review Must Be Required
        |--------------------------------------------------------------------------
        */

        $humanReviewRequired =
            (bool) (
                $candidate[
                    'human_review_required'
                ]
                ?? false
            );

        $checks[
            'human_review_required'
        ] = [

            'passed' =>
                $humanReviewRequired,

            'value' =>
                $humanReviewRequired,

            'message' =>
                $humanReviewRequired
                ?
                'Human review is required.'
                :
                'Candidate does not require human review.',
        ];

        if (!$humanReviewRequired) {
            $criticalIssues[] =
                $candidateCode
                . ': human review guardrail is missing.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 4
        | Evidence Requirement
        |--------------------------------------------------------------------------
        */

        $evidenceRequirementMet =
            $evidenceCount >=
            $minimumEvidenceRequired;

        $checks[
            'minimum_evidence_met'
        ] = [

            'passed' =>
                $evidenceRequirementMet,

            'evidence_count' =>
                $evidenceCount,

            'minimum_required' =>
                $minimumEvidenceRequired,

            'evidence_gap' =>
                max(
                    0,
                    $minimumEvidenceRequired
                    -
                    $evidenceCount
                ),

            'message' =>
                $evidenceRequirementMet
                ?
                'Minimum candidate evidence requirement is satisfied.'
                :
                'Minimum candidate evidence requirement is not yet satisfied.',
        ];

        if (!$evidenceRequirementMet) {
            $warnings[] =
                $candidateCode
                . ': additional learning evidence is required.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 5
        | Learning Maturity
        |--------------------------------------------------------------------------
        */

        $maturityAllowsReview =
            in_array(
                $learningMaturity,
                [
                    'DEVELOPING LEARNING',
                    'MATURE LEARNING',
                ],
                true
            );

        $checks[
            'learning_maturity_allows_review'
        ] = [

            'passed' =>
                $maturityAllowsReview,

            'learning_maturity' =>
                $learningMaturity,

            'message' =>
                $maturityAllowsReview
                ?
                'Learning maturity is sufficient for controlled human review consideration.'
                :
                'Learning maturity is not yet sufficient for implementation review.',
        ];

        if (!$maturityAllowsReview) {
            $warnings[] =
                $candidateCode
                . ': learning maturity remains insufficient.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 6
        | Pattern Confidence
        |--------------------------------------------------------------------------
        */

        $patternConfidenceAllowsReview =
            in_array(
                $patternConfidence,
                [
                    'MODERATE',
                    'HIGHER',
                ],
                true
            );

        $checks[
            'pattern_confidence_allows_review'
        ] = [

            'passed' =>
                $patternConfidenceAllowsReview,

            'pattern_confidence' =>
                $patternConfidence,

            'message' =>
                $patternConfidenceAllowsReview
                ?
                'Pattern confidence is sufficient for controlled human review consideration.'
                :
                'Pattern confidence remains insufficient for implementation review.',
        ];

        if (!$patternConfidenceAllowsReview) {
            $warnings[] =
                $candidateCode
                . ': pattern confidence remains insufficient.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 7
        | Candidate Status Consistency
        |--------------------------------------------------------------------------
        */

        $candidateStatusConsistent =
            true;

        if (
            $candidateStatus ===
                'READY_FOR_HUMAN_REVIEW'
            &&
            !$evidenceRequirementMet
        ) {
            $candidateStatusConsistent =
                false;
        }

        $checks[
            'candidate_status_consistent'
        ] = [

            'passed' =>
                $candidateStatusConsistent,

            'candidate_status' =>
                $candidateStatus,

            'message' =>
                $candidateStatusConsistent
                ?
                'Candidate status is consistent with evidence eligibility.'
                :
                'Candidate status conflicts with evidence eligibility.',
        ];

        if (!$candidateStatusConsistent) {
            $criticalIssues[] =
                $candidateCode
                . ': candidate status is inconsistent with evidence threshold.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check 8
        | Prohibited Automatic Action Must Be Defined
        |--------------------------------------------------------------------------
        */

        $prohibitedAutomaticAction =
            trim(
                (string) (
                    $candidate[
                        'prohibited_automatic_action'
                    ]
                    ?? ''
                )
            );

        $checks[
            'prohibited_automatic_action_defined'
        ] = [

            'passed' =>
                $prohibitedAutomaticAction !== '',

            'message' =>
                $prohibitedAutomaticAction !== ''
                ?
                'Prohibited automatic behavior is explicitly documented.'
                :
                'Candidate does not define prohibited automatic behavior.',
        ];

        if (
            $prohibitedAutomaticAction === ''
        ) {
            $criticalIssues[] =
                $candidateCode
                . ': prohibited automatic action is not documented.';
        }


        /*
        |--------------------------------------------------------------------------
        | Eligibility Decision
        |--------------------------------------------------------------------------
        */

        $eligibilityStatus =
            'OBSERVE_ONLY';

        if (!empty($criticalIssues)) {

            $eligibilityStatus =
                'BLOCKED';

        } elseif (
            $evidenceRequirementMet
            &&
            $maturityAllowsReview
            &&
            $patternConfidenceAllowsReview
            &&
            $humanReviewRequired
            &&
            !$automaticChangeAllowed
            &&
            !$implementationAllowed
        ) {
            $eligibilityStatus =
                'ELIGIBLE_FOR_HUMAN_REVIEW';
        }


        /*
        |--------------------------------------------------------------------------
        | Validation Status
        |--------------------------------------------------------------------------
        */

        $validationStatus =
            'PASSED';

        if (!empty($criticalIssues)) {

            $validationStatus =
                'FAILED';

        } elseif (!empty($warnings)) {

            $validationStatus =
                'PASSED_WITH_WARNINGS';
        }


        /*
        |--------------------------------------------------------------------------
        | Candidate Result
        |--------------------------------------------------------------------------
        */

        return [

            'candidate_code' =>
                $candidateCode,

            'category' =>
                $candidate[
                    'category'
                ]
                ?? 'UNKNOWN',

            'title' =>
                $candidate[
                    'title'
                ]
                ?? null,

            'original_candidate_status' =>
                $candidateStatus,

            'validation_status' =>
                $validationStatus,

            'eligibility_status' =>
                $eligibilityStatus,

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

            'implementation_allowed' =>
                false,

            'automatic_change_allowed' =>
                false,

            'human_review_required' =>
                true,

            'checks' =>
                $checks,

            'warnings' =>
                array_values(
                    array_unique(
                        $warnings
                    )
                ),

            'critical_issues' =>
                array_values(
                    array_unique(
                        $criticalIssues
                    )
                ),

            'source_candidate' =>
                $candidate,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Step 54.9 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [

            'automatic_model_change' =>
                false,

            'automatic_threshold_change' =>
                false,

            'automatic_confidence_change' =>
                false,

            'automatic_recommendation_change' =>
                false,

            'automatic_workflow_change' =>
                false,

            'automatic_clinical_rule_change' =>
                false,

            'automatic_clinical_action' =>
                false,

            'candidate_implementation_pre_authorized' =>
                false,

            'separate_human_review_required' =>
                true,

            'separate_governance_validation_required' =>
                true,

            'safety_engine_can_apply_changes' =>
                false,

            'message' =>
                'AI improvement safety validation determines eligibility only. It cannot implement model, threshold, confidence, recommendation, workflow, clinical rule, or clinical action changes.',
        ];
    }
}