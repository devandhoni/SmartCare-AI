<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;

class AIImprovementLifecycleFinalValidationEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'validation_status' => 'FAILED',
                'step_58_ready_for_closure' => false,
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'snapshot_id' => $snapshotId,
            ];
        }

        $lifecycle = app(
            AIImprovementLifecycleStateIntelligenceEngine::class
        )->analyze($snapshot->id);

        $governance = app(
            AIImprovementGovernanceProgressIntelligenceEngine::class
        )->analyze($snapshot->id);

        $evidence = app(
            AIImprovementEvidenceMaturityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $outcome = app(
            AIImprovementOutcomeIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIImprovementRiskExceptionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $executive = app(
            AIImprovementExecutivePortfolioIntelligenceEngine::class
        )->analyze($snapshot->id);

        $audit = app(
            AIImprovementLifecycleAuditSummaryEngine::class
        )->analyze($snapshot->id);

        /*
        |--------------------------------------------------------------------------
        | Validation Checks
        |--------------------------------------------------------------------------
        */

        $checks = [];

        $checks['lifecycle_snapshot_available'] = [
            'passed' => true,
            'message' => 'Lifecycle snapshot is available.',
        ];

        $checks['lifecycle_state_operational'] = [
            'passed' =>
                ($lifecycle['analysis_completed'] ?? false) === true
                &&
                ($lifecycle['status'] ?? null)
                    === 'LIFECYCLE_STATE_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Lifecycle state intelligence is operational.',
        ];

        $checks['governance_progress_operational'] = [
            'passed' =>
                ($governance['analysis_completed'] ?? false) === true
                &&
                ($governance['status'] ?? null)
                    === 'GOVERNANCE_PROGRESS_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Governance progress intelligence is operational.',
        ];

        $checks['evidence_maturity_operational'] = [
            'passed' =>
                ($evidence['analysis_completed'] ?? false) === true
                &&
                ($evidence['status'] ?? null)
                    === 'EVIDENCE_MATURITY_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Evidence maturity and confidence intelligence is operational.',
        ];

        $checks['outcome_intelligence_operational'] = [
            'passed' =>
                ($outcome['analysis_completed'] ?? false) === true
                &&
                ($outcome['status'] ?? null)
                    === 'IMPROVEMENT_OUTCOME_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Improvement outcome intelligence is operational.',
        ];

        $checks['risk_exception_operational'] = [
            'passed' =>
                ($risk['analysis_completed'] ?? false) === true
                &&
                ($risk['status'] ?? null)
                    === 'RISK_EXCEPTION_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Risk and exception intelligence is operational.',
        ];

        $checks['executive_portfolio_operational'] = [
            'passed' =>
                ($executive['analysis_completed'] ?? false) === true
                &&
                ($executive['status'] ?? null)
                    === 'EXECUTIVE_PORTFOLIO_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Executive improvement portfolio intelligence is operational.',
        ];

        $checks['lifecycle_audit_complete'] = [
            'passed' =>
                ($audit['audit_available'] ?? false) === true
                &&
                ($audit['audit_status'] ?? null) === 'COMPLETE',

            'message' =>
                'Lifecycle audit summary is complete.',
        ];

        $checks['governance_integrity'] = [
            'passed' =>
                ($governance[
                    'governance_integrity'
                ]['failed_checks'] ?? 1) === 0,

            'message' =>
                'Governance integrity contains no failed controls.',
        ];

        $checks['critical_risk_absent'] = [
            'passed' =>
                ($risk[
                    'exception_summary'
                ]['critical_exceptions'] ?? 1) === 0,

            'message' =>
                'No critical portfolio risk exception is present.',
        ];

        $checks['high_risk_absent'] = [
            'passed' =>
                ($risk[
                    'exception_summary'
                ]['high_exceptions'] ?? 1) === 0,

            'message' =>
                'No high-severity portfolio risk exception is present.',
        ];

        $checks['automatic_change_isolation'] = [
            'passed' =>
                !(bool) $snapshot->automatic_change_allowed
                &&
                !(bool) $snapshot->automatic_deployment_allowed
                &&
                !(bool) $snapshot->automatic_rollback_allowed
                &&
                !(bool) $snapshot->automatic_clinical_action_allowed,

            'message' =>
                'Automatic modification, deployment, rollback, and clinical-action permissions remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $snapshot->human_review_required
                &&
                (bool) $snapshot->governance_validation_required,

            'message' =>
                'Human review and governance validation remain mandatory.',
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

        if (
            strtoupper(
                (string) (
                    $evidence['overall_evidence_maturity']
                    ?? ''
                )
            ) === 'EARLY'
        ) {
            $warnings[] =
                'Improvement lifecycle evidence maturity remains EARLY.';
        }

        if (
            strtoupper(
                (string) (
                    $evidence['overall_confidence']
                    ?? ''
                )
            ) === 'LIMITED'
        ) {
            $warnings[] =
                'Improvement lifecycle evidence confidence remains LIMITED.';
        }

        if (
            strtoupper(
                (string) (
                    $outcome['outcome_confidence']
                    ?? ''
                )
            ) === 'LIMITED'
        ) {
            $warnings[] =
                'Improvement outcome confidence remains LIMITED.';
        }

        if (
            ($governance[
                'candidate_review_summary'
            ]['pending_reviews'] ?? 0) > 0
        ) {
            $warnings[] =
                ($governance[
                    'candidate_review_summary'
                ]['pending_reviews'])
                . ' candidate governance review(s) remain pending.';
        }

        if (
            ($risk[
                'exception_summary'
            ]['advisory_exceptions'] ?? 0) > 0
        ) {
            $warnings[] =
                ($risk[
                    'exception_summary'
                ]['advisory_exceptions'])
                . ' portfolio advisory item(s) remain active.';
        }

        /*
        |--------------------------------------------------------------------------
        | Final State
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
            '58.1_lifecycle_snapshot_registry' => [
                'status' => 'OPERATIONAL',
            ],

            '58.2_snapshot_generation' => [
                'status' => 'OPERATIONAL',
                'snapshot_id' => $snapshot->id,
            ],

            '58.3_lifecycle_state_intelligence' => [
                'status' => 'OPERATIONAL',
                'lifecycle_stage' =>
                    $lifecycle[
                        'current_lifecycle_state'
                    ]['lifecycle_stage'] ?? null,
            ],

            '58.4_governance_progress_intelligence' => [
                'status' => 'OPERATIONAL',
                'governance_health' =>
                    $governance[
                        'governance_health'
                    ] ?? null,
            ],

            '58.5_evidence_maturity_intelligence' => [
                'status' => 'OPERATIONAL',
                'evidence_maturity' =>
                    $evidence[
                        'overall_evidence_maturity'
                    ] ?? null,
                'confidence' =>
                    $evidence[
                        'overall_confidence'
                    ] ?? null,
            ],

            '58.6_outcome_intelligence' => [
                'status' => 'OPERATIONAL',
                'overall_outcome' =>
                    $outcome['overall_outcome'] ?? null,
            ],

            '58.7_risk_exception_intelligence' => [
                'status' => 'OPERATIONAL',
                'portfolio_risk_level' =>
                    $risk['portfolio_risk_level'] ?? null,
            ],

            '58.8_executive_portfolio_intelligence' => [
                'status' => 'OPERATIONAL',
                'portfolio_status' =>
                    $executive['portfolio_status'] ?? null,
                'executive_health_score' =>
                    $executive[
                        'executive_health_score'
                    ] ?? null,
            ],

            '58.9_lifecycle_audit_summary' => [
                'status' =>
                    $audit['audit_status'] ?? null,
            ],

            '58.10_final_validation' => [
                'status' =>
                    $validationStatus,

                'step_58_ready_for_closure' =>
                    $readyForClosure,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            'The complete Step 58 AI improvement lifecycle intelligence architecture has been validated.',

            "Current lifecycle stage is "
            . (
                $lifecycle[
                    'current_lifecycle_state'
                ]['lifecycle_stage'] ?? 'UNKNOWN'
            )
            . '.',

            "Current lifecycle maturity is "
            . (
                $lifecycle[
                    'current_lifecycle_state'
                ]['lifecycle_maturity'] ?? 'UNKNOWN'
            )
            . '.',

            "Current governance health is "
            . (
                $governance['governance_health']
                ?? 'UNKNOWN'
            )
            . '.',

            "Current evidence maturity is "
            . (
                $evidence['overall_evidence_maturity']
                ?? 'UNKNOWN'
            )
            . " with "
            . (
                $evidence['overall_confidence']
                ?? 'UNKNOWN'
            )
            . ' confidence.',

            "Current governed improvement outcome is "
            . (
                $outcome['overall_outcome']
                ?? 'UNKNOWN'
            )
            . '.',

            "Current portfolio risk level is "
            . (
                $risk['portfolio_risk_level']
                ?? 'UNKNOWN'
            )
            . '.',

            'Lifecycle intelligence remains advisory and human governed.',
        ];

        return [
            'validation_status' =>
                $validationStatus,

            'step_58_ready_for_closure' =>
                $readyForClosure,

            'lifecycle_intelligence_mode' =>
                'HUMAN_GOVERNED_IMPROVEMENT_LIFECYCLE_INTELLIGENCE',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'completion_message' =>
                $readyForClosure
                    ? 'Step 58 AI Improvement Lifecycle Intelligence has passed final validation and is ready for closure.'
                    : 'Step 58 AI Improvement Lifecycle Intelligence has unresolved validation issues.',

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

            'lifecycle_context' => [
                'lifecycle_stage' =>
                    $lifecycle[
                        'current_lifecycle_state'
                    ]['lifecycle_stage'] ?? null,

                'lifecycle_maturity' =>
                    $lifecycle[
                        'current_lifecycle_state'
                    ]['lifecycle_maturity'] ?? null,

                'lifecycle_progress_score' =>
                    $lifecycle[
                        'current_lifecycle_state'
                    ]['lifecycle_progress_score'] ?? null,

                'capability_coverage_percentage' =>
                    $lifecycle[
                        'capability_summary'
                    ]['coverage_percentage'] ?? null,

                'governance_health' =>
                    $governance[
                        'governance_health'
                    ] ?? null,

                'evidence_maturity' =>
                    $evidence[
                        'overall_evidence_maturity'
                    ] ?? null,

                'evidence_confidence' =>
                    $evidence[
                        'overall_confidence'
                    ] ?? null,

                'overall_outcome' =>
                    $outcome[
                        'overall_outcome'
                    ] ?? null,

                'outcome_confidence' =>
                    $outcome[
                        'outcome_confidence'
                    ] ?? null,

                'portfolio_risk_level' =>
                    $risk[
                        'portfolio_risk_level'
                    ] ?? null,

                'portfolio_status' =>
                    $executive[
                        'portfolio_status'
                    ] ?? null,

                'strategic_readiness' =>
                    $executive[
                        'strategic_readiness'
                    ] ?? null,

                'executive_health_score' =>
                    $executive[
                        'executive_health_score'
                    ] ?? null,
            ],

            'architecture_summary' =>
                $architectureSummary,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'lifecycle_findings' =>
                $findings,

            'step_58_guardrails' => [
                'improvement_lifecycle_intelligence_enabled' =>
                    true,

                'lifecycle_snapshotting_enabled' =>
                    true,

                'governance_progress_intelligence_enabled' =>
                    true,

                'evidence_maturity_intelligence_enabled' =>
                    true,

                'outcome_intelligence_enabled' =>
                    true,

                'risk_exception_intelligence_enabled' =>
                    true,

                'executive_portfolio_intelligence_enabled' =>
                    true,

                'autonomous_self_modification_enabled' =>
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

                'automatic_execution' =>
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
                    'Step 58 consolidates AI improvement lifecycle intelligence for human governance and management oversight. Lifecycle intelligence does not enable autonomous modification, execution, deployment, rollback, or clinical decision replacement.',
            ],
        ];
    }
}