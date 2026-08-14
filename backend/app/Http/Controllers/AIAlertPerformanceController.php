<?php

namespace App\Http\Controllers;


use App\Services\AIAlertPerformanceEngine;
use App\Helpers\ApiResponse;



class AIAlertPerformanceController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Alert Performance Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        AIAlertPerformanceEngine $engine
    )
    {


        $result =
            $engine->analyze();




        return ApiResponse::success(

            'AI alert performance analytics generated successfully',

            $result

        );


    }



}