<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15AdmissionDischargeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_formal_admission_consent_and_discharge_workflow(): void
    {
        $this->assertTestingDatabaseIsolation();

        /*
        |--------------------------------------------------------------------------
        | 1. Authentication
        |--------------------------------------------------------------------------
        */

        DB::table('roles')->insert([
            'id' => 4,
            'role_name' => 'Nurse',
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 4,
            'full_name' => 'F15 Admission Nurse',
            'email' => 'f15-admission-nurse@example.test',
            'password' => bcrypt('password'),
            'status' => 'Active',
        ]);

        $user = User::findOrFail(1);

        Sanctum::actingAs($user);

        /*
        |--------------------------------------------------------------------------
        | 2. Admission Draft Autosave
        |--------------------------------------------------------------------------
        */

        $draftResponse = $this->postJson(
            '/api/admission-drafts',
            [
                'full_name' => 'F15 Admission Resident',
                'current_step' => 2,
                'form_data' => [
                    'full_name' => 'F15 Admission Resident',
                    'gender' => 'Male',
                    'notes' => 'Initial admission draft.',
                ],
            ]
        );

        $draftResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Admission draft saved successfully.'
            )
            ->assertJsonPath(
                'draft.full_name',
                'F15 Admission Resident'
            )
            ->assertJsonPath(
                'draft.current_step',
                2
            )
            ->assertJsonPath(
                'draft.status',
                'DRAFT'
            )
            ->assertJsonPath(
                'draft.created_by',
                1
            )
            ->assertJsonPath(
                'draft.updated_by',
                1
            );

        $draftId = $draftResponse->json('draft.id');

        $this->assertNotNull($draftId);

        $draftReference =
            $draftResponse->json('draft.draft_reference');

        $this->assertNotNull($draftReference);
        $this->assertStringStartsWith(
            'DRF-',
            $draftReference
        );

        $this->assertDatabaseHas(
            'resident_admission_drafts',
            [
                'id' => $draftId,
                'full_name' => 'F15 Admission Resident',
                'current_step' => 2,
                'status' => 'DRAFT',
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Update Admission Draft
        |--------------------------------------------------------------------------
        */

        $draftUpdateResponse = $this->putJson(
            "/api/admission-drafts/{$draftId}",
            [
                'full_name' => 'F15 Admission Resident',
                'current_step' => 5,
                'form_data' => [
                    'full_name' => 'F15 Admission Resident',
                    'gender' => 'Male',
                    'notes' => 'Admission draft updated.',
                ],
            ]
        );

        $draftUpdateResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission draft updated successfully.'
            )
            ->assertJsonPath(
                'draft.current_step',
                5
            )
            ->assertJsonPath(
                'draft.form_data.notes',
                'Admission draft updated.'
            );

        $this->getJson('/api/admission-drafts')
            ->assertOk()
            ->assertJsonPath(
                'drafts.0.id',
                $draftId
            );

        $this->getJson(
            "/api/admission-drafts/{$draftId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'draft.id',
                $draftId
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Delete Autosave Draft
        |--------------------------------------------------------------------------
        */

        $this->deleteJson(
            "/api/admission-drafts/{$draftId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission draft deleted successfully.'
            );

        $this->assertDatabaseMissing(
            'resident_admission_drafts',
            [
                'id' => $draftId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Create Resident
        |--------------------------------------------------------------------------
        */

        $residentResponse = $this->postJson(
            '/api/residents',
            [
                'full_name' => 'F15 Formal Admission Resident',
                'gender' => 'Male',
                'date_of_birth' => '1955-05-10',
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
        | 6. Create Formal Admission
        |--------------------------------------------------------------------------
        */

        $admissionResponse = $this->postJson(
            '/api/admissions',
            [
                'resident_id' => $residentId,
                'admitted_at' => '2026-09-20 10:30:00',
                'admission_type' => 'NEW_ADMISSION',
                'admission_source' => 'Family Referral',
                'reason_for_admission' =>
                    'Requires ongoing nursing care.',
                'medical_summary' =>
                    'Stable at admission.',
                'mobility_notes' =>
                    'Walks with assistance.',
                'dietary_notes' =>
                    'Normal diet.',
                'special_care_instructions' =>
                    'Routine observation.',
                'belongings_notes' =>
                    'Personal clothing received.',

                'medical_history' => [
                    'primary_psychiatric_diagnosis' =>
                        'None reported',
                    'other_diagnoses' =>
                        'Hypertension history',
                    'tobacco_use' => false,
                    'alcohol_use' => false,
                    'drug_use' => false,
                    'family_psychiatric_history' => false,
                    'family_substance_abuse_history' => false,
                    'initial_observations' =>
                        'Resident calm and cooperative.',
                    'additional_comments' =>
                        'No additional concerns.',
                ],

                'hospitalizations' => [
                    [
                        'hospital_name' =>
                            'F15 General Hospital',
                        'hospitalization_date' =>
                            '2026-01-15',
                        'reason' =>
                            'Routine medical assessment',
                        'notes' =>
                            'Discharged stable.',
                    ],
                ],
            ]
        );

        $admissionResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Admission created successfully.'
            )
            ->assertJsonPath(
                'admission.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'admission.status',
                'DRAFT'
            )
           ->assertJsonPath(
                'admission.admitted_by.id',
                1
            )
            ->assertJsonPath(
                'admission.admitted_by.full_name',
                'F15 Admission Nurse'
            );

        $admissionId =
            $admissionResponse->json('admission.id');

        $this->assertNotNull($admissionId);

        $admissionNumber =
            $admissionResponse->json(
                'admission.admission_number'
            );

        $this->assertNotNull($admissionNumber);
        $this->assertStringStartsWith(
            'ADM-',
            $admissionNumber
        );

        $this->assertDatabaseHas(
            'resident_admissions',
            [
                'id' => $admissionId,
                'resident_id' => $residentId,
                'admission_type' => 'NEW_ADMISSION',
                'status' => 'DRAFT',
                'admitted_by' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'resident_admission_medical_histories',
            [
                'resident_admission_id' =>
                    $admissionId,
                'resident_id' =>
                    $residentId,
                'primary_psychiatric_diagnosis' =>
                    'None reported',
            ]
        );

        $this->assertDatabaseHas(
            'resident_admission_hospitalizations',
            [
                'resident_admission_id' =>
                    $admissionId,
                'resident_id' =>
                    $residentId,
                'hospital_name' =>
                    'F15 General Hospital',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Admission Cannot Complete Without Consent
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/admissions/{$admissionId}/complete"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Admission consent must be completed before admission completion.'
            );

        $this->assertDatabaseHas(
            'resident_admissions',
            [
                'id' => $admissionId,
                'status' => 'DRAFT',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Pending Consent Is Not Enough
        |--------------------------------------------------------------------------
        */

        $pendingConsentResponse = $this->postJson(
            "/api/admissions/{$admissionId}/consent",
            [
                'consent_given_by' =>
                    'F15 Family Representative',
                'relationship' =>
                    'Son',
                'contact_number' =>
                    '0123456789',

                'admission_consent' => true,
                'care_consent' => true,
                'medication_consent' => true,
                'emergency_treatment_consent' => true,
                'information_sharing_consent' => true,
                'family_notification_consent' => true,

                // Deliberately false to keep consent pending.
                'terms_acknowledged' => false,

                'consent_notes' =>
                    'Pending final terms acknowledgement.',
            ]
        );

        $pendingConsentResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission consent saved as pending.'
            )
            ->assertJsonPath(
                'consent.status',
                'PENDING'
            );

        $this->postJson(
            "/api/admissions/{$admissionId}/complete"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Admission consent must be completed before admission completion.'
            );

        /*
        |--------------------------------------------------------------------------
        | 9. Complete Enhanced Consent Agreement
        |--------------------------------------------------------------------------
        */

        $consentResponse = $this->postJson(
            "/api/admissions/{$admissionId}/consent",
            [
                'consent_given_by' =>
                    'F15 Family Representative',
                'relationship' =>
                    'Son',
                'contact_number' =>
                    '0123456789',

                'admission_consent' => true,
                'care_consent' => true,
                'medication_consent' => true,
                'emergency_treatment_consent' => true,
                'information_sharing_consent' => true,
                'family_notification_consent' => true,
                'terms_acknowledged' => true,

                'consent_notes' =>
                    'All admission terms accepted.',

                'agreement_version' =>
                    'F15-V1',
                'agreement_title' =>
                    'SmartCare Admission Agreement',
                'monthly_fee' =>
                    2500.00,

                'medical_care_terms_acknowledged' => true,
                'payment_fee_terms_acknowledged' => true,
                'resident_conduct_terms_acknowledged' => true,
                'belongings_terms_acknowledged' => true,
                'termination_terms_acknowledged' => true,
                'emergency_liability_terms_acknowledged' => true,
                'risk_liability_terms_acknowledged' => true,
                'death_event_terms_acknowledged' => true,
            ]
        );

        $consentResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission consent and agreement completed successfully.'
            )
            ->assertJsonPath(
                'consent.status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'consent.witnessed_by',
                1
            );

        $consentId =
            $consentResponse->json('consent.id');

        $this->assertNotNull($consentId);

        $this->assertDatabaseHas(
            'resident_admission_consents',
            [
                'id' => $consentId,
                'resident_admission_id' =>
                    $admissionId,
                'resident_id' =>
                    $residentId,
                'status' =>
                    'COMPLETED',
                'witnessed_by' =>
                    1,
            ]
        );

        $this->assertNotNull(
            DB::table(
                'resident_admission_consents'
            )
                ->where('id', $consentId)
                ->value('consented_at')
        );

        $this->assertNotNull(
            DB::table(
                'resident_admission_consents'
            )
                ->where('id', $consentId)
                ->value(
                    'agreement_acknowledged_at'
                )
        );

        $this->getJson(
            "/api/admissions/{$admissionId}/consent"
        )
            ->assertOk()
            ->assertJsonPath(
                'consent.id',
                $consentId
            )
            ->assertJsonPath(
                'consent.status',
                'COMPLETED'
            );

        /*
        |--------------------------------------------------------------------------
        | 10. Complete Admission
        |--------------------------------------------------------------------------
        */

        $completeAdmissionResponse =
            $this->postJson(
                "/api/admissions/{$admissionId}/complete"
            );

        $completeAdmissionResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission completed successfully.'
            )
            ->assertJsonPath(
                'admission.status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'admission.completed_by.id',
                1
            )
            ->assertJsonPath(
                'admission.completed_by.full_name',
                'F15 Admission Nurse'
            )
            ->assertJsonPath(
                'admission.resident.status',
                'Active'
            )
            ->assertJsonPath(
                'admission.resident.admission_date',
                '2026-09-20'
            );

        $this->assertDatabaseHas(
            'resident_admissions',
            [
                'id' => $admissionId,
                'resident_id' => $residentId,
                'status' => 'COMPLETED',
                'completed_by' => 1,
            ]
        );

        $this->assertNotNull(
            DB::table('resident_admissions')
                ->where('id', $admissionId)
                ->value('completed_at')
        );

        $this->assertDatabaseHas(
            'residents',
            [
                'id' => $residentId,
                'status' => 'Active',
                'admission_date' => '2026-09-20',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Completed Admission Is Protected
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/admissions/{$admissionId}",
            [
                'medical_summary' =>
                    'Attempted post-completion edit.',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Completed admissions cannot be edited through the draft admission endpoint.'
            );

        $this->postJson(
            "/api/admissions/{$admissionId}/complete"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission has already been completed.'
            );

        /*
        |--------------------------------------------------------------------------
        | 12. Admission Read Endpoints
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/admissions/{$admissionId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $admissionId
            )
            ->assertJsonPath(
                'status',
                'COMPLETED'
            );

        $this->getJson('/api/admissions')
            ->assertOk()
            ->assertJsonPath(
                'admissions.0.id',
                $admissionId
            );

        $this->getJson(
            "/api/residents/{$residentId}/admissions"
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                $residentId
            )
            ->assertJsonPath(
                'admissions.0.id',
                $admissionId
            );

        /*
        |--------------------------------------------------------------------------
        | 13. Create Discharge Draft
        |--------------------------------------------------------------------------
        */

        $dischargeResponse = $this->postJson(
            '/api/discharges',
            [
                'resident_id' =>
                    $residentId,
                'discharge_type' =>
                    'PLANNED',
                'discharge_destination' =>
                    'Family Home',
                'treatment_care_summary' =>
                    'Resident remained stable.',
                'medical_summary' =>
                    'Stable for discharge.',
                'follow_up_instructions' =>
                    'Continue routine follow-up.',
                'medication_summary' =>
                    'Medication list reviewed.',
                'medication_instructions' =>
                    'Continue as prescribed.',
                'discharged_to' =>
                    'F15 Family Representative',
                'discharged_to_relationship' =>
                    'Son',
                'discharged_to_contact' =>
                    '0123456789',
                'belongings_returned' =>
                    true,
                'belongings_notes' =>
                    'All belongings returned.',
                'administrative_notes' =>
                    'Discharge preparation started.',
            ]
        );

        $dischargeResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Discharge draft created successfully.'
            )
            ->assertJsonPath(
                'discharge.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'discharge.status',
                'DRAFT'
            )
            ->assertJsonPath(
                'discharge.prepared_by.id',
                1
            )
            ->assertJsonPath(
                'discharge.prepared_by.full_name',
                'F15 Admission Nurse'
            );

        $dischargeId =
            $dischargeResponse->json(
                'discharge.id'
            );

        $this->assertNotNull($dischargeId);

        $dischargeNumber =
            $dischargeResponse->json(
                'discharge.discharge_number'
            );

        $this->assertNotNull($dischargeNumber);
        $this->assertStringStartsWith(
            'DIS-',
            $dischargeNumber
        );

        /*
        |--------------------------------------------------------------------------
        | 14. Duplicate Draft Is Blocked
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            '/api/discharges',
            [
                'resident_id' =>
                    $residentId,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'A discharge draft already exists for this resident.'
            )
            ->assertJsonPath(
                'discharge.id',
                $dischargeId
            );

        /*
        |--------------------------------------------------------------------------
        | 15. Incomplete Discharge Cannot Complete
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/discharges/{$dischargeId}/complete"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Discharge date and time is required before completion.'
            );

        /*
        |--------------------------------------------------------------------------
        | 16. Update Required Discharge Fields
        |--------------------------------------------------------------------------
        */

        $dischargeUpdateResponse =
            $this->putJson(
                "/api/discharges/{$dischargeId}",
                [
                    'discharged_at' =>
                        '2026-09-25 14:00:00',
                    'reason_for_discharge' =>
                        'Planned return to family care.',
                    'condition_at_discharge' =>
                        'Stable',
                    'discharge_destination' =>
                        'Family Home',
                    'belongings_returned' =>
                        true,
                    'administrative_notes' =>
                        'Ready for completion.',
                ]
            );

        $dischargeUpdateResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Discharge draft updated successfully.'
            )
            ->assertJsonPath(
                'discharge.reason_for_discharge',
                'Planned return to family care.'
            )
            ->assertJsonPath(
                'discharge.condition_at_discharge',
                'Stable'
            );

        /*
        |--------------------------------------------------------------------------
        | 17. Complete Discharge
        |--------------------------------------------------------------------------
        */

        $completeDischargeResponse =
            $this->postJson(
                "/api/discharges/{$dischargeId}/complete"
            );

        $completeDischargeResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Resident discharge completed successfully.'
            )
            ->assertJsonPath(
                'discharge.status',
                'COMPLETED'
            )
            ->assertJsonPath(
                'discharge.completed_by.id',
                1
            )
            ->assertJsonPath(
                'discharge.completed_by.full_name',
                'F15 Admission Nurse'
            )
            ->assertJsonPath(
                'discharge.resident.status',
                'Discharged'
            );

        $this->assertDatabaseHas(
            'resident_discharges',
            [
                'id' => $dischargeId,
                'resident_id' => $residentId,
                'status' => 'COMPLETED',
                'prepared_by' => 1,
                'completed_by' => 1,
            ]
        );

        $this->assertNotNull(
            DB::table('resident_discharges')
                ->where('id', $dischargeId)
                ->value('completed_at')
        );

        $this->assertDatabaseHas(
            'residents',
            [
                'id' => $residentId,
                'status' => 'Discharged',
                'discharge_date' => '2026-09-25',
                'room_id' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 18. Discharge Timeline
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' =>
                    $residentId,
                'event_type' =>
                    'DISCHARGE',
                'event_title' =>
                    'Resident Discharge',
                'source_type' =>
                    'ResidentDischarge',
                'source_id' =>
                    $dischargeId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 19. Completed Discharge Is Protected
        |--------------------------------------------------------------------------
        */

        $this->putJson(
            "/api/discharges/{$dischargeId}",
            [
                'administrative_notes' =>
                    'Attempted edit after completion.',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Completed discharge records cannot be edited.'
            );

        $this->deleteJson(
            "/api/discharges/{$dischargeId}"
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Completed discharge records cannot be deleted.'
            );

        $this->postJson(
            "/api/discharges/{$dischargeId}/complete"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Discharge has already been completed.'
            );

        /*
        |--------------------------------------------------------------------------
        | 20. Discharge Read Endpoints
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/discharges/{$dischargeId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'discharge.id',
                $dischargeId
            )
            ->assertJsonPath(
                'discharge.status',
                'COMPLETED'
            );

        $this->getJson('/api/discharges')
            ->assertOk()
            ->assertJsonPath(
                'discharges.0.id',
                $dischargeId
            );

        $this->getJson(
            "/api/residents/{$residentId}/discharges"
        )
            ->assertOk()
            ->assertJsonPath(
                'resident.id',
                $residentId
            )
            ->assertJsonPath(
                'resident.status',
                'Discharged'
            )
            ->assertJsonPath(
                'discharges.0.id',
                $dischargeId
            );

        /*
        |--------------------------------------------------------------------------
        | 21. No New Discharge For Inactive Resident
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            '/api/discharges',
            [
                'resident_id' =>
                    $residentId,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Only active residents can have a new discharge prepared.'
            );

        /*
        |--------------------------------------------------------------------------
        | 22. Final Persistence
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'resident_admissions',
            1
        );

        $this->assertDatabaseCount(
            'resident_admission_consents',
            1
        );

        $this->assertDatabaseCount(
            'resident_admission_medical_histories',
            1
        );

        $this->assertDatabaseCount(
            'resident_admission_hospitalizations',
            1
        );

        $this->assertDatabaseCount(
            'resident_discharges',
            1
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