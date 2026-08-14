<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {


        Schema::table('nurse_tasks', function (Blueprint $table) {


            $table->unsignedBigInteger('source_alert_id')
                ->nullable()
                ->after('resident_id');


            $table->boolean('ai_generated')
                ->default(false)
                ->after('source_alert_id');


            $table->unsignedBigInteger('acknowledged_by')
                ->nullable()
                ->after('status');


            $table->timestamp('acknowledged_at')
                ->nullable()
                ->after('acknowledged_by');


        });


    }




    public function down(): void
    {


        Schema::table('nurse_tasks', function (Blueprint $table) {


            $table->dropColumn([

                'source_alert_id',

                'ai_generated',

                'acknowledged_by',

                'acknowledged_at'

            ]);


        });


    }


};