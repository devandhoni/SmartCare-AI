<?php

namespace App\Http\Controllers;


use App\Models\Resident;
use App\Services\ClinicalTimelineService;
use App\Services\ClinicalTimelineFormatter;



class ClinicalTimelineController extends Controller
{


    /**
     * Resident Clinical Timeline
     */
    public function index(

        $id,

        ClinicalTimelineService $timelineService,

        ClinicalTimelineFormatter $formatter

    )
    {


        /*
        |--------------------------------------------------------------------------
        | Find Resident
        |--------------------------------------------------------------------------
        */


        $resident = Resident::findOrFail($id);







        /*
        |--------------------------------------------------------------------------
        | Get Timeline Events
        |--------------------------------------------------------------------------
        */


        $events =

             $timelineService
            ->getTimeline($id);










        /*
        |--------------------------------------------------------------------------
        | Format Timeline
        |--------------------------------------------------------------------------
        */


        $timeline =

            $events
            ->map(function($event) use ($formatter)
            {


                return $formatter
                    ->format($event);


            })
            ->values();









        /*
        |--------------------------------------------------------------------------
        | Clinical Summary
        |--------------------------------------------------------------------------
        */


        $criticalEvents =

            $events
            ->filter(function($event)
            {


                /*
                | AI Alert Critical
                */


                if(
                    $event->event_type === 'AI_ALERT'
                    &&
                    $event->source
                )
                {

                    return
                        $event->source->severity === 'CRITICAL';

                }





                /*
                | AI Monitoring Critical
                */


                if(
                    $event->event_type === 'AI_MONITORING'
                    &&
                    $event->source
                )
                {

                    return
                        $event->source->priority === 'CRITICAL';

                }



                return false;


            })
            ->count();










        $latestEvent =

            $events
            ->first();









        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */


        return response()->json([



            "resident"=>[


                "id"=>
                    $resident->id,


                "name"=>
                    $resident->full_name,


                "gender"=>
                    $resident->gender,


                "status"=>
                    $resident->status


            ],







            "clinical_summary"=>[



                "total_events"=>

                    $events->count(),






                "critical_events"=>

                    $criticalEvents,







                "latest_condition"=>

                    $latestEvent

                    ?

                    $latestEvent->event_title

                    :

                    "No Record",







                "risk_level"=>

                    $criticalEvents > 0

                    ?

                    "CRITICAL"

                    :

                    "NORMAL"



            ],







            "timeline"=>

                $timeline




        ]);



    }


}