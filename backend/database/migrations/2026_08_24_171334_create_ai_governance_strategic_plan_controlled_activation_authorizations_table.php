<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_governance_strategic_plan_activation_authorizations',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Source Governance References
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'strategic_plan_governance_decision_confirmation_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_plan_final_governance_decision_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_plan_decision_validation_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_plan_human_decision_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_plan_decision_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_plan_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'strategic_snapshot_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'operational_snapshot_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'lifecycle_snapshot_id'
                )->nullable();

                $table->string(
                    'decision_scope',
                    50
                )->nullable();

                $table->unsignedBigInteger(
                    'resident_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Authorization Identity
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'activation_authorization_code',
                    191
                );

                $table->unique(
                    'activation_authorization_code',
                    'uq_ca_auth_code'
                );

                $table->string(
                    'activation_authorization_status',
                    100
                )->default(
                    'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                );

                $table->string(
                    'activation_authorization_mode',
                    100
                )->default(
                    'AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                );

                /*
                |--------------------------------------------------------------------------
                | Source Confirmation State
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'source_confirmation_decision',
                    100
                )->nullable();

                $table->boolean(
                    'source_confirmation_decision_recorded'
                )->default(false);

                $table->boolean(
                    'source_confirmation_made_by_authorized_human'
                )->default(false);

                $table->boolean(
                    'source_governance_decision_confirmation_completed'
                )->default(false);

                $table->string(
                    'source_controlled_activation_status',
                    100
                )->nullable();

                $table->boolean(
                    'source_controlled_activation_authorized'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Controlled Activation Authorization
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'controlled_activation_authorization_decision',
                    100
                )->nullable();

                $table->text(
                    'activation_authorization_rationale'
                )->nullable();

                $table->text(
                    'activation_authorization_notes'
                )->nullable();

                $table->string(
                    'activation_authorization_outcome',
                    100
                )->nullable();

                $table->string(
                    'activation_authorization_outcome_status',
                    100
                )->default(
                    'PENDING_AUTHORIZED_HUMAN_CONTROLLED_ACTIVATION_AUTHORIZATION'
                );

                /*
                |--------------------------------------------------------------------------
                | Readiness / Risk / Resolution
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'activation_readiness',
                    120
                )->default(
                    'AWAITING_AUTHORIZED_HUMAN_GOVERNANCE_CONFIRMATION'
                );

                $table->decimal(
                    'activation_readiness_score',
                    6,
                    2
                )->default(0);

                $table->string(
                    'activation_risk_level',
                    100
                )->nullable();

                $table->decimal(
                    'activation_risk_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'condition_resolution_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'evidence_resolution_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'combined_resolution_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'condition_pressure_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'restriction_pressure_score',
                    6,
                    2
                )->default(0);

                $table->decimal(
                    'combined_condition_restriction_pressure_score',
                    6,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Structured Governance Intelligence
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'activation_conditions'
                )->nullable();

                $table->json(
                    'validated_evidence'
                )->nullable();

                $table->json(
                    'activation_findings'
                )->nullable();

                $table->json(
                    'activation_restrictions'
                )->nullable();

                $table->json(
                    'review_context'
                )->nullable();

                $table->json(
                    'confirmation_context'
                )->nullable();

                $table->json(
                    'governance_context'
                )->nullable();

                $table->json(
                    'source_context'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Authorized Human Activation Authorization
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'authorized_by',
                    191
                )->nullable();

                $table->string(
                    'authorizer_role',
                    191
                )->nullable();

                $table->timestamp(
                    'authorized_at'
                )->nullable();

                $table->boolean(
                    'activation_authorization_made_by_authorized_human'
                )->default(false);

                $table->boolean(
                    'controlled_activation_authorization_completed'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Activation Execution State
                |--------------------------------------------------------------------------
                |
                | Authorization and actual activation remain separate.
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'controlled_activation_execution_status',
                    100
                )->default(
                    'NOT_AUTHORIZED_FOR_ACTIVATION_EXECUTION'
                );

                $table->boolean(
                    'controlled_activation_execution_authorized'
                )->default(false);

                $table->string(
                    'activation_execution_authorization_code',
                    191
                )->nullable();

                $table->string(
                    'execution_authorized_by',
                    191
                )->nullable();

                $table->string(
                    'execution_authorizer_role',
                    191
                )->nullable();

                $table->timestamp(
                    'execution_authorized_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Automatic Authority Prohibitions
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'automatic_activation_authorization_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_confirmation_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_final_decision_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_approval_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_rejection_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_conditional_approval_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_deferral_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_risk_acceptance_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_activation_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_condition_resolution_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_restriction_removal_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_evidence_validation_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_execution_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_change_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_deployment_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_rollback_allowed'
                )->default(false);

                $table->boolean(
                    'automatic_clinical_action_allowed'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Mandatory Human Governance
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'human_review_required'
                )->default(true);

                $table->boolean(
                    'governance_confirmation_required'
                )->default(true);

                $table->boolean(
                    'authorized_human_activation_authorization_required'
                )->default(true);

                $table->boolean(
                    'authorized_human_activation_execution_required'
                )->default(true);

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Explicit Short Index Names
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'strategic_plan_governance_decision_confirmation_id',
                    'idx_ca_confirmation'
                );

                $table->index(
                    'strategic_plan_final_governance_decision_id',
                    'idx_ca_final_decision'
                );

                $table->index(
                    'strategic_plan_decision_validation_id',
                    'idx_ca_validation'
                );

                $table->index(
                    'strategic_plan_human_decision_id',
                    'idx_ca_human_decision'
                );

                $table->index(
                    'strategic_plan_decision_id',
                    'idx_ca_decision'
                );

                $table->index(
                    'strategic_plan_id',
                    'idx_ca_plan'
                );

                $table->index(
                    'strategic_snapshot_id',
                    'idx_ca_strategic_snap'
                );

                $table->index(
                    'operational_snapshot_id',
                    'idx_ca_operational_snap'
                );

                $table->index(
                    'lifecycle_snapshot_id',
                    'idx_ca_lifecycle_snap'
                );

                $table->index(
                    'resident_id',
                    'idx_ca_resident'
                );

                $table->index(
                    'activation_authorization_status',
                    'idx_ca_auth_status'
                );

                $table->index(
                    'controlled_activation_authorization_completed',
                    'idx_ca_auth_complete'
                );

                $table->index(
                    'controlled_activation_execution_authorized',
                    'idx_ca_exec_auth'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_governance_strategic_plan_activation_authorizations'
        );
    }
};