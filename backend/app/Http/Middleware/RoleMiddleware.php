<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;



class RoleMiddleware
{

    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response
    {


        // Check user login

        if(!$request->user())
        {

            return response()->json([

                'message'=>'Unauthenticated'

            ],401);

        }



        // Get user's role

        $userRole = $request->user()
                           ->role
                           ->role_name;



        // Check permission

        if(!in_array($userRole,$roles))
        {

            return response()->json([

                'message'=>'Access denied',
                'required_roles'=>$roles

            ],403);

        }



        return $next($request);


    }

}