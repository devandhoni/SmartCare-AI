<?php

namespace Tests\Feature;

use App\Models\AiAlert;
use App\Models\Notification;
use App\Models\NurseTask;
use App\Services\ClinicalTimelineService;
use App\Services\MedicationComplianceService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class F11MedicationDelayTest extends TestCase
{
    public function test_delayed_medication_notifies_staff_without_duplicate_alerts(): void
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
            Carbon::parse('2026-09-18 09:20:00')
        );

        try {
            $this->createTestTables();
            $this->createTestRecords();

            // Keep this test focused on medication delay and notifications.
            // The real clinical timeline service is not called.
            $timeline = Mockery::mock(ClinicalTimelineService::class);

            $timeline
                ->shouldReceive('recordMedicationDelayed')
                ->twice()
                ->with(
                    1,
                    Mockery::type('string'),
                    1
                );

            $this->app->instance(
                ClinicalTimelineService::class,
                $timeline
            );

            $service = app(MedicationComplianceService::class);

            // First check: create the delay alert, task, and staff notifications.
            $firstResult = $service->detectDelayedMedication();

            $this->assertCount(1, $firstResult);
            $this->assertSame(1, AiAlert::count());
            $this->assertSame(1, NurseTask::count());
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
                Notification::where('title', 'Medication Delay Alert')
                    ->where('type', 'MEDICATION_DELAY')
                    ->count()
            );

            $this->assertSame(
                'MEDICATION DELAY',
                AiAlert::first()->alert_type
            );

            $this->assertSame(
                'OPEN',
                AiAlert::first()->status
            );

            $this->assertSame(
                'Medication Follow Up',
                NurseTask::first()->task_name
            );

            // Second check: existing OPEN alert must prevent duplicates.
            $secondResult = $service->detectDelayedMedication();

            $this->assertCount(1, $secondResult);
            $this->assertSame(1, AiAlert::count());
            $this->assertSame(1, NurseTask::count());
            $this->assertSame(2, Notification::count());

            // Completed medication must no longer be reported as delayed.
            DB::table('medication_administration_records')->insert([
                'resident_medication_id' => 1,
                'completed_time' => '2026-09-18 09:21:00',
                'status' => 'COMPLETED',
            ]);

            $completedResult = $service->detectDelayedMedication();

            $this->assertCount(0, $completedResult);
            $this->assertSame(1, AiAlert::count());
            $this->assertSame(1, NurseTask::count());
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

        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('medicine_name');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('resident_medications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('medication_id');
            $table->string('time_slot')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
        });

        Schema::create('medication_administration_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_medication_id');
            $table->dateTime('completed_time')->nullable();
            $table->string('status');
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

        Schema::create('nurse_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_time')->nullable();
            $table->string('status');
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
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

        DB::table('medications')->insert([
            'id' => 1,
            'medicine_name' => 'Test Medicine',
        ]);

        DB::table('resident_medications')->insert([
            'id' => 1,
            'resident_id' => 1,
            'medication_id' => 1,
            'time_slot' => 'AM',
            'scheduled_time' => '09:00:00',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);
    }
}