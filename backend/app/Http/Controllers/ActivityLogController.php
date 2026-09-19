<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'module' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::createFromFormat('!Y-m-d', $validated['from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($validated['to'])
            ? Carbon::createFromFormat('!Y-m-d', $validated['to'])->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => ['The audit start date must be on or before the end date.'],
            ]);
        }

        if ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 365) {
            throw ValidationException::withMessages([
                'to' => ['The audit period cannot exceed 366 calendar days.'],
            ]);
        }

        $query = ActivityLog::query()
            ->with(['user:id,full_name'])
            ->whereBetween('created_on', [$from, $to]);

        if (isset($validated['module'])) {
            $query->where('module', $validated['module']);
        }

        if (isset($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        $logs = $query->orderByDesc('created_on')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => $logs->getCollection()->map(static fn (ActivityLog $log): array => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'actor_name' => $log->user?->full_name,
                'resident_id' => $log->resident_id,
                'module' => $log->module,
                'action' => $log->action,
                // Legacy descriptions are free-form and are intentionally not exposed.
                'created_on' => $log->created_on,
            ])->values(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]);
    }
}
