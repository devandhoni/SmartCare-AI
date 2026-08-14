<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{

    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_on';

    protected $fillable = [

        'resident_id',
        'record_type',
        'diagnosis',
        'doctor_name',
        'notes',
        'record_date',
        'created_by'

    ];



    public function resident()
    {

        return $this->belongsTo(
            Resident::class
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