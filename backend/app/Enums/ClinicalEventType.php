<?php

namespace App\Enums;


class ClinicalEventType
{


    /*
    |--------------------------------------------------------------------------
    | Patient Movement
    |--------------------------------------------------------------------------
    */


    public const ADMISSION =
        'ADMISSION';


    public const TRANSFER_IN =
        'TRANSFER_IN';


    public const TRANSFER_OUT =
        'TRANSFER_OUT';


    public const DISCHARGE =
        'DISCHARGE';






    /*
    |--------------------------------------------------------------------------
    | Clinical Data
    |--------------------------------------------------------------------------
    */


    public const VITAL =
        'VITAL';


    public const DIAGNOSIS =
        'DIAGNOSIS';


    public const LAB_RESULT =
        'LAB_RESULT';









    /*
    |--------------------------------------------------------------------------
    | Medication
    |--------------------------------------------------------------------------
    */


    public const MEDICATION_STARTED =
        'MEDICATION_STARTED';


    public const MEDICATION_GIVEN =
        'MEDICATION_GIVEN';


    public const MEDICATION_DELAYED =
        'MEDICATION_DELAYED';


    public const MEDICATION_MISSED =
        'MEDICATION_MISSED';








    /*
    |--------------------------------------------------------------------------
    | AI Intelligence
    |--------------------------------------------------------------------------
    */


    public const AI_ALERT =
        'AI_ALERT';


    public const AI_ESCALATION =
        'AI_ESCALATION';


    public const AI_RESOLUTION =
        'AI_RESOLUTION';


    /*
    |--------------------------------------------------------------------------
    | AI Monitoring
    |
    | Stores AI clinical monitoring snapshots,
    | risk score changes and trend analysis.
    |--------------------------------------------------------------------------
    */


    public const AI_MONITORING =
        'AI_MONITORING';








    /*
    |--------------------------------------------------------------------------
    | Human Clinical Action
    |--------------------------------------------------------------------------
    */


    public const NURSE_ACTION =
        'NURSE_ACTION';


    public const DOCTOR_REVIEW =
        'DOCTOR_REVIEW';








    /*
    |--------------------------------------------------------------------------
    | External Data
    |--------------------------------------------------------------------------
    */


    public const DOCUMENT_UPLOAD =
        'DOCUMENT_UPLOAD';





}