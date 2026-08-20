<?php

namespace App\Http\Controllers;

use App\Services\AICareWorkflowApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AICareWorkflowController extends Controller
{
    /**
     * Approve AI-generated care workflow proposal.
     */
    public function approve(
        Request $request,
        int $resident,
        string $proposal,
        AICareWorkflowApprovalService $approvalService
    ): JsonResponse {
        try {

            /*
            |--------------------------------------------------------------------------
            | Human approver
            |--------------------------------------------------------------------------
            |
            | If authentication is enabled this will capture the approving user.
            | Otherwise it safely remains null.
            |
            */

            $approvedBy =
                auth()->check()
                ? auth()->id()
                : null;

            $result =
                $approvalService->approve(
                    $resident,
                    $proposal,
                    $approvedBy
                );

            return response()->json([
                'success' => true,
                'message' =>
                    $result['message']
                    ?? 'Workflow approval completed.',
                'data' =>
                    $result,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Resident not found.',
            ], 404);

        } catch (\RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to approve AI care workflow proposal.',
            ], 500);
        }
    }
}