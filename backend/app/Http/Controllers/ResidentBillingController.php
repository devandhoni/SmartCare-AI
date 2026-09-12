<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\Resident;
use App\Services\BillingPaymentService;
use App\Services\MonthlyBillingInvoiceService;
use App\Services\ResidentBillingFeeService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class ResidentBillingController extends Controller
{
    public function feeStatus(
        $residentId,
        ResidentBillingFeeService $feeService
    ) {
        return response()->json(
            $feeService->resolveForResident(
                (int) $residentId
            )
        );
    }

    public function generateMonthlyInvoice(
        Request $request,
        $residentId,
        MonthlyBillingInvoiceService $invoiceService
    ) {
        $validated = $request->validate([
            'billing_month' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $invoice = $invoiceService->generateForResident(
            (int) $residentId,
            $validated['billing_month'],
            auth()->id()
        );

        return response()->json([
            'message' =>
                'Monthly billing invoice generated successfully.',
            'invoice' =>
                $invoice,
        ], 201);
    }

    public function residentInvoices(
        $residentId
    ) {
        $resident = Resident::findOrFail(
            (int) $residentId
        );

        $invoices = BillingInvoice::with([
            'items',
            'payments.receivedBy',
        ])
            ->where('resident_id', $resident->id)
            ->orderByDesc('billing_period_start')
            ->orderByDesc('id')
            ->get();

        $outstandingBalance = $invoices
            ->filter(function ($invoice) {
                return in_array(
                    $invoice->status,
                    [
                        BillingInvoice::STATUS_ISSUED,
                        BillingInvoice::STATUS_PARTIALLY_PAID,
                    ],
                    true
                );
            })
            ->sum(function ($invoice) {
                return (float) $invoice->balance_due;
            });

        return response()->json([
            'resident_id' =>
                $resident->id,

            'resident_name' =>
                $resident->full_name,

            'outstanding_balance' =>
                number_format(
                    $outstandingBalance,
                    2,
                    '.',
                    ''
                ),

            'invoices' =>
                $invoices,
        ]);
    }

    public function showInvoice(
        $invoiceId
    ) {
        $invoice = BillingInvoice::with([
            'resident',
            'admission',
            'admissionConsent',
            'items',
            'payments.receivedBy',
            'generatedBy',
        ])->findOrFail(
            (int) $invoiceId
        );

        return response()->json([
            'invoice' =>
                $invoice,
        ]);
    }

    public function recordPayment(
        Request $request,
        $invoiceId,
        BillingPaymentService $paymentService
    ) {
        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'payment_method' => [
                'required',
                'in:'
                . BillingPayment::METHOD_CASH
                . ','
                . BillingPayment::METHOD_BANK_TRANSFER
                . ','
                . BillingPayment::METHOD_CARD
                . ','
                . BillingPayment::METHOD_CHEQUE
                . ','
                . BillingPayment::METHOD_OTHER,
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $payment = $paymentService->recordPayment(
            (int) $invoiceId,
            $validated['amount'],
            $validated['payment_method'],
            $validated['payment_date'],
            auth()->id(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' =>
                'Payment recorded successfully.',

            'payment' =>
                $payment,
        ], 201);
    }

    public function paymentReceipt(
        $paymentId
    ) {
        $payment = BillingPayment::with([
            'invoice',
            'resident',
            'receivedBy',
        ])->findOrFail(
            (int) $paymentId
        );

        return response()->json([
            'receipt' => [
                'payment_id' =>
                    $payment->id,

                'payment_reference' =>
                    $payment->payment_reference,

                'invoice_id' =>
                    $payment->invoice->id,

                'invoice_number' =>
                    $payment->invoice->invoice_number,

                'resident_id' =>
                    $payment->resident_id,

                'resident_name' =>
                    $payment->resident->full_name,

                'payment_amount' =>
                    $payment->amount,

                'payment_date' =>
                    $payment->payment_date,

                'payment_method' =>
                    $payment->payment_method,

                'notes' =>
                    $payment->notes,

                'received_by' =>
                    $payment->receivedBy?->full_name,

                'invoice_total' =>
                    $payment->invoice->total_amount,

                'invoice_amount_paid' =>
                    $payment->invoice->amount_paid,

                'invoice_balance_due' =>
                    $payment->invoice->balance_due,

                'invoice_status' =>
                    $payment->invoice->status,
            ],
        ]);
    }

    public function paymentReceiptPdf(
        $paymentId
    ) {
        $payment = BillingPayment::with([
            'invoice',
            'resident',
            'receivedBy',
        ])->findOrFail(
            (int) $paymentId
        );

        $pdf = Pdf::loadView(
            'billing.payment-receipt',
            [
                'payment' => $payment,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        $filename =
            'Receipt-'
            . $payment->payment_reference
            . '.pdf';

        return $pdf->download(
            $filename
        );
    }
}