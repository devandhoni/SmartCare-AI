<?php

namespace App\Http\Controllers;

use App\Contracts\WhatsAppProvider;
use App\Models\FamilyMessageLog;
use App\Services\FamilyNotificationService;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class FamilyMessageLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'resident_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:PENDING,SENT,FAILED,SKIPPED'],
            'message_type' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = FamilyMessageLog::query()
            ->with([
                'resident:id,full_name',
                'residentContact:id,full_name,relationship,is_primary',
                'creator:id,full_name',
            ]);

        if (!empty($validated['resident_id'])) {
            $query->where('resident_id', $validated['resident_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['message_type'])) {
            $query->where('message_type', $validated['message_type']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_number', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('resident', function ($residentQuery) use ($search) {
                        $residentQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $summaryQuery = clone $query;

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', 'PENDING')->count(),
            'sent' => (clone $summaryQuery)->where('status', 'SENT')->count(),
            'failed' => (clone $summaryQuery)->where('status', 'FAILED')->count(),
            'skipped' => (clone $summaryQuery)->where('status', 'SKIPPED')->count(),
        ];

        $logs = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'summary' => $summary,
            'messages' => $logs,
        ]);
    }

    public function retry(
        $id,
        FamilyNotificationService $familyNotificationService,
        WhatsAppProvider $whatsAppProvider
    ) {
        $log = FamilyMessageLog::findOrFail($id);

        if ($log->status !== 'FAILED') {
            return response()->json([
                'message' => 'Only failed family messages can be retried.',
            ], 422);
        }

        if ($whatsAppProvider->name() === 'NULL') {
            return response()->json([
                'message' => 'WhatsApp sending is not configured yet. The failed message remains unchanged.',
            ], 409);
        }

        try {
            $updatedLog = $familyNotificationService
                ->retryFailedLog((int) $log->id)
                ->load([
                    'resident:id,full_name',
                    'residentContact:id,full_name,relationship,is_primary',
                    'creator:id,full_name',
                ]);

            return response()->json([
                'message' => $updatedLog->status === 'SENT'
                    ? 'Family message sent successfully.'
                    : 'Family message retry completed.',
                'family_message' => $updatedLog,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to retry the family message at this time.',
            ], 500);
        }
    }
}
