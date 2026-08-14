<?php

namespace App\Http\Controllers;


use App\Services\ResidentRiskDashboardService;



class ResidentRiskDashboardController extends Controller
{


    public function show(

        $id,

        ResidentRiskDashboardService $service

    )
    {


        return response()->json(

            $service->generate($id)

        );


    }



}