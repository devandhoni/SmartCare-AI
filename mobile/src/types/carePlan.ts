export type CarePlanFrequency =
  | "DAILY"
  | "WEEKLY"
  | "EVERY_SHIFT"
  | "PRN";

export type CarePlanTimeSlot =
  | "AM"
  | "PM"
  | "NIGHT"
  | "ANY";

export type CarePlanPriority =
  | "LOW"
  | "NORMAL"
  | "HIGH"
  | "URGENT"
  | "CRITICAL";

export type CarePlanWeekday =
  | "MON"
  | "TUE"
  | "WED"
  | "THU"
  | "FRI"
  | "SAT"
  | "SUN";

export interface CarePlanUser {
  id: number;
  full_name: string;
}

export interface CarePlanResident {
  id: number;
  full_name: string;
  status: string;
}

export interface CarePlan {
  id: number;
  resident_id: number;
  care_type: string;
  title: string;
  instructions: string | null;
  frequency: CarePlanFrequency;
  time_slot: CarePlanTimeSlot | null;
  scheduled_time: string | null;
  days_of_week: CarePlanWeekday[] | null;
  start_date: string | null;
  end_date: string | null;
  priority: CarePlanPriority;
  is_active: boolean;
  created_by?: number | null;
  updated_by?: number | null;
  createdBy?: CarePlanUser | null;
  updatedBy?: CarePlanUser | null;
  resident?: CarePlanResident;
  created_at?: string;
  updated_at?: string;
}

export interface CarePlansListResponse {
  resident: CarePlanResident;
  care_plans: CarePlan[];
}

export interface CarePlanDetailResponse {
  care_plan: CarePlan;
}

export interface CarePlanInput {
  care_type: string;
  title: string;
  instructions?: string | null;
  frequency: CarePlanFrequency;
  time_slot?: CarePlanTimeSlot | null;
  scheduled_time?: string | null;
  days_of_week?: CarePlanWeekday[] | null;
  start_date?: string;
  end_date?: string | null;
  priority?: CarePlanPriority;
}

export interface CarePlanMutationResponse {
  message: string;
  care_plan: CarePlan;
}