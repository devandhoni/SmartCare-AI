<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_admission_medical_histories', function (Blueprint $table) {
            $table->id();

            // resident_admissions.id = UNSIGNED BIGINT
            $table->unsignedBigInteger('resident_admission_id');

            // residents.id = SIGNED BIGINT
            $table->bigInteger('resident_id');

            // Diagnoses
            $table->text('primary_psychiatric_diagnosis')->nullable();
            $table->text('other_diagnoses')->nullable();

            // Substance use
            $table->boolean('tobacco_use')->default(false);
            $table->string('tobacco_amount_frequency', 255)->nullable();

            $table->boolean('alcohol_use')->default(false);
            $table->string('alcohol_amount_frequency', 255)->nullable();

            $table->boolean('drug_use')->default(false);
            $table->string('drug_type_frequency', 255)->nullable();

            // Family history
            $table->boolean('family_psychiatric_history')->default(false);
            $table->text('family_psychiatric_history_details')->nullable();

            $table->boolean('family_substance_abuse_history')->default(false);
            $table->text('family_substance_abuse_history_details')->nullable();

            // Initial assessment
            $table->text('initial_observations')->nullable();
            $table->text('additional_comments')->nullable();

            $table->timestamps();

            // Short FK names to stay below MySQL's identifier limit
            $table->foreign(
                'resident_admission_id',
                'ramh_admission_fk'
            )
                ->references('id')
                ->on('resident_admissions')
                ->cascadeOnDelete();

            $table->foreign(
                'resident_id',
                'ramh_resident_fk'
            )
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();

            $table->unique(
                'resident_admission_id',
                'ramh_admission_unique'
            );

            $table->index(
                'resident_id',
                'ramh_resident_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_admission_medical_histories'
        );
    }
};