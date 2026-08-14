<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\VitalSign;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\ClinicalRecommendation;
use App\Models\NurseTask;
use App\Models\ActivityLog;
use Carbon\Carbon;



class HealthJourneyAnalyzer
{


    /*
    |--------------------------------------------------------------------------
    | Generate Resident Health Journey
    |--------------------------------------------------------------------------
    */


    public function analyze($residentId)
    {


        $resident = Resident::findOrFail($residentId);



        /*
        |--------------------------------------------------------------------------
        | Collect Timeline Events
        |--------------------------------------------------------------------------
        */


        $timeline = [];





        /*
        |--------------------------------------------------------------------------
        | Vital Sign History
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



        foreach($vitals as $vital)
        {


            $timeline[] = [


                'date'=>$vital->created_on,


                'event_type'=>'Vital Recorded',


                'description'=>

                "Blood Pressure: ".
                $vital->blood_pressure_systolic.
                "/".
                $vital->blood_pressure_diastolic.
                ", Oxygen: ".
                $vital->oxygen_level.
                "%, Temperature: ".
                $vital->temperature



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | AI Alert History
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



        foreach($alerts as $alert)
        {


            $timeline[] = [


                'date'=>$alert->created_on,


                'event_type'=>'AI Alert',


                'description'=>

                $alert->alert_type.
                " - ".
                $alert->severity



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | Prediction History
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


            $timeline[] = [


                'date'=>$prediction->created_on,


                'event_type'=>'AI Prediction',


                'description'=>

                $prediction->prediction_type.
                " detected with ".
                $prediction->confidence.
                "% confidence"



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | Clinical Recommendation History
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



        foreach($recommendations as $recommendation)
        {


            $timeline[] = [


                'date'=>$recommendation->created_on,


                'event_type'=>'Clinical Recommendation',


                'description'=>

                $recommendation->recommendation



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | Nurse Task History
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


            $timeline[] = [


                'date'=>$task->created_on,


                'event_type'=>'Nurse Task',


                'description'=>

                $task->task_name.
                " - ".
                $task->status



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | Activity Log History
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



        foreach($activities as $activity)
        {


            $timeline[] = [


                'date'=>$activity->created_on,


                'event_type'=>'System Activity',


                'description'=>

                $activity->module.
                " : ".
                $activity->description



            ];


        }





        /*
        |--------------------------------------------------------------------------
        | Sort Timeline Chronologically
        |--------------------------------------------------------------------------
        */


        $timeline = collect($timeline)
            ->sortBy('date')
            ->values();







        /*
        |--------------------------------------------------------------------------
        | Generate AI Summary
        |--------------------------------------------------------------------------
        */


        $summary = "Stable health condition maintained.";


        $insights = [];




        if($alerts->count() > 0)
        {

            $summary =
            "Resident experienced multiple AI detected health risks.";


            $insights[] =
            "AI alerts were generated requiring clinical attention.";

        }




        if($predictions->count() > 0)
        {


            $insights[] =
            "AI prediction models identified potential health deterioration risks.";

        }





        if($tasks->where('status','Completed')->count() > 0)
        {


            $insights[] =
            "Nursing interventions have been performed.";

        }







        return [



            'resident'=>[

                'id'=>$resident->id,

                'name'=>$resident->full_name

            ],




            'health_summary'=>[

                'overall_status'=>
                $alerts->count()>0
                ?
                'Deteriorating'
                :
                'Stable',


                'summary'=>$summary

            ],





            'timeline'=>$timeline,





            'ai_insight'=>$insights




        ];



    }



}