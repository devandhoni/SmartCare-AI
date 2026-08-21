<?php

namespace App\Services;

class AIExecutiveReportingSafetyEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    protected AIExecutiveNarrativeEngine $narrativeEngine;

    protected AIExecutiveTrendIntelligenceEngine $trendEngine;


    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine,
        AIExecutiveNarrativeEngine $narrativeEngine,
        AIExecutiveTrendIntelligenceEngine $trendEngine
    ) {
        $this->reportEngine =
            $reportEngine;

        $this->narrativeEngine =
            $narrativeEngine;

        $this->trendEngine =
            $trendEngine;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.6
    | Executive Reporting Safety & Consistency Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Existing Intelligence
        |--------------------------------------------------------------------------
        */

        $report =
            $this->reportEngine->analyze();

        $narrative =
            $this->narrativeEngine->analyze();

        $trend =
            $this->trendEngine->analyze();


        /*
        |--------------------------------------------------------------------------
        | 2. Source Sections
        |--------------------------------------------------------------------------
        */

        $facilityCensus =
            $report['facility_census']
            ?? [];

        $operationalRisk =
            $report['operational_risk']
            ?? [];

        $careExecution =
            $report['care_execution']
            ?? [];

        $priorityResidents =
            $report['priority_residents']
            ?? [];

        $historicalIntelligence =
            $report['historical_intelligence']
            ?? [];

        $reportGuardrails =
            $report['report_guardrails']
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | 3. Validation Containers
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $warnings = [];

        $criticalIssues = [];


        /*
        |--------------------------------------------------------------------------
        | 4. Active / Historical Census Consistency
        |--------------------------------------------------------------------------
        */

        $activeResidents =
            (int) (
                $facilityCensus[
                    'active_care_residents'
                ]
                ?? 0
            );

        $nonActiveResidents =
            (int) (
                $facilityCensus[
                    'non_active_residents'
                ]
                ?? 0
            );

        $checks[
            'resident_census_available'
        ] = [

            'passed' =>
                (
                    $activeResidents >= 0
                    &&
                    $nonActiveResidents >= 0
                ),

            'message' =>
                'Active and non-active resident census values are available.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 5. Report Status vs Active Clinical Risk
        |--------------------------------------------------------------------------
        */

        $reportStatus =
            strtoupper(
                (string) (
                    $report['report_status']
                    ?? 'UNKNOWN'
                )
            );

        $activeCriticalCases =
            (int) (
                $operationalRisk[
                    'active_critical_cases'
                ]
                ?? 0
            );

        $activeAlerts =
            (int) (
                $operationalRisk[
                    'active_care_alerts'
                ]
                ?? 0
            );

        $statusRiskConsistent =
            true;

        if (
            $activeCriticalCases > 0
            &&
            !in_array(
                $reportStatus,
                [
                    'CRITICAL',
                    'ATTENTION REQUIRED',
                    'HIGH ATTENTION',
                ],
                true
            )
        ) {
            $statusRiskConsistent =
                false;

            $criticalIssues[] =
                'Executive report status does not reflect active critical resident risk.';
        }

        $checks[
            'report_status_matches_active_risk'
        ] = [

            'passed' =>
                $statusRiskConsistent,

            'report_status' =>
                $reportStatus,

            'active_critical_cases' =>
                $activeCriticalCases,

            'active_care_alerts' =>
                $activeAlerts,

            'message' =>
                $statusRiskConsistent
                ?
                'Executive report status is consistent with current operational risk.'
                :
                'Executive report status may understate current operational risk.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 6. Operational Priority Resident Safety
        |--------------------------------------------------------------------------
        */

        $predictivePriority =
            $priorityResidents[
                'predictive_priority'
            ]
            ?? [];

        $carePriority =
            $priorityResidents[
                'care_priority'
            ]
            ?? [];

        $invalidOperationalResidents = [];

        foreach (
            array_merge(
                $predictivePriority,
                $carePriority
            )
            as $resident
        ) {
            if (!is_array($resident)) {
                continue;
            }

            $activeCareEligible =
                (bool) (
                    $resident[
                        'active_care_eligible'
                    ]
                    ?? false
                );

            if (!$activeCareEligible) {
                $invalidOperationalResidents[] = [

                    'resident_id' =>
                        $resident[
                            'resident_id'
                        ]
                        ?? null,

                    'resident_name' =>
                        $resident[
                            'resident_name'
                        ]
                        ?? 'Unknown',

                    'resident_status' =>
                        $resident[
                            'resident_status'
                        ]
                        ?? 'UNKNOWN',
                ];
            }
        }

        $operationalPrioritySafe =
            empty(
                $invalidOperationalResidents
            );

        if (!$operationalPrioritySafe) {
            $criticalIssues[] =
                'One or more non-active residents are incorrectly included in current operational priority intelligence.';
        }

        $checks[
            'operational_priority_residents_active_only'
        ] = [

            'passed' =>
                $operationalPrioritySafe,

            'invalid_residents' =>
                $invalidOperationalResidents,

            'message' =>
                $operationalPrioritySafe
                ?
                'Current operational priority intelligence contains active-care residents only.'
                :
                'Non-active residents were detected in current operational priority intelligence.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 7. Historical Intelligence Isolation
        |--------------------------------------------------------------------------
        */

        $historicalExcluded =
            (bool) (
                $historicalIntelligence[
                    'excluded_from_current_escalation'
                ]
                ??
                $reportGuardrails[
                    'historical_cases_excluded_from_current_escalation'
                ]
                ??
                false
            );

        if (!$historicalExcluded) {
            $criticalIssues[] =
                'Historical resident intelligence is not explicitly isolated from current-care escalation.';
        }

        $checks[
            'historical_intelligence_isolated'
        ] = [

            'passed' =>
                $historicalExcluded,

            'message' =>
                $historicalExcluded
                ?
                'Historical resident intelligence is excluded from current operational escalation.'
                :
                'Historical resident intelligence is not safely separated from operational escalation.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 8. Narrative Status Consistency
        |--------------------------------------------------------------------------
        */

        $narrativeStatus =
            strtoupper(
                (string) (
                    $narrative[
                        'narrative_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $narrativeConsistent =
            true;

        if (
            $reportStatus === 'CRITICAL'
            &&
            !in_array(
                $narrativeStatus,
                [
                    'CRITICAL',
                    'ATTENTION REQUIRED',
                    'HIGH ATTENTION',
                ],
                true
            )
        ) {
            $narrativeConsistent =
                false;

            $warnings[] =
                'Executive narrative status differs materially from the structured executive report status.';
        }

        $checks[
            'narrative_matches_structured_intelligence'
        ] = [

            'passed' =>
                $narrativeConsistent,

            'report_status' =>
                $reportStatus,

            'narrative_status' =>
                $narrativeStatus,

            'message' =>
                $narrativeConsistent
                ?
                'Executive narrative status is consistent with structured intelligence.'
                :
                'Narrative and structured executive status require review.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 9. Trend Evidence Safety
        |--------------------------------------------------------------------------
        */

        $trendStatus =
            strtoupper(
                (string) (
                    $trend[
                        'trend_status'
                    ]
                    ?? 'NO_DATA'
                )
            );

        $snapshotCount =
            (int) (
                $trend[
                    'snapshot_count_available'
                ]
                ?? 0
            );

        $overallTrend =
            strtoupper(
                (string) (
                    $trend[
                        'overall_trend'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $trendEvidenceSafe =
            true;

        if (
            $snapshotCount < 2
            &&
            in_array(
                $overallTrend,
                [
                    'IMPROVING',
                    'WORSENING',
                    'MIXED',
                    'STABLE',
                ],
                true
            )
        ) {
            $trendEvidenceSafe =
                false;

            $criticalIssues[] =
                'Executive trend direction was generated without sufficient stored snapshot history.';
        }

        if (
            $trendStatus ===
                'COMPARISON_AVAILABLE'
            &&
            $snapshotCount < 2
        ) {
            $trendEvidenceSafe =
                false;

            $criticalIssues[] =
                'Trend comparison is marked available despite insufficient snapshot history.';
        }

        $checks[
            'trend_supported_by_history'
        ] = [

            'passed' =>
                $trendEvidenceSafe,

            'snapshot_count' =>
                $snapshotCount,

            'trend_status' =>
                $trendStatus,

            'overall_trend' =>
                $overallTrend,

            'message' =>
                $trendEvidenceSafe
                ?
                'Executive trend interpretation is supported by stored snapshot history.'
                :
                'Executive trend interpretation is not adequately supported by historical snapshots.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 10. Workflow Performance Sample Safety
        |--------------------------------------------------------------------------
        */

        $evaluatedWorkflows =
            (int) (
                $careExecution[
                    'evaluated_workflows'
                ]
                ?? 0
            );

        $workflowSuccessRate =
            (float) (
                $careExecution[
                    'workflow_success_rate'
                ]
                ?? 0
            );

        $workflowSampleStatus =
            'NO DATA';

        if ($evaluatedWorkflows >= 20) {
            $workflowSampleStatus =
                'MATURE';

        } elseif ($evaluatedWorkflows >= 5) {
            $workflowSampleStatus =
                'DEVELOPING';

        } elseif ($evaluatedWorkflows > 0) {
            $workflowSampleStatus =
                'LIMITED';
        }

        if (
            $evaluatedWorkflows > 0
            &&
            $evaluatedWorkflows < 5
        ) {
            $warnings[] =
                'Current AI workflow effectiveness statistics are based on a very limited sample.';
        }

        $checks[
            'workflow_performance_sample_size'
        ] = [

            'passed' =>
                true,

            'evaluated_workflows' =>
                $evaluatedWorkflows,

            'workflow_success_rate' =>
                $workflowSuccessRate,

            'sample_status' =>
                $workflowSampleStatus,

            'message' =>
                $evaluatedWorkflows < 5
                ?
                'Workflow effectiveness is preliminary because fewer than 5 workflows have been evaluated.'
                :
                'Workflow effectiveness has sufficient accumulated data for broader interpretation.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 11. Automatic Clinical Action Guardrail
        |--------------------------------------------------------------------------
        */

        $automaticClinicalAction =
            (bool) (
                $reportGuardrails[
                    'automatic_clinical_action'
                ]
                ?? false
            );

        $humanReviewRequired =
            (bool) (
                $reportGuardrails[
                    'human_review_required_for_clinical_actions'
                ]
                ?? false
            );

        $clinicalActionSafe =
            (
                $automaticClinicalAction === false
                &&
                $humanReviewRequired === true
            );

        if (!$clinicalActionSafe) {
            $criticalIssues[] =
                'Executive reporting guardrails do not adequately enforce human review of clinical actions.';
        }

        $checks[
            'clinical_action_human_review_guardrail'
        ] = [

            'passed' =>
                $clinicalActionSafe,

            'automatic_clinical_action' =>
                $automaticClinicalAction,

            'human_review_required' =>
                $humanReviewRequired,

            'message' =>
                $clinicalActionSafe
                ?
                'Executive reporting does not authorize automatic clinical action and requires human review.'
                :
                'Clinical action safety guardrails require correction.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 12. Determine Safety Status
        |--------------------------------------------------------------------------
        */

        $safetyStatus =
            'SAFE';

        if (!empty($criticalIssues)) {
            $safetyStatus =
                'BLOCKED';

        } elseif (!empty($warnings)) {
            $safetyStatus =
                'SAFE_WITH_WARNINGS';
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Executive Reporting Readiness
        |--------------------------------------------------------------------------
        */

        $reportingReady =
            empty(
                $criticalIssues
            );

        /*
        |--------------------------------------------------------------------------
        | 14. Final Safety Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'safety_status' =>
                $safetyStatus,

            'executive_reporting_ready' =>
                $reportingReady,

            'validation_summary' => [

                'total_checks' =>
                    count(
                        $checks
                    ),

                'passed_checks' =>
                    collect(
                        $checks
                    )
                    ->filter(
                        fn ($check) =>
                            (
                                $check[
                                    'passed'
                                ]
                                ?? false
                            ) === true
                    )
                    ->count(),

                'failed_checks' =>
                    collect(
                        $checks
                    )
                    ->filter(
                        fn ($check) =>
                            (
                                $check[
                                    'passed'
                                ]
                                ?? false
                            ) === false
                    )
                    ->count(),

                'warnings' =>
                    count(
                        $warnings
                    ),

                'critical_issues' =>
                    count(
                        $criticalIssues
                    ),
            ],

            'checks' =>
                $checks,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'current_reporting_context' => [

                'report_status' =>
                    $reportStatus,

                'narrative_status' =>
                    $narrativeStatus,

                'trend_status' =>
                    $trendStatus,

                'overall_trend' =>
                    $overallTrend,

                'snapshot_count' =>
                    $snapshotCount,

                'active_critical_cases' =>
                    $activeCriticalCases,

                'active_care_alerts' =>
                    $activeAlerts,

                'evaluated_workflows' =>
                    $evaluatedWorkflows,

                'workflow_success_rate' =>
                    $workflowSuccessRate,

                'workflow_sample_status' =>
                    $workflowSampleStatus,
            ],

            'reporting_guardrails' => [

                'clinical_decision_replacement' =>
                    false,

                'automatic_clinical_action' =>
                    false,

                'automatic_management_action' =>
                    false,

                'historical_cases_excluded_from_current_escalation' =>
                    true,

                'trend_requires_stored_history' =>
                    true,

                'small_sample_performance_requires_caution' =>
                    true,

                'human_review_required' =>
                    true,

                'message' =>
                    'Executive AI reporting is advisory and requires human interpretation. It does not replace clinical decision-making or authorize automatic clinical action.',
            ],
        ];
    }
}