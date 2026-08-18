<?php

namespace App\Services;

use App\Models\VitalSign;


class HealthTrendAnalyzer
{


    /*
    |--------------------------------------------------------------------------
    | Analyze Resident Health Trend
    |--------------------------------------------------------------------------
    */


    public function analyze($residentId)
    {


        /*
        |--------------------------------------------------------------------------
        | Retrieve Latest 10 Vital Records
        |--------------------------------------------------------------------------
        */


        $vitals = VitalSign::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->limit(10)
            ->get()
            ->reverse();




        /*
        |--------------------------------------------------------------------------
        | Default Values
        |--------------------------------------------------------------------------
        */


        $currentStatus = "STABLE";

        $currentReasons = [];

        $trendStatus = "STABLE";

        $trendAnalysis = [];




        /*
        |--------------------------------------------------------------------------
        | Latest Vital Assessment
        |--------------------------------------------------------------------------
        */


        $latestVital = $vitals->last();



        if($latestVital)
        {


            /*
            |--------------------------------------------------------------------------
            | Blood Pressure
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->blood_pressure_systolic >= 180 ||
                $latestVital->blood_pressure_diastolic >= 120
            )
            {

                $currentReasons[] =
                "Critical blood pressure detected.";

                $currentStatus = "CRITICAL";

            }




            /*
            |--------------------------------------------------------------------------
            | Oxygen Saturation
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->oxygen_level < 90
            )
            {

                $currentReasons[] =
                "Low oxygen saturation detected.";

                $currentStatus = "CRITICAL";

            }





            /*
            |--------------------------------------------------------------------------
            | Blood Glucose
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->blood_glucose >= 13
            )
            {

                $currentReasons[] =
                "High blood glucose detected.";

                if($currentStatus !== "CRITICAL")
                {
                    $currentStatus = "HIGH";
                }

            }





            /*
            |--------------------------------------------------------------------------
            | Temperature
            |--------------------------------------------------------------------------
            */


            if(
                $latestVital->temperature >= 39
            )
            {

                $currentReasons[] =
                "High temperature detected.";

                $currentStatus = "CRITICAL";

            }


        }






        /*
        |--------------------------------------------------------------------------
        | Historical Trend Analysis
        |--------------------------------------------------------------------------
        */


        if($vitals->count() >= 2)
        {


            $firstVital = $vitals->first();

            $lastVital = $vitals->last();




            /*
            |--------------------------------------------------------------------------
            | Blood Pressure Trend
            |--------------------------------------------------------------------------
            */


            if(
                $lastVital->blood_pressure_systolic >
                $firstVital->blood_pressure_systolic
            )
            {

                $trendAnalysis[] =
                "Blood pressure trend is increasing.";

                $trendStatus = "WORSENING";

            }



            elseif(
                $lastVital->blood_pressure_systolic <
                $firstVital->blood_pressure_systolic
            )
            {

                $trendAnalysis[] =
                "Blood pressure trend is improving.";

            }






            /*
            |--------------------------------------------------------------------------
            | Oxygen Trend
            |--------------------------------------------------------------------------
            */


            if(
                $lastVital->oxygen_level <
                $firstVital->oxygen_level
            )
            {

                $trendAnalysis[] =
                "Oxygen saturation trend is decreasing.";

                $trendStatus = "WORSENING";

            }





            /*
            |--------------------------------------------------------------------------
            | Glucose Trend
            |--------------------------------------------------------------------------
            */


            if(
                $lastVital->blood_glucose >
                $firstVital->blood_glucose
            )
            {

                $trendAnalysis[] =
                "Blood glucose trend is increasing.";

                $trendStatus = "WORSENING";

            }



        }




        if(empty($trendAnalysis))
        {

            $trendAnalysis[] =
            "No significant health changes detected.";

        }







        /*
        |--------------------------------------------------------------------------
        | Data Quality Check
        |--------------------------------------------------------------------------
        */


        $dataQuality = $this->checkDataQuality($vitals);






        /*
        |--------------------------------------------------------------------------
        | Trend Confidence
        |--------------------------------------------------------------------------
        */


        $confidence = $this->calculateConfidence(
            $vitals->count(),
            $dataQuality['status']
        );







        /*
        |--------------------------------------------------------------------------
        | Prepare Chart Data
        |--------------------------------------------------------------------------
        */


        $chartData = $vitals->map(function($vital){


            return [

                "date" =>
                    $vital->created_on
                    ?
                    $vital->created_on->format('Y-m-d')
                    :
                    null,


                "blood_pressure_systolic" =>
                    $vital->blood_pressure_systolic,


                "blood_pressure_diastolic" =>
                    $vital->blood_pressure_diastolic,


                "oxygen_level" =>
                    $vital->oxygen_level,


                "blood_glucose" =>
                    $vital->blood_glucose,


                "temperature" =>
                    $vital->temperature,


                "heart_rate" =>
                    $vital->heart_rate

            ];


        })->values();








        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */


        return [


            "resident_id"=>$residentId,



            "current_condition"=>[

                "status"=>$currentStatus,

                "reasons"=>$currentReasons

            ],




            "trend"=>[

                "status"=>$trendStatus,

                "analysis"=>$trendAnalysis

            ],





            "trend_confidence"=>$confidence,





            "data_quality"=>$dataQuality,





            "data_points"=>$vitals->count(),





            "vitals"=>$chartData


        ];



    }







    /*
    |--------------------------------------------------------------------------
    | Check Duplicate / Data Quality
    |--------------------------------------------------------------------------
    */


    private function checkDataQuality($vitals)
    {


        if($vitals->count() < 3)
        {

            return [

                "status"=>"INSUFFICIENT",

                "message"=>"Limited historical vital data available."

            ];

        }




        $uniqueValues = $vitals
            ->map(function($vital){

                return implode(
                    "-",
                    [

                    $vital->blood_pressure_systolic,
                    $vital->blood_pressure_diastolic,
                    $vital->oxygen_level,
                    $vital->blood_glucose,
                    $vital->temperature

                    ]
                );

            })
            ->unique();





        if($uniqueValues->count() == 1)
        {

            return [

                "status"=>"DUPLICATED",

                "message"=>"Multiple identical vital readings detected."

            ];

        }




        return [

            "status"=>"GOOD",

            "message"=>"Sufficient vital history available."

        ];



    }







    /*
    |--------------------------------------------------------------------------
    | Calculate AI Confidence
    |--------------------------------------------------------------------------
    */


    private function calculateConfidence(
        $dataPoints,
        $quality
    )
    {


        if($quality === "DUPLICATED")
        {

            return 40;

        }



        if($dataPoints >= 10)
        {

            return 90;

        }



        if($dataPoints >= 5)
        {

            return 75;

        }



        if($dataPoints >= 3)
        {

            return 60;

        }



        return 40;



    }



}