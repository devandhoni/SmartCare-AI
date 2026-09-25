<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15FamilyFacilityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_and_facility_operational_workflows(): void
    {
        $this->assertTestingDatabaseIsolation();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Facility Nurse',
            'email' => 'f15-facility-nurse@example.test',
            'password' => bcrypt('password'),
            'status' => 'Active',
        ]);

        Sanctum::actingAs(
            User::findOrFail(1)
        );

        /*
        |--------------------------------------------------------------------------
        | Resident
        |--------------------------------------------------------------------------
        */

        $residentResponse = $this->postJson(
            '/api/residents',
            [
                'full_name' => 'F15 Facility Resident',
                'gender' => 'Female',
                'date_of_birth' => '1958-04-12',
            ]
        );

        $residentResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Resident registered successfully'
            );

        $residentId =
            $residentResponse->json('resident.id');

        $this->assertNotNull($residentId);

        /*
        |--------------------------------------------------------------------------
        | Parcel - Receive
        |--------------------------------------------------------------------------
        */

        $parcelResponse = $this->postJson(
            '/api/parcels',
            [
                'resident_id' => $residentId,
                'sender_name' => 'F15 Family Member',
                'courier_name' => 'F15 Courier',
                'tracking_number' => 'TRACK-F15-001',
                'parcel_description' => 'Clothing and toiletries',
                'quantity' => 2,
                'condition_on_arrival' => 'GOOD',
                'received_at' => '2026-09-25 09:00:00',
                'parcel_checked' => true,
                'resident_notified' => false,
                'receiving_notes' => 'Parcel checked on arrival.',
            ]
        );

        $parcelResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Parcel received successfully.'
            )
            ->assertJsonPath(
                'parcel.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'parcel.status',
                'RECEIVED'
            )
            ->assertJsonPath(
                'parcel.received_by.id',
                1
            )
            ->assertJsonPath(
                'parcel.received_by.full_name',
                'F15 Facility Nurse'
            );

        $parcelId =
            $parcelResponse->json('parcel.id');

        $parcelReference =
            $parcelResponse->json(
                'parcel.parcel_reference'
            );

        $this->assertNotNull($parcelId);
        $this->assertNotNull($parcelReference);
        $this->assertStringStartsWith(
            'PAR-',
            $parcelReference
        );

        $this->assertDatabaseHas(
            'resident_parcels',
            [
                'id' => $parcelId,
                'resident_id' => $residentId,
                'tracking_number' => 'TRACK-F15-001',
                'quantity' => 2,
                'condition_on_arrival' => 'GOOD',
                'received_by' => 1,
                'resident_notified' => 0,
                'status' => 'RECEIVED',
            ]
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_type' => 'DOCUMENT_UPLOAD',
                'event_title' => 'Parcel Received',
                'source_type' => 'ResidentParcelReceived',
                'source_id' => $parcelId,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'resident_id' => $residentId,
                'module' => 'Resident Parcel',
                'action' => 'CREATE',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Parcel - Update + Notify
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/parcels/{$parcelId}",
            [
                'sender_name' => 'F15 Family Member',
                'courier_name' => 'F15 Courier',
                'tracking_number' => 'TRACK-F15-001',
                'parcel_description' => 'Clothing, toiletries and books',
                'quantity' => 3,
                'condition_on_arrival' => 'GOOD',
                'received_at' => '2026-09-25 09:00:00',
                'parcel_checked' => true,
                'resident_notified' => false,
                'receiving_notes' => 'Contents confirmed.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Parcel updated successfully.'
            )
            ->assertJsonPath(
                'parcel.quantity',
                3
            );

        $this->postJson(
            "/api/parcels/{$parcelId}/notify"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident notification recorded.'
            )
            ->assertJsonPath(
                'parcel.resident_notified',
                true
            );

        /*
        |--------------------------------------------------------------------------
        | Parcel - Read Endpoints
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/parcels/{$parcelId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'parcel.id',
                $parcelId
            );

        $this->getJson(
            '/api/parcels?status=RECEIVED'
        )
            ->assertOk()
            ->assertJsonPath(
                'parcels.0.id',
                $parcelId
            );

        $this->getJson(
            "/api/residents/{$residentId}/parcels"
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                $residentId
            )
            ->assertJsonPath(
                'parcels.0.id',
                $parcelId
            );

        /*
        |--------------------------------------------------------------------------
        | Parcel - Collection
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/parcels/{$parcelId}/collect",
            [
                'collected_at' => '2026-09-25 10:00:00',
                'collected_by_name' => 'F15 Facility Resident',
                'collected_by_relationship' => 'Self',
                'collection_notes' => 'Parcel handed to resident.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Parcel collection recorded successfully.'
            )
            ->assertJsonPath(
                'parcel.status',
                'COLLECTED'
            )
            ->assertJsonPath(
                'parcel.collection_recorded_by.id',
                1
            );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_title' => 'Parcel Collected',
                'source_type' => 'ResidentParcelCollected',
                'source_id' => $parcelId,
            ]
        );

        $this->putJson(
            "/api/parcels/{$parcelId}",
            [
                'quantity' => 3,
                'condition_on_arrival' => 'GOOD',
                'received_at' => '2026-09-25 09:00:00',
                'parcel_checked' => true,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A collected parcel cannot be edited.'
            );

        $this->postJson(
            "/api/parcels/{$parcelId}/collect",
            [
                'collected_at' => '2026-09-25 10:30:00',
                'collected_by_name' => 'Duplicate Collection',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This parcel has already been collected.'
            );

        $this->deleteJson(
            "/api/parcels/{$parcelId}"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A collected parcel record cannot be deleted.'
            );

        /*
        |--------------------------------------------------------------------------
        | Home Leave - Start
        |--------------------------------------------------------------------------
        */

        $leaveResponse = $this->postJson(
            '/api/home-leaves',
            [
                'resident_id' => $residentId,
                'leave_at' => '2026-09-25 11:00:00',
                'reason_for_leave' => 'Family visit',
                'destination' => 'Family Home',
                'taken_by_name' => 'F15 Family Member',
                'taken_by_relationship' => 'Daughter',
                'taken_by_contact' => '0123456789',
                'expected_return_at' => '2026-09-26 18:00:00',
                'medication_handed_over' => true,
                'medication_instructions' => 'Follow medication schedule.',
                'belongings_taken' => true,
                'belongings_notes' => 'Personal bag and clothing.',
                'leave_notes' => 'Resident left in stable condition.',
            ]
        );

        $leaveResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Home leave recorded successfully.'
            )
            ->assertJsonPath(
                'home_leave.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'home_leave.status',
                'ON_LEAVE'
            )
            ->assertJsonPath(
                'home_leave.recorded_by.id',
                1
            );

        $leaveId =
            $leaveResponse->json('home_leave.id');

        $leaveReference =
            $leaveResponse->json(
                'home_leave.leave_reference'
            );

        $this->assertNotNull($leaveId);
        $this->assertNotNull($leaveReference);
        $this->assertStringStartsWith(
            'HL-',
            $leaveReference
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_title' => 'Home Leave Started',
                'source_type' => 'ResidentHomeLeaveStarted',
                'source_id' => $leaveId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Home Leave - Duplicate Active Leave
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            '/api/home-leaves',
            [
                'resident_id' => $residentId,
                'leave_at' => '2026-09-26 09:00:00',
                'taken_by_name' => 'Another Family Member',
                'expected_return_at' => '2026-09-26 15:00:00',
                'medication_handed_over' => false,
                'belongings_taken' => false,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This resident already has an active home leave record.'
            )
            ->assertJsonPath(
                'existing_home_leave.id',
                $leaveId
            );

        /*
        |--------------------------------------------------------------------------
        | Home Leave - Update
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/home-leaves/{$leaveId}",
            [
                'leave_at' => '2026-09-25 11:00:00',
                'reason_for_leave' => 'Extended family visit',
                'destination' => 'Family Home',
                'taken_by_name' => 'F15 Family Member',
                'taken_by_relationship' => 'Daughter',
                'taken_by_contact' => '0123456789',
                'expected_return_at' => '2026-09-26 20:00:00',
                'medication_handed_over' => true,
                'medication_instructions' => 'Follow medication schedule.',
                'belongings_taken' => true,
                'belongings_notes' => 'Personal bag and clothing.',
                'leave_notes' => 'Expected return updated.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Home leave updated successfully.'
            )
            ->assertJsonPath(
                'home_leave.reason_for_leave',
                'Extended family visit'
            );

        /*
        |--------------------------------------------------------------------------
        | Home Leave - Invalid Early Return
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/home-leaves/{$leaveId}/return",
            [
                'returned_at' => '2026-09-25 10:00:00',
                'condition_on_return' => 'Stable',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Return date and time cannot be earlier than the home leave departure date and time.'
            );

        /*
        |--------------------------------------------------------------------------
        | Home Leave - Return
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/home-leaves/{$leaveId}/return",
            [
                'returned_at' => '2026-09-26 19:30:00',
                'returned_by_name' => 'F15 Family Member',
                'returned_by_relationship' => 'Daughter',
                'returned_by_contact' => '0123456789',
                'condition_on_return' => 'Stable',
                'return_notes' => 'Resident returned safely.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident return recorded successfully.'
            )
            ->assertJsonPath(
                'home_leave.status',
                'RETURNED'
            )
            ->assertJsonPath(
                'home_leave.return_recorded_by.id',
                1
            );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_title' => 'Home Leave Returned',
                'source_type' => 'ResidentHomeLeaveReturned',
                'source_id' => $leaveId,
            ]
        );

        $this->getJson(
            "/api/home-leaves/{$leaveId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'home_leave.status',
                'RETURNED'
            );

        $this->getJson(
            '/api/home-leaves?status=RETURNED'
        )
            ->assertOk()
            ->assertJsonPath(
                'home_leaves.0.id',
                $leaveId
            );

        $this->getJson(
            "/api/residents/{$residentId}/home-leaves"
        )
            ->assertOk()
            ->assertJsonPath(
                'home_leaves.0.id',
                $leaveId
            );

        $this->putJson(
            "/api/home-leaves/{$leaveId}",
            [
                'leave_at' => '2026-09-25 11:00:00',
                'taken_by_name' => 'F15 Family Member',
                'expected_return_at' => '2026-09-26 20:00:00',
                'medication_handed_over' => true,
                'belongings_taken' => true,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A completed home leave record cannot be edited.'
            );

        $this->deleteJson(
            "/api/home-leaves/{$leaveId}"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A completed home leave record cannot be deleted.'
            );

        /*
        |--------------------------------------------------------------------------
        | Visitor - Check In
        |--------------------------------------------------------------------------
        */

        $visitorResponse = $this->postJson(
            '/api/visitors',
            [
                'resident_id' => $residentId,
                'visitor_name' => 'F15 Visitor',
                'relationship' => 'Friend',
                'contact_number' => '0199999999',
                'id_number' => 'F15-ID-001',
                'purpose_of_visit' => 'Social visit',
                'number_of_visitors' => 2,
                'visit_notes' => 'Routine visit.',
                'checked_in_at' => '2026-09-27 14:00:00',
            ]
        );

        $visitorResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Visitor checked in successfully.'
            )
            ->assertJsonPath(
                'visitor.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'visitor.status',
                'CHECKED_IN'
            )
            ->assertJsonPath(
                'visitor.checked_in_by.id',
                1
            );

        $visitorId =
            $visitorResponse->json('visitor.id');

        $visitReference =
            $visitorResponse->json(
                'visitor.visit_reference'
            );

        $this->assertNotNull($visitorId);
        $this->assertNotNull($visitReference);
        $this->assertStringStartsWith(
            'VIS-',
            $visitReference
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_title' => 'Visitor Checked In',
                'source_type' => 'ResidentVisitorCheckedIn',
                'source_id' => $visitorId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Visitor - Update
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/visitors/{$visitorId}",
            [
                'visitor_name' => 'F15 Visitor',
                'relationship' => 'Family Friend',
                'contact_number' => '0199999999',
                'id_number' => 'F15-ID-001',
                'purpose_of_visit' => 'Social visit',
                'number_of_visitors' => 2,
                'visit_notes' => 'Relationship description updated.',
                'checked_in_at' => '2026-09-27 14:00:00',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Visitor record updated successfully.'
            )
            ->assertJsonPath(
                'visitor.relationship',
                'Family Friend'
            );

        /*
        |--------------------------------------------------------------------------
        | Visitor - Invalid Checkout
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/visitors/{$visitorId}/checkout",
            [
                'checked_out_at' => '2026-09-27 13:00:00',
                'checkout_notes' => 'Invalid early checkout.',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Check-out date and time cannot be earlier than check-in date and time.'
            );

        /*
        |--------------------------------------------------------------------------
        | Visitor - Checkout
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/visitors/{$visitorId}/checkout",
            [
                'checked_out_at' => '2026-09-27 16:00:00',
                'checkout_notes' => 'Visit completed normally.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Visitor checked out successfully.'
            )
            ->assertJsonPath(
                'visitor.status',
                'CHECKED_OUT'
            )
            ->assertJsonPath(
                'visitor.checked_out_by.id',
                1
            );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' => $residentId,
                'event_title' => 'Visitor Checked Out',
                'source_type' => 'ResidentVisitorCheckedOut',
                'source_id' => $visitorId,
            ]
        );

        $this->getJson(
            "/api/visitors/{$visitorId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'visitor.status',
                'CHECKED_OUT'
            );

        $this->getJson(
            '/api/visitors?status=CHECKED_OUT'
        )
            ->assertOk()
            ->assertJsonPath(
                'visitors.0.id',
                $visitorId
            );

        $this->getJson(
            "/api/residents/{$residentId}/visitors"
        )
            ->assertOk()
            ->assertJsonPath(
                'visitors.0.id',
                $visitorId
            );

        $this->putJson(
            "/api/visitors/{$visitorId}",
            [
                'visitor_name' => 'F15 Visitor',
                'number_of_visitors' => 2,
                'checked_in_at' => '2026-09-27 14:00:00',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A completed visitor record cannot be edited.'
            );

        $this->deleteJson(
            "/api/visitors/{$visitorId}"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A completed visitor record cannot be deleted.'
            );

        /*
        |--------------------------------------------------------------------------
        | Family Message Log
        |--------------------------------------------------------------------------
        |
        | We insert a controlled FAILED record directly because this acceptance
        | slice is testing the family-message management API. Medication-created
        | PENDING notification generation is already covered by the F15
        | medication workflow.
        |
        */

        $familyMessageId =
            DB::table('family_message_logs')
                ->insertGetId([
                    'resident_id' => $residentId,
                    'resident_contact_id' => null,
                    'channel' => 'WHATSAPP',
                    'message_type' => 'MEDICATION_MEAL_COMPLETED',
                    'message' =>
                        'F15 controlled failed family message.',
                    'source_type' => 'F15Acceptance',
                    'source_id' => 999,
                    'recipient_name' => 'F15 Family Member',
                    'recipient_number' => '0123456789',
                    'status' => 'FAILED',
                    'provider' => 'TEST',
                    'provider_message_id' => null,
                    'attempt_count' => 1,
                    'sent_at' => null,
                    'failed_at' => now(),
                    'failure_reason' => 'Controlled F15 failure.',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        /*
        |--------------------------------------------------------------------------
        | Family Message - Search / Summary
        |--------------------------------------------------------------------------
        */

        $familyMessagesResponse =
            $this->getJson(
                "/api/family-messages?resident_id={$residentId}&status=FAILED&search=F15&per_page=10"
            );

        $familyMessagesResponse
            ->assertOk()
            ->assertJsonPath(
                'summary.total',
                1
            )
            ->assertJsonPath(
                'summary.failed',
                1
            )
            ->assertJsonPath(
                'summary.pending',
                0
            )
            ->assertJsonPath(
                'messages.data.0.id',
                $familyMessageId
            )
            ->assertJsonPath(
                'messages.data.0.status',
                'FAILED'
            );

        /*
        |--------------------------------------------------------------------------
        | Family Message - NULL Provider Retry Safety
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/family-messages/{$familyMessageId}/retry"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'WhatsApp sending is not configured yet. The failed message remains unchanged.'
            );

        $this->assertDatabaseHas(
            'family_message_logs',
            [
                'id' => $familyMessageId,
                'status' => 'FAILED',
                'provider' => 'TEST',
                'attempt_count' => 1,
                'failure_reason' =>
                    'Controlled F15 failure.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Non-Failed Family Message Cannot Be Retried
        |--------------------------------------------------------------------------
        */

        $pendingMessageId =
            DB::table('family_message_logs')
                ->insertGetId([
                    'resident_id' => $residentId,
                    'resident_contact_id' => null,
                    'channel' => 'WHATSAPP',
                    'message_type' => 'MEDICATION_MEAL_COMPLETED',
                    'message' =>
                        'F15 controlled pending family message.',
                    'source_type' => 'F15Acceptance',
                    'source_id' => 1000,
                    'recipient_name' => 'F15 Family Member',
                    'recipient_number' => '0123456789',
                    'status' => 'PENDING',
                    'provider' => 'NULL',
                    'provider_message_id' => null,
                    'attempt_count' => 0,
                    'sent_at' => null,
                    'failed_at' => null,
                    'failure_reason' => null,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $this->postJson(
            "/api/family-messages/{$pendingMessageId}/retry"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Only failed family messages can be retried.'
            );

        /*
        |--------------------------------------------------------------------------
        | Activity Log Coverage
        |--------------------------------------------------------------------------
        */

        $this->assertGreaterThanOrEqual(
            3,
            DB::table('activity_logs')
                ->where(
                    'module',
                    'Resident Parcel'
                )
                ->count()
        );

        $this->assertGreaterThanOrEqual(
            3,
            DB::table('activity_logs')
                ->where(
                    'module',
                    'Resident Home Leave'
                )
                ->count()
        );

        $this->assertGreaterThanOrEqual(
            3,
            DB::table('activity_logs')
                ->where(
                    'module',
                    'Resident Visitor'
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Final Persistence
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'resident_parcels',
            1
        );

        $this->assertDatabaseCount(
            'resident_home_leaves',
            1
        );

        $this->assertDatabaseCount(
            'resident_visitors',
            1
        );

        $this->assertDatabaseCount(
            'family_message_logs',
            2
        );

        $this->assertDatabaseHas(
            'resident_parcels',
            [
                'id' => $parcelId,
                'status' => 'COLLECTED',
                'collection_recorded_by' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'resident_home_leaves',
            [
                'id' => $leaveId,
                'status' => 'RETURNED',
                'return_recorded_by' => 1,
                'condition_on_return' => 'Stable',
            ]
        );

        $this->assertDatabaseHas(
            'resident_visitors',
            [
                'id' => $visitorId,
                'status' => 'CHECKED_OUT',
                'checked_out_by' => 1,
            ]
        );

        $this->assertTestingDatabaseIsolation();
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