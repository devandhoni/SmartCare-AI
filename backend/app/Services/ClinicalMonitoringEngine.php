<?php

namespace App\Services;


use App\Models\AiMonitoringLog;



class ClinicalMonitoringEngine
{


    /*
    |--------------------------------------------------------------------------
    | Store AI Decision Monitoring Snapshot
    |--------------------------------------------------------------------------
    */


    public function record(array $decision)
    {


        $residentId =
            $decision['resident_id'];


        $currentScore =
            $decision['decision_score'] ?? 0;


        $currentPriority =
            $decision['priority'] ?? 'NORMAL';



        /*
        |--------------------------------------------------------------------------
        | Get Previous AI Decision
        |--------------------------------------------------------------------------
        */


        $previous =

            AiMonitoringLog::where(
                'resident_id',
                $residentId
            )
            ->latest()
            ->first();





        $previousScore =
            $previous?->decision_score;


        $previousPriority =
            $previous?->priority;




        /*
        |--------------------------------------------------------------------------
        | Determine Trend
        |--------------------------------------------------------------------------
        */


        $trend = "NEW";


        if($previous)
        {


            if($currentScore > $previousScore)
            {

                $trend = "WORSENING";

            }
            elseif($currentScore < $previousScore)
            {

                $trend = "IMPROVING";

            }
            else
            {

                $trend = "STABLE";

            }


        }





        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */


        $summary =


            match($trend)
            {


                "WORSENING" =>

                    "Resident risk increased from "
                    .$previousPriority.
                    " to "
                    .$currentPriority,



                "IMPROVING" =>

                    "Resident risk improved from "
                    .$previousPriority.
                    " to "
                    .$currentPriority,



                "STABLE" =>

                    "Resident condition remains stable.",



                default =>

                    "Initial AI clinical assessment recorded."


            };






        /*
        |--------------------------------------------------------------------------
        | Extract Vital Sign Reference Safely
        |--------------------------------------------------------------------------
        */


        $vitalSignId = null;


        if(isset($decision['latest_vital']))
        {


            if(is_object($decision['latest_vital']))
            {

                $vitalSignId =
                    $decision['latest_vital']->id;

            }
            elseif(is_array($decision['latest_vital']))
            {

                $vitalSignId =
                    $decision['latest_vital']['id'] ?? null;

            }


        }





        /*
        |--------------------------------------------------------------------------
        | Save Monitoring Log
        |--------------------------------------------------------------------------
        */


        return AiMonitoringLog::create([


            'resident_id'=>
                $residentId,


            'decision_score'=>
                $currentScore,


            'priority'=>
                $currentPriority,


            'previous_priority'=>
                $previousPriority,


            'previous_score'=>
                $previousScore,


            'trend'=>
                $trend,


            'summary'=>
                $summary,


            'vital_sign_id'=>
                $vitalSignId


        ]);


    }



}