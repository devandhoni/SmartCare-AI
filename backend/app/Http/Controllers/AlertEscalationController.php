<?php

namespace App\Http\Controllers;


use App\Services\AlertEscalationEngine;
use App\Helpers\ApiResponse;



class AlertEscalationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Escalate AI Alert
    |--------------------------------------------------------------------------
    */


    public function escalate(
        $id,
        AlertEscalationEngine $engine
    )
    {


        $result =
            $engine->escalate($id);





        return ApiResponse::success(


            'Alert escalated successfully',


            [

                'escalation'=>$result

            ]


        );



    }



}