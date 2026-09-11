<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_message_logs', function (Blueprint $table) {
            $table->id();

            // residents.id is signed BIGINT in the existing SmartCare-AI schema.
            $table->bigInteger('resident_id');

            // resident_contacts.id uses Laravel's unsigned BIGINT id().
            $table->unsignedBigInteger('resident_contact_id')->nullable();

            $table->string('channel', 20)->default('WHATSAPP');
            $table->string('message_type', 50);
            $table->text('message');

            // Generic source linkage. For the medication workflow source_id is the
            // medication_administration_records.id (unsigned BIGINT).
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // Recipient snapshot is retained even if the contact later changes.
            $table->string('recipient_name', 255);
            $table->string('recipient_number', 50);

            $table->string('status', 20)->default('PENDING');
            $table->string('provider', 50)->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            // users.id is signed BIGINT in the existing schema.
            $table->bigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->onDelete('cascade');

            $table->foreign('resident_contact_id')
                ->references('id')
                ->on('resident_contacts')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(
                ['resident_id', 'status'],
                'family_message_logs_resident_status_idx'
            );

            $table->index(
                ['resident_contact_id', 'status'],
                'family_message_logs_contact_status_idx'
            );

            $table->unique(
                ['source_type', 'source_id', 'resident_contact_id', 'message_type'],
                'family_message_logs_source_contact_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_message_logs');
    }
};
