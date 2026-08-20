<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('ai_clinical_outcomes', function (Blueprint $table) {


            $table->id();



            /*
            |--------------------------------------------------------------------------
            | Relationship
            |--------------------------------------------------------------------------
            */


            $table->bigInteger(
                'resident_id'
            );


            $table->bigInteger(
                'nurse_task_id'
            )
            ->nullable();



            $table->bigInteger(
                'prediction_id'
            )
            ->nullable();




            /*
            |--------------------------------------------------------------------------
            | AI Prediction Snapshot
            |--------------------------------------------------------------------------
            */


            $table->string(
                'initial_risk_level'
            )
            ->nullable();



            $table->decimal(
                'initial_confidence',
                5,
                2
            )
            ->default(0);





            /*
            |--------------------------------------------------------------------------
            | Clinical Outcome
            |--------------------------------------------------------------------------
            */


            $table->enum(

                'outcome_status',

                [

                    'IMPROVED',

                    'STABLE',

                    'DETERIORATED',

                    'UNKNOWN'

                ]

            )
            ->default(
                'UNKNOWN'
            );




            $table->text(
                'outcome_notes'
            )
            ->nullable();





            /*
            |--------------------------------------------------------------------------
            | AI Evaluation
            |--------------------------------------------------------------------------
            */


            $table->decimal(

                'ai_accuracy_score',

                5,

                2

            )
            ->nullable();



            $table->bigInteger(
                'recorded_by'
            )
            ->nullable();



            $table->timestamp(
                'recorded_at'
            )
            ->nullable();



            $table->timestamps();



        });


    }






    public function down(): void
    {

        Schema::dropIfExists(
            'ai_clinical_outcomes'
        );


    }


};