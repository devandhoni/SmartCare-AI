import {
    useCallback,
    useEffect,
    useMemo,
    useState,
} from "react";

import api from "../services/api";


const REPORTS = [
    {
        value: "resident-census",
        label: "Resident Census",
        endpoint: "/reports/resident-census",
        description:
            "Admissions, discharges and resident occupancy evidence.",
    },
    {
        value: "care-operations",
        label: "Care Operations",
        endpoint: "/reports/care-operations",
        description:
            "Routine care tasks, completion and care record evidence.",
    },
    {
        value: "medication-operations",
        label: "Medication",
        endpoint: "/reports/medication-operations",
        description:
            "Recorded medication administrations and meal confirmation.",
    },
    {
        value: "clinical-monitoring",
        label: "Clinical Monitoring",
        endpoint: "/reports/clinical-monitoring",
        description:
            "Weekly vital signs and monthly glucose monitoring.",
    },
    {
        value: "inventory-operations",
        label: "Inventory",
        endpoint: "/reports/inventory-operations",
        description:
            "Medicine movements and the current inventory snapshot.",
    },
    {
        value: "billing-operations",
        label: "Billing",
        endpoint: "/reports/billing-operations",
        description:
            "Invoices, payments and current outstanding balances.",
    },
    {
        value: "family-facility",
        label: "Family Communication",
        endpoint: "/reports/family-facility",
        view: "family",
        description:
            "Family message delivery and communication evidence.",
    },
    {
        value: "family-facility",
        label: "Facility Activity",
        endpoint: "/reports/family-facility",
        view: "facility",
        description:
            "Recorded operational activity across the facility.",
    },
];


function localDateValue(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, "0"),
        String(date.getDate()).padStart(2, "0"),
    ].join("-");
}


function currentMonthStart() {
    const now = new Date();

    return localDateValue(
        new Date(
            now.getFullYear(),
            now.getMonth(),
            1
        )
    );
}


function todayValue() {
    return localDateValue(
        new Date()
    );
}


function dateText(value) {
    if (!value) {
        return "-";
    }

    const text = String(value);

    const datePart = text.includes("T")
        ? text.split("T")[0]
        : text.split(" ")[0];

    const parts = datePart.split("-");

    if (parts.length !== 3) {
        return text;
    }

    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}


function dateTimeText(value) {
    if (!value) {
        return "-";
    }

    const text = String(value);

    if (text.includes("T")) {
        const [
            datePart,
            timePart = "",
        ] = text.split("T");

        return `${dateText(datePart)} ${timePart
            .replace("Z", "")
            .slice(0, 8)}`.trim();
    }

    if (text.includes(" ")) {
        const [
            datePart,
            ...timeParts
        ] = text.split(" ");

        return `${dateText(datePart)} ${timeParts.join(" ")}`;
    }

    return dateText(text);
}


function money(value) {
    const amount = Number(value || 0);

    return new Intl.NumberFormat(
        "en-MY",
        {
            style: "currency",
            currency: "MYR",
            minimumFractionDigits: 2,
        }
    ).format(amount);
}


function numberText(value) {
    const number = Number(value);

    if (Number.isNaN(number)) {
        return value ?? "-";
    }

    return new Intl.NumberFormat(
        "en-MY"
    ).format(number);
}


function percentage(value) {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "-";
    }

    return `${Number(value).toFixed(1)}%`;
}


function yesNo(value) {
    return value
        ? "Yes"
        : "No";
}


function firstValidationMessage(error) {
    const validation =
        error?.response?.data?.errors;

    if (!validation) {
        return null;
    }

    return Object.values(validation)
        .flat()
        .find(Boolean) || null;
}


function safeArray(value) {
    return Array.isArray(value)
        ? value
        : [];
}


function SummaryCard({
    label,
    value,
    note,
}) {
    return (
        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-slate-500">
                {label}
            </p>

            <p className="mt-2 break-words text-2xl font-bold text-slate-900">
                {value}
            </p>

            {note && (
                <p className="mt-1 text-xs leading-5 text-slate-500">
                    {note}
                </p>
            )}
        </div>
    );
}


function StatusBadge({
    children,
}) {
    const value =
        String(children || "")
            .toUpperCase();

    let classes =
        "bg-slate-100 text-slate-700";

    if (
        [
            "ACTIVE",
            "COMPLETED",
            "PAID",
            "SENT",
            "IN_STOCK",
        ].includes(value)
    ) {
        classes =
            "bg-emerald-100 text-emerald-700";
    } else if (
        [
            "PENDING",
            "ACKNOWLEDGED",
            "PARTIALLY_PAID",
            "DELAYED",
            "EXPIRING_SOON",
        ].includes(value)
    ) {
        classes =
            "bg-amber-100 text-amber-700";
    } else if (
        [
            "FAILED",
            "MISSED",
            "REFUSED",
            "UNAVAILABLE",
            "EXPIRED",
            "OUT_OF_STOCK",
        ].includes(value)
    ) {
        classes =
            "bg-red-100 text-red-700";
    }

    return (
        <span
            className={`
                inline-flex
                max-w-full
                rounded-full
                px-2.5
                py-1
                text-xs
                font-semibold
                ${classes}
            `}
        >
            <span className="truncate">
                {children || "-"}
            </span>
        </span>
    );
}


function DataTable({
    title,
    description,
    columns,
    rows,
    emptyText = "No records were found for this period.",
}) {
    return (
        <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <h2 className="text-lg font-bold text-slate-900">
                    {title}
                </h2>

                {description && (
                    <p className="mt-1 text-sm leading-6 text-slate-500">
                        {description}
                    </p>
                )}
            </div>

            {rows.length === 0 ? (
                <div className="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center">
                    <p className="text-sm text-slate-500">
                        {emptyText}
                    </p>
                </div>
            ) : (
                <div className="mt-5 max-w-full overflow-x-auto">
                    <table className="min-w-full whitespace-nowrap text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200">
                                {columns.map(
                                    (column) => (
                                        <th
                                            key={
                                                column.key
                                            }
                                            className="px-3 py-3 text-xs font-bold uppercase tracking-wide text-slate-500"
                                        >
                                            {column.label}
                                        </th>
                                    )
                                )}
                            </tr>
                        </thead>

                        <tbody>
                            {rows.map(
                                (
                                    row,
                                    index
                                ) => (
                                    <tr
                                        key={
                                            row.id ??
                                            row.key ??
                                            index
                                        }
                                        className="border-b border-slate-100 last:border-b-0"
                                    >
                                        {columns.map(
                                            (
                                                column
                                            ) => (
                                                <td
                                                    key={
                                                        column.key
                                                    }
                                                    className="max-w-xs px-3 py-3 align-top text-slate-700"
                                                >
                                                    {column.render
                                                        ? column.render(
                                                            row
                                                        )
                                                        : row[
                                                            column.key
                                                        ] ?? "-"}
                                                </td>
                                            )
                                        )}
                                    </tr>
                                )
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}


function ResidentCensusReport({
    data,
}) {
    const summary =
        data?.summary || {};

    const residents =
        safeArray(
            data?.residents
        );

    const admissions =
        safeArray(
            data?.admissions
        );

    const discharges =
        safeArray(
            data?.discharges
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Residents Present"
                    value={
                        numberText(
                            summary.residents_present
                        )
                    }
                />

                <SummaryCard
                    label="Admissions"
                    value={
                        numberText(
                            summary.admissions
                        )
                    }
                />

                <SummaryCard
                    label="Discharges"
                    value={
                        numberText(
                            summary.discharges
                        )
                    }
                />

                <SummaryCard
                    label="Active at Period End"
                    value={
                        numberText(
                            summary.active_at_period_end
                        )
                    }
                />
            </section>

            <DataTable
                title="Residents During Period"
                description="Residents supported by completed admission and discharge evidence."
                rows={residents}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "admission_date",
                        label: "Admission",
                        render: (row) =>
                            dateText(
                                row.admission_date
                            ),
                    },
                    {
                        key: "discharge_date",
                        label: "Discharge",
                        render: (row) =>
                            dateText(
                                row.discharge_date
                            ),
                    },
                    {
                        key: "status_at_period_end",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {
                                    row.status_at_period_end
                                }
                            </StatusBadge>
                        ),
                    },
                ]}
            />

            <div className="grid min-w-0 gap-6 xl:grid-cols-2">
                <DataTable
                    title="Admissions"
                    rows={admissions}
                    columns={[
                        {
                            key: "resident_name",
                            label: "Resident",
                        },
                        {
                            key: "admitted_at",
                            label: "Admitted",
                            render: (row) =>
                                dateTimeText(
                                    row.admitted_at
                                ),
                        },
                    ]}
                />

                <DataTable
                    title="Discharges"
                    rows={discharges}
                    columns={[
                        {
                            key: "resident_name",
                            label: "Resident",
                        },
                        {
                            key: "discharged_at",
                            label: "Discharged",
                            render: (row) =>
                                dateTimeText(
                                    row.discharged_at
                                ),
                        },
                    ]}
                />
            </div>
        </>
    );
}


function CareOperationsReport({
    data,
}) {
    const summary =
        data?.summary || {};

    const tasks =
        safeArray(
            data?.scheduled_tasks
        );

    const careRecords =
        safeArray(
            data?.care_records
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Scheduled Care"
                    value={
                        numberText(
                            summary.scheduled
                        )
                    }
                />

                <SummaryCard
                    label="Completed"
                    value={
                        numberText(
                            summary.completed_scheduled
                        )
                    }
                />

                <SummaryCard
                    label="Pending"
                    value={
                        numberText(
                            summary.pending
                        )
                    }
                />

                <SummaryCard
                    label="Completion Rate"
                    value={
                        percentage(
                            summary.completion_rate
                        )
                    }
                />
            </section>

            <DataTable
                title="Routine Care Tasks"
                description="Existing scheduled routine-care evidence for the selected period."
                rows={tasks}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "title",
                        label: "Care",
                        render: (row) => (
                            <span className="block max-w-xs whitespace-normal break-words">
                                {row.task_name || "-"}
                            </span>
                        ),
                    },
                    {
                        key: "source_type",
                        label: "Source",
                    },
                    {
                        key: "scheduled_time",
                        label: "Scheduled",
                        render: (row) =>
                            dateTimeText(
                                row.scheduled_time
                            ),
                    },
                    {
                        key: "status",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {row.status}
                            </StatusBadge>
                        ),
                    },
                ]}
            />

            <DataTable
                title="Care Records"
                rows={careRecords}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "care_type",
                        label: "Care Type",
                    },
                    {
                        key: "title",
                        label: "Title",
                        render: (row) => (
                            <span className="block max-w-xs whitespace-normal break-words">
                                {row.title || "-"}
                            </span>
                        ),
                    },
                    {
                        key: "recorded_at",
                        label: "Recorded",
                        render: (row) =>
                            dateTimeText(
                                row.recorded_at
                            ),
                    },
                ]}
            />
        </>
    );
}


function MedicationReport({
    data,
}) {
    const summary =
        data?.summary || {};

    const meals =
        data?.meals || {};

    const records =
        safeArray(
            data?.records
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Administration Records"
                    value={
                        numberText(
                            summary.total_records
                        )
                    }
                />

                <SummaryCard
                    label="Completed"
                    value={
                        numberText(
                            summary.completed
                        )
                    }
                />

                <SummaryCard
                    label="Refused"
                    value={
                        numberText(
                            summary.refused
                        )
                    }
                />

                <SummaryCard
                    label="Completion Rate"
                    value={
                        percentage(
                            summary.completion_rate
                        )
                    }
                    note={
                        meals.applicable !==
                        undefined
                            ? `${numberText(
                                meals.confirmed
                            )} meal confirmations recorded`
                            : null
                    }
                />
            </section>

            <DataTable
                title="Medication Administration"
                description="Recorded administrations only. This report does not generate due medication occurrences."
                rows={records}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "medication_name",
                        label: "Medication",
                    },
                    {
                        key: "administered_date",
                        label: "Date",
                        render: (row) =>
                            dateText(
                                row.administered_date
                            ),
                    },
                    {
                        key: "time_slot",
                        label: "Round",
                    },
                    {
                        key: "status",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {row.status}
                            </StatusBadge>
                        ),
                    },
                    {
                        key: "meal_type",
                        label: "Meal",
                    },
                    {
                        key: "meal_confirmed",
                        label: "Confirmed",
                        render: (row) =>
                            row.meal_type
                                ? yesNo(
                                    row.meal_confirmed
                                )
                                : "-",
                    },
                ]}
            />
        </>
    );
}


function ClinicalMonitoringReport({
    data,
}) {
    const summary =
        data?.summary || {};

    const weekly =
        safeArray(
            data?.weekly_vitals
        );

    const monthly =
        safeArray(
            data?.monthly_glucose
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Weekly Vital Checks"
                    value={
                        numberText(
                            summary.weekly_vital_checks
                        )
                    }
                />

                <SummaryCard
                    label="Monthly Glucose Checks"
                    value={
                        numberText(
                            summary.monthly_glucose_checks
                        )
                    }
                />

                <SummaryCard
                    label="Monitoring Records"
                    value={
                        numberText(
                            summary.total_monitoring_records
                        )
                    }
                />

                <SummaryCard
                    label="Residents Monitored"
                    value={
                        numberText(
                            summary.residents_monitored
                        )
                    }
                />
            </section>

            <DataTable
                title="Weekly Vital Signs"
                rows={weekly}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "recorded_at",
                        label: "Recorded",
                        render: (row) =>
                            dateTimeText(
                                row.recorded_at
                            ),
                    },
                    {
                        key: "blood_pressure",
                        label: "Blood Pressure",
                        render: (row) =>
                            row.blood_pressure_systolic &&
                            row.blood_pressure_diastolic
                                ? `${row.blood_pressure_systolic}/${row.blood_pressure_diastolic}`
                                : "-",
                    },
                    {
                        key: "heart_rate",
                        label: "Heart Rate",
                    },
                    {
                        key: "oxygen_level",
                        label: "Oxygen",
                    },
                    {
                        key: "temperature",
                        label: "Temperature",
                    },
                    {
                        key: "weight",
                        label: "Weight",
                    },
                    {
                        key: "blood_glucose",
                        label: "Glucose",
                    },
                ]}
            />

            <DataTable
                title="Monthly Glucose Checks"
                rows={monthly}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "recorded_at",
                        label: "Recorded",
                        render: (row) =>
                            dateTimeText(
                                row.recorded_at
                            ),
                    },
                    {
                        key: "blood_glucose",
                        label: "Glucose",
                    },
                    {
                        key: "measurement_type",
                        label: "Type",
                    },
                    {
                        key: "notes",
                        label: "Notes",
                        render: (row) => (
                            <span className="block max-w-sm whitespace-normal break-words">
                                {row.notes || "-"}
                            </span>
                        ),
                    },
                ]}
            />
        </>
    );
}


function InventoryReport({
    data,
}) {
    const movement =
        data?.movement_summary || {};

    const current =
        data?.current_inventory_summary || {};

    const transactions =
        safeArray(
            data?.transactions
        );

    const inventory =
        safeArray(
            data?.current_inventory
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Transactions"
                    value={
                        numberText(
                            movement.total_transactions
                        )
                    }
                />

                <SummaryCard
                    label="Stock In"
                    value={
                        numberText(
                            movement.in_quantity
                        )
                    }
                />

                <SummaryCard
                    label="Stock Out"
                    value={
                        numberText(
                            movement.out_quantity
                        )
                    }
                />

                <SummaryCard
                    label="Current Stock"
                    value={
                        numberText(
                            current.total_quantity
                        )
                    }
                    note="Current snapshot, not historical period-end stock."
                />
            </section>

            <DataTable
                title="Medicine Transactions"
                rows={transactions}
                columns={[
                    {
                        key: "medication_name",
                        label: "Medication",
                    },
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "transaction_type",
                        label: "Type",
                        render: (row) => (
                            <StatusBadge>
                                {
                                    row.transaction_type
                                }
                            </StatusBadge>
                        ),
                    },
                    {
                        key: "quantity",
                        label: "Quantity",
                    },
                    {
                        key: "reference",
                        label: "Reference",
                    },
                    {
                        key: "transaction_date",
                        label: "Date",
                        render: (row) =>
                            dateTimeText(
                                row.transaction_date
                            ),
                    },
                ]}
            />

            <DataTable
                title="Current Inventory"
                description="Live inventory position shown alongside historical movements."
                rows={inventory}
                columns={[
                    {
                        key: "medication_name",
                        label: "Medication",
                    },
                    {
                        key: "quantity",
                        label: "Quantity",
                    },
                    {
                        key: "minimum_stock",
                        label: "Minimum",
                    },
                    {
                        key: "expiry_date",
                        label: "Expiry",
                        render: (row) =>
                            dateText(
                                row.expiry_date
                            ),
                    },
                    {
                        key: "location",
                        label: "Location",
                    },
                    {
                        key: "stock_status",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {
                                    row.stock_status
                                }
                            </StatusBadge>
                        ),
                    },
                ]}
            />
        </>
    );
}


function BillingReport({
    data,
}) {
    const invoices =
        data?.invoice_summary || {};

    const payments =
        data?.payment_summary || {};

    const invoiceRows =
        safeArray(
            data?.invoices
        );

    const paymentRows =
        safeArray(
            data?.payments
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Invoices"
                    value={
                        numberText(
                            invoices.total_invoices
                        )
                    }
                />

                <SummaryCard
                    label="Invoice Total"
                    value={
                        money(
                            invoices.non_void_invoice_total
                        )
                    }
                />

                <SummaryCard
                    label="Payments Received"
                    value={
                        money(
                            payments.amount_received
                        )
                    }
                />

                <SummaryCard
                    label="Outstanding Balance"
                    value={
                        money(
                            invoices.current_outstanding_balance
                        )
                    }
                />
            </section>

            <DataTable
                title="Invoices"
                rows={invoiceRows}
                columns={[
                    {
                        key: "invoice_number",
                        label: "Invoice",
                    },
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "billing_period",
                        label: "Billing Period",
                        render: (row) =>
                            `${dateText(
                                row.billing_period_start
                            )} - ${dateText(
                                row.billing_period_end
                            )}`,
                    },
                    {
                        key: "total_amount",
                        label: "Total",
                        render: (row) =>
                            money(
                                row.total_amount
                            ),
                    },
                    {
                        key: "amount_paid",
                        label: "Paid",
                        render: (row) =>
                            money(
                                row.amount_paid
                            ),
                    },
                    {
                        key: "balance_due",
                        label: "Balance",
                        render: (row) =>
                            money(
                                row.balance_due
                            ),
                    },
                    {
                        key: "status",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {row.status}
                            </StatusBadge>
                        ),
                    },
                ]}
            />

            <DataTable
                title="Payments Received"
                rows={paymentRows}
                columns={[
                    {
                        key: "payment_reference",
                        label: "Reference",
                    },
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "amount",
                        label: "Amount",
                        render: (row) =>
                            money(
                                row.amount
                            ),
                    },
                    {
                        key: "payment_date",
                        label: "Date",
                        render: (row) =>
                            dateText(
                                row.payment_date
                            ),
                    },
                    {
                        key: "payment_method",
                        label: "Method",
                    },
                    {
                        key: "received_by",
                        label: "Received By",
                    },
                ]}
            />
        </>
    );
}


function FamilyCommunicationReport({
    data,
}) {
    const summary =
        data?.family_communication_summary || {};

    const messages =
        safeArray(
            data?.family_messages
        );

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Messages"
                    value={
                        numberText(
                            summary.total_messages
                        )
                    }
                />

                <SummaryCard
                    label="Sent"
                    value={
                        numberText(
                            summary.sent
                        )
                    }
                />

                <SummaryCard
                    label="Pending"
                    value={
                        numberText(
                            summary.pending
                        )
                    }
                />

                <SummaryCard
                    label="Failed"
                    value={
                        numberText(
                            summary.failed
                        )
                    }
                />
            </section>

            <DataTable
                title="Family Messages"
                description="Communication evidence only. Family phone numbers are not displayed."
                rows={messages}
                columns={[
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "recipient_name",
                        label: "Family Contact",
                    },
                    {
                        key: "message_type",
                        label: "Message Type",
                    },
                    {
                        key: "channel",
                        label: "Channel",
                    },
                    {
                        key: "status",
                        label: "Status",
                        render: (row) => (
                            <StatusBadge>
                                {row.status}
                            </StatusBadge>
                        ),
                    },
                    {
                        key: "attempt_count",
                        label: "Attempts",
                    },
                    {
                        key: "created_at",
                        label: "Created",
                        render: (row) =>
                            dateTimeText(
                                row.created_at
                            ),
                    },
                ]}
            />
        </>
    );
}


function FacilityActivityReport({
    data,
}) {
    const summary =
        data?.facility_activity_summary || {};

    const activities =
        safeArray(
            data?.facility_activities
        );

    const modules =
        summary.modules || {};

    return (
        <>
            <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Activities"
                    value={
                        numberText(
                            summary.total_activities
                        )
                    }
                />

                <SummaryCard
                    label="Modules"
                    value={
                        numberText(
                            Object.keys(
                                modules
                            ).length
                        )
                    }
                />

                <SummaryCard
                    label="Most Active Module"
                    value={
                        Object.keys(
                            modules
                        )[0] || "-"
                    }
                    note={
                        Object.values(
                            modules
                        )[0] !== undefined
                            ? `${numberText(
                                Object.values(
                                    modules
                                )[0]
                            )} recorded activities`
                            : null
                    }
                />

                <SummaryCard
                    label="Recorded Actions"
                    value={
                        numberText(
                            Object.values(
                                summary.actions || {}
                            ).reduce(
                                (
                                    total,
                                    count
                                ) =>
                                    total +
                                    Number(
                                        count || 0
                                    ),
                                0
                            )
                        )
                    }
                />
            </section>

            <DataTable
                title="Facility Activity"
                description="Existing operational activity-log evidence for the selected period."
                rows={activities}
                columns={[
                    {
                        key: "user_name",
                        label: "User",
                    },
                    {
                        key: "resident_name",
                        label: "Resident",
                    },
                    {
                        key: "module",
                        label: "Module",
                    },
                    {
                        key: "action",
                        label: "Action",
                    },
                    {
                        key: "description",
                        label: "Description",
                        render: (row) => (
                            <span className="block max-w-md whitespace-normal break-words">
                                {row.description || "-"}
                            </span>
                        ),
                    },
                    {
                        key: "created_on",
                        label: "Recorded",
                        render: (row) =>
                            dateTimeText(
                                row.created_on
                            ),
                    },
                ]}
            />
        </>
    );
}


function Reports() {
    const [
        selectedReportIndex,
        setSelectedReportIndex,
    ] = useState(0);

    const [from, setFrom] =
        useState(
            currentMonthStart()
        );

    const [to, setTo] =
        useState(
            todayValue()
        );

    const [reportData, setReportData] =
        useState(null);

    const [loading, setLoading] =
        useState(false);

    const [
        downloadingFormat,
        setDownloadingFormat,
    ] = useState("");

    const [error, setError] =
        useState("");

    const selectedReport =
        REPORTS[
            selectedReportIndex
        ];


    const periodText = useMemo(
        () =>
            `${dateText(from)} - ${dateText(to)}`,
        [from, to]
    );


    const loadReport =
        useCallback(
            async () => {
                if (
                    !from ||
                    !to
                ) {
                    setError(
                        "Please select both report dates."
                    );
                    return;
                }

                if (from > to) {
                    setError(
                        "The start date must be on or before the end date."
                    );
                    return;
                }

                try {
                    setLoading(true);
                    setError("");

                    const response =
                        await api.get(
                            selectedReport.endpoint,
                            {
                                params: {
                                    from,
                                    to,
                                },
                            }
                        );

                    setReportData(
                        response.data?.data ||
                        null
                    );
                } catch (err) {
                    console.error(
                        "Report load error:",
                        err
                    );

                    setReportData(null);

                    setError(
                        firstValidationMessage(
                            err
                        ) ||
                        err?.response?.data?.message ||
                        "Unable to load the selected report."
                    );
                } finally {
                    setLoading(false);
                }
            },
            [
                from,
                to,
                selectedReport.endpoint,
            ]
        );


    useEffect(() => {
        loadReport();
    }, [loadReport]);


    const downloadReport =
        async (format) => {
            if (
                !from ||
                !to
            ) {
                setError(
                    "Please select both report dates."
                );
                return;
            }

            if (from > to) {
                setError(
                    "The start date must be on or before the end date."
                );
                return;
            }

            try {
                setDownloadingFormat(
                    format
                );

                setError("");

                const response =
                    await api.get(
                        "/reports/export",
                        {
                            params: {
                                report:
                                    selectedReport.value,
                                format,
                                from,
                                to,
                            },

                            responseType:
                                "blob",
                        }
                    );

                const contentType =
                    response.headers[
                        "content-type"
                    ] ||
                    (
                        format === "pdf"
                            ? "application/pdf"
                            : "text/csv;charset=UTF-8"
                    );

                const blob =
                    new Blob(
                        [
                            response.data,
                        ],
                        {
                            type:
                                contentType,
                        }
                    );

                const url =
                    window.URL
                        .createObjectURL(
                            blob
                        );

                const link =
                    document.createElement(
                        "a"
                    );

                link.href = url;

                link.download =
                    `smartcare-${selectedReport.value}-${from.replaceAll(
                        "-",
                        ""
                    )}-${to.replaceAll(
                        "-",
                        ""
                    )}.${format}`;

                document.body
                    .appendChild(
                        link
                    );

                link.click();

                link.remove();

                window.URL
                    .revokeObjectURL(
                        url
                    );
            } catch (err) {
                console.error(
                    `Report ${format} download error:`,
                    err
                );

                setError(
                    `Unable to download the ${format.toUpperCase()} report.`
                );
            } finally {
                setDownloadingFormat(
                    ""
                );
            }
        };


    const reportContent = () => {
        if (!reportData) {
            return null;
        }

        switch (
            selectedReport.label
        ) {
            case "Resident Census":
                return (
                    <ResidentCensusReport
                        data={
                            reportData
                        }
                    />
                );

            case "Care Operations":
                return (
                    <CareOperationsReport
                        data={
                            reportData
                        }
                    />
                );

            case "Medication":
                return (
                    <MedicationReport
                        data={
                            reportData
                        }
                    />
                );

            case "Clinical Monitoring":
                return (
                    <ClinicalMonitoringReport
                        data={
                            reportData
                        }
                    />
                );

            case "Inventory":
                return (
                    <InventoryReport
                        data={
                            reportData
                        }
                    />
                );

            case "Billing":
                return (
                    <BillingReport
                        data={
                            reportData
                        }
                    />
                );

            case "Family Communication":
                return (
                    <FamilyCommunicationReport
                        data={
                            reportData
                        }
                    />
                );

            case "Facility Activity":
                return (
                    <FacilityActivityReport
                        data={
                            reportData
                        }
                    />
                );

            default:
                return null;
        }
    };


    return (
        <div className="min-w-0 space-y-6">
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                            Management Reporting
                        </p>

                        <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                            Reports
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            Review recorded care-home
                            operations for a selected
                            reporting period and export
                            the evidence as PDF or CSV.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={
                            loadReport
                        }
                        disabled={
                            loading
                        }
                        className="shrink-0 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {loading
                            ? "Refreshing..."
                            : "Refresh"}
                    </button>
                </div>
            </section>


            {error && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {error}
                </div>
            )}


            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="min-w-0">
                        <label
                            htmlFor="report-type"
                            className="text-sm font-semibold text-slate-700"
                        >
                            Report
                        </label>

                        <select
                            id="report-type"
                            value={
                                selectedReportIndex
                            }
                            onChange={(
                                event
                            ) => {
                                setSelectedReportIndex(
                                    Number(
                                        event
                                            .target
                                            .value
                                    )
                                );

                                setReportData(
                                    null
                                );

                                setError("");
                            }}
                            className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            {REPORTS.map(
                                (
                                    report,
                                    index
                                ) => (
                                    <option
                                        key={`${report.label}-${index}`}
                                        value={
                                            index
                                        }
                                    >
                                        {
                                            report.label
                                        }
                                    </option>
                                )
                            )}
                        </select>
                    </div>


                    <div className="min-w-0">
                        <label
                            htmlFor="report-from"
                            className="text-sm font-semibold text-slate-700"
                        >
                            From
                        </label>

                        <input
                            id="report-from"
                            type="date"
                            value={from}
                            onChange={(
                                event
                            ) => {
                                setFrom(
                                    event
                                        .target
                                        .value
                                );

                                setError("");
                            }}
                            className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </div>


                    <div className="min-w-0">
                        <label
                            htmlFor="report-to"
                            className="text-sm font-semibold text-slate-700"
                        >
                            To
                        </label>

                        <input
                            id="report-to"
                            type="date"
                            value={to}
                            onChange={(
                                event
                            ) => {
                                setTo(
                                    event
                                        .target
                                        .value
                                );

                                setError("");
                            }}
                            className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </div>


                    <div className="flex min-w-0 items-end">
                        <button
                            type="button"
                            onClick={
                                loadReport
                            }
                            disabled={
                                loading
                            }
                            className="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            {loading
                                ? "Loading..."
                                : "View Report"}
                        </button>
                    </div>
                </div>


                <div className="mt-5 flex min-w-0 flex-col gap-4 border-t border-slate-100 pt-5 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <p className="font-semibold text-slate-900">
                            {
                                selectedReport.label
                            }
                        </p>

                        <p className="mt-1 text-sm leading-6 text-slate-500">
                            {
                                selectedReport.description
                            }
                        </p>

                        <p className="mt-1 text-xs font-medium text-slate-400">
                            {periodText}
                        </p>
                    </div>


                    <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            onClick={() =>
                                downloadReport(
                                    "pdf"
                                )
                            }
                            disabled={
                                !reportData ||
                                loading ||
                                Boolean(
                                    downloadingFormat
                                )
                            }
                            className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            {downloadingFormat ===
                            "pdf"
                                ? "Preparing PDF..."
                                : "Download PDF"}
                        </button>

                        <button
                            type="button"
                            onClick={() =>
                                downloadReport(
                                    "csv"
                                )
                            }
                            disabled={
                                !reportData ||
                                loading ||
                                Boolean(
                                    downloadingFormat
                                )
                            }
                            className="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {downloadingFormat ===
                            "csv"
                                ? "Preparing CSV..."
                                : "Download CSV"}
                        </button>
                    </div>
                </div>
            </section>


            {loading ? (
                <section className="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                    <p className="text-sm font-medium text-slate-500">
                        Loading report...
                    </p>
                </section>
            ) : reportData ? (
                reportContent()
            ) : (
                <section className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <p className="text-sm text-slate-500">
                        Select a report and reporting
                        period to view the recorded
                        information.
                    </p>
                </section>
            )}
        </div>
    );
}


export default Reports;