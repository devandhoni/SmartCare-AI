<?php

namespace App\Services;

use App\Models\AIImprovementMonitoring;
use App\Models\AIImprovementMonitoringObservation;

class AIImprovementMonitoringAuditSummaryEngine
{
    public function analyze(int $monitoringId): array
    {
        $monitoring = AIImprovementMonitoring::find($monitoringId);

        if (!$monitoring) {
            return [
                'audit_available' => false,
                'audit_status' => 'MONITORING_NOT_FOUND',
                'message' => 'AI improvement monitoring record was not found.',
                'monitoring_id' => $monitoringId,
            ];
        }

        $longitudinal = $monitoring->longitudinal_analysis ?? [];
        $regression = $monitoring->regression_analysis ?? [];
        $safety = $monitoring->safety_analysis ?? [];
        $sustainability = $monitoring->sustainability_analysis ?? [];

        if (
            !is_array($longitudinal) ||
            !is_array($regression) ||
            !is_array($safety) ||
            !is_array($sustainability) ||
            empty($longitudinal) ||
            empty($regression) ||
            empty($safety) ||
            empty($sustainability)
        ) {
            return [
                'audit_available' => false,
                'audit_status' => 'MONITORING_ANALYSIS_INCOMPLETE',
                'message' => 'Longitudinal, regression, safety, and sustainability analyses must be completed before monitoring audit consolidation.',
                'monitoring_id' => $monitoring->id,
            ];
        }

        $comparison =
            $longitudinal['baseline_post_improvement_comparison']
            ?? [];

        $observations =
            AIImprovementMonitoringObservation::where(
                'monitoring_id',
                $monitoring->id
            )
                ->orderBy('observed_at')
                ->orderBy('id')
                ->get();

        $observationCount =
            $observations->count();

        /*
        |--------------------------------------------------------------------------
        | Audit Integrity Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['monitoring_registry_available'] = [
            'passed' => true,
            'message' => 'Improvement monitoring registry record is available.',
        ];

        $checks['baseline_established'] = [
            'passed' =>
                $monitoring->baseline_score !== null,

            'message' =>
                'Verified controlled-execution baseline is available.',
        ];

        $checks['observations_recorded'] = [
            'passed' =>
                $observationCount > 0,

            'observation_count' =>
                $observationCount,

            'message' =>
                'Post-improvement monitoring observations are recorded.',
        ];

        $checks['minimum_observation_threshold_reached'] = [
            'passed' =>
                $observationCount >=
                (int) $monitoring->minimum_observations_required,

            'observation_count' =>
                $observationCount,

            'minimum_required' =>
                (int) $monitoring->minimum_observations_required,

            'message' =>
                'Minimum monitoring observation threshold has been reached.',
        ];

        $checks['longitudinal_analysis_available'] = [
            'passed' =>
                !empty($longitudinal),

            'message' =>
                'Longitudinal performance analysis is available.',
        ];

        $checks['baseline_comparison_available'] = [
            'passed' =>
                is_array($comparison)
                && !empty($comparison),

            'message' =>
                'Baseline versus post-improvement comparison is available.',
        ];

        $checks['regression_analysis_available'] = [
            'passed' =>
                !empty($regression),

            'message' =>
                'Regression and drift analysis is available.',
        ];

        $checks['safety_analysis_available'] = [
            'passed' =>
                !empty($safety),

            'message' =>
                'Safety and clinical-impact monitoring is available.',
        ];

        $checks['sustainability_analysis_available'] = [
            'passed' =>
                !empty($sustainability),

            'message' =>
                'Improvement sustainability intelligence is available.',
        ];

        $checks['automatic_change_isolation'] = [
            'passed' =>
                !(bool) $monitoring->automatic_change_allowed
                &&
                !(bool) $monitoring->automatic_rollback_allowed
                &&
                !(bool) $monitoring->automatic_deployment_allowed
                &&
                !(bool) $monitoring->automatic_clinical_action_allowed,

            'message' =>
                'Automatic change, rollback, deployment, and clinical-action permissions remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $monitoring->human_review_required
                &&
                (bool) $monitoring->governance_validation_required,

            'message' =>
                'Human review and governance validation remain mandatory.',
        ];

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $totalChecks = count($checks);

        $failedChecks =
            $totalChecks - $passedChecks;

        /*
        |--------------------------------------------------------------------------
        | Management Context
        |--------------------------------------------------------------------------
        */

        $benefitStatus = strtoupper(
            (string) (
                $comparison['benefit_status']
                ?? 'UNKNOWN'
            )
        );

        $regressionStatus = strtoupper(
            (string) $monitoring->regression_status
        );

        $safetyStatus = strtoupper(
            (string) $monitoring->safety_monitoring_status
        );

        $sustainabilityStatus = strtoupper(
            (string) $monitoring->sustainability_status
        );

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

        /*
        |--------------------------------------------------------------------------
        | Management Status
        |--------------------------------------------------------------------------
        */

        if (
            $failedChecks > 0
            ||
            $regressionStatus === 'REGRESSION_DETECTED'
            ||
            in_array(
                $safetyStatus,
                [
                    'SAFETY_CONCERN',
                    'BELOW_SAFETY_THRESHOLD',
                ],
                true
            )
            ||
            $sustainabilityStatus === 'NOT_SUSTAINED'
        ) {
            $managementStatus =
                'ATTENTION_REQUIRED';
        } elseif (
            $sustainabilityStatus ===
            'PROVISIONALLY_SUSTAINED'
        ) {
            $managementStatus =
                'CONTINUE_MONITORING';
        } elseif (
            $sustainabilityStatus === 'SUSTAINED'
        ) {
            $managementStatus =
                'SUSTAINED_MONITORING_SIGNAL';
        } else {
            $managementStatus =
                'REVIEW_REQUIRED';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Summary
        |--------------------------------------------------------------------------
        */

        $managementSummary =
            "Improvement monitoring currently contains {$observationCount} observation(s). "
            . "The verified execution baseline is {$monitoring->baseline_score}, while average monitored performance is {$monitoring->average_observed_score}. "
            . "Current benefit status is {$benefitStatus}, regression status is {$regressionStatus}, safety status is {$safetyStatus}, and sustainability status is {$sustainabilityStatus}. "
            . "Evidence maturity remains {$evidenceMaturity} with {$sustainabilityConfidence} sustainability confidence.";

        /*
        |--------------------------------------------------------------------------
        | Management Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            "Monitoring audit completed {$totalChecks} governance integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
            "Current monitored performance direction is {$monitoring->performance_direction}.",
            "Current stability classification is {$monitoring->stability_status}.",
            "Current retained-benefit classification is {$benefitStatus}.",
            "Current regression classification is {$regressionStatus}.",
            "Current safety classification is {$safetyStatus}.",
            "Current sustainability classification is {$sustainabilityStatus}.",
        ];

        if ($evidenceMaturity === 'EARLY') {
            $findings[] =
                'Monitoring evidence remains early and should continue accumulating before long-term effectiveness conclusions are treated as mature.';
        }

        /*
        |--------------------------------------------------------------------------
        | Management Priorities
        |--------------------------------------------------------------------------
        */

        $priorities = [];

        if ($observationCount < 10) {
            $priorities[] =
                'Continue structured post-improvement observation collection to strengthen monitoring confidence.';
        }

        if ($regressionStatus === 'NO_MATERIAL_REGRESSION') {
            $priorities[] =
                'Continue observing below-baseline fluctuations for any emerging regression pattern.';
        }

        if ($safetyStatus === 'SAFE_WITH_MONITORING') {
            $priorities[] =
                'Continue safety monitoring while longitudinal evidence accumulates.';
        }

        if (
            $sustainabilityStatus ===
            'PROVISIONALLY_SUSTAINED'
        ) {
            $priorities[] =
                'Do not treat the improvement as mature long-term evidence until additional longitudinal observations are available.';
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Result
        |--------------------------------------------------------------------------
        */

        return [
            'audit_status' =>
                $failedChecks === 0
                    ? 'COMPLETE'
                    : 'COMPLETE_WITH_ISSUES',

            'audit_available' =>
                true,

            'monitoring_id' =>
                $monitoring->id,

            'candidate_code' =>
                $monitoring->candidate_code,

            'candidate_category' =>
                $monitoring->candidate_category,

            'scope_type' =>
                $monitoring->scope_type,

            'resident_id' =>
                $monitoring->resident_id,

            'management_status' =>
                $managementStatus,

            'monitoring_context' => [
                'monitoring_status' =>
                    $monitoring->monitoring_status,

                'monitoring_stage' =>
                    $monitoring->monitoring_stage,

                'baseline_score' =>
                    $monitoring->baseline_score,

                'observation_count' =>
                    $monitoring->observation_count,

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
            ],

            'evidence_context' => [
                'evidence_maturity' =>
                    $evidenceMaturity,

                'sustainability_confidence' =>
                    $sustainabilityConfidence,

                'retention_ratio_percentage' =>
                    $comparison[
                        'retention_ratio_percentage'
                    ] ?? null,

                'sustainability_signal_score' =>
                    $sustainability[
                        'sustainability_signal_score'
                    ] ?? null,

                'long_term_confidence_ready' =>
                    (bool) (
                        $sustainability[
                            'long_term_confidence_ready'
                        ] ?? false
                    ),
            ],

            'audit_summary' => [
                'total_checks' =>
                    $totalChecks,

                'passed_checks' =>
                    $passedChecks,

                'failed_checks' =>
                    $failedChecks,
            ],

            'checks' =>
                $checks,

            'management_summary' =>
                $managementSummary,

            'management_findings' =>
                $findings,

            'management_priorities' =>
                $priorities,

            'timeline' => [
                'monitoring_started_at' =>
                    $monitoring->monitoring_started_at,

                'last_observed_at' =>
                    $monitoring->last_observed_at,

                'last_analyzed_at' =>
                    $monitoring->last_analyzed_at,

                'monitoring_completed_at' =>
                    $monitoring->monitoring_completed_at,
            ],

            'audit_guardrails' => [
                'audit_is_ai_change_authorization' =>
                    false,

                'audit_is_deployment_authorization' =>
                    false,

                'audit_is_rollback_authorization' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'human_governance_required' =>
                    true,

                'message' =>
                    'Monitoring audit consolidates longitudinal improvement evidence for management review only. It does not authorize AI modification, deployment, rollback, or clinical action.',
            ],
        ];
    }
}