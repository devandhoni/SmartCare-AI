<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;



class AiClinicalOutcome extends Model
{


    protected $table =
        'ai_clinical_outcomes';




    protected $fillable = [


        'resident_id',

        'nurse_task_id',

        'prediction_id',

        'initial_risk_level',

        'initial_confidence',

        'outcome_status',

        'outcome_notes',

        'ai_accuracy_score',

        'recorded_by',

        'recorded_at'


    ];





    public function resident()
    {


        return $this->belongsTo(

            Resident::class,

            'resident_id'

        );


    }







    public function prediction()
    {


        return $this->belongsTo(

            HealthPrediction::class,

            'prediction_id'

        );


    }





    public function nurseTask()
    {


        return $this->belongsTo(

            NurseTask::class,

            'nurse_task_id'

        );


    }


}