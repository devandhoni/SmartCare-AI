<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGovernanceStrategicPlanDecision extends Model
{
    use HasFactory;

    protected $table = 'ai_governance_strategic_plan_decisions';

    protected $guarded = [];

    protected $fillable = [
        'strategic_plan_id',
        'strategic_snapshot_id',
        'operational_snapshot_id',
        'lifecycle_snapshot_id',
        'decision_scope',
        'resident_id',

        'decision_code',
        'decision_status',
        'decision_mode',
        'decision',

        'decision_priority',
        'decision_priority_score',
        'decision_rationale',

        'planning_readiness',
        'planning_readiness_score',

        'plan_risk_level',
        'plan_risk_score',

        'dependency_feasibility_status',
        'dependency_adjusted_feasibility_score',

        'blocking_dependency_count',
        'constraining_dependency_count',

        'decision_conditions',
        'required_evidence',

        'review_context',
        'decision_context',
        'source_context',

        'reviewed_by',
        'reviewer_role',
        'reviewed_at',

        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_validation_required',

        'decided_at',
    ];

    protected $casts = [
        'decision_priority_score' => 'float',
        'planning_readiness_score' => 'float',
        'plan_risk_score' => 'float',
        'dependency_adjusted_feasibility_score' => 'float',

        'blocking_dependency_count' => 'integer',
        'constraining_dependency_count' => 'integer',

        'decision_conditions' => 'array',
        'required_evidence' => 'array',
        'review_context' => 'array',
        'decision_context' => 'array',
        'source_context' => 'array',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'reviewed_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceStrategicPlan::class,
            'strategic_plan_id'
        );
    }

    public function strategicSnapshot(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceStrategicSnapshot::class,
            'strategic_snapshot_id'
        );
    }

    public function operationalSnapshot(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceOperationalSnapshot::class,
            'operational_snapshot_id'
        );
    }
}