<?php

namespace App\Services;


use App\Enums\ClinicalEventType;


class ClinicalTimelineFormatter
{


    public function format($event)
    {


        $category = $this->getCategory(
            $event->event_type
        );


        $severity = null;

        $clinicalData = [];



        /*
        |--------------------------------------------------------------------------
        | AI Alert Data
        |--------------------------------------------------------------------------
        */


        if(
            $event->source_type === 'AiAlert'
        )
        {


            $alert = $event->source;


            if($alert)
            {

                $severity =
                    $alert->severity;


                $clinicalData = [


                    'severity'=>
                        $alert->severity,


                    'confidence'=>
                        number_format(
                            $alert->ai_confidence,
                            2
                        ).'%',


                    'message'=>
                        $alert->message


                ];

            }


        }





        /*
        |--------------------------------------------------------------------------
        | Vital Data
        |--------------------------------------------------------------------------
        */


        if(
            $event->source_type === 'VitalSign'
        )
        {


            $vital = $event->source;


            if($vital)
            {


                $clinicalData = [


                    'blood_pressure'=>

                    $vital->blood_pressure_systolic
                    .
                    '/'
                    .
                    $vital->blood_pressure_diastolic,


                    'oxygen'=>

                    $vital->oxygen_level.'%',


                    'glucose'=>

                    $vital->blood_glucose,


                    'temperature'=>

                    $vital->temperature



                ];

            }


        }





        return [


            'date'=>
                $event->event_date,


            'type'=>
                $event->event_type,


            'category'=>
                $category,


            'severity'=>
                $severity,


            'title'=>
                $event->event_title,


            'clinical_summary'=>
                $event->event_description,


            'source'=>
                $this->getSourceName(
                    $event->source_type
                ),


            'data'=>
                $clinicalData



        ];


    }







    private function getCategory($type)
    {


        return match($type)
        {


            ClinicalEventType::ADMISSION,
            ClinicalEventType::TRANSFER_IN,
            ClinicalEventType::TRANSFER_OUT,
            ClinicalEventType::DISCHARGE

            => 'Patient Movement',



            ClinicalEventType::VITAL,
            ClinicalEventType::DIAGNOSIS,
            ClinicalEventType::LAB_RESULT

            => 'Clinical Observation',




            ClinicalEventType::MEDICATION_STARTED,
            ClinicalEventType::MEDICATION_GIVEN,
            ClinicalEventType::MEDICATION_DELAYED,
            ClinicalEventType::MEDICATION_MISSED

            => 'Medication Management',




            ClinicalEventType::AI_ALERT,
            ClinicalEventType::AI_ESCALATION,
            ClinicalEventType::AI_RESOLUTION

            => 'AI Intelligence',




            ClinicalEventType::NURSE_ACTION,
            ClinicalEventType::DOCTOR_REVIEW

            => 'Clinical Action',



            ClinicalEventType::DOCUMENT_UPLOAD

            => 'Medical Document',




            default => 'General'

        };


    }







    private function getSourceName($source)
    {


        return match($source)
        {


            'AiAlert'
                => 'AI Engine',


            'VitalSign'
                => 'Vital Monitoring',


            'MedicationAdministrationRecord'
                => 'Medication System',


            default
                => $source


        };


    }


}