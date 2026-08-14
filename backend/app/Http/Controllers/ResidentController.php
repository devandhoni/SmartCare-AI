<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;


use App\Models\Resident;


use App\Services\ClinicalTimelineService;


use App\Enums\ClinicalEventType;




class ResidentController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | View Active Residents
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        return response()->json(

            Resident::where(
                'status',
                'Active'
            )
            ->get()

        );


    }









    /*
    |--------------------------------------------------------------------------
    | View Discharged Residents
    |--------------------------------------------------------------------------
    */


    public function archive()
    {


        return response()->json(

            Resident::where(
                'status',
                'Discharged'
            )
            ->get()

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Register New Resident
    |--------------------------------------------------------------------------
    */


    public function store(

        Request $request,

        ClinicalTimelineService $timelineService

    )
    {



        $data = $request->validate([


            'full_name'=>'required',


            'ic_number'=>'nullable',


            'date_of_birth'=>'nullable|date',


            'gender'=>'nullable',


            'nationality'=>'nullable',


            'address'=>'nullable',


            'profile_photo'=>'nullable',



            'emergency_contact'=>'nullable',


            'emergency_relationship'=>'nullable',


            'emergency_phone'=>'nullable',



            'blood_type'=>'nullable',


            'medical_condition'=>'nullable',


            'allergies'=>'nullable',


            'chronic_disease'=>'nullable',


            'medical_notes'=>'nullable',



            'admission_date'=>'nullable|date',


            'status'=>'nullable'


        ]);







        /*
        |--------------------------------------------------------------------------
        | Default Resident Status
        |--------------------------------------------------------------------------
        */


        if(!isset($data['status']))
        {

            $data['status']="Active";

        }








        /*
        |--------------------------------------------------------------------------
        | Create Resident
        |--------------------------------------------------------------------------
        */


        $resident = Resident::create($data);








        /*
        |--------------------------------------------------------------------------
        | Create Admission Timeline
        |--------------------------------------------------------------------------
        */


        $timelineService->record(


            $resident->id,


            ClinicalEventType::ADMISSION,


            "Resident Admission",


            "Resident admitted into SmartCare AI nursing facility.",


            "Resident",


            $resident->id



        );








        return response()->json([


            'message'=>
            'Resident registered successfully',



            'resident'=>
            $resident



        ],201);



    }









    /*
    |--------------------------------------------------------------------------
    | View Single Resident
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        $resident =
        Resident::findOrFail($id);



        return response()->json(

            $resident

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Update Resident Information
    |--------------------------------------------------------------------------
    */


    public function update(

        Request $request,

        $id,

        ClinicalTimelineService $timelineService

    )
    {



        $resident =
        Resident::findOrFail($id);







        $data = $request->validate([


            'full_name'=>'nullable',


            'ic_number'=>'nullable',


            'date_of_birth'=>'nullable|date',


            'gender'=>'nullable',


            'nationality'=>'nullable',


            'address'=>'nullable',



            'emergency_contact'=>'nullable',


            'emergency_relationship'=>'nullable',


            'emergency_phone'=>'nullable',



            'blood_type'=>'nullable',


            'medical_condition'=>'nullable',


            'allergies'=>'nullable',


            'chronic_disease'=>'nullable',


            'medical_notes'=>'nullable',



            'status'=>'nullable'


        ]);







        $resident->update($data);








        /*
        |--------------------------------------------------------------------------
        | Record Profile Update Timeline
        |--------------------------------------------------------------------------
        */


        $timelineService->record(


            $resident->id,


            ClinicalEventType::DOCUMENT_UPLOAD,


            "Resident Profile Updated",


            "Resident information updated.",


            "Resident",


            $resident->id



        );








        return response()->json([


            'message'=>
            'Resident updated successfully',



            'resident'=>
            $resident



        ]);



    }









    /*
    |--------------------------------------------------------------------------
    | Discharge Resident
    |--------------------------------------------------------------------------
    */


    public function destroy(

        $id,

        ClinicalTimelineService $timelineService

    )
    {



        $resident =
        Resident::findOrFail($id);








        $resident->update([


            'status'=>
            'Discharged',



            'discharge_date'=>
            now()



        ]);








        /*
        |--------------------------------------------------------------------------
        | Create Discharge Timeline Event
        |--------------------------------------------------------------------------
        */


        $timelineService->record(


            $resident->id,


            ClinicalEventType::DISCHARGE,


            "Resident Discharge",


            "Resident discharged from SmartCare AI nursing facility.",


            "Resident",


            $resident->id



        );








        return response()->json([


            'message'=>
            'Resident discharged successfully',



            'resident'=>
            $resident



        ]);



    }




}