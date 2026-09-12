<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Payment Receipt - {{ $payment->payment_reference }}
    </title>

    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
        }

        .subtitle {
            margin-top: 4px;
            color: #64748b;
        }

        .receipt-title {
            margin-top: 18px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reference-box {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            text-align: center;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 7px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .label {
            width: 38%;
            color: #64748b;
        }

        .value {
            font-weight: bold;
            color: #0f172a;
        }

        .amount-box {
            margin-top: 24px;
            padding: 16px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            text-align: center;
        }

        .amount-label {
            color: #475569;
            font-size: 12px;
        }

        .amount-value {
            margin-top: 5px;
            font-size: 24px;
            font-weight: bold;
            color: #1d4ed8;
        }

        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">
            SmartCare-AI
        </div>

        <div class="subtitle">
            Care Management System
        </div>

        <div class="receipt-title">
            Payment Receipt
        </div>

        <div class="reference-box">
            Receipt No:
            <strong>
                {{ $payment->payment_reference }}
            </strong>
        </div>
    </div>

    <div class="section">
        <div class="section-title">
            Payment Information
        </div>

        <table>
            <tr>
                <td class="label">
                    Resident
                </td>

                <td class="value">
                    {{ $payment->resident->full_name }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Invoice Number
                </td>

                <td class="value">
                    {{ $payment->invoice->invoice_number }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Payment Date
                </td>

                <td class="value">
                    {{ $payment->payment_date->format('d/m/Y') }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Payment Method
                </td>

                <td class="value">
                    {{ str_replace('_', ' ', $payment->payment_method) }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Received By
                </td>

                <td class="value">
                    {{ $payment->receivedBy?->full_name ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="amount-label">
            Amount Received
        </div>

        <div class="amount-value">
            RM {{ number_format((float) $payment->amount, 2) }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">
            Invoice Summary
        </div>

        <table>
            <tr>
                <td class="label">
                    Invoice Total
                </td>

                <td class="value">
                    RM {{ number_format((float) $payment->invoice->total_amount, 2) }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Total Paid
                </td>

                <td class="value">
                    RM {{ number_format((float) $payment->invoice->amount_paid, 2) }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Remaining Balance
                </td>

                <td class="value">
                    RM {{ number_format((float) $payment->invoice->balance_due, 2) }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Invoice Status
                </td>

                <td class="value">
                    {{ str_replace('_', ' ', $payment->invoice->status) }}
                </td>
            </tr>
        </table>
    </div>

    @if($payment->notes)
        <div class="section">
            <div class="section-title">
                Notes
            </div>

            <div>
                {{ $payment->notes }}
            </div>
        </div>
    @endif

    <div class="footer">
        Computer-generated payment receipt.
        No signature is required.

        <br>

        Generated:
        {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>