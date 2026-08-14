<?php

namespace App\Http\Controllers;


use App\Services\MedicationComplianceService;

use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use App\Models\AiAlert;
use App\Services\MedicationAnalyticsService;
use App\Models\Resident;


class MedicationComplianceController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Check Delayed Medication (AI Engine Test)
    |--------------------------------------------------------------------------
    */

    public function check(
        MedicationComplianceService $service
    )
    {


        return response()->json([


            'delayed_medications'=>

                $service->detectDelayedMedication()


        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Medication Compliance Dashboard
    |--------------------------------------------------------------------------
    */


    public function dashboard()
    {


        /*
        |--------------------------------------------------------------------------
        | Total Medication
        |--------------------------------------------------------------------------
        */


        $totalMedications =

            ResidentMedication::count();







        /*
        |--------------------------------------------------------------------------
        | Completed Today
        |--------------------------------------------------------------------------
        */


        $completedToday =

            MedicationAdministrationRecord::whereDate(

                'completed_time',

                today()

            )
            ->where(

                'status',

                'COMPLETED'

            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Pending Medication
        |--------------------------------------------------------------------------
        */


        $pending =

            ResidentMedication::whereDoesntHave(

                'administrationRecords',

                function($query)
                {


                    $query->whereDate(

                        'completed_time',

                        today()

                    )
                    ->where(

                        'status',

                        'COMPLETED'

                    );


                }

            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Active Medication Delay Alerts
        |--------------------------------------------------------------------------
        */


        $delayed =

            AiAlert::where(

                'alert_type',

                'MEDICATION DELAY'

            )
            ->where(

                'status',

                'OPEN'

            )
            ->count();









        /*
        |--------------------------------------------------------------------------
        | Compliance Rate
        |--------------------------------------------------------------------------
        */


        $complianceRate = 0;


        if($totalMedications > 0)
        {


            $complianceRate = round(

                (

                    $completedToday

                    /

                    $totalMedications

                )

                *

                100

            );


        }









        /*
        |--------------------------------------------------------------------------
        | Delayed List
        |--------------------------------------------------------------------------
        */


        $delayedMedications =


            AiAlert::with('resident')

            ->where(

                'alert_type',

                'MEDICATION DELAY'

            )

            ->where(

                'status',

                'OPEN'

            )

            ->latest()

            ->get()

            ->map(function($alert){


                return [


                    'resident'=>

                        $alert->resident
                        ?
                        $alert->resident->full_name
                        :
                        'Unknown',


                    'message'=>

                        $alert->message,


                    'severity'=>

                        $alert->severity,


                    'confidence'=>

                        $alert->ai_confidence.'%'


                ];


            });








        return response()->json([



            'summary'=>[


                'total_medications'=>

                    $totalMedications,


                'completed_today'=>

                    $completedToday,


                'pending'=>

                    $pending,


                'delayed'=>

                    $delayed,


                'compliance_rate'=>

                    $complianceRate.'%'


            ],





            'delayed_medications'=>

                $delayedMedications



        ]);



    }
    



    /*
|--------------------------------------------------------------------------
| Resident Medication Analytics
|--------------------------------------------------------------------------
*/

public function analytics(
    $id,
    MedicationAnalyticsService $service
)
{


    $resident = Resident::findOrFail($id);



    return response()->json([


        'resident'=>[


            'id'=>
                $resident->id,


            'name'=>
                $resident->full_name


        ],



        'medication_analytics'=>

            $service->calculateResidentCompliance($id)



    ]);


}



}