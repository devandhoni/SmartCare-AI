<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceAction extends Model
{
    use HasFactory;

    protected $table = 'a_i_governance_actions';

    protected $fillable = [
        'lifecycle_snapshot_id',

        'action_code',
        'action_category',
        'action_title',
        'action_description',

        'scope_type',
        'resident_id',

        'source_type',
        'source_id',
        'source_status',

        'priority_level',
        'priority_score',

        'action_status',
        'eligibility_status',

        'human_review_required',
        'governance_validation_required',

        'automatic_execution_allowed',
        'automatic_change_allowed',
        'automatic_deployment_allowed',
        'automatic_rollback_allowed',
        'automatic_clinical_action_allowed',

        'source_context',
        'priority_context',
        'eligibility_context',
        'review_context',
        'resolution_context',
        'action_payload',

        'reviewed_by',
        'reviewed_at',
        'review_decision',
        'review_notes',

        'resolved_by',
        'resolved_at',

        'generated_at',
    ];

    protected $casts = [
        'lifecycle_snapshot_id' => 'integer',
        'resident_id' => 'integer',

        'priority_score' => 'integer',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'automatic_execution_allowed' => 'boolean',
        'automatic_change_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'source_context' => 'array',
        'priority_context' => 'array',
        'eligibility_context' => 'array',
        'review_context' => 'array',
        'resolution_context' => 'array',
        'action_payload' => 'array',

        'reviewed_by' => 'integer',
        'resolved_by' => 'integer',

        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'generated_at' => 'datetime',
    ];
}