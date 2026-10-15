export type NurseTaskStatus =
  | "Pending"
  | "ACKNOWLEDGED"
  | "Completed"
  | "Cancelled"
  | string;

export type NurseTaskPriority =
  | "LOW"
  | "NORMAL"
  | "HIGH"
  | "URGENT"
  | "CRITICAL";

export interface NurseTaskResident {
  id: number;
  name?: string | null;
  full_name?: string | null;
  room_number?: string | null;
}

export interface NurseTaskUser {
  id: number;
  name?: string | null;
  full_name?: string | null;
}

export interface NurseTask {
  id: number;
  resident_id: number;
  resident?: NurseTaskResident | null;

  task_name: string;
  description?: string | null;
  task_type?: string | null;
  source_type?: string | null;

  status: NurseTaskStatus;
  priority?: NurseTaskPriority | string | null;

  scheduled_time?: string | null;
  created_on?: string | null;

  assigned_to?: number | null;
  assigned_user?: NurseTaskUser | null;

  completed_by?: number | NurseTaskUser | null;
  completed_time?: string | null;
  completion_notes?: string | null;

  ai_generated?: boolean;
  clinical_action_plan?: string | null;

  care_record?: Record<string, unknown> | null;
  alert?: Record<string, unknown> | null;
}

export interface NurseTasksResponse {
  success: boolean;
  message: string;
  data: NurseTask[];
}