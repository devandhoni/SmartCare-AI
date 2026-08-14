<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;


return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(

        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',

    )


    ->withMiddleware(function (Middleware $middleware) {


        /*
        |--------------------------------------------------------------------------
        | Custom Middleware Aliases
        |--------------------------------------------------------------------------
        */


        $middleware->alias([

            'role' => \App\Http\Middleware\RoleMiddleware::class

        ]);



        /*
        |--------------------------------------------------------------------------
        | Disable Web Redirect For API Authentication
        |--------------------------------------------------------------------------
        */


        $middleware->redirectGuestsTo(function () {

            return null;

        });



    })


    ->withExceptions(function (Exceptions $exceptions): void {


        /*
        |--------------------------------------------------------------------------
        | API Authentication Exception Handler
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (

            AuthenticationException $e,

            $request

        ) {


            if ($request->expectsJson() || $request->is('api/*')) {


                return response()->json([

                    'message'=>'Unauthenticated'

                ],401);


            }


        });



    })

    ->create();