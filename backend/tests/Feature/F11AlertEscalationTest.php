<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AiAlert;
use App\Models\AlertEscalationLog;
use App\Models\Notification;
use App\Services\AlertEscalationEngine;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F11AlertEscalationTest extends TestCase
{
    public function test_escalation_notifies_staff_without_duplicates_and_skips_resolved_alerts(): void
    {
        // Verify isolation BEFORE creating any tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Carbon::setTestNow(
            Carbon::parse('2026-09-18 10:00:00')
        );

        try {
            $this->createTestTables();
            $this->createTestRecords();

            $engine = app(AlertEscalationEngine::class);

            // First escalation: create staff notifications and audit records.
            $firstResult = $engine->escalate(1);

            $this->assertFalse($firstResult['duplicate']);
            $this->assertTrue($firstResult['notification_created']);
            $this->assertSame('URGENT', $firstResult['priority']);

            $this->assertSame(1, AlertEscalationLog::count());
            $this->assertSame(1, ActivityLog::count());
            $this->assertSame(2, Notification::count());

            $this->assertSame(
                [1, 2],
                Notification::orderBy('user_id')
                    ->pluck('user_id')
                    ->all()
            );

            $this->assertSame(
                0,
                Notification::whereNull('user_id')->count()
            );

            $this->assertSame(
                2,
                Notification::where('type', 'AI_ESCALATION')
                    ->where('title', 'AI Alert Escalation - URGENT')
                    ->count()
            );

            $this->assertSame(
                'ESCALATED',
                AlertEscalationLog::first()->status
            );

            $this->assertSame(
                'URGENT',
                AlertEscalationLog::first()->priority
            );

            // Repeating an escalation must not create duplicate records.
            $secondResult = $engine->escalate(1);

            $this->assertTrue($secondResult['duplicate']);
            $this->assertFalse($secondResult['notification_created']);

            $this->assertSame(1, AlertEscalationLog::count());
            $this->assertSame(1, ActivityLog::count());
            $this->assertSame(2, Notification::count());

            // A resolved alert must not be escalated.
            AiAlert::whereKey(1)->update([
                'status' => 'RESOLVED',
            ]);

            $resolvedResult = $engine->escalate(1);

            $this->assertTrue($resolvedResult['duplicate']);
            $this->assertSame(
                'Alert already resolved. Escalation not required.',
                $resolvedResult['message']
            );

            $this->assertSame(1, AlertEscalationLog::count());
            $this->assertSame(1, ActivityLog::count());
            $this->assertSame(2, Notification::count());
        } finally {
            Carbon::setTestNow();
        }
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

        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('status');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->string('alert_type');
            $table->string('severity');
            $table->text('message');
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->string('status');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('alert_escalation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alert_id');
            $table->unsignedBigInteger('resident_id');
            $table->string('priority');
            $table->text('escalation_reason');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('escalated_at')->nullable();
            $table->string('status');
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
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

        Schema::create('activity_log_integrity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('payload_hash', 64);
            $table->char('chain_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
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
    }

    private function createTestRecords(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Administrator'],
            ['id' => 2, 'role_name' => 'Manager'],
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
            [
                'id' => 3,
                'role_id' => 2,
                'full_name' => 'Test Manager',
                'email' => 'manager@example.test',
                'password' => 'test-only',
                'status' => 'Active',
            ],
        ]);

        DB::table('residents')->insert([
            'id' => 1,
            'full_name' => 'Test Resident',
            'status' => 'Active',
        ]);

        DB::table('ai_alerts')->insert([
            'id' => 1,
            'resident_id' => 1,
            'alert_type' => 'HEALTH RISK',
            'severity' => 'CRITICAL',
            'message' => 'Test critical health alert',
            'ai_confidence' => 95,
            'status' => 'OPEN',
        ]);
    }
}