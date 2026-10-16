import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import api from "../../api/api";
import { useAuth } from "../../context/AuthContext";

type Staff = { id: number; full_name: string; email: string; phone: string | null; role_id: number; role: string; status: "Active" | "Inactive" };
type FormMode = "create" | "edit" | "reset" | null;
type Form = { full_name: string; email: string; phone: string; role_id: number; password: string; password_confirmation: string };
const ROLES = [{ id: 1, name: "Administrator" }, { id: 2, name: "Manager" }, { id: 3, name: "Doctor" }, { id: 4, name: "Nurse" }];
const emptyForm = (): Form => ({ full_name: "", email: "", phone: "", role_id: 4, password: "", password_confirmation: "" });
const messageOf = (error: unknown): string => {
  const e = error as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } };
  if (e?.response?.status === 403) return "Administrator access is required for staff management.";
  const first = e?.response?.data?.errors && Object.values(e.response.data.errors).flat().find(Boolean);
  return first || e?.response?.data?.message || "Unable to complete this request.";
};
function Button({ label, onPress, disabled = false, danger = false }: { label: string; onPress: () => void; disabled?: boolean; danger?: boolean }) {
  return <Pressable disabled={disabled} onPress={onPress} style={[styles.button, danger && styles.danger, disabled && styles.disabled]}><Text style={styles.buttonText}>{label}</Text></Pressable>;
}
function Input({ label, value, onChangeText, secureTextEntry = false, keyboardType = "default", autoCapitalize = "sentences" }: { label: string; value: string; onChangeText: (s: string) => void; secureTextEntry?: boolean; keyboardType?: "default" | "email-address" | "phone-pad"; autoCapitalize?: "none" | "sentences" }) {
  return <View style={styles.field}><Text style={styles.fieldLabel}>{label}</Text><TextInput style={styles.input} value={value} onChangeText={onChangeText} secureTextEntry={secureTextEntry} keyboardType={keyboardType} autoCapitalize={autoCapitalize} autoCorrect={false} /></View>;
}
export default function StaffAdministrationScreen() {
  const { user } = useAuth();
  const [staff, setStaff] = useState<Staff[]>([]);
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<"All" | "Active" | "Inactive">("All");
  const [mode, setMode] = useState<FormMode>(null);
  const [selected, setSelected] = useState<Staff | null>(null);
  const [form, setForm] = useState<Form>(emptyForm);
  const administrator = user?.role === "Administrator";
  const load = useCallback(async () => {
    if (!administrator) return;
    setLoading(true); setError("");
    try { const response = await api.get<{ staff: Staff[] }>("/staff"); setStaff(Array.isArray(response.data?.staff) ? response.data.staff : []); }
    catch (e) { setError(messageOf(e)); }
    finally { setLoading(false); }
  }, [administrator]);
  useEffect(() => { void load(); }, [load]);
  const filtered = useMemo(() => staff.filter(s => (filter === "All" || s.status === filter) && `${s.full_name} ${s.email} ${s.role}`.toLowerCase().includes(search.toLowerCase().trim())), [staff, filter, search]);
  const active = staff.filter(s => s.status === "Active").length;
  const resetUi = () => { setMode(null); setSelected(null); setForm(emptyForm()); };
  const patch = (field: keyof Form, value: string | number) => setForm(current => ({ ...current, [field]: value }));
  const begin = (next: Exclude<FormMode, null>, member: Staff | null = null) => {
    setError(""); setNotice(""); setSelected(member); setMode(next);
    setForm(member && next === "edit" ? { full_name: member.full_name, email: member.email, phone: member.phone || "", role_id: member.role_id, password: "", password_confirmation: "" } : emptyForm());
  };
  const submit = async () => {
    if (busy || !mode) return;
    if ((mode === "create" || mode === "reset") && (form.password.length < 12 || form.password !== form.password_confirmation)) { setError("Password must be at least 12 characters and both password fields must match."); return; }
    if (mode !== "reset" && (!form.full_name.trim() || !form.email.trim())) { setError("Name and email are required."); return; }
    setBusy(true); setError(""); setNotice("");
    try {
      if (mode === "create") { await api.post("/staff", { full_name: form.full_name.trim(), email: form.email.trim(), phone: form.phone.trim() || null, role_id: form.role_id, password: form.password, password_confirmation: form.password_confirmation }); }
      else if (mode === "edit" && selected) { await api.put(`/staff/${selected.id}`, { full_name: form.full_name.trim(), email: form.email.trim(), phone: form.phone.trim() || null, role_id: form.role_id }); }
      else if (mode === "reset" && selected) { await api.put(`/staff/${selected.id}/reset-password`, { password: form.password, password_confirmation: form.password_confirmation }); }
      setNotice(mode === "create" ? "Staff account created." : mode === "edit" ? "Staff details updated." : "Password reset. Existing sessions were revoked.");
      resetUi(); await load();
    } catch (e) { setError(messageOf(e)); }
    finally { setBusy(false); }
  };
  const statusChange = (member: Staff) => {
    const next = member.status === "Active" ? "Inactive" : "Active";
    if (member.id === user?.id && next === "Inactive") { setError("You cannot deactivate your own account."); return; }
    Alert.alert(`${next === "Inactive" ? "Deactivate" : "Activate"} staff account?`, `${member.full_name}${next === "Inactive" ? " will be signed out of existing sessions." : " will be able to sign in."}`, [
      { text: "Cancel", style: "cancel" },
      { text: "Confirm", style: next === "Inactive" ? "destructive" : "default", onPress: async () => {
        if (busy) return;
        setBusy(true); setError(""); setNotice("");
        try { await api.put(`/staff/${member.id}/status`, { status: next }); setNotice(`${member.full_name} is now ${next.toLowerCase()}.`); await load(); }
        catch (e) { setError(messageOf(e)); }
        finally { setBusy(false); }
      } },
    ]);
  };
  if (!administrator) return <SafeAreaView style={styles.root}><Text style={styles.warning}>Administrator access is required for staff management.</Text></SafeAreaView>;
  return <SafeAreaView style={styles.root} edges={["bottom"]}><ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.title}>Staff Administration</Text><Text style={styles.muted}>Manage staff accounts and access. Family accounts are handled separately.</Text>
    {!!error && <Text style={styles.error}>{error}</Text>}{!!notice && <Text style={styles.notice}>{notice}</Text>}
    <View style={styles.row}><Text style={styles.summary}>Total {staff.length}  ·  Active {active}  ·  Inactive {staff.length - active}</Text></View>
    <View style={styles.row}><Button label="+ Add Staff" onPress={() => begin("create")} disabled={busy} /><Button label="Refresh" onPress={() => { void load(); }} disabled={busy || loading} /></View>
    <Input label="Find staff" value={search} onChangeText={setSearch} />
    <View style={styles.row}>{(["All", "Active", "Inactive"] as const).map(s => <Pressable key={s} onPress={() => setFilter(s)} style={[styles.chip, filter === s && styles.chipActive]}><Text style={filter === s ? styles.chipActiveText : styles.chipText}>{s}</Text></Pressable>)}</View>
    {loading && <ActivityIndicator size="large" color="#2563eb" />}
    {!loading && filtered.length === 0 && <Text style={styles.muted}>No staff accounts match your search.</Text>}
    {filtered.map(member => <View key={member.id} style={styles.card}>
      <Text style={styles.staffName}>{member.full_name}</Text><Text style={styles.muted}>{member.role} · {member.status}</Text>
      <Text style={styles.muted}>{member.email}</Text>{!!member.phone && <Text style={styles.muted}>{member.phone}</Text>}
      <View style={styles.row}><Button label="Edit" onPress={() => begin("edit", member)} disabled={busy} /><Button label="Reset Password" onPress={() => begin("reset", member)} disabled={busy} /></View>
      <Button label={member.status === "Active" ? "Deactivate Account" : "Reactivate Account"} onPress={() => statusChange(member)} disabled={busy || (member.id === user?.id && member.status === "Active")} danger={member.status === "Active"} />
    </View>)}
    {!!mode && <View style={styles.editor}>
      <Text style={styles.staffName}>{mode === "create" ? "Add Staff" : mode === "edit" ? `Edit ${selected?.full_name}` : `Reset Password: ${selected?.full_name}`}</Text>
      {mode !== "reset" && <>
        <Input label="Full name" value={form.full_name} onChangeText={s => patch("full_name", s)} />
        <Input label="Email" value={form.email} onChangeText={s => patch("email", s)} keyboardType="email-address" autoCapitalize="none" />
        <Input label="Phone (optional)" value={form.phone} onChangeText={s => patch("phone", s)} keyboardType="phone-pad" />
        <Text style={styles.fieldLabel}>Role</Text><View style={styles.row}>{ROLES.map(role => <Pressable key={role.id} disabled={mode === "edit" && selected?.id === user?.id && role.name !== "Administrator"} onPress={() => patch("role_id", role.id)} style={[styles.chip, form.role_id === role.id && styles.chipActive]}><Text style={form.role_id === role.id ? styles.chipActiveText : styles.chipText}>{role.name}</Text></Pressable>)}</View>
      </>}
      {mode !== "edit" && <><Input label="Password (minimum 12 characters)" value={form.password} onChangeText={s => patch("password", s)} secureTextEntry /><Input label="Confirm password" value={form.password_confirmation} onChangeText={s => patch("password_confirmation", s)} secureTextEntry /></>}
      <View style={styles.row}><Button label={busy ? "Saving..." : "Save"} disabled={busy} onPress={() => { Alert.alert("Confirm staff change", mode === "reset" ? "This will revoke the staff member's existing sessions." : "Save these staff account changes?", [{ text: "Cancel", style: "cancel" }, { text: "Confirm", onPress: () => { void submit(); } }]); }} /><Button label="Cancel" disabled={busy} onPress={resetUi} /></View>
    </View>}
  </ScrollView></SafeAreaView>;
}
const styles = StyleSheet.create({ root: { flex: 1, backgroundColor: "#f1f5f9" }, content: { padding: 16, paddingBottom: 36, gap: 12 }, title: { color: "#0f172a", fontSize: 26, fontWeight: "700" }, muted: { color: "#64748b", fontSize: 13, lineHeight: 20 }, warning: { margin: 20, color: "#b91c1c" }, error: { padding: 12, backgroundColor: "#fee2e2", color: "#991b1b", borderRadius: 9 }, notice: { padding: 12, backgroundColor: "#dcfce7", color: "#166534", borderRadius: 9 }, row: { flexDirection: "row", flexWrap: "wrap", alignItems: "center", gap: 8 }, summary: { fontSize: 14, fontWeight: "600", color: "#334155" }, button: { backgroundColor: "#2563eb", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 11, marginVertical: 2 }, danger: { backgroundColor: "#b91c1c" }, disabled: { opacity: 0.4 }, buttonText: { color: "white", fontWeight: "700", fontSize: 13 }, field: { gap: 5 }, fieldLabel: { color: "#334155", fontSize: 13, fontWeight: "600" }, input: { borderWidth: 1, borderColor: "#cbd5e1", backgroundColor: "white", color: "#0f172a", paddingHorizontal: 12, paddingVertical: 10, borderRadius: 10, fontSize: 15 }, chip: { borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 10, paddingHorizontal: 10, paddingVertical: 9, backgroundColor: "white" }, chipActive: { backgroundColor: "#dbeafe", borderColor: "#2563eb" }, chipText: { color: "#334155", fontSize: 12 }, chipActiveText: { color: "#1d4ed8", fontWeight: "700", fontSize: 12 }, card: { backgroundColor: "white", borderRadius: 14, padding: 14, gap: 9, borderWidth: 1, borderColor: "#e2e8f0" }, staffName: { fontSize: 17, fontWeight: "700", color: "#0f172a" }, editor: { marginTop: 12, padding: 15, borderRadius: 14, backgroundColor: "#eff6ff", borderWidth: 1, borderColor: "#bfdbfe", gap: 12 } });
