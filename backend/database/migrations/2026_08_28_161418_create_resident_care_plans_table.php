<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_care_plans', function (Blueprint $table) {
            $table->id();

            // residents.id is signed bigint(20)
            $table->bigInteger('resident_id')->index();

            $table->string('care_type', 100)->index();
            $table->string('title', 255);
            $table->text('instructions')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scheduling
            |--------------------------------------------------------------------------
            */

            $table->string('frequency', 50)->default('DAILY')->index();
            $table->string('time_slot', 50)->nullable()->index();
            $table->time('scheduled_time')->nullable();

            // JSON array such as ["MON","TUE","WED"]
            $table->json('days_of_week')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Workflow
            |--------------------------------------------------------------------------
            */

            $table->string('priority', 20)->default('NORMAL')->index();
            $table->boolean('is_active')->default(true)->index();

            // users.id is signed bigint(20)
            $table->bigInteger('created_by')->nullable()->index();
            $table->bigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->index(
                ['resident_id', 'is_active'],
                'rcp_resident_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_care_plans');
    }
};