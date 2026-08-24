<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationConditionEvidenceIntelligenceEngine
{
    public function analyze(?int $validationId = null): array
    {
        $validation = $validationId
            ? AIGovernanceStrategicPlanDecisionValidation::find($validationId)
            : AIGovernanceStrategicPlanDecisionValidation::latest('id')->first();

        if (!$validation) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_DECISION_VALIDATION_AVAILABLE',
                'message' => 'No AI governance strategic plan decision validation record is available for condition and evidence intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Validation State Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($validation->id);

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state'] ?? [];

        $stateConditionContext =
            $state['condition_context'] ?? [];

        $stateEvidenceContext =
            $state['evidence_context'] ?? [];

        $humanDecisionContext =
            $state['human_decision_context'] ?? [];

        $validatorContext =
            $state['validator_context'] ?? [];

        $completionContext =
            $state['completion_context'] ?? [];

        $reviewContext =
            $state['review_context'] ?? [];

        $governanceContext =
            $state['governance_context'] ?? [];

        $sourceContext =
            $state['source_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Validation Conditions
        |--------------------------------------------------------------------------
        */

        $validationConditions =
            is_array($validation->validation_conditions)
                ? $validation->validation_conditions
                : [];

        $conditionAnalysis = [];

        foreach ($validationConditions as $condition) {
            $status = strtoupper(
                (string) ($condition['condition_status'] ?? 'OPEN')
            );

            $priority = strtoupper(
                (string) ($condition['priority_level'] ?? 'ADVISORY')
            );

            $code =
                $condition['condition_code'] ?? 'UNKNOWN_CONDITION';

            $type =
                $condition['condition_type'] ?? 'GENERAL';

            $conditionOpen = !in_array(
                $status,
                [
                    'RESOLVED',
                    'CLOSED',
                    'COMPLETED',
                    'SATISFIED',
                    'FORMALLY_GOVERNED',
                ],
                true
            );

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                default => 25.0,
            };

            /*
            |--------------------------------------------------------------------------
            | Validation Blocking Classification
            |--------------------------------------------------------------------------
            */

            $blockingConditionCodes = [
                'FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',
                'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION_REQUIRED',
                'BLOCKING_DECISION_CONDITIONS_REMAIN',
                'DECISION_BLOCKING_EVIDENCE_REMAINS',
            ];

            $constrainingConditionCodes = [
                'HIGH_DECISION_RISK_REQUIRES_HUMAN_REVIEW',
            ];

            $conditionClassification = 'ADVISORY';

            if (
                $conditionOpen
                && in_array($code, $blockingConditionCodes, true)
            ) {
                $conditionClassification = 'BLOCKING';
            } elseif (
                $conditionOpen
                && in_array($code, $constrainingConditionCodes, true)
            ) {
                $conditionClassification = 'CONSTRAINING';
            } elseif (
                $conditionOpen
                && in_array($priority, ['CRITICAL', 'HIGH'], true)
            ) {
                $conditionClassification = 'CONSTRAINING';
            }

            $blocksGovernanceValidation =
                $conditionClassification === 'BLOCKING';

            $constrainsGovernanceValidation =
                $conditionClassification === 'CONSTRAINING';

            $conditionAnalysis[] = array_merge(
                $condition,
                [
                    'condition_open' =>
                        $conditionOpen,

                    'condition_classification' =>
                        $conditionClassification,

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_governance_validation' =>
                        $blocksGovernanceValidation,

                    'constrains_governance_validation' =>
                        $constrainsGovernanceValidation,

                    'requires_authorized_human_resolution' =>
                        (bool) (
                            $condition['requires_authorized_human_resolution']
                            ?? true
                        ),

                    'automatic_resolution_allowed' =>
                        false,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Condition Collections
        |--------------------------------------------------------------------------
        */

        $openConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_open'] ?? false) === true
            )
        );

        $resolvedConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_open'] ?? true) === false
            )
        );

        $blockingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_classification'] ?? null)
                    === 'BLOCKING'
            )
        );

        $constrainingConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_classification'] ?? null)
                    === 'CONSTRAINING'
            )
        );

        $advisoryConditions = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_classification'] ?? null)
                    === 'ADVISORY'
                    && ($condition['condition_open'] ?? false) === true
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Condition Severity Counts
        |--------------------------------------------------------------------------
        */

        $criticalOpenConditions = count(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'CRITICAL'
            )
        );

        $highOpenConditions = count(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'HIGH'
            )
        );

        $moderateOpenConditions = count(
            array_filter(
                $openConditions,
                fn ($condition) =>
                    strtoupper(
                        (string) ($condition['priority_level'] ?? '')
                    ) === 'MODERATE'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Condition Resolution Score
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            count($conditionAnalysis);

        $resolvedConditionCount =
            count($resolvedConditions);

        $conditionResolutionScore =
            $totalConditions > 0
                ? round(
                    ($resolvedConditionCount / $totalConditions) * 100,
                    2
                )
                : 100.0;

        /*
        |--------------------------------------------------------------------------
        | Condition Pressure
        |--------------------------------------------------------------------------
        */

        $conditionPressureTotal = array_sum(
            array_map(
                fn ($condition) =>
                    (float) ($condition['severity_weight'] ?? 0),
                $openConditions
            )
        );

        $conditionPressureMaximum =
            $totalConditions > 0
                ? $totalConditions * 100
                : 100;

        $validationConditionPressureScore = round(
            min(
                100,
                ($conditionPressureTotal / $conditionPressureMaximum) * 100
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Dominant Open Validation Condition
        |--------------------------------------------------------------------------
        */

        $dominantOpenValidationCondition = null;

        if (!empty($openConditions)) {
            usort(
                $openConditions,
                fn ($a, $b) =>
                    ($b['severity_weight'] ?? 0)
                    <=>
                    ($a['severity_weight'] ?? 0)
            );

            $dominantOpenValidationCondition =
                $openConditions[0] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Analysis
        |--------------------------------------------------------------------------
        */

        $validatedEvidence =
            is_array($validation->validated_evidence)
                ? $validation->validated_evidence
                : [];

        $evidenceAnalysis = [];

        foreach ($validatedEvidence as $evidence) {
            $status = strtoupper(
                (string) ($evidence['evidence_status'] ?? 'UNKNOWN')
            );

            $priority = strtoupper(
                (string) ($evidence['priority_level'] ?? 'ADVISORY')
            );

            $evidenceAvailable =
                ($evidence['evidence_available'] ?? false) === true
                || in_array(
                    $status,
                    [
                        'AVAILABLE',
                        'VALIDATED',
                        'SATISFIED',
                        'COMPLETE',
                    ],
                    true
                );

            $evidenceValidated =
                ($evidence['evidence_validated'] ?? false) === true
                || in_array(
                    $status,
                    [
                        'VALIDATED',
                        'SATISFIED',
                        'COMPLETE',
                    ],
                    true
                );

            /*
            |--------------------------------------------------------------------------
            | Existing Step 66.2 Evidence May Be Available + Validated
            |--------------------------------------------------------------------------
            */

            if (
                $evidenceAvailable
                && ($evidence['evidence_outstanding'] ?? false) === false
            ) {
                $evidenceValidated = true;
            }

            $evidenceOutstanding =
                !$evidenceAvailable
                || !$evidenceValidated;

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                default => 25.0,
            };

            $blocksGovernanceValidation =
                ($evidence['blocks_final_decision'] ?? false) === true
                && $evidenceOutstanding;

            $evidenceAnalysis[] = array_merge(
                $evidence,
                [
                    'evidence_available' =>
                        $evidenceAvailable,

                    'evidence_validated' =>
                        $evidenceValidated,

                    'evidence_outstanding' =>
                        $evidenceOutstanding,

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_governance_validation' =>
                        $blocksGovernanceValidation,

                    'requires_human_validation' =>
                        $evidenceOutstanding
                            ? (
                                (bool) (
                                    $evidence['human_validation_required']
                                    ?? true
                                )
                            )
                            : false,

                    'automatic_validation_allowed' =>
                        false,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Evidence Collections
        |--------------------------------------------------------------------------
        */

        $availableEvidence = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_available'] ?? false) === true
            )
        );

        $validatedEvidenceItems = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_validated'] ?? false) === true
            )
        );

        $outstandingValidationEvidence = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false) === true
            )
        );

        $blockingValidationEvidence = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['blocks_governance_validation'] ?? false)
                    === true
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Evidence Readiness
        |--------------------------------------------------------------------------
        */

        $totalEvidenceItems =
            count($evidenceAnalysis);

        $validatedEvidenceCount =
            count($validatedEvidenceItems);

        $validatedEvidenceReadinessScore =
            $totalEvidenceItems > 0
                ? round(
                    ($validatedEvidenceCount / $totalEvidenceItems) * 100,
                    2
                )
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Source Resolution Scores
        |--------------------------------------------------------------------------
        */

        $sourceConditionResolutionScore =
            (float) (
                $stateEvidenceContext['condition_resolution_score']
                ?? $validation->condition_resolution_score
                ?? 0
            );

        $sourceEvidenceResolutionScore =
            (float) (
                $stateEvidenceContext['evidence_resolution_score']
                ?? $validation->evidence_resolution_score
                ?? 0
            );

        $sourceCombinedResolutionScore =
            (float) (
                $stateEvidenceContext['combined_resolution_score']
                ?? $validation->combined_resolution_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Validation Evidence Sufficiency
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            ($humanDecisionContext['final_human_decision_recorded'] ?? false)
            === true;

        $decisionMadeByAuthorizedHuman =
            ($humanDecisionContext['decision_made_by_authorized_human'] ?? false)
            === true;

        $validatorAttributionComplete =
            ($validatorContext['validator_attribution_complete'] ?? false)
            === true;

        $validationMadeByAuthorizedHuman =
            ($validatorContext['validation_made_by_authorized_human'] ?? false)
            === true;

        $governanceValidationCompleted =
            ($validationState['governance_validation_completed'] ?? false)
            === true;

        $validationEvidenceSufficiency =
            count($outstandingValidationEvidence) === 0
                ? 'VALIDATION_EVIDENCE_CURRENTLY_AVAILABLE'
                : 'VALIDATION_EVIDENCE_REQUIREMENTS_OUTSTANDING';

        /*
        |--------------------------------------------------------------------------
        | Governance Validation Blocking Status
        |--------------------------------------------------------------------------
        */

        $blockingConditionCount =
            count($blockingConditions);

        $constrainingConditionCount =
            count($constrainingConditions);

        $blockingEvidenceCount =
            count($blockingValidationEvidence);

        if (!$finalHumanDecisionRecorded) {
            $governanceValidationBlockStatus =
                'GOVERNANCE_VALIDATION_BLOCKED_PENDING_FINAL_HUMAN_DECISION';
        } elseif (!$decisionMadeByAuthorizedHuman) {
            $governanceValidationBlockStatus =
                'GOVERNANCE_VALIDATION_BLOCKED_BY_HUMAN_DECISION_AUTHORITY';
        } elseif ($blockingConditionCount > 0) {
            $governanceValidationBlockStatus =
                'GOVERNANCE_VALIDATION_BLOCKED_BY_VALIDATION_CONDITIONS';
        } elseif ($blockingEvidenceCount > 0) {
            $governanceValidationBlockStatus =
                'GOVERNANCE_VALIDATION_BLOCKED_BY_EVIDENCE_REQUIREMENTS';
        } else {
            $governanceValidationBlockStatus =
                'NO_MATERIAL_VALIDATION_BLOCK_IDENTIFIED';
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Progression Readiness
        |--------------------------------------------------------------------------
        */

        if ($governanceValidationCompleted) {
            $validationProgressionReadiness =
                'GOVERNANCE_VALIDATION_COMPLETED';
        } elseif (!$finalHumanDecisionRecorded) {
            $validationProgressionReadiness =
                'AWAITING_AUTHORIZED_HUMAN_FINAL_DECISION';
        } elseif ($blockingConditionCount > 0) {
            $validationProgressionReadiness =
                'BLOCKING_VALIDATION_CONDITIONS_REQUIRE_HUMAN_GOVERNANCE';
        } elseif ($blockingEvidenceCount > 0) {
            $validationProgressionReadiness =
                'VALIDATION_EVIDENCE_REQUIREMENTS_REMAIN';
        } elseif (!$validatorAttributionComplete) {
            $validationProgressionReadiness =
                'AUTHORIZED_HUMAN_VALIDATOR_REQUIRED';
        } else {
            $validationProgressionReadiness =
                'ELIGIBLE_FOR_AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Combined Validation Condition / Evidence Score
        |--------------------------------------------------------------------------
        */

        $validationConditionEvidenceScore = round(
            (
                ($conditionResolutionScore * 0.40)
                + ($validatedEvidenceReadinessScore * 0.20)
                + ($sourceEvidenceResolutionScore * 0.15)
                + ($sourceCombinedResolutionScore * 0.15)
                + (
                    $finalHumanDecisionRecorded
                        ? 100
                        : 0
                ) * 0.10
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Validation Pressure Score
        |--------------------------------------------------------------------------
        */

        $validationPressureScore = round(
            min(
                100,
                (
                    ($validationConditionPressureScore * 0.45)
                    + (
                        (100 - $sourceEvidenceResolutionScore)
                        * 0.20
                    )
                    + (
                        (100 - $sourceCombinedResolutionScore)
                        * 0.20
                    )
                    + (
                        $finalHumanDecisionRecorded
                            ? 0
                            : 100
                    ) * 0.15
                )
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Validation Condition / Evidence Status
        |--------------------------------------------------------------------------
        */

        if ($criticalOpenConditions > 0) {
            $conditionEvidenceStatus =
                'CRITICAL_VALIDATION_REQUIREMENTS_OUTSTANDING';
        } elseif (
            $blockingConditionCount > 0
            || $blockingEvidenceCount > 0
            || !$finalHumanDecisionRecorded
        ) {
            $conditionEvidenceStatus =
                'MATERIAL_VALIDATION_CONDITION_EVIDENCE_BLOCK';
        } elseif ($constrainingConditionCount > 0) {
            $conditionEvidenceStatus =
                'VALIDATION_CONSTRAINTS_REMAIN';
        } else {
            $conditionEvidenceStatus =
                'VALIDATION_CONDITIONS_AND_EVIDENCE_CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Dominant Validation Restriction
        |--------------------------------------------------------------------------
        */

        $dominantValidationRestriction = null;

        if (!$finalHumanDecisionRecorded) {
            $dominantValidationRestriction = [
                'restriction_code' =>
                    'FINAL_AUTHORIZED_HUMAN_DECISION_REQUIRED',

                'restriction_type' =>
                    'HUMAN_DECISION',

                'severity' =>
                    'HIGH',

                'message' =>
                    'An explicitly authorized human strategic plan decision must be recorded before governance validation can progress.',
            ];
        } elseif ($dominantOpenValidationCondition) {
            $dominantValidationRestriction = [
                'restriction_code' =>
                    $dominantOpenValidationCondition['condition_code']
                    ?? 'VALIDATION_CONDITION',

                'restriction_type' =>
                    $dominantOpenValidationCondition['condition_type']
                    ?? 'VALIDATION_CONDITION',

                'severity' =>
                    $dominantOpenValidationCondition['priority_level']
                    ?? 'HIGH',

                'message' =>
                    $dominantOpenValidationCondition['condition']
                    ?? 'A material validation condition remains unresolved.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $conditionEvidenceFindings = [
            "Strategic plan governance validation condition and evidence intelligence is based on validation record {$validation->id}.",

            "{$totalConditions} validation condition(s) are represented.",

            count($openConditions)
                .' validation condition(s) remain open.',

            "{$resolvedConditionCount} validation condition(s) are currently resolved.",

            "{$blockingConditionCount} blocking governance-validation condition(s) remain active.",

            "{$constrainingConditionCount} constraining governance-validation condition(s) remain active.",

            "{$criticalOpenConditions} critical validation condition(s) remain open.",

            "{$highOpenConditions} high-priority validation condition(s) remain open.",

            "Current validation-condition resolution score is {$conditionResolutionScore}.",

            "Current validation-condition pressure score is {$validationConditionPressureScore}.",

            "{$totalEvidenceItems} validated-evidence item(s) are represented in the governance-validation package.",

            "{$validatedEvidenceCount} validation evidence item(s) are currently validated.",

            count($outstandingValidationEvidence)
                .' validation evidence item(s) remain outstanding.',

            "{$blockingEvidenceCount} validation evidence item(s) currently block governance-validation progression.",

            "Current validated-evidence readiness score is {$validatedEvidenceReadinessScore}.",

            "Source authorized-human decision condition resolution score is {$sourceConditionResolutionScore}.",

            "Source authorized-human decision evidence resolution score is {$sourceEvidenceResolutionScore}.",

            "Source combined condition/evidence resolution score is {$sourceCombinedResolutionScore}.",

            "Current validation condition/evidence intelligence score is {$validationConditionEvidenceScore}.",

            "Current validation pressure score is {$validationPressureScore}.",

            "Current governance-validation condition/evidence status is {$conditionEvidenceStatus}.",

            "Current governance-validation block status is {$governanceValidationBlockStatus}.",

            "Current validation progression readiness is {$validationProgressionReadiness}.",

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            'Governance validation condition and evidence intelligence remains advisory and does not resolve conditions, validate evidence, record the final human decision, or complete governance validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$finalHumanDecisionRecorded) {
            $managementPriorities[] =
                'Record the final strategic plan decision through an explicitly authorized human governance decision-maker before completed governance validation is considered.';
        }

        if (!$decisionMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure the final strategic plan decision remains attributable to an explicitly authorized human governance decision-maker.';
        }

        if ($blockingConditionCount > 0) {
            $managementPriorities[] =
                "Resolve or formally govern {$blockingConditionCount} blocking governance-validation condition(s) through authorized human governance.";
        }

        if ($constrainingConditionCount > 0) {
            $managementPriorities[] =
                "Reduce or formally govern {$constrainingConditionCount} constraining governance-validation condition(s) to improve validation readiness.";
        }

        if ($dominantOpenValidationCondition) {
            $managementPriorities[] =
                'Address the dominant open governance-validation condition '
                .($dominantOpenValidationCondition['condition_code']
                    ?? 'UNKNOWN')
                .' through authorized human governance.';
        }

        if (count($outstandingValidationEvidence) > 0) {
            $managementPriorities[] =
                'Complete outstanding governance-validation evidence review through authorized human governance.';
        }

        if (!$validatorAttributionComplete) {
            $managementPriorities[] =
                'Ensure any completed governance validation remains attributable to an explicitly identified authorized human governance validator.';
        }

        if (
            strtoupper(
                (string) (
                    $validationState['decision_risk_level']
                    ?? ''
                )
            ) === 'HIGH_HUMAN_DECISION_RISK'
        ) {
            $managementPriorities[] =
                'Maintain elevated authorized human governance oversight while strategic plan decision risk remains high.';
        }

        $managementPriorities[] =
            'Keep validation condition and evidence intelligence separate from final strategic plan decision authority and governance-validation authority.';

        $managementPriorities[] =
            'Preserve human decision authority, human validation authority, evidence quality, traceability, governance controls, safety controls, and strict authority separation throughout governance validation.';

        $managementPriorities =
            array_values(array_unique($managementPriorities));

        /*
        |--------------------------------------------------------------------------
        | Return Intelligence
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_CONDITION_EVIDENCE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_validation_id' =>
                $validation->id,

            'validation_code' =>
                $validation->validation_code,

            'strategic_plan_human_decision_id' =>
                $validation->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $validation->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $validation->strategic_plan_id,

            'strategic_snapshot_id' =>
                $validation->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $validation->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $validation->lifecycle_snapshot_id,

            'decision_scope' =>
                $validation->decision_scope,

            'resident_id' =>
                $validation->resident_id,

            /*
            |--------------------------------------------------------------------------
            | Condition / Evidence State
            |--------------------------------------------------------------------------
            */

            'condition_evidence_state' => [
                'condition_evidence_status' =>
                    $conditionEvidenceStatus,

                'governance_validation_block_status' =>
                    $governanceValidationBlockStatus,

                'validation_progression_readiness' =>
                    $validationProgressionReadiness,

                'validation_evidence_sufficiency' =>
                    $validationEvidenceSufficiency,

                'validation_condition_resolution_score' =>
                    $conditionResolutionScore,

                'validated_evidence_readiness_score' =>
                    $validatedEvidenceReadinessScore,

                'validation_condition_evidence_score' =>
                    $validationConditionEvidenceScore,

                'validation_condition_pressure_score' =>
                    $validationConditionPressureScore,

                'validation_pressure_score' =>
                    $validationPressureScore,

                'human_management_attention_required' =>
                    $validationPressureScore >= 50,

                'immediate_human_intervention_required' =>
                    $criticalOpenConditions > 0,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition Summary
            |--------------------------------------------------------------------------
            */

            'condition_summary' => [
                'total_validation_conditions' =>
                    $totalConditions,

                'open_validation_conditions' =>
                    count($openConditions),

                'resolved_validation_conditions' =>
                    $resolvedConditionCount,

                'blocking_validation_conditions' =>
                    $blockingConditionCount,

                'constraining_validation_conditions' =>
                    $constrainingConditionCount,

                'advisory_validation_conditions' =>
                    count($advisoryConditions),

                'critical_open_validation_conditions' =>
                    $criticalOpenConditions,

                'high_open_validation_conditions' =>
                    $highOpenConditions,

                'moderate_open_validation_conditions' =>
                    $moderateOpenConditions,

                'validation_condition_resolution_score' =>
                    $conditionResolutionScore,

                'validation_condition_pressure_score' =>
                    $validationConditionPressureScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Evidence Summary
            |--------------------------------------------------------------------------
            */

            'evidence_summary' => [
                'total_validation_evidence_items' =>
                    $totalEvidenceItems,

                'available_validation_evidence_items' =>
                    count($availableEvidence),

                'validated_validation_evidence_items' =>
                    $validatedEvidenceCount,

                'outstanding_validation_evidence_items' =>
                    count($outstandingValidationEvidence),

                'blocking_validation_evidence_items' =>
                    $blockingEvidenceCount,

                'validated_evidence_readiness_score' =>
                    $validatedEvidenceReadinessScore,

                'source_condition_resolution_score' =>
                    $sourceConditionResolutionScore,

                'source_evidence_resolution_score' =>
                    $sourceEvidenceResolutionScore,

                'source_combined_resolution_score' =>
                    $sourceCombinedResolutionScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Dominant Restriction
            |--------------------------------------------------------------------------
            */

            'dominant_validation_restriction' =>
                $dominantValidationRestriction,

            'dominant_open_validation_condition' =>
                $dominantOpenValidationCondition,

            /*
            |--------------------------------------------------------------------------
            | Detailed Condition Analysis
            |--------------------------------------------------------------------------
            */

            'blocking_validation_conditions' =>
                $blockingConditions,

            'constraining_validation_conditions' =>
                $constrainingConditions,

            'advisory_validation_conditions' =>
                $advisoryConditions,

            'resolved_validation_conditions' =>
                $resolvedConditions,

            'condition_analysis' =>
                $conditionAnalysis,

            /*
            |--------------------------------------------------------------------------
            | Detailed Evidence Analysis
            |--------------------------------------------------------------------------
            */

            'validated_evidence' =>
                $validatedEvidenceItems,

            'outstanding_validation_evidence' =>
                $outstandingValidationEvidence,

            'blocking_validation_evidence' =>
                $blockingValidationEvidence,

            'evidence_analysis' =>
                $evidenceAnalysis,

            /*
            |--------------------------------------------------------------------------
            | Human Decision Context
            |--------------------------------------------------------------------------
            */

            'human_decision_context' => [
                'final_human_decision' =>
                    $humanDecisionContext['final_human_decision'] ?? null,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'final_human_decision_available' =>
                    $humanDecisionContext['final_human_decision_available']
                    ?? false,

                'human_decision_score' =>
                    $humanDecisionContext['human_decision_score'] ?? 0,
            ],

            /*
            |--------------------------------------------------------------------------
            | Validator Context
            |--------------------------------------------------------------------------
            */

            'validator_context' => [
                'validated_by' =>
                    $validatorContext['validated_by'] ?? null,

                'validator_role' =>
                    $validatorContext['validator_role'] ?? null,

                'validated_at' =>
                    $validatorContext['validated_at'] ?? null,

                'validator_attribution_complete' =>
                    $validatorAttributionComplete,

                'validation_made_by_authorized_human' =>
                    $validationMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Completion Context
            |--------------------------------------------------------------------------
            */

            'completion_context' =>
                $completionContext,

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            /*
            |--------------------------------------------------------------------------
            | Findings / Priorities
            |--------------------------------------------------------------------------
            */

            'condition_evidence_findings' =>
                $conditionEvidenceFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 66.4 Guardrails
            |--------------------------------------------------------------------------
            */

            'validation_condition_evidence_guardrails' => [
                'strategic_plan_validation_condition_evidence_intelligence_enabled' =>
                    true,

                'condition_evidence_intelligence_is_completed_validation' =>
                    false,

                'condition_evidence_intelligence_makes_governance_validation_decision' =>
                    false,

                'condition_evidence_intelligence_records_governance_validation_decision' =>
                    false,

                'condition_evidence_intelligence_completes_governance_validation' =>
                    false,

                'condition_evidence_intelligence_approves_strategic_plan' =>
                    false,

                'condition_evidence_intelligence_rejects_strategic_plan' =>
                    false,

                'condition_evidence_intelligence_conditionally_approves_strategic_plan' =>
                    false,

                'condition_evidence_intelligence_defers_strategic_plan' =>
                    false,

                'condition_evidence_intelligence_accepts_governance_risk' =>
                    false,

                'condition_evidence_intelligence_activates_strategic_plan' =>
                    false,

                'condition_evidence_intelligence_changes_validation_status' =>
                    false,

                'condition_evidence_intelligence_changes_governance_validation_decision' =>
                    false,

                'condition_evidence_intelligence_changes_human_decision' =>
                    false,

                'condition_evidence_intelligence_changes_plan_status' =>
                    false,

                'condition_evidence_intelligence_changes_action_state' =>
                    false,

                'condition_evidence_intelligence_changes_priority' =>
                    false,

                'condition_evidence_intelligence_changes_eligibility' =>
                    false,

                'condition_evidence_intelligence_resolves_validation_conditions' =>
                    false,

                'condition_evidence_intelligence_resolves_decision_conditions' =>
                    false,

                'condition_evidence_intelligence_resolves_dependencies' =>
                    false,

                'condition_evidence_intelligence_validates_evidence' =>
                    false,

                'condition_resolution_score_authorizes_validation' =>
                    false,

                'evidence_readiness_score_authorizes_validation' =>
                    false,

                'condition_evidence_score_authorizes_validation' =>
                    false,

                'validation_pressure_score_authorizes_validation' =>
                    false,

                'condition_evidence_intelligence_authorizes_ai_change' =>
                    false,

                'condition_evidence_intelligence_authorizes_execution' =>
                    false,

                'condition_evidence_intelligence_authorizes_deployment' =>
                    false,

                'condition_evidence_intelligence_authorizes_rollback' =>
                    false,

                'condition_evidence_intelligence_authorizes_clinical_action' =>
                    false,

                'condition_evidence_intelligence_overrides_human_decision_authority' =>
                    false,

                'condition_evidence_intelligence_overrides_human_review' =>
                    false,

                'condition_evidence_intelligence_overrides_governance_validation' =>
                    false,

                'condition_evidence_intelligence_overrides_evidence_requirements' =>
                    false,

                'automatic_validation_allowed' =>
                    false,

                'automatic_decision_allowed' =>
                    false,

                'automatic_approval_allowed' =>
                    false,

                'automatic_rejection_allowed' =>
                    false,

                'automatic_activation_allowed' =>
                    false,

                'automatic_condition_resolution_allowed' =>
                    false,

                'automatic_evidence_validation_allowed' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'governance_validation_authority_reserved_for_authorized_human' =>
                    true,

                'final_decision_authority_reserved_for_authorized_human' =>
                    true,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Strategic plan governance validation condition and evidence intelligence classifies validation conditions, evidence sufficiency, validation blockers, condition pressure, evidence readiness, governance-validation progression readiness, and management attention requirements for authorized human governance. It does not resolve validation or decision conditions, validate evidence, make or record a governance-validation decision, complete governance validation, make or change the final strategic plan decision, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final strategic plan decision authority and governance-validation authority remain reserved exclusively for authorized human governance.',
            ],
        ];
    }
}