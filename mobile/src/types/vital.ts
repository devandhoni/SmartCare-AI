export interface VitalSign {
  id: number;
  resident_id?: number | null;

  blood_pressure_systolic?: number | null;
  blood_pressure_diastolic?: number | null;

  heart_rate?: number | null;
  oxygen_level?: number | null;
  blood_glucose?: number | null;
  temperature?: number | null;
  weight?: number | null;

  recorded_at?: string | null;
  created_on?: string | null;
  created_at?: string | null;

  record_source?: string | null;

  [key: string]: unknown;
}

export interface ResidentVitalsResponse {
  vitals: VitalSign[];
}

export interface CreateVitalPayload {
  blood_pressure_systolic: number;
  blood_pressure_diastolic: number;

  blood_glucose: number | null;
  heart_rate: number | null;
  oxygen_level: number | null;
  temperature: number | null;
  weight: number | null;
}

export interface CreateVitalResponse {
  vital: VitalSign;
}