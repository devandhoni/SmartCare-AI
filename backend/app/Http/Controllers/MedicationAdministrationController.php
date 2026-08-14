<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Models\Resident;
use App\Models\ResidentMedication;
use App\Models\MedicationAdministrationRecord;
use App\Models\MedicineInventory;
use App\Models\MedicineTransaction;
use App\Models\AiAlert;
use App\Models\ClinicalTimeline;


use App\Services\ClinicalTimelineService;




class MedicationAdministrationController extends Controller
{





    /*
    |--------------------------------------------------------------------------
    | Get Resident Medication Schedule
    |--------------------------------------------------------------------------
    */


    public function getSchedule($id)
    {


        $resident =
            Resident::findOrFail($id);



        $medications =
            ResidentMedication::with(
                'medication'
            )
            ->where(
                'resident_id',
                $id
            )
            ->get();





        $schedule = [

            'AM'=>[],

            'PM'=>[],

            'NIGHT'=>[],

            'OTHER'=>[]

        ];







        foreach($medications as $medication)
        {


            $latestRecord =
                MedicationAdministrationRecord::where(

                    'resident_medication_id',

                    $medication->id

                )
                ->latest()
                ->first();





            $data=[


                'id'=>$medication->id,


                'medicine'=>
                $medication->medication->medicine_name,


                'dosage'=>
                $medication->medication->dosage,


                'instruction'=>
                $medication->dosage_instruction,


                'quantity'=>
                $medication->dosage_quantity,


                'status'=>

                    $latestRecord
                    ?
                    $latestRecord->status
                    :
                    'PENDING'


            ];





            $slot =
                strtoupper(
                    $medication->time_slot
                );




            if(isset($schedule[$slot]))
            {

                $schedule[$slot][]=$data;

            }


        }







        return response()->json([


            'resident'=>
            $resident->full_name,


            'schedule'=>
            $schedule


        ]);



    }













    /*
    |--------------------------------------------------------------------------
    | Complete Medication
    |--------------------------------------------------------------------------
    */


    public function complete(

        Request $request,

        $id,

        ClinicalTimelineService $timelineService

    )
    {


        $administrationRecord = null;



        DB::transaction(function() use(

            $id,

            &$administrationRecord,

            $timelineService

        ){



            $residentMedication =

                ResidentMedication::with(
                    'medication'
                )
                ->findOrFail($id);







            $alreadyCompleted =

                MedicationAdministrationRecord::where(

                    'resident_medication_id',

                    $residentMedication->id

                )
                ->whereDate(

                    'completed_time',

                    today()

                )
                ->where(

                    'status',

                    'COMPLETED'

                )
                ->exists();






            if($alreadyCompleted)
            {

                throw new \Exception(
                    'Medication already completed today.'
                );

            }









            /*
            |--------------------------------------------------------------------------
            | Create Administration Record
            |--------------------------------------------------------------------------
            */


            $administrationRecord =

            MedicationAdministrationRecord::create([


                'resident_id'=>
                    $residentMedication->resident_id,


                'resident_medication_id'=>
                    $residentMedication->id,


                'time_slot'=>
                    $residentMedication->time_slot,


                'scheduled_time'=>
                    $residentMedication->scheduled_time,


                'status'=>
                    'COMPLETED',



                'completed_time'=>
                    now(),



                'administered_date'=>
                    now()->toDateString(),



                'completed_by'=>
                    auth()->id()


            ]);









            /*
            |--------------------------------------------------------------------------
            | Clinical Timeline Record
            |--------------------------------------------------------------------------
            */


            $timelineService->recordMedicationGiven(


                $residentMedication->resident_id,


                $residentMedication
                ->medication
                ->medicine_name
                .
                " administered.",


                $administrationRecord->id


            );









            /*
            |--------------------------------------------------------------------------
            | Reduce Medicine Stock
            |--------------------------------------------------------------------------
            */


            $inventory =

                MedicineInventory::where(

                    'medication_id',

                    $residentMedication->medication_id

                )
                ->first();







            if($inventory)
            {


                $quantity =
                    $residentMedication->dosage_quantity;





                if($inventory->quantity < $quantity)
                {

                    throw new \Exception(
                        'Insufficient medicine stock.'
                    );

                }






                $inventory->decrement(

                    'quantity',

                    $quantity

                );







                MedicineTransaction::create([


                    'medication_id'=>
                    $residentMedication->medication_id,


                    'resident_id'=>
                    $residentMedication->resident_id,


                    'transaction_type'=>
                    'OUT',


                    'quantity'=>
                    $quantity,


                    'reference'=>
                    'Resident medication administration',


                    'performed_by'=>
                    auth()->id()


                ]);









                /*
                |--------------------------------------------------------------------------
                | AI Medicine Stock Monitoring
                |--------------------------------------------------------------------------
                */


                if(
                    $inventory->quantity
                    <=
                    $inventory->minimum_stock
                )
                {


                    $existingAlert =

                    AiAlert::where(

                        'alert_type',

                        'MEDICINE LOW STOCK'

                    )
                    ->where(

                        'status',

                        'OPEN'

                    )
                    ->first();






                    if(!$existingAlert)
                    {


                        AiAlert::create([


                            'resident_id'=>
                            $residentMedication->resident_id,


                            'alert_type'=>
                            'MEDICINE LOW STOCK',


                            'severity'=>
                            'WARNING',


                            'message'=>

                            $residentMedication
                            ->medication
                            ->medicine_name

                            .
                            ' stock is low. Current stock: '

                            .
                            $inventory->quantity

                            .
                            ' units.',



                            'ai_confidence'=>
                            100,


                            'status'=>
                            'OPEN'


                        ]);

                    }


                }



            }



        });








        return response()->json([


            'message'=>
            'Medication completed successfully',


            'timeline_recorded'=>
            true


        ]);



    }












    /*
    |--------------------------------------------------------------------------
    | Add Other Medication
    |--------------------------------------------------------------------------
    */


    public function addOtherMedication(

        Request $request,

        $id,

        ClinicalTimelineService $timelineService

    )
    {


        $request->validate([


            'medication_id'=>
            'required',


            'dosage_instruction'=>
            'required',


            'dosage_quantity'=>
            'required|integer'


        ]);






        $medication =

        ResidentMedication::create([


            'resident_id'=>
            $id,


            'medication_id'=>
            $request->medication_id,


            'dosage_instruction'=>
            $request->dosage_instruction,


            'dosage_quantity'=>
            $request->dosage_quantity,


            'frequency'=>
            'ON DEMAND',


            'time_slot'=>
            'OTHER',


            'start_date'=>
            now()


        ]);







        $timelineService->recordMedicationStarted(


            $id,


            $medication->medication->medicine_name
            .
            " medication assigned.",


            $medication->id


        );







        return response()->json([


            'message'=>
            'Other medication added successfully',


            'data'=>
            $medication


        ]);



    }



    /*
|--------------------------------------------------------------------------
| Medication History
|--------------------------------------------------------------------------
*/

public function history($id)
{


    $resident = Resident::findOrFail($id);



    /*
    |--------------------------------------------------------------------------
    | Administration Records
    |--------------------------------------------------------------------------
    */


    $records =

        MedicationAdministrationRecord::with(
            [
                'residentMedication.medication',
                'completedBy'
            ]
        )

        ->where(
            'resident_id',
            $id
        )

        ->orderBy(
            'created_on',
            'desc'
        )

        ->get();





    $history = [];





    foreach($records as $record)
    {


        $history[] = [


            'medicine'=>

                $record
                ->residentMedication
                ->medication
                ->medicine_name,



            'dosage'=>

                $record
                ->residentMedication
                ->medication
                ->dosage,



            'time_slot'=>

                $record->time_slot,



            'scheduled_time'=>

                $record->scheduled_time,



            'status'=>

                $record->status,



            'completed_time'=>

                $record->completed_time,



            'completed_by'=>

                $record->completedBy
                ?
                $record->completedBy->name
                :
                null,



            'remarks'=>

                $record->remarks


        ];


    }







    /*
    |--------------------------------------------------------------------------
    | Medication Related Timeline
    |--------------------------------------------------------------------------
    */


    $timeline =

        ClinicalTimeline::where(

            'resident_id',

            $id

        )

        ->whereIn(

            'event_type',

            [

                'MEDICATION_STARTED',

                'MEDICATION_GIVEN',

                'MEDICATION_DELAYED',

                'MEDICATION_MISSED'

            ]

        )

        ->orderBy(

            'event_date',

            'desc'

        )

        ->get();







    return response()->json([



        'resident'=>[


            'id'=>

                $resident->id,


            'name'=>

                $resident->full_name



        ],






        'medication_history'=>

            $history,






        'clinical_timeline'=>

            $timeline



    ]);



}



}