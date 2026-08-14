<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;

use App\Services\RealTimeMonitoringEngine;



class MonitorResidentHealth extends Command
{


    /*
    |--------------------------------------------------------------------------
    | Command Signature
    |--------------------------------------------------------------------------
    */


    protected $signature = 'monitor:health';




    /*
    |--------------------------------------------------------------------------
    | Command Description
    |--------------------------------------------------------------------------
    */


    protected $description = 
    'Run AI real-time resident health monitoring';






    /*
    |--------------------------------------------------------------------------
    | Execute Command
    |--------------------------------------------------------------------------
    */


    public function handle(
        RealTimeMonitoringEngine $monitoringEngine
    )
    {



        $this->info(
            'Starting AI health monitoring...'
        );





        $result = $monitoringEngine->monitor();






        $this->info(
            'Health monitoring completed.'
        );





        $this->line(
            'Residents checked: '
            .
            $result['residents_checked']
        );







        foreach($result['results'] as $resident)
        {


            $this->line(

                $resident['resident_name']
                .
                ' - '
                .
                $resident['status']

            );


        }






        return Command::SUCCESS;


    }


}