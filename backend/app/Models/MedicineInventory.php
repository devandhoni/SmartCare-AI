<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MedicineInventory extends Model
{
    public const STATUS_IN_STOCK = 'IN_STOCK';
    public const STATUS_LOW_STOCK = 'LOW_STOCK';
    public const STATUS_OUT_OF_STOCK = 'OUT_OF_STOCK';
    public const STATUS_EXPIRING_SOON = 'EXPIRING_SOON';
    public const STATUS_EXPIRED = 'EXPIRED';

    public const EXPIRY_WARNING_DAYS = 30;

    protected $table = 'medicine_inventory';

    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_on';

    protected $fillable = [
        'medication_id',
        'quantity',
        'minimum_stock',
        'expiry_date',
        'location',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_stock' => 'integer',
        'expiry_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'stock_status',
        'is_out_of_stock',
        'is_low_stock',
        'is_expired',
        'is_expiring_soon',
        'days_to_expiry',
        'needs_attention',
    ];

    public function medication()
    {
        return $this->belongsTo(
            Medication::class,
            'medication_id'
        );
    }

    public function isOutOfStock(): bool
    {
        return (int) $this->quantity <= 0;
    }

    public function isLowStock(): bool
    {
        return !$this->isOutOfStock()
            && (int) $this->quantity <= (int) $this->minimum_stock;
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)
            ->startOfDay()
            ->lt(today());
    }

    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date || $this->isExpired()) {
            return false;
        }

        $expiry = Carbon::parse($this->expiry_date)->startOfDay();

        return $expiry->lte(
            today()->copy()->addDays(self::EXPIRY_WARNING_DAYS)
        );
    }

    public function daysToExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return today()->diffInDays(
            Carbon::parse($this->expiry_date)->startOfDay(),
            false
        );
    }

    public function stockStatus(): string
    {
        if ($this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isOutOfStock()) {
            return self::STATUS_OUT_OF_STOCK;
        }

        if ($this->isLowStock()) {
            return self::STATUS_LOW_STOCK;
        }

        if ($this->isExpiringSoon()) {
            return self::STATUS_EXPIRING_SOON;
        }

        return self::STATUS_IN_STOCK;
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stockStatus();
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->isOutOfStock();
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->isLowStock();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->isExpiringSoon();
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        return $this->daysToExpiry();
    }

    public function getNeedsAttentionAttribute(): bool
    {
        return $this->stockStatus() !== self::STATUS_IN_STOCK;
    }
}
