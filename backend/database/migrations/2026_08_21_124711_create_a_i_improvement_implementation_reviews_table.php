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
        Schema::create('a_i_improvement_implementation_reviews', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source Governance Records
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('improvement_review_id');

            $table->unsignedBigInteger('improvement_test_id');

            /*
            |--------------------------------------------------------------------------
            | Candidate Identity
            |--------------------------------------------------------------------------
            */

            $table->string('candidate_code');

            $table->string('candidate_category')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scope
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('resident_id')
                ->nullable();

            $table->string('scope_type')
                ->default('FACILITY');

            /*
            |--------------------------------------------------------------------------
            | Implementation Readiness
            |--------------------------------------------------------------------------
            */

            $table->string('readiness_status');

            $table->boolean('implementation_review_ready')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Human Governance Review
            |--------------------------------------------------------------------------
            */

            $table->string('review_status')
                ->default('PENDING');

            $table->string('review_decision')
                ->nullable();

            $table->text('review_notes')
                ->nullable();

            $table->unsignedBigInteger('reviewed_by')
                ->nullable();

            $table->timestamp('reviewed_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Implementation Approval
            |--------------------------------------------------------------------------
            */

            $table->boolean('approved_for_implementation')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Production Safety Guardrails
            |--------------------------------------------------------------------------
            */

            $table->boolean('production_change_allowed')
                ->default(false);

            $table->boolean('automatic_deployment_allowed')
                ->default(false);

            $table->boolean('automatic_change_allowed')
                ->default(false);

            $table->boolean('human_approval_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Evidence Payloads
            |--------------------------------------------------------------------------
            */

            $table->json('readiness_payload')
                ->nullable();

            $table->json('decision_payload')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Submission
            |--------------------------------------------------------------------------
            */

            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            |
            | Explicit short names are used because MySQL limits identifiers
            | to 64 characters.
            |
            */

            $table->unique(
                'improvement_test_id',
                'ai_impl_review_test_unique'
            );

            $table->index(
                'improvement_review_id',
                'ai_impl_review_idx'
            );

            $table->index(
                'candidate_code',
                'ai_impl_candidate_idx'
            );

            $table->index(
                'resident_id',
                'ai_impl_resident_idx'
            );

            $table->index(
                'review_status',
                'ai_impl_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'a_i_improvement_implementation_reviews'
        );
    }
};