<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\HealthRiskScore;
use App\Models\HealthPrediction;
use App\Models\AiAlert;
use App\Models\VitalSign;
use App\Models\NurseTask;



class CareRecommendationEngine
{


    /*
    |--------------------------------------------------------------------------
    | Generate Personalized Care Recommendation
    |--------------------------------------------------------------------------
    */


    public function generate($residentId)
    {


        $resident = Resident::findOrFail($residentId);



        /*
        |--------------------------------------------------------------------------
        | Retrieve Latest Clinical Data
        |--------------------------------------------------------------------------
        */


        $riskScore = HealthRiskScore::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->first();



        $latestVital = VitalSign::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->first();



        $predictions = HealthPrediction::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->limit(10)
            ->get();



        $alerts = AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'OPEN'
            )
            ->get();



        $completedTasks = NurseTask::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'Completed'
            )
            ->count();




        /*
        |--------------------------------------------------------------------------
        | Recommendation Variables
        |--------------------------------------------------------------------------
        */


        $priority = "LOW";

        $recommendations = [];

        $lifestyle = [];

        $followUp = [];





        /*
        |--------------------------------------------------------------------------
        | Risk Based Recommendation
        |--------------------------------------------------------------------------
        */


        if($riskScore)
        {


            if($riskScore->risk_score >= 80)
            {

                $priority = "CRITICAL";


                $recommendations[] = [

                    "category"=>"Overall Health Risk",

                    "action"=>
                    "Immediate clinical monitoring and physician review required."

                ];

            }


            elseif($riskScore->risk_score >=50)
            {

                $priority = "HIGH";


                $recommendations[] = [

                    "category"=>"Health Monitoring",

                    "action"=>
                    "Increase monitoring frequency and review patient condition."

                ];

            }


        }






        /*
        |--------------------------------------------------------------------------
        | Blood Pressure Recommendation
        |--------------------------------------------------------------------------
        */


        if($latestVital)
        {


            if(
                $latestVital->blood_pressure_systolic >=160 ||
                $latestVital->blood_pressure_diastolic >=100
            )
            {


                $priority = "CRITICAL";


                $recommendations[] = [

                    "category"=>"Blood Pressure Management",

                    "action"=>
                    "Monitor blood pressure regularly and evaluate medication effectiveness."

                ];


            }





            /*
            |--------------------------------------------------------------------------
            | Oxygen Recommendation
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->oxygen_level <92
            )
            {


                $priority="CRITICAL";


                $recommendations[] = [

                    "category"=>"Respiratory Care",

                    "action"=>
                    "Monitor oxygen saturation closely and assess respiratory condition."

                ];


            }





            /*
            |--------------------------------------------------------------------------
            | Diabetes Recommendation
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->blood_glucose >=10
            )
            {


                $recommendations[] = [

                    "category"=>"Diabetes Management",

                    "action"=>
                    "Monitor glucose level and review diabetes management plan."

                ];


            }




            /*
            |--------------------------------------------------------------------------
            | Fever Recommendation
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->temperature >=38
            )
            {


                $recommendations[] = [

                    "category"=>"Infection Monitoring",

                    "action"=>
                    "Monitor temperature and assess possible infection symptoms."

                ];


            }


        }






        /*
        |--------------------------------------------------------------------------
        | Chronic Disease Guidance
        |--------------------------------------------------------------------------
        */


        if($resident->chronic_disease)
        {


            $lifestyle[] =
            "Maintain regular monitoring for existing chronic disease.";

        }






        $lifestyle[] =
        "Maintain balanced diet and appropriate physical activity.";






        /*
        |--------------------------------------------------------------------------
        | Follow Up Plan
        |--------------------------------------------------------------------------
        */


        if($priority=="CRITICAL")
        {


            $followUp = [

                "next_review"=>"Within 24 hours",

                "responsible_team"=>"Nurse and Physician"

            ];


        }

        elseif($priority=="HIGH")
        {


            $followUp = [

                "next_review"=>"Within 3 days",

                "responsible_team"=>"Nursing Team"

            ];


        }

        else
        {


            $followUp = [

                "next_review"=>"Routine schedule",

                "responsible_team"=>"Care Team"

            ];


        }






        return [


            "resident_id"=>$residentId,


            "resident_name"=>$resident->full_name,


            "care_priority"=>$priority,


            "personalized_recommendations"=>$recommendations,


            "lifestyle_guidance"=>$lifestyle,


            "follow_up_plan"=>$followUp,


            "clinical_summary"=>[

                "active_alerts"=>$alerts->count(),

                "predictions"=>$predictions->count(),

                "completed_tasks"=>$completedTasks

            ]


        ];


    }



}