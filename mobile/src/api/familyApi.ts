import api from "./api";

export type ResidentOption = { id: number; full_name: string; status?: string };
export type FamilyContact = {
  id: number; resident_id: number; full_name: string; relationship: string; phone: string;
  whatsapp_number: string | null; is_primary: boolean; is_emergency_contact: boolean;
  whatsapp_enabled: boolean; medication_notifications_enabled: boolean;
  care_notifications_enabled: boolean; notes: string | null;
};
export type ContactInput = Omit<FamilyContact, "id" | "resident_id">;
export type FamilyMessage = {
  id: number; resident_id: number; recipient_name: string; recipient_number: string;
  message: string; status: "PENDING" | "SENT" | "FAILED" | "SKIPPED";
  provider?: string | null; attempt_count?: number; failure_reason?: string | null;
  created_at?: string; sent_at?: string | null; failed_at?: string | null;
  resident?: { full_name: string } | null;
};
export type FamilySummary = { total: number; pending: number; sent: number; failed: number; skipped: number };
export const emptySummary: FamilySummary = { total: 0, pending: 0, sent: 0, failed: 0, skipped: 0 };
function unwrap<T>(data: any, key: string): T[] {
  const value = Array.isArray(data) ? data : data?.[key] ?? data?.data;
  return Array.isArray(value) ? value : Array.isArray(value?.data) ? value.data : [];
}
export async function getResidents(): Promise<ResidentOption[]> {
  const { data } = await api.get("/residents");
  return unwrap<ResidentOption>(data, "residents");
}
export async function getContacts(residentId: number): Promise<FamilyContact[]> {
  const { data } = await api.get(`/residents/${residentId}/contacts`);
  return unwrap<FamilyContact>(data, "contacts");
}
export async function addContact(residentId: number, payload: ContactInput) {
  await api.post(`/residents/${residentId}/contacts`, payload);
}
export async function editContact(contactId: number, payload: ContactInput) {
  await api.put(`/resident-contacts/${contactId}`, payload);
}
export async function removeContact(contactId: number) {
  await api.delete(`/resident-contacts/${contactId}`);
}
export async function getFamilyMessages(params: { status?: string; search?: string; page?: number; resident_id?: number } = {}) {
  const { data } = await api.get("/family-messages", { params: { per_page: 25, ...params } });
  return {
    messages: unwrap<FamilyMessage>(data, "messages"),
    summary: (data?.summary ?? emptySummary) as FamilySummary,
    currentPage: Number(data?.messages?.current_page ?? 1),
    lastPage: Number(data?.messages?.last_page ?? 1),
  };
}
export async function retryFamilyMessage(id: number) {
  const { data } = await api.post(`/family-messages/${id}/retry`);
  return String(data?.message ?? "Retry completed");
}
export function familyError(error: unknown): string {
  const e = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } }; message?: string };
  const errors = e?.response?.data?.errors;
  return (errors ? Object.values(errors).flat()[0] : undefined) || e?.response?.data?.message || e?.message || "Request failed";
}
