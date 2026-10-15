import api from "./api";
import type {
  NurseTask,
  NurseTasksResponse,
} from "../types/nurseTask";

export async function getNurseTasks(): Promise<NurseTask[]> {
  const response = await api.get<NurseTasksResponse>(
    "/nurse/tasks"
  );

  const tasks = response.data?.data;

  if (!Array.isArray(tasks)) {
    throw new Error(
      "Unexpected nurse tasks response from Laravel."
    );
  }

  return tasks;
}

export async function getNurseTask(
  taskId: number
): Promise<NurseTask> {
  const response = await api.get(
    `/nurse/tasks/${taskId}`
  );

  const data = response.data?.data;

  if (data?.task && typeof data.task === "object") {
    return data.task as NurseTask;
  }

  if (data && typeof data === "object" && data.id) {
    return data as NurseTask;
  }

  throw new Error(
    "Unexpected nurse task details response."
  );
}


export interface CompleteNurseTaskInput {
  completion_notes?: string;
  care_status?: "COMPLETED" | "OBSERVED" | "NEEDS_ATTENTION";
  care_type?: string;
  care_title?: string;
}

export interface RecordNurseTaskOutcomeInput {
  outcome_status: "IMPROVED" | "STABLE" | "DETERIORATED" | "UNKNOWN";
  outcome_notes?: string;
}

export async function acceptNurseTask(taskId: number) {
  const response = await api.put(`/nurse/tasks/${taskId}/accept`);
  return response.data;
}

export async function acknowledgeNurseTask(taskId: number) {
  const response = await api.put(`/nurse/tasks/${taskId}/acknowledge`);
  return response.data;
}

export async function completeNurseTask(
  taskId: number,
  input: CompleteNurseTaskInput
) {
  const response = await api.put(
    `/nurse/tasks/${taskId}/complete`,
    input
  );

  return response.data;
}

export async function recordNurseTaskOutcome(
  taskId: number,
  input: RecordNurseTaskOutcomeInput
) {
  const response = await api.post(
    `/nurse/tasks/${taskId}/outcome`,
    input
  );

  return response.data;
}