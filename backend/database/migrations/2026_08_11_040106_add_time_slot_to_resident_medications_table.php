<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::table('resident_medications', function (Blueprint $table) {


            $table->string('time_slot')
                ->default('AM')
                ->after('frequency');


        });

    }



    public function down(): void
    {

        Schema::table('resident_medications', function (Blueprint $table) {


            $table->dropColumn('time_slot');


        });

    }


};