<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;


class ResidentProfileController extends Controller
{


    public function show($id)
    {


        $resident = Resident::with([

    'medicalRecords',

    'medications.medication',

    'vitalSigns',

    'alerts',

    'nurseTasks',

    'healthRiskScore'

])->findOrFail($id);



        return response()->json([


            "resident"=>$resident,



            "latest_vital"=>

                $resident
                ->vitalSigns()
                ->latest()
                ->first(),



            "active_alerts"=>

                $resident
                ->alerts()
                ->where(
                    'status',
                    'OPEN'
                )
                ->get(),



            "pending_tasks"=>

                $resident
                ->nurseTasks()
                ->where(
                    'status',
                    'Pending'
                )
                ->get()


        ]);


    }


}