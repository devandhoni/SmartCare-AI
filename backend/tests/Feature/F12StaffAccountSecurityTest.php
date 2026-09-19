<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12StaffAccountSecurityTest extends TestCase
{
    public function test_administrator_can_manage_staff_status_and_reset_password(): void
    {
        // Verify isolation BEFORE creating tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();
        $this->createTestUsers();

        $administrator = User::findOrFail(1);
        $nurse = User::findOrFail(2);

        Sanctum::actingAs($administrator);

        // Create a real Sanctum token for the Nurse.
        $nurse->createToken('test-nurse-token');

        $this->assertSame(1, $nurse->tokens()->count());

        // An Administrator can deactivate another staff member.
        $this->putJson('/api/staff/2/status', [
            'status' => 'Inactive',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Staff account status updated.'
            )
            ->assertJsonPath('staff.status', 'Inactive');

        $nurse->refresh();

        $this->assertSame('Inactive', $nurse->status);

        // Deactivation must revoke the Nurse's existing tokens.
        $this->assertSame(0, $nurse->tokens()->count());

        // An Administrator can reactivate the Nurse.
        $this->putJson('/api/staff/2/status', [
            'status' => 'Active',
        ])
            ->assertOk()
            ->assertJsonPath('staff.status', 'Active');

        $nurse->refresh();

        $this->assertSame('Active', $nurse->status);

        // Reactivation must not restore previously revoked tokens.
        $this->assertSame(0, $nurse->tokens()->count());

        // Create another token before resetting the password.
        $nurse->createToken('test-nurse-token-after-reactivation');

        $this->assertSame(1, $nurse->tokens()->count());

        $oldPasswordHash = $nurse->password;

        // An Administrator can reset another staff member's password.
        $this->putJson('/api/staff/2/reset-password', [
            'password' => 'NewNursePassword123!',
            'password_confirmation' => 'NewNursePassword123!',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Staff password reset. Existing tokens revoked.'
            );

        $nurse->refresh();

        // The new password must be hashed.
        $this->assertNotSame(
            'NewNursePassword123!',
            $nurse->password
        );

        $this->assertNotSame(
            $oldPasswordHash,
            $nurse->password
        );

        $this->assertTrue(
            Hash::check('NewNursePassword123!', $nurse->password)
        );

        $this->assertFalse(
            Hash::check('NursePassword123!', $nurse->password)
        );

        // Password reset must revoke existing tokens.
        $this->assertSame(0, $nurse->tokens()->count());

        // An Administrator must not deactivate their own account.
        $this->putJson('/api/staff/1/status', [
            'status' => 'Inactive',
        ])
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'You cannot deactivate your own account.'
            );

        $this->assertSame(
            'Active',
            $administrator->fresh()->status
        );

        // Invalid status values must be rejected.
        $this->putJson('/api/staff/2/status', [
            'status' => 'Suspended',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Password confirmation must be validated.
        $this->putJson('/api/staff/2/reset-password', [
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'WrongPassword123!',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Family Member accounts cannot be managed as staff.
        $this->putJson('/api/staff/3/status', [
            'status' => 'Inactive',
        ])->assertNotFound();

        $this->putJson('/api/staff/3/reset-password', [
            'password' => 'FamilyNewPassword123!',
            'password_confirmation' => 'FamilyNewPassword123!',
        ])->assertNotFound();

        $familyMember = User::findOrFail(3);

        $this->assertSame('Active', $familyMember->status);

        $this->assertTrue(
            Hash::check('FamilyPassword123!', $familyMember->password)
        );
    }

    private function createTestTables(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name', 50);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('phone', 30)->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function createTestUsers(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Administrator'],
            ['id' => 2, 'role_name' => 'Manager'],
            ['id' => 3, 'role_name' => 'Doctor'],
            ['id' => 4, 'role_name' => 'Nurse'],
            ['id' => 5, 'role_name' => 'Family Member'],
        ]);

        DB::table('users')->insert([
            [
                'id' => 1,
                'role_id' => 1,
                'full_name' => 'Test Administrator',
                'email' => 'admin@example.test',
                'password' => Hash::make('AdminPassword123!'),
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'role_id' => 4,
                'full_name' => 'Test Nurse',
                'email' => 'nurse@example.test',
                'password' => Hash::make('NursePassword123!'),
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'role_id' => 5,
                'full_name' => 'Test Family Member',
                'email' => 'family@example.test',
                'password' => Hash::make('FamilyPassword123!'),
                'status' => 'Active',
            ],
        ]);
    }
}