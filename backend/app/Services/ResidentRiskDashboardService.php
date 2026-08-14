<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\ClinicalTimeline;
use App\Services\HealthRiskAggregatorService;
use App\Services\ClinicalSummaryGeneratorService;
use App\Services\MedicationAnalyticsService;



class ResidentRiskDashboardService
{


    protected HealthRiskAggregatorService $riskService;

    protected ClinicalSummaryGeneratorService $summaryService;

    protected MedicationAnalyticsService $medicationService;



    public function __construct(

        HealthRiskAggregatorService $riskService,

        ClinicalSummaryGeneratorService $summaryService,

        MedicationAnalyticsService $medicationService

    )
    {

        $this->riskService =
            $riskService;


        $this->summaryService =
            $summaryService;


        $this->medicationService =
            $medicationService;

    }







    /*
    |--------------------------------------------------------------------------
    | Generate Resident Risk Dashboard
    |--------------------------------------------------------------------------
    */


    public function generate($residentId)
    {


        $resident =
            Resident::findOrFail($residentId);





        /*
        |--------------------------------------------------------------------------
        | Risk Calculation
        |--------------------------------------------------------------------------
        */


        $risk =

            $this->riskService
            ->calculate($residentId);







        /*
        |--------------------------------------------------------------------------
        | AI Clinical Summary
        |--------------------------------------------------------------------------
        */


        $summary =

            $this->summaryService
            ->generate($residentId);







        /*
        |--------------------------------------------------------------------------
        | Medication Analytics
        |--------------------------------------------------------------------------
        */


        $medication =

            $this->medicationService
            ->calculateResidentCompliance(
                $residentId
            );







        /*
        |--------------------------------------------------------------------------
        | Active Alerts
        |--------------------------------------------------------------------------
        */


        $alerts =

            AiAlert::where(

                'resident_id',

                $residentId

            )
            ->where(

                'status',

                'OPEN'

            )
            ->orderBy(

                'created_at',

                'desc'

            )
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Latest Clinical Condition
        |--------------------------------------------------------------------------
        */


        $latestTimeline =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->latest(
                'event_date'
            )
            ->first();








        return [



            "resident"=>[


                "id"=>

                    $resident->id,


                "name"=>

                    $resident->full_name


            ],





            "risk_dashboard"=>[



                "risk_score"=>

                    $risk['risk_score'],



                "risk_level"=>

                    $risk['risk_level'],



                "active_alerts"=>

                    $alerts->count(),




                "medication_compliance"=>

                    $medication['compliance_rate'],




                "latest_condition"=>

                    $latestTimeline

                    ?

                    $latestTimeline->event_title

                    :

                    "No clinical event",





                "ai_summary"=>

                    $summary['clinical_summary']




            ],





            "risk_contributors"=>

                $risk['contributors'],





            "active_alert_details"=>

                $alerts->map(function($alert){


                    return [


                        "type"=>

                            $alert->alert_type,


                        "severity"=>

                            $alert->severity,


                        "message"=>

                            $alert->message,


                        "confidence"=>

                            $alert->ai_confidence."%"


                    ];


                })




        ];



    }



}