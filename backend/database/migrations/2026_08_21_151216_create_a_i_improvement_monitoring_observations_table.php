<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_improvement_monitoring_observations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('monitoring_id')->index('ai_mon_obs_monitoring_idx');

            $table->unsignedBigInteger('improvement_execution_id')->index('ai_mon_obs_execution_idx');

            $table->string('candidate_code', 150);

            $table->string('observation_type', 80)->default('PERFORMANCE');

            $table->decimal('observed_score', 8, 2);

            $table->decimal('baseline_score', 8, 2)->nullable();

            $table->decimal('absolute_change', 8, 2)->nullable();

            $table->decimal('percentage_change', 8, 2)->nullable();

            $table->string('performance_direction', 50)->nullable();

            $table->boolean('safety_passed')->default(true);

            $table->string('outcome_status', 80)->nullable();

            $table->string('evidence_quality', 50)->default('LIMITED');

            $table->json('observation_context')->nullable();

            $table->boolean('human_reviewed')->default(false);

            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->dateTime('reviewed_at')->nullable();

            $table->dateTime('observed_at');

            $table->timestamps();

            $table->index(
                ['monitoring_id', 'observed_at'],
                'ai_mon_obs_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'a_i_improvement_monitoring_observations'
        );
    }
};