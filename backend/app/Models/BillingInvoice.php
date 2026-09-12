<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInvoice extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_VOID = 'VOID';

    protected $fillable = [
        'resident_id',
        'resident_admission_id',
        'admission_consent_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'adjustment_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'notes',
        'generated_by',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected $appends = [
        'is_overdue',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function admission()
    {
        return $this->belongsTo(
            ResidentAdmission::class,
            'resident_admission_id'
        );
    }

    public function admissionConsent()
    {
        return $this->belongsTo(
            ResidentAdmissionConsent::class,
            'admission_consent_id'
        );
    }

    public function generatedBy()
    {
        return $this->belongsTo(
            User::class,
            'generated_by'
        );
    }

    public function items()
    {
        return $this->hasMany(BillingInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_ISSUED,
                self::STATUS_PARTIALLY_PAID,
            ],
            true
        )
            && (float) $this->balance_due > 0
            && $this->due_date !== null
            && $this->due_date->isBefore(today());
    }
}