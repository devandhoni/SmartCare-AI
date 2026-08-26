<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResidentAdmissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Admissions
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $admissions =
            ResidentAdmission::with([
                'resident:id,full_name,status',
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent',
            ])
            ->orderByDesc('admitted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'admissions' => $admissions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident Admission History
    |--------------------------------------------------------------------------
    */

    public function residentAdmissions($residentId)
    {
        $resident =
            Resident::findOrFail(
                $residentId
            );

        $admissions =
            ResidentAdmission::with([
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent',
            ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('admitted_at')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' =>
                    $resident->full_name,
            ],

            'admissions' =>
                $admissions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Admission
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $validated =
            $request->validate([
                'resident_id' =>
                    'required|exists:residents,id',

                'admitted_at' =>
                    'required|date',

                'admission_type' =>
                    'nullable|string|max:100',

                'admission_source' =>
                    'nullable|string|max:150',

                'reason_for_admission' =>
                    'nullable|string',

                'medical_summary' =>
                    'nullable|string',

                'mobility_notes' =>
                    'nullable|string',

                'dietary_notes' =>
                    'nullable|string',

                'special_care_instructions' =>
                    'nullable|string',

                'belongings_notes' =>
                    'nullable|string',
            ]);

        $admission =
            ResidentAdmission::create([
                'resident_id' =>
                    $validated['resident_id'],

                'admission_number' =>
                    $this->generateAdmissionNumber(),

                'admitted_at' =>
                    $validated['admitted_at'],

                'admission_type' =>
                    $validated['admission_type']
                    ?? 'NEW_ADMISSION',

                'admission_source' =>
                    $validated['admission_source']
                    ?? null,

                'reason_for_admission' =>
                    $validated['reason_for_admission']
                    ?? null,

                'medical_summary' =>
                    $validated['medical_summary']
                    ?? null,

                'mobility_notes' =>
                    $validated['mobility_notes']
                    ?? null,

                'dietary_notes' =>
                    $validated['dietary_notes']
                    ?? null,

                'special_care_instructions' =>
                    $validated[
                        'special_care_instructions'
                    ] ?? null,

                'belongings_notes' =>
                    $validated['belongings_notes']
                    ?? null,

                'status' =>
                    'DRAFT',

                'admitted_by' =>
                    auth()->id(),
            ]);

        $admission->load([
            'resident:id,full_name,status',
            'admittedBy:id,full_name',
        ]);

        return response()->json([
            'message' =>
                'Admission created successfully.',

            'admission' =>
                $admission,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Admission
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $admission =
            ResidentAdmission::with([
                'resident',
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent.witness:id,full_name',
            ])
            ->findOrFail($id);

        return response()->json(
            $admission
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Admission
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $admission =
            ResidentAdmission::findOrFail(
                $id
            );

        $validated =
            $request->validate([
                'admitted_at' =>
                    'sometimes|required|date',

                'admission_type' =>
                    'sometimes|required|string|max:100',

                'admission_source' =>
                    'nullable|string|max:150',

                'reason_for_admission' =>
                    'nullable|string',

                'medical_summary' =>
                    'nullable|string',

                'mobility_notes' =>
                    'nullable|string',

                'dietary_notes' =>
                    'nullable|string',

                'special_care_instructions' =>
                    'nullable|string',

                'belongings_notes' =>
                    'nullable|string',
            ]);

        $admission->update(
            $validated
        );

        return response()->json([
            'message' =>
                'Admission updated successfully.',

            'admission' =>
                $admission->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Admission
    |--------------------------------------------------------------------------
    */

    public function complete($id)
    {
        $admission =
            ResidentAdmission::with(
                'consent'
            )->findOrFail($id);

        if (
            !$admission->consent ||
            $admission->consent->status
                !== 'COMPLETED'
        ) {
            return response()->json([
                'message' =>
                    'Admission consent must be completed before admission completion.',
            ], 422);
        }

        $admission->update([
            'status' =>
                'COMPLETED',

            'completed_at' =>
                now(),

            'completed_by' =>
                auth()->id(),
        ]);

        $admission->resident->update([
            'admission_date' =>
                $admission->admitted_at,

            'status' =>
                'Active',
        ]);

        return response()->json([
            'message' =>
                'Admission completed successfully.',

            'admission' =>
                $admission->fresh([
                    'resident:id,full_name,status',
                    'consent',
                    'completedBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Admission Number
    |--------------------------------------------------------------------------
    */

    private function generateAdmissionNumber()
    {
        do {
            $number =
                'ADM-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(6)
                );
        } while (
            ResidentAdmission::where(
                'admission_number',
                $number
            )->exists()
        );

        return $number;
    }
}