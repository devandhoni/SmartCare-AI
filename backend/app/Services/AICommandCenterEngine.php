<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\HealthRiskScore;

class AICommandCenterEngine
{
    protected AIExecutiveSummaryEngine $executiveSummary;

    protected ClinicalPerformanceDashboardEngine $clinicalPerformance;

    protected AIOutcomePerformanceEngine $outcomePerformance;

    protected PredictiveIntelligenceAggregator $predictiveAggregator;

    protected AICareRecommendationAggregator $careRecommendationAggregator;


    public function __construct(
        AIExecutiveSummaryEngine $executiveSummary,
        ClinicalPerformanceDashboardEngine $clinicalPerformance,
        AIOutcomePerformanceEngine $outcomePerformance,
        PredictiveIntelligenceAggregator $predictiveAggregator,
        AICareRecommendationAggregator $careRecommendationAggregator
    ) {
        $this->executiveSummary =
            $executiveSummary;

        $this->clinicalPerformance =
            $clinicalPerformance;

        $this->outcomePerformance =
            $outcomePerformance;

        $this->predictiveAggregator =
            $predictiveAggregator;

        $this->careRecommendationAggregator =
            $careRecommendationAggregator;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate AI Command Center
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Resident Operational Census
        |--------------------------------------------------------------------------
        |
        | Step 52.12
        |
        | The Command Center must distinguish residents currently receiving
        | care from discharged / otherwise non-active historical records.
        |
        */

        $activeResidentIds =
            Resident::query()
                ->whereRaw(
                    'UPPER(status) = ?',
                    ['ACTIVE']
                )
                ->pluck('id');

        $nonActiveResidentIds =
            Resident::query()
                ->whereRaw(
                    'UPPER(status) <> ?',
                    ['ACTIVE']
                )
                ->pluck('id');

        $totalResidentRecords =
            Resident::count();

        $activeResidentCount =
            $activeResidentIds->count();

        $nonActiveResidentCount =
            $nonActiveResidentIds->count();


        /*
        |--------------------------------------------------------------------------
        | 2. Executive Summary
        |--------------------------------------------------------------------------
        */

        $summary =
            $this->executiveSummary->analyze();


        /*
        |--------------------------------------------------------------------------
        | 3. Clinical Performance
        |--------------------------------------------------------------------------
        */

        $performance =
            $this->clinicalPerformance->analyze();


        /*
        |--------------------------------------------------------------------------
        | 4. AI Outcome Performance
        |--------------------------------------------------------------------------
        */

        $outcomePerformance =
            $this->outcomePerformance->analyze();


        /*
        |--------------------------------------------------------------------------
        | 5. Step 51.6B
        | Facility Predictive Intelligence
        |--------------------------------------------------------------------------
        |
        | Predictive intelligence intentionally retains all resident records,
        | including historical/non-active residents.
        |
        | It therefore represents broader predictive/historical intelligence
        | rather than only current operational care.
        |
        */

        $predictiveIntelligence =
            $this->predictiveAggregator->analyze();


        /*
        |--------------------------------------------------------------------------
        | 6. Step 52
        | Facility Care Recommendation Intelligence
        |--------------------------------------------------------------------------
        |
        | This is the operational care intelligence layer.
        |
        | Non-active residents should not appear as current care-priority
        | residents.
        |
        */

        $careRecommendationIntelligence =
            $this->careRecommendationAggregator->analyze();


        /*
        |--------------------------------------------------------------------------
        | 7. Active Critical Resident Count
        |--------------------------------------------------------------------------
        */

        $activeCriticalCases =
            HealthRiskScore::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | 8. Historical / Non-Active Critical Cases
        |--------------------------------------------------------------------------
        */

        $historicalCriticalCases =
            HealthRiskScore::query()
                ->whereIn(
                    'resident_id',
                    $nonActiveResidentIds
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | 9. Active-Care Alerts
        |--------------------------------------------------------------------------
        */

        $activeAlerts =
            AiAlert::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 10. Historical / Non-Active Open Alerts
        |--------------------------------------------------------------------------
        */

        $historicalOpenAlerts =
            AiAlert::query()
                ->whereIn(
                    'resident_id',
                    $nonActiveResidentIds
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | 11. Existing Priority Resident Monitoring
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Only ACTIVE residents are eligible for current operational priority
        | attention.
        |
        */

        $priorityResidents = [];

        $criticalResidents =
            HealthRiskScore::query()
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->with('resident')
                ->orderByDesc(
                    'risk_score'
                )
                ->get()
                ->unique(
                    'resident_id'
                );

        foreach ($criticalResidents as $risk) {

            if (!$risk->resident) {
                continue;
            }

            $priorityResidents[] = [

                'resident_id' =>
                    $risk->resident_id,

                'resident_name' =>
                    $risk->resident->full_name
                    ??
                    $risk->resident->name
                    ??
                    ('Resident ' . $risk->resident_id),

                'resident_status' =>
                    $risk->resident->status
                    ??
                    'UNKNOWN',

                'priority' =>
                    'CRITICAL',

                'risk_score' =>
                    (float) $risk->risk_score,

                'recommendation' =>
                    'Immediate clinical monitoring required.',
            ];
        }


        if (empty($priorityResidents)) {

            $priorityResidents[] = [

                'message' =>
                    'No active critical resident currently requires immediate attention.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Latest ACTIVE AI Clinical Decision
        |--------------------------------------------------------------------------
        |
        | Historical alerts belonging to discharged/non-active residents must
        | not become the current Command Center clinical decision.
        |
        */

        $latestAlert =
            AiAlert::with('resident')
                ->whereIn(
                    'resident_id',
                    $activeResidentIds
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->latest(
                    'created_on'
                )
                ->first();

        $latestAIDecision =
            null;

        if ($latestAlert) {

            $riskFactors = [];

            $message =
                $latestAlert->message;

            if ($message) {

                $parts =
                    explode(
                        ',',
                        $message
                    );

                foreach ($parts as $part) {

                    $part =
                        trim($part);

                    if ($part !== '') {

                        $riskFactors[] =
                            $part;
                    }
                }
            }

            $latestAIDecision = [

                'resident_id' =>
                    $latestAlert->resident_id,

                'resident_name' =>
                    $latestAlert->resident
                    ?
                    (
                        $latestAlert->resident->full_name
                        ??
                        $latestAlert->resident->name
                        ??
                        'Unknown'
                    )
                    :
                    'Unknown',

                'resident_status' =>
                    $latestAlert->resident->status
                    ??
                    'UNKNOWN',

                'priority' =>
                    $latestAlert->severity,

                'decision_score' =>
                    (float)
                    $latestAlert->ai_confidence,

                'risk_factors' =>
                    $riskFactors,

                'generated_at' =>
                    $latestAlert->created_at,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Current Operational System Status
        |--------------------------------------------------------------------------
        |
        | Historical/non-active alerts must NOT trigger the current system
        | status.
        |
        */

        $status =
            'STABLE';

        if (
            $activeCriticalCases > 0
            ||
            $activeAlerts > 0
        ) {
            $status =
                'ATTENTION REQUIRED';
        }


        /*
        |--------------------------------------------------------------------------
        | 14. Clinical Overview
        |--------------------------------------------------------------------------
        |
        | Existing fields are preserved where practical for frontend
        | compatibility.
        |
        | IMPORTANT:
        | "active_alerts" now means active-care open alerts.
        |
        */

        $clinicalOverview = [

            'total_residents' =>
                $totalResidentRecords,

            'active_care_residents' =>
                $activeResidentCount,

            'non_active_residents' =>
                $nonActiveResidentCount,

            'critical_cases' =>
                $activeCriticalCases,

            'historical_non_active_critical_cases' =>
                $historicalCriticalCases,

            'active_alerts' =>
                $activeAlerts,

            'historical_non_active_open_alerts' =>
                $historicalOpenAlerts,
        ];


        /*
        |--------------------------------------------------------------------------
        | 15. AI Performance
        |--------------------------------------------------------------------------
        |
        | These remain system-wide historical AI performance metrics.
        |
        */

        $aiPerformance = [

            'predictions_generated' =>
                HealthPrediction::count(),

            'high_risk_predictions' =>
                HealthPrediction::query()
                    ->whereIn(
                        'risk_level',
                        [
                            'HIGH',
                            'CRITICAL',
                        ]
                    )
                    ->count(),

            'active_care_predictions' =>
                HealthPrediction::query()
                    ->whereIn(
                        'resident_id',
                        $activeResidentIds
                    )
                    ->count(),

            'active_care_high_risk_predictions' =>
                HealthPrediction::query()
                    ->whereIn(
                        'resident_id',
                        $activeResidentIds
                    )
                    ->whereIn(
                        'risk_level',
                        [
                            'HIGH',
                            'CRITICAL',
                        ]
                    )
                    ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | 16. Facility Intelligence Summary
        |--------------------------------------------------------------------------
        |
        | Compact executive bridge between Step 51 predictive intelligence and
        | Step 52 operational care recommendation intelligence.
        |
        */

        $predictivePriorityCount =
            count(
                $predictiveIntelligence[
                    'priority_residents'
                ]
                ?? []
            );

        $carePriorityCount =
            count(
                $careRecommendationIntelligence[
                    'priority_residents'
                ]
                ?? []
            );

        $executionReadyCareActions =
            (int) (
                $careRecommendationIntelligence[
                    'summary'
                ]['execution_ready_actions']
                ?? 0
            );

        $doctorReviewActions =
            (int) (
                $careRecommendationIntelligence[
                    'summary'
                ]['doctor_review_actions']
                ?? 0
            );

        $evaluatedCareWorkflows =
            (int) (
                $careRecommendationIntelligence[
                    'summary'
                ]['evaluated_workflows']
                ?? 0
            );

        $careWorkflowSuccessRate =
            (float) (
                $careRecommendationIntelligence[
                    'summary'
                ]['workflow_success_rate']
                ?? 0
            );

        $facilityIntelligenceSummary = [

            'operational_system_status' =>
                $status,

            'active_care_residents' =>
                $activeResidentCount,

            'non_active_residents' =>
                $nonActiveResidentCount,

            'active_critical_cases' =>
                $activeCriticalCases,

            'historical_non_active_critical_cases' =>
                $historicalCriticalCases,

            'active_care_alerts' =>
                $activeAlerts,

            'historical_non_active_open_alerts' =>
                $historicalOpenAlerts,

            'predictive_command_status' =>
                $predictiveIntelligence[
                    'command_status'
                ]
                ?? 'UNKNOWN',

            'care_command_status' =>
                $careRecommendationIntelligence[
                    'command_status'
                ]
                ?? 'UNKNOWN',

            'predictive_priority_residents' =>
                $predictivePriorityCount,

            'care_priority_residents' =>
                $carePriorityCount,

            'execution_ready_care_actions' =>
                $executionReadyCareActions,

            'doctor_review_actions' =>
                $doctorReviewActions,

            'evaluated_care_workflows' =>
                $evaluatedCareWorkflows,

            'care_workflow_success_rate' =>
                $careWorkflowSuccessRate,

            'historical_intelligence_retained' =>
                true,

            'historical_intelligence_excluded_from_current_care_escalation' =>
                true,
        ];


        /*
        |--------------------------------------------------------------------------
        | 17. Final AI Command Center Response
        |--------------------------------------------------------------------------
        */

        return [

            'system_status' =>
                $status,

            'executive_summary' =>
                $summary,

            'clinical_overview' =>
                $clinicalOverview,

            'ai_performance' =>
                $aiPerformance,

            'clinical_performance' =>
                $performance,

            'ai_outcome_performance' =>
                $outcomePerformance,


            /*
            |--------------------------------------------------------------------------
            | Current Operational Priority Attention
            |--------------------------------------------------------------------------
            */

            'priority_attention' =>
                $priorityResidents,


            /*
            |--------------------------------------------------------------------------
            | Latest Active-Care AI Clinical Decision
            |--------------------------------------------------------------------------
            */

            'latest_ai_decision' =>
                $latestAIDecision,


            /*
            |--------------------------------------------------------------------------
            | Step 51.6B
            | Facility Predictive Intelligence
            |--------------------------------------------------------------------------
            */

            'predictive_intelligence' =>
                $predictiveIntelligence,


            /*
            |--------------------------------------------------------------------------
            | Step 52
            | Facility Care Recommendation Intelligence
            |--------------------------------------------------------------------------
            */

            'care_recommendation_intelligence' =>
                $careRecommendationIntelligence,


            /*
            |--------------------------------------------------------------------------
            | Step 52.12
            | Unified Facility Intelligence Summary
            |--------------------------------------------------------------------------
            */

            'facility_intelligence_summary' =>
                $facilityIntelligenceSummary,
        ];
    }
}