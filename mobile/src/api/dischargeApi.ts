import api from "./api";

export type DischargeForm = {
  resident_id: string;
  discharged_at: string;
  discharge_type: string;
  discharge_destination: string;
  reason_for_discharge: string;
  condition_at_discharge: string;
  treatment_care_summary: string;
  medical_summary: string;
  follow_up_instructions: string;
  medication_summary: string;
  medication_instructions: string;
  discharged_to: string;
  discharged_to_relationship: string;
  discharged_to_contact: string;
  belongings_returned: boolean;
  belongings_notes: string;
  administrative_notes: string;
};

export type Discharge = Partial<Omit<DischargeForm, "resident_id">> & {
  id: number;
  resident_id: number;
  discharge_number: string;
  status: "DRAFT" | "COMPLETED" | string;
  updated_at?: string;
  completed_at?: string | null;
  resident?: Resident | null;
  room?: { id: number; room_number: string } | null;
};

export type Resident = {
  id: number;
  full_name: string;
  status: string;
  room_id?: number | null;
  admission_date?: string | null;
  ic_number?: string | null;
  medical_condition?: string | null;
  chronic_disease?: string | null;
  allergies?: string | null;
  medical_notes?: string | null;
  emergency_contact?: string | null;
  emergency_relationship?: string | null;
  emergency_phone?: string | null;
};
export type Contact = { full_name: string; relationship?: string | null; phone?: string | null; is_primary?: boolean };
export type ResidentMedication = {
  id: number;
  medication_id?: number;
  medication?: { medicine_name?: string; dosage?: string; unit?: string } | null;
  dosage_quantity?: string | number;
  frequency?: string;
  time_slot?: string;
  dosage_instruction?: string;
};

export const emptyDischarge = (): DischargeForm => ({
  resident_id: "", discharged_at: localDateTime(), discharge_type: "PLANNED_DISCHARGE",
  discharge_destination: "", reason_for_discharge: "", condition_at_discharge: "",
  treatment_care_summary: "", medical_summary: "", follow_up_instructions: "",
  medication_summary: "", medication_instructions: "", discharged_to: "",
  discharged_to_relationship: "", discharged_to_contact: "", belongings_returned: false,
  belongings_notes: "", administrative_notes: "",
});

export function localDateTime(value?: string | null) {
  if (!value) {
    const d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  }
  // API date-only/local timestamps must not be converted through UTC.
  const normalized = value.replace(" ", "T");
  if (/^\d{4}-\d\d-\d\dT\d\d:\d\d/.test(normalized) && !/[Zz]|[+-]\d\d:\d\d$/.test(normalized)) return normalized.slice(0, 16);
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? "" : new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

export function formFromDischarge(d: Discharge): DischargeForm {
  const base = emptyDischarge();
  for (const key of Object.keys(base) as (keyof DischargeForm)[]) {
    if (key === "resident_id") continue;
    if (key === "belongings_returned") { base.belongings_returned = Boolean(d.belongings_returned); continue; }
    const value = d[key];
    (base as unknown as Record<string, unknown>)[key] = value == null ? "" : String(value);
  }
  base.resident_id = String(d.resident_id);
  base.discharged_at = localDateTime(d.discharged_at);
  return base;
}

function payload(form: DischargeForm) {
  const data: Record<string, string | boolean | null> = {};
  for (const [key, value] of Object.entries(form)) {
    if (key === "resident_id") continue;
    data[key] = typeof value === "boolean" ? value : value.trim() || null;
  }
  return data;
}

export function apiError(error: unknown, fallback: string) {
  const e = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } }; message?: string };
  const first = Object.values(e?.response?.data?.errors ?? {}).flat()[0];
  return first || e?.response?.data?.message || e?.message || fallback;
}

export async function getDischarges() {
  const response = await api.get<{ discharges: Discharge[] }>("/discharges");
  return response.data.discharges ?? [];
}
export async function getDischarge(id: number) {
  const response = await api.get<{ discharge: Discharge }>(`/discharges/${id}`);
  return response.data.discharge;
}
export async function getResidentsForDischarge() {
  const response = await api.get("/residents");
  const data = response.data?.residents ?? response.data?.data ?? response.data;
  return (Array.isArray(data) ? data : data?.data ?? []) as Resident[];
}
export async function getResidentContacts(id: number) {
  const response = await api.get(`/residents/${id}/contacts`);
  return (response.data?.contacts ?? []) as Contact[];
}
export async function getResidentMedications(id: number) {
  const response = await api.get(`/residents/${id}/medications`);
  return (response.data?.medications ?? []) as ResidentMedication[];
}
export async function createDischarge(form: DischargeForm) {
  const response = await api.post<{ discharge: Discharge }>("/discharges", { resident_id: Number(form.resident_id), ...payload(form) });
  return response.data.discharge;
}
export async function updateDischarge(id: number, form: DischargeForm) {
  const response = await api.put<{ discharge: Discharge }>(`/discharges/${id}`, payload(form));
  return response.data.discharge;
}
export async function deleteDischarge(id: number) { await api.delete(`/discharges/${id}`); }
export async function completeDischarge(id: number) {
  const response = await api.post<{ discharge: Discharge }>(`/discharges/${id}/complete`);
  return response.data.discharge;
}
