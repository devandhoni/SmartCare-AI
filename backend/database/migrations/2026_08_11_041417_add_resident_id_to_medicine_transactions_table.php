<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('medicine_transactions', function (Blueprint $table) {


            $table->bigInteger('resident_id')
                ->nullable()
                ->after('medication_id');


        });

    }



    public function down(): void
    {

        Schema::table('medicine_transactions', function (Blueprint $table) {


            $table->dropColumn('resident_id');


        });

    }

};