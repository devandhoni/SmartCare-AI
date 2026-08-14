<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\NurseTask;
use App\Models\Notification;
use App\Models\User;
use App\Models\VitalSign;
use App\Models\HealthRiskScore;
use App\Models\ClinicalTimeline;



class DashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Nurse Dashboard
    |--------------------------------------------------------------------------
    */


    public function nurseDashboard()
    {


        /*
        |--------------------------------------------------------------------------
        | Summary Count
        |--------------------------------------------------------------------------
        */


        $totalResidents = Resident::count();



        $criticalAlerts = AiAlert::where(
            'status',
            'OPEN'
        )
        ->where(
            'severity',
            'CRITICAL'
        )
        ->count();



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



        $unreadNotifications = Notification::where(
            'user_id',
            auth()->id()
        )
        ->where(
            'read_status',
            0
        )
        ->count();





        /*
        |--------------------------------------------------------------------------
        | Latest Vital Signs
        |--------------------------------------------------------------------------
        */


        $latestVitals = VitalSign::with(
            'resident'
        )
        ->latest('created_on')
        ->limit(5)
        ->get();






        /*
        |--------------------------------------------------------------------------
        | Risk Distribution
        |--------------------------------------------------------------------------
        */


        $riskSummary = [

            'critical'=>HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )->count(),


            'high'=>HealthRiskScore::where(
                'risk_level',
                'HIGH'
            )->count(),


            'medium'=>HealthRiskScore::where(
                'risk_level',
                'MEDIUM'
            )->count(),


            'low'=>HealthRiskScore::where(
                'risk_level',
                'LOW'
            )->count(),

        ];







        /*
        |--------------------------------------------------------------------------
        | Critical Residents
        |--------------------------------------------------------------------------
        */


        $criticalResidents = HealthRiskScore::with(
            'resident'
        )
        ->where(
            'risk_level',
            'CRITICAL'
        )
        ->orderByDesc(
            'risk_score'
        )
        ->limit(5)
        ->get();







        /*
        |--------------------------------------------------------------------------
        | Recent AI Alerts
        |--------------------------------------------------------------------------
        */


       $recentAlerts = AiAlert::with(
            'resident'
            )
            ->where(
                'status',
                'OPEN'
            )
            ->latest('created_on')
            ->limit(5)
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Urgent Tasks
        |--------------------------------------------------------------------------
        */


        $urgentTasks = NurseTask::with([

        'resident',

        'assignedUser',

        'alert'

            ])
            ->whereIn(

                'status',

                [
                    'Pending',
                    'ACKNOWLEDGED'
                ]

            )
            ->where(function($query){

                $query->where(

                    'ai_generated',

                    true

                )
                ->orWhere(

                    'priority',

                    'URGENT'

                );

            })
            ->orderByRaw("
                CASE
                    WHEN priority='URGENT' THEN 1
                    WHEN priority='HIGH' THEN 2
                    WHEN priority='NORMAL' THEN 3
                    ELSE 4
                END
            ")
            ->latest('created_on')
            ->limit(5)
            ->get();



        /*
|--------------------------------------------------------------------------
| AI Clinical Decision Monitoring
|--------------------------------------------------------------------------
*/


$aiClinicalDecisions = NurseTask::where(
        'resident_id',
        '>',
        0
    )
    ->where(
        'ai_generated',
        true
    )
    ->whereNotNull(
        'clinical_action_plan'
    )
    ->latest('created_on')
    ->limit(10)
    ->get();





/*
|--------------------------------------------------------------------------
| AI Nurse Task Monitoring
|--------------------------------------------------------------------------
*/


$aiNurseTasks = NurseTask::with(
        'resident'
    )
    ->where(
        'ai_generated',
        true
    )
    ->whereIn(
        'status',
        [
            'Pending',
            'ACKNOWLEDGED'
        ]
    )
    ->latest('created_on')
    ->limit(10)
    ->get();



    $aiSummary = [

    'critical_ai_tasks'=>NurseTask::where(
        'ai_generated',
        true
    )
    ->where(
        'priority',
        'URGENT'
    )
    ->whereIn(
        'status',
        [
            'Pending',
            'ACKNOWLEDGED'
        ]
    )
    ->count(),


    'pending_ai_review'=>NurseTask::where(
        'ai_generated',
        true
    )
    ->where(
        'status',
        'Pending'
    )
    ->count(),

];


        return response()->json([


            'dashboard'=>'Nurse Dashboard',



            'summary'=>[

                'total_residents'=>$totalResidents,

                'critical_alerts'=>$criticalAlerts,

                'pending_tasks'=>$pendingTasks,

                'completed_tasks'=>$completedTasks,

                'unread_notifications'=>$unreadNotifications

            ],




            'risk_summary'=>$riskSummary,



            'critical_residents'=>$criticalResidents,



            'recent_alerts'=>$recentAlerts,



            'urgent_tasks'=>$urgentTasks,


            'ai_clinical_decisions'=>$aiClinicalDecisions,


            'ai_nurse_tasks'=>$aiNurseTasks,


            'ai_summary'=>$aiSummary,


            'latest_vitals'=>$latestVitals



        ]);


            }




    


    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */



    public function adminDashboard()
    {


        $totalUsers = User::count();



        $totalResidents = Resident::count();



        $activeAlerts = AiAlert::where(
            'status',
            'OPEN'
        )
        ->count();




        $systemNotifications = Notification::count();





        /*
        |--------------------------------------------------------------------------
        | Alert Statistics
        |--------------------------------------------------------------------------
        */


        $alertStatistics = [

            'open'=>AiAlert::where(
                'status',
                'OPEN'
            )->count(),


            'resolved'=>AiAlert::where(
                'status',
                'RESOLVED'
            )->count(),

        ];








        /*
        |--------------------------------------------------------------------------
        | Resident Risk Distribution
        |--------------------------------------------------------------------------
        */


        $residentRiskDistribution = [


            'critical'=>HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )->count(),


            'high'=>HealthRiskScore::where(
                'risk_level',
                'HIGH'
            )->count(),


            'medium'=>HealthRiskScore::where(
                'risk_level',
                'MEDIUM'
            )->count(),


            'low'=>HealthRiskScore::where(
                'risk_level',
                'LOW'
            )->count(),

        ];









        return response()->json([



            'dashboard'=>'Admin Dashboard',



            'summary'=>[


                'total_users'=>$totalUsers,


                'total_residents'=>$totalResidents,


                'active_alerts'=>$activeAlerts,


                'system_notifications'=>$systemNotifications


            ],





            'alert_statistics'=>$alertStatistics,



            'resident_risk_distribution'=>$residentRiskDistribution



        ]);



    }



}