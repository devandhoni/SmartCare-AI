<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlanControlledActivationExecution extends Model
{
    protected $table = 'ai_governance_strategic_plan_activation_executions';

    protected $fillable = [
        'strategic_plan_controlled_activation_execution_authorization_id',
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

        'controlled_activation_execution_code',
        'controlled_activation_execution_status',
        'controlled_activation_execution_mode',

        'source_execution_authorization_decision',
        'source_execution_authorization_decision_recorded',
        'source_execution_authorization_made_by_authorized_human',
        'source_execution_authorization_attribution_complete',
        'source_execution_authorization_completed',
        'source_execution_authorization_status',
        'source_execution_authorization_outcome',
        'source_execution_authorization_outcome_status',

        'execution_decision',
        'execution_rationale',
        'execution_notes',
        'execution_outcome',
        'execution_outcome_status',

        'execution_readiness',
        'execution_readiness_score',
        'execution_risk_level',
        'execution_risk_score',

        'condition_resolution_score',
        'evidence_resolution_score',
        'combined_resolution_score',
        'condition_pressure_score',
        'restriction_pressure_score',
        'combined_condition_restriction_pressure_score',

        'execution_conditions',
        'validated_evidence',
        'execution_findings',
        'execution_restrictions',
        'review_context',
        'execution_authorization_context',
        'governance_context',
        'source_context',

        'executed_by',
        'executor_role',
        'executed_at',
        'execution_made_by_authorized_human',
        'execution_attribution_complete',
        'controlled_activation_executed',

        'post_execution_status',
        'post_execution_validation_status',
        'post_execution_validation_completed',

        'automatic_execution_allowed',
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
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_confirmation_required',
        'controlled_activation_authorization_required',
        'authorized_human_execution_authorization_required',
        'authorized_human_execution_required',
        'post_execution_human_validation_required',
    ];

    protected $casts = [
        'execution_conditions' => 'array',
        'validated_evidence' => 'array',
        'execution_findings' => 'array',
        'execution_restrictions' => 'array',
        'review_context' => 'array',
        'execution_authorization_context' => 'array',
        'governance_context' => 'array',
        'source_context' => 'array',

        'execution_readiness_score' => 'float',
        'execution_risk_score' => 'float',
        'condition_resolution_score' => 'float',
        'evidence_resolution_score' => 'float',
        'combined_resolution_score' => 'float',
        'condition_pressure_score' => 'float',
        'restriction_pressure_score' => 'float',
        'combined_condition_restriction_pressure_score' => 'float',

        'source_execution_authorization_decision_recorded' => 'boolean',
        'source_execution_authorization_made_by_authorized_human' => 'boolean',
        'source_execution_authorization_attribution_complete' => 'boolean',
        'source_execution_authorization_completed' => 'boolean',

        'execution_made_by_authorized_human' => 'boolean',
        'execution_attribution_complete' => 'boolean',
        'controlled_activation_executed' => 'boolean',
        'post_execution_validation_completed' => 'boolean',

        'automatic_execution_allowed' => 'boolean',
        'automatic_execution_authorization_allowed' => 'boolean',
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
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_confirmation_required' => 'boolean',
        'controlled_activation_authorization_required' => 'boolean',
        'authorized_human_execution_authorization_required' => 'boolean',
        'authorized_human_execution_required' => 'boolean',
        'post_execution_human_validation_required' => 'boolean',

        'executed_at' => 'datetime',
    ];
}