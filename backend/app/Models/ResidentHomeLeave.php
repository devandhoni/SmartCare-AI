<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentHomeLeave extends Model
{
    protected $table = 'resident_home_leaves';

    protected $fillable = [
        'resident_id',
        'leave_reference',
        'leave_at',
        'reason_for_leave',
        'destination',
        'taken_by_name',
        'taken_by_relationship',
        'taken_by_contact',
        'expected_return_at',
        'medication_handed_over',
        'medication_instructions',
        'belongings_taken',
        'belongings_notes',
        'leave_notes',
        'status',
        'returned_at',
        'returned_by_name',
        'returned_by_relationship',
        'returned_by_contact',
        'condition_on_return',
        'return_notes',
        'recorded_by',
        'return_recorded_by',
    ];

    protected $casts = [
        'leave_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'returned_at' => 'datetime',
        'medication_handed_over' => 'boolean',
        'belongings_taken' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function recordedBy()
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }

    public function returnRecordedBy()
    {
        return $this->belongsTo(
            User::class,
            'return_recorded_by'
        );
    }
}