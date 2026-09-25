<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Existing SmartCare-AI Installation Guard
        |--------------------------------------------------------------------------
        |
        | Older SmartCare-AI installations were created before the complete
        | Laravel migration history existed. Their legacy core tables already
        | exist.
        |
        | If every expected legacy table already exists, this migration adopts
        | that existing schema and performs no table creation.
        |
        | If only some legacy tables exist, stop immediately. Silently creating
        | the missing tables could combine incompatible schema generations and
        | leave the installation in an inconsistent state.
        |
        */

        $legacyTables = [
            'roles',
            'rooms',
            'users',
            'residents',
            'medications',
            'resident_medications',
            'medicine_inventory',
            'medicine_transactions',
            'ai_alerts',
            'vital_signs',
            'nurse_tasks',
            'family_members',
            'medical_records',
            'notifications',
            'alert_actions',
            'audit_logs',
        ];

        $existingLegacyTables = array_filter(
            $legacyTables,
            fn (string $table): bool => Schema::hasTable($table)
        );

        if (count($existingLegacyTables) === count($legacyTables)) {
            return;
        }

        if (count($existingLegacyTables) > 0) {
            throw new RuntimeException(
                'SmartCare-AI legacy baseline detected a partial legacy schema. '
                . 'Migration stopped to prevent creating an inconsistent database.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role_name', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        /*
        |--------------------------------------------------------------------------
        | Rooms
        |--------------------------------------------------------------------------
        */
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('room_number', 50)->nullable()->unique();
            $table->string('floor', 50)->nullable();
            $table->enum('room_type', ['Single', 'Double', 'VIP'])->nullable();
            $table->enum('status', ['Available', 'Occupied'])
                ->default('Available');
            $table->timestamp('created_at')->useCurrent();
        });

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('role_id');
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('phone', 30)->nullable();
            $table->enum('status', ['Active', 'Inactive'])
                ->default('Active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles');
        });

        /*
        |--------------------------------------------------------------------------
        | Residents
        |--------------------------------------------------------------------------
        | phone and email are intentionally excluded.
        | They are added by the 2026-08-26 contact-fields migration.
        */
        Schema::create('residents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('room_id')->nullable();
            $table->string('full_name', 150);
            $table->string('ic_number', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('nationality', 100)->nullable();
            $table->text('address')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->text('medical_condition')->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_disease')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_relationship', 50)->nullable();
            $table->string('emergency_phone', 30)->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->enum('status', ['Active', 'Discharged', 'Deceased'])
                ->default('Active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('room_id')
                ->references('id')
                ->on('rooms');
        });

        /*
        |--------------------------------------------------------------------------
        | Medications
        |--------------------------------------------------------------------------
        */
        Schema::create('medications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('medicine_name', 150)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('dosage', 100)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->timestamp('created_on')->useCurrent();
            $table->timestamp('updated_on')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | Resident Medications
        |--------------------------------------------------------------------------
        | dosage_quantity and time_slot are intentionally excluded.
        */
        Schema::create('resident_medications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id')->nullable();
            $table->bigInteger('medication_id')->nullable();
            $table->string('dosage_instruction', 255)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->time('scheduled_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('prescribed_by', 150)->nullable();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');

            $table->foreign('medication_id')
                ->references('id')
                ->on('medications');
        });

        /*
        |--------------------------------------------------------------------------
        | Medicine Inventory
        |--------------------------------------------------------------------------
        */
        Schema::create('medicine_inventory', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('medication_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(10);
            $table->date('expiry_date')->nullable();
            $table->string('location', 100)->nullable();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('medication_id')
                ->references('id')
                ->on('medications');
        });

        /*
        |--------------------------------------------------------------------------
        | Medicine Transactions
        |--------------------------------------------------------------------------
        | resident_id is intentionally excluded.
        */
        Schema::create('medicine_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('medication_id')->nullable();
            $table->enum('transaction_type', ['IN', 'OUT'])->nullable();
            $table->integer('quantity')->nullable();
            $table->string('reference', 255)->nullable();
            $table->bigInteger('performed_by')->nullable();
            $table->timestamp('transaction_date')->useCurrent();

            $table->foreign('medication_id')
                ->references('id')
                ->on('medications');
        });

        /*
        |--------------------------------------------------------------------------
        | AI Alerts
        |--------------------------------------------------------------------------
        | Workflow fields added by the later migration are excluded.
        */
        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id')->nullable();
            $table->string('alert_type', 100)->nullable();
            $table->enum(
                'severity',
                ['INFO', 'WARNING', 'LOW', 'MEDIUM', 'HIGH', 'CRITICAL']
            );
            $table->text('message')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->enum('status', ['OPEN', 'RESOLVED'])
                ->default('OPEN');
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('resolved_by')->nullable();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');
        });

        /*
        |--------------------------------------------------------------------------
        | Vital Signs
        |--------------------------------------------------------------------------
        | Glucose workflow fields and later monitoring indexes are excluded.
        */
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id');
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->decimal('blood_glucose', 5, 2)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('oxygen_level')->nullable();
            $table->decimal('temperature', 4, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->bigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');
        });

        /*
        |--------------------------------------------------------------------------
        | Nurse Tasks
        |--------------------------------------------------------------------------
        | All fields introduced by later nurse-task migrations are excluded.
        */
        Schema::create('nurse_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id')->nullable();
            $table->bigInteger('assigned_to')->nullable();
            $table->string('task_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('scheduled_time')->nullable();
            $table->enum(
                'status',
                ['Pending', 'Completed', 'Cancelled']
            )->default('Pending');
            $table->dateTime('completed_time')->nullable();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');
        });

        /*
        |--------------------------------------------------------------------------
        | Family Members
        |--------------------------------------------------------------------------
        */
        Schema::create('family_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id');
            $table->string('name', 150)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');
        });

        /*
        |--------------------------------------------------------------------------
        | Medical Records
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resident_id');
            $table->string('record_type', 100)->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('doctor_name', 150)->nullable();
            $table->text('notes')->nullable();
            $table->date('record_date')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('resident_id')
                ->references('id')
                ->on('residents');
        });

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->string('title', 150)->nullable();
            $table->text('message')->nullable();
            $table->string('type', 50)->nullable();
            $table->boolean('read_status')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('created_on')->nullable()->useCurrent();
            $table->timestamp('updated_on')->nullable()->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        /*
        |--------------------------------------------------------------------------
        | Alert Actions
        |--------------------------------------------------------------------------
        | Current operational schema contains no FK constraints here.
        */
        Schema::create('alert_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('alert_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action_type', 50);
            $table->text('description')->nullable();
            $table->timestamp('action_time')->useCurrent();
            $table->timestamp('created_on')->useCurrent();
            $table->timestamp('updated_on')->useCurrent();

            $table->index('alert_id');
            $table->index('user_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Legacy Audit Logs
        |--------------------------------------------------------------------------
        | Separate from the newer activity_logs audit system.
        */
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->string('action', 255)->nullable();
            $table->string('module', 100)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Intentionally Irreversible Legacy Baseline
        |--------------------------------------------------------------------------
        |
        | This migration supports both fresh installations and existing
        | SmartCare-AI installations whose core tables predate the complete
        | Laravel migration history.
        |
        | On an existing installation, up() may intentionally perform no
        | schema changes. Therefore down() must never drop these legacy tables,
        | because Laravel cannot determine whether this migration created them
        | or merely adopted an existing schema.
        |
        | migrate:fresh remains supported because Laravel drops the database
        | tables independently before rebuilding the migration history.
        |
        */

        // Intentionally no-op.
    }
};