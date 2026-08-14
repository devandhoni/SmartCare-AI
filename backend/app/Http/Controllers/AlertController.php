<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\AiAlert;
use App\Models\AlertEscalationLog;
use App\Services\AlertActionService;



class AlertController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Acknowledge AI Alert
    |--------------------------------------------------------------------------
    */


    public function acknowledge(
        Request $request,
        $id,
        AlertActionService $alertActionService
    )
    {


        $alert =
        AiAlert::findOrFail($id);




        $userId =
        $request->user()->id;




        /*
        |--------------------------------------------------------------------------
        | Update AI Alert
        |--------------------------------------------------------------------------
        */


        $alert->update([


            'acknowledged_by'=>
                $userId,


            'acknowledged_at'=>
                now()


        ]);






        /*
        |--------------------------------------------------------------------------
        | Update Escalation Log
        |--------------------------------------------------------------------------
        */


        AlertEscalationLog::where(
            'alert_id',
            $alert->id
        )
        ->where(
            'status',
            'ESCALATED'
        )
        ->update([


            'status'=>
                'ACKNOWLEDGED',


            'acknowledged_at'=>
                now()


        ]);








        /*
        |--------------------------------------------------------------------------
        | Create Audit History
        |--------------------------------------------------------------------------
        */


        $alertActionService
        ->acknowledged(

            $alert->id,

            $userId

        );








        return response()->json([


            'message'=>
                'Alert acknowledged successfully',



            'alert_id'=>
                $alert->id,



            'acknowledged_by'=>
                $userId,



            'acknowledged_at'=>
                now()


        ]);



    }


}