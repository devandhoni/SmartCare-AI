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
        Schema::create('a_i_improvement_executions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Governance Source
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'implementation_review_id'
            );

            $table->unsignedBigInteger(
                'improvement_review_id'
            );

            $table->unsignedBigInteger(
                'improvement_test_id'
            );

            /*
            |--------------------------------------------------------------------------
            | Candidate Identity
            |--------------------------------------------------------------------------
            */

            $table->string(
                'candidate_code'
            );

            $table->string(
                'candidate_category'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Execution Scope
            |--------------------------------------------------------------------------
            */

            $table->string(
                'scope_type'
            )->default('FACILITY');

            $table->unsignedBigInteger(
                'resident_id'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Execution Lifecycle
            |--------------------------------------------------------------------------
            */

            $table->string(
                'execution_status'
            )->default('REGISTERED');

            $table->string(
                'execution_stage'
            )->default('PREPARATION');

            /*
            |--------------------------------------------------------------------------
            | Change Specification
            |--------------------------------------------------------------------------
            */

            $table->string(
                'change_type'
            )->nullable();

            $table->text(
                'change_summary'
            )->nullable();

            $table->json(
                'baseline_configuration'
            )->nullable();

            $table->json(
                'proposed_configuration'
            )->nullable();

            $table->json(
                'execution_scope'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Impact & Safety Intelligence
            |--------------------------------------------------------------------------
            */

            $table->json(
                'impact_analysis'
            )->nullable();

            $table->json(
                'safety_validation'
            )->nullable();

            $table->string(
                'safety_status'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human Execution Authorization
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'execution_review_ready'
            )->default(false);

            $table->boolean(
                'approved_for_execution'
            )->default(false);

            $table->unsignedBigInteger(
                'authorized_by'
            )->nullable();

            $table->timestamp(
                'authorized_at'
            )->nullable();

            $table->text(
                'authorization_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Execution Safety Controls
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | These remain FALSE at registry creation.
            |
            */

            $table->boolean(
                'production_execution_allowed'
            )->default(false);

            $table->boolean(
                'automatic_execution_allowed'
            )->default(false);

            $table->boolean(
                'automatic_deployment_allowed'
            )->default(false);

            $table->boolean(
                'automatic_rollback_allowed'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Governance Requirements
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'human_execution_required'
            )->default(true);

            $table->boolean(
                'pre_execution_validation_required'
            )->default(true);

            $table->boolean(
                'post_execution_validation_required'
            )->default(true);

            $table->boolean(
                'rollback_plan_required'
            )->default(true);

            $table->boolean(
                'governance_validation_required'
            )->default(true);

            /*
            |--------------------------------------------------------------------------
            | Execution / Verification Data
            |--------------------------------------------------------------------------
            */

            $table->json(
                'execution_payload'
            )->nullable();

            $table->json(
                'execution_results'
            )->nullable();

            $table->json(
                'verification_results'
            )->nullable();

            $table->json(
                'rollback_plan'
            )->nullable();

            $table->json(
                'rollback_results'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            $table->timestamp(
                'registered_at'
            )->nullable();

            $table->timestamp(
                'execution_started_at'
            )->nullable();

            $table->timestamp(
                'execution_completed_at'
            )->nullable();

            $table->timestamp(
                'verified_at'
            )->nullable();

            $table->timestamp(
                'rolled_back_at'
            )->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            |
            | Explicit short names avoid MySQL's 64-character identifier limit.
            |
            */

            $table->unique(
                'implementation_review_id',
                'ai_exec_impl_review_unique'
            );

            $table->index(
                'improvement_review_id',
                'ai_exec_review_idx'
            );

            $table->index(
                'improvement_test_id',
                'ai_exec_test_idx'
            );

            $table->index(
                'candidate_code',
                'ai_exec_candidate_idx'
            );

            $table->index(
                'execution_status',
                'ai_exec_status_idx'
            );

            $table->index(
                'resident_id',
                'ai_exec_resident_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'a_i_improvement_executions'
        );
    }
};