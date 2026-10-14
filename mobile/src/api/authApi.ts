import api from "./api";
import type {
  LoginCredentials,
  LoginResponse,
} from "../types/auth";

export const loginUser = async (
  credentials: LoginCredentials
): Promise<LoginResponse> => {
  const response = await api.post<LoginResponse>(
    "/login",
    credentials
  );

  return response.data;
};