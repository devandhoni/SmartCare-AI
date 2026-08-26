import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../services/api";

const STEPS = [
    { id: 1, title: "Resident", subtitle: "Personal details" },
    { id: 2, title: "Medical", subtitle: "Health information" },
    { id: 3, title: "Family", subtitle: "Primary contact" },
    { id: 4, title: "Admission", subtitle: "Admission details" },
    { id: 5, title: "Consent", subtitle: "Agreement & consent" },
    { id: 6, title: "Review", subtitle: "Check & complete" },
];

const initialForm = {
    // Resident
    full_name: "",
    ic_number: "",
    date_of_birth: "",
    gender: "",
    nationality: "",
    address: "",
    blood_type: "",

    // Medical
    medical_condition: "",
    allergies: "",
    chronic_disease: "",
    medical_notes: "",

    // Family / emergency contact
    contact_full_name: "",
    contact_relationship: "",
    contact_phone: "",
    contact_whatsapp: "",
    contact_notes: "",
    is_primary: true,
    is_emergency_contact: true,
    whatsapp_enabled: true,
    medication_notifications_enabled: true,
    care_notifications_enabled: true,

    // Admission
    admitted_at: "",
    admission_type: "NEW_ADMISSION",
    admission_source: "",
    reason_for_admission: "",
    medical_summary: "",
    mobility_notes: "",
    dietary_notes: "",
    special_care_instructions: "",
    belongings_notes: "",

    // Consent
    consent_given_by: "",
    consent_relationship: "",
    consent_contact_number: "",
    admission_consent: false,
    care_consent: false,
    medication_consent: false,
    emergency_treatment_consent: false,
    information_sharing_consent: false,
    family_notification_consent: false,
    terms_acknowledged: false,
    consent_notes: "",
};

function Admissions() {
    const navigate = useNavigate();

    const [step, setStep] = useState(1);
    const [form, setForm] = useState(initialForm);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [admissions, setAdmissions] = useState([]);
    const [loadingAdmissions, setLoadingAdmissions] = useState(true);

    useEffect(() => {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);

        setForm((current) => ({
            ...current,
            admitted_at: current.admitted_at || local,
        }));

        loadAdmissions();
    }, []);

    const loadAdmissions = async () => {
        try {
            setLoadingAdmissions(true);
            const response = await api.get("/admissions");
            setAdmissions(
                Array.isArray(response.data?.admissions)
                    ? response.data.admissions
                    : []
            );
        } catch (err) {
            console.error("Unable to load admissions:", err);
        } finally {
            setLoadingAdmissions(false);
        }
    };

    const updateField = (event) => {
        const { name, value, type, checked } = event.target;

        setForm((current) => ({
            ...current,
            [name]: type === "checkbox" ? checked : value,
        }));

        setError("");
    };

    const goNext = () => {
        const message = validateStep(step);

        if (message) {
            setError(message);
            window.scrollTo({ top: 0, behavior: "smooth" });
            return;
        }

        setError("");
        setStep((current) => Math.min(current + 1, STEPS.length));
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const goBack = () => {
        setError("");
        setStep((current) => Math.max(current - 1, 1));
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const validateStep = (currentStep) => {
        if (currentStep === 1 && !form.full_name.trim()) {
            return "Resident full name is required.";
        }

        if (currentStep === 3) {
            if (!form.contact_full_name.trim()) {
                return "Primary family/contact name is required.";
            }

            if (!form.contact_relationship.trim()) {
                return "Please enter the contact's relationship to the resident.";
            }

            if (!form.contact_phone.trim()) {
                return "Primary contact phone number is required.";
            }
        }

        if (currentStep === 4 && !form.admitted_at) {
            return "Admission date and time is required.";
        }

        if (currentStep === 5) {
            if (!form.consent_given_by.trim()) {
                return "Please enter the name of the person providing consent.";
            }

            const requiredConsents = [
                form.admission_consent,
                form.care_consent,
                form.medication_consent,
                form.emergency_treatment_consent,
                form.terms_acknowledged,
            ];

            if (!requiredConsents.every(Boolean)) {
                return "Admission, care, medication, emergency treatment and terms acknowledgement must be accepted before completing admission.";
            }
        }

        return "";
    };

    const completeAdmission = async () => {
        for (let currentStep = 1; currentStep <= 5; currentStep += 1) {
            const message = validateStep(currentStep);

            if (message) {
                setStep(currentStep);
                setError(message);
                window.scrollTo({ top: 0, behavior: "smooth" });
                return;
            }
        }

        setSaving(true);
        setError("");
        setSuccess("");

        let residentId = null;
        let admissionId = null;

        try {
            // 1. Create resident master profile.
            const residentResponse = await api.post("/residents", {
                full_name: form.full_name.trim(),
                ic_number: emptyToNull(form.ic_number),
                date_of_birth: emptyToNull(form.date_of_birth),
                gender: emptyToNull(form.gender),
                nationality: emptyToNull(form.nationality),
                address: emptyToNull(form.address),

                emergency_contact: form.contact_full_name.trim(),
                emergency_relationship: form.contact_relationship.trim(),
                emergency_phone: form.contact_phone.trim(),

                blood_type: emptyToNull(form.blood_type),
                medical_condition: emptyToNull(form.medical_condition),
                allergies: emptyToNull(form.allergies),
                chronic_disease: emptyToNull(form.chronic_disease),
                medical_notes: emptyToNull(form.medical_notes),

                admission_date: form.admitted_at.slice(0, 10),
                status: "Active",
            });

            residentId = residentResponse.data?.resident?.id;

            if (!residentId) {
                throw new Error("Resident was created but no resident ID was returned.");
            }

            // 2. Create the structured family / emergency contact.
            await api.post(`/residents/${residentId}/contacts`, {
                full_name: form.contact_full_name.trim(),
                relationship: form.contact_relationship.trim(),
                phone: form.contact_phone.trim(),
                whatsapp_number:
                    emptyToNull(form.contact_whatsapp) ||
                    form.contact_phone.trim(),
                is_primary: form.is_primary,
                is_emergency_contact: form.is_emergency_contact,
                whatsapp_enabled: form.whatsapp_enabled,
                medication_notifications_enabled:
                    form.medication_notifications_enabled,
                care_notifications_enabled:
                    form.care_notifications_enabled,
                notes: emptyToNull(form.contact_notes),
            });

            // 3. Create the admission episode.
            const admissionResponse = await api.post("/admissions", {
                resident_id: residentId,
                admitted_at: form.admitted_at,
                admission_type: form.admission_type,
                admission_source: emptyToNull(form.admission_source),
                reason_for_admission: emptyToNull(form.reason_for_admission),
                medical_summary:
                    emptyToNull(form.medical_summary) ||
                    emptyToNull(form.medical_condition),
                mobility_notes: emptyToNull(form.mobility_notes),
                dietary_notes: emptyToNull(form.dietary_notes),
                special_care_instructions:
                    emptyToNull(form.special_care_instructions),
                belongings_notes: emptyToNull(form.belongings_notes),
            });

            admissionId = admissionResponse.data?.admission?.id;

            if (!admissionId) {
                throw new Error("Admission was created but no admission ID was returned.");
            }

            // 4. Save consent / agreement.
            await api.post(`/admissions/${admissionId}/consent`, {
                consent_given_by: form.consent_given_by.trim(),
                relationship: emptyToNull(form.consent_relationship),
                contact_number: emptyToNull(form.consent_contact_number),
                admission_consent: form.admission_consent,
                care_consent: form.care_consent,
                medication_consent: form.medication_consent,
                emergency_treatment_consent:
                    form.emergency_treatment_consent,
                information_sharing_consent:
                    form.information_sharing_consent,
                family_notification_consent:
                    form.family_notification_consent,
                terms_acknowledged: form.terms_acknowledged,
                consent_notes: emptyToNull(form.consent_notes),
            });

            // 5. Complete admission.
            await api.post(`/admissions/${admissionId}/complete`);

            setSuccess(
                `${form.full_name.trim()} has been admitted successfully.`
            );

            setForm(initialForm);
            setStep(1);
            await loadAdmissions();

            window.scrollTo({ top: 0, behavior: "smooth" });
        } catch (err) {
            console.error("Admission workflow failed:", err);

            const validationErrors = err.response?.data?.errors;
            const firstValidationError = validationErrors
                ? Object.values(validationErrors).flat().find(Boolean)
                : null;

            let message =
                firstValidationError ||
                err.response?.data?.message ||
                err.message ||
                "Unable to complete the admission.";

            if (residentId && !admissionId) {
                message += ` The resident profile was already created (Resident #${residentId}). Please do not create the resident again until this admission is reviewed.`;
            } else if (residentId && admissionId) {
                message += ` Resident #${residentId} and Admission #${admissionId} were already created. Please review them before retrying.`;
            }

            setError(message);
            window.scrollTo({ top: 0, behavior: "smooth" });
        } finally {
            setSaving(false);
        }
    };

    const progress = useMemo(
        () => Math.round((step / STEPS.length) * 100),
        [step]
    );

    return (
        <div className="w-full min-w-0 space-y-6">
            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="p-5 sm:p-6 lg:p-8">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">
                                Resident Management
                            </p>

                            <h1 className="mt-2 text-2xl font-bold text-slate-800 sm:text-3xl">
                                Admissions
                            </h1>

                            <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base">
                                Complete one guided admission workflow. SmartCare will create the resident profile, primary family contact, admission episode and consent record.
                            </p>
                        </div>

                        <div className="rounded-xl bg-blue-50 px-4 py-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                Current Step
                            </p>
                            <p className="mt-1 font-bold text-slate-800">
                                {step} of {STEPS.length} · {STEPS[step - 1].title}
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full rounded-full bg-blue-600 transition-all duration-300"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            </section>

            {error && (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium leading-6 text-red-700">
                    {error}
                </div>
            )}

            {success && (
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium leading-6 text-emerald-700">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span>{success}</span>

                        <button
                            type="button"
                            onClick={() => navigate("/residents")}
                            className="rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700"
                        >
                            View Residents
                        </button>
                    </div>
                </div>
            )}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <div className="grid grid-cols-3 gap-2 lg:grid-cols-6">
                        {STEPS.map((item) => {
                            const active = item.id === step;
                            const complete = item.id < step;

                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => {
                                        if (item.id <= step) {
                                            setError("");
                                            setStep(item.id);
                                        }
                                    }}
                                    className={`rounded-xl px-3 py-3 text-left transition ${
                                        active
                                            ? "bg-blue-600 text-white shadow-sm"
                                            : complete
                                                ? "bg-blue-50 text-blue-700"
                                                : "bg-white text-slate-400"
                                    }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                                active
                                                    ? "bg-white text-blue-600"
                                                    : complete
                                                        ? "bg-blue-600 text-white"
                                                        : "bg-slate-100 text-slate-500"
                                            }`}
                                        >
                                            {complete ? "✓" : item.id}
                                        </span>

                                        <span className="min-w-0">
                                            <span className="block truncate text-xs font-bold sm:text-sm">
                                                {item.title}
                                            </span>
                                            <span
                                                className={`hidden truncate text-[11px] lg:block ${
                                                    active
                                                        ? "text-blue-100"
                                                        : "text-slate-400"
                                                }`}
                                            >
                                                {item.subtitle}
                                            </span>
                                        </span>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div className="p-4 sm:p-6 lg:p-8">
                    {step === 1 && (
                        <StepCard
                            title="Resident Details"
                            description="Enter the resident's core personal information. Only the full name is required by the current resident backend; the remaining fields can be completed where available."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Full Name"
                                    name="full_name"
                                    value={form.full_name}
                                    onChange={updateField}
                                    required
                                    placeholder="Resident full name"
                                />

                                <Field
                                    label="IC / Passport Number"
                                    name="ic_number"
                                    value={form.ic_number}
                                    onChange={updateField}
                                    placeholder="Identification number"
                                />

                                <Field
                                    label="Date of Birth"
                                    name="date_of_birth"
                                    type="date"
                                    value={form.date_of_birth}
                                    onChange={updateField}
                                />

                                <SelectField
                                    label="Gender"
                                    name="gender"
                                    value={form.gender}
                                    onChange={updateField}
                                    options={[
                                        ["", "Select gender"],
                                        ["Male", "Male"],
                                        ["Female", "Female"],
                                        ["Other", "Other"],
                                    ]}
                                />

                                <Field
                                    label="Nationality"
                                    name="nationality"
                                    value={form.nationality}
                                    onChange={updateField}
                                    placeholder="Example: Malaysian"
                                />

                                <SelectField
                                    label="Blood Type"
                                    name="blood_type"
                                    value={form.blood_type}
                                    onChange={updateField}
                                    options={[
                                        ["", "Select blood type"],
                                        ["A+", "A+"],
                                        ["A-", "A-"],
                                        ["B+", "B+"],
                                        ["B-", "B-"],
                                        ["AB+", "AB+"],
                                        ["AB-", "AB-"],
                                        ["O+", "O+"],
                                        ["O-", "O-"],
                                    ]}
                                />
                            </div>

                            <TextArea
                                label="Home Address"
                                name="address"
                                value={form.address}
                                onChange={updateField}
                                placeholder="Resident's home address"
                            />
                        </StepCard>
                    )}

                    {step === 2 && (
                        <StepCard
                            title="Medical Information"
                            description="Record the key health information staff should know at the point of admission."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <TextArea
                                    label="Medical Condition"
                                    name="medical_condition"
                                    value={form.medical_condition}
                                    onChange={updateField}
                                    placeholder="Current diagnoses or medical conditions"
                                />

                                <TextArea
                                    label="Allergies"
                                    name="allergies"
                                    value={form.allergies}
                                    onChange={updateField}
                                    placeholder="Medication, food or other allergies"
                                />

                                <TextArea
                                    label="Chronic Disease"
                                    name="chronic_disease"
                                    value={form.chronic_disease}
                                    onChange={updateField}
                                    placeholder="Diabetes, hypertension, etc."
                                />

                                <TextArea
                                    label="Medical Notes"
                                    name="medical_notes"
                                    value={form.medical_notes}
                                    onChange={updateField}
                                    placeholder="Other important clinical information"
                                />
                            </div>
                        </StepCard>
                    )}

                    {step === 3 && (
                        <StepCard
                            title="Family & Emergency Contact"
                            description="Create the resident's primary contact. This information will also be stored in the resident's Family tab."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Contact Full Name"
                                    name="contact_full_name"
                                    value={form.contact_full_name}
                                    onChange={updateField}
                                    required
                                    placeholder="Family member / guardian"
                                />

                                <Field
                                    label="Relationship"
                                    name="contact_relationship"
                                    value={form.contact_relationship}
                                    onChange={updateField}
                                    required
                                    placeholder="Example: Daughter"
                                />

                                <Field
                                    label="Phone Number"
                                    name="contact_phone"
                                    value={form.contact_phone}
                                    onChange={updateField}
                                    required
                                    placeholder="+60..."
                                />

                                <Field
                                    label="WhatsApp Number"
                                    name="contact_whatsapp"
                                    value={form.contact_whatsapp}
                                    onChange={updateField}
                                    placeholder="Leave blank to use phone number"
                                />
                            </div>

                            <TextArea
                                label="Contact Notes"
                                name="contact_notes"
                                value={form.contact_notes}
                                onChange={updateField}
                                placeholder="Optional notes about communication preferences or availability"
                            />

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <Toggle
                                    name="is_primary"
                                    checked={form.is_primary}
                                    onChange={updateField}
                                    title="Primary Contact"
                                    description="Main person to contact"
                                />

                                <Toggle
                                    name="is_emergency_contact"
                                    checked={form.is_emergency_contact}
                                    onChange={updateField}
                                    title="Emergency Contact"
                                    description="May be contacted in emergencies"
                                />

                                <Toggle
                                    name="whatsapp_enabled"
                                    checked={form.whatsapp_enabled}
                                    onChange={updateField}
                                    title="WhatsApp Enabled"
                                    description="Allow WhatsApp communication"
                                />

                                <Toggle
                                    name="medication_notifications_enabled"
                                    checked={form.medication_notifications_enabled}
                                    onChange={updateField}
                                    title="Medication Updates"
                                    description="Receive medication notifications"
                                />

                                <Toggle
                                    name="care_notifications_enabled"
                                    checked={form.care_notifications_enabled}
                                    onChange={updateField}
                                    title="Care Updates"
                                    description="Receive care notifications"
                                />
                            </div>
                        </StepCard>
                    )}

                    {step === 4 && (
                        <StepCard
                            title="Admission Details"
                            description="Record the admission episode. This stays separate from the resident master profile so SmartCare can support future readmissions."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Admission Date & Time"
                                    name="admitted_at"
                                    type="datetime-local"
                                    value={form.admitted_at}
                                    onChange={updateField}
                                    required
                                />

                                <SelectField
                                    label="Admission Type"
                                    name="admission_type"
                                    value={form.admission_type}
                                    onChange={updateField}
                                    options={[
                                        ["NEW_ADMISSION", "New Admission"],
                                        ["READMISSION", "Readmission"],
                                        ["TRANSFER", "Transfer"],
                                    ]}
                                />

                                <Field
                                    label="Admission Source"
                                    name="admission_source"
                                    value={form.admission_source}
                                    onChange={updateField}
                                    placeholder="Home, hospital, other facility..."
                                />
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <TextArea
                                    label="Reason for Admission"
                                    name="reason_for_admission"
                                    value={form.reason_for_admission}
                                    onChange={updateField}
                                    placeholder="Reason the resident is entering care"
                                />

                                <TextArea
                                    label="Admission Medical Summary"
                                    name="medical_summary"
                                    value={form.medical_summary}
                                    onChange={updateField}
                                    placeholder="Clinical summary at admission"
                                />

                                <TextArea
                                    label="Mobility Notes"
                                    name="mobility_notes"
                                    value={form.mobility_notes}
                                    onChange={updateField}
                                    placeholder="Walking aid, wheelchair, fall assistance..."
                                />

                                <TextArea
                                    label="Dietary Notes"
                                    name="dietary_notes"
                                    value={form.dietary_notes}
                                    onChange={updateField}
                                    placeholder="Dietary restrictions or requirements"
                                />

                                <TextArea
                                    label="Special Care Instructions"
                                    name="special_care_instructions"
                                    value={form.special_care_instructions}
                                    onChange={updateField}
                                    placeholder="Special monitoring or care instructions"
                                />

                                <TextArea
                                    label="Belongings Notes"
                                    name="belongings_notes"
                                    value={form.belongings_notes}
                                    onChange={updateField}
                                    placeholder="Important personal belongings received at admission"
                                />
                            </div>
                        </StepCard>
                    )}

                    {step === 5 && (
                        <StepCard
                            title="Consent & Admission Agreement"
                            description="Record who is providing consent and the permissions acknowledged during admission."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Consent Given By"
                                    name="consent_given_by"
                                    value={form.consent_given_by}
                                    onChange={updateField}
                                    required
                                    placeholder="Resident / family member / guardian"
                                />

                                <Field
                                    label="Relationship"
                                    name="consent_relationship"
                                    value={form.consent_relationship}
                                    onChange={updateField}
                                    placeholder="Example: Daughter / Self"
                                />

                                <Field
                                    label="Contact Number"
                                    name="consent_contact_number"
                                    value={form.consent_contact_number}
                                    onChange={updateField}
                                    placeholder="+60..."
                                />
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                <ConsentToggle
                                    name="admission_consent"
                                    checked={form.admission_consent}
                                    onChange={updateField}
                                    title="Admission Consent *"
                                    description="Consent is given for admission into the care facility."
                                />

                                <ConsentToggle
                                    name="care_consent"
                                    checked={form.care_consent}
                                    onChange={updateField}
                                    title="Care Consent *"
                                    description="Consent is given for routine nursing and care activities."
                                />

                                <ConsentToggle
                                    name="medication_consent"
                                    checked={form.medication_consent}
                                    onChange={updateField}
                                    title="Medication Consent *"
                                    description="Consent is given for prescribed medication administration."
                                />

                                <ConsentToggle
                                    name="emergency_treatment_consent"
                                    checked={form.emergency_treatment_consent}
                                    onChange={updateField}
                                    title="Emergency Treatment Consent *"
                                    description="Consent is given for appropriate emergency treatment when required."
                                />

                                <ConsentToggle
                                    name="information_sharing_consent"
                                    checked={form.information_sharing_consent}
                                    onChange={updateField}
                                    title="Information Sharing"
                                    description="Permit appropriate sharing of care information with relevant parties."
                                />

                                <ConsentToggle
                                    name="family_notification_consent"
                                    checked={form.family_notification_consent}
                                    onChange={updateField}
                                    title="Family Notifications"
                                    description="Permit family care and medication notifications."
                                />

                                <ConsentToggle
                                    name="terms_acknowledged"
                                    checked={form.terms_acknowledged}
                                    onChange={updateField}
                                    title="Terms Acknowledged *"
                                    description="The admission terms and declarations have been understood and acknowledged."
                                />
                            </div>

                            <TextArea
                                label="Consent Notes"
                                name="consent_notes"
                                value={form.consent_notes}
                                onChange={updateField}
                                placeholder="Optional consent, guardian or witness notes"
                            />

                            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                                Fields marked * are currently required by the admission completion workflow. Information-sharing and family-notification consent remain explicit optional permissions.
                            </div>
                        </StepCard>
                    )}

                    {step === 6 && (
                        <StepCard
                            title="Review & Complete Admission"
                            description="Check the information below before creating the resident and completing the admission."
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <ReviewCard
                                    title="Resident"
                                    rows={[
                                        ["Name", form.full_name],
                                        ["IC / Passport", form.ic_number],
                                        ["Date of Birth", form.date_of_birth],
                                        ["Gender", form.gender],
                                        ["Nationality", form.nationality],
                                        ["Blood Type", form.blood_type],
                                    ]}
                                    onEdit={() => setStep(1)}
                                />

                                <ReviewCard
                                    title="Medical"
                                    rows={[
                                        ["Condition", form.medical_condition],
                                        ["Allergies", form.allergies],
                                        ["Chronic Disease", form.chronic_disease],
                                        ["Notes", form.medical_notes],
                                    ]}
                                    onEdit={() => setStep(2)}
                                />

                                <ReviewCard
                                    title="Family Contact"
                                    rows={[
                                        ["Name", form.contact_full_name],
                                        ["Relationship", form.contact_relationship],
                                        ["Phone", form.contact_phone],
                                        [
                                            "WhatsApp",
                                            form.contact_whatsapp || form.contact_phone,
                                        ],
                                    ]}
                                    onEdit={() => setStep(3)}
                                />

                                <ReviewCard
                                    title="Admission"
                                    rows={[
                                        ["Date & Time", displayDateTime(form.admitted_at)],
                                        ["Type", prettyLabel(form.admission_type)],
                                        ["Source", form.admission_source],
                                        ["Reason", form.reason_for_admission],
                                        ["Mobility", form.mobility_notes],
                                        ["Diet", form.dietary_notes],
                                    ]}
                                    onEdit={() => setStep(4)}
                                />

                                <ReviewCard
                                    title="Consent"
                                    rows={[
                                        ["Given By", form.consent_given_by],
                                        ["Relationship", form.consent_relationship],
                                        ["Admission", yesNo(form.admission_consent)],
                                        ["Care", yesNo(form.care_consent)],
                                        ["Medication", yesNo(form.medication_consent)],
                                        [
                                            "Emergency Treatment",
                                            yesNo(form.emergency_treatment_consent),
                                        ],
                                        [
                                            "Family Notifications",
                                            yesNo(form.family_notification_consent),
                                        ],
                                    ]}
                                    onEdit={() => setStep(5)}
                                />
                            </div>

                            <div className="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                                <h3 className="font-bold text-slate-800">
                                    What SmartCare will create
                                </h3>

                                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <Outcome label="Resident Profile" />
                                    <Outcome label="Family Contact" />
                                    <Outcome label="Admission Episode" />
                                    <Outcome label="Consent Record" />
                                </div>
                            </div>
                        </StepCard>
                    )}

                    <div className="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            onClick={goBack}
                            disabled={step === 1 || saving}
                            className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            ← Back
                        </button>

                        {step < STEPS.length ? (
                            <button
                                type="button"
                                onClick={goNext}
                                disabled={saving}
                                className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                Continue →
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={completeAdmission}
                                disabled={saving}
                                className="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {saving
                                    ? "Completing Admission..."
                                    : "✓ Complete Admission"}
                            </button>
                        )}
                    </div>
                </div>
            </section>

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-5 sm:p-6">
                    <h2 className="text-lg font-bold text-slate-800">
                        Recent Admissions
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Admission episodes recorded in SmartCare-AI.
                    </p>
                </div>

                {loadingAdmissions ? (
                    <div className="p-8 text-center text-sm text-slate-500">
                        Loading admissions...
                    </div>
                ) : admissions.length === 0 ? (
                    <div className="p-8 text-center">
                        <p className="font-semibold text-slate-700">
                            No admission episodes recorded yet.
                        </p>
                        <p className="mt-2 text-sm text-slate-500">
                            Your first completed admission will appear here.
                        </p>
                    </div>
                ) : (
                    <div className="divide-y divide-slate-100">
                        {admissions.slice(0, 8).map((admission) => (
                            <div
                                key={admission.id}
                                className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="font-bold text-slate-800">
                                            {admission.resident?.full_name || "Resident"}
                                        </h3>

                                        <StatusBadge status={admission.status} />
                                    </div>

                                    <p className="mt-1 text-sm text-slate-500">
                                        {admission.admission_number} ·{" "}
                                        {displayDateTime(admission.admitted_at)}
                                    </p>
                                </div>

                                {admission.resident?.id && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            navigate(
                                                `/residents/${admission.resident.id}`
                                            )
                                        }
                                        className="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                                    >
                                        Open Resident
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}

function StepCard({ title, description, children }) {
    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-slate-800 sm:text-2xl">
                    {title}
                </h2>
                <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    {description}
                </p>
            </div>

            {children}
        </div>
    );
}

function Field({
    label,
    required = false,
    type = "text",
    ...props
}) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-semibold text-slate-700">
                {label} {required && <span className="text-red-500">*</span>}
            </span>

            <input
                type={type}
                {...props}
                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            />
        </label>
    );
}

function SelectField({ label, options, ...props }) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-semibold text-slate-700">
                {label}
            </span>

            <select
                {...props}
                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            >
                {options.map(([value, text]) => (
                    <option key={`${value}-${text}`} value={value}>
                        {text}
                    </option>
                ))}
            </select>
        </label>
    );
}

function TextArea({ label, ...props }) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-semibold text-slate-700">
                {label}
            </span>

            <textarea
                rows="4"
                {...props}
                className="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            />
        </label>
    );
}

function Toggle({
    title,
    description,
    name,
    checked,
    onChange,
}) {
    return (
        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
            <input
                type="checkbox"
                name={name}
                checked={checked}
                onChange={onChange}
                className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />

            <span>
                <span className="block text-sm font-bold text-slate-700">
                    {title}
                </span>
                <span className="mt-1 block text-xs leading-5 text-slate-500">
                    {description}
                </span>
            </span>
        </label>
    );
}

function ConsentToggle({
    title,
    description,
    name,
    checked,
    onChange,
}) {
    return (
        <label
            className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${
                checked
                    ? "border-emerald-200 bg-emerald-50"
                    : "border-slate-200 bg-white hover:bg-slate-50"
            }`}
        >
            <input
                type="checkbox"
                name={name}
                checked={checked}
                onChange={onChange}
                className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
            />

            <span>
                <span className="block text-sm font-bold text-slate-700">
                    {title}
                </span>
                <span className="mt-1 block text-xs leading-5 text-slate-500">
                    {description}
                </span>
            </span>
        </label>
    );
}

function ReviewCard({ title, rows, onEdit }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-bold text-slate-800">
                    {title}
                </h3>

                <button
                    type="button"
                    onClick={onEdit}
                    className="text-sm font-semibold text-blue-600 hover:text-blue-700"
                >
                    Edit
                </button>
            </div>

            <dl className="mt-4 space-y-3">
                {rows.map(([label, value]) => (
                    <div
                        key={label}
                        className="grid grid-cols-[120px_minmax(0,1fr)] gap-3 text-sm"
                    >
                        <dt className="text-slate-400">
                            {label}
                        </dt>
                        <dd className="break-words font-medium text-slate-700">
                            {value || "—"}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}

function Outcome({ label }) {
    return (
        <div className="flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm">
            <span className="text-emerald-600">✓</span>
            {label}
        </div>
    );
}

function StatusBadge({ status }) {
    const normalized = String(status || "").toUpperCase();

    const classes =
        normalized === "COMPLETED"
            ? "bg-emerald-50 text-emerald-700"
            : normalized === "DRAFT"
                ? "bg-amber-50 text-amber-700"
                : "bg-slate-100 text-slate-600";

    return (
        <span className={`rounded-full px-3 py-1 text-xs font-bold ${classes}`}>
            {prettyLabel(normalized || "UNKNOWN")}
        </span>
    );
}

function emptyToNull(value) {
    if (typeof value !== "string") return value ?? null;
    return value.trim() === "" ? null : value.trim();
}

function yesNo(value) {
    return value ? "Yes" : "No";
}

function prettyLabel(value) {
    return String(value || "")
        .replaceAll("_", " ")
        .toLowerCase()
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function displayDateTime(value) {
    if (!value) return "—";

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

export default Admissions;