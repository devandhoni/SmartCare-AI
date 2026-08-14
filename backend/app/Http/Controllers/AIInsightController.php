<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\Resident;
use App\Models\HealthRiskScore;
use App\Models\HealthPrediction;
use App\Models\AiAlert;
use App\Models\NurseTask;



class AIInsightController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Resident AI Health Insight
    |--------------------------------------------------------------------------
    */


    public function residentInsight($id)
    {


        $resident = Resident::findOrFail($id);



        /*
        |--------------------------------------------------------------------------
        | Latest Risk Score
        |--------------------------------------------------------------------------
        */


        $riskScore = HealthRiskScore::where(
            'resident_id',
            $id
        )
        ->latest('created_on')
        ->first();





        /*
        |--------------------------------------------------------------------------
        | Active AI Alerts
        |--------------------------------------------------------------------------
        */


        $activeAlerts = AiAlert::where(
            'resident_id',
            $id
        )
        ->where(
            'status',
            'OPEN'
        )
        ->latest('created_on')
        ->get();






        /*
        |--------------------------------------------------------------------------
        | Latest AI Predictions
        |--------------------------------------------------------------------------
        */


        $predictions = HealthPrediction::where(
            'resident_id',
            $id
        )
        ->latest('created_on')
        ->limit(5)
        ->get();






        /*
        |--------------------------------------------------------------------------
        | Pending Nurse Tasks
        |--------------------------------------------------------------------------
        */


        $tasks = NurseTask::where(
            'resident_id',
            $id
        )
        ->where(
            'status',
            'Pending'
        )
        ->latest('created_on')
        ->get();







        /*
        |--------------------------------------------------------------------------
        | Generate AI Recommendation
        |--------------------------------------------------------------------------
        */


        $recommendations = [];



        if($riskScore)
        {


            if($riskScore->risk_level == 'CRITICAL')
            {

                $recommendations[] =
                "Immediate nurse monitoring required.";


                $recommendations[] =
                "Consider physician assessment.";

            }


            elseif($riskScore->risk_level == 'HIGH')
            {

                $recommendations[] =
                "Increase monitoring frequency.";

            }


        }





        if(
            $activeAlerts->where(
                'alert_type',
                'Low Oxygen Level'
            )->count() > 0
        )
        {

            $recommendations[] =
            "Monitor oxygen saturation closely.";

        }






        return response()->json([


            "resident"=>[

                "id"=>$resident->id,

                "name"=>$resident->full_name

            ],



            "health_status"=>

                $riskScore
                ?
                $riskScore->risk_level
                :
                "UNKNOWN",




            "risk_score"=>

                $riskScore
                ?
                $riskScore->risk_score
                :
                0,




            "risk_level"=>

                $riskScore
                ?
                $riskScore->risk_level
                :
                "LOW",




            "ai_summary"=>

                $riskScore
                ?
                $riskScore->reason
                :
                "No AI health analysis available.",




            "active_alert_count"=>
                $activeAlerts->count(),




            "active_alerts"=>
                $activeAlerts,




            "latest_predictions"=>
                $predictions,




            "pending_tasks"=>
                $tasks,




            "recommendations"=>
                $recommendations



        ]);



    }


}