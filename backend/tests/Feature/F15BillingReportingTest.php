<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class F15BillingReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_and_management_reporting_workflow(): void
    {
        $this->assertTestingDatabaseIsolation();

        /*
        |--------------------------------------------------------------------------
        | 1. Administrator Authentication
        |--------------------------------------------------------------------------
        */

        DB::table('roles')->insert([
            [
                'id' => 1,
                'role_name' => 'Administrator',
            ],
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'role_id' => 1,
            'full_name' => 'F15 Billing Administrator',
            'email' => 'f15.billing.admin@example.test',
            'password' => bcrypt('F15BillingAdmin!'),
            'status' => 'Active',
        ]);

        $administrator = User::findOrFail(1);

        Sanctum::actingAs($administrator);

        /*
        |--------------------------------------------------------------------------
        | 2. Resident
        |--------------------------------------------------------------------------
        */

        $residentResponse = $this->postJson(
            '/api/residents',
            [
                'full_name' => 'F15 Billing Resident',
                'gender' => 'Male',
                'date_of_birth' => '1950-01-15',
            ]
        );

        $residentResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Resident registered successfully'
            );

        $residentId = $residentResponse->json('resident.id');

        $this->assertNotNull($residentId);

        /*
        |--------------------------------------------------------------------------
        | 3. Fee Is Not Configured Before Completed Admission
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/residents/{$residentId}/billing/fee-status"
        )
            ->assertOk()
            ->assertJsonPath('configured', false)
            ->assertJsonPath(
                'reason',
                'NO_COMPLETED_ADMISSION'
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Formal Admission
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
            ]
        );

        $admissionResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Admission created successfully.'
            );

        $admissionId =
            $admissionResponse->json('admission.id');

        $this->assertNotNull($admissionId);

        /*
        |--------------------------------------------------------------------------
        | 5. Completed Enhanced Agreement
        |--------------------------------------------------------------------------
        */

        $consentResponse = $this->postJson(
            "/api/admissions/{$admissionId}/consent",
            [
                'consent_given_by' =>
                    'F15 Billing Family Representative',

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
                    'All billing and admission terms accepted.',

                'agreement_version' =>
                    'F15-BILLING-V1',

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
            );

        $consentId =
            $consentResponse->json('consent.id');

        $this->assertNotNull($consentId);

        /*
        |--------------------------------------------------------------------------
        | 6. Complete Admission
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/admissions/{$admissionId}/complete"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Admission completed successfully.'
            )
            ->assertJsonPath(
                'admission.status',
                'COMPLETED'
            );

        /*
        |--------------------------------------------------------------------------
        | 7. Billing Fee Resolution
        |--------------------------------------------------------------------------
        */

        $feeResponse = $this->getJson(
            "/api/residents/{$residentId}/billing/fee-status"
        );

        $feeResponse
            ->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath(
                'resident_id',
                $residentId
            )
            ->assertJsonPath(
                'resident_admission_id',
                $admissionId
            )
            ->assertJsonPath(
                'admission_consent_id',
                $consentId
            )
            ->assertJsonPath(
                'agreement_version',
                'F15-BILLING-V1'
            )
            ->assertJsonPath(
                'reason',
                null
            );

        $this->assertEquals(
            2500.00,
            (float) $feeResponse->json('monthly_fee')
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Invoice Before Admission Month Is Rejected
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/residents/{$residentId}/billing/invoices/monthly",
            [
                'billing_month' =>
                    '2026-08-01',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing');

        $this->assertDatabaseCount(
            'billing_invoices',
            0
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Generate September Invoice
        |--------------------------------------------------------------------------
        */

        $invoiceResponse = $this->postJson(
            "/api/residents/{$residentId}/billing/invoices/monthly",
            [
                'billing_month' =>
                    '2026-09-01',
            ]
        );

        $invoiceResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Monthly billing invoice generated successfully.'
            )
            ->assertJsonPath(
                'invoice.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'invoice.status',
                'ISSUED'
            );

        $invoiceId =
            $invoiceResponse->json('invoice.id');

        $invoiceNumber =
            $invoiceResponse->json('invoice.invoice_number');

        $this->assertNotNull($invoiceId);

        $this->assertSame(
            sprintf(
                'INV-202609-R%06d',
                $residentId
            ),
            $invoiceNumber
        );

        $this->assertEquals(
            2500.00,
            (float) $invoiceResponse->json(
                'invoice.total_amount'
            )
        );

        $this->assertEquals(
            2500.00,
            (float) $invoiceResponse->json(
                'invoice.balance_due'
            )
        );

        $this->assertDatabaseHas(
            'billing_invoices',
            [
                'id' => $invoiceId,
                'resident_id' => $residentId,
                'resident_admission_id' =>
                    $admissionId,
                'admission_consent_id' =>
                    $consentId,
                'invoice_number' =>
                    $invoiceNumber,
                'billing_period_start' =>
                '2026-09-01 00:00:00',
                'billing_period_end' =>
                '2026-09-30 00:00:00',
                'status' =>
                    'ISSUED',
                'generated_by' =>
                    1,
            ]
        );

        $this->assertDatabaseHas(
            'billing_invoice_items',
            [
                'billing_invoice_id' =>
                    $invoiceId,
                'item_type' =>
                    'MONTHLY_FEE',
            ]
        );

        $this->assertDatabaseCount(
            'billing_invoice_items',
            1
        );

        /*
        |--------------------------------------------------------------------------
        | 10. Duplicate Monthly Invoice Is Rejected
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/residents/{$residentId}/billing/invoices/monthly",
            [
                'billing_month' =>
                    '2026-09-01',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing');

        $this->assertDatabaseCount(
            'billing_invoices',
            1
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Invoice Read Endpoints
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/residents/{$residentId}/billing/invoices"
        )
            ->assertOk()
            ->assertJsonPath(
                'resident_id',
                $residentId
            )
            ->assertJsonPath(
                'invoices.0.id',
                $invoiceId
            );

        $this->getJson(
            "/api/billing/invoices/{$invoiceId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'invoice.id',
                $invoiceId
            )
            ->assertJsonPath(
                'invoice.invoice_number',
                $invoiceNumber
            )
            ->assertJsonPath(
                'invoice.status',
                'ISSUED'
            );

        /*
        |--------------------------------------------------------------------------
        | 12. Overpayment Is Rejected
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/billing/invoices/{$invoiceId}/payments",
            [
                'amount' =>
                    3000.00,
                'payment_date' =>
                    '2026-09-25',
                'payment_method' =>
                    'CASH',
                'notes' =>
                    'Invalid overpayment acceptance test.',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount(
            'billing_payments',
            0
        );

        /*
        |--------------------------------------------------------------------------
        | 13. Partial Payment
        |--------------------------------------------------------------------------
        */

        $partialPaymentResponse = $this->postJson(
            "/api/billing/invoices/{$invoiceId}/payments",
            [
                'amount' =>
                    1000.00,
                'payment_date' =>
                    '2026-09-25',
                'payment_method' =>
                    'BANK_TRANSFER',
                'notes' =>
                    'F15 partial payment.',
            ]
        );

        $partialPaymentResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Payment recorded successfully.'
            );

        $partialPaymentId =
            $partialPaymentResponse->json('payment.id');

        $this->assertNotNull(
            $partialPaymentId
        );

        $this->assertDatabaseHas(
            'billing_payments',
            [
                'id' =>
                    $partialPaymentId,
                'billing_invoice_id' =>
                    $invoiceId,
                'resident_id' =>
                    $residentId,
                'payment_method' =>
                    'BANK_TRANSFER',
                'received_by' =>
                    1,
            ]
        );

        $this->assertDatabaseHas(
            'billing_invoices',
            [
                'id' =>
                    $invoiceId,
                'status' =>
                    'PARTIALLY_PAID',
                'amount_paid' =>
                    1000.00,
                'balance_due' =>
                    1500.00,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 14. Partial Payment Receipt
        |--------------------------------------------------------------------------
        */

        $receiptResponse = $this->getJson(
            "/api/billing/payments/{$partialPaymentId}/receipt"
        );

        $receiptResponse
            ->assertOk()
            ->assertJsonPath(
                'receipt.payment_id',
                $partialPaymentId
            )
            ->assertJsonPath(
                'receipt.invoice_id',
                $invoiceId
            )
            ->assertJsonPath(
                'receipt.invoice_number',
                $invoiceNumber
            )
            ->assertJsonPath(
                'receipt.resident_id',
                $residentId
            )
            ->assertJsonPath(
                'receipt.resident_name',
                'F15 Billing Resident'
            )
            ->assertJsonPath(
                'receipt.payment_method',
                'BANK_TRANSFER'
            )
            ->assertJsonPath(
                'receipt.received_by',
                'F15 Billing Administrator'
            )
            ->assertJsonPath(
                'receipt.invoice_status',
                'PARTIALLY_PAID'
            );

        $this->assertEquals(
            1000.00,
            (float) $receiptResponse->json(
                'receipt.payment_amount'
            )
        );

        $this->assertEquals(
            1500.00,
            (float) $receiptResponse->json(
                'receipt.invoice_balance_due'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 15. Final Payment
        |--------------------------------------------------------------------------
        */

        $finalPaymentResponse = $this->postJson(
            "/api/billing/invoices/{$invoiceId}/payments",
            [
                'amount' =>
                    1500.00,
                'payment_date' =>
                    '2026-09-25',
                'payment_method' =>
                    'CASH',
                'notes' =>
                    'F15 final payment.',
            ]
        );

        $finalPaymentResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Payment recorded successfully.'
            );

        $finalPaymentId =
            $finalPaymentResponse->json('payment.id');

        $this->assertNotNull(
            $finalPaymentId
        );

        $this->assertDatabaseHas(
            'billing_invoices',
            [
                'id' =>
                    $invoiceId,
                'status' =>
                    'PAID',
                'amount_paid' =>
                    2500.00,
                'balance_due' =>
                    0.00,
            ]
        );

        $this->assertDatabaseCount(
            'billing_payments',
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 16. Further Payment Is Rejected
        |--------------------------------------------------------------------------
        */

        $this->postJson(
            "/api/billing/invoices/{$invoiceId}/payments",
            [
                'amount' =>
                    1.00,
                'payment_date' =>
                    '2026-09-25',
                'payment_method' =>
                    'CASH',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing');

        $this->assertDatabaseCount(
            'billing_payments',
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 17. Resident Billing Summary
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            "/api/residents/{$residentId}/billing/invoices"
        )
            ->assertOk()
            ->assertJsonPath(
                'outstanding_balance',
                '0.00'
            )
            ->assertJsonPath(
                'invoices.0.id',
                $invoiceId
            )
            ->assertJsonPath(
                'invoices.0.status',
                'PAID'
            );

        /*
        |--------------------------------------------------------------------------
        | 18. Billing Timeline Evidence
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' =>
                    $residentId,
                'source_type' =>
                    'BillingInvoiceGenerated',
                'source_id' =>
                    $invoiceId,
            ]
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' =>
                    $residentId,
                'source_type' =>
                    'BillingPaymentRecorded',
                'source_id' =>
                    $partialPaymentId,
            ]
        );

        $this->assertDatabaseHas(
            'clinical_timelines',
            [
                'resident_id' =>
                    $residentId,
                'source_type' =>
                    'BillingPaymentRecorded',
                'source_id' =>
                    $finalPaymentId,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 19. Management Reports
        |--------------------------------------------------------------------------
        */

        $reportRoutes = [
            '/api/reports/overview',
            '/api/reports/resident-census',
            '/api/reports/care-operations',
            '/api/reports/medication-operations',
            '/api/reports/clinical-monitoring',
            '/api/reports/inventory-operations',
            '/api/reports/billing-operations',
            '/api/reports/family-facility',
        ];

        foreach ($reportRoutes as $route) {
            $this->getJson(
                $route
                . '?from=2026-09-01&to=2026-09-30'
            )
                ->assertOk()
                ->assertJsonPath(
                    'success',
                    true
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 20. Billing Report Contains Valid Response
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/reports/billing-operations'
            . '?from=2026-09-01&to=2026-09-30'
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Billing report loaded successfully.'
            )
            ->assertJsonStructure([
                'data',
            ]);

        /*
        |--------------------------------------------------------------------------
        | 21. Report Date Validation
        |--------------------------------------------------------------------------
        */

        $this->getJson(
            '/api/reports/overview'
            . '?from=2026-09-30&to=2026-09-01'
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');

        $this->getJson(
            '/api/reports/overview'
            . '?from=2025-01-01&to=2026-09-30'
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');

        /*
        |--------------------------------------------------------------------------
        | 22. Final Persistence
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'billing_invoices',
            1
        );

        $this->assertDatabaseCount(
            'billing_invoice_items',
            1
        );

        $this->assertDatabaseCount(
            'billing_payments',
            2
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