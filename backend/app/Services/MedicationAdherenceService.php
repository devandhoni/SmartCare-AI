<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use Carbon\Carbon;



class MedicationAdherenceService
{


    public function calculate($residentId)
    {


        $resident =
            Resident::findOrFail($residentId);



        $startDate =
            Carbon::now()
            ->subDays(30)
            ->startOfDay();



        $endDate =
            Carbon::now()
            ->endOfDay();





        /*
        |--------------------------------------------------------------------------
        | Get Medication Records
        |--------------------------------------------------------------------------
        */


        $completedRecords =
            MedicationAdministrationRecord::where(
                'resident_id',
                $residentId
            )
            ->whereBetween(
                'administered_date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString()
                ]
            )
            ->where(
                'status',
                'COMPLETED'
            )
            ->get();





        $completed =
            $completedRecords->count();





        /*
        |--------------------------------------------------------------------------
        | Get Resident Medication Plan
        |--------------------------------------------------------------------------
        */


        $medications =
            ResidentMedication::where(
                'resident_id',
                $residentId
            )
            ->get();






        /*
        |--------------------------------------------------------------------------
        | No Medication Data Handling
        |--------------------------------------------------------------------------
        */


        if(
            $medications->count() == 0
            &&
            $completed == 0
        )
        {


            return [


                'resident'=>
                    $resident->full_name,


                'analysis_period'=>[

                    'from'=>
                        $startDate->toDateString(),

                    'to'=>
                        $endDate->toDateString()

                ],



                'adherence_score'=>null,


                'risk_level'=>
                    'INSUFFICIENT DATA',



                'data_quality'=>
                    'LIMITED',



                'statistics'=>[

                    'scheduled_doses'=>0,

                    'completed_doses'=>0,

                    'delayed_doses'=>0,

                    'missed_doses'=>0

                ]


            ];


        }







        /*
        |--------------------------------------------------------------------------
        | Delayed Medication
        |--------------------------------------------------------------------------
        */


        $delayed = 0;



        foreach($completedRecords as $record)
        {


            if(
                $record->scheduled_time
                &&
                $record->completed_time
                &&
                Carbon::parse(
                    $record->completed_time
                )
                ->
                greaterThan(
                    Carbon::parse(
                        $record->scheduled_time
                    )
                    ->
                    addMinutes(15)
                )
            )
            {


                $delayed++;


            }


        }








        /*
        |--------------------------------------------------------------------------
        | Scheduled Medication Count
        |--------------------------------------------------------------------------
        */


        $scheduled = 0;



        foreach($medications as $medication)
        {


            $start =
                Carbon::parse(
                    $medication->start_date
                );



            if($start < $startDate)
            {

                $start = $startDate;

            }




            $days =
                $start->diffInDays(
                    $endDate
                )
                +1;





            switch(
                strtolower(
                    $medication->frequency
                )
            )
            {


                case 'once daily':

                    $multiplier = 1;

                    break;



                case 'twice daily':

                    $multiplier = 2;

                    break;



                case 'three times daily':

                    $multiplier = 3;

                    break;



                default:

                    $multiplier = 0;


            }





            $scheduled +=
                round(
                    $days * $multiplier
                );


        }







        /*
        |--------------------------------------------------------------------------
        | Missed Medication
        |--------------------------------------------------------------------------
        */


        $missed =
            max(
                $scheduled - $completed,
                0
            );







        /*
        |--------------------------------------------------------------------------
        | AI Adherence Score
        |--------------------------------------------------------------------------
        */


        if($scheduled > 0)
        {


            $score =
                round(
                    ($completed / $scheduled)
                    *
                    100
                );


        }
        else
        {


            $score = null;


        }








        /*
        |--------------------------------------------------------------------------
        | Risk Classification
        |--------------------------------------------------------------------------
        */


        if($score === null)
        {


            $risk =
                "INSUFFICIENT DATA";


        }
        elseif($score >=90)
        {


            $risk =
                "LOW";


        }
        elseif($score >=70)
        {


            $risk =
                "MEDIUM";


        }
        else
        {


            $risk =
                "HIGH";


        }








        /*
        |--------------------------------------------------------------------------
        | Data Quality
        |--------------------------------------------------------------------------
        */


        $dataQuality =
            "GOOD";



        if($completed <5)
        {


            $dataQuality =
                "LIMITED";


        }








        return [


            'resident'=>
                $resident->full_name,



            'analysis_period'=>[


                'from'=>
                    $startDate->toDateString(),


                'to'=>
                    $endDate->toDateString()


            ],





            'adherence_score'=>
                $score,



            'risk_level'=>
                $risk,



            'data_quality'=>
                $dataQuality,





            'statistics'=>[


                'scheduled_doses'=>
                    $scheduled,


                'completed_doses'=>
                    $completed,


                'delayed_doses'=>
                    $delayed,


                'missed_doses'=>
                    $missed


            ]


        ];



    }


}