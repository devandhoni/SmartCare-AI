<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Step 52.7
     * Expand Nurse Task priority levels for AI care workflow execution.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SQLite / Test Environment
        |--------------------------------------------------------------------------
        |
        | SQLite does not support MySQL/MariaDB MODIFY COLUMN syntax.
        | Use Laravel's schema builder for the SQLite test database.
        |
        */

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('nurse_tasks', function (Blueprint $table) {
                $table->string('priority', 50)
                    ->default('NORMAL')
                    ->change();
            });

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | MySQL / MariaDB
        |--------------------------------------------------------------------------
        */

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
         * CRITICAL cannot exist after reverting to the older definition,
         * so convert it to URGENT first.
         */
        DB::table('nurse_tasks')
            ->where('priority', 'CRITICAL')
            ->update([
                'priority' => 'URGENT',
            ]);

        /*
        |--------------------------------------------------------------------------
        | SQLite / Test Environment
        |--------------------------------------------------------------------------
        */

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('nurse_tasks', function (Blueprint $table) {
                $table->string('priority', 50)
                    ->default('NORMAL')
                    ->change();
            });

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | MySQL / MariaDB
        |--------------------------------------------------------------------------
        */

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