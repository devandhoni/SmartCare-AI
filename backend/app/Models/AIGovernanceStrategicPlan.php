<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceStrategicPlan extends Model
{
    use HasFactory;

    protected $table = 'ai_governance_strategic_plans';

    protected $guarded = [];

    protected $fillable = [
    'strategic_snapshot_id',
    'operational_snapshot_id',
    'lifecycle_snapshot_id',
    'plan_scope',
    'resident_id',
    'plan_code',
    'plan_status',
    'planning_mode',
    'strategic_priority',
    'strategic_priority_score',
    'planning_readiness',
    'planning_readiness_score',
    'risk_level',
    'risk_score',
    'constraint_level',
    'primary_recommendation_code',
    'primary_recommendation',
    'objectives',
    'dependencies',
    'resource_context',
    'planning_context',
    'source_context',
    'automatic_execution_allowed',
    'automatic_change_allowed',
    'automatic_deployment_allowed',
    'automatic_rollback_allowed',
    'automatic_clinical_action_allowed',
    'human_review_required',
    'governance_validation_required',
    'generated_at',
];

    protected $casts = [
        'objectives' => 'array',
        'dependencies' => 'array',
        'resource_context' => 'array',
        'planning_context' => 'array',
        'source_context' => 'array',

        'strategic_priority_score' => 'float',
        'planning_readiness_score' => 'float',
        'risk_score' => 'float',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',
        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'generated_at' => 'datetime',
    ];
}