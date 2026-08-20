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
use App\Services\AIMonitoringAnalyzer;
use App\Services\AILearningAnalyzer;
use App\Services\PredictiveDeteriorationService;
use App\Services\AICareRecommendationEngine;
use App\Services\AICareWorkflowPreparationEngine;
use App\Services\AICareWorkflowOutcomeIntelligence;
use App\Services\AICareRecommendationLearningEngine;

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
        ClinicalDecisionEngine $clinicalDecisionEngine,
        AIMonitoringAnalyzer $aiMonitoringAnalyzer,
        AILearningAnalyzer $aiLearningAnalyzer,
        PredictiveDeteriorationService $predictiveDeteriorationService,
        AICareRecommendationEngine $aiCareRecommendationEngine,
        AICareWorkflowPreparationEngine $careWorkflowPreparationEngine,
        AICareWorkflowOutcomeIntelligence $careWorkflowOutcomeIntelligence,
        AICareRecommendationLearningEngine $careRecommendationLearningEngine
    ) {
        /*
        |--------------------------------------------------------------------------
        | Resident
        |--------------------------------------------------------------------------
        */

        $resident =
            Resident::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Latest Risk Score
        |--------------------------------------------------------------------------
        */

        $riskScore =
            HealthRiskScore::where(
                'resident_id',
                $id
            )
            ->latest(
                'created_on'
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Latest Vital Sign
        |--------------------------------------------------------------------------
        */

        $latestVital =
            VitalSign::where(
                'resident_id',
                $id
            )
            ->latest(
                'created_on'
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Health Trend Analysis
        |--------------------------------------------------------------------------
        */

        $healthTrend =
            $healthTrendAnalyzer->analyze(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | AI Learning Analyzer
        |--------------------------------------------------------------------------
        */

        $aiLearning =
            $aiLearningAnalyzer->analyze(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | Step 51
        | Predictive Intelligence
        |--------------------------------------------------------------------------
        */

        $predictiveIntelligence =
            $predictiveDeteriorationService->predict(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | Step 52.1
        | AI Care Recommendation Intelligence
        |--------------------------------------------------------------------------
        */

        $careRecommendationIntelligence =
            $aiCareRecommendationEngine->analyze(
                (int) $id
            );


        /*
        |--------------------------------------------------------------------------
        | Step 52.5
        | AI Care Workflow Preparation
        |--------------------------------------------------------------------------
        |
        | Converts execution-ready recommendations into workflow proposals.
        | This does NOT automatically create clinical tasks.
        |
        */

        $careWorkflowPreparation =
            $careWorkflowPreparationEngine->prepare(
                $careRecommendationIntelligence
            );


        /*
        |--------------------------------------------------------------------------
        | Step 52.9
        | AI Care Workflow Outcome Intelligence
        |--------------------------------------------------------------------------
        |
        | Evaluates completed AI-supported care workflows against linked
        | clinical outcomes.
        |
        | AI Recommendation
        |      ↓
        | Workflow Proposal
        |      ↓
        | Human Approval
        |      ↓
        | Operational Nurse Task
        |      ↓
        | Task Completion
        |      ↓
        | Clinical Outcome
        |      ↓
        | Workflow Effectiveness Intelligence
        |
        */

        $careWorkflowOutcome =
            $careWorkflowOutcomeIntelligence->analyze(
                (int) $id
            );


        /*
        |--------------------------------------------------------------------------
        | Step 52.10
        | AI Care Recommendation Effectiveness Learning
        |--------------------------------------------------------------------------
        |
        | Learns from completed AI-supported care workflows and their
        | associated clinical outcomes.
        |
        | IMPORTANT:
        |
        | This learning layer is advisory only.
        |
        | It does NOT automatically:
        |
        | - change clinical recommendation rules
        | - modify recommendation priorities
        | - suppress recommendations
        | - execute care actions
        |
        | Human validation remains required.
        |
        */

        $careRecommendationLearning =
            $careRecommendationLearningEngine->analyze(
                (int) $id
            );


        /*
        |--------------------------------------------------------------------------
        | Health Journey Analysis
        |--------------------------------------------------------------------------
        */

        $healthJourney =
            $healthJourneyAnalyzer->analyze(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | AI Monitoring Analysis
        |--------------------------------------------------------------------------
        */

        $aiMonitoring =
            $aiMonitoringAnalyzer->analyze(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | Existing AI Personalized Care Plan
        |--------------------------------------------------------------------------
        */

        $carePlan =
            $careRecommendationEngine->generate(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | AI Clinical Decision Intelligence
        |--------------------------------------------------------------------------
        */

        $clinicalDecision =
            $clinicalDecisionEngine->analyze(
                $id
            );


        /*
        |--------------------------------------------------------------------------
        | Active AI Alerts
        |--------------------------------------------------------------------------
        */

        $alerts =
            AiAlert::where(
                'resident_id',
                $id
            )
            ->where(
                'status',
                'OPEN'
            )
            ->latest(
                'created_on'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | AI Predictions
        |--------------------------------------------------------------------------
        */

        $predictions =
            HealthPrediction::where(
                'resident_id',
                $id
            )
            ->latest(
                'created_on'
            )
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Clinical Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendations =
            ClinicalRecommendation::where(
                'resident_id',
                $id
            )
            ->latest(
                'created_on'
            )
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Pending Nurse Tasks
        |--------------------------------------------------------------------------
        */

        $tasks =
            NurseTask::where(
                'resident_id',
                $id
            )
            ->where(
                'status',
                'Pending'
            )
            ->latest(
                'created_on'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Return Clinical Dashboard
        |--------------------------------------------------------------------------
        */

        return response()->json([

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            'resident' => [

                'id' =>
                    $resident->id,

                'name' =>
                    $resident->full_name,

                'status' =>
                    $resident->status,
            ],


            /*
            |--------------------------------------------------------------------------
            | Health Status
            |--------------------------------------------------------------------------
            */

            'health_status' => [

                'risk_score' =>
                    $riskScore?->risk_score ?? 0,

                'risk_level' =>
                    $healthTrend[
                        'current_condition'
                    ]['status']
                    ?? 'UNKNOWN',

                'summary' =>
                    !empty(
                        $healthTrend[
                            'current_condition'
                        ]['reasons']
                    )
                        ?
                        implode(
                            ' ',
                            $healthTrend[
                                'current_condition'
                            ]['reasons']
                        )
                        :
                        'No clinical risk detected.',
            ],


            /*
            |--------------------------------------------------------------------------
            | Health Trend
            |--------------------------------------------------------------------------
            */

            'health_trend' =>
                $healthTrend,


            /*
            |--------------------------------------------------------------------------
            | Latest Vital
            |--------------------------------------------------------------------------
            */

            'latest_vital' =>
                $latestVital,


            /*
            |--------------------------------------------------------------------------
            | Active Alerts
            |--------------------------------------------------------------------------
            */

            'active_alerts' => [

                'count' =>
                    $alerts->count(),

                'data' =>
                    $alerts,
            ],


            /*
            |--------------------------------------------------------------------------
            | AI Predictions
            |--------------------------------------------------------------------------
            */

            'ai_predictions' =>
                $predictions,


            /*
            |--------------------------------------------------------------------------
            | Existing Clinical Recommendations
            |--------------------------------------------------------------------------
            */

            'clinical_recommendations' =>
                $recommendations,


            /*
            |--------------------------------------------------------------------------
            | Pending Nurse Tasks
            |--------------------------------------------------------------------------
            */

            'pending_tasks' => [

                'count' =>
                    $tasks->count(),

                'data' =>
                    $tasks,
            ],


            /*
            |--------------------------------------------------------------------------
            | AI Health Journey
            |--------------------------------------------------------------------------
            */

            'health_journey' =>
                $healthJourney,


            /*
            |--------------------------------------------------------------------------
            | Existing Personalized Care Plan
            |--------------------------------------------------------------------------
            */

            'care_plan' =>
                $carePlan,


            /*
            |--------------------------------------------------------------------------
            | AI Clinical Decision
            |--------------------------------------------------------------------------
            */

            'clinical_decision' =>
                $clinicalDecision,


            /*
            |--------------------------------------------------------------------------
            | AI Monitoring Intelligence
            |--------------------------------------------------------------------------
            */

            'ai_monitoring' =>
                $aiMonitoring,


            /*
            |--------------------------------------------------------------------------
            | AI Learning
            |--------------------------------------------------------------------------
            */

            'ai_learning' =>
                $aiLearning,


            /*
            |--------------------------------------------------------------------------
            | Step 51
            | Predictive Intelligence
            |--------------------------------------------------------------------------
            */

            'predictive_intelligence' =>
                $predictiveIntelligence,


            /*
            |--------------------------------------------------------------------------
            | Step 52.1
            | Care Recommendation Intelligence
            |--------------------------------------------------------------------------
            */

            'care_recommendation_intelligence' =>
                $careRecommendationIntelligence,


            /*
            |--------------------------------------------------------------------------
            | Step 52.5
            | Care Workflow Preparation
            |--------------------------------------------------------------------------
            */

            'care_workflow_preparation' =>
                $careWorkflowPreparation,


            /*
            |--------------------------------------------------------------------------
            | Step 52.9
            | Care Workflow Outcome Intelligence
            |--------------------------------------------------------------------------
            */

            'care_workflow_outcome_intelligence' =>
                $careWorkflowOutcome,


            /*
            |--------------------------------------------------------------------------
            | Step 52.10
            | AI Care Recommendation Learning
            |--------------------------------------------------------------------------
            */

            'care_recommendation_learning' =>
                $careRecommendationLearning,
        ]);
    }
}