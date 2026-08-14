<?php

namespace App\Services;


use App\Models\ClinicalTimeline;
use App\Enums\ClinicalEventType;
use Carbon\Carbon;



class ClinicalTimelineService
{


    /**
     * Record Any Clinical Timeline Event
     */
    public function record(

        $residentId,

        $eventType,

        $eventTitle,

        $eventDescription = null,

        $sourceType = null,

        $sourceId = null,

        $eventDate = null

    )
    {


        /*
        |--------------------------------------------------------------------------
        | Validate Event Type
        |--------------------------------------------------------------------------
        */


        $allowedTypes = [

            ClinicalEventType::ADMISSION,
            ClinicalEventType::TRANSFER_IN,
            ClinicalEventType::TRANSFER_OUT,
            ClinicalEventType::DISCHARGE,

            ClinicalEventType::VITAL,

            ClinicalEventType::DIAGNOSIS,

            ClinicalEventType::LAB_RESULT,


            ClinicalEventType::MEDICATION_STARTED,
            ClinicalEventType::MEDICATION_GIVEN,
            ClinicalEventType::MEDICATION_DELAYED,
            ClinicalEventType::MEDICATION_MISSED,


            ClinicalEventType::AI_ALERT,
            ClinicalEventType::AI_ESCALATION,
            ClinicalEventType::AI_RESOLUTION,


            ClinicalEventType::NURSE_ACTION,
            ClinicalEventType::DOCTOR_REVIEW,


            ClinicalEventType::DOCUMENT_UPLOAD

        ];



        if(!in_array($eventType,$allowedTypes))
        {

            throw new \Exception(
                "Invalid clinical timeline event type: ".$eventType
            );

        }






        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Events
        |--------------------------------------------------------------------------
        */


        $existing =
        ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->where(
                'event_type',
                $eventType
            )
            ->where(
                'source_type',
                $sourceType
            )
            ->where(
                'source_id',
                $sourceId
            )
            ->where(
                'created_at',
                '>=',
                Carbon::now()->subMinutes(30)
            )
            ->first();





        if($existing)
        {

            $existing->update([

                'event_title'=>$eventTitle,

                'event_description'=>$eventDescription,

                'event_date'=>
                    $eventDate ?? Carbon::now()

            ]);


            return $existing;

        }







        /*
        |--------------------------------------------------------------------------
        | Create Timeline Event
        |--------------------------------------------------------------------------
        */


        return ClinicalTimeline::create([


            'resident_id'=>$residentId,


            'event_type'=>$eventType,


            'event_title'=>$eventTitle,


            'event_description'=>$eventDescription,


            'source_type'=>$sourceType,


            'source_id'=>$sourceId,


            'event_date'=>
                $eventDate ?? Carbon::now()


        ]);


    }









    public function recordAdmission($residentId,$description=null)
    {


        return $this->record(

            $residentId,

            ClinicalEventType::ADMISSION,

            "Resident Admission",

            $description ??
            "Resident admitted into SmartCare Nursing System",

            "Resident",

            $residentId

        );

    }









    public function recordDiagnosis($residentId,$diagnosis)
    {


        return $this->record(

            $residentId,

            ClinicalEventType::DIAGNOSIS,

            "Medical Diagnosis Recorded",

            $diagnosis,

            "Resident",

            $residentId

        );

    }









    public function recordLabResult(
        $residentId,
        $description,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::LAB_RESULT,

            "Laboratory Result Recorded",

            $description,

            "LabResult",

            $sourceId

        );

    }









    public function recordMedicationStarted(
        $residentId,
        $medication,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::MEDICATION_STARTED,

            "Medication Started",

            $medication,

            "Medication",

            $sourceId

        );

    }









    public function recordMedicationGiven(
        $residentId,
        $medication,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::MEDICATION_GIVEN,

            "Medication Administered",

            $medication,

            "MedicationAdministration",

            $sourceId

        );

    }









    /*
    |--------------------------------------------------------------------------
    | Medication Delayed  <-- NEW
    |--------------------------------------------------------------------------
    */


    public function recordMedicationDelayed(

        $residentId,

        $medication,

        $sourceId=null

    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::MEDICATION_DELAYED,

            "Medication Delayed",

            $medication,

            "ResidentMedication",

            $sourceId

        );


    }









    public function recordMedicationMissed(

        $residentId,

        $medication,

        $sourceId=null

    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::MEDICATION_MISSED,

            "Medication Missed",

            $medication,

            "MedicationAdministration",

            $sourceId

        );

    }









    public function recordNurseAction(
        $residentId,
        $action,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::NURSE_ACTION,

            "Nurse Intervention",

            $action,

            "NurseTask",

            $sourceId

        );

    }









    public function recordDoctorReview(
        $residentId,
        $review,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::DOCTOR_REVIEW,

            "Doctor Clinical Review",

            $review,

            "DoctorReview",

            $sourceId

        );

    }









    public function recordAIResolution(
        $residentId,
        $message,
        $sourceId=null
    )
    {


        return $this->record(

            $residentId,

            ClinicalEventType::AI_RESOLUTION,

            "AI Alert Resolved",

            $message,

            "AiAlert",

            $sourceId

        );

    }









    public function getTimeline($residentId)
    {


        return ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->orderBy(
                'event_date',
                'desc'
            )
            ->get();


    }









    public function countEvents($residentId)
    {


        return ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->count();


    }









    public function latestEvent($residentId)
    {


        return ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->latest('event_date')
            ->first();


    }



}