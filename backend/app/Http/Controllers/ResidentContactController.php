<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResidentContactController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Resident Contacts
    |--------------------------------------------------------------------------
    */

    public function index($residentId)
    {
        $resident = Resident::findOrFail(
            $residentId
        );

        $contacts = ResidentContact::with([
                'creator:id,full_name',
            ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('is_primary')
            ->orderByDesc('is_emergency_contact')
            ->orderBy('full_name')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
            ],

            'contacts' => $contacts,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Resident Contact
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        $residentId
    ) {
        $resident = Resident::findOrFail(
            $residentId
        );

        $validated = $request->validate([
            'full_name' =>
                'required|string|max:255',

            'relationship' =>
                'required|string|max:100',

            'phone' =>
                'required|string|max:50',

            'whatsapp_number' =>
                'nullable|string|max:50',

            'is_primary' =>
                'sometimes|boolean',

            'is_emergency_contact' =>
                'sometimes|boolean',

            'whatsapp_enabled' =>
                'sometimes|boolean',

            'medication_notifications_enabled' =>
                'sometimes|boolean',

            'care_notifications_enabled' =>
                'sometimes|boolean',

            'notes' =>
                'nullable|string',
        ]);

        $contact = DB::transaction(
            function () use (
                $validated,
                $resident
            ) {
                $isPrimary =
                    $validated['is_primary']
                    ?? false;

                /*
                |--------------------------------------------------------------------------
                | Only One Primary Contact Per Resident
                |--------------------------------------------------------------------------
                */

                if ($isPrimary) {
                    ResidentContact::where(
                        'resident_id',
                        $resident->id
                    )->update([
                        'is_primary' => false,
                    ]);
                }

                return ResidentContact::create([
                    'resident_id' =>
                        $resident->id,

                    'full_name' =>
                        $validated['full_name'],

                    'relationship' =>
                        $validated['relationship'],

                    'phone' =>
                        $validated['phone'],

                    'whatsapp_number' =>
                        $validated['whatsapp_number']
                        ?? null,

                    'is_primary' =>
                        $isPrimary,

                    'is_emergency_contact' =>
                        $validated['is_emergency_contact']
                        ?? false,

                    'whatsapp_enabled' =>
                        $validated['whatsapp_enabled']
                        ?? true,

                    'medication_notifications_enabled' =>
                        $validated[
                            'medication_notifications_enabled'
                        ] ?? true,

                    'care_notifications_enabled' =>
                        $validated[
                            'care_notifications_enabled'
                        ] ?? false,

                    'notes' =>
                        $validated['notes']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);
            }
        );

        $contact->load(
            'creator:id,full_name'
        );

        return response()->json([
            'message' =>
                'Resident contact created successfully.',

            'contact' =>
                $contact,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Contact
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $contact =
            ResidentContact::with([
                'resident:id,full_name',
                'creator:id,full_name',
            ])
            ->findOrFail($id);

        return response()->json(
            $contact
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Resident Contact
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $contact =
            ResidentContact::findOrFail($id);

        $validated = $request->validate([
            'full_name' =>
                'sometimes|required|string|max:255',

            'relationship' =>
                'sometimes|required|string|max:100',

            'phone' =>
                'sometimes|required|string|max:50',

            'whatsapp_number' =>
                'nullable|string|max:50',

            'is_primary' =>
                'sometimes|boolean',

            'is_emergency_contact' =>
                'sometimes|boolean',

            'whatsapp_enabled' =>
                'sometimes|boolean',

            'medication_notifications_enabled' =>
                'sometimes|boolean',

            'care_notifications_enabled' =>
                'sometimes|boolean',

            'notes' =>
                'nullable|string',
        ]);

        DB::transaction(
            function () use (
                $validated,
                $contact
            ) {
                /*
                |--------------------------------------------------------------------------
                | Primary Contact Enforcement
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'is_primary',
                        $validated
                    )
                    &&
                    $validated['is_primary']
                ) {
                    ResidentContact::where(
                        'resident_id',
                        $contact->resident_id
                    )
                        ->where(
                            'id',
                            '!=',
                            $contact->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $contact->update(
                    $validated
                );
            }
        );

        $contact->refresh();

        $contact->load(
            'creator:id,full_name'
        );

        return response()->json([
            'message' =>
                'Resident contact updated successfully.',

            'contact' =>
                $contact,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Resident Contact
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $contact =
            ResidentContact::findOrFail($id);

        $contact->delete();

        return response()->json([
            'message' =>
                'Resident contact deleted successfully.',
        ]);
    }
}