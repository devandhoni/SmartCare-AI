<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\ClinicalRecommendation;
use Throwable;

class AICareRecommendationEngine
{
    protected PredictiveDeteriorationService $predictiveService;

    public function __construct(
        PredictiveDeteriorationService $predictiveService
    ) {
        $this->predictiveService = $predictiveService;
    }

    /**
     * Step 52.2
     * Risk-to-Care Mapping Enhancement
     */
    public function analyze(int $residentId): array
    {
        /*
        |--------------------------------------------------------------------------
        | Resident
        |--------------------------------------------------------------------------
        */

        $resident = Resident::find($residentId);

        if (!$resident) {
            return [
                'resident_id' => $residentId,
                'status' => 'RESIDENT_NOT_FOUND',
                'message' => 'Resident could not be found.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Resident Operational Status
        |--------------------------------------------------------------------------
        */

        $residentStatus =
            strtoupper(
                (string) (
                    $resident->status
                    ?? 'UNKNOWN'
                )
            );

        $isActiveResident =
            in_array(
                $residentStatus,
                [
                    'ACTIVE',
                    'ADMITTED',
                    'IN CARE',
                    'CURRENT',
                ],
                true
            );

        /*
        |--------------------------------------------------------------------------
        | Step 51 Predictive Intelligence
        |--------------------------------------------------------------------------
        */

        try {
            $prediction =
                $this->predictiveService->predict(
                    $residentId
                );
        } catch (Throwable $e) {
            return [
                'resident_id' => $residentId,
                'resident_name' =>
                    $this->residentName($resident),
                'resident_status' =>
                    $resident->status ?? 'UNKNOWN',
                'status' =>
                    'PREDICTIVE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Predictive intelligence could not be generated.',
            ];
        }

        if (!is_array($prediction)) {
            return [
                'resident_id' => $residentId,
                'resident_name' =>
                    $this->residentName($resident),
                'resident_status' =>
                    $resident->status ?? 'UNKNOWN',
                'status' =>
                    'PREDICTIVE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Predictive intelligence returned invalid data.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Predictive Intelligence
        |--------------------------------------------------------------------------
        */

        $clinicalSeverity =
            strtoupper(
                (string) (
                    $prediction[
                        'clinical_severity'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $deteriorationRisk =
            strtoupper(
                (string) (
                    $prediction[
                        'deterioration_risk'
                    ]
                    ?? 'LOW'
                )
            );

        $trendDirection =
            strtoupper(
                (string) (
                    $prediction[
                        'trend_direction'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $escalationStatus =
            strtoupper(
                (string) (
                    $prediction[
                        'escalation_status'
                    ]
                    ?? 'NONE'
                )
            );

        $predictiveActionTiming =
            strtoupper(
                (string) (
                    $prediction[
                        'clinical_action_timing'
                    ]
                    ?? 'ROUTINE'
                )
            );

        $predictionConfidence =
            (float) (
                $prediction[
                    'prediction_confidence'
                ]
                ?? 0
            );

        $trendConfidence =
            (float) (
                $prediction[
                    'trend_confidence'
                ]
                ?? 0
            );

        $evidenceQuality =
            strtoupper(
                (string) (
                    $prediction[
                        'evidence_quality'
                    ]['status']
                    ?? 'UNKNOWN'
                )
            );

        $clinicalDrivers =
            $prediction[
                'clinical_drivers'
            ]
            ?? [];

        $primaryDriver =
            $clinicalDrivers[
                'primary_driver'
            ]
            ?? null;

        $primaryDriverScore =
            (float) (
                $clinicalDrivers[
                    'primary_score'
                ]
                ?? 0
            );

        $dominantDrivers =
            $clinicalDrivers[
                'dominant_drivers'
            ]
            ?? [];

        $domains =
            $clinicalDrivers[
                'domains'
            ]
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Existing Alert Intelligence
        |--------------------------------------------------------------------------
        */

        $openAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'OPEN'
            )
            ->get();

        $medicationAlerts =
            $openAlerts
                ->filter(
                    function ($alert) {
                        $type =
                            strtoupper(
                                (string) (
                                    $alert->alert_type
                                    ?? ''
                                )
                            );

                        $message =
                            strtoupper(
                                (string) (
                                    $alert->message
                                    ?? ''
                                )
                            );

                        return
                            str_contains(
                                $type,
                                'MEDICATION'
                            )
                            ||
                            str_contains(
                                $message,
                                'MEDICATION'
                            );
                    }
                );

        /*
        |--------------------------------------------------------------------------
        | Existing Clinical Recommendation Intelligence
        |--------------------------------------------------------------------------
        */

        $existingClinicalRecommendations =
            ClinicalRecommendation::where(
                'resident_id',
                $residentId
            )
            ->latest(
                'created_on'
            )
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Determine Care Priority
        |--------------------------------------------------------------------------
        */

        $carePriority =
            $this->determineCarePriority(
                $clinicalSeverity,
                $deteriorationRisk,
                $trendDirection,
                $escalationStatus
            );

        /*
        |--------------------------------------------------------------------------
        | Resident Status Safety Guardrail
        |--------------------------------------------------------------------------
        |
        | Historical intelligence may still show CRITICAL clinical data for a
        | discharged resident. We preserve the intelligence but prevent it from
        | being treated as an active-care instruction.
        |
        */

        $operationalCareStatus =
            $isActiveResident
            ?
            'ACTIVE CARE'
            :
            'HISTORICAL / NON-ACTIVE CARE';

        /*
        |--------------------------------------------------------------------------
        | Determine Action Timing
        |--------------------------------------------------------------------------
        */

        $actionTiming =
            $this->determineActionTiming(
                $carePriority,
                $predictiveActionTiming,
                $escalationStatus
            );

        if (!$isActiveResident) {
            $actionTiming =
                'NO ACTIVE CARE ACTION';
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | Overall Clinical Review
        |--------------------------------------------------------------------------
        */

        if ($isActiveResident) {

            if ($carePriority === 'CRITICAL') {
                $this->addRecommendation(
                    $recommendations,
                    'overall_clinical_review',
                    'Overall Clinical Review',
                    'Immediate clinical assessment and physician review required.',
                    'CRITICAL',
                    'IMMEDIATE',
                    [
                        'Current clinical severity: ' .
                            $clinicalSeverity,

                        'Deterioration risk: ' .
                            $deteriorationRisk,

                        'Escalation status: ' .
                            $escalationStatus,
                    ]
                );
            } elseif ($carePriority === 'HIGH') {
                $this->addRecommendation(
                    $recommendations,
                    'overall_clinical_review',
                    'Overall Clinical Review',
                    'Prompt clinical review and increased monitoring recommended.',
                    'HIGH',
                    'PRIORITY',
                    [
                        'Current clinical severity: ' .
                            $clinicalSeverity,

                        'Deterioration risk: ' .
                            $deteriorationRisk,

                        'Trend direction: ' .
                            $trendDirection,
                    ]
                );
            }

        } else {

            $this->addRecommendation(
                $recommendations,
                'resident_status_guardrail',
                'Resident Status Review',
                'Resident is not currently active in care. Preserve this intelligence for historical review and confirm status before initiating any new care action.',
                'ROUTINE',
                'NO ACTIVE CARE ACTION',
                [
                    'Resident status: ' .
                        $residentStatus,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Domain-Specific Risk-to-Care Mapping
        |--------------------------------------------------------------------------
        */

        foreach ($domains as $domain => $domainData) {

            if (!is_array($domainData)) {
                continue;
            }

            $score =
                (float) (
                    $domainData['score']
                    ?? 0
                );

            $level =
                strtoupper(
                    (string) (
                        $domainData['level']
                        ?? 'LOW'
                    )
                );

            $evidence =
                $domainData['evidence']
                ?? [];

            if (!is_array($evidence)) {
                $evidence = [];
            }

            if (
                $score <= 0
                &&
                empty($evidence)
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Discharged / inactive residents:
            | preserve intelligence, but no active-care instruction.
            |--------------------------------------------------------------------------
            */

            if (!$isActiveResident) {

                $this->addRecommendation(
                    $recommendations,
                    'historical_' . strtolower((string) $domain),
                    ucfirst((string) $domain) .
                        ' Historical Risk',

                    'Historical clinical risk identified in the ' .
                        strtolower((string) $domain) .
                        ' domain. Review only if the resident returns to active care.',

                    'ROUTINE',
                    'NO ACTIVE CARE ACTION',
                    $evidence
                );

                continue;
            }

            switch (
                strtolower(
                    (string) $domain
                )
            ) {

                case 'cardiovascular':

                    $this->addRecommendation(
                        $recommendations,
                        'cardiovascular_monitoring',
                        'Cardiovascular Monitoring',
                        $level === 'CRITICAL'
                            ?
                            'Repeat blood pressure assessment promptly and request clinical review of cardiovascular status.'
                            :
                            'Continue blood pressure monitoring and review persistent abnormal readings.',
                        $this->domainPriority(
                            $level,
                            $carePriority
                        ),
                        $this->domainTiming(
                            $level,
                            $carePriority
                        ),
                        $evidence
                    );

                    break;

                case 'respiratory':

                    $this->addRecommendation(
                        $recommendations,
                        'respiratory_monitoring',
                        'Respiratory Monitoring',
                        $level === 'CRITICAL'
                            ?
                            'Repeat oxygen saturation assessment promptly and request clinical review of respiratory status.'
                            :
                            'Continue oxygen saturation monitoring and assess persistent respiratory abnormalities.',
                        $this->domainPriority(
                            $level,
                            $carePriority
                        ),
                        $this->domainTiming(
                            $level,
                            $carePriority
                        ),
                        $evidence
                    );

                    break;

                case 'metabolic':

                    $this->addRecommendation(
                        $recommendations,
                        'metabolic_monitoring',
                        'Metabolic Monitoring',
                        $level === 'CRITICAL'
                            ?
                            'Repeat blood glucose assessment promptly and request review of current glucose management.'
                            :
                            'Continue blood glucose monitoring and review persistent abnormal glucose readings.',
                        $this->domainPriority(
                            $level,
                            $carePriority
                        ),
                        $this->domainTiming(
                            $level,
                            $carePriority
                        ),
                        $evidence
                    );

                    break;

                case 'infection':

                    $this->addRecommendation(
                        $recommendations,
                        'infection_monitoring',
                        'Infection Monitoring',
                        in_array(
                            $level,
                            [
                                'CRITICAL',
                                'HIGH',
                            ],
                            true
                        )
                            ?
                            'Repeat temperature assessment and request clinical review for possible infection or inflammatory deterioration.'
                            :
                            'Continue temperature monitoring and observe for additional signs of infection.',
                        $this->domainPriority(
                            $level,
                            $carePriority
                        ),
                        $this->domainTiming(
                            $level,
                            $carePriority
                        ),
                        $evidence
                    );

                    break;

                case 'medication':

                    $this->addRecommendation(
                        $recommendations,
                        'medication_review',
                        'Medication Review',
                        in_array(
                            $level,
                            [
                                'CRITICAL',
                                'HIGH',
                            ],
                            true
                        )
                            ?
                            'Review medication administration status and escalate unresolved medication-related concerns.'
                            :
                            'Review medication administration and continue compliance monitoring.',
                        $this->domainPriority(
                            $level,
                            $carePriority
                        ),
                        $this->domainTiming(
                            $level,
                            $carePriority
                        ),
                        $evidence
                    );

                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Step 52.2 Medication Alert Mapping
        |--------------------------------------------------------------------------
        |
        | Medication concerns must not depend only on the Step 51 medication
        | driver because medication delays may originate from workflow alerts.
        |
        */

        if (
            $isActiveResident
            &&
            $medicationAlerts->count() > 0
        ) {

            $medicationEvidence = [];

            foreach (
                $medicationAlerts
                as $alert
            ) {
                if ($alert->message) {
                    $medicationEvidence[] =
                        $alert->message;
                }
            }

            $this->addRecommendation(
                $recommendations,
                'medication_alert_review',
                'Medication Administration Review',
                'Review unresolved medication alerts and confirm whether scheduled medication administration requires follow-up.',
                $carePriority === 'CRITICAL'
                    ?
                    'CRITICAL'
                    :
                    'HIGH',
                $carePriority === 'CRITICAL'
                    ?
                    'IMMEDIATE'
                    :
                    'PRIORITY',
                $medicationEvidence
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Clinical Recommendation Mapping
        |--------------------------------------------------------------------------
        |
        | Step 52.2 Enhancement:
        | Consolidate duplicate historical recommendations into a single
        | clinically meaningful recommendation per category.
        |
        */

        if ($isActiveResident) {

            $consolidatedExisting = [];

            foreach (
                $existingClinicalRecommendations
                as $existing
            ) {

                $existingText =
                    trim(
                        (string) (
                            $existing->recommendation
                            ?? ''
                        )
                    );

                if ($existingText === '') {
                    continue;
                }

                $existingType =
                    trim(
                        (string) (
                            $existing->recommendation_type
                            ?? 'UNKNOWN'
                        )
                    );

                $existingPriority =
                    strtoupper(
                        (string) (
                            $existing->priority
                            ?? 'MEDIUM'
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Normalize Recommendation Category
                |--------------------------------------------------------------------------
                */

                $normalizedKey =
                    $this->normalizeRecommendationKey(
                        $existingType,
                        $existingText
                    );

                /*
                |--------------------------------------------------------------------------
                | Keep Highest Priority Version
                |--------------------------------------------------------------------------
                */

                if (
                    !isset(
                        $consolidatedExisting[
                            $normalizedKey
                        ]
                    )
                ) {

                    $consolidatedExisting[
                        $normalizedKey
                    ] = [
                        'type' =>
                            $existingType,

                        'text' =>
                            $existingText,

                        'priority' =>
                            $this->normalizePriority(
                                $existingPriority
                            ),

                        'timing' =>
                            $this->priorityToTiming(
                                $existingPriority
                            ),

                        'occurrences' =>
                            1,
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Found
                |--------------------------------------------------------------------------
                */

                $consolidatedExisting[
                    $normalizedKey
                ]['occurrences']++;

                $currentPriority =
                    $consolidatedExisting[
                        $normalizedKey
                    ]['priority'];

                $newPriority =
                    $this->normalizePriority(
                        $existingPriority
                    );

                if (
                    $this->priorityRank(
                        $newPriority
                    )
                    >
                    $this->priorityRank(
                        $currentPriority
                    )
                ) {

                    $consolidatedExisting[
                        $normalizedKey
                    ]['priority'] =
                        $newPriority;

                    $consolidatedExisting[
                        $normalizedKey
                    ]['timing'] =
                        $this->priorityToTiming(
                            $existingPriority
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Add Consolidated Recommendations
            |--------------------------------------------------------------------------
            */

            foreach (
                $consolidatedExisting
                as $key => $existing
            ) {

                /*
                |--------------------------------------------------------------------------
                | Skip if AI already generated same care domain
                |--------------------------------------------------------------------------
                */

                if (
                    $this->recommendationAlreadyCovered(
                        $recommendations,
                        $key
                    )
                ) {

                    continue;
                }

                $evidence = [
                    'Existing recommendation type: ' .
                        $existing['type'],
                ];

                if (
                    $existing['occurrences']
                    > 1
                ) {

                    $evidence[] =
                        'Similar recommendation recorded ' .
                        $existing['occurrences'] .
                        ' times.';
                }

                $this->addRecommendation(
                    $recommendations,

                    'existing_' .
                        $key,

                    $this->existingRecommendationCategory(
                        $key,
                        $existing['type']
                    ),

                    $existing['text'],

                    $existing['priority'],

                    $existing['timing'],

                    $evidence
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Worsening Trend
        |--------------------------------------------------------------------------
        */

        if (
            $isActiveResident
            &&
            $trendDirection === 'WORSENING'
        ) {

            $this->addRecommendation(
                $recommendations,
                'increased_monitoring',
                'Increased Monitoring',
                'Increase monitoring frequency because the resident health trend is worsening.',
                in_array(
                    $carePriority,
                    [
                        'CRITICAL',
                        'HIGH',
                    ],
                    true
                )
                    ?
                    $carePriority
                    :
                    'HIGH',
                $carePriority === 'CRITICAL'
                    ?
                    'IMMEDIATE'
                    :
                    'PRIORITY',
                [
                    'Health trend is worsening.',
                    'Trend confidence: ' .
                        $trendConfidence .
                        '%',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Limited Evidence
        |--------------------------------------------------------------------------
        */

        if (
            $isActiveResident
            &&
            $evidenceQuality === 'LIMITED'
        ) {

            $this->addRecommendation(
                $recommendations,
                'additional_clinical_data',
                'Additional Clinical Data',
                'Obtain additional repeat observations to strengthen the clinical evidence available to the AI system.',
                $carePriority === 'CRITICAL'
                    ?
                    'CRITICAL'
                    :
                    'HIGH',
                $carePriority === 'CRITICAL'
                    ?
                    'IMMEDIATE'
                    :
                    'PRIORITY',
                [
                    'Predictive evidence quality is LIMITED.',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Step 52.3
        | Recommendation Prioritization Intelligence
        |--------------------------------------------------------------------------
        |
        | Each recommendation receives an explainable clinical priority score.
        |
        | The score does NOT diagnose or automatically execute care.
        | It determines which recommendation should be reviewed first.
        |
        */

        $recommendations =
            $this->prioritizeRecommendations(
                $recommendations,
                $carePriority,
                $clinicalSeverity,
                $deteriorationRisk,
                $trendDirection,
                $evidenceQuality,
                $primaryDriver,
                $dominantDrivers,
                $isActiveResident
            );

        

        /*
        |--------------------------------------------------------------------------
        | Step 52.4
        | Care Recommendation Execution Readiness
        |--------------------------------------------------------------------------
        |
        | Convert ranked AI recommendations into workflow-ready recommendations.
        |
        | IMPORTANT:
        | This does NOT create nurse tasks.
        | This does NOT automatically execute clinical actions.
        | It only determines whether the recommendation is ready to enter
        | a human-reviewed clinical workflow.
        |
        */

        $recommendations =
            $this->attachExecutionReadiness(
                $recommendations,
                $isActiveResident,
                $clinicalSeverity,
                $deteriorationRisk,
                $trendDirection,
                $evidenceQuality
            );


        
        /*
        |--------------------------------------------------------------------------
        | Step 52.6
        | AI Care Workflow Task Proposal
        |--------------------------------------------------------------------------
        |
        | Convert execution-ready recommendations into structured workflow
        | proposals.
        |
        | IMPORTANT:
        | These are proposals only.
        | No NurseTask or clinical workflow record is created automatically.
        |
        */

        $workflowTaskProposals = $this->buildWorkflowTaskProposals(
            $resident,
            $recommendations
        );



        /*
        |--------------------------------------------------------------------------
        | Care Focus
        |--------------------------------------------------------------------------
        */

        $primaryCareFocus =
            $primaryDriver
            ?
            strtolower(
                (string) $primaryDriver
            )
            :
            'general_monitoring';

        /*
        |--------------------------------------------------------------------------
        | Care Summary
        |--------------------------------------------------------------------------
        */

        $careSummary =
            $this->buildCareSummary(
                $carePriority,
                $clinicalSeverity,
                $deteriorationRisk,
                $trendDirection,
                $primaryCareFocus,
                $evidenceQuality,
                $isActiveResident,
                $residentStatus
            );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Confidence
        |--------------------------------------------------------------------------
        */

        $recommendationConfidence =
            $this->calculateRecommendationConfidence(
                $predictionConfidence,
                $trendConfidence,
                $evidenceQuality
            );

        /*
        |--------------------------------------------------------------------------
        | Supporting Evidence
        |--------------------------------------------------------------------------
        */

        $supportingEvidence = [];

        foreach (
            $recommendations
            as $recommendation
        ) {

            foreach (
                (
                    $recommendation[
                        'supporting_evidence'
                    ]
                    ?? []
                )
                as $evidence
            ) {

                if (
                    is_string($evidence)
                    &&
                    trim($evidence) !== ''
                    &&
                    !in_array(
                        $evidence,
                        $supportingEvidence,
                        true
                    )
                ) {

                    $supportingEvidence[] =
                        $evidence;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Flags
        |--------------------------------------------------------------------------
        */

        $safetyFlags = [];

        if (!$isActiveResident) {

            $safetyFlags[] =
                'Resident is not currently active in care.';

            $safetyFlags[] =
                'Do not initiate new active-care actions without confirming resident status.';
        }

        if (
            $isActiveResident
            &&
            $clinicalSeverity === 'CRITICAL'
        ) {

            $safetyFlags[] =
                'Resident is currently clinically critical.';
        }

        if (
            $isActiveResident
            &&
            $escalationStatus ===
                'URGENT CLINICAL REVIEW'
        ) {

            $safetyFlags[] =
                'Urgent clinical review is required.';
        }

        if (
            $isActiveResident
            &&
            $trendDirection === 'WORSENING'
        ) {

            $safetyFlags[] =
                'Resident health trend is worsening.';
        }

        if (
            $isActiveResident
            &&
            $evidenceQuality === 'LIMITED'
        ) {

            $safetyFlags[] =
                'Predictive evidence is limited; additional observations are recommended.';
        }

        if (
            $isActiveResident
            &&
            $medicationAlerts->count() > 0
        ) {

            $safetyFlags[] =
                'Unresolved medication-related alert requires review.';
        }

        /*
        |--------------------------------------------------------------------------
        | Final Step 52.2 Response
        |--------------------------------------------------------------------------
        */

        return [

            'resident_id' =>
                $resident->id,

            'resident_name' =>
                $this->residentName(
                    $resident
                ),

            'resident_status' =>
                $resident->status
                ?? 'UNKNOWN',

            'operational_care_status' =>
                $operationalCareStatus,

            'active_care_eligible' =>
                $isActiveResident,

            'care_priority' =>
                $carePriority,

            'action_timing' =>
                $actionTiming,

            'recommendation_confidence' =>
                $recommendationConfidence,

            'primary_care_focus' =>
                $primaryCareFocus,

            'primary_driver_score' =>
                $primaryDriverScore,

            'dominant_care_drivers' =>
                $dominantDrivers,

            'medication_alert_count' =>
                $medicationAlerts->count(),

            'care_summary' =>
                $careSummary,

            'recommended_actions' =>
                $recommendations,

            'workflow_task_proposals' => 
                $workflowTaskProposals,

            'clinical_context' => [

                'clinical_severity' =>
                    $clinicalSeverity,

                'deterioration_risk' =>
                    $deteriorationRisk,

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
                    $trendDirection,

                'prediction_confidence' =>
                    $predictionConfidence,

                'trend_confidence' =>
                    $trendConfidence,

                'escalation_status' =>
                    $escalationStatus,

                'evidence_quality' =>
                    $evidenceQuality,
            ],

                'supporting_evidence' =>
                    $supportingEvidence,

            'safety_flags' =>
                array_values(
                    array_unique(
                        $safetyFlags
                    )
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Care Priority
    |--------------------------------------------------------------------------
    */

    protected function determineCarePriority(
        string $clinicalSeverity,
        string $deteriorationRisk,
        string $trendDirection,
        string $escalationStatus
    ): string {

        if (
            $clinicalSeverity === 'CRITICAL'
            ||
            $deteriorationRisk === 'CRITICAL'
            ||
            $escalationStatus ===
                'URGENT CLINICAL REVIEW'
        ) {

            return 'CRITICAL';
        }

        if (
            $clinicalSeverity === 'HIGH'
            ||
            $deteriorationRisk === 'HIGH'
            ||
            $trendDirection === 'WORSENING'
        ) {

            return 'HIGH';
        }

        if (
            in_array(
                $clinicalSeverity,
                [
                    'MEDIUM',
                    'MODERATE',
                ],
                true
            )
            ||
            in_array(
                $deteriorationRisk,
                [
                    'MEDIUM',
                    'MODERATE',
                ],
                true
            )
        ) {

            return 'MEDIUM';
        }

        return 'ROUTINE';
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Action Timing
    |--------------------------------------------------------------------------
    */

    protected function determineActionTiming(
        string $carePriority,
        string $predictiveActionTiming,
        string $escalationStatus
    ): string {

        if (
            $carePriority === 'CRITICAL'
            ||
            $predictiveActionTiming ===
                'IMMEDIATE'
            ||
            $escalationStatus ===
                'URGENT CLINICAL REVIEW'
        ) {

            return 'IMMEDIATE';
        }

        if (
            $carePriority === 'HIGH'
            ||
            in_array(
                $predictiveActionTiming,
                [
                    'URGENT',
                    'PRIORITY',
                ],
                true
            )
        ) {

            return 'PRIORITY';
        }

        if ($carePriority === 'MEDIUM') {

            return 'ENHANCED MONITORING';
        }

        return 'ROUTINE';
    }

    /*
    |--------------------------------------------------------------------------
    | Domain Priority
    |--------------------------------------------------------------------------
    */

    protected function domainPriority(
        string $domainLevel,
        string $carePriority
    ): string {

        if (
            $carePriority === 'CRITICAL'
            &&
            in_array(
                $domainLevel,
                [
                    'CRITICAL',
                    'HIGH',
                ],
                true
            )
        ) {

            return 'CRITICAL';
        }

        if ($domainLevel === 'CRITICAL') {

            return 'CRITICAL';
        }

        if ($domainLevel === 'HIGH') {

            return 'HIGH';
        }

        if (
            in_array(
                $domainLevel,
                [
                    'MEDIUM',
                    'MODERATE',
                ],
                true
            )
        ) {

            return
                $carePriority === 'CRITICAL'
                ?
                'HIGH'
                :
                'MEDIUM';
        }

        return 'ROUTINE';
    }

    /*
    |--------------------------------------------------------------------------
    | Domain Timing
    |--------------------------------------------------------------------------
    */

    protected function domainTiming(
        string $domainLevel,
        string $carePriority
    ): string {

        if (
            $domainLevel === 'CRITICAL'
            ||
            (
                $carePriority === 'CRITICAL'
                &&
                $domainLevel === 'HIGH'
            )
        ) {

            return 'IMMEDIATE';
        }

        if (
            $domainLevel === 'HIGH'
            ||
            (
                $carePriority === 'CRITICAL'
                &&
                in_array(
                    $domainLevel,
                    [
                        'MEDIUM',
                        'MODERATE',
                    ],
                    true
                )
            )
        ) {

            return 'PRIORITY';
        }

        if (
            in_array(
                $domainLevel,
                [
                    'MEDIUM',
                    'MODERATE',
                ],
                true
            )
        ) {

            return 'ENHANCED MONITORING';
        }

        return 'ROUTINE';
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Existing Recommendation Priority
    |--------------------------------------------------------------------------
    */

    protected function normalizePriority(
        string $priority
    ): string {

        return match (
            strtoupper(
                $priority
            )
        ) {

            'CRITICAL',
            'URGENT' =>
                'CRITICAL',

            'HIGH' =>
                'HIGH',

            'MEDIUM',
            'MODERATE',
            'NORMAL' =>
                'MEDIUM',

            default =>
                'ROUTINE',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Priority → Timing
    |--------------------------------------------------------------------------
    */

    protected function priorityToTiming(
        string $priority
    ): string {

        return match (
            strtoupper(
                $priority
            )
        ) {

            'CRITICAL',
            'URGENT' =>
                'IMMEDIATE',

            'HIGH' =>
                'PRIORITY',

            'MEDIUM',
            'MODERATE',
            'NORMAL' =>
                'ENHANCED MONITORING',

            default =>
                'ROUTINE',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Add Recommendation
    |--------------------------------------------------------------------------
    */

    protected function addRecommendation(
        array &$recommendations,
        string $code,
        string $category,
        string $action,
        string $priority,
        string $timing,
        array $supportingEvidence = []
    ): void {

        foreach (
            $recommendations
            as $recommendation
        ) {

            if (
                (
                    $recommendation[
                        'code'
                    ]
                    ?? null
                )
                === $code
            ) {

                return;
            }
        }

        $supportingEvidence =
            array_values(
                array_unique(
                    array_filter(
                        $supportingEvidence,
                        fn ($value) =>
                            is_string(
                                $value
                            )
                            &&
                            trim(
                                $value
                            )
                            !== ''
                    )
                )
            );

        $recommendations[] = [

            'code' =>
                $code,

            'category' =>
                $category,

            'action' =>
                $action,

            'priority' =>
                $priority,

            'timing' =>
                $timing,

            'supporting_evidence' =>
                $supportingEvidence,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sort Recommendations
    |--------------------------------------------------------------------------
    */

    protected function sortRecommendations(
        array $recommendations
    ): array {

        $priorityRank = [

            'CRITICAL' => 4,
            'HIGH' => 3,
            'MEDIUM' => 2,
            'ROUTINE' => 1,
        ];

        $timingRank = [

            'IMMEDIATE' => 5,
            'PRIORITY' => 4,
            'ENHANCED MONITORING' => 3,
            'ROUTINE' => 2,
            'NO ACTIVE CARE ACTION' => 1,
        ];

        usort(
            $recommendations,
            function (
                $a,
                $b
            ) use (
                $priorityRank,
                $timingRank
            ) {

                $aPriority =
                    strtoupper(
                        (string) (
                            $a[
                                'priority'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                $bPriority =
                    strtoupper(
                        (string) (
                            $b[
                                'priority'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                $priorityComparison =
                    (
                        $priorityRank[
                            $bPriority
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $priorityRank[
                            $aPriority
                        ]
                        ?? 0
                    );

                if (
                    $priorityComparison
                    !== 0
                ) {

                    return
                        $priorityComparison;
                }

                $aTiming =
                    strtoupper(
                        (string) (
                            $a[
                                'timing'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                $bTiming =
                    strtoupper(
                        (string) (
                            $b[
                                'timing'
                            ]
                            ?? 'ROUTINE'
                        )
                    );

                return
                    (
                        $timingRank[
                            $bTiming
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $timingRank[
                            $aTiming
                        ]
                        ?? 0
                    );
            }
        );

        return
            $recommendations;
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation Confidence
    |--------------------------------------------------------------------------
    */

    protected function calculateRecommendationConfidence(
        float $predictionConfidence,
        float $trendConfidence,
        string $evidenceQuality
    ): int {

        $confidence =
            (
                $predictionConfidence
                *
                0.70
            )
            +
            (
                $trendConfidence
                *
                0.30
            );

        $qualityAdjustment =
            match (
                $evidenceQuality
            ) {

                'HIGH',
                'STRONG' =>
                    5,

                'MODERATE' =>
                    0,

                'LIMITED' =>
                    -10,

                default =>
                    -5,
            };

        $confidence +=
            $qualityAdjustment;

        return
            (int)
            round(
                max(
                    0,
                    min(
                        100,
                        $confidence
                    )
                )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Build Care Summary
    |--------------------------------------------------------------------------
    */

    protected function buildCareSummary(
        string $carePriority,
        string $clinicalSeverity,
        string $deteriorationRisk,
        string $trendDirection,
        string $primaryCareFocus,
        string $evidenceQuality,
        bool $isActiveResident,
        string $residentStatus
    ): string {

        if (!$isActiveResident) {

            return
                'Resident status is ' .
                strtolower(
                    $residentStatus
                ) .
                '. Clinical intelligence is retained for historical review, but no new active-care action should be initiated without confirming current resident status.';
        }

        if ($carePriority === 'CRITICAL') {

            return sprintf(
                'Resident requires immediate care attention. Current clinical severity is %s, deterioration risk is %s, trend is %s, and the primary care focus is %s. Predictive evidence quality is %s.',
                strtolower(
                    $clinicalSeverity
                ),
                strtolower(
                    $deteriorationRisk
                ),
                strtolower(
                    $trendDirection
                ),
                $primaryCareFocus,
                strtolower(
                    $evidenceQuality
                )
            );
        }

        if ($carePriority === 'HIGH') {

            return sprintf(
                'Resident requires priority care monitoring. Deterioration risk is %s with a %s trend. Primary care focus is %s.',
                strtolower(
                    $deteriorationRisk
                ),
                strtolower(
                    $trendDirection
                ),
                $primaryCareFocus
            );
        }

        if ($carePriority === 'MEDIUM') {

            return sprintf(
                'Resident requires enhanced monitoring. Current deterioration risk is %s and the primary care focus is %s.',
                strtolower(
                    $deteriorationRisk
                ),
                $primaryCareFocus
            );
        }

        return
            'No immediate predictive care escalation identified. Continue routine monitoring according to the existing care plan.';
    }



    /*
    |--------------------------------------------------------------------------
    | Normalize Existing Recommendation Key
    |--------------------------------------------------------------------------
    */

    protected function normalizeRecommendationKey(
        string $type,
        string $action
    ): string {

        $combined =
            strtolower(
                $type .
                ' ' .
                $action
            );

        if (
            str_contains($combined, 'blood pressure')
            ||
            str_contains($combined, 'hypertension')
            ||
            str_contains($combined, 'cardiovascular')
        ) {
            return 'cardiovascular';
        }

        if (
            str_contains($combined, 'oxygen')
            ||
            str_contains($combined, 'respiratory')
        ) {
            return 'respiratory';
        }

        if (
            str_contains($combined, 'glucose')
            ||
            str_contains($combined, 'diabetes')
            ||
            str_contains($combined, 'metabolic')
        ) {
            return 'metabolic';
        }

        if (
            str_contains($combined, 'infection')
            ||
            str_contains($combined, 'temperature')
            ||
            str_contains($combined, 'fever')
        ) {
            return 'infection';
        }

        if (
            str_contains($combined, 'medication')
            ||
            str_contains($combined, 'medicine')
            ||
            str_contains($combined, 'drug')
        ) {
            return 'medication';
        }

        if (
            str_contains($combined, 'clinical assessment')
            ||
            str_contains($combined, 'physician')
            ||
            str_contains($combined, 'clinical review')
        ) {
            return 'overall_clinical_review';
        }

        return
            'general_' .
            substr(
                md5(
                    strtolower(
                        trim(
                            $type .
                            '|' .
                            $action
                        )
                    )
                ),
                0,
                10
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Check Whether Recommendation Domain Already Exists
    |--------------------------------------------------------------------------
    */

    protected function recommendationAlreadyCovered(
        array $recommendations,
        string $normalizedKey
    ): bool {

        $codeMap = [

            'cardiovascular' => [
                'cardiovascular_monitoring',
            ],

            'respiratory' => [
                'respiratory_monitoring',
            ],

            'metabolic' => [
                'metabolic_monitoring',
            ],

            'infection' => [
                'infection_monitoring',
            ],

            'medication' => [
                'medication_review',
                'medication_alert_review',
            ],

            'overall_clinical_review' => [
                'overall_clinical_review',
            ],
        ];

        if (!isset($codeMap[$normalizedKey])) {
            return false;
        }

        foreach ($recommendations as $recommendation) {

            $code =
                $recommendation['code']
                ?? '';

            if (
                in_array(
                    $code,
                    $codeMap[$normalizedKey],
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Recommendation Category
    |--------------------------------------------------------------------------
    */

    protected function existingRecommendationCategory(
        string $key,
        string $fallback
    ): string {

        return match ($key) {

            'cardiovascular' =>
                'Cardiovascular Monitoring',

            'respiratory' =>
                'Respiratory Monitoring',

            'metabolic' =>
                'Metabolic Monitoring',

            'infection' =>
                'Infection Monitoring',

            'medication' =>
                'Medication Review',

            'overall_clinical_review' =>
                'Overall Clinical Review',

            default =>
                $fallback
                ?: 'Clinical Recommendation',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation Priority Rank
    |--------------------------------------------------------------------------
    */

    protected function priorityRank(
        string $priority
    ): int {

        return match (
            strtoupper($priority)
        ) {

            'CRITICAL' => 4,

            'HIGH' => 3,

            'MEDIUM' => 2,

            default => 1,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Step 52.3
    | Recommendation Prioritization Intelligence
    |--------------------------------------------------------------------------
    */

    protected function prioritizeRecommendations(
        array $recommendations,
        string $carePriority,
        string $clinicalSeverity,
        string $deteriorationRisk,
        string $trendDirection,
        string $evidenceQuality,
        ?string $primaryDriver,
        array $dominantDrivers,
        bool $isActiveResident
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Non-Active Resident Guardrail
        |--------------------------------------------------------------------------
        |
        | Historical recommendations must never appear as active priority items.
        |
        */

        if (!$isActiveResident) {

            foreach (
                $recommendations
                as $index => $recommendation
            ) {

                $recommendations[$index]['priority_score'] =
                    0;

                $recommendations[$index]['priority_band'] =
                    'HISTORICAL';

                $recommendations[$index]['priority_reasoning'] = [
                    'Resident is not currently active in care.',
                    'Recommendation retained for historical review only.',
                ];
            }

            foreach (
                $recommendations
                as $index => $recommendation
            ) {

                $recommendations[$index]['priority_rank'] =
                    $index + 1;
            }

            return $recommendations;
        }

        /*
        |--------------------------------------------------------------------------
        | Score Every Recommendation
        |--------------------------------------------------------------------------
        */

        foreach (
            $recommendations
            as $index => $recommendation
        ) {

            $score = 0;

            $reasoning = [];

            $code =
                strtolower(
                    (string) (
                        $recommendation['code']
                        ?? ''
                    )
                );

            $priority =
                strtoupper(
                    (string) (
                        $recommendation['priority']
                        ?? 'ROUTINE'
                    )
                );

            $timing =
                strtoupper(
                    (string) (
                        $recommendation['timing']
                        ?? 'ROUTINE'
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | A. Recommendation Clinical Priority
            |--------------------------------------------------------------------------
            */

            switch ($priority) {

                case 'CRITICAL':

                    $score += 60;

                    $reasoning[] =
                        'Recommendation is classified as CRITICAL.';

                    break;

                case 'HIGH':

                    $score += 45;

                    $reasoning[] =
                        'Recommendation is classified as HIGH priority.';

                    break;

                case 'MEDIUM':

                    $score += 30;

                    $reasoning[] =
                        'Recommendation is classified as MEDIUM priority.';

                    break;

                default:

                    $score += 10;

                    $reasoning[] =
                        'Recommendation is classified as routine.';
                    break;
            }

            /*
            |--------------------------------------------------------------------------
            | B. Action Timing
            |--------------------------------------------------------------------------
            */

            switch ($timing) {

                case 'IMMEDIATE':

                    $score += 20;

                    $reasoning[] =
                        'Immediate action timing increases priority.';

                    break;

                case 'PRIORITY':

                    $score += 12;

                    $reasoning[] =
                        'Priority action timing increases urgency.';

                    break;

                case 'ENHANCED MONITORING':

                    $score += 6;

                    $reasoning[] =
                        'Enhanced monitoring is required.';

                    break;
            }

            /*
            |--------------------------------------------------------------------------
            | C. Current Clinical Severity
            |--------------------------------------------------------------------------
            */

            if ($clinicalSeverity === 'CRITICAL') {

                $score += 8;

                $reasoning[] =
                    'Resident is currently clinically critical.';

            } elseif ($clinicalSeverity === 'HIGH') {

                $score += 5;

                $reasoning[] =
                    'Resident currently has high clinical severity.';
            }

            /*
            |--------------------------------------------------------------------------
            | D. Future Deterioration Risk
            |--------------------------------------------------------------------------
            */

            if ($deteriorationRisk === 'CRITICAL') {

                $score += 7;

                $reasoning[] =
                    'Critical deterioration risk increases recommendation priority.';

            } elseif ($deteriorationRisk === 'HIGH') {

                $score += 5;

                $reasoning[] =
                    'High deterioration risk increases recommendation priority.';

            } elseif (
                in_array(
                    $deteriorationRisk,
                    [
                        'MEDIUM',
                        'MODERATE',
                    ],
                    true
                )
            ) {

                $score += 2;
            }

            /*
            |--------------------------------------------------------------------------
            | E. Worsening Trend
            |--------------------------------------------------------------------------
            */

            if ($trendDirection === 'WORSENING') {

                $score += 5;

                $reasoning[] =
                    'Worsening health trend increases recommendation priority.';
            }

            /*
            |--------------------------------------------------------------------------
            | F. Clinical Driver Relevance
            |--------------------------------------------------------------------------
            */

            $recommendationDomain =
                $this->recommendationDomain(
                    $code
                );

            $normalizedPrimaryDriver =
                strtolower(
                    (string) $primaryDriver
                );

            $normalizedDominantDrivers =
                array_map(
                    fn ($driver) =>
                        strtolower(
                            (string) $driver
                        ),
                    $dominantDrivers
                );

            if (
                $recommendationDomain
                &&
                $recommendationDomain ===
                    $normalizedPrimaryDriver
            ) {

                $score += 8;

                $reasoning[] =
                    ucfirst(
                        $recommendationDomain
                    ) .
                    ' is the primary deterioration driver.';

            } elseif (
                $recommendationDomain
                &&
                in_array(
                    $recommendationDomain,
                    $normalizedDominantDrivers,
                    true
                )
            ) {

                $score += 5;

                $reasoning[] =
                    ucfirst(
                        $recommendationDomain
                    ) .
                    ' is a dominant deterioration driver.';
            }

            /*
            |--------------------------------------------------------------------------
            | G. Special Recommendation Intelligence
            |--------------------------------------------------------------------------
            */

            if (
                $code ===
                    'overall_clinical_review'
            ) {

                $score += 10;

                $reasoning[] =
                    'Overall clinical review coordinates the complete care response.';
            }

            /*
            |--------------------------------------------------------------------------
            | Limited Evidence
            |--------------------------------------------------------------------------
            |
            | Limited evidence should prioritize collection of more observations.
            |
            | It should NOT artificially boost unrelated clinical interventions.
            |
            */

            if (
                $code ===
                    'additional_clinical_data'
                &&
                $evidenceQuality === 'LIMITED'
            ) {

                $score += 10;

                $reasoning[] =
                    'Limited evidence makes additional clinical observations a priority.';
            }

            /*
            |--------------------------------------------------------------------------
            | Medication Alert
            |--------------------------------------------------------------------------
            */

            if (
                $code ===
                    'medication_alert_review'
            ) {

                $score += 8;

                $reasoning[] =
                    'Unresolved medication alert requires dedicated review.';
            }

            /*
            |--------------------------------------------------------------------------
            | Increased Monitoring
            |--------------------------------------------------------------------------
            */

            if (
                $code ===
                    'increased_monitoring'
                &&
                $trendDirection === 'WORSENING'
            ) {

                $score += 5;

                $reasoning[] =
                    'Worsening trend specifically supports increased monitoring.';
            }

            /*
            |--------------------------------------------------------------------------
            | Final Score
            |--------------------------------------------------------------------------
            */

            $score =
                max(
                    0,
                    min(
                        100,
                        $score
                    )
                );

            $recommendations[
                $index
            ]['priority_score'] =
                $score;

            $recommendations[
                $index
            ]['priority_band'] =
                $this->priorityBand(
                    $score
                );

            $recommendations[
                $index
            ]['priority_reasoning'] =
                array_values(
                    array_unique(
                        $reasoning
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Highest Score First
        |--------------------------------------------------------------------------
        */

        usort(
            $recommendations,
            function (
                $a,
                $b
            ) {

                $scoreComparison =
                    (
                        $b[
                            'priority_score'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'priority_score'
                        ]
                        ?? 0
                    );

                if (
                    $scoreComparison !== 0
                ) {

                    return
                        $scoreComparison;
                }

                /*
                * If scores match, keep deterministic ordering
                * using recommendation code.
                */

                $codeRank = [
                    'overall_clinical_review' => 100,
                    'medication_alert_review' => 95,
                    'increased_monitoring' => 90,
                    'additional_clinical_data' => 85,
                    'respiratory_monitoring' => 80,
                    'cardiovascular_monitoring' => 75,
                    'metabolic_monitoring' => 70,
                    'infection_monitoring' => 65,
                    'medication_review' => 60,
                ];

                $aCode =
                    (string) (
                        $a['code']
                        ?? ''
                    );

                $bCode =
                    (string) (
                        $b['code']
                        ?? ''
                    );

                $aCodeRank =
                    $codeRank[$aCode]
                    ?? 0;

                $bCodeRank =
                    $codeRank[$bCode]
                    ?? 0;

                if ($aCodeRank !== $bCodeRank) {
                    return
                        $bCodeRank
                        <=>
                        $aCodeRank;
                }

                return strcmp(
                    $aCode,
                    $bCode
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Assign Priority Rank
        |--------------------------------------------------------------------------
        */

        foreach (
            $recommendations
            as $index => $recommendation
        ) {

            $recommendations[
                $index
            ]['priority_rank'] =
                $index + 1;
        }

        return
            $recommendations;
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Recommendation Clinical Domain
    |--------------------------------------------------------------------------
    */

    protected function recommendationDomain(
        string $code
    ): ?string {

        $code =
            strtolower(
                $code
            );

        if (
            str_contains(
                $code,
                'cardiovascular'
            )
        ) {

            return
                'cardiovascular';
        }

        if (
            str_contains(
                $code,
                'respiratory'
            )
        ) {

            return
                'respiratory';
        }

        if (
            str_contains(
                $code,
                'metabolic'
            )
        ) {

            return
                'metabolic';
        }

        if (
            str_contains(
                $code,
                'infection'
            )
        ) {

            return
                'infection';
        }

        if (
            str_contains(
                $code,
                'medication'
            )
        ) {

            return
                'medication';
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Recommendation Score Into Priority Band
    |--------------------------------------------------------------------------
    */

    protected function priorityBand(
        int $score
    ): string {

        if ($score >= 90) {

            return
                'IMMEDIATE';
        }

        if ($score >= 75) {

            return
                'URGENT';
        }

        if ($score >= 55) {

            return
                'HIGH';
        }

        if ($score >= 35) {

            return
                'MODERATE';
        }

        return
            'ROUTINE';
    }


    /*
    |--------------------------------------------------------------------------
    | Step 52.4
    | Attach Execution Readiness
    |--------------------------------------------------------------------------
    */

    protected function attachExecutionReadiness(
        array $recommendations,
        bool $isActiveResident,
        string $clinicalSeverity,
        string $deteriorationRisk,
        string $trendDirection,
        string $evidenceQuality
    ): array {

        foreach (
            $recommendations
            as $index => $recommendation
        ) {

            $code =
                strtolower(
                    (string) (
                        $recommendation['code']
                        ?? ''
                    )
                );

            $priority =
                strtoupper(
                    (string) (
                        $recommendation['priority']
                        ?? 'ROUTINE'
                    )
                );

            $priorityBand =
                strtoupper(
                    (string) (
                        $recommendation['priority_band']
                        ?? 'ROUTINE'
                    )
                );

            $priorityScore =
                (int) (
                    $recommendation['priority_score']
                    ?? 0
                );

            /*
            |--------------------------------------------------------------------------
            | Non-Active Resident Safety Guardrail
            |--------------------------------------------------------------------------
            */

            if (!$isActiveResident) {

                $recommendations[$index]['execution_ready'] =
                    false;

                $recommendations[$index]['execution_status'] =
                    'BLOCKED_NON_ACTIVE_RESIDENT';

                $recommendations[$index]['execution_type'] =
                    'HISTORICAL_REVIEW';

                $recommendations[$index]['workflow_target'] =
                    'NONE';

                $recommendations[$index]['requires_acknowledgement'] =
                    false;

                $recommendations[$index]['requires_doctor_review'] =
                    false;

                $recommendations[$index]['human_review_required'] =
                    true;

                $recommendations[$index]['auto_execution_allowed'] =
                    false;

                $recommendations[$index]['suggested_due_minutes'] =
                    null;

                $recommendations[$index]['execution_reason'] =
                    'Resident is not currently active in care. Recommendation is retained for historical review only.';

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Determine Execution Type
            |--------------------------------------------------------------------------
            */

            $executionType =
                $this->recommendationExecutionType(
                    $code
                );

            /*
            |--------------------------------------------------------------------------
            | Determine Workflow Target
            |--------------------------------------------------------------------------
            */

            $workflowTarget =
                $this->recommendationWorkflowTarget(
                    $code,
                    $priority,
                    $clinicalSeverity
                );

            /*
            |--------------------------------------------------------------------------
            | Doctor Review Requirement
            |--------------------------------------------------------------------------
            */

            $requiresDoctorReview =
                $this->recommendationRequiresDoctorReview(
                    $code,
                    $priority,
                    $clinicalSeverity,
                    $deteriorationRisk
                );

            /*
            |--------------------------------------------------------------------------
            | Suggested Completion Window
            |--------------------------------------------------------------------------
            */

            $suggestedDueMinutes =
                $this->recommendationDueMinutes(
                    $code,
                    $priorityBand,
                    $priorityScore,
                    $trendDirection
                );

            /*
            |--------------------------------------------------------------------------
            | Execution Readiness
            |--------------------------------------------------------------------------
            |
            | Recommendations may be ready for workflow preparation,
            | but they must NEVER be automatically executed.
            |
            */

            $executionReady =
                $this->isRecommendationExecutionReady(
                    $code,
                    $priorityScore,
                    $priorityBand
                );

            /*
            |--------------------------------------------------------------------------
            | Additional Data Recommendation
            |--------------------------------------------------------------------------
            |
            | This recommendation gathers evidence rather than representing
            | direct treatment.
            |
            */

            if (
                $code === 'additional_clinical_data'
                &&
                $evidenceQuality === 'LIMITED'
            ) {

                $executionType =
                    'CLINICAL_OBSERVATION';

                $workflowTarget =
                    'NURSE';

                $requiresDoctorReview =
                    false;
            }

            /*
            |--------------------------------------------------------------------------
            | Final Execution Metadata
            |--------------------------------------------------------------------------
            */

            $recommendations[$index]['execution_ready'] =
                $executionReady;

            $recommendations[$index]['execution_status'] =
                $executionReady
                ? 'READY_FOR_HUMAN_REVIEW'
                : 'MONITOR_ONLY';

            $recommendations[$index]['execution_type'] =
                $executionType;

            $recommendations[$index]['workflow_target'] =
                $workflowTarget;

            $recommendations[$index]['requires_acknowledgement'] =
                $executionReady;

            $recommendations[$index]['requires_doctor_review'] =
                $requiresDoctorReview;

            $recommendations[$index]['human_review_required'] =
                true;

            /*
            |--------------------------------------------------------------------------
            | Clinical Safety Guardrail
            |--------------------------------------------------------------------------
            |
            | No AI recommendation can automatically execute treatment.
            |
            */

            $recommendations[$index]['auto_execution_allowed'] =
                false;

            $recommendations[$index]['suggested_due_minutes'] =
                $suggestedDueMinutes;

            $recommendations[$index]['execution_reason'] =
                $this->buildExecutionReason(
                    $executionReady,
                    $priorityBand,
                    $workflowTarget,
                    $requiresDoctorReview
                );
        }

        return $recommendations;
    }


    /*
    |--------------------------------------------------------------------------
    | Recommendation Execution Type
    |--------------------------------------------------------------------------
    */

    protected function recommendationExecutionType(
        string $code
    ): string {

        return match ($code) {

            'overall_clinical_review' =>
                'CLINICAL_REVIEW',

            'additional_clinical_data' =>
                'CLINICAL_OBSERVATION',

            'respiratory_monitoring',
            'cardiovascular_monitoring',
            'metabolic_monitoring',
            'infection_monitoring',
            'increased_monitoring' =>
                'MONITORING_TASK',

            'medication_alert_review',
            'medication_review' =>
                'MEDICATION_REVIEW',

            default =>
                'CLINICAL_RECOMMENDATION',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Recommendation Workflow Target
    |--------------------------------------------------------------------------
    */

    protected function recommendationWorkflowTarget(
        string $code,
        string $priority,
        string $clinicalSeverity
    ): string {

        if ($code === 'overall_clinical_review') {
            return 'NURSE_AND_DOCTOR';
        }

        if (
            in_array(
                $code,
                [
                    'medication_alert_review',
                    'medication_review',
                ],
                true
            )
        ) {
            return 'NURSE_AND_DOCTOR';
        }

        if (
            $priority === 'CRITICAL'
            &&
            $clinicalSeverity === 'CRITICAL'
        ) {
            return 'NURSE_AND_DOCTOR';
        }

        return 'NURSE';
    }


    /*
    |--------------------------------------------------------------------------
    | Doctor Review Requirement
    |--------------------------------------------------------------------------
    */

    protected function recommendationRequiresDoctorReview(
        string $code,
        string $priority,
        string $clinicalSeverity,
        string $deteriorationRisk
    ): bool {

        if ($code === 'overall_clinical_review') {
            return true;
        }

        if (
            in_array(
                $code,
                [
                    'medication_alert_review',
                    'medication_review',
                ],
                true
            )
        ) {
            return true;
        }

        if (
            $priority === 'CRITICAL'
            &&
            $clinicalSeverity === 'CRITICAL'
        ) {
            return true;
        }

        if ($deteriorationRisk === 'CRITICAL') {
            return true;
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Suggested Recommendation Due Time
    |--------------------------------------------------------------------------
    */

    protected function recommendationDueMinutes(
        string $code,
        string $priorityBand,
        int $priorityScore,
        string $trendDirection
    ): ?int {

        if ($code === 'overall_clinical_review') {
            return 15;
        }

        if ($code === 'additional_clinical_data') {
            return 15;
        }

        if (
            $priorityBand === 'IMMEDIATE'
            ||
            $priorityScore >= 90
        ) {
            return 15;
        }

        if (
            $priorityBand === 'URGENT'
            ||
            $priorityScore >= 75
        ) {
            return 30;
        }

        if (
            $trendDirection === 'WORSENING'
            &&
            $priorityScore >= 55
        ) {
            return 30;
        }

        if (
            $priorityBand === 'HIGH'
            ||
            $priorityScore >= 55
        ) {
            return 60;
        }

        if ($priorityBand === 'MODERATE') {
            return 240;
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Determine Execution Readiness
    |--------------------------------------------------------------------------
    */

    protected function isRecommendationExecutionReady(
        string $code,
        int $priorityScore,
        string $priorityBand
    ): bool {

        if (
            in_array(
                $priorityBand,
                [
                    'IMMEDIATE',
                    'URGENT',
                    'HIGH',
                ],
                true
            )
        ) {
            return true;
        }

        if ($priorityScore >= 55) {
            return true;
        }

        if ($code === 'overall_clinical_review') {
            return true;
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Build Execution Reason
    |--------------------------------------------------------------------------
    */

    protected function buildExecutionReason(
        bool $executionReady,
        string $priorityBand,
        string $workflowTarget,
        bool $requiresDoctorReview
    ): string {

        if (!$executionReady) {
            return
                'Recommendation remains available for monitoring but does not currently meet the workflow execution-readiness threshold.';
        }

        $message =
            'Recommendation is ready for human-reviewed workflow based on its '
            .
            strtolower($priorityBand)
            .
            ' priority level.';

        if ($workflowTarget === 'NURSE_AND_DOCTOR') {

            $message .=
                ' Nurse assessment and doctor review are indicated by the workflow rules.';

        } elseif ($workflowTarget === 'NURSE') {

            $message .=
                ' Nurse workflow review is indicated.';
        }

        if (
            $requiresDoctorReview
            &&
            $workflowTarget !== 'NURSE_AND_DOCTOR'
        ) {

            $message .=
                ' Doctor review is also required.';
        }

        return $message;
    }

                  

    /*
    |--------------------------------------------------------------------------
    | Resident Display Name
    |--------------------------------------------------------------------------
    */

    protected function residentName(
        Resident $resident
    ): string {

        return
            $resident->full_name
            ??
            $resident->name
            ??
            (
                'Resident '
                .
                $resident->id
            );
    }



    /**
     *--------------------------------------------------------------------------
    * Step 52.6
    * Build AI Care Workflow Task Proposals
    *--------------------------------------------------------------------------
    *
    * Converts execution-ready care recommendations into structured workflow
    * proposals.
    *
    * SAFETY:
    * - Does not create database records.
    * - Does not automatically execute clinical actions.
    * - Requires human review before task creation.
    *
    */
    private function buildWorkflowTaskProposals(
        $resident,
        array $recommendations
    ): array {
        $proposals = [];

        /*
        |--------------------------------------------------------------------------
        | Resident Active-Care Guardrail
        |--------------------------------------------------------------------------
        */

        $residentStatus = strtoupper(
            trim((string) ($resident->status ?? 'UNKNOWN'))
        );

        if ($residentStatus !== 'ACTIVE') {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Convert Execution-Ready Recommendations
        |--------------------------------------------------------------------------
        */

        foreach ($recommendations as $recommendation) {

            /*
            |--------------------------------------------------------------------------
            | Only execution-ready recommendations may become proposals
            |--------------------------------------------------------------------------
            */

            $executionReady =
                (bool) ($recommendation['execution_ready'] ?? false);

            if (!$executionReady) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Auto-execution must never occur
            |--------------------------------------------------------------------------
            */

            $autoExecutionAllowed =
                (bool) (
                    $recommendation['auto_execution_allowed']
                    ?? false
                );

            if ($autoExecutionAllowed) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Workflow Target
            |--------------------------------------------------------------------------
            */

            $workflowTarget = strtoupper(
                (string) (
                    $recommendation['workflow_target']
                    ?? 'NURSE'
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Proposal Type
            |--------------------------------------------------------------------------
            */

            $proposalType = match ($workflowTarget) {

                'NURSE_AND_DOCTOR' =>
                    'MULTIDISCIPLINARY_CLINICAL_REVIEW',

                'DOCTOR' =>
                    'DOCTOR_REVIEW',

                'NURSE' =>
                    'NURSE_TASK',

                default =>
                    'CLINICAL_WORKFLOW_REVIEW',
            };

            /*
            |--------------------------------------------------------------------------
            | Suggested Due Time
            |--------------------------------------------------------------------------
            */

            $suggestedDueMinutes =
                $recommendation['suggested_due_minutes']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Task Title
            |--------------------------------------------------------------------------
            */

            $taskTitle = $this->buildWorkflowTaskTitle(
                $recommendation
            );

            /*
            |--------------------------------------------------------------------------
            | Human-readable task description
            |--------------------------------------------------------------------------
            */

            $taskDescription =
                $recommendation['action']
                ?? 'Review AI generated care recommendation.';

            /*
            |--------------------------------------------------------------------------
            | Proposed Workflow
            |--------------------------------------------------------------------------
            */

            $proposals[] = [

                'proposal_id' =>
                    'CARE-' .
                    $resident->id .
                    '-' .
                    strtoupper(
                        (string) (
                            $recommendation['code']
                            ?? 'ACTION'
                        )
                    ),

                'resident_id' =>
                    $resident->id,

                'resident_name' =>
                    $resident->full_name
                    ?? ('Resident ' . $resident->id),

                'source_recommendation_code' =>
                    $recommendation['code']
                    ?? null,

                'proposal_type' =>
                    $proposalType,

                'task_title' =>
                    $taskTitle,

                'task_description' =>
                    $taskDescription,

                'priority' =>
                    $recommendation['priority']
                    ?? 'NORMAL',

                'priority_score' =>
                    $recommendation['priority_score']
                    ?? 0,

                'priority_rank' =>
                    $recommendation['priority_rank']
                    ?? null,

                'priority_band' =>
                    $recommendation['priority_band']
                    ?? 'ROUTINE',

                'workflow_target' =>
                    $workflowTarget,

                'execution_type' =>
                    $recommendation['execution_type']
                    ?? 'CLINICAL_WORKFLOW',

                'suggested_due_minutes' =>
                    $suggestedDueMinutes,

                'requires_acknowledgement' =>
                    (bool) (
                        $recommendation['requires_acknowledgement']
                        ?? true
                    ),

                'requires_doctor_review' =>
                    (bool) (
                        $recommendation['requires_doctor_review']
                        ?? false
                    ),

                'human_review_required' =>
                    true,

                'auto_execution_allowed' =>
                    false,

                /*
                |--------------------------------------------------------------------------
                | Step 52.6 Safety State
                |--------------------------------------------------------------------------
                */

                'proposal_status' =>
                    'AWAITING_HUMAN_APPROVAL',

                'task_created' =>
                    false,

                'can_create_task' =>
                    true,

                /*
                |--------------------------------------------------------------------------
                | Traceability
                |--------------------------------------------------------------------------
                */

                'supporting_evidence' =>
                    $recommendation['supporting_evidence']
                    ?? [],

                'priority_reasoning' =>
                    $recommendation['priority_reasoning']
                    ?? [],

                'execution_reason' =>
                    $recommendation['execution_reason']
                    ?? null,

                'proposal_reason' =>
                    'Execution-ready AI care recommendation converted into a workflow proposal. Human approval is required before creating an operational task.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Keep proposal order aligned with recommendation priority
        |--------------------------------------------------------------------------
        */

        usort(
            $proposals,
            function ($a, $b) {

                $aRank =
                    $a['priority_rank']
                    ?? PHP_INT_MAX;

                $bRank =
                    $b['priority_rank']
                    ?? PHP_INT_MAX;

                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }

                return
                    ($b['priority_score'] ?? 0)
                    <=>
                    ($a['priority_score'] ?? 0);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Proposal Sequence
        |--------------------------------------------------------------------------
        */

        foreach ($proposals as $index => &$proposal) {
            $proposal['proposal_rank'] = $index + 1;
        }

        unset($proposal);

        return $proposals;
    }


    /**
     * Build workflow task title.
     */
    private function buildWorkflowTaskTitle(
        array $recommendation
    ): string {
        $code = strtolower(
            (string) (
                $recommendation['code']
                ?? ''
            )
        );

        return match ($code) {

            'overall_clinical_review' =>
                'AI Clinical Review',

            'additional_clinical_data' =>
                'Repeat Clinical Observations',

            'respiratory_monitoring' =>
                'Respiratory Monitoring',

            'cardiovascular_monitoring' =>
                'Blood Pressure Monitoring',

            'metabolic_monitoring' =>
                'Blood Glucose Monitoring',

            'infection_monitoring' =>
                'Temperature / Infection Monitoring',

            default =>
                $recommendation['category']
                ?? 'AI Care Recommendation Review',
        };
    }
}