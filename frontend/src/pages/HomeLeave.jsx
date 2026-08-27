import { useEffect, useMemo, useState } from "react";
import api from "../services/api";


const EMPTY_LEAVE_FORM = {
    resident_id: "",
    leave_at: "",
    reason_for_leave: "",
    destination: "",
    taken_by_name: "",
    taken_by_relationship: "",
    taken_by_contact: "",
    expected_return_at: "",
    medication_handed_over: false,
    medication_instructions: "",
    belongings_taken: false,
    belongings_notes: "",
    leave_notes: "",
};


const EMPTY_RETURN_FORM = {
    returned_at: "",
    returned_by_name: "",
    returned_by_relationship: "",
    returned_by_contact: "",
    condition_on_return: "Stable",
    return_notes: "",
};


function HomeLeave() {

    const [residents, setResidents] = useState([]);
    const [homeLeaves, setHomeLeaves] = useState([]);

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [search, setSearch] = useState("");

    const [showLeaveForm, setShowLeaveForm] = useState(false);
    const [editingLeave, setEditingLeave] = useState(null);

    const [leaveForm, setLeaveForm] = useState(
        EMPTY_LEAVE_FORM
    );

    const [returnLeave, setReturnLeave] = useState(null);

    const [returnForm, setReturnForm] = useState(
        EMPTY_RETURN_FORM
    );


    useEffect(() => {

        loadPage();

    }, []);


    const loadPage = async () => {

        try {

            setLoading(true);
            setError("");

            const [
                residentsResponse,
                leavesResponse,
            ] = await Promise.all([

                api.get("/residents"),

                api.get("/home-leaves"),

            ]);


            const residentData =
                residentsResponse.data?.residents ??
                residentsResponse.data?.data ??
                residentsResponse.data;


            const leaveData =
                leavesResponse.data?.home_leaves ??
                leavesResponse.data?.data ??
                leavesResponse.data;


            setResidents(
                Array.isArray(residentData)
                    ? residentData
                    : []
            );


            setHomeLeaves(
                Array.isArray(leaveData)
                    ? leaveData
                    : []
            );

        }
        catch (err) {

            console.error(
                "Unable to load home leave page:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to load home leave records."
                )
            );

        }
        finally {

            setLoading(false);

        }

    };


    const activeResidents = useMemo(() => {

        return residents.filter(
            (resident) =>
                String(resident.status).toLowerCase()
                === "active"
        );

    }, [residents]);


    const onLeaveRecords = useMemo(() => {

        return homeLeaves.filter(
            (leave) =>
                String(leave.status).toUpperCase()
                === "ON_LEAVE"
        );

    }, [homeLeaves]);


    const returnedRecords = useMemo(() => {

        const term =
            search.trim().toLowerCase();


        let records = homeLeaves.filter(
            (leave) =>
                String(leave.status).toUpperCase()
                === "RETURNED"
        );


        if (!term) {

            return records;

        }


        return records.filter((leave) => {

            const values = [

                leave.leave_reference,

                leave.resident?.full_name,

                leave.reason_for_leave,

                leave.destination,

                leave.taken_by_name,

                leave.returned_by_name,

            ];


            return values.some(
                (value) =>
                    String(value ?? "")
                        .toLowerCase()
                        .includes(term)
            );

        });

    }, [homeLeaves, search]);


    const residentsCurrentlyAway = useMemo(() => {

        return new Set(
            onLeaveRecords.map(
                (leave) =>
                    String(leave.resident_id)
            )
        );

    }, [onLeaveRecords]);


    const availableResidents = useMemo(() => {

        return activeResidents.filter(
            (resident) =>
                !residentsCurrentlyAway.has(
                    String(resident.id)
                )
        );

    }, [
        activeResidents,
        residentsCurrentlyAway,
    ]);


    const openNewLeave = () => {

        setEditingLeave(null);

        setLeaveForm({
            ...EMPTY_LEAVE_FORM,

            leave_at:
                toLocalDateTimeInput(
                    new Date()
                ),
        });

        setError("");
        setSuccess("");
        setShowLeaveForm(true);

    };


    const openEditLeave = (leave) => {

        setEditingLeave(leave);

        setLeaveForm({

            resident_id:
                String(leave.resident_id ?? ""),

            leave_at:
                toInputDateTime(
                    leave.leave_at
                ),

            reason_for_leave:
                leave.reason_for_leave ?? "",

            destination:
                leave.destination ?? "",

            taken_by_name:
                leave.taken_by_name ?? "",

            taken_by_relationship:
                leave.taken_by_relationship ?? "",

            taken_by_contact:
                leave.taken_by_contact ?? "",

            expected_return_at:
                toInputDateTime(
                    leave.expected_return_at
                ),

            medication_handed_over:
                Boolean(
                    leave.medication_handed_over
                ),

            medication_instructions:
                leave.medication_instructions ?? "",

            belongings_taken:
                Boolean(
                    leave.belongings_taken
                ),

            belongings_notes:
                leave.belongings_notes ?? "",

            leave_notes:
                leave.leave_notes ?? "",

        });

        setError("");
        setSuccess("");
        setShowLeaveForm(true);

    };


    const changeLeave = (event) => {

        const {
            name,
            value,
            type,
            checked,
        } = event.target;


        setLeaveForm((current) => ({

            ...current,

            [name]:
                type === "checkbox"
                    ? checked
                    : value,

        }));

    };


    const saveLeave = async (event) => {

        event.preventDefault();

        try {

            setSaving(true);
            setError("");
            setSuccess("");


            const payload = {

                leave_at:
                    leaveForm.leave_at,

                reason_for_leave:
                    emptyToNull(
                        leaveForm.reason_for_leave
                    ),

                destination:
                    emptyToNull(
                        leaveForm.destination
                    ),

                taken_by_name:
                    leaveForm.taken_by_name,

                taken_by_relationship:
                    emptyToNull(
                        leaveForm.taken_by_relationship
                    ),

                taken_by_contact:
                    emptyToNull(
                        leaveForm.taken_by_contact
                    ),

                expected_return_at:
                    leaveForm.expected_return_at,

                medication_handed_over:
                    Boolean(
                        leaveForm.medication_handed_over
                    ),

                medication_instructions:
                    emptyToNull(
                        leaveForm.medication_instructions
                    ),

                belongings_taken:
                    Boolean(
                        leaveForm.belongings_taken
                    ),

                belongings_notes:
                    emptyToNull(
                        leaveForm.belongings_notes
                    ),

                leave_notes:
                    emptyToNull(
                        leaveForm.leave_notes
                    ),

            };


            if (editingLeave) {

                await api.put(
                    `/home-leaves/${editingLeave.id}`,
                    payload
                );

                setSuccess(
                    "Home leave details updated successfully."
                );

            }
            else {

                await api.post(
                    "/home-leaves",
                    {
                        resident_id:
                            Number(
                                leaveForm.resident_id
                            ),

                        ...payload,
                    }
                );

                setSuccess(
                    "Home leave recorded successfully."
                );

            }


            setShowLeaveForm(false);
            setEditingLeave(null);
            setLeaveForm(EMPTY_LEAVE_FORM);

            await loadPage();

        }
        catch (err) {

            console.error(
                "Unable to save home leave:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to save home leave."
                )
            );

        }
        finally {

            setSaving(false);

        }

    };


    const openReturn = (leave) => {

        setReturnLeave(leave);

        setReturnForm({
            ...EMPTY_RETURN_FORM,

            returned_at:
                toLocalDateTimeInput(
                    new Date()
                ),

            returned_by_name:
                leave.taken_by_name ?? "",

            returned_by_relationship:
                leave.taken_by_relationship ?? "",

            returned_by_contact:
                leave.taken_by_contact ?? "",
        });

        setError("");
        setSuccess("");

    };


    const changeReturn = (event) => {

        const {
            name,
            value,
        } = event.target;


        setReturnForm((current) => ({

            ...current,

            [name]: value,

        }));

    };


    const saveReturn = async (event) => {

        event.preventDefault();


        if (!returnLeave) {

            return;

        }


        try {

            setSaving(true);
            setError("");
            setSuccess("");


            await api.post(
                `/home-leaves/${returnLeave.id}/return`,
                {

                    returned_at:
                        returnForm.returned_at,

                    returned_by_name:
                        emptyToNull(
                            returnForm.returned_by_name
                        ),

                    returned_by_relationship:
                        emptyToNull(
                            returnForm.returned_by_relationship
                        ),

                    returned_by_contact:
                        emptyToNull(
                            returnForm.returned_by_contact
                        ),

                    condition_on_return:
                        returnForm.condition_on_return,

                    return_notes:
                        emptyToNull(
                            returnForm.return_notes
                        ),

                }
            );


            setReturnLeave(null);

            setReturnForm(
                EMPTY_RETURN_FORM
            );

            setSuccess(
                "Resident return recorded successfully."
            );

            await loadPage();

        }
        catch (err) {

            console.error(
                "Unable to record resident return:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to record resident return."
                )
            );

        }
        finally {

            setSaving(false);

        }

    };


    const deleteLeave = async (leave) => {

        const confirmed =
            window.confirm(
                `Delete home leave ${leave.leave_reference}?`
            );


        if (!confirmed) {

            return;

        }


        try {

            setError("");
            setSuccess("");


            await api.delete(
                `/home-leaves/${leave.id}`
            );


            setSuccess(
                "Home leave record deleted."
            );

            await loadPage();

        }
        catch (err) {

            setError(
                readApiError(
                    err,
                    "Unable to delete home leave."
                )
            );

        }

    };


    if (loading) {

        return (
            <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                Loading home leave records...
            </div>
        );

    }


    return (

        <div className="space-y-6">


            {/* Page Header */}

            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>

                    <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                        Resident Operations
                    </p>

                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Home Leave
                    </h1>

                    <p className="mt-2 text-slate-500">
                        Record residents leaving the facility and safely document their return.
                    </p>

                </div>


                <button
                    type="button"
                    onClick={openNewLeave}
                    className="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    + Record Home Leave
                </button>

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
                    title="Currently On Leave"
                    value={onLeaveRecords.length}
                    subtitle="Residents currently away"
                />

                <SummaryCard
                    title="Returned"
                    value={
                        homeLeaves.filter(
                            (leave) =>
                                String(
                                    leave.status
                                ).toUpperCase()
                                === "RETURNED"
                        ).length
                    }
                    subtitle="Completed leave records"
                />

                <SummaryCard
                    title="Available Residents"
                    value={availableResidents.length}
                    subtitle="Eligible for home leave"
                />

            </div>


            {/* Currently Away */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <SectionHeader
                    title="Currently On Home Leave"
                    subtitle="Residents who have left the facility and have not yet returned."
                    count={onLeaveRecords.length}
                />


                {onLeaveRecords.length === 0 ? (

                    <EmptyState
                        title="No residents are currently on home leave."
                        text="New home leave records will appear here until the resident returns."
                    />

                ) : (

                    <div className="divide-y divide-slate-100">

                        {onLeaveRecords.map((leave) => (

                            <div
                                key={leave.id}
                                className="p-5"
                            >

                                <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

                                    <div className="min-w-0">

                                        <div className="flex flex-wrap items-center gap-2">

                                            <h3 className="text-lg font-bold text-slate-800">
                                                {leave.resident?.full_name ?? "Resident"}
                                            </h3>

                                            <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                ON HOME LEAVE
                                            </span>

                                        </div>


                                        <p className="mt-1 text-sm font-medium text-slate-500">
                                            {leave.leave_reference}
                                        </p>


                                        <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">

                                            <Info
                                                label="Left Facility"
                                                value={formatDateTime(leave.leave_at)}
                                            />

                                            <Info
                                                label="Expected Return"
                                                value={formatDateTime(leave.expected_return_at)}
                                            />

                                            <Info
                                                label="With"
                                                value={personLabel(
                                                    leave.taken_by_name,
                                                    leave.taken_by_relationship
                                                )}
                                            />

                                            <Info
                                                label="Destination"
                                                value={leave.destination || "Not recorded"}
                                            />

                                        </div>

                                    </div>


                                    <div className="flex flex-wrap gap-2">

                                        <button
                                            type="button"
                                            onClick={() =>
                                                openEditLeave(leave)
                                            }
                                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                deleteLeave(leave)
                                            }
                                            className="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                                        >
                                            Delete
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                openReturn(leave)
                                            }
                                            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Record Return
                                        </button>

                                    </div>

                                </div>

                            </div>

                        ))}

                    </div>

                )}

            </section>


            {/* History */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div className="border-b border-slate-200 p-5">

                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                        <div>

                            <h2 className="text-lg font-bold text-slate-800">
                                Home Leave History
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                Completed resident home leave records.
                            </p>

                        </div>


                        <input
                            type="text"
                            value={search}
                            onChange={(event) =>
                                setSearch(event.target.value)
                            }
                            placeholder="Search resident or reference..."
                            className="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 md:w-72"
                        />

                    </div>

                </div>


                {returnedRecords.length === 0 ? (

                    <EmptyState
                        title="No completed home leave records."
                        text="Returned residents will appear here."
                    />

                ) : (

                    <div className="overflow-x-auto">

                        <table className="min-w-full divide-y divide-slate-200">

                            <thead className="bg-slate-50">

                                <tr>

                                    <TableHead>
                                        Resident
                                    </TableHead>

                                    <TableHead>
                                        Reference
                                    </TableHead>

                                    <TableHead>
                                        Left
                                    </TableHead>

                                    <TableHead>
                                        Returned
                                    </TableHead>

                                    <TableHead>
                                        Condition
                                    </TableHead>

                                    <TableHead>
                                        Recorded By
                                    </TableHead>

                                </tr>

                            </thead>


                            <tbody className="divide-y divide-slate-100 bg-white">

                                {returnedRecords.map((leave) => (

                                    <tr
                                        key={leave.id}
                                        className="hover:bg-slate-50"
                                    >

                                        <TableCell>

                                            <div className="font-semibold text-slate-800">
                                                {leave.resident?.full_name ?? "Resident"}
                                            </div>

                                            <div className="mt-1 text-xs text-slate-500">
                                                {leave.reason_for_leave || "No reason recorded"}
                                            </div>

                                        </TableCell>


                                        <TableCell>
                                            {leave.leave_reference}
                                        </TableCell>


                                        <TableCell>
                                            {formatDateTime(leave.leave_at)}
                                        </TableCell>


                                        <TableCell>
                                            {formatDateTime(leave.returned_at)}
                                        </TableCell>


                                        <TableCell>

                                            <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                {leave.condition_on_return || "Returned"}
                                            </span>

                                        </TableCell>


                                        <TableCell>

                                            <div>
                                                {leave.returnRecordedBy?.full_name ??
                                                    leave.recordedBy?.full_name ??
                                                    "—"}
                                            </div>

                                        </TableCell>

                                    </tr>

                                ))}

                            </tbody>

                        </table>

                    </div>

                )}

            </section>


            {/* Leave Modal */}

            {showLeaveForm && (

                <Modal
                    title={
                        editingLeave
                            ? "Edit Home Leave"
                            : "Record Home Leave"
                    }
                    description={
                        editingLeave
                            ? editingLeave.leave_reference
                            : "Record the resident's departure details."
                    }
                    onClose={() =>
                        setShowLeaveForm(false)
                    }
                >

                    <form
                        onSubmit={saveLeave}
                        className="space-y-6"
                    >

                        {!editingLeave && (

                            <Field
                                label="Resident"
                                required
                            >

                                <select
                                    name="resident_id"
                                    value={leaveForm.resident_id}
                                    onChange={changeLeave}
                                    required
                                    className={inputClass}
                                >

                                    <option value="">
                                        Select resident
                                    </option>

                                    {availableResidents.map(
                                        (resident) => (

                                            <option
                                                key={resident.id}
                                                value={resident.id}
                                            >
                                                {resident.full_name}
                                            </option>

                                        )
                                    )}

                                </select>

                            </Field>

                        )}


                        <div className="grid gap-4 md:grid-cols-2">

                            <Field
                                label="Leave Date & Time"
                                required
                            >

                                <input
                                    type="datetime-local"
                                    name="leave_at"
                                    value={leaveForm.leave_at}
                                    onChange={changeLeave}
                                    required
                                    className={inputClass}
                                />

                            </Field>


                            <Field
                                label="Expected Return"
                                required
                            >

                                <input
                                    type="datetime-local"
                                    name="expected_return_at"
                                    value={leaveForm.expected_return_at}
                                    onChange={changeLeave}
                                    required
                                    className={inputClass}
                                />

                            </Field>

                        </div>


                        <div className="grid gap-4 md:grid-cols-2">

                            <Field label="Reason for Leave">

                                <input
                                    type="text"
                                    name="reason_for_leave"
                                    value={leaveForm.reason_for_leave}
                                    onChange={changeLeave}
                                    placeholder="e.g. Family visit"
                                    className={inputClass}
                                />

                            </Field>


                            <Field label="Destination">

                                <input
                                    type="text"
                                    name="destination"
                                    value={leaveForm.destination}
                                    onChange={changeLeave}
                                    placeholder="e.g. Family home"
                                    className={inputClass}
                                />

                            </Field>

                        </div>


                        <FormSectionTitle>
                            Person Accompanying Resident
                        </FormSectionTitle>


                        <div className="grid gap-4 md:grid-cols-3">

                            <Field
                                label="Name"
                                required
                            >

                                <input
                                    type="text"
                                    name="taken_by_name"
                                    value={leaveForm.taken_by_name}
                                    onChange={changeLeave}
                                    required
                                    className={inputClass}
                                />

                            </Field>


                            <Field label="Relationship">

                                <input
                                    type="text"
                                    name="taken_by_relationship"
                                    value={leaveForm.taken_by_relationship}
                                    onChange={changeLeave}
                                    placeholder="e.g. Daughter"
                                    className={inputClass}
                                />

                            </Field>


                            <Field label="Contact Number">

                                <input
                                    type="text"
                                    name="taken_by_contact"
                                    value={leaveForm.taken_by_contact}
                                    onChange={changeLeave}
                                    className={inputClass}
                                />

                            </Field>

                        </div>


                        <FormSectionTitle>
                            Medication
                        </FormSectionTitle>


                        <CheckboxCard
                            name="medication_handed_over"
                            checked={
                                leaveForm.medication_handed_over
                            }
                            onChange={changeLeave}
                            title="Medication handed over"
                            text="Confirm medication required during home leave was provided."
                        />


                        {leaveForm.medication_handed_over && (

                            <Field label="Medication Instructions">

                                <textarea
                                    name="medication_instructions"
                                    value={leaveForm.medication_instructions}
                                    onChange={changeLeave}
                                    rows={3}
                                    className={inputClass}
                                />

                            </Field>

                        )}


                        <FormSectionTitle>
                            Belongings
                        </FormSectionTitle>


                        <CheckboxCard
                            name="belongings_taken"
                            checked={
                                leaveForm.belongings_taken
                            }
                            onChange={changeLeave}
                            title="Resident took belongings"
                            text="Record personal belongings taken during home leave."
                        />


                        {leaveForm.belongings_taken && (

                            <Field label="Belongings">

                                <textarea
                                    name="belongings_notes"
                                    value={leaveForm.belongings_notes}
                                    onChange={changeLeave}
                                    rows={3}
                                    placeholder="e.g. 1 bag, mobile phone"
                                    className={inputClass}
                                />

                            </Field>

                        )}


                        <Field label="Additional Notes">

                            <textarea
                                name="leave_notes"
                                value={leaveForm.leave_notes}
                                onChange={changeLeave}
                                rows={3}
                                className={inputClass}
                            />

                        </Field>


                        <ModalActions
                            saving={saving}
                            submitLabel={
                                editingLeave
                                    ? "Save Changes"
                                    : "Record Home Leave"
                            }
                            onCancel={() =>
                                setShowLeaveForm(false)
                            }
                        />

                    </form>

                </Modal>

            )}


            {/* Return Modal */}

            {returnLeave && (

                <Modal
                    title="Record Resident Return"
                    description={
                        `${returnLeave.resident?.full_name ?? "Resident"} · ${returnLeave.leave_reference}`
                    }
                    onClose={() =>
                        setReturnLeave(null)
                    }
                >

                    <form
                        onSubmit={saveReturn}
                        className="space-y-5"
                    >

                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">

                            <div className="grid gap-3 text-sm md:grid-cols-2">

                                <Info
                                    label="Left Facility"
                                    value={
                                        formatDateTime(
                                            returnLeave.leave_at
                                        )
                                    }
                                />

                                <Info
                                    label="Expected Return"
                                    value={
                                        formatDateTime(
                                            returnLeave.expected_return_at
                                        )
                                    }
                                />

                            </div>

                        </div>


                        <Field
                            label="Returned Date & Time"
                            required
                        >

                            <input
                                type="datetime-local"
                                name="returned_at"
                                value={returnForm.returned_at}
                                onChange={changeReturn}
                                required
                                className={inputClass}
                            />

                        </Field>


                        <FormSectionTitle>
                            Returned With
                        </FormSectionTitle>


                        <div className="grid gap-4 md:grid-cols-3">

                            <Field label="Name">

                                <input
                                    type="text"
                                    name="returned_by_name"
                                    value={returnForm.returned_by_name}
                                    onChange={changeReturn}
                                    className={inputClass}
                                />

                            </Field>


                            <Field label="Relationship">

                                <input
                                    type="text"
                                    name="returned_by_relationship"
                                    value={returnForm.returned_by_relationship}
                                    onChange={changeReturn}
                                    className={inputClass}
                                />

                            </Field>


                            <Field label="Contact">

                                <input
                                    type="text"
                                    name="returned_by_contact"
                                    value={returnForm.returned_by_contact}
                                    onChange={changeReturn}
                                    className={inputClass}
                                />

                            </Field>

                        </div>


                        <Field
                            label="Condition on Return"
                            required
                        >

                            <select
                                name="condition_on_return"
                                value={returnForm.condition_on_return}
                                onChange={changeReturn}
                                required
                                className={inputClass}
                            >

                                <option value="Stable">
                                    Stable
                                </option>

                                <option value="Well">
                                    Well
                                </option>

                                <option value="Requires Observation">
                                    Requires Observation
                                </option>

                                <option value="Requires Nurse Review">
                                    Requires Nurse Review
                                </option>

                                <option value="Requires Medical Review">
                                    Requires Medical Review
                                </option>

                            </select>

                        </Field>


                        <Field label="Return Notes">

                            <textarea
                                name="return_notes"
                                value={returnForm.return_notes}
                                onChange={changeReturn}
                                rows={4}
                                placeholder="Any observations when the resident returned..."
                                className={inputClass}
                            />

                        </Field>


                        <ModalActions
                            saving={saving}
                            submitLabel="Confirm Resident Return"
                            onCancel={() =>
                                setReturnLeave(null)
                            }
                        />

                    </form>

                </Modal>

            )}

        </div>

    );

}


/*
|--------------------------------------------------------------------------
| Small UI Components
|--------------------------------------------------------------------------
*/


function SummaryCard({
    title,
    value,
    subtitle,
}) {

    return (

        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p className="text-sm font-semibold text-slate-500">
                {title}
            </p>

            <p className="mt-2 text-3xl font-bold text-slate-800">
                {value}
            </p>

            <p className="mt-1 text-sm text-slate-500">
                {subtitle}
            </p>

        </div>

    );

}


function SectionHeader({
    title,
    subtitle,
    count,
}) {

    return (

        <div className="flex items-center justify-between border-b border-slate-200 p-5">

            <div>

                <h2 className="text-lg font-bold text-slate-800">
                    {title}
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                    {subtitle}
                </p>

            </div>


            <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-700">
                {count}
            </span>

        </div>

    );

}


function EmptyState({
    title,
    text,
}) {

    return (

        <div className="p-10 text-center">

            <p className="font-semibold text-slate-700">
                {title}
            </p>

            <p className="mt-1 text-sm text-slate-500">
                {text}
            </p>

        </div>

    );

}


function Field({
    label,
    required = false,
    children,
}) {

    return (

        <label className="block">

            <span className="mb-2 block text-sm font-semibold text-slate-700">

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


function CheckboxCard({
    name,
    checked,
    onChange,
    title,
    text,
}) {

    return (

        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">

            <input
                type="checkbox"
                name={name}
                checked={checked}
                onChange={onChange}
                className="mt-1 h-4 w-4"
            />

            <div>

                <p className="font-semibold text-slate-800">
                    {title}
                </p>

                <p className="mt-1 text-sm text-slate-500">
                    {text}
                </p>

            </div>

        </label>

    );

}


function Info({
    label,
    value,
}) {

    return (

        <div>

            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>

            <p className="mt-1 font-medium text-slate-700">
                {value || "—"}
            </p>

        </div>

    );

}


function TableHead({
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

        <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
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

            <div className="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">

                <div className="sticky top-0 z-10 flex items-start justify-between border-b border-slate-200 bg-white p-5">

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
                        className="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100"
                    >
                        ✕
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
    submitLabel,
    onCancel,
}) {

    return (

        <div className="flex justify-end gap-3 border-t border-slate-200 pt-5">

            <button
                type="button"
                onClick={onCancel}
                disabled={saving}
                className="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
                Cancel
            </button>


            <button
                type="submit"
                disabled={saving}
                className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {saving
                    ? "Saving..."
                    : submitLabel}
            </button>

        </div>

    );

}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/


const inputClass =
    "w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100";


function emptyToNull(value) {

    if (
        value === undefined ||
        value === null
    ) {
        return null;
    }

    const text =
        String(value).trim();

    return text === ""
        ? null
        : text;

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
        undefined,
        {
            dateStyle: "medium",
            timeStyle: "short",
        }
    );

}


function toLocalDateTimeInput(date) {

    const pad = (value) =>
        String(value).padStart(2, "0");


    return (
        date.getFullYear()
        + "-"
        + pad(date.getMonth() + 1)
        + "-"
        + pad(date.getDate())
        + "T"
        + pad(date.getHours())
        + ":"
        + pad(date.getMinutes())
    );

}


function toInputDateTime(value) {

    if (!value) {
        return "";
    }

    const date =
        new Date(value);

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return "";
    }

    return toLocalDateTimeInput(date);

}


function personLabel(
    name,
    relationship
) {

    if (!name) {
        return "Not recorded";
    }

    if (!relationship) {
        return name;
    }

    return `${name} (${relationship})`;

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

        const first =
            Object.values(
                data.errors
            )
                .flat()
                .find(Boolean);

        if (first) {

            return first;

        }

    }


    return fallback;

}


export default HomeLeave;