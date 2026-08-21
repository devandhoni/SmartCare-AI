<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_governance_action_reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('governance_action_id');
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('review_decision', 50);
            $table->string('review_status', 50)->default('COMPLETED');

            $table->nullableMorphs('reviewer');

            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_role')->nullable();

            $table->text('decision_rationale')->nullable();
            $table->json('conditions')->nullable();
            $table->json('review_context')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->foreign('governance_action_id', 'ai_gov_review_action_fk')
                ->references('id')
                ->on('a_i_governance_actions')
                ->cascadeOnDelete();

            $table->foreign('lifecycle_snapshot_id', 'ai_gov_review_snapshot_fk')
                ->references('id')
                ->on('a_i_improvement_lifecycle_snapshots')
                ->nullOnDelete();

            $table->index(
                ['governance_action_id', 'review_decision'],
                'ai_gov_review_action_decision_idx'
            );

            $table->index(
                ['lifecycle_snapshot_id', 'review_status'],
                'ai_gov_review_snapshot_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a_i_governance_action_reviews');
    }
};