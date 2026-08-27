<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentVisitor extends Model
{
    protected $table = 'resident_visitors';

    protected $fillable = [
        'resident_id',
        'visit_reference',
        'visitor_name',
        'relationship',
        'contact_number',
        'id_number',
        'purpose_of_visit',
        'number_of_visitors',
        'visit_notes',
        'checked_in_at',
        'checked_in_by',
        'status',
        'checked_out_at',
        'checked_out_by',
        'checkout_notes',
    ];

    protected $casts = [
        'number_of_visitors' => 'integer',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
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

    public function checkedInBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }

    public function checkedOutBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_out_by'
        );
    }
}