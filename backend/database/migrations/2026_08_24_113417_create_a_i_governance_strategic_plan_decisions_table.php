<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plan_decisions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Strategic Planning Lineage
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('strategic_plan_id');

            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('decision_scope', 50)->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Decision Identity
            |--------------------------------------------------------------------------
            */

            $table->string('decision_code', 150)->unique();

            $table->string('decision_status', 100)
                ->default('PENDING_HUMAN_DECISION');

            $table->string('decision_mode', 100)
                ->default('HUMAN_GOVERNANCE_DECISION');

            /*
            |--------------------------------------------------------------------------
            | Human Governance Decision
            |--------------------------------------------------------------------------
            */

            $table->string('decision', 100)->nullable();

            $table->string('decision_priority', 50)->nullable();

            $table->decimal('decision_priority_score', 8, 2)->nullable();

            $table->text('decision_rationale')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Planning Decision Context
            |--------------------------------------------------------------------------
            */

            $table->string('planning_readiness', 100)->nullable();

            $table->decimal('planning_readiness_score', 8, 2)->nullable();

            $table->string('plan_risk_level', 100)->nullable();

            $table->decimal('plan_risk_score', 8, 2)->nullable();

            $table->string('dependency_feasibility_status', 120)->nullable();

            $table->decimal('dependency_adjusted_feasibility_score', 8, 2)->nullable();

            $table->unsignedInteger('blocking_dependency_count')->default(0);

            $table->unsignedInteger('constraining_dependency_count')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Human Conditions / Evidence
            |--------------------------------------------------------------------------
            */

            $table->json('decision_conditions')->nullable();

            $table->json('required_evidence')->nullable();

            $table->json('review_context')->nullable();

            $table->json('decision_context')->nullable();

            $table->json('source_context')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Reviewer Context
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->string('reviewer_role', 120)->nullable();

            $table->timestamp('reviewed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Explicit Authority Isolation
            |--------------------------------------------------------------------------
            */

            $table->boolean('automatic_execution_allowed')->default(false);

            $table->boolean('automatic_change_allowed')->default(false);

            $table->boolean('automatic_deployment_allowed')->default(false);

            $table->boolean('automatic_rollback_allowed')->default(false);

            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);

            $table->boolean('governance_validation_required')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Decision Lifecycle
            |--------------------------------------------------------------------------
            */

            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('strategic_plan_id', 'agspd_plan_fk')
                ->references('id')
                ->on('ai_governance_strategic_plans')
                ->cascadeOnDelete();

            $table->index('strategic_snapshot_id', 'agspd_strat_snap_idx');
            $table->index('operational_snapshot_id', 'agspd_oper_snap_idx');
            $table->index('lifecycle_snapshot_id', 'agspd_lifecycle_snap_idx');

            $table->index('resident_id', 'agspd_resident_idx');
            $table->index('reviewed_by', 'agspd_reviewer_idx');

            $table->index('decision_status', 'agspd_status_idx');
            $table->index('decision', 'agspd_decision_idx');
            $table->index('decision_priority', 'agspd_priority_idx');
                    });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_governance_strategic_plan_decisions');
    }
};