<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

use App\Models\Resident;
use App\Models\AiAlert;
use App\Models\VitalSign;
use App\Models\MedicationAdministrationRecord;
use App\Models\ResidentMedication;
use App\Models\ClinicalDocument;



class ClinicalTimeline extends Model
{


    protected $table = 'clinical_timelines';




    protected $fillable = [


        'resident_id',

        'event_type',

        'event_title',

        'event_description',

        'source_type',

        'source_id',

        'event_date',

        'decision_status',

        'reviewed_by',
        
        'reviewed_at',
        
        'review_action'


    ];





    protected $casts = [


        'event_date'=>'datetime',

        'reviewed_at'=>'datetime'


    ];







    /*
    |--------------------------------------------------------------------------
    | Resident Relationship
    |--------------------------------------------------------------------------
    */


    public function resident()
    {


        return $this->belongsTo(

            Resident::class,

            'resident_id'

        );


    }







    /*
    |--------------------------------------------------------------------------
    | Vital Sign Source
    |--------------------------------------------------------------------------
    */


    public function vital()
    {


        return $this->belongsTo(

            VitalSign::class,

            'source_id'

        )
        ->where(

            'source_type',

            'VitalSign'

        );


    }








    /*
    |--------------------------------------------------------------------------
    | AI Alert Source
    |--------------------------------------------------------------------------
    */


    public function alert()
    {


        return $this->belongsTo(

            AiAlert::class,

            'source_id'

        )
        ->where(

            'source_type',

            'AiAlert'

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Dynamic Source Relationship
    |
    | Used by ClinicalTimelineFormatter
    |--------------------------------------------------------------------------
    */


    public function source()
    {


        return match($this->source_type)
        {



            'AiAlert' =>

                $this->belongsTo(

                    AiAlert::class,

                    'source_id'

                ),






            'VitalSign' =>

                $this->belongsTo(

                    VitalSign::class,

                    'source_id'

                ),






            'MedicationAdministrationRecord' =>

                $this->belongsTo(

                    MedicationAdministrationRecord::class,

                    'source_id'

                ),






            'ResidentMedication' =>

                $this->belongsTo(

                    ResidentMedication::class,

                    'source_id'

                ),






            'ClinicalDocument' =>

                $this->belongsTo(

                    ClinicalDocument::class,

                    'source_id'

                ),






            default => null



        };


    }







}