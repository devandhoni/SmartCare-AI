<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('activity_logs', function (Blueprint $table) {


            $table->id();


            /*
            |--------------------------------------------------------------------------
            | User who performed action
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('user_id')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Related Resident
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('resident_id')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Module Name
            |--------------------------------------------------------------------------
            */

            $table->string('module');



            /*
            |--------------------------------------------------------------------------
            | Action Performed
            |--------------------------------------------------------------------------
            */

            $table->string('action');



            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */

            $table->text('description');



            $table->timestamp('created_on')
                ->nullable();


            $table->timestamp('updated_on')
                ->nullable();


        });


    }



    public function down(): void
    {

        Schema::dropIfExists('activity_logs');

    }


};