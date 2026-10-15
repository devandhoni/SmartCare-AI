import api from "./api";

import {
  MealConfirmationPayload,
  MealConfirmationResponse,
  MedicationHistoryResponse,
  MedicationOutcomePayload,
  MedicationOutcomeResponse,
  MedicationSchedule,
  MedicationScheduleResponse,
} from "../types/medication";

const emptySchedule = (): MedicationSchedule => ({
  AM: [],
  PM: [],
  NIGHT: [],
  OTHER: [],
});

export async function getMedicationSchedule(
  residentId: number
): Promise<MedicationScheduleResponse> {
  const response = await api.get(
    `/residents/${residentId}/medication-schedule`
  );

  const data = response.data ?? {};
  const rawSchedule = data.schedule ?? {};

  return {
    resident: {
      id: Number(data.resident?.id ?? residentId),
      name: String(data.resident?.name ?? ""),
      status: String(data.resident?.status ?? ""),
    },

    can_administer: Boolean(data.can_administer),

    schedule: {
      ...emptySchedule(),

      AM: Array.isArray(rawSchedule.AM)
        ? rawSchedule.AM
        : [],

      PM: Array.isArray(rawSchedule.PM)
        ? rawSchedule.PM
        : [],

      NIGHT: Array.isArray(rawSchedule.NIGHT)
        ? rawSchedule.NIGHT
        : [],

      OTHER: Array.isArray(rawSchedule.OTHER)
        ? rawSchedule.OTHER
        : [],
    },
  };
}

export async function getMedicationHistory(
  residentId: number
): Promise<MedicationHistoryResponse> {
  const response = await api.get(
    `/residents/${residentId}/medication/history`
  );

  const data = response.data ?? {};

  return {
    resident: {
      id: Number(data.resident?.id ?? residentId),
      name: String(data.resident?.name ?? ""),
      status: String(data.resident?.status ?? ""),
    },

    medication_history: Array.isArray(data.medication_history)
      ? data.medication_history
      : [],

    clinical_timeline: Array.isArray(data.clinical_timeline)
      ? data.clinical_timeline
      : [],
  };
}

export async function recordMedicationOutcome(
  residentMedicationId: number,
  payload: MedicationOutcomePayload
): Promise<MedicationOutcomeResponse> {
  const response = await api.put(
    `/medication-administration/${residentMedicationId}/complete`,
    payload
  );

  return response.data;
}

export async function confirmMedicationMeal(
  administrationRecordId: number,
  payload: MealConfirmationPayload
): Promise<MealConfirmationResponse> {
  const response = await api.put(
    `/medication-administration/${administrationRecordId}/meal-confirmation`,
    payload
  );

  return response.data;
}