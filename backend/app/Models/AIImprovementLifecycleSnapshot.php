<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementLifecycleSnapshot extends Model
{
    use HasFactory;

    protected $table = 'a_i_improvement_lifecycle_snapshots';

    protected $fillable = [
        'snapshot_scope',
        'resident_id',
        'snapshot_status',

        'total_learning_evidence',
        'total_improvement_candidates',
        'total_governance_reviews',
        'total_controlled_tests',
        'total_implementation_reviews',
        'total_controlled_executions',
        'total_monitoring_records',
        'active_monitoring_records',

        'learning_maturity',
        'governance_status',
        'execution_status',
        'monitoring_status',
        'overall_improvement_status',

        'learning_context',
        'governance_context',
        'execution_context',
        'monitoring_context',
        'lifecycle_summary',
        'snapshot_payload',

        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_validation_required',

        'captured_at',
    ];

    protected $casts = [
        'resident_id' => 'integer',

        'total_learning_evidence' => 'integer',
        'total_improvement_candidates' => 'integer',
        'total_governance_reviews' => 'integer',
        'total_controlled_tests' => 'integer',
        'total_implementation_reviews' => 'integer',
        'total_controlled_executions' => 'integer',
        'total_monitoring_records' => 'integer',
        'active_monitoring_records' => 'integer',

        'learning_context' => 'array',
        'governance_context' => 'array',
        'execution_context' => 'array',
        'monitoring_context' => 'array',
        'lifecycle_summary' => 'array',
        'snapshot_payload' => 'array',

        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'captured_at' => 'datetime',
    ];
}