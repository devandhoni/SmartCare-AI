<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanGovernanceDecisionConfirmation extends Model
{
    use HasFactory;

    protected $table =
        'ai_governance_strategic_plan_governance_decision_confirmations';

    protected $guarded = [];

    protected $casts = [
        /*
        |--------------------------------------------------------------------------
        | Structured Context
        |--------------------------------------------------------------------------
        */

        'confirmation_conditions' => 'array',
        'validated_evidence' => 'array',
        'confirmation_findings' => 'array',
        'confirmation_restrictions' => 'array',
        'review_context' => 'array',
        'validation_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        'confirmation_readiness_score' => 'float',
        'confirmation_risk_score' => 'float',

        'condition_resolution_score' => 'float',
        'evidence_resolution_score' => 'float',
        'combined_resolution_score' => 'float',

        'condition_pressure_score' => 'float',
        'restriction_pressure_score' => 'float',

        'combined_condition_restriction_pressure_score' => 'float',

        /*
        |--------------------------------------------------------------------------
        | Source Governance State
        |--------------------------------------------------------------------------
        */

        'source_final_governance_decision_recorded' => 'boolean',
        'source_decision_made_by_authorized_human' => 'boolean',
        'source_governance_validation_completed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Human Confirmation
        |--------------------------------------------------------------------------
        */

        'confirmation_made_by_authorized_human' => 'boolean',
        'governance_decision_confirmation_completed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Controlled Activation
        |--------------------------------------------------------------------------
        */

        'controlled_activation_authorized' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Controls
        |--------------------------------------------------------------------------
        */

        'automatic_confirmation_allowed' => 'boolean',
        'automatic_final_decision_allowed' => 'boolean',

        'automatic_approval_allowed' => 'boolean',
        'automatic_rejection_allowed' => 'boolean',
        'automatic_conditional_approval_allowed' => 'boolean',
        'automatic_deferral_allowed' => 'boolean',
        'automatic_risk_acceptance_allowed' => 'boolean',

        'automatic_activation_allowed' => 'boolean',

        'automatic_condition_resolution_allowed' => 'boolean',
        'automatic_restriction_removal_allowed' => 'boolean',
        'automatic_evidence_validation_allowed' => 'boolean',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Mandatory Human Governance
        |--------------------------------------------------------------------------
        */

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',
        'authorized_human_confirmation_required' => 'boolean',
        'authorized_human_activation_required' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Timestamps
        |--------------------------------------------------------------------------
        */

        'confirmed_at' => 'datetime',
        'activation_authorized_at' => 'datetime',
    ];
}