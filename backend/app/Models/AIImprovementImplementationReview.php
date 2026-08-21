<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementImplementationReview extends Model
{
    use HasFactory;

    protected $table =
        'a_i_improvement_implementation_reviews';

    protected $fillable = [
        'improvement_review_id',
        'improvement_test_id',
        'candidate_code',
        'candidate_category',
        'resident_id',
        'scope_type',
        'readiness_status',
        'implementation_review_ready',
        'review_status',
        'review_decision',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_for_implementation',
        'production_change_allowed',
        'automatic_deployment_allowed',
        'automatic_change_allowed',
        'human_approval_required',
        'governance_validation_required',
        'readiness_payload',
        'decision_payload',
        'submitted_at',
    ];

    protected $casts = [
        'implementation_review_ready' =>
            'boolean',

        'approved_for_implementation' =>
            'boolean',

        'production_change_allowed' =>
            'boolean',

        'automatic_deployment_allowed' =>
            'boolean',

        'automatic_change_allowed' =>
            'boolean',

        'human_approval_required' =>
            'boolean',

        'governance_validation_required' =>
            'boolean',

        'readiness_payload' =>
            'array',

        'decision_payload' =>
            'array',

        'reviewed_at' =>
            'datetime',

        'submitted_at' =>
            'datetime',
    ];

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