<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ResidentMedication extends Model
{

    protected $table = 'resident_medications';


    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'resident_id',

        'medication_id',

        'dosage_instruction',

        'dosage_quantity',

        'frequency',

        'time_slot',

        'scheduled_time',

        'start_date',

        'end_date',

        'prescribed_by'

    ];



    public function resident()
    {

        return $this->belongsTo(
            Resident::class
        );

    }



    public function medication()
    {

        return $this->belongsTo(
            Medication::class
        );

    }

    public function administrationRecords()
    {

        return $this->hasMany(
            MedicationAdministrationRecord::class,
            'resident_medication_id'
        );

    }

    

}