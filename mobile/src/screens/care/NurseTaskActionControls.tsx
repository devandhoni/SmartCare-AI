import React, { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";

import {
  acceptNurseTask,
  acknowledgeNurseTask,
  completeNurseTask,
  recordNurseTaskOutcome,
} from "../../api/nurseTaskApi";
import type { NurseTask } from "../../types/nurseTask";

type CareStatus = "COMPLETED" | "OBSERVED" | "NEEDS_ATTENTION";
type OutcomeStatus = "IMPROVED" | "STABLE" | "DETERIORATED" | "UNKNOWN";

interface Props {
  task: NurseTask;
  onUpdated: () => Promise<void>;
}

const CARE_STATUSES: CareStatus[] = [
  "COMPLETED",
  "OBSERVED",
  "NEEDS_ATTENTION",
];

const OUTCOME_STATUSES: OutcomeStatus[] = [
  "IMPROVED",
  "STABLE",
  "DETERIORATED",
  "UNKNOWN",
];

function errorMessage(error: unknown): string {
  if (error && typeof error === "object") {
    const value = error as {
      response?: { data?: { message?: string } };
      message?: string;
    };

    return (
      value.response?.data?.message ||
      value.message ||
      "The action could not be completed."
    );
  }

  return "The action could not be completed.";
}

export default function NurseTaskActionControls({
  task,
  onUpdated,
}: Props) {
  const [busy, setBusy] = useState(false);
  const [completionNotes, setCompletionNotes] = useState("");
  const [careStatus, setCareStatus] = useState<CareStatus>("COMPLETED");
  const [outcomeStatus, setOutcomeStatus] = useState<OutcomeStatus>("STABLE");
  const [outcomeNotes, setOutcomeNotes] = useState("");

  const isFinished =
    task.status === "Completed" || task.status === "Cancelled";

  const isAcknowledged = task.status === "ACKNOWLEDGED";

  const runAction = async (
    title: string,
    action: () => Promise<unknown>
  ) => {
    if (busy) return;

    Alert.alert(
      title,
      `Confirm this action for task #${task.id}?`,
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Confirm",
          onPress: async () => {
            if (busy) return;

            setBusy(true);

            try {
              await action();
              await onUpdated();

              Alert.alert(
                "Success",
                `${title} completed successfully.`
              );
            } catch (error) {
              Alert.alert("Unable to proceed", errorMessage(error));
            } finally {
              setBusy(false);
            }
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <Text style={styles.heading}>Task Actions</Text>

      {isFinished ? (
        <Text style={styles.info}>
          This task is {task.status.toLowerCase()}.
          No further completion actions are available.
        </Text>
      ) : (
        <>
          {!isAcknowledged && (
            <>
              <Pressable
                disabled={busy}
                style={styles.button}
                onPress={() =>
                  runAction("Accept Task", () => acceptNurseTask(task.id))
                }
              >
                <Text style={styles.buttonText}>Accept Task</Text>
              </Pressable>

              <Pressable
                disabled={busy}
                style={styles.secondaryButton}
                onPress={() =>
                  runAction("Acknowledge Task", () =>
                    acknowledgeNurseTask(task.id)
                  )
                }
              >
                <Text style={styles.secondaryButtonText}>
                  Acknowledge Task
                </Text>
              </Pressable>
            </>
          )}

          {isAcknowledged && (
            <>
              <Text style={styles.label}>Completion Notes</Text>

              <TextInput
                value={completionNotes}
                onChangeText={setCompletionNotes}
                placeholder="Describe care provided and observations"
                multiline
                maxLength={2000}
                editable={!busy}
                style={styles.notesInput}
              />

              <Text style={styles.label}>Care Status</Text>

              <View style={styles.optionRow}>
                {CARE_STATUSES.map((status) => (
                  <Pressable
                    key={status}
                    disabled={busy}
                    onPress={() => setCareStatus(status)}
                    style={[
                      styles.option,
                      careStatus === status && styles.selectedOption,
                    ]}
                  >
                    <Text
                      style={[
                        styles.optionText,
                        careStatus === status && styles.selectedOptionText,
                      ]}
                    >
                      {status.replace("_", " ")}
                    </Text>
                  </Pressable>
                ))}
              </View>

              <Pressable
                disabled={busy}
                style={styles.button}
                onPress={() => {
                  if (
                    task.task_type === "ROUTINE_CARE" &&
                    !completionNotes.trim()
                  ) {
                    Alert.alert(
                      "Completion notes required",
                      "Please record the care provided before completing this routine care task."
                    );
                    return;
                  }

                  runAction("Complete Task", () =>
                    completeNurseTask(task.id, {
                      completion_notes: completionNotes.trim(),
                      care_status: careStatus,
                    })
                  );
                }}
              >
                <Text style={styles.buttonText}>Complete Task</Text>
              </Pressable>
            </>
          )}
        </>
      )}

      {task.ai_generated === true && (
        <View style={styles.outcomeSection}>
          <Text style={styles.heading}>Clinical Outcome</Text>

          <View style={styles.optionRow}>
            {OUTCOME_STATUSES.map((status) => (
              <Pressable
                key={status}
                disabled={busy}
                onPress={() => setOutcomeStatus(status)}
                style={[
                  styles.option,
                  outcomeStatus === status && styles.selectedOption,
                ]}
              >
                <Text
                  style={[
                    styles.optionText,
                    outcomeStatus === status && styles.selectedOptionText,
                  ]}
                >
                  {status}
                </Text>
              </Pressable>
            ))}
          </View>

          <TextInput
            value={outcomeNotes}
            onChangeText={setOutcomeNotes}
            placeholder="Clinical outcome observations (optional)"
            multiline
            editable={!busy}
            style={styles.notesInput}
          />

          <Pressable
            disabled={busy}
            style={styles.secondaryButton}
            onPress={() =>
              runAction("Record Clinical Outcome", () =>
                recordNurseTaskOutcome(task.id, {
                  outcome_status: outcomeStatus,
                  outcome_notes: outcomeNotes.trim(),
                })
              )
            }
          >
            <Text style={styles.secondaryButtonText}>
              Record Outcome
            </Text>
          </Pressable>
        </View>
      )}

      {busy && (
        <ActivityIndicator style={styles.loading} />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginTop: 14,
    borderTopWidth: 1,
    borderTopColor: "#e5e9f0",
    paddingTop: 14,
  },
  heading: {
    fontSize: 15,
    fontWeight: "700",
    color: "#172033",
    marginBottom: 12,
  },
  info: {
    fontSize: 13,
    color: "#687386",
    lineHeight: 19,
  },
  label: {
    fontSize: 13,
    fontWeight: "600",
    color: "#354052",
    marginBottom: 7,
  },
  notesInput: {
    borderWidth: 1,
    borderColor: "#d9dfeb",
    borderRadius: 10,
    padding: 12,
    minHeight: 75,
    textAlignVertical: "top",
    color: "#172033",
    marginBottom: 12,
  },
  button: {
    backgroundColor: "#3157a4",
    padding: 13,
    borderRadius: 10,
    alignItems: "center",
    marginBottom: 10,
  },
  buttonText: {
    color: "#ffffff",
    fontWeight: "700",
  },
  secondaryButton: {
    borderWidth: 1,
    borderColor: "#3157a4",
    padding: 12,
    borderRadius: 10,
    alignItems: "center",
    marginBottom: 10,
  },
  secondaryButtonText: {
    color: "#3157a4",
    fontWeight: "700",
  },
  optionRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 7,
    marginBottom: 14,
  },
  option: {
    paddingHorizontal: 10,
    paddingVertical: 9,
    borderRadius: 8,
    backgroundColor: "#eef2f8",
  },
  selectedOption: {
    backgroundColor: "#3157a4",
  },
  optionText: {
    color: "#354052",
    fontSize: 11,
    fontWeight: "600",
  },
  selectedOptionText: {
    color: "#ffffff",
  },
  outcomeSection: {
    borderTopWidth: 1,
    borderTopColor: "#e5e9f0",
    marginTop: 12,
    paddingTop: 14,
  },
  loading: {
    marginTop: 8,
  },
});