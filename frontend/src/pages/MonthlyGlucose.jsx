import { useEffect, useMemo, useState } from "react";
import api from "../services/api";

const MEASUREMENT_TYPES = [
    { value: "FASTING", label: "Fasting" },
    { value: "BEFORE_MEAL", label: "Before Meal" },
    { value: "AFTER_MEAL", label: "After Meal" },
    { value: "RANDOM", label: "Random" },
];

function localDateTimeValue() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}

function readApiError(error, fallback) {
    const data = error?.response?.data;

    if (data?.message) {
        return data.message;
    }

    if (data?.errors) {
        const firstError = Object.values(data.errors)?.[0];

        if (Array.isArray(firstError) && firstError.length > 0) {
            return firstError[0];
        }
    }

    return fallback;
}

function formatDateTime(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("en-MY", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function measurementLabel(value) {
    return (
        MEASUREMENT_TYPES.find((item) => item.value === value)?.label ||
        value ||
        "—"
    );
}

function MonthlyGlucose() {
    const today = new Date();

    const [residents, setResidents] = useState([]);
    const [checks, setChecks] = useState([]);

    const [month, setMonth] = useState(today.getMonth() + 1);
    const [year, setYear] = useState(today.getFullYear());

    const [search, setSearch] = useState("");

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [selectedResident, setSelectedResident] = useState(null);

    const [form, setForm] = useState({
        blood_glucose: "",
        glucose_measurement_type: "FASTING",
        glucose_notes: "",
        recorded_at: localDateTimeValue(),
    });

    useEffect(() => {
        loadPage();
    }, [month, year]);

    async function loadPage() {
        try {
            setLoading(true);
            setError("");

            const [residentResponse, glucoseResponse] = await Promise.all([
                api.get("/residents"),
                api.get("/monthly-glucose-checks", {
                    params: {
                        month,
                        year,
                    },
                }),
            ]);

            const residentData =
                residentResponse.data?.residents ??
                residentResponse.data?.data ??
                residentResponse.data ??
                [];

            const checkData =
                glucoseResponse.data?.checks ??
                glucoseResponse.data?.data ??
                glucoseResponse.data ??
                [];

            setResidents(
                Array.isArray(residentData)
                    ? residentData.filter(
                          (resident) =>
                              String(resident.status).toLowerCase() === "active"
                      )
                    : []
            );

            setChecks(Array.isArray(checkData) ? checkData : []);
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to load monthly glucose information."
                )
            );
        } finally {
            setLoading(false);
        }
    }

    const checkByResident = useMemo(() => {
        const map = new Map();

        checks.forEach((check) => {
            map.set(String(check.resident_id), check);
        });

        return map;
    }, [checks]);

    const rows = useMemo(() => {
        const term = search.trim().toLowerCase();

        return residents.filter((resident) => {
            if (!term) {
                return true;
            }

            return (
                String(resident.full_name || "")
                    .toLowerCase()
                    .includes(term) ||
                String(resident.ic_number || "")
                    .toLowerCase()
                    .includes(term)
            );
        });
    }, [residents, search]);

    const completedCount = residents.filter((resident) =>
        checkByResident.has(String(resident.id))
    ).length;

    const pendingCount = Math.max(
        residents.length - completedCount,
        0
    );

    function openRecordForm(resident) {
        setSelectedResident(resident);

        setForm({
            blood_glucose: "",
            glucose_measurement_type: "FASTING",
            glucose_notes: "",
            recorded_at: localDateTimeValue(),
        });

        setError("");
        setSuccess("");
    }

    function closeRecordForm() {
        if (saving) {
            return;
        }

        setSelectedResident(null);
    }

    function change(field, value) {
        setForm((current) => ({
            ...current,
            [field]: value,
        }));
    }

    async function saveCheck(event) {
        event.preventDefault();

        if (!selectedResident) {
            return;
        }

        try {
            setSaving(true);
            setError("");
            setSuccess("");

            await api.post(
                `/residents/${selectedResident.id}/monthly-glucose-checks`,
                {
                    blood_glucose: Number(form.blood_glucose),
                    glucose_measurement_type:
                        form.glucose_measurement_type,
                    glucose_notes:
                        form.glucose_notes.trim() || null,
                    recorded_at: form.recorded_at,
                }
            );

            setSelectedResident(null);

            setSuccess(
                `Monthly glucose check recorded for ${selectedResident.full_name}.`
            );

            await loadPage();
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to record monthly glucose check."
                )
            );
        } finally {
            setSaving(false);
        }
    }

    const monthName = new Date(
        Number(year),
        Number(month) - 1,
        1
    ).toLocaleString("en-MY", {
        month: "long",
        year: "numeric",
    });

    return (
        <div className="space-y-6">
            {/* Header */}

            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-blue-600">
                        Care Operations
                    </p>

                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Monthly Glucose Checks
                    </h1>

                    <p className="mt-2 text-slate-500">
                        Track the monthly blood glucose check for every
                        active resident.
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Current View
                    </p>

                    <p className="mt-1 font-bold text-slate-800">
                        {monthName}
                    </p>
                </div>
            </div>

            {/* Messages */}

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

            {/* Summary Cards */}

            <div className="grid gap-4 md:grid-cols-3">
                <SummaryCard
                    title="Active Residents"
                    value={residents.length}
                    description="Residents currently requiring care"
                />

                <SummaryCard
                    title="Completed"
                    value={completedCount}
                    description={`Checks completed for ${monthName}`}
                />

                <SummaryCard
                    title="Pending"
                    value={pendingCount}
                    description="Residents still requiring a check"
                />
            </div>

            {/* Filters */}

            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="md:col-span-2">
                        <label className="mb-2 block text-sm font-semibold text-slate-700">
                            Search Resident
                        </label>

                        <input
                            type="text"
                            value={search}
                            onChange={(event) =>
                                setSearch(event.target.value)
                            }
                            placeholder="Search by resident name or IC number..."
                            className="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-semibold text-slate-700">
                            Month
                        </label>

                        <select
                            value={month}
                            onChange={(event) =>
                                setMonth(Number(event.target.value))
                            }
                            className="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            {Array.from({ length: 12 }).map(
                                (_, index) => (
                                    <option
                                        key={index + 1}
                                        value={index + 1}
                                    >
                                        {new Date(
                                            2026,
                                            index,
                                            1
                                        ).toLocaleString("en-MY", {
                                            month: "long",
                                        })}
                                    </option>
                                )
                            )}
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-semibold text-slate-700">
                            Year
                        </label>

                        <select
                            value={year}
                            onChange={(event) =>
                                setYear(Number(event.target.value))
                            }
                            className="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            {Array.from({ length: 7 }).map(
                                (_, index) => {
                                    const optionYear =
                                        today.getFullYear() -
                                        3 +
                                        index;

                                    return (
                                        <option
                                            key={optionYear}
                                            value={optionYear}
                                        >
                                            {optionYear}
                                        </option>
                                    );
                                }
                            )}
                        </select>
                    </div>
                </div>
            </div>

            {/* Checklist */}

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 px-6 py-5">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-slate-800">
                                Resident Checklist
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                One monthly glucose record is allowed per
                                resident per calendar month.
                            </p>
                        </div>

                        <div className="text-sm font-semibold text-slate-500">
                            {rows.length} resident
                            {rows.length === 1 ? "" : "s"}
                        </div>
                    </div>
                </div>

                {loading ? (
                    <div className="p-10 text-center text-slate-500">
                        Loading monthly glucose checklist...
                    </div>
                ) : rows.length === 0 ? (
                    <div className="p-10 text-center">
                        <p className="font-semibold text-slate-700">
                            No active residents found.
                        </p>

                        <p className="mt-1 text-sm text-slate-500">
                            Try changing the search criteria.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="divide-y divide-slate-100 lg:hidden">
                            {rows.map((resident) => {
                                const check = checkByResident.get(String(resident.id));

                                return (
                                    <div key={resident.id} className="p-5">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <h3 className="font-bold text-slate-800">
                                                    {resident.full_name}
                                                </h3>

                                                <p className="mt-1 break-words text-xs text-slate-400">
                                                    Resident #{resident.id}
                                                    {resident.ic_number ? ` · ${resident.ic_number}` : ""}
                                                </p>
                                            </div>

                                            {check ? (
                                                <span className="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                    Completed
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                    Pending
                                                </span>
                                            )}
                                        </div>

                                        <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                                            <MobileInfo
                                                label="Glucose"
                                                value={check ? `${check.blood_glucose} mmol/L` : "—"}
                                            />
                                            <MobileInfo
                                                label="Measurement"
                                                value={
                                                    check
                                                        ? measurementLabel(check.glucose_measurement_type)
                                                        : "—"
                                                }
                                            />
                                            <div className="col-span-2">
                                                <MobileInfo
                                                    label="Recorded"
                                                    value={
                                                        check
                                                            ? formatDateTime(check.recorded_at)
                                                            : "—"
                                                    }
                                                />
                                            </div>
                                        </div>

                                        {!check && (
                                            <button
                                                type="button"
                                                onClick={() => openRecordForm(resident)}
                                                className="mt-4 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                                            >
                                                Record Check
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <div className="hidden overflow-x-auto lg:block">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <TableHead>Resident</TableHead>
                                    <TableHead>Glucose</TableHead>
                                    <TableHead>Measurement</TableHead>
                                    <TableHead>Recorded</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead align="right">
                                        Action
                                    </TableHead>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-slate-100 bg-white">
                                {rows.map((resident) => {
                                    const check =
                                        checkByResident.get(
                                            String(resident.id)
                                        );

                                    return (
                                        <tr
                                            key={resident.id}
                                            className="transition hover:bg-slate-50"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="font-semibold text-slate-800">
                                                    {resident.full_name}
                                                </div>

                                                <div className="mt-1 text-xs text-slate-400">
                                                    Resident #
                                                    {resident.id}
                                                    {resident.ic_number
                                                        ? ` · ${resident.ic_number}`
                                                        : ""}
                                                </div>
                                            </td>

                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                {check ? (
                                                    <span className="font-bold text-slate-800">
                                                        {
                                                            check.blood_glucose
                                                        }{" "}
                                                        mmol/L
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400">
                                                        —
                                                    </span>
                                                )}
                                            </td>

                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                                {check
                                                    ? measurementLabel(
                                                          check.glucose_measurement_type
                                                      )
                                                    : "—"}
                                            </td>

                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                                {check
                                                    ? formatDateTime(
                                                          check.recorded_at
                                                      )
                                                    : "—"}
                                            </td>

                                            <td className="whitespace-nowrap px-6 py-4">
                                                {check ? (
                                                    <span className="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                        Completed
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                        Pending
                                                    </span>
                                                )}
                                            </td>

                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                {check ? (
                                                    <span className="text-sm font-medium text-slate-400">
                                                        Recorded
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            openRecordForm(
                                                                resident
                                                            )
                                                        }
                                                        className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                    >
                                                        Record Check
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                        </div>
                    </>
                )}
            </div>

            {/* Record Modal */}

            {selectedResident && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                    <div className="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
                        <div className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                    Monthly Glucose Check
                                </p>

                                <h2 className="mt-1 text-xl font-bold text-slate-800">
                                    {selectedResident.full_name}
                                </h2>

                                <p className="mt-1 text-sm text-slate-500">
                                    Record the resident's glucose reading
                                    for {monthName}.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={closeRecordForm}
                                disabled={saving}
                                className="rounded-lg px-3 py-1.5 text-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                            >
                                ×
                            </button>
                        </div>

                        <form
                            onSubmit={saveCheck}
                            className="space-y-5 p-6"
                        >
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Blood Glucose
                                    <span className="text-red-500">
                                        {" "}
                                        *
                                    </span>
                                </label>

                                <div className="relative">
                                    <input
                                        type="number"
                                        min="1"
                                        max="30"
                                        step="0.01"
                                        required
                                        value={form.blood_glucose}
                                        onChange={(event) =>
                                            change(
                                                "blood_glucose",
                                                event.target.value
                                            )
                                        }
                                        placeholder="Example: 6.8"
                                        className="w-full rounded-xl border border-slate-300 px-4 py-3 pr-24 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />

                                    <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">
                                        mmol/L
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Measurement Type
                                    <span className="text-red-500">
                                        {" "}
                                        *
                                    </span>
                                </label>

                                <select
                                    required
                                    value={
                                        form.glucose_measurement_type
                                    }
                                    onChange={(event) =>
                                        change(
                                            "glucose_measurement_type",
                                            event.target.value
                                        )
                                    }
                                    className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    {MEASUREMENT_TYPES.map((type) => (
                                        <option
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Date & Time
                                    <span className="text-red-500">
                                        {" "}
                                        *
                                    </span>
                                </label>

                                <input
                                    type="datetime-local"
                                    required
                                    value={form.recorded_at}
                                    onChange={(event) =>
                                        change(
                                            "recorded_at",
                                            event.target.value
                                        )
                                    }
                                    className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Notes
                                </label>

                                <textarea
                                    rows="3"
                                    maxLength="2000"
                                    value={form.glucose_notes}
                                    onChange={(event) =>
                                        change(
                                            "glucose_notes",
                                            event.target.value
                                        )
                                    }
                                    placeholder="Optional observation or note..."
                                    className="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>

                            <div className="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Only one monthly glucose check can be
                                recorded for this resident for{" "}
                                <strong>{monthName}</strong>.
                            </div>

                            <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                                <button
                                    type="button"
                                    disabled={saving}
                                    onClick={closeRecordForm}
                                    className="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving
                                        ? "Recording..."
                                        : "Record Check"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

function SummaryCard({ title, value, description }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-semibold text-slate-500">
                {title}
            </p>

            <p className="mt-2 text-3xl font-bold text-slate-800">
                {value}
            </p>

            <p className="mt-1 text-xs text-slate-400">
                {description}
            </p>
        </div>
    );
}

function MobileInfo({ label, value }) {
    return (
        <div className="rounded-xl bg-slate-50 p-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>
            <p className="mt-1 break-words font-semibold text-slate-800">
                {value ?? "—"}
            </p>
        </div>
    );
}


function TableHead({ children, align = "left" }) {
    return (
        <th
            className={`px-6 py-3 text-${align} text-xs font-bold uppercase tracking-wider text-slate-500`}
        >
            {children}
        </th>
    );
}

export default MonthlyGlucose;