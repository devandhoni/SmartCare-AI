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

import { getToday } from "../../api/todayApi";
import MedicationRoundsSection from "../../components/today/MedicationRoundsSection";
import CareActivitiesSection from "../../components/today/CareActivitiesSection";
import DueChecksSection from "../../components/today/DueChecksSection";
import ResidentMovementSection from "../../components/today/ResidentMovementSection";
import NeedsAttentionSection from "../../components/today/NeedsAttentionSection";
import QuickActionsSection from "../../components/today/QuickActionsSection";
import { useNavigation } from "@react-navigation/native";
import type {
  TodayResponse,
  TodaySummary,
} from "../../types/today";

export default function TodayScreen() {
    const navigation = useNavigation<any>();
  const [data, setData] =
    useState<TodayResponse | null>(null);

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] =
    useState(false);

  const [error, setError] = useState("");
  const [lastUpdated, setLastUpdated] =
    useState<Date | null>(null);

  const loadToday = useCallback(
    async (silent = false) => {
      if (silent) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }

      setError("");

      try {
        const result = await getToday();

        setData(result);
        setLastUpdated(new Date());
      } catch (err: any) {
        console.error(
          "Failed to load Today dashboard:",
          err
        );

        setError(
          err?.response?.data?.message ||
            "Unable to load today's care overview."
        );
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    []
  );

  useEffect(() => {
    loadToday();
  }, [loadToday]);

  if (loading) {
    return (
      <SafeAreaView
        style={styles.safeArea}
        edges={["bottom"]}
      >
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" />

          <Text style={styles.loadingText}>
            Loading today's care overview...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  const summary = data?.summary;

  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["bottom"]}
    >
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadToday(true)}
          />
        }
      >
        <View style={styles.header}>
          <View style={styles.headerText}>
            <Text style={styles.eyebrow}>
              DAILY CARE
            </Text>

            <Text style={styles.title}>
              Today
            </Text>

            <Text style={styles.date}>
              {formatDashboardDate(data?.date)}
            </Text>

            {lastUpdated ? (
              <Text style={styles.lastUpdated}>
                Last updated{" "}
                {formatTime(lastUpdated)}
              </Text>
            ) : null}
          </View>

          <Pressable
            disabled={refreshing}
            onPress={() => loadToday(true)}
            style={({ pressed }) => [
              styles.refreshButton,
              pressed && styles.buttonPressed,
              refreshing &&
                styles.buttonDisabled,
            ]}
          >
            <Text style={styles.refreshText}>
              {refreshing
                ? "Refreshing..."
                : "Refresh"}
            </Text>
          </Pressable>
        </View>

        {error ? (
          <View style={styles.errorCard}>
            <Text style={styles.errorTitle}>
              Unable to load Today
            </Text>

            <Text style={styles.errorMessage}>
              {error}
            </Text>

            <Pressable
              onPress={() => loadToday()}
              style={({ pressed }) => [
                styles.retryButton,
                pressed && styles.buttonPressed,
              ]}
            >
              <Text style={styles.retryText}>
                Try Again
              </Text>
            </Pressable>
          </View>
        ) : null}

        {summary ? (
          <SummarySection summary={summary} />
        ) : null}

        {data ? (
  <MedicationRoundsSection
    rounds={data.medication_rounds}
    onOpenMedication={() => navigation.navigate("Medication")}
  />
) : null}

{data ? (
  <CareActivitiesSection
    due={data.care_activities.due}
    overdue={data.care_activities.overdue}
    onOpenTasks={() =>
      navigation.navigate("NurseTasks")
    }
    onOpenCareRecords={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "CareRecords",
          title: "Care Records",
        })
    }
  />
) : null}

{data ? (
  <DueChecksSection
    weeklyVitals={data.due_checks.weekly_vitals}
    monthlyGlucose={data.due_checks.monthly_glucose}
    onOpenWeeklyVitals={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "WeeklyVitals",
          title: "Weekly Vitals",
        })
    }
    onOpenMonthlyGlucose={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "MonthlyGlucose",
          title: "Monthly Glucose",
        })
    }
  />
) : null}

{data ? (
  <ResidentMovementSection
    homeLeave={data.home_leave}
    onOpenHomeLeave={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "HomeLeave",
          title: "Home Leave",
        })
    }
  />
) : null}

{data ? (
  <NeedsAttentionSection
    tasks={data.tasks}
    onOpenTasks={() =>
      navigation.navigate("NurseTasks")
    }
  />
) : null}

{data ? (
  <QuickActionsSection
    onResidents={() =>
      navigation.navigate("Residents")
    }
    onMedication={() =>
      navigation.navigate("Medication")
    }
    onTasks={() =>
      navigation.navigate("NurseTasks")
    }
    onCareRecords={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "CareRecords",
          title: "Care Records",
        })
    }
    onWeeklyVitals={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "WeeklyVitals",
          title: "Weekly Vitals",
        })
    }
    onMonthlyGlucose={() =>
      navigation
        .getParent()
        ?.navigate("Module", {
          module: "MonthlyGlucose",
          title: "Monthly Glucose",
        })
    }
  />
) : null}



      </ScrollView>
    </SafeAreaView>
  );
}



function SummarySection({
  summary,
}: {
  summary: TodaySummary;
}) {
  const medicationAttention =
    summary.medication_overdue +
    summary.medication_exceptions +
    summary.medication_meals_pending;

  const checksDue =
    summary.weekly_vitals_due +
    summary.monthly_glucose_due;

  return (
    <View style={styles.summaryGrid}>
      <SummaryCard
        label="Active Residents"
        value={summary.active_residents}
        description="Currently in care"
      />

      <SummaryCard
        label="Pending Tasks"
        value={summary.pending_tasks}
        description={
          summary.overdue_tasks > 0
            ? `${summary.overdue_tasks} overdue`
            : "No overdue tasks"
        }
        attention={summary.overdue_tasks > 0}
      />

      <SummaryCard
        label="Care Activities"
        value={
          summary.due_care_tasks +
          summary.overdue_care_tasks
        }
        description={
          summary.overdue_care_tasks > 0
            ? `${summary.overdue_care_tasks} overdue`
            : `${summary.due_care_tasks} due today`
        }
        attention={
          summary.overdue_care_tasks > 0
        }
      />

      <SummaryCard
        label="Medication"
        value={summary.medication_scheduled}
        description={
          summary.medication_overdue > 0
            ? `${summary.medication_overdue} overdue`
            : summary.medication_meals_pending > 0
              ? `${summary.medication_meals_pending} meal confirmation pending`
              : `${summary.medication_completed} administered`
        }
        attention={medicationAttention > 0}
      />

      <SummaryCard
        label="Critical Alerts"
        value={summary.critical_alerts}
        description="Open critical alerts"
        attention={summary.critical_alerts > 0}
      />

      <SummaryCard
        label="Checks Due"
        value={checksDue}
        description="Vitals and glucose"
        attention={checksDue > 0}
      />
    </View>
  );
}

function SummaryCard({
  label,
  value,
  description,
  attention = false,
}: {
  label: string;
  value: number;
  description: string;
  attention?: boolean;
}) {
  return (
    <View
      style={[
        styles.summaryCard,
        attention && styles.summaryCardAttention,
      ]}
    >
      <Text style={styles.summaryLabel}>
        {label}
      </Text>

      <Text style={styles.summaryValue}>
        {value}
      </Text>

      <Text
        style={[
          styles.summaryDescription,
          attention &&
            styles.summaryDescriptionAttention,
        ]}
      >
        {description}
      </Text>
    </View>
  );
}

function formatDashboardDate(
  value?: string
): string {
  if (!value) {
    return "Today's care overview";
  }

  const date = new Date(`${value}T00:00:00`);

  return date.toLocaleDateString(undefined, {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

function formatTime(date: Date): string {
  return date.toLocaleTimeString(undefined, {
    hour: "numeric",
    minute: "2-digit",
  });
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f1f5f9",
  },

  content: {
    padding: 16,
    paddingBottom: 32,
  },

  loadingContainer: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
  },

  loadingText: {
    marginTop: 14,
    fontSize: 14,
    color: "#64748b",
  },

  header: {
    marginBottom: 20,
  },

  headerText: {
    marginBottom: 14,
  },

  eyebrow: {
    fontSize: 12,
    fontWeight: "700",
    letterSpacing: 1.5,
    color: "#94a3b8",
  },

  title: {
    marginTop: 4,
    fontSize: 30,
    fontWeight: "700",
    color: "#0f172a",
  },

  date: {
    marginTop: 7,
    fontSize: 14,
    color: "#64748b",
  },

  lastUpdated: {
    marginTop: 4,
    fontSize: 12,
    color: "#94a3b8",
  },

  refreshButton: {
    alignSelf: "flex-start",
    minHeight: 42,
    justifyContent: "center",
    paddingHorizontal: 18,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 12,
    backgroundColor: "#ffffff",
  },

  refreshText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#334155",
  },

  buttonPressed: {
    opacity: 0.7,
  },

  buttonDisabled: {
    opacity: 0.5,
  },

  errorCard: {
    marginBottom: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: "#fecaca",
    borderRadius: 14,
    backgroundColor: "#fef2f2",
  },

  errorTitle: {
    fontSize: 16,
    fontWeight: "700",
    color: "#991b1b",
  },

  errorMessage: {
    marginTop: 5,
    fontSize: 14,
    lineHeight: 20,
    color: "#b91c1c",
  },

  retryButton: {
    alignSelf: "flex-start",
    marginTop: 12,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 9,
    backgroundColor: "#b91c1c",
  },

  retryText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#ffffff",
  },

  summaryGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
    marginBottom: 8,
  },

  summaryCard: {
    width: "48.5%",
    minHeight: 130,
    marginBottom: 12,
    padding: 15,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  summaryCardAttention: {
    borderColor: "#fde68a",
    backgroundColor: "#fffbeb",
  },

  summaryLabel: {
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.5,
    color: "#64748b",
    textTransform: "uppercase",
  },

  summaryValue: {
    marginTop: 10,
    fontSize: 28,
    fontWeight: "700",
    color: "#0f172a",
  },

  summaryDescription: {
    marginTop: 5,
    fontSize: 12,
    lineHeight: 17,
    color: "#94a3b8",
  },

  summaryDescriptionAttention: {
    fontWeight: "600",
    color: "#b45309",
  },

  placeholderCard: {
    marginTop: 4,
    padding: 18,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  placeholderTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#0f172a",
  },

  placeholderText: {
    marginTop: 7,
    fontSize: 14,
    lineHeight: 21,
    color: "#64748b",
  },
});