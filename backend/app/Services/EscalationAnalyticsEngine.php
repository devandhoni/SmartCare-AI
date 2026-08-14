<?php

namespace App\Services;


use App\Models\AlertEscalationLog;
use Carbon\Carbon;



class EscalationAnalyticsEngine
{


    /*
    |--------------------------------------------------------------------------
    | Generate Escalation Analytics
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {


        $logs = AlertEscalationLog::latest()
            ->get();



        $results = [];




        foreach($logs as $log)
        {


            $responseTime = null;

            $resolutionTime = null;



            /*
            |--------------------------------------------------------------------------
            | Calculate Acknowledge Response Time
            |--------------------------------------------------------------------------
            */


            if(
                $log->escalated_at &&
                $log->acknowledged_at
            )
            {


                $responseTime =
                Carbon::parse($log->escalated_at)
                ->diffInMinutes(
                    Carbon::parse($log->acknowledged_at)
                );


            }






            /*
            |--------------------------------------------------------------------------
            | Calculate Resolution Time
            |--------------------------------------------------------------------------
            */


            if(
                $log->acknowledged_at &&
                $log->resolved_at
            )
            {


                $resolutionTime =
                Carbon::parse($log->acknowledged_at)
                ->diffInMinutes(
                    Carbon::parse($log->resolved_at)
                );


            }





            $results[]=[


                'alert_id'=>$log->alert_id,


                'resident_id'=>$log->resident_id,


                'priority'=>$log->priority,


                'status'=>$log->status,


                'response_time_minutes'=>$responseTime,


                'resolution_time_minutes'=>$resolutionTime,


                'sla_status'=>

                    $this->calculateSLA(
                        $log->priority,
                        $responseTime
                    )


            ];



        }






        return [


            'total_escalations'=>$logs->count(),


            'analytics'=>$results


        ];



    }









    /*
    |--------------------------------------------------------------------------
    | SLA Calculation
    |--------------------------------------------------------------------------
    */


    private function calculateSLA(
        $priority,
        $responseTime
    )
    {



        if($responseTime === null)
        {

            return "PENDING";

        }





        if($priority=="CRITICAL")
        {


            return $responseTime <= 15

                ? "WITHIN SLA"

                : "SLA BREACH";


        }





        if($priority=="HIGH")
        {


            return $responseTime <= 60

                ? "WITHIN SLA"

                : "SLA BREACH";


        }





        return "WITHIN SLA";


    }



}