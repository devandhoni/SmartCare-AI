<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImprovementMonitoring extends Model
{
    use HasFactory;

    protected $table = 'a_i_improvement_monitorings';

    protected $fillable = [
        'improvement_execution_id',
        'implementation_review_id',
        'improvement_review_id',
        'improvement_test_id',

        'candidate_code',
        'candidate_category',
        'scope_type',
        'resident_id',

        'monitoring_status',
        'monitoring_stage',
        'monitoring_mode',

        'baseline_score',
        'baseline_direction',
        'baseline_context',

        'monitoring_window_days',
        'minimum_observations_required',
        'regression_tolerance_percentage',
        'safety_threshold_percentage',

        'observation_count',
        'latest_observed_score',
        'average_observed_score',
        'performance_change',
        'performance_direction',

        'stability_status',
        'regression_status',
        'safety_monitoring_status',
        'sustainability_status',

        'monitoring_configuration',
        'observation_summary',
        'longitudinal_analysis',
        'regression_analysis',
        'safety_analysis',
        'sustainability_analysis',
        'monitoring_payload',

        'automatic_change_allowed',
        'automatic_rollback_allowed',
        'automatic_deployment_allowed',
        'automatic_clinical_action_allowed',

        'human_review_required',
        'governance_validation_required',

        'monitoring_started_at',
        'last_observed_at',
        'last_analyzed_at',
        'monitoring_completed_at',
    ];

    protected $casts = [
        'resident_id' => 'integer',

        'baseline_score' => 'float',

        'monitoring_window_days' => 'integer',
        'minimum_observations_required' => 'integer',

        'regression_tolerance_percentage' => 'float',
        'safety_threshold_percentage' => 'float',

        'observation_count' => 'integer',

        'latest_observed_score' => 'float',
        'average_observed_score' => 'float',
        'performance_change' => 'float',

        'baseline_context' => 'array',

        'monitoring_configuration' => 'array',
        'observation_summary' => 'array',
        'longitudinal_analysis' => 'array',
        'regression_analysis' => 'array',
        'safety_analysis' => 'array',
        'sustainability_analysis' => 'array',
        'monitoring_payload' => 'array',

        'automatic_change_allowed' => 'boolean',
        'automatic_rollback_allowed' => 'boolean',
        'automatic_deployment_allowed' => 'boolean',
        'automatic_clinical_action_allowed' => 'boolean',

        'human_review_required' => 'boolean',
        'governance_validation_required' => 'boolean',

        'monitoring_started_at' => 'datetime',
        'last_observed_at' => 'datetime',
        'last_analyzed_at' => 'datetime',
        'monitoring_completed_at' => 'datetime',
    ];
}