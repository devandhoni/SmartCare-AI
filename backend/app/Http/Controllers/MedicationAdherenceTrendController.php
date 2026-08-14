<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Services\MedicationAdherenceTrendService;



class MedicationAdherenceTrendController extends Controller
{


    protected $service;



    public function __construct(
        MedicationAdherenceTrendService $service
    )
    {

        $this->service = $service;

    }




    /*
    |--------------------------------------------------------------------------
    | Medication Adherence Trend
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        $result =
            $this->service
            ->calculate($id);



        return response()->json(
            $result
        );


    }



}