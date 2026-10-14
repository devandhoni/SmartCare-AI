import api from "./api";
import type {
  Resident,
  ResidentProfileResponse,
} from "../types/resident";

type ResidentsPayload =
  | Resident[]
  | {
      residents?: Resident[] | { data?: Resident[] };
      data?: Resident[];
    };

export async function getResidents(): Promise<Resident[]> {
  const response = await api.get<ResidentsPayload>("/residents");
  const payload = response.data;

  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.residents)) {
    return payload.residents;
  }

  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  if (
    payload?.residents &&
    !Array.isArray(payload.residents) &&
    Array.isArray(payload.residents.data)
  ) {
    return payload.residents.data;
  }

  return [];
}

export async function getResidentProfile(
  residentId: number
): Promise<ResidentProfileResponse> {
  const response = await api.get<ResidentProfileResponse>(
    `/residents/${residentId}/profile`
  );

  return {
    resident: response.data?.resident ?? null,
    latest_vital: response.data?.latest_vital ?? null,
    active_alerts: Array.isArray(response.data?.active_alerts)
      ? response.data.active_alerts
      : [],
    pending_tasks: Array.isArray(response.data?.pending_tasks)
      ? response.data.pending_tasks
      : [],
  };
}