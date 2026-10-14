export interface TodaySummary {
  active_residents: number;
  pending_tasks: number;
  overdue_tasks: number;
  due_care_tasks: number;
  overdue_care_tasks: number;
  medication_scheduled: number;
  medication_pending: number;
  medication_completed: number;
  medication_exceptions: number;
  medication_overdue: number;
  medication_meals_pending: number;
  medication_task_overdue: number;
  critical_alerts: number;
  weekly_vitals_due: number;
  monthly_glucose_due: number;
  residents_on_leave: number;
}

export interface TodayMedicationItem {
  resident_medication_id: number;
  resident_id: number;
  resident: string | null;

  medication_id: number;
  medicine: string | null;
  dosage: string | number | null;
  unit: string | null;

  dosage_instruction: string | null;
  dosage_quantity: string | number | null;

  scheduled_time: string | null;
  scheduled_at: string | null;

  status: string;

  administration_record_id: number | null;
  completed_time: string | null;
  completed_by: number | null;
  remarks: string | null;

  meal_type: string | null;
  meal_confirmed: boolean;
  meal_confirmed_at: string | null;
  meal_notes: string | null;

  overdue: boolean;
  round_complete: boolean;
  family_notification_eligible: boolean;
}

export interface TodayMedicationRound {
  total: number;
  completed: number;
  pending: number;
  exceptions: number;
  overdue: number;
  meals_pending: number;
  items: TodayMedicationItem[];
}

export interface TodayDueCheck {
  resident_id: number;
  resident: string | null;
}

export interface TodayHomeLeave {
  id: number;
  resident_id: number;
  resident: string | null;

  leave_reference: string | null;
  reason: string | null;
  destination: string | null;

  leave_at: string | null;
  expected_return_at: string | null;

  status: string | null;
  overdue: boolean;
}

export interface TodayCareActivity {
  id: number;
  resident_id: number;
  resident: string | null;

  task_name: string;
  description: string | null;
  priority: string | null;
  status: string;

  scheduled_time: string | null;
  source_type: string | null;

  care_plan_id: number | null;
  occurrence_key: string | null;

  overdue: boolean;
}

export interface TodayNurseTask {
  id: number;
  resident_id: number;
  resident: string | null;

  task_name: string;
  description: string | null;
  priority: string | null;
  status: string;

  scheduled_time: string | null;

  ai_generated: boolean;
  task_type: string | null;
  source_type: string | null;

  care_plan_id: number | null;
  occurrence_key: string | null;

  overdue: boolean;
}

export interface TodayResponse {
  date: string;

  summary: TodaySummary;

  medication_rounds: {
    AM: TodayMedicationRound;
    PM: TodayMedicationRound;
    NIGHT: TodayMedicationRound;
  };

  due_checks: {
    weekly_vitals: TodayDueCheck[];
    monthly_glucose: TodayDueCheck[];
  };

  home_leave: TodayHomeLeave[];

  care_activities: {
    due: TodayCareActivity[];
    overdue: TodayCareActivity[];
  };

  tasks: TodayNurseTask[];
}