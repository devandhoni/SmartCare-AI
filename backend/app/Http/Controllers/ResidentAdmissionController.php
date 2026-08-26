<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentAdmissionController extends Controller
{
    public function index()
    {
        $admissions = ResidentAdmission::with([
                'resident:id,room_id,full_name,status',
                'resident.room:id,room_number,floor,room_type,status',
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent',
                'medicalHistory',
                'hospitalizations',
            ])
            ->orderByDesc('admitted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'admissions' => $admissions,
        ]);
    }

    public function residentAdmissions($residentId)
    {
        $resident = Resident::findOrFail($residentId);

        $admissions = ResidentAdmission::with([
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent',
                'medicalHistory',
                'hospitalizations',
            ])
            ->where('resident_id', $resident->id)
            ->orderByDesc('admitted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
            ],
            'admissions' => $admissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'room_id' => 'nullable|exists:rooms,id',
            'admitted_at' => 'required|date',
            'admission_type' => 'nullable|string|max:100',
            'admission_source' => 'nullable|string|max:150',
            'reason_for_admission' => 'nullable|string',
            'medical_summary' => 'nullable|string',
            'mobility_notes' => 'nullable|string',
            'dietary_notes' => 'nullable|string',
            'special_care_instructions' => 'nullable|string',
            'belongings_notes' => 'nullable|string',

            'medical_history' => 'nullable|array',
            'medical_history.primary_psychiatric_diagnosis' => 'nullable|string',
            'medical_history.other_diagnoses' => 'nullable|string',
            'medical_history.tobacco_use' => 'nullable|boolean',
            'medical_history.tobacco_amount_frequency' => 'nullable|string|max:255',
            'medical_history.alcohol_use' => 'nullable|boolean',
            'medical_history.alcohol_amount_frequency' => 'nullable|string|max:255',
            'medical_history.drug_use' => 'nullable|boolean',
            'medical_history.drug_type_frequency' => 'nullable|string|max:255',
            'medical_history.family_psychiatric_history' => 'nullable|boolean',
            'medical_history.family_psychiatric_history_details' => 'nullable|string',
            'medical_history.family_substance_abuse_history' => 'nullable|boolean',
            'medical_history.family_substance_abuse_history_details' => 'nullable|string',
            'medical_history.initial_observations' => 'nullable|string',
            'medical_history.additional_comments' => 'nullable|string',

            'hospitalizations' => 'nullable|array',
            'hospitalizations.*.hospital_name' => 'nullable|string|max:255',
            'hospitalizations.*.hospitalization_date' => 'nullable|date',
            'hospitalizations.*.reason' => 'nullable|string',
            'hospitalizations.*.notes' => 'nullable|string',
        ]);

        $admission = DB::transaction(function () use ($validated) {
            $resident = Resident::findOrFail($validated['resident_id']);

            if (array_key_exists('room_id', $validated)) {
                $resident->update([
                    'room_id' => $validated['room_id'],
                ]);
            }

            $admission = ResidentAdmission::create([
                'resident_id' => $resident->id,
                'admission_number' => $this->generateAdmissionNumber(),
                'admitted_at' => $validated['admitted_at'],
                'admission_type' => $validated['admission_type'] ?? 'NEW_ADMISSION',
                'admission_source' => $validated['admission_source'] ?? null,
                'reason_for_admission' => $validated['reason_for_admission'] ?? null,
                'medical_summary' => $validated['medical_summary'] ?? null,
                'mobility_notes' => $validated['mobility_notes'] ?? null,
                'dietary_notes' => $validated['dietary_notes'] ?? null,
                'special_care_instructions' =>
                    $validated['special_care_instructions'] ?? null,
                'belongings_notes' => $validated['belongings_notes'] ?? null,
                'status' => 'DRAFT',
                'admitted_by' => auth()->id(),
            ]);

            if (!empty($validated['medical_history'])) {
                $admission->medicalHistory()->create(
                    array_merge(
                        $validated['medical_history'],
                        [
                            'resident_id' => $resident->id,
                        ]
                    )
                );
            }

            foreach (($validated['hospitalizations'] ?? []) as $hospitalization) {
                if (
                    empty($hospitalization['hospital_name']) &&
                    empty($hospitalization['hospitalization_date']) &&
                    empty($hospitalization['reason']) &&
                    empty($hospitalization['notes'])
                ) {
                    continue;
                }

                $admission->hospitalizations()->create(
                    array_merge(
                        $hospitalization,
                        [
                            'resident_id' => $resident->id,
                        ]
                    )
                );
            }

            return $admission;
        });

        $admission->load([
            'resident:id,room_id,full_name,status',
            'resident.room:id,room_number,floor,room_type,status',
            'admittedBy:id,full_name',
            'medicalHistory',
            'hospitalizations',
        ]);

        return response()->json([
            'message' => 'Admission created successfully.',
            'admission' => $admission,
        ], 201);
    }

    public function show($id)
    {
        $admission = ResidentAdmission::with([
                'resident',
                'resident.room:id,room_number,floor,room_type,status',
                'admittedBy:id,full_name',
                'completedBy:id,full_name',
                'consent.witness:id,full_name',
                'medicalHistory',
                'hospitalizations',
            ])
            ->findOrFail($id);

        return response()->json(
            $admission
        );
    }

    public function update(Request $request, $id)
    {
        $admission = ResidentAdmission::with([
                'resident',
                'medicalHistory',
                'hospitalizations',
            ])
            ->findOrFail($id);

        if ($admission->status === 'COMPLETED') {
            return response()->json([
                'message' =>
                    'Completed admissions cannot be edited through the draft admission endpoint.',
            ], 422);
        }

        $validated = $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'admitted_at' => 'sometimes|required|date',
            'admission_type' => 'sometimes|required|string|max:100',
            'admission_source' => 'nullable|string|max:150',
            'reason_for_admission' => 'nullable|string',
            'medical_summary' => 'nullable|string',
            'mobility_notes' => 'nullable|string',
            'dietary_notes' => 'nullable|string',
            'special_care_instructions' => 'nullable|string',
            'belongings_notes' => 'nullable|string',

            'medical_history' => 'nullable|array',
            'medical_history.primary_psychiatric_diagnosis' => 'nullable|string',
            'medical_history.other_diagnoses' => 'nullable|string',
            'medical_history.tobacco_use' => 'nullable|boolean',
            'medical_history.tobacco_amount_frequency' => 'nullable|string|max:255',
            'medical_history.alcohol_use' => 'nullable|boolean',
            'medical_history.alcohol_amount_frequency' => 'nullable|string|max:255',
            'medical_history.drug_use' => 'nullable|boolean',
            'medical_history.drug_type_frequency' => 'nullable|string|max:255',
            'medical_history.family_psychiatric_history' => 'nullable|boolean',
            'medical_history.family_psychiatric_history_details' => 'nullable|string',
            'medical_history.family_substance_abuse_history' => 'nullable|boolean',
            'medical_history.family_substance_abuse_history_details' => 'nullable|string',
            'medical_history.initial_observations' => 'nullable|string',
            'medical_history.additional_comments' => 'nullable|string',

            'hospitalizations' => 'nullable|array',
            'hospitalizations.*.hospital_name' => 'nullable|string|max:255',
            'hospitalizations.*.hospitalization_date' => 'nullable|date',
            'hospitalizations.*.reason' => 'nullable|string',
            'hospitalizations.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $admission) {
            if (array_key_exists('room_id', $validated)) {
                $admission->resident->update([
                    'room_id' => $validated['room_id'],
                ]);
            }

            $admissionFields = collect($validated)
                ->except([
                    'room_id',
                    'medical_history',
                    'hospitalizations',
                ])
                ->all();

            if (!empty($admissionFields)) {
                $admission->update($admissionFields);
            }

            if (array_key_exists('medical_history', $validated)) {
                $history = $validated['medical_history'] ?? [];

                if (!empty($history)) {
                    $admission->medicalHistory()->updateOrCreate(
                        [
                            'resident_admission_id' => $admission->id,
                        ],
                        array_merge(
                            $history,
                            [
                                'resident_id' => $admission->resident_id,
                            ]
                        )
                    );
                }
            }

            if (array_key_exists('hospitalizations', $validated)) {
                $admission->hospitalizations()->delete();

                foreach (($validated['hospitalizations'] ?? []) as $hospitalization) {
                    if (
                        empty($hospitalization['hospital_name']) &&
                        empty($hospitalization['hospitalization_date']) &&
                        empty($hospitalization['reason']) &&
                        empty($hospitalization['notes'])
                    ) {
                        continue;
                    }

                    $admission->hospitalizations()->create(
                        array_merge(
                            $hospitalization,
                            [
                                'resident_id' => $admission->resident_id,
                            ]
                        )
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Admission updated successfully.',
            'admission' => $admission->fresh([
                'resident:id,room_id,full_name,status',
                'resident.room:id,room_number,floor,room_type,status',
                'medicalHistory',
                'hospitalizations',
                'consent',
            ]),
        ]);
    }

    public function complete($id)
    {
        $admission = ResidentAdmission::with([
                'resident',
                'consent',
                'medicalHistory',
                'hospitalizations',
            ])
            ->findOrFail($id);

        if ($admission->status === 'COMPLETED') {
            return response()->json([
                'message' => 'Admission has already been completed.',
                'admission' => $admission,
            ]);
        }

        if (
            !$admission->consent ||
            $admission->consent->status !== 'COMPLETED'
        ) {
            return response()->json([
                'message' =>
                    'Admission consent must be completed before admission completion.',
            ], 422);
        }

        DB::transaction(function () use ($admission) {
            $admission->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            $admission->resident->update([
                'admission_date' =>
                    $admission->admitted_at->toDateString(),
                'status' => 'Active',
            ]);
        });

        return response()->json([
            'message' => 'Admission completed successfully.',
            'admission' => $admission->fresh([
                'resident:id,room_id,full_name,status,admission_date',
                'resident.room:id,room_number,floor,room_type,status',
                'consent.witness:id,full_name',
                'medicalHistory',
                'hospitalizations',
                'completedBy:id,full_name',
            ]),
        ]);
    }

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