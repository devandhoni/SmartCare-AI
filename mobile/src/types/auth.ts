export type UserRole = "Administrator" | "Nurse";

export interface AuthUser {
  id: number;
  name?: string;
  full_name?: string;
  email: string;
  role: UserRole;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}