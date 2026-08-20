<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\HealthPrediction;
use App\Models\HealthRiskScore;
use Carbon\Carbon;

class AIExecutiveSummaryEngine
{
    /*
    |--------------------------------------------------------------------------
    | Generate AI Executive Summary
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Step 52.12
        | Operational Resident Statistics
        |--------------------------------------------------------------------------
        |
        | Important distinction:
        |
        | - totalResidents = all residents stored in the system
        | - activeResidents = residents currently receiving active care
        | - criticalCases = ACTIVE residents with CRITICAL risk only
        |
        | Discharged / inactive residents remain available for historical
        | intelligence, but they must not inflate current operational counts.
        |
        */

        $totalResidents =
            Resident::count();

        $activeResidents =
            Resident::where(
                'status',
                'Active'
            )
            ->count();

        $nonActiveResidents =
            $totalResidents - $activeResidents;


        /*
        |--------------------------------------------------------------------------
        | Active Critical Cases
        |--------------------------------------------------------------------------
        |
        | Count only residents who:
        |
        | 1. currently have ACTIVE resident status
        | 2. have a CRITICAL HealthRiskScore
        |
        */

        $criticalCases =
            HealthRiskScore::query()
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->whereHas(
                    'resident',
                    function ($query) {
                        $query->where(
                            'status',
                            'Active'
                        );
                    }
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | Historical / Non-Active Critical Cases
        |--------------------------------------------------------------------------
        |
        | These cases remain clinically relevant for historical intelligence,
        | but they are NOT current operational care cases.
        |
        */

        $historicalCriticalCases =
            HealthRiskScore::query()
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->whereHas(
                    'resident',
                    function ($query) {
                        $query->where(
                            'status',
                            '!=',
                            'Active'
                        );
                    }
                )
                ->distinct()
                ->count(
                    'resident_id'
                );


        /*
        |--------------------------------------------------------------------------
        | Active AI Alerts
        |--------------------------------------------------------------------------
        |
        | Operational executive alerts should only count alerts belonging
        | to residents currently in active care.
        |
        */

        $activeAlerts =
            AiAlert::query()
                ->where(
                    'status',
                    'OPEN'
                )
                ->whereHas(
                    'resident',
                    function ($query) {
                        $query->where(
                            'status',
                            'Active'
                        );
                    }
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | Historical / Non-Active Open Alerts
        |--------------------------------------------------------------------------
        */

        $historicalOpenAlerts =
            AiAlert::query()
                ->where(
                    'status',
                    'OPEN'
                )
                ->whereHas(
                    'resident',
                    function ($query) {
                        $query->where(
                            'status',
                            '!=',
                            'Active'
                        );
                    }
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | AI Prediction Statistics
        |--------------------------------------------------------------------------
        |
        | Total prediction generation remains a system-level metric and may
        | include historical residents.
        |
        */

        $totalPredictions =
            HealthPrediction::count();


        /*
        |--------------------------------------------------------------------------
        | Generate Key Findings
        |--------------------------------------------------------------------------
        */

        $findings = [];


        if ($criticalCases > 0) {

            $findings[] =
                $criticalCases
                . ' active critical resident case(s) require monitoring.';
        } else {

            $findings[] =
                'No active critical resident cases currently require monitoring.';
        }


        if ($activeAlerts > 0) {

            $findings[] =
                $activeAlerts
                . ' active-care AI alert(s) require attention.';
        } else {

            $findings[] =
                'No open AI alerts currently require active-care attention.';
        }


        if ($totalPredictions > 0) {

            $findings[] =
                'AI generated '
                . $totalPredictions
                . ' clinical prediction(s) for risk assessment.';
        }


        if (
            $historicalCriticalCases > 0
            || $historicalOpenAlerts > 0
        ) {

            $findings[] =
                'Historical intelligence includes '
                . $historicalCriticalCases
                . ' non-active critical case(s) and '
                . $historicalOpenAlerts
                . ' non-active open alert(s); these are excluded from current-care escalation.';
        }


        /*
        |--------------------------------------------------------------------------
        | Priority Actions
        |--------------------------------------------------------------------------
        |
        | Only ACTIVE residents should generate current executive priority
        | actions.
        |
        */

        $priorityActions = [];


        $criticalResidents =
            HealthRiskScore::query()
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->whereHas(
                    'resident',
                    function ($query) {
                        $query->where(
                            'status',
                            'Active'
                        );
                    }
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

            $residentName =
                $risk->resident->full_name
                ?? $risk->resident->name
                ?? ('Resident ' . $risk->resident_id);


            $priorityActions[] =
                'Review '
                . $residentName
                . '\'s critical health condition.';
        }


        if (empty($priorityActions)) {

            $priorityActions[] =
                'Continue routine monitoring for active residents.';
        }


        /*
        |--------------------------------------------------------------------------
        | Overall Operational Status
        |--------------------------------------------------------------------------
        */

        if (
            $criticalCases > 0
            || $activeAlerts > 0
        ) {

            $overallStatus =
                'ATTENTION REQUIRED';

        } else {

            $overallStatus =
                'STABLE';
        }


        /*
        |--------------------------------------------------------------------------
        | Executive Message
        |--------------------------------------------------------------------------
        */

        $message =
            'SmartCare AI currently monitors '
            . $activeResidents
            . ' active resident(s) out of '
            . $totalResidents
            . ' resident record(s). ';


        if ($criticalCases > 0) {

            $message .=
                $criticalCases
                . ' active critical case(s) detected requiring clinical attention. ';

        } else {

            $message .=
                'No active critical cases detected. ';
        }


        if ($activeAlerts > 0) {

            $message .=
                $activeAlerts
                . ' active-care alert(s) are currently being monitored.';

        } else {

            $message .=
                'No active-care alerts currently require attention.';
        }


        /*
        |--------------------------------------------------------------------------
        | Return Executive Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'summary_date' =>
                Carbon::now()->format(
                    'Y-m-d'
                ),


            'executive_message' =>
                $message,


            /*
            |--------------------------------------------------------------------------
            | Step 52.12 Operational Census
            |--------------------------------------------------------------------------
            */

            'resident_census' => [

                'total_resident_records' =>
                    $totalResidents,

                'active_care_residents' =>
                    $activeResidents,

                'non_active_residents' =>
                    $nonActiveResidents,
            ],


            /*
            |--------------------------------------------------------------------------
            | Step 52.12 Operational Risk Summary
            |--------------------------------------------------------------------------
            */

            'operational_risk_summary' => [

                'active_critical_cases' =>
                    $criticalCases,

                'historical_non_active_critical_cases' =>
                    $historicalCriticalCases,

                'active_open_alerts' =>
                    $activeAlerts,

                'historical_non_active_open_alerts' =>
                    $historicalOpenAlerts,
            ],


            'key_findings' =>
                $findings,


            'priority_actions' =>
                $priorityActions,


            'overall_status' =>
                $overallStatus,
        ];
    }
}