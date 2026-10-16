import api from "./api";

export type AIRecord = Record<string, unknown>;
export const unwrapAI = (response: unknown): AIRecord => {
  const root = (response ?? {}) as AIRecord;
  const data = root.data;
  return data && typeof data === "object" && !Array.isArray(data) ? data as AIRecord : root;
};
export const getAIOverview = async () => unwrapAI((await api.get("/dashboard/intelligence")).data);
export const getAIAlerts = async (): Promise<AIRecord[]> => {
  const result = unwrapAI((await api.get("/ai-alerts")).data);
  return Array.isArray(result.alerts) ? result.alerts as AIRecord[] : [];
};
export const acknowledgeAIAlert = async (id: number) => api.put(`/ai-alerts/${id}/acknowledge`);
export const resolveAIAlert = async (id: number, resolution_note: string) => api.put(`/ai-alerts/${id}/resolve`, { resolution_note });
export const getResidentRisk = async (id: number) => unwrapAI((await api.get(`/residents/${id}/risk-dashboard`)).data);
export const getResidentPrediction = async (id: number) => unwrapAI((await api.get(`/residents/${id}/deterioration-prediction`)).data);
export const getNurseRecommendation = async (id: number) => unwrapAI((await api.get(`/residents/${id}/nurse-recommendation`)).data);
