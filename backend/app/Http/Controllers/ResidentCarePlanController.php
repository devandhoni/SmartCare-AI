<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentCarePlan;
use Illuminate\Http\Request;

class ResidentCarePlanController extends Controller
{
    /**
     * List all care plans for a resident.
     *
     * Historical plans remain visible for discharged/deceased residents.
     */
    public function index($residentId)
    {
        $resident = Resident::findOrFail($residentId);

        $plans = ResidentCarePlan::with([
            'createdBy:id,full_name',
            'updatedBy:id,full_name',
        ])
            ->where('resident_id', $resident->id)
            ->orderByDesc('is_active')
            ->orderBy('scheduled_time')
            ->orderBy('title')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'status' => $resident->status,
            ],
            'care_plans' => $plans,
        ]);
    }

    /**
     * Create a recurring care-plan requirement.
     */
    public function store(Request $request, $residentId)
    {
        $resident = Resident::findOrFail($residentId);

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' => 'Care plans can only be added for active residents.',
            ], 422);
        }

        $validated = $request->validate([
            'care_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',

            'frequency' => 'required|in:DAILY,WEEKLY,EVERY_SHIFT,PRN',
            'time_slot' => 'nullable|in:AM,PM,NIGHT,ANY',
            'scheduled_time' => 'nullable|date_format:H:i',

            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'in:MON,TUE,WED,THU,FRI,SAT,SUN',

            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            'priority' => 'nullable|in:LOW,NORMAL,HIGH,URGENT,CRITICAL',
        ]);

        $plan = ResidentCarePlan::create([
            'resident_id' => $resident->id,
            'care_type' => $validated['care_type'],
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,

            'frequency' => $validated['frequency'],
            'time_slot' => $validated['time_slot'] ?? null,
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'days_of_week' => $validated['days_of_week'] ?? null,

            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'end_date' => $validated['end_date'] ?? null,

            'priority' => $validated['priority'] ?? 'NORMAL',
            'is_active' => true,

            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Resident care plan created successfully.',
            'care_plan' => $plan->load([
                'createdBy:id,full_name',
                'updatedBy:id,full_name',
            ]),
        ], 201);
    }

    /**
     * View one care plan.
     */
    public function show($id)
    {
        $plan = ResidentCarePlan::with([
            'resident:id,full_name,status',
            'createdBy:id,full_name',
            'updatedBy:id,full_name',
        ])->findOrFail($id);

        return response()->json([
            'care_plan' => $plan,
        ]);
    }

    /**
     * Update an active resident's care plan.
     */
    public function update(Request $request, $id)
    {
        $plan = ResidentCarePlan::with('resident')->findOrFail($id);

        if (!$plan->resident || $plan->resident->status !== 'Active') {
            return response()->json([
                'message' => 'Care plans can only be updated for active residents.',
            ], 422);
        }

        $validated = $request->validate([
            'care_type' => 'sometimes|required|string|max:100',
            'title' => 'sometimes|required|string|max:255',
            'instructions' => 'nullable|string',

            'frequency' => 'sometimes|required|in:DAILY,WEEKLY,EVERY_SHIFT,PRN',
            'time_slot' => 'nullable|in:AM,PM,NIGHT,ANY',
            'scheduled_time' => 'nullable|date_format:H:i',

            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'in:MON,TUE,WED,THU,FRI,SAT,SUN',

            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',

            'priority' => 'sometimes|required|in:LOW,NORMAL,HIGH,URGENT,CRITICAL',
        ]);

        $startDate = array_key_exists('start_date', $validated)
            ? $validated['start_date']
            : optional($plan->start_date)->toDateString();

        $endDate = array_key_exists('end_date', $validated)
            ? $validated['end_date']
            : optional($plan->end_date)->toDateString();

        if ($startDate && $endDate && $endDate < $startDate) {
            return response()->json([
                'message' => 'The end date must be on or after the start date.',
            ], 422);
        }

        $validated['updated_by'] = auth()->id();

        $plan->update($validated);

        return response()->json([
            'message' => 'Resident care plan updated successfully.',
            'care_plan' => $plan->fresh([
                'createdBy:id,full_name',
                'updatedBy:id,full_name',
            ]),
        ]);
    }

    /**
     * Deactivate a care plan.
     *
     * We retain the record for history/audit instead of deleting it.
     */
    public function deactivate($id)
    {
        $plan = ResidentCarePlan::with('resident')->findOrFail($id);

        if (!$plan->resident || $plan->resident->status !== 'Active') {
            return response()->json([
                'message' => 'Care plans can only be changed for active residents.',
            ], 422);
        }

        if (!$plan->is_active) {
            return response()->json([
                'message' => 'Care plan is already inactive.',
                'care_plan' => $plan,
            ]);
        }

        $plan->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Resident care plan deactivated successfully.',
            'care_plan' => $plan->fresh([
                'createdBy:id,full_name',
                'updatedBy:id,full_name',
            ]),
        ]);
    }

    /**
     * Reactivate a previously deactivated plan.
     */
    public function activate($id)
    {
        $plan = ResidentCarePlan::with('resident')->findOrFail($id);

        if (!$plan->resident || $plan->resident->status !== 'Active') {
            return response()->json([
                'message' => 'Care plans can only be activated for active residents.',
            ], 422);
        }

        if ($plan->is_active) {
            return response()->json([
                'message' => 'Care plan is already active.',
                'care_plan' => $plan,
            ]);
        }

        $plan->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Resident care plan activated successfully.',
            'care_plan' => $plan->fresh([
                'createdBy:id,full_name',
                'updatedBy:id,full_name',
            ]),
        ]);
    }
}