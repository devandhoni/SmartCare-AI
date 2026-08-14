<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('alert_escalation_logs', function (Blueprint $table) {


            $table->id();



            /*
            AI Alert Reference
            */

            $table->bigInteger('alert_id');



            /*
            Assigned User
            */

            $table->bigInteger('assigned_to')
                ->nullable();



            /*
            Escalation Details
            */

            $table->string('status')
                ->default('ESCALATED');


            $table->timestamp('acknowledged_at')
                ->nullable();


            $table->timestamp('resolved_at')
                ->nullable();


            $table->timestamps();



            /*
            Relationships
            */


            $table->foreign('alert_id')
                ->references('id')
                ->on('ai_alerts')
                ->cascadeOnDelete();



            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();



        });

    }




    public function down(): void
    {

        Schema::dropIfExists(
            'alert_escalation_logs'
        );

    }


};