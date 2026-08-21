<?php

namespace App\Services;

use App\Models\AIImprovementTest;
use Illuminate\Support\Facades\DB;

class AIImprovementControlledTestValidationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 55.6
    | Controlled Test Human Validation & Governance Decision
    |--------------------------------------------------------------------------
    |
    | Supported decisions:
    |
    | - VALIDATE
    | - REJECT
    | - REQUIRE_MORE_EVIDENCE
    |
    | IMPORTANT:
    |
    | Validation does not authorize production deployment.
    |
    */

    public function validate(
        int $testId,
        string $decision,
        ?int $validatedBy = null,
        ?string $notes = null
    ): array {
        $decision = strtoupper(trim($decision));

        $allowedDecisions = [
            'VALIDATE',
            'REJECT',
            'REQUIRE_MORE_EVIDENCE',
        ];

        if (!in_array($decision, $allowedDecisions, true)) {
            return [
                'validation_applied' => false,
                'status' => 'INVALID_VALIDATION_DECISION',
                'message' => 'Unsupported controlled test validation decision.',
                'allowed_decisions' => $allowedDecisions,
            ];
        }

        $test = AIImprovementTest::find($testId);

        if (!$test) {
            return [
                'validation_applied' => false,
                'status' => 'TEST_NOT_FOUND',
                'message' => 'AI improvement controlled test was not found.',
                'test_id' => $testId,
            ];
        }

        $currentStatus = strtoupper((string) ($test->test_status ?? ''));

        if ($currentStatus !== 'AWAITING_VALIDATION') {
            return [
                'validation_applied' => false,
                'status' => 'INVALID_TEST_STATE',
                'message' => 'Only controlled tests awaiting validation may receive a validation decision.',
                'test_id' => $test->id,
                'current_test_status' => $currentStatus,
            ];
        }

        $criticalIssues = [];

        if ((bool) $test->production_change_allowed) {
            $criticalIssues[] = 'Production change permission is enabled.';
        }

        if ((bool) $test->automatic_deployment_allowed) {
            $criticalIssues[] = 'Automatic deployment permission is enabled.';
        }

        if (!(bool) $test->human_validation_required) {
            $criticalIssues[] = 'Human validation requirement is disabled.';
        }

        if (!(bool) $test->governance_validation_required) {
            $criticalIssues[] = 'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'validation_applied' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Controlled test validation is blocked because one or more governance controls are invalid.',
                'test_id' => $test->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        $results = $test->test_results ?? [];

        if (!is_array($results) || empty($results)) {
            return [
                'validation_applied' => false,
                'status' => 'MISSING_TEST_RESULTS',
                'message' => 'Controlled test results are not available for validation.',
                'test_id' => $test->id,
            ];
        }

        $safetyPassed = (bool) ($results['safety_passed'] ?? false);
        $outcomeStatus = strtoupper((string) ($results['outcome_status'] ?? 'UNKNOWN'));

        if ($decision === 'VALIDATE' && !$safetyPassed) {
            return [
                'validation_applied' => false,
                'status' => 'SAFETY_VALIDATION_FAILED',
                'message' => 'A controlled test that failed safety validation cannot be validated as successful.',
                'test_id' => $test->id,
                'outcome_status' => $outcomeStatus,
                'safety_passed' => false,
            ];
        }

        return DB::transaction(function () use (
            $test,
            $decision,
            $validatedBy,
            $notes,
            $results
        ) {
            $testStatus = 'AWAITING_VALIDATION';

            switch ($decision) {
                case 'VALIDATE':
                    $testStatus = 'VALIDATED';
                    break;

                case 'REJECT':
                    $testStatus = 'REJECTED';
                    break;

                case 'REQUIRE_MORE_EVIDENCE':
                    $testStatus = 'MORE_EVIDENCE_REQUIRED';
                    break;
            }

            $updatedResults = $results;

            $updatedResults['human_validation'] = [
                'decision' => $decision,
                'notes' => $notes,
                'validated_by' => $validatedBy,
                'validated_at' => now()->toIso8601String(),
                'production_change_authorized' => false,
                'automatic_deployment_authorized' => false,
                'implementation_authorized' => false,
            ];

            $test->update([
                'test_status' => $testStatus,
                'test_results' => $updatedResults,
                'validated_by' => $validatedBy,
                'validated_at' => now(),
                'production_change_allowed' => false,
                'automatic_deployment_allowed' => false,
                'human_validation_required' => true,
                'governance_validation_required' => true,
            ]);

            $test->refresh();

            return [
                'validation_applied' => true,
                'status' => 'VALIDATION_RECORDED',
                'message' => 'Controlled AI improvement test validation decision recorded successfully.',

                'test' => [
                    'test_id' => $test->id,
                    'candidate_code' => $test->candidate_code,
                    'test_status' => $test->test_status,
                    'validation_decision' => $decision,
                    'validated_by' => $test->validated_by,
                    'validated_at' => $test->validated_at,
                    'test_results' => $test->test_results,
                    'production_change_allowed' => (bool) $test->production_change_allowed,
                    'automatic_deployment_allowed' => (bool) $test->automatic_deployment_allowed,
                    'human_validation_required' => (bool) $test->human_validation_required,
                    'governance_validation_required' => (bool) $test->governance_validation_required,
                ],

                'validation_guardrails' => [
                    'validation_is_production_approval' => false,
                    'validation_is_implementation_approval' => false,
                    'production_change_allowed' => false,
                    'automatic_deployment_allowed' => false,
                    'automatic_model_change' => false,
                    'automatic_threshold_change' => false,
                    'automatic_confidence_change' => false,
                    'automatic_recommendation_change' => false,
                    'automatic_workflow_change' => false,
                    'automatic_clinical_rule_change' => false,
                    'automatic_clinical_action' => false,
                    'separate_implementation_governance_required' => true,
                    'human_review_required' => true,
                    'message' => 'Human validation confirms the controlled test result only. It does not authorize production implementation, deployment, or automatic AI system modification.',
                ],
            ];
        });
    }
}