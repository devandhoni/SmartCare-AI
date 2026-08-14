<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('health_predictions', function (Blueprint $table) {


            $table->id();


            $table->bigInteger('resident_id');


            $table->string('prediction_type');


            $table->enum(
                'risk_level',
                [
                    'LOW',
                    'MEDIUM',
                    'HIGH',
                    'CRITICAL'
                ]
            )
            ->default('LOW');


            $table->text('prediction');


            $table->decimal(
                'confidence',
                5,
                2
            )
            ->default(0);


            $table->timestamp(
                'created_on'
            )
            ->nullable();


            $table->timestamp(
                'updated_on'
            )
            ->nullable();


        });

    }



    public function down(): void
    {

        Schema::dropIfExists(
            'health_predictions'
        );

    }

};