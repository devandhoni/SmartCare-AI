export interface Resident {
  id: number;
  full_name?: string | null;
  name?: string | null;
  room_number?: string | null;
  room?: string | null;
  gender?: string | null;
  status?: string | null;

  date_of_birth?: string | null;
  admission_date?: string | null;
  identification_number?: string | null;
  nationality?: string | null;
  marital_status?: string | null;
  blood_group?: string | null;

  mobility_status?: string | null;
  dietary_requirements?: string | null;
  allergies?: string | null;
  medical_conditions?: string | null;
  chronic_disease?: string | null;
  notes?: string | null;

  created_at?: string | null;
  updated_at?: string | null;

  [key: string]: unknown;
}

export interface ResidentLatestVital {
  id?: number;
  systolic?: number | string | null;
  diastolic?: number | string | null;
  pulse?: number | string | null;
  spo2?: number | string | null;
  glucose?: number | string | null;
  weight?: number | string | null;
  temperature?: number | string | null;
  recorded_at?: string | null;
  created_on?: string | null;

  [key: string]: unknown;
}

export interface ResidentAlert {
  id: number;
  severity?: string | null;
  status?: string | null;
  title?: string | null;
  message?: string | null;
  alert_type?: string | null;
  created_at?: string | null;

  [key: string]: unknown;
}

export interface ResidentPendingTask {
  id: number;
  task_name?: string | null;
  description?: string | null;
  priority?: string | null;
  status?: string | null;
  scheduled_time?: string | null;
  task_type?: string | null;
  ai_generated?: boolean | number | null;

  [key: string]: unknown;
}

export interface ResidentProfileResponse {
  resident: Resident | null;
  latest_vital: ResidentLatestVital | null;
  active_alerts: ResidentAlert[];
  pending_tasks: ResidentPendingTask[];
}