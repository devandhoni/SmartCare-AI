<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Services\CareRecommendationEngine;



class CareRecommendationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Personalized Care Recommendation
    |--------------------------------------------------------------------------
    */


    public function show(
        $id,
        CareRecommendationEngine $careRecommendationEngine
    )
    {


        $recommendation = $careRecommendationEngine->generate($id);



        return response()->json([


            'message'=>
            'Care recommendation generated successfully',


            'care_plan'=>$recommendation



        ]);


    }


}