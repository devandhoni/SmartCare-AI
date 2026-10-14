import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

interface QuickActionsSectionProps {
  onResidents: () => void;
  onMedication: () => void;
  onTasks: () => void;
  onCareRecords: () => void;
  onWeeklyVitals: () => void;
  onMonthlyGlucose: () => void;
}

export default function QuickActionsSection({
  onResidents,
  onMedication,
  onTasks,
  onCareRecords,
  onWeeklyVitals,
  onMonthlyGlucose,
}: QuickActionsSectionProps) {
  return (
    <View style={styles.panel}>
      <View style={styles.header}>
        <Text style={styles.title}>
          Quick Actions
        </Text>

        <Text style={styles.description}>
          Jump directly to common daily care workflows.
        </Text>
      </View>

      <View style={styles.content}>
        <View style={styles.grid}>
          <ActionButton
            title="Residents"
            subtitle="Resident records"
            onPress={onResidents}
          />

          <ActionButton
            title="Medication"
            subtitle="Medication rounds"
            onPress={onMedication}
          />

          <ActionButton
            title="Nurse Tasks"
            subtitle="Outstanding work"
            onPress={onTasks}
          />

          <ActionButton
            title="Care Records"
            subtitle="Record daily care"
            onPress={onCareRecords}
          />

          <ActionButton
            title="Weekly Vitals"
            subtitle="Routine observations"
            onPress={onWeeklyVitals}
          />

          <ActionButton
            title="Monthly Glucose"
            subtitle="Glucose checks"
            onPress={onMonthlyGlucose}
          />
        </View>
      </View>
    </View>
  );
}

function ActionButton({
  title,
  subtitle,
  onPress,
}: {
  title: string;
  subtitle: string;
  onPress: () => void;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.action,
        pressed && styles.actionPressed,
      ]}
    >
      <Text style={styles.actionTitle}>
        {title}
      </Text>

      <Text style={styles.actionSubtitle}>
        {subtitle}
      </Text>

      <Text style={styles.actionArrow}>
        Open ›
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  panel: {
    marginTop: 12,
    marginBottom: 8,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  header: {
    padding: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
  },

  title: {
    fontSize: 17,
    fontWeight: "700",
    color: "#0f172a",
  },

  description: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  content: {
    padding: 14,
  },

  grid: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
  },

  action: {
    width: "48.5%",
    minHeight: 118,
    marginBottom: 10,
    padding: 14,
    justifyContent: "space-between",
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 13,
    backgroundColor: "#f8fafc",
  },

  actionPressed: {
    opacity: 0.65,
  },

  actionTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1e293b",
  },

  actionSubtitle: {
    marginTop: 5,
    fontSize: 11,
    lineHeight: 16,
    color: "#64748b",
  },

  actionArrow: {
    marginTop: 12,
    fontSize: 12,
    fontWeight: "700",
    color: "#2563eb",
  },
});