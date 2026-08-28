<?php

namespace App\Http\Controllers;

use App\Models\CareRecord;
use App\Models\Resident;
use Illuminate\Http\Request;

class CareRecordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Resident Care Records
    |--------------------------------------------------------------------------
    */

    public function index($residentId)
    {
        $resident = Resident::findOrFail($residentId);

        $records = CareRecord::with([
                'recorder:id,full_name',
            ])
            ->where('resident_id', $resident->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
            ],

            'care_records' => $records,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Care Record
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        $residentId
    ) {
        $resident = Resident::findOrFail($residentId);

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' => 'Care records can only be added for active residents.',
            ], 422);
        }

        $validated = $request->validate([
            'care_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',

            'notes' => 'nullable|string',

            'care_status' =>
                'required|in:COMPLETED,OBSERVED,NEEDS_ATTENTION',

            'recorded_at' =>
                'nullable|date',
        ]);

        $record = CareRecord::create([
            'resident_id' => $resident->id,

            'care_type' =>
                $validated['care_type'],

            'title' =>
                $validated['title'],

            'notes' =>
                $validated['notes'] ?? null,

            'care_status' =>
                $validated['care_status'],

            'recorded_at' =>
                $validated['recorded_at'] ?? now(),

            'recorded_by' =>
                auth()->id(),
        ]);

        $record->load(
            'recorder:id,full_name'
        );

        return response()->json([
            'message' =>
                'Care record created successfully.',

            'care_record' =>
                $record,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | View Single Record
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $record = CareRecord::with([
                'resident:id,full_name',
                'recorder:id,full_name',
            ])
            ->findOrFail($id);

        return response()->json(
            $record
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Care Record
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $record =
            CareRecord::findOrFail($id);

        $validated = $request->validate([
            'care_type' =>
                'sometimes|required|string|max:100',

            'title' =>
                'sometimes|required|string|max:255',

            'notes' =>
                'nullable|string',

            'care_status' =>
                'sometimes|required|in:COMPLETED,OBSERVED,NEEDS_ATTENTION',

            'recorded_at' =>
                'sometimes|required|date',
        ]);

        $record->update(
            $validated
        );

        $record->load(
            'recorder:id,full_name'
        );

        return response()->json([
            'message' =>
                'Care record updated successfully.',

            'care_record' =>
                $record,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Care Record
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $record =
            CareRecord::findOrFail($id);

        $record->delete();

        return response()->json([
            'message' =>
                'Care record deleted successfully.',
        ]);
    }
}