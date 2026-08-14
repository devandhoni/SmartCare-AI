<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\NurseTask;
use Carbon\Carbon;



class AIAutoNurseTaskService
{


    /*
    |--------------------------------------------------------------------------
    | Generate Automatic Nurse Tasks From AI Alerts
    |--------------------------------------------------------------------------
    */


    public function generate(

        $residentId,

        $decisionData = null

    )
    {


        $createdTasks = [];

        $clinicalActionPlan = null;


            if($decisionData)
            {

                $clinicalActionPlan =
                    $decisionData['clinical_action_plan'] ?? null;

            }



        /*
        |--------------------------------------------------------------------------
        | Get Active AI Alerts
        |--------------------------------------------------------------------------
        */


        $alerts = AiAlert::where(

                'resident_id',

                $residentId

            )
            ->where(

                'status',

                'OPEN'

            )
            ->get();









        foreach($alerts as $alert)
        {





            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Task
            |--------------------------------------------------------------------------
            */


            $existingTask = NurseTask::where(

                'resident_id',

                $residentId

            )
            ->where(

                'ai_generated',

                true

            )
            ->whereIn(

                'status',

                [
                    'Pending',
                    'ACKNOWLEDGED'
                ]

            )
            ->latest('created_on')
            ->first();




            if($existingTask)
            {

                continue;

            }









            /*
            |--------------------------------------------------------------------------
            | Determine Priority
            |--------------------------------------------------------------------------
            */


            if($alert->severity == "CRITICAL")
            {


                $priority = "URGENT";


                $dueMinutes = 15;


            }
            elseif($alert->severity == "HIGH")
            {


                $priority = "HIGH";


                $dueMinutes = 60;


            }
            else
            {


                $priority = "NORMAL";


                $dueMinutes = 240;


            }









            /*
            |--------------------------------------------------------------------------
            | Create Nurse Task
            |--------------------------------------------------------------------------
            */


            $task = NurseTask::create([



                'resident_id'=>

                    $residentId,



                'source_alert_id'=>

                    $alert->id,



                'ai_generated'=>

                    true,



                'task_name'=>

                    'AI Clinical Intervention',



                'description'=>

                    $alert->alert_type
                    .
                    ': '
                    .
                    $alert->message,



                'clinical_action_plan'=>$clinicalActionPlan,



                'scheduled_time'=>

                    Carbon::now()
                    ->addMinutes($dueMinutes),



                'status'=>

                    'Pending',



                'priority'=>

                    $priority,



            ]);









            /*
            |--------------------------------------------------------------------------
            | Record Clinical Timeline
            |--------------------------------------------------------------------------
            */


            app(ClinicalTimelineService::class)
            ->record(


                $residentId,


                'NURSE_ACTION',


                'AI Generated Nurse Task',


                'Automatic nurse intervention created from AI clinical decision.',


                'NurseTask',


                $task->id



            );









            /*
            |--------------------------------------------------------------------------
            | Return Result
            |--------------------------------------------------------------------------
            */


            $createdTasks[] = [



                'task_id'=>

                    $task->id,



                'source'=>

                    'AI_ALERT',



                'priority'=>

                    $priority,



                'alert'=>

                    $alert->alert_type,



                'clinical_action_plan'=>

                    $clinicalActionPlan,



                'due_time'=>

                    $task->scheduled_time



            ];




        }







        return $createdTasks;



    }



}