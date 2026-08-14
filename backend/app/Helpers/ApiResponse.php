<?php

namespace App\Helpers;


class ApiResponse
{


    public static function success(
        $message,
        $data = null,
        $status = 200
    )
    {

        return response()->json([

            'success'=>true,

            'message'=>$message,

            'data'=>$data

        ],$status);


    }







    public static function error(
        $message,
        $errors = null,
        $status = 400
    )
    {

        return response()->json([

            'success'=>false,

            'message'=>$message,

            'errors'=>$errors

        ],$status);


    }



}