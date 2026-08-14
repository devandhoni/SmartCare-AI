<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\VitalSign;



class RealTimeMonitoringEngine
{


    protected AIHealthAnalyzer $aiHealthAnalyzer;



    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */


    public function __construct(
        AIHealthAnalyzer $aiHealthAnalyzer
    )
    {

        $this->aiHealthAnalyzer = $aiHealthAnalyzer;

    }







    /*
    |--------------------------------------------------------------------------
    | Run Real Time Health Monitoring
    |--------------------------------------------------------------------------
    */


    public function monitor()
    {


        /*
        |--------------------------------------------------------------------------
        | Get Residents For Monitoring
        |--------------------------------------------------------------------------
        |
        | Development mode:
        | Monitor all residents.
        |
        | Later production can filter:
        | Active / Admitted only.
        |
        |--------------------------------------------------------------------------
        */


        $residents = Resident::all();





        $results = [];






        foreach($residents as $resident)
        {




            /*
            |--------------------------------------------------------------------------
            | Get Latest Vital Sign
            |--------------------------------------------------------------------------
            */


            $latestVital = VitalSign::where(
                    'resident_id',
                    $resident->id
                )
                ->latest('created_on')
                ->first();






            /*
            |--------------------------------------------------------------------------
            | Skip Resident Without Vital Data
            |--------------------------------------------------------------------------
            */


            if(!$latestVital)
            {


                $results[] = [

                    'resident_id'=>$resident->id,

                    'resident_name'=>$resident->full_name,

                    'alerts_generated'=>0,

                    'monitoring_result'=>
                    'No vital sign data available',


                    'status'=>'NO DATA'

                ];


                continue;


            }








            /*
            |--------------------------------------------------------------------------
            | Run AI Health Analysis
            |--------------------------------------------------------------------------
            */


            $alerts = $this->aiHealthAnalyzer->analyze(
                $latestVital
            );








            /*
            |--------------------------------------------------------------------------
            | Store Monitoring Result
            |--------------------------------------------------------------------------
            */


            $results[] = [


                'resident_id'=>$resident->id,


                'resident_name'=>$resident->full_name,


                'latest_vital_id'=>$latestVital->id,



                'alerts_generated'=>count($alerts),




                'monitoring_result'=>

                    count($alerts) > 0

                    ?

                    'New health alerts generated'

                    :

                    'Resident monitored, no new alerts',




                'status'=>

                    count($alerts) > 0

                    ?

                    'ATTENTION REQUIRED'

                    :

                    'NORMAL'


            ];



        }







        /*
        |--------------------------------------------------------------------------
        | Return Monitoring Summary
        |--------------------------------------------------------------------------
        */


        return [


            'monitoring_status'=>'COMPLETED',


            'monitoring_time'=>now(),


            'residents_checked'=>$residents->count(),


            'results'=>$results



        ];



    }



}