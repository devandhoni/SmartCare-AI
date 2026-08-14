<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;



class ActivityLog extends Model
{


    protected $table = 'activity_logs';



    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'user_id',

        'resident_id',

        'module',

        'action',

        'description'

    ];



    public function user()
    {

        return $this->belongsTo(
            User::class,
            'user_id'
        );

    }



    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );

    }


}