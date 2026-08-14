<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Medication extends Model
{

    protected $table = 'medications';



    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'medicine_name',

        'category',

        'dosage',

        'unit',

        'supplier'

    ];



    public function residentMedications()
    {

        return $this->hasMany(
            ResidentMedication::class
        );

    }

}