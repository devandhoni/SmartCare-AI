<?php

namespace App\Services;


class ClinicalActionPlanGenerator
{


    /*
    |--------------------------------------------------------------------------
    | Generate Clinical Action Plan
    |--------------------------------------------------------------------------
    */


    public function generate(
        $priority,
        $reasons,
        $latestVital = null,
        $healthTrend = null
    )
    {


        $nurseActions = [];

        $doctorActions = [];

        $monitoringFrequency = "Routine monitoring";

        $followUpRequired = false;






        /*
        |--------------------------------------------------------------------------
        | Critical Priority Actions
        |--------------------------------------------------------------------------
        */


        if($priority == "CRITICAL")
        {


            $nurseActions[] =
            "Immediate nurse monitoring required.";


            $nurseActions[] =
            "Repeat vital sign measurement.";


            $doctorActions[] =
            "Physician assessment required.";


            $monitoringFrequency =
            "Every 30 minutes";


            $followUpRequired = true;


        }







        /*
        |--------------------------------------------------------------------------
        | High Priority Actions
        |--------------------------------------------------------------------------
        */


        elseif($priority == "HIGH")
        {


            $nurseActions[] =
            "Increase monitoring frequency.";


            $nurseActions[] =
            "Review abnormal vital signs.";


            $doctorActions[] =
            "Clinical review recommended.";


            $monitoringFrequency =
            "Every 2 hours";


            $followUpRequired = true;


        }







        /*
        |--------------------------------------------------------------------------
        | Low Priority Actions
        |--------------------------------------------------------------------------
        */


        else
        {


            $nurseActions[] =
            "Continue routine observation.";


            $monitoringFrequency =
            "Every shift";


        }









        /*
        |--------------------------------------------------------------------------
        | Vital Specific Actions
        |--------------------------------------------------------------------------
        */


        if($latestVital)
        {


            if(
                $latestVital->blood_pressure_systolic >=160
            )
            {


                $nurseActions[] =
                "Monitor blood pressure closely.";


                $doctorActions[] =
                "Review hypertension management plan.";

            }







            if(
                $latestVital->oxygen_level <92
            )
            {


                $nurseActions[] =
                "Monitor oxygen saturation continuously.";


                $doctorActions[] =
                "Assess respiratory condition.";

            }







            if(
                $latestVital->blood_glucose >=10
            )
            {


                $nurseActions[] =
                "Monitor blood glucose level.";


                $doctorActions[] =
                "Review diabetes management.";

            }







            if(
                $latestVital->temperature >=38
            )
            {


                $nurseActions[] =
                "Monitor body temperature.";


                $doctorActions[] =
                "Assess possible infection.";

            }


        }









        /*
        |--------------------------------------------------------------------------
        | Health Trend Actions
        |--------------------------------------------------------------------------
        */


        if(
            isset($healthTrend['trend_status'])
        )
        {


            if(
                $healthTrend['trend_status']=="WORSENING"
            )
            {


                $nurseActions[] =
                "Increase observation due to worsening health trend.";


                $doctorActions[] =
                "Review patient deterioration risk.";


                $followUpRequired = true;


            }



            elseif(
                $healthTrend['trend_status']=="IMPROVING"
            )
            {


                $nurseActions[] =
                "Continue current treatment monitoring.";

            }


        }









        /*
        |--------------------------------------------------------------------------
        | Return Action Plan
        |--------------------------------------------------------------------------
        */


        return [


            "priority"=>$priority,


            "nurse_actions"=>array_unique($nurseActions),


            "doctor_actions"=>array_unique($doctorActions),


            "monitoring_frequency"=>$monitoringFrequency,


            "follow_up_required"=>$followUpRequired,


            "reason_summary"=>$reasons


        ];



    }



}