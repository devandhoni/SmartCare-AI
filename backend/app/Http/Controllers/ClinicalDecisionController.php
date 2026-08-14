<?php

namespace App\Http\Controllers;


use App\Services\ClinicalDecisionEngine;



class ClinicalDecisionController extends Controller
{


    protected ClinicalDecisionEngine $engine;



    public function __construct(
        ClinicalDecisionEngine $engine
    )
    {

        $this->engine = $engine;

    }





    /*
    |--------------------------------------------------------------------------
    | Get AI Clinical Decision
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        $decision = $this->engine->analyze((int)$id);



        return response()->json([


            'message'=>'Clinical decision generated successfully',


            'decision'=>$decision


        ]);


    }


}