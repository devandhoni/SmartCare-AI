<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('billing_invoice_id');

            // Existing residents.id is signed BIGINT.
            $table->bigInteger('resident_id');

            $table->string('payment_reference', 100)->unique();

            $table->decimal('amount', 12, 2);
            $table->date('payment_date');

            $table->string('payment_method', 30);
            $table->text('notes')->nullable();

            // Existing users.id is signed BIGINT.
            $table->bigInteger('received_by')->nullable();

            $table->timestamps();

            $table->foreign('billing_invoice_id')
                ->references('id')
                ->on('billing_invoices')
                ->onDelete('restrict');

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents')
                ->onDelete('restrict');

            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(
                ['billing_invoice_id', 'payment_date'],
                'billing_payments_invoice_date_idx'
            );

            $table->index(
                ['resident_id', 'payment_date'],
                'billing_payments_resident_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};