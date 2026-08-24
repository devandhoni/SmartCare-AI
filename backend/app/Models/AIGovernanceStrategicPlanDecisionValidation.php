<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanDecisionValidation extends Model
{
    use HasFactory;

    protected $table =
        'ai_governance_strategic_plan_decision_validations';

    protected $guarded = [];

    protected $fillable = [
        'strategic_plan_human_decision_id',
        'strategic_plan_decision_id',
        'strategic_plan_id',

        'strategic_snapshot_id',
        'operational_snapshot_id',
        'lifecycle_snapshot_id',

        'decision_scope',
        'resident_id',

        'validation_code',
        'validation_status',
        'validation_mode',

        'prepared_decision',
        'final_human_decision',

        'final_human_decision_recorded',
        'decision_made_by_authorized_human',

        'governance_validation_decision',
        'validation_rationale',
        'validation_notes',

        'governance_validation_readiness',
        'governance_validation_readiness_score',

        'decision_risk_level',
        'decision_risk_score',

        'condition_resolution_score',
        'evidence_resolution_score',
        'combined_resolution_score',

        'validation_conditions',
        'validated_evidence',
        'validation_findings',

        'review_context',
        'governance_context',
        'source_context',

        'validated_by',
        'validator_role',
        'validated_at',

        'validation_made_by_authorized_human',
        'governance_validation_completed',

        'automatic_validation_allowed',
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
        'validation_conditions' => 'array',
        'validated_evidence' => 'array',
        'validation_findings' => 'array',

        'review_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        'governance_validation_readiness_score' => 'float',
        'decision_risk_score' => 'float',
        'condition_resolution_score' => 'float',
        'evidence_resolution_score' => 'float',
        'combined_resolution_score' => 'float',

        'final_human_decision_recorded' => 'boolean',
        'decision_made_by_authorized_human' => 'boolean',

        'validation_made_by_authorized_human' => 'boolean',
        'governance_validation_completed' => 'boolean',

        'automatic_validation_allowed' => 'boolean',
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

        'validated_at' => 'datetime',
    ];

    public function humanDecision()
    {
        return $this->belongsTo(
            AIGovernanceStrategicPlanHumanDecision::class,
            'strategic_plan_human_decision_id'
        );
    }

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