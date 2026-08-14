<?php

namespace App\Services;


use App\Models\ActivityLog;



class ActivityLogger
{


    public function log(
        $module,
        $action,
        $description,
        $residentId = null
    )
    {


        ActivityLog::create([


            'user_id'=>auth()->id(),


            'resident_id'=>$residentId,


            'module'=>$module,


            'action'=>$action,


            'description'=>$description


        ]);


    }



}