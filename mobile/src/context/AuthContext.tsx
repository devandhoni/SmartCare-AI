import React, {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import * as SecureStore from "expo-secure-store";

import { loginUser } from "../api/authApi";
import type {
  AuthUser,
  LoginCredentials,
} from "../types/auth";

const TOKEN_KEY = "token";
const USER_KEY = "user";

interface AuthContextValue {
  user: AuthUser | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (credentials: LoginCredentials) => Promise<AuthUser>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(
  undefined
);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({
  children,
}: AuthProviderProps) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    restoreSession();
  }, []);

  const restoreSession = async () => {
    try {
      const [storedToken, storedUser] = await Promise.all([
        SecureStore.getItemAsync(TOKEN_KEY),
        SecureStore.getItemAsync(USER_KEY),
      ]);

      if (!storedToken || !storedUser) {
        return;
      }

      const parsedUser = JSON.parse(storedUser) as AuthUser;

      if (
        !parsedUser ||
        !parsedUser.id ||
        !parsedUser.email ||
        !parsedUser.role
      ) {
        await clearSession();
        return;
      }

      setToken(storedToken);
      setUser(parsedUser);
    } catch (error) {
      console.error(
        "Failed to restore authentication session:",
        error
      );

      await clearSession();
    } finally {
      setIsLoading(false);
    }
  };

  const clearSession = async () => {
    await Promise.all([
      SecureStore.deleteItemAsync(TOKEN_KEY),
      SecureStore.deleteItemAsync(USER_KEY),
    ]);

    setToken(null);
    setUser(null);
  };

  const login = async (
    credentials: LoginCredentials
  ): Promise<AuthUser> => {
    const result = await loginUser(credentials);

    if (!result.token || !result.user) {
      throw new Error(
        "Invalid login response from server."
      );
    }

    if (
      result.user.role !== "Administrator" &&
      result.user.role !== "Nurse"
    ) {
      throw new Error("Unsupported user role.");
    }

    await Promise.all([
      SecureStore.setItemAsync(
        TOKEN_KEY,
        result.token
      ),
      SecureStore.setItemAsync(
        USER_KEY,
        JSON.stringify(result.user)
      ),
    ]);

    setToken(result.token);
    setUser(result.user);

    return result.user;
  };

  const logout = async () => {
    await clearSession();
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        isAuthenticated: Boolean(token && user),
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error(
      "useAuth must be used inside AuthProvider."
    );
  }

  return context;
}