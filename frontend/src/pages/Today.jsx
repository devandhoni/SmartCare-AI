import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../services/api";

function Today() {
    const navigate = useNavigate();

    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");
    const [lastUpdated, setLastUpdated] = useState(null);

    const loadToday = useCallback(async (silent = false) => {
        if (silent) {
            setRefreshing(true);
        } else {
            setLoading(true);
        }

        setError("");

        try {
            const response = await api.get("/today");

            setData(response.data);
            setLastUpdated(new Date());
        } catch (err) {
            console.error("Failed to load Today dashboard:", err);

            setError(
                err?.response?.data?.message ||
                    "Unable to load today's care overview."
            );
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        loadToday();
    }, [loadToday]);

    const summary = data?.summary ?? {};

    const medicationRounds = data?.medication_rounds ?? {
        AM: { total: 0, items: [] },
        PM: { total: 0, items: [] },
        NIGHT: { total: 0, items: [] },
    };

    const weeklyVitals = data?.due_checks?.weekly_vitals ?? [];
    const monthlyGlucose = data?.due_checks?.monthly_glucose ?? [];
    const tasks = data?.tasks ?? [];
    const homeLeave = data?.home_leave ?? [];

    const overdueHomeLeave = useMemo(
        () => homeLeave.filter((leave) => leave.overdue),
        [homeLeave]
    );

    if (loading) {
        return (
            <div className="flex min-h-[420px] items-center justify-center">
                <div className="text-center">
                    <div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-slate-700" />
                    <p className="mt-4 text-sm font-medium text-slate-500">
                        Loading today's care overview...
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="w-full min-w-0 space-y-6">
            <section className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">
                        Daily Care
                    </p>

                    <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                        Today
                    </h1>

                    <p className="mt-2 text-sm text-slate-500">
                        {formatDashboardDate(data?.date)}
                    </p>

                    {lastUpdated && (
                        <p className="mt-1 text-xs text-slate-400">
                            Last updated {formatTime(lastUpdated)}
                        </p>
                    )}
                </div>

                <button
                    type="button"
                    onClick={() => loadToday(true)}
                    disabled={refreshing}
                    className="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                >
                    {refreshing ? "Refreshing..." : "Refresh"}
                </button>
            </section>

            {error && (
                <div className="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p className="font-semibold text-red-800">
                        Unable to load Today
                    </p>

                    <p className="mt-1 text-sm text-red-700">{error}</p>

                    <button
                        type="button"
                        onClick={() => loadToday()}
                        className="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Try Again
                    </button>
                </div>
            )}

            <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <SummaryCard
                    label="Active Residents"
                    value={summary.active_residents ?? 0}
                    description="Currently in care"
                />

                <SummaryCard
                    label="Pending Tasks"
                    value={summary.pending_tasks ?? 0}
                    description={
                        summary.overdue_tasks
                            ? `${summary.overdue_tasks} overdue`
                            : "No overdue tasks"
                    }
                    attention={(summary.overdue_tasks ?? 0) > 0}
                />

                <SummaryCard
                    label="Critical Alerts"
                    value={summary.critical_alerts ?? 0}
                    description="Open critical alerts"
                    attention={(summary.critical_alerts ?? 0) > 0}
                />

                <SummaryCard
                    label="Checks Due"
                    value={
                        (summary.weekly_vitals_due ?? 0) +
                        (summary.monthly_glucose_due ?? 0)
                    }
                    description="Vitals and glucose"
                    attention={
                        (summary.weekly_vitals_due ?? 0) +
                            (summary.monthly_glucose_due ?? 0) >
                        0
                    }
                />
            </section>

            <section className="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                <div className="min-w-0 space-y-6">
                    <Panel
                        title="Medication Rounds"
                        description="Today's medication schedule for active residents."
                        action={
                            <button
                                type="button"
                                onClick={() => navigate("/medication")}
                                className="text-sm font-semibold text-slate-700 hover:text-slate-950"
                            >
                                Medication →
                            </button>
                        }
                    >
                        <div className="grid gap-4 lg:grid-cols-3">
                            <MedicationRound
                                title="AM"
                                meal="Breakfast"
                                round={medicationRounds.AM}
                                onResident={(id) =>
                                    navigate(`/residents/${id}`)
                                }
                            />

                            <MedicationRound
                                title="PM"
                                meal="Lunch"
                                round={medicationRounds.PM}
                                onResident={(id) =>
                                    navigate(`/residents/${id}`)
                                }
                            />

                            <MedicationRound
                                title="Night"
                                meal="Dinner"
                                round={medicationRounds.NIGHT}
                                onResident={(id) =>
                                    navigate(`/residents/${id}`)
                                }
                            />
                        </div>
                    </Panel>

                    <Panel
                        title="Due Checks"
                        description="Routine resident checks still outstanding."
                    >
                        <div className="grid gap-4 lg:grid-cols-2">
                            <DueCheckCard
                                title="Weekly Vitals"
                                count={weeklyVitals.length}
                                items={weeklyVitals}
                                buttonLabel="Open Weekly Vitals"
                                onOpen={() => navigate("/weekly-vitals")}
                                onResident={(id) =>
                                    navigate(`/residents/${id}`)
                                }
                            />

                            <DueCheckCard
                                title="Monthly Glucose"
                                count={monthlyGlucose.length}
                                items={monthlyGlucose}
                                buttonLabel="Open Monthly Glucose"
                                onOpen={() => navigate("/monthly-glucose")}
                                onResident={(id) =>
                                    navigate(`/residents/${id}`)
                                }
                            />
                        </div>
                    </Panel>

                    <Panel
                        title="Resident Movement"
                        description="Residents currently away on home leave."
                        action={
                            <button
                                type="button"
                                onClick={() => navigate("/home-leave")}
                                className="text-sm font-semibold text-slate-700 hover:text-slate-950"
                            >
                                Home Leave →
                            </button>
                        }
                    >
                        {homeLeave.length === 0 ? (
                            <EmptyState text="No residents are currently recorded as away on home leave." />
                        ) : (
                            <div className="space-y-3">
                                {homeLeave.map((leave) => (
                                    <div
                                        key={leave.id}
                                        className="rounded-xl border border-slate-200 p-4"
                                    >
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="min-w-0">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        navigate(
                                                            `/residents/${leave.resident_id}`
                                                        )
                                                    }
                                                    className="text-left font-semibold text-slate-900 hover:underline"
                                                >
                                                    {leave.resident}
                                                </button>

                                                <p className="mt-1 text-sm text-slate-500">
                                                    {leave.reason ||
                                                        "Home leave"}
                                                    {leave.destination
                                                        ? ` • ${leave.destination}`
                                                        : ""}
                                                </p>

                                                <p className="mt-2 text-xs text-slate-500">
                                                    Expected return:{" "}
                                                    {formatDateTime(
                                                        leave.expected_return_at
                                                    )}
                                                </p>
                                            </div>

                                            <StatusBadge
                                                attention={leave.overdue}
                                                text={
                                                    leave.overdue
                                                        ? "Return Overdue"
                                                        : "Away"
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {overdueHomeLeave.length > 0 && (
                            <p className="mt-4 text-sm font-semibold text-red-700">
                                {overdueHomeLeave.length} resident
                                {overdueHomeLeave.length === 1 ? "" : "s"}{" "}
                                overdue for return.
                            </p>
                        )}
                    </Panel>
                </div>

                <div className="min-w-0 space-y-6">
                    <Panel
                        title="Needs Attention"
                        description="Outstanding nurse tasks and urgent work."
                        action={
                            <button
                                type="button"
                                onClick={() => navigate("/tasks")}
                                className="text-sm font-semibold text-slate-700 hover:text-slate-950"
                            >
                                All Tasks →
                            </button>
                        }
                    >
                        {tasks.length === 0 ? (
                            <EmptyState text="No pending nurse tasks." />
                        ) : (
                            <div className="space-y-3">
                                {tasks.slice(0, 8).map((task) => (
                                    <button
                                        type="button"
                                        key={task.id}
                                        onClick={() =>
                                            task.resident_id &&
                                            navigate(
                                                `/residents/${task.resident_id}/clinical-dashboard`
                                            )
                                        }
                                        className="w-full rounded-xl border border-slate-200 p-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-semibold text-slate-900">
                                                    {task.task_name}
                                                </p>

                                                <p className="mt-1 text-sm text-slate-500">
                                                    {task.resident ||
                                                        "Resident"}
                                                </p>
                                            </div>

                                            <StatusBadge
                                                attention={
                                                    task.overdue ||
                                                    String(
                                                        task.priority
                                                    ).toUpperCase() === "URGENT"
                                                }
                                                text={
                                                    task.overdue
                                                        ? "Overdue"
                                                        : task.priority ||
                                                          "Pending"
                                                }
                                            />
                                        </div>

                                        {task.scheduled_time && (
                                            <p className="mt-3 text-xs text-slate-400">
                                                Due{" "}
                                                {formatDateTime(
                                                    task.scheduled_time
                                                )}
                                            </p>
                                        )}
                                    </button>
                                ))}
                            </div>
                        )}
                    </Panel>

                    <Panel
                        title="Quick Actions"
                        description="Open common daily care workflows."
                    >
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <QuickAction
                                title="Residents"
                                description="Open resident profiles"
                                onClick={() => navigate("/residents")}
                            />

                            <QuickAction
                                title="Nurse Tasks"
                                description="Review pending work"
                                onClick={() => navigate("/tasks")}
                            />

                            <QuickAction
                                title="Weekly Vitals"
                                description={`${weeklyVitals.length} due`}
                                onClick={() => navigate("/weekly-vitals")}
                            />

                            <QuickAction
                                title="Monthly Glucose"
                                description={`${monthlyGlucose.length} due`}
                                onClick={() => navigate("/monthly-glucose")}
                            />

                            <QuickAction
                                title="Home Leave"
                                description={`${homeLeave.length} currently away`}
                                onClick={() => navigate("/home-leave")}
                            />

                            <QuickAction
                                title="Care Records"
                                description="Open daily care records"
                                onClick={() => navigate("/care-records")}
                            />
                        </div>
                    </Panel>
                </div>
            </section>
        </div>
    );
}

function SummaryCard({
    label,
    value,
    description,
    attention = false,
}) {
    return (
        <div
            className={`rounded-2xl border p-4 shadow-sm ${
                attention
                    ? "border-amber-200 bg-amber-50"
                    : "border-slate-200 bg-white"
            }`}
        >
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="mt-2 text-3xl font-bold text-slate-900">
                {value}
            </p>

            <p
                className={`mt-1 text-xs ${
                    attention
                        ? "font-medium text-amber-700"
                        : "text-slate-400"
                }`}
            >
                {description}
            </p>
        </div>
    );
}

function Panel({ title, description, action, children }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="font-bold text-slate-900">{title}</h2>

                    {description && (
                        <p className="mt-1 text-sm text-slate-500">
                            {description}
                        </p>
                    )}
                </div>

                {action}
            </div>

            <div className="p-5">{children}</div>
        </section>
    );
}

function MedicationRound({ title, meal, round, onResident }) {
    const items = round?.items ?? [];

    const completed = items.filter(
        (item) => String(item.status).toUpperCase() === "COMPLETED"
    ).length;

    return (
        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-bold text-slate-900">{title}</p>
                    <p className="text-xs text-slate-500">{meal}</p>
                </div>

                <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 shadow-sm">
                    {completed}/{items.length}
                </span>
            </div>

            {items.length === 0 ? (
                <p className="mt-5 text-sm text-slate-400">
                    No medication scheduled.
                </p>
            ) : (
                <div className="mt-4 space-y-2">
                    {items.slice(0, 5).map((item) => (
                        <button
                            type="button"
                            key={item.resident_medication_id}
                            onClick={() => onResident(item.resident_id)}
                            className="w-full rounded-lg bg-white p-3 text-left shadow-sm transition hover:bg-slate-100"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <p className="truncate text-sm font-semibold text-slate-800">
                                    {item.resident}
                                </p>

                                <span
                                    className={`text-xs font-semibold ${
                                        String(
                                            item.status
                                        ).toUpperCase() === "COMPLETED"
                                            ? "text-emerald-700"
                                            : "text-amber-700"
                                    }`}
                                >
                                    {String(
                                        item.status
                                    ).toUpperCase() === "COMPLETED"
                                        ? "Done"
                                        : "Pending"}
                                </span>
                            </div>

                            <p className="mt-1 truncate text-xs text-slate-500">
                                {item.medicine}
                                {item.scheduled_time
                                    ? ` • ${formatMedicationTime(
                                          item.scheduled_time
                                      )}`
                                    : ""}
                            </p>
                        </button>
                    ))}

                    {items.length > 5 && (
                        <p className="pt-1 text-xs font-medium text-slate-500">
                            +{items.length - 5} more scheduled
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

function DueCheckCard({
    title,
    count,
    items,
    buttonLabel,
    onOpen,
    onResident,
}) {
    return (
        <div className="rounded-xl border border-slate-200 p-4">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-semibold text-slate-900">{title}</h3>

                <span
                    className={`rounded-full px-2.5 py-1 text-xs font-bold ${
                        count > 0
                            ? "bg-amber-100 text-amber-800"
                            : "bg-emerald-100 text-emerald-700"
                    }`}
                >
                    {count} due
                </span>
            </div>

            {items.length === 0 ? (
                <p className="mt-4 text-sm text-slate-500">
                    All active residents are up to date.
                </p>
            ) : (
                <div className="mt-4 space-y-2">
                    {items.slice(0, 5).map((item) => (
                        <button
                            type="button"
                            key={item.resident_id}
                            onClick={() => onResident(item.resident_id)}
                            className="block w-full rounded-lg bg-slate-50 px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        >
                            {item.resident}
                        </button>
                    ))}

                    {items.length > 5 && (
                        <p className="text-xs text-slate-400">
                            +{items.length - 5} more residents
                        </p>
                    )}
                </div>
            )}

            <button
                type="button"
                onClick={onOpen}
                className="mt-4 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                {buttonLabel}
            </button>
        </div>
    );
}

function QuickAction({ title, description, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-xl border border-slate-200 p-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
        >
            <p className="font-semibold text-slate-900">{title}</p>
            <p className="mt-1 text-xs text-slate-500">{description}</p>
        </button>
    );
}

function StatusBadge({ text, attention = false }) {
    return (
        <span
            className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${
                attention
                    ? "bg-red-100 text-red-700"
                    : "bg-slate-100 text-slate-600"
            }`}
        >
            {text}
        </span>
    );
}

function EmptyState({ text }) {
    return (
        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
            <p className="text-sm text-slate-500">{text}</p>
        </div>
    );
}

function formatDashboardDate(value) {
    if (!value) {
        return "Today's care overview";
    }

    const date = new Date(`${value}T00:00:00`);

    return date.toLocaleDateString(undefined, {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

function formatDateTime(value) {
    if (!value) {
        return "Not specified";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        day: "numeric",
        month: "short",
        hour: "numeric",
        minute: "2-digit",
    });
}

function formatMedicationTime(value) {
    if (!value) {
        return "";
    }

    const parts = String(value).split(":");

    if (parts.length < 2) {
        return value;
    }

    const date = new Date();

    date.setHours(
        Number(parts[0]),
        Number(parts[1]),
        Number(parts[2] || 0),
        0
    );

    return date.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit",
    });
}

function formatTime(date) {
    return date.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit",
    });
}

export default Today;