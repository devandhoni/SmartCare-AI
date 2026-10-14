import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

import type {
  TodayDueCheck,
} from "../../types/today";

interface DueChecksSectionProps {
  weeklyVitals: TodayDueCheck[];
  monthlyGlucose: TodayDueCheck[];
  onOpenWeeklyVitals: () => void;
  onOpenMonthlyGlucose: () => void;
}

export default function DueChecksSection({
  weeklyVitals,
  monthlyGlucose,
  onOpenWeeklyVitals,
  onOpenMonthlyGlucose,
}: DueChecksSectionProps) {
  const total =
    weeklyVitals.length + monthlyGlucose.length;

  return (
    <View style={styles.panel}>
      <View style={styles.header}>
        <Text style={styles.title}>
          Due Checks
        </Text>

        <Text style={styles.description}>
          Routine clinical checks still due for active
          residents.
        </Text>
      </View>

      <View style={styles.content}>
        {total === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyTitle}>
              All routine checks are up to date
            </Text>

            <Text style={styles.emptyText}>
              No weekly vitals or monthly glucose checks
              are currently due.
            </Text>
          </View>
        ) : (
          <>
            <CheckGroup
              title="Weekly Vitals"
              description="Residents without a weekly vitals check this week."
              items={weeklyVitals}
              buttonLabel="Open Weekly Vitals"
              onOpen={onOpenWeeklyVitals}
            />

            <CheckGroup
              title="Monthly Glucose"
              description="Residents without a monthly glucose check this month."
              items={monthlyGlucose}
              buttonLabel="Open Monthly Glucose"
              onOpen={onOpenMonthlyGlucose}
            />
          </>
        )}
      </View>
    </View>
  );
}

function CheckGroup({
  title,
  description,
  items,
  buttonLabel,
  onOpen,
}: {
  title: string;
  description: string;
  items: TodayDueCheck[];
  buttonLabel: string;
  onOpen: () => void;
}) {
  return (
    <View style={styles.checkCard}>
      <View style={styles.checkHeader}>
        <View style={styles.checkHeaderText}>
          <Text style={styles.checkTitle}>
            {title}
          </Text>

          <Text style={styles.checkDescription}>
            {description}
          </Text>
        </View>

        <View
          style={[
            styles.countBadge,
            items.length > 0
              ? styles.countBadgeDue
              : styles.countBadgeComplete,
          ]}
        >
          <Text
            style={[
              styles.countText,
              items.length > 0
                ? styles.countTextDue
                : styles.countTextComplete,
            ]}
          >
            {items.length}
          </Text>
        </View>
      </View>

      {items.length === 0 ? (
        <Text style={styles.completeText}>
          Up to date
        </Text>
      ) : (
        <View style={styles.residents}>
          {items.slice(0, 5).map((item) => (
            <View
              key={item.resident_id}
              style={styles.residentRow}
            >
              <View style={styles.residentDot} />

              <Text
                style={styles.residentName}
                numberOfLines={1}
              >
                {item.resident || "Resident"}
              </Text>

              <Text style={styles.dueText}>
                Due
              </Text>
            </View>
          ))}

          {items.length > 5 ? (
            <Text style={styles.moreText}>
              +{items.length - 5} more residents
            </Text>
          ) : null}
        </View>
      )}

      <Pressable
        onPress={onOpen}
        style={({ pressed }) => [
          styles.openButton,
          pressed && styles.buttonPressed,
        ]}
      >
        <Text style={styles.openButtonText}>
          {buttonLabel}
        </Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  panel: {
    marginTop: 12,
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

  checkCard: {
    marginBottom: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 14,
    backgroundColor: "#f8fafc",
  },

  checkHeader: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
  },

  checkHeaderText: {
    flex: 1,
    marginRight: 12,
  },

  checkTitle: {
    fontSize: 15,
    fontWeight: "700",
    color: "#1e293b",
  },

  checkDescription: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: "#64748b",
  },

  countBadge: {
    minWidth: 30,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },

  countBadgeDue: {
    backgroundColor: "#fef3c7",
  },

  countBadgeComplete: {
    backgroundColor: "#d1fae5",
  },

  countText: {
    fontSize: 12,
    fontWeight: "700",
  },

  countTextDue: {
    color: "#92400e",
  },

  countTextComplete: {
    color: "#047857",
  },

  residents: {
    marginTop: 12,
  },

  residentRow: {
    flexDirection: "row",
    alignItems: "center",
    minHeight: 38,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
  },

  residentDot: {
    width: 7,
    height: 7,
    marginRight: 9,
    borderRadius: 999,
    backgroundColor: "#f59e0b",
  },

  residentName: {
    flex: 1,
    fontSize: 13,
    fontWeight: "600",
    color: "#334155",
  },

  dueText: {
    marginLeft: 8,
    fontSize: 11,
    fontWeight: "700",
    color: "#b45309",
  },

  moreText: {
    marginTop: 9,
    fontSize: 12,
    fontWeight: "600",
    color: "#64748b",
  },

  completeText: {
    marginTop: 12,
    fontSize: 12,
    fontWeight: "700",
    color: "#047857",
  },

  openButton: {
    alignItems: "center",
    justifyContent: "center",
    minHeight: 42,
    marginTop: 14,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 10,
    backgroundColor: "#ffffff",
  },

  openButtonText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#334155",
  },

  empty: {
    paddingVertical: 14,
  },

  emptyTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#047857",
  },

  emptyText: {
    marginTop: 5,
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  buttonPressed: {
    opacity: 0.7,
  },
});