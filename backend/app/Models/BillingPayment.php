<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPayment extends Model
{
    public const METHOD_CASH = 'CASH';
    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';
    public const METHOD_CARD = 'CARD';
    public const METHOD_CHEQUE = 'CHEQUE';
    public const METHOD_OTHER = 'OTHER';

    protected $fillable = [
        'billing_invoice_id',
        'resident_id',
        'payment_reference',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            BillingInvoice::class,
            'billing_invoice_id'
        );
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }
}