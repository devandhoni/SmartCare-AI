<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12StaffAccessTest extends TestCase
{
    public function test_staff_routes_require_an_active_administrator(): void
    {
        // Verify isolation before creating any tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();
        $this->createTestUsers();

        $routes = [
            ['GET', '/api/staff'],
            ['POST', '/api/staff'],
            ['GET', '/api/staff/1'],
            ['PUT', '/api/staff/1'],
            ['PUT', '/api/staff/1/status'],
            ['PUT', '/api/staff/1/reset-password'],
        ];

        // No authentication: every staff endpoint must return 401.
        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri)->assertStatus(401);
        }

        // An active Nurse must not manage staff.
        Sanctum::actingAs(User::findOrFail(2));

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Access denied');
        }

        // A Family Member must not manage staff either.
        Sanctum::actingAs(User::findOrFail(3));

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Access denied');
        }

        // Even an Administrator must be rejected when inactive.
        $administrator = User::findOrFail(1);
        $administrator->update(['status' => 'Inactive']);
        Sanctum::actingAs($administrator);

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri)
                ->assertStatus(403)
                ->assertJsonPath(
                    'message',
                    'Account is inactive. Contact your administrator.'
                );
        }
    }

    private function createTestTables(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    private function createTestUsers(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Administrator'],
            ['id' => 4, 'role_name' => 'Nurse'],
            ['id' => 5, 'role_name' => 'Family Member'],
        ]);

        DB::table('users')->insert([
            [
                'id' => 1,
                'role_id' => 1,
                'full_name' => 'Test Administrator',
                'email' => 'admin@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'role_id' => 4,
                'full_name' => 'Test Nurse',
                'email' => 'nurse@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'role_id' => 5,
                'full_name' => 'Test Family Member',
                'email' => 'family@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
        ]);
    }
}