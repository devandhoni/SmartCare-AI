import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from "react-native";
import { type NativeStackScreenProps } from "@react-navigation/native-stack";
import api from "../../api/api";
import { admissionError, deleteAdmissionDraft, getAdmissionDraft, getAdmissionMedicines, getAdmissionRooms, saveAdmissionDraft, type Medicine, type Room } from "../../api/admissionApi";
import type { AppStackParamList } from "../../navigation/AppNavigator";

type Props = NativeStackScreenProps<AppStackParamList, "AdmissionWizard">;
type Row = Record<string, string>;
type Form = Record<string, string | boolean | Row[]> & { hospitalizations: Row[]; medications: Row[] };
const hospitalRow = (): Row => ({ hospital_name: "", hospitalization_date: "", reason: "", notes: "" });
const medicationRow = (): Row => ({ medication_id: "", medicine_search: "", dosage_instruction: "", dosage_quantity: "1", frequency: "", time_slot: "", scheduled_time: "", start_date: "", end_date: "", prescribed_by: "" });
const consentFields = ["admission_consent", "care_consent", "medication_consent", "emergency_treatment_consent", "information_sharing_consent", "family_notification_consent", "terms_acknowledged"] as const;
const agreementFields = ["medical_care_terms_acknowledged", "payment_fee_terms_acknowledged", "resident_conduct_terms_acknowledged", "belongings_terms_acknowledged", "termination_terms_acknowledged", "emergency_liability_terms_acknowledged", "risk_liability_terms_acknowledged", "death_event_terms_acknowledged"] as const;
const agreementLabels = ["Medical & Care Terms", "Payment & Fee Terms", "Resident Conduct Terms", "Belongings Terms", "Termination Terms", "Emergency & Liability Terms", "Risk & Liability Terms", "Death Event Terms"];
const titles = ["Resident", "Family", "Medical History", "Hospital & Medication", "Care Assessment", "Consent", "Review"];
const emptyForm = (): Form => ({
  full_name: "", ic_number: "", date_of_birth: "", gender: "", nationality: "", address: "", phone: "", email: "", blood_type: "", room_id: "",
  contact_full_name: "", contact_relationship: "", contact_phone: "", contact_whatsapp: "", contact_notes: "", is_primary: true, is_emergency_contact: true, whatsapp_enabled: true, medication_notifications_enabled: true, care_notifications_enabled: true,
  medical_condition: "", allergies: "", chronic_disease: "", medical_notes: "", primary_psychiatric_diagnosis: "", other_diagnoses: "", tobacco_use: false, tobacco_amount_frequency: "", alcohol_use: false, alcohol_amount_frequency: "", drug_use: false, drug_type_frequency: "", family_psychiatric_history: false, family_psychiatric_history_details: "", family_substance_abuse_history: false, family_substance_abuse_history_details: "", initial_observations: "", additional_comments: "",
  hospitalizations: [hospitalRow()], medications: [medicationRow()], admitted_at: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16), admission_type: "NEW_ADMISSION", admission_source: "", reason_for_admission: "", medical_summary: "", mobility_notes: "", dietary_notes: "", special_care_instructions: "", belongings_notes: "",
  consent_given_by: "", consent_relationship: "", consent_contact_number: "", admission_consent: false, care_consent: false, medication_consent: false, emergency_treatment_consent: false, information_sharing_consent: false, family_notification_consent: false, terms_acknowledged: false, consent_notes: "", agreement_version: "1.0", agreement_title: "Consent / Admission Agreement", monthly_fee: "", medical_care_terms_acknowledged: false, payment_fee_terms_acknowledged: false, resident_conduct_terms_acknowledged: false, belongings_terms_acknowledged: false, termination_terms_acknowledged: false, emergency_liability_terms_acknowledged: false, risk_liability_terms_acknowledged: false, death_event_terms_acknowledged: false,
});
const str = (value: unknown): string => typeof value === "string" ? value : "";
const nil = (value: unknown): string | null => str(value).trim() || null;
const hasRow = (r: Row) => Object.entries(r).some(([key, val]) => key !== "dosage_quantity" && key !== "medicine_search" && val.trim() !== "");
const hasHospital = (r: Row) => Object.values(r).some((v) => v.trim() !== "");
const medicineLabel = (m: Medicine) => [m.name || m.medication_name || m.generic_name || m.brand_name || `Medicine #${m.id}`, m.strength].filter(Boolean).join(" · ");

export default function AdmissionWizardScreen({ route, navigation }: Props) {
  const requestedDraftId = route.params?.draftId;
  const [form, setForm] = useState<Form>(emptyForm);
  const [step, setStep] = useState(1);
  const [draftId, setDraftId] = useState<number | null>(requestedDraftId ?? null);
  const [draftReference, setDraftReference] = useState("");
  const [rooms, setRooms] = useState<Room[]>([]);
  const [medicines, setMedicines] = useState<Medicine[]>([]);
  const [loading, setLoading] = useState(Boolean(requestedDraftId));
  const [saving, setSaving] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submissionBlocked, setSubmissionBlocked] = useState(false);
  const [error, setError] = useState("");
  const [medicineSearchIndex, setMedicineSearchIndex] = useState<number | null>(null);

  useEffect(() => {
    let active = true;
    const load = async () => {
      const results = await Promise.allSettled([getAdmissionRooms(), getAdmissionMedicines(), requestedDraftId ? getAdmissionDraft(requestedDraftId) : Promise.resolve(null)]);
      if (!active) return;
      if (results[0].status === "fulfilled") setRooms(results[0].value);
      if (results[1].status === "fulfilled") setMedicines(results[1].value);
      if (results[2].status === "fulfilled" && results[2].value) {
        const draft = results[2].value;
        const stored = draft.form_data || {};
        const defaults = emptyForm();
        setForm({ ...defaults, ...(stored as unknown as Partial<Form>), hospitalizations: Array.isArray(stored.hospitalizations) ? stored.hospitalizations as Row[] : defaults.hospitalizations, medications: Array.isArray(stored.medications) ? stored.medications as Row[] : defaults.medications });
        setStep(Math.max(1, Math.min(7, Number(draft.current_step) || 1)));
        setDraftReference(draft.draft_reference || "");
      } else if (requestedDraftId) {
        setError("Could not load this draft. Please return to Admissions and retry.");
      }
      setLoading(false);
    };
    void load();
    return () => { active = false; };
  }, [requestedDraftId]);

  const set = (key: string, value: string | boolean) => { setForm((prev) => ({ ...prev, [key]: value })); setError(""); };
  const updateRow = (kind: "hospitalizations" | "medications", index: number, key: string, value: string) => {
    setForm((prev) => ({ ...prev, [kind]: prev[kind].map((r, i) => i === index ? { ...r, [key]: value } : r) }));
    setError("");
  };
  const addRow = (kind: "hospitalizations" | "medications") => setForm((prev) => ({ ...prev, [kind]: [...prev[kind], kind === "hospitalizations" ? hospitalRow() : medicationRow()] }));
  const removeRow = (kind: "hospitalizations" | "medications", index: number) => setForm((prev) => ({ ...prev, [kind]: prev[kind].length === 1 ? [kind === "hospitalizations" ? hospitalRow() : medicationRow()] : prev[kind].filter((_, i) => i !== index) }));

  const field = (label: string, key: string, hint = "", multiline = false, keyboardType: "default" | "email-address" | "phone-pad" | "numeric" = "default") => (
    <View style={s.field} key={key}><Text style={s.label}>{label}</Text><TextInput style={[s.input, multiline && s.multi]} placeholder={hint} placeholderTextColor="#94a3b8" value={str(form[key])} onChangeText={(v) => set(key, v)} multiline={multiline} keyboardType={keyboardType} autoCapitalize={keyboardType === "email-address" ? "none" : "sentences"} /></View>
  );
  const choice = (label: string, key: string, values: { value: string; label: string }[]) => (
    <View style={s.field} key={key}><Text style={s.label}>{label}</Text><View style={s.wrap}>{values.map((v) => <TouchableOpacity key={v.value} onPress={() => set(key, form[key] === v.value ? "" : v.value)} style={[s.chip, form[key] === v.value && s.chipOn]}><Text style={[s.chipText, form[key] === v.value && s.chipTextOn]}>{v.label}</Text></TouchableOpacity>)}</View></View>
  );
  const toggle = (label: string, key: string) => (
    <View key={key} style={s.toggle}><Text style={s.toggleLabel}>{label}</Text><Switch value={form[key] === true} onValueChange={(v) => set(key, v)} trackColor={{ true: "#93c5fd" }} thumbColor={form[key] === true ? "#2563eb" : "#f1f5f9"} /></View>
  );
  const rowField = (kind: "hospitalizations" | "medications", index: number, label: string, key: string, hint = "") => (
    <View style={s.field} key={key}><Text style={s.label}>{label}</Text><TextInput style={s.input} placeholder={hint} value={form[kind][index]?.[key] || ""} onChangeText={(v) => updateRow(kind, index, key, v)} /></View>
  );
  const rowChoices = (index: number, key: string, values: string[]) => (
    <View style={s.field}><Text style={s.label}>Round / Time Slot</Text><View style={s.wrap}>{values.map((v) => <TouchableOpacity key={v} style={[s.chip, form.medications[index]?.[key] === v && s.chipOn]} onPress={() => updateRow("medications", index, key, v)}><Text style={[s.chipText, form.medications[index]?.[key] === v && s.chipTextOn]}>{v}</Text></TouchableOpacity>)}</View></View>
  );
  const validate = useCallback((n: number): string => {
    if (n === 1 && !str(form.full_name).trim()) return "Resident full name is required.";
    if (n === 2) {
      if (!str(form.contact_full_name).trim()) return "Primary family/contact name is required.";
      if (!str(form.contact_relationship).trim()) return "Contact relationship is required.";
      if (!str(form.contact_phone).trim()) return "Primary contact phone is required.";
    }
    if (n === 4) for (const med of form.medications.filter(hasRow)) {
      if (!med.medication_id) return "Select a medicine for each medication row.";
      if (!med.dosage_instruction?.trim()) return "Dosage instruction is required.";
      if (!med.frequency?.trim()) return "Medication frequency is required.";
      if (!med.time_slot) return "Select AM, PM, NIGHT or OTHER.";
      if (!Number.isInteger(Number(med.dosage_quantity)) || Number(med.dosage_quantity) < 1) return "Dosage quantity must be a whole number of at least 1.";
    }
    if (n === 5 && !str(form.admitted_at)) return "Admission date and time is required.";
    if (n === 6) {
      if (!str(form.consent_given_by).trim()) return "Consent provider name is required.";
      const required = ["admission_consent", "care_consent", "medication_consent", "emergency_treatment_consent", "terms_acknowledged", ...agreementFields];
      if (!required.every((key) => form[key] === true)) return "All required consent and eight agreement acknowledgements must be accepted.";
    }
    return "";
  }, [form]);
  const next = () => { const message = validate(step); if (message) { setError(message); return; } setError(""); setStep((n) => Math.min(7, n + 1)); };
  const save = async () => {
    if (saving || submitting || submissionBlocked) return;
    setSaving(true); setError("");
    try {
      const draft = await saveAdmissionDraft({ full_name: nil(form.full_name), current_step: step, form_data: form }, draftId);
      setDraftId(draft.id); setDraftReference(draft.draft_reference || "");
      Alert.alert("Draft Saved", `${draft.draft_reference || `#${draft.id}`} is available on mobile and web.`);
    } catch (e) { setError(admissionError(e)); } finally { setSaving(false); }
  };
  const finish = () => Alert.alert("Complete Admission", "This creates the resident, contact, admission, medication assignments and consent. Confirm that the details are correct. Do not submit twice.", [ { text: "Cancel", style: "cancel" }, { text: "Complete Admission", onPress: () => void finalize() } ]);
  const finalize = async () => {
    if (submitting || submissionBlocked) return;
    for (let n = 1; n <= 6; n++) { const message = validate(n); if (message) { setStep(n); setError(message); return; } }
    setSubmitting(true); setError("");
    let residentId: number | null = null;
    let admissionId: number | null = null;
    try {
      const r = await api.post("/residents", {
        full_name: str(form.full_name).trim(), ic_number: nil(form.ic_number), date_of_birth: nil(form.date_of_birth), gender: nil(form.gender), nationality: nil(form.nationality), address: nil(form.address), phone: nil(form.phone), email: nil(form.email), room_id: nil(form.room_id), emergency_contact: str(form.contact_full_name).trim(), emergency_relationship: str(form.contact_relationship).trim(), emergency_phone: str(form.contact_phone).trim(), blood_type: nil(form.blood_type), medical_condition: nil(form.medical_condition), allergies: nil(form.allergies), chronic_disease: nil(form.chronic_disease), medical_notes: nil(form.medical_notes), admission_date: str(form.admitted_at).slice(0, 10), status: "Active",
      });
      residentId = Number(r.data?.resident?.id) || null;
      if (!residentId) throw new Error("Resident was created but no ID was returned.");
      await api.post(`/residents/${residentId}/contacts`, {
        full_name: str(form.contact_full_name).trim(), relationship: str(form.contact_relationship).trim(), phone: str(form.contact_phone).trim(), whatsapp_number: nil(form.contact_whatsapp) || str(form.contact_phone).trim(), is_primary: form.is_primary, is_emergency_contact: form.is_emergency_contact, whatsapp_enabled: form.whatsapp_enabled, medication_notifications_enabled: form.medication_notifications_enabled, care_notifications_enabled: form.care_notifications_enabled, notes: nil(form.contact_notes),
      });
      const a = await api.post("/admissions", {
        resident_id: residentId, admitted_at: form.admitted_at, admission_type: form.admission_type, admission_source: nil(form.admission_source), reason_for_admission: nil(form.reason_for_admission), medical_summary: nil(form.medical_summary) || nil(form.medical_condition), mobility_notes: nil(form.mobility_notes), dietary_notes: nil(form.dietary_notes), special_care_instructions: nil(form.special_care_instructions), belongings_notes: nil(form.belongings_notes), room_id: nil(form.room_id),
        medical_history: { primary_psychiatric_diagnosis: nil(form.primary_psychiatric_diagnosis), other_diagnoses: nil(form.other_diagnoses), tobacco_use: form.tobacco_use, tobacco_amount_frequency: nil(form.tobacco_amount_frequency), alcohol_use: form.alcohol_use, alcohol_amount_frequency: nil(form.alcohol_amount_frequency), drug_use: form.drug_use, drug_type_frequency: nil(form.drug_type_frequency), family_psychiatric_history: form.family_psychiatric_history, family_psychiatric_history_details: nil(form.family_psychiatric_history_details), family_substance_abuse_history: form.family_substance_abuse_history, family_substance_abuse_history_details: nil(form.family_substance_abuse_history_details), initial_observations: nil(form.initial_observations), additional_comments: nil(form.additional_comments) },
        hospitalizations: form.hospitalizations.filter(hasHospital).map((h) => ({ hospital_name: nil(h.hospital_name), hospitalization_date: nil(h.hospitalization_date), reason: nil(h.reason), notes: nil(h.notes) })),
      });
      admissionId = Number(a.data?.admission?.id) || null;
      if (!admissionId) throw new Error("Admission was created but no ID was returned.");
      for (const m of form.medications.filter(hasRow)) await api.post(`/residents/${residentId}/medications`, { medication_id: Number(m.medication_id), dosage_instruction: nil(m.dosage_instruction), dosage_quantity: Number(m.dosage_quantity), frequency: m.frequency.trim(), time_slot: m.time_slot, scheduled_time: nil(m.scheduled_time), start_date: nil(m.start_date), end_date: nil(m.end_date), prescribed_by: nil(m.prescribed_by) });
      await api.post(`/admissions/${admissionId}/consent`, {
        consent_given_by: str(form.consent_given_by).trim(), relationship: nil(form.consent_relationship), contact_number: nil(form.consent_contact_number), ...Object.fromEntries(consentFields.map((key) => [key, form[key] === true])), consent_notes: nil(form.consent_notes), agreement_version: nil(form.agreement_version), agreement_title: nil(form.agreement_title), monthly_fee: str(form.monthly_fee) === "" ? null : Number(form.monthly_fee), ...Object.fromEntries(agreementFields.map((key) => [key, form[key] === true])),
      });
      await api.post(`/admissions/${admissionId}/complete`);
      if (draftId) { try { await deleteAdmissionDraft(draftId); } catch { /* Completed record takes precedence over draft cleanup. */ } }
      Alert.alert("Admission Completed", `${str(form.full_name).trim()} has been admitted successfully.`, [{ text: "OK", onPress: () => navigation.goBack() }]);
      setSubmissionBlocked(true);
    } catch (e) {
      setSubmissionBlocked(true);
      const refs = residentId ? ` Resident #${residentId}${admissionId ? ` and Admission #${admissionId}` : ""} may already exist.` : " The request may have reached the server.";
      setError(`${admissionError(e)}${refs} Do not submit again until a supervisor checks Admissions and Residents. Return to Admissions to review existing records.`);
      Alert.alert("Admission Needs Review", "Submission stopped. Please check existing resident and admission records before attempting another admission.");
    } finally { setSubmitting(false); }
  };

  const review = (label: string, values: [string, string][], editStep: number) => <View style={s.card} key={label}><TouchableOpacity onPress={() => setStep(editStep)}><Text style={s.cardTitle}>{label}  ·  Edit</Text></TouchableOpacity>{values.map(([key, value]) => <Text key={key} style={s.reviewText}>{key}: {value || "—"}</Text>)}</View>;
  if (loading) return <View style={s.center}><ActivityIndicator size="large" color="#2563eb" /><Text>Loading admission draft...</Text></View>;
  return <ScrollView style={s.container} contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
    <Text style={s.heading}>{draftId ? "Admission Draft" : "New Admission"}</Text>
    <Text style={s.subtitle}>Step {step} of 7 · {titles[step - 1]}</Text>
    <View style={s.progress}><View style={[s.progressFill, { width: `${step / 7 * 100}%` }]} /></View>
    {draftReference ? <Text style={s.draft}>Draft: {draftReference}</Text> : null}
    {error ? <Text style={s.error}>{error}</Text> : null}
    {submissionBlocked ? <Text style={s.warning}>Final submission is locked to prevent duplicates. Return to Admissions and review records.</Text> : null}
    {step === 1 && <View style={s.card}><Text style={s.cardTitle}>Resident Details</Text>
      {field("Full Name *", "full_name", "Resident full name")}{field("IC / Passport Number", "ic_number")}{field("Date of Birth", "date_of_birth", "YYYY-MM-DD")}
      {choice("Gender", "gender", ["Male", "Female", "Other"].map((v) => ({ value: v, label: v })))}
      {choice("Blood Type", "blood_type", ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"].map((v) => ({ value: v, label: v })))}
      {field("Nationality", "nationality")}{field("Resident Phone", "phone", "+60...", false, "phone-pad")}{field("Resident Email", "email", "Optional", false, "email-address")}{field("Home Address", "address", "", true)}
      {choice("Room", "room_id", rooms.map((r) => ({ value: String(r.id), label: `Room ${r.room_number}${r.floor ? ` · ${r.floor}` : ""}` })))}
      {rooms.length === 0 && <Text style={s.hint}>No rooms loaded; room is optional.</Text>}
    </View>}
    {step === 2 && <View style={s.card}><Text style={s.cardTitle}>Family & Emergency Contact</Text>
      {field("Contact Full Name *", "contact_full_name")}{field("Relationship *", "contact_relationship")}{field("Phone Number *", "contact_phone", "+60...", false, "phone-pad")}{field("WhatsApp Number", "contact_whatsapp", "Blank uses phone", false, "phone-pad")}{field("Contact Notes", "contact_notes", "", true)}
      {toggle("Primary Contact", "is_primary")}{toggle("Emergency Contact", "is_emergency_contact")}{toggle("WhatsApp Enabled", "whatsapp_enabled")}{toggle("Medication Updates", "medication_notifications_enabled")}{toggle("Care Updates", "care_notifications_enabled")}
    </View>}
    {step === 3 && <View style={s.card}><Text style={s.cardTitle}>Medical History</Text>
      {field("Medical Condition", "medical_condition", "", true)}{field("Allergies", "allergies", "", true)}{field("Chronic Disease", "chronic_disease", "", true)}{field("Medical Notes", "medical_notes", "", true)}
      {field("Primary Psychiatric Diagnosis", "primary_psychiatric_diagnosis", "", true)}{field("Other Diagnoses", "other_diagnoses", "", true)}
      {toggle("Tobacco Use", "tobacco_use")}{form.tobacco_use && field("Tobacco Amount / Frequency", "tobacco_amount_frequency")}
      {toggle("Alcohol Use", "alcohol_use")}{form.alcohol_use && field("Alcohol Amount / Frequency", "alcohol_amount_frequency")}
      {toggle("Drug Use", "drug_use")}{form.drug_use && field("Drug Type / Frequency", "drug_type_frequency")}
      {toggle("Family Psychiatric History", "family_psychiatric_history")}{form.family_psychiatric_history && field("Psychiatric Family History Details", "family_psychiatric_history_details", "", true)}
      {toggle("Family Substance Abuse History", "family_substance_abuse_history")}{form.family_substance_abuse_history && field("Substance Abuse Family History Details", "family_substance_abuse_history_details", "", true)}
    </View>}
    {step === 4 && <><View style={s.card}><Text style={s.cardTitle}>Previous Hospitalisations</Text>
      {form.hospitalizations.map((h, i) => <View key={i} style={s.rowCard}><Text style={s.cardTitle}>Hospitalisation {i + 1}</Text>{rowField("hospitalizations", i, "Hospital Name", "hospital_name")}{rowField("hospitalizations", i, "Date", "hospitalization_date", "YYYY-MM-DD")}{rowField("hospitalizations", i, "Reason", "reason")}{rowField("hospitalizations", i, "Notes", "notes")}<TouchableOpacity onPress={() => removeRow("hospitalizations", i)}><Text style={s.deleteText}>Remove Hospitalisation</Text></TouchableOpacity></View>)}
      <TouchableOpacity style={s.secondary} onPress={() => addRow("hospitalizations")}><Text style={s.secondaryText}>+ Add Hospitalisation</Text></TouchableOpacity>
    </View><View style={s.card}><Text style={s.cardTitle}>Medication Setup</Text><Text style={s.hint}>Leave blank if medications will be assigned later.</Text>
      {form.medications.map((m, i) => <View key={i} style={s.rowCard}><Text style={s.cardTitle}>Medication {i + 1}</Text>
        <TouchableOpacity style={s.secondary} onPress={() => setMedicineSearchIndex(medicineSearchIndex === i ? null : i)}><Text style={s.secondaryText}>{m.medication_id ? `Selected: ${m.medicine_search || `#${m.medication_id}`}` : "Select Medicine"}</Text></TouchableOpacity>
        {medicineSearchIndex === i && <View style={s.searchBox}><TextInput style={s.input} placeholder="Search medicine catalogue" value={m.medicine_search || ""} onChangeText={(v) => { updateRow("medications", i, "medicine_search", v); updateRow("medications", i, "medication_id", ""); }} />
          {medicines.filter((v) => medicineLabel(v).toLowerCase().includes((m.medicine_search || "").toLowerCase())).slice(0, 30).map((v) => <TouchableOpacity key={v.id} style={s.searchItem} onPress={() => { setForm((prev) => ({ ...prev, medications: prev.medications.map((row, index) => index === i ? { ...row, medication_id: String(v.id), medicine_search: medicineLabel(v) } : row) })); setMedicineSearchIndex(null); }}><Text>{medicineLabel(v)}</Text></TouchableOpacity>)}
        </View>}
        {rowField("medications", i, "Dosage Instruction", "dosage_instruction", "e.g. After food")}{rowField("medications", i, "Dosage Quantity", "dosage_quantity", "1")}{rowField("medications", i, "Frequency", "frequency", "e.g. Daily")}{rowChoices(i, "time_slot", ["AM", "PM", "NIGHT", "OTHER"])}{rowField("medications", i, "Scheduled Time", "scheduled_time", "HH:mm")}{rowField("medications", i, "Start Date", "start_date", "YYYY-MM-DD")}{rowField("medications", i, "End Date", "end_date", "YYYY-MM-DD")}{rowField("medications", i, "Prescribed By", "prescribed_by")}
        <TouchableOpacity onPress={() => removeRow("medications", i)}><Text style={s.deleteText}>Remove Medication</Text></TouchableOpacity></View>)}
      <TouchableOpacity style={s.secondary} onPress={() => addRow("medications")}><Text style={s.secondaryText}>+ Add Medication</Text></TouchableOpacity>
    </View></>}
    {step === 5 && <View style={s.card}><Text style={s.cardTitle}>Admission & Care Assessment</Text>
      {field("Admission Date & Time *", "admitted_at", "YYYY-MM-DDTHH:mm")}{choice("Admission Type", "admission_type", ["NEW_ADMISSION", "READMISSION", "TRANSFER"].map((v) => ({ value: v, label: v.replaceAll("_", " ") })))}
      {field("Admission Source", "admission_source")}{field("Reason for Admission", "reason_for_admission", "", true)}{field("Admission Medical Summary", "medical_summary", "", true)}{field("Mobility Notes", "mobility_notes", "", true)}{field("Dietary Notes", "dietary_notes", "", true)}{field("Special Care Instructions", "special_care_instructions", "", true)}{field("Belongings Notes", "belongings_notes", "", true)}{field("Initial Observations", "initial_observations", "", true)}{field("Additional Comments", "additional_comments", "", true)}
    </View>}
    {step === 6 && <View style={s.card}><Text style={s.cardTitle}>Consent & Admission Agreement</Text>
      {field("Consent Given By *", "consent_given_by")}{field("Relationship", "consent_relationship")}{field("Contact Number", "consent_contact_number", "", false, "phone-pad")}
      <Text style={s.section}>Care Permissions</Text>
      {consentFields.map((key) => toggle(key.replaceAll("_", " ").replace(/\b\w/g, (c) => c.toUpperCase()) + (["information_sharing_consent", "family_notification_consent"].includes(key) ? " (optional)" : " *"), key))}
      <Text style={s.section}>Structured Agreement</Text>
      {field("Agreement Title", "agreement_title")}{field("Agreement Version", "agreement_version")}{field("Monthly Fee (RM)", "monthly_fee", "0.00", false, "numeric")}
      {agreementFields.map((key, i) => toggle(`${i + 1}. ${agreementLabels[i]} *`, key))}{field("Consent / Agreement Notes", "consent_notes", "", true)}
      <Text style={s.hint}>All eight agreement acknowledgements are required. Optional sharing and family notification permissions remain independent choices.</Text>
    </View>}
    {step === 7 && <><Text style={s.section}>Review Before Completion</Text>
      {review("Resident", [["Name", str(form.full_name)], ["IC / Passport", str(form.ic_number)], ["Room", rooms.find((r) => String(r.id) === form.room_id)?.room_number || "Not assigned"], ["Phone", str(form.phone)]], 1)}
      {review("Family Contact", [["Name", str(form.contact_full_name)], ["Relationship", str(form.contact_relationship)], ["Phone", str(form.contact_phone)]], 2)}
      {review("Medical History", [["Conditions", str(form.medical_condition)], ["Allergies", str(form.allergies)], ["Psychiatric Diagnosis", str(form.primary_psychiatric_diagnosis)]], 3)}
      {review("Hospital & Medication", [["Hospitalisations", String(form.hospitalizations.filter(hasHospital).length)], ["Medications", String(form.medications.filter(hasRow).length)]], 4)}
      {review("Care Assessment", [["Admission", str(form.admitted_at)], ["Type", str(form.admission_type)], ["Reason", str(form.reason_for_admission)]], 5)}
      {review("Consent & Agreement", [["Given By", str(form.consent_given_by)], ["Required Permissions", ["admission_consent", "care_consent", "medication_consent", "emergency_treatment_consent", "terms_acknowledged"].every((key) => form[key] === true) ? "Accepted" : "Incomplete"], ["Agreement Sections", `${agreementFields.filter((key) => form[key] === true).length} of 8`]], 6)}
      <Text style={s.warning}>Completion creates real resident and admission records. Confirm all details and permissions before proceeding.</Text>
    </>}
    <View style={s.actions}>
      {step > 1 && <TouchableOpacity disabled={saving || submitting} style={s.secondary} onPress={() => { setStep((n) => n - 1); setError(""); }}><Text style={s.secondaryText}>← Back</Text></TouchableOpacity>}
      <TouchableOpacity disabled={saving || submitting || submissionBlocked} style={[s.draftButton, (saving || submitting || submissionBlocked) && s.disabled]} onPress={() => void save()}><Text style={s.buttonText}>{saving ? "Saving..." : draftId ? "Save Draft Changes" : "Save Draft"}</Text></TouchableOpacity>
      <TouchableOpacity disabled={saving || submitting || submissionBlocked} style={[s.nextButton, (saving || submitting || submissionBlocked) && s.disabled]} onPress={step === 7 ? finish : next}><Text style={s.buttonText}>{submitting ? "Completing..." : step === 7 ? "Complete Admission" : "Continue →"}</Text></TouchableOpacity>
    </View>
    <Text style={s.hint}>Drafts can be resumed on web or mobile. Saving a draft does not create a resident.</Text>
  </ScrollView>;
}
const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 60 }, center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 12 }, heading: { fontSize: 25, fontWeight: "800", color: "#0f172a" }, subtitle: { marginTop: 5, color: "#64748b" }, progress: { height: 8, backgroundColor: "#e2e8f0", borderRadius: 6, overflow: "hidden", marginVertical: 16 }, progressFill: { height: "100%", backgroundColor: "#2563eb" }, draft: { color: "#1d4ed8", fontWeight: "700", marginBottom: 10 }, error: { color: "#b91c1c", backgroundColor: "#fef2f2", padding: 12, borderRadius: 10, marginBottom: 12 }, warning: { color: "#92400e", backgroundColor: "#fffbeb", padding: 12, borderRadius: 10, marginBottom: 12, lineHeight: 21 }, card: { backgroundColor: "white", borderColor: "#e2e8f0", borderWidth: 1, borderRadius: 14, padding: 16, marginBottom: 14 }, cardTitle: { color: "#0f172a", fontWeight: "700", fontSize: 18, marginBottom: 15 }, section: { fontSize: 17, fontWeight: "700", color: "#334155", marginTop: 12, marginBottom: 12 }, field: { marginBottom: 15 }, label: { color: "#334155", fontWeight: "600", marginBottom: 6 }, input: { borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 9, padding: 12, fontSize: 15, color: "#0f172a", backgroundColor: "white" }, multi: { minHeight: 76, textAlignVertical: "top" }, wrap: { flexDirection: "row", flexWrap: "wrap", gap: 8 }, chip: { borderColor: "#cbd5e1", borderWidth: 1, padding: 10, borderRadius: 9 }, chipOn: { borderColor: "#2563eb", backgroundColor: "#eff6ff" }, chipText: { color: "#475569", fontWeight: "600" }, chipTextOn: { color: "#1d4ed8" }, toggle: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", borderBottomWidth: 1, borderBottomColor: "#f1f5f9", paddingVertical: 9, gap: 10 }, toggleLabel: { flex: 1, color: "#334155", fontWeight: "500" }, rowCard: { borderWidth: 1, borderColor: "#e2e8f0", backgroundColor: "#f8fafc", borderRadius: 12, padding: 12, marginBottom: 12 }, secondary: { borderColor: "#bfdbfe", borderWidth: 1, padding: 13, borderRadius: 10, marginBottom: 10, alignItems: "center", backgroundColor: "#eff6ff" }, secondaryText: { color: "#1d4ed8", fontWeight: "700" }, deleteText: { color: "#b91c1c", fontWeight: "700", paddingVertical: 10 }, searchBox: { marginVertical: 10 }, searchItem: { padding: 12, borderBottomWidth: 1, borderBottomColor: "#e2e8f0" }, hint: { color: "#64748b", fontSize: 12, lineHeight: 19, marginBottom: 12 }, reviewText: { color: "#475569", marginBottom: 8, lineHeight: 20 }, actions: { gap: 10, marginTop: 12 }, draftButton: { backgroundColor: "#d97706", borderRadius: 10, padding: 15, alignItems: "center" }, nextButton: { backgroundColor: "#2563eb", borderRadius: 10, padding: 15, alignItems: "center" }, buttonText: { color: "white", fontSize: 16, fontWeight: "700" }, disabled: { opacity: 0.5 },
});
