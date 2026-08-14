<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::table('resident_medications', function (Blueprint $table) {


            $table->integer('dosage_quantity')
                ->default(1)
                ->after('dosage_instruction');


        });

    }



    public function down(): void
    {

        Schema::table('resident_medications', function (Blueprint $table) {


            $table->dropColumn('dosage_quantity');


        });

    }


};