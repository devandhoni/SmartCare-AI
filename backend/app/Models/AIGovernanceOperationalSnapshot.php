<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceOperationalSnapshot extends Model
{
    use HasFactory;

    protected $table = 'a_i_governance_operational_snapshots';

    protected $fillable = [
        'lifecycle_snapshot_id',
        'snapshot_scope',
        'resident_id',
        'snapshot_status',
        'operational_status',
        'governance_workload_status',
        'decision_status',

        'total_governance_actions',
        'active_governance_actions',
        'closed_governance_actions',

        'pending_human_reviews',
        'pending_human_decisions',
        'evidence_waiting_actions',
        'deferred_actions',

        'high_priority_active_actions',
        'critical_priority_active_actions',

        'action_closure_percentage',
        'decision_completion_percentage',

        'decision_consistency_status',
        'decision_consistency_score',

        'decision_risk_level',
        'decision_risk_score',

        'operational_context',
        'source_context',

        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_validation_required',

        'captured_at',
    ];

    protected $casts = [
        'resident_id' => 'integer',
        'lifecycle_snapshot_id' => 'integer',

        'total_governance_actions' => 'integer',
        'active_governance_actions' => 'integer',
        'closed_governance_actions' => 'integer',

        'pending_human_reviews' => 'integer',
        'pending_human_decisions' => 'integer',
        'evidence_waiting_actions' => 'integer',
        'deferred_actions' => 'integer',

        'high_priority_active_actions' => 'integer',
        'critical_priority_active_actions' => 'integer',

        'action_closure_percentage' => 'float',
        'decision_completion_percentage' => 'float',
        'decision_consistency_score' => 'float',
        'decision_risk_score' => 'integer',

        'operational_context' => 'array',
        'source_context' => 'array',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'captured_at' => 'datetime',
    ];
}