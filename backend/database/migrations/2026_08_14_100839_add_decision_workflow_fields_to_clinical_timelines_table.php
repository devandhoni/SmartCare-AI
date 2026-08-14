<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('clinical_timelines', function (Blueprint $table) {


            $table->enum(
                'decision_status',
                [
                    'PENDING_REVIEW',
                    'ACKNOWLEDGED',
                    'UNDER_MANAGEMENT',
                    'RESOLVED'
                ]
            )
            ->nullable()
            ->after('event_type');



            $table->unsignedBigInteger('reviewed_by')
                ->nullable()
                ->after('decision_status');



            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('reviewed_by');



            $table->text('review_action')
                ->nullable()
                ->after('reviewed_at');


        });

    }



    public function down(): void
    {

        Schema::table('clinical_timelines', function (Blueprint $table) {


            $table->dropColumn([
                'decision_status',
                'reviewed_by',
                'reviewed_at',
                'review_action'
            ]);


        });

    }

};