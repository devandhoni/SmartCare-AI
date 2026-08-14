<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Notification extends Model
{

    protected $table = 'notifications';



    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'user_id',

        'title',

        'message',

        'type',

        'read_status'

    ];



    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }


}