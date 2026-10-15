import React, { useCallback, useEffect, useMemo, useState } from "react";
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

import { getResidents } from "../../api/residentApi";
import {
  confirmMedicationMeal,
  getMedicationHistory,
  getMedicationSchedule,
  recordMedicationOutcome,
} from "../../api/medicationApi";
import { Resident } from "../../types/resident";
import {
  MedicationHistoryRecord,
  MedicationOutcome,
  MedicationSchedule,
  MedicationSlot,
  ScheduledMedication,
} from "../../types/medication";
import MedicationActionControls from "./MedicationActionControls";

const EMPTY_SCHEDULE: MedicationSchedule = {
  AM: [],
  PM: [],
  NIGHT: [],
  OTHER: [],
};

const SLOT_LABELS: Record<MedicationSlot, string> = {
  AM: "AM · Breakfast",
  PM: "PM · Lunch",
  NIGHT: "Night · Dinner",
  OTHER: "Other Medication",
};

function residentName(resident: Resident): string {
  return String(
    resident.full_name ??
      resident.name ??
      `Resident #${resident.id}`
  );
}

function residentRoom(resident: Resident): string {
  return String(
    resident.room_number ??
      resident.room ??
      ""
  );
}

function formatTime(value?: string | null): string {
  if (!value) return "Not specified";

  const text = String(value);

  if (/^\d{2}:\d{2}/.test(text)) {
    return text.slice(0, 5);
  }

  const date = new Date(text);

  if (Number.isNaN(date.getTime())) {
    return text;
  }

  return date.toLocaleTimeString("en-MY", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatDateTime(value?: string | null): string {
  if (!value) return "-";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleString("en-MY", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function medicineDescription(
  medication: ScheduledMedication
): string {
  const parts = [
    medication.dosage,
    medication.unit,
  ].filter(Boolean);

  return parts.length > 0
    ? parts.join(" ")
    : "Dosage not specified";
}

function readError(error: any, fallback: string): string {
  const validationErrors = error?.response?.data?.errors;

  if (validationErrors) {
    const first = Object.values(validationErrors)
      .flat()
      .find(Boolean);

    if (first) {
      return String(first);
    }
  }

  return (
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    fallback
  );
}

function StatusBadge({ status }: { status?: string | null }) {
  const normalized = String(status || "PENDING").toUpperCase();

  return (
    <View style={styles.statusBadge}>
      <Text style={styles.statusText}>
        {normalized}
      </Text>
    </View>
  );
}

function MedicationCard({
  medication,
  canAdminister,
  submittingMedicationId,
  onRecordOutcome,
  onConfirmMeal,
}: {
  medication: ScheduledMedication;
  canAdminister: boolean;
  submittingMedicationId: number | null;
  onRecordOutcome: (
    medication: ScheduledMedication,
    outcome: MedicationOutcome,
    remarks: string,
    mealConfirmed: boolean,
    mealNotes: string
  ) => void;
  onConfirmMeal: (
    medication: ScheduledMedication,
    mealNotes: string
  ) => void;
}) {

  return (
    <View style={styles.medicationCard}>
      <View style={styles.cardTopRow}>
        <View style={styles.cardTitleArea}>
          <Text style={styles.medicineName}>
            {medication.medicine || "Medication"}
          </Text>

          <Text style={styles.medicineMeta}>
            {medicineDescription(medication)}
          </Text>
        </View>

        <StatusBadge status={medication.status} />
      </View>

      <View style={styles.detailRow}>
        <Text style={styles.detailLabel}>Scheduled</Text>
        <Text style={styles.detailValue}>
          {formatTime(medication.scheduled_time)}
        </Text>
      </View>

      {medication.quantity != null && (
        <View style={styles.detailRow}>
          <Text style={styles.detailLabel}>Quantity</Text>
          <Text style={styles.detailValue}>
            {String(medication.quantity)}
          </Text>
        </View>
      )}

      {!!medication.frequency && (
        <View style={styles.detailRow}>
          <Text style={styles.detailLabel}>Frequency</Text>
          <Text style={styles.detailValue}>
            {medication.frequency}
          </Text>
        </View>
      )}

      {!!medication.instruction && (
        <View style={styles.noteBox}>
          <Text style={styles.noteLabel}>Instruction</Text>
          <Text style={styles.noteText}>
            {medication.instruction}
          </Text>
        </View>
      )}

      {!!medication.meal_type && (
        <View style={styles.mealRow}>
          <Text style={styles.mealText}>
            {medication.meal_type}:{" "}
            {medication.meal_confirmed
              ? "Confirmed"
              : "Not confirmed"}
          </Text>
        </View>
      )}

      {!!medication.remarks && (
        <Text style={styles.remarks}>
          Note: {medication.remarks}
        </Text>
      )}

      <MedicationActionControls
        medication={medication}
        canAdminister={canAdminister}
        submitting={
          submittingMedicationId === medication.resident_medication_id
        }
        anySubmitting={submittingMedicationId !== null}
        onRecordOutcome={onRecordOutcome}
        onConfirmMeal={onConfirmMeal}
      />
    </View>
  );
}

function RoundSection({
  slot,
  medications,
  canAdminister,
  submittingMedicationId,
  onRecordOutcome,
  onConfirmMeal,
}: {
  slot: MedicationSlot;
  medications: ScheduledMedication[];
  canAdminister: boolean;
  submittingMedicationId: number | null;
  onRecordOutcome: (
    medication: ScheduledMedication,
    outcome: MedicationOutcome,
    remarks: string,
    mealConfirmed: boolean,
    mealNotes: string
  ) => void;
  onConfirmMeal: (
    medication: ScheduledMedication,
    mealNotes: string
  ) => void;
}) {
  return (
    <View style={styles.section}>
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>
          {SLOT_LABELS[slot]}
        </Text>

        <Text style={styles.sectionCount}>
          {medications.length}
        </Text>
      </View>

      {medications.length === 0 ? (
        <Text style={styles.emptySectionText}>
          No medication scheduled.
        </Text>
      ) : (
        medications.map((medication) => (
          <MedicationCard
            key={medication.resident_medication_id}
            medication={medication}
            canAdminister={canAdminister}
            submittingMedicationId={submittingMedicationId}
            onRecordOutcome={onRecordOutcome}
            onConfirmMeal={onConfirmMeal}
          />
        ))
      )}
    </View>
  );
}

function HistoryCard({
  record,
}: {
  record: MedicationHistoryRecord;
}) {
  return (
    <View style={styles.historyCard}>
      <View style={styles.cardTopRow}>
        <View style={styles.cardTitleArea}>
          <Text style={styles.historyMedicine}>
            {record.medicine || "Medication"}
          </Text>

          <Text style={styles.historyDate}>
            {formatDateTime(record.completed_time)}
          </Text>
        </View>

        <StatusBadge status={record.status} />
      </View>

      <Text style={styles.historyMeta}>
        {record.time_slot || "Other"}
        {record.scheduled_time
          ? ` · ${formatTime(record.scheduled_time)}`
          : ""}
      </Text>

      {!!record.completed_by && (
        <Text style={styles.historyMeta}>
          Recorded by: {record.completed_by}
        </Text>
      )}

      {!!record.remarks && (
        <Text style={styles.remarks}>
          Note: {record.remarks}
        </Text>
      )}

      {!!record.meal_type && (
        <Text style={styles.historyMeta}>
          {record.meal_type}:{" "}
          {record.meal_confirmed
            ? "Confirmed"
            : "Not confirmed"}
        </Text>
      )}

      {!!record.meal_notes && (
        <Text style={styles.remarks}>
          Meal note: {record.meal_notes}
        </Text>
      )}
    </View>
  );
}

export default function MedicationScreen() {
  const [residents, setResidents] = useState<Resident[]>([]);
  const [selectedResident, setSelectedResident] =
    useState<Resident | null>(null);

  const [search, setSearch] = useState("");

  const [schedule, setSchedule] =
    useState<MedicationSchedule>(EMPTY_SCHEDULE);

  const [history, setHistory] =
    useState<MedicationHistoryRecord[]>([]);

  const [canAdminister, setCanAdminister] =
    useState(false);

  const [loadingResidents, setLoadingResidents] =
    useState(true);

  const [loadingMedication, setLoadingMedication] =
    useState(false);

  const [refreshing, setRefreshing] =
    useState(false);

  const [error, setError] = useState("");

  const [submittingMedicationId, setSubmittingMedicationId] =
  useState<number | null>(null);

  const loadResidents = useCallback(async () => {
    try {
      setLoadingResidents(true);
      setError("");

      const list = await getResidents();

      setResidents(list);
    } catch (err) {
      setError(
        readError(
          err,
          "Unable to load residents."
        )
      );
    } finally {
      setLoadingResidents(false);
    }
  }, []);

  const loadMedicationData = useCallback(
    async (residentId: number) => {
      try {
        setLoadingMedication(true);
        setError("");

        const [scheduleResponse, historyResponse] =
          await Promise.all([
            getMedicationSchedule(residentId),
            getMedicationHistory(residentId),
          ]);

        setSchedule(scheduleResponse.schedule);
        setCanAdminister(
          scheduleResponse.can_administer
        );

        setHistory(
          historyResponse.medication_history
        );
      } catch (err) {
        setSchedule(EMPTY_SCHEDULE);
        setHistory([]);
        setCanAdminister(false);

        setError(
          readError(
            err,
            "Unable to load medication information."
          )
        );
      } finally {
        setLoadingMedication(false);
      }
    },
    []
  );

  const submitMedicationOutcome = useCallback(
  async (
    medication: ScheduledMedication,
    outcome: MedicationOutcome,
    enteredRemarks: string,
    enteredMealConfirmed: boolean,
    enteredMealNotes: string
  ) => {
    if (!selectedResident) {
      return;
    }

    if (submittingMedicationId !== null) {
      return;
    }

    if (!canAdminister) {
      Alert.alert(
        "Medication unavailable",
        "Medication administration is not available for this resident."
      );
      return;
    }

    const medicationId =
      medication.resident_medication_id;

    const note = enteredRemarks.trim();
    const mealNote = enteredMealNotes.trim();

    if (outcome !== "COMPLETED" && !note) {
      Alert.alert(
        "Remarks required",
        `Please enter remarks before recording ${outcome.toLowerCase()}.`
      );
      return;
    }

    const supportsMeal =
      medication.time_slot === "AM" ||
      medication.time_slot === "PM" ||
      medication.time_slot === "NIGHT";

    const confirmMeal =
      outcome === "COMPLETED" &&
      supportsMeal &&
      Boolean(enteredMealConfirmed);

    const outcomeLabel =
      outcome.charAt(0) +
      outcome.slice(1).toLowerCase();

    Alert.alert(
      "Confirm Medication",
      `Record ${medication.medicine || "this medication"} as ${outcomeLabel}?`,
      [
        {
          text: "Cancel",
          style: "cancel",
        },
        {
          text: "Confirm",
          onPress: async () => {
            try {
              setSubmittingMedicationId(medicationId);
              setError("");

              const result =
                await recordMedicationOutcome(
                  medicationId,
                  {
                    status: outcome,
                    remarks: note || null,
                    meal_confirmed: confirmMeal,
                    meal_notes:
                      outcome === "COMPLETED" &&
                      supportsMeal
                        ? mealNote || null
                        : null,
                  }
                );


              await loadMedicationData(
                selectedResident.id
              );

              Alert.alert(
                "Medication updated",
                result?.message ||
                  "Medication outcome recorded successfully."
              );
            } catch (err) {
              const message = readError(
                err,
                "Unable to record medication outcome."
              );

              setError(message);

              Alert.alert(
                "Medication not updated",
                message
              );

              await loadMedicationData(
                selectedResident.id
              );
            } finally {
              setSubmittingMedicationId(null);
            }
          },
        },
      ]
    );
  },
  [
    canAdminister,
  loadMedicationData,
  selectedResident,
  submittingMedicationId,
  ]
);


const submitMealConfirmation = useCallback(
  async (
    medication: ScheduledMedication,
    enteredMealNotes: string
  ) => {
    if (!selectedResident) {
      return;
    }

    if (!medication.administration_record_id) {
      Alert.alert(
        "Meal confirmation unavailable",
        "The medication administration record could not be found."
      );
      return;
    }

    if (medication.status !== "COMPLETED") {
      Alert.alert(
        "Medication not completed",
        "The medication must be completed before confirming the meal separately."
      );
      return;
    }

    if (!medication.meal_type) {
      Alert.alert(
        "No linked meal",
        "This medication does not have a linked meal."
      );
      return;
    }

    if (medication.meal_confirmed) {
      Alert.alert(
        "Meal already confirmed",
        `${medication.meal_type} has already been confirmed.`
      );
      return;
    }

    const medicationId =
      medication.resident_medication_id;

    const note = enteredMealNotes.trim();

    Alert.alert(
      "Confirm Meal",
      `Confirm ${medication.meal_type} for ${
        medication.medicine || "this medication"
      }?`,
      [
        {
          text: "Cancel",
          style: "cancel",
        },
        {
          text: "Confirm",
          onPress: async () => {
            try {
              setSubmittingMedicationId(
                medicationId
              );
              setError("");

              const result =
                await confirmMedicationMeal(
                  medication.administration_record_id!,
                  {
                    meal_notes: note || null,
                  }
                );


              await loadMedicationData(
                selectedResident.id
              );

              Alert.alert(
                "Meal confirmed",
                result?.message ||
                  `${medication.meal_type} confirmed successfully.`
              );
            } catch (err) {
              const message = readError(
                err,
                "Unable to confirm the meal."
              );

              setError(message);

              Alert.alert(
                "Meal not confirmed",
                message
              );

              await loadMedicationData(
                selectedResident.id
              );
            } finally {
              setSubmittingMedicationId(null);
            }
          },
        },
      ]
    );
  },
  [
    loadMedicationData,
    selectedResident,
    submittingMedicationId,
  ]
);



  useEffect(() => {
    loadResidents();
  }, [loadResidents]);

  useEffect(() => {
    if (!selectedResident) {
      setSchedule(EMPTY_SCHEDULE);
      setHistory([]);
      setCanAdminister(false);
      return;
    }

    loadMedicationData(selectedResident.id);
  }, [selectedResident, loadMedicationData]);

  const filteredResidents = useMemo(() => {
    const query = search.trim().toLowerCase();

    if (!query) {
      return residents;
    }

    return residents.filter((resident) => {
      const name = residentName(resident).toLowerCase();
      const room = residentRoom(resident).toLowerCase();

      return (
        name.includes(query) ||
        room.includes(query)
      );
    });
  }, [residents, search]);

  const refresh = useCallback(async () => {
    try {
      setRefreshing(true);

      await loadResidents();

      if (selectedResident) {
        await loadMedicationData(
          selectedResident.id
        );
      }
    } finally {
      setRefreshing(false);
    }
  }, [
    loadResidents,
    loadMedicationData,
    selectedResident,
  ]);

  if (loadingResidents && residents.length === 0) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.center}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>
            Loading residents...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!selectedResident) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.container}>
          <Text style={styles.pageTitle}>
            Medication
          </Text>

          <Text style={styles.pageSubtitle}>
            Select a resident to review today's
            medication schedule and administration
            history.
          </Text>

          {!!error && (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>
                {error}
              </Text>

              <Pressable
                style={styles.retryButton}
                onPress={loadResidents}
              >
                <Text style={styles.retryButtonText}>
                  Retry
                </Text>
              </Pressable>
            </View>
          )}

          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder="Search resident or room"
            style={styles.searchInput}
            autoCapitalize="none"
            autoCorrect={false}
          />

          <Text style={styles.residentCount}>
            {filteredResidents.length} resident
            {filteredResidents.length === 1
              ? ""
              : "s"}
          </Text>

          <FlatList
            data={filteredResidents}
            keyExtractor={(item) =>
              String(item.id)
            }
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={refresh}
              />
            }
            contentContainerStyle={
              styles.residentList
            }
            ListEmptyComponent={
              <Text style={styles.emptyText}>
                No residents found.
              </Text>
            }
            renderItem={({ item }) => (
              <Pressable
                style={styles.residentCard}
                onPress={() =>
                  setSelectedResident(item)
                }
              >
                <View>
                  <Text style={styles.residentName}>
                    {residentName(item)}
                  </Text>

                  <Text style={styles.residentMeta}>
                    {residentRoom(item)
                      ? `Room ${residentRoom(item)}`
                      : "Room not assigned"}
                  </Text>
                </View>

                <Text style={styles.openText}>
                  Open ›
                </Text>
              </Pressable>
            )}
          />
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={refresh}
          />
        }
      >
        <Pressable
          style={styles.changeResidentButton}
          onPress={() => {
            setSelectedResident(null);
            setSearch("");
            setError("");
          }}
        >
          <Text style={styles.changeResidentText}>
            ‹ Change Resident
          </Text>
        </Pressable>

        <Text style={styles.pageTitle}>
          {residentName(selectedResident)}
        </Text>

        <Text style={styles.pageSubtitle}>
          {residentRoom(selectedResident)
            ? `Room ${residentRoom(selectedResident)} · `
            : ""}
          Medication
        </Text>

        <View
          style={[
            styles.permissionBox,
            !canAdminister &&
              styles.permissionWarning,
          ]}
        >
          <Text style={styles.permissionText}>
            {canAdminister
              ? "Resident is active and eligible for medication administration."
              : "Medication administration is not available for this resident."}
          </Text>
        </View>

        {!!error && (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>
              {error}
            </Text>

            <Pressable
              style={styles.retryButton}
              onPress={() =>
                loadMedicationData(
                  selectedResident.id
                )
              }
            >
              <Text style={styles.retryButtonText}>
                Retry
              </Text>
            </Pressable>
          </View>
        )}

        {loadingMedication ? (
          <View style={styles.loadingBlock}>
            <ActivityIndicator size="large" />
            <Text style={styles.loadingText}>
              Loading medication...
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.groupHeading}>
              Today's Schedule
            </Text>

            <RoundSection
              slot="AM"
              medications={schedule.AM}
              canAdminister={canAdminister}
              submittingMedicationId={submittingMedicationId}
              onRecordOutcome={submitMedicationOutcome}
              onConfirmMeal={submitMealConfirmation}
            />

            <RoundSection
              slot="PM"
              medications={schedule.PM}
              canAdminister={canAdminister}
              submittingMedicationId={submittingMedicationId}
              onRecordOutcome={submitMedicationOutcome}
              onConfirmMeal={submitMealConfirmation}
            />

            <RoundSection
              slot="NIGHT"
              medications={schedule.NIGHT}
              canAdminister={canAdminister}
              submittingMedicationId={submittingMedicationId}
              onRecordOutcome={submitMedicationOutcome}
              onConfirmMeal={submitMealConfirmation}
            />

            <RoundSection
              slot="OTHER"
              medications={schedule.OTHER}
              canAdminister={canAdminister}
              submittingMedicationId={submittingMedicationId}
              onRecordOutcome={submitMedicationOutcome}
              onConfirmMeal={submitMealConfirmation}
            />

            <Text style={styles.groupHeading}>
              Administration History
            </Text>

            {history.length === 0 ? (
              <View style={styles.section}>
                <Text style={styles.emptySectionText}>
                  No medication administration
                  history recorded.
                </Text>
              </View>
            ) : (
              history.map((record) => (
                <HistoryCard
                  key={record.id}
                  record={record}
                />
              ))
            )}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f5f7fb",
  },

  container: {
    flex: 1,
    paddingHorizontal: 16,
  },

  content: {
    paddingBottom: 32,
  },

  center: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
  },

  pageTitle: {
    fontSize: 28,
    fontWeight: "700",
    color: "#172033",
    marginTop: 8,
  },

  pageSubtitle: {
    fontSize: 14,
    color: "#687386",
    lineHeight: 20,
    marginTop: 6,
    marginBottom: 16,
  },

  loadingText: {
    marginTop: 12,
    color: "#687386",
  },

  loadingBlock: {
    paddingVertical: 40,
    alignItems: "center",
  },

  searchInput: {
    backgroundColor: "#ffffff",
    borderWidth: 1,
    borderColor: "#d9dfeb",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 16,
    color: "#172033",
  },

  residentCount: {
    marginTop: 12,
    marginBottom: 8,
    color: "#687386",
    fontSize: 13,
  },

  residentList: {
    paddingBottom: 24,
  },

  residentCard: {
    backgroundColor: "#ffffff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 10,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    borderWidth: 1,
    borderColor: "#e5e9f0",
  },

  residentName: {
    fontSize: 16,
    fontWeight: "700",
    color: "#172033",
  },

  residentMeta: {
    marginTop: 4,
    fontSize: 13,
    color: "#687386",
  },

  openText: {
    fontWeight: "600",
    color: "#3157a4",
  },

  changeResidentButton: {
    alignSelf: "flex-start",
    paddingVertical: 8,
    marginTop: 2,
  },

  changeResidentText: {
    color: "#3157a4",
    fontWeight: "600",
  },

  permissionBox: {
    backgroundColor: "#edf7ef",
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },

  permissionWarning: {
    backgroundColor: "#fff3e8",
  },

  permissionText: {
    color: "#354052",
    fontSize: 13,
    lineHeight: 19,
  },

  groupHeading: {
    fontSize: 20,
    fontWeight: "700",
    color: "#172033",
    marginTop: 8,
    marginBottom: 10,
  },

  section: {
    backgroundColor: "#ffffff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: "#e5e9f0",
  },

  sectionHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },

  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#172033",
  },

  sectionCount: {
    minWidth: 26,
    textAlign: "center",
    backgroundColor: "#eef2f8",
    borderRadius: 13,
    paddingHorizontal: 8,
    paddingVertical: 3,
    color: "#4f5b6d",
    fontWeight: "600",
  },

  medicationCard: {
    borderTopWidth: 1,
    borderTopColor: "#edf0f5",
    paddingTop: 12,
    marginTop: 4,
    marginBottom: 8,
  },

  cardTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    gap: 10,
  },

  cardTitleArea: {
    flex: 1,
  },

  medicineName: {
    fontSize: 16,
    fontWeight: "700",
    color: "#172033",
  },

  medicineMeta: {
    marginTop: 3,
    color: "#687386",
    fontSize: 13,
  },

  statusBadge: {
    backgroundColor: "#eef2f8",
    borderRadius: 10,
    paddingHorizontal: 8,
    paddingVertical: 4,
  },

  statusText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#354052",
  },

  detailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    gap: 12,
    marginTop: 9,
  },

  detailLabel: {
    color: "#687386",
    fontSize: 13,
  },

  detailValue: {
    flex: 1,
    textAlign: "right",
    color: "#172033",
    fontSize: 13,
    fontWeight: "500",
  },

  noteBox: {
    marginTop: 10,
    backgroundColor: "#f7f8fb",
    borderRadius: 10,
    padding: 10,
  },

  noteLabel: {
    fontSize: 12,
    fontWeight: "700",
    color: "#687386",
  },

  noteText: {
    marginTop: 3,
    color: "#354052",
    fontSize: 13,
    lineHeight: 18,
  },

  mealRow: {
    marginTop: 10,
  },

  mealText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#354052",
  },

  remarks: {
    marginTop: 8,
    color: "#687386",
    fontSize: 13,
    lineHeight: 18,
  },

  emptySectionText: {
    color: "#7a8495",
    fontSize: 14,
    paddingVertical: 6,
  },

  historyCard: {
    backgroundColor: "#ffffff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#e5e9f0",
  },

  historyMedicine: {
    fontSize: 15,
    fontWeight: "700",
    color: "#172033",
  },

  historyDate: {
    marginTop: 3,
    fontSize: 12,
    color: "#687386",
  },

  historyMeta: {
    marginTop: 7,
    fontSize: 13,
    color: "#4f5b6d",
  },

  errorBox: {
    backgroundColor: "#fff0f0",
    borderRadius: 12,
    padding: 12,
    marginBottom: 14,
  },

  errorText: {
    color: "#8a2d2d",
    lineHeight: 19,
  },

  retryButton: {
    alignSelf: "flex-start",
    marginTop: 10,
    backgroundColor: "#ffffff",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },

  retryButtonText: {
    color: "#3157a4",
    fontWeight: "600",
  },

  emptyText: {
    textAlign: "center",
    color: "#7a8495",
    paddingVertical: 30,
  },
});