<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->string('glucose_measurement_type', 50)
                ->nullable()
                ->after('blood_glucose');

            $table->text('glucose_notes')
                ->nullable()
                ->after('glucose_measurement_type');

            $table->string('record_source', 50)
                ->default('VITALS')
                ->after('glucose_notes');

            $table->index(
                ['resident_id', 'record_source', 'recorded_at'],
                'vs_resident_source_recorded_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->dropIndex('vs_resident_source_recorded_idx');

            $table->dropColumn([
                'glucose_measurement_type',
                'glucose_notes',
                'record_source',
            ]);
        });
    }
};