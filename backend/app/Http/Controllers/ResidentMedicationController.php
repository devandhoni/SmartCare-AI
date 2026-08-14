<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resident;
use App\Models\ResidentMedication;


class ResidentMedicationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Assign Medication To Resident
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, $id)
    {


        $request->validate([

            'medication_id'=>'required|exists:medications,id',
            'dosage_instruction'=>'required',
            'frequency'=>'required'

        ]);



        $residentMedication = ResidentMedication::create([

            'resident_id'=>$id,
            'medication_id'=>$request->medication_id,
            'dosage_instruction'=>$request->dosage_instruction,
            'frequency'=>$request->frequency,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'prescribed_by'=>$request->prescribed_by

        ]);



        return response()->json([

            'message'=>'Medication assigned successfully',
            'resident_medication'=>$residentMedication

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | View Resident Medication List
    |--------------------------------------------------------------------------
    */


    public function index($id)
    {

        $resident = Resident::findOrFail($id);


        return response()->json([

            'resident'=>$resident->full_name,

            'medications'=>
            $resident->medications

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | Update Resident Medication
    |--------------------------------------------------------------------------
    */

    public function update(Request $request,$id)
    {

        $record = ResidentMedication::findOrFail($id);


        $record->update(
            $request->all()
        );


        return response()->json([

            'message'=>'Resident medication updated successfully',
            'record'=>$record

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | Remove Medication
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {

        $record = ResidentMedication::findOrFail($id);


        $record->delete();



        return response()->json([

            'message'=>'Resident medication removed successfully'

        ]);

    }


}