<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentDocument extends Model
{
    protected $fillable = [
        'resident_id',
        'document_type',
        'title',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
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
    | Uploaded By
    |--------------------------------------------------------------------------
    */

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}