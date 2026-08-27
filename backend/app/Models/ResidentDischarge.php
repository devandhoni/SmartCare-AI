<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentDischarge extends Model
{
    protected $fillable = [
        'resident_id',
        'room_id_at_discharge',
        'discharge_number',

        'discharged_at',
        'discharge_type',
        'discharge_destination',
        'reason_for_discharge',

        'condition_at_discharge',
        'treatment_care_summary',
        'medical_summary',
        'follow_up_instructions',

        'medication_summary',
        'medication_instructions',

        'discharged_to',
        'discharged_to_relationship',
        'discharged_to_contact',

        'belongings_returned',
        'belongings_notes',

        'administrative_notes',

        'status',
        'prepared_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'discharged_at' =>
            'datetime',

        'completed_at' =>
            'datetime',

        'belongings_returned' =>
            'boolean',
    ];

    public function resident()
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function room()
    {
        return $this->belongsTo(
            Room::class,
            'room_id_at_discharge'
        );
    }

    public function preparedBy()
    {
        return $this->belongsTo(
            User::class,
            'prepared_by'
        );
    }

    public function completedBy()
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }
}