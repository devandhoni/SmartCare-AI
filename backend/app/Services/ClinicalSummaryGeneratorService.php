<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\MedicationAdministrationRecord;
use App\Models\ResidentMedication;



class ClinicalSummaryGeneratorService
{


    /*
    |--------------------------------------------------------------------------
    | Generate AI Clinical Summary
    |--------------------------------------------------------------------------
    */


    public function generate($residentId)
    {


        $resident = Resident::findOrFail($residentId);



        $summary = [];

        $findings = [];

        $recommendations = [];





        /*
        |--------------------------------------------------------------------------
        | Get Active AI Alerts
        |--------------------------------------------------------------------------
        */


        $criticalAlerts =

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





        if($criticalAlerts->count() > 0)
        {


            foreach($criticalAlerts as $alert)
            {


                $findings[] =

                    $alert->message;


            }



            $recommendations[] =

                "Immediate clinical review required due to active AI alerts.";

        }







        /*
        |--------------------------------------------------------------------------
        | Medication Compliance Analysis
        |--------------------------------------------------------------------------
        */


        $totalMedication =

            ResidentMedication::where(
                'resident_id',
                $residentId
            )
            ->count();




        $completedMedication =

            MedicationAdministrationRecord::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'COMPLETED'
            )
            ->count();






        $compliance = 0;



        if($totalMedication > 0)
        {


            $compliance = round(

                (
                    $completedMedication
                    /
                    $totalMedication
                )
                *
                100,

                1

            );


        }







        if($compliance < 70 && $totalMedication > 0)
        {


            $findings[] =

                "Medication compliance is below recommended level: "
                .
                $compliance
                .
                "%";



            $recommendations[] =

                "Review medication adherence and nursing follow-up.";

        }







        /*
        |--------------------------------------------------------------------------
        | Generate Overall Summary
        |--------------------------------------------------------------------------
        */


        if(count($findings) == 0)
        {


            $summaryText =

                "Resident condition appears stable based on available clinical data.";



            $recommendations[] =

                "Continue routine monitoring.";

        }

        else
        {


            $summaryText =

                "Resident shows increased clinical risk due to "
                .
                implode(
                    " and ",
                    $findings
                )
                .
                ".";


        }







        return [


            "resident"=>

                $resident->full_name,



            "overall_status"=>

                count($criticalAlerts) > 0

                ?

                "HIGH RISK"

                :

                "STABLE",





            "clinical_summary"=>

                $summaryText,





            "key_findings"=>

                $findings,





            "recommended_actions"=>

                $recommendations



        ];



    }



}