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
        Schema::create('resident_parcels', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Resident
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('resident_id');

            /*
            |--------------------------------------------------------------------------
            | Parcel Identification
            |--------------------------------------------------------------------------
            */

            $table->string('parcel_reference', 100)->unique();

            $table->string('sender_name', 255)->nullable();

            $table->string('courier_name', 150)->nullable();

            $table->string('tracking_number', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Parcel Details
            |--------------------------------------------------------------------------
            */

            $table->string('parcel_description', 500)->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->string('condition_on_arrival', 50)->default('GOOD');

            /*
            |--------------------------------------------------------------------------
            | Receiving Information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('received_at');

            $table->bigInteger('received_by')->nullable();

            $table->boolean('parcel_checked')->default(false);

            $table->boolean('resident_notified')->default(false);

            $table->text('receiving_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Collection Information
            |--------------------------------------------------------------------------
            */

            $table->string('status', 50)->default('RECEIVED');

            $table->dateTime('collected_at')->nullable();

            $table->string('collected_by_name', 255)->nullable();

            $table->string('collected_by_relationship', 100)->nullable();

            $table->string('collected_by_contact', 50)->nullable();

            $table->bigInteger('collection_recorded_by')->nullable();

            $table->text('collection_notes')->nullable();

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

            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('collection_recorded_by')
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
                'rp_resident_status_idx'
            );

            $table->index(
                ['resident_id', 'received_at'],
                'rp_resident_received_idx'
            );

            $table->index(
                'tracking_number',
                'rp_tracking_number_idx'
            );

            $table->index(
                'received_at',
                'rp_received_at_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_parcels');
    }
};