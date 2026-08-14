<?php

namespace App\Http\Controllers;


use App\Services\AIDashboardEngine;
use App\Helpers\ApiResponse;



class AIDashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | SmartCare AI Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        AIDashboardEngine $dashboard
    )
    {


        $result =

            $dashboard->generate();




        return ApiResponse::success(


            'AI dashboard data generated successfully',


            $result


        );


    }



}