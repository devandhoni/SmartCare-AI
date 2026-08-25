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

const [vitalHistory, setVitalHistory] = useState({});
const [vitalsLoading, setVitalsLoading] = useState(false);
const [vitalsError, setVitalsError] = useState("");
const [savingVitals, setSavingVitals] = useState(false);


const [careRecords, setCareRecords] = useState({
    1: {
        tasks: [
            { id: 1, label: "Morning hygiene", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:30 AM" },
            { id: 2, label: "Bath / shower", category: "Personal Care", completed: false, completedAt: null },
            { id: 3, label: "Oral care", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:45 AM" },
            { id: 4, label: "Mobility / assisted walk", category: "Mobility", completed: false, completedAt: null },
            { id: 5, label: "Hydration check", category: "Nutrition", completed: false, completedAt: null },
            { id: 6, label: "Room and comfort check", category: "Environment", completed: true, completedAt: "25 Aug 2026, 09:00 AM" },
        ],
        notes: [
            { id: 1, recordedAt: "25 Aug 2026, 09:05 AM", note: "Resident comfortable after morning care. No new concerns observed." },
        ],
    },
    2: {
        tasks: [
            { id: 1, label: "Morning hygiene", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:40 AM" },
            { id: 2, label: "Bath / shower", category: "Personal Care", completed: false, completedAt: null },
            { id: 3, label: "Oral care", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:50 AM" },
            { id: 4, label: "Mobility / assisted walk", category: "Mobility", completed: false, completedAt: null },
            { id: 5, label: "Hydration check", category: "Nutrition", completed: false, completedAt: null },
            { id: 6, label: "Room and comfort check", category: "Environment", completed: true, completedAt: "25 Aug 2026, 09:10 AM" },
        ],
        notes: [],
    },
    3: {
        tasks: [
            { id: 1, label: "Morning hygiene", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:35 AM" },
            { id: 2, label: "Bath / shower", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 08:00 AM" },
            { id: 3, label: "Oral care", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 08:05 AM" },
            { id: 4, label: "Mobility / assisted walk", category: "Mobility", completed: false, completedAt: null },
            { id: 5, label: "Hydration check", category: "Nutrition", completed: true, completedAt: "25 Aug 2026, 09:15 AM" },
            { id: 6, label: "Room and comfort check", category: "Environment", completed: true, completedAt: "25 Aug 2026, 09:20 AM" },
        ],
        notes: [],
    },
    4: {
        tasks: [
            { id: 1, label: "Morning hygiene", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:20 AM" },
            { id: 2, label: "Bath / shower", category: "Personal Care", completed: false, completedAt: null },
            { id: 3, label: "Oral care", category: "Personal Care", completed: true, completedAt: "25 Aug 2026, 07:35 AM" },
            { id: 4, label: "Mobility / assisted walk", category: "Mobility", completed: false, completedAt: null },
            { id: 5, label: "Hydration check", category: "Nutrition", completed: false, completedAt: null },
            { id: 6, label: "Room and comfort check", category: "Environment", completed: true, completedAt: "25 Aug 2026, 09:05 AM" },
        ],
        notes: [],
    },
});

const [careNote, setCareNote] = useState("");

const [documentRecords, setDocumentRecords] = useState({
    1: [
        { id: 1, name: "Admission Form", category: "Admission", updatedAt: "01 Aug 2026", status: "Completed" },
        { id: 2, name: "Consent & Admission", category: "Consent", updatedAt: "01 Aug 2026", status: "Completed" },
        { id: 3, name: "Weekly Vital Signs Check", category: "Clinical", updatedAt: "25 Aug 2026", status: "Active" },
        { id: 4, name: "Monthly Glucose Check", category: "Clinical", updatedAt: "25 Aug 2026", status: "Active" },
    ],
    2: [], 3: [], 4: [],
});

const [familyContacts, setFamilyContacts] = useState({
    1: [
        { id: 1, name: "Michael Victor", relationship: "Son", phone: "012-0000000", primary: true, whatsappEnabled: true },
        { id: 2, name: "Sarah Victor", relationship: "Daughter", phone: "012-0000001", primary: false, whatsappEnabled: false },
    ],
    2: [{ id: 1, name: "Family Contact", relationship: "Daughter", phone: "012-0000000", primary: true, whatsappEnabled: true }],
    3: [{ id: 1, name: "Family Contact", relationship: "Brother", phone: "012-0000000", primary: true, whatsappEnabled: true }],
    4: [{ id: 1, name: "Family Contact", relationship: "Daughter", phone: "012-0000000", primary: true, whatsappEnabled: true }],
});

const [showFamilyForm, setShowFamilyForm] = useState(false);
const [familyForm, setFamilyForm] = useState({ name: "", relationship: "", phone: "", whatsappEnabled: true });

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
            residentApi.emergency_contact ||
            "No contact recorded",

        relationship:
            residentApi.emergency_relationship ||
            "-",

        phone:
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
const residentCare = careRecords[id] || { tasks: [], notes: [] };
const completedCareTasks = residentCare.tasks.filter((task) => task.completed).length;
const careProgress = residentCare.tasks.length
    ? Math.round((completedCareTasks / residentCare.tasks.length) * 100)
    : 0;

const handleCareTaskToggle = (taskId) => {
    setCareRecords((current) => ({
        ...current,
        [id]: {
            ...(current[id] || { tasks: [], notes: [] }),
            tasks: (current[id]?.tasks || []).map((task) =>
                task.id === taskId
                    ? {
                        ...task,
                        completed: !task.completed,
                        completedAt: !task.completed ? new Date().toLocaleString() : null,
                    }
                    : task
            ),
        },
    }));
};

const handleSaveCareNote = (event) => {
    event.preventDefault();

    const note = careNote.trim();
    if (!note) return;

    setCareRecords((current) => ({
        ...current,
        [id]: {
            ...(current[id] || { tasks: [], notes: [] }),
            tasks: current[id]?.tasks || [],
            notes: [
                {
                    id: Date.now(),
                    recordedAt: new Date().toLocaleString(),
                    note,
                },
                ...(current[id]?.notes || []),
            ],
        },
    }));

    setCareNote("");
};

const residentDocuments = documentRecords[id] || [];
const residentFamilyContacts = familyContacts[id] || [];

const handleDocumentUpload = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setDocumentRecords((current) => ({
        ...current,
        [id]: [
            {
                id: Date.now(),
                name: file.name,
                category: "Uploaded Document",
                updatedAt: new Date().toLocaleDateString(),
                status: "Uploaded",
            },
            ...(current[id] || []),
        ],
    }));

    event.target.value = "";
};

const handleAddFamilyContact = (event) => {
    event.preventDefault();
    if (!familyForm.name.trim() || !familyForm.relationship.trim() || !familyForm.phone.trim()) return;

    setFamilyContacts((current) => ({
        ...current,
        [id]: [
            ...(current[id] || []),
            {
                id: Date.now(),
                name: familyForm.name.trim(),
                relationship: familyForm.relationship.trim(),
                phone: familyForm.phone.trim(),
                primary: (current[id] || []).length === 0,
                whatsappEnabled: familyForm.whatsappEnabled,
            },
        ],
    }));

    setFamilyForm({ name: "", relationship: "", phone: "", whatsappEnabled: true });
    setShowFamilyForm(false);
};

const timelineEntries = [
    ...residentVitalHistory.slice(0, 4).map((record) => ({
        id: `vital-${record.id}`,
        type: "Vitals",
        title: `Vital signs recorded: BP ${record.bloodPressure}, SpO₂ ${record.spo2}%`,
        time: record.recordedAt,
        detail: record.notes || "Routine vital signs observation.",
    })),
    ...medicationHistory.slice(0, 6).map((entry, index) => ({
        id: `medication-history-${index}`,
        type: "Medication",
        title: `${entry.medicine || "Medication"} administered`,
        time: formatMedicationDateTime(entry.completed_time),
        detail: `${entry.time_slot || "Medication"} round${
            entry.completed_by ? ` • Recorded by ${entry.completed_by}` : ""
        }`,
    })),
    ...residentCare.notes.slice(0, 4).map((entry) => ({
        id: `care-${entry.id}`,
        type: "Care",
        title: "Care note recorded",
        time: entry.recordedAt,
        detail: entry.note,
    })),
    {
        id: "admission",
        type: "Admission",
        title: `${resident.name} admitted to SmartCare`,
        time: resident.admissionDate,
        detail: `${resident.room} • ${resident.primaryDiagnosis}`,
    },
];



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

        <div className="space-y-6">

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800">Daily Care</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Complete routine care tasks and record short nursing observations for {resident.name}.
                        </p>
                    </div>

                    <div className="min-w-[220px] rounded-xl bg-slate-50 p-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-semibold text-slate-700">Today's progress</span>
                            <span className="font-bold text-blue-700">{careProgress}%</span>
                        </div>
                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                            <div
                                className="h-full rounded-full bg-blue-600 transition-all"
                                style={{ width: `${careProgress}%` }}
                            />
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            {completedCareTasks} of {residentCare.tasks.length} routine care tasks completed
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 p-6">
                        <h3 className="text-lg font-bold text-slate-800">Today's Care Checklist</h3>
                        <p className="mt-1 text-sm text-slate-500">
                            Tick an item only after the care activity has been completed.
                        </p>
                    </div>

                    <div className="divide-y divide-slate-100">
                        {residentCare.tasks.map((task) => (
                            <label
                                key={task.id}
                                className={`flex cursor-pointer items-start gap-4 p-5 transition ${
                                    task.completed ? "bg-emerald-50/60" : "hover:bg-slate-50"
                                }`}
                            >
                                <input
                                    type="checkbox"
                                    checked={task.completed}
                                    onChange={() => handleCareTaskToggle(task.id)}
                                    className="mt-1 h-5 w-5"
                                />

                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="font-semibold text-slate-800">{task.label}</p>
                                            <p className="mt-1 text-sm text-slate-500">{task.category}</p>
                                        </div>

                                        <span
                                            className={`w-fit rounded-full px-3 py-1 text-xs font-semibold ${
                                                task.completed
                                                    ? "bg-emerald-100 text-emerald-700"
                                                    : "bg-amber-50 text-amber-700"
                                            }`}
                                        >
                                            {task.completed ? "Completed" : "Pending"}
                                        </span>
                                    </div>

                                    {task.completedAt && (
                                        <p className="mt-2 text-xs text-emerald-700">
                                            Completed • {task.completedAt}
                                        </p>
                                    )}
                                </div>
                            </label>
                        ))}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800">Add Care Note</h3>
                        <p className="mt-1 text-sm text-slate-500">
                            Record only important observations or changes noticed during care.
                        </p>

                        <form onSubmit={handleSaveCareNote} className="mt-5">
                            <textarea
                                rows="5"
                                value={careNote}
                                onChange={(event) => setCareNote(event.target.value)}
                                placeholder="Example: Resident needed assistance while walking to the dining area..."
                                className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500"
                            />

                            <button
                                type="submit"
                                disabled={!careNote.trim()}
                                className={`mt-3 w-full rounded-lg px-5 py-3 font-semibold ${
                                    careNote.trim()
                                        ? "bg-blue-600 text-white hover:bg-blue-700"
                                        : "cursor-not-allowed bg-slate-100 text-slate-400"
                                }`}
                            >
                                Save Care Note
                            </button>
                        </form>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800">Current Care Status</h3>
                        <div className="mt-4 rounded-lg bg-emerald-50 p-4">
                            <p className="text-sm font-semibold text-emerald-800">{resident.careStatus}</p>
                            <p className="mt-1 text-sm leading-6 text-emerald-700">
                                Continue routine care and escalate any meaningful change to the nurse in charge.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-6">
                    <h3 className="text-lg font-bold text-slate-800">Care Notes History</h3>
                    <p className="mt-1 text-sm text-slate-500">
                        Recent nursing and daily-care observations for this resident.
                    </p>
                </div>

                {residentCare.notes.length > 0 ? (
                    <div className="divide-y divide-slate-100">
                        {residentCare.notes.map((entry) => (
                            <div key={entry.id} className="p-5">
                                <p className="text-sm text-slate-700">{entry.note}</p>
                                <p className="mt-2 text-xs text-slate-400">{entry.recordedAt}</p>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="p-8 text-center text-sm text-slate-500">
                        No care notes have been recorded yet.
                    </div>
                )}
            </div>

            <div className="rounded-xl border border-blue-100 bg-blue-50 p-5">
                <p className="text-sm leading-6 text-slate-600">
                    This is currently frontend test data. When we connect the Laravel API, each completed care task and note will be stored against the resident together with the nurse and timestamp.
                </p>
            </div>

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
                <InsightCard title="Care Progress" value={`${careProgress}% complete`} detail={`${completedCareTasks} of ${residentCare.tasks.length} daily care tasks completed`} />
                <InsightCard title="Medication" value={`${completedMedicationRounds}/3 rounds`} detail="Medication rounds require nurse confirmation before completion." />
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-bold text-slate-800">What Needs Attention</h3>
                    <div className="mt-5 space-y-3">
                        <AIActionRow priority={resident.careStatus === "Needs Review" ? "High" : "Routine"} text={resident.careStatus === "Needs Review" ? "Review the latest observations and repeat abnormal vital signs if clinically appropriate." : "Continue routine monitoring and scheduled care."} />
                        <AIActionRow priority="Routine" text={`${3 - completedMedicationRounds} medication round(s) remain pending today.`} />
                        <AIActionRow priority="Routine" text={`${residentCare.tasks.filter((task) => !task.completed).length} daily care task(s) remain pending.`} />
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
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-bold text-slate-800">Resident Timeline</h2>
                <p className="mt-1 text-sm text-slate-500">A simple chronological view of important care, vital, medication and admission events.</p>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="space-y-0">
                    {timelineEntries.map((entry, index) => (
                        <div key={entry.id} className="relative flex gap-4 pb-7 last:pb-0">
                            {index !== timelineEntries.length - 1 && <div className="absolute left-[11px] top-7 h-[calc(100%-8px)] w-px bg-slate-200" />}
                            <div className="mt-1 h-6 w-6 shrink-0 rounded-full border-4 border-white bg-blue-500 ring-1 ring-slate-200" />
                            <div className="min-w-0 flex-1 rounded-xl bg-slate-50 p-4">
                                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex items-center gap-2">
                                        <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">{entry.type}</span>
                                        <p className="font-semibold text-slate-800">{entry.title}</p>
                                    </div>
                                    <span className="text-xs text-slate-400">{entry.time}</span>
                                </div>
                                <p className="mt-2 text-sm leading-6 text-slate-600">{entry.detail}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    )
}

{/* DOCUMENTS TAB */}
{
    activeTab === "documents" && (
        <div className="space-y-6">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800">Documents & Forms</h2>
                        <p className="mt-1 text-sm text-slate-500">Resident forms and supporting records are kept together here.</p>
                    </div>
                    <label className="cursor-pointer rounded-lg bg-blue-600 px-5 py-3 text-center font-semibold text-white hover:bg-blue-700">
                        + Upload Document
                        <input type="file" className="hidden" onChange={handleDocumentUpload} />
                    </label>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {["Admission & Consent", "Clinical Monitoring", "Leave / Discharge", "Other Records"].map((label) => (
                    <div key={label} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-medium text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-800">{label === "Clinical Monitoring" ? residentDocuments.filter((d) => d.category === "Clinical").length : residentDocuments.filter((d) => d.category !== "Clinical").length}</p>
                    </div>
                ))}
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-6"><h3 className="text-lg font-bold text-slate-800">Resident Records</h3></div>
                {residentDocuments.length ? (
                    <div className="divide-y divide-slate-100">
                        {residentDocuments.map((doc) => (
                            <div key={doc.id} className="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="font-semibold text-slate-800">{doc.name}</p>
                                    <p className="mt-1 text-sm text-slate-500">{doc.category} • Updated {doc.updatedAt}</p>
                                </div>
                                <span className="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{doc.status}</span>
                            </div>
                        ))}
                    </div>
                ) : <div className="p-8 text-center text-sm text-slate-500">No documents have been added yet.</div>}
            </div>

            <div className="rounded-xl border border-blue-100 bg-blue-50 p-5 text-sm leading-6 text-slate-600">
                The paper admission, consent, discharge, glucose, weekly vital-sign, home-leave and other forms will be converted into structured digital workflows. File upload here is currently a frontend demonstration only.
            </div>
        </div>
    )
}

{/* FAMILY TAB */}
{
    activeTab === "family" && (
        <div className="space-y-6">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800">Family & Contacts</h2>
                        <p className="mt-1 text-sm text-slate-500">Management or nurses can maintain family contacts and WhatsApp notification preferences.</p>
                    </div>
                    <button type="button" onClick={() => setShowFamilyForm((v) => !v)} className="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">+ Add Family Contact</button>
                </div>
            </div>

            {showFamilyForm && (
                <form onSubmit={handleAddFamilyContact} className="rounded-xl border border-blue-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-bold text-slate-800">New Family Contact</h3>
                    <div className="mt-5 grid gap-4 md:grid-cols-3">
                        <SimpleInput label="Full Name" value={familyForm.name} onChange={(value) => setFamilyForm((c) => ({...c, name:value}))} />
                        <SimpleInput label="Relationship" value={familyForm.relationship} onChange={(value) => setFamilyForm((c) => ({...c, relationship:value}))} />
                        <SimpleInput label="Phone Number" value={familyForm.phone} onChange={(value) => setFamilyForm((c) => ({...c, phone:value}))} />
                    </div>
                    <label className="mt-4 flex items-center gap-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                        <input type="checkbox" checked={familyForm.whatsappEnabled} onChange={(e) => setFamilyForm((c) => ({...c, whatsappEnabled:e.target.checked}))} />
                        Receive WhatsApp care and medication notifications
                    </label>
                    <div className="mt-5 flex justify-end gap-3">
                        <button type="button" onClick={() => setShowFamilyForm(false)} className="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700">Cancel</button>
                        <button type="submit" className="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white">Save Contact</button>
                    </div>
                </form>
            )}

            <div className="grid gap-5 lg:grid-cols-2">
                {residentFamilyContacts.map((contact) => (
                    <div key={contact.id} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h3 className="text-lg font-bold text-slate-800">{contact.name}</h3>
                                    {contact.primary && <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Primary</span>}
                                </div>
                                <p className="mt-1 text-sm text-slate-500">{contact.relationship}</p>
                            </div>
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${contact.whatsappEnabled ? "bg-emerald-50 text-emerald-700" : "bg-slate-100 text-slate-500"}`}>{contact.whatsappEnabled ? "WhatsApp On" : "WhatsApp Off"}</span>
                        </div>
                        <div className="mt-5 divide-y divide-slate-100">
                            <InfoRow label="Phone" value={contact.phone} />
                            <InfoRow label="Medication notifications" value={contact.whatsappEnabled ? "Enabled" : "Disabled"} />
                        </div>
                    </div>
                ))}
            </div>

            <div className="rounded-xl border border-blue-100 bg-blue-50 p-6">
                <h3 className="font-bold text-slate-800">WhatsApp Message Example</h3>
                <p className="mt-2 text-sm leading-6 text-slate-600">{resident.name} has taken the AM medication and breakfast.</p>
                <p className="mt-3 text-xs text-slate-500">Real WhatsApp delivery will be triggered by the backend after a nurse completes the medication and meal round. Phone numbers are maintained here, not entered during each medication round.</p>
            </div>
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

function SimpleInput({ label, value, onChange }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">{label}</label>
            <input value={value} onChange={(event) => onChange(event.target.value)} className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500" />
        </div>
    );
}


export default ResidentProfile;