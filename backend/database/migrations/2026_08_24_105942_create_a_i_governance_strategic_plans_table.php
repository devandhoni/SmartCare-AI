<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plans', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('plan_scope')->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('plan_code')->unique();
            $table->string('plan_status')->default('DRAFT');
            $table->string('planning_mode')->default('HUMAN_GOVERNED_STRATEGIC_PLANNING');

            $table->string('strategic_priority')->nullable();
            $table->decimal('strategic_priority_score', 8, 2)->nullable();

            $table->string('planning_readiness')->nullable();
            $table->decimal('planning_readiness_score', 8, 2)->nullable();

            $table->string('risk_level')->nullable();
            $table->decimal('risk_score', 8, 2)->nullable();

            $table->json('objectives')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('resource_context')->nullable();
            $table->json('planning_context')->nullable();
            $table->json('source_context')->nullable();

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);
            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->boolean('human_review_required')->default(true);
            $table->boolean('governance_validation_required')->default(true);

            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->index('strategic_snapshot_id');
            $table->index('operational_snapshot_id');
            $table->index('lifecycle_snapshot_id');
            $table->index('resident_id');
            $table->index('plan_status');
            $table->index('strategic_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_governance_strategic_plans');
    }
};