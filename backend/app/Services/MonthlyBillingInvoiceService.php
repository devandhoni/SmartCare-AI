<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Models\ResidentAdmission;
use App\Models\ResidentDischarge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyBillingInvoiceService
{
    public function __construct(
    private ResidentBillingFeeService $feeService,
    private ClinicalTimelineService $timelineService
) {
}

    public function generateForResident(
        int $residentId,
        string|Carbon $billingMonth,
        ?int $generatedBy = null
    ): BillingInvoice {
        $month = $billingMonth instanceof Carbon
            ? $billingMonth->copy()
            : Carbon::parse($billingMonth);

        $periodStartCarbon = $month
            ->copy()
            ->startOfMonth();

        $periodEndCarbon = $month
            ->copy()
            ->endOfMonth();

        $periodStart = $periodStartCarbon
            ->toDateString();

        $periodEnd = $periodEndCarbon
            ->toDateString();

        $fee = $this->feeService
            ->resolveForResident($residentId);

        if (!$fee['configured']) {
            throw ValidationException::withMessages([
                'billing' =>
                    'Monthly invoice cannot be generated because the resident billing fee is not configured. Reason: '
                    . $fee['reason'],
            ]);
        }

        $admission = ResidentAdmission::query()
            ->where('id', $fee['resident_admission_id'])
            ->where('resident_id', $residentId)
            ->where('status', 'COMPLETED')
            ->first();

        if (!$admission || !$admission->admitted_at) {
            throw ValidationException::withMessages([
                'billing' =>
                    'Monthly invoice cannot be generated because the completed admission date is unavailable.',
            ]);
        }

        $admissionMonth = Carbon::parse(
            $admission->admitted_at
        )->startOfMonth();

        if ($periodStartCarbon->lt($admissionMonth)) {
            throw ValidationException::withMessages([
                'billing' =>
                    'Monthly invoice cannot be generated for a billing period before the resident admission month.',
            ]);
        }

        $completedDischarge = ResidentDischarge::query()
            ->where('resident_id', $residentId)
            ->where('status', 'COMPLETED')
            ->whereNotNull('discharged_at')
            ->where(
                'discharged_at',
                '>=',
                Carbon::parse($admission->admitted_at)
            )
            ->orderByDesc('discharged_at')
            ->orderByDesc('id')
            ->first();

        if ($completedDischarge) {
            $dischargeMonth = Carbon::parse(
                $completedDischarge->discharged_at
            )->startOfMonth();

            if ($periodStartCarbon->gt($dischargeMonth)) {
                throw ValidationException::withMessages([
                    'billing' =>
                        'Monthly invoice cannot be generated for a billing period after the resident discharge month.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $residentId,
            $periodStart,
            $periodEnd,
            $fee,
            $generatedBy
        ) {
            $existing = BillingInvoice::query()
                ->where('resident_id', $residentId)
                ->where('billing_period_start', $periodStart)
                ->where('billing_period_end', $periodEnd)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'billing' =>
                        'A billing invoice already exists for this resident and billing period.',
                ]);
            }

            $monthlyFee = $fee['monthly_fee'];

            $invoice = BillingInvoice::create([
                'resident_id' =>
                    $residentId,

                'resident_admission_id' =>
                    $fee['resident_admission_id'],

                'admission_consent_id' =>
                    $fee['admission_consent_id'],

                'invoice_number' =>
                    $this->generateInvoiceNumber(
                        $residentId,
                        $periodStart
                    ),

                'billing_period_start' =>
                    $periodStart,

                'billing_period_end' =>
                    $periodEnd,

                'issue_date' =>
                    now()->toDateString(),

                'due_date' =>
                    Carbon::parse($periodStart)
                        ->endOfMonth()
                        ->toDateString(),

                'subtotal' =>
                    $monthlyFee,

                'discount_amount' =>
                    '0.00',

                'adjustment_amount' =>
                    '0.00',

                'total_amount' =>
                    $monthlyFee,

                'amount_paid' =>
                    '0.00',

                'balance_due' =>
                    $monthlyFee,

                'status' =>
                    BillingInvoice::STATUS_ISSUED,

                'notes' =>
                    null,

                'generated_by' =>
                    $generatedBy,
            ]);

            BillingInvoiceItem::create([
                'billing_invoice_id' =>
                    $invoice->id,

                'item_type' =>
                    BillingInvoiceItem::TYPE_MONTHLY_FEE,

                'description' =>
                    'Monthly residential care fee for '
                    . Carbon::parse($periodStart)
                        ->format('F Y'),

                'quantity' =>
                    '1.00',

                'unit_amount' =>
                    $monthlyFee,

                'line_total' =>
                    $monthlyFee,
            ]);


            $this->timelineService
            ->recordBillingInvoiceGenerated(
                $residentId,
                $invoice->invoice_number,
                Carbon::parse($periodStart)->format('F Y'),
                $invoice->id
            );

            return $invoice->load([
                'items',
                'resident',
                'admissionConsent',
            ]);
        });
    }

    private function generateInvoiceNumber(
        int $residentId,
        string $periodStart
    ): string {
        $period = Carbon::parse($periodStart)
            ->format('Ym');

        return sprintf(
            'INV-%s-R%06d',
            $period,
            $residentId
        );
    }
}
