<?php

namespace App\Http\Controllers;


use App\Services\ClinicalPerformanceDashboardEngine;
use App\Helpers\ApiResponse;



class ClinicalPerformanceDashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Clinical Performance Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        ClinicalPerformanceDashboardEngine $engine
    )
    {


        $dashboard =
            $engine->analyze();





        return ApiResponse::success(

            'Clinical performance dashboard generated successfully',

            $dashboard

        );


    }



}