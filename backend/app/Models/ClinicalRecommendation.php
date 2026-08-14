<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class ClinicalRecommendation extends Model
{


    protected $table = 'clinical_recommendations';


    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'updated_on';



    protected $fillable = [

        'resident_id',

        'recommendation_type',

        'recommendation',

        'priority'

    ];



    public function resident()
    {

        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );

    }


}