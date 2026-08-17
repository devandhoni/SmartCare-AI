<?php

namespace App\Services;


use App\Models\AiMonitoringLog;
use App\Models\ClinicalTimeline;
use App\Enums\ClinicalEventType;



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
        | Get Previous AI Monitoring Decision
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
        | Generate Summary
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
        | Extract Vital Sign Reference
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
        | Save AI Monitoring Log
        |--------------------------------------------------------------------------
        */


        $log = AiMonitoringLog::create([


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








        /*
        |--------------------------------------------------------------------------
        | Create AI Monitoring Timeline Event
        |--------------------------------------------------------------------------
        */


        ClinicalTimeline::create([


            'resident_id'=>
                $residentId,


            'event_type'=>
                ClinicalEventType::AI_MONITORING,


            'event_title'=>
                'AI Monitoring Review',


            'event_description'=>

                'AI Score: '
                .$currentScore.
                ', Priority: '
                .$currentPriority.
                ', Trend: '
                .$trend,


            'source_type'=>
                'AiMonitoringLog',


            'source_id'=>
                $log->id,


            'event_date'=>
                now(),


            'decision_status'=>
                'PENDING_REVIEW',


            'reviewed_by'=>
                null,


            'reviewed_at'=>
                null,


            'review_action'=>
                null


        ]);







        return $log;


    }



}