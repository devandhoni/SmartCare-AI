<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanControlledActivationAuthorization extends Model
{
    use HasFactory;

    protected $table =
        'ai_governance_strategic_plan_activation_authorizations';

    protected $guarded = [];

    protected $casts = [
        /*
        |--------------------------------------------------------------------------
        | Structured Context
        |--------------------------------------------------------------------------
        */

        'activation_conditions' => 'array',
        'validated_evidence' => 'array',
        'activation_findings' => 'array',
        'activation_restrictions' => 'array',
        'review_context' => 'array',
        'confirmation_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        'activation_readiness_score' => 'float',
        'activation_risk_score' => 'float',
        'condition_resolution_score' => 'float',
        'evidence_resolution_score' => 'float',
        'combined_resolution_score' => 'float',
        'condition_pressure_score' => 'float',
        'restriction_pressure_score' => 'float',
        'combined_condition_restriction_pressure_score' => 'float',

        /*
        |--------------------------------------------------------------------------
        | Source Confirmation State
        |--------------------------------------------------------------------------
        */

        'source_confirmation_decision_recorded' => 'boolean',
        'source_confirmation_made_by_authorized_human' => 'boolean',
        'source_governance_decision_confirmation_completed' => 'boolean',
        'source_controlled_activation_authorized' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Authorization State
        |--------------------------------------------------------------------------
        */

        'activation_authorization_made_by_authorized_human' => 'boolean',
        'controlled_activation_authorization_completed' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization
        |--------------------------------------------------------------------------
        */

        'controlled_activation_execution_authorized' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Controls
        |--------------------------------------------------------------------------
        */

        'automatic_activation_authorization_allowed' => 'boolean',
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
        'governance_confirmation_required' => 'boolean',
        'authorized_human_activation_authorization_required' => 'boolean',
        'authorized_human_activation_execution_required' => 'boolean',

        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        'authorized_at' => 'datetime',
        'execution_authorized_at' => 'datetime',
    ];
}