import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import axios from "axios";
import api from "../../api/api";
import { useAuth } from "../../context/AuthContext";

type AuditEntry = {
  id: number;
  user_id: number | null;
  actor_name: string | null;
  resident_id: number | null;
  module: string;
  action: string;
  created_on: string;
};
type AuditPage = { data: AuditEntry[]; current_page: number; last_page: number; total: number; per_page: number };
type IntegrityResult = {
  status: "verified" | "integrity_failure" | "verification_unavailable";
  valid?: boolean;
  checked?: number;
  failed_entry_id?: number | null;
  reason?: string | null;
  message?: string;
};

const localDate = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
const startOfMonth = () => { const today = new Date(); return localDate(new Date(today.getFullYear(), today.getMonth(), 1)); };
const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;
function validDate(value: string): boolean {
  if (!ISO_DATE.test(value)) return false;
  const [year, month, day] = value.split("-").map(Number);
  const d = new Date(year, month - 1, day);
  return d.getFullYear() === year && d.getMonth() === month - 1 && d.getDate() === day;
}
function dateLabel(value: string) { return String(value || "-").replace("T", " ").replace(/\.\d+Z?$/, "").slice(0, 19); }
function apiMessage(error: unknown, fallback: string): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data;
    const first = data?.errors && Object.values(data.errors).flat().find(Boolean);
    return first ? String(first) : data?.message || (error.response?.status === 403 ? "Administrator access is required." : fallback);
  }
  return fallback;
}

export default function AuditHistoryScreen() {
  const { user } = useAuth();
  const [from, setFrom] = useState(startOfMonth);
  const [to, setTo] = useState(() => localDate(new Date()));
  const [module, setModule] = useState("");
  const [action, setAction] = useState("");
  const [actorSearch, setActorSearch] = useState("");
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<AuditPage | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [integrity, setIntegrity] = useState<IntegrityResult | null>(null);
  const [integrityLoading, setIntegrityLoading] = useState(false);

  const load = useCallback(async (targetPage: number) => {
    if (!validDate(from) || !validDate(to)) { Alert.alert("Invalid dates", "Use YYYY-MM-DD for both dates."); return; }
    const a = new Date(`${from}T00:00:00`);
    const b = new Date(`${to}T00:00:00`);
    if (a > b || Math.round((b.getTime() - a.getTime()) / 86400000) > 365) {
      Alert.alert("Date range", "Choose a range of up to 366 calendar days, with the start on or before the end."); return;
    }
    setLoading(true);
    setError("");
    try {
      const response = await api.get<AuditPage>("/activity-logs", {
        params: { from, to, module: module.trim() || undefined, action: action.trim() || undefined, per_page: 25, page: targetPage },
      });
      setResult(response.data);
      setPage(response.data.current_page || targetPage);
    } catch (e) { setResult(null); setError(apiMessage(e, "Unable to load audit history.")); }
    finally { setLoading(false); }
  }, [from, to, module, action]);

  useEffect(() => { void load(1); }, []); // Load default reporting period once on entry.

  const verify = async () => {
    setIntegrityLoading(true);
    setIntegrity(null);
    try {
      const response = await api.get<IntegrityResult>("/activity-logs/integrity");
      setIntegrity(response.data);
    } catch (e) {
      if (axios.isAxiosError(e) && (e.response?.status === 409 || e.response?.status === 503) && e.response.data?.status) {
        setIntegrity(e.response.data as IntegrityResult);
      } else {
        Alert.alert("Integrity check unavailable", apiMessage(e, "Unable to perform the integrity check."));
      }
    } finally { setIntegrityLoading(false); }
  };

  if (user?.role !== "Administrator") {
    return <SafeAreaView style={styles.root}><View style={styles.card}><Text style={styles.heading}>Administrator access required</Text><Text>Audit history is restricted to authorized administrators.</Text></View></SafeAreaView>;
  }

  const items = (result?.data || []).filter((entry) => {
    const query = actorSearch.trim().toLowerCase();
    return !query || (entry.actor_name || "").toLowerCase().includes(query) || String(entry.user_id || "").includes(query);
  });

  return (
    <SafeAreaView style={styles.root} edges={["bottom"]}>
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text style={styles.title}>Audit History</Text>
        <Text style={styles.note}>Read-only activity evidence. Administrators only.</Text>
        <View style={styles.card}>
          <Text style={styles.heading}>Filters</Text>
          <Text style={styles.label}>From (YYYY-MM-DD)</Text>
          <TextInput value={from} onChangeText={setFrom} placeholder="YYYY-MM-DD" autoCapitalize="none" style={styles.input} />
          <Text style={styles.label}>To (YYYY-MM-DD)</Text>
          <TextInput value={to} onChangeText={setTo} placeholder="YYYY-MM-DD" autoCapitalize="none" style={styles.input} />
          <Text style={styles.label}>Module (exact value, optional)</Text>
          <TextInput value={module} onChangeText={setModule} placeholder="e.g. Staff Administration" style={styles.input} />
          <Text style={styles.label}>Action (exact value, optional)</Text>
          <TextInput value={action} onChangeText={setAction} placeholder="e.g. created" style={styles.input} />
          <Pressable style={styles.button} disabled={loading} onPress={() => void load(1)}><Text style={styles.buttonText}>{loading ? "Loading…" : "Apply Filters / Refresh"}</Text></Pressable>
        </View>
        <View style={styles.card}>
          <Text style={styles.heading}>Audit integrity</Text>
          <Text style={styles.note}>Checks the integrity of the recorded audit chain. This does not alter records.</Text>
          <Pressable style={styles.button} disabled={integrityLoading} onPress={() => void verify()}><Text style={styles.buttonText}>{integrityLoading ? "Checking…" : "Verify Integrity"}</Text></Pressable>
          {integrity && <View style={[styles.integrity, integrity.status === "verified" ? styles.good : styles.bad]}>
            <Text style={styles.heading}>{integrity.status === "verified" ? "Verified" : integrity.status === "integrity_failure" ? "INTEGRITY FAILURE" : "Verification unavailable"}</Text>
            {typeof integrity.checked === "number" && <Text>Entries checked: {integrity.checked}</Text>}
            {integrity.failed_entry_id != null && <Text>Failed entry: {integrity.failed_entry_id}</Text>}
            {integrity.reason && <Text>Reason: {integrity.reason}</Text>}
            {integrity.message && <Text>{integrity.message}</Text>}
            {integrity.status !== "verified" && <Text style={styles.warning}>Do not treat these records as verified. Escalate to system administration.</Text>}
          </View>}
        </View>
        <View style={styles.card}>
          <Text style={styles.heading}>Activity records</Text>
          {result && <Text style={styles.note}>Total records: {result.total} · Page {result.current_page} of {result.last_page || 1}</Text>}
          <Text style={styles.label}>Find staff on this page</Text>
          <TextInput value={actorSearch} onChangeText={setActorSearch} placeholder="Staff name or user ID" style={styles.input} />
          <Text style={styles.note}>Staff search filters only the currently loaded page. Module and action filters search the backend.</Text>
          {loading && <ActivityIndicator style={{ margin: 12 }} />}
          {!!error && <Text style={styles.warning}>{error}</Text>}
          {!loading && result && items.length === 0 && <Text style={styles.note}>No matching audit activity on this page.</Text>}
          {!loading && items.map(entry => <View key={entry.id} style={styles.entry}>
            <Text style={styles.entryTitle}>{entry.module || "Unknown module"} · {entry.action || "Unknown action"}</Text>
            <Text style={styles.note}>{dateLabel(entry.created_on)}</Text>
            <Text>Staff: {entry.actor_name || `User ${entry.user_id ?? "unknown"}`}</Text>
            {entry.resident_id != null && <Text>Resident ID: {entry.resident_id}</Text>}
            <Text style={styles.note}>Audit ID: {entry.id}</Text>
          </View>)}
          {result && <View style={styles.pager}>
            <Pressable disabled={loading || page <= 1} style={[styles.smallButton, (loading || page <= 1) && styles.disabled]} onPress={() => void load(page - 1)}><Text style={styles.buttonText}>Previous</Text></Pressable>
            <Pressable disabled={loading || page >= result.last_page} style={[styles.smallButton, (loading || page >= result.last_page) && styles.disabled]} onPress={() => void load(page + 1)}><Text style={styles.buttonText}>Next</Text></Pressable>
          </View>}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#f1f5f9" }, content: { padding: 16, paddingBottom: 40, gap: 14 },
  title: { fontSize: 25, fontWeight: "700", color: "#0f172a" },
  heading: { fontSize: 17, fontWeight: "700", color: "#0f172a", marginBottom: 6 },
  label: { fontSize: 13, fontWeight: "600", color: "#334155", marginTop: 8, marginBottom: 4 },
  note: { fontSize: 12, color: "#64748b", lineHeight: 19, marginBottom: 5 },
  card: { backgroundColor: "#fff", padding: 15, borderRadius: 14, gap: 3 },
  input: { borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 9, paddingHorizontal: 12, paddingVertical: 10, fontSize: 15, color: "#0f172a" },
  button: { backgroundColor: "#2563eb", padding: 13, borderRadius: 9, alignItems: "center", marginTop: 10 },
  smallButton: { backgroundColor: "#2563eb", paddingVertical: 11, paddingHorizontal: 20, borderRadius: 9 },
  disabled: { opacity: 0.4 }, buttonText: { color: "#fff", fontWeight: "700" },
  integrity: { padding: 12, borderRadius: 9, marginTop: 10 }, good: { backgroundColor: "#dcfce7" }, bad: { backgroundColor: "#fee2e2" },
  warning: { color: "#991b1b", fontWeight: "600", marginTop: 7 },
  entry: { paddingVertical: 12, borderTopWidth: 1, borderTopColor: "#e2e8f0", gap: 3 },
  entryTitle: { fontSize: 14, fontWeight: "700", color: "#1e293b" },
  pager: { flexDirection: "row", justifyContent: "space-between", marginTop: 10 },
});
