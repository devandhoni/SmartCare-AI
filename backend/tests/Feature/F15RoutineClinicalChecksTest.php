<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15RoutineClinicalChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_glucose_and_weekly_vital_workflows(): void
    {
        $this->assertTestingDatabaseIsolation();

        /*
        |--------------------------------------------------------------------------
        | Nurse
        |--------------------------------------------------------------------------
        */

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Clinical Nurse',
            'email' => 'f15-clinical-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        /*
        |--------------------------------------------------------------------------
        | Active Resident
        |--------------------------------------------------------------------------
        */

        DB::table('residents')->insert([
            'id' => 1,
            'full_name' => 'F15 Clinical Resident',
            'status' => 'Active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Fixed Test Dates
        |--------------------------------------------------------------------------
        */

        $monthlyDate = Carbon::create(
            2026,
            9,
            10,
            9,
            0,
            0
        );

        $weeklyDate = Carbon::create(
            2026,
            9,
            14,
            10,
            0,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | 1. Monthly Glucose Check
        |--------------------------------------------------------------------------
        */

        $monthlyResponse = $this->postJson(
            '/api/residents/1/monthly-glucose-checks',
            [
                'blood_glucose' => 5.5,
                'glucose_measurement_type' => 'FASTING',
                'glucose_notes' =>
                    'Routine monthly fasting glucose check.',
                'recorded_at' =>
                    $monthlyDate->toDateTimeString(),
            ]
        );

        $monthlyResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Monthly glucose check recorded successfully.'
            )
            ->assertJsonPath(
                'check.resident_id',
                1
            )
            ->assertJsonPath(
                'check.blood_glucose',
                5.5
            )
            ->assertJsonPath(
                'check.glucose_measurement_type',
                'FASTING'
            )
            ->assertJsonPath(
                'check.glucose_notes',
                'Routine monthly fasting glucose check.'
            )
            ->assertJsonPath(
                'check.record_source',
                'MONTHLY_GLUCOSE'
            )
            ->assertJsonPath(
                'check.recorded_by',
                1
            )
            ->assertJsonPath(
                'check.resident.id',
                1
            )
            ->assertJsonPath(
                'check.resident.status',
                'Active'
            );

        $monthlyCheckId = $monthlyResponse->json(
            'check.id'
        );

        $this->assertNotNull(
            $monthlyCheckId
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Monthly Glucose Database Record
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'vital_signs',
            [
                'id' => $monthlyCheckId,
                'resident_id' => 1,
                'blood_glucose' => 5.5,
                'glucose_measurement_type' => 'FASTING',
                'glucose_notes' =>
                    'Routine monthly fasting glucose check.',
                'record_source' => 'MONTHLY_GLUCOSE',
                'recorded_by' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Glucose-only Record
        |--------------------------------------------------------------------------
        */

        $monthlyRecord = DB::table('vital_signs')
            ->where('id', $monthlyCheckId)
            ->first();

        $this->assertNotNull(
            $monthlyRecord
        );

        $this->assertNull(
            $monthlyRecord->blood_pressure_systolic
        );

        $this->assertNull(
            $monthlyRecord->blood_pressure_diastolic
        );

        $this->assertNull(
            $monthlyRecord->heart_rate
        );

        $this->assertNull(
            $monthlyRecord->oxygen_level
        );

        $this->assertNull(
            $monthlyRecord->temperature
        );

        $this->assertNull(
            $monthlyRecord->weight
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Monthly Glucose Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'VITAL',
                'event_title' => 'Monthly Glucose Check',
                'source_type' => 'VitalSign',
                'source_id' => $monthlyCheckId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Monthly Glucose Activity Audit
        |--------------------------------------------------------------------------
        |
        | ActivityLogger stores the related resident in resident_id.
        |
        */

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' => 1,
                'resident_id' => 1,
                'module' => 'Monthly Glucose Check',
                'action' => 'CREATE',
                'description' =>
                    'Monthly glucose check recorded for resident.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Monthly Glucose List
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/monthly-glucose-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'checks.0.id',
                $monthlyCheckId
            )
            ->assertJsonPath(
                'checks.0.record_source',
                'MONTHLY_GLUCOSE'
            )
            ->assertJsonPath(
                'checks.0.resident.id',
                1
            );

        /*
        |--------------------------------------------------------------------------
        | 6. Resident Monthly Glucose History
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/residents/1/monthly-glucose-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                1
            )
            ->assertJsonPath(
                'resident.status',
                'Active'
            )
            ->assertJsonPath(
                'checks.0.id',
                $monthlyCheckId
            )
            ->assertJsonPath(
                'checks.0.record_source',
                'MONTHLY_GLUCOSE'
            );

        /*
        |--------------------------------------------------------------------------
        | 7. Show Monthly Glucose Check
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/monthly-glucose-checks/{$monthlyCheckId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'check.id',
                $monthlyCheckId
            )
            ->assertJsonPath(
                'check.record_source',
                'MONTHLY_GLUCOSE'
            );

        /*
        |--------------------------------------------------------------------------
        | 8. Duplicate Monthly Check
        |--------------------------------------------------------------------------
        */

        $duplicateMonthlyResponse = $this->postJson(
            '/api/residents/1/monthly-glucose-checks',
            [
                'blood_glucose' => 6.0,
                'glucose_measurement_type' => 'RANDOM',
                'glucose_notes' =>
                    'Duplicate monthly attempt.',
                'recorded_at' =>
                    '2026-09-25 11:00:00',
            ]
        );

        $duplicateMonthlyResponse
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A monthly glucose check already exists for this resident for September 2026.'
            )
            ->assertJsonPath(
                'existing_check.id',
                $monthlyCheckId
            );

        $this->assertSame(
            1,
            DB::table('vital_signs')
                ->where('resident_id', 1)
                ->where(
                    'record_source',
                    'MONTHLY_GLUCOSE'
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Weekly Vital Check
        |--------------------------------------------------------------------------
        */

        $weeklyResponse = $this->postJson(
            '/api/residents/1/weekly-vital-checks',
            [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'blood_glucose' => 5.6,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'temperature' => 36.7,
                'weight' => 65.5,
                'recorded_at' =>
                    $weeklyDate->toDateTimeString(),
            ]
        );

        $weeklyResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Weekly vital signs check recorded successfully.'
            )
            ->assertJsonPath(
                'check.resident_id',
                1
            )
            ->assertJsonPath(
                'check.blood_pressure_systolic',
                120
            )
            ->assertJsonPath(
                'check.blood_pressure_diastolic',
                80
            )
            ->assertJsonPath(
                'check.blood_glucose',
                5.6
            )
            ->assertJsonPath(
                'check.heart_rate',
                72
            )
            ->assertJsonPath(
                'check.oxygen_level',
                98
            )
            ->assertJsonPath(
                'check.temperature',
                36.7
            )
            ->assertJsonPath(
                'check.weight',
                65.5
            )
            ->assertJsonPath(
                'check.record_source',
                'WEEKLY_VITALS'
            )
            ->assertJsonPath(
                'check.recorded_by',
                1
            )
            ->assertJsonPath(
                'check.resident.id',
                1
            );

        $weeklyCheckId = $weeklyResponse->json(
            'check.id'
        );

        $this->assertNotNull(
            $weeklyCheckId
        );

        /*
        |--------------------------------------------------------------------------
        | 10. Weekly Vital Database Record
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'vital_signs',
            [
                'id' => $weeklyCheckId,
                'resident_id' => 1,
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'blood_glucose' => 5.6,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'temperature' => 36.7,
                'weight' => 65.5,
                'record_source' => 'WEEKLY_VITALS',
                'recorded_by' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Weekly Vital Clinical Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'VITAL',
                'event_title' =>
                    'Weekly Vital Signs Check',
                'source_type' =>
                    'WeeklyVitalSign',
                'source_id' =>
                    $weeklyCheckId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Weekly Vital Activity Audit
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' => 1,
                'resident_id' => 1,
                'module' => 'Weekly Vital Signs',
                'action' => 'CREATE',
                'description' =>
                    'Weekly vital signs check recorded for resident.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 13. Weekly Vital List
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/weekly-vital-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'checks.0.id',
                $weeklyCheckId
            )
            ->assertJsonPath(
                'checks.0.record_source',
                'WEEKLY_VITALS'
            )
            ->assertJsonPath(
                'checks.0.resident.id',
                1
            );

        /*
        |--------------------------------------------------------------------------
        | 14. Resident Weekly Vital History
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/residents/1/weekly-vital-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                1
            )
            ->assertJsonPath(
                'resident.status',
                'Active'
            )
            ->assertJsonPath(
                'checks.0.id',
                $weeklyCheckId
            )
            ->assertJsonPath(
                'checks.0.record_source',
                'WEEKLY_VITALS'
            );

        /*
        |--------------------------------------------------------------------------
        | 15. Show Weekly Vital Check
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/weekly-vital-checks/{$weeklyCheckId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'check.id',
                $weeklyCheckId
            )
            ->assertJsonPath(
                'check.record_source',
                'WEEKLY_VITALS'
            );

        /*
        |--------------------------------------------------------------------------
        | 16. Duplicate Weekly Check
        |--------------------------------------------------------------------------
        */

        $weekStart = $weeklyDate
            ->copy()
            ->startOfWeek();

        $duplicateWeeklyResponse = $this->postJson(
            '/api/residents/1/weekly-vital-checks',
            [
                'blood_pressure_systolic' => 121,
                'blood_pressure_diastolic' => 81,
                'blood_glucose' => 5.7,
                'heart_rate' => 73,
                'oxygen_level' => 98,
                'temperature' => 36.8,
                'weight' => 65.6,
                'recorded_at' =>
                    '2026-09-16 10:00:00',
            ]
        );

        $duplicateWeeklyResponse
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A weekly vital signs check already exists for this resident for the week of ' .
                $weekStart->format('d M Y') .
                '.'
            )
            ->assertJsonPath(
                'existing_check.id',
                $weeklyCheckId
            );

        $this->assertSame(
            1,
            DB::table('vital_signs')
                ->where('resident_id', 1)
                ->where(
                    'record_source',
                    'WEEKLY_VITALS'
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | 17. Source Separation
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'vital_signs',
            2
        );

        $this->assertSame(
            1,
            DB::table('vital_signs')
                ->where(
                    'record_source',
                    'MONTHLY_GLUCOSE'
                )
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('vital_signs')
                ->where(
                    'record_source',
                    'WEEKLY_VITALS'
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | 18. Normal AI Processing
        |--------------------------------------------------------------------------
        */
        
        $this->assertDatabaseCount(
            'ai_alerts',
            0
        );

        /*
        |--------------------------------------------------------------------------
        | 19. Activity Audit + Integrity
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            2,
            DB::table('activity_logs')
                ->where('user_id', 1)
                ->where('resident_id', 1)
                ->whereIn(
                    'module',
                    [
                        'Monthly Glucose Check',
                        'Weekly Vital Signs',
                    ]
                )
                ->where(
                    'action',
                    'CREATE'
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Every New Activity Log Is Integrity Protected
        |--------------------------------------------------------------------------
        */

        $activityLogIds = DB::table(
            'activity_logs'
        )
            ->where('user_id', 1)
            ->where('resident_id', 1)
            ->whereIn(
                'module',
                [
                    'Monthly Glucose Check',
                    'Weekly Vital Signs',
                ]
            )
            ->where(
                'action',
                'CREATE'
            )
            ->pluck('id');

        $this->assertCount(
            2,
            $activityLogIds
        );

        foreach ($activityLogIds as $activityLogId) {
            $this->assertDatabaseHas(
                'activity_log_integrity',
                [
                    'activity_log_id' =>
                        $activityLogId,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 20. Discharge Resident
        |--------------------------------------------------------------------------
        */

        $this->deleteJson(
            '/api/residents/1'
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident discharged successfully'
            );

        $this->assertDatabaseHas(
            'residents',
            [
                'id' => 1,
                'status' => 'Discharged',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 21. Monthly Glucose Rejected After Discharge
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            '/api/residents/1/monthly-glucose-checks',
            [
                'blood_glucose' => 5.4,
                'glucose_measurement_type' =>
                    'FASTING',
                'recorded_at' =>
                    '2026-10-10 09:00:00',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Monthly glucose checks can only be recorded for active residents.'
            );

        /*
        |--------------------------------------------------------------------------
        | 22. Weekly Vital Rejected After Discharge
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            '/api/residents/1/weekly-vital-checks',
            [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'blood_glucose' => 5.5,
                'heart_rate' => 72,
                'oxygen_level' => 98,
                'temperature' => 36.7,
                'weight' => 65.5,
                'recorded_at' =>
                    '2026-09-28 10:00:00',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Weekly vital checks can only be recorded for active residents.'
            );

        /*
        |--------------------------------------------------------------------------
        | 23. No New Clinical Records After Discharge
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'vital_signs',
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 24. Historical Monthly Record Remains Readable
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/residents/1/monthly-glucose-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.status',
                'Discharged'
            )
            ->assertJsonPath(
                'checks.0.id',
                $monthlyCheckId
            );

        /*
        |--------------------------------------------------------------------------
        | 25. Historical Weekly Record Remains Readable
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/residents/1/weekly-vital-checks'
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.status',
                'Discharged'
            )
            ->assertJsonPath(
                'checks.0.id',
                $weeklyCheckId
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
            config(
                'database.connections.sqlite.database'
            )
        );

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );
    }
}