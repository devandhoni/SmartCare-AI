<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\NurseTask;
use App\Models\AiAlert;
use App\Models\ActivityLog;
use App\Models\AlertEscalationLog;
use App\Models\ClinicalTimeline;

use App\Helpers\ApiResponse;



class NurseTaskController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | View All Nurse Tasks
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $tasks =

            NurseTask::with([

                'resident',

                'assignedUser',

                'alert'

            ])
            ->orderBy(
                'created_on',
                'desc'
            )
            ->get();




        return ApiResponse::success(

            'Nurse tasks retrieved successfully',

            $tasks

        );


    }








    /*
    |--------------------------------------------------------------------------
    | View Single Task
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        $task =

            NurseTask::with([

                'resident',

                'assignedUser',

                'alert'

            ])
            ->findOrFail($id);




        return ApiResponse::success(

            'Nurse task retrieved successfully',

            $task

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Assign Task
    |--------------------------------------------------------------------------
    */


    public function assign(
        Request $request,
        $id
    )
    {


        $request->validate([

            'assigned_to'=>'required|exists:users,id'

        ]);




        $task =

            NurseTask::findOrFail($id);




        $task->update([

            'assigned_to'=>
                $request->assigned_to

        ]);






        ActivityLog::create([

            'user_id'=>
                auth()->id(),

            'resident_id'=>
                $task->resident_id,

            'module'=>
                'Nurse Task',

            'action'=>
                'ASSIGN',

            'description'=>
                'Nurse task assigned.'

        ]);





        return ApiResponse::success(

            'Task assigned successfully',

            [

                'task'=>$task

            ]

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Nurse Accept Task
    |--------------------------------------------------------------------------
    */


    public function accept($id)
    {


        $task =

            NurseTask::findOrFail($id);





        $task->update([


            'assigned_to'=>auth()->id(),

            'status'=>'ACKNOWLEDGED',

            'acknowledged_by'=>auth()->id(),

            'acknowledged_at'=>now()


        ]);


        /*
        |--------------------------------------------------------------------------
        | Acknowledge Linked AI Alert
        |--------------------------------------------------------------------------
        */

        if($task->source_alert_id)
        {

            $alert = AiAlert::find(
                $task->source_alert_id
            );


            if($alert)
            {

                $alert->update([

                    'acknowledged_by'=>auth()->id(),

                    'acknowledged_at'=>now()

                ]);

            }

        }



        ClinicalTimeline::create([

            'resident_id'=>$task->resident_id,

            'event_type'=>'NURSE_ACTION',

            'event_title'=>'Nurse Accepted AI Task',

            'event_description'=>
                'Nurse reviewed and accepted AI generated clinical intervention.',

            'source_type'=>'NurseTask',

            'source_id'=>$task->id,

            'event_date'=>now(),

            'decision_status'=>'REVIEWED',

            'reviewed_by'=>auth()->id(),

            'reviewed_at'=>now(),

            'review_action'=>
                'AI recommendation accepted by nurse.'

        ]);






        ActivityLog::create([


            'user_id'=>
                auth()->id(),


            'resident_id'=>
                $task->resident_id,


            'module'=>
                'Nurse Task',


            'action'=>
                'ACCEPT',


            'description'=>
                'Nurse accepted task.'

        ]);





        return ApiResponse::success(

            'Task accepted successfully',

            [

                'task'=>$task

            ]

        );


    }

        /*
    |--------------------------------------------------------------------------
    | Complete Nurse Task
    |--------------------------------------------------------------------------
    */


    public function complete($id)
    {


        $task =

            NurseTask::findOrFail($id);





        /*
        |--------------------------------------------------------------------------
        | Complete Task
        |--------------------------------------------------------------------------
        */


        $task->update([


            'status'=>
                'Completed',


            'completed_time'=>
                now()


        ]);








        /*
        |--------------------------------------------------------------------------
        | Resolve Linked AI Alert
        |--------------------------------------------------------------------------
        */


        $alert = null;



        if($task->source_alert_id)
        {


            $alert =

                AiAlert::find(
                    $task->source_alert_id
                );


        }


        if($alert)
        {


            $alert->update([


                'status'=>
                    'RESOLVED',


                'resolved_by'=>
                    auth()->id(),


                'resolved_at'=>
                    now(),


                'resolution_note'=>

                    'Resolved through nurse task completion. Task ID: '
                    .$task->id


            ]);






            /*
            |--------------------------------------------------------------------------
            | Update Alert Escalation
            |--------------------------------------------------------------------------
            */


            AlertEscalationLog::where(

                'alert_id',

                $alert->id

            )
            ->latest()
            ->first()
            ?->update([


                'status'=>
                    'RESOLVED',


                'resolved_at'=>
                    now()


            ]);



        }









        /*
        |--------------------------------------------------------------------------
        | Update AI Clinical Decision Timeline
        |--------------------------------------------------------------------------
        */


        ClinicalTimeline::where(

            'resident_id',

            $task->resident_id

        )
        ->where(

            'event_type',

            'AI_DECISION'

        )
        ->latest('event_date')
        ->first()
        ?->update([


            'decision_status'=>
                'RESOLVED',


            'reviewed_by'=>
                auth()->id(),


            'reviewed_at'=>
                now(),


            'review_action'=>

                'AI recommendation reviewed, intervention completed, and clinical task resolved.'


        ]);









        /*
        |--------------------------------------------------------------------------
        | Create Nurse Completion Timeline
        |--------------------------------------------------------------------------
        */


        ClinicalTimeline::create([


            'resident_id'=>
                $task->resident_id,


            'event_type'=>
                'NURSE_ACTION',


            'event_title'=>
                'Nurse Task Completed',


            'event_description'=>

                'AI generated nurse task completed.',


            'source_type'=>
                'NurseTask',


            'source_id'=>
                $task->id,


            'event_date'=>
                now()


        ]);









        ActivityLog::create([


            'user_id'=>
                auth()->id(),


            'resident_id'=>
                $task->resident_id,


            'module'=>
                'Nurse Task',


            'action'=>
                'COMPLETE',


            'description'=>
                'Nurse completed AI clinical task.'


        ]);








        return ApiResponse::success(


            'Task completed successfully',


            [

                'task'=>$task,

                'alert'=>$alert

            ]


        );


    }












    /*
    |--------------------------------------------------------------------------
    | Acknowledge Nurse Task
    |--------------------------------------------------------------------------
    */


    public function acknowledge($id)
    {


        $task =

            NurseTask::findOrFail($id);






        if($task->acknowledged_at)
        {


            return ApiResponse::success(


                'Task already acknowledged.',


                [

                    'task'=>$task

                ]


            );


        }







        $task->update([


            'status'=>
                'ACKNOWLEDGED',


            'acknowledged_by'=>
                auth()->id(),


            'acknowledged_at'=>
                now()


        ]);








        /*
        |--------------------------------------------------------------------------
        | Update Alert Escalation
        |--------------------------------------------------------------------------
        */


        $escalationLog = null;



        if($task->source_alert_id)
        {


            $escalationLog =

                AlertEscalationLog::where(

                    'alert_id',

                    $task->source_alert_id

                )
                ->latest()
                ->first();





            if($escalationLog)
            {


                $escalationLog->update([


                    'status'=>
                        'ACKNOWLEDGED',


                    'acknowledged_at'=>
                        now()


                ]);


            }









            /*
            |--------------------------------------------------------------------------
            | Update AI Alert Acknowledgement
            |--------------------------------------------------------------------------
            */


            $alert = AiAlert::find(
    $task->source_alert_id
);


if($alert)
{

    \Log::info('ACKNOWLEDGE ALERT FOUND', [

        'alert_id' => $alert->id,

        'before_acknowledged_by' => $alert->acknowledged_by,

        'before_acknowledged_at' => $alert->acknowledged_at,

        'user_id' => auth()->id(),

    ]);


    $alert->update([

        'acknowledged_by'=>
            auth()->id(),

        'acknowledged_at'=>
            now()

    ]);


    \Log::info('ACKNOWLEDGE ALERT AFTER UPDATE', [

        'alert_id' => $alert->id,

        'after_acknowledged_by' => $alert->fresh()->acknowledged_by,

        'after_acknowledged_at' => $alert->fresh()->acknowledged_at,

    ]);

}
else
{

    \Log::warning('ACKNOWLEDGE ALERT NOT FOUND', [

        'source_alert_id'=>$task->source_alert_id

    ]);

}



        }









        /*
        |--------------------------------------------------------------------------
        | Update Clinical Timeline
        |--------------------------------------------------------------------------
        */


        ClinicalTimeline::where(

            'resident_id',

            $task->resident_id

        )
        ->where(

            'event_type',

            'AI_DECISION'

        )
        ->latest('event_date')
        ->first()
        ?->update([


            'decision_status'=>
                'ACKNOWLEDGED',


            'reviewed_by'=>
                auth()->id(),


            'reviewed_at'=>
                now(),


            'review_action'=>

                'Nurse acknowledged AI clinical decision.'


        ]);









        ActivityLog::create([


            'user_id'=>
                auth()->id(),


            'resident_id'=>
                $task->resident_id,


            'module'=>
                'Nurse Task',


            'action'=>
                'ACKNOWLEDGE',


            'description'=>

                'Nurse acknowledged AI generated task.'


        ]);









        return ApiResponse::success(


            'Task acknowledged successfully',


            [

                'task'=>$task,

                'escalation_log'=>$escalationLog


            ]


        );


    }





}