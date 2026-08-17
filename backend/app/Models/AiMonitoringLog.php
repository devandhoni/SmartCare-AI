<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AiMonitoringLog extends Model
{


    protected $table = 'ai_monitoring_logs';



    protected $fillable = [

        'resident_id',

        'decision_score',

        'priority',

        'previous_priority',

        'previous_score',

        'trend',

        'summary',

        'vital_sign_id'

    ];




    protected $casts = [


        'decision_score' => 'integer',

        'previous_score' => 'integer'


    ];





    /*
    |--------------------------------------------------------------------------
    | Resident Relationship
    |--------------------------------------------------------------------------
    */


    public function resident()
    {

        return $this->belongsTo(

            Resident::class,

            'resident_id'

        );

    }





    /*
    |--------------------------------------------------------------------------
    | Vital Sign Relationship
    |--------------------------------------------------------------------------
    */


    public function vitalSign()
    {

        return $this->belongsTo(

            VitalSign::class,

            'vital_sign_id'

        );

    }


}