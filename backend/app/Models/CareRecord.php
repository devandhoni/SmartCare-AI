<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareRecord extends Model
{
    protected $fillable = [
        'resident_id',
        'care_type',
        'title',
        'notes',
        'care_status',
        'recorded_at',
        'recorded_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function recorder()
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}