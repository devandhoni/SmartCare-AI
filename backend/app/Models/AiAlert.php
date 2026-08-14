<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AiAlert extends Model
{

    protected $table = 'ai_alerts';


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

        'alert_type',

        'severity',

        'message',

        'ai_confidence',

        'status',


        'resolved_by',

        'resolved_at',

        'resolution_note',


        'acknowledged_by',

        'acknowledged_at',


        'created_on',

        'updated_on'


    ];




    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [


        'resolved_at' => 'datetime',

        'acknowledged_at' => 'datetime',


        'created_on' => 'datetime',

        'updated_on' => 'datetime',


        'ai_confidence' => 'decimal:2'


    ];






    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */



    /*
    | Alert Owner
    */

    public function resident()
    {

        return $this->belongsTo(
            Resident::class
        );

    }





    /*
    | User who resolved alert
    */

    public function resolver()
    {

        return $this->belongsTo(
            User::class,
            'resolved_by'
        );

    }





    /*
    | User who acknowledged alert
    */

    public function acknowledger()
    {

        return $this->belongsTo(
            User::class,
            'acknowledged_by'
        );

    }



}