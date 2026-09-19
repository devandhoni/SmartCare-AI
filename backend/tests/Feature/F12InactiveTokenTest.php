<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F12InactiveTokenTest extends TestCase
{
    public function test_inactive_account_cannot_use_existing_token_on_role_protected_routes(): void
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

        $nurse = User::findOrFail(2);

        // Simulate an authenticated nurse with an existing token.
        Sanctum::actingAs($nurse);

        $this->getJson('/api/nurse/test')
            ->assertOk()
            ->assertJsonPath('message', 'Welcome Nurse');

        // Deactivate the account after authentication.
        DB::table('users')
            ->where('id', 2)
            ->update(['status' => 'Inactive']);

        // Reload the authenticated model to reflect the status change.
        $nurse->refresh();

        $this->getJson('/api/nurse/test')
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Account is inactive. Contact your administrator.'
            );

        $this->getJson('/api/notifications')
            ->assertStatus(403);

        // Reactivation restores access for this simulated session.
        DB::table('users')
            ->where('id', 2)
            ->update(['status' => 'Active']);

        $nurse->refresh();

        $this->getJson('/api/nurse/test')
            ->assertOk()
            ->assertJsonPath('message', 'Welcome Nurse');

        // Role restrictions must still apply.
        $this->getJson('/api/admin/test')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Access denied');
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
        ]);
    }
}