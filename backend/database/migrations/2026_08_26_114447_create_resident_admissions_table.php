<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_admissions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('resident_id');

            /*
            |--------------------------------------------------------------------------
            | Admission Identification
            |--------------------------------------------------------------------------
            */

            $table->string(
                'admission_number',
                100
            )->unique();

            $table->dateTime(
                'admitted_at'
            );

            /*
            |--------------------------------------------------------------------------
            | Admission Information
            |--------------------------------------------------------------------------
            */

            $table->string(
                'admission_type',
                100
            )->default('NEW_ADMISSION');

            $table->string(
                'admission_source',
                150
            )->nullable();

            $table->text(
                'reason_for_admission'
            )->nullable();

            $table->text(
                'medical_summary'
            )->nullable();

            $table->text(
                'mobility_notes'
            )->nullable();

            $table->text(
                'dietary_notes'
            )->nullable();

            $table->text(
                'special_care_instructions'
            )->nullable();

            $table->text(
                'belongings_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Admission Status
            |--------------------------------------------------------------------------
            */

            $table->string(
                'status',
                50
            )->default('DRAFT');

            $table->dateTime(
                'completed_at'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Staff Attribution
            |--------------------------------------------------------------------------
            */

            $table->bigInteger(
                'admitted_by'
            )->nullable();

            $table->bigInteger(
                'completed_by'
            )->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();

            $table->foreign('admitted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('completed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['resident_id', 'status'],
                'ra_resident_status_idx'
            );

            $table->index(
                ['resident_id', 'admitted_at'],
                'ra_resident_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_admissions'
        );
    }
};