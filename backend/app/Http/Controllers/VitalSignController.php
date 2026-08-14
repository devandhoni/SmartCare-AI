<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;


use App\Models\VitalSign;
use App\Models\Resident;


use App\Services\AIHealthAnalyzer;
use App\Services\ActivityLogger;
use App\Enums\ClinicalEventType;
use App\Services\ClinicalTimelineService;



class VitalSignController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Record Vital Sign
    |--------------------------------------------------------------------------
    */


    public function store(
        Request $request,
        $id,
        AIHealthAnalyzer $aiAnalyzer,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    )
    {


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */


        $request->validate([


            'blood_pressure_systolic'=>[
                'required',
                'integer',
                'min:50',
                'max:250'
            ],


            'blood_pressure_diastolic'=>[
                'required',
                'integer',
                'min:30',
                'max:150'
            ],


            'blood_glucose'=>[
                'nullable',
                'numeric',
                'min:1',
                'max:30'
            ],


            'heart_rate'=>[
                'nullable',
                'integer',
                'min:30',
                'max:200'
            ],


            'oxygen_level'=>[
                'nullable',
                'integer',
                'min:50',
                'max:100'
            ],


            'temperature'=>[
                'nullable',
                'numeric',
                'min:30',
                'max:45'
            ],


            'weight'=>[
                'nullable',
                'numeric',
                'min:1',
                'max:300'
            ]


        ]);







        /*
        |--------------------------------------------------------------------------
        | Create Vital Sign Record
        |--------------------------------------------------------------------------
        */


        $vital = VitalSign::create([


            'resident_id'=>$id,


            'blood_pressure_systolic'=>
                $request->blood_pressure_systolic,


            'blood_pressure_diastolic'=>
                $request->blood_pressure_diastolic,


            'blood_glucose'=>
                $request->blood_glucose,


            'heart_rate'=>
                $request->heart_rate,


            'oxygen_level'=>
                $request->oxygen_level,


            'temperature'=>
                $request->temperature,


            'weight'=>
                $request->weight,


            'recorded_by'=>
                $request->user()->id


        ]);








        /*
        |--------------------------------------------------------------------------
        | Create Clinical Timeline Record
        |--------------------------------------------------------------------------
        */


        $timelineService->record(


            $id,

            ClinicalEventType::VITAL,


            "Vital Signs Recorded",


            "BP ".
            $vital->blood_pressure_systolic.
            "/".
            $vital->blood_pressure_diastolic.
            ", Oxygen ".
            $vital->oxygen_level.
            "%, Glucose ".
            $vital->blood_glucose.
            ", Temperature ".
            $vital->temperature,


            "VitalSign",


            $vital->id


        );









        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */


        $logger->log(


            'Vital Signs',


            'CREATE',


            'New vital signs recorded for resident.',


            $id


        );









        /*
        |--------------------------------------------------------------------------
        | Run AI Health Analysis
        |--------------------------------------------------------------------------
        */


        $aiAnalyzer->analyze($vital);









        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */


        return response()->json([


            'message'=>
                'Vital sign recorded successfully',


            'vital'=>
                $vital


        ]);



    }









    /*
    |--------------------------------------------------------------------------
    | Resident Vital History
    |--------------------------------------------------------------------------
    */


    public function residentVitals($id)
    {


        $resident =
            Resident::findOrFail($id);



        return response()->json([


            'resident'=>
                $resident->full_name,


            'vitals'=>
                $resident->vitalSigns



        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Single Vital Record
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        return response()->json(


            VitalSign::findOrFail($id)


        );


    }



}