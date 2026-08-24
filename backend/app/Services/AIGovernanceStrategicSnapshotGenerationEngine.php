<?php

namespace App\Services;

use App\Models\AIGovernanceOperationalSnapshot;
use App\Models\AIGovernanceStrategicSnapshot;

use App\Services\AIGovernanceOperationalStateIntelligenceEngine;
use App\Services\AIGovernanceAttentionEscalationIntelligenceEngine;
use App\Services\AIGovernanceBottleneckIntelligenceEngine;
use App\Services\AIGovernanceOperationalThroughputEfficiencyIntelligenceEngine;
use App\Services\AIGovernanceOperationalRiskIntelligenceEngine;
use Illuminate\Support\Facades\DB;

class AIGovernanceStrategicSnapshotGenerationEngine
{
    /**
     * Step 62.2
     *
     * Capture a human-governed strategic governance snapshot from the
     * latest operational governance snapshot and the validated Step 61
     * operational intelligence layers.
     *
     * This engine is intelligence/snapshot-only.
     * It does not make governance decisions, resolve actions,
     * modify AI behaviour, execute changes, deploy, rollback,
     * or initiate clinical action.
     */
    public function capture(?int $operationalSnapshotId = null): array
    {
        $operationalSnapshot = $operationalSnapshotId
            ? AIGovernanceOperationalSnapshot::find($operationalSnapshotId)
            : AIGovernanceOperationalSnapshot::latest('id')->first();

        if (!$operationalSnapshot) {
            return [
                'captured' => false,
                'status' => 'OPERATIONAL_SNAPSHOT_NOT_AVAILABLE',
                'message' => 'A governance operational snapshot is required before a strategic snapshot can be captured.',
                'strategic_guardrails' => $this->guardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Step 61 derived operational intelligence
        |--------------------------------------------------------------------------
        |
        | AIGovernanceOperationalSnapshot intentionally stores the core
        | operational snapshot state only. Scores such as attention,
        | escalation, bottleneck, efficiency, and operational risk are
        | derived by their respective Step 61 intelligence engines.
        |
        | Step 62 must therefore read those authoritative engines rather
        | than assume the derived values exist as snapshot columns.
        |
        */

        $operationalState = app(
            AIGovernanceOperationalStateIntelligenceEngine::class
        )->analyze($operationalSnapshot->id);

        $escalation = app(
            AIGovernanceAttentionEscalationIntelligenceEngine::class
        )->analyze($operationalSnapshot->id);

        $bottleneck = app(
            AIGovernanceBottleneckIntelligenceEngine::class
        )->analyze($operationalSnapshot->id);

        $throughput = app(
            AIGovernanceOperationalThroughputEfficiencyIntelligenceEngine::class
        )->analyze($operationalSnapshot->id);

        $operationalRisk = app(
            AIGovernanceOperationalRiskIntelligenceEngine::class
        )->analyze($operationalSnapshot->id);

        /*
        |--------------------------------------------------------------------------
        | Authoritative operational values
        |--------------------------------------------------------------------------
        */

        $attentionLevel = (string) data_get(
            $operationalState,
            'operational_state.attention_level',
            'UNKNOWN'
        );

        $attentionScore = (float) data_get(
            $operationalState,
            'operational_state.attention_score',
            0
        );

        $operationalHealth = (string) data_get(
            $operationalState,
            'operational_state.operational_health',
            'UNKNOWN'
        );

        $operationalMaturity = (string) data_get(
            $operationalState,
            'operational_state.operational_maturity',
            'UNKNOWN'
        );

        $operationalMaturityScore = (float) data_get(
            $operationalState,
            'operational_state.operational_maturity_score',
            0
        );

        $escalationLevel = (string) data_get(
            $escalation,
            'escalation_state.escalation_level',
            'UNKNOWN'
        );

        $escalationScore = (float) data_get(
            $escalation,
            'escalation_state.escalation_score',
            0
        );

        $managementEscalationRecommended = (bool) data_get(
            $escalation,
            'escalation_state.management_escalation_recommended',
            false
        );

        $immediateEscalationRequired = (bool) data_get(
            $escalation,
            'escalation_state.immediate_escalation_required',
            false
        );

        $bottleneckLevel = (string) data_get(
            $bottleneck,
            'bottleneck_state.bottleneck_level',
            'UNKNOWN'
        );

        $bottleneckScore = (float) data_get(
            $bottleneck,
            'bottleneck_state.bottleneck_score',
            0
        );

        $dominantBottleneck = data_get(
            $bottleneck,
            'bottleneck_state.dominant_bottleneck'
        );

        $throughputStatus = (string) data_get(
            $throughput,
            'throughput_state.throughput_status',
            'UNKNOWN'
        );

        $efficiencyStatus = (string) data_get(
            $throughput,
            'throughput_state.efficiency_status',
            'UNKNOWN'
        );

        $efficiencyScore = (float) data_get(
            $throughput,
            'throughput_state.efficiency_score',
            0
        );

        $processBalanceStatus = (string) data_get(
            $throughput,
            'throughput_state.process_balance_status',
            'UNKNOWN'
        );

        $operationalRiskLevel = (string) data_get(
            $operationalRisk,
            'operational_risk_state.operational_risk_level',
            'UNKNOWN'
        );

        $operationalRiskScore = (float) data_get(
            $operationalRisk,
            'operational_risk_state.operational_risk_score',
            0
        );

        $governanceIntegrityIntact = (bool) data_get(
            $operationalRisk,
            'operational_risk_state.governance_integrity_intact',
            false
        );

        /*
        |--------------------------------------------------------------------------
        | Core workload state
        |--------------------------------------------------------------------------
        */

        $totalActions = (int) $operationalSnapshot->total_governance_actions;
        $activeActions = (int) $operationalSnapshot->active_governance_actions;
        $closedActions = (int) $operationalSnapshot->closed_governance_actions;

        $pendingHumanReviews = (int) $operationalSnapshot->pending_human_reviews;
        $pendingHumanDecisions = (int) $operationalSnapshot->pending_human_decisions;

        $evidenceWaitingActions = (int) $operationalSnapshot->evidence_waiting_actions;
        $deferredActions = (int) $operationalSnapshot->deferred_actions;

        $highPriorityActiveActions = (int) $operationalSnapshot->high_priority_active_actions;
        $criticalPriorityActiveActions = (int) $operationalSnapshot->critical_priority_active_actions;

        $actionClosurePercentage = (float) $operationalSnapshot->action_closure_percentage;
        $decisionCompletionPercentage = (float) $operationalSnapshot->decision_completion_percentage;

        /*
        |--------------------------------------------------------------------------
        | Strategic pressure score
        |--------------------------------------------------------------------------
        |
        | Strategic pressure represents current governance-operational pressure.
        | It is descriptive only and never expands AI authority.
        |
        */

        $governancePressureScore = $this->calculateGovernancePressureScore(
            attentionScore: $attentionScore,
            escalationScore: $escalationScore,
            bottleneckScore: $bottleneckScore,
            operationalRiskScore: $operationalRiskScore,
            highPriorityActiveActions: $highPriorityActiveActions,
            criticalPriorityActiveActions: $criticalPriorityActiveActions
        );

        $demandPressureStatus = $this->classifyDemandPressure(
            $governancePressureScore
        );

        /*
        |--------------------------------------------------------------------------
        | Governance capacity score
        |--------------------------------------------------------------------------
        |
        | Capacity represents how much governed operational capacity is
        | currently available after accounting for closure, completion,
        | efficiency, unresolved work, and evidence constraints.
        |
        */

        $governanceCapacityScore = $this->calculateGovernanceCapacityScore(
            actionClosurePercentage: $actionClosurePercentage,
            decisionCompletionPercentage: $decisionCompletionPercentage,
            efficiencyScore: $efficiencyScore,
            totalActions: $totalActions,
            activeActions: $activeActions,
            evidenceWaitingActions: $evidenceWaitingActions,
            deferredActions: $deferredActions
        );

        $capacityStatus = $this->classifyCapacity(
            $governanceCapacityScore
        );

        /*
        |--------------------------------------------------------------------------
        | Strategic readiness score
        |--------------------------------------------------------------------------
        */

        $strategicReadinessScore = $this->calculateStrategicReadinessScore(
            governanceCapacityScore: $governanceCapacityScore,
            governancePressureScore: $governancePressureScore,
            operationalMaturityScore: $operationalMaturityScore,
            efficiencyScore: $efficiencyScore,
            actionClosurePercentage: $actionClosurePercentage,
            decisionCompletionPercentage: $decisionCompletionPercentage,
            governanceIntegrityIntact: $governanceIntegrityIntact
        );

        $strategicReadiness = $this->classifyStrategicReadiness(
            $strategicReadinessScore
        );

        /*
        |--------------------------------------------------------------------------
        | Strategic constraints
        |--------------------------------------------------------------------------
        */

        $constraints = $this->buildConstraints(
            pendingHumanReviews: $pendingHumanReviews,
            pendingHumanDecisions: $pendingHumanDecisions,
            evidenceWaitingActions: $evidenceWaitingActions,
            deferredActions: $deferredActions,
            highPriorityActiveActions: $highPriorityActiveActions,
            criticalPriorityActiveActions: $criticalPriorityActiveActions,
            actionClosurePercentage: $actionClosurePercentage,
            bottleneckLevel: $bottleneckLevel,
            bottleneckScore: $bottleneckScore,
            managementEscalationRecommended: $managementEscalationRecommended,
            immediateEscalationRequired: $immediateEscalationRequired
        );

        $constraintStatus = empty($constraints)
            ? 'NO_MATERIAL_CONSTRAINTS'
            : 'MATERIAL_CONSTRAINTS_PRESENT';

        $strategicStatus = $this->classifyStrategicStatus(
            strategicReadinessScore: $strategicReadinessScore,
            governancePressureScore: $governancePressureScore,
            criticalPriorityActiveActions: $criticalPriorityActiveActions,
            immediateEscalationRequired: $immediateEscalationRequired,
            governanceIntegrityIntact: $governanceIntegrityIntact
        );

        /*
        |--------------------------------------------------------------------------
        | Persist strategic snapshot
        |--------------------------------------------------------------------------
        */

        $strategicSnapshot = DB::transaction(function () use (
            $operationalSnapshot,
            $strategicStatus,
            $strategicReadiness,
            $capacityStatus,
            $demandPressureStatus,
            $constraintStatus,
            $totalActions,
            $activeActions,
            $closedActions,
            $pendingHumanReviews,
            $evidenceWaitingActions,
            $deferredActions,
            $highPriorityActiveActions,
            $criticalPriorityActiveActions,
            $actionClosurePercentage,
            $decisionCompletionPercentage,
            $strategicReadinessScore,
            $governanceCapacityScore,
            $governancePressureScore,
            $operationalHealth,
            $attentionLevel,
            $attentionScore,
            $operationalMaturity,
            $operationalMaturityScore,
            $escalationLevel,
            $escalationScore,
            $managementEscalationRecommended,
            $immediateEscalationRequired,
            $bottleneckLevel,
            $bottleneckScore,
            $dominantBottleneck,
            $throughputStatus,
            $efficiencyStatus,
            $efficiencyScore,
            $processBalanceStatus,
            $operationalRiskLevel,
            $operationalRiskScore,
            $governanceIntegrityIntact,
            $pendingHumanDecisions,
            $constraints
        ) {
            return AIGovernanceStrategicSnapshot::create([
                'operational_snapshot_id' => $operationalSnapshot->id,
                'lifecycle_snapshot_id' => $operationalSnapshot->lifecycle_snapshot_id,

                'snapshot_scope' => $operationalSnapshot->snapshot_scope,
                'resident_id' => $operationalSnapshot->resident_id,

                'snapshot_status' => 'CAPTURED',

                'strategic_status' => $strategicStatus,
                'strategic_readiness' => $strategicReadiness,
                'capacity_status' => $capacityStatus,
                'demand_pressure_status' => $demandPressureStatus,
                'constraint_status' => $constraintStatus,

                'total_governance_actions' => $totalActions,
                'active_governance_actions' => $activeActions,
                'closed_governance_actions' => $closedActions,

                'pending_human_reviews' => $pendingHumanReviews,
                'evidence_waiting_actions' => $evidenceWaitingActions,
                'deferred_actions' => $deferredActions,

                'high_priority_active_actions' => $highPriorityActiveActions,
                'critical_priority_active_actions' => $criticalPriorityActiveActions,

                'action_closure_percentage' => round($actionClosurePercentage, 2),
                'decision_completion_percentage' => round($decisionCompletionPercentage, 2),

                'strategic_readiness_score' => round($strategicReadinessScore, 2),
                'governance_capacity_score' => round($governanceCapacityScore, 2),
                'governance_pressure_score' => round($governancePressureScore, 2),

                'strategic_context' => [
                    'snapshot_version' => '62.2',

                    'source_operational_snapshot' => [
                        'operational_snapshot_id' => $operationalSnapshot->id,
                        'lifecycle_snapshot_id' => $operationalSnapshot->lifecycle_snapshot_id,
                        'operational_status' => $operationalSnapshot->operational_status,
                        'governance_workload_status' => $operationalSnapshot->governance_workload_status,
                        'decision_status' => $operationalSnapshot->decision_status,
                    ],

                    'step_61_operational_intelligence' => [
                        'operational_health' => $operationalHealth,
                        'operational_maturity' => $operationalMaturity,
                        'operational_maturity_score' => $operationalMaturityScore,

                        'attention_level' => $attentionLevel,
                        'attention_score' => $attentionScore,

                        'escalation_level' => $escalationLevel,
                        'escalation_score' => $escalationScore,
                        'management_escalation_recommended' => $managementEscalationRecommended,
                        'immediate_escalation_required' => $immediateEscalationRequired,

                        'bottleneck_level' => $bottleneckLevel,
                        'bottleneck_score' => $bottleneckScore,
                        'dominant_bottleneck' => $dominantBottleneck,

                        'throughput_status' => $throughputStatus,
                        'efficiency_status' => $efficiencyStatus,
                        'efficiency_score' => $efficiencyScore,
                        'process_balance_status' => $processBalanceStatus,

                        'operational_risk_level' => $operationalRiskLevel,
                        'operational_risk_score' => $operationalRiskScore,

                        'governance_integrity_intact' => $governanceIntegrityIntact,
                    ],

                    'strategic_calculation' => [
                        'strategic_readiness_score' => round($strategicReadinessScore, 2),
                        'governance_capacity_score' => round($governanceCapacityScore, 2),
                        'governance_pressure_score' => round($governancePressureScore, 2),
                    ],

                    'human_governance_work' => [
                        'pending_human_reviews' => $pendingHumanReviews,
                        'pending_human_decisions' => $pendingHumanDecisions,
                        'evidence_waiting_actions' => $evidenceWaitingActions,
                        'deferred_actions' => $deferredActions,
                        'high_priority_active_actions' => $highPriorityActiveActions,
                        'critical_priority_active_actions' => $criticalPriorityActiveActions,
                    ],

                    'strategic_constraints' => $constraints,

                    'automatic_execution_authorized' => false,
                    'automatic_change_authorized' => false,
                    'automatic_deployment_authorized' => false,
                    'automatic_rollback_authorized' => false,
                    'automatic_clinical_action_authorized' => false,

                    'captured_at' => now()->toIso8601String(),
                ],

                'source_context' => [
                    'operational_snapshot_id' => $operationalSnapshot->id,
                    'lifecycle_snapshot_id' => $operationalSnapshot->lifecycle_snapshot_id,

                    'operational_health' => $operationalHealth,
                    'attention_level' => $attentionLevel,
                    'attention_score' => $attentionScore,

                    'escalation_level' => $escalationLevel,
                    'escalation_score' => $escalationScore,

                    'bottleneck_level' => $bottleneckLevel,
                    'bottleneck_score' => $bottleneckScore,

                    'efficiency_status' => $efficiencyStatus,
                    'efficiency_score' => $efficiencyScore,

                    'operational_risk_level' => $operationalRiskLevel,
                    'operational_risk_score' => $operationalRiskScore,

                    'decision_consistency_status' => $operationalSnapshot->decision_consistency_status,
                    'decision_consistency_score' => (float) $operationalSnapshot->decision_consistency_score,

                    'governance_integrity_intact' => $governanceIntegrityIntact,
                ],

                'automatic_execution_allowed' => false,
                'automatic_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'automatic_rollback_allowed' => false,
                'automatic_clinical_action_allowed' => false,

                'human_review_required' => true,
                'governance_validation_required' => true,

                'captured_at' => now(),
            ]);
        });

        return [
            'captured' => true,
            'status' => 'GOVERNANCE_STRATEGIC_SNAPSHOT_CAPTURED',
            'message' => 'AI governance strategic snapshot captured successfully.',

            'snapshot' => [
                'strategic_snapshot_id' => $strategicSnapshot->id,
                'operational_snapshot_id' => $strategicSnapshot->operational_snapshot_id,
                'lifecycle_snapshot_id' => $strategicSnapshot->lifecycle_snapshot_id,

                'snapshot_scope' => $strategicSnapshot->snapshot_scope,
                'resident_id' => $strategicSnapshot->resident_id,

                'snapshot_status' => $strategicSnapshot->snapshot_status,

                'strategic_status' => $strategicSnapshot->strategic_status,
                'strategic_readiness' => $strategicSnapshot->strategic_readiness,
                'capacity_status' => $strategicSnapshot->capacity_status,
                'demand_pressure_status' => $strategicSnapshot->demand_pressure_status,
                'constraint_status' => $strategicSnapshot->constraint_status,

                'total_governance_actions' => $strategicSnapshot->total_governance_actions,
                'active_governance_actions' => $strategicSnapshot->active_governance_actions,
                'closed_governance_actions' => $strategicSnapshot->closed_governance_actions,

                'pending_human_reviews' => $strategicSnapshot->pending_human_reviews,
                'evidence_waiting_actions' => $strategicSnapshot->evidence_waiting_actions,
                'deferred_actions' => $strategicSnapshot->deferred_actions,

                'high_priority_active_actions' => $strategicSnapshot->high_priority_active_actions,
                'critical_priority_active_actions' => $strategicSnapshot->critical_priority_active_actions,

                'action_closure_percentage' => (float) $strategicSnapshot->action_closure_percentage,
                'decision_completion_percentage' => (float) $strategicSnapshot->decision_completion_percentage,

                'strategic_readiness_score' => (float) $strategicSnapshot->strategic_readiness_score,
                'governance_capacity_score' => (float) $strategicSnapshot->governance_capacity_score,
                'governance_pressure_score' => (float) $strategicSnapshot->governance_pressure_score,

                'captured_at' => $strategicSnapshot->captured_at,
            ],

            'source_operational_intelligence' => [
                'operational_health' => $operationalHealth,
                'attention_level' => $attentionLevel,
                'attention_score' => $attentionScore,

                'escalation_level' => $escalationLevel,
                'escalation_score' => $escalationScore,

                'bottleneck_level' => $bottleneckLevel,
                'bottleneck_score' => $bottleneckScore,
                'dominant_bottleneck' => $dominantBottleneck,

                'throughput_status' => $throughputStatus,
                'efficiency_status' => $efficiencyStatus,
                'efficiency_score' => $efficiencyScore,
                'process_balance_status' => $processBalanceStatus,

                'operational_risk_level' => $operationalRiskLevel,
                'operational_risk_score' => $operationalRiskScore,

                'governance_integrity_intact' => $governanceIntegrityIntact,
            ],

            'strategic_guardrails' => $this->guardrails(),
        ];
    }

    private function calculateGovernancePressureScore(
        float $attentionScore,
        float $escalationScore,
        float $bottleneckScore,
        float $operationalRiskScore,
        int $highPriorityActiveActions,
        int $criticalPriorityActiveActions
    ): float {
        $basePressure =
            ($attentionScore * 0.25)
            + ($escalationScore * 0.25)
            + ($bottleneckScore * 0.25)
            + ($operationalRiskScore * 0.25);

        $priorityAdjustment = min(
            20,
            ($highPriorityActiveActions * 5)
            + ($criticalPriorityActiveActions * 10)
        );

        return round(
            min(100, $basePressure + $priorityAdjustment),
            2
        );
    }

    private function calculateGovernanceCapacityScore(
        float $actionClosurePercentage,
        float $decisionCompletionPercentage,
        float $efficiencyScore,
        int $totalActions,
        int $activeActions,
        int $evidenceWaitingActions,
        int $deferredActions
    ): float {
        if ($totalActions <= 0) {
            return 100.0;
        }

        $activePercentage = ($activeActions / $totalActions) * 100;
        $evidenceWaitingPercentage = ($evidenceWaitingActions / $totalActions) * 100;
        $deferredPercentage = ($deferredActions / $totalActions) * 100;

        $progressCapacity =
            ($actionClosurePercentage * 0.35)
            + ($decisionCompletionPercentage * 0.25)
            + ($efficiencyScore * 0.40);

        $constraintPenalty =
            ($activePercentage * 0.10)
            + ($evidenceWaitingPercentage * 0.20)
            + ($deferredPercentage * 0.10);

        return round(
            max(0, min(100, $progressCapacity - $constraintPenalty)),
            2
        );
    }

    private function calculateStrategicReadinessScore(
        float $governanceCapacityScore,
        float $governancePressureScore,
        float $operationalMaturityScore,
        float $efficiencyScore,
        float $actionClosurePercentage,
        float $decisionCompletionPercentage,
        bool $governanceIntegrityIntact
    ): float {
        $baseReadiness =
            ($governanceCapacityScore * 0.25)
            + ($operationalMaturityScore * 0.20)
            + ($efficiencyScore * 0.20)
            + ($actionClosurePercentage * 0.15)
            + ($decisionCompletionPercentage * 0.20);

        $pressurePenalty = $governancePressureScore * 0.20;

        $integrityPenalty = $governanceIntegrityIntact ? 0 : 30;

        return round(
            max(
                0,
                min(
                    100,
                    $baseReadiness - $pressurePenalty - $integrityPenalty
                )
            ),
            2
        );
    }

    private function classifyDemandPressure(float $score): string
    {
        return match (true) {
            $score >= 80 => 'CRITICAL_GOVERNANCE_PRESSURE',
            $score >= 65 => 'HIGH_GOVERNANCE_PRESSURE',
            $score >= 45 => 'MODERATE_GOVERNANCE_PRESSURE',
            $score >= 25 => 'LOW_GOVERNANCE_PRESSURE',
            default => 'MINIMAL_GOVERNANCE_PRESSURE',
        };
    }

    private function classifyCapacity(float $score): string
    {
        return match (true) {
            $score >= 80 => 'STRONG_CAPACITY',
            $score >= 65 => 'AVAILABLE_CAPACITY',
            $score >= 45 => 'DEVELOPING_CAPACITY',
            $score >= 25 => 'CONSTRAINED_CAPACITY',
            default => 'SEVERELY_CONSTRAINED_CAPACITY',
        };
    }

    private function classifyStrategicReadiness(float $score): string
    {
        return match (true) {
            $score >= 85 => 'STRATEGICALLY_READY',
            $score >= 70 => 'HIGH_READINESS',
            $score >= 55 => 'MODERATE_READINESS',
            $score >= 35 => 'LIMITED_READINESS',
            default => 'LOW_READINESS',
        };
    }

    private function classifyStrategicStatus(
        float $strategicReadinessScore,
        float $governancePressureScore,
        int $criticalPriorityActiveActions,
        bool $immediateEscalationRequired,
        bool $governanceIntegrityIntact
    ): string {
        if (!$governanceIntegrityIntact) {
            return 'STRATEGIC_GOVERNANCE_INTEGRITY_CONCERN';
        }

        if ($immediateEscalationRequired || $criticalPriorityActiveActions > 0) {
            return 'CRITICAL_STRATEGIC_ATTENTION_REQUIRED';
        }

        if ($governancePressureScore >= 65) {
            return 'ELEVATED_STRATEGIC_PRESSURE';
        }

        if ($governancePressureScore >= 45) {
            return 'CONTROLLED_STRATEGIC_PRESSURE';
        }

        if ($strategicReadinessScore >= 70) {
            return 'STRATEGICALLY_STABLE';
        }

        return 'CONTROLLED_STRATEGIC_DEVELOPMENT';
    }

    private function buildConstraints(
        int $pendingHumanReviews,
        int $pendingHumanDecisions,
        int $evidenceWaitingActions,
        int $deferredActions,
        int $highPriorityActiveActions,
        int $criticalPriorityActiveActions,
        float $actionClosurePercentage,
        string $bottleneckLevel,
        float $bottleneckScore,
        bool $managementEscalationRecommended,
        bool $immediateEscalationRequired
    ): array {
        $constraints = [];

        if ($criticalPriorityActiveActions > 0) {
            $constraints[] = [
                'constraint_code' => 'CRITICAL_PRIORITY_GOVERNANCE_WORK',
                'severity' => 'CRITICAL',
                'value' => $criticalPriorityActiveActions,
                'message' => 'Critical-priority governance work remains active.',
            ];
        }

        if ($highPriorityActiveActions > 0) {
            $constraints[] = [
                'constraint_code' => 'HIGH_PRIORITY_GOVERNANCE_WORK',
                'severity' => 'HIGH',
                'value' => $highPriorityActiveActions,
                'message' => 'High-priority governance work remains active.',
            ];
        }

        if ($evidenceWaitingActions > 0) {
            $constraints[] = [
                'constraint_code' => 'EVIDENCE_DEPENDENCY',
                'severity' => 'HIGH',
                'value' => $evidenceWaitingActions,
                'message' => 'Governance work remains constrained by additional evidence requirements.',
            ];
        }

        if ($pendingHumanReviews > 0) {
            $constraints[] = [
                'constraint_code' => 'PENDING_HUMAN_REVIEWS',
                'severity' => 'MODERATE',
                'value' => $pendingHumanReviews,
                'message' => 'Governance work remains pending explicit human review.',
            ];
        }

        if ($pendingHumanDecisions > 0) {
            $constraints[] = [
                'constraint_code' => 'PENDING_HUMAN_DECISIONS',
                'severity' => 'MODERATE',
                'value' => $pendingHumanDecisions,
                'message' => 'Governance work remains pending explicit human decision.',
            ];
        }

        if ($deferredActions > 0) {
            $constraints[] = [
                'constraint_code' => 'DEFERRED_GOVERNANCE_WORK',
                'severity' => 'ADVISORY',
                'value' => $deferredActions,
                'message' => 'Deferred governance work remains under controlled observation.',
            ];
        }

        if ($actionClosurePercentage < 50) {
            $constraints[] = [
                'constraint_code' => 'LOW_ACTION_CLOSURE',
                'severity' => 'MODERATE',
                'value' => round($actionClosurePercentage, 2),
                'threshold' => 50.0,
                'message' => 'Governance action closure remains below the preferred strategic progression threshold.',
            ];
        }

        if ($bottleneckScore >= 50) {
            $constraints[] = [
                'constraint_code' => 'OPERATIONAL_BOTTLENECK',
                'severity' => $bottleneckScore >= 70 ? 'HIGH' : 'MODERATE',
                'value' => [
                    'bottleneck_level' => $bottleneckLevel,
                    'bottleneck_score' => $bottleneckScore,
                ],
                'message' => 'Operational governance bottleneck pressure constrains strategic readiness.',
            ];
        }

        if ($managementEscalationRecommended) {
            $constraints[] = [
                'constraint_code' => 'MANAGEMENT_ESCALATION_RECOMMENDED',
                'severity' => 'MODERATE',
                'value' => true,
                'message' => 'Current operational governance conditions warrant elevated management visibility.',
            ];
        }

        if ($immediateEscalationRequired) {
            $constraints[] = [
                'constraint_code' => 'IMMEDIATE_ESCALATION_REQUIRED',
                'severity' => 'CRITICAL',
                'value' => true,
                'message' => 'Current operational governance conditions require immediate human escalation.',
            ];
        }

        return $constraints;
    }

    private function guardrails(): array
    {
        return [
            'strategic_snapshotting_enabled' => true,

            'snapshot_is_governance_decision' => false,
            'snapshot_is_governance_approval' => false,
            'snapshot_is_action_resolution' => false,
            'snapshot_is_execution_authorization' => false,

            'snapshot_changes_priority' => false,
            'snapshot_changes_eligibility' => false,

            'strategic_readiness_expands_ai_authority' => false,

            'automatic_execution_allowed' => false,
            'automatic_change_allowed' => false,
            'automatic_deployment_allowed' => false,
            'automatic_rollback_allowed' => false,
            'automatic_clinical_action_allowed' => false,

            'human_review_required' => true,
            'governance_validation_required' => true,

            'message' => 'Governance strategic snapshot generation consolidates operational governance capacity, pressure, readiness, constraints, workload, risk, and progression for human strategic planning only. Strategic snapshot generation does not make governance decisions, resolve actions, alter priority or eligibility, authorize AI modification, execute changes, deploy updates, trigger rollback, or initiate clinical action.',
        ];
    }
}