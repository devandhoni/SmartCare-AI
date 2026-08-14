<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MedicineTransaction extends Model
{


    protected $table = 'medicine_transactions';


    public $timestamps = false;



    protected $fillable = [

        'medication_id',

        'resident_id',

        'transaction_type',

        'quantity',

        'reference',

        'performed_by',

        'transaction_date'

    ];



    public function medication()
    {

        return $this->belongsTo(
            Medication::class,
            'medication_id'
        );

    }



    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );

    }



    public function performedBy()
    {

        return $this->belongsTo(
            User::class,
            'performed_by'
        );

    }


}