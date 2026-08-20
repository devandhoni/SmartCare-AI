<?php

namespace App\Services;


use App\Models\AiClinicalOutcome;



class AIOutcomePerformanceEngine
{


    /*
    |--------------------------------------------------------------------------
    | Analyse AI Clinical Outcome Performance
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {


        /*
        |--------------------------------------------------------------------------
        | Total Outcome Records
        |--------------------------------------------------------------------------
        */


        $totalOutcomes =
            AiClinicalOutcome::count();






        /*
        |--------------------------------------------------------------------------
        | Average AI Accuracy
        |--------------------------------------------------------------------------
        */


        $averageAccuracy =
            AiClinicalOutcome::avg(
                'ai_accuracy_score'
            );






        /*
        |--------------------------------------------------------------------------
        | Outcome Distribution
        |--------------------------------------------------------------------------
        */


        $improved =

            AiClinicalOutcome::where(
                'outcome_status',
                'IMPROVED'
            )
            ->count();




        $stable =

            AiClinicalOutcome::where(
                'outcome_status',
                'STABLE'
            )
            ->count();




        $deteriorated =

            AiClinicalOutcome::where(
                'outcome_status',
                'DETERIORATED'
            )
            ->count();




        $unknown =

            AiClinicalOutcome::where(
                'outcome_status',
                'UNKNOWN'
            )
            ->count();








        /*
        |--------------------------------------------------------------------------
        | Successful Intervention Rate
        |--------------------------------------------------------------------------
        */


        $successfulInterventions =

            $improved + $stable;





        $successRate = 0;



        if($totalOutcomes > 0)
        {


            $successRate = round(

                (
                    $successfulInterventions
                    /
                    $totalOutcomes
                )
                *
                100,

                2

            );


        }









        /*
        |--------------------------------------------------------------------------
        | Return Intelligence Data
        |--------------------------------------------------------------------------
        */


        return [



            'total_outcomes_recorded'=>

                $totalOutcomes,





            'average_ai_accuracy'=>

                round(
                    $averageAccuracy ?? 0,
                    2
                ),






            'successful_interventions'=>

                $successfulInterventions,






            'intervention_success_rate'=>

                $successRate,






            'outcome_distribution'=>[


                'IMPROVED'=>

                    $improved,


                'STABLE'=>

                    $stable,


                'DETERIORATED'=>

                    $deteriorated,


                'UNKNOWN'=>

                    $unknown


            ]



        ];



    }



}