import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, RefreshControl, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from "react-native";
import { acknowledgeAIAlert, getAIAlerts, getAIOverview, getNurseRecommendation, getResidentPrediction, getResidentRisk, resolveAIAlert, type AIRecord } from "../../api/aiIntelligenceApi";

const str = (v: unknown): string => v === null || v === undefined ? "—" : typeof v === "object" ? JSON.stringify(v, null, 2) : String(v);
const name = (v: unknown): string => {
  if (!v || typeof v !== "object") return "Resident";
  const r = v as AIRecord;
  return str(r.full_name ?? r.name ?? ([r.first_name, r.last_name].filter(Boolean).join(" ") || "Resident"));
};
const errorText = (e: unknown) => e instanceof Error ? e.message : "Request failed. Please try again.";
const Field = ({ label, value }: { label: string; value: unknown }) => <View style={styles.field}><Text style={styles.label}>{label}</Text><Text style={styles.value}>{str(value)}</Text></View>;
const Panel = ({ title, data }: { title: string; data: unknown }) => <View style={styles.card}><Text style={styles.heading}>{title}</Text>{data && typeof data === "object" ? Object.entries(data as AIRecord).map(([key, value]) => <Field key={key} label={key.replace(/_/g, " ")} value={value} />) : <Text>{str(data)}</Text>}</View>;

export default function AIIntelligenceScreen() {
  const [overview, setOverview] = useState<AIRecord | null>(null);
  const [alerts, setAlerts] = useState<AIRecord[]>([]);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [residentId, setResidentId] = useState("");
  const [residentBusy, setResidentBusy] = useState(false);
  const [risk, setRisk] = useState<AIRecord | null>(null);
  const [prediction, setPrediction] = useState<AIRecord | null>(null);
  const [recommendation, setRecommendation] = useState<AIRecord | null>(null);
  const [noteFor, setNoteFor] = useState<number | null>(null);
  const [note, setNote] = useState("");
  const load = useCallback(async () => {
    setBusy(true); setMessage("");
    const [a, b] = await Promise.allSettled([getAIOverview(), getAIAlerts()]);
    if (a.status === "fulfilled") setOverview(a.value);
    if (b.status === "fulfilled") setAlerts(b.value);
    if (a.status === "rejected" || b.status === "rejected") setMessage("Some AI data could not be loaded. Pull down to retry.");
    setBusy(false);
  }, []);
  useEffect(() => { void load(); }, [load]);
  const action = async (id: number, type: "ack" | "resolve", resolutionNote = "") => {
    try {
      setBusy(true);
      if (type === "ack") await acknowledgeAIAlert(id);
      else await resolveAIAlert(id, resolutionNote);
      setNoteFor(null); setNote("");
      await load();
    } catch (e) { setBusy(false); Alert.alert("Unable to update alert", errorText(e)); }
  };
  const inspect = async (value?: number) => {
    const id = value ?? Number(residentId.trim());
    if (!Number.isSafeInteger(id) || id <= 0) { Alert.alert("Resident required", "Enter a valid resident ID."); return; }
    setResidentId(String(id)); setRisk(null); setPrediction(null); setRecommendation(null); setResidentBusy(true);
    const results = await Promise.allSettled([getResidentRisk(id), getResidentPrediction(id), getNurseRecommendation(id)]);
    if (results[0].status === "fulfilled") setRisk(results[0].value);
    if (results[1].status === "fulfilled") setPrediction(results[1].value);
    if (results[2].status === "fulfilled") setRecommendation(results[2].value);
    if (results.some(r => r.status === "rejected")) Alert.alert("Partial information", "Some resident AI information is unavailable.");
    setResidentBusy(false);
  };
  const summary = overview?.summary && typeof overview.summary === "object" ? overview.summary as AIRecord : {};
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={() => void load()} />}>
    <Text style={styles.title}>AI Intelligence</Text>
    <Text style={styles.sub}>Decision support only. Always verify clinical information before acting.</Text>
    {busy && <ActivityIndicator />}
    {!!message && <Text style={styles.warning}>{message}</Text>}
    <View style={styles.card}><Text style={styles.heading}>Care overview</Text>
      <Field label="Residents" value={summary.total_residents} /><Field label="Critical risk records" value={summary.critical_cases} />
      <Field label="Open alerts" value={summary.active_alerts} /><Field label="Pending tasks" value={summary.pending_tasks} />
      <Field label="Unread notifications" value={summary.unread_notifications} />
    </View>
    <View style={styles.card}><Text style={styles.heading}>Critical risk records</Text>
      <Text style={styles.sub}>Risk scores may contain multiple records per resident.</Text>
      {Array.isArray(overview?.critical_residents) && overview.critical_residents.length ? (overview.critical_residents as AIRecord[]).map((item, i) => <TouchableOpacity key={String(item.id ?? i)} onPress={() => void inspect(Number(item.resident_id))} disabled={!item.resident_id} style={styles.item}><Text style={styles.bold}>{name(item.resident)}</Text><Text>Risk: {str(item.risk_level)}</Text><Text style={styles.link}>View resident insights</Text></TouchableOpacity>) : <Text>No critical records returned.</Text>}
    </View>
    <View style={styles.card}><Text style={styles.heading}>Open AI alerts ({alerts.length})</Text>
      {alerts.length === 0 && <Text>No open alerts returned.</Text>}
      {alerts.map((item, i) => {
        const id = Number(item.id); const acknowledged = !!item.acknowledged_at;
        return <View key={String(item.id ?? i)} style={styles.item}>
          <Text style={styles.bold}>{str(item.title ?? item.alert_type ?? item.type ?? `Alert ${i + 1}`)}</Text>
          <Text>{str(item.message ?? item.description ?? item.reason ?? "")}</Text>
          <Text>Resident: {name(item.resident)} · Severity: {str(item.severity ?? item.priority)}</Text>
          <Text>Status: {str(item.status)} · {acknowledged ? "Acknowledged" : "Not acknowledged"}</Text>
          {!!item.resident_id && <TouchableOpacity onPress={() => void inspect(Number(item.resident_id))}><Text style={styles.link}>View resident insights</Text></TouchableOpacity>}
          {Number.isSafeInteger(id) && id > 0 && <View style={styles.actions}>
            {!acknowledged && <TouchableOpacity style={styles.button} disabled={busy} onPress={() => Alert.alert("Acknowledge alert?", "Confirm you have reviewed this alert.", [{ text: "Cancel", style: "cancel" }, { text: "Acknowledge", onPress: () => void action(id, "ack") }])}><Text style={styles.buttonText}>Acknowledge</Text></TouchableOpacity>}
            <TouchableOpacity style={styles.secondaryButton} disabled={busy} onPress={() => { setNoteFor(id); setNote(""); }}><Text>Resolve…</Text></TouchableOpacity>
          </View>}
          {noteFor === id && <View><Text style={styles.label}>Resolution note</Text><TextInput style={styles.input} multiline placeholder="Describe the action taken" value={note} onChangeText={setNote} /><View style={styles.actions}><TouchableOpacity style={styles.secondaryButton} onPress={() => setNoteFor(null)}><Text>Cancel</Text></TouchableOpacity><TouchableOpacity style={styles.button} disabled={!note.trim() || busy} onPress={() => Alert.alert("Resolve this alert?", "This will mark the alert as RESOLVED in SmartCare-AI.", [{ text: "Cancel", style: "cancel" }, { text: "Confirm resolve", onPress: () => void action(id, "resolve", note.trim()) }])}><Text style={styles.buttonText}>Confirm resolution</Text></TouchableOpacity></View></View>}
        </View>;
      })}
    </View>
    <View style={styles.card}><Text style={styles.heading}>Resident AI insights</Text><Text style={styles.sub}>Select a resident above or enter their ID.</Text>
      <TextInput style={styles.input} keyboardType="number-pad" placeholder="Resident ID" value={residentId} onChangeText={setResidentId} />
      <TouchableOpacity style={styles.button} disabled={residentBusy} onPress={() => void inspect()}><Text style={styles.buttonText}>Load insights</Text></TouchableOpacity>
      {residentBusy && <ActivityIndicator />}
    </View>
    {risk && <Panel title="Resident risk" data={risk} />}
    {prediction && <Panel title="Deterioration prediction" data={prediction.prediction ?? prediction} />}
    {recommendation && <Panel title="Nurse recommendations" data={recommendation} />}
    <Text style={styles.footer}>AI output is advisory, not a diagnosis or substitute for professional assessment.</Text>
  </ScrollView>;
}
const styles = StyleSheet.create({ screen: { flex: 1, backgroundColor: "#f1f5f9" }, content: { padding: 16, paddingBottom: 36, gap: 12 }, title: { fontSize: 24, fontWeight: "700", color: "#0f172a" }, heading: { fontSize: 17, fontWeight: "700", marginBottom: 8, color: "#0f172a" }, sub: { color: "#64748b", marginBottom: 8 }, card: { backgroundColor: "white", borderRadius: 12, padding: 16, gap: 8 }, field: { flexDirection: "row", justifyContent: "space-between", gap: 12, borderBottomWidth: 1, borderBottomColor: "#f1f5f9", paddingBottom: 6 }, label: { color: "#64748b", textTransform: "capitalize", flex: 1 }, value: { color: "#0f172a", flex: 1, textAlign: "right" }, item: { paddingVertical: 12, borderTopWidth: 1, borderTopColor: "#e2e8f0", gap: 6 }, bold: { fontWeight: "700", color: "#0f172a" }, link: { color: "#2563eb", fontWeight: "600", marginTop: 4 }, actions: { flexDirection: "row", gap: 10, marginTop: 6, flexWrap: "wrap" }, button: { backgroundColor: "#2563eb", padding: 12, borderRadius: 8, alignItems: "center" }, buttonText: { color: "white", fontWeight: "700" }, secondaryButton: { borderWidth: 1, borderColor: "#94a3b8", padding: 12, borderRadius: 8, alignItems: "center" }, input: { borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 8, padding: 10, minHeight: 44, backgroundColor: "white" }, warning: { color: "#b45309" }, footer: { color: "#64748b", fontSize: 12, textAlign: "center" } });
