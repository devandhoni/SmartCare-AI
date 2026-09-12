<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('billing_invoice_id');

            $table->string('item_type', 30)->default('MONTHLY_FEE');
            $table->string('description', 255);

            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_amount', 12, 2);
            $table->decimal('line_total', 12, 2);

            $table->timestamps();

            $table->foreign('billing_invoice_id')
                ->references('id')
                ->on('billing_invoices')
                ->onDelete('cascade');

            $table->index(
                ['billing_invoice_id', 'item_type'],
                'billing_invoice_items_invoice_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoice_items');
    }
};