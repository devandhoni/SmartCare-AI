<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Step 52.7
     * Expand Nurse Task priority levels for AI care workflow execution.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE nurse_tasks
            MODIFY COLUMN priority
            ENUM(
                'LOW',
                'NORMAL',
                'HIGH',
                'URGENT',
                'CRITICAL'
            )
            NOT NULL
            DEFAULT 'NORMAL'
        ");
    }

    /**
     * Reverse the migration safely.
     */
    public function down(): void
    {
        /*
         * CRITICAL cannot exist after reverting to the older enum,
         * so convert it to URGENT before shrinking the enum.
         */
        DB::table('nurse_tasks')
            ->where('priority', 'CRITICAL')
            ->update([
                'priority' => 'URGENT',
            ]);

        DB::statement("
            ALTER TABLE nurse_tasks
            MODIFY COLUMN priority
            ENUM(
                'LOW',
                'NORMAL',
                'HIGH',
                'URGENT'
            )
            NOT NULL
            DEFAULT 'NORMAL'
        ");
    }
};