<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\ActivityLogger;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger)
    {
    }

    private const STAFF_ROLES = [
        'Administrator',
        'Manager',
        'Doctor',
        'Nurse',
    ];

    public function index(): JsonResponse
    {
        $staff = User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->whereIn('role_name', self::STAFF_ROLES);
            })
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $user) => $this->staffData($user));

        return response()->json(['staff' => $staff]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'staff' => $this->staffData($this->findStaff($id)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->whereIn('role_name', self::STAFF_ROLES);
                }),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role_id' => $validated['role_id'],
                'password' => Hash::make($validated['password']),
                'status' => 'Active',
            ]);

            $this->activityLogger->log('Staff Administration', 'created', "Staff account ID {$user->id} created.");

            return $user;
        });

        return response()->json([
            'message' => 'Staff account created.',
            'staff' => $this->staffData($user->load('role')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->findStaff($id);

        $validated = $request->validate([
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->whereIn('role_name', self::STAFF_ROLES);
                }),
            ],
        ]);

        // Prevent an administrator from removing their own administrator role.
        if (
            isset($validated['role_id']) &&
            $user->id === $request->user()->id
        ) {
            $newRole = Role::findOrFail($validated['role_id']);

            if ($newRole->role_name !== 'Administrator') {
                return response()->json([
                    'message' => 'You cannot remove your own administrator role.',
                ], 403);
            }
        }

        DB::transaction(function () use ($user, $validated): void {
            $user->fill($validated);
            $changedFields = array_keys($user->getDirty());
            $user->save();

            if ($changedFields !== []) {
                $fields = implode(', ', $changedFields);
                $this->activityLogger->log('Staff Administration', 'updated', "Staff account ID {$user->id} updated. Changed fields: {$fields}.");
            }
        });

        return response()->json([
            'message' => 'Staff account updated.',
            'staff' => $this->staffData($user->load('role')),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->findStaff($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if (
            $user->id === $request->user()->id &&
            $validated['status'] === 'Inactive'
        ) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        DB::transaction(function () use ($user, $validated): void {
            $statusChanged = $user->status !== $validated['status'];
            $user->update(['status' => $validated['status']]);

            if ($validated['status'] === 'Inactive') {
                $user->tokens()->delete();
            }

            if ($statusChanged) {
                $status = $validated['status'];
                $this->activityLogger->log('Staff Administration', 'status_changed', "Staff account ID {$user->id} status changed to {$status}.");
            }
        });

        return response()->json([
            'message' => 'Staff account status updated.',
            'staff' => $this->staffData($user->load('role')),
        ]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = $this->findStaff($id);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            $user->tokens()->delete();
            $this->activityLogger->log('Staff Administration', 'password_reset', "Password reset for staff account ID {$user->id}. Existing tokens revoked.");
        });

        return response()->json([
            'message' => 'Staff password reset. Existing tokens revoked.',
        ]);
    }

    private function findStaff(int $id): User
    {
        return User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->whereIn('role_name', self::STAFF_ROLES);
            })
            ->findOrFail($id);
    }

    private function staffData(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'role' => $user->role?->role_name,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}