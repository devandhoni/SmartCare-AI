<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('medication_administration_records', function (Blueprint $table) {


            $table->id();



            /*
            |--------------------------------------------------
            | Resident Reference
            |--------------------------------------------------
            */

            $table->bigInteger('resident_id')
                ->nullable();



            /*
            |--------------------------------------------------
            | Assigned Medication
            |--------------------------------------------------
            */

            $table->bigInteger('resident_medication_id')
                ->nullable();



            /*
            AM
            PM
            NIGHT
            OTHER
            */

            $table->string('time_slot');



            $table->time('scheduled_time')
                ->nullable();



            /*
            Actual completion time
            */

            $table->timestamp('completed_time')
                ->nullable();



            /*
            Nurse who completed medication
            */

            $table->bigInteger('completed_by')
                ->nullable();



            /*
            PENDING
            COMPLETED
            MISSED
            */

            $table->string('status')
                ->default('PENDING');



            $table->text('remarks')
                ->nullable();



            $table->timestamps();



            /*
            Foreign Keys
            */

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();



            $table->foreign('resident_medication_id')
                ->references('id')
                ->on('resident_medications')
                ->nullOnDelete();



            $table->foreign('completed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();



        });

    }




    public function down(): void
    {

        Schema::dropIfExists(
            'medication_administration_records'
        );

    }


};