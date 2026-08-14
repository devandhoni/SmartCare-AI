<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;



class AlertAction extends Model
{


    protected $table = 'alert_actions';

    public $timestamps = true;

    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';

    protected $fillable = [

        'alert_id',

        'user_id',

        'action_type',

        'description'

    ];



    public function alert()
    {

        return $this->belongsTo(
            AiAlert::class,
            'alert_id'
        );

    }



    public function user()
    {

        return $this->belongsTo(
            User::class,
            'user_id'
        );

    }



}