<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\HealthRiskScore;



class AICommandCenterEngine
{


    protected AIExecutiveSummaryEngine $executiveSummary;

    protected ClinicalPerformanceDashboardEngine $clinicalPerformance;



    public function __construct(
        AIExecutiveSummaryEngine $executiveSummary,
        ClinicalPerformanceDashboardEngine $clinicalPerformance
    )
    {

        $this->executiveSummary =
            $executiveSummary;


        $this->clinicalPerformance =
            $clinicalPerformance;

    }







    /*
    |--------------------------------------------------------------------------
    | Generate AI Command Center
    |--------------------------------------------------------------------------
    */


    public function analyze()
    {



        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */


        $summary =
            $this->executiveSummary->analyze();









        /*
        |--------------------------------------------------------------------------
        | Clinical Performance
        |--------------------------------------------------------------------------
        */


        $performance =
            $this->clinicalPerformance->analyze();











        /*
        |--------------------------------------------------------------------------
        | Priority Resident Monitoring
        |--------------------------------------------------------------------------
        */


        $priorityResidents = [];



        $criticalResidents =

            HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )

            ->with('resident')

            ->orderByDesc(
                'risk_score'
            )

            ->get();







        foreach($criticalResidents as $risk)
        {


            if($risk->resident)
            {


                $priorityResidents[] = [


                    'resident_id'=>
                    $risk->resident_id,


                    'resident_name'=>
                    $risk->resident->full_name,


                    'priority'=>
                    'CRITICAL',


                    'risk_score'=>
                    $risk->risk_score,


                    'recommendation'=>
                    'Immediate clinical monitoring required.'


                ];



            }


        }








        if(empty($priorityResidents))
        {


            $priorityResidents[] = [


                'message'=>
                'No critical resident requires immediate attention.'


            ];


        }









        /*
        |--------------------------------------------------------------------------
        | Latest AI Decision
        |--------------------------------------------------------------------------
        */


        $latestAlert =

            AiAlert::with('resident')

            ->where(
                'status',
                'OPEN'
            )

            ->where(
                'severity',
                'CRITICAL'
            )

            ->latest(
                'created_on'
            )

            ->first();





        $latestAIDecision = null;





        if($latestAlert)
        {


            $riskFactors = [];



            $message = 
                $latestAlert->message;



            if($message)
            {


                $parts = explode(
                    ',',
                    $message
                );


                foreach($parts as $part)
                {


                    $riskFactors[] =
                        trim($part);


                }


            }






            $latestAIDecision = [


                'resident_id'=>
                $latestAlert->resident_id,



                'resident_name'=>
                $latestAlert->resident
                ?
                $latestAlert->resident->full_name
                :
                'Unknown',



                'priority'=>
                $latestAlert->severity,



                'decision_score'=>
                (float)
                $latestAlert->ai_confidence,



                'risk_factors'=>
                $riskFactors,



                'generated_at'=>
                $latestAlert->created_at



            ];



        }









        /*
        |--------------------------------------------------------------------------
        | System Health Status
        |--------------------------------------------------------------------------
        */


        $activeAlerts =

            AiAlert::where(
                'status',
                'OPEN'
            )

            ->count();







        $status =

            $activeAlerts > 0

            ?

            'ATTENTION REQUIRED'

            :

            'STABLE';













        /*
        |--------------------------------------------------------------------------
        | Return AI Command Center
        |--------------------------------------------------------------------------
        */


        return [



            'system_status'=>
            $status,





            'executive_summary'=>
            $summary,







            'clinical_overview'=>[



                'total_residents'=>
                Resident::count(),



                'critical_cases'=>

                HealthRiskScore::where(
                    'risk_level',
                    'CRITICAL'
                )

                ->distinct(
                    'resident_id'
                )

                ->count(
                    'resident_id'
                ),



                'active_alerts'=>
                $activeAlerts



            ],









            'ai_performance'=>[



                'predictions_generated'=>
                HealthPrediction::count(),



                'high_risk_predictions'=>

                HealthPrediction::whereIn(
                    'risk_level',
                    [
                        'HIGH',
                        'CRITICAL'
                    ]
                )

                ->count()



            ],









            'clinical_performance'=>
            $performance,








            'priority_attention'=>
            $priorityResidents,








            /*
            | NEW
            | Latest AI Clinical Decision
            */

            'latest_ai_decision'=>
            $latestAIDecision



        ];



    }



}