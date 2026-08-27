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
        Schema::create('resident_visitors', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            // residents.id in SmartCare-AI is signed BIGINT.
            $table->bigInteger('resident_id');

            /*
            |--------------------------------------------------------------------------
            | Visit Identification
            |--------------------------------------------------------------------------
            */

            $table->string('visit_reference', 100)->unique();

            /*
            |--------------------------------------------------------------------------
            | Visitor Information
            |--------------------------------------------------------------------------
            */

            $table->string('visitor_name', 255);

            $table->string(
                'relationship',
                100
            )->nullable();

            $table->string(
                'contact_number',
                50
            )->nullable();

            $table->string(
                'id_number',
                100
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Visit Information
            |--------------------------------------------------------------------------
            */

            $table->string(
                'purpose_of_visit',
                500
            )->nullable();

            $table->unsignedInteger(
                'number_of_visitors'
            )->default(1);

            $table->text(
                'visit_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Check-In
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'checked_in_at'
            );

            // users.id in SmartCare-AI is signed BIGINT.
            $table->bigInteger(
                'checked_in_by'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Visit Status
            |--------------------------------------------------------------------------
            */

            $table->string(
                'status',
                50
            )->default('CHECKED_IN');

            /*
            |--------------------------------------------------------------------------
            | Check-Out
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'checked_out_at'
            )->nullable();

            $table->bigInteger(
                'checked_out_by'
            )->nullable();

            $table->text(
                'checkout_notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
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

            $table->foreign('checked_in_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('checked_out_by')
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
                'rv_resident_status_idx'
            );

            $table->index(
                ['resident_id', 'checked_in_at'],
                'rv_resident_checkin_idx'
            );

            $table->index(
                'checked_in_at',
                'rv_checked_in_at_idx'
            );

            $table->index(
                'checked_out_at',
                'rv_checked_out_at_idx'
            );

            $table->index(
                'visitor_name',
                'rv_visitor_name_idx'
            );

            $table->index(
                'contact_number',
                'rv_contact_number_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_visitors');
    }
};