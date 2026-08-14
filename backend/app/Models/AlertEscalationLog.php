<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;



class AlertEscalationLog extends Model
{


    protected $table = 'alert_escalation_logs';



    protected $fillable = [


        'alert_id',

        'resident_id',

        'priority',

        'escalation_reason',

        'assigned_to',

        'escalated_at',

        'acknowledged_at',

        'resolved_at',

        'status'


    ];





    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */



    public function alert()
    {

        return $this->belongsTo(
            AiAlert::class,
            'alert_id'
        );

    }






    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );

    }






    public function assignedUser()
    {

        return $this->belongsTo(
            User::class,
            'assigned_to'
        );

    }



}