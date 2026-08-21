<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_executive_intelligence_snapshots',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Overall Executive Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'report_status',
                    50
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Facility Census
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'active_care_residents'
                )->default(0);

                $table->unsignedInteger(
                    'non_active_residents'
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Operational Risk
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'active_critical_cases'
                )->default(0);

                $table->unsignedInteger(
                    'active_care_alerts'
                )->default(0);

                $table->unsignedInteger(
                    'predictive_priority_residents'
                )->default(0);

                $table->unsignedInteger(
                    'care_priority_residents'
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Care Execution
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'execution_ready_actions'
                )->default(0);

                $table->unsignedInteger(
                    'doctor_review_actions'
                )->default(0);

                $table->unsignedInteger(
                    'evaluated_workflows'
                )->default(0);

                $table->decimal(
                    'workflow_success_rate',
                    5,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | AI Outcome Performance
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'total_outcomes_recorded'
                )->default(0);

                $table->decimal(
                    'average_ai_accuracy',
                    5,
                    2
                )->default(0);

                $table->decimal(
                    'intervention_success_rate',
                    5,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Operational Performance
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'sla_compliance_percentage',
                    5,
                    2
                )->default(0);

                $table->decimal(
                    'task_completion_rate',
                    5,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Learning Maturity
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'learning_maturity',
                    50
                )->nullable();

                $table->string(
                    'learning_confidence',
                    50
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Full Snapshot Payload
                |--------------------------------------------------------------------------
                |
                | Keeps the original report available for future reporting
                | without requiring executive metrics to be reconstructed.
                |
                */

                $table->json(
                    'snapshot_payload'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Snapshot Time
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'captured_at'
                );

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'captured_at'
                );

                $table->index(
                    'report_status'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_executive_intelligence_snapshots'
        );
    }
};