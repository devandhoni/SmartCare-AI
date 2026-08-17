<?php

namespace App\Services;


use App\Models\Resident;
use App\Models\VitalSign;
use App\Models\HealthRiskScore;
use App\Models\HealthPrediction;
use App\Models\AiAlert;
use App\Services\ClinicalDecisionLogger;


class ClinicalDecisionEngine
{


    protected HealthTrendAnalyzer $healthTrendAnalyzer;

    protected ClinicalActionPlanGenerator $clinicalActionPlanGenerator;

    protected ClinicalDecisionLogger $clinicalDecisionLogger;

    

    public function __construct(

        HealthTrendAnalyzer $healthTrendAnalyzer,

        ClinicalActionPlanGenerator $clinicalActionPlanGenerator,

        ClinicalDecisionLogger $clinicalDecisionLogger
    )
    {

        $this->healthTrendAnalyzer =
            $healthTrendAnalyzer;


        $this->clinicalActionPlanGenerator =
            $clinicalActionPlanGenerator;


        $this->clinicalDecisionLogger =
            $clinicalDecisionLogger;
    }





    /*
    |--------------------------------------------------------------------------
    | Generate Clinical Decision
    |--------------------------------------------------------------------------
    */


    public function analyze($residentId)
    {


        $resident =
            Resident::findOrFail($residentId);





        /*
        |--------------------------------------------------------------------------
        | Initialize Decision Variables
        |--------------------------------------------------------------------------
        */


        $decisionScore = 0;


        $priority = "LOW";


        $reasons = [];


        $riskFactors = [];


        $recommendedActions = [];






        /*
        |--------------------------------------------------------------------------
        | Retrieve Clinical Data
        |--------------------------------------------------------------------------
        */


        $latestVital =
            VitalSign::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->first();





        $riskScore =
            HealthRiskScore::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->first();





        $predictions =
            HealthPrediction::where(
                'resident_id',
                $residentId
            )
            ->latest('created_on')
            ->get();





        $activeAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'status',
                'OPEN'
            )
            ->whereNotIn(
                'alert_type',
                [
                    'Critical Clinical Decision'
                ]
            )
            ->get();



        /*
        |--------------------------------------------------------------------------
        | Health Trend Analysis
        |--------------------------------------------------------------------------
        */


        $healthTrend =
            $this->healthTrendAnalyzer
            ->analyze($residentId);







        /*
        |--------------------------------------------------------------------------
        | Health Risk Score Evaluation
        |--------------------------------------------------------------------------
        */


        if($riskScore)
        {


            if($riskScore->risk_score >=80)
            {


                $decisionScore +=30;


                $riskFactors[] =
                "High overall health risk score detected.";


                $reasons[] =
                "Critical health risk score detected.";

            }


            elseif($riskScore->risk_score >=50)
            {


                $decisionScore +=15;


                $riskFactors[] =
                "Moderate health risk detected.";


                $reasons[] =
                "Moderate health risk detected.";

            }


        }








        /*
        |--------------------------------------------------------------------------
        | AI Alert Intelligence
        |--------------------------------------------------------------------------
        */


        foreach($activeAlerts as $alert)
            {


                if($alert->alert_type=="MEDICATION DELAY")
                {


                    $decisionScore +=15;


                    $riskFactors[] =
                    "Medication compliance issue detected.";


                    $reasons[] =
                    $alert->message;


                    $recommendedActions[] =
                    "Review delayed medication administration.";

                }



                elseif($alert->severity=="CRITICAL")
                {


                    $decisionScore +=40;


                    $riskFactors[] =
                    "Critical AI alert detected: ".$alert->alert_type;


                    $reasons[] =
                    "Critical AI alert requires clinical attention: "
                    .$alert->alert_type;


                    $recommendedActions[] =
                    "Immediate clinical assessment required.";

                }



                elseif($alert->severity=="HIGH")
                {


                    $decisionScore +=25;


                    $riskFactors[] =
                    "High severity AI alert detected.";


                    $reasons[] =
                    $alert->message;


                    $recommendedActions[] =
                    "Close resident monitoring required.";

                }


            }







        /*
        |--------------------------------------------------------------------------
        | Health Trend Evaluation
        |--------------------------------------------------------------------------
        */


        if(
            isset($healthTrend['trend_status'])
        )
        {


            if(
                $healthTrend['trend_status']=="WORSENING"
            )
            {


                $decisionScore +=15;


                $riskFactors[] =
                "Health condition trend is worsening.";


                $reasons[] =
                "Resident condition shows deterioration.";


                $recommendedActions[] =
                "Increase monitoring frequency.";

            }


        }








        /*
        |--------------------------------------------------------------------------
        | Vital Sign Evaluation
        |--------------------------------------------------------------------------
        */


        if($latestVital)
        {


            if(
                $latestVital->blood_pressure_systolic >=160
            )
            {


                $decisionScore +=10;


                $reasons[] =
                "Elevated blood pressure detected.";


            }





            if(
                $latestVital->oxygen_level <92
            )
            {


                $decisionScore +=15;


                $reasons[] =
                "Low oxygen saturation detected.";


                $recommendedActions[] =
                "Repeat oxygen saturation monitoring.";

            }






            if(
                $latestVital->blood_glucose >=10
            )
            {


                $decisionScore +=10;


                $reasons[] =
                "High blood glucose detected.";


            }


        }







        /*
        |--------------------------------------------------------------------------
        | Prediction Intelligence
        |--------------------------------------------------------------------------
        */


        foreach($predictions as $prediction)
        {


            if($prediction->risk_level=="CRITICAL")
            {


                $decisionScore +=20;


                $riskFactors[] =
                "Critical prediction detected: ".$prediction->prediction;


            }


        }








        /*
        |--------------------------------------------------------------------------
        | Convert Score Into Priority
        |--------------------------------------------------------------------------
        */


        if($decisionScore >=80)
        {

            $priority="CRITICAL";

        }
        elseif($decisionScore >=60)
        {

            $priority="HIGH";

        }
        elseif($decisionScore >=30)
        {

            $priority="MODERATE";

        }
        else
        {

            $priority="LOW";

        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Decision Score
        |--------------------------------------------------------------------------
        */

        $decisionScore = min($decisionScore,100);






        /*
        |--------------------------------------------------------------------------
        | Recommended Action
        |--------------------------------------------------------------------------
        */


        if($priority=="CRITICAL")
        {


            $action =
            "Immediate nurse monitoring and physician assessment required.";


        }
        elseif($priority=="HIGH")
        {


            $action =
            "Close monitoring and clinical review recommended.";


        }
        else
        {


            $action =
            "Continue routine monitoring.";

        }








        /*
        |--------------------------------------------------------------------------
        | Clinical Action Plan
        |--------------------------------------------------------------------------
        */


        $clinicalActionPlan =

        $this->clinicalActionPlanGenerator->generate(

            $priority,

            $reasons,

            $latestVital,

            $healthTrend

        );




        /*
        |--------------------------------------------------------------------------
        | Store AI Clinical Decision History
        |--------------------------------------------------------------------------
        */


        $this->clinicalDecisionLogger->log([


            'resident_id'=>
                $residentId,


            'decision_score'=>
                $decisionScore,


            'priority'=>
                $priority,


            'risk_factors'=>
                $riskFactors,


            'recommended_actions'=>
                $recommendedActions,

            'latest_vital'=>
                $latestVital


        ]);



        /*
        |--------------------------------------------------------------------------
        | Return Decision
        |--------------------------------------------------------------------------
        */


        return [

            "resident_id"=>$residentId,


            "resident_name"=>$resident->full_name,


            "decision_score"=>$decisionScore,


            "priority"=>$priority,


            "risk_factors"=>$riskFactors,


            "reasons"=>$reasons,


            "recommended_actions"=>$recommendedActions,


            "recommended_action"=>$action,


            "health_trend"=>$healthTrend,


            "clinical_action_plan"=>$clinicalActionPlan,


            "latest_vital"=>$latestVital,


            "active_alert_count"=>$activeAlerts->count(),


            "prediction_count"=>$predictions->count()

        ];


    }


}