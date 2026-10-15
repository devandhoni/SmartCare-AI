import api from "./api";

import type {
  CarePlan,
  CarePlanInput,
  CarePlanDetailResponse,
  CarePlansListResponse,
  CarePlanMutationResponse,
} from "../types/carePlan";

// Get all care plans for a resident
export async function getResidentCarePlans(
  residentId: number
): Promise<CarePlansListResponse> {
  const response = await api.get<CarePlansListResponse>(
    `/residents/${residentId}/care-plans`
  );

  return response.data;
}

// Get one care plan
export async function getCarePlan(
  planId: number
): Promise<CarePlan> {
  const response = await api.get<CarePlanDetailResponse>(
    `/care-plans/${planId}`
  );

  return response.data.care_plan;
}

// Create a new care plan
export async function createCarePlan(
  residentId: number,
  input: CarePlanInput
): Promise<CarePlanMutationResponse> {
  const response = await api.post<CarePlanMutationResponse>(
    `/residents/${residentId}/care-plans`,
    input
  );

  return response.data;
}

// Update an existing care plan
export async function updateCarePlan(
  planId: number,
  input: Partial<CarePlanInput>
): Promise<CarePlanMutationResponse> {
  const response = await api.put<CarePlanMutationResponse>(
    `/care-plans/${planId}`,
    input
  );

  return response.data;
}

// Deactivate a care plan (preserve history)
export async function deactivateCarePlan(
  planId: number
): Promise<CarePlanMutationResponse> {
  const response = await api.put<CarePlanMutationResponse>(
    `/care-plans/${planId}/deactivate`
  );

  return response.data;
}

// Reactivate a care plan
export async function activateCarePlan(
  planId: number
): Promise<CarePlanMutationResponse> {
  const response = await api.put<CarePlanMutationResponse>(
    `/care-plans/${planId}/activate`
  );

  return response.data;
}