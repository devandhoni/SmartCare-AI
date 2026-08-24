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
       Schema::create('ai_governance_strategic_snapshots', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('snapshot_scope')->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('snapshot_status')->default('CAPTURED');

            $table->string('strategic_status')->nullable();
            $table->string('strategic_readiness')->nullable();
            $table->string('capacity_status')->nullable();
            $table->string('demand_pressure_status')->nullable();
            $table->string('constraint_status')->nullable();

            $table->unsignedInteger('total_governance_actions')->default(0);
            $table->unsignedInteger('active_governance_actions')->default(0);
            $table->unsignedInteger('closed_governance_actions')->default(0);

            $table->unsignedInteger('pending_human_reviews')->default(0);
            $table->unsignedInteger('evidence_waiting_actions')->default(0);
            $table->unsignedInteger('deferred_actions')->default(0);

            $table->unsignedInteger('high_priority_active_actions')->default(0);
            $table->unsignedInteger('critical_priority_active_actions')->default(0);

            $table->decimal('action_closure_percentage', 6, 2)->default(0);
            $table->decimal('decision_completion_percentage', 6, 2)->default(0);

            $table->string('operational_health')->nullable();
            $table->string('attention_level')->nullable();
            $table->decimal('attention_score', 6, 2)->nullable();

            $table->string('escalation_level')->nullable();
            $table->decimal('escalation_score', 6, 2)->nullable();

            $table->string('bottleneck_level')->nullable();
            $table->decimal('bottleneck_score', 6, 2)->nullable();

            $table->string('efficiency_status')->nullable();
            $table->decimal('efficiency_score', 6, 2)->nullable();

            $table->string('operational_risk_level')->nullable();
            $table->decimal('operational_risk_score', 6, 2)->nullable();

            $table->decimal('strategic_readiness_score', 6, 2)->nullable();
            $table->decimal('governance_capacity_score', 6, 2)->nullable();
            $table->decimal('governance_pressure_score', 6, 2)->nullable();

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);
            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);
            $table->boolean('governance_validation_required')->default(true);

            $table->json('strategic_context')->nullable();

            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            $table->index('operational_snapshot_id');
            $table->index('lifecycle_snapshot_id');
            $table->index('snapshot_scope');
            $table->index('strategic_status');
            $table->index('strategic_readiness');
            $table->index('capacity_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_governance_strategic_snapshots');
    }
};
