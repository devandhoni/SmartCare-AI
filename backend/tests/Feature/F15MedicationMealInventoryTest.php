<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15MedicationMealInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_am_medication_meal_inventory_and_family_notification_workflow(): void
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
            'full_name' => 'F15 Medication Nurse',
            'email' => 'f15-medication-nurse@example.test',
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
            'full_name' => 'F15 Medication Resident',
            'status' => 'Active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Medication
        |--------------------------------------------------------------------------
        */

        DB::table('medications')->insert([
            'id' => 1,
            'medicine_name' => 'F15 Test Medicine',
            'category' => 'Routine',
            'dosage' => '500',
            'unit' => 'mg',
            'supplier' => 'F15 Supplier',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        DB::table('medicine_inventory')->insert([
            'id' => 1,
            'medication_id' => 1,
            'quantity' => 10,
            'minimum_stock' => 2,
            'expiry_date' => now()
                ->addYear()
                ->toDateString(),
            'location' => 'Medication Room',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Family Contact
        |--------------------------------------------------------------------------
        */

        DB::table('resident_contacts')->insert([
            'id' => 1,
            'resident_id' => 1,
            'full_name' => 'F15 Family Contact',
            'relationship' => 'Daughter',
            'phone' => '0120000000',
            'whatsapp_number' => '60120000000',
            'is_primary' => true,
            'is_emergency_contact' => true,
            'whatsapp_enabled' => true,
            'medication_notifications_enabled' => true,
            'care_notifications_enabled' => false,
            'created_by' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Assign AM Medication
        |--------------------------------------------------------------------------
        */

        $assignResponse = $this->postJson(
            '/api/residents/1/medications',
            [
                'medication_id' => 1,
                'dosage_instruction' => 'Take after food',
                'dosage_quantity' => 2,
                'frequency' => 'Once daily',
                'time_slot' => 'AM',
                'scheduled_time' => '08:00',
                'start_date' => today()->toDateString(),
                'prescribed_by' => 'F15 Test Doctor',
            ]
        );

        $assignResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Medication assigned successfully'
            )
            ->assertJsonPath(
                'resident_medication.resident_id',
                1
            )
            ->assertJsonPath(
                'resident_medication.medication_id',
                1
            )
            ->assertJsonPath(
                'resident_medication.dosage_quantity',
                2
            )
            ->assertJsonPath(
                'resident_medication.time_slot',
                'AM'
            );

        $residentMedicationId = $assignResponse->json(
            'resident_medication.id'
        );

        $this->assertNotNull(
            $residentMedicationId
        );

        $this->assertDatabaseHas(
            'resident_medications',
            [
                'id' => $residentMedicationId,
                'resident_id' => 1,
                'medication_id' => 1,
                'dosage_quantity' => 2,
                'time_slot' => 'AM',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Schedule Before Administration
        |--------------------------------------------------------------------------
        */

        $scheduleBefore = $this->getJson(
            '/api/residents/1/medication-schedule'
        );

        $scheduleBefore
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
                'can_administer',
                true
            )
            ->assertJsonPath(
                'schedule.AM.0.resident_medication_id',
                $residentMedicationId
            )
            ->assertJsonPath(
                'schedule.AM.0.status',
                'PENDING'
            )
            ->assertJsonPath(
                'schedule.AM.0.meal_type',
                'Breakfast'
            )
            ->assertJsonPath(
                'schedule.AM.0.meal_confirmed',
                false
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Complete AM Medication + Breakfast
        |--------------------------------------------------------------------------
        */

        $completeResponse = $this->putJson(
            "/api/medication-administration/{$residentMedicationId}/complete",
            [
                'status' => 'COMPLETED',
                'meal_confirmed' => true,
                'meal_notes' => 'Breakfast completed normally.',
            ]
        );

        $completeResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Medication administration recorded successfully.'
            )
            ->assertJsonPath(
                'round_complete',
                true
            )
            ->assertJsonPath(
                'family_notification_eligible',
                true
            )
            ->assertJsonPath(
                'administration_record.resident_id',
                1
            )
            ->assertJsonPath(
                'administration_record.resident_medication_id',
                $residentMedicationId
            )
            ->assertJsonPath(
                'administration_record.time_slot',
                'AM'
            )
            ->assertJsonPath(
                'administration_record.status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'administration_record.completed_by.id',
                1
            )
            ->assertJsonPath(
                'administration_record.completed_by.full_name',
                'F15 Medication Nurse'
            )
            ->assertJsonPath(
                'administration_record.meal_type',
                'Breakfast'
            )
            ->assertJsonPath(
                'administration_record.meal_confirmed',
                true
            )
            ->assertJsonPath(
                'family_notification.eligible',
                true
            )
            ->assertJsonPath(
                'family_notification.created',
                1
            )
            ->assertJsonPath(
                'family_notification.existing',
                0
            )
            ->assertJsonPath(
                'family_notification.failed',
                0
            );

        $administrationRecordId = $completeResponse->json(
            'administration_record.id'
        );

        $this->assertNotNull(
            $administrationRecordId
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Medication Administration Record
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'medication_administration_records',
            [
                'id' => $administrationRecordId,
                'resident_id' => 1,
                'resident_medication_id' => $residentMedicationId,
                'time_slot' => 'AM',
                'status' => 'COMPLETED',
                'completed_by' => 1,
                'meal_type' => 'Breakfast',
                'meal_confirmed' => 1,
                'meal_notes' => 'Breakfast completed normally.',
            ]
        );

        $administrationRecord = DB::table(
            'medication_administration_records'
        )
            ->where(
                'id',
                $administrationRecordId
            )
            ->first();

        $this->assertNotNull(
            $administrationRecord
        );

        $this->assertNotNull(
            $administrationRecord->administered_date
        );

        $this->assertNotNull(
            $administrationRecord->completed_time
        );

        $this->assertNotNull(
            $administrationRecord->created_on
        );

        $this->assertNotNull(
            $administrationRecord->updated_on
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Inventory Deduction
        |--------------------------------------------------------------------------
        |
        | Starting quantity = 10
        | Dosage quantity   = 2
        | Expected balance  = 8
        |
        */

        $this->assertDatabaseHas(
            'medicine_inventory',
            [
                'id' => 1,
                'medication_id' => 1,
                'quantity' => 8,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Inventory OUT Transaction
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'medicine_transactions',
            [
                'medication_id' => 1,
                'resident_id' => 1,
                'transaction_type' => 'OUT',
                'quantity' => 2,
                'reference' => 'Resident medication administration',
                'performed_by' => 1,
            ]
        );

        $this->assertDatabaseCount(
            'medicine_transactions',
            1
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Medication Given Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'MEDICATION_GIVEN',
                'event_title' => 'Medication Administered',
                'source_type' => 'MedicationAdministration',
                'source_id' => $administrationRecordId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Breakfast Confirmation Timeline
        |--------------------------------------------------------------------------
        |
        | There is no dedicated medication-meal ClinicalEventType.
        | ClinicalTimelineService intentionally records this as NURSE_ACTION.
        |
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => 1,
                'event_type' => 'NURSE_ACTION',
                'event_title' => 'Medication Meal Confirmed',
                'source_type' => 'MedicationMealConfirmation',
                'source_id' => $administrationRecordId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Family Notification Audit
        |--------------------------------------------------------------------------
        |
        | Default WhatsApp provider is NULL, so this verifies that the
        | notification is safely recorded without external delivery.
        |
        */

        $expectedMessage =
            'F15 Medication Resident has taken the AM medication and breakfast.';

        $this->assertDatabaseHas(
            'family_message_logs',
            [
                'resident_id' => 1,
                'resident_contact_id' => 1,
                'channel' => 'WHATSAPP',
                'message_type' => 'MEDICATION_MEAL_COMPLETED',
                'message' => $expectedMessage,
                'source_type' => 'MedicationAdministrationRecord',
                'source_id' => $administrationRecordId,
                'recipient_name' => 'F15 Family Contact',
                'recipient_number' => '60120000000',
                'status' => 'PENDING',
                'provider' => 'NULL',
                'attempt_count' => 0,
                'created_by' => 1,
            ]
        );

        $this->assertDatabaseCount(
            'family_message_logs',
            1
        );

        /*
        |--------------------------------------------------------------------------
        | 10. Schedule After Administration
        |--------------------------------------------------------------------------
        */

        $scheduleAfter = $this->getJson(
            '/api/residents/1/medication-schedule'
        );

        $scheduleAfter
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                1
            )
            ->assertJsonPath(
                'can_administer',
                true
            )
            ->assertJsonPath(
                'schedule.AM.0.resident_medication_id',
                $residentMedicationId
            )
            ->assertJsonPath(
                'schedule.AM.0.status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'schedule.AM.0.administration_record_id',
                $administrationRecordId
            )
            ->assertJsonPath(
                'schedule.AM.0.meal_type',
                'Breakfast'
            )
            ->assertJsonPath(
                'schedule.AM.0.meal_confirmed',
                true
            );

        /*
        |--------------------------------------------------------------------------
        | 11. Medication History Endpoint
        |--------------------------------------------------------------------------
        |
        | Verify that the completed administration is represented in history.
        | We avoid assuming presentation-only field names beyond the core
        | administration data.
        |
        */

        $historyResponse = $this->getJson(
            '/api/residents/1/medication/history'
        );

        $historyResponse
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                1
            )
            ->assertJsonPath(
                'resident.status',
                'Active'
            );

        /*
        |--------------------------------------------------------------------------
        | 12. Duplicate Final Outcome Protection
        |--------------------------------------------------------------------------
        */

        $duplicateResponse = $this->putJson(
            "/api/medication-administration/{$residentMedicationId}/complete",
            [
                'status' => 'COMPLETED',
                'meal_confirmed' => true,
            ]
        );

        $duplicateResponse
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A final medication outcome has already been recorded today.'
            );

        /*
        |--------------------------------------------------------------------------
        | 13. No Duplicate Side Effects
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'medicine_inventory',
            [
                'id' => 1,
                'medication_id' => 1,
                'quantity' => 8,
            ]
        );

        $this->assertDatabaseCount(
            'medicine_transactions',
            1
        );

        $this->assertDatabaseCount(
            'medication_administration_records',
            1
        );

        $this->assertDatabaseCount(
            'family_message_logs',
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Exactly One Medication-Given Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            DB::table('clinical_timelines')
                ->where(
                    'resident_id',
                    1
                )
                ->where(
                    'event_type',
                    'MEDICATION_GIVEN'
                )
                ->where(
                    'event_title',
                    'Medication Administered'
                )
                ->where(
                    'source_type',
                    'MedicationAdministration'
                )
                ->where(
                    'source_id',
                    $administrationRecordId
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Exactly One Meal-Confirmation Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            DB::table('clinical_timelines')
                ->where(
                    'resident_id',
                    1
                )
                ->where(
                    'event_type',
                    'NURSE_ACTION'
                )
                ->where(
                    'event_title',
                    'Medication Meal Confirmed'
                )
                ->where(
                    'source_type',
                    'MedicationMealConfirmation'
                )
                ->where(
                    'source_id',
                    $administrationRecordId
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Final Inventory Safety Check
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            8,
            (int) DB::table('medicine_inventory')
                ->where(
                    'id',
                    1
                )
                ->value('quantity')
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