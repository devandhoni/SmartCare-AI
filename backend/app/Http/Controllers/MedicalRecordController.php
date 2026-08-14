<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use App\Models\Resident;


class MedicalRecordController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Create Medical Record
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {

        $request->validate([

            'resident_id' => 'required|exists:residents,id',
            'record_type' => 'required',
            'diagnosis' => 'required',
            'record_date' => 'required|date'

        ]);


        $record = MedicalRecord::create([

            'resident_id' => $request->resident_id,
            'record_type' => $request->record_type,
            'diagnosis' => $request->diagnosis,
            'doctor_name' => $request->doctor_name,
            'notes' => $request->notes,
            'record_date' => $request->record_date,
            'created_by' => auth()->id()

        ]);


        return response()->json([

            'message'=>'Medical record created successfully',
            'record'=>$record

        ]);

    }




    /*
    |--------------------------------------------------------------------------
    | View Resident Medical History
    |--------------------------------------------------------------------------
    */


    public function residentRecords($id)
    {

        $resident = Resident::findOrFail($id);


        return response()->json([

            'resident'=>$resident->full_name,

            'medical_records'=>
                $resident->medicalRecords

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | View Single Medical Record
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {

        return response()->json(

            MedicalRecord::findOrFail($id)

        );

    }





    /*
    |--------------------------------------------------------------------------
    | Update Medical Record
    |--------------------------------------------------------------------------
    */


    public function update(Request $request,$id)
    {

        $record = MedicalRecord::findOrFail($id);


        $record->update($request->all());


        return response()->json([

            'message'=>'Medical record updated successfully',
            'record'=>$record

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | Delete Medical Record
    |--------------------------------------------------------------------------
    */


    public function destroy($id)
    {

        $record = MedicalRecord::findOrFail($id);


        $record->delete();


        return response()->json([

            'message'=>'Medical record deleted successfully'

        ]);

    }


}