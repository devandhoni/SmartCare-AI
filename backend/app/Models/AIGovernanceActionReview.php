<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGovernanceActionReview extends Model
{
    use HasFactory;

    protected $table = 'a_i_governance_action_reviews';

    protected $fillable = [
        'governance_action_id',
        'lifecycle_snapshot_id',
        'review_decision',
        'review_status',
        'reviewer_type',
        'reviewer_id',
        'reviewer_name',
        'reviewer_role',
        'decision_rationale',
        'conditions',
        'review_context',
        'reviewed_at',
    ];

    protected $casts = [
        'governance_action_id' => 'integer',
        'lifecycle_snapshot_id' => 'integer',
        'reviewer_id' => 'integer',
        'conditions' => 'array',
        'review_context' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function governanceAction()
    {
        return $this->belongsTo(
            AIGovernanceAction::class,
            'governance_action_id'
        );
    }

    public function reviewer()
    {
        return $this->morphTo();
    }
}