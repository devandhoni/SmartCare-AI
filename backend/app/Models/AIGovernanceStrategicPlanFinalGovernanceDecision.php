<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanFinalGovernanceDecision extends Model
{
    use HasFactory;

    protected $table =
        'ai_governance_strategic_plan_final_governance_decisions';

    protected $guarded = [];

    protected $casts = [
        /*
        |--------------------------------------------------------------------------
        | Structured Governance Context
        |--------------------------------------------------------------------------
        */

        'decision_conditions' => 'array',
        'validated_evidence' => 'array',
        'decision_findings' => 'array',
        'decision_restrictions' => 'array',
        'review_context' => 'array',
        'validation_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        'final_decision_readiness_score' => 'float',
        'final_decision_risk_score' => 'float',
        'condition_resolution_score' => 'float',
        'evidence_resolution_score' => 'float',
        'combined_resolution_score' => 'float',

        /*
        |--------------------------------------------------------------------------
        | Source Decision / Validation State
        |--------------------------------------------------------------------------
        */

        'source_final_human_decision_recorded' => 'boolean',
        'source_decision_made_by_authorized_human' => 'boolean',
        'source_governance_validation_completed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Human Attribution
        |--------------------------------------------------------------------------
        */

        'decision_made_by_authorized_human' => 'boolean',
        'final_governance_confirmation_completed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Isolation
        |--------------------------------------------------------------------------
        */

        'automatic_final_decision_allowed' => 'boolean',
        'automatic_approval_allowed' => 'boolean',
        'automatic_rejection_allowed' => 'boolean',
        'automatic_conditional_approval_allowed' => 'boolean',
        'automatic_deferral_allowed' => 'boolean',
        'automatic_risk_acceptance_allowed' => 'boolean',
        'automatic_activation_allowed' => 'boolean',
        'automatic_condition_resolution_allowed' => 'boolean',
        'automatic_evidence_validation_allowed' => 'boolean',
        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Human Governance Requirements
        |--------------------------------------------------------------------------
        */

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',
        'authorized_human_final_decision_required' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        'decided_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function validation()
    {
        return $this->belongsTo(
            AIGovernanceStrategicPlanDecisionValidation::class,
            'strategic_plan_decision_validation_id'
        );
    }

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
}