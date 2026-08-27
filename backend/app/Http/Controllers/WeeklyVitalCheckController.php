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

class WeeklyVitalCheckController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Weekly Vital Checks
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = VitalSign::with(
            'resident:id,full_name,status'
        )
            ->where(
                'record_source',
                'WEEKLY_VITALS'
            );

        if ($request->filled('resident_id')) {
            $query->where(
                'resident_id',
                $request->integer('resident_id')
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
            ->get();

        return response()->json([
            'checks' => $checks,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resident Weekly Vital History
    |--------------------------------------------------------------------------
    */

    public function residentChecks($id)
    {
        $resident =
            Resident::findOrFail($id);

        $checks =
            VitalSign::where(
                'resident_id',
                $resident->id
            )
                ->where(
                    'record_source',
                    'WEEKLY_VITALS'
                )
                ->orderByDesc(
                    'recorded_at'
                )
                ->get();

        return response()->json([
            'resident' => [
                'id' =>
                    $resident->id,

                'full_name' =>
                    $resident->full_name,

                'status' =>
                    $resident->status,
            ],

            'checks' =>
                $checks,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Weekly Vital Check
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        $id,
        AIHealthAnalyzer $aiAnalyzer,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $resident =
            Resident::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Active Resident Validation
        |--------------------------------------------------------------------------
        */

        if (
            $resident->status !== 'Active'
        ) {
            return response()->json([
                'message' =>
                    'Weekly vital checks can only be recorded for active residents.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Vital Signs
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'blood_pressure_systolic' => [
                    'required',
                    'integer',
                    'min:50',
                    'max:250',
                ],

                'blood_pressure_diastolic' => [
                    'required',
                    'integer',
                    'min:30',
                    'max:150',
                ],

                'blood_glucose' => [
                    'nullable',
                    'numeric',
                    'min:1',
                    'max:30',
                ],

                'heart_rate' => [
                    'required',
                    'integer',
                    'min:30',
                    'max:200',
                ],

                'oxygen_level' => [
                    'required',
                    'integer',
                    'min:50',
                    'max:100',
                ],

                'temperature' => [
                    'required',
                    'numeric',
                    'min:30',
                    'max:45',
                ],

                'weight' => [
                    'required',
                    'numeric',
                    'min:1',
                    'max:300',
                ],

                'recorded_at' => [
                    'required',
                    'date',
                ],
            ]);


        $recordedAt =
            Carbon::parse(
                $validated['recorded_at']
            );


        /*
        |--------------------------------------------------------------------------
        | Weekly Duplicate Protection
        |--------------------------------------------------------------------------
        |
        | One WEEKLY_VITALS record per resident per ISO calendar week.
        |
        */

        $weekStart =
            $recordedAt
                ->copy()
                ->startOfWeek();

        $weekEnd =
            $recordedAt
                ->copy()
                ->endOfWeek();


        $existing =
            VitalSign::where(
                'resident_id',
                $resident->id
            )
                ->where(
                    'record_source',
                    'WEEKLY_VITALS'
                )
                ->whereBetween(
                    'recorded_at',
                    [
                        $weekStart,
                        $weekEnd,
                    ]
                )
                ->first();


        if ($existing) {
            return response()->json([
                'message' =>
                    'A weekly vital signs check already exists for this resident for the week of ' .
                    $weekStart->format('d M Y') .
                    '.',

                'existing_check' =>
                    $existing,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Weekly Vital Record
        |--------------------------------------------------------------------------
        */

        $vital =
            VitalSign::create([
                'resident_id' =>
                    $resident->id,

                'blood_pressure_systolic' =>
                    $validated[
                        'blood_pressure_systolic'
                    ],

                'blood_pressure_diastolic' =>
                    $validated[
                        'blood_pressure_diastolic'
                    ],

                'blood_glucose' =>
                    $validated[
                        'blood_glucose'
                    ] ?? null,

                'glucose_measurement_type' =>
                    null,

                'glucose_notes' =>
                    null,

                'record_source' =>
                    'WEEKLY_VITALS',

                'heart_rate' =>
                    $validated[
                        'heart_rate'
                    ] ?? null,

                'oxygen_level' =>
                    $validated[
                        'oxygen_level'
                    ] ?? null,

                'temperature' =>
                    $validated[
                        'temperature'
                    ] ?? null,

                'weight' =>
                    $validated[
                        'weight'
                    ] ?? null,

                'recorded_by' =>
                    $request->user()->id,

                'recorded_at' =>
                    $validated[
                        'recorded_at'
                    ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $timelineService->record(
            $resident->id,

            ClinicalEventType::VITAL,

            'Weekly Vital Signs Check',

            'Weekly vital signs recorded. BP ' .
                $vital->blood_pressure_systolic .
                '/' .
                $vital->blood_pressure_diastolic .
                ', Pulse ' .
                ($vital->heart_rate ?? '-') .
                ', SpO2 ' .
                ($vital->oxygen_level ?? '-') .
                '%, Temperature ' .
                ($vital->temperature ?? '-') .
                ' C, Weight ' .
                ($vital->weight ?? '-') .
                ' kg.',

            'WeeklyVitalSign',

            $vital->id
        );


        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Weekly Vital Signs',
            'CREATE',
            'Weekly vital signs check recorded for resident.',
            $resident->id
        );


        /*
        |--------------------------------------------------------------------------
        | Existing AI Health Analysis
        |--------------------------------------------------------------------------
        */

        $aiAnalyzer->analyze(
            $vital
        );


        return response()->json([
            'message' =>
                'Weekly vital signs check recorded successfully.',

            'check' =>
                $vital->load(
                    'resident:id,full_name,status'
                ),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Single Weekly Check
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $check =
            VitalSign::with(
                'resident:id,full_name,status'
            )
                ->where(
                    'record_source',
                    'WEEKLY_VITALS'
                )
                ->findOrFail($id);

        return response()->json([
            'check' =>
                $check,
        ]);
    }
}