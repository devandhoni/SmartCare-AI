<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentMedication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResidentMedicationController extends Controller
{
    public function store(Request $request, $id)
    {
        $resident = Resident::findOrFail($id);

        $this->ensureResidentIsActive($resident);

        $validated = $request->validate([
            'medication_id' =>
                'required|exists:medications,id',

            'dosage_instruction' =>
                'required|string|max:255',

            'dosage_quantity' =>
                'required|integer|min:1',

            'frequency' =>
                'required|string|max:100',

            'time_slot' =>
                'required|in:AM,PM,NIGHT,OTHER',

            'scheduled_time' =>
                'nullable|date_format:H:i',

            'start_date' =>
                'nullable|date',

            'end_date' =>
                'nullable|date|after_or_equal:start_date',

            'prescribed_by' =>
                'nullable|string|max:255',
        ]);

        $residentMedication = ResidentMedication::create([
            'resident_id' =>
                $resident->id,

            'medication_id' =>
                $validated['medication_id'],

            'dosage_instruction' =>
                $validated['dosage_instruction'],

            'dosage_quantity' =>
                $validated['dosage_quantity'],

            'frequency' =>
                $validated['frequency'],

            'time_slot' =>
                $validated['time_slot'],

            'scheduled_time' =>
                $validated['scheduled_time'] ?? null,

            'start_date' =>
                $validated['start_date'] ?? null,

            'end_date' =>
                $validated['end_date'] ?? null,

            'prescribed_by' =>
                $validated['prescribed_by'] ?? null,
        ]);

        return response()->json([
            'message' =>
                'Medication assigned successfully',

            'resident_medication' =>
                $residentMedication->load('medication'),
        ], 201);
    }

    public function index($id)
    {
        $resident = Resident::findOrFail($id);

        $medications = $resident->medications()
            ->with('medication')
            ->orderBy('time_slot')
            ->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'resident' => [
                'id' =>
                    $resident->id,

                'name' =>
                    $resident->full_name,

                'status' =>
                    $resident->status,
            ],

            'can_manage' =>
                $resident->status === 'Active',

            'medications' =>
                $medications,
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = ResidentMedication::findOrFail($id);

        $resident = Resident::findOrFail(
            $record->resident_id
        );

        $this->ensureResidentIsActive($resident);

        $validated = $request->validate([
            'medication_id' =>
                'sometimes|required|exists:medications,id',

            'dosage_instruction' =>
                'sometimes|required|string|max:255',

            'dosage_quantity' =>
                'sometimes|required|integer|min:1',

            'frequency' =>
                'sometimes|required|string|max:100',

            'time_slot' =>
                'sometimes|required|in:AM,PM,NIGHT,OTHER',

            'scheduled_time' =>
                'nullable|date_format:H:i',

            'start_date' =>
                'nullable|date',

            /*
            | The final effective start/end relationship is validated below
            | so partial updates are handled correctly.
            */
            'end_date' =>
                'nullable|date',

            'prescribed_by' =>
                'nullable|string|max:255',
        ]);

        $effectiveStartDate =
            array_key_exists('start_date', $validated)
                ? $validated['start_date']
                : $record->start_date;

        $effectiveEndDate =
            array_key_exists('end_date', $validated)
                ? $validated['end_date']
                : $record->end_date;

        if (
            $effectiveStartDate
            && $effectiveEndDate
            && Carbon::parse($effectiveEndDate)->lt(
                Carbon::parse($effectiveStartDate)
            )
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'The end date must be a date after or equal to the start date.',
            ]);
        }

        $record->update($validated);

        return response()->json([
            'message' =>
                'Resident medication updated successfully',

            'record' =>
                $record->fresh('medication'),
        ]);
    }

    public function destroy($id)
    {
        $record = ResidentMedication::findOrFail($id);

        $resident = Resident::findOrFail(
            $record->resident_id
        );

        $this->ensureResidentIsActive($resident);

        $record->delete();

        return response()->json([
            'message' =>
                'Resident medication removed successfully',
        ]);
    }

    private function ensureResidentIsActive(Resident $resident): void
    {
        if ($resident->status !== 'Active') {
            abort(
                422,
                'Medication changes can only be made for active residents.'
            );
        }
    }
}
