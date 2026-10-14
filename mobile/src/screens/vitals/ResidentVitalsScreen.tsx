import React, {
  useCallback,
  useEffect,
  useState,
} from "react";
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import {
  useNavigation,
  useRoute,
} from "@react-navigation/native";

import { getResidentVitals } from "../../api/vitalApi";
import type { VitalSign } from "../../types/vital";

export default function ResidentVitalsScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const residentId = Number(route.params?.residentId);

  const [vitals, setVitals] = useState<VitalSign[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");

  const loadVitals = useCallback(
    async (isRefresh = false) => {
      if (!residentId) {
        setError("Resident information is unavailable.");
        setLoading(false);
        return;
      }

      try {
        if (isRefresh) {
          setRefreshing(true);
        } else {
          setLoading(true);
        }

        setError("");

        const rows = await getResidentVitals(residentId);
        setVitals(rows);
      } catch (requestError) {
        console.error(
          "Failed to load resident vitals:",
          requestError
        );

        setError("Unable to load vital signs.");
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [residentId]
  );

  useEffect(() => {
    loadVitals();
  }, [loadVitals]);

  if (loading) {
    return (
      <SafeAreaView
        style={styles.safeArea}
        edges={["bottom"]}
      >
        <View style={styles.centerState}>
          <ActivityIndicator size="large" />
          <Text style={styles.stateText}>
            Loading vital signs...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["bottom"]}
    >
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadVitals(true)}
          />
        }
      >
        <View style={styles.header}>
          <View style={styles.headerText}>
            <Text style={styles.eyebrow}>
              Clinical Information
            </Text>

            <Text style={styles.title}>
              Vital Signs
            </Text>

            <Text style={styles.subtitle}>
              Review the resident's latest readings
              and vital-sign history.
            </Text>
          </View>

          <Pressable
            style={styles.recordButton}
            onPress={() =>
              navigation.navigate("RecordVitals", {
                residentId,
              })
            }
          >
            <Text style={styles.recordButtonText}>
              Record Vitals
            </Text>
          </Pressable>
        </View>

        {error ? (
          <View style={styles.errorCard}>
            <Text style={styles.errorText}>
              {error}
            </Text>

            <Pressable onPress={() => loadVitals()}>
              <Text style={styles.retryText}>
                Try Again
              </Text>
            </Pressable>
          </View>
        ) : null}

        {!error && vitals.length > 0 ? (
          <LatestVitals vital={vitals[0]} />
        ) : null}

        {!error ? (
          <View style={styles.historySection}>
            <Text style={styles.sectionTitle}>
              Vital History
            </Text>

            {vitals.length === 0 ? (
              <View style={styles.emptyState}>
                <Text style={styles.emptyTitle}>
                  No vital signs recorded
                </Text>

                <Text style={styles.stateText}>
                  This resident does not have any
                  recorded vital signs yet.
                </Text>
              </View>
            ) : (
              vitals.map((vital) => (
                <VitalHistoryCard
                  key={vital.id}
                  vital={vital}
                />
              ))
            )}
          </View>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

function LatestVitals({
  vital,
}: {
  vital: VitalSign;
}) {
  return (
    <View style={styles.latestSection}>
      <View style={styles.sectionHeadingRow}>
        <Text style={styles.sectionTitle}>
          Latest Reading
        </Text>

        <Text style={styles.dateText}>
          {formatDate(vital)}
        </Text>
      </View>

      <View style={styles.metricGrid}>
        <Metric
          label="Blood Pressure"
          value={formatBloodPressure(vital)}
          unit="mmHg"
        />

        <Metric
          label="Pulse"
          value={display(vital.heart_rate)}
          unit="bpm"
        />

        <Metric
          label="SpO₂"
          value={display(vital.oxygen_level)}
          unit="%"
        />

        <Metric
          label="Glucose"
          value={display(vital.blood_glucose)}
        />

        <Metric
          label="Temperature"
          value={display(vital.temperature)}
          unit="°C"
        />

        <Metric
          label="Weight"
          value={display(vital.weight)}
          unit="kg"
        />
      </View>
    </View>
  );
}

function VitalHistoryCard({
  vital,
}: {
  vital: VitalSign;
}) {
  return (
    <View style={styles.historyCard}>
      <View style={styles.historyHeader}>
        <Text style={styles.historyDate}>
          {formatDate(vital)}
        </Text>

        {vital.record_source ? (
          <View style={styles.sourceBadge}>
            <Text style={styles.sourceText}>
              {String(vital.record_source)}
            </Text>
          </View>
        ) : null}
      </View>

      <View style={styles.historyGrid}>
        <HistoryValue
          label="BP"
          value={formatBloodPressure(vital)}
        />

        <HistoryValue
          label="Pulse"
          value={display(vital.heart_rate)}
        />

        <HistoryValue
          label="SpO₂"
          value={display(vital.oxygen_level)}
        />

        <HistoryValue
          label="Glucose"
          value={display(vital.blood_glucose)}
        />

        <HistoryValue
          label="Temp"
          value={display(vital.temperature)}
        />

        <HistoryValue
          label="Weight"
          value={display(vital.weight)}
        />
      </View>
    </View>
  );
}

function Metric({
  label,
  value,
  unit,
}: {
  label: string;
  value: string;
  unit?: string;
}) {
  return (
    <View style={styles.metricCard}>
      <Text style={styles.metricLabel}>
        {label}
      </Text>

      <Text style={styles.metricValue}>
        {value}
      </Text>

      {unit && value !== "-" ? (
        <Text style={styles.metricUnit}>
          {unit}
        </Text>
      ) : null}
    </View>
  );
}

function HistoryValue({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <View style={styles.historyValue}>
      <Text style={styles.historyLabel}>
        {label}
      </Text>

      <Text style={styles.historyNumber}>
        {value}
      </Text>
    </View>
  );
}

function display(
  value: number | null | undefined
): string {
  return value === null || value === undefined
    ? "-"
    : String(value);
}

function formatBloodPressure(
  vital: VitalSign
): string {
  if (
    vital.blood_pressure_systolic === null ||
    vital.blood_pressure_systolic === undefined ||
    vital.blood_pressure_diastolic === null ||
    vital.blood_pressure_diastolic === undefined
  ) {
    return "-";
  }

  return `${vital.blood_pressure_systolic}/${vital.blood_pressure_diastolic}`;
}

function formatDate(vital: VitalSign): string {
  const value =
    vital.recorded_at ||
    vital.created_on ||
    vital.created_at;

  if (!value) {
    return "-";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString("en-MY", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f8fafc",
  },

  content: {
    padding: 16,
    paddingBottom: 36,
  },

  centerState: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 28,
  },

  stateText: {
    marginTop: 9,
    textAlign: "center",
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  header: {
    padding: 18,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  headerText: {
    flex: 1,
  },

  eyebrow: {
    fontSize: 13,
    fontWeight: "700",
    color: "#2563eb",
  },

  title: {
    marginTop: 4,
    fontSize: 25,
    fontWeight: "800",
    color: "#0f172a",
  },

  subtitle: {
    marginTop: 7,
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  recordButton: {
    alignSelf: "flex-start",
    marginTop: 16,
    paddingHorizontal: 15,
    paddingVertical: 11,
    borderRadius: 11,
    backgroundColor: "#2563eb",
  },

  recordButtonText: {
    fontSize: 13,
    fontWeight: "800",
    color: "#ffffff",
  },

  errorCard: {
    marginTop: 14,
    padding: 15,
    borderWidth: 1,
    borderColor: "#fecaca",
    borderRadius: 14,
    backgroundColor: "#fef2f2",
  },

  errorText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#b91c1c",
  },

  retryText: {
    marginTop: 9,
    fontSize: 13,
    fontWeight: "800",
    color: "#991b1b",
  },

  latestSection: {
    marginTop: 14,
    padding: 15,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  sectionHeadingRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 10,
  },

  sectionTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#0f172a",
  },

  dateText: {
    flexShrink: 1,
    textAlign: "right",
    fontSize: 10,
    color: "#64748b",
  },

  metricGrid: {
    marginTop: 13,
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
  },

  metricCard: {
    width: "48.5%",
    minHeight: 100,
    marginBottom: 9,
    padding: 13,
    justifyContent: "center",
    borderRadius: 12,
    backgroundColor: "#f8fafc",
  },

  metricLabel: {
    fontSize: 10,
    fontWeight: "700",
    textTransform: "uppercase",
    color: "#64748b",
  },

  metricValue: {
    marginTop: 5,
    fontSize: 20,
    fontWeight: "800",
    color: "#0f172a",
  },

  metricUnit: {
    marginTop: 2,
    fontSize: 10,
    color: "#94a3b8",
  },

  historySection: {
    marginTop: 14,
  },

  historyCard: {
    marginTop: 10,
    padding: 14,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 14,
    backgroundColor: "#ffffff",
  },

  historyHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 8,
  },

  historyDate: {
    flex: 1,
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
  },

  sourceBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: "#eff6ff",
  },

  sourceText: {
    fontSize: 9,
    fontWeight: "700",
    color: "#1d4ed8",
  },

  historyGrid: {
    marginTop: 12,
    flexDirection: "row",
    flexWrap: "wrap",
  },

  historyValue: {
    width: "33.33%",
    marginBottom: 12,
  },

  historyLabel: {
    fontSize: 9,
    fontWeight: "700",
    textTransform: "uppercase",
    color: "#94a3b8",
  },

  historyNumber: {
    marginTop: 3,
    fontSize: 13,
    fontWeight: "700",
    color: "#334155",
  },

  emptyState: {
    marginTop: 10,
    minHeight: 180,
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 14,
    backgroundColor: "#ffffff",
  },

  emptyTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#334155",
  },
});