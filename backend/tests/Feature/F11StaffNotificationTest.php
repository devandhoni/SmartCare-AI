<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Services\StaffNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F11StaffNotificationTest extends TestCase
{
    public function test_notifications_go_only_to_active_administrators_and_nurses(): void
    {
        // Stop before creating tables if database isolation is incorrect.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        // Minimal tables for this test only.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
            $table->timestamps();
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
                'role_id' => 1,
                'full_name' => 'Inactive Administrator',
                'email' => 'inactive@example.test',
                'password' => 'test-only',
                'status' => 'Inactive',
            ],
            [
                'id' => 4,
                'role_id' => 2,
                'full_name' => 'Test Manager',
                'email' => 'manager@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
            [
                'id' => 5,
                'role_id' => 5,
                'full_name' => 'Test Family Member',
                'email' => 'family@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
        ]);

        $created = app(StaffNotificationService::class)
            ->notifyOperationalStaff(
                'F11 Test Alert',
                'Isolated notification delivery test.',
                'SYSTEM'
            );

        $this->assertSame(2, $created);

        $this->assertSame(
            [1, 2],
            Notification::query()
                ->orderBy('user_id')
                ->pluck('user_id')
                ->all()
        );

        $this->assertSame(2, Notification::count());

        $this->assertSame(
            0,
            Notification::whereNull('user_id')->count()
        );

        $this->assertSame(
            2,
            Notification::where('title', 'F11 Test Alert')
                ->where('message', 'Isolated notification delivery test.')
                ->where('type', 'SYSTEM')
                ->count()
        );
    }
}