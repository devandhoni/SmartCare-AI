<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Services\HealthJourneyAnalyzer;



class HealthJourneyController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Patient Health Journey
    |--------------------------------------------------------------------------
    */


    public function show(
        $id,
        HealthJourneyAnalyzer $healthJourneyAnalyzer
    )
    {


        $journey = $healthJourneyAnalyzer->analyze($id);



        return response()->json([


            'message'=>
            'Health journey generated successfully',


            'journey'=>$journey



        ]);


    }



}