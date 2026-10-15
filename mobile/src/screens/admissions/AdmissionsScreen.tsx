import React, { useCallback, useState } from "react";
import { ActivityIndicator, Alert, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { useFocusEffect, useNavigation, type NavigationProp } from "@react-navigation/native";
import { admissionError, deleteAdmissionDraft, getAdmissionDrafts, getAdmissions, type Admission, type AdmissionDraft } from "../../api/admissionApi";
import type { AppStackParamList } from "../../navigation/AppNavigator";

export default function AdmissionsScreen() {
  const navigation = useNavigation<NavigationProp<AppStackParamList>>();
  const [drafts, setDrafts] = useState<AdmissionDraft[]>([]);
  const [admissions, setAdmissions] = useState<Admission[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [busyDraft, setBusyDraft] = useState<number | null>(null);
  const [error, setError] = useState("");
  const load = useCallback(async () => {
    const results = await Promise.allSettled([getAdmissionDrafts(), getAdmissions()]);
    if (results[0].status === "fulfilled") setDrafts(results[0].value);
    if (results[1].status === "fulfilled") setAdmissions(results[1].value);
    setError(results.some((r) => r.status === "rejected") ? "Some admissions data could not be loaded. Pull down to retry." : "");
    setLoading(false); setRefreshing(false);
  }, []);
  useFocusEffect(useCallback(() => { setLoading(true); void load(); }, [load]));
  const discard = (draft: AdmissionDraft) => Alert.alert("Discard Draft", `Permanently discard ${draft.full_name || draft.draft_reference || "this draft"}?`, [
    { text: "Cancel", style: "cancel" },
    { text: "Discard", style: "destructive", onPress: async () => { setBusyDraft(draft.id); try { await deleteAdmissionDraft(draft.id); await load(); } catch (e) { Alert.alert("Unable to discard", admissionError(e)); } finally { setBusyDraft(null); } } },
  ]);
  if (loading) return <View style={s.center}><ActivityIndicator size="large" color="#2563eb" /><Text style={s.hint}>Loading admissions...</Text></View>;
  return <ScrollView style={s.container} contentContainerStyle={s.content} refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} />}>
    <Text style={s.heading}>Admissions</Text><Text style={s.hint}>Create admissions and continue drafts from web or mobile.</Text>
    {error ? <Text style={s.error}>{error}</Text> : null}
    <TouchableOpacity style={s.primary} onPress={() => navigation.navigate("AdmissionWizard", undefined)}><Text style={s.primaryText}>+ New Admission</Text></TouchableOpacity>
    <Text style={s.section}>In Progress Admissions ({drafts.length})</Text>
    {drafts.length === 0 && <Text style={s.empty}>No saved admission drafts.</Text>}
    {drafts.map((d) => <View key={`draft-${d.id}`} style={s.card}>
      <Text style={s.cardTitle}>{d.full_name || "Unnamed Resident"}</Text>
      <Text style={s.ref}>{d.draft_reference}</Text>
      <Text style={s.hint}>Step {d.current_step || 1} of 7 · Draft</Text>
      <Text style={s.hint}>Updated {d.updated_at ? new Date(d.updated_at).toLocaleString() : "—"}</Text>
      <View style={s.row}><TouchableOpacity style={s.secondary} disabled={busyDraft !== null} onPress={() => navigation.navigate("AdmissionWizard", { draftId: d.id })}><Text style={s.secondaryText}>Resume</Text></TouchableOpacity><TouchableOpacity style={s.danger} disabled={busyDraft !== null} onPress={() => discard(d)}><Text style={s.dangerText}>{busyDraft === d.id ? "Discarding..." : "Discard"}</Text></TouchableOpacity></View>
    </View>)}
    <Text style={s.section}>Recent Admissions ({admissions.length})</Text>
    {admissions.length === 0 && <Text style={s.empty}>No admissions found.</Text>}
    {admissions.map((a) => <View key={`admission-${a.id}`} style={s.card}>
      <Text style={s.cardTitle}>{a.resident?.full_name || `Resident #${a.resident_id}`}</Text>
      <Text style={s.ref}>{a.admission_reference || a.admission_number || `Admission #${a.id}`}</Text>
      <Text style={s.hint}>{a.admission_type || "Admission"} · {a.admitted_at ? new Date(a.admitted_at).toLocaleDateString() : "—"}</Text>
      <Text style={a.status === "COMPLETED" ? s.completed : s.pending}>{a.status}</Text>
    </View>)}
  </ScrollView>;
}
const s = StyleSheet.create({ container: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 40 }, center: { flex: 1, alignItems: "center", justifyContent: "center" }, heading: { fontSize: 26, fontWeight: "800", color: "#0f172a" }, hint: { color: "#64748b", marginTop: 6, lineHeight: 20 }, primary: { backgroundColor: "#2563eb", borderRadius: 12, padding: 15, alignItems: "center", marginVertical: 20 }, primaryText: { color: "white", fontWeight: "700", fontSize: 16 }, section: { fontSize: 19, fontWeight: "700", color: "#0f172a", marginTop: 14, marginBottom: 12 }, card: { backgroundColor: "white", borderColor: "#e2e8f0", borderWidth: 1, borderRadius: 14, padding: 16, marginBottom: 12 }, cardTitle: { fontSize: 16, fontWeight: "700", color: "#0f172a" }, ref: { color: "#2563eb", fontWeight: "600", marginTop: 5 }, row: { flexDirection: "row", gap: 10, marginTop: 14 }, secondary: { flex: 1, alignItems: "center", padding: 12, backgroundColor: "#eff6ff", borderRadius: 10 }, secondaryText: { color: "#1d4ed8", fontWeight: "700" }, danger: { flex: 1, alignItems: "center", padding: 12, backgroundColor: "#fef2f2", borderRadius: 10 }, dangerText: { color: "#b91c1c", fontWeight: "700" }, completed: { color: "#15803d", fontWeight: "700", marginTop: 10 }, pending: { color: "#b45309", fontWeight: "700", marginTop: 10 }, empty: { color: "#94a3b8", marginBottom: 12 }, error: { color: "#b91c1c", padding: 10, backgroundColor: "#fef2f2", borderRadius: 10 } });
