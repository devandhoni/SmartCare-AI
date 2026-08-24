<?php

namespace App\Services;

use App\Models\AIGovernanceStrategicPlanDecision;

class AIGovernanceStrategicPlanDecisionConditionEvidenceIntelligenceEngine
{
    public function analyze(?int $strategicPlanDecisionId = null): array
    {
        $decision = $this->resolveDecision($strategicPlanDecisionId);

        if (!$decision) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_STRATEGIC_PLAN_DECISION_NOT_AVAILABLE',
                'message' => 'No AI governance strategic plan decision record is available for condition and evidence analysis.',
            ];
        }

        $conditions = is_array($decision->decision_conditions)
            ? $decision->decision_conditions
            : [];

        $evidenceItems = is_array($decision->required_evidence)
            ? $decision->required_evidence
            : [];

        $conditionAnalysis = $this->analyzeConditions($conditions);
        $evidenceAnalysis = $this->analyzeEvidence($evidenceItems);

        $conditionSummary = $this->buildConditionSummary($conditionAnalysis);
        $evidenceSummary = $this->buildEvidenceSummary($evidenceAnalysis);

        $dominantCondition = $this->determineDominantCondition(
            $conditionAnalysis
        );

        $dominantEvidenceRequirement = $this->determineDominantEvidence(
            $evidenceAnalysis
        );

        $conditionEvidenceAlignment = $this->analyzeConditionEvidenceAlignment(
            $conditionAnalysis,
            $evidenceAnalysis
        );

        $conditionPressureScore = $this->calculateConditionPressureScore(
            $conditionSummary
        );

        $evidenceReadinessScore = $this->calculateEvidenceReadinessScore(
            $evidenceSummary
        );

        $conditionResolutionScore = $this->calculateConditionResolutionScore(
            $conditionSummary
        );

        $decisionEvidenceReadinessScore =
            $this->calculateDecisionEvidenceReadinessScore(
                $conditionResolutionScore,
                $evidenceReadinessScore
            );

        $conditionEvidenceState = $this->classifyConditionEvidenceState(
            $conditionSummary,
            $evidenceSummary,
            $decisionEvidenceReadinessScore
        );

        $decisionBlockStatus = $this->classifyDecisionBlockStatus(
            $conditionSummary,
            $evidenceSummary
        );

        $humanResolutionReadiness =
            $this->classifyHumanResolutionReadiness(
                $conditionSummary,
                $evidenceSummary,
                $decisionEvidenceReadinessScore
            );

        $outstandingRequirements = $this->buildOutstandingRequirements(
            $conditionAnalysis,
            $evidenceAnalysis
        );

        $managementPriorities = $this->buildManagementPriorities(
            $dominantCondition,
            $dominantEvidenceRequirement,
            $conditionSummary,
            $evidenceSummary
        );

        $findings = $this->buildFindings(
            $decision,
            $conditionSummary,
            $evidenceSummary,
            $conditionPressureScore,
            $conditionResolutionScore,
            $evidenceReadinessScore,
            $decisionEvidenceReadinessScore,
            $conditionEvidenceState,
            $decisionBlockStatus,
            $humanResolutionReadiness,
            $conditionEvidenceAlignment,
            $dominantCondition,
            $dominantEvidenceRequirement
        );

        return [
            'analysis_completed' => true,

            'status' =>
                'GOVERNANCE_STRATEGIC_PLAN_DECISION_CONDITION_EVIDENCE_INTELLIGENCE_AVAILABLE',

            'strategic_plan_decision_id' => $decision->id,
            'decision_code' => $decision->decision_code,

            'strategic_plan_id' => $decision->strategic_plan_id,
            'strategic_snapshot_id' => $decision->strategic_snapshot_id,
            'operational_snapshot_id' => $decision->operational_snapshot_id,
            'lifecycle_snapshot_id' => $decision->lifecycle_snapshot_id,

            'decision_scope' => $decision->decision_scope,
            'resident_id' => $decision->resident_id,

            'condition_evidence_state' => [
                'condition_evidence_status' => $conditionEvidenceState,
                'decision_block_status' => $decisionBlockStatus,

                'condition_pressure_score' => $conditionPressureScore,
                'condition_resolution_score' => $conditionResolutionScore,

                'evidence_readiness_score' => $evidenceReadinessScore,

                'decision_evidence_readiness_score' =>
                    $decisionEvidenceReadinessScore,

                'human_resolution_readiness' =>
                    $humanResolutionReadiness,

                'human_management_attention_required' =>
                    $this->requiresHumanManagementAttention(
                        $conditionSummary,
                        $evidenceSummary
                    ),

                'immediate_human_intervention_required' =>
                    $this->requiresImmediateHumanIntervention(
                        $conditionSummary,
                        $evidenceSummary
                    ),
            ],

            'condition_summary' => $conditionSummary,

            'evidence_summary' => $evidenceSummary,

            'dominant_open_condition' => $dominantCondition,

            'dominant_evidence_requirement' =>
                $dominantEvidenceRequirement,

            'condition_analysis' => $conditionAnalysis,

            'evidence_analysis' => $evidenceAnalysis,

            'condition_evidence_alignment' =>
                $conditionEvidenceAlignment,

            'outstanding_requirements' =>
                $outstandingRequirements,

            'decision_context' => [
                'decision_status' => $decision->decision_status,
                'decision_mode' => $decision->decision_mode,

                'prepared_decision' => $decision->decision,

                'decision_priority' => $decision->decision_priority,
                'decision_priority_score' =>
                    (float) $decision->decision_priority_score,

                'planning_readiness' =>
                    $decision->planning_readiness,

                'planning_readiness_score' =>
                    (float) $decision->planning_readiness_score,

                'plan_risk_level' =>
                    $decision->plan_risk_level,

                'plan_risk_score' =>
                    (float) $decision->plan_risk_score,

                'dependency_feasibility_status' =>
                    $decision->dependency_feasibility_status,

                'dependency_adjusted_feasibility_score' =>
                    (float) $decision->dependency_adjusted_feasibility_score,

                'blocking_dependency_count' =>
                    (int) $decision->blocking_dependency_count,

                'constraining_dependency_count' =>
                    (int) $decision->constraining_dependency_count,

                'reviewed' =>
                    !is_null($decision->reviewed_at),

                'reviewed_by' =>
                    $decision->reviewed_by,

                'reviewer_role' =>
                    $decision->reviewer_role,

                'reviewed_at' =>
                    $decision->reviewed_at,

                'final_human_decision_recorded' =>
                    !is_null($decision->decided_at),
            ],

            'review_context' => [
                'human_review_required' =>
                    (bool) $decision->human_review_required,

                'governance_validation_required' =>
                    (bool) $decision->governance_validation_required,

                'reviewed_by' =>
                    $decision->reviewed_by,

                'reviewer_role' =>
                    $decision->reviewer_role,

                'reviewed_at' =>
                    $decision->reviewed_at,

                'decided_at' =>
                    $decision->decided_at,

                'review_state' =>
                    $this->resolveReviewState($decision),
            ],

            'source_context' => is_array($decision->source_context)
                ? $decision->source_context
                : [],

            'condition_evidence_findings' => $findings,

            'management_priorities' => $managementPriorities,

            'condition_evidence_guardrails' =>
                $this->guardrails(),
        ];
    }

    private function resolveDecision(
        ?int $strategicPlanDecisionId
    ): ?AIGovernanceStrategicPlanDecision {
        if ($strategicPlanDecisionId !== null) {
            return AIGovernanceStrategicPlanDecision::query()
                ->find($strategicPlanDecisionId);
        }

        return AIGovernanceStrategicPlanDecision::query()
            ->latest('id')
            ->first();
    }

    private function analyzeConditions(array $conditions): array
    {
        $analysis = [];

        foreach ($conditions as $condition) {
            $priority = strtoupper(
                (string) ($condition['priority_level'] ?? 'ADVISORY')
            );

            $status = strtoupper(
                (string) ($condition['condition_status'] ?? 'OPEN')
            );

            $isOpen = in_array(
                $status,
                [
                    'OPEN',
                    'PENDING',
                    'UNRESOLVED',
                    'REQUIRES_REVIEW',
                ],
                true
            );

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                default => 25.0,
            };

            $conditionClassification = match ($priority) {
                'CRITICAL' => 'CRITICAL_BLOCKING',
                'HIGH' => 'BLOCKING',
                'MODERATE' => 'CONSTRAINING',
                default => 'ADVISORY',
            };

            $analysis[] = array_merge(
                $condition,
                [
                    'condition_open' => $isOpen,

                    'condition_classification' =>
                        $conditionClassification,

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_final_decision' =>
                        $isOpen &&
                        in_array(
                            $priority,
                            ['CRITICAL', 'HIGH'],
                            true
                        ),

                    'constrains_final_decision' =>
                        $isOpen &&
                        $priority === 'MODERATE',

                    'requires_human_resolution' =>
                        (bool) (
                            $condition['human_review_required']
                            ?? true
                        ),

                    'automatic_resolution_allowed' =>
                        false,
                ]
            );
        }

        return $analysis;
    }

    private function analyzeEvidence(array $evidenceItems): array
    {
        $analysis = [];

        foreach ($evidenceItems as $evidence) {
            $status = strtoupper(
                (string) ($evidence['evidence_status'] ?? 'REQUIRED')
            );

            $priority = strtoupper(
                (string) ($evidence['priority_level'] ?? 'MODERATE')
            );

            $isAvailable = in_array(
                $status,
                [
                    'AVAILABLE',
                    'VALIDATED',
                    'SATISFIED',
                    'COMPLETE',
                ],
                true
            );

            $isRequired = !$isAvailable;

            $severityWeight = match ($priority) {
                'CRITICAL' => 100.0,
                'HIGH' => 75.0,
                'MODERATE' => 50.0,
                default => 25.0,
            };

            $analysis[] = array_merge(
                $evidence,
                [
                    'evidence_available' => $isAvailable,

                    'evidence_outstanding' => $isRequired,

                    'severity_weight' =>
                        $severityWeight,

                    'blocks_final_decision' =>
                        $isRequired &&
                        in_array(
                            $priority,
                            ['CRITICAL', 'HIGH'],
                            true
                        ),

                    'requires_human_validation' =>
                        (bool) (
                            $evidence['human_validation_required']
                            ?? true
                        ),

                    'automatic_validation_allowed' =>
                        false,
                ]
            );
        }

        return $analysis;
    }

    private function buildConditionSummary(
        array $conditionAnalysis
    ): array {
        $open = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_open'] ?? false) === true
            )
        );

        return [
            'total_conditions' =>
                count($conditionAnalysis),

            'open_conditions' =>
                count($open),

            'resolved_conditions' =>
                count($conditionAnalysis) - count($open),

            'critical_open_conditions' =>
                $this->countByPriority(
                    $open,
                    'CRITICAL',
                    'priority_level'
                ),

            'high_open_conditions' =>
                $this->countByPriority(
                    $open,
                    'HIGH',
                    'priority_level'
                ),

            'moderate_open_conditions' =>
                $this->countByPriority(
                    $open,
                    'MODERATE',
                    'priority_level'
                ),

            'advisory_open_conditions' =>
                $this->countByPriority(
                    $open,
                    'ADVISORY',
                    'priority_level'
                ),

            'blocking_conditions' =>
                count(
                    array_filter(
                        $open,
                        fn ($condition) =>
                            ($condition['blocks_final_decision'] ?? false)
                            === true
                    )
                ),

            'constraining_conditions' =>
                count(
                    array_filter(
                        $open,
                        fn ($condition) =>
                            (
                                $condition[
                                    'constrains_final_decision'
                                ] ?? false
                            ) === true
                    )
                ),
        ];
    }

    private function buildEvidenceSummary(
        array $evidenceAnalysis
    ): array {
        $available = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_available'] ?? false) === true
            )
        );

        $outstanding = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false) === true
            )
        );

        return [
            'total_evidence_items' =>
                count($evidenceAnalysis),

            'available_evidence_items' =>
                count($available),

            'outstanding_evidence_items' =>
                count($outstanding),

            'critical_outstanding_evidence_items' =>
                $this->countByPriority(
                    $outstanding,
                    'CRITICAL',
                    'priority_level'
                ),

            'high_outstanding_evidence_items' =>
                $this->countByPriority(
                    $outstanding,
                    'HIGH',
                    'priority_level'
                ),

            'moderate_outstanding_evidence_items' =>
                $this->countByPriority(
                    $outstanding,
                    'MODERATE',
                    'priority_level'
                ),

            'decision_blocking_evidence_items' =>
                count(
                    array_filter(
                        $outstanding,
                        fn ($evidence) =>
                            ($evidence['blocks_final_decision'] ?? false)
                            === true
                    )
                ),
        ];
    }

    private function determineDominantCondition(
        array $conditionAnalysis
    ): ?array {
        $open = array_values(
            array_filter(
                $conditionAnalysis,
                fn ($condition) =>
                    ($condition['condition_open'] ?? false) === true
            )
        );

        if (empty($open)) {
            return null;
        }

        usort(
            $open,
            fn ($a, $b) =>
                ($b['severity_weight'] ?? 0)
                <=>
                ($a['severity_weight'] ?? 0)
        );

        return $open[0];
    }

    private function determineDominantEvidence(
        array $evidenceAnalysis
    ): ?array {
        $outstanding = array_values(
            array_filter(
                $evidenceAnalysis,
                fn ($evidence) =>
                    ($evidence['evidence_outstanding'] ?? false) === true
            )
        );

        if (empty($outstanding)) {
            return null;
        }

        usort(
            $outstanding,
            fn ($a, $b) =>
                ($b['severity_weight'] ?? 0)
                <=>
                ($a['severity_weight'] ?? 0)
        );

        return $outstanding[0];
    }

    private function analyzeConditionEvidenceAlignment(
        array $conditions,
        array $evidenceItems
    ): array {
        $alignment = [];

        foreach ($conditions as $condition) {
            $conditionCode =
                $condition['condition_code'] ?? null;

            $matchingEvidence = [];

            foreach ($evidenceItems as $evidence) {
                $sourceDependencyCode =
                    $evidence['source_dependency_code'] ?? null;

                $evidenceCode =
                    $evidence['evidence_code'] ?? null;

                if (
                    $conditionCode !== null &&
                    (
                        $conditionCode === $sourceDependencyCode ||
                        $conditionCode === $evidenceCode
                    )
                ) {
                    $matchingEvidence[] = $evidence;
                }
            }

            $alignment[] = [
                'condition_code' =>
                    $conditionCode,

                'condition_type' =>
                    $condition['condition_type'] ?? null,

                'condition_status' =>
                    $condition['condition_status'] ?? null,

                'priority_level' =>
                    $condition['priority_level'] ?? null,

                'matching_evidence_count' =>
                    count($matchingEvidence),

                'evidence_linkage_present' =>
                    count($matchingEvidence) > 0,

                'matching_evidence' =>
                    $matchingEvidence,

                'human_review_required' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        return $alignment;
    }

    private function calculateConditionPressureScore(
        array $summary
    ): float {
        $score =
            ($summary['critical_open_conditions'] * 25)
            +
            ($summary['high_open_conditions'] * 15)
            +
            ($summary['moderate_open_conditions'] * 8)
            +
            ($summary['advisory_open_conditions'] * 3);

        return round(
            min(100, $score),
            2
        );
    }

    private function calculateConditionResolutionScore(
        array $summary
    ): float {
        $total =
            (int) $summary['total_conditions'];

        if ($total === 0) {
            return 100.0;
        }

        return round(
            (
                $summary['resolved_conditions']
                / $total
            ) * 100,
            2
        );
    }

    private function calculateEvidenceReadinessScore(
        array $summary
    ): float {
        $total =
            (int) $summary['total_evidence_items'];

        if ($total === 0) {
            return 100.0;
        }

        return round(
            (
                $summary['available_evidence_items']
                / $total
            ) * 100,
            2
        );
    }

    private function calculateDecisionEvidenceReadinessScore(
        float $conditionResolutionScore,
        float $evidenceReadinessScore
    ): float {
        return round(
            (
                ($conditionResolutionScore * 0.55)
                +
                ($evidenceReadinessScore * 0.45)
            ),
            2
        );
    }

    private function classifyConditionEvidenceState(
        array $conditionSummary,
        array $evidenceSummary,
        float $readinessScore
    ): string {
        if (
            $conditionSummary['critical_open_conditions'] > 0 ||
            $evidenceSummary[
                'critical_outstanding_evidence_items'
            ] > 0
        ) {
            return 'CRITICAL_CONDITION_EVIDENCE_BLOCK';
        }

        if (
            $conditionSummary['blocking_conditions'] > 0 ||
            $evidenceSummary[
                'decision_blocking_evidence_items'
            ] > 0
        ) {
            return 'MATERIAL_CONDITION_EVIDENCE_BLOCK';
        }

        if ($readinessScore < 50) {
            return 'LOW_CONDITION_EVIDENCE_READINESS';
        }

        if ($readinessScore < 75) {
            return 'PARTIAL_CONDITION_EVIDENCE_READINESS';
        }

        return 'CONTROLLED_CONDITION_EVIDENCE_READINESS';
    }

    private function classifyDecisionBlockStatus(
        array $conditionSummary,
        array $evidenceSummary
    ): string {
        if (
            $conditionSummary['critical_open_conditions'] > 0 ||
            $evidenceSummary[
                'critical_outstanding_evidence_items'
            ] > 0
        ) {
            return 'CRITICAL_DECISION_BLOCK';
        }

        if (
            $conditionSummary['blocking_conditions'] > 0 ||
            $evidenceSummary[
                'decision_blocking_evidence_items'
            ] > 0
        ) {
            return 'DECISION_BLOCKED_BY_MATERIAL_REQUIREMENTS';
        }

        if (
            $conditionSummary['open_conditions'] > 0 ||
            $evidenceSummary['outstanding_evidence_items'] > 0
        ) {
            return 'DECISION_CONSTRAINED_BY_OUTSTANDING_REQUIREMENTS';
        }

        return 'NO_MATERIAL_CONDITION_EVIDENCE_BLOCK';
    }

    private function classifyHumanResolutionReadiness(
        array $conditionSummary,
        array $evidenceSummary,
        float $readinessScore
    ): string {
        if (
            $conditionSummary['critical_open_conditions'] > 0 ||
            $evidenceSummary[
                'critical_outstanding_evidence_items'
            ] > 0
        ) {
            return 'IMMEDIATE_HUMAN_RESOLUTION_REQUIRED';
        }

        if (
            $conditionSummary['blocking_conditions'] > 0 ||
            $evidenceSummary[
                'decision_blocking_evidence_items'
            ] > 0
        ) {
            return 'MATERIAL_HUMAN_RESOLUTION_REQUIRED';
        }

        if ($readinessScore < 50) {
            return 'SUBSTANTIAL_HUMAN_REVIEW_WORK_REQUIRED';
        }

        if ($readinessScore < 75) {
            return 'PARTIAL_HUMAN_RESOLUTION_READINESS';
        }

        return 'CONTROLLED_HUMAN_RESOLUTION_READINESS';
    }

    private function buildOutstandingRequirements(
        array $conditionAnalysis,
        array $evidenceAnalysis
    ): array {
        $requirements = [];

        foreach ($conditionAnalysis as $condition) {
            if (
                ($condition['condition_open'] ?? false)
                !== true
            ) {
                continue;
            }

            $requirements[] = [
                'requirement_type' =>
                    'DECISION_CONDITION',

                'requirement_code' =>
                    $condition['condition_code'] ?? null,

                'category' =>
                    $condition['condition_type'] ?? null,

                'priority_level' =>
                    $condition['priority_level'] ?? null,

                'status' =>
                    $condition['condition_status'] ?? null,

                'requirement' =>
                    $condition['condition'] ?? null,

                'blocks_final_decision' =>
                    $condition['blocks_final_decision']
                    ?? false,

                'requires_human_resolution' =>
                    true,

                'automatic_resolution_allowed' =>
                    false,
            ];
        }

        foreach ($evidenceAnalysis as $evidence) {
            if (
                ($evidence['evidence_outstanding'] ?? false)
                !== true
            ) {
                continue;
            }

            $requirements[] = [
                'requirement_type' =>
                    'EVIDENCE_REQUIREMENT',

                'requirement_code' =>
                    $evidence['evidence_code'] ?? null,

                'category' =>
                    $evidence['evidence_type'] ?? null,

                'priority_level' =>
                    $evidence['priority_level'] ?? null,

                'status' =>
                    $evidence['evidence_status'] ?? null,

                'requirement' =>
                    $evidence['requirement'] ?? null,

                'blocks_final_decision' =>
                    $evidence['blocks_final_decision']
                    ?? false,

                'requires_human_validation' =>
                    true,

                'automatic_validation_allowed' =>
                    false,
            ];
        }

        return $requirements;
    }

    private function buildManagementPriorities(
        ?array $dominantCondition,
        ?array $dominantEvidence,
        array $conditionSummary,
        array $evidenceSummary
    ): array {
        $priorities = [];

        if ($dominantCondition) {
            $priorities[] =
                'Address the dominant open strategic plan decision condition '
                . ($dominantCondition['condition_code'] ?? 'UNKNOWN')
                . ' through the established human governance process.';
        }

        if ($dominantEvidence) {
            $priorities[] =
                'Provide and validate the highest-priority outstanding evidence requirement '
                . ($dominantEvidence['evidence_code'] ?? 'UNKNOWN')
                . ' before final strategic plan approval is considered.';
        }

        if ($conditionSummary['blocking_conditions'] > 0) {
            $priorities[] =
                'Resolve or formally govern active blocking decision conditions before recording an unrestricted final strategic plan approval.';
        }

        if (
            $evidenceSummary[
                'decision_blocking_evidence_items'
            ] > 0
        ) {
            $priorities[] =
                'Complete outstanding decision-blocking evidence requirements through authorized human validation.';
        }

        if (
            $conditionSummary['constraining_conditions'] > 0
        ) {
            $priorities[] =
                'Reduce remaining constraining decision conditions to improve human decision readiness.';
        }

        $priorities[] =
            'Maintain complete traceability between decision conditions, supporting evidence, strategic plan intelligence, and the final human governance decision.';

        $priorities[] =
            'Ensure evidence is validated by authorized human governance before any condition is treated as satisfied.';

        $priorities[] =
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation throughout strategic plan decision progression.';

        return array_values(
            array_unique($priorities)
        );
    }

    private function buildFindings(
        AIGovernanceStrategicPlanDecision $decision,
        array $conditionSummary,
        array $evidenceSummary,
        float $conditionPressureScore,
        float $conditionResolutionScore,
        float $evidenceReadinessScore,
        float $decisionEvidenceReadinessScore,
        string $conditionEvidenceState,
        string $decisionBlockStatus,
        string $humanResolutionReadiness,
        array $alignment,
        ?array $dominantCondition,
        ?array $dominantEvidence
    ): array {
        $findings = [
            'Strategic plan decision condition and evidence intelligence is based on decision record '
                . $decision->id . '.',

            $conditionSummary['total_conditions']
                . ' strategic plan decision condition(s) are represented.',

            $conditionSummary['open_conditions']
                . ' strategic plan decision condition(s) remain open.',

            $conditionSummary['blocking_conditions']
                . ' blocking decision condition(s) remain active.',

            $conditionSummary['constraining_conditions']
                . ' constraining decision condition(s) remain active.',

            $evidenceSummary['total_evidence_items']
                . ' strategic plan decision evidence item(s) are represented.',

            $evidenceSummary['outstanding_evidence_items']
                . ' evidence requirement(s) remain outstanding.',

            $evidenceSummary['available_evidence_items']
                . ' evidence item(s) are currently available.',

            'Current decision condition pressure score is '
                . $conditionPressureScore . '.',

            'Current decision condition resolution score is '
                . $conditionResolutionScore . '.',

            'Current evidence readiness score is '
                . $evidenceReadinessScore . '.',

            'Current combined condition and evidence readiness score is '
                . $decisionEvidenceReadinessScore . '.',

            'Current condition and evidence state is '
                . $conditionEvidenceState . '.',

            'Current decision blocking status is '
                . $decisionBlockStatus . '.',

            'Current human resolution readiness is '
                . $humanResolutionReadiness . '.',

            count($alignment)
                . ' decision condition-to-evidence alignment record(s) were evaluated.',
        ];

        if ($dominantCondition) {
            $findings[] =
                'Current dominant open decision condition is '
                . ($dominantCondition['condition_code'] ?? 'UNKNOWN')
                . ' with '
                . ($dominantCondition['priority_level'] ?? 'UNKNOWN')
                . ' priority.';
        }

        if ($dominantEvidence) {
            $findings[] =
                'Current dominant outstanding evidence requirement is '
                . ($dominantEvidence['evidence_code'] ?? 'UNKNOWN')
                . ' with '
                . ($dominantEvidence['priority_level'] ?? 'UNKNOWN')
                . ' priority.';
        }

        if (
            $conditionSummary['critical_open_conditions'] === 0 &&
            $evidenceSummary[
                'critical_outstanding_evidence_items'
            ] === 0
        ) {
            $findings[] =
                'No critical strategic plan decision condition or critical outstanding evidence requirement is currently detected.';
        }

        $findings[] =
            'Condition and evidence intelligence remains advisory and does not resolve conditions, validate evidence, approve or reject the strategic plan, or record the final governance decision.';

        return $findings;
    }

    private function requiresHumanManagementAttention(
        array $conditionSummary,
        array $evidenceSummary
    ): bool {
        return
            $conditionSummary['open_conditions'] > 0
            ||
            $evidenceSummary['outstanding_evidence_items'] > 0;
    }

    private function requiresImmediateHumanIntervention(
        array $conditionSummary,
        array $evidenceSummary
    ): bool {
        return
            $conditionSummary['critical_open_conditions'] > 0
            ||
            $evidenceSummary[
                'critical_outstanding_evidence_items'
            ] > 0;
    }

    private function resolveReviewState(
        AIGovernanceStrategicPlanDecision $decision
    ): string {
        if (!is_null($decision->decided_at)) {
            return 'FINAL_HUMAN_DECISION_RECORDED';
        }

        if (!is_null($decision->reviewed_at)) {
            return 'HUMAN_GOVERNANCE_REVIEW_COMPLETED';
        }

        return 'AWAITING_HUMAN_GOVERNANCE_REVIEW';
    }

    private function countByPriority(
        array $items,
        string $priority,
        string $key
    ): int {
        return count(
            array_filter(
                $items,
                fn ($item) =>
                    strtoupper(
                        (string) ($item[$key] ?? '')
                    ) === $priority
            )
        );
    }

    private function guardrails(): array
    {
        return [
            'strategic_plan_decision_condition_evidence_intelligence_enabled'
                => true,

            'condition_analysis_is_human_final_decision'
                => false,

            'condition_analysis_is_governance_approval'
                => false,

            'condition_analysis_is_governance_rejection'
                => false,

            'condition_analysis_changes_decision_status'
                => false,

            'condition_analysis_changes_plan_status'
                => false,

            'condition_analysis_changes_action_state'
                => false,

            'condition_analysis_resolves_conditions'
                => false,

            'condition_analysis_resolves_dependencies'
                => false,

            'evidence_analysis_validates_evidence'
                => false,

            'evidence_analysis_marks_evidence_satisfied'
                => false,

            'evidence_readiness_authorizes_approval'
                => false,

            'condition_resolution_score_authorizes_approval'
                => false,

            'condition_evidence_readiness_authorizes_execution'
                => false,

            'condition_evidence_intelligence_activates_plan'
                => false,

            'condition_evidence_intelligence_authorizes_ai_change'
                => false,

            'condition_evidence_intelligence_authorizes_execution'
                => false,

            'condition_evidence_intelligence_authorizes_deployment'
                => false,

            'condition_evidence_intelligence_authorizes_rollback'
                => false,

            'condition_evidence_intelligence_authorizes_clinical_action'
                => false,

            'condition_evidence_intelligence_overrides_human_review'
                => false,

            'condition_evidence_intelligence_overrides_evidence_requirements'
                => false,

            'automatic_condition_resolution_allowed'
                => false,

            'automatic_evidence_validation_allowed'
                => false,

            'automatic_execution_allowed'
                => false,

            'automatic_change_allowed'
                => false,

            'automatic_deployment_allowed'
                => false,

            'automatic_rollback_allowed'
                => false,

            'automatic_clinical_action_allowed'
                => false,

            'human_review_required'
                => true,

            'governance_validation_required'
                => true,

            'message' =>
                'Governance strategic plan decision condition and evidence intelligence evaluates open decision conditions, outstanding evidence requirements, condition-evidence alignment, decision blocking requirements, and human resolution readiness for authorized human governance review only. It does not resolve conditions or dependencies, validate or satisfy evidence, make or record the final governance decision, approve or reject the strategic plan, activate planning work, change plan or action state, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}