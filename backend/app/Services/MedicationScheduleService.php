<?php

namespace App\Services;


use App\Models\ResidentMedication;
use App\Models\NurseTask;
use App\Models\Notification;
use App\Models\MedicationAdministrationRecord;
use Carbon\Carbon;


class MedicationScheduleService
{


    /*
    |--------------------------------------------------------------------------
    | Check Due Medications
    |--------------------------------------------------------------------------
    */

    public function checkDueMedications()
    {


        $now = Carbon::now();


        $currentTime = $now->format('H:i:s');



        $medications = ResidentMedication::with(
            'resident',
            'medication'
        )
        ->whereNotNull(
            'scheduled_time'
        )
        ->get();



        $dueMedications = [];



        foreach($medications as $medication)
        {


            /*
            |--------------------------------------------------------------------------
            | Check Time Difference
            |--------------------------------------------------------------------------
            */


            $scheduled =
                Carbon::parse(
                    $medication->scheduled_time
                );



            $current =
                Carbon::parse(
                    $currentTime
                );



            $difference =
                $current->diffInMinutes(
                    $scheduled,
                    false
                );



            /*
            |--------------------------------------------------------------------------
            | Medication Due Window
            |
            | 0 to 30 minutes before medication time
            |--------------------------------------------------------------------------
            */


            if(
                $difference <= 0 &&
                $difference >= -30
            )
            {


                /*
                |--------------------------------------------------------------------------
                | Check Already Completed
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
    | Create Nurse Task
    |--------------------------------------------------------------------------
    */


    $existingTask =
        NurseTask::where(
            'resident_id',
            $medication->resident_id
        )
        ->where(
            'task_name',
            'Administer Medication'
        )
        ->whereDate(
            'scheduled_time',
            today()
        )
        ->where(
            'status',
            'Pending'
        )
        ->first();



                if(!$existingTask)
                {


                    NurseTask::create([


                        'resident_id'=>
                            $medication->resident_id,


                        'task_name'=>
                            'Administer Medication',


                        'description'=>

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
                            ' is due at '
                            .
                            $medication
                            ->scheduled_time,


                        'scheduled_time'=>
                            Carbon::parse(
                                $medication->scheduled_time
                            ),


                        'status'=>
                            'Pending'


                    ]);



                    /*
                    |--------------------------------------------------------------------------
                    | Create Notification
                    |--------------------------------------------------------------------------
                    */


                    Notification::create([


                        'user_id'=>
                            null,


                        'title'=>
                            'Medication Reminder',


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
                            ' is due at '
                            .
                            $medication->scheduled_time,


                        'type'=>
                            'MEDICATION',


                        'read_status'=>
                            0


                    ]);



                }



                $dueMedications[] = [

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


                    'minutes_difference'=>
                        abs($difference)

                ];

            }


            }


        }



        return $dueMedications;


    }


}