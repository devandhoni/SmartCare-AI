<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\AlertEscalationLog;
use Carbon\Carbon;



class AlertEscalationEngine
{


    /*
    |--------------------------------------------------------------------------
    | Escalate AI Alert
    |--------------------------------------------------------------------------
    */


    public function escalate($alertId)
    {


        $alert = AiAlert::findOrFail($alertId);



        /*
        |--------------------------------------------------------------------------
        | Prevent Escalation For Resolved Alert
        |--------------------------------------------------------------------------
        */


        if($alert->status === 'RESOLVED')
        {

            return [

                'alert_id'=>$alert->id,

                'resident_id'=>$alert->resident_id,

                'message'=>
                    'Alert already resolved. Escalation not required.',

                'duplicate'=>true

            ];

        }





        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Escalation
        |--------------------------------------------------------------------------
        */


        $existingEscalation =

            AlertEscalationLog::where(
                'alert_id',
                $alert->id
            )
            ->whereIn(
                'status',
                [
                    'ESCALATED',
                    'ACKNOWLEDGED'
                ]
            )
            ->first();





        if($existingEscalation)
        {

            return [

                'alert_id'=>$alert->id,

                'resident_id'=>$alert->resident_id,

                'priority'=>$existingEscalation->priority,

                'notification_created'=>false,

                'duplicate'=>true,

                'message'=>
                    'Alert already escalated.',

                'escalation_log_id'=>
                    $existingEscalation->id

            ];

        }







        /*
        |--------------------------------------------------------------------------
        | Determine Escalation Priority
        |--------------------------------------------------------------------------
        */


        switch($alert->severity)
        {


            case "CRITICAL":


                $priority = "URGENT";


                $notificationMessage =
                    "CRITICAL AI alert requires immediate attention: "
                    .$alert->message;


                break;



            case "HIGH":


                $priority = "HIGH";


                $notificationMessage =
                    "HIGH priority AI alert detected: "
                    .$alert->message;


                break;




            default:


                $priority = "NORMAL";


                $notificationMessage =
                    "AI health alert requires monitoring: "
                    .$alert->message;


                break;


        }








        /*
        |--------------------------------------------------------------------------
        | Create Notification
        |--------------------------------------------------------------------------
        */


        $existingNotification =

            Notification::where(
                'type',
                'AI_ESCALATION'
            )
            ->where(
                'message',
                $notificationMessage
            )
            ->where(
                'read_status',
                0
            )
            ->first();





        if(!$existingNotification)
        {


            Notification::create([


                'user_id'=>1,


                'title'=>
                    "AI Alert Escalation - ".$priority,


                'message'=>
                    $notificationMessage,


                'type'=>
                    "AI_ESCALATION",


                'read_status'=>0


            ]);


            $notificationCreated=true;


        }
        else
        {

            $notificationCreated=false;

        }









        /*
        |--------------------------------------------------------------------------
        | Create Escalation Log
        |--------------------------------------------------------------------------
        */


        $escalationLog =

            AlertEscalationLog::create([


                'alert_id'=>
                    $alert->id,


                'resident_id'=>
                    $alert->resident_id,


                'priority'=>
                    $priority,


                'escalation_reason'=>
                    $alert->message,


                'assigned_to'=>
                    null,


                'escalated_at'=>
                    Carbon::now(),


                'status'=>
                    'ESCALATED'


            ]);









        /*
        |--------------------------------------------------------------------------
        | Activity Logging
        |--------------------------------------------------------------------------
        */


        ActivityLog::create([


            'user_id'=>
                auth()->id() ?? 1,


            'resident_id'=>
                $alert->resident_id,


            'module'=>
                "AI Alert Escalation",


            'action'=>
                "ESCALATE",


            'description'=>

                "AI alert escalated with priority level: "
                .$priority


        ]);









        return [


            'alert_id'=>
                $alert->id,


            'resident_id'=>
                $alert->resident_id,


            'priority'=>
                $priority,


            'notification_created'=>
                $notificationCreated,


            'escalation_log_id'=>
                $escalationLog->id,


            'task_created'=>
                false,


            'duplicate'=>
                false


        ];


    }



}