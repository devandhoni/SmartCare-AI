<?php

namespace App\Services;


use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use App\Models\AiAlert;



class MedicationAnalyticsService
{


    /*
    |--------------------------------------------------------------------------
    | Calculate Resident Medication Compliance
    |--------------------------------------------------------------------------
    */


    public function calculateResidentCompliance($residentId)
    {


        /*
        |--------------------------------------------------------------------------
        | Total Scheduled Medication
        |--------------------------------------------------------------------------
        */


        $totalScheduled =

            ResidentMedication::where(

                'resident_id',

                $residentId

            )
            ->count();







        /*
        |--------------------------------------------------------------------------
        | Completed Medication
        |--------------------------------------------------------------------------
        */


        $completed =

            MedicationAdministrationRecord::where(

                'resident_id',

                $residentId

            )
            ->where(

                'status',

                'COMPLETED'

            )
            ->count();








        /*
        |--------------------------------------------------------------------------
        | Delayed Medication
        |--------------------------------------------------------------------------
        */


        $delayed =

            AiAlert::where(

                'resident_id',

                $residentId

            )
            ->where(

                'alert_type',

                'MEDICATION DELAY'

            )
            ->count();










        /*
        |--------------------------------------------------------------------------
        | Missed Medication
        |--------------------------------------------------------------------------
        */


        $missed =

            MedicationAdministrationRecord::where(

                'resident_id',

                $residentId

            )
            ->where(

                'status',

                'MISSED'

            )
            ->count();










        /*
        |--------------------------------------------------------------------------
        | Compliance Calculation
        |--------------------------------------------------------------------------
        */


        $complianceRate = 0;



        if($totalScheduled > 0)
        {


            $complianceRate = round(

                (

                    $completed

                    /

                    $totalScheduled

                )

                *

                100,

                1

            );


        }









        /*
        |--------------------------------------------------------------------------
        | Risk Classification
        |--------------------------------------------------------------------------
        */


        if($complianceRate >= 90)
        {

            $riskLevel = "LOW";

        }
        elseif($complianceRate >=70)
        {

            $riskLevel = "MEDIUM";

        }
        else
        {

            $riskLevel = "HIGH";

        }









        return [


            'total_scheduled'=>

                $totalScheduled,


            'completed'=>

                $completed,


            'delayed'=>

                $delayed,


            'missed'=>

                $missed,


            'compliance_rate'=>

                $complianceRate.'%',


            'risk_level'=>

                $riskLevel


        ];


    }



}