<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\VitalSign;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\ClinicalRecommendation;
use App\Models\NurseTask;
use App\Models\ActivityLog;
use App\Models\AiClinicalOutcome;

use Illuminate\Support\Collection;
use Carbon\Carbon;



class HealthJourneyAnalyzerBackup
{


    /*
    |--------------------------------------------------------------------------
    | AI Clinical Journey Intelligence Engine
    |--------------------------------------------------------------------------
    */


    public function analyze($residentId)
    {


        $resident = Resident::findOrFail($residentId);



        /*
        |--------------------------------------------------------------------------
        | Raw Clinical Events Container
        |--------------------------------------------------------------------------
        */


        $timeline = [];



        /*
        |--------------------------------------------------------------------------
        | 1. Vital Intelligence Engine
        |--------------------------------------------------------------------------
        */


        $vitals = VitalSign::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_on',
                'asc'
            )
            ->get();




        if($vitals->count() > 0)
        {


            $vitalGroups = [];



            foreach($vitals as $vital)
            {


                $signature = implode(
                    "-",
                    [

                        $vital->blood_pressure_systolic,

                        $vital->blood_pressure_diastolic,

                        $vital->oxygen_level,

                        $vital->blood_glucose,

                        $vital->temperature,

                        $vital->heart_rate

                    ]
                );



                if(!isset($vitalGroups[$signature]))
                {


                    $vitalGroups[$signature] = [

                        "first_date"=>$vital->created_on,

                        "count"=>0,

                        "vital"=>$vital

                    ];


                }



                $vitalGroups[$signature]['count']++;


            }






            foreach($vitalGroups as $group)
            {


                $vital = $group['vital'];



                $severity =
                    (
                        $vital->blood_pressure_systolic >=180

                        ||

                        $vital->oxygen_level <90

                        ||

                        $vital->temperature >=39

                    )

                    ?

                    "CRITICAL"

                    :

                    "NORMAL";





                $timeline[]=[


                    "date"=>$group['first_date'],


                    "event_type"=>

                    $severity=="CRITICAL"

                    ?

                    "Critical Vital Episode"

                    :

                    "Vital Assessment",




                    "severity"=>$severity,




                    "occurrences"=>$group['count'],




                    "description"=>


                    "Blood Pressure: ".
                    $vital->blood_pressure_systolic.
                    "/".
                    $vital->blood_pressure_diastolic.

                    ", Oxygen: ".
                    $vital->oxygen_level.

                    "%, Temperature: ".
                    $vital->temperature.



                    (
                        $group['count']>1

                        ?

                        ". Repeated ".$group['count']." times during monitoring."

                        :

                        ""

                    )

                ];

            }



        }







        /*
        |--------------------------------------------------------------------------
        | 2. AI Alert Intelligence
        |--------------------------------------------------------------------------
        */


        $alerts = AiAlert::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_on',
                'asc'
            )
            ->get();





        $alertGroups = $alerts
            ->groupBy(function($alert){


                return 

                $alert->alert_type
                .
                "-"
                .
                $alert->severity;


            });







        foreach($alertGroups as $key=>$group)
        {


            $first=$group->first();



            $timeline[]=[


                "date"=>$first->created_on,


                "event_type"=>"AI Alert Episode",


                "severity"=>$first->severity,


                "occurrences"=>$group->count(),



                "description"=>


                $first->alert_type.
                " detected with severity ".
                $first->severity.

                (
                    $group->count()>1

                    ?

                    ". Occurred ".$group->count()." times."

                    :

                    ""

                )


            ];



        }







        /*
        |--------------------------------------------------------------------------
        | 3. AI Prediction Intelligence
        |--------------------------------------------------------------------------
        */


        $predictions = HealthPrediction::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_on',
                'asc'
            )
            ->get();







        foreach($predictions as $prediction)
        {


            $timeline[]=[


                "date"=>$prediction->created_on,


                "event_type"=>"AI Prediction",


                "severity"=>$prediction->risk_level,



                "occurrences"=>1,



                "description"=>


                $prediction->prediction_type.

                " detected with ".

                $prediction->confidence.

                "% confidence"



            ];


        }

                /*
        |--------------------------------------------------------------------------
        | 4. Clinical Recommendation Intelligence
        |--------------------------------------------------------------------------
        */


        $recommendations = ClinicalRecommendation::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_on',
                'asc'
            )
            ->get();



        $recommendationGroups = $recommendations
            ->groupBy(function($item){

                return $item->recommendation;

            });





        foreach($recommendationGroups as $recommendation=>$group)
        {


            $first = $group->first();



            $timeline[]=[


                "date"=>$first->created_on,


                "event_type"=>"Clinical Recommendation",


                "severity"=>$first->priority,



                "occurrences"=>$group->count(),




                "description"=>


                $recommendation.


                (

                    $group->count()>1

                    ?

                    " (Repeated ".$group->count()." times)"

                    :

                    ""

                )


            ];


        }







        /*
        |--------------------------------------------------------------------------
        | 5. Nurse Intervention Intelligence
        |--------------------------------------------------------------------------
        */


        $tasks = NurseTask::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_on',
                'asc'
            )
            ->get();






        foreach($tasks as $task)
        {


            $timeline[]=[


                "date"=>$task->created_on,


                "event_type"=>"Nurse Intervention",


                "severity"=>$task->priority,



                "occurrences"=>1,



                "description"=>


                $task->task_name.
                " - ".
                $task->status



            ];



        }









        /*
        |--------------------------------------------------------------------------
        | 6. AI Clinical Outcome Learning
        |--------------------------------------------------------------------------
        */


        $outcomes = AiClinicalOutcome::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'created_at',
                'asc'
            )
            ->get();






        foreach($outcomes as $outcome)
        {


            $timeline[]=[


                "date"=>$outcome->created_at,


                "event_type"=>"AI Outcome Evaluation",


                "severity"=>$outcome->outcome_status,



                "occurrences"=>1,



                "description"=>


                "AI prediction outcome evaluated. Result: ".

                $outcome->outcome_status.

                ". AI Accuracy: ".

                $outcome->ai_accuracy_score.

                "%"



            ];


        }









/*
|--------------------------------------------------------------------------
| 7. System Activity Intelligence
|--------------------------------------------------------------------------
*/


$activities = ActivityLog::where(
        'resident_id',
        $residentId
    )
    ->orderBy(
        'created_on',
        'asc'
    )
    ->get();



$activityGroups = [];



foreach($activities as $activity)
{


    $description = strtolower(
        trim($activity->description)
    );



    /*
    |--------------------------------------------------------------------------
    | Clinical Decision AI
    |--------------------------------------------------------------------------
    */


    if(
        str_contains(
            $description,
            'clinical decision ai'
        )
    )
    {


        preg_match(
            '/score\s*(\d+)/i',
            $activity->description,
            $matches
        );


        $score =
            $matches[1]
            ??
            'unknown';



        $key =
            'clinical_decision_'.$score;



        if(!isset($activityGroups[$key]))
        {

            $activityGroups[$key]=[

                'date'=>$activity->created_on,

                'event_type'=>'AI Monitoring Cycle',

                'severity'=>
                    str_contains(
                        $description,
                        'critical'
                    )
                    ?
                    'CRITICAL'
                    :
                    'NORMAL',

                'occurrences'=>0,

                'description'=>
                    "AI monitored clinical decision risk score ".$score

            ];

        }


        $activityGroups[$key]['occurrences']++;


        continue;


    }






    /*
    |--------------------------------------------------------------------------
    | AI Alert Escalation
    |--------------------------------------------------------------------------
    */


    if(
        str_contains(
            $description,
            'ai alert escalation'
        )
    )
    {


        $key='alert_escalation';



        if(!isset($activityGroups[$key]))
        {

            $activityGroups[$key]=[

                'date'=>$activity->created_on,

                'event_type'=>'AI Alert Escalation',

                'severity'=>'URGENT',

                'occurrences'=>0,

                'description'=>
                    'AI alert escalation activities recorded'

            ];

        }



        $activityGroups[$key]['occurrences']++;


        continue;

    }







    /*
    |--------------------------------------------------------------------------
    | Nurse Activity
    |--------------------------------------------------------------------------
    */


    if(
        str_contains(
            $description,
            'nurse task'
        )
    )
    {


        $key='nurse_activity';



        if(!isset($activityGroups[$key]))
        {

            $activityGroups[$key]=[

                'date'=>$activity->created_on,

                'event_type'=>'Nurse Task Activity',

                'severity'=>'NORMAL',

                'occurrences'=>0,

                'description'=>
                    'Nurse workflow activities recorded'

            ];

        }


        $activityGroups[$key]['occurrences']++;


        continue;

    }








    /*
    |--------------------------------------------------------------------------
    | Other System Activities
    |--------------------------------------------------------------------------
    */


    $key =
        $activity->module
        .
        "-"
        .
        $activity->description;



    if(!isset($activityGroups[$key]))
    {


        $activityGroups[$key]=[

            'date'=>$activity->created_on,

            'event_type'=>'System Activity',

            'occurrences'=>0,

            'description'=>
                $activity->module.
                " : ".
                $activity->description

        ];

    }



    $activityGroups[$key]['occurrences']++;


}




foreach($activityGroups as $event)
{

    $timeline[]=$event;

}









        /*
        |--------------------------------------------------------------------------
        | Timeline Optimization
        |--------------------------------------------------------------------------
        */


        $originalEventCount =
            $activities->count()
            +
            $vitals->count()
            +
            $alerts->count()
            +
            $predictions->count()
            +
            $recommendations->count()
            +
            $tasks->count()
            +
            $outcomes->count();



        $timeline = collect($timeline)
            ->sortBy('date')
            ->values();





        $optimizedEventCount = $timeline->count();




        $duplicatesRemoved =

            $originalEventCount
            -
            $optimizedEventCount;









        /*
        |--------------------------------------------------------------------------
        | AI Learning Performance
        |--------------------------------------------------------------------------
        */


        $averageAccuracy =

            $outcomes->count()

            ?

            round(
                $outcomes->avg(
                    'ai_accuracy_score'
                ),
                2
            )

            :

            0;





        $successfulInterventions =

            $outcomes
            ->where(
                'outcome_status',
                'IMPROVED'
            )
            ->count();





        $successRate =

            $outcomes->count()

            ?

            round(
                (
                    $successfulInterventions
                    /
                    $outcomes->count()
                )
                *
                100,
                2
            )

            :

            0;









        /*
        |--------------------------------------------------------------------------
        | AI Health Summary
        |--------------------------------------------------------------------------
        */


        $healthStatus =
        
        $alerts->where(
            'severity',
            'CRITICAL'
        )->count()>0

        ?

        "Deteriorating"

        :

        "Stable";






        $insights=[];





        if($alerts->count()>0)
        {

            $insights[] =
            "AI alerts were generated requiring clinical attention.";

        }





        if($predictions->count()>0)
        {

            $insights[] =
            "AI prediction models identified potential deterioration risks.";

        }





        if(
            $tasks
            ->where(
                'status',
                'Completed'
            )
            ->count()>0
        )
        {

            $insights[] =
            "Nursing intervention activities were completed.";

        }





        if($averageAccuracy>0)
        {

            $insights[] =
            "AI learning evaluation recorded ".
            $averageAccuracy.
            "% prediction accuracy.";

        }









        /*
        |--------------------------------------------------------------------------
        | Final Health Journey Response
        |--------------------------------------------------------------------------
        */


        return [


            "resident"=>[

                "id"=>$resident->id,

                "name"=>$resident->full_name

            ],




            "health_summary"=>[


                "overall_status"=>$healthStatus,


                "summary"=>

                $healthStatus=="Deteriorating"

                ?

                "Resident experienced AI detected clinical risks requiring monitoring."

                :

                "Resident health condition remains stable."



            ],






            "timeline_statistics"=>[


                "total_events"=>$originalEventCount,


                "optimized_events"=>$optimizedEventCount,


                "duplicates_removed"=>$duplicatesRemoved



            ],






            "ai_learning_summary"=>[


                "predictions_evaluated"=>$outcomes->count(),


                "average_ai_accuracy"=>$averageAccuracy,


                "successful_interventions"=>$successfulInterventions,


                "success_rate"=>$successRate



            ],






            "timeline"=>$timeline,






            "ai_insight"=>$insights




        ];



    }



}