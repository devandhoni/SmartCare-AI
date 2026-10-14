import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

import type {
  TodayHomeLeave,
} from "../../types/today";

interface ResidentMovementSectionProps {
  homeLeave: TodayHomeLeave[];
  onOpenHomeLeave: () => void;
}

export default function ResidentMovementSection({
  homeLeave,
  onOpenHomeLeave,
}: ResidentMovementSectionProps) {
  const overdueCount = homeLeave.filter(
    (item) => item.overdue
  ).length;

  return (
    <View style={styles.panel}>
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={styles.title}>
            Resident Movement
          </Text>

          <Text style={styles.description}>
            Residents currently recorded as away from
            the facility.
          </Text>
        </View>

        <Pressable onPress={onOpenHomeLeave}>
          <Text style={styles.link}>
            Home Leave ›
          </Text>
        </Pressable>
      </View>

      <View style={styles.content}>
        {homeLeave.length === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyTitle}>
              No residents currently on leave
            </Text>

            <Text style={styles.emptyText}>
              There are no active home leave records
              requiring attention.
            </Text>
          </View>
        ) : (
          <>
            <View style={styles.summaryRow}>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryValue}>
                  {homeLeave.length}
                </Text>

                <Text style={styles.summaryLabel}>
                  On leave
                </Text>
              </View>

              <View style={styles.summaryItem}>
                <Text
                  style={[
                    styles.summaryValue,
                    overdueCount > 0 &&
                      styles.overdueValue,
                  ]}
                >
                  {overdueCount}
                </Text>

                <Text style={styles.summaryLabel}>
                  Overdue return
                </Text>
              </View>
            </View>

            {homeLeave.slice(0, 5).map((item) => (
              <LeaveItem
                key={item.id}
                item={item}
              />
            ))}

            {homeLeave.length > 5 ? (
              <Text style={styles.moreText}>
                +{homeLeave.length - 5} more residents
              </Text>
            ) : null}
          </>
        )}

        <Pressable
          onPress={onOpenHomeLeave}
          style={({ pressed }) => [
            styles.openButton,
            pressed && styles.buttonPressed,
          ]}
        >
          <Text style={styles.openButtonText}>
            Open Home Leave
          </Text>
        </Pressable>
      </View>
    </View>
  );
}

function LeaveItem({
  item,
}: {
  item: TodayHomeLeave;
}) {
  return (
    <View
      style={[
        styles.leaveCard,
        item.overdue && styles.overdueCard,
      ]}
    >
      <View style={styles.leaveHeader}>
        <View style={styles.leaveHeaderText}>
          <Text
            style={styles.residentName}
            numberOfLines={1}
          >
            {item.resident || "Resident"}
          </Text>

          {item.leave_reference ? (
            <Text style={styles.reference}>
              {item.leave_reference}
            </Text>
          ) : null}
        </View>

        <View
          style={[
            styles.statusBadge,
            item.overdue
              ? styles.overdueBadge
              : styles.leaveBadge,
          ]}
        >
          <Text
            style={[
              styles.statusText,
              item.overdue
                ? styles.overdueText
                : styles.leaveText,
            ]}
          >
            {item.overdue
              ? "Return overdue"
              : "On leave"}
          </Text>
        </View>
      </View>

      {item.destination ? (
        <InfoRow
          label="Destination"
          value={item.destination}
        />
      ) : null}

      {item.reason ? (
        <InfoRow
          label="Reason"
          value={item.reason}
        />
      ) : null}

      {item.leave_at ? (
        <InfoRow
          label="Left"
          value={formatDateTime(item.leave_at)}
        />
      ) : null}

      {item.expected_return_at ? (
        <InfoRow
          label="Expected return"
          value={formatDateTime(
            item.expected_return_at
          )}
          attention={item.overdue}
        />
      ) : null}
    </View>
  );
}

function InfoRow({
  label,
  value,
  attention = false,
}: {
  label: string;
  value: string;
  attention?: boolean;
}) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>
        {label}
      </Text>

      <Text
        style={[
          styles.infoValue,
          attention && styles.overdueText,
        ]}
      >
        {value}
      </Text>
    </View>
  );
}

function formatDateTime(value: string): string {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString(undefined, {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
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

  headerText: {
    marginBottom: 10,
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

  link: {
    fontSize: 14,
    fontWeight: "700",
    color: "#2563eb",
  },

  content: {
    padding: 14,
  },

  empty: {
    paddingVertical: 12,
  },

  emptyTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#334155",
  },

  emptyText: {
    marginTop: 5,
    fontSize: 13,
    lineHeight: 19,
    color: "#94a3b8",
  },

  summaryRow: {
    flexDirection: "row",
    marginBottom: 12,
    gap: 10,
  },

  summaryItem: {
    flex: 1,
    padding: 12,
    borderRadius: 11,
    backgroundColor: "#f8fafc",
  },

  summaryValue: {
    fontSize: 22,
    fontWeight: "700",
    color: "#0f172a",
  },

  overdueValue: {
    color: "#b91c1c",
  },

  summaryLabel: {
    marginTop: 3,
    fontSize: 11,
    fontWeight: "600",
    color: "#64748b",
  },

  leaveCard: {
    marginBottom: 9,
    padding: 12,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 11,
    backgroundColor: "#f8fafc",
  },

  overdueCard: {
    borderColor: "#fecaca",
    backgroundColor: "#fef2f2",
  },

  leaveHeader: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
    marginBottom: 8,
  },

  leaveHeaderText: {
    flex: 1,
    marginRight: 8,
  },

  residentName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1e293b",
  },

  reference: {
    marginTop: 3,
    fontSize: 11,
    color: "#64748b",
  },

  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },

  leaveBadge: {
    backgroundColor: "#dbeafe",
  },

  overdueBadge: {
    backgroundColor: "#fee2e2",
  },

  statusText: {
    fontSize: 10,
    fontWeight: "700",
  },

  leaveText: {
    color: "#1d4ed8",
  },

  overdueText: {
    color: "#b91c1c",
  },

  infoRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    marginTop: 5,
  },

  infoLabel: {
    width: 105,
    fontSize: 11,
    fontWeight: "600",
    color: "#64748b",
  },

  infoValue: {
    flex: 1,
    fontSize: 11,
    lineHeight: 16,
    color: "#334155",
  },

  moreText: {
    marginTop: 2,
    fontSize: 12,
    fontWeight: "600",
    color: "#64748b",
  },

  openButton: {
    alignItems: "center",
    justifyContent: "center",
    minHeight: 44,
    marginTop: 12,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 11,
    backgroundColor: "#ffffff",
  },

  openButtonText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#334155",
  },

  buttonPressed: {
    opacity: 0.7,
  },
});