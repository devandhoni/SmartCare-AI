<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInvoiceItem extends Model
{
    public const TYPE_MONTHLY_FEE = 'MONTHLY_FEE';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    public const TYPE_OTHER = 'OTHER';

    protected $fillable = [
        'billing_invoice_id',
        'item_type',
        'description',
        'quantity',
        'unit_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            BillingInvoice::class,
            'billing_invoice_id'
        );
    }
}