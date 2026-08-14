<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{


    public function up(): void
    {

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