<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\ClinicalTimeline;
use App\Models\MedicationAdministrationRecord;
use App\Models\ResidentMedication;



class HealthRiskAggregatorService
{


    /*
    |--------------------------------------------------------------------------
    | Calculate Overall Resident Health Risk
    |--------------------------------------------------------------------------
    */


    public function calculate($residentId)
    {


        $riskScore = 0;


        $contributors = [];




        /*
        |--------------------------------------------------------------------------
        | 1. Check Critical AI Alerts
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
            ->count();




        if($criticalAlerts > 0)
        {


            $riskScore += 50;


            $contributors[] = [


                'factor'=>

                    'Critical AI Alert',


                'impact'=>

                    'CRITICAL',


                'details'=>

                    $criticalAlerts.
                    ' active critical alert(s)'


            ];


        }







        /*
        |--------------------------------------------------------------------------
        | 2. Medication Compliance Risk
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



            if($compliance < 70)
            {


                $riskScore += 25;


                $contributors[]=[


                    'factor'=>

                        'Medication Compliance',


                    'impact'=>

                        'HIGH',


                    'details'=>

                        'Compliance rate '.$compliance.'%'


                ];


            }


            elseif($compliance < 90)
            {


                $riskScore += 10;


                $contributors[]=[


                    'factor'=>

                        'Medication Compliance',


                    'impact'=>

                        'MEDIUM',


                    'details'=>

                        'Compliance rate '.$compliance.'%'


                ];


            }



        }








        /*
        |--------------------------------------------------------------------------
        | 3. Recent AI Clinical Events
        |--------------------------------------------------------------------------
        */


        $recentClinicalEvents =

            ClinicalTimeline::where(

                'resident_id',

                $residentId

            )
            ->where(

                'event_type',

                'AI_ALERT'

            )
            ->where(

                'created_at',

                '>=',

                now()->subDays(7)

            )
            ->count();





        if($recentClinicalEvents > 0)
        {


            $riskScore += 15;


            $contributors[]=[


                'factor'=>

                    'Recent AI Clinical Events',


                'impact'=>

                    'MEDIUM',


                'details'=>

                    $recentClinicalEvents.
                    ' AI events in last 7 days'


            ];


        }








        /*
        |--------------------------------------------------------------------------
        | Risk Level
        |--------------------------------------------------------------------------
        */


        if($riskScore >=80)
        {


            $riskLevel="CRITICAL";


        }
        elseif($riskScore >=50)
        {


            $riskLevel="HIGH";


        }
        elseif($riskScore >=25)
        {


            $riskLevel="MEDIUM";


        }
        else
        {


            $riskLevel="LOW";


        }







        return [


            'risk_score'=>

                min($riskScore,100),



            'risk_level'=>

                $riskLevel,



            'contributors'=>

                $contributors



        ];



    }



}