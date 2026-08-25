<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'ai_governance_strategic_plan_activation_execution_auths',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Source Governance Chain
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger('strategic_plan_controlled_activation_authorization_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_governance_decision_confirmation_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_final_governance_decision_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_decision_validation_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_human_decision_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_decision_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_plan_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('strategic_snapshot_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('operational_snapshot_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('lifecycle_snapshot_id')
                    ->nullable();

                $table
                    ->string('decision_scope', 50)
                    ->nullable();

                $table
                    ->unsignedBigInteger('resident_id')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Step 70 Execution Authorization Identity
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('execution_authorization_code', 191);

                $table
                    ->string('execution_authorization_status', 120)
                    ->default(
                        'PENDING_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION'
                    );

                $table
                    ->string('execution_authorization_mode', 120)
                    ->default(
                        'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION_AUTHORIZATION'
                    );

                /*
                |--------------------------------------------------------------------------
                | Source Step 69 Activation Authorization State
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('source_activation_authorization_decision', 100)
                    ->nullable();

                $table
                    ->boolean('source_activation_authorization_decision_recorded')
                    ->default(false);

                $table
                    ->boolean('source_activation_authorization_made_by_authorized_human')
                    ->default(false);

                $table
                    ->boolean('source_activation_authorization_attribution_complete')
                    ->default(false);

                $table
                    ->boolean('source_controlled_activation_authorization_completed')
                    ->default(false);

                $table
                    ->string('source_activation_authorization_outcome', 100)
                    ->nullable();

                $table
                    ->string('source_activation_authorization_outcome_status', 120)
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Source Step 69 Execution State
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('source_controlled_activation_execution_status', 120)
                    ->nullable();

                $table
                    ->boolean('source_controlled_activation_execution_authorized')
                    ->default(false);

                $table
                    ->string('source_activation_execution_authorization_code', 191)
                    ->nullable();

                $table
                    ->string('source_execution_authorized_by', 191)
                    ->nullable();

                $table
                    ->string('source_execution_authorizer_role', 191)
                    ->nullable();

                $table
                    ->timestamp('source_execution_authorized_at')
                    ->nullable();

                $table
                    ->boolean('source_execution_authorization_attribution_complete')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Step 70 Authorized-Human Execution Authorization Decision
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('controlled_activation_execution_authorization_decision', 100)
                    ->nullable();

                $table
                    ->text('execution_authorization_rationale')
                    ->nullable();

                $table
                    ->text('execution_authorization_notes')
                    ->nullable();

                $table
                    ->string('execution_authorization_outcome', 100)
                    ->nullable();

                $table
                    ->string('execution_authorization_outcome_status', 120)
                    ->default(
                        'PENDING_AUTHORIZED_HUMAN_ACTIVATION_EXECUTION_AUTHORIZATION'
                    );

                /*
                |--------------------------------------------------------------------------
                | Readiness / Risk
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('execution_readiness', 140)
                    ->default(
                        'AWAITING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                    );

                $table
                    ->decimal('execution_readiness_score', 6, 2)
                    ->default(0);

                $table
                    ->string('execution_risk_level', 120)
                    ->nullable();

                $table
                    ->decimal('execution_risk_score', 6, 2)
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Resolution / Pressure Intelligence
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal('condition_resolution_score', 6, 2)
                    ->default(0);

                $table
                    ->decimal('evidence_resolution_score', 6, 2)
                    ->default(0);

                $table
                    ->decimal('combined_resolution_score', 6, 2)
                    ->default(0);

                $table
                    ->decimal('condition_pressure_score', 6, 2)
                    ->default(0);

                $table
                    ->decimal('restriction_pressure_score', 6, 2)
                    ->default(0);

                $table
                    ->decimal(
                        'combined_condition_restriction_pressure_score',
                        6,
                        2
                    )
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Structured Intelligence Package
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('execution_conditions')
                    ->nullable();

                $table
                    ->json('validated_evidence')
                    ->nullable();

                $table
                    ->json('execution_findings')
                    ->nullable();

                $table
                    ->json('execution_restrictions')
                    ->nullable();

                $table
                    ->json('review_context')
                    ->nullable();

                $table
                    ->json('activation_authorization_context')
                    ->nullable();

                $table
                    ->json('governance_context')
                    ->nullable();

                $table
                    ->json('source_context')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Authorized Human Execution Authorization Attribution
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('execution_authorized_by', 191)
                    ->nullable();

                $table
                    ->string('execution_authorizer_role', 191)
                    ->nullable();

                $table
                    ->timestamp('execution_authorized_at')
                    ->nullable();

                $table
                    ->boolean('execution_authorization_made_by_authorized_human')
                    ->default(false);

                $table
                    ->boolean('controlled_activation_execution_authorization_completed')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Actual Controlled Activation Execution
                |--------------------------------------------------------------------------
                |
                | Step 70 DOES NOT execute activation.
                | These fields keep execution mechanically separated from
                | execution authorization.
                |
                */

                $table
                    ->string('controlled_activation_execution_status', 120)
                    ->default('NOT_EXECUTED');

                $table
                    ->boolean('controlled_activation_executed')
                    ->default(false);

                $table
                    ->string('controlled_activation_execution_code', 191)
                    ->nullable();

                $table
                    ->string('executed_by', 191)
                    ->nullable();

                $table
                    ->string('executor_role', 191)
                    ->nullable();

                $table
                    ->timestamp('executed_at')
                    ->nullable();

                $table
                    ->boolean('execution_attribution_complete')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Autonomous Authority Guardrails
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('automatic_execution_authorization_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_activation_authorization_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_confirmation_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_final_decision_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_approval_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_rejection_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_conditional_approval_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_deferral_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_risk_acceptance_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_activation_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_condition_resolution_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_restriction_removal_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_evidence_validation_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_execution_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_change_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_deployment_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_rollback_allowed')
                    ->default(false);

                $table
                    ->boolean('automatic_clinical_action_allowed')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Mandatory Governance Requirements
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('human_review_required')
                    ->default(true);

                $table
                    ->boolean('governance_confirmation_required')
                    ->default(true);

                $table
                    ->boolean('controlled_activation_authorization_required')
                    ->default(true);

                $table
                    ->boolean('authorized_human_execution_authorization_required')
                    ->default(true);

                $table
                    ->boolean('authorized_human_execution_required')
                    ->default(true);

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Explicit Short Index Names
                |--------------------------------------------------------------------------
                |
                | Avoid MySQL identifier-length failures.
                |
                */

                $table->unique(
                    'execution_authorization_code',
                    'sp_exec_auth_code_uq'
                );

                $table->index(
                    'strategic_plan_controlled_activation_authorization_id',
                    'sp_exec_src_auth_idx'
                );

                $table->index(
                    'strategic_plan_governance_decision_confirmation_id',
                    'sp_exec_conf_idx'
                );

                $table->index(
                    'strategic_plan_final_governance_decision_id',
                    'sp_exec_final_dec_idx'
                );

                $table->index(
                    'strategic_plan_decision_validation_id',
                    'sp_exec_val_idx'
                );

                $table->index(
                    'strategic_plan_id',
                    'sp_exec_plan_idx'
                );

                $table->index(
                    'resident_id',
                    'sp_exec_resident_idx'
                );

                $table->index(
                    'execution_authorization_status',
                    'sp_exec_status_idx'
                );

                $table->index(
                    'controlled_activation_execution_status',
                    'sp_exec_run_status_idx'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'ai_governance_strategic_plan_activation_execution_auths'
        );
    }
};