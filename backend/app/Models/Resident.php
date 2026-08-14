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

        // Personal Information

        'full_name',
        'ic_number',
        'date_of_birth',
        'gender',
        'nationality',
        'address',
        'profile_photo',


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
        'status'

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


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
            MedicationAdministrationRecord::class
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
    | Latest Health Risk Score
    |--------------------------------------------------------------------------
    |
    | One resident has one latest AI risk assessment
    |
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