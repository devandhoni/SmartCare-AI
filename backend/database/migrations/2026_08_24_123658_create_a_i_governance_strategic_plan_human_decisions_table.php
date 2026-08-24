<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plan_human_decisions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source Governance References
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('strategic_plan_decision_id');
            $table->unsignedBigInteger('strategic_plan_id')->nullable();
            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Decision Scope
            |--------------------------------------------------------------------------
            */

            $table->string('decision_scope', 50)->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Decision Identity
            |--------------------------------------------------------------------------
            */

            $table->string('human_decision_code', 120);
            $table->unique('human_decision_code', 'sp_human_dec_code_uq');

            $table->string('human_decision_status', 80)
                ->default('PENDING_AUTHORIZED_HUMAN_DECISION');

            $table->string('human_decision_mode', 100)
                ->default('AUTHORIZED_HUMAN_GOVERNANCE_DECISION');

            /*
            |--------------------------------------------------------------------------
            | Prepared Decision Context
            |--------------------------------------------------------------------------
            */

            $table->string('prepared_decision', 100)->nullable();

            $table->string('prepared_decision_status', 80)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Final Human Governance Decision
            |--------------------------------------------------------------------------
            */

            $table->string('final_human_decision', 100)->nullable();

            $table->text('decision_rationale')->nullable();

            $table->text('decision_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Decision Classification
            |--------------------------------------------------------------------------
            */

            $table->string('decision_priority', 50)->nullable();
            $table->decimal('decision_priority_score', 8, 2)->nullable();

            $table->string('decision_risk_level', 80)->nullable();
            $table->decimal('decision_risk_score', 8, 2)->nullable();

            $table->string('approval_eligibility', 100)->nullable();
            $table->decimal('decision_eligibility_score', 8, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Governance Conditions
            |--------------------------------------------------------------------------
            */

            $table->json('decision_conditions')->nullable();
            $table->json('condition_resolution_context')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Governance Evidence
            |--------------------------------------------------------------------------
            */

            $table->json('evidence_context')->nullable();
            $table->json('validated_evidence')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Decision Context
            |--------------------------------------------------------------------------
            */

            $table->json('review_context')->nullable();
            $table->json('governance_context')->nullable();
            $table->json('source_context')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Authority
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('decided_by')->nullable();
            $table->string('decider_role', 120)->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Governance Validation
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('validated_by')->nullable();
            $table->string('validator_role', 120)->nullable();
            $table->timestamp('validated_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Governance Guardrails
            |--------------------------------------------------------------------------
            */

            $table->boolean('decision_made_by_authorized_human')
                ->default(false);

            $table->boolean('governance_validation_completed')
                ->default(false);

            $table->boolean('automatic_decision_allowed')
                ->default(false);

            $table->boolean('automatic_approval_allowed')
                ->default(false);

            $table->boolean('automatic_rejection_allowed')
                ->default(false);

            $table->boolean('automatic_activation_allowed')
                ->default(false);

            $table->boolean('automatic_condition_resolution_allowed')
                ->default(false);

            $table->boolean('automatic_evidence_validation_allowed')
                ->default(false);

            $table->boolean('automatic_execution_allowed')
                ->default(false);

            $table->boolean('automatic_change_allowed')
                ->default(false);

            $table->boolean('automatic_deployment_allowed')
                ->default(false);

            $table->boolean('automatic_rollback_allowed')
                ->default(false);

            $table->boolean('automatic_clinical_action_allowed')
                ->default(false);

            $table->boolean('human_review_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Short Explicit Index Names
            |--------------------------------------------------------------------------
            |
            | We use explicit names because MySQL has a 64-character identifier
            | limit and these table/column names are long.
            |
            */

            $table->index(
                'strategic_plan_decision_id',
                'sp_human_dec_sp_dec_idx'
            );

            $table->index(
                'strategic_plan_id',
                'sp_human_dec_plan_idx'
            );

            $table->index(
                'strategic_snapshot_id',
                'sp_human_dec_strat_snap_idx'
            );

            $table->index(
                'operational_snapshot_id',
                'sp_human_dec_op_snap_idx'
            );

            $table->index(
                'lifecycle_snapshot_id',
                'sp_human_dec_life_snap_idx'
            );

            $table->index(
                'resident_id',
                'sp_human_dec_resident_idx'
            );

            $table->index(
                'human_decision_status',
                'sp_human_dec_status_idx'
            );

            $table->index(
                'final_human_decision',
                'sp_human_dec_final_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_governance_strategic_plan_human_decisions');
    }
};