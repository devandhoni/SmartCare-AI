import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../services/api";

const STEPS = [
    { id: 1, title: "Resident", subtitle: "Personal & room details" },
    { id: 2, title: "Family", subtitle: "Primary contact" },
    { id: 3, title: "Medical History", subtitle: "Health & background" },
    { id: 4, title: "Hospital & Medication", subtitle: "Previous care & medicines" },
    { id: 5, title: "Care Assessment", subtitle: "Admission care needs" },
    { id: 6, title: "Consent", subtitle: "Agreement & consent" },
    { id: 7, title: "Review", subtitle: "Check & complete" },
];

const initialForm = {
    // Resident
    full_name: "",
    ic_number: "",
    date_of_birth: "",
    gender: "",
    nationality: "",
    address: "",
    phone: "",
    email: "",
    blood_type: "",
    room_id: "",

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

    // Resident medical master information
    medical_condition: "",
    allergies: "",
    chronic_disease: "",
    medical_notes: "",

    // Admission medical history
    primary_psychiatric_diagnosis: "",
    other_diagnoses: "",
    tobacco_use: false,
    tobacco_amount_frequency: "",
    alcohol_use: false,
    alcohol_amount_frequency: "",
    drug_use: false,
    drug_type_frequency: "",
    family_psychiatric_history: false,
    family_psychiatric_history_details: "",
    family_substance_abuse_history: false,
    family_substance_abuse_history_details: "",
    initial_observations: "",
    additional_comments: "",

    // Previous hospitalization rows
    hospitalizations: [
        {
            hospital_name: "",
            hospitalization_date: "",
            reason: "",
            notes: "",
        },
    ],

    // Medication setup rows
    medications: [
        {
            medication_id: "",
            medicine_search: "",
            dosage_instruction: "",
            dosage_quantity: "1",
            frequency: "",
            time_slot: "",
            scheduled_time: "",
            start_date: "",
            end_date: "",
            prescribed_by: "",
        },
    ],

    // Admission / care assessment
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

    // Structured Admission Agreement
    agreement_version: "1.0",
    agreement_title: "Consent / Admission Agreement",
    monthly_fee: "",
    medical_care_terms_acknowledged: false,
    payment_fee_terms_acknowledged: false,
    resident_conduct_terms_acknowledged: false,
    belongings_terms_acknowledged: false,
    termination_terms_acknowledged: false,
    emergency_liability_terms_acknowledged: false,
    risk_liability_terms_acknowledged: false,
    death_event_terms_acknowledged: false,
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
    const [rooms, setRooms] = useState([]);
    const [medicationOptions, setMedicationOptions] = useState([]);
    const [drafts, setDrafts] = useState([]);
    const [loadingDrafts, setLoadingDrafts] = useState(true);
    const [draftId, setDraftId] = useState(null);
    const [draftReference, setDraftReference] = useState("");
    const [savingDraft, setSavingDraft] = useState(false);
    const [discardingDraftId, setDiscardingDraftId] = useState(null);

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
        loadDrafts();
        loadRooms();
        loadMedicationOptions();
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

    const loadDrafts = async () => {
        try {
            setLoadingDrafts(true);
            const response = await api.get("/admission-drafts");
            setDrafts(Array.isArray(response.data?.drafts) ? response.data.drafts : []);
        } catch (err) {
            console.error("Unable to load admission drafts:", err);
            setDrafts([]);
        } finally {
            setLoadingDrafts(false);
        }
    };

    const loadRooms = async () => {
        try {
            const response = await api.get("/rooms");
            const data = response.data?.rooms ?? response.data;
            setRooms(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error("Unable to load rooms:", err);
            setRooms([]);
        }
    };

    const loadMedicationOptions = async () => {
        try {
            const response = await api.get("/medications");
            const data = response.data?.data ?? response.data?.medications ?? response.data;
            setMedicationOptions(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error("Unable to load medication catalogue:", err);
            setMedicationOptions([]);
        }
    };

    const updateHospitalization = (index, field, value) => {
        setForm((current) => ({
            ...current,
            hospitalizations: current.hospitalizations.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item
            ),
        }));
        setError("");
    };

    const addHospitalization = () => {
        setForm((current) => ({
            ...current,
            hospitalizations: [
                ...current.hospitalizations,
                { hospital_name: "", hospitalization_date: "", reason: "", notes: "" },
            ],
        }));
    };

    const removeHospitalization = (index) => {
        setForm((current) => ({
            ...current,
            hospitalizations:
                current.hospitalizations.length === 1
                    ? [{ hospital_name: "", hospitalization_date: "", reason: "", notes: "" }]
                    : current.hospitalizations.filter((_, itemIndex) => itemIndex !== index),
        }));
    };

    const updateMedication = (index, field, value) => {
        setForm((current) => ({
            ...current,
            medications: current.medications.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item
            ),
        }));
        setError("");
    };

    const addMedication = () => {
        setForm((current) => ({
            ...current,
            medications: [
                ...current.medications,
                {
                    medication_id: "",
                    medicine_search: "",
                    dosage_instruction: "",
                    dosage_quantity: "",
                    frequency: "",
                    time_slot: "",
                    scheduled_time: "",
                    start_date: "",
                    end_date: "",
                    prescribed_by: "",
                },
            ],
        }));
    };

    const removeMedication = (index) => {
        setForm((current) => ({
            ...current,
            medications:
                current.medications.length === 1
                    ? [{
                        medication_id: "",
                        dosage_instruction: "",
                        dosage_quantity: "",
                        frequency: "",
                        time_slot: "",
                        scheduled_time: "",
                        start_date: "",
                        end_date: "",
                        prescribed_by: "",
                    }]
                    : current.medications.filter((_, itemIndex) => itemIndex !== index),
        }));
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

        if (currentStep === 2) {
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

        if (currentStep === 4) {
            for (const medication of form.medications.filter(hasMedicationData)) {
                if (!medication.medication_id) return "Please select a medicine for each medication row.";
                if (!medication.dosage_instruction.trim()) return "Dosage instruction is required for each selected medicine.";
                if (!medication.frequency.trim()) return "Frequency is required for each selected medicine.";
                if (!medication.time_slot) return "Please choose AM, PM, NIGHT or OTHER for each selected medicine.";
                if (!Number.isInteger(Number(medication.dosage_quantity)) || Number(medication.dosage_quantity) < 1) return "Dosage quantity must be a whole number of at least 1.";
            }
        }

        if (currentStep === 5 && !form.admitted_at) {
            return "Admission date and time is required.";
        }

        if (currentStep === 6) {
            if (!form.consent_given_by.trim()) {
                return "Please enter the name of the person providing consent.";
            }

            const requiredConsents = [
                form.admission_consent,
                form.care_consent,
                form.medication_consent,
                form.emergency_treatment_consent,
                form.terms_acknowledged,
                form.medical_care_terms_acknowledged,
                form.payment_fee_terms_acknowledged,
                form.resident_conduct_terms_acknowledged,
                form.belongings_terms_acknowledged,
                form.termination_terms_acknowledged,
                form.emergency_liability_terms_acknowledged,
                form.risk_liability_terms_acknowledged,
                form.death_event_terms_acknowledged,
            ];

            if (!requiredConsents.every(Boolean)) {
                return "All required consent and admission agreement acknowledgements must be accepted before completing admission.";
            }
        }

        return "";
    };

    const completeAdmission = async () => {
        for (let currentStep = 1; currentStep <= 6; currentStep += 1) {
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
                phone: emptyToNull(form.phone),
                email: emptyToNull(form.email),
                room_id: emptyToNull(form.room_id),

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
                room_id: emptyToNull(form.room_id),
                medical_history: {
                    primary_psychiatric_diagnosis: emptyToNull(form.primary_psychiatric_diagnosis),
                    other_diagnoses: emptyToNull(form.other_diagnoses),
                    tobacco_use: form.tobacco_use,
                    tobacco_amount_frequency: emptyToNull(form.tobacco_amount_frequency),
                    alcohol_use: form.alcohol_use,
                    alcohol_amount_frequency: emptyToNull(form.alcohol_amount_frequency),
                    drug_use: form.drug_use,
                    drug_type_frequency: emptyToNull(form.drug_type_frequency),
                    family_psychiatric_history: form.family_psychiatric_history,
                    family_psychiatric_history_details: emptyToNull(form.family_psychiatric_history_details),
                    family_substance_abuse_history: form.family_substance_abuse_history,
                    family_substance_abuse_history_details: emptyToNull(form.family_substance_abuse_history_details),
                    initial_observations: emptyToNull(form.initial_observations),
                    additional_comments: emptyToNull(form.additional_comments),
                },
                hospitalizations: form.hospitalizations
                    .filter(hasHospitalizationData)
                    .map((item) => ({
                        hospital_name: emptyToNull(item.hospital_name),
                        hospitalization_date: emptyToNull(item.hospitalization_date),
                        reason: emptyToNull(item.reason),
                        notes: emptyToNull(item.notes),
                    })),
            });

            admissionId = admissionResponse.data?.admission?.id;

            if (!admissionId) {
                throw new Error("Admission was created but no admission ID was returned.");
            }

            // 4. Add admission medications through the existing resident medication workflow.
            for (const medication of form.medications.filter(hasMedicationData)) {
                if (!medication.medication_id) {
                    throw new Error("Please select a medicine for every medication row that contains medication details.");
                }

                await api.post(`/residents/${residentId}/medications`, {
                    medication_id: Number(medication.medication_id),
                    dosage_instruction: emptyToNull(medication.dosage_instruction),
                    dosage_quantity: Number(medication.dosage_quantity),
                    frequency: medication.frequency.trim(),
                    time_slot: medication.time_slot,
                    scheduled_time: emptyToNull(medication.scheduled_time),
                    start_date: emptyToNull(medication.start_date),
                    end_date: emptyToNull(medication.end_date),
                    prescribed_by: emptyToNull(medication.prescribed_by),
                });
            }

            // 5. Save consent / agreement.
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

                agreement_version: emptyToNull(form.agreement_version),
                agreement_title: emptyToNull(form.agreement_title),
                monthly_fee: form.monthly_fee === "" ? null : Number(form.monthly_fee),
                medical_care_terms_acknowledged: form.medical_care_terms_acknowledged,
                payment_fee_terms_acknowledged: form.payment_fee_terms_acknowledged,
                resident_conduct_terms_acknowledged: form.resident_conduct_terms_acknowledged,
                belongings_terms_acknowledged: form.belongings_terms_acknowledged,
                termination_terms_acknowledged: form.termination_terms_acknowledged,
                emergency_liability_terms_acknowledged: form.emergency_liability_terms_acknowledged,
                risk_liability_terms_acknowledged: form.risk_liability_terms_acknowledged,
                death_event_terms_acknowledged: form.death_event_terms_acknowledged,
            });

            // 6. Complete admission.
            await api.post(`/admissions/${admissionId}/complete`);

            setSuccess(
                `${form.full_name.trim()} has been admitted successfully.`
            );

            if (draftId) {
                try {
                    await api.delete(`/admission-drafts/${draftId}`);
                } catch (draftCleanupError) {
                    console.error("Admission completed, but draft cleanup failed:", draftCleanupError);
                }
            }

            resetAdmissionForm();
            await Promise.all([loadAdmissions(), loadDrafts()]);

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

    const saveDraft = async () => {
        setSavingDraft(true);
        setError("");
        setSuccess("");

        try {
            const payload = {
                full_name: form.full_name.trim() || null,
                current_step: step,
                form_data: form,
            };

            const response = draftId
                ? await api.put(`/admission-drafts/${draftId}`, payload)
                : await api.post("/admission-drafts", payload);

            const savedDraft = response.data?.draft;

            if (!savedDraft?.id) {
                throw new Error("Draft was saved but no draft ID was returned.");
            }

            setDraftId(savedDraft.id);
            setDraftReference(savedDraft.draft_reference || "");
            setSuccess(`Admission draft saved${savedDraft.draft_reference ? ` as ${savedDraft.draft_reference}` : ""}.`);
            await loadDrafts();
            window.scrollTo({ top: 0, behavior: "smooth" });
        } catch (err) {
            console.error("Unable to save admission draft:", err);
            const validationErrors = err.response?.data?.errors;
            const firstValidationError = validationErrors
                ? Object.values(validationErrors).flat().find(Boolean)
                : null;
            setError(firstValidationError || err.response?.data?.message || err.message || "Unable to save admission draft.");
            window.scrollTo({ top: 0, behavior: "smooth" });
        } finally {
            setSavingDraft(false);
        }
    };

    const resumeDraft = (draft) => {
        const savedForm = draft?.form_data && typeof draft.form_data === "object"
            ? draft.form_data
            : {};

        setForm(normalizeDraftForm(savedForm));
        setStep(Math.min(Math.max(Number(draft?.current_step) || 1, 1), STEPS.length));
        setDraftId(draft.id);
        setDraftReference(draft.draft_reference || "");
        setError("");
        setSuccess(`Resumed ${draft.draft_reference || "admission draft"}.`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const discardDraft = async (draft) => {
        const label = draft?.full_name || draft?.draft_reference || "this admission draft";
        if (!window.confirm(`Discard ${label}? This cannot be undone.`)) return;

        setDiscardingDraftId(draft.id);
        setError("");
        setSuccess("");

        try {
            await api.delete(`/admission-drafts/${draft.id}`);

            if (String(draftId) === String(draft.id)) {
                resetAdmissionForm();
            }

            setSuccess("Admission draft discarded.");
            await loadDrafts();
        } catch (err) {
            console.error("Unable to discard admission draft:", err);
            setError(err.response?.data?.message || err.message || "Unable to discard admission draft.");
        } finally {
            setDiscardingDraftId(null);
        }
    };

    const resetAdmissionForm = () => {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);

        setForm({ ...initialForm, admitted_at: local });
        setStep(1);
        setDraftId(null);
        setDraftReference("");
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
                            {draftId && (
                                <p className="mt-1 text-xs font-semibold text-blue-700">
                                    Draft: {draftReference || `#${draftId}`}
                                </p>
                            )}
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
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
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
                            description="Enter the resident's personal and contact information and assign a room where available."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field label="Full Name" name="full_name" value={form.full_name} onChange={updateField} required placeholder="Resident full name" />
                                <Field label="IC / Passport Number" name="ic_number" value={form.ic_number} onChange={updateField} placeholder="Identification number" />
                                <Field label="Date of Birth" name="date_of_birth" type="date" value={form.date_of_birth} onChange={updateField} />
                                <SelectField
                                    label="Gender"
                                    name="gender"
                                    value={form.gender}
                                    onChange={updateField}
                                    options={[["", "Select gender"], ["Male", "Male"], ["Female", "Female"], ["Other", "Other"]]}
                                />
                                <Field label="Nationality" name="nationality" value={form.nationality} onChange={updateField} placeholder="Example: Malaysian" />
                                <SelectField
                                    label="Blood Type"
                                    name="blood_type"
                                    value={form.blood_type}
                                    onChange={updateField}
                                    options={[["", "Select blood type"], ["A+", "A+"], ["A-", "A-"], ["B+", "B+"], ["B-", "B-"], ["AB+", "AB+"], ["AB-", "AB-"], ["O+", "O+"], ["O-", "O-"]]}
                                />
                                <Field label="Resident Phone" name="phone" value={form.phone} onChange={updateField} placeholder="+60..." />
                                <Field label="Resident Email" name="email" type="email" value={form.email} onChange={updateField} placeholder="Optional email" />
                                <SelectField
                                    label="Room"
                                    name="room_id"
                                    value={form.room_id}
                                    onChange={updateField}
                                    options={[
                                        ["", rooms.length ? "Select room" : "No rooms configured"],
                                        ...rooms.map((room) => [
                                            String(room.id),
                                            `Room ${room.room_number}${room.floor ? ` · ${room.floor}` : ""}${room.room_type ? ` · ${room.room_type}` : ""}`,
                                        ]),
                                    ]}
                                />
                            </div>
                            <TextArea label="Home Address" name="address" value={form.address} onChange={updateField} placeholder="Resident's home address" />
                        </StepCard>
                    )}

                    {step === 2 && (
                        <StepCard
                            title="Family & Emergency Contact"
                            description="Create the resident's primary family or guardian contact. These details will also appear in the resident Family tab."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field label="Contact Full Name" name="contact_full_name" value={form.contact_full_name} onChange={updateField} required placeholder="Family member / guardian" />
                                <Field label="Relationship" name="contact_relationship" value={form.contact_relationship} onChange={updateField} required placeholder="Example: Daughter" />
                                <Field label="Phone Number" name="contact_phone" value={form.contact_phone} onChange={updateField} required placeholder="+60..." />
                                <Field label="WhatsApp Number" name="contact_whatsapp" value={form.contact_whatsapp} onChange={updateField} placeholder="Leave blank to use phone number" />
                            </div>
                            <TextArea label="Contact Notes" name="contact_notes" value={form.contact_notes} onChange={updateField} placeholder="Optional communication notes" />
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <Toggle name="is_primary" checked={form.is_primary} onChange={updateField} title="Primary Contact" description="Main person to contact" />
                                <Toggle name="is_emergency_contact" checked={form.is_emergency_contact} onChange={updateField} title="Emergency Contact" description="May be contacted in emergencies" />
                                <Toggle name="whatsapp_enabled" checked={form.whatsapp_enabled} onChange={updateField} title="WhatsApp Enabled" description="Allow WhatsApp communication" />
                                <Toggle name="medication_notifications_enabled" checked={form.medication_notifications_enabled} onChange={updateField} title="Medication Updates" description="Receive medication notifications" />
                                <Toggle name="care_notifications_enabled" checked={form.care_notifications_enabled} onChange={updateField} title="Care Updates" description="Receive care notifications" />
                            </div>
                        </StepCard>
                    )}

                    {step === 3 && (
                        <StepCard
                            title="Medical History"
                            description="Record the resident's core clinical information and the structured medical, psychiatric and substance-use history collected during admission."
                        >
                            <SectionTitle title="Current Medical Information" />
                            <div className="grid gap-5 md:grid-cols-2">
                                <TextArea label="Medical Condition" name="medical_condition" value={form.medical_condition} onChange={updateField} placeholder="Current diagnoses or medical conditions" />
                                <TextArea label="Allergies" name="allergies" value={form.allergies} onChange={updateField} placeholder="Medication, food or other allergies" />
                                <TextArea label="Chronic Disease" name="chronic_disease" value={form.chronic_disease} onChange={updateField} placeholder="Diabetes, hypertension, etc." />
                                <TextArea label="Medical Notes" name="medical_notes" value={form.medical_notes} onChange={updateField} placeholder="Other important clinical information" />
                            </div>

                            <SectionTitle title="Admission Medical & Psychiatric History" />
                            <div className="grid gap-5 md:grid-cols-2">
                                <TextArea label="Primary Psychiatric Diagnosis" name="primary_psychiatric_diagnosis" value={form.primary_psychiatric_diagnosis} onChange={updateField} placeholder="If applicable" />
                                <TextArea label="Other Diagnoses" name="other_diagnoses" value={form.other_diagnoses} onChange={updateField} placeholder="Other relevant diagnoses" />
                            </div>

                            <div className="grid gap-4 lg:grid-cols-3">
                                <HistoryToggle
                                    title="Tobacco Use"
                                    name="tobacco_use"
                                    checked={form.tobacco_use}
                                    onChange={updateField}
                                    detailName="tobacco_amount_frequency"
                                    detailValue={form.tobacco_amount_frequency}
                                    detailPlaceholder="Amount / frequency"
                                />
                                <HistoryToggle
                                    title="Alcohol Use"
                                    name="alcohol_use"
                                    checked={form.alcohol_use}
                                    onChange={updateField}
                                    detailName="alcohol_amount_frequency"
                                    detailValue={form.alcohol_amount_frequency}
                                    detailPlaceholder="Amount / frequency"
                                />
                                <HistoryToggle
                                    title="Drug Use"
                                    name="drug_use"
                                    checked={form.drug_use}
                                    onChange={updateField}
                                    detailName="drug_type_frequency"
                                    detailValue={form.drug_type_frequency}
                                    detailPlaceholder="Type / frequency"
                                />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <HistoryToggle
                                    title="Family Psychiatric History"
                                    name="family_psychiatric_history"
                                    checked={form.family_psychiatric_history}
                                    onChange={updateField}
                                    detailName="family_psychiatric_history_details"
                                    detailValue={form.family_psychiatric_history_details}
                                    detailPlaceholder="Details"
                                />
                                <HistoryToggle
                                    title="Family Substance Abuse History"
                                    name="family_substance_abuse_history"
                                    checked={form.family_substance_abuse_history}
                                    onChange={updateField}
                                    detailName="family_substance_abuse_history_details"
                                    detailValue={form.family_substance_abuse_history_details}
                                    detailPlaceholder="Details"
                                />
                            </div>
                        </StepCard>
                    )}

                    {step === 4 && (
                        <StepCard
                            title="Previous Hospitalisation & Medication"
                            description="Record previous hospital stays and set up medications that should become part of the resident's normal medication workflow."
                        >
                            <SectionTitle
                                title="Previous Hospitalisations"
                                action={
                                    <button type="button" onClick={addHospitalization} className="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                                        + Add Hospitalisation
                                    </button>
                                }
                            />

                            <div className="space-y-4">
                                {form.hospitalizations.map((item, index) => (
                                    <div key={index} className="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h4 className="font-bold text-slate-700">Hospitalisation {index + 1}</h4>
                                            <button type="button" onClick={() => removeHospitalization(index)} className="text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Field label="Hospital Name" value={item.hospital_name} onChange={(e) => updateHospitalization(index, "hospital_name", e.target.value)} placeholder="Hospital / facility" />
                                            <Field label="Date" type="date" value={item.hospitalization_date} onChange={(e) => updateHospitalization(index, "hospitalization_date", e.target.value)} />
                                            <TextArea label="Reason" value={item.reason} onChange={(e) => updateHospitalization(index, "reason", e.target.value)} placeholder="Reason for hospitalisation" />
                                            <TextArea label="Notes" value={item.notes} onChange={(e) => updateHospitalization(index, "notes", e.target.value)} placeholder="Optional notes" />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <SectionTitle
                                title="Medication Setup"
                                action={
                                    <button type="button" onClick={addMedication} className="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                                        + Add Medication
                                    </button>
                                }
                            />

                            <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                                Medicines entered here use SmartCare's existing resident-medication workflow. Leave this section blank if medication will be configured later from the resident Medication tab.
                            </div>

                            <div className="space-y-4">
                                {form.medications.map((item, index) => (
                                    <div key={index} className="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h4 className="font-bold text-slate-700">Medication {index + 1}</h4>
                                            <button type="button" onClick={() => removeMedication(index)} className="text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                            <SearchableMedicineSelect
                                                medications={medicationOptions}
                                                selectedId={item.medication_id}
                                                searchValue={item.medicine_search || ""}
                                                onSearchChange={(value) => updateMedication(index, "medicine_search", value)}
                                                onSelect={(medicine) => {
                                                    updateMedication(index, "medication_id", String(medicine.id));
                                                    updateMedication(index, "medicine_search", medicineDisplayName(medicine));
                                                }}
                                                onClear={() => {
                                                    updateMedication(index, "medication_id", "");
                                                    updateMedication(index, "medicine_search", "");
                                                }}
                                            />
                                            <Field label="Dosage Instruction" value={item.dosage_instruction} onChange={(e) => updateMedication(index, "dosage_instruction", e.target.value)} placeholder="e.g. After food" />
                                            <Field label="Dosage Quantity" type="number" min="1" step="1" value={item.dosage_quantity} onChange={(e) => updateMedication(index, "dosage_quantity", e.target.value)} placeholder="1" />
                                            <Field label="Frequency" value={item.frequency} onChange={(e) => updateMedication(index, "frequency", e.target.value)} placeholder="e.g. Daily" />
                                            <SelectField
                                                label="Round / Time Slot"
                                                value={item.time_slot}
                                                onChange={(e) => updateMedication(index, "time_slot", e.target.value)}
                                                options={[["", "Select round"], ["AM", "AM"], ["PM", "PM"], ["NIGHT", "Night"], ["OTHER", "Other"]]}
                                            />
                                            <Field label="Scheduled Time" type="time" value={item.scheduled_time} onChange={(e) => updateMedication(index, "scheduled_time", e.target.value)} />
                                            <Field label="Start Date" type="date" value={item.start_date} onChange={(e) => updateMedication(index, "start_date", e.target.value)} />
                                            <Field label="End Date" type="date" value={item.end_date} onChange={(e) => updateMedication(index, "end_date", e.target.value)} />
                                            <Field label="Prescribed By" value={item.prescribed_by} onChange={(e) => updateMedication(index, "prescribed_by", e.target.value)} placeholder="Doctor / prescriber" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </StepCard>
                    )}

                    {step === 5 && (
                        <StepCard
                            title="Admission & Care Assessment"
                            description="Record the admission episode and the resident's immediate care needs, observations and belongings."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field label="Admission Date & Time" name="admitted_at" type="datetime-local" value={form.admitted_at} onChange={updateField} required />
                                <SelectField
                                    label="Admission Type"
                                    name="admission_type"
                                    value={form.admission_type}
                                    onChange={updateField}
                                    options={[["NEW_ADMISSION", "New Admission"], ["READMISSION", "Readmission"], ["TRANSFER", "Transfer"]]}
                                />
                                <Field label="Admission Source" name="admission_source" value={form.admission_source} onChange={updateField} placeholder="Home, hospital, other facility..." />
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <TextArea label="Reason for Admission" name="reason_for_admission" value={form.reason_for_admission} onChange={updateField} placeholder="Reason the resident is entering care" />
                                <TextArea label="Admission Medical Summary" name="medical_summary" value={form.medical_summary} onChange={updateField} placeholder="Clinical summary at admission" />
                                <TextArea label="Mobility Notes" name="mobility_notes" value={form.mobility_notes} onChange={updateField} placeholder="Walking aid, wheelchair, fall assistance..." />
                                <TextArea label="Dietary Notes" name="dietary_notes" value={form.dietary_notes} onChange={updateField} placeholder="Dietary restrictions or requirements" />
                                <TextArea label="Special Care Instructions" name="special_care_instructions" value={form.special_care_instructions} onChange={updateField} placeholder="Special monitoring or care instructions" />
                                <TextArea label="Belongings Notes" name="belongings_notes" value={form.belongings_notes} onChange={updateField} placeholder="Personal belongings received at admission" />
                                <TextArea label="Initial Observations" name="initial_observations" value={form.initial_observations} onChange={updateField} placeholder="Initial behavioural / clinical observations" />
                                <TextArea label="Additional Comments" name="additional_comments" value={form.additional_comments} onChange={updateField} placeholder="Any other admission comments" />
                            </div>
                        </StepCard>
                    )}

                    {step === 6 && (
                        <StepCard
                            title="Consent & Admission Agreement"
                            description="Record the person giving consent, core care permissions and each section of the structured admission agreement."
                        >
                            <SectionTitle title="Consent Provider" />
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                <Field label="Consent Given By" name="consent_given_by" value={form.consent_given_by} onChange={updateField} required placeholder="Resident / family member / guardian" />
                                <Field label="Relationship" name="consent_relationship" value={form.consent_relationship} onChange={updateField} placeholder="Example: Daughter / Self" />
                                <Field label="Contact Number" name="consent_contact_number" value={form.consent_contact_number} onChange={updateField} placeholder="+60..." />
                            </div>

                            <SectionTitle title="Care Permissions" />
                            <div className="grid gap-3 md:grid-cols-2">
                                <ConsentToggle name="admission_consent" checked={form.admission_consent} onChange={updateField} title="Admission Consent *" description="Consent is given for admission into the care facility." />
                                <ConsentToggle name="care_consent" checked={form.care_consent} onChange={updateField} title="Care Consent *" description="Consent is given for routine nursing and care activities." />
                                <ConsentToggle name="medication_consent" checked={form.medication_consent} onChange={updateField} title="Medication Consent *" description="Consent is given for prescribed medication administration." />
                                <ConsentToggle name="emergency_treatment_consent" checked={form.emergency_treatment_consent} onChange={updateField} title="Emergency Treatment Consent *" description="Consent is given for appropriate emergency treatment when required." />
                                <ConsentToggle name="information_sharing_consent" checked={form.information_sharing_consent} onChange={updateField} title="Information Sharing" description="Permit appropriate sharing of care information with relevant parties." />
                                <ConsentToggle name="family_notification_consent" checked={form.family_notification_consent} onChange={updateField} title="Family Notifications" description="Permit family care and medication notifications." />
                                <ConsentToggle name="terms_acknowledged" checked={form.terms_acknowledged} onChange={updateField} title="General Terms Acknowledged *" description="The admission terms and declarations have been understood." />
                            </div>

                            <SectionTitle title="Admission Agreement" />
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                <Field label="Agreement Title" name="agreement_title" value={form.agreement_title} onChange={updateField} />
                                <Field label="Agreement Version" name="agreement_version" value={form.agreement_version} onChange={updateField} placeholder="1.0" />
                                <Field label="Monthly Fee" name="monthly_fee" type="number" step="0.01" min="0" value={form.monthly_fee} onChange={updateField} placeholder="0.00" />
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                <ConsentToggle name="medical_care_terms_acknowledged" checked={form.medical_care_terms_acknowledged} onChange={updateField} title="1. Medical & Care Terms *" description="Medical and care responsibilities have been read and acknowledged." />
                                <ConsentToggle name="payment_fee_terms_acknowledged" checked={form.payment_fee_terms_acknowledged} onChange={updateField} title="2. Payment & Fee Terms *" description="Fees, payment responsibilities and related terms have been acknowledged." />
                                <ConsentToggle name="resident_conduct_terms_acknowledged" checked={form.resident_conduct_terms_acknowledged} onChange={updateField} title="3. Resident Conduct Terms *" description="Resident conduct and facility expectations have been acknowledged." />
                                <ConsentToggle name="belongings_terms_acknowledged" checked={form.belongings_terms_acknowledged} onChange={updateField} title="4. Belongings Terms *" description="Personal belongings responsibilities have been acknowledged." />
                                <ConsentToggle name="termination_terms_acknowledged" checked={form.termination_terms_acknowledged} onChange={updateField} title="5. Termination Terms *" description="Termination and discharge-related terms have been acknowledged." />
                                <ConsentToggle name="emergency_liability_terms_acknowledged" checked={form.emergency_liability_terms_acknowledged} onChange={updateField} title="6. Emergency & Liability Terms *" description="Emergency response and liability terms have been acknowledged." />
                                <ConsentToggle name="risk_liability_terms_acknowledged" checked={form.risk_liability_terms_acknowledged} onChange={updateField} title="7. Risk & Liability Terms *" description="Relevant risk and liability declarations have been acknowledged." />
                                <ConsentToggle name="death_event_terms_acknowledged" checked={form.death_event_terms_acknowledged} onChange={updateField} title="8. Death Event Terms *" description="The agreement provisions concerning a death event have been acknowledged." />
                            </div>

                            <TextArea label="Consent / Agreement Notes" name="consent_notes" value={form.consent_notes} onChange={updateField} placeholder="Optional guardian, witness or agreement notes" />

                            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                                All eight structured agreement sections are required in this digital workflow. Information-sharing and family-notification permissions remain explicit optional consents.
                            </div>
                        </StepCard>
                    )}

                    {step === 7 && (
                        <StepCard
                            title="Review & Complete Admission"
                            description="Review the admission before SmartCare creates and activates the resident's complete admission record."
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <ReviewCard
                                    title="Resident"
                                    rows={[
                                        ["Name", form.full_name],
                                        ["IC / Passport", form.ic_number],
                                        ["Phone", form.phone],
                                        ["Email", form.email],
                                        ["Room", roomLabel(form.room_id, rooms)],
                                        ["Blood Type", form.blood_type],
                                    ]}
                                    onEdit={() => setStep(1)}
                                />
                                <ReviewCard
                                    title="Family Contact"
                                    rows={[
                                        ["Name", form.contact_full_name],
                                        ["Relationship", form.contact_relationship],
                                        ["Phone", form.contact_phone],
                                        ["WhatsApp", form.contact_whatsapp || form.contact_phone],
                                    ]}
                                    onEdit={() => setStep(2)}
                                />
                                <ReviewCard
                                    title="Medical History"
                                    rows={[
                                        ["Condition", form.medical_condition],
                                        ["Allergies", form.allergies],
                                        ["Psychiatric Diagnosis", form.primary_psychiatric_diagnosis],
                                        ["Tobacco Use", yesNo(form.tobacco_use)],
                                        ["Alcohol Use", yesNo(form.alcohol_use)],
                                        ["Drug Use", yesNo(form.drug_use)],
                                    ]}
                                    onEdit={() => setStep(3)}
                                />
                                <ReviewCard
                                    title="Hospital & Medication"
                                    rows={[
                                        ["Hospitalisations", String(form.hospitalizations.filter(hasHospitalizationData).length)],
                                        ["Medications to Add", String(form.medications.filter(hasMedicationData).length)],
                                    ]}
                                    onEdit={() => setStep(4)}
                                />
                                <ReviewCard
                                    title="Care Assessment"
                                    rows={[
                                        ["Date & Time", displayDateTime(form.admitted_at)],
                                        ["Type", prettyLabel(form.admission_type)],
                                        ["Source", form.admission_source],
                                        ["Reason", form.reason_for_admission],
                                        ["Mobility", form.mobility_notes],
                                        ["Diet", form.dietary_notes],
                                    ]}
                                    onEdit={() => setStep(5)}
                                />
                                <ReviewCard
                                    title="Consent & Agreement"
                                    rows={[
                                        ["Given By", form.consent_given_by],
                                        ["Admission", yesNo(form.admission_consent)],
                                        ["Care", yesNo(form.care_consent)],
                                        ["Medication", yesNo(form.medication_consent)],
                                        ["Agreement", form.agreement_title],
                                        ["Version", form.agreement_version],
                                        ["Monthly Fee", form.monthly_fee ? `RM ${form.monthly_fee}` : "—"],
                                        ["8 Agreement Sections", agreementCount(form) === 8 ? "All acknowledged" : `${agreementCount(form)} of 8`],
                                    ]}
                                    onEdit={() => setStep(6)}
                                />
                            </div>

                            <div className="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                                <h3 className="font-bold text-slate-800">What SmartCare will create</h3>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    <Outcome label="Resident Profile" />
                                    <Outcome label="Family Contact" />
                                    <Outcome label="Admission Episode" />
                                    <Outcome label="Medical History" />
                                    <Outcome label="Hospitalisation History" />
                                    <Outcome label="Consent & Agreement" />
                                    <Outcome label="Medication Setup" />
                                </div>
                            </div>
                        </StepCard>
                    )}

                    <div className="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 lg:flex-row lg:items-center lg:justify-between">
                        <button
                            type="button"
                            onClick={goBack}
                            disabled={step === 1 || saving || savingDraft}
                            className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            ← Back
                        </button>

                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={saveDraft}
                                disabled={saving || savingDraft}
                                className="rounded-xl border border-amber-300 bg-amber-50 px-5 py-3 font-semibold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {savingDraft ? "Saving Draft..." : draftId ? "Save Draft Changes" : "Save Draft"}
                            </button>

                            {step < STEPS.length ? (
                                <button
                                    type="button"
                                    onClick={goNext}
                                    disabled={saving || savingDraft}
                                    className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                >
                                    Continue →
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={completeAdmission}
                                    disabled={saving || savingDraft}
                                    className="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {saving ? "Completing Admission..." : "✓ Complete Admission"}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <section className="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
                <div className="border-b border-amber-100 bg-amber-50/60 p-5 sm:p-6">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-slate-800">In Progress Admissions</h2>
                            <p className="mt-1 text-sm text-slate-500">Saved admission drafts can be resumed without creating a resident until admission is completed.</p>
                        </div>
                        <span className="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{drafts.length} Draft{drafts.length === 1 ? "" : "s"}</span>
                    </div>
                </div>

                {loadingDrafts ? (
                    <div className="p-8 text-center text-sm text-slate-500">Loading admission drafts...</div>
                ) : drafts.length === 0 ? (
                    <div className="p-8 text-center">
                        <p className="font-semibold text-slate-700">No admissions are currently in progress.</p>
                        <p className="mt-2 text-sm text-slate-500">Use Save Draft at any step when a nurse needs to continue later.</p>
                    </div>
                ) : (
                    <div className="divide-y divide-slate-100">
                        {drafts.map((draft) => (
                            <div key={draft.id} className={`flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6 ${String(draftId) === String(draft.id) ? "bg-blue-50/50" : ""}`}>
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="font-bold text-slate-800">{draft.full_name || "Unnamed Resident"}</h3>
                                        <StatusBadge status="DRAFT" />
                                        {String(draftId) === String(draft.id) && <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">Currently Open</span>}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">{draft.draft_reference} · Step {draft.current_step || 1} of {STEPS.length} · Updated {displayDateTime(draft.updated_at)}</p>
                                    {(draft.updater?.full_name || draft.creator?.full_name) && <p className="mt-1 text-xs text-slate-400">Last saved by {draft.updater?.full_name || draft.creator?.full_name}</p>}
                                </div>
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <button type="button" onClick={() => resumeDraft(draft)} disabled={saving || savingDraft || discardingDraftId === draft.id} className="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-50">Resume</button>
                                    <button type="button" onClick={() => discardDraft(draft)} disabled={saving || savingDraft || discardingDraftId === draft.id} className="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50">{discardingDraftId === draft.id ? "Discarding..." : "Discard"}</button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
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

function SearchableMedicineSelect({ medications, selectedId, searchValue, onSearchChange, onSelect, onClear }) {
    const [open, setOpen] = useState(false);
    const selected = medications.find((medicine) => String(medicine.id) === String(selectedId));
    const term = String(searchValue || "").trim().toLowerCase();
    const matches = medications.filter((medicine) => {
        if (!term) return true;
        return [medicine.medicine_name, medicine.category, medicine.dosage, medicine.unit, medicine.supplier]
            .filter(Boolean).join(" ").toLowerCase().includes(term);
    }).slice(0, 12);

    return (
        <div className="relative">
            <span className="mb-2 block text-sm font-semibold text-slate-700">Medicine</span>
            <div className="flex gap-2">
                <input
                    value={selected ? medicineDisplayName(selected) : searchValue}
                    onFocus={() => setOpen(true)}
                    onChange={(event) => {
                        if (selectedId) onClear();
                        onSearchChange(event.target.value);
                        setOpen(true);
                    }}
                    placeholder={medications.length ? "Type medicine name, strength, category..." : "No medicines available"}
                    className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                />
                {selectedId && (
                    <button type="button" onClick={() => { onClear(); setOpen(true); }} className="rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</button>
                )}
            </div>
            {open && !selectedId && (
                <div className="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                    {matches.length === 0 ? (
                        <div className="px-4 py-4 text-sm text-slate-500">No medicine found. Add it first in Inventory → Medicine Master.</div>
                    ) : matches.map((medicine) => (
                        <button key={medicine.id} type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => { onSelect(medicine); setOpen(false); }} className="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-blue-50">
                            <div className="font-semibold text-slate-800">{medicine.medicine_name}</div>
                            <div className="mt-1 text-xs text-slate-500">{[medicine.dosage, medicine.unit, medicine.category, medicine.supplier].filter(Boolean).join(" · ") || "No additional details"}</div>
                        </button>
                    ))}
                </div>
            )}
            {selected && <div className="mt-2 text-xs text-emerald-700">Selected from Medicine Master</div>}
        </div>
    );
}

function SectionTitle({ title, action = null }) {
    return (
        <div className="flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 className="text-base font-bold text-slate-800">{title}</h3>
            {action}
        </div>
    );
}

function HistoryToggle({
    title,
    name,
    checked,
    onChange,
    detailName,
    detailValue,
    detailPlaceholder,
}) {
    return (
        <div className={`rounded-xl border p-4 ${checked ? "border-blue-200 bg-blue-50" : "border-slate-200"}`}>
            <Toggle
                title={title}
                description={checked ? "Yes" : "No"}
                name={name}
                checked={checked}
                onChange={onChange}
            />
            {checked && (
                <input
                    name={detailName}
                    value={detailValue}
                    onChange={onChange}
                    placeholder={detailPlaceholder}
                    className="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                />
            )}
        </div>
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

function normalizeDraftForm(savedForm) {
    const merged = { ...initialForm, ...savedForm };

    merged.hospitalizations = Array.isArray(savedForm?.hospitalizations) && savedForm.hospitalizations.length
        ? savedForm.hospitalizations
        : initialForm.hospitalizations.map((item) => ({ ...item }));

    merged.medications = Array.isArray(savedForm?.medications) && savedForm.medications.length
        ? savedForm.medications.map((item) => ({
            medication_id: "",
            medicine_search: "",
            dosage_instruction: "",
            dosage_quantity: "1",
            frequency: "",
            time_slot: "",
            scheduled_time: "",
            start_date: "",
            end_date: "",
            prescribed_by: "",
            ...item,
        }))
        : initialForm.medications.map((item) => ({ ...item }));

    return merged;
}

function medicineDisplayName(medicine) {
    return [medicine?.medicine_name, medicine?.dosage, medicine?.unit].filter(Boolean).join(" · ");
}

function hasHospitalizationData(item) {
    return Boolean(
        item?.hospital_name?.trim() ||
        item?.hospitalization_date ||
        item?.reason?.trim() ||
        item?.notes?.trim()
    );
}

function hasMedicationData(item) {
    return Boolean(
        item?.medication_id ||
        item?.dosage_instruction?.trim() ||
        item?.dosage_quantity ||
        item?.frequency?.trim() ||
        item?.time_slot ||
        item?.scheduled_time ||
        item?.start_date ||
        item?.end_date ||
        item?.prescribed_by?.trim()
    );
}

function agreementCount(form) {
    return [
        form.medical_care_terms_acknowledged,
        form.payment_fee_terms_acknowledged,
        form.resident_conduct_terms_acknowledged,
        form.belongings_terms_acknowledged,
        form.termination_terms_acknowledged,
        form.emergency_liability_terms_acknowledged,
        form.risk_liability_terms_acknowledged,
        form.death_event_terms_acknowledged,
    ].filter(Boolean).length;
}

function roomLabel(roomId, rooms) {
    if (!roomId) return "Not assigned";
    const room = rooms.find((item) => String(item.id) === String(roomId));
    return room ? `Room ${room.room_number}` : `Room #${roomId}`;
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