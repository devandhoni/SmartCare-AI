<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up()
    {

        Schema::create('health_risk_scores', function (Blueprint $table) {


            $table->id();


            /*
            Existing SmartCare residents table
            uses bigint(20) without Laravel foreignId
            */

            $table->bigInteger('resident_id');



            $table->integer('risk_score')
            ->default(0);



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



            $table->text('reason')
            ->nullable();



            $table->timestamp('created_on')
            ->nullable();



            $table->timestamp('updated_on')
            ->nullable();



        });

    }



    public function down()
    {

        Schema::dropIfExists('health_risk_scores');

    }

};