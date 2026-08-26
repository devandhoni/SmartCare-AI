<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentAdmissionDraft extends Model
{
    protected $fillable = [
        'draft_reference',
        'full_name',
        'current_step',
        'form_data',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'form_data' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}