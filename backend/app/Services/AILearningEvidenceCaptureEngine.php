<?php

namespace App\Services;

use App\Models\AILearningEvidence;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;

class AILearningEvidenceCaptureEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 54.2
    | AI Learning Evidence Capture Engine
    |--------------------------------------------------------------------------
    |
    | Purpose:
    |
    | Convert existing AI intelligence, workflow, outcome, and human-review
    | information into standardized learning evidence.
    |
    | IMPORTANT:
    |
    | This engine records evidence only.
    |
    | It does NOT:
    |
    | - modify clinical rules
    | - change recommendation priorities
    | - alter predictive thresholds
    | - automatically suppress recommendations
    | - authorize clinical action
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Capture Generic Learning Evidence
    |--------------------------------------------------------------------------
    */

    public function capture(
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Core Identity
        |--------------------------------------------------------------------------
        */

        $evidenceType =
            strtoupper(
                trim(
                    (string) (
                        $data[
                            'evidence_type'
                        ]
                        ?? 'UNKNOWN'
                    )
                )
            );

        $aiDomain =
            strtoupper(
                trim(
                    (string) (
                        $data[
                            'ai_domain'
                        ]
                        ?? 'UNKNOWN'
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Required Field Validation
        |--------------------------------------------------------------------------
        */

        if ($evidenceType === 'UNKNOWN') {
            return [

                'captured' =>
                    false,

                'status' =>
                    'REJECTED',

                'message' =>
                    'Learning evidence type is required.',

                'evidence' =>
                    null,
            ];
        }

        if ($aiDomain === 'UNKNOWN') {
            return [

                'captured' =>
                    false,

                'status' =>
                    'REJECTED',

                'message' =>
                    'AI domain is required for learning evidence.',

                'evidence' =>
                    null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Resident Context
        |--------------------------------------------------------------------------
        */

        $residentId =
            isset(
                $data[
                    'resident_id'
                ]
            )
            ?
            (int) $data[
                'resident_id'
            ]
            :
            null;

        $resident =
            null;

        if ($residentId) {
            $resident =
                Resident::find(
                    $residentId
                );
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Resolve Resident Status
        |--------------------------------------------------------------------------
        */

        $residentStatus =
            $data[
                'resident_status'
            ]
            ??
            $resident?->status
            ??
            'UNKNOWN';


        /*
        |--------------------------------------------------------------------------
        | 5. Resolve Active Care Eligibility
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'active_care_eligible',
                $data
            )
        ) {
            $activeCareEligible =
                (bool) $data[
                    'active_care_eligible'
                ];

        } else {
            $activeCareEligible =
                $this->isActiveCareStatus(
                    $residentStatus
                );
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Normalize Scores
        |--------------------------------------------------------------------------
        */

        $aiConfidence =
            $this->normalizePercentage(
                $data[
                    'ai_confidence'
                ]
                ?? null
            );

        $aiRiskScore =
            $this->normalizePercentage(
                $data[
                    'ai_risk_score'
                ]
                ?? null
            );

        $accuracyScore =
            $this->normalizePercentage(
                $data[
                    'accuracy_score'
                ]
                ?? null
            );

        $effectivenessScore =
            $this->normalizePercentage(
                $data[
                    'effectiveness_score'
                ]
                ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | 7. Human Review Context
        |--------------------------------------------------------------------------
        */

        $humanReviewStatus =
            strtoupper(
                trim(
                    (string) (
                        $data[
                            'human_review_status'
                        ]
                        ?? 'NOT_REVIEWED'
                    )
                )
            );

        $humanAgreement =
            array_key_exists(
                'human_agreement',
                $data
            )
            ?
            (
                $data[
                    'human_agreement'
                ]
                === null
                ?
                null
                :
                (bool) $data[
                    'human_agreement'
                ]
            )
            :
            null;


        /*
        |--------------------------------------------------------------------------
        | 8. Learning Status
        |--------------------------------------------------------------------------
        */

        $learningStatus =
            strtoupper(
                trim(
                    (string) (
                        $data[
                            'learning_status'
                        ]
                        ??
                        $this->determineLearningStatus(
                            $data
                        )
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 9. Evidence Quality
        |--------------------------------------------------------------------------
        */

        $evidenceQuality =
            strtoupper(
                trim(
                    (string) (
                        $data[
                            'evidence_quality'
                        ]
                        ??
                        $this->determineEvidenceQuality(
                            $data
                        )
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 10. Duplicate Prevention
        |--------------------------------------------------------------------------
        |
        | If source_type + source_id + evidence_type + ai_domain are present,
        | they form a stable evidence identity.
        |
        */

        $sourceType =
            isset(
                $data[
                    'source_type'
                ]
            )
            ?
            strtoupper(
                trim(
                    (string) $data[
                        'source_type'
                    ]
                )
            )
            :
            null;

        $sourceId =
            isset(
                $data[
                    'source_id'
                ]
            )
            ?
            (int) $data[
                'source_id'
            ]
            :
            null;


        if (
            $sourceType
            &&
            $sourceId
        ) {
            $existing =
                AILearningEvidence::query()
                    ->where(
                        'evidence_type',
                        $evidenceType
                    )
                    ->where(
                        'ai_domain',
                        $aiDomain
                    )
                    ->where(
                        'source_type',
                        $sourceType
                    )
                    ->where(
                        'source_id',
                        $sourceId
                    )
                    ->first();

            if ($existing) {
                return [

                    'captured' =>
                        false,

                    'status' =>
                        'DUPLICATE_PREVENTED',

                    'message' =>
                        'Learning evidence already exists for this source.',

                    'evidence_id' =>
                        $existing->id,

                    'evidence' =>
                        $existing->toArray(),
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Build Evidence Payload
        |--------------------------------------------------------------------------
        */

        $payload =
            $data[
                'evidence_payload'
            ]
            ?? [];

        if (!is_array($payload)) {
            $payload = [
                'raw_payload' =>
                    $payload,
            ];
        }

        $payload[
            'capture_metadata'
        ] = [

            'capture_engine' =>
                'AILearningEvidenceCaptureEngine',

            'capture_version' =>
                '54.2',

            'captured_at' =>
                now()->toIso8601String(),

            'automatic_rule_change_allowed' =>
                false,

            'automatic_clinical_action_allowed' =>
                false,

            'human_validation_required' =>
                true,
        ];


        /*
        |--------------------------------------------------------------------------
        | 12. Persist Evidence
        |--------------------------------------------------------------------------
        */

        try {

            $evidence =
                DB::transaction(
                    function () use (
                        $data,
                        $evidenceType,
                        $sourceType,
                        $sourceId,
                        $residentId,
                        $residentStatus,
                        $activeCareEligible,
                        $aiDomain,
                        $aiConfidence,
                        $aiRiskScore,
                        $humanReviewStatus,
                        $humanAgreement,
                        $accuracyScore,
                        $effectivenessScore,
                        $evidenceQuality,
                        $learningStatus,
                        $payload
                    ) {
                        return AILearningEvidence::create([

                            'evidence_type' =>
                                $evidenceType,

                            'source_type' =>
                                $sourceType,

                            'source_id' =>
                                $sourceId,

                            'resident_id' =>
                                $residentId,

                            'resident_status' =>
                                $residentStatus,

                            'active_care_eligible' =>
                                $activeCareEligible,

                            'ai_domain' =>
                                $aiDomain,

                            'ai_decision' =>
                                $data[
                                    'ai_decision'
                                ]
                                ?? null,

                            'ai_confidence' =>
                                $aiConfidence,

                            'ai_risk_score' =>
                                $aiRiskScore,

                            'human_review_status' =>
                                $humanReviewStatus,

                            'human_agreement' =>
                                $humanAgreement,

                            'workflow_status' =>
                                isset(
                                    $data[
                                        'workflow_status'
                                    ]
                                )
                                ?
                                strtoupper(
                                    trim(
                                        (string) $data[
                                            'workflow_status'
                                        ]
                                    )
                                )
                                :
                                null,

                            'outcome_status' =>
                                isset(
                                    $data[
                                        'outcome_status'
                                    ]
                                )
                                ?
                                strtoupper(
                                    trim(
                                        (string) $data[
                                            'outcome_status'
                                        ]
                                    )
                                )
                                :
                                null,

                            'accuracy_score' =>
                                $accuracyScore,

                            'effectiveness_score' =>
                                $effectivenessScore,

                            'evidence_quality' =>
                                $evidenceQuality,

                            'learning_status' =>
                                $learningStatus,

                            'evidence_payload' =>
                                $payload,

                            'observed_at' =>
                                $data[
                                    'observed_at'
                                ]
                                ?? now(),

                            'evaluated_at' =>
                                $data[
                                    'evaluated_at'
                                ]
                                ?? (
                                    $learningStatus ===
                                    'EVALUATED'
                                    ?
                                    now()
                                    :
                                    null
                                ),
                        ]);
                    }
                );

        } catch (\Throwable $e) {

            return [

                'captured' =>
                    false,

                'status' =>
                    'FAILED',

                'message' =>
                    'Learning evidence capture failed.',

                'error' =>
                    $e->getMessage(),

                'evidence' =>
                    null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Successful Capture
        |--------------------------------------------------------------------------
        */

        return [

            'captured' =>
                true,

            'status' =>
                'CAPTURED',

            'message' =>
                'AI learning evidence captured successfully.',

            'evidence_id' =>
                $evidence->id,

            'evidence' =>
                $evidence->toArray(),

            'learning_guardrails' => [

                'automatic_clinical_rule_changes' =>
                    false,

                'automatic_prediction_threshold_changes' =>
                    false,

                'automatic_recommendation_priority_changes' =>
                    false,

                'automatic_clinical_action' =>
                    false,

                'human_validation_required' =>
                    true,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Percentage / Score
    |--------------------------------------------------------------------------
    */

    private function normalizePercentage(
        mixed $value
    ): ?float {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Determine Active Care Status
    |--------------------------------------------------------------------------
    */

    private function isActiveCareStatus(
        ?string $status
    ): bool {
        $status =
            strtoupper(
                trim(
                    (string) $status
                )
            );

        return in_array(
            $status,
            [
                'ACTIVE',
                'ADMITTED',
                'IN CARE',
                'IN_CARE',
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Determine Learning Status
    |--------------------------------------------------------------------------
    */

    private function determineLearningStatus(
        array $data
    ): string {
        $hasOutcome =
            !empty(
                $data[
                    'outcome_status'
                ]
                ?? null
            );

        $hasAccuracy =
            isset(
                $data[
                    'accuracy_score'
                ]
            );

        $hasEffectiveness =
            isset(
                $data[
                    'effectiveness_score'
                ]
            );

        if (
            $hasOutcome
            ||
            $hasAccuracy
            ||
            $hasEffectiveness
        ) {
            return 'EVALUATED';
        }

        return 'PENDING';
    }


    /*
    |--------------------------------------------------------------------------
    | Determine Evidence Quality
    |--------------------------------------------------------------------------
    */

    private function determineEvidenceQuality(
        array $data
    ): string {
        $score = 0;

        if (
            !empty(
                $data[
                    'outcome_status'
                ]
                ?? null
            )
        ) {
            $score++;
        }

        if (
            isset(
                $data[
                    'accuracy_score'
                ]
            )
        ) {
            $score++;
        }

        if (
            isset(
                $data[
                    'effectiveness_score'
                ]
            )
        ) {
            $score++;
        }

        if (
            (
                $data[
                    'human_review_status'
                ]
                ?? null
            )
            === 'REVIEWED'
        ) {
            $score++;
        }

        if (
            array_key_exists(
                'human_agreement',
                $data
            )
        ) {
            $score++;
        }


        if ($score >= 5) {
            return 'STRONG';
        }

        if ($score >= 3) {
            return 'MODERATE';
        }

        return 'LIMITED';
    }
}