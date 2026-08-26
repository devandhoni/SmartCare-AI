<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Services\ClinicalTimelineService;
use Illuminate\Http\Request;

class ResidentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | View Active Residents
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(
            Resident::where(
                'status',
                'Active'
            )->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | View Discharged Residents
    |--------------------------------------------------------------------------
    */

    public function archive()
    {
        return response()->json(
            Resident::where(
                'status',
                'Discharged'
            )->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Register New Resident
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        ClinicalTimelineService $timelineService
    ) {
        $data = $request->validate([
            'room_id' =>
                'nullable|exists:rooms,id',

            'full_name' =>
                'required|string|max:255',

            'ic_number' =>
                'nullable|string|max:100',

            'date_of_birth' =>
                'nullable|date',

            'gender' =>
                'nullable|string|max:50',

            'nationality' =>
                'nullable|string|max:100',

            'address' =>
                'nullable|string',

            'phone' =>
                'nullable|string|max:50',

            'email' =>
                'nullable|email|max:255',

            'profile_photo' =>
                'nullable',

            'emergency_contact' =>
                'nullable|string|max:255',

            'emergency_relationship' =>
                'nullable|string|max:100',

            'emergency_phone' =>
                'nullable|string|max:50',

            'blood_type' =>
                'nullable|string|max:20',

            'medical_condition' =>
                'nullable|string',

            'allergies' =>
                'nullable|string',

            'chronic_disease' =>
                'nullable|string',

            'medical_notes' =>
                'nullable|string',

            'admission_date' =>
                'nullable|date',

            'status' =>
                'nullable|string|max:50',
        ]);

        if (!isset($data['status'])) {
            $data['status'] = 'Active';
        }

        $resident =
            Resident::create(
                $data
            );

        $timelineService->record(
            $resident->id,
            ClinicalEventType::ADMISSION,
            'Resident Admission',
            'Resident admitted into SmartCare AI nursing facility.',
            'Resident',
            $resident->id
        );

        return response()->json([
            'message' =>
                'Resident registered successfully',

            'resident' =>
                $resident,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Resident
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $resident =
            Resident::findOrFail(
                $id
            );

        return response()->json(
            $resident
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Resident Information
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $resident =
            Resident::findOrFail(
                $id
            );

        $data = $request->validate([
            'room_id' =>
                'nullable|exists:rooms,id',

            'full_name' =>
                'nullable|string|max:255',

            'ic_number' =>
                'nullable|string|max:100',

            'date_of_birth' =>
                'nullable|date',

            'gender' =>
                'nullable|string|max:50',

            'nationality' =>
                'nullable|string|max:100',

            'address' =>
                'nullable|string',

            'phone' =>
                'nullable|string|max:50',

            'email' =>
                'nullable|email|max:255',

            'emergency_contact' =>
                'nullable|string|max:255',

            'emergency_relationship' =>
                'nullable|string|max:100',

            'emergency_phone' =>
                'nullable|string|max:50',

            'blood_type' =>
                'nullable|string|max:20',

            'medical_condition' =>
                'nullable|string',

            'allergies' =>
                'nullable|string',

            'chronic_disease' =>
                'nullable|string',

            'medical_notes' =>
                'nullable|string',

            'status' =>
                'nullable|string|max:50',
        ]);

        $resident->update(
            $data
        );

        $timelineService->record(
            $resident->id,
            ClinicalEventType::DOCUMENT_UPLOAD,
            'Resident Profile Updated',
            'Resident information updated.',
            'Resident',
            $resident->id
        );

        return response()->json([
            'message' =>
                'Resident updated successfully',

            'resident' =>
                $resident,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Discharge Resident
    |--------------------------------------------------------------------------
    */

    public function destroy(
        $id,
        ClinicalTimelineService $timelineService
    ) {
        $resident =
            Resident::findOrFail(
                $id
            );

        $resident->update([
            'status' =>
                'Discharged',

            'discharge_date' =>
                now(),
        ]);

        $timelineService->record(
            $resident->id,
            ClinicalEventType::DISCHARGE,
            'Resident Discharge',
            'Resident discharged from SmartCare AI nursing facility.',
            'Resident',
            $resident->id
        );

        return response()->json([
            'message' =>
                'Resident discharged successfully',

            'resident' =>
                $resident,
        ]);
    }
}