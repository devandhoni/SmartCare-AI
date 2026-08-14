<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class VitalSign extends Model
{

    protected $table = 'vital_signs';


    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'resident_id',

        'blood_pressure_systolic',

        'blood_pressure_diastolic',

        'blood_glucose',

        'heart_rate',

        'oxygen_level',

        'temperature',

        'weight',

        'recorded_by',

        'recorded_at'

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    */


    public function resident()
    {

        return $this->belongsTo(
            Resident::class
        );

    }


}