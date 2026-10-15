import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { activateCarePlan, createCarePlan, deactivateCarePlan, getResidentCarePlans, updateCarePlan } from "../../api/carePlanApi";
import { getResidents } from "../../api/residentApi";
import type {
  CarePlan,
  CarePlanFrequency,
  CarePlanInput,
  CarePlanPriority,
  CarePlanTimeSlot,
  CarePlanWeekday,
} from "../../types/carePlan";
import type { Resident } from "../../types/resident";

const frequencies: CarePlanFrequency[] = ["DAILY", "WEEKLY", "EVERY_SHIFT", "PRN"];
const timeSlots: CarePlanTimeSlot[] = ["AM", "PM", "NIGHT", "ANY"];
const priorities: CarePlanPriority[] = ["LOW", "NORMAL", "HIGH", "URGENT", "CRITICAL"];
const weekdays: CarePlanWeekday[] = ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"];

function residentName(resident: Resident): string {
  return resident.full_name || `Resident #${resident.id}`;
}

function displayDate(value?: string | null): string {
  return value ? value.slice(0, 10) : "Not specified";
}

function displayTime(value?: string | null): string {
  return value ? value.slice(0, 5) : "Not specified";
}

function displayValue(value?: string | null): string {
  return value ? value.replace(/_/g, " ") : "Not specified";
}

function errorMessage(error: unknown): string {
  if (error && typeof error === "object") {
    const value = error as {
      response?: {
        data?: { message?: string; errors?: Record<string, string[]> };
      };
      message?: string;
    };
    const data = value.response?.data;
    const fieldError = data?.errors ? Object.values(data.errors).flat()[0] : undefined;
    return fieldError || data?.message || value.message || "Unable to save care plan.";
  }
  return "Unable to save care plan.";
}

function Detail({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.detailRow}>
      <Text style={styles.detailLabel}>{label}</Text>
      <Text style={styles.detailValue}>{value}</Text>
    </View>
  );
}

function CarePlanCard({ plan, onEdit, onToggleActive, busy }: {
  plan: CarePlan;
  onEdit: (plan: CarePlan) => void;
  onToggleActive: (plan: CarePlan) => void;
  busy: boolean;
}) {
  const days = Array.isArray(plan.days_of_week)
    ? plan.days_of_week.join(", ")
    : "Not specified";

  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.cardTitle}>{plan.title}</Text>
        <Text style={[styles.status, plan.is_active ? styles.active : styles.inactive]}>
          {plan.is_active ? "ACTIVE" : "INACTIVE"}
        </Text>
      </View>
      <Text style={styles.careType}>{plan.care_type}</Text>
      <Detail label="Frequency" value={displayValue(plan.frequency)} />
      <Detail label="Time Slot" value={displayValue(plan.time_slot)} />
      <Detail label="Scheduled Time" value={displayTime(plan.scheduled_time)} />
      <Detail label="Priority" value={displayValue(plan.priority)} />
      <Detail label="Weekdays" value={days} />
      <Detail label="Start Date" value={displayDate(plan.start_date)} />
      <Detail label="End Date" value={displayDate(plan.end_date)} />
      {typeof plan.instructions === "string" && plan.instructions.trim().length > 0 ? (
        <View style={styles.instructionsBox}>
          <Text style={styles.instructionsLabel}>Care Instructions</Text>
          <Text style={styles.instructions}>{plan.instructions}</Text>
        </View>
      ) : null}
      <Pressable
        style={[styles.editButton, busy && styles.disabledButton]}
        onPress={() => onEdit(plan)}
        disabled={busy}
        accessibilityRole="button"
      >
        <Text style={styles.editButtonText}>Edit Care Plan</Text>
      </Pressable>
      <Pressable
        style={[styles.toggleButton, busy && styles.disabledButton]}
        onPress={() => onToggleActive(plan)}
        disabled={busy}
        accessibilityRole="button"
      >
        <Text style={styles.toggleButtonText}>
          {busy ? "Updating..." : plan.is_active ? "Deactivate Care Plan" : "Reactivate Care Plan"}
        </Text>
      </Pressable>
    </View>
  );
}

function ChoiceGroup<T extends string>({
  label,
  options,
  value,
  onChange,
}: {
  label: string;
  options: readonly T[];
  value: T | null;
  onChange: (next: T | null) => void;
}) {
  return (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.choiceRow}>
        {options.map((option) => (
          <Pressable
            key={option}
            accessibilityRole="button"
            accessibilityState={{ selected: value === option }}
            onPress={() => onChange(value === option ? null : option)}
            style={[styles.choice, value === option && styles.choiceSelected]}
          >
            <Text style={[styles.choiceText, value === option && styles.choiceTextSelected]}>
              {displayValue(option)}
            </Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

function validDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  const [year, month, day] = value.split("-").map(Number);
  const parsed = new Date(Date.UTC(year, month - 1, day));
  return (
    parsed.getUTCFullYear() === year &&
    parsed.getUTCMonth() + 1 === month &&
    parsed.getUTCDate() === day
  );
}

export default function CarePlansScreen() {
  const [residents, setResidents] = useState<Resident[]>([]);
  const [selectedResidentId, setSelectedResidentId] = useState<number | null>(null);
  const selectedResidentRef = useRef<number | null>(null);
  const requestSequence = useRef(0);
  const savingRef = useRef(false);
  const togglingRef = useRef(false);
  const [togglingPlanId, setTogglingPlanId] = useState<number | null>(null);

  const [plans, setPlans] = useState<CarePlan[]>([]);
  const [residentSearch, setResidentSearch] = useState("");
  const [planSearch, setPlanSearch] = useState("");
  const [showResidents, setShowResidents] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [editingPlanId, setEditingPlanId] = useState<number | null>(null);

  const [careType, setCareType] = useState("");
  const [title, setTitle] = useState("");
  const [instructions, setInstructions] = useState("");
  const [frequency, setFrequency] = useState<CarePlanFrequency>("DAILY");
  const [timeSlot, setTimeSlot] = useState<CarePlanTimeSlot | null>(null);
  const [scheduledTime, setScheduledTime] = useState("");
  const [selectedDays, setSelectedDays] = useState<CarePlanWeekday[]>([]);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [priority, setPriority] = useState<CarePlanPriority>("NORMAL");
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState("");

  const [loading, setLoading] = useState(true);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");

  const resetForm = () => {
    setCareType("");
    setTitle("");
    setInstructions("");
    setFrequency("DAILY");
    setTimeSlot(null);
    setScheduledTime("");
    setSelectedDays([]);
    setStartDate("");
    setEndDate("");
    setPriority("NORMAL");
    setFormError("");
    setEditingPlanId(null);
  };

  const openEdit = (plan: CarePlan) => {
    if (savingRef.current || togglingRef.current) return;
    setEditingPlanId(plan.id);
    setCareType(plan.care_type || "");
    setTitle(plan.title || "");
    setInstructions(plan.instructions || "");
    setFrequency(plan.frequency);
    setTimeSlot(plan.time_slot || null);
    setScheduledTime(displayTime(plan.scheduled_time) === "Not specified" ? "" : displayTime(plan.scheduled_time));
    setSelectedDays(Array.isArray(plan.days_of_week) ? [...plan.days_of_week] : []);
    setStartDate(plan.start_date ? plan.start_date.slice(0, 10) : "");
    setEndDate(plan.end_date ? plan.end_date.slice(0, 10) : "");
    setPriority(plan.priority || "NORMAL");
    setFormError("");
    setShowResidents(false);
    setShowCreate(true);
  };

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

  const loadPlans = useCallback(async (residentId: number) => {
    const sequence = ++requestSequence.current;
    setLoadingPlans(true);
    try {
      const result = await getResidentCarePlans(residentId);
      if (sequence !== requestSequence.current || selectedResidentRef.current !== residentId) return;
      setPlans(Array.isArray(result.care_plans) ? result.care_plans : []);
      setError("");
    } catch (err) {
      if (sequence !== requestSequence.current || selectedResidentRef.current !== residentId) return;
      setPlans([]);
      setError(errorMessage(err));
    } finally {
      if (sequence === requestSequence.current) setLoadingPlans(false);
    }
  }, []);

  useEffect(() => {
    void loadResidents();
  }, [loadResidents]);

  const selectedResident = residents.find((resident) => resident.id === selectedResidentId);
  const filteredResidents = useMemo(() => {
    const query = residentSearch.trim().toLowerCase();
    return query
      ? residents.filter((resident) => residentName(resident).toLowerCase().includes(query))
      : residents;
  }, [residents, residentSearch]);

  const filteredPlans = useMemo(() => {
    const query = planSearch.trim().toLowerCase();
    if (!query) return plans;
    return plans.filter((plan) =>
      [plan.title, plan.care_type, plan.frequency, plan.priority, plan.instructions,
        plan.is_active ? "active" : "inactive"]
        .filter((value): value is string => typeof value === "string")
        .join(" ")
        .toLowerCase()
        .includes(query)
    );
  }, [plans, planSearch]);

  const selectResident = (resident: Resident) => {
    if (savingRef.current || togglingRef.current) return;
    selectedResidentRef.current = resident.id;
    setSelectedResidentId(resident.id);
    setPlans([]);
    setPlanSearch("");
    setShowResidents(false);
    setShowCreate(false);
    resetForm();
    setError("");
    void loadPlans(resident.id);
  };

  const refresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await loadResidents();
      if (selectedResidentRef.current !== null) {
        await loadPlans(selectedResidentRef.current);
      }
    } finally {
      setRefreshing(false);
    }
  }, [loadResidents, loadPlans]);

  const toggleDay = (day: CarePlanWeekday) => {
    setSelectedDays((current) =>
      current.includes(day) ? current.filter((value) => value !== day) : [...current, day]
    );
  };

  const toggleActive = (plan: CarePlan) => {
    if (savingRef.current || togglingRef.current || selectedResidentRef.current !== plan.resident_id) return;

    const deactivate = plan.is_active;
    Alert.alert(
      deactivate ? "Deactivate Care Plan?" : "Reactivate Care Plan?",
      deactivate
        ? `Deactivate "${plan.title}"? The plan and its history will be preserved, but future routine care tasks will no longer be generated from it.`
        : `Reactivate "${plan.title}"? Scheduled routine care tasks may be generated again according to this plan.`,
      [
        { text: "Cancel", style: "cancel" },
        {
          text: deactivate ? "Deactivate" : "Reactivate",
          style: deactivate ? "destructive" : "default",
          onPress: () => {
            if (savingRef.current || togglingRef.current || selectedResidentRef.current !== plan.resident_id) return;
            togglingRef.current = true;
            setTogglingPlanId(plan.id);
            setError("");
            void (async () => {
              try {
                if (deactivate) {
                  await deactivateCarePlan(plan.id);
                } else {
                  await activateCarePlan(plan.id);
                }
                if (selectedResidentRef.current === plan.resident_id) {
                  await loadPlans(plan.resident_id);
                  Alert.alert(
                    deactivate ? "Care Plan Deactivated" : "Care Plan Reactivated",
                    "The care plan status was updated successfully."
                  );
                }
              } catch (err) {
                Alert.alert("Unable to Update Care Plan", errorMessage(err));
              } finally {
                togglingRef.current = false;
                setTogglingPlanId(null);
              }
            })();
          },
        },
      ]
    );
  };

  const savePlan = async () => {
    const residentId = selectedResidentRef.current;
    if (savingRef.current || togglingRef.current || residentId === null) return;
    setFormError("");

    if (!careType.trim() || !title.trim()) {
      setFormError("Care type and title are required.");
      return;
    }
    if (careType.trim().length > 100 || title.trim().length > 255) {
      setFormError("Care type must be at most 100 characters and title at most 255 characters.");
      return;
    }
    const time = scheduledTime.trim();
    if (time && !/^([01]\d|2[0-3]):[0-5]\d$/.test(time)) {
      setFormError("Scheduled time must use 24-hour HH:mm format, for example 08:30.");
      return;
    }
    const start = startDate.trim();
    const end = endDate.trim();
    if ((start && !validDate(start)) || (end && !validDate(end))) {
      setFormError("Dates must be valid and use YYYY-MM-DD format.");
      return;
    }
    if (start && end && end < start) {
      setFormError("End date cannot be earlier than start date.");
      return;
    }
    if (end && !start) {
      setFormError("Enter a start date when specifying an end date.");
      return;
    }

    const input: CarePlanInput = {
      care_type: careType.trim(),
      title: title.trim(),
      instructions: instructions.trim() || null,
      frequency,
      time_slot: timeSlot,
      scheduled_time: time || null,
      days_of_week: selectedDays.length ? weekdays.filter((day) => selectedDays.includes(day)) : null,
      priority,
      ...(start ? { start_date: start } : {}),
      ...(editingPlanId !== null ? { end_date: end || null } : end ? { end_date: end } : {}),
    };

    savingRef.current = true;
    setSaving(true);
    try {
      if (editingPlanId !== null) {
        await updateCarePlan(editingPlanId, input);
      } else {
        await createCarePlan(residentId, input);
      }
      const wasEditing = editingPlanId !== null;
      setShowCreate(false);
      resetForm();
      setPlanSearch("");
      if (selectedResidentRef.current === residentId) {
        await loadPlans(residentId);
      }
      Alert.alert(
        wasEditing ? "Care Plan Updated" : "Care Plan Created",
        wasEditing ? "Changes were saved successfully." : "The care plan was saved successfully."
      );
    } catch (err) {
      setFormError(errorMessage(err));
    } finally {
      savingRef.current = false;
      setSaving(false);
    }
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
        <Text style={styles.heading}>Care Plans</Text>
        <Text style={styles.subtitle}>Review scheduled care requirements and recurring nursing plans.</Text>

        {error ? (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{error}</Text>
            <Pressable onPress={() => void refresh()}>
              <Text style={styles.retryText}>Retry</Text>
            </Pressable>
          </View>
        ) : null}

        <Pressable
          style={styles.residentSelector}
          disabled={saving || togglingPlanId !== null}
          onPress={() => setShowResidents((value) => !value)}
        >
          <Text style={styles.selectorText}>
            {selectedResident ? residentName(selectedResident) : "Select a resident"}
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
                  <Text style={styles.residentOptionText}>{residentName(item)}</Text>
                </Pressable>
              )}
              ListEmptyComponent={<Text style={styles.emptyText}>No residents found.</Text>}
            />
          </View>
        ) : null}

        {selectedResidentId !== null ? (
          <>
            <Pressable
              style={[styles.primaryButton, (saving || togglingPlanId !== null) && styles.disabledButton]}
              disabled={saving || togglingPlanId !== null}
              onPress={() => {
                if (showCreate) {
                  resetForm();
                  setShowCreate(false);
                } else {
                  resetForm();
                  setShowCreate(true);
                }
                setShowResidents(false);
              }}
            >
              <Text style={styles.primaryButtonText}>
                {showCreate ? (editingPlanId !== null ? "Cancel Editing" : "Cancel New Care Plan") : "+ Create Care Plan"}
              </Text>
            </Pressable>

            {showCreate ? (
              <KeyboardAvoidingView
                style={styles.formContainer}
                behavior={Platform.OS === "ios" ? "padding" : undefined}
                keyboardVerticalOffset={100}
              >
                <ScrollView
                  style={styles.formScroll}
                  contentContainerStyle={styles.formContent}
                  keyboardShouldPersistTaps="handled"
                >
                  <Text style={styles.formHeading}>{editingPlanId !== null ? "Edit Care Plan" : "New Care Plan"}</Text>
                  <Text style={styles.formHint}>
                    Resident: {selectedResident ? residentName(selectedResident) : `#${selectedResidentId}`}
                  </Text>
                  <Text style={styles.fieldLabel}>Care Type *</Text>
                  <TextInput
                    style={styles.searchInput}
                    value={careType}
                    onChangeText={setCareType}
                    placeholder="e.g. Personal Hygiene"
                    maxLength={100}
                  />
                  <Text style={styles.fieldLabel}>Title *</Text>
                  <TextInput
                    style={styles.searchInput}
                    value={title}
                    onChangeText={setTitle}
                    placeholder="Care plan title"
                    maxLength={255}
                  />
                  <Text style={styles.fieldLabel}>Instructions</Text>
                  <TextInput
                    style={[styles.searchInput, styles.multilineInput]}
                    value={instructions}
                    onChangeText={setInstructions}
                    placeholder="Care instructions for nursing staff"
                    multiline
                    textAlignVertical="top"
                  />

                  <ChoiceGroup
                    label="Frequency *"
                    options={frequencies}
                    value={frequency}
                    onChange={(next) => { if (next) setFrequency(next); }}
                  />
                  <ChoiceGroup
                    label="Time Slot (optional)"
                    options={timeSlots}
                    value={timeSlot}
                    onChange={setTimeSlot}
                  />

                  <Text style={styles.fieldLabel}>Scheduled Time (optional)</Text>
                  <TextInput
                    style={styles.searchInput}
                    value={scheduledTime}
                    onChangeText={setScheduledTime}
                    placeholder="HH:mm, e.g. 08:30"
                    keyboardType="numbers-and-punctuation"
                    maxLength={5}
                  />

                  <Text style={styles.fieldLabel}>Days of Week (optional)</Text>
                  <View style={styles.choiceRow}>
                    {weekdays.map((day) => (
                      <Pressable
                        key={day}
                        onPress={() => toggleDay(day)}
                        style={[styles.choice, selectedDays.includes(day) && styles.choiceSelected]}
                      >
                        <Text style={[styles.choiceText, selectedDays.includes(day) && styles.choiceTextSelected]}>
                          {day}
                        </Text>
                      </Pressable>
                    ))}
                  </View>

                  <Text style={styles.fieldLabel}>Start Date (optional)</Text>
                  <TextInput
                    style={styles.searchInput}
                    value={startDate}
                    onChangeText={setStartDate}
                    placeholder="YYYY-MM-DD (blank = today)"
                    keyboardType="numbers-and-punctuation"
                    maxLength={10}
                  />
                  <Text style={styles.fieldLabel}>End Date (optional)</Text>
                  <TextInput
                    style={styles.searchInput}
                    value={endDate}
                    onChangeText={setEndDate}
                    placeholder="YYYY-MM-DD"
                    keyboardType="numbers-and-punctuation"
                    maxLength={10}
                  />
                  <ChoiceGroup
                    label="Priority"
                    options={priorities}
                    value={priority}
                    onChange={(next) => { if (next) setPriority(next); }}
                  />

                  {formError ? (
                    <View style={styles.errorBox}>
                      <Text style={styles.errorText}>{formError}</Text>
                    </View>
                  ) : null}
                  <Pressable
                    style={[styles.primaryButton, saving && styles.disabledButton]}
                    disabled={saving}
                    onPress={() => void savePlan()}
                  >
                    <Text style={styles.primaryButtonText}>
                      {saving ? "Saving..." : editingPlanId !== null ? "Save Changes" : "Save Care Plan"}
                    </Text>
                  </Pressable>
                  {saving ? <ActivityIndicator style={{ marginTop: 8 }} /> : null}
                </ScrollView>
              </KeyboardAvoidingView>
            ) : (
              <>
                <TextInput
                  value={planSearch}
                  onChangeText={setPlanSearch}
                  placeholder="Search care plans"
                  style={styles.searchInput}
                />
                <Text style={styles.count}>
                  {filteredPlans.length} care plan{filteredPlans.length === 1 ? "" : "s"}
                </Text>
                {loadingPlans ? (
                  <ActivityIndicator style={styles.loadingIndicator} />
                ) : (
                  <FlatList
                    data={filteredPlans}
                    keyExtractor={(item) => String(item.id)}
                    renderItem={({ item }) => (
                      <CarePlanCard
                        plan={item}
                        onEdit={openEdit}
                        onToggleActive={toggleActive}
                        busy={saving || togglingPlanId !== null}
                      />
                    )}
                    contentContainerStyle={styles.listContent}
                    refreshControl={
                      <RefreshControl refreshing={refreshing} onRefresh={() => void refresh()} />
                    }
                    ListEmptyComponent={
                      <Text style={styles.emptyText}>No care plans found for this resident.</Text>
                    }
                  />
                )}
              </>
            )}
          </>
        ) : !showResidents ? (
          <View style={styles.center}>
            <Text style={styles.muted}>Select a resident to view care plans.</Text>
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
  residentSelector: {
    backgroundColor: "#ffffff", borderWidth: 1, borderColor: "#d9dfeb", borderRadius: 12,
    padding: 14, marginBottom: 12, flexDirection: "row", justifyContent: "space-between",
  },
  selectorText: { color: "#172033", fontSize: 15, fontWeight: "600", flexShrink: 1 },
  selectorArrow: { color: "#687386", marginLeft: 8 },
  residentPanel: { backgroundColor: "#ffffff", borderRadius: 12, padding: 10, marginBottom: 12, maxHeight: 250 },
  residentList: { maxHeight: 190 },
  residentOption: { paddingVertical: 12, paddingHorizontal: 10, borderBottomWidth: 1, borderBottomColor: "#edf0f5" },
  residentOptionText: { color: "#172033", fontSize: 14 },
  searchInput: {
    backgroundColor: "#ffffff", borderWidth: 1, borderColor: "#d9dfeb", borderRadius: 12,
    paddingHorizontal: 14, paddingVertical: 12, color: "#172033", fontSize: 15, marginBottom: 12,
  },
  count: { color: "#687386", fontSize: 13, marginBottom: 8 },
  card: { backgroundColor: "#ffffff", borderRadius: 14, padding: 15, marginBottom: 12, borderWidth: 1, borderColor: "#e5e9f0" },
  cardHeader: { flexDirection: "row", alignItems: "flex-start", justifyContent: "space-between", gap: 8 },
  cardTitle: { flex: 1, fontSize: 16, fontWeight: "700", color: "#172033" },
  careType: { color: "#3157a4", fontSize: 13, marginTop: 5, marginBottom: 10 },
  status: { fontSize: 11, fontWeight: "700", overflow: "hidden", borderRadius: 7, paddingHorizontal: 8, paddingVertical: 5 },
  active: { backgroundColor: "#e6f6ed", color: "#17643b" },
  inactive: { backgroundColor: "#f1f5f9", color: "#64748b" },
  detailRow: { flexDirection: "row", justifyContent: "space-between", gap: 12, marginTop: 7 },
  detailLabel: { color: "#687386", fontSize: 13 },
  detailValue: { flex: 1, textAlign: "right", color: "#354052", fontSize: 13 },
  instructionsBox: { marginTop: 12, padding: 12, borderRadius: 9, backgroundColor: "#f1f5f9" },
  instructionsLabel: { fontSize: 12, fontWeight: "700", color: "#354052", marginBottom: 5 },
  instructions: { fontSize: 13, lineHeight: 19, color: "#4f5b6d" },
  listContent: { paddingBottom: 30 },
  errorBox: { backgroundColor: "#fff0f0", borderRadius: 12, padding: 12, marginBottom: 12 },
  errorText: { color: "#8a2d2d", fontSize: 13, marginBottom: 8 },
  retryText: { color: "#3157a4", fontWeight: "700" },
  emptyText: { color: "#7a8495", textAlign: "center", marginTop: 24 },
  loadingIndicator: { marginTop: 24 },
  primaryButton: { backgroundColor: "#3157a4", padding: 13, borderRadius: 11, alignItems: "center", marginBottom: 12 },
  primaryButtonText: { color: "#ffffff", fontWeight: "700", fontSize: 15 },
  disabledButton: { opacity: 0.55 },
  formContainer: { flex: 1 },
  formScroll: { flex: 1 },
  formContent: { paddingBottom: 42 },
  formHeading: { fontSize: 20, fontWeight: "700", color: "#172033", marginBottom: 4 },
  formHint: { color: "#687386", fontSize: 13, marginBottom: 18 },
  fieldLabel: { color: "#354052", fontWeight: "600", fontSize: 14, marginBottom: 8 },
  fieldGroup: { marginBottom: 12 },
  multilineInput: { minHeight: 90 },
  choiceRow: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginBottom: 14 },
  choice: { paddingHorizontal: 12, paddingVertical: 9, borderRadius: 9, backgroundColor: "#ffffff", borderWidth: 1, borderColor: "#d9dfeb" },
  choiceSelected: { backgroundColor: "#e7eefc", borderColor: "#3157a4" },
  choiceText: { color: "#354052", fontSize: 13 },
  choiceTextSelected: { color: "#244887", fontWeight: "700" },
  editButton: { borderColor: "#3157a4", borderWidth: 1, borderRadius: 9, padding: 11, alignItems: "center", marginTop: 14 },
  editButtonText: { color: "#3157a4", fontWeight: "700", fontSize: 14 },
  toggleButton: { borderColor: "#8b5e34", borderWidth: 1, borderRadius: 9, padding: 11, alignItems: "center", marginTop: 9 },
  toggleButtonText: { color: "#8b5e34", fontWeight: "700", fontSize: 14 },
});
