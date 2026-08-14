<?php

namespace App\Http\Controllers;


use App\Services\PredictiveDeteriorationService;



class PredictiveDeteriorationController extends Controller
{


    public function show(

        $id,

        PredictiveDeteriorationService $service

    )
    {


        return response()->json([


            "resident_id"=>

                $id,



            "prediction"=>

                $service->predict($id)



        ]);


    }



}