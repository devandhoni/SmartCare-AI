<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentAdmission extends Model
{
    protected $fillable = [
        'resident_id',
        'admission_number',
        'admitted_at',
        'admission_type',
        'admission_source',
        'reason_for_admission',
        'medical_summary',
        'mobility_notes',
        'dietary_notes',
        'special_care_instructions',
        'belongings_notes',
        'status',
        'completed_at',
        'admitted_by',
        'completed_by',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function admittedBy()
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function consent()
    {
        return $this->hasOne(
            ResidentAdmissionConsent::class,
            'resident_admission_id'
        );
    }

    public function medicalHistory()
    {
        return $this->hasOne(
            ResidentAdmissionMedicalHistory::class,
            'resident_admission_id'
        );
    }

    public function hospitalizations()
    {
        return $this->hasMany(
            ResidentAdmissionHospitalization::class,
            'resident_admission_id'
        );
    }
}