<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_admission_drafts', function (Blueprint $table) {
            $table->id();

            $table->string('draft_reference', 50)->unique();

            $table->string('full_name')->nullable();

            $table->unsignedTinyInteger('current_step')
                ->default(1);

            $table->json('form_data');

            $table->string('status', 30)
                ->default('DRAFT');

            /*
            |--------------------------------------------------------------------------
            | Existing users.id is signed BIGINT
            |--------------------------------------------------------------------------
            */

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('created_by', 'rad_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'rad_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['status', 'updated_at'],
                'rad_status_updated_idx'
            );

            $table->index(
                ['created_by', 'status'],
                'rad_creator_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_admission_drafts'
        );
    }
};