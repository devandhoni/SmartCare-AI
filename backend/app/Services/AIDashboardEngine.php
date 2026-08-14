<?php

namespace App\Services;


use App\Models\HealthRiskScore;
use App\Models\HealthPrediction;
use App\Models\AiAlert;
use App\Models\MedicationAdministrationRecord;
use App\Models\NurseTask;
use Carbon\Carbon;



class AIDashboardEngine
{


    protected $executiveSummary;

    protected $alertPerformance;

    protected $clinicalDecisionEngine;




    public function __construct(

        AIExecutiveSummaryEngine $executiveSummary,

        AIAlertPerformanceEngine $alertPerformance,

        ClinicalDecisionEngine $clinicalDecisionEngine

    )
    {


        $this->executiveSummary =
            $executiveSummary;


        $this->alertPerformance =
            $alertPerformance;


        $this->clinicalDecisionEngine =
            $clinicalDecisionEngine;


    }








    /*
    |--------------------------------------------------------------------------
    | Generate SmartCare AI Dashboard
    |--------------------------------------------------------------------------
    */


    public function generate()
    {


        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */


        $executive =

            $this->executiveSummary
            ->analyze();






        /*
        |--------------------------------------------------------------------------
        | Alert Performance
        |--------------------------------------------------------------------------
        */


        $alertAnalytics =

            $this->alertPerformance
            ->analyze();








        /*
        |--------------------------------------------------------------------------
        | Resident Risk Overview
        |--------------------------------------------------------------------------
        */


        $highRiskResidents =

            HealthRiskScore::whereIn(

                'risk_level',

                [

                    'HIGH',

                    'CRITICAL'

                ]

            )
            ->with('resident')
            ->get()
            ->map(function($risk){


                return [


                    'resident_id'=>
                        $risk->resident_id,


                    'resident'=>
                        $risk->resident?->full_name,


                    'risk_level'=>
                        $risk->risk_level,


                    'risk_score'=>
                        $risk->risk_score


                ];


            });










        /*
        |--------------------------------------------------------------------------
        | Active Critical Alerts
        |--------------------------------------------------------------------------
        */


        $activeAlerts =


            AiAlert::with('resident')

            ->where(
                'status',
                'OPEN'
            )

            ->whereIn(

                'severity',

                [

                    'HIGH',

                    'CRITICAL'

                ]

            )

            ->get()

            ->map(function($alert){


                return [


                    'alert_id'=>
                        $alert->id,


                    'resident_id'=>
                        $alert->resident_id,


                    'resident'=>
                        $alert->resident?->full_name,


                    'type'=>
                        $alert->alert_type,


                    'severity'=>
                        $alert->severity,


                    'message'=>
                        $alert->message


                ];


            });










        /*
        |--------------------------------------------------------------------------
        | Prediction Overview
        |--------------------------------------------------------------------------
        */


        $predictions =


            HealthPrediction::with('resident')

            ->latest()

            ->limit(10)

            ->get()

            ->map(function($prediction){


                return [


                    'resident_id'=>
                        $prediction->resident_id,


                    'resident'=>
                        $prediction->resident?->full_name,


                    'condition'=>
                        $prediction->prediction_type,


                    'risk_level'=>
                        $prediction->risk_level,


                    'prediction'=>
                        $prediction->prediction,


                    'confidence'=>
                        $prediction->confidence.'%',


                    'clinical_action'=>

                        $this->generateRecommendation(
                            $prediction
                        )


                ];


            });










        /*
        |--------------------------------------------------------------------------
        | Critical Resident Ranking + AI Decision
        |--------------------------------------------------------------------------
        */


        $criticalResidents =


            HealthRiskScore::whereIn(

                'risk_level',

                [

                    'HIGH',

                    'CRITICAL'

                ]

            )

            ->with('resident')

            ->orderBy(

                'risk_score',

                'desc'

            )

            ->get()

            ->map(function($risk){



                $clinicalDecision =

                    $this->clinicalDecisionEngine

                    ->analyze(

                        $risk->resident_id

                    );





                return [


                    'resident_id'=>

                        $risk->resident_id,


                    'resident'=>

                        $risk->resident?->full_name,


                    'risk_level'=>

                        $risk->risk_level,


                    'risk_score'=>

                        $risk->risk_score,



                    'priority'=>

                        $risk->risk_level === 'CRITICAL'

                        ?

                        'IMMEDIATE REVIEW'

                        :

                        'MONITOR CLOSELY',




                    /*
                    |--------------------------------------------------------------------------
                    | Explainable AI
                    |--------------------------------------------------------------------------
                    */


                    'clinical_decision'=>[


                        'decision_score'=>

                            $clinicalDecision['decision_score'],



                        'priority'=>

                            $clinicalDecision['priority'],



                        'risk_factors'=>

                            $clinicalDecision['risk_factors'],



                        'recommended_actions'=>

                            $clinicalDecision['recommended_actions']


                    ]


                ];


            });









        /*
        |--------------------------------------------------------------------------
        | Today's Clinical Activity
        |--------------------------------------------------------------------------
        */


        $today =
            Carbon::today();





        $todayAlerts =


            AiAlert::whereDate(

                'created_at',

                $today

            )

            ->count();





        $todayResolvedAlerts =


            AiAlert::whereDate(

                'resolved_at',

                $today

            )

            ->count();





        $todayCompletedTasks =


            NurseTask::where(

                'status',

                'Completed'

            )

            ->whereDate(

                'completed_time',

                $today

            )

            ->count();





        $todayMedicationCompleted =


            MedicationAdministrationRecord::where(

                'status',

                'COMPLETED'

            )

            ->whereDate(

                'completed_time',

                $today

            )

            ->count();









        /*
        |--------------------------------------------------------------------------
        | AI Recommendation Panel
        |--------------------------------------------------------------------------
        */


        $recommendations=[];





        if($criticalResidents->count()>0)
        {


            foreach($criticalResidents as $resident)
            {


                if($resident['risk_level']=="CRITICAL")
                {


                    $recommendations[] =

                        "Immediate clinical review required for "
                        .
                        $resident['resident']
                        .
                        ".";


                }


            }


        }





        if(
            $alertAnalytics['alert_summary']['open_alerts'] > 0
        )
        {


            $recommendations[] =

                "Review unresolved AI alerts requiring attention.";


        }





        if(
            $alertAnalytics['nurse_task_summary']['pending_tasks'] > 0
        )
        {


            $recommendations[] =

                "Follow up pending nurse clinical tasks.";


        }









        /*
        |--------------------------------------------------------------------------
        | Dashboard Response
        |--------------------------------------------------------------------------
        */


        return [



            'critical_residents'=>[


                'total'=>

                    count($criticalResidents),



                'residents'=>

                    $criticalResidents


            ],






            'today_activity'=>[


                'alerts_generated'=>

                    $todayAlerts,


                'alerts_resolved'=>

                    $todayResolvedAlerts,


                'completed_nurse_tasks'=>

                    $todayCompletedTasks,


                'completed_medications'=>

                    $todayMedicationCompleted


            ],






            'ai_recommendations'=>

                $recommendations,





            'dashboard_date'=>

                now()->format('Y-m-d'),






            'executive_summary'=>

                $executive,







            'alert_performance'=>

                $alertAnalytics,







            'risk_overview'=>[


                'high_risk_residents'=>

                    $highRiskResidents,


                'total_high_risk'=>

                    count($highRiskResidents)


            ],







            'active_alerts'=>[


                'total'=>

                    count($activeAlerts),


                'alerts'=>

                    $activeAlerts


            ],







            'recent_predictions'=>

                $predictions



        ];



    }









    /*
    |--------------------------------------------------------------------------
    | Generate Clinical Recommendation
    |--------------------------------------------------------------------------
    */


    private function generateRecommendation($prediction)
    {


        switch($prediction->prediction_type)
        {


            case 'Respiratory Risk':

                return
                "Immediate respiratory assessment recommended.";



            case 'Hypertension Risk':

                return
                "Monitor blood pressure and perform cardiovascular review.";



            case 'Diabetes Risk':

                return
                "Review glucose level and diabetes management plan.";



            case 'Infection Risk':

                return
                "Monitor temperature and assess possible infection symptoms.";



            default:

                return
                "Continue clinical monitoring.";


        }


    }



}