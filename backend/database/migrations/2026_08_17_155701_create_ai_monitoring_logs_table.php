<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('ai_monitoring_logs', function (Blueprint $table) {


            $table->id();


            /*
            |--------------------------------------------------------------------------
            | Resident Reference
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('resident_id');


            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();



            /*
            |--------------------------------------------------------------------------
            | AI Decision Snapshot
            |--------------------------------------------------------------------------
            */

            $table->integer('decision_score');


            $table->string('priority');


            /*
            |--------------------------------------------------------------------------
            | Previous AI State
            |--------------------------------------------------------------------------
            */

            $table->string('previous_priority')
                ->nullable();


            $table->integer('previous_score')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | AI Trend Analysis
            |--------------------------------------------------------------------------
            */

            $table->string('trend')
                ->nullable();


            $table->text('summary')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Source Vital Sign
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('vital_sign_id')
                ->nullable();


            $table->foreign('vital_sign_id')
                ->references('id')
                ->on('vital_signs')
                ->nullOnDelete();



            $table->timestamps();


        });

    }



    public function down(): void
    {

        Schema::dropIfExists('ai_monitoring_logs');

    }

};