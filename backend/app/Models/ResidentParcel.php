<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentParcel extends Model
{
    protected $table = 'resident_parcels';

    protected $fillable = [

        'resident_id',

        'parcel_reference',

        'sender_name',

        'courier_name',

        'tracking_number',

        'parcel_description',

        'quantity',

        'condition_on_arrival',

        'received_at',

        'received_by',

        'parcel_checked',

        'resident_notified',

        'receiving_notes',

        'status',

        'collected_at',

        'collected_by_name',

        'collected_by_relationship',

        'collected_by_contact',

        'collection_recorded_by',

        'collection_notes',

    ];

    protected $casts = [

        'quantity' => 'integer',

        'parcel_checked' => 'boolean',

        'resident_notified' => 'boolean',

        'received_at' => 'datetime',

        'collected_at' => 'datetime',

        'created_at' => 'datetime',

        'updated_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Resident
    |--------------------------------------------------------------------------
    */

    public function resident()
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Member Who Received Parcel
    |--------------------------------------------------------------------------
    */

    public function receivedBy()
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Member Who Recorded Collection
    |--------------------------------------------------------------------------
    */

    public function collectionRecordedBy()
    {
        return $this->belongsTo(
            User::class,
            'collection_recorded_by'
        );
    }
}