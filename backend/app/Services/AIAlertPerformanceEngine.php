<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\AlertEscalationLog;
use App\Models\NurseTask;
use Carbon\Carbon;



class AIAlertPerformanceEngine
{


    /*
    |--------------------------------------------------------------------------
    | Generate AI Alert Performance Analytics
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {


        /*
        |--------------------------------------------------------------------------
        | Alert Statistics
        |--------------------------------------------------------------------------
        */


        $totalAlerts =
            AiAlert::count();



        $criticalAlerts =
            AiAlert::where(
                'severity',
                'CRITICAL'
            )
            ->count();




        $openAlerts =
            AiAlert::where(
                'status',
                'OPEN'
            )
            ->count();




        $resolvedAlerts =
            AiAlert::where(
                'status',
                'RESOLVED'
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Escalation Statistics
        |--------------------------------------------------------------------------
        */


        $totalEscalated =
            AlertEscalationLog::count();



        $acknowledgedAlerts =
            AlertEscalationLog::whereNotNull(
                'acknowledged_at'
            )
            ->count();



        $resolvedEscalations =
            AlertEscalationLog::where(
                'status',
                'RESOLVED'
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Average Response Time
        |
        | Alert Created
        |        -
        | Acknowledged Time
        |--------------------------------------------------------------------------
        */


        $responseTimes = [];



        $logs =
            AlertEscalationLog::with('alert')
            ->whereNotNull(
                'acknowledged_at'
            )
            ->get();





        foreach($logs as $log)
        {


            if($log->alert)
            {


                $minutes =

                    Carbon::parse(

                        $log->alert->created_at

                    )
                    ->diffInMinutes(

                        Carbon::parse(

                            $log->acknowledged_at

                        )

                    );



                $responseTimes[] = $minutes;


            }


        }





        $averageResponseTime =

            count($responseTimes)

            ?

            round(

                array_sum($responseTimes)
                /
                count($responseTimes),

                2

            )

            :

            0;









        /*
        |--------------------------------------------------------------------------
        | Average Resolution Time
        |
        | Alert Created
        |        -
        | Resolved Time
        |--------------------------------------------------------------------------
        */


        $resolutionTimes = [];



        $resolvedAlertsList =

            AiAlert::where(

                'status',

                'RESOLVED'

            )
            ->whereNotNull(

                'resolved_at'

            )
            ->get();






        foreach($resolvedAlertsList as $alert)
        {


            $minutes =

                Carbon::parse(

                    $alert->created_at

                )
                ->diffInMinutes(

                    Carbon::parse(

                        $alert->resolved_at

                    )

                );



            $resolutionTimes[] = $minutes;


        }







        $averageResolutionTime =

            count($resolutionTimes)

            ?

            round(

                array_sum($resolutionTimes)
                /
                count($resolutionTimes),

                2

            )

            :

            0;









        /*
        |--------------------------------------------------------------------------
        | Nurse Task Performance
        |--------------------------------------------------------------------------
        */


        $totalTasks =

            NurseTask::count();



        $completedTasks =

            NurseTask::where(

                'status',

                'Completed'

            )
            ->count();




        $pendingTasks =

            NurseTask::where(

                'status',

                'Pending'

            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Return Analytics
        |--------------------------------------------------------------------------
        */


        return [


            'alert_summary'=>[


                'total_alerts'=>
                    $totalAlerts,


                'critical_alerts'=>
                    $criticalAlerts,


                'open_alerts'=>
                    $openAlerts,


                'resolved_alerts'=>
                    $resolvedAlerts


            ],





            'escalation_summary'=>[


                'total_escalated'=>
                    $totalEscalated,


                'acknowledged'=>
                    $acknowledgedAlerts,


                'resolved'=>
                    $resolvedEscalations


            ],





            'performance_metrics'=>[


                'average_response_time_minutes'=>
                    $averageResponseTime,


                'average_resolution_time_minutes'=>
                    $averageResolutionTime


            ],





            'nurse_task_summary'=>[


                'total_tasks'=>
                    $totalTasks,


                'completed_tasks'=>
                    $completedTasks,


                'pending_tasks'=>
                    $pendingTasks


            ]



        ];



    }



}