<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medication;


class MedicationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | View All Medications
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        return response()->json(

            Medication::all()

        );

    }



    /*
    |--------------------------------------------------------------------------
    | Create Medication
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {

        $request->validate([

            'medicine_name'=>'required'

        ]);


        $medication = Medication::create([

            'medicine_name'=>$request->medicine_name,
            'category'=>$request->category,
            'dosage'=>$request->dosage,
            'unit'=>$request->unit,
            'supplier'=>$request->supplier

        ]);


        return response()->json([

            'message'=>'Medication created successfully',
            'medication'=>$medication

        ]);

    }




    /*
    |--------------------------------------------------------------------------
    | View Single Medication
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {

        return response()->json(

            Medication::findOrFail($id)

        );

    }




    /*
    |--------------------------------------------------------------------------
    | Update Medication
    |--------------------------------------------------------------------------
    */

    public function update(Request $request,$id)
    {

        $medication = Medication::findOrFail($id);


        $medication->update(
            $request->all()
        );


        return response()->json([

            'message'=>'Medication updated successfully',
            'medication'=>$medication

        ]);

    }




    /*
    |--------------------------------------------------------------------------
    | Delete Medication
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {

        $medication = Medication::findOrFail($id);


        $medication->delete();


        return response()->json([

            'message'=>'Medication deleted successfully'

        ]);

    }


}