<?php

namespace App\Http\Controllers;


use App\Services\HealthRiskAggregatorService;
use App\Models\Resident;



class HealthRiskController extends Controller
{


    public function show(
        $id,
        HealthRiskAggregatorService $service
    )
    {


        $resident = Resident::findOrFail($id);



        return response()->json([



            'resident'=>[


                'id'=>

                    $resident->id,


                'name'=>

                    $resident->full_name


            ],




            'health_risk'=>

                $service->calculate($id)



        ]);



    }



}