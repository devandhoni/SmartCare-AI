<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12NurseDashboardAccessTest extends TestCase
{
    public function test_nurse_dashboard_routes_require_authentication_and_allowed_role(): void
    {
        // Confirm database isolation before creating tables or records.
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
            '/api/nurse/residents/1/medication-dashboard',
            '/api/nurse/dashboard/1',
        ];

        // Unauthenticated requests must stop before reaching controllers.
        foreach ($routes as $route) {
            $this->getJson($route)->assertStatus(401);
        }

        // A Family Member must not access either route.
        $familyMember = User::findOrFail(3);
        Sanctum::actingAs($familyMember);

        foreach ($routes as $route) {
            $this->getJson($route)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Access denied');
        }

        // An inactive Nurse must also be rejected.
        $nurse = User::findOrFail(2);
        Sanctum::actingAs($nurse);

        DB::table('users')
            ->where('id', 2)
            ->update(['status' => 'Inactive']);

        $nurse->refresh();

        foreach ($routes as $route) {
            $this->getJson($route)
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
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status');
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