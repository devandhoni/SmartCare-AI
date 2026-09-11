<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_administration_records', function (Blueprint $table) {
            $table->string('meal_type', 20)
                ->nullable()
                ->after('remarks');

            $table->boolean('meal_confirmed')
                ->default(false)
                ->after('meal_type');

            $table->timestamp('meal_confirmed_at')
                ->nullable()
                ->after('meal_confirmed');

            $table->text('meal_notes')
                ->nullable()
                ->after('meal_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('medication_administration_records', function (Blueprint $table) {
            $table->dropColumn([
                'meal_type',
                'meal_confirmed',
                'meal_confirmed_at',
                'meal_notes',
            ]);
        });
    }
};
