<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIExecutiveIntelligenceSnapshot extends Model
{
    protected $table =
        'ai_executive_intelligence_snapshots';

    protected $fillable = [

        'report_status',

        'active_care_residents',
        'non_active_residents',

        'active_critical_cases',
        'active_care_alerts',

        'predictive_priority_residents',
        'care_priority_residents',

        'execution_ready_actions',
        'doctor_review_actions',

        'evaluated_workflows',
        'workflow_success_rate',

        'total_outcomes_recorded',
        'average_ai_accuracy',
        'intervention_success_rate',

        'sla_compliance_percentage',
        'task_completion_rate',

        'learning_maturity',
        'learning_confidence',

        'snapshot_payload',

        'captured_at',
    ];

    protected $casts = [

        'active_care_residents' =>
            'integer',

        'non_active_residents' =>
            'integer',

        'active_critical_cases' =>
            'integer',

        'active_care_alerts' =>
            'integer',

        'predictive_priority_residents' =>
            'integer',

        'care_priority_residents' =>
            'integer',

        'execution_ready_actions' =>
            'integer',

        'doctor_review_actions' =>
            'integer',

        'evaluated_workflows' =>
            'integer',

        'workflow_success_rate' =>
            'float',

        'total_outcomes_recorded' =>
            'integer',

        'average_ai_accuracy' =>
            'float',

        'intervention_success_rate' =>
            'float',

        'sla_compliance_percentage' =>
            'float',

        'task_completion_rate' =>
            'float',

        'snapshot_payload' =>
            'array',

        'captured_at' =>
            'datetime',
    ];
}