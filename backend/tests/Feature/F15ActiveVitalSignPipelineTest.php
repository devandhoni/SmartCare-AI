<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15ActiveVitalSignPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_resident_vital_sign_runs_clinical_pipeline(): void
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
            'email' => 'f15-active-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        DB::table('residents')->insert([
            'id' => 1,
            'full_name' => 'F15 Active Resident',
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        $response = $this->postJson(
            '/api/residents/1/vitals',
            [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'blood_glucose' => 5.5,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'temperature' => 36.7,
                'weight' => 65.5,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Vital sign recorded successfully'
            )
            ->assertJsonPath(
                'vital.resident_id',
                '1'
            )
            ->assertJsonPath(
                'vital.blood_pressure_systolic',
                120
            )
            ->assertJsonPath(
                'vital.blood_pressure_diastolic',
                80
            )
            ->assertJsonPath(
                'vital.heart_rate',
                72
            )
            ->assertJsonPath(
                'vital.oxygen_level',
                98
            )
            ->assertJsonPath(
                'vital.recorded_by',
                1
            );

        $this->assertDatabaseHas(
            'vital_signs',
            [
                'resident_id' => 1,
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'recorded_by' => 1,
            ]
        );

        $vitalId = DB::table('vital_signs')
            ->where('resident_id', 1)
            ->value('id');

        $this->assertNotNull($vitalId);

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'VITAL',
                'event_title' => 'Vital Signs Recorded',
                'source_type' => 'VitalSign',
                'source_id' => $vitalId,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' => 1,
                'resident_id' => 1,
                'module' => 'Vital Signs',
                'action' => 'CREATE',
                'description' => 'New vital signs recorded for resident.',
            ]
        );

        $activityLogId = DB::table('activity_logs')
            ->where('resident_id', 1)
            ->where('module', 'Vital Signs')
            ->where('action', 'CREATE')
            ->value('id');

        $this->assertNotNull($activityLogId);

        $this->assertDatabaseHas(
            'activity_log_integrity',
            [
                'activity_log_id' => $activityLogId,
            ]
        );

        $this->assertDatabaseHas(
            'health_risk_scores',
            [
                'resident_id' => 1,
                'risk_score' => 0,
                'risk_level' => 'LOW',
            ]
        );

        $this->assertDatabaseCount(
            'ai_alerts',
            0
        );

        $this->assertDatabaseCount(
            'health_predictions',
            0
        );

        $this->assertDatabaseCount(
            'clinical_recommendations',
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