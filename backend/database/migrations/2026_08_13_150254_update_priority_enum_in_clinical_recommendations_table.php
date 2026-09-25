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
        | Laravel's schema builder can rebuild the column safely for SQLite.
        |
        */

        if (DB::getDriverName() === 'sqlite') {
            DB::table('clinical_recommendations')
                ->where('priority', 'URGENT')
                ->update(['priority' => 'CRITICAL']);

            Schema::table('clinical_recommendations', function (Blueprint $table) {
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
        | Step 1: Temporarily remove ENUM restriction
        |--------------------------------------------------------------------------
        */

        DB::statement("
            ALTER TABLE clinical_recommendations
            MODIFY priority VARCHAR(50)
        ");

        /*
        |--------------------------------------------------------------------------
        | Step 2: Convert existing values
        |--------------------------------------------------------------------------
        */

        DB::statement("
            UPDATE clinical_recommendations
            SET priority='CRITICAL'
            WHERE priority='URGENT'
        ");

        /*
        |--------------------------------------------------------------------------
        | Step 3: Apply final ENUM
        |--------------------------------------------------------------------------
        */

        DB::statement("
            ALTER TABLE clinical_recommendations
            MODIFY priority
            ENUM(
                'LOW',
                'NORMAL',
                'HIGH',
                'CRITICAL'
            )
            DEFAULT 'NORMAL'
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
            Schema::table('clinical_recommendations', function (Blueprint $table) {
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
            ALTER TABLE clinical_recommendations
            MODIFY priority
            ENUM(
                'LOW',
                'NORMAL',
                'HIGH',
                'URGENT'
            )
            DEFAULT 'NORMAL'
        ");
    }
};