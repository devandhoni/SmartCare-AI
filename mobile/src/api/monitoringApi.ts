import api from "./api";

export type MonitoringResident = {
  id: number; full_name: string; status: string; ic_number?: string | null;
  room?: { room_number?: string | null } | null;
};
export type MonitoringCheck = {
  id: number; resident_id: number; recorded_at: string;
  blood_glucose?: number | string | null;
  glucose_measurement_type?: string | null; glucose_notes?: string | null;
  blood_pressure_systolic?: number | null; blood_pressure_diastolic?: number | null;
  heart_rate?: number | null; oxygen_level?: number | null;
  temperature?: number | null; weight?: number | null;
  resident?: { full_name: string } | null;
};
export type GlucosePayload = {
  blood_glucose: number; glucose_measurement_type: string;
  glucose_notes: string | null; recorded_at: string;
};
export type WeeklyPayload = {
  blood_pressure_systolic: number; blood_pressure_diastolic: number;
  heart_rate: number; oxygen_level: number; temperature: number;
  weight: number; blood_glucose: number | null; recorded_at: string;
};
const unpack = <T>(value: unknown, key: string): T[] => {
  const data = value as Record<string, unknown> | T[] | null;
  if (Array.isArray(data)) return data;
  if (!data || typeof data !== "object") return [];
  const candidate = (data as Record<string, unknown>)[key] ?? (data as Record<string, unknown>).data;
  if (Array.isArray(candidate)) return candidate as T[];
  if (candidate && typeof candidate === "object" && Array.isArray((candidate as {data?: unknown}).data)) return (candidate as {data: T[]}).data;
  return [];
};
export async function getMonitoringResidents(): Promise<MonitoringResident[]> {
  const response = await api.get("/residents");
  return unpack<MonitoringResident>(response.data, "residents").filter(r => String(r.status).toLowerCase() === "active");
}
export async function getMonthlyChecks(month: number, year: number): Promise<MonitoringCheck[]> {
  const response = await api.get("/monthly-glucose-checks", { params: {month, year} });
  return unpack<MonitoringCheck>(response.data, "checks");
}
export async function createMonthlyCheck(residentId: number, payload: GlucosePayload): Promise<void> {
  await api.post(`/residents/${residentId}/monthly-glucose-checks`, payload);
}
export async function getWeeklyChecks(): Promise<MonitoringCheck[]> {
  const response = await api.get("/weekly-vital-checks");
  return unpack<MonitoringCheck>(response.data, "checks");
}
export async function createWeeklyCheck(residentId: number, payload: WeeklyPayload): Promise<void> {
  await api.post(`/residents/${residentId}/weekly-vital-checks`, payload);
}
export function monitoringError(error: unknown): string {
  const e = error as { response?: { data?: {message?: string; errors?: Record<string, string[]>} }; message?: string };
  const errors = e?.response?.data?.errors;
  return (errors && Object.values(errors).flat()[0]) || e?.response?.data?.message || e?.message || "Request failed.";
}
export function localDateTime(): string {
  const d = new Date();
  const pad = (v: number) => String(v).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}
export function displayDateTime(value?: string | null): string {
  if (!value) return "—";
  const d = new Date(value.replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString("en-MY", {day:"2-digit",month:"short",year:"numeric",hour:"2-digit",minute:"2-digit"});
}
export function validDateTime(value: string): boolean {
  return /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/.test(value) && !Number.isNaN(new Date(value.replace(" ","T")).getTime());
}
