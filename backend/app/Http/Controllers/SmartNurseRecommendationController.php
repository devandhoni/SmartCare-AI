<?php

namespace App\Http\Controllers;


use App\Services\SmartNurseRecommendationService;



class SmartNurseRecommendationController extends Controller
{


    public function show(

        $id,

        SmartNurseRecommendationService $service

    )
    {


        return response()->json(

            $service->generate($id)

        );


    }


}