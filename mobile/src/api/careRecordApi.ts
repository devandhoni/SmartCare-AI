import api from "./api";

import type {
  CareRecord,
  CareRecordInput,
  CareRecordsListResponse,
  CareRecordMutationResponse,
} from "../types/careRecord";

export async function getResidentCareRecords(
  residentId: number
): Promise<CareRecordsListResponse> {
  const response = await api.get<CareRecordsListResponse>(
    `/residents/${residentId}/care-records`
  );

  return response.data;
}

export async function getCareRecord(
  recordId: number
): Promise<CareRecord> {
  const response = await api.get<CareRecord>(
    `/care-records/${recordId}`
  );

  return response.data;
}

export async function createCareRecord(
  residentId: number,
  input: CareRecordInput
): Promise<CareRecordMutationResponse> {
  const response = await api.post<CareRecordMutationResponse>(
    `/residents/${residentId}/care-records`,
    input
  );

  return response.data;
}

export async function updateCareRecord(
  recordId: number,
  input: Partial<CareRecordInput>
): Promise<CareRecordMutationResponse> {
  const response = await api.put<CareRecordMutationResponse>(
    `/care-records/${recordId}`,
    input
  );

  return response.data;
}

export async function deleteCareRecord(
  recordId: number
): Promise<{ message: string }> {
  const response = await api.delete<{ message: string }>(
    `/care-records/${recordId}`
  );

  return response.data;
}