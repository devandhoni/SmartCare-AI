<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_governance_operational_snapshots', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('snapshot_scope', 50)->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('snapshot_status', 50)->default('CAPTURED');
            $table->string('operational_status', 100)->default('NOT_EVALUATED');
            $table->string('governance_workload_status', 100)->nullable();
            $table->string('decision_status', 100)->nullable();

            $table->unsignedInteger('total_governance_actions')->default(0);
            $table->unsignedInteger('active_governance_actions')->default(0);
            $table->unsignedInteger('closed_governance_actions')->default(0);

            $table->unsignedInteger('pending_human_reviews')->default(0);
            $table->unsignedInteger('pending_human_decisions')->default(0);
            $table->unsignedInteger('evidence_waiting_actions')->default(0);
            $table->unsignedInteger('deferred_actions')->default(0);

            $table->unsignedInteger('high_priority_active_actions')->default(0);
            $table->unsignedInteger('critical_priority_active_actions')->default(0);

            $table->decimal('action_closure_percentage', 8, 2)->default(0);
            $table->decimal('decision_completion_percentage', 8, 2)->default(0);

            $table->string('decision_consistency_status', 100)->nullable();
            $table->decimal('decision_consistency_score', 8, 2)->nullable();

            $table->string('decision_risk_level', 100)->nullable();
            $table->unsignedInteger('decision_risk_score')->nullable();

            $table->json('operational_context')->nullable();
            $table->json('source_context')->nullable();

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);
            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);
            $table->boolean('governance_validation_required')->default(true);

            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            $table->index('lifecycle_snapshot_id');
            $table->index('snapshot_scope');
            $table->index('snapshot_status');
            $table->index('operational_status');
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a_i_governance_operational_snapshots');
    }
};