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
        Schema::create('a_i_improvement_reviews', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Candidate Identity
            |--------------------------------------------------------------------------
            */

            $table->string('candidate_code');

            $table->string('candidate_category')
                ->nullable();

            $table->string('candidate_title')
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
            | Learning Context
            |--------------------------------------------------------------------------
            */

            $table->string('learning_maturity')
                ->nullable();

            $table->string('pattern_confidence')
                ->nullable();

            $table->unsignedInteger('evidence_count')
                ->default(0);

            $table->unsignedInteger('minimum_evidence_required')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Candidate Safety Context
            |--------------------------------------------------------------------------
            */

            $table->string('safety_status')
                ->nullable();

            $table->string('eligibility_status')
                ->nullable();

            $table->boolean('implementation_review_ready')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Governance Review
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
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->boolean('approved_for_testing')
                ->default(false);

            $table->boolean('approved_for_implementation')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Governance Controls
            |--------------------------------------------------------------------------
            */

            $table->boolean('automatic_change_allowed')
                ->default(false);

            $table->boolean('human_approval_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Full Candidate Snapshot
            |--------------------------------------------------------------------------
            */

            $table->json('candidate_payload')
                ->nullable();

            $table->json('safety_payload')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit Times
            |--------------------------------------------------------------------------
            */

            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('candidate_code');
            $table->index('resident_id');
            $table->index('review_status');
            $table->index('review_decision');
            $table->index('eligibility_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_improvement_reviews');
    }
};
