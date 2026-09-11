import { useCallback, useEffect, useMemo, useState } from "react";
import api from "../services/api";

const STATUS_OPTIONS = ["", "PENDING", "SENT", "FAILED", "SKIPPED"];

function FamilyMessages() {
    const [messages, setMessages] = useState([]);
    const [summary, setSummary] = useState({
        total: 0,
        pending: 0,
        sent: 0,
        failed: 0,
        skipped: 0,
    });
    const [filters, setFilters] = useState({
        status: "",
        search: "",
    });
    const [loading, setLoading] = useState(true);
    const [retryingId, setRetryingId] = useState(null);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const loadMessages = useCallback(async () => {
        try {
            setLoading(true);
            setError("");

            const params = {
                per_page: 100,
            };

            if (filters.status) {
                params.status = filters.status;
            }

            if (filters.search.trim()) {
                params.search = filters.search.trim();
            }

            const response = await api.get("/family-messages", { params });
            const payload = response.data || {};

            setSummary(payload.summary || {
                total: 0,
                pending: 0,
                sent: 0,
                failed: 0,
                skipped: 0,
            });

            setMessages(
                Array.isArray(payload.messages?.data)
                    ? payload.messages.data
                    : []
            );
        } catch (err) {
            console.error("Family messages load error:", err);
            setError(
                err?.response?.data?.message ||
                "Unable to load family communication history."
            );
        } finally {
            setLoading(false);
        }
    }, [filters.search, filters.status]);

    useEffect(() => {
        const timer = setTimeout(() => {
            loadMessages();
        }, 250);

        return () => clearTimeout(timer);
    }, [loadMessages]);

    const retryMessage = async (item) => {
        try {
            setRetryingId(item.id);
            setError("");
            setSuccess("");

            const response = await api.post(
                `/family-messages/${item.id}/retry`
            );

            setSuccess(
                response.data?.message ||
                "Family message retry completed."
            );

            await loadMessages();
        } catch (err) {
            console.error("Family message retry error:", err);
            setError(
                err?.response?.data?.message ||
                "Unable to retry this family message."
            );
        } finally {
            setRetryingId(null);
        }
    };

    const hasFilters = useMemo(
        () => Boolean(filters.status || filters.search.trim()),
        [filters]
    );

    return (
        <div className="min-w-0 space-y-6">
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                            Family Communication
                        </p>
                        <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                            WhatsApp Message History
                        </h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            Review medication and meal messages prepared for resident family contacts.
                            Failed delivery attempts can be retried once a live WhatsApp provider is configured.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={loadMessages}
                        disabled={loading}
                        className="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {loading ? "Refreshing..." : "Refresh"}
                    </button>
                </div>
            </section>

            <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <SummaryCard label="Total" value={summary.total} />
                <SummaryCard label="Pending" value={summary.pending} />
                <SummaryCard label="Sent" value={summary.sent} />
                <SummaryCard label="Failed" value={summary.failed} />
                <SummaryCard label="Skipped" value={summary.skipped} />
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div className="grid gap-3 md:grid-cols-[220px_minmax(0,1fr)_auto]">
                    <label className="block">
                        <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Status
                        </span>
                        <select
                            value={filters.status}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    status: event.target.value,
                                }))
                            }
                            className="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            {STATUS_OPTIONS.map((status) => (
                                <option key={status || "ALL"} value={status}>
                                    {status || "All statuses"}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="block">
                        <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Search
                        </span>
                        <input
                            type="search"
                            value={filters.search}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    search: event.target.value,
                                }))
                            }
                            placeholder="Resident, recipient, number or message"
                            className="min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>

                    <div className="flex items-end">
                        <button
                            type="button"
                            disabled={!hasFilters}
                            onClick={() =>
                                setFilters({
                                    status: "",
                                    search: "",
                                })
                            }
                            className="min-h-11 w-full rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 md:w-auto"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            {error && (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {error}
                </div>
            )}

            {success && (
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {success}
                </div>
            )}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 px-5 py-4">
                    <h2 className="font-bold text-slate-900">Communication Audit</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Recipient, delivery state and provider evidence for family messages.
                    </p>
                </div>

                {loading ? (
                    <div className="p-10 text-center text-sm text-slate-500">
                        Loading family communication history...
                    </div>
                ) : messages.length === 0 ? (
                    <div className="p-10 text-center">
                        <h3 className="font-semibold text-slate-800">
                            No family messages found
                        </h3>
                        <p className="mt-2 text-sm text-slate-500">
                            Messages will appear here after eligible medication and meal completion.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="space-y-3 p-4 lg:hidden">
                            {messages.map((item) => (
                                <MessageCard
                                    key={item.id}
                                    item={item}
                                    retrying={retryingId === item.id}
                                    onRetry={retryMessage}
                                />
                            ))}
                        </div>

                        <div className="hidden overflow-x-auto lg:block">
                            <table className="w-full min-w-[1050px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3 font-semibold">Resident</th>
                                        <th className="px-5 py-3 font-semibold">Recipient</th>
                                        <th className="px-5 py-3 font-semibold">Message</th>
                                        <th className="px-5 py-3 font-semibold">Status</th>
                                        <th className="px-5 py-3 font-semibold">Provider</th>
                                        <th className="px-5 py-3 font-semibold">Attempts</th>
                                        <th className="px-5 py-3 font-semibold">Time</th>
                                        <th className="px-5 py-3 font-semibold">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {messages.map((item) => (
                                        <tr key={item.id} className="align-top">
                                            <td className="px-5 py-4 font-semibold text-slate-800">
                                                {item.resident?.full_name || `Resident #${item.resident_id}`}
                                            </td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-slate-800">
                                                    {item.recipient_name}
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {item.recipient_number}
                                                </p>
                                            </td>
                                            <td className="max-w-md px-5 py-4 text-slate-600">
                                                <p className="leading-6">{item.message}</p>
                                                {item.failure_reason && (
                                                    <p className="mt-2 text-xs font-medium text-red-600">
                                                        {item.failure_reason}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                <StatusBadge status={item.status} />
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">
                                                {item.provider || "—"}
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">
                                                {item.attempt_count ?? 0}
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">
                                                <Timestamp item={item} />
                                            </td>
                                            <td className="px-5 py-4">
                                                {item.status === "FAILED" ? (
                                                    <RetryButton
                                                        item={item}
                                                        retrying={retryingId === item.id}
                                                        onRetry={retryMessage}
                                                    />
                                                ) : (
                                                    <span className="text-xs text-slate-400">No action</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </section>
        </div>
    );
}

function SummaryCard({ label, value }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>
            <p className="mt-2 text-2xl font-bold text-slate-900">
                {value ?? 0}
            </p>
        </div>
    );
}

function MessageCard({ item, retrying, onRetry }) {
    return (
        <article className="rounded-2xl border border-slate-200 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-bold text-slate-900">
                        {item.resident?.full_name || `Resident #${item.resident_id}`}
                    </p>
                    <p className="mt-1 text-sm text-slate-600">
                        {item.recipient_name} · {item.recipient_number}
                    </p>
                </div>
                <StatusBadge status={item.status} />
            </div>

            <p className="mt-4 text-sm leading-6 text-slate-700">
                {item.message}
            </p>

            {item.failure_reason && (
                <div className="mt-3 rounded-xl bg-red-50 p-3 text-xs leading-5 text-red-700">
                    {item.failure_reason}
                </div>
            )}

            <div className="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-500">
                <div>
                    <span className="block font-semibold text-slate-700">Provider</span>
                    {item.provider || "—"}
                </div>
                <div>
                    <span className="block font-semibold text-slate-700">Attempts</span>
                    {item.attempt_count ?? 0}
                </div>
                <div className="col-span-2">
                    <span className="block font-semibold text-slate-700">Time</span>
                    <Timestamp item={item} />
                </div>
            </div>

            {item.status === "FAILED" && (
                <div className="mt-4">
                    <RetryButton
                        item={item}
                        retrying={retrying}
                        onRetry={onRetry}
                    />
                </div>
            )}
        </article>
    );
}

function RetryButton({ item, retrying, onRetry }) {
    return (
        <button
            type="button"
            onClick={() => onRetry(item)}
            disabled={retrying}
            className="inline-flex min-h-10 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
            {retrying ? "Retrying..." : "Retry"}
        </button>
    );
}

function StatusBadge({ status }) {
    const value = String(status || "PENDING").toUpperCase();

    const classes = {
        SENT: "bg-emerald-100 text-emerald-700",
        FAILED: "bg-red-100 text-red-700",
        PENDING: "bg-amber-100 text-amber-700",
        SKIPPED: "bg-slate-100 text-slate-600",
    };

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${
                classes[value] || classes.PENDING
            }`}
        >
            {value}
        </span>
    );
}

function Timestamp({ item }) {
    const raw =
        item.sent_at ||
        item.failed_at ||
        item.created_at;

    if (!raw) {
        return "—";
    }

    const date = new Date(raw);

    if (Number.isNaN(date.getTime())) {
        return raw;
    }

    return date.toLocaleString();
}

export default FamilyMessages;
