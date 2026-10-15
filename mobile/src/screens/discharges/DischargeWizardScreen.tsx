import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from "react-native";
import { useNavigation, useRoute, type RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { AppStackParamList } from "../../navigation/AppNavigator";
import {
  apiError, completeDischarge, createDischarge, emptyDischarge, formFromDischarge,
  getDischarge, getResidentContacts, getResidentMedications, getResidentsForDischarge,
  updateDischarge, type Contact, type DischargeForm, type Resident, type ResidentMedication,
} from "../../api/dischargeApi";

type FieldName = Exclude<keyof DischargeForm, "belongings_returned" | "resident_id">;
const types = [
  ["PLANNED_DISCHARGE", "Planned Discharge"], ["HOSPITAL_TRANSFER", "Hospital Transfer"],
  ["CARE_FACILITY_TRANSFER", "Another Care Facility"], ["FAMILY_REQUEST", "Family Request"],
  ["RESIDENT_REQUEST", "Resident Request"], ["OTHER", "Other"],
];
const labels: Partial<Record<FieldName, string>> = {
  discharged_at: "Discharge Date & Time *", discharge_destination: "Destination", reason_for_discharge: "Reason for Discharge *",
  condition_at_discharge: "Condition at Discharge *", treatment_care_summary: "Treatment / Care Summary",
  medical_summary: "Medical Summary", follow_up_instructions: "Follow-Up Instructions", medication_summary: "Medication Summary",
  medication_instructions: "Medication Instructions", discharged_to: "Discharged To", discharged_to_relationship: "Relationship",
  discharged_to_contact: "Contact Number", belongings_notes: "Belongings Notes", administrative_notes: "Administrative Notes",
};
const longFields: FieldName[] = ["reason_for_discharge", "condition_at_discharge", "treatment_care_summary", "medical_summary", "follow_up_instructions", "medication_summary", "medication_instructions", "belongings_notes", "administrative_notes"];
const steps = ["Resident", "Discharge", "Handover", "Review"];
const colors = { ink: "#0f172a", blue: "#2563eb", red: "#b91c1c", muted: "#64748b" };

export default function DischargeWizardScreen() {
  const route = useRoute<RouteProp<AppStackParamList, "DischargeWizard">>();
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParamList>>();
  const [form, setForm] = useState<DischargeForm>(emptyDischarge);
  const [residents, setResidents] = useState<Resident[]>([]);
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [meds, setMeds] = useState<ResidentMedication[]>([]);
  const [step, setStep] = useState(1);
  const [id, setId] = useState<number | null>(route.params?.dischargeId ?? null);
  const [reference, setReference] = useState("");
  const [completed, setCompleted] = useState(false);
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedId, setSelectedId] = useState("");

  const update = (key: FieldName, value: string) => { setForm(f => ({ ...f, [key]: value })); setError(""); };
  const loadResidentExtras = useCallback(async (residentId: number, autoFill: boolean, residentList: Resident[]) => {
    const [cs, ms] = await Promise.all([
      getResidentContacts(residentId).catch(() => [] as Contact[]),
      getResidentMedications(residentId).catch(() => [] as ResidentMedication[]),
    ]);
    setContacts(cs); setMeds(ms);
    if (autoFill) {
      const r = residentList.find(x => x.id === residentId);
      const primary = cs.find(x => x.is_primary) ?? cs[0];
      const medical = [r?.medical_condition && `Medical condition: ${r.medical_condition}`, r?.chronic_disease && `Chronic disease: ${r.chronic_disease}`, r?.allergies && `Allergies: ${r.allergies}`, r?.medical_notes && `Medical notes: ${r.medical_notes}`].filter(Boolean).join("\n");
      const medication = ms.map(m => [m.medication?.medicine_name ?? `Medication #${m.medication_id}`, m.medication?.dosage, m.medication?.unit, m.dosage_quantity && `Qty ${m.dosage_quantity}`, m.frequency, m.time_slot, m.dosage_instruction].filter(Boolean).join(" · ")).join("\n");
      setForm(f => ({ ...f,
        medical_summary: f.medical_summary || medical, medication_summary: f.medication_summary || medication,
        discharged_to: f.discharged_to || primary?.full_name || r?.emergency_contact || "",
        discharged_to_relationship: f.discharged_to_relationship || primary?.relationship || r?.emergency_relationship || "",
        discharged_to_contact: f.discharged_to_contact || primary?.phone || r?.emergency_phone || "",
      }));
    }
  }, []);

  useEffect(() => {
    let active = true;
    const load = async () => {
      try {
        const list = await getResidentsForDischarge();
        if (!active) return;
        setResidents(list);
        if (route.params?.dischargeId) {
          const d = await getDischarge(route.params.dischargeId);
          if (!active) return;
          setForm(formFromDischarge(d)); setId(d.id); setReference(d.discharge_number);
          setSelectedId(String(d.resident_id)); setCompleted(d.status === "COMPLETED");
          setStep(d.status === "COMPLETED" ? 4 : 2);
          await loadResidentExtras(d.resident_id, false, list);
        }
      } catch (e) { if (active) setError(apiError(e, "Unable to load discharge.")); }
      finally { if (active) setLoading(false); }
    };
    void load();
    return () => { active = false; };
  }, [route.params?.dischargeId, loadResidentExtras]);

  const selectResident = async (residentId: string) => {
    setSelectedId(residentId);
    setForm(f => ({ ...f, resident_id: residentId }));
    setContacts([]); setMeds([]); setError("");
    if (residentId) await loadResidentExtras(Number(residentId), true, residents);
  };
  const selected = residents.find(r => String(r.id) === form.resident_id);
  const validation = (n: number) => {
    if (n === 1 && !form.resident_id) return "Select an active resident.";
    if (n === 2 && !form.discharged_at.trim()) return "Discharge date and time is required.";
    if (n === 2 && !/^\d{4}-\d\d-\d\dT\d\d:\d\d$/.test(form.discharged_at)) return "Use YYYY-MM-DDTHH:mm for discharge date and time.";
    if (n === 2 && !form.reason_for_discharge.trim()) return "Reason for discharge is required.";
    if (n === 2 && !form.condition_at_discharge.trim()) return "Condition at discharge is required.";
    return "";
  };
  const save = async () => {
    if (busy || completed) return;
    if (!form.resident_id) { setStep(1); setError("Select a resident before saving."); return; }
    setBusy(true); setError("");
    try {
      const d = id ? await updateDischarge(id, form) : await createDischarge(form);
      setId(d.id); setReference(d.discharge_number);
      Alert.alert("Draft saved", `Discharge ${d.discharge_number} saved. Resident remains Active.`);
    } catch (e) { setError(apiError(e, "Unable to save discharge draft.")); }
    finally { setBusy(false); }
  };
  const finalize = () => {
    for (const n of [1, 2]) { const problem = validation(n); if (problem) { setStep(n); setError(problem); return; } }
    if (busy || completed) return;
    Alert.alert("Complete discharge?", `This will change ${selected?.full_name ?? "the resident"} to Discharged and release their room. This cannot be undone here.`, [
      { text: "Cancel", style: "cancel" },
      { text: "Complete Discharge", style: "destructive", onPress: async () => {
        setBusy(true); setError("");
        try {
          const d = id ? await updateDischarge(id, form) : await createDischarge(form);
          // Retain the server record ID before finalizing, including when the request fails.
          setId(d.id); setReference(d.discharge_number);
          const result = await completeDischarge(d.id);
          setCompleted(true);
          Alert.alert("Discharge completed", `${result.resident?.full_name ?? selected?.full_name ?? "Resident"} has been discharged.`, [
            { text: "Back to Discharges", onPress: () => navigation.goBack() },
          ]);
        } catch (e) {
          setError(`${apiError(e, "Unable to complete discharge.")} Check the discharge status before retrying. Do not create another draft.`);
        } finally { setBusy(false); }
      } },
    ]);
  };
  const field = (key: FieldName, multiline = longFields.includes(key)) => (
    <View key={key} style={styles.field}>
      <Text style={styles.label}>{labels[key] ?? key}</Text>
      <TextInput style={[styles.input, multiline && styles.area]} value={form[key]} onChangeText={v => update(key, v)} editable={!completed && !busy} multiline={multiline} textAlignVertical={multiline ? "top" : "center"} placeholder={key === "discharged_at" ? "YYYY-MM-DDTHH:mm" : ""} autoCapitalize="sentences" />
    </View>
  );
  const options = (value: string, onPress: (v: string) => void, optionsList: string[][]) => (
    <View style={styles.options}>{optionsList.map(([v, label]) => <TouchableOpacity key={v} disabled={completed || busy} style={[styles.choice, value === v && styles.selected]} onPress={() => onPress(v)}><Text style={[styles.choiceText, value === v && styles.selectedText]}>{label}</Text></TouchableOpacity>)}</View>
  );
  const line = (label: string, value?: string | number | boolean | null) => <View style={styles.summaryRow} key={label}><Text style={styles.summaryLabel}>{label}</Text><Text style={styles.summaryValue}>{value == null || value === "" ? "—" : String(value)}</Text></View>;
  if (loading) return <View style={styles.loading}><ActivityIndicator color={colors.blue} /><Text style={styles.hint}>Loading discharge details...</Text></View>;
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.title}>{completed ? "Discharge Summary" : "Prepare Discharge"}</Text>
    <Text style={styles.hint}>{reference || "New discharge"} · {completed ? "Completed" : `Step ${step} of 4 · ${steps[step - 1]}`}</Text>
    {!completed && <View style={styles.progress}><View style={[styles.progressFill, { width: `${step * 25}%` }]} /></View>}
    {error ? <Text style={styles.error}>{error}</Text> : null}
    {!completed && <View style={styles.options}>{steps.map((label, i) => <TouchableOpacity key={label} disabled={i + 1 > step || busy} style={[styles.choice, i + 1 === step && styles.selected]} onPress={() => setStep(i + 1)}><Text style={styles.choiceText}>{i + 1}. {label}</Text></TouchableOpacity>)}</View>}
    {step === 1 && <View style={styles.card}>
      <Text style={styles.section}>1. Select Active Resident</Text>
      <Text style={styles.hint}>Saving a draft does not discharge the resident.</Text>
      {options(form.resident_id, v => void selectResident(v), residents.filter(r => r.status === "Active" || String(r.id) === selectedId).map(r => [String(r.id), `${r.full_name}${r.ic_number ? ` · ${r.ic_number}` : ""}`]))}
      {selected && <>{line("Name", selected.full_name)}{line("Admission", selected.admission_date)}{line("Room", selected.room_id ? `Room #${selected.room_id}` : "Not assigned")}{line("Allergies", selected.allergies)}{line("Medical Condition", selected.medical_condition)}</>}
    </View>}
    {step === 2 && <View style={styles.card}>
      <Text style={styles.section}>2. Discharge & Clinical Summary</Text>
      {field("discharged_at")}
      <Text style={styles.label}>Discharge Type</Text>
      {options(form.discharge_type, v => update("discharge_type", v), types)}
      {(["discharge_destination", "reason_for_discharge", "condition_at_discharge", "treatment_care_summary", "medical_summary", "follow_up_instructions"] as FieldName[]).map(k => field(k))}
    </View>}
    {step === 3 && <View style={styles.card}>
      <Text style={styles.section}>3. Medication, Handover & Belongings</Text>
      <Text style={styles.label}>Current Medication</Text>
      {meds.length ? meds.map(m => <Text key={m.id} style={styles.hint}>• {[m.medication?.medicine_name ?? `Medication #${m.medication_id}`, m.medication?.dosage, m.frequency, m.time_slot, m.dosage_instruction].filter(Boolean).join(" · ")}</Text>) : <Text style={styles.hint}>No resident medications found.</Text>}
      {(["medication_summary", "medication_instructions", "discharged_to", "discharged_to_relationship", "discharged_to_contact"] as FieldName[]).map(k => field(k))}
      {contacts.length ? <Text style={styles.hint}>Available contacts: {contacts.map(c => `${c.full_name}${c.relationship ? ` (${c.relationship})` : ""}`).join(", ")}</Text> : null}
      <View style={styles.switchRow}><Text style={styles.label}>Belongings Returned</Text><Switch disabled={completed || busy} value={form.belongings_returned} onValueChange={v => setForm(f => ({ ...f, belongings_returned: v }))} /></View>
      {field("belongings_notes")}{field("administrative_notes")}
    </View>}
    {step === 4 && <View style={styles.card}>
      <Text style={styles.section}>4. {completed ? "Completed Discharge Summary" : "Review & Complete"}</Text>
      {line("Resident", selected?.full_name)}{line("Discharge Reference", reference)}{line("Status", completed ? "COMPLETED" : "DRAFT")}
      {line("Discharge Date", form.discharged_at)}{line("Type", types.find(x => x[0] === form.discharge_type)?.[1])}
      {line("Destination", form.discharge_destination)}{line("Reason", form.reason_for_discharge)}{line("Condition", form.condition_at_discharge)}
      {line("Treatment / Care", form.treatment_care_summary)}{line("Medical Summary", form.medical_summary)}{line("Follow-Up", form.follow_up_instructions)}
      {line("Medication Summary", form.medication_summary)}{line("Medication Instructions", form.medication_instructions)}
      {line("Discharged To", form.discharged_to)}{line("Relationship", form.discharged_to_relationship)}{line("Contact", form.discharged_to_contact)}
      {line("Belongings Returned", form.belongings_returned ? "Yes" : "No")}{line("Belongings Notes", form.belongings_notes)}{line("Administrative Notes", form.administrative_notes)}
      {!completed && <Text style={styles.warning}>Completing this discharge changes the resident to Discharged, releases the room, and records a clinical timeline event.</Text>}
    </View>}
    {!completed && <>
      <View style={styles.actions}>
        <TouchableOpacity disabled={step === 1 || busy} style={styles.outline} onPress={() => { setStep(s => Math.max(1, s - 1)); setError(""); }}><Text style={styles.outlineText}>← Back</Text></TouchableOpacity>
        <TouchableOpacity disabled={busy} style={styles.outline} onPress={() => void save()}><Text style={styles.outlineText}>{busy ? "Saving..." : "Save Draft"}</Text></TouchableOpacity>
      </View>
      <TouchableOpacity disabled={busy} style={[styles.button, step === 4 && styles.dangerButton]} onPress={() => {
        if (step === 4) { finalize(); return; }
        const msg = validation(step); if (msg) { setError(msg); return; }
        setError(""); setStep(s => Math.min(4, s + 1));
      }}><Text style={styles.buttonText}>{busy ? "Please wait..." : step === 4 ? "Complete Discharge" : "Continue →"}</Text></TouchableOpacity>
    </>}
  </ScrollView>;
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 48 }, loading: { flex: 1, justifyContent: "center", alignItems: "center" },
  title: { fontSize: 25, fontWeight: "800", color: colors.ink }, hint: { color: colors.muted, marginTop: 6, lineHeight: 21 },
  progress: { backgroundColor: "#e2e8f0", height: 8, borderRadius: 10, marginVertical: 16, overflow: "hidden" }, progressFill: { height: "100%", backgroundColor: colors.blue },
  card: { backgroundColor: "white", padding: 16, borderRadius: 14, borderColor: "#e2e8f0", borderWidth: 1, marginTop: 14, marginBottom: 18 },
  section: { color: colors.ink, fontSize: 19, fontWeight: "800", marginBottom: 12 }, field: { marginTop: 14 }, label: { color: "#334155", fontWeight: "700", marginBottom: 7 },
  input: { borderColor: "#cbd5e1", borderWidth: 1, borderRadius: 10, padding: 12, color: colors.ink, backgroundColor: "white" }, area: { minHeight: 90 },
  options: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginTop: 10, marginBottom: 10 }, choice: { borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 10, padding: 10, backgroundColor: "white" },
  selected: { borderColor: colors.blue, backgroundColor: "#dbeafe" }, choiceText: { color: "#334155", fontWeight: "600" }, selectedText: { color: "#1d4ed8" },
  summaryRow: { borderBottomWidth: 1, borderBottomColor: "#f1f5f9", paddingVertical: 10 }, summaryLabel: { fontSize: 12, color: colors.muted, fontWeight: "700" }, summaryValue: { marginTop: 4, color: colors.ink },
  switchRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginTop: 14 },
  actions: { flexDirection: "row", gap: 10, marginBottom: 12 }, outline: { flex: 1, borderWidth: 1, borderColor: "#93c5fd", borderRadius: 10, padding: 13, alignItems: "center" }, outlineText: { color: colors.blue, fontWeight: "700" },
  button: { backgroundColor: colors.blue, padding: 15, borderRadius: 10, alignItems: "center" }, dangerButton: { backgroundColor: colors.red }, buttonText: { color: "white", fontWeight: "800" },
  error: { color: colors.red, marginVertical: 12 }, warning: { color: "#991b1b", backgroundColor: "#fef2f2", padding: 12, borderRadius: 8, marginTop: 14 },
});
