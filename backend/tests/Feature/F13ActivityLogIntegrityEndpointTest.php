<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F13ActivityLogIntegrityEndpointTest extends TestCase
{
    public function test_integrity_endpoint_requires_active_administrator_and_reports_verification_results(): void
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
        Schema::create('activity_log_integrity', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('payload_hash', 64);
            $table->char('chain_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Administrator'],
            ['id' => 4, 'role_name' => 'Nurse'],
        ]);
        foreach ([[1, 1, 'Active'], [2, 4, 'Active'], [3, 1, 'Inactive']] as [$id, $role, $status]) {
            DB::table('users')->insert([
                'id' => $id,
                'role_id' => $role,
                'status' => $status,
                'full_name' => 'Test User '.$id,
                'email' => 'user'.$id.'@example.test',
                'password' => Hash::make('TestPassword123!'),
            ]);
        }

        $this->getJson('/api/activity-logs/integrity')->assertUnauthorized();
        Sanctum::actingAs(User::findOrFail(2));
        $this->getJson('/api/activity-logs/integrity')->assertForbidden();
        Sanctum::actingAs(User::findOrFail(3));
        $this->getJson('/api/activity-logs/integrity')->assertForbidden();

        Sanctum::actingAs(User::findOrFail(1));
        $this->getJson('/api/activity-logs/integrity')->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('valid', true)
            ->assertJsonPath('checked', 0);

        $log = ActivityLog::create([
            'user_id' => 1,
            'resident_id' => null,
            'module' => 'Staff Administration',
            'action' => 'UPDATE',
            'description' => 'PRIVATE-INTEGRITY-TEST-CONTENT',
        ]);
        $this->assertSame(1, DB::table('activity_log_integrity')->count());
        $response = $this->getJson('/api/activity-logs/integrity')->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('valid', true)
            ->assertJsonPath('checked', 1)
            ->assertJsonPath('failed_entry_id', null)
            ->assertJsonPath('reason', null);
        $this->assertStringNotContainsString('PRIVATE-INTEGRITY-TEST-CONTENT', $response->getContent());

        DB::table('activity_logs')->where('id', $log->getKey())->update(['description' => 'ALTERED-PRIVATE-CONTENT']);
        $response = $this->getJson('/api/activity-logs/integrity')->assertStatus(409)
            ->assertJsonPath('status', 'integrity_failure')
            ->assertJsonPath('valid', false)
            ->assertJsonPath('checked', 0)
            ->assertJsonPath('failed_entry_id', 1)
            ->assertJsonPath('reason', 'Protected activity log content has changed.');
        $this->assertStringNotContainsString('ALTERED-PRIVATE-CONTENT', $response->getContent());

        DB::table('activity_logs')->where('id', $log->getKey())->delete();
        $this->getJson('/api/activity-logs/integrity')->assertStatus(409)
            ->assertJsonPath('reason', 'Protected activity log is missing.');

        Schema::drop('activity_log_integrity');
        $this->getJson('/api/activity-logs/integrity')->assertStatus(503)
            ->assertJsonPath('status', 'verification_unavailable');
    }
}
