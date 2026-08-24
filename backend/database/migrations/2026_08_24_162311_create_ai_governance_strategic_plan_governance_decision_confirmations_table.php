<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_governance_strategic_plan_governance_decision_confirmations',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Source Governance Chain
                |--------------------------------------------------------------------------
                */

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
                    ->string('decision_scope', 100)
                    ->nullable();

                $table
                    ->unsignedBigInteger('resident_id')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Explicit Short Index Names
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'strategic_plan_final_governance_decision_id',
                    'sp_gov_confirm_final_dec_idx'
                );

                $table->index(
                    'strategic_plan_decision_validation_id',
                    'sp_gov_confirm_validation_idx'
                );

                $table->index(
                    'strategic_plan_human_decision_id',
                    'sp_gov_confirm_human_dec_idx'
                );

                $table->index(
                    'strategic_plan_decision_id',
                    'sp_gov_confirm_decision_idx'
                );

                $table->index(
                    'strategic_plan_id',
                    'sp_gov_confirm_plan_idx'
                );

                $table->index(
                    'strategic_snapshot_id',
                    'sp_gov_confirm_strat_snap_idx'
                );

                $table->index(
                    'operational_snapshot_id',
                    'sp_gov_confirm_op_snap_idx'
                );

                $table->index(
                    'lifecycle_snapshot_id',
                    'sp_gov_confirm_life_snap_idx'
                );

                $table->index(
                    'decision_scope',
                    'sp_gov_confirm_scope_idx'
                );

                $table->index(
                    'resident_id',
                    'sp_gov_confirm_resident_idx'
                );

                /*
                |--------------------------------------------------------------------------
                | Confirmation Identity
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('confirmation_code', 191);

                $table->unique(
                    'confirmation_code',
                    'sp_gov_confirm_code_uq'
                );

                $table
                    ->string('confirmation_status', 150)
                    ->default(
                        'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION'
                    );

                $table->index(
                    'confirmation_status',
                    'sp_gov_confirm_status_idx'
                );

                $table
                    ->string('confirmation_mode', 150)
                    ->default(
                        'AUTHORIZED_HUMAN_GOVERNANCE_DECISION_CONFIRMATION'
                    );

                /*
                |--------------------------------------------------------------------------
                | Source Final Governance Decision
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('prepared_decision', 150)
                    ->nullable();

                $table
                    ->string('source_final_governance_decision', 150)
                    ->nullable();

                $table
                    ->boolean('source_final_governance_decision_recorded')
                    ->default(false);

                $table
                    ->boolean('source_decision_made_by_authorized_human')
                    ->default(false);

                $table
                    ->string('source_final_governance_outcome', 150)
                    ->nullable();

                $table
                    ->string('source_final_governance_outcome_status', 150)
                    ->nullable();

                $table
                    ->boolean('source_governance_validation_completed')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Governance Confirmation
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('governance_confirmation_decision', 150)
                    ->nullable();

                $table
                    ->text('confirmation_rationale')
                    ->nullable();

                $table
                    ->text('confirmation_notes')
                    ->nullable();

                $table
                    ->string('confirmation_outcome', 150)
                    ->nullable();

                $table
                    ->string('confirmation_outcome_status', 150)
                    ->default(
                        'PENDING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION'
                    );

                /*
                |--------------------------------------------------------------------------
                | Confirmation Readiness / Risk
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('confirmation_readiness', 150)
                    ->nullable();

                $table
                    ->decimal('confirmation_readiness_score', 8, 2)
                    ->default(0);

                $table
                    ->string('confirmation_risk_level', 150)
                    ->nullable();

                $table
                    ->decimal('confirmation_risk_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal('condition_resolution_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal('evidence_resolution_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal('combined_resolution_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal('condition_pressure_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal('restriction_pressure_score', 8, 2)
                    ->default(0);

                $table
                    ->decimal(
                        'combined_condition_restriction_pressure_score',
                        8,
                        2
                    )
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Structured Confirmation Context
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('confirmation_conditions')
                    ->nullable();

                $table
                    ->json('validated_evidence')
                    ->nullable();

                $table
                    ->json('confirmation_findings')
                    ->nullable();

                $table
                    ->json('confirmation_restrictions')
                    ->nullable();

                $table
                    ->json('review_context')
                    ->nullable();

                $table
                    ->json('validation_context')
                    ->nullable();

                $table
                    ->json('governance_context')
                    ->nullable();

                $table
                    ->json('source_context')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Authorized Human Confirmation Attribution
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('confirmed_by', 191)
                    ->nullable();

                $table
                    ->string('confirmer_role', 191)
                    ->nullable();

                $table
                    ->timestamp('confirmed_at')
                    ->nullable();

                $table
                    ->boolean('confirmation_made_by_authorized_human')
                    ->default(false);

                $table
                    ->boolean('governance_decision_confirmation_completed')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Controlled Activation State
                |--------------------------------------------------------------------------
                |
                | This registry records activation governance state only.
                | It does not activate the strategic plan.
                |
                */

                $table
                    ->string('controlled_activation_status', 150)
                    ->default('NOT_AUTHORIZED_FOR_ACTIVATION');

                $table
                    ->boolean('controlled_activation_authorized')
                    ->default(false);

                $table
                    ->string(
                        'controlled_activation_authorization_code',
                        191
                    )
                    ->nullable();

                $table
                    ->string('activation_authorized_by', 191)
                    ->nullable();

                $table
                    ->string('activation_authorizer_role', 191)
                    ->nullable();

                $table
                    ->timestamp('activation_authorized_at')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Automatic Authority Prohibitions
                |--------------------------------------------------------------------------
                */

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
                | Mandatory Human Governance Controls
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('human_review_required')
                    ->default(true);

                $table
                    ->boolean('governance_validation_required')
                    ->default(true);

                $table
                    ->boolean('authorized_human_confirmation_required')
                    ->default(true);

                $table
                    ->boolean('authorized_human_activation_required')
                    ->default(true);

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_governance_strategic_plan_governance_decision_confirmations'
        );
    }
};