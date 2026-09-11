<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMessageLog extends Model
{
    public const CHANNEL_WHATSAPP = 'WHATSAPP';

    public const TYPE_MEDICATION_MEAL_COMPLETED =
        'MEDICATION_MEAL_COMPLETED';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_SKIPPED = 'SKIPPED';

    protected $fillable = [
        'resident_id',
        'resident_contact_id',
        'channel',
        'message_type',
        'message',
        'source_type',
        'source_id',
        'recipient_name',
        'recipient_number',
        'status',
        'provider',
        'provider_message_id',
        'attempt_count',
        'sent_at',
        'failed_at',
        'failure_reason',
        'created_by',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'attempt_count' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(
            Resident::class
        );
    }

    public function residentContact()
    {
        return $this->belongsTo(
            ResidentContact::class
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
