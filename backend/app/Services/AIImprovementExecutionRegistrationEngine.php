<?php

namespace App\Services;

use App\Models\AIImprovementExecution;
use App\Models\AIImprovementImplementationReview;
use App\Models\AIImprovementReview;
use App\Models\AIImprovementTest;
use Illuminate\Support\Facades\DB;

class AIImprovementExecutionRegistrationEngine
{
    /*
    |--------------------------------------------------------------------------
    | Step 56.2
    | Approved Change Specification & Execution Registration Engine
    |--------------------------------------------------------------------------
    |
    | This engine converts a human-approved implementation governance record
    | into a structured execution preparation record.
    |
    | Registration does NOT authorize execution.
    |
    */

    public function register(
        int $implementationReviewId,
        string $changeType,
        string $changeSummary,
        array $baselineConfiguration = [],
        array $proposedConfiguration = [],
        array $executionScope = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Implementation Governance Review
        |--------------------------------------------------------------------------
        */

        $implementationReview =
            AIImprovementImplementationReview::find(
                $implementationReviewId
            );

        if (!$implementationReview) {
            return [
                'registered' => false,
                'status' => 'IMPLEMENTATION_REVIEW_NOT_FOUND',
                'message' => 'AI improvement implementation governance review was not found.',
                'implementation_review_id' => $implementationReviewId,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Load Source Review
        |--------------------------------------------------------------------------
        */

        $improvementReview =
            AIImprovementReview::find(
                $implementationReview->improvement_review_id
            );

        if (!$improvementReview) {
            return [
                'registered' => false,
                'status' => 'SOURCE_REVIEW_NOT_FOUND',
                'message' => 'Source AI improvement review was not found.',
                'implementation_review_id' => $implementationReview->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Load Validated Controlled Test
        |--------------------------------------------------------------------------
        */

        $improvementTest =
            AIImprovementTest::find(
                $implementationReview->improvement_test_id
            );

        if (!$improvementTest) {
            return [
                'registered' => false,
                'status' => 'CONTROLLED_TEST_NOT_FOUND',
                'message' => 'Associated AI improvement controlled test was not found.',
                'implementation_review_id' => $implementationReview->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Governance Approval Validation
        |--------------------------------------------------------------------------
        */

        $implementationReviewStatus =
            strtoupper(
                (string) (
                    $implementationReview->review_status
                    ?? ''
                )
            );

        $implementationDecision =
            strtoupper(
                (string) (
                    $implementationReview->review_decision
                    ?? ''
                )
            );

        $approvedForImplementation =
            (bool) (
                $implementationReview->approved_for_implementation
                ?? false
            );

        if (
            $implementationReviewStatus !== 'APPROVED'
            ||
            $implementationDecision !== 'APPROVE_FOR_IMPLEMENTATION'
            ||
            !$approvedForImplementation
        ) {
            return [
                'registered' => false,
                'status' => 'IMPLEMENTATION_NOT_APPROVED',
                'message' => 'Execution registration requires separate human implementation governance approval.',
                'implementation_review_id' => $implementationReview->id,
                'review_status' => $implementationReviewStatus,
                'review_decision' => $implementationDecision,
                'approved_for_implementation' => $approvedForImplementation,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Controlled Test Validation
        |--------------------------------------------------------------------------
        */

        $testStatus =
            strtoupper(
                (string) (
                    $improvementTest->test_status
                    ?? ''
                )
            );

        if ($testStatus !== 'VALIDATED') {
            return [
                'registered' => false,
                'status' => 'CONTROLLED_TEST_NOT_VALIDATED',
                'message' => 'Execution registration requires a validated controlled test.',
                'test_id' => $improvementTest->id,
                'test_status' => $testStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Production Safety Validation
        |--------------------------------------------------------------------------
        */

        $criticalIssues = [];

        if ((bool) $implementationReview->production_change_allowed) {
            $criticalIssues[] =
                'Implementation review already permits production change.';
        }

        if ((bool) $implementationReview->automatic_deployment_allowed) {
            $criticalIssues[] =
                'Implementation review already permits automatic deployment.';
        }

        if ((bool) $implementationReview->automatic_change_allowed) {
            $criticalIssues[] =
                'Implementation review already permits automatic AI change.';
        }

        if ((bool) $improvementReview->automatic_change_allowed) {
            $criticalIssues[] =
                'Source improvement review permits automatic AI change.';
        }

        if ((bool) $improvementTest->production_change_allowed) {
            $criticalIssues[] =
                'Controlled test permits production change.';
        }

        if ((bool) $improvementTest->automatic_deployment_allowed) {
            $criticalIssues[] =
                'Controlled test permits automatic deployment.';
        }

        if (!(bool) $implementationReview->human_approval_required) {
            $criticalIssues[] =
                'Human approval requirement is disabled.';
        }

        if (!(bool) $implementationReview->governance_validation_required) {
            $criticalIssues[] =
                'Governance validation requirement is disabled.';
        }

        if (!empty($criticalIssues)) {
            return [
                'registered' => false,
                'status' => 'GOVERNANCE_BLOCKED',
                'message' => 'Execution registration is blocked because governance safety controls are invalid.',
                'implementation_review_id' => $implementationReview->id,
                'critical_issues' => $criticalIssues,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Change Specification Validation
        |--------------------------------------------------------------------------
        */

        $changeType =
            strtoupper(
                trim($changeType)
            );

        $changeSummary =
            trim($changeSummary);

        if ($changeType === '') {
            return [
                'registered' => false,
                'status' => 'INVALID_CHANGE_SPECIFICATION',
                'message' => 'change_type is required.',
            ];
        }

        if ($changeSummary === '') {
            return [
                'registered' => false,
                'status' => 'INVALID_CHANGE_SPECIFICATION',
                'message' => 'change_summary is required.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Detect Prohibited Execution Permissions
        |--------------------------------------------------------------------------
        */

        $prohibitedKeys = [
            'production_execution_allowed',
            'automatic_execution_allowed',
            'automatic_deployment_allowed',
            'automatic_rollback_allowed',
            'automatic_model_change',
            'automatic_threshold_change',
            'automatic_confidence_change',
            'automatic_recommendation_change',
            'automatic_workflow_change',
            'automatic_clinical_rule_change',
            'automatic_clinical_action',
        ];

        $prohibitedConfigurationKeys = [];

        foreach (
            [
                $proposedConfiguration,
                $executionScope,
            ]
            as $configuration
        ) {
            foreach ($prohibitedKeys as $key) {
                if (
                    array_key_exists(
                        $key,
                        $configuration
                    )
                    &&
                    $configuration[$key] === true
                ) {
                    $prohibitedConfigurationKeys[] =
                        $key;
                }
            }
        }

        $prohibitedConfigurationKeys =
            array_values(
                array_unique(
                    $prohibitedConfigurationKeys
                )
            );

        if (!empty($prohibitedConfigurationKeys)) {
            return [
                'registered' => false,
                'status' => 'PROHIBITED_EXECUTION_CONFIGURATION',
                'message' => 'Execution specification contains prohibited automatic or production execution permissions.',
                'prohibited_keys' => $prohibitedConfigurationKeys,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Duplicate Prevention
        |--------------------------------------------------------------------------
        */

        $existingExecution =
            AIImprovementExecution::query()
                ->where(
                    'implementation_review_id',
                    $implementationReview->id
                )
                ->first();

        if ($existingExecution) {
            return [
                'registered' => false,
                'status' => 'EXECUTION_ALREADY_REGISTERED',
                'message' => 'An execution preparation record already exists for this implementation governance review.',
                'execution_id' => $existingExecution->id,
                'execution_status' => $existingExecution->execution_status,
                'execution_stage' => $existingExecution->execution_stage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Create Execution Preparation Record
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $implementationReview,
                $improvementReview,
                $improvementTest,
                $changeType,
                $changeSummary,
                $baselineConfiguration,
                $proposedConfiguration,
                $executionScope
            ) {
                $execution =
                    AIImprovementExecution::create([

                        'implementation_review_id' =>
                            $implementationReview->id,

                        'improvement_review_id' =>
                            $improvementReview->id,

                        'improvement_test_id' =>
                            $improvementTest->id,

                        'candidate_code' =>
                            $implementationReview->candidate_code,

                        'candidate_category' =>
                            $implementationReview->candidate_category,

                        'scope_type' =>
                            $implementationReview->scope_type
                            ?? 'FACILITY',

                        'resident_id' =>
                            $implementationReview->resident_id,

                        /*
                        |--------------------------------------------------------------------------
                        | Initial Lifecycle
                        |--------------------------------------------------------------------------
                        */

                        'execution_status' =>
                            'REGISTERED',

                        'execution_stage' =>
                            'PREPARATION',

                        /*
                        |--------------------------------------------------------------------------
                        | Approved Change Specification
                        |--------------------------------------------------------------------------
                        */

                        'change_type' =>
                            $changeType,

                        'change_summary' =>
                            $changeSummary,

                        'baseline_configuration' =>
                            $baselineConfiguration,

                        'proposed_configuration' =>
                            $proposedConfiguration,

                        'execution_scope' =>
                            $executionScope,

                        /*
                        |--------------------------------------------------------------------------
                        | Impact & Safety Not Yet Evaluated
                        |--------------------------------------------------------------------------
                        */

                        'impact_analysis' =>
                            null,

                        'safety_validation' =>
                            null,

                        'safety_status' =>
                            'PENDING',

                        /*
                        |--------------------------------------------------------------------------
                        | Execution Authorization
                        |--------------------------------------------------------------------------
                        */

                        'execution_review_ready' =>
                            false,

                        'approved_for_execution' =>
                            false,

                        'authorized_by' =>
                            null,

                        'authorized_at' =>
                            null,

                        'authorization_notes' =>
                            null,

                        /*
                        |--------------------------------------------------------------------------
                        | Critical Execution Guardrails
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

                        'human_execution_required' =>
                            true,

                        'pre_execution_validation_required' =>
                            true,

                        'post_execution_validation_required' =>
                            true,

                        'rollback_plan_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        /*
                        |--------------------------------------------------------------------------
                        | Execution Evidence
                        |--------------------------------------------------------------------------
                        */

                        'execution_payload' => [
                            'registration_engine' =>
                                'AIImprovementExecutionRegistrationEngine',

                            'registration_version' =>
                                '56.2',

                            'implementation_governance_approved' =>
                                true,

                            'controlled_test_validated' =>
                                true,

                            'execution_authorized' =>
                                false,

                            'production_execution_authorized' =>
                                false,

                            'automatic_execution_authorized' =>
                                false,

                            'automatic_deployment_authorized' =>
                                false,

                            'registered_at' =>
                                now()->toIso8601String(),
                        ],

                        'execution_results' =>
                            null,

                        'verification_results' =>
                            null,

                        'rollback_plan' =>
                            null,

                        'rollback_results' =>
                            null,

                        /*
                        |--------------------------------------------------------------------------
                        | Timeline
                        |--------------------------------------------------------------------------
                        */

                        'registered_at' =>
                            now(),

                        'execution_started_at' =>
                            null,

                        'execution_completed_at' =>
                            null,

                        'verified_at' =>
                            null,

                        'rolled_back_at' =>
                            null,
                    ]);

                return [

                    'registered' =>
                        true,

                    'status' =>
                        'EXECUTION_REGISTERED',

                    'message' =>
                        'AI improvement execution preparation record registered successfully.',

                    'execution' => [

                        'execution_id' =>
                            $execution->id,

                        'implementation_review_id' =>
                            $execution->implementation_review_id,

                        'improvement_review_id' =>
                            $execution->improvement_review_id,

                        'improvement_test_id' =>
                            $execution->improvement_test_id,

                        'candidate_code' =>
                            $execution->candidate_code,

                        'candidate_category' =>
                            $execution->candidate_category,

                        'scope_type' =>
                            $execution->scope_type,

                        'resident_id' =>
                            $execution->resident_id,

                        'execution_status' =>
                            $execution->execution_status,

                        'execution_stage' =>
                            $execution->execution_stage,

                        'change_type' =>
                            $execution->change_type,

                        'change_summary' =>
                            $execution->change_summary,

                        'safety_status' =>
                            $execution->safety_status,

                        'execution_review_ready' =>
                            (bool) $execution->execution_review_ready,

                        'approved_for_execution' =>
                            (bool) $execution->approved_for_execution,

                        'production_execution_allowed' =>
                            (bool) $execution->production_execution_allowed,

                        'automatic_execution_allowed' =>
                            (bool) $execution->automatic_execution_allowed,

                        'automatic_deployment_allowed' =>
                            (bool) $execution->automatic_deployment_allowed,

                        'automatic_rollback_allowed' =>
                            (bool) $execution->automatic_rollback_allowed,

                        'registered_at' =>
                            $execution->registered_at,
                    ],

                    'registration_guardrails' => [

                        'implementation_approval_is_execution_authorization' =>
                            false,

                        'execution_registered_is_execution_authorized' =>
                            false,

                        'approved_for_execution' =>
                            false,

                        'production_execution_allowed' =>
                            false,

                        'automatic_execution_allowed' =>
                            false,

                        'automatic_deployment_allowed' =>
                            false,

                        'automatic_rollback_allowed' =>
                            false,

                        'human_execution_required' =>
                            true,

                        'pre_execution_validation_required' =>
                            true,

                        'post_execution_validation_required' =>
                            true,

                        'rollback_plan_required' =>
                            true,

                        'governance_validation_required' =>
                            true,

                        'message' =>
                            'Execution registration records an approved change specification for preparation only. It does not authorize or execute any production AI system change.',
                    ],
                ];
            }
        );
    }
}