<?php

namespace App\Http\Controllers;

use App\Services\AICommandCenterEngine;
use App\Services\AIOutcomePerformanceEngine;
use App\Helpers\ApiResponse;


class AICommandCenterController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Command Center Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        AICommandCenterEngine $engine,
        AIOutcomePerformanceEngine $performanceEngine
    )
    {


        /*
        |--------------------------------------------------------------------------
        | Generate AI Command Center Intelligence
        |--------------------------------------------------------------------------
        */


        $commandCenter = $engine->analyze();



        /*
        |--------------------------------------------------------------------------
        | Generate AI Learning Performance
        |--------------------------------------------------------------------------
        */


        $aiOutcomePerformance =
            $performanceEngine->analyze();





        /*
        |--------------------------------------------------------------------------
        | Merge AI Outcome Intelligence
        |--------------------------------------------------------------------------
        */


        $commandCenter['ai_outcome_performance'] =
            $aiOutcomePerformance;





        /*
        |--------------------------------------------------------------------------
        | Return Complete AI Command Center
        |--------------------------------------------------------------------------
        */


        return ApiResponse::success(

            'AI Command Center generated successfully',

            $commandCenter

        );


    }


}