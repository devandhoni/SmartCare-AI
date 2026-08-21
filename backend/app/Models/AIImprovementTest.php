<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementTest extends Model
{
    use HasFactory;

    protected $table =
        'a_i_improvement_tests';

    protected $fillable = [
        'improvement_review_id',
        'candidate_code',
        'candidate_category',
        'resident_id',
        'scope_type',
        'test_status',
        'test_environment',
        'test_objective',
        'test_hypothesis',
        'baseline_configuration',
        'proposed_configuration',
        'test_metrics',
        'test_results',
        'production_change_allowed',
        'automatic_deployment_allowed',
        'human_validation_required',
        'governance_validation_required',
        'created_by',
        'validated_by',
        'started_at',
        'completed_at',
        'validated_at',
    ];

    protected $casts = [
        'baseline_configuration' =>
            'array',

        'proposed_configuration' =>
            'array',

        'test_metrics' =>
            'array',

        'test_results' =>
            'array',

        'production_change_allowed' =>
            'boolean',

        'automatic_deployment_allowed' =>
            'boolean',

        'human_validation_required' =>
            'boolean',

        'governance_validation_required' =>
            'boolean',

        'started_at' =>
            'datetime',

        'completed_at' =>
            'datetime',

        'validated_at' =>
            'datetime',
    ];

    public function review()
    {
        return $this->belongsTo(
            AIImprovementReview::class,
            'improvement_review_id'
        );
    }
}