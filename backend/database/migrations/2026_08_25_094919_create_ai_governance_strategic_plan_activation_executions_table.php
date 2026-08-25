<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plan_activation_executions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('strategic_plan_controlled_activation_execution_authorization_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_controlled_activation_authorization_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_governance_decision_confirmation_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_final_governance_decision_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_decision_validation_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_human_decision_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_decision_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_id')->nullable();
            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('decision_scope', 50)->nullable();
            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('controlled_activation_execution_code', 191);
            $table->string('controlled_activation_execution_status', 120)
                ->default('PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION');

            $table->string('controlled_activation_execution_mode', 120)
                ->default('AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION');

            $table->string('source_execution_authorization_decision', 100)->nullable();
            $table->boolean('source_execution_authorization_decision_recorded')->default(false);
            $table->boolean('source_execution_authorization_made_by_authorized_human')->default(false);
            $table->boolean('source_execution_authorization_attribution_complete')->default(false);
            $table->boolean('source_execution_authorization_completed')->default(false);

            $table->string('source_execution_authorization_status', 120)->nullable();
            $table->string('source_execution_authorization_outcome', 100)->nullable();
            $table->string('source_execution_authorization_outcome_status', 120)->nullable();

            $table->string('execution_decision', 100)->nullable();
            $table->text('execution_rationale')->nullable();
            $table->text('execution_notes')->nullable();

            $table->string('execution_outcome', 100)->nullable();
            $table->string('execution_outcome_status', 120)
                ->default('PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_EXECUTION');

            $table->string('execution_readiness', 120)
                ->default('AWAITING_AUTHORIZED_HUMAN_EXECUTION_AUTHORIZATION');

            $table->decimal('execution_readiness_score', 6, 2)->default(0);
            $table->string('execution_risk_level', 100)->nullable();
            $table->decimal('execution_risk_score', 6, 2)->default(0);

            $table->decimal('condition_resolution_score', 6, 2)->default(0);
            $table->decimal('evidence_resolution_score', 6, 2)->default(0);
            $table->decimal('combined_resolution_score', 6, 2)->default(0);
            $table->decimal('condition_pressure_score', 6, 2)->default(0);
            $table->decimal('restriction_pressure_score', 6, 2)->default(0);
            $table->decimal('combined_condition_restriction_pressure_score', 6, 2)->default(0);

            $table->json('execution_conditions')->nullable();
            $table->json('validated_evidence')->nullable();
            $table->json('execution_findings')->nullable();
            $table->json('execution_restrictions')->nullable();

            $table->json('review_context')->nullable();
            $table->json('execution_authorization_context')->nullable();
            $table->json('governance_context')->nullable();
            $table->json('source_context')->nullable();

            $table->string('executed_by', 191)->nullable();
            $table->string('executor_role', 191)->nullable();
            $table->timestamp('executed_at')->nullable();

            $table->boolean('execution_made_by_authorized_human')->default(false);
            $table->boolean('execution_attribution_complete')->default(false);
            $table->boolean('controlled_activation_executed')->default(false);

            $table->string('post_execution_status', 120)
                ->default('NOT_EXECUTED');

            $table->string('post_execution_validation_status', 120)->nullable();
            $table->boolean('post_execution_validation_completed')->default(false);

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_execution_authorization_allowed')->default(false);
            $table->boolean('automatic_activation_authorization_allowed')->default(false);
            $table->boolean('automatic_confirmation_allowed')->default(false);
            $table->boolean('automatic_final_decision_allowed')->default(false);
            $table->boolean('automatic_approval_allowed')->default(false);
            $table->boolean('automatic_rejection_allowed')->default(false);
            $table->boolean('automatic_conditional_approval_allowed')->default(false);
            $table->boolean('automatic_deferral_allowed')->default(false);
            $table->boolean('automatic_risk_acceptance_allowed')->default(false);
            $table->boolean('automatic_activation_allowed')->default(false);
            $table->boolean('automatic_condition_resolution_allowed')->default(false);
            $table->boolean('automatic_restriction_removal_allowed')->default(false);
            $table->boolean('automatic_evidence_validation_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);
            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);
            $table->boolean('governance_confirmation_required')->default(true);
            $table->boolean('controlled_activation_authorization_required')->default(true);
            $table->boolean('authorized_human_execution_authorization_required')->default(true);
            $table->boolean('authorized_human_execution_required')->default(true);
            $table->boolean('post_execution_human_validation_required')->default(true);

            $table->timestamps();

            $table->unique(
                'controlled_activation_execution_code',
                'ai_gov_sp_act_exec_code_uq'
            );

            $table->unique(
                'strategic_plan_controlled_activation_execution_authorization_id',
                'ai_gov_sp_exec_auth_uq'
            );

            $table->index(
                'strategic_plan_controlled_activation_authorization_id',
                'ai_gov_sp_act_auth_idx'
            );

            $table->index(
                'strategic_plan_id',
                'ai_gov_sp_exec_plan_idx'
            );

            $table->index(
                'resident_id',
                'ai_gov_sp_exec_res_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_governance_strategic_plan_activation_executions');
    }
};