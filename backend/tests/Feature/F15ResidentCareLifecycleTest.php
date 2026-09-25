<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15ResidentCareLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurse_can_complete_resident_and_care_lifecycle(): void
    {
        $this->assertTestingDatabaseIsolation();

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Lifecycle Nurse',
            'email' => 'f15-lifecycle-nurse@example.test',
            'password' => 'test-only',
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        /*
         * --------------------------------------------------------------
         * 1. Register resident
         * --------------------------------------------------------------
         */

        $createResponse = $this->postJson(
            '/api/residents',
            [
                'full_name' => 'F15 Lifecycle Resident',
                'ic_number' => 'F15-RESIDENT-001',
                'date_of_birth' => '1950-01-15',
                'gender' => 'Female',
                'nationality' => 'Malaysian',
                'phone' => '0123456789',
                'email' => 'resident@example.test',
                'blood_type' => 'O+',
                'medical_condition' => 'Hypertension',
                'allergies' => 'None known',
                'admission_date' => '2026-09-25',
            ]
        );

        $createResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Resident registered successfully'
            )
            ->assertJsonPath(
                'resident.full_name',
                'F15 Lifecycle Resident'
            )
            ->assertJsonPath(
                'resident.status',
                'Active'
            );

        $residentId = $createResponse->json(
            'resident.id'
        );

        $this->assertNotNull($residentId);

        $this->assertDatabaseHas(
            'residents',
            [
                'id' => $residentId,
                'full_name' => 'F15 Lifecycle Resident',
                'status' => 'Active',
                'medical_condition' => 'Hypertension',
            ]
        );

        /*
         * Admission timeline event promised by ResidentController.
         */
        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_type' => 'ADMISSION',
                'event_title' => 'Resident Admission',
                'source_type' => 'Resident',
                'source_id' => $residentId,
            ]
        );

        /*
         * --------------------------------------------------------------
         * 2. Resident appears in active list
         * --------------------------------------------------------------
         */

        $this->getJson('/api/residents')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $residentId,
                'full_name' => 'F15 Lifecycle Resident',
                'status' => 'Active',
            ]);

        /*
         * --------------------------------------------------------------
         * 3. Retrieve resident
         * --------------------------------------------------------------
         */

        $this->getJson(
            "/api/residents/{$residentId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $residentId
            )
            ->assertJsonPath(
                'full_name',
                'F15 Lifecycle Resident'
            )
            ->assertJsonPath(
                'status',
                'Active'
            );

        /*
         * --------------------------------------------------------------
         * 4. Update resident profile
         * --------------------------------------------------------------
         */

        $this->putJson(
            "/api/residents/{$residentId}",
            [
                'phone' => '0198765432',
                'medical_notes' =>
                    'Requires routine blood pressure monitoring.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident updated successfully'
            )
            ->assertJsonPath(
                'resident.phone',
                '0198765432'
            )
            ->assertJsonPath(
                'resident.medical_notes',
                'Requires routine blood pressure monitoring.'
            );

        $this->assertDatabaseHas(
            'residents',
            [
                'id' => $residentId,
                'phone' => '0198765432',
                'medical_notes' =>
                    'Requires routine blood pressure monitoring.',
            ]
        );

        /*
         * ResidentController currently records profile updates as
         * DOCUMENT_UPLOAD timeline events.
         */
        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_type' => 'DOCUMENT_UPLOAD',
                'event_title' => 'Resident Profile Updated',
                'source_type' => 'Resident',
                'source_id' => $residentId,
            ]
        );

        /*
         * --------------------------------------------------------------
         * 5. Create care record
         * --------------------------------------------------------------
         */

        $careResponse = $this->postJson(
            "/api/residents/{$residentId}/care-records",
            [
                'care_type' => 'Daily Care',
                'title' => 'Morning hygiene completed',
                'notes' =>
                    'Resident assisted with morning hygiene.',
                'care_status' => 'COMPLETED',
                'recorded_at' => '2026-09-25 09:00:00',
            ]
        );

        $careResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Care record created successfully.'
            )
            ->assertJsonPath(
                'care_record.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'care_record.care_type',
                'Daily Care'
            )
            ->assertJsonPath(
                'care_record.care_status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'care_record.recorded_by',
                1
            )
            ->assertJsonPath(
                'care_record.recorder.full_name',
                'F15 Lifecycle Nurse'
            );

        $careRecordId = $careResponse->json(
            'care_record.id'
        );

        $this->assertNotNull($careRecordId);

        $this->assertDatabaseHas(
            'care_records',
            [
                'id' => $careRecordId,
                'resident_id' => $residentId,
                'care_type' => 'Daily Care',
                'title' => 'Morning hygiene completed',
                'care_status' => 'COMPLETED',
                'recorded_by' => 1,
            ]
        );

        /*
         * --------------------------------------------------------------
         * 6. Retrieve resident care records
         * --------------------------------------------------------------
         */

        $this->getJson(
            "/api/residents/{$residentId}/care-records"
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                $residentId
            )
            ->assertJsonPath(
                'resident.full_name',
                'F15 Lifecycle Resident'
            )
            ->assertJsonPath(
                'care_records.0.id',
                $careRecordId
            )
            ->assertJsonPath(
                'care_records.0.title',
                'Morning hygiene completed'
            )
            ->assertJsonPath(
                'care_records.0.recorder.full_name',
                'F15 Lifecycle Nurse'
            );

        /*
         * --------------------------------------------------------------
         * 7. Discharge resident
         * --------------------------------------------------------------
         */

        $this->deleteJson(
            "/api/residents/{$residentId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident discharged successfully'
            )
            ->assertJsonPath(
                'resident.status',
                'Discharged'
            );

        $resident = DB::table('residents')
            ->where('id', $residentId)
            ->first();

        $this->assertNotNull($resident);
        $this->assertSame(
            'Discharged',
            $resident->status
        );
        $this->assertNotNull(
            $resident->discharge_date
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_type' => 'DISCHARGE',
                'event_title' => 'Resident Discharge',
                'source_type' => 'Resident',
                'source_id' => $residentId,
            ]
        );

        /*
         * --------------------------------------------------------------
         * 8. Discharged resident disappears from active list
         * --------------------------------------------------------------
         */

        $this->getJson('/api/residents')
            ->assertOk()
            ->assertJsonMissing([
                'id' => $residentId,
                'full_name' => 'F15 Lifecycle Resident',
            ]);

        /*
         * --------------------------------------------------------------
         * 9. Discharged resident appears in archive
         * --------------------------------------------------------------
         */

        $this->getJson('/api/residents/archive')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $residentId,
                'full_name' => 'F15 Lifecycle Resident',
                'status' => 'Discharged',
            ]);

        /*
         * --------------------------------------------------------------
         * 10. No new care record after discharge
         * --------------------------------------------------------------
         */

        $this->postJson(
            "/api/residents/{$residentId}/care-records",
            [
                'care_type' => 'Daily Care',
                'title' => 'Should not be created',
                'care_status' => 'COMPLETED',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Care records can only be added for active residents.'
            );

        $this->assertDatabaseCount(
            'care_records',
            1
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