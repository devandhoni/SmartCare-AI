<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Models\VitalSign;
use App\Services\ActivityLogger;
use App\Services\AIHealthAnalyzer;
use App\Services\ClinicalTimelineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyGlucoseCheckController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Monthly Glucose Checks
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = VitalSign::with([
                'resident:id,full_name,status',
            ])
            ->where(
                'record_source',
                'MONTHLY_GLUCOSE'
            )
            ->whereNotNull(
                'blood_glucose'
            );

        if ($request->filled('resident_id')) {
            $query->where(
                'resident_id',
                $request->integer('resident_id')
            );
        }

        if ($request->filled('month')) {
            $query->whereMonth(
                'recorded_at',
                $request->integer('month')
            );
        }

        if ($request->filled('year')) {
            $query->whereYear(
                'recorded_at',
                $request->integer('year')
            );
        }

        $checks = $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'checks' => $checks,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident Monthly Glucose History
    |--------------------------------------------------------------------------
    */

    public function residentChecks($id)
    {
        $resident = Resident::findOrFail($id);

        $checks = VitalSign::where(
                'resident_id',
                $resident->id
            )
            ->where(
                'record_source',
                'MONTHLY_GLUCOSE'
            )
            ->whereNotNull(
                'blood_glucose'
            )
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'status' => $resident->status,
            ],

            'checks' => $checks,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Monthly Glucose Check
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        $id,
        AIHealthAnalyzer $aiAnalyzer,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $resident = Resident::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Only Active Residents
        |--------------------------------------------------------------------------
        */

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' =>
                    'Monthly glucose checks can only be recorded for active residents.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'blood_glucose' => [
                'required',
                'numeric',
                'min:1',
                'max:30',
            ],

            'glucose_measurement_type' => [
                'required',
                'in:FASTING,BEFORE_MEAL,AFTER_MEAL,RANDOM',
            ],

            'glucose_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'recorded_at' => [
                'required',
                'date',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Monthly Period
        |--------------------------------------------------------------------------
        */

        $recordedAt = Carbon::parse(
            $validated['recorded_at']
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Monthly Check
        |--------------------------------------------------------------------------
        |
        | Only one MONTHLY_GLUCOSE record is allowed for a resident during
        | the same calendar month.
        |
        | Routine VITALS glucose records are unaffected.
        |
        */

        $existing = VitalSign::where(
                'resident_id',
                $resident->id
            )
            ->where(
                'record_source',
                'MONTHLY_GLUCOSE'
            )
            ->whereYear(
                'recorded_at',
                $recordedAt->year
            )
            ->whereMonth(
                'recorded_at',
                $recordedAt->month
            )
            ->first();

        if ($existing) {
            return response()->json([
                'message' =>
                    'A monthly glucose check already exists for this resident for ' .
                    $recordedAt->format('F Y') .
                    '.',

                'existing_check' =>
                    $existing,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Glucose Vital Record
        |--------------------------------------------------------------------------
        */

        $vital = VitalSign::create([
            'resident_id' =>
                $resident->id,

            /*
            |--------------------------------------------------------------------------
            | This Is A Glucose-Only Check
            |--------------------------------------------------------------------------
            */

            'blood_pressure_systolic' =>
                null,

            'blood_pressure_diastolic' =>
                null,

            'blood_glucose' =>
                $validated['blood_glucose'],

            'glucose_measurement_type' =>
                $validated['glucose_measurement_type'],

            'glucose_notes' =>
                $validated['glucose_notes']
                ?? null,

            'record_source' =>
                'MONTHLY_GLUCOSE',

            'heart_rate' =>
                null,

            'oxygen_level' =>
                null,

            'temperature' =>
                null,

            'weight' =>
                null,

            'recorded_by' =>
                $request->user()->id,

            'recorded_at' =>
                $recordedAt,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $measurementType = ucwords(
            strtolower(
                str_replace(
                    '_',
                    ' ',
                    $vital->glucose_measurement_type
                )
            )
        );

        $timelineService->record(
            $resident->id,

            ClinicalEventType::VITAL,

            'Monthly Glucose Check',

            'Blood glucose ' .
                $vital->blood_glucose .
                ' mmol/L (' .
                $measurementType .
                ').',

            'VitalSign',

            $vital->id
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Monthly Glucose Check',

            'CREATE',

            'Monthly glucose check recorded for resident.',

            $resident->id
        );

        /*
        |--------------------------------------------------------------------------
        | AI Health Analysis
        |--------------------------------------------------------------------------
        */

        $aiAnalyzer->analyze(
            $vital
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $vital->load([
            'resident:id,full_name,status',
        ]);

        return response()->json([
            'message' =>
                'Monthly glucose check recorded successfully.',

            'check' =>
                $vital,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Single Monthly Glucose Check
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $check = VitalSign::with([
                'resident:id,full_name,status',
            ])
            ->where(
                'record_source',
                'MONTHLY_GLUCOSE'
            )
            ->findOrFail($id);

        return response()->json([
            'check' => $check,
        ]);
    }
}