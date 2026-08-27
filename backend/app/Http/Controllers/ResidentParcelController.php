<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Models\ResidentParcel;
use App\Services\ActivityLogger;
use App\Services\ClinicalTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentParcelController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Parcels
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = ResidentParcel::with([
            'resident:id,full_name,status',
            'receivedBy:id,full_name',
            'collectionRecordedBy:id,full_name',
        ]);

        if ($request->filled('resident_id')) {
            $query->where(
                'resident_id',
                $request->integer('resident_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                strtoupper($request->status)
            );
        }

        if ($request->filled('date')) {
            $query->whereDate(
                'received_at',
                $request->date
            );
        }

        $parcels = $query
            ->orderByDesc('received_at')
            ->get();

        return response()->json([
            'parcels' => $parcels,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident Parcel History
    |--------------------------------------------------------------------------
    */

    public function residentParcels($id)
    {
        $resident = Resident::findOrFail($id);

        $parcels = ResidentParcel::with([
            'receivedBy:id,full_name',
            'collectionRecordedBy:id,full_name',
        ])
            ->where('resident_id', $resident->id)
            ->orderByDesc('received_at')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'status' => $resident->status,
            ],

            'parcels' => $parcels,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Receive Parcel
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $validated = $request->validate([
            'resident_id' => [
                'required',
                'integer',
                'exists:residents,id',
            ],

            'sender_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'courier_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'tracking_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'parcel_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'condition_on_arrival' => [
                'required',
                'in:GOOD,DAMAGED,OPENED,OTHER',
            ],

            'received_at' => [
                'required',
                'date',
            ],

            'parcel_checked' => [
                'required',
                'boolean',
            ],

            'resident_notified' => [
                'nullable',
                'boolean',
            ],

            'receiving_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $resident = Resident::findOrFail(
            $validated['resident_id']
        );

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' =>
                    'Parcels can only be received for active residents.',
            ], 422);
        }

        $parcel = DB::transaction(function () use (
            $validated,
            $resident,
            $request
        ) {
            return ResidentParcel::create([
                'resident_id' =>
                    $resident->id,

                'parcel_reference' =>
                    $this->generateParcelReference(),

                'sender_name' =>
                    $validated['sender_name'] ?? null,

                'courier_name' =>
                    $validated['courier_name'] ?? null,

                'tracking_number' =>
                    $validated['tracking_number'] ?? null,

                'parcel_description' =>
                    $validated['parcel_description'] ?? null,

                'quantity' =>
                    $validated['quantity'],

                'condition_on_arrival' =>
                    $validated['condition_on_arrival'],

                'received_at' =>
                    $validated['received_at'],

                'received_by' =>
                    $request->user()->id,

                'parcel_checked' =>
                    $validated['parcel_checked'],

                'resident_notified' =>
                    $validated['resident_notified'] ?? false,

                'receiving_notes' =>
                    $validated['receiving_notes'] ?? null,

                'status' =>
                    'RECEIVED',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $timelineService->record(
            $resident->id,
            ClinicalEventType::DOCUMENT_UPLOAD,
            'Parcel Received',
            'Incoming parcel received for resident. Parcel reference: ' .
                $parcel->parcel_reference . '.',
            'ResidentParcelReceived',
            $parcel->id
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Resident Parcel',
            'CREATE',
            'Incoming parcel recorded for resident. Reference: ' .
                $parcel->parcel_reference,
            $resident->id
        );

        return response()->json([
            'message' =>
                'Parcel received successfully.',

            'parcel' =>
                $parcel->load([
                    'resident:id,full_name,status',
                    'receivedBy:id,full_name',
                ]),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Parcel
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $parcel = ResidentParcel::with([
            'resident:id,full_name,status',
            'receivedBy:id,full_name',
            'collectionRecordedBy:id,full_name',
        ])->findOrFail($id);

        return response()->json([
            'parcel' => $parcel,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Received Parcel
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id,
        ActivityLogger $logger
    ) {
        $parcel = ResidentParcel::findOrFail($id);

        if ($parcel->status === 'COLLECTED') {
            return response()->json([
                'message' =>
                    'A collected parcel cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'sender_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'courier_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'tracking_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'parcel_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'condition_on_arrival' => [
                'required',
                'in:GOOD,DAMAGED,OPENED,OTHER',
            ],

            'received_at' => [
                'required',
                'date',
            ],

            'parcel_checked' => [
                'required',
                'boolean',
            ],

            'resident_notified' => [
                'nullable',
                'boolean',
            ],

            'receiving_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $parcel->update([
            'sender_name' =>
                $validated['sender_name'] ?? null,

            'courier_name' =>
                $validated['courier_name'] ?? null,

            'tracking_number' =>
                $validated['tracking_number'] ?? null,

            'parcel_description' =>
                $validated['parcel_description'] ?? null,

            'quantity' =>
                $validated['quantity'],

            'condition_on_arrival' =>
                $validated['condition_on_arrival'],

            'received_at' =>
                $validated['received_at'],

            'parcel_checked' =>
                $validated['parcel_checked'],

            'resident_notified' =>
                $validated['resident_notified'] ?? false,

            'receiving_notes' =>
                $validated['receiving_notes'] ?? null,
        ]);

        $logger->log(
            'Resident Parcel',
            'UPDATE',
            'Parcel record updated. Reference: ' .
                $parcel->parcel_reference,
            $parcel->resident_id
        );

        return response()->json([
            'message' =>
                'Parcel updated successfully.',

            'parcel' =>
                $parcel->fresh()->load([
                    'resident:id,full_name,status',
                    'receivedBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Resident as Notified
    |--------------------------------------------------------------------------
    */

    public function markNotified(
        $id,
        ActivityLogger $logger
    ) {
        $parcel = ResidentParcel::findOrFail($id);

        if ($parcel->status === 'COLLECTED') {
            return response()->json([
                'message' =>
                    'The parcel has already been collected.',
            ], 422);
        }

        if (!$parcel->resident_notified) {
            $parcel->update([
                'resident_notified' => true,
            ]);

            $logger->log(
                'Resident Parcel',
                'UPDATE',
                'Resident marked as notified for parcel. Reference: ' .
                    $parcel->parcel_reference,
                $parcel->resident_id
            );
        }

        return response()->json([
            'message' =>
                'Resident notification recorded.',

            'parcel' =>
                $parcel->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Collect Parcel
    |--------------------------------------------------------------------------
    */

    public function collect(
        Request $request,
        $id,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $parcel = ResidentParcel::with('resident')
            ->findOrFail($id);

        if ($parcel->status === 'COLLECTED') {
            return response()->json([
                'message' =>
                    'This parcel has already been collected.',
            ], 422);
        }

        $validated = $request->validate([
            'collected_at' => [
                'required',
                'date',
            ],

            'collected_by_name' => [
                'required',
                'string',
                'max:255',
            ],

            'collected_by_relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'collected_by_contact' => [
                'nullable',
                'string',
                'max:50',
            ],

            'collection_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use (
            $parcel,
            $validated,
            $request
        ) {
            $parcel->update([
                'status' =>
                    'COLLECTED',

                'collected_at' =>
                    $validated['collected_at'],

                'collected_by_name' =>
                    $validated['collected_by_name'],

                'collected_by_relationship' =>
                    $validated['collected_by_relationship'] ?? null,

                'collected_by_contact' =>
                    $validated['collected_by_contact'] ?? null,

                'collection_recorded_by' =>
                    $request->user()->id,

                'collection_notes' =>
                    $validated['collection_notes'] ?? null,
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $timelineService->record(
            $parcel->resident_id,
            ClinicalEventType::DOCUMENT_UPLOAD,
            'Parcel Collected',
            'Parcel ' .
                $parcel->parcel_reference .
                ' collected by ' .
                $parcel->collected_by_name .
                '.',
            'ResidentParcelCollected',
            $parcel->id
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Resident Parcel',
            'UPDATE',
            'Parcel collected. Reference: ' .
                $parcel->parcel_reference,
            $parcel->resident_id
        );

        return response()->json([
            'message' =>
                'Parcel collection recorded successfully.',

            'parcel' =>
                $parcel->fresh()->load([
                    'resident:id,full_name,status',
                    'receivedBy:id,full_name',
                    'collectionRecordedBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Parcel
    |--------------------------------------------------------------------------
    */

    public function destroy(
        $id,
        ActivityLogger $logger
    ) {
        $parcel = ResidentParcel::findOrFail($id);

        if ($parcel->status === 'COLLECTED') {
            return response()->json([
                'message' =>
                    'A collected parcel record cannot be deleted.',
            ], 422);
        }

        $residentId = $parcel->resident_id;

        $reference = $parcel->parcel_reference;

        $parcel->delete();

        $logger->log(
            'Resident Parcel',
            'DELETE',
            'Parcel record deleted. Reference: ' .
                $reference,
            $residentId
        );

        return response()->json([
            'message' =>
                'Parcel record deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Parcel Reference
    |--------------------------------------------------------------------------
    */

    private function generateParcelReference(): string
    {
        do {
            $reference =
                'PAR-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(Str::random(6));

        } while (
            ResidentParcel::where(
                'parcel_reference',
                $reference
            )->exists()
        );

        return $reference;
    }
}