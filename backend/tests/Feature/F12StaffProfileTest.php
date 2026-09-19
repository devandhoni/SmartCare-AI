<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12StaffProfileTest extends TestCase
{
    public function test_administrator_can_list_view_and_edit_staff(): void
    {
        // Verify isolation before creating tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();
        $this->createTestUsers();

        Sanctum::actingAs(User::findOrFail(1));

        // Staff listing must exclude Family Member accounts.
        $response = $this->getJson('/api/staff')
            ->assertOk()
            ->assertJsonCount(2, 'staff');

        $staffIds = collect($response->json('staff'))
            ->pluck('id')
            ->all();

        $this->assertContains(1, $staffIds);
        $this->assertContains(2, $staffIds);
        $this->assertNotContains(3, $staffIds);

        foreach ($response->json('staff') as $staff) {
            $this->assertArrayNotHasKey('password', $staff);
        }

        // An Administrator can view an individual staff account.
        $this->getJson('/api/staff/2')
            ->assertOk()
            ->assertJsonPath('staff.full_name', 'Test Nurse')
            ->assertJsonPath('staff.role', 'Nurse')
            ->assertJsonMissingPath('staff.password');

        // Family Member accounts must not be accessible as staff.
        $this->getJson('/api/staff/3')
            ->assertNotFound();

        // Update the Nurse's profile and change their role to Doctor.
        $this->putJson('/api/staff/2', [
            'full_name' => 'Updated Test Doctor',
            'email' => 'updated-doctor@example.test',
            'phone' => '0123456789',
            'role_id' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Staff account updated.')
            ->assertJsonPath('staff.full_name', 'Updated Test Doctor')
            ->assertJsonPath('staff.email', 'updated-doctor@example.test')
            ->assertJsonPath('staff.phone', '0123456789')
            ->assertJsonPath('staff.role', 'Doctor');

        $updated = User::findOrFail(2);

        $this->assertSame('Updated Test Doctor', $updated->full_name);
        $this->assertSame('updated-doctor@example.test', $updated->email);
        $this->assertSame('0123456789', $updated->phone);
        $this->assertSame(3, (int) $updated->role_id);

        // An existing staff member cannot be assigned the Family Member role.
        $this->putJson('/api/staff/2', [
            'role_id' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role_id']);

        // Duplicate staff email addresses must be rejected.
        $this->putJson('/api/staff/2', [
            'email' => 'admin@example.test',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // An Administrator cannot remove their own Administrator role.
        $this->putJson('/api/staff/1', [
            'role_id' => 4,
        ])
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'You cannot remove your own administrator role.'
            );

        $this->assertSame(
            1,
            (int) User::findOrFail(1)->role_id
        );

        // Staff Administration must not edit Family Member accounts.
        $this->putJson('/api/staff/3', [
            'full_name' => 'Should Not Change',
        ])->assertNotFound();

        $this->assertSame(
            'Test Family Member',
            User::findOrFail(3)->full_name
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