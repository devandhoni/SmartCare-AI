import api from "./api";
import type { TodayResponse } from "../types/today";

export async function getToday(): Promise<TodayResponse> {
  const response = await api.get<TodayResponse>("/today");

  return response.data;
}