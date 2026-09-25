<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15SecurityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_role_active_account_staff_and_audit_boundaries(): void
    {
        $this->assertTestingDatabaseIsolation();

        /*
        |--------------------------------------------------------------------------
        | 1. Roles And Users
        |--------------------------------------------------------------------------
        */

        DB::table('roles')->insert([
            [
                'id' => 1,
                'role_name' => 'Administrator',
            ],
            [
                'id' => 2,
                'role_name' => 'Manager',
            ],
            [
                'id' => 3,
                'role_name' => 'Doctor',
            ],
            [
                'id' => 4,
                'role_name' => 'Nurse',
            ],
        ]);

        DB::table('users')->insert([
            [
                'id' => 1,
                'role_id' => 1,
                'full_name' => 'F15 Active Administrator',
                'email' => 'f15.admin@example.test',
                'password' => Hash::make('AdminPassword123!'),
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'role_id' => 4,
                'full_name' => 'F15 Active Nurse',
                'email' => 'f15.nurse@example.test',
                'password' => Hash::make('NursePassword123!'),
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'role_id' => 1,
                'full_name' => 'F15 Inactive Administrator',
                'email' => 'f15.inactive.admin@example.test',
                'password' => Hash::make('InactivePassword123!'),
                'status' => 'Inactive',
            ],
        ]);

        $administrator = User::findOrFail(1);
        $nurse = User::findOrFail(2);
        $inactiveAdministrator = User::findOrFail(3);

        /*
        |--------------------------------------------------------------------------
        | 2. Unauthenticated Access Is Rejected
        |--------------------------------------------------------------------------
        */

        $this->getJson('/api/staff')
            ->assertUnauthorized();

        $this->getJson('/api/activity-logs')
            ->assertUnauthorized();

        $this->getJson('/api/activity-logs/integrity')
            ->assertUnauthorized();

        $this->getJson('/api/reports/overview')
            ->assertUnauthorized();

        /*
        |--------------------------------------------------------------------------
        | 3. Nurse Cannot Access Administrator Resources
        |--------------------------------------------------------------------------
        */

        Sanctum::actingAs($nurse);

        $this->getJson('/api/staff')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Access denied'
            );

        $this->getJson('/api/activity-logs')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Access denied'
            );

        $this->getJson('/api/activity-logs/integrity')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Access denied'
            );

        $this->getJson('/api/reports/overview')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Access denied'
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Inactive Administrator Is Rejected Everywhere
        |--------------------------------------------------------------------------
        */

        Sanctum::actingAs($inactiveAdministrator);

        foreach ([
            '/api/staff',
            '/api/activity-logs',
            '/api/activity-logs/integrity',
            '/api/reports/overview',
            '/api/reports/resident-census',
            '/api/reports/care-operations',
            '/api/reports/medication-operations',
            '/api/reports/clinical-monitoring',
            '/api/reports/inventory-operations',
            '/api/reports/billing-operations',
            '/api/reports/family-facility',
        ] as $route) {
            $this->getJson($route)
                ->assertForbidden()
                ->assertJsonPath(
                    'message',
                    'Account is inactive. Contact your administrator.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Active Administrator Can Access Staff
        |--------------------------------------------------------------------------
        */

        Sanctum::actingAs($administrator);

        $this->getJson('/api/staff')
            ->assertOk();

        /*
        |--------------------------------------------------------------------------
        | 6. Administrator Creates Staff Account
        |--------------------------------------------------------------------------
        */

        $createResponse = $this->postJson(
            '/api/staff',
            [
                'full_name' =>
                    'F15 Managed Nurse',

                'email' =>
                    'f15.managed.nurse@example.test',

                'phone' =>
                    '0128889999',

                'role_id' =>
                    4,

                'password' =>
                    'ManagedNurse123!',

                'password_confirmation' =>
                    'ManagedNurse123!',
            ]
        );

        $createResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Staff account created.'
            )
            ->assertJsonPath(
                'staff.full_name',
                'F15 Managed Nurse'
            )
            ->assertJsonPath(
                'staff.email',
                'f15.managed.nurse@example.test'
            )
            ->assertJsonPath(
                'staff.role',
                'Nurse'
            )
            ->assertJsonPath(
                'staff.status',
                'Active'
            );

        $managedStaffId =
            $createResponse->json('staff.id');

        $this->assertNotNull(
            $managedStaffId
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' =>
                    $managedStaffId,
                'role_id' =>
                    4,
                'full_name' =>
                    'F15 Managed Nurse',
                'email' =>
                    'f15.managed.nurse@example.test',
                'status' =>
                    'Active',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Administrator Cannot Remove Own Administrator Role
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            '/api/staff/1',
            [
                'full_name' =>
                    'F15 Active Administrator',

                'email' =>
                    'f15.admin@example.test',

                'role_id' =>
                    4,
            ]
        )
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'You cannot remove your own administrator role.'
            );

        $this->assertSame(
            1,
            (int) $administrator->fresh()->role_id
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Administrator Cannot Deactivate Own Account
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            '/api/staff/1/status',
            [
                'status' => 'Inactive',
            ]
        )
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'You cannot deactivate your own account.'
            );

        $this->assertSame(
            'Active',
            $administrator->fresh()->status
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Managed Staff Token Revocation On Deactivation
        |--------------------------------------------------------------------------
        */

        $managedStaff =
            User::findOrFail($managedStaffId);

        $managedStaff->createToken(
            'f15-managed-staff-token'
        );

        $this->assertSame(
            1,
            $managedStaff->tokens()->count()
        );

        $this->putJson(
            "/api/staff/{$managedStaffId}/status",
            [
                'status' => 'Inactive',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Staff account status updated.'
            )
            ->assertJsonPath(
                'staff.status',
                'Inactive'
            );

        $this->assertSame(
            0,
            $managedStaff->tokens()->count()
        );

        /*
        |--------------------------------------------------------------------------
        | 10. Reactivate Managed Staff
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/staff/{$managedStaffId}/status",
            [
                'status' => 'Active',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'staff.status',
                'Active'
            );

        /*
        |--------------------------------------------------------------------------
        | 11. Password Reset Revokes Existing Tokens
        |--------------------------------------------------------------------------
        */

        $managedStaff->refresh();

        $managedStaff->createToken(
            'f15-managed-staff-token-two'
        );

        $this->assertSame(
            1,
            $managedStaff->tokens()->count()
        );

        $oldPasswordHash =
            $managedStaff->password;

        $this->putJson(
            "/api/staff/{$managedStaffId}/reset-password",
            [
                'password' =>
                    'ReplacementPassword123!',

                'password_confirmation' =>
                    'ReplacementPassword123!',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Staff password reset. Existing tokens revoked.'
            );

        $managedStaff->refresh();

        $this->assertSame(
            0,
            $managedStaff->tokens()->count()
        );

        $this->assertNotSame(
            $oldPasswordHash,
            $managedStaff->password
        );

        $this->assertTrue(
            Hash::check(
                'ReplacementPassword123!',
                $managedStaff->password
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Staff Administration Audit Records Exist
        |--------------------------------------------------------------------------
        */

        $staffAuditCount = DB::table(
            'activity_logs'
        )
            ->where(
                'module',
                'Staff Administration'
            )
            ->count();

        $this->assertGreaterThanOrEqual(
            4,
            $staffAuditCount
        );

        /*
        |--------------------------------------------------------------------------
        | 13. Every New Audit Record Is Integrity Protected
        |--------------------------------------------------------------------------
        */

        $activityLogCount =
            DB::table('activity_logs')->count();

        $integrityCount =
            DB::table(
                'activity_log_integrity'
            )->count();

        $this->assertGreaterThan(
            0,
            $activityLogCount
        );

        $this->assertSame(
            $activityLogCount,
            $integrityCount
        );

        /*
        |--------------------------------------------------------------------------
        | 14. Safe Activity Log Endpoint
        |--------------------------------------------------------------------------
        */

        $activityResponse = $this->getJson(
            '/api/activity-logs'
            . '?module=Staff%20Administration'
            . '&per_page=50'
        );

        $activityResponse
            ->assertOk();

        $this->assertGreaterThanOrEqual(
            4,
            (int) $activityResponse->json(
                'total'
            )
        );

        $activityResponse
            ->assertJsonPath(
                'data.0.module',
                'Staff Administration'
            );

        $activityResponse
            ->assertJsonMissingPath(
                'data.0.description'
            );

        $this->assertStringNotContainsString(
            'ReplacementPassword123!',
            $activityResponse->getContent()
        );

        $this->assertStringNotContainsString(
            $managedStaff->password,
            $activityResponse->getContent()
        );

        /*
        |--------------------------------------------------------------------------
        | 15. Integrity Endpoint Verifies Full Test Chain
        |--------------------------------------------------------------------------
        */

        $integrityResponse = $this->getJson(
            '/api/activity-logs/integrity'
        );

        $integrityResponse
            ->assertOk()
            ->assertJsonPath(
                'status',
                'verified'
            )
            ->assertJsonPath(
                'valid',
                true
            )
            ->assertJsonPath(
                'failed_entry_id',
                null
            )
            ->assertJsonPath(
                'reason',
                null
            );

        $this->assertSame(
            $activityLogCount,
            (int) $integrityResponse->json(
                'checked'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 16. Active Administrator Can Access All Management Reports
        |--------------------------------------------------------------------------
        */

        foreach ([
            '/api/reports/overview',
            '/api/reports/resident-census',
            '/api/reports/care-operations',
            '/api/reports/medication-operations',
            '/api/reports/clinical-monitoring',
            '/api/reports/inventory-operations',
            '/api/reports/billing-operations',
            '/api/reports/family-facility',
        ] as $route) {
            $this->getJson(
                $route
                . '?from=2026-09-01&to=2026-09-30'
            )->assertOk();
        }

        /*
        |--------------------------------------------------------------------------
        | 17. Final Isolation Verification
        |--------------------------------------------------------------------------
        */

        $this->assertTestingDatabaseIsolation();
    }

    private function assertTestingDatabaseIsolation(): void
    {
        $this->assertSame(
            'testing',
            app()->environment()
        );

        $this->assertSame(
            'sqlite',
            config('database.default')
        );

        $this->assertSame(
            ':memory:',
            config(
                'database.connections.sqlite.database'
            )
        );

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );
    }
}