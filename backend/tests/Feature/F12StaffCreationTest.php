<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12StaffCreationTest extends TestCase
{
    public function test_administrator_can_create_staff_with_validation(): void
    {
        // Confirm isolation BEFORE creating tables or records.
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

        $validData = [
            'full_name' => 'New Test Nurse',
            'email' => 'new-nurse@example.test',
            'phone' => '0123456789',
            'role_id' => 4,
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ];

        // An Administrator can create a Nurse account.
        $response = $this->postJson('/api/staff', $validData)
            ->assertStatus(201)
            ->assertJsonPath('message', 'Staff account created.')
            ->assertJsonPath('staff.full_name', 'New Test Nurse')
            ->assertJsonPath('staff.email', 'new-nurse@example.test')
            ->assertJsonPath('staff.role', 'Nurse')
            ->assertJsonPath('staff.status', 'Active');

        // Passwords must never appear in the API response.
        $response->assertJsonMissingPath('staff.password');

        $createdUser = User::where(
            'email',
            'new-nurse@example.test'
        )->firstOrFail();

        $this->assertSame('Active', $createdUser->status);
        $this->assertSame(4, (int) $createdUser->role_id);
        $this->assertSame('0123456789', $createdUser->phone);

        // Verify the password was hashed, not stored as plain text.
        $this->assertNotSame(
            'TestPassword123!',
            $createdUser->password
        );

        $this->assertTrue(
            Hash::check('TestPassword123!', $createdUser->password)
        );

        // Duplicate email addresses must be rejected.
        $this->postJson('/api/staff', $validData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Family Member is not a permitted staff role.
        $familyData = array_merge($validData, [
            'email' => 'new-family@example.test',
            'role_id' => 5,
        ]);

        $this->postJson('/api/staff', $familyData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role_id']);

        // Password confirmation is required.
        $missingConfirmation = $validData;
        $missingConfirmation['email'] = 'unconfirmed@example.test';
        unset($missingConfirmation['password_confirmation']);

        $this->postJson('/api/staff', $missingConfirmation)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // A password shorter than 12 characters must be rejected.
        $shortPassword = array_merge($validData, [
            'email' => 'short-password@example.test',
            'password' => 'Short123!',
            'password_confirmation' => 'Short123!',
        ]);

        $this->postJson('/api/staff', $shortPassword)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Only the valid staff account should have been created.
        $this->assertSame(2, DB::table('users')->count());
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
            'id' => 1,
            'role_id' => 1,
            'full_name' => 'Test Administrator',
            'email' => 'admin@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 'Active',
        ]);
    }
}