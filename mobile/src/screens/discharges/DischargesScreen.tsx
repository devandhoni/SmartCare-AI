import React, { useCallback, useState } from "react";
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { useFocusEffect, useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { AppStackParamList } from "../../navigation/AppNavigator";
import { apiError, deleteDischarge, getDischarges, type Discharge } from "../../api/dischargeApi";

const colors = { ink: "#0f172a", muted: "#64748b", blue: "#2563eb", red: "#b91c1c" };
export default function DischargesScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParamList>>();
  const [records, setRecords] = useState<Discharge[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const load = useCallback(async () => {
    setLoading(true);
    try { setRecords(await getDischarges()); setError(""); }
    catch (e) { setError(apiError(e, "Unable to load discharges.")); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { void load(); }, [load]));
  const drafts = records.filter(x => x.status === "DRAFT");
  const completed = records.filter(x => x.status === "COMPLETED");
  const discard = (d: Discharge) => Alert.alert("Discard draft?", `Delete ${d.discharge_number} for ${d.resident?.full_name ?? "this resident"}?`, [
    { text: "Cancel", style: "cancel" },
    { text: "Discard", style: "destructive", onPress: async () => {
      if (busy) return;
      setBusy(true);
      try { await deleteDischarge(d.id); await load(); }
      catch (e) { Alert.alert("Unable to discard", apiError(e, "Please try again.")); }
      finally { setBusy(false); }
    } },
  ]);
  const card = (d: Discharge, isDraft: boolean) => (
    <View key={d.id} style={styles.card}>
      <Text style={styles.name}>{d.resident?.full_name ?? `Resident #${d.resident_id}`}</Text>
      <Text style={styles.sub}>{d.discharge_number}</Text>
      <Text style={styles.sub}>{isDraft ? "Draft" : "Completed"}{d.discharged_at ? ` · ${String(d.discharged_at).slice(0, 16).replace("T", " ")}` : ""}</Text>
      <View style={styles.actions}>
        <TouchableOpacity style={styles.button} onPress={() => navigation.navigate("DischargeWizard", { dischargeId: d.id })}>
          <Text style={styles.buttonText}>{isDraft ? "Resume" : "View Summary"}</Text>
        </TouchableOpacity>
        {isDraft && <TouchableOpacity disabled={busy} style={styles.outline} onPress={() => discard(d)}><Text style={styles.danger}>Discard</Text></TouchableOpacity>}
        {d.resident?.id && <TouchableOpacity style={styles.outline} onPress={() => navigation.navigate("ResidentProfile", { residentId: d.resident!.id })}><Text style={styles.outlineText}>Resident</Text></TouchableOpacity>}
      </View>
    </View>
  );
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} refreshControl={undefined}>
    <Text style={styles.title}>Resident Discharges</Text>
    <Text style={styles.sub}>Prepare a discharge without changing the resident's status until completion.</Text>
    <TouchableOpacity style={[styles.button, { marginTop: 18 }]} onPress={() => navigation.navigate("DischargeWizard")}><Text style={styles.buttonText}>+ Prepare Discharge</Text></TouchableOpacity>
    {error ? <Text style={styles.error}>{error}</Text> : null}
    {loading ? <ActivityIndicator style={{ marginTop: 28 }} color={colors.blue} /> : <>
      <Text style={styles.heading}>In Progress ({drafts.length})</Text>
      {drafts.length ? drafts.map(d => card(d, true)) : <Text style={styles.sub}>No discharge drafts.</Text>}
      <Text style={styles.heading}>Completed ({completed.length})</Text>
      {completed.length ? completed.map(d => card(d, false)) : <Text style={styles.sub}>No completed discharges.</Text>}
      <TouchableOpacity style={styles.outline} onPress={() => void load()}><Text style={styles.outlineText}>Refresh</Text></TouchableOpacity>
    </>}
  </ScrollView>;
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 44 }, title: { fontSize: 25, fontWeight: "800", color: colors.ink },
  heading: { fontSize: 19, fontWeight: "800", color: colors.ink, marginTop: 24, marginBottom: 12 },
  card: { backgroundColor: "white", borderRadius: 14, borderColor: "#e2e8f0", borderWidth: 1, padding: 15, marginBottom: 10 },
  name: { fontSize: 16, fontWeight: "700", color: colors.ink }, sub: { color: colors.muted, marginTop: 5 },
  actions: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginTop: 14 },
  button: { backgroundColor: colors.blue, borderRadius: 10, paddingVertical: 12, paddingHorizontal: 16, alignItems: "center" },
  buttonText: { color: "white", fontWeight: "700" }, outline: { borderColor: "#cbd5e1", borderWidth: 1, borderRadius: 10, paddingVertical: 11, paddingHorizontal: 15, alignItems: "center", marginTop: 2 },
  outlineText: { color: colors.blue, fontWeight: "700" }, danger: { color: colors.red, fontWeight: "700" }, error: { color: colors.red, marginTop: 12 },
});
