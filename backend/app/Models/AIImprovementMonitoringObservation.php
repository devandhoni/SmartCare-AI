<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementMonitoringObservation extends Model
{
    use HasFactory;

    protected $table = 'a_i_improvement_monitoring_observations';

    protected $fillable = [
        'monitoring_id',
        'improvement_execution_id',
        'candidate_code',

        'observation_type',

        'observed_score',
        'baseline_score',

        'absolute_change',
        'percentage_change',

        'performance_direction',

        'safety_passed',
        'outcome_status',
        'evidence_quality',

        'observation_context',

        'human_reviewed',
        'reviewed_by',
        'reviewed_at',

        'observed_at',
    ];

    protected $casts = [
        'monitoring_id' => 'integer',
        'improvement_execution_id' => 'integer',

        'observed_score' => 'float',
        'baseline_score' => 'float',

        'absolute_change' => 'float',
        'percentage_change' => 'float',

        'safety_passed' => 'boolean',

        'observation_context' => 'array',

        'human_reviewed' => 'boolean',

        'reviewed_by' => 'integer',

        'reviewed_at' => 'datetime',
        'observed_at' => 'datetime',
    ];
}