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


        $vitals = VitalSign::where(
            'resident_id',
            $residentId
        )
        ->latest('created_on')
        ->limit(5)
        ->get()
        ->reverse();



        $analysis = [];

        $trendStatus = "STABLE";



        /*
        |--------------------------------------------------------------------------
        | Check Blood Pressure Trend
        |--------------------------------------------------------------------------
        */


        if($vitals->count() >= 2)
        {


            $firstBP =
                $vitals->first()->blood_pressure_systolic;


            $lastBP =
                $vitals->last()->blood_pressure_systolic;



            if($lastBP > $firstBP)
            {

                $analysis[] =
                "Blood pressure trend is increasing.";

                $trendStatus = "WORSENING";

            }


            elseif($lastBP < $firstBP)
            {

                $analysis[] =
                "Blood pressure is improving.";

            }



        }





        /*
        |--------------------------------------------------------------------------
        | Check Oxygen Trend
        |--------------------------------------------------------------------------
        */


        if($vitals->count() >= 2)
        {


            $firstOxygen =
                $vitals->first()->oxygen_level;


            $lastOxygen =
                $vitals->last()->oxygen_level;



            if($lastOxygen < $firstOxygen)
            {

                $analysis[] =
                "Oxygen saturation trend is decreasing.";

                $trendStatus = "WORSENING";

            }


        }





        /*
        |--------------------------------------------------------------------------
        | Check Blood Glucose Trend
        |--------------------------------------------------------------------------
        */


        if($vitals->count() >= 2)
        {


            $firstGlucose =
                $vitals->first()->blood_glucose;


            $lastGlucose =
                $vitals->last()->blood_glucose;



            if($lastGlucose > $firstGlucose)
            {

                $analysis[] =
                "Blood glucose trend is increasing.";

                $trendStatus = "WORSENING";

            }


        }





        /*
        |--------------------------------------------------------------------------
        | No Significant Change
        |--------------------------------------------------------------------------
        */


        if(empty($analysis))
        {

            $analysis[] =
            "No significant health changes detected.";

        }





        return [


            'resident_id'=>$residentId,


            'trend_status'=>$trendStatus,


            'analysis'=>$analysis,


            'data_points'=>$vitals->count()


        ];



    }


}