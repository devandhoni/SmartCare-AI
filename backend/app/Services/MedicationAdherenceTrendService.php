<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use Carbon\Carbon;



class MedicationAdherenceTrendService
{


    public function calculate($residentId)
    {


        $resident =
            Resident::findOrFail($residentId);



        /*
        |--------------------------------------------------------------------------
        | Current 30 Days
        |--------------------------------------------------------------------------
        */


        $currentStart =
            Carbon::now()
            ->subDays(30)
            ->startOfDay();



        $currentEnd =
            Carbon::now()
            ->endOfDay();




        /*
        |--------------------------------------------------------------------------
        | Previous 30 Days
        |--------------------------------------------------------------------------
        */


        $previousStart =
            Carbon::now()
            ->subDays(60)
            ->startOfDay();



        $previousEnd =
            Carbon::now()
            ->subDays(31)
            ->endOfDay();




        $currentScore =
            abs(
                $this->calculatePeriodScore(
                    $residentId,
                    $currentStart,
                    $currentEnd
                )
            );



       $previousScore =
            abs(
                $this->calculatePeriodScore(
                    $residentId,
                    $previousStart,
                    $previousEnd
                )
            );





        /*
        |--------------------------------------------------------------------------
        | Trend Calculation
        |--------------------------------------------------------------------------
        */


        $difference =
            round(
                $currentScore - $previousScore
            );


        if($difference == 0)
        {

            $difference = 0;

        }





        /*
        |--------------------------------------------------------------------------
        | Trend Data Quality Assessment
        |--------------------------------------------------------------------------
        */


        $dataQuality = "GOOD";




        if(
            $previousScore == 0
            &&
            $currentScore < 10
        )
        {


            $trend =
                "INSUFFICIENT DATA";



            $note =
                "Insufficient historical medication data available for reliable trend analysis.";



            $dataQuality =
                "LIMITED";


        }
        else
        {


            if($difference >= 10)
            {


                $trend =
                    "IMPROVING";



                $note =
                    "Medication adherence has improved compared with previous period.";


            }
            elseif($difference <= -10)
            {


                $trend =
                    "DECLINING";



                $note =
                    "Medication adherence has decreased and requires monitoring.";


            }
            else
            {


                $trend =
                    "STABLE";



                $note =
                    "Medication adherence remains consistent.";


            }


        }







        return [


            'resident'=>$resident->full_name,



            'trend'=>$trend,



            'previous_period'=>[

                'score'=>$previousScore

            ],



            'current_period'=>[

                'score'=>$currentScore

            ],



            'change'=>$difference,



            'data_quality'=>$dataQuality,



            'clinical_note'=>$note



        ];

    }






    private function calculatePeriodScore(
        $residentId,
        $startDate,
        $endDate
    )
    {



        $completed =
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
            ->count();





        $medications =
            ResidentMedication::where(
                'resident_id',
                $residentId
            )
            ->get();





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






        if($scheduled == 0)
        {

            return 0;

        }






        return round(

            ($completed / $scheduled)
            *
            100

        );



    }


}