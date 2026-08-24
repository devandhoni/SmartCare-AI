<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanHumanDecision;

class AIGovernanceStrategicPlanHumanDecisionConditionEvidenceResolutionIntelligenceEngine
{
    public function analyze(?int $humanDecisionId = null): array
    {
        $humanDecision = $humanDecisionId
            ? AIGovernanceStrategicPlanHumanDecision::find($humanDecisionId)
            : AIGovernanceStrategicPlanHumanDecision::latest('id')->first();

        if (!$humanDecision) {
            return [
                'analysis_completed' => false,
                'status' => 'NO_STRATEGIC_PLAN_HUMAN_DECISION_AVAILABLE',
                'message' => 'No AI governance strategic plan human decision record is available for condition and evidence resolution intelligence.',
            ];
        }

        $decisionConditions = is_array($humanDecision->decision_conditions)
            ? $humanDecision->decision_conditions
            : [];

        $evidenceContext = is_array($humanDecision->evidence_context)
            ? $humanDecision->evidence_context
            : [];

        $validatedEvidence = is_array($humanDecision->validated_evidence)
            ? $humanDecision->validated_evidence
            : [];

        $requirements = is_array($evidenceContext['requirements'] ?? null)
            ? $evidenceContext['requirements']
            : [];

        /*
        |--------------------------------------------------------------------------
        | Condition intelligence
        |--------------------------------------------------------------------------
        */

        $conditionAnalysis = [];

        foreach ($decisionConditions as $condition) {
            $priorityLevel = strtoupper((string) ($condition['priority_level'] ?? 'ADVISORY'));
            $status = strtoupper((string) ($condition['condition_status'] ?? 'OPEN'));

            $isOpen = !in_array($status, [
                'RESOLVED',
                'SATISFIED',
                'CLOSED',
                'COMPLETED',
            ], true);

            $classification = match (true) {
                !$isOpen => 'RESOLVED',

                in_array($priorityLevel, ['CRITICAL', 'HIGH'], true)
                    => 'BLOCKING',

                $priorityLevel === 'MODERATE'
                    => 'CONSTRAINING',

                default
                    => 'ADVISORY',
            };

            $severityWeight = match ($priorityLevel) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'ADVISORY' => 25.0,
                default => 25.0,
            };

            $conditionAnalysis[] = array_merge($condition, [
                'condition_open' => $isOpen,
                'condition_classification' => $classification,
                'severity_weight' => $severityWeight,

                'blocks_final_decision' =>
                    $isOpen && $classification === 'BLOCKING',

                'constrains_final_decision' =>
                    $isOpen && $classification === 'CONSTRAINING',

                'requires_human_resolution' =>
                    $isOpen && in_array($classification, [
                        'BLOCKING',
                        'CONSTRAINING',
                    ], true),

                'automatic_resolution_allowed' => false,
            ]);
        }

        $openConditions = collect($conditionAnalysis)
            ->where('condition_open', true)
            ->values();

        $resolvedConditions = collect($conditionAnalysis)
            ->where('condition_open', false)
            ->values();

        $blockingConditions = collect($conditionAnalysis)
            ->where('condition_classification', 'BLOCKING')
            ->where('condition_open', true)
            ->values();

        $constrainingConditions = collect($conditionAnalysis)
            ->where('condition_classification', 'CONSTRAINING')
            ->where('condition_open', true)
            ->values();

        $advisoryConditions = collect($conditionAnalysis)
            ->where('condition_classification', 'ADVISORY')
            ->where('condition_open', true)
            ->values();

        $criticalOpenConditions = collect($conditionAnalysis)
            ->filter(
                fn ($condition) =>
                    ($condition['condition_open'] ?? false)
                    && strtoupper((string) ($condition['priority_level'] ?? '')) === 'CRITICAL'
            )
            ->count();

        $highOpenConditions = collect($conditionAnalysis)
            ->filter(
                fn ($condition) =>
                    ($condition['condition_open'] ?? false)
                    && strtoupper((string) ($condition['priority_level'] ?? '')) === 'HIGH'
            )
            ->count();

        $moderateOpenConditions = collect($conditionAnalysis)
            ->filter(
                fn ($condition) =>
                    ($condition['condition_open'] ?? false)
                    && strtoupper((string) ($condition['priority_level'] ?? '')) === 'MODERATE'
            )
            ->count();

        $totalConditions = count($conditionAnalysis);
        $resolvedConditionCount = $resolvedConditions->count();

        $conditionResolutionScore = $totalConditions > 0
            ? round(($resolvedConditionCount / $totalConditions) * 100, 2)
            : 100.0;

        $dominantOpenCondition = $openConditions
            ->sortByDesc(fn ($condition) =>
                (($condition['severity_weight'] ?? 0) * 10)
                + (($condition['blocks_final_decision'] ?? false) ? 1 : 0)
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Evidence intelligence
        |--------------------------------------------------------------------------
        */

        $validatedEvidenceCodes = collect($validatedEvidence)
            ->map(function ($item) {
                if (is_string($item)) {
                    return strtoupper($item);
                }

                return strtoupper((string) (
                    $item['evidence_code']
                    ?? $item['code']
                    ?? ''
                ));
            })
            ->filter()
            ->values()
            ->all();

        $evidenceAnalysis = [];

        foreach ($requirements as $requirement) {
            $evidenceCode = strtoupper(
                (string) ($requirement['evidence_code'] ?? '')
            );

            $evidenceStatus = strtoupper(
                (string) ($requirement['evidence_status'] ?? 'REQUIRED')
            );

            $priorityLevel = strtoupper(
                (string) ($requirement['priority_level'] ?? 'ADVISORY')
            );

            $alreadyAvailable = in_array(
                $evidenceStatus,
                ['AVAILABLE', 'VALIDATED', 'SATISFIED'],
                true
            );

            $validatedByRecord = $evidenceCode !== ''
                && in_array($evidenceCode, $validatedEvidenceCodes, true);

            $evidenceValidated = $alreadyAvailable || $validatedByRecord;
            $evidenceOutstanding = !$evidenceValidated;

            $severityWeight = match ($priorityLevel) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                'ADVISORY' => 25.0,
                default => 25.0,
            };

            $blocksFinalDecision =
                $evidenceOutstanding
                && in_array($priorityLevel, ['CRITICAL', 'HIGH'], true)
                && $evidenceStatus === 'REQUIRED';

            $evidenceAnalysis[] = array_merge($requirement, [
                'evidence_available' => !$evidenceOutstanding,
                'evidence_validated' => $evidenceValidated,
                'evidence_outstanding' => $evidenceOutstanding,
                'severity_weight' => $severityWeight,
                'blocks_final_decision' => $blocksFinalDecision,
                'requires_human_validation' =>
                    $evidenceOutstanding
                    && ($requirement['human_validation_required'] ?? true),

                'automatic_validation_allowed' => false,
            ]);
        }

        $outstandingEvidence = collect($evidenceAnalysis)
            ->where('evidence_outstanding', true)
            ->values();

        $satisfiedEvidence = collect($evidenceAnalysis)
            ->where('evidence_outstanding', false)
            ->values();

        $blockingEvidence = collect($evidenceAnalysis)
            ->where('blocks_final_decision', true)
            ->values();

        $criticalOutstandingEvidence = collect($evidenceAnalysis)
            ->filter(
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false)
                    && strtoupper((string) ($evidence['priority_level'] ?? '')) === 'CRITICAL'
            )
            ->count();

        $highOutstandingEvidence = collect($evidenceAnalysis)
            ->filter(
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false)
                    && strtoupper((string) ($evidence['priority_level'] ?? '')) === 'HIGH'
            )
            ->count();

        $totalEvidenceRequirements = count($evidenceAnalysis);
        $satisfiedEvidenceCount = $satisfiedEvidence->count();

        $evidenceResolutionScore = $totalEvidenceRequirements > 0
            ? round(($satisfiedEvidenceCount / $totalEvidenceRequirements) * 100, 2)
            : 100.0;

        $dominantEvidenceRequirement = $outstandingEvidence
            ->sortByDesc(fn ($evidence) =>
                (($evidence['severity_weight'] ?? 0) * 10)
                + (($evidence['blocks_final_decision'] ?? false) ? 1 : 0)
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Resolution pressure
        |--------------------------------------------------------------------------
        */

        $conditionPressure = min(
            100,
            ($blockingConditions->count() * 15)
            + ($constrainingConditions->count() * 8)
            + ($advisoryConditions->count() * 3)
            + ($criticalOpenConditions * 10)
        );

        $evidencePressure = min(
            100,
            ($blockingEvidence->count() * 20)
            + ($criticalOutstandingEvidence * 15)
            + ($highOutstandingEvidence * 8)
            + ($outstandingEvidence->count() * 4)
        );

        $resolutionPressureScore = round(
            ($conditionPressure * 0.55)
            + ($evidencePressure * 0.45),
            2
        );

        $combinedResolutionScore = round(
            ($conditionResolutionScore * 0.55)
            + ($evidenceResolutionScore * 0.45),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Resolution classification
        |--------------------------------------------------------------------------
        */

        $conditionResolutionStatus = match (true) {
            $blockingConditions->count() > 0
                => 'BLOCKING_CONDITIONS_REMAIN',

            $constrainingConditions->count() > 0
                => 'CONSTRAINING_CONDITIONS_REMAIN',

            $openConditions->count() > 0
                => 'ADVISORY_CONDITIONS_REMAIN',

            default
                => 'ALL_DECISION_CONDITIONS_RESOLVED',
        };

        $evidenceResolutionStatus = match (true) {
            $blockingEvidence->count() > 0
                => 'DECISION_BLOCKING_EVIDENCE_OUTSTANDING',

            $outstandingEvidence->count() > 0
                => 'EVIDENCE_REQUIREMENTS_OUTSTANDING',

            default
                => 'EVIDENCE_REQUIREMENTS_SATISFIED',
        };

        $decisionResolutionStatus = match (true) {
            $blockingConditions->count() > 0
            || $blockingEvidence->count() > 0
                => 'MATERIAL_RESOLUTION_REQUIREMENTS_OUTSTANDING',

            $constrainingConditions->count() > 0
            || $outstandingEvidence->count() > 0
                => 'PARTIAL_RESOLUTION_REQUIREMENTS_OUTSTANDING',

            default
                => 'DECISION_REQUIREMENTS_RESOLVED',
        };

        $governanceValidationReadiness = match (true) {
            $blockingConditions->count() > 0
            || $blockingEvidence->count() > 0
                => 'NOT_READY_FOR_GOVERNANCE_VALIDATION',

            $openConditions->count() > 0
            || $outstandingEvidence->count() > 0
                => 'LIMITED_GOVERNANCE_VALIDATION_READINESS',

            default
                => 'READY_FOR_GOVERNANCE_VALIDATION_REVIEW',
        };

        $finalDecisionProgressionReadiness = match (true) {
            $blockingConditions->count() > 0
            || $blockingEvidence->count() > 0
                => 'FINAL_DECISION_PROGRESSION_BLOCKED',

            $constrainingConditions->count() > 0
            || $outstandingEvidence->count() > 0
                => 'FINAL_DECISION_PROGRESSION_CONSTRAINED',

            default
                => 'READY_FOR_AUTHORIZED_HUMAN_DECISION_REVIEW',
        };

        /*
        |--------------------------------------------------------------------------
        | Human management state
        |--------------------------------------------------------------------------
        */

        $humanManagementAttentionLevel = match (true) {
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidence > 0
                => 'CRITICAL',

            $blockingConditions->count() > 0
            || $blockingEvidence->count() > 0
                => 'HIGH',

            $constrainingConditions->count() > 0
                => 'MODERATE',

            default
                => 'ROUTINE',
        };

        $immediateHumanInterventionRequired =
            $criticalOpenConditions > 0
            || $criticalOutstandingEvidence > 0;

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Strategic plan human decision condition and evidence resolution intelligence is based on human decision record {$humanDecision->id}.",
            "{$totalConditions} human decision condition(s) are represented.",
            "{$openConditions->count()} human decision condition(s) remain open.",
            "{$resolvedConditionCount} human decision condition(s) are currently resolved.",
            "{$blockingConditions->count()} blocking human decision condition(s) remain active.",
            "{$constrainingConditions->count()} constraining human decision condition(s) remain active.",
            "Current condition resolution score is {$conditionResolutionScore}.",
            "{$totalEvidenceRequirements} strategic plan human decision evidence requirement(s) are represented.",
            "{$outstandingEvidence->count()} strategic plan human decision evidence requirement(s) remain outstanding.",
            "{$blockingEvidence->count()} outstanding evidence requirement(s) currently block unrestricted final decision progression.",
            "Current evidence resolution score is {$evidenceResolutionScore}.",
            "Current combined condition and evidence resolution score is {$combinedResolutionScore}.",
            "Current resolution pressure score is {$resolutionPressureScore}.",
            "Current decision resolution status is {$decisionResolutionStatus}.",
            "Current governance validation readiness is {$governanceValidationReadiness}.",
            "Current final decision progression readiness is {$finalDecisionProgressionReadiness}.",
            'Condition and evidence resolution intelligence remains advisory and does not automatically resolve conditions, validate evidence, complete governance validation, or make the final strategic plan decision.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Management priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($dominantOpenCondition) {
            $managementPriorities[] =
                'Address the dominant unresolved human decision condition '
                .($dominantOpenCondition['condition_code'] ?? 'UNKNOWN')
                .' through the authorized human governance process.';
        }

        if ($dominantEvidenceRequirement) {
            $managementPriorities[] =
                'Provide and validate the dominant outstanding evidence requirement '
                .($dominantEvidenceRequirement['evidence_code'] ?? 'UNKNOWN')
                .' through authorized human governance.';
        }

        if ($blockingConditions->count() > 0) {
            $managementPriorities[] =
                'Resolve or formally govern active blocking human decision conditions before unrestricted final strategic plan approval is considered.';
        }

        if ($blockingEvidence->count() > 0) {
            $managementPriorities[] =
                'Provide and validate outstanding decision-blocking evidence before unrestricted final strategic plan approval is considered.';
        }

        if ($constrainingConditions->count() > 0) {
            $managementPriorities[] =
                'Reduce active constraining human decision conditions to improve final decision readiness.';
        }

        if ($outstandingEvidence->count() > 0) {
            $managementPriorities[] =
                'Complete outstanding evidence review and validation through authorized human governance.';
        }

        if ($governanceValidationReadiness === 'NOT_READY_FOR_GOVERNANCE_VALIDATION') {
            $managementPriorities[] =
                'Do not treat the human decision package as ready for governance validation while material blocking requirements remain.';
        }

        $managementPriorities[] =
            'Preserve human decision authority, evidence quality, governance validation, traceability, safety controls, and authority separation throughout condition and evidence resolution.';

        $managementPriorities = array_values(
            array_unique($managementPriorities)
        );

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_HUMAN_DECISION_CONDITION_EVIDENCE_RESOLUTION_INTELLIGENCE_AVAILABLE',

            'strategic_plan_human_decision_id' => $humanDecision->id,
            'human_decision_code' => $humanDecision->human_decision_code,

            'strategic_plan_decision_id' =>
                $humanDecision->strategic_plan_decision_id,

            'strategic_plan_id' =>
                $humanDecision->strategic_plan_id,

            'strategic_snapshot_id' =>
                $humanDecision->strategic_snapshot_id,

            'operational_snapshot_id' =>
                $humanDecision->operational_snapshot_id,

            'lifecycle_snapshot_id' =>
                $humanDecision->lifecycle_snapshot_id,

            'decision_scope' =>
                $humanDecision->decision_scope,

            'resident_id' =>
                $humanDecision->resident_id,

            'resolution_state' => [
                'decision_resolution_status' =>
                    $decisionResolutionStatus,

                'condition_resolution_status' =>
                    $conditionResolutionStatus,

                'evidence_resolution_status' =>
                    $evidenceResolutionStatus,

                'governance_validation_readiness' =>
                    $governanceValidationReadiness,

                'final_decision_progression_readiness' =>
                    $finalDecisionProgressionReadiness,

                'condition_resolution_score' =>
                    $conditionResolutionScore,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,

                'combined_resolution_score' =>
                    $combinedResolutionScore,

                'resolution_pressure_score' =>
                    $resolutionPressureScore,

                'human_management_attention_level' =>
                    $humanManagementAttentionLevel,

                'human_management_attention_required' =>
                    $humanManagementAttentionLevel !== 'ROUTINE',

                'immediate_human_intervention_required' =>
                    $immediateHumanInterventionRequired,

                'final_human_decision_recorded' =>
                    !empty($humanDecision->final_human_decision),

                'decision_made_by_authorized_human' =>
                    (bool) $humanDecision->decision_made_by_authorized_human,

                'governance_validation_completed' =>
                    (bool) $humanDecision->governance_validation_completed,
            ],

            'condition_summary' => [
                'total_conditions' => $totalConditions,
                'open_conditions' => $openConditions->count(),
                'resolved_conditions' => $resolvedConditionCount,
                'blocking_conditions' => $blockingConditions->count(),
                'constraining_conditions' => $constrainingConditions->count(),
                'advisory_conditions' => $advisoryConditions->count(),
                'critical_open_conditions' => $criticalOpenConditions,
                'high_open_conditions' => $highOpenConditions,
                'moderate_open_conditions' => $moderateOpenConditions,
                'condition_resolution_score' => $conditionResolutionScore,
            ],

            'evidence_summary' => [
                'total_evidence_requirements' =>
                    $totalEvidenceRequirements,

                'satisfied_evidence_items' =>
                    $satisfiedEvidenceCount,

                'outstanding_evidence_items' =>
                    $outstandingEvidence->count(),

                'decision_blocking_evidence_items' =>
                    $blockingEvidence->count(),

                'critical_outstanding_evidence_items' =>
                    $criticalOutstandingEvidence,

                'high_outstanding_evidence_items' =>
                    $highOutstandingEvidence,

                'evidence_resolution_score' =>
                    $evidenceResolutionScore,
            ],

            'dominant_open_condition' =>
                $dominantOpenCondition,

            'dominant_evidence_requirement' =>
                $dominantEvidenceRequirement,

            'blocking_conditions' =>
                $blockingConditions->values()->all(),

            'constraining_conditions' =>
                $constrainingConditions->values()->all(),

            'resolved_conditions' =>
                $resolvedConditions->values()->all(),

            'condition_analysis' =>
                $conditionAnalysis,

            'outstanding_evidence' =>
                $outstandingEvidence->values()->all(),

            'satisfied_evidence' =>
                $satisfiedEvidence->values()->all(),

            'evidence_analysis' =>
                $evidenceAnalysis,

            'authorization_context' => [
                'decided_by' => $humanDecision->decided_by,
                'decider_role' => $humanDecision->decider_role,
                'decided_at' => $humanDecision->decided_at,

                'decision_made_by_authorized_human' =>
                    (bool) $humanDecision->decision_made_by_authorized_human,

                'final_human_decision' =>
                    $humanDecision->final_human_decision,

                'final_human_decision_recorded' =>
                    !empty($humanDecision->final_human_decision),
            ],

            'validation_context' => [
                'validated_by' => $humanDecision->validated_by,
                'validator_role' => $humanDecision->validator_role,
                'validated_at' => $humanDecision->validated_at,

                'governance_validation_completed' =>
                    (bool) $humanDecision->governance_validation_completed,

                'governance_validation_required' =>
                    (bool) $humanDecision->governance_validation_required,

                'governance_validation_readiness' =>
                    $governanceValidationReadiness,
            ],

            'human_decision_context' => [
                'human_decision_status' =>
                    $humanDecision->human_decision_status,

                'human_decision_mode' =>
                    $humanDecision->human_decision_mode,

                'prepared_decision' =>
                    $humanDecision->prepared_decision,

                'prepared_decision_status' =>
                    $humanDecision->prepared_decision_status,

                'final_human_decision' =>
                    $humanDecision->final_human_decision,

                'approval_eligibility' =>
                    $humanDecision->approval_eligibility,

                'decision_eligibility_score' =>
                    (float) ($humanDecision->decision_eligibility_score ?? 0),

                'decision_risk_level' =>
                    $humanDecision->decision_risk_level,

                'decision_risk_score' =>
                    (float) ($humanDecision->decision_risk_score ?? 0),

                'decision_priority' =>
                    $humanDecision->decision_priority,

                'decision_priority_score' =>
                    (float) ($humanDecision->decision_priority_score ?? 0),
            ],

            'resolution_findings' =>
                $findings,

            'management_priorities' =>
                $managementPriorities,

            'condition_evidence_resolution_guardrails' => [
                'human_decision_condition_evidence_resolution_intelligence_enabled' => true,

                'resolution_intelligence_is_final_human_decision' => false,
                'resolution_intelligence_makes_governance_decision' => false,
                'resolution_intelligence_records_governance_decision' => false,

                'resolution_intelligence_approves_strategic_plan' => false,
                'resolution_intelligence_rejects_strategic_plan' => false,
                'resolution_intelligence_activates_strategic_plan' => false,

                'resolution_intelligence_changes_human_decision_status' => false,
                'resolution_intelligence_changes_final_human_decision' => false,
                'resolution_intelligence_changes_plan_status' => false,
                'resolution_intelligence_changes_action_state' => false,
                'resolution_intelligence_changes_priority' => false,
                'resolution_intelligence_changes_eligibility' => false,

                'resolution_intelligence_resolves_conditions' => false,
                'resolution_intelligence_resolves_dependencies' => false,
                'resolution_intelligence_validates_evidence' => false,
                'resolution_intelligence_completes_governance_validation' => false,

                'condition_resolution_score_authorizes_approval' => false,
                'evidence_resolution_score_authorizes_approval' => false,
                'combined_resolution_score_authorizes_approval' => false,

                'governance_validation_readiness_completes_validation' => false,
                'final_decision_progression_readiness_records_decision' => false,

                'resolution_intelligence_authorizes_ai_change' => false,
                'resolution_intelligence_authorizes_execution' => false,
                'resolution_intelligence_authorizes_deployment' => false,
                'resolution_intelligence_authorizes_rollback' => false,
                'resolution_intelligence_authorizes_clinical_action' => false,

                'resolution_intelligence_overrides_human_review' => false,
                'resolution_intelligence_overrides_governance_validation' => false,
                'resolution_intelligence_overrides_evidence_requirements' => false,

                'automatic_decision_allowed' => false,
                'automatic_approval_allowed' => false,
                'automatic_rejection_allowed' => false,
                'automatic_activation_allowed' => false,
                'automatic_condition_resolution_allowed' => false,
                'automatic_evidence_validation_allowed' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'final_decision_authority_reserved_for_authorized_human' => true,
                'human_review_required' => true,
                'governance_validation_required' => true,

                'message' =>
                    'Strategic plan human decision condition and evidence resolution intelligence evaluates unresolved decision conditions, evidence requirements, resolution progress, decision-blocking requirements, governance-validation readiness, and final-decision progression readiness for authorized human governance. It does not automatically resolve conditions or dependencies, validate evidence, complete governance validation, make or record the final human decision, approve or reject the strategic plan, activate planning work, modify AI behavior, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }
}