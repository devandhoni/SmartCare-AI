<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\VitalSign;


class VitalTrendController extends Controller
{


    public function index(
        Request $request,
        $residentId
    )
    {


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */


        $period = $request->query(
            'period',
            'today'
        );


        $metric = $request->query(
            'metric',
            'all'
        );





        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        */


        $query = VitalSign::where(
            'resident_id',
            $residentId
        );





        switch($period)
        {


            case '24hours':

                $query->where(
                    'recorded_at',
                    '>=',
                    now()->subDay()
                );

                break;



            case '7days':

                $query->where(
                    'recorded_at',
                    '>=',
                    now()->subDays(7)
                );

                break;



            case '30days':

                $query->where(
                    'recorded_at',
                    '>=',
                    now()->subDays(30)
                );

                break;



            case 'today':

            default:

                $query->whereDate(
                    'recorded_at',
                    today()
                );

                break;


        }





        $vitals = $query
            ->orderBy(
                'recorded_at',
                'asc'
            )
            ->get();






        /*
        |--------------------------------------------------------------------------
        | Prepare Chart Data
        |--------------------------------------------------------------------------
        */


        $data = $vitals->map(function($vital) use ($metric){


            $item = [


                'datetime' => 
                    $vital->recorded_at
                    ?
                    $vital->recorded_at->format('Y-m-d H:i:s')
                    :
                    null,



                'date' =>
                    $vital->recorded_at
                    ?
                    $vital->recorded_at->format('d M Y')
                    :
                    null,



                'time' =>
                    $vital->recorded_at
                    ?
                    $vital->recorded_at->format('H:i')
                    :
                    null,

            ];






            if(
                $metric === 'temperature'
                ||
                $metric === 'all'
            )
            {

                $item['temperature'] =
                    $vital->temperature;

            }







            if(
                $metric === 'oxygen'
                ||
                $metric === 'all'
            )
            {

                $item['oxygen_level'] =
                    $vital->oxygen_level;

            }







            if(
                $metric === 'glucose'
                ||
                $metric === 'all'
            )
            {

                $item['blood_glucose'] =
                    $vital->blood_glucose;

            }







          if(
                $metric === 'blood_pressure'
                ||
                $metric === 'all'
            )
            {

                $item['blood_pressure_systolic'] =
                    $vital->blood_pressure_systolic;


                $item['blood_pressure_diastolic'] =
                    $vital->blood_pressure_diastolic;


                // Keep combined value for display purposes
                $item['blood_pressure'] =
                    $vital->blood_pressure_systolic
                    .
                    "/"
                    .
                    $vital->blood_pressure_diastolic;

            }







            if(
                $metric === 'heart_rate'
                ||
                $metric === 'all'
            )
            {

                $item['heart_rate'] =
                    $vital->heart_rate;

            }



            return $item;


        });







        return response()->json([


            'resident_id'=>$residentId,


            'period'=>$period,


            'metric'=>$metric,


            'data'=>$data


        ]);



    }


}