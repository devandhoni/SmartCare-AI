<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15VitalSignResidentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_discharged_resident_cannot_receive_new_vital_signs(): void
    {
        $this->assertTestingDatabaseIsolation();

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Test Nurse',
            'email' => 'f15-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        DB::table('residents')->insert([
            'id' => 1,
            'full_name' => 'F15 Discharged Resident',
            'status' => 'Discharged',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        $this->postJson(
            '/api/residents/1/vitals',
            [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'temperature' => 36.7,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Vital signs can only be recorded for active residents.'
            );

        $this->assertDatabaseCount(
            'vital_signs',
            0
        );
    }

    public function test_missing_resident_returns_not_found_instead_of_undefined_variable_failure(): void
    {
        $this->assertTestingDatabaseIsolation();

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Test Nurse',
            'email' => 'f15-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        $this->postJson(
            '/api/residents/999999/vitals',
            [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
            ]
        )->assertStatus(404);

        $this->assertDatabaseCount(
            'vital_signs',
            0
        );
    }

    private function assertTestingDatabaseIsolation(): void
    {
        $this->assertSame(
            'testing',
            app()->environment()
        );

        $this->assertSame(
            'sqlite',
            config('database.default')
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );
    }
}