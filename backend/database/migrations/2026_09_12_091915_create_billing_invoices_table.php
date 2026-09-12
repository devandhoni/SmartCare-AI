<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();

            // Existing SmartCare-AI residents.id is signed BIGINT.
            $table->bigInteger('resident_id');

            // Existing admission-related IDs are Laravel unsigned BIGINT IDs.
            $table->unsignedBigInteger('resident_admission_id')->nullable();
            $table->unsignedBigInteger('admission_consent_id')->nullable();

            $table->string('invoice_number', 50)->unique();

            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('issue_date');
            $table->date('due_date');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('adjustment_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);

            $table->string('status', 30)->default('DRAFT');
            $table->text('notes')->nullable();

            // users.id is signed BIGINT.
            $table->bigInteger('generated_by')->nullable();

            $table->timestamps();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->onDelete('restrict');

            $table->foreign('resident_admission_id')
                ->references('id')
                ->on('resident_admissions')
                ->onDelete('set null');

            $table->foreign('admission_consent_id')
                ->references('id')
                ->on('resident_admission_consents')
                ->onDelete('set null');

            $table->foreign('generated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(
                ['resident_id', 'status'],
                'billing_invoices_resident_status_idx'
            );

            $table->index(
                ['billing_period_start', 'billing_period_end'],
                'billing_invoices_period_idx'
            );

            $table->index(
                ['due_date', 'status'],
                'billing_invoices_due_status_idx'
            );

            $table->unique(
                ['resident_id', 'billing_period_start', 'billing_period_end'],
                'billing_invoices_resident_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
    }
};