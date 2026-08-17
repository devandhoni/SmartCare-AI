<?php

namespace App\Services;


use App\Models\ClinicalTimeline;
use App\Models\ClinicalRecommendation;
use App\Models\AiAlert;
use App\Models\ActivityLog;
use App\Services\ClinicalMonitoringEngine;
use Carbon\Carbon;



class ClinicalDecisionLogger
{


    protected AlertEscalationEngine $alertEscalationEngine;

    protected ClinicalMonitoringEngine $clinicalMonitoringEngine;


    public function __construct(
        AlertEscalationEngine $alertEscalationEngine,
        ClinicalMonitoringEngine $clinicalMonitoringEngine
    )
    {

        $this->alertEscalationEngine =
            $alertEscalationEngine;


        $this->clinicalMonitoringEngine =
            $clinicalMonitoringEngine;

    }







    /*
    |--------------------------------------------------------------------------
    | Store AI Clinical Decision History
    |--------------------------------------------------------------------------
    */


    public function log(array $decision)
    {


        $residentId =
            $decision['resident_id'];


        $priority =
            $decision['priority'] ?? 'NORMAL';


        $decisionScore =
            $decision['decision_score'] ?? 0;


        $riskFactors =
            $decision['risk_factors'] ?? [];









        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate AI Decision Timeline
        |--------------------------------------------------------------------------
        */


        $existingTimeline =

        ClinicalTimeline::where(
            'resident_id',
            $residentId
        )
        ->where(
            'event_type',
            'AI_DECISION'
        )
        ->where(
            'event_description',
            'LIKE',
            "%decision score {$decisionScore}%"
        )
        ->where(
            'created_at',
            '>=',
            Carbon::now()->subMinutes(30)
        )
        ->latest('event_date')
        ->first();






        if(!$existingTimeline)
        {


            $timeline = ClinicalTimeline::create([


                'resident_id'=>
                    $residentId,


                'event_type'=>
                    'AI_DECISION',


                'decision_status'=>
                    'PENDING_REVIEW',


                'reviewed_by'=>
                    null,


                'reviewed_at'=>
                    null,


                'review_action'=>
                    null,


                'event_title'=>
                    'AI Clinical Decision Generated',


                'event_description'=>

                    "AI classified resident as "
                    .$priority.
                    " with decision score "
                    .$decisionScore.
                    ". Risk factors: "
                    .
                    implode(
                        ', ',
                        $riskFactors
                    ),


                'source_type'=>
                    'ClinicalDecision',


                'source_id'=>
                    null,


                'event_date'=>
                    now()


            ]);


        }
        else
        {


            $timeline =
                $existingTimeline;


        }


        /*
|--------------------------------------------------------------------------
| Create Or Escalate Critical AI Alert
|--------------------------------------------------------------------------
*/

if($priority === "CRITICAL")
{


    $criticalAlert = AiAlert::where(
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
        ->latest('created_on')
        ->first();



    if($criticalAlert)
    {


        /*
        |--------------------------------------------------------------------------
        | Existing Critical Alert Found
        |--------------------------------------------------------------------------
        */


        $this->alertEscalationEngine
            ->escalate(
                $criticalAlert->id
            );


    }
    else
    {


        /*
        |--------------------------------------------------------------------------
        | No Open Critical Alert Found
        | Create New Alert
        |--------------------------------------------------------------------------
        */


        $newAlert = AiAlert::create([


            'resident_id'=>
                $residentId,


            'alert_type'=>
                'Critical Clinical Decision',


            'severity'=>
                'CRITICAL',


            'message'=>

                "AI detected critical clinical condition. "
                .
                implode(
                    ', ',
                    $riskFactors
                ),


            'ai_confidence'=>
                $decisionScore,


            'status'=>
                'OPEN'


        ]);




        $this->alertEscalationEngine
            ->escalate(
                $newAlert->id
            );


    }


}



        /*
        |--------------------------------------------------------------------------
        | Create AI Clinical Recommendations
        |--------------------------------------------------------------------------
        */


        foreach(
            $decision['recommended_actions'] ?? []
            as $action
        )
        {


            $exists =


                ClinicalRecommendation::where(
                    'resident_id',
                    $residentId
                )
                ->where(
                    'recommendation_type',
                    'AI_CLINICAL_ACTION'
                )
                ->where(
                    'recommendation',
                    $action
                )
                ->where(
                    'priority',
                    $priority
                )
                ->exists();






            if(!$exists)
            {


                ClinicalRecommendation::create([


                    'resident_id'=>
                        $residentId,


                    'recommendation_type'=>
                        'AI_CLINICAL_ACTION',


                    'recommendation'=>
                        $action,


                    'priority'=>
                        $priority


                ]);


            }


        }









        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */


        ActivityLog::create([


            'user_id'=>
                auth()->id(),


            'resident_id'=>
                $residentId,


            'module'=>
                'Clinical Decision AI',


            'action'=>
                'GENERATE_DECISION',


            'description'=>

                "AI generated "
                .$priority.
                " clinical decision with score "
                .$decisionScore


        ]);



        /*
        |--------------------------------------------------------------------------
        | AI Monitoring Snapshot
        |--------------------------------------------------------------------------
        */

        $this->clinicalMonitoringEngine
            ->record($decision);





        return [


            'logged'=>
                !$existingTimeline,


            'duplicate'=>
                $existingTimeline ? true : false,


            'timeline_id'=>
                $timeline->id


        ];



    }


}