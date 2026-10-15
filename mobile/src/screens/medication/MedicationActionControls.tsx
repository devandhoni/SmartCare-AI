import React, { useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";

import {
  MedicationOutcome,
  ScheduledMedication,
} from "../../types/medication";

type Props = {
  medication: ScheduledMedication;
  canAdminister: boolean;
  submitting: boolean;
  anySubmitting: boolean;
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
};

const OUTCOMES: MedicationOutcome[] = [
  "COMPLETED",
  "DELAYED",
  "MISSED",
  "HELD",
  "REFUSED",
  "UNAVAILABLE",
];

export default function MedicationActionControls({
  medication,
  canAdminister,
  submitting,
  anySubmitting,
  onRecordOutcome,
  onConfirmMeal,
}: Props) {
  const [outcome, setOutcome] =
    useState<MedicationOutcome>("COMPLETED");
  const [remarks, setRemarks] = useState("");
  const [mealConfirmed, setMealConfirmed] = useState(false);
  const [mealNotes, setMealNotes] = useState("");

  const isPending =
    medication.status === "PENDING" ||
    medication.status === "DELAYED";

  const canConfirmMeal =
    medication.status === "COMPLETED" &&
    !!medication.meal_type &&
    !medication.meal_confirmed &&
    !!medication.administration_record_id;

  const supportsMeal =
    medication.time_slot !== "OTHER" &&
    !!medication.meal_type;

  const disabled = !canAdminister || anySubmitting;

  if (!isPending && !canConfirmMeal) {
    return null;
  }

  return (
    <View style={styles.container}>
      {isPending && canAdminister && (
        <>
          <Text style={styles.heading}>
            Record Medication Outcome
          </Text>

          <View style={styles.outcomeRow}>
            {OUTCOMES.map((item) => (
              <Pressable
                key={item}
                disabled={disabled}
                onPress={() => setOutcome(item)}
                style={[
                  styles.outcomeButton,
                  outcome === item && styles.selectedButton,
                ]}
              >
                <Text
                  style={[
                    styles.outcomeText,
                    outcome === item && styles.selectedText,
                  ]}
                >
                  {item}
                </Text>
              </Pressable>
            ))}
          </View>

          <Text style={styles.label}>
            Remarks {outcome !== "COMPLETED" ? "(required)" : "(optional)"}
          </Text>

          <TextInput
            value={remarks}
            onChangeText={setRemarks}
            editable={!disabled}
            multiline
            placeholder="Enter medication remarks"
            style={styles.input}
          />

          {outcome === "COMPLETED" && supportsMeal && (
            <>
              <Pressable
                disabled={disabled}
                onPress={() =>
                  setMealConfirmed((value) => !value)
                }
                style={styles.checkRow}
              >
                <Text style={styles.checkMark}>
                  {mealConfirmed ? "☑" : "☐"}
                </Text>
                <Text style={styles.checkLabel}>
                  Confirm {medication.meal_type}
                </Text>
              </Pressable>

              <Text style={styles.label}>
                Meal Notes (optional)
              </Text>

              <TextInput
                value={mealNotes}
                onChangeText={setMealNotes}
                editable={!disabled}
                multiline
                placeholder="Enter meal notes"
                style={styles.input}
              />
            </>
          )}

          <Pressable
            disabled={
              disabled ||
              (outcome !== "COMPLETED" && !remarks.trim())
            }
            onPress={() =>
              onRecordOutcome(
                medication,
                outcome,
                remarks.trim(),
                mealConfirmed,
                mealNotes.trim()
              )
            }
            style={[
              styles.submitButton,
              (disabled ||
                (outcome !== "COMPLETED" && !remarks.trim())) &&
                styles.disabledButton,
            ]}
          >
            {submitting ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.submitText}>
                Review & Confirm Outcome
              </Text>
            )}
          </Pressable>
        </>
      )}

      {canConfirmMeal && canAdminister && (
        <>
          <Text style={styles.heading}>
            Confirm {medication.meal_type}
          </Text>

          <Text style={styles.label}>
            Meal Notes (optional)
          </Text>

          <TextInput
            value={mealNotes}
            onChangeText={setMealNotes}
            editable={!disabled}
            multiline
            placeholder="Enter meal notes"
            style={styles.input}
          />

          <Pressable
            disabled={disabled}
            onPress={() =>
              onConfirmMeal(medication, mealNotes.trim())
            }
            style={[
              styles.submitButton,
              disabled && styles.disabledButton,
            ]}
          >
            {submitting ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.submitText}>
                Review & Confirm Meal
              </Text>
            )}
          </Pressable>
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginTop: 14,
    paddingTop: 14,
    borderTopWidth: 1,
    borderTopColor: "#e5e9f0",
  },
  heading: {
    fontSize: 15,
    fontWeight: "700",
    color: "#172033",
    marginBottom: 12,
  },
  outcomeRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
    marginBottom: 14,
  },
  outcomeButton: {
    paddingHorizontal: 10,
    paddingVertical: 9,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: "#d9dfeb",
    backgroundColor: "#ffffff",
  },
  selectedButton: {
    backgroundColor: "#3157a4",
    borderColor: "#3157a4",
  },
  outcomeText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#354052",
  },
  selectedText: {
    color: "#ffffff",
  },
  label: {
    fontSize: 13,
    fontWeight: "600",
    color: "#354052",
    marginBottom: 7,
  },
  input: {
    backgroundColor: "#ffffff",
    borderWidth: 1,
    borderColor: "#d9dfeb",
    borderRadius: 10,
    padding: 12,
    minHeight: 46,
    marginBottom: 14,
    color: "#172033",
    textAlignVertical: "top",
  },
  checkRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 14,
    gap: 8,
  },
  checkMark: {
    fontSize: 23,
    color: "#3157a4",
  },
  checkLabel: {
    fontSize: 14,
    color: "#172033",
    fontWeight: "600",
  },
  submitButton: {
    backgroundColor: "#3157a4",
    borderRadius: 10,
    padding: 13,
    alignItems: "center",
  },
  disabledButton: {
    opacity: 0.45,
  },
  submitText: {
    color: "#ffffff",
    fontWeight: "700",
    fontSize: 13,
  },
});