import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { createCareRecord, deleteCareRecord, getCareRecord, getResidentCareRecords, updateCareRecord } from "../../api/careRecordApi";
import { getResidents } from "../../api/residentApi";
import type { CareRecord, CareRecordStatus } from "../../types/careRecord";
import type { Resident } from "../../types/resident";

const STATUSES: CareRecordStatus[] = ["COMPLETED", "OBSERVED", "NEEDS_ATTENTION"];

function getResidentName(resident: Resident): string {
  return resident.full_name || `Resident #${resident.id}`;
}

function formatDate(value?: string | null): string {
  if (!value) return "Not recorded";
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleString("en-MY", { dateStyle: "medium", timeStyle: "short" });
}

function errorMessage(error: unknown): string {
  if (error && typeof error === "object") {
    const value = error as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } };
      message?: string;
    };
    const validation = value.response?.data?.errors;
    const firstValidation = validation ? Object.values(validation).flat()[0] : undefined;
    return firstValidation || value.response?.data?.message || value.message || "Request failed.";
  }
  return "Request failed.";
}

function CareRecordCard({ record, onEdit, onDelete, disabled }: { record: CareRecord; onEdit: (record: CareRecord) => void; onDelete: (record: CareRecord) => void; disabled: boolean }) {
  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>{record.title}</Text>
      <Text style={styles.careType}>{record.care_type}</Text>
      <View style={styles.detailRow}>
        <Text style={styles.detailLabel}>Status</Text>
        <Text style={styles.status}>{record.care_status}</Text>
      </View>
      <View style={styles.detailRow}>
        <Text style={styles.detailLabel}>Recorded</Text>
        <Text style={styles.detailValue}>{formatDate(record.recorded_at)}</Text>
      </View>
      <View style={styles.detailRow}>
        <Text style={styles.detailLabel}>Recorded By</Text>
        <Text style={styles.detailValue}>{record.recorder?.full_name || "Not available"}</Text>
      </View>
      {typeof record.notes === "string" && record.notes.trim().length > 0 ? (
        <Text style={styles.notes}>{record.notes}</Text>
      ) : null}
      <Pressable style={[styles.editButton, disabled && styles.disabledButton]} disabled={disabled} onPress={() => onEdit(record)}>
        <Text style={styles.editButtonText}>Edit Record</Text>
      </Pressable>
      <Pressable
        style={[styles.deleteButton, disabled && styles.disabledButton]}
        disabled={disabled}
        onPress={() => onDelete(record)}
      >
        <Text style={styles.deleteButtonText}>Delete Record</Text>
      </Pressable>
    </View>
  );
}

export default function CareRecordsScreen() {
  const [residents, setResidents] = useState<Resident[]>([]);
  const [selectedResidentId, setSelectedResidentId] = useState<number | null>(null);
  const selectedResidentIdRef = useRef<number | null>(null);
  const requestSequence = useRef(0);
  const [records, setRecords] = useState<CareRecord[]>([]);
  const [residentSearch, setResidentSearch] = useState("");
  const [recordSearch, setRecordSearch] = useState("");
  const [showResidents, setShowResidents] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingRecords, setLoadingRecords] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");
  const [showCreate, setShowCreate] = useState(false);
  const [editingRecordId, setEditingRecordId] = useState<number | null>(null);
  const [loadingEdit, setLoadingEdit] = useState(false);
  const editRequestSequence = useRef(0);
  const [careType, setCareType] = useState("");
  const [title, setTitle] = useState("");
  const [notes, setNotes] = useState("");
  const [careStatus, setCareStatus] = useState<CareRecordStatus>("COMPLETED");
  const [saving, setSaving] = useState(false);
  const savingRef = useRef(false);

  const loadResidents = useCallback(async () => {
    try {
      const result = await getResidents();
      setResidents(result);
      setError("");
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  const loadRecords = useCallback(async (residentId: number) => {
    const sequence = ++requestSequence.current;
    setLoadingRecords(true);
    try {
      const result = await getResidentCareRecords(residentId);
      if (sequence !== requestSequence.current || selectedResidentIdRef.current !== residentId) return;
      setRecords(Array.isArray(result.care_records) ? result.care_records : []);
      setError("");
    } catch (err) {
      if (sequence !== requestSequence.current || selectedResidentIdRef.current !== residentId) return;
      setRecords([]);
      setError(errorMessage(err));
    } finally {
      if (sequence === requestSequence.current) setLoadingRecords(false);
    }
  }, []);

  useEffect(() => {
    void loadResidents();
  }, [loadResidents]);

  const selectedResident = residents.find((resident) => resident.id === selectedResidentId);
  const filteredResidents = useMemo(() => {
    const query = residentSearch.trim().toLowerCase();
    return query
      ? residents.filter((resident) => getResidentName(resident).toLowerCase().includes(query))
      : residents;
  }, [residents, residentSearch]);

  const filteredRecords = useMemo(() => {
    const query = recordSearch.trim().toLowerCase();
    if (!query) return records;
    return records.filter((record) =>
      [record.title, record.care_type, record.care_status, record.notes, record.recorder?.full_name]
        .filter((value): value is string => typeof value === "string")
        .join(" ")
        .toLowerCase()
        .includes(query)
    );
  }, [records, recordSearch]);

  const resetForm = () => {
    setCareType("");
    setTitle("");
    setNotes("");
    setCareStatus("COMPLETED");
  };

  const selectResident = (resident: Resident) => {
    if (savingRef.current) return;
    selectedResidentIdRef.current = resident.id;
    setSelectedResidentId(resident.id);
    setRecords([]);
    setRecordSearch("");
    setShowResidents(false);
    ++editRequestSequence.current;
    setLoadingEdit(false);
    setEditingRecordId(null);
    setShowCreate(false);
    resetForm();
    setError("");
    void loadRecords(resident.id);
  };

  const refresh = useCallback(async () => {
    if (savingRef.current) return;
    setRefreshing(true);
    try {
      await loadResidents();
      if (selectedResidentId !== null) await loadRecords(selectedResidentId);
    } finally {
      setRefreshing(false);
    }
  }, [loadResidents, loadRecords, selectedResidentId]);

  const cancelForm = () => {
    ++editRequestSequence.current;
    setLoadingEdit(false);
    setEditingRecordId(null);
    setShowCreate(false);
    resetForm();
  };

  const startEdit = async (record: CareRecord) => {
    if (savingRef.current || loadingEdit || selectedResidentIdRef.current !== record.resident_id) return;
    const sequence = ++editRequestSequence.current;
    setLoadingEdit(true);
    try {
      // Fetch current server values instead of editing potentially stale list data.
      const current = await getCareRecord(record.id);
      if (sequence !== editRequestSequence.current || selectedResidentIdRef.current !== current.resident_id) return;
      setEditingRecordId(current.id);
      setCareType(current.care_type);
      setTitle(current.title);
      setNotes(current.notes ?? "");
      setCareStatus(current.care_status);
      setShowCreate(true);
    } catch (err) {
      if (sequence === editRequestSequence.current) Alert.alert("Unable to open record", errorMessage(err));
    } finally {
      if (sequence === editRequestSequence.current) setLoadingEdit(false);
    }
  };

  const saveRecord = async (residentId: number, recordId: number | null) => {
    if (savingRef.current || selectedResidentIdRef.current !== residentId) return;
    savingRef.current = true;
    setSaving(true);
    try {
      const input = {
        care_type: careType.trim(),
        title: title.trim(),
        notes: notes.trim() || null,
        care_status: careStatus,
      };
      if (recordId !== null) {
        await updateCareRecord(recordId, input);
      } else {
        await createCareRecord(residentId, input);
      }
      cancelForm();
      await loadRecords(residentId);
      Alert.alert("Saved", recordId !== null ? "The care record was updated successfully." : "The care record was created successfully.");
    } catch (err) {
      Alert.alert("Unable to save", errorMessage(err));
    } finally {
      savingRef.current = false;
      setSaving(false);
    }
  };

  const confirmSave = () => {
    if (savingRef.current || loadingEdit || selectedResidentId === null) return;
    if (!careType.trim() || !title.trim()) {
      Alert.alert("Required fields", "Enter a care type and title before saving.");
      return;
    }
    if (careType.trim().length > 100 || title.trim().length > 255) {
      Alert.alert("Text too long", "Care Type must be at most 100 characters and Title at most 255 characters.");
      return;
    }
    const residentId = selectedResidentId;
    const recordId = editingRecordId;
    Alert.alert(
      recordId !== null ? "Confirm care record update" : "Confirm care record",
      `${recordId !== null ? "Update" : "Create"} a ${careStatus.replace(/_/g, " ").toLowerCase()} care record for ${selectedResident ? getResidentName(selectedResident) : `Resident #${residentId}`}?`,
      [
        { text: "Cancel", style: "cancel" },
        { text: recordId !== null ? "Update Record" : "Save Record", onPress: () => void saveRecord(residentId, recordId) },
      ]
    );
  };

  const deleteRecord = async (residentId: number, recordId: number) => {
    if (savingRef.current || selectedResidentIdRef.current !== residentId) return;
    savingRef.current = true;
    setSaving(true);
    try {
      await deleteCareRecord(recordId);
      await loadRecords(residentId);
      Alert.alert("Deleted", "The care record was deleted successfully.");
    } catch (err) {
      Alert.alert("Unable to delete", errorMessage(err));
    } finally {
      savingRef.current = false;
      setSaving(false);
    }
  };

  const confirmDelete = (record: CareRecord) => {
    if (savingRef.current || loadingEdit || loadingRecords || refreshing || showCreate) return;
    if (selectedResidentIdRef.current !== record.resident_id) return;
    const residentId = record.resident_id;
    const recordId = record.id;
    Alert.alert(
      "Delete care record?",
      `Delete "${record.title}" (record #${recordId}) for ${selectedResident ? getResidentName(selectedResident) : `Resident #${residentId}`}? This cannot be undone.`,
      [
        { text: "Cancel", style: "cancel" },
        { text: "Delete Record", style: "destructive", onPress: () => void deleteRecord(residentId, recordId) },
      ]
    );
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.center}>
          <ActivityIndicator size="large" />
          <Text style={styles.muted}>Loading residents...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.container}>
        <Text style={styles.heading}>Care Records</Text>
        <Text style={styles.subtitle}>Review resident care history and nursing observations.</Text>

        {error ? (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{error}</Text>
            <Pressable onPress={() => void refresh()} disabled={saving}>
              <Text style={styles.retryText}>Retry</Text>
            </Pressable>
          </View>
        ) : null}

        <Pressable
          style={styles.residentSelector}
          onPress={() => !saving && setShowResidents((value) => !value)}
          disabled={saving}
        >
          <Text style={styles.selectorText}>
            {selectedResident ? getResidentName(selectedResident) : "Select a resident"}
          </Text>
          <Text style={styles.selectorArrow}>{showResidents ? "▲" : "▼"}</Text>
        </Pressable>

        {showResidents ? (
          <View style={styles.residentPanel}>
            <TextInput
              value={residentSearch}
              onChangeText={setResidentSearch}
              placeholder="Search residents"
              style={styles.searchInput}
            />
            <FlatList
              data={filteredResidents}
              keyExtractor={(item) => String(item.id)}
              keyboardShouldPersistTaps="handled"
              style={styles.residentList}
              renderItem={({ item }) => (
                <Pressable style={styles.residentOption} onPress={() => selectResident(item)}>
                  <Text style={styles.residentOptionText}>{getResidentName(item)}</Text>
                </Pressable>
              )}
              ListEmptyComponent={<Text style={styles.emptyText}>No residents found.</Text>}
            />
          </View>
        ) : null}

        {selectedResidentId !== null ? (
          <>
            <Pressable
              style={[styles.createButton, saving && styles.disabledButton]}
              disabled={saving || loadingEdit}
              onPress={() => {
                if (showCreate) cancelForm();
                else { resetForm(); setEditingRecordId(null); setShowCreate(true); }
              }}
            >
              <Text style={styles.createButtonText}>
                {showCreate ? "Close Record Form" : "+ Create Care Record"}
              </Text>
            </Pressable>

            {showCreate ? (
              <ScrollView
                style={styles.formScroll}
                contentContainerStyle={styles.formContent}
                keyboardShouldPersistTaps="handled"
              >
                <Text style={styles.formHeading}>{editingRecordId !== null ? `Edit Care Record #${editingRecordId}` : "New Care Record"}</Text>
                <Text style={styles.formHint}>For {selectedResident ? getResidentName(selectedResident) : `Resident #${selectedResidentId}`}</Text>
                <Text style={styles.fieldLabel}>Care Type *</Text>
                <TextInput
                  value={careType}
                  onChangeText={setCareType}
                  placeholder="e.g. Personal Care"
                  maxLength={100}
                  editable={!saving}
                  style={styles.searchInput}
                />
                <Text style={styles.fieldLabel}>Title *</Text>
                <TextInput
                  value={title}
                  onChangeText={setTitle}
                  placeholder="Care activity title"
                  maxLength={255}
                  editable={!saving}
                  style={styles.searchInput}
                />
                <Text style={styles.fieldLabel}>Care Status *</Text>
                <View style={styles.statusOptions}>
                  {STATUSES.map((status) => (
                    <Pressable
                      key={status}
                      disabled={saving}
                      onPress={() => setCareStatus(status)}
                      style={[styles.statusOption, careStatus === status && styles.statusOptionSelected]}
                    >
                      <Text style={[styles.statusOptionText, careStatus === status && styles.statusOptionTextSelected]}>
                        {status.replace(/_/g, " ")}
                      </Text>
                    </Pressable>
                  ))}
                </View>
                <Text style={styles.fieldLabel}>Notes (optional)</Text>
                <TextInput
                  value={notes}
                  onChangeText={setNotes}
                  placeholder="Nursing observations or additional details"
                  multiline
                  textAlignVertical="top"
                  editable={!saving}
                  style={[styles.searchInput, styles.notesInput]}
                />
                <Text style={styles.formHint}>{editingRecordId !== null ? "Original recorded time is preserved." : "Recorded time will be set by the server."}</Text>
                <Pressable
                  style={[styles.saveButton, saving && styles.disabledButton]}
                  onPress={confirmSave}
                  disabled={saving}
                >
                  {saving ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.saveButtonText}>{editingRecordId !== null ? "Review and Update" : "Review and Save"}</Text>}
                </Pressable>
                <Pressable
                  style={styles.cancelButton}
                  disabled={saving}
                  onPress={cancelForm}
                >
                  <Text style={styles.cancelButtonText}>Cancel</Text>
                </Pressable>
              </ScrollView>
            ) : (
              <>
                <TextInput
                  value={recordSearch}
                  onChangeText={setRecordSearch}
                  placeholder="Search care records"
                  style={styles.searchInput}
                />
                <Text style={styles.count}>
                  {filteredRecords.length} care record{filteredRecords.length === 1 ? "" : "s"}
                  {loadingEdit ? "  · Opening record..." : ""}
                </Text>
                {loadingRecords ? (
                  <ActivityIndicator style={styles.loadingIndicator} />
                ) : (
                  <FlatList
                    data={filteredRecords}
                    keyExtractor={(item) => String(item.id)}
                    renderItem={({ item }) => <CareRecordCard record={item} onEdit={(record) => void startEdit(record)} onDelete={confirmDelete} disabled={saving || loadingEdit || refreshing} />}
                    contentContainerStyle={styles.listContent}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => void refresh()} />}
                    ListEmptyComponent={<Text style={styles.emptyText}>No care records found for this resident.</Text>}
                  />
                )}
              </>
            )}
          </>
        ) : !showResidents ? (
          <View style={styles.center}>
            <Text style={styles.muted}>Select a resident to view care records.</Text>
          </View>
        ) : null}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: "#f5f7fb" },
  container: { flex: 1, paddingHorizontal: 16 },
  center: { flex: 1, alignItems: "center", justifyContent: "center", padding: 24 },
  heading: { fontSize: 28, fontWeight: "700", color: "#172033", marginTop: 8 },
  subtitle: { fontSize: 14, color: "#687386", marginTop: 6, marginBottom: 16 },
  muted: { color: "#687386", textAlign: "center", marginTop: 12 },
  residentSelector: { backgroundColor: "#ffffff", borderWidth: 1, borderColor: "#d9dfeb", borderRadius: 12, padding: 14, marginBottom: 12, flexDirection: "row", justifyContent: "space-between" },
  selectorText: { color: "#172033", fontSize: 15, fontWeight: "600", flexShrink: 1 },
  selectorArrow: { color: "#687386", marginLeft: 8 },
  residentPanel: { backgroundColor: "#ffffff", borderRadius: 12, padding: 10, marginBottom: 12, maxHeight: 250 },
  residentList: { maxHeight: 190 },
  residentOption: { paddingVertical: 12, paddingHorizontal: 10, borderBottomWidth: 1, borderBottomColor: "#edf0f5" },
  residentOptionText: { color: "#172033", fontSize: 14 },
  searchInput: { backgroundColor: "#ffffff", borderWidth: 1, borderColor: "#d9dfeb", borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, color: "#172033", fontSize: 15, marginBottom: 12 },
  count: { color: "#687386", fontSize: 13, marginBottom: 8 },
  card: { backgroundColor: "#ffffff", borderRadius: 14, padding: 15, marginBottom: 12, borderWidth: 1, borderColor: "#e5e9f0" },
  cardTitle: { fontSize: 16, fontWeight: "700", color: "#172033" },
  careType: { color: "#3157a4", fontSize: 13, marginTop: 5, marginBottom: 10 },
  detailRow: { flexDirection: "row", justifyContent: "space-between", gap: 12, marginTop: 7 },
  detailLabel: { color: "#687386", fontSize: 13 },
  detailValue: { flex: 1, textAlign: "right", color: "#354052", fontSize: 13 },
  status: { color: "#3157a4", fontWeight: "700", fontSize: 13 },
  notes: { marginTop: 12, color: "#4f5b6d", fontSize: 13, lineHeight: 19 },
  listContent: { paddingBottom: 30 },
  editButton: { marginTop: 14, paddingVertical: 10, alignItems: "center", borderRadius: 9, borderWidth: 1, borderColor: "#3157a4" },
  editButtonText: { color: "#3157a4", fontWeight: "700" },
  deleteButton: { marginTop: 9, paddingVertical: 10, alignItems: "center", borderRadius: 9, borderWidth: 1, borderColor: "#b91c1c" },
  deleteButtonText: { color: "#b91c1c", fontWeight: "700" },
  errorBox: { backgroundColor: "#fff0f0", borderRadius: 12, padding: 12, marginBottom: 12 },
  errorText: { color: "#8a2d2d", fontSize: 13, marginBottom: 8 },
  retryText: { color: "#3157a4", fontWeight: "700" },
  emptyText: { color: "#7a8495", textAlign: "center", marginTop: 24 },
  loadingIndicator: { marginTop: 24 },
  createButton: { backgroundColor: "#3157a4", borderRadius: 12, padding: 14, alignItems: "center", marginBottom: 12 },
  createButtonText: { color: "#ffffff", fontWeight: "700", fontSize: 15 },
  disabledButton: { opacity: 0.5 },
  formScroll: { flex: 1 },
  formContent: { paddingBottom: 50 },
  formHeading: { fontSize: 19, fontWeight: "700", color: "#172033", marginBottom: 6 },
  formHint: { color: "#687386", fontSize: 13, marginBottom: 14 },
  fieldLabel: { color: "#354052", fontWeight: "600", fontSize: 14, marginBottom: 7 },
  statusOptions: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginBottom: 16 },
  statusOption: { borderWidth: 1, borderColor: "#d9dfeb", backgroundColor: "#ffffff", borderRadius: 9, paddingVertical: 10, paddingHorizontal: 12 },
  statusOptionSelected: { borderColor: "#3157a4", backgroundColor: "#eaf0ff" },
  statusOptionText: { fontSize: 12, fontWeight: "600", color: "#354052" },
  statusOptionTextSelected: { color: "#23468e" },
  notesInput: { minHeight: 110 },
  saveButton: { backgroundColor: "#1e7b57", borderRadius: 12, padding: 15, alignItems: "center", marginTop: 6 },
  saveButtonText: { color: "#ffffff", fontSize: 15, fontWeight: "700" },
  cancelButton: { alignItems: "center", padding: 14 },
  cancelButtonText: { color: "#687386", fontWeight: "600" },
});
