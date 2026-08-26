<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_admission_hospitalizations', function (Blueprint $table) {
            $table->id();

            // resident_admissions.id = UNSIGNED BIGINT
            $table->unsignedBigInteger('resident_admission_id');

            // residents.id = SIGNED BIGINT
            $table->bigInteger('resident_id');

            $table->string('hospital_name', 255)->nullable();
            $table->date('hospitalization_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Short FK names
            $table->foreign(
                'resident_admission_id',
                'rah_admission_fk'
            )
                ->references('id')
                ->on('resident_admissions')
                ->cascadeOnDelete();

            $table->foreign(
                'resident_id',
                'rah_resident_fk'
            )
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();

            $table->index(
                [
                    'resident_admission_id',
                    'hospitalization_date',
                ],
                'rah_admission_date_idx'
            );

            $table->index(
                'resident_id',
                'rah_resident_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_admission_hospitalizations'
        );
    }
};