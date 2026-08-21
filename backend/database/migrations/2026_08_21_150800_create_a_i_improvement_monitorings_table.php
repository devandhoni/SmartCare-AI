<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_improvement_monitorings', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source Improvement Lifecycle
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('improvement_execution_id')
                ->index('ai_mon_execution_idx');

            $table->unsignedBigInteger('implementation_review_id')
                ->nullable()
                ->index('ai_mon_impl_review_idx');

            $table->unsignedBigInteger('improvement_review_id')
                ->nullable()
                ->index('ai_mon_review_idx');

            $table->unsignedBigInteger('improvement_test_id')
                ->nullable()
                ->index('ai_mon_test_idx');

            /*
            |--------------------------------------------------------------------------
            | Candidate / Scope Context
            |--------------------------------------------------------------------------
            */

            $table->string('candidate_code', 150);

            $table->string('candidate_category', 100)
                ->nullable();

            $table->string('scope_type', 50)
                ->default('FACILITY');

            $table->unsignedBigInteger('resident_id')
                ->nullable()
                ->index('ai_mon_resident_idx');

            /*
            |--------------------------------------------------------------------------
            | Monitoring Lifecycle
            |--------------------------------------------------------------------------
            */

            $table->string('monitoring_status', 50)
                ->default('REGISTERED');

            $table->string('monitoring_stage', 50)
                ->default('INITIALIZED');

            $table->string('monitoring_mode', 50)
                ->default('LONGITUDINAL_VALIDATION');

            /*
            |--------------------------------------------------------------------------
            | Baseline
            |--------------------------------------------------------------------------
            */

            $table->decimal('baseline_score', 8, 2)
                ->nullable();

            $table->string('baseline_direction', 50)
                ->nullable();

            $table->json('baseline_context')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Monitoring Configuration
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('monitoring_window_days')
                ->default(30);

            $table->unsignedInteger('minimum_observations_required')
                ->default(5);

            $table->decimal('regression_tolerance_percentage', 8, 2)
                ->default(10);

            $table->decimal('safety_threshold_percentage', 8, 2)
                ->default(80);

            /*
            |--------------------------------------------------------------------------
            | Observation / Performance State
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('observation_count')
                ->default(0);

            $table->decimal('latest_observed_score', 8, 2)
                ->nullable();

            $table->decimal('average_observed_score', 8, 2)
                ->nullable();

            $table->decimal('performance_change', 8, 2)
                ->nullable();

            $table->string('performance_direction', 50)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Longitudinal Intelligence
            |--------------------------------------------------------------------------
            */

            $table->string('stability_status', 50)
                ->default('INSUFFICIENT_DATA');

            $table->string('regression_status', 50)
                ->default('NOT_EVALUATED');

            $table->string('safety_monitoring_status', 50)
                ->default('NOT_EVALUATED');

            $table->string('sustainability_status', 50)
                ->default('NOT_EVALUATED');

            /*
            |--------------------------------------------------------------------------
            | Structured Monitoring Data
            |--------------------------------------------------------------------------
            */

            $table->json('monitoring_configuration')
                ->nullable();

            $table->json('observation_summary')
                ->nullable();

            $table->json('longitudinal_analysis')
                ->nullable();

            $table->json('regression_analysis')
                ->nullable();

            $table->json('safety_analysis')
                ->nullable();

            $table->json('sustainability_analysis')
                ->nullable();

            $table->json('monitoring_payload')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Safety / Governance
            |--------------------------------------------------------------------------
            */

            $table->boolean('automatic_change_allowed')
                ->default(false);

            $table->boolean('automatic_rollback_allowed')
                ->default(false);

            $table->boolean('automatic_deployment_allowed')
                ->default(false);

            $table->boolean('automatic_clinical_action_allowed')
                ->default(false);

            $table->boolean('human_review_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamp('monitoring_started_at')
                ->nullable();

            $table->timestamp('last_observed_at')
                ->nullable();

            $table->timestamp('last_analyzed_at')
                ->nullable();

            $table->timestamp('monitoring_completed_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | One active longitudinal monitoring record per execution
            |--------------------------------------------------------------------------
            */

            $table->unique(
                'improvement_execution_id',
                'ai_mon_execution_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'a_i_improvement_monitorings'
        );
    }
};