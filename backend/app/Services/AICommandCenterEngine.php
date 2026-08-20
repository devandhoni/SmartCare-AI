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

    public function __construct(
        AIExecutiveSummaryEngine $executiveSummary,
        ClinicalPerformanceDashboardEngine $clinicalPerformance,
        AIOutcomePerformanceEngine $outcomePerformance,
        PredictiveIntelligenceAggregator $predictiveAggregator
    ) {
        $this->executiveSummary =
            $executiveSummary;

        $this->clinicalPerformance =
            $clinicalPerformance;

        $this->outcomePerformance =
            $outcomePerformance;

        $this->predictiveAggregator =
            $predictiveAggregator;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate AI Command Center
    |--------------------------------------------------------------------------
    */

    public function analyze()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Executive Summary
        |--------------------------------------------------------------------------
        */

        $summary =
            $this->executiveSummary->analyze();

        /*
        |--------------------------------------------------------------------------
        | 2. Clinical Performance
        |--------------------------------------------------------------------------
        */

        $performance =
            $this->clinicalPerformance->analyze();

        /*
        |--------------------------------------------------------------------------
        | 3. AI Outcome Performance
        |--------------------------------------------------------------------------
        */

        $outcomePerformance =
            $this->outcomePerformance->analyze();

        /*
        |--------------------------------------------------------------------------
        | 4. Step 51.6B
        | Facility Predictive Intelligence
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | This is NOT a single resident prediction.
        |
        | PredictiveIntelligenceAggregator analyzes all residents using
        | PredictiveDeteriorationService and returns facility-level:
        |
        | - command status
        | - predictive summary
        | - top clinical drivers
        | - priority residents
        |
        */

        $predictiveIntelligence =
            $this->predictiveAggregator->analyze();

        /*
        |--------------------------------------------------------------------------
        | 5. Existing Priority Resident Monitoring
        |--------------------------------------------------------------------------
        */

        $priorityResidents = [];

        $criticalResidents =
            HealthRiskScore::where(
                'risk_level',
                'CRITICAL'
            )
            ->with('resident')
            ->orderByDesc(
                'risk_score'
            )
            ->get();

        foreach ($criticalResidents as $risk) {

            if (!$risk->resident) {
                continue;
            }

            $priorityResidents[] = [

                'resident_id' =>
                    $risk->resident_id,

                'resident_name' =>
                    $risk->resident->full_name
                    ?? $risk->resident->name
                    ?? ('Resident ' . $risk->resident_id),

                'priority' =>
                    'CRITICAL',

                'risk_score' =>
                    $risk->risk_score,

                'recommendation' =>
                    'Immediate clinical monitoring required.',
            ];
        }

        if (empty($priorityResidents)) {

            $priorityResidents[] = [

                'message' =>
                    'No critical resident requires immediate attention.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Latest AI Clinical Decision
        |--------------------------------------------------------------------------
        */

        $latestAlert =
            AiAlert::with('resident')
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
        | 7. System Health Status
        |--------------------------------------------------------------------------
        */

        $activeAlerts =
            AiAlert::where(
                'status',
                'OPEN'
            )
            ->count();

        $status =
            $activeAlerts > 0
            ?
            'ATTENTION REQUIRED'
            :
            'STABLE';

        /*
        |--------------------------------------------------------------------------
        | 8. Clinical Overview
        |--------------------------------------------------------------------------
        */

        $clinicalOverview = [

            'total_residents' =>
                Resident::count(),

            'critical_cases' =>
                HealthRiskScore::where(
                    'risk_level',
                    'CRITICAL'
                )
                ->distinct()
                ->count(
                    'resident_id'
                ),

            'active_alerts' =>
                $activeAlerts,
        ];

        /*
        |--------------------------------------------------------------------------
        | 9. AI Performance
        |--------------------------------------------------------------------------
        */

        $aiPerformance = [

            'predictions_generated' =>
                HealthPrediction::count(),

            'high_risk_predictions' =>
                HealthPrediction::whereIn(
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
        | 10. Final AI Command Center Response
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
            | Existing Critical Priority Monitoring
            |--------------------------------------------------------------------------
            */

            'priority_attention' =>
                $priorityResidents,

            /*
            |--------------------------------------------------------------------------
            | Latest AI Clinical Decision
            |--------------------------------------------------------------------------
            */

            'latest_ai_decision' =>
                $latestAIDecision,

            /*
            |--------------------------------------------------------------------------
            | Step 51.6B Facility Predictive Intelligence
            |--------------------------------------------------------------------------
            */

            'predictive_intelligence' =>
                $predictiveIntelligence,
        ];
    }
}