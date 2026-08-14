<?php

namespace App\Http\Controllers;


use App\Services\MedicationScheduleService;


class MedicationScheduleController extends Controller
{


    public function checkDue(
        MedicationScheduleService $service
    )
    {


        $result =
            $service->checkDueMedications();



        return response()->json([

            'due_medications'=>$result

        ]);


    }


}