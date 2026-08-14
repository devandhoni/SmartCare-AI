<?php

namespace App\Http\Controllers;


use App\Services\EscalationAnalyticsEngine;
use App\Helpers\ApiResponse;



class EscalationAnalyticsController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Escalation Analytics Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        EscalationAnalyticsEngine $engine
    )
    {


        $analytics =
            $engine->analyze();





        return ApiResponse::success(

            'Escalation analytics generated successfully',

            $analytics

        );


    }



}