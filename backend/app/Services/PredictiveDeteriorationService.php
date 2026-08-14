<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\ClinicalTimeline;
use App\Models\VitalSign;
use App\Models\MedicationAdministrationRecord;



class PredictiveDeteriorationService
{


    /*
    |--------------------------------------------------------------------------
    | Predict Resident Deterioration Risk
    |--------------------------------------------------------------------------
    */


    public function predict($residentId)
    {


        $riskScore = 0;


        $warningSigns = [];


        $recommendations = [];





        /*
        |--------------------------------------------------------------------------
        | 1. Vital Sign Trend Analysis
        |--------------------------------------------------------------------------
        */


        $criticalVitals =

            VitalSign::where(

                'resident_id',

                $residentId

            )
            ->where(function($query){

                $query

                ->where(
                    'blood_pressure_systolic',
                    '>=',
                    160
                )

                ->orWhere(
                    'blood_pressure_diastolic',
                    '>=',
                    100
                )

                ->orWhere(
                    'oxygen_level',
                    '<',
                    92
                )

                ->orWhere(
                    'temperature',
                    '>=',
                    38
                )

                ->orWhere(
                    'blood_glucose',
                    '>=',
                    11
                );

            })
            ->count();





        if($criticalVitals > 0)
        {


            $riskScore += 40;


            $warningSigns[] =

                $criticalVitals .
                " abnormal vital sign record(s) detected";



            $recommendations[] =

                "Repeat vital signs monitoring regularly.";


        }








        /*
        |--------------------------------------------------------------------------
        | 2. Active Critical AI Alerts
        |--------------------------------------------------------------------------
        */


        $criticalAlerts =

            AiAlert::where(

                'resident_id',

                $residentId

            )
            ->where(

                'severity',

                'CRITICAL'

            )
            ->where(

                'status',

                'OPEN'

            )
            ->count();





        if($criticalAlerts > 0)
        {


            $riskScore += 35;


            $warningSigns[] =

                $criticalAlerts .
                " unresolved critical AI alert(s)";



            $recommendations[] =

                "Immediate clinical assessment recommended.";


        }







        /*
        |--------------------------------------------------------------------------
        | 3. Medication Behaviour Analysis
        |--------------------------------------------------------------------------
        */


        $delayedMedication =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->where(

                'event_type',

                'MEDICATION_DELAYED'

            )
            ->where(

                'created_at',

                '>=',

                now()->subDays(7)

            )
            ->count();







        if($delayedMedication > 0)
        {


            $riskScore += 15;


            $warningSigns[] =

                $delayedMedication .
                " medication delay event(s) detected";



            $recommendations[] =

                "Review medication adherence.";


        }







        /*
        |--------------------------------------------------------------------------
        | 4. Historical Clinical Event Pattern
        |--------------------------------------------------------------------------
        */


        $clinicalEvents =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->where(

                'event_type',

                'AI_ALERT'

            )
            ->where(

                'created_at',

                '>=',

                now()->subDays(30)

            )
            ->count();







        if($clinicalEvents >= 3)
        {


            $riskScore += 10;


            $warningSigns[] =

                "Frequent AI clinical events detected within 30 days";


        }









        /*
        |--------------------------------------------------------------------------
        | Prediction Classification
        |--------------------------------------------------------------------------
        */


        if($riskScore >=80)
        {


            $predictionRisk = "CRITICAL";


        }
        elseif($riskScore >=60)
        {


            $predictionRisk = "HIGH";


        }
        elseif($riskScore >=30)
        {


            $predictionRisk = "MEDIUM";


        }
        else
        {


            $predictionRisk = "LOW";


        }









        return [


            "deterioration_risk"=>

                $predictionRisk,



            "risk_score"=>

                min($riskScore,100),




            "prediction_window"=>

                "Next 24-48 hours",




            "warning_signs"=>

                $warningSigns,




            "recommended_monitoring"=>

                $recommendations



        ];



    }



}