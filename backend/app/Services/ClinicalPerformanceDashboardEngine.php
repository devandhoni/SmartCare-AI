<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\HealthRiskScore;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\NurseTask;
use App\Models\AlertEscalationLog;



class ClinicalPerformanceDashboardEngine
{


    /*
    |--------------------------------------------------------------------------
    | Generate Clinical Performance Dashboard
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {


        /*
        |--------------------------------------------------------------------------
        | Resident Statistics
        |--------------------------------------------------------------------------
        */


        $totalResidents = Resident::count();



        /*
        |--------------------------------------------------------------------------
        | Critical Resident Count
        |--------------------------------------------------------------------------
        */


        $criticalResidents = HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )
            ->distinct('resident_id')
            ->count('resident_id');








        /*
        |--------------------------------------------------------------------------
        | AI Alert Metrics
        |--------------------------------------------------------------------------
        */


        $activeAlerts = AiAlert::where(
                'status',
                'OPEN'
            )
            ->count();








        /*
        |--------------------------------------------------------------------------
        | AI Prediction Metrics
        |--------------------------------------------------------------------------
        */


        $totalPredictions = HealthPrediction::count();



        $highRiskPredictions = HealthPrediction::whereIn(
                'risk_level',
                [
                    'HIGH',
                    'CRITICAL'
                ]
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Escalation Performance
        |--------------------------------------------------------------------------
        */


        $escalations = AlertEscalationLog::all();



        $responseTimes = [];

        $resolutionTimes = [];




        foreach($escalations as $log)
        {


            /*
            |--------------------------------------------------------------------------
            | Response Time
            |--------------------------------------------------------------------------
            */


            if(
                $log->escalated_at &&
                $log->acknowledged_at
            )
            {


                $responseTimes[] =
                \Carbon\Carbon::parse(
                    $log->escalated_at
                )
                ->diffInMinutes(
                    \Carbon\Carbon::parse(
                        $log->acknowledged_at
                    )
                );


            }





            /*
            |--------------------------------------------------------------------------
            | Resolution Time
            |--------------------------------------------------------------------------
            */


            if(
                $log->acknowledged_at &&
                $log->resolved_at
            )
            {


                $resolutionTimes[] =
                \Carbon\Carbon::parse(
                    $log->acknowledged_at
                )
                ->diffInMinutes(
                    \Carbon\Carbon::parse(
                        $log->resolved_at
                    )
                );


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
        | SLA Performance
        |--------------------------------------------------------------------------
        */


        $totalEscalations =
            AlertEscalationLog::count();



        $withinSLA =
            AlertEscalationLog::where(
                'status',
                'RESOLVED'
            )
            ->count();





        $slaPercentage =
            $totalEscalations
            ?
            round(
                ($withinSLA / $totalEscalations) * 100,
                2
            )
            :
            0;









        /*
        |--------------------------------------------------------------------------
        | Nurse Performance
        |--------------------------------------------------------------------------
        */


        $pendingTasks = NurseTask::where(
                'status',
                'Pending'
            )
            ->count();



        $completedTasks = NurseTask::where(
                'status',
                'Completed'
            )
            ->count();










        /*
        |--------------------------------------------------------------------------
        | Dashboard Response
        |--------------------------------------------------------------------------
        */


        return [



            'system_status'=>'ACTIVE',





            'clinical_summary'=>[


                'total_residents'=>$totalResidents,


                'critical_cases'=>$criticalResidents,


                'active_alerts'=>$activeAlerts


            ],







            'ai_metrics'=>[


                'predictions_generated'=>$totalPredictions,


                'high_risk_predictions'=>$highRiskPredictions


            ],







            'escalation_metrics'=>[


                'total_escalations'=>$totalEscalations,


                'average_response_time_minutes'=>$averageResponseTime,


                'average_resolution_time_minutes'=>$averageResolutionTime,


                'sla_compliance_percentage'=>$slaPercentage


            ],







            'nursing_metrics'=>[


                'pending_tasks'=>$pendingTasks,


                'completed_tasks'=>$completedTasks


            ]



        ];



    }



}