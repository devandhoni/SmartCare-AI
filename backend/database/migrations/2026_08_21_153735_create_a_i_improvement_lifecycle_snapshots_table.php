<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_improvement_lifecycle_snapshots', function (Blueprint $table) {
            $table->id();

            $table->string('snapshot_scope', 50)->default('FACILITY');

            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('snapshot_status', 50)->default('CAPTURED');

            $table->unsignedInteger('total_learning_evidence')->default(0);

            $table->unsignedInteger('total_improvement_candidates')->default(0);

            $table->unsignedInteger('total_governance_reviews')->default(0);

            $table->unsignedInteger('total_controlled_tests')->default(0);

            $table->unsignedInteger('total_implementation_reviews')->default(0);

            $table->unsignedInteger('total_controlled_executions')->default(0);

            $table->unsignedInteger('total_monitoring_records')->default(0);

            $table->unsignedInteger('active_monitoring_records')->default(0);

            $table->string('learning_maturity', 80)->nullable();

            $table->string('governance_status', 80)->nullable();

            $table->string('execution_status', 80)->nullable();

            $table->string('monitoring_status', 80)->nullable();

            $table->string('overall_improvement_status', 80)->nullable();

            $table->json('learning_context')->nullable();

            $table->json('governance_context')->nullable();

            $table->json('execution_context')->nullable();

            $table->json('monitoring_context')->nullable();

            $table->json('lifecycle_summary')->nullable();

            $table->json('snapshot_payload')->nullable();

            $table->boolean('automatic_change_allowed')->default(false);

            $table->boolean('automatic_deployment_allowed')->default(false);

            $table->boolean('automatic_rollback_allowed')->default(false);

            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);

            $table->boolean('governance_validation_required')->default(true);

            $table->dateTime('captured_at');

            $table->timestamps();

            $table->index(
                ['snapshot_scope', 'captured_at'],
                'ai_imp_lifecycle_scope_time_idx'
            );

            $table->index(
                ['resident_id', 'captured_at'],
                'ai_imp_lifecycle_resident_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a_i_improvement_lifecycle_snapshots');
    }
};