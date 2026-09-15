<?php

namespace App\Http\Controllers;

use App\Services\OperationalReportService;
use App\Services\OperationalReportExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Operational Report Overview
    |--------------------------------------------------------------------------
    |
    | Read-only management reporting overview.
    |
    */

    public function overview(
        Request $request,
        OperationalReportService $reportService
    ): JsonResponse {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        $from = isset($validated['from'])
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['from']
            )->startOfDay()
            : now()->startOfMonth();

        $to = isset($validated['to'])
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['to']
            )->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => [
                    'The report start date must be on or before the end date.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reporting Range Safety
        |--------------------------------------------------------------------------
        |
        | Maximum supported reporting period is 366 inclusive calendar days.
        |
        */

        if ($from->diffInDays($to) > 365) {
            throw ValidationException::withMessages([
                'to' => [
                    'The reporting period cannot exceed 366 days.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'Operational report overview loaded successfully.',

            'data' =>
                $reportService->overview(
                    $from,
                    $to
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resident Census Report
    |--------------------------------------------------------------------------
    |
    | Historical resident census reporting based on completed admission and
    | discharge evidence.
    |
    | This endpoint is strictly read-only.
    |
    */

    public function residentCensus(
        Request $request,
        OperationalReportService $reportService
    ): JsonResponse {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        $from = isset($validated['from'])
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['from']
            )->startOfDay()
            : now()->startOfMonth();

        $to = isset($validated['to'])
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['to']
            )->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => [
                    'The report start date must be on or before the end date.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reporting Range Safety
        |--------------------------------------------------------------------------
        */

        if ($from->diffInDays($to) > 365) {
            throw ValidationException::withMessages([
                'to' => [
                    'The reporting period cannot exceed 366 days.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'Resident census report loaded successfully.',

            'data' =>
                $reportService->residentCensus(
                    $from,
                    $to
                ),
        ]);
    }

    /*
|--------------------------------------------------------------------------
| Care Operations Report
|--------------------------------------------------------------------------
*/

public function careOperations(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Care operations report loaded successfully.',

        'data' =>
            $reportService->careOperations(
                $from,
                $to
            ),
    ]);
}


/*
|--------------------------------------------------------------------------
| Medication Operations Report
|--------------------------------------------------------------------------
*/

public function medicationOperations(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Medication operations report loaded successfully.',

        'data' =>
            $reportService->medicationOperations(
                $from,
                $to
            ),
    ]);
}

/*
|--------------------------------------------------------------------------
| Clinical Monitoring Report
|--------------------------------------------------------------------------
*/

public function clinicalMonitoring(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Clinical monitoring report loaded successfully.',

        'data' =>
            $reportService->clinicalMonitoring(
                $from,
                $to
            ),
    ]);
}

/*
|--------------------------------------------------------------------------
| Inventory Report
|--------------------------------------------------------------------------
*/

public function inventoryOperations(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Inventory report loaded successfully.',

        'data' =>
            $reportService->inventoryOperations(
                $from,
                $to
            ),
    ]);
}


/*
|--------------------------------------------------------------------------
| Billing Report
|--------------------------------------------------------------------------
*/

public function billingOperations(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Billing report loaded successfully.',

        'data' =>
            $reportService->billingOperations(
                $from,
                $to
            ),
    ]);
}

/*
|--------------------------------------------------------------------------
| Family Communication & Facility Activity Report
|--------------------------------------------------------------------------
*/

public function familyFacilityOperations(
    Request $request,
    OperationalReportService $reportService
): JsonResponse {
    $validated = $request->validate([
        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],
        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return response()->json([
        'success' => true,

        'message' =>
            'Family communication and facility activity report loaded successfully.',

        'data' =>
            $reportService->familyFacilityOperations(
                $from,
                $to
            ),
    ]);
}


public function export(
    Request $request,
    OperationalReportExportService $exportService
) {
    $validated = $request->validate([
        'report' => [
            'required',
            'string',
            'in:resident-census,care-operations,medication-operations,clinical-monitoring,inventory-operations,billing-operations,family-facility',
        ],

        'format' => [
            'required',
            'string',
            'in:csv,pdf',
        ],

        'from' => [
            'nullable',
            'date_format:Y-m-d',
        ],

        'to' => [
            'nullable',
            'date_format:Y-m-d',
        ],
    ]);

    $from = isset($validated['from'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['from']
        )->startOfDay()
        : now()->startOfMonth();

    $to = isset($validated['to'])
        ? Carbon::createFromFormat(
            'Y-m-d',
            $validated['to']
        )->endOfDay()
        : now()->endOfDay();

    if ($from->greaterThan($to)) {
        throw ValidationException::withMessages([
            'from' => [
                'The report start date must be on or before the end date.',
            ],
        ]);
    }

    if ($from->diffInDays($to) > 365) {
        throw ValidationException::withMessages([
            'to' => [
                'The reporting period cannot exceed 366 days.',
            ],
        ]);
    }

    return $exportService->export(
        $validated['report'],
        $validated['format'],
        $from,
        $to
    );
}




}