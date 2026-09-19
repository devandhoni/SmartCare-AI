<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F13ActivityLogAccessTest extends TestCase
{
    public function test_only_active_administrator_can_read_filtered_paginated_safe_audit_history(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('role_name');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('Active');
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('resident_id')->nullable();
            $table->string('module');
            $table->string('action');
            $table->text('description');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Administrator'],
            ['id' => 4, 'role_name' => 'Nurse'],
        ]);
        foreach ([
            [1, 1, 'Active'],
            [2, 4, 'Active'],
            [3, 1, 'Inactive'],
        ] as [$id, $role, $status]) {
            DB::table('users')->insert([
                'id' => $id, 'role_id' => $role, 'status' => $status,
                'full_name' => 'Test User '.$id,
                'email' => 'user'.$id.'@example.test',
                'password' => Hash::make('TestPassword123!'),
            ]);
        }
        DB::table('activity_logs')->insert([
            ['id' => 1, 'user_id' => 1, 'resident_id' => null, 'module' => 'Staff Administration', 'action' => 'CREATE', 'description' => 'SENSITIVE-LEGACY-TEXT', 'created_on' => now()->subDay()],
            ['id' => 2, 'user_id' => 1, 'resident_id' => 7, 'module' => 'Nurse Task', 'action' => 'COMPLETE', 'description' => 'Another sensitive value', 'created_on' => now()->subDay()],
            ['id' => 3, 'user_id' => null, 'resident_id' => null, 'module' => 'Staff Administration', 'action' => 'UPDATE', 'description' => 'Legacy private content', 'created_on' => now()->subDays(40)],
        ]);

        $this->getJson('/api/activity-logs')->assertUnauthorized();
        Sanctum::actingAs(User::findOrFail(2));
        $this->getJson('/api/activity-logs')->assertForbidden();
        Sanctum::actingAs(User::findOrFail(3));
        $this->getJson('/api/activity-logs')->assertForbidden();

        Sanctum::actingAs(User::findOrFail(1));
        $response = $this->getJson('/api/activity-logs?module=Staff%20Administration&action=CREATE&per_page=1')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.actor_name', 'Test User 1')
            ->assertJsonPath('data.0.action', 'CREATE');
        $response->assertJsonMissingPath('data.0.description');
        $this->assertStringNotContainsString('SENSITIVE-LEGACY-TEXT', $response->getContent());
        $this->getJson('/api/activity-logs?per_page=1')->assertOk()
            ->assertJsonPath('per_page', 1)->assertJsonPath('total', 2);
        $this->getJson('/api/activity-logs?from=2026-12-31&to=2026-01-01')
            ->assertUnprocessable()->assertJsonValidationErrors(['from']);
        $this->getJson('/api/activity-logs?per_page=101')
            ->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
        $this->postJson('/api/activity-logs', [])->assertStatus(405);
        $this->deleteJson('/api/activity-logs/1')->assertNotFound();
    }
}
