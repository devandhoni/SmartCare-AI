<?php

namespace App\Services;


use App\Models\ClinicalRecommendation;
use Carbon\Carbon;



class ClinicalRecommendationAnalyzer
{


    public function analyze($vital)
    {


        $recommendations = [];




        /*
        |--------------------------------------------------------------------------
        | Blood Pressure Recommendation
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_pressure_systolic >= 160 ||
            $vital->blood_pressure_diastolic >= 100
        )
        {


            $recommendations[] = [


                'type'=>'Blood Pressure Management',


                'priority'=>'CRITICAL',


                'recommendation'=>
                'Monitor blood pressure immediately. Repeat measurement and consider physician assessment due to hypertensive risk.'


            ];


        }






        /*
        |--------------------------------------------------------------------------
        | Oxygen Recommendation
        |--------------------------------------------------------------------------
        */


        if(
            $vital->oxygen_level < 92
        )
        {


            $recommendations[] = [


                'type'=>'Respiratory Monitoring',


                'priority'=>'CRITICAL',


                'recommendation'=>
                'Assess respiratory condition immediately. Monitor oxygen saturation and provide clinical intervention if required.'


            ];


        }






        /*
        |--------------------------------------------------------------------------
        | Temperature Recommendation
        |--------------------------------------------------------------------------
        */


        if(
            $vital->temperature >= 38
        )
        {


            $recommendations[] = [


                'type'=>'Infection Assessment',


                'priority'=>'HIGH',


                'recommendation'=>
                'Assess patient for possible infection. Monitor temperature and observe for additional symptoms.'


            ];


        }






        /*
        |--------------------------------------------------------------------------
        | Blood Glucose Recommendation
        |--------------------------------------------------------------------------
        */


        if(
            $vital->blood_glucose >= 10
        )
        {


            $recommendations[] = [


                'type'=>'Diabetes Management',


                'priority'=>'HIGH',


                'recommendation'=>
                'Review glucose control. Monitor blood sugar levels and evaluate current diabetes management plan.'


            ];


        }







        /*
        |--------------------------------------------------------------------------
        | Save Recommendations
        |--------------------------------------------------------------------------
        */


        foreach($recommendations as $recommendation)
        {



            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Recommendation Within 30 Minutes
            |--------------------------------------------------------------------------
            */


            $existing = ClinicalRecommendation::where(
                    'resident_id',
                    $vital->resident_id
                )
                ->where(
                    'recommendation_type',
                    $recommendation['type']
                )
                ->where(
                    'created_on',
                    '>=',
                    Carbon::now()->subMinutes(30)
                )
                ->first();





            if(!$existing)
            {


                ClinicalRecommendation::create([


                    'resident_id'=>$vital->resident_id,


                    'recommendation_type'=>$recommendation['type'],


                    'recommendation'=>$recommendation['recommendation'],


                    'priority'=>$recommendation['priority']


                ]);



            }



        }



        return $recommendations;



    }


}