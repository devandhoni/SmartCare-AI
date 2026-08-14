<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('clinical_recommendations', function (Blueprint $table) {


            $table->id();


            $table->bigInteger('resident_id');


            $table->string('recommendation_type');


            $table->text('recommendation');


            $table->enum(
                'priority',
                [
                    'LOW',
                    'MEDIUM',
                    'HIGH',
                    'URGENT'
                ]
            )
            ->default('LOW');


            $table->timestamp('created_on')
            ->nullable();


            $table->timestamp('updated_on')
            ->nullable();


        });


    }



    public function down(): void
    {

        Schema::dropIfExists('clinical_recommendations');

    }

};