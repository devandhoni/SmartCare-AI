<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentContact extends Model
{
    protected $fillable = [
        'resident_id',
        'full_name',
        'relationship',
        'phone',
        'whatsapp_number',
        'is_primary',
        'is_emergency_contact',
        'whatsapp_enabled',
        'medication_notifications_enabled',
        'care_notifications_enabled',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_emergency_contact' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'medication_notifications_enabled' => 'boolean',
        'care_notifications_enabled' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Resident
    |--------------------------------------------------------------------------
    */

    public function resident()
    {
        return $this->belongsTo(
            Resident::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Created By
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}