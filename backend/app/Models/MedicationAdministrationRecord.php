<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;



class MedicationAdministrationRecord extends Model
{


    protected $table =
    'medication_administration_records';



    public $timestamps=true;



    const CREATED_AT='created_on';

    const UPDATED_AT='updated_on';




    protected $fillable=[


        'resident_id',

        'resident_medication_id',

        'time_slot',

        'scheduled_time',

        'administered_date',

        'completed_time',

        'completed_by',

        'status',

        'remarks'


    ];





    protected $casts=[


        'administered_date'=>'date',

        'completed_time'=>'datetime',

        'scheduled_time'=>'datetime'


    ];







    public function resident()
    {


        return $this->belongsTo(
            Resident::class
        );


    }








    public function residentMedication()
    {


        return $this->belongsTo(

            ResidentMedication::class,

            'resident_medication_id'

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