<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | View All Medications
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(
            Medication::orderBy('medicine_name')
                ->orderBy('dosage')
                ->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Medication
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicine_name' =>
                'required|string|max:255',

            'category' =>
                'nullable|string|max:100',

            'dosage' =>
                'nullable|string|max:100',

            'unit' =>
                'nullable|string|max:100',

            'supplier' =>
                'nullable|string|max:255',
        ]);

        $duplicate = Medication::where(
                'medicine_name',
                $validated['medicine_name']
            )
            ->where(
                'dosage',
                $validated['dosage'] ?? null
            )
            ->where(
                'unit',
                $validated['unit'] ?? null
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'A medicine with the same name, dosage and unit already exists.',
            ], 422);
        }

        $medication =
            Medication::create(
                $validated
            );

        return response()->json([
            'message' =>
                'Medication created successfully',

            'medication' =>
                $medication,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Medication
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        return response()->json(
            Medication::findOrFail($id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Medication
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $medication =
            Medication::findOrFail(
                $id
            );

        $validated = $request->validate([
            'medicine_name' =>
                'sometimes|required|string|max:255',

            'category' =>
                'nullable|string|max:100',

            'dosage' =>
                'nullable|string|max:100',

            'unit' =>
                'nullable|string|max:100',

            'supplier' =>
                'nullable|string|max:255',
        ]);

        $medicineName =
            $validated['medicine_name']
            ?? $medication->medicine_name;

        $dosage =
            array_key_exists(
                'dosage',
                $validated
            )
                ? $validated['dosage']
                : $medication->dosage;

        $unit =
            array_key_exists(
                'unit',
                $validated
            )
                ? $validated['unit']
                : $medication->unit;

        $duplicate = Medication::where(
                'medicine_name',
                $medicineName
            )
            ->where(
                'dosage',
                $dosage
            )
            ->where(
                'unit',
                $unit
            )
            ->where(
                'id',
                '!=',
                $medication->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'Another medicine with the same name, dosage and unit already exists.',
            ], 422);
        }

        $medication->update(
            $validated
        );

        return response()->json([
            'message' =>
                'Medication updated successfully',

            'medication' =>
                $medication,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Medication
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $medication =
            Medication::findOrFail(
                $id
            );

        if (
            $medication
                ->residentMedications()
                ->exists()
        ) {
            return response()->json([
                'message' =>
                    'This medication cannot be deleted because it is already assigned to one or more residents.',
            ], 422);
        }

        $medication->delete();

        return response()->json([
            'message' =>
                'Medication deleted successfully',
        ]);
    }
}