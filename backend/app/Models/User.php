<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;



class User extends Authenticatable
{

    use HasApiTokens, Notifiable;



    protected $table = 'users';



    protected $fillable = [

        'role_id',

        'full_name',

        'email',

        'password',

        'phone',

        'status'

    ];



    protected $hidden = [

        'password'

    ];





    public function role()
    {

        return $this->belongsTo(
            Role::class
        );

    }
    

    public function completedMedicationRecords()
    {

        return $this->hasMany(
            MedicationAdministrationRecord::class,
            'completed_by'
        );

    }

}