<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Models\ResidentVisitor;
use App\Services\ActivityLogger;
use App\Services\ClinicalTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentVisitorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Visitor Records
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = ResidentVisitor::with([
            'resident:id,full_name,status',
            'checkedInBy:id,full_name',
            'checkedOutBy:id,full_name',
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
                'checked_in_at',
                $request->date
            );
        }

        $visitors = $query
            ->orderByDesc('checked_in_at')
            ->get();

        return response()->json([
            'visitors' => $visitors,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident Visitor History
    |--------------------------------------------------------------------------
    */

    public function residentVisitors($id)
    {
        $resident = Resident::findOrFail($id);

        $visitors = ResidentVisitor::with([
            'checkedInBy:id,full_name',
            'checkedOutBy:id,full_name',
        ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('checked_in_at')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'status' => $resident->status,
            ],

            'visitors' => $visitors,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Visitor Check-In
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

            'visitor_name' => [
                'required',
                'string',
                'max:255',
            ],

            'relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'id_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purpose_of_visit' => [
                'nullable',
                'string',
                'max:500',
            ],

            'number_of_visitors' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],

            'visit_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'checked_in_at' => [
                'required',
                'date',
            ],
        ]);

        $resident = Resident::findOrFail(
            $validated['resident_id']
        );

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' =>
                    'Visitors can only be checked in for active residents.',
            ], 422);
        }

        $visitor = DB::transaction(function () use (
            $validated,
            $resident,
            $request
        ) {
            return ResidentVisitor::create([
                'resident_id' =>
                    $resident->id,

                'visit_reference' =>
                    $this->generateVisitReference(),

                'visitor_name' =>
                    $validated['visitor_name'],

                'relationship' =>
                    $validated['relationship'] ?? null,

                'contact_number' =>
                    $validated['contact_number'] ?? null,

                'id_number' =>
                    $validated['id_number'] ?? null,

                'purpose_of_visit' =>
                    $validated['purpose_of_visit'] ?? null,

                'number_of_visitors' =>
                    $validated['number_of_visitors'],

                'visit_notes' =>
                    $validated['visit_notes'] ?? null,

                'checked_in_at' =>
                    $validated['checked_in_at'],

                'checked_in_by' =>
                    $request->user()->id,

                'status' =>
                    'CHECKED_IN',
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

            'Visitor Checked In',

            'Visitor ' .
                $visitor->visitor_name .
                ' checked in to visit resident. Reference: ' .
                $visitor->visit_reference .
                '.',

            'ResidentVisitorCheckedIn',

            $visitor->id
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Resident Visitor',
            'CREATE',
            'Visitor checked in. Reference: ' .
                $visitor->visit_reference,
            $resident->id
        );

        return response()->json([
            'message' =>
                'Visitor checked in successfully.',

            'visitor' =>
                $visitor->load([
                    'resident:id,full_name,status',
                    'checkedInBy:id,full_name',
                ]),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Visitor Record
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $visitor = ResidentVisitor::with([
            'resident:id,full_name,status',
            'checkedInBy:id,full_name',
            'checkedOutBy:id,full_name',
        ])->findOrFail($id);

        return response()->json([
            'visitor' => $visitor,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Active Visit
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id,
        ActivityLogger $logger
    ) {
        $visitor = ResidentVisitor::findOrFail($id);

        if ($visitor->status === 'CHECKED_OUT') {
            return response()->json([
                'message' =>
                    'A completed visitor record cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'visitor_name' => [
                'required',
                'string',
                'max:255',
            ],

            'relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'id_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purpose_of_visit' => [
                'nullable',
                'string',
                'max:500',
            ],

            'number_of_visitors' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],

            'visit_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'checked_in_at' => [
                'required',
                'date',
            ],
        ]);

        $visitor->update([
            'visitor_name' =>
                $validated['visitor_name'],

            'relationship' =>
                $validated['relationship'] ?? null,

            'contact_number' =>
                $validated['contact_number'] ?? null,

            'id_number' =>
                $validated['id_number'] ?? null,

            'purpose_of_visit' =>
                $validated['purpose_of_visit'] ?? null,

            'number_of_visitors' =>
                $validated['number_of_visitors'],

            'visit_notes' =>
                $validated['visit_notes'] ?? null,

            'checked_in_at' =>
                $validated['checked_in_at'],
        ]);

        $logger->log(
            'Resident Visitor',
            'UPDATE',
            'Visitor record updated. Reference: ' .
                $visitor->visit_reference,
            $visitor->resident_id
        );

        return response()->json([
            'message' =>
                'Visitor record updated successfully.',

            'visitor' =>
                $visitor->fresh()->load([
                    'resident:id,full_name,status',
                    'checkedInBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Visitor Check-Out
    |--------------------------------------------------------------------------
    */

    public function checkout(
        Request $request,
        $id,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $visitor = ResidentVisitor::with('resident')
            ->findOrFail($id);

        if ($visitor->status === 'CHECKED_OUT') {
            return response()->json([
                'message' =>
                    'This visitor has already checked out.',
            ], 422);
        }

        $validated = $request->validate([
            'checked_out_at' => [
                'required',
                'date',
            ],

            'checkout_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $checkedOutAt =
            \Carbon\Carbon::parse(
                $validated['checked_out_at']
            );

        if (
            $checkedOutAt->lt(
                $visitor->checked_in_at
            )
        ) {
            return response()->json([
                'message' =>
                    'Check-out date and time cannot be earlier than check-in date and time.',
            ], 422);
        }

        DB::transaction(function () use (
            $visitor,
            $validated,
            $request
        ) {
            $visitor->update([
                'status' =>
                    'CHECKED_OUT',

                'checked_out_at' =>
                    $validated['checked_out_at'],

                'checked_out_by' =>
                    $request->user()->id,

                'checkout_notes' =>
                    $validated['checkout_notes'] ?? null,
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $timelineService->record(
            $visitor->resident_id,

            ClinicalEventType::DOCUMENT_UPLOAD,

            'Visitor Checked Out',

            'Visitor ' .
                $visitor->visitor_name .
                ' checked out. Reference: ' .
                $visitor->visit_reference .
                '.',

            'ResidentVisitorCheckedOut',

            $visitor->id
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $logger->log(
            'Resident Visitor',
            'UPDATE',
            'Visitor checked out. Reference: ' .
                $visitor->visit_reference,
            $visitor->resident_id
        );

        return response()->json([
            'message' =>
                'Visitor checked out successfully.',

            'visitor' =>
                $visitor->fresh()->load([
                    'resident:id,full_name,status',
                    'checkedInBy:id,full_name',
                    'checkedOutBy:id,full_name',
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Active Visit
    |--------------------------------------------------------------------------
    */

    public function destroy(
        $id,
        ActivityLogger $logger
    ) {
        $visitor = ResidentVisitor::findOrFail($id);

        if ($visitor->status === 'CHECKED_OUT') {
            return response()->json([
                'message' =>
                    'A completed visitor record cannot be deleted.',
            ], 422);
        }

        $residentId =
            $visitor->resident_id;

        $reference =
            $visitor->visit_reference;

        $visitor->delete();

        $logger->log(
            'Resident Visitor',
            'DELETE',
            'Visitor record deleted. Reference: ' .
                $reference,
            $residentId
        );

        return response()->json([
            'message' =>
                'Visitor record deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Visit Reference
    |--------------------------------------------------------------------------
    */

    private function generateVisitReference(): string
    {
        do {
            $reference =
                'VIS-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(6)
                );

        } while (
            ResidentVisitor::where(
                'visit_reference',
                $reference
            )->exists()
        );

        return $reference;
    }
}