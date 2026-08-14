<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {


        Schema::table('ai_alerts', function (Blueprint $table) {


            $table->bigInteger('acknowledged_by')
                ->nullable()
                ->after('resolved_by');


            $table->timestamp('acknowledged_at')
                ->nullable()
                ->after('acknowledged_by');


            $table->timestamp('resolved_at')
                ->nullable()
                ->after('resolved_by');


            $table->text('resolution_note')
                ->nullable()
                ->after('resolved_at');


        });


    }




    public function down(): void
    {


        Schema::table('ai_alerts', function (Blueprint $table) {


            $table->dropColumn([

                'acknowledged_by',

                'acknowledged_at',

                'resolved_at',

                'resolution_note'

            ]);


        });


    }


};