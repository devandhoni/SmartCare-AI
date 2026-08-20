<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class HealthPrediction extends Model
{


    protected $table = 'health_predictions';



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

        'resident_id',

        'prediction_type',

        'risk_level',

        'prediction',

        'confidence'

    ];





    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    |
    | A health prediction belongs to one resident
    |
    */


    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );

    }

    public function outcomes()
    {

        return $this->hasMany(

            AiClinicalOutcome::class,

            'prediction_id'

        );

    }



}