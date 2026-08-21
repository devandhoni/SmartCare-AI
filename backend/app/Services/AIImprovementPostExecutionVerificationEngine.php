<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use Illuminate\Support\Facades\DB;

class AIImprovementPostExecutionVerificationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.7
    | Post-Execution Human Verification
    |--------------------------------------------------------------------------
    |
    | Supported decisions:
    |
    | - VERIFY
    | - REQUIRE_ROLLBACK
    | - REJECT_RESULT
    |
    | Verification confirms the controlled execution evidence only.
    | It does not enable production deployment.
    |
    */

    public function verify(
        int $executionId,
        string $decision,
        ?int $verifiedBy = null,
        ?string $notes = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Normalize Decision
        |--------------------------------------------------------------------------
        */

        $decision = strtoupper(trim($decision));

        $allowedDecisions = [
            'VERIFY',
            'REQUIRE_ROLLBACK',
            'REJECT_RESULT',
        ];

        if (!in_array($decision, $allowedDecisions, true)) {
            return [
                'verification_applied' => false,
                'status' => 'INVALID_VERIFICATION_DECISION',
                'message' => 'Unsupported post-execution verification decision.',
                'allowed_decisions' => $allowedDecisions,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Execution
        |--------------------------------------------------------------------------
        */

        $execution = AIImprovementExecution::find($executionId);

        if (!$execution) {
            return [
                'verification_applied' => false,
                'status' => 'EXECUTION_NOT_FOUND',
                'message' => 'AI improvement execution record was not found.',
                'execution_id' => $executionId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Required State
        |--------------------------------------------------------------------------
        */

        $executionStatus = strtoupper(
            (string) ($execution->execution_status ?? '')
        );

        $executionStage = strtoupper(
            (string) ($execution->execution_stage ?? '')
        );

        if (
            $executionStatus !== 'AWAITING_VERIFICATION'
            ||
            $executionStage !== 'CONTROLLED_EXECUTION_COMPLETED'
        ) {
            return [
                'verification_applied' => false,
                'status' => 'INVALID_EXECUTION_STATE',
                'message' => 'Only a completed controlled execution awaiting verification may receive a post-execution decision.',
                'execution_id' => $execution->id,
                'execution_status' => $executionStatus,
                'execution_stage' => $executionStage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Load Execution Results
        |--------------------------------------------------------------------------
        */

        $results = $execution->execution_results ?? [];

        if (!is_array($results) || empty($results)) {
            return [
                'verification_applied' => false,
                'status' => 'EXECUTION_RESULTS_MISSING',
                'message' => 'Controlled execution results are required before verification.',
                'execution_id' => $execution->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Safety Isolation
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $execution->production_execution_allowed) {
            $criticalIssues[] =
                'Production execution permission is enabled.';
        }

        if ((bool) $execution->automatic_execution_allowed) {
            $criticalIssues[] =
                'Automatic execution permission is enabled.';
        }

        if ((bool) $execution->automatic_deployment_allowed) {
            $criticalIssues[] =
                'Automatic deployment permission is enabled.';
        }

        if ((bool) $execution->automatic_rollback_allowed) {
            $criticalIssues[] =
                'Automatic rollback permission is enabled.';
        }

        if (!(bool) $execution->post_execution_validation_required) {
            $criticalIssues[] =
                'Post-execution validation requirement is disabled.';
        }

        if (!(bool) $execution->rollback_plan_required) {
            $criticalIssues[] =
                'Rollback planning requirement is disabled.';
        }

        if (!(bool) $execution->governance_validation_required) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'verification_applied' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Post-execution verification is blocked because governance controls are invalid.',
                'execution_id' => $execution->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Result Context
        |--------------------------------------------------------------------------
        */

        $safetyPassed = (bool) (
            $results['safety_passed']
            ?? false
        );

        $direction = strtoupper(
            (string) (
                $results['direction']
                ?? 'UNKNOWN'
            )
        );

        $outcomeStatus = strtoupper(
            (string) (
                $results['outcome_status']
                ?? 'UNKNOWN'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 7. VERIFY Eligibility
        |--------------------------------------------------------------------------
        */

        if (
            $decision === 'VERIFY'
            &&
            (
                !$safetyPassed
                ||
                $direction === 'DETERIORATED'
                ||
                in_array(
                    $outcomeStatus,
                    [
                        'NEGATIVE_SIGNAL',
                        'SAFETY_REVIEW_REQUIRED',
                    ],
                    true
                )
            )
        ) {
            return [
                'verification_applied' => false,
                'status' => 'RESULT_NOT_ELIGIBLE_FOR_VERIFICATION',
                'message' => 'Controlled execution results cannot be positively verified because the recorded evidence contains a negative or unsafe signal.',
                'execution_id' => $execution->id,
                'direction' => $direction,
                'outcome_status' => $outcomeStatus,
                'safety_passed' => $safetyPassed,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Determine New State
        |--------------------------------------------------------------------------
        */

        switch ($decision) {
            case 'VERIFY':
                $newStatus =
                    'VERIFIED';

                $newStage =
                    'POST_EXECUTION_VERIFIED';

                break;

            case 'REQUIRE_ROLLBACK':
                $newStatus =
                    'ROLLBACK_REQUIRED';

                $newStage =
                    'ROLLBACK_GOVERNANCE_REQUIRED';

                break;

            case 'REJECT_RESULT':
                $newStatus =
                    'VERIFICATION_REJECTED';

                $newStage =
                    'POST_EXECUTION_REJECTED';

                break;

            default:
                $newStatus =
                    'AWAITING_VERIFICATION';

                $newStage =
                    'CONTROLLED_EXECUTION_COMPLETED';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Verification Payload
        |--------------------------------------------------------------------------
        */

        $verificationResults = [
            'verification_version' =>
                '56.7',

            'decision' =>
                $decision,

            'verified_by' =>
                $verifiedBy,

            'verification_notes' =>
                $notes,

            'verified_at' =>
                now()->toIso8601String(),

            'execution_direction' =>
                $direction,

            'execution_outcome_status' =>
                $outcomeStatus,

            'execution_safety_passed' =>
                $safetyPassed,

            'controlled_execution_verified' =>
                $decision === 'VERIFY',

            'rollback_required' =>
                $decision === 'REQUIRE_ROLLBACK',

            /*
            |--------------------------------------------------------------------------
            | No production authority
            |--------------------------------------------------------------------------
            */

            'production_change_authorized' =>
                false,

            'automatic_execution_authorized' =>
                false,

            'automatic_deployment_authorized' =>
                false,

            'automatic_rollback_authorized' =>
                false,
        ];

        /*
        |--------------------------------------------------------------------------
        | 10. Persist Verification
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $execution,
                $decision,
                $newStatus,
                $newStage,
                $verificationResults
            ) {
                $execution->update([
                    'execution_status' =>
                        $newStatus,

                    'execution_stage' =>
                        $newStage,

                    'verification_results' =>
                        $verificationResults,

                    'verified_at' =>
                        now(),

                    /*
                    |--------------------------------------------------------------------------
                    | Production isolation remains
                    |--------------------------------------------------------------------------
                    */

                    'production_execution_allowed' =>
                        false,

                    'automatic_execution_allowed' =>
                        false,

                    'automatic_deployment_allowed' =>
                        false,

                    'automatic_rollback_allowed' =>
                        false,
                ]);

                $execution->refresh();

                return [
                    'verification_applied' =>
                        true,

                    'status' =>
                        'POST_EXECUTION_VERIFICATION_RECORDED',

                    'message' =>
                        'Post-execution human verification decision recorded successfully.',

                    'execution' => [
                        'execution_id' =>
                            $execution->id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'verification_decision' =>
                            $decision,

                        'verified_at' =>
                            $execution->verified_at,

                        'verification_results' =>
                            $execution->verification_results,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,
                    ],

                    'verification_guardrails' => [
                        'verification_is_production_deployment' =>
                            false,

                        'verification_is_automatic_change_authorization' =>
                            false,

                        'controlled_execution_verified' =>
                            $decision === 'VERIFY',

                        'rollback_required' =>
                            $decision === 'REQUIRE_ROLLBACK',

                        'production_execution_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Post-execution verification confirms or rejects controlled execution evidence only. It does not authorize production deployment, automatic execution, or autonomous rollback.',
                    ],
                ];
            }
        );
    }
}