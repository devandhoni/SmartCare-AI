<?php

namespace App\Services;


use App\Models\AiAlert;
use App\Models\Notification;

use App\Services\HealthPredictionAnalyzer;
use App\Services\ClinicalRecommendationAnalyzer;
use App\Services\AlertEscalationEngine;
use App\Services\AlertActionService;
use App\Services\ClinicalTimelineService;



class AIHealthAnalyzer
{


    protected HealthRiskAnalyzer $healthRiskAnalyzer;

    protected HealthPredictionAnalyzer $healthPredictionAnalyzer;

    protected ClinicalRecommendationAnalyzer $clinicalRecommendationAnalyzer;

    protected AlertEscalationEngine $alertEscalationEngine;

    protected AlertActionService $alertActionService;

    protected ClinicalTimelineService $clinicalTimelineService;



    public function __construct(

        HealthRiskAnalyzer $healthRiskAnalyzer,

        HealthPredictionAnalyzer $healthPredictionAnalyzer,

        ClinicalRecommendationAnalyzer $clinicalRecommendationAnalyzer,

        AlertEscalationEngine $alertEscalationEngine,

        AlertActionService $alertActionService,

        ClinicalTimelineService $clinicalTimelineService

    )
    {


        $this->healthRiskAnalyzer =
            $healthRiskAnalyzer;


        $this->healthPredictionAnalyzer =
            $healthPredictionAnalyzer;


        $this->clinicalRecommendationAnalyzer =
            $clinicalRecommendationAnalyzer;


        $this->alertEscalationEngine =
            $alertEscalationEngine;


        $this->alertActionService =
            $alertActionService;


        $this->clinicalTimelineService =
            $clinicalTimelineService;


    }









    public function analyze($vital)
    {


        $alerts = [];



        /*
        |--------------------------------------------------------------------------
        | Multi-System Clinical Deterioration Detection
        |--------------------------------------------------------------------------
        */


        $criticalIndicators = 0;


        $clinicalFindings = [];





        if(
            $vital->blood_pressure_systolic >=160 ||
            $vital->blood_pressure_diastolic >=100
        )
        {

            $criticalIndicators++;

            $clinicalFindings[] =
            "Severe hypertension detected";

        }






        if(
            $vital->oxygen_level <92
        )
        {

            $criticalIndicators++;

            $clinicalFindings[] =
            "Low oxygen saturation detected";

        }






        if(
            $vital->temperature >=38
        )
        {

            $criticalIndicators++;

            $clinicalFindings[] =
            "Elevated temperature detected";

        }






        if(
            $vital->blood_glucose >=11
        )
        {

            $criticalIndicators++;

            $clinicalFindings[] =
            "High blood glucose detected";

        }









        /*
        |--------------------------------------------------------------------------
        | Generate AI Clinical Event
        |--------------------------------------------------------------------------
        */


        if($criticalIndicators >=2)
        {


            $alerts[] = [


                'type'=>
                'Critical Multi-System Deterioration',


                'severity'=>
                'CRITICAL',


                'message'=>

                'Multiple abnormal vital signs detected: '
                .
                implode(
                    ', ',
                    $clinicalFindings
                )
                .
                '. Immediate clinical intervention required.',


                'confidence'=>97.00


            ];


        }







        else
        {


            if(
                $vital->blood_pressure_systolic >=160 ||
                $vital->blood_pressure_diastolic >=100
            )
            {


                $alerts[]=[


                    'type'=>'High Blood Pressure',

                    'severity'=>'CRITICAL',

                    'message'=>
                    'Blood pressure exceeds safe threshold. Immediate monitoring required.',

                    'confidence'=>95.50


                ];


            }







            if(
                $vital->oxygen_level <92
            )
            {


                $alerts[]=[


                    'type'=>'Low Oxygen Level',

                    'severity'=>'CRITICAL',

                    'message'=>
                    'Oxygen saturation is below safe level.',

                    'confidence'=>94.00


                ];


            }







            if(
                $vital->temperature >=38
            )
            {


                $alerts[]=[


                    'type'=>'High Temperature',

                    'severity'=>'HIGH',

                    'message'=>
                    'Possible fever detected.',

                    'confidence'=>90.00


                ];


            }


        }




        /*
|--------------------------------------------------------------------------
| Clinical Event Consolidation
|--------------------------------------------------------------------------
*/


foreach($alerts as $alert)
{


    /*
    |--------------------------------------------------------------------------
    | Check Existing Active Alert
    |--------------------------------------------------------------------------
    */


    $existingAlert = AiAlert::where(
            'resident_id',
            $vital->resident_id
        )
        ->where(
            'alert_type',
            $alert['type']
        )
        ->whereIn(
            'status',
            [
                'OPEN',
                'ACKNOWLEDGED'
            ]
        )
        ->latest('created_on')
        ->first();







    /*
    |--------------------------------------------------------------------------
    | Update Existing Alert
    |--------------------------------------------------------------------------
    */


    if($existingAlert)
    {


        $existingAlert->update([


            'severity'=>
            $alert['severity'],


            'message'=>
            $alert['message'],


            'ai_confidence'=>
            $alert['confidence'],


            'status'=>
            'OPEN'


        ]);






        /*
        |--------------------------------------------------------------------------
        | Timeline Update
        |--------------------------------------------------------------------------
        */


        $this->clinicalTimelineService
        ->record(


            $vital->resident_id,


            'AI_ALERT',


            $alert['type'].' Updated',


            $alert['message'],


            'AiAlert',


            $existingAlert->id


        );




        continue;


    }







    /*
    |--------------------------------------------------------------------------
    | Create New Alert
    |--------------------------------------------------------------------------
    */


    $createdAlert = AiAlert::create([


        'resident_id'=>
        $vital->resident_id,


        'alert_type'=>
        $alert['type'],


        'severity'=>
        $alert['severity'],


        'message'=>
        $alert['message'],


        'ai_confidence'=>
        $alert['confidence'],


        'status'=>
        'OPEN'


    ]);









    /*
    |--------------------------------------------------------------------------
    | Alert Action Log
    |--------------------------------------------------------------------------
    */


    $this->alertActionService
    ->record(

        $createdAlert->id,

        'CREATED',

        'AI generated new health alert'

    );








    /*
    |--------------------------------------------------------------------------
    | Clinical Timeline Record
    |--------------------------------------------------------------------------
    */


    $this->clinicalTimelineService
    ->record(


        $vital->resident_id,


        ClinicalEventType::AI_ALERT,


        $alert['type'].' Detected',


        $alert['message'],


        'AiAlert',


        $createdAlert->id


    );








    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */


    Notification::create([


        'user_id'=>1,


        'title'=>
        'AI Health Alert: '.$alert['type'],


        'message'=>
        $alert['message'],


        'type'=>
        'AI_ALERT',


        'read_status'=>0


    ]);








    /*
    |--------------------------------------------------------------------------
    | Escalation
    |--------------------------------------------------------------------------
    */


    if(
        $alert['severity']=="CRITICAL"
    )
    {


        $this->alertEscalationEngine
        ->escalate(

            $createdAlert->id

        );


    }



}



        /*
        |--------------------------------------------------------------------------
        | AI Intelligence Generation
        |--------------------------------------------------------------------------
        */


        $this->healthRiskAnalyzer
            ->calculate($vital);



        $this->healthPredictionAnalyzer
            ->analyze($vital);



        $this->clinicalRecommendationAnalyzer
            ->analyze($vital);








        return $alerts;



    }



}