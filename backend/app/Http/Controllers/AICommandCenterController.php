<?php

namespace App\Http\Controllers;


use App\Services\AICommandCenterEngine;
use App\Helpers\ApiResponse;



class AICommandCenterController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Command Center Dashboard
    |--------------------------------------------------------------------------
    */


    public function index(
        AICommandCenterEngine $engine
    )
    {


        $commandCenter =
            $engine->analyze();





        return ApiResponse::success(

            'AI Command Center generated successfully',

            $commandCenter

        );


    }



}