import {
    useCallback,
    useEffect,
    useMemo,
    useState,
} from "react";

import api from "../services/api";


const PAYMENT_METHODS = [
    {
        value: "CASH",
        label: "Cash",
    },
    {
        value: "BANK_TRANSFER",
        label: "Bank Transfer",
    },
    {
        value: "CARD",
        label: "Card",
    },
    {
        value: "CHEQUE",
        label: "Cheque",
    },
    {
        value: "OTHER",
        label: "Other",
    },
];


function currentMonthValue() {
    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, "0"),
    ].join("-");
}


function todayValue() {
    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, "0"),
        String(now.getDate()).padStart(2, "0"),
    ].join("-");
}


function money(value) {
    const amount = Number(value || 0);

    return new Intl.NumberFormat("en-MY", {
        style: "currency",
        currency: "MYR",
        minimumFractionDigits: 2,
    }).format(amount);
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


function monthLabel(start, end) {
    if (!start) {
        return "-";
    }

    return `${dateText(start)} - ${dateText(end)}`;
}


function statusClasses(status) {
    switch (status) {
        case "PAID":
            return "bg-emerald-100 text-emerald-700";

        case "PARTIALLY_PAID":
            return "bg-amber-100 text-amber-700";

        case "VOID":
            return "bg-slate-200 text-slate-600";

        case "DRAFT":
            return "bg-slate-100 text-slate-700";

        default:
            return "bg-blue-100 text-blue-700";
    }
}


function reasonText(reason) {
    switch (reason) {
        case "NO_COMPLETED_ADMISSION":
            return "No completed admission is available.";

        case "NO_ADMISSION_CONSENT":
            return "No admission agreement is available.";

        case "CONSENT_NOT_COMPLETED":
            return "The admission agreement is not completed.";

        case "PAYMENT_TERMS_NOT_ACKNOWLEDGED":
            return "Payment and fee terms have not been acknowledged.";

        case "AGREEMENT_NOT_ACKNOWLEDGED":
            return "The admission agreement has not been acknowledged.";

        case "BILLING_FEE_NOT_CONFIGURED":
            return "Monthly billing fee is not configured.";

        default:
            return "Billing fee is not currently configured.";
    }
}


function getResidentsFromPayload(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload?.residents)) {
        return payload.residents;
    }

    if (Array.isArray(payload?.residents?.data)) {
        return payload.residents.data;
    }

    return [];
}


function Billing() {
    const [residents, setResidents] = useState([]);
    const [selectedResidentId, setSelectedResidentId] =
        useState("");

    const [feeStatus, setFeeStatus] = useState(null);
    const [invoices, setInvoices] = useState([]);
    const [outstandingBalance, setOutstandingBalance] =
        useState("0.00");

    const [billingMonth, setBillingMonth] =
        useState(currentMonthValue());

    const [paymentInvoice, setPaymentInvoice] =
        useState(null);

    const [paymentForm, setPaymentForm] = useState({
        amount: "",
        payment_date: todayValue(),
        payment_method: "CASH",
        notes: "",
    });

    const [receipt, setReceipt] = useState(null);

    const [loadingResidents, setLoadingResidents] =
        useState(true);

    const [loadingBilling, setLoadingBilling] =
        useState(false);

    const [generating, setGenerating] =
        useState(false);

    const [recordingPayment, setRecordingPayment] =
        useState(false);

    const [loadingReceiptId, setLoadingReceiptId] =
        useState(null);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");


    const selectedResident = useMemo(
        () =>
            residents.find(
                (resident) =>
                    String(resident.id) ===
                    String(selectedResidentId)
            ) || null,
        [residents, selectedResidentId]
    );


    const isActiveResident =
        selectedResident?.status === "Active";


    const loadResidents = useCallback(async () => {
        try {
            setLoadingResidents(true);
            setError("");

            const response = await api.get(
                "/residents",
                {
                    params: {
                        per_page: 200,
                    },
                }
            );

            const items = getResidentsFromPayload(
                response.data
            );

            setResidents(items);

            if (
                items.length > 0 &&
                !selectedResidentId
            ) {
                const firstActive =
                    items.find(
                        (resident) =>
                            resident.status === "Active"
                    ) || items[0];

                setSelectedResidentId(
                    String(firstActive.id)
                );
            }
        } catch (err) {
            console.error(
                "Billing residents load error:",
                err
            );

            setError(
                err?.response?.data?.message ||
                "Unable to load residents."
            );
        } finally {
            setLoadingResidents(false);
        }
    }, [selectedResidentId]);


    const loadBilling = useCallback(async () => {
        if (!selectedResidentId) {
            return;
        }

        try {
            setLoadingBilling(true);
            setError("");
            setReceipt(null);

            const [
                feeResponse,
                invoiceResponse,
            ] = await Promise.all([
                api.get(
                    `/residents/${selectedResidentId}/billing/fee-status`
                ),

                api.get(
                    `/residents/${selectedResidentId}/billing/invoices`
                ),
            ]);

            setFeeStatus(
                feeResponse.data || null
            );

            setInvoices(
                Array.isArray(
                    invoiceResponse.data?.invoices
                )
                    ? invoiceResponse.data.invoices
                    : []
            );

            setOutstandingBalance(
                invoiceResponse.data
                    ?.outstanding_balance ||
                "0.00"
            );
        } catch (err) {
            console.error(
                "Billing load error:",
                err
            );

            setFeeStatus(null);
            setInvoices([]);
            setOutstandingBalance("0.00");

            setError(
                err?.response?.data?.message ||
                "Unable to load billing information."
            );
        } finally {
            setLoadingBilling(false);
        }
    }, [selectedResidentId]);


    useEffect(() => {
        loadResidents();
    }, [loadResidents]);


    useEffect(() => {
        loadBilling();
    }, [loadBilling]);


    const generateInvoice = async () => {
        if (!selectedResidentId) {
            return;
        }

        if (!isActiveResident) {
            setError(
                "Invoices cannot be generated for a discharged or inactive resident."
            );
            return;
        }

        if (!feeStatus?.configured) {
            setError(
                "A valid monthly billing fee must be configured before generating an invoice."
            );
            return;
        }

        try {
            setGenerating(true);
            setError("");
            setSuccess("");

            const response = await api.post(
                `/residents/${selectedResidentId}/billing/invoices/monthly`,
                {
                    billing_month:
                        `${billingMonth}-01`,
                }
            );

            setSuccess(
                response.data?.message ||
                "Monthly invoice generated successfully."
            );

            await loadBilling();
        } catch (err) {
            console.error(
                "Invoice generation error:",
                err
            );

            const validation =
                err?.response?.data?.errors;

            const firstValidationMessage =
                validation
                    ? Object.values(validation)
                        .flat()
                        .find(Boolean)
                    : null;

            setError(
                firstValidationMessage ||
                err?.response?.data?.message ||
                "Unable to generate the invoice."
            );
        } finally {
            setGenerating(false);
        }
    };


    const openPayment = (invoice) => {
        if (!isActiveResident) {
            setError(
                "Payments cannot be recorded for a discharged or inactive resident from this operational screen."
            );
            return;
        }

        setError("");
        setSuccess("");
        setReceipt(null);

        setPaymentInvoice(invoice);

        setPaymentForm({
            amount: invoice.balance_due || "",
            payment_date: todayValue(),
            payment_method: "CASH",
            notes: "",
        });
    };


    const closePayment = () => {
        if (recordingPayment) {
            return;
        }

        setPaymentInvoice(null);

        setPaymentForm({
            amount: "",
            payment_date: todayValue(),
            payment_method: "CASH",
            notes: "",
        });
    };


    const recordPayment = async (event) => {
        event.preventDefault();

        if (!paymentInvoice) {
            return;
        }

        try {
            setRecordingPayment(true);
            setError("");
            setSuccess("");

            const response = await api.post(
                `/billing/invoices/${paymentInvoice.id}/payments`,
                {
                    amount: Number(
                        paymentForm.amount
                    ),

                    payment_date:
                        paymentForm.payment_date,

                    payment_method:
                        paymentForm.payment_method,

                    notes:
                        paymentForm.notes.trim() ||
                        null,
                }
            );

            setSuccess(
                response.data?.message ||
                "Payment recorded successfully."
            );

            setPaymentInvoice(null);

            await loadBilling();
        } catch (err) {
            console.error(
                "Payment recording error:",
                err
            );

            const validation =
                err?.response?.data?.errors;

            const firstValidationMessage =
                validation
                    ? Object.values(validation)
                        .flat()
                        .find(Boolean)
                    : null;

            setError(
                firstValidationMessage ||
                err?.response?.data?.message ||
                "Unable to record payment."
            );
        } finally {
            setRecordingPayment(false);
        }
    };


    const viewReceipt = async (paymentId) => {
        try {
            setLoadingReceiptId(paymentId);
            setError("");
            setReceipt(null);

            const response = await api.get(
                `/billing/payments/${paymentId}/receipt`
            );

            setReceipt(
                response.data?.receipt || null
            );
        } catch (err) {
            console.error(
                "Receipt load error:",
                err
            );

            setError(
                err?.response?.data?.message ||
                "Unable to load payment receipt."
            );
        } finally {
            setLoadingReceiptId(null);
        }
    };

    const downloadReceiptPdf = async (
    payment
) => {
    try {
        setError("");

        const response = await api.get(
            `/billing/payments/${payment.id}/receipt/pdf`,
            {
                responseType: "blob",
            }
        );

        const blob = new Blob(
            [response.data],
            {
                type: "application/pdf",
            }
        );

        const url =
            window.URL.createObjectURL(blob);

        const link =
            document.createElement("a");

        link.href = url;

        link.download =
            `Receipt-${payment.payment_reference}.pdf`;

        document.body.appendChild(link);

        link.click();

        link.remove();

        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error(
            "Receipt PDF download error:",
            err
        );

        setError(
            "Unable to download the payment receipt PDF."
        );
    }
};


    return (
        <div className="min-w-0 space-y-6">
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                            Resident Billing
                        </p>

                        <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                            Billing & Payments
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            Review monthly fees,
                            invoices, payments and
                            outstanding balances for
                            each resident.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={loadBilling}
                        disabled={
                            loadingBilling ||
                            !selectedResidentId
                        }
                        className="
                            rounded-xl
                            border
                            border-slate-300
                            bg-white
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-slate-700
                            transition
                            hover:bg-slate-50
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                        "
                    >
                        {loadingBilling
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


            {success && (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {success}
                </div>
            )}


            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <label
                    htmlFor="billing-resident"
                    className="text-sm font-semibold text-slate-700"
                >
                    Resident
                </label>

                <select
                    id="billing-resident"
                    value={selectedResidentId}
                    disabled={loadingResidents}
                    onChange={(event) => {
                        setSelectedResidentId(
                            event.target.value
                        );
                        setError("");
                        setSuccess("");
                        setReceipt(null);
                    }}
                    className="
                        mt-2
                        w-full
                        rounded-xl
                        border
                        border-slate-300
                        bg-white
                        px-3
                        py-3
                        text-sm
                        text-slate-900
                        outline-none
                        transition
                        focus:border-blue-500
                        focus:ring-2
                        focus:ring-blue-100
                    "
                >
                    {residents.length === 0 && (
                        <option value="">
                            No residents available
                        </option>
                    )}

                    {residents.map(
                        (resident) => (
                            <option
                                key={resident.id}
                                value={resident.id}
                            >
                                {resident.full_name}
                                {" - "}
                                {resident.status}
                            </option>
                        )
                    )}
                </select>

                {selectedResident && (
                    <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
                        <span className="font-semibold text-slate-900">
                            {selectedResident.full_name}
                        </span>

                        <span
                            className={`
                                rounded-full
                                px-2.5
                                py-1
                                text-xs
                                font-semibold
                                ${
                                    isActiveResident
                                        ? "bg-emerald-100 text-emerald-700"
                                        : "bg-slate-200 text-slate-600"
                                }
                            `}
                        >
                            {selectedResident.status}
                        </span>
                    </div>
                )}

                {selectedResident &&
                    !isActiveResident && (
                        <p className="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            Historical billing remains
                            available for this resident.
                            New operational billing
                            actions are disabled.
                        </p>
                    )}
            </section>


            {loadingBilling ? (
                <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p className="text-sm text-slate-500">
                        Loading billing information...
                    </p>
                </section>
            ) : (
                <>
                    <section className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">
                                Monthly Fee
                            </p>

                            <p className="mt-2 break-words text-2xl font-bold text-slate-900">
                                {feeStatus?.configured
                                    ? money(
                                        feeStatus.monthly_fee
                                    )
                                    : "Not configured"}
                            </p>

                            {!feeStatus?.configured &&
                                feeStatus?.reason && (
                                    <p className="mt-2 text-xs leading-5 text-amber-700">
                                        {reasonText(
                                            feeStatus.reason
                                        )}
                                    </p>
                                )}
                        </div>

                        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">
                                Outstanding Balance
                            </p>

                            <p className="mt-2 break-words text-2xl font-bold text-slate-900">
                                {money(
                                    outstandingBalance
                                )}
                            </p>
                        </div>

                        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                            <p className="text-sm font-medium text-slate-500">
                                Invoice History
                            </p>

                            <p className="mt-2 text-2xl font-bold text-slate-900">
                                {invoices.length}
                            </p>

                            <p className="mt-1 text-xs text-slate-500">
                                Total recorded invoices
                            </p>
                        </div>
                    </section>


                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-slate-900">
                                    Generate Monthly Invoice
                                </h2>

                                <p className="mt-1 text-sm text-slate-500">
                                    One invoice can be
                                    created per resident
                                    per calendar month.
                                </p>
                            </div>

                            <div className="flex w-full flex-col gap-3 sm:flex-row lg:w-auto">
                                <input
                                    type="month"
                                    value={billingMonth}
                                    onChange={(event) =>
                                        setBillingMonth(
                                            event.target.value
                                        )
                                    }
                                    className="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-300
                                        px-3
                                        py-2.5
                                        text-sm
                                        outline-none
                                        focus:border-blue-500
                                        focus:ring-2
                                        focus:ring-blue-100
                                        sm:w-auto
                                    "
                                />

                                <button
                                    type="button"
                                    onClick={
                                        generateInvoice
                                    }
                                    disabled={
                                        generating ||
                                        !isActiveResident ||
                                        !feeStatus?.configured ||
                                        !billingMonth
                                    }
                                    className="
                                        rounded-xl
                                        bg-blue-600
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-semibold
                                        text-white
                                        transition
                                        hover:bg-blue-700
                                        disabled:cursor-not-allowed
                                        disabled:bg-slate-300
                                    "
                                >
                                    {generating
                                        ? "Generating..."
                                        : "Generate Invoice"}
                                </button>
                            </div>
                        </div>
                    </section>


                    <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">
                                Invoice History
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                View invoice balances,
                                payments and receipt
                                evidence.
                            </p>
                        </div>

                        {invoices.length === 0 ? (
                            <div className="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center">
                                <p className="text-sm text-slate-500">
                                    No billing invoices
                                    have been recorded for
                                    this resident.
                                </p>
                            </div>
                        ) : (
                            <div className="mt-5 space-y-4">
                                {invoices.map(
                                    (invoice) => {
                                        const canPay =
                                            isActiveResident &&
                                            [
                                                "ISSUED",
                                                "PARTIALLY_PAID",
                                            ].includes(
                                                invoice.status
                                            ) &&
                                            Number(
                                                invoice.balance_due
                                            ) > 0;

                                        return (
                                            <article
                                                key={
                                                    invoice.id
                                                }
                                                className="min-w-0 rounded-2xl border border-slate-200 p-4 sm:p-5"
                                            >
                                                <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div className="min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <h3 className="break-all text-base font-bold text-slate-900">
                                                                {
                                                                    invoice.invoice_number
                                                                }
                                                            </h3>

                                                            <span
                                                                className={`
                                                                    rounded-full
                                                                    px-2.5
                                                                    py-1
                                                                    text-xs
                                                                    font-semibold
                                                                    ${statusClasses(
                                                                        invoice.status
                                                                    )}
                                                                `}
                                                            >
                                                                {
                                                                    invoice.status
                                                                }
                                                            </span>

                                                            {invoice.is_overdue && (
                                                                <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                                    Overdue
                                                                </span>
                                                            )}
                                                        </div>

                                                        <p className="mt-2 text-sm text-slate-500">
                                                            Billing period:{" "}
                                                            {monthLabel(
                                                                invoice.billing_period_start,
                                                                invoice.billing_period_end
                                                            )}
                                                        </p>

                                                        <p className="mt-1 text-sm text-slate-500">
                                                            Due:{" "}
                                                            {dateText(
                                                                invoice.due_date
                                                            )}
                                                        </p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        disabled={
                                                            !canPay
                                                        }
                                                        onClick={() =>
                                                            openPayment(
                                                                invoice
                                                            )
                                                        }
                                                        className="
                                                            shrink-0
                                                            rounded-xl
                                                            bg-blue-600
                                                            px-4
                                                            py-2.5
                                                            text-sm
                                                            font-semibold
                                                            text-white
                                                            hover:bg-blue-700
                                                            disabled:cursor-not-allowed
                                                            disabled:bg-slate-300
                                                        "
                                                    >
                                                        Record Payment
                                                    </button>
                                                </div>

                                                <div className="mt-5 grid min-w-0 gap-3 sm:grid-cols-3">
                                                    <div className="min-w-0 rounded-xl bg-slate-50 p-3">
                                                        <p className="text-xs font-medium text-slate-500">
                                                            Total
                                                        </p>
                                                        <p className="mt-1 break-words font-bold text-slate-900">
                                                            {money(
                                                                invoice.total_amount
                                                            )}
                                                        </p>
                                                    </div>

                                                    <div className="min-w-0 rounded-xl bg-slate-50 p-3">
                                                        <p className="text-xs font-medium text-slate-500">
                                                            Paid
                                                        </p>
                                                        <p className="mt-1 break-words font-bold text-emerald-700">
                                                            {money(
                                                                invoice.amount_paid
                                                            )}
                                                        </p>
                                                    </div>

                                                    <div className="min-w-0 rounded-xl bg-slate-50 p-3">
                                                        <p className="text-xs font-medium text-slate-500">
                                                            Balance
                                                        </p>
                                                        <p className="mt-1 break-words font-bold text-slate-900">
                                                            {money(
                                                                invoice.balance_due
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="mt-5 border-t border-slate-100 pt-4">
                                                    <h4 className="text-sm font-bold text-slate-800">
                                                        Payments
                                                    </h4>

                                                    {!Array.isArray(
                                                        invoice.payments
                                                    ) ||
                                                    invoice.payments.length ===
                                                        0 ? (
                                                        <p className="mt-2 text-sm text-slate-500">
                                                            No payments
                                                            recorded.
                                                        </p>
                                                    ) : (
                                                        <div className="mt-3 space-y-2">
                                                            {invoice.payments.map(
                                                                (
                                                                    payment
                                                                ) => (
                                                                    <div
                                                                        key={
                                                                            payment.id
                                                                        }
                                                                        className="flex min-w-0 flex-col gap-3 rounded-xl bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                                    >
                                                                        <div className="min-w-0">
                                                                            <p className="break-all text-sm font-semibold text-slate-900">
                                                                                {
                                                                                    payment.payment_reference
                                                                                }
                                                                            </p>

                                                                            <p className="mt-1 text-xs leading-5 text-slate-500">
                                                                                {dateText(
                                                                                    payment.payment_date
                                                                                )}
                                                                                {" · "}
                                                                                {
                                                                                    payment.payment_method
                                                                                }
                                                                                {" · "}
                                                                                {money(
                                                                                    payment.amount
                                                                                )}
                                                                            </p>
                                                                        </div>

                                                                        <div className="flex shrink-0 flex-wrap gap-2">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() =>
                                                                                    viewReceipt(
                                                                                        payment.id
                                                                                    )
                                                                                }
                                                                                disabled={
                                                                                    loadingReceiptId ===
                                                                                    payment.id
                                                                                }
                                                                                className="
                                                                                    rounded-lg
                                                                                    border
                                                                                    border-slate-300
                                                                                    bg-white
                                                                                    px-3
                                                                                    py-2
                                                                                    text-xs
                                                                                    font-semibold
                                                                                    text-slate-700
                                                                                    hover:bg-slate-50
                                                                                    disabled:opacity-50
                                                                                "
                                                                            >
                                                                                {loadingReceiptId ===
                                                                                payment.id
                                                                                    ? "Loading..."
                                                                                    : "View Receipt"}
                                                                            </button>

                                                                            <button
                                                                                type="button"
                                                                                onClick={() =>
                                                                                    downloadReceiptPdf(
                                                                                        payment
                                                                                    )
                                                                                }
                                                                                className="
                                                                                    rounded-lg
                                                                                    bg-emerald-600
                                                                                    px-3
                                                                                    py-2
                                                                                    text-xs
                                                                                    font-semibold
                                                                                    text-white
                                                                                    hover:bg-emerald-700
                                                                                "
                                                                            >
                                                                                Download PDF
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                )
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </article>
                                        );
                                    }
                                )}
                            </div>
                        )}
                    </section>
                </>
            )}


            {paymentInvoice && (
                <section className="rounded-2xl border border-blue-200 bg-blue-50/40 p-5 shadow-sm sm:p-6">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                                Record Payment
                            </p>

                            <h2 className="mt-1 text-xl font-bold text-slate-900">
                                {
                                    paymentInvoice.invoice_number
                                }
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                Outstanding:{" "}
                                {money(
                                    paymentInvoice.balance_due
                                )}
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={closePayment}
                            disabled={
                                recordingPayment
                            }
                            className="text-sm font-semibold text-slate-500 hover:text-slate-800"
                        >
                            Close
                        </button>
                    </div>

                    <form
                        onSubmit={recordPayment}
                        className="mt-5 grid min-w-0 gap-4 md:grid-cols-2"
                    >
                        <div>
                            <label className="text-sm font-semibold text-slate-700">
                                Amount
                            </label>

                            <input
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                                value={
                                    paymentForm.amount
                                }
                                onChange={(event) =>
                                    setPaymentForm(
                                        (current) => ({
                                            ...current,
                                            amount:
                                                event
                                                    .target
                                                    .value,
                                        })
                                    )
                                }
                                className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>

                        <div>
                            <label className="text-sm font-semibold text-slate-700">
                                Payment Date
                            </label>

                            <input
                                type="date"
                                required
                                value={
                                    paymentForm.payment_date
                                }
                                onChange={(event) =>
                                    setPaymentForm(
                                        (current) => ({
                                            ...current,
                                            payment_date:
                                                event
                                                    .target
                                                    .value,
                                        })
                                    )
                                }
                                className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>

                        <div>
                            <label className="text-sm font-semibold text-slate-700">
                                Payment Method
                            </label>

                            <select
                                value={
                                    paymentForm.payment_method
                                }
                                onChange={(event) =>
                                    setPaymentForm(
                                        (current) => ({
                                            ...current,
                                            payment_method:
                                                event
                                                    .target
                                                    .value,
                                        })
                                    )
                                }
                                className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                {PAYMENT_METHODS.map(
                                    (method) => (
                                        <option
                                            key={
                                                method.value
                                            }
                                            value={
                                                method.value
                                            }
                                        >
                                            {
                                                method.label
                                            }
                                        </option>
                                    )
                                )}
                            </select>
                        </div>

                        <div>
                            <label className="text-sm font-semibold text-slate-700">
                                Notes
                            </label>

                            <input
                                type="text"
                                maxLength={2000}
                                value={
                                    paymentForm.notes
                                }
                                onChange={(event) =>
                                    setPaymentForm(
                                        (current) => ({
                                            ...current,
                                            notes:
                                                event
                                                    .target
                                                    .value,
                                        })
                                    )
                                }
                                placeholder="Optional"
                                className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row md:col-span-2">
                            <button
                                type="submit"
                                disabled={
                                    recordingPayment
                                }
                                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:bg-slate-300"
                            >
                                {recordingPayment
                                    ? "Recording..."
                                    : "Confirm Payment"}
                            </button>

                            <button
                                type="button"
                                onClick={closePayment}
                                disabled={
                                    recordingPayment
                                }
                                className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </section>
            )}


            {receipt && (
                <section className="min-w-0 rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm sm:p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-sm font-semibold uppercase tracking-wide text-emerald-600">
                                Payment Evidence
                            </p>

                            <h2 className="mt-1 break-all text-xl font-bold text-slate-900">
                                {
                                    receipt.payment_reference
                                }
                            </h2>
                        </div>

                        <button
                            type="button"
                            onClick={() =>
                                setReceipt(null)
                            }
                            className="text-sm font-semibold text-slate-500 hover:text-slate-800"
                        >
                            Close
                        </button>
                    </div>

                    <div className="mt-5 grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Resident
                            </p>
                            <p className="mt-1 break-words text-sm font-semibold text-slate-900">
                                {
                                    receipt.resident_name
                                }
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Invoice
                            </p>
                            <p className="mt-1 break-all text-sm font-semibold text-slate-900">
                                {
                                    receipt.invoice_number
                                }
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Amount
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {money(
                                    receipt.payment_amount
                                )}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Payment Date
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {dateText(
                                    receipt.payment_date
                                )}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Method
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {
                                    receipt.payment_method
                                }
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Received By
                            </p>
                            <p className="mt-1 break-words text-sm font-semibold text-slate-900">
                                {receipt.received_by ||
                                    "-"}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Invoice Total
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {money(
                                    receipt.invoice_total
                                )}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Total Paid
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {money(
                                    receipt.invoice_amount_paid
                                )}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Remaining Balance
                            </p>
                            <p className="mt-1 text-sm font-semibold text-slate-900">
                                {money(
                                    receipt.invoice_balance_due
                                )}
                            </p>
                        </div>
                    </div>
                </section>
            )}
        </div>
    );
}


export default Billing;