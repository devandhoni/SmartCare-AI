<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;



class AuthController extends Controller
{


    public function login(Request $request)
    {


        $request->validate([

            'email'=>'required|email',

            'password'=>'required'

        ]);



        $user = User::where(
            'email',
            $request->email
        )->first();



        if(!$user || 
        !Hash::check(
            $request->password,
            $user->password
        ))
        {

            return response()->json([

                'message'=>'Invalid login details'

            ],401);

        }



        $token = $user->createToken(
            'smartcare-token'
        )->plainTextToken;



        return response()->json([

            'message'=>'Login successful',

            'token'=>$token,

            'user'=>[

                'id'=>$user->id,

                'name'=>$user->full_name,

                'email'=>$user->email,

                'role'=>$user->role->role_name

            ]

        ]);

    }




    public function logout(Request $request)
    {

        $request->user()
        ->currentAccessToken()
        ->delete();


        return response()->json([

            'message'=>'Logged out successfully'

        ]);

    }


}