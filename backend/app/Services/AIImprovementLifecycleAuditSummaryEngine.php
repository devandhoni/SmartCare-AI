<?php

namespace App\Services;

use App\Models\AIImprovementLifecycleSnapshot;

class AIImprovementLifecycleAuditSummaryEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'audit_available' => false,
                'audit_status' => 'SNAPSHOT_NOT_FOUND',
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

        $sources = [
            'lifecycle' => $lifecycle,
            'governance' => $governance,
            'evidence' => $evidence,
            'outcome' => $outcome,
            'risk' => $risk,
            'executive' => $executive,
        ];

        foreach ($sources as $source => $result) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'audit_available' => false,
                    'audit_status' => 'SOURCE_INTELLIGENCE_INCOMPLETE',
                    'message' => "Lifecycle audit could not be completed because {$source} intelligence is unavailable.",
                    'snapshot_id' => $snapshot->id,
                    'failed_source' => $source,
                ];
            }
        }

        $checks = [];

        $checks['snapshot_available'] = [
            'passed' => true,
            'message' => 'Lifecycle snapshot is available.',
        ];

        $checks['lifecycle_state_available'] = [
            'passed' =>
                ($lifecycle['status'] ?? null) ===
                'LIFECYCLE_STATE_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Lifecycle state intelligence is available.',
        ];

        $checks['governance_progress_available'] = [
            'passed' =>
                ($governance['status'] ?? null) ===
                'GOVERNANCE_PROGRESS_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Governance progress intelligence is available.',
        ];

        $checks['evidence_maturity_available'] = [
            'passed' =>
                ($evidence['status'] ?? null) ===
                'EVIDENCE_MATURITY_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Evidence maturity intelligence is available.',
        ];

        $checks['outcome_intelligence_available'] = [
            'passed' =>
                ($outcome['status'] ?? null) ===
                'IMPROVEMENT_OUTCOME_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Improvement outcome intelligence is available.',
        ];

        $checks['risk_exception_intelligence_available'] = [
            'passed' =>
                ($risk['status'] ?? null) ===
                'RISK_EXCEPTION_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Risk and exception intelligence is available.',
        ];

        $checks['executive_portfolio_available'] = [
            'passed' =>
                ($executive['status'] ?? null) ===
                'EXECUTIVE_PORTFOLIO_INTELLIGENCE_AVAILABLE',

            'message' =>
                'Executive improvement portfolio intelligence is available.',
        ];

        $checks['governance_integrity'] = [
            'passed' =>
                ($governance['governance_integrity']['failed_checks'] ?? 1) === 0,

            'message' =>
                'Governance integrity checks contain no failures.',
        ];

        $checks['no_critical_risk'] = [
            'passed' =>
                ($risk['exception_summary']['critical_exceptions'] ?? 1) === 0,

            'message' =>
                'No critical improvement portfolio exception is detected.',
        ];

        $checks['no_high_risk'] = [
            'passed' =>
                ($risk['exception_summary']['high_exceptions'] ?? 1) === 0,

            'message' =>
                'No high-severity improvement portfolio exception is detected.',
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
                'Automatic change, deployment, rollback, and clinical action remain disabled.',
        ];

        $checks['human_governance_controls'] = [
            'passed' =>
                (bool) $snapshot->human_review_required
                &&
                (bool) $snapshot->governance_validation_required,

            'message' =>
                'Human review and governance validation remain mandatory.',
        ];

        $totalChecks = count($checks);

        $passedChecks = collect($checks)
            ->filter(
                fn ($check) =>
                    (bool) ($check['passed'] ?? false)
            )
            ->count();

        $failedChecks =
            $totalChecks - $passedChecks;

        $auditStatus =
            $failedChecks === 0
                ? 'COMPLETE'
                : 'COMPLETE_WITH_ISSUES';

        $findings = [
            "Lifecycle audit completed {$totalChecks} integrity check(s), with {$passedChecks} passed and {$failedChecks} failed.",
            "Current lifecycle stage is {$lifecycle['current_lifecycle_state']['lifecycle_stage']}.",
            "Current lifecycle maturity is {$lifecycle['current_lifecycle_state']['lifecycle_maturity']}.",
            "Current governance health is {$governance['governance_health']}.",
            "Current evidence maturity is {$evidence['overall_evidence_maturity']} with {$evidence['overall_confidence']} confidence.",
            "Current governed improvement outcome is {$outcome['overall_outcome']} with {$outcome['outcome_confidence']} confidence.",
            "Current portfolio risk level is {$risk['portfolio_risk_level']}.",
            "Current executive portfolio status is {$executive['portfolio_status']}.",
        ];

        $priorities = [];

        foreach (
            $executive['executive_priorities'] ?? []
            as $priority
        ) {
            $priorities[] = $priority;
        }

        return [
            'audit_available' =>
                true,

            'audit_status' =>
                $auditStatus,

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

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

            'lifecycle_summary' => [
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
            ],

            'governance_summary' => [
                'governance_stage' =>
                    $governance['governance_stage'] ?? null,

                'governance_health' =>
                    $governance['governance_health'] ?? null,

                'pending_reviews' =>
                    $governance[
                        'candidate_review_summary'
                    ]['pending_reviews'] ?? null,

                'integrity_failed_checks' =>
                    $governance[
                        'governance_integrity'
                    ]['failed_checks'] ?? null,
            ],

            'evidence_summary' => [
                'overall_evidence_maturity' =>
                    $evidence[
                        'overall_evidence_maturity'
                    ] ?? null,

                'overall_confidence' =>
                    $evidence[
                        'overall_confidence'
                    ] ?? null,

                'evidence_depth_score' =>
                    $evidence[
                        'evidence_depth_score'
                    ] ?? null,

                'domain_coverage_percentage' =>
                    $evidence[
                        'domain_coverage_summary'
                    ]['coverage_percentage'] ?? null,
            ],

            'outcome_summary' => [
                'overall_outcome' =>
                    $outcome['overall_outcome'] ?? null,

                'outcome_confidence' =>
                    $outcome['outcome_confidence'] ?? null,

                'outcome_score' =>
                    $outcome['outcome_score'] ?? null,
            ],

            'risk_summary' => [
                'portfolio_risk_level' =>
                    $risk['portfolio_risk_level'] ?? null,

                'exception_status' =>
                    $risk['exception_status'] ?? null,

                'critical_exceptions' =>
                    $risk[
                        'exception_summary'
                    ]['critical_exceptions'] ?? null,

                'high_exceptions' =>
                    $risk[
                        'exception_summary'
                    ]['high_exceptions'] ?? null,

                'advisory_exceptions' =>
                    $risk[
                        'exception_summary'
                    ]['advisory_exceptions'] ?? null,
            ],

            'executive_summary' => [
                'portfolio_status' =>
                    $executive['portfolio_status'] ?? null,

                'strategic_readiness' =>
                    $executive['strategic_readiness'] ?? null,

                'executive_health_score' =>
                    $executive['executive_health_score'] ?? null,
            ],

            'audit_findings' =>
                $findings,

            'management_priorities' =>
                array_values(array_unique($priorities)),

            'audit_guardrails' => [
                'audit_is_ai_authority' =>
                    false,

                'audit_is_implementation_approval' =>
                    false,

                'audit_is_execution_authorization' =>
                    false,

                'audit_is_deployment_authorization' =>
                    false,

                'automatic_change_allowed' =>
                    false,

                'automatic_execution_allowed' =>
                    false,

                'automatic_deployment_allowed' =>
                    false,

                'automatic_rollback_allowed' =>
                    false,

                'automatic_clinical_action_allowed' =>
                    false,

                'human_review_required' =>
                    true,

                'governance_validation_required' =>
                    true,

                'message' =>
                    'Lifecycle audit consolidates Step 58 intelligence for governance and management review only. It does not authorize AI modification, testing, implementation, execution, deployment, rollback, or clinical action.',
            ],
        ];
    }
}