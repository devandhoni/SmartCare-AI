import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import api from "../services/api";

const SLOT_META = {
    AM: {
        label: "AM",
        meal: "Breakfast",
        description: "Morning medication round",
    },
    PM: {
        label: "PM",
        meal: "Lunch",
        description: "Afternoon medication round",
    },
    NIGHT: {
        label: "Night",
        meal: "Dinner",
        description: "Night medication round",
    },
    OTHER: {
        label: "Other",
        meal: null,
        description: "As-needed or other medication",
    },
};

const OUTCOMES = [
    { value: "COMPLETED", label: "Administered" },
    { value: "HELD", label: "Held" },
    { value: "REFUSED", label: "Refused" },
    { value: "UNAVAILABLE", label: "Unavailable" },
    { value: "MISSED", label: "Missed" },
    { value: "DELAYED", label: "Delayed" },
];

const EMPTY_OUTCOME = {
    status: "COMPLETED",
    remarks: "",
    meal_confirmed: false,
    meal_notes: "",
};

function Medication() {
    const [residents, setResidents] = useState([]);
    const [selectedResidentId, setSelectedResidentId] = useState("");
    const [schedule, setSchedule] = useState(emptySchedule());
    const [history, setHistory] = useState([]);
    const [activeSlot, setActiveSlot] = useState("AM");

    const [loadingResidents, setLoadingResidents] = useState(true);
    const [loadingMedication, setLoadingMedication] = useState(false);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [outcomeMedication, setOutcomeMedication] = useState(null);
    const [outcomeForm, setOutcomeForm] = useState(EMPTY_OUTCOME);

    const [mealRecord, setMealRecord] = useState(null);
    const [mealNotes, setMealNotes] = useState("");

    useEffect(() => {
        loadResidents();
    }, []);

    useEffect(() => {
        if (!selectedResidentId) {
            setSchedule(emptySchedule());
            setHistory([]);
            return;
        }

        loadMedicationData(selectedResidentId);
    }, [selectedResidentId]);

    const selectedResident = useMemo(
        () =>
            residents.find(
                (resident) =>
                    String(resident.id) === String(selectedResidentId)
            ) || null,
        [residents, selectedResidentId]
    );

    const allToday = useMemo(
        () =>
            Object.values(schedule)
                .flat()
                .filter(Boolean),
        [schedule]
    );

    const metrics = useMemo(() => {
        const completed = allToday.filter(
            (item) => item.status === "COMPLETED"
        ).length;

        const pending = allToday.filter(
            (item) =>
                item.status === "PENDING"
                || item.status === "DELAYED"
        ).length;

        const exceptions = allToday.filter(
            (item) =>
                !["PENDING", "COMPLETED", "DELAYED"].includes(
                    item.status
                )
        ).length;

        const mealsConfirmed = allToday.filter(
            (item) =>
                item.meal_type
                && item.meal_confirmed
        ).length;

        return {
            total: allToday.length,
            pending,
            completed,
            exceptions,
            mealsConfirmed,
        };
    }, [allToday]);

    const loadResidents = async () => {
        try {
            setLoadingResidents(true);
            setError("");

            const response = await api.get("/residents");

            const data =
                response.data?.residents
                ?? response.data?.data
                ?? response.data;

            const list = Array.isArray(data) ? data : [];

            const active = list
                .filter(
                    (resident) =>
                        String(resident.status || "").toLowerCase()
                        === "active"
                )
                .sort((a, b) =>
                    residentName(a).localeCompare(residentName(b))
                );

            setResidents(active);

            setSelectedResidentId((current) => {
                if (
                    current
                    && active.some(
                        (resident) =>
                            String(resident.id) === String(current)
                    )
                ) {
                    return current;
                }

                return active[0]?.id
                    ? String(active[0].id)
                    : "";
            });
        } catch (err) {
            setResidents([]);
            setError(
                readApiError(
                    err,
                    "Unable to load active residents."
                )
            );
        } finally {
            setLoadingResidents(false);
        }
    };

    const loadMedicationData = async (residentId) => {
        try {
            setLoadingMedication(true);
            setError("");

            const [scheduleResponse, historyResponse] =
                await Promise.all([
                    api.get(
                        `/residents/${residentId}/medication-schedule`
                    ),
                    api.get(
                        `/residents/${residentId}/medication/history`
                    ),
                ]);

            setSchedule(
                normalizeSchedule(
                    scheduleResponse.data?.schedule
                )
            );

            setHistory(
                Array.isArray(
                    historyResponse.data?.medication_history
                )
                    ? historyResponse.data.medication_history
                    : []
            );
        } catch (err) {
            setSchedule(emptySchedule());
            setHistory([]);
            setError(
                readApiError(
                    err,
                    "Unable to load medication rounds."
                )
            );
        } finally {
            setLoadingMedication(false);
        }
    };

    const openOutcome = (medication) => {
        clearMessages();

        setOutcomeMedication(medication);
        setOutcomeForm({
            ...EMPTY_OUTCOME,
            status:
                medication.status === "DELAYED"
                    ? "COMPLETED"
                    : "COMPLETED",
        });
    };

    const saveOutcome = async (event) => {
        event.preventDefault();

        if (!outcomeMedication) return;

        if (
            outcomeForm.status !== "COMPLETED"
            && !outcomeForm.remarks.trim()
        ) {
            setError(
                "Please enter a reason or note for this medication outcome."
            );
            return;
        }

        try {
            setSaving(true);
            clearMessages();

            const payload = {
                status: outcomeForm.status,
                remarks:
                    outcomeForm.remarks.trim() || null,
                meal_confirmed:
                    outcomeForm.status === "COMPLETED"
                        ? Boolean(outcomeForm.meal_confirmed)
                        : false,
                meal_notes:
                    outcomeForm.status === "COMPLETED"
                        ? outcomeForm.meal_notes.trim() || null
                        : null,
            };

            const response = await api.put(
                `/medication-administration/${outcomeMedication.resident_medication_id}/complete`,
                payload
            );

            setSuccess(
                response.data?.message
                || "Medication outcome recorded."
            );

            setOutcomeMedication(null);
            setOutcomeForm(EMPTY_OUTCOME);

            await loadMedicationData(selectedResidentId);
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to record medication outcome."
                )
            );
        } finally {
            setSaving(false);
        }
    };

    const openMealConfirmation = (medication) => {
        clearMessages();

        setMealRecord(medication);
        setMealNotes(
            medication.meal_notes || ""
        );
    };

    const saveMealConfirmation = async (event) => {
        event.preventDefault();

        if (!mealRecord?.administration_record_id) {
            setError(
                "Medication administration record could not be identified."
            );
            return;
        }

        try {
            setSaving(true);
            clearMessages();

            const response = await api.put(
                `/medication-administration/${mealRecord.administration_record_id}/meal-confirmation`,
                {
                    meal_notes:
                        mealNotes.trim() || null,
                }
            );

            setSuccess(
                response.data?.message
                || `${mealRecord.meal_type} confirmed.`
            );

            setMealRecord(null);
            setMealNotes("");

            await loadMedicationData(selectedResidentId);
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to confirm the meal."
                )
            );
        } finally {
            setSaving(false);
        }
    };

    const clearMessages = () => {
        setError("");
        setSuccess("");
    };

    return (
        <div className="mx-auto max-w-7xl space-y-6">
            <header className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                        Daily Care Operations
                    </p>

                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Medication Rounds
                    </h1>

                    <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Record AM, PM and Night medication outcomes and confirm the linked meal without mixing medication administration with stock management.
                    </p>
                </div>

                <Link
                    to="/inventory"
                    className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Open Medicine Inventory
                </Link>
            </header>

            {error && (
                <Alert tone="error">
                    {error}
                </Alert>
            )}

            {success && (
                <Alert tone="success">
                    {success}
                </Alert>
            )}

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <label className="block">
                        <span className="mb-2 block text-sm font-bold text-slate-700">
                            Active Resident
                        </span>

                        <select
                            value={selectedResidentId}
                            disabled={loadingResidents}
                            onChange={(event) => {
                                clearMessages();
                                setSelectedResidentId(
                                    event.target.value
                                );
                            }}
                            className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                            {residents.length === 0 && (
                                <option value="">
                                    No active residents
                                </option>
                            )}

                            {residents.map((resident) => (
                                <option
                                    key={resident.id}
                                    value={resident.id}
                                >
                                    {residentName(resident)}
                                </option>
                            ))}
                        </select>
                    </label>

                    {selectedResident && (
                        <Link
                            to={`/residents/${selectedResident.id}`}
                            className="inline-flex h-[46px] items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50"
                        >
                            View Resident Profile
                        </Link>
                    )}
                </div>
            </section>

            <section className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <Metric
                    label="Scheduled"
                    value={metrics.total}
                />
                <Metric
                    label="Pending"
                    value={metrics.pending}
                    tone={metrics.pending ? "warning" : "normal"}
                />
                <Metric
                    label="Administered"
                    value={metrics.completed}
                    tone={metrics.completed ? "success" : "normal"}
                />
                <Metric
                    label="Exceptions"
                    value={metrics.exceptions}
                    tone={metrics.exceptions ? "danger" : "normal"}
                />
                <Metric
                    label="Meals Confirmed"
                    value={metrics.mealsConfirmed}
                />
            </section>

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-4">
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        {Object.entries(SLOT_META).map(
                            ([slot, meta]) => (
                                <button
                                    key={slot}
                                    type="button"
                                    onClick={() =>
                                        setActiveSlot(slot)
                                    }
                                    className={`rounded-xl px-3 py-3 text-left transition ${
                                        activeSlot === slot
                                            ? "bg-blue-600 text-white shadow-sm"
                                            : "bg-slate-100 text-slate-700 hover:bg-slate-200"
                                    }`}
                                >
                                    <div className="text-sm font-bold">
                                        {meta.label}
                                    </div>
                                    <div
                                        className={`mt-0.5 text-xs ${
                                            activeSlot === slot
                                                ? "text-blue-100"
                                                : "text-slate-500"
                                        }`}
                                    >
                                        {meta.meal
                                            ? `+ ${meta.meal}`
                                            : "No linked meal"}
                                    </div>
                                </button>
                            )
                        )}
                    </div>
                </div>

                <div className="p-5">
                    <div className="mb-5">
                        <h2 className="text-xl font-bold text-slate-800">
                            {SLOT_META[activeSlot].label} Round
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {SLOT_META[activeSlot].description}
                            {SLOT_META[activeSlot].meal
                                ? ` · Linked meal: ${SLOT_META[activeSlot].meal}`
                                : ""}
                        </p>
                    </div>

                    {loadingMedication ? (
                        <LoadingState />
                    ) : !selectedResidentId ? (
                        <EmptyState
                            title="Select a resident"
                            description="Choose an active resident to view today's medication rounds."
                        />
                    ) : schedule[activeSlot].length === 0 ? (
                        <EmptyState
                            title={`No ${SLOT_META[activeSlot].label} medication`}
                            description="No active medication is scheduled in this round today."
                        />
                    ) : (
                        <div className="grid gap-4 xl:grid-cols-2">
                            {schedule[activeSlot].map(
                                (medication) => (
                                    <MedicationCard
                                        key={medication.id}
                                        medication={medication}
                                        onOutcome={openOutcome}
                                        onConfirmMeal={
                                            openMealConfirmation
                                        }
                                    />
                                )
                            )}
                        </div>
                    )}
                </div>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-5">
                    <h2 className="text-lg font-bold text-slate-800">
                        Recent Medication History
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Latest administration evidence for the selected resident.
                    </p>
                </div>

                {loadingMedication ? (
                    <LoadingState />
                ) : history.length === 0 ? (
                    <EmptyState
                        title="No medication history"
                        description="Medication outcomes will appear here after they are recorded."
                    />
                ) : (
                    <>
                        <div className="divide-y divide-slate-100 lg:hidden">
                            {history.slice(0, 12).map(
                                (record) => (
                                    <HistoryCard
                                        key={record.id}
                                        record={record}
                                    />
                                )
                            )}
                        </div>

                        <div className="hidden overflow-x-auto lg:block">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">
                                            Date
                                        </th>
                                        <th className="px-5 py-3">
                                            Medicine
                                        </th>
                                        <th className="px-5 py-3">
                                            Round
                                        </th>
                                        <th className="px-5 py-3">
                                            Outcome
                                        </th>
                                        <th className="px-5 py-3">
                                            Meal
                                        </th>
                                        <th className="px-5 py-3">
                                            Recorded By
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {history
                                        .slice(0, 20)
                                        .map((record) => (
                                            <tr key={record.id}>
                                                <td className="whitespace-nowrap px-5 py-4 text-slate-600">
                                                    {formatDateTime(
                                                        record.completed_time
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="font-bold text-slate-800">
                                                        {record.medicine
                                                            || "Medication"}
                                                    </div>
                                                    <div className="mt-1 text-xs text-slate-500">
                                                        {record.dosage
                                                            || "—"}
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4 text-slate-600">
                                                    {slotLabel(
                                                        record.time_slot
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <StatusBadge
                                                        status={
                                                            record.status
                                                        }
                                                    />
                                                    {record.remarks && (
                                                        <div className="mt-2 max-w-xs text-xs leading-5 text-slate-500">
                                                            {
                                                                record.remarks
                                                            }
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <MealBadge
                                                        record={
                                                            record
                                                        }
                                                    />
                                                </td>

                                                <td className="px-5 py-4 text-slate-600">
                                                    {record.completed_by
                                                        || "—"}
                                                </td>
                                            </tr>
                                        ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </section>

            {outcomeMedication && (
                <Modal
                    title="Record Medication Outcome"
                    description={`${outcomeMedication.medicine || "Medication"} · ${slotLabel(outcomeMedication.time_slot)} round`}
                    onClose={() => {
                        if (!saving) {
                            setOutcomeMedication(null);
                            setOutcomeForm(EMPTY_OUTCOME);
                        }
                    }}
                >
                    <form
                        onSubmit={saveOutcome}
                        className="space-y-5"
                    >
                        <div className="rounded-xl bg-slate-50 p-4">
                            <div className="font-bold text-slate-800">
                                {outcomeMedication.medicine}
                                {outcomeMedication.dosage
                                    ? ` · ${outcomeMedication.dosage}`
                                    : ""}
                            </div>

                            <div className="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                <div>
                                    Scheduled:{" "}
                                    <strong>
                                        {formatTime(
                                            outcomeMedication.scheduled_time
                                        )}
                                    </strong>
                                </div>
                                <div>
                                    Quantity:{" "}
                                    <strong>
                                        {outcomeMedication.quantity}
                                    </strong>
                                </div>
                            </div>

                            {outcomeMedication.instruction && (
                                <p className="mt-3 text-sm leading-6 text-slate-600">
                                    {
                                        outcomeMedication.instruction
                                    }
                                </p>
                            )}
                        </div>

                        <label className="block">
                            <span className="mb-2 block text-sm font-bold text-slate-700">
                                Medication Outcome *
                            </span>

                            <select
                                value={outcomeForm.status}
                                onChange={(event) =>
                                    setOutcomeForm(
                                        (current) => ({
                                            ...current,
                                            status:
                                                event.target
                                                    .value,
                                            meal_confirmed:
                                                event.target
                                                    .value
                                                === "COMPLETED"
                                                    ? current.meal_confirmed
                                                    : false,
                                        })
                                    )
                                }
                                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                {OUTCOMES.map((outcome) => (
                                    <option
                                        key={outcome.value}
                                        value={outcome.value}
                                    >
                                        {outcome.label}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-bold text-slate-700">
                                {outcomeForm.status === "COMPLETED"
                                    ? "Administration Notes"
                                    : "Reason / Notes *"}
                            </span>

                            <textarea
                                rows={4}
                                maxLength={2000}
                                value={outcomeForm.remarks}
                                onChange={(event) =>
                                    setOutcomeForm(
                                        (current) => ({
                                            ...current,
                                            remarks:
                                                event.target
                                                    .value,
                                        })
                                    )
                                }
                                placeholder={
                                    outcomeForm.status === "COMPLETED"
                                        ? "Optional administration notes"
                                        : "Explain why the medication was held, refused, unavailable, missed or delayed."
                                }
                                className="w-full resize-y rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            />
                        </label>

                        {outcomeForm.status === "COMPLETED"
                            && outcomeMedication.meal_type && (
                                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                    <label className="flex cursor-pointer items-start gap-3">
                                        <input
                                            type="checkbox"
                                            checked={
                                                outcomeForm.meal_confirmed
                                            }
                                            onChange={(
                                                event
                                            ) =>
                                                setOutcomeForm(
                                                    (
                                                        current
                                                    ) => ({
                                                        ...current,
                                                        meal_confirmed:
                                                            event
                                                                .target
                                                                .checked,
                                                    })
                                                )
                                            }
                                            className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600"
                                        />

                                        <div>
                                            <div className="font-bold text-slate-800">
                                                Confirm{" "}
                                                {
                                                    outcomeMedication.meal_type
                                                } now
                                            </div>
                                            <p className="mt-1 text-sm leading-5 text-slate-600">
                                                Leave this unchecked if the medication was given but the meal has not yet been confirmed. It can be confirmed later from the round card.
                                            </p>
                                        </div>
                                    </label>

                                    {outcomeForm.meal_confirmed && (
                                        <textarea
                                            rows={3}
                                            maxLength={2000}
                                            value={
                                                outcomeForm.meal_notes
                                            }
                                            onChange={(
                                                event
                                            ) =>
                                                setOutcomeForm(
                                                    (
                                                        current
                                                    ) => ({
                                                        ...current,
                                                        meal_notes:
                                                            event
                                                                .target
                                                                .value,
                                                    })
                                                )
                                            }
                                            placeholder={`${outcomeMedication.meal_type} notes (optional)`}
                                            className="mt-4 w-full resize-y rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                        />
                                    )}
                                </div>
                            )}

                        <ModalActions
                            saving={saving}
                            onCancel={() => {
                                setOutcomeMedication(null);
                                setOutcomeForm(
                                    EMPTY_OUTCOME
                                );
                            }}
                            submitLabel="Save Outcome"
                        />
                    </form>
                </Modal>
            )}

            {mealRecord && (
                <Modal
                    title={`Confirm ${mealRecord.meal_type}`}
                    description={`${mealRecord.medicine || "Medication"} · ${slotLabel(mealRecord.time_slot)} round`}
                    onClose={() => {
                        if (!saving) {
                            setMealRecord(null);
                            setMealNotes("");
                        }
                    }}
                >
                    <form
                        onSubmit={saveMealConfirmation}
                        className="space-y-5"
                    >
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <div className="font-bold text-slate-800">
                                Medication already administered
                            </div>
                            <p className="mt-1 text-sm leading-6 text-slate-600">
                                Confirm the linked{" "}
                                <strong>
                                    {mealRecord.meal_type}
                                </strong>{" "}
                                only after it has actually been completed.
                            </p>
                        </div>

                        <label className="block">
                            <span className="mb-2 block text-sm font-bold text-slate-700">
                                Meal Notes
                            </span>
                            <textarea
                                rows={4}
                                maxLength={2000}
                                value={mealNotes}
                                onChange={(event) =>
                                    setMealNotes(
                                        event.target.value
                                    )
                                }
                                placeholder="Optional meal notes"
                                className="w-full resize-y rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            />
                        </label>

                        <ModalActions
                            saving={saving}
                            onCancel={() => {
                                setMealRecord(null);
                                setMealNotes("");
                            }}
                            submitLabel={`Confirm ${mealRecord.meal_type}`}
                        />
                    </form>
                </Modal>
            )}
        </div>
    );
}

function MedicationCard({
    medication,
    onOutcome,
    onConfirmMeal,
}) {
    const finalException = [
        "HELD",
        "REFUSED",
        "UNAVAILABLE",
        "MISSED",
    ].includes(medication.status);

    const canRecordOutcome =
        medication.status === "PENDING"
        || medication.status === "DELAYED";

    const canConfirmMeal =
        medication.status === "COMPLETED"
        && medication.meal_type
        && !medication.meal_confirmed
        && medication.administration_record_id;

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="break-words text-lg font-bold text-slate-800">
                        {medication.medicine
                            || "Medication"}
                    </h3>

                    <p className="mt-1 text-sm text-slate-500">
                        {[
                            medication.dosage,
                            medication.unit,
                        ]
                            .filter(Boolean)
                            .join(" · ")
                            || "Dosage details not recorded"}
                    </p>
                </div>

                <StatusBadge
                    status={medication.status}
                />
            </div>

            <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                <Info
                    label="Scheduled"
                    value={formatTime(
                        medication.scheduled_time
                    )}
                />
                <Info
                    label="Quantity"
                    value={medication.quantity ?? "—"}
                />
                <Info
                    label="Frequency"
                    value={medication.frequency || "—"}
                />
                <Info
                    label="Linked Meal"
                    value={medication.meal_type || "None"}
                />
            </div>

            {medication.instruction && (
                <div className="mt-4 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600">
                    {medication.instruction}
                </div>
            )}

            {medication.remarks && (
                <div className="mt-4 rounded-xl border border-slate-200 p-3">
                    <div className="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Medication Notes
                    </div>
                    <p className="mt-1 text-sm leading-6 text-slate-600">
                        {medication.remarks}
                    </p>
                </div>
            )}

            {medication.meal_type && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                    <div>
                        <div className="text-sm font-bold text-slate-800">
                            {medication.meal_type}
                        </div>
                        <div className="mt-0.5 text-xs text-slate-500">
                            {medication.meal_confirmed
                                ? `Confirmed ${formatDateTime(medication.meal_confirmed_at)}`
                                : "Not yet confirmed"}
                        </div>
                    </div>

                    {medication.meal_confirmed ? (
                        <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            Confirmed
                        </span>
                    ) : medication.status === "COMPLETED" ? (
                        <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                            Awaiting meal
                        </span>
                    ) : (
                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                            Pending
                        </span>
                    )}
                </div>
            )}

            {medication.meal_notes && (
                <p className="mt-3 text-sm leading-6 text-slate-500">
                    Meal note: {medication.meal_notes}
                </p>
            )}

            <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                {canRecordOutcome && (
                    <button
                        type="button"
                        onClick={() =>
                            onOutcome(medication)
                        }
                        className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700"
                    >
                        {medication.status === "DELAYED"
                            ? "Update Outcome"
                            : "Record Outcome"}
                    </button>
                )}

                {canConfirmMeal && (
                    <button
                        type="button"
                        onClick={() =>
                            onConfirmMeal(medication)
                        }
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700"
                    >
                        Confirm {medication.meal_type}
                    </button>
                )}

                {medication.status === "COMPLETED"
                    && (
                        medication.meal_confirmed
                        || !medication.meal_type
                    ) && (
                        <div className="rounded-xl bg-emerald-50 px-4 py-2.5 text-center text-sm font-bold text-emerald-700">
                            Round Complete
                        </div>
                    )}

                {finalException && (
                    <div className="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-sm font-bold text-slate-600">
                        Outcome Recorded
                    </div>
                )}
            </div>
        </article>
    );
}

function HistoryCard({ record }) {
    return (
        <div className="p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="font-bold text-slate-800">
                        {record.medicine || "Medication"}
                    </div>
                    <div className="mt-1 text-sm text-slate-500">
                        {record.dosage || "—"} ·{" "}
                        {slotLabel(record.time_slot)}
                    </div>
                </div>

                <StatusBadge status={record.status} />
            </div>

            <div className="mt-4 grid grid-cols-2 gap-3">
                <Info
                    label="Recorded"
                    value={formatDateTime(
                        record.completed_time
                    )}
                />
                <Info
                    label="Recorded By"
                    value={record.completed_by || "—"}
                />
            </div>

            {record.meal_type && (
                <div className="mt-3">
                    <MealBadge record={record} />
                </div>
            )}

            {record.remarks && (
                <p className="mt-3 text-sm leading-6 text-slate-600">
                    {record.remarks}
                </p>
            )}
        </div>
    );
}

function StatusBadge({ status }) {
    const normalized =
        String(status || "PENDING").toUpperCase();

    const styles = {
        COMPLETED:
            "bg-emerald-100 text-emerald-700",
        PENDING:
            "bg-slate-100 text-slate-700",
        DELAYED:
            "bg-amber-100 text-amber-700",
        HELD:
            "bg-orange-100 text-orange-700",
        REFUSED:
            "bg-red-100 text-red-700",
        UNAVAILABLE:
            "bg-violet-100 text-violet-700",
        MISSED:
            "bg-red-100 text-red-700",
    };

    const labels = {
        COMPLETED: "Administered",
        PENDING: "Pending",
        DELAYED: "Delayed",
        HELD: "Held",
        REFUSED: "Refused",
        UNAVAILABLE: "Unavailable",
        MISSED: "Missed",
    };

    return (
        <span
            className={`rounded-full px-2.5 py-1 text-xs font-bold ${
                styles[normalized]
                || "bg-slate-100 text-slate-700"
            }`}
        >
            {labels[normalized]
                || normalized.replaceAll("_", " ")}
        </span>
    );
}

function MealBadge({ record }) {
    if (!record.meal_type) {
        return (
            <span className="text-sm text-slate-400">
                No linked meal
            </span>
        );
    }

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${
                record.meal_confirmed
                    ? "bg-emerald-100 text-emerald-700"
                    : "bg-slate-100 text-slate-600"
            }`}
        >
            {record.meal_type}:{" "}
            {record.meal_confirmed
                ? "Confirmed"
                : "Pending"}
        </span>
    );
}

function Metric({
    label,
    value,
    tone = "normal",
}) {
    const styles = {
        normal:
            "border-slate-200 bg-white",
        warning:
            "border-amber-200 bg-amber-50",
        success:
            "border-emerald-200 bg-emerald-50",
        danger:
            "border-red-200 bg-red-50",
    };

    return (
        <div
            className={`rounded-xl border px-3 py-3 text-center ${
                styles[tone] || styles.normal
            }`}
        >
            <div className="text-xl font-bold text-slate-800">
                {value}
            </div>
            <div className="mt-0.5 text-xs font-semibold text-slate-500">
                {label}
            </div>
        </div>
    );
}

function Info({ label, value }) {
    return (
        <div className="min-w-0 rounded-xl bg-slate-50 p-3">
            <div className="text-xs font-bold uppercase tracking-wide text-slate-400">
                {label}
            </div>
            <div className="mt-1 break-words font-semibold text-slate-800">
                {value ?? "—"}
            </div>
        </div>
    );
}

function Alert({ tone, children }) {
    const style =
        tone === "success"
            ? "border-emerald-200 bg-emerald-50 text-emerald-700"
            : "border-red-200 bg-red-50 text-red-700";

    return (
        <div
            className={`rounded-xl border px-4 py-3 text-sm font-medium ${style}`}
        >
            {children}
        </div>
    );
}

function LoadingState() {
    return (
        <div className="p-10 text-center text-sm text-slate-500">
            Loading medication rounds...
        </div>
    );
}

function EmptyState({ title, description }) {
    return (
        <div className="p-10 text-center">
            <div className="font-bold text-slate-700">
                {title}
            </div>
            <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
                {description}
            </p>
        </div>
    );
}

function Modal({
    title,
    description,
    onClose,
    children,
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white p-5">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800">
                            {title}
                        </h2>
                        {description && (
                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                {description}
                            </p>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100"
                    >
                        Close
                    </button>
                </div>

                <div className="p-5">
                    {children}
                </div>
            </div>
        </div>
    );
}

function ModalActions({
    saving,
    onCancel,
    submitLabel,
}) {
    return (
        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <button
                type="button"
                disabled={saving}
                onClick={onCancel}
                className="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
                Cancel
            </button>

            <button
                type="submit"
                disabled={saving}
                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
            >
                {saving
                    ? "Saving..."
                    : submitLabel}
            </button>
        </div>
    );
}

function emptySchedule() {
    return {
        AM: [],
        PM: [],
        NIGHT: [],
        OTHER: [],
    };
}

function normalizeSchedule(value) {
    const source =
        value && typeof value === "object"
            ? value
            : {};

    return {
        AM: Array.isArray(source.AM)
            ? source.AM
            : [],
        PM: Array.isArray(source.PM)
            ? source.PM
            : [],
        NIGHT: Array.isArray(source.NIGHT)
            ? source.NIGHT
            : [],
        OTHER: Array.isArray(source.OTHER)
            ? source.OTHER
            : [],
    };
}

function residentName(resident) {
    return (
        resident.full_name
        || resident.name
        || `Resident #${resident.id}`
    );
}

function slotLabel(slot) {
    return (
        SLOT_META[
            String(slot || "").toUpperCase()
        ]?.label
        || slot
        || "—"
    );
}

function formatTime(value) {
    if (!value) return "—";

    const text = String(value);

    const timeMatch =
        text.match(/(?:T|\s)?(\d{2}):(\d{2})/);

    if (!timeMatch) {
        return text;
    }

    const hours = Number(timeMatch[1]);
    const minutes = timeMatch[2];

    if (!Number.isFinite(hours)) {
        return text;
    }

    const suffix =
        hours >= 12 ? "PM" : "AM";

    const displayHour =
        hours % 12 || 12;

    return `${displayHour}:${minutes} ${suffix}`;
}

function formatDateTime(value) {
    if (!value) return "—";

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleString("en-MY", {
              day: "2-digit",
              month: "short",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          });
}

function readApiError(error, fallback) {
    const data = error?.response?.data;

    if (data?.errors) {
        const first =
            Object.values(data.errors)
                .flat()[0];

        if (first) return first;
    }

    return (
        data?.message
        || error?.message
        || fallback
    );
}

export default Medication;
