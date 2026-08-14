import api from "../services/api";

export const loginUser = async (data) => {
  const response = await api.post("/login", data);
  return response.data;
};