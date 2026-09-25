<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15CriticalVitalSignPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_vital_sign_runs_alert_and_escalation_pipeline(): void
    {
        $this->assertTestingDatabaseIsolation();

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Critical Test Nurse',
            'email' => 'f15-critical-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        DB::table('residents')->insert([
            'id' => 1,
            'full_name' => 'F15 Critical Resident',
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        $response = $this->postJson(
            '/api/residents/1/vitals',
            [
                'blood_pressure_systolic' => 170,
                'blood_pressure_diastolic' => 105,
                'blood_glucose' => 5.5,
                'heart_rate' => 90,
                'oxygen_level' => 89,
                'temperature' => 36.8,
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
                170
            )
            ->assertJsonPath(
                'vital.blood_pressure_diastolic',
                105
            )
            ->assertJsonPath(
                'vital.oxygen_level',
                89
            )
            ->assertJsonPath(
                'vital.recorded_by',
                1
            );

        $this->assertDatabaseHas(
            'vital_signs',
            [
                'resident_id' => 1,
                'blood_pressure_systolic' => 170,
                'blood_pressure_diastolic' => 105,
                'oxygen_level' => 89,
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

        $this->assertDatabaseHas(
            'ai_alerts',
            [
                'resident_id' => 1,
                'alert_type' => 'Critical Multi-System Deterioration',
                'severity' => 'CRITICAL',
                'status' => 'OPEN',
            ]
        );

        $alertId = DB::table('ai_alerts')
            ->where('resident_id', 1)
            ->where(
                'alert_type',
                'Critical Multi-System Deterioration'
            )
            ->value('id');

        $this->assertNotNull($alertId);

        $this->assertDatabaseHas(
            'alert_actions',
            [
                'alert_id' => $alertId,
                'action_type' => 'CREATED',
                'description' => 'AI generated new health alert',
            ]
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'AI_ALERT',
                'event_title' =>
                    'Critical Multi-System Deterioration Detected',
                'source_type' => 'AiAlert',
                'source_id' => $alertId,
            ]
        );

        $this->assertDatabaseHas(
            'alert_escalation_logs',
            [
                'alert_id' => $alertId,
                'resident_id' => 1,
                'priority' => 'URGENT',
                'status' => 'ESCALATED',
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' => 1,
                'resident_id' => 1,
                'module' => 'AI Alert Escalation',
                'action' => 'ESCALATE',
                'description' =>
                    'AI alert escalated with priority level: URGENT',
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => 1,
                'title' =>
                    'AI Health Alert: Critical Multi-System Deterioration',
                'type' => 'AI_ALERT',
                'read_status' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => 1,
                'title' => 'AI Alert Escalation - URGENT',
                'type' => 'AI_ESCALATION',
                'read_status' => 0,
            ]
        );

        $this->assertDatabaseCount(
            'notifications',
            2
        );

        /*
         * Risk score:
         *
         * High BP       = 30
         * Low oxygen    = 25
         * Active alert  = 15
         * ------------------
         * Total         = 70 / HIGH
         */
        $this->assertDatabaseHas(
            'health_risk_scores',
            [
                'resident_id' => 1,
                'risk_score' => 70,
                'risk_level' => 'HIGH',
            ]
        );

        $this->assertDatabaseHas(
            'health_predictions',
            [
                'resident_id' => 1,
                'prediction_type' => 'Hypertension Risk',
                'risk_level' => 'HIGH',
            ]
        );

        $this->assertDatabaseHas(
            'health_predictions',
            [
                'resident_id' => 1,
                'prediction_type' => 'Respiratory Risk',
                'risk_level' => 'CRITICAL',
            ]
        );

        $this->assertDatabaseCount(
            'health_predictions',
            2
        );

        $this->assertDatabaseHas(
            'clinical_recommendations',
            [
                'resident_id' => 1,
                'recommendation_type' =>
                    'Blood Pressure Management',
                'priority' => 'CRITICAL',
            ]
        );

        $this->assertDatabaseHas(
            'clinical_recommendations',
            [
                'resident_id' => 1,
                'recommendation_type' =>
                    'Respiratory Monitoring',
                'priority' => 'CRITICAL',
            ]
        );

        $this->assertDatabaseCount(
            'clinical_recommendations',
            2
        );

        /*
         * Both ActivityLog records created by this request must be
         * protected by the F13 integrity chain.
         */
        $activityLogIds = DB::table('activity_logs')
            ->where('resident_id', 1)
            ->pluck('id');

        $this->assertCount(
            2,
            $activityLogIds
        );

        foreach ($activityLogIds as $activityLogId) {
            $this->assertDatabaseHas(
                'activity_log_integrity',
                [
                    'activity_log_id' => $activityLogId,
                ]
            );
        }
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