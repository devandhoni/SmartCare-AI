<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_documents', function (Blueprint $table) {
            $table->id();

            // Existing residents.id is SIGNED BIGINT.
            $table->bigInteger('resident_id');

            /*
            |--------------------------------------------------------------------------
            | Document Information
            |--------------------------------------------------------------------------
            */

            $table->string('document_type', 100);

            $table->string('title', 255);

            $table->string('original_name', 255);

            $table->string('stored_name', 255);

            $table->string('file_path', 500);

            $table->string('mime_type', 150)
                ->nullable();

            $table->bigInteger('file_size')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status / Notes
            |--------------------------------------------------------------------------
            */

            $table->string('status', 50)
                ->default('ACTIVE');

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Uploaded By
            |--------------------------------------------------------------------------
            */

            // Existing users.id is SIGNED BIGINT.
            $table->bigInteger('uploaded_by')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->cascadeOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['resident_id', 'document_type'],
                'rd_resident_type_idx'
            );

            $table->index(
                ['resident_id', 'status'],
                'rd_resident_status_idx'
            );

            $table->index(
                ['resident_id', 'created_at'],
                'rd_resident_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_documents'
        );
    }
};