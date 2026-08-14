<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;


use App\Models\AiAlert;
use App\Models\AlertEscalationLog;


use App\Services\ActivityLogger;
use App\Helpers\ApiResponse;
use App\Services\AlertActionService;



class AIAlertWorkflowController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Acknowledge Alert
    |--------------------------------------------------------------------------
    */


    public function acknowledge(
        $id,
        ActivityLogger $logger
    )
    {


        $alert =
            AiAlert::findOrFail($id);





        $alert->update([


            'acknowledged_by'=>auth()->id(),


            'acknowledged_at'=>now()


        ]);








        /*
        |--------------------------------------------------------------------------
        | Update Escalation Log
        |--------------------------------------------------------------------------
        */


        $escalation =
            AlertEscalationLog::where(
                'alert_id',
                $alert->id
            )
            ->where(
                'status',
                'ESCALATED'
            )
            ->latest()
            ->first();






        if($escalation)
        {


            $escalation->update([


                'assigned_to'=>auth()->id(),


                'acknowledged_at'=>now(),


                'status'=>'ACKNOWLEDGED'


            ]);


        }








        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */


        $logger->log(


            'AI Alert',


            'ACKNOWLEDGE',


            'AI alert acknowledged by user.',


            $alert->resident_id


        );









        return ApiResponse::success(


            'Alert acknowledged successfully',


            [

                'alert'=>$alert,

                'escalation'=>$escalation

            ]


        );



    }









    /*
    |--------------------------------------------------------------------------
    | Resolve Alert
    |--------------------------------------------------------------------------
    */


    public function resolve(
        Request $request,
        $id,
        ActivityLogger $logger,
        AlertActionService $alertActionService
    )
    {


        $alert =
            AiAlert::findOrFail($id);








        $alert->update([


            'status'=>'RESOLVED',


            'resolved_by'=>auth()->id(),


            'resolved_at'=>now(),


            'resolution_note'=>$request->resolution_note


        ]);









        /*
        |--------------------------------------------------------------------------
        | Update Escalation Log
        |--------------------------------------------------------------------------
        */


        $escalation =
            AlertEscalationLog::where(
                'alert_id',
                $alert->id
            )
            ->latest()
            ->first();






        if($escalation)
        {


            $escalation->update([


                'resolved_at'=>now(),


                'status'=>'RESOLVED'


            ]);


        }




        /*
        |--------------------------------------------------------------------------
        | Alert Action Audit
        |--------------------------------------------------------------------------
        */

        $alertActionService->record(

            $alert->id,

            'RESOLVED',

            'Nurse resolved AI alert',

            auth()->id()

        );




        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */


        $logger->log(


            'AI Alert',


            'RESOLVED',


            'AI alert resolved by user.',


            $alert->resident_id


        );









        return ApiResponse::success(


            'Alert resolved successfully',


            [

                'alert'=>$alert,

                'escalation'=>$escalation

            ]


        );



    }



}