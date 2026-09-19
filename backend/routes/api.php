<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ResidentMedicationController;
use App\Http\Controllers\VitalSignController;
use App\Http\Controllers\ResidentProfileController;
use App\Http\Controllers\NurseTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AIInsightController;
use App\Http\Controllers\ClinicalDashboardController;
use App\Http\Controllers\AIAlertWorkflowController;
use App\Http\Controllers\ClinicalDecisionController;
use App\Http\Controllers\AIIntelligenceDashboardController;
use App\Http\Controllers\HealthJourneyController;
use App\Http\Controllers\CareRecommendationController;
use App\Http\Controllers\AlertEscalationController;
use App\Http\Controllers\EscalationAnalyticsController;
use App\Http\Controllers\ClinicalPerformanceDashboardController;
use App\Http\Controllers\AIExecutiveSummaryController;
use App\Http\Controllers\AICommandCenterController;
use App\Http\Controllers\AIAlertController;
use App\Http\Controllers\MedicationAdministrationController;
use App\Http\Controllers\MedicationScheduleController;
use App\Http\Controllers\MedicationComplianceController;
use App\Http\Controllers\MedicationAdherenceController;
use App\Http\Controllers\MedicationAdherenceTrendController;
use App\Http\Controllers\NurseDashboardController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ClinicalTimelineController;
use App\Http\Controllers\HealthRiskController;
use App\Http\Controllers\ClinicalSummaryController;
use App\Http\Controllers\ResidentRiskDashboardController;
use App\Http\Controllers\PredictiveDeteriorationController;
use App\Http\Controllers\SmartNurseRecommendationController;
use App\Http\Controllers\AIAutoNurseTaskController;
use App\Http\Controllers\AIAlertPerformanceController;
use App\Http\Controllers\AIDashboardController;
use App\Http\Controllers\ClinicalDecisionReviewController;
use App\Http\Controllers\VitalTrendController;
use App\Http\Controllers\AICareWorkflowController;
use App\Http\Controllers\AIExecutiveReportingController;
use App\Http\Controllers\CareRecordController;
use App\Http\Controllers\ResidentContactController;
use App\Http\Controllers\ResidentDocumentController;
use App\Http\Controllers\ResidentAdmissionController;
use App\Http\Controllers\ResidentAdmissionConsentController;
use App\Http\Controllers\MedicineInventoryController;
use App\Http\Controllers\ResidentAdmissionDraftController;
use App\Http\Controllers\ResidentDischargeController;
use App\Http\Controllers\MonthlyGlucoseCheckController;
use App\Http\Controllers\ResidentParcelController;
use App\Http\Controllers\ResidentHomeLeaveController;
use App\Http\Controllers\ResidentVisitorController;
use App\Http\Controllers\WeeklyVitalCheckController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\FamilyMessageLogController;
use App\Http\Controllers\ResidentCarePlanController;
use App\Http\Controllers\ResidentBillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffController;


/*
|--------------------------------------------------------------------------
| Authentication API
|--------------------------------------------------------------------------
*/


Route::post(
    '/login',
    [
        AuthController::class,
        'login'
    ]
);



Route::middleware(['auth:sanctum', 'active'])
->post(
    '/logout',
    [
        AuthController::class,
        'logout'
    ]
);


/*
|--------------------------------------------------------------------------
| Role Testing
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/admin/test',
    function(){

        return [
            'message'=>'Welcome Administrator'
        ];

    }
);



Route::middleware([
    'auth:sanctum',
    'role:Nurse'
])
->get(
    '/nurse/test',
    function(){

        return [
            'message'=>'Welcome Nurse'
        ];

    }
);



/*
|--------------------------------------------------------------------------
| Dashboard API
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/admin/dashboard',
    [
        DashboardController::class,
        'adminDashboard'
    ]
);



Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/nurse/dashboard',
    [
        DashboardController::class,
        'nurseDashboard'
    ]
);

Route::middleware([
    'auth:sanctum',
    'active',
    'role:Administrator,Nurse'
])->get(
    '/nurse/residents/{id}/medication-dashboard',
    [NurseDashboardController::class,'medicationDashboard']
);

Route::middleware([
    'auth:sanctum',
    'active',
    'role:Administrator,Nurse'
])->get(
    '/nurse/dashboard/{residentId}',
    [NurseDashboardController::class,'residentDashboard']
);



/*
|--------------------------------------------------------------------------
| Management Reports
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/overview',
    [
        ReportController::class,
        'overview'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/resident-census',
    [
        ReportController::class,
        'residentCensus'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/care-operations',
    [
        ReportController::class,
        'careOperations'
    ]
);


Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/medication-operations',
    [
        ReportController::class,
        'medicationOperations'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/clinical-monitoring',
    [
        ReportController::class,
        'clinicalMonitoring'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/inventory-operations',
    [
        ReportController::class,
        'inventoryOperations'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/billing-operations',
    [
        ReportController::class,
        'billingOperations'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/family-facility',
    [
        ReportController::class,
        'familyFacilityOperations'
    ]
);

Route::middleware([
    'auth:sanctum',
    'role:Administrator'
])
->get(
    '/reports/export',
    [
        ReportController::class,
        'export'
    ]
);


/*
|--------------------------------------------------------------------------
| F12 Staff Administration
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active',
    'role:Administrator',
])->group(function () {

    Route::get('/staff', [StaffController::class, 'index']);

    Route::post('/staff', [StaffController::class, 'store']);

    Route::get('/staff/{id}', [StaffController::class, 'show']);

    Route::put('/staff/{id}', [StaffController::class, 'update']);

    Route::put('/staff/{id}/status', [StaffController::class, 'updateStatus']);

    Route::put('/staff/{id}/reset-password', [StaffController::class, 'resetPassword']);

});



/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'active',
    'role:Administrator,Nurse'
])
->group(function(){



/*
|--------------------------------------------------------------------------
| Family Communication / WhatsApp Audit
|--------------------------------------------------------------------------
*/

Route::get(
    '/family-messages',
    [FamilyMessageLogController::class, 'index']
);

Route::post(
    '/family-messages/{id}/retry',
    [FamilyMessageLogController::class, 'retry']
);

/*
|--------------------------------------------------------------------------
| Resident Parcel Controller
|--------------------------------------------------------------------------
*/

Route::get(
    '/parcels',
    [ResidentParcelController::class, 'index']
);

Route::post(
    '/parcels',
    [ResidentParcelController::class, 'store']
);

Route::get(
    '/parcels/{id}',
    [ResidentParcelController::class, 'show']
);

Route::put(
    '/parcels/{id}',
    [ResidentParcelController::class, 'update']
);

Route::delete(
    '/parcels/{id}',
    [ResidentParcelController::class, 'destroy']
);

Route::post(
    '/parcels/{id}/notify',
    [ResidentParcelController::class, 'markNotified']
);

Route::post(
    '/parcels/{id}/collect',
    [ResidentParcelController::class, 'collect']
);

Route::get(
    '/residents/{id}/parcels',
    [ResidentParcelController::class, 'residentParcels']
);


/*
|--------------------------------------------------------------------------
| Resident Billing Controller
|--------------------------------------------------------------------------
*/


Route::get(
    '/residents/{residentId}/billing/fee-status',
    [ResidentBillingController::class, 'feeStatus']
);



Route::post(
    '/residents/{residentId}/billing/invoices/monthly',
    [ResidentBillingController::class, 'generateMonthlyInvoice']
);


Route::get(
    '/residents/{residentId}/billing/invoices',
    [ResidentBillingController::class, 'residentInvoices']
);

Route::get(
    '/billing/invoices/{invoiceId}',
    [ResidentBillingController::class, 'showInvoice']
);

Route::post(
    '/billing/invoices/{invoiceId}/payments',
    [ResidentBillingController::class, 'recordPayment']
);

Route::get(
    '/billing/payments/{paymentId}/receipt',
    [ResidentBillingController::class, 'paymentReceipt']
);

Route::get(
    '/billing/payments/{paymentId}/receipt/pdf',
    [ResidentBillingController::class, 'paymentReceiptPdf']
);

/*
|--------------------------------------------------------------------------
| Residents
|--------------------------------------------------------------------------
*/


Route::get(
    '/residents',
    [
        ResidentController::class,
        'index'
    ]
);



Route::get(
    '/residents/archive',
    [
        ResidentController::class,
        'archive'
    ]
);



Route::post(
    '/residents',
    [
        ResidentController::class,
        'store'
    ]
);



Route::get(
    '/residents/{id}',
    [
        ResidentController::class,
        'show'
    ]
);



Route::put(
    '/residents/{id}',
    [
        ResidentController::class,
        'update'
    ]
);



Route::delete(
    '/residents/{id}',
    [
        ResidentController::class,
        'destroy'
    ]
);


Route::get(
    '/residents/{id}/contacts',
    [ResidentContactController::class, 'index']
);

Route::post(
    '/residents/{id}/contacts',
    [ResidentContactController::class, 'store']
);

Route::get(
    '/resident-contacts/{id}',
    [ResidentContactController::class, 'show']
);

Route::put(
    '/resident-contacts/{id}',
    [ResidentContactController::class, 'update']
);

Route::delete(
    '/resident-contacts/{id}',
    [ResidentContactController::class, 'destroy']
);



/*
|--------------------------------------------------------------------------
| Residents Documents Controller 
|--------------------------------------------------------------------------
*/



Route::get(
    '/residents/{id}/documents',
    [ResidentDocumentController::class, 'index']
);

Route::post(
    '/residents/{id}/documents',
    [ResidentDocumentController::class, 'store']
);

Route::get(
    '/resident-documents/{id}',
    [ResidentDocumentController::class, 'show']
);

Route::get(
    '/resident-documents/{id}/download',
    [ResidentDocumentController::class, 'download']
);

Route::put(
    '/resident-documents/{id}',
    [ResidentDocumentController::class, 'update']
);

Route::delete(
    '/resident-documents/{id}',
    [ResidentDocumentController::class, 'destroy']
);




/*
|--------------------------------------------------------------------------
| Residents Admission Document Controller 
|--------------------------------------------------------------------------
*/

Route::get(
    '/admissions',
    [ResidentAdmissionController::class, 'index']
);

Route::post(
    '/admissions',
    [ResidentAdmissionController::class, 'store']
);

Route::get(
    '/admissions/{id}',
    [ResidentAdmissionController::class, 'show']
);

Route::put(
    '/admissions/{id}',
    [ResidentAdmissionController::class, 'update']
);

Route::post(
    '/admissions/{id}/complete',
    [ResidentAdmissionController::class, 'complete']
);

Route::get(
    '/residents/{id}/admissions',
    [ResidentAdmissionController::class, 'residentAdmissions']
);

Route::get(
    '/admissions/{id}/consent',
    [ResidentAdmissionConsentController::class, 'show']
);

Route::post(
    '/admissions/{id}/consent',
    [ResidentAdmissionConsentController::class, 'store']
);



/*
|--------------------------------------------------------------------------
| Residents Admission Draft Controller 
|--------------------------------------------------------------------------
*/

Route::get(
    '/admission-drafts',
    [
        ResidentAdmissionDraftController::class,
        'index'
    ]
);

Route::post(
    '/admission-drafts',
    [
        ResidentAdmissionDraftController::class,
        'store'
    ]
);

Route::get(
    '/admission-drafts/{id}',
    [
        ResidentAdmissionDraftController::class,
        'show'
    ]
);

Route::put(
    '/admission-drafts/{id}',
    [
        ResidentAdmissionDraftController::class,
        'update'
    ]
);

Route::delete(
    '/admission-drafts/{id}',
    [
        ResidentAdmissionDraftController::class,
        'destroy'
    ]
);



/*
|--------------------------------------------------------------------------
| Residents Discharge Controller
|--------------------------------------------------------------------------
*/


Route::get(
    '/discharges',
    [
        ResidentDischargeController::class,
        'index'
    ]
);

Route::post(
    '/discharges',
    [
        ResidentDischargeController::class,
        'store'
    ]
);

Route::get(
    '/discharges/{id}',
    [
        ResidentDischargeController::class,
        'show'
    ]
);

Route::put(
    '/discharges/{id}',
    [
        ResidentDischargeController::class,
        'update'
    ]
);

Route::delete(
    '/discharges/{id}',
    [
        ResidentDischargeController::class,
        'destroy'
    ]
);

Route::post(
    '/discharges/{id}/complete',
    [
        ResidentDischargeController::class,
        'complete'
    ]
);

Route::get(
    '/residents/{id}/discharges',
    [
        ResidentDischargeController::class,
        'residentDischarges'
    ]
);

/*
|--------------------------------------------------------------------------
| Medical Records
|--------------------------------------------------------------------------
*/


        Route::post(
            '/medical-records',
            [
                MedicalRecordController::class,
                'store'
            ]
        );



        Route::get(
            '/residents/{id}/medical-records',
            [
                MedicalRecordController::class,
                'residentRecords'
            ]
        );



        Route::get(
            '/medical-records/{id}',
            [
                MedicalRecordController::class,
                'show'
            ]
        );



        Route::put(
            '/medical-records/{id}',
            [
                MedicalRecordController::class,
                'update'
            ]
        );



        Route::delete(
            '/medical-records/{id}',
            [
                MedicalRecordController::class,
                'destroy'
            ]
        );



/*
|--------------------------------------------------------------------------
| Medication
|--------------------------------------------------------------------------
*/


        Route::apiResource(
            '/medications',
            MedicationController::class
        );



/*
|--------------------------------------------------------------------------
| Resident Medication
|--------------------------------------------------------------------------
*/


        Route::post(
            '/residents/{id}/medications',
            [
                ResidentMedicationController::class,
                'store'
            ]
        );



        Route::get(
            '/residents/{id}/medications',
            [
                ResidentMedicationController::class,
                'index'
            ]
        );



        Route::put(
            '/resident-medications/{id}',
            [
                ResidentMedicationController::class,
                'update'
            ]
        );



        Route::delete(
            '/resident-medications/{id}',
            [
                ResidentMedicationController::class,
                'destroy'
            ]
        );



/*
|--------------------------------------------------------------------------
| Medicine Inventory Controller 
|--------------------------------------------------------------------------
*/


        Route::get(
            '/medicine-inventory',
            [
                MedicineInventoryController::class,
                'index'
            ]
        );

        Route::post(
            '/medicine-inventory',
            [
                MedicineInventoryController::class,
                'store'
            ]
        );

        Route::put(
            '/medicine-inventory/{id}',
            [
                MedicineInventoryController::class,
                'update'
            ]
        );

        Route::post(
            '/medicine-inventory/{id}/stock-adjustment',
            [
                MedicineInventoryController::class,
                'stockAdjustment'
            ]
        );

        Route::get(
            '/medicine-inventory/{id}/transactions',
            [
                MedicineInventoryController::class,
                'transactions'
            ]
        );

/*
|--------------------------------------------------------------------------
| Medication Administration Workflow
|--------------------------------------------------------------------------
*/


        Route::get(
            '/residents/{id}/medication-schedule',
            [
                MedicationAdministrationController::class,
                'getSchedule'
            ]
        );



        Route::put(
            '/medication-administration/{id}/complete',
            [
                MedicationAdministrationController::class,
                'complete'
            ]
        );


        Route::put(
            '/medication-administration/{id}/meal-confirmation',
            [
                MedicationAdministrationController::class,
                'confirmMeal'
            ]
        );



        Route::post(
            '/residents/{id}/other-medication',
            [
                MedicationAdministrationController::class,
                'addOtherMedication'
            ]
        );


        Route::post(
            '/residents/{id}/medication/complete',
            [MedicationAdministrationController::class,'complete']
        );


        /*
|--------------------------------------------------------------------------
| Vital Signs
|--------------------------------------------------------------------------
*/

        Route::get(
            '/residents/{id}/medication-adherence',
            [MedicationAdherenceController::class,'show']
        );




/*
|--------------------------------------------------------------------------
| Vital Signs
|--------------------------------------------------------------------------
*/


Route::post(
    '/residents/{id}/vitals',
    [
        VitalSignController::class,
        'store'
    ]
);



Route::get(
    '/residents/{id}/vitals',
    [
        VitalSignController::class,
        'residentVitals'
    ]
);



Route::get(
    '/vitals/{id}',
    [
        VitalSignController::class,
        'show'
    ]
);



/*
|--------------------------------------------------------------------------
| Monthly Glucose Check Controller
|--------------------------------------------------------------------------
*/


Route::get(
    '/monthly-glucose-checks',
    [MonthlyGlucoseCheckController::class, 'index']
);

Route::get(
    '/monthly-glucose-checks/{id}',
    [MonthlyGlucoseCheckController::class, 'show']
);

Route::get(
    '/residents/{id}/monthly-glucose-checks',
    [MonthlyGlucoseCheckController::class, 'residentChecks']
);

Route::post(
    '/residents/{id}/monthly-glucose-checks',
    [MonthlyGlucoseCheckController::class, 'store']
);


/*
|--------------------------------------------------------------------------
| Resident Profile
|--------------------------------------------------------------------------
*/


Route::get(
    '/residents/{id}/profile',
    [
        ResidentProfileController::class,
        'show'
    ]
);



/*
|--------------------------------------------------------------------------
| Today Controller
|--------------------------------------------------------------------------
*/

Route::get(
    '/today',
    [TodayController::class, 'index']
);

/*
|--------------------------------------------------------------------------
| Nurse Tasks
|--------------------------------------------------------------------------
*/

Route::post('/nurse-tasks/routine-care', [NurseTaskController::class, 'storeRoutineCare']);

Route::get(
    '/nurse/tasks',
    [
        NurseTaskController::class,
        'index'
    ]
);



Route::get(
    '/nurse/tasks/{id}',
    [
        NurseTaskController::class,
        'show'
    ]
);



Route::put(
    '/nurse/tasks/{id}/assign',
    [
        NurseTaskController::class,
        'assign'
    ]
);



Route::put(
    '/nurse/tasks/{id}/complete',
    [
        NurseTaskController::class,
        'complete'
    ]
);


Route::post(

    '/nurse/tasks/{id}/outcome',
    [
        NurseTaskController::class,

        'recordOutcome'
    ]

);


Route::put(
    '/nurse/tasks/{id}/accept',
    [
        NurseTaskController::class,
        'accept'
    ]
);


Route::put(

    '/nurse/tasks/{id}/acknowledge',

    [

        NurseTaskController::class,

        'acknowledge'

    ]

);


/*
|--------------------------------------------------------------------------
| Resident Care Plan Controller
|--------------------------------------------------------------------------
*/



Route::get('/residents/{residentId}/care-plans', [ResidentCarePlanController::class, 'index']);
Route::post('/residents/{residentId}/care-plans', [ResidentCarePlanController::class, 'store']);

Route::get('/care-plans/{id}', [ResidentCarePlanController::class, 'show']);
Route::put('/care-plans/{id}', [ResidentCarePlanController::class, 'update']);

Route::put('/care-plans/{id}/deactivate', [ResidentCarePlanController::class, 'deactivate']);
Route::put('/care-plans/{id}/activate', [ResidentCarePlanController::class, 'activate']);



/*
|--------------------------------------------------------------------------
| Weekly Vital Signs Checks
|--------------------------------------------------------------------------
*/

Route::get(
    '/weekly-vital-checks',
    [WeeklyVitalCheckController::class, 'index']
);

Route::get(
    '/weekly-vital-checks/{id}',
    [WeeklyVitalCheckController::class, 'show']
);

Route::get(
    '/residents/{id}/weekly-vital-checks',
    [WeeklyVitalCheckController::class, 'residentChecks']
);

Route::post(
    '/residents/{id}/weekly-vital-checks',
    [WeeklyVitalCheckController::class, 'store']
);



/*
|--------------------------------------------------------------------------
| AI Care Workflow Controller
|--------------------------------------------------------------------------
*/

    Route::post(
        '/residents/{resident}/care-workflow/{proposal}/approve',
        [AICareWorkflowController::class, 'approve']
    );





/*
|--------------------------------------------------------------------------
| AI Executive Reporting Controller
|--------------------------------------------------------------------------
*/


    Route::get(
        '/ai/executive-report',
        [
            AIExecutiveReportingController::class,
            'index'
        ]
    );


/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/


Route::get(
    '/notifications',
    [
        NotificationController::class,
        'index'
    ]
);



Route::get(
    '/notifications/unread-count',
    [
        NotificationController::class,
        'unreadCount'
    ]
);



Route::put(
    '/notifications/{id}/read',
    [
        NotificationController::class,
        'markAsRead'
    ]

);

/*
|--------------------------------------------------------------------------
| AI Health Insight API
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/residents/{id}/ai-insights',
    [
        AIInsightController::class,
        'residentInsight'
    ]
);


/*
|--------------------------------------------------------------------------
| Clinical Decision Support Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/residents/{id}/clinical-dashboard',
    [
        ClinicalDashboardController::class,
        'show'
    ]
);


/*
|--------------------------------------------------------------------------
| SmartCare AI Intelligence Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/dashboard/intelligence',
    [
        AIIntelligenceDashboardController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| Escalation Analytics Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/dashboard/escalation-analytics',
    [
        EscalationAnalyticsController::class,
        'index'
    ]
);



/*
|--------------------------------------------------------------------------
| Clinical Performance Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/dashboard/clinical-performance',
    [
        ClinicalPerformanceDashboardController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| AI Executive Summary Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/dashboard/executive-summary',
    [
        AIExecutiveSummaryController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| AI Command Center Dashboard
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->get(
    '/dashboard/ai-command-center',
    [
        AICommandCenterController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| Clinical Decision Engine
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])

->get(
    '/residents/{id}/clinical-decision',
    [
        ClinicalDecisionController::class,
        'show'
    ]
);


/*
|--------------------------------------------------------------------------
| Care Recommendations 
|--------------------------------------------------------------------------
*/

Route::get(
    '/residents/{id}/care-recommendations',
    [
        CareRecommendationController::class,
        'show'
    ]
);




/*
|--------------------------------------------------------------------------
| Care in Resident Tab
|--------------------------------------------------------------------------
*/

Route::get(
    '/residents/{id}/care-records',
    [CareRecordController::class, 'index']
);

Route::post(
    '/residents/{id}/care-records',
    [CareRecordController::class, 'store']
);

Route::get(
    '/care-records/{id}',
    [CareRecordController::class, 'show']
);

Route::put(
    '/care-records/{id}',
    [CareRecordController::class, 'update']
);

Route::delete(
    '/care-records/{id}',
    [CareRecordController::class, 'destroy']
);

/*
|--------------------------------------------------------------------------
| Health Journey Analyzer
|--------------------------------------------------------------------------
*/

Route::get(
    '/residents/{id}/health-journey',
    [
        HealthJourneyController::class,
        'show'
    ]
);


/*
|--------------------------------------------------------------------------
| Medication Schedule Control
|--------------------------------------------------------------------------
*/

Route::get(
    '/medication/check-due',
    [
        MedicationScheduleController::class,
        'checkDue'
    ]
);


/*
|--------------------------------------------------------------------------
| Medication Compliance Control
|--------------------------------------------------------------------------
*/

Route::get(
    '/medication/check-delay',
    [
        MedicationComplianceController::class,
        'check'
    ]
);

Route::get(
    '/medication/compliance/dashboard',
    [
        MedicationComplianceController::class,
        'dashboard'
    ]
);


/*
|--------------------------------------------------------------------------
| Medication Adherence Trend Controller
|--------------------------------------------------------------------------
*/

Route::get(
    '/residents/{id}/medication-adherence-trend',
    [MedicationAdherenceTrendController::class,'show']
);


/*
|--------------------------------------------------------------------------
| Alert Controller
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
->post(
    '/alerts/{id}/acknowledge',
    [AlertController::class,'acknowledge']
);


/*
|--------------------------------------------------------------------------
| Clinical Timeline
|--------------------------------------------------------------------------
*/

Route::get(
    '/residents/{id}/timeline',
    [ClinicalTimelineController::class,'index']
);


/*
|--------------------------------------------------------------------------
| Medication History
|--------------------------------------------------------------------------
*/


Route::get(

    '/residents/{id}/medication/history',

    [
        MedicationAdministrationController::class,
        'history'
    ]

);



/*
|--------------------------------------------------------------------------
| Medication Analytics
|--------------------------------------------------------------------------
*/

Route::get(

    '/residents/{id}/medication/analytics',

    [
        MedicationComplianceController::class,
        'analytics'
    ]

);


/*
|--------------------------------------------------------------------------
| Health Risk
|--------------------------------------------------------------------------
*/


Route::get(

    '/residents/{id}/health-risk',

    [
        HealthRiskController::class,
        'show'
    ]

);


/*
|--------------------------------------------------------------------------
| Clinical Summary
|--------------------------------------------------------------------------
*/



Route::get(

    '/residents/{id}/clinical-summary',

    [
        ClinicalSummaryController::class,
        'show'
    ]

);


/*
|--------------------------------------------------------------------------
| Resident Risk Dashboard
|--------------------------------------------------------------------------
*/


Route::get(

    '/residents/{id}/risk-dashboard',

    [
        ResidentRiskDashboardController::class,
        'show'
    ]

);



/*
|--------------------------------------------------------------------------
| Deterioration Prediction
|--------------------------------------------------------------------------
*/

Route::get(

    '/residents/{id}/deterioration-prediction',

    [
        PredictiveDeteriorationController::class,
        'show'
    ]

);


/*
|--------------------------------------------------------------------------
| Nurse Recommendation
|--------------------------------------------------------------------------
*/

Route::get(

    '/residents/{id}/nurse-recommendation',

    [
        SmartNurseRecommendationController::class,
        'show'
    ]

);


/*
|--------------------------------------------------------------------------
| AI Auto Nurse Task
|--------------------------------------------------------------------------
*/


Route::post(

    '/residents/{id}/ai-generate-task',

    [
        AIAutoNurseTaskController::class,
        'generate'
    ]

);


/*
|--------------------------------------------------------------------------
| AI Alert Performance Analytics
|--------------------------------------------------------------------------
*/


Route::get(

    '/ai-alerts/performance',

    [

        AIAlertPerformanceController::class,

        'index'

    ]

);


/*
|--------------------------------------------------------------------------
| AI Dashboard
|--------------------------------------------------------------------------
*/


Route::get(

    '/ai-dashboard',

    [

        AIDashboardController::class,

        'index'

    ]

);

/*
|--------------------------------------------------------------------------
| Clinical Decision Review
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->post(
    '/residents/{id}/clinical-decision/review',
    [
        ClinicalDecisionReviewController::class,
        'review'
    ]
);



/*
|--------------------------------------------------------------------------
| Vital Trend Controller
|--------------------------------------------------------------------------
*/


Route::get(
    '/residents/{residentId}/vital-trends',
    [VitalTrendController::class,'index']
);


/*
|--------------------------------------------------------------------------
| Resident Home Leave Controller
|--------------------------------------------------------------------------
*/

Route::get(
    '/home-leaves',
    [ResidentHomeLeaveController::class, 'index']
);

Route::post(
    '/home-leaves',
    [ResidentHomeLeaveController::class, 'store']
);

Route::get(
    '/home-leaves/{id}',
    [ResidentHomeLeaveController::class, 'show']
);

Route::put(
    '/home-leaves/{id}',
    [ResidentHomeLeaveController::class, 'update']
);

Route::delete(
    '/home-leaves/{id}',
    [ResidentHomeLeaveController::class, 'destroy']
);

Route::post(
    '/home-leaves/{id}/return',
    [ResidentHomeLeaveController::class, 'recordReturn']
);

Route::get(
    '/residents/{id}/home-leaves',
    [ResidentHomeLeaveController::class, 'residentLeaves']
);



/*
|--------------------------------------------------------------------------
| Resident Visitor Controller
|--------------------------------------------------------------------------
*/

Route::get(
    '/visitors',
    [ResidentVisitorController::class, 'index']
);

Route::post(
    '/visitors',
    [ResidentVisitorController::class, 'store']
);

Route::get(
    '/visitors/{id}',
    [ResidentVisitorController::class, 'show']
);

Route::put(
    '/visitors/{id}',
    [ResidentVisitorController::class, 'update']
);

Route::delete(
    '/visitors/{id}',
    [ResidentVisitorController::class, 'destroy']
);

Route::post(
    '/visitors/{id}/checkout',
    [ResidentVisitorController::class, 'checkout']
);

Route::get(
    '/residents/{id}/visitors',
    [ResidentVisitorController::class, 'residentVisitors']
);


/*
|--------------------------------------------------------------------------
| AI Alert Workflow
|--------------------------------------------------------------------------
*/


    Route::middleware([
        'auth:sanctum',
        'role:Administrator,Nurse'
    ])
    ->group(function(){


    Route::middleware('auth:sanctum')
    ->get(
        '/ai-alerts',
        [AIAlertController::class,'index']
    );


    Route::put(
        '/ai-alerts/{id}/acknowledge',
        [
            AIAlertWorkflowController::class,
            'acknowledge'
        ]
    );


    Route::put(
        '/ai-alerts/{id}/resolve',
        [
            AIAlertWorkflowController::class,
            'resolve'
        ]
    );


     Route::put(
        '/ai-alerts/{id}/escalate',
        [
            AlertEscalationController::class,
            'escalate'
        ]
    );


});


});