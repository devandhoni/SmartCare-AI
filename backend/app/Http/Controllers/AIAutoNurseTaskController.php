<?php

namespace App\Http\Controllers;


use App\Services\AIAutoNurseTaskService;
use App\Services\ClinicalDecisionEngine;



class AIAutoNurseTaskController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Generate Automatic Nurse Tasks
    |--------------------------------------------------------------------------
    */


    public function generate(

        $id,

        AIAutoNurseTaskService $service,

        ClinicalDecisionEngine $clinicalDecisionEngine

    )
    {


        /*
        |--------------------------------------------------------------------------
        | Generate AI Clinical Decision
        |--------------------------------------------------------------------------
        */


        $decision =

            $clinicalDecisionEngine
            ->analyze($id);







        /*
        |--------------------------------------------------------------------------
        | Generate Nurse Task With Clinical Action Plan
        |--------------------------------------------------------------------------
        */


        $tasks =

            $service
            ->generate(

                $id,

                $decision

            );







        return response()->json([


            "resident_id"=>

                $id,


            "clinical_decision"=>

                $decision,



            "generated_tasks"=>

                $tasks



        ]);



    }



}