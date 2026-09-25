<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_discharges', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident / Room
            |--------------------------------------------------------------------------
            */

            // residents.id = SIGNED BIGINT
            $table->bigInteger('resident_id');

            // Snapshot of the room occupied at discharge.
            // rooms.id = SIGNED BIGINT
            $table->bigInteger('room_id_at_discharge')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Discharge Reference
            |--------------------------------------------------------------------------
            */

            $table->string(
                'discharge_number',
                100
            )->unique();

            /*
            |--------------------------------------------------------------------------
            | Discharge Details
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'discharged_at'
            )->nullable();

            $table->string(
                'discharge_type',
                100
            )->nullable();

            $table->string(
                'discharge_destination',
                255
            )->nullable();

            $table->text(
                'reason_for_discharge'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Clinical Summary
            |--------------------------------------------------------------------------
            */

            $table->text(
                'condition_at_discharge'
            )->nullable();

            $table->text(
                'treatment_care_summary'
            )->nullable();

            $table->text(
                'medical_summary'
            )->nullable();

            $table->text(
                'follow_up_instructions'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Medication Summary
            |--------------------------------------------------------------------------
            */

            $table->text(
                'medication_summary'
            )->nullable();

            $table->text(
                'medication_instructions'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Handover
            |--------------------------------------------------------------------------
            */

            $table->string(
                'discharged_to',
                255
            )->nullable();

            $table->string(
                'discharged_to_relationship',
                100
            )->nullable();

            $table->string(
                'discharged_to_contact',
                50
            )->nullable();

            $table->boolean(
                'belongings_returned'
            )->default(false);

            $table->text(
                'belongings_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Administrative
            |--------------------------------------------------------------------------
            */

            $table->text(
                'administrative_notes'
            )->nullable();

            $table->string(
                'status',
                50
            )->default('DRAFT');

            // users.id = SIGNED BIGINT
            $table->bigInteger(
                'prepared_by'
            )->nullable();

            $table->bigInteger(
                'completed_by'
            )->nullable();

            $table->dateTime(
                'completed_at'
            )->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign(
                'resident_id',
                'rd_resident_fk'
            )
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();

            $table->foreign(
                'room_id_at_discharge',
                'rd_room_fk'
            )
                ->references('id')
                ->on('rooms')
                ->nullOnDelete();

            $table->foreign(
                'prepared_by',
                'rd_prepared_fk'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign(
                'completed_by',
                'rd_completed_fk'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'resident_id',
                    'status',
                ],
                'resident_discharges_resident_status_idx'
            );

            $table->index(
                'discharged_at',
                'rd_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_discharges'
        );
    }
};