<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('vital_signs', function (Blueprint $table) {

            $table->index(
                [
                    'resident_id',
                    'recorded_at'
                ],
                'vital_monitoring_index'
            );

        });

    }



    public function down(): void
    {

        Schema::table('vital_signs', function (Blueprint $table) {

            $table->dropIndex(
                'vital_monitoring_index'
            );

        });

    }

};