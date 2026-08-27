import { useEffect, useMemo, useState } from "react";
import api from "../services/api";


function localDateTimeValue() {
    const now = new Date();

    now.setMinutes(
        now.getMinutes() -
        now.getTimezoneOffset()
    );

    return now
        .toISOString()
        .slice(0, 16);
}


function readApiError(
    error,
    fallback
) {
    const data =
        error?.response?.data;

    if (data?.message) {
        return data.message;
    }

    if (data?.errors) {
        const firstError =
            Object.values(
                data.errors
            )?.[0];

        if (
            Array.isArray(firstError)
            &&
            firstError.length > 0
        ) {
            return firstError[0];
        }
    }

    return fallback;
}


function formatDateTime(value) {
    if (!value) {
        return "—";
    }

    const date =
        new Date(value);

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return value;
    }

    return date.toLocaleString(
        "en-MY",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }
    );
}


function getWeekRange(date = new Date()) {
    const working =
        new Date(date);

    working.setHours(
        0,
        0,
        0,
        0
    );

    const day =
        working.getDay();

    /*
    |--------------------------------------------------------------------------
    | Monday = Start of Week
    |--------------------------------------------------------------------------
    */

    const differenceToMonday =
        day === 0
            ? -6
            : 1 - day;

    const start =
        new Date(working);

    start.setDate(
        working.getDate()
        + differenceToMonday
    );

    start.setHours(
        0,
        0,
        0,
        0
    );


    const end =
        new Date(start);

    end.setDate(
        start.getDate()
        + 6
    );

    end.setHours(
        23,
        59,
        59,
        999
    );


    return {
        start,
        end,
    };
}


function formatWeekRange(
    start,
    end
) {
    const formatter =
        new Intl.DateTimeFormat(
            "en-MY",
            {
                day: "2-digit",
                month: "short",
                year: "numeric",
            }
        );

    return (
        formatter.format(start)
        +
        " - "
        +
        formatter.format(end)
    );
}


function WeeklyVitals() {
    const [residents, setResidents] =
        useState([]);

    const [checks, setChecks] =
        useState([]);

    const [loading, setLoading] =
        useState(true);

    const [saving, setSaving] =
        useState(false);

    const [error, setError] =
        useState("");

    const [success, setSuccess] =
        useState("");

    const [search, setSearch] =
        useState("");

    const [
        selectedResident,
        setSelectedResident,
    ] = useState(null);


    const [form, setForm] =
        useState({
            blood_pressure_systolic: "",
            blood_pressure_diastolic: "",
            heart_rate: "",
            oxygen_level: "",
            temperature: "",
            weight: "",
            blood_glucose: "",
            recorded_at:
                localDateTimeValue(),
        });


    const currentWeek =
        useMemo(
            () =>
                getWeekRange(
                    new Date()
                ),
            []
        );


    useEffect(() => {
        loadPage();
    }, []);


    async function loadPage() {
        try {
            setLoading(true);
            setError("");

            const [
                residentResponse,
                weeklyResponse,
            ] = await Promise.all([
                api.get(
                    "/residents"
                ),

                api.get(
                    "/weekly-vital-checks"
                ),
            ]);


            const residentData =
                residentResponse
                    .data?.residents
                ??
                residentResponse
                    .data?.data
                ??
                residentResponse.data
                ??
                [];


            const checkData =
                weeklyResponse
                    .data?.checks
                ??
                weeklyResponse
                    .data?.data
                ??
                weeklyResponse.data
                ??
                [];


            setResidents(
                Array.isArray(
                    residentData
                )
                    ? residentData.filter(
                        (resident) =>
                            String(
                                resident.status
                            ).toLowerCase()
                            === "active"
                    )
                    : []
            );


            setChecks(
                Array.isArray(
                    checkData
                )
                    ? checkData
                    : []
            );

        }
        catch (err) {
            console.error(
                "Unable to load weekly vitals:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to load weekly vital checks."
                )
            );
        }
        finally {
            setLoading(false);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Current Week Records
    |--------------------------------------------------------------------------
    */

    const currentWeekChecks =
        useMemo(() => {
            return checks.filter(
                (check) => {
                    if (
                        !check.recorded_at
                    ) {
                        return false;
                    }

                    const recorded =
                        new Date(
                            check.recorded_at
                        );

                    return (
                        recorded
                        >= currentWeek.start
                        &&
                        recorded
                        <= currentWeek.end
                    );
                }
            );
        }, [
            checks,
            currentWeek,
        ]);


    /*
    |--------------------------------------------------------------------------
    | One Current Check Per Resident
    |--------------------------------------------------------------------------
    */

    const checkByResident =
        useMemo(() => {
            const map =
                new Map();

            currentWeekChecks.forEach(
                (check) => {
                    map.set(
                        String(
                            check.resident_id
                        ),
                        check
                    );
                }
            );

            return map;
        }, [
            currentWeekChecks,
        ]);


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    const visibleResidents =
        useMemo(() => {
            const term =
                search
                    .trim()
                    .toLowerCase();

            if (!term) {
                return residents;
            }

            return residents.filter(
                (resident) => {
                    const searchable = [
                        resident.full_name,
                        resident.ic_number,
                        resident.room?.room_number,
                        resident.room?.name,
                    ]
                        .filter(Boolean)
                        .join(" ")
                        .toLowerCase();

                    return searchable.includes(
                        term
                    );
                }
            );
        }, [
            residents,
            search,
        ]);


    const completedCount =
        residents.filter(
            (resident) =>
                checkByResident.has(
                    String(
                        resident.id
                    )
                )
        ).length;


    const pendingCount =
        Math.max(
            residents.length
            - completedCount,
            0
        );


    const completionPercentage =
        residents.length > 0
            ? Math.round(
                (
                    completedCount
                    /
                    residents.length
                )
                * 100
            )
            : 0;


    function openCheckForm(
        resident
    ) {
        setSelectedResident(
            resident
        );

        setForm({
            blood_pressure_systolic: "",
            blood_pressure_diastolic: "",
            heart_rate: "",
            oxygen_level: "",
            temperature: "",
            weight: "",
            blood_glucose: "",
            recorded_at:
                localDateTimeValue(),
        });

        setError("");
        setSuccess("");
    }


    function closeCheckForm() {
        if (saving) {
            return;
        }

        setSelectedResident(
            null
        );
    }


    function updateForm(
        field,
        value
    ) {
        setForm(
            (current) => ({
                ...current,
                [field]: value,
            })
        );
    }


    async function saveCheck(
        event
    ) {
        event.preventDefault();

        if (
            !selectedResident
        ) {
            return;
        }


        try {
            setSaving(true);
            setError("");
            setSuccess("");


            await api.post(
                `/residents/${selectedResident.id}/weekly-vital-checks`,
                {
                    blood_pressure_systolic:
                        Number(
                            form
                                .blood_pressure_systolic
                        ),

                    blood_pressure_diastolic:
                        Number(
                            form
                                .blood_pressure_diastolic
                        ),

                    heart_rate:
                        Number(
                            form
                                .heart_rate
                        ),

                    oxygen_level:
                        Number(
                            form
                                .oxygen_level
                        ),

                    temperature:
                        Number(
                            form
                                .temperature
                        ),

                    weight:
                        Number(
                            form
                                .weight
                        ),

                    blood_glucose:
                        form.blood_glucose
                            ? Number(
                                form
                                    .blood_glucose
                            )
                            : null,

                    recorded_at:
                        form.recorded_at,
                }
            );


            setSuccess(
                `Weekly vital signs recorded for ${selectedResident.full_name}.`
            );


            setSelectedResident(
                null
            );


            await loadPage();

        }
        catch (err) {
            console.error(
                "Unable to save weekly vitals:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to save weekly vital signs."
                )
            );
        }
        finally {
            setSaving(false);
        }
    }


    if (loading) {
        return (
            <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                Loading weekly vital checks...
            </div>
        );
    }


    return (
        <div className="space-y-6">


            {/* Header */}

            <div>
                <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                    Resident Operations
                </p>

                <h1 className="mt-1 text-3xl font-bold text-slate-800">
                    Weekly Vital Checks
                </h1>

                <p className="mt-2 max-w-3xl text-slate-500">
                    Record the required weekly vital signs for every active resident.
                </p>

                <p className="mt-2 text-sm font-semibold text-slate-600">
                    Current Week:{" "}
                    {formatWeekRange(
                        currentWeek.start,
                        currentWeek.end
                    )}
                </p>
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


            {/* Summary */}

            <div className="grid gap-4 md:grid-cols-3">

                <SummaryCard
                    label="Completed"
                    value={completedCount}
                    description="Residents checked this week"
                />

                <SummaryCard
                    label="Still Due"
                    value={pendingCount}
                    description="Residents awaiting weekly vitals"
                />

                <SummaryCard
                    label="Completion"
                    value={`${completionPercentage}%`}
                    description="Weekly checklist progress"
                />

            </div>


            {/* Progress */}

            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div className="flex items-center justify-between">

                    <div>
                        <h2 className="font-bold text-slate-800">
                            Weekly Progress
                        </h2>

                        <p className="mt-1 text-sm text-slate-500">
                            {completedCount} of {residents.length} active residents completed
                        </p>
                    </div>

                    <span className="text-lg font-bold text-blue-600">
                        {completionPercentage}%
                    </span>

                </div>


                <div className="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">

                    <div
                        className="h-full rounded-full bg-blue-600 transition-all"
                        style={{
                            width:
                                `${completionPercentage}%`,
                        }}
                    />

                </div>

            </div>


            {/* Search */}

            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                <input
                    type="search"
                    value={search}
                    onChange={
                        (event) =>
                            setSearch(
                                event.target.value
                            )
                    }
                    placeholder="Search resident name or IC number..."
                    className={inputClass}
                />

            </div>


            {/* Resident Checklist */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div className="border-b border-slate-200 px-5 py-4">

                    <h2 className="text-lg font-bold text-slate-800">
                        Resident Weekly Checklist
                    </h2>

                    <p className="mt-1 text-sm text-slate-500">
                        Each active resident requires one completed check per week.
                    </p>

                </div>


                {visibleResidents.length === 0 ? (
                    <div className="p-10 text-center text-sm text-slate-500">
                        No active residents found.
                    </div>
                ) : (

                    <div className="divide-y divide-slate-100">

                        {visibleResidents.map(
                            (resident) => {

                                const check =
                                    checkByResident.get(
                                        String(
                                            resident.id
                                        )
                                    );


                                return (
                                    <ResidentRow
                                        key={resident.id}
                                        resident={resident}
                                        check={check}
                                        onRecord={
                                            openCheckForm
                                        }
                                    />
                                );
                            }
                        )}

                    </div>

                )}

            </section>


            {/* Current Week Completed Records */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div className="border-b border-slate-200 px-5 py-4">

                    <h2 className="text-lg font-bold text-slate-800">
                        Completed This Week
                    </h2>

                    <p className="mt-1 text-sm text-slate-500">
                        Weekly vital observations already recorded.
                    </p>

                </div>


                {currentWeekChecks.length === 0 ? (

                    <div className="p-10 text-center text-sm text-slate-500">
                        No weekly vital checks have been recorded yet.
                    </div>

                ) : (

                    <div className="overflow-x-auto">

                        <table className="min-w-full divide-y divide-slate-200 text-sm">

                            <thead className="bg-slate-50">
                                <tr>
                                    <TableHeader>
                                        Resident
                                    </TableHeader>

                                    <TableHeader>
                                        Recorded
                                    </TableHeader>

                                    <TableHeader>
                                        BP
                                    </TableHeader>

                                    <TableHeader>
                                        Pulse
                                    </TableHeader>

                                    <TableHeader>
                                        SpO₂
                                    </TableHeader>

                                    <TableHeader>
                                        Temperature
                                    </TableHeader>

                                    <TableHeader>
                                        Weight
                                    </TableHeader>

                                    <TableHeader>
                                        Glucose
                                    </TableHeader>
                                </tr>
                            </thead>


                            <tbody className="divide-y divide-slate-100">

                                {currentWeekChecks.map(
                                    (check) => (

                                        <tr
                                            key={check.id}
                                            className="hover:bg-slate-50"
                                        >

                                            <TableCell>
                                                <span className="font-semibold text-slate-800">
                                                    {check.resident?.full_name || `Resident #${check.resident_id}`}
                                                </span>
                                            </TableCell>


                                            <TableCell>
                                                {formatDateTime(
                                                    check.recorded_at
                                                )}
                                            </TableCell>


                                            <TableCell>
                                                {check.blood_pressure_systolic}/
                                                {check.blood_pressure_diastolic}
                                            </TableCell>


                                            <TableCell>
                                                {check.heart_rate} bpm
                                            </TableCell>


                                            <TableCell>
                                                {check.oxygen_level}%
                                            </TableCell>


                                            <TableCell>
                                                {check.temperature} °C
                                            </TableCell>


                                            <TableCell>
                                                {check.weight} kg
                                            </TableCell>


                                            <TableCell>
                                                {check.blood_glucose
                                                    ? `${check.blood_glucose} mmol/L`
                                                    : "—"}
                                            </TableCell>

                                        </tr>

                                    )
                                )}

                            </tbody>

                        </table>

                    </div>

                )}

            </section>


            {/* Record Modal */}

            {selectedResident && (

                <Modal
                    title="Record Weekly Vital Signs"
                    description={`Recording for ${selectedResident.full_name}`}
                    onClose={
                        closeCheckForm
                    }
                >

                    <form
                        onSubmit={
                            saveCheck
                        }
                        className="space-y-6"
                    >


                        <div className="rounded-xl border border-blue-100 bg-blue-50 p-4">

                            <p className="text-sm font-semibold text-blue-800">
                                Weekly Vital Signs Checklist
                            </p>

                            <p className="mt-1 text-sm text-blue-700">
                                Blood pressure, pulse, SpO₂, temperature and weight are required. Blood glucose is optional.
                            </p>

                        </div>


                        <Field
                            label="Recorded Date & Time"
                            required
                        >

                            <input
                                required
                                type="datetime-local"
                                value={
                                    form.recorded_at
                                }
                                onChange={
                                    (event) =>
                                        updateForm(
                                            "recorded_at",
                                            event.target.value
                                        )
                                }
                                className={
                                    inputClass
                                }
                            />

                        </Field>


                        <FormSectionTitle>
                            Blood Pressure
                        </FormSectionTitle>


                        <div className="grid gap-4 md:grid-cols-2">

                            <Field
                                label="Systolic (mmHg)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="50"
                                    max="250"
                                    value={
                                        form.blood_pressure_systolic
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "blood_pressure_systolic",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 120"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>


                            <Field
                                label="Diastolic (mmHg)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="30"
                                    max="150"
                                    value={
                                        form.blood_pressure_diastolic
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "blood_pressure_diastolic",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 80"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>

                        </div>


                        <FormSectionTitle>
                            Vital Measurements
                        </FormSectionTitle>


                        <div className="grid gap-4 md:grid-cols-2">

                            <Field
                                label="Pulse / Heart Rate (bpm)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="30"
                                    max="200"
                                    value={
                                        form.heart_rate
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "heart_rate",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 76"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>


                            <Field
                                label="SpO₂ (%)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="50"
                                    max="100"
                                    value={
                                        form.oxygen_level
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "oxygen_level",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 98"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>


                            <Field
                                label="Temperature (°C)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="30"
                                    max="45"
                                    step="0.1"
                                    value={
                                        form.temperature
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "temperature",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 36.7"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>


                            <Field
                                label="Weight (kg)"
                                required
                            >

                                <input
                                    required
                                    type="number"
                                    min="1"
                                    max="300"
                                    step="0.1"
                                    value={
                                        form.weight
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "weight",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Example: 69.2"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>


                            <Field label="Blood Glucose (mmol/L)">

                                <input
                                    type="number"
                                    min="1"
                                    max="30"
                                    step="0.1"
                                    value={
                                        form.blood_glucose
                                    }
                                    onChange={
                                        (event) =>
                                            updateForm(
                                                "blood_glucose",
                                                event.target.value
                                            )
                                    }
                                    placeholder="Optional"
                                    className={
                                        inputClass
                                    }
                                />

                            </Field>

                        </div>


                        <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">

                            <button
                                type="button"
                                onClick={
                                    closeCheckForm
                                }
                                disabled={
                                    saving
                                }
                                className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                disabled={
                                    saving
                                }
                                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                {saving
                                    ? "Saving..."
                                    : "Save Weekly Vitals"}
                            </button>

                        </div>

                    </form>

                </Modal>

            )}

        </div>
    );
}


/*
|--------------------------------------------------------------------------
| Resident Checklist Row
|--------------------------------------------------------------------------
*/


function ResidentRow({
    resident,
    check,
    onRecord,
}) {
    return (
        <div className="p-5">

            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    <div className="flex flex-wrap items-center gap-3">

                        <h3 className="text-lg font-bold text-slate-800">
                            {resident.full_name}
                        </h3>


                        {check ? (

                            <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                ✓ COMPLETED
                            </span>

                        ) : (

                            <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                DUE THIS WEEK
                            </span>

                        )}

                    </div>


                    {resident.ic_number && (
                        <p className="mt-1 text-sm text-slate-500">
                            IC: {resident.ic_number}
                        </p>
                    )}


                    {check && (

                        <div className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600">

                            <span>
                                Recorded:{" "}
                                <strong>
                                    {formatDateTime(
                                        check.recorded_at
                                    )}
                                </strong>
                            </span>

                            <span>
                                BP:{" "}
                                <strong>
                                    {check.blood_pressure_systolic}/
                                    {check.blood_pressure_diastolic}
                                </strong>
                            </span>

                            <span>
                                SpO₂:{" "}
                                <strong>
                                    {check.oxygen_level}%
                                </strong>
                            </span>

                        </div>

                    )}

                </div>


                {!check && (

                    <button
                        type="button"
                        onClick={
                            () =>
                                onRecord(
                                    resident
                                )
                        }
                        className="shrink-0 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700"
                    >
                        Record Weekly Vitals
                    </button>

                )}

            </div>

        </div>
    );
}


/*
|--------------------------------------------------------------------------
| UI Components
|--------------------------------------------------------------------------
*/


function SummaryCard({
    label,
    value,
    description,
}) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p className="text-sm font-semibold text-slate-500">
                {label}
            </p>

            <p className="mt-2 text-3xl font-bold text-slate-800">
                {value}
            </p>

            <p className="mt-1 text-xs text-slate-500">
                {description}
            </p>

        </div>
    );
}


function Field({
    label,
    required,
    children,
}) {
    return (
        <label className="block">

            <span className="mb-1.5 block text-sm font-semibold text-slate-700">

                {label}

                {required && (
                    <span className="ml-1 text-red-500">
                        *
                    </span>
                )}

            </span>

            {children}

        </label>
    );
}


function FormSectionTitle({
    children,
}) {
    return (
        <div className="border-b border-slate-200 pb-2">

            <h3 className="font-bold text-slate-800">
                {children}
            </h3>

        </div>
    );
}


function TableHeader({
    children,
}) {
    return (
        <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            {children}
        </th>
    );
}


function TableCell({
    children,
}) {
    return (
        <td className="whitespace-nowrap px-5 py-4 text-slate-600">
            {children}
        </td>
    );
}


function Modal({
    title,
    description,
    onClose,
    children,
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">

            <div className="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">

                <div className="sticky top-0 z-10 flex items-start justify-between border-b border-slate-200 bg-white px-6 py-5">

                    <div>

                        <h2 className="text-xl font-bold text-slate-800">
                            {title}
                        </h2>

                        {description && (
                            <p className="mt-1 text-sm text-slate-500">
                                {description}
                            </p>
                        )}

                    </div>


                    <button
                        type="button"
                        onClick={
                            onClose
                        }
                        className="ml-4 rounded-lg px-3 py-1.5 text-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >
                        ×
                    </button>

                </div>


                <div className="p-6">
                    {children}
                </div>

            </div>

        </div>
    );
}


const inputClass =
    "w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100";


export default WeeklyVitals;