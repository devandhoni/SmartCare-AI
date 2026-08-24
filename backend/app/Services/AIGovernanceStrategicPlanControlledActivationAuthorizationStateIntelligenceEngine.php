<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanControlledActivationAuthorization;

class AIGovernanceStrategicPlanControlledActivationAuthorizationStateIntelligenceEngine
{
    public function analyze(?int $authorizationId = null): array
    {
        /*
        |--------------------------------------------------------------------------
        | Locate Step 69 Authorization Record
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
                    'No strategic plan controlled activation authorization record is available for activation-authorization state intelligence.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Structured Data
        |--------------------------------------------------------------------------
        */

        $activationConditions =
            is_array($authorization->activation_conditions)
                ? $authorization->activation_conditions
                : [];

        $activationRestrictions =
            is_array($authorization->activation_restrictions)
                ? $authorization->activation_restrictions
                : [];

        $validatedEvidence =
            is_array($authorization->validated_evidence)
                ? $authorization->validated_evidence
                : [];

        $reviewContext =
            is_array($authorization->review_context)
                ? $authorization->review_context
                : [];

        $confirmationContext =
            is_array($authorization->confirmation_context)
                ? $authorization->confirmation_context
                : [];

        $governanceContext =
            is_array($authorization->governance_context)
                ? $authorization->governance_context
                : [];

        $sourceContext =
            is_array($authorization->source_context)
                ? $authorization->source_context
                : [];

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation State
        |--------------------------------------------------------------------------
        */

        $sourceConfirmationDecisionRecorded =
            (bool) $authorization->source_confirmation_decision_recorded;

        $sourceConfirmationMadeByAuthorizedHuman =
            (bool) $authorization->source_confirmation_made_by_authorized_human;

        $sourceGovernanceConfirmationCompleted =
            (bool) $authorization->source_governance_decision_confirmation_completed;

        $sourceControlledActivationAuthorized =
            (bool) $authorization->source_controlled_activation_authorized;

        /*
        |--------------------------------------------------------------------------
        | Current Authorization State
        |--------------------------------------------------------------------------
        */

        $authorizationDecisionRecorded =
            filled($authorization->controlled_activation_authorization_decision);

        $authorizationMadeByAuthorizedHuman =
            (bool) $authorization->activation_authorization_made_by_authorized_human;

        $authorizationCompleted =
            (bool) $authorization->controlled_activation_authorization_completed;

        $executionAuthorized =
            (bool) $authorization->controlled_activation_execution_authorized;

        /*
        |--------------------------------------------------------------------------
        | Authorization Attribution
        |--------------------------------------------------------------------------
        */

        $authorizerIdentified =
            filled($authorization->authorized_by);

        $authorizerRoleAvailable =
            filled($authorization->authorizer_role);

        $authorizationTimestampAvailable =
            !is_null($authorization->authorized_at);

        $authorizationAttributionComplete =
            $authorizerIdentified
            && $authorizerRoleAvailable
            && $authorizationTimestampAvailable
            && $authorizationMadeByAuthorizedHuman;

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization Attribution
        |--------------------------------------------------------------------------
        */

        $executionAuthorizerIdentified =
            filled($authorization->execution_authorized_by);

        $executionAuthorizerRoleAvailable =
            filled($authorization->execution_authorizer_role);

        $executionAuthorizationTimestampAvailable =
            !is_null($authorization->execution_authorized_at);

        $executionAuthorizationCodeAvailable =
            filled($authorization->activation_execution_authorization_code);

        $executionAttributionComplete =
            $executionAuthorizerIdentified
            && $executionAuthorizerRoleAvailable
            && $executionAuthorizationTimestampAvailable
            && $executionAuthorizationCodeAvailable
            && $executionAuthorized;

        /*
        |--------------------------------------------------------------------------
        | Analyze Activation Conditions
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = [];

        $openConditions = [];
        $resolvedConditions = [];
        $blockingAuthorizationConditions = [];
        $blockingExecutionConditions = [];
        $constrainingConditions = [];
        $criticalConditions = [];

        foreach ($activationConditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $status =
                strtoupper(
                    (string) ($condition['condition_status'] ?? 'OPEN')
                );

            $priority =
                strtoupper(
                    (string) ($condition['priority_level'] ?? 'MODERATE')
                );

            $isOpen =
                !in_array(
                    $status,
                    [
                        'RESOLVED',
                        'CLOSED',
                        'SATISFIED',
                        'COMPLETED',
                        'WAIVED',
                    ],
                    true
                );

            $isResolved =
                !$isOpen;

            $blocksAuthorization =
                $isOpen
                && (
                    ($condition['blocks_activation_authorization'] ?? false)
                    === true
                );

            $blocksExecution =
                $isOpen
                && (
                    ($condition['blocks_activation_execution'] ?? false)
                    === true
                );

            $constrainsAuthorization =
                $isOpen
                && !$blocksAuthorization
                && !$blocksExecution;

            $severityWeight =
                match ($priority) {
                    'CRITICAL' => 100.0,
                    'HIGH' => 75.0,
                    'MODERATE' => 50.0,
                    'LOW' => 25.0,
                    default => 25.0,
                };

            $classification =
                $isResolved
                    ? 'RESOLVED'
                    : (
                        $blocksAuthorization || $blocksExecution
                            ? 'BLOCKING'
                            : 'CONSTRAINING'
                    );

            $analyzed = array_merge(
                $condition,
                [
                    'condition_open' =>
                        $isOpen,

                    'condition_resolved' =>
                        $isResolved,

                    'condition_classification' =>
                        $classification,

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_activation_authorization' =>
                        $blocksAuthorization,

                    'blocks_activation_execution' =>
                        $blocksExecution,

                    'constrains_activation_authorization' =>
                        $constrainsAuthorization,
                ]
            );

            $conditionAnalysis[] =
                $analyzed;

            if ($isOpen) {
                $openConditions[] =
                    $analyzed;
            }

            if ($isResolved) {
                $resolvedConditions[] =
                    $analyzed;
            }

            if ($blocksAuthorization) {
                $blockingAuthorizationConditions[] =
                    $analyzed;
            }

            if ($blocksExecution) {
                $blockingExecutionConditions[] =
                    $analyzed;
            }

            if ($constrainsAuthorization) {
                $constrainingConditions[] =
                    $analyzed;
            }

            if ($isOpen && $priority === 'CRITICAL') {
                $criticalConditions[] =
                    $analyzed;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Analyze Activation Restrictions
        |--------------------------------------------------------------------------
        */

        $restrictionAnalysis = [];

        $materialRestrictions = [];
        $criticalRestrictions = [];
        $highRestrictions = [];

        foreach ($activationRestrictions as $restriction) {
            if (!is_array($restriction)) {
                continue;
            }

            $severity =
                strtoupper(
                    (string) ($restriction['severity'] ?? 'MODERATE')
                );

            $severityWeight =
                match ($severity) {
                    'CRITICAL' => 100.0,
                    'HIGH' => 75.0,
                    'MODERATE' => 50.0,
                    'LOW' => 25.0,
                    default => 25.0,
                };

            $material =
                in_array(
                    $severity,
                    ['CRITICAL', 'HIGH', 'MODERATE'],
                    true
                );

            $critical =
                $severity === 'CRITICAL';

            $analyzed = array_merge(
                $restriction,
                [
                    'severity_weight' =>
                        $severityWeight,

                    'material_restriction' =>
                        $material,

                    'critical_restriction' =>
                        $critical,

                    'requires_authorized_human_governance' =>
                        (bool) (
                            $restriction['requires_authorized_human_governance']
                            ?? true
                        ),

                    'automatic_restriction_removal_allowed' =>
                        false,
                ]
            );

            $restrictionAnalysis[] =
                $analyzed;

            if ($material) {
                $materialRestrictions[] =
                    $analyzed;
            }

            if ($critical) {
                $criticalRestrictions[] =
                    $analyzed;
            }

            if ($severity === 'HIGH') {
                $highRestrictions[] =
                    $analyzed;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Analyze Evidence
        |--------------------------------------------------------------------------
        */

        $evidenceAnalysis = [];

        $availableEvidence = [];
        $outstandingEvidence = [];
        $blockingAuthorizationEvidence = [];
        $blockingExecutionEvidence = [];
        $criticalOutstandingEvidence = [];

        foreach ($validatedEvidence as $evidence) {
            if (!is_array($evidence)) {
                continue;
            }

            $status =
                strtoupper(
                    (string) ($evidence['evidence_status'] ?? 'UNKNOWN')
                );

            $priority =
                strtoupper(
                    (string) ($evidence['priority_level'] ?? 'MODERATE')
                );

            $available =
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

            $validated =
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

            $outstanding =
                ($evidence['evidence_outstanding'] ?? false) === true
                || !$available;

            $blocksAuthorization =
                $outstanding
                && (
                    ($evidence['blocks_activation_authorization'] ?? false)
                    === true
                );

            $blocksExecution =
                $outstanding
                && (
                    ($evidence['blocks_activation_execution'] ?? false)
                    === true
                );

            $critical =
                $outstanding
                && $priority === 'CRITICAL';

            $analyzed = array_merge(
                $evidence,
                [
                    'evidence_available' =>
                        $available,

                    'evidence_validated' =>
                        $validated,

                    'evidence_outstanding' =>
                        $outstanding,

                    'blocks_activation_authorization' =>
                        $blocksAuthorization,

                    'blocks_activation_execution' =>
                        $blocksExecution,

                    'critical_outstanding_evidence' =>
                        $critical,
                ]
            );

            $evidenceAnalysis[] =
                $analyzed;

            if ($available) {
                $availableEvidence[] =
                    $analyzed;
            }

            if ($outstanding) {
                $outstandingEvidence[] =
                    $analyzed;
            }

            if ($blocksAuthorization) {
                $blockingAuthorizationEvidence[] =
                    $analyzed;
            }

            if ($blocksExecution) {
                $blockingExecutionEvidence[] =
                    $analyzed;
            }

            if ($critical) {
                $criticalOutstandingEvidence[] =
                    $analyzed;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        $activationReadinessScore =
            $this->score(
                $authorization->activation_readiness_score
            );

        $activationRiskScore =
            $this->score(
                $authorization->activation_risk_score
            );

        $conditionResolutionScore =
            $this->score(
                $authorization->condition_resolution_score
            );

        $evidenceResolutionScore =
            $this->score(
                $authorization->evidence_resolution_score
            );

        $combinedResolutionScore =
            $this->score(
                $authorization->combined_resolution_score
            );

        $conditionPressureScore =
            $this->score(
                $authorization->condition_pressure_score
            );

        $restrictionPressureScore =
            $this->score(
                $authorization->restriction_pressure_score
            );

        $combinedPressureScore =
            $this->score(
                $authorization->combined_condition_restriction_pressure_score
            );

        /*
        |--------------------------------------------------------------------------
        | Current State Classification
        |--------------------------------------------------------------------------
        */

        if (
            $authorizationCompleted
            && $authorizationDecisionRecorded
            && $authorizationMadeByAuthorizedHuman
            && $authorizationAttributionComplete
        ) {
            $authorizationState =
                'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION_COMPLETED';
        } elseif (
            !$sourceConfirmationDecisionRecorded
        ) {
            $authorizationState =
                'PENDING_SOURCE_GOVERNANCE_CONFIRMATION_DECISION';
        } elseif (
            !$sourceConfirmationMadeByAuthorizedHuman
        ) {
            $authorizationState =
                'PENDING_SOURCE_AUTHORIZED_HUMAN_CONFIRMATION_ATTRIBUTION';
        } elseif (
            !$sourceGovernanceConfirmationCompleted
        ) {
            $authorizationState =
                'PENDING_SOURCE_GOVERNANCE_CONFIRMATION_COMPLETION';
        } elseif (
            count($blockingAuthorizationConditions) > 0
            || count($materialRestrictions) > 0
            || count($blockingAuthorizationEvidence) > 0
        ) {
            $authorizationState =
                'PENDING_ACTIVATION_AUTHORIZATION_WITH_MATERIAL_REQUIREMENTS';
        } elseif (
            !$authorizationDecisionRecorded
        ) {
            $authorizationState =
                'AWAITING_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_DECISION';
        } elseif (
            !$authorizationMadeByAuthorizedHuman
            || !$authorizationAttributionComplete
        ) {
            $authorizationState =
                'AWAITING_AUTHORIZED_HUMAN_ACTIVATION_AUTHORIZATION_ATTRIBUTION';
        } else {
            $authorizationState =
                'CONTROLLED_ACTIVATION_AUTHORIZATION_REVIEW_STATE';
        }

        /*
        |--------------------------------------------------------------------------
        | Readiness Classification
        |--------------------------------------------------------------------------
        */

        if ($activationReadinessScore >= 85) {
            $readinessClassification =
                'HIGH_ACTIVATION_AUTHORIZATION_READINESS';
        } elseif ($activationReadinessScore >= 65) {
            $readinessClassification =
                'MODERATE_ACTIVATION_AUTHORIZATION_READINESS';
        } elseif ($activationReadinessScore >= 40) {
            $readinessClassification =
                'LIMITED_ACTIVATION_AUTHORIZATION_READINESS';
        } elseif ($activationReadinessScore > 0) {
            $readinessClassification =
                'VERY_LIMITED_ACTIVATION_AUTHORIZATION_READINESS';
        } else {
            $readinessClassification =
                'NO_CURRENT_ACTIVATION_AUTHORIZATION_READINESS';
        }

        /*
        |--------------------------------------------------------------------------
        | Confidence Classification
        |--------------------------------------------------------------------------
        */

        if ($activationReadinessScore >= 80) {
            $authorizationConfidence =
                'HIGH';
        } elseif ($activationReadinessScore >= 60) {
            $authorizationConfidence =
                'MODERATE';
        } elseif ($activationReadinessScore >= 30) {
            $authorizationConfidence =
                'LIMITED';
        } else {
            $authorizationConfidence =
                'EXTREMELY_LIMITED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Attention
        |--------------------------------------------------------------------------
        */

        $immediateHumanInterventionRequired =
            count($criticalConditions) > 0
            || count($criticalRestrictions) > 0
            || count($criticalOutstandingEvidence) > 0;

        $humanManagementAttentionRequired =
            $immediateHumanInterventionRequired
            || count($blockingAuthorizationConditions) > 0
            || count($blockingExecutionConditions) > 0
            || count($materialRestrictions) > 0
            || $activationRiskScore >= 75
            || !$sourceConfirmationDecisionRecorded
            || !$sourceConfirmationMadeByAuthorizedHuman
            || !$sourceGovernanceConfirmationCompleted
            || !$authorizationCompleted
            || !$executionAuthorized;

        if ($immediateHumanInterventionRequired) {
            $humanManagementAttentionLevel =
                'CRITICAL';
        } elseif (
            $activationRiskScore >= 75
            || count($blockingAuthorizationConditions) > 0
            || count($blockingExecutionConditions) > 0
            || count($materialRestrictions) > 0
        ) {
            $humanManagementAttentionLevel =
                'HIGH';
        } elseif ($humanManagementAttentionRequired) {
            $humanManagementAttentionLevel =
                'MODERATE';
        } else {
            $humanManagementAttentionLevel =
                'CONTROLLED';
        }

        /*
        |--------------------------------------------------------------------------
        | Authorization State
        |--------------------------------------------------------------------------
        */

        $activationAuthorizationState = [
            'activation_authorization_status' =>
                $authorization->activation_authorization_status,

            'activation_authorization_mode' =>
                $authorization->activation_authorization_mode,

            'controlled_activation_authorization_decision' =>
                $authorization->controlled_activation_authorization_decision,

            'activation_authorization_outcome' =>
                $authorization->activation_authorization_outcome,

            'activation_authorization_outcome_status' =>
                $authorization->activation_authorization_outcome_status,

            'controlled_activation_authorization_state' =>
                $authorizationState,

            'activation_readiness' =>
                $authorization->activation_readiness,

            'activation_readiness_score' =>
                $activationReadinessScore,

            'activation_readiness_classification' =>
                $readinessClassification,

            'activation_authorization_confidence' =>
                $authorizationConfidence,

            'activation_risk_level' =>
                $authorization->activation_risk_level,

            'activation_risk_score' =>
                $activationRiskScore,

            'human_management_attention_level' =>
                $humanManagementAttentionLevel,

            'human_management_attention_required' =>
                $humanManagementAttentionRequired,

            'immediate_human_intervention_required' =>
                $immediateHumanInterventionRequired,

            'authorization_decision_recorded' =>
                $authorizationDecisionRecorded,

            'activation_authorization_made_by_authorized_human' =>
                $authorizationMadeByAuthorizedHuman,

            'authorization_attribution_complete' =>
                $authorizationAttributionComplete,

            'controlled_activation_authorization_completed' =>
                $authorizationCompleted,

            'controlled_activation_execution_status' =>
                $authorization->controlled_activation_execution_status,

            'controlled_activation_execution_authorized' =>
                $executionAuthorized,

            'execution_authorization_attribution_complete' =>
                $executionAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Condition Context
        |--------------------------------------------------------------------------
        */

        $conditionContext = [
            'total_conditions' =>
                count($conditionAnalysis),

            'open_conditions' =>
                count($openConditions),

            'resolved_conditions' =>
                count($resolvedConditions),

            'blocking_activation_authorization_conditions' =>
                count($blockingAuthorizationConditions),

            'blocking_activation_execution_conditions' =>
                count($blockingExecutionConditions),

            'constraining_conditions' =>
                count($constrainingConditions),

            'critical_open_conditions' =>
                count($criticalConditions),

            'condition_resolution_score' =>
                $conditionResolutionScore,

            'condition_pressure_score' =>
                $conditionPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Restriction Context
        |--------------------------------------------------------------------------
        */

        $restrictionContext = [
            'total_restrictions' =>
                count($restrictionAnalysis),

            'material_restrictions' =>
                count($materialRestrictions),

            'critical_restrictions' =>
                count($criticalRestrictions),

            'high_restrictions' =>
                count($highRestrictions),

            'restriction_pressure_score' =>
                $restrictionPressureScore,

            'combined_condition_restriction_pressure_score' =>
                $combinedPressureScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Evidence Context
        |--------------------------------------------------------------------------
        */

        $evidenceContext = [
            'total_evidence_items' =>
                count($evidenceAnalysis),

            'available_evidence_items' =>
                count($availableEvidence),

            'outstanding_evidence_items' =>
                count($outstandingEvidence),

            'blocking_activation_authorization_evidence_items' =>
                count($blockingAuthorizationEvidence),

            'blocking_activation_execution_evidence_items' =>
                count($blockingExecutionEvidence),

            'critical_outstanding_evidence_items' =>
                count($criticalOutstandingEvidence),

            'evidence_resolution_score' =>
                $evidenceResolutionScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Resolution Context
        |--------------------------------------------------------------------------
        */

        $resolutionContext = [
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

            'activation_readiness' =>
                $authorization->activation_readiness,

            'activation_readiness_score' =>
                $activationReadinessScore,

            'activation_risk_level' =>
                $authorization->activation_risk_level,

            'activation_risk_score' =>
                $activationRiskScore,
        ];

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation Context
        |--------------------------------------------------------------------------
        */

        $sourceConfirmationContext = [
            'source_confirmation_decision' =>
                $authorization->source_confirmation_decision,

            'source_confirmation_decision_recorded' =>
                $sourceConfirmationDecisionRecorded,

            'source_confirmation_made_by_authorized_human' =>
                $sourceConfirmationMadeByAuthorizedHuman,

            'source_governance_decision_confirmation_completed' =>
                $sourceGovernanceConfirmationCompleted,

            'source_controlled_activation_status' =>
                $authorization->source_controlled_activation_status,

            'source_controlled_activation_authorized' =>
                $sourceControlledActivationAuthorized,
        ];

        /*
        |--------------------------------------------------------------------------
        | Authorization Attribution Context
        |--------------------------------------------------------------------------
        */

        $authorizationContext = [
            'authorized_by' =>
                $authorization->authorized_by,

            'authorizer_role' =>
                $authorization->authorizer_role,

            'authorized_at' =>
                optional($authorization->authorized_at)?->toISOString(),

            'authorizer_identified' =>
                $authorizerIdentified,

            'authorizer_role_available' =>
                $authorizerRoleAvailable,

            'authorization_timestamp_available' =>
                $authorizationTimestampAvailable,

            'authorization_attribution_complete' =>
                $authorizationAttributionComplete,

            'activation_authorization_made_by_authorized_human' =>
                $authorizationMadeByAuthorizedHuman,

            'controlled_activation_authorization_completed' =>
                $authorizationCompleted,
        ];

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization Context
        |--------------------------------------------------------------------------
        */

        $executionContext = [
            'controlled_activation_execution_status' =>
                $authorization->controlled_activation_execution_status,

            'controlled_activation_execution_authorized' =>
                $executionAuthorized,

            'activation_execution_authorization_code' =>
                $authorization->activation_execution_authorization_code,

            'execution_authorized_by' =>
                $authorization->execution_authorized_by,

            'execution_authorizer_role' =>
                $authorization->execution_authorizer_role,

            'execution_authorized_at' =>
                optional(
                    $authorization->execution_authorized_at
                )?->toISOString(),

            'execution_authorization_code_available' =>
                $executionAuthorizationCodeAvailable,

            'execution_authorizer_identified' =>
                $executionAuthorizerIdentified,

            'execution_authorizer_role_available' =>
                $executionAuthorizerRoleAvailable,

            'execution_authorization_timestamp_available' =>
                $executionAuthorizationTimestampAvailable,

            'execution_authorization_attribution_complete' =>
                $executionAttributionComplete,
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $activationAuthorizationFindings = [
            "Controlled activation authorization state intelligence is based on authorization record {$authorization->id}.",

            'Current activation authorization status is '
                .($authorization->activation_authorization_status ?? 'UNKNOWN').'.',

            'Current controlled activation authorization state is '
                .$authorizationState.'.',

            'Current activation readiness is '
                .($authorization->activation_readiness ?? 'UNKNOWN')
                .' with score '
                .$activationReadinessScore.'.',

            'Current activation readiness classification is '
                .$readinessClassification.'.',

            'Current activation authorization confidence is '
                .$authorizationConfidence.'.',

            'Current activation risk is '
                .($authorization->activation_risk_level ?? 'UNKNOWN')
                .' with score '
                .$activationRiskScore.'.',

            count($conditionAnalysis)
                .' activation authorization condition(s) are represented.',

            count($openConditions)
                .' activation authorization condition(s) remain open.',

            count($blockingAuthorizationConditions)
                .' activation condition(s) currently block authorization progression.',

            count($blockingExecutionConditions)
                .' activation condition(s) currently block activation execution.',

            count($constrainingConditions)
                .' activation condition(s) currently constrain progression.',

            count($criticalConditions)
                .' critical activation authorization condition(s) remain open.',

            count($restrictionAnalysis)
                .' activation authorization restriction(s) are represented.',

            count($materialRestrictions)
                .' material activation authorization restriction(s) remain active.',

            count($criticalRestrictions)
                .' critical activation authorization restriction(s) remain active.',

            count($evidenceAnalysis)
                .' activation evidence item(s) are represented.',

            count($outstandingEvidence)
                .' activation evidence requirement(s) remain outstanding.',

            count($blockingAuthorizationEvidence)
                .' activation evidence requirement(s) currently block authorization progression.',

            count($blockingExecutionEvidence)
                .' activation evidence requirement(s) currently block activation execution.',

            'Current condition resolution score is '
                .$conditionResolutionScore.'.',

            'Current evidence resolution score is '
                .$evidenceResolutionScore.'.',

            'Current combined resolution score is '
                .$combinedResolutionScore.'.',

            'Current condition pressure score is '
                .$conditionPressureScore.'.',

            'Current restriction pressure score is '
                .$restrictionPressureScore.'.',

            'Current combined condition/restriction pressure score is '
                .$combinedPressureScore.'.',

            'Source governance confirmation decision recorded is '
                .($sourceConfirmationDecisionRecorded ? 'YES' : 'NO').'.',

            'Source governance confirmation made by authorized human is '
                .($sourceConfirmationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Source governance confirmation completed is '
                .($sourceGovernanceConfirmationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation authorization decision recorded is '
                .($authorizationDecisionRecorded ? 'YES' : 'NO').'.',

            'Controlled activation authorization made by authorized human is '
                .($authorizationMadeByAuthorizedHuman ? 'YES' : 'NO').'.',

            'Controlled activation authorization attribution complete is '
                .($authorizationAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation authorization completed is '
                .($authorizationCompleted ? 'YES' : 'NO').'.',

            'Controlled activation execution authorized is '
                .($executionAuthorized ? 'YES' : 'NO').'.',

            'Controlled activation execution attribution complete is '
                .($executionAttributionComplete ? 'YES' : 'NO').'.',

            'Controlled activation authorization state intelligence remains informational and does not authorize activation or execution.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if (!$sourceConfirmationDecisionRecorded) {
            $managementPriorities[] =
                'Record an explicitly authorized human governance confirmation decision before controlled activation authorization progression.';
        }

        if (!$sourceConfirmationMadeByAuthorizedHuman) {
            $managementPriorities[] =
                'Ensure governance confirmation remains attributable to an explicitly authorized human governance confirmer.';
        }

        if (!$sourceGovernanceConfirmationCompleted) {
            $managementPriorities[] =
                'Complete governance decision confirmation through explicitly authorized human governance before controlled activation authorization.';
        }

        if (count($blockingAuthorizationConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingAuthorizationConditions)
                .' activation condition(s) currently blocking controlled activation authorization.';
        }

        if (count($blockingExecutionConditions) > 0) {
            $managementPriorities[] =
                'Resolve or formally govern '
                .count($blockingExecutionConditions)
                .' activation condition(s) currently blocking activation execution.';
        }

        if (count($materialRestrictions) > 0) {
            $managementPriorities[] =
                'Maintain explicit authorized-human governance treatment for '
                .count($materialRestrictions)
                .' material controlled-activation restriction(s).';
        }

        if (count($criticalRestrictions) > 0) {
            $managementPriorities[] =
                'Escalate '
                .count($criticalRestrictions)
                .' critical controlled-activation restriction(s) for immediate authorized-human governance review.';
        }

        if ($activationRiskScore >= 75) {
            $managementPriorities[] =
                'Maintain elevated authorized-human governance oversight while controlled activation risk remains high or critical.';
        }

        if (!$authorizationDecisionRecorded) {
            $managementPriorities[] =
                'An explicitly authorized human controlled-activation authorization decision remains required.';
        }

        if (!$authorizationAttributionComplete) {
            $managementPriorities[] =
                'Ensure any completed activation authorization remains explicitly attributable to an identified authorized human authorizer.';
        }

        if (!$executionAuthorized) {
            $managementPriorities[] =
                'Keep activation execution prohibited until a separate authorized-human execution authorization is explicitly completed.';
        }

        $managementPriorities[] =
            'Keep activation-authorization state intelligence separate from activation authorization authority and activation execution authority.';

        $managementPriorities[] =
            'Do not interpret readiness, risk, resolution, pressure, condition, restriction, or evidence scores as activation authorization.';

        $managementPriorities[] =
            'Preserve governance confirmation, human authorization attribution, execution attribution, evidence traceability, safety controls, and authority separation throughout controlled activation progression.';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'analysis_completed' =>
                true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_CONTROLLED_ACTIVATION_AUTHORIZATION_STATE_INTELLIGENCE_AVAILABLE',

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

            'activation_authorization_state' =>
                $activationAuthorizationState,

            'condition_context' =>
                $conditionContext,

            'restriction_context' =>
                $restrictionContext,

            'evidence_context' =>
                $evidenceContext,

            'resolution_context' =>
                $resolutionContext,

            'source_confirmation_context' =>
                $sourceConfirmationContext,

            'authorization_context' =>
                $authorizationContext,

            'execution_context' =>
                $executionContext,

            'review_context' =>
                $reviewContext,

            'confirmation_context' =>
                $confirmationContext,

            'governance_context' =>
                $governanceContext,

            'source_context' =>
                $sourceContext,

            'condition_analysis' =>
                $conditionAnalysis,

            'open_activation_conditions' =>
                $openConditions,

            'resolved_activation_conditions' =>
                $resolvedConditions,

            'blocking_activation_authorization_conditions' =>
                $blockingAuthorizationConditions,

            'blocking_activation_execution_conditions' =>
                $blockingExecutionConditions,

            'constraining_activation_conditions' =>
                $constrainingConditions,

            'restriction_analysis' =>
                $restrictionAnalysis,

            'material_activation_restrictions' =>
                $materialRestrictions,

            'critical_activation_restrictions' =>
                $criticalRestrictions,

            'evidence_analysis' =>
                $evidenceAnalysis,

            'outstanding_activation_evidence' =>
                $outstandingEvidence,

            'blocking_activation_authorization_evidence' =>
                $blockingAuthorizationEvidence,

            'blocking_activation_execution_evidence' =>
                $blockingExecutionEvidence,

            'activation_authorization_findings' =>
                $activationAuthorizationFindings,

            'management_priorities' =>
                array_values(
                    array_unique($managementPriorities)
                ),

            'activation_authorization_state_guardrails' =>
                $this->guardrails(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Score Helper
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Step 69.3 Guardrails
    |--------------------------------------------------------------------------
    */

    private function guardrails(): array
    {
        return [
            'controlled_activation_authorization_state_intelligence_enabled' =>
                true,

            'activation_state_intelligence_is_activation_authorization' =>
                false,

            'activation_state_intelligence_is_activation_execution_authorization' =>
                false,

            'activation_state_intelligence_makes_activation_authorization_decision' =>
                false,

            'activation_state_intelligence_records_activation_authorization_decision' =>
                false,

            'activation_state_intelligence_completes_activation_authorization' =>
                false,

            'activation_state_intelligence_authorizes_activation_execution' =>
                false,

            'activation_state_intelligence_activates_strategic_plan' =>
                false,

            'activation_state_intelligence_changes_source_confirmation' =>
                false,

            'activation_state_intelligence_changes_activation_authorization_status' =>
                false,

            'activation_state_intelligence_changes_activation_authorization_decision' =>
                false,

            'activation_state_intelligence_changes_activation_outcome' =>
                false,

            'activation_state_intelligence_changes_execution_status' =>
                false,

            'activation_state_intelligence_changes_plan_status' =>
                false,

            'activation_state_intelligence_changes_action_state' =>
                false,

            'activation_state_intelligence_changes_priority' =>
                false,

            'activation_state_intelligence_changes_eligibility' =>
                false,

            'activation_state_intelligence_resolves_conditions' =>
                false,

            'activation_state_intelligence_waives_conditions' =>
                false,

            'activation_state_intelligence_removes_restrictions' =>
                false,

            'activation_state_intelligence_downgrades_restrictions' =>
                false,

            'activation_state_intelligence_resolves_dependencies' =>
                false,

            'activation_state_intelligence_validates_evidence' =>
                false,

            'activation_readiness_score_authorizes_activation' =>
                false,

            'activation_readiness_score_authorizes_execution' =>
                false,

            'activation_risk_score_authorizes_activation' =>
                false,

            'activation_risk_score_authorizes_execution' =>
                false,

            'condition_resolution_score_authorizes_activation' =>
                false,

            'evidence_resolution_score_authorizes_activation' =>
                false,

            'combined_resolution_score_authorizes_activation' =>
                false,

            'condition_pressure_score_authorizes_activation' =>
                false,

            'restriction_pressure_score_authorizes_activation' =>
                false,

            'activation_state_intelligence_authorizes_ai_change' =>
                false,

            'activation_state_intelligence_authorizes_execution' =>
                false,

            'activation_state_intelligence_authorizes_deployment' =>
                false,

            'activation_state_intelligence_authorizes_rollback' =>
                false,

            'activation_state_intelligence_authorizes_clinical_action' =>
                false,

            'activation_state_intelligence_overrides_human_review' =>
                false,

            'activation_state_intelligence_overrides_governance_confirmation' =>
                false,

            'activation_state_intelligence_overrides_activation_authorization' =>
                false,

            'activation_state_intelligence_overrides_execution_authorization' =>
                false,

            'activation_state_intelligence_overrides_evidence_requirements' =>
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

            'final_governance_decision_authority_reserved_for_authorized_human' =>
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
                'Step 69.3 controlled activation authorization state intelligence evaluates the current activation-authorization record, source governance-confirmation state, authorization attribution, activation-execution attribution, activation conditions, restrictions, evidence state, readiness, risk, resolution progress, pressure, and management attention requirements for explicitly authorized human governance. State intelligence does not make, record, or complete a controlled-activation authorization decision; authorize activation execution; activate the strategic plan; change governance confirmation or final governance decisions; resolve or waive conditions; remove or downgrade restrictions; validate evidence; modify AI behavior; execute changes; deploy updates; trigger rollback; or initiate clinical action. Controlled activation authorization authority and activation execution authority remain reserved exclusively for explicitly authorized human governance.',
        ];
    }
}