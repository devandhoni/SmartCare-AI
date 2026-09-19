<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F12LoginStatusTest extends TestCase
{
    public function test_active_user_can_login_and_inactive_user_cannot(): void
    {
        // Verify database isolation before any database operation.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();
        $this->createTestUsers();

        // An active account can log in.
        $activeResponse = $this->postJson('/api/login', [
            'email' => 'active@example.test',
            'password' => 'TestPassword123!',
        ]);

        $activeResponse
            ->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.id', 1)
            ->assertJsonPath('user.role', 'Administrator')
            ->assertJsonStructure(['token']);

        $this->assertSame(
            1,
            DB::table('personal_access_tokens')->count()
        );

        // Correct credentials must not allow an inactive account to log in.
        $inactiveResponse = $this->postJson('/api/login', [
            'email' => 'inactive@example.test',
            'password' => 'TestPassword123!',
        ]);

        $inactiveResponse
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Account is inactive. Contact your administrator.'
            )
            ->assertJsonMissingPath('token');

        // The rejected login must not create another token.
        $this->assertSame(
            1,
            DB::table('personal_access_tokens')->count()
        );

        // Incorrect credentials must still be rejected.
        $this->postJson('/api/login', [
            'email' => 'active@example.test',
            'password' => 'WrongPassword',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid login details');

        $this->assertSame(
            1,
            DB::table('personal_access_tokens')->count()
        );
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

    private function createTestUsers(): void
    {
        DB::table('roles')->insert([
            'id' => 1,
            'role_name' => 'Administrator',
        ]);

        $passwordHash = Hash::make('TestPassword123!');

        User::create([
            'role_id' => 1,
            'full_name' => 'Active Test User',
            'email' => 'active@example.test',
            'password' => $passwordHash,
            'status' => 'Active',
        ]);

        User::create([
            'role_id' => 1,
            'full_name' => 'Inactive Test User',
            'email' => 'inactive@example.test',
            'password' => $passwordHash,
            'status' => 'Inactive',
        ]);
    }
}