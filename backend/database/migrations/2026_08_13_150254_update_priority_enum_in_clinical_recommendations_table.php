<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public function up(): void
    {


        /*
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