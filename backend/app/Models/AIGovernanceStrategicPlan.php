<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIGovernanceStrategicPlan extends Model
{
    use HasFactory;

    protected $table = 'ai_governance_strategic_plans';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    |
    | We use $fillable as the explicit list of fields that may be assigned.
    | There is no need to keep protected $guarded = [] when $fillable is
    | already being used.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Step 62 Strategic Snapshot Relationship
    |--------------------------------------------------------------------------
    */

    public function strategicSnapshot(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceStrategicSnapshot::class,
            'strategic_snapshot_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Step 61 Operational Snapshot Relationship
    |--------------------------------------------------------------------------
    */

    public function operationalSnapshot(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceOperationalSnapshot::class,
            'operational_snapshot_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Governance Lifecycle Snapshot Relationship
    |--------------------------------------------------------------------------
    */

    public function lifecycleSnapshot(): BelongsTo
    {
        return $this->belongsTo(
            AIGovernanceLifecycleSnapshot::class,
            'lifecycle_snapshot_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Step 64 Strategic Plan Decisions
    |--------------------------------------------------------------------------
    |
    | A strategic plan may receive one or more human governance decisions.
    |
    | Examples later in Step 64 may include:
    |
    | - APPROVE
    | - APPROVE_WITH_CONDITIONS
    | - REQUEST_REVISION
    | - DEFER
    | - REJECT
    |
    | These decisions remain human-governed and must not automatically
    | activate, execute, deploy, rollback, or modify AI behaviour.
    |
    */

    public function decisions(): HasMany
    {
        return $this->hasMany(
            AIGovernanceStrategicPlanDecision::class,
            'strategic_plan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Strategic Plan Decision
    |--------------------------------------------------------------------------
    |
    | Convenience relationship for retrieving the most recent governance
    | decision associated with this strategic plan.
    |
    */

    public function latestDecision()
    {
        return $this->hasOne(
            AIGovernanceStrategicPlanDecision::class,
            'strategic_plan_id'
        )->latestOfMany();
    }
}