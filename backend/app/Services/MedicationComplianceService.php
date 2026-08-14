<?php

namespace App\Services;


use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use App\Models\AiAlert;
use App\Models\NurseTask;
use App\Models\Notification;

use App\Services\ClinicalTimelineService;

use App\Enums\ClinicalEventType;

use Carbon\Carbon;




class MedicationComplianceService
{





    protected ClinicalTimelineService $timelineService;




    public function __construct(
        ClinicalTimelineService $timelineService
    )
    {

        $this->timelineService =
            $timelineService;

    }








    /*
    |--------------------------------------------------------------------------
    | Detect Delayed Medication
    |--------------------------------------------------------------------------
    */


    public function detectDelayedMedication()
    {


        $now =
            Carbon::now();





        $medications =

            ResidentMedication::with(

                [
                    'resident',
                    'medication'
                ]

            )
            ->whereNotNull(
                'scheduled_time'
            )
            ->get();







        $delayedMedications=[];







        foreach($medications as $medication)
        {




            /*
            |--------------------------------------------------------------------------
            | Build Scheduled Date Time
            |--------------------------------------------------------------------------
            */


            $scheduledDateTime =

                Carbon::today()
                ->setTimeFromTimeString(

                    $medication->scheduled_time

                );








            /*
            |--------------------------------------------------------------------------
            | Calculate Delay
            |--------------------------------------------------------------------------
            */


            $delayMinutes = round(

                $scheduledDateTime
                ->diffInMinutes(
                    $now,
                    false
                )

            );








            /*
            |--------------------------------------------------------------------------
            | Delay Threshold
            |--------------------------------------------------------------------------
            */


            if($delayMinutes > 15)
            {






                /*
                |--------------------------------------------------------------------------
                | Check Completed
                |--------------------------------------------------------------------------
                */


                $completed =

                    MedicationAdministrationRecord::where(

                        'resident_medication_id',

                        $medication->id

                    )
                    ->whereDate(

                        'completed_time',

                        today()

                    )
                    ->where(

                        'status',

                        'COMPLETED'

                    )
                    ->exists();







                if(!$completed)
                {





                    /*
                    |--------------------------------------------------------------------------
                    | Clinical Timeline - Medication Delayed
                    |--------------------------------------------------------------------------
                    */


                    $this->timelineService
                    ->recordMedicationDelayed(


                        $medication->resident_id,


                        $medication->medication->medicine_name
                        .
                        " delayed by "
                        .
                        $delayMinutes
                        .
                        " minutes.",


                        $medication->id



                    );









                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Duplicate AI Alert
                    |--------------------------------------------------------------------------
                    */


                    $existingAlert =


                    AiAlert::where(

                        'resident_id',

                        $medication->resident_id

                    )
                    ->where(

                        'alert_type',

                        'MEDICATION DELAY'

                    )
                    ->where(

                        'status',

                        'OPEN'

                    )
                    ->where(

                        'message',

                        'LIKE',

                        '%'
                        .
                        $medication->medication->medicine_name
                        .
                        '%'

                    )
                    ->first();








                    if(!$existingAlert)
                    {





                        /*
                        |--------------------------------------------------------------------------
                        | Create AI Alert
                        |--------------------------------------------------------------------------
                        */


                        $alert =

                        AiAlert::create([



                            'resident_id'=>

                                $medication->resident_id,



                            'alert_type'=>

                                'MEDICATION DELAY',



                            'severity'=>

                                'WARNING',



                            'message'=>

                                $medication
                                ->medication
                                ->medicine_name

                                .
                                ' for '

                                .
                                $medication
                                ->resident
                                ->full_name

                                .
                                ' has not been administered. Delay: '

                                .
                                $delayMinutes

                                .
                                ' minutes.',



                            'ai_confidence'=>

                                95,



                            'status'=>

                                'OPEN'


                        ]);









                        /*
                        |--------------------------------------------------------------------------
                        | Nurse Task
                        |--------------------------------------------------------------------------
                        */


                        NurseTask::create([



                            'resident_id'=>

                                $medication->resident_id,



                            'task_name'=>

                                'Medication Follow Up',



                            'description'=>

                                $medication
                                ->medication
                                ->medicine_name

                                .
                                ' administration overdue by '

                                .
                                $delayMinutes

                                .
                                ' minutes.',



                            'scheduled_time'=>

                                $scheduledDateTime,



                            'status'=>

                                'Pending'


                        ]);









                        /*
                        |--------------------------------------------------------------------------
                        | Notification
                        |--------------------------------------------------------------------------
                        */


                        Notification::create([



                            'user_id'=>

                                null,



                            'title'=>

                                'Medication Delay Alert',



                            'message'=>

                                $medication
                                ->medication
                                ->medicine_name

                                .
                                ' for '

                                .
                                $medication
                                ->resident
                                ->full_name

                                .
                                ' is overdue by '

                                .
                                $delayMinutes

                                .
                                ' minutes.',



                            'type'=>

                                'MEDICATION_DELAY',



                            'read_status'=>

                                0


                        ]);




                    }







                    $delayedMedications[]= [



                        'resident_id'=>

                            $medication->resident_id,



                        'resident'=>

                            $medication->resident->full_name,



                        'medicine'=>

                            $medication->medication->medicine_name,



                        'time_slot'=>

                            $medication->time_slot,



                        'scheduled_time'=>

                            $medication->scheduled_time,



                        'delay_minutes'=>

                            $delayMinutes,



                        'severity'=>

                            'WARNING'


                    ];



                }



            }



        }







        return $delayedMedications;



    }


    





}