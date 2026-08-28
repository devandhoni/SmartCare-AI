import { useEffect, useMemo, useState } from "react";
import api from "../services/api";

const EMPTY_RECEIVE_FORM = {
    resident_id: "",
    sender_name: "",
    courier_name: "",
    tracking_number: "",
    parcel_description: "",
    quantity: 1,
    condition_on_arrival: "GOOD",
    received_at: "",
    parcel_checked: false,
    resident_notified: false,
    receiving_notes: "",
};

const EMPTY_COLLECTION_FORM = {
    collected_at: "",
    collected_by_name: "",
    collected_by_relationship: "",
    collected_by_contact: "",
    collection_notes: "",
};

function localDateTimeValue() {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60000;

    return new Date(now.getTime() - offset)
        .toISOString()
        .slice(0, 16);
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

    return date.toLocaleString();
}

function formatCondition(value) {
    if (!value) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .toLowerCase()
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function StatusBadge({ status }) {
    const normalized = String(status || "").toUpperCase();

    const classes =
        normalized === "COLLECTED"
            ? "bg-emerald-100 text-emerald-700"
            : "bg-amber-100 text-amber-700";

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${classes}`}
        >
            {normalized || "UNKNOWN"}
        </span>
    );
}

function ConditionBadge({ condition }) {
    const normalized = String(condition || "").toUpperCase();

    let classes = "bg-slate-100 text-slate-700";

    if (normalized === "GOOD") {
        classes = "bg-emerald-100 text-emerald-700";
    }

    if (normalized === "DAMAGED") {
        classes = "bg-red-100 text-red-700";
    }

    if (normalized === "OPENED") {
        classes = "bg-orange-100 text-orange-700";
    }

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${classes}`}
        >
            {formatCondition(condition)}
        </span>
    );
}

function SectionHeader({ title, subtitle, count }) {
    return (
        <div className="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 className="text-lg font-bold text-slate-800">
                    {title}
                </h2>

                {subtitle && (
                    <p className="mt-1 text-sm text-slate-500">
                        {subtitle}
                    </p>
                )}
            </div>

            {typeof count === "number" && (
                <span className="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
                    {count}
                </span>
            )}
        </div>
    );
}

function EmptyState({ text }) {
    return (
        <div className="px-6 py-10 text-center text-sm text-slate-500">
            {text}
        </div>
    );
}

function Field({ label, required, children }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-slate-700">
                {label}

                {required && (
                    <span className="ml-1 text-red-500">*</span>
                )}
            </span>

            {children}
        </label>
    );
}

const inputClass =
    "w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100";

function Parcels() {
    const [parcels, setParcels] = useState([]);
    const [residents, setResidents] = useState([]);

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [search, setSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState("ALL");

    const [showReceiveForm, setShowReceiveForm] = useState(false);

    const [receiveForm, setReceiveForm] = useState({
        ...EMPTY_RECEIVE_FORM,
        received_at: localDateTimeValue(),
    });

    const [editingParcel, setEditingParcel] = useState(null);

    const [collectingParcel, setCollectingParcel] = useState(null);

    const [collectionForm, setCollectionForm] = useState({
        ...EMPTY_COLLECTION_FORM,
        collected_at: localDateTimeValue(),
    });

    useEffect(() => {
        loadPage();
    }, []);

    const loadPage = async () => {
        try {
            setLoading(true);
            setError("");

            const [parcelResponse, residentResponse] =
                await Promise.all([
                    api.get("/parcels"),
                    api.get("/residents"),
                ]);

            const parcelData =
                parcelResponse.data?.parcels ??
                parcelResponse.data?.data ??
                parcelResponse.data;

            const residentData =
                residentResponse.data?.residents ??
                residentResponse.data?.data ??
                residentResponse.data;

            setParcels(
                Array.isArray(parcelData)
                    ? parcelData
                    : []
            );

            setResidents(
                Array.isArray(residentData)
                    ? residentData
                    : []
            );
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to load parcel records."
                )
            );
        } finally {
            setLoading(false);
        }
    };

    const activeResidents = useMemo(() => {
        return residents.filter(
            (resident) =>
                String(resident.status).toLowerCase() ===
                "active"
        );
    }, [residents]);

    const filteredParcels = useMemo(() => {
        const term = search.trim().toLowerCase();

        return parcels.filter((parcel) => {
            const statusMatches =
                statusFilter === "ALL" ||
                String(parcel.status).toUpperCase() ===
                    statusFilter;

            if (!statusMatches) {
                return false;
            }

            if (!term) {
                return true;
            }

            const searchable = [
                parcel.parcel_reference,
                parcel.resident?.full_name,
                parcel.sender_name,
                parcel.courier_name,
                parcel.tracking_number,
                parcel.parcel_description,
            ]
                .filter(Boolean)
                .join(" ")
                .toLowerCase();

            return searchable.includes(term);
        });
    }, [parcels, search, statusFilter]);

    const pendingParcels = useMemo(
        () =>
            filteredParcels.filter(
                (parcel) =>
                    String(parcel.status).toUpperCase() ===
                    "RECEIVED"
            ),
        [filteredParcels]
    );

    const collectedParcels = useMemo(
        () =>
            filteredParcels.filter(
                (parcel) =>
                    String(parcel.status).toUpperCase() ===
                    "COLLECTED"
            ),
        [filteredParcels]
    );

    const totalPending = parcels.filter(
        (parcel) =>
            String(parcel.status).toUpperCase() ===
            "RECEIVED"
    ).length;

    const awaitingNotification = parcels.filter(
        (parcel) =>
            String(parcel.status).toUpperCase() ===
                "RECEIVED" &&
            !parcel.resident_notified
    ).length;

    const totalCollected = parcels.filter(
        (parcel) =>
            String(parcel.status).toUpperCase() ===
            "COLLECTED"
    ).length;

    const openReceiveForm = () => {
        setEditingParcel(null);

        setReceiveForm({
            ...EMPTY_RECEIVE_FORM,
            received_at: localDateTimeValue(),
        });

        setError("");
        setSuccess("");
        setShowReceiveForm(true);
    };

    const openEditForm = (parcel) => {
        setEditingParcel(parcel);

        setReceiveForm({
            resident_id:
                parcel.resident_id ?? "",
            sender_name:
                parcel.sender_name ?? "",
            courier_name:
                parcel.courier_name ?? "",
            tracking_number:
                parcel.tracking_number ?? "",
            parcel_description:
                parcel.parcel_description ?? "",
            quantity:
                parcel.quantity ?? 1,
            condition_on_arrival:
                parcel.condition_on_arrival ?? "GOOD",
            received_at:
                parcel.received_at
                    ? toInputDateTime(parcel.received_at)
                    : localDateTimeValue(),
            parcel_checked:
                Boolean(parcel.parcel_checked),
            resident_notified:
                Boolean(parcel.resident_notified),
            receiving_notes:
                parcel.receiving_notes ?? "",
        });

        setError("");
        setSuccess("");
        setShowReceiveForm(true);
    };

    const closeReceiveForm = () => {
        if (saving) {
            return;
        }

        setShowReceiveForm(false);
        setEditingParcel(null);
    };

    const updateReceiveForm = (field, value) => {
        setReceiveForm((current) => ({
            ...current,
            [field]: value,
        }));
    };

    const saveParcel = async (event) => {
        event.preventDefault();

        try {
            setSaving(true);
            setError("");
            setSuccess("");

            const payload = {
                sender_name:
                    receiveForm.sender_name.trim() || null,

                courier_name:
                    receiveForm.courier_name.trim() || null,

                tracking_number:
                    receiveForm.tracking_number.trim() || null,

                parcel_description:
                    receiveForm.parcel_description.trim() ||
                    null,

                quantity:
                    Number(receiveForm.quantity),

                condition_on_arrival:
                    receiveForm.condition_on_arrival,

                received_at:
                    receiveForm.received_at,

                parcel_checked:
                    Boolean(receiveForm.parcel_checked),

                resident_notified:
                    Boolean(receiveForm.resident_notified),

                receiving_notes:
                    receiveForm.receiving_notes.trim() || null,
            };

            if (editingParcel) {
                await api.put(
                    `/parcels/${editingParcel.id}`,
                    payload
                );

                setSuccess(
                    "Parcel details updated successfully."
                );
            } else {
                await api.post("/parcels", {
                    resident_id:
                        Number(receiveForm.resident_id),

                    ...payload,
                });

                setSuccess(
                    "Incoming parcel recorded successfully."
                );
            }

            setShowReceiveForm(false);
            setEditingParcel(null);

            await loadPage();
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to save parcel."
                )
            );
        } finally {
            setSaving(false);
        }
    };

    const markResidentNotified = async (parcel) => {
        try {
            setError("");
            setSuccess("");

            await api.post(
                `/parcels/${parcel.id}/notify`
            );

            setSuccess(
                `${parcel.resident?.full_name || "Resident"} marked as notified.`
            );

            await loadPage();
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to update notification status."
                )
            );
        }
    };

    const openCollection = (parcel) => {
        setCollectingParcel(parcel);

        setCollectionForm({
            ...EMPTY_COLLECTION_FORM,
            collected_at: localDateTimeValue(),
            collected_by_name:
                parcel.resident?.full_name || "",
            collected_by_relationship:
                "Resident",
        });

        setError("");
        setSuccess("");
    };

    const closeCollection = () => {
        if (saving) {
            return;
        }

        setCollectingParcel(null);
    };

    const updateCollectionForm = (field, value) => {
        setCollectionForm((current) => ({
            ...current,
            [field]: value,
        }));
    };

    const collectParcel = async (event) => {
        event.preventDefault();

        if (!collectingParcel) {
            return;
        }

        try {
            setSaving(true);
            setError("");
            setSuccess("");

            await api.post(
                `/parcels/${collectingParcel.id}/collect`,
                {
                    collected_at:
                        collectionForm.collected_at,

                    collected_by_name:
                        collectionForm.collected_by_name.trim(),

                    collected_by_relationship:
                        collectionForm.collected_by_relationship.trim() ||
                        null,

                    collected_by_contact:
                        collectionForm.collected_by_contact.trim() ||
                        null,

                    collection_notes:
                        collectionForm.collection_notes.trim() ||
                        null,
                }
            );

            setSuccess(
                "Parcel collection recorded successfully."
            );

            setCollectingParcel(null);

            await loadPage();
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to record parcel collection."
                )
            );
        } finally {
            setSaving(false);
        }
    };

    const deleteParcel = async (parcel) => {
        const confirmed = window.confirm(
            `Delete parcel ${parcel.parcel_reference}? This action cannot be undone.`
        );

        if (!confirmed) {
            return;
        }

        try {
            setError("");
            setSuccess("");

            await api.delete(
                `/parcels/${parcel.id}`
            );

            setSuccess(
                "Parcel record deleted successfully."
            );

            await loadPage();
        } catch (err) {
            console.error(err);

            setError(
                readApiError(
                    err,
                    "Unable to delete parcel."
                )
            );
        }
    };

    if (loading) {
        return (
            <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                Loading parcel records...
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                        Care Operations
                    </p>

                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Incoming Parcels
                    </h1>

                    <p className="mt-2 max-w-2xl text-sm text-slate-500">
                        Record parcels received for residents,
                        notify residents and confirm collection.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={openReceiveForm}
                    className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700"
                >
                    + Receive Parcel
                </button>
            </div>

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

            <div className="grid gap-4 md:grid-cols-3">
                <SummaryCard
                    label="Waiting Collection"
                    value={totalPending}
                    description="Parcels currently held for residents"
                />

                <SummaryCard
                    label="Need Notification"
                    value={awaitingNotification}
                    description="Residents not yet marked as notified"
                />

                <SummaryCard
                    label="Collected"
                    value={totalCollected}
                    description="Completed parcel collections"
                />
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="grid gap-3 md:grid-cols-[1fr_220px]">
                    <input
                        type="search"
                        value={search}
                        onChange={(event) =>
                            setSearch(event.target.value)
                        }
                        placeholder="Search resident, parcel reference, sender, courier or tracking number..."
                        className={inputClass}
                    />

                    <select
                        value={statusFilter}
                        onChange={(event) =>
                            setStatusFilter(
                                event.target.value
                            )
                        }
                        className={inputClass}
                    >
                        <option value="ALL">
                            All Statuses
                        </option>

                        <option value="RECEIVED">
                            Waiting Collection
                        </option>

                        <option value="COLLECTED">
                            Collected
                        </option>
                    </select>
                </div>
            </div>

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <SectionHeader
                    title="Waiting for Collection"
                    subtitle="Parcels currently held for residents."
                    count={pendingParcels.length}
                />

                {pendingParcels.length === 0 ? (
                    <EmptyState text="No parcels are currently waiting for collection." />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {pendingParcels.map((parcel) => (
                            <PendingParcelCard
                                key={parcel.id}
                                parcel={parcel}
                                onEdit={openEditForm}
                                onNotify={
                                    markResidentNotified
                                }
                                onCollect={
                                    openCollection
                                }
                                onDelete={deleteParcel}
                            />
                        ))}
                    </div>
                )}
            </section>

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <SectionHeader
                    title="Collection History"
                    subtitle="Completed parcel handovers."
                    count={collectedParcels.length}
                />

                {collectedParcels.length === 0 ? (
                    <EmptyState text="No collected parcels found." />
                ) : (
                    <CollectedParcelTable
                        parcels={collectedParcels}
                    />
                )}
            </section>

            {showReceiveForm && (
                <Modal
                    title={
                        editingParcel
                            ? "Edit Incoming Parcel"
                            : "Receive Incoming Parcel"
                    }
                    description={
                        editingParcel
                            ? editingParcel.parcel_reference
                            : "Complete the parcel receiving checklist."
                    }
                    onClose={closeReceiveForm}
                >
                    <form
                        onSubmit={saveParcel}
                        className="space-y-6"
                    >
                        {!editingParcel && (
                            <Field
                                label="Resident"
                                required
                            >
                                <select
                                    required
                                    value={
                                        receiveForm.resident_id
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "resident_id",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="">
                                        Select active resident
                                    </option>

                                    {activeResidents.map(
                                        (resident) => (
                                            <option
                                                key={
                                                    resident.id
                                                }
                                                value={
                                                    resident.id
                                                }
                                            >
                                                {
                                                    resident.full_name
                                                }
                                            </option>
                                        )
                                    )}
                                </select>
                            </Field>
                        )}

                        {editingParcel && (
                            <div className="rounded-xl bg-slate-50 p-4">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Resident
                                </p>

                                <p className="mt-1 font-bold text-slate-800">
                                    {editingParcel
                                        .resident
                                        ?.full_name ||
                                        "Resident"}
                                </p>
                            </div>
                        )}

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Sender Name">
                                <input
                                    type="text"
                                    value={
                                        receiveForm.sender_name
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "sender_name",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                    placeholder="Optional"
                                />
                            </Field>

                            <Field label="Courier / Delivery Service">
                                <input
                                    type="text"
                                    value={
                                        receiveForm.courier_name
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "courier_name",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                    placeholder="Example: J&T Express"
                                />
                            </Field>

                            <Field label="Tracking Number">
                                <input
                                    type="text"
                                    value={
                                        receiveForm.tracking_number
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "tracking_number",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                    placeholder="Optional"
                                />
                            </Field>

                            <Field
                                label="Date & Time Received"
                                required
                            >
                                <input
                                    required
                                    type="datetime-local"
                                    value={
                                        receiveForm.received_at
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "received_at",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                />
                            </Field>

                            <Field
                                label="Quantity"
                                required
                            >
                                <input
                                    required
                                    type="number"
                                    min="1"
                                    max="100"
                                    value={
                                        receiveForm.quantity
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "quantity",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                />
                            </Field>

                            <Field
                                label="Condition on Arrival"
                                required
                            >
                                <select
                                    required
                                    value={
                                        receiveForm.condition_on_arrival
                                    }
                                    onChange={(event) =>
                                        updateReceiveForm(
                                            "condition_on_arrival",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="GOOD">
                                        Good
                                    </option>

                                    <option value="DAMAGED">
                                        Damaged
                                    </option>

                                    <option value="OPENED">
                                        Opened
                                    </option>

                                    <option value="OTHER">
                                        Other
                                    </option>
                                </select>
                            </Field>
                        </div>

                        <Field label="Parcel Description">
                            <textarea
                                rows="3"
                                value={
                                    receiveForm.parcel_description
                                }
                                onChange={(event) =>
                                    updateReceiveForm(
                                        "parcel_description",
                                        event.target.value
                                    )
                                }
                                className={inputClass}
                                placeholder="Example: Clothing parcel, personal items, documents..."
                            />
                        </Field>

                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 className="font-bold text-slate-800">
                                Receiving Checklist
                            </h3>

                            <div className="mt-4 space-y-3">
                                <label className="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="checkbox"
                                        checked={
                                            receiveForm.parcel_checked
                                        }
                                        onChange={(event) =>
                                            updateReceiveForm(
                                                "parcel_checked",
                                                event.target.checked
                                            )
                                        }
                                        className="mt-1 h-4 w-4"
                                    />

                                    <span>
                                        <span className="block text-sm font-semibold text-slate-700">
                                            Parcel checked
                                        </span>

                                        <span className="text-xs text-slate-500">
                                            Staff has visually
                                            checked the parcel and
                                            recorded its condition.
                                        </span>
                                    </span>
                                </label>

                                <label className="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="checkbox"
                                        checked={
                                            receiveForm.resident_notified
                                        }
                                        onChange={(event) =>
                                            updateReceiveForm(
                                                "resident_notified",
                                                event.target.checked
                                            )
                                        }
                                        className="mt-1 h-4 w-4"
                                    />

                                    <span>
                                        <span className="block text-sm font-semibold text-slate-700">
                                            Resident already
                                            notified
                                        </span>

                                        <span className="text-xs text-slate-500">
                                            Select this if the
                                            resident has already
                                            been informed.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <Field label="Receiving Notes">
                            <textarea
                                rows="3"
                                value={
                                    receiveForm.receiving_notes
                                }
                                onChange={(event) =>
                                    updateReceiveForm(
                                        "receiving_notes",
                                        event.target.value
                                    )
                                }
                                className={inputClass}
                                placeholder="Optional notes"
                            />
                        </Field>

                        <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={closeReceiveForm}
                                disabled={saving}
                                className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={saving}
                                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                {saving
                                    ? "Saving..."
                                    : editingParcel
                                      ? "Save Changes"
                                      : "Record Parcel"}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {collectingParcel && (
                <Modal
                    title="Record Parcel Collection"
                    description={`${collectingParcel.parcel_reference} · ${collectingParcel.resident?.full_name || "Resident"}`}
                    onClose={closeCollection}
                >
                    <form
                        onSubmit={collectParcel}
                        className="space-y-5"
                    >
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <p className="text-sm font-semibold text-emerald-800">
                                Confirm who received the
                                parcel before completing the
                                handover.
                            </p>
                        </div>

                        <Field
                            label="Collection Date & Time"
                            required
                        >
                            <input
                                required
                                type="datetime-local"
                                value={
                                    collectionForm.collected_at
                                }
                                onChange={(event) =>
                                    updateCollectionForm(
                                        "collected_at",
                                        event.target.value
                                    )
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Collected By"
                            required
                        >
                            <input
                                required
                                type="text"
                                value={
                                    collectionForm.collected_by_name
                                }
                                onChange={(event) =>
                                    updateCollectionForm(
                                        "collected_by_name",
                                        event.target.value
                                    )
                                }
                                className={inputClass}
                                placeholder="Name of person receiving parcel"
                            />
                        </Field>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Relationship">
                                <select
                                    value={
                                        collectionForm.collected_by_relationship
                                    }
                                    onChange={(event) =>
                                        updateCollectionForm(
                                            "collected_by_relationship",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="">
                                        Select
                                    </option>

                                    <option value="Resident">
                                        Resident
                                    </option>

                                    <option value="Father">
                                        Father
                                    </option>

                                    <option value="Mother">
                                        Mother
                                    </option>

                                    <option value="Spouse">
                                        Spouse
                                    </option>

                                    <option value="Son">
                                        Son
                                    </option>

                                    <option value="Daughter">
                                        Daughter
                                    </option>

                                    <option value="Sibling">
                                        Sibling
                                    </option>

                                    <option value="Guardian">
                                        Guardian
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>
                                </select>
                            </Field>

                            <Field label="Contact Number">
                                <input
                                    type="text"
                                    value={
                                        collectionForm.collected_by_contact
                                    }
                                    onChange={(event) =>
                                        updateCollectionForm(
                                            "collected_by_contact",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                    placeholder="Optional"
                                />
                            </Field>
                        </div>

                        <Field label="Collection Notes">
                            <textarea
                                rows="3"
                                value={
                                    collectionForm.collection_notes
                                }
                                onChange={(event) =>
                                    updateCollectionForm(
                                        "collection_notes",
                                        event.target.value
                                    )
                                }
                                className={inputClass}
                                placeholder="Optional handover notes"
                            />
                        </Field>

                        <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={closeCollection}
                                disabled={saving}
                                className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={saving}
                                className="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-60"
                            >
                                {saving
                                    ? "Saving..."
                                    : "Confirm Collection"}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}

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

function PendingParcelCard({
    parcel,
    onEdit,
    onNotify,
    onCollect,
    onDelete,
}) {
    return (
        <div className="p-5">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-bold text-slate-800">
                            {parcel.resident?.full_name ||
                                "Resident"}
                        </h3>

                        <StatusBadge
                            status={parcel.status}
                        />

                        <ConditionBadge
                            condition={
                                parcel.condition_on_arrival
                            }
                        />
                    </div>

                    <p className="mt-1 text-sm font-semibold text-blue-600">
                        {parcel.parcel_reference}
                    </p>

                    <div className="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <Info
                            label="Received"
                            value={formatDateTime(
                                parcel.received_at
                            )}
                        />

                        <Info
                            label="Sender"
                            value={
                                parcel.sender_name || "—"
                            }
                        />

                        <Info
                            label="Courier"
                            value={
                                parcel.courier_name || "—"
                            }
                        />

                        <Info
                            label="Tracking"
                            value={
                                parcel.tracking_number ||
                                "—"
                            }
                        />

                        <Info
                            label="Quantity"
                            value={parcel.quantity}
                        />

                        <Info
                            label="Received By"
                            value={
                                parcel.received_by
                                    ?.full_name ||
                                parcel.receivedBy
                                    ?.full_name ||
                                "—"
                            }
                        />
                    </div>

                    {parcel.parcel_description && (
                        <p className="mt-4 text-sm text-slate-600">
                            {parcel.parcel_description}
                        </p>
                    )}

                    <div className="mt-4 flex flex-wrap gap-2">
                        <span
                            className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                parcel.parcel_checked
                                    ? "bg-emerald-100 text-emerald-700"
                                    : "bg-slate-100 text-slate-600"
                            }`}
                        >
                            {parcel.parcel_checked
                                ? "✓ Parcel Checked"
                                : "Not Checked"}
                        </span>

                        <span
                            className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                parcel.resident_notified
                                    ? "bg-emerald-100 text-emerald-700"
                                    : "bg-amber-100 text-amber-700"
                            }`}
                        >
                            {parcel.resident_notified
                                ? "✓ Resident Notified"
                                : "Resident Not Yet Notified"}
                        </span>
                    </div>
                </div>

                <div className="flex shrink-0 flex-wrap gap-2">
                    {!parcel.resident_notified && (
                        <button
                            type="button"
                            onClick={() =>
                                onNotify(parcel)
                            }
                            className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-100"
                        >
                            Mark Notified
                        </button>
                    )}

                    <button
                        type="button"
                        onClick={() =>
                            onEdit(parcel)
                        }
                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
                    >
                        Edit
                    </button>

                    <button
                        type="button"
                        onClick={() =>
                            onCollect(parcel)
                        }
                        className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700"
                    >
                        Record Collection
                    </button>

                    <button
                        type="button"
                        onClick={() =>
                            onDelete(parcel)
                        }
                        className="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    );
}

function CollectedParcelTable({ parcels }) {
    return (
        <>
            <div className="divide-y divide-slate-100 lg:hidden">
                {parcels.map((parcel) => (
                    <div key={parcel.id} className="p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="font-bold text-slate-800">
                                    {parcel.resident?.full_name || "Resident"}
                                </h3>
                                <p className="mt-1 break-all text-sm font-semibold text-blue-600">
                                    {parcel.parcel_reference}
                                </p>
                            </div>

                            <StatusBadge status={parcel.status} />
                        </div>

                        <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <Info
                                label="Received"
                                value={formatDateTime(parcel.received_at)}
                            />
                            <Info
                                label="Collected"
                                value={formatDateTime(parcel.collected_at)}
                            />
                            <Info
                                label="Collected By"
                                value={parcel.collected_by_name || "—"}
                            />
                            <Info
                                label="Courier / Sender"
                                value={parcel.courier_name || parcel.sender_name || "—"}
                            />
                        </div>

                        {parcel.collected_by_relationship && (
                            <p className="mt-3 text-sm text-slate-500">
                                Relationship: {parcel.collected_by_relationship}
                            </p>
                        )}
                    </div>
                ))}
            </div>

            <div className="hidden overflow-x-auto lg:block">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            <TableHeader>Resident</TableHeader>
                            <TableHeader>Parcel</TableHeader>
                            <TableHeader>Received</TableHeader>
                            <TableHeader>Collected</TableHeader>
                            <TableHeader>Collected By</TableHeader>
                            <TableHeader>Status</TableHeader>
                        </tr>
                    </thead>

                    <tbody className="divide-y divide-slate-100 bg-white">
                        {parcels.map((parcel) => (
                            <tr key={parcel.id} className="hover:bg-slate-50">
                                <TableCell>
                                    <span className="font-semibold text-slate-800">
                                        {parcel.resident?.full_name || "Resident"}
                                    </span>
                                </TableCell>

                                <TableCell>
                                    <div>
                                        <p className="font-semibold text-blue-600">
                                            {parcel.parcel_reference}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            {parcel.courier_name || parcel.sender_name || "—"}
                                        </p>
                                    </div>
                                </TableCell>

                                <TableCell>{formatDateTime(parcel.received_at)}</TableCell>
                                <TableCell>{formatDateTime(parcel.collected_at)}</TableCell>

                                <TableCell>
                                    <div>
                                        <p className="font-medium text-slate-700">
                                            {parcel.collected_by_name || "—"}
                                        </p>
                                        {parcel.collected_by_relationship && (
                                            <p className="text-xs text-slate-500">
                                                {parcel.collected_by_relationship}
                                            </p>
                                        )}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <StatusBadge status={parcel.status} />
                                </TableCell>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}


function Info({ label, value }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>

            <p className="mt-1 break-words font-medium text-slate-700">
                {value ?? "—"}
            </p>
        </div>
    );
}

function TableHeader({ children }) {
    return (
        <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            {children}
        </th>
    );
}

function TableCell({ children }) {
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
                        onClick={onClose}
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

function toInputDateTime(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const offset =
        date.getTimezoneOffset() * 60000;

    return new Date(
        date.getTime() - offset
    )
        .toISOString()
        .slice(0, 16);
}

export default Parcels;