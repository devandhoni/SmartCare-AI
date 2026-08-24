<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plan_decision_validations', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source Decision References
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('strategic_plan_human_decision_id');
            $table->unsignedBigInteger('strategic_plan_decision_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_id')->nullable();

            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scope
            |--------------------------------------------------------------------------
            */

            $table->string('decision_scope', 50)->nullable();
            $table->unsignedBigInteger('resident_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Validation Identity
            |--------------------------------------------------------------------------
            */

            $table->string('validation_code', 100);

            $table->string(
                'validation_status',
                80
            )->default('PENDING_GOVERNANCE_VALIDATION');

            $table->string(
                'validation_mode',
                100
            )->default('AUTHORIZED_HUMAN_GOVERNANCE_VALIDATION');

            /*
            |--------------------------------------------------------------------------
            | Human Decision Context
            |--------------------------------------------------------------------------
            */

            $table->string('prepared_decision', 100)->nullable();
            $table->string('final_human_decision', 100)->nullable();

            $table->boolean(
                'final_human_decision_recorded'
            )->default(false);

            $table->boolean(
                'decision_made_by_authorized_human'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Governance Validation Decision
            |--------------------------------------------------------------------------
            */

            $table->string(
                'governance_validation_decision',
                100
            )->nullable();

            $table->text(
                'validation_rationale'
            )->nullable();

            $table->text(
                'validation_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Readiness / Risk / Resolution Context
            |--------------------------------------------------------------------------
            */

            $table->string(
                'governance_validation_readiness',
                100
            )->nullable();

            $table->decimal(
                'governance_validation_readiness_score',
                8,
                2
            )->nullable();

            $table->string(
                'decision_risk_level',
                100
            )->nullable();

            $table->decimal(
                'decision_risk_score',
                8,
                2
            )->nullable();

            $table->decimal(
                'condition_resolution_score',
                8,
                2
            )->nullable();

            $table->decimal(
                'evidence_resolution_score',
                8,
                2
            )->nullable();

            $table->decimal(
                'combined_resolution_score',
                8,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Governance Context
            |--------------------------------------------------------------------------
            */

            $table->json('validation_conditions')->nullable();
            $table->json('validated_evidence')->nullable();
            $table->json('validation_findings')->nullable();
            $table->json('review_context')->nullable();
            $table->json('governance_context')->nullable();
            $table->json('source_context')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Validator Attribution
            |--------------------------------------------------------------------------
            */

            $table->string('validated_by')->nullable();
            $table->string('validator_role')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->boolean(
                'validation_made_by_authorized_human'
            )->default(false);

            $table->boolean(
                'governance_validation_completed'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Authority Isolation
            |--------------------------------------------------------------------------
            */

            $table->boolean('automatic_validation_allowed')->default(false);
            $table->boolean('automatic_decision_allowed')->default(false);
            $table->boolean('automatic_approval_allowed')->default(false);
            $table->boolean('automatic_rejection_allowed')->default(false);
            $table->boolean('automatic_activation_allowed')->default(false);

            $table->boolean(
                'automatic_condition_resolution_allowed'
            )->default(false);

            $table->boolean(
                'automatic_evidence_validation_allowed'
            )->default(false);

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);

            $table->boolean(
                'automatic_clinical_action_allowed'
            )->default(false);

            $table->boolean('human_review_required')->default(true);

            $table->boolean(
                'governance_validation_required'
            )->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Short Explicit Index Names
            |--------------------------------------------------------------------------
            | MySQL has a 64-character identifier limit, so we explicitly use
            | short names rather than Laravel's generated index names.
            |--------------------------------------------------------------------------
            */

            $table->unique(
                'validation_code',
                'gov_sp_val_code_uq'
            );

            $table->index(
                'strategic_plan_human_decision_id',
                'gov_sp_val_hdec_idx'
            );

            $table->index(
                'strategic_plan_decision_id',
                'gov_sp_val_dec_idx'
            );

            $table->index(
                'strategic_plan_id',
                'gov_sp_val_plan_idx'
            );

            $table->index(
                'strategic_snapshot_id',
                'gov_sp_val_ss_idx'
            );

            $table->index(
                'operational_snapshot_id',
                'gov_sp_val_os_idx'
            );

            $table->index(
                'lifecycle_snapshot_id',
                'gov_sp_val_ls_idx'
            );

            $table->index(
                'resident_id',
                'gov_sp_val_res_idx'
            );

            $table->index(
                'validation_status',
                'gov_sp_val_status_idx'
            );

            $table->index(
                'governance_validation_completed',
                'gov_sp_val_done_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_governance_strategic_plan_decision_validations'
        );
    }
};