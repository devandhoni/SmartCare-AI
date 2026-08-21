<?php

namespace App\Http\Controllers;

use App\Services\AIExecutiveReportingPackageEngine;
use App\Services\AIExecutiveReportingPeriodIntelligence;
use App\Services\AIExecutiveReportingPeriodSummaryEngine;
use App\Services\AIExecutiveReportingFinalValidationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIExecutiveReportingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Step 53.7 + 53.8D
    | Executive AI Reporting Endpoint
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request,
        AIExecutiveReportingPackageEngine $reportingPackageEngine,
        AIExecutiveReportingPeriodIntelligence $reportingPeriodIntelligence,
        AIExecutiveReportingPeriodSummaryEngine $reportingPeriodSummaryEngine,
        AIExecutiveReportingFinalValidationEngine $finalValidationEngine
    ): JsonResponse {
        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Reporting Period
            |--------------------------------------------------------------------------
            |
            | Supported:
            |
            | ?days=7
            | ?days=30
            | ?days=90
            |
            | Service itself also protects the range to 1-365 days.
            |
            */

            $days =
                (int) $request->query(
                    'days',
                    7
                );

            $days =
                max(
                    1,
                    min(
                        $days,
                        365
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | 2. Generate Executive Reporting Package
            |--------------------------------------------------------------------------
            */

            $report =
                $reportingPackageEngine->generate();


            /*
            |--------------------------------------------------------------------------
            | 3. Reporting Safety Gate
            |--------------------------------------------------------------------------
            */

            $reportingReady =
                (bool) (
                    $report[
                        'executive_reporting_ready'
                    ]
                    ?? false
                );

            if (!$reportingReady) {

                return response()->json(
                    [
                        'success' =>
                            false,

                        'message' =>
                            'Executive AI reporting is currently blocked by reporting safety validation.',

                        'data' =>
                            $report,
                    ],
                    409
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Reporting Period Intelligence
            |--------------------------------------------------------------------------
            */

            $periodIntelligence =
                $reportingPeriodIntelligence->analyze(
                    $days
                );


            
            /*
            |--------------------------------------------------------------------------
            | Step 53.9
            | Executive Reporting Period Management Summary
            |--------------------------------------------------------------------------
            */

            $periodManagementSummary =
                $reportingPeriodSummaryEngine->analyze(
                    $days
                );



            /*
            |--------------------------------------------------------------------------
            | Step 53.10
            | Final Executive Reporting Validation
            |--------------------------------------------------------------------------
            */

            $finalValidation =
                $finalValidationEngine->analyze(
                    $days
                );


            /*
            |--------------------------------------------------------------------------
            | 5. Successful Executive Report
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' =>
                    true,

                'message' =>
                    'Executive AI intelligence report generated successfully.',

                'reporting_period_days' =>
                    $days,

                'data' => [

                    /*
                    |--------------------------------------------------------------------------
                    | Existing Step 53.7 Package
                    |--------------------------------------------------------------------------
                    */

                    ...$report,

                    /*
                    |--------------------------------------------------------------------------
                    | Step 53.8D
                    | Reporting Period Intelligence
                    |--------------------------------------------------------------------------
                    */

                    'reporting_period_intelligence' =>
                        $periodIntelligence,


                    /*
                    |--------------------------------------------------------------------------
                    | Step 53.9
                    | Reporting Period Management Summary
                    |--------------------------------------------------------------------------
                    */

                    'reporting_period_management_summary' =>
                        $periodManagementSummary,

                    
                    /*
                    |--------------------------------------------------------------------------
                    | Step 53.10
                    | Final Executive Reporting Validation
                    |--------------------------------------------------------------------------
                    */

                    'final_validation' =>
                        $finalValidation,
                ],
            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Failure Isolation
            |--------------------------------------------------------------------------
            */

            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'Unable to generate Executive AI intelligence report.',

                    'error' =>
                        $e->getMessage(),
                ],
                500
            );
        }
    }
}