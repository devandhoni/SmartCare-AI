<?php

namespace App\Services;

class AIExecutiveReportingFinalValidationEngine
{
    protected AIExecutiveReportingPackageEngine $reportingPackageEngine;

    protected AIExecutiveReportingPeriodIntelligence $periodIntelligence;

    protected AIExecutiveReportingPeriodSummaryEngine $periodSummaryEngine;

    public function __construct(
        AIExecutiveReportingPackageEngine $reportingPackageEngine,
        AIExecutiveReportingPeriodIntelligence $periodIntelligence,
        AIExecutiveReportingPeriodSummaryEngine $periodSummaryEngine
    ) {
        $this->reportingPackageEngine =
            $reportingPackageEngine;

        $this->periodIntelligence =
            $periodIntelligence;

        $this->periodSummaryEngine =
            $periodSummaryEngine;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.10
    | Final Executive Reporting Validation
    |--------------------------------------------------------------------------
    */

    public function analyze(
        int $days = 7
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Reporting Period
        |--------------------------------------------------------------------------
        */

        $days =
            max(
                1,
                min(
                    $days,
                    365
                )
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Load Final Reporting Layers
        |--------------------------------------------------------------------------
        */

        $package =
            $this->reportingPackageEngine->generate();

        $period =
            $this->periodIntelligence->analyze(
                $days
            );

        $periodSummary =
            $this->periodSummaryEngine->analyze(
                $days
            );


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
        | 4. Package Reporting Readiness
        |--------------------------------------------------------------------------
        */

        $packageReady =
            (bool) (
                $package[
                    'executive_reporting_ready'
                ]
                ?? false
            );

        $checks[
            'executive_reporting_ready'
        ] = [

            'passed' =>
                $packageReady,

            'message' =>
                $packageReady
                ?
                'Executive reporting package is marked ready for management reporting.'
                :
                'Executive reporting package is not ready.',
        ];

        if (!$packageReady) {
            $criticalIssues[] =
                'Executive reporting package is not ready.';
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Reporting Safety Validation
        |--------------------------------------------------------------------------
        */

        $safetyStatus =
            strtoupper(
                (string) (
                    $package[
                        'reporting_safety'
                    ]['safety_status']
                    ?? 'UNKNOWN'
                )
            );

        $safetyCriticalIssues =
            $package[
                'reporting_safety'
            ]['critical_issues']
            ?? [];

        $safetyPassed =
            empty(
                $safetyCriticalIssues
            )
            &&
            in_array(
                $safetyStatus,
                [
                    'SAFE',
                    'SAFE_WITH_WARNINGS',
                ],
                true
            );

        $checks[
            'reporting_safety'
        ] = [

            'passed' =>
                $safetyPassed,

            'safety_status' =>
                $safetyStatus,

            'critical_issue_count' =>
                count(
                    $safetyCriticalIssues
                ),

            'message' =>
                $safetyPassed
                ?
                'Executive reporting safety validation passed.'
                :
                'Executive reporting safety validation identified blocking issues.',
        ];

        if (!$safetyPassed) {
            $criticalIssues[] =
                'Executive reporting safety validation failed.';
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Operational vs Historical Isolation
        |--------------------------------------------------------------------------
        */

        $currentPriority =
            $package[
                'priority_intelligence'
            ]['current_operational_priority']
            ?? [];

        $historical =
            $package[
                'priority_intelligence'
            ]['historical_intelligence']
            ?? [];

        $invalidOperationalResidents =
            [];

        foreach (
            [
                'predictive_priority',
                'care_priority',
            ]
            as $group
        ) {
            foreach (
                (
                    $currentPriority[
                        $group
                    ]
                    ?? []
                )
                as $resident
            ) {
                if (
                    !is_array(
                        $resident
                    )
                ) {
                    continue;
                }

                $active =
                    (bool) (
                        $resident[
                            'active_care_eligible'
                        ]
                        ?? false
                    );

                if (!$active) {
                    $invalidOperationalResidents[] =
                        [
                            'resident_id' =>
                                $resident[
                                    'resident_id'
                                ]
                                ?? null,

                            'resident_name' =>
                                $resident[
                                    'resident_name'
                                ]
                                ?? 'UNKNOWN',

                            'source_group' =>
                                $group,
                        ];
                }
            }
        }

        $historicalExcluded =
            (bool) (
                $historical[
                    'excluded_from_current_escalation'
                ]
                ?? false
            );

        $historicalIsolationPassed =
            empty(
                $invalidOperationalResidents
            )
            &&
            $historicalExcluded;

        $checks[
            'operational_historical_isolation'
        ] = [

            'passed' =>
                $historicalIsolationPassed,

            'invalid_operational_residents' =>
                $invalidOperationalResidents,

            'historical_excluded_from_current_escalation' =>
                $historicalExcluded,

            'message' =>
                $historicalIsolationPassed
                ?
                'Current operational priority intelligence contains active-care residents only, and historical intelligence is isolated.'
                :
                'Operational and historical intelligence isolation requires review.',
        ];

        if (!$historicalIsolationPassed) {
            $criticalIssues[] =
                'Historical or non-active resident intelligence may be leaking into current operational reporting.';
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Current Status Consistency
        |--------------------------------------------------------------------------
        */

        $reportStatus =
            strtoupper(
                (string) (
                    $package[
                        'current_status'
                    ]['report_status']
                    ?? 'UNKNOWN'
                )
            );

        $activeCriticalCases =
            (int) (
                $package[
                    'current_status'
                ]['active_critical_cases']
                ?? 0
            );

        $activeAlerts =
            (int) (
                $package[
                    'current_status'
                ]['active_care_alerts']
                ?? 0
            );

        $statusConsistent =
            true;

        if (
            (
                $activeCriticalCases > 0
                ||
                $activeAlerts > 0
            )
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
            $statusConsistent =
                false;
        }

        $checks[
            'current_status_consistency'
        ] = [

            'passed' =>
                $statusConsistent,

            'report_status' =>
                $reportStatus,

            'active_critical_cases' =>
                $activeCriticalCases,

            'active_care_alerts' =>
                $activeAlerts,

            'message' =>
                $statusConsistent
                ?
                'Executive report status is consistent with current operational risk.'
                :
                'Executive report status does not match current operational risk.',
        ];

        if (!$statusConsistent) {
            $criticalIssues[] =
                'Executive report status is inconsistent with current operational risk.';
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Trend Intelligence Support
        |--------------------------------------------------------------------------
        */

        $trend =
            $package[
                'trend_intelligence'
            ]
            ?? [];

        $snapshotCount =
            (int) (
                $trend[
                    'snapshot_count_available'
                ]
                ?? 0
            );

        $trendStatus =
            strtoupper(
                (string) (
                    $trend[
                        'trend_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $trendSupported =
            $snapshotCount >= 2
            ?
            $trendStatus ===
                'COMPARISON_AVAILABLE'
            :
            in_array(
                $trendStatus,
                [
                    'BASELINE_ONLY',
                    'NO_DATA',
                ],
                true
            );

        $checks[
            'trend_history_support'
        ] = [

            'passed' =>
                $trendSupported,

            'snapshot_count' =>
                $snapshotCount,

            'trend_status' =>
                $trendStatus,

            'message' =>
                $trendSupported
                ?
                'Executive trend interpretation is supported by stored snapshot history.'
                :
                'Executive trend interpretation does not match available snapshot history.',
        ];

        if (!$trendSupported) {
            $criticalIssues[] =
                'Trend interpretation is inconsistent with available stored snapshot history.';
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Reporting Period Consistency
        |--------------------------------------------------------------------------
        */

        $periodDays =
            (int) (
                $period[
                    'reporting_period'
                ]['days']
                ?? 0
            );

        $summaryDays =
            (int) (
                $periodSummary[
                    'reporting_period_days'
                ]
                ?? 0
            );

        $periodStatus =
            strtoupper(
                (string) (
                    $period[
                        'period_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $summaryStatus =
            strtoupper(
                (string) (
                    $periodSummary[
                        'summary_status'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $periodConsistent =
            $periodDays ===
                $days
            &&
            $summaryDays ===
                $days
            &&
            (
                (
                    $periodStatus ===
                    'AVAILABLE'
                    &&
                    $summaryStatus ===
                    'AVAILABLE'
                )
                ||
                (
                    $periodStatus !==
                    'AVAILABLE'
                    &&
                    $summaryStatus ===
                    'NO_DATA'
                )
            );

        $checks[
            'reporting_period_consistency'
        ] = [

            'passed' =>
                $periodConsistent,

            'requested_days' =>
                $days,

            'period_intelligence_days' =>
                $periodDays,

            'period_summary_days' =>
                $summaryDays,

            'period_status' =>
                $periodStatus,

            'period_summary_status' =>
                $summaryStatus,

            'message' =>
                $periodConsistent
                ?
                'Reporting-period intelligence and management summary are aligned.'
                :
                'Reporting-period intelligence and management summary are inconsistent.',
        ];

        if (!$periodConsistent) {
            $criticalIssues[] =
                'Reporting-period intelligence and management summary are inconsistent.';
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Coverage / Confidence Consistency
        |--------------------------------------------------------------------------
        */

        $coverageStatus =
            strtoupper(
                (string) (
                    $period[
                        'data_coverage'
                    ]['coverage_status']
                    ?? 'UNKNOWN'
                )
            );

        $periodConfidence =
            strtoupper(
                (string) (
                    $periodSummary[
                        'period_confidence'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $expectedConfidence =
            match (
                $coverageStatus
            ) {
                'GOOD' =>
                    'HIGHER',

                'PARTIAL' =>
                    'MODERATE',

                'LIMITED' =>
                    'LIMITED',

                default =>
                    'UNKNOWN',
            };

        $coverageConfidenceConsistent =
            $expectedConfidence ===
                'UNKNOWN'
            ||
            $periodConfidence ===
                $expectedConfidence;

        $checks[
            'coverage_confidence_consistency'
        ] = [

            'passed' =>
                $coverageConfidenceConsistent,

            'coverage_status' =>
                $coverageStatus,

            'period_confidence' =>
                $periodConfidence,

            'expected_confidence' =>
                $expectedConfidence,

            'message' =>
                $coverageConfidenceConsistent
                ?
                'Period confidence is consistent with snapshot coverage.'
                :
                'Period confidence does not match snapshot coverage.',
        ];

        if (!$coverageConfidenceConsistent) {
            $criticalIssues[] =
                'Reporting-period confidence is inconsistent with snapshot coverage.';
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Period Trend Consistency
        |--------------------------------------------------------------------------
        */

        $periodTrend =
            strtoupper(
                (string) (
                    $period[
                        'period_trend'
                    ]
                    ?? 'UNKNOWN'
                )
            );

        $summaryTrend =
            strtoupper(
                (string) (
                    $periodSummary[
                        'trend_context'
                    ]['period_trend']
                    ?? 'UNKNOWN'
                )
            );

        $periodTrendConsistent =
            $periodTrend ===
                $summaryTrend;

        $checks[
            'period_trend_consistency'
        ] = [

            'passed' =>
                $periodTrendConsistent,

            'period_intelligence_trend' =>
                $periodTrend,

            'management_summary_trend' =>
                $summaryTrend,

            'message' =>
                $periodTrendConsistent
                ?
                'Management summary trend matches reporting-period intelligence.'
                :
                'Management summary trend does not match reporting-period intelligence.',
        ];

        if (!$periodTrendConsistent) {
            $criticalIssues[] =
                'Management period trend is inconsistent with source reporting-period intelligence.';
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Small Sample Warning
        |--------------------------------------------------------------------------
        */

        $evaluatedWorkflows =
            (int) (
                $package[
                    'care_execution_summary'
                ]['evaluated_workflows']
                ?? 0
            );

        $workflowSuccessRate =
            (float) (
                $package[
                    'care_execution_summary'
                ]['workflow_success_rate']
                ?? 0
            );

        $workflowSampleStatus =
            $evaluatedWorkflows >= 20
            ?
            'MATURE'
            :
            (
                $evaluatedWorkflows >= 5
                ?
                'DEVELOPING'
                :
                'LIMITED'
            );

        if ($workflowSampleStatus === 'LIMITED') {
            $warnings[] =
                'AI workflow effectiveness remains based on a limited number of evaluated workflows.';
        }

        $checks[
            'workflow_sample_maturity'
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
                $workflowSampleStatus ===
                'LIMITED'
                ?
                'Workflow performance is valid but remains preliminary because the evaluated sample is small.'
                :
                'Workflow performance sample has reached a broader evidence level.',
        ];


        /*
        |--------------------------------------------------------------------------
        | 13. Period Coverage Warning
        |--------------------------------------------------------------------------
        */

        $coveragePercentage =
            (float) (
                $period[
                    'data_coverage'
                ]['coverage_percentage']
                ?? 0
            );

        if ($coverageStatus === 'LIMITED') {
            $warnings[] =
                'Selected reporting period has limited snapshot coverage at '
                . $coveragePercentage
                . '%.';
        }


        /*
        |--------------------------------------------------------------------------
        | 14. Human Review Guardrails
        |--------------------------------------------------------------------------
        */

        $guardrails =
            $package[
                'reporting_guardrails'
            ]
            ?? [];

        $automaticClinicalAction =
            (bool) (
                $guardrails[
                    'automatic_clinical_action'
                ]
                ?? true
            );

        $automaticManagementAction =
            (bool) (
                $guardrails[
                    'automatic_management_action'
                ]
                ?? true
            );

        $humanReviewRequired =
            (bool) (
                $guardrails[
                    'human_review_required'
                ]
                ?? false
            );

        $guardrailsPassed =
            !$automaticClinicalAction
            &&
            !$automaticManagementAction
            &&
            $humanReviewRequired;

        $checks[
            'human_review_guardrails'
        ] = [

            'passed' =>
                $guardrailsPassed,

            'automatic_clinical_action' =>
                $automaticClinicalAction,

            'automatic_management_action' =>
                $automaticManagementAction,

            'human_review_required' =>
                $humanReviewRequired,

            'message' =>
                $guardrailsPassed
                ?
                'Executive reporting remains advisory and requires human interpretation.'
                :
                'Executive reporting guardrails are not correctly configured.',
        ];

        if (!$guardrailsPassed) {
            $criticalIssues[] =
                'Human-review reporting guardrails are not correctly configured.';
        }


        /*
        |--------------------------------------------------------------------------
        | 15. Validation Counts
        |--------------------------------------------------------------------------
        */

        $passedChecks =
            0;

        $failedChecks =
            0;

        foreach ($checks as $check) {

            if (
                $check[
                    'passed'
                ]
                ?? false
            ) {
                $passedChecks++;
            } else {
                $failedChecks++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 16. Final Step 53 Status
        |--------------------------------------------------------------------------
        */

        $criticalIssues =
            array_values(
                array_unique(
                    $criticalIssues
                )
            );

        $warnings =
            array_values(
                array_unique(
                    $warnings
                )
            );

        $step53Ready =
            empty(
                $criticalIssues
            )
            &&
            $failedChecks === 0;

        $validationStatus =
            $step53Ready
            ?
            (
                empty(
                    $warnings
                )
                ?
                'PASSED'
                :
                'PASSED_WITH_WARNINGS'
            )
            :
            'FAILED';


        /*
        |--------------------------------------------------------------------------
        | 17. Step 53 Completion Summary
        |--------------------------------------------------------------------------
        */

        $completionMessage =
            $step53Ready
            ?
            'Step 53 Executive AI Intelligence Reporting has passed final validation and is ready for closure.'
            :
            'Step 53 Executive AI Intelligence Reporting requires correction before closure.';


        /*
        |--------------------------------------------------------------------------
        | 18. Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'validation_status' =>
                $validationStatus,

            'step_53_ready_for_closure' =>
                $step53Ready,

            'reporting_period_days' =>
                $days,

            'completion_message' =>
                $completionMessage,

            'validation_summary' => [

                'total_checks' =>
                    count(
                        $checks
                    ),

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'warning_count' =>
                    count(
                        $warnings
                    ),

                'critical_issue_count' =>
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

                'package_status' =>
                    $package[
                        'package_status'
                    ]
                    ?? 'UNKNOWN',

                'report_status' =>
                    $reportStatus,

                'overall_trend' =>
                    $package[
                        'current_status'
                    ]['overall_trend']
                    ?? 'UNKNOWN',

                'period_trend' =>
                    $periodTrend,

                'coverage_status' =>
                    $coverageStatus,

                'coverage_percentage' =>
                    $coveragePercentage,

                'workflow_sample_status' =>
                    $workflowSampleStatus,

                'evaluated_workflows' =>
                    $evaluatedWorkflows,

                'workflow_success_rate' =>
                    $workflowSuccessRate,
            ],

            'step_53_guardrails' => [

                'executive_reporting_advisory_only' =>
                    true,

                'automatic_clinical_action' =>
                    false,

                'automatic_management_action' =>
                    false,

                'historical_residents_excluded_from_current_escalation' =>
                    true,

                'trend_requires_stored_snapshots' =>
                    true,

                'reporting_period_interpretation_is_coverage_sensitive' =>
                    true,

                'small_sample_performance_requires_caution' =>
                    true,

                'human_interpretation_required' =>
                    true,

                'message' =>
                    'Step 53 provides executive decision-support intelligence only. Clinical and management actions remain subject to human review.',
            ],
        ];
    }
}