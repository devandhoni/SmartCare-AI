<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Resident;
use App\Models\User;
use App\Models\AiAlert;



class NurseTask extends Model
{


    protected $table = 'nurse_tasks';



    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';





    protected $fillable = [


        'resident_id',


        'source_alert_id',


        'ai_generated',


        'assigned_to',


        'task_name',


        'description',

        
        'clinical_action_plan',


        'scheduled_time',


        'status',


        'priority',


        'acknowledged_by',


        'acknowledged_at',


        'completed_time'


    ];






    /*
    |--------------------------------------------------------------------------
    | Data Casting
    |--------------------------------------------------------------------------
    */


    protected $casts = [

        'clinical_action_plan'=>'array',

        'ai_generated' => 'boolean',

        'scheduled_time' => 'datetime',

        'acknowledged_at' => 'datetime',

        'completed_time' => 'datetime',


        


    ];









    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */





    /*
    |--------------------------------------------------------------------------
    | Resident
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
    | Assigned Nurse/User
    |--------------------------------------------------------------------------
    */


    public function assignedUser()
    {


        return $this->belongsTo(

            User::class,

            'assigned_to'

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Source AI Alert
    |
    | Track which AI alert generated this task
    |--------------------------------------------------------------------------
    */


    public function alert()
    {


        return $this->belongsTo(

            AiAlert::class,

            'source_alert_id'

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Nurse Who Acknowledged Task
    |--------------------------------------------------------------------------
    */


    public function acknowledgedBy()
    {


        return $this->belongsTo(

            User::class,

            'acknowledged_by'

        );


    }


    public function clinicalOutcome()
    {

        return $this->hasOne(

            AiClinicalOutcome::class,

            'nurse_task_id'

        );

    }




}