<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_learning_evidence', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Evidence Identity
            |--------------------------------------------------------------------------
            */

            $table->string('evidence_type', 50);
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Resident Context
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('resident_id')->nullable();
            $table->string('resident_status', 50)->nullable();
            $table->boolean('active_care_eligible')->default(false);

            /*
            |--------------------------------------------------------------------------
            | AI Decision Context
            |--------------------------------------------------------------------------
            */

            $table->string('ai_domain', 50);
            $table->string('ai_decision', 255)->nullable();

            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->decimal('ai_risk_score', 5, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Human / Workflow Evidence
            |--------------------------------------------------------------------------
            */

            $table->string('human_review_status', 50)
                ->default('NOT_REVIEWED');

            $table->boolean('human_agreement')->nullable();

            $table->string('workflow_status', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Outcome Evidence
            |--------------------------------------------------------------------------
            */

            $table->string('outcome_status', 50)->nullable();

            $table->decimal('accuracy_score', 5, 2)->nullable();
            $table->decimal('effectiveness_score', 5, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Evidence Quality
            |--------------------------------------------------------------------------
            */

            $table->string('evidence_quality', 50)
                ->default('LIMITED');

            $table->string('learning_status', 50)
                ->default('PENDING');

            /*
            |--------------------------------------------------------------------------
            | Audit Payload
            |--------------------------------------------------------------------------
            */

            $table->json('evidence_payload')->nullable();

            $table->timestamp('observed_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('evidence_type');
            $table->index('ai_domain');
            $table->index('resident_id');
            $table->index('learning_status');
            $table->index('observed_at');

            $table->index([
                'ai_domain',
                'learning_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_learning_evidence');
    }
};