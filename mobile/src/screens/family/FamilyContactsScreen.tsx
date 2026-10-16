import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Switch, Text, TextInput, View } from "react-native";
import { addContact, ContactInput, editContact, FamilyContact, familyError, getContacts, getResidents, removeContact, ResidentOption } from "../../api/familyApi";

const blank = (): ContactInput => ({ full_name: "", relationship: "", phone: "", whatsapp_number: "", is_primary: false, is_emergency_contact: false, whatsapp_enabled: true, medication_notifications_enabled: true, care_notifications_enabled: false, notes: "" });
const Button = ({ title, onPress, disabled = false }: { title: string; onPress: () => void; disabled?: boolean }) => <Pressable onPress={onPress} disabled={disabled} style={[s.button, disabled && { opacity: .45 }]}><Text style={s.buttonText}>{title}</Text></Pressable>;
export default function FamilyContactsScreen() {
  const [residents, setResidents] = useState<ResidentOption[]>([]);
  const [residentId, setResidentId] = useState<number | null>(null);
  const [contacts, setContacts] = useState<FamilyContact[]>([]);
  const [form, setForm] = useState<ContactInput>(blank());
  const [editing, setEditing] = useState<number | null>(null);
  const [mode, setMode] = useState<"list" | "form">("list");
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const loadResidents = useCallback(async () => { try { setResidents(await getResidents()); } catch (e) { setError(familyError(e)); } }, []);
  const loadContacts = useCallback(async () => { if (residentId === null) { setContacts([]); return; } setLoading(true); try { setContacts(await getContacts(residentId)); setError(""); } catch (e) { setError(familyError(e)); } finally { setLoading(false); } }, [residentId]);
  useEffect(() => { void loadResidents(); }, [loadResidents]);
  useEffect(() => { void loadContacts(); }, [loadContacts]);
  const save = async () => {
    if (residentId === null) return;
    if (!form.full_name.trim() || !form.relationship.trim() || !form.phone.trim()) { Alert.alert("Required fields", "Enter name, relationship, and phone number."); return; }
    setBusy(true);
    try { const payload = { ...form, full_name: form.full_name.trim(), relationship: form.relationship.trim(), phone: form.phone.trim(), whatsapp_number: form.whatsapp_number?.trim() || null, notes: form.notes?.trim() || null };
      if (editing !== null) await editContact(editing, payload); else await addContact(residentId, payload);
      setMode("list"); setEditing(null); await loadContacts();
    } catch (e) { Alert.alert("Unable to save", familyError(e)); } finally { setBusy(false); }
  };
  const confirmDelete = (contact: FamilyContact) => Alert.alert("Delete family contact?", `Delete ${contact.full_name}?`, [{ text: "Cancel", style: "cancel" }, { text: "Delete", style: "destructive", onPress: async () => { setBusy(true); try { await removeContact(contact.id); await loadContacts(); } catch (e) { Alert.alert("Unable to delete", familyError(e)); } finally { setBusy(false); } } }]);
  const edit = (contact?: FamilyContact) => { setEditing(contact?.id ?? null); setForm(contact ? { full_name: contact.full_name, relationship: contact.relationship, phone: contact.phone, whatsapp_number: contact.whatsapp_number, is_primary: contact.is_primary, is_emergency_contact: contact.is_emergency_contact, whatsapp_enabled: contact.whatsapp_enabled, medication_notifications_enabled: contact.medication_notifications_enabled, care_notifications_enabled: contact.care_notifications_enabled, notes: contact.notes } : blank()); setMode("form"); };
  const update = (key: keyof ContactInput, value: any) => setForm(old => ({ ...old, [key]: value }));
  return <ScrollView style={s.page} contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
    <Text style={s.heading}>Family Contacts</Text><Text style={s.muted}>Select a resident to manage their contacts.</Text>
    {error ? <Text style={s.error}>{error}</Text> : null}
    {mode === "list" ? <>
      <TextInput style={s.input} placeholder="Find resident" value={search} onChangeText={setSearch} />
      <View style={s.residentList}>{residents.filter(r => r.full_name.toLowerCase().includes(search.toLowerCase())).map(r => <Pressable key={r.id} onPress={() => setResidentId(r.id)} style={[s.choice, residentId === r.id && s.selected]}><Text style={s.label}>{r.full_name}</Text></Pressable>)}</View>
      {residentId !== null ? <><View style={s.actions}><Button title="+ Add Contact" onPress={() => edit()} /><Button title="Refresh" onPress={() => void loadContacts()} /></View>
        {loading ? <ActivityIndicator /> : contacts.length === 0 ? <Text style={s.muted}>No contacts recorded for this resident.</Text> : contacts.map(c => <View key={c.id} style={s.card}><Text style={s.cardTitle}>{c.full_name}{c.is_primary ? " · Primary" : ""}</Text><Text>{c.relationship} · {c.phone}</Text><Text style={s.muted}>WhatsApp: {c.whatsapp_number || "Not provided"}</Text><Text style={s.muted}>Emergency: {c.is_emergency_contact ? "Yes" : "No"} · WhatsApp enabled: {c.whatsapp_enabled ? "Yes" : "No"}</Text><Text style={s.muted}>Medication updates: {c.medication_notifications_enabled ? "On" : "Off"} · Care updates: {c.care_notifications_enabled ? "On" : "Off"}</Text><View style={s.actions}><Button title="Edit" onPress={() => edit(c)} /><Button title="Delete" disabled={busy} onPress={() => confirmDelete(c)} /></View></View>)}</> : null}
    </> : <><Text style={s.cardTitle}>{editing === null ? "New contact" : "Edit contact"}</Text>
      {([ ["full_name", "Full name *"], ["relationship", "Relationship *"], ["phone", "Phone *"], ["whatsapp_number", "WhatsApp number"], ["notes", "Notes"] ] as const).map(([key, label]) => <View key={key}><Text style={s.label}>{label}</Text><TextInput style={[s.input, key === "notes" && { minHeight: 70 }]} value={String(form[key] ?? "")} onChangeText={value => update(key, value)} multiline={key === "notes"} autoCapitalize={key === "phone" || key === "whatsapp_number" ? "none" : "sentences"} keyboardType={key === "phone" || key === "whatsapp_number" ? "phone-pad" : "default"} /></View>)}
      {([ ["is_primary", "Primary contact"], ["is_emergency_contact", "Emergency contact"], ["whatsapp_enabled", "Enable WhatsApp"], ["medication_notifications_enabled", "Medication and meal updates"], ["care_notifications_enabled", "Care notifications"] ] as const).map(([key, label]) => <View key={key} style={s.switchRow}><Text style={s.label}>{label}</Text><Switch value={form[key]} onValueChange={value => update(key, value)} /></View>)}
      <Text style={s.muted}>Only one primary contact is allowed per resident. Selecting this option updates the previous primary contact.</Text>
      <View style={s.actions}><Button title="Cancel" onPress={() => setMode("list")} /><Button title={busy ? "Saving..." : "Save Contact"} disabled={busy} onPress={() => void save()} /></View>
    </>}
  </ScrollView>;
}
const s = StyleSheet.create({ page: { flex: 1, backgroundColor: "#f8fafc" }, content: { padding: 16, paddingBottom: 60, gap: 12 }, heading: { fontSize: 25, fontWeight: "800", color: "#0f172a" }, muted: { color: "#64748b" }, label: { fontWeight: "600", color: "#334155" }, input: { backgroundColor: "white", borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 10, padding: 12, color: "#0f172a" }, residentList: { maxHeight: 230, gap: 6 }, choice: { padding: 10, backgroundColor: "white", borderWidth: 1, borderColor: "#cbd5e1", borderRadius: 9 }, selected: { borderColor: "#2563eb", backgroundColor: "#dbeafe" }, actions: { flexDirection: "row", flexWrap: "wrap", gap: 8 }, button: { backgroundColor: "#2563eb", paddingVertical: 10, paddingHorizontal: 14, borderRadius: 9 }, buttonText: { color: "white", fontWeight: "700" }, card: { padding: 14, backgroundColor: "white", borderRadius: 12, borderWidth: 1, borderColor: "#e2e8f0", gap: 7 }, cardTitle: { fontSize: 17, fontWeight: "700", color: "#0f172a" }, switchRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 6 }, error: { color: "#991b1b", backgroundColor: "#fee2e2", padding: 10, borderRadius: 8 } });
