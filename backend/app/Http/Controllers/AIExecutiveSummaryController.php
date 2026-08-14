<?php

namespace App\Http\Controllers;


use App\Services\AIExecutiveSummaryEngine;
use App\Helpers\ApiResponse;



class AIExecutiveSummaryController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Executive Summary Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        AIExecutiveSummaryEngine $engine
    )
    {


        $summary =
            $engine->analyze();





        return ApiResponse::success(

            'AI executive summary generated successfully',

            $summary

        );


    }



}