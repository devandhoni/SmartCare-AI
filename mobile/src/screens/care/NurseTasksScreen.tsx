import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { getNurseTasks } from "../../api/nurseTaskApi";
import type { NurseTask } from "../../types/nurseTask";
import NurseTaskActionControls from "./NurseTaskActionControls";

function getResidentName(task: NurseTask): string {
  return (
    task.resident?.full_name ||
    task.resident?.name ||
    `Resident #${task.resident_id}`
  );
}

function formatDate(value?: string | null): string {
  if (!value) return "Not scheduled";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString("en-MY", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function getErrorMessage(error: unknown): string {
  if (error && typeof error === "object") {
    const err = error as {
      response?: { data?: { message?: string } };
      message?: string;
    };

    return (
      err.response?.data?.message ||
      err.message ||
      "Unable to load nurse tasks."
    );
  }

  return "Unable to load nurse tasks.";
}

function TaskCard({
  task,
  onUpdated,
}: {
  task: NurseTask;
  onUpdated: () => Promise<void>;
}) {
  return (
    <View style={styles.taskCard}>
      <View style={styles.cardHeader}>
        <Text style={styles.taskTitle}>
          {task.task_name || "Nursing Task"}
        </Text>

        <View style={styles.priorityBadge}>
          <Text style={styles.priorityText}>
            {task.priority || "NORMAL"}
          </Text>
        </View>
      </View>

      <Text style={styles.residentName}>
        {getResidentName(task)}
      </Text>

      <View style={styles.statusRow}>
        <Text style={styles.fieldLabel}>Status</Text>
        <Text style={styles.statusValue}>
          {task.status}
        </Text>
      </View>

      <View style={styles.statusRow}>
        <Text style={styles.fieldLabel}>Scheduled</Text>
        <Text style={styles.fieldValue}>
          {formatDate(task.scheduled_time)}
        </Text>
      </View>

      {!!task.task_type && (
        <View style={styles.statusRow}>
          <Text style={styles.fieldLabel}>Task Type</Text>
          <Text style={styles.fieldValue}>
            {task.task_type}
          </Text>
        </View>
      )}

      {typeof task.description === "string" &&
        task.description.trim().length > 0 && (
            <Text style={styles.description}>
            {task.description}
            </Text>
    )}

      {typeof task.clinical_action_plan === "string" &&
        task.clinical_action_plan.trim().length > 0 && (
        <View style={styles.actionPlanBox}>
          <Text style={styles.actionPlanTitle}>
            Clinical Action Plan
          </Text>
          <Text style={styles.actionPlanText}>
            {task.clinical_action_plan}
          </Text>
        </View>
      )}

      {!!task.completion_notes && (
        <View style={styles.notesBox}>
          <Text style={styles.notesTitle}>
            Completion Notes
          </Text>
          <Text style={styles.notesText}>
            {task.completion_notes}
          </Text>
        </View>
      )}
      <NurseTaskActionControls
        task={task}
        onUpdated={onUpdated}
        />
    </View>
  );
}

export default function NurseTasksScreen() {
  const [tasks, setTasks] = useState<NurseTask[]>([]);
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");

  const loadTasks = useCallback(async () => {
    try {
      setError("");
      const result = await getNurseTasks();
      setTasks(result);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadTasks();
  }, [loadTasks]);

  const filteredTasks = useMemo(() => {
    const query = search.trim().toLowerCase();

    if (!query) return tasks;

    return tasks.filter((task) => {
      const searchable = [
        task.task_name,
        task.description,
        task.status,
        task.priority,
        task.task_type,
        getResidentName(task),
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();

      return searchable.includes(query);
    });
  }, [tasks, search]);

  const refresh = useCallback(async () => {
    setRefreshing(true);

    try {
      await loadTasks();
    } finally {
      setRefreshing(false);
    }
  }, [loadTasks]);

  if (loading) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.center}>
          <ActivityIndicator size="large" />
          <Text style={styles.muted}>
            Loading nurse tasks...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.container}>
        <Text style={styles.pageTitle}>Nurse Tasks</Text>

        <Text style={styles.subtitle}>
          Review assigned, pending, and completed nursing tasks.
        </Text>

        {!!error && (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{error}</Text>

            <Pressable
              onPress={loadTasks}
              style={styles.retryButton}
            >
              <Text style={styles.retryText}>Retry</Text>
            </Pressable>
          </View>
        )}

        <TextInput
          value={search}
          onChangeText={setSearch}
          placeholder="Search task, resident, or status"
          autoCapitalize="none"
          autoCorrect={false}
          style={styles.searchInput}
        />

        <Text style={styles.resultCount}>
          {filteredTasks.length} task
          {filteredTasks.length === 1 ? "" : "s"}
        </Text>

        <FlatList
          data={filteredTasks}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <TaskCard
                task={item}
                onUpdated={loadTasks}
            />
            )}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={refresh}
            />
          }
          ListEmptyComponent={
            <Text style={styles.emptyText}>
              {error
                ? "Unable to display tasks."
                : "No nurse tasks found."}
            </Text>
          }
        />
      </View>
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
  subtitle: {
    fontSize: 14,
    lineHeight: 20,
    color: "#687386",
    marginTop: 6,
    marginBottom: 16,
  },
  muted: {
    marginTop: 12,
    color: "#687386",
  },
  searchInput: {
    backgroundColor: "#ffffff",
    borderWidth: 1,
    borderColor: "#d9dfeb",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    color: "#172033",
  },
  resultCount: {
    color: "#687386",
    fontSize: 13,
    marginTop: 12,
    marginBottom: 8,
  },
  listContent: {
    paddingBottom: 30,
  },
  taskCard: {
    backgroundColor: "#ffffff",
    borderRadius: 14,
    padding: 15,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#e5e9f0",
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
    gap: 10,
  },
  taskTitle: {
    flex: 1,
    fontSize: 16,
    fontWeight: "700",
    color: "#172033",
  },
  priorityBadge: {
    backgroundColor: "#eef2f8",
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: 9,
  },
  priorityText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#354052",
  },
  residentName: {
    color: "#3157a4",
    fontSize: 14,
    fontWeight: "600",
    marginTop: 7,
    marginBottom: 10,
  },
  statusRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    gap: 12,
    marginTop: 7,
  },
  fieldLabel: {
    color: "#687386",
    fontSize: 13,
  },
  fieldValue: {
    flex: 1,
    textAlign: "right",
    color: "#354052",
    fontSize: 13,
  },
  statusValue: {
    fontSize: 13,
    fontWeight: "700",
    color: "#3157a4",
  },
  description: {
    marginTop: 12,
    color: "#4f5b6d",
    fontSize: 13,
    lineHeight: 19,
  },
  actionPlanBox: {
    backgroundColor: "#eef4ff",
    padding: 12,
    borderRadius: 10,
    marginTop: 12,
  },
  actionPlanTitle: {
    fontSize: 12,
    fontWeight: "700",
    color: "#3157a4",
    marginBottom: 5,
  },
  actionPlanText: {
    color: "#354052",
    fontSize: 13,
    lineHeight: 19,
  },
  notesBox: {
    backgroundColor: "#f4f6f9",
    padding: 12,
    borderRadius: 10,
    marginTop: 12,
  },
  notesTitle: {
    fontSize: 12,
    fontWeight: "700",
    color: "#4f5b6d",
    marginBottom: 5,
  },
  notesText: {
    color: "#354052",
    fontSize: 13,
    lineHeight: 19,
  },
  errorBox: {
    backgroundColor: "#fff0f0",
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
  },
  errorText: {
    color: "#8a2d2d",
    fontSize: 13,
  },
  retryButton: {
    alignSelf: "flex-start",
    paddingVertical: 8,
    marginTop: 6,
  },
  retryText: {
    color: "#3157a4",
    fontWeight: "700",
  },
  emptyText: {
    textAlign: "center",
    color: "#7a8495",
    marginTop: 30,
  },
});