<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_contacts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            // Existing residents.id is SIGNED BIGINT.
            $table->bigInteger('resident_id');

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */

            $table->string('full_name', 255);

            $table->string(
                'relationship',
                100
            );

            $table->string(
                'phone',
                50
            );

            $table->string(
                'whatsapp_number',
                50
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Role
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'is_primary'
            )->default(false);

            $table->boolean(
                'is_emergency_contact'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Communication Preferences
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'whatsapp_enabled'
            )->default(true);

            $table->boolean(
                'medication_notifications_enabled'
            )->default(true);

            $table->boolean(
                'care_notifications_enabled'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Additional Information
            |--------------------------------------------------------------------------
            */

            $table->text(
                'notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit Attribution
            |--------------------------------------------------------------------------
            */

            // Existing users.id is SIGNED BIGINT.
            $table->bigInteger(
                'created_by'
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

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['resident_id', 'is_primary'],
                'rc_resident_primary_idx'
            );

            $table->index(
                ['resident_id', 'is_emergency_contact'],
                'rc_resident_emergency_idx'
            );

            $table->index(
                ['resident_id', 'whatsapp_enabled'],
                'rc_resident_whatsapp_idx'
            );

            $table->index(
                ['resident_id', 'medication_notifications_enabled'],
                'rc_resident_med_notify_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_contacts'
        );
    }
};