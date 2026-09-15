<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalReportExportService
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_PDF = 'pdf';

    private const REPORT_TITLES = [
        'resident-census' => 'Resident Census Report',
        'care-operations' => 'Care Operations Report',
        'medication-operations' => 'Medication Operations Report',
        'clinical-monitoring' => 'Clinical Monitoring Report',
        'inventory-operations' => 'Inventory Operations Report',
        'billing-operations' => 'Billing Operations Report',
        'family-facility' => 'Family Communication & Facility Activity Report',
    ];

    public function __construct(
        private readonly OperationalReportService $reportService
    ) {
    }

    public function export(
        string $report,
        string $format,
        Carbon $from,
        Carbon $to
    ): Response|StreamedResponse {
        $data = $this->reportData(
            $report,
            $from,
            $to
        );

        return match ($format) {
            self::FORMAT_CSV =>
                $this->csv(
                    $report,
                    $data,
                    $from,
                    $to
                ),

            self::FORMAT_PDF =>
                $this->pdf(
                    $report,
                    $data,
                    $from,
                    $to
                ),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported export format.'
                ),
        };
    }

    private function reportData(
        string $report,
        Carbon $from,
        Carbon $to
    ): array {
        return match ($report) {
            'resident-census' =>
                $this->reportService->residentCensus(
                    $from,
                    $to
                ),

            'care-operations' =>
                $this->reportService->careOperations(
                    $from,
                    $to
                ),

            'medication-operations' =>
                $this->reportService->medicationOperations(
                    $from,
                    $to
                ),

            'clinical-monitoring' =>
                $this->reportService->clinicalMonitoring(
                    $from,
                    $to
                ),

            'inventory-operations' =>
                $this->reportService->inventoryOperations(
                    $from,
                    $to
                ),

            'billing-operations' =>
                $this->reportService->billingOperations(
                    $from,
                    $to
                ),

            'family-facility' =>
                $this->reportService->familyFacilityOperations(
                    $from,
                    $to
                ),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported report type.'
                ),
        };
    }

    private function csv(
        string $report,
        array $data,
        Carbon $from,
        Carbon $to
    ): StreamedResponse {
        $filename = $this->filename(
            $report,
            $from,
            $to,
            'csv'
        );

        return response()->streamDownload(
            function () use ($report, $data) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                /*
                |--------------------------------------------------------------------------
                | UTF-8 BOM
                |--------------------------------------------------------------------------
                |
                | Helps Excel open UTF-8 CSV files correctly.
                |
                */

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        self::REPORT_TITLES[$report],
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'Period',
                        $data['period']['from']
                            . ' to '
                            . $data['period']['to'],
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'Days',
                        $data['period']['days'],
                    ]
                );

                fputcsv(
                    $handle,
                    []
                );

                foreach ($this->csvSections(
                    $report,
                    $data
                ) as $section) {
                    fputcsv(
                        $handle,
                        [
                            $section['title'],
                        ]
                    );

                    fputcsv(
                        $handle,
                        $section['headers']
                    );

                    foreach ($section['rows'] as $row) {
                        fputcsv(
                            $handle,
                            $row
                        );
                    }

                    fputcsv(
                        $handle,
                        []
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    private function pdf(
        string $report,
        array $data,
        Carbon $from,
        Carbon $to
    ): Response {
        $title =
            self::REPORT_TITLES[$report];

        $sections =
            $this->pdfSections(
                $report,
                $data
            );

        $pdf = Pdf::loadView(
            'reports.operational-report',
            [
                'title' => $title,
                'reportType' => $report,
                'period' => $data['period'],
                'sections' => $sections,
                'generatedAt' => now(),
            ]
        );

        $pdf->setPaper(
            'a4',
            'landscape'
        );

        return $pdf->download(
            $this->filename(
                $report,
                $from,
                $to,
                'pdf'
            )
        );
    }

    private function csvSections(
        string $report,
        array $data
    ): array {
        return $this->sections(
            $report,
            $data
        );
    }

    private function pdfSections(
        string $report,
        array $data
    ): array {
        return $this->sections(
            $report,
            $data
        );
    }

    private function sections(
        string $report,
        array $data
    ): array {
        return match ($report) {
            'resident-census' =>
                $this->residentCensusSections(
                    $data
                ),

            'care-operations' =>
                $this->careOperationsSections(
                    $data
                ),

            'medication-operations' =>
                $this->medicationSections(
                    $data
                ),

            'clinical-monitoring' =>
                $this->clinicalMonitoringSections(
                    $data
                ),

            'inventory-operations' =>
                $this->inventorySections(
                    $data
                ),

            'billing-operations' =>
                $this->billingSections(
                    $data
                ),

            'family-facility' =>
                $this->familyFacilitySections(
                    $data
                ),

            default =>
                [],
        };
    }

    private function residentCensusSections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Summary',
                $data['summary']
            ),

            $this->tableSection(
                'Residents',
                [
                    'Resident ID',
                    'Resident',
                    'Admission Date',
                    'Discharge Date',
                    'Status at Period End',
                ],
                collect($data['residents'])
                    ->map(
                        fn ($row) => [
                            $row['resident_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['admission_date'] ?? '',
                            $row['discharge_date'] ?? '',
                            $row['status_at_period_end'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Admissions',
                [
                    'Admission ID',
                    'Resident',
                    'Admitted At',
                ],
                collect($data['admissions'])
                    ->map(
                        fn ($row) => [
                            $row['admission_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['admitted_at'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Discharges',
                [
                    'Discharge ID',
                    'Resident',
                    'Discharged At',
                ],
                collect($data['discharges'])
                    ->map(
                        fn ($row) => [
                            $row['discharge_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['discharged_at'] ?? '',
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function careOperationsSections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Summary',
                $data['summary']
            ),

            $this->summarySection(
                'Source Summary',
                $data['source_summary']
            ),

            $this->tableSection(
                'Scheduled Tasks',
                [
                    'Task ID',
                    'Resident',
                    'Title',
                    'Status',
                    'Source',
                    'Scheduled Time',
                    'Completed Time',
                ],
                collect($data['scheduled_tasks'])
                    ->map(
                        fn ($row) => [
                            $row['task_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['title'] ?? '',
                            $row['status'] ?? '',
                            $row['source_type'] ?? '',
                            $row['scheduled_time'] ?? '',
                            $row['completed_time'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Care Records',
                [
                    'Care Record ID',
                    'Resident',
                    'Care Type',
                    'Title',
                    'Recorded At',
                ],
                collect($data['care_records'])
                    ->map(
                        fn ($row) => [
                            $row['care_record_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['care_type'] ?? '',
                            $row['title'] ?? '',
                            $row['recorded_at'] ?? '',
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function medicationSections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Summary',
                $data['summary']
            ),

            $this->summarySection(
                'Medication Slots',
                $data['slots']
            ),

            $this->summarySection(
                'Meal Confirmation',
                $data['meals']
            ),

            $this->tableSection(
                'Medication Administration Records',
                [
                    'Record ID',
                    'Resident',
                    'Medication',
                    'Date',
                    'Slot',
                    'Status',
                    'Meal',
                    'Meal Confirmed',
                ],
                collect($data['records'])
                    ->map(
                        fn ($row) => [
                            $row['administration_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['medication_name'] ?? '',
                            $row['administered_date'] ?? '',
                            $row['time_slot'] ?? '',
                            $row['status'] ?? '',
                            $row['meal_type'] ?? '',
                            $this->yesNo(
                                $row['meal_confirmed']
                                    ?? false
                            ),
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function clinicalMonitoringSections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Summary',
                $data['summary']
            ),

            $this->summarySection(
                'Weekly Measurement Coverage',
                $data['weekly_measurement_coverage']
            ),

            $this->summarySection(
                'Glucose Measurement Types',
                $data['glucose_measurement_types']
            ),

            $this->tableSection(
                'Weekly Vital Signs',
                [
                    'Vital ID',
                    'Resident',
                    'Recorded At',
                    'BP',
                    'Heart Rate',
                    'Oxygen',
                    'Temperature',
                    'Weight',
                    'Glucose',
                ],
                collect($data['weekly_vitals'])
                    ->map(
                        fn ($row) => [
                            $row['vital_sign_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['recorded_at'] ?? '',
                            $this->bloodPressure(
                                $row
                            ),
                            $row['heart_rate'] ?? '',
                            $row['oxygen_level'] ?? '',
                            $row['temperature'] ?? '',
                            $row['weight'] ?? '',
                            $row['blood_glucose'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Monthly Glucose Checks',
                [
                    'Vital ID',
                    'Resident',
                    'Recorded At',
                    'Glucose',
                    'Type',
                    'Notes',
                ],
                collect($data['monthly_glucose'])
                    ->map(
                        fn ($row) => [
                            $row['vital_sign_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['recorded_at'] ?? '',
                            $row['blood_glucose'] ?? '',
                            $row['measurement_type'] ?? '',
                            $row['notes'] ?? '',
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function inventorySections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Movement Summary',
                $data['movement_summary']
            ),

            $this->summarySection(
                'Current Inventory Summary',
                $data['current_inventory_summary']
            ),

            $this->tableSection(
                'Inventory Transactions',
                [
                    'Transaction ID',
                    'Medication',
                    'Resident',
                    'Type',
                    'Quantity',
                    'Reference',
                    'Performed By',
                    'Transaction Date',
                ],
                collect($data['transactions'])
                    ->map(
                        fn ($row) => [
                            $row['transaction_id'] ?? '',
                            $row['medication_name'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['transaction_type'] ?? '',
                            $row['quantity'] ?? '',
                            $row['reference'] ?? '',
                            $row['performed_by'] ?? '',
                            $row['transaction_date'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Current Inventory Snapshot',
                [
                    'Inventory ID',
                    'Medication',
                    'Quantity',
                    'Minimum',
                    'Expiry',
                    'Location',
                    'Status',
                    'Needs Attention',
                ],
                collect($data['current_inventory'])
                    ->map(
                        fn ($row) => [
                            $row['inventory_id'] ?? '',
                            $row['medication_name'] ?? '',
                            $row['quantity'] ?? '',
                            $row['minimum_stock'] ?? '',
                            $row['expiry_date'] ?? '',
                            $row['location'] ?? '',
                            $row['stock_status'] ?? '',
                            $this->yesNo(
                                $row['needs_attention']
                                    ?? false
                            ),
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function billingSections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Invoice Summary',
                $data['invoice_summary']
            ),

            $this->summarySection(
                'Payment Summary',
                [
                    'payments_received' =>
                        $data['payment_summary']['payments_received'],

                    'amount_received' =>
                        $data['payment_summary']['amount_received'],

                    ...collect(
                        $data['payment_summary']['methods']
                    )
                        ->mapWithKeys(
                            fn ($value, $key) => [
                                'method_' . strtolower($key) =>
                                    $value,
                            ]
                        )
                        ->all(),
                ]
            ),

            $this->tableSection(
                'Invoices',
                [
                    'Invoice',
                    'Resident',
                    'Period',
                    'Issue Date',
                    'Due Date',
                    'Total',
                    'Paid',
                    'Balance',
                    'Status',
                    'Overdue',
                ],
                collect($data['invoices'])
                    ->map(
                        fn ($row) => [
                            $row['invoice_number'] ?? '',
                            $row['resident_name'] ?? '',
                            ($row['billing_period_start'] ?? '')
                                . ' to '
                                . ($row['billing_period_end'] ?? ''),
                            $row['issue_date'] ?? '',
                            $row['due_date'] ?? '',
                            $row['total_amount'] ?? '',
                            $row['amount_paid'] ?? '',
                            $row['balance_due'] ?? '',
                            $row['status'] ?? '',
                            $this->yesNo(
                                $row['is_overdue']
                                    ?? false
                            ),
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Payments Received During Period',
                [
                    'Payment Reference',
                    'Invoice',
                    'Resident',
                    'Amount',
                    'Payment Date',
                    'Method',
                    'Received By',
                ],
                collect($data['payments'])
                    ->map(
                        fn ($row) => [
                            $row['payment_reference'] ?? '',
                            $row['invoice_number'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['amount'] ?? '',
                            $row['payment_date'] ?? '',
                            $row['payment_method'] ?? '',
                            $row['received_by'] ?? '',
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function familyFacilitySections(
        array $data
    ): array {
        return [
            $this->summarySection(
                'Family Communication Summary',
                $data['family_communication_summary']
            ),

            $this->summarySection(
                'Facility Activity Modules',
                collect(
                    $data['facility_activity_summary']['modules']
                )->all()
            ),

            $this->summarySection(
                'Facility Activity Actions',
                collect(
                    $data['facility_activity_summary']['actions']
                )->all()
            ),

            $this->tableSection(
                'Family Messages',
                [
                    'Message ID',
                    'Resident',
                    'Recipient',
                    'Channel',
                    'Message Type',
                    'Status',
                    'Provider',
                    'Attempts',
                    'Created At',
                    'Sent At',
                ],
                collect($data['family_messages'])
                    ->map(
                        fn ($row) => [
                            $row['family_message_log_id'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['recipient_name'] ?? '',
                            $row['channel'] ?? '',
                            $row['message_type'] ?? '',
                            $row['status'] ?? '',
                            $row['provider'] ?? '',
                            $row['attempt_count'] ?? '',
                            $row['created_at'] ?? '',
                            $row['sent_at'] ?? '',
                        ]
                    )
                    ->all()
            ),

            $this->tableSection(
                'Facility Activity',
                [
                    'Activity ID',
                    'User',
                    'Resident',
                    'Module',
                    'Action',
                    'Description',
                    'Created On',
                ],
                collect($data['facility_activities'])
                    ->map(
                        fn ($row) => [
                            $row['activity_log_id'] ?? '',
                            $row['user_name'] ?? '',
                            $row['resident_name'] ?? '',
                            $row['module'] ?? '',
                            $row['action'] ?? '',
                            $row['description'] ?? '',
                            $row['created_on'] ?? '',
                        ]
                    )
                    ->all()
            ),
        ];
    }

    private function summarySection(
        string $title,
        array $values
    ): array {
        return [
            'title' => $title,
            'headers' => [
                'Metric',
                'Value',
            ],
            'rows' =>
                collect($values)
                    ->map(
                        fn ($value, $key) => [
                            $this->label(
                                (string) $key
                            ),
                            $this->scalar(
                                $value
                            ),
                        ]
                    )
                    ->values()
                    ->all(),
        ];
    }

    private function tableSection(
        string $title,
        array $headers,
        array $rows
    ): array {
        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    private function label(
        string $value
    ): string {
        return ucwords(
            str_replace(
                '_',
                ' ',
                $value
            )
        );
    }

    private function scalar(
        mixed $value
    ): string|int|float {
        if (is_bool($value)) {
            return $this->yesNo(
                $value
            );
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );
        }

        return $value;
    }

    private function yesNo(
        bool $value
    ): string {
        return $value
            ? 'Yes'
            : 'No';
    }

    private function bloodPressure(
        array $row
    ): string {
        $systolic =
            $row['blood_pressure_systolic']
            ?? null;

        $diastolic =
            $row['blood_pressure_diastolic']
            ?? null;

        if (
            $systolic === null
            || $diastolic === null
        ) {
            return '';
        }

        return $systolic
            . '/'
            . $diastolic;
    }

    private function filename(
        string $report,
        Carbon $from,
        Carbon $to,
        string $extension
    ): string {
        return 'smartcare-'
            . $report
            . '-'
            . $from->format('Ymd')
            . '-'
            . $to->format('Ymd')
            . '.'
            . $extension;
    }
}