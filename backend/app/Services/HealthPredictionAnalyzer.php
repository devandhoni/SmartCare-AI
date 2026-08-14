<?php

namespace App\Services;


use App\Models\HealthPrediction;


class HealthPredictionAnalyzer
{


    public function analyze($vital)
    {


        $predictions = [];



        /*
        |--------------------------------------------------------------------------
        | Blood Pressure Prediction
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_pressure_systolic >= 160 ||
            $vital->blood_pressure_diastolic >= 100
        )
        {

            $predictions[] = [

                'type'=>'Hypertension Risk',

                'risk_level'=>'HIGH',

                'prediction'=>
                'Blood pressure readings indicate high risk of hypertension complications.',

                'confidence'=>90.00

            ];

        }





        /*
        |--------------------------------------------------------------------------
        | Oxygen Prediction
        |--------------------------------------------------------------------------
        */


        if(
            $vital->oxygen_level < 92
        )
        {

            $predictions[] = [

                'type'=>'Respiratory Risk',

                'risk_level'=>'CRITICAL',

                'prediction'=>
                'Low oxygen saturation indicates possible respiratory deterioration.',

                'confidence'=>94.00

            ];

        }





        /*
        |--------------------------------------------------------------------------
        | Blood Glucose Prediction
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_glucose >= 10
        )
        {

            $predictions[] = [

                'type'=>'Diabetes Risk',

                'risk_level'=>'HIGH',

                'prediction'=>
                'High glucose level indicates possible uncontrolled diabetes condition.',

                'confidence'=>88.00

            ];

        }





        /*
        |--------------------------------------------------------------------------
        | Temperature Prediction
        |--------------------------------------------------------------------------
        */


        if(
            $vital->temperature >= 38
        )
        {

            $predictions[] = [

                'type'=>'Infection Risk',

                'risk_level'=>'HIGH',

                'prediction'=>
                'Elevated temperature may indicate possible infection or inflammation.',

                'confidence'=>85.00

            ];

        }





        /*
        |--------------------------------------------------------------------------
        | Save / Update AI Predictions
        |--------------------------------------------------------------------------
        |
        | Prevent duplicate predictions.
        |
        | Same resident + same prediction type
        | will update existing record instead of creating a new one.
        |
        |--------------------------------------------------------------------------
        */


        foreach($predictions as $prediction)
        {


            HealthPrediction::updateOrCreate(

                [

                    'resident_id'=>$vital->resident_id,

                    'prediction_type'=>$prediction['type']

                ],


                [

                    'risk_level'=>$prediction['risk_level'],

                    'prediction'=>$prediction['prediction'],

                    'confidence'=>$prediction['confidence']

                ]

            );


        }





        /*
        |--------------------------------------------------------------------------
        | Return AI Prediction Result
        |--------------------------------------------------------------------------
        */


        return $predictions;


    }


}