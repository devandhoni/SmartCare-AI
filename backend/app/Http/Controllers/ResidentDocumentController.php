<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResidentDocumentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Resident Documents
    |--------------------------------------------------------------------------
    */

    public function index($residentId)
    {
        $resident = Resident::findOrFail(
            $residentId
        );

        $documents = ResidentDocument::with([
                'uploader:id,full_name',
            ])
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
            ],

            'documents' => $documents,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Resident Document
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
            'document_type' =>
                'required|string|max:100',

            'title' =>
                'required|string|max:255',

            'document' =>
                'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',

            'notes' =>
                'nullable|string',
        ]);

        $file = $request->file(
            'document'
        );

        $originalName =
            $file->getClientOriginalName();

        $extension =
            $file->getClientOriginalExtension();

        $storedName =
            Str::uuid()->toString()
            .
            (
                $extension
                    ? '.' . $extension
                    : ''
            );

        /*
        |--------------------------------------------------------------------------
        | Store File
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | resident-documents/1/uuid.pdf
        |
        */

        $filePath = $file->storeAs(
            'resident-documents/' . $resident->id,
            $storedName,
            'public'
        );

        $document = ResidentDocument::create([
            'resident_id' =>
                $resident->id,

            'document_type' =>
                $validated['document_type'],

            'title' =>
                $validated['title'],

            'original_name' =>
                $originalName,

            'stored_name' =>
                $storedName,

            'file_path' =>
                $filePath,

            'mime_type' =>
                $file->getMimeType(),

            'file_size' =>
                $file->getSize(),

            'status' =>
                'ACTIVE',

            'notes' =>
                $validated['notes'] ?? null,

            'uploaded_by' =>
                auth()->id(),
        ]);

        $document->load(
            'uploader:id,full_name'
        );

        return response()->json([
            'message' =>
                'Document uploaded successfully.',

            'document' =>
                $document,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Document
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $document =
            ResidentDocument::with([
                'resident:id,full_name',
                'uploader:id,full_name',
            ])
            ->findOrFail($id);

        return response()->json(
            $document
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Download / View Document
    |--------------------------------------------------------------------------
    */

    public function download($id)
    {
        $document =
            ResidentDocument::findOrFail(
                $id
            );

        if (
            !Storage::disk('public')
                ->exists($document->file_path)
        ) {
            return response()->json([
                'message' =>
                    'Document file could not be found.',
            ], 404);
        }

        return Storage::disk('public')
            ->download(
                $document->file_path,
                $document->original_name
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Document Information
    |--------------------------------------------------------------------------
    |
    | This edits metadata only.
    | We do not silently replace the uploaded clinical file.
    |
    */

    public function update(
        Request $request,
        $id
    ) {
        $document =
            ResidentDocument::findOrFail(
                $id
            );

        $validated = $request->validate([
            'document_type' =>
                'sometimes|required|string|max:100',

            'title' =>
                'sometimes|required|string|max:255',

            'status' =>
                'sometimes|required|in:ACTIVE,ARCHIVED',

            'notes' =>
                'nullable|string',
        ]);

        $document->update(
            $validated
        );

        $document->load(
            'uploader:id,full_name'
        );

        return response()->json([
            'message' =>
                'Document updated successfully.',

            'document' =>
                $document,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Document
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $document =
            ResidentDocument::findOrFail(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | Delete Physical File
        |--------------------------------------------------------------------------
        */

        if (
            Storage::disk('public')
                ->exists($document->file_path)
        ) {
            Storage::disk('public')
                ->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'message' =>
                'Document deleted successfully.',
        ]);
    }
}