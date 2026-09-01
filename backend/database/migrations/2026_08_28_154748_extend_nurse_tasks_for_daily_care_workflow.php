<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nurse_tasks', function (Blueprint $table) {

            $table->string('task_type', 50)
                ->default('GENERAL')
                ->after('ai_generated')
                ->index();

            $table->string('source_type', 50)
                ->default('MANUAL')
                ->after('task_type')
                ->index();

            $table->text('completion_notes')
                ->nullable()
                ->after('completed_time');

            // users.id is signed bigint(20)
            $table->bigInteger('completed_by')
                ->nullable()
                ->after('completion_notes')
                ->index();

            // care_records.id is unsigned bigint(20)
            $table->unsignedBigInteger('care_record_id')
                ->nullable()
                ->after('completed_by')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('nurse_tasks', function (Blueprint $table) {
            $table->dropIndex(['task_type']);
            $table->dropIndex(['source_type']);
            $table->dropIndex(['completed_by']);
            $table->dropIndex(['care_record_id']);

            $table->dropColumn([
                'task_type',
                'source_type',
                'completion_notes',
                'completed_by',
                'care_record_id',
            ]);
        });
    }
};