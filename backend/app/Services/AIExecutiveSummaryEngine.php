<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\HealthRiskScore;
use Carbon\Carbon;



class AIExecutiveSummaryEngine
{


    /*
    |--------------------------------------------------------------------------
    | Generate AI Executive Summary
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {


        /*
        |--------------------------------------------------------------------------
        | Basic Statistics
        |--------------------------------------------------------------------------
        */


        $totalResidents = Resident::count();



        $criticalCases = HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )
            ->distinct('resident_id')
            ->count('resident_id');




        $activeAlerts = AiAlert::where(
                'status',
                'OPEN'
            )
            ->count();





        $totalPredictions = HealthPrediction::count();






        /*
        |--------------------------------------------------------------------------
        | Generate Key Findings
        |--------------------------------------------------------------------------
        */


        $findings = [];




        if($criticalCases > 0)
        {


            $findings[] =
            $criticalCases .
            " critical resident case(s) require monitoring.";


        }





        if($activeAlerts > 0)
        {


            $findings[] =
            $activeAlerts .
            " active AI alert(s) require attention.";


        }





        if($totalPredictions > 0)
        {


            $findings[] =
            "AI generated "
            .
            $totalPredictions
            .
            " clinical prediction(s) for risk assessment.";


        }









        /*
        |--------------------------------------------------------------------------
        | Priority Actions
        |--------------------------------------------------------------------------
        */


        $priorityActions = [];




        $criticalResidents =
            HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )
            ->with('resident')
            ->get();





        foreach($criticalResidents as $risk)
        {


            if($risk->resident)
            {


                $priorityActions[] =
                "Review "
                .
                $risk->resident->full_name
                .
                "'s critical health condition.";


            }


        }





        if(empty($priorityActions))
        {


            $priorityActions[] =
            "Continue routine resident monitoring.";


        }









        /*
        |--------------------------------------------------------------------------
        | Overall Status
        |--------------------------------------------------------------------------
        */


        if($criticalCases > 0 || $activeAlerts > 0)
        {


            $overallStatus =
            "ATTENTION REQUIRED";


        }
        else
        {


            $overallStatus =
            "STABLE";


        }









        /*
        |--------------------------------------------------------------------------
        | Executive Message
        |--------------------------------------------------------------------------
        */


        $message =
        "SmartCare AI monitored "
        .
        $totalResidents
        .
        " resident(s). ";



        if($criticalCases > 0)
        {


            $message .=
            $criticalCases
            .
            " critical case(s) detected requiring clinical attention. ";


        }
        else
        {


            $message .=
            "No critical cases detected. ";


        }





        if($activeAlerts > 0)
        {


            $message .=
            $activeAlerts
            .
            " active alert(s) are currently being monitored.";


        }









        return [



            'summary_date'=>
            Carbon::now()->format('Y-m-d'),





            'executive_message'=>
            $message,





            'key_findings'=>
            $findings,





            'priority_actions'=>
            $priorityActions,





            'overall_status'=>
            $overallStatus



        ];



    }



}