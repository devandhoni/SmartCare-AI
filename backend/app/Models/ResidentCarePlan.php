<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentCarePlan extends Model
{
    protected $table = 'resident_care_plans';

    protected $fillable = [
        'resident_id',
        'care_type',
        'title',
        'instructions',
        'frequency',
        'time_slot',
        'scheduled_time',
        'days_of_week',
        'start_date',
        'end_date',
        'priority',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
    | Created By
    |--------------------------------------------------------------------------
    */

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Updated By
    |--------------------------------------------------------------------------
    */

    public function updatedBy()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Active
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}