<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class HealthRiskScore extends Model
{


    protected $table = 'health_risk_scores';



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

        'risk_score',

        'risk_level',

        'reason'

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    */


    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id',
        );

    }


}