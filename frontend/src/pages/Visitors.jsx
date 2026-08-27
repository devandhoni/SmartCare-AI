import { useEffect, useMemo, useState } from "react";
import api from "../services/api";


const EMPTY_VISITOR_FORM = {
    resident_id: "",
    visitor_name: "",
    relationship: "",
    contact_number: "",
    id_number: "",
    purpose_of_visit: "",
    number_of_visitors: 1,
    visit_notes: "",
    checked_in_at: "",
};


const EMPTY_CHECKOUT_FORM = {
    checked_out_at: "",
    checkout_notes: "",
};


function Visitors() {

    const [residents, setResidents] = useState([]);
    const [visitors, setVisitors] = useState([]);

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [search, setSearch] = useState("");

    const [showVisitorForm, setShowVisitorForm] =
        useState(false);

    const [editingVisitor, setEditingVisitor] =
        useState(null);

    const [visitorForm, setVisitorForm] =
        useState({
            ...EMPTY_VISITOR_FORM,
            checked_in_at: localDateTimeValue(),
        });

    const [checkoutVisitor, setCheckoutVisitor] =
        useState(null);

    const [checkoutForm, setCheckoutForm] =
        useState({
            ...EMPTY_CHECKOUT_FORM,
            checked_out_at: localDateTimeValue(),
        });


    useEffect(() => {

        loadPage();

    }, []);


    const loadPage = async () => {

        try {

            setLoading(true);
            setError("");

            const [
                residentResponse,
                visitorResponse,
            ] = await Promise.all([

                api.get("/residents"),

                api.get("/visitors"),

            ]);


            const residentData =
                residentResponse.data?.residents ??
                residentResponse.data?.data ??
                residentResponse.data;


            const visitorData =
                visitorResponse.data?.visitors ??
                visitorResponse.data?.data ??
                visitorResponse.data;


            setResidents(
                Array.isArray(residentData)
                    ? residentData
                    : []
            );


            setVisitors(
                Array.isArray(visitorData)
                    ? visitorData
                    : []
            );

        }
        catch (err) {

            console.error(
                "Unable to load visitors:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to load visitor records."
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
                String(
                    resident.status
                ).toLowerCase()
                === "active"
        );

    }, [residents]);


    const checkedInVisitors = useMemo(() => {

        return visitors.filter(
            (visitor) =>
                String(
                    visitor.status
                ).toUpperCase()
                === "CHECKED_IN"
        );

    }, [visitors]);


    const checkedOutVisitors = useMemo(() => {

        const term =
            search.trim().toLowerCase();


        let records =
            visitors.filter(
                (visitor) =>
                    String(
                        visitor.status
                    ).toUpperCase()
                    === "CHECKED_OUT"
            );


        if (!term) {

            return records;

        }


        return records.filter(
            (visitor) => {

                const values = [

                    visitor.visit_reference,

                    visitor.visitor_name,

                    visitor.relationship,

                    visitor.contact_number,

                    visitor.id_number,

                    visitor.purpose_of_visit,

                    visitor.resident
                        ?.full_name,

                ];


                return values.some(
                    (value) =>
                        String(
                            value ?? ""
                        )
                            .toLowerCase()
                            .includes(term)
                );

            }
        );

    }, [visitors, search]);


    const totalVisitorsToday = useMemo(() => {

        const today =
            new Date();


        return visitors.filter(
            (visitor) => {

                if (
                    !visitor.checked_in_at
                ) {
                    return false;
                }


                const date =
                    new Date(
                        visitor.checked_in_at
                    );


                return (
                    date.getFullYear()
                        === today.getFullYear()
                    &&
                    date.getMonth()
                        === today.getMonth()
                    &&
                    date.getDate()
                        === today.getDate()
                );

            }
        ).length;

    }, [visitors]);


    const totalPeopleInside = useMemo(() => {

        return checkedInVisitors.reduce(
            (total, visitor) =>
                total
                +
                Number(
                    visitor.number_of_visitors
                    || 1
                ),
            0
        );

    }, [checkedInVisitors]);


    const openNewVisitor = () => {

        setEditingVisitor(null);

        setVisitorForm({
            ...EMPTY_VISITOR_FORM,

            number_of_visitors: 1,

            checked_in_at:
                localDateTimeValue(),
        });

        setError("");
        setSuccess("");
        setShowVisitorForm(true);

    };


    const openEditVisitor = (
        visitor
    ) => {

        setEditingVisitor(
            visitor
        );

        setVisitorForm({

            resident_id:
                String(
                    visitor.resident_id
                    ?? ""
                ),

            visitor_name:
                visitor.visitor_name
                ?? "",

            relationship:
                visitor.relationship
                ?? "",

            contact_number:
                visitor.contact_number
                ?? "",

            id_number:
                visitor.id_number
                ?? "",

            purpose_of_visit:
                visitor.purpose_of_visit
                ?? "",

            number_of_visitors:
                visitor.number_of_visitors
                ?? 1,

            visit_notes:
                visitor.visit_notes
                ?? "",

            checked_in_at:
                toInputDateTime(
                    visitor.checked_in_at
                ),
        });

        setError("");
        setSuccess("");
        setShowVisitorForm(true);

    };


    const changeVisitorForm = (
        event
    ) => {

        const {
            name,
            value,
        } = event.target;


        setVisitorForm(
            (current) => ({

                ...current,

                [name]:
                    value,

            })
        );

    };


    const saveVisitor = async (
        event
    ) => {

        event.preventDefault();

        try {

            setSaving(true);
            setError("");
            setSuccess("");


            const payload = {

                visitor_name:
                    visitorForm
                        .visitor_name
                        .trim(),

                relationship:
                    emptyToNull(
                        visitorForm
                            .relationship
                    ),

                contact_number:
                    emptyToNull(
                        visitorForm
                            .contact_number
                    ),

                id_number:
                    emptyToNull(
                        visitorForm
                            .id_number
                    ),

                purpose_of_visit:
                    emptyToNull(
                        visitorForm
                            .purpose_of_visit
                    ),

                number_of_visitors:
                    Number(
                        visitorForm
                            .number_of_visitors
                    ),

                visit_notes:
                    emptyToNull(
                        visitorForm
                            .visit_notes
                    ),

                checked_in_at:
                    visitorForm
                        .checked_in_at,

            };


            if (editingVisitor) {

                await api.put(
                    `/visitors/${editingVisitor.id}`,
                    payload
                );


                setSuccess(
                    "Visitor details updated successfully."
                );

            }
            else {

                await api.post(
                    "/visitors",
                    {

                        resident_id:
                            Number(
                                visitorForm
                                    .resident_id
                            ),

                        ...payload,

                    }
                );


                setSuccess(
                    "Visitor checked in successfully."
                );

            }


            setShowVisitorForm(false);

            setEditingVisitor(null);

            await loadPage();

        }
        catch (err) {

            console.error(
                "Unable to save visitor:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to save visitor record."
                )
            );

        }
        finally {

            setSaving(false);

        }

    };


    const openCheckout = (
        visitor
    ) => {

        setCheckoutVisitor(
            visitor
        );

        setCheckoutForm({

            checked_out_at:
                localDateTimeValue(),

            checkout_notes:
                "",

        });

        setError("");
        setSuccess("");

    };


    const changeCheckoutForm = (
        event
    ) => {

        const {
            name,
            value,
        } = event.target;


        setCheckoutForm(
            (current) => ({

                ...current,

                [name]:
                    value,

            })
        );

    };


    const saveCheckout = async (
        event
    ) => {

        event.preventDefault();


        if (
            !checkoutVisitor
        ) {
            return;
        }


        try {

            setSaving(true);
            setError("");
            setSuccess("");


            await api.post(
                `/visitors/${checkoutVisitor.id}/checkout`,
                {

                    checked_out_at:
                        checkoutForm
                            .checked_out_at,

                    checkout_notes:
                        emptyToNull(
                            checkoutForm
                                .checkout_notes
                        ),

                }
            );


            setCheckoutVisitor(
                null
            );

            setSuccess(
                "Visitor checked out successfully."
            );

            await loadPage();

        }
        catch (err) {

            console.error(
                "Unable to check out visitor:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to check out visitor."
                )
            );

        }
        finally {

            setSaving(false);

        }

    };


    const deleteVisitor = async (
        visitor
    ) => {

        const confirmed =
            window.confirm(
                `Delete visitor record ${visitor.visit_reference}?`
            );


        if (!confirmed) {

            return;

        }


        try {

            setError("");
            setSuccess("");


            await api.delete(
                `/visitors/${visitor.id}`
            );


            setSuccess(
                "Visitor record deleted successfully."
            );

            await loadPage();

        }
        catch (err) {

            console.error(
                "Unable to delete visitor:",
                err
            );

            setError(
                readApiError(
                    err,
                    "Unable to delete visitor record."
                )
            );

        }

    };


    if (loading) {

        return (

            <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                Loading visitor records...
            </div>

        );

    }


    return (

        <div className="space-y-6">


            {/* Header */}

            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>

                    <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
                        Resident Operations
                    </p>

                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Visitors
                    </h1>

                    <p className="mt-2 max-w-2xl text-slate-500">
                        Record visitor check-in and check-out for residents.
                    </p>

                </div>


                <button
                    type="button"
                    onClick={
                        openNewVisitor
                    }
                    className="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    + Check In Visitor
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
                    title="Visitors Inside"
                    value={
                        checkedInVisitors.length
                    }
                    subtitle="Active visitor records"
                />

                <SummaryCard
                    title="People Inside"
                    value={
                        totalPeopleInside
                    }
                    subtitle="Total visitors currently in facility"
                />

                <SummaryCard
                    title="Visits Today"
                    value={
                        totalVisitorsToday
                    }
                    subtitle="Visitor records today"
                />

            </div>


            {/* Currently Inside */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <SectionHeader
                    title="Visitors Currently Inside"
                    subtitle="Visitors who have checked in and have not yet checked out."
                    count={
                        checkedInVisitors.length
                    }
                />


                {
                    checkedInVisitors.length
                    === 0
                        ? (

                            <EmptyState
                                title="No visitors are currently inside."
                                text="New check-ins will appear here."
                            />

                        )
                        : (

                            <div className="divide-y divide-slate-100">

                                {
                                    checkedInVisitors.map(
                                        (visitor) => (

                                            <VisitorCard

                                                key={
                                                    visitor.id
                                                }

                                                visitor={
                                                    visitor
                                                }

                                                onEdit={
                                                    openEditVisitor
                                                }

                                                onCheckout={
                                                    openCheckout
                                                }

                                                onDelete={
                                                    deleteVisitor
                                                }

                                            />

                                        )
                                    )
                                }

                            </div>

                        )
                }

            </section>


            {/* History */}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div className="border-b border-slate-200 p-5">

                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                        <div>

                            <h2 className="text-lg font-bold text-slate-800">
                                Visitor History
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                Completed visitor check-in and check-out records.
                            </p>

                        </div>


                        <input
                            type="search"
                            value={
                                search
                            }
                            onChange={
                                (event) =>
                                    setSearch(
                                        event.target
                                            .value
                                    )
                            }
                            placeholder="Search visitor, resident or reference..."
                            className="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 md:w-80"
                        />

                    </div>

                </div>


                {
                    checkedOutVisitors.length
                    === 0
                        ? (

                            <EmptyState
                                title="No completed visitor records."
                                text="Checked-out visitors will appear here."
                            />

                        )
                        : (

                            <VisitorHistoryTable
                                visitors={
                                    checkedOutVisitors
                                }
                            />

                        )
                }

            </section>


            {/* Check-In Modal */}

            {
                showVisitorForm && (

                    <Modal

                        title={
                            editingVisitor
                                ? "Edit Visitor"
                                : "Check In Visitor"
                        }

                        description={
                            editingVisitor
                                ? editingVisitor
                                    .visit_reference
                                : "Enter the visitor details before allowing entry."
                        }

                        onClose={
                            () =>
                                setShowVisitorForm(
                                    false
                                )
                        }

                    >

                        <form
                            onSubmit={
                                saveVisitor
                            }
                            className="space-y-6"
                        >


                            {
                                !editingVisitor
                                && (

                                    <Field
                                        label="Resident Being Visited"
                                        required
                                    >

                                        <select

                                            required

                                            name="resident_id"

                                            value={
                                                visitorForm
                                                    .resident_id
                                            }

                                            onChange={
                                                changeVisitorForm
                                            }

                                            className={
                                                inputClass
                                            }

                                        >

                                            <option value="">
                                                Select active resident
                                            </option>


                                            {
                                                activeResidents.map(
                                                    (
                                                        resident
                                                    ) => (

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
                                                )
                                            }

                                        </select>

                                    </Field>

                                )
                            }


                            {
                                editingVisitor
                                && (

                                    <div className="rounded-xl bg-slate-50 p-4">

                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Resident Being Visited
                                        </p>

                                        <p className="mt-1 font-bold text-slate-800">
                                            {
                                                editingVisitor
                                                    .resident
                                                    ?.full_name
                                                ??
                                                "Resident"
                                            }
                                        </p>

                                    </div>

                                )
                            }


                            <FormSectionTitle>
                                Visitor Details
                            </FormSectionTitle>


                            <div className="grid gap-4 md:grid-cols-2">

                                <Field
                                    label="Visitor Name"
                                    required
                                >

                                    <input

                                        required

                                        type="text"

                                        name="visitor_name"

                                        value={
                                            visitorForm
                                                .visitor_name
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        placeholder="Full name"

                                        className={
                                            inputClass
                                        }

                                    />

                                </Field>


                                <Field label="Relationship">

                                    <select

                                        name="relationship"

                                        value={
                                            visitorForm
                                                .relationship
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        className={
                                            inputClass
                                        }

                                    >

                                        <option value="">
                                            Select relationship
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

                                        <option value="Brother">
                                            Brother
                                        </option>

                                        <option value="Sister">
                                            Sister
                                        </option>

                                        <option value="Relative">
                                            Relative
                                        </option>

                                        <option value="Friend">
                                            Friend
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

                                        name="contact_number"

                                        value={
                                            visitorForm
                                                .contact_number
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        placeholder="Phone number"

                                        className={
                                            inputClass
                                        }

                                    />

                                </Field>


                                <Field label="ID / IC / Passport Number">

                                    <input

                                        type="text"

                                        name="id_number"

                                        value={
                                            visitorForm
                                                .id_number
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        placeholder="Optional"

                                        className={
                                            inputClass
                                        }

                                    />

                                </Field>

                            </div>


                            <FormSectionTitle>
                                Visit Information
                            </FormSectionTitle>


                            <div className="grid gap-4 md:grid-cols-2">

                                <Field
                                    label="Check-In Date & Time"
                                    required
                                >

                                    <input

                                        required

                                        type="datetime-local"

                                        name="checked_in_at"

                                        value={
                                            visitorForm
                                                .checked_in_at
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        className={
                                            inputClass
                                        }

                                    />

                                </Field>


                                <Field
                                    label="Number of Visitors"
                                    required
                                >

                                    <input

                                        required

                                        type="number"

                                        min="1"

                                        max="20"

                                        name="number_of_visitors"

                                        value={
                                            visitorForm
                                                .number_of_visitors
                                        }

                                        onChange={
                                            changeVisitorForm
                                        }

                                        className={
                                            inputClass
                                        }

                                    />

                                </Field>

                            </div>


                            <Field label="Purpose of Visit">

                                <input

                                    type="text"

                                    name="purpose_of_visit"

                                    value={
                                        visitorForm
                                            .purpose_of_visit
                                    }

                                    onChange={
                                        changeVisitorForm
                                    }

                                    placeholder="e.g. Family visit"

                                    className={
                                        inputClass
                                    }

                                />

                            </Field>


                            <Field label="Visit Notes">

                                <textarea

                                    rows="4"

                                    name="visit_notes"

                                    value={
                                        visitorForm
                                            .visit_notes
                                    }

                                    onChange={
                                        changeVisitorForm
                                    }

                                    placeholder="Optional notes"

                                    className={
                                        inputClass
                                    }

                                />

                            </Field>


                            <ModalActions

                                saving={
                                    saving
                                }

                                submitLabel={
                                    editingVisitor
                                        ? "Save Changes"
                                        : "Check In Visitor"
                                }

                                onCancel={
                                    () =>
                                        setShowVisitorForm(
                                            false
                                        )
                                }

                            />

                        </form>

                    </Modal>

                )
            }


            {/* Checkout Modal */}

            {
                checkoutVisitor && (

                    <Modal

                        title="Check Out Visitor"

                        description={
                            `${checkoutVisitor.visitor_name} · ${checkoutVisitor.visit_reference}`
                        }

                        onClose={
                            () =>
                                setCheckoutVisitor(
                                    null
                                )
                        }

                    >

                        <form

                            onSubmit={
                                saveCheckout
                            }

                            className="space-y-5"

                        >


                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">

                                <div className="grid gap-4 md:grid-cols-2">

                                    <Info
                                        label="Resident"
                                        value={
                                            checkoutVisitor
                                                .resident
                                                ?.full_name
                                        }
                                    />

                                    <Info
                                        label="Visitor"
                                        value={
                                            visitorLabel(
                                                checkoutVisitor
                                            )
                                        }
                                    />

                                    <Info
                                        label="Checked In"
                                        value={
                                            formatDateTime(
                                                checkoutVisitor
                                                    .checked_in_at
                                            )
                                        }
                                    />

                                    <Info
                                        label="People"
                                        value={
                                            checkoutVisitor
                                                .number_of_visitors
                                        }
                                    />

                                </div>

                            </div>


                            <Field
                                label="Check-Out Date & Time"
                                required
                            >

                                <input

                                    required

                                    type="datetime-local"

                                    name="checked_out_at"

                                    value={
                                        checkoutForm
                                            .checked_out_at
                                    }

                                    onChange={
                                        changeCheckoutForm
                                    }

                                    className={
                                        inputClass
                                    }

                                />

                            </Field>


                            <Field label="Checkout Notes">

                                <textarea

                                    rows="4"

                                    name="checkout_notes"

                                    value={
                                        checkoutForm
                                            .checkout_notes
                                    }

                                    onChange={
                                        changeCheckoutForm
                                    }

                                    placeholder="Optional notes when visitor leaves"

                                    className={
                                        inputClass
                                    }

                                />

                            </Field>


                            <ModalActions

                                saving={
                                    saving
                                }

                                submitLabel="Confirm Check-Out"

                                submitClassName="bg-emerald-600 hover:bg-emerald-700"

                                onCancel={
                                    () =>
                                        setCheckoutVisitor(
                                            null
                                        )
                                }

                            />

                        </form>

                    </Modal>

                )
            }

        </div>

    );

}


/*
|--------------------------------------------------------------------------
| Visitor Card
|--------------------------------------------------------------------------
*/


function VisitorCard({
    visitor,
    onEdit,
    onCheckout,
    onDelete,
}) {

    return (

        <div className="p-5">

            <div className="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">

                <div className="min-w-0">

                    <div className="flex flex-wrap items-center gap-2">

                        <h3 className="text-lg font-bold text-slate-800">
                            {
                                visitor.visitor_name
                            }
                        </h3>


                        <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            INSIDE
                        </span>


                        {
                            Number(
                                visitor.number_of_visitors
                            ) > 1
                            && (

                                <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">

                                    {
                                        visitor.number_of_visitors
                                    }{" "}
                                    visitors

                                </span>

                            )
                        }

                    </div>


                    <p className="mt-1 text-sm font-medium text-blue-600">
                        {
                            visitor.visit_reference
                        }
                    </p>


                    <div className="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-4">

                        <Info

                            label="Visiting"

                            value={
                                visitor.resident
                                    ?.full_name
                            }

                        />


                        <Info

                            label="Relationship"

                            value={
                                visitor.relationship
                                || "—"
                            }

                        />


                        <Info

                            label="Checked In"

                            value={
                                formatDateTime(
                                    visitor.checked_in_at
                                )
                            }

                        />


                        <Info

                            label="Contact"

                            value={
                                visitor.contact_number
                                || "—"
                            }

                        />

                    </div>


                    {
                        visitor.purpose_of_visit
                        && (

                            <p className="mt-4 text-sm text-slate-600">

                                <span className="font-semibold">
                                    Purpose:
                                </span>{" "}

                                {
                                    visitor.purpose_of_visit
                                }

                            </p>

                        )
                    }

                </div>


                <div className="flex shrink-0 flex-wrap gap-2">

                    <button

                        type="button"

                        onClick={
                            () =>
                                onEdit(
                                    visitor
                                )
                        }

                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"

                    >
                        Edit
                    </button>


                    <button

                        type="button"

                        onClick={
                            () =>
                                onDelete(
                                    visitor
                                )
                        }

                        className="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"

                    >
                        Delete
                    </button>


                    <button

                        type="button"

                        onClick={
                            () =>
                                onCheckout(
                                    visitor
                                )
                        }

                        className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"

                    >
                        Check Out
                    </button>

                </div>

            </div>

        </div>

    );

}


/*
|--------------------------------------------------------------------------
| History Table
|--------------------------------------------------------------------------
*/


function VisitorHistoryTable({
    visitors,
}) {

    return (

        <div className="overflow-x-auto">

            <table className="min-w-full divide-y divide-slate-200">

                <thead className="bg-slate-50">

                    <tr>

                        <TableHead>
                            Visitor
                        </TableHead>

                        <TableHead>
                            Resident
                        </TableHead>

                        <TableHead>
                            Reference
                        </TableHead>

                        <TableHead>
                            Check-In
                        </TableHead>

                        <TableHead>
                            Check-Out
                        </TableHead>

                        <TableHead>
                            Staff
                        </TableHead>

                    </tr>

                </thead>


                <tbody className="divide-y divide-slate-100 bg-white">

                    {
                        visitors.map(
                            (visitor) => (

                                <tr

                                    key={
                                        visitor.id
                                    }

                                    className="hover:bg-slate-50"

                                >

                                    <TableCell>

                                        <div className="font-semibold text-slate-800">

                                            {
                                                visitor.visitor_name
                                            }

                                        </div>


                                        <div className="mt-1 text-xs text-slate-500">

                                            {
                                                visitor.relationship
                                                || "Relationship not recorded"
                                            }

                                        </div>

                                    </TableCell>


                                    <TableCell>

                                        {
                                            visitor.resident
                                                ?.full_name
                                            ?? "Resident"
                                        }

                                    </TableCell>


                                    <TableCell>

                                        <span className="font-medium text-blue-600">

                                            {
                                                visitor.visit_reference
                                            }

                                        </span>

                                    </TableCell>


                                    <TableCell>

                                        {
                                            formatDateTime(
                                                visitor.checked_in_at
                                            )
                                        }

                                    </TableCell>


                                    <TableCell>

                                        {
                                            formatDateTime(
                                                visitor.checked_out_at
                                            )
                                        }

                                    </TableCell>


                                    <TableCell>

                                        <div>

                                            {
                                                visitor.checkedOutBy
                                                    ?.full_name
                                                ??
                                                visitor.checkedInBy
                                                    ?.full_name
                                                ??
                                                "—"
                                            }

                                        </div>

                                    </TableCell>

                                </tr>

                            )
                        )
                    }

                </tbody>

            </table>

        </div>

    );

}


/*
|--------------------------------------------------------------------------
| Small Components
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

        <div className="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 className="text-lg font-bold text-slate-800">
                    {title}
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                    {subtitle}
                </p>

            </div>


            <span className="w-fit rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-700">
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

                {
                    required
                    && (

                        <span className="ml-1 text-red-500">
                            *
                        </span>

                    )
                }

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


function Info({
    label,
    value,
}) {

    return (

        <div>

            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>

            <p className="mt-1 break-words font-medium text-slate-700">
                {
                    value
                    ?? "—"
                }
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

                        {
                            description
                            && (

                                <p className="mt-1 text-sm text-slate-500">
                                    {description}
                                </p>

                            )
                        }

                    </div>


                    <button

                        type="button"

                        onClick={
                            onClose
                        }

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
    submitClassName =
        "bg-blue-600 hover:bg-blue-700",
}) {

    return (

        <div className="flex justify-end gap-3 border-t border-slate-200 pt-5">

            <button

                type="button"

                onClick={
                    onCancel
                }

                disabled={
                    saving
                }

                className="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"

            >
                Cancel
            </button>


            <button

                type="submit"

                disabled={
                    saving
                }

                className={`rounded-xl px-5 py-2.5 font-semibold text-white disabled:opacity-50 ${submitClassName}`}

            >

                {
                    saving
                        ? "Saving..."
                        : submitLabel
                }

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
    "w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100";


function localDateTimeValue() {

    return toLocalDateTimeInput(
        new Date()
    );

}


function toLocalDateTimeInput(
    date
) {

    const pad = (value) =>
        String(
            value
        ).padStart(
            2,
            "0"
        );


    return (
        date.getFullYear()
        +
        "-"
        +
        pad(
            date.getMonth()
            + 1
        )
        +
        "-"
        +
        pad(
            date.getDate()
        )
        +
        "T"
        +
        pad(
            date.getHours()
        )
        +
        ":"
        +
        pad(
            date.getMinutes()
        )
    );

}


function toInputDateTime(
    value
) {

    if (!value) {

        return "";

    }


    const date =
        new Date(
            value
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return "";

    }


    return toLocalDateTimeInput(
        date
    );

}


function emptyToNull(
    value
) {

    if (
        value === undefined
        ||
        value === null
    ) {

        return null;

    }


    const text =
        String(
            value
        ).trim();


    return (
        text === ""
            ? null
            : text
    );

}


function formatDateTime(
    value
) {

    if (!value) {

        return "—";

    }


    const date =
        new Date(
            value
        );


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


function visitorLabel(
    visitor
) {

    if (
        !visitor
    ) {

        return "—";

    }


    if (
        visitor.relationship
    ) {

        return (
            visitor.visitor_name
            +
            " ("
            +
            visitor.relationship
            +
            ")"
        );

    }


    return visitor.visitor_name;

}


function readApiError(
    error,
    fallback
) {

    const data =
        error?.response?.data;


    if (
        data?.message
    ) {

        return data.message;

    }


    if (
        data?.errors
    ) {

        const first =
            Object.values(
                data.errors
            )
                .flat()
                .find(
                    Boolean
                );


        if (
            first
        ) {

            return first;

        }

    }


    return fallback;

}


export default Visitors;