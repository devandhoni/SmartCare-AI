<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F11NotificationApiTest extends TestCase
{
    public function test_notification_api_is_scoped_to_authenticated_user(): void
    {
        // Verify isolation BEFORE creating any tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $this->createTestTables();
        $this->createTestRecords();

        $nurse = User::findOrFail(2);

        $this->actingAs($nurse, 'sanctum');

        // The nurse should see only their own notifications.
        $listResponse = $this->getJson('/api/notifications');

        $listResponse->assertOk()
            ->assertJsonCount(2, 'data.notifications')
            ->assertJsonPath('data.notifications.0.id', 2)
            ->assertJsonPath('data.notifications.1.id', 1);

        $this->assertStringNotContainsString(
            'Administrator only notification',
            $listResponse->getContent()
        );

        // Only one of the nurse's two notifications is unread.
        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        // The nurse must not be able to mark the administrator's
        // notification as read.
        $this->putJson('/api/notifications/3/read')
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => 3,
            'user_id' => 1,
            'read_status' => 0,
        ]);

        $this->assertSame(
            0,
            DB::table('activity_logs')->count()
        );

        // Mark the nurse's own unread notification as read.
        $this->putJson('/api/notifications/1/read')
            ->assertOk()
            ->assertJsonPath('data.notification.id', 1)
            ->assertJsonPath('data.notification.read_status', 1);

        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'user_id' => 2,
            'read_status' => 1,
        ]);

        // The activity log must belong to the nurse.
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 2,
            'module' => 'Notification',
            'action' => 'READ',
        ]);

        $this->assertSame(
            1,
            DB::table('activity_logs')->count()
        );

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        // Switching users must not expose the nurse's notifications.
        $administrator = User::findOrFail(1);

        $this->actingAs($administrator, 'sanctum');

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data.notifications')
            ->assertJsonPath('data.notifications.0.id', 3);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->putJson('/api/notifications/1/read')
            ->assertNotFound();

        $this->assertSame(
            1,
            DB::table('activity_logs')->count()
        );
    }

    private function createTestTables(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('password');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type');
            $table->boolean('read_status')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('resident_id')->nullable();
            $table->string('module');
            $table->string('action');
            $table->text('description')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });
    }

    private function createTestRecords(): void
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

        DB::table('notifications')->insert([
            [
                'id' => 1,
                'user_id' => 2,
                'title' => 'Nurse unread notification',
                'message' => 'Nurse notification one',
                'type' => 'MEDICATION',
                'read_status' => 0,
                'created_on' => '2026-09-18 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'title' => 'Nurse read notification',
                'message' => 'Nurse notification two',
                'type' => 'MEDICATION',
                'read_status' => 1,
                'created_on' => '2026-09-18 11:00:00',
            ],
            [
                'id' => 3,
                'user_id' => 1,
                'title' => 'Administrator only notification',
                'message' => 'Administrator notification',
                'type' => 'AI_ESCALATION',
                'read_status' => 0,
                'created_on' => '2026-09-18 12:00:00',
            ],
        ]);
    }
}