<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogIntegrityService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ActivityLogIntegrityController extends Controller
{
    public function __invoke(ActivityLogIntegrityService $integrity): JsonResponse
    {
        try {
            $result = $integrity->verify();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'verification_unavailable',
                'message' => 'Audit integrity verification could not be completed.',
            ], 503);
        }

        return response()->json([
            'status' => $result['valid'] ? 'verified' : 'integrity_failure',
            'valid' => $result['valid'],
            'checked' => $result['checked'],
            'failed_entry_id' => $result['failed_entry_id'],
            'reason' => $result['reason'],
        ], $result['valid'] ? 200 : 409);
    }
}
