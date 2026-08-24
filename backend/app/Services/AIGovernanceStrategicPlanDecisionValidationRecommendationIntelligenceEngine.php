<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecisionValidation;

class AIGovernanceStrategicPlanDecisionValidationRecommendationIntelligenceEngine
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
                'message' => 'No AI governance strategic plan decision validation record is available for recommendation intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 66 Intelligence Engines
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanDecisionValidationStateIntelligenceEngine::class
        );

        $conditionEvidenceEngine = app(
            AIGovernanceStrategicPlanDecisionValidationConditionEvidenceIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanDecisionValidationEligibilitySafetyIntelligenceEngine::class
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Intelligence
        |--------------------------------------------------------------------------
        */

        $state = $stateEngine->analyze($validation->id);

        $conditionEvidence = $conditionEvidenceEngine->analyze(
            $validation->id
        );

        $eligibilitySafety = $eligibilitySafetyEngine->analyze(
            $validation->id
        );

        /*
        |--------------------------------------------------------------------------
        | Context Extraction
        |--------------------------------------------------------------------------
        */

        $validationState =
            $state['validation_state'] ?? [];

        $conditionContext =
            $eligibilitySafety['condition_context']
            ?? $conditionEvidence['condition_context']
            ?? [];

        $evidenceContext =
            $eligibilitySafety['evidence_context']
            ?? $conditionEvidence['evidence_context']
            ?? [];

        $resolutionContext =
            $eligibilitySafety['resolution_context']
            ?? $conditionEvidence['resolution_context']
            ?? [];

        $eligibilityState =
            $eligibilitySafety['validation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['validation_safety_state'] ?? [];

        $humanDecisionContext =
            $eligibilitySafety['human_decision_context']
            ?? $state['human_decision_context']
            ?? [];

        $validatorContext =
            $eligibilitySafety['validator_context']
            ?? $state['validator_context']
            ?? [];

        $reviewContext =
            $eligibilitySafety['review_context']
            ?? $state['review_context']
            ?? [];

        $governanceContext =
            $eligibilitySafety['governance_context']
            ?? $state['governance_context']
            ?? [];

        $dominantRestriction =
            $eligibilitySafety['dominant_eligibility_restriction']
            ?? $conditionEvidence['dominant_validation_restriction']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Current Validation Conditions
        |--------------------------------------------------------------------------
        */

        $validationConditions =
            is_array($validation->validation_conditions)
                ? $validation->validation_conditions
                : [];

        $validatedEvidence =
            is_array($validation->validated_evidence)
                ? $validation->validated_evidence
                : [];

        /*
        |--------------------------------------------------------------------------
        | Core Counts
        |--------------------------------------------------------------------------
        */

        $totalConditions =
            (int) (
                $conditionContext['total_conditions']
                ?? count($validationConditions)
            );

        $openConditions =
            (int) (
                $conditionContext['open_conditions']
                ?? collect($validationConditions)
                    ->filter(function ($condition) {
                        return strtoupper(
                            (string) ($condition['condition_status'] ?? 'OPEN')
                        ) !== 'RESOLVED';
                    })
                    ->count()
            );

        $blockingConditions =
            (int) (
                $conditionContext['blocking_conditions']
                ?? collect($validationConditions)
                    ->filter(function ($condition) {
                        return strtoupper(
                            (string) ($condition['priority_level'] ?? '')
                        ) === 'HIGH'
                            && strtoupper(
                                (string) ($condition['condition_status'] ?? 'OPEN')
                            ) !== 'RESOLVED';
                    })
                    ->count()
            );

        $constrainingConditions =
            (int) (
                $conditionContext['constraining_conditions']
                ?? max(
                    0,
                    $openConditions - $blockingConditions
                )
            );

        $criticalOpenConditions =
            (int) (
                $conditionContext['critical_open_conditions']
                ?? collect($validationConditions)
                    ->filter(function ($condition) {
                        return strtoupper(
                            (string) ($condition['priority_level'] ?? '')
                        ) === 'CRITICAL'
                            && strtoupper(
                                (string) ($condition['condition_status'] ?? 'OPEN')
                            ) !== 'RESOLVED';
                    })
                    ->count()
            );

        $totalEvidenceItems =
            (int) (
                $evidenceContext['total_evidence_items']
                ?? count($validatedEvidence)
            );

        $validatedEvidenceItems =
            (int) (
                $evidenceContext['validated_evidence_items']
                ?? count($validatedEvidence)
            );

        $outstandingEvidenceItems =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

        $blockingEvidenceItems =
            (int) (
                $evidenceContext['blocking_evidence_items']
                ?? 0
            );

        $criticalOutstandingEvidence =
            (int) (
                $evidenceContext['critical_outstanding_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Human Decision / Authorization State
        |--------------------------------------------------------------------------
        */

        $finalHumanDecisionRecorded =
            ($eligibilityState['final_human_decision_recorded']
                ?? $validation->final_human_decision_recorded
                ?? false) === true;

        $decisionMadeByAuthorizedHuman =
            ($eligibilityState['decision_made_by_authorized_human']
                ?? $validation->decision_made_by_authorized_human
                ?? false) === true;

        $governanceValidationCompleted =
            (bool) $validation->governance_validation_completed;

        $validationMadeByAuthorizedHuman =
            (bool) $validation->validation_made_by_authorized_human;

        $validatorAttributionComplete =
            !empty($validation->validated_by)
            && !empty($validation->validator_role)
            && !empty($validation->validated_at);

        /*
        |--------------------------------------------------------------------------
        | Validation Readiness
        |--------------------------------------------------------------------------
        */

        $governanceValidationReadiness =
            $validationState['governance_validation_readiness']
            ?? $eligibilityState['validation_progression_readiness']
            ?? $validation->governance_validation_readiness
            ?? 'UNKNOWN';

        $governanceValidationReadinessScore =
            (float) (
                $validationState['governance_validation_readiness_score']
                ?? $validation->governance_validation_readiness_score
                ?? 0
            );

        $validationEligibilityScore =
            (float) (
                $eligibilityState['validation_eligibility_score']
                ?? 0
            );

        $validationSafetyScore =
            (float) (
                $safetyState['validation_safety_score']
                ?? 0
            );

        $conditionResolutionScore =
            (float) (
                $resolutionContext['condition_resolution_score']
                ?? $validation->condition_resolution_score
                ?? 0
            );

        $evidenceResolutionScore =
            (float) (
                $resolutionContext['evidence_resolution_score']
                ?? $validation->evidence_resolution_score
                ?? 0
            );

        $combinedResolutionScore =
            (float) (
                $resolutionContext['combined_resolution_score']
                ?? $validation->combined_resolution_score
                ?? 0
            );

        $decisionRiskLevel =
            $humanDecisionContext['decision_risk_level']
            ?? $validation->decision_risk_level
            ?? 'UNKNOWN';

        $decisionRiskScore =
            (float) (
                $humanDecisionContext['decision_risk_score']
                ?? $validation->decision_risk_score
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Eligibility Paths
        |--------------------------------------------------------------------------
        */

        $reviewEligibility =
            $eligibilityState['authorized_human_validation_review_eligibility']
            ?? 'UNKNOWN';

        $completionEligibility =
            $eligibilityState['governance_validation_completion_eligibility']
            ?? 'UNKNOWN';

        $unrestrictedValidationEligibility =
            $eligibilityState['unrestricted_validation_eligibility']
            ?? 'UNKNOWN';

        $conditionalValidationEligibility =
            $eligibilityState['conditional_validation_consideration_eligibility']
            ?? 'UNKNOWN';

        $validationDeferralEligibility =
            $eligibilityState['validation_deferral_eligibility']
            ?? 'UNKNOWN';

        $additionalResolutionEligibility =
            $eligibilityState['request_additional_resolution_eligibility']
            ?? 'UNKNOWN';

        /*
        |--------------------------------------------------------------------------
        | Build Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | Final Human Decision Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$finalHumanDecisionRecorded) {
            $recommendations[] = [
                'recommendation_code' =>
                    'RECORD_AUTHORIZED_HUMAN_FINAL_STRATEGIC_PLAN_DECISION',

                'recommendation_category' =>
                    'HUMAN_DECISION',

                'priority_level' =>
                    'HIGH',

                'recommended_validation_path' =>
                    'AWAIT_AUTHORIZED_HUMAN_FINAL_DECISION',

                'recommendation' =>
                    'Record an explicitly authorized human strategic plan decision before governance validation completion is considered.',

                'reason' =>
                    'No final authorized-human strategic plan decision is currently recorded.',

                'related_condition_count' =>
                    $blockingConditions,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Human Decision Attribution Recommendation
        |--------------------------------------------------------------------------
        */

        if (
            !$decisionMadeByAuthorizedHuman
            || empty($validation->final_human_decision)
        ) {
            $recommendations[] = [
                'recommendation_code' =>
                    'ESTABLISH_AUTHORIZED_HUMAN_DECISION_ATTRIBUTION',

                'recommendation_category' =>
                    'AUTHORIZATION',

                'priority_level' =>
                    'HIGH',

                'recommended_validation_path' =>
                    'AUTHORIZED_HUMAN_DECISION_ATTRIBUTION',

                'recommendation' =>
                    'Ensure the final strategic plan decision is explicitly attributable to an authorized human governance decision-maker before completed governance validation.',

                'reason' =>
                    'Authorized-human decision attribution is incomplete or the final human decision has not yet been recorded.',

                'related_condition_count' =>
                    null,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Blocking Validation Conditions
        |--------------------------------------------------------------------------
        */

        if ($blockingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' =>
                    'RESOLVE_BLOCKING_GOVERNANCE_VALIDATION_CONDITIONS',

                'recommendation_category' =>
                    'VALIDATION_CONDITION_MANAGEMENT',

                'priority_level' =>
                    'HIGH',

                'recommended_validation_path' =>
                    'REQUEST_ADDITIONAL_RESOLUTION',

                'recommendation' =>
                    "Resolve or formally govern {$blockingConditions} blocking governance-validation condition(s) before completed or unrestricted governance validation is considered.",

                'reason' =>
                    "{$blockingConditions} blocking governance-validation condition(s) remain active.",

                'related_condition_count' =>
                    $blockingConditions,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Constraining Conditions
        |--------------------------------------------------------------------------
        */

        if ($constrainingConditions > 0) {
            $recommendations[] = [
                'recommendation_code' =>
                    'REDUCE_GOVERNANCE_VALIDATION_CONSTRAINTS',

                'recommendation_category' =>
                    'VALIDATION_READINESS',

                'priority_level' =>
                    'MODERATE',

                'recommended_validation_path' =>
                    'CONDITIONAL_GOVERNANCE_REVIEW',

                'recommendation' =>
                    "Reduce or formally govern {$constrainingConditions} constraining governance-validation condition(s) to improve validation readiness.",

                'reason' =>
                    "{$constrainingConditions} constraining governance-validation condition(s) remain active.",

                'related_condition_count' =>
                    $constrainingConditions,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Outstanding Validation Evidence
        |--------------------------------------------------------------------------
        */

        if ($blockingEvidenceItems > 0) {
            $recommendations[] = [
                'recommendation_code' =>
                    'PROVIDE_BLOCKING_GOVERNANCE_VALIDATION_EVIDENCE',

                'recommendation_category' =>
                    'VALIDATION_EVIDENCE',

                'priority_level' =>
                    'HIGH',

                'recommended_validation_path' =>
                    'REQUEST_ADDITIONAL_RESOLUTION',

                'recommendation' =>
                    "Provide and validate {$blockingEvidenceItems} validation-blocking evidence requirement(s) through authorized human governance before completed governance validation.",

                'reason' =>
                    "{$blockingEvidenceItems} validation-blocking evidence requirement(s) remain outstanding.",

                'related_condition_count' =>
                    null,

                'related_evidence_count' =>
                    $blockingEvidenceItems,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        } elseif ($outstandingEvidenceItems > 0) {
            $recommendations[] = [
                'recommendation_code' =>
                    'COMPLETE_OUTSTANDING_GOVERNANCE_VALIDATION_EVIDENCE',

                'recommendation_category' =>
                    'VALIDATION_EVIDENCE',

                'priority_level' =>
                    'MODERATE',

                'recommended_validation_path' =>
                    'CONDITIONAL_GOVERNANCE_REVIEW',

                'recommendation' =>
                    "Complete review of {$outstandingEvidenceItems} outstanding governance-validation evidence requirement(s).",

                'reason' =>
                    "{$outstandingEvidenceItems} governance-validation evidence requirement(s) remain outstanding.",

                'related_condition_count' =>
                    null,

                'related_evidence_count' =>
                    $outstandingEvidenceItems,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validator Attribution Recommendation
        |--------------------------------------------------------------------------
        */

        if (!$validatorAttributionComplete) {
            $recommendations[] = [
                'recommendation_code' =>
                    'ESTABLISH_AUTHORIZED_GOVERNANCE_VALIDATOR_ATTRIBUTION',

                'recommendation_category' =>
                    'VALIDATOR_AUTHORIZATION',

                'priority_level' =>
                    $finalHumanDecisionRecorded
                        ? 'HIGH'
                        : 'MODERATE',

                'recommended_validation_path' =>
                    'AUTHORIZED_HUMAN_VALIDATION_REVIEW',

                'recommendation' =>
                    'Ensure completed governance validation is explicitly attributable to an identified authorized human governance validator.',

                'reason' =>
                    'Authorized governance-validator attribution is not yet complete.',

                'related_condition_count' =>
                    null,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | High Decision Risk
        |--------------------------------------------------------------------------
        */

        if ($decisionRiskScore >= 75) {
            $recommendations[] = [
                'recommendation_code' =>
                    'MAINTAIN_ELEVATED_HUMAN_GOVERNANCE_RISK_REVIEW',

                'recommendation_category' =>
                    'RISK',

                'priority_level' =>
                    'HIGH',

                'recommended_validation_path' =>
                    'CONDITIONAL_GOVERNANCE_REVIEW',

                'recommendation' =>
                    'Maintain elevated authorized-human governance review while strategic plan decision risk remains high.',

                'reason' =>
                    "Strategic plan decision risk remains {$decisionRiskLevel} with score {$decisionRiskScore}.",

                'related_condition_count' =>
                    null,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Low Condition Resolution
        |--------------------------------------------------------------------------
        */

        if ($conditionResolutionScore < 50) {
            $recommendations[] = [
                'recommendation_code' =>
                    'IMPROVE_VALIDATION_CONDITION_RESOLUTION',

                'recommendation_category' =>
                    'CONDITION_RESOLUTION',

                'priority_level' =>
                    $blockingConditions > 0
                        ? 'HIGH'
                        : 'MODERATE',

                'recommended_validation_path' =>
                    'REQUEST_ADDITIONAL_RESOLUTION',

                'recommendation' =>
                    'Improve governance-validation condition resolution through authorized human governance before completed validation.',

                'reason' =>
                    "Current validation condition resolution score is {$conditionResolutionScore}.",

                'related_condition_count' =>
                    $openConditions,

                'related_evidence_count' =>
                    null,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Resolution Readiness
        |--------------------------------------------------------------------------
        */

        if ($combinedResolutionScore < 50) {
            $recommendations[] = [
                'recommendation_code' =>
                    'IMPROVE_COMBINED_GOVERNANCE_VALIDATION_RESOLUTION',

                'recommendation_category' =>
                    'VALIDATION_READINESS',

                'priority_level' =>
                    'MODERATE',

                'recommended_validation_path' =>
                    'CONDITIONAL_GOVERNANCE_REVIEW',

                'recommendation' =>
                    'Improve combined governance-validation condition and evidence resolution before completed validation is considered.',

                'reason' =>
                    "Current combined governance-validation resolution score is {$combinedResolutionScore}.",

                'related_condition_count' =>
                    $openConditions,

                'related_evidence_count' =>
                    $outstandingEvidenceItems,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Readiness Recommendation
        |--------------------------------------------------------------------------
        */

        if ($governanceValidationReadinessScore < 50) {
            $recommendations[] = [
                'recommendation_code' =>
                    'IMPROVE_GOVERNANCE_VALIDATION_READINESS',

                'recommendation_category' =>
                    'VALIDATION_READINESS',

                'priority_level' =>
                    'MODERATE',

                'recommended_validation_path' =>
                    'AUTHORIZED_HUMAN_VALIDATION_REVIEW',

                'recommendation' =>
                    'Improve governance-validation readiness before completed governance validation is considered.',

                'reason' =>
                    "Current governance-validation readiness is {$governanceValidationReadiness} with score {$governanceValidationReadinessScore}.",

                'related_condition_count' =>
                    $openConditions,

                'related_evidence_count' =>
                    $outstandingEvidenceItems,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Deferral Recommendation
        |--------------------------------------------------------------------------
        */

        if (
            !$finalHumanDecisionRecorded
            || $blockingConditions > 0
            || $decisionRiskScore >= 75
        ) {
            $recommendations[] = [
                'recommendation_code' =>
                    'MAINTAIN_GOVERNANCE_VALIDATION_DEFERRAL_OPTION',

                'recommendation_category' =>
                    'VALIDATION_PATH',

                'priority_level' =>
                    'ADVISORY',

                'recommended_validation_path' =>
                    'GOVERNANCE_VALIDATION_DEFERRAL',

                'recommendation' =>
                    'Maintain governance-validation deferral as an available authorized-human pathway while material validation restrictions remain.',

                'reason' =>
                    'Completed governance validation is not currently eligible under the existing decision, condition, attribution, and risk state.',

                'related_condition_count' =>
                    $blockingConditions,

                'related_evidence_count' =>
                    $blockingEvidenceItems,

                'requires_authorized_human_action' =>
                    true,

                'automatic_action_allowed' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Human Authority Advisory
        |--------------------------------------------------------------------------
        */

        $recommendations[] = [
            'recommendation_code' =>
                'PRESERVE_AUTHORIZED_HUMAN_VALIDATION_AUTHORITY',

            'recommendation_category' =>
                'GOVERNANCE',

            'priority_level' =>
                'ADVISORY',

            'recommended_validation_path' =>
                'AUTHORIZED_HUMAN_GOVERNANCE_ONLY',

            'recommendation' =>
                'Preserve final strategic plan decision authority and governance-validation authority exclusively for explicitly authorized human governance.',

            'reason' =>
                'Validation intelligence, readiness scores, eligibility classifications, safety scores, recommendations, and evidence status remain advisory and must not become autonomous governance authority.',

            'related_condition_count' =>
                null,

            'related_evidence_count' =>
                null,

            'requires_authorized_human_action' =>
                true,

            'automatic_action_allowed' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Priority Sorting
        |--------------------------------------------------------------------------
        */

        $priorityWeights = [
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MODERATE' => 2,
            'ADVISORY' => 1,
        ];

        usort(
            $recommendations,
            function ($a, $b) use ($priorityWeights) {
                $aWeight =
                    $priorityWeights[
                        strtoupper(
                            $a['priority_level'] ?? 'ADVISORY'
                        )
                    ] ?? 0;

                $bWeight =
                    $priorityWeights[
                        strtoupper(
                            $b['priority_level'] ?? 'ADVISORY'
                        )
                    ] ?? 0;

                return $bWeight <=> $aWeight;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Counts
        |--------------------------------------------------------------------------
        */

        $criticalRecommendations =
            collect($recommendations)
                ->where('priority_level', 'CRITICAL')
                ->count();

        $highRecommendations =
            collect($recommendations)
                ->where('priority_level', 'HIGH')
                ->count();

        $moderateRecommendations =
            collect($recommendations)
                ->where('priority_level', 'MODERATE')
                ->count();

        $advisoryRecommendations =
            collect($recommendations)
                ->where('priority_level', 'ADVISORY')
                ->count();

        $totalRecommendations =
            count($recommendations);

        $topRecommendation =
            $recommendations[0] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Recommendation Status
        |--------------------------------------------------------------------------
        */

        if ($criticalRecommendations > 0) {
            $recommendationStatus =
                'CRITICAL_GOVERNANCE_VALIDATION_ATTENTION_REQUIRED';
        } elseif ($highRecommendations > 0) {
            $recommendationStatus =
                'ELEVATED_GOVERNANCE_VALIDATION_MANAGEMENT_ATTENTION';
        } elseif ($moderateRecommendations > 0) {
            $recommendationStatus =
                'MODERATE_GOVERNANCE_VALIDATION_ATTENTION';
        } else {
            $recommendationStatus =
                'CONTROLLED_GOVERNANCE_VALIDATION_ADVISORY';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionRequired =
            $highRecommendations > 0
            || $criticalRecommendations > 0
            || $blockingConditions > 0
            || !$finalHumanDecisionRecorded
            || $decisionRiskScore >= 75;

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidence > 0;

        /*
        |--------------------------------------------------------------------------
        | Recommendation Findings
        |--------------------------------------------------------------------------
        */

        $recommendationFindings = [
            "Strategic plan governance-validation recommendation intelligence is based on validation record {$validation->id}.",

            "Current recommendation status is {$recommendationStatus}.",

            "Current governance-validation review eligibility is {$reviewEligibility}.",

            "Current governance-validation completion eligibility is {$completionEligibility}.",

            "Current unrestricted governance-validation eligibility is {$unrestrictedValidationEligibility}.",

            "Current conditional governance-review eligibility is {$conditionalValidationEligibility}.",

            "Current governance-validation deferral eligibility is {$validationDeferralEligibility}.",

            "Current additional-resolution request eligibility is {$additionalResolutionEligibility}.",

            "Current governance-validation readiness is {$governanceValidationReadiness} with score {$governanceValidationReadinessScore}.",

            "Current validation eligibility score is {$validationEligibilityScore}.",

            "Current validation safety score is {$validationSafetyScore}.",

            "{$blockingConditions} blocking governance-validation condition(s) remain active.",

            "{$constrainingConditions} constraining governance-validation condition(s) remain active.",

            "{$outstandingEvidenceItems} governance-validation evidence requirement(s) remain outstanding.",

            "{$blockingEvidenceItems} governance-validation evidence requirement(s) currently block validation progression.",

            "Current condition resolution score is {$conditionResolutionScore}.",

            "Current evidence resolution score is {$evidenceResolutionScore}.",

            "Current combined governance-validation resolution score is {$combinedResolutionScore}.",

            "Current strategic plan decision risk is {$decisionRiskLevel} with score {$decisionRiskScore}.",

            'Final authorized-human strategic plan decision recorded is '
                .($finalHumanDecisionRecorded ? 'YES' : 'NO').'.',

            'Decision made by explicitly authorized human is '
                .($decisionMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Authorized governance validator attribution complete is '
                .($validatorAttributionComplete ? 'YES' : 'NO').'.',

            'Governance validation completed is '
                .($governanceValidationCompleted ? 'YES' : 'NO').'.',

            "{$totalRecommendations} governance-validation recommendation(s) are currently available.",

            'Governance-validation recommendation intelligence remains advisory and does not perform or complete governance validation.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        foreach ($recommendations as $recommendationItem) {
            if (
                in_array(
                    $recommendationItem['priority_level'] ?? null,
                    ['CRITICAL', 'HIGH', 'MODERATE'],
                    true
                )
            ) {
                $managementPriorities[] =
                    $recommendationItem['recommendation'];
            }
        }

        $managementPriorities[] =
            'Keep validation recommendation intelligence separate from final strategic plan decision authority and governance-validation authority.';

        $managementPriorities[] =
            'Preserve human governance authority, decision attribution, validator attribution, evidence quality, traceability, safety controls, and authority separation throughout strategic plan governance validation.';

        $managementPriorities =
            array_values(
                array_unique($managementPriorities)
            );

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_VALIDATION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

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
            | Recommendation State
            |--------------------------------------------------------------------------
            */

            'recommendation_state' => [
                'recommendation_mode' =>
                    'AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION_ADVISORY',

                'recommendation_status' =>
                    $recommendationStatus,

                'human_management_attention_required' =>
                    $humanManagementAttentionRequired,

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'governance_integrity_intact' =>
                    ($safetyState['governance_integrity_intact'] ?? false) === true,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recommendation Summary
            |--------------------------------------------------------------------------
            */

            'recommendation_summary' => [
                'total_recommendations' =>
                    $totalRecommendations,

                'critical_recommendations' =>
                    $criticalRecommendations,

                'high_recommendations' =>
                    $highRecommendations,

                'moderate_recommendations' =>
                    $moderateRecommendations,

                'advisory_recommendations' =>
                    $advisoryRecommendations,

                'top_recommendation_code' =>
                    $topRecommendation['recommendation_code'] ?? null,

                'top_recommendation_priority' =>
                    $topRecommendation['priority_level'] ?? null,

                'top_recommended_validation_path' =>
                    $topRecommendation['recommended_validation_path'] ?? null,
            ],

            'top_recommendation' =>
                $topRecommendation,

            'recommendations' =>
                $recommendations,

            /*
            |--------------------------------------------------------------------------
            | Validation Eligibility Context
            |--------------------------------------------------------------------------
            */

            'validation_eligibility_context' => [
                'authorized_human_validation_review_eligibility' =>
                    $reviewEligibility,

                'governance_validation_completion_eligibility' =>
                    $completionEligibility,

                'unrestricted_validation_eligibility' =>
                    $unrestrictedValidationEligibility,

                'conditional_validation_consideration_eligibility' =>
                    $conditionalValidationEligibility,

                'validation_deferral_eligibility' =>
                    $validationDeferralEligibility,

                'request_additional_resolution_eligibility' =>
                    $additionalResolutionEligibility,

                'validation_eligibility_score' =>
                    $validationEligibilityScore,

                'validation_progression_readiness' =>
                    $eligibilityState['validation_progression_readiness']
                    ?? null,

                'validation_progression_blocked' =>
                    $eligibilityState['validation_progression_blocked']
                    ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Validation Safety Context
            |--------------------------------------------------------------------------
            */

            'validation_safety_context' => [
                'validation_safety_status' =>
                    $safetyState['validation_safety_status'] ?? null,

                'validation_safety_level' =>
                    $safetyState['validation_safety_level'] ?? null,

                'validation_safety_score' =>
                    $validationSafetyScore,

                'governance_integrity_intact' =>
                    $safetyState['governance_integrity_intact']
                    ?? false,

                'human_validation_required' =>
                    $safetyState['human_validation_required']
                    ?? true,

                'authorized_validator_required' =>
                    $safetyState['authorized_validator_required']
                    ?? true,

                'human_management_attention_required' =>
                    $safetyState['human_management_attention_required']
                    ?? $humanManagementAttentionRequired,

                'immediate_human_intervention_required' =>
                    $safetyState['immediate_human_intervention_required']
                    ?? false,
            ],

            /*
            |--------------------------------------------------------------------------
            | Condition Context
            |--------------------------------------------------------------------------
            */

            'condition_context' => [
                'total_conditions' =>
                    $totalConditions,

                'open_conditions' =>
                    $openConditions,

                'blocking_conditions' =>
                    $blockingConditions,

                'constraining_conditions' =>
                    $constrainingConditions,

                'critical_open_conditions' =>
                    $criticalOpenConditions,

                'condition_resolution_score' =>
                    $conditionResolutionScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Validation Evidence Context
            |--------------------------------------------------------------------------
            |
            | This context intentionally represents Step 66 validation-package
            | evidence only. It must not be confused with unresolved evidence
            | inherited from Step 65's authorized-human decision layer.
            |
            */

            'evidence_context' => [
                'evidence_scope' =>
                    'STEP_66_GOVERNANCE_VALIDATION_PACKAGE',

                'total_evidence_items' =>
                    $totalEvidenceItems,

                'validated_evidence_items' =>
                    $validatedEvidenceItems,

                'outstanding_evidence_items' =>
                    $outstandingEvidenceItems,

                'blocking_evidence_items' =>
                    $blockingEvidenceItems,

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidence,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Resolution Context
            |--------------------------------------------------------------------------
            */

            'resolution_context' => [
                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'governance_validation_readiness' =>
                    $governanceValidationReadiness,

                'governance_validation_readiness_score' =>
                    $governanceValidationReadinessScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Human Decision Context
            |--------------------------------------------------------------------------
            */

            'human_decision_context' => [
                'prepared_decision' =>
                    $humanDecisionContext['prepared_decision']
                    ?? $validation->prepared_decision,

                'final_human_decision' =>
                    $humanDecisionContext['final_human_decision']
                    ?? $validation->final_human_decision,

                'final_human_decision_recorded' =>
                    $finalHumanDecisionRecorded,

                'decision_made_by_authorized_human' =>
                    $decisionMadeByAuthorizedHuman,

                'decision_risk_level' =>
                    $decisionRiskLevel,

                'decision_risk_score' =>
                    $decisionRiskScore,
            ],

            /*
            |--------------------------------------------------------------------------
            | Validator Context
            |--------------------------------------------------------------------------
            */

            'validator_context' => [
                'validated_by' =>
                    $validation->validated_by,

                'validator_role' =>
                    $validation->validator_role,

                'validated_at' =>
                    $validation->validated_at,

                'validator_attribution_complete' =>
                    $validatorAttributionComplete,

                'validation_made_by_authorized_human' =>
                    $validationMadeByAuthorizedHuman,

                'governance_validation_completed' =>
                    $governanceValidationCompleted,
            ],

            'dominant_eligibility_restriction' =>
                $dominantRestriction,

            'review_context' =>
                $reviewContext,

            'governance_context' =>
                $governanceContext,

            'recommendation_findings' =>
                $recommendationFindings,

            'management_priorities' =>
                $managementPriorities,

            /*
            |--------------------------------------------------------------------------
            | Step 66.6 Guardrails
            |--------------------------------------------------------------------------
            */

            'validation_recommendation_guardrails' => [
                'strategic_plan_validation_recommendation_intelligence_enabled' =>
                    true,

                'recommendation_is_completed_validation' =>
                    false,

                'recommendation_makes_validation_decision' =>
                    false,

                'recommendation_records_validation_decision' =>
                    false,

                'recommendation_completes_governance_validation' =>
                    false,

                'recommendation_approves_strategic_plan' =>
                    false,

                'recommendation_rejects_strategic_plan' =>
                    false,

                'recommendation_conditionally_approves_strategic_plan' =>
                    false,

                'recommendation_defers_strategic_plan' =>
                    false,

                'recommendation_accepts_governance_risk' =>
                    false,

                'recommendation_activates_strategic_plan' =>
                    false,

                'recommended_validation_path_is_validation_decision' =>
                    false,

                'conditional_review_recommendation_completes_validation' =>
                    false,

                'deferral_recommendation_defers_validation' =>
                    false,

                'resolution_recommendation_resolves_conditions' =>
                    false,

                'recommendation_changes_validation_status' =>
                    false,

                'recommendation_changes_human_decision' =>
                    false,

                'recommendation_changes_final_human_decision' =>
                    false,

                'recommendation_changes_plan_status' =>
                    false,

                'recommendation_changes_action_state' =>
                    false,

                'recommendation_changes_priority' =>
                    false,

                'recommendation_changes_eligibility_record' =>
                    false,

                'recommendation_resolves_conditions' =>
                    false,

                'recommendation_resolves_dependencies' =>
                    false,

                'recommendation_validates_evidence' =>
                    false,

                'recommendation_completes_validation_attribution' =>
                    false,

                'recommendation_priority_authorizes_validation' =>
                    false,

                'recommendation_priority_authorizes_approval' =>
                    false,

                'recommendation_priority_authorizes_execution' =>
                    false,

                'recommendation_authorizes_ai_change' =>
                    false,

                'recommendation_authorizes_execution' =>
                    false,

                'recommendation_authorizes_deployment' =>
                    false,

                'recommendation_authorizes_rollback' =>
                    false,

                'recommendation_authorizes_clinical_action' =>
                    false,

                'recommendation_overrides_human_review' =>
                    false,

                'recommendation_overrides_final_human_decision' =>
                    false,

                'recommendation_overrides_governance_validation' =>
                    false,

                'recommendation_overrides_evidence_requirements' =>
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
                    'Strategic plan governance-validation recommendation intelligence converts validation state, validation eligibility, safety restrictions, unresolved validation conditions, validation-package evidence, condition and evidence resolution, human-decision status, validator attribution, governance-validation readiness, and decision risk into ranked advisory recommendations for authorized human governance. Recommendations may identify validation pathways for human consideration, but they do not constitute or complete governance validation, approve, reject, conditionally approve, defer, accept governance risk, activate the strategic plan, resolve conditions or dependencies, validate evidence, alter the final human decision, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action. Final strategic plan decision authority and governance-validation authority remain reserved exclusively for authorized human governance.',
            ],
        ];
    }
}