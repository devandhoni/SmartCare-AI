<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\MedicineInventory;
use App\Models\AiAlert;
use App\Models\NurseTask;
use App\Models\MedicationAdministrationRecord;

use Carbon\Carbon;



class NurseDashboardService
{


    protected $adherenceService;

    protected $trendService;

    protected $clinicalDecisionEngine;




    public function __construct(

        MedicationAdherenceService $adherenceService,

        MedicationAdherenceTrendService $trendService,

        ClinicalDecisionEngine $clinicalDecisionEngine

    )
    {


        $this->adherenceService =
            $adherenceService;


        $this->trendService =
            $trendService;


        $this->clinicalDecisionEngine =
            $clinicalDecisionEngine;


    }









    public function getDashboard($residentId)
    {


        /*
        |--------------------------------------------------------------------------
        | Resident
        |--------------------------------------------------------------------------
        */


        $resident =
            Resident::where(
                'id',
                $residentId
            )
            ->where(
                'status',
                'Active'
            )
            ->firstOrFail();








        /*
        |--------------------------------------------------------------------------
        | Clinical Intelligence
        |--------------------------------------------------------------------------
        */


        $clinicalDecision =
            $this->clinicalDecisionEngine
            ->analyze(
                $residentId
            );









        /*
        |--------------------------------------------------------------------------
        | Medication Intelligence
        |--------------------------------------------------------------------------
        */


        $adherence =
            $this->adherenceService
            ->calculate(
                $residentId
            );




        $trend =
            $this->trendService
            ->calculate(
                $residentId
            );









        /*
        |--------------------------------------------------------------------------
        | Today's Medication Status
        |--------------------------------------------------------------------------
        */


        $today =
            Carbon::today();




        $records =
            MedicationAdministrationRecord::where(
                'resident_id',
                $residentId
            )
            ->whereDate(
                'administered_date',
                $today
            )
            ->get();





        $completed =
            $records
            ->where(
                'status',
                'COMPLETED'
            )
            ->count();




        $delayed = 0;



        foreach($records as $record)
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
        | Medicine Stock Status
        |--------------------------------------------------------------------------
        */


        $lowStock =
            MedicineInventory::whereColumn(
                'quantity',
                '<=',
                'minimum_stock'
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Active AI Alerts
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
            ->count();



        /*
        |--------------------------------------------------------------------------
        | AI Alert History
        |--------------------------------------------------------------------------
        */


        $totalAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->count();



        $activeAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'OPEN'
            )
            ->count();



        $criticalAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'severity',
                'CRITICAL'
            )
            ->count();







        /*
        |--------------------------------------------------------------------------
        | Pending Nurse Tasks
        |--------------------------------------------------------------------------
        */


        $tasks =
            NurseTask::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'Pending'
            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Clinical Attention
        |--------------------------------------------------------------------------
        */


        $attention = [];





        /*
        |--------------------------------------------------------------------------
        | AI Clinical Risk Attention
        |--------------------------------------------------------------------------
        */


        if(
            isset($clinicalDecision['priority'])
            &&
            $clinicalDecision['priority'] == "CRITICAL"
        )
        {

            $attention[] = [

                "priority"=>"CRITICAL",

                "message"=>
                "Critical vital abnormalities require immediate clinical attention."

            ];

        }



        elseif(
            isset($clinicalDecision['priority'])
            &&
            $clinicalDecision['priority'] == "HIGH"
        )
        {

            $attention[] = [

                "priority"=>"HIGH",

                "message"=>
                "Resident requires increased clinical monitoring."

            ];

        }







        /*
        |--------------------------------------------------------------------------
        | Medication Attention
        |--------------------------------------------------------------------------
        */


        if(
            $adherence['risk_level']=="HIGH"
        )
        {

            $attention[] = [

                "priority"=>"HIGH",

                "message"=>
                "Medication adherence requires monitoring"

            ];

        }







        /*
        |--------------------------------------------------------------------------
        | Medicine Stock Attention
        |--------------------------------------------------------------------------
        */


        if($lowStock > 0)
        {

            $attention[] = [

                "priority"=>"MEDIUM",

                "message"=>
                "Medicine stock requires attention"

            ];

        }







        /*
        |--------------------------------------------------------------------------
        | Active AI Alert Attention
        |--------------------------------------------------------------------------
        */


        if($alerts > 0)
        {

            $attention[] = [

                "priority"=>"CRITICAL",

                "message"=>
                "Active AI alerts require review"

            ];

        }







        /*
        |--------------------------------------------------------------------------
        | Return Dashboard
        |--------------------------------------------------------------------------
        */


        return [



            'resident'=>
                $resident->full_name,






            'medication_summary'=>[


                'adherence_score'=>
                    $adherence['adherence_score'],


                'risk_level'=>
                    $adherence['risk_level'],


                'trend'=>
                    $trend['trend'],


                'data_quality'=>
                    $trend['data_quality']


            ],








            'clinical_intelligence'=>[


                'priority'=>
                    $clinicalDecision['priority'],


                'recommended_action'=>
                    $clinicalDecision['recommended_action'],


                'clinical_action_plan'=>
                    $clinicalDecision['clinical_action_plan']


            ],








            'today_medication'=>[


                'completed'=>
                    $completed,


                'delayed'=>
                    $delayed


            ],








            'stock_status'=>[


                'low_stock_items'=>
                    $lowStock


            ],


            'alerts'=>[

            'active'=>
                $activeAlerts,

            'total_history'=>
                $totalAlerts,

            'critical_history'=>
                $criticalAlerts

            ],


            'pending_tasks'=>
                $tasks,


            'clinical_attention'=>
                $attention



        ];



    }



}