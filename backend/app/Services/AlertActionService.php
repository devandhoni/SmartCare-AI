<?php

namespace App\Services;


use App\Models\AlertAction;



class AlertActionService
{


    /*
    |--------------------------------------------------------------------------
    | Record Alert Action
    |--------------------------------------------------------------------------
    */


    public function record(
        $alertId,
        $actionType,
        $description = null,
        $userId = null
    )
    {


        return AlertAction::create([


            'alert_id'=>
                $alertId,


            'user_id'=>
                $userId,


            'action_type'=>
                $actionType,


            'description'=>
                $description


        ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Alert Acknowledged Action
    |--------------------------------------------------------------------------
    */


    public function acknowledged(
        $alertId,
        $userId
    )
    {


        return $this->record(


            $alertId,


            'ACKNOWLEDGED',


            'Nurse acknowledged AI alert',


            $userId


        );


    }



}