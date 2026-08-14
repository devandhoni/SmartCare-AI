<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ClinicalTimeline;
use Carbon\Carbon;



class ClinicalDecisionReviewController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Review AI Clinical Decision
    |--------------------------------------------------------------------------
    */


    public function review(
        Request $request,
        $id
    )
    {


        $request->validate([


            'status'=>
            'required|in:ACKNOWLEDGED,UNDER_MANAGEMENT,RESOLVED',


            'action'=>
            'required|string'


        ]);





        /*
        |--------------------------------------------------------------------------
        | Find Latest Pending AI Decision
        |--------------------------------------------------------------------------
        */


        $timeline =

            ClinicalTimeline::where(
                'resident_id',
                $id
            )
            ->where(
                'event_type',
                'AI_DECISION'
            )
            ->latest('event_date')
            ->first();





        if(!$timeline)
        {

            return response()->json([

                'message'=>
                'No AI clinical decision found.'

            ],404);

        }







        /*
        |--------------------------------------------------------------------------
        | Update Review Status
        |--------------------------------------------------------------------------
        */


        $timeline->update([


            'decision_status'=>
                $request->status,


            'reviewed_by'=>
                auth()->id(),


            'reviewed_at'=>
                Carbon::now(),


            'review_action'=>
                $request->action


        ]);








        return response()->json([


            'message'=>
            'Clinical decision reviewed successfully.',


            'timeline'=>
            $timeline


        ]);



    }



}