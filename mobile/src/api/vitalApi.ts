import api from "./api";

import type {
  CreateVitalPayload,
  CreateVitalResponse,
  ResidentVitalsResponse,
  VitalSign,
} from "../types/vital";

export async function getResidentVitals(
  residentId: number
): Promise<VitalSign[]> {
  const response =
    await api.get<ResidentVitalsResponse>(
      `/residents/${residentId}/vitals`
    );

  const vitals = Array.isArray(
    response.data?.vitals
  )
    ? response.data.vitals
    : [];

  return [...vitals].sort((a, b) => {
    const aDate = new Date(
      a.recorded_at ||
        a.created_on ||
        a.created_at ||
        0
    ).getTime();

    const bDate = new Date(
      b.recorded_at ||
        b.created_on ||
        b.created_at ||
        0
    ).getTime();

    return bDate - aDate;
  });
}

export async function createResidentVital(
  residentId: number,
  payload: CreateVitalPayload
): Promise<VitalSign> {
  const response =
    await api.post<CreateVitalResponse>(
      `/residents/${residentId}/vitals`,
      payload
    );

  return response.data.vital;
}