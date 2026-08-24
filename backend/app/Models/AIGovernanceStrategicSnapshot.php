<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicSnapshot extends Model
{
    use HasFactory;

    protected $table = 'ai_governance_strategic_snapshots';

    protected $fillable = [
        'operational_snapshot_id',
        'lifecycle_snapshot_id',
        'snapshot_scope',
        'resident_id',
        'snapshot_status',
        'strategic_status',
        'strategic_readiness',
        'capacity_status',
        'demand_pressure_status',
        'constraint_status',
        'total_governance_actions',
        'active_governance_actions',
        'closed_governance_actions',
        'pending_human_reviews',
        'evidence_waiting_actions',
        'deferred_actions',
        'high_priority_active_actions',
        'critical_priority_active_actions',
        'action_closure_percentage',
        'decision_completion_percentage',
        'operational_health',
        'attention_level',
        'attention_score',
        'escalation_level',
        'escalation_score',
        'bottleneck_level',
        'bottleneck_score',
        'efficiency_status',
        'efficiency_score',
        'operational_risk_level',
        'operational_risk_score',
        'strategic_readiness_score',
        'governance_capacity_score',
        'governance_pressure_score',
        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',
        'human_review_required',
        'governance_validation_required',
        'strategic_context',
        'captured_at',
    ];

    protected $casts = [
        'resident_id' => 'integer',

        'total_governance_actions' => 'integer',
        'active_governance_actions' => 'integer',
        'closed_governance_actions' => 'integer',
        'pending_human_reviews' => 'integer',
        'evidence_waiting_actions' => 'integer',
        'deferred_actions' => 'integer',
        'high_priority_active_actions' => 'integer',
        'critical_priority_active_actions' => 'integer',

        'action_closure_percentage' => 'float',
        'decision_completion_percentage' => 'float',
        'attention_score' => 'float',
        'escalation_score' => 'float',
        'bottleneck_score' => 'float',
        'efficiency_score' => 'float',
        'operational_risk_score' => 'float',
        'strategic_readiness_score' => 'float',
        'governance_capacity_score' => 'float',
        'governance_pressure_score' => 'float',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'strategic_context' => 'array',

        'captured_at' => 'datetime',
    ];
}