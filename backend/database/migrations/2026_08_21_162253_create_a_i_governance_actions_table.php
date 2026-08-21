<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_governance_actions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('action_code', 150);
            $table->string('action_category', 100);

            $table->string('action_title', 255);
            $table->text('action_description')->nullable();

            $table->string('scope_type', 50)->default('FACILITY');
            $table->unsignedBigInteger('resident_id')->nullable();

            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('source_status', 100)->nullable();

            $table->string('priority_level', 50)->default('ADVISORY');

            $table->unsignedInteger('priority_score')->default(0);

            $table->string('action_status', 80)->default('OPEN');

            $table->string('eligibility_status', 80)->default('PENDING_VALIDATION');

            $table->boolean('human_review_required')->default(true);
            $table->boolean('governance_validation_required')->default(true);

            $table->boolean('automatic_execution_allowed')->default(false);
            $table->boolean('automatic_change_allowed')->default(false);
            $table->boolean('automatic_deployment_allowed')->default(false);
            $table->boolean('automatic_rollback_allowed')->default(false);
            $table->boolean('automatic_clinical_action_allowed')->default(false);

            $table->json('source_context')->nullable();
            $table->json('priority_context')->nullable();
            $table->json('eligibility_context')->nullable();
            $table->json('review_context')->nullable();
            $table->json('resolution_context')->nullable();
            $table->json('action_payload')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->string('review_decision', 80)->nullable();
            $table->text('review_notes')->nullable();

            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();

            $table->dateTime('generated_at');

            $table->timestamps();

            $table->index(
                ['action_status', 'priority_level'],
                'ai_gov_action_status_priority_idx'
            );

            $table->index(
                ['scope_type', 'resident_id'],
                'ai_gov_action_scope_resident_idx'
            );

            $table->index(
                ['source_type', 'source_id'],
                'ai_gov_action_source_idx'
            );

            $table->index(
                'lifecycle_snapshot_id',
                'ai_gov_action_snapshot_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a_i_governance_actions');
    }
};