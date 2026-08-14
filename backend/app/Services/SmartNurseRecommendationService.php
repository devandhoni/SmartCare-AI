<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\Resident;
use App\Models\ClinicalTimeline;



class SmartNurseRecommendationService
{


    /*
    |--------------------------------------------------------------------------
    | Generate Nursing Recommendations
    |--------------------------------------------------------------------------
    */


    public function generate($residentId)
    {


        $resident = Resident::findOrFail($residentId);



        $actions = [];

        $priority = "NORMAL";

        $monitoringFrequency = "Routine monitoring";






        /*
        |--------------------------------------------------------------------------
        | Check Active Critical Alerts
        |--------------------------------------------------------------------------
        */


        $criticalAlerts =

            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'severity',
                'CRITICAL'
            )
            ->where(
                'status',
                'OPEN'
            )
            ->get();





        if($criticalAlerts->count() > 0)
        {


            $priority = "URGENT";


            $actions[] =

                "Perform immediate resident assessment.";


            $actions[] =

                "Repeat vital signs measurement.";

            
            $actions[] =

                "Escalate clinical condition to doctor.";


            $monitoringFrequency =

                "Every 2 hours";


        }








        /*
        |--------------------------------------------------------------------------
        | Check Medication Delay
        |--------------------------------------------------------------------------
        */


        $medicationDelay =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->where(

                'event_type',

                'MEDICATION_DELAYED'

            )
            ->where(

                'created_at',

                '>=',

                now()->subDays(7)

            )
            ->count();






        if($medicationDelay > 0)
        {


            $priority =

                $priority == "URGENT"

                ?

                "URGENT"

                :

                "HIGH";



            $actions[] =

                "Review delayed medication administration.";

            
            $actions[] =

                "Confirm medication availability and schedule.";

        }








        /*
        |--------------------------------------------------------------------------
        | Check Oxygen Related Risk
        |--------------------------------------------------------------------------
        */


        $oxygenIssue =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->where(

                'event_description',

                'LIKE',

                '%Oxygen%'

            )
            ->count();






        if($oxygenIssue > 0)
        {


            $actions[] =

                "Monitor oxygen saturation closely.";

            
            $actions[] =

                "Prepare oxygen support if clinically required.";


        }








        /*
        |--------------------------------------------------------------------------
        | Default Recommendation
        |--------------------------------------------------------------------------
        */


        if(count($actions)==0)
        {


            $actions[] =

                "Continue routine nursing observation.";


        }







        return [


            "resident"=>

                $resident->full_name,



            "priority"=>

                $priority,



            "nursing_actions"=>

                $actions,



            "monitoring_frequency"=>

                $monitoringFrequency



        ];



    }



}