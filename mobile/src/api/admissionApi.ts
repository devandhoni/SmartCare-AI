import api from "./api";

export interface AdmissionDraft {
  id: number;
  draft_reference: string;
  full_name: string | null;
  current_step: number;
  form_data: Record<string, unknown>;
  status: string;
  updated_at: string;
  creator?: { id: number; full_name: string } | null;
  updater?: { id: number; full_name: string } | null;
}
export interface Admission {
  id: number;
  admission_reference?: string;
  admission_number?: string;
  resident_id: number;
  admitted_at: string;
  admission_type: string | null;
  status: string;
  resident?: { id: number; full_name: string; status: string } | null;
}
export interface Room { id: number; room_number: string; floor?: string | null; room_type?: string | null }
export interface Medicine { id: number; name?: string; medication_name?: string; generic_name?: string; brand_name?: string; strength?: string; dosage_form?: string }
export async function getAdmissionDrafts(): Promise<AdmissionDraft[]> {
  const r = await api.get<{ drafts: AdmissionDraft[] }>("/admission-drafts");
  return r.data.drafts ?? [];
}
export async function getAdmissions(): Promise<Admission[]> {
  const r = await api.get<{ admissions: Admission[] }>("/admissions");
  return r.data.admissions ?? [];
}
export async function getAdmissionDraft(id: number): Promise<AdmissionDraft> {
  const r = await api.get<{ draft: AdmissionDraft }>(`/admission-drafts/${id}`);
  return r.data.draft;
}
export async function deleteAdmissionDraft(id: number): Promise<void> {
  await api.delete(`/admission-drafts/${id}`);
}
export async function saveAdmissionDraft(payload: { full_name: string | null; current_step: number; form_data: Record<string, unknown> }, id?: number | null): Promise<AdmissionDraft> {
  const r = id ? await api.put(`/admission-drafts/${id}`, payload) : await api.post("/admission-drafts", payload);
  if (!r.data?.draft?.id) throw new Error("Draft was saved without an ID.");
  return r.data.draft;
}
export async function getAdmissionRooms(): Promise<Room[]> {
  const r = await api.get("/rooms");
  const value = r.data?.rooms ?? r.data;
  return Array.isArray(value) ? value : [];
}
export async function getAdmissionMedicines(): Promise<Medicine[]> {
  const r = await api.get("/medications");
  const value = r.data?.data ?? r.data?.medications ?? r.data;
  return Array.isArray(value) ? value : [];
}
export function admissionError(err: unknown): string {
  const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } }; message?: string };
  const errors = e.response?.data?.errors;
  const first = errors ? Object.values(errors).flat().find(Boolean) : undefined;
  return first || e.response?.data?.message || e.message || "The request could not be completed.";
}
