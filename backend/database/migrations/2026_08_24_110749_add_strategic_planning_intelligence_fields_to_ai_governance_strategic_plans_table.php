<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_governance_strategic_plans', function (Blueprint $table) {
            $table->string('constraint_level')->nullable()->after('risk_score');
            $table->string('primary_recommendation_code')->nullable()->after('constraint_level');
            $table->text('primary_recommendation')->nullable()->after('primary_recommendation_code');
        });
    }

    public function down(): void
    {
        Schema::table('ai_governance_strategic_plans', function (Blueprint $table) {
            $table->dropColumn([
                'constraint_level',
                'primary_recommendation_code',
                'primary_recommendation',
            ]);
        });
    }
};