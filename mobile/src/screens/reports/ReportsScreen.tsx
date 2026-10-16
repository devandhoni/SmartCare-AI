import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from "react-native";
import * as FileSystem from "expo-file-system/legacy";
import * as Sharing from "expo-sharing";
import * as SecureStore from "expo-secure-store";
import api from "../../api/api";

const REPORTS = [
  { label: "Overview", key: "overview", endpoint: "/reports/overview" },
  { label: "Resident Census", key: "resident-census", endpoint: "/reports/resident-census" },
  { label: "Care Operations", key: "care-operations", endpoint: "/reports/care-operations" },
  { label: "Medication", key: "medication-operations", endpoint: "/reports/medication-operations" },
  { label: "Clinical Monitoring", key: "clinical-monitoring", endpoint: "/reports/clinical-monitoring" },
  { label: "Inventory", key: "inventory-operations", endpoint: "/reports/inventory-operations" },
  { label: "Billing", key: "billing-operations", endpoint: "/reports/billing-operations" },
  { label: "Family Communication", key: "family-facility", endpoint: "/reports/family-facility", view: "family" },
  { label: "Facility Activity", key: "family-facility", endpoint: "/reports/family-facility", view: "facility" },
] as const;

type JsonRecord = Record<string, unknown>;
const objectValue = (value: unknown): JsonRecord => value && typeof value === "object" && !Array.isArray(value) ? value as JsonRecord : {};
const label = (key: string) => key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
const display = (value: unknown): string => {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "boolean") return value ? "Yes" : "No";
  if (typeof value === "number") return value.toLocaleString("en-MY");
  if (typeof value === "string") return value;
  if (Array.isArray(value)) return `${value.length} records`;
  return "Details";
};
const localDate = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
const validDate = (date: string) => /^\d{4}-\d{2}-\d{2}$/.test(date) && !Number.isNaN(new Date(`${date}T12:00:00`).getTime()) && localDate(new Date(`${date}T12:00:00`)) === date;
const firstError = (error: any) => {
  if (error?.response?.status === 403) return "Administrator permission is required for reports.";
  if (error?.response?.status === 401) return "Your session has expired. Please sign in again.";
  const errors = error?.response?.data?.errors;
  if (errors && typeof errors === "object") {
    const first = Object.values(errors).flat().find((v) => typeof v === "string");
    if (first) return String(first);
  }
  return error?.response?.data?.message || "Unable to load the requested report.";
};

function DataGroup({ name, records }: { name: string; records: unknown[] }) {
  const [expanded, setExpanded] = useState(false);
  const [showAll, setShowAll] = useState(false);
  return <View style={styles.panel}>
    <TouchableOpacity onPress={() => setExpanded(!expanded)} style={styles.groupHeader} accessibilityRole="button">
      <Text style={styles.subtitle}>{label(name)} ({records.length})</Text>
      <Text style={styles.blue}>{expanded ? "Hide" : "View"}</Text>
    </TouchableOpacity>
    {expanded && (records.length === 0 ? <Text style={styles.muted}>No records for the selected period.</Text> : <>
      {records.slice(0, showAll ? 100 : 15).map((item, i) => {
        const fields = objectValue(item);
        return <View key={`${name}-${i}`} style={styles.record}>
          {Object.entries(fields).filter(([, v]) => v !== null && typeof v !== "object").slice(0, 12).map(([k, v]) =>
            <Text key={k} style={styles.recordText}><Text style={styles.recordKey}>{label(k)}: </Text>{display(v)}</Text>)}
        </View>;
      })}
      {records.length > 15 && !showAll && <TouchableOpacity onPress={() => setShowAll(true)} style={styles.buttonOutline}><Text style={styles.blue}>Show more records</Text></TouchableOpacity>}
      {records.length > 100 && showAll && <Text style={styles.muted}>Showing first 100 records. Export the report for the complete data.</Text>}
    </>)}
  </View>;
}

export default function ReportsScreen() {
  const now = new Date();
  const [from, setFrom] = useState(localDate(new Date(now.getFullYear(), now.getMonth(), 1)));
  const [to, setTo] = useState(localDate(now));
  const [index, setIndex] = useState(0);
  const [data, setData] = useState<JsonRecord | null>(null);
  const [loading, setLoading] = useState(false);
  const [exporting, setExporting] = useState<"pdf" | "csv" | null>(null);
  const [error, setError] = useState("");
  const selected = REPORTS[index];
  const checkDates = () => {
    if (!validDate(from) || !validDate(to)) { setError("Enter valid dates in YYYY-MM-DD format."); return false; }
    if (from > to) { setError("Start date must be on or before the end date."); return false; }
    const days = Math.round((Date.parse(`${to}T12:00:00`) - Date.parse(`${from}T12:00:00`)) / 86400000);
    if (days > 365) { setError("Reporting period cannot exceed 366 days."); return false; }
    return true;
  };
  const loadReport = useCallback(async () => {
    if (!checkDates()) return;
    setLoading(true); setError(""); setData(null);
    try {
      const response = await api.get(selected.endpoint, { params: { from, to } });
      if (response.data?.success === false) throw new Error("Report request was unsuccessful.");
      setData(objectValue(response.data?.data));
    } catch (err: any) { setError(firstError(err)); }
    finally { setLoading(false); }
  }, [selected.endpoint, from, to]);
  useEffect(() => { loadReport(); }, [index]); // Refresh when changing report type; date edits require tapping View Report.
  const exportReport = async (format: "pdf" | "csv") => {
    if (!checkDates() || !data || selected.key === "overview") return;
    setExporting(format); setError("");
    try {
      const token = await SecureStore.getItemAsync("token");
      if (!token) throw new Error("Please sign in again to download a report.");
      const baseURL = String(api.defaults.baseURL || "").replace(/\/$/, "");
      const params = new URLSearchParams({ report: selected.key, format, from, to });
      const target = `${baseURL}/reports/export?${params.toString()}`;
      const dir = FileSystem.cacheDirectory;
      if (!dir) throw new Error("Temporary file storage is unavailable.");
      const filename = `smartcare-${selected.key}-${from}-${to}.${format}`;
      const result = await FileSystem.downloadAsync(target, `${dir}${filename}`, {
        headers: { Authorization: `Bearer ${token}`, Accept: format === "pdf" ? "application/pdf" : "text/csv" },
      });
      if (result.status !== 200) throw new Error(`Report export failed (HTTP ${result.status}).`);
      if (!(await Sharing.isAvailableAsync())) throw new Error("File sharing is unavailable on this device.");
      await Sharing.shareAsync(result.uri, { mimeType: format === "pdf" ? "application/pdf" : "text/csv", dialogTitle: `Share ${selected.label} report`, UTI: format === "pdf" ? "com.adobe.pdf" : "public.comma-separated-values-text" });
    } catch (err: any) { const msg = err?.message || `Unable to share the ${format.toUpperCase()} report.`; setError(msg); Alert.alert("Export failed", msg); }
    finally { setExporting(null); }
  };
  const summaries = data ? Object.entries(data).filter(([key, v]) => !Array.isArray(v) && v !== null && typeof v === "object" && (key.includes("summary") || key === "meals")) : [];
  const metrics = data ? Object.entries(data).filter(([, v]) => v === null || ["number", "string", "boolean"].includes(typeof v)) : [];
  const arrays = data ? Object.entries(data).filter(([, v]) => Array.isArray(v)) : [];
  return <ScrollView style={styles.root} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.title}>Management Reports</Text>
    <Text style={styles.muted}>Read-only operational reports. Administrator access required.</Text>
    <Text style={styles.heading}>Choose a report</Text>
    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.choices}>
      {REPORTS.map((r, i) => <TouchableOpacity key={`${r.label}-${i}`} onPress={() => { setIndex(i); setData(null); setError(""); }} style={[styles.chip, index === i && styles.chipActive]}><Text style={[styles.chipText, index === i && styles.chipTextActive]}>{r.label}</Text></TouchableOpacity>)}
    </ScrollView>
    <View style={styles.panel}>
      <Text style={styles.subtitle}>Reporting period</Text>
      <View style={styles.dateRow}>
        <View style={styles.dateField}><Text style={styles.muted}>From (YYYY-MM-DD)</Text><TextInput style={styles.input} value={from} onChangeText={setFrom} maxLength={10} autoCapitalize="none" keyboardType="numbers-and-punctuation" /></View>
        <View style={styles.dateField}><Text style={styles.muted}>To (YYYY-MM-DD)</Text><TextInput style={styles.input} value={to} onChangeText={setTo} maxLength={10} autoCapitalize="none" keyboardType="numbers-and-punctuation" /></View>
      </View>
      <TouchableOpacity style={styles.button} disabled={loading} onPress={loadReport}><Text style={styles.buttonText}>{loading ? "Loading…" : "View Report / Refresh"}</Text></TouchableOpacity>
    </View>
    {Boolean(error) && <View style={styles.error}><Text style={styles.errorText}>{error}</Text></View>}
    {loading && <ActivityIndicator size="large" color="#2563eb" style={{ marginVertical: 22 }} />}
    {!loading && data && <>
      <Text style={styles.heading}>{selected.label}</Text>
      {metrics.length > 0 && <View style={styles.panel}>{metrics.map(([k, v]) => <Text key={k} style={styles.recordText}><Text style={styles.recordKey}>{label(k)}: </Text>{display(v)}</Text>)}</View>}
      {summaries.map(([section, values]) => <View key={section} style={styles.panel}><Text style={styles.subtitle}>{label(section)}</Text>{Object.entries(objectValue(values)).filter(([, v]) => v === null || typeof v !== "object").map(([k, v]) => <View key={k} style={styles.summaryRow}><Text style={styles.summaryLabel}>{label(k)}</Text><Text style={styles.summaryValue}>{display(v)}</Text></View>)}</View>)}
      {arrays.map(([name, rows]) => <DataGroup key={`${selected.label}-${name}`} name={name} records={rows as unknown[]} />)}
      {summaries.length === 0 && arrays.length === 0 && metrics.length === 0 && <Text style={styles.muted}>No report details returned for this period.</Text>}
      {selected.key !== "overview" && <View style={styles.panel}>
        <Text style={styles.subtitle}>Export report</Text><Text style={styles.muted}>Share a PDF or CSV file using the iPhone share menu.</Text>
        <View style={styles.dateRow}><TouchableOpacity disabled={!!exporting} onPress={() => exportReport("pdf")} style={[styles.button, styles.exportButton]}><Text style={styles.buttonText}>{exporting === "pdf" ? "Preparing…" : "Share PDF"}</Text></TouchableOpacity><TouchableOpacity disabled={!!exporting} onPress={() => exportReport("csv")} style={[styles.button, styles.exportButton]}><Text style={styles.buttonText}>{exporting === "csv" ? "Preparing…" : "Share CSV"}</Text></TouchableOpacity></View>
      </View>}
    </>}
  </ScrollView>;
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 50, gap: 12 },
  title: { fontSize: 23, fontWeight: "800", color: "#0f172a" }, heading: { fontSize: 18, fontWeight: "700", color: "#0f172a", marginTop: 8 },
  subtitle: { fontSize: 16, fontWeight: "700", color: "#0f172a", marginBottom: 8 }, muted: { fontSize: 12, color: "#64748b", lineHeight: 18 }, blue: { color: "#2563eb", fontWeight: "700" },
  choices: { flexGrow: 0 }, chip: { borderRadius: 20, paddingVertical: 9, paddingHorizontal: 13, backgroundColor: "#e2e8f0", marginRight: 8 }, chipActive: { backgroundColor: "#2563eb" }, chipText: { color: "#334155", fontWeight: "600" }, chipTextActive: { color: "white" },
  panel: { backgroundColor: "#fff", padding: 15, borderRadius: 14, borderWidth: 1, borderColor: "#e2e8f0" }, dateRow: { flexDirection: "row", gap: 10, marginVertical: 8 }, dateField: { flex: 1 }, input: { marginTop: 5, padding: 10, borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 9, color: "#0f172a", fontSize: 14 },
  button: { borderRadius: 10, backgroundColor: "#2563eb", padding: 13, alignItems: "center", marginTop: 6 }, buttonText: { color: "white", fontWeight: "700" }, exportButton: { flex: 1 },
  error: { backgroundColor: "#fef2f2", padding: 12, borderRadius: 10 }, errorText: { color: "#b91c1c" },
  groupHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" }, record: { borderTopWidth: 1, borderColor: "#e2e8f0", paddingVertical: 10 }, recordText: { color: "#334155", fontSize: 13, lineHeight: 20 }, recordKey: { fontWeight: "700" },
  summaryRow: { flexDirection: "row", justifyContent: "space-between", gap: 10, paddingVertical: 6, borderBottomWidth: 1, borderColor: "#f1f5f9" }, summaryLabel: { flex: 1, color: "#475569", fontSize: 13 }, summaryValue: { flexShrink: 1, fontWeight: "700", color: "#0f172a", textAlign: "right" }, buttonOutline: { borderWidth: 1, borderColor: "#cbd5e1", padding: 10, alignItems: "center", borderRadius: 10 },
});
