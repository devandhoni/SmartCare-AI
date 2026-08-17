<?php

namespace App\Services;


use App\Models\AiMonitoringLog;



class AIMonitoringAnalyzer
{


    /*
    |--------------------------------------------------------------------------
    | Analyze AI Monitoring History
    |--------------------------------------------------------------------------
    */


    public function analyze($residentId)
    {


        $logs =

            AiMonitoringLog::where(
                'resident_id',
                $residentId
            )
            ->latest()
            ->limit(5)
            ->get()
            ->reverse();




        $analysis = [];


        $status = "STABLE";


        $recommendation =
            "Continue routine monitoring.";





        if($logs->count() < 2)
        {


            return [


                'resident_id'=>
                    $residentId,


                'monitoring_status'=>
                    "INSUFFICIENT_DATA",


                'analysis'=>[
                    "Not enough AI monitoring history available."
                ],


                'recommendation'=>
                    "Continue collecting clinical data."


            ];


        }





        $previous =
            $logs->first();


        $current =
            $logs->last();





        $scoreChange =

            $current->decision_score
            -
            $previous->decision_score;







        /*
        |--------------------------------------------------------------------------
        | Detect Risk Increase
        |--------------------------------------------------------------------------
        */


        if($scoreChange > 0)
        {


            $status =
                "WORSENING";


            $analysis[] =
                "AI clinical risk score increased by "
                .$scoreChange
                ." points.";


            $recommendation =
                "Increase monitoring frequency.";


        }





        elseif($scoreChange < 0)
        {


            $status =
                "IMPROVING";


            $analysis[] =
                "AI clinical risk score decreased by "
                .abs($scoreChange)
                ." points.";


            $recommendation =
                "Continue current care plan.";


        }





        else
        {


            $status =
                "STABLE";


            $analysis[] =
                "AI clinical risk score remains unchanged.";


        }








        /*
        |--------------------------------------------------------------------------
        | Priority Change
        |--------------------------------------------------------------------------
        */


        if(
            $previous->priority !==
            $current->priority
        )
        {


            $analysis[] =

                "Priority changed from "
                .$previous->priority
                ." to "
                .$current->priority;


        }







        return [


            'resident_id'=>
                $residentId,


            'monitoring_status'=>
                $status,


            'current_score'=>
                $current->decision_score,


            'previous_score'=>
                $previous->decision_score,


            'score_change'=>
                $scoreChange,


            'current_priority'=>
                $current->priority,


            'previous_priority'=>
                $previous->priority,


            'analysis'=>
                $analysis,


            'recommendation'=>
                $recommendation


        ];



    }



}