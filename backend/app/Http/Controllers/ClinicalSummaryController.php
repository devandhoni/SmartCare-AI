<?php

namespace App\Http\Controllers;


use App\Services\ClinicalSummaryGeneratorService;



class ClinicalSummaryController extends Controller
{


    public function show(

        $id,

        ClinicalSummaryGeneratorService $service

    )
    {


        return response()->json(

            $service->generate($id)

        );


    }


}