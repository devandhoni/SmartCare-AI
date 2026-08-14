<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MedicineInventory extends Model
{


    protected $table = "medicine_inventory";


    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'medication_id',

        'quantity',

        'minimum_stock',

        'expiry_date',

        'location'

    ];



    public function medication()
    {

        return $this->belongsTo(
            Medication::class,
            'medication_id'
        );

    }



    public function isLowStock()
    {

        return $this->quantity <= $this->minimum_stock;

    }


}