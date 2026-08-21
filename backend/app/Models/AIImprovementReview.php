<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementReview extends Model
{
    use HasFactory;

    protected $table =
        'a_i_improvement_reviews';

    protected $fillable = [

        'candidate_code',
        'candidate_category',
        'candidate_title',

        'resident_id',
        'scope_type',

        'learning_maturity',
        'pattern_confidence',

        'evidence_count',
        'minimum_evidence_required',

        'safety_status',
        'eligibility_status',
        'implementation_review_ready',

        'review_status',
        'review_decision',
        'review_notes',

        'reviewed_by',
        'reviewed_at',

        'approved_for_testing',
        'approved_for_implementation',

        'automatic_change_allowed',
        'human_approval_required',
        'governance_validation_required',

        'candidate_payload',
        'safety_payload',

        'submitted_at',
    ];

    protected $casts = [

        'implementation_review_ready' =>
            'boolean',

        'approved_for_testing' =>
            'boolean',

        'approved_for_implementation' =>
            'boolean',

        'automatic_change_allowed' =>
            'boolean',

        'human_approval_required' =>
            'boolean',

        'governance_validation_required' =>
            'boolean',

        'candidate_payload' =>
            'array',

        'safety_payload' =>
            'array',

        'reviewed_at' =>
            'datetime',

        'submitted_at' =>
            'datetime',
    ];
}