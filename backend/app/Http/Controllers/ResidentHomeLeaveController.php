<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalEventType;
use App\Models\Resident;
use App\Models\ResidentHomeLeave;
use App\Services\ActivityLogger;
use App\Services\ClinicalTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentHomeLeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = ResidentHomeLeave::with([
            'resident:id,full_name,status',
            'recordedBy:id,full_name',
            'returnRecordedBy:id,full_name',
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

        $leaves = $query
            ->orderByDesc('leave_at')
            ->get();

        return response()->json([
            'home_leaves' => $leaves,
        ]);
    }

    public function residentLeaves($id)
    {
        $resident = Resident::findOrFail($id);

        $leaves = ResidentHomeLeave::with([
            'recordedBy:id,full_name',
            'returnRecordedBy:id,full_name',
        ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('leave_at')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'status' => $resident->status,
            ],

            'home_leaves' => $leaves,
        ]);
    }

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

            'leave_at' => [
                'required',
                'date',
            ],

            'reason_for_leave' => [
                'nullable',
                'string',
                'max:500',
            ],

            'destination' => [
                'nullable',
                'string',
                'max:500',
            ],

            'taken_by_name' => [
                'required',
                'string',
                'max:255',
            ],

            'taken_by_relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'taken_by_contact' => [
                'nullable',
                'string',
                'max:50',
            ],

            'expected_return_at' => [
                'required',
                'date',
                'after:leave_at',
            ],

            'medication_handed_over' => [
                'required',
                'boolean',
            ],

            'medication_instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'belongings_taken' => [
                'required',
                'boolean',
            ],

            'belongings_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'leave_notes' => [
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
                    'Home leave can only be created for active residents.',
            ], 422);
        }

        $existing = ResidentHomeLeave::where(
                'resident_id',
                $resident->id
            )
            ->where(
                'status',
                'ON_LEAVE'
            )
            ->first();

        if ($existing) {
            return response()->json([
                'message' =>
                    'This resident already has an active home leave record.',
                'existing_home_leave' =>
                    $existing,
            ], 422);
        }

        $leave = DB::transaction(function () use (
            $validated,
            $resident,
            $request
        ) {
            return ResidentHomeLeave::create([
                'resident_id' =>
                    $resident->id,

                'leave_reference' =>
                    $this->generateLeaveReference(),

                'leave_at' =>
                    $validated['leave_at'],

                'reason_for_leave' =>
                    $validated['reason_for_leave'] ?? null,

                'destination' =>
                    $validated['destination'] ?? null,

                'taken_by_name' =>
                    $validated['taken_by_name'],

                'taken_by_relationship' =>
                    $validated['taken_by_relationship'] ?? null,

                'taken_by_contact' =>
                    $validated['taken_by_contact'] ?? null,

                'expected_return_at' =>
                    $validated['expected_return_at'],

                'medication_handed_over' =>
                    $validated['medication_handed_over'],

                'medication_instructions' =>
                    $validated['medication_instructions'] ?? null,

                'belongings_taken' =>
                    $validated['belongings_taken'],

                'belongings_notes' =>
                    $validated['belongings_notes'] ?? null,

                'leave_notes' =>
                    $validated['leave_notes'] ?? null,

                'status' =>
                    'ON_LEAVE',

                'recorded_by' =>
                    $request->user()->id,
            ]);
        });

        $timelineService->record(
            $resident->id,

            ClinicalEventType::DOCUMENT_UPLOAD,

            'Home Leave Started',

            'Resident left the facility for home leave. Reference: ' .
                $leave->leave_reference .
                '. Expected return: ' .
                $leave->expected_return_at->format('Y-m-d H:i') .
                '.',

            'ResidentHomeLeaveStarted',

            $leave->id
        );

        $logger->log(
            'Resident Home Leave',
            'CREATE',
            'Home leave started. Reference: ' .
                $leave->leave_reference,
            $resident->id
        );

        return response()->json([
            'message' =>
                'Home leave recorded successfully.',

            'home_leave' =>
                $leave->load([
                    'resident:id,full_name,status',
                    'recordedBy:id,full_name',
                ]),
        ], 201);
    }

    public function show($id)
    {
        $leave = ResidentHomeLeave::with([
            'resident:id,full_name,status',
            'recordedBy:id,full_name',
            'returnRecordedBy:id,full_name',
        ])->findOrFail($id);

        return response()->json([
            'home_leave' => $leave,
        ]);
    }

    public function update(
        Request $request,
        $id,
        ActivityLogger $logger
    ) {
        $leave = ResidentHomeLeave::findOrFail($id);

        if ($leave->status === 'RETURNED') {
            return response()->json([
                'message' =>
                    'A completed home leave record cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'leave_at' => [
                'required',
                'date',
            ],

            'reason_for_leave' => [
                'nullable',
                'string',
                'max:500',
            ],

            'destination' => [
                'nullable',
                'string',
                'max:500',
            ],

            'taken_by_name' => [
                'required',
                'string',
                'max:255',
            ],

            'taken_by_relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'taken_by_contact' => [
                'nullable',
                'string',
                'max:50',
            ],

            'expected_return_at' => [
                'required',
                'date',
                'after:leave_at',
            ],

            'medication_handed_over' => [
                'required',
                'boolean',
            ],

            'medication_instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'belongings_taken' => [
                'required',
                'boolean',
            ],

            'belongings_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'leave_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $leave->update([
            'leave_at' =>
                $validated['leave_at'],

            'reason_for_leave' =>
                $validated['reason_for_leave'] ?? null,

            'destination' =>
                $validated['destination'] ?? null,

            'taken_by_name' =>
                $validated['taken_by_name'],

            'taken_by_relationship' =>
                $validated['taken_by_relationship'] ?? null,

            'taken_by_contact' =>
                $validated['taken_by_contact'] ?? null,

            'expected_return_at' =>
                $validated['expected_return_at'],

            'medication_handed_over' =>
                $validated['medication_handed_over'],

            'medication_instructions' =>
                $validated['medication_instructions'] ?? null,

            'belongings_taken' =>
                $validated['belongings_taken'],

            'belongings_notes' =>
                $validated['belongings_notes'] ?? null,

            'leave_notes' =>
                $validated['leave_notes'] ?? null,
        ]);

        $logger->log(
            'Resident Home Leave',
            'UPDATE',
            'Home leave record updated. Reference: ' .
                $leave->leave_reference,
            $leave->resident_id
        );

        return response()->json([
            'message' =>
                'Home leave updated successfully.',

            'home_leave' =>
                $leave->fresh()->load([
                    'resident:id,full_name,status',
                    'recordedBy:id,full_name',
                ]),
        ]);
    }

    public function recordReturn(
        Request $request,
        $id,
        ActivityLogger $logger,
        ClinicalTimelineService $timelineService
    ) {
        $leave = ResidentHomeLeave::with('resident')
            ->findOrFail($id);

        if ($leave->status === 'RETURNED') {
            return response()->json([
                'message' =>
                    'This home leave has already been completed.',
            ], 422);
        }

        $validated = $request->validate([
            'returned_at' => [
                'required',
                'date',
            ],

            'returned_by_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'returned_by_relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'returned_by_contact' => [
                'nullable',
                'string',
                'max:50',
            ],

            'condition_on_return' => [
                'required',
                'string',
                'max:255',
            ],

            'return_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $returnedAt = \Carbon\Carbon::parse(
            $validated['returned_at']
        );

        if ($returnedAt->lt($leave->leave_at)) {
            return response()->json([
                'message' =>
                    'Return date and time cannot be earlier than the home leave departure date and time.',
            ], 422);
        }

        DB::transaction(function () use (
            $leave,
            $validated,
            $request
        ) {
            $leave->update([
                'status' =>
                    'RETURNED',

                'returned_at' =>
                    $validated['returned_at'],

                'returned_by_name' =>
                    $validated['returned_by_name'] ?? null,

                'returned_by_relationship' =>
                    $validated['returned_by_relationship'] ?? null,

                'returned_by_contact' =>
                    $validated['returned_by_contact'] ?? null,

                'condition_on_return' =>
                    $validated['condition_on_return'],

                'return_notes' =>
                    $validated['return_notes'] ?? null,

                'return_recorded_by' =>
                    $request->user()->id,
            ]);
        });

        $timelineService->record(
            $leave->resident_id,

            ClinicalEventType::DOCUMENT_UPLOAD,

            'Home Leave Returned',

            'Resident returned from home leave. Reference: ' .
                $leave->leave_reference .
                '. Condition on return: ' .
                $leave->condition_on_return .
                '.',

            'ResidentHomeLeaveReturned',

            $leave->id
        );

        $logger->log(
            'Resident Home Leave',
            'UPDATE',
            'Resident returned from home leave. Reference: ' .
                $leave->leave_reference,
            $leave->resident_id
        );

        return response()->json([
            'message' =>
                'Resident return recorded successfully.',

            'home_leave' =>
                $leave->fresh()->load([
                    'resident:id,full_name,status',
                    'recordedBy:id,full_name',
                    'returnRecordedBy:id,full_name',
                ]),
        ]);
    }

    public function destroy(
        $id,
        ActivityLogger $logger
    ) {
        $leave = ResidentHomeLeave::findOrFail($id);

        if ($leave->status === 'RETURNED') {
            return response()->json([
                'message' =>
                    'A completed home leave record cannot be deleted.',
            ], 422);
        }

        $residentId =
            $leave->resident_id;

        $reference =
            $leave->leave_reference;

        $leave->delete();

        $logger->log(
            'Resident Home Leave',
            'DELETE',
            'Home leave record deleted. Reference: ' .
                $reference,
            $residentId
        );

        return response()->json([
            'message' =>
                'Home leave record deleted successfully.',
        ]);
    }

    private function generateLeaveReference(): string
    {
        do {
            $reference =
                'HL-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(Str::random(6));

        } while (
            ResidentHomeLeave::where(
                'leave_reference',
                $reference
            )->exists()
        );

        return $reference;
    }
}