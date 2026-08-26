<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentAdmissionMedicalHistory extends Model
{
    protected $fillable = [
        'resident_admission_id',
        'resident_id',

        'primary_psychiatric_diagnosis',
        'other_diagnoses',

        'tobacco_use',
        'tobacco_amount_frequency',

        'alcohol_use',
        'alcohol_amount_frequency',

        'drug_use',
        'drug_type_frequency',

        'family_psychiatric_history',
        'family_psychiatric_history_details',

        'family_substance_abuse_history',
        'family_substance_abuse_history_details',

        'initial_observations',
        'additional_comments',
    ];

    protected $casts = [
        'tobacco_use' =>
            'boolean',

        'alcohol_use' =>
            'boolean',

        'drug_use' =>
            'boolean',

        'family_psychiatric_history' =>
            'boolean',

        'family_substance_abuse_history' =>
            'boolean',
    ];

    public function admission()
    {
        return $this->belongsTo(
            ResidentAdmission::class,
            'resident_admission_id'
        );
    }

    public function resident()
    {
        return $this->belongsTo(
            Resident::class
        );
    }
}