<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nurse_tasks', function (Blueprint $table) {
            // resident_care_plans.id is unsigned bigint(20)
            $table->unsignedBigInteger('care_plan_id')
                ->nullable()
                ->after('resident_id')
                ->index();

            $table->string('occurrence_key', 100)
                ->nullable()
                ->after('care_plan_id');

            $table->unique(
                ['care_plan_id', 'occurrence_key'],
                'nt_care_plan_occurrence_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('nurse_tasks', function (Blueprint $table) {
            $table->dropUnique('nt_care_plan_occurrence_uq');
            $table->dropIndex(['care_plan_id']);

            $table->dropColumn([
                'care_plan_id',
                'occurrence_key',
            ]);
        });
    }
};