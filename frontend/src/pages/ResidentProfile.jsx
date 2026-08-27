import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import api from "../services/api";
import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
} from "recharts";


const mapVitalFromApi = (vital) => {
    const dateValue =
        vital.recorded_at ||
        vital.created_on ||
        null;

    let recordedAt = "-";

    if (dateValue) {
        const date = new Date(dateValue);

        recordedAt = Number.isNaN(date.getTime())
            ? dateValue
            : date.toLocaleString("en-MY", {
                  day: "2-digit",
                  month: "short",
                  year: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
              });
    }

    return {
        id: vital.id,

        recordedAt,

        bloodPressure:
            vital.blood_pressure_systolic !== null &&
            vital.blood_pressure_systolic !== undefined &&
            vital.blood_pressure_diastolic !== null &&
            vital.blood_pressure_diastolic !== undefined
                ? `${vital.blood_pressure_systolic}/${vital.blood_pressure_diastolic}`
                : "-",

        pulse:
            vital.heart_rate ?? "-",

        spo2:
            vital.oxygen_level ?? "-",

        glucose:
            vital.blood_glucose ?? "-",

        weight:
            vital.weight ?? "-",

        temperature:
            vital.temperature ?? "-",

        notes: "",
    };
};

function ResidentProfile() {

    const { id } = useParams();
    const [residentApi, setResidentApi] = useState(null);
    const [latestVital, setLatestVital] = useState(null);
    const [activeAlerts, setActiveAlerts] = useState([]);
    const [pendingTasks, setPendingTasks] = useState([]);
    const [clinicalTimeline, setClinicalTimeline] = useState([]);
    const [timelineLoading, setTimelineLoading] = useState(false);
    const [timelineError, setTimelineError] = useState("");

    const [residentLoading, setResidentLoading] = useState(true);
    const [residentError, setResidentError] = useState("");

    const [activeTab, setActiveTab] = useState("overview");

    const [showVitalForm, setShowVitalForm] = useState(false);

const [vitalForm, setVitalForm] = useState({
    systolic: "",
    diastolic: "",
    pulse: "",
    spo2: "",
    glucose: "",
    weight: "",
    temperature: "",
    notes: "",
});


useEffect(() => {
    const loadResidentProfile = async () => {
        try {
            setResidentLoading(true);
            setResidentError("");

            const response = await api.get(`/residents/${id}/profile`);

            setResidentApi(response.data.resident || null);
            setLatestVital(response.data.latest_vital || null);
            setActiveAlerts(response.data.active_alerts || []);
            setPendingTasks(response.data.pending_tasks || []);
        } catch (error) {
            console.error("Failed to load resident profile:", error);

            setResidentApi(null);

            if (error.response?.status === 404) {
                setResidentError("Resident not found.");
            } else {
                setResidentError(
                    "Unable to load resident information. Please try again."
                );
            }
        } finally {
            setResidentLoading(false);
        }
    };

    loadResidentProfile();
}, [id]);

useEffect(() => {
    const loadVitals = async () => {
        try {
            setVitalsLoading(true);
            setVitalsError("");

            const response = await api.get(
                `/residents/${id}/vitals`
            );

            const vitals = Array.isArray(response.data?.vitals)
                ? response.data.vitals
                : [];

            const mappedVitals = vitals
                .map(mapVitalFromApi)
                .sort((a, b) => {
                    const aDate = new Date(
                        vitals.find(
                            (item) => item.id === a.id
                        )?.recorded_at ||
                        vitals.find(
                            (item) => item.id === a.id
                        )?.created_on ||
                        0
                    );

                    const bDate = new Date(
                        vitals.find(
                            (item) => item.id === b.id
                        )?.recorded_at ||
                        vitals.find(
                            (item) => item.id === b.id
                        )?.created_on ||
                        0
                    );

                    return bDate - aDate;
                });

            setVitalHistory((current) => ({
                ...current,
                [id]: mappedVitals,
            }));

            if (vitals.length > 0) {
                const newest = [...vitals].sort(
                    (a, b) =>
                        new Date(
                            b.recorded_at ||
                            b.created_on ||
                            0
                        ) -
                        new Date(
                            a.recorded_at ||
                            a.created_on ||
                            0
                        )
                )[0];

                setLatestVital(newest);
            }
        } catch (error) {
            console.error(
                "Failed to load resident vitals:",
                error
            );

            setVitalsError(
                "Unable to load vital signs."
            );
        } finally {
            setVitalsLoading(false);
        }
    };

    loadVitals();
}, [id]);


useEffect(() => {
    const loadClinicalTimeline = async () => {
        try {
            setTimelineLoading(true);
            setTimelineError("");

            const response = await api.get(
                `/residents/${id}/timeline`
            );

            setClinicalTimeline(
                Array.isArray(response.data?.timeline)
                    ? response.data.timeline
                    : []
            );
        } catch (error) {
            console.error(
                "Failed to load clinical timeline:",
                error
            );

            setClinicalTimeline([]);

            setTimelineError(
                error.response?.data?.message ||
                "Unable to load resident timeline."
            );
        } finally {
            setTimelineLoading(false);
        }
    };

    loadClinicalTimeline();
}, [id]);

const [vitalHistory, setVitalHistory] = useState({});
const [vitalsLoading, setVitalsLoading] = useState(false);
const [vitalsError, setVitalsError] = useState("");
const [savingVitals, setSavingVitals] = useState(false);


const [careApiRecords, setCareApiRecords] = useState([]);
const [careLoading, setCareLoading] = useState(false);
const [careError, setCareError] = useState("");
const [showCareForm, setShowCareForm] = useState(false);
const [savingCareRecord, setSavingCareRecord] = useState(false);
const [editingCareRecordId, setEditingCareRecordId] = useState(null);
const [careForm, setCareForm] = useState({
    care_type: "Personal Care",
    title: "",
    notes: "",
    care_status: "COMPLETED",
    recorded_at: "",
});

const careTypeOptions = [
    "Personal Care",
    "Nutrition",
    "Mobility",
    "Toileting",
    "Skin / Wound",
    "Behaviour",
    "Observation",
    "Nursing Note",
    "Incident",
    "Environment",
    "Other",
];

const loadCareRecords = async () => {
    try {
        setCareLoading(true);
        setCareError("");

        const response = await api.get(
            `/residents/${id}/care-records`
        );

        setCareApiRecords(
            Array.isArray(response.data?.care_records)
                ? response.data.care_records
                : []
        );
    } catch (error) {
        console.error("Failed to load care records:", error);

        setCareApiRecords([]);

        setCareError(
            error.response?.data?.message ||
            "Unable to load care records. Please try again."
        );
    } finally {
        setCareLoading(false);
    }
};

useEffect(() => {
    loadCareRecords();
}, [id]);

const [documentRecords, setDocumentRecords] = useState([]);
const [documentsLoading, setDocumentsLoading] = useState(false);
const [documentsError, setDocumentsError] = useState("");
const [showDocumentForm, setShowDocumentForm] = useState(false);
const [savingDocument, setSavingDocument] = useState(false);
const [editingDocumentId, setEditingDocumentId] = useState(null);
const [selectedDocumentFile, setSelectedDocumentFile] = useState(null);

const [documentForm, setDocumentForm] = useState({
    document_type: "Admission",
    title: "",
    notes: "",
    status: "ACTIVE",
});

const documentTypeOptions = [
    "Admission",
    "Consent",
    "Medical",
    "Clinical",
    "Lab Result",
    "Medication",
    "Care",
    "Discharge",
    "Home Leave",
    "Identification",
    "Other",
];

const loadResidentDocuments = async () => {
    try {
        setDocumentsLoading(true);
        setDocumentsError("");

        const response = await api.get(`/residents/${id}/documents`);

        setDocumentRecords(
            Array.isArray(response.data?.documents)
                ? response.data.documents
                : []
        );
    } catch (error) {
        console.error("Failed to load resident documents:", error);
        setDocumentRecords([]);
        setDocumentsError(
            error.response?.data?.message ||
            "Unable to load resident documents. Please try again."
        );
    } finally {
        setDocumentsLoading(false);
    }
};

useEffect(() => {
    loadResidentDocuments();
}, [id]);

const resetDocumentForm = () => {
    setDocumentForm({
        document_type: "Admission",
        title: "",
        notes: "",
        status: "ACTIVE",
    });
    setSelectedDocumentFile(null);
    setEditingDocumentId(null);
};

const openNewDocumentForm = () => {
    resetDocumentForm();
    setDocumentsError("");
    setShowDocumentForm(true);
};

const openEditDocumentForm = (document) => {
    setDocumentForm({
        document_type: document.document_type || "Other",
        title: document.title || "",
        notes: document.notes || "",
        status: document.status || "ACTIVE",
    });
    setSelectedDocumentFile(null);
    setEditingDocumentId(document.id);
    setDocumentsError("");
    setShowDocumentForm(true);
};

const handleDocumentFormChange = (event) => {
    const { name, value } = event.target;
    setDocumentForm((current) => ({
        ...current,
        [name]: value,
    }));
};

const handleDocumentFileChange = (event) => {
    const file = event.target.files?.[0] || null;
    setSelectedDocumentFile(file);

    if (file && !documentForm.title.trim()) {
        const titleFromFile = file.name.replace(/\.[^/.]+$/, "");
        setDocumentForm((current) => ({
            ...current,
            title: titleFromFile,
        }));
    }
};

const handleSaveDocument = async (event) => {
    event.preventDefault();

    if (!documentForm.title.trim()) {
        setDocumentsError("Please enter a document title.");
        return;
    }

    if (!editingDocumentId && !selectedDocumentFile) {
        setDocumentsError("Please choose a document to upload.");
        return;
    }

    try {
        setSavingDocument(true);
        setDocumentsError("");

        if (editingDocumentId) {
            await api.put(`/resident-documents/${editingDocumentId}`, {
                document_type: documentForm.document_type,
                title: documentForm.title.trim(),
                notes: documentForm.notes.trim() || null,
                status: documentForm.status,
            });
        } else {
            const payload = new FormData();
            payload.append("document_type", documentForm.document_type);
            payload.append("title", documentForm.title.trim());

            if (documentForm.notes.trim()) {
                payload.append("notes", documentForm.notes.trim());
            }

            payload.append("document", selectedDocumentFile);

            await api.post(`/residents/${id}/documents`, payload, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });
        }

        await loadResidentDocuments();
        setShowDocumentForm(false);
        resetDocumentForm();
    } catch (error) {
        console.error("Failed to save resident document:", error);

        if (error.response?.status === 422 && error.response?.data?.errors) {
            const firstValidationError = Object.values(
                error.response.data.errors
            ).flat().find(Boolean);

            setDocumentsError(
                firstValidationError ||
                "Please check the document information."
            );
        } else {
            setDocumentsError(
                error.response?.data?.message ||
                "Unable to save the document. Please try again."
            );
        }
    } finally {
        setSavingDocument(false);
    }
};

const handleDownloadDocument = async (document) => {
    try {
        setDocumentsError("");

        const response = await api.get(
            `/resident-documents/${document.id}/download`,
            { responseType: "blob" }
        );

        const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
        const link = window.document.createElement("a");
        link.href = blobUrl;
        link.download = document.original_name || document.title || "resident-document";
        window.document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(blobUrl);
    } catch (error) {
        console.error("Failed to download document:", error);
        setDocumentsError(
            error.response?.data?.message ||
            "Unable to download the document. Please try again."
        );
    }
};

const handleArchiveDocument = async (document) => {
    const nextStatus =
        String(document.status || "").toUpperCase() === "ARCHIVED"
            ? "ACTIVE"
            : "ARCHIVED";

    try {
        setDocumentsError("");
        await api.put(`/resident-documents/${document.id}`, {
            status: nextStatus,
        });
        await loadResidentDocuments();
    } catch (error) {
        console.error("Failed to update document status:", error);
        setDocumentsError(
            error.response?.data?.message ||
            "Unable to update the document status. Please try again."
        );
    }
};

const handleDeleteDocument = async (document) => {
    const confirmed = window.confirm(
        `Delete "${document.title}" from ${resident.name}'s documents? This will also remove the uploaded file.`
    );

    if (!confirmed) return;

    try {
        setDocumentsError("");
        await api.delete(`/resident-documents/${document.id}`);
        await loadResidentDocuments();
    } catch (error) {
        console.error("Failed to delete document:", error);
        setDocumentsError(
            error.response?.data?.message ||
            "Unable to delete the document. Please try again."
        );
    }
};

const formatDocumentDate = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleString("en-MY", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDocumentSize = (bytes) => {
    const size = Number(bytes);
    if (!Number.isFinite(size) || size <= 0) return "-";
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

const [familyContacts, setFamilyContacts] = useState([]);
const [familyLoading, setFamilyLoading] = useState(false);
const [familyError, setFamilyError] = useState("");
const [showFamilyForm, setShowFamilyForm] = useState(false);
const [savingFamilyContact, setSavingFamilyContact] = useState(false);
const [editingFamilyContactId, setEditingFamilyContactId] = useState(null);

const [familyForm, setFamilyForm] = useState({
    full_name: "",
    relationship: "",
    phone: "",
    whatsapp_number: "",
    is_primary: false,
    is_emergency_contact: false,
    whatsapp_enabled: true,
    medication_notifications_enabled: true,
    care_notifications_enabled: false,
    notes: "",
});

const loadFamilyContacts = async () => {
    try {
        setFamilyLoading(true);
        setFamilyError("");

        const response = await api.get(`/residents/${id}/contacts`);

        setFamilyContacts(
            Array.isArray(response.data?.contacts)
                ? response.data.contacts
                : []
        );
    } catch (error) {
        console.error("Failed to load family contacts:", error);
        setFamilyContacts([]);
        setFamilyError(
            error.response?.data?.message ||
            "Unable to load family contacts. Please try again."
        );
    } finally {
        setFamilyLoading(false);
    }
};

useEffect(() => {
    loadFamilyContacts();
}, [id]);

const resetFamilyForm = () => {
    setFamilyForm({
        full_name: "",
        relationship: "",
        phone: "",
        whatsapp_number: "",
        is_primary: false,
        is_emergency_contact: false,
        whatsapp_enabled: true,
        medication_notifications_enabled: true,
        care_notifications_enabled: false,
        notes: "",
    });

    setEditingFamilyContactId(null);
};

const openNewFamilyContact = () => {
    resetFamilyForm();
    setFamilyError("");
    setShowFamilyForm(true);
};

const openEditFamilyContact = (contact) => {
    setFamilyForm({
        full_name: contact.full_name || "",
        relationship: contact.relationship || "",
        phone: contact.phone || "",
        whatsapp_number: contact.whatsapp_number || "",
        is_primary: Boolean(contact.is_primary),
        is_emergency_contact: Boolean(contact.is_emergency_contact),
        whatsapp_enabled: Boolean(contact.whatsapp_enabled),
        medication_notifications_enabled: Boolean(contact.medication_notifications_enabled),
        care_notifications_enabled: Boolean(contact.care_notifications_enabled),
        notes: contact.notes || "",
    });

    setEditingFamilyContactId(contact.id);
    setFamilyError("");
    setShowFamilyForm(true);
};

const handleFamilyFormChange = (event) => {
    const { name, value, type, checked } = event.target;

    setFamilyForm((current) => ({
        ...current,
        [name]: type === "checkbox" ? checked : value,
    }));
};

const handleSaveFamilyContact = async (event) => {
    event.preventDefault();

    if (
        !familyForm.full_name.trim() ||
        !familyForm.relationship.trim() ||
        !familyForm.phone.trim()
    ) {
        setFamilyError(
            "Full name, relationship and phone number are required."
        );
        return;
    }

    try {
        setSavingFamilyContact(true);
        setFamilyError("");

        const payload = {
            full_name: familyForm.full_name.trim(),
            relationship: familyForm.relationship.trim(),
            phone: familyForm.phone.trim(),
            whatsapp_number: familyForm.whatsapp_number.trim() || null,
            is_primary: familyForm.is_primary,
            is_emergency_contact: familyForm.is_emergency_contact,
            whatsapp_enabled: familyForm.whatsapp_enabled,
            medication_notifications_enabled: familyForm.medication_notifications_enabled,
            care_notifications_enabled: familyForm.care_notifications_enabled,
            notes: familyForm.notes.trim() || null,
        };

        if (editingFamilyContactId) {
            await api.put(
                `/resident-contacts/${editingFamilyContactId}`,
                payload
            );
        } else {
            await api.post(
                `/residents/${id}/contacts`,
                payload
            );
        }

        await loadFamilyContacts();
        setShowFamilyForm(false);
        resetFamilyForm();
    } catch (error) {
        console.error("Failed to save family contact:", error);

        if (
            error.response?.status === 422 &&
            error.response?.data?.errors
        ) {
            const firstValidationError = Object.values(
                error.response.data.errors
            ).flat().find(Boolean);

            setFamilyError(
                firstValidationError ||
                "Please check the family contact information."
            );
        } else {
            setFamilyError(
                error.response?.data?.message ||
                "Unable to save the family contact. Please try again."
            );
        }
    } finally {
        setSavingFamilyContact(false);
    }
};

const handleDeleteFamilyContact = async (contact) => {
    const confirmed = window.confirm(
        `Delete ${contact.full_name} from ${resident.name}'s family contacts?`
    );

    if (!confirmed) return;

    try {
        setFamilyError("");
        await api.delete(`/resident-contacts/${contact.id}`);
        await loadFamilyContacts();
    } catch (error) {
        console.error("Failed to delete family contact:", error);
        setFamilyError(
            error.response?.data?.message ||
            "Unable to delete the family contact. Please try again."
        );
    }
};

const [medicationSchedule, setMedicationSchedule] = useState({
    AM: [],
    PM: [],
    NIGHT: [],
    OTHER: [],
});
const [medicationHistory, setMedicationHistory] = useState([]);
const [medicationLoading, setMedicationLoading] = useState(false);
const [medicationError, setMedicationError] = useState("");
const [completingMedicationRound, setCompletingMedicationRound] = useState("");
const [mealConfirmations, setMealConfirmations] = useState({
    AM: false,
    PM: false,
    NIGHT: false,
});

useEffect(() => {
    const loadMedicationData = async () => {
        try {
            setMedicationLoading(true);
            setMedicationError("");

            const [scheduleResponse, historyResponse] = await Promise.all([
                api.get(`/residents/${id}/medication-schedule`),
                api.get(`/residents/${id}/medication/history`),
            ]);

            setMedicationSchedule({
                AM: Array.isArray(scheduleResponse.data?.schedule?.AM)
                    ? scheduleResponse.data.schedule.AM
                    : [],
                PM: Array.isArray(scheduleResponse.data?.schedule?.PM)
                    ? scheduleResponse.data.schedule.PM
                    : [],
                NIGHT: Array.isArray(scheduleResponse.data?.schedule?.NIGHT)
                    ? scheduleResponse.data.schedule.NIGHT
                    : [],
                OTHER: Array.isArray(scheduleResponse.data?.schedule?.OTHER)
                    ? scheduleResponse.data.schedule.OTHER
                    : [],
            });

            setMedicationHistory(
                Array.isArray(historyResponse.data?.medication_history)
                    ? historyResponse.data.medication_history
                    : []
            );
        } catch (error) {
            console.error("Failed to load medication data:", error);

            setMedicationError(
                error.response?.data?.message ||
                "Unable to load medication information. Please try again."
            );
        } finally {
            setMedicationLoading(false);
        }
    };

    loadMedicationData();
}, [id]);


    const residentVitalHistory = vitalHistory[id] || [];

const vitalTrendData = [...residentVitalHistory]
    .reverse()
    .map((record) => {

        const [systolic, diastolic] =
            record.bloodPressure !== "-"
                ? record.bloodPressure.split("/").map(Number)
                : [null, null];

        return {
            date: record.recordedAt?.split(",")[0] || "-",

            systolic,
            diastolic,

            glucose:
                record.glucose === "-"
                    ? null
                    : Number(record.glucose),

            spo2:
                record.spo2 === "-"
                    ? null
                    : Number(record.spo2),

            weight:
                record.weight === "-"
                    ? null
                    : Number(record.weight),
        };

    });



    if (residentLoading) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />

                    <div>
                        <h1 className="text-lg font-bold text-slate-800">
                            Loading resident
                        </h1>

                        <p className="mt-1 text-sm text-slate-500">
                            Retrieving resident information...
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    if (residentError || !residentApi) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h1 className="text-2xl font-bold text-slate-800">
                    Resident unavailable
                </h1>

                <p className="mt-2 text-slate-500">
                    {residentError || "Resident information could not be found."}
                </p>

                <Link
                    to="/residents"
                    className="mt-4 inline-block font-semibold text-blue-600"
                >
                    ← Back to Residents
                </Link>
            </div>
        );
    }


    const calculateAge = (dateOfBirth) => {
        if (!dateOfBirth) return "-";

        const birthDate = new Date(dateOfBirth);

        if (Number.isNaN(birthDate.getTime())) {
            return "-";
        }

        const today = new Date();

        let age =
            today.getFullYear() -
            birthDate.getFullYear();

        const monthDifference =
            today.getMonth() -
            birthDate.getMonth();

        if (
            monthDifference < 0 ||
            (
                monthDifference === 0 &&
                today.getDate() < birthDate.getDate()
            )
        ) {
            age--;
        }

        return age;
    };


    const formatDate = (value) => {
        if (!value) return "-";

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString("en-MY", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    };


    const getVitalValue = (...keys) => {
        if (!latestVital) return null;

        for (const key of keys) {
            if (
                latestVital[key] !== null &&
                latestVital[key] !== undefined &&
                latestVital[key] !== ""
            ) {
                return latestVital[key];
            }
        }

        return null;
    };


    const getMedicationOverviewStatus = (slot) => {
        const medications = medicationSchedule[slot] || [];

        if (medications.length === 0) {
            return "None";
        }

        const allCompleted = medications.every(
            (medication) =>
                String(medication.status || "").toUpperCase() === "COMPLETED"
        );

        return allCompleted ? "Completed" : "Due";
    };


    const resident = {
    ...residentApi,

    name:
        residentApi.full_name ||
        "Unnamed Resident",

    gender:
        residentApi.gender ||
        "-",

    age:
        calculateAge(residentApi.date_of_birth),

    room:
        residentApi.room_number ||
        residentApi.room ||
        "Room not assigned",

    status:
        residentApi.status ||
        "Unknown",

    admissionDate:
        formatDate(residentApi.admission_date),

    primaryDiagnosis:
        residentApi.medical_condition ||
        residentApi.chronic_disease ||
        "No medical condition recorded",

    allergies:
        residentApi.allergies ||
        "No allergies recorded",

    careStatus:
        activeAlerts.length > 0
            ? "Needs Attention"
            : "Stable",

    aiSummary:
        activeAlerts.length > 0
            ? `${activeAlerts.length} active alert${activeAlerts.length === 1 ? "" : "s"} require review.`
            : "No active alerts currently require attention.",

    latestVitals: {
        bloodPressure:
            getVitalValue(
                "blood_pressure_systolic"
            ) !== null &&
            getVitalValue(
                "blood_pressure_diastolic"
            ) !== null
                ? `${getVitalValue(
                    "blood_pressure_systolic"
                )}/${getVitalValue(
                    "blood_pressure_diastolic"
                )}`
                : "-",
            

        pulse:
            getVitalValue("heart_rate") ?? "-",

        spo2:
            getVitalValue("oxygen_level") !== null
                ? `${getVitalValue("oxygen_level")}%`
                : "-",

        glucose:
            getVitalValue("blood_glucose") !== null
                ? `${getVitalValue(
                    "blood_glucose"
                )} mmol/L`
                : "-",

        weight:
            getVitalValue("weight") !== null
                ? `${getVitalValue("weight")} kg`
                : "-",

        temperature:
            getVitalValue("temperature") !== null
                ? `${getVitalValue("temperature")}°C`
                : "-",
    },

    medication: {
        am: getMedicationOverviewStatus("AM"),
        pm: getMedicationOverviewStatus("PM"),
        night: getMedicationOverviewStatus("NIGHT"),
    },

    emergencyContact: {
        name:
            familyContacts.find((contact) => contact.is_primary)?.full_name ||
            familyContacts.find((contact) => contact.is_emergency_contact)?.full_name ||
            residentApi.emergency_contact ||
            "No contact recorded",

        relationship:
            familyContacts.find((contact) => contact.is_primary)?.relationship ||
            familyContacts.find((contact) => contact.is_emergency_contact)?.relationship ||
            residentApi.emergency_relationship ||
            "-",

        phone:
            familyContacts.find((contact) => contact.is_primary)?.phone ||
            familyContacts.find((contact) => contact.is_emergency_contact)?.phone ||
            residentApi.emergency_phone ||
            "-",
    },
};


    const tabs = [

        {
            key: "overview",
            label: "Overview",
        },

        {
            key: "vitals",
            label: "Vitals",
        },

        {
            key: "medication",
            label: "Medication",
        },

        {
            key: "care",
            label: "Care",
        },

        {
            key: "ai",
            label: "AI Insights",
        },

        {
            key: "timeline",
            label: "Timeline",
        },

        {
            key: "documents",
            label: "Documents",
        },

        {
            key: "family",
            label: "Family",
        },

    ];


    const getMedicationStyle = (status) => {

        if (status === "Completed") {
            return "bg-emerald-50 text-emerald-700";
        }

        if (status === "Due") {
            return "bg-amber-50 text-amber-700";
        }

        if (status === "None") {
            return "bg-slate-100 text-slate-500";
        }

        return "bg-blue-50 text-blue-700";
    };


    const handleVitalChange = (event) => {

    const { name, value } = event.target;

    setVitalForm((current) => ({
        ...current,
        [name]: value,
    }));

};


const handleSaveVitals = async (event) => {
    event.preventDefault();

    if (
        !vitalForm.systolic ||
        !vitalForm.diastolic
    ) {
        alert(
            "Blood pressure systolic and diastolic readings are required."
        );
        return;
    }

    try {
        setSavingVitals(true);
        setVitalsError("");

        const payload = {
            blood_pressure_systolic:
                Number(vitalForm.systolic),

            blood_pressure_diastolic:
                Number(vitalForm.diastolic),

            blood_glucose:
                vitalForm.glucose
                    ? Number(vitalForm.glucose)
                    : null,

            heart_rate:
                vitalForm.pulse
                    ? Number(vitalForm.pulse)
                    : null,

            oxygen_level:
                vitalForm.spo2
                    ? Number(vitalForm.spo2)
                    : null,

            temperature:
                vitalForm.temperature
                    ? Number(vitalForm.temperature)
                    : null,

            weight:
                vitalForm.weight
                    ? Number(vitalForm.weight)
                    : null,
        };

        const response = await api.post(
            `/residents/${id}/vitals`,
            payload
        );

        const savedVital = response.data?.vital;

        if (savedVital) {
            const mappedVital =
                mapVitalFromApi(savedVital);

            setVitalHistory((current) => ({
                ...current,

                [id]: [
                    mappedVital,
                    ...(current[id] || []).filter(
                        (item) =>
                            item.id !== mappedVital.id
                    ),
                ],
            }));

            setLatestVital(savedVital);
        }

        setVitalForm({
            systolic: "",
            diastolic: "",
            pulse: "",
            spo2: "",
            glucose: "",
            weight: "",
            temperature: "",
            notes: "",
        });

        setShowVitalForm(false);
    } catch (error) {
        console.error(
            "Failed to save vital signs:",
            error
        );

        if (
            error.response?.status === 422 &&
            error.response?.data?.errors
        ) {
            const firstValidationError =
                Object.values(
                    error.response.data.errors
                )
                    .flat()
                    .find(Boolean);

            setVitalsError(
                firstValidationError ||
                "Please check the vital sign values."
            );
        } else {
            setVitalsError(
                "Unable to save vital signs. Please try again."
            );
        }
    } finally {
        setSavingVitals(false);
    }
};


const medicationRoundConfig = {
    AM: {
        key: "AM",
        label: "AM",
        meal: "Breakfast",
        scheduledTime: "08:00 AM",
        icon: "🌅",
    },
    PM: {
        key: "PM",
        label: "PM",
        meal: "Lunch",
        scheduledTime: "01:00 PM",
        icon: "☀️",
    },
    NIGHT: {
        key: "NIGHT",
        label: "Night",
        meal: "Dinner",
        scheduledTime: "08:00 PM",
        icon: "🌙",
    },
};

const medicationRoundEntries = ["AM", "PM", "NIGHT"].map((slot) => {
    const medications = medicationSchedule[slot] || [];
    const completedMedicationCount = medications.filter(
        (medication) =>
            String(medication.status || "").toUpperCase() === "COMPLETED"
    ).length;

    return {
        ...medicationRoundConfig[slot],
        medications,
        completedMedicationCount,
        allMedicationCompleted:
            medications.length > 0 &&
            completedMedicationCount === medications.length,
        mealConfirmed: mealConfirmations[slot] || false,
    };
});

const completedMedicationRounds = medicationRoundEntries.filter(
    (round) => round.allMedicationCompleted
).length;

const formatMedicationDateTime = (value) => {
    if (!value) return "-";

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
};

const refreshMedicationData = async () => {
    const [scheduleResponse, historyResponse] = await Promise.all([
        api.get(`/residents/${id}/medication-schedule`),
        api.get(`/residents/${id}/medication/history`),
    ]);

    setMedicationSchedule({
        AM: Array.isArray(scheduleResponse.data?.schedule?.AM)
            ? scheduleResponse.data.schedule.AM
            : [],
        PM: Array.isArray(scheduleResponse.data?.schedule?.PM)
            ? scheduleResponse.data.schedule.PM
            : [],
        NIGHT: Array.isArray(scheduleResponse.data?.schedule?.NIGHT)
            ? scheduleResponse.data.schedule.NIGHT
            : [],
        OTHER: Array.isArray(scheduleResponse.data?.schedule?.OTHER)
            ? scheduleResponse.data.schedule.OTHER
            : [],
    });

    setMedicationHistory(
        Array.isArray(historyResponse.data?.medication_history)
            ? historyResponse.data.medication_history
            : []
    );
};

const handleMealConfirmationChange = (slot) => {
    setMealConfirmations((current) => ({
        ...current,
        [slot]: !current[slot],
    }));
};

const handleCompleteMedicationRound = async (round) => {
    if (!round) return;

    if (round.medications.length === 0) {
        alert(`No medication is scheduled for the ${round.label} round.`);
        return;
    }

    if (!round.mealConfirmed) {
        alert(`Please confirm that ${round.meal.toLowerCase()} has been taken.`);
        return;
    }

    const pendingMedications = round.medications.filter(
        (medication) =>
            String(medication.status || "").toUpperCase() !== "COMPLETED"
    );

    if (pendingMedications.length === 0) {
        alert(`${round.label} medication has already been completed.`);
        return;
    }

    const medicationNames = pendingMedications
        .map((medication) => medication.medicine)
        .filter(Boolean)
        .join(", ");

    const confirmed = window.confirm(
        `Confirm ${round.label} medication for ${resident.name}?\n\n` +
        `${medicationNames || `${pendingMedications.length} medication(s)`}\n` +
        `${round.meal} confirmed: Yes`
    );

    if (!confirmed) return;

    try {
        setCompletingMedicationRound(round.key);
        setMedicationError("");

        for (const medication of pendingMedications) {
            await api.put(
                `/medication-administration/${medication.id}/complete`,
                {}
            );
        }

        await refreshMedicationData();

        alert(
            `${round.label} medication recorded successfully for ${resident.name}.`
        );
    } catch (error) {
        console.error("Failed to complete medication round:", error);

        setMedicationError(
            error.response?.data?.message ||
            error.response?.data?.error ||
            "Unable to complete the medication round. Please check the medication record and stock, then try again."
        );

        try {
            await refreshMedicationData();
        } catch (refreshError) {
            console.error(
                "Failed to refresh medication data after completion error:",
                refreshError
            );
        }
    } finally {
        setCompletingMedicationRound("");
    }
};

const getMedicationRoundMessage = (round) =>
    `${resident.name} has taken the ${round.label} medication and ${round.meal.toLowerCase()}.`;
const formatCareDateTime = (value) => {
    if (!value) return "-";

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
};

const isCareRecordToday = (value) => {
    if (!value) return false;

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return false;
    }

    const today = new Date();

    return (
        date.getFullYear() === today.getFullYear() &&
        date.getMonth() === today.getMonth() &&
        date.getDate() === today.getDate()
    );
};

const todayCareRecords = careApiRecords.filter((record) =>
    isCareRecordToday(record.recorded_at)
);

const completedCareRecords = todayCareRecords.filter(
    (record) =>
        String(record.care_status || "").toUpperCase() === "COMPLETED"
);

const attentionCareRecords = careApiRecords.filter(
    (record) =>
        String(record.care_status || "").toUpperCase() === "NEEDS_ATTENTION"
);

const observedCareRecords = careApiRecords.filter(
    (record) =>
        String(record.care_status || "").toUpperCase() === "OBSERVED"
);

const resetCareForm = () => {
    setCareForm({
        care_type: "Personal Care",
        title: "",
        notes: "",
        care_status: "COMPLETED",
        recorded_at: "",
    });

    setEditingCareRecordId(null);
};

const openNewCareRecord = () => {
    resetCareForm();
    setCareError("");
    setShowCareForm(true);
};

const openEditCareRecord = (record) => {
    let recordedAt = "";

    if (record.recorded_at) {
        const date = new Date(record.recorded_at);

        if (!Number.isNaN(date.getTime())) {
            const localDate = new Date(
                date.getTime() - date.getTimezoneOffset() * 60000
            );

            recordedAt = localDate
                .toISOString()
                .slice(0, 16);
        }
    }

    setCareForm({
        care_type: record.care_type || "Personal Care",
        title: record.title || "",
        notes: record.notes || "",
        care_status: record.care_status || "COMPLETED",
        recorded_at: recordedAt,
    });

    setEditingCareRecordId(record.id);
    setCareError("");
    setShowCareForm(true);
};

const handleCareFormChange = (event) => {
    const { name, value } = event.target;

    setCareForm((current) => ({
        ...current,
        [name]: value,
    }));
};

const handleSaveCareRecord = async (event) => {
    event.preventDefault();

    if (!careForm.title.trim()) {
        setCareError("Please enter a short care record title.");
        return;
    }

    try {
        setSavingCareRecord(true);
        setCareError("");

        const payload = {
            care_type: careForm.care_type,
            title: careForm.title.trim(),
            notes: careForm.notes.trim() || null,
            care_status: careForm.care_status,
        };

        if (careForm.recorded_at) {
            payload.recorded_at = careForm.recorded_at;
        }

        if (editingCareRecordId) {
            await api.put(
                `/care-records/${editingCareRecordId}`,
                payload
            );
        } else {
            await api.post(
                `/residents/${id}/care-records`,
                payload
            );
        }

        await loadCareRecords();

        setShowCareForm(false);
        resetCareForm();
    } catch (error) {
        console.error("Failed to save care record:", error);

        if (
            error.response?.status === 422 &&
            error.response?.data?.errors
        ) {
            const firstValidationError = Object.values(
                error.response.data.errors
            )
                .flat()
                .find(Boolean);

            setCareError(
                firstValidationError ||
                "Please check the care record information."
            );
        } else {
            setCareError(
                error.response?.data?.message ||
                "Unable to save the care record. Please try again."
            );
        }
    } finally {
        setSavingCareRecord(false);
    }
};

const handleDeleteCareRecord = async (record) => {
    const confirmed = window.confirm(
        `Delete "${record.title}" from ${resident.name}'s care history?`
    );

    if (!confirmed) return;

    try {
        setCareError("");

        await api.delete(
            `/care-records/${record.id}`
        );

        await loadCareRecords();
    } catch (error) {
        console.error("Failed to delete care record:", error);

        setCareError(
            error.response?.data?.message ||
            "Unable to delete the care record. Please try again."
        );
    }
};

const getCareStatusStyle = (status) => {
    const normalized = String(status || "").toUpperCase();

    if (normalized === "COMPLETED") {
        return "bg-emerald-50 text-emerald-700";
    }

    if (normalized === "NEEDS_ATTENTION") {
        return "bg-rose-50 text-rose-700";
    }

    return "bg-amber-50 text-amber-700";
};

const getCareStatusLabel = (status) => {
    const normalized = String(status || "").toUpperCase();

    if (normalized === "NEEDS_ATTENTION") {
        return "Needs Attention";
    }

    if (normalized === "OBSERVED") {
        return "Observed";
    }

    return "Completed";
};

const residentDocuments = documentRecords;
const residentFamilyContacts = familyContacts;

const formatTimelineDate = (value) => {
    if (!value) {
        return "-";
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
};


const getTimelineCategory = (entry) => {
    const source =
        String(entry?.source || "");

    const title =
        String(entry?.title || "");


    if (
        source.includes("ResidentVisitor")
        ||
        title.includes("Visitor")
    ) {
        return "Visitor";
    }


    if (
        source.includes("ResidentHomeLeave")
        ||
        title.includes("Home Leave")
    ) {
        return "Home Leave";
    }


    if (
        source.includes("ResidentParcel")
        ||
        title.includes("Parcel")
    ) {
        return "Parcel";
    }


    if (
        source === "WeeklyVitalSign"
        ||
        title.includes("Weekly Vital")
    ) {
        return "Weekly Vitals";
    }


    if (
        title.includes("Monthly Glucose")
    ) {
        return "Monthly Glucose";
    }


    if (
        entry?.type === "ADMISSION"
    ) {
        return "Admission";
    }


    if (
        entry?.type === "DISCHARGE"
    ) {
        return "Discharge";
    }


    if (
        String(entry?.type || "")
            .startsWith("MEDICATION")
    ) {
        return "Medication";
    }


    if (
        String(entry?.type || "")
            .startsWith("AI_")
    ) {
        return "AI";
    }


    if (
        entry?.type === "VITAL"
    ) {
        return "Vitals";
    }


    return (
        entry?.category ||
        "Clinical"
    );
};


const getTimelineBadgeStyle = (entry) => {
    const category =
        getTimelineCategory(entry);

    if (
        category === "Admission"
        ||
        category === "Discharge"
    ) {
        return "bg-purple-50 text-purple-700";
    }

    if (
        category === "Vitals"
        ||
        category === "Weekly Vitals"
        ||
        category === "Monthly Glucose"
    ) {
        return "bg-blue-50 text-blue-700";
    }

    if (
        category === "Medication"
    ) {
        return "bg-emerald-50 text-emerald-700";
    }

    if (
        category === "Home Leave"
    ) {
        return "bg-amber-50 text-amber-700";
    }

    if (
        category === "Visitor"
    ) {
        return "bg-cyan-50 text-cyan-700";
    }

    if (
        category === "Parcel"
    ) {
        return "bg-orange-50 text-orange-700";
    }

    if (
        category === "AI"
    ) {
        return "bg-rose-50 text-rose-700";
    }

    return "bg-slate-100 text-slate-600";
};



    return (

        <div className="
            w-full
            min-w-0
            space-y-5
            overflow-x-hidden
            pb-10
            sm:space-y-6
        ">


            {/* BACK */}

            <Link
                to="/residents"
                className="
                    inline-flex
                    items-center
                    text-sm
                    font-semibold
                    text-slate-500
                    hover:text-blue-600
                "
            >
                ← Back to Residents
            </Link>


            {/* RESIDENT HEADER */}

            <div className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-4
                shadow-sm
                sm:p-6
            ">

                <div className="
                    flex
                    flex-col
                    gap-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">

                    <div className="
                        flex
                        min-w-0
                        items-center
                        gap-3
                        sm:gap-4
                    ">

                        <div className="
                            flex
                            h-16
                            w-16
                            items-center
                            justify-center
                            rounded-full
                            bg-blue-100
                            text-xl
                            font-bold
                            text-blue-700
                        ">
                            {resident.name
                                .split(" ")
                                .map((name) => name[0])
                                .slice(0, 2)
                                .join("")}
                        </div>


                        <div>

                            <h1 className="
                                break-words
                                text-2xl
                                font-bold
                                text-slate-800
                                sm:text-3xl
                            ">
                                {resident.name}
                            </h1>

                            <div className="
                                mt-2
                                flex
                                flex-wrap
                                items-center
                                gap-3
                                text-sm
                                text-slate-500
                            ">

                                <span>
                                    {resident.room}
                                </span>

                                <span>
                                    •
                                </span>

                                <span>
                                    {resident.gender}
                                </span>

                                <span>
                                    •
                                </span>

                                <span>
                                    {resident.age} years
                                </span>

                            </div>

                        </div>

                    </div>


                    <div className="
                        flex
                        flex-wrap
                        items-center
                        gap-3
                    ">

                        <span className="
                            rounded-full
                            bg-emerald-50
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-emerald-700
                        ">
                            {resident.status}
                        </span>

                        <button
                            type="button"
                            className="
                                rounded-lg
                                border
                                border-slate-300
                                px-4
                                py-2
                                text-sm
                                font-semibold
                                text-slate-700
                                hover:border-blue-500
                                hover:text-blue-600
                            "
                        >
                            Edit Resident
                        </button>

                    </div>

                </div>

            </div>


            {/* TABS */}

            <div className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-2
                shadow-sm
            ">

                <div className="
                    grid
                    w-full
                    grid-cols-2
                    gap-1
                    sm:grid-cols-4
                    xl:grid-cols-8
                ">

                    {
                        tabs.map((tab) => (

                            <button
                                key={tab.key}
                                type="button"
                                onClick={() =>
                                    setActiveTab(tab.key)
                                }
                                className={`
                                    min-w-0
                                    rounded-xl
                                    px-2
                                    py-3
                                    text-center
                                    text-xs
                                    font-semibold
                                    transition
                                    sm:text-sm
                                    xl:px-3
                                    xl:py-4

                                    ${
                                        activeTab === tab.key
                                            ? "bg-blue-600 text-white shadow-sm"
                                            : "text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                                    }
                                `}
                            >
                                <span className="block truncate">
                                    {tab.label}
                                </span>
                            </button>

                        ))
                    }

                </div>

            </div>


            {/* OVERVIEW */}

            {
                activeTab === "overview" && (

                    <div className="space-y-6">


                        {/* IMPORTANT STATUS */}

                        <div className="
                            grid
                            gap-4
                            lg:grid-cols-3
                        ">


                            <div className="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                                shadow-sm
                            ">

                                <p className="
                                    text-sm
                                    font-medium
                                    text-slate-500
                                ">
                                    Care Status
                                </p>

                                <p className="
                                    mt-2
                                    text-xl
                                    font-bold
                                    text-slate-800
                                ">
                                    {resident.careStatus}
                                </p>

                            </div>


                            <div className="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                                shadow-sm
                            ">

                                <p className="
                                    text-sm
                                    font-medium
                                    text-slate-500
                                ">
                                    Primary Diagnosis
                                </p>

                                <p className="
                                    mt-2
                                    text-xl
                                    font-bold
                                    text-slate-800
                                ">
                                    {resident.primaryDiagnosis}
                                </p>

                            </div>


                            <div className="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                                shadow-sm
                            ">

                                <p className="
                                    text-sm
                                    font-medium
                                    text-slate-500
                                ">
                                    Admission Date
                                </p>

                                <p className="
                                    mt-2
                                    text-xl
                                    font-bold
                                    text-slate-800
                                ">
                                    {resident.admissionDate}
                                </p>

                            </div>

                        </div>


                        {/* LATEST VITALS */}

                        <div className="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            p-6
                            shadow-sm
                        ">

                            <div className="
                                mb-5
                                flex
                                items-center
                                justify-between
                            ">

                                <div>

                                    <h2 className="
                                        text-xl
                                        font-bold
                                        text-slate-800
                                    ">
                                        Latest Vitals
                                    </h2>

                                    <p className="
                                        mt-1
                                        text-sm
                                        text-slate-500
                                    ">
                                        Most recently recorded observations
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    onClick={() =>
                                        setActiveTab("vitals")
                                    }
                                    className="
                                        text-sm
                                        font-semibold
                                        text-blue-600
                                        hover:text-blue-700
                                    "
                                >
                                    View Trends →
                                </button>

                            </div>


                            <div className="
                                grid
                                gap-4
                                sm:grid-cols-2
                                lg:grid-cols-3
                                xl:grid-cols-6
                            ">

                                <VitalBox
                                    label="Blood Pressure"
                                    value={resident.latestVitals.bloodPressure}
                                />

                                <VitalBox
                                    label="Pulse"
                                    value={
                                        resident.latestVitals.pulse === "-"
                                            ? "-"
                                            : `${resident.latestVitals.pulse} bpm`
                                    }
                                />

                                <VitalBox
                                    label="SpO₂"
                                    value={resident.latestVitals.spo2}
                                />

                                <VitalBox
                                    label="Glucose"
                                    value={resident.latestVitals.glucose}
                                />

                                <VitalBox
                                    label="Weight"
                                    value={resident.latestVitals.weight}
                                />

                                <VitalBox
                                    label="Temperature"
                                    value={resident.latestVitals.temperature}
                                />

                            </div>

                        </div>


                        {/* MEDICATION ROUND */}

                        <div className="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            p-6
                            shadow-sm
                        ">

                            <div className="
                                mb-5
                                flex
                                items-center
                                justify-between
                            ">

                                <div>

                                    <h2 className="
                                        text-xl
                                        font-bold
                                        text-slate-800
                                    ">
                                        Today's Medication
                                    </h2>

                                    <p className="
                                        mt-1
                                        text-sm
                                        text-slate-500
                                    ">
                                        AM, PM and Night medication rounds
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    onClick={() =>
                                        setActiveTab("medication")
                                    }
                                    className="
                                        text-sm
                                        font-semibold
                                        text-blue-600
                                        hover:text-blue-700
                                    "
                                >
                                    View Medication →
                                </button>

                            </div>


                            <div className="
                                grid
                                gap-4
                                md:grid-cols-3
                            ">

                                {
                                    [
                                        {
                                            label: "AM",
                                            meal: "Breakfast",
                                            status: resident.medication.am,
                                        },

                                        {
                                            label: "PM",
                                            meal: "Lunch",
                                            status: resident.medication.pm,
                                        },

                                        {
                                            label: "Night",
                                            meal: "Dinner",
                                            status: resident.medication.night,
                                        },

                                    ].map((round) => (

                                        <div
                                            key={round.label}
                                            className="
                                                rounded-xl
                                                border
                                                border-slate-200
                                                p-5
                                            "
                                        >

                                            <div className="
                                                flex
                                                items-center
                                                justify-between
                                            ">

                                                <div>

                                                    <h3 className="
                                                        text-lg
                                                        font-bold
                                                        text-slate-800
                                                    ">
                                                        {round.label}
                                                    </h3>

                                                    <p className="
                                                        mt-1
                                                        text-sm
                                                        text-slate-500
                                                    ">
                                                        {round.meal}
                                                    </p>

                                                </div>


                                                <span
                                                    className={`
                                                        rounded-full
                                                        px-3
                                                        py-1
                                                        text-xs
                                                        font-semibold
                                                        ${getMedicationStyle(
                                                            round.status
                                                        )}
                                                    `}
                                                >
                                                    {round.status}
                                                </span>

                                            </div>

                                        </div>

                                    ))
                                }

                            </div>

                        </div>


                        {/* AI SUMMARY */}

                        <div className="
                            rounded-xl
                            border
                            border-blue-100
                            bg-blue-50
                            p-6
                        ">

                            <div className="
                                flex
                                items-start
                                gap-4
                            ">

                                <div className="
                                    flex
                                    h-10
                                    w-10
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-blue-600
                                    text-white
                                ">
                                    ✦
                                </div>


                                <div>

                                    <h2 className="
                                        text-lg
                                        font-bold
                                        text-slate-800
                                    ">
                                        AI Care Summary
                                    </h2>

                                    <p className="
                                        mt-2
                                        leading-7
                                        text-slate-700
                                    ">
                                        {resident.aiSummary}
                                    </p>


                                    <button
                                        type="button"
                                        onClick={() =>
                                            setActiveTab("ai")
                                        }
                                        className="
                                            mt-3
                                            text-sm
                                            font-semibold
                                            text-blue-700
                                        "
                                    >
                                        View AI Insights →
                                    </button>

                                </div>

                            </div>

                        </div>


                        {/* BASIC INFORMATION */}

                        <div className="
                            grid
                            gap-6
                            lg:grid-cols-2
                        ">


                            <div className="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-6
                                shadow-sm
                            ">

                                <h2 className="
                                    text-xl
                                    font-bold
                                    text-slate-800
                                ">
                                    Clinical Information
                                </h2>


                                <div className="
                                    mt-5
                                    divide-y
                                    divide-slate-100
                                ">

                                    <InfoRow
                                        label="Primary Diagnosis"
                                        value={resident.primaryDiagnosis}
                                    />

                                    <InfoRow
                                        label="Allergies"
                                        value={resident.allergies}
                                    />

                                    <InfoRow
                                        label="Care Status"
                                        value={resident.careStatus}
                                    />

                                </div>

                            </div>


                            <div className="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-6
                                shadow-sm
                            ">

                                <div className="
                                    flex
                                    items-center
                                    justify-between
                                ">

                                    <h2 className="
                                        text-xl
                                        font-bold
                                        text-slate-800
                                    ">
                                        Family Contact
                                    </h2>


                                    <button
                                        type="button"
                                        onClick={() =>
                                            setActiveTab("family")
                                        }
                                        className="
                                            text-sm
                                            font-semibold
                                            text-blue-600
                                        "
                                    >
                                        View
                                    </button>

                                </div>


                                <div className="
                                    mt-5
                                    divide-y
                                    divide-slate-100
                                ">

                                    <InfoRow
                                        label="Name"
                                        value={resident.emergencyContact.name}
                                    />

                                    <InfoRow
                                        label="Relationship"
                                        value={resident.emergencyContact.relationship}
                                    />

                                    <InfoRow
                                        label="Phone"
                                        value={resident.emergencyContact.phone}
                                    />

                                </div>

                            </div>

                        </div>

                    </div>

                )
            }


            {/* VITALS TAB */}

{
    activeTab === "vitals" && (

        <div className="space-y-6">


            {/* VITALS HEADER */}

            <div className="
                rounded-xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
            ">

                <div className="
                    flex
                    flex-col
                    gap-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                ">

                    <div>

                        <h2 className="
                            text-xl
                            font-bold
                            text-slate-800
                        ">
                            Vital Signs
                        </h2>

                        <p className="
                            mt-1
                            text-sm
                            text-slate-500
                        ">
                            Record and review {resident.name}'s vital signs.
                        </p>

                    </div>


                    <button
                        type="button"
                        onClick={() =>
                            setShowVitalForm(true)
                        }
                        className="
                            rounded-lg
                            bg-blue-600
                            px-5
                            py-3
                            font-semibold
                            text-white
                            hover:bg-blue-700
                        "
                    >
                        + Record Vitals
                    </button>

                </div>

            </div>

            {vitalsError && (
                <div className="
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    px-5
                    py-4
                    text-sm
                    font-medium
                    text-red-700
                ">
                    {vitalsError}
                </div>
            )}


            {vitalsLoading && (
                <div className="
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    text-sm
                    text-slate-500
                    shadow-sm
                ">
                    Loading vital sign history...
                </div>
            )}


            {/* LATEST VITAL SUMMARY */}

            {
                residentVitalHistory.length > 0 && (

                    <div className="
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        shadow-sm
                    ">

                        <div className="mb-5">

                            <h3 className="
                                text-lg
                                font-bold
                                text-slate-800
                            ">
                                Latest Reading
                            </h3>

                            <p className="
                                mt-1
                                text-sm
                                text-slate-500
                            ">
                                {
                                    residentVitalHistory[0]
                                        .recordedAt
                                }
                            </p>

                        </div>


                        <div className="
                            grid
                            gap-4
                            sm:grid-cols-2
                            lg:grid-cols-3
                            xl:grid-cols-6
                        ">

                            <VitalBox
                                label="Blood Pressure"
                                value={
                                    residentVitalHistory[0]
                                        .bloodPressure
                                }
                            />

                            <VitalBox
                                label="Pulse"
                                value={
                                    residentVitalHistory[0].pulse === "-"
                                        ? "-"
                                        : `${residentVitalHistory[0].pulse} bpm`
                                }
                            />

                            <VitalBox
                                label="SpO₂"
                                value={
                                    residentVitalHistory[0].spo2 === "-"
                                        ? "-"
                                        : `${residentVitalHistory[0].spo2}%`
                                }
                            />

                            <VitalBox
                                label="Glucose"
                                value={
                                    residentVitalHistory[0].glucose === "-"
                                        ? "-"
                                        : `${residentVitalHistory[0].glucose} mmol/L`
                                }
                            />

                            <VitalBox
                                label="Weight"
                                value={
                                    residentVitalHistory[0].weight === "-"
                                        ? "-"
                                        : `${residentVitalHistory[0].weight} kg`
                                }
                            />

                            <VitalBox
                                label="Temperature"
                                value={
                                    residentVitalHistory[0].temperature === "-"
                                        ? "-"
                                        : `${residentVitalHistory[0].temperature}°C`
                                }
                            />

                        </div>

                    </div>

                )
            }


            {/* RECORD VITALS FORM */}

            {
                showVitalForm && (

                    <div className="
                        rounded-xl
                        border
                        border-blue-200
                        bg-white
                        p-6
                        shadow-sm
                    ">

                        <div className="mb-6">

                            <h3 className="
                                text-xl
                                font-bold
                                text-slate-800
                            ">
                                Record New Vital Signs
                            </h3>

                            <p className="
                                mt-1
                                text-sm
                                text-slate-500
                            ">
                                Recording for {resident.name}. The resident is selected automatically.
                            </p>

                        </div>


                        <form
                            onSubmit={handleSaveVitals}
                            className="space-y-6"
                        >

                            <div className="
                                grid
                                gap-5
                                md:grid-cols-2
                                xl:grid-cols-3
                            ">


                                <div>

                                    <label className="
                                        mb-2
                                        block
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                    ">
                                        Blood Pressure *
                                    </label>

                                    <div className="
                                        grid
                                        grid-cols-2
                                        gap-2
                                    ">

                                        <input
                                            type="number"
                                            name="systolic"
                                            value={vitalForm.systolic}
                                            onChange={handleVitalChange}
                                            placeholder="Systolic"
                                            className="
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-300
                                                px-4
                                                py-3
                                                outline-none
                                                focus:border-blue-500
                                            "
                                        />

                                        <input
                                            type="number"
                                            name="diastolic"
                                            value={vitalForm.diastolic}
                                            onChange={handleVitalChange}
                                            placeholder="Diastolic"
                                            className="
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-300
                                                px-4
                                                py-3
                                                outline-none
                                                focus:border-blue-500
                                            "
                                        />

                                    </div>

                                </div>


                                <VitalInput
                                    label="Pulse"
                                    name="pulse"
                                    value={vitalForm.pulse}
                                    onChange={handleVitalChange}
                                    unit="bpm"
                                    required
                                />


                                <VitalInput
                                    label="SpO₂"
                                    name="spo2"
                                    value={vitalForm.spo2}
                                    onChange={handleVitalChange}
                                    unit="%"
                                    required
                                />


                                <VitalInput
                                    label="Glucose"
                                    name="glucose"
                                    value={vitalForm.glucose}
                                    onChange={handleVitalChange}
                                    unit="mmol/L"
                                />


                                <VitalInput
                                    label="Weight"
                                    name="weight"
                                    value={vitalForm.weight}
                                    onChange={handleVitalChange}
                                    unit="kg"
                                />


                                <VitalInput
                                    label="Temperature"
                                    name="temperature"
                                    value={vitalForm.temperature}
                                    onChange={handleVitalChange}
                                    unit="°C"
                                    required
                                />

                            </div>


                            <div>

                                <label className="
                                    mb-2
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                ">
                                    Notes
                                </label>

                                <textarea
                                    name="notes"
                                    value={vitalForm.notes}
                                    onChange={handleVitalChange}
                                    rows="3"
                                    placeholder="Optional observation or comments..."
                                    className="
                                        w-full
                                        rounded-lg
                                        border
                                        border-slate-300
                                        px-4
                                        py-3
                                        outline-none
                                        focus:border-blue-500
                                    "
                                />

                            </div>


                            <div className="
                                flex
                                flex-col-reverse
                                gap-3
                                sm:flex-row
                                sm:justify-end
                            ">

                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowVitalForm(false)
                                    }
                                    className="
                                        rounded-lg
                                        border
                                        border-slate-300
                                        px-5
                                        py-3
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    Cancel
                                </button>


                                <button
                                    type="submit"
                                    disabled={savingVitals}
                                    className="
                                        rounded-lg
                                        bg-blue-600
                                        px-6
                                        py-3
                                        font-semibold
                                        text-white
                                        hover:bg-blue-700
                                        disabled:cursor-not-allowed
                                        disabled:opacity-60
                                    "
                                >
                                    {savingVitals
                                        ? "Saving..."
                                        : "Save Vital Signs"}
                                </button>

                            </div>

                        </form>

                    </div>

                )
            }


            {/* VITAL TRENDS */}

                <div className="
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                ">

                    <div className="mb-6">

                        <h3 className="
                            text-xl
                            font-bold
                            text-slate-800
                        ">
                            Vital Trends
                        </h3>

                        <p className="
                            mt-1
                            text-sm
                            text-slate-500
                        ">
                            Changes in {resident.name}'s key vital signs over time.
                        </p>

                    </div>


                    {
                        vitalTrendData.length > 0 ? (

                            <div className="
                                grid
                                gap-6
                                xl:grid-cols-2
                            ">

                                <VitalTrendChart
                                    title="Blood Pressure"
                                    subtitle="Systolic and diastolic readings"
                                >

                                    <ResponsiveContainer width="100%" height={280}>

                                        <LineChart data={vitalTrendData}>

                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                            />

                                            <XAxis
                                                dataKey="date"
                                                fontSize={12}
                                            />

                                            <YAxis
                                                fontSize={12}
                                                domain={["auto", "auto"]}
                                            />

                                            <Tooltip />

                                            <Legend />

                                            <Line
                                                type="monotone"
                                                dataKey="systolic"
                                                name="Systolic"
                                                stroke="#2563eb"
                                                strokeWidth={3}
                                                connectNulls
                                            />

                                            <Line
                                                type="monotone"
                                                dataKey="diastolic"
                                                name="Diastolic"
                                                stroke="#64748b"
                                                strokeWidth={3}
                                                connectNulls
                                            />

                                        </LineChart>

                                    </ResponsiveContainer>

                                </VitalTrendChart>


                                <VitalTrendChart
                                    title="Blood Glucose"
                                    subtitle="mmol/L"
                                >

                                    <ResponsiveContainer width="100%" height={280}>

                                        <LineChart data={vitalTrendData}>

                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                            />

                                            <XAxis
                                                dataKey="date"
                                                fontSize={12}
                                            />

                                            <YAxis
                                                fontSize={12}
                                                domain={["auto", "auto"]}
                                            />

                                            <Tooltip />

                                            <Line
                                                type="monotone"
                                                dataKey="glucose"
                                                name="Glucose"
                                                stroke="#ea580c"
                                                strokeWidth={3}
                                                connectNulls
                                            />

                                        </LineChart>

                                    </ResponsiveContainer>

                                </VitalTrendChart>


                                <VitalTrendChart
                                    title="Oxygen Saturation"
                                    subtitle="SpO₂ percentage"
                                >

                                    <ResponsiveContainer width="100%" height={280}>

                                        <LineChart data={vitalTrendData}>

                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                            />

                                            <XAxis
                                                dataKey="date"
                                                fontSize={12}
                                            />

                                            <YAxis
                                                fontSize={12}
                                                domain={[85, 100]}
                                            />

                                            <Tooltip />

                                            <Line
                                                type="monotone"
                                                dataKey="spo2"
                                                name="SpO₂"
                                                stroke="#059669"
                                                strokeWidth={3}
                                                connectNulls
                                            />

                                        </LineChart>

                                    </ResponsiveContainer>

                                </VitalTrendChart>


                                <VitalTrendChart
                                    title="Weight"
                                    subtitle="Kilograms"
                                >

                                    <ResponsiveContainer width="100%" height={280}>

                                        <LineChart data={vitalTrendData}>

                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                            />

                                            <XAxis
                                                dataKey="date"
                                                fontSize={12}
                                            />

                                            <YAxis
                                                fontSize={12}
                                                domain={["auto", "auto"]}
                                            />

                                            <Tooltip />

                                            <Line
                                                type="monotone"
                                                dataKey="weight"
                                                name="Weight"
                                                stroke="#7c3aed"
                                                strokeWidth={3}
                                                connectNulls
                                            />

                                        </LineChart>

                                    </ResponsiveContainer>

                                </VitalTrendChart>

                            </div>

                        ) : (

                            <div className="
                                rounded-lg
                                bg-slate-50
                                p-8
                                text-center
                                text-slate-500
                            ">
                                No vital trend information is available yet.
                            </div>

                        )
                    }

                </div>



            {/* VITAL HISTORY */}

            <div className="
                overflow-hidden
                rounded-xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">

                <div className="
                    border-b
                    border-slate-200
                    p-6
                ">

                    <h3 className="
                        text-xl
                        font-bold
                        text-slate-800
                    ">
                        Vital Signs History
                    </h3>

                    <p className="
                        mt-1
                        text-sm
                        text-slate-500
                    ">
                        Previous observations for this resident.
                    </p>

                </div>


                <div className="overflow-x-auto">

                    <table className="
                        min-w-full
                        divide-y
                        divide-slate-200
                    ">

                        <thead className="bg-slate-50">

                            <tr>

                                {
                                    [
                                        "Date & Time",
                                        "BP",
                                        "Pulse",
                                        "SpO₂",
                                        "Glucose",
                                        "Weight",
                                        "Temp.",
                                        "Notes",
                                    ].map((heading) => (

                                        <th
                                            key={heading}
                                            className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-left
                                                text-xs
                                                font-semibold
                                                uppercase
                                                tracking-wide
                                                text-slate-500
                                            "
                                        >
                                            {heading}
                                        </th>

                                    ))
                                }

                            </tr>

                        </thead>


                        <tbody className="
                            divide-y
                            divide-slate-100
                        ">

                            {
                                residentVitalHistory.map(
                                    (record) => (

                                        <tr
                                            key={record.id}
                                            className="hover:bg-slate-50"
                                        >

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-sm
                                                font-medium
                                                text-slate-700
                                            ">
                                                {record.recordedAt}
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                font-semibold
                                                text-slate-800
                                            ">
                                                {record.bloodPressure}
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-slate-700
                                            ">
                                                {record.pulse} bpm
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-slate-700
                                            ">
                                                {record.spo2}%
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-slate-700
                                            ">
                                                {
                                                    record.glucose === "-"
                                                        ? "-"
                                                        : `${record.glucose} mmol/L`
                                                }
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-slate-700
                                            ">
                                                {
                                                    record.weight === "-"
                                                        ? "-"
                                                        : `${record.weight} kg`
                                                }
                                            </td>

                                            <td className="
                                                whitespace-nowrap
                                                px-5
                                                py-4
                                                text-slate-700
                                            ">
                                                {record.temperature}°C
                                            </td>

                                            <td className="
                                                min-w-[220px]
                                                px-5
                                                py-4
                                                text-sm
                                                text-slate-500
                                            ">
                                                {record.notes || "-"}
                                            </td>

                                        </tr>

                                    )
                                )
                            }

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    )
}


{/* MEDICATION TAB */}

{
    activeTab === "medication" && (

        <div className="w-full min-w-0 space-y-5 sm:space-y-6">

            {/* Medication page heading */}
            <div className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-4
                shadow-sm
                sm:p-6
            ">
                <div className="
                    flex
                    flex-col
                    gap-4
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">
                    <div className="flex min-w-0 items-start gap-4">
                        <div className="
                            hidden
                            h-12
                            w-12
                            shrink-0
                            items-center
                            justify-center
                            rounded-xl
                            bg-blue-50
                            text-2xl
                            sm:flex
                        ">
                            💊
                        </div>

                        <div className="min-w-0">
                            <h2 className="
                                break-words
                                text-xl
                                font-bold
                                text-slate-800
                                sm:text-2xl
                            ">
                                Today's Medication
                            </h2>

                            <p className="
                                mt-2
                                max-w-3xl
                                text-sm
                                leading-6
                                text-slate-500
                            ">
                                Review AM, PM and Night medication. Confirm the meal only after the resident has eaten, then complete the medication round.
                            </p>
                        </div>
                    </div>

                    <div className="
                        w-full
                        rounded-xl
                        bg-blue-50
                        px-4
                        py-3
                        lg:w-auto
                        lg:min-w-[180px]
                    ">
                        <p className="text-xs font-semibold uppercase tracking-wide text-blue-500">
                            Resident
                        </p>

                        <p className="mt-1 break-words font-bold text-blue-700">
                            {resident.name}
                        </p>
                    </div>
                </div>
            </div>

            {medicationError && (
                <div className="
                    w-full
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    px-4
                    py-4
                    text-sm
                    font-medium
                    text-red-700
                    sm:px-5
                ">
                    {medicationError}
                </div>
            )}

            {medicationLoading ? (
                <div className="
                    w-full
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    text-sm
                    text-slate-500
                    shadow-sm
                ">
                    Loading medication schedule...
                </div>
            ) : (
                <div className="
                    grid
                    w-full
                    min-w-0
                    grid-cols-1
                    gap-4
                    md:grid-cols-2
                    xl:grid-cols-3
                    xl:gap-5
                ">
                    {medicationRoundEntries.map((round) => {
                        const isCompleting =
                            completingMedicationRound === round.key;

                        const hasMedication =
                            round.medications.length > 0;

                        const pendingCount =
                            round.medications.length -
                            round.completedMedicationCount;

                        const canComplete =
                            hasMedication &&
                            pendingCount > 0 &&
                            round.mealConfirmed &&
                            !isCompleting;

                        return (
                            <section
                                key={round.key}
                                className="
                                    flex
                                    min-w-0
                                    flex-col
                                    overflow-hidden
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    shadow-sm
                                "
                            >
                                {/* Round header */}
                                <div className="border-b border-slate-200 px-4 py-5 sm:px-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-2xl font-bold text-slate-800">
                                                    {round.label}
                                                </h3>

                                                <span
                                                    className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                                        !hasMedication
                                                            ? "bg-slate-100 text-slate-500"
                                                            : round.allMedicationCompleted
                                                                ? "bg-emerald-50 text-emerald-700"
                                                                : "bg-amber-50 text-amber-700"
                                                    }`}
                                                >
                                                    {!hasMedication
                                                        ? "No Medication"
                                                        : round.allMedicationCompleted
                                                            ? "Completed"
                                                            : `${pendingCount} Pending`}
                                                </span>
                                            </div>

                                            <p className="mt-2 text-sm text-slate-500">
                                                {round.scheduledTime} • {round.meal}
                                            </p>
                                        </div>

                                        <div className="shrink-0 text-2xl">
                                            {round.icon}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-1 flex-col gap-5 p-4 sm:p-5">

                                    {/* Medication list */}
                                    <div>
                                        <p className="mb-3 text-sm font-semibold text-slate-700">
                                            Medication
                                        </p>

                                        {hasMedication ? (
                                            <div className="space-y-3">
                                                {round.medications.map((medication) => {
                                                    const completed =
                                                        String(
                                                            medication.status || ""
                                                        ).toUpperCase() === "COMPLETED";

                                                    return (
                                                        <div
                                                            key={medication.id}
                                                            className={`min-w-0 rounded-xl border p-4 ${
                                                                completed
                                                                    ? "border-emerald-200 bg-emerald-50"
                                                                    : "border-slate-200 bg-slate-50"
                                                            }`}
                                                        >
                                                            <div className="
                                                                flex
                                                                flex-col
                                                                gap-3
                                                                sm:flex-row
                                                                sm:items-start
                                                                sm:justify-between
                                                            ">
                                                                <div className="min-w-0">
                                                                    <p className="break-words font-semibold text-slate-800">
                                                                        {medication.medicine || "Medication"}
                                                                    </p>

                                                                    <p className="mt-1 break-words text-sm leading-5 text-slate-500">
                                                                        {medication.dosage || "-"}
                                                                        {medication.instruction
                                                                            ? ` • ${medication.instruction}`
                                                                            : ""}
                                                                    </p>

                                                                    {medication.quantity !== null &&
                                                                        medication.quantity !== undefined && (
                                                                            <p className="mt-1 text-xs text-slate-400">
                                                                                Quantity: {medication.quantity}
                                                                            </p>
                                                                        )}
                                                                </div>

                                                                <span
                                                                    className={`w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                                                                        completed
                                                                            ? "bg-emerald-100 text-emerald-700"
                                                                            : "bg-amber-100 text-amber-700"
                                                                    }`}
                                                                >
                                                                    {completed ? "Given ✓" : "Pending"}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            <div className="
                                                flex
                                                min-h-[125px]
                                                items-center
                                                justify-center
                                                rounded-xl
                                                border
                                                border-dashed
                                                border-slate-200
                                                bg-slate-50
                                                p-5
                                                text-center
                                            ">
                                                <div>
                                                    <div className="text-3xl">🧴</div>
                                                    <p className="mt-3 text-sm font-medium text-slate-500">
                                                        No medication is scheduled for this round.
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Meal confirmation */}
                                    <div>
                                        <p className="mb-3 text-sm font-semibold text-slate-700">
                                            Meal Confirmation
                                        </p>

                                        <label
                                            className={`flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border p-4 ${
                                                round.mealConfirmed
                                                    ? "border-emerald-200 bg-emerald-50"
                                                    : "border-slate-200 bg-slate-50"
                                            } ${
                                                round.allMedicationCompleted || isCompleting
                                                    ? "cursor-not-allowed opacity-75"
                                                    : ""
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={round.mealConfirmed}
                                                disabled={round.allMedicationCompleted || isCompleting}
                                                onChange={() =>
                                                    handleMealConfirmationChange(round.key)
                                                }
                                                className="mt-1 h-4 w-4 shrink-0"
                                            />

                                            <div className="min-w-0">
                                                <p className="break-words font-semibold text-slate-800">
                                                    {round.meal} taken
                                                </p>

                                                <p className="mt-1 text-sm leading-5 text-slate-500">
                                                    Confirm after the resident has taken the meal.
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    {/* Action */}
                                    <div className="mt-auto">
                                        <button
                                            type="button"
                                            disabled={!canComplete}
                                            onClick={() =>
                                                handleCompleteMedicationRound(round)
                                            }
                                            className={`w-full rounded-xl px-4 py-3 text-sm font-semibold transition sm:text-base ${
                                                round.allMedicationCompleted
                                                    ? "cursor-not-allowed bg-emerald-100 text-emerald-700"
                                                    : canComplete
                                                        ? "bg-blue-600 text-white hover:bg-blue-700"
                                                        : "cursor-not-allowed bg-slate-100 text-slate-400"
                                            }`}
                                        >
                                            {isCompleting
                                                ? "Saving..."
                                                : round.allMedicationCompleted
                                                    ? "Medication Completed ✓"
                                                    : !hasMedication
                                                        ? "No Medication Scheduled"
                                                        : `Complete ${round.label} Round`}
                                        </button>

                                        {round.allMedicationCompleted && (
                                            <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                                <p className="text-sm font-semibold text-emerald-800">
                                                    Today's {round.label} medication has been recorded.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </section>
                        );
                    })}
                </div>
            )}

            {/* WhatsApp information */}
            <section className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-blue-200
                bg-blue-50
                p-4
                sm:p-6
            ">
                <div className="
                    flex
                    flex-col
                    gap-4
                    sm:flex-row
                    sm:items-start
                ">
                    <div className="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-blue-600
                        text-xl
                        text-white
                    ">
                        💬
                    </div>

                    <div className="min-w-0 flex-1">
                        <h3 className="text-lg font-bold text-slate-800">
                            Family WhatsApp Notification
                        </h3>

                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Medication completion is saved to the backend. Meal confirmation is still temporary on this screen, so SmartCare will not report a WhatsApp message as sent until the meal and WhatsApp backend is connected.
                        </p>

                        <div className="
                            mt-4
                            min-w-0
                            rounded-xl
                            border
                            border-white
                            bg-white
                            p-4
                        ">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Future notification example
                            </p>

                            <p className="mt-2 break-words text-sm font-semibold leading-6 text-slate-700">
                                {resident.name} has taken the AM medication and breakfast.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Medication History */}
            <section className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">
                <div className="
                    flex
                    flex-col
                    gap-3
                    border-b
                    border-slate-200
                    p-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    sm:p-6
                ">
                    <div className="min-w-0">
                        <h3 className="text-lg font-bold text-slate-800">
                            Medication History
                        </h3>

                        <p className="mt-1 text-sm text-slate-500">
                            Medication administrations recorded for this resident.
                        </p>
                    </div>

                    <span className="
                        w-fit
                        shrink-0
                        rounded-full
                        bg-slate-100
                        px-3
                        py-1
                        text-xs
                        font-semibold
                        text-slate-600
                    ">
                        {medicationHistory.length} record{medicationHistory.length === 1 ? "" : "s"}
                    </span>
                </div>

                {medicationHistory.length > 0 ? (
                    <>
                        {/* Mobile / tablet cards */}
                        <div className="divide-y divide-slate-100 lg:hidden">
                            {medicationHistory.map((entry, index) => (
                                <div
                                    key={`mobile-${entry.medicine || "medication"}-${entry.completed_time || index}-${index}`}
                                    className="p-4 sm:p-5"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="break-words font-semibold text-slate-800">
                                                {entry.medicine || "-"}
                                            </p>

                                            <p className="mt-1 text-sm text-slate-500">
                                                {entry.dosage || "-"}
                                            </p>
                                        </div>

                                        <span
                                            className={`w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                                                String(entry.status || "").toUpperCase() === "COMPLETED"
                                                    ? "bg-emerald-50 text-emerald-700"
                                                    : "bg-amber-50 text-amber-700"
                                            }`}
                                        >
                                            {entry.status || "-"}
                                        </span>
                                    </div>

                                    <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Round
                                            </p>
                                            <p className="mt-1 text-slate-700">
                                                {entry.time_slot || "-"}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Nurse
                                            </p>
                                            <p className="mt-1 break-words text-slate-700">
                                                {entry.completed_by || "-"}
                                            </p>
                                        </div>

                                        <div className="col-span-2">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Completed
                                            </p>
                                            <p className="mt-1 text-slate-700">
                                                {formatMedicationDateTime(entry.completed_time)}
                                            </p>
                                        </div>

                                        {entry.remarks && (
                                            <div className="col-span-2">
                                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Remarks
                                                </p>
                                                <p className="mt-1 break-words text-slate-600">
                                                    {entry.remarks}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Laptop / desktop table */}
                        <div className="hidden overflow-x-auto lg:block">
                            <table className="w-full min-w-[900px] divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        {[
                                            "Medication",
                                            "Dosage",
                                            "Round",
                                            "Status",
                                            "Completed",
                                            "Nurse",
                                            "Remarks",
                                        ].map((heading) => (
                                            <th
                                                key={heading}
                                                className="whitespace-nowrap px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                                            >
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {medicationHistory.map((entry, index) => (
                                        <tr
                                            key={`${entry.medicine || "medication"}-${entry.completed_time || index}-${index}`}
                                            className="hover:bg-slate-50"
                                        >
                                            <td className="px-5 py-4 font-semibold text-slate-800">
                                                <div className="max-w-[220px] break-words">
                                                    {entry.medicine || "-"}
                                                </div>
                                            </td>

                                            <td className="px-5 py-4 text-slate-700">
                                                <div className="max-w-[180px] break-words">
                                                    {entry.dosage || "-"}
                                                </div>
                                            </td>

                                            <td className="whitespace-nowrap px-5 py-4 text-slate-700">
                                                {entry.time_slot || "-"}
                                            </td>

                                            <td className="whitespace-nowrap px-5 py-4">
                                                <span
                                                    className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                                        String(entry.status || "").toUpperCase() === "COMPLETED"
                                                            ? "bg-emerald-50 text-emerald-700"
                                                            : "bg-amber-50 text-amber-700"
                                                    }`}
                                                >
                                                    {entry.status || "-"}
                                                </span>
                                            </td>

                                            <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                {formatMedicationDateTime(entry.completed_time)}
                                            </td>

                                            <td className="px-5 py-4 text-sm text-slate-600">
                                                <div className="max-w-[180px] break-words">
                                                    {entry.completed_by || "-"}
                                                </div>
                                            </td>

                                            <td className="px-5 py-4 text-sm text-slate-500">
                                                <div className="max-w-[220px] break-words">
                                                    {entry.remarks || "-"}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <div className="
                        flex
                        min-h-[180px]
                        items-center
                        justify-center
                        p-6
                        text-center
                    ">
                        <div>
                            <div className="
                                mx-auto
                                flex
                                h-12
                                w-12
                                items-center
                                justify-center
                                rounded-full
                                bg-slate-100
                                text-xl
                            ">
                                📋
                            </div>

                            <p className="mt-4 text-sm font-medium text-slate-500">
                                No medication administrations have been recorded yet.
                            </p>

                            <p className="mt-1 text-sm text-slate-400">
                                Completed medication will appear here.
                            </p>
                        </div>
                    </div>
                )}
            </section>

            {(medicationSchedule.OTHER || []).length > 0 && (
                <section className="
                    w-full
                    min-w-0
                    overflow-hidden
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                    shadow-sm
                    sm:p-6
                ">
                    <h3 className="text-lg font-bold text-slate-800">
                        Other / On-Demand Medication
                    </h3>

                    <div className="mt-4 space-y-3">
                        {medicationSchedule.OTHER.map((medication) => (
                            <div
                                key={medication.id}
                                className="
                                    flex
                                    min-w-0
                                    flex-col
                                    gap-3
                                    rounded-xl
                                    bg-slate-50
                                    p-4
                                    sm:flex-row
                                    sm:items-center
                                    sm:justify-between
                                "
                            >
                                <div className="min-w-0">
                                    <p className="break-words font-semibold text-slate-800">
                                        {medication.medicine || "Medication"}
                                    </p>

                                    <p className="mt-1 break-words text-sm text-slate-500">
                                        {medication.dosage || "-"}
                                        {medication.instruction
                                            ? ` • ${medication.instruction}`
                                            : ""}
                                    </p>
                                </div>

                                <span className="w-fit shrink-0 rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {medication.status || "PENDING"}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>
            )}

        </div>

    )
}


{/* CARE TAB */}

{
    activeTab === "care" && (

        <div className="w-full min-w-0 space-y-5 sm:space-y-6">

            {/* Care heading */}
            <section className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-4
                shadow-sm
                sm:p-6
            ">
                <div className="
                    flex
                    flex-col
                    gap-4
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">
                    <div className="min-w-0">
                        <div className="flex items-center gap-3">
                            <div className="
                                hidden
                                h-11
                                w-11
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-blue-50
                                text-xl
                                sm:flex
                            ">
                                🩺
                            </div>

                            <div>
                                <h2 className="text-xl font-bold text-slate-800 sm:text-2xl">
                                    Care Records
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                    Record personal care, nutrition, mobility, nursing observations and other care activities for {resident.name}.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openNewCareRecord}
                        className="
                            w-full
                            rounded-xl
                            bg-blue-600
                            px-5
                            py-3
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-blue-700
                            sm:w-auto
                        "
                    >
                        + Add Care Record
                    </button>
                </div>
            </section>

            {careError && (
                <div className="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    px-5
                    py-4
                    text-sm
                    font-medium
                    text-red-700
                ">
                    {careError}
                </div>
            )}

            {/* Today summary */}
            <section className="
                grid
                grid-cols-2
                gap-3
                sm:grid-cols-4
                sm:gap-4
            ">
                <CareSummaryCard
                    label="Today"
                    value={todayCareRecords.length}
                    detail="care records"
                    tone="blue"
                />

                <CareSummaryCard
                    label="Completed"
                    value={completedCareRecords.length}
                    detail="today"
                    tone="green"
                />

                <CareSummaryCard
                    label="Observed"
                    value={observedCareRecords.length}
                    detail="all records"
                    tone="amber"
                />

                <CareSummaryCard
                    label="Needs Attention"
                    value={attentionCareRecords.length}
                    detail="requires review"
                    tone="red"
                />
            </section>

            {/* Add/Edit form */}
            {showCareForm && (
                <section className="
                    overflow-hidden
                    rounded-2xl
                    border
                    border-blue-200
                    bg-white
                    shadow-sm
                ">
                    <div className="border-b border-slate-200 bg-blue-50 px-4 py-5 sm:px-6">
                        <h3 className="text-lg font-bold text-slate-800">
                            {editingCareRecordId
                                ? "Edit Care Record"
                                : "Add Care Record"}
                        </h3>

                        <p className="mt-1 text-sm text-slate-500">
                            Resident: {resident.name}. The logged-in user will be recorded automatically.
                        </p>
                    </div>

                    <form
                        onSubmit={handleSaveCareRecord}
                        className="space-y-5 p-4 sm:p-6"
                    >
                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Care Type *
                                </label>

                                <select
                                    name="care_type"
                                    value={careForm.care_type}
                                    onChange={handleCareFormChange}
                                    className="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-300
                                        bg-white
                                        px-4
                                        py-3
                                        outline-none
                                        focus:border-blue-500
                                        focus:ring-4
                                        focus:ring-blue-100
                                    "
                                >
                                    {careTypeOptions.map((type) => (
                                        <option key={type} value={type}>
                                            {type}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">
                                    Status *
                                </label>

                                <select
                                    name="care_status"
                                    value={careForm.care_status}
                                    onChange={handleCareFormChange}
                                    className="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-300
                                        bg-white
                                        px-4
                                        py-3
                                        outline-none
                                        focus:border-blue-500
                                        focus:ring-4
                                        focus:ring-blue-100
                                    "
                                >
                                    <option value="COMPLETED">Completed</option>
                                    <option value="OBSERVED">Observed</option>
                                    <option value="NEEDS_ATTENTION">Needs Attention</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Short Description *
                            </label>

                            <input
                                type="text"
                                name="title"
                                value={careForm.title}
                                onChange={handleCareFormChange}
                                placeholder="Example: Morning hygiene completed"
                                maxLength={255}
                                className="
                                    w-full
                                    rounded-xl
                                    border
                                    border-slate-300
                                    px-4
                                    py-3
                                    outline-none
                                    focus:border-blue-500
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Notes
                            </label>

                            <textarea
                                rows="4"
                                name="notes"
                                value={careForm.notes}
                                onChange={handleCareFormChange}
                                placeholder="Optional nursing observation or additional information..."
                                className="
                                    w-full
                                    resize-y
                                    rounded-xl
                                    border
                                    border-slate-300
                                    px-4
                                    py-3
                                    outline-none
                                    focus:border-blue-500
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            />
                        </div>

                        <div className="max-w-md">
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Date & Time
                            </label>

                            <input
                                type="datetime-local"
                                name="recorded_at"
                                value={careForm.recorded_at}
                                onChange={handleCareFormChange}
                                className="
                                    w-full
                                    rounded-xl
                                    border
                                    border-slate-300
                                    px-4
                                    py-3
                                    outline-none
                                    focus:border-blue-500
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            />

                            <p className="mt-2 text-xs text-slate-400">
                                Leave blank to use the current date and time.
                            </p>
                        </div>

                        <div className="
                            flex
                            flex-col-reverse
                            gap-3
                            border-t
                            border-slate-100
                            pt-5
                            sm:flex-row
                            sm:justify-end
                        ">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowCareForm(false);
                                    resetCareForm();
                                    setCareError("");
                                }}
                                className="
                                    rounded-xl
                                    border
                                    border-slate-300
                                    px-5
                                    py-3
                                    font-semibold
                                    text-slate-700
                                    hover:bg-slate-50
                                "
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={savingCareRecord}
                                className="
                                    rounded-xl
                                    bg-blue-600
                                    px-6
                                    py-3
                                    font-semibold
                                    text-white
                                    hover:bg-blue-700
                                    disabled:cursor-not-allowed
                                    disabled:opacity-60
                                "
                            >
                                {savingCareRecord
                                    ? "Saving..."
                                    : editingCareRecordId
                                        ? "Update Care Record"
                                        : "Save Care Record"}
                            </button>
                        </div>
                    </form>
                </section>
            )}

            {/* Care history */}
            <section className="
                w-full
                min-w-0
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">
                <div className="
                    flex
                    flex-col
                    gap-3
                    border-b
                    border-slate-200
                    p-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    sm:p-6
                ">
                    <div>
                        <h3 className="text-lg font-bold text-slate-800">
                            Care History
                        </h3>

                        <p className="mt-1 text-sm text-slate-500">
                            Care activities and observations recorded for this resident.
                        </p>
                    </div>

                    <span className="
                        w-fit
                        rounded-full
                        bg-slate-100
                        px-3
                        py-1
                        text-xs
                        font-semibold
                        text-slate-600
                    ">
                        {careApiRecords.length} record{careApiRecords.length === 1 ? "" : "s"}
                    </span>
                </div>

                {careLoading ? (
                    <div className="p-8 text-center text-sm text-slate-500">
                        Loading care records...
                    </div>
                ) : careApiRecords.length > 0 ? (
                    <div className="divide-y divide-slate-100">
                        {careApiRecords.map((record) => (
                            <article
                                key={record.id}
                                className="
                                    p-4
                                    transition
                                    hover:bg-slate-50
                                    sm:p-5
                                    lg:p-6
                                "
                            >
                                <div className="
                                    flex
                                    flex-col
                                    gap-4
                                    lg:flex-row
                                    lg:items-start
                                    lg:justify-between
                                ">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="
                                                rounded-full
                                                bg-blue-50
                                                px-3
                                                py-1
                                                text-xs
                                                font-semibold
                                                text-blue-700
                                            ">
                                                {record.care_type || "Care"}
                                            </span>

                                            <span
                                                className={`rounded-full px-3 py-1 text-xs font-semibold ${getCareStatusStyle(
                                                    record.care_status
                                                )}`}
                                            >
                                                {getCareStatusLabel(record.care_status)}
                                            </span>
                                        </div>

                                        <h4 className="
                                            mt-3
                                            break-words
                                            text-base
                                            font-bold
                                            text-slate-800
                                            sm:text-lg
                                        ">
                                            {record.title}
                                        </h4>

                                        {record.notes && (
                                            <p className="
                                                mt-2
                                                break-words
                                                text-sm
                                                leading-6
                                                text-slate-600
                                            ">
                                                {record.notes}
                                            </p>
                                        )}

                                        <div className="
                                            mt-4
                                            flex
                                            flex-wrap
                                            gap-x-4
                                            gap-y-2
                                            text-xs
                                            text-slate-400
                                        ">
                                            <span>
                                                {formatCareDateTime(record.recorded_at)}
                                            </span>

                                            <span>
                                                Recorded by {record.recorder?.full_name || record.recorder?.name || "System / Unassigned"}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 gap-2">
                                        <button
                                            type="button"
                                            onClick={() => openEditCareRecord(record)}
                                            className="
                                                rounded-lg
                                                border
                                                border-slate-300
                                                px-4
                                                py-2
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:border-blue-300
                                                hover:bg-blue-50
                                                hover:text-blue-700
                                            "
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => handleDeleteCareRecord(record)}
                                            className="
                                                rounded-lg
                                                border
                                                border-rose-200
                                                px-4
                                                py-2
                                                text-sm
                                                font-semibold
                                                text-rose-600
                                                hover:bg-rose-50
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="
                        flex
                        min-h-[240px]
                        items-center
                        justify-center
                        p-6
                        text-center
                    ">
                        <div className="max-w-md">
                            <div className="
                                mx-auto
                                flex
                                h-14
                                w-14
                                items-center
                                justify-center
                                rounded-full
                                bg-blue-50
                                text-2xl
                            ">
                                🩺
                            </div>

                            <h4 className="mt-4 font-bold text-slate-800">
                                No care records yet
                            </h4>

                            <p className="mt-2 text-sm leading-6 text-slate-500">
                                Add the first care record after completing a care activity or recording an important observation.
                            </p>

                            <button
                                type="button"
                                onClick={openNewCareRecord}
                                className="
                                    mt-5
                                    rounded-xl
                                    bg-blue-600
                                    px-5
                                    py-3
                                    text-sm
                                    font-semibold
                                    text-white
                                    hover:bg-blue-700
                                "
                            >
                                + Add First Care Record
                            </button>
                        </div>
                    </div>
                )}
            </section>

            <section className="
                rounded-2xl
                border
                border-blue-100
                bg-blue-50
                p-5
                sm:p-6
            ">
                <div className="flex items-start gap-3">
                    <div className="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center
                        rounded-lg
                        bg-blue-600
                        text-white
                    ">
                        ✓
                    </div>

                    <div>
                        <h3 className="font-bold text-slate-800">
                            One Care Entry, Used Across SmartCare
                        </h3>

                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Care records are now stored against the selected resident in Laravel. The same records also appear in the resident Timeline, so nurses do not need to enter the same information twice.
                        </p>
                    </div>
                </div>
            </section>

        </div>

    )
}

{/* AI INSIGHTS TAB */}
{
    activeTab === "ai" && (
        <div className="space-y-6">
            <div className="rounded-xl border border-blue-100 bg-blue-50 p-6">
                <div className="flex items-start gap-4">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">✦</div>
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-xl font-bold text-slate-800">AI Clinical Overview</h2>
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${resident.careStatus === "Needs Review" ? "bg-amber-100 text-amber-700" : "bg-emerald-100 text-emerald-700"}`}>
                                {resident.careStatus === "Needs Review" ? "Review Recommended" : "Stable"}
                            </span>
                        </div>
                        <p className="mt-3 max-w-4xl leading-7 text-slate-700">{resident.aiSummary}</p>
                        <p className="mt-3 text-xs text-slate-500">AI information supports nursing review and does not replace clinical judgment.</p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <InsightCard title="Latest Vitals" value={`${resident.latestVitals.bloodPressure} BP`} detail={`SpO₂ ${resident.latestVitals.spo2} • Glucose ${resident.latestVitals.glucose}`} />
                <InsightCard
                    title="Care Activity"
                    value={`${todayCareRecords.length} today`}
                    detail={
                        attentionCareRecords.length > 0
                            ? `${attentionCareRecords.length} care record(s) need attention`
                            : "No care records currently need attention"
                    }
                />
                <InsightCard title="Medication" value={`${completedMedicationRounds}/3 rounds`} detail="Medication rounds require nurse confirmation before completion." />
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-bold text-slate-800">What Needs Attention</h3>
                    <div className="mt-5 space-y-3">
                        <AIActionRow priority={resident.careStatus === "Needs Review" ? "High" : "Routine"} text={resident.careStatus === "Needs Review" ? "Review the latest observations and repeat abnormal vital signs if clinically appropriate." : "Continue routine monitoring and scheduled care."} />
                        <AIActionRow priority="Routine" text={`${3 - completedMedicationRounds} medication round(s) remain pending today.`} />
                        <AIActionRow
                            priority={attentionCareRecords.length > 0 ? "High" : "Routine"}
                            text={
                                attentionCareRecords.length > 0
                                    ? `${attentionCareRecords.length} care record(s) are marked as needing attention.`
                                    : todayCareRecords.length > 0
                                        ? `${todayCareRecords.length} care record(s) have been recorded today.`
                                        : "No care records have been entered today."
                            }
                        />
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-bold text-slate-800">Trend Interpretation</h3>
                    <div className="mt-5 space-y-4">
                        <InfoRow label="Blood Pressure" value={resident.latestVitals.bloodPressure} />
                        <InfoRow label="SpO₂" value={resident.latestVitals.spo2} />
                        <InfoRow label="Glucose" value={resident.latestVitals.glucose} />
                        <InfoRow label="Recorded readings" value={`${residentVitalHistory.length}`} />
                    </div>
                    <button type="button" onClick={() => setActiveTab("vitals")} className="mt-5 text-sm font-semibold text-blue-600">View Vital Trends →</button>
                </div>
            </div>
        </div>
    )
}

{/* TIMELINE TAB */}

{
    activeTab === "timeline" && (

        <div className="space-y-6">


            <section className="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
                sm:p-6
            ">

                <div className="
                    flex
                    flex-col
                    gap-3
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                ">

                    <div>

                        <h2 className="
                            text-xl
                            font-bold
                            text-slate-800
                            sm:text-2xl
                        ">
                            Resident Timeline
                        </h2>

                        <p className="
                            mt-1
                            max-w-3xl
                            text-sm
                            leading-6
                            text-slate-500
                        ">
                            Chronological clinical and operational history for {resident.name}.
                        </p>

                    </div>


                    <span className="
                        w-fit
                        rounded-full
                        bg-slate-100
                        px-3
                        py-1
                        text-xs
                        font-semibold
                        text-slate-600
                    ">
                        {clinicalTimeline.length} event
                        {clinicalTimeline.length === 1 ? "" : "s"}
                    </span>

                </div>

            </section>


            {timelineError && (

                <div className="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    px-5
                    py-4
                    text-sm
                    font-medium
                    text-red-700
                ">
                    {timelineError}
                </div>

            )}


            {timelineLoading ? (

                <div className="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-8
                    text-center
                    text-sm
                    text-slate-500
                    shadow-sm
                ">
                    Loading resident timeline...
                </div>

            ) : clinicalTimeline.length > 0 ? (

                <section className="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                    shadow-sm
                    sm:p-6
                ">

                    <div className="space-y-0">

                        {clinicalTimeline.map(
                            (entry, index) => (

                                <div
                                    key={`${entry.source || entry.type}-${entry.title}-${entry.date}-${index}`}
                                    className="
                                        relative
                                        flex
                                        gap-4
                                        pb-7
                                        last:pb-0
                                    "
                                >

                                    {index !==
                                        clinicalTimeline.length - 1 && (

                                        <div className="
                                            absolute
                                            left-[11px]
                                            top-7
                                            h-[calc(100%-8px)]
                                            w-px
                                            bg-slate-200
                                        " />

                                    )}


                                    <div className="
                                        relative
                                        z-[1]
                                        mt-1
                                        h-6
                                        w-6
                                        shrink-0
                                        rounded-full
                                        border-4
                                        border-white
                                        bg-blue-500
                                        ring-1
                                        ring-slate-200
                                    " />


                                    <article className="
                                        min-w-0
                                        flex-1
                                        rounded-xl
                                        border
                                        border-slate-100
                                        bg-slate-50
                                        p-4
                                        sm:p-5
                                    ">

                                        <div className="
                                            flex
                                            flex-col
                                            gap-3
                                            lg:flex-row
                                            lg:items-start
                                            lg:justify-between
                                        ">

                                            <div className="min-w-0">

                                                <div className="
                                                    flex
                                                    flex-wrap
                                                    items-center
                                                    gap-2
                                                ">

                                                    <span
                                                        className={`
                                                            rounded-full
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-semibold
                                                            ${getTimelineBadgeStyle(entry)}
                                                        `}
                                                    >
                                                        {getTimelineCategory(entry)}
                                                    </span>


                                                    {entry.severity && (

                                                        <span className="
                                                            rounded-full
                                                            bg-rose-100
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-semibold
                                                            text-rose-700
                                                        ">
                                                            {entry.severity}
                                                        </span>

                                                    )}

                                                </div>


                                                <h3 className="
                                                    mt-3
                                                    break-words
                                                    font-bold
                                                    text-slate-800
                                                    sm:text-lg
                                                ">
                                                    {entry.title || "Clinical Event"}
                                                </h3>

                                            </div>


                                            <span className="
                                                shrink-0
                                                text-xs
                                                text-slate-400
                                            ">
                                                {formatTimelineDate(
                                                    entry.date
                                                )}
                                            </span>

                                        </div>


                                        {entry.clinical_summary && (

                                            <p className="
                                                mt-3
                                                break-words
                                                text-sm
                                                leading-6
                                                text-slate-600
                                            ">
                                                {entry.clinical_summary}
                                            </p>

                                        )}


                                        <div className="
                                            mt-4
                                            flex
                                            flex-wrap
                                            gap-x-4
                                            gap-y-2
                                            border-t
                                            border-slate-200
                                            pt-3
                                            text-xs
                                            text-slate-400
                                        ">

                                            <span>
                                                Event: {entry.type || "—"}
                                            </span>

                                            {entry.source && (

                                                <span>
                                                    Source: {entry.source}
                                                </span>

                                            )}

                                        </div>

                                    </article>

                                </div>

                            )
                        )}

                    </div>

                </section>

            ) : (

                <section className="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-10
                    text-center
                    shadow-sm
                ">

                    <div className="
                        mx-auto
                        flex
                        h-14
                        w-14
                        items-center
                        justify-center
                        rounded-full
                        bg-blue-50
                        text-2xl
                    ">
                        📋
                    </div>

                    <h3 className="
                        mt-4
                        font-bold
                        text-slate-800
                    ">
                        No timeline events yet
                    </h3>

                    <p className="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                        Clinical and operational events will appear here automatically.
                    </p>

                </section>

            )}

        </div>

    )
}

{/* DOCUMENTS TAB */}
{
    activeTab === "documents" && (
        <div className="w-full min-w-0 space-y-5 sm:space-y-6">

            <section className="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex min-w-0 items-start gap-3">
                        <div className="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xl sm:flex">📄</div>
                        <div className="min-w-0">
                            <h2 className="text-xl font-bold text-slate-800 sm:text-2xl">Documents & Forms</h2>
                            <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                                Upload and manage resident documents in one place. Nurses and management can keep admission, consent, clinical, discharge and supporting records linked directly to {resident.name}.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openNewDocumentForm}
                        className="w-full rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto"
                    >
                        + Upload Document
                    </button>
                </div>
            </section>

            {documentsError && (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {documentsError}
                </div>
            )}

            <section className="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                <DocumentSummaryCard label="Total" value={residentDocuments.length} detail="documents" tone="blue" />
                <DocumentSummaryCard
                    label="Active"
                    value={residentDocuments.filter((document) => String(document.status || "").toUpperCase() !== "ARCHIVED").length}
                    detail="available"
                    tone="green"
                />
                <DocumentSummaryCard
                    label="Clinical"
                    value={residentDocuments.filter((document) => ["MEDICAL", "CLINICAL", "LAB RESULT", "MEDICATION", "CARE"].includes(String(document.document_type || "").toUpperCase())).length}
                    detail="clinical"
                    tone="amber"
                />
                <DocumentSummaryCard
                    label="Archived"
                    value={residentDocuments.filter((document) => String(document.status || "").toUpperCase() === "ARCHIVED").length}
                    detail="records"
                    tone="slate"
                />
            </section>

            {showDocumentForm && (
                <section className="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 bg-blue-50 px-4 py-5 sm:px-6">
                        <h3 className="text-lg font-bold text-slate-800">
                            {editingDocumentId ? "Edit Document Details" : "Upload Resident Document"}
                        </h3>
                        <p className="mt-1 text-sm leading-6 text-slate-500">
                            {editingDocumentId
                                ? "Update the document title, category, notes or archive status. The original uploaded file is not replaced."
                                : `Choose a file for ${resident.name}. The logged-in user will be recorded automatically.`}
                        </p>
                    </div>

                    <form onSubmit={handleSaveDocument} className="space-y-6 p-4 sm:p-6">
                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">Document Type *</label>
                                <select
                                    name="document_type"
                                    value={documentForm.document_type}
                                    onChange={handleDocumentFormChange}
                                    className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    {documentTypeOptions.map((type) => (
                                        <option key={type} value={type}>{type}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">Title *</label>
                                <input
                                    type="text"
                                    name="title"
                                    value={documentForm.title}
                                    onChange={handleDocumentFormChange}
                                    placeholder="Example: Admission Consent Form"
                                    maxLength={255}
                                    className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                />
                            </div>
                        </div>

                        {!editingDocumentId && (
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">File *</label>
                                <label className="flex min-h-[120px] cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 text-center transition hover:border-blue-300 hover:bg-blue-50">
                                    <div className="text-3xl">📎</div>
                                    <p className="mt-3 font-semibold text-slate-700">
                                        {selectedDocumentFile ? selectedDocumentFile.name : "Choose document"}
                                    </p>
                                    <p className="mt-1 text-xs leading-5 text-slate-400">PDF, JPG, PNG, DOC or DOCX • Maximum 10 MB</p>
                                    <input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        onChange={handleDocumentFileChange}
                                        className="hidden"
                                    />
                                </label>
                            </div>
                        )}

                        {editingDocumentId && (
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                                <select
                                    name="status"
                                    value={documentForm.status}
                                    onChange={handleDocumentFormChange}
                                    className="w-full max-w-md rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="ACTIVE">Active</option>
                                    <option value="ARCHIVED">Archived</option>
                                </select>
                            </div>
                        )}

                        <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                            <textarea
                                rows="4"
                                name="notes"
                                value={documentForm.notes}
                                onChange={handleDocumentFormChange}
                                placeholder="Optional notes about this document..."
                                className="w-full resize-y rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            />
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowDocumentForm(false);
                                    resetDocumentForm();
                                    setDocumentsError("");
                                }}
                                className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={savingDocument}
                                className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {savingDocument ? "Saving..." : editingDocumentId ? "Update Document" : "Upload Document"}
                            </button>
                        </div>
                    </form>
                </section>
            )}

            <section className="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <h3 className="text-lg font-bold text-slate-800">Resident Documents</h3>
                        <p className="mt-1 text-sm text-slate-500">Uploaded files and supporting records for this resident.</p>
                    </div>
                    <span className="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {residentDocuments.length} document{residentDocuments.length === 1 ? "" : "s"}
                    </span>
                </div>

                {documentsLoading ? (
                    <div className="p-8 text-center text-sm text-slate-500">Loading resident documents...</div>
                ) : residentDocuments.length > 0 ? (
                    <div className="divide-y divide-slate-100">
                        {residentDocuments.map((document) => {
                            const archived = String(document.status || "").toUpperCase() === "ARCHIVED";

                            return (
                                <article key={document.id} className="p-4 transition hover:bg-slate-50 sm:p-5 lg:p-6">
                                    <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="flex min-w-0 flex-1 items-start gap-4">
                                            <div className={`hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl sm:flex ${archived ? "bg-slate-100" : "bg-blue-50"}`}>
                                                {String(document.mime_type || "").includes("pdf")
                                                    ? "📕"
                                                    : String(document.mime_type || "").includes("image")
                                                        ? "🖼️"
                                                        : "📄"}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                        {document.document_type || "Other"}
                                                    </span>
                                                    <span className={`rounded-full px-3 py-1 text-xs font-semibold ${archived ? "bg-slate-100 text-slate-600" : "bg-emerald-50 text-emerald-700"}`}>
                                                        {archived ? "Archived" : "Active"}
                                                    </span>
                                                </div>

                                                <h4 className="mt-3 break-words text-base font-bold text-slate-800 sm:text-lg">{document.title}</h4>
                                                <p className="mt-1 break-all text-sm text-slate-500">{document.original_name || "Uploaded document"}</p>

                                                {document.notes && (
                                                    <p className="mt-3 break-words text-sm leading-6 text-slate-600">{document.notes}</p>
                                                )}

                                                <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                                    <span>Uploaded {formatDocumentDate(document.created_at)}</span>
                                                    <span>{formatDocumentSize(document.file_size)}</span>
                                                    <span>By {document.uploader?.full_name || "System / Unassigned"}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2 xl:justify-end">
                                            <button type="button" onClick={() => handleDownloadDocument(document)} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Download</button>
                                            <button type="button" onClick={() => openEditDocumentForm(document)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">Edit</button>
                                            <button type="button" onClick={() => handleArchiveDocument(document)} className="rounded-lg border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">{archived ? "Restore" : "Archive"}</button>
                                            <button type="button" onClick={() => handleDeleteDocument(document)} className="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                ) : (
                    <div className="flex min-h-[250px] items-center justify-center p-6 text-center">
                        <div className="max-w-md">
                            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-2xl">📄</div>
                            <h4 className="mt-4 font-bold text-slate-800">No documents uploaded yet</h4>
                            <p className="mt-2 text-sm leading-6 text-slate-500">Upload the resident's first admission, consent, clinical or supporting document.</p>
                            <button type="button" onClick={openNewDocumentForm} className="mt-5 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">+ Upload First Document</button>
                        </div>
                    </div>
                )}
            </section>

            <section className="
    rounded-2xl
    border
    border-emerald-100
    bg-emerald-50
    p-5
    sm:p-6
">

    <div className="flex items-start gap-3">

        <div className="
            flex
            h-10
            w-10
            shrink-0
            items-center
            justify-center
            rounded-xl
            bg-emerald-600
            text-white
        ">
            ✓
        </div>


        <div className="min-w-0">

            <h3 className="
                font-bold
                text-slate-800
            ">
                Structured Digital Workflows Active
            </h3>

            <p className="
                mt-2
                text-sm
                leading-6
                text-slate-600
            ">
                Admission, consent, discharge, monthly glucose,
                weekly vital signs, home leave, visitor and parcel
                workflows are now stored as structured SmartCare
                records. Important workflow events are linked
                automatically to the resident timeline.
            </p>

        </div>

    </div>

</section>

        </div>
    )
}

{/* FAMILY TAB */}
{
    activeTab === "family" && (
        <div className="w-full min-w-0 space-y-5 sm:space-y-6">

            <section className="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-3">
                            <div className="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xl sm:flex">
                                👪
                            </div>

                            <div>
                                <h2 className="text-xl font-bold text-slate-800 sm:text-2xl">
                                    Family & Contacts
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                    Nurses and management can maintain family, emergency and WhatsApp notification details for {resident.name}.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openNewFamilyContact}
                        className="w-full rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto"
                    >
                        + Add Family Contact
                    </button>
                </div>
            </section>

            {familyError && (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {familyError}
                </div>
            )}

            {showFamilyForm && (
                <section className="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 bg-blue-50 px-4 py-5 sm:px-6">
                        <h3 className="text-lg font-bold text-slate-800">
                            {editingFamilyContactId
                                ? "Edit Family Contact"
                                : "Add Family Contact"}
                        </h3>

                        <p className="mt-1 text-sm text-slate-500">
                            Contact information is stored against {resident.name}. The logged-in user is recorded automatically.
                        </p>
                    </div>

                    <form onSubmit={handleSaveFamilyContact} className="space-y-6 p-4 sm:p-6">
                        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <FamilyTextInput
                                label="Full Name"
                                name="full_name"
                                value={familyForm.full_name}
                                onChange={handleFamilyFormChange}
                                placeholder="Example: Michael Ahmad"
                                required
                            />

                            <FamilyTextInput
                                label="Relationship"
                                name="relationship"
                                value={familyForm.relationship}
                                onChange={handleFamilyFormChange}
                                placeholder="Example: Son"
                                required
                            />

                            <FamilyTextInput
                                label="Phone Number"
                                name="phone"
                                value={familyForm.phone}
                                onChange={handleFamilyFormChange}
                                placeholder="Example: 012-3456789"
                                required
                            />

                            <FamilyTextInput
                                label="WhatsApp Number"
                                name="whatsapp_number"
                                value={familyForm.whatsapp_number}
                                onChange={handleFamilyFormChange}
                                placeholder="Leave blank if same as phone"
                            />
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <FamilyToggle
                                label="Primary Contact"
                                description="Main family contact for this resident."
                                name="is_primary"
                                checked={familyForm.is_primary}
                                onChange={handleFamilyFormChange}
                            />

                            <FamilyToggle
                                label="Emergency Contact"
                                description="Contact this person during an emergency."
                                name="is_emergency_contact"
                                checked={familyForm.is_emergency_contact}
                                onChange={handleFamilyFormChange}
                            />

                            <FamilyToggle
                                label="WhatsApp Enabled"
                                description="Allow WhatsApp communication to this contact."
                                name="whatsapp_enabled"
                                checked={familyForm.whatsapp_enabled}
                                onChange={handleFamilyFormChange}
                            />

                            <FamilyToggle
                                label="Medication Updates"
                                description="Receive medication and meal completion notifications."
                                name="medication_notifications_enabled"
                                checked={familyForm.medication_notifications_enabled}
                                onChange={handleFamilyFormChange}
                            />

                            <FamilyToggle
                                label="Care Updates"
                                description="Receive future care-related notifications."
                                name="care_notifications_enabled"
                                checked={familyForm.care_notifications_enabled}
                                onChange={handleFamilyFormChange}
                            />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Notes
                            </label>

                            <textarea
                                rows="4"
                                name="notes"
                                value={familyForm.notes}
                                onChange={handleFamilyFormChange}
                                placeholder="Optional notes about contact preference, availability or communication instructions..."
                                className="w-full resize-y rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            />
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowFamilyForm(false);
                                    resetFamilyForm();
                                    setFamilyError("");
                                }}
                                className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={savingFamilyContact}
                                className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {savingFamilyContact
                                    ? "Saving..."
                                    : editingFamilyContactId
                                        ? "Update Contact"
                                        : "Save Contact"}
                            </button>
                        </div>
                    </form>
                </section>
            )}

            <section className="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <h3 className="text-lg font-bold text-slate-800">
                            Resident Contacts
                        </h3>

                        <p className="mt-1 text-sm text-slate-500">
                            Family and emergency contacts linked to this resident.
                        </p>
                    </div>

                    <span className="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {residentFamilyContacts.length} contact{residentFamilyContacts.length === 1 ? "" : "s"}
                    </span>
                </div>

                {familyLoading ? (
                    <div className="p-8 text-center text-sm text-slate-500">
                        Loading family contacts...
                    </div>
                ) : residentFamilyContacts.length > 0 ? (
                    <div className="grid gap-4 p-4 md:grid-cols-2 sm:p-6">
                        {residentFamilyContacts.map((contact) => (
                            <article
                                key={contact.id}
                                className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h4 className="break-words text-lg font-bold text-slate-800">
                                                {contact.full_name}
                                            </h4>

                                            {contact.is_primary && (
                                                <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    Primary
                                                </span>
                                            )}

                                            {contact.is_emergency_contact && (
                                                <span className="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                    Emergency
                                                </span>
                                            )}
                                        </div>

                                        <p className="mt-1 text-sm text-slate-500">
                                            {contact.relationship}
                                        </p>
                                    </div>

                                    <span
                                        className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                                            contact.whatsapp_enabled
                                                ? "bg-emerald-50 text-emerald-700"
                                                : "bg-slate-100 text-slate-500"
                                        }`}
                                    >
                                        {contact.whatsapp_enabled
                                            ? "WhatsApp On"
                                            : "WhatsApp Off"}
                                    </span>
                                </div>

                                <div className="mt-5 divide-y divide-slate-100">
                                    <InfoRow label="Phone" value={contact.phone || "-"} />
                                    <InfoRow label="WhatsApp" value={contact.whatsapp_number || contact.phone || "-"} />
                                    <InfoRow
                                        label="Medication updates"
                                        value={contact.medication_notifications_enabled ? "Enabled" : "Disabled"}
                                    />
                                    <InfoRow
                                        label="Care updates"
                                        value={contact.care_notifications_enabled ? "Enabled" : "Disabled"}
                                    />
                                </div>

                                {contact.notes && (
                                    <div className="mt-4 rounded-xl bg-slate-50 p-4">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Notes
                                        </p>

                                        <p className="mt-2 break-words text-sm leading-6 text-slate-600">
                                            {contact.notes}
                                        </p>
                                    </div>
                                )}

                                <div className="mt-5 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => openEditFamilyContact(contact)}
                                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => handleDeleteFamilyContact(contact)}
                                        className="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="flex min-h-[240px] items-center justify-center p-6 text-center">
                        <div className="max-w-md">
                            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-2xl">
                                👪
                            </div>

                            <h4 className="mt-4 font-bold text-slate-800">
                                No family contacts yet
                            </h4>

                            <p className="mt-2 text-sm leading-6 text-slate-500">
                                Nurses or management can add the resident's family and emergency contact information here.
                            </p>

                            <button
                                type="button"
                                onClick={openNewFamilyContact}
                                className="mt-5 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                + Add First Contact
                            </button>
                        </div>
                    </div>
                )}
            </section>

            <section className="rounded-2xl border border-blue-100 bg-blue-50 p-5 sm:p-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                        💬
                    </div>

                    <div className="min-w-0">
                        <h3 className="font-bold text-slate-800">
                            Medication WhatsApp Notification
                        </h3>

                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Contacts marked with WhatsApp and Medication Updates enabled are prepared for the future automated notification workflow.
                        </p>

                        <div className="mt-4 rounded-xl bg-white p-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Message Example
                            </p>

                            <p className="mt-2 break-words text-sm font-semibold leading-6 text-slate-700">
                                {resident.name} has taken the AM medication and breakfast.
                            </p>
                        </div>

                        <p className="mt-3 text-xs leading-5 text-slate-500">
                            WhatsApp delivery itself is not enabled yet. This screen now stores the real recipient and notification preferences that the backend will use later.
                        </p>
                    </div>
                </div>
            </section>

        </div>
    )
}

        </div>

    );

}


function VitalBox({ label, value }) {

    return (

        <div className="
            rounded-xl
            bg-slate-50
            p-4
        ">

            <p className="
                text-xs
                font-medium
                uppercase
                tracking-wide
                text-slate-500
            ">
                {label}
            </p>

            <p className="
                mt-2
                text-lg
                font-bold
                text-slate-800
            ">
                {value}
            </p>

        </div>

    );

}


function VitalInput({
    label,
    name,
    value,
    onChange,
    unit,
    required = false,
}) {

    return (

        <div>

            <label className="
                mb-2
                block
                text-sm
                font-semibold
                text-slate-700
            ">
                {label}
                {required ? " *" : ""}
            </label>

            <div className="relative">

                <input
                    type="number"
                    step="0.01"
                    name={name}
                    value={value}
                    onChange={onChange}
                    className="
                        w-full
                        rounded-lg
                        border
                        border-slate-300
                        px-4
                        py-3
                        pr-20
                        outline-none
                        focus:border-blue-500
                    "
                />

                <span className="
                    absolute
                    right-4
                    top-1/2
                    -translate-y-1/2
                    text-sm
                    text-slate-400
                ">
                    {unit}
                </span>

            </div>

        </div>

    );

}


function InfoRow({ label, value }) {

    return (

        <div className="
            flex
            items-center
            justify-between
            gap-4
            py-4
        ">

            <span className="
                text-sm
                text-slate-500
            ">
                {label}
            </span>

            <span className="
                text-right
                font-semibold
                text-slate-700
            ">
                {value}
            </span>

        </div>

    );

}


function VitalTrendChart({
    title,
    subtitle,
    children,
}) {

    return (

        <div className="
            rounded-xl
            border
            border-slate-200
            bg-slate-50
            p-5
        ">

            <div className="mb-4">

                <h4 className="
                    font-bold
                    text-slate-800
                ">
                    {title}
                </h4>

                <p className="
                    mt-1
                    text-sm
                    text-slate-500
                ">
                    {subtitle}
                </p>

            </div>

            <div className="h-[280px] w-full">

                {children}

            </div>

        </div>

    );

}


function InsightCard({ title, value, detail }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-slate-500">{title}</p>
            <p className="mt-2 text-xl font-bold text-slate-800">{value}</p>
            <p className="mt-2 text-sm leading-6 text-slate-500">{detail}</p>
        </div>
    );
}

function AIActionRow({ priority, text }) {
    const critical = priority === "High";
    return (
        <div className={`rounded-lg border p-4 ${critical ? "border-amber-200 bg-amber-50" : "border-slate-200 bg-slate-50"}`}>
            <div className="flex items-start gap-3">
                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${critical ? "bg-amber-100 text-amber-700" : "bg-white text-slate-600"}`}>{priority}</span>
                <p className="text-sm leading-6 text-slate-700">{text}</p>
            </div>
        </div>
    );
}

function CareSummaryCard({ label, value, detail, tone = "blue" }) {
    const toneStyles = {
        blue: "bg-blue-50 text-blue-700",
        green: "bg-emerald-50 text-emerald-700",
        amber: "bg-amber-50 text-amber-700",
        red: "bg-rose-50 text-rose-700",
    };

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>

            <div className="mt-3 flex items-end justify-between gap-3">
                <p className="text-2xl font-bold text-slate-800 sm:text-3xl">
                    {value}
                </p>

                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${toneStyles[tone] || toneStyles.blue}`}>
                    {detail}
                </span>
            </div>
        </div>
    );
}


function DocumentSummaryCard({
    label,
    value,
    detail,
    tone = "blue",
}) {
    const toneStyles = {
        blue: "bg-blue-50 text-blue-700",
        green: "bg-emerald-50 text-emerald-700",
        amber: "bg-amber-50 text-amber-700",
        slate: "bg-slate-100 text-slate-600",
    };

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
            <div className="mt-3 flex items-end justify-between gap-3">
                <p className="text-2xl font-bold text-slate-800 sm:text-3xl">{value}</p>
                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${toneStyles[tone] || toneStyles.blue}`}>
                    {detail}
                </span>
            </div>
        </div>
    );
}


function FamilyTextInput({
    label,
    name,
    value,
    onChange,
    placeholder = "",
    required = false,
}) {
    return (
        <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
                {label}
                {required ? " *" : ""}
            </label>

            <input
                type="text"
                name={name}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            />
        </div>
    );
}


function FamilyToggle({
    label,
    description,
    name,
    checked,
    onChange,
}) {
    return (
        <label
            className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${
                checked
                    ? "border-blue-200 bg-blue-50"
                    : "border-slate-200 bg-slate-50"
            }`}
        >
            <input
                type="checkbox"
                name={name}
                checked={checked}
                onChange={onChange}
                className="mt-1 h-4 w-4 shrink-0"
            />

            <div className="min-w-0">
                <p className="font-semibold text-slate-800">
                    {label}
                </p>

                <p className="mt-1 text-sm leading-5 text-slate-500">
                    {description}
                </p>
            </div>
        </label>
    );
}


export default ResidentProfile;