import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

import type {
  TodayCareActivity,
} from "../../types/today";

interface CareActivitiesSectionProps {
  due: TodayCareActivity[];
  overdue: TodayCareActivity[];
  onOpenCareRecords: () => void;
  onOpenTasks: () => void;
}

export default function CareActivitiesSection({
  due,
  overdue,
  onOpenCareRecords,
  onOpenTasks,
}: CareActivitiesSectionProps) {
  const total = due.length + overdue.length;

  return (
    <View style={styles.panel}>
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={styles.title}>
            Care Activities
          </Text>

          <Text style={styles.description}>
            Scheduled resident care requiring attention today.
          </Text>
        </View>

        <Pressable onPress={onOpenTasks}>
          <Text style={styles.link}>
            Tasks ›
          </Text>
        </Pressable>
      </View>

      <View style={styles.content}>
        {total === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyTitle}>
              No care activities due
            </Text>

            <Text style={styles.emptyText}>
              There are no outstanding scheduled care
              activities for today.
            </Text>
          </View>
        ) : (
          <>
            {overdue.length > 0 ? (
              <ActivityGroup
                title="Overdue"
                items={overdue}
                critical
                onOpenTasks={onOpenTasks}
              />
            ) : null}

            {due.length > 0 ? (
              <ActivityGroup
                title="Due Today"
                items={due}
                onOpenTasks={onOpenTasks}
              />
            ) : null}
          </>
        )}

        <Pressable
          onPress={onOpenCareRecords}
          style={({ pressed }) => [
            styles.careRecordsButton,
            pressed && styles.buttonPressed,
          ]}
        >
          <Text style={styles.careRecordsText}>
            Open Care Records
          </Text>
        </Pressable>
      </View>
    </View>
  );
}

function ActivityGroup({
  title,
  items,
  critical = false,
  onOpenTasks,
}: {
  title: string;
  items: TodayCareActivity[];
  critical?: boolean;
  onOpenTasks: () => void;
}) {
  return (
    <View style={styles.group}>
      <View style={styles.groupHeader}>
        <Text
          style={[
            styles.groupTitle,
            critical && styles.criticalText,
          ]}
        >
          {title}
        </Text>

        <View
          style={[
            styles.countBadge,
            critical && styles.criticalBadge,
          ]}
        >
          <Text
            style={[
              styles.countText,
              critical && styles.criticalText,
            ]}
          >
            {items.length}
          </Text>
        </View>
      </View>

      {items.slice(0, 5).map((item) => (
        <ActivityItem
          key={item.id}
          item={item}
          onOpenTasks={onOpenTasks}
        />
      ))}

      {items.length > 5 ? (
        <Text style={styles.moreText}>
          +{items.length - 5} more
        </Text>
      ) : null}
    </View>
  );
}

function ActivityItem({
  item,
  onOpenTasks,
}: {
  item: TodayCareActivity;
  onOpenTasks: () => void;
}) {
  return (
    <View
      style={[
        styles.activity,
        item.overdue && styles.overdueActivity,
      ]}
    >
      <View style={styles.activityTop}>
        <View style={styles.activityText}>
          <Text
            style={styles.resident}
            numberOfLines={1}
          >
            {item.resident || "Resident"}
          </Text>

          <Text
            style={styles.taskName}
            numberOfLines={2}
          >
            {item.task_name}
          </Text>
        </View>

        <View
          style={[
            styles.statusBadge,
            item.overdue
              ? styles.overdueBadge
              : styles.dueBadge,
          ]}
        >
          <Text
            style={[
              styles.statusText,
              item.overdue
                ? styles.overdueText
                : styles.dueText,
            ]}
          >
            {item.overdue ? "Overdue" : "Due"}
          </Text>
        </View>
      </View>

      <View style={styles.meta}>
        {item.scheduled_time ? (
          <Text style={styles.metaText}>
            {formatTaskTime(item.scheduled_time)}
          </Text>
        ) : null}

        {item.priority ? (
          <Text style={styles.metaText}>
            {formatLabel(item.priority)}
          </Text>
        ) : null}

        {item.source_type ? (
          <Text style={styles.metaText}>
            {formatLabel(item.source_type)}
          </Text>
        ) : null}
      </View>

      {item.description ? (
        <Text
          style={styles.activityDescription}
          numberOfLines={2}
        >
          {item.description}
        </Text>
      ) : null}

      <Pressable onPress={onOpenTasks}>
        <Text style={styles.itemLink}>
          Open Tasks ›
        </Text>
      </Pressable>
    </View>
  );
}

function formatTaskTime(value: string): string {
  const parts = String(value).split(":");

  if (parts.length < 2) {
    return value;
  }

  const date = new Date();

  date.setHours(
    Number(parts[0]),
    Number(parts[1]),
    Number(parts[2] || 0),
    0
  );

  return date.toLocaleTimeString(undefined, {
    hour: "numeric",
    minute: "2-digit",
  });
}

function formatLabel(value: string): string {
  return String(value)
    .replace(/_/g, " ")
    .toLowerCase()
    .replace(/\b\w/g, (character) =>
      character.toUpperCase()
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

  group: {
    marginBottom: 14,
  },

  groupHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 9,
  },

  groupTitle: {
    fontSize: 13,
    fontWeight: "700",
    color: "#475569",
  },

  countBadge: {
    marginLeft: 8,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
    backgroundColor: "#e2e8f0",
  },

  countText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#475569",
  },

  criticalBadge: {
    backgroundColor: "#fee2e2",
  },

  criticalText: {
    color: "#b91c1c",
  },

  activity: {
    marginBottom: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 11,
    backgroundColor: "#f8fafc",
  },

  overdueActivity: {
    borderColor: "#fecaca",
    backgroundColor: "#fef2f2",
  },

  activityTop: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
  },

  activityText: {
    flex: 1,
    marginRight: 8,
  },

  resident: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1e293b",
  },

  taskName: {
    marginTop: 3,
    fontSize: 13,
    lineHeight: 18,
    color: "#475569",
  },

  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },

  dueBadge: {
    backgroundColor: "#dbeafe",
  },

  overdueBadge: {
    backgroundColor: "#fee2e2",
  },

  statusText: {
    fontSize: 10,
    fontWeight: "700",
  },

  dueText: {
    color: "#1d4ed8",
  },

  overdueText: {
    color: "#b91c1c",
  },

  meta: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginTop: 8,
    gap: 8,
  },

  metaText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#64748b",
  },

  activityDescription: {
    marginTop: 8,
    fontSize: 12,
    lineHeight: 18,
    color: "#64748b",
  },

  itemLink: {
    marginTop: 9,
    fontSize: 12,
    fontWeight: "700",
    color: "#2563eb",
  },

  moreText: {
    marginTop: 2,
    fontSize: 12,
    fontWeight: "600",
    color: "#64748b",
  },

  empty: {
    paddingVertical: 14,
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

  careRecordsButton: {
    alignItems: "center",
    justifyContent: "center",
    minHeight: 44,
    marginTop: 2,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 11,
    backgroundColor: "#ffffff",
  },

  careRecordsText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#334155",
  },

  buttonPressed: {
    opacity: 0.7,
  },
});