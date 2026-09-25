<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('alert_escalation_logs')) {
            return;
        }

        Schema::table('alert_escalation_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('alert_escalation_logs', 'resident_id')) {
                $table->unsignedBigInteger('resident_id')
                    ->nullable()
                    ->after('alert_id');
            }

            if (!Schema::hasColumn('alert_escalation_logs', 'priority')) {
                $table->string('priority')
                    ->default('NORMAL')
                    ->after('resident_id');
            }

            if (!Schema::hasColumn('alert_escalation_logs', 'escalation_reason')) {
                $table->text('escalation_reason')
                    ->nullable()
                    ->after('priority');
            }

            if (!Schema::hasColumn('alert_escalation_logs', 'escalated_at')) {
                $table->timestamp('escalated_at')
                    ->nullable()
                    ->after('assigned_to');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('alert_escalation_logs')) {
            return;
        }

        $columns = [];

        foreach ([
            'resident_id',
            'priority',
            'escalation_reason',
            'escalated_at',
        ] as $column) {
            if (Schema::hasColumn('alert_escalation_logs', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table('alert_escalation_logs', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};