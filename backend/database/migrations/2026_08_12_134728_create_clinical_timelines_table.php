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

        Schema::create('clinical_timelines', function (Blueprint $table) {


            $table->id();



            /*
            |--------------------------------------------------------------------------
            | Resident Reference
            |--------------------------------------------------------------------------
            */


            $table->unsignedBigInteger('resident_id');



            /*
            |--------------------------------------------------------------------------
            | Event Classification
            |--------------------------------------------------------------------------
            */


            $table->string('event_type',100);


            /*
            
            Examples:

            VITAL
            AI_ALERT
            ESCALATION
            ACKNOWLEDGEMENT
            MEDICATION
            RESOLUTION
            CLINICAL_DECISION
            HISTORICAL_RECORD

            */



            $table->string('event_title');



            $table->text('event_description')
                  ->nullable();





            /*
            |--------------------------------------------------------------------------
            | Source Reference
            |--------------------------------------------------------------------------
            */


            $table->string('source_type',100)
                  ->nullable();


            /*
            
            Examples:

            VitalSign
            AiAlert
            NurseTask
            MedicationRecord

            */



            $table->unsignedBigInteger('source_id')
                  ->nullable();






            /*
            |--------------------------------------------------------------------------
            | Event Time
            |--------------------------------------------------------------------------
            */


            $table->timestamp('event_date');





            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */


            $table->timestamps();



            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */


            $table->index('resident_id');

            $table->index('event_type');

            $table->index('event_date');



        });


    }





    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('clinical_timelines');

    }


};