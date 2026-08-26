<?php

namespace App\Http\Controllers;

use App\Models\ResidentAdmissionDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResidentAdmissionDraftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Admission Drafts
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $drafts = ResidentAdmissionDraft::with([
                'creator:id,full_name',
                'updater:id,full_name',
            ])
            ->where('status', 'DRAFT')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'drafts' => $drafts,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Admission Draft
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' =>
                'nullable|string|max:255',

            'current_step' =>
                'nullable|integer|min:1|max:7',

            'form_data' =>
                'required|array',
        ]);

        $draft = ResidentAdmissionDraft::create([
            'draft_reference' =>
                $this->generateDraftReference(),

            'full_name' =>
                $validated['full_name']
                ?? data_get(
                    $validated,
                    'form_data.full_name'
                ),

            'current_step' =>
                $validated['current_step']
                ?? 1,

            'form_data' =>
                $validated['form_data'],

            'status' =>
                'DRAFT',

            'created_by' =>
                auth()->id(),

            'updated_by' =>
                auth()->id(),
        ]);

        $draft->load([
            'creator:id,full_name',
            'updater:id,full_name',
        ]);

        return response()->json([
            'message' =>
                'Admission draft saved successfully.',

            'draft' =>
                $draft,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Admission Draft
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $draft = ResidentAdmissionDraft::with([
                'creator:id,full_name',
                'updater:id,full_name',
            ])
            ->where('status', 'DRAFT')
            ->findOrFail($id);

        return response()->json([
            'draft' => $draft,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Admission Draft
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $draft = ResidentAdmissionDraft::where(
                'status',
                'DRAFT'
            )
            ->findOrFail($id);

        $validated = $request->validate([
            'full_name' =>
                'nullable|string|max:255',

            'current_step' =>
                'nullable|integer|min:1|max:7',

            'form_data' =>
                'required|array',
        ]);

        $draft->update([
            'full_name' =>
                $validated['full_name']
                ?? data_get(
                    $validated,
                    'form_data.full_name'
                )
                ?? $draft->full_name,

            'current_step' =>
                $validated['current_step']
                ?? $draft->current_step,

            'form_data' =>
                $validated['form_data'],

            'updated_by' =>
                auth()->id(),
        ]);

        $draft->load([
            'creator:id,full_name',
            'updater:id,full_name',
        ]);

        return response()->json([
            'message' =>
                'Admission draft updated successfully.',

            'draft' =>
                $draft,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Admission Draft
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $draft = ResidentAdmissionDraft::where(
                'status',
                'DRAFT'
            )
            ->findOrFail($id);

        $draft->delete();

        return response()->json([
            'message' =>
                'Admission draft deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Draft Reference
    |--------------------------------------------------------------------------
    */

    private function generateDraftReference(): string
    {
        do {
            $reference =
                'DRF-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(6)
                );
        } while (
            ResidentAdmissionDraft::where(
                'draft_reference',
                $reference
            )->exists()
        );

        return $reference;
    }
}