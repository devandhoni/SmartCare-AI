<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_strategic_plan_final_governance_decisions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source Governance References
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('strategic_plan_decision_validation_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_human_decision_id')->nullable();
            $table->unsignedBigInteger('strategic_plan_decision_id')->nullable();

            $table->unsignedBigInteger('strategic_plan_id')->nullable();
            $table->unsignedBigInteger('strategic_snapshot_id')->nullable();
            $table->unsignedBigInteger('operational_snapshot_id')->nullable();
            $table->unsignedBigInteger('lifecycle_snapshot_id')->nullable();

            $table->string('decision_scope', 100)->nullable();
            $table->unsignedBigInteger('resident_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Final Governance Decision Identity
            |--------------------------------------------------------------------------
            */

            $table->string('final_governance_decision_code', 150);

            $table->string(
                'final_governance_decision_status',
                100
            )->default('PENDING_AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION');

            $table->string(
                'final_governance_decision_mode',
                100
            )->default('AUTHORIZED_HUMAN_FINAL_GOVERNANCE_DECISION');

            /*
            |--------------------------------------------------------------------------
            | Upstream Decision / Validation Context
            |--------------------------------------------------------------------------
            */

            $table->string('prepared_decision', 150)->nullable();

            $table->string('source_human_decision', 150)->nullable();

            $table->boolean('source_final_human_decision_recorded')
                ->default(false);

            $table->boolean('source_decision_made_by_authorized_human')
                ->default(false);

            $table->string('source_governance_validation_decision', 150)->nullable();

            $table->boolean('source_governance_validation_completed')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Final Governance Outcome
            |--------------------------------------------------------------------------
            */

            $table->string('final_governance_decision', 150)->nullable();

            $table->text('final_governance_decision_rationale')->nullable();

            $table->longText('final_governance_decision_notes')->nullable();

            $table->string('final_governance_outcome', 150)->nullable();

            $table->string('final_governance_outcome_status', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Decision Readiness / Risk
            |--------------------------------------------------------------------------
            */

            $table->string('final_decision_readiness', 150)->nullable();

            $table->decimal(
                'final_decision_readiness_score',
                8,
                2
            )->default(0);

            $table->string('final_decision_risk_level', 150)->nullable();

            $table->decimal(
                'final_decision_risk_score',
                8,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Resolution / Validation State
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'condition_resolution_score',
                8,
                2
            )->default(0);

            $table->decimal(
                'evidence_resolution_score',
                8,
                2
            )->default(0);

            $table->decimal(
                'combined_resolution_score',
                8,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Structured Governance Context
            |--------------------------------------------------------------------------
            */

            $table->json('decision_conditions')->nullable();

            $table->json('validated_evidence')->nullable();

            $table->json('decision_findings')->nullable();

            $table->json('decision_restrictions')->nullable();

            $table->json('review_context')->nullable();

            $table->json('validation_context')->nullable();

            $table->json('governance_context')->nullable();

            $table->json('source_context')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Authorized Human Decision Attribution
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('decided_by')->nullable();

            $table->string('decider_role', 150)->nullable();

            $table->timestamp('decided_at')->nullable();

            $table->boolean('decision_made_by_authorized_human')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Final Governance Confirmation
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('confirmed_by')->nullable();

            $table->string('confirmer_role', 150)->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->boolean('final_governance_confirmation_completed')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Authority Isolation
            |--------------------------------------------------------------------------
            */

            $table->boolean('automatic_final_decision_allowed')
                ->default(false);

            $table->boolean('automatic_approval_allowed')
                ->default(false);

            $table->boolean('automatic_rejection_allowed')
                ->default(false);

            $table->boolean('automatic_conditional_approval_allowed')
                ->default(false);

            $table->boolean('automatic_deferral_allowed')
                ->default(false);

            $table->boolean('automatic_risk_acceptance_allowed')
                ->default(false);

            $table->boolean('automatic_activation_allowed')
                ->default(false);

            $table->boolean('automatic_condition_resolution_allowed')
                ->default(false);

            $table->boolean('automatic_evidence_validation_allowed')
                ->default(false);

            $table->boolean('automatic_execution_allowed')
                ->default(false);

            $table->boolean('automatic_change_allowed')
                ->default(false);

            $table->boolean('automatic_deployment_allowed')
                ->default(false);

            $table->boolean('automatic_rollback_allowed')
                ->default(false);

            $table->boolean('automatic_clinical_action_allowed')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Required Human Governance
            |--------------------------------------------------------------------------
            */

            $table->boolean('human_review_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            $table->boolean('authorized_human_final_decision_required')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            |
            | Explicit short names prevent MySQL's 64-character identifier
            | limitation that we encountered earlier.
            |
            */

            $table->unique(
                'final_governance_decision_code',
                'gov_final_decision_code_uq'
            );

            $table->index(
                'strategic_plan_decision_validation_id',
                'gov_final_validation_idx'
            );

            $table->index(
                'strategic_plan_human_decision_id',
                'gov_final_human_decision_idx'
            );

            $table->index(
                'strategic_plan_decision_id',
                'gov_final_source_decision_idx'
            );

            $table->index(
                'strategic_plan_id',
                'gov_final_plan_idx'
            );

            $table->index(
                'resident_id',
                'gov_final_resident_idx'
            );

            $table->index(
                'final_governance_decision_status',
                'gov_final_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_governance_strategic_plan_final_governance_decisions'
        );
    }
};