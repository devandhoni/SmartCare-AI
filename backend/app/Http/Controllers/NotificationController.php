<?php

namespace App\Http\Controllers;


use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Helpers\ApiResponse;



class NotificationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Get User Notifications
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $notifications =
            Notification::where(

                'user_id',

                auth()->id()

            )
            ->orderBy(

                'created_on',

                'desc'

            )
            ->get();







        return ApiResponse::success(

            'Notifications retrieved successfully',

            [

                'notifications'=>$notifications

            ]

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Unread Notification Count
    |--------------------------------------------------------------------------
    */


    public function unreadCount()
    {


        $count =
            Notification::where(

                'user_id',

                auth()->id()

            )
            ->where(

                'read_status',

                0

            )
            ->count();







        return ApiResponse::success(

            'Unread notification count retrieved successfully',

            [

                'unread_count'=>$count

            ]

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Mark Notification As Read
    |--------------------------------------------------------------------------
    */


    public function markAsRead(

        $id,

        ActivityLogger $logger

    )
    {


        $notification =
            Notification::where(

                'user_id',

                auth()->id()

            )
            ->findOrFail($id);







        $notification->update([


            'read_status'=>1


        ]);









        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */


        $logger->log(


            'Notification',


            'READ',


            'Notification marked as read.',


            null


        );









        return ApiResponse::success(

            'Notification marked as read',

            [

                'notification'=>$notification

            ]

        );


    }



}