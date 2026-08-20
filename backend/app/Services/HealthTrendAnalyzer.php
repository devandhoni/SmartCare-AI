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
        | Retrieve Latest Vital Records
        |--------------------------------------------------------------------------
        */


        $rawVitals = VitalSign::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->limit(20)
            ->get()
            ->reverse();



        /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Vital Readings
        |--------------------------------------------------------------------------
        */


        $vitals = $this->removeDuplicateVitals(
            $rawVitals
        );






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


            if(
                $latestVital->blood_pressure_systolic >=180 ||
                $latestVital->blood_pressure_diastolic >=120
            )
            {

                $currentReasons[] =
                "Critical blood pressure detected.";

                $currentStatus="CRITICAL";

            }



            if(
                $latestVital->oxygen_level <90
            )
            {

                $currentReasons[] =
                "Low oxygen saturation detected.";

                $currentStatus="CRITICAL";

            }



            if(
                $latestVital->blood_glucose >=13
            )
            {

                $currentReasons[] =
                "High blood glucose detected.";

                if($currentStatus!=="CRITICAL")
                {
                    $currentStatus="HIGH";
                }

            }




            if(
                $latestVital->temperature >=39
            )
            {

                $currentReasons[] =
                "High temperature detected.";

                $currentStatus="CRITICAL";

            }


        }







        /*
        |--------------------------------------------------------------------------
        | Trend Analysis
        |--------------------------------------------------------------------------
        */


        if($vitals->count() >=2)
        {


            $firstVital=$vitals->first();

            $lastVital=$vitals->last();





            if(
                $lastVital->blood_pressure_systolic >
                $firstVital->blood_pressure_systolic
            )
            {

                $trendAnalysis[]=
                "Blood pressure trend is increasing.";

                $trendStatus="WORSENING";

            }


            elseif(
                $lastVital->blood_pressure_systolic <
                $firstVital->blood_pressure_systolic
            )
            {

                $trendAnalysis[]=
                "Blood pressure trend is improving.";

            }





            if(
                $lastVital->oxygen_level <
                $firstVital->oxygen_level
            )
            {

                $trendAnalysis[]=
                "Oxygen saturation trend is decreasing.";

                $trendStatus="WORSENING";

            }





            if(
                $lastVital->blood_glucose >
                $firstVital->blood_glucose
            )
            {

                $trendAnalysis[]=
                "Blood glucose trend is increasing.";

                $trendStatus="WORSENING";

            }


        }





        if(empty($trendAnalysis))
        {

            $trendAnalysis[]=
            "No significant health changes detected.";

        }







        /*
        |--------------------------------------------------------------------------
        | Data Quality
        |--------------------------------------------------------------------------
        */


        $dataQuality =
            $this->checkDataQuality(
                $rawVitals,
                $vitals
            );






        /*
        |--------------------------------------------------------------------------
        | Confidence
        |--------------------------------------------------------------------------
        */


        $confidence =
            $this->calculateConfidence(
                $vitals->count()
            );







        /*
        |--------------------------------------------------------------------------
        | Chart Data
        |--------------------------------------------------------------------------
        */


        $chartData =
            $vitals
            ->map(function($vital){


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


            })
            ->values();









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
    | Remove Duplicate Vitals
    |--------------------------------------------------------------------------
    */


    private function removeDuplicateVitals($vitals)
    {


        return $vitals
            ->unique(function($vital){


                return implode("-",[

                    $vital->blood_pressure_systolic,

                    $vital->blood_pressure_diastolic,

                    $vital->oxygen_level,

                    $vital->blood_glucose,

                    $vital->temperature,

                    $vital->heart_rate

                ]);


            })
            ->values();


    }









    /*
    |--------------------------------------------------------------------------
    | Data Quality
    |--------------------------------------------------------------------------
    */


    private function checkDataQuality(
        $original,
        $cleaned
    )
    {


        $removed =
            $original->count()
            -
            $cleaned->count();



        if($removed > 0)
        {

            return [

                "status"=>"CLEANED",

                "message"=>"Duplicate vital readings removed.",

                "original_points"=>$original->count(),

                "unique_points"=>$cleaned->count(),

                "duplicates_removed"=>$removed

            ];

        }



        return [

            "status"=>"GOOD",

            "message"=>"Vital history is clean.",

            "original_points"=>$original->count(),

            "unique_points"=>$cleaned->count(),

            "duplicates_removed"=>0

        ];


    }









    /*
    |--------------------------------------------------------------------------
    | AI Confidence
    |--------------------------------------------------------------------------
    */


    private function calculateConfidence($points)
    {


        if($points >=10)
        {
            return 90;
        }


        if($points >=5)
        {
            return 75;
        }


        if($points >=3)
        {
            return 60;
        }


        return 40;


    }


}