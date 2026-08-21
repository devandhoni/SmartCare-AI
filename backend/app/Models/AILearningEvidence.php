<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AILearningEvidence extends Model
{
    use HasFactory;

    protected $table = 'ai_learning_evidence';

    protected $fillable = [
        'evidence_type',
        'source_type',
        'source_id',

        'resident_id',
        'resident_status',
        'active_care_eligible',

        'ai_domain',
        'ai_decision',
        'ai_confidence',
        'ai_risk_score',

        'human_review_status',
        'human_agreement',
        'workflow_status',

        'outcome_status',
        'accuracy_score',
        'effectiveness_score',

        'evidence_quality',
        'learning_status',

        'evidence_payload',

        'observed_at',
        'evaluated_at',
    ];

    protected $casts = [
        'active_care_eligible' => 'boolean',
        'human_agreement' => 'boolean',

        'ai_confidence' => 'float',
        'ai_risk_score' => 'float',
        'accuracy_score' => 'float',
        'effectiveness_score' => 'float',

        'evidence_payload' => 'array',

        'observed_at' => 'datetime',
        'evaluated_at' => 'datetime',
    ];
}