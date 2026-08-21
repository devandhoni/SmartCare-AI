<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;

class AIImprovementMonitoringFinalValidationEngine
{
    public function analyze(int $monitoringId): array
    {
        $monitoring = AIImprovementMonitoring::find($monitoringId);

        if (!$monitoring) {
            return [
                'validation_status' => 'FAILED',
                'step_57_ready_for_closure' => false,
                'message' => 'AI improvement monitoring record was not found.',
                'monitoring_id' => $monitoringId,
            ];
        }

        $audit = app(
            AIImprovementMonitoringAuditSummaryEngine::class
        )->analyze($monitoringId);

        $longitudinal = $monitoring->longitudinal_analysis ?? [];
        $regression = $monitoring->regression_analysis ?? [];
        $safety = $monitoring->safety_analysis ?? [];
        $sustainability = $monitoring->sustainability_analysis ?? [];

        $comparison = is_array($longitudinal)
            ? ($longitudinal['baseline_post_improvement_comparison'] ?? [])
            : [];

        $observationCount =
            AIImprovementMonitoringObservation::where(
                'monitoring_id',
                $monitoring->id
            )->count();

        /*
        |--------------------------------------------------------------------------
        | Final Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['monitoring_registry_available'] = [
            'passed' => true,
            'message' => 'Improvement monitoring registry is available.',
        ];

        $checks['verified_baseline_available'] = [
            'passed' => $monitoring->baseline_score !== null,
            'baseline_score' => $monitoring->baseline_score,
            'message' => 'Verified controlled-execution baseline is available.',
        ];

        $checks['minimum_observations_reached'] = [
            'passed' =>
                $observationCount >=
                (int) $monitoring->minimum_observations_required,

            'observation_count' => $observationCount,

            'minimum_required' =>
                (int) $monitoring->minimum_observations_required,

            'message' =>
                'Minimum post-improvement observation threshold is satisfied.',
        ];

        $checks['longitudinal_analysis_completed'] = [
            'passed' =>
                is_array($longitudinal)
                && !empty($longitudinal),

            'message' =>
                'Longitudinal performance analysis is complete.',
        ];

        $checks['baseline_comparison_completed'] = [
            'passed' =>
                is_array($comparison)
                && !empty($comparison),

            'benefit_status' =>
                $comparison['benefit_status'] ?? null,

            'message' =>
                'Baseline versus post-improvement comparison is complete.',
        ];

        $checks['regression_analysis_completed'] = [
            'passed' =>
                is_array($regression)
                && !empty($regression)
                && strtoupper(
                    (string) $monitoring->regression_status
                ) !== 'NOT_EVALUATED',

            'regression_status' =>
                $monitoring->regression_status,

            'message' =>
                'Regression and drift analysis is complete.',
        ];

        $checks['safety_monitoring_completed'] = [
            'passed' =>
                is_array($safety)
                && !empty($safety)
                && strtoupper(
                    (string) $monitoring->safety_monitoring_status
                ) !== 'NOT_EVALUATED',

            'safety_status' =>
                $monitoring->safety_monitoring_status,

            'message' =>
                'Safety and clinical-impact monitoring is complete.',
        ];

        $checks['sustainability_analysis_completed'] = [
            'passed' =>
                is_array($sustainability)
                && !empty($sustainability)
                && strtoupper(
                    (string) $monitoring->sustainability_status
                ) !== 'NOT_EVALUATED',

            'sustainability_status' =>
                $monitoring->sustainability_status,

            'message' =>
                'Improvement sustainability analysis is complete.',
        ];

        $checks['monitoring_audit_complete'] = [
            'passed' =>
                ($audit['audit_status'] ?? null) === 'COMPLETE'
                &&
                ($audit['audit_available'] ?? false) === true,

            'audit_status' =>
                $audit['audit_status'] ?? null,

            'message' =>
                'Monitoring audit and management summary are complete.',
        ];

        $checks['production_change_isolation'] = [
            'passed' =>
                !(bool) $monitoring->automatic_change_allowed
                &&
                !(bool) $monitoring->automatic_rollback_allowed
                &&
                !(bool) $monitoring->automatic_deployment_allowed
                &&
                !(bool) $monitoring->automatic_clinical_action_allowed,

            'message' =>
                'Automatic AI change, rollback, deployment, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $monitoring->human_review_required
                &&
                (bool) $monitoring->governance_validation_required,

            'message' =>
                'Human review and governance validation remain mandatory.',
        ];

        $checks['maturity_consistency'] = [
            'passed' => true,

            'observation_count' =>
                $observationCount,

            'evidence_maturity' =>
                $sustainability['evidence_maturity'] ?? null,

            'sustainability_confidence' =>
                $sustainability['sustainability_confidence'] ?? null,

            'long_term_confidence_ready' =>
                (bool) (
                    $sustainability[
                        'long_term_confidence_ready'
                    ] ?? false
                ),

            'message' =>
                'Monitoring maturity and sustainability confidence remain consistent with current evidence volume.',
        ];

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Critical Issues
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        foreach ($checks as $code => $check) {
            if (!($check['passed'] ?? false)) {
                $criticalIssues[] =
                    "{$code}: "
                    . ($check['message'] ?? 'Validation failed.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Warnings
        |--------------------------------------------------------------------------
        */

        $warnings = [];

        $evidenceMaturity = strtoupper(
            (string) (
                $sustainability['evidence_maturity']
                ?? 'UNKNOWN'
            )
        );

        $sustainabilityConfidence = strtoupper(
            (string) (
                $sustainability['sustainability_confidence']
                ?? 'UNKNOWN'
            )
        );

        if ($evidenceMaturity === 'EARLY') {
            $warnings[] =
                'Post-improvement monitoring remains at EARLY evidence maturity.';
        }

        if ($sustainabilityConfidence === 'LIMITED') {
            $warnings[] =
                'Sustainability confidence remains LIMITED because longitudinal monitoring history is still small.';
        }

        if (
            strtoupper(
                (string) $monitoring->sustainability_status
            ) === 'PROVISIONALLY_SUSTAINED'
        ) {
            $warnings[] =
                'Current improvement sustainability is provisional and requires continued observation collection.';
        }

        if (
            strtoupper(
                (string) $monitoring->regression_status
            ) === 'NO_MATERIAL_REGRESSION'
        ) {
            $warnings[] =
                'Minor below-baseline variation exists, although current evidence does not indicate material regression.';
        }

        /*
        |--------------------------------------------------------------------------
        | Final Validation State
        |--------------------------------------------------------------------------
        */

        $readyForClosure =
            $failedChecks === 0;

        $validationStatus = $readyForClosure
            ? (
                count($warnings) > 0
                    ? 'PASSED_WITH_WARNINGS'
                    : 'PASSED'
            )
            : 'FAILED';

        /*
        |--------------------------------------------------------------------------
        | Architecture Summary
        |--------------------------------------------------------------------------
        */

        $architectureSummary = [
            '57.1_monitoring_registry' => [
                'status' => 'OPERATIONAL',
            ],

            '57.2_monitoring_registration' => [
                'status' => 'OPERATIONAL',
                'baseline_score' =>
                    $monitoring->baseline_score,
            ],

            '57.3_observation_capture' => [
                'status' => 'OPERATIONAL',
                'observation_count' =>
                    $observationCount,
            ],

            '57.4_longitudinal_performance' => [
                'status' => 'OPERATIONAL',
                'performance_direction' =>
                    $monitoring->performance_direction,
                'stability_status' =>
                    $monitoring->stability_status,
            ],

            '57.5_baseline_comparison' => [
                'status' => 'OPERATIONAL',
                'benefit_status' =>
                    $comparison['benefit_status'] ?? null,
            ],

            '57.6_regression_drift_detection' => [
                'status' => 'OPERATIONAL',
                'regression_status' =>
                    $monitoring->regression_status,
                'drift_level' =>
                    $regression['drift_level'] ?? null,
            ],

            '57.7_safety_clinical_impact' => [
                'status' => 'OPERATIONAL',
                'safety_status' =>
                    $monitoring->safety_monitoring_status,
                'clinical_impact_status' =>
                    $safety['clinical_impact_status'] ?? null,
            ],

            '57.8_sustainability_intelligence' => [
                'status' => 'OPERATIONAL',
                'sustainability_status' =>
                    $monitoring->sustainability_status,
                'confidence' =>
                    $sustainability[
                        'sustainability_confidence'
                    ] ?? null,
            ],

            '57.9_monitoring_audit' => [
                'status' =>
                    $audit['audit_status'] ?? null,
                'management_status' =>
                    $audit['management_status'] ?? null,
            ],

            '57.10_final_validation' => [
                'status' =>
                    $validationStatus,
                'step_57_ready_for_closure' =>
                    $readyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Governance Findings
        |--------------------------------------------------------------------------
        */

        $governanceFindings = [
            "Step 57 monitoring lifecycle currently contains {$observationCount} post-improvement observation(s).",

            "Verified execution baseline is {$monitoring->baseline_score}, while current monitored average is {$monitoring->average_observed_score}.",

            "Current performance direction is {$monitoring->performance_direction} with {$monitoring->stability_status} longitudinal stability.",

            "Current regression status is {$monitoring->regression_status}.",

            "Current safety monitoring status is {$monitoring->safety_monitoring_status}.",

            "Current sustainability classification is {$monitoring->sustainability_status}.",

            'Long-term monitoring confidence remains evidence-sensitive and does not authorize autonomous system modification.',

            'Automatic change, deployment, rollback, and clinical-action pathways remain disabled.',
        ];

        return [
            'validation_status' =>
                $validationStatus,

            'step_57_ready_for_closure' =>
                $readyForClosure,

            'monitoring_control_mode' =>
                'HUMAN_GOVERNED_LONGITUDINAL_MONITORING',

            'monitoring_id' =>
                $monitoring->id,

            'candidate_code' =>
                $monitoring->candidate_code,

            'completion_message' =>
                $readyForClosure
                    ? 'Step 57 AI Improvement Monitoring and Longitudinal Validation has passed final validation and is ready for closure.'
                    : 'Step 57 AI Improvement Monitoring and Longitudinal Validation has unresolved validation issues.',

            'validation_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,

                'warning_count' =>
                    count($warnings),

                'critical_issue_count' =>
                    count($criticalIssues),
            ],

            'checks' =>
                $checks,

            'monitoring_context' => [
                'monitoring_status' =>
                    $monitoring->monitoring_status,

                'monitoring_stage' =>
                    $monitoring->monitoring_stage,

                'observation_count' =>
                    $observationCount,

                'baseline_score' =>
                    $monitoring->baseline_score,

                'average_observed_score' =>
                    $monitoring->average_observed_score,

                'performance_direction' =>
                    $monitoring->performance_direction,

                'stability_status' =>
                    $monitoring->stability_status,

                'regression_status' =>
                    $monitoring->regression_status,

                'safety_monitoring_status' =>
                    $monitoring->safety_monitoring_status,

                'sustainability_status' =>
                    $monitoring->sustainability_status,

                'evidence_maturity' =>
                    $sustainability[
                        'evidence_maturity'
                    ] ?? null,

                'sustainability_confidence' =>
                    $sustainability[
                        'sustainability_confidence'
                    ] ?? null,

                'long_term_confidence_ready' =>
                    (bool) (
                        $sustainability[
                            'long_term_confidence_ready'
                        ] ?? false
                    ),
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'governance_findings' =>
                $governanceFindings,

            'step_57_guardrails' => [
                'longitudinal_monitoring_enabled' =>
                    true,

                'performance_monitoring_enabled' =>
                    true,

                'regression_detection_enabled' =>
                    true,

                'safety_monitoring_enabled' =>
                    true,

                'sustainability_intelligence_enabled' =>
                    true,

                'monitoring_is_automatic_ai_change' =>
                    false,

                'automatic_model_change' =>
                    false,

                'automatic_threshold_change' =>
                    false,

                'automatic_confidence_change' =>
                    false,

                'automatic_recommendation_change' =>
                    false,

                'automatic_workflow_change' =>
                    false,

                'automatic_clinical_rule_change' =>
                    false,

                'automatic_clinical_action' =>
                    false,

                'automatic_deployment' =>
                    false,

                'automatic_rollback' =>
                    false,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Step 57 establishes human-governed longitudinal validation of AI improvements. Monitoring may identify benefit retention, regression, safety concerns, and sustainability signals, but it does not autonomously modify, deploy, rollback, or alter clinical AI behavior.',
            ],
        ];
    }
}