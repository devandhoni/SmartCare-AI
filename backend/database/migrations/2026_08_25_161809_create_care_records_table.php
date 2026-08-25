<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_records', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Existing SmartCare IDs are SIGNED BIGINT
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('resident_id');

            $table->string('care_type', 100);

            $table->string('title', 255);

            $table->text('notes')->nullable();

            $table->enum('care_status', [
                'COMPLETED',
                'OBSERVED',
                'NEEDS_ATTENTION',
            ])->default('COMPLETED');

            $table->dateTime('recorded_at');

            $table->bigInteger('recorded_by')->nullable();

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

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'resident_id',
                'recorded_at',
            ]);

            $table->index([
                'resident_id',
                'care_status',
            ]);

            $table->index('care_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_records');
    }
};