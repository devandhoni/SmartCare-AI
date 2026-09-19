import { useCallback, useEffect, useState } from "react";

import {
    getStaff,
    createStaff,
    updateStaff,
    updateStaffStatus,
    resetStaffPassword,
} from "../api/staffApi";

const ROLES = [
    { id: 1, name: "Administrator" },
    { id: 2, name: "Manager" },
    { id: 3, name: "Doctor" },
    { id: 4, name: "Nurse" },
];

const EMPTY_FORM = {
    full_name: "",
    email: "",
    phone: "",
    role_id: "4",
    password: "",
    password_confirmation: "",
};

const FIELD_CLASS =
    "w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100";

const BUTTON_CLASS =
    "rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50";

function readApiError(error, fallback) {
    const data = error?.response?.data;

    if (data?.errors) {
        const firstError = Object.values(data.errors)
            .flat()
            .find(Boolean);

        if (firstError) {
            return String(firstError);
        }
    }

    return data?.message || fallback;
}

function getCurrentUserId() {
    try {
        const user = JSON.parse(
            localStorage.getItem("user") || "null"
        );

        return user?.id == null ? null : String(user.id);
    } catch {
        return null;
    }
}

function Field({ label, children }) {
    return (
        <label className="block min-w-0">
            <span className="mb-1.5 block text-sm font-semibold text-slate-700">
                {label}
            </span>

            {children}
        </label>
    );
}

function StaffAdministration() {
    const [staff, setStaff] = useState([]);
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);

    const [search, setSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState("All");

    const [selectedStaff, setSelectedStaff] = useState(null);
    const [mode, setMode] = useState(null);
    const [form, setForm] = useState(EMPTY_FORM);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const currentUserId = getCurrentUserId();

    const loadStaff = useCallback(async () => {
        setLoading(true);
        setError("");

        try {
            const data = await getStaff();

            setStaff(
                Array.isArray(data?.staff) ? data.staff : []
            );
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to load staff accounts."
                )
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadStaff();
    }, [loadStaff]);

    const filteredStaff = staff.filter((member) => {
        const query = search.trim().toLowerCase();

        const matchesSearch =
            !query ||
            [
                member.full_name,
                member.email,
                member.role,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value).toLowerCase().includes(query)
                );

        const matchesStatus =
            statusFilter === "All" ||
            member.status === statusFilter;

        return matchesSearch && matchesStatus;
    });

    const activeCount = staff.filter(
        (member) => member.status === "Active"
    ).length;

    function clearMessages() {
        setError("");
        setSuccess("");
    }

    function closeForm() {
        setMode(null);
        setSelectedStaff(null);
        setForm(EMPTY_FORM);
    }

    function openCreate() {
        clearMessages();
        setSelectedStaff(null);
        setForm({ ...EMPTY_FORM });
        setMode("create");
    }

    function openEdit(member) {
        clearMessages();
        setSelectedStaff(member);

        setForm({
            full_name: member.full_name || "",
            email: member.email || "",
            phone: member.phone || "",
            role_id: String(member.role_id),
            password: "",
            password_confirmation: "",
        });

        setMode("edit");
    }

    function openReset(member) {
        clearMessages();
        setSelectedStaff(member);

        setForm({
            ...EMPTY_FORM,
            password: "",
            password_confirmation: "",
        });

        setMode("reset");
    }

    function updateField(field, value) {
        setForm((previous) => ({
            ...previous,
            [field]: value,
        }));
    }

    async function handleSave(event) {
        event.preventDefault();

        if (busy) {
            return;
        }

        setBusy(true);
        clearMessages();

        try {
            if (mode === "create") {
                await createStaff({
                    full_name: form.full_name.trim(),
                    email: form.email.trim(),
                    phone: form.phone.trim() || null,
                    role_id: Number(form.role_id),
                    password: form.password,
                    password_confirmation:
                        form.password_confirmation,
                });

                setSuccess("Staff account created successfully.");
            } else if (mode === "edit" && selectedStaff) {
                await updateStaff(selectedStaff.id, {
                    full_name: form.full_name.trim(),
                    email: form.email.trim(),
                    phone: form.phone.trim() || null,
                    role_id: Number(form.role_id),
                });

                setSuccess("Staff details updated successfully.");
            }

            closeForm();
            await loadStaff();
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to save staff details."
                )
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleStatusChange(member) {
        if (busy) {
            return;
        }

        const nextStatus =
            member.status === "Active"
                ? "Inactive"
                : "Active";

        const action =
            nextStatus === "Inactive"
                ? "deactivate"
                : "reactivate";

        const confirmed = window.confirm(
            `Are you sure you want to ${action} ${member.full_name}?` +
                (nextStatus === "Inactive"
                    ? "\n\nTheir existing login sessions will be revoked."
                    : "")
        );

        if (!confirmed) {
            return;
        }

        setBusy(true);
        clearMessages();

        try {
            await updateStaffStatus(member.id, nextStatus);

            setSuccess(
                `${member.full_name} is now ${nextStatus.toLowerCase()}.`
            );

            await loadStaff();
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to change account status."
                )
            );
        } finally {
            setBusy(false);
        }
    }

    async function handlePasswordReset(event) {
        event.preventDefault();

        if (busy || !selectedStaff) {
            return;
        }

        if (form.password !== form.password_confirmation) {
            setError("Passwords do not match.");
            return;
        }

        setBusy(true);
        clearMessages();

        try {
            await resetStaffPassword(selectedStaff.id, {
                password: form.password,
                password_confirmation:
                    form.password_confirmation,
            });

            setSuccess(
                `Password reset for ${selectedStaff.full_name}. Existing login sessions were revoked.`
            );

            closeForm();
            await loadStaff();
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to reset the password."
                )
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="mx-auto w-full min-w-0 max-w-7xl space-y-6 pb-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Staff Administration
                    </h1>

                    <p className="mt-2 text-sm text-slate-500">
                        Manage staff accounts and access.
                        Family Member accounts are managed separately.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={openCreate}
                    disabled={busy}
                    className={`${BUTTON_CLASS} bg-blue-600 text-white hover:bg-blue-700`}
                >
                    + Add Staff
                </button>
            </div>

            {error && (
                <div
                    role="alert"
                    className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {error}
                </div>
            )}

            {success && (
                <div
                    role="status"
                    className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {success}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                    <p className="text-sm text-slate-500">
                        Total staff
                    </p>
                    <p className="mt-2 text-3xl font-bold text-slate-900">
                        {staff.length}
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                    <p className="text-sm text-slate-500">
                        Active
                    </p>
                    <p className="mt-2 text-3xl font-bold text-green-700">
                        {activeCount}
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                    <p className="text-sm text-slate-500">
                        Inactive
                    </p>
                    <p className="mt-2 text-3xl font-bold text-slate-600">
                        {staff.length - activeCount}
                    </p>
                </div>
            </div>

            <section className="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
                    <h2 className="text-lg font-bold text-slate-900">
                        Staff Accounts
                    </h2>

                    <button
                        type="button"
                        onClick={loadStaff}
                        disabled={loading || busy}
                        className={`${BUTTON_CLASS} border border-slate-300 text-slate-700 hover:bg-slate-50`}
                    >
                        Refresh
                    </button>
                </div>

                <div className="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                    <input
                        type="search"
                        value={search}
                        onChange={(event) =>
                            setSearch(event.target.value)
                        }
                        placeholder="Search name, email or role"
                        aria-label="Search staff"
                        className={FIELD_CLASS}
                    />

                    <select
                        value={statusFilter}
                        onChange={(event) =>
                            setStatusFilter(event.target.value)
                        }
                        aria-label="Filter by status"
                        className={FIELD_CLASS}
                    >
                        <option value="All">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                {loading ? (
                    <p className="p-6 text-center text-sm text-slate-500">
                        Loading staff accounts...
                    </p>
                ) : filteredStaff.length === 0 ? (
                    <p className="p-6 text-center text-sm text-slate-500">
                        No staff accounts match your search.
                    </p>
                ) : (
                    <div className="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3 sm:p-5">
                        {filteredStaff.map((member) => {
                            const isSelf =
                                currentUserId !== null &&
                                String(member.id) === currentUserId;

                            return (
                                <article
                                    key={member.id}
                                    className="flex min-w-0 flex-col rounded-xl border border-slate-200 p-4"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <h3 className="break-words font-bold text-slate-900">
                                                {member.full_name}
                                            </h3>

                                            <p className="mt-1 text-sm text-slate-500">
                                                {member.role}
                                            </p>
                                        </div>

                                        <span
                                            className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                member.status === "Active"
                                                    ? "bg-green-100 text-green-700"
                                                    : "bg-slate-100 text-slate-600"
                                            }`}
                                        >
                                            {member.status}
                                        </span>
                                    </div>

                                    <p className="mt-4 break-all text-sm text-slate-600">
                                        {member.email}
                                    </p>

                                    <p className="mt-1 text-sm text-slate-500">
                                        {member.phone || "No phone number"}
                                    </p>

                                    <div className="mt-auto flex flex-wrap gap-2 pt-5">
                                        <button
                                            type="button"
                                            onClick={() => openEdit(member)}
                                            disabled={busy}
                                            className={`${BUTTON_CLASS} border border-slate-300 text-slate-700 hover:bg-slate-50`}
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => openReset(member)}
                                            disabled={busy}
                                            className={`${BUTTON_CLASS} border border-slate-300 text-slate-700 hover:bg-slate-50`}
                                        >
                                            Reset Password
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleStatusChange(member)
                                            }
                                            disabled={busy || isSelf}
                                            title={
                                                isSelf
                                                    ? "You cannot deactivate your own account."
                                                    : undefined
                                            }
                                            className={`${BUTTON_CLASS} ${
                                                member.status === "Active"
                                                    ? "border border-red-200 text-red-700 hover:bg-red-50"
                                                    : "border border-green-200 text-green-700 hover:bg-green-50"
                                            }`}
                                        >
                                            {member.status === "Active"
                                                ? "Deactivate"
                                                : "Reactivate"}
                                        </button>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>

            {mode && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3 sm:p-6"
                    role="presentation"
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="staff-form-title"
                        className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-7"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h2
                                    id="staff-form-title"
                                    className="text-xl font-bold text-slate-900"
                                >
                                    {mode === "create"
                                        ? "Add Staff"
                                        : mode === "edit"
                                          ? "Edit Staff"
                                          : "Reset Password"}
                                </h2>

                                {selectedStaff && (
                                    <p className="mt-1 break-words text-sm text-slate-500">
                                        {selectedStaff.full_name}
                                    </p>
                                )}
                            </div>

                            <button
                                type="button"
                                onClick={closeForm}
                                disabled={busy}
                                aria-label="Close form"
                                className="rounded-lg px-3 py-1 text-xl text-slate-500 hover:bg-slate-100"
                            >
                                ×
                            </button>
                        </div>

                        <form
                            onSubmit={
                                mode === "reset"
                                    ? handlePasswordReset
                                    : handleSave
                            }
                            className="mt-6 space-y-4"
                        >
                            {mode !== "reset" && (
                                <>
                                    <Field label="Full name">
                                        <input
                                            type="text"
                                            required
                                            maxLength={150}
                                            value={form.full_name}
                                            onChange={(event) =>
                                                updateField(
                                                    "full_name",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        />
                                    </Field>

                                    <Field label="Email address">
                                        <input
                                            type="email"
                                            required
                                            maxLength={150}
                                            value={form.email}
                                            onChange={(event) =>
                                                updateField(
                                                    "email",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        />
                                    </Field>

                                    <Field label="Phone number (optional)">
                                        <input
                                            type="tel"
                                            maxLength={30}
                                            value={form.phone}
                                            onChange={(event) =>
                                                updateField(
                                                    "phone",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        />
                                    </Field>

                                    <Field label="Staff role">
                                        <select
                                            required
                                            value={form.role_id}
                                            onChange={(event) =>
                                                updateField(
                                                    "role_id",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        >
                                            {ROLES.filter(
                                                (role) =>
                                                    !(
                                                        mode === "edit" &&
                                                        selectedStaff &&
                                                        currentUserId !== null &&
                                                        String(
                                                            selectedStaff.id
                                                        ) === currentUserId &&
                                                        role.name !==
                                                            "Administrator"
                                                    )
                                            ).map((role) => (
                                                <option
                                                    key={role.id}
                                                    value={role.id}
                                                >
                                                    {role.name}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                </>
                            )}

                            {(mode === "create" ||
                                mode === "reset") && (
                                <>
                                    <Field label="New password">
                                        <input
                                            type="password"
                                            required
                                            minLength={12}
                                            autoComplete="new-password"
                                            value={form.password}
                                            onChange={(event) =>
                                                updateField(
                                                    "password",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        />
                                    </Field>

                                    <Field label="Confirm password">
                                        <input
                                            type="password"
                                            required
                                            minLength={12}
                                            autoComplete="new-password"
                                            value={
                                                form.password_confirmation
                                            }
                                            onChange={(event) =>
                                                updateField(
                                                    "password_confirmation",
                                                    event.target.value
                                                )
                                            }
                                            className={FIELD_CLASS}
                                        />
                                    </Field>

                                    <p className="text-xs text-slate-500">
                                        Use at least 12 characters.
                                        Passwords are never displayed after saving.
                                    </p>
                                </>
                            )}

                            {mode === "reset" && (
                                <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                                    Resetting this password will revoke
                                    the staff member&apos;s existing login
                                    sessions.
                                </p>
                            )}

                            <div className="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                                <button
                                    type="button"
                                    onClick={closeForm}
                                    disabled={busy}
                                    className={`${BUTTON_CLASS} border border-slate-300 text-slate-700 hover:bg-slate-50`}
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    disabled={busy}
                                    className={`${BUTTON_CLASS} bg-blue-600 text-white hover:bg-blue-700`}
                                >
                                    {busy
                                        ? "Saving..."
                                        : mode === "create"
                                          ? "Create Staff"
                                          : mode === "edit"
                                            ? "Save Changes"
                                            : "Reset Password"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

export default StaffAdministration;