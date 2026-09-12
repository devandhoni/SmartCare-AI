<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingPaymentService
{
    public function __construct(
        private ClinicalTimelineService $timelineService
    ) {
    }

    public function recordPayment(
        int $invoiceId,
        string|float $amount,
        string $paymentMethod,
        string|Carbon $paymentDate,
        ?int $receivedBy = null,
        ?string $notes = null
    ): BillingPayment {
        $paymentAmount = round(
            (float) $amount,
            2
        );

        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' =>
                    'Payment amount must be greater than zero.',
            ]);
        }

        $allowedMethods = [
            BillingPayment::METHOD_CASH,
            BillingPayment::METHOD_BANK_TRANSFER,
            BillingPayment::METHOD_CARD,
            BillingPayment::METHOD_CHEQUE,
            BillingPayment::METHOD_OTHER,
        ];

        if (!in_array(
            $paymentMethod,
            $allowedMethods,
            true
        )) {
            throw ValidationException::withMessages([
                'payment_method' =>
                    'Invalid payment method.',
            ]);
        }

        $date = $paymentDate instanceof Carbon
            ? $paymentDate->copy()
            : Carbon::parse($paymentDate);

        return DB::transaction(function () use (
            $invoiceId,
            $paymentAmount,
            $paymentMethod,
            $date,
            $receivedBy,
            $notes
        ) {
            $invoice = BillingInvoice::query()
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $invoice->status ===
                BillingInvoice::STATUS_VOID
            ) {
                throw ValidationException::withMessages([
                    'billing' =>
                        'Payment cannot be recorded against a void invoice.',
                ]);
            }

            if (
                $invoice->status ===
                    BillingInvoice::STATUS_PAID
                || (float) $invoice->balance_due <= 0
            ) {
                throw ValidationException::withMessages([
                    'billing' =>
                        'This invoice has already been paid in full.',
                ]);
            }

            if (!in_array(
                $invoice->status,
                [
                    BillingInvoice::STATUS_ISSUED,
                    BillingInvoice::STATUS_PARTIALLY_PAID,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'billing' =>
                        'Payments can only be recorded against an issued invoice.',
                ]);
            }

            $currentBalance = round(
                (float) $invoice->balance_due,
                2
            );

            if (
                $paymentAmount >
                $currentBalance
            ) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment amount cannot exceed the outstanding balance.',
                ]);
            }

            $newAmountPaid = round(
                (float) $invoice->amount_paid
                + $paymentAmount,
                2
            );

            $newBalance = round(
                (float) $invoice->total_amount
                - $newAmountPaid,
                2
            );

            if ($newBalance < 0) {
                $newBalance = 0.00;
            }

            $payment = BillingPayment::create([
                'billing_invoice_id' =>
                    $invoice->id,

                'resident_id' =>
                    $invoice->resident_id,

                'payment_reference' =>
                    $this->generatePaymentReference(
                        $invoice->id
                    ),

                'amount' =>
                    number_format(
                        $paymentAmount,
                        2,
                        '.',
                        ''
                    ),

                'payment_date' =>
                    $date->toDateString(),

                'payment_method' =>
                    $paymentMethod,

                'notes' =>
                    $notes,

                'received_by' =>
                    $receivedBy,
            ]);

            $invoice->amount_paid =
                number_format(
                    $newAmountPaid,
                    2,
                    '.',
                    ''
                );

            $invoice->balance_due =
                number_format(
                    $newBalance,
                    2,
                    '.',
                    ''
                );

            $invoice->status =
                $newBalance <= 0
                    ? BillingInvoice::STATUS_PAID
                    : BillingInvoice::STATUS_PARTIALLY_PAID;

            $invoice->save();

            /*
            |--------------------------------------------------------------------------
            | F9.9 Billing Audit Evidence
            |--------------------------------------------------------------------------
            |
            | Record only high-level operational evidence in the resident timeline.
            | Financial amounts, payment method, balance and notes remain inside
            | the Billing module and receipt evidence.
            |
            */

            $this->timelineService
                ->recordBillingPaymentRecorded(
                    $invoice->resident_id,
                    $payment->payment_reference,
                    $invoice->invoice_number,
                    $payment->id
                );

            return $payment->load([
                'invoice',
                'resident',
                'receivedBy',
            ]);
        });
    }

    private function generatePaymentReference(
        int $invoiceId
    ): string {
        do {
            $reference = sprintf(
                'PAY-%s-I%06d-%s',
                now()->format('YmdHis'),
                $invoiceId,
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(3)
                        ),
                        0,
                        6
                    )
                )
            );
        } while (
            BillingPayment::where(
                'payment_reference',
                $reference
            )->exists()
        );

        return $reference;
    }
}