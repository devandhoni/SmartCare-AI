<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentAdmissionHospitalization extends Model
{
    protected $fillable = [
        'resident_admission_id',
        'resident_id',
        'hospital_name',
        'hospitalization_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'hospitalization_date' =>
            'date',
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