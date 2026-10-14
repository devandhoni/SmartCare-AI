import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

import type {
  TodayNurseTask,
} from "../../types/today";

interface NeedsAttentionSectionProps {
  tasks: TodayNurseTask[];
  onOpenTasks: () => void;
}

export default function NeedsAttentionSection({
  tasks,
  onOpenTasks,
}: NeedsAttentionSectionProps) {
  const overdueTasks = tasks.filter(
    (task) => task.overdue
  );

  const pendingTasks = tasks.filter(
    (task) => !task.overdue
  );

  return (
    <View style={styles.panel}>
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={styles.title}>
            Needs Attention
          </Text>

          <Text style={styles.description}>
            Outstanding nurse tasks requiring action
            today.
          </Text>
        </View>

        <Pressable onPress={onOpenTasks}>
          <Text style={styles.link}>
            Tasks ›
          </Text>
        </Pressable>
      </View>

      <View style={styles.content}>
        {tasks.length === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyTitle}>
              No outstanding tasks
            </Text>

            <Text style={styles.emptyText}>
              There are no pending nurse tasks requiring
              attention.
            </Text>
          </View>
        ) : (
          <>
            <View style={styles.summaryRow}>
              <SummaryBox
                value={tasks.length}
                label="Outstanding"
              />

              <SummaryBox
                value={overdueTasks.length}
                label="Overdue"
                attention={overdueTasks.length > 0}
              />
            </View>

            {overdueTasks.length > 0 ? (
              <TaskGroup
                title="Overdue"
                tasks={overdueTasks}
                onOpenTasks={onOpenTasks}
                critical
              />
            ) : null}

            {pendingTasks.length > 0 ? (
              <TaskGroup
                title="Pending"
                tasks={pendingTasks}
                onOpenTasks={onOpenTasks}
              />
            ) : null}
          </>
        )}

        <Pressable
          onPress={onOpenTasks}
          style={({ pressed }) => [
            styles.openButton,
            pressed && styles.buttonPressed,
          ]}
        >
          <Text style={styles.openButtonText}>
            Open Nurse Tasks
          </Text>
        </Pressable>
      </View>
    </View>
  );
}

function SummaryBox({
  value,
  label,
  attention = false,
}: {
  value: number;
  label: string;
  attention?: boolean;
}) {
  return (
    <View
      style={[
        styles.summaryBox,
        attention && styles.summaryBoxAttention,
      ]}
    >
      <Text
        style={[
          styles.summaryValue,
          attention && styles.attentionText,
        ]}
      >
        {value}
      </Text>

      <Text style={styles.summaryLabel}>
        {label}
      </Text>
    </View>
  );
}

function TaskGroup({
  title,
  tasks,
  onOpenTasks,
  critical = false,
}: {
  title: string;
  tasks: TodayNurseTask[];
  onOpenTasks: () => void;
  critical?: boolean;
}) {
  return (
    <View style={styles.group}>
      <View style={styles.groupHeader}>
        <Text
          style={[
            styles.groupTitle,
            critical && styles.attentionText,
          ]}
        >
          {title}
        </Text>

        <View
          style={[
            styles.countBadge,
            critical && styles.countBadgeCritical,
          ]}
        >
          <Text
            style={[
              styles.countText,
              critical && styles.attentionText,
            ]}
          >
            {tasks.length}
          </Text>
        </View>
      </View>

      {tasks.slice(0, 8).map((task) => (
        <TaskItem
          key={task.id}
          task={task}
          onOpenTasks={onOpenTasks}
        />
      ))}

      {tasks.length > 8 ? (
        <Text style={styles.moreText}>
          +{tasks.length - 8} more tasks
        </Text>
      ) : null}
    </View>
  );
}

function TaskItem({
  task,
  onOpenTasks,
}: {
  task: TodayNurseTask;
  onOpenTasks: () => void;
}) {
  return (
    <View
      style={[
        styles.taskCard,
        task.overdue && styles.taskCardOverdue,
      ]}
    >
      <View style={styles.taskTop}>
        <View style={styles.taskText}>
          <Text
            style={styles.residentName}
            numberOfLines={1}
          >
            {task.resident || "Resident"}
          </Text>

          <Text
            style={styles.taskName}
            numberOfLines={2}
          >
            {task.task_name}
          </Text>
        </View>

        <View
          style={[
            styles.statusBadge,
            task.overdue
              ? styles.statusOverdue
              : styles.statusPending,
          ]}
        >
          <Text
            style={[
              styles.statusText,
              task.overdue
                ? styles.statusTextOverdue
                : styles.statusTextPending,
            ]}
          >
            {task.overdue
              ? "Overdue"
              : formatLabel(task.status)}
          </Text>
        </View>
      </View>

      <View style={styles.meta}>
        {task.scheduled_time ? (
          <Text style={styles.metaText}>
            {formatTaskTime(task.scheduled_time)}
          </Text>
        ) : null}

        {task.priority ? (
          <Text style={styles.metaText}>
            {formatLabel(task.priority)}
          </Text>
        ) : null}

        {task.task_type ? (
          <Text style={styles.metaText}>
            {formatLabel(task.task_type)}
          </Text>
        ) : null}

        {task.ai_generated ? (
          <Text style={styles.aiText}>
            AI Generated
          </Text>
        ) : null}
      </View>

      {task.description ? (
        <Text
          style={styles.taskDescription}
          numberOfLines={2}
        >
          {task.description}
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

  empty: {
    paddingVertical: 12,
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

  summaryRow: {
    flexDirection: "row",
    marginBottom: 14,
    gap: 10,
  },

  summaryBox: {
    flex: 1,
    padding: 12,
    borderRadius: 11,
    backgroundColor: "#f8fafc",
  },

  summaryBoxAttention: {
    backgroundColor: "#fef2f2",
  },

  summaryValue: {
    fontSize: 22,
    fontWeight: "700",
    color: "#0f172a",
  },

  summaryLabel: {
    marginTop: 3,
    fontSize: 11,
    fontWeight: "600",
    color: "#64748b",
  },

  attentionText: {
    color: "#b91c1c",
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

  countBadgeCritical: {
    backgroundColor: "#fee2e2",
  },

  countText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#475569",
  },

  taskCard: {
    marginBottom: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 11,
    backgroundColor: "#f8fafc",
  },

  taskCardOverdue: {
    borderColor: "#fecaca",
    backgroundColor: "#fef2f2",
  },

  taskTop: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
  },

  taskText: {
    flex: 1,
    marginRight: 8,
  },

  residentName: {
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

  statusPending: {
    backgroundColor: "#fef3c7",
  },

  statusOverdue: {
    backgroundColor: "#fee2e2",
  },

  statusText: {
    fontSize: 10,
    fontWeight: "700",
  },

  statusTextPending: {
    color: "#92400e",
  },

  statusTextOverdue: {
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

  aiText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#7c3aed",
  },

  taskDescription: {
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

  openButton: {
    alignItems: "center",
    justifyContent: "center",
    minHeight: 44,
    marginTop: 2,
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