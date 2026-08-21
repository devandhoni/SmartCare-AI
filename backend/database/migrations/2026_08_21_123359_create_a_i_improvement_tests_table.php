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
        Schema::create('a_i_improvement_tests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('improvement_review_id');

            $table->string('candidate_code');

            $table->string('candidate_category')
                ->nullable();

            $table->unsignedBigInteger('resident_id')
                ->nullable();

            $table->string('scope_type')
                ->default('FACILITY');

            $table->string('test_status')
                ->default('PLANNED');

            $table->string('test_environment')
                ->default('CONTROLLED');

            $table->text('test_objective')
                ->nullable();

            $table->text('test_hypothesis')
                ->nullable();

            $table->json('baseline_configuration')
                ->nullable();

            $table->json('proposed_configuration')
                ->nullable();

            $table->json('test_metrics')
                ->nullable();

            $table->json('test_results')
                ->nullable();

            $table->boolean('production_change_allowed')
                ->default(false);

            $table->boolean('automatic_deployment_allowed')
                ->default(false);

            $table->boolean('human_validation_required')
                ->default(true);

            $table->boolean('governance_validation_required')
                ->default(true);

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->unsignedBigInteger('validated_by')
                ->nullable();

            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamp('validated_at')
                ->nullable();

            $table->timestamps();

            $table->index('improvement_review_id');
            $table->index('candidate_code');
            $table->index('resident_id');
            $table->index('test_status');

            $table->unique(
                [
                    'improvement_review_id',
                ],
                'ai_improvement_test_review_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_improvement_tests');
    }
};
