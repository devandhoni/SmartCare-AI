<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    protected $table = 'residents';

    /*
    |--------------------------------------------------------------------------
    | Custom Timestamp Columns
    |--------------------------------------------------------------------------
    */

    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_on';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        // Room Assignment
        'room_id',

        // Personal Information
        'full_name',
        'ic_number',
        'date_of_birth',
        'gender',
        'nationality',
        'address',
        'profile_photo',
        'phone',
        'email',

        // Emergency Contact
        'emergency_contact',
        'emergency_relationship',
        'emergency_phone',

        // Medical Summary
        'blood_type',
        'medical_condition',
        'allergies',
        'chronic_disease',
        'medical_notes',

        // Admission Information
        'admission_date',
        'discharge_date',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Room
    |--------------------------------------------------------------------------
    */

    public function room()
    {
        return $this->belongsTo(
            Room::class,
            'room_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Contacts
    |--------------------------------------------------------------------------
    */

    public function contacts()
    {
        return $this->hasMany(
            ResidentContact::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

    public function documents()
    {
        return $this->hasMany(
            ResidentDocument::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admissions
    |--------------------------------------------------------------------------
    */

    public function admissions()
    {
        return $this->hasMany(
            ResidentAdmission::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admission Medical Histories
    |--------------------------------------------------------------------------
    */

    public function admissionMedicalHistories()
    {
        return $this->hasMany(
            ResidentAdmissionMedicalHistory::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admission Hospitalizations
    |--------------------------------------------------------------------------
    */

    public function admissionHospitalizations()
    {
        return $this->hasMany(
            ResidentAdmissionHospitalization::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Medical Records
    |--------------------------------------------------------------------------
    */

    public function medicalRecords()
    {
        return $this->hasMany(
            MedicalRecord::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Medication Administration Records
    |--------------------------------------------------------------------------
    */

    public function medicationAdministrationRecords()
    {
        return $this->hasMany(
            MedicationAdministrationRecord::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Medications
    |--------------------------------------------------------------------------
    */

    public function medications()
    {
        return $this->hasMany(
            ResidentMedication::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vital Signs
    |--------------------------------------------------------------------------
    */

    public function vitalSigns()
    {
        return $this->hasMany(
            VitalSign::class,
            'resident_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Discharge Resident
    |--------------------------------------------------------------------------
    */

    public function discharges()
    {
        return $this->hasMany(
            ResidentDischarge::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Health Risk Score
    |--------------------------------------------------------------------------
    */

    public function healthRiskScore()
    {
        return $this->hasOne(
            HealthRiskScore::class,
            'resident_id'
        )->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | AI Health Alerts
    |--------------------------------------------------------------------------
    */

    public function alerts()
    {
        return $this->hasMany(
            AiAlert::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Nurse Tasks
    |--------------------------------------------------------------------------
    */

    public function nurseTasks()
    {
        return $this->hasMany(
            NurseTask::class,
            'resident_id'
        );
    }
}