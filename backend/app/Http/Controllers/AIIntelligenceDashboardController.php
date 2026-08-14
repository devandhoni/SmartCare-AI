<?php

namespace App\Http\Controllers;


use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\NurseTask;
use App\Models\Notification;
use App\Models\HealthRiskScore;
use App\Models\ActivityLog;
use App\Helpers\ApiResponse;



class AIIntelligenceDashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | SmartCare AI Intelligence Dashboard
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        /*
        |--------------------------------------------------------------------------
        | System Summary
        |--------------------------------------------------------------------------
        */


        $totalResidents =
            Resident::count();



        $criticalCases =
            HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )
            ->count();





        $activeAlerts =
            AiAlert::where(
                'status',
                'OPEN'
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
        | Critical Residents
        |--------------------------------------------------------------------------
        */


        $criticalResidents =
            HealthRiskScore::with(
                'resident'
            )
            ->where(
                'risk_level',
                'CRITICAL'
            )
            ->latest()
            ->limit(5)
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Recent AI Activities
        |--------------------------------------------------------------------------
        */


        $recentActivities =
            ActivityLog::latest()
            ->limit(10)
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Notification Summary
        |--------------------------------------------------------------------------
        */


        $unreadNotifications =
            Notification::where(
                'read_status',
                0
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Intelligence Dashboard Data
        |--------------------------------------------------------------------------
        */


        $dashboard = [


            'system_status'=>'ACTIVE',



            'summary'=>[


                'total_residents'=>
                $totalResidents,


                'critical_cases'=>
                $criticalCases,


                'active_alerts'=>
                $activeAlerts,


                'pending_tasks'=>
                $pendingTasks,


                'unread_notifications'=>
                $unreadNotifications


            ],





            'critical_residents'=>
            $criticalResidents,





            'recent_ai_activity'=>
            $recentActivities



        ];








        return ApiResponse::success(

            'AI intelligence dashboard generated successfully',

            $dashboard

        );



    }



}