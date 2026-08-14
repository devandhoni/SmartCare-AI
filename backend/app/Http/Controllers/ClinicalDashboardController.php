<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\Resident;
use App\Models\HealthRiskScore;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\ClinicalRecommendation;
use App\Models\NurseTask;
use App\Models\VitalSign;

use App\Services\HealthTrendAnalyzer;
use App\Services\HealthJourneyAnalyzer;
use App\Services\CareRecommendationEngine;
use App\Services\ClinicalDecisionEngine;


class ClinicalDashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | AI Clinical Decision Support Dashboard
    |--------------------------------------------------------------------------
    */


    public function show(
        $id,
        HealthTrendAnalyzer $healthTrendAnalyzer,
        HealthJourneyAnalyzer $healthJourneyAnalyzer,
        CareRecommendationEngine $careRecommendationEngine,
        ClinicalDecisionEngine $clinicalDecisionEngine
    )
    {


        $resident = Resident::findOrFail($id);







        /*
        |--------------------------------------------------------------------------
        | Latest Risk Score
        |--------------------------------------------------------------------------
        */


        $riskScore = HealthRiskScore::where(
                'resident_id',
                $id
            )
            ->latest('created_on')
            ->first();







        /*
        |--------------------------------------------------------------------------
        | Latest Vital Sign
        |--------------------------------------------------------------------------
        */


        $latestVital = VitalSign::where(
                'resident_id',
                $id
            )
            ->latest('created_on')
            ->first();








        /*
        |--------------------------------------------------------------------------
        | Health Trend Analysis
        |--------------------------------------------------------------------------
        */


        $healthTrend = $healthTrendAnalyzer->analyze($id);








        /*
        |--------------------------------------------------------------------------
        | Health Journey Analysis
        |--------------------------------------------------------------------------
        */


        $healthJourney = $healthJourneyAnalyzer->analyze($id);



        /*
        |--------------------------------------------------------------------------
        | AI Personalized Care Plan
        |--------------------------------------------------------------------------
        */

        $carePlan = $careRecommendationEngine->generate($id);


        /*
        |--------------------------------------------------------------------------
        | AI Clinical Decision Intelligence
        |--------------------------------------------------------------------------
        */


        $clinicalDecision =

            $clinicalDecisionEngine
            ->analyze($id);


        /*
        |--------------------------------------------------------------------------
        | Active AI Alerts
        |--------------------------------------------------------------------------
        */


        $alerts = AiAlert::where(
                'resident_id',
                $id
            )
            ->where(
                'status',
                'OPEN'
            )
            ->latest('created_on')
            ->get();








        /*
        |--------------------------------------------------------------------------
        | AI Predictions
        |--------------------------------------------------------------------------
        */


        $predictions = HealthPrediction::where(
                'resident_id',
                $id
            )
            ->latest('created_on')
            ->limit(10)
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Clinical Recommendations
        |--------------------------------------------------------------------------
        */


        $recommendations = ClinicalRecommendation::where(
                'resident_id',
                $id
            )
            ->latest('created_on')
            ->limit(10)
            ->get();








        /*
        |--------------------------------------------------------------------------
        | Pending Nurse Tasks
        |--------------------------------------------------------------------------
        */


        $tasks = NurseTask::where(
                'resident_id',
                $id
            )
            ->where(
                'status',
                'Pending'
            )
            ->latest('created_on')
            ->get();









        /*
        |--------------------------------------------------------------------------
        | Return Clinical Dashboard
        |--------------------------------------------------------------------------
        */


        return response()->json([



            'resident'=>[

                'id'=>$resident->id,

                'name'=>$resident->full_name,

                'status'=>$resident->status

            ],







            'health_status'=>[

                'risk_score'=>$riskScore?->risk_score ?? 0,

                'risk_level'=>$riskScore?->risk_level ?? 'LOW',

                'summary'=>$riskScore?->reason 
                    ?? 
                    'No risk assessment available'

            ],








            'health_trend'=>$healthTrend,








            'latest_vital'=>$latestVital,








            'active_alerts'=>[

                'count'=>$alerts->count(),

                'data'=>$alerts

            ],








            'ai_predictions'=>$predictions,








            'clinical_recommendations'=>$recommendations,








            'pending_tasks'=>[

                'count'=>$tasks->count(),

                'data'=>$tasks

            ],








            /*
            |--------------------------------------------------------------------------
            | NEW: AI Health Journey
            |--------------------------------------------------------------------------
            */


            'health_journey'=>$healthJourney,

            'care_plan'=>$carePlan,


            /*
            |--------------------------------------------------------------------------
            | AI Clinical Decision
            |--------------------------------------------------------------------------
            */


            'clinical_decision'=>$clinicalDecision

        ]);


    }



}