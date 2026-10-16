import api from "./api";

export type TimelineEvent = Record<string, unknown>;
export type TimelineResponse = { resident?: { id: number; name: string }; clinical_summary?: { total_events?: number; critical_events?: number; latest_condition?: string; risk_level?: string }; timeline?: TimelineEvent[] };
export type ResidentDocument = { id: number; resident_id: number; document_type: string; title: string; original_name?: string; mime_type?: string; file_size?: number; notes?: string | null; status?: string; created_at?: string; uploader?: { full_name?: string } };
export async function getResidentTimeline(id: number) { const { data } = await api.get<TimelineResponse>(`/residents/${id}/timeline`); return data; }
export async function getResidentDocuments(id: number) { const { data } = await api.get<{documents?: ResidentDocument[]}>(`/residents/${id}/documents`); return Array.isArray(data.documents) ? data.documents : []; }
export async function updateResidentDocument(id: number, fields: {title: string; document_type: string; notes: string; status: string}) { await api.put(`/resident-documents/${id}`, fields); }
export async function deleteResidentDocument(id: number) { await api.delete(`/resident-documents/${id}`); }
export async function uploadResidentDocument(id: number, file: {uri: string; name: string; mimeType?: string}, fields: {title: string; document_type: string; notes: string}) {
  const form = new FormData();
  form.append("title", fields.title); form.append("document_type", fields.document_type); form.append("notes", fields.notes);
  form.append("document", {uri:file.uri,name:file.name,type:file.mimeType || "application/octet-stream"} as any);
  await api.post(`/residents/${id}/documents`, form, { headers: { "Content-Type": "multipart/form-data" } });
}
