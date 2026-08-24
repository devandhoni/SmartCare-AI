<?php

namespace App\Services;

class AIGovernanceExecutiveStrategicIntelligenceEngine
{
    public function __construct(
        protected AIGovernanceStrategicStateIntelligenceEngine $strategicStateEngine,
        protected AIGovernanceStrategicCapacityDemandIntelligenceEngine $capacityDemandEngine,
        protected AIGovernanceStrategicConstraintIntelligenceEngine $constraintEngine,
        protected AIGovernanceStrategicRiskIntelligenceEngine $riskEngine,
        protected AIGovernanceStrategicRecommendationIntelligenceEngine $recommendationEngine,
    ) {
    }

    public function analyze(?int $strategicSnapshotId = null): array
    {
        $strategicState = $this->strategicStateEngine->analyze($strategicSnapshotId);
        $capacityDemand = $this->capacityDemandEngine->analyze($strategicSnapshotId);
        $constraints = $this->constraintEngine->analyze($strategicSnapshotId);
        $risk = $this->riskEngine->analyze($strategicSnapshotId);
        $recommendations = $this->recommendationEngine->analyze($strategicSnapshotId);

        if (
            !($strategicState['analysis_completed'] ?? false) ||
            !($capacityDemand['analysis_completed'] ?? false) ||
            !($constraints['analysis_completed'] ?? false) ||
            !($risk['analysis_completed'] ?? false) ||
            !($recommendations['analysis_completed'] ?? false)
        ) {
            return [
                'analysis_completed' => false,
                'status' => 'GOVERNANCE_EXECUTIVE_STRATEGIC_INTELLIGENCE_UNAVAILABLE',
                'message' => 'Required strategic governance intelligence is not currently available.',
            ];
        }

        $state = $strategicState['strategic_state'] ?? [];
        $capacity = $capacityDemand['capacity_demand_state'] ?? [];
        $constraintState = $constraints['constraint_state'] ?? [];
        $constraintSummary = $constraints['constraint_summary'] ?? [];
        $riskState = $risk['strategic_risk_state'] ?? [];
        $riskSummary = $risk['risk_summary'] ?? [];
        $recommendationState = $recommendations['recommendation_state'] ?? [];
        $recommendationSummary = $recommendations['recommendation_summary'] ?? [];
        $workContext = $recommendations['governance_work_context'] ?? [];

        $strategicReadinessScore = (float) ($state['strategic_readiness_score'] ?? 0);
        $strategicBalanceScore = (float) ($state['strategic_balance_score'] ?? 0);
        $capacityScore = (float) ($capacity['capacity_score'] ?? 0);
        $pressureScore = (float) ($capacity['demand_pressure_score'] ?? 0);
        $capacityDemandGap = (float) ($capacity['capacity_demand_gap'] ?? 0);
        $constraintScore = (float) ($constraintState['constraint_score'] ?? 0);
        $strategicRiskScore = (float) ($riskState['strategic_risk_score'] ?? 0);

        $actionClosurePercentage = (float) ($workContext['action_closure_percentage'] ?? 0);
        $decisionCompletionPercentage = (float) ($workContext['decision_completion_percentage'] ?? 0);

        $criticalRecommendations = (int) ($recommendationSummary['critical_recommendations'] ?? 0);
        $highRecommendations = (int) ($recommendationSummary['high_recommendations'] ?? 0);

        $criticalRiskSignals = (int) ($riskSummary['critical_signals'] ?? 0);
        $highRiskSignals = (int) ($riskSummary['high_signals'] ?? 0);

        $criticalConstraints = (int) ($constraintSummary['critical_constraints'] ?? 0);
        $highConstraints = (int) ($constraintSummary['high_constraints'] ?? 0);

        $governanceIntegrityIntact =
            (bool) ($riskState['governance_integrity_intact'] ?? true) &&
            (bool) ($recommendationState['governance_integrity_intact'] ?? true);

        $baseExecutiveScore = round(
            (
                $strategicReadinessScore +
                $strategicBalanceScore +
                $capacityScore +
                $actionClosurePercentage +
                $decisionCompletionPercentage
            ) / 5,
            2
        );

        $strategicPressureScore = round(
            (
                $pressureScore +
                $constraintScore +
                $strategicRiskScore
            ) / 3,
            2
        );

        $pressureAdjustment = round($strategicPressureScore * 0.10, 2);

        $executiveStrategicScore = round(
            max(0, min(100, $baseExecutiveScore - $pressureAdjustment)),
            2
        );

        $executiveStrategicStatus = $this->determineExecutiveStrategicStatus(
            $strategicRiskScore,
            $constraintScore,
            $criticalRiskSignals,
            $criticalConstraints
        );

        $executiveReadiness = $this->determineExecutiveReadiness(
            $executiveStrategicScore,
            $strategicReadinessScore,
            $capacityScore,
            $pressureScore
        );

        $executiveConfidence = $this->determineExecutiveConfidence(
            $state['strategic_confidence'] ?? null,
            $strategicRiskScore,
            $governanceIntegrityIntact
        );

        $managementEscalationRecommended =
            $strategicRiskScore >= 70 ||
            $constraintScore >= 75 ||
            $highRecommendations >= 3 ||
            $highRiskSignals >= 3;

        $immediateEscalationRequired =
            $criticalRiskSignals > 0 ||
            $criticalConstraints > 0 ||
            $criticalRecommendations > 0 ||
            (bool) ($riskState['immediate_human_intervention_required'] ?? false);

        $topRecommendation = $recommendations['top_recommendation'] ?? null;

        $executiveAttentionItems = [];

        if (($workContext['high_priority_active_actions'] ?? 0) > 0) {
            $executiveAttentionItems[] =
                ($workContext['high_priority_active_actions'] ?? 0) .
                ' high-priority governance action(s) remain active.';
        }

        if (($workContext['critical_priority_active_actions'] ?? 0) > 0) {
            $executiveAttentionItems[] =
                ($workContext['critical_priority_active_actions'] ?? 0) .
                ' critical-priority governance action(s) remain active.';
        }

        if (($workContext['pending_human_reviews'] ?? 0) > 0) {
            $executiveAttentionItems[] =
                ($workContext['pending_human_reviews'] ?? 0) .
                ' governance action(s) remain pending human review.';
        }

        if (($workContext['evidence_waiting_actions'] ?? 0) > 0) {
            $executiveAttentionItems[] =
                ($workContext['evidence_waiting_actions'] ?? 0) .
                ' governance action(s) remain dependent on additional validated evidence.';
        }

        if (($workContext['deferred_actions'] ?? 0) > 0) {
            $executiveAttentionItems[] =
                ($workContext['deferred_actions'] ?? 0) .
                ' governance action(s) remain deferred.';
        }

        if ($capacityScore < 50) {
            $executiveAttentionItems[] =
                "Governance capacity remains constrained at score {$capacityScore}.";
        }

        if ($pressureScore >= 60) {
            $executiveAttentionItems[] =
                "Governance demand pressure remains high at score {$pressureScore}.";
        }

        if ($capacityDemandGap >= 20) {
            $executiveAttentionItems[] =
                "Governance demand exceeds available capacity by {$capacityDemandGap} percentage point(s).";
        }

        if ($constraintScore >= 75) {
            $executiveAttentionItems[] =
                'Material strategic governance constraints remain active.';
        }

        if ($strategicRiskScore >= 70) {
            $executiveAttentionItems[] =
                'Strategic governance risk remains elevated and requires continued human management attention.';
        }

        $executivePriorities = array_values(array_unique(array_filter([
            $topRecommendation['recommendation'] ?? null,
            'Reduce governance demand pressure before materially expanding strategic workload.',
            'Strengthen governance capacity while preserving existing human-review and evidence controls.',
            'Increase validated evidence availability for governance work that cannot currently progress.',
            'Complete pending human governance reviews in descending priority order.',
            'Improve formal governance action closure progression to release constrained capacity.',
            'Reduce material strategic constraints before increasing governance workload.',
            'Maintain elevated human management visibility while strategic risk remains high.',
            'Preserve human review, governance validation, evidence quality, safety controls, and authority separation throughout strategic improvement.',
        ])));

        $executiveSummary =
            'Strategic governance currently operates with ' .
            ($state['strategic_health'] ?? 'UNKNOWN') .
            ' strategic health. Readiness is ' .
            ($state['strategic_readiness'] ?? 'UNKNOWN') .
            " with score {$strategicReadinessScore}, governance capacity is " .
            ($capacity['capacity_status'] ?? 'UNKNOWN') .
            " with score {$capacityScore}, and demand pressure is " .
            ($capacity['demand_pressure_status'] ?? 'UNKNOWN') .
            " with score {$pressureScore}. Strategic constraint level is " .
            ($constraintState['constraint_level'] ?? 'UNKNOWN') .
            " with score {$constraintScore}, while strategic risk is " .
            ($riskState['strategic_risk_level'] ?? 'UNKNOWN') .
            " with score {$strategicRiskScore}. Executive strategic status is {$executiveStrategicStatus} " .
            "with readiness {$executiveReadiness} and executive strategic score {$executiveStrategicScore}.";

        return [
            'analysis_completed' => true,
            'status' => 'GOVERNANCE_EXECUTIVE_STRATEGIC_INTELLIGENCE_AVAILABLE',

            'strategic_snapshot_id' => $strategicState['strategic_snapshot_id'] ?? null,
            'operational_snapshot_id' => $strategicState['operational_snapshot_id'] ?? null,
            'lifecycle_snapshot_id' => $strategicState['lifecycle_snapshot_id'] ?? null,
            'snapshot_scope' => $strategicState['snapshot_scope'] ?? null,
            'resident_id' => $strategicState['resident_id'] ?? null,

            'executive_strategic_state' => [
                'executive_strategic_status' => $executiveStrategicStatus,
                'executive_readiness' => $executiveReadiness,
                'executive_confidence' => $executiveConfidence,
                'executive_strategic_score' => $executiveStrategicScore,
                'governance_integrity_intact' => $governanceIntegrityIntact,
                'management_escalation_recommended' => $managementEscalationRecommended,
                'immediate_escalation_required' => $immediateEscalationRequired,
            ],

            'executive_summary' => $executiveSummary,

            'strategic_context' => [
                'strategic_status' => $state['strategic_status'] ?? null,
                'strategic_health' => $state['strategic_health'] ?? null,
                'strategic_readiness' => $state['strategic_readiness'] ?? null,
                'strategic_readiness_score' => $strategicReadinessScore,
                'strategic_balance_score' => $strategicBalanceScore,
                'strategic_maturity' => $state['strategic_maturity'] ?? null,
                'strategic_confidence' => $state['strategic_confidence'] ?? null,
            ],

            'capacity_demand_context' => [
                'capacity_status' => $capacity['capacity_status'] ?? null,
                'capacity_score' => $capacityScore,
                'capacity_adequacy' => $capacity['capacity_adequacy'] ?? null,
                'demand_pressure_status' => $capacity['demand_pressure_status'] ?? null,
                'demand_pressure_score' => $pressureScore,
                'demand_intensity' => $capacity['demand_intensity'] ?? null,
                'capacity_demand_gap' => $capacityDemandGap,
                'capacity_demand_balance' => $capacity['capacity_demand_balance'] ?? null,
                'strategic_load_status' => $capacity['strategic_load_status'] ?? null,
                'workload_expansion_readiness' => $capacity['workload_expansion_readiness'] ?? null,
            ],

            'constraint_context' => [
                'constraint_level' => $constraintState['constraint_level'] ?? null,
                'constraint_score' => $constraintScore,
                'detected_constraints' => $constraintState['detected_constraints'] ?? null,
                'dominant_constraint' => $constraintState['dominant_constraint'] ?? null,
                'strategic_flexibility' => $constraintState['strategic_flexibility'] ?? null,
                'constraint_relief_readiness' => $constraintState['constraint_relief_readiness'] ?? null,
                'critical_constraints' => $criticalConstraints,
                'high_constraints' => $highConstraints,
            ],

            'risk_context' => [
                'strategic_risk_level' => $riskState['strategic_risk_level'] ?? null,
                'strategic_risk_score' => $strategicRiskScore,
                'risk_control_status' => $riskState['risk_control_status'] ?? null,
                'critical_signals' => $criticalRiskSignals,
                'high_signals' => $highRiskSignals,
                'moderate_signals' => (int) ($riskSummary['moderate_signals'] ?? 0),
                'advisory_signals' => (int) ($riskSummary['advisory_signals'] ?? 0),
            ],

            'recommendation_context' => [
                'recommendation_status' => $recommendationState['recommendation_status'] ?? null,
                'total_recommendations' => (int) ($recommendationSummary['total_recommendations'] ?? 0),
                'critical_recommendations' => $criticalRecommendations,
                'high_recommendations' => $highRecommendations,
                'moderate_recommendations' => (int) ($recommendationSummary['moderate_recommendations'] ?? 0),
                'advisory_recommendations' => (int) ($recommendationSummary['advisory_recommendations'] ?? 0),
                'top_recommendation' => $topRecommendation,
            ],

            'progress_context' => [
                'action_closure_percentage' => $actionClosurePercentage,
                'decision_completion_percentage' => $decisionCompletionPercentage,
            ],

            'score_components' => [
                'strategic_readiness' => $strategicReadinessScore,
                'strategic_balance' => $strategicBalanceScore,
                'governance_capacity' => $capacityScore,
                'action_closure' => $actionClosurePercentage,
                'decision_completion' => $decisionCompletionPercentage,
                'base_executive_score' => $baseExecutiveScore,
                'strategic_pressure_score' => $strategicPressureScore,
                'pressure_adjustment' => $pressureAdjustment,
                'executive_strategic_score' => $executiveStrategicScore,
            ],

            'highest_risk_active_action' => $risk['highest_risk_active_action'] ?? null,

            'executive_attention_items' => $executiveAttentionItems,

            'executive_priorities' => $executivePriorities,

            'executive_findings' => [
                'Current executive strategic status is ' . $executiveStrategicStatus . '.',
                'Current executive strategic readiness is ' . $executiveReadiness . '.',
                "Current executive strategic score is {$executiveStrategicScore}.",
                'Current executive confidence is ' . $executiveConfidence . '.',
                'Strategic governance health is ' . ($state['strategic_health'] ?? 'UNKNOWN') . '.',
                "Strategic readiness score is {$strategicReadinessScore}.",
                "Strategic balance score is {$strategicBalanceScore}.",
                "Governance capacity score is {$capacityScore}.",
                "Governance demand pressure score is {$pressureScore}.",
                "Capacity-demand gap is {$capacityDemandGap}.",
                'Strategic constraint level is ' . ($constraintState['constraint_level'] ?? 'UNKNOWN') .
                    " with score {$constraintScore}.",
                'Strategic risk level is ' . ($riskState['strategic_risk_level'] ?? 'UNKNOWN') .
                    " with score {$strategicRiskScore}.",
                'Strategic recommendation status is ' .
                    ($recommendationState['recommendation_status'] ?? 'UNKNOWN') . '.',
                ($recommendationSummary['total_recommendations'] ?? 0) .
                    ' strategic governance management recommendation(s) are currently generated.',
                "Governance action closure is {$actionClosurePercentage}%.",
                "Governance decision completion is {$decisionCompletionPercentage}%.",
                'Governance integrity remains ' .
                    ($governanceIntegrityIntact ? 'INTACT' : 'NOT_INTACT') . '.',
                'Management escalation recommendation is ' .
                    ($managementEscalationRecommended ? 'ACTIVE' : 'NOT_ACTIVE') . '.',
                'Immediate escalation requirement is ' .
                    ($immediateEscalationRequired ? 'ACTIVE' : 'NOT_REQUIRED') . '.',
                'Executive strategic intelligence remains advisory and human governed.',
            ],

            'executive_guardrails' => [
                'executive_strategic_intelligence_enabled' => true,
                'executive_strategic_status_is_governance_decision' => false,
                'executive_readiness_is_governance_approval' => false,
                'executive_readiness_is_execution_authorization' => false,
                'executive_score_is_staff_performance_rating' => false,
                'executive_intelligence_changes_action_state' => false,
                'executive_intelligence_changes_priority' => false,
                'executive_intelligence_changes_eligibility' => false,
                'management_escalation_is_automatic_notification' => false,
                'strategic_risk_authorizes_automation' => false,
                'strategic_constraint_authorizes_automation' => false,
                'capacity_shortage_authorizes_automation' => false,
                'executive_intelligence_authorizes_ai_change' => false,
                'executive_intelligence_authorizes_execution' => false,
                'executive_intelligence_authorizes_deployment' => false,
                'executive_intelligence_authorizes_rollback' => false,
                'executive_intelligence_authorizes_clinical_action' => false,
                'executive_intelligence_overrides_human_review' => false,
                'executive_intelligence_overrides_evidence_requirements' => false,
                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,
                'human_review_required' => true,
                'governance_validation_required' => true,
                'message' => 'Executive strategic governance intelligence consolidates strategic readiness, capacity, demand pressure, constraints, risk, recommendations, workload progression, and executive management priorities for human strategic oversight only. Executive status, readiness, scores, escalation recommendations, and priorities do not make governance decisions, change action state, alter priority or eligibility, bypass evidence or human review, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
            ],
        ];
    }

    private function determineExecutiveStrategicStatus(
        float $riskScore,
        float $constraintScore,
        int $criticalRiskSignals,
        int $criticalConstraints
    ): string {
        if ($criticalRiskSignals > 0 || $criticalConstraints > 0) {
            return 'CRITICAL_STRATEGIC_GOVERNANCE_ATTENTION';
        }

        if ($riskScore >= 75 || $constraintScore >= 90) {
            return 'CONTROLLED_HIGH_STRATEGIC_PRESSURE';
        }

        if ($riskScore >= 50 || $constraintScore >= 60) {
            return 'CONTROLLED_ELEVATED_STRATEGIC_PRESSURE';
        }

        return 'CONTROLLED_STRATEGIC_GOVERNANCE';
    }

    private function determineExecutiveReadiness(
        float $executiveScore,
        float $strategicReadinessScore,
        float $capacityScore,
        float $pressureScore
    ): string {
        if (
            $executiveScore >= 75 &&
            $strategicReadinessScore >= 70 &&
            $capacityScore >= 65 &&
            $pressureScore < 50
        ) {
            return 'STRATEGICALLY_READY';
        }

        if (
            $executiveScore >= 55 &&
            $strategicReadinessScore >= 50 &&
            $capacityScore >= 50
        ) {
            return 'PARTIALLY_READY';
        }

        return 'LIMITED_READINESS';
    }

    private function determineExecutiveConfidence(
        ?string $strategicConfidence,
        float $riskScore,
        bool $governanceIntegrityIntact
    ): string {
        if (!$governanceIntegrityIntact) {
            return 'LOW';
        }

        if ($strategicConfidence === 'VERY_LIMITED') {
            return 'VERY_LIMITED';
        }

        if ($riskScore >= 75) {
            return 'LIMITED';
        }

        return $strategicConfidence ?: 'LIMITED';
    }
}