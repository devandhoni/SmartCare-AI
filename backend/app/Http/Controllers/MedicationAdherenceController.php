<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Services\MedicationAdherenceService;



class MedicationAdherenceController extends Controller
{


    protected $service;



    public function __construct(
        MedicationAdherenceService $service
    )
    {

        $this->service = $service;

    }





    /*
    |--------------------------------------------------------------------------
    | Resident Medication Adherence Score
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