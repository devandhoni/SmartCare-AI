export type MedicationSlot = "AM" | "PM" | "NIGHT" | "OTHER";

export type MedicationOutcome =
  | "COMPLETED"
  | "HELD"
  | "REFUSED"
  | "UNAVAILABLE"
  | "MISSED"
  | "DELAYED";

export interface MedicationResident {
  id: number;
  name: string;
  status: string;
}

export interface ScheduledMedication {
  id: number;
  resident_medication_id: number;
  medication_id?: number | null;

  medicine?: string | null;
  dosage?: string | null;
  unit?: string | null;
  instruction?: string | null;
  quantity?: number | string | null;
  frequency?: string | null;

  time_slot: MedicationSlot;
  scheduled_time?: string | null;
  start_date?: string | null;
  end_date?: string | null;

  status: MedicationOutcome | "PENDING";
  administration_record_id?: number | null;
  completed_time?: string | null;
  completed_by?: number | null;
  remarks?: string | null;

  meal_type?: string | null;
  meal_confirmed: boolean;
  meal_confirmed_at?: string | null;
  meal_notes?: string | null;
}

export interface MedicationSchedule {
  AM: ScheduledMedication[];
  PM: ScheduledMedication[];
  NIGHT: ScheduledMedication[];
  OTHER: ScheduledMedication[];
}

export interface MedicationScheduleResponse {
  resident: MedicationResident;
  can_administer: boolean;
  schedule: MedicationSchedule;
}

export interface MedicationHistoryRecord {
  id: number;
  resident_medication_id: number;

  medicine?: string | null;
  dosage?: string | null;

  time_slot?: MedicationSlot | string | null;
  scheduled_time?: string | null;

  status: MedicationOutcome;
  completed_time?: string | null;
  completed_by?: string | null;
  remarks?: string | null;

  meal_type?: string | null;
  meal_confirmed: boolean;
  meal_confirmed_at?: string | null;
  meal_notes?: string | null;
}

export interface MedicationHistoryResponse {
  resident: MedicationResident;
  medication_history: MedicationHistoryRecord[];
  clinical_timeline?: unknown[];
}

export interface MedicationOutcomePayload {
  status: MedicationOutcome;
  remarks: string | null;
  meal_confirmed: boolean;
  meal_notes: string | null;
}

export interface MedicationOutcomeResponse {
  message: string;
  round_complete: boolean;
  family_notification_eligible: boolean;
  family_notification?: unknown;
  administration_record: unknown;
}

export interface MealConfirmationPayload {
  meal_notes: string | null;
}

export interface MealConfirmationResponse {
  message: string;
  [key: string]: unknown;
}