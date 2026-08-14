<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\NurseTask;
use App\Models\AiAlert;
use App\Models\Notification;
use App\Models\Resident;

use App\Services\NurseDashboardService;
use App\Services\ClinicalDecisionEngine;



class NurseDashboardController extends Controller
{


    protected $dashboardService;


    protected $clinicalDecisionEngine;




    public function __construct(
        NurseDashboardService $dashboardService,
        ClinicalDecisionEngine $clinicalDecisionEngine
    )
    {

        $this->dashboardService =
            $dashboardService;


        $this->clinicalDecisionEngine =
            $clinicalDecisionEngine;

    }







    /*
    |--------------------------------------------------------------------------
    | Main Nurse Dashboard
    |--------------------------------------------------------------------------
    */


    public function dashboard(Request $request)
    {


        $user =
            $request->user();





        /*
        |--------------------------------------------------------------------------
        | Clinical Intelligence
        |--------------------------------------------------------------------------
        */


        $priorityResidents = [];



        $residents =
            Resident::where(
                'status',
                'Active'
            )
            ->get();





        foreach($residents as $resident)
        {


            $clinicalDecision =
                $this->clinicalDecisionEngine
                ->analyze(
                    $resident->id
                );





            if(
                in_array(
                    $clinicalDecision['priority'],
                    [
                        'CRITICAL',
                        'HIGH'
                    ]
                )
            )
            {


                $priorityResidents[] = [


                    "resident_id" =>
                        $resident->id,



                    "resident_name" =>
                        $resident->full_name,



                    "priority" =>
                        $clinicalDecision['priority'],



                    "recommended_action" =>
                        $clinicalDecision['recommended_action'],



                    "clinical_action_plan" =>
                        $clinicalDecision['clinical_action_plan']



                ];


            }



        }









        return response()->json([



            "nurse"=>[


                "id"=>$user->id,


                "name"=>$user->name


            ],






            "pending_tasks" => NurseTask::where(

                'assigned_to',

                $user->id

            )
            ->where(

                'status',

                'Pending'

            )
            ->get(),







            "critical_alerts" => AiAlert::where(

                'status',

                'OPEN'

            )
            ->where(

                'severity',

                'CRITICAL'

            )
            ->get(),








            "notifications" => Notification::where(

                'user_id',

                $user->id

            )
            ->where(

                'read_status',

                0

            )
            ->get(),








            "resident_count" => Resident::count(),






            "clinical_intelligence"=>[


                "priority_residents" =>
                    $priorityResidents


            ]




        ]);



    }











    /*
    |--------------------------------------------------------------------------
    | Resident Medication Intelligence Dashboard
    |--------------------------------------------------------------------------
    */


    public function medicationDashboard($id)
    {


        $dashboard =

            $this->dashboardService

            ->getDashboard($id);



        return response()->json(

            $dashboard

        );


    }



    /*
    |--------------------------------------------------------------------------
    | Individual Resident Clinical Dashboard
    |--------------------------------------------------------------------------
    */

    public function residentDashboard(
        $residentId
    )
    {


        $dashboard =
            $this->dashboardService
            ->getDashboard($residentId);



        return response()->json([


            "dashboard" =>
                "Resident Clinical Dashboard",


            "data" =>
                $dashboard


        ]);


    }



}