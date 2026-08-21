<?php

namespace App\Services;

class AIExecutiveNarrativeEngine
{
    protected AIExecutiveIntelligenceReportEngine $reportEngine;

    protected AIExecutiveRiskSummaryEngine $riskSummaryEngine;

    protected AIExecutivePerformanceSummaryEngine $performanceSummaryEngine;


    public function __construct(
        AIExecutiveIntelligenceReportEngine $reportEngine,
        AIExecutiveRiskSummaryEngine $riskSummaryEngine,
        AIExecutivePerformanceSummaryEngine $performanceSummaryEngine
    ) {
        $this->reportEngine =
            $reportEngine;

        $this->riskSummaryEngine =
            $riskSummaryEngine;

        $this->performanceSummaryEngine =
            $performanceSummaryEngine;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 53.4
    | Executive Narrative Intelligence
    |--------------------------------------------------------------------------
    */

    public function analyze(): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Existing Executive Intelligence
        |--------------------------------------------------------------------------
        */

        $report =
            $this->reportEngine->analyze();

        $riskSummary =
            $this->riskSummaryEngine->analyze();

        $performanceSummary =
            $this->performanceSummaryEngine->analyze();


        /*
        |--------------------------------------------------------------------------
        | 2. Extract Main Data
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

        $historicalIntelligence =
            $report['historical_intelligence']
            ?? [];

        $activePriorityResidents =
            $riskSummary['active_priority_residents']
            ?? [];

        $workflowPerformance =
            $performanceSummary['workflow_performance']
            ?? [];

        $aiOutcomePerformance =
            $performanceSummary['ai_outcome_performance']
            ?? [];

        $operationalPerformance =
            $performanceSummary['operational_performance']
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | 3. Basic Executive Values
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

        $criticalCases =
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

        $predictivePriorityResidents =
            (int) (
                $operationalRisk[
                    'predictive_priority_residents'
                ]
                ?? 0
            );

        $carePriorityResidents =
            (int) (
                $operationalRisk[
                    'care_priority_residents'
                ]
                ?? 0
            );

        $executionReadyActions =
            (int) (
                $careExecution[
                    'execution_ready_actions'
                ]
                ?? 0
            );

        $doctorReviewActions =
            (int) (
                $careExecution[
                    'doctor_review_actions'
                ]
                ?? 0
            );

        $workflowSuccessRate =
            (float) (
                $workflowPerformance[
                    'workflow_success_rate'
                ]
                ?? 0
            );

        $evaluatedWorkflows =
            (int) (
                $workflowPerformance[
                    'evaluated_workflows'
                ]
                ?? 0
            );

        $learningMaturity =
            $workflowPerformance[
                'learning_maturity'
            ]
            ?? 'AWAITING OUTCOME DATA';

        $learningConfidence =
            $workflowPerformance[
                'learning_confidence'
            ]
            ?? 'NONE';

        $averageAIAccuracy =
            (float) (
                $aiOutcomePerformance[
                    'average_ai_accuracy'
                ]
                ?? 0
            );

        $interventionSuccessRate =
            (float) (
                $aiOutcomePerformance[
                    'intervention_success_rate'
                ]
                ?? 0
            );

        $slaCompliance =
            (float) (
                $operationalPerformance[
                    'sla_compliance_percentage'
                ]
                ?? 0
            );

        $taskCompletionRate =
            (float) (
                $operationalPerformance[
                    'task_completion_rate'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | 4. Executive Status Narrative
        |--------------------------------------------------------------------------
        */

        $executiveStatus =
            $riskSummary['attention_level']
            ?? 'ROUTINE';

        $headline =
            'Facility AI intelligence indicates stable operational conditions.';

        if ($executiveStatus === 'CRITICAL') {

            $headline =
                'Facility AI intelligence indicates active clinical risk requiring management attention.';

        } elseif ($executiveStatus === 'HIGH ATTENTION') {

            $headline =
                'Facility AI intelligence indicates elevated operational attention requirements.';
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Clinical Risk Narrative
        |--------------------------------------------------------------------------
        */

        $clinicalRiskNarrative =
            'No active critical resident currently requires executive escalation.';

        if ($criticalCases > 0) {

            $clinicalRiskNarrative =
                $criticalCases
                . ' active critical resident case(s) currently require clinical attention.';

            if ($predictivePriorityResidents > 0) {

                $clinicalRiskNarrative .=
                    ' '
                    . $predictivePriorityResidents
                    . ' active resident(s) are also identified by predictive intelligence as priority cases.';
            }

            if ($activeAlerts > 0) {

                $clinicalRiskNarrative .=
                    ' '
                    . $activeAlerts
                    . ' active-care AI alert(s) remain open.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Priority Resident Narrative
        |--------------------------------------------------------------------------
        */

        $priorityResidentNarratives = [];

        foreach ($activePriorityResidents as $resident) {

            if (!is_array($resident)) {
                continue;
            }

            $name =
                $resident['resident_name']
                ?? 'Unknown Resident';

            $clinicalSeverity =
                strtoupper(
                    (string) (
                        $resident[
                            'clinical_severity'
                        ]
                        ?? 'UNKNOWN'
                    )
                );

            $deteriorationRisk =
                strtoupper(
                    (string) (
                        $resident[
                            'deterioration_risk'
                        ]
                        ?? 'UNKNOWN'
                    )
                );

            $carePriority =
                strtoupper(
                    (string) (
                        $resident[
                            'care_priority'
                        ]
                        ?? 'UNKNOWN'
                    )
                );

            $primaryDriver =
                $resident['primary_driver']
                ?? null;

            $actionTiming =
                strtoupper(
                    (string) (
                        $resident[
                            'action_timing'
                        ]
                        ?? 'ROUTINE'
                    )
                );

            $sentence =
                $name
                . ' is an active priority resident';

            if ($clinicalSeverity !== 'UNKNOWN') {

                $sentence .=
                    ' with '
                    . strtolower($clinicalSeverity)
                    . ' current clinical severity';
            }

            if ($deteriorationRisk !== 'UNKNOWN') {

                $sentence .=
                    ' and '
                    . strtolower($deteriorationRisk)
                    . ' predicted deterioration risk';
            }

            if (
                $carePriority !== 'UNKNOWN'
                &&
                $carePriority !== ''
            ) {

                $sentence .=
                    ', with a '
                    . strtolower($carePriority)
                    . ' AI care priority';
            }

            if ($primaryDriver) {

                $sentence .=
                    '. The primary care driver is '
                    . $primaryDriver;
            }

            if ($actionTiming === 'IMMEDIATE') {

                $sentence .=
                    ', and immediate human-reviewed clinical action is indicated';
            }

            $sentence .= '.';

            $priorityResidentNarratives[] =
                $sentence;
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Care Execution Narrative
        |--------------------------------------------------------------------------
        */

        $careExecutionNarrative =
            'No AI-supported care action is currently awaiting workflow review.';

        if ($executionReadyActions > 0) {

            $careExecutionNarrative =
                $executionReadyActions
                . ' AI-supported care action(s) are currently execution-ready for human-reviewed workflow.';

            if ($doctorReviewActions > 0) {

                $careExecutionNarrative .=
                    ' '
                    . $doctorReviewActions
                    . ' action(s) specifically require physician review.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 8. AI Performance Narrative
        |--------------------------------------------------------------------------
        */

        $aiPerformanceNarrative =
            'Insufficient evaluated AI workflow data is currently available for performance interpretation.';

        if ($evaluatedWorkflows > 0) {

            $aiPerformanceNarrative =
                'AI-supported care workflows currently show a '
                . $workflowSuccessRate
                . '% success rate across '
                . $evaluatedWorkflows
                . ' evaluated workflow(s).';

            if ($averageAIAccuracy > 0) {

                $aiPerformanceNarrative .=
                    ' Recorded AI outcome accuracy averages '
                    . $averageAIAccuracy
                    . '%.';
            }

            if ($interventionSuccessRate > 0) {

                $aiPerformanceNarrative .=
                    ' Recorded intervention success is '
                    . $interventionSuccessRate
                    . '%.';
            }

            if (
                $learningMaturity ===
                'EARLY LEARNING'
            ) {

                $aiPerformanceNarrative .=
                    ' These results remain preliminary because the current learning sample is small.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Operational Performance Narrative
        |--------------------------------------------------------------------------
        */

        $operationalNarrative =
            'Operational workflow performance remains within current monitoring parameters.';

        if (
            $slaCompliance > 0
            ||
            $taskCompletionRate > 0
        ) {

            $operationalNarrative =
                'Current escalation SLA compliance is '
                . $slaCompliance
                . '%, while nurse task completion is '
                . $taskCompletionRate
                . '%.';

            if ($slaCompliance < 75) {

                $operationalNarrative .=
                    ' Escalation response performance should be reviewed for possible workflow delays.';
            }

            if ($taskCompletionRate < 75) {

                $operationalNarrative .=
                    ' Pending nursing workload should also be reviewed.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Historical Intelligence Narrative
        |--------------------------------------------------------------------------
        */

        $historicalCriticalCases =
            (int) (
                $historicalIntelligence[
                    'historical_critical_cases'
                ]
                ?? 0
            );

        $historicalAlerts =
            (int) (
                $historicalIntelligence[
                    'historical_open_alerts'
                ]
                ?? 0
            );

        $historicalNarrative =
            'No significant historical non-active clinical intelligence is currently highlighted.';

        if (
            $historicalCriticalCases > 0
            ||
            $historicalAlerts > 0
        ) {

            $historicalNarrative =
                'Historical intelligence retains '
                . $historicalCriticalCases
                . ' non-active critical case(s) and '
                . $historicalAlerts
                . ' non-active open alert(s). These records are excluded from current operational escalation.';
        }


        /*
        |--------------------------------------------------------------------------
        | 11. Executive Management Priorities
        |--------------------------------------------------------------------------
        */

        $managementPriorities = [];

        if ($criticalCases > 0) {

            $managementPriorities[] =
                'Confirm timely review of all active critical residents.';
        }

        if ($activeAlerts > 0) {

            $managementPriorities[] =
                'Review unresolved active-care AI alerts and associated escalation workload.';
        }

        if ($doctorReviewActions > 0) {

            $managementPriorities[] =
                'Ensure AI-supported care actions requiring physician review are assigned appropriately.';
        }

        if ($executionReadyActions > 0) {

            $managementPriorities[] =
                'Confirm execution-ready AI care recommendations receive human review before operational action.';
        }

        if ($slaCompliance < 75) {

            $managementPriorities[] =
                'Review escalation SLA performance and identify potential response-time bottlenecks.';
        }

        if ($taskCompletionRate < 75) {

            $managementPriorities[] =
                'Review pending nursing workload and operational capacity.';
        }

        if (
            $learningMaturity ===
            'EARLY LEARNING'
        ) {

            $managementPriorities[] =
                'Continue collecting workflow outcome data before treating current AI effectiveness rates as mature evidence.';
        }

        if (empty($managementPriorities)) {

            $managementPriorities[] =
                'Continue routine review of executive AI intelligence indicators.';
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Build Executive Brief
        |--------------------------------------------------------------------------
        */

        $executiveBrief =
            $headline
            . ' '
            . $clinicalRiskNarrative
            . ' '
            . $careExecutionNarrative
            . ' '
            . $aiPerformanceNarrative
            . ' '
            . $operationalNarrative;


        /*
        |--------------------------------------------------------------------------
        | 13. Final Narrative Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            'narrative_status' =>
                $executiveStatus,

            'headline' =>
                $headline,

            'executive_brief' =>
                $executiveBrief,

            'narrative_sections' => [

                'clinical_risk' =>
                    $clinicalRiskNarrative,

                'priority_residents' =>
                    $priorityResidentNarratives,

                'care_execution' =>
                    $careExecutionNarrative,

                'ai_performance' =>
                    $aiPerformanceNarrative,

                'operational_performance' =>
                    $operationalNarrative,

                'historical_intelligence' =>
                    $historicalNarrative,
            ],

            'management_priorities' =>
                $managementPriorities,

            'intelligence_context' => [

                'active_care_residents' =>
                    $activeResidents,

                'non_active_residents' =>
                    $nonActiveResidents,

                'active_critical_cases' =>
                    $criticalCases,

                'predictive_priority_residents' =>
                    $predictivePriorityResidents,

                'care_priority_residents' =>
                    $carePriorityResidents,

                'learning_maturity' =>
                    $learningMaturity,

                'learning_confidence' =>
                    $learningConfidence,
            ],

            'narrative_guardrails' => [

                'source_intelligence_only' =>
                    true,

                'new_clinical_diagnosis_generated' =>
                    false,

                'automatic_clinical_action' =>
                    false,

                'historical_cases_excluded_from_current_escalation' =>
                    true,

                'human_review_required' =>
                    true,
            ],
        ];
    }
}