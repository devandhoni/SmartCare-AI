<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F12BearerTokenStatusTest extends TestCase
{
    public function test_existing_bearer_token_stops_working_when_account_is_inactive(): void
    {
        // Verify isolation BEFORE any database operation.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();

        DB::table('roles')->insert([
            'id' => 1,
            'role_name' => 'Administrator',
        ]);

        $user = User::create([
            'role_id' => 1,
            'full_name' => 'Bearer Token Test User',
            'email' => 'bearer@example.test',
            'password' => Hash::make('TestPassword123!'),
            'status' => 'Active',
        ]);

        // Obtain an actual Sanctum token through the login endpoint.
        $login = $this->postJson('/api/login', [
            'email' => 'bearer@example.test',
            'password' => 'TestPassword123!',
        ]);

        $login->assertOk()->assertJsonStructure(['token']);

        $token = $login->json('token');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertSame(1, DB::table('personal_access_tokens')->count());

        // Send the token as a real Authorization: Bearer header.
        $this->withToken($token)
            ->getJson('/api/admin/test')
            ->assertOk()
            ->assertJsonPath('message', 'Welcome Administrator');

        // Deactivate the account without deleting its existing token.
        DB::table('users')
            ->where('id', $user->id)
            ->update(['status' => 'Inactive']);

        
        $user->refresh();
        $this->assertSame('Inactive', $user->status);
        auth()->forgetGuards();

        $this->assertSame(1, DB::table('personal_access_tokens')->count());

        // Both role-protected and explicitly active-protected routes must reject it.
        $this->withToken($token)
            ->getJson('/api/admin/test')
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Account is inactive. Contact your administrator.'
            );

        $this->withToken($token)
            ->getJson('/api/notifications')
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Account is inactive. Contact your administrator.'
            );

        // Reactivation restores access using the same existing token.
        DB::table('users')
            ->where('id', $user->id)
            ->update(['status' => 'Active']);
        
        auth()->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/admin/test')
            ->assertOk()
            ->assertJsonPath('message', 'Welcome Administrator');
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
            $table->string('phone')->nullable();
            $table->string('status');
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
}