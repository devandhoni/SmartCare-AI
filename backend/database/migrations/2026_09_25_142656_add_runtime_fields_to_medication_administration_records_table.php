<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('medication_administration_records')) {
            return;
        }

        if (!Schema::hasColumn(
            'medication_administration_records',
            'administered_date'
        )) {
            Schema::table(
                'medication_administration_records',
                function (Blueprint $table) {
                    $table->date('administered_date')
                        ->nullable()
                        ->after('scheduled_time');
                }
            );
        }

        if (!Schema::hasColumn(
            'medication_administration_records',
            'created_on'
        )) {
            Schema::table(
                'medication_administration_records',
                function (Blueprint $table) {
                    $table->timestamp('created_on')
                        ->nullable();
                }
            );
        }

        if (!Schema::hasColumn(
            'medication_administration_records',
            'updated_on'
        )) {
            Schema::table(
                'medication_administration_records',
                function (Blueprint $table) {
                    $table->timestamp('updated_on')
                        ->nullable();
                }
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('medication_administration_records')) {
            return;
        }

        $columns = [];

        foreach ([
            'administered_date',
            'created_on',
            'updated_on',
        ] as $column) {
            if (Schema::hasColumn(
                'medication_administration_records',
                $column
            )) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table(
                'medication_administration_records',
                function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                }
            );
        }
    }
};