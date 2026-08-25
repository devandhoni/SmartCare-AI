<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanControlledActivationExecutionAuthorization extends Model
{
    use HasFactory;

    /**
     * Explicit shortened table name avoids Laravel generating an excessively
     * long MySQL table / index identifier.
     */
    protected $table =
        'ai_governance_strategic_plan_activation_execution_auths';

    protected $fillable = [
        /*
        |--------------------------------------------------------------------------
        | Source Governance Chain
        |--------------------------------------------------------------------------
        */

        'strategic_plan_controlled_activation_authorization_id',
        'strategic_plan_governance_decision_confirmation_id',
        'strategic_plan_final_governance_decision_id',
        'strategic_plan_decision_validation_id',
        'strategic_plan_human_decision_id',
        'strategic_plan_decision_id',
        'strategic_plan_id',
        'strategic_snapshot_id',
        'operational_snapshot_id',
        'lifecycle_snapshot_id',
        'decision_scope',
        'resident_id',

        /*
        |--------------------------------------------------------------------------
        | Execution Authorization Identity
        |--------------------------------------------------------------------------
        */

        'execution_authorization_code',
        'execution_authorization_status',
        'execution_authorization_mode',

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Authorization State
        |--------------------------------------------------------------------------
        */

        'source_activation_authorization_decision',
        'source_activation_authorization_decision_recorded',
        'source_activation_authorization_made_by_authorized_human',
        'source_activation_authorization_attribution_complete',
        'source_controlled_activation_authorization_completed',
        'source_activation_authorization_outcome',
        'source_activation_authorization_outcome_status',

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Execution State
        |--------------------------------------------------------------------------
        */

        'source_controlled_activation_execution_status',
        'source_controlled_activation_execution_authorized',
        'source_activation_execution_authorization_code',
        'source_execution_authorized_by',
        'source_execution_authorizer_role',
        'source_execution_authorized_at',
        'source_execution_authorization_attribution_complete',

        /*
        |--------------------------------------------------------------------------
        | Step 70 Execution Authorization
        |--------------------------------------------------------------------------
        */

        'controlled_activation_execution_authorization_decision',
        'execution_authorization_rationale',
        'execution_authorization_notes',
        'execution_authorization_outcome',
        'execution_authorization_outcome_status',

        /*
        |--------------------------------------------------------------------------
        | Readiness / Risk
        |--------------------------------------------------------------------------
        */

        'execution_readiness',
        'execution_readiness_score',
        'execution_risk_level',
        'execution_risk_score',

        /*
        |--------------------------------------------------------------------------
        | Resolution / Pressure
        |--------------------------------------------------------------------------
        */

        'condition_resolution_score',
        'evidence_resolution_score',
        'combined_resolution_score',
        'condition_pressure_score',
        'restriction_pressure_score',
        'combined_condition_restriction_pressure_score',

        /*
        |--------------------------------------------------------------------------
        | Intelligence Package
        |--------------------------------------------------------------------------
        */

        'execution_conditions',
        'validated_evidence',
        'execution_findings',
        'execution_restrictions',
        'review_context',
        'activation_authorization_context',
        'governance_context',
        'source_context',

        /*
        |--------------------------------------------------------------------------
        | Authorized Human Execution Authorization Attribution
        |--------------------------------------------------------------------------
        */

        'execution_authorized_by',
        'execution_authorizer_role',
        'execution_authorized_at',
        'execution_authorization_made_by_authorized_human',
        'controlled_activation_execution_authorization_completed',

        /*
        |--------------------------------------------------------------------------
        | Actual Execution Separation
        |--------------------------------------------------------------------------
        */

        'controlled_activation_execution_status',
        'controlled_activation_executed',
        'controlled_activation_execution_code',
        'executed_by',
        'executor_role',
        'executed_at',
        'execution_attribution_complete',

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority Guardrails
        |--------------------------------------------------------------------------
        */

        'automatic_execution_authorization_allowed',
        'automatic_activation_authorization_allowed',
        'automatic_confirmation_allowed',
        'automatic_final_decision_allowed',
        'automatic_approval_allowed',
        'automatic_rejection_allowed',
        'automatic_conditional_approval_allowed',
        'automatic_deferral_allowed',
        'automatic_risk_acceptance_allowed',
        'automatic_activation_allowed',
        'automatic_condition_resolution_allowed',
        'automatic_restriction_removal_allowed',
        'automatic_evidence_validation_allowed',
        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        /*
        |--------------------------------------------------------------------------
        | Mandatory Human Governance
        |--------------------------------------------------------------------------
        */

        'human_review_required',
        'governance_confirmation_required',
        'controlled_activation_authorization_required',
        'authorized_human_execution_authorization_required',
        'authorized_human_execution_required',
    ];

    protected $casts = [
        /*
        |--------------------------------------------------------------------------
        | JSON
        |--------------------------------------------------------------------------
        */

        'execution_conditions' =>
            'array',

        'validated_evidence' =>
            'array',

        'execution_findings' =>
            'array',

        'execution_restrictions' =>
            'array',

        'review_context' =>
            'array',

        'activation_authorization_context' =>
            'array',

        'governance_context' =>
            'array',

        'source_context' =>
            'array',

        /*
        |--------------------------------------------------------------------------
        | Scores
        |--------------------------------------------------------------------------
        */

        'execution_readiness_score' =>
            'float',

        'execution_risk_score' =>
            'float',

        'condition_resolution_score' =>
            'float',

        'evidence_resolution_score' =>
            'float',

        'combined_resolution_score' =>
            'float',

        'condition_pressure_score' =>
            'float',

        'restriction_pressure_score' =>
            'float',

        'combined_condition_restriction_pressure_score' =>
            'float',

        /*
        |--------------------------------------------------------------------------
        | Source Step 69 Flags
        |--------------------------------------------------------------------------
        */

        'source_activation_authorization_decision_recorded' =>
            'boolean',

        'source_activation_authorization_made_by_authorized_human' =>
            'boolean',

        'source_activation_authorization_attribution_complete' =>
            'boolean',

        'source_controlled_activation_authorization_completed' =>
            'boolean',

        'source_controlled_activation_execution_authorized' =>
            'boolean',

        'source_execution_authorization_attribution_complete' =>
            'boolean',

        /*
        |--------------------------------------------------------------------------
        | Step 70 Human Authorization
        |--------------------------------------------------------------------------
        */

        'execution_authorization_made_by_authorized_human' =>
            'boolean',

        'controlled_activation_execution_authorization_completed' =>
            'boolean',

        /*
        |--------------------------------------------------------------------------
        | Actual Execution
        |--------------------------------------------------------------------------
        */

        'controlled_activation_executed' =>
            'boolean',

        'execution_attribution_complete' =>
            'boolean',

        /*
        |--------------------------------------------------------------------------
        | Automatic Authority
        |--------------------------------------------------------------------------
        */

        'automatic_execution_authorization_allowed' =>
            'boolean',

        'automatic_activation_authorization_allowed' =>
            'boolean',

        'automatic_confirmation_allowed' =>
            'boolean',

        'automatic_final_decision_allowed' =>
            'boolean',

        'automatic_approval_allowed' =>
            'boolean',

        'automatic_rejection_allowed' =>
            'boolean',

        'automatic_conditional_approval_allowed' =>
            'boolean',

        'automatic_deferral_allowed' =>
            'boolean',

        'automatic_risk_acceptance_allowed' =>
            'boolean',

        'automatic_activation_allowed' =>
            'boolean',

        'automatic_condition_resolution_allowed' =>
            'boolean',

        'automatic_restriction_removal_allowed' =>
            'boolean',

        'automatic_evidence_validation_allowed' =>
            'boolean',

        'automatic_execution_allowed' =>
            'boolean',

        'automatic_change_allowed' =>
            'boolean',

        'automatic_deployment_allowed' =>
            'boolean',

        'automatic_rollback_allowed' =>
            'boolean',

        'automatic_clinical_action_allowed' =>
            'boolean',

        /*
        |--------------------------------------------------------------------------
        | Mandatory Governance
        |--------------------------------------------------------------------------
        */

        'human_review_required' =>
            'boolean',

        'governance_confirmation_required' =>
            'boolean',

        'controlled_activation_authorization_required' =>
            'boolean',

        'authorized_human_execution_authorization_required' =>
            'boolean',

        'authorized_human_execution_required' =>
            'boolean',

        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        'source_execution_authorized_at' =>
            'datetime',

        'execution_authorized_at' =>
            'datetime',

        'executed_at' =>
            'datetime',
    ];
}