<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NurseTask;
use App\Services\MedicationScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class F11MedicationReminderTest extends TestCase
{
    public function test_due_medication_creates_one_task_and_staff_notifications_without_duplicates(): void
    {
        // Verify isolation BEFORE creating tables or records.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Carbon::setTestNow(
            Carbon::parse('2026-09-18 09:10:00')
        );

        try {
            $this->createTestTables();
            $this->createTestRecords();

            // First check: medication is due.
            $firstResult = app(MedicationScheduleService::class)
                ->checkDueMedications();

            $this->assertCount(1, $firstResult);
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
                Notification::where('type', 'MEDICATION')
                    ->where('title', 'Medication Reminder')
                    ->count()
            );

            $this->assertSame(
                'MED:1:2026-09-18',
                NurseTask::first()->occurrence_key
            );

            // Second check: same occurrence must not create duplicates.
            $secondResult = app(MedicationScheduleService::class)
                ->checkDueMedications();

            $this->assertCount(1, $secondResult);
            $this->assertSame(1, NurseTask::count());
            $this->assertSame(2, Notification::count());

            // A completed medication must not be returned or reminded.
            DB::table('medication_administration_records')->insert([
                'resident_medication_id' => 1,
                'administered_date' => '2026-09-18',
                'status' => 'COMPLETED',
            ]);

            $completedResult = app(MedicationScheduleService::class)
                ->checkDueMedications();

            $this->assertCount(0, $completedResult);
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
            $table->timestamps();
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
            $table->date('administered_date');
            $table->string('status');
        });

        Schema::create('nurse_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->string('occurrence_key')->nullable()->unique();
            $table->string('source_type')->nullable();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_time')->nullable();
            $table->string('status');
            $table->string('priority')->nullable();
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