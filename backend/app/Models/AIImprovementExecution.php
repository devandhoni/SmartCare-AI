<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementExecution extends Model
{
    use HasFactory;

    protected $table =
        'a_i_improvement_executions';

    protected $fillable = [
        'implementation_review_id',
        'improvement_review_id',
        'improvement_test_id',

        'candidate_code',
        'candidate_category',

        'scope_type',
        'resident_id',

        'execution_status',
        'execution_stage',

        'change_type',
        'change_summary',

        'baseline_configuration',
        'proposed_configuration',
        'execution_scope',

        'impact_analysis',
        'safety_validation',
        'safety_status',

        'execution_review_ready',
        'approved_for_execution',

        'authorized_by',
        'authorized_at',
        'authorization_notes',

        'production_execution_allowed',
        'automatic_execution_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',

        'human_execution_required',
        'pre_execution_validation_required',
        'post_execution_validation_required',
        'rollback_plan_required',
        'governance_validation_required',

        'execution_payload',
        'execution_results',
        'verification_results',

        'rollback_plan',
        'rollback_results',

        'registered_at',
        'execution_started_at',
        'execution_completed_at',
        'verified_at',
        'rolled_back_at',
    ];

    protected $casts = [
        'baseline_configuration' =>
            'array',

        'proposed_configuration' =>
            'array',

        'execution_scope' =>
            'array',

        'impact_analysis' =>
            'array',

        'safety_validation' =>
            'array',

        'execution_review_ready' =>
            'boolean',

        'approved_for_execution' =>
            'boolean',

        'production_execution_allowed' =>
            'boolean',

        'automatic_execution_allowed' =>
            'boolean',

        'automatic_deployment_allowed' =>
            'boolean',

        'automatic_rollback_allowed' =>
            'boolean',

        'human_execution_required' =>
            'boolean',

        'pre_execution_validation_required' =>
            'boolean',

        'post_execution_validation_required' =>
            'boolean',

        'rollback_plan_required' =>
            'boolean',

        'governance_validation_required' =>
            'boolean',

        'execution_payload' =>
            'array',

        'execution_results' =>
            'array',

        'verification_results' =>
            'array',

        'rollback_plan' =>
            'array',

        'rollback_results' =>
            'array',

        'authorized_at' =>
            'datetime',

        'registered_at' =>
            'datetime',

        'execution_started_at' =>
            'datetime',

        'execution_completed_at' =>
            'datetime',

        'verified_at' =>
            'datetime',

        'rolled_back_at' =>
            'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function implementationReview()
    {
        return $this->belongsTo(
            AIImprovementImplementationReview::class,
            'implementation_review_id'
        );
    }

    public function improvementReview()
    {
        return $this->belongsTo(
            AIImprovementReview::class,
            'improvement_review_id'
        );
    }

    public function improvementTest()
    {
        return $this->belongsTo(
            AIImprovementTest::class,
            'improvement_test_id'
        );
    }
}