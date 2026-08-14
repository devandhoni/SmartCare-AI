<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('nurse_tasks', function (Blueprint $table) {

            $table->json('clinical_action_plan')
                  ->nullable()
                  ->after('description');

        });

    }



    public function down(): void
    {

        Schema::table('nurse_tasks', function (Blueprint $table) {

            $table->dropColumn('clinical_action_plan');

        });

    }

};