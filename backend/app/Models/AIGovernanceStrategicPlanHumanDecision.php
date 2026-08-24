<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanHumanDecision extends Model
{
    use HasFactory;

    protected $table = 'ai_governance_strategic_plan_human_decisions';

    protected $guarded = [];

    protected $fillable = [
        'strategic_plan_decision_id',
        'strategic_plan_id',
        'strategic_snapshot_id',
        'operational_snapshot_id',
        'lifecycle_snapshot_id',

        'decision_scope',
        'resident_id',

        'human_decision_code',
        'human_decision_status',
        'human_decision_mode',

        'prepared_decision',
        'prepared_decision_status',

        'final_human_decision',
        'decision_rationale',
        'decision_notes',

        'decision_priority',
        'decision_priority_score',

        'decision_risk_level',
        'decision_risk_score',

        'approval_eligibility',
        'decision_eligibility_score',

        'decision_conditions',
        'condition_resolution_context',

        'evidence_context',
        'validated_evidence',

        'review_context',
        'governance_context',
        'source_context',

        'decided_by',
        'decider_role',
        'reviewed_at',
        'decided_at',

        'validated_by',
        'validator_role',
        'validated_at',

        'decision_made_by_authorized_human',
        'governance_validation_completed',

        'automatic_decision_allowed',
        'automatic_approval_allowed',
        'automatic_rejection_allowed',
        'automatic_activation_allowed',
        'automatic_condition_resolution_allowed',
        'automatic_evidence_validation_allowed',

        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_validation_required',
    ];

    protected $casts = [
        'decision_priority_score' => 'float',
        'decision_risk_score' => 'float',
        'decision_eligibility_score' => 'float',

        'decision_conditions' => 'array',
        'condition_resolution_context' => 'array',

        'evidence_context' => 'array',
        'validated_evidence' => 'array',

        'review_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        'reviewed_at' => 'datetime',
        'decided_at' => 'datetime',
        'validated_at' => 'datetime',

        'decision_made_by_authorized_human' => 'boolean',
        'governance_validation_completed' => 'boolean',

        'automatic_decision_allowed' => 'boolean',
        'automatic_approval_allowed' => 'boolean',
        'automatic_rejection_allowed' => 'boolean',
        'automatic_activation_allowed' => 'boolean',
        'automatic_condition_resolution_allowed' => 'boolean',
        'automatic_evidence_validation_allowed' => 'boolean',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',
    ];

    public function strategicPlanDecision()
    {
        return $this->belongsTo(
            AIGovernanceStrategicPlanDecision::class,
            'strategic_plan_decision_id'
        );
    }

    public function strategicPlan()
    {
        return $this->belongsTo(
            AIGovernanceStrategicPlan::class,
            'strategic_plan_id'
        );
    }
}