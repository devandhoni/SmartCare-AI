<?php

namespace App\Services;


use App\Models\HealthRiskScore;
use App\Models\AiAlert;



class HealthRiskAnalyzer
{


    public function calculate($vital)
    {


        $score = 0;


        $reasons = [];



        /*
        |--------------------------------------------------------------------------
        | Blood Pressure Risk
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_pressure_systolic >= 160 ||
            $vital->blood_pressure_diastolic >= 100
        )
        {

            $score += 30;

            $reasons[] = "High Blood Pressure";

        }




        /*
        |--------------------------------------------------------------------------
        | Oxygen Risk
        |--------------------------------------------------------------------------
        */


        if(
            $vital->oxygen_level < 92
        )
        {

            $score += 25;

            $reasons[] = "Low Oxygen Level";

        }




        /*
        |--------------------------------------------------------------------------
        | Temperature Risk
        |--------------------------------------------------------------------------
        */


        if(
            $vital->temperature >= 38
        )
        {

            $score += 15;

            $reasons[] = "High Temperature";

        }




        /*
        |--------------------------------------------------------------------------
        | Blood Glucose Risk
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_glucose >= 10
        )
        {

            $score += 15;

            $reasons[] = "High Blood Glucose";

        }




        /*
        |--------------------------------------------------------------------------
        | Existing AI Alerts
        |--------------------------------------------------------------------------
        */


        $alertCount = AiAlert::where(
            'resident_id',
            $vital->resident_id
        )
        ->where(
            'status',
            'OPEN'
        )
        ->count();



        if($alertCount > 0)
        {

            $score += 15;

            $reasons[] = "Active AI Health Alerts";

        }




        /*
        |--------------------------------------------------------------------------
        | Determine Risk Level
        |--------------------------------------------------------------------------
        */


        if($score >= 81)
        {

            $level = "CRITICAL";

        }
        elseif($score >= 61)
        {

            $level = "HIGH";

        }
        elseif($score >= 31)
        {

            $level = "MEDIUM";

        }
        else
        {

            $level = "LOW";

        }





        /*
        |--------------------------------------------------------------------------
        | Save Health Risk Score
        |--------------------------------------------------------------------------
        */


        HealthRiskScore::updateOrCreate(

            [
                'resident_id'=>$vital->resident_id
            ],


            [

                'risk_score'=>$score,

                'risk_level'=>$level,

                'reason'=>implode(
                    ', ',
                    $reasons
                )

            ]

        );



        return [

            'score'=>$score,

            'level'=>$level,

            'reason'=>$reasons

        ];



    }



}