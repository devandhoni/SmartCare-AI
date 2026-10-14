import React, { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import {
  useNavigation,
  useRoute,
} from "@react-navigation/native";
import axios from "axios";

import { createResidentVital } from "../../api/vitalApi";
import type { CreateVitalPayload } from "../../types/vital";

type FormState = {
  systolic: string;
  diastolic: string;
  glucose: string;
  pulse: string;
  spo2: string;
  temperature: string;
  weight: string;
};

const EMPTY_FORM: FormState = {
  systolic: "",
  diastolic: "",
  glucose: "",
  pulse: "",
  spo2: "",
  temperature: "",
  weight: "",
};

export default function RecordVitalsScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();

  const residentId = Number(route.params?.residentId);

  const [form, setForm] =
    useState<FormState>(EMPTY_FORM);

  const [saving, setSaving] = useState(false);

  const updateField = (
    field: keyof FormState,
    value: string
  ) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const handleSave = async () => {
    if (!residentId) {
      Alert.alert(
        "Unable to Record",
        "Resident information is unavailable."
      );
      return;
    }

    const validationMessage =
      validateForm(form);

    if (validationMessage) {
      Alert.alert(
        "Check Vital Signs",
        validationMessage
      );
      return;
    }

    const payload: CreateVitalPayload = {
      blood_pressure_systolic:
        Number(form.systolic),

      blood_pressure_diastolic:
        Number(form.diastolic),

      blood_glucose:
        optionalNumber(form.glucose),

      heart_rate:
        optionalNumber(form.pulse),

      oxygen_level:
        optionalNumber(form.spo2),

      temperature:
        optionalNumber(form.temperature),

      weight:
        optionalNumber(form.weight),
    };

    try {
      setSaving(true);

      await createResidentVital(
        residentId,
        payload
      );

      Alert.alert(
        "Vital Signs Recorded",
        "The resident's vital signs were recorded successfully.",
        [
          {
            text: "OK",
            onPress: () => navigation.goBack(),
          },
        ]
      );
    } catch (error) {
      console.error(
        "Failed to record vital signs:",
        error
      );

      let message =
        "Unable to record vital signs. Please try again.";

      if (axios.isAxiosError(error)) {
        const responseMessage =
          error.response?.data?.message;

        if (
          typeof responseMessage === "string" &&
          responseMessage.trim()
        ) {
          message = responseMessage;
        }

        const validationErrors =
          error.response?.data?.errors;

        if (
          validationErrors &&
          typeof validationErrors === "object"
        ) {
          const firstError = Object.values(
            validationErrors
          )
            .flat()
            .find(
              (item) =>
                typeof item === "string"
            );

          if (
            typeof firstError === "string"
          ) {
            message = firstError;
          }
        }
      }

      Alert.alert(
        "Unable to Record",
        message
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["bottom"]}
    >
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={
          Platform.OS === "ios"
            ? "padding"
            : undefined
        }
      >
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
        >
          <View style={styles.introCard}>
            <Text style={styles.eyebrow}>
              Clinical Recording
            </Text>

            <Text style={styles.title}>
              Record Vital Signs
            </Text>

            <Text style={styles.subtitle}>
              Blood pressure is required. Other
              readings may be recorded when
              available.
            </Text>
          </View>

          <Section title="Blood Pressure">
            <View style={styles.twoColumn}>
              <Field
                label="Systolic *"
                value={form.systolic}
                onChangeText={(value) =>
                  updateField(
                    "systolic",
                    value
                  )
                }
                placeholder="50–250"
              />

              <Field
                label="Diastolic *"
                value={form.diastolic}
                onChangeText={(value) =>
                  updateField(
                    "diastolic",
                    value
                  )
                }
                placeholder="30–150"
              />
            </View>
          </Section>

          <Section title="Additional Readings">
            <Field
              label="Pulse (bpm)"
              value={form.pulse}
              onChangeText={(value) =>
                updateField("pulse", value)
              }
              placeholder="30–200"
            />

            <Field
              label="SpO₂ (%)"
              value={form.spo2}
              onChangeText={(value) =>
                updateField("spo2", value)
              }
              placeholder="50–100"
            />

            <Field
              label="Blood Glucose"
              value={form.glucose}
              onChangeText={(value) =>
                updateField(
                  "glucose",
                  value
                )
              }
              placeholder="1–30"
              decimal
            />

            <Field
              label="Temperature (°C)"
              value={form.temperature}
              onChangeText={(value) =>
                updateField(
                  "temperature",
                  value
                )
              }
              placeholder="30–45"
              decimal
            />

            <Field
              label="Weight (kg)"
              value={form.weight}
              onChangeText={(value) =>
                updateField("weight", value)
              }
              placeholder="1–300"
              decimal
            />
          </Section>

          <View style={styles.notice}>
            <Text style={styles.noticeTitle}>
              After saving
            </Text>

            <Text style={styles.noticeText}>
              SmartCare-AI will process the
              reading through the existing
              clinical timeline, activity
              logging, and health-analysis
              workflow.
            </Text>
          </View>

          <Pressable
            style={[
              styles.saveButton,
              saving &&
                styles.saveButtonDisabled,
            ]}
            disabled={saving}
            onPress={handleSave}
          >
            {saving ? (
              <ActivityIndicator
                color="#ffffff"
              />
            ) : (
              <Text
                style={styles.saveButtonText}
              >
                Save Vital Signs
              </Text>
            )}
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function Section({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>
        {title}
      </Text>

      {children}
    </View>
  );
}

function Field({
  label,
  value,
  onChangeText,
  placeholder,
  decimal = false,
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder: string;
  decimal?: boolean;
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>
        {label}
      </Text>

      <TextInput
        style={styles.input}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#94a3b8"
        keyboardType={
          decimal
            ? "decimal-pad"
            : "number-pad"
        }
      />
    </View>
  );
}

function optionalNumber(
  value: string
): number | null {
  const trimmed = value.trim();

  return trimmed === ""
    ? null
    : Number(trimmed);
}

function validateForm(
  form: FormState
): string | null {
  if (
    !form.systolic.trim() ||
    !form.diastolic.trim()
  ) {
    return "Blood pressure systolic and diastolic readings are required.";
  }

  const systolic = Number(form.systolic);
  const diastolic = Number(form.diastolic);

  if (
    !Number.isInteger(systolic) ||
    systolic < 50 ||
    systolic > 250
  ) {
    return "Systolic blood pressure must be a whole number between 50 and 250.";
  }

  if (
    !Number.isInteger(diastolic) ||
    diastolic < 30 ||
    diastolic > 150
  ) {
    return "Diastolic blood pressure must be a whole number between 30 and 150.";
  }

  const optionalRules: Array<{
    value: string;
    label: string;
    min: number;
    max: number;
    integer?: boolean;
  }> = [
    {
      value: form.glucose,
      label: "Blood glucose",
      min: 1,
      max: 30,
    },
    {
      value: form.pulse,
      label: "Pulse",
      min: 30,
      max: 200,
      integer: true,
    },
    {
      value: form.spo2,
      label: "SpO₂",
      min: 50,
      max: 100,
      integer: true,
    },
    {
      value: form.temperature,
      label: "Temperature",
      min: 30,
      max: 45,
    },
    {
      value: form.weight,
      label: "Weight",
      min: 1,
      max: 300,
    },
  ];

  for (const rule of optionalRules) {
    if (!rule.value.trim()) {
      continue;
    }

    const numericValue =
      Number(rule.value);

    if (
      !Number.isFinite(numericValue)
    ) {
      return `${rule.label} must be a valid number.`;
    }

    if (
      rule.integer &&
      !Number.isInteger(numericValue)
    ) {
      return `${rule.label} must be a whole number.`;
    }

    if (
      numericValue < rule.min ||
      numericValue > rule.max
    ) {
      return `${rule.label} must be between ${rule.min} and ${rule.max}.`;
    }
  }

  return null;
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },

  safeArea: {
    flex: 1,
    backgroundColor: "#f8fafc",
  },

  content: {
    padding: 16,
    paddingBottom: 40,
  },

  introCard: {
    padding: 18,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  eyebrow: {
    fontSize: 13,
    fontWeight: "700",
    color: "#2563eb",
  },

  title: {
    marginTop: 4,
    fontSize: 24,
    fontWeight: "800",
    color: "#0f172a",
  },

  subtitle: {
    marginTop: 7,
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  section: {
    marginTop: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  sectionTitle: {
    marginBottom: 12,
    fontSize: 16,
    fontWeight: "800",
    color: "#0f172a",
  },

  twoColumn: {
    flexDirection: "row",
    gap: 10,
  },

  field: {
    flex: 1,
    marginBottom: 13,
  },

  label: {
    marginBottom: 6,
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
  },

  input: {
    minHeight: 48,
    paddingHorizontal: 13,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 11,
    backgroundColor: "#ffffff",
    fontSize: 16,
    color: "#0f172a",
  },

  notice: {
    marginTop: 14,
    padding: 15,
    borderRadius: 14,
    backgroundColor: "#eff6ff",
  },

  noticeTitle: {
    fontSize: 13,
    fontWeight: "800",
    color: "#1e40af",
  },

  noticeText: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: "#475569",
  },

  saveButton: {
    minHeight: 52,
    marginTop: 18,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 13,
    backgroundColor: "#2563eb",
  },

  saveButtonDisabled: {
    opacity: 0.6,
  },

  saveButtonText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#ffffff",
  },
});