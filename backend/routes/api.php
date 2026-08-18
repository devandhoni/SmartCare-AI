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



Route::middleware('auth:sanctum')
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

Route::get(
    '/nurse/residents/{id}/medication-dashboard',
    [NurseDashboardController::class,'medicationDashboard']
);

Route::get(
    '/nurse/dashboard/{residentId}',
    [NurseDashboardController::class,'residentDashboard']
);

/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/


Route::middleware([
    'auth:sanctum',
    'role:Administrator,Nurse'
])
->group(function(){



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
| Nurse Tasks
|--------------------------------------------------------------------------
*/


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