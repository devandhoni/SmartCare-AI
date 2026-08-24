<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationRecommendationIntelligenceEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Authorization
        |--------------------------------------------------------------------------
        */

        $authorization = $authorizationId
            ? AIGovernanceStrategicPlanControlledActivationAuthorization::find($authorizationId)
            : AIGovernanceStrategicPlanControlledActivationAuthorization::latest('id')->first();

        if (!$authorization) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_CONTROLLED_ACTIVATION_AUTHORIZATION_AVAILABLE',
                'message' =>
                    'No strategic plan controlled activation authorization record is available for recommendation intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Load Step 69 Intelligence
        |--------------------------------------------------------------------------
        */

        $stateEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine::class
        );

        $conditionRestrictionEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationConditionRestrictionIntelligenceEngine::class
        );

        $eligibilitySafetyEngine = app(
            AIGovernanceStrategicPlanControlledActivationAuthorizationEligibilitySafetyIntelligenceEngine::class
        );

        $state = $stateEngine->analyze($authorization->id);

        $conditionRestriction =
            $conditionRestrictionEngine->analyze($authorization->id);

        $eligibilitySafety =
            $eligibilitySafetyEngine->analyze($authorization->id);

        if (!($state['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Recommendation intelligence could not continue because activation authorization state intelligence is unavailable.',
            ];
        }

        if (!($conditionRestriction['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_CONDITION_RESTRICTION_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Recommendation intelligence could not continue because activation authorization condition and restriction intelligence is unavailable.',
            ];
        }

        if (!($eligibilitySafety['analysis_completed'] ?? false)) {
            return [
                'analysis_completed' => false,
                'status' => 'CONTROLLED_ACTIVATION_AUTHORIZATION_ELIGIBILITY_SAFETY_INTELLIGENCE_UNAVAILABLE',
                'message' =>
                    'Recommendation intelligence could not continue because activation authorization eligibility and safety intelligence is unavailable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $activationState =
            $state['activation_authorization_state'] ?? [];

        $sourceConfirmationContext =
            $state['source_confirmation_context'] ?? [];

        $authorizationContext =
            $state['authorization_context'] ?? [];

        $executionContext =
            $state['execution_context'] ?? [];

        $conditionContext =
            $state['condition_context'] ?? [];

        $restrictionContext =
            $state['restriction_context'] ?? [];

        $evidenceContext =
            $state['evidence_context'] ?? [];

        $resolutionContext =
            $state['resolution_context'] ?? [];

        $conditionRestrictionState =
            $conditionRestriction['condition_restriction_state'] ?? [];

        $eligibilityState =
            $eligibilitySafety['activation_eligibility_state'] ?? [];

        $safetyState =
            $eligibilitySafety['activation_safety_state'] ?? [];

        $riskContext =
            $eligibilitySafety['activation_risk_context'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Core Values
        |--------------------------------------------------------------------------
        */

        $sourceConfirmationDecisionRecorded =
            (bool) (
                $sourceConfirmationContext['source_confirmation_decision_recorded']
                ?? false
            );

        $sourceConfirmationMadeByAuthorizedHuman =
            (bool) (
                $sourceConfirmationContext['source_confirmation_made_by_authorized_human']
                ?? false
            );

        $sourceConfirmationCompleted =
            (bool) (
                $sourceConfirmationContext['source_governance_decision_confirmation_completed']
                ?? false
            );

        $authorizationDecisionRecorded =
            (bool) (
                $activationState['authorization_decision_recorded']
                ?? false
            );

        $authorizationMadeByAuthorizedHuman =
            (bool) (
                $activationState['activation_authorization_made_by_authorized_human']
                ?? false
            );

        $authorizationAttributionComplete =
            (bool) (
                $activationState['authorization_attribution_complete']
                ?? false
            );

        $authorizationCompleted =
            (bool) (
                $activationState['controlled_activation_authorization_completed']
                ?? false
            );

        $executionAuthorized =
            (bool) (
                $executionContext['controlled_activation_execution_authorized']
                ?? false
            );

        $executionAttributionComplete =
            (bool) (
                $executionContext['execution_authorization_attribution_complete']
                ?? false
            );

        $governanceIntegrityIntact =
            (bool) (
                $safetyState['governance_integrity_intact']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $blockingAuthorizationConditions =
            (int) (
                $conditionContext['blocking_activation_authorization_conditions']
                ?? 0
            );

        $blockingExecutionConditions =
            (int) (
                $conditionContext['blocking_activation_execution_conditions']
                ?? 0
            );

        $materialRestrictions =
            (int) (
                $restrictionContext['material_restrictions']
                ?? 0
            );

        $criticalRestrictions =
            (int) (
                $restrictionContext['critical_restrictions']
                ?? 0
            );

        $outstandingEvidence =
            (int) (
                $evidenceContext['outstanding_evidence_items']
                ?? 0
            );

        $blockingAuthorizationEvidence =
            (int) (
                $evidenceContext['blocking_activation_authorization_evidence_items']
                ?? 0
            );

        $blockingExecutionEvidence =
            (int) (
                $evidenceContext['blocking_activation_execution_evidence_items']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $activationReadinessScore =
            $this->score(
                $activationState['activation_readiness_score']
                ?? $authorization->activation_readiness_score
            );

        $activationRiskScore =
            $this->score(
                $activationState['activation_risk_score']
                ?? $authorization->activation_risk_score
            );

        $eligibilityScore =
            $this->score(
                $eligibilityState['activation_authorization_eligibility_score']
                ?? 0
            );

        $safetyScore =
            $this->score(
                $safetyState['activation_authorization_safety_score']
                ?? 0
            );

        $conditionResolutionScore =
            $this->score(
                $resolutionContext['condition_resolution_score']
                ?? 0
            );

        $evidenceResolutionScore =
            $this->score(
                $resolutionContext['evidence_resolution_score']
                ?? 0
            );

        $combinedResolutionScore =
            $this->score(
                $resolutionContext['combined_resolution_score']
                ?? 0
            );

        $conditionPressureScore =
            $this->score(
                $resolutionContext['condition_pressure_score']
                ?? 0
            );

        $restrictionPressureScore =
            $this->score(
                $resolutionContext['restriction_pressure_score']
                ?? 0
            );

        $combinedPressureScore =
            $this->score(
                $resolutionContext['combined_condition_restriction_pressure_score']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Build Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $recommendations[] = $this->recommendation(
                'RECORD_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION',
                'GOVERNANCE_CONFIRMATION',
                'CRITICAL',
                100,
                'Record the required governance confirmation decision through explicitly authorized human governance before controlled activation authorization progresses.',
                'AWAIT_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION'
            );
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_ATTRIBUTION',
                'AUTHORIZATION',
                'CRITICAL',
                98,
                'Ensure the governance confirmation decision is explicitly attributable to an authorized human governance confirmer.',
                'COMPLETE_GOVERNANCE_CONFIRMATION_ATTRIBUTION'
            );
        }

        if (!$sourceConfirmationCompleted) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION',
                'GOVERNANCE_CONFIRMATION',
                'CRITICAL',
                96,
                'Complete the governance decision confirmation process through explicitly authorized human governance.',
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION'
            );
        }

        if ($criticalRestrictions > 0) {
            $recommendations[] = $this->recommendation(
                'ESCALATE_CRITICAL_CONTROLLED_ACTIVATION_RESTRICTIONS',
                'SAFETY',
                'CRITICAL',
                95,
                "Escalate {$criticalRestrictions} critical controlled activation restriction(s) for immediate authorized-human governance review.",
                'IMMEDIATE_AUTHORIZED_HUMAN_GOVERNANCE_REVIEW'
            );
        }

        if ($blockingAuthorizationConditions > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_CONTROLLED_ACTIVATION_AUTHORIZATION_BLOCKERS',
                'CONDITION_RESOLUTION',
                'HIGH',
                90,
                "Resolve or formally govern {$blockingAuthorizationConditions} condition(s) currently blocking controlled activation authorization progression.",
                'REQUEST_ADDITIONAL_ACTIVATION_RESOLUTION'
            );
        }

        if ($blockingExecutionConditions > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_CONTROLLED_ACTIVATION_EXECUTION_BLOCKERS',
                'EXECUTION_CONTROL',
                'HIGH',
                88,
                "Resolve or formally govern {$blockingExecutionConditions} condition(s) currently blocking controlled activation execution.",
                'MAINTAIN_EXECUTION_PROHIBITION'
            );
        }

        if ($materialRestrictions > 0) {
            $recommendations[] = $this->recommendation(
                'GOVERN_MATERIAL_CONTROLLED_ACTIVATION_RESTRICTIONS',
                'RESTRICTION_MANAGEMENT',
                'HIGH',
                86,
                "Maintain explicit authorized-human governance treatment for {$materialRestrictions} material controlled activation restriction(s).",
                'MAINTAIN_RESTRICTED_ACTIVATION_STATE'
            );
        }

        if ($activationRiskScore >= 90) {
            $recommendations[] = $this->recommendation(
                'MAINTAIN_CRITICAL_CONTROLLED_ACTIVATION_RISK_OVERSIGHT',
                'RISK',
                'CRITICAL',
                94,
                "Maintain critical authorized-human governance oversight while controlled activation risk remains at {$activationRiskScore}.",
                'CRITICAL_HUMAN_GOVERNANCE_OVERSIGHT'
            );
        } elseif ($activationRiskScore >= 75) {
            $recommendations[] = $this->recommendation(
                'MAINTAIN_HIGH_CONTROLLED_ACTIVATION_RISK_OVERSIGHT',
                'RISK',
                'HIGH',
                85,
                "Maintain elevated authorized-human governance oversight while controlled activation risk remains at {$activationRiskScore}.",
                'ELEVATED_HUMAN_GOVERNANCE_OVERSIGHT'
            );
        }

        if ($outstandingEvidence > 0) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_OUTSTANDING_CONTROLLED_ACTIVATION_EVIDENCE',
                'EVIDENCE',
                'HIGH',
                82,
                "Complete or formally govern {$outstandingEvidence} outstanding controlled activation evidence requirement(s).",
                'COMPLETE_ACTIVATION_EVIDENCE'
            );
        }

        if ($blockingAuthorizationEvidence > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_BLOCKING_ACTIVATION_AUTHORIZATION_EVIDENCE',
                'EVIDENCE',
                'CRITICAL',
                93,
                "Resolve {$blockingAuthorizationEvidence} evidence requirement(s) currently blocking controlled activation authorization.",
                'RESOLVE_BLOCKING_AUTHORIZATION_EVIDENCE'
            );
        }

        if ($blockingExecutionEvidence > 0) {
            $recommendations[] = $this->recommendation(
                'RESOLVE_BLOCKING_ACTIVATION_EXECUTION_EVIDENCE',
                'EVIDENCE',
                'CRITICAL',
                92,
                "Resolve {$blockingExecutionEvidence} evidence requirement(s) currently blocking activation execution.",
                'RESOLVE_BLOCKING_EXECUTION_EVIDENCE'
            );
        }

        if (!$authorizationDecisionRecorded) {
            $recommendations[] = $this->recommendation(
                'AWAIT_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_DECISION',
                'ACTIVATION_AUTHORIZATION',
                'HIGH',
                84,
                'An explicitly authorized human controlled activation authorization decision remains required.',
                'AWAIT_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION'
            );
        }

        if (!$authorizationMadeByAuthorizedHuman) {
            $recommendations[] = $this->recommendation(
                'PRESERVE_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION',
                'AUTHORIZATION',
                'HIGH',
                83,
                'Ensure any controlled activation authorization decision is made explicitly by an authorized human governance authority.',
                'AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_ONLY'
            );
        }

        if (!$authorizationAttributionComplete) {
            $recommendations[] = $this->recommendation(
                'COMPLETE_CONTROLLED_ACTIVATION_AUTHORIZATION_ATTRIBUTION',
                'AUTHORIZATION',
                'HIGH',
                81,
                'Ensure any completed controlled activation authorization includes identified human authorizer, role, timestamp, and authorized-human attribution.',
                'COMPLETE_ACTIVATION_AUTHORIZATION_ATTRIBUTION'
            );
        }

        if (!$authorizationCompleted) {
            $recommendations[] = $this->recommendation(
                'KEEP_CONTROLLED_ACTIVATION_AUTHORIZATION_INCOMPLETE',
                'AUTHORIZATION_CONTROL',
                'HIGH',
                80,
                'Do not mark controlled activation authorization complete until all required human-governance conditions are explicitly satisfied.',
                'MAINTAIN_PENDING_ACTIVATION_AUTHORIZATION'
            );
        }

        if (!$executionAuthorized) {
            $recommendations[] = $this->recommendation(
                'KEEP_ACTIVATION_EXECUTION_PROHIBITED',
                'EXECUTION_CONTROL',
                'CRITICAL',
                97,
                'Keep controlled activation execution prohibited until a separate explicitly authorized human execution authorization is completed.',
                'DO_NOT_AUTHORIZE_ACTIVATION_EXECUTION'
            );
        }

        if (!$executionAttributionComplete) {
            $recommendations[] = $this->recommendation(
                'REQUIRE_SEPARATE_EXECUTION_AUTHORIZATION_ATTRIBUTION',
                'EXECUTION_AUTHORIZATION',
                'HIGH',
                79,
                'Require separately attributable authorized-human execution authorization before any controlled activation execution.',
                'AWAIT_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION'
            );
        }

        if (!$governanceIntegrityIntact) {
            $recommendations[] = $this->recommendation(
                'RESTORE_CONTROLLED_ACTIVATION_GOVERNANCE_INTEGRITY',
                'GOVERNANCE_INTEGRITY',
                'CRITICAL',
                100,
                'Restore controlled activation governance integrity before any authorization or execution progression.',
                'HALT_ACTIVATION_PROGRESSION'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Persistent Advisory Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations[] = $this->recommendation(
            'PRESERVE_ACTIVATION_AUTHORIZATION_AUTHORITY_ISOLATION',
            'AUTHORITY_ISOLATION',
            'ADVISORY',
            55,
            'Keep recommendation intelligence strictly separated from controlled activation authorization authority.',
            'MAINTAIN_AUTHORITY_ISOLATION'
        );

        $recommendations[] = $this->recommendation(
            'PRESERVE_ACTIVATION_EXECUTION_AUTHORITY_ISOLATION',
            'AUTHORITY_ISOLATION',
            'ADVISORY',
            54,
            'Keep controlled activation execution authority separate from recommendation, eligibility, safety, readiness, and authorization intelligence.',
            'MAINTAIN_EXECUTION_AUTHORITY_ISOLATION'
        );

        /*
        |--------------------------------------------------------------------------
        | Sort Recommendations
        |--------------------------------------------------------------------------
        */

        usort(
            $recommendations,
            fn (array $a, array $b): int =>
                ($b['priority_score'] ?? 0)
                <=>
                ($a['priority_score'] ?? 0)
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation Counts
        |--------------------------------------------------------------------------
        */

        $criticalRecommendations =
            array_values(
                array_filter(
                    $recommendations,
                    fn (array $item): bool =>
                        ($item['priority_level'] ?? null) === 'CRITICAL'
                )
            );

        $highRecommendations =
            array_values(
                array_filter(
                    $recommendations,
                    fn (array $item): bool =>
                        ($item['priority_level'] ?? null) === 'HIGH'
                )
            );

        $moderateRecommendations =
            array_values(
                array_filter(
                    $recommendations,
                    fn (array $item): bool =>
                        ($item['priority_level'] ?? null) === 'MODERATE'
                )
            );

        $advisoryRecommendations =
            array_values(
                array_filter(
                    $recommendations,
                    fn (array $item): bool =>
                        ($item['priority_level'] ?? null) === 'ADVISORY'
                )
            );

        $topRecommendation =
            $recommendations[0] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Recommendation Status
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $recommendationStatus =
                'CRITICAL_CONTROLLED_ACTIVATION_GOVERNANCE_INTEGRITY_ACTION_REQUIRED';
        } elseif (count($criticalRecommendations) > 0) {
            $recommendationStatus =
                'CRITICAL_CONTROLLED_ACTIVATION_AUTHORIZATION_RECOMMENDATIONS';
        } elseif (count($highRecommendations) > 0) {
            $recommendationStatus =
                'ELEVATED_CONTROLLED_ACTIVATION_AUTHORIZATION_MANAGEMENT_ATTENTION';
        } elseif (count($moderateRecommendations) > 0) {
            $recommendationStatus =
                'MODERATE_CONTROLLED_ACTIVATION_AUTHORIZATION_RECOMMENDATIONS';
        } else {
            $recommendationStatus =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_ADVISORY_RECOMMENDATIONS';
        }

        /*
        |--------------------------------------------------------------------------
        | Recommended Path
        |--------------------------------------------------------------------------
        */

        if (!$governanceIntegrityIntact) {
            $recommendedPath =
                'HALT_ACTIVATION_PROGRESSION';
        } elseif (!$sourceConfirmationDecisionRecorded) {
            $recommendedPath =
                'AWAIT_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION_DECISION';
        } elseif (!$sourceConfirmationMadeByAuthorizedHuman) {
            $recommendedPath =
                'COMPLETE_GOVERNANCE_CONFIRMATION_ATTRIBUTION';
        } elseif (!$sourceConfirmationCompleted) {
            $recommendedPath =
                'COMPLETE_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION';
        } elseif ($criticalRestrictions > 0) {
            $recommendedPath =
                'IMMEDIATE_AUTHORIZED_HUMAN_GOVERNANCE_REVIEW';
        } elseif ($blockingAuthorizationConditions > 0) {
            $recommendedPath =
                'REQUEST_ADDITIONAL_ACTIVATION_RESOLUTION';
        } elseif (!$authorizationDecisionRecorded) {
            $recommendedPath =
                'AWAIT_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION';
        } elseif (!$authorizationCompleted) {
            $recommendedPath =
                'COMPLETE_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION';
        } elseif (!$executionAuthorized) {
            $recommendedPath =
                'AWAIT_SEPARATE_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION';
        } else {
            $recommendedPath =
                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Recommendation State
        |--------------------------------------------------------------------------
        */

        $recommendationState = [
            'recommendation_status' =>
                $recommendationStatus,

            'total_recommendations' =>
                count($recommendations),

            'critical_recommendations' =>
                count($criticalRecommendations),

            'high_recommendations' =>
                count($highRecommendations),

            'moderate_recommendations' =>
                count($moderateRecommendations),

            'advisory_recommendations' =>
                count($advisoryRecommendations),

            'top_recommendation_code' =>
                $topRecommendation['recommendation_code'] ?? null,

            'top_recommendation_priority_level' =>
                $topRecommendation['priority_level'] ?? null,

            'top_recommendation_priority_score' =>
                $topRecommendation['priority_score'] ?? null,

            'top_recommended_activation_path' =>
                $recommendedPath,

            'activation_progression_blocked' =>
                (bool) (
                    $eligibilityState['activation_progression_blocked']
                    ?? true
                ),

            'governance_integrity_intact' =>
                $governanceIntegrityIntact,

            'human_management_attention_required' =>
                (bool) (
                    $safetyState['human_management_attention_required']
                    ?? true
                ),

            'immediate_human_intervention_required' =>
                (bool) (
                    $safetyState['immediate_human_intervention_required']
                    ?? false
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Recommendation Context
        |--------------------------------------------------------------------------
        */

        $recommendationContext = [
            'activation_authorization_state' =>
                $activationState['controlled_activation_authorization_state']
                ?? null,

            'activation_readiness' =>
                $activationState['activation_readiness']
                ?? null,

            'activation_readiness_score' =>
                $activationReadinessScore,

            'activation_risk_level' =>
                $activationState['activation_risk_level']
                ?? null,

            'activation_risk_score' =>
                $activationRiskScore,

            'activation_authorization_eligibility_score' =>
                $eligibilityScore,

            'activation_authorization_safety_status' =>
                $safetyState['activation_authorization_safety_status']
                ?? null,

            'activation_authorization_safety_level' =>
                $safetyState['activation_authorization_safety_level']
                ?? null,

            'activation_authorization_safety_score' =>
                $safetyScore,

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'evidence_resolution_score' =>
                $evidenceResolutionScore,

            'combined_resolution_score' =>
                $combinedResolutionScore,

            'condition_pressure_score' =>
                $conditionPressureScore,

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,

            'blocking_activation_authorization_conditions' =>
                $blockingAuthorizationConditions,

            'blocking_activation_execution_conditions' =>
                $blockingExecutionConditions,

            'material_restrictions' =>
                $materialRestrictions,

            'critical_restrictions' =>
                $criticalRestrictions,

            'outstanding_evidence_items' =>
                $outstandingEvidence,

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded,

            'source_confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman,

            'source_governance_decision_confirmation_completed' =>
                $sourceConfirmationCompleted,

            'controlled_activation_authorization_completed' =>
                $authorizationCompleted,

            'controlled_activation_execution_authorized' =>
                $executionAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Controlled activation authorization recommendation intelligence is based on authorization record {$authorization->id}.",

            'Current recommendation status is '
                .$recommendationStatus.'.',

            count($recommendations)
                .' controlled activation recommendation(s) are represented.',

            count($criticalRecommendations)
                .' critical recommendation(s) are represented.',

            count($highRecommendations)
                .' high recommendation(s) are represented.',

            count($moderateRecommendations)
                .' moderate recommendation(s) are represented.',

            count($advisoryRecommendations)
                .' advisory recommendation(s) are represented.',

            'Top recommendation is '
                .($topRecommendation['recommendation_code'] ?? 'NONE').'.',

            'Top recommended controlled activation path is '
                .$recommendedPath.'.',

            'Current activation readiness score is '
                .$activationReadinessScore.'.',

            'Current activation risk score is '
                .$activationRiskScore.'.',

            'Current activation authorization eligibility score is '
                .$eligibilityScore.'.',

            'Current activation authorization safety score is '
                .$safetyScore.'.',

            "{$blockingAuthorizationConditions} condition(s) currently block activation authorization.",

            "{$blockingExecutionConditions} condition(s) currently block activation execution.",

            "{$materialRestrictions} material restriction(s) remain active.",

            "{$criticalRestrictions} critical restriction(s) remain active.",

            'Governance integrity remains '
                .($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT').'.',

            'Recommendation intelligence remains advisory and does not authorize activation or execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        foreach (array_slice($recommendations, 0, 10) as $recommendation) {
            $managementPriorities[] =
                $recommendation['recommendation'];
        }

        $managementPriorities[] =
            'Keep recommendation intelligence strictly separate from controlled activation authorization authority.';

        $managementPriorities[] =
            'Keep controlled activation execution authority separately reserved for explicitly authorized human governance.';

        $managementPriorities[] =
            'Do not interpret recommendation ranking or priority scores as authorization to activate or execute.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_RECOMMENDATION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_controlled_activation_authorization_id' =>
                $authorization->id,

            'activation_authorization_code' =>
                $authorization->activation_authorization_code,

            'strategic_plan_governance_decision_confirmation_id' =>
                $authorization->strategic_plan_governance_decision_confirmation_id,

            'strategic_plan_final_governance_decision_id' =>
                $authorization->strategic_plan_final_governance_decision_id,

            'strategic_plan_decision_validation_id' =>
                $authorization->strategic_plan_decision_validation_id,

            'strategic_plan_human_decision_id' =>
                $authorization->strategic_plan_human_decision_id,

            'strategic_plan_decision_id' =>
                $authorization->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $authorization->strategic_plan_id,

            'strategic_snapshot_id' =>
                $authorization->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $authorization->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $authorization->lifecycle_snapshot_id,

            'decision_scope' =>
                $authorization->decision_scope,

            'resident_id' =>
                $authorization->resident_id,

            'recommendation_state' =>
                $recommendationState,

            'top_recommendation' =>
                $topRecommendation,

            'recommendations' =>
                $recommendations,

            'critical_recommendations' =>
                $criticalRecommendations,

            'high_recommendations' =>
                $highRecommendations,

            'moderate_recommendations' =>
                $moderateRecommendations,

            'advisory_recommendations' =>
                $advisoryRecommendations,

            'recommendation_context' =>
                $recommendationContext,

            'activation_authorization_state_context' =>
                $activationState,

            'condition_restriction_state_context' =>
                $conditionRestrictionState,

            'activation_eligibility_context' =>
                $eligibilityState,

            'activation_safety_context' =>
                $safetyState,

            'activation_risk_context' =>
                $riskContext,

            'source_confirmation_context' =>
                $sourceConfirmationContext,

            'authorization_context' =>
                $authorizationContext,

            'execution_context' =>
                $executionContext,

            'review_context' =>
                $state['review_context'] ?? [],

            'confirmation_context' =>
                $state['confirmation_context'] ?? [],

            'governance_context' =>
                $state['governance_context'] ?? [],

            'source_context' =>
                $state['source_context'] ?? [],

            'recommendation_findings' =>
                $findings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'activation_authorization_recommendation_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function recommendation(
        string $code,
        string $type,
        string $priorityLevel,
        float $priorityScore,
        string $recommendation,
        string $recommendedPath
    ): array {
        return [
            'recommendation_code' =>
                $code,

            'recommendation_type' =>
                $type,

            'priority_level' =>
                $priorityLevel,

            'priority_score' =>
                $priorityScore,

            'recommendation' =>
                $recommendation,

            'recommended_path' =>
                $recommendedPath,

            'requires_authorized_human_governance' =>
                true,

            'automatic_action_allowed' =>
                false,

            'authorizes_activation' =>
                false,

            'authorizes_execution' =>
                false,
        ];
    }

    private function score(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
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

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_recommendation_intelligence_enabled' =>
                true,

            'recommendation_intelligence_is_activation_authorization' =>
                false,

            'recommendation_intelligence_is_execution_authorization' =>
                false,

            'recommendation_intelligence_makes_activation_authorization_decision' =>
                false,

            'recommendation_intelligence_records_activation_authorization_decision' =>
                false,

            'recommendation_intelligence_completes_activation_authorization' =>
                false,

            'recommendation_intelligence_authorizes_activation_execution' =>
                false,

            'recommendation_intelligence_activates_strategic_plan' =>
                false,

            'recommendation_intelligence_changes_governance_confirmation' =>
                false,

            'recommendation_intelligence_changes_authorization_status' =>
                false,

            'recommendation_intelligence_changes_authorization_decision' =>
                false,

            'recommendation_intelligence_changes_authorization_outcome' =>
                false,

            'recommendation_intelligence_changes_execution_status' =>
                false,

            'recommendation_intelligence_resolves_conditions' =>
                false,

            'recommendation_intelligence_waives_conditions' =>
                false,

            'recommendation_intelligence_removes_restrictions' =>
                false,

            'recommendation_intelligence_validates_evidence' =>
                false,

            'recommendation_priority_authorizes_activation' =>
                false,

            'recommendation_priority_authorizes_execution' =>
                false,

            'top_recommendation_authorizes_activation' =>
                false,

            'top_recommendation_authorizes_execution' =>
                false,

            'recommendation_intelligence_authorizes_ai_change' =>
                false,

            'recommendation_intelligence_authorizes_execution' =>
                false,

            'recommendation_intelligence_authorizes_deployment' =>
                false,

            'recommendation_intelligence_authorizes_rollback' =>
                false,

            'recommendation_intelligence_authorizes_clinical_action' =>
                false,

            'recommendation_intelligence_overrides_human_review' =>
                false,

            'recommendation_intelligence_overrides_governance_confirmation' =>
                false,

            'recommendation_intelligence_overrides_activation_authorization' =>
                false,

            'recommendation_intelligence_overrides_execution_authorization' =>
                false,

            'automatic_activation_authorization_allowed' =>
                false,

            'automatic_confirmation_allowed' =>
                false,

            'automatic_final_decision_allowed' =>
                false,

            'automatic_approval_allowed' =>
                false,

            'automatic_rejection_allowed' =>
                false,

            'automatic_conditional_approval_allowed' =>
                false,

            'automatic_deferral_allowed' =>
                false,

            'automatic_risk_acceptance_allowed' =>
                false,

            'automatic_activation_allowed' =>
                false,

            'automatic_condition_resolution_allowed' =>
                false,

            'automatic_restriction_removal_allowed' =>
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

            'activation_authorization_authority_reserved_for_authorized_human' =>
                true,

            'activation_execution_authority_reserved_for_authorized_human' =>
                true,

            'governance_confirmation_authority_reserved_for_authorized_human' =>
                true,

            'human_review_required' =>
                true,

            'governance_confirmation_required' =>
                true,

            'authorized_human_activation_authorization_required' =>
                true,

            'authorized_human_activation_execution_required' =>
                true,

            'message' =>
                'Step 69.6 controlled activation authorization recommendation intelligence prioritizes governance-confirmation completion, authorization blockers, execution blockers, material and critical restrictions, evidence requirements, controlled activation risk, human authorization attribution, and execution-authority separation for explicitly authorized human governance review. Recommendations and priority scores are advisory only and do not make or record an activation authorization decision, complete authorization, authorize execution, activate the strategic plan, resolve or waive conditions, remove restrictions, validate evidence, alter governance confirmation, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}