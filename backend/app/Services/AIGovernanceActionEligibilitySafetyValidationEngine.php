<?php

namespace App\Services;

use App\Models\AIGovernanceAction;
use App\Models\AIImprovementLifecycleSnapshot;
use Illuminate\Support\Facades\DB;

class AIGovernanceActionEligibilitySafetyValidationEngine
{
    public function analyze(?int $snapshotId = null): array
    {
        $snapshot = $snapshotId !== null
            ? AIImprovementLifecycleSnapshot::find($snapshotId)
            : AIImprovementLifecycleSnapshot::latest('id')->first();

        if (!$snapshot) {
            return [
                'validation_completed' => false,
                'status' => 'SNAPSHOT_NOT_FOUND',
                'message' => 'AI improvement lifecycle snapshot was not found.',
                'snapshot_id' => $snapshotId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Snapshot Guardrail
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $snapshot->automatic_change_allowed ||
            (bool) $snapshot->automatic_deployment_allowed ||
            (bool) $snapshot->automatic_rollback_allowed ||
            (bool) $snapshot->automatic_clinical_action_allowed
        ) {
            return [
                'validation_completed' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Governance action eligibility validation is blocked because an automatic-change permission is enabled.',
                'snapshot_id' => $snapshot->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Source Intelligence
        |--------------------------------------------------------------------------
        */

        $priority = app(
            AIGovernanceActionPriorityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $risk = app(
            AIImprovementRiskExceptionIntelligenceEngine::class
        )->analyze($snapshot->id);

        $evidence = app(
            AIImprovementEvidenceMaturityIntelligenceEngine::class
        )->analyze($snapshot->id);

        $governance = app(
            AIImprovementGovernanceProgressIntelligenceEngine::class
        )->analyze($snapshot->id);

        foreach (
            [
                'priority' => $priority,
                'risk' => $risk,
                'evidence' => $evidence,
                'governance' => $governance,
            ] as $source => $result
        ) {
            if (!($result['analysis_completed'] ?? false)) {
                return [
                    'validation_completed' => false,
                    'status' => 'SOURCE_INTELLIGENCE_INCOMPLETE',
                    'message' => "Governance action eligibility validation could not continue because {$source} intelligence is unavailable.",
                    'snapshot_id' => $snapshot->id,
                    'failed_source' => $source,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Load Actions
        |--------------------------------------------------------------------------
        */

        $actions = AIGovernanceAction::query()
            ->where('lifecycle_snapshot_id', $snapshot->id)
            ->whereIn('action_status', [
                'OPEN',
                'PENDING_REVIEW',
                'UNDER_REVIEW',
                'APPROVED',
                'DEFERRED',
            ])
            ->orderByDesc('priority_score')
            ->orderBy('id')
            ->get();

        if ($actions->isEmpty()) {
            return [
                'validation_completed' => true,
                'status' => 'NO_GOVERNANCE_ACTIONS_AVAILABLE',
                'snapshot_id' => $snapshot->id,
                'eligibility_summary' => [
                    'total_actions' => 0,
                    'eligible_for_human_review' => 0,
                    'observe_only_actions' => 0,
                    'blocked_actions' => 0,
                ],
                'validated_actions' => [],
                'eligibility_guardrails' => $this->eligibilityGuardrails(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Shared Context
        |--------------------------------------------------------------------------
        */

        $portfolioRiskLevel = strtoupper(
            (string) (
                $risk['portfolio_risk_level']
                ?? 'UNKNOWN'
            )
        );

        $criticalExceptions = (int) (
            $risk['exception_summary']['critical_exceptions']
            ?? 0
        );

        $highExceptions = (int) (
            $risk['exception_summary']['high_exceptions']
            ?? 0
        );

        $evidenceMaturity = strtoupper(
            (string) (
                $evidence['overall_evidence_maturity']
                ?? 'UNKNOWN'
            )
        );

        $evidenceConfidence = strtoupper(
            (string) (
                $evidence['overall_confidence']
                ?? 'UNKNOWN'
            )
        );

        $pendingReviews = (int) (
            $governance['candidate_review_summary']['pending_reviews']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Actions
        |--------------------------------------------------------------------------
        */

        $validated = [];

        DB::transaction(function () use (
            $actions,
            $portfolioRiskLevel,
            $criticalExceptions,
            $highExceptions,
            $evidenceMaturity,
            $evidenceConfidence,
            $pendingReviews,
            &$validated
        ) {
            foreach ($actions as $action) {
                $checks = [];

                /*
                |--------------------------------------------------------------------------
                | Core Safety Checks
                |--------------------------------------------------------------------------
                */

                $checks['human_review_required'] = [
                    'passed' =>
                        (bool) $action->human_review_required,

                    'message' =>
                        'Governance actions must require human review.',
                ];

                $checks['governance_validation_required'] = [
                    'passed' =>
                        (bool) $action->governance_validation_required,

                    'message' =>
                        'Governance actions must require governance validation.',
                ];

                $checks['automatic_execution_disabled'] = [
                    'passed' =>
                        !(bool) $action->automatic_execution_allowed,

                    'message' =>
                        'Automatic execution must remain disabled.',
                ];

                $checks['automatic_change_disabled'] = [
                    'passed' =>
                        !(bool) $action->automatic_change_allowed,

                    'message' =>
                        'Automatic AI system modification must remain disabled.',
                ];

                $checks['automatic_deployment_disabled'] = [
                    'passed' =>
                        !(bool) $action->automatic_deployment_allowed,

                    'message' =>
                        'Automatic deployment must remain disabled.',
                ];

                $checks['automatic_rollback_disabled'] = [
                    'passed' =>
                        !(bool) $action->automatic_rollback_allowed,

                    'message' =>
                        'Automatic rollback must remain disabled.',
                ];

                $checks['automatic_clinical_action_disabled'] = [
                    'passed' =>
                        !(bool) $action->automatic_clinical_action_allowed,

                    'message' =>
                        'Automatic clinical action must remain disabled.',
                ];

                $checks['priority_available'] = [
                    'passed' =>
                        in_array(
                            strtoupper((string) $action->priority_level),
                            [
                                'ADVISORY',
                                'MODERATE',
                                'HIGH',
                                'CRITICAL',
                            ],
                            true
                        ),

                    'message' =>
                        'Governance action must have a recognized priority classification.',
                ];

                $checks['priority_score_valid'] = [
                    'passed' =>
                        (int) $action->priority_score >= 0
                        &&
                        (int) $action->priority_score <= 100,

                    'message' =>
                        'Priority score must remain between 0 and 100.',
                ];

                $checks['source_context_available'] = [
                    'passed' =>
                        !empty($action->source_type)
                        &&
                        $action->source_context !== null,

                    'message' =>
                        'Governance action must retain source evidence context.',
                ];

                /*
                |--------------------------------------------------------------------------
                | Action-Specific Eligibility
                |--------------------------------------------------------------------------
                */

                $actionSpecificEligible = true;
                $eligibilityReasons = [];

                switch ($action->action_code) {
                    case 'REVIEW_PENDING_IMPROVEMENT_CANDIDATES':
                        if ($pendingReviews <= 0) {
                            $actionSpecificEligible = false;

                            $eligibilityReasons[] =
                                'No pending improvement governance reviews currently remain.';
                        } else {
                            $eligibilityReasons[] =
                                "{$pendingReviews} pending candidate review(s) remain available for human governance review.";
                        }

                        break;

                    case 'REASSESS_DEFERRED_IMPROVEMENT_CANDIDATES':
                        if (
                            in_array(
                                $evidenceMaturity,
                                ['EARLY', 'FOUNDATIONAL', 'INSUFFICIENT'],
                                true
                            )
                        ) {
                            $eligibilityReasons[] =
                                'Deferred candidate reassessment remains observation-oriented because evidence maturity is still limited.';
                        }

                        break;

                    case 'COLLECT_ADDITIONAL_LEARNING_EVIDENCE':
                        if (
                            in_array(
                                $evidenceMaturity,
                                ['EARLY', 'FOUNDATIONAL', 'INSUFFICIENT'],
                                true
                            )
                            ||
                            in_array(
                                $evidenceConfidence,
                                ['LIMITED', 'VERY LIMITED'],
                                true
                            )
                        ) {
                            $eligibilityReasons[] =
                                'Additional evidence collection is appropriate because current evidence maturity or confidence remains limited.';
                        } else {
                            $eligibilityReasons[] =
                                'Evidence collection may continue as a governance quality activity.';
                        }

                        break;

                    case 'STRENGTHEN_IMPROVEMENT_EVIDENCE_CONFIDENCE':
                        if (
                            in_array(
                                $evidenceConfidence,
                                ['LIMITED', 'VERY LIMITED'],
                                true
                            )
                        ) {
                            $eligibilityReasons[] =
                                'Evidence-strengthening activity remains appropriate because confidence is limited.';
                        } else {
                            $actionSpecificEligible = false;

                            $eligibilityReasons[] =
                                'Evidence confidence is no longer limited enough to justify this action.';
                        }

                        break;

                    case 'CONTINUE_LONGITUDINAL_MONITORING':
                        $eligibilityReasons[] =
                            'Longitudinal monitoring is observational and may continue under human governance.';

                        break;

                    case 'REVIEW_IMPROVEMENT_PORTFOLIO_ADVISORIES':
                        $eligibilityReasons[] =
                            'Portfolio advisories may be reviewed by human governance without modifying AI behavior.';

                        break;

                    case 'ESCALATE_CRITICAL_IMPROVEMENT_EXCEPTIONS':
                        if ($criticalExceptions <= 0) {
                            $actionSpecificEligible = false;

                            $eligibilityReasons[] =
                                'No critical exception is currently present.';
                        } else {
                            $eligibilityReasons[] =
                                "{$criticalExceptions} critical exception(s) require human escalation.";
                        }

                        break;

                    case 'REVIEW_HIGH_SEVERITY_IMPROVEMENT_RISKS':
                        if ($highExceptions <= 0) {
                            $actionSpecificEligible = false;

                            $eligibilityReasons[] =
                                'No high-severity exception is currently present.';
                        } else {
                            $eligibilityReasons[] =
                                "{$highExceptions} high-severity exception(s) require prioritized human review.";
                        }

                        break;

                    default:
                        $eligibilityReasons[] =
                            'No additional action-specific restriction is configured.';
                        break;
                }

                $checks['action_specific_eligibility'] = [
                    'passed' =>
                        $actionSpecificEligible,

                    'message' =>
                        implode(' ', $eligibilityReasons),
                ];

                /*
                |--------------------------------------------------------------------------
                | Count Checks
                |--------------------------------------------------------------------------
                */

                $totalChecks =
                    count($checks);

                $passedChecks =
                    collect($checks)
                        ->filter(
                            fn ($check) =>
                                (bool) ($check['passed'] ?? false)
                        )
                        ->count();

                $failedChecks =
                    $totalChecks - $passedChecks;

                /*
                |--------------------------------------------------------------------------
                | Determine Eligibility
                |--------------------------------------------------------------------------
                */

                $hasSafetyFailure =
                    !($checks['human_review_required']['passed'] ?? false)
                    ||
                    !($checks['governance_validation_required']['passed'] ?? false)
                    ||
                    !($checks['automatic_execution_disabled']['passed'] ?? false)
                    ||
                    !($checks['automatic_change_disabled']['passed'] ?? false)
                    ||
                    !($checks['automatic_deployment_disabled']['passed'] ?? false)
                    ||
                    !($checks['automatic_rollback_disabled']['passed'] ?? false)
                    ||
                    !($checks['automatic_clinical_action_disabled']['passed'] ?? false);

                if ($hasSafetyFailure) {
                    $eligibilityStatus =
                        'BLOCKED';

                    $actionStatus =
                        'OPEN';
                } elseif (!$actionSpecificEligible) {
                    $eligibilityStatus =
                        'OBSERVE_ONLY';

                    $actionStatus =
                        'OPEN';
                } else {
                    $eligibilityStatus =
                        'ELIGIBLE_FOR_HUMAN_REVIEW';

                    $actionStatus =
                        'PENDING_REVIEW';
                }

                /*
                |--------------------------------------------------------------------------
                | Eligibility Context
                |--------------------------------------------------------------------------
                */

                $eligibilityContext = [
                    'validation_version' =>
                        '59.4',

                    'eligibility_status' =>
                        $eligibilityStatus,

                    'portfolio_risk_level' =>
                        $portfolioRiskLevel,

                    'evidence_maturity' =>
                        $evidenceMaturity,

                    'evidence_confidence' =>
                        $evidenceConfidence,

                    'total_checks' =>
                        $totalChecks,

                    'passed_checks' =>
                        $passedChecks,

                    'failed_checks' =>
                        $failedChecks,

                    'checks' =>
                        $checks,

                    'automatic_execution_authorized' =>
                        false,

                    'automatic_change_authorized' =>
                        false,

                    'automatic_deployment_authorized' =>
                        false,

                    'automatic_rollback_authorized' =>
                        false,

                    'automatic_clinical_action_authorized' =>
                        false,

                    'human_review_required' =>
                        true,

                    'validated_at' =>
                        now()->toIso8601String(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Persist
                |--------------------------------------------------------------------------
                */

                $action->update([
                    'eligibility_status' =>
                        $eligibilityStatus,

                    'action_status' =>
                        $actionStatus,

                    'eligibility_context' =>
                        $eligibilityContext,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_change_allowed' =>
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
                ]);

                $validated[] = [
                    'action_id' =>
                        $action->id,

                    'action_code' =>
                        $action->action_code,

                    'action_category' =>
                        $action->action_category,

                    'priority_level' =>
                        $action->priority_level,

                    'priority_score' =>
                        (int) $action->priority_score,

                    'eligibility_status' =>
                        $eligibilityStatus,

                    'action_status' =>
                        $actionStatus,

                    'total_checks' =>
                        $totalChecks,

                    'passed_checks' =>
                        $passedChecks,

                    'failed_checks' =>
                        $failedChecks,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_change_allowed' =>
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
                ];
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $eligible =
            collect($validated)
                ->where(
                    'eligibility_status',
                    'ELIGIBLE_FOR_HUMAN_REVIEW'
                )
                ->count();

        $observeOnly =
            collect($validated)
                ->where(
                    'eligibility_status',
                    'OBSERVE_ONLY'
                )
                ->count();

        $blocked =
            collect($validated)
                ->where(
                    'eligibility_status',
                    'BLOCKED'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Findings
        |--------------------------------------------------------------------------
        */

        $findings = [
            count($validated)
                . ' governance action(s) completed eligibility and safety validation.',

            "{$eligible} action(s) are eligible for human governance review.",

            "{$observeOnly} action(s) remain observation-only.",

            "{$blocked} action(s) are blocked by current safety or governance controls.",

            'No validated governance action receives automatic execution, deployment, rollback, AI-change, or clinical-action authority.',
        ];

        if ($blocked === 0) {
            $findings[] =
                'No governance action is currently blocked by an autonomous-permission safety violation.';
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'validation_completed' =>
                true,

            'status' =>
                'GOVERNANCE_ACTION_ELIGIBILITY_VALIDATED',

            'snapshot_id' =>
                $snapshot->id,

            'snapshot_scope' =>
                $snapshot->snapshot_scope,

            'resident_id' =>
                $snapshot->resident_id,

            'eligibility_summary' => [
                'total_actions' =>
                    count($validated),

                'eligible_for_human_review' =>
                    $eligible,

                'observe_only_actions' =>
                    $observeOnly,

                'blocked_actions' =>
                    $blocked,
            ],

            'validated_actions' =>
                $validated,

            'validation_context' => [
                'portfolio_risk_level' =>
                    $portfolioRiskLevel,

                'critical_exceptions' =>
                    $criticalExceptions,

                'high_exceptions' =>
                    $highExceptions,

                'evidence_maturity' =>
                    $evidenceMaturity,

                'evidence_confidence' =>
                    $evidenceConfidence,

                'pending_governance_reviews' =>
                    $pendingReviews,
            ],

            'eligibility_findings' =>
                $findings,

            'eligibility_guardrails' =>
                $this->eligibilityGuardrails(),
        ];
    }

    private function eligibilityGuardrails(): array
    {
        return [
            'eligibility_validation_is_approval' =>
                false,

            'eligibility_validation_is_execution' =>
                false,

            'eligible_for_human_review_is_execution_authorization' =>
                false,

            'automatic_execution_allowed' =>
                false,

            'automatic_change_allowed' =>
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
                'Governance action eligibility validation determines whether a work item may proceed to human review only. Eligibility does not authorize AI modification, execution, deployment, rollback, or clinical action.',
        ];
    }
}