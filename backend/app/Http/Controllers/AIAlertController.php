<?php

namespace App\Http\Controllers;


use App\Models\AiAlert;
use App\Helpers\ApiResponse;



class AIAlertController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Get AI Alert List
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $alerts = AiAlert::with(

            'resident'

        )
        ->where(

            'status',

            'OPEN'

        )
        ->orderBy(

            'created_on',

            'desc'

        )
        ->get();





        return ApiResponse::success(


            'AI alerts retrieved successfully',


            [

                'alerts'=>$alerts

            ]


        );


    }



}