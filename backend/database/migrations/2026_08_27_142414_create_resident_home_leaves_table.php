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
        Schema::create('resident_home_leaves', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            // Existing residents.id uses signed BIGINT in this project.
            $table->bigInteger('resident_id');


            /*
            |--------------------------------------------------------------------------
            | Home Leave Identification
            |--------------------------------------------------------------------------
            */

            $table->string('leave_reference', 100)->unique();


            /*
            |--------------------------------------------------------------------------
            | Departure Information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('leave_at');

            $table->string('reason_for_leave', 500)->nullable();

            $table->string('destination', 500)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Person Taking Resident
            |--------------------------------------------------------------------------
            */

            $table->string('taken_by_name', 255);

            $table->string('taken_by_relationship', 100)->nullable();

            $table->string('taken_by_contact', 50)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Expected Return
            |--------------------------------------------------------------------------
            */

            $table->dateTime('expected_return_at');


            /*
            |--------------------------------------------------------------------------
            | Medication / Belongings
            |--------------------------------------------------------------------------
            */

            $table->boolean('medication_handed_over')
                ->default(false);

            $table->text('medication_instructions')->nullable();

            $table->boolean('belongings_taken')
                ->default(false);

            $table->text('belongings_notes')->nullable();

            $table->text('leave_notes')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Leave Status
            |--------------------------------------------------------------------------
            */

            $table->string('status', 50)
                ->default('ON_LEAVE');


            /*
            |--------------------------------------------------------------------------
            | Return Information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('returned_at')->nullable();

            $table->string('returned_by_name', 255)->nullable();

            $table->string('returned_by_relationship', 100)->nullable();

            $table->string('returned_by_contact', 50)->nullable();

            $table->string('condition_on_return', 255)->nullable();

            $table->text('return_notes')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Staff Audit
            |--------------------------------------------------------------------------
            */

            // Existing users.id uses signed BIGINT in this project.
            $table->bigInteger('recorded_by')->nullable();

            $table->bigInteger('return_recorded_by')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Laravel Audit
            |--------------------------------------------------------------------------
            */

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

            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('return_recorded_by')
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
                'rhl_resident_status_idx'
            );

            $table->index(
                ['resident_id', 'leave_at'],
                'rhl_resident_leave_idx'
            );

            $table->index(
                'expected_return_at',
                'rhl_expected_return_idx'
            );

            $table->index(
                'leave_at',
                'rhl_leave_at_idx'
            );

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_home_leaves');
    }
};