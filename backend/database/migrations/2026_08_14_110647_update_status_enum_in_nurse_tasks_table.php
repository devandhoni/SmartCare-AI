<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SQLite / Test Environment
        |--------------------------------------------------------------------------
        |
        | SQLite does not support MySQL's ALTER TABLE ... MODIFY syntax.
        | Use Laravel's schema builder so fresh SQLite test databases can
        | reproduce the current migration history.
        |
        */

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('nurse_tasks', function (Blueprint $table) {
                $table->string('status', 50)
                    ->default('Pending')
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
            MODIFY status
            ENUM(
                'Pending',
                'ACKNOWLEDGED',
                'Completed',
                'Cancelled'
            )
            DEFAULT 'Pending'
        ");
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SQLite / Test Environment
        |--------------------------------------------------------------------------
        */

        if (DB::getDriverName() === 'sqlite') {
            /*
             * ACKNOWLEDGED cannot be represented by the previous schema.
             * Convert it back to Pending before restoring the earlier
             * definition.
             */
            DB::table('nurse_tasks')
                ->where('status', 'ACKNOWLEDGED')
                ->update(['status' => 'Pending']);

            Schema::table('nurse_tasks', function (Blueprint $table) {
                $table->string('status', 50)
                    ->default('Pending')
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
            MODIFY status
            ENUM(
                'Pending',
                'Completed',
                'Cancelled'
            )
            DEFAULT 'Pending'
        ");
    }
};