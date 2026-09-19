import api from "../services/api";

export const getStaff = async () => {
    const response = await api.get("/staff");
    return response.data;
};

export const getStaffMember = async (id) => {
    const response = await api.get(`/staff/${id}`);
    return response.data;
};

export const createStaff = async (data) => {
    const response = await api.post("/staff", data);
    return response.data;
};

export const updateStaff = async (id, data) => {
    const response = await api.put(`/staff/${id}`, data);
    return response.data;
};

export const updateStaffStatus = async (id, status) => {
    const response = await api.put(`/staff/${id}/status`, {
        status,
    });

    return response.data;
};

export const resetStaffPassword = async (id, data) => {
    const response = await api.put(
        `/staff/${id}/reset-password`,
        data
    );

    return response.data;
};